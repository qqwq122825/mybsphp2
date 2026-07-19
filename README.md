# MyBsPHP2

基于 PHP 8.3、Laravel 13 与 MySQL 8 重构的开源手动发卡、软件授权和代理管理系统。
旧版参考代码位于 `示例/`，新版以模块化单体重新实现。

W0～W3 已完成，应用、卡密、手动发卡与激活核心闭环已经可以运行。详细路线图见
[AI 总计划](docs/AI_MASTER_PLAN.md)、[当前状态](docs/PROJECT_STATUS.md)、
[MVP 进度](docs/MVP_PROGRESS.md)和[W0 验收报告](docs/W0_COMPLETION_AUDIT.md)。

## 当前阶段

- W0 调研与需求冻结：DONE。
- W1 Laravel 工程基线：DONE。
- W2 首次安装与管理员登录：DONE。
- W3 动态后台、响应式导航、角色权限与管理员维护：DONE。
- W4 应用管理主流程：DONE。
- W5 卡种、批次与手动发卡主流程：DONE。
- W6 卡密激活核心链路：CORE DONE。
- 下一批：卡密导入/导出、在线会话、账户充值与 Legacy `login.ic`。

## 环境要求

- PHP >= 8.3，扩展：PDO MySQL、JSON、OpenSSL、Mbstring、Ctype、Tokenizer、XML
- MySQL >= 8.0
- Composer >= 2

macOS 开发环境使用 Homebrew PHP/MySQL 即可。正式工程位于 `laravel/`，启动方式见
[`laravel/README.md`](laravel/README.md)。

安装中断、数据库已有数据或需要覆盖安装时，参见
[覆盖安装与安装锁说明](docs/REINSTALL.md)。

## 当前进度

- [x] 旧版安装、后台、Legacy 与 iOS 调用链调研
- [x] 在线后台 UI 调研与手机端策略
- [x] 框架、首版范围、数据库、API 和权限业务决策
- [x] Laravel 正式脚手架与工程基线
- [x] 五步首次安装、动态后台入口与管理员登录
- [x] 动态管理首页、桌面侧栏与手机导航
- [x] 应用、卡种、批次与卡密后台
- [x] 手动发卡与发卡记录
- [x] 静态 HTML 卡密激活模拟与服务端验证
- [ ] 卡密导入与批量导出
- [ ] 代理等级、定价与钱包
- [ ] Legacy 授权 API、账户充值与风险策略
