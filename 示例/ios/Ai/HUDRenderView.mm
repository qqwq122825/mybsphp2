//
//  HUDRenderView.mm
//  Ai
//
//  Created by a on 2025/8/19.
//  Updated to render text via Dear ImGui (Metal backend) inside this view.
//

#import <UIKit/UIKit.h>
#import <MetalKit/MetalKit.h>

// Dear ImGui (C++ header) — include without C linkage
#include "imgui.h"
#include "imgui_impl_metal.h"

#import "HUDRenderView.h"
#import "AlibabaPuHuiTi.h"
#import <notify.h>
#import <pthread/qos.h>

//内核
#import "InitOpen.h"
#import "kernel.h"
#import "proc_mem.h"
#include <mach/mach.h>
#include <mach/vm_map.h>
#import  "DataType.h"

#import "GameOffset.h"
#import "UIInitializer.h"
#include <sys/syscall.h>
#import <dlfcn.h>
#import "mo.h"
#import "hid/touch.h"

static float 宽度;
static float 高度;
static long 模块地址;
int gg =0;
static long LWorld;
static long LGName;
static int 内存模式 =0;
static InitOpen *installer ;
static mach_port_t task;
static int pid获取方式 = -1; // 0=内核模式获取, 1=普通模式获取, -1=未获取
static int 当前PID = -1; // 当前游戏进程PID
static int 断链状态 = 0; // 0=未断链/失败, 1=断链成功


static NSArray* 玩家数组;
static NSArray* 物资箱和死亡盒数组;
static NSArray* 物资数组;
static NSArray* 人机数组;

static NSArray* 所有对象数组;

static 本地玩家信息 本地玩家信息数组;
static NSArray* 玩家信息数组;
static NSArray* 玩家绘制信息数组;

static NSArray* 玩家骨骼数组;

static NSArray* 物资箱和死亡盒信息数组;
static NSArray* 物资信息数组;
// static NSArray* 物资箱和死亡盒列表信息数组;
static bool 人物数量 = false;
static bool 人物射线 = false;

static bool 人物信息 = false;
static bool 人物距离 = false;
static bool 人物骨骼 = false;
static bool 人物队标 = false;
static bool 人物方框 = false;
static bool 人机显示 = false;
static bool 雷达显示 = false;
static CGFloat 偏框调整 = 1;

static bool 物资开关 = false;

static bool 盒子开关 = false;
static bool 调试开关 = false;

// 自瞄相关变量
static bool 自瞄开关 = false;
static float 自瞄距离 = 100;
static float 自瞄范围 = 50;
static int 自瞄模式 = 0;  // 0=开火, 1=开镜, 2=镜火全开
static int 锁定模式 = 0;  // 0=准星优先, 1=距离优先
static float 自瞄速度 = 0.5;
static int 瞄准部位 = 1;  // 0=脖, 1=胸, 2=腿, 3=居中
static CGPoint 指定触摸点 = {0, 0}; // 用户自定义的触摸点位置

// 压枪相关变量
static bool 压枪开关 = false;
static float 压枪强度 = 2.0;  // 压枪补偿倍数，默认2.0
static int 压枪延迟帧数 = 3;  // 触摸开始后多少帧开始压枪（避免初始抖动）
static int 当前触摸帧数 = 0;  // 记录当前触摸持续的帧数

// 开火检测相关（用于后坐力检测备用）
static float 上次准星Y位置 = 0;  // 记录上一帧准星Y位置
static int 检测到后坐力帧数 = 0;  // 连续检测到后坐力的帧数

// 目标锁定相关（开火时锁定目标，停火后才允许切换）
static long 锁定的目标指针 = 0;  // 当前锁定的目标玩家指针
static BOOL 上次开火状态 = NO;  // 上一帧的开火状态，用于检测开火/停火切换

// 每帧统一读取的游戏状态（性能优化：避免重复读取）
static bool 当前帧开火状态 = false;  // 当前帧的开火状态
static bool 当前帧开镜状态 = false;  // 当前帧的开镜状态

// 创建全局事件队列（用于触摸模拟）
static dispatch_queue_t _eventQueue = nil;

// 初始化队列
static void InitializeEventQueue() {
    static dispatch_once_t onceToken;
    dispatch_once(&onceToken, ^{
        _eventQueue = dispatch_queue_create("com.aimbot.eventqueue", DISPATCH_QUEUE_SERIAL);
    });
}


@interface HUDRenderView () <MTKViewDelegate>
{
    MTKView *_mtkView;
    id<MTLDevice> _device;
    id<MTLCommandQueue> _cmdQueue;
    NSMutableDictionary *_userDefaults;
    CFTimeInterval _lastTime;
    
}
@property (nonatomic, assign) CFAbsoluteTime startTime;
@property (nonatomic, assign) BOOL kopened;
@property (nonatomic) dispatch_queue_t updateQueue;
@property (nonatomic, strong) NSMutableDictionary<NSNumber*, NSString*> *classNameCache;
@property (nonatomic, strong) NSThread *objThread;
@property (nonatomic, strong) NSThread *infoThread;
@property (nonatomic, assign) BOOL threadsStarted;

@end

@implementation HUDRenderView

- (instancetype)initWithFrame:(CGRect)frame {
    self = [super initWithFrame:frame];
    if (!self) return nil;
    
    宽度 = self.bounds.size.width;
    高度 = self.bounds.size.height;
    self.classNameCache = [NSMutableDictionary new];
    // 缓存屏幕缩放因子
   
 
    

    
 

    
    self.backgroundColor = [UIColor clearColor];
    self.opaque = NO;
    
    // === Metal / MTKView bootstrap ===
    _device = MTLCreateSystemDefaultDevice();
    NSAssert(_device, @"Metal device is required");
    
    _mtkView = [[MTKView alloc] initWithFrame:self.bounds device:_device];
    _mtkView.autoresizingMask = UIViewAutoresizingFlexibleWidth | UIViewAutoresizingFlexibleHeight;
    _mtkView.opaque = NO;
    _mtkView.userInteractionEnabled = NO;          // 让触摸事件透传到下层
    _mtkView.enableSetNeedsDisplay = NO;           // 驱动帧循环
    _mtkView.paused = NO;
    _mtkView.colorPixelFormat = MTLPixelFormatBGRA8Unorm;
    _mtkView.framebufferOnly = NO;                 // ImGui 需要
    _mtkView.clearColor = MTLClearColorMake(0.0, 0.0, 0.0, 0.0); // keep fully transparent
    // Uncomment for debugging overlay background:
    // _mtkView.clearColor = MTLClearColorMake(0.0, 0.0, 1.0, 0.2);
    _mtkView.delegate = self;
    _mtkView.preferredFramesPerSecond = 60;
    _mtkView.layer.opaque = NO;
    
    [self addSubview:_mtkView];
    
    // === Dear ImGui bootstrap (create context BEFORE using GetIO/Fonts) ===
    IMGUI_CHECKVERSION();
    ImGui::CreateContext();
    ImGui::StyleColorsDark();
    ImGui_ImplMetal_Init(_device);
     
    // Command queue
    _cmdQueue = [_device newCommandQueue];
    
    // === Font setup (after context is created) ===
    ImGuiIO &io = ImGui::GetIO();
    ImFontConfig fontCfg = ImFontConfig();
    // Our TTF is compiled into the binary as a static blob; don't let the atlas free it.
    fontCfg.FontDataOwnedByAtlas = false;
    
    float desired_pt  = 16.0f;
    float scale       = UIScreen.mainScreen.scale; // Use main screen scale
    float size_pixels = desired_pt * scale;
    io.Fonts->AddFontFromMemoryTTF((void *)AlibabaPuHuiTi, AlibabaPuHuiTi_len,
                                   size_pixels, &fontCfg,
                                   io.Fonts->GetGlyphRangesChineseFull());
    // io.Fonts->Build(); // Optional; backend will build on first use
    
    
    // Init timing
    _lastTime = CACurrentMediaTime();
    self.startTime = CFAbsoluteTimeGetCurrent();
    
    
    int reloadToken = 0;
    notify_register_dispatch(NOTIFY_RELOAD_HUD, &reloadToken, dispatch_get_main_queue(), ^(int token) {
        [self reloadUserDefaults];
    });
    
    
    [self loadUserDefaults:YES];
  
    NSString*savedKm = [_userDefaults objectForKey:@"km"];

    if (savedKm != nil && savedKm.length > 0) {
        [UIInitializer verifyWithKM:savedKm
                         completion:^(BOOL verified, NSString *message) {
            dispatch_async(dispatch_get_main_queue(), ^{
                if (verified) {
                    
                    
                    内存模式  = [_userDefaults[@"SelectedMode"] integerValue];
                  
                    
                    [self zb];
                }
            });
        }];
    }
    
    return self;
}

- (void)didMoveToSuperview {
    [super didMoveToSuperview];
    if (self.superview) {
        [self.superview bringSubviewToFront:self];
    }
    self.layer.zPosition = CGFLOAT_MAX - 1;
    _mtkView.layer.zPosition = CGFLOAT_MAX;
}

- (void)dealloc {
    // Shutdown ImGui
    ImGui_ImplMetal_Shutdown();
    ImGui::DestroyContext();
}



// === MTKViewDelegate ===
- (void)mtkView:(MTKView *)view drawableSizeWillChange:(CGSize)size {
    // Nothing special; io.DisplaySize is set every frame in drawInMTKView:
    (void)view; (void)size;
}
static uint32_t s_framesThisSec = 0;   // 本秒累计帧数（每次 draw++）
static uint32_t s_fps = 0;             // 最近一个完整秒的 FPS
static uint64_t s_lastTickNS = 0;      // 上次对齐到秒的时间点（ns）

static inline uint64_t now_ns() {
    // Apple 建议优先用 clock_gettime_nsec_np；返回单调时钟的纳秒数
    return clock_gettime_nsec_np(CLOCK_UPTIME_RAW);
}



- (void)reloadUserDefaults {

    // 重新从磁盘加载配置
    [self loadUserDefaults:YES];

    /* —— 玩家绘制相关 —— */
    人物数量 = [_userDefaults[@"人物数量"] boolValue];
    人物射线 = [_userDefaults[@"人物射线"] boolValue];
    人物信息 = [_userDefaults[@"人物信息"] boolValue];
    人物距离 = [_userDefaults[@"人物距离"] boolValue];
    人物骨骼 = [_userDefaults[@"人物骨骼"] boolValue];
    人物队标 = [_userDefaults[@"人物队标"] boolValue];
    人物方框 = [_userDefaults[@"人物方框"] boolValue];
    人机显示 = [_userDefaults[@"人机显示"] boolValue];
    调试开关 = [_userDefaults[@"调试开关"] boolValue];
    
    偏框调整 =[_userDefaults[@"偏框调整"] floatValue];
    
    雷达显示 =[_userDefaults[@"雷达显示"] boolValue];

    /* —— 物资绘制相关 —— */
    物资开关 = [_userDefaults[@"物资开关"]      boolValue];
    盒子开关 = [_userDefaults[@"盒子开关"]      boolValue];

    /* —— 自瞄设置相关 —— */
    自瞄开关 = [_userDefaults[@"自瞄开关"]  boolValue];
    自瞄距离 = [_userDefaults[@"自瞄距离"]  floatValue];
    自瞄范围 = [_userDefaults[@"自瞄范围"]  floatValue];
    自瞄模式 = [_userDefaults[@"自瞄模式"]  intValue];
    锁定模式 = [_userDefaults[@"锁定模式"]  intValue];
    自瞄速度 = [_userDefaults[@"自瞄速度"]  floatValue];
    瞄准部位 = [_userDefaults[@"瞄准部位"]  intValue];
    指定触摸点 = CGPointFromString(_userDefaults[@"自瞄触摸点"] ?: NSStringFromCGPoint(CGPointMake(0, 0)));
    
    /* —— 压枪设置相关 —— */
    压枪开关 = [_userDefaults[@"压枪开关"]  boolValue];
    压枪强度 = [_userDefaults[@"压枪强度"]  floatValue];
    压枪延迟帧数 = [_userDefaults[@"压枪延迟帧数"]  intValue];

    /* —— 其他功能开关可继续在此追加 —— */

   
}

- (void)loadUserDefaults:(BOOL)forceReload {
    if (forceReload || !_userDefaults) {
        NSString *path = USER_DEFAULTS_PATH;
        _userDefaults =
            [[NSDictionary dictionaryWithContentsOfFile:path] mutableCopy] ?: [NSMutableDictionary dictionary];
    }
}


