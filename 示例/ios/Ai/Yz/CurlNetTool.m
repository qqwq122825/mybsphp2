//
//  CurlNetTool.m
//  HiddenNetworkModule
//

#import "CurlNetTool.h"

#import "DES3Util.h"
#import "UIInitializer.h"
#import "NSDictionary+StichingStringkeyValue.h"
#import "NSString+MD5.h"
#import "NSString+URLCode.h"
#include "curl/curl.h"

// 是否打印日志
#define ConfigLog(fmt, ...) \
    if (Config_NSLog_ENABLED) { \
        NSLog((@"ConfigLog: " fmt), ##__VA_ARGS__); \
    }

#define Config_NSLog_ENABLED NO
// Mode: 0 = encrypted (default), 1 = send plaintext
static int kCurlNetToolMode = 1;
// Request method: 0 = GET (default), 1 = POST
static int kCurlNetToolRequestMethod = 0;

// libcurl 写回调，将输出写入 NSMutableData
static size_t write_callback(void *contents, size_t size, size_t nmemb, void *userp) {
    size_t realSize = size * nmemb;
    NSMutableData *buffer = (__bridge NSMutableData *)userp;
    [buffer appendBytes:contents length:realSize];
    return realSize;
}

@implementation CurlNetTool

+ (void)__attribute__((optnone))Q4CompressedStream:(NSString *)appendURL
                                    parameters:(NSDictionary<NSString*, NSString *> *)param
                                       success:(void (^)(id responseObject))success
                                       failure:(void (^)(NSError *error))failure
{
    // —— 1. 签名与加密逻辑 ——
    NSMutableDictionary *parameters = [NSMutableDictionary dictionary];
    parameters[@"json"] = @"ok";
    if (param) {
        ConfigLog(@"[CurlNetTool] original param dict: %@", param);
        if (kCurlNetToolMode == 0) {
            // Encrypted mode
            NSString *desString = [NSDictionary stitchingStringFromDictionary:param];
            NSString *md5String = [BSPHP_PASSWORD md5:BSPHP_PASSWORD];
            desString = [DES3Util encrypt:desString gkey:md5String];
            
            NSString *sginstr = [BSPHP_INSGIN stringByReplacingOccurrencesOfString:@"[KEY]" withString:desString];
            NSString *sginstrMD5 = [sginstr md5:sginstr];
            ConfigLog(@"replaceStr=%@", sginstrMD5);
            parameters[@"sgin"] = sginstrMD5;
            // Avoid double-encoding: store raw encrypted string; percent-encoding will happen later
            parameters[@"parameter"] = desString;
        } else {
            // Plaintext mode
            NSString *rawString = [NSDictionary stitchingStringFromDictionary:param];
            
            parameters[@"sgin"] = @""; // skip signature in plaintext
            parameters[@"parameter"] = rawString;
        }
    }
    ConfigLog(@"[CurlNetTool] parameters dict: %@", parameters);
    
    // —— 2. 构造 POST 字符串 ——
    NSMutableString *postString = [NSMutableString string];
    [parameters enumerateKeysAndObjectsUsingBlock:^(NSString *key, NSString *value, BOOL *stop) {
        // Percent-encode the key normally
        NSString *escKey = [key stringByAddingPercentEncodingWithAllowedCharacters:
                            [NSCharacterSet URLQueryAllowedCharacterSet]];
        // Create an allowed set that excludes '#', '&', and '=' so they are percent-encoded
        NSMutableCharacterSet *allowed = [[NSCharacterSet URLQueryAllowedCharacterSet] mutableCopy];
        [allowed removeCharactersInString:@"#&="];
        NSString *escVal = [value stringByAddingPercentEncodingWithAllowedCharacters:allowed];
        [postString appendFormat:@"%@=%@&", escKey, escVal];
    }];
    if (postString.length) {
        [postString deleteCharactersInRange:NSMakeRange(postString.length - 1, 1)];
    }
    ConfigLog(@"[CurlNetTool] postString: %@", postString);


    // Compute fullURL based on request method
    NSString *fullURL = appendURL;
    if (kCurlNetToolRequestMethod == 0) {
        // GET: append all parameters to URL
        if (postString.length > 0) {
            NSString *separator = [appendURL containsString:@"?"] ? @"&" : @"?";
            fullURL = [appendURL stringByAppendingFormat:@"%@%@", separator, postString];
        }
    }
    ConfigLog(@"[CurlNetTool] fullURL: %@", fullURL);

    // —— 3. 发起 libcurl 请求 ——
    curl_global_init(CURL_GLOBAL_ALL);
    
    CURL *curl = curl_easy_init();
    // Force verbose output for debugging
    curl_easy_setopt(curl, CURLOPT_STDERR, stdout);
    curl_easy_setopt(curl, CURLOPT_VERBOSE, 1L);

    if (kCurlNetToolRequestMethod == 1) {
        // POST request
        curl_easy_setopt(curl, CURLOPT_POST, 1L);
        curl_easy_setopt(curl, CURLOPT_POSTFIELDS, postString.UTF8String);
        // Use fullURL as URL
        curl_easy_setopt(curl, CURLOPT_URL, fullURL.UTF8String);
    } else {
        // GET request
        curl_easy_setopt(curl, CURLOPT_URL, fullURL.UTF8String);
    }

    if (!curl) {
        if (failure) {
            NSError *err = [NSError errorWithDomain:@"CurlNetTool" code:-1
                                           userInfo:@{NSLocalizedDescriptionKey:@"curl_easy_init returned NULL"}];
            failure(err);
        }
        return;
    }
    // 回调缓冲
    NSMutableData *responseData = [NSMutableData data];
    curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION, write_callback);
    curl_easy_setopt(curl, CURLOPT_WRITEDATA, (__bridge void *)responseData);
    // Ensure URL has HTTP scheme
    NSString *urlString = appendURL;
    if (![urlString hasPrefix:@"http://"] && ![urlString hasPrefix:@"https://"]) {
        urlString = [@"http://" stringByAppendingString:urlString];
    }
    //防抓包 & 混淆
    //curl_easy_setopt(curl, CURLOPT_PROXY, "");
    
//    curl_easy_setopt(curl, CURLOPT_PROXY, "http://192.168.0.2:8081");
    curl_easy_setopt(curl, CURLOPT_USERAGENT, "UIKitInit/1.0");
    // Add Content-Type header for form POST and disable Expect header
    struct curl_slist *headers = NULL;
    if (kCurlNetToolRequestMethod == 1) {
        // POST: set form URL-encoded content type
        headers = curl_slist_append(headers, "Content-Type: application/x-www-form-urlencoded");
    }
    headers = curl_slist_append(headers, "Connection: keep-alive");
    headers = curl_slist_append(headers, "Accept: */*");
    headers = curl_slist_append(headers, "Accept-Language: zh-Hans-CN;q=1");
    headers = curl_slist_append(headers, "Accept-Encoding: gzip, deflate");
    curl_easy_setopt(curl, CURLOPT_HTTPHEADER, headers);
    
    if (kCurlNetToolRequestMethod == 1) {
        ConfigLog(@"[CurlNetTool] Sending POST request to URL: %@", fullURL);
    } else {
        ConfigLog(@"[CurlNetTool] Sending GET request to URL: %@", fullURL);
    }
    // Force HTTP/1.0 to avoid chunked transfer and set Content-Length
    curl_easy_setopt(curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    
    // 异步执行
    dispatch_async(dispatch_get_global_queue(DISPATCH_QUEUE_PRIORITY_DEFAULT, 0), ^{
        CURLcode res = curl_easy_perform(curl);
        NSString *rawResponse = [[NSString alloc] initWithData:responseData encoding:NSUTF8StringEncoding];
        ConfigLog(@"[CurlNetTool] raw response data: %@", rawResponse);
        // 清理
        // Clean up headers list
        curl_slist_free_all(headers);
        curl_easy_cleanup(curl);
        curl_global_cleanup();
        
        if (res != CURLE_OK) {
            if (failure) {
                NSError *err = [NSError errorWithDomain:@"CurlNetTool" code:res
                                               userInfo:@{NSLocalizedDescriptionKey:
                                                          [NSString stringWithUTF8String:
                                                           curl_easy_strerror(res)]}];
                failure(err);
            }
        } else {
            // —— 4. 解密逻辑 ——
            // 原始返回是加密后的 base64 文本
            NSString *encText = [[NSString alloc] initWithData:responseData
                                                      encoding:NSUTF8StringEncoding];
            NSString *key = [BSPHP_PASSWORD md5:BSPHP_PASSWORD];
            NSString *plain = [DES3Util decrypt:encText gkey:key];
            ConfigLog(@"[CurlNetTool] decrypted text: %@", plain);
            NSData *data = [plain dataUsingEncoding:NSUTF8StringEncoding];
            if (success) success(data);
        }
    });

    // 返回 nil 占位，调用方不用关心
}

@end
