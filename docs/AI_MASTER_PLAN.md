# MyBsPHP2 全 AI 重构开发总计划

> 项目目标：参考并重构新版 BSPHP，而不是在旧版 PHP 5 代码上继续修改。  
> 工作方式：按 `W阶段-任务号` 逐项实施；一项完成、测试、记录后再进入下一项。  
> 当前阶段：W0～W3 已完成；W4 应用管理主流程、W5 手动发卡主流程和
> W6 卡密激活核心链路已完成，后续继续补齐账户模式、Legacy 入口与风控。

## 1. 项目基线

### 推荐技术

- PHP 8.3
- Laravel 13
- MySQL 8
- Blade + Alpine.js
- 响应式 CSS
- PHPUnit
- 模块化单体
- Legacy v1 + REST v2

### 首版包含

- 首次安装。
- 默认 `/admin`，安装时可修改后台路径。
- 管理员、用户、代理。
- 卡密直接登录。
- 账户注册、登录、充值卡充值。
- 应用、卡种、批次、手动发卡。
- Legacy `AppEn.php` 和 `api=login.ic`。
- 固定 `arr[0/1/2/3/4/6/8]` 响应契约。
- 应用级风险开关。
- 代理细粒度权限。
- 手机、平板和电脑适配。

### 首版排除

- 在线支付。
- 自动发货。
- 邮件和邮箱。
- 短信。
- 图形验证码。
- 邀请推广和佣金。

## 2. 任务编号规则

```text
W0-0
│  └── 当前阶段中的任务序号
└───── 工作阶段
```

`W` 代表 Work Package，不强制等于自然周。每个任务原则上独立交付，避免一次修改过多模块。

任务状态：

- `TODO`：尚未开始。
- `DOING`：正在实施。
- `REVIEW`：实现完成，等待复查。
- `DONE`：测试与日志全部完成。
- `CORE DONE`：当前阶段的核心闭环完成，扩展任务仍在计划内。
- `PARTIAL`：本任务已完成当前阶段所需部分，剩余部分有明确后续任务。
- `BLOCKED`：依赖尚未满足。

## 3. 每项任务固定流程

每个 `W*-*` 任务严格执行：

1. 在 `docs/logs/` 创建任务日志。
2. 写清需求、输入、输出和验收条件。
3. 涉及 API 时先写 API 契约。
4. 涉及数据库时先画字段和状态变化。
5. 先创建测试用例或测试清单。
6. 实现本任务最小代码。
7. 执行目标测试。
8. 执行已有全量回归。
9. UI 任务使用浏览器检查桌面和手机。
10. 更新任务日志、状态表和相关文档。
11. 主 Agent 复查后标记 `DONE`。

## 4. 阶段 W0：调研与需求冻结

| 编号 | 状态 | 内容 | 交付物 |
|---|---|---|---|
| W0-0 | DONE | 审阅旧版目录、安装器和后台入口 | 旧版审阅记录 |
| W0-1 | DONE | 审阅 `AppEn.php`、`applibapi` 分派 | Legacy 调用链 |
| W0-2 | DONE | 审阅 iOS `UIInitializer.mm` | 请求与返回契约 |
| W0-3 | DONE | 登录在线 BSPHP 后台，整理菜单和视觉 | `UI_RESEARCH.md` |
| W0-4 | DONE | 框架选择 | `FRAMEWORK_DECISION.md` |
| W0-5 | DONE | 首版包含项与排除项 | 开发规格 |
| W0-6 | DONE | 数据库 ER 图与状态机 | `DATABASE_DESIGN.md` |
| W0-7 | DONE | 完整 API 目录 | `API_CATALOG.md` |
| W0-8 | DONE | 确认管理员二级密码是否保留 | 决策记录 |
| W0-9 | DONE | 确认代理层级和余额提卡规则 | 代理规则 |

### W0 验收门

- Laravel 方案确认。
- 数据模型确认。
- API 清单确认。
- 管理员二级密码策略确认。
- 代理规则确认。

**验收结果：已于 2026-07-18 全部通过。** 逐项证据见
[`W0_COMPLETION_AUDIT.md`](W0_COMPLETION_AUDIT.md)。

