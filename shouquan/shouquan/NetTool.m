#import "NetTool.h"
#import "BSPHPConfig.h"
#import "BSPHPTransportCrypto.h"

static NSString * const NetToolErrorDomain = @"com.mybsphp.nettool";

@implementation NetTool

+ (void)Post_AppendURL:(NSString *)URLString
             parameters:(NSDictionary<NSString *,id> *)parameters
                success:(NetToolSuccess)success
                failure:(NetToolFailure)failure {
    NSURL *URL = [NSURL URLWithString:URLString ?: @""];
    if (URL == nil) {
        [self finishFailure:failure error:[self error:@"服务器地址错误" code:-1]];
        return;
    }

    NSString *plainBody = [self formBody:parameters];
#if DEBUG
    NSLog(@"\n[BSPHP][请求地址]\n%@", URLString);
    NSLog(@"\n[BSPHP][请求加密前]\n%@", plainBody);
#endif
    NSError *cryptoError = nil;
    BOOL accountEndpoint = [URLString isEqualToString:BSPHP_ACCOUNT_HOST];
    NSString *publicKey = accountEndpoint ? BSPHP_ACCOUNT_PUBLIC_KEY : BSPHP_CARD_PUBLIC_KEY;
    NSString *mutualKey = accountEndpoint ? BSPHP_ACCOUNT_MUTUALKEY : BSPHP_CARD_MUTUALKEY;
    NSDictionary *sealed = [BSPHPTransportCrypto sealRequest:plainBody
                                                publicKeyPEM:publicKey
                                                   mutualKey:mutualKey
                                                       error:&cryptoError];
    if (sealed == nil) {
        [self finishFailure:failure error:cryptoError ?: [self error:@"请求加密失败" code:-2]];
        return;
    }

    NSError *jsonError = nil;
    // 线上使用无字段名的紧凑数组，避免在数据包中直接暴露 iv/data/mac 等语义名称。
    NSArray *wireRequest = [self compactRequestEnvelope:sealed[@"envelope"]];
    NSData *HTTPBody = [NSJSONSerialization dataWithJSONObject:wireRequest options:0 error:&jsonError];
    if (HTTPBody == nil) {
        [self finishFailure:failure error:jsonError ?: [self error:@"请求编码失败" code:-3]];
        return;
    }

#if DEBUG
    NSString *encryptedRequest = [[NSString alloc] initWithData:HTTPBody encoding:NSUTF8StringEncoding];
    NSLog(@"\n[BSPHP][请求加密后/最终 HTTP Body]\n%@", encryptedRequest);
#endif

    NSMutableURLRequest *request = [NSMutableURLRequest requestWithURL:URL];
    request.HTTPMethod = @"POST";
    request.HTTPBody = HTTPBody;
    request.timeoutInterval = 20;
    [request setValue:@"application/json" forHTTPHeaderField:@"Content-Type"];
    [request setValue:@"application/json" forHTTPHeaderField:@"Accept"];

    NSURLSessionConfiguration *configuration = NSURLSessionConfiguration.ephemeralSessionConfiguration;
    configuration.timeoutIntervalForRequest = 20;
    NSURLSession *session = [NSURLSession sessionWithConfiguration:configuration];
    [[session dataTaskWithRequest:request completionHandler:^(NSData *data, NSURLResponse *response, NSError *networkError) {
        if (networkError != nil) {
            [self finishFailure:failure error:networkError];
            return;
        }

#if DEBUG
        NSString *encryptedResponse = [[NSString alloc] initWithData:data encoding:NSUTF8StringEncoding];
        NSLog(@"\n[BSPHP][响应解密前/原始 HTTP Body]\n%@", encryptedResponse);
#endif

        NSError *parseError = nil;
        id JSON = data.length ? [NSJSONSerialization JSONObjectWithData:data options:0 error:&parseError] : nil;
        if (![JSON isKindOfClass:NSDictionary.class] && ![JSON isKindOfClass:NSArray.class]) {
            [self finishFailure:failure error:parseError ?: [self error:@"服务器返回格式错误" code:-4]];
            return;
        }

        NSDictionary *dictionary = [JSON isKindOfClass:NSDictionary.class] ? JSON : nil;
        if ([dictionary[@"ok"] isEqual:@NO]) {
            NSDictionary *errorData = [dictionary[@"error"] isKindOfClass:NSDictionary.class] ? dictionary[@"error"] : nil;
            NSString *message = [errorData[@"message"] isKindOfClass:NSString.class] ? errorData[@"message"] : @"请求失败";
            NSInteger statusCode = [(NSHTTPURLResponse *)response statusCode];
            NSMutableDictionary *userInfo = [@{NSLocalizedDescriptionKey: message} mutableCopy];
            if ([errorData[@"code"] isKindOfClass:NSString.class]) userInfo[@"BSPHPErrorCode"] = errorData[@"code"];
            if ([errorData[@"details"] isKindOfClass:NSDictionary.class]) userInfo[@"BSPHPErrorDetails"] = errorData[@"details"];
            NSError *serverError = [NSError errorWithDomain:NetToolErrorDomain code:statusCode ?: -5 userInfo:userInfo];
            [self finishFailure:failure error:serverError];
            return;
        }

        NSDictionary *responseEnvelope = [JSON isKindOfClass:NSArray.class]
            ? [self expandResponsePacket:JSON]
            : dictionary;
        if (responseEnvelope == nil) {
            [self finishFailure:failure error:[self error:@"紧凑响应格式错误" code:-7]];
            return;
        }

        NSError *decryptError = nil;
        NSString *plainResponse = [BSPHPTransportCrypto openResponse:responseEnvelope
                                                           sessionKey:sealed[@"sessionKey"]
                                                         requestNonce:sealed[@"requestNonce"]
                                                                error:&decryptError];
        if (plainResponse == nil) {
            [self finishFailure:failure error:decryptError ?: [self error:@"响应解密失败" code:-6]];
            return;
        }

#if DEBUG
        NSLog(@"\n[BSPHP][响应第一层解密后]\n%@", plainResponse);
#endif
        dispatch_async(dispatch_get_main_queue(), ^{ if (success) success(plainResponse); });
    }] resume];
}