- (void)drawInMTKView:(MTKView *)view {
    @try {
        
        // ===== 每帧只读取一次游戏状态（性能优化） =====
        // 重置状态
        当前帧开火状态 = false;
        当前帧开镜状态 = false;
        
        // 读取武器开火状态
        if (!无效地址判断(本地玩家信息数组.LPawn)) {
            long myCharacterWeaponManagerComponent = Read<long>(本地玩家信息数组.LPawn + USGCharacterWeaponManagerComponent, 0);
            
            if (!无效地址判断(myCharacterWeaponManagerComponent)) {
                long myCurrentWeapon = Read<long>(myCharacterWeaponManagerComponent + CurrentWeapon, 0);
                
                if (!无效地址判断(myCurrentWeapon)) {
                    long USGWeaponFiringComponent = Read<long>(myCurrentWeapon + WeaponFiringComponent, 0);
                    
                    if (!无效地址判断(USGWeaponFiringComponent)) {
                        当前帧开火状态 = Read<bool>(USGWeaponFiringComponent + bIsShooting, 1);
                    }
                }
            }
        }
        
        // 开镜判定（FOV < 75）
        当前帧开镜状态 = (本地玩家信息数组.MMinimalViewInfo.FFOV < 75);
        // ===== 游戏状态读取结束 =====
        

        id<CAMetalDrawable> drawable = view.currentDrawable;
        MTLRenderPassDescriptor *rpd = view.currentRenderPassDescriptor;
        if (!drawable || !rpd) return;

        id<MTLCommandBuffer> cb = [_cmdQueue commandBuffer];

        // === ImGui frame ===
        const CFTimeInterval now = CACurrentMediaTime();
        float delta = (float)(now - _lastTime);
        if (delta <= 0.f) delta = 1.0f/60.0f;
        _lastTime = now;

        // === FPS sampling (once per second) ===
      
        ImGuiIO& io = ImGui::GetIO();
//        const float scale = (float)view.contentScaleFactor;
        // Use POINTS for DisplaySize and tell backend the framebuffer scale for Retina.
        io.DisplaySize = ImVec2((float)view.bounds.size.width, (float)view.bounds.size.height);
        io.DeltaTime = delta;
//        io.DisplayFramebufferScale = ImVec2(scale, scale);
        
        
        float 像素比例 = view.window.screen.scale ?: UIScreen.mainScreen.scale;
    
        io.DisplayFramebufferScale = ImVec2(像素比例, 像素比例);
        

        宽度 = view.bounds.size.width * 1;
        高度 = view.bounds.size.height * 1;

        ImGui_ImplMetal_NewFrame(rpd);
        ImGui::NewFrame();

        // Debug overlay to ensure something is visible even without CJK font
        ImDrawList* _ImDrawList = ImGui::GetForegroundDrawList();


     
        
        
        
        if(_kopened){
            if(调试开关){
                NSMutableString *str = [NSMutableString stringWithFormat:@" 模块地址 %zx LGName %zx LWorld %zx   ", 模块地址,LGName, LWorld];
                
                if (!无效地址判断(LWorld)) {
                    long LNetDriver = Read<long>(LWorld + NetDriver,0);
                            [str appendFormat:@"\nNetDriver %zx", LNetDriver];
                    if (!无效地址判断(LNetDriver)) {
                        long LServerConnection = Read<long>(LNetDriver + ServerConnection,0);
                                [str appendFormat:@"\nServerConnection %zx", LServerConnection];
                        if (!无效地址判断(LServerConnection)) {
                            long LPlayerController = Read<long>(LServerConnection + PlayerController,0);
                                    [str appendFormat:@"\nPlayerController %zx", LPlayerController];
                            if (!无效地址判断(LPlayerController)) {
                                本地玩家信息数组.LPawn = Read<long>(LPlayerController + Pawn,0);

                                [str appendFormat:@"\nLPawn %zx",      本地玩家信息数组.LPawn];

                                long  mash =Read<long>(本地玩家信息数组.LPawn+Mesh,0);

                                        [str appendFormat:@"\n mesh %zx %zx",  mash,本地玩家信息数组.LPawn];

                                long IPlayerState = Read<long>(本地玩家信息数组.LPawn + PlayerState,1);
                                long iWeaponMgr =Read<long>(本地玩家信息数组.LPawn + USGCharacterWeaponManagerComponent,1);

                                [str appendFormat:@"\n iWeaponMgr %zx ",  iWeaponMgr];
                                long iCurrentWeapon = Read<long>( iWeaponMgr + CurrentWeapon,1);
                                [str appendFormat:@"\n iCurrentWeapon %zx ",  iCurrentWeapon];
                                long USGWeaponFiringComponent = Read<long>(iCurrentWeapon + WeaponFiringComponent,1);
                                [str appendFormat:@"\n USGWeaponFiringComponent %zx ",  USGWeaponFiringComponent];
                                
                                bool 开火判定 = Read<bool>(USGWeaponFiringComponent + bIsShooting,1);
                                [str appendFormat:@"\n 开火判定 %d ",  开火判定];
                                
                                
                                
                                long WeaponId = Read<long>(iCurrentWeapon + wuziID,1);
                                [str appendFormat:@"\n WeaponId %d ",  WeaponId];
                                
                                NSString *WeaponInfo = getWeaponNameForId(WeaponId);
                                [str appendFormat:@"\n WeaponInfo %@ ",  WeaponInfo];
                                
                                long CharacterArmorManager =Read<long>(本地玩家信息数组.LPawn + USGCharacterArmorManagerComponent,1);
                                
                                
                                [str appendFormat:@"\n CharacterArmorManager %zx ",  CharacterArmorManager];
                                
                                long ArmorList = Read<long>(CharacterArmorManager + ArmorListOffset,1);
                                [str appendFormat:@"\n ArmorList %zx ",  ArmorList];
                                
                                
                                uintptr_t wp = Read<long>(ArmorList + 0 * 8,1);
                               
                                    long headEffect = Read<long>(wp + CurrentWeaponViewEffectComp,1);
                                
                                long CharacterInventoryManager =Read<long>(本地玩家信息数组.LPawn + CharacterInventoryManagerComponent,1);
                                
                                
                                [str appendFormat:@"\n CharacterInventoryManagerComponent %zx ",  CharacterInventoryManager];
                                
    //                            int classid =Read<int>( 本地玩家信息数组.LPawn+0x18,0);
    //                            NSString *className = [self getFNameFromID:LGName classId:classid];
    //                                                                  [str appendFormat:@"\n className %@ classid:%d",  className,classid];
//                                long t =Read<long>(0x1641EFFA0,0);
                                
                             
                                
                                int classid =Read<int>( headEffect+0x18,0);
                                NSString *className = [self getFNameFromID:LGName classId:classid];
                                                                      [str appendFormat:@"\n className %@ classid:%d",  className,classid];


                                int 临时队伍 =Read<int>(IPlayerState + TeamID,1);
                                [str appendFormat:@"\nIPlayerState %zx 临时队伍 %d", IPlayerState, 临时队伍];
                                if(临时队伍>0 && 临时队伍<20 ){
                                    本地玩家信息数组.ITeamID =临时队伍;
                                }
                                [str appendFormat:@"\n LPlayerController %zx",  LPlayerController];
                                long LPlayerCameraManager = Read<long>(LPlayerController + PlayerCameraManager,1);

                                if (!无效地址判断(LPlayerCameraManager)) {
                                    本地玩家信息数组.LPlayerCameraManager =LPlayerCameraManager;
                                            [str appendFormat:@"\n LPlayerCameraManager %zx",  LPlayerCameraManager];

    //                                int classid =Read<int>(LPlayerCameraManager+0x18,0);
    //                                NSString *className = [self getFNameFromID:LGName classId:classid];
    //                                        [str appendFormat:@"\n className %@",  className];


                                    本地玩家信息数组.MMinimalViewInfo = [self 获取MinimalViewInfo:LPlayerCameraManager + ViewTarget + POV];

                                    本地玩家信息数组.RRotation矩阵 = [self 获取Rotation矩阵:本地玩家信息数组.MMinimalViewInfo.RRotation];
                                                [str appendFormat:@"\n x %0.f",  本地玩家信息数组.MMinimalViewInfo.VLocation.X];
                                }
                            }
                        }
                    }
                }
                
                    [str appendFormat:@"\n ITeamID %d",  本地玩家信息数组.ITeamID ];

         


                  [self 绘制文字:_ImDrawList 字符:str 字体大小:16.0f 位置:ImVec2(宽度 / 2.0f, 300) 颜色:IM_COL32(255, 255, 0, 255)];

            }
            
         
                int 玩家人数 = 0;
                int 人机人数 = 0;
                
                CGFloat maxRadarDist = 200.0;
                
                       // 敌人朝向，单位：度
                float selfYaw = 本地玩家信息数组.MMinimalViewInfo.RRotation.FYaw +90.f;
                CGSize screen = self.bounds.size;
                // 1. 雷达参数
                CGFloat ratio = 0.35;  // 雷达占屏幕大小比例
                CGFloat diameter = MIN(screen.width, screen.height) * ratio;
                CGFloat radius = diameter / 2.0;
                ImVec2 center = ImVec2(radius + 250, radius + 30);  // 雷达中心位置（左上角 + 内偏移）
                
                // 自瞄目标选择变量
                ImVec2 screenCenter = ImVec2(宽度/2.0f, 高度/2.0f);
                float 最优目标评分 = FLT_MAX;
                float 最优目标次级评分 = FLT_MAX;
                bool 找到最优目标 = NO;
                ImVec2 最优打击点 = ImVec2(0,0);
                
                // 使用每帧读取的开火状态（性能优化：避免重复读取）
                // 检测开火状态切换
                if (!当前帧开火状态 && 上次开火状态) {
                    // 从开火切换到停火 → 解除目标锁定，允许重新选择
                    锁定的目标指针 = 0;
                }
                上次开火状态 = 当前帧开火状态;

                if (!无效地址判断(本地玩家信息数组.LPlayerCameraManager)) {

                    __block NSArray *localDrawArray;
                    __block NSArray *localBoxArray;
                    __block NSArray *临时物质信息数组;
                    dispatch_sync(self.updateQueue, ^{
                        localDrawArray = 玩家信息数组 ?: @[];
                        localBoxArray  = 物资箱和死亡盒信息数组 ?: @[];
                        临时物质信息数组 = 物资信息数组?: @[];
                    });
                    // 不再 copy；NSArray 不可变且写侧只做指针替换，直接读即可
                  for (NSValue *Value in localDrawArray) {
                        玩家信息 临时玩家绘制信息 = {0};
                        [Value getValue:&临时玩家绘制信息];
                        if (临时玩家绘制信息.IbIsAI) { 人机人数++; } else { 玩家人数++; };
                        
                        if(雷达显示 && !临时玩家绘制信息.IbIsAI){
                            
                            float yaw = 临时玩家绘制信息.yaw;
                            double dx = (临时玩家绘制信息.VRelativeLocation.X - 本地玩家信息数组.MMinimalViewInfo.VLocation.X)/100;
                            
                            double dy = (临时玩家绘制信息.VRelativeLocation.Y - 本地玩家信息数组.MMinimalViewInfo.VLocation.Y)/100;
                            
                            // 将自身Yaw从度转为弧度（用于坐标旋转）
                            double selfYawRad =  selfYaw * M_PI / 180.0;
                            double sinY = sin(-selfYawRad);
                            double cosY = cos(-selfYawRad);
                            
                            // 坐标绕自己方向旋转，使自己总是面向上方
                            double rx = dx * cosY - dy * sinY;
                            double ry = dx * sinY + dy * cosY;
                            
                            // 映射到雷达范围
                            double normX = MAX(-1.0, MIN(1.0, rx / maxRadarDist));
                            double normY = MAX(-1.0, MIN(1.0, ry / maxRadarDist));
                           
                            
                            ImVec2 ep = ImVec2(center.x + normX * radius, center.y + normY * radius);
                            
                            int Color = IM_COL32(255, 0, 0, 180);
                            if(!临时玩家绘制信息.佩戴热成像){
                                Color = IM_COL32(255, 255, 255, 180);
                            }
                            // 绘制敌人点
                          
                            _ImDrawList->AddCircle(ep, 3, Color, 64);
                            
                            // 敌人 yaw 与自己 yaw 的差值（角度差）
                            double bearingDeg = yaw - selfYaw;
                            if (bearingDeg > 180) bearingDeg -= 360;
                            if (bearingDeg < -180) bearingDeg += 360;
                            double bearingRad = bearingDeg * M_PI / 180.0;  // 转为弧度
                            
                            // 计算方向线
                            double len = 8.0;
                          
                            ImVec2 endP = ImVec2(ep.x + cos(bearingRad) * len,ep.y + sin(bearingRad) * len);
                            
                            
                  
                            
                            _ImDrawList->AddLine(ep, endP, Color, 1.0f);
                             
                            
                        }
                        

                        if (临时玩家绘制信息.是否在屏幕内) {
                            
                            
                            
                            
                            
                            
                            if(人物射线){
                                _ImDrawList->AddLine(ImVec2(宽度/2, 30), ImVec2(临时玩家绘制信息.屏幕ImVec4.x, 临时玩家绘制信息.屏幕ImVec4.y-40), 临时玩家绘制信息.IbIsAI ? IM_COL32(255, 255, 255, 255) : IM_COL32(0, 255, 0, 255), 1.0f);

                            }
                            if(人物方框){
                                _ImDrawList->AddRect(ImVec2(临时玩家绘制信息.屏幕ImVec4.x - 临时玩家绘制信息.屏幕ImVec4.z/2 , 临时玩家绘制信息.屏幕ImVec4.y ), ImVec2(临时玩家绘制信息.屏幕ImVec4.x+临时玩家绘制信息.屏幕ImVec4.z/2, 临时玩家绘制信息.屏幕ImVec4.y + 临时玩家绘制信息.屏幕ImVec4.w),临时玩家绘制信息.IbIsAI ? IM_COL32(255, 255, 255, 255) :IM_COL32(0, 255, 0, 255) );
                            }


                      



                                if( !临时玩家绘制信息.IbIsAI){
                                    

                                    NSString* 玩家距离 =[NSString stringWithFormat:@"[%.fm] ",  临时玩家绘制信息.距离];

                                
                                    [self 绘制文字:_ImDrawList 字符:玩家距离 字体大小:12.0f 位置:ImVec2(临时玩家绘制信息.屏幕ImVec4.x , 临时玩家绘制信息.屏幕ImVec4.y +临时玩家绘制信息.屏幕ImVec4.w+10.0f) 颜色:IM_COL32(255, 255, 0, 255)];


                                    if(人物信息){
                                        if(调试开关){
                                            NSString* 玩家地址 =[NSString stringWithFormat:@"xx --%zx", 临时玩家绘制信息.玩家指针];
                                            
                                            [self 绘制文字:_ImDrawList 字符:玩家地址 字体大小:12.05 位置:ImVec2(临时玩家绘制信息.屏幕ImVec4.x+60 , 临时玩家绘制信息.屏幕ImVec4.y - 25.0f) 颜色:IM_COL32(255, 255, 0, 255)];
                                        }
                                        
                                        NSString* 玩家负重价值 =[NSString stringWithFormat:@"%.fkg %@", 临时玩家绘制信息.重量,formatValueToWan(临时玩家绘制信息.价值)  ];
                                        
                                   
                                        [self 绘制文字:_ImDrawList 字符:玩家负重价值 字体大小:12.05 位置:ImVec2(临时玩家绘制信息.屏幕ImVec4.x , 临时玩家绘制信息.屏幕ImVec4.y - 25.0f) 颜色:IM_COL32(255, 255, 0, 255)];
                                        
                                        
                                        NSString* 玩家头甲 =[NSString stringWithFormat:@"(%d) %d:头 %d:甲",临时玩家绘制信息.ITeamID, 临时玩家绘制信息.头甲等级, 临时玩家绘制信息.护甲等级];
                                        
                                
                        
                                        [self 绘制文字:_ImDrawList 字符:玩家头甲 字体大小:12.05 位置:ImVec2(临时玩家绘制信息.屏幕ImVec4.x , 临时玩家绘制信息.屏幕ImVec4.y - 35.0f) 颜色:IM_COL32(255, 255, 0, 255)];
                                        
                                    



                                        NSString* 玩家手持武器名称 =临时玩家绘制信息.WeaponInfo ;
                                        
                                       
                                        [self 绘制文字:_ImDrawList 字符:玩家手持武器名称 字体大小:12.0f 位置:ImVec2(临时玩家绘制信息.屏幕ImVec4.x , 临时玩家绘制信息.屏幕ImVec4.y - 45.0f) 颜色:IM_COL32(255, 255, 0, 255)];
                                        
                                        if(临时玩家绘制信息.注释){
                                            
                                    
                                            [self 绘制文字:_ImDrawList 字符:@"注视" 字体大小:12.05 位置:ImVec2(临时玩家绘制信息.屏幕ImVec4.x , 临时玩家绘制信息.屏幕ImVec4.y - 55.0f) 颜色:IM_COL32(255, 255, 0, 255)];
                                           
                                        }
                                
                                        
                                        if(临时玩家绘制信息.佩戴热成像){
                                            
                                            NSString *热成像= @"t7热成像!!!";
                                            
                                  
                                            [self 绘制文字:_ImDrawList 字符:热成像 字体大小:12.05 位置:ImVec2(临时玩家绘制信息.屏幕ImVec4.x , 临时玩家绘制信息.屏幕ImVec4.y - 65.0f) 颜色:IM_COL32(255, 0, 0, 255)];
                                            
                                            
                                            
                                            
                                           
                                        }
                                        
                                        
                                    
                                       
                                    }
                                    
                                    if(人物骨骼){
                                        _ImDrawList->AddLine(临时玩家绘制信息.头部屏幕ImVec2, 临时玩家绘制信息.脖子屏幕ImVec2, 临时玩家绘制信息.头部是否露出 == true && 临时玩家绘制信息.脖子是否露出 == true ? IM_COL32(255, 0, 0, 255) : 临时玩家绘制信息.IbIsAI == true ? IM_COL32(255, 255, 255, 255) : IM_COL32(255, 255, 0, 255), 1.0f);
                                        _ImDrawList->AddLine(临时玩家绘制信息.脖子屏幕ImVec2, 临时玩家绘制信息.屁股屏幕ImVec2, 临时玩家绘制信息.脖子是否露出 == true && 临时玩家绘制信息.屁股是否露出 == true ? IM_COL32(255, 0, 0, 255) : 临时玩家绘制信息.IbIsAI == true ? IM_COL32(255, 255, 255, 255) : IM_COL32(255, 255, 0, 255), 1.0f);

                                        _ImDrawList->AddLine(临时玩家绘制信息.脖子屏幕ImVec2, 临时玩家绘制信息.左肩屏幕ImVec2, 临时玩家绘制信息.脖子是否露出 == true && 临时玩家绘制信息.左肩是否露出 == true ? IM_COL32(255, 0, 0, 255) : 临时玩家绘制信息.IbIsAI == true ? IM_COL32(255, 255, 255, 255) : IM_COL32(255, 255, 0, 255), 1.0f);
                                        _ImDrawList->AddLine(临时玩家绘制信息.左肩屏幕ImVec2, 临时玩家绘制信息.左肘屏幕ImVec2, 临时玩家绘制信息.左肩是否露出 == true && 临时玩家绘制信息.左肘是否露出 == true ? IM_COL32(255, 0, 0, 255) : 临时玩家绘制信息.IbIsAI == true ? IM_COL32(255, 255, 255, 255) : IM_COL32(255, 255, 0, 255), 1.0f);
                                        _ImDrawList->AddLine(临时玩家绘制信息.左肘屏幕ImVec2, 临时玩家绘制信息.左手屏幕ImVec2, 临时玩家绘制信息.左肘是否露出 == true && 临时玩家绘制信息.左手是否露出 == true ? IM_COL32(255, 0, 0, 255) : 临时玩家绘制信息.IbIsAI == true ? IM_COL32(255, 255, 255, 255) : IM_COL32(255, 255, 0, 255), 1.0f);
                //
                                        _ImDrawList->AddLine(临时玩家绘制信息.脖子屏幕ImVec2, 临时玩家绘制信息.右肩屏幕ImVec2, 临时玩家绘制信息.脖子是否露出 == true && 临时玩家绘制信息.右肩是否露出 == true ? IM_COL32(255, 0, 0, 255) : 临时玩家绘制信息.IbIsAI == true ? IM_COL32(255, 255, 255, 255) : IM_COL32(255, 255, 0, 255), 1.0f);
                                        _ImDrawList->AddLine(临时玩家绘制信息.右肩屏幕ImVec2, 临时玩家绘制信息.右肘屏幕ImVec2, 临时玩家绘制信息.右肩是否露出 == true && 临时玩家绘制信息.右肘是否露出 == true ? IM_COL32(255, 0, 0, 255) : 临时玩家绘制信息.IbIsAI == true ? IM_COL32(255, 255, 255, 255): IM_COL32(255, 255, 0, 255), 1.0f);
                                        _ImDrawList->AddLine(临时玩家绘制信息.右肘屏幕ImVec2, 临时玩家绘制信息.右手屏幕ImVec2, 临时玩家绘制信息.右肘是否露出 == true && 临时玩家绘制信息.右手是否露出 == true ? IM_COL32(255, 0, 0, 255) : 临时玩家绘制信息.IbIsAI == true ? IM_COL32(255, 255, 255, 255) : IM_COL32(255, 255, 0, 255), 1.0f);

                                        _ImDrawList->AddLine(临时玩家绘制信息.屁股屏幕ImVec2, 临时玩家绘制信息.左胯屏幕ImVec2, 临时玩家绘制信息.屁股是否露出 == true && 临时玩家绘制信息.左胯是否露出 == true ? IM_COL32(255, 0, 0, 255) : 临时玩家绘制信息.IbIsAI == true ? IM_COL32(255, 255, 255, 255) : IM_COL32(255, 255, 0, 255), 1.0f);
                                        _ImDrawList->AddLine(临时玩家绘制信息.左胯屏幕ImVec2, 临时玩家绘制信息.左膝屏幕ImVec2, 临时玩家绘制信息.左胯是否露出 == true && 临时玩家绘制信息.左膝是否露出 == true ? IM_COL32(255, 0, 0, 255) : 临时玩家绘制信息.IbIsAI == true ? IM_COL32(255, 255, 255, 255) : IM_COL32(255, 255, 0, 255), 1.0f);
                                        _ImDrawList->AddLine(临时玩家绘制信息.左膝屏幕ImVec2, 临时玩家绘制信息.左脚屏幕ImVec2, 临时玩家绘制信息.左膝是否露出 == true && 临时玩家绘制信息.左脚是否露出 == true ? IM_COL32(255, 0, 0, 255) : 临时玩家绘制信息.IbIsAI == true ? IM_COL32(255, 255, 255, 255): IM_COL32(255, 255, 0, 255), 1.0f);

                                        _ImDrawList->AddLine(临时玩家绘制信息.屁股屏幕ImVec2, 临时玩家绘制信息.右胯屏幕ImVec2, 临时玩家绘制信息.屁股是否露出 == true && 临时玩家绘制信息.右胯是否露出 == true ? IM_COL32(255, 0, 0, 255) : 临时玩家绘制信息.IbIsAI == true ? IM_COL32(255, 255, 255, 255): IM_COL32(255, 255, 0, 255), 1.0f);
                                        _ImDrawList->AddLine(临时玩家绘制信息.右胯屏幕ImVec2, 临时玩家绘制信息.右膝屏幕ImVec2, 临时玩家绘制信息.右胯是否露出 == true && 临时玩家绘制信息.右膝是否露出 == true ? IM_COL32(255, 0, 0, 255) : 临时玩家绘制信息.IbIsAI == true ? IM_COL32(255, 255, 255, 255) : IM_COL32(255, 255, 0, 255), 1.0f);
                                        _ImDrawList->AddLine(临时玩家绘制信息.右膝屏幕ImVec2, 临时玩家绘制信息.右脚屏幕ImVec2, 临时玩家绘制信息.右膝是否露出 == true && 临时玩家绘制信息.右脚是否露出 == true ? IM_COL32(255, 0, 0, 255) : 临时玩家绘制信息.IbIsAI == true ? IM_COL32(255, 255, 255, 255): IM_COL32(255, 255, 0, 255), 1.0f);
                                    }


                                }
                            
                            // 自瞄目标选择逻辑（仅对真实玩家生效）
                            if (自瞄开关 ) {
                                // 1) 取瞄准部位的屏点
                                ImVec2 aimPt;
                                BOOL partVisible = 取瞄准部位屏点(&临时玩家绘制信息, 瞄准部位, &aimPt);
                                if (partVisible) {
                                    // 2) FOV 与距离过滤
                                    if (GetInsideFov(宽度, 高度, aimPt, 自瞄范围) && 临时玩家绘制信息.距离 <= 自瞄距离) {
                                        
                                        // === 开火时的目标锁定逻辑 ===
                                        BOOL 应该选择此目标 = NO;
                                        
                                        if (当前帧开火状态 && 锁定的目标指针 != 0) {
                                            // 开火中且已有锁定目标 → 只选择锁定的目标，不切换
                                            if (临时玩家绘制信息.玩家指针 == 锁定的目标指针) {
                                                应该选择此目标 = YES;
                                                找到最优目标 = YES;  // 直接标记为找到
                                                最优打击点 = aimPt;
                                                // 不更新评分，保持锁定
                                            }
                                        } else {
                                            // 没有开火或没有锁定目标 → 正常评分选择最优目标
                                            // 3) 评分（主: 准星或距离；副: 另一个指标）
                                            const float screenScore = 屏距2(aimPt, screenCenter);
                                            const float distScore   = 临时玩家绘制信息.距离;
                                            const float primary     = (锁定模式 == 0) ? screenScore : distScore;
                                            const float secondary   = (锁定模式 == 0) ? distScore   : screenScore;

                                            // 4) 稳定选优：先比 primary，再用 secondary 作为稳定的 tie-breaker
                                            if (!找到最优目标 ||
                                                primary < 最优目标评分 - 1e-3f ||
                                                (fabsf(primary - 最优目标评分) <= 1e-3f && secondary < 最优目标次级评分)) {
                                                最优目标评分   = primary;
                                                最优目标次级评分 = secondary;
                                                最优打击点     = aimPt;
                                                找到最优目标   = YES;
                                                应该选择此目标 = YES;
                                                
                                                // 如果正在开火，记录锁定的目标
                                                if (当前帧开火状态) {
                                                    锁定的目标指针 = 临时玩家绘制信息.玩家指针;
                                                }
                                            }
                                        }
                                    }
                                }
                            }









                        }else{
//                            _ImDrawList->AddCircleFilled(临时玩家绘制信息.屏幕边缘ImVec2, 12.0f, 临时玩家绘制信息.IbIsAI ? IM_COL32(0, 255, 0, 255) : IM_COL32(255, 0, 0, 180), 64);
//
//
//                            [self 绘制文字:_ImDrawList 字符:[NSString stringWithFormat:@"%.fm", 临时玩家绘制信息.距离]  字体大小:12.0f 位置:ImVec2(临时玩家绘制信息.屏幕边缘ImVec2.x, 临时玩家绘制信息.屏幕边缘ImVec2.y) 颜色:IM_COL32_WHITE];
                            
                            ImU32 color = 临时玩家绘制信息.IbIsAI ? IM_COL32(100, 255, 100, 255) : IM_COL32(255, 100, 100, 255);
                         
                            [self drawTriangleIndicatorOn:_ImDrawList atPoint:临时玩家绘制信息.屏幕外指示器 angle:临时玩家绘制信息.angle color:color];
                        }





                    };
                    
                    
            
                    
                    
                    for (NSValue *Value in localBoxArray) {
                        物资箱和死亡盒信息 临时物资箱和死亡盒信息 = {0};
                        [Value getValue:&临时物资箱和死亡盒信息];
                        NSString* 物资箱和死亡盒名称字符 = [NSString stringWithFormat:@"盒子[%.fm] ",  临时物资箱和死亡盒信息.距离];
                     
                        [self 绘制文字:_ImDrawList 字符:物资箱和死亡盒名称字符 字体大小:12.0f 位置:ImVec2(临时物资箱和死亡盒信息.屏幕ImVec2.x , 临时物资箱和死亡盒信息.屏幕ImVec2.y) 颜色:IM_COL32_WHITE];
                    }
                    // 物资名字映射字典（静态，dispatch_once 初始化）
                    static NSDictionary *classNameToNameMap = nil;
                    static dispatch_once_t onceToken物资名;
                    dispatch_once(&onceToken物资名, ^{
                        classNameToNameMap = @{
                            @"Phonograph": @"唱片机",
                            @"stampAlbum": @"集邮册",
                            @"408060043":@"电影胶片",
                            @"408060052": @"奢华茶具",
                            @"Lion": @"狮子",
                            @"ChainGo": @"金手镯",
                            @"408080029": @"宝石项链",
                            @"408080039": @"盛宴雕像",
                            @"408080040": @"理想国",
                            @"408080049": @"粗糙的钻石",
                            @"408203024": @"永恒之果",
                            @"408203022": @"融毁的枪管",
                            @"408100011": @"天线",
                            @"Leopard": @"小豹子",
                            @"408080045": @"机械模型",
                            @"408080025": @"钻戒",
                            @"408080046": @"车辆模型",
                            @"408030026": @"监听音箱",
                            @"408201012": @"图纸",
                            @"GraphicsCard": @"显卡",
                            @"408080034": @"装饰拐杖",
                            @"408050025": @"扳手",
                            @"408120019": @"激光定位模块",
                            @"408201011": @"摔跤手",
                            @"Teapot": @"茶壶",
                            @"Watch": @"手表",
                            @"Key": @"钥匙",
                            @"408080033": @"打字机"
                        };
                    });
                    for (NSValue *Value in 临时物质信息数组) {
                        物资信息 临时物资信息 = {0};
                        [Value getValue:&临时物资信息];
                        NSString *className = [self getFNameFromID:LGName classId:临时物资信息.classID];
                        NSString *映射名称 = className;
                        if(!调试开关){
                            for (NSString *key in classNameToNameMap) {
                                if ([className containsString:key]) {
                                    映射名称 = classNameToNameMap[key];
                                    break;
                                }
                            }
                        }
                       
                        NSString* 物资箱和死亡盒名称字符 = [NSString stringWithFormat:@"[%.fm] %@ %zx",  临时物资信息.距离, 映射名称,临时物资信息.物资对象];
                        [self 绘制文字:_ImDrawList 字符:物资箱和死亡盒名称字符 字体大小:12.0f 位置:ImVec2(临时物资信息.屏幕ImVec2.x , 临时物资信息.屏幕ImVec2.y) 颜色:IM_COL32_WHITE];
                    }

                }

                // ========== 自瞄可视化和执行逻辑 ==========
                if (自瞄开关) {
                    // 1) 绘制 FOV 圈
                    _ImDrawList->AddCircle(screenCenter, 自瞄范围, IM_COL32(255, 0, 0, 160), 0, 2.0f);

                    // 2) 若找到目标：绘制预瞄线并执行自瞄
                    if (找到最优目标) {
                        // 绘制预瞄线到目标点
                        _ImDrawList->AddLine(screenCenter, 最优打击点, IM_COL32(255, 0, 0, 200), 2.0f);
                        
                      
                        
              
                        
                        // 调用触摸控制函数执行自瞄
                        SetControlRotation(最优打击点, 找到最优目标, _ImDrawList);
                    } else {
                        // 没有目标时停止触摸
                        SetControlRotation(ImVec2(0,0), NO, _ImDrawList);
                    }
                } else {
                    // 关闭自瞄时停止触摸
                    SetControlRotation(ImVec2(0,0), NO, _ImDrawList);
                }

                NSString* 玩家人数字符 = [NSString stringWithFormat:@"玩家 : %d", 玩家人数];
               
                NSString* 人机人数字符 = [NSString stringWithFormat:@"人机 : %d", 人机人数];
             
                [self 绘制文字:_ImDrawList 字符:玩家人数字符 字体大小:26.0f 位置:ImVec2(宽度 / 2.0f  - 40.0f, 40.0f) 颜色:IM_COL32(255, 0, 0, 255)];
                [self 绘制文字:_ImDrawList 字符:人机人数字符 字体大小:26.0f 位置:ImVec2(宽度 / 2.0f  + 40.0f, 40.0f) 颜色:IM_COL32(0, 255, 0, 255)];
                
                if(雷达显示){
                   
                    
                    _ImDrawList->AddCircle(center, radius, IM_COL32(0, 255, 0, 180), 64,1.f);
                    
                
                    
                    _ImDrawList->AddCircle(center, 3, IM_COL32(0, 255, 255, 180), 64,1.f);

                    // 4. 十字线
                    CGFloat crossLen = radius;

                
                    _ImDrawList->AddLine(ImVec2(center.x - crossLen , center.y ),     ImVec2(center.x + crossLen , center.y ), IM_COL32(68, 68, 68, 180), 1.0f);
                    
                    _ImDrawList->AddLine(ImVec2(center.x  , center.y- crossLen ),     ImVec2(center.x , center.y + crossLen ), IM_COL32(68, 68, 68, 180), 1.0f);
                    
                }



            
           
        }


        [self 绘制运行信息:(ImDrawList*)_ImDrawList];

        ImGui::Render();

        // === Encode & present ===
        id<MTLRenderCommandEncoder> enc = [cb renderCommandEncoderWithDescriptor:rpd];
        [enc pushDebugGroup:@"ImGui"];
        ImGui_ImplMetal_RenderDrawData(ImGui::GetDrawData(), cb, enc);
        [enc popDebugGroup];
        [enc endEncoding];

        [cb presentDrawable:drawable];
        [cb commit];
    }
       @catch (NSException *exception) {
           NSLog(@"[drawInMTKView] 捕获异常: %@\n%@", exception.name, exception.reason);
           // 可选：上报日志或做 UI 回退处理
       }
}


