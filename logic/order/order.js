// Enhanced Order Page JavaScript - FIXED VERSION
class OrderPage {
    constructor() {
        this.productPrice = window.productPrice || 299000
        this.shippingCost = window.shippingCost || 15000
        this.currentQuantity = 1
        this.isSubmitting = false
        this.init()
    }

    init() {
        this.setupFormValidation()
        this.setupQuantityControls()
        this.setupFormSubmission()
        this.setupPhoneFormatting()
        this.setupAddressAutocomplete()
        this.updateOrderSummary()
    }

    // Setup form validation
    setupFormValidation() {
        const inputs = document.querySelectorAll("input, select, textarea")

        inputs.forEach((input) => {
            input.addEventListener("blur", () => {
                this.validateField(input)
            })

            input.addEventListener("input", () => {
                this.clearFieldError(input)
                this.updateFieldAppearance(input)
            })

            input.addEventListener("focus", () => {
                this.handleFieldFocus(input)
            })
        })
    }

    // Handle field focus
    handleFieldFocus(field) {
        const formGroup = field.closest(".form-group")
        if (formGroup) {
            formGroup.classList.add("focused")
        }
    }

    // Update field appearance based on content
    updateFieldAppearance(field) {
        const formGroup = field.closest(".form-group")

        if (field.value.trim()) {
            field.style.background = "white"
            field.style.color = "#1f2937"
        } else {
            field.style.background = "#f9fafb"
            field.style.color = "#374151"
        }

        if (formGroup) {
            formGroup.classList.remove("focused")
        }
    }

    // Validate individual field
    validateField(field) {
        const value = field.value.trim()
        const fieldName = field.name
        const formGroup = field.closest(".form-group")

        this.clearFieldError(field)

        let isValid = true
        let errorMessage = ""

        if (field.hasAttribute("required") && !value) {
            isValid = false
            errorMessage = `${this.getFieldLabel(fieldName)} harus diisi`
        }

        if (value) {
            switch (fieldName) {
                case "phone":
                    if (!this.isValidPhone(value)) {
                        isValid = false
                        errorMessage = "Format nomor telepon tidak valid"
                    }
                    break

                case "address":
                    if (value.length < 20) {
                        isValid = false
                        errorMessage = "Alamat terlalu singkat (minimal 20 karakter)"
                    }
                    break

                case "quantity":
                    const qty = Number.parseInt(value)
                    if (qty < 1 || qty > 10) {
                        isValid = false
                        errorMessage = "Jumlah harus antara 1-10 item"
                    }
                    break
            }
        }

        if (!isValid) {
            this.showFieldError(formGroup, errorMessage)
        } else if (value) {
            this.showFieldSuccess(formGroup)
        }

        return isValid
    }

    // Clear field error state
    clearFieldError(field) {
        const formGroup = field.closest(".form-group")
        if (formGroup) {
            formGroup.classList.remove("error", "success")

            const existingError = formGroup.querySelector(".error-message")
            if (existingError) {
                existingError.remove()
            }
        }
    }

    // Show field error
    showFieldError(formGroup, message) {
        if (formGroup) {
            formGroup.classList.add("error")
            formGroup.classList.remove("success")

            const errorElement = document.createElement("div")
            errorElement.className = "error-message"
            errorElement.textContent = message
            formGroup.appendChild(errorElement)
        }
    }

    // Show field success
    showFieldSuccess(formGroup) {
        if (formGroup) {
            formGroup.classList.add("success")
            formGroup.classList.remove("error")
        }
    }

    // Get field label
    getFieldLabel(fieldName) {
        const labels = {
            color: "Warna produk",
            quantity: "Jumlah",
            phone: "Nomor telepon",
            address: "Alamat pengiriman",
        }
        return labels[fieldName] || fieldName
    }

    // Phone validation
    isValidPhone(phone) {
        const phoneRegex = /^(\+62|62|0)[0-9]{9,13}$/
        return phoneRegex.test(phone.replace(/\s|-/g, ""))
    }

