# MyBsPHP2 开发规格 v1

> 状态：**首版需求已确认，进入正式开发**  
> 依据：已审阅旧版首次安装、后台入口、`AppEn.php`、`include/applibapi`、`login.ic` 以及 iOS `UIInitializer.mm` 调用。

## 1. 当前理解

MyBsPHP2 不只是一个网页发卡平台，还应同时包含：

1. 网站前台、用户中心、代理中心、管理后台。
2. 商品/卡种、批次、库存和后台手动发卡。
3. 软件应用管理、卡密管理、设备绑定和在线验证。
4. 面向客户端的可扩展接口系统。
5. 首次访问时可完成数据库和管理员配置的 Web 安装向导。
6. 兼容旧客户端 `AppEn.php + api=login.ic` 的交互方式。
7. 后台可针对不同应用选择验证模式、加解密、输出格式和定制返回内容。
8. 所有网页均支持手机、平板和电脑。

## 1.1 已确认的首版范围

- 首版采用**手动发卡**。
- 管理员可生成卡密，并发给指定用户、代理或线下客户。
- 支持单张发卡、批量发卡以及 TXT/CSV 批量导出。
- 暂缓公开下单、在线付款、支付回调、退款、补单和自动发货。
- 暂缓支付宝、微信、易支付等支付渠道。
- 首版不采集邮箱，也不发送邮件。
- 首版不包含邮箱验证、短信验证和图形验证码。
- 原版独立的“库卡列表、库卡增减、库卡归零”模块不进入新版。
- 可售数量直接由普通卡密的状态和归属统计，不再建立“库卡账户”概念。
- 管理员、代理和用户使用账号密码登录；登录保护采用频率限制、失败延迟和会话策略。

支付、邮件、验证码及旧版库卡模块不进入首版导航、安装器和验收范围。

## 1.2 已确认的后台、授权与风控要求

- 后台路径默认 `admin`，首次安装时允许改写，例如 `nnnnnnnnnnxxx`。
- 后台路径只改变外部访问路由，内部仍使用统一 Admin 模块。
- 支持两种首版业务模式：
  1. **卡密登录模式**：客户端直接使用卡密登录并绑定设备。
  2. **账户模式**：用户注册/登录账户，再使用充值卡增加账户时长或点数。
- 代理使用细粒度权限；默认不授予删卡、解绑设备、管理用户账户等高影响权限。
- 每个应用可以独立配置 Legacy 响应模板和 `arr[2]` 业务字段。
- `arr[2]` 可用于游戏核心 `GWorld` 地址等应用自定义值；不需要该字段的应用允许返回空字符串。
- 每个应用提供风险开关、时间窗口、次数阈值、设备数阈值和处置方式。
- 同一卡密短时间多次登录可触发观察、阻断或诱饵响应。
- 诱饵响应可以让 `arr[2]` 返回该应用配置的伪值；所有处置均记录风险事件。

## 2. 旧版设计审阅结论

### 2.1 首次安装

旧版流程：

- 根目录 `index.php` 检查 `Data/install.txt`。
- 未安装时跳转 `install/`。
- 安装器检查 PHP 环境和目录权限。
- 填写 MySQL 地址、账号、密码、数据库名和表前缀。
- 导入结构与初始化数据，写数据库配置。
- 创建安装标记，阻止再次安装。
- 安装完成后引导进入后台。

相关文件：

- `示例/index.php`
- `示例/install/index.php`
- `示例/install/install.php`
- `示例/install/template/install.php`
- `示例/install/data/AppEn.mysql.txt`
- `示例/install/data/mysql.data.txt`

**新版保留其“首次访问即可安装”的体验，但重写实现：**

1. 欢迎与使用协议。
2. 环境检测：PHP、扩展、目录权限、HTTPS 建议。
3. 数据库连接测试，可选自动创建数据库。
4. 站点配置：名称、URL、时区。
5. 管理员账号、强密码。
6. 后台访问目录设置。
7. 执行迁移、写 `.env`、创建安装锁。
8. 显示后台地址和后续安全提示。

### 2.2 后台入口

旧版后台入口本质是一个独立入口目录：

```php
const BSPHP_SET = 'ADMIN';
require '../LibBsphp/Global.Bsphp.Inc.php';
```

