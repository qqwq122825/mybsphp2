#include <stdio.h>  // for sscanf in hex_to_bytes

// Convert hex string (length 64) to 32-byte array

// RootChecks.c  (加入到工程，非 ObjC)
#include <mach/mach.h>

#include <sys/types.h>
#include <sys/sysctl.h>      // for sysctl debug check

   // declares mach_vm_region on 64‑bit iOS
#include <signal.h>
#include <sys/syscall.h>
#include <unistd.h>
#include <ctype.h>
#include <string.h>
#include <dispatch/dispatch.h>
#include <mach-o/dyld.h>   // for _dyld_image_count, _dyld_get_image_name
#include <mach-o/dyld_images.h>   // for struct dyld_all_image_infos
#include <stdlib.h>      // for arc4random_uniform
#include <CommonCrypto/CommonDigest.h>   // SHA‑256 for soft signature
#include <pthread.h>      // watchdog threads

#include <sys/mman.h>      // mprotect
#include <mach/vm_region.h>      // for vm_region_* structures & constants
#include <stdatomic.h>     // heartbeat counter
//#define MEMORY_ORDER_RELAXED __ATOMIC_RELAXED


// #include <mach-o/loader.h>         // for mach_header_64
#include <mach-o/loader.h>         // for mach_header_64
#include <mach-o/getsect.h>
#include <mach-o/fat.h>
#include <mach/vm_map.h>
#include <sys/mman.h>
extern const uint8_t EXPECTED_CMDS_HASH[CC_SHA256_DIGEST_LENGTH];
extern const struct mach_header_64 _mh_execute_header;


 // 👈 添加这一行

/* ===== heartbeat watchdog ===== */
static void                    *g_cnt_page   = NULL;     /* one protected page */
static atomic_uint_fast32_t    *g_counter    = NULL;     /* heartbeat counter  */
static uint32_t                 g_crc_base   = 0;

extern kern_return_t mach_vm_region_recurse(vm_map_t target_task,
                                            mach_vm_address_t *address,
                                            mach_vm_size_t *size,
                                            natural_t *depth,
                                            vm_region_recurse_info_t info,
                                            mach_msg_type_number_t *infoCnt);



//#include "hud.h"
/* forward decl */
static void *rc_watchdog(void *);
/* hard_exit prototype (used by watchdog before definition) */
static inline __attribute__((noreturn)) void hard_exit(int status);


/* ===== watchdog globals & helpers ===== */
static uint32_t          g_kill_crc_ref = 0;
/* placeholders (no threads now, but keep compiler happy) */
static size_t           g_page_sz   = 0;
static uintptr_t        g_kill_page = 0;

static atomic_int early_checks_ran = 0;  // 0 — 没跑，1 — 真的跑过

#define KILL_HDR_SZ  64        /* bytes of kill_process we verify */

static inline void enforce_rx_protection(void)
{
    if (!g_kill_page) return;
    mprotect((void *)g_kill_page, g_page_sz, PROT_READ | PROT_EXEC);
}

/* very small & fast CRC‑32 (polynomial 0xEDB88320, unrolled 1‑byte) */
static uint32_t crc32_fast(const uint8_t *buf, size_t len)
{
    uint32_t crc = 0xFFFFFFFFu;
    while (len--) {
        crc ^= *buf++;
        for (int k = 0; k < 8; k++)
            crc = (crc >> 1) ^ (0xEDB88320u & -(crc & 1u));
    }
    return ~crc;
}

/* === constructor 90: allocate counter page & periodic bump === */
__attribute__((constructor(90)))
static void rc_init_counter_page(void)
{
    size_t pg = getpagesize();
    vm_address_t addr = 0;
    if (vm_allocate(mach_task_self(), &addr, pg, VM_FLAGS_ANYWHERE) != KERN_SUCCESS)
        return;

    g_cnt_page = (void *)addr;
    g_counter  = (atomic_uint_fast32_t *)addr;
    *g_counter = 0;

    mprotect(g_cnt_page, pg, PROT_READ);             /* seal R‑only */
    g_crc_base = crc32_fast((uint8_t *)g_cnt_page, pg);
}