    // Setup quantity controls - FIXED
    setupQuantityControls() {
        const quantityInput = document.getElementById("quantity")
        const minusBtn = document.querySelector(".qty-btn.minus")
        const plusBtn = document.querySelector(".qty-btn.plus")

        if (quantityInput) {
            this.currentQuantity = Number.parseInt(quantityInput.value) || 1

            quantityInput.addEventListener("input", () => {
                const newQuantity = Number.parseInt(quantityInput.value) || 1
                this.currentQuantity = Math.max(1, Math.min(10, newQuantity))
                quantityInput.value = this.currentQuantity
                this.updateOrderSummary()
            })
        }

        if (minusBtn) {
            minusBtn.addEventListener("click", () => {
                this.changeQuantity(-1)
            })
        }

        if (plusBtn) {
            plusBtn.addEventListener("click", () => {
                this.changeQuantity(1)
            })
        }
    }

    // Change quantity - FIXED
    changeQuantity(delta) {
        if (!window.profileComplete) return

        const quantityInput = document.getElementById("quantity")
        const newQuantity = Math.max(1, Math.min(10, this.currentQuantity + delta))

        if (newQuantity !== this.currentQuantity) {
            this.currentQuantity = newQuantity
            quantityInput.value = this.currentQuantity
            this.updateOrderSummary()

            // Add animation to quantity buttons
            const btn = delta > 0 ? document.querySelector(".qty-btn.plus") : document.querySelector(".qty-btn.minus")
            this.animateButton(btn)
        }
    }

    // Animate button click
    animateButton(button) {
        if (!button) return

        button.style.transform = "scale(0.9)"
        button.style.background = "#3b82f6"
        button.style.color = "white"

        setTimeout(() => {
            button.style.transform = "scale(1)"
            button.style.background = ""
            button.style.color = ""
        }, 150)
    }

    // Update order summary
    updateOrderSummary() {
        const itemCountElement = document.getElementById("item-count")
        const subtotalElement = document.getElementById("subtotal")
        const totalElement = document.getElementById("total")

        if (itemCountElement && subtotalElement && totalElement) {
            const subtotal = this.productPrice * this.currentQuantity
            const total = subtotal + this.shippingCost

            itemCountElement.textContent = this.currentQuantity
            subtotalElement.textContent = this.formatCurrency(subtotal)
            totalElement.textContent = this.formatCurrency(total)

                // Add animation to updated elements
                ;[subtotalElement, totalElement].forEach((element) => {
                    element.style.transform = "scale(1.05)"
                    element.style.color = "#3b82f6"

                    setTimeout(() => {
                        element.style.transform = "scale(1)"
                        element.style.color = ""
                    }, 200)
                })
        }
    }

    // Format currency
    formatCurrency(amount) {
        return "Rp " + amount.toLocaleString("id-ID")
    }

    // Setup phone number formatting
    setupPhoneFormatting() {
        const phoneInput = document.getElementById("phone")
        if (phoneInput) {
            phoneInput.addEventListener("input", (e) => {
                let value = e.target.value.replace(/\D/g, "")

                if (value.startsWith("62")) {
                    value = "+" + value
                } else if (value.startsWith("0")) {
                    value = "+62" + value.substring(1)
                } else if (value && !value.startsWith("+")) {
                    value = "+62" + value
                }

                e.target.value = value
            })
        }
    }

    // Setup address autocomplete
    setupAddressAutocomplete() {
        const addressInput = document.getElementById("address")
        if (addressInput) {
            const formGroup = addressInput.closest(".form-group")
            const counter = document.createElement("div")
            counter.className = "character-counter"
            counter.style.cssText = `
                font-size: 0.8rem;
                color: #64748b;
                text-align: right;
                margin-top: 0.25rem;
            `
            formGroup.appendChild(counter)

            const updateCounter = () => {
                const length = addressInput.value.length
                counter.textContent = `${length}/500 karakter`

                if (length < 20) {
                    counter.style.color = "#ef4444"
                } else if (length > 450) {
                    counter.style.color = "#f59e0b"
                } else {
                    counter.style.color = "#64748b"
                }
            }

            addressInput.addEventListener("input", updateCounter)
            updateCounter()
        }
    }

