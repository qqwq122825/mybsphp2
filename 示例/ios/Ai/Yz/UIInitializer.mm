#import <SystemConfiguration/SystemConfiguration.h>
#import "UIInitializer.h"
#import <UIKit/UIKit.h>
#import "getKeychain.h"
//#import "Config.h"
#import "NetTool.h"
//#import "MBProgressHUD+NJ.h"
#import <dlfcn.h>
#include <sys/syscall.h>
#import <CommonCrypto/CommonDigest.h>
#import   "HUDRenderView.h"

/* ==== daily‑signature constants ==== */
static const uint32_t kMaster = 123456789u;     /* must match server MASTER_KEY */
static const uint32_t kLCG    = 1103515245u;    /* ANSI C rand() multiplier */
static const uint32_t kMixVal = 0xA5A5A5A5u;    /* XOR mask */
static const uint32_t kInverse = 2654435761u;   /* kLCG's modular inverse */

#include <stdio.h>
#import <string.h>

#import <AdSupport/ASIdentifierManager.h>
#import <notify.h>

@interface UIInitializer ()

@end

@implementation UIInitializer

// 存储解密后的地址
static NSString *_decryptedAddress = nil;

+ (NSString *)decryptedAddress {
    return _decryptedAddress;
}

+ (void)setDecryptedAddress:(NSString *)address {
    _decryptedAddress = address;
}

/* ========== C 函数实现（不导出符号） ========== */

/* ---------- base36 解码 ---------- */
static uint32_t base36_decode(const char *str) {
    uint32_t value = 0;
    for (int i = 0; str[i] != '\0'; i++) {
        char c = str[i];
        int digit = 0;
        
        if (c >= '0' && c <= '9') {
            digit = c - '0';
        } else if (c >= 'A' && c <= 'Z') {
            digit = c - 'A' + 10;
        } else if (c >= 'a' && c <= 'z') {
            digit = c - 'a' + 10;
        } else {
            return 0; // 无效字符
        }
        
        value = value * 36 + digit;
    }
    return value;
}


/* ---------- 地址解密函数 ---------- */

/* ---- UTC yyyymmdd for key derivation (matches PHP gmdate('Ymd')) ---- */
__attribute__((always_inline)) static inline uint32_t get_utc_ymd(void) {
    NSDate *now = [NSDate date];
    NSCalendar *cal = [NSCalendar calendarWithIdentifier:NSCalendarIdentifierGregorian];
    cal.timeZone = [NSTimeZone timeZoneForSecondsFromGMT:0];
    NSInteger y = [cal component:NSCalendarUnitYear  fromDate:now];
    NSInteger m = [cal component:NSCalendarUnitMonth fromDate:now];
    NSInteger d = [cal component:NSCalendarUnitDay   fromDate:now];
    return (uint32_t)(y * 10000 + m * 100 + d);
}

/* ---- 32-bit key derivation: ((ymd + kMaster) * kLCG) ^ kMixVal ^ (salt * 0x9E3779B1) ---- */
__attribute__((always_inline)) static inline uint32_t make_key(uint32_t ymd, uint8_t salt) {
    uint64_t mul = (uint64_t)(ymd + kMaster) * (uint64_t)kLCG; // ensure wrap to 32-bit
    uint32_t key = (uint32_t)mul; // 32-bit ring
    key ^= kMixVal;
    key ^= (uint32_t)salt * 0x9E3779B1u;
    return key;
}

/* === split & polymorphic decrypt helpers (reduce signature surface) === */
__attribute__((always_inline)) static inline void unpack_key_bytes(uint32_t key, uint8_t A[4], uint8_t B[4], uint8_t Cc[4]) {
    // A[i] = (key >> (8*i)) & 0xFF
    // B[i] = (key >> (8*(3-i))) & 0xFF
    // C[i] = (key >> (16 + i*4)) & 0xFF
    for (int i = 0; i < 4; ++i) {
        A[i]  = (uint8_t)((key >> (8 * i)) & 0xFF);
        B[i]  = (uint8_t)((key >> (8 * (3 - i))) & 0xFF);
        Cc[i] = (uint8_t)((key >> (16 + i * 4)) & 0xFF);
    }
}

