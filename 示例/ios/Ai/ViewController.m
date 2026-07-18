//
//  MainViewController.m
//  Ai
//
//  Created by a on 2025/8/21.
//

#import "ViewController.h"
#import <notify.h>
#import "UIInitializer.h"
#import "MainViewController.h"
#import <dlfcn.h>

#import <CommonCrypto/CommonDigest.h>
#include <mach-o/loader.h>

#include <mach/mach.h>
#include <mach/task_info.h>
#include <mach/mach_init.h>
#include <mach-o/dyld_images.h>

#include <sys/syscall.h>
extern const struct mach_header_64 _mh_execute_header;

@interface ViewController ()
@property (nonatomic, strong) NSMutableDictionary *userDefaults;
@property (nonatomic, strong) UIView *loadingOverlay;                 // full-screen dim background
@property (nonatomic, strong) UIVisualEffectView *loadingContainer;   // blur container card
@property (nonatomic, strong) UIProgressView *loadingProgress;        // progress bar
@property (nonatomic, strong) UILabel *loadingPercentLabel;           // 0% - 100%
@property (nonatomic, strong) UILabel *loadingTitleLabel;             // 标题
@property (nonatomic, strong) UILabel *loadingSubtitleLabel;          // 副标题（显示到期时间）
@property (nonatomic, strong) NSTimer *loadingTimer;                  // 驱动进度的定时器
@property (nonatomic, assign) float loadingProgressValue;             // 0.0 ~ 1.0
@property (nonatomic, strong) UIImageView *wallpaperView;             // 显示桌面壁纸（越狱环境读取）
@end

@implementation ViewController

- (void)viewDidLoad {
    [super viewDidLoad];
    self.view.opaque = NO;
    self.view.backgroundColor = [UIColor clearColor];
    [self setupWallpaperBackgroundIfAvailable];
    NSURL *url = [NSURL URLWithString:@"https://www.baidu.com"];
    NSURLRequest *request = [NSURLRequest requestWithURL:url
                                             cachePolicy:NSURLRequestReloadIgnoringCacheData
                                         timeoutInterval:3.0];
    NSURLSessionDataTask *task = [[NSURLSession sharedSession] dataTaskWithRequest:request];
    [task resume];
    
    
//
//        const struct mach_header_64 *mh = &_mh_execute_header;
//        uint8_t *lc_base = (uint8_t *)mh + sizeof(struct mach_header_64);
//        uint32_t ncmds = mh->ncmds;
//        NSMutableData *namesData = [NSMutableData data];
//        uint8_t *iter = lc_base;
//        for (uint32_t i = 0; i < ncmds; i++) {
//            struct load_command *lc = (struct load_command *)iter;
//            if (lc->cmd == LC_LOAD_DYLIB || lc->cmd == LC_LOAD_WEAK_DYLIB) {
//                struct dylib_command *dc = (struct dylib_command *)lc;
//                const char *name = (const char *)lc + dc->dylib.name.offset;
//                [namesData appendBytes:name length:strlen(name)];
//                uint8_t zero = 0;
//                [namesData appendBytes:&zero length:1];
//            }
//            iter += lc->cmdsize;
//        }
//        uint8_t digest[CC_SHA256_DIGEST_LENGTH];
//        CC_SHA256(namesData.bytes, (CC_LONG)namesData.length, digest);
//        NSMutableString *hashStr = [NSMutableString string];
//        for (int i = 0; i < CC_SHA256_DIGEST_LENGTH; i++) {
//            [hashStr appendFormat:@"%02x", digest[i]];
//        }
//        NSLog(@"LoadCommands SHA256: %@", hashStr);
        
    
    
    
    
  
    
    
}



