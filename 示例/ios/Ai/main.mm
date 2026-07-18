#import <notify.h>
#import <mach-o/dyld.h>
#import <sys/utsname.h>
#import <objc/runtime.h>
#import <objc/message.h>

#import "IOKit+SPI.h"
#import "BackboardServices.h"
#import "UIApplication+Private.h"
#import "HUDHelper.h"

#define PID_PATH "/var/mobile/Library/Caches/com.mtx.anqu.txt"

int main(int argc, char *argv[])
{
    @autoreleasepool
    {
        NSLog(@"[main] argc=%d", argc);
        for (int i = 0; i < argc; i++) {
//            NSLog(@"[main] argv[%d]=%s", i, argv[i]);
        }

        if (argc <= 1) {
//              NSLog(@"[main] no extra args -> launching normal app with AppDelegate");
              return UIApplicationMain(argc, argv, nil, @"AppDelegate");
//            return UIApplicationMain(argc, argv, nil, @"HUDMainApplicationDelegate");
        }

        if (strcmp(argv[1], "-hud") == 0)
        {
//            NSLog(@"[main] entering -hud branch (minimal bootstrap)");
            pid_t pid = getpid();
            NSString *pidString = [NSString stringWithFormat:@"%d", pid];
            [pidString writeToFile:@PID_PATH atomically:YES encoding:NSUTF8StringEncoding error:nil];
            
//            NSLog(@"[main] -hud: launching via UIApplicationMain with HUDMainApplicationDelegate");



            [UIScreen initialize];
            CFRunLoopGetCurrent();

            GSInitialize();
            BKSDisplayServicesStart();
            UIApplicationInitialize();

            UIApplicationInstantiateSingleton(objc_getClass("HUDMainApplication"));
            static id<UIApplicationDelegate> appDelegate = [[objc_getClass("HUDMainApplicationDelegate") alloc] init];
            [UIApplication.sharedApplication setDelegate:appDelegate];
            [UIApplication.sharedApplication _accessibilityInit];

            [NSRunLoop currentRunLoop];
         

            if (@available(iOS 15.0, *)) {
                GSEventInitialize(0);
                GSEventPushRunLoopMode(kCFRunLoopDefaultMode);
            }

            [UIApplication.sharedApplication __completeAndRunAsPlugin];

            static int _springboardBootToken;
            notify_register_dispatch("SBSpringBoardDidLaunchNotification", &_springboardBootToken, dispatch_get_main_queue(), ^(int token) {
                notify_cancel(token);

                notify_post(NOTIFY_DISMISSAL_HUD);

                // Re-enable HUD after SpringBoard is launched.
                SetHUDEnabled(YES);

                // Exit the current instance of HUD.
                kill(pid, SIGKILL);
            });

            CFRunLoopRun();
            return EXIT_SUCCESS;
        }
        else if (strcmp(argv[1], "-exit") == 0)
        {
//            NSLog(@"[main] entering -exit branch");
            NSString *pidString = [NSString stringWithContentsOfFile:@PID_PATH encoding:NSUTF8StringEncoding error:nil];
            if (pidString) {
                pid_t pid = (pid_t)[pidString intValue];
                kill(pid, SIGKILL);
                unlink([@PID_PATH UTF8String]);
            }
            return 0;
        }
        else if (strcmp(argv[1], "-check") == 0)
        {
//            NSLog(@"[main] entering -check branch");
            NSString *pidString = [NSString stringWithContentsOfFile:@PID_PATH encoding:NSUTF8StringEncoding error:nil];
            if (pidString) {
                pid_t pid = (pid_t)[pidString intValue];
                int alive = kill(pid, 0);
                return (alive == 0 ? 1 : 0);
            }
            return 0;
        }

//        return UIApplicationMain(argc, argv, @"MainApplication", @"MainApplicationDelegate");
//        NSLog(@"[main] fallthrough -> launching normal app with AppDelegate");
        return UIApplicationMain(argc, argv, nil, @"AppDelegate");
    }
}
