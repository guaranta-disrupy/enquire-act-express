<script setup>
import { ref, onMounted, watch, nextTick, onUnmounted, computed, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { usePage, router } from '@inertiajs/vue3';
import WarningModal from '@/S4/Warning.vue';
import '../../../css/chatbot.css';
import ChatbotModal from './ChatbotModal.vue';
import AISuggestive from './AISuggestive.vue';
import TooltipChat from '@/S4/TooltipChat.vue';
import ChatBotMenu from './ChatBotMenu.vue';
import CustomerSupport, { detectCustomerSupportRequest } from './CustomerSupport.vue';
import ChatbotList from './ChatbotList.vue';
import { useSidebarState } from './composables/useChatbotStatePersistence.js';
import {
    detectWebsitePositionFromSidebar,
    detectWebsitePositionFromPath,
    formatTimestamp as formatTimestampUtil,
    truncateText,
    stripHtml,
    formatFileSize,
    clamp,
} from './composables/chatbotUiUtils.js';
import {
    CUSTOMER_SUPPORT_STATE_KEY,
    restoreTermsModalState,
    persistTermsModalState,
    persistCustomerSupportState,
} from './composables/chatbotStorageUtils.js';
import { 
    PartSuggestionBubble, 
    
} from './ChatReusables.vue';
import aiLogo from '../../../images/ai.png';

const props = defineProps({
    open: {
        type: Boolean,
        default: false
    },
    conversationId: {
        type: String,
        default: null
    },
    platform: {
        type: String,
        default: 'act'
    },
    updateMetadata: {
        type: Function,
        default: null
    },
    anchor: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['update:open', 'close-chatbot-list', 'conversation-cleared']);

// Sidebar State Management (using composable for persistence)
const showSidebar = useSidebarState();
const chatListRef = ref(null);
const sidebarWidth = ref(showSidebar.value ? 'w-[210px]' : 'w-0');
const MAIN_CONTENT_WIDTH = '31rem';
const COLLAPSED_MODAL_WIDTH = '31rem';
const EXPANDED_MODAL_WIDTH = '44rem';
const modalMaxWidth = ref(showSidebar.value ? EXPANDED_MODAL_WIDTH : COLLAPSED_MODAL_WIDTH);
const isSidebarClosing = ref(false);
const isModalResizing = ref(false);
const mainChatContentRef = ref(null);
const chatLayoutRef = ref(null);
const sidebarContainerRef = ref(null);


// Track current conversation ID - must be declared before watch
const conversationId = ref(null);
// Computed property to ensure conversationId is always available for child components
const currentConversationId = computed(() => conversationId.value || props.conversationId);
const hasActiveConversation = computed(() => !!currentConversationId.value);
let storageKey = '';
// Messages array - must be declared before watch
const messages = ref([]);
const actionData = ref(null);
const customerSupportData = ref(null);
const skipCustomerSupportTransition = ref(false);
const skipNextLoadConversationId = ref(null);
const skipNextSelectionLoad = ref(false);
const customerSupportMessageIndex = computed(() =>
    messages.value.findIndex((msg) => msg?.hasCustomerSupport && msg?.role === 'assistant')
);
const userFirstName = computed(() => user.value?.name?.trim()?.split(/\s+/)?.[0] || 'there');

// Function to load messages for a conversation (Hybrid approach: localStorage first, then MongoDB)
const loadMessages = async () => {
    try {
        console.log('[Chatbot] loadMessages called with conversationId:', conversationId.value);
        
        // Only load from MongoDB if we have a conversation_id starting with 'conv_'
        if (conversationId.value && conversationId.value.startsWith('conv_')) {
            try {
                console.log('[Chatbot] Loading messages from MongoDB for:', conversationId.value);
                const response = await axios.get(route('api.chat.conversation.messages', { conversationId: conversationId.value }));
                
                console.log('[Chatbot] MongoDB response:', response.data);
                
                if (response.data?.success && response.data?.messages && Array.isArray(response.data.messages)) {
                    const mongoMessages = response.data.messages;
                    console.log('[Chatbot] Received messages from MongoDB:', mongoMessages.length);
                    
                    // Normalize MongoDB messages
                    const normalizedMessages = mongoMessages.map(msg => {
                        const timestamp = msg.timestamp || msg.created_at || new Date().toISOString();
                        return {
                            ...msg,
                            liked: msg.liked || false,
                            disliked: msg.disliked || false,
                            feedbackSubmitted: msg.feedbackSubmitted || false,
                            feedbackDisabled: msg.feedbackDisabled || false,
                            isStreaming: false,
                            type: msg.type || 'normal',
                            timestamp: timestamp
                        };
                    });

                    // Sort by timestamp (oldest first)
                    normalizedMessages.sort((a, b) => {
                        const timeA = new Date(a.timestamp).getTime();
                        const timeB = new Date(b.timestamp).getTime();
                        return timeA - timeB;
                    });

                    // Check if any message has hasCustomerSupport flag
                    const hasCustomerSupportMessage = normalizedMessages.some(msg => msg.hasCustomerSupport && msg.role === 'assistant');
                    if (hasCustomerSupportMessage) {
                        customerSupportData.value = { show: true };
                        persistCustomerSupportState(true);
                    } else {
                        customerSupportData.value = null;
                        try {
                            localStorage.removeItem(CUSTOMER_SUPPORT_STATE_KEY);
                        } catch (e) {
                            console.warn('Failed to remove customer support state', e);
                        }
                    }

                    messages.value = normalizedMessages;
                    console.log('[Chatbot] Successfully loaded', normalizedMessages.length, 'messages from MongoDB');
                    return;
                } else {
                    console.warn('[Chatbot] Unexpected response format from MongoDB:', response.data);
                }
            } catch (error) {
                console.error('[Chatbot] Failed to load messages from MongoDB:', error);
                console.error('[Chatbot] Error response:', error.response?.data);
            }
        } else {
            console.log('[Chatbot] Conversation ID not ready for MongoDB load:', {
                hasId: !!conversationId.value,
                startsWithConv: conversationId.value?.startsWith('conv_'),
                conversationId: conversationId.value
            });
            // If no conversationId, clear messages to show empty state
            messages.value = [];
            return;
        }
        
        // Show greeting if no messages loaded (but conversationId exists)
        console.log('[Chatbot] No messages to load, showing greeting');
        messages.value = [{
            role: 'assistant',
            content: getGreeting(),
            type: 'normal',
            liked: false,
            disliked: false,
            feedbackSubmitted: false,
            feedbackDisabled: false,
            isStreaming: false,
            timestamp: new Date().toISOString()
        }];
    } catch (error) {
        console.error('[Chatbot] Error loading messages:', error);
        // Only show greeting if we have a conversationId
        if (conversationId.value) {
            messages.value = [{
                role: 'assistant',
                content: getGreeting(),
                type: 'normal',
                liked: false,
                disliked: false,
                feedbackSubmitted: false,
                feedbackDisabled: false,
                isStreaming: false,
                timestamp: new Date().toISOString()
            }];
        } else {
            messages.value = [];
        }
    }
};

// Watch for conversationId changes to load different conversations
watch(() => props.conversationId, async (newId) => {
    if (newId) {
        // Update internal conversationId when prop changes
        if (conversationId.value !== newId) {
            conversationId.value = newId;
        }
        const conversationKey = newId;
        storageKey = `${props.platform || platform.value}-chat:${conversationKey}`;
        await loadMessages();
        await nextTick();
        scrollToBottom(false);
        focusMessageInput();
    } else {
        conversationId.value = null;
        storageKey = null;
        messages.value = [];
        actionData.value = null;
        customerSupportData.value = null;
        persistCustomerSupportState(false);
    }
}, { immediate: true });

// Handle conversation selection from sidebar
const handleConversationSelected = async (selectedId) => {
    if (!selectedId) {
        conversationId.value = null;
        storageKey = null;
        messages.value = [];
        actionData.value = null;
        customerSupportData.value = null;
        persistCustomerSupportState(false);
        return;
    }

    // Update conversationId
    conversationId.value = selectedId;

    // When conversation is created from composer send flow, skip one reload to avoid
    // replacing in-flight first message UI with backend greeting.
    if (skipNextSelectionLoad.value || skipNextLoadConversationId.value === selectedId) {
        skipNextSelectionLoad.value = false;
        skipNextLoadConversationId.value = null;
        if (window.innerWidth < 768) {
            showSidebar.value = false;
        }
        focusMessageInput();
        return;
    }
    
    // Close sidebar on mobile after selection
    if (window.innerWidth < 768) {
        showSidebar.value = false;
    }
    
    // Load messages for the selected conversation
    await loadMessages();
    await nextTick();
    scrollToBottom(false);
    
    // Focus the message input after selection
    focusMessageInput();
};

// Handle new conversation creation
const handleNewConversation = () => {
    // Create new conversation logic here
    // For now, just close sidebar on mobile
    if (window.innerWidth < 768) {
        showSidebar.value = false;
    }
};

const handleConversationsEmpty = (isEmpty) => {
    if (isEmpty) {
        conversationId.value = null;
        storageKey = null;
        messages.value = [];
        actionData.value = null;
        customerSupportData.value = null;
        persistCustomerSupportState(false);
    }
};

// Toggle Sidebar Function (Fluent-style implementation)
const toggleSidebar = () => {
    const layoutEl = chatLayoutRef.value;
    const sidebarEl = sidebarContainerRef.value;
    const mainEl = mainChatContentRef.value;
    const layoutStyles = layoutEl ? window.getComputedStyle(layoutEl) : null;
    const sidebarStyles = sidebarEl ? window.getComputedStyle(sidebarEl) : null;
    const mainStyles = mainEl ? window.getComputedStyle(mainEl) : null;
    
    // Toggle sidebar state
    showSidebar.value = !showSidebar.value;
    
    if (showSidebar.value) {
        // OPENING SIDEBAR
        // Start modal resize transition first
        isModalResizing.value = true;
        
        // Wait for modal to start resizing, then show sidebar
        // This creates a smooth coordinated animation
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                sidebarWidth.value = 'w-[210px]';  
                isSidebarClosing.value = false;
                modalMaxWidth.value = EXPANDED_MODAL_WIDTH;  
            });
        });
    } else {
        // CLOSING SIDEBAR
        // Start the closing animation
        isSidebarClosing.value = true;
        isModalResizing.value = true;
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                sidebarWidth.value = 'w-0';  
                modalMaxWidth.value = COLLAPSED_MODAL_WIDTH; 
            });
        });
    }
};

// Handle Sidebar Width Transition End
const handleSidebarWidthTransitionEnd = (event) => {
    if (!event || event.propertyName !== 'width') {
        return;
    }
    if (isModalResizing.value) {
        isModalResizing.value = false;
    }
};

// Handle Sidebar Leave (after Vue transition completes)
const handleSidebarLeave = () => {
    // After slide-out animation completes, collapse the width and resize modal
    sidebarWidth.value = 'w-0';
    isSidebarClosing.value = false;
};

// Computed property for modal class to handle sidebar resizing transitions
const modalClass = computed(() => {
    const baseClass = 'chatbot-modal-wrapper';
    const resizingClass = isModalResizing.value ? 'sidebar-resizing' : '';
    return resizingClass ? `${baseClass} ${resizingClass}` : baseClass;
});




// Reactive references for folder, survey, platform, and website position - passed to the backend with the user's message
const folder = ref();
const survey = ref();
const platform = ref();
const websitePosition = ref();
const sessionId = ref(null);
const user = ref();
const expertMode = ref(false); // Expert mode flag

// Toggle expert mode
const toggleExpertMode = () => {
    expertMode.value = !expertMode.value;
    console.log('[Expert Mode] Toggled:', expertMode.value ? 'ON' : 'OFF');
    if (expertMode.value) {
        console.log('[Expert Mode] Expert mode is now active - using research knowledge base');
        attachedFiles.value = [];
    } else {
        addedQuestions.value = [];
    }
};

const storageListener = (e) => {
    if (!storageKey || e.key !== storageKey) return;
    try {
        const parsed = e.newValue ? JSON.parse(e.newValue) : null;
        if (Array.isArray(parsed)) {
            // Normalize messages from storage listener too
            messages.value = parsed.map(msg => ({
                ...msg,
                liked: msg.liked || false,
                disliked: msg.disliked || false,
                feedbackSubmitted: msg.feedbackSubmitted || false,
                feedbackDisabled: msg.feedbackDisabled || false,
                isStreaming: msg.isStreaming || false,
                type: msg.type || 'normal'
            }));
        }
    } catch {}
};

const tokenPercent = ref(0);
const tokenUsed = ref(0);
const tokenLimit = ref(25000);
let tokenUpdateInterval = null;


// Timestamp helpers
const currentTime = ref(new Date());
let currentTimeInterval = null;

const startCurrentTimeTicker = () => {
    if (currentTimeInterval) return;
    currentTimeInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 10000);
};

const stopCurrentTimeTicker = () => {
    if (currentTimeInterval) {
        clearInterval(currentTimeInterval);
        currentTimeInterval = null;
    }
};

const showTermsModal = ref(!restoreTermsModalState()); // Show modal if not acknowledged

const formatTimestamp = (timestamp) => {
    return formatTimestampUtil(timestamp, currentTime.value);
};

