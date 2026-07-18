// proc_mem.c
#include <stdio.h>
// 蜜罐命中计数器，全局变量
int g_honeypotHitCount = 0;

#include "proc_mem.h"
#include "primitives.h"
#include <string.h>
#include "kernel.h"
#include <libjailbreak/info.h>
#include "translation.h"
#include <os/lock.h>

#include <mach/mach.h>
#include <mach/vm_map.h>
#include <mach-o/dyld.h>
#include <mach-o/getsect.h>

#include <mach-o/dyld_images.h>
#include <sys/sysctl.h>

#include "kfd.h"
#include <unistd.h>


// ────────────────────────────────────────────────
//  Very small VA‑page → PA‑page direct‑mapped cache
//  Covers 2 Ki entries = 32 KiB RAM
// ────────────────────────────────────────────────
#define PAGE_CACHE_BITS 14
#define PAGE_CACHE_SIZE (1ULL << PAGE_CACHE_BITS)
#include <time.h>
static int g_virt2physCallCount = 0;

static int g_physreadbufCount = 0;

int getAndResetPhysreadbufCount(void) {
    int count = g_physreadbufCount;
    g_physreadbufCount = 0;
    return count;
}

static int monitored_physreadbuf(uint64_t paddr, void *out_buf, size_t len) {
    g_physreadbufCount++;
    return physreadbuf(paddr, out_buf, len);
}

static time_t g_lastVirt2physTime = 0;

int getVirt2physCallCount(void) {
    int count = g_virt2physCallCount;
    g_virt2physCallCount = 0;
    return count;
}

typedef struct {
    uint64_t tag;        // VA page (16 KiB‑aligned)
    uint64_t pa;         // PA page base
    uint64_t timestamp;  // last insert time (in seconds)
} PageCacheLine;

static PageCacheLine g_pageCache[PAGE_CACHE_SIZE] = {0};


static inline bool page_cache_lookup(uint64_t pageVA, uint64_t *outPA)
{
    uint64_t idx = (pageVA >> 14) & (PAGE_CACHE_SIZE - 1);   // 16 KiB → shift 14
    PageCacheLine cl = g_pageCache[idx];
    if (cl.tag == pageVA) { *outPA = cl.pa; return true; }
    return false;
}
static inline void page_cache_insert(uint64_t pageVA, uint64_t pagePA)
{
    uint64_t idx = (pageVA >> 14) & (PAGE_CACHE_SIZE - 1);
    g_pageCache[idx].tag = pageVA;
    g_pageCache[idx].pa  = pagePA;
    g_pageCache[idx].timestamp = time(NULL);
}
// ────────────────────────────────────────────────

// 全局上下文缓存
typedef struct {
    pid_t pid;
    uint64_t proc;
    uint64_t task;
    uint64_t vmMap;
    uint64_t pmap;
    uint64_t tte;
    bool initialized;
} MemoryContext;

static MemoryContext g_memCtx = {0};
static os_unfair_lock g_lock = OS_UNFAIR_LOCK_INIT;

// 私有函数声明

static BOOL isValidAddress(uintptr_t address) {
    return (address && address > 0x100100000 && address < 0x300000000);
}

// 初始化/刷新上下文（线程安全）
int memory_init(pid_t pid) {
 
    memset(&g_memCtx, 0, sizeof(g_memCtx));
    
    g_memCtx.proc = proc_find(pid);
    if (!g_memCtx.proc) {
        
        return -1;
    }

    g_memCtx.task = proc_task(g_memCtx.proc);
    if (!g_memCtx.task) {
       
        return -2;
    }

    g_memCtx.vmMap = kread_ptr(g_memCtx.task + koffsetof(task, map));
    if (!g_memCtx.vmMap) {
    
        return -3;
    }

    g_memCtx.pmap = kread_ptr(g_memCtx.vmMap + koffsetof(vm_map, pmap));
    if (!g_memCtx.pmap) {
       
        return -4;
    }

    g_memCtx.tte = kread64(g_memCtx.pmap + koffsetof(pmap, tte));
    if (!g_memCtx.tte) {
       
        return -5;
    }

    g_memCtx.pid = pid;
    g_memCtx.initialized = true;
    
   
    
   
  
    return 0;
}





