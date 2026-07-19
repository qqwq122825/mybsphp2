# MyBsPHP2 Laravel 正式工程

PHP 8.3、Laravel 13 和 MySQL 8 的正式业务工程。根目录中的旧 PHP 页面只作为历史
协议与 UI 参考。

## 本地环境

- PHP 8.3，包含 `pdo_mysql`、OpenSSL、Mbstring、JSON、Ctype、Tokenizer、XML。
- Composer 2。
- MySQL 8。
- macOS 使用 Homebrew PHP/MySQL 即可。

本项目固定使用 PHP 8.3 可执行文件，避免系统 PHP 版本不同：

```bash
/opt/homebrew/opt/php@8.3/bin/php -v
/opt/homebrew/opt/php@8.3/bin/php "$(which composer)" install
```

## 首次启动

```bash
cp .env.example .env
/opt/homebrew/opt/php@8.3/bin/php artisan key:generate
/opt/homebrew/opt/php@8.3/bin/php artisan serve --host=127.0.0.1 --port=8091
```

打开 `http://127.0.0.1:8091/` 后进入五步安装：

1. 环境检测。
2. MySQL 连接与建库。
3. 站点名称、URL、时区和后台入口。
4. 超级管理员与可选二级密码。
5. 安装完成并进入动态后台。

后台入口默认 `/admin`，安装步骤 3 可以改写。修改后旧入口返回 404。

## 质量检查

测试使用独立 MySQL 库 `mybsphp_test`。本机测试凭据写入被 Git 忽略的
`.env.testing`，可从 `.env.testing.example` 创建。

```bash
/opt/homebrew/opt/php@8.3/bin/php vendor/bin/pint --test
/opt/homebrew/opt/php@8.3/bin/php -d memory_limit=1G vendor/bin/phpstan analyse --debug
/opt/homebrew/opt/php@8.3/bin/php artisan test
```

当前验收基线：47 tests / 362 assertions，PHPStan 0 错误。

## 安装状态

- 安装锁：`storage/installed.lock`，不提交到 Git。
- 环境标记：`APP_INSTALLED=true`。
- 动态入口：`ADMIN_PATH` 与安装锁元数据共同决定。
- 管理员密码、数据库密码和二级密码不写入安装锁或任务日志。

已安装站点需要重新安装时，先备份数据库，再在服务器生成一次性维护链接：

```bash
/opt/homebrew/opt/php@8.3/bin/php artisan mybsphp:reinstall-link --ttl=30
```

也可以按 BSPHP 的习惯直接访问 `/install`。页面会提示删除
`storage/installed.lock`；删除后刷新 `/install` 即可重新进入安装向导。数据库已有数据时默认
保留应用、卡种、卡密、批次和发卡记录，只重置后台管理员与权限。选择“清空全部数据”时，
最后直接点击“确认清空并安装”。

开发计划和任务日志位于上级目录的 `docs/`。
