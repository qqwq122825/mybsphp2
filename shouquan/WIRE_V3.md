# BSPHP Wire v3 紧凑数据包说明

Wire v3 只改变 HTTP Body 的序列化外观，RSA、AES、HMAC、时间戳、nonce 和响应绑定的
计算规则保持不变。客户端内部会恢复为协议 v2 加密上下文后再进行统一验签和解密。

## 请求格式

```json
[3, TIMESTAMP, REQUEST_NONCE, WRAPPED_KEY, REQUEST_IV, CIPHERTEXT, MAC, AUTH]
```

| 下标 | 内部含义 | 类型 | 说明 |
| ---: | --- | --- | --- |
| `0` | wire version | number | 固定为 `3` |
| `1` | timestamp | number | Unix 秒时间戳，服务端检查允许时间窗口 |
| `2` | request nonce | string | 16 字节随机值的 Base64URL，服务端用于防重放 |
| `3` | wrapped key | string | RSA-OAEP 封装后的 32 字节随机会话密钥 |
| `4` | request IV | string | 16 字节 AES IV 的 Base64URL |
| `5` | ciphertext | string | AES-256-CBC 加密后的业务参数 |
| `6` | MAC | string | 本次会话 MAC Key 生成的 HMAC-SHA256 |
| `7` | auth | string | 通信认证 Key 对整个请求上下文生成的 HMAC-SHA256 |

## 响应格式

```json
[3, TIMESTAMP, RESPONSE_NONCE, REQUEST_NONCE, RESPONSE_IV, CIPHERTEXT, MAC]
```

| 下标 | 内部含义 | 类型 | 说明 |
| ---: | --- | --- | --- |
| `0` | wire version | number | 固定为 `3` |
| `1` | timestamp | number | 服务端响应 Unix 秒时间戳 |
| `2` | response nonce | string | 服务端生成的 16 字节随机值 |
| `3` | request nonce | string | 必须等于本次请求的 nonce，用于响应绑定 |
| `4` | response IV | string | 响应 AES IV |
| `5` | ciphertext | string | 加密后的授权响应 |
| `6` | MAC | string | 对响应方向、时间、nonce、请求 nonce、IV 和密文的 HMAC |

## 服务端位置

服务端展开和压缩逻辑位于：

```text
laravel/app/Http/Controllers/LegacyGatewayController.php
```

相关方法：

```text
expandCompactRequest()
compactResponse()
```

服务端同时接受原协议 v2 字典数据包和 wire v3 紧凑数组。客户端发送紧凑数组时，
服务端也返回紧凑数组；业务错误仍返回普通 JSON 错误对象。

## iOS Demo 位置

客户端序列化和还原逻辑位于：

```text
shouquan/shouquan/NetTool.m
```

相关方法：

```text
compactRequestEnvelope:
expandResponsePacket:
```

`BSPHPTransportCrypto.mm` 只负责密码学操作，不依赖 wire v3 的数组位置，因此后续替换传输外观时
不需要重新实现 RSA/AES/HMAC。

## 错误响应

错误响应保留可读结构，例如：

```json
{
  "ok": false,
  "error": {
    "code": "PACKET_REPLAYED",
    "message": "数据包 nonce 已使用。"
  }
}
```

这样客户端仍可向开发者展示准确错误信息。
