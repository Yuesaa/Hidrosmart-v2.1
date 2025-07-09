// About Us Page JavaScript
class AboutPage {
    constructor() {
        this.init();
    }

    init() {
        this.setupAnimations();
        this.setupCounters();
        this.setupTeamCards();
        this.setupValueCards();
        this.setupScrollEffects();
        this.startAchievementCounters();
    }

    // Setup scroll animations
    setupAnimations() {
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
            .story-left,
            .story-right,
            .value-card,
            .achievement-item,
            .team-card,
            .cta-content
        `);

        animatedElements.forEach((el, index) => {
            if (el.classList.contains('story-left') || el.classList.contains('cta-content')) {
                el.classList.add('fade-in-up');
            } else if (el.classList.contains('story-right')) {
                el.classList.add('scale-in');
            } else {
                el.classList.add('fade-in-up');
                // Stagger animation for cards
                el.style.transitionDelay = `${index * 0.1}s`;
            }
            
            observer.observe(el);
        });
    }

    // Setup achievement counters
    setupCounters() {
        const achievementNumbers = document.querySelectorAll('.achievement-number');
        
        achievementNumbers.forEach(number => {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.animateCounter(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            });
            
            observer.observe(number);
        });
    }

    // Animate counter numbers
    animateCounter(element) {
        const text = element.textContent;
        const hasPlus = text.includes('+');
        const hasStar = text.includes('★');
        const hasPercent = text.includes('%');
        const isTime = text.includes('/');
        
        let targetNumber;
        let suffix = '';
        
        if (isTime) {
            // Handle 24/7 case
            element.textContent = '0/0';
            setTimeout(() => {
                element.textContent = '24/7';
            }, 1000);
            return;
        } else if (hasPlus) {
            targetNumber = parseInt(text.replace(/\D/g, ''));
            suffix = '+';
        } else if (hasStar) {
            targetNumber = parseFloat(text.replace(/[^\d.]/g, ''));
            suffix = '★';
        } else if (hasPercent) {
            targetNumber = parseFloat(text.replace(/[^\d.]/g, ''));
            suffix = '%';
        } else {
            targetNumber = parseInt(text.replace(/\D/g, ''));
        }
        
        let currentNumber = 0;
        const increment = targetNumber / 50; // 50 steps
        const duration = 2000; // 2 seconds
        const stepTime = duration / 50;
        
        const timer = setInterval(() => {
            currentNumber += increment;
            
            if (currentNumber >= targetNumber) {
                currentNumber = targetNumber;
                clearInterval(timer);
            }
            
            if (hasStar) {
                element.textContent = currentNumber.toFixed(1) + suffix;
            } else if (hasPercent) {
                element.textContent = currentNumber.toFixed(1) + suffix;
            } else {
                element.textContent = Math.floor(currentNumber).toLocaleString('id-ID') + suffix;
            }
        }, stepTime);
    }

    // Setup team card interactions
    setupTeamCards() {
        const teamCards = document.querySelectorAll('.container-team');
        
        teamCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                this.highlightTeamCard(card);
            });
            
            card.addEventListener('mouseleave', () => {
                this.resetTeamCard(card);
            });
            
            // Add click handler for mobile
            card.addEventListener('click', () => {
                this.showTeamMemberDetails(card);
            });
        });
    }

    // Highlight team card
    highlightTeamCard(card) {
        const avatar = card.querySelector('.team-card');
        const name = card.querySelector('.team-name');
        
        if (avatar) {
            avatar.style.transform = 'scale(1.1)';
            avatar.style.boxShadow = '0 15px 40px rgba(59, 130, 246, 0.4)';
        }
        
        if (name) {
            name.style.color = '#3b82f6';
        }
    }

    // Reset team card
    resetTeamCard(card) {
        const avatar = card.querySelector('.team-card');
        const name = card.querySelector('.team-name');
        
        if (avatar) {
            avatar.style.transform = 'scale(1)';
            avatar.style.boxShadow = '0 0px 0px rgba(59, 130, 246, 0.3)';
        }
        
        if (name) {
            name.style.color = '#1e293b';
        }
    }

    // Setup value card interactions
    setupValueCards() {
        const valueCards = document.querySelectorAll('.value-card');
        
        valueCards.forEach(card => {
            const icon = card.querySelector('.value-icon');
            const iconBg = window.getComputedStyle(icon).backgroundColor;
            
            // Set CSS custom property for hover effects
            card.style.setProperty('--card-color', iconBg);
            
            card.addEventListener('mouseenter', () => {
                this.animateValueCard(card, true);
            });
            
            card.addEventListener('mouseleave', () => {
                this.animateValueCard(card, false);
            });
        });
    }

    // Animate value card
    animateValueCard(card, isHover) {
        const icon = card.querySelector('.value-icon');
        
        if (isHover) {
            icon.style.transform = 'scale(1.1) rotate(5deg)';
            card.style.borderColor = window.getComputedStyle(icon).backgroundColor;
        } else {
            icon.style.transform = 'scale(1) rotate(0deg)';
            card.style.borderColor = '#e2e8f0';
        }
    }

    // Setup scroll effects
    setupScrollEffects() {
        let ticking = false;
        
        const updateScrollEffects = () => {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            
            // Parallax effect for hero section
            const hero = document.querySelector('.about-hero');
            if (hero) {
                hero.style.transform = `translateY(${rate}px)`;
            }
            
            ticking = false;
        };
        
        const requestScrollUpdate = () => {
            if (!ticking) {
                requestAnimationFrame(updateScrollEffects);
                ticking = true;
            }
        };
        
        window.addEventListener('scroll', requestScrollUpdate);
    }

    // Start achievement counters periodically
    startAchievementCounters() {
        // Update achievements every 30 seconds
        setInterval(() => {
            this.updateAchievements();
        }, 30000);
    }

    // Update achievements with new data
    updateAchievements() {
        const achievements = document.querySelectorAll('.achievement-number');
        
        achievements.forEach((achievement, index) => {
            const currentText = achievement.textContent;
            let newValue;
            
            switch(index) {
                case 0: // Active users
                    const currentUsers = parseInt(currentText.replace(/\D/g, ''));
                    newValue = (currentUsers + Math.floor(Math.random() * 10)) + '+';
                    break;
                case 1: // Rating
                    const ratings = ['4.8', '4.9', '5.0'];
                    newValue = ratings[Math.floor(Math.random() * ratings.length)] + '★';
                    break;
                case 2: // Uptime
                    const uptimes = ['99.8%', '99.9%', '100%'];
                    newValue = uptimes[Math.floor(Math.random() * uptimes.length)];
                    break;
                case 3: // Support
                    newValue = '24/7';
                    break;
            }
            
            if (newValue && newValue !== currentText) {
                this.animateValueChange(achievement, newValue);
            }
        });
    }

    // Animate value change
    animateValueChange(element, newValue) {
        element.style.transform = 'scale(1.1)';
        element.style.color = '#10b981';
        
        setTimeout(() => {
            element.textContent = newValue;
            element.style.transform = 'scale(1)';
            element.style.color = '';
        }, 300);
    }

    // Show modal
    showModal(options) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>${options.title}</h3>
                    <button class="modal-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    ${options.subtitle ? `<p class="modal-subtitle">${options.subtitle}</p>` : ''}
                    <p>${options.content}</p>
                </div>
            </div>
        `;
        
        // Add styles
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;
        
        const modalContent = modal.querySelector('.modal-content');
        modalContent.style.cssText = `
            background: white;
            border-radius: 15px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        `;
        
        document.body.appendChild(modal);
        
        // Animate in
        setTimeout(() => {
            modal.style.opacity = '1';
            modalContent.style.transform = 'scale(1)';
        }, 10);
        
        // Close handlers
        const closeBtn = modal.querySelector('.modal-close');
        const closeModal = () => {
            modal.style.opacity = '0';
            modalContent.style.transform = 'scale(0.9)';
            setTimeout(() => {
                document.body.removeChild(modal);
            }, 300);
        };
        
        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        
        // ESC key handler
        const escHandler = (e) => {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    }

    // Utility functions
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
    new AboutPage();
});

// Handle page visibility changes
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        // Refresh animations when page becomes visible
        const aboutPage = new AboutPage();
    }
});