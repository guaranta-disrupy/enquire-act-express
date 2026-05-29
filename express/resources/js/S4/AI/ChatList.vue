<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';

const props = defineProps({
    currentConversationId: {
        type: [String, Number],
        default: null
    },
    isOpen: {
        type: Boolean,
        default: false
    },
    isProcessing: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['conversation-selected', 'new-conversation', 'delete-conversation', 'all-conversations-cleared', 'request-delete-with-rating']);

const toast = window.toast ?? {
    success: (m) => console.log('[toast.success]', m),
    error: (m) => console.error('[toast.error]', m),
    info: (m) => console.log('[toast.info]', m),
};

// EXPRESS-only: platform display helpers
const getPlatformLogo = () => null; // no image in Express by default; use icon in template
const getPlatformName = () => 'EXPRESS';
const getAIName = () => 'EXPRESS AI';

const route = (typeof window !== 'undefined' && typeof window.route === 'function')
    ? window.route
    : ((name) => {
        switch (name) {
            case 'api.chat.history': return '/api/chat/history';
            case 'api.chat.clear': return '/api/chat/clear';
            case 'api.chat.rename': return '/api/chat/rename';
            case 'api.chat.pin': return '/api/chat/pin';
            default: return name;
        }
    });

// State
const conversations = ref([]);
const isLoading = ref(false);
const conversationToClear = ref(null);
const showClearOneModal = ref(false);
const showClearAllModal = ref(false);
const isClearingAll = ref(false);
const currentTime = ref(new Date());
const showKebabMenu = ref(null);
const editingConversationId = ref(null);
const editingTitle = ref('');

let timestampUpdateInterval = null;
let conversationPollInterval = null;

const startTimestampUpdates = () => {
    currentTime.value = new Date();
    timestampUpdateInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 60000);
};

const stopTimestampUpdates = () => {
    if (timestampUpdateInterval) {
        clearInterval(timestampUpdateInterval);
        timestampUpdateInterval = null;
    }
};

const startConversationPolling = () => {
    if (!props.isOpen || conversationPollInterval) return;
    conversationPollInterval = setInterval(() => {
        if (props.isOpen && !isLoading.value && !document.hidden) {
            fetchConversations();
        }
    }, 30000);
};

const stopConversationPolling = () => {
    if (conversationPollInterval) {
        clearInterval(conversationPollInterval);
        conversationPollInterval = null;
    }
};


const fetchConversations = async () => {
    isLoading.value = true;
    try {
        const response = await fetch(route('api.chat.history'), {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });

        if (!response.ok) throw new Error('Failed to fetch conversations');

        const data = await response.json();
        const raw = (data.conversations || [])
            .filter(conv => {
                const isDeleted = !!conv.deleted_at;
                const isCleared = !!(conv.metadata && conv.metadata.isCleared);
                const isExpressPlatform = !conv.platform || conv.platform === 'express';
                return !isDeleted && !isCleared && isExpressPlatform;
            })
            .map(conv => ({
                ...conv,
                id: conv.id || conv.conversation_id,
                conversation_id: conv.conversation_id || conv.id
            }));
        const seen = new Set();
        const deduped = raw.filter(conv => {
            const id = conv.conversation_id || conv.id;
            if (seen.has(id)) return false;
            seen.add(id);
            return true;
        });

        conversations.value = deduped.sort((a, b) => {
            const pinnedAtA = a.metadata?.pinned_at ? new Date(a.metadata.pinned_at).getTime() : 0;
            const pinnedAtB = b.metadata?.pinned_at ? new Date(b.metadata.pinned_at).getTime() : 0;

            const isPinnedA = pinnedAtA > 0;
            const isPinnedB = pinnedAtB > 0;

            if (isPinnedA && !isPinnedB) return -1;
            if (!isPinnedA && isPinnedB) return 1;
            if (isPinnedA && isPinnedB) {
                // Newer pin first
                return pinnedAtB - pinnedAtA;
            }

            // Neither pinned: fall back to recency
            const timeA = new Date(a.updated_at || a.created_at).getTime();
            const timeB = new Date(b.updated_at || b.created_at).getTime();
            return timeB - timeA;
        });
    } catch (error) {
        console.error('Error fetching conversations:', error);
        toast.error('Failed to load conversations');
    } finally {
        isLoading.value = false;
    }
};

