# PREVIEW-7：正式工程首页与浏览器验收规则

**状态：DONE**

**日期：2026-07-19**

## 问题

8091 根地址仍展示 Laravel 13 默认欢迎页，容易被理解为 MyBsPHP2 的正式 UI。
此前只验证了 HTTP 和框架启动状态，页面交付信息不够清晰。

## 修正

1. 根地址改为 MyBsPHP2 工程基线页。
2. 页面明确标注：
   - 8091：Laravel 正式工程
   - 8090：当前后台原型
   - 当前阶段：W1
   - 下一任务：W1-5
3. 提供“查看当前后台原型”和“检查 API 状态”入口。
4. 在 AI 开发流程中增加每个任务的强制浏览器验收规则。

## 浏览器验收

| 检查项 | 结果 |
|---|---|
| 1280 × 900 | 页面宽度 1280，滚动宽度 1280 |
| 390 × 844 | 页面宽度 390，滚动宽度 390 |
| 手机主要按钮 | 高度 46px |
| 页面标题 | `MyBsPHP2 · 正式工程基线` |
| 浏览器控制台 | 无 error / warn |
| 应用内浏览器 | 已打开并停留在 8091 根页面 |

截图：

- [`W1-baseline-desktop.png`](../assets/qa/W1-baseline-desktop.png)
- [`W1-baseline-mobile.png`](../assets/qa/W1-baseline-mobile.png)

## 自动化验收

- PHPUnit：9 tests / 39 assertions。
- Laravel Pint：通过。
