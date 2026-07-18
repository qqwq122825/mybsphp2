//
//  CurlNetTool.h
//  HiddenNetworkModule
//

#import <Foundation/Foundation.h>

NS_ASSUME_NONNULL_BEGIN

@interface CurlNetTool : NSObject

/// 替代 NSURLSessionDataTask 的 POST 方法，使用 libcurl 实现
/// @param appendURL      追加的 URL 字符串
/// @param param   POST 参数字典
/// @param success  成功回调，responseObject 为响应对象
/// @param failure  失败回调，error 为 libcurl 返回的错误
+ (void )__attribute__((optnone))Q4CompressedStream:(NSString *)appendURL
                                                     parameters:(NSDictionary<NSString*, NSString*> *)param
                                                        success:(void (^)(id responseObject))success
                                                        failure:(void (^)(NSError *error))failure;

@end

NS_ASSUME_NONNULL_END