const selectConversation = (conversation) => {
    if (props.isProcessing) return;
    emit('conversation-selected', conversation);
};

const createNewConversation = () => {
    emit('new-conversation', { platform: 'express' });
};

const clearConversation = (conversation, event) => {
    event.stopPropagation();
    conversationToClear.value = conversation;
    showClearOneModal.value = true;
};

const handleClearOneConfirm = async () => {
    const conversation = conversationToClear.value;
    if (!conversation) {
        showClearOneModal.value = false;
        conversationToClear.value = null;
        return;
    }
    showClearOneModal.value = false;
    const conversationToDelete = conversationToClear.value;
    conversationToClear.value = null;
    
    emit('request-delete-with-rating', conversationToDelete);
};

const togglePinConversation = async (conversation, event) => {
    if (event) event.stopPropagation();
    const conversationId = conversation.conversation_id || conversation.id;
    if (!conversationId) return;

    const currentlyPinned = !!(conversation.metadata && conversation.metadata.pinned_at);

    try {
        const response = await fetch(route('api.chat.pin'), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
            body: JSON.stringify({
                conversation_id: conversationId,
                pinned: !currentlyPinned,
            }),
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            throw new Error(data.message || 'Failed to update pin');
        }

        const data = await response.json();
        const index = conversations.value.findIndex(
            c => (c.conversation_id || c.id) === conversationId
        );
        if (index !== -1) {
            if (!conversations.value[index].metadata) {
                conversations.value[index].metadata = {};
            }
            conversations.value[index].metadata.pinned_at = data.pinned_at || null;
        }

        // Re-sort locally to reflect new pin order
        const seen = new Set();
        const deduped = conversations.value.filter(conv => {
            const id = conv.conversation_id || conv.id;
            if (seen.has(id)) return false;
            seen.add(id);
            return true;
        });
        conversations.value = deduped.sort((a, b) => {
            const pinnedAtA = a.metadata?.pinned_at ? new Date(a.metadata.pinned_at).getTime() : 0;
            const pinnedAtB = b.metadata?.pinned_at ? new Date(b.metadata.pinned_at).getTime() : 0;
            const isPinnedA = pinnedAtA > 0;
            const isPinnedB = pinnedAtB > 0;
            if (isPinnedA && !isPinnedB) return -1;
            if (!isPinnedA && isPinnedB) return 1;
            if (isPinnedA && isPinnedB) return pinnedAtB - pinnedAtA;
            const timeA = new Date(a.updated_at || a.created_at).getTime();
            const timeB = new Date(b.updated_at || b.created_at).getTime();
            return timeB - timeA;
        });

        toast.success(!currentlyPinned ? 'Conversation pinned' : 'Conversation unpinned');
    } catch (e) {
        console.error(e);
        toast.error('Failed to update pin');
    } finally {
        closeKebabMenu();
    }
};

const requestClearAllConversations = () => {
    if (conversations.value.length === 0) {
        toast.info('No conversations to clear');
        return;
    }
    showClearAllModal.value = true;
};

const confirmClearAllConversations = async () => {
    if (isClearingAll.value) return;
    isClearingAll.value = true;
    showClearAllModal.value = false;
    try {
        const response = await fetch(route('api.chat.clear'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({}),
        });
        if (!response.ok) throw new Error('Failed to clear all conversations');
        const count = conversations.value.length;
        conversations.value = [];
        toast.success(`Cleared ${count} conversation(s)`);
        setTimeout(() => {
            fetchConversations();
            emit('all-conversations-cleared');
        }, 300);
    } catch (error) {
        console.error(error);
        toast.error('Failed to clear all conversations');
    } finally {
        isClearingAll.value = false;
    }
};