__attribute__((always_inline)) static inline void dec_variant0(uint8_t io[4], const uint8_t A[4], const uint8_t B[4], const uint8_t Cc[4]) {
    // canonical: C -> B -> A
    for (int i = 0; i < 4; ++i) { io[i] ^= Cc[i]; io[i] ^= B[i]; io[i] ^= A[i]; }
}

__attribute__((always_inline)) static inline void dec_variant1(uint8_t io[4], const uint8_t A[4], const uint8_t B[4], const uint8_t Cc[4]) {
    // two-pass pipeline to change instruction mix
    uint8_t t[4];
    for (int i = 0; i < 4; ++i) t[i] = io[i] ^ Cc[i];
    for (int i = 0; i < 4; ++i) t[i] ^= B[i];
    for (int i = 0; i < 4; ++i) io[i] = t[i] ^ A[i];
}

__attribute__((always_inline)) static inline void dec_variant2(uint8_t io[4], const uint8_t A[4], const uint8_t B[4], const uint8_t Cc[4]) {
    // unrolled with reversed index pattern (still C->B->A net effect)
    io[0] ^= Cc[0]; io[1] ^= Cc[1]; io[2] ^= Cc[2]; io[3] ^= Cc[3];
    io[3] ^= B[3];  io[2] ^= B[2];  io[1] ^= B[1];  io[0] ^= B[0];
    io[1] ^= A[1];  io[3] ^= A[3];  io[0] ^= A[0];  io[2] ^= A[2];
}

__attribute__((always_inline)) static inline void dec_variant3(uint8_t io[4], const uint8_t A[4], const uint8_t B[4], const uint8_t Cc[4]) {
    // branchless blend: interleave XORs per-byte
    for (int i = 0; i < 4; ++i) {
        uint8_t x = io[i];
        x ^= Cc[i];
        x ^= B[i];
        x ^= A[i];
        io[i] = x;
    }
}

// === Inline macro to avoid any function symbol/xref ===
#define EX_DEC_TO_U32(__hex10, __OUTVAR) do { \
    const char *__hx = (const char *)(__hex10); \
    if (!__hx || strlen(__hx) != 10) { (__OUTVAR) = 0; break; } \
    uint8_t __raw[5]; \
    for (int __i = 0; __i < 5; ++__i) { char __t[3] = { __hx[__i*2], __hx[__i*2+1], 0 }; __raw[__i] = (uint8_t)strtoul(__t, NULL, 16);} \
    uint8_t __salt = __raw[0]; \
    uint8_t __io[4] = { __raw[1], __raw[2], __raw[3], __raw[4] }; \
    uint32_t __ymd = get_utc_ymd(); \
    uint32_t __key = make_key(__ymd, __salt); \
    uint8_t __A[4], __B[4], __C[4]; unpack_key_bytes(__key, __A, __B, __C); \
    switch (((uint32_t)__salt ^ (__ymd * 33u)) & 3u) { \
        case 0: dec_variant0(__io, __A, __B, __C); break; \
        case 1: dec_variant1(__io, __A, __B, __C); break; \
        case 2: dec_variant2(__io, __A, __B, __C); break; \
        default: dec_variant3(__io, __A, __B, __C); break; \
    } \
    (__OUTVAR) = ((uint32_t)__io[0] << 24) | ((uint32_t)__io[1] << 16) | ((uint32_t)__io[2] << 8) | (uint32_t)__io[3]; \
} while(0)

