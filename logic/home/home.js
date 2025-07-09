// HidroSmart Website JavaScript
class HidroSmartWebsite {
    constructor() {
        this.init();
    }

    init() {
        this.setupMobileMenu();
        this.setupScrollAnimations();
        this.setupProgressBar();
        this.setupSmoothScrolling();
        this.setupFormHandlers();
        this.startRealTimeUpdates();
    }

    // Mobile Menu Toggle
    setupMobileMenu() {
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        const navMenu = document.querySelector('.nav-menu');
        
        if (mobileToggle && navMenu) {
            mobileToggle.addEventListener('click', () => {
                navMenu.classList.toggle('active');
                
                // Animate hamburger icon
                const icon = mobileToggle.querySelector('i');
                if (navMenu.classList.contains('active')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });

            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!mobileToggle.contains(e.target) && !navMenu.contains(e.target)) {
                    navMenu.classList.remove('active');
                    const icon = mobileToggle.querySelector('i');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
        }
    }

    // Scroll Animations
    setupScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        // Add animation classes to elements
        const animatedElements = document.querySelectorAll(`
            .hero-left,
            .hero-right,
            .about-left,
            .about-right,
            .user-card,
            .feature-card,
            .stat-item
        `);

        animatedElements.forEach((el, index) => {
            // Add appropriate animation class
            if (el.classList.contains('hero-left') || el.classList.contains('about-right')) {
                el.classList.add('slide-in-left');
            } else if (el.classList.contains('hero-right') || el.classList.contains('about-left')) {
                el.classList.add('slide-in-right');
            } else {
                el.classList.add('fade-in');
                // Stagger animation for cards
                el.style.transitionDelay = `${index * 0.1}s`;
            }
            
            observer.observe(el);
        });
    }

    // Progress Bar Animation
    setupProgressBar() {
        const progressBar = document.querySelector('.progress-fill');
        if (progressBar) {
            // Animate progress bar on load
            setTimeout(() => {
                progressBar.style.width = '75%';
            }, 1000);

            // Update progress periodically
            setInterval(() => {
                const currentWidth = parseInt(progressBar.style.width);
                const newWidth = Math.max(50, Math.min(100, currentWidth + (Math.random() - 0.5) * 10));
                progressBar.style.width = newWidth + '%';
                
                // Update text
                const levelText = document.querySelector('.level-text');
                if (levelText) {
                    const volume = Math.round((newWidth / 100) * 1000);
                    levelText.textContent = `${volume}ml / 1000ml`;
                }
            }, 5000);
        }
    }

    // Smooth Scrolling
    setupSmoothScrolling() {
        const links = document.querySelectorAll('a[href^="#"]');
        
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                const targetId = link.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    const headerHeight = document.querySelector('.header').offsetHeight;
                    const targetPosition = targetElement.offsetTop - headerHeight - 20;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    // Form Handlers
    setupFormHandlers() {
        // Contact form handler (if exists)
        const contactForms = document.querySelectorAll('form[action*="contact"]');
        
        contactForms.forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleContactForm(form);
            });
        });

        // Newsletter signup (if exists)
        const newsletterForms = document.querySelectorAll('form[action*="newsletter"]');
        
        newsletterForms.forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleNewsletterSignup(form);
            });
        });
    }

    // Handle Contact Form
    handleContactForm(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        // Show loading state
        submitBtn.textContent = 'Mengirim...';
        submitBtn.disabled = true;
        
        // Simulate form submission
        setTimeout(() => {
            this.showNotification('Pesan berhasil dikirim! Kami akan segera menghubungi Anda.', 'success');
            form.reset();
            
            // Reset button
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }, 2000);
    }

    // Handle Newsletter Signup
    handleNewsletterSignup(form) {
        const email = form.querySelector('input[type="email"]').value;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        // Show loading state
        submitBtn.textContent = 'Mendaftar...';
        submitBtn.disabled = true;
        
        // Simulate signup
        setTimeout(() => {
            this.showNotification(`Terima kasih! ${email} telah terdaftar untuk newsletter kami.`, 'success');
            form.reset();
            
            // Reset button
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }, 1500);
    }

    // Show Notification
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                <span>${message}</span>
                <button class="notification-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : '#3b82f6'};
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

    // Close Notification
    closeNotification(notification) {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }

    // Real-time Updates
    startRealTimeUpdates() {
        // Update stats periodically
        this.updateStats();
        setInterval(() => {
            this.updateStats();
        }, 30000); // Every 30 seconds

        // Update device status
        this.updateDeviceStatus();
        setInterval(() => {
            this.updateDeviceStatus();
        }, 10000); // Every 10 seconds
    }

    // Update Statistics
    updateStats() {
        const statNumbers = document.querySelectorAll('.stat-number');
        
        statNumbers.forEach((stat, index) => {
            const currentText = stat.textContent;
            let newValue;
            
            switch(index) {
                case 0: // Active users
                    const currentUsers = parseInt(currentText.replace(/\D/g, ''));
                    newValue = (currentUsers + Math.floor(Math.random() * 5)) + '+';
                    break;
                case 1: // App rating
                    const ratings = ['4.7', '4.8', '4.9'];
                    newValue = ratings[Math.floor(Math.random() * ratings.length)] + '★';
                    break;
                case 2: // Support
                    newValue = '24/7';
                    break;
            }
            
            if (newValue && newValue !== currentText) {
                this.animateNumber(stat, newValue);
            }
        });
    }

    // Update Device Status
    updateDeviceStatus() {
        const statusText = document.querySelector('.status-text');
        const statuses = ['Connected', 'Syncing', 'Online'];
        const colors = ['#10b981', '#f59e0b', '#3b82f6'];
        
        if (statusText) {
            const randomStatus = statuses[Math.floor(Math.random() * statuses.length)];
            const randomColor = colors[statuses.indexOf(randomStatus)];
            
            statusText.textContent = `Status: ${randomStatus}`;
            statusText.style.color = randomColor;
        }
    }

    // Animate Number Change
    animateNumber(element, newValue) {
        element.style.transform = 'scale(1.1)';
        element.style.color = '#60a5fa';
        
        setTimeout(() => {
            element.textContent = newValue;
            element.style.transform = 'scale(1)';
            element.style.color = '';
        }, 200);
    }

    // Utility Functions
    static formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }

    static formatDate(date) {
        return new Intl.DateTimeFormat('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    }

    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new HidroSmartWebsite();
});

// Handle page visibility changes
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        // Refresh data when page becomes visible
        console.log('Page is now visible, refreshing data...');
    }
});

// Handle online/offline status
window.addEventListener('online', () => {
    console.log('Connection restored');
});

window.addEventListener('offline', () => {
    console.log('Connection lost');
});