void memory_deinit(void) {
    os_unfair_lock_lock(&g_lock);
    memset(&g_memCtx, 0, sizeof(g_memCtx));
    memset(g_pageCache, 0, sizeof(g_pageCache));
    os_unfair_lock_unlock(&g_lock);
}

void resetPageCache(void) {
    os_unfair_lock_lock(&g_lock);
    memset(g_pageCache, 0, sizeof(g_pageCache));
    os_unfair_lock_unlock(&g_lock);
}

uint64_t virt2phys(uint64_t vaddr) {
    if (!g_memCtx.initialized) return 0;
    if (vaddr < 0x10000) return 0;
    g_virt2physCallCount++;   // 只做计数
    return vtophys(g_memCtx.tte, vaddr);
}











// 可选：暴露蜜罐命中计数获取函数
int getHoneypotHitCount(void) {
    return g_honeypotHitCount;
}




bool safe_readbuf(uint64_t vaddr, void *out_buf, size_t len) {
    os_unfair_lock_lock(&g_lock);
    if (!isValidAddress(vaddr) || out_buf == NULL || len == 0) {
        os_unfair_lock_unlock(&g_lock);
        return false;
    }

    uint64_t paddr = virt2phys(vaddr);
    if (!paddr) {
//        NSLog(@"地址转换失败 %zx",vaddr);
        os_unfair_lock_unlock(&g_lock);
        return false;
    }

    int result = monitored_physreadbuf(paddr, out_buf, len);
    os_unfair_lock_unlock(&g_lock);
    return result == 0;
}

//bool safe_readbuf1(uint64_t vaddr, void *out_buf, size_t len)
//{
//    os_unfair_lock_lock(&g_lock);
//    if (!isValidAddress(vaddr) || out_buf == NULL || len == 0) {
//        os_unfair_lock_unlock(&g_lock);
//        return false;
//    }
//
//    const uint64_t pageVA = vaddr & ~0x3FFFULL;
//    const uint64_t offset = vaddr & 0x3FFFULL;
//    uint64_t pagePA;
//
//    uint64_t idx = (pageVA >> 14) & (PAGE_CACHE_SIZE - 1);
//    bool hit = (g_pageCache[idx].tag == pageVA);
//    if (hit && (time(NULL) - g_pageCache[idx].timestamp > 60)) {
//        hit = false;
//    }
//
//    if (!hit) {
//        pagePA = virt2phys(pageVA);
//        if (!pagePA) {
//            os_unfair_lock_unlock(&g_lock);
//            return false;
//        }
//        page_cache_insert(pageVA, pagePA);
//    } else {
//        pagePA = g_pageCache[idx].pa;
//    }
//
//    int result = monitored_physreadbuf(pagePA + offset, out_buf, len);
//    os_unfair_lock_unlock(&g_lock);
//    return result == 0;
//}




bool safe_readbuf1(uint64_t vaddr, void *out_buf, size_t len)
{
    if (!isValidAddress(vaddr) || !out_buf || len == 0) {
        return false;
    }

    size_t   remaining = len;
    uint8_t *dst       = out_buf;
    uint64_t curVA     = vaddr;
    time_t   startSec  = time(NULL);

    while (remaining) {
        uint64_t pageVA = curVA & ~0x3FFFULL;          // 16 KiB‑aligned
        uint64_t offset = curVA & 0x3FFFULL;
        size_t   chunk  = MIN(0x4000 - offset, remaining);
        uint64_t idx    = (pageVA >> 14) & (PAGE_CACHE_SIZE - 1);

        /* ── ① 进入临界区：查询/更新页缓存 + 物理拷贝 ─────────────── */
        os_unfair_lock_lock(&g_lock);

        bool hit = (g_pageCache[idx].tag == pageVA) &&
                   (startSec - g_pageCache[idx].timestamp <= 60);

        uint64_t pagePA;
        if (!hit) {
            pagePA = virt2phys(pageVA);
            if (!pagePA) {          // 无效虚拟地址，放弃
                g_pageCache[idx].tag = 0;
                os_unfair_lock_unlock(&g_lock);
                return false;
            }
            page_cache_insert(pageVA, pagePA);
        } else {
            pagePA = g_pageCache[idx].pa;
        }

        int rc = monitored_physreadbuf(pagePA + offset, dst, chunk);
        os_unfair_lock_unlock(&g_lock);
        if (rc != 0) return false;
        /* ─────────────────────────────────────────────────────────── */

        remaining -= chunk;
        dst       += chunk;
        curVA     += chunk;
    }
    return true;
}