    // Setup form submission
    setupFormSubmission() {
        const form = document.querySelector(".order-form")

        if (form) {
            form.addEventListener("submit", (e) => {
                e.preventDefault()
                this.handleOrderSubmit()
            })
        }
    }

    // Handle order submit button click
    handleOrderSubmit() {
        if (!window.profileComplete) {
            alert("Lengkapi profil di dashboard terlebih dahulu")
            return
        }

        if (this.isSubmitting) {
            return
        }

        // Validate form
        const color = document.getElementById("color").value
        const phone = document.getElementById("phone").value.trim()
        const address = document.getElementById("address").value.trim()

        if (!color || !phone || !address) {
            alert("Mohon lengkapi semua field yang wajib diisi")
            return
        }

        if (address.length < 10) {
            alert("Alamat terlalu singkat (minimal 10 karakter)")
            return
        }

        // Check for profile changes first
        if (this.checkProfileChanges()) {
            // No changes, show order confirmation
            this.showOrderConfirmation()
        }
    }

    // Show order confirmation modal
    showOrderConfirmation() {
        const color = document.getElementById("color").value
        const quantity = document.getElementById("quantity").value
        const colorName = window.productColors[color] || color
        const total = this.productPrice * quantity + this.shippingCost

        document.getElementById("confirm-color").textContent = colorName
        document.getElementById("confirm-quantity").textContent = quantity + " unit"
        document.getElementById("confirm-total").textContent = "Rp " + total.toLocaleString("id-ID")

        document.getElementById("orderConfirmationModal").style.display = "flex"
    }

    // Check for profile changes before form submission
    checkProfileChanges() {
        if (!window.profileComplete) return true

        const currentPhone = document.getElementById("phone").value.trim()
        const currentAddress = document.getElementById("address").value.trim()

        const phoneChanged = currentPhone !== window.originalPhone
        const addressChanged = currentAddress !== window.originalAddress

        if (phoneChanged || addressChanged) {
            const changes = []
            if (phoneChanged) {
                changes.push(
                    `<div class="change-item"><strong>Nomor Telepon:</strong><br>Dari: ${window.originalPhone}<br>Ke: ${currentPhone}</div>`,
                )
            }
            if (addressChanged) {
                changes.push(
                    `<div class="change-item"><strong>Alamat:</strong><br>Dari: ${window.originalAddress.substring(0, 50)}...<br>Ke: ${currentAddress.substring(0, 50)}...</div>`,
                )
            }

            document.getElementById("profileChanges").innerHTML = changes.join("")
            document.getElementById("profileChangeModal").style.display = "flex"
            return false
        }

        return true
    }

    // Set form loading state
    setFormLoading(button, loading) {
        if (!button) return

        if (loading) {
            button.classList.add("form-loading")
            button.disabled = true
            const originalText = button.innerHTML
            button.setAttribute("data-original-text", originalText)
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...'
        } else {
            button.classList.remove("form-loading")
            button.disabled = false
            const originalText = button.getAttribute("data-original-text")
            if (originalText) {
                button.innerHTML = originalText
            }
        }
    }

    // Show notification
    showNotification(message, type = "info") {
        const existingNotifications = document.querySelectorAll(".notification")
        existingNotifications.forEach((notification) => {
            notification.remove()
        })

        const notification = document.createElement("div")
        notification.className = `notification notification-${type}`

        const iconMap = {
            success: "fa-check-circle",
            error: "fa-exclamation-circle",
            info: "fa-info-circle",
            warning: "fa-exclamation-triangle",
        }

        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${iconMap[type]}"></i>
                <span>${message}</span>
                <button class="notification-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `

        notification.style.cssText = `
            position: fixed;
            top: 100px;
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
        `

        document.body.appendChild(notification)

        setTimeout(() => {
            notification.style.transform = "translateX(0)"
        }, 100)

        const closeBtn = notification.querySelector(".notification-close")
        closeBtn.addEventListener("click", () => {
            this.closeNotification(notification)
        })

        setTimeout(() => {
            this.closeNotification(notification)
        }, 5000)
    }

