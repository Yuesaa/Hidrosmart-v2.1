// admin-dashboard.js - Enhanced Admin Dashboard JavaScript

class AdminDashboard {
    constructor() {
        // Mapping status to default timeline description
        this.statusDescriptions = {
            "Pesanan Dibuat": "Pesanan berhasil dibuat dan menunggu pembayaran",
            "Pembayaran Dikonfirmasi": "Pembayaran telah dikonfirmasi, pesanan akan segera diproses",
            "Sedang Dikemas": "Pesanan sedang dikemas di gudang",
            "Sedang Dalam Perjalanan": "Pesanan sedang dalam perjalanan ke alamat tujuan",
            "Diterima Customer": "Pesanan telah diterima oleh customer. Terima kasih!",
        };

        this.init()
    }

    init() {
        this.setupModals()
        this.setupForms()
        this.setupNotifications()
    }

    // Modal Management
    setupModals() {
        // Close modal when clicking outside
        window.addEventListener("click", (e) => {
            if (e.target.classList.contains("modal")) {
                this.closeModal(e.target.id)
            }
        })

        // Close modal with Escape key
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                const openModal = document.querySelector('.modal[style*="block"]')
                if (openModal) {
                    this.closeModal(openModal.id)
                }
            }
        })
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId)
        if (modal) {
            modal.style.display = "block"
            document.body.style.overflow = "hidden"

            // Focus first input
            const firstInput = modal.querySelector("input, select, textarea")
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100)
            }
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId)
        if (modal) {
            modal.style.display = "none"
            document.body.style.overflow = "auto"

            // Reset form if exists
            const form = modal.querySelector("form")
            if (form) {
                form.reset()
            }
        }
    }

    // Form Handling
    setupForms() {
        // ======== Autofill timeline description when status changes ========
        const statusSelectEl = document.getElementById("new_status");
        if (statusSelectEl) {
            statusSelectEl.addEventListener("change", () => this.autofillNotes());
        }

        // Update Status Form
        const updateStatusForm = document.getElementById("updateStatusForm")
        if (updateStatusForm) {
            updateStatusForm.addEventListener("submit", (e) => {
                e.preventDefault()
                this.updateOrderStatus()
            })
        }

        // Guarantee Note Form
        const guaranteeNoteForm = document.getElementById("guaranteeNoteForm")
        if (guaranteeNoteForm) {
            guaranteeNoteForm.addEventListener("submit", (e) => {
                e.preventDefault()
                this.addGuaranteeNoteWithModal()
            })
        }
    }

    // ================= Notifications =================
    setupNotifications() {
        // Poll every 30s for new notifications
        setInterval(() => {
            this.fetchNotifications()
        }, 30000)

        // Initial fetch
        this.fetchNotifications()
    }

    async fetchNotifications() {
        try {
            const formData = new FormData()
            formData.append("action", "get_unread_notifications")

            const response = await fetch(window.location.href, {
                method: "POST",
                body: formData,
            })
            const result = await response.json()
            if (result.success) {
                this.updateNotifUI(result.data)
            }
        } catch (err) {
            console.error("Error fetching notifications:", err)
        }
    }

    updateNotifUI(notifs) {
        const bell = document.querySelector(".notification-bell")
        const badge = bell?.querySelector(".notif-badge")
        const dropdown = document.getElementById("notifDropdown")
        if (!bell || !dropdown) return

        // Update badge
        if (notifs.length) {
            if (badge) {
                badge.textContent = notifs.length
            } else {
                const span = document.createElement("span")
                span.className = "notif-badge"
                span.textContent = notifs.length
                bell.appendChild(span)
            }
        } else if (badge) {
            badge.remove()
        }

        // Update dropdown list
        dropdown.innerHTML = notifs.length
            ? notifs
                .map(
                    (n) => `<div class="notif-item" data-id="${n.id_notifikasi}" onclick="markNotifRead(this)">
                                <p>${n.pesan}</p>
                                <small>${new Date(n.waktu).toLocaleString("id-ID")}</small>
                             </div>`,
                )
                .join("")
            : '<div class="notif-empty">Tidak ada notifikasi baru</div>'
    }

    // ================= End Notifications =================

    // API Calls
    async updateOrderStatus() {
        const formData = new FormData(document.getElementById("updateStatusForm"))
        formData.append("action", "update_order_status")

        this.showLoading(true)

        try {
            const response = await fetch(window.location.href, {
                method: "POST",
                body: formData,
            })

            const result = await response.json()

            if (result.success) {
                this.showNotification(result.message, "success")
                this.closeModal("updateStatusModal")
                setTimeout(() => location.reload(), 1000)
            } else {
                this.showNotification(result.message, "error")
            }
        } catch (error) {
            this.showNotification("Terjadi kesalahan sistem", "error")
        } finally {
            this.showLoading(false)
        }
    }

    autofillNotes() {
        const status = document.getElementById("new_status")?.value;
        const notesField = document.getElementById("admin_notes");
        if (!status || !notesField) return;
        notesField.value = this.statusDescriptions[status] || "";
    }

    async loadValidStatuses(orderId) {
        try {
            const formData = new FormData()
            formData.append("action", "get_valid_statuses")
            formData.append("order_id", orderId)

            const response = await fetch(window.location.href, {
                method: "POST",
                body: formData,
            })

            const result = await response.json()

            if (result.success) {
                const statusSelect = document.getElementById("new_status")
                statusSelect.innerHTML = ""

                result.data.forEach((status) => {
                    const option = document.createElement("option")
                    option.value = status
                    option.textContent = status
                    statusSelect.appendChild(option);
                    // if first option auto-selected, fill notes
                    if (statusSelect.options.length === 1) {
                        this.autofillNotes();
                    }
                })
            }
        } catch (error) {
            console.error("Error loading valid statuses:", error)
        }
    }

    async updateGuaranteeStatus(guaranteeId, status, notes = "") {
        const formData = new FormData()
        formData.append("action", "update_guarantee_status")
        formData.append("guarantee_id", guaranteeId)
        formData.append("status", status)
        formData.append("admin_notes", notes)

        this.showLoading(true)

        try {
            const response = await fetch(window.location.href, {
                method: "POST",
                body: formData,
            })

            const result = await response.json()

            if (result.success) {
                this.showNotification(result.message, "success")
                setTimeout(() => location.reload(), 1000)
            } else {
                this.showNotification(result.message, "error")
            }
        } catch (error) {
            this.showNotification("Terjadi kesalahan sistem", "error")
        } finally {
            this.showLoading(false)
        }
    }

    async addGuaranteeNoteWithModal() {
        const formData = new FormData(document.getElementById("guaranteeNoteForm"))
        formData.append("action", "update_guarantee_status")
        formData.append("status", "menunggu")

        this.showLoading(true)

        try {
            const response = await fetch(window.location.href, {
                method: "POST",
                body: formData,
            })

            const result = await response.json()

            if (result.success) {
                this.showNotification(result.message, "success")
                this.closeModal("guaranteeNoteModal")
                setTimeout(() => location.reload(), 1000)
            } else {
                this.showNotification(result.message, "error")
            }
        } catch (error) {
            this.showNotification("Terjadi kesalahan sistem", "error")
        } finally {
            this.showLoading(false)
        }
    }

    async markMessageAsRead(messageId) {
        const formData = new FormData()
        formData.append("action", "mark_message_read")
        formData.append("message_id", messageId)

        this.showLoading(true)

        try {
            const response = await fetch(window.location.href, {
                method: "POST",
                body: formData,
            })

            const result = await response.json()

            if (result.success) {
                this.showNotification(result.message, "success")

                // Update UI immediately without page reload
                const messageCard = document.querySelector(`[data-message-id="${messageId}"]`)
                if (messageCard) {
                    // Remove unread class and add read class
                    messageCard.classList.remove("unread")
                    messageCard.classList.add("read")

                    // Update status badge
                    const statusBadge = messageCard.querySelector(".status-badge")
                    if (statusBadge) {
                        statusBadge.className = "status-badge status-read"
                        statusBadge.textContent = "Read"
                    }

                    // Remove mark as read button
                    const actionButtons = messageCard.querySelector(".message-actions")
                    if (actionButtons) {
                        actionButtons.remove()
                    }
                }
            } else {
                this.showNotification(result.message || "Gagal menandai pesan sebagai dibaca", "error")
            }
        } catch (error) {
            console.error("Error:", error)
            this.showNotification("Terjadi kesalahan sistem", "error")
        } finally {
            this.showLoading(false)
        }
    }

    async deleteUser(userId) {
        const result = await Swal.fire({
            title: 'Hapus user?',
            text: 'Semua data terkait akan ikut terhapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        });
        if (!result.isConfirmed) {
            return;
        }

        const formData = new FormData()
        formData.append("action", "delete_user")
        formData.append("user_id", userId)

        this.showLoading(true)

        try {
            const response = await fetch(window.location.href, {
                method: "POST",
                body: formData,
            })

            const result = await response.json()

            if (result.success) {
                this.showNotification(result.message, "success")
                setTimeout(() => location.reload(), 1000)
            } else {
                this.showNotification(result.message, "error")
            }
        } catch (error) {
            this.showNotification("Terjadi kesalahan sistem", "error")
        } finally {
            this.showLoading(false)
        }
    }

    async viewUser(userId) {
        this.showLoading(true)

        try {
            const formData = new FormData()
            formData.append("action", "get_user_details")
            formData.append("user_id", userId)

            const response = await fetch(window.location.href, {
                method: "POST",
                body: formData,
            })

            const result = await response.json()

            if (result.success) {
                this.displayUserDetails(result.data)
                this.openModal("userDetailModal")
            } else {
                this.showNotification(result.message, "error")
            }
        } catch (error) {
            this.showNotification("Terjadi kesalahan sistem", "error")
        } finally {
            this.showLoading(false)
        }
    }

    displayUserDetails(data) {
        const container = document.getElementById("userDetailContent")
        const user = data.user
        const stats = data.stats

        // Avatar path fix
        const avatarPath = user.avatar ? `../logic/user/avatars/${user.avatar}` : null

        container.innerHTML = `
            <div class="user-detail-container">
                <div class="user-detail-header">
                    <div class="user-detail-avatar">
                        ${avatarPath
                ? `<img src="${avatarPath}" alt="Avatar" class="user-detail-avatar-img">`
                : '<i class="fas fa-user-circle"></i>'
            }
                    </div>
                    <div class="user-detail-info">
                        <h3>${user.name}</h3>
                        <p><i class="fas fa-envelope"></i> ${user.email}</p>
                        <p><i class="fas fa-phone"></i> ${user.phone || "Not set"}</p>
                        <p><i class="fas fa-map-marker-alt"></i> ${user.alamat || "Not set"}</p>
                    </div>
                    <div class="user-detail-stats">
                        <div>
                            <div class="stat-item-small">
                                <div class="stat-number-small">${stats.total_orders}</div>
                                <div class="stat-label-small">Orders</div>
                            </div>
                            
                            <div class="stat-item-small">
                                <div class="stat-number-small">${stats.total_reviews}</div>
                                <div class="stat-label-small">Reviews</div>
                            </div>
                        </div>
                    </div>
                        <div class="stat-item-small">
                            <div class="stat-number-small">Rp ${stats.total_spent.toLocaleString("id-ID")}</div>
                            <div class="stat-label-small">Total Spent</div>
                        </div>
                     </div>

                <div class="user-detail-tabs">
                    <button class="user-tab-btn active" data-tab="orders">Orders (${data.orders.length})</button>
                    <button class="user-tab-btn" data-tab="reviews">Reviews (${data.reviews.length})</button>
                    <button class="user-tab-btn" data-tab="guarantees">Guarantees (${data.guarantees.length})</button>
                    <button class="user-tab-btn" data-tab="contacts">Messages (${data.contacts.length})</button>
                </div>

                <div class="user-detail-content">
                    <div id="orders-tab" class="user-tab-content active">
                        ${this.generateOrdersTable(data.orders)}
                    </div>
                    <div id="reviews-tab" class="user-tab-content">
                        ${this.generateReviewsList(data.reviews)}
                    </div>
                    <div id="guarantees-tab" class="user-tab-content">
                        ${this.generateGuaranteesList(data.guarantees)}
                    </div>
                    <div id="contacts-tab" class="user-tab-content">
                        ${this.generateContactsList(data.contacts)}
                    </div>
                </div>
            </div>
        `

        // Setup tab switching
        this.setupUserDetailTabs()
    }

    generateOrdersTable(orders) {
        if (orders.length === 0) {
            return '<div class="empty-state-small"><i class="fas fa-shopping-cart"></i><p>No orders found</p></div>'
        }

        return `
            <div class="table-container-small">
                <table class="data-table-small">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${orders
                .map(
                    (order) => `
                            <tr>
                                <td><span class="order-id">#${order.id_order}</span></td>
                                <td>
                                    <div class="product-info-small">
                                        <img src="${order.product_image}" alt="HidroSmart" class="product-image-small">
                                        <span>${order.color}</span>
                                    </div>
                                </td>
                                <td><span class="badge badge-secondary">${order.kuantitas}</span></td>
                                <td><span class="price">Rp ${Number.parseInt(order.total_harga).toLocaleString("id-ID")}</span></td>
                                <td><span class="status-badge status-${order.status.toLowerCase().replace(/\s+/g, "-")}">${order.status}</span></td>
                                <td>${new Date(order.tanggal_transaksi).toLocaleDateString("id-ID")}</td>
                            </tr>
                        `,
                )
                .join("")}
                    </tbody>
                </table>
            </div>
        `
    }

    generateReviewsList(reviews) {
        if (reviews.length === 0) {
            return '<div class="empty-state-small"><i class="fas fa-star"></i><p>No reviews found</p></div>'
        }

        return `
            <div class="reviews-list-small">
                ${reviews
                .map(
                    (review) => `
                    <div class="review-item-small">
                        <div class="review-header-small">
                            <div class="review-rating-small">
                                ${Array.from(
                        { length: 5 },
                        (_, i) => `<span class="star ${i < review.rating ? "filled" : ""}"">★</span>`,
                    ).join("")}
                            </div>
                            <div class="review-date-small">${new Date(review.created_at).toLocaleDateString("id-ID")}</div>
                        </div>
                        <div class="review-content-small">
                            <p><strong>Order #${review.order_id}</strong></p>
                            <p>${review.review_text}</p>
                        </div>
                    </div>
                `,
                )
                .join("")}
            </div>
        `
    }

    generateGuaranteesList(guarantees) {
        if (guarantees.length === 0) {
            return '<div class="empty-state-small"><i class="fas fa-shield-alt"></i><p>No guarantee claims found</p></div>'
        }

        return `
            <div class="guarantees-list-small">
                ${guarantees
                .map(
                    (guarantee) => `
                    <div class="guarantee-item-small">
                        <div class="guarantee-header-small">
                            <h5>Claim #${guarantee.id_guarantee}</h5>
                            <span class="status-badge status-${guarantee.status_klaim}">${guarantee.status_klaim}</span>
                        </div>
                        <div class="guarantee-content-small">
                            <p><strong>Order:</strong> #${guarantee.id_order}</p>
                            <p><strong>Date:</strong> ${new Date(guarantee.tanggal_klaim).toLocaleDateString("id-ID")}</p>
                            <p><strong>Description:</strong> ${guarantee.deskripsi || "No description"}</p>
                            ${guarantee.catatan_admin ? `<p><strong>Admin Notes:</strong> ${guarantee.catatan_admin}</p>` : ""}
                        </div>
                    </div>
                `,
                )
                .join("")}
            </div>
        `
    }

    generateContactsList(contacts) {
        if (contacts.length === 0) {
            return '<div class="empty-state-small"><i class="fas fa-comments"></i><p>No messages found</p></div>'
        }

        return `
            <div class="contacts-list-small">
                ${contacts
                .map(
                    (contact) => `
                    <div class="contact-item-small">
                        <div class="contact-header-small">
                            <h5>${contact.subject}</h5>
                            <div class="contact-date-small">${new Date(contact.tanggal_submit).toLocaleDateString("id-ID")}</div>
                        </div>
                        <div class="contact-content-small">
                            <p>${contact.pesan}</p>
                        </div>
                    </div>
                `,
                )
                .join("")}
            </div>
        `
    }

    setupUserDetailTabs() {
        const tabButtons = document.querySelectorAll(".user-tab-btn")
        const tabContents = document.querySelectorAll(".user-tab-content")

        tabButtons.forEach((button) => {
            button.addEventListener("click", () => {
                const tabId = button.getAttribute("data-tab")

                // Remove active class from all buttons and contents
                tabButtons.forEach((btn) => btn.classList.remove("active"))
                tabContents.forEach((content) => content.classList.remove("active"))

                // Add active class to clicked button and corresponding content
                button.classList.add("active")
                document.getElementById(`${tabId}-tab`).classList.add("active")
            })
        })
    }

    // Utility Methods
    showLoading(show) {
        const overlay = document.getElementById("loadingOverlay")
        if (overlay) {
            overlay.style.display = show ? "flex" : "none"
        }
    }

    showNotification(message, type = "info") {
        const container = document.getElementById("notification-container")
        if (!container) return

        // Remove existing notifications
        container.innerHTML = ""

        const notification = document.createElement("div")
        notification.className = `notification notification-${type}`

        const iconMap = {
            success: "fa-check-circle",
            error: "fa-exclamation-circle",
            warning: "fa-exclamation-triangle",
            info: "fa-info-circle",
        }

        notification.innerHTML = `
            <i class="fas ${iconMap[type]}"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `

        container.appendChild(notification)

        // Show notification
        setTimeout(() => {
            notification.classList.add("show")
        }, 100)

        // Auto hide after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.transform = "translateX(400px)"
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove()
                    }
                }, 300)
            }
        }, 5000)
    }
}

