#import <Foundation/Foundation.h>

NS_ASSUME_NONNULL_BEGIN

typedef void (^NetToolSuccess)(id responseObject);
typedef void (^NetToolFailure)(NSError *error);

/// 服务端业务错误附加字段，供页面统一识别封控、到期等状态。
FOUNDATION_EXPORT NSString * const BSPHPErrorCodeUserInfoKey;
FOUNDATION_EXPORT NSString * const BSPHPErrorDetailsUserInfoKey;
FOUNDATION_EXPORT NSString * const BSPHPRetryAfterUserInfoKey;

/// BSPHP 网络层：内部完成请求加密、响应验签和第一层解密。
/// 业务页面只需要传参数并处理回调。
@interface NetTool : NSObject

+ (void)Post_AppendURL:(NSString *)URLString
             parameters:(NSDictionary<NSString *, id> *)parameters
                success:(NetToolSuccess)success
                failure:(NetToolFailure)failure;

@end

NS_ASSUME_NONNULL_END