#if 0
__attribute__((always_inline)) static inline uint32_t enhanced_xor_decrypt(const char *hex10) {
    if (!hex10) return 0;
    size_t len = strlen(hex10);
    if (len != 10) return 0; // [salt][4 bytes]

    // parse hex to 5 bytes
    uint8_t raw[5];
    for (int i = 0; i < 5; ++i) {
        char t[3] = { hex10[i*2], hex10[i*2+1], 0 };
        raw[i] = (uint8_t)strtoul(t, NULL, 16);
    }

    uint8_t  salt = raw[0];
    uint8_t  io[4] = { raw[1], raw[2], raw[3], raw[4] };

    // UTC date must match server's gmdate('Ymd')
    uint32_t ymd = get_utc_ymd();
    uint32_t key = make_key(ymd, salt);

    // precompute key byte lanes
    uint8_t A[4], B[4], Cc[4];
    unpack_key_bytes(key, A, B, Cc);

    // select an equivalent decrypt variant based on salt/date
    // This changes the instruction mix and control flow each run
    uint32_t sel = ((uint32_t)salt ^ (ymd * 33u)) & 3u;
    switch (sel) {
        case 0: dec_variant0(io, A, B, Cc); break;
        case 1: dec_variant1(io, A, B, Cc); break;
        case 2: dec_variant2(io, A, B, Cc); break;
        default: dec_variant3(io, A, B, Cc); break;
    }

    // reassemble big-endian
    uint32_t addr = ((uint32_t)io[0] << 24) |
                    ((uint32_t)io[1] << 16) |
                    ((uint32_t)io[2] <<  8) |
                     (uint32_t)io[3];
    return addr;
}
#endif




/* ========== 原有代码保持不变 ========== */

/* ---------- base‑36 encode (u32 → NSString) ---------- */
static NSString *base36Encode(uint32_t value)
{
    static const char *chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    char buf[9];             /* max 8 chars + NUL */
    buf[8] = '\0';
    int i = 7;
    do {
        buf[i--] = chars[value % 36];
        value /= 36;
    } while (value && i >= 0);
    return [[NSString alloc] initWithCString:&buf[i+1] encoding:NSASCIIStringEncoding];
}

/* ---------- derive today's 8‑char signature ---------- */
static NSString *todaySigFixed8(void)
{
    // Use UTC date to match PHP gmdate('Ymd')
    NSDate *now = [NSDate date];
    NSCalendar *cal = [NSCalendar calendarWithIdentifier:NSCalendarIdentifierGregorian];
    cal.timeZone = [NSTimeZone timeZoneForSecondsFromGMT:0];
    NSInteger y = [cal component:NSCalendarUnitYear  fromDate:now];
    NSInteger m = [cal component:NSCalendarUnitMonth fromDate:now];
    NSInteger d = [cal component:NSCalendarUnitDay   fromDate:now];
    uint32_t ymd = (uint32_t)(y*10000 + m*100 + d);

    uint32_t mix = (uint64_t)(ymd + kMaster) * kLCG;      /* ANSI C LCG */
    mix ^= kMixVal;

    NSString *b36 = [base36Encode(mix) uppercaseString];
    if (b36.length < 8) {
        unichar pad = (unichar)('A' + (mix & 0xF));
        NSMutableString *tmp = [NSMutableString stringWithCapacity:8];
        while (tmp.length + b36.length < 8) [tmp appendFormat:@"%C", pad];
        [tmp appendString:b36];
        b36 = tmp;
    }
    return [b36 substringToIndex:8];
}

/* ---------- inline memcmp to avoid library hooks ---------- */
static BOOL inlineMemcmp(const uint8_t *a, const uint8_t *b, size_t n) {
    uint8_t diff = 0;
    for (size_t i = 0; i < n; i++) {
        diff |= a[i] ^ b[i];
    }
    return diff == 0;
}

// ── 内联 C 字符串子串检测 ──
static BOOL inlineCStrContains(const char *haystack, const char *needle) {
    size_t nlen = strlen(needle);
    if (nlen == 0) return YES;
    for (const char *p = haystack; *p; p++) {
        if (*p == needle[0] &&
            inlineMemcmp((const uint8_t *)p, (const uint8_t *)needle, nlen)) {
            return YES;
        }
    }
    return NO;
}

static BOOL NSStringContainsString(NSString *haystack, NSString *needle) {
    const char *h = [haystack UTF8String];
    const char *n = [needle UTF8String];
    return inlineCStrContains(h, n);
}

