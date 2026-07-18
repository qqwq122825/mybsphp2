#import <Foundation/Foundation.h>
#import "Device.h"


@interface InitOpen : NSObject

@property (nonatomic, strong, readonly) Device *device;
@property (nonatomic, readonly) BOOL installationFinished;
@property (nonatomic, readonly) BOOL installedSuccessfully;
@property (nonatomic, strong) NSFileManager *fileManager;
@property (nonatomic, strong) NSString *docsDir;
@property (nonatomic, strong) NSString *kernelPath;

//- (BOOL)do_open; // 同步执行并返回结果
- (int)do_open; // 同步执行并返回结果
- (void)do_close; // 同步执行并返回结果
- (instancetype)init;
- (BOOL)doDirectInstallWithDevice:(Device *)device;

@end


