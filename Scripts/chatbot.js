// Enhanced AI Chatbot Functionality with improved error handling and features
class Chatbot {
    constructor() {
        this.modal = null;
        this.chatIcon = null;
        this.closeBtn = null;
        this.messages = null;
        this.input = null;
        this.sendBtn = null;
        this.typingIndicator = null;
        this.isOpen = false;
        this.isTyping = false;
        this.messageHistory = [];
        this.maxHistoryLength = 50;
        
        // Check if utils are available
        if (!window.Utils) {
            console.warn('Utils not loaded. Chatbot may have limited functionality.');
        }

        this.init();
    }

    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.setupElements();
            this.setupEventListeners();
            this.loadMessageHistory();
            this.setupKeyboardShortcuts();
            console.log('Enhanced Chatbot initialized successfully');
        });
    }

    setupElements() {
        this.modal = document.getElementById('chatbotModal') || document.querySelector('#chatbotModal');
        this.chatIcon = document.querySelector('.ai-chatbot-icon .chatbot-link') || document.querySelector('[onclick*="chatbot.open"]');
        this.closeBtn = document.getElementById('closeChatbotBtn') || document.querySelector('#closeChatbotBtn');
        this.messages = document.getElementById('chatMessages') || document.querySelector('#chatMessages');
        this.input = document.getElementById('chatInput') || document.querySelector('#chatInput');
        this.sendBtn = document.getElementById('sendChatBtn') || document.querySelector('#sendChatBtn');

        // Add ID if missing
        if (this.chatIcon && !this.chatIcon.id) {
            this.chatIcon.id = 'openChatbotBtn';
        }

        // Create typing indicator if it doesn't exist
        if (this.messages && !document.getElementById('typingIndicator')) {
            this.createTypingIndicator();
        }
        
        // Create send button if it doesn't exist
        if (this.input && !this.sendBtn) {
            this.createSendButton();
        }
    }

    createTypingIndicator() {
        if (!this.messages) {
            console.error('Messages container not found for typing indicator');
            return;
        }
        const indicator = document.createElement('div');
        indicator.id = 'typingIndicator';
        indicator.className = 'typing-indicator';
        indicator.innerHTML = `
            <div class="typing-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <span class="typing-text">AI is typing...</span>
        `;
        
        this.messages.appendChild(indicator);
    }

    createSendButton() {
        const sendBtn = document.createElement('button');
        sendBtn.id = 'sendChatBtn';
        sendBtn.className = 'send-btn';
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        sendBtn.setAttribute('aria-label', 'Send message');
        
        if (this.input && this.input.parentElement) {
            const inputContainer = this.input.parentElement;
            inputContainer.appendChild(sendBtn);
        }
        
        this.sendBtn = sendBtn;
    }

    setupEventListeners() {
        if (!this.chatIcon || !this.modal) {
            console.error('Chatbot elements not found');
            if (window.Utils && window.Utils.UIHelper && window.Utils.UIHelper.showToast) {
                window.Utils.UIHelper.showToast('Chatbot unavailable. Please refresh the page.', 'error');
            }
            return;
        }

        // Open chatbot
        if (this.chatIcon) {
            this.chatIcon.addEventListener('click', (e) => {
                e.preventDefault();
                this.open();
            });
        }

        // Close chatbot
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => {
                this.close();
            });
        }

        // Send message on Enter key
        if (this.input) {
            this.input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            // Character count and validation
            this.input.addEventListener('input', () => {
                this.updateCharacterCount();
                this.toggleSendButton();
            });
        }

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            this.adjustChatHeight();
        });

        // Send message on button click
        if (this.sendBtn) {
            this.sendBtn.addEventListener('click', () => {
                this.sendMessage();
            });
        }

        // Attach functions to window for inline onclick handlers
        window.sendMessage = () => this.sendMessage();
        window.askQuickQuestion = (question) => this.askQuickQuestion(question);
        window.handleKeyPress = (e) => this.handleKeyPress(e);
    }

    setupKeyboardShortcuts() {
        // Add keyboard shortcuts for quick actions
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'k':
                        e.preventDefault();
                        if (this.modal) {
                            this.open();
                        }
                        break;
                    case 'Escape':
                        e.preventDefault();
                        if (this.modal) {
                            this.close();
                        }
                        break;
                }
            }
        });
    }

    loadMessageHistory() {
        if (typeof(Storage) === 'undefined') {
            console.error('LocalStorage is not supported in this browser');
            return;
        }
        const savedHistory = localStorage.getItem('chatbotHistory');
        if (savedHistory) {
            try {
                this.messageHistory = JSON.parse(savedHistory);
                this.displayHistory();
            } catch (error) {
                console.error('Failed to load message history:', error);
                this.messageHistory = [];
            }
        }
    }

    saveMessageHistory() {
        if (typeof(Storage) === 'undefined') {
            console.error('LocalStorage is not supported in this browser');
            return;
        }
        try {
            localStorage.setItem('chatbotHistory', JSON.stringify(this.messageHistory));
        } catch (error) {
            console.error('Failed to save message history:', error);
        }
    }

    displayHistory() {
        if (!this.messages) return;
        
        if (this.messageHistory && Array.isArray(this.messageHistory)) {
            this.messageHistory.forEach(msg => {
                this.addMessageToUI(msg.text, msg.sender, msg.timestamp, false);
            });
        }
    }

    addMessageToUI(text, sender, timestamp = Date.now(), save = true) {
        if (!this.messages) {
            console.error('Chatbot messages container not found!');
            return;
        }
        const messageDiv = document.createElement('div');
        messageDiv.className = `message message-${sender}`;
        
        const timeString = new Date(timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        messageDiv.innerHTML = `
            <div class="message-content">${(window.Utils && window.Utils.UIHelper && window.Utils.UIHelper.sanitizeHTML) ? window.Utils.UIHelper.sanitizeHTML(text) : text}</div>
            <div class="message-time">${timeString}</div>
        `;
        
        this.messages.appendChild(messageDiv);
        this.messages.scrollTop = this.messages.scrollHeight;
        
        if (save) {
            this.messageHistory.push({ text, sender, timestamp });
            if (this.messageHistory.length > this.maxHistoryLength) {
                this.messageHistory.shift();
            }
            this.saveMessageHistory();
        }
    }

    updateCharacterCount() {
        if (!this.input) return;
        
        const currentLength = this.input.value.length;
        const maxLength = 500;
        
        // Update character count display if it exists
        let charCount = document.getElementById('charCount');
        if (!charCount && this.input.parentElement) {
            charCount = document.createElement('div');
            charCount.id = 'charCount';
            charCount.className = 'char-count';
            this.input.parentElement.appendChild(charCount);
        }
        
        if (charCount) {
            charCount.textContent = `${currentLength}/${maxLength}`;
            charCount.style.color = currentLength > maxLength * 0.9 ? 'var(--danger)' : 'var(--text-gray)';
        }
    }

    toggleSendButton() {
        if (!this.sendBtn || !this.input) return;
        
        const hasText = this.input.value.trim().length > 0;
        this.sendBtn.disabled = !hasText;
        if (this.sendBtn.style) {
            this.sendBtn.style.opacity = hasText ? '1' : '0.5';
        }
    }

    adjustChatHeight() {
        if (!this.modal) return;
        
        const maxHeight = window.innerHeight * 0.8;
        const currentHeight = this.modal.offsetHeight;
        
        if (currentHeight > maxHeight && this.modal.style) {
            this.modal.style.height = `${maxHeight}px`;
        }
    }

    showTypingIndicator() {
        if (this.typingIndicator && this.typingIndicator.style) {
            this.typingIndicator.style.display = 'flex';
            if (this.messages) {
                this.messages.scrollTop = this.messages.scrollHeight;
            }
        }
    }

    hideTypingIndicator() {
        if (this.typingIndicator && this.typingIndicator.style) {
            this.typingIndicator.style.display = 'none';
        }
    }

    open() {
        console.log('Opening chatbot...');
        console.log('Modal element:', this.modal);
        if (this.modal) {
            this.modal.classList.add('open');
            this.isOpen = true;
            console.log('Chatbot opened, class added:', this.modal.classList.contains('open'));
            if (this.input && this.input.focus) this.input.focus();
        } else {
            console.error('Modal element not found!');
        }
    }

    close() {
        console.log('Closing chatbot...');
        if (this.modal) {
            this.modal.classList.remove('open');
            this.isOpen = false;
            console.log('Chatbot closed, class removed:', this.modal.classList.contains('open'));
        } else {
            console.error('Modal element not found for closing!');
        }
    }

    addMessage(text, sender) {
        if (!this.messages) {
            console.error('Chatbot messages container not found!');
            return;
        }
        const messageDiv = document.createElement('div');
        messageDiv.className = `message message-${sender}`;

        const now = new Date();
        const timeString = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

        messageDiv.innerHTML = `
            ${text}
            <div class="message-time">${timeString}</div>
        `;

        this.messages.appendChild(messageDiv);
        this.messages.scrollTop = this.messages.scrollHeight;
    }

    processMessage(userMessage) {
        const lowerMessage = userMessage.toLowerCase();
        let response = "";

        // Incident-related queries
        if (lowerMessage.includes('incident') || lowerMessage.includes('emergency')) {
            if (lowerMessage.includes('active') || lowerMessage.includes('current')) {
                response = `🚨 **Active Incidents Report** 🚨\n\n` +
                          `**Total:** 42 incidents\n` +
                          `**Avg Response Time:** 8.2 minutes\n\n` +
                          `**Breakdown:**\n` +
                          `• Emergency: 15 incidents\n` +
                          `• Health: 12 incidents\n` +
                          `• Safety: 8 incidents\n` +
                          `• Traffic: 5 incidents\n` +
                          `• Environmental: 2 incidents\n\n` +
                          `**Trend:** ↓ 12% from last week`;
            }
            else if (lowerMessage.includes('procedure') || lowerMessage.includes('handle')) {
                response = `📋 **Emergency Procedures** 📋\n\n` +
                          `**MEDICAL EMERGENCY:**\n` +
                          `1. Call 911 immediately\n` +
                          `2. Provide first aid\n` +
                          `3. Keep patient calm\n` +
                          `4. Clear area for responders\n\n` +
                          `**FIRE EMERGENCY:**\n` +
                          `1. Activate fire alarm\n` +
                          `2. Evacuate immediately\n` +
                          `3. Use extinguisher if safe\n` +
                          `4. Report to assembly point`;
            }
        }

        // Campaign-related queries
        else if (lowerMessage.includes('campaign')) {
            response = `📢 **Campaign Management** 📢\n\n` +
                      `**Active Campaigns:**\n` +
                      `• Summer Safety Drive\n` +
                      `• Road Awareness Program\n` +
                      `• Health Check Initiative\n\n` +
                      `**Suggested Campaigns:**\n` +
                      `• Community Safety Workshops\n` +
                      `• Digital Awareness Campaign\n` +
                      `• School Safety Program\n` +
                      `• Emergency Response Training`;
        }

        // Report-related queries
        else if (lowerMessage.includes('report') || lowerMessage.includes('generate')) {
            response = `📊 **Report Generator** 📊\n\n` +
                      `I can help create:\n\n` +
                      `**Daily Report:**\n` +
                      `• Incident summary\n` +
                      `• Response metrics\n` +
                      `• Campaign updates\n\n` +
                      `**Weekly Report:**\n` +
                      `• Trend analysis\n` +
                      `• Resource allocation\n` +
                      `• Performance review\n\n` +
                      `**Monthly Report:**\n` +
                      `• Executive summary\n` +
                      `• Budget analysis\n` +
                      `• Future planning`;
        }

        // Emergency procedures
        else if (lowerMessage.includes('guide') || lowerMessage.includes('help')) {
            response = `🆘 **Emergency Guide** 🆘\n\n` +
                      `**Immediate Actions:**\n` +
                      `1. Assess the situation\n` +
                      `2. Call emergency services\n` +
                      `3. Secure the area\n` +
                      `4. Provide assistance\n\n` +
                      `**Safety Tips:**\n` +
                      `• Stay calm and focused\n` +
                      `• Follow evacuation routes\n` +
                      `• Use emergency equipment\n` +
                      `• Report to authorities`;
        }

        // Default response
        else {
            response = `🤖 **Public Safety AI Assistant** 🤖\n\n` +
                      `I can help you with:\n\n` +
                      `🔹 **Incident Management**\n` +
                      `• Active incident tracking\n` +
                      `• Emergency procedures\n` +
                      `• Response coordination\n\n` +
                      `🔹 **Campaign Planning**\n` +
                      `• Campaign suggestions\n` +
                      `• Resource allocation\n` +
                      `• Success metrics\n\n` +
                      `🔹 **Reporting & Analytics**\n` +
                      `• Report generation\n` +
                      `• Trend analysis\n` +
                      `• Performance insights\n\n` +
                      `Try asking me about active incidents or campaign ideas!`;
        }

        return response;
    }

    sendMessage() {
        if (!this.input) {
            console.error('Chatbot input element not found!');
            return;
        }
        const message = this.input.value.trim();
        if (!message) return;
        
        // Validate message content
        if (!this.validateMessage(message)) {
            if (window.Utils && window.Utils.UIHelper && window.Utils.UIHelper.showToast) {
                window.Utils.UIHelper.showToast('Invalid message content', 'error');
            }
            return;
        }
        
        // Sanitize message before processing
        const sanitizedMessage = (window.Utils && window.Utils.UIHelper && window.Utils.UIHelper.sanitizeHTML) ? window.Utils.UIHelper.sanitizeHTML(message) : message;
        
        // Add user message
        this.addMessage(sanitizedMessage, 'user');
        this.input.value = '';

        // Simulate AI thinking
        setTimeout(() => {
            const aiResponse = this.processMessage(sanitizedMessage);
            this.addMessage(aiResponse, 'ai');
        }, 600);
    }
    
    // Validate message content
    validateMessage(message) {
        if (typeof message !== 'string') {
            return false;
        }
        
        // Check for potentially dangerous patterns
        const dangerousPatterns = [/<script/i, /javascript:/i, /vbscript:/i, /on\w+=/i, /<iframe/i, /<object/i, /<embed/i];
        
        for (const pattern of dangerousPatterns) {
            if (pattern.test(message)) {
                return false;
            }
        }
        
        // Check message length
        if (message.length > 1000) {
            return false;
        }
        
        return true;
    }

    askQuickQuestion(question) {
        if (this.input) {
            this.input.value = question;
            this.sendMessage();
        }
    }

    handleKeyPress(event) {
        if (event.key === 'Enter') {
            this.sendMessage();
        }
    }
}

// Create chatbot instance immediately and make it globally available
window.chatbot = new Chatbot();
console.log('Chatbot instance created:', window.chatbot);