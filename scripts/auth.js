
// Authentication Management
class AuthManager {
    constructor() {
        this.loginForm = document.getElementById('loginForm');
        this.registerForm = document.getElementById('registerForm');
        this.passwordToggle = document.getElementById('passwordToggle');
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.setupPasswordStrength();
    }
    
    bindEvents() {
        // Login form
        if (this.loginForm) {
            this.loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }
        
        // Register form
        if (this.registerForm) {
            this.registerForm.addEventListener('submit', (e) => this.handleRegister(e));
            this.setupFormValidation();
        }
        
        // Password toggle
        if (this.passwordToggle) {
            this.passwordToggle.addEventListener('click', () => this.togglePassword());
        }
    }
    
    handleLogin(e) {
        e.preventDefault();
        
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const remember = document.getElementById('remember').checked;
        
        // Simulate login process
        this.showLoading(e.target.querySelector('[type="submit"]'));
        
        setTimeout(() => {
            // Mock authentication
            if (email && password) {
                localStorage.setItem('isLoggedIn', 'true');
                localStorage.setItem('userEmail', email);
                if (remember) {
                    localStorage.setItem('rememberUser', 'true');
                }
                
                // Check if admin credentials
                if (email.includes('admin')) {
                    window.location.href = './admin/dashboard.html';
                } else {
                    window.location.href = './user/dashboard.html';
                }
            } else {
                this.showError('Please enter valid credentials');
            }
            
            this.hideLoading(e.target.querySelector('[type="submit"]'));
        }, 1500);
    }
    
    handleRegister(e) {
        e.preventDefault();
        
        if (!this.validateForm()) {
            return;
        }
        
        const formData = {
            fullName: document.getElementById('fullName').value,
            email: document.getElementById('email').value,
            password: document.getElementById('password').value,
            terms: document.getElementById('terms').checked
        };
        
        // Simulate registration process
        this.showLoading(e.target.querySelector('[type="submit"]'));
        
        setTimeout(() => {
            // Mock registration
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('userEmail', formData.email);
            localStorage.setItem('userName', formData.fullName);
            
            window.location.href = '/user/dashboard.html';
        }, 2000);
    }
    
    setupFormValidation() {
        const fullName = document.getElementById('fullName');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');
        
        if (fullName) {
            fullName.addEventListener('blur', () => this.validateFullName());
        }
        
        if (email) {
            email.addEventListener('blur', () => this.validateEmail());
        }
        
        if (password) {
            password.addEventListener('input', () => {
                this.validatePassword();
                this.updatePasswordStrength();
            });
        }
        
        if (confirmPassword) {
            confirmPassword.addEventListener('blur', () => this.validateConfirmPassword());
        }
    }
    
    validateForm() {
        return this.validateFullName() && 
               this.validateEmail() && 
               this.validatePassword() && 
               this.validateConfirmPassword();
    }
    
    validateFullName() {
        const fullName = document.getElementById('fullName');
        const error = document.getElementById('fullNameError');
        
        if (!fullName.value.trim()) {
            this.showFieldError(error, 'Full name is required');
            return false;
        }
        
        if (fullName.value.trim().length < 2) {
            this.showFieldError(error, 'Full name must be at least 2 characters');
            return false;
        }
        
        this.hideFieldError(error);
        return true;
    }
    
    validateEmail() {
        const email = document.getElementById('email');
        const error = document.getElementById('emailError');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (!email.value) {
            this.showFieldError(error, 'Email is required');
            return false;
        }
        
        if (!emailRegex.test(email.value)) {
            this.showFieldError(error, 'Please enter a valid email address');
            return false;
        }
        
        this.hideFieldError(error);
        return true;
    }
    
    validatePassword() {
        const password = document.getElementById('password');
        const error = document.getElementById('passwordError');
        
        if (!password.value) {
            this.showFieldError(error, 'Password is required');
            return false;
        }
        
        if (password.value.length < 8) {
            this.showFieldError(error, 'Password must be at least 8 characters');
            return false;
        }
        
        this.hideFieldError(error);
        return true;
    }
    
    validateConfirmPassword() {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');
        const error = document.getElementById('confirmPasswordError');
        
        if (!confirmPassword.value) {
            this.showFieldError(error, 'Please confirm your password');
            return false;
        }
        
        if (password.value !== confirmPassword.value) {
            this.showFieldError(error, 'Passwords do not match');
            return false;
        }
        
        this.hideFieldError(error);
        return true;
    }
    
    showFieldError(errorElement, message) {
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }
    
    hideFieldError(errorElement) {
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
    }
    
    setupPasswordStrength() {
        const password = document.getElementById('password');
        if (password) {
            password.addEventListener('input', () => this.updatePasswordStrength());
        }
    }
    
    updatePasswordStrength() {
        const password = document.getElementById('password');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        
        if (!password || !strengthFill || !strengthText) return;
        
        const value = password.value;
        let strength = 0;
        let strengthLabel = '';
        
        // Calculate strength
        if (value.length >= 8) strength++;
        if (/[a-z]/.test(value)) strength++;
        if (/[A-Z]/.test(value)) strength++;
        if (/[0-9]/.test(value)) strength++;
        if (/[^A-Za-z0-9]/.test(value)) strength++;
        
        // Update display
        strengthFill.className = 'strength-fill';
        
        if (value.length === 0) {
            strengthText.textContent = 'Password strength';
        } else if (strength <= 2) {
            strengthFill.classList.add('weak');
            strengthText.textContent = 'Weak password';
        } else if (strength === 3) {
            strengthFill.classList.add('fair');
            strengthText.textContent = 'Fair password';
        } else if (strength === 4) {
            strengthFill.classList.add('good');
            strengthText.textContent = 'Good password';
        } else {
            strengthFill.classList.add('strong');
            strengthText.textContent = 'Strong password';
        }
    }
    
    togglePassword() {
        const password = document.getElementById('password');
        const toggle = document.getElementById('passwordToggle');
        
        if (password.type === 'password') {
            password.type = 'text';
            toggle.textContent = '🙈';
        } else {
            password.type = 'password';
            toggle.textContent = '👁️';
        }
    }
    
    showLoading(button) {
        const originalText = button.textContent;
        button.textContent = 'Please wait...';
        button.disabled = true;
        button.dataset.originalText = originalText;
    }
    
    hideLoading(button) {
        button.textContent = button.dataset.originalText;
        button.disabled = false;
    }
    
    showError(message) {
        // Create or update error message
        let errorDiv = document.querySelector('.auth-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'auth-error';
            errorDiv.style.cssText = `
                background: #fee;
                color: #c33;
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 16px;
                border: 1px solid #fcc;
                text-align: center;
                font-size: 14px;
            `;
            const form = document.querySelector('.auth-form');
            form.insertBefore(errorDiv, form.firstChild);
        }
        errorDiv.textContent = message;
        
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        }, 5000);
    }
}

// Initialize auth manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AuthManager();
});