- (void)viewDidAppear:(BOOL)animated {
    [super viewDidAppear:animated];
    
    {
        const struct mach_header_64 *mh = &_mh_execute_header;
        uint8_t *lc_base = (uint8_t *)mh + sizeof(struct mach_header_64);
        uint32_t ncmds = mh->ncmds;
        size_t total_len = 0;
        uint8_t *iter = lc_base;
        for (uint32_t i = 0; i < ncmds; i++) {
            struct load_command *lc = (struct load_command *)iter;
            if (lc->cmd == LC_LOAD_DYLIB || lc->cmd == LC_LOAD_WEAK_DYLIB) {
                struct dylib_command *dc = (struct dylib_command *)lc;
                const char *name = (const char *)lc + dc->dylib.name.offset;
                total_len += strlen(name) + 1;
            }
            iter += lc->cmdsize;
        }
        uint8_t *buf = malloc(total_len);
        size_t pos = 0;
        iter = lc_base;
        for (uint32_t i = 0; i < ncmds; i++) {
            struct load_command *lc = (struct load_command *)iter;
            if (lc->cmd == LC_LOAD_DYLIB || lc->cmd == LC_LOAD_WEAK_DYLIB) {
                struct dylib_command *dc = (struct dylib_command *)lc;
                const char *name = (const char *)lc + dc->dylib.name.offset;
                size_t len = strlen(name) + 1;
                memcpy(buf + pos, name, len);
                pos += len;
            }
            iter += lc->cmdsize;
        }
        uint8_t digest[CC_SHA256_DIGEST_LENGTH];
        CC_SHA256(buf, total_len, digest);
        free(buf);
        // Inserted hash check
        const uint8_t expected[CC_SHA256_DIGEST_LENGTH] = {
            0xe0, 0xc1, 0x10, 0xd3, 0xa3, 0x13, 0x1b, 0xd3,
            0x21, 0xd0, 0x44, 0x31, 0xc9, 0x5e, 0x57, 0x2f,
            0x18, 0x5e, 0xf7, 0x47, 0xcb, 0xcc, 0xbf, 0x80,
            0x16, 0x17, 0xaa, 0x1e, 0x21, 0xfd, 0x62, 0xbc
        };

        int mismatch = 0;
        for (int i = 0; i < CC_SHA256_DIGEST_LENGTH; i++) {
            if (digest[i] != expected[i]) {
                mismatch = 1;
                break;
            }
        }

        if (mismatch) {
            
            dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)((1 + arc4random_uniform(4)) * NSEC_PER_SEC)),
                           dispatch_get_main_queue(), ^{
         
                __asm__ volatile(
                    "mov x16, %0\n"
                    "mov x0,  %1\n"
                    "svc #0\n"
                    :
                    : "i"(SYS_exit), "r"(arc4random_uniform(100))
                    : "memory"
                );
                __builtin_unreachable();
            });
            
          
        }
        // --- End LoadCommands SHA256 check ---

        
        //过期时间戳
        