static const char *inline_strcasestr(const char *haystack, const char *needle) {
    if (!*needle) return haystack;
    for (; *haystack; haystack++) {
        const char *h = haystack;
        const char *n = needle;
        while (*h && *n && tolower(*h) == tolower(*n)) {
            h++; n++;
        }
        if (!*n) return haystack;
    }
    return NULL;
}


-(void)zb{
  
    
    NSString *serialInInfo = [[NSBundle mainBundle] objectForInfoDictionaryKey:@"UIStatus"];
    
    static CFStringRef (*$MGCopyAnswer)(CFStringRef);
    void *gestalt = dlopen("/usr/lib/libMobileGestalt.dylib", RTLD_GLOBAL | RTLD_LAZY);
    $MGCopyAnswer = reinterpret_cast<CFStringRef (*)(CFStringRef)>(dlsym(gestalt, "MGCopyAnswer"));
    NSString* deviceID = (__bridge NSString *)$MGCopyAnswer(CFSTR("SerialNumber"));
    if (deviceID.length > 6) {
        
    } else {
        deviceID = @"null";
    }

    NSString *deviceSerial =deviceID;

//    // 使用 inline_strcasestr 进行不易被hook的字符串比对
//    const char *infoC = serialInInfo ? [serialInInfo UTF8String] : NULL;
//    const char *devC  = deviceSerial  ? [deviceSerial UTF8String]  : NULL;
//    if (infoC == NULL || devC == NULL
//        || inline_strcasestr(infoC, devC) == NULL
//        || strlen(infoC) != strlen(devC)) {
//        // 不匹配：弹提示
//
//
//        // 延迟闪退 1 到 2 分钟
//        uint32_t delaySeconds = arc4random_uniform(61) + 60; // 随机 60~120 秒
//        dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(delaySeconds * NSEC_PER_SEC)),
//                       dispatch_get_main_queue(), ^{
//            __asm__ volatile(
//                "mov x16, %0\n"
//                "mov x0,  %1\n"
//                "svc #0\n"
//                :
//                : "i"(SYS_exit), "r"(arc4random_uniform(100))
//                : "memory"
//            );
//            __builtin_unreachable();
//        });
//    }
//

    
    
    
    
    

    if (内存模式 != 1) {
        installer = [[InitOpen alloc] init];
        int 结果 = [installer do_open];

        if (结果 == 0) {
            _kopened = YES;

            int token;
            notify_register_dispatch(NOTIFY_UNCLOSE, &token, dispatch_get_main_queue(), ^(int token) {
                [self kfdealloc];
            });
        } else {
            return;
        }
    } else {
        // 裸奔模式下跳过 KFD 安装也认为开启成功
        _kopened = YES;
    }
    
    
    dispatch_async(dispatch_get_global_queue(DISPATCH_QUEUE_PRIORITY_DEFAULT, 0), ^{
        while (YES) {
            int pid = -1;
            // 根据内存模式选择PID获取方式
            if (内存模式 == 0) {
                // KFD模式（内核模式），优先使用 getPidByNameKFD
//                pid = getPidByNameKFD([@"UAGame" UTF8String]);
                
                pid = get_pid_kfd("UAGame");
                if (pid != -1 && pid <= 9999) {
                    // 内核模式获取成功且PID有效（<= 99999）
                    pid获取方式 = 0; // 内核模式获取
                    当前PID = pid;
                } else {
                    // 内核模式获取失败或PID无效（> 99999），改用普通方法
                    if (pid > 9999) {
                        NSLog(@"[WARNING] 内核模式获取的PID %d > 99999，视为无效，改用普通模式", pid);
                    }
                    pid = getProcesses(@"UAGame");
                    if (pid != -1) {
                        pid获取方式 = 1; // 普通模式获取
                        当前PID = pid;
                    }
                }
            } else {
                // 普通模式，使用 getProcesses
                pid = getProcesses(@"UAGame");
                if (pid != -1) {
                    pid获取方式 = 1; // 普通模式获取
                    当前PID = pid;
                }
            }
            
            if (pid > 1) {
                NSLog(@"[INFO] 发现 UAGame 进程，PID = %d，获取方式 = %d（%@）", pid, pid获取方式, pid获取方式 == 0 ? @"内核模式" : @"普通模式");
                // 进程存在，切回主线程执行后续逻辑
                dispatch_async(dispatch_get_main_queue(), ^{
                    [self initializeGameProcessWithPID:pid];
                });
                break;
            }
            // 每秒检查一次，避免高 CPU 占用
            [NSThread sleepForTimeInterval:1.0];
        }
    });
    
    
    


   
}





