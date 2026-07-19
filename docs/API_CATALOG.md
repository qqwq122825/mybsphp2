# MyBsPHP2 首版 API 目录

> 任务：W0-7  
> 状态：**DONE（设计冻结，业务代码尚未开始）**  
> 依据：`AI_MASTER_PLAN.md`、`DEVELOPMENT_SPEC_DRAFT.md`、旧版
> `AppEn.php/include/applibapi` 与 iOS `UIInitializer.mm`。

## 1. 目的与边界

本目录是 W1～W10 编写路由、Service、Policy、Feature/Contract 测试时的首版契约索引。
同一业务只实现一次 Domain/Application Service，后台网页接口、REST v2 和 Legacy v1
均调用该 Service，不复制卡密登录、账户充值、余额提卡等业务。

首版包含：

- 首次安装、动态后台路径（默认 `admin`）。
- 管理员认证、可选二级密码、RBAC 与审计。
- 应用、Legacy 参数、响应档案和风险策略。
- 卡种、批次、卡密、导入导出及手动发卡。
- 卡密直接登录模式。
- 账户注册、登录和充值卡充值模式。
- 代理余额、代理价格、自助提卡和细粒度权限。
- 在线会话、登录事件、风险事件。
- `/AppEn.php` Legacy v1 和 `/api/v2` 标准 JSON。

首版明确不包含：

- 公开下单、支付、支付回调、退款、补单和自动发货。
- 邮箱字段、邮件、短信、邮箱验证码、短信验证码和图形验证码。
- 邀请推广、佣金结算和默认多级代理。

账户注册只接收用户名、密码、密码确认和可选设备号；旧参数 `mail`、`mobile`、`img`
不进入首版数据模型，也不作为注册成功条件。

## 2. 统一约定

### 2.1 URL 与版本

| 类型 | 基础地址 | 说明 |
|---|---|---|
| 安装 | `/install` | 仅未安装状态可用 |
| 后台 Web/API | `/{ADMIN_PATH}`、`/{ADMIN_PATH}/api` | `ADMIN_PATH` 默认 `admin`，安装时可重写 |
| 代理 Web/API | `/agent`、`/agent/api` | Session + RBAC |
| Legacy v1 | `/AppEn.php` | 保留旧客户端调用形态 |
| 客户端 REST v2 | `/api/v2` | 新客户端标准 JSON |

后台路径只改变外部路由前缀，内部控制器、权限名和审计资源名保持稳定。路径仅允许
字母、数字、`-`、`_`，旧路径变更后不再显示登录页。

### 2.2 认证标记

| 标记 | 含义 |
|---|---|
| `Public` | 无登录态；仍经过应用状态、时间窗、限流和请求审计 |
| `Install` | 仅未安装状态 + 一次性安装会话 |
| `AdminSession` | 管理员 Session、CSRF、后台路径和权限检查 |
| `AgentSession` | 代理 Session、CSRF、权限和数据范围检查 |
| `ClientCredential` | app code/app id + 应用密钥签名或 Legacy `appid/md5/parameter` |
| `LicenseSession` | 卡密或账户登录后颁发的短期会话令牌 |

### 2.3 幂等

- 所有资金、库存、充值和发卡写操作必须在请求头携带
  `Idempotency-Key: <uuid>`。
- 服务端按“调用方 + 路由 + Key + 请求体摘要”保存结果。相同 Key 和相同摘要返回
  首次结果；相同 Key 配不同摘要返回 `409 IDEMPOTENCY_CONFLICT`。
- 导入任务以文件 SHA-256 + 调用方作为第二道去重。
- Legacy 没有请求头保证时，以
  `appid + api + credential + BSphpSeSsL + date + parameter摘要`
  生成请求指纹；充值类动作在业务表上再使用消费卡唯一约束。
- GET/HEAD 天然只读；DELETE 对已经删除/作废的目标返回当前终态，不重复产生流水。

### 2.4 限流档位

下表是首版默认值，最终值允许按应用/环境配置，但测试必须覆盖阈值边界：

| 档位 | 默认规则 | 适用 |
|---|---|---|
| `install-read` | 60 次/分钟/IP | 安装检查 |
| `install-write` | 10 次/10 分钟/IP | 数据库测试和完成安装 |
| `auth-strict` | 5 次/10 分钟/账号+IP，失败递增延迟 | 管理员、代理、账户登录 |
| `client-login` | 20 次/分钟/凭证，60 次/分钟/IP | 卡密/账户客户端登录 |
| `client-heartbeat` | 12 次/分钟/会话 | 心跳 |
| `write-normal` | 30 次/分钟/主体 | 后台一般写操作 |
| `write-sensitive` | 10 次/分钟/主体 | 发卡、充值、余额、解绑、密钥轮换 |
| `read-normal` | 120 次/分钟/主体 | 后台列表和详情 |
| `export` | 3 次/分钟/主体 | TXT/CSV 导出 |

应用风险策略可以在 `client-login` 之前进一步收紧，但不得放宽平台硬上限。

### 2.5 REST v2 成功和错误结构

成功：

```json
{
  "ok": true,
  "data": {},
  "meta": {
    "request_id": "01J...",
    "server_time": "2026-07-18T12:00:00Z"
  }
}
```

失败：

