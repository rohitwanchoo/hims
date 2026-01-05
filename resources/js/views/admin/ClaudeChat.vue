<template>
    <div class="claude-chat">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0"><i class="bi bi-robot"></i> Claude AI Assistant</h4>
                <small class="text-muted">System Admin Development Assistant</small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" @click="clearChat" :disabled="loading">
                    <i class="bi bi-trash"></i> Clear Chat
                </button>
                <button class="btn btn-outline-primary btn-sm" @click="newConversation">
                    <i class="bi bi-plus-circle"></i> New Chat
                </button>
            </div>
        </div>

        <!-- Chat Container -->
        <div class="chat-container">
            <!-- Messages Area -->
            <div class="messages-area" ref="messagesContainer">
                <!-- Welcome Message -->
                <div v-if="messages.length === 0" class="welcome-message text-center py-5">
                    <div class="welcome-icon mb-4">
                        <i class="bi bi-robot" style="font-size: 4rem; color: #6c5ce7;"></i>
                    </div>
                    <h4>Welcome to Claude AI Assistant</h4>
                    <p class="text-muted mb-4">
                        I'm here to help you with HIMS development, debugging, and implementation.<br>
                        Ask me anything about the codebase, suggest features, or get coding assistance.
                    </p>
                    <div class="quick-prompts">
                        <button
                            v-for="prompt in quickPrompts"
                            :key="prompt"
                            class="btn btn-outline-secondary btn-sm m-1"
                            @click="sendQuickPrompt(prompt)"
                        >
                            {{ prompt }}
                        </button>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div
                    v-for="(msg, index) in messages"
                    :key="index"
                    :class="['message', msg.role === 'user' ? 'user-message' : 'assistant-message']"
                >
                    <div class="message-avatar">
                        <i :class="msg.role === 'user' ? 'bi bi-person-fill' : 'bi bi-robot'"></i>
                    </div>
                    <div class="message-content">
                        <div class="message-header">
                            <strong>{{ msg.role === 'user' ? 'You' : 'Claude' }}</strong>
                            <small class="text-muted ms-2">{{ formatTime(msg.timestamp) }}</small>
                        </div>
                        <div class="message-body" v-html="formatMessage(msg.content)"></div>
                    </div>
                </div>

                <!-- Loading Indicator -->
                <div v-if="loading" class="message assistant-message">
                    <div class="message-avatar">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div class="message-content">
                        <div class="typing-indicator">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="input-area">
                <div class="input-container">
                    <textarea
                        v-model="inputMessage"
                        ref="inputField"
                        class="form-control"
                        placeholder="Type your message... (Shift+Enter for new line)"
                        rows="1"
                        @keydown="handleKeydown"
                        @input="autoResize"
                        :disabled="loading"
                    ></textarea>
                    <button
                        class="btn btn-primary send-btn"
                        @click="sendMessage"
                        :disabled="!inputMessage.trim() || loading"
                    >
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
                <div class="input-footer">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i>
                        Claude can help with code, debugging, and feature implementation.
                        Press Enter to send, Shift+Enter for new line.
                    </small>
                </div>
            </div>
        </div>

        <!-- Error Alert -->
        <div v-if="error" class="alert alert-danger mt-3 alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> {{ error }}
            <button type="button" class="btn-close" @click="error = null"></button>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { marked } from 'marked';
import hljs from 'highlight.js';

