# 登录 `ih` 与蜜罐事件 — 服务端已落地协议

2026-09-10 联调更新：沿用现有检测器，补齐 CRC / T0 的登录、心跳、补报和授权撤销链路。旧客户端字段保持兼容；新增结果字段均为可选。

网关不变：`POST /AppEn.php?appid=APP_CODE&md5=GATEWAY_TOKEN`，外层仍是 Hybrid / Wire v3。下面写的都是**解密后的明文参数**。

---

## 1. 登录：可选字段 `ih`

`login.ic` / `login.lg` 明文里可带：

```text
api=login.ic
icid=卡密
key=设备号
BSphpSeSsL=64位会话
appsafecode=...
ih={"cm":0,"o":2,"c":1,"dc":1,"s":[1,5,9],"ls":5,"lk":"imp","lo":0}
```

- 键名必须是 **`ih`**（不是 `integrity_hint`）。
- 值是 **JSON 字符串**，最长 512 字节；客户端现有上限约 400 字节，兼容。
- **不带 `ih`、空字符串、非法 JSON、全 0 空账本**：不因缺少信号处罚；这不表示服务器证明客户端完整。
- 服务端**不会**把 `ih` 回显给客户端。管理员「客户端请求日志 / card_login_trace」**全文明文**（含 `ih`、session、safe code、响应字段），便于自查。

### `ih` JSON 字段（与客户端 `MTXCanaryLedgerExportHint` 一致）

| 键 | 类型 | 含义 |
|---|---|---|
| `cm` | 0\|1 | TEXT CRC：本地已算完时带上；`0` 匹配，`1` 失配。超时未算完则省略本字段 |
| `tm` | 0\|1 | 本进程现有 T0 结构异常探针已命中（hard_hit 或 extra_text_count）；不是新增探针，不从历史队列恢复 |
| `o` | int | 观察（I0）累计 |
| `c` | int | 确认（D0）累计 |
| `dc` | int | 诱饵槽确认次数 |
| `s` | int[] | 命中过的槽号，例如 `[1,5,9]` |
| `ls` | int | 最近一次槽号 |
| `lk` | string | 最近一次 kind：`imp` / `code` / `image` |
| `lo` | 0\|1 | 最近一次是否跳到主镜像外 |

> 不再发送版本号 `v`。干净设备常见：`{"cm":0}`。

槽号是客户端约定的整数，服务端按表认：

| 槽 | 服务端分类 |
|---:|---|
| 1–6 | 诱饵 |
| 8、9–16 | 真链（设备号 / 门禁 / 日签 / 解密 / 能力 / BXHost / BXBox） |
| 7、其余 | 不按诱饵、不按真槽特殊处理 |

### 登录处置（看该软件「蜜罐策略 → 处置模式」）

| 条件 | 关闭 | 观察 | 处置 |
|---|---|---|---|
| 不带 / 空 / 坏 JSON | 忽略 | 忽略 | 忽略 |
| `dc>0`，或 `s` 含 1–6 且 `c>0` | 忽略 | 只记事件 | **登录前拉黑设备+IP，不建会话** |
| `cm=1`（TEXT CRC 失配） | 忽略 | 只记事件 | **登录前拉黑设备+IP，不建会话** |
| `tm=1`（现有 T0 结构异常） | 忽略 | 只记事件 | **登录前拉黑设备+IP，不建会话** |
| 只有真槽 8 / 9–16 | 忽略 | 只记观察 | **只观察，正常登录** |

卡密模式对外固定：

```text
INVALID_CARD / 卡密不存在
```

账号模式对外仍是「账号或密码错误」。后台日志写清真实原因，例如：

```text
登录 ih：诱饵槽确认被 Hook（dc=1 c=1 槽 1,5 kind=imp），已拉黑设备。后续任意软件登录显示卡密不存在。
```

换卡密、换软件，同一设备仍拒。

---

## 2. `security.report`：认 evidence，不改接口

```text
api=security.report
BSphpSeSsL=...
key=设备号
event_id=...
event_code=D0
report_token=...
evidence={"slot":2,"invoked":true,"decoy":true}
```

`event_code`：`D0` `D1` `R0` `S0` `I0` `I1` `CRC` `T0`。客户端 CRC 使用服务端已有的 `CRC` 事件码，避免与普通 I0 共用去重槽。
`evidence` JSON 编码后仍最多 **512** 字节。

服务端会读这些键（有则用，没有不报错）：

| 键 | 作用 |
|---|---|
| `slot` | 整数槽号 |
| `decoy` | true / 1 / "true" 当诱饵 |
| `invoked` | true 当诱饵方法被调用 |
| `kind` `persona` | kind 与明确的 cm/tm 配对分类；persona 仅记录 |
| `cm` | I0 + kind=text + cm=1 按 CRC 失配处置；普通 I0 仍观察 |
| `tm` | T0 + kind=text + tm=1 接入现有结构异常处置；旧 T0 仍观察 |

### report 处置（处置模式 = 处置时）