```json
{
  "ok": false,
  "error": {
    "code": "LICENSE_EXPIRED",
    "message": "授权已到期",
    "details": {
      "field": "card"
    }
  },
  "meta": {
    "request_id": "01J...",
    "server_time": "2026-07-18T12:00:00Z"
  }
}
```

规则：

- `code` 是稳定、全大写的机器码；客户端不得依赖可变的 `message`。
- 字段校验使用 `422 VALIDATION_FAILED`，`details.fields` 给出字段错误。
- 未登录 `401`、无权限或超出代理数据范围 `403`、不存在 `404`、状态冲突
  `409`、超限 `429`、服务异常 `500`。
- 所有响应带 `X-Request-Id`；审计、风险事件和服务端日志使用同一个请求 ID。
- 敏感字段（密码、二级密码、应用密钥、完整卡密）不写入错误详情或普通日志。

首版稳定业务错误码至少包括：

`APP_DISABLED`、`APP_MODE_MISMATCH`、`INVALID_CREDENTIAL`、
`LICENSE_NOT_FOUND`、`LICENSE_UNISSUED`、`LICENSE_FROZEN`、
`LICENSE_REVOKED`、`LICENSE_EXPIRED`、`DEVICE_MISMATCH`、
`SESSION_EXPIRED`、`ACCOUNT_FROZEN`、`RECHARGE_ALREADY_USED`、
`INSUFFICIENT_BALANCE`、`INSUFFICIENT_STOCK`、`RISK_BLOCKED`、
`RATE_LIMITED`、`IDEMPOTENCY_CONFLICT`、`PERMISSION_DENIED`。

## 3. 安装 API

| 任务 ID | Method / Path | 认证 | 用途 | 幂等 / 限流 | 关键测试 |
|---|---|---|---|---|---|
| W2-1 | `GET /install/status` | Public | 判断安装状态；已安装只返回状态，不泄露配置 | 只读 / `install-read` | 未安装、已安装、安装锁损坏 |
| W2-2 | `GET /install/requirements` | Install | PHP、扩展、目录权限、HTTPS 建议 | 只读 / `install-read` | 必需项缺失、建议项缺失、字段脱敏 |
| W2-3 | `POST /install/database/test` | Install | 测试 MySQL；可选检查建库权限 | 相同请求可重试 / `install-write` | 成功、拒绝、库不存在、错误不回显密码 |
| W2-4 | `POST /install/site/validate` | Install | 校验站点名、URL、时区和后台路径 | 相同请求可重试 / `install-write` | 默认 `admin`、自定义路径、保留字、非法字符 |
| W2-6 | `POST /install/admin/validate` | Install | 校验超级管理员账号、密码及可选二级密码策略 | 相同请求可重试 / `install-write` | 弱密码、两密码相同、二级密码关闭 |
| W2-10 | `POST /install/complete` | Install | 分阶段完成迁移、管理员、配置和安装锁 | 必须 `Idempotency-Key` / `install-write` | 成功、失败重试、重复提交、并发安装、锁生效 |

`/install/complete` 只在全部步骤通过后提交。管理员与角色绑定使用事务；失败时清理临时
配置且不写安装完成信号。MySQL Migration 允许保留为可重试状态，下次提交先核对已执行
版本，页面始终将其识别为“安装未完成”。

## 4. 管理员认证、RBAC 与系统 API

| 任务 ID | Method / Path（前缀 `/{ADMIN_PATH}`） | 认证 | 用途 | 幂等 / 限流 | 关键测试 |
|---|---|---|---|---|---|
| W2-7～9 | `POST /api/auth/login` | Public | 账号密码登录；策略开启时校验二级密码 | 非幂等 / `auth-strict` | 正确、错误、冻结、二级密码开关、失败延迟、Session 轮换 |
| W2-7 | `POST /api/auth/logout` | AdminSession | 注销当前 Session | 终态幂等 / `write-normal` | CSRF、重复注销、Session 失效 |
| W2-7 | `GET /api/auth/me` | AdminSession | 当前管理员、角色、权限 | 只读 / `read-normal` | 未登录、权限快照、不返回密码字段 |
| W3-8 | `GET/POST /api/admins` | `admins.view/create` | 管理员列表、创建 | POST 需 Key / 读写档位 | 唯一账号、权限、密码散列、审计 |
| W3-8 | `GET/PATCH /api/admins/{admin}` | `admins.view/update` | 管理员详情与资料/状态修改 | PATCH 需 Key / `write-normal` | 乐观锁、自冻结保护、越权 |
| W3-8 | `POST /api/admins/{admin}/password` | `admins.password.reset` | 重置管理员密码 | 需 Key / `write-sensitive` | 强密码、Session 全失效、审计脱敏 |
| W3-8 | `POST /api/admins/{admin}/secondary-password` | `admins.secondary.reset` | 设置/清除可选二级密码 | 需 Key / `write-sensitive` | 策略关闭、散列、清除权限 |
| W3-2～4 | `GET/POST /api/roles` | `roles.view/create` | 角色列表、创建 | POST 需 Key / 读写档位 | 唯一名、系统角色保护 |
| W3-2～5 | `GET/PATCH/DELETE /api/roles/{role}` | 对应 `roles.*` | 角色维护 | 写操作需 Key / `write-normal` | 被使用角色、系统角色、重复删除 |
| W3-2～5 | `PUT /api/roles/{role}/permissions` | `roles.permissions.update` | 替换角色权限集合 | 需 Key / `write-sensitive` | 未知权限、最小权限、审计差异 |
| W3-2 | `GET /api/permissions` | `roles.view` | 权限字典与分组 | 只读 / `read-normal` | 稳定权限码、代理禁用项标记 |
| W3-8 | `PUT /api/admins/{admin}/roles` | `admins.roles.update` | 替换管理员角色 | 需 Key / `write-sensitive` | 保留最后一名超级管理员 |
| W3-7 | `GET /api/audit-logs` | `audit.view` | 操作审计分页筛选 | 只读 / `read-normal` | 数据范围、脱敏、分页、时间筛选 |
| W3-7 | `GET /api/audit-logs/{log}` | `audit.view` | 审计详情 | 只读 / `read-normal` | 不可修改、前后快照脱敏 |
| W2-4～5 | `GET/PATCH /api/system/site` | `system.view/update` | 站点名、URL、时区等 | PATCH 需 Key / `write-sensitive` | 后台路径单独变更、配置校验 |
| W2-5 | `POST /api/system/admin-path` | `system.admin_path.update` | 修改外部后台路径 | 需 Key / `write-sensitive` | 新路径即时可用、旧路径 404、当前会话策略 |