示例中目录已经从默认 `admin` 改成：

```text
nnnnnnnnnnxxx/
```

新版建议：

- 安装时允许填写后台路径。
- 默认值需要本次需求确认。
- 路径只接受字母、数字、`-`、`_`。
- 实际仍进入统一入口和后台路由，不复制整套后台代码。
- 后台路径保存在环境配置中，修改后路由立即使用。
- 旧地址不暴露后台登录页。

### 2.3 旧版 AppEn 接口分派

旧版入口为：

```text
AppEn.php?appid=APP_ID&md5=APP_MD5
```

请求正文使用：

```text
Content-Type: application/x-www-form-urlencoded
json=ok&parameter=ENCODED_OR_ENCRYPTED_QUERY_STRING
```

`parameter` 解密后仍是 Query String：

```text
api=login.ic
&BSphpSeSsL=SESSION_VALUE
&date=yyyy-MM-dd#HH:mm:ss
&mutualkey=MUTUAL_KEY
&icid=CARD
&icpwd=
&key=DEVICE_ID
&maxoror=DEVICE_ID
&appsafecode=ECHO_VALUE
```

旧版分派过程：

1. 根据 `appid` 读取应用配置。
2. 加载该应用设置的请求解密器、响应加密器和输出编码器。
3. 验证站点/应用开关、连接参数、时间差和通信 Key。
4. 从请求参数读取 `api`，例如 `login.ic`。
5. 转换成处理器文件名 `AppEn.login.ic.php`。
6. 按优先级查找处理器。
7. 执行处理器并通过统一 `PrInfo()` 输出。

旧版处理器优先级非常值得保留：

```text
应用自定义 API 目录
    > 当前授权模式 API 目录
        > Default 通用 API 目录
```

对应旧文件：

- `示例/AppEn.php`
- `示例/include/modules/applib/appen.php`
- `示例/include/modules/applib/common/appen_inc.function.php`
- `示例/include/applibapi/api/Default/`
- `示例/include/applibapi/api/CardPoint/`
- `示例/include/applibapi/api/LoginPoint/`
- `示例/include/applibapi/encryption/`
- `示例/include/applibapi/output/`

### 2.4 `login.ic` 与 iOS 客户端

iOS 调用位置：

- `示例/ios/Ai/Yz/UIInitializer.mm`
- `示例/ios/Ai/Yz/UIInitializer.h`
- `示例/ios/Ai/Yz/NetTool.mm`
- `示例/ios/Ai/Yz/NSDictionary+StichingStringkeyValue.m`

客户端在 `yanzhengAndUseIt` 中明确设置：

```objc
param[@"api"] = @"login.ic";
param[@"BSphpSeSsL"] = [UIInitializer getSystemDate];
param[@"date"] = [UIInitializer getSystemDate];
param[@"mutualkey"] = BSPHP_MUTUALKEY;
param[@"icid"] = km;
param[@"icpwd"] = @"";
param[@"key"] = [UIInitializer serialNumber];
param[@"maxoror"] = [UIInitializer serialNumber];
param[@"appsafecode"] = appsafecode;
```

当前客户端期待外层数据含有：

```json
{
  "response": {
    "data": "CUSTOM_PIPE_STRING",
    "date": "...",
    "unix": "...",
    "microtime": "..."
  }
}
```

随后把 `data` 以 `|` 分割，并依赖固定位置：

| 位置 | 当前客户端用途 |
|---|---|
| `arr[0]` | 成功标记，如 `01` |
| `arr[1]` | 当前代码要求含 `xx` |
| `arr[2]` | 客户端业务字段/加密信息 |
| `arr[4]` | UTC 每日签名 |
| `arr[6]` | 到期时间 |
| `arr[8]` | 服务器返回的绑定机器码 |

这说明用户所说的“后期定制返回结果”是核心需求：同一个 `login.ic` 可以按应用、模式甚至客户端版本返回不同的字段布局和字段值。

### 2.5 当前示例中的协议错位

审阅后发现，当前客户端与 PHP 示例并没有完全对齐，正式开发前必须把目标契约确认清楚：

