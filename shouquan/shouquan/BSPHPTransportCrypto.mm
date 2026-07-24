#import "BSPHPTransportCrypto.h"

#import <CommonCrypto/CommonCryptor.h>
#import <CommonCrypto/CommonHMAC.h>
#import <Security/Security.h>

static NSString * const BSPHPAlgorithm = @"RSA-OAEP+A256CBC-HS256";
static NSString * const BSPHPErrorDomain = @"com.mybsphp.transport";

@implementation BSPHPTransportCrypto

+ (NSDictionary<NSString *,id> *)sealRequest:(NSString *)plaintext
                                publicKeyPEM:(NSString *)publicKeyPEM
                                   mutualKey:(NSString *)mutualKey
                                       error:(NSError **)error
{
    NSData *sessionKey = [self randomData:32 error:error];
    NSData *iv = [self randomData:kCCBlockSizeAES128 error:error];
    NSData *nonceData = [self randomData:16 error:error];
    if (!sessionKey || !iv || !nonceData) return nil;

    SecKeyRef publicKey = [self publicKeyFromValue:publicKeyPEM error:error];
    if (!publicKey) return nil;

    SecKeyAlgorithm algorithm = kSecKeyAlgorithmRSAEncryptionOAEPSHA1;
    if (!SecKeyIsAlgorithmSupported(publicKey, kSecKeyOperationTypeEncrypt, algorithm)) {
        CFRelease(publicKey);
        [self setError:error code:1002 message:@"当前 iOS 设备不支持 RSA-OAEP 加密"];
        return nil;
    }

    CFErrorRef rsaError = NULL;
    CFDataRef wrappedRef = SecKeyCreateEncryptedData(publicKey,
                                                      algorithm,
                                                      (__bridge CFDataRef)sessionKey,
                                                      &rsaError);
    CFRelease(publicKey);
    if (!wrappedRef) {
        if (error) *error = CFBridgingRelease(rsaError);
        return nil;
    }
    NSData *wrappedKey = CFBridgingRelease(wrappedRef);

    NSData *encKey = [self hmacSHA256:[@"bsphp-v2/enc" dataUsingEncoding:NSUTF8StringEncoding]
                                  key:sessionKey];
    NSData *macKey = [self hmacSHA256:[@"bsphp-v2/mac" dataUsingEncoding:NSUTF8StringEncoding]
                                  key:sessionKey];
    NSData *ciphertext = [self aesCBC:[plaintext dataUsingEncoding:NSUTF8StringEncoding]
                                  key:encKey
                                   iv:iv
                              encrypt:YES
                                error:error];
    if (!ciphertext) return nil;

    NSNumber *timestamp = @((NSInteger)[NSDate date].timeIntervalSince1970);
    NSString *nonce = [self base64URL:nonceData];
    NSString *ek = [self base64URL:wrappedKey];
    NSString *ivValue = [self base64URL:iv];
    NSString *dataValue = [self base64URL:ciphertext];
    NSString *canonical = [NSString stringWithFormat:@"2|%@|%@|%@|%@|%@|%@",
                           BSPHPAlgorithm, timestamp, nonce, ek, ivValue, dataValue];
    NSData *canonicalData = [canonical dataUsingEncoding:NSUTF8StringEncoding];
    NSString *mac = [self base64URL:[self hmacSHA256:canonicalData key:macKey]];
    NSString *authCanonical = [canonical stringByAppendingFormat:@"|%@", mac];
    NSString *auth = [self base64URL:[self hmacSHA256:[authCanonical dataUsingEncoding:NSUTF8StringEncoding]
                                                       key:[mutualKey dataUsingEncoding:NSUTF8StringEncoding]]];

    NSDictionary *envelope = @{
        @"v": @2,
        @"alg": BSPHPAlgorithm,
        @"ts": timestamp,
        @"nonce": nonce,
        @"ek": ek,
        @"iv": ivValue,
        @"data": dataValue,
        @"mac": mac,
        @"auth": auth,
    };

    return @{
        @"envelope": envelope,
        @"sessionKey": sessionKey,
        @"requestNonce": nonce,
    };
}

