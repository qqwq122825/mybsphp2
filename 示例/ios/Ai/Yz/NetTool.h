//
//  NetTool.h
//  easy-imgui
//

#import <Foundation/Foundation.h>

NS_ASSUME_NONNULL_BEGIN

@interface NetTool : NSObject

/// POST 请求方法，使用 NSURLSession 实现
/// @param appendURL      请求的 URL 字符串
/// @param param          POST 参数字典
/// @param success        成功回调，responseObject 为响应对象（字符串或 NSData）
/// @param failure        失败回调，error 为错误信息
+ (void)Post_AppendURL:(NSString *)appendURL
            parameters:(NSDictionary *)param
               success:(void (^)(id responseObject))success
               failure:(void (^)(NSError *error))failure;

@end

NS_ASSUME_NONNULL_END