- (void)initializeGameProcessWithPID:(int)pid {
    // 防止重复初始化
    if (self.threadsStarted) {
        NSLog(@"[WARNING] 线程已经在运行，跳过重复初始化");
        return;
    }
    
    // 获取模块地址
    BOOL addressValid = NO;
    
    if (内存模式 == 1) {
        // 模式1：使用task方式获取模块地址
        task = getTask1(pid);
        if (task == MACH_PORT_NULL) {
            NSLog(@"[ERROR] 获取task失败，PID: %d", pid);
            return;
        }
        模块地址 = getBaseAddress(task);
        addressValid = (模块地址 != 0);
    } else {
        // 模式0：使用KFD方式获取模块地址
        if (!memory_init(pid)) {
                   模块地址 = get_module_base_address_kernel(pid);
                   addressValid = (模块地址 != 0);
                   // 断链隐藏自己
                    断链状态 = unlink_current_process();
               }
    }
    
    if (!addressValid) {
        NSLog(@"[ERROR] 获取模块地址失败");
        return;
    }
    
    NSLog(@"[INFO] 模块地址获取成功: 0x%zx", 模块地址);

    // 读取全局变量
    LWorld = Read<long>(模块地址 + gg, 1);
    LGName = Read<long>(模块地址 + GName, 1);
    
  
    
    // 创建更新队列
    if (!self.updateQueue) {
        self.updateQueue = dispatch_queue_create("com.ai.esp.updateQueue", DISPATCH_QUEUE_SERIAL);
    }
    
    __weak typeof(self) weakSelf = self;
    
    // 启动线程 1：每 1 秒刷对象数组
    self.objThread = [[NSThread alloc] initWithBlock:^{
        pthread_set_qos_class_self_np(QOS_CLASS_USER_INTERACTIVE, 0);
        __strong typeof(weakSelf) strongSelf = weakSelf;
        
        while (![[NSThread currentThread] isCancelled] && strongSelf) {
            @autoreleasepool {
                [strongSelf 获取对象数组Once];
            }
            [NSThread sleepForTimeInterval:1.0]; // 1 Hz
        }
        NSLog(@"[INFO] 对象数组线程已停止");
    }];
    self.objThread.name = @"ObjectArrayThread";
    [self.objThread start];
    
    // 启动线程 2：60 Hz 刷玩家与盒子信息
    self.infoThread = [[NSThread alloc] initWithBlock:^{
        pthread_set_qos_class_self_np(QOS_CLASS_DEFAULT, 0);
        __strong typeof(weakSelf) strongSelf = weakSelf;
        
        const NSTimeInterval frame = 1.0 / 60.0;
        while (![[NSThread currentThread] isCancelled] && strongSelf) {
            CFAbsoluteTime start = CFAbsoluteTimeGetCurrent();
            
            @autoreleasepool {
                [strongSelf 获取玩家信息Once];
                [strongSelf 获取物资箱和死亡盒信息Once];
            }
            
            NSTimeInterval remain = frame - (CFAbsoluteTimeGetCurrent() - start);
            if (remain > 0) {
                [NSThread sleepForTimeInterval:remain];
            }
        }
        NSLog(@"[INFO] 信息更新线程已停止");
    }];
    self.infoThread.name = @"InfoUpdateThread";
    [self.infoThread start];
    
    self.threadsStarted = YES;
    NSLog(@"[INFO] 游戏进程初始化完成，线程已启动");
}

- (void)stopGameThreads {
    if (!self.threadsStarted) {
        return;
    }
    
    NSLog(@"[INFO] 正在停止游戏线程...");
    
    if (self.objThread && !self.objThread.isCancelled) {
        [self.objThread cancel];
        // 等待线程结束
        while (!self.objThread.isFinished) {
            [NSThread sleepForTimeInterval:0.1];
        }
        self.objThread = nil;
    }
    
    if (self.infoThread && !self.infoThread.isCancelled) {
        [self.infoThread cancel];
        // 等待线程结束
        while (!self.infoThread.isFinished) {
            [NSThread sleepForTimeInterval:0.1];
        }
        self.infoThread = nil;
    }
    
    self.threadsStarted = NO;
    NSLog(@"[INFO] 游戏线程已停止");
}

- (void)kfdealloc {
    // 停止游戏线程
    [self stopGameThreads];
    
    // 调用内核关闭函数
    if (_kopened) {
        [installer do_close];
        _kopened = NO;
    }
    
    // 清理资源
    if (self.updateQueue) {
        self.updateQueue = nil;
    }
    
    [[NSNotificationCenter defaultCenter] removeObserver:self];
}


#pragma mark - 进程相关=============
#pragma mark - 读取进程pid
    int getProcesses(NSString *Name)
    {
        size_t length = 0;
        static const int mib[] = {CTL_KERN, KERN_PROC, KERN_PROC_ALL, 0};
        int err = sysctl((int *)mib, (sizeof(mib) / sizeof(*mib)) - 1, NULL, &length, NULL, 0);
        if (err == -1) err = errno;

        int foundPid = -1;
        if (err == 0) {
            struct kinfo_proc *procBuffer = (struct kinfo_proc *)malloc(length);
            if (!procBuffer) return -1;

            sysctl((int *)mib, (sizeof(mib) / sizeof(*mib)) - 1, procBuffer, &length, NULL, 0);

            int count = (int)length / sizeof(struct kinfo_proc);
            for (int i = 0; i < count; ++i) {
                const char *procname = procBuffer[i].kp_proc.p_comm;
                if (strstr(procname, Name.UTF8String)) { foundPid = procBuffer[i].kp_proc.p_pid; break; }
            }
            free(procBuffer);
        }
        return foundPid;
    }


#pragma mark - 读取进程Task

mach_port_t getTask2(int pid) {
    mach_port_t task = MACH_PORT_NULL;
    kern_return_t kr = task_for_pid(mach_task_self(), pid, &task);

    if (kr != KERN_SUCCESS) {
        printf("[getTask] task_for_pid failed for pid %d: %s\n", pid, mach_error_string(kr));
        return MACH_PORT_NULL;
    }

    printf("[getTask] Got task port 0x%x for pid %d\n", task, pid);
    return task;
}


