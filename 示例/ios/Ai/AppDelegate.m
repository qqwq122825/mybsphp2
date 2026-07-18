// AppDelegate.m
#import "AppDelegate.h"
#import "ViewController.h"   // 你的自定义首页 VC

@implementation AppDelegate

- (BOOL)application:(UIApplication *)application
        didFinishLaunchingWithOptions:(NSDictionary *)launchOptions {

    self.window = [[UIWindow alloc] initWithFrame:[UIScreen mainScreen].bounds];

    // 如果你需要导航栈，用 UINavigationController 包一下
    UIViewController *rootVC = [ViewController new];
    // UINavigationController *nav = [[UINavigationController alloc] initWithRootViewController:rootVC];
    self.window.rootViewController = rootVC; // 或 nav

    [self.window makeKeyAndVisible];
    return YES;
}

@end