const getLatestMessage = (conversation) => {
    if (!conversation.messages || conversation.messages.length === 0) return '';
    const lastMessage = conversation.messages[conversation.messages.length - 1];
    if (!lastMessage || !lastMessage.content) return '';
    let content = typeof lastMessage.content === 'string' ? lastMessage.content : JSON.stringify(lastMessage.content);
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = content;
    content = (tempDiv.textContent || tempDiv.innerText || content).replace(/\s+/g, ' ').trim();
    return content.length > 18 ? content.substring(0, 18) + '...' : content;
};

const formatRelativeTime = (dateString) => {
    const date = new Date(dateString);
    const now = currentTime.value;
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    const convDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    
    if (convDate.getTime() === today.getTime()) {
        return 'Today';
    } else if (convDate.getTime() === yesterday.getTime()) {
        return 'Yesterday';
    } else {
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined });
    }
};

const formatDateTime = (dateString) => {
    const date = new Date(dateString);
    const time = date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    const dateStr = date.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
    return `${time} | ${dateStr}`;
};

const toggleKebabMenu = (conversationId, event) => {
    event.stopPropagation();
    showKebabMenu.value = showKebabMenu.value === conversationId ? null : conversationId;
};

const closeKebabMenu = () => {
    showKebabMenu.value = null;
};

const getConversationTitle = (conversation) => {
    return conversation.title && conversation.title.trim() !== '' 
        ? conversation.title 
        : 'EXPRESS AI';
};

const startRename = (conversation, event) => {
    event.stopPropagation();
    const convId = conversation.id || conversation.conversation_id;
    editingConversationId.value = convId;
    editingTitle.value = conversation.title || '';
    closeKebabMenu();
    nextTick(() => {
        const input = document.querySelector(`input[data-conversation-id="${convId}"]`);
        if (input) {
            input.focus();
            input.select();
        }
    });
};

const saveRename = async (conversation) => {
    const convId = conversation.id || conversation.conversation_id;
    if (editingConversationId.value !== convId) return;

    const title = editingTitle.value.trim();
    
    try {
        const response = await fetch(route('api.chat.rename'), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
            body: JSON.stringify({
                conversation_id: convId,
                title: title || null,
            }),
        });

        if (!response.ok) throw new Error('Failed to update title');

        const data = await response.json();
        
        const index = conversations.value.findIndex(
            c => (c.id || c.conversation_id) === convId
        );
        if (index !== -1) {
            conversations.value[index].title = data.title || null;
        }

        editingConversationId.value = null;
        editingTitle.value = '';
        
        setTimeout(() => fetchConversations(), 300);
    } catch (error) {
        console.error('Error updating title:', error);
        toast.error('Failed to update title');
        editingConversationId.value = null;
        editingTitle.value = '';
    }
};

const cancelRename = () => {
    editingConversationId.value = null;
    editingTitle.value = '';
};

const handleVisibilityChange = () => {
    if (!document.hidden && props.isOpen && !isLoading.value) fetchConversations();
};

const handleClickOutside = (event) => {
    if (showKebabMenu.value && !event.target.closest('.kebab-menu-container')) {
        closeKebabMenu();
    }
    // Cancel rename if clicking outside the input
    if (editingConversationId.value && !event.target.closest('input[data-conversation-id]')) {
        const conversation = conversations.value.find(
            c => (c.id || c.conversation_id) === editingConversationId.value
        );
        if (conversation) {
            saveRename(conversation);
        }
    }
};

onMounted(() => {
    startTimestampUpdates();
    document.addEventListener('visibilitychange', handleVisibilityChange);
    document.addEventListener('click', handleClickOutside);
    if (props.isOpen) {
        fetchConversations();
        startConversationPolling();
    }
});

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        if (conversations.value.length === 0 || !props.isProcessing) fetchConversations();
        startConversationPolling();
    } else {
        stopConversationPolling();
    }
});