+ (NSString *)openResponse:(NSDictionary<NSString *,id> *)envelope
                 sessionKey:(NSData *)sessionKey
               requestNonce:(NSString *)requestNonce
                      error:(NSError **)error
{
    if (sessionKey.length != 32 ||
        ![envelope[@"v"] isEqual:@2] ||
        ![envelope[@"alg"] isEqual:BSPHPAlgorithm] ||
        ![envelope[@"direction"] isEqual:@"response"] ||
        ![envelope[@"request_nonce"] isEqual:requestNonce]) {
        [self setError:error code:1101 message:@"响应上下文不匹配"];
        return nil;
    }

    NSArray<NSString *> *required = @[@"ts", @"nonce", @"iv", @"data", @"mac"];
    for (NSString *field in required) {
        if (!envelope[field]) {
            [self setError:error code:1102 message:[@"响应缺少字段：" stringByAppendingString:field]];
            return nil;
        }
    }

    NSString *canonical = [NSString stringWithFormat:@"2|%@|response|%@|%@|%@|%@|%@",
                           BSPHPAlgorithm,
                           envelope[@"ts"],
                           envelope[@"nonce"],
                           requestNonce,
                           envelope[@"iv"],
                           envelope[@"data"]];
    NSData *macKey = [self hmacSHA256:[@"bsphp-v2/mac" dataUsingEncoding:NSUTF8StringEncoding]
                                  key:sessionKey];
    NSString *expectedMac = [self base64URL:[self hmacSHA256:[canonical dataUsingEncoding:NSUTF8StringEncoding]
                                                              key:macKey]];
    if (![self constantTimeEqual:expectedMac other:envelope[@"mac"]]) {
        [self setError:error code:1103 message:@"响应完整性校验失败"];
        return nil;
    }

    NSData *iv = [self base64URLDecode:envelope[@"iv"]];
    NSData *ciphertext = [self base64URLDecode:envelope[@"data"]];
    NSData *encKey = [self hmacSHA256:[@"bsphp-v2/enc" dataUsingEncoding:NSUTF8StringEncoding]
                                  key:sessionKey];
    NSData *plaintext = [self aesCBC:ciphertext key:encKey iv:iv encrypt:NO error:error];
    if (!plaintext) return nil;

    NSString *result = [[NSString alloc] initWithData:plaintext encoding:NSUTF8StringEncoding];
    if (!result) [self setError:error code:1104 message:@"响应不是 UTF-8 文本"];
    return result;
}

+ (SecKeyRef)publicKeyFromValue:(NSString *)value error:(NSError **)error CF_RETURNS_RETAINED
{
    // New configurations use one-line Base64. Keep accepting historical PEM
    // values so an older BSPHPConfig.h can still connect after an upgrade.
    NSString *body = [value stringByReplacingOccurrencesOfString:@"-----BEGIN RSA PUBLIC KEY-----" withString:@""];
    body = [body stringByReplacingOccurrencesOfString:@"-----END RSA PUBLIC KEY-----" withString:@""];
    NSCharacterSet *whitespace = [NSCharacterSet whitespaceAndNewlineCharacterSet];
    body = [[body componentsSeparatedByCharactersInSet:whitespace] componentsJoinedByString:@""];
    NSData *der = [[NSData alloc] initWithBase64EncodedString:body options:0];
    if (!der) {
        [self setError:error code:1001 message:@"服务器公钥格式错误"];
        return NULL;
    }

    NSDictionary *attributes = @{
        (__bridge id)kSecAttrKeyType: (__bridge id)kSecAttrKeyTypeRSA,
        (__bridge id)kSecAttrKeyClass: (__bridge id)kSecAttrKeyClassPublic,
        (__bridge id)kSecAttrKeySizeInBits: @2048,
    };
    CFErrorRef keyError = NULL;
    SecKeyRef key = SecKeyCreateWithData((__bridge CFDataRef)der,
                                         (__bridge CFDictionaryRef)attributes,
                                         &keyError);
    if (!key && error) *error = CFBridgingRelease(keyError);
    return key;
}

