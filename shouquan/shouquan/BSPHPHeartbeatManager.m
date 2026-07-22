#import "BSPHPHeartbeatManager.h"
#import "BSPHPResponseProcessor.h"
#import "NetTool.h"

NSNotificationName const BSPHPHeartbeatStateDidChangeNotification = @"BSPHPHeartbeatStateDidChangeNotification";
NSString * const BSPHPHeartbeatStatusCodeKey = @"statusCode";
NSString * const BSPHPHeartbeatMessageKey = @"message";
NSString * const BSPHPHeartbeatDrawingAllowedKey = @"drawingAllowed";

@interface BSPHPHeartbeatManager ()
@property (atomic, readwrite, getter=isDrawingAllowed) BOOL drawingAllowed;
@property (atomic, copy, readwrite) NSString *statusCode;
@property (atomic, copy, readwrite) NSString *statusMessage;
@property (atomic, copy, readwrite) NSString *sessionHandle;
@property (atomic, readwrite, getter=isRunning) BOOL running;
@property (nonatomic, copy) NSString *api;
@property (nonatomic, copy) NSString *host;
@property (nonatomic, copy) NSString *deviceID;
@property (nonatomic, strong) NSTimer *timer;
@property (nonatomic) NSUInteger consecutiveNetworkFailures;
@end

@implementation BSPHPHeartbeatManager

+ (instancetype)sharedManager {
    static BSPHPHeartbeatManager *manager;
    static dispatch_once_t onceToken;
    dispatch_once(&onceToken, ^{ manager = [BSPHPHeartbeatManager new]; });
    return manager;
}

+ (NSString *)newSessionHandle {
    NSString *left = [NSUUID.UUID.UUIDString stringByReplacingOccurrencesOfString:@"-" withString:@""];
    NSString *right = [NSUUID.UUID.UUIDString stringByReplacingOccurrencesOfString:@"-" withString:@""];
    return [left stringByAppendingString:right].lowercaseString;
}

- (instancetype)init {
    self = [super init];
    if (self) { _statusCode = @"NOT_LOGGED_IN"; _statusMessage = @"尚未登录"; _sessionHandle = @""; }
    return self;
}

- (void)startWithAPI:(NSString *)api host:(NSString *)host sessionHandle:(NSString *)sessionHandle
             deviceID:(NSString *)deviceID interval:(NSTimeInterval)interval {
    dispatch_async(dispatch_get_main_queue(), ^{
        [self.timer invalidate];
        self.api = api; self.host = host; self.sessionHandle = sessionHandle; self.deviceID = deviceID;
        self.consecutiveNetworkFailures = 0; self.running = YES;
        [self publishCode:@"5031" message:@"登录成功，等待心跳" drawingAllowed:YES];
        self.timer = [NSTimer timerWithTimeInterval:MAX(5.0, interval)
                                             target:self selector:@selector(sendHeartbeatNow)
                                           userInfo:nil repeats:YES];
        [[NSRunLoop mainRunLoop] addTimer:self.timer forMode:NSRunLoopCommonModes];
    });
}

- (void)sendHeartbeatNow {
    if (!self.running || self.sessionHandle.length != 64) return;
    NSString *safeCode = [NSString stringWithFormat:@"heartbeat-%lld-%@",
                          (long long)(NSDate.date.timeIntervalSince1970 * 1000), NSUUID.UUID.UUIDString];
    NSDictionary *param = @{@"api": self.api, @"BSphpSeSsL": self.sessionHandle,
                            @"key": self.deviceID ?: @"", @"appsafecode": safeCode};
    __weak typeof(self) weakSelf = self;
    [NetTool Post_AppendURL:self.host parameters:param success:^(id responseObject) {
        NSError *error = nil;
        NSDictionary *result = [BSPHPResponseProcessor processHeartbeatResponse:responseObject
                                                                    sessionHandle:weakSelf.sessionHandle
                                                                      appSafeCode:safeCode error:&error];
        if (error) { [weakSelf handleFailure:error.localizedDescription]; return; }
        weakSelf.consecutiveNetworkFailures = 0;
        NSString *code = result[@"code"] ?: @"UNKNOWN";
        BOOL valid = [result[@"valid"] boolValue];
        [weakSelf publishCode:code message:result[@"message"] ?: @"心跳状态异常" drawingAllowed:valid];
        if (!valid) [weakSelf stopTimerOnly];
    } failure:^(NSError *error) {
        [weakSelf handleFailure:error.localizedDescription];
    }];
}

- (void)handleFailure:(NSString *)message {
    self.consecutiveNetworkFailures++;
    NSLog(@"[BSPHP][插件心跳] 网络失败 %lu/3：%@", (unsigned long)self.consecutiveNetworkFailures, message);
    if (self.consecutiveNetworkFailures < 3) return;
    [self publishCode:@"NETWORK_TIMEOUT" message:@"连续三次心跳请求失败" drawingAllowed:NO];
    [self stopTimerOnly];
}

- (void)stop {
    dispatch_async(dispatch_get_main_queue(), ^{
        [self stopTimerOnly];
        [self publishCode:@"STOPPED" message:@"登录连接已停止" drawingAllowed:NO];
        self.sessionHandle = @"";
    });
}

- (void)stopTimerOnly {
    [self.timer invalidate]; self.timer = nil; self.running = NO;
}

- (void)publishCode:(NSString *)code message:(NSString *)message drawingAllowed:(BOOL)drawingAllowed {
    void (^publish)(void) = ^{
        self.statusCode = code; self.statusMessage = message; self.drawingAllowed = drawingAllowed;
        [[NSNotificationCenter defaultCenter] postNotificationName:BSPHPHeartbeatStateDidChangeNotification
                                                            object:self
                                                          userInfo:@{BSPHPHeartbeatStatusCodeKey: code,
                                                                     BSPHPHeartbeatMessageKey: message,
                                                                     BSPHPHeartbeatDrawingAllowedKey: @(drawingAllowed)}];
    };
    NSThread.isMainThread ? publish() : dispatch_async(dispatch_get_main_queue(), publish);
}

@end
