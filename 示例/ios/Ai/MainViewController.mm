//
//  MainViewController.m
//  Ai
//
//  Created by a on 2025/8/19.
//

#include <mach-o/dyld.h>
#import "MainViewController.h"
#import <notify.h>
#import <spawn.h>
#include <unistd.h>
#import "SettingsViewController.h"
#ifdef __cplusplus

#endif
#import <sys/wait.h>
#import <string.h>
#import <errno.h>
#import <stdlib.h>
#import <os/log.h>

#import "HUDHelper.h"
#import "touch.h"



extern char **environ;

@interface MainViewController ()

@property (nonatomic, strong) UIButton *mainButton;
@property (nonatomic, strong) UIView *topBar;
@property (nonatomic, strong) UIImageView *logoView;
@property (nonatomic, strong) UILabel *titleLabel;
@property (nonatomic, strong) UIButton *settingsButton;

@end



@implementation MainViewController

- (void)setupTopBar {
    self.topBar = [[UIView alloc] initWithFrame:CGRectZero];
    self.topBar.translatesAutoresizingMaskIntoConstraints = NO;
    self.topBar.backgroundColor = [UIColor systemBackgroundColor];
    self.topBar.layer.shadowColor = [UIColor blackColor].CGColor;
    self.topBar.layer.shadowOpacity = 0.06;
    self.topBar.layer.shadowRadius = 2.0;
    self.topBar.layer.shadowOffset = CGSizeMake(0, 1);
    [self.view addSubview:self.topBar];

    UILayoutGuide *guide = self.view.safeAreaLayoutGuide;
    [NSLayoutConstraint activateConstraints:@[
        [self.topBar.topAnchor constraintEqualToAnchor:guide.topAnchor],
        [self.topBar.leadingAnchor constraintEqualToAnchor:self.view.leadingAnchor],
        [self.topBar.trailingAnchor constraintEqualToAnchor:self.view.trailingAnchor],
        [self.topBar.heightAnchor constraintEqualToConstant:44.0]
    ]];

    self.logoView = [[UIImageView alloc] initWithImage:[UIImage systemImageNamed:@"swift"]];
    self.logoView.translatesAutoresizingMaskIntoConstraints = NO;
    self.logoView.tintColor = [UIColor systemOrangeColor];
    [self.topBar addSubview:self.logoView];

    self.titleLabel = [[UILabel alloc] initWithFrame:CGRectZero];
    self.titleLabel.translatesAutoresizingMaskIntoConstraints = NO;
    self.titleLabel.text = @"AI·暗区突围v6.6";//版本号
    self.titleLabel.font = [UIFont systemFontOfSize:17 weight:UIFontWeightSemibold];
    self.titleLabel.textColor = [UIColor labelColor];
    [self.topBar addSubview:self.titleLabel];

    self.settingsButton = [UIButton buttonWithType:UIButtonTypeSystem];
    self.settingsButton.translatesAutoresizingMaskIntoConstraints = NO;
    [self.settingsButton setTitle:@"功能设置" forState:UIControlStateNormal];
    self.settingsButton.titleLabel.font = [UIFont systemFontOfSize:16 weight:UIFontWeightRegular];
    [self.settingsButton addTarget:self action:@selector(openSettings) forControlEvents:UIControlEventTouchUpInside];
    [self.topBar addSubview:self.settingsButton];

    // Constraints
    [NSLayoutConstraint activateConstraints:@[
        [self.logoView.leadingAnchor constraintEqualToAnchor:self.topBar.leadingAnchor constant:16.0],
        [self.logoView.centerYAnchor constraintEqualToAnchor:self.topBar.centerYAnchor],
        [self.logoView.widthAnchor constraintEqualToConstant:20.0],
        [self.logoView.heightAnchor constraintEqualToConstant:20.0],

        [self.titleLabel.leadingAnchor constraintEqualToAnchor:self.logoView.trailingAnchor constant:8.0],
        [self.titleLabel.centerYAnchor constraintEqualToAnchor:self.topBar.centerYAnchor],

        [self.settingsButton.trailingAnchor constraintEqualToAnchor:self.topBar.trailingAnchor constant:-16.0],
        [self.settingsButton.centerYAnchor constraintEqualToAnchor:self.topBar.centerYAnchor],

        [self.titleLabel.trailingAnchor constraintLessThanOrEqualToAnchor:self.settingsButton.leadingAnchor constant:-12.0]
    ]];

    // Fallback if symbol is nil: draw a small orange circle
 
}


- (void)openSettings {
   
    
    SettingsViewController *vc = [[SettingsViewController alloc] init];
       [self.navigationController pushViewController:vc animated:YES];
    
  
}

- (void)viewWillAppear:(BOOL)animated {
    [super viewWillAppear:animated];
    [self.navigationController setNavigationBarHidden:YES animated:animated];
}