//bool safe_readbuf1(uint64_t vaddr, void *out_buf, size_t len)
//{
//    os_unfair_lock_lock(&g_lock);
//    if (!isValidAddress(vaddr) || out_buf == NULL || len == 0) {
//        os_unfair_lock_unlock(&g_lock);
//        return false;
//    }
//
//    const uint64_t pageVA = vaddr & ~0x3FFFULL;          // 16 KiB‑aligned
//    const uint64_t offset = vaddr & 0x3FFFULL;
//    uint64_t pagePA;
//
//    // ① cache lookup with timeout
//    uint64_t idx = (pageVA >> 14) & (PAGE_CACHE_SIZE - 1);
//    bool hit = (g_pageCache[idx].tag == pageVA);
//    if (hit && (time(NULL) - g_pageCache[idx].timestamp > 60)) {
//        // Cache expired
//        hit = false;
//    }
//
//    if (!hit) {
//        pagePA = virt2phys(pageVA);
//        if (!pagePA) {
////            NSLog(@"地址转换失败 0x%llx", vaddr);
//            os_unfair_lock_unlock(&g_lock);
//            return false;
//        }
//        page_cache_insert(pageVA, pagePA);
//    } else {
//        pagePA = g_pageCache[idx].pa;
//    }
//
//    // ③ final physread
//    int result = physreadbuf(pagePA + offset, out_buf, len);
//    os_unfair_lock_unlock(&g_lock);
//    return result == 0;
//}
bool safe_writebuf(uint64_t vaddr, const void *in_buf, size_t len) {
    os_unfair_lock_lock(&g_lock);
    for (size_t i = 0; i < len; i++) {
        uint64_t addr = vaddr + i;
        uint64_t pageVA = addr & ~0x3FFFULL;
        uint64_t offset = addr & 0x3FFFULL;
        uint64_t pagePA;

        if (!page_cache_lookup(pageVA, &pagePA)) {
            pagePA = virt2phys(pageVA);
            if (!pagePA) { os_unfair_lock_unlock(&g_lock); return false; }
            page_cache_insert(pageVA, pagePA);
        }
        if (physwrite8(pagePA + offset, ((const uint8_t *)in_buf)[i]) != 0) {
            os_unfair_lock_unlock(&g_lock);
            return false;
        }
    }
    os_unfair_lock_unlock(&g_lock);
    return true;
}

void write_float(uint64_t vaddr, float value) {
    uint32_t raw;
    memcpy(&raw, &value, sizeof(raw));

    os_unfair_lock_lock(&g_lock);
    uint64_t paddr = virt2phys(vaddr);
    if (paddr) {
        physwrite32(paddr, raw);
    }
    os_unfair_lock_unlock(&g_lock);
}



// 写入函数
void write32(uint64_t vaddr, uint32_t value) {
    uint64_t paddr = virt2phys(vaddr);
    if (paddr) physwrite32(paddr, value);
}

void write64(uint64_t vaddr, uint64_t value) {
    uint64_t paddr = virt2phys(vaddr);
    if (paddr) physwrite64(paddr, value);
}

int getPidByName(NSString *Name)
{
   size_t length = 0;
   static const int mib[] = {CTL_KERN, KERN_PROC, KERN_PROC_ALL, 0};
   int err = sysctl((int *)mib, (sizeof(mib) / sizeof(*mib)) - 1, NULL, &length, NULL, 0);
   if (err == -1) {
       err = errno;
   }

   if (err == 0) {
       struct kinfo_proc *procBuffer = (struct kinfo_proc *)malloc(length);
       if(procBuffer == NULL) {
           return -1;
       }

       sysctl( (int *)mib, (sizeof(mib) / sizeof(*mib)) - 1, procBuffer, &length, NULL, 0);

       int count = (int)length / sizeof(struct kinfo_proc);
       for (int i = 0; i < count; ++i) {
           const char *procname = procBuffer[i].kp_proc.p_comm;
           if (strstr(procname, Name.UTF8String)) {
               return procBuffer[i].kp_proc.p_pid;
           }
       }
   }
   return -1;
}


