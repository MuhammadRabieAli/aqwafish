
// Admin Dashboard Management
class AdminManager {
    constructor() {
        this.sidebar = document.getElementById('adminSidebar');
        this.sidebarToggle = document.getElementById('sidebarToggle');
        this.logoutBtn = document.getElementById('logoutBtn');
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.checkAuth();
        this.updateStats();
    }
    
    bindEvents() {
        // Sidebar toggle
        if (this.sidebarToggle) {
            this.sidebarToggle.addEventListener('click', () => this.toggleSidebar());
        }
        
        // Logout button
        if (this.logoutBtn) {
            this.logoutBtn.addEventListener('click', () => this.handleLogout());
        }
        
        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!this.sidebar.contains(e.target) && !this.sidebarToggle.contains(e.target)) {
                    this.closeSidebar();
                }
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                this.sidebar.classList.remove('mobile-open');
                document.querySelector('.admin-main').classList.remove('expanded');
            }
        });
    }
    
    checkAuth() {
        const isLoggedIn = localStorage.getItem('isLoggedIn');
        const userEmail = localStorage.getItem('userEmail');
        
        // Check if user is logged in and has admin privileges
        if (!isLoggedIn || !userEmail || !userEmail.includes('admin')) {
            window.location.href = '/login.html';
            return;
        }
        
        // Update user info in sidebar
        const userName = document.querySelector('.user-name');
        if (userName) {
            userName.textContent = userEmail.split('@')[0];
        }
    }
    
    toggleSidebar() {
        if (window.innerWidth <= 768) {
            this.sidebar.classList.toggle('mobile-open');
        } else {
            this.sidebar.classList.toggle('collapsed');
            document.querySelector('.admin-main').classList.toggle('expanded');
        }
    }
    
    closeSidebar() {
        this.sidebar.classList.remove('mobile-open');
    }
    
    handleLogout() {
        if (confirm('Are you sure you want to logout?')) {
            localStorage.removeItem('isLoggedIn');
            localStorage.removeItem('userEmail');
            localStorage.removeItem('userName');
            localStorage.removeItem('rememberUser');
            window.location.href = '/login.html';
        }
    }
    
    updateStats() {
        // Simulate real-time stats updates
        const stats = {
            users: this.getRandomCount(1200, 1300),
            pendingFish: this.getRandomCount(20, 30),
            approvedFish: this.getRandomCount(880, 920),
            monthlyViews: this.getRandomCount(44000, 47000)
        };
        
        // Update stat numbers with animation
        this.animateCounter('.stat-card:nth-child(1) .stat-number', stats.users);
        this.animateCounter('.stat-card:nth-child(2) .stat-number', stats.pendingFish);
        this.animateCounter('.stat-card:nth-child(3) .stat-number', stats.approvedFish);
        this.animateCounter('.stat-card:nth-child(4) .stat-number', `${(stats.monthlyViews / 1000).toFixed(1)}K`);
        
        // Update activity feed
        this.updateActivityFeed();
    }
    
    getRandomCount(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }
    
    animateCounter(selector, targetValue) {
        const element = document.querySelector(selector);
        if (!element) return;
        
        const isNumeric = typeof targetValue === 'number';
        const target = isNumeric ? targetValue : parseInt(targetValue);
        const current = parseInt(element.textContent) || 0;
        const increment = Math.ceil((target - current) / 20);
        
        if (current < target) {
            const timer = setInterval(() => {
                const newValue = parseInt(element.textContent) + increment;
                if (newValue >= target) {
                    element.textContent = targetValue;
                    clearInterval(timer);
                } else {
                    element.textContent = isNumeric ? newValue : `${(newValue / 1000).toFixed(1)}K`;
                }
            }, 50);
        }
    }
    
    updateActivityFeed() {
        const activities = [
            { icon: '🐟', text: 'New fish submission: Clownfish', time: '2 minutes ago' },
            { icon: '👤', text: 'User john_doe registered', time: '15 minutes ago' },
            { icon: '✅', text: 'Approved: Angelfish submission', time: '1 hour ago' },
            { icon: '🔍', text: 'Fish database updated', time: '3 hours ago' },
            { icon: '📊', text: 'Weekly report generated', time: '5 hours ago' }
        ];
        
        const activityList = document.querySelector('.activity-list');
        if (activityList) {
            activityList.innerHTML = activities.map(activity => `
                <div class="activity-item">
                    <div class="activity-icon">${activity.icon}</div>
                    <div class="activity-content">
                        <span class="activity-text">${activity.text}</span>
                        <span class="activity-time">${activity.time}</span>
                    </div>
                </div>
            `).join('');
        }
    }
    
    // Utility method for showing notifications
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--background);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            box-shadow: 0 4px 12px var(--shadow);
            z-index: 9999;
            max-width: 300px;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
}

// Add notification animations to the page
const notificationStyles = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = notificationStyles;
document.head.appendChild(styleSheet);

// Initialize admin manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AdminManager();
});
