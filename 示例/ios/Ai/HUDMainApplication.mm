////
////  HUDMainApplication.m
////  Ai
////
////  Created by a on 2025/8/19.
////
//
//#import "HUDMainApplication.h"
//#import <UIKit/UIKit.h>
//#import <notify.h>
//#import <objc/runtime.h>
//#import <objc/message.h>
//
//// 与 main.m 保持一致的退出通知（用于淡出并终止 HUD）
//static const char *kHUDExitNotifyKey = "com.ai.hud.exit";
//
//@implementation HUDMainApplication {
//    int _dismissToken;
//}
//
//- (instancetype)init {
//    if (self = [super init]) {
////        log_debug(OS_LOG_DEFAULT, "- [HUDMainApplication init]");
//
//        {
//            int outToken;
//            notify_register_dispatch("com.ai.hud.exit", &outToken, dispatch_get_main_queue(), ^(int token) {
//                notify_cancel(token);
//
//                [UIView animateWithDuration:0.2 animations:^{
//#pragma clang diagnostic push
//#pragma clang diagnostic ignored "-Wdeprecated-declarations"
//                    [[self.windows firstObject] setAlpha:0.0];
//#pragma clang diagnostic pop
//                } completion:^(BOOL finished) {
//                    SEL selTerminate = NSSelectorFromString(@"terminateWithSuccess");
//                    if ([self respondsToSelector:selTerminate]) {
//                        ((void(*)(id,SEL))objc_msgSend)(self, selTerminate);
//                    } else {
//                        exit(0);
//                    }
//                }];
//            });
//        }
//
//        do {
//            id dispatcher = [self valueForKey:@"eventDispatcher"];
//            if (!dispatcher) break;
//
//            SEL selInstall = NSSelectorFromString(@"_installEventRunLoopSources:");
//            if ([dispatcher respondsToSelector:selInstall]) {
//                CFRunLoopRef mainLoop = CFRunLoopGetMain();
//                ((void(*)(id,SEL,CFRunLoopRef))objc_msgSend)(dispatcher, selInstall, mainLoop);
//            }
//
//            Class UIEventFetcherCls = objc_getClass("UIEventFetcher");
//            if (UIEventFetcherCls) {
//                id fetcher = [[UIEventFetcherCls alloc] init];
//                SEL selSetSink = NSSelectorFromString(@"setEventFetcherSink:");
//                if ([fetcher respondsToSelector:selSetSink]) {
//                    ((void(*)(id,SEL,id))objc_msgSend)(fetcher, selSetSink, dispatcher);
//                } else {
//                    @try { [fetcher setValue:dispatcher forKey:@"eventFetcherSink"]; } @catch (__unused id e) {}
//                }
//                @try { [self setValue:fetcher forKey:@"eventFetcher"]; } @catch (__unused id e) {}
//            }
//        } while (false);
//    }
//    return self;
//}
//
//- (void)dealloc {
//    if (_dismissToken) {
//        notify_cancel(_dismissToken);
//        _dismissToken = 0;
//    }
//}
//
//@end


//
//  HUDMainApplication.mm
//  TrollSpeed
//
//  Created by Lessica on 2024/1/24.
//

#import <notify.h>
#import <pthread.h>
#import <mach/mach.h>
#import <mach-o/dyld.h>
#import <objc/runtime.h>
#import <objc/message.h>

#import "pac_helper.h"
#import "UIEventFetcher.h"
#import "UIEventDispatcher.h"
#import "HUDMainApplication.h"
#import "UIApplication+Private.h"

@implementation HUDMainApplication