// Interface-only mode flag and fallbacks
const INTERFACE_ONLY = (window.__CHATBOT_INTERFACE_ONLY__ ?? false);
const toast = window.toast ?? {
    success: (m) => console.log('[toast.success]', m),
    error: (m) => console.error('[toast.error]', m),
    info: (m) => console.log('[toast.info]', m),
};
const route = (typeof window !== 'undefined' && typeof window.route === 'function')
    ? window.route
    : ((name, ...params) => {
        // Fallback explicit paths when Ziggy route helper is unavailable
        switch (name) {
            case 'api.chat.message':
                return '/api/chat/message';
            case 'api.chat.message.stream':
                return '/api/chat/message/stream';
            case 'api.chat.clear':
                return '/api/chat/clear';
            case 'api.chat.conversation-rating':
                return '/api/chat/conversation-rating';
            case 'api.chat.feedback':
                return '/api/chat/feedback';
            case 'api.chat.token-usage':
                return '/api/chat/token-usage';
            case 'api.ai.execute':
                return `/api/ai/execute/${params?.[0] ?? ''}`;
            default:
                return name;
        }
    });


    const fetchTokenUsage = async () => {
    try {
        const { data } = await axios.get(route('api.chat.token-usage'));
        const newUsed = data?.used ?? 0;
        const newLimit = data?.limit ?? 25000;
        
        // Only update if values have changed to avoid unnecessary re-renders
        if (tokenUsed.value !== newUsed || tokenLimit.value !== newLimit) {
            tokenUsed.value = newUsed;
            tokenLimit.value = newLimit;
            const percent = (tokenUsed.value / Math.max(1, tokenLimit.value)) * 100;
            tokenPercent.value = Math.min(100, Math.max(0, parseFloat(percent)));
            console.log('Token usage updated:', { used: newUsed, limit: newLimit, percent: tokenPercent.value });
        }
    } catch (e) {
        console.error('Failed to fetch token usage:', e);
        tokenPercent.value = 0;
    }
};

const startTokenUsagePolling = () => {
    // Clear any existing interval
    if (tokenUpdateInterval) {
        clearInterval(tokenUpdateInterval);
    }
    
    // Poll for token usage updates every 3 seconds
    tokenUpdateInterval = setInterval(() => {
        fetchTokenUsage();
    }, 3000);
    
    console.log('Token usage polling started');
};

const stopTokenUsagePolling = () => {
    if (tokenUpdateInterval) {
        clearInterval(tokenUpdateInterval);
        tokenUpdateInterval = null;
        console.log('Token usage polling stopped');
    }
};

const openTermsModal = () => {
    showTermsModal.value = true;
};

const acknowledgeTerms = () => {
    showTermsModal.value = false;
    persistTermsModalState(true);
};

onMounted(() => {
    console.log(usePage().props);
    folder.value = usePage().props?.folder;
    survey.value = usePage().props?.survey;
    sessionId.value = usePage().props?.session_id || null;
    user.value = usePage().props?.auth?.user;

    console.log('folder id: ' + (folder.value?.id ?? survey.value?.folder?.id));
    console.log('survey id: ' + survey.value?.id);
    console.log('survey uuid: ' + survey.value?.uuid);

    // Compute platform 
    // ENABLE THIS LATER
    // const hostParts = window.location.hostname.split('.');
    // platform.value = hostParts.length >= 3 ? hostParts[0] : null;
    // console.log("platform: ", platform.value);

    //DELETE THIS LATER
    platform.value = 'act';

    // Compute website position using active sidebar item first, then URL fallback
    const fromSidebar = detectWebsitePositionFromSidebar();
    websitePosition.value = fromSidebar || detectWebsitePositionFromPath();

    console.log('website position:', websitePosition.value)

    storageKey = `act-chat:${sessionId.value || 'anon'}`;

    // restore messages from localStorage for this session
    try {
        const saved = localStorage.getItem(storageKey);
        if (saved) {
            const parsed = JSON.parse(saved);
            if (Array.isArray(parsed) && parsed.length) {
                // Normalize messages: ensure all have required properties and fix any stuck isStreaming flags
                messages.value = parsed.map(msg => ({
                    ...msg,
                    liked: msg.liked || false,
                    disliked: msg.disliked || false,
                    feedbackSubmitted: msg.feedbackSubmitted || false,
                    feedbackDisabled: msg.feedbackDisabled || false,
                    isStreaming: false, // Always set to false for loaded messages
                    type: msg.type || 'normal'
                }));
            }
        }
    } catch (e) {
        console.warn('Failed to restore chat from localStorage', e);
    }

    window.addEventListener('storage', storageListener);
    nextTick(() => {
        scrollToBottom();
        autoResize();
        focusMessageInput();
    });
    fetchTokenUsage();
    startCurrentTimeTicker();
});

onUnmounted(() => {
    window.removeEventListener('storage', storageListener);
    stopTokenUsagePolling();
    stopCurrentTimeTicker();
});

// const emit = defineEmits(['update:open']);

const detectMethod = false; 
const getGreeting = () => {
    const userName = user.value?.name?.trim().split(" ")[0];
    const userEmail = user.value?.email;
    console.log('userEmail: ', userEmail);
    console.log('detectMethod: ', detectMethod);
    console.log('userName: ', userName);
    
    const developerEmails = [
        'dexter.orate@splitsecondresearch.com',
        'chrisjunsabanpan.ssr@gmail.com',
        'chrisjun.sabanpan@splitsecondresearch.com',
        'ivantfredondoy.ssr@gmail.com',
        'ivant@splitsecondresearch.com',
        'fred@splitsecondresearch.com'
    ];
    
    const qaEmails = [
        'lorelieamay.ssr@gmail.com',
        'lorelie.amay@splitsecondresearch.com'
    ];
    
    // Developer names (for name-based detection)
    const developerNames = ['Chrisjun', 'Dexter', 'Don', 'CJ', 'Ivant Fred', 'Ivant', 'Fred'];
    
    // QA names (for name-based detection)
    const qaNames = ['Lorelie', 'Lors'];
    
    let isDeveloper = false;
    let isQA = false;
    
    if (detectMethod) {
        isDeveloper = userEmail && developerEmails.includes(userEmail);
        isQA = userEmail && qaEmails.includes(userEmail);
    } else {
        isDeveloper = userName && developerNames.includes(userName);
        isQA = userName && qaNames.includes(userName);
    }
    
    if (isDeveloper) {
        const epicGreetings = [
            `Hello ${userName}! I'm Ace, your ACT AI assistant. How can I help you today?`
            
        ];
        
        const randomGreeting = epicGreetings[Math.floor(Math.random() * epicGreetings.length)];
        return randomGreeting;
    } else if (isQA) {
        const friendlyGreetings = [
            `Hey Lotlot! What's up? Ready to tackle some ACT stuff together? Just dont be angry with me.`,
            `Hi Lotlot! How can I help you today without getting you angry?`,
            `Hey there, Lotlot! Ready to make ACT even more awesome so that you will not be angry? Let's do this!`,
            `Lotlot! Great to see you! What ACT adventure are we heading into today? Just reply in a way that doesn't sound angry, if that's okay with you.`,
            `Hey Lotlot! What can your AI buddy help you with in ACT today? So you will be happy`,
            `Hey Lotlot! Ready to conquer ACT together? Let's make magic happen!`,
            `Lotlot! My favorite person! How can I help you shine in ACT today?`,
            `What adventure awaits us in ACT today Lotlot?`,
        ];
        
        const randomGreeting = friendlyGreetings[Math.floor(Math.random() * friendlyGreetings.length)];
        return randomGreeting;
        // UNTIL HERE
    } else if (userName) {
        // UNTIL HERE
        return `Hello ${userName}! I'm your ACT AI assistant. How can I help you today?`;
    }
    return 'Hello! I\'m your ACT AI assistant. How can I help you today?';
};

const newMessage = ref('');
const loading = ref(false);
const chatContainer = ref(null);
const messageInput = ref(null);
const showConfirmClear = ref(false);
const showConversationRating = ref(false);
const conversationRating = ref(0);
const conversationRatingComment = ref('');
const showFeedbackDialog = ref(false);
const feedbackMessageIndex = ref(null);
const feedbackReasons = ref([]);
const feedbackText = ref('');
const showFeedbackThankYou = ref(false);
const showScrollToBottomButton = ref(false);
const pendingNotificationCount = ref(0);
const customerSupportCardRef = ref(null);
const actionConfirmationRef = ref(null);

// File handling
const selectedFiles = ref([]);
const attachedFiles = ref([]); // Files attached to the current message
const fileInputRef = ref(null);

// Expert mode related
const addedQuestions = ref([]); // Questions added when expert mode is off

// Computed property to check if feedback is valid
const isFeedbackValid = computed(() => {
    return feedbackReasons.value.length > 0;
});

const clearConversation = () => {
    const clearedConversationId = conversationId.value;

    if (clearedConversationId && chatListRef.value?.removeConversation) {
        chatListRef.value.removeConversation(clearedConversationId, platform.value);
    }
    if (clearedConversationId) {
        emit('conversation-cleared', { conversationId: clearedConversationId, platform: platform.value });
    }

    try {
        const key = storageKey || `act-chat:${sessionId.value || 'anon'}`;
        localStorage.removeItem(key);
    } catch (e) {
        console.warn('Failed to clear chat in localStorage', e);
    }
    messages.value = [
        {
            role: 'assistant',
            content: getGreeting(),
            type: 'normal',
            liked: false,
            disliked: false,
            feedbackSubmitted: false,
            feedbackDisabled: false,
            isStreaming: false
        }
    ];
    actionData.value = null;
    conversationId.value = null; // Reset conversation ID
    toast.success('Conversation cleared');
};

const requestClearConversation = () => {
    showConfirmClear.value = true;
};

const confirmClearConversation = () => {
    // Close the first modal and show the rating modal
    showConfirmClear.value = false;
    showConversationRating.value = true;
};

const cancelClearConversation = () => {
    showConfirmClear.value = false;
};

