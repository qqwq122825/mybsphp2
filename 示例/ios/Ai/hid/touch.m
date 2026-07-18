
//
//  touch.m
//  Ps
//
//  Created by a on 2025/9/28.
//

#import "touch.h"
#import <dlfcn.h>
#import <sys/sysctl.h>
#import <mach/mach_time.h>
#import "HUDRenderView.h"
static const NSTimeInterval fingerLiftDelay = 0.05;
static const NSTimeInterval multiTapInterval = 0.15;
static const NSTimeInterval fingerMoveInterval = 0.016;
static const NSTimeInterval longPressHoldDelay = 2.0;
static const IOHIDFloat defaultMajorRadius = 5;
static const IOHIDFloat defaultPathPressure = 0.2;


NSUInteger const HIDMaxTouchCount = 30;
typedef struct {
    int identifier;
    CGPoint point;
    IOHIDFloat pathMajorRadius;
    IOHIDFloat pathPressure;
    UInt8 pathProximity;
    BOOL isStylus;
    IOHIDFloat azimuthAngle;
    IOHIDFloat altitudeAngle;
} SyntheticEventDigitizerInfo;

static int fingerIdentifiers[HIDMaxTouchCount] = {
    0x2, 0x3, 0x4, 0x5, 0x1, 0x6, 0x7, 0x8, 0x9, 0xa,
    11, 12, 13, 14, 15, 16, 17, 18, 19, 20,
    21, 22, 23, 24, 25, 26, 27, 28, 29, 30,
};
typedef enum {
    HandEventNull,
    HandEventTouched,
    HandEventMoved,
    HandEventChordChanged,
    HandEventLifted,
    HandEventCanceled,
    StylusEventTouched,
    StylusEventMoved,
    StylusEventLifted,
} HandEventType;




@implementation touch{
    SyntheticEventDigitizerInfo _activePoints[HIDMaxTouchCount];
    NSUInteger _activePointCount;
    CGSize _screenSize;
}


+ (touch *)shared{
    static touch *_eventGenerator = nil;
    static dispatch_once_t onceToken;
    dispatch_once(&onceToken, ^{
        _eventGenerator = [[touch alloc] init];
    });
    return _eventGenerator;
}


- (instancetype)init {
    self = [super init];
    if (!self) return nil;
    
 
    if (_screenSize.width <= 0 || _screenSize.height <= 0) {
        _screenSize = [UIScreen mainScreen].bounds.size;
        NSLog(@"⚠️ fallback to UIScreen: (%.2f, %.2f)", _screenSize.width, _screenSize.height);
    }
   

    for (NSUInteger i = 0; i < HIDMaxTouchCount; ++i)
        _activePoints[i].identifier = fingerIdentifiers[i];
    
#if DEBUG
    _shouldShowTouches = YES;
#endif

    return self;
}

- (void)singleTouchBeginAt:(CGPoint)location  {
    [self _touchDown:location touchCount:1];
   
}

- (void)singleTouchMoveTo:(CGPoint)location {
    CGPoint pts[1] = { location };
    [self _updateTouchPoints:pts count:1];
  
}

- (void)singleTouchEndAt:(CGPoint)location  {
    [self _liftUp:location touchCount:1];

}
- (void)_touchDown:(CGPoint)location touchCount:(NSUInteger)touchCount {
    NSParameterAssert(touchCount > 0);
    
    touchCount = MIN(touchCount, HIDMaxTouchCount);

    CGPoint locations[touchCount];

    for (NSUInteger index = 0; index < touchCount; ++index)
        locations[index] = location;

    [self touchDownAtPoints:locations touchCount:touchCount];
}

- (void)touchDownAtPoints:(CGPoint *)locations touchCount:(NSUInteger)touchCount {
    NSParameterAssert(touchCount > 0);
    
    touchCount = MIN(touchCount, HIDMaxTouchCount);

    _activePointCount = touchCount;

    for (NSUInteger index = 0; index < touchCount; ++index) {
        _activePoints[index].point = locations[index];
        _activePoints[index].isStylus = NO;
    }

    IOHIDEventRef eventRef = [self _createIOHIDEventType:HandEventTouched];
    _sendHIDEvent(eventRef);
    CFRelease(eventRef);
}

