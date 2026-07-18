# PREVIEW-5 · 蓝白登录页与后台首页

## 基本信息

- 日期：2026-07-18
- 状态：DONE
- 依赖：PREVIEW-4 整体 UI 风格确认

## 实现

- 登录页改为浅蓝背景、左右分栏、白色表单和蓝色按钮。
- 保留管理员账号、主密码、二级密码和语言。
- 后台改为深蓝渐变顶栏、白色菜单侧栏和浅灰蓝工作区。
- 首页增加四项指标、应用概览、快捷操作和最近动态。
- 移除黑绿主题、超大衬线标题和开发期引导文案。
- 增加桌面侧栏折叠、手机抽屉、遮罩关闭和页面刷新。
- 手机端增加两列指标与底部四入口导航。

## 验证

```text
PHP view syntax：PASS
JavaScript syntax：PASS
管理员二级密码单元测试：PASS
Legacy license regression：PASS
1440×900 登录页截图：PASS
1440×900 后台首页截图：PASS
移动断点页面截图：PASS
```

## 预览

- `docs/assets/mybsphp-login-blue-preview.png`
- `docs/assets/mybsphp-dashboard-blue-preview.png`

## 后续

- W3 后台组件任务将把导航项接入真实路由。
- W4/W5 业务页面复用本次按钮、面板、表单、状态和响应式布局规范。

