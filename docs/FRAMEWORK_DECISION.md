# MyBsPHP2 PHP 脚手架决策

> 状态：**已确认**  
> 决策原则：先确定框架和边界，再继续写正式业务代码。

确认记录：项目负责人同意按推荐规则开始 W0 收尾，并以 Laravel 13 + PHP 8.3 + MySQL 8 作为正式基线。

## 1. 是否需要脚手架

需要。

MyBsPHP2 包含首次安装、动态后台入口、管理员与代理权限、两种授权模式、卡密状态机、Legacy 协议、风控、日志和大量接口。如果继续使用自制路由与数据库层，后续会持续补登录、验证、迁移、测试、异常处理和命令行工具，维护成本较高。

脚手架负责通用能力，BSPHP 特有业务仍由项目实现。

## 2. 候选方案

| 方案 | 优点 | 主要代价 | 结论 |
|---|---|---|---|
| 自制轻量框架 | 初期文件少、完全自由 | 登录、迁移、中间件、测试和升级均自行维护 | 仅适合协议原型 |
| Symfony 7.4 LTS | 边界严谨、长期支持、组件成熟 | 首版开发速度较慢、后台业务代码更重 | 可选 |
| Laravel 13 | 脚手架完整、迁移/测试/限流/策略成熟、开发效率高 | 本机 PHP 需从 8.1 升级 | **推荐** |

截至 2026 年 7 月，Laravel 13 支持 PHP 8.3–8.5，安全支持到 2028 年 3 月；Symfony 7.4 LTS 支持 PHP 8.2+，安全支持到 2029 年 11 月。项目更看重后台业务开发效率和接口测试体验，因此推荐 Laravel 13。

参考：

- [Laravel 13 发布与支持周期](https://laravel.com/docs/13.x/releases.)
- [Symfony 版本与支持周期](https://symfony.com/releases)

## 3. 推荐技术基线

- PHP 8.3
- Laravel 13
- MySQL 8
- Composer 2
- Blade 服务端模板
- Alpine.js 处理少量交互
- 响应式 CSS；视觉确认后再决定是否引入 Tailwind
- PHPUnit：单元、Feature、Legacy 契约测试
- Laravel Session：管理端、代理端、用户端登录
- Laravel Rate Limiter：后台登录和客户端 API 频率限制
- Laravel Gate/Policy：管理员与代理细粒度权限
- Laravel Migration/Seeder：首次安装与版本升级

首版暂不引入：

- 支付 SDK
- 邮件组件业务
- 图形/短信/邮箱验证码
- Redis 强依赖
- 队列强依赖
- 微服务
- SPA 前端框架

## 4. 正式目录建议

```text
app/
├── Domain/
│   ├── Application/
│   ├── Card/
│   ├── Account/
│   ├── Agent/
│   ├── License/
│   └── Risk/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   ├── Agent/
│   │   ├── Install/
│   │   ├── Legacy/
│   │   └── Api/V2/
│   ├── Middleware/
│   └── Requests/
├── Policies/
└── Support/

database/
├── migrations/
├── seeders/
└── factories/

resources/views/
├── install/
├── admin/
├── agent/
└── user/

routes/
├── web.php
├── api.php
├── legacy.php
└── install.php

tests/
├── Unit/
├── Feature/
├── Contract/
└── Fixtures/
```

## 5. BSPHP 特殊能力的落点

### 首次安装

- `/install` 环境检查、数据库、站点、后台路径和管理员。
- 默认后台路径 `admin`，安装时可改。
- 安装完成写 `.env` 与安装锁。
- 后续数据库变更统一走 migration。

### 动态后台路径

```env
ADMIN_PATH=admin
```

路由在启动时读取该值：

```text
/{ADMIN_PATH}/login
/{ADMIN_PATH}/dashboard
/{ADMIN_PATH}/apps
```

### Legacy 接口

保留：

```text
POST /AppEn.php?appid=APP_ID&md5=APP_MD5
parameter=...
api=login.ic
```

Legacy Controller 只负责解包和组包，实际登录、绑定、充值和风控调用领域 Service。

### 两种业务模式

1. `card_login`
   - 卡密直接登录。
   - 首次使用激活并绑定设备。
   - 可配置到期时间、点数和 `arr[2]`。

2. `account_recharge`
   - 用户名密码注册/登录。
   - 充值卡给账户增加时长或点数。
   - 充值卡使用后进入已消费状态。

### 风控

- 应用级开关。
- 短时间登录次数和设备数量阈值。
- `observe`：记录并正常返回。
- `decoy`：记录并替换 `arr[2]`。
- `block`：记录并返回失败。
- 所有风险决策均可在后台查看。

## 6. 现有原型处理方式

当前工作区已有一部分自制路由、安装页面、登录页面和纯 PHP 风控原型。这些内容先冻结为参考：

- 纯领域类和测试可迁移到 Laravel。
- UI 可作为视觉草稿。
- 自制路由、安装控制器和认证控制器不作为最终框架基线。
- 框架确认后再统一迁移，避免两套结构并存。

## 7. 最终决策

```text
Laravel 13 + PHP 8.3 + MySQL 8
Blade + Alpine.js + 响应式 CSS
模块化单体 + Legacy v1 + REST v2
```

W1 阶段执行：

1. 升级/切换 PHP 8.3。
2. 在干净目录生成 Laravel 脚手架。
3. 迁移已确认的纯领域代码和 UI 草稿。
4. 从首次安装与管理员登录开始逐个任务开发。
