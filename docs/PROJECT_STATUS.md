# MyBsPHP2 项目状态

## 当前阶段

```text
W0 调研与需求冻结：DONE
W1 正式脚手架与工程基线：DONE
W2 首次安装与管理员登录：IN PROGRESS
```

## 当前任务

```text
W2-1 未安装状态与 `/install` 中间件：TODO
```

## 已完成

- W0-0 旧版目录、安装器与后台入口审阅。
- W0-1 AppEn 与 applibapi 分派审阅。
- W0-2 iOS 客户端调用审阅。
- W0-3 在线后台 UI 调研。
- W0-4 Laravel 13 + PHP 8.3 + MySQL 8 框架决策。
- W0-5 首版范围整理。
- W0-6 数据库 ER、约束与状态机。
- W0-7 完整 API 目录与 Legacy/REST 契约。
- W0-8 可选管理员二级密码策略。
- W0-9 一级代理、手动充余额和等级价提卡规则。
- W1-0 冻结当前原型并建立 Git 可回滚基线。
- W1-1 安装并验证 PHP 8.3.32、Composer 2.9.2 与必需扩展。
- W1-2 创建 Laravel 13 正式目录并完成 MySQL、时区与环境基线。
- W1-3 建立独立 MySQL 测试库、PHPUnit 隔离检查与 Fixture 规范。
- W1-4 建立 REST v2 统一 JSON、ULID 请求 ID 与异常映射。
- W1-5 建立请求日志上下文、递归脱敏和不可变审计基础。
- W1-6 建立 Composer 统一检查、Pint 与 Larastan/PHPStan 6 级静态分析。
- W2-0 冻结安装五步流程、动态后台入口和管理员登录页面规格。

## W0 验收门

- [x] Laravel 方案确认。
- [x] 数据模型确认。
- [x] API 清单确认。
- [x] 管理员二级密码策略确认。
- [x] 代理规则确认。

## 下一步

1. W2-1 实现未安装状态与 `/install` 中间件。
2. W2-2 实现 PHP、扩展和目录环境检查。
3. 按 `W2_INSTALL_AUTH_PAGE_SPEC.md` 将 8090 已确认体验迁入 8091。

## 规则

- 当前任务完成、测试并记录日志后，才进入下一任务。
- `docs/logs/` 每个任务一份独立日志。
- 业务代码从 W1-0 开始。
- W0 的完成证据以 `docs/W0_COMPLETION_AUDIT.md` 为准。
