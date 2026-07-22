#import "BSPHPResponseProcessor.h"
#import "BSPHPConfig.h"
#include <stdint.h>
#include <stdlib.h>
#include <string.h>

static NSString * const BSPHPResponseErrorDomain = @"com.mybsphp.response";
static const uint32_t kBSPHPMaster = 123456789u;
static const uint32_t kBSPHPLCG = 1103515245u;
static const uint32_t kBSPHPMix = 0xA5A5A5A5u;

__attribute__((always_inline)) static inline uint32_t BSPHPUTCYMD(void) {
    NSCalendar *calendar = [NSCalendar calendarWithIdentifier:NSCalendarIdentifierGregorian];
    calendar.timeZone = [NSTimeZone timeZoneForSecondsFromGMT:0];
    NSDate *now = NSDate.date;
    return (uint32_t)([calendar component:NSCalendarUnitYear fromDate:now] * 10000
        + [calendar component:NSCalendarUnitMonth fromDate:now] * 100
        + [calendar component:NSCalendarUnitDay fromDate:now]);
}

__attribute__((always_inline)) static inline NSString *BSPHPBase36(uint32_t value) {
    static const char alphabet[] = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    char output[9] = {0};
    NSInteger position = 7;
    do { output[position--] = alphabet[value % 36]; value /= 36; } while (value && position >= 0);
    return [NSString stringWithUTF8String:&output[position + 1]];
}

__attribute__((always_inline)) static inline NSString *BSPHPTodaySignature(void) {
    uint32_t mixed = (uint32_t)(((uint64_t)(BSPHPUTCYMD() + kBSPHPMaster) * kBSPHPLCG) & 0xFFFFFFFFu);
    mixed ^= kBSPHPMix;
    NSString *value = BSPHPBase36(mixed).uppercaseString;
    if (value.length < 8) {
        NSMutableString *padded = NSMutableString.string;
        unichar pad = (unichar)('A' + (mixed & 0x0F));
        while (padded.length + value.length < 8) [padded appendFormat:@"%C", pad];
        [padded appendString:value];
        value = padded;
    }
    return [value substringToIndex:8];
}

__attribute__((always_inline)) static inline BOOL BSPHPConstantTimeEqual(NSString *left, NSString *right) {
    NSData *a = [left dataUsingEncoding:NSUTF8StringEncoding];
    NSData *b = [right dataUsingEncoding:NSUTF8StringEncoding];
    if (a.length != b.length) return NO;
    const uint8_t *ab = a.bytes, *bb = b.bytes;
    uint8_t difference = 0;
    for (NSUInteger index = 0; index < a.length; index++) difference |= ab[index] ^ bb[index];
    return difference == 0;
}

// 与 UIInitializer.mm 一致：拆分密钥，并按 salt/UTC 日期选择等价解密路径。
__attribute__((always_inline)) static inline void BSPHPUnpackKey(uint32_t key, uint8_t a[4], uint8_t b[4], uint8_t c[4]) {
    for (int index = 0; index < 4; index++) {
        a[index] = (uint8_t)((key >> (8 * index)) & 0xFF);
        b[index] = (uint8_t)((key >> (8 * (3 - index))) & 0xFF);
        c[index] = (uint8_t)((key >> (16 + index * 4)) & 0xFF);
    }
}

__attribute__((always_inline)) static inline void BSPHPDec0(uint8_t io[4], const uint8_t a[4], const uint8_t b[4], const uint8_t c[4]) {
    for (int i = 0; i < 4; i++) { io[i] ^= c[i]; io[i] ^= b[i]; io[i] ^= a[i]; }
}

__attribute__((always_inline)) static inline void BSPHPDec1(uint8_t io[4], const uint8_t a[4], const uint8_t b[4], const uint8_t c[4]) {
    uint8_t temporary[4];
    for (int i = 0; i < 4; i++) temporary[i] = io[i] ^ c[i];
    for (int i = 0; i < 4; i++) temporary[i] ^= b[i];
    for (int i = 0; i < 4; i++) io[i] = temporary[i] ^ a[i];
}

__attribute__((always_inline)) static inline void BSPHPDec2(uint8_t io[4], const uint8_t a[4], const uint8_t b[4], const uint8_t c[4]) {
    io[0] ^= c[0]; io[1] ^= c[1]; io[2] ^= c[2]; io[3] ^= c[3];
    io[3] ^= b[3]; io[2] ^= b[2]; io[1] ^= b[1]; io[0] ^= b[0];
    io[1] ^= a[1]; io[3] ^= a[3]; io[0] ^= a[0]; io[2] ^= a[2];
}

__attribute__((always_inline)) static inline void BSPHPDec3(uint8_t io[4], const uint8_t a[4], const uint8_t b[4], const uint8_t c[4]) {
    for (int i = 0; i < 4; i++) { uint8_t v = io[i]; v ^= c[i]; v ^= b[i]; v ^= a[i]; io[i] = v; }
}

