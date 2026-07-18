//
//  UIInitializer.h
//  AI
//
//  Created by a on 2025/6/30.
//

//服务器地址
#define BSPHP_HOST  @"http://54.151.194.225/AppEn.php?appid=201&md5=d40e9f6e133abd55e5da99be059714c9"


//通信认证Key
#define BSPHP_MUTUALKEY @"c91e82afeecfa95a476d1e8d64cc1988"
//接收Sgin验证 注意必须填写 并且有[KEY]
#define BSPHP_INSGIN @"[KEY]RsffgSNGRG"
//输出Sgin验证 注意必须填写 并且有[KEY]
#define BSPHP_TOSGIN @"[KEY]RsffgSNGRG"
//数据加密密码
#define BSPHP_PASSWORD @"1ypZikhCfYyxIDkupB"

#define gIv   @"cookies"


#define JN_VERSION @"v1.0"

//加密秘钥 这个在服务器文件后台 网站目录/include/applibapi/encryption下的bsphp_3des_vi.php 里面保持一致 搜索bsphp666就有3处地方都改掉 和这里源码一致 BS安装默认为bsphp666
//**注意 仅限8位数


//定期验证 单位为秒 可以设置5分钟 几分钟看个人需求 用于动态检测版本更新 公告更新 检测是否到期 检测是否冻结 时间太短 用户多会增加服务器压力
#define  BS_DSQ 300

//如果使用udid获取描述文件 需要udid.php上传到域名指定目录
#define  UDID_HOST  @""

//启动APP后多少秒开始验证 单位秒 ** 因为很多游戏有启动画面 启动动画 会刷新UI 刷掉弹窗 相当于没了验证 比如光遇 王者荣耀 等 要等启动动画结束才弹窗 自己测试时间
#define BS延迟启动时间 5





#import <Foundation/Foundation.h>

NS_ASSUME_NONNULL_BEGIN

typedef void(^VerifyCompletion)(BOOL verified, NSString *message);

@interface UIInitializer : NSObject

/// Performs verification for the given card key.
/// @param km         The card key (km) to verify.
/// @param completion Callback invoked with the verification result and message.
+ (void)verifyWithKM:(NSString *)km
          completion:(VerifyCompletion)completion;

/// Returns the device serial number.
+ (NSString *)serialNumber;

@end

NS_ASSUME_NONNULL_END
