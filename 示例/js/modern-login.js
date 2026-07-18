// 现代化登录系统JavaScript
class ModernLogin {
    constructor() {
        this.init();
        this.captchaText = '';
        this.maxLoginAttempts = 5;
        this.loginAttempts = 0;
        this.lockoutTime = 15 * 60 * 1000; // 15分钟锁定
    }

    init() {
        this.bindEvents();
        this.generateCaptcha();
        this.loadSavedCredentials();
        this.checkLoginStatus();
    }

    bindEvents() {
        // 表单提交事件
        document.getElementById('loginForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin();
        });

        // 密码显示/隐藏切换
        document.getElementById('togglePassword').addEventListener('click', () => {
            this.togglePasswordVisibility();
        });

        // 验证码刷新
        document.getElementById('refreshCaptcha').addEventListener('click', () => {
            this.generateCaptcha();
        });

        // 验证码点击刷新
        document.getElementById('captchaCanvas').addEventListener('click', () => {
            this.generateCaptcha();
        });

        // 输入框实时验证
        document.getElementById('username').addEventListener('input', (e) => {
            this.validateUsername(e.target.value);
        });

        document.getElementById('password').addEventListener('input', (e) => {
            this.validatePassword(e.target.value);
        });

        document.getElementById('captcha').addEventListener('input', (e) => {
            this.validateCaptcha(e.target.value);
        });

        // 社交登录按钮
        document.querySelector('.qq-btn').addEventListener('click', () => {
            this.showNotification('QQ登录功能开发中...', 'info');
        });

        document.querySelector('.wechat-btn').addEventListener('click', () => {
            this.showNotification('微信登录功能开发中...', 'info');
        });