const submitConversationRating = async () => {
    // Submit rating if user provided one
    if (conversationRating.value > 0 && conversationId.value && !INTERFACE_ONLY) {
        try {
            await axios.post(route('api.chat.conversation-rating'), {
                conversation_id: conversationId.value,
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

const skipConversationRating = () => {
    // Skip rating and just clear
    performClearConversation();
};

const cancelConversationRating = () => {
    // Reset rating modal
    showConversationRating.value = false;
    conversationRating.value = 0;
    conversationRatingComment.value = '';
};

const performClearConversation = () => {
    if (INTERFACE_ONLY) {
        clearConversation();
        showConversationRating.value = false;
        conversationRating.value = 0;
        conversationRatingComment.value = '';
        return;
    }
    
    axios.post(route('api.chat.clear'), {
        platform: platform.value,
        conversation_id: conversationId.value,
        scope: null,
    }).catch(() => {
    }).finally(() => {
        clearConversation();
        showConversationRating.value = false;
        conversationRating.value = 0;
        conversationRatingComment.value = '';
    });
};

const disablePreviousFeedback = () => {
    // Disable feedback buttons on all previous assistant messages
    messages.value.forEach((message, index) => {
        if (message.role === 'assistant' && !message.isStreaming) {
            message.feedbackDisabled = true;
        }
    });
};

const toggleLike = async (messageIndex) => {
    const message = messages.value[messageIndex];
    if (message.role === 'assistant' && !message.feedbackSubmitted && !message.feedbackDisabled) {
        if (message.liked) {
            message.liked = false;
        } else {
            message.liked = true;
            message.disliked = false;
            
            // Hide feedback modal if it's open for this message
            if (feedbackMessageIndex.value === messageIndex) {
                showFeedbackDialog.value = false;
                feedbackMessageIndex.value = null;
                feedbackReasons.value = [];
                feedbackText.value = '';
            }
            
            // Send like feedback to backend
            if (conversationId.value && !INTERFACE_ONLY) {
                try {
                    await axios.post('/chat/feedback', {
                        conversation_id: conversationId.value,
                        message_id: message._id || null,
                        type: 'like'
                    });
                    console.log('Like feedback sent successfully');
                } catch (error) {
                    console.error('Failed to send like feedback:', error);
                }
            }
            
            // Show thank you message when liking
            showFeedbackThankYou.value = true;
            setTimeout(() => {
                showFeedbackThankYou.value = false;
            }, 3000); // Hide after 3 seconds
        }
    }
};

const toggleDislike = (messageIndex) => {
    const message = messages.value[messageIndex];
    if (message.role === 'assistant' && !message.feedbackSubmitted && !message.feedbackDisabled) {
        if (message.disliked) {
            message.disliked = false;
        } else {
            message.disliked = true;
            message.liked = false;
            // Show feedback dialog when marking as bad
            showFeedbackDialog.value = true;
            feedbackMessageIndex.value = messageIndex;
            feedbackReasons.value = [];
            feedbackText.value = '';
        }
    }
};

// Check if message is the initial greeting
const isGreetingMessage = (message) => {
    if (message.role !== 'assistant') return false;
    
    const greetingContent = message.content.toLowerCase();
    return greetingContent.includes('hello') && 
           (greetingContent.includes('act ai assistant') || 
            greetingContent.includes('how can i help you today'));
};

// Feedback dialog functions
const toggleFeedbackReason = (reason) => {
    const index = feedbackReasons.value.indexOf(reason);
    if (index > -1) {
        feedbackReasons.value.splice(index, 1);
    } else {
        feedbackReasons.value.push(reason);
    }
};

const submitFeedback = async () => {
    if (!isFeedbackValid.value) {
        toast.error('Please select at least one reason for the bad response');
        return;
    }
    
    // Mark the message as having feedback submitted
    if (feedbackMessageIndex.value !== null) {
        const message = messages.value[feedbackMessageIndex.value];
        if (message) {
            message.feedbackSubmitted = true;
        }
        
        // Map frontend category names to backend category names
        const categoryMap = {
            'Harmful': 'harmful',
            'Not accurate': 'not_accurate',
            'Not useful or incomplete': 'not_useful_or_incomplete',
            'Something else': 'something_else'
        };
        
        // Get the first selected reason and map it to backend format
        const backendCategory = feedbackReasons.value.length > 0 
            ? categoryMap[feedbackReasons.value[0]] 
            : 'something_else';
        
        // Send feedback to backend
        if (conversationId.value && !INTERFACE_ONLY) {
            try {
                await axios.post('/chat/feedback', {
                    conversation_id: conversationId.value,
                    message_id: message._id || null,
                    type: 'dislike',
                    category: backendCategory,
                    comment: feedbackText.value.trim() || null
                });
                console.log('Dislike feedback sent successfully');
                toast.success('Feedback submitted successfully');
                
                // Show thank you message for dislike feedback
                showFeedbackThankYou.value = true;
                setTimeout(() => {
                    showFeedbackThankYou.value = false;
                }, 3000); // Hide after 3 seconds
            } catch (error) {
                console.error('Failed to send dislike feedback:', error);
                toast.error('Failed to submit feedback, but your response has been noted');
            }
        } else {
            // Interface only mode or no conversation ID
            console.log('Feedback submitted (interface only):', {
                messageIndex: feedbackMessageIndex.value,
                reasons: feedbackReasons.value,
                category: backendCategory,
                text: feedbackText.value
            });
            toast.success('Feedback submitted successfully');
            
            // Show thank you message for dislike feedback (interface only)
            showFeedbackThankYou.value = true;
            setTimeout(() => {
                showFeedbackThankYou.value = false;
            }, 3000); // Hide after 3 seconds
        }
    }
    
    // Close the dialog
    showFeedbackDialog.value = false;
    feedbackMessageIndex.value = null;
    feedbackReasons.value = [];
    feedbackText.value = '';
};

const cancelFeedback = () => {
    // Reset the dislike state when canceling
    if (feedbackMessageIndex.value !== null) {
        const message = messages.value[feedbackMessageIndex.value];
        if (message) {
            message.disliked = false;
        }
    }
    
    showFeedbackDialog.value = false;
    feedbackMessageIndex.value = null;
    feedbackReasons.value = [];
    feedbackText.value = '';
};

const detectAction = (message) => {
    console.log('🔍 [FRONTEND DEBUG] detectAction called with:', message);
    
    try {
        // Check for new confirmation format
        if (message.show_confirmation && message.confirmation_buttons) {
            console.log('🔍 [FRONTEND DEBUG] Detected confirmation format');
            return {
                ...message,
                actionable_id: null, // No longer using actionable_id
                message: message.message,
                action: message.action,
                scope: message.scope,
                intent: message.intent,
                preview_data: message.preview_data,
                questionnaire_code: message.questionnaire_code,
                confirmation_buttons: message.confirmation_buttons
            };
        }
        
        // Check for legacy actionable_id format
        if (message.actionable_id) {
            console.log('🔍 [FRONTEND DEBUG] Detected legacy actionable_id format');
            return message;
        }
        
        console.log('🔍 [FRONTEND DEBUG] No action detected');
    } catch (e) {
        console.log('🔍 [FRONTEND DEBUG] Error in detectAction:', e);
        // Not valid JSON with action structure
    }
    return null;
};

const executeAction = async () => {
    console.log('🔍 [FRONTEND DEBUG] executeAction called');
    console.log('🔍 [FRONTEND DEBUG] actionData.value:', actionData.value);
    
    if (!actionData.value) {
        console.log('🔍 [FRONTEND DEBUG] No actionData, returning');
        return;
    }
    
    console.log('🔍 [FRONTEND DEBUG] Executing action:', actionData.value);
    
    try {
        if (INTERFACE_ONLY) {
            console.log('Interface only mode - executing mock action');
            messages.value.push({
                role: 'assistant',
                content: '✅ Action executed (mock).',
                type: 'normal'
            });
            toast.success('Action executed (mock)');
        } else {
            // Check if this is the new confirmation format
            if (actionData.value.confirmation_buttons) {
                // Extract test_id from URL if we're on an editor page
                // URL pattern: /survey/{survey_id}/editor/{test_id}/update
                let testId = null;
                const pathSegments = window.location.pathname.split('/').filter(Boolean);
                const editorIndex = pathSegments.indexOf('editor');
                if (editorIndex !== -1 && pathSegments[editorIndex + 1]) {
                    const potentialTestId = pathSegments[editorIndex + 1];
                    // Check if it's a number (test_id)
                    if (/^\d+$/.test(potentialTestId)) {
                        testId = parseInt(potentialTestId, 10);
                    }
                }
                
                // Try to get device info from editor iframe if available
                let activeDevice = actionData.value.active_device || null;
                let currentOrientation = actionData.value.current_orientation || null;
                
                // Check if we're in an editor context by looking for the editor iframe
                const editorIframe = document.getElementById('app1') || 
                                     document.querySelector('iframe[src*="/surveys/"][src*="/preview"]');
                
                if (editorIframe && editorIframe.contentWindow && (!activeDevice || !currentOrientation)) {
                    try {
                        // Try to get device info via postMessage (non-blocking)
                        const deviceInfo = await new Promise((resolve) => {
                            const timeout = setTimeout(() => resolve(null), 100);
                            
                            // Listen for response
                            const messageHandler = (event) => {
                                if (event.data?.type === 'EDITOR_DEVICE_INFO_RESPONSE') {
                                    clearTimeout(timeout);
                                    window.removeEventListener('message', messageHandler);
                                    resolve(event.data.payload);
                                }
                            };
                            
                            window.addEventListener('message', messageHandler);
                            
                            // Request device info
                            editorIframe.contentWindow.postMessage(
                                { type: 'GET_EDITOR_DEVICE_INFO' },
                                '*'
                            );
                        });
                        
                        if (deviceInfo) {
                            activeDevice = activeDevice || deviceInfo.activeDevice;
                            currentOrientation = currentOrientation || deviceInfo.currentOrientation;
                        }
                    } catch (e) {
                        console.warn('Could not get device info from editor iframe:', e);
                    }
                }
                
                // Use the new confirmation endpoint
                const confirmData = {
                    action: actionData.value.action,
                    scope: actionData.value.scope,
                    intent: actionData.value.intent,
                    preview_data: actionData.value.preview_data,
                    questionnaire_code: actionData.value.questionnaire_code || survey.value?.code || survey.value?.questionnaire_code,
                    test_id: testId || actionData.value.test_id || null,
                    active_device: activeDevice,
                    current_orientation: currentOrientation,
                    expert_mode: expertMode.value
                };
                
                console.log('Confirming action with data:', confirmData);
                
                const response = await axios.post(route('api.chat.confirm-action'), confirmData);
                
                console.log('Confirmation response:', response.data);
                
                // Only add ✅ if message doesn't already start with ❌ (error)
                let content = response.data.message.startsWith('❌') 
                    ? response.data.message 
                    : `✅ ${response.data.message}`;
                // Only show "Click here to view" link if it's not an automatic refresh (test_data, styles, or rounds_list)
                if (response.data?.route && response.data?.refresh_type !== 'test_data' && response.data?.refresh_type !== 'styles' && response.data?.refresh_type !== 'rounds_list') {
                    content += `<br><a href="${response.data.route}" class="link">Click here to view</a>`;
                }
                
                messages.value.push({
                    role: 'assistant',
                    content: content,
                    type: 'normal'
                });
                
                toast.success('Action executed successfully');
                
                // If this is a style update, notify the editor iframe to reload styles
                if (response.data?.refresh_type === 'styles' || 
                    actionData.value?.action === 'act_questionnaire_update' || 
                    actionData.value?.action === 'app_questionnaire_update') {
                    // Send message to window (Create2.vue will forward to iframe)
                    window.postMessage(
                        {
                            type: "STYLES_UPDATED"
                        },
                        window.location.origin
                    );
                    
                    // Also try sending directly to iframe as fallback
                    const editorIframeEl = document.getElementById("app1");
                    if (editorIframeEl && editorIframeEl.contentWindow) {
                        try {
                            editorIframeEl.contentWindow.postMessage(
                                {
                                    type: "STYLES_UPDATED"
                                },
                                "*"
                            );
                        } catch (e) {
                            console.warn('Failed to send message directly to iframe:', e);
                        }
                    }
                }
                
                // If this is a test data update (primes, targets, instructions), notify the editor iframe
                if (response.data?.refresh_type === 'test_data' && response.data?.test_id && response.data?.survey_id) {
                    // Send message to window (Create2.vue will forward to iframe)
                    window.postMessage(
                        {
                            type: "TEST_DATA_UPDATED",
                            test_id: response.data.test_id,
                            survey_id: response.data.survey_id
                        },
                        window.location.origin
                    );
                    
                    // Also try sending directly to iframe as fallback
                    const editorIframeEl = document.getElementById("app1");
                    if (editorIframeEl && editorIframeEl.contentWindow) {
                        try {
                            editorIframeEl.contentWindow.postMessage(
                                {
                                    type: "TEST_DATA_UPDATED",
                                    test_id: response.data.test_id,
                                    survey_id: response.data.survey_id
                                },
                                "*"
                            );
                        } catch (e) {
                            console.warn('Failed to send message directly to iframe:', e);
                        }
                    }
                }
                
                // If this is a rounds list update (round created/updated), refresh the rounds list
                if (response.data?.refresh_type === 'rounds_list' && response.data?.survey_id) {
                    // Send message to parent window (Index.vue) to refresh rounds
                    window.postMessage(
                        {
                            type: "ROUNDS_LIST_UPDATED",
                            survey_id: response.data.survey_id
                        },
                        window.location.origin
                    );
                }
                
                // If this is a question creation, refresh the page to show the new question
                if (actionData.value?.action?.startsWith('act_') && actionData.value?.action?.endsWith('_create')) {
                    // Check if we're on the questions page
                    if (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/')) {
                        // Use Inertia router to reload the current page
                        router.reload({ only: ['questions'] });
                    }
                }
                
            } else if (actionData.value.actionable_id) {
                // Legacy actionable_id format
                // Get questionnaire code from survey context using the same logic as sendMessage
                let questionnaireCode = survey.value?.code || 
                                      survey.value?.questionnaire_code || 
                                      survey.value?.questionnaire?.code ||
                                      null;
                
                // If still not found, try to get it from URL parameters
                if (!questionnaireCode) {
                    const urlParams = new URLSearchParams(window.location.search);
                    questionnaireCode = urlParams.get('questionnaire_code') || 
                                      urlParams.get('code') || 
                                      null;
                }
                
                // If still not found, try to get it from the current path
                // The route structure is /survey/{questionnaire_code}/
                if (!questionnaireCode) {
                    const pathSegments = window.location.pathname.split('/');
                    const surveyIndex = pathSegments.indexOf('survey');
                    if (surveyIndex !== -1 && pathSegments[surveyIndex + 1]) {
                        questionnaireCode = pathSegments[surveyIndex + 1];
                    }
                }
                
                // If still not found, try to get it from the questionnaire object in props
                if (!questionnaireCode) {
                    const pageProps = usePage().props;
                    questionnaireCode = pageProps?.questionnaire?.code || 
                                      pageProps?.questionnaire_code ||
                                      null;
                }
                
                console.log('Legacy action execution:', questionnaireCode);
                
                const requestParams = {
                    questionnaire_code: questionnaireCode
                };
                
                const response = await axios.get(route('api.ai.execute', actionData.value.actionable_id), {
                    params: requestParams
                });
                
                console.log('Legacy response:', response.data);
                
                // Only add ✅ if message doesn't already start with ❌ (error)
                let content = response.data.message.startsWith('❌') 
                    ? response.data.message 
                    : `✅ ${response.data.message}`;
                // Only show "Click here to view" link if it's not a test_data refresh (which updates automatically)
                if (response.data?.route && response.data?.refresh_type !== 'test_data') {
                    content += `<br><a href="${response.data.route}" class="link">Click here to view</a>`;
                }
                
                messages.value.push({
                    role: 'assistant',
                    content: content,
                    type: 'normal'
                });
                
                toast.success('Action executed successfully');
                
                // If this is a test data update (primes, targets, instructions), notify the editor iframe
                if (response.data?.refresh_type === 'test_data' && response.data?.test_id && response.data?.survey_id) {
                    // Send message to window (Create2.vue will forward to iframe)
                    window.postMessage(
                        {
                            type: "TEST_DATA_UPDATED",
                            test_id: response.data.test_id,
                            survey_id: response.data.survey_id
                        },
                        window.location.origin
                    );
                    
                    // Also try sending directly to iframe as fallback
                    const editorIframeEl = document.getElementById("app1");
                    if (editorIframeEl && editorIframeEl.contentWindow) {
                        try {
                            editorIframeEl.contentWindow.postMessage(
                                {
                                    type: "TEST_DATA_UPDATED",
                                    test_id: response.data.test_id,
                                    survey_id: response.data.survey_id
                                },
                                "*"
                            );
                        } catch (e) {
                            console.warn('Failed to send message directly to iframe:', e);
                        }
                    }
                }
                
                // If this is a rounds list update (round created/updated), refresh the rounds list
                if (response.data?.refresh_type === 'rounds_list' && response.data?.survey_id) {
                    // Send message to parent window (Index.vue) to refresh rounds
                    window.postMessage(
                        {
                            type: "ROUNDS_LIST_UPDATED",
                            survey_id: response.data.survey_id
                        },
                        window.location.origin
                    );
                }
                
                // If the response contains an editor route, it might be a style update
                // Notify the editor iframe to reload styles
                if (response.data?.route && (response.data.route.includes('/editor') || response.data.route.includes('/update'))) {
                    // Send message to window (Create2.vue will forward to iframe)
                    window.postMessage(
                        {
                            type: "STYLES_UPDATED"
                        },
                        window.location.origin
                    );
                    
                    // Also try sending directly to iframe as fallback
                    const editorIframeEl = document.getElementById("app1");
                    if (editorIframeEl && editorIframeEl.contentWindow) {
                        try {
                            editorIframeEl.contentWindow.postMessage(
                                {
                                    type: "STYLES_UPDATED"
                                },
                                "*"
                            );
                        } catch (e) {
                            console.warn('Failed to send message directly to iframe:', e);
                        }
                    }
                }
            }
        }
    } catch (error) {
        console.error('Error executing action:', error);
        console.error('Error response:', error.response?.data);
        console.error('Error status:', error.response?.status);
        
        messages.value.push({
            role: 'assistant',
            content: 'Sorry, I couldn\'t complete that action.',
            type: 'normal'
        });
        toast.error('Failed to execute action');
    } finally {
        actionData.value = null;
    }
};

const cancelAction = async () => {
    try {
        // Call the cancel endpoint if this is a new confirmation format
        if (actionData.value?.confirmation_buttons) {
            await axios.post(route('api.chat.cancel-action'));
        }
        
        messages.value.push({
            role: 'assistant',
            content: '❌ Action cancelled',
            type: 'normal'
        });
    } catch (error) {
        console.error('Error cancelling action:', error);
        messages.value.push({
            role: 'assistant',
            content: '❌ Action cancelled',
            type: 'normal'
        });
    } finally {
        actionData.value = null;
    }
};

const handleKeydown = (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
};

const autoResize = () => {
    if (messageInput.value) {
        messageInput.value.style.height = 'auto';
        messageInput.value.style.height = Math.min(messageInput.value.scrollHeight, 120) + 'px';
    }
};

// Auto-focus the message input
const focusMessageInput = () => {
    nextTick(() => {
        if (messageInput.value && !loading.value) {
            messageInput.value.focus();
        }
    });
};

// Handle clicks anywhere in chat to refocus input (except on interactive elements)
const handleChatAreaClick = (event) => {
    // Don't refocus if clicking on interactive elements
    const target = event.target;
    const isInteractive = target.closest('button, a, input, select, textarea, [contenteditable="true"]');
    
    // Don't refocus if text is being selected
    const selection = window.getSelection();
    if (selection && selection.toString().length > 0) {
        return;
    }
    
    // Refocus the message input
    if (!isInteractive && !loading.value) {
        focusMessageInput();
    }
};

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files || []);
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = ['.xls', '.xlsx', '.csv', '.xlsm'];
    
    files.forEach(file => {
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        if (!allowedTypes.includes(fileExtension)) {
            toast.error(`File type ${fileExtension} is not allowed. Please upload Excel or CSV files only.`);
            return;
        }
        if (file.size > maxSize) {
            toast.error(`File ${file.name} exceeds 10MB limit.`);
            return;
        }
        if (selectedFiles.value.length < 10) {
            selectedFiles.value.push(file);
        } else {
            toast.error('Maximum 10 files allowed.');
        }
    });
    
    // Reset input
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1);
};

const triggerFileSelect = () => {
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

const ensureActiveConversation = async () => {
    if (conversationId.value) {
        return true;
    }

    if (chatListRef.value?.createNewConversation) {
        // Must be set before creation starts because ChatbotList emits selection immediately.
        skipNextSelectionLoad.value = true;
        const createdConversationId = await chatListRef.value.createNewConversation(platform.value);
        if (createdConversationId) {
            skipNextLoadConversationId.value = createdConversationId;
            conversationId.value = createdConversationId;
            return true;
        }
        skipNextSelectionLoad.value = false;
    }

    try {
        const response = await axios.post(route('api.chat.conversation.create'), {
            platform: platform.value || 'act',
        });
        if (response.data?.conversation_id) {
            skipNextLoadConversationId.value = response.data.conversation_id;
            conversationId.value = response.data.conversation_id;
            return true;
        }
    } catch (error) {
        console.error('Failed to create conversation directly from chat composer:', error);
    }

    return false;
};

const sendMessage = async () => {
    if (newMessage.value.trim() === '' || loading.value) return;
    const hasConversation = await ensureActiveConversation();
    if (!hasConversation) {
        toast.error('Failed to create conversation. Please try again.');
        return;
    }
    // Capture attached files once so all branches can safely access them.
    const filesToSend = expertMode.value ? [] : [...attachedFiles.value];
    const isCustomerSupportRequest = detectCustomerSupportRequest(newMessage.value);
    
    // Add user message to chat
    messages.value.push({
        role: 'user',
        content: newMessage.value,
        type: 'normal',
        timestamp: new Date().toISOString(),
        files: filesToSend.length > 0 ? filesToSend.map(f => ({
            id: f.id,
            name: f.name,
            size: f.size,
            type: f.type,
            file: f.file
        })) : []
    });

   // If customer support is requested, handle it specially
   if (isCustomerSupportRequest) {
        const userMessageContent = newMessage.value;
        
        newMessage.value = '';
        // Clear attached files
        if (!expertMode.value) {
            attachedFiles.value = [];
        }
        
        // Save user message to database
        if (conversationId.value) {
            try {
                await axios.post(route('api.chat.utility-prompt'), {
                    type: 'user_message',
                    conversation_id: conversationId.value,
                    platform: platform.value,
                    user_message: userMessageContent,
                    websitePosition: websitePosition.value || 'dashboard'
                });
            } catch (error) {
                console.error('Failed to save user message for customer support:', error);
                // Continue even if save fails
            }
        }
        
        // Add assistant's initial response message immediately
        // This will trigger the CustomerSupport component to show via hasCustomerSupport flag
        try {
            const response = await axios.post(route('api.chat.utility-prompt'), {
                type: 'support_initial',
                conversation_id: conversationId.value,
                platform: platform.value
            });
            
            if (response.data?.success && response.data?.message) {
                // Add assistant message with hasCustomerSupport flag
                messages.value.push({
                    role: response.data.message.role || 'assistant',
                    content: response.data.message.content,
                    type: response.data.message.type || 'normal',
                    hasCustomerSupport: true,
                    liked: false,
                    disliked: false,
                    feedbackSubmitted: false,
                    feedbackDisabled: false,
                    isStreaming: false,
                    timestamp: response.data.message.timestamp || new Date().toISOString()
                });
                
                // Show customer support card after assistant message is added
                setTimeout(() => {
                    showCustomerSupportCard();
                }, 100);
            }
        } catch (error) {
            console.error('Failed to get initial support message:', error);
            // Fallback: add message locally and show card
            messages.value.push({
                role: 'assistant',
                content: 'How would you like to proceed?',
                type: 'normal',
                hasCustomerSupport: true,
                liked: false,
                disliked: false,
                feedbackSubmitted: false,
                feedbackDisabled: false,
                isStreaming: false,
                timestamp: new Date().toISOString()
            });
            setTimeout(() => {
                showCustomerSupportCard();
            }, 100);
        }
        
        return; // Don't send to backend for normal processing
    }
    
    // Disable feedback on previous messages when new message is sent
    disablePreviousFeedback();
    
    const userQuery = newMessage.value;
    newMessage.value = '';
    
    // Exclude files when expert mode is active
    // filesToSend is ready for future file upload implementation via FormData
    if (!expertMode.value) {
        attachedFiles.value = [];
    }
    loading.value = true;
    
    // Create placeholder message that will be populated during streaming
    const streamingMessageIndex = messages.value.length;
    messages.value.push({
        role: 'assistant',
        content: '',
        type: 'normal',
        liked: false,
        disliked: false,
        feedbackSubmitted: false,
        feedbackDisabled: false,
        isStreaming: true,
        timestamp: new Date().toISOString()
    });
    
    try {
        // Try to get questionnaire code from various sources
        let questionnaireCode = survey.value?.code || 
                              survey.value?.questionnaire_code || 
                              survey.value?.questionnaire?.code ||
                              null;
        
        // If still not found, try to get it from URL parameters
        if (!questionnaireCode) {
            const urlParams = new URLSearchParams(window.location.search);
            questionnaireCode = urlParams.get('questionnaire_code') || 
                              urlParams.get('code') || 
                              null;
        }
        
        // If still not found, try to get it from the current path
        // The route structure is /survey/{questionnaire_code}/
        if (!questionnaireCode) {
            const pathSegments = window.location.pathname.split('/');
            const surveyIndex = pathSegments.indexOf('survey');
            if (surveyIndex !== -1 && pathSegments[surveyIndex + 1]) {
                questionnaireCode = pathSegments[surveyIndex + 1];
            }
        }
        
        // If still not found, try to get it from the questionnaire object in props
        if (!questionnaireCode) {
            const pageProps = usePage().props;
            questionnaireCode = pageProps?.questionnaire?.code || 
                              pageProps?.questionnaire_code ||
                              null;
        }
        
        // Extract test_id from URL if we're on an editor page
        // URL pattern: /survey/{survey_id}/editor/{test_id}/update
        let testId = null;
        const pathSegments = window.location.pathname.split('/').filter(Boolean);
        const editorIndex = pathSegments.indexOf('editor');
        if (editorIndex !== -1 && pathSegments[editorIndex + 1]) {
            const potentialTestId = pathSegments[editorIndex + 1];
            // Check if it's a number (test_id)
            if (/^\d+$/.test(potentialTestId)) {
                testId = parseInt(potentialTestId, 10);
            }
        }
        
        // Try to get device info from editor iframe if available
        let activeDevice = null;
        let currentOrientation = null;
        
        // Check if we're in an editor context by looking for the editor iframe
        const editorIframe = document.getElementById('app1') || 
                             document.querySelector('iframe[src*="/surveys/"][src*="/preview"]');
        
        if (editorIframe && editorIframe.contentWindow) {
            try {
                // Try to get device info via postMessage (non-blocking)
                // The editor iframe should expose this via window.editorStore or postMessage
                const deviceInfo = await new Promise((resolve) => {
                    const timeout = setTimeout(() => resolve(null), 100);
                    
                    // Listen for response
                    const messageHandler = (event) => {
                        if (event.data?.type === 'EDITOR_DEVICE_INFO_RESPONSE') {
                            clearTimeout(timeout);
                            window.removeEventListener('message', messageHandler);
                            resolve(event.data.payload);
                        }
                    };
                    
                    window.addEventListener('message', messageHandler);
                    
                    // Request device info
                    editorIframe.contentWindow.postMessage(
                        { type: 'GET_EDITOR_DEVICE_INFO' },
                        '*'
                    );
                });
                
                if (deviceInfo) {
                    activeDevice = deviceInfo.activeDevice;
                    currentOrientation = deviceInfo.currentOrientation;
                }
            } catch (e) {
                console.warn('Could not get device info from editor iframe:', e);
            }
        }

        
        
        const requestData = {
            message: userQuery,
            platform: platform.value,
            websitePosition: websitePosition.value,
            folder: folder.value?.id ?? survey.value?.folder?.id,
            survey: survey.value?.id,
            surveyuuid: survey.value?.uuid,
            questionnaire_code: questionnaireCode,
            test_id: testId,
            active_device: activeDevice,
            current_orientation: currentOrientation,
            expert_mode: expertMode.value,
            conversation_id: conversationId.value,
            // file_context: fileContext,
        };

        if (expertMode.value) {
            console.log('[Expert Mode] Sending message with expert mode enabled - will use research knowledge base');
        }
        
        // Use fetch API for streaming with Server-Sent Events
        const response = await fetch(route('api.chat.message.stream'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'text/event-stream',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
            body: JSON.stringify(requestData)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        let firstChunkReceived = false;
        let receivedActionData = null;

        try {
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                // Decode the chunk and split by lines
                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';

                // Process each line from the Server-Sent Event stream
                for (const line of lines) {
                    if (line.startsWith('data: ')) {
                        try {
                            const data = JSON.parse(line.slice(6));
                            if (data.action_data) {
                                receivedActionData = data.action_data;
                                if (messages.value[streamingMessageIndex]) {
                                    messages.value.splice(streamingMessageIndex, 1);
                                }
                                loading.value = false;
                                actionData.value = receivedActionData;
                                
                                if (receivedActionData.preview_data && Object.keys(receivedActionData.preview_data).length > 0) {
                                    setTimeout(() => {
                                        actionData.value.loading = false;
                                    }, 500);
                                }
                            }
                            
                            if (data.text && !receivedActionData && messages.value[streamingMessageIndex]) {
                                const text = messages.value[streamingMessageIndex].content + data.text;
                                const isActionMessage = text.toLowerCase().includes('do you want') || 
                                                       text.toLowerCase().includes('would you like') ||
                                                       text.toLowerCase().includes('proceed') ||
                                                       text.toLowerCase().includes('create question');
                                
                                if (isActionMessage && !actionData.value) {
                                    loading.value = false;
                                    actionData.value = { loading: true };
                                }
                                
                                // Hide loading indicator once first chunk arrives
                                if (!firstChunkReceived) {
                                    loading.value = false;
                                    firstChunkReceived = true;
                                }
                                // Append streaming text to the message in real-time
                                messages.value[streamingMessageIndex].content += data.text;
                            }
                            
                            // Check if conversation_id is included in the response
                            if (data.conversation_id && !conversationId.value) {
                                conversationId.value = data.conversation_id;
                                console.log('Conversation ID received:', conversationId.value);
                            }
                            if (data.message_id && messages.value[streamingMessageIndex]) {
                                messages.value[streamingMessageIndex]._id = data.message_id;
                                console.log('Message ID received:', data.message_id);
                            }
                            if (data.done) {
                                if (messages.value[streamingMessageIndex]) {
                                    messages.value[streamingMessageIndex].isStreaming = false;
                                }
                                break;
                            }
                        } catch (e) {
                            console.warn('Failed to parse SSE data:', e);
                        }
                    }
                }
            }
        } catch (streamError) {
            console.error('Error in streaming:', streamError);
        } finally {
            // Always set isStreaming to false when streaming ends, regardless of how it ends
            if (messages.value[streamingMessageIndex]) {
                messages.value[streamingMessageIndex].isStreaming = false;
            }
        }
    } catch (error) {
        console.error('Error sending message:', error);
        // Remove placeholder if it exists
        if (messages.value[streamingMessageIndex] && messages.value[streamingMessageIndex].content === '') {
            messages.value.splice(streamingMessageIndex, 1);
        }
        
        // Determine the error message based on the error type
        let errorMessage = 'Sorry, I encountered an error. Please try again later.';
        
        if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
            // Request timeout
            errorMessage = 'I\'m sorry, the request timed out. Please try again with a shorter message or try again later.';
        } else if (error.response) {
            // Server responded with error status
            if (error.response.status === 401) {
                errorMessage = 'I\'m sorry, there was an authentication issue. Please refresh the page and try again.';
            } else if (error.response.status === 429) {
                errorMessage = 'I\'m sorry, you\'ve reached the rate limit. Please wait a moment and try again.';
            } else if (error.response.status >= 500) {
                errorMessage = 'I\'m sorry, there was a server error. Please try again in a moment.';
            } else if (error.response.data && error.response.data.message) {
                errorMessage = error.response.data.message;
            }
        } else if (error.request) {
            // Network error - no response received
            errorMessage = 'I\'m sorry, I couldn\'t connect to the server. Please check your internet connection and try again.';
        } else {
            // Something else happened
            errorMessage = 'I\'m sorry, something unexpected happened. Please try again.';
        }
        
        messages.value.push({
            role: 'assistant',
            content: errorMessage,
            liked: false,
            disliked: false,
            feedbackSubmitted: false,
            feedbackDisabled: false,
            isStreaming: false,
            timestamp: new Date().toISOString()

        });
        toast.error('Failed to get response from the AI assistant');
    } finally {
        loading.value = false;
        focusMessageInput();
    }
    fetchTokenUsage();
};

//Contact Support Handler
const handleContactSupport = () => {

newMessage.value = 'Contact Support';
sendMessage();
};

const showCustomerSupportCard = () => {
customerSupportData.value = {
    show: true
};

persistCustomerSupportState(true);
// CustomerSupport component will handle its own initialization via onMounted
};

// Note: Initial message is now handled directly in sendMessage function
// No separate handler needed

// Handle Submit Success
const handleSupportSubmitSuccess = (result) => {
try {
    skipCustomerSupportTransition.value = true;
    
    // Remove the message with hasCustomerSupport flag
    const messageIndex = messages.value.findIndex(m => m.hasCustomerSupport);
    if (messageIndex !== -1) {
        messages.value.splice(messageIndex, 1);
    }
    
    // Add the success message and normalize to display in standard chat bubble.
    if (result?.message?.content) {
        messages.value.push({
            role: result.message.role || 'assistant',
            content: result.message.content,
            type: 'normal',
            liked: false,
            disliked: false,
            feedbackSubmitted: false,
            feedbackDisabled: false,
            isStreaming: false,
            timestamp: result.message.timestamp || new Date().toISOString(),
        });
    }
    
    // Clear customer support state
    customerSupportData.value = null;
    persistCustomerSupportState(false);
    
    // Re-enable transitions after a brief delay
    setTimeout(() => {
        skipCustomerSupportTransition.value = false;
    }, 100);
} catch (error) {
    console.error('Failed to handle support submit success:', error);
    toast.error('Failed to process support request response.');
}
};

// Handle Keep Chatting with streaming
const handleSupportKeepChatting = async (result) => {
skipCustomerSupportTransition.value = true;

// Remove the message with hasCustomerSupport flag
const messageIndex = messages.value.findIndex(m => m.hasCustomerSupport);
if (messageIndex !== -1) {
    messages.value.splice(messageIndex, 1);
}

// Get the full message content from backend (utility-prompt API, NOT from Prism)
const fullContent = result?.message?.content || 'Sure thing! We\'ll continue our conversation here. What would you like help with next?';

// Create streaming message placeholder
const streamingMessageIndex = messages.value.length;
messages.value.push({
    role: result?.message?.role || 'assistant',
    content: '',
    type: 'normal', // Use 'normal' type so it displays in the chat bubble
    liked: false,
    disliked: false,
    feedbackSubmitted: false,
    feedbackDisabled: false,
    isStreaming: true, // Start streaming
    hasCustomerSupport: false, // Clear this flag since we're closing support
    timestamp: result?.message?.timestamp || new Date().toISOString()
});

// Clear customer support state immediately
customerSupportData.value = null;
persistCustomerSupportState(false);

// Stream the message character by character
try {
    const streamMessage = async () => {
        const words = fullContent.split(' ');
        let currentText = '';
        
        for (let i = 0; i < words.length; i++) {
            // Add word with space (except for first word)
            currentText += (i === 0 ? '' : ' ') + words[i];
            
            // Update message content
            if (messages.value[streamingMessageIndex]) {
                messages.value[streamingMessageIndex].content = currentText;
                
                // Scroll to bottom as text streams
                await nextTick();
                scrollToBottom();
            }
            
            // Small delay between words for natural streaming effect
            await new Promise(resolve => setTimeout(resolve, 30));
        }
        
        // Mark streaming as complete
        if (messages.value[streamingMessageIndex]) {
            messages.value[streamingMessageIndex].isStreaming = false;
        }
        
        // Final scroll
        await nextTick();
        scrollToBottom();
    };
    
    await streamMessage();
} catch (error) {
    console.error('[Chatbot] Error streaming keep chatting message:', error);
    // Fallback: show full message immediately
    if (messages.value[streamingMessageIndex]) {
        messages.value[streamingMessageIndex].content = fullContent;
        messages.value[streamingMessageIndex].isStreaming = false;
    }
}

// Re-enable transitions after streaming completes
setTimeout(() => {
    skipCustomerSupportTransition.value = false;
}, 100);
};

const isElementBelowChatViewport = (el) => {
    if (!chatContainer.value || !el) return false;
    const containerRect = chatContainer.value.getBoundingClientRect();
    const elementRect = el.getBoundingClientRect();
    return elementRect.bottom > containerRect.bottom - 8;
};

let scrollTimeout = null;
const updateScrollIndicators = () => {
    if (!chatContainer.value) {
        showScrollToBottomButton.value = false;
        pendingNotificationCount.value = 0;
        return;
    }

    const container = chatContainer.value;
    const scrollTop = container.scrollTop;
    const scrollHeight = container.scrollHeight;
    const clientHeight = container.clientHeight;
    
    // Calculate distance to bottom more accurately
    const distanceToBottom = scrollHeight - (scrollTop + clientHeight);
    // Use a slightly larger threshold (20px) to account for rounding and ensure button disappears reliably
    const isAtBottom = distanceToBottom <= 20;

    showScrollToBottomButton.value = !isAtBottom;

    if (isAtBottom) {
        pendingNotificationCount.value = 0;
        return;
    }

    let pendingCount = 0;
    const hasCustomerSupportCard =
        customerSupportData.value &&
        customerSupportMessageIndex.value !== -1 &&
        messages.value[customerSupportMessageIndex.value]?.hasCustomerSupport &&
        messages.value[customerSupportMessageIndex.value]?.role === 'assistant';

    if (hasCustomerSupportCard && isElementBelowChatViewport(customerSupportCardRef.value)) {
        pendingCount += 1;
    }

    if (actionData.value && isElementBelowChatViewport(actionConfirmationRef.value)) {
        pendingCount += 1;
    }

    pendingNotificationCount.value = pendingCount;
};

// Throttled scroll handler for better performance
const handleScroll = () => {
    if (scrollTimeout) {
        clearTimeout(scrollTimeout);
    }
    scrollTimeout = setTimeout(() => {
        updateScrollIndicators();
    }, 50);
};

const scrollToPendingAction = () => {
    const hasCustomerSupportCard =
        customerSupportData.value &&
        customerSupportMessageIndex.value !== -1 &&
        messages.value[customerSupportMessageIndex.value]?.hasCustomerSupport &&
        messages.value[customerSupportMessageIndex.value]?.role === 'assistant';

    if (hasCustomerSupportCard && customerSupportCardRef.value && isElementBelowChatViewport(customerSupportCardRef.value)) {
        customerSupportCardRef.value.scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
    }

    if (actionData.value && actionConfirmationRef.value && isElementBelowChatViewport(actionConfirmationRef.value)) {
        actionConfirmationRef.value.scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
    }

    scrollToBottom(true);
};

const scrollToBottom = (smooth = false) => {
    const container = chatContainer.value;
    if (!container) return;

    if (smooth) {
        container.scrollTo({
            top: container.scrollHeight,
            behavior: 'smooth',
        });
        // Smooth scrolling is async, so refresh indicators after animation.
        setTimeout(() => {
            updateScrollIndicators();
        }, 400);
        return;
    }

    // Keep conversation changes deterministic: jump to latest messages immediately.
    container.scrollTop = container.scrollHeight;
    updateScrollIndicators();
};

const getLastMessageSummary = () => {
    for (let i = messages.value.length - 1; i >= 0; i -= 1) {
        const msg = messages.value[i];
        if (msg?.content && msg.content.trim()) {
            return msg;
        }
    }
    return null;
};

watch(messages, () => {
    scrollToBottom();
    try {
        if (storageKey) {
            localStorage.setItem(storageKey, JSON.stringify(messages.value));
        }
    } catch (e) {
        console.warn('Failed to persist chat to localStorage', e);
    }
    if (chatListRef.value?.updateConversationMetadata && conversationId.value) {
        const lastMsg = getLastMessageSummary();
        if (lastMsg) {
            chatListRef.value.updateConversationMetadata(
                conversationId.value,
                lastMsg.content,
                lastMsg.timestamp,
                messages.value.length
            );
        }
    }
}, { deep: true });

watch(
    [messages, customerSupportData, actionData],
    async () => {
        await nextTick();
        updateScrollIndicators();
    },
    { deep: true }
);

// Watch chatContainer ref to attach scroll listener when it becomes available
watch(() => chatContainer.value, (newContainer, oldContainer) => {
    if (oldContainer) {
        oldContainer.removeEventListener('scroll', handleScroll);
    }
    if (newContainer) {
        newContainer.addEventListener('scroll', handleScroll, { passive: true });
        updateScrollIndicators();
    }
}, { immediate: true });

onMounted(() => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.addEventListener('scroll', handleScroll, { passive: true });
            updateScrollIndicators();
        }
    });
});

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        nextTick(() => {
            // Ensure scroll listener is attached when chatbot opens
            if (chatContainer.value) {
                chatContainer.value.addEventListener('scroll', handleScroll, { passive: true });
            }
            scrollToBottom();
            updateScrollIndicators();
        });
        // Start polling when chatbot is opened
        startTokenUsagePolling();
    } else {
        // Stop polling when chatbot is closed to save resources
        stopTokenUsagePolling();
    }
});


