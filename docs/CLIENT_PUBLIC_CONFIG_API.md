# 客户端软件状态与公共配置 API

客户端启动时先请求此接口，再决定进入登录页面、展示公告或显示软件关闭提示。

## 请求

```http
GET /api/v2/software/{APP_CODE}/public-config
Accept: application/json
```

`APP_CODE` 就是客户端配置里已有的软件代号（例如 `10000001`）。此接口只返回公开展示配置，响应中不包含通信认证 Key、服务器私钥或兼容密钥。

已经保存软件公开 ID 的客户端也可使用：

```http
GET /api/v2/apps/{APPLICATION_PUBLIC_ID}/public-config
```

## 正常响应

```json
{
  "ok": true,
  "data": {
    "application": {
      "id": "01K...",
      "code": "10000001",
      "name": "示例软件",
      "mode": "account_recharge",
      "status": "active",
      "is_open": true,
      "closed_message": "系统维护，暂时关闭，请稍后再访问！",
      "version": "v1.0",
      "announcement": "欢迎使用",
      "binding_mode": "verified",
      "max_concurrent_sessions": 1
    }
  },
  "meta": {
    "request_id": "01K...",
    "server_time": "2026-07-22T00:00:00.000000Z"
  }
}
```

## 字段说明

| 字段 | 取值 | 客户端处理 |
|---|---|---|
| `is_open` | `true / false` | 是否允许进入注册、登录或卡密激活流程 |
| `status` | `active` | 正常开放 |
| `status` | `draft` | 草稿，不开放 |
| `status` | `suspended` | 暂停使用，不开放 |
| `status` | `archived` | 已归档，不开放 |
| `closed_message` | 字符串 | `is_open=false` 时直接展示 |
| `mode` | `card_login` | 展示卡密登录界面 |
| `mode` | `account_recharge` | 展示账号注册、登录和充值界面 |
| `binding_mode` | `verified` | 客户端必须上传稳定的 `device_id` / `key` |
| `binding_mode` | `unverified` | 设备特征可留空，多开数量由 `max_concurrent_sessions` 控制 |
| `announcement` | 字符串或 `null` | 非空时展示软件公告 |
| `version` | 字符串或 `null` | 用于显示服务端当前版本 |

## 客户端启动流程

```text
请求 public-config
  ├─ ok=false：展示网络或服务错误，可提供重试按钮
  └─ ok=true
       ├─ is_open=false：展示 closed_message，停止登录/激活
       └─ is_open=true：展示 announcement，按 mode 进入对应登录页
```

前端判断以 `is_open` 为准，不要通过中文提示文字判断状态。

## 软件关闭后的业务接口响应

账号注册、账号登录、账号充值、卡密激活及 AppEn 登录在软件关闭时返回：

```json
{
  "ok": false,
  "error": {
    "code": "APP_DISABLED",
    "message": "系统维护，暂时关闭，请稍后再访问！",
    "details": {
      "application_status": "suspended",
      "is_open": false
    }
  }
}
```

客户端收到 `APP_DISABLED` 后，应退出当前授权流程并展示 `error.message`。卡密接口同样返回 `APP_DISABLED` 和后台配置的关闭提示。

## Objective-C 示例

```objc
BOOL isOpen = [application[@"is_open"] boolValue];
if (!isOpen) {
    NSString *message = application[@"closed_message"] ?: @"软件暂未开放";
    [self showStatusMessage:message];
    return;
}

NSString *mode = application[@"mode"];
if ([mode isEqualToString:@"account_recharge"]) {
    [self showAccountLogin];
} else {
    [self showCardLogin];
}
```
