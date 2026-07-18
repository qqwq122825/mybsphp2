//
//  HUDMainApplicationDelegate.m
//  Ai
//
//  Created by a on 2025/8/19.
//

#import "HUDMainApplicationDelegate.h"
#import <UIKit/UIKit.h>
#import <objc/runtime.h>
#import <objc/message.h>
#import "HUDMainWindow.h"
#import "HUDRootViewController.h"
#import "UIWindow+Private.h" // 确保声明了 -_contextId
#import "SBSAccessibilityWindowHostingController.h"


@implementation HUDMainApplicationDelegate {
    SBSAccessibilityWindowHostingController *_windowHostingController;
}

- (instancetype)init {
    if (self = [super init]) {
        NSLog(@"[HUD] HUDMainApplicationDelegate init");
    }
    return self;
}

- (BOOL)application:(UIApplication *)application didFinishLaunchingWithOptions:(NSDictionary *)launchOptions {
    // 1) 创建主 HUD 窗口
    HUDMainWindow *mainWindow = [[HUDMainWindow alloc] initWithFrame:[UIScreen mainScreen].bounds];
    HUDRootViewController *rootVC = [HUDRootViewController new];
    mainWindow.rootViewController = rootVC;
    mainWindow.windowLevel = UIWindowLevelStatusBar + 2; // 保证在状态栏之上
    mainWindow.hidden = NO;
    [mainWindow makeKeyAndVisible];
    self.window = mainWindow;

    // 2) 注册窗口到系统级的 Accessibility Hosting Controller
    _windowHostingController = [[objc_getClass("SBSAccessibilityWindowHostingController") alloc] init];
    NSMethodSignature *sig = [NSMethodSignature signatureWithObjCTypes:"v@:Id"];
    SEL selReg = NSSelectorFromString(@"registerWindowWithContextID:atLevel:");

    unsigned int contextId = [mainWindow _contextId];
    double windowLevel = mainWindow.windowLevel;

    NSInvocation *inv = [NSInvocation invocationWithMethodSignature:sig];
    [inv setTarget:_windowHostingController];
    [inv setSelector:selReg];
    [inv setArgument:&contextId atIndex:2];
    [inv setArgument:&windowLevel atIndex:3];
    [inv invoke];

    return YES;
}
- (UISceneConfiguration *)application:(UIApplication *)application
        configurationForConnectingSceneSession:(UISceneSession *)connectingSceneSession
                                     options:(UISceneConnectionOptions *)options
{
    if (@available(iOS 13.0, *)) {
        if (getenv("HUD_MODE")) {
            UISceneConfiguration *cfg = [[UISceneConfiguration alloc] initWithName:@"HUDScene"
                                                                        sessionRole:connectingSceneSession.role];
            cfg.delegateClass = NSClassFromString(@"HUDSceneDelegate");
            return cfg;
        }
    }
    return nil; // 非 HUD 模式：沿用 Info.plist 的默认配置
}

@end