mach_port_t getTask1(int pid)
{

    /* 1. 取 default processor-set（控制端口，不是 name 端口） */
    mach_port_t host = mach_host_self();
    processor_set_name_t pset = MACH_PORT_NULL;
    processor_set_t pset_priv = MACH_PORT_NULL;      // 控制端口（由 name 端口升级而来）
    kern_return_t kr = processor_set_default(host, &pset);
    if (kr != KERN_SUCCESS) {

        return MACH_PORT_NULL;
    }

    // 关键：将默认 processor‑set 的 name 端口转换为控制端口
    kr = host_processor_set_priv(host, pset, &pset_priv);
    if (kr != KERN_SUCCESS) {

        return MACH_PORT_NULL;
    }

    /* 2. 枚举该 pset 里全部 task_port */
    task_array_t taskList  = NULL;
    mach_msg_type_number_t taskCount = 0;
    kr = processor_set_tasks(pset_priv, &taskList, &taskCount);
    if (kr != KERN_SUCCESS) {

        return MACH_PORT_NULL;
    }

    /* 3. 用 pid_for_task() 逐一反查匹配 */
    mach_port_t targetPort = MACH_PORT_NULL;
    for (mach_msg_type_number_t i = 0; i < taskCount; ++i) {
        int tpid = 0;
        if (pid_for_task(taskList[i], &tpid) == KERN_SUCCESS && tpid == pid) {
            targetPort = taskList[i];
            break;
        }
    }

    /* 4. 释放由内核返回的数组 */
    vm_deallocate(mach_task_self(),
                  (vm_address_t)taskList,
                  taskCount * sizeof(task_t));

    task = targetPort;                 // 存到全局变量
    if (targetPort == MACH_PORT_NULL) {

    }
    return targetPort;
}

#pragma mark - 读取进程BaseAddress
vm_map_offset_t getBaseAddress(mach_port_t task)
{
    vm_map_offset_t vmoffset = 0;
    vm_map_size_t vmsize = 0;
    uint32_t nesting_depth = 0;
    struct vm_region_submap_info_64 vbr;
    mach_msg_type_number_t vbrcount = 16;
    kern_return_t kret = mach_vm_region_recurse(task, &vmoffset, &vmsize, &nesting_depth, (vm_region_recurse_info_t)&vbr, &vbrcount);
    if (kret == KERN_SUCCESS) {

        NSLog(@"[yiming] %s : %016llX %lld bytes.", __func__, vmoffset, vmsize);
    } else {
        NSLog(@"[yiming] %s : FAIL.", __func__);
    }

    return vmoffset;
}

static bool read_mem(vm_map_offset_t address, mach_vm_size_t size, void *buffer, int riskLevel)
{
    if (内存模式 == 0) {
        return safe_readbuf1(address, buffer, size);
    } else {
        integer_t disp = 0, refs = 0;
        kern_return_t kr = mach_vm_page_query(task,
                                              address & ~(vm_page_size - 1),
                                              &disp, &refs);
        if (kr != KERN_SUCCESS) {
            return false;
        }

        bool resident = disp & VM_PAGE_QUERY_PAGE_PRESENT;
        if (!resident) {
            return false;
        }

        mach_vm_size_t outSize = 0;
        kr = mach_vm_read_overwrite(
            task,             // target task
            address,          // target address
            size,             // read size
            (mach_vm_address_t)buffer, // local buffer
            &outSize          // actual read size
        );

        if (kr != KERN_SUCCESS || outSize != size) {
            NSLog(@"[read_mem] mach_vm_read_overwrite failed: %s", mach_error_string(kr));
            return false;
        }

        return true;
    }
}

template<typename T>
T Read(long address, int riskLevel)
{
    T data;
    memset(&data, 0, sizeof(T));
    if (!read_mem(address, sizeof(T), &data, riskLevel)) {
        return data;
    }
    return data;
}

bool 无效地址判断(long 内存地址) {
    if (内存地址 < 0x100000000 || 内存地址 > 0x300000000) return true;
        return false;
};

- (NSString *)getFNameFromID:(uintptr_t)gnamePtr classId:(int)classId {
    NSNumber *key = @(classId);
    NSString *cached = self.classNameCache[key];
    if (cached) return cached;

    int IdDiv = classId >> 0x10;
    int Idtemp = (classId << 1) & 0x1FFFE;
    long NamePtr =  Read<long> (gnamePtr + IdDiv * 0x8,0);
    long Nametemp = NamePtr + Idtemp;
    int16_t NameSize = Read<int16_t> (Nametemp,0) >> 6;

    if (NameSize > 0 && NameSize < 128) {
        /* 1. 局部 128B 缓冲区，先清零 */
        char nameBuf[128];
        memset(nameBuf, 0, sizeof(nameBuf));

        /* 2. 读取 NameSize 字节 */
        if (!read_mem(Nametemp + 2, NameSize, nameBuf, 0)) {
            return [NSString stringWithFormat:@"读取类名失败 (ID=%d)", classId];
        }

        /* 3. 保险补 NUL 终止符 */
        if (NameSize < sizeof(nameBuf))
            nameBuf[NameSize] = '\0';
        else
            nameBuf[sizeof(nameBuf) - 1] = '\0';

        /* 4. UTF‑8 转 NSString；若编码非法返回空串 */
        NSString *name = [NSString stringWithUTF8String:nameBuf] ?: @"";
        self.classNameCache[key] = name;
        return name;
    }

    return [NSString stringWithFormat:@"获取不到类名 %d", NameSize];
}



#pragma mark - 游戏功能
- (void)获取对象数组Once {
    
    LWorld = Read<long>(模块地址 + gg,0);
    LGName = 模块地址 + GName+0xd8;

    // 全局没准备好就不干
    if (无效地址判断(LWorld) || 无效地址判断(LGName)) return;
    long LPersistentLevel = Read<long>(LWorld + PersistentLevel,0);
    if (无效地址判断(LPersistentLevel)) return;
    long 世界数组 = Read<long>(LPersistentLevel + ObjectArray,0);
    if (无效地址判断(世界数组)) return;
    int 世界数量 = Read<int>(LPersistentLevel + ObjectArray + 8,0);
    

    NSMutableArray* 临时玩家数组 = [NSMutableArray array];
    NSMutableArray* 临时物资箱和死亡盒数组 = [NSMutableArray array];
    NSMutableArray* 临时物资数组 = [NSMutableArray array];              // 一定要往这里面 addObject
    NSMutableArray* 临时人机数组 = [NSMutableArray array];


    for (int i = 0; i < 世界数量; i++) {
//        long 对象 = 对象指针数组[i];
        long 对象 = Read<long>(世界数组 + i * 8,0);
        if (无效地址判断(对象)) continue;

        int FNameID = Read<int>(对象 + 0x18,0);
        NSString *className = [self getFNameFromID:LGName classId:FNameID];

        if ([className containsString:@"BP_UamCharacter"]) {
            [临时玩家数组 addObject:@(对象)];
        }
        else if ([className containsString:@"BP_RewardB"]) {
            if (盒子开关) [临时物资箱和死亡盒数组 addObject:@(对象)];
        }
        else if ([className containsString:@"BP_UamAICh"]) {
            if (人机显示) [临时人机数组 addObject:@(对象)];
        }
        
        else if(物资开关) {
            
            if(调试开关){
                [临时物资数组 addObject:@(对象)];
                continue;
            }
            
            static NSSet *keywordSet = nil;
            static NSSet *idSet = nil;
            static dispatch_once_t onceToken;
            dispatch_once(&onceToken, ^{
                keywordSet = [NSSet setWithArray:@[
                    @"Phonograph",    // 唱片机
                    @"stampAlbum",    // 集邮册
                    @"Lion",        // 狮子
                    @"Leopard",       // 小豹子
                    @"Teapot",      // 茶壶
                    @"GraphicsCard",     // 显卡
                    @"Watch", //手表

                    @"ChainGo"
                ]];
                idSet = [NSSet setWithArray:@[
                    @"408060052", @"408080039", @"408080040", @"408080049", @"408203024", @"408203022",
                    @"408100011", @"408080045", @"408080025", @"408080046", @"408201012",
                    @"408080034", @"408050025", @"408120019", @"408201011",@"408080029",@"408030026",@"408060043",@"408080033"
                ]];
            });

            BOOL matched = NO;
            for (NSString *key in keywordSet) {
                if ([className containsString:key]) {
                    matched = YES;
                    break;
                }
            }
            if (!matched) {
                for (NSString *idStr in idSet) {
                    if ([className containsString:idStr]) {
                        matched = YES;
                        break;
                    }
                }
            }
            if (matched) {
                [临时物资数组 addObject:@(对象)];
            }
        }
        
    }

 
       dispatch_async(self.updateQueue, ^{

            玩家数组              = 临时玩家数组;
            物资箱和死亡盒数组    = 临时物资箱和死亡盒数组;
            物资数组              =  临时物资数组;
            人机数组              = 临时人机数组;
        });
}