## 5. 阶段 W1：正式脚手架与工程基线

| 编号 | 内容 | 测试/验收 |
|---|---|---|
| W1-0 | 冻结当前原型并建立 Git 可回滚基线 | 基线提交、分支和敏感文件排除检查通过 |
| W1-1 | 切换 PHP 8.3、Composer 与扩展 | 环境检查全部通过 |
| W1-2 | 创建 Laravel 正式目录，配置 MySQL、时区和 `.env` | Laravel 首页、配置测试和测试命令正常 |
| W1-3 | PHPUnit、测试数据库和 Fixture 规范 | 示例 Unit/Feature 通过 |
| W1-4 | 异常处理、统一 JSON、请求 ID | Feature 测试 |
| W1-5 | 日志、审计基础和敏感字段脱敏 | 日志测试 |
| W1-6 | 代码规范和静态检查命令 | 全量命令通过 |

### W1 验收门

- 新脚手架干净可启动。
- 测试环境与开发数据库隔离。
- 后续模块都从 Laravel 基线开发。

**验收结果：已于 2026-07-19 全部通过。** 工程统一检查入口为
`cd laravel && composer check`，规则见
[`ENGINEERING_CHECKS.md`](ENGINEERING_CHECKS.md)。

## 6. 阶段 W2：首次安装与管理员登录

| 编号 | 状态 | 内容 | 测试/验收 |
|---|---|---|---|
| W2-0 | DONE | 调研旧安装器和在线登录页的交互 | [`W2_INSTALL_AUTH_PAGE_SPEC.md`](W2_INSTALL_AUTH_PAGE_SPEC.md) |
| W2-1 | DONE | 未安装状态与 `/install` 中间件 | Feature |
| W2-2 | DONE | PHP/扩展/目录环境检查 | Unit + Feature |
| W2-3 | DONE | MySQL 连接测试和数据库创建 | 实际 MySQL + Feature |
| W2-4 | DONE | 站点名称、URL、默认 `admin` 路径 | Feature |
| W2-5 | DONE | 安装时修改后台路径 | Feature + 路由测试 |
| W2-6 | DONE | 创建超级管理员 | Migration + 实际 MySQL |
| W2-7 | DONE | 管理员登录/退出 | Feature + Browser |
| W2-8 | DONE | 可选二级密码策略 | Feature |
| W2-9 | DONE | 登录限流、失败延迟、Session 轮换 | Security Feature |
| W2-10 | DONE | 安装锁与重复安装保护 | Unit + Feature |
| W2-11 | DONE | 桌面/手机安装和登录页面 | Browser + 响应式巡检 |

### W2 验收门

- 五步安装可在 8091 完整执行。
- 自定义后台入口生效，旧 `/admin` 返回 404。
- 管理员登录、退出、二级密码、限流和 Session 轮换通过。
- 安装锁阻止普通重复安装。
- 桌面与窄屏页面无横向溢出。

**验收结果：已于 2026-07-19 全部通过。** 本机实际安装库为
`mybsphp_dynamic`，动态后台入口按安装输入生成；账号凭据不写入任务日志。

## 7. 阶段 W3：后台框架、角色与权限

| 编号 | 状态 | 内容 | 测试/验收 |
|---|---|---|---|
| W3-0 | DONE | 后台 Layout、顶栏、侧栏、管理首页 | Browser |
| W3-1 | DONE | 手机抽屉和底部导航 | Browser + 响应式巡检 |
| W3-2 | DONE | 管理员、角色、权限及关联表 | Migration + MySQL |
| W3-3 | DONE | Laravel Gate/Policy 权限层 | Unit |
| W3-4 | DONE | 超级管理员、管理员、代理角色 | Seeder |
| W3-5 | DONE | 代理默认权限模板 | Permission |
| W3-6 | DONE | 默认禁止删卡、解绑、管理账户 | Permission |
| W3-7 | DONE | 管理操作审计日志 | Feature |
| W3-8 | DONE | 后台管理员管理页面 | Feature + Browser |

## 8. 阶段 W4：应用与协议配置

