# PREVIEW-1 · 管理员六位密码与二级密码

## 基本信息

- 日期：2026-07-18
- 状态：DONE
- 范围：W1 前的安装/登录 UI 原型修正

## 目标

- 管理员主密码最小长度调整为 6 位。
- 首次安装时创建独立二级密码。
- 后台登录同时校验账号、主密码和二级密码。

## 变更

- 安装页增加二级密码和确认二级密码字段。
- 主密码、确认密码、二级密码和确认二级密码均使用 6 位最小长度。
- 新增管理员二级密码数据库迁移。
- 二级密码使用独立 `password_hash`，安装后启用二级密码开关。
- 后台登录增加二级密码输入和统一失败提示。

## 验证

```text
PHP 语法检查：PASS
Admin secondary password validation tests：PASS
License unit regression：PASS
MySQL 临时库迁移：PASS
本地安装页字段检查：PASS
```

## 说明

该任务修正当前可预览原型。Laravel 正式实现仍按 W2-8 的决策、Migration、Feature 测试和
审计要求重新落地。

