# 软件客户端 API 接入文档（当前实现）

本文档给软件前端/iOS 客户端使用，只列出当前服务端已经实现并可调用的接口。

## 1. 客户端固定配置

后台「软件配置 → 通信安全」中复制：

- `APP_CODE`：软件代号，例如 `10000001`
- `GATEWAY_TOKEN`：服务器地址中 `md5=` 后的 32 位值
- `COMMUNICATION_KEY`：通信认证 Key
- `SERVER_PUBLIC_KEY`：服务器 RSA 公钥
- `BASE_URL`：服务端地址

AppEn 统一入口：

```text
POST {BASE_URL}/AppEn.php?appid={APP_CODE}&md5={GATEWAY_TOKEN}
Content-Type: application/json
```

客户端先把业务参数编码为 `application/x-www-form-urlencoded` 文本，再使用 RSA-OAEP + AES-256-CBC + HMAC 封装为 Wire v3 JSON 数组。数据包格式详见 `shouquan/WIRE_V3.md`。

## 2. AppEn API 总表

| `api` | 软件模式 | 用途 |
|---|---|---|
| `BSphpSeSsL.in` | 两种模式 | 获取/确认 64 位会话标识 |
| `login.ic` | 卡串验证限时模式 | 卡密登录、首次激活、设备绑定 |
| `timeout.ic` | 卡串验证限时模式 | 卡密会话心跳 |
| `GetPleaseregister.lg` | 账号登录限时模式 | 检查用户名是否存在 |
| `registration.lg` | 账号登录限时模式 | 注册账号 |
| `registrationaska.lg` | 账号登录限时模式 | 注册账号，可同时使用充值卡和邀请码 |
| `login.lg` | 账号登录限时模式 | 账号登录 |
| `chong.lg` | 账号登录限时模式 | 账号充值卡充值 |
| `timeout.lg` | 账号登录限时模式 | 账号会话心跳 |

`.ic` 与 `.lg` 不能混用。卡串模式调用 `.lg`、账号模式调用 `.ic` 会返回 `MODE_MISMATCH` 或 `UNKNOWN_API`。

## 3. 公共参数

| 参数 | 必填 | 说明 |
|---|---:|---|
| `api` | 是 | 上表中的 API 名称 |
| `key` | 按设置 | 设备唯一特征；验证绑定模式下必填，建议使用 Keychain 中持久化 UUID |
| `appsafecode` | 否 | 客户端自定义透传字符串，成功响应中原样返回 |
| `BSphpSeSsL` | 登录/心跳 | 64 位会话标识；先调用 `BSphpSeSsL.in` 获取 |

验证绑定模式下，同一账号或卡密后续必须提交首次绑定的 `key`。免验证模式可留空，多开数量由后台最大并发会话数控制。

## 4. BSphpSeSsL.in：申请会话标识

业务明文：

```text
api=BSphpSeSsL.in
```

也可携带客户端自己生成的 64 位标识：

```text
api=BSphpSeSsL.in&BSphpSeSsL={64_CHAR_HANDLE}
```

解密后的响应是 64 位 `BSphpSeSsL`。登录成功后继续保存并用于心跳。

## 5. login.ic：卡密登录

请求参数：

| 参数 | 必填 | 说明 |
|---|---:|---|
| `api=login.ic` | 是 | 固定值 |
| `icid` | 是 | 卡密号码 |
| `icpwd` | 否 | 当前无独立密码时传空字符串 |
| `key` | 按设置 | 设备特征 |
| `appsafecode` | 否 | 透传数据 |
| `BSphpSeSsL` | 建议 | 64 位会话标识 |

示例明文：

```text
api=login.ic&icid=CARD_CODE&icpwd=&key=DEVICE_ID&appsafecode=IOS&BSphpSeSsL=SESSION_HANDLE
```

成功响应解密后用 `|` 分隔：

```text
01|xx|ENCRYPTED_PAYLOAD|APPSAFECODE|DAILY_SIGNATURE|1|EXPIRES_AT|1|DEVICE|SESSION_HANDLE|
```

| 下标 | 内容 |
|---:|---|
| `0` | `01` 表示成功 |
| `2` | 后台“验证数据”的每日编码值，对应业务数据 `a[2]` |
| `3` | `appsafecode` 原样返回 |
| `4` | 每日校验签名 |
| `6` | ISO 8601 到期时间 |
| `8` | 已绑定设备特征 |
| `9` | 会话标识 |

## 6. GetPleaseregister.lg：检查账号

请求：

```text
api=GetPleaseregister.lg&user=USERNAME
```

响应：

```text
01|账号可注册
```

或：

```text
00|账号已存在
```