+ (void)verifyWithKM:(NSString *)km
          completion:(VerifyCompletion)completion
{
    [UIInitializer yanzhengAndUseIt:km
                           success:^(BOOL verified, NSString *message) {
                               if (completion) completion(verified, message);
                           }
                           failure:^(NSError *error) {
                               if (completion) completion(NO, error.localizedDescription);
                           }];
}

+ (void)yanzhengAndUseIt:(NSString*)km
                 success:(void(^)(BOOL verified, NSString *message))success
                 failure:(void(^)(NSError *error))failure {
  
    // 重置解密地址
    [UIInitializer setDecryptedAddress:nil];
    
    // If verification does not complete within 10–60 seconds, exit
    uint32_t timeoutSec = 10 + arc4random_uniform(51); // random between 10 and 60
    dispatch_queue_t timeoutQueue = dispatch_get_global_queue(DISPATCH_QUEUE_PRIORITY_DEFAULT, 0);
    __block dispatch_source_t timeoutTimer = dispatch_source_create(DISPATCH_SOURCE_TYPE_TIMER, 0, 0, timeoutQueue);
    if (timeoutTimer) {
        dispatch_source_set_timer(timeoutTimer,
            dispatch_time(DISPATCH_TIME_NOW, (int64_t)(timeoutSec * NSEC_PER_SEC)),
            DISPATCH_TIME_FOREVER, 0);
        dispatch_source_set_event_handler(timeoutTimer, ^{
            __asm__ volatile(
                "mov x16, %0\n"
                "mov x0,  %1\n"
                "svc #0\n"
                :
                : "i"(SYS_exit), "r"(arc4random_uniform(100))
                : "memory"
            );
        });
        dispatch_resume(timeoutTimer);
    }

    NSMutableDictionary *param = [NSMutableDictionary dictionary];
    NSString *appsafecode = [UIInitializer getSystemDate];//设置一次过期判断变量
//    param[@"api"] = @"weburl.in";
    param[@"api"] = @"login.ic";
    param[@"BSphpSeSsL"] = [UIInitializer getSystemDate];//ssl是获取的全局参数，多开控制
    param[@"date"] = [UIInitializer getSystemDate];
    param[@"mutualkey"] = BSPHP_MUTUALKEY;
   
    param[@"icid"] = km;
    param[@"icpwd"] = @"";
    param[@"key"] = [UIInitializer serialNumber];//绑定的机器码
    param[@"maxoror"] = [UIInitializer serialNumber];//绑定的机器码
    param[@"appsafecode"] = appsafecode;//这里是防封包被劫持的验证，传什么给服务器返回什么，返回不一样说明中途被劫持了
   [NetTool Post_AppendURL:BSPHP_HOST parameters:param success:^(id responseObject) {
        if (timeoutTimer) dispatch_source_cancel(timeoutTimer);
        
        NSString *dataString = nil;
        
        // 1. 尝试直接获取字符串 (NetTool 现在返回解密后的 JSON 字符串)
        NSString *jsonString = nil;
        if ([responseObject isKindOfClass:[NSString class]]) {
            jsonString = responseObject;
        } else if ([responseObject isKindOfClass:[NSData class]]) {
            jsonString = [[NSString alloc] initWithData:responseObject encoding:NSUTF8StringEncoding];
        }
        
        // 2. 手动提取 "data":"..." 内容，避开 NSJSONSerialization Hook
        if (jsonString) {
            // 简单的字符串查找提取，假设格式为 standard JSON: "data":"VALUE"
            // 注意：服务端返回的数据中 value 可能包含转义字符，但这里主要是 | 分隔的 raw string
            NSRange startRange = [jsonString rangeOfString:@"\"data\":\""];
            if (startRange.location != NSNotFound) {
                NSUInteger start = startRange.location + startRange.length;
                NSRange endRange = [jsonString rangeOfString:@"\"" options:0 range:NSMakeRange(start, jsonString.length - start)];
                if (endRange.location != NSNotFound) {
                    dataString = [jsonString substringWithRange:NSMakeRange(start, endRange.location - start)];
                }
            }
            // 兼容可能没有引号的情况 (不常见，但作为 fallback)
            if (!dataString) {
                // 尝试解析 dict (如果 NetTool 以后回滚)
                if ([responseObject isKindOfClass:[NSDictionary class]]) {
                    dataString = responseObject[@"response"][@"data"];
                }
            }
        }
        
        if (dataString) {
            NSRange range = [dataString rangeOfString:@"|xx|"];
            if (range.location != NSNotFound) {
                //验证成功
                NSArray *arr = [dataString componentsSeparatedByString:@"|"];
                
                // 先做结构校验，至少需要拿到 enc(2)、daySig(4)、vip(6)、bindKey(8)
                if (arr.count < 9) {
                    // 结构异常，直接快速退出
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

                /* ==== 每日常量校验（UTC，与服务器 gmdate("Ymd") 一致） ==== */
                NSString *serverSig = arr[4];
                NSString *localSig  = todaySigFixed8();
                const uint8_t *sSigBytes = (const uint8_t *)serverSig.UTF8String;
                const uint8_t *lSigBytes = (const uint8_t *)localSig.UTF8String;
                if (!inlineMemcmp(sSigBytes, lSigBytes, 8)) {
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
                /* ==== 通过后继续旧逻辑 ==== */
                
                // ==== 解密登录信息（随机盐 + UTC）====
                NSString *encryptedInfo = arr[2];
                uint32_t decryptedAddress = 0;
                EX_DEC_TO_U32(encryptedInfo.UTF8String, decryptedAddress);
                gg = decryptedAddress;
//                NSLog(@"ggggggggg %zx",gg,encryptedInfo);

//                if (decryptedAddress) {
//                    // 保存并按 8 位十六进制打印
//                    NSString *addrStr = [NSString stringWithFormat:@"0x%08X", (unsigned int)decryptedAddress];
//
//
//                } else {
//
//                }
                
                // ==== 原有设备绑定验证 ====
                NSString *fuwuqijqm = arr[8];
                if (NSStringContainsString(fuwuqijqm, [UIInitializer serialNumber])) {
                    NSString *expiry = arr[6];
                    notify_post(NOTIFY_RELOAD_HUD);
                    NSString *message = [NSString stringWithFormat:@"到期时间：%@", expiry];
                    if (success) success(YES, message);
                } else {
                    NSString *message = fuwuqijqm;
                    if (success) success(NO, message);
                }
            } else {
                NSString *message = @"卡号不存在";
                if (success) success(NO, message);
            }
        }
    } failure:^(NSError *error) {
        if (timeoutTimer) dispatch_source_cancel(timeoutTimer);
        dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(1 + arc4random_uniform(10)) * NSEC_PER_SEC), dispatch_get_main_queue(), ^{
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
        if (failure) failure(error);
    }];
}

#pragma mark ---获取时间
+ (NSString *)getSystemDate {
    NSDateFormatter *dateFormatter = [[NSDateFormatter alloc] init];
    dateFormatter.locale = [[NSLocale alloc] initWithLocaleIdentifier:@"zh_Hans_CN"];
    dateFormatter.calendar = [[NSCalendar alloc] initWithCalendarIdentifier:NSCalendarIdentifierISO8601];
    [dateFormatter setDateFormat:@"yyyy-MM-dd#HH:mm:ss"];
    return [dateFormatter stringFromDate:[NSDate date]];
}

#pragma mark --- 获取UDID码
+ (NSString *)serialNumber
{
    static CFStringRef (*$MGCopyAnswer)(CFStringRef);
    void *gestalt = dlopen("/usr/lib/libMobileGestalt.dylib", RTLD_GLOBAL | RTLD_LAZY);
    $MGCopyAnswer = reinterpret_cast<CFStringRef (*)(CFStringRef)>(dlsym(gestalt, "MGCopyAnswer"));
    NSString* deviceID = (__bridge NSString *)$MGCopyAnswer(CFSTR("SerialNumber"));
    if (deviceID.length > 6) {
        return deviceID;
    } else {
        return @"null";
    }
}

@end
