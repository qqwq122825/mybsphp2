# MyBsPHP2 重构实施计划

> 本文是实施摘要；细粒度任务、状态与证据分别以
> [AI_MASTER_PLAN.md](AI_MASTER_PLAN.md)、
> [PROJECT_STATUS.md](PROJECT_STATUS.md) 和
> [W0_COMPLETION_AUDIT.md](W0_COMPLETION_AUDIT.md) 为准。

## 1. 项目定位

使用 PHP 8.3、Laravel 13 与 MySQL 8，从零重构一个可独立部署的开源手动发卡、
软件授权与一级代理系统。旧版 `示例/` 只用于理解业务、协议和 UI，不延续旧式全局变量、
混合 SQL/HTML 或按用户输入直接加载 PHP 文件的实现。

## 2. 技术架构

- 模块化单体：Laravel Controller → Application Service → Domain → Eloquent/Repository。
- Blade + Alpine.js + 响应式 CSS，同时覆盖手机、平板和桌面。
- Laravel Migration/Seeder、Session、Rate Limiter、Gate/Policy 与审计。
- Legacy v1 `/AppEn.php` 与 REST v2 共用业务 Service。
- PHPUnit 覆盖 Unit、Feature、Contract、事务与并发边界。

```text
Browser / iOS client
        │
        ├── Web + JSON API
        ├── Legacy /AppEn.php
        └── REST /api/v2
                    │
             Laravel Controllers
                    │
       Application / Domain Services
                    │
                  MySQL 8
```

## 3. 首版范围

### 包含

- 首次安装；后台默认 `/admin`，安装时可重写。
- 可选管理员二级密码，新安装默认关闭。
- 管理员、普通用户、一级代理及细粒度 RBAC。
- 应用、卡种、批次、卡密生成/导入/导出和后台手动发卡。
- `card_login` 卡密直接登录模式。
- `account_recharge` 账户注册、登录和充值卡充值模式。
- 代理等级价、管理员手动充扣余额、代理自助提卡和不可变钱包流水。
- Legacy `login.ic`、固定 `arr[0/1/2/3/4/6/8]`、UTC 每日签名。
- 应用级 Observe/Decoy/Block 风控和 `arr[2]` 真实/空/诱饵策略。
- 审计、限流、幂等、设备绑定、在线会话与手机端适配。

### 首版排除

- 在线订单、支付、回调、退款、补单和自动发货。
- 邮箱、邮件、短信及图形/短信/邮箱验证码。
- 多级代理、返佣、提现和推广。
- 微服务、Redis/队列强依赖和 SPA 前端框架。

## 4. 核心业务规则

1. 金额统一使用整数分，不使用浮点金额。
2. 发卡、充值、余额变更和代理提卡使用事务与幂等键。
3. 代理默认只读写自身数据，默认无删卡、解绑、账户管理、改价和余额管理权限。
4. 卡密与充值卡采用明确状态机，终态不回退；纠错用新流水或显式管理动作。
5. Legacy 处理器优先级固定：
   `App Override > Mode Handler > Core Default Handler`。
6. `appsafecode` 在 `arr[3]` 原样回传；风险 Decoy 只替换 `arr[2]`。

## 5. 交付阶段

| 阶段 | 内容 | 状态 |
|---|---|---|
| W0 | 调研、框架、范围、数据库、API 和业务决策 | DONE |
| W1 | Laravel 脚手架、PHP 8.3、测试与日志基线 | TODO |
| W2 | 首次安装、动态后台路径、管理员登录 | TODO |
| W3 | 后台框架、RBAC、代理权限和审计 | TODO |
| W4 | 应用、协议、响应档案和风险配置 | TODO |
| W5 | 卡种、批次、卡密与手动发卡 | TODO |
| W6 | 卡密直接登录与设备绑定 | TODO |
| W7 | 账户注册、登录与充值 | TODO |
| W8 | Legacy AppEn 与 REST v2 | TODO |
| W9 | 风险控制 | TODO |
| W10 | 一级代理、余额、价格和自助提卡 | TODO |

每个 `W*-*` 任务独立记录在 `docs/logs/`，先契约/测试清单，后实现，再执行目标测试与
全量回归。

## 6. 开发环境

### macOS

- Homebrew PHP 8.3 + Composer 2。
- Homebrew MySQL 8。
- Laravel 本地服务或 PHP 内置服务。
- 不额外引入 phpStudy，避免与现有 Homebrew 环境重复。

W1-1 负责切换 PHP 版本并验证扩展；W1-2 负责 MySQL、`.env`、测试库和时区。生产环境
采用 Linux + Nginx + PHP-FPM + MySQL 8。

## 7. W0 已冻结的设计入口

- 旧版审阅：[LEGACY_REVIEW.md](LEGACY_REVIEW.md)
- 客户端契约：[CLIENT_CONTRACT.md](CLIENT_CONTRACT.md)
- UI 调研：[UI_RESEARCH.md](UI_RESEARCH.md)
- 框架决策：[FRAMEWORK_DECISION.md](FRAMEWORK_DECISION.md)
- 数据库设计：[DATABASE_DESIGN.md](DATABASE_DESIGN.md)
- API 目录：[API_CATALOG.md](API_CATALOG.md)
- 二级密码：[decisions/W0-8_ADMIN_SECONDARY_PASSWORD.md](decisions/W0-8_ADMIN_SECONDARY_PASSWORD.md)
- 代理规则：[decisions/W0-9_AGENT_RULES.md](decisions/W0-9_AGENT_RULES.md)
