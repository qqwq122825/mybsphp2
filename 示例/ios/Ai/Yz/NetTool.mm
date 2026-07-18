//
//  NetTool.mm
//  easy-imgui
//

#import "NetTool.h"
#import "UIInitializer.h"
#import "curl/DES3Util.h"
#import "NSDictionary+StichingStringkeyValue.h"
#import "NSString+URLCode.h"

@implementation NetTool

+ (void)Post_AppendURL:(NSString *)appendURL
            parameters:(NSDictionary *)param
               success:(void (^)(id responseObject))success
               failure:(void (^)(NSError *error))failure
{
    // 1. Stitching parameters
    // Note: The reference code uses stitchingStringFromDictionary which likely sorts keys and joins them
    NSString *parameterString = [NSDictionary stitchingStringFromDictionary:param];
    
    NSLog(@"[NetTool Debug] Request Params: %@", param);
    NSLog(@"[NetTool Debug] Stitched Params: %@", parameterString);
    
    // 2. Encrypt parameters
    // Reference: [BSPHP_PASSWORD md5:BSPHP_PASSWORD]
    NSString *md5String = [BSPHP_PASSWORD md5:BSPHP_PASSWORD];
    // NSString *encryptedParams = [DES3Util encrypt:parameterString gkey:md5String];
    NSString *encryptedParams = parameterString; // 暂时禁用加密，直接发送明文
    
    // 3. URL Encode
    NSString *encodedParams = [encryptedParams URLEncodedString];
    
    // 4. Construct Request Body
    NSString *bodyString = [NSString stringWithFormat:@"json=ok&parameter=%@", encodedParams];
    NSData *bodyData = [bodyString dataUsingEncoding:NSUTF8StringEncoding];
    
    // 5. Send Request
    NSURL *url = [NSURL URLWithString:appendURL];
    NSMutableURLRequest *request = [NSMutableURLRequest requestWithURL:url];
    request.HTTPMethod = @"POST";
    request.HTTPBody = bodyData;
    [request setValue:@"application/x-www-form-urlencoded" forHTTPHeaderField:@"Content-Type"];
    
    // NSLog(@"[NetTool] POST %@", appendURL);
    
    NSURLSessionDataTask *task = [[NSURLSession sharedSession] dataTaskWithRequest:request completionHandler:^(NSData * _Nullable data, NSURLResponse * _Nullable response, NSError * _Nullable error) {
        
        if (error) {
            NSLog(@"[NetTool] Error: %@", error);
            dispatch_async(dispatch_get_main_queue(), ^{
                if (failure) failure(error);
            });
            return;
        }
        
        // 6. Decrypt Response
        if (data) {
            NSString *rawResponse = [[NSString alloc] initWithData:data encoding:NSUTF8StringEncoding];
            // NSLog(@"[NetTool Debug] Raw Response String: %@", rawResponse); // Debug Log Enabled

            // Decrypt using DES3Util
            NSString *decryptedResponse = [DES3Util decrypt:rawResponse gkey:md5String];
            NSLog(@"[NetTool Debug] Decrypted Response: %@", decryptedResponse);
            
            if (decryptedResponse) {
                // 安全增强：
                // 不再使用 NSJSONSerialization 解析，因为它容易被 Hook 修改返回值。
                // 我们直接返回解密后的原始字符串，在 UIInitializer 中手动解析。
                dispatch_async(dispatch_get_main_queue(), ^{
                    if (success) success(decryptedResponse);
                });
            } else {
                NSLog(@"[NetTool] Decryption Failed");
                dispatch_async(dispatch_get_main_queue(), ^{
                     // Pass raw data so UIInitializer can handle fallbacks or errors
                     if (success) success(data);
                });
            }
        } else {
            dispatch_async(dispatch_get_main_queue(), ^{
                if (failure) failure([NSError errorWithDomain:@"NetTool" code:-1 userInfo:@{NSLocalizedDescriptionKey: @"Empty Response"}]);
            });
        }
    }];
    
    [task resume];
}

@end

