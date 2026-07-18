#import <Foundation/Foundation.h>

@interface Version : NSObject

@property (nonatomic, readonly) NSInteger major;
@property (nonatomic, readonly) NSInteger minor;
@property (nonatomic, nullable, readonly) NSNumber *patch;

- (instancetype)initWithString:(NSString *)versionString;
- (NSComparisonResult)compare:(Version *)otherVersion;
- (BOOL)isLessThan:(Version *)otherVersion;
- (BOOL)isLessThanOrEqualTo:(Version *)otherVersion;
- (BOOL)isEqualToVersion:(Version *)otherVersion;
- (NSString *)readableString;

@end