W0-8 已确认二级密码为可选全局能力，新安装默认关闭；对被标记为必需的管理员，
主密码与二级密码均成功后才创建 Session。客户端不使用验证码，管理员登录保护由
限流、递增延迟、Session 轮换和审计完成。

## 5. 应用、协议、响应档案与风险策略 API

以下路径均使用 `/{ADMIN_PATH}/api` 前缀。

| 任务 ID | Method / Path | 认证 | 用途 | 幂等 / 限流 | 关键测试 |
|---|---|---|---|---|---|
| W4-2～3 | `GET/POST /apps` | `apps.view/create` | 应用列表/统计、创建应用 | POST 需 Key / 读写档位 | code 唯一、两种业务模式、默认配置 |
| W4-3 | `GET/PATCH /apps/{app}` | `apps.view/update` | 应用详情、名称、模式、状态 | PATCH 需 Key / `write-normal` | 模式变更保护、停用后客户端失败 |
| W4-3 | `POST /apps/{app}/enable`、`/disable` | `apps.status.update` | 启停应用 | 需 Key / `write-sensitive` | 终态幂等、在线会话处理、审计 |
| W4-6 | `GET/PUT /apps/{app}/legacy-settings` | `apps.protocol.view/update` | 参数名、`appid`、`md5`、时间窗、编解码器 | PUT 需 Key / `write-sensitive` | 默认参数、非法适配器、密钥不明文返回 |
| W4-6 | `POST /apps/{app}/credentials/rotate` | `apps.credentials.rotate` | 轮换 REST/Legacy 应用凭据 | 需 Key / `write-sensitive` | 宽限期、旧密钥到期、仅展示一次 |
| W4-7～8 | `GET/POST /apps/{app}/response-profiles` | `profiles.view/create` | 返回档案列表/创建 | POST 需 Key / 读写档位 | 模板变量白名单、默认档案唯一 |
| W4-7～8 | `GET/PATCH/DELETE /apps/{app}/response-profiles/{profile}` | 对应 `profiles.*` | 档案维护 | 写需 Key / `write-normal` | 被引用档案、版本条件、删除保护 |
| W4-7～8 | `POST /apps/{app}/response-profiles/{profile}/preview` | `profiles.preview` | 用模拟输入预览 Legacy/JSON 返回 | 请求无业务写入 / `write-normal` | 真实/空/诱饵 `arr[2]`、转义、字段位置 |
| W4-7 | `PUT /apps/{app}/payload-policy` | `apps.payload.update` | 配置 `arr[2]` 类型：`encrypted_value/empty/constant` 及诱饵值 | 需 Key / `write-sensitive` | 32 位值边界、10 位大写 HEX、空值、伪值 |
| W4-8 | `POST /apps/{app}/daily-signature/preview` | `profiles.preview` | 按 UTC 日期预览每日签名 | 无业务写入 / `write-normal` | UTC 跨日、固定长度、测试向量 |
| W4-9 | `GET/PUT /apps/{app}/binding-policy` | `apps.binding.view/update` | 设备绑定、并发数、换绑规则 | PUT 需 Key / `write-sensitive` | 首绑、禁绑、设备上限、并发边界 |
| W9-0～8 | `GET/PUT /apps/{app}/risk-policy` | `risk.policy.view/update` | 风险开关、窗口、次数/设备阈值、`observe/decoy/block` | PUT 需 Key / `write-sensitive` | 关闭、阈值边界、非法诱饵、策略版本 |
| W9-0～8 | `POST /apps/{app}/risk-policy/simulate` | `risk.policy.simulate` | 模拟请求序列并显示决策，不写真实风险状态 | 无业务写入 / `write-normal` | Observe/Decoy/Block、响应组装前决策 |

响应处理器查找优先级固定为：

```text
App Override > Mode Handler > Core Default Handler
```

- `App Override`：受版本控制的应用扩展处理器。
- `Mode Handler`：`card_login` 或 `account_recharge` 的模式处理器。
- `Core Default Handler`：平台内置通用处理器。
- 未声明支持的 action 不允许靠文件名或用户输入任意加载 PHP。
- 风险引擎在响应组装前返回决策；响应器再选择真实、空、诱饵 `arr[2]` 或错误。

