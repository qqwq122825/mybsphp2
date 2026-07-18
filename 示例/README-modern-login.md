# 现代化登录系统

这是一个为BsPHP系统设计的现代化登录界面，支持手机端响应式设计，完全兼容现有的BsPHP数据库结构。

## 功能特性

### 🎨 现代化UI设计
- 渐变背景和毛玻璃效果
- 响应式设计，完美支持手机端
- 深色模式支持
- 流畅的动画效果
- 现代化图标和字体

### 🔐 安全功能
- 图形验证码
- 登录尝试次数限制
- 账户锁定机制
- 会话管理和验证
- 记住我功能
- IP地址验证

### 📱 移动端优化
- 完全响应式设计
- 触摸友好的界面
- 移动端侧边栏
- 优化的表单输入

### 🔧 技术特性
- 兼容现有BsPHP数据库结构
- AJAX异步登录
- 实时表单验证
- 错误处理和用户反馈
- 自动会话检查

## 文件结构

```
├── modern-login.html          # 登录页面
├── dashboard.php             # 仪表板页面
├── css/
│   ├── modern-login.css      # 登录页面样式
│   └── modern-dashboard.css   # 仪表板样式
├── js/
│   ├── modern-login.js       # 登录页面脚本
│   └── modern-dashboard.js   # 仪表板脚本
└── api/
    ├── modern-login.php      # 登录API
    ├── check-session.php     # 会话检查API
    └── logout.php            # 退出登录API
```

## 安装和使用

### 1. 文件部署
将所有文件上传到您的BsPHP系统根目录。

### 2. 数据库兼容性
系统完全兼容现有的BsPHP数据库结构，使用以下表：
- `bs_php_user` - 用户表
- `bs_php_login_log` - 登录日志表（自动创建）
- `bs_php_logout_log` - 退出日志表（自动创建）

### 3. 访问登录页面
在浏览器中访问：
```
http://your-domain.com/modern-login.html
```

### 4. 登录后跳转
登录成功后会跳转到：
```
http://your-domain.com/dashboard.php
```

## API接口说明

### 登录API (`api/modern-login.php`)
**请求方式**: POST  
**Content-Type**: application/json

**请求参数**:
```json
{
    "username": "用户名",
    "password": "密码",
    "captcha": "验证码",
    "rememberMe": true/false
}
```

**响应示例**:
```json
{
    "success": true,
    "message": "登录成功",
    "data": {
        "redirect": "dashboard.php",
        "userInfo": {
            "uid": 1,
            "username": "admin",
            "email": "admin@example.com",
            "qq": "123456789",
            "isAgent": false
        }
    },
    "timestamp": 1640995200
}
```

### 会话检查API (`api/check-session.php`)
**请求方式**: GET

**响应示例**:
```json
{
    "success": true,
    "loggedIn": true,
    "message": "已登录",
    "data": {
        "userInfo": {
            "user_uid": 1,
            "user_user": "admin",
            "user_email": "admin@example.com",
            "user_qq": "123456789",
            "user_Mobile": "13800138000",
            "user_daili": 0,
            "user_rmb": 1000.00,
            "user_jifen": 5000,
            "user_Login_date": "2023-12-31 12:00:00",
            "user_Login_ip": "127.0.0.1"
        },
        "redirect": "dashboard.php"
    },
    "timestamp": 1640995200
}
```

### 退出登录API (`api/logout.php`)
**请求方式**: POST

**响应示例**:
```json
{
    "success": true,
    "message": "退出登录成功",
    "timestamp": 1640995200
}
```

## 安全特性

### 1. 验证码系统
- 4位随机字符验证码
- 点击刷新功能
- 防止机器人攻击

### 2. 登录限制
- 15分钟内最多5次失败尝试
- 失败次数达到限制后账户锁定15分钟
- IP地址记录和验证

### 3. 会话安全
- MD7验证码验证
- IP地址匹配检查
- 24小时会话超时
- 安全的Cookie处理

### 4. 密码安全
- 兼容BsPHP的md6加密
- 密码长度验证
- 特殊字符检查

## 自定义配置

### 1. 修改样式
编辑 `css/modern-login.css` 文件来自定义样式：
- 修改颜色主题
- 调整布局
- 添加自定义动画

### 2. 修改功能
编辑 `js/modern-login.js` 文件来自定义功能：
- 修改验证规则
- 添加新的验证逻辑
- 自定义错误处理

### 3. 修改API
编辑 `api/modern-login.php` 文件来自定义API：
- 修改登录逻辑
- 添加新的验证规则
- 自定义响应格式

## 浏览器兼容性

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- 移动端浏览器

## 注意事项

1. **数据库权限**: 确保PHP有权限创建新表（登录日志表）
2. **会话配置**: 确保PHP会话配置正确
3. **文件权限**: 确保API文件有执行权限
4. **HTTPS**: 生产环境建议使用HTTPS
5. **备份**: 部署前请备份现有系统

## 故障排除

### 常见问题

1. **登录失败**
   - 检查数据库连接
   - 验证用户表结构
   - 查看错误日志

2. **验证码不显示**
   - 检查Canvas支持
   - 验证JavaScript是否启用

3. **样式不加载**
   - 检查CSS文件路径
   - 验证文件权限

4. **API请求失败**
   - 检查网络连接
   - 验证API文件权限
   - 查看浏览器控制台错误

### 调试模式
在API文件中添加调试信息：
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 更新日志

### v1.0.0 (2023-12-31)
- 初始版本发布
- 现代化UI设计
- 完整的登录功能
- 移动端支持
- 安全特性实现

## 技术支持

如果您在使用过程中遇到问题，请：
1. 检查本文档的故障排除部分
2. 查看浏览器控制台错误信息
3. 检查服务器错误日志
4. 验证数据库连接和表结构

## 许可证

本项目基于MIT许可证开源，您可以自由使用、修改和分发。
