// 现代化仪表板JavaScript
class ModernDashboard {
    constructor() {
        this.init();
        this.userInfo = null;
    }

    init() {
        this.bindEvents();
        this.checkLoginStatus();
        this.loadUserData();
        this.startAutoRefresh();
    }

    bindEvents() {
        // 侧边栏切换
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            this.toggleSidebar();
        });

        // 退出登录
        document.getElementById('logoutBtn').addEventListener('click', () => {
            this.handleLogout();
        });

        // 导航菜单
        document.querySelectorAll('.nav-item a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleNavigation(link);
            });
        });

        // 功能按钮
        document.querySelectorAll('.feature-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleFeatureClick(btn);
            });
        });

        // 通知按钮
        document.querySelector('.notification-btn').addEventListener('click', () => {
            this.showNotifications();
        });

        // 响应式处理
        window.addEventListener('resize', () => {
            this.handleResize();
        });

        // 点击外部关闭侧边栏
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                const sidebar = document.querySelector('.sidebar');
                const toggleBtn = document.getElementById('sidebarToggle');
                
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    }

    // 检查登录状态
    async checkLoginStatus() {
        try {
            const response = await fetch('api/check-session.php');
            const result = await response.json();

            if (!result.loggedIn) {
                this.showNotification('请先登录', 'warning');
                setTimeout(() => {
                    window.location.href = 'modern-login.html';
                }, 1500);
                return;
            }

            this.userInfo = result.data.userInfo;
            this.updateUserInterface();

        } catch (error) {
            console.error('检查登录状态失败:', error);
            this.showNotification('检查登录状态失败', 'error');
        }
    }

    // 加载用户数据
    async loadUserData() {
        try {
            // 模拟加载统计数据
            await this.loadStatistics();
            
            // 模拟加载最近活动
            await this.loadRecentActivity();

        } catch (error) {
            console.error('加载用户数据失败:', error);
        }
    }

    // 加载统计数据
    async loadStatistics() {
        // 这里应该调用实际的API获取统计数据
        // 现在使用模拟数据
        setTimeout(() => {
            document.getElementById('totalUsers').textContent = '1,234';
            document.getElementById('activeUsers').textContent = '567';
            document.getElementById('accountBalance').textContent = '¥1,234.56';
            document.getElementById('userPoints').textContent = '8,888';
        }, 500);
    }

    // 加载最近活动
    async loadRecentActivity() {
        // 这里应该调用实际的API获取活动数据
        // 现在使用模拟数据
        const activities = [
            { icon: 'fas fa-sign-in-alt', text: '成功登录系统', time: '刚刚' },
            { icon: 'fas fa-user-edit', text: '更新了个人资料', time: '2小时前' },
            { icon: 'fas fa-shield-alt', text: '修改了密码', time: '1天前' },
            { icon: 'fas fa-cog', text: '更新了系统设置', time: '3天前' }
        ];

        const activityList = document.getElementById('activityList');
        activityList.innerHTML = '';

        activities.forEach(activity => {
            const activityItem = document.createElement('div');
            activityItem.className = 'activity-item';
            activityItem.innerHTML = `
                <div class="activity-icon">
                    <i class="${activity.icon}"></i>
                </div>
                <div class="activity-content">
                    <p>${activity.text}</p>
                    <span class="activity-time">${activity.time}</span>
                </div>
            `;
            activityList.appendChild(activityItem);
        });
    }

    // 更新用户界面
    updateUserInterface() {
        if (!this.userInfo) return;

        // 更新用户名
        document.getElementById('username').textContent = this.userInfo.user_user || '未知用户';
        
        // 更新用户角色
        const roleText = this.userInfo.user_daili > 0 ? '代理用户' : '普通用户';
        document.getElementById('userRole').textContent = roleText;

        // 更新登录信息
        if (this.userInfo.user_Login_date) {
            const loginTime = new Date(this.userInfo.user_Login_date).toLocaleString('zh-CN');
            document.getElementById('loginTime').textContent = loginTime;
        }
        
        if (this.userInfo.user_Login_ip) {
            document.getElementById('loginIP').textContent = this.userInfo.user_Login_ip;
        }

        // 更新账户余额和积分
        if (this.userInfo.user_rmb !== undefined) {
            document.getElementById('accountBalance').textContent = `¥${parseFloat(this.userInfo.user_rmb).toFixed(2)}`;
        }
        
        if (this.userInfo.user_jifen !== undefined) {
            document.getElementById('userPoints').textContent = this.userInfo.user_jifen;
        }
    }

    // 切换侧边栏
    toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('open');
    }

    // 处理导航
    handleNavigation(link) {
        // 移除所有活动状态
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });

        // 添加当前活动状态
        link.parentElement.classList.add('active');

        // 获取目标页面
        const target = link.getAttribute('href').substring(1);
        
        // 根据目标显示不同内容
        this.showPage(target);

        // 在移动端关闭侧边栏
        if (window.innerWidth <= 768) {
            document.querySelector('.sidebar').classList.remove('open');
        }
    }

    // 显示页面内容
    showPage(page) {
        const contentArea = document.querySelector('.content-area');
        
        switch (page) {
            case 'dashboard':
                this.showDashboard();
                break;
            case 'profile':
                this.showProfile();
                break;
            case 'settings':
                this.showSettings();
                break;
            case 'help':
                this.showHelp();
                break;
            default:
                this.showDashboard();
        }
    }

    // 显示仪表板
    showDashboard() {
        // 仪表板内容已经在HTML中定义
        document.querySelector('.header-left h1').textContent = '仪表板';
    }

    // 显示个人资料
    showProfile() {
        document.querySelector('.header-left h1').textContent = '个人资料';
        this.showNotification('个人资料功能开发中...', 'info');
    }

    // 显示设置
    showSettings() {
        document.querySelector('.header-left h1').textContent = '设置';
        this.showNotification('设置功能开发中...', 'info');
    }

    // 显示帮助
    showHelp() {
        document.querySelector('.header-left h1').textContent = '帮助';
        this.showNotification('帮助功能开发中...', 'info');
    }

    // 处理功能按钮点击
    handleFeatureClick(btn) {
        const featureCard = btn.closest('.feature-card');
        const featureTitle = featureCard.querySelector('h3').textContent;
        
        switch (featureTitle) {
            case '个人资料':
                this.showNotification('个人资料功能开发中...', 'info');
                break;
            case '安全设置':
                this.showNotification('安全设置功能开发中...', 'info');
                break;
            case '登录历史':
                this.showNotification('登录历史功能开发中...', 'info');
                break;
            case '系统设置':
                this.showNotification('系统设置功能开发中...', 'info');
                break;
            default:
                this.showNotification('功能开发中...', 'info');
        }
    }

    // 显示通知
    showNotifications() {
        this.showNotification('通知功能开发中...', 'info');
    }

    // 处理退出登录
    async handleLogout() {
        if (!confirm('确定要退出登录吗？')) {
            return;
        }

        try {
            const response = await fetch('api/logout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('已退出登录', 'success');
                setTimeout(() => {
                    window.location.href = 'modern-login.html';
                }, 1000);
            } else {
                this.showNotification('退出登录失败', 'error');
            }

        } catch (error) {
            console.error('退出登录错误:', error);
            // 即使API失败，也清除本地会话并跳转
            this.showNotification('已退出登录', 'success');
            setTimeout(() => {
                window.location.href = 'modern-login.html';
            }, 1000);
        }
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

    // 处理窗口大小变化
    handleResize() {
        const sidebar = document.querySelector('.sidebar');
        
        if (window.innerWidth > 768) {
            sidebar.classList.remove('open');
        }
    }

    // 开始自动刷新
    startAutoRefresh() {
        // 每5分钟刷新一次统计数据
        setInterval(() => {
            this.loadStatistics();
        }, 5 * 60 * 1000);
    }

    // 格式化时间
    formatTime(timestamp) {
        return new Date(timestamp).toLocaleString('zh-CN');
    }

    // 格式化数字
    formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', () => {
    new ModernDashboard();
});

// 添加一些实用的工具函数
window.DashboardUtils = {
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
    },
    
    // 复制到剪贴板
    copyToClipboard: (text) => {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text);
        } else {
            // 备用方案
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
    }
};
