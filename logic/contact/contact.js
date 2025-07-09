
// Enhanced Contact Page JavaScript with Login Check and Improved Animations
class ContactPageEnhanced {
    constructor() {
        this.form = document.querySelector(".contact-form")
        this.submitBtn = document.querySelector(".btn-submit")
        this.isSubmitting = false
        this.isLoggedIn = !document.querySelector(".form-subtitle").textContent.includes("harus login")
        this.init()
    }

    init() {
        this.setupFormValidation()
        this.setupEnhancedAnimations()
        this.setupFormSubmission()
        this.setupInteractiveElements()
        this.setupScrollEffects()
        this.setupParallaxEffects()
        this.initializeCounters()
    }

    // Enhanced form validation with better UX
    setupFormValidation() {
        const inputs = this.form.querySelectorAll("input, textarea")

        inputs.forEach((input) => {
            // Real-time validation with debouncing
            let validationTimeout

            input.addEventListener("input", () => {
                clearTimeout(validationTimeout)
                this.clearFieldError(input)
                this.updateFieldAppearance(input)

                // Debounced validation
                validationTimeout = setTimeout(() => {
                    if (input.value.trim()) {
                        this.validateField(input)
                    }
                }, 500)
            })

            input.addEventListener("blur", () => {
                this.validateField(input)
            })

            // Enhanced focus effects
            input.addEventListener("focus", () => {
                this.handleFieldFocus(input)
            })
        })
    }

    // Enhanced field focus with animation
    handleFieldFocus(field) {
        const formGroup = field.closest(".form-group")
        formGroup.classList.add("focused")

        // Add ripple effect
        this.createRippleEffect(field)
    }

    // Create ripple effect on focus
    createRippleEffect(element) {
        const ripple = document.createElement("div")
        ripple.className = "input-ripple"

        const rect = element.getBoundingClientRect()
        const size = Math.max(rect.width, rect.height)

        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            background: rgba(25, 118, 210, 0.1);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
            pointer-events: none;
            z-index: 1;
        `

        element.style.position = "relative"
        element.appendChild(ripple)

        setTimeout(() => {
            if (ripple.parentNode) {
                ripple.parentNode.removeChild(ripple)
            }
        }, 600)
    }

    // Enhanced field validation
    validateField(field) {
        const value = field.value.trim()
        const fieldName = field.name
        const formGroup = field.closest(".form-group")

        this.clearFieldError(field)

        let isValid = true
        let errorMessage = ""

        // Required field validation
        if (field.hasAttribute("required") && !value) {
            isValid = false
            errorMessage = `${this.getFieldLabel(fieldName)} harus diisi`
        }

        // Specific validations
        if (value) {
            switch (fieldName) {
                case "subject":
                    if (value.length < 5) {
                        isValid = false
                        errorMessage = "Subjek minimal 5 karakter"
                    } else if (value.length > 255) {
                        isValid = false
                        errorMessage = "Subjek maksimal 255 karakter"
                    }
                    break

                case "message":
                    if (value.length < 10) {
                        isValid = false
                        errorMessage = "Pesan minimal 10 karakter"
                    } else if (value.length > 2000) {
                        isValid = false
                        errorMessage = "Pesan maksimal 2000 karakter"
                    }
                    break
            }
        }

        // Apply validation state with animation
        if (!isValid) {
            this.showFieldError(formGroup, errorMessage)
        } else if (value) {
            this.showFieldSuccess(formGroup)
        }

        return isValid
    }

    // Enhanced form submission with login check
    setupFormSubmission() {
        this.form.addEventListener("submit", (e) => {
            e.preventDefault()
            this.handleFormSubmission()
        })
    }

    // Handle form submission with enhanced UX
    async handleFormSubmission() {
        if (this.isSubmitting) return

        // Validate all fields
        const inputs = this.form.querySelectorAll("input, textarea")
        let isFormValid = true

        inputs.forEach((input) => {
            if (!this.validateField(input)) {
                isFormValid = false
            }
        })

        if (!isFormValid) {
            this.showEnhancedNotification("Mohon perbaiki kesalahan pada form", "error")
            this.shakeForm()
            return
        }

        // Check if user is logged in
        if (!this.isLoggedIn) {
            this.showEnhancedNotification("Anda harus login terlebih dahulu untuk mengirim pesan.", "warning")
            this.shakeForm()
            return
        }

        // Set submitting state
        this.isSubmitting = true
        this.setFormLoading(true)

        try {
            // Prepare form data
            const formData = new FormData(this.form)
            formData.append("submit_contact", "1") // Tambahkan ini untuk konsistensi

            // Submit to controller dengan AJAX header
            const response = await fetch("../logic/contact/contact-controller.php", {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            })

            const result = await response.json()

            if (result.success) {
                this.showEnhancedNotification(result.message, "success")
                this.resetFormWithAnimation()
            } else {
                if (result.type === "login_required") {
                    this.showLoginRequiredModal(result.message)
                } else {
                    this.showEnhancedNotification(result.message, "error")
                    if (result.errors && result.errors.length > 0) {
                        this.showValidationErrors(result.errors)
                    }
                }
            }
        } catch (error) {
            console.error("Form submission error:", error)
            this.showEnhancedNotification("Terjadi kesalahan jaringan. Silakan coba lagi.", "error")
        } finally {
            this.isSubmitting = false
            this.setFormLoading(false)
        }
    }

    // Show login required modal
    showLoginRequiredModal(message) {
        const modal = document.createElement("div")
        modal.className = "login-required-modal"
        modal.innerHTML = `
            <div class="modal-overlay">
                <div class="modal-content">
                    <div class="modal-header">
                        <i class="fas fa-user-lock"></i>
                        <h3>Login Diperlukan</h3>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-actions">
                        <button class="btn-secondary modal-close">Batal</button>
                        <a href="login-register.php" class="btn-primary">Login Sekarang</a>
                    </div>
                </div>
            </div>
        `

        // Add styles
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        `

        document.body.appendChild(modal)

        // Animate in
        setTimeout(() => {
            modal.querySelector(".modal-overlay").style.background = "rgba(0,0,0,0.5)"
            modal.querySelector(".modal-content").style.transform = "scale(1)"
        }, 10)

        // Close handlers
        modal.querySelector(".modal-close").addEventListener("click", () => {
            this.closeModal(modal)
        })

        modal.querySelector(".modal-overlay").addEventListener("click", (e) => {
            if (e.target === modal.querySelector(".modal-overlay")) {
                this.closeModal(modal)
            }
        })
    }

