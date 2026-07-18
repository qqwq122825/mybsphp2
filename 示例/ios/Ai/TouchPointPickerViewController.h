//
//  TouchPointPickerViewController.h
//  Ps
//
//  Created by a on 2025/9/26.
//

#import <UIKit/UIKit.h>

NS_ASSUME_NONNULL_BEGIN

@interface TouchPointPickerViewController : UIViewController

@property (nonatomic, copy) void (^onTouchPointSelected)(CGPoint point);

@end

NS_ASSUME_NONNULL_END

