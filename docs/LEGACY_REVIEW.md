# W0 旧版 BSPHP 审阅记录

## 1. 审阅范围

权威样本位于：

```text
示例/
```

重点文件：

- `示例/index.php`
- `示例/install/`
- `示例/nnnnnnnnnnxxx/index.php`
- `示例/AppEn.php`
- `示例/LibBsphp/Global.Bsphp.Inc.php`
- `示例/include/modules/applib/appen.php`
- `示例/include/modules/applib/common/appen_inc.function.php`
- `示例/include/applibapi/`
- `示例/include/modules/admin/`
- `示例/include/modules/agent/`
- `示例/include/templates/default/`

## 2. 首次安装流程

旧版入口 `示例/index.php` 检测：

```text
Data/install.txt
```

未检测到标记时跳转 `install/`。安装器依次：

1. 展示欢迎页。
2. 检查 PHP、Zend Loader 和目录权限。
3. 收集 MySQL 地址、账号、密码、数据库名、表前缀。
4. 写入 `Data/Bsmysql.Config.php`。
5. 导入 `AppEn.mysql.txt`。
6. 导入 `mysql.data.txt`。
7. 写入 `Data/install.txt`。

值得保留：

- 首次访问自动安装。
- 环境检测。
- 数据库结构与种子分离。
- 安装标记。
- 表前缀概念。

新版调整：

- 使用 Laravel Migration/Seeder。
- PHP 8.3。
- PDO/Eloquent。
- 安装时创建管理员，不提供固定默认密码。
- `.env` 位于公开目录外。
- 安装锁、迁移版本和错误回滚。

## 3. 后台入口

示例后台对外目录：

```text
示例/nnnnnnnnnnxxx/
```

入口内部仍声明：

```php
const BSPHP_SET = 'ADMIN';
```

这说明旧版已经区分：

- 外部后台路径。
- 内部 Admin 模块。

新版保留这一设计：

```env
ADMIN_PATH=admin
```

默认路径为 `admin`，安装时允许改写。Laravel 内部模块名称保持不变。

## 4. 多入口结构

旧版通过 `BSPHP_SET` 区分：

- `INDEX`：网站。
- `ADMIN`：管理后台。
- `USER`：用户中心。
- `AGENT`：代理中心。
- `APPEN`：客户端协议。

新版使用 Laravel 路由组和中间件表达相同边界，不再维护多套入口引导文件。

## 5. AppEn 调用链

旧版调用链：

```text
AppEn.php
→ Global.Bsphp.Inc.php
→ bs_lib::Include_Modules()
→ appen::call_open_begin()
→ 读取 appid 对应应用
→ 加载请求解密器
→ 解析 parameter
→ 验证 mutualkey/date/md5/BSphpSeSsL
→ 读取 api
→ 选择 AppEn.{api}.php
→ PrInfo()
→ 输出适配器
→ 响应加密器
→ exit
```

## 6. API 处理器优先级

`示例/include/modules/applib/appen.php` 的处理器查找顺序：

```text
include/applibapi/api/{app_api_dir}/AppEn.{api}.php
    >
include/applibapi/api/{app_MoShi}/AppEn.{api}.php
    >
include/applibapi/api/Default/AppEn.{api}.php
```

即：

```text
应用覆盖 > 授权模式 > 系统默认
```

这是新版必须保留的核心扩展能力。

新版使用类和注册表表达：

```text
App Override Handler
    > Mode Handler
        > Core Handler
```

## 7. 请求与输出管线

旧版请求：

```text
POST /AppEn.php?appid=APP_ID&md5=APP_MD5
Content-Type: application/x-www-form-urlencoded

parameter=ENCODED_OR_ENCRYPTED_QUERY_STRING
```

`parameter` 解密后仍为 Query String。输出依次经过：

1. 业务处理器生成业务值。
2. `app_print_fun` 包装格式。
3. `app_show_encryption` 加密。
4. 输出并结束请求。

新版保留可插拔的：

- Request Decoder。
- Handler Registry。
- Response Profile。
- Response Encoder。

## 8. 四种旧模式

旧版模式：

- `CardTerm`：卡密 + 时间。
- `CardPoint`：卡密 + 点数。
- `LoginTerm`：账户 + 时间。
- `LoginPoint`：账户 + 点数。

新版归并为两个凭证模式：

1. `card_login`
2. `account_recharge`

再由产品计费类型表达：

- `duration`
- `points`
- `fixed_expiry`

这样避免复制四套相近代码，同时保留旧模式的业务能力。

## 9. 旧版高价值设计

- Web 首次安装。
- 可改后台入口。
- 应用作为顶级业务隔离单位。
- 应用覆盖/模式/默认三级 API。
- 请求解密、响应加密、输出格式分离。
- 卡密时间与点数模式。
- 用户、代理、管理员分端。
- Legacy 参数名可配置。
- 软件管理卡片集中展示用户、会话、卡密和库存。

## 10. 需要重构的问题

- PHP 5.3/5.4 和废弃扩展。
- MD5 管理员密码。
- SQL 字符串拼接。
- 固定默认管理员凭据。
- 安装流程可被备用入口重复执行。
- 配置和凭据位于 Web 目录。
- 失败日志记录敏感输入。
- Session/Cookie 策略不完整。
- iframe 后台不利于手机适配和路由治理。
- 相似模式代码重复。
- Legacy 字段缺少稳定契约测试。

## 11. W0 结论

新版不是直接改旧文件，而是：

```text
保留业务模型和兼容协议
＋
使用 Laravel 13 重新实现
＋
用测试固定 Legacy 行为
＋
用响应式后台重做 UI
```