watch(() => props.currentConversationId, (newId, oldId) => {
    if (newId && newId !== oldId) {
        setTimeout(() => {
            if (props.isOpen && !isLoading.value) fetchConversations();
        }, 500);
    }
});

onUnmounted(() => {
    stopTimestampUpdates();
    stopConversationPolling();
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    document.removeEventListener('click', handleClickOutside);
});

defineExpose({
    refresh: fetchConversations
});
</script>

<template>
    <div
        class="flex flex-col h-full bg-base-100 border-r border-base-300 overflow-hidden transition-all duration-200"
        :class="{ 'pointer-events-none': isProcessing }"
    >
        <!-- Chats Section Header -->
        <div
            class="px-3 py-3 border-b border-base-300 bg-base-100 relative z-10"
        >
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-base font-semibold text-base-content flex-shrink-0">Chats</h2>
                <div class="flex items-center gap-1 flex-shrink-0 relative z-20">
                    <button
                        @click.stop="createNewConversation"
                        :disabled="isProcessing"
                        class="chat-header-btn chat-header-btn-new"
                        style="pointer-events: auto;"
                        title="New conversation"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                            <path fill="none" stroke="#ee920c" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.992 16.342a2 2 0 0 1 .094 1.167l-1.065 3.29a1 1 0 0 0 1.236 1.168l3.413-.998a2 2 0 0 1 1.099.092a10 10 0 1 0-4.777-4.719M8 12h8m-4-4v8" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Conversation List -->
        <div
            class="flex-1 overflow-y-auto overflow-x-hidden pt-2"
        >
            <div v-if="isLoading && conversations.length === 0 && !isProcessing" class="flex items-center justify-center py-8">
                <span class="loading loading-spinner loading-md text-primary"></span>
            </div>

            <div v-else-if="conversations.length === 0" class="flex flex-col items-center justify-center py-12 px-4 text-center">
                <div class="bg-base-200/50 rounded-full p-4 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-base-content/40">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-base-content/70 mb-1">No conversations yet</p>
                <p class="text-xs text-base-content/50">Start a new chat to begin</p>
            </div>

            <div v-else class="py-2">
                <div
                    v-for="conversation in conversations"
                    :key="conversation.id || conversation.conversation_id"
                    @click="(conversation.id === currentConversationId || conversation.conversation_id === currentConversationId) ? null : selectConversation(conversation)"
                    class="mx-2 mb-2 px-4 py-2 rounded-lg transition-all duration-200 group relative overflow-visible"
                    :class="[
                        isProcessing || (conversation.id === currentConversationId || conversation.conversation_id === currentConversationId)
                            ? 'cursor-not-allowed'
                            : 'cursor-pointer',
                        (conversation.id === currentConversationId || conversation.conversation_id === currentConversationId)
                            ? 'bg-[#EEE3D3] !text-[var(--mcolor)] border-[var(--mcolor)] border'
                            : 'bg-gray-100 hover:bg-gray-200'
                    ]"
                    @mouseleave="closeKebabMenu"
                >
                            <div
                                v-if="conversation.metadata && conversation.metadata.pinned_at"
                                class="absolute rounded-full pin-indicator z-10 pointer-events-none"
                                title="Pinned"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" class="pin-icon">
                                    <path d="m3 21l4.63-4.631m.005-.005l-2.78-2.78c-.954-.953.006-2.996 1.31-3.078c1.178-.075 3.905.352 4.812-.555l2.49-2.49c.617-.618.225-2 .185-2.762c-.058-1.016 1.558-2.271 2.415-1.414l4.647 4.648c.86.858-.4 2.469-1.413 2.415c-.762-.04-2.145-.432-2.763.185l-2.49 2.49c-.906.907-.48 3.633-.554 4.811c-.082 1.305-2.125 2.265-3.08 1.31z"/>
                                </svg>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <!-- Inline title editing -->
                                    <div v-if="editingConversationId === (conversation.id || conversation.conversation_id)" class="mb-2">
                                        <input
                                            :data-conversation-id="conversation.id || conversation.conversation_id"
                                            v-model="editingTitle"
                                            @blur="saveRename(conversation)"
                                            @keydown.enter="saveRename(conversation)"
                                            @keydown.esc="cancelRename"
                                            class="text-sm font-semibold w-full px-2 py-1 rounded border focus:outline-none focus:ring-2 focus:ring-[#EE920C] focus:border-transparent"
                                            :class="(conversation.id === currentConversationId || conversation.conversation_id === currentConversationId) 
                                                ? 'bg-white text-gray-900 border-white/30 placeholder-gray-400' 
                                                : 'bg-white text-gray-900 border-gray-300 placeholder-gray-400'"
                                            placeholder="EXPRESS AI"
                                            maxlength="100"
                                        />
                                    </div>
                                    <!-- Display title -->
                                    <p v-else class="text-sm font-bold leading-snug mb-2 whitespace-nowrap" :class="(conversation.id === currentConversationId || conversation.conversation_id === currentConversationId) ? 'text-[var(--mcolor)]' : 'text-gray-900'">
                                        {{ getConversationTitle(conversation) }}
                                    </p>
                                    <p class="text-[10px] opacity-80 whitespace-nowrap" :class="(conversation.id === currentConversationId || conversation.conversation_id === currentConversationId) ? 'text-[var(--mcolor)]/90' : 'text-gray-600'">
                                        {{ formatDateTime(conversation.updated_at || conversation.created_at) }}
                                    </p>
                                </div>
                            </div>
                            <div class="absolute top-3 right-3 kebab-menu-container">
                                <button
                                    @click="toggleKebabMenu(conversation.id || conversation.conversation_id, $event)"
                                    :disabled="isProcessing"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity p-1 hover:bg-black/10 rounded"
                                    :class="(conversation.id === currentConversationId || conversation.conversation_id === currentConversationId) ? 'hover:bg-white/20' : 'hover:bg-gray-300'"
                                    title="More options"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                        <g fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1">
                                            <circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-width="2" />
                                            <path stroke-width="3" d="M12 12h.01v.01H12zm0-4.5h.01v.01H12zm0 9h.01v.01H12z" />
                                        </g>
                                    </svg>
                                </button>
                                <div
                                    v-if="showKebabMenu === (conversation.id || conversation.conversation_id)"
                                    class="absolute right-0 top-full mt-1 w-40 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
                                >
                                        <button
                                            @click.stop="startRename(conversation, $event)"
                                            class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                                <path fill="#323232" d="m14.06 9.02l.92.92L5.92 19H5v-.92zM17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83l3.75 3.75l1.83-1.83a.996.996 0 0 0 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29m-3.6 3.19L3 17.25V21h3.75L17.81 9.94z" />
                                            </svg>
                                            <span>Rename</span>
                                        </button>
                                        <button
                                            @click.stop="togglePinConversation(conversation, $event)"
                                            class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                                <path fill="none" stroke="#323232" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m3 21l4.63-4.631m.005-.005l-2.78-2.78c-.954-.953.006-2.996 1.31-3.078c1.178-.075 3.905.352 4.812-.555l2.49-2.49c.617-.618.225-2 .185-2.762c-.058-1.016 1.558-2.271 2.415-1.414l4.647 4.648c.86.858-.4 2.469-1.413 2.415c-.762-.04-2.145-.432-2.763.185l-2.49 2.49c-.906.907-.48 3.633-.554 4.811c-.082 1.305-2.125 2.265-3.08 1.31z" />
                                            </svg>
                                            <span>{{ conversation.metadata && conversation.metadata.pinned_at ? 'Unpin' : 'Pin' }}</span>
                                        </button>
                                        <button
                                            @click.stop="clearConversation(conversation, $event); closeKebabMenu()"
                                            class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                                <path fill="#323232" d="M7.616 20q-.672 0-1.144-.472T6 18.385V6H5V5h4v-.77h6V5h4v1h-1v12.385q0 .69-.462 1.153T16.384 20zM17 6H7v12.385q0 .269.173.442t.443.173h8.769q.23 0 .423-.192t.192-.424zM9.808 17h1V8h-1zm3.384 0h1V8h-1zM7 6v13z" />
                                            </svg>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </div>
                        </div>
            </div>
        </div>

        <!-- Clear one conversation modal -->
        <div v-if="showClearOneModal" class="fixed inset-0 z-[1100] flex items-center justify-center p-4 bg-black/50" @click.self="showClearOneModal = false; conversationToClear = null">
            <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
                <h3 class="font-medium text-gray-900 mb-2">Clear conversation?</h3>
                <p class="text-sm text-gray-600 mb-4">This action cannot be undone.</p>
                <div class="flex justify-end gap-2">
                    <button class="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200" @click="showClearOneModal = false; conversationToClear = null">Cancel</button>
                    <button class="px-3 py-2 text-sm font-medium text-white rounded-lg bg-red-600 hover:bg-red-700" @click="handleClearOneConfirm">Clear</button>
                </div>
            </div>
        </div>

        <!-- Clear all conversations modal -->
        <div v-if="showClearAllModal" class="fixed inset-0 z-[1100] flex items-center justify-center p-4 bg-black/50" @click.self="showClearAllModal = false">
            <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
                <h3 class="font-medium text-red-600 mb-2">Clear All Conversations</h3>
                <p class="text-base-content mb-4">Are you sure you want to clear all <strong>{{ conversations.length }}</strong> conversation{{ conversations.length !== 1 ? 's' : '' }}? This action cannot be undone.</p>
                <div class="flex justify-end gap-2">
                    <button class="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200" @click="showClearAllModal = false">Cancel</button>
                    <button class="px-3 py-2 text-sm font-medium text-white rounded-lg bg-red-600 hover:bg-red-700" :disabled="isClearingAll" @click="confirmClearAllConversations">
                        <span v-if="isClearingAll" class="loading loading-spinner loading-xs"></span>
                        <span v-else>Clear All</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: hsl(var(--bc) / 0.2);
    border-radius: 3px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: hsl(var(--bc) / 0.3);
}

