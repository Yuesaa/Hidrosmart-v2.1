// Auth Page JavaScript - Fixed Version
class AuthPage {
    constructor() {
        this.currentTab = 'login';
        this.init();
    }

    init() {
        this.setupFormValidation();
        this.setupPasswordToggle();
        this.setupFormSubmission();
        this.setupAnimations();
        this.setupKeyboardNavigation();
        this.clearLoadingStates();
    }

    // Clear any loading states on page load
    clearLoadingStates() {
        const buttons = document.querySelectorAll('.auth-btn');
        buttons.forEach(button => {
            button.disabled = false;
            button.classList.remove('loading');
            const span = button.querySelector('span');
            if (span) {
                if (span.textContent === 'Memproses...') {
                    span.textContent = button.name === 'login' ? 'Masuk' : 'Daftar';
                }
            }
        });
    }

    // Switch between login and register tabs
    switchTab(tab) {
        this.currentTab = tab;
        
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[onclick="switchTab('${tab}')"]`).classList.add('active');
        
        // Update forms
        document.querySelectorAll('.auth-form').forEach(form => {
            form.classList.remove('active');
        });
        document.getElementById(`${tab}-form`).classList.add('active');
        
        // Clear any existing errors
        this.clearAllErrors();
        
        // Focus first input
        setTimeout(() => {
            const firstInput = document.querySelector(`#${tab}-form input`);
            if (firstInput) {
                firstInput.focus();
            }
        }, 100);
    }

    // Setup form validation
    setupFormValidation() {
        const inputs = document.querySelectorAll('input');
        
        inputs.forEach(input => {
            // Real-time validation
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
            
            input.addEventListener('input', () => {
                this.clearFieldError(input);
                this.updateFieldState(input);
            });
            
            // Focus effects
            input.addEventListener('focus', () => {
                this.handleFieldFocus(input);
            });
        });
        
        // Password confirmation validation
        const confirmPassword = document.getElementById('register-confirm-password');
        const password = document.getElementById('register-password');
        
        if (confirmPassword && password) {
            confirmPassword.addEventListener('input', () => {
                this.validatePasswordConfirmation(password, confirmPassword);
            });
            
            password.addEventListener('input', () => {
                if (confirmPassword.value) {
                    this.validatePasswordConfirmation(password, confirmPassword);
                }
            });
        }
    }

    // Handle field focus
    handleFieldFocus(field) {
        const formGroup = field.closest('.form-group');
        formGroup.classList.add('focused');
        
        const icon = formGroup.querySelector('.input-icon');
        if (icon) {
            icon.style.color = '#3b82f6';
        }
    }

    // Update field state
    updateFieldState(field) {
        const formGroup = field.closest('.form-group');
        const icon = formGroup.querySelector('.input-icon');
        
        if (field.value.trim()) {
            field.style.background = 'white';
            field.style.color = '#1f2937';
        } else {
            field.style.background = '#f9fafb';
            field.style.color = '#374151';
            if (icon) {
                icon.style.color = '#9ca3af';
            }
        }
        
        formGroup.classList.remove('focused');
    }

    // Validate individual field
    validateField(field) {
        const value = field.value.trim();
        const fieldType = field.type;
        const fieldName = field.name;
        const formGroup = field.closest('.form-group');
        
        // Clear existing errors
        this.clearFieldError(field);
        
        let isValid = true;
        let errorMessage = '';
        
        // Required field validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = `${this.getFieldLabel(fieldName)} harus diisi`;
        }
        
        // Specific field validations
        if (value) {
            switch (fieldType) {
                case 'email':
                    if (!this.isValidEmail(value)) {
                        isValid = false;
                        errorMessage = 'Format email tidak valid';
                    }
                    break;
                    
                case 'password':
                    if (fieldName === 'password' && value.length < 6) {
                        isValid = false;
                        errorMessage = 'Password minimal 6 karakter';
                    }
                    break;
                    
                case 'text':
                    if (fieldName === 'name' && value.length < 2) {
                        isValid = false;
                        errorMessage = 'Nama minimal 2 karakter';
                    }
                    break;
            }
        }
        
        // Apply validation state
        if (!isValid) {
            this.showFieldError(formGroup, errorMessage);
        } else if (value) {
            this.showFieldSuccess(formGroup);
        }
        
        return isValid;
    }

    // Validate password confirmation
    validatePasswordConfirmation(passwordField, confirmField) {
        const formGroup = confirmField.closest('.form-group');
        this.clearFieldError(confirmField);
        
        if (confirmField.value && passwordField.value !== confirmField.value) {
            this.showFieldError(formGroup, 'Konfirmasi password tidak cocok');
            return false;
        } else if (confirmField.value) {
            this.showFieldSuccess(formGroup);
            return true;
        }
        
        return true;
    }

    // Clear field error
    clearFieldError(field) {
        const formGroup = field.closest('.form-group');
        formGroup.classList.remove('error', 'success');
        
        const existingError = formGroup.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
    }

    // Show field error
    showFieldError(formGroup, message) {
        formGroup.classList.add('error');
        formGroup.classList.remove('success');
        
        const errorElement = document.createElement('div');
        errorElement.className = 'error-message';
        errorElement.textContent = message;
        formGroup.appendChild(errorElement);
    }

    // Show field success
    showFieldSuccess(formGroup) {
        formGroup.classList.add('success');
        formGroup.classList.remove('error');
    }

    // Clear all errors
    clearAllErrors() {
        document.querySelectorAll('.form-group').forEach(group => {
            group.classList.remove('error', 'success', 'focused');
            const errorMessage = group.querySelector('.error-message');
            if (errorMessage) {
                errorMessage.remove();
            }
        });
    }

    // Get field label
    getFieldLabel(fieldName) {
        const labels = {
            'name': 'Nama lengkap',
            'email': 'Email',
            'password': 'Password',
            'confirm_password': 'Konfirmasi password'
        };
        return labels[fieldName] || fieldName;
    }

    // Email validation
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Setup password toggle
    setupPasswordToggle() {
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                const input = toggle.parentElement.querySelector('input');
                const icon = toggle.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                    toggle.setAttribute('aria-label', 'Sembunyikan password');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                    toggle.setAttribute('aria-label', 'Tampilkan password');
                }
                
                // Add click animation
                toggle.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    toggle.style.transform = 'scale(1)';
                }, 150);
            });
        });
    }

    // Setup form submission
    setupFormSubmission() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                const submitBtn = form.querySelector('.auth-btn');
                const isLoginForm = form.closest('#login-form');
                
                // Validate form before submission
                let isValid = true;
                const inputs = form.querySelectorAll('input[required]');
                
                inputs.forEach(input => {
                    if (!this.validateField(input)) {
                        isValid = false;
                    }
                });
                
                // Additional validation for register form
                if (!isLoginForm) {
                    const password = form.querySelector('input[name="password"]');
                    const confirmPassword = form.querySelector('input[name="confirm_password"]');
                    
                    if (!this.validatePasswordConfirmation(password, confirmPassword)) {
                        isValid = false;
                    }
                }
                
                if (!isValid) {
                    e.preventDefault();
                    this.showNotification('Mohon perbaiki kesalahan pada form', 'error');
                    return;
                }
                
                // Show loading state
                this.setFormLoading(submitBtn, true);
                
                // Form will submit naturally to PHP
            });
        });
    }

    // Set form loading state
    setFormLoading(button, loading) {
        if (loading) {
            button.classList.add('loading');
            button.disabled = true;
            const span = button.querySelector('span');
            if (span) {
                span.textContent = 'Memproses...';
            }
        } else {
            button.classList.remove('loading');
            button.disabled = false;
            const span = button.querySelector('span');
            if (span) {
                const isLogin = button.name === 'login';
                span.textContent = isLogin ? 'Masuk' : 'Daftar';
            }
        }
    }

    // Setup animations
    setupAnimations() {
        // Animate floating elements
        const floatingElements = document.querySelectorAll('.floating-element');
        floatingElements.forEach((element, index) => {
            element.style.animationDelay = `${index * 0.5}s`;
        });
        
        // Animate auth card on load
        const authCard = document.querySelector('.auth-card');
        if (authCard) {
            authCard.style.opacity = '0';
            authCard.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                authCard.style.transition = 'all 0.6s ease';
                authCard.style.opacity = '1';
                authCard.style.transform = 'translateY(0)';
            }, 200);
        }
        
        // Animate brand header
        const brandHeader = document.querySelector('.brand-header');
        if (brandHeader) {
            brandHeader.style.opacity = '0';
            brandHeader.style.transform = 'translateY(-20px)';
            
            setTimeout(() => {
                brandHeader.style.transition = 'all 0.6s ease';
                brandHeader.style.opacity = '1';
                brandHeader.style.transform = 'translateY(0)';
            }, 100);
        }
    }

    // Setup keyboard navigation
    setupKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            // Tab switching with Ctrl+Tab
            if (e.ctrlKey && e.key === 'Tab') {
                e.preventDefault();
                const newTab = this.currentTab === 'login' ? 'register' : 'login';
                this.switchTab(newTab);
            }
            
            // Enter key handling
            if (e.key === 'Enter') {
                const activeElement = document.activeElement;
                if (activeElement.tagName === 'INPUT') {
                    const form = activeElement.closest('form');
                    const inputs = Array.from(form.querySelectorAll('input'));
                    const currentIndex = inputs.indexOf(activeElement);
                    
                    // Move to next input or submit
                    if (currentIndex < inputs.length - 1) {
                        e.preventDefault();
                        inputs[currentIndex + 1].focus();
                    }
                }
            }
        });
    }

    // Show notification
    showNotification(message, type = 'info') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notification => {
            notification.remove();
        });
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        const iconMap = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'info': 'fa-info-circle',
            'warning': 'fa-exclamation-triangle'
        };
        
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${iconMap[type]}"></i>
                <span>${message}</span>
                <button class="notification-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${this.getNotificationColor(type)};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 10000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            max-width: 400px;
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Close button handler
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            this.closeNotification(notification);
        });
        
        // Auto close after 5 seconds
        setTimeout(() => {
            this.closeNotification(notification);
        }, 5000);
    }

    // Get notification color
    getNotificationColor(type) {
        const colors = {
            'success': '#10b981',
            'error': '#ef4444',
            'info': '#3b82f6',
            'warning': '#f59e0b'
        };
        return colors[type] || colors.info;
    }

    // Close notification
    closeNotification(notification) {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
}

// Global function for tab switching (called from HTML)
function switchTab(tab) {
    if (window.authPage) {
        window.authPage.switchTab(tab);
    }
}

// Global function for password toggle (called from HTML)
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const toggle = input.parentElement.querySelector('.password-toggle');
    const icon = toggle.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.authPage = new AuthPage();
});