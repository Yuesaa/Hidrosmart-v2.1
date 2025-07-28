// Enhanced Dashboard JavaScript with improved animations and features
class DashboardManager {
    constructor() {
        this.currentTab = "status-pesanan"
        this.init()
    }

    init() {
        this.setupTabNavigation()
        this.setupModals()
        this.setupForms()
        this.setupRatingSystem()
        this.setupAvatarUpload()
        this.setupSimpleAnimations()
        this.setupPhoneValidation()
        this.loadInitialData()
    }

    // Tab Navigation
    setupTabNavigation() {
        const tabButtons = document.querySelectorAll(".tab-btn")

        tabButtons.forEach((button) => {
            button.addEventListener("click", () => {
                const tabId = button.getAttribute("data-tab")
                this.switchTab(tabId)
            })
        })
    }

    switchTab(tabId) {
        // Simple button state update without excessive scaling
        document.querySelectorAll(".tab-btn").forEach((btn) => {
            btn.classList.remove("active")
        })

        const activeBtn = document.querySelector(`[data-tab="${tabId}"]`)
        activeBtn.classList.add("active")

        // Simple content transition
        document.querySelectorAll(".tab-content").forEach((content) => {
            content.classList.remove("active")
        })

        const newContent = document.getElementById(tabId)
        newContent.classList.add("active")

        this.currentTab = tabId
        this.loadTabData(tabId)
    }

    loadTabData(tabId) {
        switch (tabId) {
            case "status-pesanan":
                this.refreshOrderData()
                break
            case "ulasan":
                this.refreshReviewData()
                break
            case "garansi":
                this.refreshWarrantyData()
                break
            case "profil":
                this.loadProfileData()
                break
            case "suggestion":
                this.loadSuggestionData()
                break
        }
    }

    // Enhanced Phone Validation
    setupPhoneValidation() {
        const phoneInput = document.getElementById("profile_phone")
        if (phoneInput) {
            if (!phoneInput.value) {
                phoneInput.value = "+62"
            }

            phoneInput.addEventListener("input", (e) => {
                let value = e.target.value

                if (!value.startsWith("+62")) {
                    value = "+62" + value.replace(/^\+?62?/, "")
                }

                const prefix = "+62"
                const digits = value.slice(3).replace(/\D/g, "")
                const limitedDigits = digits.slice(0, 13)

                e.target.value = prefix + limitedDigits

                const helpText = e.target.parentElement.querySelector(".form-help")
                if (limitedDigits.length < 10) {
                    helpText.style.color = "#ef4444"
                    helpText.textContent = "Minimal 10 digit setelah +62"
                } else if (limitedDigits.length > 13) {
                    helpText.style.color = "#ef4444"
                    helpText.textContent = "Maksimal 13 digit setelah +62"
                } else {
                    helpText.style.color = "var(--color-primary)"
                    helpText.textContent = "Format: +62 diikuti 10-13 digit angka"
                }
            })

            phoneInput.addEventListener("focus", (e) => {
                if (e.target.value === "") {
                    e.target.value = "+62"
                }
            })
        }
    }

