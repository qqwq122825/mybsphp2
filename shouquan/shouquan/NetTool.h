#import <Foundation/Foundation.h>

NS_ASSUME_NONNULL_BEGIN

typedef void (^NetToolSuccess)(id responseObject);
typedef void (^NetToolFailure)(NSError *error);

/// BSPHP 网络层：内部完成请求加密、响应验签和第一层解密。
/// 业务页面只需要传参数并处理回调。
@interface NetTool : NSObject

+ (void)Post_AppendURL:(NSString *)URLString
             parameters:(NSDictionary<NSString *, id> *)parameters
                success:(NetToolSuccess)success
                failure:(NetToolFailure)failure;

@end

NS_ASSUME_NONNULL_END