// 在调用点内联展开，不产生独立的二次解密 Objective-C 方法入口。
#define BSPHP_EX_DEC_TO_U32(__hex10, __outValue, __didDecrypt) do { \
    const char *__input = (const char *)(__hex10); \
    (__didDecrypt) = NO; (__outValue) = 0; \
    if (!__input || strlen(__input) != 10) break; \
    uint8_t __raw[5] = {0}; BOOL __valid = YES; \
    for (int __index = 0; __index < 5; __index++) { \
        char __pair[3] = {__input[__index * 2], __input[__index * 2 + 1], 0}; \
        for (int __digit = 0; __digit < 2; __digit++) { \
            char __ch = __pair[__digit]; \
            if (!((__ch >= '0' && __ch <= '9') || (__ch >= 'A' && __ch <= 'F') || (__ch >= 'a' && __ch <= 'f'))) __valid = NO; \
        } \
        __raw[__index] = (uint8_t)strtoul(__pair, NULL, 16); \
    } \
    if (!__valid) break; \
    uint8_t __salt = __raw[0], __io[4] = {__raw[1], __raw[2], __raw[3], __raw[4]}; \
    uint32_t __ymd = BSPHPUTCYMD(); \
    uint32_t __key = (uint32_t)(((uint64_t)(__ymd + kBSPHPMaster) * kBSPHPLCG) & 0xFFFFFFFFu); \
    __key ^= kBSPHPMix; __key ^= (uint32_t)((uint64_t)__salt * 0x9E3779B1u); \
    uint8_t __a[4], __b[4], __c[4]; BSPHPUnpackKey(__key, __a, __b, __c); \
    switch (((uint32_t)__salt ^ (__ymd * 33u)) & 3u) { \
        case 0: BSPHPDec0(__io, __a, __b, __c); break; \
        case 1: BSPHPDec1(__io, __a, __b, __c); break; \
        case 2: BSPHPDec2(__io, __a, __b, __c); break; \
        default: BSPHPDec3(__io, __a, __b, __c); break; \
    } \
    (__outValue) = ((uint32_t)__io[0] << 24) | ((uint32_t)__io[1] << 16) | ((uint32_t)__io[2] << 8) | __io[3]; \
    (__didDecrypt) = YES; \
} while (0)

@implementation BSPHPResponseProcessor

+ (NSDictionary<NSString *,id> *)processCardResponse:(id)responseObject
                                              cardCode:(NSString *)cardCode
                                           appSafeCode:(NSString *)appSafeCode
                                                 error:(NSError **)error {
    NSString *plain = [self stringFromResponse:responseObject];
    if (plain.length == 0) return [self fail:@"授权响应为空" code:-1 error:error];

    NSArray<NSString *> *fields = [plain componentsSeparatedByString:@"|"];
    if (fields.count < 9 || ![fields[0] isEqualToString:@"01"]) {
        return [self fail:@"授权响应字段不完整" code:-2 error:error];
    }

    NSString *serverDaily = fields[4];
    NSString *localDaily = BSPHPTodaySignature();
    BOOL dailyPassed = BSPHPConstantTimeEqual(serverDaily, localDaily);
    NSLog(@"[BSPHP][每日校验] server=%@ local=%@ result=%@", serverDaily, localDaily, dailyPassed ? @"PASS" : @"FAIL");
    if (!dailyPassed) return [self fail:@"每日校验失败" code:-3 error:error];

    NSString *returnedSafeCode = fields[3];
    BOOL requestPassed = BSPHPConstantTimeEqual(returnedSafeCode, appSafeCode ?: @"");
    NSLog(@"[BSPHP][每次校验] request=%@ response=%@ result=%@", appSafeCode, returnedSafeCode, requestPassed ? @"PASS" : @"FAIL");
    if (!requestPassed) return [self fail:@"每次请求校验失败" code:-4 error:error];

    BOOL didSecondDecrypt = NO;
    uint32_t decryptedValue = 0;
    NSString *encryptedPayload = fields[2];
    BSPHP_EX_DEC_TO_U32(encryptedPayload.UTF8String, decryptedValue, didSecondDecrypt);
    NSString *payload = didSecondDecrypt ? [NSString stringWithFormat:@"0x%08X", decryptedValue] : encryptedPayload;
    NSLog(@"[BSPHP][二次解密] encrypted=%@ decrypted=%@ applied=%@", encryptedPayload, payload, didSecondDecrypt ? @"YES" : @"NO");

    NSString *masked = cardCode.length >= 4
        ? [@"••••-" stringByAppendingString:[cardCode substringFromIndex:cardCode.length - 4]]
        : cardCode;
    return @{
        @"application": @{@"name": BSPHP_APP_NAME, @"code": BSPHP_APP_CODE},
        @"card": @{@"masked": masked ?: @"", @"status": @"active", @"expires_at": fields[6]},
        @"device": fields[8],
        @"payload": payload,
        @"encrypted_payload": encryptedPayload,
        @"daily_signature": serverDaily,
        @"daily_check_passed": @(dailyPassed),
        @"request_check_passed": @(requestPassed),
        @"appsafecode": returnedSafeCode,
        @"BSphpSeSsL": fields.count > 9 ? fields[9] : @"",
        @"second_decrypt_applied": @(didSecondDecrypt),
        @"legacy_response": plain
    };
}