- (void)_updateTouchPoints:(CGPoint *)points count:(NSUInteger)count {
    NSParameterAssert(count > 0);
    
    HandEventType handEventType;

    // The hand event type is based on previous state.
    if (!_activePointCount)
        handEventType = HandEventTouched;
    else if (!count)
        handEventType = HandEventLifted;
    else if (count == _activePointCount)
        handEventType = HandEventMoved;
    else
        handEventType = HandEventChordChanged;

    // Update previous count for next event.
    _activePointCount = count;

    // Update point locations.
    for (NSUInteger i = 0; i < count; ++i) {
        _activePoints[i].point = points[i];
    }

    IOHIDEventRef eventRef = [self _createIOHIDEventType:handEventType];
    _sendHIDEvent(eventRef);
    CFRelease(eventRef);
}

- (void)_liftUp:(CGPoint)location touchCount:(NSUInteger)touchCount {
    NSParameterAssert(touchCount > 0);
    
    touchCount = MIN(touchCount, HIDMaxTouchCount);

    CGPoint locations[touchCount];

    for (NSUInteger index = 0; index < touchCount; ++index)
        locations[index] = location;

    [self liftUpAtPoints:locations touchCount:touchCount];
}


- (void)liftUpAtPoints:(CGPoint *)locations touchCount:(NSUInteger)touchCount {
    NSParameterAssert(touchCount > 0);
    
    touchCount = MIN(touchCount, HIDMaxTouchCount);
    touchCount = MIN(touchCount, _activePointCount);

    NSUInteger newPointCount = _activePointCount - touchCount;

    for (NSUInteger index = 0; index < touchCount; ++index) {
        _activePoints[newPointCount + index].point = locations[index];
    }

    IOHIDEventRef eventRef = [self _createIOHIDEventType:HandEventLifted];
    _sendHIDEvent(eventRef);
    CFRelease(eventRef);

    _activePointCount = newPointCount;
}

- (IOHIDEventRef)_createIOHIDEventType:(HandEventType)eventType {
    BOOL isTouching = (eventType == HandEventTouched || eventType == HandEventMoved || eventType == HandEventChordChanged || eventType == StylusEventTouched || eventType == StylusEventMoved);

    IOHIDDigitizerEventMask eventMask = 0;
    if (eventType == HandEventTouched) {
        eventMask = kIOHIDDigitizerEventTouch | kIOHIDDigitizerEventRange | kIOHIDDigitizerEventIdentity;
    } else if (eventType == HandEventMoved) {
        eventMask = kIOHIDDigitizerEventPosition | kIOHIDDigitizerEventTouch | kIOHIDDigitizerEventAttribute;
    } else if (eventType == HandEventChordChanged) {
        eventMask = kIOHIDDigitizerEventPosition | kIOHIDDigitizerEventTouch | kIOHIDDigitizerEventAttribute | kIOHIDDigitizerEventIdentity;
    } else if (eventType == HandEventLifted || eventType == HandEventCanceled) {
        eventMask = kIOHIDDigitizerEventRange | kIOHIDDigitizerEventTouch | kIOHIDDigitizerEventIdentity;
    }

    uint64_t machTime = mach_absolute_time();
    IOHIDEventRef eventRef = IOHIDEventCreateDigitizerEvent(kCFAllocatorDefault, machTime,
                                                            kIOHIDDigitizerTransducerTypeHand,
                                                            0,
                                                            1,
                                                            eventMask,
                                                            0,
                                                            0, 0, 0,
                                                            0,
                                                            0,
                                                            0,
                                                            isTouching,
                                                            kIOHIDEventOptionNone);

    IOHIDEventSetIntegerValue(eventRef, kIOHIDEventFieldIsBuiltIn, 1);
    IOHIDEventSetIntegerValue(eventRef, kIOHIDEventFieldDigitizerIsDisplayIntegrated, 1);

    for (NSUInteger i = 0; i < _activePointCount; ++i) {
        SyntheticEventDigitizerInfo *pointInfo = &_activePoints[i];
        if (eventType == HandEventTouched) {
            if (!pointInfo->pathMajorRadius)
                pointInfo->pathMajorRadius = defaultMajorRadius;
            if (!pointInfo->pathPressure)
                pointInfo->pathPressure = defaultPathPressure;
            if (!pointInfo->pathProximity)
                pointInfo->pathProximity = kGSEventPathInfoInTouch | kGSEventPathInfoInRange;
        } else if (eventType == HandEventLifted || eventType == HandEventCanceled || eventType == StylusEventLifted) {
            pointInfo->pathMajorRadius = 0;
            pointInfo->pathPressure = 0;
            pointInfo->pathProximity = 0;
        }

        CGPoint point = pointInfo->point;
        point = CGPointMake(point.x / _screenSize.width, point.y / _screenSize.height);
        

        IOHIDEventRef subEvent;
        subEvent = IOHIDEventCreateDigitizerFingerEvent(kCFAllocatorDefault,      // allocator
                                                        machTime,                 // timestamp
                                                        20,    // index
                                                       20,    // identity
                                                        eventMask,                // eventMask
                                                        point.x, point.y, 0,      // x, y, z
                                                        pointInfo->pathPressure,  // tipPressure
                                                        90.0,                     // twist
                                                        pointInfo->pathProximity & kGSEventPathInfoInRange,  // range
                                                        pointInfo->pathProximity & kGSEventPathInfoInTouch,  // touch
                                                        kIOHIDEventOptionNone);

        IOHIDEventSetFloatValue(subEvent, kIOHIDEventFieldDigitizerMinorRadius, pointInfo->pathMajorRadius);     // minor radius
        IOHIDEventSetFloatValue(subEvent, kIOHIDEventFieldDigitizerMajorRadius, pointInfo->pathMajorRadius);     // major radius

        IOHIDEventAppendEvent(eventRef, subEvent, 0);
        CFRelease(subEvent);
    }

    return eventRef;
}
static pid_t gSpringBoardPID = -1;