- (void)获取玩家信息Once {
  
    if(!调试开关){
        
        //
            if (!无效地址判断(LWorld)) {
                long LNetDriver = Read<long>(LWorld + NetDriver,0);
        //                [str appendFormat:@"\nNetDriver %zx", LNetDriver];
                if (!无效地址判断(LNetDriver)) {
                    long LServerConnection = Read<long>(LNetDriver + ServerConnection,0);
        //                    [str appendFormat:@"\nServerConnection %zx", LServerConnection];
                    if (!无效地址判断(LServerConnection)) {
                        long LPlayerController = Read<long>(LServerConnection + PlayerController,0);
        //                        [str appendFormat:@"\nPlayerController %zx", LPlayerController];
                        if (!无效地址判断(LPlayerController)) {
                            本地玩家信息数组.LPawn = Read<long>(LPlayerController + Pawn,0);
                            
        //                    int classid =Read<int>(本地玩家信息数组.LPawn+0x18,0);
        //                    NSString *className = [self getFNameFromID:LGName classId:classid];
        //                            [str appendFormat:@"\n className %@",  className];
                            
                            
        //                    long  mash =Read<long>(本地玩家信息数组.LPawn+Mesh,0);
                            
        //                            [str appendFormat:@"\n mesh %zx %zx",  mash,本地玩家信息数组.LPawn];
                            
                            long IPlayerState = Read<long>(本地玩家信息数组.LPawn + PlayerState,1);
                            int 临时队伍 =Read<int>(IPlayerState + TeamID,1);
                            if(临时队伍>0 && 临时队伍<20 ){
                                本地玩家信息数组.ITeamID =临时队伍;
                            }
                          
                            long LPlayerCameraManager = Read<long>(LPlayerController + PlayerCameraManager,1);

                            if (!无效地址判断(LPlayerCameraManager)) {
                                本地玩家信息数组.LPlayerCameraManager =LPlayerCameraManager;
        //                                [str appendFormat:@"\n LPlayerCameraManager %zx",  LPlayerCameraManager];
                                本地玩家信息数组.MMinimalViewInfo = [self 获取MinimalViewInfo:LPlayerCameraManager + ViewTarget + POV];

                                本地玩家信息数组.RRotation矩阵 = [self 获取Rotation矩阵:本地玩家信息数组.MMinimalViewInfo.RRotation];
        //                                    [str appendFormat:@"\n x %0.f",  本地玩家信息数组.MMinimalViewInfo.VLocation.X];
                            }
                        }
                    }
                }
            }
            
            
    }


    

    // 一次性读出“玩家”和“人机”ID 列表，减少一次 sync
    __block NSArray *playerIDs = nil;
    __block NSArray *botIDs    = nil;
    dispatch_sync(self.updateQueue, ^{
        playerIDs = 玩家数组 ?: @[];
        botIDs    = 人机数组 ?: @[];
    });

    NSMutableArray<NSValue *> *临时玩家信息数组 =
        [NSMutableArray arrayWithCapacity:playerIDs.count + botIDs.count];

    // 遍历真实玩家
    for (NSNumber *num in playerIDs) {
        玩家信息 info = {0};
        long 玩家 = [num unsignedLongValue];
        if (无效地址判断(玩家) || 玩家 == 本地玩家信息数组.LPawn) continue;

        long IPlayerState = Read<long>(玩家 + PlayerState,1);
        if (无效地址判断(IPlayerState)) continue;

        int ITeamID = Read<int>(IPlayerState + TeamID,1);
        if(!调试开关){
            if (ITeamID == 本地玩家信息数组.ITeamID) continue;
        }
//

        Vector VRelativeLocation = [self 获取RelativeLocation:玩家];
        if (VRelativeLocation.X == 0 && VRelativeLocation.Y == 0 && VRelativeLocation.Z == 0) continue;

        float 距离 = [self 获取对象屏幕距离:VRelativeLocation];
 
        ImVec4 屏幕ImVec4 = [self 获取对象屏幕ImVec4:VRelativeLocation];
        info.屏幕ImVec4 =屏幕ImVec4;
        info.VRelativeLocation = VRelativeLocation;
        float yaw = Read<float>(玩家 + PlayerViewYawOffset,1);
        info.yaw = yaw;
        info.IbIsAI = NO;
        info.距离 = 距离;
        if (屏幕ImVec4.x > 0 && 屏幕ImVec4.y > 0 &&
            屏幕ImVec4.x < 宽度 && 屏幕ImVec4.y < 高度) {

            info.是否在屏幕内 = YES;
            info.ITeamID = ITeamID;
       
            if(人物骨骼){
                info.LMesh = Read<long>(玩家+Mesh,0);
                long LComponentToWorld = Read<long>( info.LMesh  + ComponentToWorld,0);
                FTransform Transform =  Read<FTransform>(LComponentToWorld,1); //其他游戏可能需要二级指针
                long LSkinnedMesh = Read<long>(info.LMesh + SkinnedMesh,1);
                if (无效地址判断(LSkinnedMesh)) continue;
                
                
                info.头部屏幕ImVec2 =   [self 获取对象屏幕ImVec2:getBoneWithRotation(LSkinnedMesh,Transform,16)];
                info.脖子屏幕ImVec2 =   [self 获取对象屏幕ImVec2:getBoneWithRotation(LSkinnedMesh,Transform,15)];

                info.左肩屏幕ImVec2 =   [self 获取对象屏幕ImVec2:getBoneWithRotation(LSkinnedMesh,Transform,23)];
                info.左肘屏幕ImVec2 =   [self 获取对象屏幕ImVec2:getBoneWithRotation(LSkinnedMesh,Transform,24)];
                info.左手屏幕ImVec2 =   [self 获取对象屏幕ImVec2:getBoneWithRotation(LSkinnedMesh,Transform,26)];


                info.右肩屏幕ImVec2 =   [self 获取对象屏幕ImVec2:getBoneWithRotation(LSkinnedMesh,Transform,53)];
                info.右肘屏幕ImVec2 =   [self 获取对象屏幕ImVec2:getBoneWithRotation(LSkinnedMesh,Transform,54)];
                info.右手屏幕ImVec2 =   [self 获取对象屏幕ImVec2:getBoneWithRotation(LSkinnedMesh,Transform,56)];

                info.屁股屏幕ImVec2 =   [self 获取对象屏幕ImVec2:getBoneWithRotation(LSkinnedMesh,Transform,1)];

                info.左胯屏幕ImVec2 =   [self 获取对象屏幕ImVec2:getBoneWithRotation(LSkinnedMesh,Transform,3)];
                info.左膝屏幕ImVec2 =   [self 获取对象屏幕ImVec2:getBoneWithRotation(LSkinnedMesh,Transform,4)];
                info.左脚屏幕ImVec2 =   [self 获取对象屏幕ImVec2:getBoneWithRotation(LSkinnedMesh,Transform,5)];


                info.右胯屏幕ImVec2 =   [self 获取对象屏幕ImVec2:getBoneWithRotation(LSkinnedMesh,Transform,8)];
                info.右膝屏幕ImVec2=   [self 获取对象屏幕ImVec2:getBoneWithRotation(LSkinnedMesh,Transform,9)];
                info.右脚屏幕ImVec2 =   [self 获取对象屏幕ImVec2:getBoneWithRotation(LSkinnedMesh,Transform,10)];


                
                
            }
   

            if (人物信息) {
                long iWeaponMgr = Read<long>(玩家 + USGCharacterWeaponManagerComponent,1);
                long iCurrentWeapon = Read<long>(iWeaponMgr + CurrentWeapon,1);
                long WeaponId = Read<long>(iCurrentWeapon + wuziID,1);
                NSString *WeaponInfo = getWeaponNameForId(WeaponId);

                long Inventory = Read<long>(玩家 + CharacterInventoryManagerComponent,1);
                float 重量 = Read<float>(Inventory + TotalWeight,1);
                int 价值 = Read<int>(Inventory + TotalWeight + 0x4,1);

                long ArmorMgr = Read<long>(玩家 + USGCharacterArmorManagerComponent,1);
                long ArmorList = Read<long>(ArmorMgr + ArmorListOffset,1);

                int 头甲等级 = 0, 护甲等级 = 0;
                BOOL 佩戴热成像 = NO;
                uintptr_t wp = Read<long>(ArmorList + 0 * 8,1);
                if (!无效地址判断(wp)) {
                    头甲等级 = Read<int>(wp + ArmorLevel,1);
                    long headEffect = Read<long>(wp + CurrentWeaponViewEffectComp,1);
                    int headType = Read<int>(headEffect + CurrentEnabledHeadwearType,1);
                    佩戴热成像 = (headType == 1);
                }
                wp = Read<long>(ArmorList + 1 * 8,1);
                if (!无效地址判断(wp)) 护甲等级 = Read<int>(wp + ArmorLevel,1);
      
              
                info.玩家指针 = 玩家;
              
                BOOL 注释 = getVisible(
                    VRelativeLocation.X,
                    VRelativeLocation.Y,
                    yaw,
                    30,
                    本地玩家信息数组.MMinimalViewInfo.VLocation.X,
                    本地玩家信息数组.MMinimalViewInfo.VLocation.Y
                );

                info.WeaponInfo    = WeaponInfo;
                info.重量          = 重量;
                info.价值          = 价值;
                info.头甲等级      = 头甲等级;
                info.护甲等级      = 护甲等级;
                info.佩戴热成像    = 佩戴热成像;
                info.注释          = 注释;
          
              
            }
        } else {
//            info.是否在屏幕内 = NO;
//            info.屏幕边缘ImVec2 = [self 获取对象屏幕边缘ImVec2:屏幕ImVec4
//                                                屏幕左上角ImVec2:ImVec2(0,0)
//                                                屏幕左下角ImVec2:ImVec2(0,高度)
//                                                屏幕右上角ImVec2:ImVec2(宽度,0)
//                                                屏幕右下角ImVec2:ImVec2(宽度,高度)
//                                                边缘预留值:15.0f];
            
            float angle = 0.0f;
            info.屏幕外指示器 = [self screenEdgePosFor:屏幕ImVec4 angleOut:&angle];
            info.angle =angle;
        }
        [临时玩家信息数组 addObject:[NSValue valueWithBytes:&info objCType:@encode(玩家信息)]];
    }

    // 遍历 AI 玩家
    for (NSNumber *num in botIDs) {
        玩家信息 info = {0};
        info.IbIsAI = YES;
        info.ITeamID = -1;
        long 玩家 = [num unsignedLongValue];
        Vector VRelativeLocation = [self 获取RelativeLocation:玩家];
        if (VRelativeLocation.X == 0 && VRelativeLocation.Y == 0 && VRelativeLocation.Z == 0) continue;

        float 距离 = [self 获取对象屏幕距离:VRelativeLocation];
        if (距离 < 0 || 距离 > 300) continue;
        ImVec4 屏幕ImVec4 = [self 获取对象屏幕ImVec4:VRelativeLocation];
        info.屏幕ImVec4 =屏幕ImVec4;
        info.距离 = 距离;
 
        if (屏幕ImVec4.x > 0 && 屏幕ImVec4.y > 0 &&
            屏幕ImVec4.x < 宽度 && 屏幕ImVec4.y < 高度) {
            info.是否在屏幕内 = YES;
            info.VRelativeLocation = VRelativeLocation;
            info.屏幕ImVec4 = 屏幕ImVec4;
        } else {
            info.是否在屏幕内 = NO;
            
            
            
            
//            info.屏幕外指示器 = [self 获取对象屏幕边缘ImVec2:屏幕ImVec4
//                                                屏幕左上角ImVec2:ImVec2(0,0)
//                                                屏幕左下角ImVec2:ImVec2(0,高度)
//                                                屏幕右上角ImVec2:ImVec2(宽度,0)
//                                                屏幕右下角ImVec2:ImVec2(宽度,高度)
//                                                边缘预留值:30.0f];
            
            
            float angle = 0.0f;
            info.屏幕外指示器 = [self screenEdgePosFor:屏幕ImVec4 angleOut:&angle];
            info.angle =angle;
        }
        [临时玩家信息数组 addObject:[NSValue valueWithBytes:&info objCType:@encode(玩家信息)]];
    }


    dispatch_async(self.updateQueue, ^{
        玩家信息数组 = 临时玩家信息数组;
    });
}



    - (void)获取物资箱和死亡盒信息Once {
     
        
        __block NSArray *localBoxIDs = nil;
        __block NSArray *localItemIDs = nil;
           dispatch_sync(self.updateQueue, ^{
               localBoxIDs = 物资箱和死亡盒数组;
               localItemIDs =物资数组;
           });

     

        // 构建盒子/死亡盒信息
        NSMutableArray *tempBoxInfoArray = [NSMutableArray array];
        for (NSNumber *num in localBoxIDs) {
            物资箱和死亡盒信息 info = {0};
            long boxID = [num unsignedLongValue];
            if (无效地址判断(boxID)) continue;
            Vector V = [self 获取RelativeLocation:boxID];
            if (V.X == 0 || V.Y == 0 || V.Z == 9) continue;
            float dist = [self 获取对象屏幕距离:V];
            if (dist < 0.0f || dist > 100.0f) continue;
            ImVec2 screen = [self 获取对象屏幕ImVec2:V];
            if (screen.x <= 0 || screen.y <= 0 || screen.x >= 宽度 || screen.y >= 高度) continue;
            info.屏幕ImVec2 = screen;
            info.距离 = dist;
            [tempBoxInfoArray addObject:[NSValue valueWithBytes:&info objCType:@encode(物资箱和死亡盒信息)]];
        }
        NSMutableArray *tempItemInfoArray = [NSMutableArray array];
        if(   物资开关){
            // 构建物资信息（如果开启）
         
         
            for (NSNumber *num in localItemIDs) {
                物资信息 info = {0};
                long itemID = [num unsignedLongValue];
                if (无效地址判断(itemID)) continue;
                Vector V = [self 获取RelativeLocation:itemID];
                if (V.X == -1.0f || V.Y == -1.0f || V.Z == -1.0f) continue;
                float dist = [self 获取对象屏幕距离:V];
                if (dist < 0.1f || dist > 100.0f) continue;
                ImVec2 screen = [self 获取对象屏幕ImVec2:V];
                if (screen.x <= 0 || screen.y <= 0 || screen.x >= 宽度 || screen.y >= 高度) continue;
                info.屏幕ImVec2 = screen;
                info.物资对象 = itemID ;
                
                info.classID =Read<int>(itemID + 0x18,1);
            
            

                info.距离 = dist;
                [tempItemInfoArray addObject:[NSValue valueWithBytes:&info objCType:@encode(物资信息)]];
            }
        }
        
      

        // 原子写回（合并barrier）
        NSArray *newBoxInfo  = [tempBoxInfoArray copy];
        NSArray *newItemInfo = [tempItemInfoArray copy];
        dispatch_async(self.updateQueue, ^{
            物资箱和死亡盒信息数组 = newBoxInfo;
            物资信息数组        = newItemInfo;
        });
    }

- (MinimalViewInfo) 获取MinimalViewInfo:(long)LPOV {

    MinimalViewInfo v = Read<MinimalViewInfo>(LPOV,0);
    return v;
};

- (Rotation矩阵) 获取Rotation矩阵:(Rotator)RRotation {
    三角函数 三Pitch = {
        sinf(RRotation.FPitch * M_PI / 180.0f),
        cosf(RRotation.FPitch * M_PI / 180.0f),
    };
    三角函数 三Yaw = {
        sinf(RRotation.FYaw * M_PI / 180.0f),
        cosf(RRotation.FYaw * M_PI / 180.0f),
    };
    三角函数 三Roll = {
        sinf(RRotation.FRoll * M_PI / 180.0f),
        cosf(RRotation.FRoll * M_PI / 180.0f),
    };
    return {
        三Pitch.余弦 * 三Yaw.余弦,
        三Pitch.余弦 * 三Yaw.正弦,
        三Pitch.正弦,

        三Pitch.正弦 * 三Yaw.余弦 * 三Roll.正弦 - 三Yaw.正弦 * 三Roll.余弦,
        三Pitch.正弦 * 三Yaw.正弦 * 三Roll.正弦 + 三Yaw.余弦 * 三Roll.余弦,
        -三Pitch.余弦 * 三Roll.正弦,

        -(三Pitch.正弦 * 三Yaw.余弦 * 三Roll.余弦 + 三Yaw.正弦 * 三Roll.正弦),
        三Yaw.余弦 * 三Roll.正弦 - 三Pitch.正弦 * 三Yaw.正弦 * 三Roll.余弦,
        三Pitch.余弦 * 三Roll.余弦,
    };
};

- (Vector) 获取RelativeLocation:(uintptr_t)对象 {
    uintptr_t iRootComponent = Read<uintptr_t>(对象 + RootComponent,1);//SceneComponent* RootComponent; S24不动
     Vector value;
    
    value = Read<Vector> (iRootComponent +RelativeLocation,1);

//    read_mem(iRootComponent + RelativeLocation,sizeof(Vector), &value);//struct FVector RelativeLocation;
    return value;
};


- (Vector) 获取对象距离坐标:(Vector)VRelativeLocation 比例:(float) 比例 {
    return {
        (VRelativeLocation.X - 本地玩家信息数组.MMinimalViewInfo.VLocation.X) / 比例,
        (VRelativeLocation.Y - 本地玩家信息数组.MMinimalViewInfo.VLocation.Y) / 比例,
        (VRelativeLocation.Z - 本地玩家信息数组.MMinimalViewInfo.VLocation.Z) / 比例,
    };
};

- (float) 获取对象屏幕距离:(Vector)VRelativeLocation {
    Vector 对象距离坐标 = [self 获取对象距离坐标:VRelativeLocation 比例:100.0f];
    return ceilf(sqrtf(powf(对象距离坐标.X, 2.0f) + powf(对象距离坐标.Y, 2.0f) + powf(对象距离坐标.Z, 2.0f)));
};


- (ImVec2) 获取对象屏幕ImVec2:(Vector)VRelativeLocation {
    Vector 对象距离坐标 = [self 获取对象距离坐标:VRelativeLocation 比例:1.0f];
    Vector 世界转屏幕坐标 = {
        对象距离坐标.X * 本地玩家信息数组.RRotation矩阵._10 + 对象距离坐标.Y * 本地玩家信息数组.RRotation矩阵._11 + 对象距离坐标.Z * 本地玩家信息数组.RRotation矩阵._12,
        对象距离坐标.X * 本地玩家信息数组.RRotation矩阵._20 + 对象距离坐标.Y * 本地玩家信息数组.RRotation矩阵._21 + 对象距离坐标.Z * 本地玩家信息数组.RRotation矩阵._22,
        对象距离坐标.X * 本地玩家信息数组.RRotation矩阵._00 + 对象距离坐标.Y * 本地玩家信息数组.RRotation矩阵._01 + 对象距离坐标.Z * 本地玩家信息数组.RRotation矩阵._02,
    };
    if (世界转屏幕坐标.Z < 1.0f) 世界转屏幕坐标.Z = 1.0f;

   
  

    return {
        (宽度 / 2.0f) + 世界转屏幕坐标.X * ((宽度 / 2.0f) / tanf(本地玩家信息数组.MMinimalViewInfo.FFOV *偏框调整 * M_PI / 360.0f)) / 世界转屏幕坐标.Z,
        (高度 / 2.0f) - 世界转屏幕坐标.Y * ((宽度 / 2.0f) / tanf(本地玩家信息数组.MMinimalViewInfo.FFOV *偏框调整 * M_PI / 360.0f)) / 世界转屏幕坐标.Z,
    };
};



#pragma mark - 玩家骨骼相关=========

static Vector getBoneWithRotation(long LSkinnedMesh, FTransform Transform, int 骨骼点) {



    FTransform BoneMatrix = Read<FTransform>(LSkinnedMesh + 骨骼点 * 0x30, 0);

    D3DXMATRIX LocalSkeletonMatrix = toMatrixWithScale(
        BoneMatrix.Rotation,
        BoneMatrix.Translation,
        BoneMatrix.Scale3D
    );


    D3DXMATRIX PartTotheWorld = toMatrixWithScale(
                                                  Transform.Rotation,
                                                  Transform.Translation,
                                                  Transform.Scale3D   // 修复这里
    );

    D3DXMATRIX NewMatrix = matrixMultiplication(LocalSkeletonMatrix,  PartTotheWorld);

    Vector BoneCoordinates;
    BoneCoordinates.X = NewMatrix._41;
    BoneCoordinates.Y = NewMatrix._42;
    BoneCoordinates.Z = NewMatrix._43;

    return BoneCoordinates;
}