+ (NSData *)randomData:(NSUInteger)length error:(NSError **)error
{
    NSMutableData *data = [NSMutableData dataWithLength:length];
    if (SecRandomCopyBytes(kSecRandomDefault, length, data.mutableBytes) != errSecSuccess) {
        [self setError:error code:1201 message:@"生成安全随机数失败"];
        return nil;
    }
    return data;
}

+ (NSData *)hmacSHA256:(NSData *)data key:(NSData *)key
{
    unsigned char output[CC_SHA256_DIGEST_LENGTH];
    CCHmac(kCCHmacAlgSHA256, key.bytes, key.length, data.bytes, data.length, output);
    return [NSData dataWithBytes:output length:sizeof(output)];
}

+ (NSData *)aesCBC:(NSData *)input
                key:(NSData *)key
                 iv:(NSData *)iv
            encrypt:(BOOL)encrypt
              error:(NSError **)error
{
    if (key.length != kCCKeySizeAES256 || iv.length != kCCBlockSizeAES128) {
        [self setError:error code:1202 message:@"AES 密钥或 IV 长度错误"];
        return nil;
    }

    size_t capacity = input.length + kCCBlockSizeAES128;
    NSMutableData *output = [NSMutableData dataWithLength:capacity];
    size_t moved = 0;
    CCCryptorStatus status = CCCrypt(encrypt ? kCCEncrypt : kCCDecrypt,
                                     kCCAlgorithmAES,
                                     kCCOptionPKCS7Padding,
                                     key.bytes,
                                     key.length,
                                     iv.bytes,
                                     input.bytes,
                                     input.length,
                                     output.mutableBytes,
                                     output.length,
                                     &moved);
    if (status != kCCSuccess) {
        [self setError:error code:1203 message:@"AES 数据处理失败"];
        return nil;
    }
    output.length = moved;
    return output;
}

+ (NSString *)base64URL:(NSData *)data
{
    NSString *value = [data base64EncodedStringWithOptions:0];
    value = [value stringByReplacingOccurrencesOfString:@"+" withString:@"-"];
    value = [value stringByReplacingOccurrencesOfString:@"/" withString:@"_"];
    return [value stringByTrimmingCharactersInSet:[NSCharacterSet characterSetWithCharactersInString:@"="]];
}

+ (NSData *)base64URLDecode:(NSString *)value
{
    value = [value stringByReplacingOccurrencesOfString:@"-" withString:@"+"];
    value = [value stringByReplacingOccurrencesOfString:@"_" withString:@"/"];
    NSUInteger remainder = value.length % 4;
    if (remainder) value = [value stringByPaddingToLength:value.length + (4 - remainder)
                                               withString:@"="
                                          startingAtIndex:0];
    return [[NSData alloc] initWithBase64EncodedString:value options:0];
}

+ (BOOL)constantTimeEqual:(NSString *)left other:(NSString *)right
{
    NSData *a = [left dataUsingEncoding:NSUTF8StringEncoding];
    NSData *b = [right dataUsingEncoding:NSUTF8StringEncoding];
    if (a.length != b.length) return NO;
    const uint8_t *ab = (const uint8_t *)a.bytes;
    const uint8_t *bb = (const uint8_t *)b.bytes;
    uint8_t difference = 0;
    for (NSUInteger i = 0; i < a.length; i++) difference |= ab[i] ^ bb[i];
    return difference == 0;
}

+ (void)setError:(NSError **)error code:(NSInteger)code message:(NSString *)message
{
    if (error) *error = [NSError errorWithDomain:BSPHPErrorDomain
                                             code:code
                                         userInfo:@{NSLocalizedDescriptionKey: message}];
}

@end
