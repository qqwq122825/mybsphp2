# 工程检查约定

> 任务：W1-6
> 适用目录：`laravel/`

## 1. 一条命令完成验收

```bash
cd laravel
composer check
```

该命令按顺序执行：

1. `composer validate --strict`：校验 Composer 清单和锁文件。
2. `composer lint`：使用 Laravel Pint 检查 PHP 代码格式。
3. `composer analyse`：使用 Larastan/PHPStan 进行 6 级静态分析。
4. `composer test`：清理配置缓存并运行 Unit、Feature 及 MySQL 集成测试。

任意一步返回非零状态时，整条检查立即失败。合并业务代码前必须保证
`composer check` 全部通过。

## 2. 可单独执行的命令

| 命令 | 用途 |
|---|---|
| `composer format` | 自动修正 Pint 能处理的代码格式 |
| `composer lint` | 只检查格式，不改文件 |
| `composer analyse` | 执行 Larastan/PHPStan 静态分析 |
| `composer test:unit` | 只运行 Unit 测试 |
| `composer test:feature` | 只运行 Feature 测试 |
| `composer test` | 运行全部自动化测试 |

## 3. 静态分析基线

- 分析范围：`app/`、`bootstrap/`、`database/`、`routes/`、`tests/`。
- 等级：6。
- 不建立忽略错误基线；新发现的问题直接修复。
- 仅排除 Laravel 自动生成的 `bootstrap/cache/`。

## 4. MySQL 测试约定

测试环境读取 `laravel/.env.testing`，使用独立的 `mybsphp_test` 数据库。
测试命令会运行迁移并在事务或刷新数据库机制下隔离数据，不使用开发库。
数据库账号、密码和连接错误不得写入响应、审计上下文或版本库。
