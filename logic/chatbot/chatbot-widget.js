// logic/chatbot/chatbot-widget.js
// HidroSmart Chatbot Widget with user authentication
;(() => {
  // Do not load on admin pages
  if (window.location.pathname.toLowerCase().includes("admin")) return

  // Check if user is logged in by looking for session indicators
  function isUserLoggedIn() {
    // Check for common login indicators
    const loginIndicators = [
      document.querySelector("[data-user-id]"),
      document.querySelector(".user-profile"),
      document.querySelector(".user-avatar"),
      document.querySelector("#user-menu"),
      document.querySelector(".logout-btn"),
      document.querySelector('[href*="logout"]'),
      document.querySelector(".user-name"),
      document.querySelector(".dashboard-user"),
    ]

    // Check if any login indicator exists
    const hasLoginIndicator = loginIndicators.some((element) => element !== null)

    // Also check for logout links or user-specific content
    const hasUserContent =
      document.body.innerHTML.includes("logout") ||
      document.body.innerHTML.includes("profile") ||
      document.body.innerHTML.includes("dashboard")

    // Check if we're on a user-specific page
    const userPages = ["user.php", "profile.php", "dashboard.php", "order.php"]
    const isUserPage = userPages.some((page) => window.location.pathname.includes(page))

    return hasLoginIndicator || (hasUserContent && isUserPage)
  }

  // Wait for DOM to be fully loaded before checking login status
  function initializeChatbot() {
    if (!isUserLoggedIn()) {
      console.log("HidroSmart Chatbot: User not logged in, chatbot disabled")
      return
    }

    console.log("HidroSmart Chatbot: User logged in, initializing chatbot")
    createChatbot()
  }

  function createChatbot() {
    // Determine current username from meta tag or DOM element
    const metaUser = document.querySelector('meta[name="hidro-username"]')?.content;
    const greetingText = document.querySelector('.user-greeting')?.textContent;
    const uiName = greetingText ? greetingText.replace(/^Halo,\s*/i, '').trim() : null;
    const CURRENT_USER = metaUser || uiName || 'pengguna';
    // Chat history storage key is now namespaced per user
    const STORAGE_KEY = `hidrosmart_chat_history_${CURRENT_USER}`;
    // Reset chat history if user changes (login/logout)
    const lastUser = localStorage.getItem('hidrosmart_chat_last_user');
    if (lastUser && lastUser !== CURRENT_USER) {
      // Remove all chat histories for previous users
      Object.keys(localStorage).forEach((k) => {
        if (k.startsWith('hidrosmart_chat_history_')) localStorage.removeItem(k);
      });
    }
    // Jika flag logout ter-set, bersihkan seluruh history & hapus flag
    if (localStorage.getItem('hidrosmart_logged_out') === '1') {
      Object.keys(localStorage).forEach((k)=>{
        if (k.startsWith('hidrosmart_chat_history_')) localStorage.removeItem(k)
      })
      localStorage.removeItem('hidrosmart_logged_out')
    }
    localStorage.setItem('hidrosmart_chat_last_user', CURRENT_USER);

    // Load chat history from localStorage
    function loadChatHistory() {
      try {
        const history = localStorage.getItem(STORAGE_KEY)
        return history ? JSON.parse(history) : []
      } catch (e) {
        return []
      }
    }

    // Save chat history to localStorage
    function saveChatHistory(messages) {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(messages))
      } catch (e) {
        console.warn("Could not save chat history")
      }
    }

    // Create toggle button
    const btn = document.createElement("button")
    btn.id = "hidro-chat-toggle"
    btn.innerHTML = '<i class="fas fa-comment"></i>'
    btn.title = "Chat dengan HidroSmart Assistant"
    document.body.appendChild(btn)

    // Create chat container
    const container = document.createElement("div")
    container.id = "hidro-chat-container"
    container.innerHTML = `
      <div class="chat-header">
        <div class="header-content">
          <i class="fas fa-robot"></i>
          <span class="chat-title">HidroSmart Assistant</span>
        </div>
        <button id="hidro-chat-close" title="Tutup chat">&times;</button>
      </div>
      <div class="chat-messages" id="hidro-chat-messages">
        <div class="welcome-message">
          <div class="msg bot">
          </div>
        </div>
      </div>
      <div class="chat-input-area">
        <input type="text" id="hidro-chat-input" placeholder="Ketik pesan Anda..." maxlength="500" />
        <button id="hidro-chat-send" title="Kirim pesan">
          <i class="fas fa-paper-plane"></i>
        </button>
      </div>
      <div class="chat-status" id="hidro-chat-status"></div>`

    document.body.appendChild(container)

    // Enhanced styles
    const style = document.createElement("style")
    style.textContent = `
      #hidro-chat-toggle {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: linear-gradient(135deg, #1976d2 0%, #26c6da 100%);
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        font-size: 24px;
        cursor: pointer;
        z-index: 9999;
        box-shadow: 0 8px 25px rgba(25, 118, 210, 0.3);
        display: flex;
        justify-content: center;
        align-items: center;
        transition: all 0.3s ease;
        animation: pulse 2s infinite;
      }
      
      #hidro-chat-toggle:hover {
        transform: scale(1.1);
        box-shadow: 0 12px 35px rgba(25, 118, 210, 0.4);
      }
      
      @keyframes pulse {
        0% { box-shadow: 0 8px 25px rgba(25, 118, 210, 0.3); }
        50% { box-shadow: 0 8px 25px rgba(25, 118, 210, 0.5), 0 0 0 10px rgba(25, 118, 210, 0.1); }
        100% { box-shadow: 0 8px 25px rgba(25, 118, 210, 0.3); }
      }
      
      #hidro-chat-container {
        position: fixed;
        bottom: 95px;
        right: 20px;
        width: 380px;
        max-width: calc(100vw - 40px);
        height: 550px;
        max-height: calc(100vh - 140px);
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        display: none;
        flex-direction: column;
        overflow: hidden;
        z-index: 9999;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        border: 1px solid rgba(0, 0, 0, 0.1);
      }
      
      #hidro-chat-container.open { 
        display: flex; 
        animation: slideUp 0.3s ease-out;
      }
      
      @keyframes slideUp {
        from { 
          opacity: 0; 
          transform: translateY(20px) scale(0.95); 
        }
        to { 
          opacity: 1; 
          transform: translateY(0) scale(1); 
        }
      }
      
      .chat-header {
        background: linear-gradient(135deg, #1976d2 0%, #26c6da 100%);
        color: #fff;
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      }
      
      .header-content {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        font-size: 16px;
      }
      
      .header-content i {
        font-size: 20px;
      }
      
      #hidro-chat-close {
        background: none;
        border: none;
        color: #fff;
        font-size: 24px;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: background-color 0.2s;
      }
      
      #hidro-chat-close:hover {
        background-color: rgba(255, 255, 255, 0.2);
      }
      
      .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        background: linear-gradient(to bottom, #f8fafc 0%, #ffffff 100%);
        scroll-behavior: smooth;
      }
      
      .chat-messages::-webkit-scrollbar {
        width: 6px;
      }
      
      .chat-messages::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
      }
      
      .chat-messages::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
      }
      
      .chat-messages::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
      }
      
      .welcome-message {
        margin-bottom: 16px;
      }
      
      .chat-input-area {
        display: flex;
        gap: 12px;
        padding: 16px 20px;
        border-top: 1px solid #e5e7eb;
        background: #fff;
      }
      
      .chat-input-area input {
        flex: 1;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 25px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s;
        font-family: inherit;
      }
      
      .chat-input-area input:focus {
        border-color: #1976d2;
      }
      
      .chat-input-area input:disabled {
        background-color: #f3f4f6;
        cursor: not-allowed;
      }
      
      .chat-input-area button {
        background: linear-gradient(135deg, #1976d2 0%, #26c6da 100%);
        color: #fff;
        border: none;
        padding: 12px 16px;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 48px;
        height: 48px;
      }
      
      .chat-input-area button:hover:not(:disabled) {
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(25, 118, 210, 0.3);
      }
      
      .chat-input-area button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
      }
      
      .msg {
        margin-bottom: 16px;
        display: flex;
        animation: fadeIn 0.3s ease-out;
      }
      
      @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
      }
      
      .msg.user { justify-content: flex-end; }
      .msg.bot { justify-content: flex-start; }
      
      .msg .bubble {
        max-width: 280px;
        padding: 12px 16px;
        border-radius: 18px;
        font-size: 14px;
        line-height: 1.5;
        white-space: pre-wrap;
        word-wrap: break-word;
        position: relative;
      }
      
      .msg.user .bubble { 
        background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
        color: #fff; 
        border-bottom-right-radius: 6px;
        box-shadow: 0 2px 8px rgba(25, 118, 210, 0.2);
      }
      
      .msg.bot .bubble { 
        background: #f8f9fa; 
        color: #2d3748; 
        border-bottom-left-radius: 6px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      }
      
      .msg.bot .bubble i.fas.fa-robot {
        color: #1976d2;
        margin-right: 6px;
      }
      
      .chat-status {
        padding: 8px 20px;
        font-size: 12px;
        color: #6b7280;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        text-align: center;
      }
      
      .typing-indicator {
        display: flex;
        align-items: center;
        gap: 4px;
        color: #6b7280;
        font-style: italic;
      }
      
      .typing-dots {
        display: flex;
        gap: 2px;
      }
      
      .typing-dots span {
        width: 4px;
        height: 4px;
        background: #6b7280;
        border-radius: 50%;
        animation: typing 1.4s infinite ease-in-out;
      }
      
      .typing-dots span:nth-child(1) { animation-delay: -0.32s; }
      .typing-dots span:nth-child(2) { animation-delay: -0.16s; }
      
      @keyframes typing {
        0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
        40% { transform: scale(1); opacity: 1; }
      }
      
      @media (max-width: 480px) {
        #hidro-chat-container {
          width: calc(100vw - 20px);
          right: 10px;
          bottom: 85px;
          height: calc(100vh - 120px);
        }
        
        #hidro-chat-toggle {
          bottom: 20px;
          right: 20px;
          width: 56px;
          height: 56px;
          font-size: 22px;
        }
      }
    `
    document.head.appendChild(style)

    // Chat state
    const chatHistory = loadChatHistory()
    let isTyping = false

    // Toggle functions
    function openChat() {
      container.classList.add("open")
      setTimeout(() => {
        const input = document.getElementById("hidro-chat-input")
        if (input) input.focus()
      }, 100)
    }

    function closeChat() {
      container.classList.remove("open")
    }

    // Event listeners
    btn.addEventListener("click", () => {
      if (container.classList.contains("open")) {
        closeChat()
      } else {
        openChat()
        loadHistoryToUI()
      }
    })

    container.querySelector("#hidro-chat-close").addEventListener("click", closeChat)

    // Message helpers
    const addMessage = (text, sender = "bot", save = true) => {
      const wrap = document.createElement("div")
      wrap.className = `msg ${sender}`

      if (sender === "bot") {
        wrap.innerHTML = `<div class="bubble"><i class="fas fa-robot"></i> ${text}</div>`
      } else {
        wrap.innerHTML = `<div class="bubble">${text}</div>`
      }

      const msgBox = document.getElementById("hidro-chat-messages")
      msgBox.appendChild(wrap)
      msgBox.scrollTop = msgBox.scrollHeight

      if (save) {
        chatHistory.push({ text, sender, timestamp: Date.now() })
        saveChatHistory(chatHistory)
      }
    }

    // Load chat history to UI
    function loadHistoryToUI() {
      const msgBox = document.getElementById("hidro-chat-messages")
      // Clear existing messages except welcome message
      const welcomeMsg = msgBox.querySelector(".welcome-message")
      msgBox.innerHTML = ""
      if (welcomeMsg) msgBox.appendChild(welcomeMsg)

      // Load history
      chatHistory.forEach((msg) => {
        addMessage(msg.text, msg.sender, false)
      })
    }

    // Show typing indicator
    function showTyping() {
      if (isTyping) return
      isTyping = true

      const wrap = document.createElement("div")
      wrap.className = "msg bot typing-msg"
      wrap.innerHTML = `
        <div class="bubble typing-indicator">
          <i class="fas fa-robot"></i>
          <div class="typing-dots">
            <span></span>
            <span></span>
            <span></span>
          </div>
        </div>`

      const msgBox = document.getElementById("hidro-chat-messages")
      msgBox.appendChild(wrap)
      msgBox.scrollTop = msgBox.scrollHeight
    }

    // Hide typing indicator
    function hideTyping() {
      isTyping = false
      const typingMsg = document.querySelector(".typing-msg")
      if (typingMsg) typingMsg.remove()
    }

    // Send message function
    async function sendMessage() {
      const input = document.getElementById("hidro-chat-input")
      const sendBtn = document.getElementById("hidro-chat-send")
      const text = input.value.trim()

      if (!text || isTyping) return

      // Disable input
      input.disabled = true
      sendBtn.disabled = true
      input.value = ""

      // Add user message
      addMessage(text, "user")

      // Show typing
      showTyping()

      try {
        const response = await fetch("../logic/chatbot/send.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ message: text, username: CURRENT_USER }),
        })

        const data = await response.json()
        hideTyping()

        if (response.status === 401) {
          addMessage("Silakan login terlebih dahulu untuk menggunakan chatbot HidroSmart.", "bot")
          return
        }

        const reply =
          data.reply ||
          "Mohon maaf, saya tidak dapat memproses permintaan Anda saat ini. Silakan coba lagi atau hubungi customer service kami di +62 812-3456-7890"
        addMessage(reply, "bot")
      } catch (error) {
        hideTyping()
        console.error("Chat error:", error)
        addMessage(
          "Mohon maaf, terjadi masalah koneksi. Silakan periksa internet Anda dan coba lagi. Untuk bantuan langsung, hubungi +62 812-3456-7890",
          "bot",
        )
      } finally {
        // Re-enable input
        input.disabled = false
        sendBtn.disabled = false
        input.focus()
      }
    }

    // Event listeners for sending messages
    document.getElementById("hidro-chat-send").addEventListener("click", sendMessage)
    document.getElementById("hidro-chat-input").addEventListener("keydown", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault()
        sendMessage()
      }
    })

    // Initialize chat history on load
    setTimeout(() => {
      if (chatHistory.length === 0) {
        // Add initial welcome message to history
        chatHistory.push({
          text: "Halo! Saya asisten virtual HidroSmart. Ada yang bisa saya bantu mengenai HidroSmart Tumbler? Jika ada pertanyaan atau informasi yang ingin Anda ketahui, silakan beritahu saya! 😊",
          sender: "bot",
          timestamp: Date.now(),
        })
        saveChatHistory(chatHistory)
      }
    }, 100)
  }

  // Initialize chatbot when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeChatbot)
  } else {
    // DOM is already loaded
    setTimeout(initializeChatbot, 500) // Small delay to ensure all elements are rendered
  }
})()