+ (NSArray *)compactRequestEnvelope:(NSDictionary *)envelope {
    // Wire v3 请求位置表详见 README/WIRE_V3.md；这里只负责序列化，不改变密码学计算。
    return @[
        @3,
        envelope[@"ts"] ?: @0,
        envelope[@"nonce"] ?: @"",
        envelope[@"ek"] ?: @"",
        envelope[@"iv"] ?: @"",
        envelope[@"data"] ?: @"",
        envelope[@"mac"] ?: @"",
        envelope[@"auth"] ?: @""
    ];
}

+ (NSDictionary *)expandResponsePacket:(NSArray *)packet {
    // Wire v3 响应位置表详见 README/WIRE_V3.md；还原后交给统一验签/解密实现。
    if (packet.count != 7 || ![packet[0] isEqual:@3]) return nil;
    return @{
        @"v": @2,
        @"alg": @"RSA-OAEP+A256CBC-HS256",
        @"direction": @"response",
        @"ts": packet[1],
        @"nonce": packet[2],
        @"request_nonce": packet[3],
        @"iv": packet[4],
        @"data": packet[5],
        @"mac": packet[6]
    };
}

+ (NSString *)formBody:(NSDictionary<NSString *, id> *)parameters {
    NSMutableArray<NSString *> *parts = [NSMutableArray arrayWithCapacity:parameters.count];
    NSMutableCharacterSet *allowed = [NSCharacterSet URLQueryAllowedCharacterSet].mutableCopy;
    [allowed removeCharactersInString:@"&=+?"];
    for (NSString *key in [parameters.allKeys sortedArrayUsingSelector:@selector(compare:)]) {
        NSString *value = [NSString stringWithFormat:@"%@", parameters[key] ?: @""];
        NSString *encodedKey = [key stringByAddingPercentEncodingWithAllowedCharacters:allowed] ?: @"";
        NSString *encodedValue = [value stringByAddingPercentEncodingWithAllowedCharacters:allowed] ?: @"";
        [parts addObject:[NSString stringWithFormat:@"%@=%@", encodedKey, encodedValue]];
    }
    return [parts componentsJoinedByString:@"&"];
}

+ (NSError *)error:(NSString *)message code:(NSInteger)code {
    return [NSError errorWithDomain:NetToolErrorDomain
                               code:code
                           userInfo:@{NSLocalizedDescriptionKey: message ?: @"请求失败"}];
}

+ (void)finishFailure:(NetToolFailure)failure error:(NSError *)error {
    dispatch_async(dispatch_get_main_queue(), ^{ if (failure) failure(error); });
}

@end