export default {
    name: 'ClaudeChat',
    setup() {
        const messages = ref([]);
        const inputMessage = ref('');
        const loading = ref(false);
        const error = ref(null);
        const conversationId = ref(null);
        const messagesContainer = ref(null);
        const inputField = ref(null);

        const quickPrompts = [
            'Explain the project structure',
            'How do I add a new API endpoint?',
            'Show me the patient registration flow',
            'How to fix common Laravel errors?',
            'Best practices for Vue components',
        ];

        // Configure marked for syntax highlighting
        marked.setOptions({
            highlight: function(code, lang) {
                if (lang && hljs.getLanguage(lang)) {
                    try {
                        return hljs.highlight(code, { language: lang }).value;
                    } catch (e) {}
                }
                return hljs.highlightAuto(code).value;
            },
            breaks: true,
            gfm: true,
        });

        const scrollToBottom = async () => {
            await nextTick();
            if (messagesContainer.value) {
                messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
            }
        };

        const formatMessage = (content) => {
            try {
                return marked.parse(content);
            } catch (e) {
                return content.replace(/\n/g, '<br>');
            }
        };

        const formatTime = (timestamp) => {
            if (!timestamp) return '';
            return new Date(timestamp).toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
            });
        };

        const autoResize = () => {
            const textarea = inputField.value;
            if (textarea) {
                textarea.style.height = 'auto';
                textarea.style.height = Math.min(textarea.scrollHeight, 150) + 'px';
            }
        };

        const handleKeydown = (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        };

        const sendMessage = async () => {
            if (!inputMessage.value.trim() || loading.value) return;

            const userMessage = inputMessage.value.trim();
            inputMessage.value = '';

            // Reset textarea height
            if (inputField.value) {
                inputField.value.style.height = 'auto';
            }

            // Add user message to chat
            messages.value.push({
                role: 'user',
                content: userMessage,
                timestamp: new Date().toISOString(),
            });

            scrollToBottom();
            loading.value = true;
            error.value = null;

            try {
                const response = await axios.post('/api/claude-chat/send', {
                    message: userMessage,
                    conversation_id: conversationId.value,
                });

                if (response.data.success) {
                    conversationId.value = response.data.conversation_id;

                    messages.value.push({
                        role: 'assistant',
                        content: response.data.message,
                        timestamp: new Date().toISOString(),
                    });
                } else {
                    error.value = response.data.message || 'Unknown error occurred';
                }
            } catch (err) {
                error.value = err.response?.data?.message || err.message || 'Failed to send message';
                console.error('Chat error:', err);
            } finally {
                loading.value = false;
                scrollToBottom();
            }
        };

        const sendQuickPrompt = (prompt) => {
            inputMessage.value = prompt;
            sendMessage();
        };

        const clearChat = async () => {
            if (!confirm('Are you sure you want to clear the conversation?')) return;

            try {
                await axios.post('/api/claude-chat/clear', {
                    conversation_id: conversationId.value,
                });
                messages.value = [];
                conversationId.value = null;
            } catch (err) {
                error.value = 'Failed to clear conversation';
            }
        };

        const newConversation = () => {
            messages.value = [];
            conversationId.value = null;
            error.value = null;
        };

        onMounted(() => {
            if (inputField.value) {
                inputField.value.focus();
            }
        });

        return {
            messages,
            inputMessage,
            loading,
            error,
            conversationId,
            messagesContainer,
            inputField,
            quickPrompts,
            formatMessage,
            formatTime,
            autoResize,
            handleKeydown,
            sendMessage,
            sendQuickPrompt,
            clearChat,
            newConversation,
        };
    },
};
</script>

<style scoped>
.claude-chat {
    height: calc(100vh - 120px);
    display: flex;
    flex-direction: column;
}

.chat-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #f8f9fa;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}

.welcome-message {
    max-width: 600px;
    margin: 0 auto;
}

.welcome-icon {
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.quick-prompts {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 8px;
}

.message {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.user-message {
    flex-direction: row-reverse;
}

.message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.2rem;
}

.user-message .message-avatar {
    background: #007bff;
    color: white;
}

.assistant-message .message-avatar {
    background: #6c5ce7;
    color: white;
}

.message-content {
    max-width: 80%;
    background: white;
    padding: 12px 16px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.user-message .message-content {
    background: #007bff;
    color: white;
    border-radius: 12px 12px 0 12px;
}

.assistant-message .message-content {
    border-radius: 12px 12px 12px 0;
}

.message-header {
    font-size: 0.85rem;
    margin-bottom: 4px;
}

.user-message .message-header .text-muted {
    color: rgba(255, 255, 255, 0.7) !important;
}

.message-body {
    line-height: 1.5;
}

.message-body :deep(pre) {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 12px;
    border-radius: 6px;
    overflow-x: auto;
    font-size: 0.85rem;
    margin: 8px 0;
}

.message-body :deep(code) {
    font-family: 'Fira Code', monospace;
}

.message-body :deep(p) {
    margin-bottom: 8px;
}

.message-body :deep(p:last-child) {
    margin-bottom: 0;
}

.message-body :deep(ul), .message-body :deep(ol) {
    padding-left: 20px;
    margin-bottom: 8px;
}

.typing-indicator {
    display: flex;
    gap: 4px;
    padding: 8px 0;
}

.typing-indicator span {
    width: 8px;
    height: 8px;
    background: #6c5ce7;
    border-radius: 50%;
    animation: bounce 1.4s infinite ease-in-out;
}

.typing-indicator span:nth-child(1) { animation-delay: 0s; }
.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

@keyframes bounce {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-8px); }
}

.input-area {
    padding: 16px;
    background: white;
    border-top: 1px solid #e9ecef;
}

.input-container {
    display: flex;
    gap: 12px;
    align-items: flex-end;
}

.input-container textarea {
    flex: 1;
    resize: none;
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 1rem;
    border: 2px solid #e9ecef;
    transition: border-color 0.2s;
    max-height: 150px;
}

.input-container textarea:focus {
    border-color: #6c5ce7;
    box-shadow: none;
    outline: none;
}

.send-btn {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #6c5ce7;
    border: none;
    flex-shrink: 0;
}

.send-btn:hover:not(:disabled) {
    background: #5a4bd1;
}

.send-btn:disabled {
    background: #ccc;
}

.input-footer {
    margin-top: 8px;
    text-align: center;
}
</style>
