//
//  TouchPointPicker.m
//  Ps
//
//  Created by a on 2025/9/26.
//

#import "TouchPointPickerViewController.h"

@implementation UINavigationController (TouchPointPickerOrientationForwarding)
- (BOOL)shouldAutorotate {
    return [self.topViewController shouldAutorotate];
}
- (UIInterfaceOrientationMask)supportedInterfaceOrientations {
    return [self.topViewController supportedInterfaceOrientations];
}
- (UIInterfaceOrientation)preferredInterfaceOrientationForPresentation {
    return [self.topViewController preferredInterfaceOrientationForPresentation];
}
@end

@interface TouchPointPickerViewController () <UIImagePickerControllerDelegate, UINavigationControllerDelegate>
@property (nonatomic, assign) BOOL dotWasMovedByUser;
@property (nonatomic, assign) CGPoint dotNorm; // (0..1, 0..1) relative to image/view rect
@end

@implementation TouchPointPickerViewController

// Force landscape orientation before setting up UI
- (void)viewDidLoad {
    [super viewDidLoad];
    self.view.backgroundColor = [UIColor blackColor];


    self.navigationItem.rightBarButtonItem = [[UIBarButtonItem alloc]
        initWithBarButtonSystemItem:UIBarButtonSystemItemDone
        target:self
        action:@selector(onDone)];

    // Add a draggable dot
    UIView *dotView = [[UIView alloc] initWithFrame:CGRectMake(self.view.bounds.size.width/2-12, self.view.bounds.size.height/2-12, 24, 24)];
    dotView.backgroundColor = [UIColor redColor];
    dotView.layer.cornerRadius = 12;
    dotView.layer.masksToBounds = YES;
    dotView.userInteractionEnabled = YES;
    dotView.tag = 999;
    [self.view addSubview:dotView];

    // Add pan gesture
    UIPanGestureRecognizer *pan = [[UIPanGestureRecognizer alloc] initWithTarget:self action:@selector(handlePan:)];
    [dotView addGestureRecognizer:pan];

    // Add horizontal and vertical crosshair lines
    UIView *hLine = [[UIView alloc] initWithFrame:CGRectMake(0, dotView.center.y, self.view.bounds.size.width, 1)];
    hLine.backgroundColor = [[UIColor whiteColor] colorWithAlphaComponent:0.2];
    hLine.tag = 1001;
    [self.view addSubview:hLine];

    UIView *vLine = [[UIView alloc] initWithFrame:CGRectMake(dotView.center.x, 0, 1, self.view.bounds.size.height)];
    vLine.backgroundColor = [[UIColor whiteColor] colorWithAlphaComponent:0.2];
    vLine.tag = 1002;
    [self.view addSubview:vLine];

    // Add coordinate label
    UILabel *coordLabel = [[UILabel alloc] initWithFrame:CGRectMake(dotView.center.x + 12, dotView.center.y + 12, 120, 20)];
    coordLabel.textColor = [UIColor whiteColor];
    coordLabel.font = [UIFont systemFontOfSize:12];
    coordLabel.tag = 1003;
    [self.view addSubview:coordLabel];
    coordLabel.text = [NSString stringWithFormat:@"(%.0f, %.0f)", dotView.center.x, dotView.center.y];
    [self captureNormalizedDotFromRect:self.view.bounds];

    UIButton *openImageBtn = [UIButton buttonWithType:UIButtonTypeSystem];
    openImageBtn.frame = CGRectMake(20, self.view.bounds.size.height - 60, self.view.bounds.size.width - 40, 40);
    [openImageBtn setTitle:@"打开图片" forState:UIControlStateNormal];
    [openImageBtn addTarget:self action:@selector(onOpenImage) forControlEvents:UIControlEventTouchUpInside];
    openImageBtn.tag = 2001;
    [self.view addSubview:openImageBtn];
}


- (void)handlePan:(UIPanGestureRecognizer *)pan {
    UIView *dot = pan.view;
    CGPoint translation = [pan translationInView:self.view];
    dot.center = CGPointMake(dot.center.x + translation.x, dot.center.y + translation.y);
    [pan setTranslation:CGPointZero inView:self.view];

    // Move crosshair lines
    UIView *hLine = [self.view viewWithTag:1001];
    hLine.frame = CGRectMake(0, dot.center.y, self.view.bounds.size.width, 1);

    UIView *vLine = [self.view viewWithTag:1002];
    vLine.frame = CGRectMake(dot.center.x, 0, 1, self.view.bounds.size.height);

    // Update label position and text
    UILabel *coordLabel = (UILabel *)[self.view viewWithTag:1003];
    coordLabel.frame = CGRectMake(dot.center.x + 12, dot.center.y + 12, 120, 20);
    coordLabel.text = [NSString stringWithFormat:@"(%.0f, %.0f)", dot.center.x, dot.center.y];
    self.dotWasMovedByUser = YES;
    [self captureNormalizedDotFromRect:[self currentBaseRect]];
}