- (instancetype)init
{
    if (self = [super init])
    {
//        log_debug(OS_LOG_DEFAULT, "- [HUDMainApplication init]");

        {
            int outToken;
            notify_register_dispatch(NOTIFY_DISMISSAL_HUD, &outToken, dispatch_get_main_queue(), ^(int token) {
                notify_cancel(token);
                
                // Fade out the HUD window
                [UIView animateWithDuration:FADE_OUT_DURATION animations:^{
#pragma clang diagnostic push
#pragma clang diagnostic ignored "-Wdeprecated-declarations"
                    [[self.windows firstObject] setAlpha:0.0];
#pragma clang diagnostic pop
                } completion:^(BOOL finished) {
                    // Terminate the HUD app
                    [self terminateWithSuccess];
                }];
            });
        }

        do {
            UIEventDispatcher *dispatcher = (UIEventDispatcher *)[self valueForKey:@"eventDispatcher"];
            if (!dispatcher)
            {
                break;
            }

            if ([dispatcher respondsToSelector:@selector(_installEventRunLoopSources:)])
            {
                CFRunLoopRef mainRunLoop = CFRunLoopGetMain();
                [dispatcher _installEventRunLoopSources:mainRunLoop];
            }
            else
            {
                IMP runMethodIMP = class_getMethodImplementation([self class], @selector(_run));
                if (!runMethodIMP)
                {
                    break;
                }

                uint32_t *runMethodPtr = (uint32_t *)make_sym_readable((void *)runMethodIMP);

                void (*orig_UIEventDispatcher__installEventRunLoopSources_)(id _Nonnull, SEL _Nonnull, CFRunLoopRef) = NULL;
                for (int i = 0; i < 0x140; i++)
                {
                    // mov x2, x0
                    // mov x0, x?
                    if (runMethodPtr[i] != 0xaa0003e2 || (runMethodPtr[i + 1] & 0xff000000) != 0xaa000000)
                        continue;
                    
                    // bl -[UIEventDispatcher _installEventRunLoopSources:]
                    uint32_t blInst = runMethodPtr[i + 2];
                    uint32_t *blInstPtr = &runMethodPtr[i + 2];
                    if ((blInst & 0xfc000000) != 0x94000000)
                    {

                        continue;
                    }


                    int32_t blOffset = blInst & 0x03ffffff;
                    if (blOffset & 0x02000000)
                        blOffset |= 0xfc000000;
                    blOffset <<= 2;

                    uint64_t blAddr = (uint64_t)blInstPtr + blOffset;
                    uint32_t cbzInst = *((uint32_t *)make_sym_readable((void *)blAddr));
                    if ((cbzInst & 0xff000000) != 0xb4000000)
                    {
                        continue;
                    }

                    
                    orig_UIEventDispatcher__installEventRunLoopSources_ = (void (*)(id  _Nonnull __strong, SEL _Nonnull, CFRunLoopRef))make_sym_callable((void *)blAddr);
                }

                if (!orig_UIEventDispatcher__installEventRunLoopSources_)
                {

                    break;
                }


                CFRunLoopRef mainRunLoop = CFRunLoopGetMain();
                orig_UIEventDispatcher__installEventRunLoopSources_(dispatcher, @selector(_installEventRunLoopSources:), mainRunLoop);
                
                
//                SEL selInstall = NSSelectorFromString(@"_installEventRunLoopSources:");
//               if ([dispatcher respondsToSelector:selInstall]) {
//                   CFRunLoopRef mainLoop = CFRunLoopGetMain();
//                   ((void(*)(id,SEL,CFRunLoopRef))objc_msgSend)(dispatcher, selInstall, mainLoop);
//               }
            }

            UIEventFetcher *fetcher = [[objc_getClass("UIEventFetcher") alloc] init];
            [dispatcher setValue:fetcher forKey:@"eventFetcher"];

            if ([fetcher respondsToSelector:@selector(setEventFetcherSink:)]) {
                [fetcher setEventFetcherSink:dispatcher];
            }
            else
            {
                /* Tested on iOS 15.1.1 and below */
                [fetcher setValue:dispatcher forKey:@"eventFetcherSink"];
            }

            [self setValue:fetcher forKey:@"eventFetcher"];
        } while (NO);
    }
    return self;
}

@end
