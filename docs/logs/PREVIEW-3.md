# PREVIEW-3 · 覆盖安装与安装锁重装流程

## 基本信息

- 日期：2026-07-18
- 状态：DONE
- 范围：安装 UI 原型

## 目标

- 数据库存在管理员或旧表时给出明确选择。
- 覆盖安装必须二次确认。
- 安装锁存在时通过服务器本地一次性令牌进入重装模式。

## 实现

- 数据库步骤检测非空 Schema。
- 默认停止并提示选择其他数据库或覆盖。
- 覆盖选择只保存到安装 Session，最后一步才执行。
- 最后一步必须输入数据库名并勾选清除确认。
- 已安装站点使用 `php bin/enable-reinstall.php` 生成 30 分钟一次性 URL。
- 成功后删除重装令牌并重写安装锁。
- 管理员和超级管理员角色绑定保持事务一致。

## 验证

```text
PHP syntax：PASS
非空数据库友好提示：PASS
勾选覆盖后进入下一步：PASS
临时 MySQL 覆盖安装：PASS
管理员 + super_admin + 二级密码：PASS
重装令牌正确/错误值：PASS
临时数据库和 Fixture 自动清理：PASS
```

## 文档

- `docs/REINSTALL.md`
- `tests/run_install_mysql_integration.php`