## 7. registration.lg / registrationaska.lg：账号注册

两个名称当前进入同一注册逻辑。`registrationaska.lg` 适合携带注册充值卡和邀请码。

| 参数 | 必填 | 说明 |
|---|---:|---|
| `user` | 是 | 3–32 位，以字母开头，可用字母、数字、`_ . -` |
| `pwd` | 是 | 6–72 位密码 |
| `key` | 按设置 | 设备特征 |
| `icid` | 否 | 注册时使用的充值卡 |
| `icpwd` | 否 | 充值卡密码，当前无密码时留空 |
| `invitation_code` | 否 | 邀请码；兼容参数名 `invite`、`invitation`、`u` |
| `appsafecode` | 否 | 透传数据 |
| `BSphpSeSsL` | 建议 | 64 位会话标识 |

示例：

```text
api=registrationaska.lg&user=demo01&pwd=PASSWORD&key=DEVICE_ID&icid=CARD_CODE&icpwd=&invitation_code=INVITE_CODE&appsafecode=IOS&BSphpSeSsL=SESSION_HANDLE
```

新账号的授权时间按以下顺序累计：首次注册赠送 → 注册充值卡 → 邀请赠送。没有任何时长时，账号注册成功但授权状态为已过期。

成功响应：

```text
01|1011|DEVICE|ENCRYPTED_PAYLOAD|EXPIRES_AT|APPSAFECODE|DAILY_SIGNATURE|INVITATION_CODE|USERNAME|SESSION_HANDLE|
```

## 8. login.lg：账号登录

请求：

```text
api=login.lg&user=USERNAME&pwd=PASSWORD&key=DEVICE_ID&appsafecode=IOS&BSphpSeSsL=SESSION_HANDLE
```

成功响应字段与账号注册相同：

```text
01|1011|DEVICE|ENCRYPTED_PAYLOAD|EXPIRES_AT|APPSAFECODE|DAILY_SIGNATURE|INVITATION_CODE|USERNAME|SESSION_HANDLE|
```

账号密码正确但授权到期时返回 `ACCOUNT_EXPIRED`。客户端可提示用户充值，不应当把它显示成密码错误。

## 9. chong.lg：账号充值

请求：

```text
api=chong.lg&user=USERNAME&icid=CARD_CODE&icpwd=&appsafecode=IOS
```

充值卡只能消费一次。成功响应仍使用账号响应格式，并返回充值后的到期时间、余额或点数。

## 10. timeout.ic / timeout.lg：会话心跳

卡串模式：

```text
api=timeout.ic&key=DEVICE_ID&BSphpSeSsL=SESSION_HANDLE&appsafecode=IOS
```

账号模式：

```text
api=timeout.lg&key=DEVICE_ID&BSphpSeSsL=SESSION_HANDLE&appsafecode=IOS
```

成功响应：

```text
CODE|SESSION_HANDLE|APPSAFECODE|SERVER_TIME
```

当前主要状态码：

| `CODE` | 含义 |
|---|---|
| `5031` | 会话和授权正常 |
| `5030` | 授权到期、账号不可用或卡密不可用 |

客户端按后台配置的心跳间隔发送；超过连接超时后，服务端会把旧会话视为失效。

## 11. 软件启动状态 API

此接口不走 AppEn 加密，用于客户端进入登录页之前读取开关、关闭提示、模式、公告和版本：

```http
GET {BASE_URL}/api/v2/software/{APP_CODE}/public-config
```

客户端以 `data.application.is_open` 判断是否开放。为 `false` 时展示 `closed_message` 并停止登录/激活。详细响应见 `docs/CLIENT_PUBLIC_CONFIG_API.md`。

## 12. REST JSON API

若客户端不使用 AppEn 兼容接口，也可调用 REST v2。`{APP_PUBLIC_ID}` 是软件公开 ID。

| Method | Path | 用途 |
|---|---|---|
| `GET` | `/api/v2/apps/{APP_PUBLIC_ID}/public-config` | 公共配置 |
| `POST` | `/api/v2/apps/{APP_PUBLIC_ID}/sessions/handle` | 申请会话标识 |
| `POST` | `/api/v2/apps/{APP_PUBLIC_ID}/sessions/heartbeat` | 会话心跳 |
| `POST` | `/api/v2/apps/{APP_PUBLIC_ID}/accounts/exists` | 检查账号 |
| `POST` | `/api/v2/apps/{APP_PUBLIC_ID}/accounts/register` | 注册账号 |
| `POST` | `/api/v2/apps/{APP_PUBLIC_ID}/accounts/login` | 账号登录 |
| `POST` | `/api/v2/apps/{APP_PUBLIC_ID}/accounts/recharge` | 账号充值 |
| `POST` | `/api/v2/demo/activate` | 卡密激活/登录 |