## 6. 卡种、批次、卡密和手动发卡 API

以下路径均使用 `/{ADMIN_PATH}/api` 前缀。

| 任务 ID | Method / Path | 认证 | 用途 | 幂等 / 限流 | 关键测试 |
|---|---|---|---|---|---|
| W5-1 | `GET/POST /apps/{app}/card-types` | `card_types.view/create` | 卡种列表、创建时长/点数卡种 | POST 需 Key / 读写档位 | 正时长/点数、模式兼容、停用 |
| W5-1 | `GET/PATCH/DELETE /card-types/{type}` | 对应 `card_types.*` | 卡种维护 | 写需 Key / `write-normal` | 已生成卡的变更/删除保护 |
| W5-2～3 | `GET/POST /card-batches` | `batches.view/create` | 批次列表；创建批次并安全随机制卡 | POST 需 Key / `write-sensitive` | 数量上限、唯一性、随机性、事务回滚 |
| W5-2 | `GET/PATCH /card-batches/{batch}` | `batches.view/update` | 批次详情、名称和备注 | PATCH 需 Key / `write-normal` | 汇总数量一致、已封存限制 |
| W5-4 | `POST /card-batches/imports` | `cards.import` | 上传 TXT/CSV，建立预检任务 | Key + 文件 SHA-256 / `write-sensitive` | 编码、重复卡、跨应用、坏行报告、不部分写入 |
| W5-4 | `GET /card-imports/{import}` | `cards.import` | 导入预检/执行结果 | 只读 / `read-normal` | 进度、失败明细、数据范围 |
| W5-4 | `POST /card-imports/{import}/commit` | `cards.import` | 确认导入 | 需 Key / `write-sensitive` | 重复提交、并发唯一约束、全量事务 |
| W5-5 | `GET /cards` | `cards.view` | 卡密分页、筛选、脱敏显示 | 只读 / `read-normal` | 状态、应用/批次、默认脱敏 |
| W5-5 | `GET /cards/{card}` | `cards.view` | 卡密详情和状态历史 | 只读 / `read-normal` | 完整卡密查看单独权限、日志脱敏 |
| W5-5 | `POST /cards/{card}/freeze`、`/unfreeze`、`/revoke` | 对应 `cards.*` | 卡密状态操作 | 需 Key / `write-sensitive` | 状态机合法性、终态幂等、审计 |
| W6-7 | `POST /cards/{card}/unbind` | `cards.unbind` | 管理员解绑/换绑审批动作 | 需 Key / `write-sensitive` | 代理默认 403、Session 失效、审计 |
| W5-6 | `POST /card-issues` | `cards.issue` | 单张手动发给用户/代理/线下客户 | 需 Key / `write-sensitive` | 库存锁、归属、状态、重复提交、发卡记录 |
| W5-7～8 | `POST /card-issues/bulk` | `cards.issue.bulk` | 批量手动发卡 | 需 Key / `write-sensitive` | 数量、原子性、库存不足、收件目标 |
| W5-10 | `GET /card-issues`、`GET /card-issues/{issue}` | `card_issues.view` | 发卡记录及详情 | 只读 / `read-normal` | 操作人/领取人/备注、数据范围 |
| W5-9 | `POST /card-exports` | `cards.export` | 按筛选创建 TXT/CSV 导出 | 需 Key / `export` | 权限、水印/审计、CSV 注入转义、数量上限 |
| W5-9 | `GET /card-exports/{export}` | `cards.export` | 获取导出状态或一次性下载地址 | 只读 / `export` | 过期、归属、重复下载策略 |

完整卡密查看、复制和导出分别使用 `cards.secret.view`、`cards.secret.copy`、
`cards.export` 权限。代理默认不具备删卡、作废、解绑和查看非自身卡密的权限。

## 7. 账户注册、登录和充值 API

### 7.1 后台账户维护

路径使用 `/{ADMIN_PATH}/api` 前缀。

| 任务 ID | Method / Path | 认证 | 用途 | 幂等 / 限流 | 关键测试 |
|---|---|---|---|---|---|
| W7-8 | `GET /accounts`、`GET /accounts/{account}` | `accounts.view` | 账户列表/详情 | 只读 / `read-normal` | 应用隔离、密码不返回、绑定信息 |
| W7-8 | `PATCH /accounts/{account}` | `accounts.update` | 状态、备注等受控字段 | 需 Key / `write-normal` | 代理默认 403、乐观锁、审计 |
| W7-8 | `POST /accounts/{account}/freeze`、`/unfreeze` | `accounts.freeze` | 冻结/解冻 | 需 Key / `write-sensitive` | 在线 Session 失效、终态幂等 |
| W7-7～8 | `POST /accounts/{account}/unbind` | `accounts.unbind` | 解绑设备 | 需 Key / `write-sensitive` | 代理默认 403、设备历史保留 |
| W7-8 | `POST /accounts/{account}/password` | `accounts.password.reset` | 管理员重置密码 | 需 Key / `write-sensitive` | 强密码、Session 失效、审计脱敏 |
| W7-3～4 | `GET /recharge-records`、`GET /recharge-records/{record}` | `recharges.view` | 充值流水查询 | 只读 / `read-normal` | 卡与账户关联、幂等键、数据范围 |

