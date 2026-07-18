#import <UIKit/UIKit.h>
#import "InitOpen.h"
#import "patchfind.h" // 包含 initialise_kernel_info 声明
#import "kfd/kfd.h"
//#import "libgrabkernel2.h"
#import "escalate.h"
#import "dmaFail.h"
#import "kalloc_pt.h"

// ---- Version helpers ----
static inline NSComparisonResult _vercmp(NSString *a, NSString *b) {
    return [a compare:b options:NSNumericSearch];
}
static inline BOOL _ver_in_range(NSString *v, NSString *min, NSString *max) {
    return (_vercmp(v, min) != NSOrderedAscending) && (_vercmp(v, max) != NSOrderedDescending);
}
static inline BOOL _physpuppet_supported_for(NSString *v) {
    // physpuppet 支持：14.0~15.7.3（含）以及 16.0~16.3.1（含）
    return _ver_in_range(v, @"14.0", @"15.7.3") || _ver_in_range(v, @"16.0", @"16.3.1");
}

@implementation InitOpen

- (instancetype)init {
    self = [super init];
    if (self) {
        _fileManager = [NSFileManager defaultManager];
        NSURL *docsURL = [_fileManager URLsForDirectory:NSDocumentDirectory inDomains:NSUserDomainMask].firstObject;
        _docsDir = docsURL.path;
        
        // 默认使用原路径
        _kernelPath = @"";

        _device = [[Device alloc] init];
    }
    return self;
}




- (void)do_close{
    krw_deinit();
    PPLRW_deinit();
}





- (int)do_open {
//    NSLog(@"开始安装");
    


    if (!_device) {
        _device = [[Device alloc] init];
    }

    if (_installationFinished) {
//        NSLog(@"Installation already completed");
        return _installedSuccessfully ? 0 : -1;
    }

    if (!_device.isSupported) {
//        NSLog(@"❌ Device not supported");
        _installationFinished = YES;
        return -2;
    }

    BOOL iOS14;
    bool supportsFullPhysRW = false;

    if (@available(iOS 15, *)) {
        iOS14 = false;
        supportsFullPhysRW = true;
    } else {
        iOS14 = true;
    }

    if (!iOS14) {
        if (![self getKernelWithDevice:_device]) {
//            NSLog(@"Failed to get kernel");
            _installationFinished = YES;
            _installedSuccessfully = NO;
            return -3;
        }
    }

//    NSLog(@"内核文件 %@", _kernelPath);
//    NSLog(@"Gathering kernel information");

    if (!initialise_kernel_info(_kernelPath.UTF8String, iOS14)) {
//        NSLog(@"Failed to patchfind kernel");
        _installationFinished = YES;
        _installedSuccessfully = NO;
        return -4;
    }


//
    // 选择后端：若系统版本在 physpuppet 支持范围内（14.0~15.7.3, 16.0~16.3.1），优先用 physpuppet；
    // 超出范围或初始化失败时回退 landa。16.5+ 会自然走 landa。
    NSString *sysVer = [UIDevice currentDevice].systemVersion ?: @"0";
    BOOL tryPuppet = _physpuppet_supported_for(sysVer);

    if (tryPuppet) {
        if (!krw_init_physpuppet()) {
            // 失败则回退 landa
            if (!krw_init_landa()) {
                _installationFinished = YES;
                _installedSuccessfully = NO;
                return -5;
            }
        }
    } else {
        // 不在支持范围，直接用 landa
        if (!krw_init_landa()) {
            _installationFinished = YES;
            _installedSuccessfully = NO;
            return -5;
        }
    }

    
    
    
    
    post_kernel_exploit(iOS14);

//    if (supportsFullPhysRW) {
//        if (_device.isArm64e) {
////            NSLog(@"isArm64e");
//
//            if (!PPLRW_init()) {
////                NSLog(@"ppl 安装失败");
//                _installationFinished = YES;
//                _installedSuccessfully = NO;
//                return -6;
//            }
//        }
//
//        if (@available(iOS 16, *)) {
////            NSLog(@"alloc");
////            libjailbreak_kalloc_pt_init();
//        }
//
//
//
//    }

    _installedSuccessfully = YES;
    _installationFinished = YES;
//    NSLog(@" 安装成功准备返回");
    return 0;
}



- (BOOL)getKernelWithDevice:(Device *)device {
    // 1. 首先检查自定义目录是否存在
//    NSString *customPath = @"/var/mobile/Library/tersafe";
//    if ([_fileManager fileExistsAtPath:customPath]) {
//        // 如果自定义目录存在，使用自定义目录
//        _kernelPath = customPath;
//        return YES;
//    }
    
    // 2. 自定义目录不存在，检查系统目录
    NSString *systemKernelPath = [self findSystemKernelPath];
    if (systemKernelPath) {
        // 如果系统目录存在，使用系统目录
        _kernelPath = systemKernelPath;
        return YES;
    }
    
    // 3. 两个目录都不存在，返回NO
    return NO;
}


- (NSString *)findSystemKernelPath {
    NSError *error = nil;
    NSArray *prebootContents = [_fileManager contentsOfDirectoryAtPath:@"/private/preboot" error:&error];
    
    if (error) {
//        NSLog(@"❌ 访问/private/preboot失败: %@", error);
        return nil;
    }
    
    for (NSString *item in prebootContents) {
        // 移除长度限制 - 兼容32/64位系统目录
        NSCharacterSet *hexSet = [NSCharacterSet characterSetWithCharactersInString:@"0123456789ABCDEF"];
        
        // 仅验证是否为十六进制字符串
        if ([item rangeOfCharacterFromSet:[hexSet invertedSet]].location == NSNotFound) {
            // 拼接系统内核路径
            NSString *kernelPath = [NSString stringWithFormat:
                                   @"/private/preboot/%@/System/Library/Caches/com.apple.kernelcaches/kernelcache",
                                   item];
            
            if ([_fileManager fileExistsAtPath:kernelPath]) {
                // 调试输出实际目录长度
//                NSLog(@"✅ 找到内核: %@ (目录长度: %ld)", kernelPath, item.length);
                return kernelPath;
            }
        }
    }
    
    return nil;
}

//+ (BOOL)isIOS14ForDevice:(Device *)device {
//    Version *v150 = [[Version alloc] initWithString:@"15.0"];
//    Version *v1511 = [[Version alloc] initWithString:@"15.1.1"];
//    Version *v152 = [[Version alloc] initWithString:@"15.2"];
//
//    // 是否小于 iOS 15
//    BOOL isBefore15 = [device.version isLessThan:v150];
//
//    // 是否支持 full physical read/write
//    BOOL supportsFullPhysRW = !([device.cpuFamily isEqualToString:@"A8"] && [device.version isGreaterThan:v1511]) &&
//        ((device.isArm64e && [device.version isGreaterThanOrEqualTo:v152]) ||
//         (!device.isArm64e && [device.version isGreaterThanOrEqualTo:v150]));
//
//    // 如果是 iOS14 或者不支持 full phys RW，就视为 iOS14 环境处理
//    return isBefore15 || !supportsFullPhysRW;
//}




- (BOOL)checkForMDCUnsandbox {
    // 实现沙箱检查逻辑
    NSString *testPath = [_docsDir stringByAppendingPathComponent:@"sandbox_test.txt"];
    return [_fileManager createFileAtPath:testPath contents:nil attributes:nil];
}

@end
