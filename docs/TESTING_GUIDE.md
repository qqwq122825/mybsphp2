# MyBsPHP2 测试与 Fixture 规范

## 1. 测试命令

正式工程统一使用 PHP 8.3：

```bash
cd laravel
/opt/homebrew/opt/php@8.3/bin/php artisan test
/opt/homebrew/opt/php@8.3/bin/php vendor/bin/pint --test
```

首次本地配置：

```bash
cp .env.testing.example .env.testing
/opt/homebrew/opt/php@8.3/bin/php artisan key:generate --env=testing
```

`.env.testing` 只连接独立的 `mybsphp_test` 数据库，并保持在版本控制之外。

## 2. 测试分层

| 类型 | 目录 | 用途 |
|---|---|---|
| Unit | `tests/Unit` | 纯业务规则、值对象、签名和状态机 |
| Feature | `tests/Feature` | HTTP、数据库事务、权限和 Legacy 契约 |
| Fixture | `tests/Fixtures` | 稳定、可读的业务场景数据 |
| Factory | `database/factories` | 模型默认数据与批量生成 |
| Seeder | `database/seeders` | 系统角色、权限等参考数据 |

## 3. Fixture 规则

1. Factory 保存通用默认值，Fixture 表达具体业务场景。
2. Fixture 必须支持 `$overrides`，测试只覆写与场景相关的字段。
3. 固定邮箱使用 `.test` 域名；固定设备号、卡密和令牌使用明显的测试前缀。
4. 时间相关测试使用 Laravel 时间冻结能力，不依赖当前真实时间。
5. 每个 Feature 数据库测试使用 `RefreshDatabase` 或显式事务。
6. 测试数据中不放生产密钥、真实用户资料和线上地址。
7. 风控、签名和 Legacy 数组响应均保存成功、失败、过期、重放四类 Fixture。

## 4. 隔离要求

- 开发库：`mybsphp_laravel`
- 测试库：`mybsphp_test`
- 旧原型库保持独立
- 测试启动时断言当前数据库名，避免误写开发库
- 覆盖安装和清库类测试另建一次性数据库，不操作上述长期数据库