/* Ensure header buttons are always visible */
.chat-header-btn {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 2rem !important;
    height: 2rem !important;
    min-width: 2rem !important;
    min-height: 2rem !important;
    padding: 0 !important;
    border-radius: 9999px !important;
    border: 1px solid hsl(var(--bc) / 0.2) !important;
    background-color: transparent !important;
    color: hsl(var(--bc) / 0.7) !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    opacity: 1 !important;
    visibility: visible !important;
    flex-shrink: 0 !important;
}

.chat-header-btn:hover:not(:disabled) {
    background-color: hsl(var(--bc) / 0.1) !important;
    border-color: hsl(var(--bc) / 0.3) !important;
    color: hsl(var(--bc)) !important;
    opacity: 1 !important;
}

.chat-header-btn-clear:hover:not(:disabled) {
    background-color: hsl(var(--er) / 0.1) !important;
    border-color: hsl(var(--er) / 0.3) !important;
    color: hsl(var(--er)) !important;
}

.chat-header-btn:disabled {
    opacity: 0.4 !important;
    cursor: not-allowed !important;
    pointer-events: auto !important;
}

.pin-indicator {
    --pin-overlap: 10px;
    --pin-size: 15px;
    --pin-icon-size: 20px;
    top: calc(-1 * var(--pin-overlap));
    left: calc(100% - var(--pin-overlap));
    width: var(--pin-size);
    height: var(--pin-size);
    display: flex;
    align-items: center;
    justify-content: center;
    background: hsl(var(--b1));
    box-shadow: 0 1px 3px hsl(var(--nf) / 0.15);
    color: var(--mcolor);
}
.pin-indicator .pin-icon {
    width: var(--pin-icon-size);
    height: var(--pin-icon-size);
    flex-shrink: 0;
}

.chat-header-btn svg {
    width: 18px !important;
    height: 18px !important;
    display: block !important;
    flex-shrink: 0 !important;
}
</style>
