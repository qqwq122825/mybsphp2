#import <Foundation/Foundation.h>

NS_ASSUME_NONNULL_BEGIN

FOUNDATION_EXPORT NSNotificationName const BSPHPHeartbeatStateDidChangeNotification;
FOUNDATION_EXPORT NSString * const BSPHPHeartbeatStatusCodeKey;
FOUNDATION_EXPORT NSString * const BSPHPHeartbeatMessageKey;
FOUNDATION_EXPORT NSString * const BSPHPHeartbeatDrawingAllowedKey;

/// 进程级心跳中心。主页面、悬浮窗、插件和动态库读取同一份授权状态。
@interface BSPHPHeartbeatManager : NSObject

@property (atomic, readonly, getter=isDrawingAllowed) BOOL drawingAllowed;
@property (atomic, copy, readonly) NSString *statusCode;
@property (atomic, copy, readonly) NSString *statusMessage;
@property (atomic, copy, readonly) NSString *sessionHandle;
@property (atomic, readonly, getter=isRunning) BOOL running;

+ (instancetype)sharedManager;
+ (NSString *)newSessionHandle;

- (void)startWithAPI:(NSString *)api
                 host:(NSString *)host
        sessionHandle:(NSString *)sessionHandle
             deviceID:(NSString *)deviceID
             interval:(NSTimeInterval)interval;
- (void)sendHeartbeatNow;
- (void)stop;

@end

/// 插件绘制循环内可直接调用；心跳异常后立即返回 NO。
NS_INLINE BOOL BSPHPPluginDrawingAllowed(void) {
    return BSPHPHeartbeatManager.sharedManager.isDrawingAllowed;
}

NS_ASSUME_NONNULL_END
