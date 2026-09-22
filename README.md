# MyBsPHP2 Laravel 正式工程

PHP 8.3、Laravel 13 和 MySQL 8 的正式业务工程。根目录中的旧 PHP 页面只作为历史
协议与 UI 参考。

## DFMProxy v133.4 联动

软件配置新增“代理联动”：随机单入口、独立节点密钥、已激活用户与秒级有效期同步。部署需要新增表迁移及 `npm run build`，详情见 [接入与部署说明](docs/dfmproxy-link-v1334.md)。默认关闭，原客户端登录接口保持独立。

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

## 后台组件库（Element Plus）

代理端登录/中控台/卡管理/余额制卡/财务管理、软件管理、软件配置/新增、软件用户列表与详情、封控中心、客户端请求日志（简要/详细）、黑名单请求日志和系统日志已使用 **Vue 3 + Element Plus**。
按钮、输入框、下拉框、标签、表格选择/固定列、分页、表单、弹窗、确认框与消息提示使用组件库原生组件。
应用 CSS 仅处理页面布局、间距与业务内容排版，不复制组件库的控件实现。

```bash
npm ci
npm run build
npm run test:ui
```

部署时需包含构建生成的 `public/build`（依赖和构建目录不提交到 Git）。运行时从本站加载资源，无 CDN 依赖。

- `resources/js/admin-ui/AdminList.vue`：组件库列表页面，新增页面应复用它或直接使用 Element Plus 组件。
- `resources/js/admin-ui/SoftwareList.vue` / `SoftwareForm.vue`：软件卡片、统计、配置页签及表单。
- 添加软件时由服务端默认生成未占用的 8 位数字代号（包含软删除记录查重），仍可手动修改；校验失败保留输入，创建后代号保持不变。
- `resources/js/admin-ui/FormFields.vue` / `UserDialogs.vue`：复用 Element Plus 输入、数字、日期组件和按内容自适应的用户弹窗。
- `resources/js/admin-ui/read-list.js`：旧 Blade 页面的迁移适配，仅读取后端已经输出的数据、权限结果、表单地址和 CSRF 字段；不在前端重复封控/日志业务规则；软件表单由 `read-software.js` / `read-users.js` 适配，保留原字段和值。
- 保留服务器渲染页面用于无 JavaScript 情况。成功挂载后隐藏旧页面；正常页面不叠加旧版按钮、表格或弹窗样式。
- 单行操作只提交自身记录键；批量操作提交选择项。实际操作仍经原有服务端路由、权限、CSRF 和审计校验。
- `resources/js/agent-ui/AgentApp.vue`：代理端统一页面，菜单/Tab/布局/统计/表格/金额确认/输入/弹窗均使用 Element Plus；不新增代理控件 CSS。`read-agent.js` 只读取服务端权限过滤后的数据、价格和原始表单。
- 代理端导航不重复：桌面端只显示左侧 ElMenu，800px 及以下只显示顶部 ElTabs；登录页隐藏两种导航。这组移动端 Tab 是功能导航，不是浏览器式多页面工作区。
- 代理制卡预计费用以分为单位计算；真实扣费、退款、解绑及可操作范围继续由后端校验。代理标记请求加在途状态，失败提示并保留原状态。
- 其他后台页面按需迁移；不要新增手写基础控件或照抄 Element Plus 样式。

生成只读本地组件预览（示例数据）：

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= /opt/homebrew/opt/php@8.3/bin/php tests/render_admin_table_preview.php
python3 -m http.server 8092 --bind 127.0.0.1 --directory storage/app/admin-table-preview
```

本地验收入口：`http://127.0.0.1:8092/agent-dashboard.html`（代理端）、`http://127.0.0.1:8092/software.html`（软件管理）。验收预览使用内存构建的示例数据，不执行真实制卡、退款或登录。
