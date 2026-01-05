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
            <div v-if="isOpen" class="assistant-window" :class="{ 'expanded': showCodeChanges && pendingChanges.length > 0 }">
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

                <div class="assistant-content">
                    <!-- Main Chat Panel -->
                    <div class="chat-panel">
                        <div class="assistant-body" ref="chatBody">
                            <!-- Welcome Message -->
                            <div v-if="messages.length === 0" class="welcome-message">
                                <div class="welcome-icon">
                                    <i class="bi bi-robot"></i>
                                </div>
                                <h6>HIMS Development Assistant</h6>
                                <p class="text-muted small mb-3">
                                    I can help you with Laravel code, Vue components, database migrations,
                                    bug fixes, and more. I can now <strong>apply code changes directly</strong>!
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
                                <div class="message-meta">
                                    <span class="message-time">{{ formatTime(msg.timestamp) }}</span>
                                    <span v-if="msg.hasChanges" class="changes-badge" @click="showChangesForMessage(msg)">
                                        <i class="bi bi-code-slash me-1"></i>
                                        {{ msg.changesCount }} file(s)
                                    </span>
                                </div>
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

                    <!-- Code Changes Panel -->
                    <transition name="slide-right">
                        <div v-if="showCodeChanges && pendingChanges.length > 0" class="changes-panel">
                            <div class="changes-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="fw-semibold">
                                        <i class="bi bi-code-slash me-2"></i>
                                        Code Changes ({{ pendingChanges.length }})
                                    </span>
                                    <button class="btn btn-sm btn-link p-0" @click="showCodeChanges = false">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <div class="changes-actions mt-2">
                                    <button
                                        class="btn btn-sm btn-success me-2"
                                        @click="applyAllChanges"
                                        :disabled="isApplying"
                                    >
                                        <i class="bi bi-check-all me-1"></i>
                                        Apply All
                                    </button>
                                    <button
                                        class="btn btn-sm btn-outline-danger"
                                        @click="rejectAllChanges"
                                        :disabled="isApplying"
                                    >
                                        <i class="bi bi-x-lg me-1"></i>
                                        Reject All
                                    </button>
                                </div>
                            </div>

                            <div class="changes-list">
                                <div
                                    v-for="(change, index) in pendingChanges"
                                    :key="index"
                                    class="change-item"
                                    :class="{ 'expanded': expandedChange === index }"
                                >
                                    <div class="change-header" @click="toggleChangeExpand(index)">
                                        <div class="change-info">
                                            <span class="change-action" :class="change.action">
                                                {{ change.action }}
                                            </span>
                                            <span class="change-path">{{ change.path }}</span>
                                        </div>
                                        <div class="change-toggle">
                                            <i class="bi" :class="expandedChange === index ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                        </div>
                                    </div>

                                    <!-- Expanded Diff View -->
                                    <div v-if="expandedChange === index" class="change-diff">
                                        <div class="diff-toolbar">
                                            <button
                                                class="btn btn-sm"
                                                :class="diffView === 'unified' ? 'btn-primary' : 'btn-outline-secondary'"
                                                @click="diffView = 'unified'"
                                            >
                                                Unified
                                            </button>
                                            <button
                                                class="btn btn-sm"
                                                :class="diffView === 'split' ? 'btn-primary' : 'btn-outline-secondary'"
                                                @click="diffView = 'split'"
                                            >
                                                Split
                                            </button>
                                        </div>

                                        <div v-if="change.loadingDiff" class="diff-loading">
                                            <div class="spinner-border spinner-border-sm me-2"></div>
                                            Loading diff...
                                        </div>

                                        <div v-else-if="diffView === 'unified'" class="diff-content unified">
                                            <pre><code v-html="renderDiff(change)"></code></pre>
                                        </div>

                                        <div v-else class="diff-content split">
                                            <div class="split-old">
                                                <div class="split-header">Current</div>
                                                <pre><code>{{ change.currentContent || '(New File)' }}</code></pre>
                                            </div>
                                            <div class="split-new">
                                                <div class="split-header">New</div>
                                                <pre><code>{{ change.content }}</code></pre>
                                            </div>
                                        </div>

                                        <div class="change-actions">
                                            <button
                                                class="btn btn-sm btn-success me-2"
                                                @click.stop="applyChange(index)"
                                                :disabled="isApplying || change.applied"
                                            >
                                                <i class="bi bi-check me-1"></i>
                                                {{ change.applied ? 'Applied' : 'Apply' }}
                                            </button>
                                            <button
                                                class="btn btn-sm btn-outline-danger"
                                                @click.stop="rejectChange(index)"
                                                :disabled="isApplying || change.applied"
                                            >
                                                <i class="bi bi-x me-1"></i>
                                                Reject
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Git Commit Option -->
                            <div class="commit-section" v-if="hasAppliedChanges">
                                <div class="form-check mb-2">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="createCommit"
                                        v-model="createCommit"
                                    >
                                    <label class="form-check-label" for="createCommit">
                                        Create Git commit
                                    </label>
                                </div>
                                <div v-if="createCommit">
                                    <input
                                        type="text"
                                        class="form-control form-control-sm"
                                        v-model="commitMessage"
                                        placeholder="Commit message..."
                                    >
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </transition>

        <!-- Apply Result Toast -->
        <div v-if="applyResult" class="apply-toast" :class="applyResult.success ? 'success' : 'error'">
            <i class="bi" :class="applyResult.success ? 'bi-check-circle' : 'bi-exclamation-circle'"></i>
            {{ applyResult.message }}
        </div>
    </div>