- (void)viewDidLoad {
    [super viewDidLoad];
    self.view.backgroundColor = [UIColor systemBackgroundColor];
    [self setupTopBar];

    self.mainButton = [UIButton buttonWithType:UIButtonTypeSystem];
    self.mainButton.translatesAutoresizingMaskIntoConstraints = NO;
    self.mainButton.titleLabel.font = [UIFont systemFontOfSize:20 weight:UIFontWeightSemibold];
    [self.mainButton addTarget:self action:@selector(tmButton:) forControlEvents:UIControlEventTouchUpInside];

    [self.view addSubview:self.mainButton];
    [NSLayoutConstraint activateConstraints:@[
        [self.mainButton.centerXAnchor constraintEqualToAnchor:self.view.centerXAnchor],
        [self.mainButton.centerYAnchor constraintEqualToAnchor:self.view.centerYAnchor]
    ]];

    [self reloadMainButtonState];
    
    
}

- (BOOL)isHUDEnabled {
//    extern BOOL IsHUDEnabled(void);
    return IsHUDEnabled();
}

- (void)setHUDEnabled:(BOOL)enabled {
 
    SetHUDEnabled(enabled);
}

- (void)reloadMainButtonState {
    BOOL isActive = [self isHUDEnabled];
    [UIView transitionWithView:self.mainButton duration:0.25 options:UIViewAnimationOptionTransitionCrossDissolve animations:^{
        [self.mainButton setTitle:(isActive ? @"关闭绘制" : @"启动绘制") forState:UIControlStateNormal];
    } completion:nil];
}

- (void)tmButton:(UIButton *)sender {
    
    CGPoint portraitPoint = CGPointMake(180, 102); // 目标位置
//
////        创建内部的串行队列，仅用于本函数内
       dispatch_queue_t _eventQueue = dispatch_queue_create("com.touch.sim.queue", DISPATCH_QUEUE_SERIAL);
       
       dispatch_async(_eventQueue, ^{
           [[touch shared] touchDownAtPoint:portraitPoint withIndex:0];
           
           CGPoint portraitPoint1 = CGPointMake(180, 104); // 目标位置
           [[touch shared] moveTouchToPoint:portraitPoint1 withIndex:0];
           [[touch shared] liftUpAtPoint:portraitPoint1 withIndex:0];
           
       });
    
  

//    dispatch_async(_eventQueue, ^{
//        CGPoint portraitPoint = CGPointMake(180, 102);
//        int total = 1000;
//        double interval = 1.0 / 5.0;  // 每 0.2 秒一次
//
//        for (int i = 0; i < total; i++) {
//            // 执行 touchDown（你也可以在这之后加 liftUp、move 等）
//            [[touch shared] touchDownAtPoint:portraitPoint withIndex:0];
//
//            // 延迟 interval 秒再继续下一个
//            [NSThread sleepForTimeInterval:interval];
//        }
//    });
    
//    LogAvailableHIDServices(YES); // 先尝试 Admin，不行自动降级
   
//    [[touch shared] logAvailableHIDServices];
  
    
//    uint64_t rid = [[touch shared] preferredTouchscreenRegistryID];
//    NSLog(@"✅ 找到触摸屏 registryID: 0x%llx", rid);
    
    
    BOOL isNowEnabled = [self isHUDEnabled];
    if (isNowEnabled) {
        notify_post(NOTIFY_UNCLOSE); // 通知关闭 kfd
    }

    [self setHUDEnabled:!isNowEnabled];
    isNowEnabled = !isNowEnabled;

    if (isNowEnabled) {
        dispatch_semaphore_t semaphore = dispatch_semaphore_create(0);
        int token;
        notify_register_dispatch(NOTIFY_LAUNCHED_HUD, &token, dispatch_get_main_queue(), ^(int token) {
            notify_cancel(token);
            dispatch_semaphore_signal(semaphore);
        });

        self.view.userInteractionEnabled = NO;
        dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(0.5 * NSEC_PER_SEC)), dispatch_get_global_queue(QOS_CLASS_UTILITY, 0), ^{
            intptr_t timeout = dispatch_semaphore_wait(semaphore, dispatch_time(DISPATCH_TIME_NOW, (int64_t)(5.0 * NSEC_PER_SEC)));
            dispatch_async(dispatch_get_main_queue(), ^{
                if (timeout) {
                    os_log_error(OS_LOG_DEFAULT, "Timed out waiting for HUD to launch");
                }
                [self reloadMainButtonState];
                self.view.userInteractionEnabled = YES;
            });
        });
    } else {
        self.view.userInteractionEnabled = NO;
        dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(0.5 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
            [self reloadMainButtonState];
            self.view.userInteractionEnabled = YES;
        });
    }
  
}

@end