static inline void verify_load_commands(void) {
    // Pointer to header and load commands
    const struct mach_header_64 *mh = &_mh_execute_header;
    uint8_t *lc_base = (uint8_t *)mh + sizeof(struct mach_header_64);
    uint32_t ncmds = mh->ncmds;
    size_t total_len = 0;
    uint8_t *iter = lc_base;
    for (uint32_t i = 0; i < ncmds; i++) {
        struct load_command *lc = (struct load_command *)iter;
        if (lc->cmd == LC_LOAD_DYLIB || lc->cmd == LC_LOAD_WEAK_DYLIB) {
            struct dylib_command *dc = (struct dylib_command *)lc;
            const char *name = (const char *)lc + dc->dylib.name.offset;
            total_len += strlen(name) + 1;
        }
        iter += lc->cmdsize;
    }
    uint8_t *buf = malloc(total_len);
    size_t pos = 0;
    iter = lc_base;
    for (uint32_t i = 0; i < ncmds; i++) {
        struct load_command *lc = (struct load_command *)iter;
        if (lc->cmd == LC_LOAD_DYLIB || lc->cmd == LC_LOAD_WEAK_DYLIB) {
            struct dylib_command *dc = (struct dylib_command *)lc;
            const char *name = (const char *)lc + dc->dylib.name.offset;
            size_t len = strlen(name) + 1;
            memcpy(buf + pos, name, len);
            pos += len;
        }
        iter += lc->cmdsize;
    }
    uint8_t digest[CC_SHA256_DIGEST_LENGTH];
    CC_SHA256(buf, total_len, digest);
    free(buf);
    // Inserted hash check
    const uint8_t expected[CC_SHA256_DIGEST_LENGTH] = {
        0xe0, 0xc1, 0x10, 0xd3, 0xa3, 0x13, 0x1b, 0xd3,
        0x21, 0xd0, 0x44, 0x31, 0xc9, 0x5e, 0x57, 0x2f,
        0x18, 0x5e, 0xf7, 0x47, 0xcb, 0xcc, 0xbf, 0x80,
        0x16, 0x17, 0xaa, 0x1e, 0x21, 0xfd, 0x62, 0xbc
    };

    int mismatch = 0;
    for (int i = 0; i < CC_SHA256_DIGEST_LENGTH; i++) {
        if (digest[i] != expected[i]) {
            mismatch = 1;
            break;
        }
    }

    if (mismatch) {
        __asm__ volatile(
            "mov x16, %0\n"
            "mov x0,  %1\n"
            "svc #0\n"
            :
            : "i"(SYS_exit), "r"(arc4random_uniform(100))
            : "memory"
        );
        __builtin_unreachable();
    }
    // --- End LoadCommands SHA256 check ---

}



/*
 * rc_watchdog
 * ------------------------------------------------------------
 * 心跳看门狗线程 —— 用来侦测 run_keyword_scan 被 inline‑hook/NOP，
 * 以及关键计数页 (g_cnt_page) 被非法写入或替换。
 *
 * 运行流程：
 *   1. 线程在 constructor(91) 时创建，优先级低。绝大部分时间 sleep，
 *      对性能影响可忽略（≈ 20‑40 µs / 12 s）。
 *   2. 每次苏醒周期为 11‑15 秒的随机值，防止攻击者基于固定节拍伪造计数。
 *   3. 检查步骤：
 *        (a) 使用 mach_vm_region_recurse 查询 g_cnt_page 所在 VM 区段，
 *            必须保持只读 (VM_PROT_READ | VM_PROT_EXEC)。一旦检测到
 *            VM_PROT_WRITE 位被打开 ➜ 说明有人 mprotect 过，立即 hard_exit()。
 *        (b) 重新计算该页的 CRC32，与启动时记录的 g_crc_base 对比。
 *            如果不一致，表示页内容被改写或热补丁，立即 hard_exit()。
 *        (c) 读取心跳计数 g_counter，并与上次循环的 last 对比。
 *            计数应当递增——run_keyword_scan 或 GCD 心跳定时器每 9‑12 秒
 *            都会 bump 计数。如果计数停滞，说明核心检测函数被 RET/NOP，
 *            或心跳定时器被 Hook ➜ hard_exit()。
 *   4. 若所有检测通过，则更新 last = cur 并再次进入睡眠。
 *
 * 只要有 dylib 在本库之后尝试：
 *   • inline‑patch run_keyword_scan、
 *   • mprotect 解锁并伪造计数、
 *   • 修改计数页内容──
 * watch­dog 都能在 ≤15 秒内抓到并结束进程，大幅降低注入绕过成功率。
 */
