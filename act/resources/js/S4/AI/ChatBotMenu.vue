<script setup>
import { ref, computed, nextTick, watch, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Helpers from '@/Mixins/Helpers.js';
import WarningModal from '../Warning.vue';
import TooltipChat from '@/S4/TooltipChat.vue';


const { toast } = Helpers();

const props = defineProps({
    conversationId: {
        type: [String, Number],
        default: null
    },
    aiName: {
        type: String,
        required: true
    },
    platform: {
        type: String,
        default: 'act'
    }
});

const emit = defineEmits(['cleared', 'contact-support']);

const user = usePage().props?.auth?.user;
const isRootUser = computed(() => user?.role === 'root');

const showMenu = ref(false);
const buttonRef = ref(null);
const menuPosition = ref({ top: 0, left: 0 });
const isResettingTokens = ref(false);
const showConversationInfoModal = ref(false);
const conversationData = ref(null);
const isLoadingConversation = ref(false);
// Preserve displayed content during modal transition to prevent shrinking
const displayedConversationData = ref(null);
const displayedIsLoading = ref(false);

// Conversation clearing modal state (same as Chatbot.vue and ChatbotList.vue)
const showConfirmClear = ref(false);
const showConversationRating = ref(false);
const conversationRating = ref(0);
const conversationRatingComment = ref('');
const isProcessing = ref(false);

// Calculate menu position based on button position
const updateMenuPosition = async () => {
    if (buttonRef.value && showMenu.value) {
        await nextTick();
        const rect = buttonRef.value.getBoundingClientRect();
        const menuWidth = 120; 
        menuPosition.value = {
            top: rect.bottom + 8,
            left: rect.left - menuWidth - 8 
        };
    }
};


watch(showMenu, async (newVal) => {
    if (newVal) {
        await updateMenuPosition();
    }
});


const handleScrollOrResize = () => {
    if (showMenu.value) {
        updateMenuPosition();
    }
};

onMounted(() => {
    window.addEventListener('scroll', handleScrollOrResize, true);
    window.addEventListener('resize', handleScrollOrResize);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScrollOrResize, true);
    window.removeEventListener('resize', handleScrollOrResize);
});

// Route helper function (same as Chatbot.vue)
const route = (typeof window !== 'undefined' && typeof window.route === 'function')
    ? window.route
    : ((name, params) => {
        // Fallback explicit paths when Ziggy route helper is unavailable
        switch (name) {
            case 'api.chat.clear':
                return '/api/chat/clear';
            case 'api.chat.conversation-rating':
                return '/api/chat/conversation-rating';
            case 'api.chat.get':
                // Handle route parameters - params can be an object { conversation: id } or array
                const conversationId = params?.conversation || (Array.isArray(params) ? params[0] : params) || '';
                return `/api/chat/${conversationId}`;
            case 'api.chat.token-usage':
                return '/api/chat/token-usage';
            case 'api.chat.reset-tokens':
                return '/api/chat/reset-tokens';
            default:
                return `/${name.replace(/\./g, '/')}`;
        }
    });

const handleClearConversation = () => {
    showMenu.value = false;
    // Show confirmation modal first
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
};

