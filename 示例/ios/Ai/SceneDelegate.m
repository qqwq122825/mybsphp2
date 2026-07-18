//
//  SceneDelegate.m
//  Ai
//
//  Created by a on 2025/8/19.
//

#import "SceneDelegate.h"
#import <UIKit/UIKit.h>

@interface SceneDelegate ()

@property (nonatomic, strong) UIView *privacyShield; // 防快照遮罩

@end

/// 清理【当前 App 沙盒】下的 SplashBoard/Caches 快照；无法清理系统全局目录
static inline void ClearMyAppSnapshots(void) {
    NSString *bid = [NSBundle mainBundle].bundleIdentifier ?: @"";
    NSArray<NSString *> *dirs = @[
        [NSHomeDirectory() stringByAppendingPathComponent:@"Library/SplashBoard/Snapshots"],
        [NSHomeDirectory() stringByAppendingPathComponent:@"Library/Caches/Snapshots"],
        [NSHomeDirectory() stringByAppendingPathComponent:[NSString stringWithFormat:@"Library/SplashBoard/Snapshots/%@", bid]],
        [NSHomeDirectory() stringByAppendingPathComponent:[NSString stringWithFormat:@"Library/Caches/Snapshots/%@", bid]],
    ];
    NSFileManager *fm = NSFileManager.defaultManager;
    for (NSString *dir in dirs) {
        BOOL isDir = NO;
        if ([fm fileExistsAtPath:dir isDirectory:&isDir] && isDir) {
            NSArray *items = [fm contentsOfDirectoryAtPath:dir error:nil] ?: @[];
            for (NSString *name in items) {
                [fm removeItemAtPath:[dir stringByAppendingPathComponent:name] error:nil];
            }
        }
    }
}

@implementation SceneDelegate

#pragma mark - Privacy Shield

- (void)installPrivacyShield {
    if (self.privacyShield.superview) return;
    UIWindow *win = self.window ?: UIApplication.sharedApplication.keyWindow ?: UIApplication.sharedApplication.windows.firstObject;
    if (!win) return;

    // 纯黑覆盖；如需毛玻璃可改为 UIVisualEffectView
    UIView *shield = [[UIView alloc] initWithFrame:win.bounds];
    shield.autoresizingMask = UIViewAutoresizingFlexibleWidth | UIViewAutoresizingFlexibleHeight;
    shield.backgroundColor = [UIColor blackColor];
    shield.alpha = 0.0;
    [win addSubview:shield];
    self.privacyShield = shield;
    [UIView animateWithDuration:0.12 animations:^{ shield.alpha = 1.0; }];
}

- (void)removePrivacyShield {
    if (!self.privacyShield) return;
    UIView *shield = self.privacyShield; self.privacyShield = nil;
    [UIView animateWithDuration:0.12 animations:^{ shield.alpha = 0.0; }
                     completion:^(BOOL finished) { [shield removeFromSuperview]; }];
}

- (void)scene:(UIScene *)scene willConnectToSession:(UISceneSession *)session options:(UISceneConnectionOptions *)connectionOptions {
    // Use this method to optionally configure and attach the UIWindow `window` to the provided UIWindowScene `scene`.
    // If using a storyboard, the `window` property will automatically be initialized and attached to the scene.
    // This delegate does not imply the connecting scene or session are new (see `application:configurationForConnectingSceneSession` instead).

    if (@available(iOS 11.0, *)) {
        // 首次根据状态应用
        if (UIScreen.mainScreen.isCaptured) { [self installPrivacyShield]; }
        // 监听录屏/镜像状态变化
        [NSNotificationCenter.defaultCenter addObserverForName:UIScreenCapturedDidChangeNotification object:nil queue:NSOperationQueue.mainQueue usingBlock:^(__unused NSNotification * _Nonnull note) {
            if (UIScreen.mainScreen.isCaptured) { [self installPrivacyShield]; }
            else { [self removePrivacyShield]; }
        }];
    }
}


- (void)sceneDidDisconnect:(UIScene *)scene {
    // Called as the scene is being released by the system.
    // This occurs shortly after the scene enters the background, or when its session is discarded.
    // Release any resources associated with this scene that can be re-created the next time the scene connects.
    // The scene may re-connect later, as its session was not necessarily discarded (see `application:didDiscardSceneSessions` instead).
    [NSNotificationCenter.defaultCenter removeObserver:self];
}


- (void)sceneDidBecomeActive:(UIScene *)scene {
    // 已激活：再保险，确保遮罩被移除
    [self removePrivacyShield];
}


- (void)sceneWillResignActive:(UIScene *)scene {
    // 将要失活：先盖遮罩，避免敏感内容进入快照
    [self installPrivacyShield];
}


- (void)sceneWillEnterForeground:(UIScene *)scene {
    // 将回到前台：移除遮罩
    [self removePrivacyShield];
}


- (void)sceneDidEnterBackground:(UIScene *)scene {
    // 已入后台：iOS 会生成 App Switcher 快照；稍作延迟后清理本 App 的沙盒快照
    UIApplication *app = UIApplication.sharedApplication;
    __block UIBackgroundTaskIdentifier bg = UIBackgroundTaskInvalid;
    bg = [app beginBackgroundTaskWithName:@"ClearSnapshots" expirationHandler:^{
        if (bg != UIBackgroundTaskInvalid) { [app endBackgroundTask:bg]; bg = UIBackgroundTaskInvalid; }
    }];

    // 经验延迟 0.3~0.8s；这里取 0.6s
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(0.6 * NSEC_PER_SEC)),
                   dispatch_get_global_queue(QOS_CLASS_BACKGROUND, 0), ^{
        ClearMyAppSnapshots();
        if (bg != UIBackgroundTaskInvalid) { [app endBackgroundTask:bg]; bg = UIBackgroundTaskInvalid; }
    });
}


@end