pid_t getSpringBoardPID(void) {
    if (gSpringBoardPID > 0) return gSpringBoardPID;

    size_t length = 0;
    static const int mib[] = {CTL_KERN, KERN_PROC, KERN_PROC_ALL, 0};
    if (sysctl((int *)mib, 3, NULL, &length, NULL, 0) == -1) return -1;

    struct kinfo_proc *procBuffer = malloc(length);
    if (!procBuffer) return -1;

    if (sysctl((int *)mib, 3, procBuffer, &length, NULL, 0) == -1) {
        free(procBuffer);
        return -1;
    }

    int count = (int)(length / sizeof(struct kinfo_proc));
    for (int i = 0; i < count; ++i) {
        const char *procName = procBuffer[i].kp_proc.p_comm;
        if (strcmp(procName, "SpringBoard") == 0) {
            gSpringBoardPID = procBuffer[i].kp_proc.p_pid;
            break;
        }
    }

    free(procBuffer);
    return gSpringBoardPID;
}

static void _sendHIDEvent(IOHIDEventRef eventRef) {
 
    static IOHIDEventSystemClientRef _ioSystemClient = nil;
    static dispatch_once_t onceToken;
    dispatch_once(&onceToken, ^{
        _ioSystemClient = IOHIDEventSystemClientCreate(kCFAllocatorDefault);
    });

    if (!eventRef) return;


    // Retain event for async dispatch
    IOHIDEventRef strongEvent = (IOHIDEventRef)CFRetain(eventRef);
    dispatch_async(dispatch_get_main_queue(), ^{
        IOHIDEventSetSenderID(strongEvent, 0x8000000817319379);
//        NSLog(@"发送");


        IOHIDEventSystemClientDispatchEvent(_ioSystemClient, strongEvent);


        CFRelease(strongEvent);
    });
}

