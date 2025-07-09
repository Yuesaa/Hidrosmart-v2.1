// Enhanced Payment Page JavaScript
class PaymentPage {
    constructor() {
        this.selectedMethod = null
        this.maxFileSize = 5 * 1024 * 1024 // 5MB
        this.allowedTypes = ["image/jpeg", "image/jpg", "image/png", "application/pdf"]
        this.currentFile = null
        this.isSubmitting = false
        this.init()
    }

    init() {
        this.setupPaymentMethods()
        this.setupFileUpload()
        this.setupFormSubmission()
        this.setupPaymentMethodHandling()
        this.setupCustomConfirmation()
        this.createProcessingPopupHandler()
    }

    // Setup custom confirmation instead of browser alert
    setupCustomConfirmation() {
        let formDirty = false
        const form = document.querySelector(".payment-form")

        if (form) {
            const formInputs = form.querySelectorAll("input, select, textarea")

            formInputs.forEach((input) => {
                input.addEventListener("change", () => {
                    formDirty = true
                })
            })


        }
    }

    // Setup payment method selection with enhanced animations
    setupPaymentMethods() {
        const paymentOptions = document.querySelectorAll('input[name="payment_method"]')

        paymentOptions.forEach((option) => {
            option.addEventListener("change", () => {
                this.handlePaymentMethodChange(option.value)
            })

            // Add hover effects to payment option labels
            const label = option.nextElementSibling
            label.addEventListener("mouseenter", () => {
                if (!option.checked) {
                    label.style.transform = "translateY(-2px)"
                    label.style.boxShadow = "0 8px 25px rgba(0,0,0,0.1)"
                }
            })

            label.addEventListener("mouseleave", () => {
                if (!option.checked) {
                    label.style.transform = "translateY(0)"
                    label.style.boxShadow = "0 2px 10px rgba(0,0,0,0.05)"
                }
            })
        })

        // Check if any method is already selected
        const selectedOption = document.querySelector('input[name="payment_method"]:checked')
        if (selectedOption) {
            this.handlePaymentMethodChange(selectedOption.value)
        }
    }

    // Handle payment method change with smooth animations
    handlePaymentMethodChange(method) {
        this.selectedMethod = method

        // Update all payment option styles
        const allOptions = document.querySelectorAll(".payment-option-label")
        allOptions.forEach((label) => {
            label.style.transform = "translateY(0)"
            label.style.boxShadow = "0 2px 10px rgba(0,0,0,0.05)"
            label.style.borderColor = "#e2e8f0"
            label.style.background = "white"
        })

        // Highlight selected option
        const selectedLabel = document.querySelector(`label[for="${method}"]`)
        if (selectedLabel) {
            selectedLabel.style.borderColor = "#3b82f6"
            selectedLabel.style.background = "#f0f9ff"
            selectedLabel.style.transform = "translateY(-2px)"
            selectedLabel.style.boxShadow = "0 8px 25px rgba(59, 130, 246, 0.2)"
        }

        // Hide all payment details with fade out
        const allDetails = document.querySelectorAll(".payment-details")
        allDetails.forEach((detail) => {
            detail.style.opacity = "0"
            detail.style.transform = "translateY(10px)"
            setTimeout(() => {
                detail.style.display = "none"
            }, 200)
        })

        // Show selected method details with fade in
        setTimeout(() => {
            const selectedDetails = document.getElementById(`${method.replace("_", "-")}-details`)
            if (selectedDetails) {
                selectedDetails.style.display = "block"
                selectedDetails.style.opacity = "0"
                selectedDetails.style.transform = "translateY(10px)"

                setTimeout(() => {
                    selectedDetails.style.transition = "all 0.4s cubic-bezier(0.4, 0, 0.2, 1)"
                    selectedDetails.style.opacity = "1"
                    selectedDetails.style.transform = "translateY(0)"
                }, 50)
            }
        }, 200)

        // Update submit button with animation
        this.updateSubmitButton(method)
    }