// Submit conversation rating and then clear
const submitConversationRating = async () => {
    // Submit rating if user provided one
    if (conversationRating.value > 0 && props.conversationId) {
        try {
            await axios.post(route('api.chat.conversation-rating'), {
                conversation_id: props.conversationId,
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
    showConfirmClear.value = false;
};

// Perform the actual conversation clearing (same pattern as Chatbot.vue)
const performClearConversation = () => {
    if (!props.conversationId) {
        console.warn('No conversation ID to clear');
        return;
    }
    
    isProcessing.value = true;
    
    // Call API to clear the conversation from backend (same as Chatbot.vue)
    axios.post(route('api.chat.clear'), {
        platform: props.platform,
        conversation_id: props.conversationId,
        scope: null,
    }).then((response) => {
        // The API returns the conversation_id that was cleared
        const clearedConversationId = response.data?.conversation_id || props.conversationId;
        
        if (response.data?.success !== false) {
            // Emit event to parent to handle clearing (Chatbot.vue will handle the rest)
            emit('cleared', { conversationId: clearedConversationId, platform: props.platform });
        } else {
            console.error('Failed to clear conversation:', response.data?.message || 'Unknown error');
            toast.error(response.data?.message || 'Failed to clear conversation');
        }
    }).catch((error) => {
        console.error('Failed to clear conversation:', error);
        // Even if API fails, emit event to parent
        emit('cleared', { conversationId: props.conversationId, platform: props.platform });
        toast.error(error.response?.data?.message || 'Failed to clear conversation');
    }).finally(() => {
        isProcessing.value = false;
        showConversationRating.value = false;
        conversationRating.value = 0;
        conversationRatingComment.value = '';
        showConfirmClear.value = false;
    });
};

const handleContactSupport = () => {
    showMenu.value = false;
    emit('contact-support');
};

const handleConversationInfo = async () => {
    showMenu.value = false;
    
    // Debug logging
    console.log('[ChatBotMenu] handleConversationInfo called', {
        conversationId: props.conversationId,
        type: typeof props.conversationId,
        isNull: props.conversationId === null,
        isUndefined: props.conversationId === undefined,
        isEmptyString: props.conversationId === '',
        truthy: !!props.conversationId
    });
    
    // Check if conversationId exists and is not empty
    if (!props.conversationId || (typeof props.conversationId === 'string' && props.conversationId.trim() === '')) {
        console.error('[ChatBotMenu] No conversation ID available:', props.conversationId);
        toast.error('No conversation ID available');
        return;
    }
    
    // Clear previous data when opening (after any previous transition has completed)
    conversationData.value = null;
    displayedConversationData.value = null;
    displayedIsLoading.value = false;
    
    isLoadingConversation.value = true;
    displayedIsLoading.value = true;
    showConversationInfoModal.value = true;
    
    try {
        const conversationIdValue = String(props.conversationId).trim();
        console.log('[ChatBotMenu] Fetching conversation info for:', conversationIdValue);
        const routeUrl = route('api.chat.get', { conversation: conversationIdValue });
        console.log('[ChatBotMenu] Route URL:', routeUrl);
        
        const response = await axios.get(routeUrl, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        
        if (response.data?.success && response.data?.conversation) {
            conversationData.value = response.data.conversation;
            displayedConversationData.value = response.data.conversation;
        } else {
            toast.error('Failed to load conversation data');
            showConversationInfoModal.value = false;
        }
    } catch (error) {
        console.error('Failed to fetch conversation info:', error);
        const errorMsg = error.response?.data?.message || 'Failed to load conversation information. Please try again.';
        toast.error(errorMsg);
        showConversationInfoModal.value = false;
    } finally {
        isLoadingConversation.value = false;
        displayedIsLoading.value = false;
    }
};

const closeConversationInfoModal = () => {
    showConversationInfoModal.value = false;
};

const formattedPlatform = computed(() => {
    if (!displayedConversationData.value?.platform) return 'N/A';
    return displayedConversationData.value.platform.toUpperCase();
});

const formattedDate = computed(() => {
    if (!displayedConversationData.value?.created_at) return 'N/A';
    const date = new Date(displayedConversationData.value.created_at);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
});

const messageCount = computed(() => {
    if (!displayedConversationData.value) return 0;
    const metadataCount = displayedConversationData.value.metadata?.message_count;
    if (metadataCount !== undefined && metadataCount !== null) {
        return metadataCount;
    }
    return displayedConversationData.value.messages?.length || 0;
});

const totalTokens = computed(() => {
    if (!displayedConversationData.value?.metadata?.total_tokens_used) return 0;
    return displayedConversationData.value.metadata.total_tokens_used;
});

const formattedTokens = computed(() => {
    return totalTokens.value.toLocaleString();
});

const feedbackStats = computed(() => {
    if (!displayedConversationData.value?.feedback_summary) {
        return {
            overall_likes: 0,
            overall_dislikes: 0,
            dislike_categories: {
                harmful: 0,
                not_accurate: 0,
                not_useful_or_incomplete: 0,
                something_else: 0
            },
            last_feedback_at: null
        };
    }
    return displayedConversationData.value.feedback_summary;
});

const handleResetTokens = async () => {
    showMenu.value = false;
    
    if (!isRootUser.value) {
        toast.error('Unauthorized: Only root users can reset tokens');
        return;
    }
    
    isResettingTokens.value = true;
    
    try {
        const response = await axios.post(route('api.chat.reset-tokens'), {
            conversation_id: props.conversationId
        });
        
        if (response.data?.success) {
            toast.success(response.data?.message || 'Tokens reset successfully');
        } else {
            toast.error(response.data?.message || 'Failed to reset tokens');
        }
    } catch (error) {
        console.error('Failed to reset tokens:', error);
        const errorMsg = error.response?.data?.message || error.response?.data?.error || 'Failed to reset tokens. Please try again.';
        toast.error(errorMsg);
    } finally {
        isResettingTokens.value = false;
    }
};

// Close menu when clicking outside
const closeMenu = () => {
    showMenu.value = false;
};
</script>

<template>
    <div class="relative">
        <!-- Menu Trigger Button -->
        <TooltipChat
            :label="`Menu`"
            
        >
            <template #svg>
                <button 
                    ref="buttonRef"
                    type="button" 
                    @click.stop="showMenu = !showMenu; updateMenuPosition()"
                    class="btn btn-ghost btn-xs relative"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="text-base-content">
                        <circle cx="6" cy="12" r="2" fill="currentColor"/>
                        <circle cx="12" cy="12" r="2" fill="currentColor"/>
                        <circle cx="18" cy="12" r="2" fill="currentColor"/>
                    </svg>
                </button>
            </template>
        </TooltipChat>

        <!-- Teleport menu to body level to ensure it's above ChatbotList -->
        <Teleport to="body">
            <!-- Dropdown Menu Backdrop -->
            <div 
                v-show="showMenu" 
                class="fixed inset-0 z-[100] bg-transparent" 
                @click="closeMenu"
            ></div>
            
            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
            >
                <div
                    v-show="showMenu"
                    class="fixed w-56 rounded-lg bg-white border border-gray-200 shadow-lg z-[101] overflow-hidden"
                    :style="{ top: `${menuPosition.top}px`, left: `${menuPosition.left}px` }"
                    @click.stop
                >
                <div class="py-2 space-y-2">
                    <!-- Contact Support -->
                    <button
                        @click="handleContactSupport"
                        class="w-full px-4 py-3                                                                                                                                   flex items-center gap-3 text-left hover:bg-base-200/80 active:bg-base-200 transition-all duration-150 group"
                    >
                        <svg class="w-5 h-5 text-base-content/60 group-hover:text-base-content/80 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-sm font-medium text-base-content/90 group-hover:text-base-content transition-colors">Contact Support</span>
                    </button>

                    <!-- Conversation Info -->
                    <button
                        @click="handleConversationInfo"
                        class="w-full px-4 py-3 flex items-center gap-3 text-left hover:bg-base-200/80 active:bg-base-200 transition-all duration-150 group"
                    >
                        <svg class="w-5 h-5 text-base-content/60 group-hover:text-base-content/80 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm font-medium text-base-content/90 group-hover:text-base-content transition-colors">Conversation Info</span>
                    </button>



                    <!-- Divider -->
                    <div class="my-1 mx-4 border-t border-base-300/60"></div>
                    <!-- Clear Conversation -->
                    <button
                        @click="handleClearConversation"
                        class="w-full px-4 py-2.5 flex items-center gap-3 text-left hover:bg-error/10 active:bg-error/15 transition-all duration-150 group"
                    >
                        <svg class="w-5 h-5 text-error/80 group-hover:text-error transition-colors" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M16 9v10H8V9zm-1.5-6h-5l-1 1H5v2h14V4h-3.5zM18 7H6v12c0 1.1.9 2 2 2h8c11 0 2-.9 2-2z"/>
                        </svg>
                        <span class="text-sm font-medium text-error/90 group-hover:text-error transition-colors">Clear Conversation</span>
                    </button>

                    <!-- DEBUG Section -->
                    <div v-if="isRootUser" class="mt-2 pt-2 border-t border-base-300/60">
                        <button
                            @click="handleResetTokens"
                            :disabled="isResettingTokens"
                            class="w-full px-4 py-2.5 flex items-center gap-3 text-left hover:bg-base-200/80 active:bg-base-200 transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent group"
                        >
                            <svg class="w-5 h-5 text-base-content/60 group-hover:text-base-content/80 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span class="text-sm font-medium text-base-content/90 group-hover:text-base-content transition-colors">
                                {{ isResettingTokens ? 'Resetting...' : 'Reset Tokens' }} 
                            </span>
                            <span class="ml-auto text-[10px] text-base-content/50">Debug</span>
                            <span v-if="isResettingTokens" class="ml-auto loading loading-spinner loading-xs text-base-content/50"></span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>
        </Teleport>

        <!-- Conversation Info Modal -->
        <WarningModal :show="showConversationInfoModal" @close="closeConversationInfoModal" icon="info">
            <template #header>
                <div class="flex items-center gap-2">
                    <!-- <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg> -->
                    <h3 class="font-medium text-lg mt-2">Conversation Info</h3>
                </div>
            </template>
            <template #content>
                <div v-if="displayedIsLoading" class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <span class="loading loading-spinner loading-lg text-primary"></span>
                        <p class="mt-4 text-sm text-base-content/60">Loading conversation info...</p>
                    </div>
                </div>
                <div v-else-if="displayedConversationData" class="space-y-6 py-2">
                    <!-- Basic Information Cards -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-semibold text-base-content/70 uppercase tracking-wider flex items-center gap-2">
                            
                            Basic Information
                        </h4>
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Platform Card -->
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100/50 dark:from-blue-900/20 dark:to-blue-800/10 rounded-lg p-4 border border-blue-200/50 dark:border-blue-800/30">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-xs font-medium text-blue-700 dark:text-blue-300 uppercase tracking-wide">Platform</p>
                                </div>
                                <p class="text-lg font-bold text-blue-900 dark:text-blue-100">{{ formattedPlatform }}</p>
                            </div>

                            <!-- Date Created Card -->
                            <div class="bg-gradient-to-br from-purple-50 to-purple-100/50 dark:from-purple-900/20 dark:to-purple-800/10 rounded-lg p-4 border border-purple-200/50 dark:border-purple-800/30">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-xs font-medium text-purple-700 dark:text-purple-300 uppercase tracking-wide">Date Created</p>
                                </div>
                                <p class="text-sm font-semibold text-purple-900 dark:text-purple-100 leading-tight">{{ formattedDate }}</p>
                            </div>

                            <!-- Messages Card -->
                            <div class="bg-gradient-to-br from-green-50 to-green-100/50 dark:from-green-900/20 dark:to-green-800/10 rounded-lg p-4 border border-green-200/50 dark:border-green-800/30">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <p class="text-xs font-medium text-green-700 dark:text-green-300 uppercase tracking-wide">Messages</p>
                                </div>
                                <p class="text-2xl font-bold text-green-900 dark:text-green-100">{{ messageCount }}</p>
                            </div>

                            <!-- Tokens Card -->
                            <div class="bg-gradient-to-br from-orange-50 to-orange-100/50 dark:from-orange-900/20 dark:to-orange-800/10 rounded-lg p-4 border border-orange-200/50 dark:border-orange-800/30">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    <p class="text-xs font-medium text-orange-700 dark:text-orange-300 uppercase tracking-wide">Total Tokens</p>
                                </div>
                                <p class="text-2xl font-bold text-orange-900 dark:text-orange-100">{{ formattedTokens }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Feedback Summary -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-semibold text-base-content/70 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Feedback Summary
                        </h4>
                        
                        <!-- Overall Feedback Cards -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-gradient-to-br from-emerald-50 to-emerald-100/50 dark:from-emerald-900/20 dark:to-emerald-800/10 rounded-lg p-4 border border-emerald-200/50 dark:border-emerald-800/30">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300 uppercase tracking-wide mb-1">Likes</p>
                                        <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ feedbackStats.overall_likes || 0 }}</p>
                                    </div>
                                    <div class="w-10 h-10 rounded-full bg-emerald-200 dark:bg-emerald-800/50 flex items-center justify-center">
                                        <!-- Thumbs up icon from Chatbot.vue -->
                                        <svg v-if="feedbackStats.overall_likes > 0" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" class="text-emerald-700 dark:text-emerald-300">
                                            <path fill="currentColor" fill-rule="evenodd" d="M11.277 4.781A4 4 0 0 1 14.606 3h.213a2 2 0 0 1 1.973 2.329L16.18 9h2.38a3 3 0 0 1 2.942 3.588l-1.2 6A3 3 0 0 1 17.36 21H6a3 3 0 0 1-3-3v-8a1 1 0 0 1 1-1h3.93a1 1 0 0 0 .832-.445zM7 11H5v7a1 1 0 0 0 1 1h1z" clip-rule="evenodd" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" class="text-emerald-600 dark:text-emerald-400">
                                            <g fill="none">
                                                <path fill="currentColor" d="m15 10l-.986-.164A1 1 0 0 0 15 11zM4 10V9a1 1 0 0 0-1 1zm16.522 2.392l.98.196zM6 21h11.36v-2H6zM18.56 9H15v2h3.56zm-2.573 1.164l.805-4.835L14.82 5l-.806 4.836zM14.82 3h-.214v2h.214zm-3.543 1.781L8.763 8.555l1.664 1.11l2.516-3.774zM7.93 9H4v2h3.93zM3 10v8h2v-8zm17.302 8.588l1.2-6l-1.96-.392l-1.2 6zM8.762 8.555A1 1 0 0 1 7.93 9v2a3 3 0 0 0 2.496-1.336zm8.03-3.226A2 2 0 0 0 14.82 3v2zM18.56 11a1 1 0 0 1 .981 1.196l1.961.392A3 3 0 0 0 18.561 9zm-1.2 10a3 3 0 0 0 2.942-2.412l-1.96-.392a1 1 0 0 1-.982.804zM14.606 3a4 4 0 0 0-3.328 1.781l1.664 1.11A2 2 0 0 1 14.606 5zM6 19a1 1 0 0 1-1-1H3a3 3 0 0 0 3 3z" />
                                                <path stroke="currentColor" stroke-width="2" d="M8 10v10" />
                                            </g>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-red-50 to-red-100/50 dark:from-red-900/20 dark:to-red-800/10 rounded-lg p-4 border border-red-200/50 dark:border-red-800/30">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-medium text-red-700 dark:text-red-300 uppercase tracking-wide mb-1">Dislikes</p>
                                        <p class="text-2xl font-bold text-red-900 dark:text-red-100">{{ feedbackStats.overall_dislikes || 0 }}</p>
                                    </div>
                                    <div class="w-10 h-10 rounded-full bg-red-200 dark:bg-red-800/50 flex items-center justify-center">
                                        <!-- Thumbs down icon from Chatbot.vue -->
                                        <svg v-if="feedbackStats.overall_dislikes > 0" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" class="text-red-700 dark:text-red-300">
                                            <path fill="currentColor" fill-rule="evenodd" d="M11.277 19.219A4 4 0 0 0 14.606 21h.213a2 2 0 0 0 1.973-2.329L16.18 15h2.38a3 3 0 0 0 2.942-3.588l-1.2-6A3 3 0 0 0 17.36 3H6a3 3 0 0 0-3 3v8a1 1 0 0 0 1 1h3.93a1 1 0 0 1 .832.445zM7 5v8H5V6a1 1 0 0 1 1-1z" clip-rule="evenodd" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" class="text-red-600 dark:text-red-400">
                                            <g fill="none">
                                                <path fill="currentColor" d="m15 14l-.986.164A1 1 0 0 1 15 13zM4 14v1a1 1 0 0 1-1-1zm16.522-2.392l.98-.196zM6 3h11.36v2H6zm12.56 12H15v-2h3.56zm-2.573-1.164l.805 4.835L14.82 19l-.806-4.836zM14.82 21h-.214v-2h.214zm-3.543-1.781l-2.515-3.774l1.664-1.11l2.516 3.774zM7.93 15H4v-2h3.93zM3 14V6h2v8zm17.302-8.588l1.2 6l-1.96.392l-1.2-6zM8.762 15.445A1 1 0 0 0 7.93 15v-2a3 3 0 0 1 2.496 1.336zm8.03 3.226A2 2 0 0 1 14.82 21v-2zM18.56 13a1 1 0 0 0 .981-1.196l1.961-.392A3 3 0 0 1 18.561 15zm-1.2-10a3 3 0 0 1 2.942 2.412l-1.96.392A1 1 0 0 0 17.36 5zm-2.754 18a4 4 0 0 1-3.328-1.781l1.664-1.11a2 2 0 0 0 1.664.891zM6 5a1 1 0 0 0-1 1H3a3 3 0 0 1 3-3z" />
                                                <path stroke="currentColor" stroke-width="2" d="M8 14V4" />
                                            </g>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dislike Categories -->
                        <div v-if="feedbackStats.dislike_categories && Object.values(feedbackStats.dislike_categories).some(v => v > 0)" class="space-y-2">
                            <p class="text-xs font-medium text-base-content/70 mb-2">Dislike Categories</p>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="flex items-center justify-between p-3 bg-base-100 dark:bg-base-800 rounded-lg border border-base-300 dark:border-base-700 hover:border-red-300 dark:hover:border-red-700/50 transition-colors">
                                    <span class="text-xs font-medium text-base-content/80">Harmful</span>
                                    <span class="text-xs font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 px-2 py-1 rounded-full">{{ feedbackStats.dislike_categories.harmful || 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-base-100 dark:bg-base-800 rounded-lg border border-base-300 dark:border-base-700 hover:border-red-300 dark:hover:border-red-700/50 transition-colors">
                                    <span class="text-xs font-medium text-base-content/80">Not Accurate</span>
                                    <span class="text-xs font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 px-2 py-1 rounded-full">{{ feedbackStats.dislike_categories.not_accurate || 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-base-100 dark:bg-base-800 rounded-lg border border-base-300 dark:border-base-700 hover:border-red-300 dark:hover:border-red-700/50 transition-colors">
                                    <span class="text-xs font-medium text-base-content/80">Not Useful</span>
                                    <span class="text-xs font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 px-2 py-1 rounded-full">{{ feedbackStats.dislike_categories.not_useful_or_incomplete || 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-base-100 dark:bg-base-800 rounded-lg border border-base-300 dark:border-base-700 hover:border-red-300 dark:hover:border-red-700/50 transition-colors">
                                    <span class="text-xs font-medium text-base-content/80">Something Else</span>
                                    <span class="text-xs font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 px-2 py-1 rounded-full">{{ feedbackStats.dislike_categories.something_else || 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Last Feedback -->
                        <div class="bg-base-100 dark:bg-base-800 rounded-lg p-4 border border-base-300 dark:border-base-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary/10 dark:bg-primary/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-base-content/60 mb-1">Last Feedback</p>
                                    <p v-if="feedbackStats.last_feedback_at" class="text-sm font-semibold text-base-content">
                                        {{ new Date(feedbackStats.last_feedback_at).toLocaleDateString('en-US', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        }) }}
                                    </p>
                                    <p v-else class="text-sm font-medium text-base-content/50 italic">No feedback submitted yet</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <svg class="w-12 h-12 text-base-content/30 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm font-medium text-base-content/60">No conversation data available</p>
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <button 
                        class="btn btn-sm text-white hover:opacity-90 transition-opacity px-2 py-1 rounded-lg text-sm" 
                        style="background-color: #257556;"
                        @click="closeConversationInfoModal"
                    >
                        Close
                    </button>
                </div>
            </template>
        </WarningModal>
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
                <button @click="confirmClearConversation"
                    class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
                    type="button">
                    Clear
                </button>
                <button @click="cancelClearConversation"
                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:text-gray-400 sm:mt-0 sm:w-auto"
                    type="button">
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
                    <div class="bg-gradient-to-r from-#257556 to-#257556 px-6 py-5 border-b border-#257556">
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
                                    <!-- Tooltip -->
                                    <span class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                        {{ star === 1 ? 'Poor' : star === 2 ? 'Fair' : star === 3 ? 'Good' : star === 4 ? 'Very Good' : 'Excellent' }}
                                    </span>
                                </button>
                            </div>
                            <!-- Selected rating text -->
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
                                :class="conversationRating > 0 ? 'focus:ring-#257556 hover:opacity-90' : 'bg-gray-300 cursor-not-allowed'"
                                :style="conversationRating > 0 ? 'background: linear-gradient(to right, #257556, #257556);' : ''"
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
</template>