uint64_t toKernelAddress(uint64_t user_va) {
    os_unfair_lock_lock(&g_lock);
    uint64_t paddr = virt2phys(user_va);
    if (!paddr) {
        os_unfair_lock_unlock(&g_lock);
        return 0;
    }
    uint64_t result = phystokv(paddr);
    os_unfair_lock_unlock(&g_lock);
    return result;
}



uint64_t getProcByName(const char* nm) {
    uint64_t proc =get_kernproc();
    NSLog(@"[getProcByName] start: searching for name = \"%s\", currentProc = 0x%llx", nm, proc);

    while (true) {
        uint64_t nameptr = proc + 0x381;
        char name[32] = {0};
        kreadbuf(nameptr, &name, 32);
        NSLog(@"[getProcByName] inspect proc = 0x%llx, nameptr = 0x%llx, name read = \"%s\"", proc, nameptr, name);

        if (strcmp(name, nm) == 0) {
            NSLog(@"[getProcByName] found proc match: \"%s\" at proc = 0x%llx", nm, proc);
            return proc;
        }
        uint64_t next = kread64(proc + 0x8);
        NSLog(@"[getProcByName] moving to next proc: next = 0x%llx", next);

        proc = next;
        if (!proc) {
            NSLog(@"[getProcByName] reached end of list, proc == 0");
            return -1;
        }
    }

    // not reached
    return 0;
}

int getPidByName2(const char* nm) {
    uint64_t proc = getProcByName(nm);
    NSLog(@"[getPidByName2] proc for name \"%s\" = 0x%llx", nm, proc);

    if (proc == (uint64_t)-1) {
        NSLog(@"[getPidByName2] getProcByName returned -1, no match");
        return -1;
    }
    uint32_t pid = kread32(proc + 0x60);
    NSLog(@"[getPidByName2] reading pid at proc + 0x60 = 0x%llx, pid = %u", proc + 0x60, pid);
    return (int)pid;
}

/**
 * @brief 通过进程名获取PID（KFD内核模式，使用当前项目逻辑）
 * @param proc_name 目标进程名（如"DeltaForceClient"）
 * @return 成功返回PID，失败返回-1
 */
int getPidByNameKFD(const char* proc_name) {
    if (!proc_name || strlen(proc_name) == 0) {
        return -1;
    }
    
    // 使用当前项目的逻辑：参考 getProcByName 和 getPidByName2
    // 1. 从 get_kernproc() 开始（功能上与 ksymbol(allproc) 等效，都能遍历进程链表）
    //    - get_kernproc(): 从内核进程开始，通过 list_prev (0x8) 向后遍历
    //    - ksymbol(allproc): 从链表头开始，通过 list_next (0x0) 向前遍历
    uint64_t proc = get_kernproc();
    if (!proc) {
        return -1;
    }
    
    // 2. 遍历进程链表（使用硬编码偏移，与 getProcByName 保持一致）
    while (true) {
    
        uint64_t nameptr = proc + 0x381;
        char name[32] = {0};
        kreadbuf(nameptr, &name, 32);
        
        // 精确匹配（使用 strcmp，与 getProcByName 保持一致）
        if (strcmp(name, proc_name) == 0) {
            // 读取PID（使用硬编码偏移 0x60，与 getPidByName2 保持一致）
            uint32_t pid = kread32(proc + 0x60);
            if (pid > 0) {
                return (int)pid;
            }
        }
        
        // 读取下一个进程节点（使用硬编码偏移 0x8，与 getProcByName 保持一致）
        uint64_t next = kread64(proc + 0x8);
        if (!next) {
            break; // 到达链表末尾
        }
        proc = next;
    }
    
    return -1; // 未找到目标进程
}