    // Update submit button based on payment method
    updateSubmitButton(method) {
        const submitBtn = document.querySelector(".btn-submit")
        if (!submitBtn) return

        // Add loading animation
        submitBtn.style.transform = "scale(0.98)"

        setTimeout(() => {
            const icon = submitBtn.querySelector("i")
            const textNodes = Array.from(submitBtn.childNodes).filter((node) => node.nodeType === Node.TEXT_NODE)

            switch (method) {
                case "bank_transfer":
                    if (icon) icon.className = "fas fa-university"
                    if (textNodes.length > 0) textNodes[0].textContent = " Konfirmasi Transfer Bank"
                    submitBtn.style.background = "linear-gradient(45deg, #1e40af, #3b82f6)"
                    break
                case "ewallet":
                    if (icon) icon.className = "fas fa-mobile-alt"
                    if (textNodes.length > 0) textNodes[0].textContent = " Konfirmasi E-Wallet"
                    submitBtn.style.background = "linear-gradient(45deg, #059669, #10b981)"
                    break
                case "cod":
                    if (icon) icon.className = "fas fa-money-bill-wave"
                    if (textNodes.length > 0) textNodes[0].textContent = " Konfirmasi COD"
                    submitBtn.style.background = "linear-gradient(45deg,  #d97706, #fbbf24)"
                    break
                default:
                    if (icon) icon.className = "fas fa-check"
                    if (textNodes.length > 0) textNodes[0].textContent = " Konfirmasi Pesanan"
                    submitBtn.style.background = "linear-gradient(45deg, #3b82f6, #1d4ed8)"
            }

            submitBtn.style.transform = "scale(1)"
        }, 100)
    }

    // Setup enhanced file upload
    setupFileUpload() {
        const fileInput = document.getElementById("payment_proof")
        const uploadArea = document.getElementById("fileUploadArea")
        const filePreview = document.getElementById("filePreview")
        const removeBtn = document.getElementById("removeFileBtn")

        if (!fileInput || !uploadArea) return

        // Click to upload
        uploadArea.addEventListener("click", () => {
            fileInput.click()
        })

        // Drag and drop functionality
        uploadArea.addEventListener("dragover", (e) => {
            e.preventDefault()
            uploadArea.classList.add("drag-over")
        })

        uploadArea.addEventListener("dragleave", (e) => {
            e.preventDefault()
            uploadArea.classList.remove("drag-over")
        })

        uploadArea.addEventListener("drop", (e) => {
            e.preventDefault()
            uploadArea.classList.remove("drag-over")

            if (e.dataTransfer.files.length > 0) {
                const file = e.dataTransfer.files[0]
                this.handleFileSelection(file)
            }
        })

        // File input change
        fileInput.addEventListener("change", (e) => {
            if (e.target.files.length > 0) {
                this.handleFileSelection(e.target.files[0])
            }
        })

        // Remove file button
        if (removeBtn) {
            removeBtn.addEventListener("click", () => {
                this.removeFile()
            })
        }
    }

    // Handle file selection
    handleFileSelection(file) {
        // Validate file
        if (!this.validateFile(file)) {
            return
        }

        this.currentFile = file
        this.showSingleFilePreview(file)
    }

    // Validate file
    validateFile(file) {
        // Check file type
        if (!this.allowedTypes.includes(file.type)) {
            this.showNotification("Format file tidak didukung. Gunakan JPG, PNG, atau PDF.", "error")
            return false
        }

        // Check file size
        if (file.size > this.maxFileSize) {
            this.showNotification("Ukuran file terlalu besar. Maksimal 5MB.", "error")
            return false
        }

        return true
    }

    // Show single file preview (prevents duplicates)
    showSingleFilePreview(file) {
        const uploadArea = document.getElementById("fileUploadArea")
        const filePreview = document.getElementById("filePreview")
        const previewContent = document.getElementById("previewContent")
        const fileInfo = document.getElementById("fileInfo")

        if (!uploadArea || !filePreview || !previewContent || !fileInfo) return

        // Clear any existing preview
        previewContent.innerHTML = ""
        fileInfo.innerHTML = ""

        // Hide upload area and show preview
        uploadArea.style.display = "none"
        filePreview.style.display = "block"

        // Create preview based on file type
        if (file.type.startsWith("image/")) {
            const img = document.createElement("img")
            img.src = URL.createObjectURL(file)
            img.alt = "Preview"
            img.className = "image-preview"
            img.onload = () => URL.revokeObjectURL(img.src)
            previewContent.appendChild(img)
        } else if (file.type === "application/pdf") {
            const pdfIcon = document.createElement("div")
            pdfIcon.className = "pdf-preview"
            pdfIcon.innerHTML = `
          <i class="fas fa-file-pdf"></i>
          <span>PDF Document</span>
      `
            previewContent.appendChild(pdfIcon)
        }

        // Show file info
        fileInfo.innerHTML = `
        <div class="file-details">
            <span class="file-name">${file.name}</span>
            <span class="file-size">${this.formatFileSize(file.size)}</span>
        </div>
    `

        // Add success animation
        filePreview.style.opacity = "0"
        filePreview.style.transform = "translateY(10px)"

        setTimeout(() => {
            filePreview.style.transition = "all 0.3s ease"
            filePreview.style.opacity = "1"
            filePreview.style.transform = "translateY(0)"
        }, 100)

        this.showNotification("File berhasil dipilih!", "success")
    }

