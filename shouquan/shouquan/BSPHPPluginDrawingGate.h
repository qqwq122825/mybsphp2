#import "BSPHPHeartbeatManager.h"

#ifndef BSPHPPluginDrawingGate_h
#define BSPHPPluginDrawingGate_h

/// 放在插件每帧绘制入口的第一行。心跳过期、冻结或连续超时时立即返回。
#define BSPHP_GUARD_DRAW_OR_RETURN() do { \
    if (!BSPHPPluginDrawingAllowed()) return; \
} while (0)

/// 用于有返回值的绘制函数。
#define BSPHP_GUARD_DRAW_OR_RETURN_VALUE(value) do { \
    if (!BSPHPPluginDrawingAllowed()) return (value); \
} while (0)

#endif
