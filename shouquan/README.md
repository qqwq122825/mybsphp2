# MyBsPHP iOS 授权 Demo

这是一个干净、可运行、方便复制到现有项目的 Objective-C Demo。页面只负责组装参数和展示结果，
网络加密与授权响应处理已经拆成独立文件。

## 文件职责

| 文件 | 用途 |
| --- | --- |
| `BSPHPConfig.h` | 后台复制的软件地址、通信 Key、RSA 公钥 |
| `NetTool.h/.m` | POST、请求加密、响应验签、第一层解密 |
| `BSPHPResponseProcessor.h/.m` | 每日校验、每次校验、二次解密、结果整理 |
| `BSPHPTransportCrypto.h/.mm` | RSA-OAEP、AES-256-CBC、HMAC-SHA256 底层实现 |
| `ViewController.m` | 最小业务调用示例和结果 UI |

## 1. 填写配置

只需修改 `BSPHPConfig.h`：

```objc
#define BSPHP_APP_CODE @"APP_ID"
#define BSPHP_APP_NAME @"APP_NAME"

#define BSPHP_HOST @"https://HOST/AppEn.php?appid=APP_ID&md5=TOKEN"
#define BSPHP_MUTUALKEY @"COMMUNICATION_KEY"
#define BSPHP_SERVER_PUBLIC_KEY @"MIIBCgKCAQEA...IDAQAB"
#define BSPHP_APPSAFECODE @"YOUR_APP_PREFIX"
```

服务器公钥是后台生成的单行 Base64，可整段直接粘贴，不含 PEM 头尾和换行。

## 2. 页面中的调用方式

```objc
#import "BSPHPConfig.h"
#import "BSPHPResponseProcessor.h"
#import "NetTool.h"

NSString *appSafeCode = [NSString stringWithFormat:@"%@-%lld-%@",
                         BSPHP_APPSAFECODE,
                         (long long)(NSDate.date.timeIntervalSince1970 * 1000),
                         NSUUID.UUID.UUIDString];

NSMutableDictionary *param = [NSMutableDictionary dictionary];
param[@"api"] = @"login.ic";
param[@"icid"] = cardCode;
param[@"icpwd"] = cardPassword;
param[@"key"] = deviceID;
param[@"appsafecode"] = appSafeCode;

[NetTool Post_AppendURL:BSPHP_HOST parameters:param success:^(id responseObject) {
    NSError *error = nil;
    NSDictionary *result = [BSPHPResponseProcessor processCardResponse:responseObject
                                                               cardCode:cardCode
                                                            appSafeCode:appSafeCode
                                                                  error:&error];
    if (error) {
        NSLog(@"授权响应处理失败：%@", error.localizedDescription);
        return;
    }

    NSLog(@"激活成功");
    NSLog(@"到期时间：%@", result[@"card"][@"expires_at"]);
    NSLog(@"验证数据：%@", result[@"payload"]);
} failure:^(NSError *error) {
    NSDictionary *details = error.userInfo[BSPHPErrorDetailsUserInfoKey];
    NSNumber *retryAfter = error.userInfo[BSPHPRetryAfterUserInfoKey];
    if ([details[@"control_level"] isEqualToString:@"high"]) {
        NSLog(@"登录进入高级封控，%@ 秒后重试", retryAfter ?: @0);
        return;
    }
    NSLog(@"网络或服务端错误：%@", error.localizedDescription);
}];
```

线上 HTTP Body 使用无字段名的紧凑数组，不直接出现 `iv`、`data`、`mac`、`auth`、
`request_nonce` 或算法名称。`success` 回调中的 `responseObject` 是完成 HMAC 验证和第一层 AES 解密后的字符串。
完整位置说明见 [`WIRE_V3.md`](WIRE_V3.md)。
业务页面在回调中交给 `BSPHPResponseProcessor`，得到统一的字典结果。

上线运行时高级封控表现为普通 HTTP 503 服务器故障，页面按常规退避停止密集重试；调试模式下 `NetTool` 可读取 `error.details.control_level` 和 HTTP `Retry-After` 并展示等待时间。低级封控保持成功字段布局和到期时间，客户端仍按原有位置校验验证数据。

## 账号模式规则

- 注册只创建账号身份，不自动赠送授权时长。
- 所有充值卡均为纯卡号，不使用独立卡密码；`icpwd`/`card_secret` 保留为空即可。
- 注册时填写充值卡或邀请码获得奖励后，到期时间才会向后延长。
- 无充值卡、无邀请奖励的账号会返回明确到期时间，授权状态为“已过期”。
- 账号密码正确但授权已过期时，服务端返回 `ACCOUNT_EXPIRED`，并附带
  `credentials_verified`、`expires_at` 和 `authorization.status`，页面据此展示“账号验证成功，授权已过期”。