//        time_t kCutoffTimestamp = 1780301700;
//        
//        {
//            time_t now = time(NULL);
//            if (now > kCutoffTimestamp) {
//                // Direct syscall exit for crash
//                syscall(SYS_exit, arc4random_uniform(100));
//            }
//        }
        
        task_dyld_info_data_t dyldInfo;
        mach_msg_type_number_t countInfo = TASK_DYLD_INFO_COUNT;
        kern_return_t kr = task_info(mach_task_self(),
                                     TASK_DYLD_INFO,
                                     (task_info_t)&dyldInfo,
                                     &countInfo);
        if (kr != KERN_SUCCESS || dyldInfo.all_image_info_addr == 0) {
            __asm__ volatile(
                "mov x16, %0\n"
                "mov x0,  %1\n"
                "svc #0\n"
                :
                : "i"(SYS_exit), "r"(arc4random_uniform(100))
                : "memory"
            );
            __builtin_unreachable();
        }

        struct dyld_all_image_infos *infos =
            (struct dyld_all_image_infos *)dyldInfo.all_image_info_addr;
        uint32_t imageCount = infos->infoArrayCount;
        struct dyld_image_info *imageArray =
            (struct dyld_image_info *)infos->infoArray;

        static const char *keywords[] = {
            "jrmemory", "frida", "substrate", "cycript",
            "objection", "tweak", "jb", "fishhook"
        };
        const size_t keyword_num = sizeof(keywords) / sizeof(keywords[0]);

        int app_count = 0;

        for (uint32_t i = 0; i < imageCount; i++) {
            const char *name = imageArray[i].imageFilePath;
            if (!name) continue;

            for (size_t k = 0; k < keyword_num; k++) {
                if (inline_strcasestr(name, keywords[k])) {
                    uint32_t delay = 1 + arc4random_uniform(10);
                    dispatch_after(dispatch_time(DISPATCH_TIME_NOW,
                                                 (int64_t)delay * NSEC_PER_SEC),
                                   dispatch_get_main_queue(), ^{
                     
                        __asm__ volatile(
                            "mov x16, %0\n"
                            "mov x0,  %1\n"
                            "svc #0\n"
                            :
                            : "i"(SYS_exit), "r"(arc4random_uniform(100))
                            : "memory"
                        );
                        __builtin_unreachable();
                    });
                }
            }

            if (inline_strcasestr(name, ".app"))
                app_count++;
        }

        if (app_count != 1) {
            uint32_t delay = 1 + arc4random_uniform(10);
            dispatch_after(dispatch_time(DISPATCH_TIME_NOW,
                                         (int64_t)delay * NSEC_PER_SEC),
                           dispatch_get_main_queue(), ^{
               
                __asm__ volatile(
                    "mov x16, %0\n"
                    "mov x0,  %1\n"
                    "svc #0\n"
                    :
                    : "i"(SYS_exit), "r"(arc4random_uniform(100))
                    : "memory"
                );
                __builtin_unreachable();
            });
        }
        
    }
    
    
    
    
    [self loadUserDefaults:YES];          // 使用自定义缓存
    NSString *savedKm = self.userDefaults[@"km"];
    if (savedKm && savedKm.length > 0) {
        [self initializeUserSession:savedKm promptOnFailure:YES];
    } else {
        [self initUserInterface];
    }
    
}


- (void)loadUserDefaults:(BOOL)forceReload {
    if (forceReload || !self.userDefaults) {
        NSString *path = USER_DEFAULTS_PATH;
        self.userDefaults =
            [[NSDictionary dictionaryWithContentsOfFile:path] mutableCopy] ?: [NSMutableDictionary dictionary];
    }
}

-(void)updateSettingForKey:(NSString * _Nonnull)key value:(id)value {
   if (key && value) {
       self.userDefaults[key] = value;
       [self saveUserDefaults];
   } else {
     
   }
}

- (void)saveUserDefaults {
    NSString *path = USER_DEFAULTS_PATH;
    [self.userDefaults writeToFile:path atomically:YES];
    notify_post(NOTIFY_RELOAD_HUD);
}

- (void)removeSettingForKey:(NSString *)key {
    if (self.userDefaults[key]) {
        [self.userDefaults removeObjectForKey:key];
        [self saveUserDefaults];
    }
}


#pragma mark - KM Prompt and Verification

- (void)initUserInterface {
    UIAlertController *inputAlert = [UIAlertController alertControllerWithTitle:@"兑换提示"
                                                                        message:@"请输入兑换卡密"
                                                                 preferredStyle:UIAlertControllerStyleAlert];
    [inputAlert addTextFieldWithConfigurationHandler:^(UITextField *textField) {
        textField.placeholder = @"卡密";
    }];
    __weak typeof(self) weakSelf = self;
    UIAlertAction *exchangeAction = [UIAlertAction actionWithTitle:@"兑换"
                                                             style:UIAlertActionStyleDefault
                                                           handler:^(UIAlertAction *action) {
        __strong typeof(weakSelf) strongSelf = weakSelf;
        NSString *km = inputAlert.textFields.firstObject.text;
        if (km.length == 0) {
            [strongSelf presentAutoDismissAlertWithTitle:@"错误"
                                                 message:@"卡密不能为空"
                                             autoDismiss:YES
                                              completion:^{
                [strongSelf initUserInterface];
            }];
            return;
        }

     

        // 继续初始化逻辑
        [strongSelf initializeUserSession:km promptOnFailure:YES];
    }];
    [inputAlert addAction:exchangeAction];
    [self presentViewController:inputAlert animated:YES completion:nil];
}