+ (NSDictionary<NSString *,id> *)processAccountResponse:(id)responseObject
                                              appSafeCode:(NSString *)appSafeCode
                                                    error:(NSError **)error {
    NSString *plain = [self stringFromResponse:responseObject];
    if (plain.length == 0) return [self fail:@"账户响应为空" code:-11 error:error];

    NSArray<NSString *> *fields = [plain componentsSeparatedByString:@"|"];
    if (fields.count < 9 || ![fields[0] isEqualToString:@"01"] || ![fields[1] isEqualToString:@"1011"]) {
        return [self fail:@"账户响应字段不完整" code:-12 error:error];
    }

    NSString *serverDaily = fields[6];
    NSString *localDaily = BSPHPTodaySignature();
    BOOL dailyPassed = BSPHPConstantTimeEqual(serverDaily, localDaily);
    NSLog(@"[BSPHP][账户每日校验] server=%@ local=%@ result=%@", serverDaily, localDaily, dailyPassed ? @"PASS" : @"FAIL");
    if (!dailyPassed) return [self fail:@"账户每日校验失败" code:-13 error:error];

    NSString *returnedSafeCode = fields[5];
    BOOL requestPassed = BSPHPConstantTimeEqual(returnedSafeCode, appSafeCode ?: @"");
    NSLog(@"[BSPHP][账户每次校验] result=%@", requestPassed ? @"PASS" : @"FAIL");
    if (!requestPassed) return [self fail:@"账户每次请求校验失败" code:-14 error:error];

    BOOL didSecondDecrypt = NO;
    uint32_t decryptedValue = 0;
    NSString *encryptedPayload = fields[3];
    BSPHP_EX_DEC_TO_U32(encryptedPayload.UTF8String, decryptedValue, didSecondDecrypt);
    NSString *payload = didSecondDecrypt ? [NSString stringWithFormat:@"0x%08X", decryptedValue] : encryptedPayload;
    NSLog(@"[BSPHP][账户二次解密] encrypted=%@ decrypted=%@ applied=%@", encryptedPayload, payload, didSecondDecrypt ? @"YES" : @"NO");

    return @{
        @"application": @{@"name": BSPHP_ACCOUNT_APP_NAME, @"code": BSPHP_ACCOUNT_APP_CODE},
        @"account": @{
            @"username": fields[8],
            @"status": @"active",
            @"expires_at": fields[4],
            @"invitation_code": fields[7]
        },
        @"device": fields[2],
        @"payload": payload,
        @"encrypted_payload": encryptedPayload,
        @"daily_signature": serverDaily,
        @"daily_check_passed": @(dailyPassed),
        @"request_check_passed": @(requestPassed),
        @"appsafecode": returnedSafeCode,
        @"BSphpSeSsL": fields.count > 9 ? fields[9] : @"",
        @"second_decrypt_applied": @(didSecondDecrypt),
        @"legacy_response": plain
    };
}

+ (NSDictionary<NSString *,id> *)processHeartbeatResponse:(id)responseObject
                                              sessionHandle:(NSString *)sessionHandle
                                                appSafeCode:(NSString *)appSafeCode
                                                      error:(NSError **)error {
    NSString *plain = [self stringFromResponse:responseObject];
    NSArray<NSString *> *fields = [plain componentsSeparatedByString:@"|"];
    if (fields.count < 4) return [self fail:@"心跳响应字段不完整" code:-21 error:error];
    if (!BSPHPConstantTimeEqual(fields[1], sessionHandle ?: @"")) return [self fail:@"心跳连接标记不匹配" code:-22 error:error];
    if (!BSPHPConstantTimeEqual(fields[2], appSafeCode ?: @"")) return [self fail:@"心跳每次请求校验失败" code:-23 error:error];
    NSString *code = fields[0];
    NSDictionary *messages = @{@"5031": @"状态正常", @"5033": @"软件用户已冻结", @"5030": @"授权时间已到期", @"5026": @"登录连接超时"};
    NSLog(@"[BSPHP][心跳] code=%@ session=%@", code, sessionHandle);
    return @{@"code": code, @"valid": @([code isEqualToString:@"5031"]), @"message": messages[code] ?: @"心跳状态异常", @"server_time": fields[3]};
}

+ (NSString *)stringFromResponse:(id)responseObject {
    if ([responseObject isKindOfClass:NSString.class]) return responseObject;
    if ([responseObject isKindOfClass:NSData.class]) return [[NSString alloc] initWithData:responseObject encoding:NSUTF8StringEncoding];
    if ([responseObject isKindOfClass:NSDictionary.class]) {
        id value = responseObject[@"data"] ?: responseObject[@"response"][@"data"];
        return [value isKindOfClass:NSString.class] ? value : nil;
    }
    return nil;
}

+ (NSDictionary *)fail:(NSString *)message code:(NSInteger)code error:(NSError **)error {
    if (error) {
        *error = [NSError errorWithDomain:BSPHPResponseErrorDomain
                                     code:code
                                 userInfo:@{NSLocalizedDescriptionKey: message}];
    }
    return nil;
}

@end