    // Remove file
    removeFile() {
        const fileInput = document.getElementById("payment_proof")
        const uploadArea = document.getElementById("fileUploadArea")
        const filePreview = document.getElementById("filePreview")

        if (fileInput) fileInput.value = ""
        this.currentFile = null

        if (uploadArea) uploadArea.style.display = "flex"
        if (filePreview) filePreview.style.display = "none"

        this.showNotification("File dihapus", "info")
    }

    // Format file size
    formatFileSize(bytes) {
        if (bytes === 0) return "0 Bytes"
        const k = 1024
        const sizes = ["Bytes", "KB", "MB", "GB"]
        const i = Math.floor(Math.log(bytes) / Math.log(k))
        return Number.parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i]
    }

    // Setup form submission
    setupFormSubmission() {
        const form = document.querySelector(".payment-form")

        if (form) {
            form.addEventListener("submit", (e) => {
                e.preventDefault()

                if (this.isSubmitting) return

                // Validate fields before showing confirmation
                let isFormValid = true
                const inputs = form.querySelectorAll("input[required], select[required], textarea[required]")
                inputs.forEach((input)=>{ if(!this.validateField(input)){ isFormValid=false } })

                // Validate required file
                const requiredFile = form.querySelector('input[type="file"][required]')
                if(requiredFile && !requiredFile.files.length){
                    this.showNotification("Bukti pembayaran harus diupload","error")
                    isFormValid=false
                }

                if(!isFormValid){
                    this.showNotification("Mohon perbaiki kesalahan pada form","error")
                    return
                }

                // All good, show confirmation modal
                this.showConfirmationPopup(form)
            })
        }
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
            this.resetFormLoading(button)
        }
    }

    // ---------- Confirmation Popup & Submit ----------
    showConfirmationPopup(form){
        const popup=document.getElementById('confirmationPopup')
        const body=document.getElementById('confirmationBody')
        if(!popup||!body){
            this.proceedSubmit(form)
            return
        }
        const d = window.orderData || {}
        const selectedMethodInput = document.querySelector('input[name="payment_method"]:checked')
        const methodName = selectedMethodInput ? selectedMethodInput.nextElementSibling.querySelector('span').textContent.trim() : '-'
        body.innerHTML = `
            <p><strong>Produk:</strong> HidroSmart Tumbler (${d.color ?? '-'})</p>
            <p><strong>Jumlah:</strong> ${d.quantity ?? 0} unit</p>
            <p><strong>Metode Pembayaran:</strong> ${methodName}</p>
            <p><strong>Total:</strong> Rp ${new Intl.NumberFormat('id-ID').format(d.total ?? 0)}</p>
            <hr style="margin:1rem 0;"/>
            <p>Apakah Anda yakin dengan pesanan ini?</p>
        `
        popup.style.display='flex'
        popup.style.opacity='0'
        setTimeout(()=>{popup.style.transition='all .3s';popup.style.opacity='1'},50)
        const yesBtn=document.getElementById('confirmYesBtn')
        const noBtn=document.getElementById('confirmNoBtn')
        const closeBtn=document.querySelector('#confirmationPopup .close-btn')
        const cleanup=()=>{
            popup.style.display='none'
            yesBtn.removeEventListener('click',yesHandler)
            noBtn.removeEventListener('click',noHandler)
            if(closeBtn) closeBtn.removeEventListener('click',noHandler)
        }
        const yesHandler=()=>{cleanup();this.proceedSubmit(form)}
        const noHandler=()=>{cleanup()}
        yesBtn.addEventListener('click',yesHandler)
        noBtn.addEventListener('click',noHandler)
        if(closeBtn) closeBtn.addEventListener('click',noHandler)
    }

    proceedSubmit(form){
        const submitBtn=form.querySelector('.btn-submit')
        this.setFormLoading(submitBtn,true)
        this.isSubmitting=true
        this.showProcessingPopup()
        setTimeout(()=>{form.submit()},1200)
    }

    // Membuat handler popup proses
    createProcessingPopupHandler() {
        this.processingPopup = document.getElementById("processingPopup")
    }

    // Tampilkan popup proses
    showProcessingPopup() {
        if (this.processingPopup) {
            this.processingPopup.style.display = "flex"
            this.processingPopup.style.opacity = "0"
            setTimeout(() => {
                this.processingPopup.style.transition = "all 0.3s ease"
                this.processingPopup.style.opacity = "1"
            }, 50)
        }
    }

    // Reset form loading state
    resetFormLoading(button) {
        if (!button) return

        button.classList.remove("form-loading")
        button.disabled = false
        const originalText = button.getAttribute("data-original-text")
        if (originalText) {
            button.innerHTML = originalText
        }
        this.isSubmitting = false
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
                case "account_name":
                    if (value.length < 3) {
                        isValid = false
                        errorMessage = "Nama rekening minimal 3 karakter"
                    }
                    break

                case "account_number":
                    if (!this.isValidAccountNumber(value)) {
                        isValid = false
                        errorMessage = "Nomor rekening tidak valid"
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
            payment_method: "Metode pembayaran",
            account_name: "Nama rekening",
            account_number: "Nomor rekening",
            payment_proof: "Bukti pembayaran",
        }
        return labels[fieldName] || fieldName
    }

    // Validate account number
    isValidAccountNumber(accountNumber) {
        const cleanNumber = accountNumber.replace(/\s|-/g, "")
        return /^\d{8,20}$/.test(cleanNumber)
    }

    // Setup payment method handling
    setupPaymentMethodHandling() {
        const paymentMethodSelect = document.getElementById("payment_method")
        const bankInfoDiv = document.getElementById("bank_info")

        if (paymentMethodSelect && bankInfoDiv) {
            paymentMethodSelect.addEventListener("change", (e) => {
                this.updateBankInfo(e.target.value)
            })

            // Initialize on page load
            this.updateBankInfo(paymentMethodSelect.value)
        }
    }

    // Update bank information based on selected payment method
    updateBankInfo(paymentMethod) {
        const bankInfoDiv = document.getElementById("bank_info")
        if (!bankInfoDiv) return

        const bankDetails = {
            bca: {
                name: "Bank Central Asia (BCA)",
                account: "1234567890",
                holder: "PT HidroSmart Indonesia",
            },
            mandiri: {
                name: "Bank Mandiri",
                account: "0987654321",
                holder: "PT HidroSmart Indonesia",
            },
            bni: {
                name: "Bank Negara Indonesia (BNI)",
                account: "1122334455",
                holder: "PT HidroSmart Indonesia",
            },
            bri: {
                name: "Bank Rakyat Indonesia (BRI)",
                account: "5544332211",
                holder: "PT HidroSmart Indonesia",
            },
        }

        if (paymentMethod && bankDetails[paymentMethod]) {
            const bank = bankDetails[paymentMethod]
            bankInfoDiv.innerHTML = `
        <div class="bank-details">
          <h4><i class="fas fa-university"></i> ${bank.name}</h4>
          <div class="account-info">
            <div class="account-row">
              <span class="label">Nomor Rekening:</span>
              <span class="value">${bank.account}</span>
              <button type="button" class="copy-btn" onclick="copyToClipboard('${bank.account}')">
                <i class="fas fa-copy"></i>
              </button>
            </div>
            <div class="account-row">
              <span class="label">Atas Nama:</span>
              <span class="value">${bank.holder}</span>
            </div>
          </div>
        </div>
      `
            bankInfoDiv.style.display = "block"
        } else {
            bankInfoDiv.style.display = "none"
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

// Global function to copy text to clipboard
function copyToClipboard(text) {
    navigator.clipboard
        .writeText(text)
        .then(() => {
            if (window.paymentPage) {
                window.paymentPage.showNotification("Nomor rekening berhasil disalin!", "success")
            }
        })
        .catch(() => {
            if (window.paymentPage) {
                window.paymentPage.showNotification("Gagal menyalin nomor rekening", "error")
            }
        })
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
    window.paymentPage = new PaymentPage()
})

// Handle page visibility changes
document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
        console.log("Payment page is now visible")
    }
})