REST 请求使用 JSON，例如账号登录：

```json
{
  "username": "demo01",
  "password": "PASSWORD",
  "device_id": "DEVICE_ID",
  "appsafecode": "IOS",
  "BSphpSeSsL": "64_CHAR_SESSION_HANDLE"
}
```

REST 成功外壳：

```json
{
  "ok": true,
  "data": {},
  "meta": {
    "request_id": "REQUEST_ID",
    "server_time": "2026-07-22T00:00:00.000000Z"
  }
}
```

REST/AppEn 业务错误外壳：

```json
{
  "ok": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "可直接展示的提示"
  },
  "meta": {
    "request_id": "REQUEST_ID",
    "server_time": "2026-07-22T00:00:00.000000Z"
  }
}
```

## 13. 客户端必须处理的错误码

| 错误码 | 前端处理 |
|---|---|
| `APP_DISABLED` | 展示后台“关闭提示”，停止授权流程 |
| `SERVICE_DISABLED` | 展示系统维护提示，允许稍后重试 |
| `MODE_MISMATCH` | 客户端接口后缀配置错误 |
| `INVALID_CREDENTIALS` | 提示账号或密码错误 |
| `ACCOUNT_EXPIRED` | 凭据正确但授权到期，进入充值界面 |
| `DEVICE_REQUIRED` | 采集并上传稳定设备特征 |
| `DEVICE_MISMATCH` | 提示设备不匹配/申请转绑定 |
| `INVALID_CARD` | 卡号错误 |
| `CARD_FROZEN` | 卡密已冻结 |
| `CARD_UNAVAILABLE` | 卡密已使用或状态不可用 |
| `SESSION_REQUIRED` | 重新调用 `BSphpSeSsL.in` 并登录 |
| `SESSION_INVALID` | 清理旧会话并重新登录 |
| `SESSION_LIMIT_REACHED` | 已达到后台并发上限 |
| `PACKET_REPLAYED` | 重新生成 nonce 后重发一次 |
| `INVALID_PACKET` | 检查时间戳、RSA/AES/HMAC 和 Wire v3 字段 |
| `SERVER_UNAVAILABLE` | 上线运行下统一按临时服务器故障处理；不推断封控状态，不自动高频重试 |
| `ACCESS_TEMPORARILY_RESTRICTED`（仅调试模式默认值） | 根据 `error.details.control_level` 读取高级封控判定及 `Retry-After` |

### 13.1 登录封控响应

账号登录、卡串登录以及 AppEn `login.ic/login.lg` 共用同一套滚动窗口。上线运行时高级封控返回 HTTP 503、`SERVER_UNAVAILABLE` 和统一服务器故障提示，不携带 `details` 或 `Retry-After`。下例是调试模式响应：

```http
HTTP/1.1 429 Too Many Requests
Retry-After: 1800
Content-Type: application/json
```

```json
{
  "ok": false,
  "error": {
    "code": "ACCESS_TEMPORARILY_RESTRICTED",
    "message": "当前请求过于频繁，请稍后再试。",
    "details": {
      "control_level": "high",
      "retry_after_seconds": 1800
    }
  },
  "meta": {
    "request_id": "REQUEST_ID",
    "server_time": "2026-07-23T00:00:00.000000Z"
  }
}
```

调试模式下客户端可依据 `control_level=high` 展示封控诊断。上线运行时客户端只会看到普通服务器故障，应停止密集自动重试并使用常规退避。低级封控始终保持成功字段布局和到期时间；上线运行不增加字段，调试模式额外增加 `data.risk_control`。

## 14. 推荐客户端调用顺序

```text
1. GET public-config
2. is_open=false → 展示 closed_message
3. BSphpSeSsL.in → 保存 64 位会话标识
4. 根据 mode：
   card_login       → login.ic
   account_recharge → GetPleaseregister.lg / registration.lg / login.lg
5. 登录成功后读取到期时间、验证数据和邀请码
6. 定时调用 timeout.ic 或 timeout.lg
7. 收到到期、冻结、设备不符或软件关闭错误后停止授权功能
8. 收到 `control_level=high` 后停止自动重试，按 `Retry-After` 展示剩余等待时间
```

## 15. 现成客户端代码位置

项目内 Objective-C 示例已经实现数据包加密、Wire v3、登录响应拆分与心跳：

```text
shouquan/shouquan/NetTool.m
shouquan/shouquan/BSPHPTransportCrypto.mm
shouquan/shouquan/BSPHPResponseProcessor.m
shouquan/shouquan/UIInitializer.mm
```
