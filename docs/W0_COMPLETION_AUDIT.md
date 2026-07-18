# W0 调研与需求冻结完成验收

> 日期：2026-07-18  
> 结果：**PASS / W0 DONE**  
> 范围：只完成调研、契约、架构与业务规则冻结；正式 Laravel 业务代码从 W1-0 开始。

## 1. 任务证据

| 任务 | 状态 | 验收内容 | 主要证据 | 独立日志 |
|---|---|---|---|---|
| W0-0 | DONE | 旧版目录、首次安装、动态后台入口 | [LEGACY_REVIEW.md](LEGACY_REVIEW.md) | [W0-0.md](logs/W0-0.md) |
| W0-1 | DONE | `AppEn.php`、`applibapi`、处理器优先级 | [LEGACY_REVIEW.md](LEGACY_REVIEW.md) | [W0-1.md](logs/W0-1.md) |
| W0-2 | DONE | `UIInitializer.mm` 请求与固定响应位置 | [CLIENT_CONTRACT.md](CLIENT_CONTRACT.md) | [W0-2.md](logs/W0-2.md) |
| W0-3 | DONE | 在线后台菜单、页面结构、视觉与移动端策略 | [UI_RESEARCH.md](UI_RESEARCH.md) | [W0-3.md](logs/W0-3.md) |
| W0-4 | DONE | Laravel 13 + PHP 8.3 + MySQL 8 | [FRAMEWORK_DECISION.md](FRAMEWORK_DECISION.md) | [W0-4.md](logs/W0-4.md) |
| W0-5 | DONE | 首版包含项、排除项和两种模式 | [DEVELOPMENT_SPEC_DRAFT.md](DEVELOPMENT_SPEC_DRAFT.md) | [W0-5.md](logs/W0-5.md) |
| W0-6 | DONE | ER、约束、状态机、事务、幂等和迁移分组 | [DATABASE_DESIGN.md](DATABASE_DESIGN.md) | [W0-6.md](logs/W0-6.md) |
| W0-7 | DONE | 全量 API 目录、Legacy v1 与 REST v2 | [API_CATALOG.md](API_CATALOG.md) | [W0-7.md](logs/W0-7.md) |
| W0-8 | DONE | 可选二级密码，新安装默认关闭 | [二级密码决策](decisions/W0-8_ADMIN_SECONDARY_PASSWORD.md) | [W0-8.md](logs/W0-8.md) |
| W0-9 | DONE | 一级代理、整数分余额、等级价自助提卡 | [代理规则](decisions/W0-9_AGENT_RULES.md) | [W0-9.md](logs/W0-9.md) |

## 2. W0 验收门

| 验收门 | 结论 | 冻结结果 |
|---|---|---|
| Laravel 方案确认 | PASS | PHP 8.3、Laravel 13、MySQL 8、Blade + Alpine.js |
| 数据模型确认 | PASS | 约 30 张逻辑表、M00–M10、两模式、状态机、事务与索引 |
| API 清单确认 | PASS | 安装、后台、应用、卡密、账户、代理、Legacy、风控与审计均已编号 |
| 管理员二级密码策略 | PASS | 可选全局开关，安装默认关闭；账号级要求；独立哈希与审计 |
| 代理规则确认 | PASS | 首版一级代理；管理员手动充扣；代理按等级价从库存自助提卡 |

## 3. 已冻结的关键契约

### 3.1 运行与界面

- 正式基线使用 PHP 8.3、Laravel 13、MySQL 8。
- 后台默认 `/admin`，安装时允许改写为合规路径。
- Blade + Alpine.js 响应式实现；验收宽度为 390px、768px 和 1280px。
- 旧版 UI 只参考信息架构和视觉，不延续 iframe/页签式技术结构。

### 3.2 业务

- 两种应用模式：`card_login` 与 `account_recharge`。
- 后台生成并手动发卡；首版不含公开支付和自动发货。
- 一级代理不创建下级代理；代理等级只用于定价。
- 金额统一用整数分；余额不得为负；钱包流水不可变。
- 代理默认只看自身数据，默认无删卡、解绑、账户管理、改价或余额管理权限。

### 3.3 Legacy 与风险

```text
POST /AppEn.php?appid=APP_ID&md5=APP_MD5
api=login.ic

01|xx|{payload}|{appsafecode}|{daily_signature}|1|{expires_at}|1|{device_id}||
```

- 固定 `arr[0/1/2/3/4/5/6/7/8]`；`arr[3]` 原样回传本次 `appsafecode`。
- 处理器优先级：
  `App Override > Mode Handler > Core Default Handler`。
- 风险策略支持 Observe、Decoy、Block；Decoy 只替换 `arr[2]`。
- `arr[2]` 可输出真实 10 位 HEX、空值或应用配置的诱饵值。

### 3.4 首版排除项

- 在线订单、支付、支付回调、退款、补单和自动发货。
- 邮箱、邮件、短信及图形/短信/邮箱验证码。
- 多级代理、返佣、提现和推广。

## 4. 文档一致性检查

执行以下静态验收：

```bash
# W0-0 至 W0-9 的日志全部存在并标记 DONE
for n in {0..9}; do
  test -s "docs/logs/W0-${n}.md"
  grep -q '状态：DONE' "docs/logs/W0-${n}.md"
done

# 核心设计文件存在
test -s docs/DATABASE_DESIGN.md
test -s docs/API_CATALOG.md
test -s docs/decisions/W0-8_ADMIN_SECONDARY_PASSWORD.md
test -s docs/decisions/W0-9_AGENT_RULES.md

# W0 主状态表中不再有 TODO / DOING / REVIEW / BLOCKED
sed -n '/## 4. 阶段 W0/,/### W0 验收门/p' docs/AI_MASTER_PLAN.md \
  | grep -Eq 'TODO|DOING|REVIEW|BLOCKED' && exit 1 || true
```

检查结果：

- 10/10 份 W0 任务日志存在且状态为 `DONE`。
- 10/10 个 W0 主任务在总计划中为 `DONE`。
- 数据库、API、二级密码和代理规则四项门禁文档均存在。
- W0 状态区没有遗留的 `TODO`、`DOING`、`REVIEW` 或 `BLOCKED`。
- 40 个 Markdown 相对链接全部有效，27 份 Markdown 的代码围栏全部闭合。
- 旧原型回归 `php tests/run_license_unit.php` 通过。
- 本阶段未把现有自制 PHP 原型冒充为正式 Laravel 交付物。

## 5. 本机环境快照

```text
PHP 8.1.33
Composer 2.9.2
MySQL Client 9.4.0
```

这是 W0 完成时的现状，不是正式工程基线。W1-1 将 PHP 切换为 8.3 并验证扩展，W1-2
将开发/测试数据库按 MySQL 8 基线配置；macOS 使用 Homebrew，不引入 phpStudy。

## 6. 结论与下一任务

W0 的调研、需求冻结和全部验收门已完成。下一项是 **W1-0：冻结当前原型，建立正式
Laravel 目录**；随后在 W1-1 验证 PHP 8.3 和扩展，在 W1-2 接入 MySQL 8。