static uint64_t preferredTouchscreenRegistryID() {
    IOHIDEventSystemClientRef client = IOHIDEventSystemClientCreate(kCFAllocatorDefault);
    if (!client) return 0;

    CFArrayRef services = IOHIDEventSystemClientCopyServices(client);
    if (!services) {
        CFRelease(client);
        return 0;
    }

    CFIndex count = CFArrayGetCount(services);
    uint64_t matchedRID = 0;

    for (CFIndex i = 0; i < count; i++) {
        IOHIDServiceClientRef service = (IOHIDServiceClientRef)CFArrayGetValueAtIndex(services, i);

        CFTypeRef usagePageObj = IOHIDServiceClientCopyProperty(service, CFSTR("PrimaryUsagePage"));
        CFTypeRef usageObj     = IOHIDServiceClientCopyProperty(service, CFSTR("PrimaryUsage"));

        int usagePage = 0, usage = 0;
        if (usagePageObj && CFGetTypeID(usagePageObj) == CFNumberGetTypeID())
            CFNumberGetValue((CFNumberRef)usagePageObj, kCFNumberIntType, &usagePage);
        if (usageObj && CFGetTypeID(usageObj) == CFNumberGetTypeID())
            CFNumberGetValue((CFNumberRef)usageObj, kCFNumberIntType, &usage);

        if (usagePageObj) CFRelease(usagePageObj);
        if (usageObj) CFRelease(usageObj);

        if (usagePage == 0x0D && usage == 0x04) {
            // 获取 registryID（先尝试函数）
            uint64_t registryIDValue = 0;
            static dispatch_once_t onceToken;
            static uint64_t (*GetRegistryIDFunc)(IOHIDServiceClientRef) = NULL;
            static void *libHandle = NULL;
            dispatch_once(&onceToken, ^{
                libHandle = dlopen("/System/Library/Frameworks/IOKit.framework/IOKit", RTLD_LAZY);
                if (libHandle) {
                    GetRegistryIDFunc = (uint64_t (*)(IOHIDServiceClientRef))dlsym(libHandle, "IOHIDServiceClientGetRegistryID");
                }
            });

            if (GetRegistryIDFunc) {
                registryIDValue = GetRegistryIDFunc(service);
            } else {
                CFNumberRef regNum = IOHIDServiceClientCopyProperty(service, CFSTR("registryID"));
                if (regNum && CFGetTypeID(regNum) == CFNumberGetTypeID()) {
                    CFNumberGetValue(regNum, kCFNumberSInt64Type, &registryIDValue);
                }
                if (regNum) CFRelease(regNum);
            }

            matchedRID = registryIDValue;
            break; // 找到就退出
        }
    }

    CFRelease(services);
    CFRelease(client);
    return matchedRID;
}

static uint64_t gNextSenderID = 0x1000003b0;  // 初始起始 ID

static void trySenderIDCollision(IOHIDEventRef eventRef) {
    static IOHIDEventSystemClientRef _ioSystemClient = nil;
    static dispatch_once_t onceToken;
    dispatch_once(&onceToken, ^{
        _ioSystemClient = IOHIDEventSystemClientCreate(kCFAllocatorDefault);
    
  
    });
    if (!eventRef) return;

    // 拷贝一个 event 用于注入
    IOHIDEventRef strongEvent = (IOHIDEventRef)CFRetain(eventRef);

    dispatch_async(dispatch_get_main_queue(), ^{
        uint64_t sid = 0x8000000817319379;
//        gNextSenderID += 1;  // 每次调用就 +1
//        NSLog(@"尝试 senderID = 0x%llx", sid);
        IOHIDEventSetSenderID(strongEvent, sid);
       
        IOHIDEventSystemClientDispatchEvent(_ioSystemClient, strongEvent);

        CFRelease(strongEvent);
    });
}

- (void)touchDownAtPoint:(CGPoint)location withIndex:(NSUInteger)index {
    if (index >= HIDMaxTouchCount) {
        NSLog(@"手指索引超出范围: %lu", (unsigned long)index);
        return;
    }
    
    _activePointCount = MAX(_activePointCount, index + 1);
    _activePoints[index].point = location;
    _activePoints[index].isStylus = NO;
    _activePoints[index].pathMajorRadius = defaultMajorRadius;
    _activePoints[index].pathPressure = defaultPathPressure;
    _activePoints[index].pathProximity = kGSEventPathInfoInTouch | kGSEventPathInfoInRange;
    
    IOHIDEventRef eventRef = [self _createIOHIDEventType:HandEventTouched];
    _sendHIDEvent(eventRef);
    CFRelease(eventRef);
    
    // 添加 marker 事件
//    [self sendMarkerHIDEvent];
    
    NSLog(@"手指按下 - 索引: %lu, ID: %d, 位置: (%.0f, %.0f)",
          (unsigned long)index, _activePoints[index].identifier, location.x, location.y);
}