    // Modal Management
    setupModals() {
        window.addEventListener("click", (e) => {
            if (e.target.classList.contains("modal")) {
                this.closeModal(e.target.id)
            }
        })

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                const openModal = document.querySelector('.modal.active')
                if (openModal) {
                    this.closeModal(openModal.id)
                }
            }
        })
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId)
        if (modal) {
            // Tambahkan class active agar CSS visibility:visible
            modal.classList.add('active')
            document.body.style.overflow = 'hidden'

            // Pastikan opacity mulai dari 0 lalu fade-in
            modal.style.opacity = '0'
            setTimeout(() => {
                modal.style.transition = 'opacity 0.2s ease'
                modal.style.opacity = '1'
            }, 10)

            const firstInput = modal.querySelector('input, textarea, button')
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 200)
            }
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId)
        if (modal) {
            modal.style.opacity = '0'

            setTimeout(() => {
                modal.classList.remove('active')
                document.body.style.overflow = 'auto'

                const form = modal.querySelector('form')
                if (form) {
                    form.reset()
                    this.resetRating()
                }
            }, 200)
        }
    }

    // Enhanced Rating System
    setupRatingSystem() {
        const ratingContainer = document.getElementById("reviewRating")
        if (ratingContainer) {
            const stars = ratingContainer.querySelectorAll(".star")
            const ratingText = document.querySelector(".rating-text")

            stars.forEach((star, index) => {
                star.addEventListener("click", () => {
                    this.setRating(index + 1)
                    this.updateRatingText(index + 1)
                })

                star.addEventListener("mouseenter", () => {
                    this.highlightStars(index + 1)
                    this.updateRatingText(index + 1)
                })
            })

            ratingContainer.addEventListener("mouseleave", () => {
                const currentRating = document.getElementById("rating").value
                this.highlightStars(currentRating || 0)
                this.updateRatingText(currentRating || 0)
            })
        }
    }

    setRating(rating) {
        document.getElementById("rating").value = rating
        this.highlightStars(rating)
        this.updateRatingText(rating)
    }

    highlightStars(rating) {
        const stars = document.querySelectorAll("#reviewRating .star")
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.add("active")
                star.style.color = "#fbbf24"
            } else {
                star.classList.remove("active")
                star.style.color = "#d1d5db"
            }
        })
    }

    updateRatingText(rating) {
        const ratingText = document.querySelector(".rating-text")
        if (ratingText) {
            const texts = {
                0: "Pilih rating Anda",
                1: "Sangat Tidak Puas",
                2: "Tidak Puas",
                3: "Cukup Puas",
                4: "Puas",
                5: "Sangat Puas",
            }
            ratingText.textContent = texts[rating] || texts[0]
        }
    }

    resetRating() {
        document.getElementById("rating").value = ""
        document.querySelectorAll("#reviewRating .star").forEach((star) => {
            star.classList.remove("active")
            star.style.color = "#d1d5db"
        })
        this.updateRatingText(0)
    }

    // Avatar Upload
    setupAvatarUpload() {
        const avatarSection = document.querySelector(".profile-avatar-section")
        const avatarInput = document.getElementById("avatarInput")

        if (avatarSection && avatarInput) {
            avatarSection.addEventListener("click", () => {
                avatarInput.click()
            })

            avatarInput.addEventListener("change", (e) => {
                const file = e.target.files[0]
                if (file) {
                    this.uploadAvatar(file)
                }
            })
        }
    }

    async uploadAvatar(file) {
        if (!file.type.startsWith("image/")) {
            this.showNotification("File harus berupa gambar", "error")
            return
        }

        if (file.size > 2 * 1024 * 1024) {
            this.showNotification("Ukuran file maksimal 2MB", "error")
            return
        }

        this.showLoading(true)

        try {
            const formData = new FormData()
            formData.append("action", "upload_avatar")
            formData.append("avatar", file)

            const response = await fetch(window.location.href, {
                method: "POST",
                body: formData,
            })

            const result = await response.json()

            if (result.success) {
                this.showNotification(result.message, "success")
                this.updateAvatarDisplay(result.filename)
            } else {
                this.showNotification(result.message, "error")
            }
        } catch (error) {
            this.showNotification("Terjadi kesalahan sistem", "error")
        } finally {
            this.showLoading(false)
        }
    }

    updateAvatarDisplay(filename) {
        const avatarElements = document.querySelectorAll(".avatar-img, .avatar-img-large")
        const avatarIcons = document.querySelectorAll(".user-avatar i, .profile-avatar-large i")

        avatarElements.forEach((img) => {
            img.src = `../logic/user/avatars/${filename}`
            img.style.display = "block"
        })

        avatarIcons.forEach((icon) => {
            icon.style.display = "none"
        })

        const userAvatar = document.querySelector(".user-avatar")
        const profileAvatar = document.querySelector(".profile-avatar-large")

        if (userAvatar && !userAvatar.querySelector(".avatar-img")) {
            userAvatar.innerHTML = `<img src="../logic/user/avatars/${filename}" alt="Avatar" class="avatar-img">`
        }

        if (profileAvatar && !profileAvatar.querySelector(".avatar-img-large")) {
            profileAvatar.innerHTML = `<img src="../logic/user/avatars/${filename}" alt="Avatar" class="avatar-img-large">
                                      <div class="avatar-upload-overlay">
                                          <i class="fas fa-camera"></i>
                                          <span>Ubah Foto</span>
                                      </div>`
        }
    }

    // Form Handling
    setupForms() {
        const reviewForm = document.getElementById("reviewForm")
        if (reviewForm) {
            reviewForm.addEventListener("submit", (e) => {
                e.preventDefault()
                this.submitReview()
            })
        }

        const profileForm = document.getElementById("profileForm")
        if (profileForm) {
            profileForm.addEventListener("submit", (e) => {
                e.preventDefault()
                this.updateProfile()
            })

            const alamatInput = document.getElementById("profile_alamat")
            const newPasswordInput = document.getElementById("new_password")
            const currentPasswordInput = document.getElementById("current_password")

            if (alamatInput) {
                alamatInput.addEventListener("input", (e) => {
                    const remaining = 500 - e.target.value.length
                    const helpText = e.target.parentElement.querySelector(".form-help")
                    if (remaining < 0) {
                        helpText.style.color = "#ef4444"
                        helpText.textContent = `Melebihi ${Math.abs(remaining)} karakter`
                    } else {
                        helpText.style.color = "var(--color-primary)"
                        helpText.textContent = `Sisa ${remaining} karakter`
                    }
                })
            }

            if (newPasswordInput && currentPasswordInput) {
                newPasswordInput.addEventListener("input", (e) => {
                    if (e.target.value && !currentPasswordInput.value) {
                        currentPasswordInput.style.borderColor = "#ef4444"
                        currentPasswordInput.placeholder = "Password lama wajib diisi"
                    } else {
                        currentPasswordInput.style.borderColor = ""
                        currentPasswordInput.placeholder = "Kosongkan jika tidak ingin mengubah"
                    }
                })
            }
        }
    }

    // Simple Animations (reduced from excessive scaling)
    setupSimpleAnimations() {
        // Simple fade-in for stats cards
        const statCards = document.querySelectorAll(".stat-card")
        statCards.forEach((card, index) => {
            card.style.opacity = "0"
            card.style.transform = "translateY(20px)"

            setTimeout(() => {
                card.style.transition = "all 0.4s ease"
                card.style.opacity = "1"
                card.style.transform = "translateY(0)"
            }, index * 100)
        })

        // Simple hover effects for cards (no excessive scaling)
        this.setupCardHoverEffects()
        this.setupScrollAnimations()
    }

    setupCardHoverEffects() {
        const cards = document.querySelectorAll(".stat-card, .order-card, .review-card, .warranty-card")

        cards.forEach((card) => {
            card.addEventListener("mouseenter", () => {
                card.style.transform = "translateY(-4px)" // Reduced from -8px
                card.style.boxShadow = "0 8px 25px rgba(0,0,0,0.1)" // Reduced shadow
            })

            card.addEventListener("mouseleave", () => {
                card.style.transform = "translateY(0)"
                card.style.boxShadow = ""
            })
        })
    }

    setupScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px",
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = "1"
                    entry.target.style.transform = "translateY(0)"
                }
            })
        }, observerOptions)

        const animateElements = document.querySelectorAll(".order-card, .review-card, .warranty-card, .insight-card")
        animateElements.forEach((el) => {
            el.style.opacity = "0"
            el.style.transform = "translateY(20px)" // Reduced from 30px
            el.style.transition = "all 0.4s ease" // Reduced from 0.6s
            observer.observe(el)
        })
    }

    // API Calls
    async submitReview() {
        const formData = new FormData(document.getElementById("reviewForm"))
        formData.append("action", "submit_review")

        if (!formData.get("rating")) {
            this.showNotification("Silakan berikan rating terlebih dahulu", "warning")
            return
        }

        if (formData.get("review_text").trim().length < 10) {
            this.showNotification("Ulasan minimal 10 karakter", "warning")
            return
        }

        this.showLoading(true)

        try {
            const response = await fetch(window.location.href, {
                method: "POST",
                body: formData,
            })

            const result = await response.json()

            if (result.success) {
                this.showNotification(result.message, "success")
                this.closeModal("reviewModal")
                setTimeout(() => {
                    window.location.reload()
                }, 1500)
            } else {
                this.showNotification(result.message, "error")
            }
        } catch (error) {
            this.showNotification("Terjadi kesalahan sistem", "error")
        } finally {
            this.showLoading(false)
        }
    }

    async updateProfile() {
        const formData = new FormData(document.getElementById("profileForm"))
        formData.append("action", "update_profile")

        let phone = formData.get("phone").trim()
        const alamat = formData.get("alamat")
        const newPassword = formData.get("new_password")
        const currentPassword = formData.get("current_password")

        if (phone) {
            const digits = phone.replace(/\D/g, "") // hanya angka
            if (digits.length < 9 || digits.length > 13) {
                this.showNotification("Nomor telepon 9-13 digit", "error")
                return
            }
            // Pastikan dikirim dengan prefix +62
            if (!phone.startsWith("+62")) {
                phone = "+62" + digits
                formData.set("phone", phone)
            }
        }

        if (alamat && alamat.length > 500) {
            this.showNotification("Alamat maksimal 500 karakter", "error")
            return
        }

        if (newPassword && !currentPassword) {
            this.showNotification("Password lama harus diisi untuk mengubah password", "error")
            return
        }

        this.showLoading(true)

        try {
            const response = await fetch(window.location.href, {
                method: "POST",
                body: formData,
            })

            const result = await response.json()

            if (result.success) {
                this.showNotification(result.message, "success")
                document.getElementById("current_password").value = ""
                document.getElementById("new_password").value = ""

                const newName = formData.get("name")
                const greetings = document.querySelectorAll(".user-greeting")
                greetings.forEach((greeting) => {
                    greeting.textContent = `Halo, ${newName}`
                })
            } else {
                this.showNotification(result.message, "error")
            }
        } catch (error) {
            this.showNotification("Terjadi kesalahan sistem", "error")
        } finally {
            this.showLoading(false)
        }
    }

    

    async showOrderTracking(orderId) {
        this.showLoading(true)

        try {
            const formData = new FormData()
            formData.append("action", "get_order_tracking")
            formData.append("order_id", orderId)

            const response = await fetch(window.location.href, {
                method: "POST",
                body: formData,
            })

            const result = await response.json()

            if (result.success) {
                this.displayOrderTracking(result.tracking, orderId, result.payment_method)
                this.openModal("trackingModal")
            } else {
                this.showNotification("Gagal memuat data tracking", "error")
            }
        } catch (error) {
            this.showNotification("Terjadi kesalahan sistem", "error")
        } finally {
            this.showLoading(false)
        }
    }

    displayOrderTracking(trackingData, orderId, paymentMethod) {
        const container = document.getElementById("trackingContent")

        if (!trackingData || trackingData.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-truck"></i>
                    <p>Belum ada data tracking untuk Order #${orderId}</p>
                </div>
            `
            return
        }

        // Calculate progress based on payment method
        const isCOD = paymentMethod === "cod"
        const totalSteps = isCOD ? 4 : 5
        const currentStep = trackingData.length
        const progressPercentage = (currentStep / totalSteps) * 100

        const timelineHTML = trackingData
            .map((item, index) => {
                const isCompleted = index < trackingData.length - 1
                const isActive = index === trackingData.length - 1

                return `
                <div class="timeline-item ${isCompleted ? "completed" : ""} ${isActive ? "active" : ""}" style="animation-delay: ${index * 0.1}s">
                    <div class="timeline-dot">
                        <i class="fas ${this.getStatusIcon(item.status)}"></i>
                    </div>
                    <div class="timeline-content">
                        <h5>${this.getStatusTitle(item.status)}</h5>
                        <p>${item.description}</p>
                        <small><i class="fas fa-clock"></i> ${this.formatDate(item.created_at)}</small>
                    </div>
                </div>
            `
            })
            .join("")

        container.innerHTML = `
            <div class="tracking-header">
                <h4>Tracking Order #${orderId}</h4>
                <div class="tracking-progress">
                    <div class="progress-bar" style="width: ${progressPercentage}%"></div>
                </div>
                <p style="color: var(--color-primary); font-size: 0.85rem; margin-top: 0.5rem;">
                    Progress: ${currentStep} dari ${totalSteps} tahap ${isCOD ? "(COD)" : "(Transfer/E-wallet)"}
                </p>
            </div>
            <div class="tracking-timeline">
                ${timelineHTML}
            </div>
        `

        // Simple animation for timeline items
        setTimeout(() => {
            const timelineItems = container.querySelectorAll(".timeline-item")
            timelineItems.forEach((item, index) => {
                setTimeout(() => {
                    item.classList.add("animate")
                }, index * 100)
            })
        }, 100)
    }

    getStatusIcon(status) {
        const statusIcons = {
            "Pesanan Dibuat": "fa-file-alt",
            "Pembayaran Dikonfirmasi": "fa-credit-card",
            "Sedang Dikemas": "fa-box",
            "Sedang Dalam Perjalanan": "fa-truck",
            "Diterima Customer": "fa-check-circle",
        }
        return statusIcons[status] || "fa-circle"
    }

    getStatusTitle(status) {
        return status
    }

    formatDate(dateString) {
        const date = new Date(dateString)
        return date.toLocaleDateString("id-ID", {
            day: "2-digit",
            month: "long",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        })
    }

    // Enhanced method to show payment proof
    showPaymentProof(orderId, proofImage) {
        const modal = document.createElement("div")
        modal.className = "modal"
        modal.id = "paymentProofModal"
        modal.innerHTML = `
            <div class="modal-content modal-compact" style="margin-top:40px;">
                <div class="modal-header">
                    <h3>Bukti Pembayaran - Order #${orderId}</h3>
                    <span class="close" onclick="window.dashboardManager.closeProofModal(this)">&times;</span>
                </div>
                <div class="modal-body" style="text-align: center;">
                    <img src="../logic/payment/uploads/${proofImage}" 
                         alt="Bukti Pembayaran" 
                         style="max-width: 100%; max-height: 400px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <p style="margin-top: 1rem; color: var(--color-gray-600); font-size: 0.875rem;">
                        Bukti pembayaran yang telah diupload
                    </p>
                </div>
            </div>
        `

        document.body.appendChild(modal)
        modal.classList.add('active')
        document.body.style.overflow = 'hidden'

        // Simple fade in
        modal.style.opacity = '0'
        setTimeout(() => {
            modal.style.transition = 'opacity 0.2s ease'
            modal.style.opacity = '1'
        }, 10)

        // Close on outside click
        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.style.opacity = "0"
                setTimeout(() => {
                    modal.remove()
                    document.body.style.overflow = "auto"
                }, 200)
            }
        })
    }

    // Helper to close payment proof modal and restore scroll
    closeProofModal(closeElement) {
        const modal = closeElement.closest('.modal');
        if (!modal) return;
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.remove();
            document.body.style.overflow = 'auto';
        }, 200);
    }

    // Data Refresh Methods
    refreshOrderData() {
        console.log("Refreshing order data...")
    }

    refreshReviewData() {
        console.log("Refreshing review data...")
    }

    refreshWarrantyData() {
        console.log("Refreshing warranty data...")
    }

    loadProfileData() {
        console.log("Loading profile data...")
    }

    loadSuggestionData() {
        console.log("Loading suggestion data...")
    }

    loadInitialData() {
        console.log("Loading initial dashboard data...")
    }

    // Utility Methods
    showLoading(show) {
        const overlay = document.getElementById("loadingOverlay")
        if (overlay) {
            if (show) {
                overlay.style.display = "flex"
                overlay.style.opacity = "0"
                setTimeout(() => {
                    overlay.style.transition = "opacity 0.2s ease"
                    overlay.style.opacity = "1"
                }, 10)
            } else {
                overlay.style.opacity = "0"
                setTimeout(() => {
                    overlay.style.display = "none"
                }, 200)
            }
        }
    }

    showNotification(message, type = "info") {
        const container = document.getElementById("notification-container")
        if (!container) return

        container.innerHTML = ""

        const notification = document.createElement("div")
        notification.className = `notification ${type}`

        const iconMap = {
            success: "fa-check-circle",
            error: "fa-exclamation-circle",
            warning: "fa-exclamation-triangle",
            info: "fa-info-circle",
        }

        notification.innerHTML = `
            <div class="notification-icon">
                <i class="fas ${iconMap[type]}"></i>
            </div>
            <div class="notification-content">
                <span>${message}</span>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `

        container.appendChild(notification)

        // Simple slide in animation
        notification.style.transform = "translateX(300px)"
        notification.style.opacity = "0"

        setTimeout(() => {
            notification.style.transition = "all 0.3s ease"
            notification.style.transform = "translateX(0)"
            notification.style.opacity = "1"
        }, 100)

        // Auto hide after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.transform = "translateX(300px)"
                notification.style.opacity = "0"
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove()
                    }
                }, 300)
            }
        }, 5000)
    }
}

// Global Functions
function openReviewModal(orderId) {
    document.getElementById("review_order_id").value = orderId
    window.dashboardManager.openModal("reviewModal")
}

function showOrderTracking(orderId) {
    window.dashboardManager.showOrderTracking(orderId)
}

function showPaymentProof(orderId, proofImage) {
    window.dashboardManager.showPaymentProof(orderId, proofImage)
}

function closeModal(modalId) {
    window.dashboardManager.closeModal(modalId)
}

// Initialize Dashboard
document.addEventListener("DOMContentLoaded", () => {
    window.dashboardManager = new DashboardManager()
    
    // Inisialisasi modal dan event listeners untuk halaman profil
    initProfilePage()
})

/**
 * Inisialisasi halaman profil
 */
function initProfilePage() {
    // Inisialisasi modal
    const changeEmailBtn = document.getElementById('changeEmailBtn')
    const changePasswordBtn = document.getElementById('changePasswordBtn')
    const emailModal = document.getElementById('emailModal')
    const passwordModal = document.getElementById('passwordModal')
    

    
    // Modal untuk ganti email
    if (changeEmailBtn) {
        changeEmailBtn.addEventListener('click', () => {
            window.dashboardManager.openModal('emailModal')
        })
    }
    
    // Modal untuk ganti password
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', () => {
            window.dashboardManager.openModal('passwordModal')
        })
    }
    
    // Validasi form ganti password (handled in IIFE below)

    
    // Toggle password visibility
window.togglePasswordVisibility = function(icon) {
    const input = icon.parentElement.querySelector('input');
    if (!input) return;
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
    // legacy code removed
}

// Duplicate listener removed, handled above
(function() {
    const passwordForm = document.getElementById('passwordForm')
    if (!passwordForm) return
    passwordForm.addEventListener('submit', function(e) {
        const newPass = document.getElementById('new_password').value
        const confirmPass = document.getElementById('confirm_password').value
        if (newPass !== confirmPass) {
            e.preventDefault()
            showNotification('Konfirmasi password tidak cocok', 'error')
            document.getElementById('confirm_password').focus()
            return false
        }
        // Kirim data ke server
        e.preventDefault()
        const currentPassword = document.getElementById('current_password').value
        const formData = new FormData()
        formData.append('current_password', currentPassword)
        formData.append('new_password', newPass)
        fetch('../logic/user/update_password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Password berhasil diubah', 'success')
                closeModal('passwordModal')
                passwordForm.reset()
            } else {
                showNotification(data.message || 'Gagal mengubah password', 'error')
            }
        })
        .catch(error => {
            console.error('Error:', error)
            showNotification('Terjadi kesalahan saat mengubah password', 'error')
        })
    })
})();

// Validasi & submit form ganti email
    const emailForm = document.getElementById('changeEmailForm')
    if (emailForm) {
        emailForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const emailInput = document.getElementById('new_email')
            const email = emailInput.value.trim()
            
            // Validasi format email Google
            const emailPattern = /^[a-zA-Z0-9._%+-]+@gmail\.com$/
            if (!emailPattern.test(email)) {
                e.preventDefault()
                showNotification('Hanya email @gmail.com yang diperbolehkan', 'error')
                emailInput.focus()
                return false
            }
            
            // Tampilkan loading
            const submitBtn = emailForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

            // Kirim request AJAX
            const formData = new FormData();
            formData.append('email', email);

            fetch('../logic/user/update_email.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Email berhasil diubah', 'success');
                    closeModal('emailModal');
                    emailForm.reset();
                } else {
                    showNotification(data.message || 'Gagal mengubah email', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showNotification('Terjadi kesalahan saat mengubah email', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
            
        })
    }
    
    // Handle avatar upload
    const avatarInput = document.getElementById('avatarInput')
    const avatarPreview = document.querySelector('.profile-avatar-large')
    
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0]
            if (file) {
                // Validasi ukuran file (maks 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showNotification('Ukuran file maksimal 2MB', 'error')
                    return
                }
                
                // Validasi tipe file
                const validTypes = ['image/jpeg', 'image/png', 'image/gif']
                if (!validTypes.includes(file.type)) {
                    showNotification('Format file tidak didukung. Gunakan JPG, PNG, atau GIF', 'error')
                    return
                }
                
                // Tampilkan preview
                const reader = new FileReader()
                reader.onload = function(e) {
                    // Hapus ikon default jika ada
                    const existingIcon = avatarPreview.querySelector('i')
                    if (existingIcon) {
                        existingIcon.remove()
                    }
                    
                    // Set gambar preview
                    let img = avatarPreview.querySelector('img')
                    if (!img) {
                        img = document.createElement('img')
                        img.className = 'avatar-img-large'
                        avatarPreview.prepend(img)
                    }
                    img.src = e.target.result
                    
                    // Upload otomatis
                    uploadAvatar(file)
                }
                reader.readAsDataURL(file)
            }
        })
        
        // Fungsi untuk mengupload avatar
        function uploadAvatar(file) {
            const formData = new FormData()
            formData.append('avatar', file)
            
            // Tampilkan loading
            const uploadIndicator = document.createElement('div')
            uploadIndicator.className = 'upload-indicator'
            uploadIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'
            avatarPreview.appendChild(uploadIndicator)
            
            fetch('../logic/user/upload_avatar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Foto profil berhasil diubah', 'success')
                    // Perbarui gambar profil di header jika ada
                    const headerAvatar = document.querySelector('.user-avatar')
                    if (headerAvatar) {
                        headerAvatar.src = data.avatarUrl + '?t=' + new Date().getTime()
                    }
                } else {
                    showNotification(data.message || 'Gagal mengunggah foto profil', 'error')
                }
            })
            .catch(error => {
                console.error('Error:', error)
                showNotification('Terjadi kesalahan saat mengunggah foto', 'error')
            })
            .finally(() => {
                // Hapus indikator loading
                if (uploadIndicator.parentNode === avatarPreview) {
                    avatarPreview.removeChild(uploadIndicator)
                }
            })
        }
    }


/**
 * Menampilkan notifikasi
 * @param {string} message - Pesan notifikasi
 * @param {string} type - Tipe notifikasi (success, error, warning, info)
 */
function showNotification(message, type = 'info') {
    // Dapatkan container notifikasi
    let container = document.getElementById('notification-container');
    if (!container) {
        // Buat container jika belum ada
        container = document.createElement('div');
        container.id = 'notification-container';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '1000';
        document.body.appendChild(container);
    } else {
        // Hapus notifikasi yang sudah ada
        container.innerHTML = '';
    }
    
    // Buat elemen notifikasi
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    // Tambahkan ikon berdasarkan tipe
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    else if (type === 'error') icon = 'exclamation-circle';
    else if (type === 'warning') icon = 'exclamation-triangle';
    
    notification.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
    `;
    
    // Tambahkan ke container
    container.appendChild(notification);
    
    // Tampilkan notifikasi dengan animasi
    requestAnimationFrame(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-20px)';
        
        requestAnimationFrame(() => {
            notification.style.transition = 'opacity 0.3s, transform 0.3s';
            notification.style.opacity = '1';
            notification.style.transform = 'translateY(0)';
        });
    });
    
    // Sembunyikan setelah 5 detik
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-20px)';
        
        // Hapus elemen setelah animasi selesai
        setTimeout(() => {
            if (notification.parentNode === container) {
                container.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

/**
 * Menutup modal
 * @param {string} modalId - ID modal yang akan ditutup
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId)
    if (modal) {
        modal.classList.remove('active')
        document.body.style.overflow = 'auto'
    }
}
