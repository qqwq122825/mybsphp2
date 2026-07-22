#import <Foundation/Foundation.h>

NS_ASSUME_NONNULL_BEGIN

/// 授权响应处理层：结构检查、每日校验、每次校验和二次解密。
@interface BSPHPResponseProcessor : NSObject

+ (nullable NSDictionary<NSString *, id> *)processCardResponse:(id)responseObject
                                                       cardCode:(NSString *)cardCode
                                                    appSafeCode:(NSString *)appSafeCode
                                                          error:(NSError **)error;

+ (nullable NSDictionary<NSString *, id> *)processAccountResponse:(id)responseObject
                                                        appSafeCode:(NSString *)appSafeCode
                                                              error:(NSError **)error;

+ (nullable NSDictionary<NSString *, id> *)processHeartbeatResponse:(id)responseObject
                                                        sessionHandle:(NSString *)sessionHandle
                                                          appSafeCode:(NSString *)appSafeCode
                                                                error:(NSError **)error;

@end

NS_ASSUME_NONNULL_END
