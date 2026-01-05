<template>
    <div class="claude-assistant">
        <!-- Floating Button -->
        <button
            class="assistant-toggle-btn"
            @click="toggleChat"
            :class="{ 'has-unread': hasUnread }"
            title="AI Development Assistant"
        >
            <i class="bi" :class="isOpen ? 'bi-x-lg' : 'bi-robot'"></i>
        </button>

        <!-- Chat Window -->
        <transition name="slide-up">
            <div v-if="isOpen" class="assistant-window">
                <div class="assistant-header">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-robot me-2"></i>
                        <span class="fw-semibold">Claude AI Assistant</span>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-sm btn-link text-white p-0 me-2" @click="clearChat" title="Clear chat">
                            <i class="bi bi-trash"></i>
                        </button>
                        <button class="btn btn-sm btn-link text-white p-0" @click="toggleChat">
                            <i class="bi bi-dash-lg"></i>
                        </button>
                    </div>
                </div>

                <div class="assistant-body" ref="chatBody">
                    <!-- Welcome Message -->
                    <div v-if="messages.length === 0" class="welcome-message">
                        <div class="welcome-icon">
                            <i class="bi bi-robot"></i>
                        </div>
                        <h6>HIMS Development Assistant</h6>
                        <p class="text-muted small mb-3">
                            I can help you with Laravel code, Vue components, database migrations,
                            bug fixes, and more. Just describe what you need!
                        </p>
                        <div class="quick-prompts">
                            <button
                                v-for="prompt in quickPrompts"
                                :key="prompt"
                                class="quick-prompt-btn"
                                @click="sendQuickPrompt(prompt)"
                            >
                                {{ prompt }}
                            </button>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div v-for="(msg, index) in messages" :key="index" class="message" :class="msg.role">
                        <div class="message-content">
                            <div v-if="msg.role === 'assistant'" v-html="renderMarkdown(msg.content)"></div>
                            <div v-else>{{ msg.content }}</div>
                        </div>
                        <div class="message-time">{{ formatTime(msg.timestamp) }}</div>
                    </div>

                    <!-- Loading -->
                    <div v-if="isLoading" class="message assistant">
                        <div class="message-content">
                            <div class="typing-indicator">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Error -->
                    <div v-if="error" class="error-message">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        {{ error }}
                        <button class="btn btn-sm btn-link" @click="error = ''">Dismiss</button>
                    </div>
                </div>

                <div class="assistant-footer">
                    <form @submit.prevent="sendMessage" class="input-form">
                        <textarea
                            v-model="userInput"
                            @keydown.enter.exact.prevent="sendMessage"
                            @keydown.shift.enter="userInput += '\n'"
                            placeholder="Describe what you need help with..."
                            rows="1"
                            :disabled="isLoading"
                            ref="inputField"
                        ></textarea>
                        <button type="submit" :disabled="!userInput.trim() || isLoading" class="send-btn">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </form>
                    <div class="footer-hint">
                        Press Enter to send, Shift+Enter for new line
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, nextTick, onMounted, watch } from 'vue';
import axios from 'axios';
import { marked } from 'marked';
import DOMPurify from 'dompurify';

const isOpen = ref(false);
const userInput = ref('');
const messages = ref([]);
const isLoading = ref(false);
const error = ref('');
const hasUnread = ref(false);
const chatBody = ref(null);
const inputField = ref(null);

const quickPrompts = [
    'Create a new API endpoint',
    'Add a Vue component',
    'Write a migration',
    'Fix a bug in...',
];

// Configure marked for code highlighting
marked.setOptions({
    breaks: true,
    gfm: true,
});

const toggleChat = () => {
    isOpen.value = !isOpen.value;
    hasUnread.value = false;
    if (isOpen.value) {
        nextTick(() => {
            inputField.value?.focus();
        });
    }
};

const clearChat = () => {
    messages.value = [];
    error.value = '';
    localStorage.removeItem('claude_chat_history');
};

const sendQuickPrompt = (prompt) => {
    userInput.value = prompt;
    inputField.value?.focus();
};

const sendMessage = async () => {
    const message = userInput.value.trim();
    if (!message || isLoading.value) return;

    // Add user message
    messages.value.push({
        role: 'user',
        content: message,
        timestamp: new Date()
    });

    userInput.value = '';
    isLoading.value = true;
    error.value = '';

    scrollToBottom();

    try {
        // Prepare conversation history (last 10 messages for context)
        const history = messages.value.slice(-10).map(m => ({
            role: m.role,
            content: m.content
        }));

        const response = await axios.post('/api/claude-assistant/chat', {
            message: message,
            conversation_history: history.slice(0, -1) // Exclude the message we just sent
        });

        if (response.data.success) {
            messages.value.push({
                role: 'assistant',
                content: response.data.message,
                timestamp: new Date()
            });

            if (!isOpen.value) {
                hasUnread.value = true;
            }
        } else {
            error.value = response.data.error || 'Failed to get response';
        }
    } catch (err) {
        console.error('Claude Assistant Error:', err);
        error.value = err.response?.data?.error || err.message || 'Failed to connect to assistant';
    } finally {
        isLoading.value = false;
        scrollToBottom();
        saveHistory();
    }
};

