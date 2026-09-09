# 登录 `ih` 与蜜罐事件 — 服务端已落地协议

给客户端对照：服务端已经按现有字段接收并处置。**客户端协议不用改**，继续发现在的 `ih` / `security.report` 即可。

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
ih={"v":1,"o":2,"c":1,"dc":1,"s":[1,5,9],"ls":5,"lk":"imp","lo":0}
```

- 键名必须是 **`ih`**（不是 `integrity_hint`）。
- 值是 **JSON 字符串**，最长 512 字节；客户端现有上限约 400 字节，兼容。
- **不带 `ih`、空字符串、非法 JSON、全 0 空账本**：当干净设备，不罚。
- 服务端**不会**把 `ih` 回显给客户端。管理员「客户端请求日志 / card_login_trace」**全文明文**（含 `ih`、session、safe code、响应字段），便于自查。

### `ih` JSON 字段（与客户端 `MTXCanaryLedgerExportHint` 一致）

| 键 | 类型 | 含义 |
|---|---|---|
| `v` | int | 版本，现为 `1` |
| `o` | int | 观察（I0）累计 |
| `c` | int | 确认（D0）累计 |
| `dc` | int | 诱饵槽确认次数 |
| `s` | int[] | 命中过的槽号，例如 `[1,5,9]` |
| `ls` | int | 最近一次槽号 |
| `lk` | string | 最近一次 kind：`imp` / `code` / `image` |
| `lo` | 0\|1 | 最近一次是否跳到主镜像外 |

> **`cm`（TEXT CRC）**：客户端只做本地校验，**不再随登录 `ih` 上传**。服务端若收到旧包里的 `cm` 可忽略，不作为拉黑依据。

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

`event_code` 白名单不变：`D0` `D1` `R0` `S0` `I0` `I1` `T0`。  
`evidence` JSON 编码后仍最多 **512** 字节。

服务端会读这些键（有则用，没有不报错）：

| 键 | 作用 |
|---|---|
| `slot` | 整数槽号 |
| `decoy` | true / 1 / "true" 当诱饵 |
| `invoked` | true 当诱饵方法被调用 |
| `kind` `persona` | 只进日志，不单独处罚 |

### report 处置（处置模式 = 处置时）

| evidence | `event_code` | 动作 |
|---|---|---|
| `decoy` / `invoked` / slot 1–6 | D0 | 冻会话、冻卡、拉黑设备+IP |
| slot 8 / 9–16，且非诱饵 | D0 | **只观察**（避免真链误杀） |
| 旧包：无 slot，只有 `selector_hash` 等 | D0 | 仍按高置信拉黑（兼容已有客户端） |
| 任意 | D1 / R0 / S0 | 与原来一样，处置下拉黑 |
| 任意 | I0 / I1 / T0 | 只观察 |

成功响应不变：

```text
01|accepted|REQUEST_SAFE_CODE|SERVER_TIME
```

观察 / 关闭模式：只记或不收，不拉黑。

---

## 3. 客户端不用改、也不要清账本

- 继续：登录有账本才带 `ih`；没有就不带。
- 继续：`queueCode` / `reportCode` 发 `I0` `D0`，evidence 带 `slot` / `kind` / `decoy` / `invoked`。
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

---

## 5. 实现位置（服务端仓库）

- 解析：`app/Security/CanaryIntegrityHint.php`
- 登录/分级：`app/Security/CanaryIntegrityHintService.php`
- 网关：`app/Http/Controllers/LegacyGatewayController.php`（`login.ic` / `login.lg` 读 `ih`）
- report：`app/Security/ClientSecurityReportService.php`
- 拉黑原因：`app/Security/DeviceSecurityStateService.php`（审计动作 `client.security_auto_block`）
- 迁移：`database/migrations/2026_08_31_000036_allow_login_ih_security_events_without_session.php`（登录时还没有 session 也能写事件）