### 7.2 客户端 REST v2

| 任务 ID | Method / Path | 认证 | 用途 | 幂等 / 限流 | 关键测试 |
|---|---|---|---|---|---|
| W7-1 | `POST /api/v2/apps/{app}/accounts/register` | ClientCredential | 用户名密码注册；可选首次绑定设备 | 需 Key / `auth-strict` | 用户名唯一、密码规则、无邮箱/验证码、设备重复 |
| W7-2 | `POST /api/v2/apps/{app}/accounts/login` | ClientCredential | 账户登录并创建 LicenseSession | 请求指纹去重 / `client-login` | 正确、错误、冻结、到期、设备、风控 |
| W7-2 | `POST /api/v2/apps/{app}/accounts/logout` | LicenseSession | 注销当前会话 | 终态幂等 / `write-normal` | 重复注销、会话失效 |
| W7-3～6 | `POST /api/v2/apps/{app}/accounts/recharge` | LicenseSession | 使用充值卡增加时长/点数 | 必须 Key + 消费卡唯一约束 / `write-sensitive` | 成功、已使用、错应用、并发双花、事务回滚 |
| W7-2 | `GET /api/v2/apps/{app}/accounts/me` | LicenseSession | 当前账户授权、余额/点数、到期和设备摘要 | 只读 / `read-normal` | 脱敏、到期边界、应用隔离 |
| W7-7 | `POST /api/v2/apps/{app}/accounts/password` | LicenseSession | 以原密码修改密码 | 需 Key / `write-sensitive` | 原密码错误、强密码、其他 Session 失效 |

充值卡是后台生成并手动发出的库存卡，不产生订单或支付记录。

## 8. 代理、余额、提卡和权限 API

管理员路径使用 `/{ADMIN_PATH}/api`，代理路径使用 `/agent/api`。

| 任务 ID | Method / Path | 认证 | 用途 | 幂等 / 限流 | 关键测试 |
|---|---|---|---|---|---|
| W10-1 | `GET/POST /agent-levels` | `agent_levels.view/create` | 代理等级 | POST 需 Key / 读写档位 | 唯一名、默认等级、删除保护 |
| W10-1～2 | `GET/PATCH/DELETE /agent-levels/{level}` | 对应 `agent_levels.*` | 等级和状态维护 | 写需 Key / `write-normal` | 已使用等级、停用 |
| W10-2 | `PUT /agent-levels/{level}/prices` | `agent_prices.update` | 按应用/卡种设置代理价 | 需 Key / `write-sensitive` | 非负价格、缺省价、版本并发 |
| W10-0～1 | `GET/POST /agents` | `agents.view/create` | 代理列表、创建账号 | POST 需 Key / 读写档位 | 唯一账号、等级、默认权限 |
| W10-1 | `GET/PATCH /agents/{agent}` | `agents.view/update` | 代理详情、等级和状态 | PATCH 需 Key / `write-normal` | 冻结、Session 失效、数据范围 |
| W10-3 | `POST /agents/{agent}/wallet/credits` | `agents.wallet.credit` | 管理员手动加/减代理余额 | 必须 Key / `write-sensitive` | Decimal 精度、余额不足、并发、双录流水、审计 |
| W10-3 | `GET /agents/{agent}/wallet/ledger` | `agents.wallet.view` | 管理员查看余额流水 | 只读 / `read-normal` | 余额可重算、分页、脱敏 |
| W10-6 | `GET/PUT /agents/{agent}/permissions` | `agents.permissions.view/update` | 代理个人权限覆盖 | PUT 需 Key / `write-sensitive` | 禁止越过平台硬禁项、差异审计 |
| W10-0 | `POST /agent/api/auth/login` | Public | 代理账号密码登录 | 非幂等 / `auth-strict` | 正确、冻结、限流、Session 轮换 |
| W10-0 | `POST /agent/api/auth/logout`、`GET /agent/api/auth/me` | AgentSession | 注销/当前代理 | 注销终态幂等 / 对应档位 | Session、权限快照 |
| W10-2 | `GET /agent/api/catalog` | `agent.catalog.view` | 当前代理可见应用、卡种、价格和库存摘要 | 只读 / `read-normal` | 等级价、停用项、不可见应用 |
| W10-4 | `POST /agent/api/card-withdrawals` | `agent.cards.withdraw` | 用余额自助提卡 | 必须 Key / `write-sensitive` | 余额/库存并发锁、价格快照、原子流水、重复请求 |
| W10-4～5 | `GET /agent/api/card-withdrawals`、`GET /agent/api/card-withdrawals/{id}` | `agent.cards.view` | 自身提卡记录及卡密 | 只读 / `read-normal` | 只能查看自身、卡密展示权限 |
| W10-5 | `POST /agent/api/card-issues` | `agent.cards.issue` | 将自身库存卡手动发给线下客户/允许的用户 | 需 Key / `write-sensitive` | 仅自身库存、状态、发卡记录 |
| W10-3 | `GET /agent/api/wallet`、`GET /agent/api/wallet/ledger` | `agent.wallet.view` | 自身余额与流水 | 只读 / `read-normal` | 只能查看自身、余额一致 |

代理首版默认权限：