    // Close modal with animation
    closeModal(modal) {
        modal.querySelector(".modal-overlay").style.background = "rgba(0,0,0,0)"
        modal.querySelector(".modal-content").style.transform = "scale(0.8)"

        setTimeout(() => {
            if (modal.parentNode) {
                modal.parentNode.removeChild(modal)
            }
        }, 300)
    }

    // Enhanced animations setup
    setupEnhancedAnimations() {
        // Add CSS animations
        this.addAnimationStyles()

        // Setup intersection observer for scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px",
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("animate-in")

                    // Stagger animations for child elements
                    const children = entry.target.querySelectorAll(".animate-child")
                    children.forEach((child, index) => {
                        setTimeout(() => {
                            child.classList.add("animate-in")
                        }, index * 100)
                    })
                }
            })
        }, observerOptions)

        // Observe elements
        const animatedElements = document.querySelectorAll(`
            .contact-info-card,
            .form-section,
            .support-section,
            .support-item,
            .faq-item
        `)

        animatedElements.forEach((el) => {
            el.classList.add("animate-element")
            observer.observe(el)
        })
    }

    // Add animation styles
    addAnimationStyles() {
        const style = document.createElement("style")
        style.textContent = `
            @keyframes ripple-animation {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
            
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            
            @keyframes slideInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            @keyframes fadeInScale {
                from {
                    opacity: 0;
                    transform: scale(0.8);
                }
                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            .animate-element {
                opacity: 0;
                transform: translateY(30px);
                transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .animate-element.animate-in {
                opacity: 1;
                transform: translateY(0);
            }
            
            .form-shake {
                animation: shake 0.5s ease-in-out;
            }
            
            .modal-overlay {
                background: rgba(0,0,0,0);
                transition: background 0.3s ease;
            }
            
            .modal-content {
                background: white;
                padding: 2rem;
                border-radius: 15px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                transform: scale(0.8);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                max-width: 400px;
                text-align: center;
            }
            
            .modal-header i {
                font-size: 3rem;
                color: #1976d2;
                margin-bottom: 1rem;
            }
            
            .modal-actions {
                display: flex;
                gap: 1rem;
                margin-top: 2rem;
                justify-content: center;
            }
            
            .btn-primary, .btn-secondary {
                padding: 0.75rem 1.5rem;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 500;
                transition: all 0.3s ease;
                border: none;
                cursor: pointer;
            }
            
            .btn-primary {
                background: #1976d2;
                color: white;
            }
            
            .btn-primary:hover {
                background: #1565c0;
                transform: translateY(-2px);
            }
            
            .btn-secondary {
                background: #f5f5f5;
                color: #666;
            }
            
            .btn-secondary:hover {
                background: #e0e0e0;
            }
        `

        document.head.appendChild(style)
    }

    // Shake form animation
    shakeForm() {
        this.form.classList.add("form-shake")
        setTimeout(() => {
            this.form.classList.remove("form-shake")
        }, 500)
    }

    // Reset form with animation
    resetFormWithAnimation() {
        // Fade out form content
        this.form.style.opacity = "0.5"

        setTimeout(() => {
            this.form.reset()
            this.clearAllFieldStates()
            this.resetFieldAppearances()

            // Fade back in
            this.form.style.opacity = "1"
        }, 300)
    }

    // Enhanced notification system
    showEnhancedNotification(message, type = "info") {
        // Remove existing notifications
        document.querySelectorAll(".enhanced-notification").forEach((n) => n.remove())

        const notification = document.createElement("div")
        notification.className = `enhanced-notification notification-${type}`

        const iconMap = {
            success: "fa-check-circle",
            error: "fa-exclamation-circle",
            info: "fa-info-circle",
            warning: "fa-exclamation-triangle",
        }

        const colorMap = {
            success: "#10B981",
            error: "#EF4444",
            info: "#3B82F6",
            warning: "#F59E0B",
        }

        notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-icon">
                <i class="fas ${iconMap[type]}"></i>
            </div>
            <div class="notification-text">
                <span>${message}</span>
            </div>
            <button class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="notification-progress"></div>
    `

        // Enhanced styling
        notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        transform: translateX(400px);
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        max-width: 400px;
        overflow: hidden;
        border-left: 4px solid ${colorMap[type]};
        font-family: 'Inter', sans-serif;
    `

        document.body.appendChild(notification)

        // Add internal styles
        const style = document.createElement("style")
        style.innerHTML = `
        .notification-content {
            display: flex;
            align-items: center;
            padding: 16px;
            position: relative;
            z-index: 2;
        }
        
        .notification-icon {
            margin-right: 12px;
            font-size: 20px;
            color: ${colorMap[type]};
        }
        
        .notification-text {
            flex: 1;
            color: #1F2937;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .notification-close {
            background: none;
            border: none;
            cursor: pointer;
            color: #9CA3AF;
            font-size: 14px;
            margin-left: 10px;
            transition: color 0.2s;
            padding: 4px;
            border-radius: 4px;
        }
        
        .notification-close:hover {
            color: #6B7280;
            background: rgba(0,0,0,0.05);
        }
        
        .notification-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: ${colorMap[type]};
            width: 100%;
            transform-origin: left;
            transition: transform 0.05s linear;
        }
    `
        notification.appendChild(style)

        // Animate in
        setTimeout(() => {
            notification.style.transform = "translateX(0)"
        }, 100)

        // Progress bar animation
        const progressBar = notification.querySelector(".notification-progress")
        let progress = 0
        const interval = setInterval(() => {
            progress += 1
            progressBar.style.transform = `scaleX(${progress / 100})`

            if (progress >= 100) {
                clearInterval(interval)
            }
        }, 50)

        // Close button
        notification.querySelector(".notification-close").addEventListener("click", () => {
            notification.style.transform = "translateX(400px)"
            setTimeout(() => {
                notification.remove()
            }, 400)
        })

        // Auto close
        setTimeout(() => {
            notification.style.transform = "translateX(400px)"
            setTimeout(() => {
                notification.remove()
            }, 400)
        }, 5000)
    }

    // Setup interactive elements
    setupInteractiveElements() {
        // Contact info cards hover effects
        const infoCards = document.querySelectorAll(".contact-info-card")
        infoCards.forEach((card) => {
            card.addEventListener("mouseenter", () => {
                card.style.transform = "translateY(-10px) scale(1.02)"
            })

            card.addEventListener("mouseleave", () => {
                card.style.transform = "translateY(0) scale(1)"
            })
        })

        // Support items interactive effects
        const supportItems = document.querySelectorAll(".support-item")
        supportItems.forEach((item) => {
            item.addEventListener("click", () => {
                item.classList.add("clicked")
                setTimeout(() => {
                    item.classList.remove("clicked")
                }, 200)
            })
        })
    }

    // Setup scroll effects
    setupScrollEffects() {
        let ticking = false

        const updateScrollEffects = () => {
            const scrolled = window.pageYOffset

            // Parallax for hero section
            const hero = document.querySelector(".contact-hero")
            if (hero) {
                hero.style.transform = `translateY(${scrolled * 0.3}px)`
            }

            // Update header background
            const header = document.querySelector(".header")
            if (header) {
                if (scrolled > 100) {
                    header.classList.add("scrolled")
                } else {
                    header.classList.remove("scrolled")
                }
            }

            ticking = false
        }

        const requestScrollUpdate = () => {
            if (!ticking) {
                requestAnimationFrame(updateScrollEffects)
                ticking = true
            }
        }

        window.addEventListener("scroll", requestScrollUpdate)
    }

    // Setup parallax effects
    setupParallaxEffects() {
        const parallaxElements = document.querySelectorAll(".contact-info-card")

        window.addEventListener("mousemove", (e) => {
            const mouseX = e.clientX / window.innerWidth
            const mouseY = e.clientY / window.innerHeight

            parallaxElements.forEach((element, index) => {
                const speed = (index + 1) * 0.5
                const x = (mouseX - 0.5) * speed
                const y = (mouseY - 0.5) * speed

                element.style.transform = `translate(${x}px, ${y}px)`
            })
        })
    }

    // Initialize counters (if any)
    initializeCounters() {
        const counters = document.querySelectorAll("[data-counter]")

        counters.forEach((counter) => {
            const target = Number.parseInt(counter.getAttribute("data-counter"))
            const duration = 2000
            const step = target / (duration / 16)
            let current = 0

            const updateCounter = () => {
                current += step
                if (current < target) {
                    counter.textContent = Math.floor(current)
                    requestAnimationFrame(updateCounter)
                } else {
                    counter.textContent = target
                }
            }

            // Start counter when element is visible
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        updateCounter()
                        observer.unobserve(entry.target)
                    }
                })
            })

            observer.observe(counter)
        })
    }

    // Setup interactive elements such as FAQ accordion
    setupInteractiveElements() {
        const faqItems = document.querySelectorAll(".faq-item");
        if (faqItems.length === 0) return;

        faqItems.forEach((item) => {
            const wrapper = item.querySelector(".faq-question-wrapper");
            if (!wrapper) return;

            wrapper.addEventListener("click", () => {
                const isActive = item.classList.contains("active");
                // Close all FAQ items first
                faqItems.forEach((i) => i.classList.remove("active"));
                // Toggle the clicked item
                if (!isActive) {
                    item.classList.add("active");
                }
            });
        });
    }

    // Utility methods
    clearFieldError(field) {
        const formGroup = field.closest(".form-group")
        formGroup.classList.remove("error", "success")

        const existingError = formGroup.querySelector(".error-message")
        if (existingError) {
            existingError.remove()
        }
    }

    showFieldError(formGroup, message) {
        formGroup.classList.add("error")
        formGroup.classList.remove("success")

        const errorElement = document.createElement("div")
        errorElement.className = "error-message"
        errorElement.textContent = message
        formGroup.appendChild(errorElement)
    }

    showFieldSuccess(formGroup) {
        formGroup.classList.add("success")
        formGroup.classList.remove("error")
    }

    getFieldLabel(fieldName) {
        const labels = {
            subject: "Subjek",
            message: "Pesan",
        }
        return labels[fieldName] || fieldName
    }

    updateFieldAppearance(field) {
        const formGroup = field.closest(".form-group")

        if (field.value.trim()) {
            field.style.background = "white"
            field.style.color = "#1f2937"
        } else {
            field.style.background = "#f9fafb"
            field.style.color = "#6b7280"
        }

        formGroup.classList.remove("focused")
    }

    setFormLoading(loading) {
        if (loading) {
            this.form.classList.add("form-loading")
            this.submitBtn.disabled = true
            this.submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...'
        } else {
            this.form.classList.remove("form-loading")
            this.submitBtn.disabled = false
            this.submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Kirim Pesan'
        }
    }

    clearAllFieldStates() {
        const formGroups = this.form.querySelectorAll(".form-group")
        formGroups.forEach((group) => {
            group.classList.remove("error", "success", "focused")
            const errorMessage = group.querySelector(".error-message")
            if (errorMessage) {
                errorMessage.remove()
            }
        })
    }

    resetFieldAppearances() {
        const inputs = this.form.querySelectorAll("input, textarea")
        inputs.forEach((input) => {
            input.style.background = "#f9fafb"
            input.style.color = "#6b7280"
        })
    }

    showValidationErrors(errors) {
        errors.forEach((error, index) => {
            setTimeout(() => {
                this.showEnhancedNotification(error, "error")
            }, index * 1000)
        })
    }
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
    new ContactPageEnhanced()
})

// Handle page visibility changes
document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
        console.log("Contact page is now visible")
    }
})

// Add smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
        e.preventDefault()
        const target = document.querySelector(this.getAttribute("href"))
        if (target) {
            target.scrollIntoView({
                behavior: "smooth",
                block: "start",
            })
        }
    })
})
