<script setup>
import { ref, onMounted, onUnmounted, watch, computed, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Chatbot from './Chatbot.vue';
//import DialogModal from '@/Components/DialogModal.vue';
import WarningModal from '@/S4/Warning.vue';

import actLogo from '../../../images/act.png';


const props = defineProps({
    open: {
        type: Boolean,
        default: false
    },
    selectedPlatform: {
        type: String,
        default: 'act'
    },
    sidebarOnly: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:open', 'close', 'switch-platform', 'conversations-empty', 'conversation-selected', 'new-conversation']);

// Route helper function for axios (baseURL is '/api/' so paths don't include '/api/')
const route = (typeof window !== 'undefined' && typeof window.route === 'function')
    ? window.route
    : ((name, ...params) => {
        // Fallback explicit paths when Ziggy route helper is unavailable
        // Axios has baseURL='/api/' so we don't include '/api/' prefix here
        switch (name) {
            case 'api.chat.clear':
                return '/chat/clear';
            case 'api.chat.conversation-rating':
                return '/chat/conversation-rating';
            case 'api.chat.rename':
                return '/chat/rename';
            case 'api.chat.pin':
                return '/chat/pin';
            case 'api.chat.history':
                return '/chat/history';
            case 'api.chat.conversation.create':
                return '/chat/conversation/create';
            default:
                return `/${name.replace(/\./g, '/')}`;
        }
    });

// Route helper for fetch calls (needs full path with '/api/' prefix)
const fetchRoute = (name, ...params) => {
    const ziggyRoute = (typeof window !== 'undefined' && typeof window.route === 'function')
        ? window.route(name, ...params)
        : null;
    
    if (ziggyRoute) {
        // If Ziggy returns a full URL or path starting with /api/, use it as-is
        // Otherwise, check if it needs /api/ prefix
        return ziggyRoute.startsWith('/api/') || ziggyRoute.startsWith('http') 
            ? ziggyRoute 
            : `/api${ziggyRoute}`;
    }
    
    // Fallback: add /api/ prefix for fetch calls
    switch (name) {
        case 'api.chat.clear':
            return '/api/chat/clear';
        case 'api.chat.conversation-rating':
            return '/api/chat/conversation-rating';
        case 'api.chat.rename':
            return '/api/chat/rename';
        case 'api.chat.pin':
            return '/api/chat/pin';
        case 'api.chat.history':
            return '/api/chat/history';
        case 'api.chat.conversation.create':
            return '/api/chat/conversation/create';
        default:
            return `/api/${name.replace(/\./g, '/')}`;
    }
};

// Toast fallback (same as in Chatbot.vue)
const toast = window.toast ?? {
    success: (m) => console.log('[toast.success]', m),
    error: (m) => console.error('[toast.error]', m),
    info: (m) => console.log('[toast.info]', m),
};

// Platform configuration
const platformConfig = {
    act: { name: 'ACT AI', logo: actLogo },
};

// State
const isCollapsed = ref(false);
const conversations = ref([]);
const selectedConversationId = ref(null);
const isChatbotOpen = ref(true);
const showPlatformModal = ref(false);
const isProcessing = ref(false);
const isInitializing = ref(false); // Guard to prevent duplicate conversation creation
const hasLoadedInitialConversations = ref(false);

// Conversation rating modal state
const showConfirmClear = ref(false);
const showConversationRating = ref(false);
const conversationRating = ref(0);
const conversationRatingComment = ref('');
const conversationToDelete = ref(null); // Store conversation being deleted

// Clear all conversations modal state
const showConfirmClearAll = ref(false);
const isClearingAll = ref(false);
const showKebabMenu = ref(null);

// Rename state
const editingConversationId = ref(null);
const editingTitle = ref('');


// Load conversations from backend API
const loadConversations = async () => {
  try {
    const response = await axios.get(route('api.chat.history'));

    if (response.data && Array.isArray(response.data.conversations)) {
      const active = response.data.conversations
        .filter((conv) => {
          // keep only active conversations
          const status = conv.status || conv.metadata?.status;
          const isCleared = !!(conv.isCleared ?? conv.metadata?.isCleared);

          return status !== 'cleared' && !isCleared;
        })
        .map((conv) => ({
          id: conv.conversation_id,
          conversation_id: conv.conversation_id,
          platform: conv.platform || 'act',
          title: conv.title || conv.metadata?.summary || 'ACT AI',
          lastMessage: conv.messages?.[conv.messages.length - 1]?.content || '',
          timestamp: conv.updated_at || new Date().toISOString(),
          messageCount: conv.messages?.length || 0,
          metadata: conv.metadata || {},
        }));

      // Sort: pinned first (by pinned_at desc), then by timestamp (newest first)
      conversations.value = active.sort((a, b) => {
        const pinnedAtA = a.metadata?.pinned_at ? new Date(a.metadata.pinned_at).getTime() : 0;
        const pinnedAtB = b.metadata?.pinned_at ? new Date(b.metadata.pinned_at).getTime() : 0;
        const isPinnedA = pinnedAtA > 0;
        const isPinnedB = pinnedAtB > 0;
        
        // Pinned items come first
        if (isPinnedA && !isPinnedB) return -1;
        if (!isPinnedA && isPinnedB) return 1;
        
        // If both pinned, sort by pinned_at (newest pinned first)
        if (isPinnedA && isPinnedB) {
          return pinnedAtB - pinnedAtA;
        }
        
        // If neither pinned, sort by timestamp (newest first)
        const timeA = new Date(a.timestamp).getTime();
        const timeB = new Date(b.timestamp).getTime();
        return timeB - timeA;
      });
    } else {
      conversations.value = [];
    }
  } catch (e) {
    console.warn('Failed to load conversations from backend', e);
    conversations.value = [];
    return false;
  }
};

// Create a new conversation on the backend
const createNewConversation = async (platform = null) => {
    // Ensure platform is valid, default to 'act' if not in platformConfig
    let targetPlatform = platform || props.selectedPlatform;
    if (!platformConfig[targetPlatform]) {
        targetPlatform = 'act'; // Fallback to act if platform doesn't exist
    }
    
    isProcessing.value = true;
    try {
        // Create conversation on backend using dedicated endpoint
        const response = await axios.post(route('api.chat.conversation.create'), {
            platform: targetPlatform,
        });
        
        if (response.data && response.data.conversation_id) {
            const platformInfo = platformConfig[targetPlatform] || platformConfig.act;
            const newConversation = {
                id: response.data.conversation_id, // Real MongoDB ID from backend
                platform: targetPlatform,
                title: platformInfo.name,
                lastMessage: response.data.greeting_message || '', // Store greeting as last message
                timestamp: new Date().toISOString(),
                messageCount: 1 // Has initial greeting message
            };
            
            conversations.value.unshift(newConversation);
            selectConversation(newConversation.id);
            
            // Close modal if open
            showPlatformModal.value = false;
            
            return newConversation.id;
        }
    } catch (error) {
        console.error('Failed to create conversation on backend:', error);
        toast.error('Failed to create conversation');
    } finally {
        isProcessing.value = false;
    }
};



// Select a conversation
const selectConversation = (conversationId) => {
    selectedConversationId.value = conversationId;

    if (!props.sidebarOnly) {
        // Normal mode: open chatbot
        isChatbotOpen.value = true;
    }
};

// Update conversation ID when MongoDB returns the actual conversation_id
const updateConversationId = (oldId, newId) => {
    const conversation = conversations.value.find(c => c.id === oldId);
    if (conversation && oldId !== newId) {
        // Update the conversation ID to match MongoDB
        conversation.id = newId;
        
        // Update selected conversation ID if it matches
        if (selectedConversationId.value === oldId) {
            selectedConversationId.value = newId;
        }
    }
};

// Update conversation metadata when messages change
const updateConversationMetadata = (conversationId, lastMessage, timestamp = null, messageCount = null) => {
    const conversation = conversations.value.find(c => c.id === conversationId);
    if (conversation) {
        const previousLastMessage = conversation.lastMessage;
        conversation.lastMessage = lastMessage || '';
        if (typeof messageCount === 'number') {
            conversation.messageCount = messageCount;
        }
        
        // Only update timestamp if the last message actually changed (new message added)
        if (previousLastMessage !== conversation.lastMessage && conversation.lastMessage) {
            conversation.timestamp = timestamp || new Date().toISOString();
            // Re-sort conversations when timestamp is updated
            conversations.value.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
        }
    }
};

// Clear a conversation (delete it via API and remove from list)
const clearConversation = (conversation, event) => {

    if (event) {
        event.stopPropagation();
    }
    
    if (isProcessing.value) {
        return;
    }

    conversationToDelete.value = conversation;
    showConfirmClear.value = true;
};

// Confirm clear - close confirmation modal and show rating modal
const confirmClearConversation = () => {
    // Close the confirmation modal and show the rating modal
    showConfirmClear.value = false;
    showConversationRating.value = true;
};

// Cancel clear confirmation
const cancelClearConversation = () => {
    showConfirmClear.value = false;
    conversationToDelete.value = null;
};

// Submit conversation rating and then clear
const submitConversationRating = async () => {
    const conversation = conversationToDelete.value;
    if (!conversation) return;
    
    const conversationId = conversation.id;
    const platform = conversation.platform || props.selectedPlatform;
    
    // Submit rating if user provided one
    if (conversationRating.value > 0) {
        try {
            await axios.post(route('api.chat.conversation-rating'), {
                conversation_id: conversationId,
                rating: conversationRating.value,
                comment: conversationRatingComment.value.trim() || null
            });
        } catch (error) {
            console.error('Failed to submit conversation rating:', error);
        }
    }
    
    // Now clear the conversation
    performClearConversation();
};

// Skip rating and just clear
const skipConversationRating = () => {
    performClearConversation();
};

// Cancel rating modal
const cancelConversationRating = () => {
    // Reset rating modal
    showConversationRating.value = false;
    conversationRating.value = 0;
    conversationRatingComment.value = '';
    conversationToDelete.value = null;
    showConfirmClear.value = false;
};

// Perform the actual conversation clearing (same pattern as Chatbot.vue)
const performClearConversation = () => {
    const conversation = conversationToDelete.value;
    if (!conversation) return;
    
    const conversationId = conversation.id;
    const platform = conversation.platform || props.selectedPlatform;
    
    isProcessing.value = true;
    
    // Call API to clear the conversation from backend (same as Chatbot.vue)
    axios.post(route('api.chat.clear'), {
        platform: platform,
        conversation_id: conversationId,
        scope: null,
    }).then((response) => {
        // The API returns the conversation_id that was cleared
        const clearedConversationId = response.data?.conversation_id || conversationId;
        
        if (response.data?.success !== false) {
            // Remove from local list and localStorage
            removeConversation(clearedConversationId, platform);
        } else {
            console.error('Failed to clear conversation:', response.data?.message || 'Unknown error');
        }
    }).catch((error) => {
        console.error('Failed to clear conversation:', error);
        // Even if API fails, remove from local list
        removeConversation(conversationId, platform);
    }).finally(() => {
        isProcessing.value = false;
        showConversationRating.value = false;
        conversationRating.value = 0;
        conversationRatingComment.value = '';
        conversationToDelete.value = null;
    });
};

// Request to clear all conversations - show confirmation modal
const requestClearAllConversations = () => {
    if (conversations.value.length === 0 || isProcessing.value || isClearingAll.value) {
        return;
    }
    showConfirmClearAll.value = true;
};

// Confirm clear all conversations
const confirmClearAllConversations = async () => {
    showConfirmClearAll.value = false;
    isClearingAll.value = true;
    isProcessing.value = true;

    try {
        // Clear all conversations by calling the API for each one
        const clearPromises = conversations.value.map(conversation => 
            axios.post(route('api.chat.clear'), {
                platform: conversation.platform || props.selectedPlatform,
                conversation_id: conversation.id,
                scope: null,
            }).catch(error => {
                console.error('Failed to clear conversation:', conversation.id, error);
                return null; // Continue even if one fails
            })
        );

        await Promise.all(clearPromises);

        // Clear the local conversations list
        conversations.value = [];
        selectedConversationId.value = null;

        console.log('[ChatbotList] All conversations cleared successfully');
        
    } catch (error) {
        console.error('Error clearing all conversations:', error);
    } finally {
        isClearingAll.value = false;
        isProcessing.value = false;
    }
};

// Cancel clear all confirmation
const cancelClearAllConversations = () => {
    showConfirmClearAll.value = false;
};

// Remove a conversation from the list (when cleared)
const removeConversation = (conversationId, platform = null) => {
    const targetPlatform = platform || props.selectedPlatform;
    
    console.log('[ChatbotList] Attempting to remove conversation:', {
        conversationId,
        platform: targetPlatform,
        currentConversations: conversations.value.map(c => ({ id: c.id, platform: c.platform })),
        selectedConversationId: selectedConversationId.value
    });
    
    // First, try to find by exact ID match (with platform)
    let index = conversations.value.findIndex(c => c.id === conversationId && c.platform === targetPlatform);
    
    // If not found, try to find by ID only (ignore platform)
    if (index === -1) {
        index = conversations.value.findIndex(c => c.id === conversationId);
        if (index !== -1) {
            console.log('[ChatbotList] Found conversation by ID only (platform mismatch)');
        }
    }
    
    // If still not found and we have selectedConversationId, try that
    if (index === -1 && selectedConversationId.value) {
        index = conversations.value.findIndex(c => c.id === selectedConversationId.value);
        if (index !== -1) {
            console.log('[ChatbotList] Found conversation by selectedConversationId');
        }
    }
    
    // Last resort: find any conversation with matching platform
    if (index === -1) {
        index = conversations.value.findIndex(c => c.platform === targetPlatform);
        if (index !== -1) {
            console.log('[ChatbotList] Found conversation by platform only (last resort)');
        }
    }
    
    if (index !== -1) {
        const removedConversation = conversations.value[index];
        conversations.value.splice(index, 1);
        
        console.log('[ChatbotList] Removed conversation:', removedConversation);
        
        // If this was the selected conversation, select another one or close
        if (selectedConversationId.value === removedConversation.id || selectedConversationId.value === conversationId) {
            if (conversations.value.length > 0) {
                selectedConversationId.value = conversations.value[0].id;
            } else {
                selectedConversationId.value = null;
                isChatbotOpen.value = false;
            }
        }
    } else {
        console.warn('[ChatbotList] Could not find conversation to remove:', {
            conversationId,
            platform: targetPlatform,
            availableConversations: conversations.value
        });
    }
};

// Format timestamp
const formatTimestamp = (timestamp) => {
    try {
        const date = new Date(timestamp);
        const time = date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        const dateStr = date.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' });
        return `${time} | ${dateStr}`;
    } catch (e) {
        return '';
    }
};

const toggleKebabMenu = (conversationId, event) => {
    event.stopPropagation();
    showKebabMenu.value = showKebabMenu.value === conversationId ? null : conversationId;
};

const closeKebabMenu = () => {
    showKebabMenu.value = null;
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
        const response = await fetch(fetchRoute('api.chat.rename'), {
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

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to update title');
        }

        const data = await response.json();
        
        // Update local state immediately
        const index = conversations.value.findIndex(
            c => (c.id || c.conversation_id) === convId
        );
        if (index !== -1) {
            conversations.value[index].title = data.title || null;
        }

        editingConversationId.value = null;
        editingTitle.value = '';
        
        // Reload conversations to ensure consistency
        await loadConversations();
        
        toast.success('Title updated successfully');
    } catch (error) {
        console.error('Error updating title:', error);
        toast.error(error.message || 'Failed to update title');
        editingConversationId.value = null;
        editingTitle.value = '';
    }
};

const cancelRename = () => {
    editingConversationId.value = null;
    editingTitle.value = '';
};

const togglePinConversation = async (conversation, event) => {
    if (event) event.stopPropagation();
    const conversationId = conversation.conversation_id || conversation.id;
    if (!conversationId) return;

    const currentlyPinned = !!(conversation.metadata && conversation.metadata.pinned_at);

    try {
        const response = await fetch(fetchRoute('api.chat.pin'), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
            body: JSON.stringify({
                conversation_id: conversationId,
                pinned: !currentlyPinned,
            }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to update pin');
        }

        const data = await response.json();
        
        // Update local state immediately
        const index = conversations.value.findIndex(
            c => (c.conversation_id || c.id) === conversationId
        );
        if (index !== -1) {
            if (!conversations.value[index].metadata) {
                conversations.value[index].metadata = {};
            }
            conversations.value[index].metadata.pinned_at = data.pinned_at || null;
        }

        // Reload conversations to ensure consistency and proper sorting
        await loadConversations();

        toast.success(!currentlyPinned ? 'Conversation pinned' : 'Conversation unpinned');
    } catch (e) {
        toast.error(e.message || 'Failed to update pin');
    } finally {
        closeKebabMenu();
    }
};
// Truncate text
const truncateText = (text, maxLength = 50) => {
  if (!text) return '';
  // normalize whitespace (handles \n, tabs, multiple spaces)
  const clean = String(text).replace(/\s+/g, ' ').trim();
  return clean.length > maxLength ? clean.slice(0, maxLength) + '…' : clean;
};

// Get platform logo with fallback
const getPlatformLogo = (platform) => {
    // Always ensure we have a valid platform key
    let platformKey = platform;
    if (!platformKey || !platformConfig[platformKey]) {
        platformKey = props.selectedPlatform || 'act';
    }
    if (!platformConfig[platformKey]) {
        platformKey = 'act'; // Final fallback
    }
    
    const logo = platformConfig[platformKey]?.logo || platformConfig.act.logo;
    
    // Debug logging
    if (!logo) {
        console.warn('[ChatbotList] No logo found for platform:', platformKey, 'Available platforms:', Object.keys(platformConfig));
    }
    
    return logo;
};

// Handle image load error
const handleImageError = (event) => {
    console.warn('[ChatbotList] Image failed to load:', event.target.src);
    // Fallback to act logo if image fails to load
    const fallbackLogo = platformConfig.act.logo;
    if (event.target.src !== fallbackLogo && fallbackLogo) {
        console.log('[ChatbotList] Attempting fallback logo:', fallbackLogo);
        event.target.src = fallbackLogo;
    } else {
        console.error('[ChatbotList] Fallback logo also unavailable');
    }
};

// Close ChatbotList
const closeChatbotList = () => {
    emit('update:open', false);
    emit('close');
};


// Handle conversation cleared event from Chatbot
const handleConversationCleared = (data) => {
    console.log('[ChatbotList] Conversation cleared event received:', data);
    const { conversationId, platform } = data;
    const targetPlatform = platform || props.selectedPlatform;
    
    // The currently selected conversation is the one being cleared
    // Use selectedConversationId as the primary identifier
    if (!selectedConversationId.value) {
        console.warn('[ChatbotList] No selectedConversationId, cannot remove conversation');
        return;
    }
    
    console.log('[ChatbotList] Removing selected conversation:', {
        selectedConversationId: selectedConversationId.value,
        mongoConversationId: conversationId,
        platform: targetPlatform,
        allConversations: conversations.value.map(c => ({ id: c.id, platform: c.platform }))
    });
    
    // Find the conversation to remove
    const conversationIndex = conversations.value.findIndex(c => c.id === selectedConversationId.value);
    
    if (conversationIndex === -1) {
        console.warn('[ChatbotList] Could not find conversation with ID:', selectedConversationId.value);
        // Try to find by MongoDB conversation_id as fallback
        if (conversationId) {
            const fallbackIndex = conversations.value.findIndex(c => c.id === conversationId);
            if (fallbackIndex !== -1) {
                console.log('[ChatbotList] Found conversation by MongoDB conversation_id instead');
                conversations.value.splice(fallbackIndex, 1);
                // Update selectedConversationId if it matches
                if (selectedConversationId.value === conversationId) {
                    selectedConversationId.value = null;
                    isChatbotOpen.value = false;
                }
            }
        }
        return;
    }
    
    // Remove the conversation
    const removedConversation = conversations.value[conversationIndex];
    conversations.value.splice(conversationIndex, 1);
    
    console.log('[ChatbotList] Successfully removed conversation:', removedConversation);
    
    // Clear selected conversation
    selectedConversationId.value = null;
    isChatbotOpen.value = false;
    
    // Select another conversation if available
    if (conversations.value.length > 0) {
        selectedConversationId.value = conversations.value[0].id;
        isChatbotOpen.value = true;
    }
};



// Expose methods for Chatbot to call
defineExpose({
    updateConversationMetadata,
    removeConversation,
    updateConversationId,
    createNewConversation
});

onMounted(async () => {
  await loadConversations();
  console.log('[ChatbotList] history length:', conversations.value.length);

  // Select the first conversation if available, otherwise leave as null
  if (conversations.value.length > 0) {
    selectedConversationId.value = conversations.value[0].id;
  } else {
    selectedConversationId.value = null;
  }

  hasLoadedInitialConversations.value = true;
  emit('conversations-empty', conversations.value.length === 0);
  if (props.sidebarOnly) {
    emit('conversation-selected', selectedConversationId.value || null);
  }

  // Attach click outside handler for kebab menu
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  // Clean up event listener
  document.removeEventListener('click', handleClickOutside);
});

watch(selectedConversationId, (newId) => {
  if (props.sidebarOnly && hasLoadedInitialConversations.value) {
    emit('conversation-selected', newId || null);
  }
});

watch(() => conversations.value.length, (length) => {
  if (hasLoadedInitialConversations.value) {
    emit('conversations-empty', length === 0);
  }
});
</script>

<template>
  <!-- Sidebar Only Mode (for use inside Chatbot) -->
  <template v-if="sidebarOnly">
    <div class="flex flex-col h-full w-full">
      <!-- Sidebar Header -->
      <div class="px-4 py-4 border-b border-gray-200 bg-white">
        <div class="flex items-center justify-between">
          <h2 class="text-base font-semibold text-base-content">Chats</h2>
          <div class="flex items-center gap-1">
          <button 
            @click.stop="requestClearAllConversations"
            :disabled="isProcessing || isClearingAll || conversations.length === 0"
            class="inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-red-200"
            title="Clear all conversations"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
            </svg>
          </button>
          <button
            @click.stop="createNewConversation"
            :disabled="isProcessing"
            class="btn btn-sm btn-circle btn-ghost hover:bg-base-200"
            title="New conversation"
          >
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
          <g fill="none" stroke="#257556" stroke-linecap="round" stroke-width="1.5">
                  <path stroke-linejoin="round" d="M21.25 12a9.23 9.23 0 0 1-2.705 6.54A9.25 9.25 0 0 1 12 21.25a9.2 9.2 0 0 1-3.795-.81l-3.867.572a1.195 1.195 0 0 1-1.361-1.43l.537-3.923A8.9 8.9 0 0 1 2.75 12a9.23 9.23 0 0 1 2.705-6.54A9.25 9.25 0 0 1 12 2.75a9.26 9.26 0 0 1 6.545 2.71A9.24 9.24 0 0 1 21.25 12" />
                  <path stroke-miterlimit="10" d="M11.693 7.816v7.795M7.8 11.722h7.804" />
          </g>
          </svg>
          </button>
          </div>
        </div>
      </div>

      <!-- Conversation List -->
      <div class="flex-1 overflow-y-auto p-2">
        <div
            v-for="conversation in conversations"
            :key="conversation.id"
            @click="selectConversation(conversation.id)"
            class="group relative flex items-start p-3 cursor-pointer transition-colors border rounded-lg mb-2
                    bg-gray-50 hover:bg-gray-100 border-gray-100"
            :style="selectedConversationId === conversation.id
                ? { backgroundColor: '#d6f1f3', borderColor: '#257556' }
                : {}"
            >
          <!-- Left content (reserve space for top-right button) -->
          <div class="flex items-start gap-3.5 min-w-0 flex-1">
            <!-- Platform Avatar -->
            <div class="flex-shrink-0 relative">
              
              <!-- Pin Indicator -->
              <div
                v-if="conversation.metadata && conversation.metadata.pinned_at"
                class="absolute -top-[20px] left-[160px] rounded-full pin-indicator z-10 pointer-events-none bg-white p-0.5 shadow-sm"
                title="Pinned"
              >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" class="pin-icon w-4 h-4 text-[#257556]">
                  <path d="m3 21l4.63-4.631m.005-.005l-2.78-2.78c-.954-.953.006-2.996 1.31-3.078c1.178-.075 3.905.352 4.812-.555l2.49-2.49c.617-.618.225-2 .185-2.762c-.058-1.016 1.558-2.271 2.415-1.414l4.647 4.648c.86.858-.4 2.469-1.413 2.415c-.762-.04-2.145-.432-2.763.185l-2.49 2.49c-.906.907-.48 3.633-.554 4.811c-.082 1.305-2.125 2.265-3.08 1.31z"/>
                </svg>
              </div>
            </div>

            <!-- Conversation Info -->
            <div class="min-w-0 flex-1">
              <p v-if="editingConversationId !== (conversation.id || conversation.conversation_id)" class="text-xs font-semibold text-[#257556] mb-1.5">
                {{ conversation.title || 'ACT AI' }}
              </p>
              <input
                v-else
                :data-conversation-id="conversation.id || conversation.conversation_id"
                v-model="editingTitle"
                @keyup.enter="saveRename(conversation)"
                @keyup.esc="cancelRename"
                class="text-xs font-semibold text-[#257556] mb-1.5 w-full px-1 border border-[#257556] rounded focus:outline-none focus:ring-1 focus:ring-[#257556]"
                placeholder="Enter title..."
              />

              <div class="flex items-center gap-1 text-[10px] text-gray-600 mb-1.5 whitespace-nowrap">
                <span>{{ formatTimestamp(conversation.timestamp) }}</span>
              </div>

              <p
                v-if="conversation.lastMessage"
                class="text-xs text-gray-600 truncate overflow-hidden whitespace-nowrap w-full"
              >
                {{ truncateText(conversation.lastMessage, 40) }}
              </p>
              <p v-else class="text-xs text-gray-400 italic">
                No messages yet
              </p>
            </div>
          </div>

          <!-- Kebab Menu Container -->
          <div class="kebab-menu-container absolute top-2 right-2">
            <!-- Kebab Menu Button -->
            <button
              @click.stop="toggleKebabMenu(conversation.id || conversation.conversation_id, $event)"
              class="inline-flex items-center justify-center w-8 h-8 bg-transparent text-gray-500 opacity-0 group-hover:opacity-100 transition-all duration-200 "
              title="More options"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="5" r="2"/>
                <circle cx="12" cy="12" r="2"/>
                <circle cx="12" cy="19" r="2"/>
              </svg>
            </button>

            <!-- Kebab Menu Dropdown -->
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

          <!-- Clear button (top-right) -->
          <!-- <button
            @click.stop="clearConversation(conversation, $event)"
            :disabled="isProcessing"
            class="absolute top-1 right-1 inline-flex items-center justify-center w-8 h-8 rounded-full border border-transparent bg-transparent text-gray-500 opacity-0 group-hover:opacity-100 transition-all duration-200 hover:bg-red-50 hover:text-red-600 hover:border-red-200 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-red-200 focus:ring-offset-1"
            title="Clear conversation"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24">
              <path
                fill="currentColor"
                d="M16 9v10H8V9zm-1.5-6h-5l-1 1H5v2h14V4h-3.5zM18 7H6v12c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2z"
              />
            </svg>
          </button> -->
        </div>

        <!-- Empty State -->
        <div v-if="conversations.length === 0" class="p-6 text-center text-gray-400 text-sm flex flex-col items-center justify-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" aria-hidden="true">
            <path
              fill="none"
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13.593 19.855A9.96 9.96 0 0 1 7.7 19L3 20l1.3-3.9C1.976 12.663 2.874 8.228 6.4 5.726c3.526-2.501 8.59-2.296 11.845.48c2.128 1.816 3.053 4.363 2.693 6.813M22 22l-5-5m0 5l5-5"
            />
          </svg>
          <span>No conversations yet</span>
        </div>
      </div>
    </div>
  </template>

  <!-- Full Mode (standalone with Chatbot) - Only render when NOT sidebarOnly -->
  <div
    v-else-if="!sidebarOnly"
    v-show="open"
    class="fixed bottom-[107px] right-[4vmin] z-40 bg-white rounded-xl h-[70vmin] border border-gray-200 shadow-xl flex overflow-hidden transition-all duration-300"
    :class="isCollapsed ? 'w-[calc(60px+50vmin)]' : 'w-[calc(250px+50vmin)]'"
  >
    <!-- Left Sidebar -->
    <div
      class="bg-white border-r border-gray-200 transition-all duration-300 flex flex-col h-full flex-shrink-0"
      :class="isCollapsed ? 'w-[60px]' : 'w-[250px]'"
    >
      <!-- Sidebar Header -->
      <div class="px-4 py-4 border-b border-gray-200 bg-white">
        <div class="flex items-center justify-between">
          <h2 class="text-base font-semibold text-base-content">Chats</h2>
          <div class="flex items-center gap-1">
            <button 
              @click.stop="requestClearAllConversations"
              :disabled="isProcessing || isClearingAll || conversations.length === 0"
              class="inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-red-200"
              title="Clear all conversations"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
              </svg>
            </button>
            <button
              @click.stop="createNewConversation"
              :disabled="isProcessing"
              class="btn btn-sm btn-circle btn-ghost hover:bg-base-200"
              title="New conversation"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="M12 5v14M5 12h14" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Conversation List -->
      <div class="flex-1 overflow-y-auto">
        <div
            v-for="conversation in conversations"
            :key="conversation.id"
            @click="selectConversation(conversation.id)"
            class="group relative flex items-start gap-3 p-3 cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors border-b border-gray-100"
            :style="selectedConversationId === conversation.id
                ? { backgroundColor: '#b6f5f9', borderColor: '#257556' }
                : {}"
            >
          <!-- Platform Avatar -->
          <div class="flex-shrink-0 relative">
            <div class="w-10 h-10 rounded-full flex items-center justify-center overflow-hidden bg-gray-100">
              <img
                :src="getPlatformLogo(conversation?.platform)"
                :alt="conversation?.title || 'Chat'"
                class="object-contain"
                :class="isCollapsed ? 'w-12 h-12' : 'w-10 h-10'"
                style="display: block; min-width: 100%; min-height: 100%;"
                @error="handleImageError"
              />
            </div>
            <!-- Pin Indicator -->
            <div
              v-if="conversation.metadata && conversation.metadata.pinned_at"
              class="absolute -top-1 -right-1 rounded-full pin-indicator z-10 pointer-events-none bg-white p-0.5 shadow-sm"
              title="Pinned"
            >
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" class="pin-icon w-4 h-4 text-[#257556]">
                <path d="m3 21l4.63-4.631m.005-.005l-2.78-2.78c-.954-.953.006-2.996 1.31-3.078c1.178-.075 3.905.352 4.812-.555l2.49-2.49c.617-.618.225-2 .185-2.762c-.058-1.016 1.558-2.271 2.415-1.414l4.647 4.648c.86.858-.4 2.469-1.413 2.415c-.762-.04-2.145-.432-2.763.185l-2.49 2.49c-.906.907-.48 3.633-.554 4.811c-.082 1.305-2.125 2.265-3.08 1.31z"/>
              </svg>
            </div>
          </div>

          <!-- Conversation Info -->
          <div v-if="!isCollapsed" class="flex-1 min-w-0 pr-10">
            <div class="flex items-center justify-between mb-1">
              <h3 class="font-semibold text-sm text-gray-900">
                {{ conversation.title || 'ACT AI' }}
              </h3>
            </div>

            <p class="text-xs text-gray-500 mb-1 whitespace-nowrap">
              {{ formatTimestamp(conversation.timestamp) }}
            </p>

            <p
              v-if="conversation.lastMessage"
              class="text-xs text-gray-600 truncate overflow-hidden whitespace-nowrap w-full"
            >
              {{ truncateText(conversation.lastMessage, 40) }}
            </p>
            <p v-else class="text-xs text-gray-400 italic">
              No messages yet
            </p>
          </div>

          <!-- Clear button (top-right) -->
          <button
            @click.stop="clearConversation(conversation, $event)"
            :disabled="isProcessing"
            class="absolute top-2 right-2 inline-flex items-center justify-center w-8 h-8 rounded-full border border-transparent bg-transparent text-gray-500 opacity-0 group-hover:opacity-100 transition-all duration-200 hover:bg-red-50 hover:text-red-600 hover:border-red-200 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-red-200 focus:ring-offset-1"
            title="Clear conversation"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24">
              <path
                fill="currentColor"
                d="M16 9v10H8V9zm-1.5-6h-5l-1 1H5v2h14V4h-3.5zM18 7H6v12c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2z"
              />
            </svg>
          </button>
        </div>

        <!-- Empty State -->
        <div v-if="conversations.length === 0 && !isCollapsed" class="p-4 text-center text-gray-400 text-sm">
          No conversations yet
        </div>
      </div>
    </div>

    <!-- Main Content Area (Chatbot) -->
    <div class="w-[50vmin] flex flex-col flex-shrink-0">
      <Chatbot
        :key="selectedConversationId || 'empty'"
        v-model:open="isChatbotOpen"
        :conversation-id="selectedConversationId"
        :platform="selectedPlatform"
        :update-metadata="updateConversationMetadata"
        @close-chatbot-list="closeChatbotList"
        @conversation-cleared="handleConversationCleared"
      />
    </div>
  </div>

  <!-- Confirm clear overlay using Warning modal -->
  <teleport to="body">
    <WarningModal :show="showConfirmClear" @close="cancelClearConversation" icon="delete">
      <template #header>
        Clear Conversation
      </template>
      <template #content>
        Are you sure you want to clear this conversation? This action cannot be undone.
      </template>
      <template #footer>
        <button
          @click="confirmClearConversation"
          class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
          type="button"
        >
          Clear
        </button>
        <button
          @click="cancelClearConversation"
          class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:text-gray-400 sm:mt-0 sm:w-auto"
          type="button"
        >
          Cancel
        </button>
      </template>
    </WarningModal>
  </teleport>

  <!-- Conversation Rating Modal (same as Chatbot.vue) -->
  <teleport to="body">
    <div v-if="showConversationRating" class="fixed inset-0 z-[1100] overflow-y-auto">
      <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <!-- Backdrop -->
        <div @click="cancelConversationRating" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <!-- Modal Content -->
        <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
          <!-- Decorative Header -->
          <div class="bg-gradient-to-r from-#257556 to-#257556 px-6 py-5 border-b border-blue-100">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" style="color: #257556" viewBox="0 0 24 24">
                    <path fill="#257556" d="m9.94 12.646l-2.248-.749c-2.353-.784-3.53-1.176-3.53-1.897c0-.72 1.177-1.113 3.53-1.897l8.513-2.838c1.656-.552 2.484-.828 2.921-.391s.161 1.265-.39 2.92l-2.839 8.514c-.784 2.353-1.176 3.53-1.897 3.53c-.72 0-1.113-1.177-1.897-3.53l-.75-2.247l4.354-4.354a1 1 0 0 0-1.414-1.414z" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-xl font-semibold text-gray-900">Before you go...</h3>
                  <p class="text-sm text-gray-600 mt-1">How was your chat experience?</p>
                </div>
              </div>
              <button @click="cancelConversationRating" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Modal Body -->
          <div class="px-6 py-6">
            <!-- Star Rating -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-3 text-center">
                Rate your experience
              </label>
              <div class="flex justify-center space-x-2">
                <button
                  v-for="star in 5"
                  :key="star"
                  @click="conversationRating = star"
                  class="group relative transition-all duration-200 transform hover:scale-110 focus:outline-none"
                  :class="conversationRating >= star ? 'scale-110' : ''"
                >
                  <svg
                    class="w-12 h-12 transition-all duration-200"
                    :class="conversationRating >= star ? 'text-yellow-400 drop-shadow-lg' : 'text-gray-300 group-hover:text-yellow-200'"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <span class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                    {{ star === 1 ? 'Poor' : star === 2 ? 'Fair' : star === 3 ? 'Good' : star === 4 ? 'Very Good' : 'Excellent' }}
                  </span>
                </button>
              </div>

              <p v-if="conversationRating > 0" class="text-center mt-4 text-sm font-medium text-gray-700 animate-fade-in">
                {{ conversationRating === 1 ? '😞 Poor' : conversationRating === 2 ? '😐 Fair' : conversationRating === 3 ? '🙂 Good' : conversationRating === 4 ? '😊 Very Good' : '🌟 Excellent' }}
              </p>
            </div>

            <!-- Optional Comment -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Tell us more (optional)
              </label>
              <textarea
                v-model="conversationRatingComment"
                rows="3"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-[#257556] focus:border-transparent resize-none text-sm"
                placeholder="What did you like or what could we improve?"
              ></textarea>
              <p class="mt-2 text-xs text-gray-500">Your feedback helps us improve ACT AI</p>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="bg-gray-50 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-between gap-3">
            <button
              @click="skipConversationRating"
              class="inline-flex justify-center items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
            >
              Skip & Clear
            </button>

            <div class="flex gap-3">
              <button
                @click="cancelConversationRating"
                class="inline-flex justify-center items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
              >
                Cancel
              </button>

              <button
                @click="submitConversationRating"
                :disabled="conversationRating === 0"
                class="inline-flex justify-center items-center px-6 py-2.5 text-sm font-semibold text-white rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all"
                :class="conversationRating > 0 ? 'focus:ring-[#257556] hover:opacity-90' : 'bg-gray-300 cursor-not-allowed'"
                :style="conversationRating > 0 ? 'background: linear-gradient(to right, #257556, #2a7d84);' : ''"
              >
                <svg v-if="conversationRating > 0" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Submit & Clear
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </teleport>

  <!-- Confirm Clear All Conversations Modal -->
  <teleport to="body">
    <WarningModal :show="showConfirmClearAll" @close="cancelClearAllConversations" icon="delete">
      <template #header>
        Clear All Conversations
      </template>
      <template #content>
        Are you sure you want to delete all conversations? This will clear all {{ conversations.length }} conversation(s). This action cannot be undone.
      </template>
      <template #footer>
        <button
          @click="confirmClearAllConversations"
          :disabled="isClearingAll"
          class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
          type="button"
        >
          <span v-if="isClearingAll">Clearing...</span>
          <span v-else>Delete All</span>
        </button>
        <button
          @click="cancelClearAllConversations"
          :disabled="isClearingAll"
          class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:text-gray-400 sm:mt-0 sm:w-auto disabled:opacity-50"
          type="button"
        >
          Cancel
        </button>
      </template>
    </WarningModal>
  </teleport>
</template>

<style scoped>
/* Smooth transitions for collapse */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>