- 允许：查看授权目录和价格、自助提卡、查看自身库存/提卡/发卡记录、查看自身余额。
- 禁止：删卡/作废卡、解绑设备、管理用户账户、重置用户密码、修改应用协议或风险策略、
  修改代理余额、查看其他代理数据。
- 是否允许代理向线下客户发卡由 `agent.cards.issue` 单独开关。
- W0-9 已确认首版按一级代理建模；API 不暴露创建下级代理接口。

## 9. 在线会话、风险与审计 API

| 任务 ID | Method / Path | 认证 | 用途 | 幂等 / 限流 | 关键测试 |
|---|---|---|---|---|---|
| W6-6/W8-10 | `POST /api/v2/apps/{app}/sessions/heartbeat` | LicenseSession | 续活并返回授权状态 | 请求指纹去重 / `client-heartbeat` | 过期、冻结、设备、并发上限、风险决策 |
| W6-6 | `DELETE /api/v2/apps/{app}/sessions/current` | LicenseSession | 注销当前客户端会话 | 终态幂等 / `write-normal` | 重复注销、在线数更新 |
| W6-6/W11-2 | `GET /{ADMIN_PATH}/api/online-sessions` | `sessions.view` | 在线会话分页筛选 | 只读 / `read-normal` | 应用/凭证筛选、过期清理、脱敏 |
| W6-7 | `DELETE /{ADMIN_PATH}/api/online-sessions/{session}` | `sessions.terminate` | 管理员强制下线 | 终态幂等 / `write-sensitive` | 代理默认 403、下次心跳失败、审计 |
| W6-8 | `GET /{ADMIN_PATH}/api/login-events` | `login_events.view` | 登录成功/失败事件 | 只读 / `read-normal` | 请求 ID、设备/IP 脱敏、筛选 |
| W9-9 | `GET /{ADMIN_PATH}/api/risk-events` | `risk.events.view` | 风险事件分页筛选 | 只读 / `read-normal` | Observe/Decoy/Block、策略版本、数据范围 |
| W9-9 | `GET /{ADMIN_PATH}/api/risk-events/{event}` | `risk.events.view` | 风险判定证据和响应摘要 | 只读 / `read-normal` | 不泄露密钥/完整卡密、请求关联 |
| W9-9 | `POST /{ADMIN_PATH}/api/risk-events/{event}/review` | `risk.events.review` | 标记已复核、误报或确认 | 需 Key / `write-normal` | 状态机、备注、审计 |
| W9-9 | `POST /{ADMIN_PATH}/api/risk-subjects/{type}/{id}/block`、`/unblock` | `risk.subjects.block` | 人工阻断/解除卡密、账户或设备 | 需 Key / `write-sensitive` | 类型白名单、会话失效、终态幂等 |

同一卡密短时间多次登录、短时间多设备和重放指纹均产生风险事件。`observe` 继续正常
业务；`decoy` 保持成功外形但替换 `arr[2]`；`block` 返回稳定错误。每次决策都记录
策略版本、计数窗口和请求 ID。

## 10. Legacy v1 `/AppEn.php`

### 10.1 网关请求

```http
POST /AppEn.php?appid=APP_ID&md5=APP_MD5
Content-Type: application/x-www-form-urlencoded

json=ok&parameter=ENCODED_OR_ENCRYPTED_QUERY_STRING
```

`parameter` 解包后的公共参数：

```text
api=login.ic
BSphpSeSsL=SESSION_VALUE
date=yyyy-MM-dd#HH:mm:ss
mutualkey=MUTUAL_KEY
icid=CARD
icpwd=
key=DEVICE_ID
maxoror=DEVICE_ID
appsafecode=ECHO_VALUE
nonce=OPTIONAL_NONCE
```

网关必须依次完成：应用定位、应用状态、请求解码、时间窗、通信 Key、请求指纹/Nonce、
action 白名单和处理器分派。`api` 只允许目录中声明的逻辑名，不直接拼接磁盘路径。

### 10.2 固定外层 JSON

Legacy 成功和业务失败均保持相同外层，HTTP 层可对网关级错误使用相应状态码：

```json
{
  "response": {
    "data": "CUSTOM_PIPE_STRING_OR_LEGACY_ERROR",
    "date": "2026-07-18 12:00:00",
    "unix": "1784376000",
    "microtime": "1784376000.123456"
  }
}
```

字段名 `response.data/date/unix/microtime` 固定，不重命名、不改变层级。响应加密器若启用，
对配置所规定的响应载荷执行处理，但解密后必须得到此契约。

### 10.3 `login.ic` 固定竖线位置

成功 `response.data`：

```text
01|xx|{payload}|{appsafecode}|{daily_signature}|1|{expires_at}|1|{device_id}||
```

| 位置 | 固定含义 | 约束 |
|---|---|---|
| `arr[0]` | 成功标记 | 固定 `01` |
| `arr[1]` | 客户端识别标记 | 固定包含 `xx`，首版固定 `xx` |
| `arr[2]` | 应用业务值 | 真实 10 位大写 HEX、应用配置常量、空串或风险诱饵值 |
| `arr[3]` | `appsafecode` | 对本次请求原样回传；不把它当作服务端秘密 |
| `arr[4]` | UTC 每日签名 | 同一应用/UTC 日按档案生成，契约测试固定 |
| `arr[5]` | 保留成功位 | 固定 `1` |
| `arr[6]` | 到期时间 | `YYYY-MM-DD HH:mm:ss`；永久授权按档案固定表达 |
| `arr[7]` | 保留成功位 | 固定 `1` |
| `arr[8]` | 绑定设备号 | 首绑完成后必须返回更新后的设备号 |
| `arr[9..10]` | 保留空位 | 结尾保持 `||` |