1. `UIInitializer.mm` 调用的是 `login.ic`，并把 `arr[2]` 作为 **10 位十六进制加密业务值**进行解码。
2. `Default/AppEn.login.ic.php` 当前在 `arr[2]` 返回的是固定值 `1`。
3. 真正生成 `[1 字节 salt + 4 字节密文]` 这类 10 位值的代码位于 `Default/AppEn.weburl.in.php`。
4. 客户端虽然发送 `appsafecode`，当前两个 PHP 文件都没有把它放入返回结果。
5. 首次绑定时 PHP 先执行 `Update_Login_Key()`，但随后仍从更新前的 `$carinfo` 读取绑定值，因此第一次响应可能返回空绑定值。
6. `Default/AppEn.login.ic.php` 对 `CarLogin()` 存在重复调用，目标行为需要合并成一次清晰、幂等的授权流程。
7. `CardTerm` 目录没有自己的 `login.ic`，所以会回落到 `Default`；`CardPoint` 有自己的 `login.ic`，返回字段布局又是另一套。

因此新版不会直接照抄任意一个旧 PHP 返回串，而是先以客户端实际消费字段为准建立一份明确的 **Response Contract**，再用自动化契约测试固定下来。

## 3. 新版接口架构建议

### 3.1 两套协议并存

#### Legacy v1

- 保留 `/AppEn.php?appid=...&md5=...`。
- 保留 `parameter` 请求字段。
- 保留 `api=login.ic` 等点号接口名。
- 可选择旧 JSON 外壳和竖线数据。
- 为现有 iOS 客户端提供兼容。

#### REST v2

- 新地址示例：`POST /api/v2/apps/{appCode}/licenses/login`。
- 标准 JSON 请求与响应。
- 使用明确的错误码、HTTP 状态码、时间戳和请求 ID。
- 新客户端优先接入 v2。

Legacy v1 与 REST v2 最终调用同一套 Service，避免产生两套卡密业务。

### 3.2 新版处理器优先级

```text
App Override
    > Mode Handler
        > Core Default Handler
```

建议代码边界：

```text
src/Modules/Licenses/
├── Application/LicenseLoginService.php
├── Domain/
├── Infrastructure/
└── Protocol/
    ├── Legacy/
    │   ├── LegacyRequestDecoder.php
    │   ├── LegacyResponseEncoder.php
    │   └── Handlers/
    └── V2/

extensions/apps/{APP_CODE}/
├── handlers/
└── response-profiles/
```

扩展包需要清单文件声明支持的 API、版本和权限；后台启用后生效，不直接修改核心文件。

### 3.3 可定制返回

每个应用可以选择“返回配置档案”：

- 输出外壳：Legacy JSON、标准 JSON、纯文本、XML（按需）。
- 业务数据：竖线模板或 JSON 字段映射。
- 字段来源：成功码、授权状态、到期时间、设备号、剩余点数、公告、版本、服务端时间、自定义常量。
- 自定义常量：应用级键值配置。
- 客户端版本条件：不同版本可使用不同返回档案。
- 成功和失败分别设置档案。
- 后台提供“输入模拟参数 → 预览最终响应”。

竖线模板示例：

```text
{success_code}|{marker}|{payload}|1|{daily_signature}|1|{expires_at}|1|{device_id}||
```

第一阶段不让后台直接编辑可执行 PHP；复杂逻辑通过受版本控制的扩展处理器实现。这样仍能定制结果，同时便于更新、排错和回滚。

### 3.4 应用与模式

旧版模式建议先兼容其业务含义，再统一内部模型：

- `CardPoint`：单卡 + 点数。
- `CardTerm`：单卡 + 到期时间。
- `LoginPoint`：账号登录 + 点数。
- `LoginTerm`：账号登录 + 到期时间。

新版内部可使用：

- 凭证类型：`card` / `account`
- 计费类型：`points` / `duration` / `fixed_expiry`

旧模式名作为预设配置保留，不再复制四套相似业务代码。

## 4. 首次安装规格

### 4.1 触发

- 未检测到安装锁时，访问网站跳转 `/install`。
- 已安装时 `/install` 只显示“已安装”，不再接受配置提交。
- 命令行也提供同等安装/迁移能力。

### 4.2 页面步骤

1. 欢迎页。
2. 环境检查。
3. 数据库连接。
4. 站点与后台路径。
5. 管理员账号。
6. 安装进度与结果。

### 4.3 安装数据

