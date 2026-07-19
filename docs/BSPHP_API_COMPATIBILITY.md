# BSPHP API 命名与模式兼容表

> 调研日期：2026-07-19  
> 参考：[BSPHP 开发者文档](https://www.bsphp.com/chm-22.html)、
> [API 后缀与软件模式说明](https://www.bsphp.com/product-d1e3y9.html)

## 1. 后缀规则

| 后缀 | BSPHP 用途 | MyBsPHP2 对应模式 |
|---|---|---|
| `.ic` | 卡串模式 | `card_login` |
| `.lg` | 账户登录模式 | `account_recharge` |
| `.in` | 软件公用信息 | 两种模式共用 |

后缀由创建软件时选择的模式决定，无需在通信配置中重复编辑。

## 2. 默认登录 API

| 软件模式 | 默认 API | 客户端核心参数 |
|---|---|---|
| 卡密验证限时模式 | `login.ic` | `icid`、`icpwd`、`key`、`appsafecode` |
| 账户注册与充值模式 | `login.lg` | `user`、`pwd`、`key`、`maxoror`、`appsafecode` |

MyBsPHP2 会在创建软件时写入对应默认值，保存软件配置时也会根据模式校正。

## 3. 接口目录规划

### 卡串模式 `.ic`

- `login.ic`：卡密登录。
- `timeout.ic`：会话心跳。
- `getdata.ic`：取验证数据。
- `getinfo.ic`：取卡密用户信息。
- `getdate.ic`：取到期时间。
- `setcarnot.ic`：解除设备绑定。
- `chong.ic`：卡模式续期。
- `cancellation.ic`：注销当前登录。

### 账户模式 `.lg`

- `login.lg`：账户登录。
- `registration.lg`：账户注册。
- `chong.lg`：账户充值/续期。
- `timeout.lg`：会话心跳。
- `getuserinfo.lg`：取账户信息。
- `vipdate.lg`：取到期时间。
- `password.lg`：修改密码。
- `cancellation.lg`：注销当前登录。

### 公用模式 `.in`

- `date.in`：服务器时间。
- `v.in`：软件版本。
- `gg.in`：软件公告。
- `globalinfo.in`：软件全局配置。
- `url.in`：预设地址。

## 4. MyBsPHP2 接入顺序

1. 保持 `login.ic` 的加密登录闭环。
2. W7 账户表与充值状态机接入 `registration.lg`、`login.lg`、`chong.lg`。
3. W6 会话与解绑任务接入 `timeout.ic/.lg`、`cancellation.ic/.lg`和 `setcarnot.ic`。
4. 将公告、版本、验证数据映射到 `.in` 公用接口。
5. 保留当前 RSA + AES + HMAC 封包和 nonce 防重放层。