`arr[2]` 用于 GWorld 地址等应用自定义 32 位业务值时，默认档案输出
`[1-byte salt][4-byte ciphertext]` 共 10 位十六进制。无需地址的应用可配置空串。
风险 `decoy` 只替换 `arr[2]`，其余位置不漂移。风险判断发生在该字符串组装前。

固定 Contract 测试必须使用 `UIInitializer.mm` Fixture 证明：

1. `response.data` 可按 `|` 分割且位置稳定。
2. `arr[2]` 真实值为 10 位 HEX 时可被客户端测试向量解码。
3. `arr[3]` 与请求 `appsafecode` 完全一致。
4. UTC 跨日时 `arr[4]` 使用正确日期。
5. 首次绑定时 `arr[8]` 不为空且等于请求设备号。
6. 重复请求不重复激活，风险处置只改变规定字段或返回规定错误。

### 10.4 首版 Legacy action 集合

所有 action 使用 `POST /AppEn.php`，认证均为 `ClientCredential`；表中限流是额外档位。

| 任务 ID | `api` 名 | 用途 / REST v2 等价接口 | 幂等 / 限流 | 关键测试 |
|---|---|---|---|---|
| W8-2～9 | `login.ic` | 卡密登录；`POST .../licenses/login` | 指纹去重 / `client-login` | 固定 arr、首激活/首绑、过期、风险、iOS Fixture |
| W8-2～9 | `timeout.ic` | 卡密心跳；`POST .../sessions/heartbeat` | 指纹去重 / `client-heartbeat` | 登录态、过期、冻结、并发 |
| W8-2～9 | `cancellation.ic` | 卡密注销；`DELETE .../sessions/current` | 终态幂等 / `write-normal` | 重复注销、在线数 |
| W8-2～9 | `getdate.ic` | 查询当前卡密到期时间；授权摘要 GET | 只读 / `read-normal` | 到期格式、无会话 |
| W8-2～9 | `getinfo.ic` | 按响应档案查询卡密授权摘要 | 只读 / `read-normal` | 模板变量白名单、脱敏 |
| W8-2～9 | `login.lg` | 账户登录；`POST .../accounts/login` | 指纹去重 / `client-login` | 账户模式、设备、冻结、风险 |
| W8-2～9 | `registration.lg` | 账户注册；`POST .../accounts/register` | 指纹去重 / `auth-strict` | 仅用户名密码、无邮箱/验证码、重复账号 |
| W8-2～9 | `chong.lg` | 登录账户使用充值卡；`POST .../accounts/recharge` | 消费卡唯一 + 指纹 / `write-sensitive` | 时长/点数、双花、事务 |
| W8-2～9 | `timeout.lg` | 账户心跳；`POST .../sessions/heartbeat` | 指纹去重 / `client-heartbeat` | 到期、冻结、会话 |
| W8-2～9 | `cancellation.lg` | 账户注销；`DELETE .../sessions/current` | 终态幂等 / `write-normal` | 重复注销 |
| W8-2～9 | `getuserinfo.lg` | 查询当前账户授权摘要；`GET .../accounts/me` | 只读 / `read-normal` | 只允许档案字段、无邮箱/密保字段 |
| W8-2～9 | `userkey.lg` | 查询当前账户绑定设备号 | 只读 / `read-normal` | 无绑定、设备脱敏策略 |
| W8-2～9 | `password.lg` | 当前账户修改密码；`POST .../accounts/password` | 指纹去重 / `write-sensitive` | 原密码、强密码、Session 失效 |
| W8-2～9 | `pointsdeduction.ic` | 卡密点数模式原子扣点 | 指纹 + 扣点事务 / `write-sensitive` | 正数/上限、余额不足、并发 |
| W8-2～9 | `pointsdeduction.lg` | 账户点数模式原子扣点 | 指纹 + 扣点事务 / `write-sensitive` | 正数/上限、余额不足、并发 |
| W8-2～9 | `gg.in` | 获取应用公告；REST 应用公开配置 | 只读 / `read-normal` | 停用应用、档案转义 |
| W8-2～9 | `v.in` | 获取客户端版本策略 | 只读 / `read-normal` | 强更/可选更新、版本比较 |
| W8-2～9 | `globalinfo.in` | 获取白名单内应用公共配置 | 只读 / `read-normal` | 禁止泄露密钥、字段白名单 |

旧版下列 action 不进入首版公开白名单：

- `registrationaska.lg`、`setthesecuritycode.lg`、`GetPleaseregister.lg`：
  依赖密保、验证码或找回流程。
- `gettaobao.lg`、`socard.in`、`pushaddmoney.in`：依赖购买/支付语义。
- `Special_AddNewUsers.lg`、`Special_GetLoginUsersList.lg`：由后台 RBAC API 取代。
- `runkey.ic`、`AddCardFeatures.key.ic`、`setcarnot.ic`、`setcaron.ic`、
  `remotecancellation.*`：由受权限保护的后台解绑/会话终止 API 取代。