        // 忘记密码
        document.querySelector('.forgot-password').addEventListener('click', (e) => {
            e.preventDefault();
            this.showNotification('密码重置功能开发中...', 'info');
        });
    }

    // 生成验证码
    generateCaptcha() {
        const canvas = document.getElementById('captchaCanvas');
        const ctx = canvas.getContext('2d');
        
        // 清空画布
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // 生成随机验证码
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        this.captchaText = '';
        for (let i = 0; i < 4; i++) {
            this.captchaText += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        
        // 设置背景
        ctx.fillStyle = '#f8f9fa';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // 添加干扰线
        for (let i = 0; i < 5; i++) {
            ctx.strokeStyle = `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 0.3)`;
            ctx.beginPath();
            ctx.moveTo(Math.random() * canvas.width, Math.random() * canvas.height);
            ctx.lineTo(Math.random() * canvas.width, Math.random() * canvas.height);
            ctx.stroke();
        }
        
        // 绘制验证码文字
        ctx.font = 'bold 18px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        
        for (let i = 0; i < this.captchaText.length; i++) {
            const x = (canvas.width / this.captchaText.length) * i + (canvas.width / this.captchaText.length) / 2;
            const y = canvas.height / 2 + (Math.random() - 0.5) * 10;
            const angle = (Math.random() - 0.5) * 0.5;
            
            ctx.save();
            ctx.translate(x, y);
            ctx.rotate(angle);
            ctx.fillStyle = `hsl(${Math.floor(Math.random() * 360)}, 70%, 50%)`;
            ctx.fillText(this.captchaText[i], 0, 0);
            ctx.restore();
        }
        
        // 添加干扰点
        for (let i = 0; i < 50; i++) {
            ctx.fillStyle = `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 0.3)`;
            ctx.beginPath();
            ctx.arc(Math.random() * canvas.width, Math.random() * canvas.height, 1, 0, 2 * Math.PI);
            ctx.fill();
        }
    }

    // 切换密码显示/隐藏
    togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePassword');
        const icon = toggleBtn.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // 验证用户名
    validateUsername(username) {
        const input = document.getElementById('username');
        if (username.length < 2) {
            this.setInputError(input, '用户名至少需要2个字符');
            return false;
        }
        if (username.length > 20) {
            this.setInputError(input, '用户名不能超过20个字符');
            return false;
        }
        if (!/^[a-zA-Z0-9_\u4e00-\u9fa5]+$/.test(username)) {
            this.setInputError(input, '用户名只能包含字母、数字、下划线和中文');
            return false;
        }
        this.clearInputError(input);
        return true;
    }

    // 验证密码
    validatePassword(password) {
        const input = document.getElementById('password');
        if (password.length < 6) {
            this.setInputError(input, '密码至少需要6个字符');
            return false;
        }
        if (password.length > 20) {
            this.setInputError(input, '密码不能超过20个字符');
            return false;
        }
        this.clearInputError(input);
        return true;
    }

    // 验证验证码
    validateCaptcha(captcha) {
        const input = document.getElementById('captcha');
        if (captcha.length !== 4) {
            this.setInputError(input, '验证码必须是4位');
            return false;
        }
        if (captcha.toUpperCase() !== this.captchaText) {
            this.setInputError(input, '验证码错误');
            return false;
        }
        this.clearInputError(input);
        return true;
    }

    // 设置输入框错误状态
    setInputError(input, message) {
        input.style.borderColor = '#ff4757';
        input.style.boxShadow = '0 0 0 3px rgba(255, 71, 87, 0.1)';
        
        // 移除现有的错误提示
        const existingError = input.parentNode.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
        
        // 添加新的错误提示
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.style.color = '#ff4757';
        errorDiv.style.fontSize = '12px';
        errorDiv.style.marginTop = '5px';
        errorDiv.textContent = message;
        input.parentNode.appendChild(errorDiv);
    }

    // 清除输入框错误状态
    clearInputError(input) {
        input.style.borderColor = '#e1e5e9';
        input.style.boxShadow = 'none';
        
        const errorDiv = input.parentNode.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    // 处理登录
    async handleLogin() {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        const captcha = document.getElementById('captcha').value.trim();
        const rememberMe = document.getElementById('rememberMe').checked;

        // 验证输入
        if (!this.validateUsername(username) || !this.validatePassword(password) || !this.validateCaptcha(captcha)) {
            this.showNotification('请检查输入信息', 'error');
            return;
        }

        // 检查登录尝试次数
        if (this.isAccountLocked()) {
            this.showNotification('账户已被锁定，请稍后再试', 'error');
            return;
        }

        // 显示加载状态
        this.setLoadingState(true);

        try {
            const response = await fetch('api/modern-login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    username: username,
                    password: password,
                    captcha: captcha,
                    rememberMe: rememberMe
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('登录成功！', 'success');
                
                // 保存登录状态
                if (rememberMe) {
                    this.saveCredentials(username);
                }
                
                // 延迟跳转
                setTimeout(() => {
                    window.location.href = result.redirect || 'dashboard.php';
                }, 1000);
            } else {
                this.loginAttempts++;
                this.showNotification(result.message || '登录失败', 'error');
                this.generateCaptcha(); // 刷新验证码
                
                // 检查是否需要锁定账户
                if (this.loginAttempts >= this.maxLoginAttempts) {
                    this.lockAccount();
                }
            }
        } catch (error) {
            console.error('登录错误:', error);
            this.showNotification('网络错误，请稍后重试', 'error');
        } finally {
            this.setLoadingState(false);
        }
    }

    // 设置加载状态
    setLoadingState(loading) {
        const loginBtn = document.getElementById('loginBtn');
        const btnText = loginBtn.querySelector('.btn-text');
        const btnLoading = loginBtn.querySelector('.btn-loading');
        
        if (loading) {
            loginBtn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'flex';
        } else {
            loginBtn.disabled = false;
            btnText.style.display = 'block';
            btnLoading.style.display = 'none';
        }
    }

    // 检查账户是否被锁定
    isAccountLocked() {
        const lockTime = localStorage.getItem('loginLockTime');
        if (lockTime) {
            const now = Date.now();
            const lockTimestamp = parseInt(lockTime);
            if (now - lockTimestamp < this.lockoutTime) {
                return true;
            } else {
                localStorage.removeItem('loginLockTime');
                this.loginAttempts = 0;
            }
        }
        return false;
    }

    // 锁定账户
    lockAccount() {
        localStorage.setItem('loginLockTime', Date.now().toString());
        this.showNotification(`账户已被锁定${this.lockoutTime / 60000}分钟`, 'error');
    }

    // 显示通知
    showNotification(message, type = 'info') {
        const notification = document.getElementById('notification');
        const icon = notification.querySelector('.notification-icon');
        const messageEl = notification.querySelector('.notification-message');
        
        // 移除所有类型类
        notification.classList.remove('success', 'error', 'warning', 'info');
        notification.classList.add(type);
        
        // 设置图标
        const icons = {
            success: '✓',
            error: '✗',
            warning: '⚠',
            info: 'ℹ'
        };
        icon.textContent = icons[type] || icons.info;
        
        // 设置消息
        messageEl.textContent = message;
        
        // 显示通知
        notification.classList.add('show');
        
        // 自动隐藏
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }

    // 保存登录凭据
    saveCredentials(username) {
        localStorage.setItem('savedUsername', username);
    }

    // 加载保存的凭据
    loadSavedCredentials() {
        const savedUsername = localStorage.getItem('savedUsername');
        if (savedUsername) {
            document.getElementById('username').value = savedUsername;
            document.getElementById('rememberMe').checked = true;
        }
    }

    // 检查登录状态
    checkLoginStatus() {
        // 检查是否有有效的登录会话
        fetch('api/check-session.php')
            .then(response => response.json())
            .then(result => {
                if (result.loggedIn) {
                    window.location.href = result.redirect || 'dashboard.php';
                }
            })
            .catch(error => {
                console.log('未检测到登录状态');
            });
    }
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', () => {
    new ModernLogin();
});

// 添加一些实用的工具函数
window.ModernLoginUtils = {
    // 格式化时间
    formatTime: (timestamp) => {
        return new Date(timestamp).toLocaleString('zh-CN');
    },
    
    // 防抖函数
    debounce: (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // 节流函数
    throttle: (func, limit) => {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
};