- 登录页支持 `chong.lg` 充值卡续期；续期成功后重新返回到期时间、每日校验、每次校验和验证数据。

## BSphpSeSsL 与心跳

`BSphpSeSsL` 是 64 位登录连接句柄，作用类似 session token，但不是 Laravel 后台 Cookie。
客户端可调用 `BSphpSeSsL.in` 获取，也可自行生成 64 位随机字符串。登录时把它随请求提交，
服务端只保存 SHA-256 摘要，并将连接绑定到软件、账号或卡号、设备。

它主要用于：

- 主程序与多个页面、插件或动态库共享同一次登录状态；
- 区分同一软件的多个登录账号；
- 心跳、多开统计、远程冻结和连接超时判断。

软件公告、版本号和验证数据属于软件配置数据，不依赖该句柄。

登录成功后 Demo 每 30 秒调用一次：

- 卡串模式：`timeout.ic`
- 账号模式：`timeout.lg`

心跳返回 `5031` 表示正常，`5033` 表示用户冻结，`5030` 表示授权到期，`5026` 表示连接超时。
后台“软件配置 → 限开控制”可设置心跳间隔和连接超时时间。

Demo 使用进程级 `BSPHPHeartbeatManager` 持续心跳，因此主页面销毁后插件仍使用同一份授权状态。
插件每帧绘制入口先做快速检查：

```objc
#import "BSPHPPluginDrawingGate.h"

- (void)drawOverlayFrame {
    BSPHP_GUARD_DRAW_OR_RETURN();
    [self drawAuthorizedContent];
}
```

心跳返回冻结、到期或连接失效时，`BSPHPPluginDrawingAllowed()` 立即变为 `NO`。
连续 3 次网络心跳失败也会关闭绘制，避免断网后插件长时间继续运行。
如果需要立即清除已绘制内容，监听 `BSPHPHeartbeatStateDidChangeNotification`：

```objc
[[NSNotificationCenter defaultCenter]
    addObserverForName:BSPHPHeartbeatStateDidChangeNotification
                object:BSPHPHeartbeatManager.sharedManager
                 queue:NSOperationQueue.mainQueue
            usingBlock:^(NSNotification *note) {
    if (![note.userInfo[BSPHPHeartbeatDrawingAllowedKey] boolValue]) {
        [overlay clear];
        [displayLink invalidate];
    }
}];
```

## 3. 移植到其他项目

把下面 10 个文件拖进 Xcode Target：

1. `BSPHPConfig.h`
2. `NetTool.h`
3. `NetTool.m`
4. `BSPHPResponseProcessor.h`
5. `BSPHPResponseProcessor.m`
6. `BSPHPTransportCrypto.h`
7. `BSPHPTransportCrypto.mm`
8. `BSPHPHeartbeatManager.h`
9. `BSPHPHeartbeatManager.m`
10. `BSPHPPluginDrawingGate.h`

项目需要链接系统 `Security.framework`。`BSPHPTransportCrypto.mm` 使用系统 CommonCrypto，
不依赖 AFNetworking 或其他第三方库。

## 4. 处理流程

1. `NetTool` 将业务参数编码后使用随机会话密钥加密。
2. RSA-OAEP 封装会话密钥，HMAC-SHA256 认证请求。
3. 服务端返回后，`NetTool` 验证 HMAC 并完成第一层 AES 解密。
4. `BSPHPResponseProcessor` 检查字段结构。
5. 使用 UTC 每日签名进行每日校验。
6. 恒定时间比较本次 `appsafecode`，检查响应是否对应当前请求。
7. 按 `UIInitializer.mm` 的盐值和 UTC 日密钥机制进行二次解密。

Debug 构建会在 Xcode 控制台输出：`[请求加密前]`、`[请求加密后/最终 HTTP Body]`、
`[响应解密前/原始 HTTP Body]`、`[响应第一层解密后]`、`[每日校验]`、`[每次校验]`、
`[二次解密]`。请求日志包含卡号、密码和设备标识，仅在 `#if DEBUG` 中启用。

## 5. 运行

1. 启动本地服务 `http://127.0.0.1:8091`。
2. 后台制作一张库存卡。
3. 用 Xcode 打开 `shouquan.xcodeproj`。
4. 输入纯卡号或 `卡号----密码`，点击“立即激活”。

真机调试时，把 `127.0.0.1` 换成 Mac 的局域网 IP，并让服务监听局域网地址。
