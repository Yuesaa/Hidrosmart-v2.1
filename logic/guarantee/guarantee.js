// Enhanced Guarantee Claim Page JavaScript
class GuaranteePage {
  constructor() {
    this.form = document.querySelector(".guarantee-form")
    this.submitBtn = document.querySelector(".btn-submit")
    this.isSubmitting = false
    this.isLoggedIn = !document.querySelector(".login-warning")
    this.init()
  }

  init() {
    this.setupFormValidation()
    this.setupFormSubmission()
    this.setupEnhancedFileUpload()
    this.setupAnimations()
  }

  // Setup form validation
  setupFormValidation() {
    const inputs = this.form.querySelectorAll("input, select, textarea")

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
    formGroup.classList.add("focused")
  }

  // Update field appearance
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

  // Validate field
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

    if (value && fieldName === "deskripsi") {
      if (value.length < 10) {
        isValid = false
        errorMessage = "Deskripsi minimal 10 karakter"
      } else if (value.length > 1000) {
        isValid = false
        errorMessage = "Deskripsi maksimal 1000 karakter"
      }
    }

    if (!isValid) {
      this.showFieldError(formGroup, errorMessage)
    } else if (value) {
      this.showFieldSuccess(formGroup)
    }

    return isValid
  }

  // Clear field error
  clearFieldError(field) {
    const formGroup = field.closest(".form-group")
    formGroup.classList.remove("error", "success")

    const existingError = formGroup.querySelector(".error-message")
    if (existingError) {
      existingError.remove()
    }
  }

  // Show field error
  showFieldError(formGroup, message) {
    formGroup.classList.add("error")
    formGroup.classList.remove("success")

    const errorElement = document.createElement("div")
    errorElement.className = "error-message"
    errorElement.textContent = message
    formGroup.appendChild(errorElement)
  }

  // Show field success
  showFieldSuccess(formGroup) {
    formGroup.classList.add("success")
    formGroup.classList.remove("error")
  }

  // Get field label
  getFieldLabel(fieldName) {
    const labels = {
      id_order: "Order",
      deskripsi: "Deskripsi masalah",
      bukti_gambar: "Bukti kerusakan",
    }
    return labels[fieldName] || fieldName
  }

  // Enhanced file upload functionality
  setupEnhancedFileUpload() {
    const fileInput = document.getElementById("bukti_gambar")
    const fileLabel = document.querySelector(".file-upload-label")
    const filePreview = document.querySelector(".file-preview")
    const wrapper = fileInput?.closest(".file-upload-wrapper")

    if (fileInput && wrapper) {
      // Drag and drop functionality
      ;["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
        fileLabel.addEventListener(eventName, this.preventDefaults, false)
      })
      ;["dragenter", "dragover"].forEach((eventName) => {
        fileLabel.addEventListener(
          eventName,
          () => {
            fileLabel.classList.add("drag-over")
          },
          false,
        )
      })
      ;["dragleave", "drop"].forEach((eventName) => {
        fileLabel.addEventListener(
          eventName,
          () => {
            fileLabel.classList.remove("drag-over")
          },
          false,
        )
      })

      fileLabel.addEventListener(
        "drop",
        (e) => {
          const files = e.dataTransfer.files
          if (files.length > 0) {
            fileInput.files = files
            this.handleEnhancedFileUpload(fileInput)
          }
        },
        false,
      )

      fileInput.addEventListener("change", () => {
        this.handleEnhancedFileUpload(fileInput)
      })
    }
  }

  // Prevent default drag behaviors
  preventDefaults(e) {
    e.preventDefault()
    e.stopPropagation()
  }

  // Enhanced file upload handling
  handleEnhancedFileUpload(input) {
    const file = input.files[0]
    const wrapper = input.closest(".file-upload-wrapper")
    const preview = wrapper.parentNode.querySelector(".file-preview")
    const label = wrapper.querySelector(".file-upload-label")

    if (!file) {
      this.resetFileUpload(wrapper, preview, label)
      return
    }

    // Validate file
    const validTypes = ["image/jpeg", "image/jpg", "image/png", "application/pdf"]
    const maxSize = 5 * 1024 * 1024 // 5MB

    if (!validTypes.includes(file.type)) {
      this.showNotification("Format file tidak didukung. Gunakan JPG, PNG, atau PDF", "error")
      input.value = ""
      return
    }

    if (file.size > maxSize) {
      this.showNotification("Ukuran file terlalu besar. Maksimal 5MB", "error")
      input.value = ""
      return
    }

    // Show upload animation
    this.showUploadAnimation(label, file, preview)
  }

  // Show upload animation
  showUploadAnimation(label, file, preview) {
    // Update label with success state
    label.style.borderColor = "#10b981"
    label.style.background = "#ecfdf5"
    label.style.transform = "scale(1.02)"

    const icon = label.querySelector("i")
    const span = label.querySelector("span")

    if (icon) {
      icon.className = "fas fa-check-circle"
      icon.style.color = "#10b981"
    }
    if (span) {
      span.textContent = "File berhasil dipilih"
    }

    setTimeout(() => {
      label.style.transform = "scale(1)"
    }, 200)

    // Show single preview with animation
    this.showEnhancedFilePreview(file, preview)
  }

  // Show enhanced file preview (single preview only)
  showEnhancedFilePreview(file, preview) {
    preview.innerHTML = `
      <div class="file-preview-item" style="opacity: 0; transform: translateY(10px);">
        <div class="file-info">
          <i class="fas ${file.type.includes("pdf") ? "fa-file-pdf" : "fa-file-image"}"></i>
          <div class="file-details">
            <span class="file-name">${file.name}</span>
            <small class="file-size">(${this.formatFileSize(file.size)})</small>
          </div>
        </div>
        <button type="button" class="file-close-btn" onclick="window.guaranteePage.removeFile(this)">
          <i class="fas fa-times"></i>
        </button>
      </div>
    `

    preview.style.display = "block"

    // Animate in
    setTimeout(() => {
      const item = preview.querySelector(".file-preview-item")
      item.style.transition = "all 0.3s ease"
      item.style.opacity = "1"
      item.style.transform = "translateY(0)"
    }, 50)

    // Show single image preview if it's an image
    if (file.type.startsWith("image/")) {
      const reader = new FileReader()
      reader.onload = (e) => {
        const imagePreview = document.createElement("div")
        imagePreview.className = "image-preview"
        imagePreview.style.opacity = "0"
        imagePreview.innerHTML = `
          <img src="${e.target.result}" alt="Preview" class="preview-image">
        `
        preview.querySelector(".file-preview-item").appendChild(imagePreview)

        setTimeout(() => {
          imagePreview.style.transition = "opacity 0.3s ease"
          imagePreview.style.opacity = "1"
        }, 100)
      }
      reader.readAsDataURL(file)
    }
  }

  // Remove file with animation
  removeFile(button) {
    const preview = button.closest(".file-preview")
    const fileInput = preview.closest(".form-group").querySelector(".file-input")
    const wrapper = preview.closest(".form-group").querySelector(".file-upload-wrapper")
    const label = wrapper.querySelector(".file-upload-label")

    // Animate out
    const item = button.closest(".file-preview-item")
    item.style.transition = "all 0.3s ease"
    item.style.opacity = "0"
    item.style.transform = "translateY(-10px)"

    setTimeout(() => {
      // Clear file input
      fileInput.value = ""

      // Reset upload area
      this.resetFileUpload(wrapper, preview, label)
    }, 300)
  }

  // Reset file upload area
  resetFileUpload(wrapper, preview, label) {
    // Reset label
    const icon = label.querySelector("i")
    const span = label.querySelector("span")

    if (icon) {
      icon.className = "fas fa-cloud-upload-alt"
      icon.style.color = "#64748b"
    }
    if (span) {
      span.textContent = "Pilih file atau drag & drop"
    }

    label.style.borderColor = "#d1d5db"
    label.style.background = "white"
    label.style.transform = "scale(1)"

    // Hide preview
    preview.style.display = "none"
    preview.innerHTML = ""
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
    this.form.addEventListener("submit", (e) => {
      e.preventDefault()
      this.handleFormSubmission()
    })
  }

  // Handle form submission
  async handleFormSubmission() {
    if (this.isSubmitting) return

    // 1. Check if user is logged in
    if (!this.isLoggedIn) {
      this.showNotification("Anda harus login terlebih dahulu untuk mengajukan klaim garansi.", "warning")
      this.shakeForm()
      return
    }

    // Validate all fields
    const inputs = this.form.querySelectorAll("input[required], select[required], textarea[required]")
    let isFormValid = true

    inputs.forEach((input) => {
      if (!this.validateField(input)) {
        isFormValid = false
      }
    })

    if (!isFormValid) {
      this.showNotification("Mohon lengkapi semua field yang diperlukan.", "error")
      this.shakeForm()
      return
    }

    // Set submitting state
    this.isSubmitting = true
    this.setFormLoading(true)

    try {
      // Prepare form data
      const formData = new FormData(this.form)
      formData.append("submit_guarantee", "1")

      // Submit to controller
      const response = await fetch("../logic/guarantee/guarantee-controller.php", {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      })

      const result = await response.json()

      if (result.success) {
        this.showSuccessModal(result.claim_id, result.order_id)
        this.resetForm()
      } else {
        // Handle different error types with specific notifications
        switch (result.type) {
          case "login_required":
            this.showNotification("Anda harus login terlebih dahulu untuk mengajukan klaim garansi.", "warning")
            break
          case "no_order":
            this.showNotification(
              "Anda harus melakukan pemesanan terlebih dahulu sebelum dapat mengajukan klaim garansi.",
              "info",
            )
            break
          case "order_not_completed":
            this.showNotification(
              'Klaim garansi hanya dapat diajukan setelah pesanan Anda berstatus "Diterima Customer". Silakan tunggu hingga pesanan selesai.',
              "info",
            )
            break
          case "profile_incomplete":
            this.showNotification(
              'Anda harus melengkapi nomor handphone dan alamat di dashboard profil terlebih dahulu. <a href="user.php" style="color: #60a5fa; text-decoration: underline;">Klik di sini</a> untuk melengkapi profil.',
              "warning",
            )
            break
          default:
            this.showNotification(result.message, "error")
            if (result.errors && result.errors.length > 0) {
              result.errors.forEach((error, index) => {
                setTimeout(
                  () => {
                    this.showNotification(error, "error")
                  },
                  (index + 1) * 1000,
                )
              })
            }
            break
        }
        this.shakeForm()
      }
    } catch (error) {
      console.error("Form submission error:", error)
      this.showNotification("Terjadi kesalahan jaringan. Silakan coba lagi.", "error")
    } finally {
      this.isSubmitting = false
      this.setFormLoading(false)
    }
  }

  // Show success modal
  showSuccessModal(claimId, orderId) {
    const modal = document.createElement("div")
    modal.className = "success-modal-overlay"
    modal.innerHTML = `
            <div class="success-modal">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Klaim Garansi Berhasil!</h3>
                <div class="claim-details">
                    <p><strong>ID Klaim:</strong> ${claimId}</p>
                    <p><strong>Order ID:</strong> ${orderId}</p>
                </div>
                <p>Kami akan memproses klaim Anda dalam 1×24 jam dan mengirimkan update melalui WhatsApp/SMS.</p>
                <div class="modal-actions">
                    <button class="btn-modal-primary" onclick="this.closest('.success-modal-overlay').remove()">
                        Tutup
                    </button>
                    <button class="btn-modal-secondary" onclick="window.copyClaimId('${claimId}')">
                        Salin ID Klaim
                    </button>
                </div>
            </div>
        `

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
        `

    document.body.appendChild(modal)

    setTimeout(() => {
      modal.style.opacity = "1"
    }, 10)

    // Add copy function
    window.copyClaimId = (claimId) => {
      navigator.clipboard.writeText(claimId).then(() => {
        this.showNotification("ID Klaim berhasil disalin!", "success")
      })
    }
  }

  // Reset form
  resetForm() {
    this.form.reset()
    this.clearAllFieldStates()
    this.resetFieldAppearances()

    // Reset file upload
    const fileLabel = document.querySelector(".file-upload-label")
    const filePreview = document.querySelector(".file-preview")

    if (fileLabel) {
      fileLabel.innerHTML = `
                <i class="fas fa-cloud-upload-alt"></i>
                <span>Pilih file atau drag & drop</span>
                <small>JPG, PNG, PDF (Max 5MB)</small>
            `
      fileLabel.style.background = ""
      fileLabel.style.borderColor = ""
    }

    if (filePreview) {
      filePreview.style.display = "none"
      filePreview.innerHTML = ""
    }
  }

  // Set form loading state
  setFormLoading(loading) {
    if (loading) {
      this.form.classList.add("form-loading")
      this.submitBtn.disabled = true
      this.submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...'
    } else {
      this.form.classList.remove("form-loading")
      this.submitBtn.disabled = false
      this.submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Klaim Garansi'
    }
  }

  // Clear all field states
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

  // Reset field appearances
  resetFieldAppearances() {
    const inputs = this.form.querySelectorAll("input, select, textarea")
    inputs.forEach((input) => {
      input.style.background = "#f9fafb"
      input.style.color = "#6b7280"
    })
  }

  // Shake form animation
  shakeForm() {
    this.form.classList.add("form-shake")
    setTimeout(() => {
      this.form.classList.remove("form-shake")
    }, 500)
  }

  // Setup simple animations
  setupAnimations() {
    const observerOptions = {
      threshold: 0.1,
      rootMargin: "0px 0px -50px 0px",
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("visible")
        }
      })
    }, observerOptions)

    const animatedElements = document.querySelectorAll(`
            .form-section,
            .info-card,
            .support-card,
            .info-item
        `)

    animatedElements.forEach((el, index) => {
      el.classList.add("animate-element")
      el.style.transitionDelay = `${index * 0.1}s`
      observer.observe(el)
    })

    // Add animation styles
    this.addAnimationStyles()
  }

  // Add animation styles
  addAnimationStyles() {
    const style = document.createElement("style")
    style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            
            .form-shake {
                animation: shake 0.5s ease-in-out;
            }
            
            .animate-element {
                opacity: 0;
                transform: translateY(30px);
                transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .animate-element.visible {
                opacity: 1;
                transform: translateY(0);
            }
            
            .login-warning {
                background: #fef3c7;
                color: #92400e;
                padding: 1rem;
                border-radius: 8px;
                margin-top: 1rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .claim-details {
                background: #f8fafc;
                padding: 1rem;
                border-radius: 8px;
                margin: 1rem 0;
                text-align: left;
            }
            
            .claim-details p {
                margin: 0.5rem 0;
                color: #1e293b;
            }
            
            /* Enhanced File Upload Styles */
            .file-upload-wrapper {
                position: relative;
                margin-bottom: 1rem;
            }
            
            .file-upload-label {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 2rem;
                border: 2px dashed #d1d5db;
                border-radius: 12px;
                background: white;
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                min-height: 120px;
            }
            
            .file-upload-label:hover {
                border-color: #3b82f6;
                background: #f0f9ff;
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(59, 130, 246, 0.1);
            }
            
            .file-upload-label i {
                font-size: 2rem;
                color: #64748b;
                margin-bottom: 0.5rem;
                transition: all 0.3s ease;
            }
            
            .file-upload-label span {
                font-weight: 500;
                color: #374151;
                margin-bottom: 0.25rem;
            }
            
            .file-upload-label small {
                color: #6b7280;
                font-size: 0.8rem;
            }
            
            .file-input {
                position: absolute;
                opacity: 0;
                width: 100%;
                height: 100%;
                cursor: pointer;
            }
            
            .drag-over {
                border-color: #3b82f6 !important;
                background: #f0f9ff !important;
                transform: scale(1.02) !important;
                box-shadow: 0 8px 25px rgba(59, 130, 246, 0.2) !important;
            }
            
            /* Enhanced File Preview Styles */
            .file-preview-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 1rem;
                margin-top: 0.5rem;
                animation: slideIn 0.3s ease;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }
            
            .file-info {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                flex: 1;
            }
            
            .file-info i {
                font-size: 1.5rem;
                color: #3b82f6;
            }
            
            .file-details {
                display: flex;
                flex-direction: column;
            }
            
            .file-name {
                font-weight: 500;
                color: #1e293b;
                margin-bottom: 0.25rem;
                word-break: break-word;
            }
            
            .file-size {
                color: #64748b;
                font-size: 0.8rem;
            }
            
            .file-close-btn {
                background: #ef4444;
                color: white;
                border: none;
                border-radius: 50%;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.3s ease;
                flex-shrink: 0;
            }
            
            .file-close-btn:hover {
                background: #dc2626;
                transform: scale(1.1);
            }
            
            .image-preview {
                margin-top: 0.75rem;
                text-align: center;
                width: 50%;
            }
            
            .preview-image {
                max-width: 150px;
                max-height: 150px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                transition: transform 0.3s ease;
            }
            
            .preview-image:hover {
                transform: scale(1.05);
            }
            
            /* Select and Textarea Styles */
            select, textarea {
                padding: 0.875rem 1rem;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                font-size: 0.95rem;
                transition: all 0.3s ease;
                background: #f9fafb;
                color: #6b7280;
                font-family: 'Inter', sans-serif;
            }
            
            select:focus, textarea:focus {
                outline: none;
                border-color: #3b82f6;
                background: white;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
                color: #1f2937;
            }
            
            select:disabled {
                background: #f3f4f6;
                color: #9ca3af;
                cursor: not-allowed;
            }
            
            textarea {
                resize: vertical;
                min-height: 100px;
            }
            
            textarea::placeholder {
                color: #9ca3af;
            }

            .notification-content {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            .notification-content div {
                flex: 1;
            }

            .notification-content a {
                color: inherit;
                text-decoration: underline;
            }
            
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `

    document.head.appendChild(style)
  }

  // Show notification
  showNotification(message, type = "info") {
    document.querySelectorAll(".notification").forEach((n) => n.remove())

    const notification = document.createElement("div")
    notification.className = `notification notification-${type}`

    const iconMap = {
      success: "fa-check-circle",
      error: "fa-exclamation-circle",
      info: "fa-info-circle",
      warning: "fa-exclamation-triangle",
    }

    const colorMap = {
      success: "#10b981",
      error: "#ef4444",
      info: "#3b82f6",
      warning: "#f59e0b",
    }

    notification.innerHTML = `
    <div class="notification-content">
        <i class="fas ${iconMap[type]}"></i>
        <div>${message}</div>
        <button class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    </div>
`

    notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: ${colorMap[type]};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 10000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            max-width: 400px;
            font-family: 'Inter', sans-serif;
        `

    document.body.appendChild(notification)

    setTimeout(() => {
      notification.style.transform = "translateX(0)"
    }, 100)

    notification.querySelector(".notification-close").addEventListener("click", () => {
      notification.style.transform = "translateX(400px)"
      setTimeout(() => notification.remove(), 300)
    })

    setTimeout(() => {
      notification.style.transform = "translateX(400px)"
      setTimeout(() => notification.remove(), 300)
    }, 5000)
  }
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  window.guaranteePage = new GuaranteePage()
})

// Add modal styles
const modalStyles = document.createElement("style")
modalStyles.textContent = `
    .success-modal {
        background: white;
        border-radius: 20px;
        padding: 3rem;
        text-align: center;
        max-width: 500px;
        width: 90%;
        transform: scale(0.9);
        transition: transform 0.3s ease;
    }
    
    .success-modal-overlay .success-modal {
        animation: modalSlideIn 0.3s ease;
    }
    
    .success-icon {
        width: 80px;
        height: 80px;
        background: #10b981;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
        font-size: 2rem;
        color: white;
    }
    
    .success-modal h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 1rem;
    }
    
    .success-modal p {
        color: #64748b;
        margin-bottom: 1rem;
        line-height: 1.6;
    }
    
    .modal-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
    }
    
    .btn-modal-primary,
    .btn-modal-secondary {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
    }
    
    .btn-modal-primary {
        background: #3b82f6;
        color: white;
    }
    
    .btn-modal-primary:hover {
        background: #1d4ed8;
    }
    
    .btn-modal-secondary {
        background: #f1f5f9;
        color: #64748b;
    }
    
    .btn-modal-secondary:hover {
        background: #e2e8f0;
    }
    
    @keyframes modalSlideIn {
        from { transform: scale(0.9) translateY(20px); }
        to { transform: scale(1) translateY(0); }
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        margin-left: auto;
        opacity: 0.8;
        transition: opacity 0.2s;
    }
    
    .notification-close:hover {
        opacity: 1;
    }
`
document.head.appendChild(modalStyles)