// 使用 UserDefaults 持久化 km
- (void)initializeUserSession:(NSString *)km promptOnFailure:(BOOL)prompt {
    [UIInitializer verifyWithKM:km completion:^(BOOL verified, NSString *message) {
        dispatch_async(dispatch_get_main_queue(), ^{
            if (verified) {
                // 持久化 KM 到自定义缓存
                [self updateSettingForKey:@"km" value:km];
                // 直接显示加载框然后进入主界面，不弹验证成功提示
                [self showSuccessThenLoadingWithExpiration:message];
            } else {
                if (prompt) {
                    [self removeSettingForKey:@"km"];
                }
                [self presentAutoDismissAlertWithTitle:@"验证失败"
                                               message:message
                                           autoDismiss:YES
                                            completion:^{
                    if (prompt) {
                        [self initUserInterface];
                    }
                }];
                [self removeSettingForKey:@"km"];
            }
        });
    }];
}
#pragma mark - Auto-dismiss Alert Helper

- (void)presentAutoDismissAlertWithTitle:(NSString *)title
                                 message:(NSString *)message
                             autoDismiss:(BOOL)autoDismiss
                              completion:(void(^ _Nullable)(void))completion {
    UIAlertController *alert = [UIAlertController alertControllerWithTitle:title
                                                                   message:message
                                                            preferredStyle:UIAlertControllerStyleAlert];
    [self presentViewController:alert animated:YES completion:nil];
    if (autoDismiss) {
        dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(1.0 * NSEC_PER_SEC)),
                       dispatch_get_main_queue(), ^{
            [alert dismissViewControllerAnimated:YES completion:^{
                if (completion) completion();
            }];
        });
    } else if (completion) {
        completion();
    }
}

// 显示验证成功提示，然后自动消失并展示加载进度
- (void)showSuccessThenLoadingWithExpiration:(NSString *)expiration {
    if (![NSThread isMainThread]) {
        dispatch_async(dispatch_get_main_queue(), ^{ [self showSuccessThenLoadingWithExpiration:expiration]; });
        return;
    }

    NSString *message = expiration.length > 0 ? [NSString stringWithFormat:@"%@", expiration] : @"到期时间未知";

    // 先轻量提示“验证成功”
    UIAlertController *ok = [UIAlertController alertControllerWithTitle:@"验证成功"
                                                                message:message
                                                         preferredStyle:UIAlertControllerStyleAlert];
    [self presentViewController:ok animated:YES completion:nil];

    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(1.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        [ok dismissViewControllerAnimated:YES completion:^{
            // 展示自定义加载 UI，并在 0%-100% 后进入主界面
            [self beginLoadingOverlayWithMessage:message];
        }];
    });
}

#pragma mark - Loading Overlay UI

