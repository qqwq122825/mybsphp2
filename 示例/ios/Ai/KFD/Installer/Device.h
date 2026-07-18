#import <Foundation/Foundation.h>
#import "Version.h"
#import <sys/sysctl.h>

typedef NS_ENUM(NSInteger, CPUFamily) {
    CPUFamilyUnknown,
    CPUFamilyA8,
    CPUFamilyA9,
    CPUFamilyA10,
    CPUFamilyA11,
    CPUFamilyA12,
    CPUFamilyA13,
    CPUFamilyA14,
    CPUFamilyA15,
    CPUFamilyA16
};

@interface Device : NSObject
//@property (nonatomic, copy) NSString *version;
@property (nonatomic, readonly) Version *version;
@property (nonatomic, readonly) BOOL isArm64e;
@property (nonatomic, readonly) BOOL supportsOTA;
@property (nonatomic, readonly) BOOL isSupported;
@property (nonatomic, readonly) BOOL isOnSupported17Beta;
@property (nonatomic) CPUFamily cpuFamily;

@property (nonatomic, readonly) NSString *modelIdentifier;
@property (nonatomic, readonly) BOOL supportsDirectInstall;

- (instancetype)init;

@end