/*
#pragma mark - Navigation

// In a storyboard-based application, you will often want to do a little preparation before navigation
- (void)prepareForSegue:(UIStoryboardSegue *)segue sender:(id)sender {
    // Get the new view controller using [segue destinationViewController].
    // Pass the selected object to the new view controller.
}
*/

- (void)userDidPickPoint:(CGPoint)point {
    if (self.onTouchPointSelected) {
        self.onTouchPointSelected(point);
    }
}


- (void)onDone {
    UIView *dotView = [self.view viewWithTag:999];
    if (self.onTouchPointSelected && dotView) {
        self.onTouchPointSelected(dotView.center);
    }
    [self.navigationController popViewControllerAnimated:YES];
}

- (void)onOpenImage {
    UIImagePickerController *picker = [[UIImagePickerController alloc] init];
    picker.sourceType = UIImagePickerControllerSourceTypePhotoLibrary;
    picker.delegate = (id<UIImagePickerControllerDelegate, UINavigationControllerDelegate>)self;
    [self presentViewController:picker animated:YES completion:nil];
}

#pragma mark - UIImagePickerControllerDelegate

- (void)imagePickerController:(UIImagePickerController *)picker didFinishPickingMediaWithInfo:(NSDictionary<NSString *,id> *)info {
    UIImage *image = info[UIImagePickerControllerOriginalImage];
    if (image) {
        [[self.view viewWithTag:888] removeFromSuperview];

        UIImageView *bgImageView = [[UIImageView alloc] init];
        bgImageView.userInteractionEnabled = NO;
        bgImageView.tag = 888;

        CGFloat viewWidth = self.view.bounds.size.width;
        CGFloat viewHeight = self.view.bounds.size.height;
        CGFloat imageAspect = image.size.width / image.size.height;
        CGFloat viewAspect = viewWidth / viewHeight;

        CGRect targetFrame;
        if (imageAspect > viewAspect) {
            // Image is wider than screen, fit width
            CGFloat scaledHeight = viewWidth / imageAspect;
            targetFrame = CGRectMake(0, (viewHeight - scaledHeight) / 2, viewWidth, scaledHeight);
        } else {
            // Image is taller or same aspect, fit height
            CGFloat scaledWidth = viewHeight * imageAspect;
            targetFrame = CGRectMake((viewWidth - scaledWidth) / 2, 0, scaledWidth, viewHeight);
        }

        bgImageView.frame = targetFrame;
        bgImageView.image = image;
        bgImageView.contentMode = UIViewContentModeScaleAspectFit;

        [self.view insertSubview:bgImageView atIndex:0];
        [self centerCrosshairInRect:targetFrame];
        self.dotNorm = CGPointMake(0.5, 0.5);
    }

    [picker dismissViewControllerAnimated:YES completion:nil];
}


- (void)imagePickerControllerDidCancel:(UIImagePickerController *)picker {
    [picker dismissViewControllerAnimated:YES completion:nil];
}

- (void)forceLandscapeRight {
#if TARGET_OS_IOS
    if (@available(iOS 16.0, *)) {
        UIInterfaceOrientationMask mask = UIInterfaceOrientationMaskLandscapeRight;
        UIWindowScene *scene = (UIWindowScene *)self.view.window.windowScene;
        if (scene) {
            [scene requestGeometryUpdateWithPreferences:[[UIWindowSceneGeometryPreferencesIOS alloc] initWithInterfaceOrientations:mask]
                                            errorHandler:^(NSError * _Nonnull error) {
                NSLog(@"[TouchPointPicker] geometry update failed: %@", error);
            }];
        }
    }
    // Always call the classic path as a fallback to cover cases where the scene isn't ready yet.
    NSNumber *value = @(UIInterfaceOrientationLandscapeRight);
    [[UIDevice currentDevice] setValue:value forKey:@"orientation"];
    [UIViewController attemptRotationToDeviceOrientation];
#endif
}

- (void)viewDidAppear:(BOOL)animated {
    [super viewDidAppear:animated];
    [self forceLandscapeRight];
}

