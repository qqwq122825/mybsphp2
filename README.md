# MyBsPHP2

基于 PHP 8.3、Laravel 13 与 MySQL 8 重构的开源手动发卡、软件授权和代理管理系统。
旧版参考代码位于 `示例/`，新版以模块化单体重新实现。

W0、W1 和 W2 已完成，W3 后台权限阶段正在实施。详细路线图见
[AI 总计划](docs/AI_MASTER_PLAN.md)、[当前状态](docs/PROJECT_STATUS.md)和
[W0 验收报告](docs/W0_COMPLETION_AUDIT.md)。

## 当前阶段

- W0 调研与需求冻结：DONE。
- W1 Laravel 工程基线：DONE。
- W2 首次安装与管理员登录：DONE。
- W3-0～W3-3 动态后台首页、响应式导航、RBAC 数据层和 Gate/Policy：DONE。
- 下一任务：W3-4，系统角色与权限 Seeder。

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
- [ ] 应用/商品/卡密后台
- [ ] 手动发卡、批量导出与发卡记录
- [ ] 代理等级、定价与钱包
- [ ] 授权 API 与版本管理
