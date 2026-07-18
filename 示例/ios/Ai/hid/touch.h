//
//  touch.h
//  Ps
//
//  Created by a on 2025/9/28.
//

#import <Foundation/Foundation.h>
#import "IOKitSPI.h"
#import <CoreGraphics/CGGeometry.h>

NS_ASSUME_NONNULL_BEGIN

@interface touch : NSObject

@property (nonatomic, assign) BOOL shouldShowTouches;

+ (touch *)shared;
- (void)singleTouchBeginAt:(CGPoint)location;
- (void)singleTouchMoveTo:(CGPoint)location;
- (void)singleTouchEndAt:(CGPoint)location;


- (void)touchDownAtPoint:(CGPoint)location withIndex:(NSUInteger)index;
- (void)moveTouchToPoint:(CGPoint)location withIndex:(NSUInteger)index;
- (void)liftUpAtPoint:(CGPoint)location withIndex:(NSUInteger)index;
-(void)logAvailableHIDServices;

//-(uint64_t)preferredTouchscreenRegistryID ;


@end

NS_ASSUME_NONNULL_END
