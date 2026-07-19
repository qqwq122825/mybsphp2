# iOS 通信加密接入

## 使用文件

- `BSPHPTransportCrypto.h`
- `BSPHPTransportCrypto.mm`

工程链接：

- `Security.framework`
- `CommonCrypto`（通过系统 SDK 头文件引入）

最低建议 iOS 10。客户端只保存软件配置页复制出的服务器公钥和通信认证 Key；服务器
私钥保留在服务端。

## 请求

```objc
NSError *error = nil;
NSDictionary *sealed = [BSPHPTransportCrypto sealRequest:@"api=login.ic&icid=卡号&key=设备号"
                                             publicKeyPEM:BSPHP_SERVER_PUBLIC_KEY
                                                mutualKey:BSPHP_MUTUALKEY
                                                    error:&error];

NSDictionary *envelope = sealed[@"envelope"];
NSData *sessionKey = sealed[@"sessionKey"];
NSString *requestNonce = sealed[@"requestNonce"];

NSData *body = [NSJSONSerialization dataWithJSONObject:envelope options:0 error:&error];
```

把 JSON 作为 `POST` 请求体发送到 `BSPHP_HOST`。`sessionKey` 和 `requestNonce` 只保留到
对应响应完成，不写入磁盘。

## 响应

```objc
NSDictionary *responseEnvelope = [NSJSONSerialization JSONObjectWithData:responseData
                                                                   options:0
                                                                     error:&error];
NSString *plainResponse = [BSPHPTransportCrypto openResponse:responseEnvelope
                                                  sessionKey:sessionKey
                                                requestNonce:requestNonce
                                                       error:&error];
```

`plainResponse` 解密成功后仍沿用 Legacy 字段位置，例如 `a[2]` 为软件“验证数据”。

## 协议

- RSA-2048 OAEP：客户端用服务器公钥封装每次请求随机生成的 32 字节会话密钥。
- AES-256-CBC：加密请求和响应正文，每包使用新的 16 字节 IV。
- HMAC-SHA256：校验密文和固定顺序字段，先校验再解密。
- 时间戳：服务端默认接受前后 300 秒。
- Nonce：网关接入时写入一次性缓存，重复 nonce 直接拦截。
- Base64URL：二进制字段统一使用无填充 Base64URL。

旧版客户端继续使用配置页中的 18 位兼容密钥和 3DES 模式；新客户端默认使用 v2
混合加密。