// ===== Added Guarantee Status Modal submit handling =====
const guaranteeStatusForm = document.getElementById("guaranteeStatusForm");
if (guaranteeStatusForm) {
    guaranteeStatusForm.addEventListener("submit", (e) => {
        e.preventDefault();
        const id = document.getElementById("guarantee_status_id").value;
        const status = document.getElementById("guarantee_status_action").value;
        const notes = document.getElementById("admin_status_note").value.trim();
        if (status === "ditolak" && !notes) {
            window.adminDashboard.showNotification("Harap masukkan alasan penolakan", "error");
            return;
        }
        window.adminDashboard.updateGuaranteeStatus(id, status, notes || (status === "disetujui" ? "Klaim garansi disetujui" : ""));
        window.adminDashboard.closeModal("guaranteeStatusModal");
    });
}

// Global Functions (called from HTML)
function updateOrderStatus(orderId) {
    document.getElementById("update_order_id").value = orderId
    // Load valid statuses for this order
    window.adminDashboard.loadValidStatuses(orderId)
    window.adminDashboard.openModal("updateStatusModal")
}

function updateGuaranteeStatus(guaranteeId, status) {
    // Prepare and show modal instead of native prompt
    document.getElementById("guarantee_status_id").value = guaranteeId;
    document.getElementById("guarantee_status_action").value = status;

    const titleEl = document.getElementById("guaranteeStatusModalTitle");
    const headerEl = titleEl?.parentElement;

    if (status === "ditolak") {
        titleEl.innerHTML = '<i class="fas fa-times-circle"></i> Tolak Klaim';
        headerEl?.classList.remove("approve");
        headerEl?.classList.add("reject");
        document.getElementById("admin_status_note").placeholder = "Masukkan alasan penolakan...";
        document.getElementById("admin_status_note").required = true;
    } else {
        titleEl.innerHTML = '<i class="fas fa-check-circle"></i> Setujui Klaim';
        headerEl?.classList.remove("reject");
        headerEl?.classList.add("approve");
        document.getElementById("admin_status_note").placeholder = "Catatan admin (opsional)...";
        document.getElementById("admin_status_note").required = false;
    }

    window.adminDashboard.openModal("guaranteeStatusModal");
}