const renderMarkdown = (content) => {
    const html = marked(content);
    return DOMPurify.sanitize(html);
};

const formatTime = (timestamp) => {
    return new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const scrollToBottom = () => {
    nextTick(() => {
        if (chatBody.value) {
            chatBody.value.scrollTop = chatBody.value.scrollHeight;
        }
    });
};

const saveHistory = () => {
    // Save last 20 messages to localStorage
    const historyToSave = messages.value.slice(-20);
    localStorage.setItem('claude_chat_history', JSON.stringify(historyToSave));
};

const loadHistory = () => {
    try {
        const saved = localStorage.getItem('claude_chat_history');
        if (saved) {
            messages.value = JSON.parse(saved);
        }
    } catch (e) {
        console.error('Failed to load chat history:', e);
    }
};

onMounted(() => {
    loadHistory();
});

// Auto-resize textarea
watch(userInput, () => {
    nextTick(() => {
        if (inputField.value) {
            inputField.value.style.height = 'auto';
            inputField.value.style.height = Math.min(inputField.value.scrollHeight, 120) + 'px';
        }
    });
});
</script>

<style scoped>
.claude-assistant {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}

.assistant-toggle-btn {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.assistant-toggle-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
}

.assistant-toggle-btn.has-unread::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 14px;
    height: 14px;
    background: #ef4444;
    border-radius: 50%;
    border: 2px solid white;
}

.assistant-window {
    position: absolute;
    bottom: 70px;
    right: 0;
    width: 420px;
    height: 550px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

@media (max-width: 480px) {
    .assistant-window {
        width: calc(100vw - 40px);
        height: calc(100vh - 100px);
        right: -10px;
    }
}

.assistant-header {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    padding: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-actions button {
    opacity: 0.8;
}

.header-actions button:hover {
    opacity: 1;
}

.assistant-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: #f8fafc;
}

.welcome-message {
    text-align: center;
    padding: 20px;
}

.welcome-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-size: 28px;
    color: white;
}

.quick-prompts {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
}

.quick-prompt-btn {
    padding: 6px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    background: white;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.quick-prompt-btn:hover {
    background: #6366f1;
    color: white;
    border-color: #6366f1;
}

.message {
    margin-bottom: 12px;
    display: flex;
    flex-direction: column;
}

.message.user {
    align-items: flex-end;
}

.message.assistant {
    align-items: flex-start;
}

.message-content {
    max-width: 85%;
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 14px;
    line-height: 1.5;
}

.message.user .message-content {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    border-bottom-right-radius: 4px;
}

.message.assistant .message-content {
    background: white;
    color: #1e293b;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.message-time {
    font-size: 10px;
    color: #94a3b8;
    margin-top: 4px;
}

/* Markdown styles */
.message.assistant .message-content :deep(pre) {
    background: #1e293b;
    color: #e2e8f0;
    padding: 12px;
    border-radius: 8px;
    overflow-x: auto;
    font-size: 12px;
    margin: 8px 0;
}

.message.assistant .message-content :deep(code) {
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
}

.message.assistant .message-content :deep(pre code) {
    background: transparent;
    padding: 0;
}

.message.assistant .message-content :deep(p) {
    margin: 0 0 8px 0;
}

.message.assistant .message-content :deep(p:last-child) {
    margin-bottom: 0;
}

.message.assistant .message-content :deep(ul),
.message.assistant .message-content :deep(ol) {
    margin: 8px 0;
    padding-left: 20px;
}

.typing-indicator {
    display: flex;
    gap: 4px;
    padding: 4px 0;
}

.typing-indicator span {
    width: 8px;
    height: 8px;
    background: #94a3b8;
    border-radius: 50%;
    animation: typing 1.4s infinite ease-in-out;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
    }
    30% {
        transform: translateY(-8px);
    }
}

.error-message {
    background: #fef2f2;
    color: #dc2626;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 13px;
    margin-top: 8px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
}

.assistant-footer {
    padding: 12px;
    border-top: 1px solid #e2e8f0;
    background: white;
}

.input-form {
    display: flex;
    gap: 8px;
    align-items: flex-end;
}

.input-form textarea {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    resize: none;
    font-size: 14px;
    line-height: 1.4;
    max-height: 120px;
    transition: border-color 0.2s;
}

.input-form textarea:focus {
    outline: none;
    border-color: #6366f1;
}

.send-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.send-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.send-btn:not(:disabled):hover {
    transform: scale(1.05);
}

.footer-hint {
    font-size: 11px;
    color: #94a3b8;
    text-align: center;
    margin-top: 6px;
}

/* Animations */
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>