const closeModal = () => {
    emit('update:open', false);
    emit('close-chatbot-list');
};

// draggable modal logic
// const GAP = 10;                 // space between button and modal
// const EDGE = 12;                // viewport padding
const DEFAULT_W = 900;          // fallback if you can’t measure
const DEFAULT_H = 680;

const modalPos = ref({ left: 0, top: 0 });
const modalSize = ref({ w: DEFAULT_W, h: DEFAULT_H });

// optional: attach this to your modal root if you can
const modalRootRef = ref(null);

const measureModal = async () => {
  await nextTick();
  const el = modalRootRef.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  if (rect.width > 0 && rect.height > 0) {
    modalSize.value = { w: rect.width, h: rect.height };
  }
};

const computeModalPos = () => {
  if (!props.anchor) return;

  const vw = window.innerWidth;
  const vh = window.innerHeight;
  const { w, h } = modalSize.value;

  const a = props.anchor;

  // Prefer to open to the LEFT of the button (common for floating buttons on right)
  const openLeft = (a.right + GAP + w > vw - EDGE);

  let left = openLeft
    ? (a.left - GAP - w)
    : (a.right + GAP);

  // Vertical: align modal top to button top by default
  let top = a.top;

  // If it overflows bottom, lift it up
  if (top + h > vh - EDGE) {
    top = (vh - EDGE) - h;
  }

  // If it overflows top, push it down
  if (top < EDGE) top = EDGE;

  // Clamp left too
  left = clamp(left, EDGE, (vw - EDGE) - w);

  modalPos.value = { left, top };
};