| 编号 | 状态 | 内容 | 测试/验收 |
|---|---|---|---|
| W4-0 | DONE | 继续调研 BSPHP“新建/配置软件”页面 | UI 记录 |
| W4-1 | DONE | 应用表、模式和状态 | Migration |
| W4-2 | DONE | 应用列表与统计卡片 | Feature + Browser |
| W4-3 | DONE | 新建/编辑/停用应用 | Feature |
| W4-4 | DONE | `card_login` 模式 | Unit |
| W4-5 | PARTIAL | `account_recharge` 模式 | 模式可选，账户业务在 W7 实现 |
| W4-6 | DONE | Legacy 参数名配置 | Unit + Feature |
| W4-7 | DONE | `arr[2]` 真实值、空值、诱饵值配置 | Contract |
| W4-8 | DONE | 每日签名和响应档案 | Unit |
| W4-9 | PARTIAL | 应用级设备绑定和并发设置 | 设备绑定完成，会话并发在 W6-6 实现 |

## 9. 阶段 W5：卡种、批次与手动发卡

| 编号 | 状态 | 内容 | 测试/验收 |
|---|---|---|---|
| W5-0 | DONE | 调研卡种、制卡、批量维护页面 | UI 记录 |
| W5-1 | DONE | 卡种和时长/点数规则 | Migration + Unit |
| W5-2 | DONE | 卡密批次 | Migration + Feature |
| W5-3 | DONE | 安全随机卡密生成 | Unit |
| W5-4 | TODO | 卡密导入 | Feature |
| W5-5 | DONE | 卡密状态机 | Unit |
| W5-6 | DONE | 单张手动发卡 | Transaction |
| W5-7 | DONE | 批量手动发卡 | Transaction |
| W5-8 | PARTIAL | 发给用户/代理/线下客户 | 线下客户完成，用户/代理在 W7/W10 接入 |
| W5-9 | TODO | TXT/CSV 导出 | Feature |
| W5-10 | DONE | 发卡记录和备注 | Feature |
| W5-11 | DONE | 手机卡密列表与批量操作 | Browser |

## 10. 阶段 W6：卡密直接登录

| 编号 | 状态 | 内容 | 测试/验收 |
|---|---|---|---|
| W6-0 | DONE | 卡密查询和状态验证 Service | Unit |
| W6-1 | DONE | 首次激活和到期时间计算 | Unit |
| W6-2 | DONE | 首次绑定设备 | Unit + Transaction |
| W6-3 | DONE | 异机登录处理 | Unit |
| W6-4 | DONE | 冻结、过期、作废处理 | Unit |
| W6-5 | PARTIAL | `login.ic` 业务 Service | 激活 Service 完成，Legacy 分派在 W8 接入 |
| W6-6 | TODO | 心跳和在线会话 | Feature |
| W6-7 | TODO | 解绑与换绑审批 | Permission |
| W6-8 | PARTIAL | 登录事件和设备记录 | 卡密状态与审计完成，独立登录事件待补 |

## 11. 阶段 W7：账户注册、登录与充值

| 编号 | 内容 | 测试/验收 |
|---|---|---|
| W7-0 | 账户模式数据模型 | Migration |
| W7-1 | 用户名密码注册 | Feature |
| W7-2 | 账户登录 | Feature |
| W7-3 | 充值卡增加时长 | Transaction |
| W7-4 | 充值卡增加点数 | Transaction |
| W7-5 | 充值卡一次性消费 | Unit |
| W7-6 | 重复请求幂等 | Concurrency |
| W7-7 | 账户冻结和设备绑定 | Unit |
| W7-8 | 管理员账户维护 | Permission |

## 12. 阶段 W8：Legacy AppEn 协议

| 编号 | 内容 | 测试/验收 |
|---|---|---|
| W8-0 | `/AppEn.php` 兼容入口 | Contract |
| W8-1 | `appid/md5/parameter` 解包 | Contract |
| W8-2 | `api=login.ic` 分派 | Contract |
| W8-3 | 应用覆盖 > 模式 > 默认处理器 | Unit |
| W8-4 | JSON 外壳和竖线数据 | Contract |
| W8-5 | 固定 `arr[0/1/2/3/4/6/8]` | Contract |
| W8-6 | `appsafecode` 原样回传 | Contract |
| W8-7 | UTC 每日签名 | Unit |
| W8-8 | 旧错误码映射 | Contract |
| W8-9 | iOS Fixture 端到端验证 | End-to-end |
| W8-10 | REST v2 等价接口 | Feature |