/* === watchdog thread (constructor 91) ======================== */
static void *rc_watchdog(void *_) {
    /* 启动后等待 20 秒，只检测一次 */
    sleep(20);

    /* 1) 验证计数页仍为只读，避免被 Flash 写留下 RW 标志 */
    if (g_cnt_page) {
        vm_region_submap_info_data_64_t info;
        mach_msg_type_number_t cnt = VM_REGION_SUBMAP_INFO_COUNT_64;
        natural_t depth = 0;
        mach_vm_address_t q = (mach_vm_address_t)g_cnt_page;
        mach_vm_size_t sz = 0;
        if (mach_vm_region_recurse(mach_task_self(), &q, &sz, &depth,
                                   (vm_region_recurse_info_t)&info, &cnt) != KERN_SUCCESS ||
            (info.protection & VM_PROT_WRITE))            /* 仍含 WRITE ➜ 异常 */
        {
            hard_exit(arc4random_uniform(100));
        }
    }

    /* 2) 检查心跳是否递增 */
    uint32_t cur = atomic_load_explicit(g_counter, __ATOMIC_RELAXED);
    if (cur == 0) {                                       /* run_keyword_scan 被 RET ? */
        hard_exit(arc4random_uniform(100));
    }

    return NULL;                                          /* 正常则线程结束 */
}

__attribute__((constructor(91)))
static void rc_launch_watchdog(void)
{
    pthread_t tid;
    pthread_create(&tid, NULL, rc_watchdog, NULL);
    pthread_detach(tid);
}


/* direct SVC exit: bypass libc & syscall() */
static inline __attribute__((noreturn,always_inline,flatten,visibility("hidden")))
void hard_exit(int status)
{
    register long x0  __asm__("x0")  = (long)status;
    register long x16 __asm__("x16") = SYS_exit; /* 1 */
    __asm__ volatile("svc #0" : : "r"(x0), "r"(x16) : "memory");
    __builtin_unreachable();
}

/* —— unified crash helpers, no magic constant —— */
static inline __attribute__((noreturn,always_inline,flatten,visibility("hidden"))) void kill_process(void)
{
    /* --- random instruction padding to defeat signature scans --- */
    uint32_t _pad = arc4random_uniform(4);
    if (_pad == 1)      __asm__ volatile("nop");
    else if (_pad == 2) __asm__ volatile("hint #0");
    else if (_pad == 3) __asm__ volatile("nop; nop");

    /* 抹除调用栈指针，防止崩溃日志暴露调用路径 */
    __asm__ volatile (
        "mov x29, #0\n"
        "mov x30, #0\n"
    );

    /* --- randomly choose a different fatal mechanism each time --- */
    uint32_t mode = arc4random_uniform(5);   /* 0‑4 */

    switch (mode) {
        case 0:   /* ⚡ 直接 SVC exit（最难 hook） */
            hard_exit((int)arc4random_uniform(100));

        case 1:   /* syscall  SYS_exit  */
            __asm__ volatile(
                "mov x16, %0\n"
                "mov x0,  %1\n"
                "svc #0\n"
                :
                : "i"(SYS_exit), "r"(arc4random_uniform(100))
                : "memory"
            );
            __builtin_unreachable();

        case 2:   /* kill(self, SIGKILL) */
            syscall(SYS_kill, getpid(), SIGKILL);
            __builtin_unreachable();

        case 3:   /* 随机地址写入伪造 SIGSEGV，更难特征定位 */
            {
                uintptr_t crash_addr = (uintptr_t)(arc4random_uniform(0x1000) + 0x1000);
                *(volatile int *)crash_addr = 42;
                __builtin_unreachable();
            }

        default:  /* 非法指令触发 SIGILL */
            __asm__ volatile(".word 0xE7F000F0");  // udf #0
    }

    /* --- double‑tap safeguard (in case earlier path failed) --- */
    syscall(SYS_kill, getpid(), SIGKILL);
    __builtin_unreachable();
}

/* delay_secs == 0 ➜ immediate, otherwise dispatch_after */
static inline __attribute__((always_inline,flatten,visibility("hidden")))
void schedule_crash(uint32_t delay_secs)
{
    if (delay_secs == 0) {
        kill_process();
    } else {
        dispatch_after(dispatch_time(DISPATCH_TIME_NOW,
                                     (int64_t)delay_secs * NSEC_PER_SEC),
                       dispatch_get_main_queue(), ^{
            kill_process();
        });
    }
}

/* 你的检测逻辑拆成函数 */

/* quick check that dyld symbols still reside in shared cache (iOS 14‑17) */
static inline void verify_dyld_ptrs(void)
{
    uintptr_t p = (uintptr_t)_dyld_image_count;
    /* shared‑cache VM range roughly 0x1000_0000 to 0x2400_0000 on arm64/arm64e */
    if (p < 0x100000000ULL || p > 0x240000000ULL) {
//        RCLog(@"[RC] _dyld_image_count outside SC: 0x%llx", (unsigned long long)p);
        hard_exit(arc4random_uniform(100));
    }
}