</template>

<script setup>
import { ref, nextTick, onMounted, watch, computed } from 'vue';
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

// Code changes state
const showCodeChanges = ref(false);
const pendingChanges = ref([]);
const expandedChange = ref(null);
const diffView = ref('unified');
const isApplying = ref(false);
const applyResult = ref(null);
const createCommit = ref(false);
const commitMessage = ref('');

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

const hasAppliedChanges = computed(() => {
    return pendingChanges.value.some(c => c.applied);
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
    pendingChanges.value = [];
    showCodeChanges.value = false;
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
            conversation_history: history.slice(0, -1)
        });

        if (response.data.success) {
            const assistantMsg = {
                role: 'assistant',
                content: response.data.message,
                timestamp: new Date(),
                hasChanges: response.data.has_changes,
                changesCount: response.data.code_changes?.length || 0,
                codeChanges: response.data.code_changes || []
            };

            messages.value.push(assistantMsg);

            // If there are code changes, show the panel
            if (response.data.has_changes && response.data.code_changes?.length > 0) {
                pendingChanges.value = response.data.code_changes.map(c => ({
                    ...c,
                    applied: false,
                    loadingDiff: false,
                    currentContent: null,
                    diff: null
                }));
                showCodeChanges.value = true;
            }

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

const showChangesForMessage = (msg) => {
    if (msg.codeChanges && msg.codeChanges.length > 0) {
        pendingChanges.value = msg.codeChanges.map(c => ({
            ...c,
            applied: false,
            loadingDiff: false,
            currentContent: null,
            diff: null
        }));
        showCodeChanges.value = true;
    }
};

const toggleChangeExpand = async (index) => {
    if (expandedChange.value === index) {
        expandedChange.value = null;
    } else {
        expandedChange.value = index;

        // Load diff if not already loaded
        const change = pendingChanges.value[index];
        if (!change.diff && !change.loadingDiff) {
            change.loadingDiff = true;
            try {
                const response = await axios.post('/api/claude-assistant/preview-diff', {
                    path: change.path,
                    new_content: change.content
                });

                if (response.data.success) {
                    change.currentContent = response.data.current_content;
                    change.diff = response.data.diff;
                    change.isNewFile = response.data.is_new_file;
                }
            } catch (err) {
                console.error('Failed to load diff:', err);
            } finally {
                change.loadingDiff = false;
            }
        }
    }
};

const renderDiff = (change) => {
    if (!change.diff) {
        if (change.isNewFile) {
            return change.content.split('\n').map(line =>
                `<span class="diff-add">+ ${escapeHtml(line)}</span>`
            ).join('\n');
        }
        return escapeHtml(change.content);
    }

    const lines = change.diff.split('\n');
    return lines.map(line => {
        if (line.startsWith('+') && !line.startsWith('+++')) {
            return `<span class="diff-add">${escapeHtml(line)}</span>`;
        } else if (line.startsWith('-') && !line.startsWith('---')) {
            return `<span class="diff-remove">${escapeHtml(line)}</span>`;
        } else if (line.startsWith('@@ ')) {
            return `<span class="diff-hunk">${escapeHtml(line)}</span>`;
        }
        return escapeHtml(line);
    }).join('\n');
};

const escapeHtml = (str) => {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
};

const applyChange = async (index) => {
    isApplying.value = true;
    const change = pendingChanges.value[index];

    try {
        const response = await axios.post('/api/claude-assistant/apply-changes', {
            changes: [{
                path: change.path,
                content: change.content,
                action: change.action
            }]
        });

        if (response.data.success || response.data.applied?.length > 0) {
            change.applied = true;
            showApplyResult(true, `Applied: ${change.path}`);
        } else {
            showApplyResult(false, response.data.errors?.[0]?.error || 'Failed to apply change');
        }
    } catch (err) {
        showApplyResult(false, err.response?.data?.error || 'Failed to apply change');
    } finally {
        isApplying.value = false;
    }
};

const rejectChange = (index) => {
    pendingChanges.value.splice(index, 1);
    if (pendingChanges.value.length === 0) {
        showCodeChanges.value = false;
    }
    expandedChange.value = null;
};

const applyAllChanges = async () => {
    isApplying.value = true;

    const changesToApply = pendingChanges.value.filter(c => !c.applied);

    try {
        const response = await axios.post('/api/claude-assistant/apply-changes', {
            changes: changesToApply.map(c => ({
                path: c.path,
                content: c.content,
                action: c.action
            })),
            commit_message: createCommit.value ? commitMessage.value : null
        });

        if (response.data.applied?.length > 0) {
            response.data.applied.forEach(applied => {
                const change = pendingChanges.value.find(c => c.path === applied.path);
                if (change) change.applied = true;
            });

            let msg = `Applied ${response.data.applied.length} file(s)`;
            if (response.data.git?.success) {
                msg += ' (committed)';
            }
            showApplyResult(true, msg);
        }

        if (response.data.errors?.length > 0) {
            showApplyResult(false, `${response.data.errors.length} error(s) occurred`);
        }
    } catch (err) {
        showApplyResult(false, err.response?.data?.error || 'Failed to apply changes');
    } finally {
        isApplying.value = false;
    }
};

const rejectAllChanges = () => {
    pendingChanges.value = [];
    showCodeChanges.value = false;
    expandedChange.value = null;
};

const showApplyResult = (success, message) => {
    applyResult.value = { success, message };
    setTimeout(() => {
        applyResult.value = null;
    }, 3000);
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
    // Save last 20 messages to localStorage (without code changes to save space)
    const historyToSave = messages.value.slice(-20).map(m => ({
        role: m.role,
        content: m.content,
        timestamp: m.timestamp,
        hasChanges: m.hasChanges,
        changesCount: m.changesCount
    }));
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
    transition: width 0.3s ease;
}

.assistant-window.expanded {
    width: 900px;
}

@media (max-width: 960px) {
    .assistant-window.expanded {
        width: calc(100vw - 40px);
    }
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

.assistant-content {
    flex: 1;
    display: flex;
    overflow: hidden;
}

.chat-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
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

.message-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 4px;
}

.message-time {
    font-size: 10px;
    color: #94a3b8;
}

.changes-badge {
    font-size: 10px;
    background: #dbeafe;
    color: #2563eb;
    padding: 2px 8px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.changes-badge:hover {
    background: #2563eb;
    color: white;
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

/* Code Changes Panel */
.changes-panel {
    width: 480px;
    background: #f8fafc;
    border-left: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.changes-header {
    padding: 12px 16px;
    background: white;
    border-bottom: 1px solid #e2e8f0;
}

.changes-list {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
}

.change-item {
    background: white;
    border-radius: 8px;
    margin-bottom: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.change-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    cursor: pointer;
    transition: background 0.2s;
}

.change-header:hover {
    background: #f8fafc;
}

.change-info {
    display: flex;
    align-items: center;
    gap: 8px;
    overflow: hidden;
}

.change-action {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    padding: 2px 8px;
    border-radius: 4px;
    flex-shrink: 0;
}

.change-action.create {
    background: #dcfce7;
    color: #16a34a;
}

.change-action.update {
    background: #dbeafe;
    color: #2563eb;
}

.change-action.delete {
    background: #fee2e2;
    color: #dc2626;
}

.change-path {
    font-size: 13px;
    color: #475569;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.change-diff {
    border-top: 1px solid #e2e8f0;
    padding: 12px;
}

.diff-toolbar {
    display: flex;
    gap: 4px;
    margin-bottom: 12px;
}

.diff-toolbar .btn {
    font-size: 11px;
    padding: 4px 12px;
}

.diff-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    color: #64748b;
}

.diff-content {
    background: #1e293b;
    border-radius: 8px;
    overflow: auto;
    max-height: 300px;
}

.diff-content pre {
    margin: 0;
    padding: 12px;
    font-size: 12px;
    line-height: 1.5;
}

.diff-content code {
    font-family: 'Monaco', 'Menlo', monospace;
    color: #e2e8f0;
    white-space: pre;
}

.diff-content :deep(.diff-add) {
    color: #4ade80;
    display: block;
}

.diff-content :deep(.diff-remove) {
    color: #f87171;
    display: block;
}

.diff-content :deep(.diff-hunk) {
    color: #60a5fa;
    display: block;
}

.diff-content.split {
    display: flex;
    background: transparent;
}

.split-old,
.split-new {
    flex: 1;
    background: #1e293b;
    border-radius: 8px;
    overflow: auto;
    max-height: 300px;
}

.split-old {
    margin-right: 4px;
}

.split-new {
    margin-left: 4px;
}

.split-header {
    padding: 8px 12px;
    background: rgba(255, 255, 255, 0.1);
    font-size: 11px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
}

.split-old pre,
.split-new pre {
    margin: 0;
    padding: 12px;
    font-size: 11px;
    line-height: 1.5;
}

.split-old code,
.split-new code {
    font-family: 'Monaco', 'Menlo', monospace;
    color: #e2e8f0;
}

.change-actions {
    margin-top: 12px;
    display: flex;
    justify-content: flex-end;
}

.commit-section {
    padding: 12px;
    background: white;
    border-top: 1px solid #e2e8f0;
}

/* Apply Result Toast */
.apply-toast {
    position: fixed;
    bottom: 90px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 500;
    animation: slideIn 0.3s ease;
    z-index: 10000;
}

.apply-toast.success {
    background: #dcfce7;
    color: #16a34a;
}

.apply-toast.error {
    background: #fee2e2;
    color: #dc2626;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
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

.slide-right-enter-active,
.slide-right-leave-active {
    transition: all 0.3s ease;
}

.slide-right-enter-from,
.slide-right-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>