## 13. 阶段 W9：风险控制

| 编号 | 内容 | 测试/验收 |
|---|---|---|
| W9-0 | 风险策略数据模型 | Migration |
| W9-1 | 请求时间窗口 | Unit |
| W9-2 | Nonce/请求指纹去重 | Unit + Cache |
| W9-3 | 同一卡密短时登录次数 | Unit |
| W9-4 | 同一卡密短时设备数量 | Unit |
| W9-5 | Observe 决策 | Unit |
| W9-6 | Decoy 替换 `arr[2]` | Contract |
| W9-7 | Block 返回 | Contract |
| W9-8 | 应用级风险开关 | Unit |
| W9-9 | 风险事件后台 | Feature + Browser |
| W9-10 | 重放与并发回归 | Security |

说明：`appsafecode` 原样回传用于客户端核对；服务端时间窗口、Nonce 和请求指纹共同处理重放风险。

## 14. 阶段 W10：代理系统

| 编号 | 内容 | 测试/验收 |
|---|---|---|
| W10-0 | 调研现有代理后台 | UI 记录 |
| W10-1 | 代理等级 | Migration |
| W10-2 | 应用/卡种代理价格 | Unit |
| W10-3 | 管理员手动充值代理余额 | Transaction |
| W10-4 | 代理自助提卡 | Transaction |
| W10-5 | 代理发卡记录 | Feature |
| W10-6 | 代理权限开关 | Permission |
| W10-7 | 代理数据范围 | Permission |
| W10-8 | 代理手机后台 | Browser |
| W10-9 | 多级代理 | 依赖 W0-9 |

## 15. 阶段 W11：完整 UI 与统计

| 编号 | 内容 | 测试/验收 |
|---|---|---|
| W11-0 | 概览统计 | Feature |
| W11-1 | 应用统计卡片 | Browser |
| W11-2 | 在线连接与设备统计 | Feature |
| W11-3 | 卡密与库存统计 | Feature |
| W11-4 | 日志筛选和详情 | Feature |
| W11-5 | 空状态、错误状态、加载状态 | Browser |
| W11-6 | 390/768/1280 全页面巡检 | Browser |
| W11-7 | 深色模式评估 | UI 决策 |

## 16. 阶段 W12：发布与部署

| 编号 | 内容 | 测试/验收 |
|---|---|---|
| W12-0 | 全量 Unit/Feature/Contract | 全部通过 |
| W12-1 | 新安装回归 | 空数据库通过 |
| W12-2 | 升级迁移回归 | 旧版本升级通过 |
| W12-3 | Nginx/PHP-FPM 配置 | 部署验证 |
| W12-4 | 备份与恢复文档 | 恢复演练 |
| W12-5 | 安全配置检查 | Checklist |
| W12-6 | 用户文档与 API 文档 | 文档审阅 |
| W12-7 | GitHub 首个版本 | Tag/Release |

## 17. 每次任务的交付格式

任务完成后统一报告：

```text
任务：W5-6 单张手动发卡
状态：DONE

完成：
- ...

变更：
- app/...
- database/migrations/...

API：
- POST /admin/cards/issue

测试：
- php artisan test --filter=IssueCardTest
- 8 passed

浏览器检查：
- 390px 通过
- 1280px 通过

日志：
- docs/logs/W5-6.md

下一任务：
- W5-7 批量手动发卡
```

## 18. 开发启动顺序

正式启动前依次完成：

1. 确认 Laravel 13 + PHP 8.3。
2. 完成 W0-6 数据库设计。
3. 完成 W0-7 API 目录。
4. 确认 W0-8 二级密码策略。
5. 确认 W0-9 代理规则。
6. 从 W1-0 开始正式脚手架。