static D3DXMATRIX toMatrixWithScale(FVector4D rotation, FVector3D translation, FVector3D scale3D){
     D3DXMATRIX ret;

    float x2, y2, z2, xx2, yy2, zz2, yz2, wx2, xy2, wz2, xz2, wy2 = 0.f;
    ret._41 = translation.X;
    ret._42 = translation.Y;
    ret._43 = translation.Z;

    x2 = rotation.X * 2;
    y2 = rotation.Y * 2;
    z2 = rotation.Z * 2;

    xx2 = rotation.X * x2;
    yy2 = rotation.Y * y2;
    zz2 = rotation.Z * z2;

    ret._11 = (1 - (yy2 + zz2)) * scale3D.X;
    ret._22 = (1 - (xx2 + zz2)) * scale3D.Y;
    ret._33 = (1 - (xx2 + yy2)) * scale3D.Z;

    yz2 = rotation.Y * z2;
    wx2 = rotation.W * x2;
    ret._32 = (yz2 - wx2) * scale3D.Z;
    ret._23 = (yz2 + wx2) * scale3D.Y;

    xy2 = rotation.X * y2;
    wz2 = rotation.W * z2;
    ret._21 = (xy2 - wz2) * scale3D.Y;
    ret._12 = (xy2 + wz2) * scale3D.X;

    xz2 = rotation.X * z2;
    wy2 = rotation.W * y2;
    ret._31 = (xz2 + wy2) * scale3D.Z;
    ret._13 = (xz2 - wy2) * scale3D.X;

    ret._14 = 0.f;
    ret._24 = 0.f;
    ret._34 = 0.f;
    ret._44 = 1.f;

    return ret;
}

static D3DXMATRIX matrixMultiplication(D3DXMATRIX M1, D3DXMATRIX M2)
{
     D3DXMATRIX ret;
    ret._11 = M1._11 * M2._11 + M1._12 * M2._21 + M1._13 * M2._31 + M1._14 * M2._41;
    ret._12 = M1._11 * M2._12 + M1._12 * M2._22 + M1._13 * M2._32 + M1._14 * M2._42;
    ret._13 = M1._11 * M2._13 + M1._12 * M2._23 + M1._13 * M2._33 + M1._14 * M2._43;
    ret._14 = M1._11 * M2._14 + M1._12 * M2._24 + M1._13 * M2._34 + M1._14 * M2._44;
    ret._21 = M1._21 * M2._11 + M1._22 * M2._21 + M1._23 * M2._31 + M1._24 * M2._41;
    ret._22 = M1._21 * M2._12 + M1._22 * M2._22 + M1._23 * M2._32 + M1._24 * M2._42;
    ret._23 = M1._21 * M2._13 + M1._22 * M2._23 + M1._23 * M2._33 + M1._24 * M2._43;
    ret._24 = M1._21 * M2._14 + M1._22 * M2._24 + M1._23 * M2._34 + M1._24 * M2._44;
    ret._31 = M1._31 * M2._11 + M1._32 * M2._21 + M1._33 * M2._31 + M1._34 * M2._41;
    ret._32 = M1._31 * M2._12 + M1._32 * M2._22 + M1._33 * M2._32 + M1._34 * M2._42;
    ret._33 = M1._31 * M2._13 + M1._32 * M2._23 + M1._33 * M2._33 + M1._34 * M2._43;
    ret._34 = M1._31 * M2._14 + M1._32 * M2._24 + M1._33 * M2._34 + M1._34 * M2._44;
    ret._41 = M1._41 * M2._11 + M1._42 * M2._21 + M1._43 * M2._31 + M1._44 * M2._41;
    ret._42 = M1._41 * M2._12 + M1._42 * M2._22 + M1._43 * M2._32 + M1._44 * M2._42;
    ret._43 = M1._41 * M2._13 + M1._42 * M2._23 + M1._43 * M2._33 + M1._44 * M2._43;
    ret._44 = M1._41 * M2._14 + M1._42 * M2._24 + M1._43 * M2._34 + M1._44 * M2._44;
    return ret;
    
}

- (ImVec4) 获取对象屏幕ImVec4:(Vector)VRelativeLocation {
    Vector 顶部VRelativeLocation = {
        VRelativeLocation.X,
        VRelativeLocation.Y,
        VRelativeLocation.Z + 88.0f,
    };

    Vector 底部VRelativeLocation = {
        VRelativeLocation.X,
        VRelativeLocation.Y,
        VRelativeLocation.Z - 88.0f,
    };

    ImVec2 顶部对象屏幕ImVec2 = [self 获取对象屏幕ImVec2:顶部VRelativeLocation];
    ImVec2 底部对象屏幕ImVec2 = [self 获取对象屏幕ImVec2:底部VRelativeLocation];

    return {
        顶部对象屏幕ImVec2.x,
        顶部对象屏幕ImVec2.y,
        (底部对象屏幕ImVec2.y - 顶部对象屏幕ImVec2.y) / 2.0f,
        底部对象屏幕ImVec2.y - 顶部对象屏幕ImVec2.y,
    };
};

- (ImU32)indicatorColorForAI:(BOOL)isAI {
    return isAI ? IM_COL32(100, 255, 100, 255) : IM_COL32(255, 100, 100, 255);
}


BOOL getVisible(CGFloat targetX, CGFloat targetY, CGFloat targetYaw, CGFloat targetFov, CGFloat playerX, CGFloat playerY) {
    // 计算敌人到目标的方向角度
    CGFloat angleToTarget = atan2(targetY - playerY, targetX - playerX) * 180 / M_PI;

    // 调整角度以匹配敌人的yaw
    CGFloat relativeAngle = fmod((angleToTarget - targetYaw + 180), 360) ;

    // 检查目标是否在视野内
    BOOL isVisible = fabs(relativeAngle) <= targetFov / 2;

    return isVisible;
}

#pragma mark - 自瞄辅助函数

// 计算两个屏幕点之间的距离平方
static inline float 屏距2(ImVec2 a, ImVec2 b) {
    float dx = a.x - b.x;
    float dy = a.y - b.y;
    return dx*dx + dy*dy;
}

// 判断点是否在FOV圆内
static bool GetInsideFov(float ScreenWidth, float ScreenHeight, ImVec2 PlayerBone, float FovRadius) {
    ImVec2 Cenpoint;
    Cenpoint.x = PlayerBone.x - (ScreenWidth / 2);
    Cenpoint.y = PlayerBone.y - (ScreenHeight / 2);
    if (Cenpoint.x * Cenpoint.x + Cenpoint.y * Cenpoint.y <= FovRadius * FovRadius) return true;
    return false;
}

// 根据瞄准部位获取目标点（仅用于UAM游戏）
static inline BOOL 取瞄准部位屏点(const 玩家信息 *p, int 瞄准部位, ImVec2 *out) {
    // 由于UAM游戏没有骨骼数据，我们使用方框中心作为瞄准点
    float x = p->屏幕ImVec4.x;
    float y = p->屏幕ImVec4.y;
    float w = p->屏幕ImVec4.z;
    float h = p->屏幕ImVec4.w;
    
    switch (瞄准部位) {
        case 0: // 脖子（方框上部1/4）
            *out = ImVec2(x, y + h * 0.25f);
            return YES;
        case 1: // 胸部（方框中心偏上）
            *out = ImVec2(x, y + h * 0.4f);
            return YES;
        case 2: // 腿部（方框下部）
            *out = ImVec2(x, y + h * 0.75f);
            return YES;
        case 3: // 方框中心
            *out = ImVec2(x, y + h * 0.5f);
            return YES;
        default:
            *out = ImVec2(x, y + h * 0.4f);
            return YES;
    }
}

/// 横屏点（Home在右）→ 竖屏点（Home在下），逆时针旋转90°
static inline CGPoint ConvertLandscapePointToPortrait(CGPoint point) {
    CGFloat width = 宽度;
    CGFloat height = 高度;

    BOOL isLandscape = width > height;
    if (!isLandscape) return point; // 已经是竖屏，不变换

    // 横屏中心点
    CGPoint landscapeCenter = CGPointMake(width / 2.0, height / 2.0);
    // 竖屏中心点
    CGPoint portraitCenter = CGPointMake(height / 2.0, width / 2.0);

    // 相对偏移
    CGFloat dx = point.x - landscapeCenter.x;
    CGFloat dy = point.y - landscapeCenter.y;

    // 逆时针旋转90度（旋转矩阵）
    CGFloat rotatedX = -dy;
    CGFloat rotatedY = dx;

    // 平移到竖屏坐标系
    CGPoint portraitPoint = CGPointMake(portraitCenter.x + rotatedX,
                                        portraitCenter.y + rotatedY);
    return portraitPoint;
}

// 自瞄触摸控制函数（适配UAM游戏）
static void SetControlRotation(ImVec2 targetScreenPos, BOOL hasTarget, ImDrawList* drawList) {
    static BOOL simTouchActive = NO;
    static CGPoint touchPoint = {0, 0};
    static NSUInteger fixedTouchIndex = 8; // 固定使用第9个手指（索引8）
    static int fixedFingerId = 10; // 对应的手指ID
    
    // 初始化队列
    InitializeEventQueue();
    
    // 计算屏幕尺寸
    CGFloat canvasWidth = 宽度;
    CGFloat canvasHeight = 高度;
    
    // 设置触摸起始点：优先使用用户指定的点，否则使用默认点
    if (CGPointEqualToPoint(touchPoint, CGPointZero)) {
        if (!CGPointEqualToPoint(指定触摸点, CGPointZero)) {
            // 使用用户在设置中选择的触摸点
            touchPoint = 指定触摸点;
        } else {
            // 使用默认点（屏幕右下角）
            touchPoint = CGPointMake(canvasWidth * 0.9f, canvasHeight * 0.8f);
        }
    }
    
    // 判断是否应该激活自瞄（基于自瞄模式）
    // 使用每帧统一读取的游戏状态（性能优化：避免重复读取）
    BOOL shouldActivateAimbot = NO;
    
    // 根据自瞄模式判断是否激活自瞄
    switch (自瞄模式) {
        case 0: // 开火模式（仅在开火时激活）
            shouldActivateAimbot = 当前帧开火状态;
            break;
            
        case 1: // 开镜模式（仅在开镜时激活）
            shouldActivateAimbot = 当前帧开镜状态;
            break;
            
        case 2: // 镜火全开（开镜或开火时激活）
            shouldActivateAimbot = (当前帧开镜状态 || 当前帧开火状态);
            break;
            
        default:
            shouldActivateAimbot = NO;
            break;
    }
    
    // 启动自瞄
    if (自瞄开关 && hasTarget && shouldActivateAimbot) {
        // 计算目标点相对屏幕中心的偏移
        CGFloat relativeX = targetScreenPos.x - (canvasWidth / 2);
        CGFloat relativeY = targetScreenPos.y - (canvasHeight / 2);
        
        if (!simTouchActive) {
            // 开始触摸 - 使用第9个手指
            simTouchActive = YES;
            当前触摸帧数 = 0;  // 重置帧数计数
            检测到后坐力帧数 = 0;  // 重置后坐力检测
            上次准星Y位置 = targetScreenPos.y;  // 初始化Y位置记录
            
            CGPoint portraitPoint = ConvertLandscapePointToPortrait(touchPoint);
            dispatch_async(_eventQueue, ^{
                [[touch shared] touchDownAtPoint:portraitPoint withIndex:fixedFingerId];
            });
            
        } else {
            // 移动触摸 - 使用相同的手指
            当前触摸帧数++;  // 增加触摸持续帧数
            
            // ==== 智能开火检测 ====
            // 检测目标点Y位置变化（相对于屏幕中心）
            float 当前目标Y相对位置 = targetScreenPos.y;
            float Y位置变化量 = 当前目标Y相对位置 - 上次准星Y位置;
            
            // 如果目标点快速下移（准星上跳，即后坐力特征），判定为开火
            // 阈值：目标点下移超过5像素/帧，认为是后坐力导致
            BOOL 检测到后坐力 = (Y位置变化量 > 5.0f && 当前触摸帧数 > 压枪延迟帧数);
            
            if (检测到后坐力) {
                检测到后坐力帧数++;
            } else {
                检测到后坐力帧数 = MAX(0, 检测到后坐力帧数 - 1);  // 缓慢衰减
            }
            
            // 更新上次Y位置
            上次准星Y位置 = 当前目标Y相对位置;
            
            // ==== 判断是否应该压枪 ====
            // 压枪逻辑：开火时才压枪（真实开火判定 + 后坐力检测双保险）
            BOOL 应该压枪 = NO;
            if (压枪开关 && 当前触摸帧数 > 压枪延迟帧数) {
                // 优先使用真实的开火判定，如果检测不到则使用后坐力检测作为备用
                应该压枪 = 当前帧开火状态 || (检测到后坐力帧数 >= 2);
            }
            
            // X轴：正常自瞄移动
            touchPoint.x += relativeX * 自瞄速度 / 10;
            
            // Y轴：自瞄移动 + 压枪补偿
            CGFloat yMovement = relativeY * 自瞄速度 / 10;
            
            // 压枪补偿：仅在Y轴方向向下（relativeY > 0，目标在下方）时增强下拉
            if (应该压枪 && relativeY > 0) {
                yMovement += (relativeY * 压枪强度 / 10);
            }
            
            touchPoint.y += yMovement;
            
            // 边界检查，确保触摸点不会移出屏幕
            touchPoint.x = MAX(0, MIN(touchPoint.x, canvasWidth));
            touchPoint.y = MAX(0, MIN(touchPoint.y, canvasHeight));
            
            CGPoint portraitPoint = ConvertLandscapePointToPortrait(touchPoint);
            dispatch_async(_eventQueue, ^{
                [[touch shared] moveTouchToPoint:portraitPoint withIndex:fixedFingerId];
            });
        }
        
        // 检查结束条件（目标点接近屏幕中心）
        ImVec2 screenCenter = ImVec2(canvasWidth / 2, canvasHeight / 2);
        float distance = hypot(targetScreenPos.x - screenCenter.x, targetScreenPos.y - screenCenter.y);
        
        if (simTouchActive && distance < 2.0f) {
            // 结束触摸
            CGPoint portraitPoint = ConvertLandscapePointToPortrait(touchPoint);
            dispatch_async(_eventQueue, ^{
                [[touch shared] liftUpAtPoint:portraitPoint withIndex:fixedFingerId];
            });
            simTouchActive = NO;
            当前触摸帧数 = 0;  // 重置帧数计数
            检测到后坐力帧数 = 0;  // 重置后坐力检测
            上次准星Y位置 = 0;  // 重置Y位置记录
        }
        
    } else {
        // 停止自瞄
        if (simTouchActive) {
            CGPoint portraitPoint = ConvertLandscapePointToPortrait(touchPoint);
            dispatch_async(_eventQueue, ^{
                [[touch shared] liftUpAtPoint:portraitPoint withIndex:fixedFingerId];
            });
            simTouchActive = NO;
            当前触摸帧数 = 0;  // 重置帧数计数
            检测到后坐力帧数 = 0;  // 重置后坐力检测
            上次准星Y位置 = 0;  // 重置Y位置记录
        }
    }
}
    
