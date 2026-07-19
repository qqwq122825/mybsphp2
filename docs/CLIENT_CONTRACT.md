# W0 iOS 客户端与 Legacy v1 契约

## 1. 权威客户端样本

- `示例/ios/Ai/Yz/UIInitializer.mm`
- `示例/ios/Ai/Yz/UIInitializer.h`
- `示例/ios/Ai/Yz/NetTool.mm`
- `示例/ios/Ai/Yz/NSDictionary+StichingStringkeyValue.m`
- `示例/ios/Ai/Yz/curl/DES3Util.m`

服务器样本：

- `示例/include/applibapi/api/Default/AppEn.login.ic.php`
- `示例/include/applibapi/api/Default/AppEn.weburl.in.php`
- `示例/include/applibapi/api/CardPoint/AppEn.login.ic.php`
- `示例/include/applibapi/output/bsphp_josn.php`

## 2. 请求契约

`UIInitializer.mm` 的验证函数发送：

```objc
param[@"api"] = @"login.ic";
param[@"BSphpSeSsL"] = [UIInitializer getSystemDate];
param[@"date"] = [UIInitializer getSystemDate];
param[@"mutualkey"] = BSPHP_MUTUALKEY;
param[@"icid"] = km;
param[@"icpwd"] = @"";
param[@"key"] = [UIInitializer serialNumber];
param[@"maxoror"] = [UIInitializer serialNumber];
param[@"appsafecode"] = appsafecode;
```

客户端把字典拼为 Query String，再放入：

```text
json=ok&parameter=...
```

首版 Legacy v1 需要接受：

| 字段 | 含义 |
|---|---|
| `api` | 逻辑接口名称 |
| `BSphpSeSsL` | 会话/多开标识 |
| `date` | 客户端请求时间 |
| `mutualkey` | 应用通信认证值 |
| `icid` | 卡密 |
| `icpwd` | 可选卡密密码 |
| `key` | 绑定设备标识 |
| `maxoror` | 在线设备/并发标识 |
| `appsafecode` | 客户端本次请求核对值 |

## 3. 外层响应

Legacy JSON 外壳：

```json
{
  "response": {
    "data": "PIPE_STRING",
    "date": "SERVER_DATE",
    "unix": 0,
    "microtime": "0.0000"
  }
}
```

响应编码或加密发生在该外壳生成之后。

## 4. `login.ic` 固定位置

成功数据：

```text
01|xx|{business_value}|{appsafecode}|{daily_signature}|1|{expires_at}|1|{device_id}||
```

| 下标 | 字段 | 约束 |
|---|---|---|
| `arr[0]` | 成功标记 | 固定 `01` |
| `arr[1]` | 客户端识别标记 | 固定 `xx` |
| `arr[2]` | 软件配置中的“验证数据” | 地址、编码值、文本、诱饵值或空；默认原样返回 |
| `arr[3]` | `appsafecode` | 原样回传 |
| `arr[4]` | UTC 每日签名 | 固定8字符 |
| `arr[5]` | 登录成功位 | 固定 `1` |
| `arr[6]` | 到期时间 | 标准时间字符串 |
| `arr[7]` | 绑定成功位 | 固定 `1` |
| `arr[8]` | 绑定设备号 | 与服务端最终绑定结果一致 |
| `arr[9..10]` | 保留空字段 | 保持结尾 `||` |

`arr[2]` 为空时仍保留分隔符，后续字段下标不移动。

## 5. 当前样本错位

审阅发现：

1. 客户端把 `arr[2]` 当作10位编码业务值处理。
2. `Default/AppEn.login.ic.php` 当前返回固定 `1`。
3. 10位值生成逻辑位于 `Default/AppEn.weburl.in.php`。
4. 客户端发送 `appsafecode`，旧 PHP 没有按目标位置回传。
5. 首次绑定更新数据库后，旧代码仍可能使用更新前的 `$carinfo` 返回空设备号。
6. `CarLogin()` 存在重复调用。
7. `CardPoint` 与 `Default` 的返回布局不同。

新版以本文件的固定契约为准，并用 Contract Test 固定。

## 6. 每日签名

为兼容当前客户端，首版保留旧算法：

```text
UTC YYYYMMDD
→ (YYYYMMDD + MASTER_KEY) × 1103515245
→ 截取 uint32
→ XOR 0xA5A5A5A5
→ Base36
→ 固定8字符
```

该字段属于客户端核对信号。服务端不把它单独当作请求认证依据。

## 7. 重放与风险基线

`appsafecode` 原样回传只能让客户端核对本次响应，不单独承担服务端重放拦截。

服务端首版组合：

- 请求时间窗口。
- 服务端接收时间。
- 请求指纹。
- Nonce/`appsafecode` 使用记录。
- 同卡密短时间登录次数。
- 同卡密短时间设备数量。
- `BSphpSeSsL` 在线会话。
- 应用风险策略。

风险动作：

- `observe`：正常响应并记录。
- `decoy`：用策略值替换 `arr[2]`。
- `block`：稳定失败响应并记录。

## 8. Handler 优先级

```text
应用覆盖处理器
    > 当前模式处理器
        > 系统默认处理器
```

自定义只改变声明允许的字段和业务逻辑，不允许破坏外层 JSON 与已锁定字段位置，除非应用明确选择独立 Response Profile。

## 9. W0 契约测试清单

W8 阶段至少实现：

- `arr[2]` 非空。
- `arr[2]` 为空。
- `arr[3]` 原样回传。
- `arr[4]` UTC 日期边界。
- `arr[6]` 到期时间。
- `arr[8]` 首次绑定后立即返回。
- 结尾保留 `||`。
- Observe 不替换 `arr[2]`。
- Decoy 只替换 `arr[2]`。
- Block 使用稳定错误结构。
- 过期、冻结、异机和不存在卡密。
- Legacy JSON 外壳。