function markAsRead(messageId) {
    window.adminDashboard.markMessageAsRead(messageId)
}

function deleteUser(userId) {
    window.adminDashboard.deleteUser(userId)
}

function viewUser(userId) {
    window.adminDashboard.viewUser(userId)
}

function closeModal(modalId) {
    window.adminDashboard.closeModal(modalId)
}

async function deletePaymentProof(orderId) {
    if (!confirm("Hapus bukti pembayaran untuk order " + orderId + "? Order akan dikembalikan ke status menunggu."))
        return

    const formData = new FormData()
    formData.append("action", "delete_payment_proof")
    formData.append("order_id", orderId)
    try {
        const res = await fetch(window.location.href, { method: "POST", body: formData })
        const json = await res.json()
        if (json.success) {
            window.adminDashboard.showNotification("Bukti pembayaran berhasil dihapus", "success")
            setTimeout(() => location.reload(), 1000)
        } else {
            window.adminDashboard.showNotification(json.message || "Gagal menghapus bukti pembayaran", "error")
        }
    } catch (e) {
        console.error(e)
        window.adminDashboard.showNotification("Terjadi kesalahan sistem", "error")
    }
}

// Delete entire order
async function deleteOrder(orderId) {
    if (!confirm("Hapus order " + orderId + " secara permanen?")) return;

    const formData = new FormData();
    formData.append("action", "delete_order");
    formData.append("order_id", orderId);

    try {
        const res = await fetch(window.location.href, { method: "POST", body: formData });
        const json = await res.json();
        if (json.success) {
            window.adminDashboard.showNotification(json.message || "Order berhasil dihapus", "success");
            setTimeout(() => location.reload(), 1000);
        } else {
            window.adminDashboard.showNotification(json.message || "Gagal menghapus order", "error");
        }
    } catch (e) {
        console.error(e);
        window.adminDashboard.showNotification("Terjadi kesalahan sistem", "error");
    }
}