- 生成 `APP_KEY`。
- 保存数据库配置到 `.env`。
- 运行全部迁移。
- 创建超级管理员和基础角色。
- 初始化系统设置、默认接口处理器和响应档案。
- 写入 `storage/installed.lock`。
- 显示实际后台地址。

## 5. 管理后台信息架构

### 首页

- 今日手动发卡量、卡密库存、即将到期授权。
- 最近发卡记录、验证请求趋势、异常请求。

### 应用

- 应用信息、应用编号、状态、通信密钥。
- 验证模式、设备绑定策略、并发策略。
- 请求协议、请求解密、响应加密、输出格式。
- API 处理器与返回档案。
- 版本、公告、更新策略。

### 卡密

- 卡种、批次、生成、导入、导出。
- 未售、已售、已激活、冻结、过期、作废。
- 解绑、换绑、延期、扣点和操作记录。

### 手动发卡

- 选择应用、卡种、批次和可用卡密。
- 发给指定用户、指定代理或标记为线下客户。
- 单张发卡、批量发卡和批量导出。
- 记录操作人、领取人、发卡时间、备注和状态变化。
- 支持复制卡号、导出 TXT/CSV 和打印。

### 代理

- 代理等级、折扣/独立价、余额、提卡。
- 下级代理、返佣和流水。

### 系统

- 管理员与权限、日志、备份。
- 后台路径、网站设置和安全策略。

## 6. 手机端要求

- 安装向导、前台、用户中心、代理中心和后台全部响应式。
- 手机后台以卡片列表代替宽表格。
- 批量操作使用底部操作栏。
- 导航使用底栏和抽屉；桌面使用侧边栏。
- 手动发卡、取卡、复制卡密、代理提卡支持单手操作。
- 最小触控区域 44px，适配 iPhone 安全区。
- API 返回配置编辑器在手机上提供简化模式，桌面提供完整预览。

## 7. 初步开发顺序

### 阶段 A：可安装、可登录

- 新安装器。
- 数据库迁移。
- 管理员、角色和登录。
- 可配置后台路径。
- 响应式后台壳。

### 阶段 B：应用与协议

- 应用管理。
- 四种旧模式预设。
- Legacy `/AppEn.php` 网关。
- `login.ic` 默认处理器。
- 输出档案、加解密适配器和响应预览。
- 使用旧 `UIInitializer.mm` 做兼容测试。

### 阶段 C：手动发卡与代理

- 商品、卡种、批次和库存。
- 后台单张/批量手动发卡和导出。
- 发卡记录与状态回溯。
- 代理等级、价格、余额和提卡。

### 阶段 D：授权完整能力

- 激活、心跳、解绑、换绑、点数、到期。
- 版本、公告、更新和维护开关。
- 审计、频率限制与统计。

## 8. 首版协议基线

Legacy v1 和 REST v2 共用业务 Service。第一轮以卡密登录和账户充值两种模式为主，点数/时长通过产品配置表达。

Legacy 成功响应的 `data` 固定位置：

| 位置 | 内容 |
|---|---|
| `arr[0]` | `01` 成功标记 |
| `arr[1]` | `xx` 客户端识别标记 |
| `arr[2]` | 应用业务值；可配置为10位加密值、诱饵值或空 |
| `arr[3]` | 原样返回 `appsafecode` |
| `arr[4]` | UTC 每日签名 |
| `arr[5]` | 保留成功位 `1` |
| `arr[6]` | 到期时间 |
| `arr[7]` | 保留成功位 `1` |
| `arr[8]` | 绑定设备号 |

风险决策必须发生在响应组装前。风险引擎只返回决策，响应器负责按决策选择真实 `arr[2]`、伪值或错误结果，以便审计与测试。

## 9. 确认后的首个可验收版本

需求确认后，首个版本以以下结果为验收点：

1. 新部署访问网站自动进入手机适配的安装向导。
2. 完成数据库、管理员和后台路径设置。
3. 从设置后的后台地址登录。
4. 创建一个应用并选择授权模式。
5. 创建卡种并生成测试卡。
6. 管理员将一张卡手动发给用户或代理，并生成发卡记录。
7. 旧 iOS 示例用 `api=login.ic` 完成一次成功验证。
8. 后台修改响应档案后，测试客户端得到对应的新返回。
