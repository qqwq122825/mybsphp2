#import "Device.h"
#import <UIKit/UIKit.h> // 新增UIKit导入
#import <sys/utsname.h>
#import <IOKit/IOKitLib.h>

@implementation Device

- (instancetype)init {
    self = [super init];
    if (self) {
        // 获取系统版本（关键修正）
        _version = [[Version alloc] initWithString:[[UIDevice currentDevice] systemVersion]];
        
        // 检查arm64e
        int32_t cpusubtype = 0;
        size_t len = sizeof(cpusubtype);
        sysctlbyname("hw.cpusubtype", &cpusubtype, &len, NULL, 0);
        _isArm64e = cpusubtype == CPU_SUBTYPE_ARM64E;
        
        // 检查OTA支持
        if (_isArm64e) {
            Version *otaMaxVersion = [[Version alloc] initWithString:@"15.7"];
            _supportsOTA = [_version isLessThan:otaMaxVersion];
        } else {
            Version *minVersion = [[Version alloc] initWithString:@"15.0"];
            Version *maxVersion = [[Version alloc] initWithString:@"15.5"];
            _supportsOTA = [_version isLessThanOrEqualTo:maxVersion] && ![_version isLessThan:minVersion];
        }
        
        // 获取CPU家族
        int deviceCPU = 0;
        len = sizeof(deviceCPU);
        sysctlbyname("hw.cpufamily", &deviceCPU, &len, NULL, 0);
        
        switch (deviceCPU) {
            case 0x2C91A47E: _cpuFamily = CPUFamilyA8; break;
            case 0x92FB37C8: _cpuFamily = CPUFamilyA9; break;
            case 0x67CEEE93: _cpuFamily = CPUFamilyA10; break;
            case 0xE81E7EF6: _cpuFamily = CPUFamilyA11; break;
            case 0x07D34B9F: _cpuFamily = CPUFamilyA12; break;
            case 0x462504D2: _cpuFamily = CPUFamilyA13; break;
            case 0x1B588BB3: _cpuFamily = CPUFamilyA14; break;
            case 0xDA33D83D: _cpuFamily = CPUFamilyA15; break;
            case 0x8765EDEA: _cpuFamily = CPUFamilyA16; break;
            default: _cpuFamily = CPUFamilyUnknown;
        }
        
        // 检查17 Beta版本
        len = 256;
        char buildNumber[256];
        sysctlbyname("kern.osversion", buildNumber, &len, NULL, 0);
        NSString *buildNumberStr = [NSString stringWithCString:buildNumber encoding:NSASCIIStringEncoding];
        NSArray *supportedBuilds = @[@"21A5248v", @"21A5268h", @"21A5277j", @"21A5291h", @"21A5291j"];
        _isOnSupported17Beta = [supportedBuilds containsObject:buildNumberStr];
        
        // 检查M2芯片
        BOOL isM2 = NO;
        io_registry_entry_t registryEntry = IORegistryEntryFromPath(MACH_PORT_NULL, "IODeviceTree:/chosen");
        if (registryEntry != MACH_PORT_NULL) {
            CFTypeRef chipID = IORegistryEntryCreateCFProperty(registryEntry, CFSTR("chip-id"), kCFAllocatorDefault, 0);
            if (chipID) {
                NSData *data = (__bridge NSData *)chipID;
                if (data.length >= sizeof(int)) {
                    int cpid = 0;
                    [data getBytes:&cpid length:sizeof(int)];
                    isM2 = cpid == 0x8112;
                }
                CFRelease(chipID);
            }
            IOObjectRelease(registryEntry);
        }
        
        // 判断设备是否受支持
        if (_cpuFamily == CPUFamilyA8) {
            Version *a8MaxVersion = [[Version alloc] initWithString:@"15.2"];
            _isSupported = [_version isLessThan:a8MaxVersion];
        } else {
            Version *maxVersion = [[Version alloc] initWithString:@"16.6.1"];
            BOOL baseCondition = [_version isLessThanOrEqualTo:maxVersion];
            
            BOOL betaCondition = _isOnSupported17Beta && !(
                (_cpuFamily == CPUFamilyA15 && !isM2) ||
                _cpuFamily == CPUFamilyA16
            );
            
            _isSupported = baseCondition || betaCondition;
        }
    }
    return self;
}

- (NSString *)modelIdentifier {
    // 处理模拟器情况
    NSString *simulatorID = [NSProcessInfo processInfo].environment[@"SIMULATOR_MODEL_IDENTIFIER"];
    if (simulatorID) return simulatorID;
    
    // 获取真实设备信息
    struct utsname sysinfo;
    uname(&sysinfo);
    return [NSString stringWithCString:sysinfo.machine encoding:NSASCIIStringEncoding];
}

- (BOOL)supportsDirectInstall {
    if (!self.isArm64e) return YES;
    
    Version *thresholdVersion;
    if (self.cpuFamily == CPUFamilyA15 || self.cpuFamily == CPUFamilyA16) {
        thresholdVersion = [[Version alloc] initWithString:@"16.5.1"];
    } else {
        thresholdVersion = [[Version alloc] initWithString:@"16.6"];
    }
    
    return [_version isLessThan:thresholdVersion];
}

@end