// Helper for notifications
function toggleNotifDropdown() {
    const dropdown = document.getElementById("notifDropdown")
    if (dropdown) {
        dropdown.classList.toggle("show")
    }
}

async function markNotifRead(el) {
    const notifId = el.dataset.id
    if (!notifId) return

    const formData = new FormData()
    formData.append("action", "mark_notification_read")
    formData.append("notif_id", notifId)

    try {
        const response = await fetch(window.location.href, {
            method: "POST",
            body: formData,
        })
        const result = await response.json()
        if (result.success) {
            el.remove()
            // decrement badge
            const badge = document.querySelector(".notif-badge")
            if (badge) {
                const num = Number.parseInt(badge.textContent) - 1
                if (num > 0) {
                    badge.textContent = num
                } else {
                    badge.remove()
                }
            }
        }
    } catch (err) {
        console.error(err)
    }
}

/**
 * Reply to a contact message via WhatsApp.
 */
async function replyContact(messageId) {
    console.log('replyContact called with id', messageId);
    const { value: text } = await Swal.fire({
        title: 'Reply to message',
        input: 'textarea',
        inputPlaceholder: 'Type your reply here...',
        showCancelButton: true,
    });
    if (!text) return;
    const formData = new FormData();
    formData.append('action', 'reply_contact');
    formData.append('message_id', messageId);
    formData.append('reply_text', text);
    try {
        const res = await fetch(window.location.href, { method: 'POST', body: formData });
        const json = await res.json();
        if (json.success) {
            Swal.fire('Sent!', 'Your reply was sent via WhatsApp.', 'success');
            markAsRead(messageId);
        } else {
            Swal.fire('Error', json.message || 'Failed to send reply.', 'error');
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'An error occurred while sending reply.', 'error');
    }
}

// Inline reply form handlers
function toggleReplyArea(messageId) {
    const area = document.getElementById(`reply-area-${messageId}`);
    if (area) {
        area.style.display = area.style.display === 'block' ? 'none' : 'block';
    }
}

async function sendInlineReply(messageId) {
    const textarea = document.getElementById(`reply-text-${messageId}`);
    const text = textarea?.value.trim();
    if (!text) return;
    // Ambil nomor telepon dari atribut data-phone pada card
    const card = document.querySelector(`.message-card[data-message-id="${messageId}"]`);
    const phone = card?.dataset.phone;
    if (!phone) {
        window.adminDashboard.showNotification('Nomor telepon tidak tersedia', 'error');
        return;
    }
    // Buka chat WhatsApp dengan teks balasan
    const url = `https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(text)}`;
    window.open(url, '_blank');
    // Tandai pesan sebagai terbaca dan sembunyikan form
    markAsRead(messageId);
    toggleReplyArea(messageId);
}

// Initialize Dashboard
document.addEventListener("DOMContentLoaded", () => {
    window.adminDashboard = new AdminDashboard()
})