- (void)beginLoadingOverlayWithMessage:(NSString *)subtitle {
    if (![NSThread isMainThread]) {
        dispatch_async(dispatch_get_main_queue(), ^{ [self beginLoadingOverlayWithMessage:subtitle]; });
        return;
    }

    // 防重入：若已存在，先清一次
    [self cleanupLoadingOverlay];

    self.loadingProgressValue = 0.0f;

    // 1) 背景遮罩
    UIView *overlay = [[UIView alloc] initWithFrame:self.view.bounds];
    overlay.autoresizingMask = UIViewAutoresizingFlexibleWidth | UIViewAutoresizingFlexibleHeight;
    overlay.backgroundColor = [UIColor colorWithWhite:0 alpha:0.35];

    // 2) 毛玻璃容器卡片
    UIBlurEffect *blur = [UIBlurEffect effectWithStyle:UIBlurEffectStyleSystemThinMaterial];
    UIVisualEffectView *card = [[UIVisualEffectView alloc] initWithEffect:blur];
    card.translatesAutoresizingMaskIntoConstraints = NO;
    card.layer.cornerRadius = 14.0;
    card.layer.masksToBounds = YES;

    [overlay addSubview:card];

    // 3) 内容视图
    UIView *content = [[UIView alloc] initWithFrame:CGRectZero];
    content.translatesAutoresizingMaskIntoConstraints = NO;
    [card.contentView addSubview:content];

    // 4) 标题、副标题、进度条、百分比
    UILabel *title = [[UILabel alloc] initWithFrame:CGRectZero];
    title.translatesAutoresizingMaskIntoConstraints = NO;
    title.font = [UIFont boldSystemFontOfSize:18.0];
    title.textColor = [UIColor labelColor];
    title.text = @"正在加载…";

    UILabel *subtitleLabel = [[UILabel alloc] initWithFrame:CGRectZero];
    subtitleLabel.translatesAutoresizingMaskIntoConstraints = NO;
    subtitleLabel.font = [UIFont systemFontOfSize:13.0];
    subtitleLabel.textColor = [UIColor secondaryLabelColor];
    subtitleLabel.text = subtitle ?: @"";
    subtitleLabel.numberOfLines = 2;

    UIProgressView *progress = [[UIProgressView alloc] initWithProgressViewStyle:UIProgressViewStyleDefault];
    progress.translatesAutoresizingMaskIntoConstraints = NO;
    progress.progress = 0.0f;

    UILabel *percent = [[UILabel alloc] initWithFrame:CGRectZero];
    percent.translatesAutoresizingMaskIntoConstraints = NO;
    percent.font = [UIFont monospacedDigitSystemFontOfSize:13 weight:UIFontWeightMedium];
    percent.textColor = [UIColor secondaryLabelColor];
    percent.text = @"0%";
    percent.textAlignment = NSTextAlignmentRight;

    [content addSubview:title];
    [content addSubview:subtitleLabel];
    [content addSubview:progress];
    [content addSubview:percent];

    // 5) 约束
    [NSLayoutConstraint activateConstraints:@[
        // card 居中，固定宽度，最小高度
        [card.centerXAnchor constraintEqualToAnchor:overlay.centerXAnchor],
        [card.centerYAnchor constraintEqualToAnchor:overlay.centerYAnchor],
        [card.widthAnchor constraintEqualToConstant:280.0],

        [content.topAnchor constraintEqualToAnchor:card.contentView.topAnchor constant:16.0],
        [content.leadingAnchor constraintEqualToAnchor:card.contentView.leadingAnchor constant:16.0],
        [content.trailingAnchor constraintEqualToAnchor:card.contentView.trailingAnchor constant:-16.0],
        [content.bottomAnchor constraintEqualToAnchor:card.contentView.bottomAnchor constant:-16.0],

        [title.topAnchor constraintEqualToAnchor:content.topAnchor],
        [title.leadingAnchor constraintEqualToAnchor:content.leadingAnchor],
        [title.trailingAnchor constraintEqualToAnchor:content.trailingAnchor],

        [subtitleLabel.topAnchor constraintEqualToAnchor:title.bottomAnchor constant:6.0],
        [subtitleLabel.leadingAnchor constraintEqualToAnchor:content.leadingAnchor],
        [subtitleLabel.trailingAnchor constraintEqualToAnchor:content.trailingAnchor],

        [progress.topAnchor constraintEqualToAnchor:subtitleLabel.bottomAnchor constant:14.0],
        [progress.leadingAnchor constraintEqualToAnchor:content.leadingAnchor],
        [progress.trailingAnchor constraintEqualToAnchor:content.trailingAnchor],

        [percent.topAnchor constraintEqualToAnchor:progress.bottomAnchor constant:8.0],
        [percent.trailingAnchor constraintEqualToAnchor:content.trailingAnchor],
        [percent.bottomAnchor constraintEqualToAnchor:content.bottomAnchor],
    ]];

    // 6) 保留引用
    self.loadingOverlay = overlay;
    self.loadingContainer = card;
    self.loadingProgress = progress;
    self.loadingPercentLabel = percent;
    self.loadingTitleLabel = title;
    self.loadingSubtitleLabel = subtitleLabel;

    // 7) 显示
    overlay.alpha = 0.0;
    [self.view addSubview:overlay];
    [UIView animateWithDuration:0.2 animations:^{ overlay.alpha = 1.0; }];

    // 8) 启动计时器：从 0% → 100%
    __weak typeof(self) weakSelf = self;
    self.loadingTimer = [NSTimer scheduledTimerWithTimeInterval:0.02 repeats:YES block:^(NSTimer * _Nonnull timer) {
        __strong typeof(weakSelf) strongSelf = weakSelf;
        if (!strongSelf) { [timer invalidate]; return; }
        [strongSelf tickLoading:timer];
    }];
}

