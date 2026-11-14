/* script.js - ChatGPT-like Chat Interactivity */

document.addEventListener('DOMContentLoaded', () => {
    const messageInput = document.getElementById('messageInput');
    const chatForm = document.getElementById('chatForm');
    const btnSend = document.getElementById('btnSend');
    const messagesContainer = document.getElementById('messagesContainer');
    const emptyState = document.getElementById('emptyState');
    const typingIndicator = document.getElementById('typingIndicator');

    // Auto-resize textarea
    messageInput.addEventListener('input', () => {
        messageInput.style.height = 'auto';
        messageInput.style.height = Math.min(messageInput.scrollHeight, 160) + 'px';
        toggleSendButton();
    });

    // Enable/disable send button
    function toggleSendButton() {
        btnSend.disabled = messageInput.value.trim() === '';
    }

    messageInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.dispatchEvent(new Event('submit'));
        }
    });

    // Handle form submission
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const message = messageInput.value.trim();
        if (!message) return;

        // Add user message to UI
        addMessage('user', message);
        messageInput.value = '';
        messageInput.style.height = 'auto';
        toggleSendButton();

        // Hide empty state on first message
        if (emptyState) emptyState.style.display = 'none';

        // Show typing indicator
        showTyping();

        try {
            const response = await fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=' + encodeURIComponent(message)
            });

            const data = await response.json();

            // Hide typing indicator
            hideTyping();

            if (data.success) {
                addMessage('assistant', data.response);
            } else {
                addMessage('assistant', 'Sorry, something went wrong. Please try again.');
            }
        } catch (error) {
            hideTyping();
            addMessage('assistant', 'Connection error. Please check your server and try again.');
            console.error('Error:', error);
        }

        // Scroll to bottom
        scrollToBottom();
    });

    // Add a message bubble to the chat
    function addMessage(role, content) {
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const isUser = role === 'user';

        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isUser ? 'message-user' : 'message-assistant'}`;

        const avatarIcon = isUser ? 'fa-user' : 'fa-robot';
        const avatarClass = isUser ? 'avatar-user' : 'avatar-ai';

        messageDiv.innerHTML = `
            <div class="message-avatar ${avatarClass}">
                <i class="fa-solid ${avatarIcon}"></i>
            </div>
            <div class="message-content">
                <div class="message-bubble">${escapeHtml(content)}</div>
                <div class="message-time">${time}</div>
            </div>
        `;

        messagesContainer.appendChild(messageDiv);
        scrollToBottom();
    }

    // Show typing indicator
    function showTyping() {
        typingIndicator.style.display = 'flex';
        scrollToBottom();
    }

    // Hide typing indicator
    function hideTyping() {
        typingIndicator.style.display = 'none';
    }

    // Scroll messages to bottom
    function scrollToBottom() {
        setTimeout(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 100);
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Quick suggestion chips
    window.sendSuggestion = (text) => {
        messageInput.value = text;
        messageInput.style.height = 'auto';
        messageInput.style.height = Math.min(messageInput.scrollHeight, 160) + 'px';
        toggleSendButton();
        messageInput.focus();
    };

    // New chat
    window.newChat = () => {
        if (confirm('Start a new chat? This will clear the current conversation.')) {
            window.location.href = '?clear=1';
        }
    };

    // Select chat from sidebar
    window.selectChat = (element) => {
        document.querySelectorAll('.chat-item').forEach(item => item.classList.remove('active'));
        element.classList.add('active');
    };

    // Initialize
    toggleSendButton();
    scrollToBottom();

    // Toggle sidebar on mobile
    window.toggleSidebar = function() {
        document.querySelector('.sidebar').classList.toggle('open');
    };
    document.getElementById('sidebar-toggle').addEventListener('click', toggleSidebar);
});