| evidence | `event_code` | 动作 |
|---|---|---|
| `decoy` / `invoked` / slot 1–6 | D0 | 冻会话、冻卡、拉黑设备+IP |
| slot 8 / 9–16，且非诱饵 | D0 | **只观察**（避免真链误杀） |
| 旧包：无 slot，只有 `selector_hash` 等 | D0 | 仍按高置信拉黑（兼容已有客户端） |
| 任意 | D1 / R0 / S0 | 与原来一样，处置下拉黑 |
| 任意 | CRC | 处置模式下冻结会话/授权主体、拉黑设备+IP |
| kind=text, cm=1 | I0 | 与 CRC 一致 |
| kind=text, tm=1 | T0 | 处置模式下冻结会话/授权主体、拉黑设备+IP |
| 其余 | I0 / I1 / T0 | 只观察 |

成功响应在原四项后追加 action，原位置不移动：

```text
01|accepted|REQUEST_SAFE_CODE|SERVER_TIME|freeze
```

action 为 `freeze` 或 `observe`。旧客户端继续读前四项；新客户端在校验响应和 safe code 后，仅对匹配会话立即停止心跳授权。旧服务器四字段回包仅表示接收，不凭空推断冻结。

相同 event_id 的同一设备/同一事件码重试返回原处置结果；不同事件码/设备占用同一 ID 返回错误。客户端升级 evidence 时生成新 ID，网络重试复用原 ID。

观察 / 关闭模式：只记或不收，不拉黑。客户端自身日签失配仍停止本地授权。

---

## 3. 心跳、客户端生命周期与账本

- 登录及 `timeout.ic` / `timeout.lg` 共用 `ih` 导出。CRC 只用本进程实际扫描结果；磁盘旧 cm 留作诊断，不作为当前扫描结论。
- 登录最多短等 350ms；迟到的 CRC 用持久队列补报，下一次心跳同时补带 ih。服务端先验证心跳会话/设备，再处理 ih，处置后本拍返回 5026。
- 心跳每次检查会话绑定设备的全局黑名单，即使请求省略 key；其他软件的旧活动会话也在下一拍失效。
- HUD 从本地 lease 读取参数后，先取得有效服务器心跳，再安装 capability / 启动 Reader；本地 lease 不再免检启动。
- 心跳日签失配先排队 S0，再立即发布授权失效；旧成功回包受会话 generation 约束，不恢复已撤销授权。
- 现有 canary 的 `I0` / `D0` 与槽号分类保留；补报会话切换后忽略旧回包。接收 ACK 仅删除相同 event_id 的队列条目，不清累计 canary 账本。
- **不要**在 report 成功后调用 `MTXCanaryLedgerClearAfterAcceptedReport()`。服务端没有「可以清」的回包，清早了会漏报。
- **不要**给 `ih` 再套一层时间戳加密。整包已经加密+nonce；缺字段或伪造干净账本无法靠再加密解决。
- **不要**做成「没带 `ih` 就封」——旧包和未部署 canary 的版本会误伤。

---

## 4. 联调怎么确认服务端已接上

1. 软件后台：封控设置 → 蜜罐策略 → **处置 · 高置信自动冻结**（默认是观察，观察时只记不封）。
2. 登录带诱饵 `ih`（`dc>0` 或槽 1–6 且 `c>0`）→ 客户端看到「卡密不存在」，后台「客户端请求日志」有 `client.security_auto_block`，原因含「登录 ih」「诱饵槽」。
3. 登录只带 `s:[9]` 且 `dc=0` → **能登录成功**，事件为观察。
4. 登录成功后 `security.report` D0 + `{"slot":2,"decoy":true}` → 当前会话失效，再登录仍「卡密不存在」。
5. 同设备换一张卡再登录 → 仍「卡密不存在」。
6. 响应里不把原始 `ih` 回给客户端；后台登录诊断日志明文可见。
7. 登录无 cm 或 cm=0 成功后，上报 CRC，或下一拍携带 cm=1 / tm=1 → 本拍/下一拍 5026，旧会话停止续权。
8. 本地自动化：先运行客户端 `Tests/run_integrity_tests.sh`，再设置 `MTX_WIRE_CLIENT` 和 `PHP_BIN` 运行服务端 `tests/run_mtx_integration.sh`。测试数据库固定 127.0.0.1/mybsphp_test。

---

## 5. 实现位置（服务端仓库）

- 解析：`app/Security/CanaryIntegrityHint.php`
- 登录/分级：`app/Security/CanaryIntegrityHintService.php`
- 网关：`app/Http/Controllers/LegacyGatewayController.php`（`login.ic` / `login.lg` 读 `ih`）
- report：`app/Security/ClientSecurityReportService.php`
- 拉黑原因：`app/Security/DeviceSecurityStateService.php`（审计动作 `client.security_auto_block`）
- 迁移：`database/migrations/2026_08_31_000036_allow_login_ih_security_events_without_session.php`（登录时还没有 session 也能写事件）