- (void)tickLoading:(NSTimer *)timer {
    self.loadingProgressValue += 0.01f; // 每次 +1%
    if (self.loadingProgressValue > 1.0f) self.loadingProgressValue = 1.0f;

    [self.loadingProgress setProgress:self.loadingProgressValue animated:YES];
    int percentInt = (int)roundf(self.loadingProgressValue * 100.0f);
    self.loadingPercentLabel.text = [NSString stringWithFormat:@"%d%%", percentInt];

    if (self.loadingProgressValue >= 1.0f) {
        [timer invalidate];
        self.loadingTimer = nil;
        // 小延迟让 100% 停留一会儿更自然
        dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(0.15 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
            [self cleanupLoadingOverlay];
            [self enterMainController];
        });
    }
}

- (void)cleanupLoadingOverlay {
    if (self.loadingTimer) {
        [self.loadingTimer invalidate];
        self.loadingTimer = nil;
    }
    if (self.loadingOverlay.superview) {
        [UIView animateWithDuration:0.2 animations:^{ self.loadingOverlay.alpha = 0.0; } completion:^(BOOL finished) {
            [self.loadingOverlay removeFromSuperview];
        }];
    }
    self.loadingOverlay = nil;
    self.loadingContainer = nil;
    self.loadingProgress = nil;
    self.loadingPercentLabel = nil;
    self.loadingTitleLabel = nil;
    self.loadingSubtitleLabel = nil;
}

- (void)enterMainController {
    // 进入 MainViewController：直接替换根控制器，无返回栈 & 无上推动画
    UIViewController *vc = nil;
#ifdef __has_include
#  if __has_include("MainViewController.h")
    vc = [[MainViewController alloc] init];
#  else
    Class cls = NSClassFromString(@"MainViewController");
    if (cls) vc = [[cls alloc] init];
#  endif
#else
    Class cls = NSClassFromString(@"MainViewController");
    if (cls) vc = [[cls alloc] init];
#endif
    if (!vc) return;

    // 若后续还需要 push，可包一层导航控制器，但隐藏导航并禁用侧滑返回
    UINavigationController *nav = [[UINavigationController alloc] initWithRootViewController:vc];
//    nav.navigationBarHidden = YES;
    if (nav.interactivePopGestureRecognizer) {
        nav.interactivePopGestureRecognizer.enabled = NO;
    }

    UIWindow *window = self.view.window;
    if (!window) {
        // 兜底：尽可能拿到一个窗口
        window = UIApplication.sharedApplication.keyWindow ?: UIApplication.sharedApplication.windows.firstObject;
    }
    if (!window) {
        // 实在取不到窗口，则直接无动画 present（依旧禁止返回）
        vc.modalPresentationStyle = UIModalPresentationFullScreen;
        [self presentViewController:vc animated:NO completion:nil];
        return;
    }

    // 直接替换 rootViewController，避免任何 push/present 动画
    [UIView transitionWithView:window duration:0.0 options:UIViewAnimationOptionTransitionNone animations:^{
        window.rootViewController = nav;
    } completion:nil];
}

- (void)dealloc {
    [self cleanupLoadingOverlay];
}

#pragma mark - Wallpaper (Jailbreak-only best-effort)

-(void)setupWallpaperBackgroundIfAvailable {
    self.view.backgroundColor = [UIColor clearColor];
    self.view.opaque = NO;
}

static inline int inline_strcasestr(const char *haystack, const char *needle) {
    size_t hlen = strlen(haystack), nlen = strlen(needle);
    for (size_t i = 0; i <= hlen - nlen; i++) {
        size_t j = 0;
        while (j < nlen && tolower((unsigned char)haystack[i + j]) == tolower((unsigned char)needle[j])) {
            j++;
        }
        if (j == nlen) return 1;
    }
    return 0;
}


@end