- (void)moveTouchToPoint:(CGPoint)location withIndex:(NSUInteger)index {
    if (index >= _activePointCount) {
        NSLog(@"手指未按下，无法移动: %lu", (unsigned long)index);
        return;
    }
    
    _activePoints[index].point = location;
    
    IOHIDEventRef eventRef = [self _createIOHIDEventType:HandEventMoved];
    _sendHIDEvent(eventRef);
    CFRelease(eventRef);
    
 
}

- (void)liftUpAtPoint:(CGPoint)location withIndex:(NSUInteger)index {
    if (index >= _activePointCount) {
        NSLog(@"手指未按下，无法抬起: %lu", (unsigned long)index);
        return;
    }
    
    _activePoints[index].point = location;
    _activePoints[index].pathMajorRadius = 0;
    _activePoints[index].pathPressure = 0;
    _activePoints[index].pathProximity = 0;
    
    IOHIDEventRef eventRef = [self _createIOHIDEventType:HandEventLifted];
    _sendHIDEvent(eventRef);
    CFRelease(eventRef);
    
    // 添加 marker 事件（标记手势结束）
//    [self sendMarkerHIDEvent];
    
    // 如果抬起的是最后一个触摸点，调整计数
    if (index == _activePointCount - 1) {
        _activePointCount--;
    }
    
    NSLog(@"手指抬起 - 索引: %lu, ID: %d", (unsigned long)index, _activePoints[index].identifier);
}



-(void)logAvailableHIDServices {
    IOHIDEventSystemClientRef client = IOHIDEventSystemClientCreate(kCFAllocatorDefault);
    if (!client) {
        NSLog(@"❌ Failed to create IOHIDEventSystemClient");
        return;
    }

    CFArrayRef services = IOHIDEventSystemClientCopyServices(client);
    if (!services) {
        NSLog(@"⚠️ No IOHID services found");
        CFRelease(client);
        return;
    }

    CFIndex count = CFArrayGetCount(services);
    NSLog(@"🔍 Found %ld IOHID services:", count);

    for (CFIndex i = 0; i < count; i++) {
        IOHIDServiceClientRef service = (IOHIDServiceClientRef)CFArrayGetValueAtIndex(services, i);

        // Fallback logic for registryID
        uint64_t registryIDValue = 0;
        static dispatch_once_t onceToken;
        static uint64_t (*GetRegistryIDFunc)(IOHIDServiceClientRef) = NULL;
        static void *libHandle = NULL;
        dispatch_once(&onceToken, ^{
            libHandle = dlopen("/System/Library/Frameworks/IOKit.framework/IOKit", RTLD_LAZY);
            if (libHandle) {
                GetRegistryIDFunc = (uint64_t (*)(IOHIDServiceClientRef))dlsym(libHandle, "IOHIDServiceClientGetRegistryID");
            }
        });

        if (GetRegistryIDFunc) {
            registryIDValue = GetRegistryIDFunc(service);
        } else {
            CFNumberRef regNum = IOHIDServiceClientCopyProperty(service, CFSTR("registryID"));
            if (regNum && CFGetTypeID(regNum) == CFNumberGetTypeID()) {
                CFNumberGetValue(regNum, kCFNumberSInt64Type, &registryIDValue);
            }
            if (regNum) CFRelease(regNum);
        }

        NSString *registryIDStr = [NSString stringWithFormat:@"0x%llx", registryIDValue];

        CFTypeRef product = IOHIDServiceClientCopyProperty(service, CFSTR("Product"));
        CFTypeRef usagePage = IOHIDServiceClientCopyProperty(service, CFSTR("PrimaryUsagePage"));
        CFTypeRef usage = IOHIDServiceClientCopyProperty(service, CFSTR("PrimaryUsage"));
        CFTypeRef senderID = IOHIDServiceClientCopyProperty(service, CFSTR("SenderID"));

        NSLog(@"🟦 [%ld] Product: %@ | UsagePage: %@ | Usage: %@ | SenderID: %@ registryID:%@",
              (long)i,
              (__bridge id)product ?: @"(null)",
              (__bridge id)usagePage ?: @"(null)",
              (__bridge id)usage ?: @"(null)",
              (__bridge id)senderID ?: @"(null)",
              registryIDStr);

        if (product) CFRelease(product);
        if (usagePage) CFRelease(usagePage);
        if (usage) CFRelease(usage);
        if (senderID) CFRelease(senderID);
        // (no registryID to release here)
    }

    CFRelease(services);
    CFRelease(client);
}

@end