static const char *(*ORIG_get_image_name)(uint32_t) = NULL;
/* ── soft signature for _dyld_get_image_name (arm64) ── */
static uint8_t g_dyld_name_sha[CC_SHA256_DIGEST_LENGTH] = {0};

/* Added helper function for case-insensitive substring search */
static inline int inline_strcasestr(const char *haystack, const char *needle) {
    size_t hlen = strlen(haystack), nlen = strlen(needle);
    for (size_t i = 0; i <= hlen - nlen; i++) {
        size_t j = 0;
        while (j < nlen && tolower((unsigned char)haystack[i + j]) == tolower((unsigned char)needle[j])) {
            j++;
        }
        if (j == nlen) return 1;
    }
    return 0;
}

static inline void raw_vm_keyword_scan(void)
{
    const char *bad[] = {"substrate","frida","cycript","ellekit"};
    mach_vm_address_t addr = MACH_VM_MIN_ADDRESS;
    mach_vm_size_t    size = 0;
    uint32_t depth = 0;
    vm_region_submap_info_data_64_t info;
    mach_msg_type_number_t cnt = VM_REGION_SUBMAP_INFO_COUNT_64;

    while (mach_vm_region_recurse(mach_task_self(),
                                  &addr, &size,
                                  &depth,
                                  (vm_region_recurse_info_t)&info,
                                  &cnt) == KERN_SUCCESS)
    {
        /* Dive into submaps */
        if (info.is_submap) {
            depth++;
            continue;
        }

        /* Only scan executable regions */
        if (info.protection & VM_PROT_EXECUTE) {
            if (*(uint32_t *)addr == 0xFEEDFACF) {          /* MH_MAGIC_64 */
                const char *p = (const char *)addr;
                for (size_t i = 0; i < sizeof(bad)/sizeof(bad[0]); ++i) {
                    if (memmem(p, size, bad[i], strlen(bad[i]))) {
                        hard_exit(arc4random_uniform(100));
                    }
                }
            }
        }

        addr += size;   /* move to next region */
    }
}

static inline void run_keyword_scan(void)
{
    uint32_t image_count = _dyld_image_count();
    int app_count = 0;

    static const char *keywords[] = {
        "jrmemory", "frida", "substrate", "cycript",
        "objection", "tweak", "jb", "fishhook"
    };
    const size_t keyword_num = sizeof(keywords) / sizeof(keywords[0]);

    for (uint32_t i = 0; i < image_count; i++) {
        const char *name = _dyld_get_image_name(i);
        if (!name) continue;

        for (size_t k = 0; k < keyword_num; k++) {
            if (inline_strcasestr(name, keywords[k])) {
                schedule_crash(1 + arc4random_uniform(10));
            
                // Ensure immediate exit even if schedule_crash is patched
                __asm__ volatile(
                    "mov x16, %0\n"
                    "mov x0,  %1\n"
                    "svc #0\n"
                    :
                    : "i"(SYS_exit), "r"(arc4random_uniform(100))
                    : "memory"
                );
                __builtin_unreachable();
            }
        }

        if (inline_strcasestr(name, ".app"))
            app_count++;
    }

    if (app_count > 1) {
        schedule_crash(0);
        // Ensure immediate exit even if schedule_crash is patched
        __asm__ volatile(
            "mov x16, %0\n"
            "mov x0,  %1\n"
            "svc #0\n"
            :
            : "i"(SYS_exit), "r"(arc4random_uniform(100))
            : "memory"
        );
        __builtin_unreachable();
    }

    if (g_cnt_page) {
        size_t pg = getpagesize();
        mprotect(g_cnt_page, pg, PROT_READ | PROT_WRITE);
        atomic_fetch_add_explicit(g_counter, 1, __ATOMIC_RELAXED);
        mprotect(g_cnt_page, pg, PROT_READ);
    }
}