    // Get notification color
    getNotificationColor(type) {
        const colors = {
            success: "#10b981",
            error: "#ef4444",
            info: "#3b82f6",
            warning: "#f59e0b",
        }
        return colors[type] || colors.info
    }

    // Close notification
    closeNotification(notification) {
        if (!notification || !notification.parentNode) return

        notification.style.transform = "translateX(400px)"
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification)
            }
        }, 300)
    }
}

// Global functions for HTML onclick handlers
function changeQuantity(delta) {
    if (window.orderPage) {
        window.orderPage.changeQuantity(delta)
    }
}

function handleOrderSubmit() {
    if (window.orderPage) {
        window.orderPage.handleOrderSubmit()
    }
}

function enablePhoneEdit() {
    const phoneInput = document.getElementById("phone")
    phoneInput.removeAttribute("readonly")
    phoneInput.focus()
    phoneInput.style.borderColor = "#f59e0b"
    phoneInput.style.background = "#fffbeb"
}

function enableAddressEdit() {
    const addressInput = document.getElementById("address")
    addressInput.removeAttribute("readonly")
    addressInput.focus()
    addressInput.style.borderColor = "#f59e0b"
    addressInput.style.background = "#fffbeb"
}

function confirmOrder() {
    if (window.orderPage && window.orderPage.isSubmitting) return

    window.orderPage.isSubmitting = true

    // Update button states
    const confirmBtn = document.getElementById("confirmBtn")
    const submitBtn = document.getElementById("submitBtn")

    confirmBtn.disabled = true
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...'

    submitBtn.disabled = true
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...'

    // Copy data to hidden form
    document.getElementById("hidden-color").value = document.getElementById("color").value
    document.getElementById("hidden-quantity").value = document.getElementById("quantity").value
    document.getElementById("hidden-phone").value = document.getElementById("phone").value
    document.getElementById("hidden-address").value = document.getElementById("address").value

    // Hide modal
    document.getElementById("orderConfirmationModal").style.display = "none"

    // Submit hidden form
    setTimeout(() => {
        document.getElementById("hiddenOrderForm").submit()
    }, 100)
}

function cancelOrder() {
    document.getElementById("orderConfirmationModal").style.display = "none"
}

function confirmProfileChange() {
    document.getElementById("profileChangeModal").style.display = "none"
    if (window.orderPage) {
        window.orderPage.showOrderConfirmation()
    }
}

function cancelProfileChange() {
    document.getElementById("profileChangeModal").style.display = "none"
    // Reset fields
    document.getElementById("phone").value = window.originalPhone
    document.getElementById("address").value = window.originalAddress

    // Reset styles
    const phoneInput = document.getElementById("phone")
    const addressInput = document.getElementById("address")

    phoneInput.setAttribute("readonly", true)
    addressInput.setAttribute("readonly", true)

    phoneInput.style.borderColor = ""
    phoneInput.style.background = ""
    addressInput.style.borderColor = ""
    addressInput.style.background = ""
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
    window.orderPage = new OrderPage()
})

// Handle page unload warning
document.addEventListener("DOMContentLoaded", () => {
    let formDirty = false
    const formInputs = document.querySelectorAll("#orderForm input, #orderForm select, #orderForm textarea")

    formInputs.forEach((input) => {
        input.addEventListener("change", () => {
            formDirty = true
        })
    })

    window.addEventListener("beforeunload", (e) => {
        if (formDirty && !window.orderPage.isSubmitting) {
            e.preventDefault()
            e.returnValue = ""
        }
    })
})

// Handle page visibility changes
document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
        console.log("Order page is now visible")
    }
})