// 全局变量缓存偏移量，避免每次都搜
static uint64_t g_off_p_comm = 0;

uint64_t find_p_comm_offset(void) {
    if (g_off_p_comm != 0) return g_off_p_comm; // 已缓存

    uint64_t kern_proc = get_kernproc();
    if (kern_proc == 0) return 0;

    // 搜索范围：经验值 0x200 到 0x600 应该覆盖了 iOS 14 - 17
    // iOS 14/15: ~0x260
    // iOS 16: ~0x380 / 0x388
    // iOS 17: 可能变动
    for (uint64_t offset = 0x200; offset < 0x600; offset += 8) {
        char name[32] = {0};
        
        // 读取 16 字节
        kreadbuf(kern_proc + offset, name, 16);
        name[16] = '\0'; // 强制截断

        // 检查是否匹配 "kernel_task"
        // 注意：p_comm 有时是 "kernel_task"，有时可能是 "kernel_task\0..."
        // strncmp 更安全
        if (strncmp(name, "kernel_task", 11) == 0) {
            printf("[+] Found p_comm offset: 0x%llx\n", offset);
            g_off_p_comm = offset;
            return offset;
        }
    }
    
    printf("[-] Failed to find p_comm offset within range!\n");
    return 0; // 没找到
}

int get_pid_kfd(const char* proc_name) {
    if (!proc_name) return -1;

    // 1. 动态获取偏移量
    uint64_t off_p_comm = find_p_comm_offset();
    if (off_p_comm == 0) {
        // 如果自动搜索失败，可以使用一个默认备选值（如 0x260 或 0x381）
        // 或者直接返回错误
        return -1;
    }

    uint64_t kern_proc = get_kernproc();
    if (kern_proc == 0) return -1;

    uint64_t proc = kern_proc;
    
    // 其他偏移量
    uint64_t off_p_pid  = 0x60;    // 通常稳定
    uint64_t off_p_list_next = 0x08; // 通常稳定

    // 2. 遍历链表
    int loop_count = 0;
    do {
        char name[32] = {0};
        
        kreadbuf(proc + off_p_comm, name, 16);
        name[16] = '\0';

        if (strcmp(name, proc_name) == 0) {
            return kread32(proc + off_p_pid);
        }

        proc = kread64(proc + off_p_list_next);
        
        // 安全退出机制：防止死循环或链表断裂
        loop_count++;
        if (loop_count > 10000) break; // 假设系统进程不会超过 1 万个

    } while (proc != 0 && proc != kern_proc);

    return -1;
}

// 辅助函数：根据 PAC_MASK 去除指针签名
static uint64_t strip_pac_with_mask(uint64_t ptr, uint64_t mask) {
    if (ptr == 0) return 0;
    // 如果 mask 为 0，尝试使用硬编码 mask
    if (mask == 0) mask = 0xFFFFFF8000000000ULL;
    
    // 尝试去除 PAC
    uint64_t strip = UNSIGN_PTR(ptr);
    if (strip == 0 || strip == ptr) { // 如果 UNSIGN_PTR 没起作用或返回0
        return (ptr | mask);
    }
    return strip;
}

/**
 * @brief 断开当前进程在内核进程链表中的链接（断链/隐藏进程）
 * 包含自动扫描链表偏移量的功能
 * @return 1=成功, 0=失败
 */