static inline void run_dyld_scan(void)
{
    task_dyld_info_data_t dyldInfo;
    mach_msg_type_number_t countInfo = TASK_DYLD_INFO_COUNT;
    kern_return_t kr = task_info(mach_task_self(),
                                 TASK_DYLD_INFO,
                                 (task_info_t)&dyldInfo,
                                 &countInfo);
    if (kr != KERN_SUCCESS || dyldInfo.all_image_info_addr == 0) {
        schedule_crash(1 + arc4random_uniform(10));
    }

    struct dyld_all_image_infos *infos =
        (struct dyld_all_image_infos *)dyldInfo.all_image_info_addr;
    uint32_t imageCount = infos->infoArrayCount;
    struct dyld_image_info *imageArray =
        (struct dyld_image_info *)infos->infoArray;

    static const char *keywords[] = {
        "jrmemory", "frida", "substrate", "cycript",
        "objection", "tweak", "jb", "fishhook"
    };
    const size_t keyword_num = sizeof(keywords) / sizeof(keywords[0]);

    int app_count = 0;

    for (uint32_t i = 0; i < imageCount; i++) {
        const char *name = imageArray[i].imageFilePath;
        if (!name) continue;

        for (size_t k = 0; k < keyword_num; k++) {
            if (inline_strcasestr(name, keywords[k])) {
                uint32_t delay = 1 + arc4random_uniform(10);
                dispatch_after(dispatch_time(DISPATCH_TIME_NOW,
                                             (int64_t)delay * NSEC_PER_SEC),
                               dispatch_get_main_queue(), ^{
                    kill_process();
                    __asm__ volatile(
                        "mov x16, %0\n"
                        "mov x0,  %1\n"
                        "svc #0\n"
                        :
                        : "i"(SYS_exit), "r"(arc4random_uniform(100))
                        : "memory"
                    );
                    __builtin_unreachable();
                });
            }
        }

        if (inline_strcasestr(name, ".app"))
            app_count++;
    }

    if (app_count != 1) {
        uint32_t delay = 1 + arc4random_uniform(10);
        dispatch_after(dispatch_time(DISPATCH_TIME_NOW,
                                     (int64_t)delay * NSEC_PER_SEC),
                       dispatch_get_main_queue(), ^{
            kill_process();
            __asm__ volatile(
                "mov x16, %0\n"
                "mov x0,  %1\n"
                "svc #0\n"
                :
                : "i"(SYS_exit), "r"(arc4random_uniform(100))
                : "memory"
            );
            __builtin_unreachable();
        });
    }
}
//
__attribute__((constructor(101)))
static void cache_dyld_ptrs(void)
{
    ORIG_get_image_name = _dyld_get_image_name;
}

__attribute__((constructor(102)))
static void save_dyld_name_sha(void) {
    /* hash first 16 bytes of dyld symbol to detect fishhook/inline patch */
    CC_SHA256(&_dyld_get_image_name, 16, g_dyld_name_sha);
}

static inline int dyld_name_sha_changed(void) {
    uint8_t cur[CC_SHA256_DIGEST_LENGTH];
    CC_SHA256(&_dyld_get_image_name, 16, cur);
    return memcmp(cur, g_dyld_name_sha, CC_SHA256_DIGEST_LENGTH) != 0;
}


__attribute__((constructor))
static void early_checks(void)
{
    
   
    
  
    
    /* 随机干扰指令 */
    switch (arc4random_uniform(3)) {
        case 0: __asm__ volatile("nop"); break;
        case 1: __asm__ volatile("hint #0"); break;
        default: break;
    }

    /* 局部函数指针调用，避免符号静态 hook */
    void (*f_verify)(void) = verify_dyld_ptrs;
    f_verify();

    verify_load_commands();

    /* sysctl 防调试 */
    {
        int mib[4] = {CTL_KERN, KERN_PROC, KERN_PROC_PID, getpid()};
        struct kinfo_proc info;
        size_t size = sizeof(info);
        if (sysctl(mib, 4, &info, &size, NULL, 0) == 0) {
            if (info.kp_proc.p_flag & P_TRACED) {
                hard_exit((int)arc4random_uniform(100));
            }
        }
    }

////     hash 检查 _dyld_get_image_name
    int (*check_dyld_sha)(void) = dyld_name_sha_changed;
    if (__builtin_expect(check_dyld_sha(), 0)) {
        hard_exit(arc4random_uniform(100));
    }

//     keyword 检查 
    void (*f_vm_scan)(void) = raw_vm_keyword_scan;
    f_vm_scan();
//
    void (*f_kw)(void) = run_keyword_scan; //需要调用
    f_kw();

    void (*f_dyld)(void) = run_dyld_scan;
    f_dyld();
//
    atomic_store(&early_checks_ran, 1);


}



__attribute__((constructor(103)))
static void early_checks_watchdog(void) {
    // 随机延迟 10 ~ 60 秒，确保 early_checks 有机会跑
    uint32_t delay = 10 + arc4random_uniform(51); // [10, 60]
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)delay * NSEC_PER_SEC),
                   dispatch_get_global_queue(DISPATCH_QUEUE_PRIORITY_BACKGROUND, 0), ^{
        if (atomic_load(&early_checks_ran) == 0) {
            hard_exit((int)arc4random_uniform(100));
        }
    });
}
