#import <Foundation/Foundation.h>

NS_ASSUME_NONNULL_BEGIN

/// MyBsPHP2 v2 packet crypto for Objective-C / Objective-C++ clients.
/// Link Security.framework and CommonCrypto.
@interface BSPHPTransportCrypto : NSObject

/// Returns { envelope, sessionKey, requestNonce }.
+ (nullable NSDictionary<NSString *, id> *)sealRequest:(NSString *)plaintext
                                          publicKeyPEM:(NSString *)publicKeyPEM
                                             mutualKey:(NSString *)mutualKey
                                                 error:(NSError **)error;

/// Verifies and decrypts a v2 response envelope.
+ (nullable NSString *)openResponse:(NSDictionary<NSString *, id> *)envelope
                         sessionKey:(NSData *)sessionKey
                       requestNonce:(NSString *)requestNonce
                              error:(NSError **)error;

@end

NS_ASSUME_NONNULL_END