- (void)viewDidLayoutSubviews {
    [super viewDidLayoutSubviews];

    CGFloat w = self.view.bounds.size.width;
    CGFloat h = self.view.bounds.size.height;

    UIView *dotView = [self.view viewWithTag:999];
    if (dotView) {
        // Clamp to visible area
        CGPoint c = dotView.center;
        c.x = fmin(fmax(c.x, 0), w);
        c.y = fmin(fmax(c.y, 0), h);
        dotView.center = c;

        UIView *hLine = [self.view viewWithTag:1001];
        if (hLine) hLine.frame = CGRectMake(0, c.y, w, 1);

        UIView *vLine = [self.view viewWithTag:1002];
        if (vLine) vLine.frame = CGRectMake(c.x, 0, 1, h);

        UILabel *coordLabel = (UILabel *)[self.view viewWithTag:1003];
        if (coordLabel) coordLabel.frame = CGRectMake(c.x + 12, c.y + 12, 120, 20);
    }

    UIButton *openImageBtn = (UIButton *)[self.view viewWithTag:2001];
    if (openImageBtn) {
        openImageBtn.frame = CGRectMake(20, h - 60, w - 40, 40);
    }
}

- (BOOL)shouldAutorotate {
    return YES;
}

- (UIInterfaceOrientationMask)supportedInterfaceOrientations {
    return UIInterfaceOrientationMaskLandscape;
}

- (UIInterfaceOrientation)preferredInterfaceOrientationForPresentation {
    return UIInterfaceOrientationLandscapeRight;
}

- (void)setCrosshairCenter:(CGPoint)c {
    UIView *dotView = [self.view viewWithTag:999];
    if (dotView) {
        dotView.center = c;
    }
    UIView *hLine = [self.view viewWithTag:1001];
    if (hLine) {
        hLine.frame = CGRectMake(0, c.y, self.view.bounds.size.width, 1);
    }
    UIView *vLine = [self.view viewWithTag:1002];
    if (vLine) {
        vLine.frame = CGRectMake(c.x, 0, 1, self.view.bounds.size.height);
    }
    UILabel *coordLabel = (UILabel *)[self.view viewWithTag:1003];
    if (coordLabel) {
        coordLabel.frame = CGRectMake(c.x + 12, c.y + 12, 120, 20);
        coordLabel.text = [NSString stringWithFormat:@"(%.0f, %.0f)", c.x, c.y];
    }
}

- (void)centerCrosshairInRect:(CGRect)rect {
    CGPoint centerPoint = CGPointMake(CGRectGetMidX(rect), CGRectGetMidY(rect));
    [self setCrosshairCenter:centerPoint];
    self.dotNorm = CGPointMake(0.5, 0.5);
    self.dotWasMovedByUser = NO;
}
// --- Normalized dot helpers and rotation handling ---

- (CGRect)currentBaseRect {
    UIView *img = [self.view viewWithTag:888];
    if (img) return ((UIView *)img).frame;
    return self.view.bounds;
}

- (void)captureNormalizedDotFromRect:(CGRect)rect {
    UIView *dot = [self.view viewWithTag:999];
    if (!dot) return;
    CGFloat w = rect.size.width, h = rect.size.height;
    if (w <= 0 || h <= 0) return;
    CGPoint c = dot.center;
    CGFloat nx = (c.x - CGRectGetMinX(rect)) / w;
    CGFloat ny = (c.y - CGRectGetMinY(rect)) / h;
    // clamp to [0,1]
    nx = fmin(fmax(nx, 0.0), 1.0);
    ny = fmin(fmax(ny, 0.0), 1.0);
    self.dotNorm = CGPointMake(nx, ny);
}

- (void)applyNormalizedDotToRect:(CGRect)rect {
    CGFloat w = rect.size.width, h = rect.size.height;
    if (w <= 0 || h <= 0) return;
    CGPoint c = CGPointMake(CGRectGetMinX(rect) + self.dotNorm.x * w,
                            CGRectGetMinY(rect) + self.dotNorm.y * h);
    [self setCrosshairCenter:c];
}

- (void)viewWillTransitionToSize:(CGSize)size withTransitionCoordinator:(id<UIViewControllerTransitionCoordinator>)coordinator {
    [super viewWillTransitionToSize:size withTransitionCoordinator:coordinator];
    // capture normalized coords against the "before" rect
    CGRect beforeRect = [self currentBaseRect];
    [self captureNormalizedDotFromRect:beforeRect];

    [coordinator animateAlongsideTransition:^(id<UIViewControllerTransitionCoordinatorContext>  _Nonnull context) {
        // no-op; frames are in flux
    } completion:^(id<UIViewControllerTransitionCoordinatorContext>  _Nonnull context) {
        // apply to the "after" rect (new coordinate system)
        CGRect afterRect = [self currentBaseRect];
        [self applyNormalizedDotToRect:afterRect];
    }];
}


@end

