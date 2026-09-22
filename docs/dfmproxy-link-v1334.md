# DFMProxy v133.4 接入与部署

## 部署步骤

在站点当前代码目录完成常规数据库备份后，更新代码并执行（使用服务器配置的 PHP 8.3+）：

```sh
php artisan migrate --force
npm ci
npm run build
php artisan optimize:clear
```

按站点原有流程刷新 PHP-FPM / OPcache；若使用 route:cache、config:cache，清理后重新生成缓存。新表为 proxy_links、proxy_credentials，原有卡密/客户端会话表和登录协议保持独立。

public/build 被 Git 忽略，因此仅拉取源码不包含新 Vue 界面；恢复新版 Vue 界面时需构建并发布该目录。软件配置页会检查构建版本，旧资源或资源不完整时直接使用服务端兼容表单，联动开关仍可使用。公开站点 URL 应配置为正确的 HTTPS 域名；随机地址由该 URL 生成。

## 后台配置

软件配置新增“代理联动”，需具备软件配置修改权限：

- 打开“启用代理联动”开关后自动保存并生成应用专属 `/lk_` + 32 位随机十六进制路径和 64 位独立节点密钥。
- 地址长期固定。关闭后保留地址，再次开启沿用；普通保存不轮换；正常轮换保留旧路径 10 分钟，立即重置立刻撤销旧路径。
- 默认仅重放，可选仅录制或录制＋重放。关闭联动不关闭原客户端登录。
- 首次成功 sync 绑定 node_id；ping 仅测试，不抢占节点。重置密钥解除绑定，保留用户代理凭据；更换节点时先停止旧实例，其已有离线租约最多存活 300 秒。
- “最近拉取”表示服务端已生成响应，不表示 DFMProxy 已确认落盘；代理端显示实际同步结果。
- 卡密模式的代理账号＝密码＝原卡密；卡密在原表加密保存，不额外复制到明文用户名列。账号充值模式在首次同步时生成独立代理码，账号和密码均填该代理码，代理码加密落库且续期沿用。管理员按授权 public_id 查询并复制凭据，不把卡密放进搜索 URL。原客户端自动展示代理连接信息不在本次两个仓库的改造范围内。

## CDN / Nginx

- CDN 精确匹配域名和生成的完整路径，只允许需要的 POST 请求；不设置整个站点通配白名单。
- 对机器请求免浏览器 JS/验证码挑战，保留 DDoS 防护与适当限流。固定节点可增加出口 IP 限制。
- 不缓存成功响应或错误响应，服务端设置 Cache-Control: no-store；应用层日志隐藏随机路径。CDN/Nginx 访问日志也应限制访问和保留期限，避免复制敏感路径。
- 密钥使用 Authorization: Bearer 请求头。CDN/WAF/反向代理需保留此请求头和 JSON 正文，转发至 Laravel front controller。该路径是虚拟路由，不创建物理目录。
- 根路径的现有策略保持不变。随机路径不作为身份凭据，接口同时校验密钥、应用和节点。
- 本接口按来源限流 60 请求/分钟；节点默认 30 秒同步，测试与手动同步也计入。

## 协议 dfmproxy-link-v1

向同一完整地址 POST JSON，action 为 ping 或 sync，其他参数为 node_id（UUID）、node_name、可选预期 app_id。应用始终由随机入口解析，正文 app_id 仅用于一致性校验。

成功响应 data 包含 protocol、application_id（public_id）、app_id（软件代号）、name、server_time（UTC Unix 秒）、lease_seconds（300）。sync 额外包含 complete=true 与完整 users 数组。

每个用户包含 subject_id（card:public_id 或 account:public_id）、相同的 username/password、label、expires_at（Unix 秒）、status、perm、allow_multi=false。仅同步已激活卡或软件账号。每次完整替换同一绑定的缓存，缺失主体撤销访问。DFMProxy 在服务端有效期到达后自动删除本地联动用户与缓存，并断开连接；后台卡密与历史记录不删除，续期后下一次同步恢复。DFMProxy 手动用户维持独立管理，详细参数中的用户下拉框仅列手动用户。应用冻结/关闭、全局客户端服务关闭、联动关闭及密钥/节点错误返回明确业务错误。

当前上限 10,000 主体；超限返回 413，禁止截断后冒充完整快照。DFMProxy 另有 8 MiB 响应限制。限时应用需要明确到期时间；积分卡、空到期值不映射为永久。软件用户冻结/撤销与已用卡冻结分离。

应用权限和默认代理权限整体作用于对应节点；前端并发/设备字段不直接等价为代理多开。多应用/多节点路由、卡种套餐、多开名额和客户端显示连接信息可在后续版本独立扩展。

## 本地测试

PHPUnit 使用专用 MySQL 库 mybsphp_test。新增 ProxyLinkTest 覆盖入口稳定性、密钥/应用/节点隔离、卡与账号模式、续期、冻结、过期、删除、地址轮换和管理页。DFMProxy 对实际 Laravel 测试响应进行了跨语言消费验证。

本轮后端 181 项通过、2 项跳过，前端 30 项通过，前端构建成功；联动相关文件 PHPStan 零错误。全仓库 PHPStan 在其余模块仍报告 108 项类型检查问题，未将该项记为全量通过。

迁移采用新增表，功能默认关闭。代码发布后由管理员开启并配置 CDN 与 DFMProxy，再进行真实卡密、短时到期及断网验证。

## 代理联动标签空白的排查

新 Blade 页面搭配旧 JavaScript 时可能只显示“代理联动”标签，而没有开关与内容。必须同时发布更新后的前端资源：

```sh
npm ci
npm run build
php artisan optimize:clear
```

确认 `public/build/manifest.json` 及其引用的资源一起部署；按实际缓存规则刷新 CDN 的后台页面 / manifest 缓存，浏览器强制刷新并重新打开软件配置。仅在服务器运行数据库迁移不会更新前端组件。本次开关修正没有新增数据库迁移，已执行过 v133.4 迁移的站点无需追加表结构变更。

### Git-only 部署兼容（2026-09-22）

- 软件配置页面检查 `public/build/software-ui-version.json` 的组件版本、manifest 摘要及入口依赖文件。缺少标记、旧版本或资源不完整时，保留服务端表单，不交给旧 Vue 包隐藏；兼容界面的联动开关仍然自动提交、生成地址。
- `npm run build` 成功后自动生成版本标记；发布 `public/build` 时应同步整个目录。检查通过后自动恢复 Vue 界面。Vite 开发模式仍从源码加载。
- 只更新 Git 源码的站点也可使用联动开关。更新到本次提交后执行 `composer dump-autoload -o` 和 `php artisan optimize:clear`，刷新后台页面；数据库结构保持不变。
- 若更新后仍出现原来的空白页，核对部署实际目录、分支与提交是否更新（尤其前一次 Git 克隆失败时），以及 PHP-FPM/OPcache、CDN 是否仍提供旧页面；先确认新源码已到达运行目录，再排查缓存。