watch(
  () => [props.open, props.anchor?.x, props.anchor?.y],
  async () => {
    if (!props.open) return;
    await measureModal();
    computeModalPos();
  },
  { immediate: true }
);

const onViewportChange = () => {
  if (!props.open) return;
  computeModalPos();
};

onMounted(() => {
  window.addEventListener("resize", onViewportChange);
  window.addEventListener("scroll", onViewportChange, true);
  window.addEventListener("resize", updateModalPos);
  window.addEventListener("scroll", updateModalPos, true);
});

onBeforeUnmount(() => {
  if (chatContainer.value) {
    chatContainer.value.removeEventListener('scroll', handleScroll);
  }
  if (scrollTimeout) {
    clearTimeout(scrollTimeout);
  }
  window.removeEventListener("resize", onViewportChange);
  window.removeEventListener("scroll", onViewportChange, true);
  window.removeEventListener("resize", updateModalPos);
  window.removeEventListener("scroll", updateModalPos, true);
  
});

const modalLeft = ref(0);
const modalTop = ref(0);

// tune these
const GAP = 10;
const EDGE = 12;

// Modal width - changes based on sidebar state
const COLLAPSED_MODAL_WIDTH_PX = 496; // 31rem when sidebar is closed
const EXPANDED_MODAL_WIDTH_PX = 704; // 44rem when sidebar is open
const MODAL_H = 650;