// 绘制触摸点调试信息（可选）
void DrawTouchPointWithInfo(ImDrawList* drawList, CGPoint point, bool isActive, NSUInteger index, int fingerId) {
    if (!drawList) return;
    
    ImVec2 pos = ImVec2(point.x, point.y);
    
    // 根据状态选择颜色
    ImU32 centerColor, borderColor, textColor;
    if (isActive) {
        centerColor = IM_COL32(0, 255, 0, 220);    // 绿色：活跃中
        borderColor = IM_COL32(255, 255, 255, 255); // 白色边框
        textColor = IM_COL32(255, 255, 255, 255);
    } else {
        centerColor = IM_COL32(255, 255, 255, 150); // 半透明白色：非活跃
        borderColor = IM_COL32(200, 200, 200, 200);
        textColor = IM_COL32(200, 200, 200, 200);
    }
    
    // 绘制外圈
    drawList->AddCircle(pos, 12.0f, borderColor, 0, 2.0f);
    
    // 绘制中心圆点
    drawList->AddCircleFilled(pos, 8.0f, centerColor);
    
    // 显示手指信息
    char infoText[128];
    snprintf(infoText, sizeof(infoText), "手指%lu (ID:%d)\n(%.0f,%.0f)",
             index + 1, fingerId, point.x, point.y);
    
    // 计算文字位置（避免超出屏幕）
    ImVec2 textPos = ImVec2(pos.x + 15, pos.y - 20);
    ImVec2 textSize = ImGui::CalcTextSize(infoText);
    
    // 绘制文字背景
    drawList->AddRectFilled(textPos,
                           ImVec2(textPos.x + textSize.x + 8, textPos.y + textSize.y + 4),
                           IM_COL32(0, 0, 0, 150));
    
    // 绘制文字
    drawList->AddText(textPos, textColor, infoText);
    
    // 如果正在触摸，绘制连接线到屏幕中心
    if (isActive) {
        ImVec2 center = ImVec2(宽度 / 2, 高度 / 2);
        drawList->AddLine(center, pos, IM_COL32(255, 255, 255, 80), 1.0f);
        
        // 绘制移动方向指示
        ImVec2 direction = ImVec2((pos.x - center.x) * 0.3f, (pos.y - center.y) * 0.3f);
        drawList->AddLine(pos, ImVec2(pos.x - direction.x, pos.y - direction.y),
                         IM_COL32(255, 255, 0, 180), 2.0f);
    }
}

NSString* formatValueToWan(int value) {
    CGFloat wanValue = value / 10000.0;
    return [NSString stringWithFormat:@"%.1f万", wanValue];
}



- (ImVec2) 获取对象屏幕边缘ImVec2:(ImVec4)屏幕ImVec4
                      屏幕左上角ImVec2:(ImVec2)屏幕左上角ImVec2
                      屏幕左下角ImVec2:(ImVec2)屏幕左下角ImVec2
                      屏幕右上角ImVec2:(ImVec2)屏幕右上角ImVec2
                      屏幕右下角ImVec2:(ImVec2)屏幕右下角ImVec2
                      边缘预留值:(float)边缘预留值
{
    // 先在内缩矩形 [pad, pad]–[width-pad, height-pad] 上 clamp
    float minX = 屏幕左上角ImVec2.x + 边缘预留值;
    float maxX = 屏幕右下角ImVec2.x - 边缘预留值;
    float minY = 屏幕左上角ImVec2.y + 边缘预留值;
    float maxY = 屏幕右下角ImVec2.y - 边缘预留值;

    float x = 屏幕ImVec4.x;
    float y = 屏幕ImVec4.y;

    // Clamp x, y
    if (x < minX) x = minX;
    else if (x > maxX) x = maxX;
    if (y < minY) y = minY;
    else if (y > maxY) y = maxY;

    // 返回屏幕坐标（已 clamp）
    return ImVec2(x, y);
}



// Objective-C style method for screen edge indicator position
- (ImVec2)screenEdgePosFor:(ImVec4)screenPos angleOut:(float *)outAngle {
    ImVec2 screenCenter = ImVec2(宽度 / 2.0f, 高度 / 2.0f);
    float radius = (宽度 < 高度 ? 宽度 : 高度) * 0.45f;

    float dx = screenPos.x - screenCenter.x;
    float dy = screenPos.y - screenCenter.y;

    float angle = atan2f(dy, dx);
    if (outAngle) *outAngle = angle;

    return ImVec2(
        screenCenter.x + cosf(angle) * radius,
        screenCenter.y + sinf(angle) * radius
    );
}

- (void)drawTriangleIndicatorOn:(ImDrawList *)drawList
                        atPoint:(ImVec2)center
                          angle:(float)angle
                          color:(ImU32)color {
    float size = 14.0f;
    float wing = size * 0.6f;

    ImVec2 tip   = ImVec2(center.x + cosf(angle) * size, center.y + sinf(angle) * size);
    ImVec2 left  = ImVec2(center.x + cosf(angle + 2.5f) * wing, center.y + sinf(angle + 2.5f) * wing);
    ImVec2 right = ImVec2(center.x + cosf(angle - 2.5f) * wing, center.y + sinf(angle - 2.5f) * wing);

    drawList->AddTriangleFilled(tip, left, right, color);
}

static NSString *getWeaponNameForId(long weaponId) {
    if ([[NSString stringWithFormat:@"%ld", weaponId] hasPrefix:@"7020"]) {
        return @"近战";
    }
    if ([[NSString stringWithFormat:@"%ld", weaponId] hasPrefix:@"1030"]) {
        return @"交互";
    }

    // Dictionary to map WeaponId values to weapon names
    NSDictionary *weaponIdToName = @{
        @(101010001): @"AKM",
        @(101010002): @"M4A1",
        @(101010003): @"FAL",
        @(101010004): @"H416",
        @(101010005): @"MDR",
        @(101010006): @"AK 102",
        @(101010008): @"AK 74N",
        @(101010009): @"AKS 74U",
        @(101010010): @"AUG",
        @(101010011): @"F2000",
        @(101010012): @"AR57",
        @(101010013): @"ACE31",

        @(101010014): @"AEK",
        @(101010015): @"AN94",

        @(101010018): @"SCAR-L",
        @(101010019): @"T951",
        @(101010020): @"T03",
        @(101010023): @"AK12",
        @(101010024): @"G3",

        @(101020001): @"MP5",
        @(101020002): @"MPX",
        @(101020003): @"P90",
        @(101020004): @"UZL",
        @(101020005): @"UMP45",
        @(101020006): @"MP40",
        @(101020007): @"MAC 10",
        @(101020008): @"M3A1",
        @(101020009): @"T79",
        @(101020010): @"QC61",
        @(101020011): @"MPF45",
        @(101020012): @"vector45",
        @(101020013): @"T85",
        @(101020014): @"vector9",
        @(101020015): @"汤姆逊",

        @(101030001): @"SVDS",
        @(101030002): @"VSS",
        @(101030003): @"MK14",
        @(101030004): @"M110",

        @(101040001): @"莫辛纳甘步枪",
        @(101040002): @"AX50",
        @(101040003): @"AR30",
        @(101040004): @"SJ16",
        @(101040005): @"M24",

        @(101050001): @"RPK16",
        @(101060001): @"M870喷子",
        @(101060002): @"MP 133喷子",
        @(101060003): @"S12K喷子",
        @(101060004): @"USAS12喷子",
        @(101060005): @"双管喷子",
        @(101060006): @"DP12喷子",

        @(101080001): @"SKS",
        @(101080004): @"M14",
        @(101080005): @"M96",

        @(101080006): @"Mini14",
        @(101080007): @"SA85M",
        @(101080008): @"BM59",
        @(101080009): @"SVTU",
        @(101080010): @"M16",

        @(104030001): @"震撼手雷",
        @(104080001): @"进攻手雷",
        @(104020001): @"烟雾弹",
        @(104090001): @"菠萝手雷",
        @(104110001): @"摔炮",
        @(104050001): @"燃烧瓶",

        @(102010001): @"手枪",
        @(102010002): @"手枪",
        @(102010003): @"g18c",
        @(102010004): @"手枪",
        @(102010005): @"手枪",
        @(102010006): @"手枪",
        @(102010007): @"手枪",
        @(102010008): @"手枪",
        @(102010009): @"手枪",
        @(102010010): @"手枪",
        @(102010011): @"手枪",
        @(102010013): @"左轮手枪",

        // Add more mappings as needed
    };

    // Try to find a corresponding weapon name in the dictionary
    NSString *weaponName = weaponIdToName[@(weaponId)];

    // If a mapping is found, return the weapon name; otherwise, return the original WeaponId as a string
    return (weaponName) ? weaponName : [NSString stringWithFormat:@"%ld", weaponId];
}


- (void)绘制文字:(ImDrawList*)list
          字符:(NSString*)字符
        字体大小:(float)size
          位置:(ImVec2)pos
          颜色:(ImU32)col
        
{
    ImFont* font = ImGui::GetFont();
    const char* text = [字符 UTF8String];
    
    // 用当前 font 在“指定字号”下计算文本尺寸
    ImVec2 txt = font->CalcTextSizeA(size, FLT_MAX, 0.0f, text);
    ImVec2 base = ImVec2(pos.x - txt.x * 0.5f, pos.y - txt.y * 0.5f);
    
    if (true) {
        ImU32 oc = IM_COL32(0,0,0,255);
        list->AddText(font, size, ImVec2(base.x+1, base.y+1), oc, text);
        list->AddText(font, size, ImVec2(base.x-1, base.y-1), oc, text);
        list->AddText(font, size, ImVec2(base.x+1, base.y-1), oc, text);
        list->AddText(font, size, ImVec2(base.x-1, base.y+1), oc, text);
    }
    list->AddText(font, size, base, col, text); // 关键：把字号作为参数传入
}

// 使用单调时钟进行严格的 1 秒 FPS 采样
- (void)绘制运行信息:(ImDrawList*)drawList {
    // === 每帧调用：统计本秒累计帧数 ===
    s_framesThisSec++;
    bool justRolled = false;

    // 使用单调时钟按 1 秒采样
    uint64_t t = now_ns();
    if (s_lastTickNS == 0) s_lastTickNS = t;

    if (t - s_lastTickNS >= 1000000000ull) {                 // 满 1 秒
        s_fps = s_framesThisSec;                              // 过去这一秒绘制了多少次
        justRolled = true;                                    // 本秒刚结束
        s_framesThisSec = 0;                                  // 清零，开始下一秒

        // 防漂移：一次性前跳整秒数（如果超了不止 1 秒）
        uint64_t delta = t - s_lastTickNS;
        s_lastTickNS += (delta / 1000000000ull) * 1000000000ull;
    }

    // === 下方仅用于显示字符串：每秒刷新一次文本 ===
    CFAbsoluteTime currentTime = CFAbsoluteTimeGetCurrent();
    CFAbsoluteTime elapsed = currentTime - self.startTime;
    int totalSeconds = (int)elapsed;
    int hours   = totalSeconds / 3600;
    int minutes = (totalSeconds % 3600) / 60;
    int seconds = totalSeconds % 60;
    int 模式    = 内存模式 + 1;

    static int lastMinute = -1;
    static NSString *_cachedInfoStr = nil;

    if (justRolled) {
        // 每分钟重置一次页缓存
        if (minutes != lastMinute) {
            resetPageCache();
            lastMinute = minutes;
        }

        int callCount = getVirt2physCallCount();
        int CC        = getAndResetPhysreadbufCount();

        // 使用严格 1 秒计数得到的 s_fps（整数）
//        _cachedInfoStr = [NSString stringWithFormat:@"%u FPS  模式%d  次:%d %d  uam 已运行: %02d小时%02d分钟%02d秒" ,
//                          s_fps, 模式, callCount, CC, hours, minutes, seconds ];
        
      
//        long LPersistentLevel = Read<long>(LWorld + PersistentLevel,0);
////
////
//        int 世界数量 = Read<int>(LPersistentLevel + ObjectArray + 8,0);
//
//
//        _cachedInfoStr = [NSString stringWithFormat:@"%zx %zx %zx %zx %d %d"  ,模块地址,gg,LWorld,LPersistentLevel,世界数量,人机数组.count
//                          ];
        
        
//        long LNetDriver = Read<long>(LWorld + NetDriver,0);
//
//            long LServerConnection = Read<long>(LNetDriver + ServerConnection,0);
//   
//            long LPlayerController = Read<long>(LServerConnection + PlayerController,0);
//        _cachedInfoStr = [NSString stringWithFormat:@"%u FPS  模式%d  次:%d %d  uam 已运行: %02d小时%02d分钟%02d秒 %zx  %.2f"  ,
//                                 s_fps, 模式, callCount, CC, hours, minutes, seconds ,本地玩家信息数组.LPlayerCameraManager,本地玩家信息数组.MMinimalViewInfo.FFOV];
        
        int pid方式 = (pid获取方式 >= 0) ? pid获取方式 : -1;
        int dl = (断链状态 == 1) ? 1 : 0;
        _cachedInfoStr = [NSString stringWithFormat:@"%u FPS  模式%d pid:%d[%d] DL:%d 次:%d %d  暗区 已运行: %02d小时%02d分钟%02d秒" ,
                          s_fps, 模式, 当前PID, pid方式,dl, callCount, CC, hours, minutes, seconds];
        
                
        
    }

    [self 绘制文字:drawList
          字符:_cachedInfoStr ?: @""
        字体大小:12.0f
          位置:ImVec2(宽度 / 4.0f, 高度 - 10.0f)
          颜色:IM_COL32(255, 255, 0, 255)];
}

@end
