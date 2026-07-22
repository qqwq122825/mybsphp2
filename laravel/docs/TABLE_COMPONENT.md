# 后台表格组件约定

后台新增或改造表格时，统一使用以下 Blade 组件，不再单独创建表格外框、工具栏和滚动容器。

- `x-admin.table-shell`：完整列表页，包含标题、搜索区、表格和分页。
- `x-admin.table-frame`：嵌入现有页面的表格，包含工具栏、横向滚动和页脚。

```blade
<x-admin.table-shell title="列表标题">
    <x-slot:search>...</x-slot:search>
    <x-slot:toolbar>...</x-slot:toolbar>
    <table>...</table>
    <x-slot:footer>...</x-slot:footer>
</x-admin.table-shell>
```

状态标签、选择框、操作按钮及空数据行均使用 `mybsphp.css` 中的公共样式。
