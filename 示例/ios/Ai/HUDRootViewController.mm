//
//  HUDRootViewController.m
//  Ai
//
//  Created by a on 2025/8/19.
//

#import "HUDRootViewController.h"
#import <UIKit/UIKit.h>
#import "HUDRenderView.h"
#import <objc/runtime.h>

#import "FBSOrientationUpdate.h"
#import "FBSOrientationObserver.h"
#import <notify.h>


@interface HUDRootViewController (){
    NSMutableDictionary *_userDefaults;
}


@property HUDRenderView *renderView;


@end
@implementation HUDRootViewController
{
    FBSOrientationObserver *_orientationObserver;
    UIInterfaceOrientation _orientation;
}

- (instancetype)init {
    self = [super init];
    if (self) {
        _orientationObserver = [[objc_getClass("FBSOrientationObserver") alloc] init];
        __weak HUDRootViewController *weakSelf = self;
        [_orientationObserver setHandler:^(FBSOrientationUpdate *orientationUpdate) {
            HUDRootViewController *strongSelf = weakSelf;
            dispatch_async(dispatch_get_main_queue(), ^{
                [strongSelf updateOrientation:(UIInterfaceOrientation)orientationUpdate.orientation animateWithDuration:orientationUpdate.duration];
            });
        }];
    }
    return self;
}

- (void)viewDidLoad {
    [super viewDidLoad];
    self.view = [[UIView alloc] initWithFrame:[UIScreen mainScreen].bounds];
    [self loadUserDefaults:YES];
    bool model =  [_userDefaults[@"直播模式"] boolValue];
    self. renderView = [[HUDRenderView alloc] initWithFrame:self.view.bounds];
    self.renderView.autoresizingMask = UIViewAutoresizingFlexibleWidth | UIViewAutoresizingFlexibleHeight;
    
    if(model){
        UIView *yc = [self getBgView];
        yc.frame = self.view.bounds;
        [yc addSubview:self.renderView];
        [self.view addSubview:yc];
    }else{
        [self.view addSubview:self.renderView];
    }
    
    
    
    
   

    notify_post(NOTIFY_LAUNCHED_HUD);
}

- (void)loadUserDefaults:(BOOL)forceReload {
    if (forceReload || !_userDefaults) {
        NSString *path = USER_DEFAULTS_PATH;
        _userDefaults =
            [[NSDictionary dictionaryWithContentsOfFile:path] mutableCopy] ?: [NSMutableDictionary dictionary];
    }
}


-(UIView *)getBgView{
    UITextField *bgTextField = [[UITextField alloc] init];
    [bgTextField setSecureTextEntry:true];
    
    UIView *bgView = bgTextField.subviews.firstObject;
    [bgView setUserInteractionEnabled:false];
   
    return bgView;
}


static inline CGFloat orientationAngle(UIInterfaceOrientation orientation)
{
    switch (orientation) {
        case UIInterfaceOrientationPortraitUpsideDown: return M_PI;
        case UIInterfaceOrientationLandscapeLeft: return -M_PI_2;
        case UIInterfaceOrientationLandscapeRight: return M_PI_2;
        default: return 0;
    }
}

static inline CGRect orientationBounds(UIInterfaceOrientation orientation, CGRect bounds)
{
    switch (orientation) {
        case UIInterfaceOrientationLandscapeLeft:
        case UIInterfaceOrientationLandscapeRight:
            return CGRectMake(0, 0, bounds.size.height, bounds.size.width);
        default:
            return bounds;
    }
}

- (void)updateOrientation:(UIInterfaceOrientation)orientation animateWithDuration:(NSTimeInterval)duration
{
    if (orientation == _orientation) return;
    _orientation = orientation;

    CGRect bounds = orientationBounds(orientation, [UIScreen mainScreen].bounds);

    __weak typeof(self) weakSelf = self;
    [UIView animateWithDuration:duration animations:^{
        UIView *v = weakSelf.view;
        v.transform = CGAffineTransformMakeRotation(orientationAngle(orientation));
        v.bounds = bounds;
    } completion:^(BOOL finished) {
        weakSelf.renderView.frame = weakSelf.view.bounds;
    }];
}

- (BOOL)shouldAutorotate { return YES; }
- (UIInterfaceOrientationMask)supportedInterfaceOrientations { return UIInterfaceOrientationMaskAll; }
- (UIInterfaceOrientation)preferredInterfaceOrientationForPresentation { return UIInterfaceOrientationPortrait; }

@end
