

#define GameViewport 0x8C0 // GameViewportClient* GameViewport
#define World 0x78 // World* World


//#define GName 0xd5b68d8 // IDA
#define GName 0xefb2900// IDA





#define PersistentLevel 0x30 // Level* PersistentLevel

#define ObjectArray 0x98



#define NetDriver 0x38 // NetDriver* NetDriver
#define ServerConnection 0x88 // NetConnection* ServerConnection
#define PlayerController 0x30 //struct APlayerController* PlayerController; // 0x30(0x8)

#define Pawn 0x03e8 // Pawn* Pawn


// struct ASGCharacter : ASGCharacterBase 下
#define CmpCacheArray 0x18C8

#define USGCharacterWeaponManagerComponent 0x1A30  // CmpCacheArray+45*8
#define CurrentWeapon 0x130
#define wuziID 0x728

#define bCanEverMergeWeapon 0xBA0 // 0xBA0

#define WeaponFiringComponent 0xBB0 //bCanEverMergeWeapon+3*8
#define bIsShooting  0x2D6

#define CharacterInventoryManagerComponent CmpCacheArray+47*8 //CmpCacheArray+47*8
#define TotalWeight 0x01E0

#define USGCharacterArmorManagerComponent CmpCacheArray+52*8 //CmpCacheArray+52*8
#define ArmorListOffset 0x260
#define ArmorLevel 0x730
#define CurrentWeaponViewEffectComp 0x930 // 其实是 SGInventoryHelmetSwitchComponent 需要转内存看


#define CurrentEnabledHeadwearType 0x12C

#define PlayerViewYawOffset 0x1664


#define PlayerCameraManager 0x0440 // PlayerCameraManager* PlayerCameraManager
#define ViewTarget 0x11A0 // TViewTarget ViewTarget

#define POV 0x10 // MinimalViewInfo POV



#define PlayerState 0x03d8 //struct APlayerState* PlayerState;
#define TeamID 0x0648 // int32_t TeamIndex;

#define RootComponent 0x0158 // SceneComponent* RootComponent
#define RelativeLocation 0x154 // Vector RelativeLocation

#define bIsAI 0xAB4 // bool bIsAI



#define Mesh 0x418 // SkeletalMeshComponent* Mesh
#define ComponentToWorld 0x200 // Transform ComponentToWorld
#define SkinnedMesh 0x700 // USkinnedMeshComponent : UMeshComponent 下面 有个大小为f8的地址



//#define Health 0xE38 // float Health
//#define HealthMax 0xE40 // float HealthMax

#define PlayerName 0x4E8 // FString PlayerNamePrivate