- 其余旧文件名不自动获得兼容资格；需要后续任务逐项写契约和测试后才可加入白名单。

### 10.5 Legacy 错误

业务错误保持外层 JSON，`response.data` 返回 Legacy 错误码映射；映射表必须一对一指向
统一业务错误码。例如 `LICENSE_EXPIRED -> 5030`。未知异常不得返回 PHP 堆栈、SQL 或路径，
而返回通用 Legacy 服务错误并用 `request_id` 在服务端关联（请求 ID 同时放响应头）。

## 11. REST v2 等价客户端接口总表

| 任务 ID | Method / Path | 认证 | Legacy 等价 | 关键响应 |
|---|---|---|---|---|
| W8-10 | `POST /api/v2/apps/{app}/licenses/login` | ClientCredential | `login.ic` | `session_token`、`payload`、`appsafecode`、签名、到期、设备、风险决策摘要 |
| W8-10 | `GET /api/v2/apps/{app}/licenses/current` | LicenseSession | `getdate.ic/getinfo.ic` | 授权状态、到期、剩余点数、绑定设备摘要 |
| W8-10 | `POST /api/v2/apps/{app}/sessions/heartbeat` | LicenseSession | `timeout.ic/timeout.lg` | 当前授权状态、下次心跳建议 |
| W8-10 | `DELETE /api/v2/apps/{app}/sessions/current` | LicenseSession | `cancellation.ic/.lg` | 注销终态 |
| W8-10 | `POST /api/v2/apps/{app}/accounts/register` | ClientCredential | `registration.lg` | 账户 ID、授权摘要 |
| W8-10 | `POST /api/v2/apps/{app}/accounts/login` | ClientCredential | `login.lg` | `session_token`、账户授权摘要 |
| W8-10 | `GET /api/v2/apps/{app}/accounts/me` | LicenseSession | `getuserinfo.lg/userkey.lg` | 账户、到期/点数、设备摘要 |
| W8-10 | `POST /api/v2/apps/{app}/accounts/recharge` | LicenseSession | `chong.lg` | 充值前后快照、充值记录 ID |
| W8-10 | `POST /api/v2/apps/{app}/accounts/password` | LicenseSession | `password.lg` | 密码更新时间、Session 处理 |
| W8-10 | `POST /api/v2/apps/{app}/usage/deduct` | LicenseSession | `pointsdeduction.ic/.lg` | 扣点前后余额、流水 ID |
| W8-10 | `GET /api/v2/apps/{app}/public-config` | ClientCredential | `gg.in/v.in/globalinfo.in` | 公告、版本、白名单公共配置 |

REST v2 与 Legacy 的授权状态、到期计算、绑定结果、扣点/充值流水和风险决策必须一致；
差异只存在于协议编解码和返回展示。

## 12. API 开发顺序与验收门

按依赖逐项实现，每个 API 先写契约/测试，再写业务：

1. **W1-4～5 基线**：统一 JSON、异常、请求 ID、审计脱敏、幂等中间件 Fixture。
2. **W2 安装与认证**：安装状态 → 环境/DB 测试 → 完成安装 → 管理员登录/退出。
3. **W3 RBAC**：权限字典 → 角色 → 管理员 → Policy → 审计查询。
4. **W4 应用协议**：应用 → Legacy 设置 → 返回档案 → `arr[2]` → 绑定/风险策略。
5. **W5 库存**：卡种 → 批次/生成 → 导入 → 状态机 → 手动发卡 → 导出。
6. **W6 卡密服务**：卡密验证 → 首激活 → 首绑定 → 会话/心跳 → 解绑。
7. **W7 账户服务**：注册 → 登录 → 充值 → 幂等/并发 → 账户维护。
8. **W8 Legacy/REST**：网关解包 → 处理器优先级 → 固定外壳/arr → iOS Fixture →
   REST v2 等价测试。
9. **W9 风险**：时间窗/Nonce/指纹 → 次数/设备规则 → Observe/Decoy/Block →
   风险后台。
10. **W10 代理**：等级/价格 → 余额 → 提卡 → 数据范围 → 代理手机端。

单项 API 的完成定义：

- 契约已写明请求、响应、认证、权限、幂等和限流。
- Unit/Feature/Contract（按类型）覆盖成功、校验、权限、状态冲突和并发边界。
- 写操作产生正确审计；敏感字段通过日志脱敏测试。
- Legacy 接口通过固定 JSON/竖线字段测试；等价 v2 接口通过同 Service 一致性测试。
- 目标测试及已有全量回归均通过，任务日志更新为 `DONE`。

## 13. W0-7 决策摘要

1. 后台路径默认 `admin`，安装时可改写。
2. Legacy v1 与 REST v2 共用业务 Service。
3. 处理器优先级固定为
   `App Override > Mode Handler > Core Default Handler`。
4. `login.ic` 外层 JSON 和
   `arr[0/1/2/3/4/5/6/7/8]` 位置固定；`appsafecode` 位于 `arr[3]` 原样回传。
5. 首版双模式为卡密直接登录、账户注册/登录后充值。
6. 风险决策在响应组装前完成，支持 Observe/Decoy/Block。
7. 发卡、充值、余额和提卡均强制幂等与事务。
8. 代理默认不具备删卡、解绑、管理账户及跨代理数据权限。
9. 首版无支付、邮箱、邮件、短信或任何验证码 API。
