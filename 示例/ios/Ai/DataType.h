#import "imgui.h"
#import "imgui_impl_metal.h"
#import <vector>

struct Vector {
    float X;
    float Y;
    float Z;
};

struct Rotator {
    float FPitch;
    float FYaw;
    float FRoll;
};

struct MinimalViewInfo {
    Vector VLocation;
    Rotator RRotation;
    float FFOV;
};

struct Rotation矩阵 {
    float _00, _01, _02;
    float _10, _11, _12;
    float _20, _21, _22;
};

struct 三角函数 {
    float 正弦;
    float 余弦;
};

struct ImGuiData {
    bool IsScreen;
    long UMesh;
    long UComponentToWorld;
    long UStaticMesh;
    float FHealth;
    long UPlayerName;
};

struct 本地玩家信息 {
    long LPawn;
    long LPlayerCameraManager;
    int ITeamID;
    int IbFreeCamera;
    MinimalViewInfo MMinimalViewInfo;
    Rotation矩阵 RRotation矩阵;
};

struct 玩家信息 {
    int ITeamID;
    float 距离;
    int IbIsAI;
    bool 是否在屏幕内;
    Vector VRelativeLocation;
    long LMesh;
    long 玩家指针;
    float 百分比血量;
    long LPlayerName;
    ImVec2 屏幕边缘ImVec2;
    ImVec2 屏幕外指示器;
    float angle;
    NSString *WeaponInfo;
    float 重量;
    float yaw;
    int 价值;
    int  头甲等级;
    int  护甲等级;
    bool  佩戴热成像;
    bool  注释;
    
    
    ImVec4 屏幕ImVec4;
    ImVec2 头部屏幕ImVec2;
    bool 头部是否露出 = false;
    ImVec2 脖子屏幕ImVec2;
    bool 脖子是否露出 = false;
    ImVec2 左肩屏幕ImVec2;
    bool 左肩是否露出 = false;
    ImVec2 左肘屏幕ImVec2;
    bool 左肘是否露出 = false;
    ImVec2 左手屏幕ImVec2;
    bool 左手是否露出 = false;
    ImVec2 右肩屏幕ImVec2;
    bool 右肩是否露出 = false;
    ImVec2 右肘屏幕ImVec2;
    bool 右肘是否露出 = false;
    ImVec2 右手屏幕ImVec2;
    bool 右手是否露出 = false;
    ImVec2 屁股屏幕ImVec2;
    bool 屁股是否露出 = false;
    ImVec2 左胯屏幕ImVec2;
    bool 左胯是否露出 = false;
    ImVec2 左膝屏幕ImVec2;
    bool 左膝是否露出 = false;
    ImVec2 左脚屏幕ImVec2;
    bool 左脚是否露出 = false;
    ImVec2 右胯屏幕ImVec2;
    bool 右胯是否露出 = false;
    ImVec2 右膝屏幕ImVec2;
    bool 右膝是否露出 = false;
    ImVec2 右脚屏幕ImVec2;
    bool 右脚是否露出 = false;
    
};

struct 骨骼数据 {
    bool 屏幕内判断;
    bool 人机判断;
    ImVec2 头部屏幕ImVec2;
    bool 头部是否露出 = false;
    ImVec2 脖子屏幕ImVec2;
    bool 脖子是否露出 = false;
    ImVec2 左肩屏幕ImVec2;
    bool 左肩是否露出 = false;
    ImVec2 左肘屏幕ImVec2;
    bool 左肘是否露出 = false;
    ImVec2 左手屏幕ImVec2;
    bool 左手是否露出 = false;
    ImVec2 右肩屏幕ImVec2;
    bool 右肩是否露出 = false;
    ImVec2 右肘屏幕ImVec2;
    bool 右肘是否露出 = false;
    ImVec2 右手屏幕ImVec2;
    bool 右手是否露出 = false;
    ImVec2 屁股屏幕ImVec2;
    bool 屁股是否露出 = false;
    ImVec2 左胯屏幕ImVec2;
    bool 左胯是否露出 = false;
    ImVec2 左膝屏幕ImVec2;
    bool 左膝是否露出 = false;
    ImVec2 左脚屏幕ImVec2;
    bool 左脚是否露出 = false;
    ImVec2 右胯屏幕ImVec2;
    bool 右胯是否露出 = false;
    ImVec2 右膝屏幕ImVec2;
    bool 右膝是否露出 = false;
    ImVec2 右脚屏幕ImVec2;
    bool 右脚是否露出 = false;
};


struct 玩家绘制信息 {
    Vector VRelativeLocation;
    NSString *WeaponInfo;
    float 重量;
    int 价值;
    int  头甲等级;
    int  护甲等级;
    bool  佩戴热成像;
    int ITeamID;
    float 距离;
    int IbIsAI;
    bool 是否在屏幕内;
    float 百分比血量;
    long LPlayerName;
    bool  注释;
    ImVec4 屏幕ImVec4;
    ImVec2 屏幕边缘ImVec2;
    ImVec2 头部屏幕ImVec2;
    ImVec2 脖子屏幕ImVec2;
    ImVec2 左肩屏幕ImVec2;
    ImVec2 左肘屏幕ImVec2;
    ImVec2 左手屏幕ImVec2;
    ImVec2 右肩屏幕ImVec2;
    ImVec2 右肘屏幕ImVec2;
    ImVec2 右手屏幕ImVec2;
    ImVec2 屁股屏幕ImVec2;
    ImVec2 左胯屏幕ImVec2;
    ImVec2 左膝屏幕ImVec2;
    ImVec2 左脚屏幕ImVec2;
    ImVec2 右胯屏幕ImVec2;
    ImVec2 右膝屏幕ImVec2;
    ImVec2 右脚屏幕ImVec2;
};

//struct 测试骨骼信息 {
//    int 骨骼点总数;
//    std::array<ImVec2, 100> 骨骼屏幕ImVec2;
//};

struct 物资箱和死亡盒信息 {
    float 距离;
    ImVec2 屏幕ImVec2;
    int IGameID;
};

struct 物资信息 {
    float 距离;
    ImVec2 屏幕ImVec2;
    int itemID;
    int classID;
    NSString *物资名字;
    long 物资对象;
};

struct Quat {
    float X;
    float Y;
    float Z;
    float W;
};

struct Transform {
    Quat QRotation;
    Vector VTranslation;
    Vector VScale3D;
};

struct Transform矩阵 {
    float _00, _01, _02;
    float _10, _11, _12;
    float _20, _21;
    float _30, _31, _32;
};
typedef struct FVector3D {
    float X;
    float Y;
    float Z;
} FVector3D;


typedef struct FVector4D {
    float X;
    float Y;
    float Z;
    float W;
} FVector4D;


typedef struct FTransform {
    FVector4D Rotation;
    FVector3D Translation;
    FVector3D Scale3D;
} FTransform;

typedef struct D3DXMATRIX {
    float _11, _12, _13, _14;
    float _21, _22, _23, _24;
    float _31, _32, _33, _34;
    float _41, _42, _43, _44;
} D3DXMATRIX;