// Compute actual modal width based on sidebar state
const getModalWidth = () => showSidebar.value ? EXPANDED_MODAL_WIDTH_PX : COLLAPSED_MODAL_WIDTH_PX;

const updateModalPos = async () => {
  if (!props.open || !props.anchor) return;

  await nextTick();

  const vw = window.innerWidth;
  const vh = window.innerHeight;
  const MODAL_W = getModalWidth(); // Get dynamic modal width based on sidebar state

  const a = props.anchor;

  // open left if near right edge
  const openLeft = a.right + GAP + MODAL_W > vw - EDGE;

  let left = openLeft ? (a.left - GAP - MODAL_W) : (a.right + GAP);
  let top = a.top; // align with button

  // clamp
  left = clamp(left, EDGE, vw - EDGE - MODAL_W);
  top = clamp(top, EDGE, vh - EDGE - MODAL_H);

  modalLeft.value = left;
  modalTop.value = top;
};

watch(
  () => [props.open, props.anchor?.x, props.anchor?.y],
  () => updateModalPos(),
  { immediate: true }
);
</script>

<template>
    <Teleport to="body">
  <div
    v-show="open"
    ref="modalRootRef"
    :style="{
      position: 'fixed',
      left: modalPos.left + 'px',
      top: modalPos.top + 'px',
      zIndex: 10000
    }"
  >
    <ChatbotModal 
        :show="open" 
        @close="closeModal" 
        :max-width="modalMaxWidth"
        :modal-class="modalClass"
        positionMode="anchored"
        :anchorLeft="modalLeft"
        :anchorTop="modalTop"
    >
        <template #header>
            <div class="flex items-center gap-3">
                <!-- Left side: Toggle button -->
                <div class="flex items-center">
                    <!-- Sidebar Toggle Button -->
                    <TooltipChat label="Toggle Conversations">
                        <template #svg>
                            <button type="button" @click.stop="toggleSidebar" class="btn btn-ghost btn-xs">
                            <!-- Expanded state -->
                            <svg v-if="showSidebar" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                                <path d="M4 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm5-2v16"/>
                                <path d="m15 10l-2 2l2 2"/>
                                </g>
                            </svg>

                            <!-- Collapsed state -->
                            <svg v-else xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
                                <path d="M4 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm11-2v16"/>
                                <path d="m9 10l2 2l-2 2"/>
                                </g>
                            </svg>
                            </button>
                        </template>
                    </TooltipChat>
                    
                </div>
                <div class="flex-1 flex items-center ml-12 justify-center gap-3">
                    <div class="avatar">
                        <div class="w-8 h-8 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28px" height="28px" viewBox="0 0 24 24">
                                <path fill="none" stroke="var(--yellow-200)" stroke-linejoin="round" stroke-width="2" d="M15 19c1.2-3.678 2.526-5.005 6-6c-3.474-.995-4.8-2.322-6-6c-1.2 3.678-2.526 5.005-6 6c3.474.995 4.8 2.322 6 6Zm-8-9c.6-1.84 1.263-2.503 3-3c-1.737-.497-2.4-1.16-3-3c-.6 1.84-1.263 2.503-3 3c1.737.497 2.4 1.16 3 3Zm1.5 10c.3-.92.631-1.251 1.5-1.5c-.869-.249-1.2-.58-1.5-1.5c-.3.92-.631 1.251-1.5 1.5c.869.249 1.2.58 1.5 1.5Z" />
                            </svg>
                        </div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="28px" height="28px" viewBox="0 0 24 24">
                            <path fill="none" stroke="#15919A" stroke-linejoin="round" stroke-width="2" d="M15 19c1.2-3.678 2.526-5.005 6-6c-3.474-.995-4.8-2.322-6-6c-1.2 3.678-2.526 5.005-6 6c3.474.995 4.8 2.322 6 6Zm-8-9c.6-1.84 1.263-2.503 3-3c-1.737-.497-2.4-1.16-3-3c-.6 1.84-1.263 2.503-3 3c1.737.497 2.4 1.16 3 3Zm1.5 10c.3-.92.631-1.251 1.5-1.5c-.869-.249-1.2-.58-1.5-1.5c-.3.92-.631 1.251-1.5 1.5c.869.249 1.2.58 1.5 1.5Z" />
                        </svg>
                    <h3 class="text-md -ml-2">{{ platform === 'act' ? 'ACT AI' : 'AI Assistant' }}</h3>
                </div>
                
                <!-- Right side: Menu and Close buttons -->
                <div class="flex items-center gap-2">
                    <ChatBotMenu
                        :conversation-id="currentConversationId"
                        :ai-name="platform === 'act' ? 'ACT AI' : 'AI Assistant'"
                        :platform="platform"
                        @cleared="performClearConversation"
                        @contact-support="handleContactSupport"
                    />
                    <TooltipChat label="Close">
                        <template #svg>
                            <button type="button" @click.stop="closeModal" class="btn btn-ghost btn-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" width="25px" height="25px" viewBox="0 0 24 24" class="text-base-content">
                                <g fill="none" fill-rule="evenodd">
                                <path d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.018-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z" />
                                <path fill="currentColor" d="M2.5 12A1.5 1.5 0 0 1 4 10.5h16a1.5 1.5 0 0 1 0 3H4A1.5 1.5 0 0 1 2.5 12" />
                                </g>
                            </svg>
                            </button>
                        </template>
                    </TooltipChat>
                </div>
            </div>
        </template>

        <template #content>
            <div 
                ref="chatLayoutRef"
                class="flex flex-1 min-h-0 w-full relative overflow-hidden"
                :style="{ width: modalMaxWidth, maxWidth: EXPANDED_MODAL_WIDTH, transition: 'width 200ms cubic-bezier(0.4, 0, 0.2, 1)' }"
            >
                <!-- Sidebar Container -->
                <div 
                    ref="sidebarContainerRef"
                    class="sidebar-container bg-white border-r border-gray-200 flex flex-col flex-shrink-0"
                    :class="sidebarWidth"
                    style="transition: width 200ms cubic-bezier(0.4, 0, 0.2, 1) !important; will-change: width; overflow: hidden;"
                    @transitionend="handleSidebarWidthTransitionEnd"
                >
                    <!-- Vue Transition for slide animation -->
                    <transition
                        enter-active-class="ease-out duration-200"
                        enter-from-class="opacity-0 -translate-x-full"
                        enter-to-class="opacity-100 translate-x-0"
                        leave-active-class="ease-in duration-200"
                        leave-from-class="opacity-100 translate-x-0"
                        leave-to-class="opacity-0 -translate-x-full"
                        @before-leave="isSidebarClosing = true"
                        @after-leave="handleSidebarLeave"
                    >
                        <div v-if="showSidebar" class=" h-full" style="will-change: transform, opacity;">
                            <!-- ChatbotList integrated into sidebar -->
                             
                            <ChatbotList
                                ref="chatListRef"
                                :open="true"
                                :selected-platform="platform"
                                :sidebar-only="true"
                                @switch-platform="(newPlatform) => { platform = newPlatform; }"
                                @conversations-empty="handleConversationsEmpty"
                                @conversation-selected="handleConversationSelected"
                                @new-conversation="handleNewConversation"
                            />
                        </div>
                    </transition>
                </div>

                <!-- Main Chat Area -->
                <div 
                    ref="mainChatContentRef"
                    class="flex flex-col flex-1 min-w-0 min-h-0 main-chat-content relative"
                    :class="{ 'sidebar-closing': isSidebarClosing, 'modal-resizing': isModalResizing }"
                    @click="handleChatAreaClick"
                    :style="{ 
                        padding: '0 0 0 0',
                        width: isModalResizing ? MAIN_CONTENT_WIDTH : 'auto',
                        transition: isModalResizing ? 'width 200ms cubic-bezier(0.4, 0, 0.2, 1)' : 'none'
                    }"
                >
                    <!-- Visual left edge that animates with sidebar -->
                    <div 
                        v-if="showSidebar || isSidebarClosing"
                        class="main-chat-edge absolute left-0 top-0 bottom-0 w-px bg-gray-200 transition-opacity duration-200"
                        :class="{ 'sidebar-closing': isSidebarClosing }"
                    ></div>




        <!-- chat interface -->
		<!-- <div v-show="open" class="fixed bottom-20 left-20 z-[1000] w-[500px] max-w-[90vw] rounded-xl border border-gray bg-white shadow-xl">
			<div class="flex items-center border-b border-gray px-2 py-2 pt-4 w-full">
				<div class="avatar">
					<div class="w-8 h-8 rounded-full flex items-center justify-center">
						<svg xmlns="http://www.w3.org/2000/svg" width="28px" height="28px" viewBox="0 0 24 24">
                            <path fill="none" stroke="#15919A" stroke-linejoin="round" stroke-width="2" d="M15 19c1.2-3.678 2.526-5.005 6-6c-3.474-.995-4.8-2.322-6-6c-1.2 3.678-2.526 5.005-6 6c3.474.995 4.8 2.322 6 6Zm-8-9c.6-1.84 1.263-2.503 3-3c-1.737-.497-2.4-1.16-3-3c-.6 1.84-1.263 2.503-3 3c1.737.497 2.4 1.16 3 3Zm1.5 10c.3-.92.631-1.251 1.5-1.5c-.869-.249-1.2-.58-1.5-1.5c-.3.92-.631 1.251-1.5 1.5c.869.249 1.2.58 1.5 1.5Z" />
                        </svg>
					</div>
				</div>
				<h3 class="font-medium ml-2">Ace</h3>
				<div class="ml-auto flex items-center gap-3 mr-2">
					<div class="tooltip" data-tip="Clear Conversation">
						<button type="button" @click="requestClearConversation" class="">
							<svg xmlns="http://www.w3.org/2000/svg" width="25px" height="25px" viewBox="0 0 24 24"><path fill="#323232" d="M7 21q-.825 0-1.412-.587T5 19V6H4V4h5V3h6v1h5v2h-1v13q0 .825-.587 1.413T17 21zm2-4h2V8H9zm4 0h2V8h-2z"/></svg>
						</button>
					</div>
					<div class="tooltip" data-tip="Close">
						<button type="button" @click="closeModal" class="">
							<svg xmlns="http://www.w3.org/2000/svg" width="25px" height="25px" viewBox="0 0 24 24">
								<g fill="none" fill-rule="evenodd">
									<path d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.018-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z" />
									<path fill="#323232" d="M2.5 12A1.5 1.5 0 0 1 4 10.5h16a1.5 1.5 0 0 1 0 3H4A1.5 1.5 0 0 1 2.5 12" />
								</g>
							</svg>
						</button>
					</div>
				</div>
			</div> -->

			<!-- Body -->
			<div class="relative flex flex-col h-[65vh]">
				<!-- Chat messages -->
				<div ref="chatContainer" class="relative flex-1 overflow-y-auto p-6 mt-2 space-y-6">
                    <div
                        v-if="!hasActiveConversation"
                        class="relative h-full min-h-[18rem] flex items-center justify-center text-center px-6"
                    >
                        <div class="flex absolute top-[10px] sm:top-[-10px] items-center w-full justify-center">
                            <img class="inline-block w-[45vmin] sm:w-[60vmin] max-w-[300px] max-h-[300px] h-auto mx-1 align-middle rounded" :src="aiLogo" alt="AI" />
                            <div class="flex flex-col items-center w-full justify-center text-center absolute top-[220px] sm:top-[250px] max-h-[35vmin]">
                                <h1 class="text-xl sm:text-3xl font-bold">
                                    Hello there, <span class="text-[#15919A] font-bold text-xl sm:text-3xl">{{ userFirstName }}!</span>
                                </h1>
                                <p class="text-sm sm:text-lg text-gray-500 text-center font-semibold mt-2">
                                    How can I help you today?
                                </p>
                            </div>
                        </div>
                    </div>

                    <template v-else>
					<div 
						v-for="(message, index) in messages" 
						:key="index" 
						class="bubble-speech" 
						:class="message.role === 'user' ? 'bubble-right' : 'bubble-left'"
					>
                        <div v-if="message.type === 'normal' && message.content.trim() !== ''" class="chat-bubble" :class="message.role === 'user' ? 'bubble-right user-bubble' : 'bubble-left'">
							<div class="prose prose-sm text-black [&_*]:!text-black" v-html="message.content.replace(/\n/g, '<br>')"></div>
                            <div v-if="message.timestamp" class="mt-1 text-[11px] text-gray-500" :class="message.role === 'user' ? 'text-right' : 'text-left'">
                                {{ formatTimestamp(message.timestamp) }}
                            </div>
							<!-- Like/Dislike buttons for AI responses (exclude greeting message and streaming messages) -->
							<div v-if="message.role === 'assistant' && !isGreetingMessage(message) && !message.isStreaming" class="like-dislike-buttons">
								
								<button 
									@click="toggleLike(index)" 
									class="dislike-btn"
									:class="{ 'active': message.liked, 'disabled': message.feedbackSubmitted || message.feedbackDisabled }"
									:disabled="message.feedbackSubmitted || message.feedbackDisabled"
									title="Good Response"
								>
									<!-- Filled dislike icon when active -->
									<svg v-if="message.liked" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
										<path fill="#15919A" fill-rule="evenodd" d="M11.277 4.781A4 4 0 0 1 14.606 3h.213a2 2 0 0 1 1.973 2.329L16.18 9h2.38a3 3 0 0 1 2.942 3.588l-1.2 6A3 3 0 0 1 17.36 21H6a3 3 0 0 1-3-3v-8a1 1 0 0 1 1-1h3.93a1 1 0 0 0 .832-.445zM7 11H5v7a1 1 0 0 0 1 1h1z" clip-rule="evenodd" />
									</svg>
									<!-- Outline dislike icon when inactive -->
									<svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
										<g fill="none">
											<path fill="#9ca3af" d="m15 10l-.986-.164A1 1 0 0 0 15 11zM4 10V9a1 1 0 0 0-1 1zm16.522 2.392l.98.196zM6 21h11.36v-2H6zM18.56 9H15v2h3.56zm-2.573 1.164l.805-4.835L14.82 5l-.806 4.836zM14.82 3h-.214v2h.214zm-3.543 1.781L8.763 8.555l1.664 1.11l2.516-3.774zM7.93 9H4v2h3.93zM3 10v8h2v-8zm17.302 8.588l1.2-6l-1.96-.392l-1.2 6zM8.762 8.555A1 1 0 0 1 7.93 9v2a3 3 0 0 0 2.496-1.336zm8.03-3.226A2 2 0 0 0 14.82 3v2zM18.56 11a1 1 0 0 1 .981 1.196l1.961.392A3 3 0 0 0 18.561 9zm-1.2 10a3 3 0 0 0 2.942-2.412l-1.96-.392a1 1 0 0 1-.982.804zM14.606 3a4 4 0 0 0-3.328 1.781l1.664 1.11A2 2 0 0 1 14.606 5zM6 19a1 1 0 0 1-1-1H3a3 3 0 0 0 3 3z" />
											<path stroke="#9ca3af" stroke-width="2" d="M8 10v10" />
										</g>
									</svg>
								</button>
                                <button 
									@click="toggleDislike(index)" 
									class="like-btn"
									:class="{ 'active': message.disliked, 'disabled': message.feedbackSubmitted || message.feedbackDisabled }"
									:disabled="message.feedbackSubmitted || message.feedbackDisabled"
									title="Bad Response"
								>
									<!-- Filled like icon when active -->
                                    <svg v-if="message.disliked" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
										<path fill="#15919A" fill-rule="evenodd" d="M11.277 19.219A4 4 0 0 0 14.606 21h.213a2 2 0 0 0 1.973-2.329L16.18 15h2.38a3 3 0 0 0 2.942-3.588l-1.2-6A3 3 0 0 0 17.36 3H6a3 3 0 0 0-3 3v8a1 1 0 0 0 1 1h3.93a1 1 0 0 1 .832.445zM7 5v8H5V6a1 1 0 0 1 1-1z" clip-rule="evenodd" />
									</svg>
									
									<!-- Outline like icon when inactive -->
									<svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
										<g fill="none">
											<path fill="#9ca3af" d="m15 14l-.986.164A1 1 0 0 1 15 13zM4 14v1a1 1 0 0 1-1-1zm16.522-2.392l.98-.196zM6 3h11.36v2H6zm12.56 12H15v-2h3.56zm-2.573-1.164l.805 4.835L14.82 19l-.806-4.836zM14.82 21h-.214v-2h.214zm-3.543-1.781l-2.515-3.774l1.664-1.11l2.516 3.774zM7.93 15H4v-2h3.93zM3 14V6h2v8zm17.302-8.588l1.2 6l-1.96.392l-1.2-6zM8.762 15.445A1 1 0 0 0 7.93 15v-2a3 3 0 0 1 2.496 1.336zm8.03 3.226A2 2 0 0 1 14.82 21v-2zM18.56 13a1 1 0 0 0 .981-1.196l1.961-.392A3 3 0 0 1 18.561 15zm-1.2-10a3 3 0 0 1 2.942 2.412l-1.96.392A1 1 0 0 0 17.36 5zm-2.754 18a4 4 0 0 1-3.328-1.781l1.664-1.11a2 2 0 0 0 1.664.891zM6 5a1 1 0 0 0-1 1H3a3 3 0 0 1 3-3z" />
											<path stroke="#9ca3af" stroke-width="2" d="M8 14V4" />
										</g>
									</svg>
								</button>
							</div>
						</div>
					</div>
                    </template>

                    <!-- Customer Support Card - Show after assistant reply, before actionData -->
					<!-- Only show if there's a message with hasCustomerSupport flag -->
					<div
                        v-if="customerSupportData && customerSupportMessageIndex !== -1 && messages[customerSupportMessageIndex]?.hasCustomerSupport && messages[customerSupportMessageIndex]?.role === 'assistant'"
                        ref="customerSupportCardRef"
                        class="mt-4 px-2 flex justify-start"
                    >
						<template v-if="!skipCustomerSupportTransition">
							<transition
								appear
								appear-active-class="transition-all duration-300 ease-out"
								appear-from-class="opacity-0 transform scale-95 -translate-y-2"
								appear-to-class="opacity-100 transform scale-100 translate-y-0"
								enter-active-class="transition-all duration-300 ease-out"
								enter-from-class="opacity-0 transform scale-95 -translate-y-2"
								enter-to-class="opacity-100 transform scale-100 translate-y-0"
								leave-active-class="transition-all duration-200 ease-in"
								leave-from-class="opacity-100 transform scale-100 translate-y-0"
								leave-to-class="opacity-0 transform scale-95 -translate-y-2"
							>
								<div class="w-[24rem]">
									<CustomerSupport
										:conversation-id="conversationId"
										:platform="platform"
										@submit-success="handleSupportSubmitSuccess"
										@keep-chatting="handleSupportKeepChatting"
									/>
								</div>
							</transition>
						</template>
						<div v-else class="w-[24rem]">
							<CustomerSupport
								:conversation-id="conversationId"
								:platform="platform"
								@submit-success="handleSupportSubmitSuccess"
								@keep-chatting="handleSupportKeepChatting"
							/>
						</div>
					</div>
					
					<!-- Action confirmation bubble -->
					<div v-if="actionData" ref="actionConfirmationRef" class="bubble-speech bubble-left relative">
						<!-- <div class="chat-bubble bg-white border border-gray-200 shadow-lg">
							<div class="confirmation-content p-4">
								<div v-if="actionData.loading" class="text-center py-8">
									<div class="typing-bubble inline-flex">
										<span class="dot"></span>
										<span class="dot"></span>
										<span class="dot"></span>
									</div>
									<p class="text-sm text-gray-500 mt-4">Preparing Question<br>Please Wait.</p>
								</div>
								
								<div v-else>
									<p class="text-gray-800 text-sm">{{ actionData.message }}</p>
									<div v-if="actionData.preview_data && actionData.preview_data.title_and_question_text" 
										 class="question-preview bg-white p-4 rounded-lg border-2 border-[#e5e7eb] mb-4">
										
										<h4 class="text-sm font-bold text-gray-800 mb-2">
											{{ truncateText(actionData.preview_data.title_and_question_text.name || 'Question', 60) }}
										</h4>
									
										<p class="text-sm text-gray-600 mb-3 line-clamp-2">
											{{ stripHtml(actionData.preview_data.title_and_question_text['question text']) }}
										</p>
										
						
										<div v-if="actionData.preview_data.choices && actionData.preview_data.choices.length > 0" 
											 class="space-y-2 max-h-32 overflow-y-auto border-t pt-3">
											<div v-for="(choice, index) in actionData.preview_data.choices.slice(0, 6)" 
												 :key="index"
												 class="flex items-center gap-2 text-sm">
												<input type="radio" class="focus:ring-[#15919A]" style="color: #15919A;" checked disabled>
												<span class="text-gray-700 truncate">{{ truncateText(choice.text || choice.value || choice.name || choice, 70) }}</span>
											</div>
											<div v-if="actionData.preview_data.choices.length > 6" 
												 class="text-xs text-gray-400 italic pl-6">
												... and {{ actionData.preview_data.choices.length - 6 }} more
											</div>
										</div>
										
								
										<div v-else-if="actionData.preview_data.items && actionData.preview_data.items.length > 0" 
											 class="space-y-2 max-h-32 overflow-y-auto border-t pt-3">
											<div v-for="(item, index) in actionData.preview_data.items.slice(0, 6)" 
												 :key="index"
												 class="flex items-center gap-2 text-sm">
												<input type="checkbox" class="focus:ring-[#15919A]" style="color: #15919A;" checked disabled>
												<span class="text-gray-700 truncate">{{ truncateText(item.text || item.name || item, 70) }}</span>
											</div>
											<div v-if="actionData.preview_data.items.length > 6" 
												 class="text-xs text-gray-400 italic pl-6">
												... and {{ actionData.preview_data.items.length - 6 }} more
											</div>
										</div>
										
								
										<div v-else-if="actionData.preview_data.values && actionData.preview_data.values.length > 0" 
											 class="space-y-2 max-h-32 overflow-y-auto border-t pt-3">
											<div v-for="(value, index) in actionData.preview_data.values.slice(0, 6)" 
												 :key="index"
												 class="flex items-center gap-2 text-sm">
												<input type="checkbox" class="focus:ring-[#15919A]" style="color: #15919A;" checked disabled>
												<span class="text-gray-700 truncate">{{ truncateText(value.text || value.value || value.name || value, 70) }}</span>
											</div>
											<div v-if="actionData.preview_data.values.length > 6" 
												 class="text-xs text-gray-400 italic pl-6">
												... and {{ actionData.preview_data.values.length - 6 }} more
											</div>
										</div>
									</div>
								</div>
							</div>
						</div> -->
						
						<!-- Floating Action Buttons - Outside and Centered -->
						<div v-if="!actionData.loading" class="floating-confirmation-buttons">
							<div class="flex mb-1 bg-white/95 backdrop-blur-md rounded-full shadow-xl border border-gray-200/50 floating-button-container transition-all duration-300">
								<button 
									@click="executeAction" 
									class="confirmation-btn confirmation-btn-success group"
								>
									<svg class="w-3.5 h-3.5 mr-1.5 group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
									</svg>
									<span class="font-medium text-sm">Yes, proceed</span>
								</button>
								<div class="confirmation-divider"></div>
								<button 
									@click="cancelAction" 
									class="confirmation-btn confirmation-btn-error group"
								>
									<svg class="w-3.5 h-3.5 mr-1.5 group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
									</svg>
									<span class="font-medium text-sm">No, cancel</span>
								</button>
							</div>
						</div>
					</div>

					<!-- Feedback Dialog -->
					<div v-if="showFeedbackDialog" class="bubble-speech bubble-left">
						<div class="chat-bubble bg-white border border-gray-200 shadow-md max-w-md">
							<div class="p-4">
								<h3 class="text-md font-semibold text-gray-900 mb-4">Why this response is bad?</h3>
								
								<div class="space-y-3 mb-6">
									<label class="flex items-center">
										<input 
											type="checkbox" 
											:value="'Harmful'"
											@change="toggleFeedbackReason('Harmful')"
											class="rounded border-gray-300 focus:ring-[#15919A]"
											style="color: #15919A;"
										>
										<span class="ml-2 text-sm text-gray-700">Harmful</span>
									</label>
									<label class="flex items-center">
										<input 
											type="checkbox" 
											:value="'Not accurate'"
											@change="toggleFeedbackReason('Not accurate')"
											class="rounded border-gray-300 focus:ring-[#15919A]"
											style="color: #15919A;"
										>
										<span class="ml-2 text-sm text-gray-700">Not accurate</span>
									</label>
									<label class="flex items-center">
										<input 
											type="checkbox" 
											:value="'Not useful or incomplete'"
											@change="toggleFeedbackReason('Not useful or incomplete')"
											class="rounded border-gray-300 focus:ring-[#15919A]"
											style="color: #15919A;"
										>
										<span class="ml-2 text-sm text-gray-700">Not useful or incomplete</span>
									</label>
									<label class="flex items-center">
										<input 
											type="checkbox" 
											:value="'Something else'"
											@change="toggleFeedbackReason('Something else')"
											class="rounded border-gray-300 focus:ring-[#15919A]"
											style="color: #15919A;"
										>
										<span class="ml-2 text-sm text-gray-700">Something else</span>
									</label>
								</div>

								<div class="mb-6">
									<label class="block text-sm font-medium text-gray-700 mb-2">
										Provide additional feedback (optional)
									</label>
									<textarea 
										v-model="feedbackText"
										rows="4"
										class="w-full px-3 py-2 border text-sm border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[#15919A] focus:border-[#15919A] resize-none"
										placeholder="Enter your feedback here..."
									></textarea>
									<p class="mt-1 text-xs text-gray-500">Do not enter personal, confidential, or sensitive information.</p>
								</div>

								<div class="flex justify-end space-x-3">
									<button 
										@click="cancelFeedback"
										class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
									>
										Cancel
									</button>
							<button 
								@click="submitFeedback"
								:disabled="!isFeedbackValid"
								class="px-4 py-2 text-sm font-medium text-white border border-transparent rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#15919A]"
								:class="isFeedbackValid ? 'hover:opacity-90' : 'cursor-not-allowed'"
								:style="isFeedbackValid ? 'background-color: #15919A;' : 'background-color: #9ca3af;'"
							>
								Submit
							</button>
								</div>
							</div>
						</div>
					</div>
					
                    <!-- Single typing indicator for both loading and streaming states -->
                    <div v-if="loading || messages.some(msg => msg.isStreaming && msg.content.trim() === '')" class="bubble-speech bubble-left">
                        <div class="chat-bubble typing-bubble">
                            <span class="dot"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                        </div>
                    </div>
				</div>

                <div v-if="showScrollToBottomButton" class="absolute bottom-[150px] right-4 z-50">
                    <div class="relative">
                        <button
                            @click="scrollToBottom(true)"
                            class="w-8 h-8 rounded-full bg-[#15919A] cursor-pointer text-white shadow-lg hover:shadow-xl hover:scale-110 active:scale-95 transition-all duration-200 flex items-center justify-center"
                            title="Scroll to bottom"
                            type="button"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m6 9l6 6l6-6"/>
                            </svg>
                        </button>

                        <button
                            v-if="pendingNotificationCount > 0"
                            @click.stop="scrollToPendingAction"
                            class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-error text-white flex cursor-pointer items-center justify-center text-[10px] font-bold shadow-lg hover:scale-110 active:scale-95 transition-all duration-200"
                            title="Jump to pending item"
                            type="button"
                        >
                            {{ pendingNotificationCount }}
                        </button>
                    </div>
                </div>
				
				<!-- Thank You Message - Modern & Stylish -->
				<div v-if="showFeedbackThankYou" class="flex justify-center items-center py-6">
					<div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl px-6 py-4 shadow-sm animate-fade-in">
						<div class="flex items-center space-x-3">
							<div class="flex-shrink-0">
								<div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
									<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
										<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
									</svg>
								</div>
							</div>
							<div class="text-center">
								<p class="text-green-800 font-semibold text-sm">Thank you for your feedback!</p>
								<p class="text-green-600 text-xs mt-1">Your input helps us improve</p>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Chat input -->
				<div class="p-2 border-t border-gray mx-1 max-w-full">
					<!-- Unified input container with border -->
					<div class="border border-gray rounded-2xl bg-white  max-w-full">
						<!-- Selected files display -->
						<div v-if="selectedFiles.length > 0" class="px-4 pt-3 pb-2 border-b border-gray">
							<div class="flex flex-wrap gap-2">
								<div 
									v-for="(file, index) in selectedFiles"
									:key="index"
									class="flex items-center gap-2 px-3 py-1.5 bg-base-200 rounded-lg text-xs"
								>
									<svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
									</svg>
									<span class="font-medium">{{ file.name }}</span>
									<span class="text-base-content/60">({{ formatFileSize(file.size) }})</span>
									<button 
										type="button"
										@click="removeFile(index)"
										class="ml-1 hover:text-error transition-colors"
										title="Remove file"
									>
										<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
										</svg>
									</button>
								</div>
							</div>
						</div>
						
						<!-- Top section: Textarea input -->
						<form @submit.prevent="sendMessage" class="relative max-w-full">
							<!-- Hidden file input -->
							<input 
								ref="fileInputRef"
								type="file"
								multiple
								accept=".xls,.xlsx,.csv,.xlsm,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv"
								@change="handleFileSelect"
								class="hidden"
							/>
							
						<textarea 
							ref="messageInput"
							v-model="newMessage"
							@keydown="handleKeydown"
							rows="1"
							placeholder="Ask or command ACT to do task for you..."
							class="w-full text-sm resize-none px-4 pt-3 pb-2 bg-transparent border-none focus:outline-none focus:ring-0 focus:ring-offset-0 overflow-y-auto"
							:class="{ 'opacity-50': loading }"
							:disabled="loading"
							autofocus
							style="max-height: 150px; min-height: 2.5rem; outline: none !important; box-shadow: none !important;"
						></textarea>
							
							<!-- Bottom section: Controls bar -->
							<div class="flex items-center gap-2 px-3 pb-2 border-gray">
								<!-- Water-like Token Usage Indicator -->
								<TooltipChat :label="`Daily token usage: ${tokenUsed.toLocaleString()}/${tokenLimit.toLocaleString()} tokens`">
                                    <template #svg>
                                        <div class="relative group cursor-pointer token-indicator">
										<!-- Outer glow ring -->
										<div class="absolute inset-0 rounded-full opacity-20 animate-pulse" style="background-color: #15919A;"></div>
										
										<!-- Main progress circle -->
										<div class="relative w-10 h-10 rounded-full border-2 border-gray overflow-hidden bg-white shadow-sm">
											<!-- Water-like fill with animation -->
											<div class="absolute bottom-0 left-0 right-0 water-fill"
												:style="`--fill-percentage: ${tokenPercent > 5 ? tokenPercent : 0};`"
												:class="'fill-warning'">
												
												<!-- Multiple wave layers -->
												<div class="absolute inset-0 water-wave wave-1"></div>
												<div class="absolute inset-0 water-wave wave-2"></div>
												<div class="absolute inset-0 water-wave wave-3"></div>
											</div>
											
											<!-- Center content -->
											<div class="absolute inset-0 flex items-center justify-center z-10">
												<span class="text-xs font-bold text-black drop-shadow">{{ parseInt(tokenPercent) }}%</span>
											</div>
										</div>
										
										<!-- Floating particles for high usage -->
										<div v-if="tokenPercent >= 80" class="absolute inset-0 pointer-events-none">
											<div class="particle particle-1"></div>
											<div class="particle particle-2"></div>
											<div class="particle particle-3"></div>
										</div>
									</div>
								  </template>
                                </TooltipChat>

							<!-- Plan Mode Toggle -->
							<TooltipChat :label="expertMode ? 'Expert Mode is enabled' : 'Enable Expert Mode'">
                                    <template #svg>
                                        <button 
                                        @click="toggleExpertMode"
                                        class="flex items-center gap-1 px-2 py-2 rounded-full transition-all duration-200 text-sm whitespace-nowrap h-8 border border-[#15919A]"
                                        :class="expertMode ? 'border-[#15919A] bg-[#15919A] text-white shadow-lg' : 'text-[#15919A] bg-white hover:bg-[#15919A]/10'"
									:disabled="loading"
                                    >
                                        <svg v-if="expertMode" xmlns="http://www.w3.org/2000/svg" width="18px" height="18px" viewBox="0 0 24 24">
                                            <path fill="currentColor" d="M16 21a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2v-1h8zM12.339 2.009A6 6 0 0 0 19.99 9.66q.009.17.01.34a7.97 7.97 0 0 1-1.756 5.001c-.55.686-1.889 1.727-2.185 2.999H7.94c-.297-1.273-1.637-2.315-2.187-3.001a8 8 0 0 1 6.585-12.99m5.19-1.68a.507.507 0 0 1 .942 0l.253.61a4.37 4.37 0 0 0 2.25 2.327l.718.32a.53.53 0 0 1 0 .962l-.76.338a4.36 4.36 0 0 0-2.218 2.25l-.247.566a.506.506 0 0 1-.934 0l-.246-.565a4.36 4.36 0 0 0-2.22-2.251l-.76-.338a.53.53 0 0 1 0-.963l.718-.32A4.37 4.37 0 0 0 17.276.94z" />
											</svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="18px" height="18px" viewBox="0 0 24 24">
                                            <path fill="currentColor" d="M12.339 2.009L12 4a6 6 0 0 0-4.686 9.748c.086.107.806.853.917.974c.997 1.084 1.61 2.076 1.742 3.278h4.054c.132-1.201.745-2.193 1.74-3.277c.113-.122.83-.866.917-.973A5.97 5.97 0 0 0 18 10l1.99-.34q.009.17.01.34a7.97 7.97 0 0 1-1.756 5.001C17.624 15.774 16 17 16 18.5V21a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2v-2.5c0-1.5-1.625-2.727-2.246-3.501a8 8 0 0 1 6.585-12.99M10 21h4v-1h-4zM17.53.328a.507.507 0 0 1 .94 0l.254.612a4.37 4.37 0 0 0 2.25 2.326l.718.32a.53.53 0 0 1 0 .962l-.76.338a4.36 4.36 0 0 0-2.218 2.251l-.247.565a.506.506 0 0 1-.934 0l-.246-.565a4.36 4.36 0 0 0-2.22-2.251l-.76-.338a.53.53 0 0 1 0-.963l.718-.32A4.37 4.37 0 0 0 17.276.94z" />
											</svg>
                                        <span :class="expertMode ? 'text-white' : 'text-[#15919A]'" class="text-xs">Research Mode</span>
										</button>
								  </template>
                                </TooltipChat>

								<!-- Spacer to push action buttons to the right -->
								<div class="flex-1"></div>

								<!-- Action buttons on the right -->
								<div class="flex items-center gap-2">
									<!-- Attachment icon -->
									<!-- <button 
										type="button"
										@click="triggerFileSelect"
										class="p-1.5 hover:bg-base-200 rounded transition-colors relative"
										:disabled="loading"
										:class="{ 'opacity-50': loading }"
										title="Attach Excel file (max 10MB)"
									>
										<svg class="w-5 h-5 text-base-content" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
										</svg>
									
										<span 
											v-if="selectedFiles.length > 0"
											class="absolute -top-1 -right-1 bg-primary text-primary-content text-xs rounded-full w-4 h-4 flex items-center justify-center font-bold"
										>
											{{ selectedFiles.length }}
										</span>
									</button> -->
									
								<!-- Send button -->
								<button 
									type="submit" 
									class="p-2 rounded-lg bg-[#15919A] text-white hover:bg-[#0C7C84] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
									:disabled="loading || newMessage.trim() === ''"
								>
										<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" v-if="!loading" fill="currentColor">
											<path fill-rule="evenodd" d="m9.94 12.646l-2.248-.749c-2.353-.784-3.53-1.176-3.53-1.897c0-.72 1.177-1.113 3.53-1.897l8.513-2.838c1.656-.552 2.484-.828 2.921-.391s.161 1.265-.39 2.92l-2.839 8.514c-.784 2.353-1.176 3.53-1.897 3.53c-.72 0-1.113-1.177-1.897-3.53l-.75-2.247l4.354-4.354a1 1 0 0 0-1.414-1.414z" clip-rule="evenodd" />
										</svg>
										<span v-else class="w-5 h-5 loading loading-spinner loading-xs"></span>
									</button>
								</div>
							</div>
						</form>
					</div>
					<p class="text-xs py-1 text-gray-500 py-2 text-center">By using this you agree to our <button @click="openTermsModal" class="text-[#15919A] hover:underline">terms and conditions</button>.</p>
				</div>
			</div>

			<!-- Terms Modal (Inside Chat Interface) -->
			<transition
				enter-active-class="ease-out duration-300"
				enter-from-class="opacity-0 scale-95"
				enter-to-class="opacity-100 scale-100"
				leave-active-class="ease-in duration-200"
				leave-from-class="opacity-100 scale-100"
				leave-to-class="opacity-0 scale-95"
			>
				<div v-if="showTermsModal" class="absolute inset-0 flex items-center justify-center p-4 bg-gray-500 bg-opacity-75 backdrop-blur-[2px] rounded-xl z-[99999]">
					<!-- Modal Content -->
					<div 
						@click.stop 
						class="bg-white rounded-2xl shadow-[0_0_50px_rgba(0,0,0,0.3)] ring-1 ring-gray-300 w-full max-w-md max-h-[calc(100%-2rem)] overflow-y-auto relative z-[100000]"
					>
						<!-- Header -->
						<div class="px-6 py-4 border-b border-gray-200">
							<h3 class="text-lg font-semibold text-[#15919A]">Chatbot Terms and Conditions</h3>
						</div>
						
						<!-- Content -->
						<div class="px-6 py-4 space-y-4 bg-white">
							<p class="text-sm text-gray-700 text-justify leading-relaxed">
								By using this chatbot, you acknowledge and agree that your conversations may be recorded, monitored, and reviewed by authorized administrators for the purposes of:
							</p>
							<ul class="space-y-2 text-sm text-gray-700 ml-6">
								<li class="flex leading-tight">
									<span class="mr-2 flex-shrink-0">•</span>
									<span class="text-justify">Improving chatbot accuracy and user experience,</span>
								</li>
								<li class="flex leading-tight">
									<span class="mr-2 flex-shrink-0">•</span>
									<span class="text-justify">Ensuring service quality and compliance, and</span>
								</li>
								<li class="flex leading-tight">
									<span class="mr-2 flex-shrink-0">•</span>
									<span class="text-justify">Supporting management and troubleshooting activities.</span>
								</li>
							</ul>
							<p class="text-sm text-gray-700 text-justify leading-relaxed">
								All data collected will be handled in accordance with our Privacy Policy and will not be shared with unauthorized third parties. By continuing to interact with this chatbot, you consent to this data usage and monitoring.
							</p>
						</div>
						
						<!-- Footer -->
						<div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end rounded-b-2xl">
							<button 
								@click="acknowledgeTerms"
								class="px-4 py-2 text-sm font-medium rounded-lg bg-[#15919A] hover:bg-[#0C7C84] text-white transition-colors"
							>
								I acknowledge
							</button>
						</div>
					</div>
				</div>
			</transition>
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

		<!-- Conversation Rating Modal -->
		<teleport to="body">
			<div v-if="showConversationRating" class="fixed inset-0 z-[1100] overflow-y-auto">
				<div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
					<!-- Backdrop -->
					<div @click="cancelConversationRating" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
					
					<!-- Modal Content -->
					<div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
						<!-- Decorative Header -->
						<div class="bg-gradient-to-r from-teal-50 to-cyan-50 px-6 py-5 border-b border-teal-100">
							<div class="flex items-center justify-between">
								<div class="flex items-center space-x-3">
									<div class="flex-shrink-0">
										<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" style="color: #15919A" viewBox="0 0 24 24">
											<path fill="#15919A" d="m9.94 12.646l-2.248-.749c-2.353-.784-3.53-1.176-3.53-1.897c0-.72 1.177-1.113 3.53-1.897l8.513-2.838c1.656-.552 2.484-.828 2.921-.391s.161 1.265-.39 2.92l-2.839 8.514c-.784 2.353-1.176 3.53-1.897 3.53c-.72 0-1.113-1.177-1.897-3.53l-.75-2.247l4.354-4.354a1 1 0 0 0-1.414-1.414z" />
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
									class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-[#15919A] focus:border-transparent resize-none text-sm"
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
									:class="conversationRating > 0 ? 'focus:ring-[#15919A] hover:opacity-90' : 'bg-gray-300 cursor-not-allowed'"
									:style="conversationRating > 0 ? 'background: linear-gradient(to right, #15919A, #0d9488);' : ''"
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
    </ChatbotModal>
     </div>
</Teleport>

</template>