int unlink_current_process(void) {
    uint64_t kern_proc = get_kernproc();
    if (kern_proc == 0) {
//        NSLog(@"[断链] 失败：无法获取 kern_proc");
        return 0;
    }

    uint64_t pac_mask = PAC_MASK;
    // 如果 PAC_MASK 为 0，尝试使用默认值
    if (pac_mask == 0) pac_mask = 0xFFFFFF8000000000ULL;

    pid_t my_pid = getpid();
//    NSLog(@"[断链] 开始，PID: %d, PAC_MASK: 0x%llx", my_pid, pac_mask);
    
    uint64_t proc = kern_proc;
    
    // 初始遍历偏移 (get_kernproc 逻辑)
    uint64_t off_p_pid  = 0x60;
    uint64_t off_traverse_next = 0x08; // 遍历用的 next 指针偏移

    int loop_count = 0;
    bool found = false;
    
    do {
        int pid = kread32(proc + off_p_pid);
        
        if (pid == my_pid) {
            found = true;
//            NSLog(@"[断链] 找到进程 PID=%d @ 0x%llx", pid, proc);

            // ==========================================
            // 自动扫描 le_prev 偏移量
            // 原理：proc->le_prev 指向 prev->le_next，而 prev->le_next 的值应该是 proc
            // 所以我们要找一个 offset，使得 *(*(proc + offset)) == proc
            // ==========================================
            
            uint64_t found_prev_offset = 0;
            uint64_t found_prev_ptr = 0; // 这是存放 prev 指针的地址 (proc + offset)
            uint64_t found_effective_prev = 0; // 这是 prev 指针指向的地址 (即前一个节点的 next 字段地址)

//            NSLog(@"[断链] 启动偏移量自动扫描 (范围 0x00 - 0x50)...");
            
            for (uint64_t offset = 0; offset <= 0x50; offset += 8) {
                uint64_t raw_ptr = kread64(proc + offset);
                
                // 简单过滤无效指针
                if (raw_ptr == 0) continue;
                
                // 去除 PAC
                uint64_t effective_ptr = strip_pac_with_mask(raw_ptr, pac_mask);
                
                // 读取指针指向的内容
                uint64_t content = kread64(effective_ptr);
                
                if (content == proc) {
//                    NSLog(@"[断链] [SCAN] 命中! Offset: 0x%llx -> PrevPtr: 0x%llx -> Content: 0x%llx (== proc)", offset, effective_ptr, content);
                    
                    found_prev_offset = offset;
                    found_prev_ptr = raw_ptr; // 保存原始带签名指针
                    found_effective_prev = effective_ptr;
                    break;
                }
            }
            
            if (found_prev_offset == 0) {
//                NSLog(@"[断链] 扫描失败：未能在 proc 结构中找到指向自身的双向链表指针。无法安全断链。");
                return 0;
            }
            
//            NSLog(@"[断链] 确定使用 Prev Offset: 0x%llx", found_prev_offset);
            
            // 确定 Next 指针偏移
            // 在 BSD 链表 (LIST_ENTRY) 中:
            // struct {
            //    struct type *le_next;  /* next element */
            //    struct type **le_prev; /* address of previous next element */
            // }
            // le_next 始终在 le_prev 之前，且紧邻，指针大小为 8 字节
            // 所以 next_offset = prev_offset - 8
            
            uint64_t derived_next_offset = found_prev_offset - 8;
            uint64_t next_proc = kread64(proc + derived_next_offset);
            
//            NSLog(@"[断链] 推导 Next Offset: 0x%llx, Next Proc: 0x%llx", derived_next_offset, next_proc);
            
            // 执行断链
            // 1. prev->next = next
            if (kwrite64(found_effective_prev, next_proc) != 0) {
//                NSLog(@"[断链] 写入失败：无法修改前驱节点！");
                return 0;
            }
//            NSLog(@"[断链] 修改前驱节点成功");

            // 2. next->prev = prev
            if (next_proc != 0) {
                // next 节点的 prev 偏移量应该与当前节点的 prev 偏移量一致
                uint64_t next_prev_addr = next_proc + found_prev_offset;
                if (kwrite64(next_prev_addr, found_prev_ptr) != 0) { // 写入原始带签名指针
//                    NSLog(@"[断链] 写入失败：无法修改后继节点！");
                } else {
//                    NSLog(@"[断链] 修改后继节点成功");
                }
            }
            
            // 3. 清理自身
            kwrite64(proc + derived_next_offset, 0);
            kwrite64(proc + found_prev_offset, 0);
            
//            NSLog(@"[断链] 成功！");
            return 1;
        }

        proc = kread64(proc + off_traverse_next);
        loop_count++;
        if (loop_count > 10000) break;

    } while (proc != 0 && proc != kern_proc);
    
    if (!found) {
//        NSLog(@"[断链] 未找到 PID=%d", my_pid);
    }
    return 0;
}
