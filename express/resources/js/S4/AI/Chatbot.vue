<script setup>
import { ref, onMounted, watch, nextTick, onUnmounted, computed } from 'vue';
import axios from 'axios';
import { usePage, router } from '@inertiajs/vue3';
import WarningModal from '../Warning.vue';
import Tooltip from '../Tooltip.vue';
import AISuggestive from './AISuggestive.vue';
import ChatList from './ChatList.vue';
import { 
    PartSuggestionBubble, 
    ConfirmationBubble, 
    FeedbackDialog, 
    PreparingDialog, 
    ThankYouMessage, 
    TermsModal,
    QuestionSuggestionBubble,
    PluralQuestionCountBubble
} from './components';
import CustomerSupport, { detectCustomerSupportRequest } from './components/CustomerSupport.vue';

/**
 * Detect if user wants to cancel/dismiss current action (nevermind, cancel, etc.)
 * @param {string} userMessage - The user's message
 * @returns {boolean} - True if user wants to cancel
 */
const detectNevermindRequest = (userMessage) => {
    const cancelKeywords = [
        'nevermind',
        'never mind',
        'never mind',
        'cancel',
        'dismiss',
        'forget it',
        'forget about it',
        'skip',
        'skip it',
        'not needed',
        'not necessary',
        'ignore',
        'ignore it',
        'disregard',
        'disregard it',
        'stop',
        'stop it',
        'abort',
        'abort it',
        'no thanks',
        'no thank you',
        'not now',
        'maybe later',
        'later',
        'nvm',
        'nm',
    ];
    
    const lowerMessage = userMessage.toLowerCase().trim();
    // Check for exact matches or if message contains any of the keywords
    return cancelKeywords.some(keyword => {
        const keywordLower = keyword.toLowerCase();
        // Exact match or starts with keyword followed by punctuation/space/end
        return lowerMessage === keywordLower || 
               lowerMessage.startsWith(keywordLower + ' ') ||
               lowerMessage.startsWith(keywordLower + '.') ||
               lowerMessage.startsWith(keywordLower + '!') ||
               lowerMessage.startsWith(keywordLower + '?') ||
               lowerMessage.includes(' ' + keywordLower + ' ') ||
               lowerMessage.endsWith(' ' + keywordLower) ||
               lowerMessage.endsWith(' ' + keywordLower + '.') ||
               lowerMessage.endsWith(' ' + keywordLower + '!') ||
               lowerMessage.endsWith(' ' + keywordLower + '?');
    });
};

/**
 * Detect if the user is asking to create/add/generate multiple questions (plural) and whether a count is specified.
 * @param {string} userMessage - The user's message
 * @returns {{ isPlural: boolean, count: number|null }} - isPlural when intent is plural questions; count when a number is specified
 */
const detectPluralQuestionRequest = (userMessage) => {
    const lower = userMessage.toLowerCase().trim();
    const numWordMap = { one: 1, two: 2, three: 3, four: 4, five: 5, six: 6, seven: 7, eight: 8, nine: 9, ten: 10, eleven: 11, twelve: 12, fifteen: 15, twenty: 20 };

    // "create 2 text selection question" or "create two ... question" - number before "question(s)"
    const createNumQuestionMatch = lower.match(/\b(create|add|generate|make|write)\s+(\d+)\s+.+?\bquestions?\b/);
    if (createNumQuestionMatch) {
        const n = parseInt(createNumQuestionMatch[2], 10);
        if (n >= 1 && n <= 20) return { isPlural: n > 1, count: n };
    }
    for (const [word, n] of Object.entries(numWordMap)) {
        if (n >= 2 && n <= 20 && new RegExp(`\\b(create|add|generate|make|write)\\s+${word}\\s+.+?\\bquestions?\\b`).test(lower)) {
            return { isPlural: true, count: n };
        }
    }

    // Create-type verbs + "questions" (plural) - original logic
    const pluralQuestionPattern = /\b(add|create|generate|make|write|insert)\s+(?:me\s+)?(?:some\s+)?(?:a\s+)?(?:few\s+)?(?:more\s+)?questions?\b|\bquestions?\s+(?:to\s+)?(?:add|create|generate|make|write)\b/i;
    const hasPluralIntent = /\bquestions\b/i.test(lower) && (
        /\b(add|create|generate|make|write|insert)\s+.+questions?\b/i.test(lower) ||
        /\bquestions?\s+(?:to\s+)?(add|create|generate|make|write)/i.test(lower) ||
        pluralQuestionPattern.test(lower)
    );
    if (!hasPluralIntent) return { isPlural: false, count: null };
    // Explicit number: "3 questions" or "five questions"
    const digitMatch = lower.match(/(\d+)\s*questions?\b/);
    if (digitMatch) return { isPlural: true, count: parseInt(digitMatch[1], 10) };
    for (const [word, n] of Object.entries(numWordMap)) {
        if (new RegExp(`\\b${word}\\s+questions?\\b`).test(lower)) return { isPlural: true, count: n };
    }
    // Plural "questions" with no number
    return { isPlural: true, count: null };
};

/**
 * Parse a number from a user reply to "How many questions?" (e.g. "5", "five", "3 please").
 * @param {string} reply - The user's reply
 * @returns {number|null} - The parsed count or null
 */
const parseQuestionCountFromReply = (reply) => {
    const t = reply.trim();
    const digitMatch = t.match(/^\s*(\d+)/) || t.match(/(\d+)\s*$/);
    if (digitMatch) return parseInt(digitMatch[1], 10);
    const words = { one: 1, two: 2, three: 3, four: 4, five: 5, six: 6, seven: 7, eight: 8, nine: 9, ten: 10, eleven: 11, twelve: 12, fifteen: 15, twenty: 20 };
    const lower = t.toLowerCase();
    for (const [word, n] of Object.entries(words)) {
        if (lower === word || lower.startsWith(word + ' ') || lower.endsWith(' ' + word)) return n;
    }
    return null;
};

/**
 * Build a single message that includes the question count for the backend (e.g. "add 5 questions about satisfaction").
 * @param {string} originalMessage - The original plural request without a number
 * @param {number} count - How many questions
 * @returns {string} - Message with count inserted before "questions"
 */
const buildCombinedPluralQuestionMessage = (originalMessage, count) => {
    const replaced = originalMessage.replace(/\bquestions\b/i, `${count} questions`);
    const base = replaced !== originalMessage
        ? replaced
        : `create ${count} questions: ${originalMessage}`;
    // Return only the user-facing message; distinct-question instruction is handled via pluralQuestionRequestedCount
    return base;
};

import { AITranslateToast } from '@/composables/AITranslateToast.js';
import '../../../css/chatbot.css';
import aiImg from '../../../assets/icons/ai.png';

// AI Helpbot base URL – align with Fluent: respect page protocol when in browser
function getAiHelpbotBaseUrl() {
    const base = import.meta.env.VITE_AI_HELPBOT_URL || 'http://ai-helpbot.test';
    if (typeof window !== 'undefined' && window.location?.protocol) {
        try {
            const u = new URL(base);
            u.protocol = window.location.protocol;
            return u.origin;
        } catch (_) {}
    }
    return base;
}

// Helper to build ai-helpbot API URLs
const aiHelpbotApiUrl = (endpoint, params = {}) => {
    let url = `${getAiHelpbotBaseUrl()}/api/${endpoint}`;

    Object.keys(params).forEach((key) => {
        url = url.replace(`{${key}}`, params[key]);
    });

    return url;
};

// Helper to get current user ID
const getUserId = () => {
    const page = usePage();
    const currentUser = page.props?.auth?.user;
    return currentUser?.id || null;
};

// Helper to get default headers for ai-helpbot API calls
const getAiHelpbotHeaders = (additionalHeaders = {}) => {
    const headers = {
        Accept: additionalHeaders.Accept || 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...additionalHeaders,
    };

    const userId = getUserId();
    if (userId) {
        headers['X-User-Id'] = userId.toString();
    }

    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');
    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }

    return headers;
};

function getAuthTokenForHelpbot() {
    const user = usePage().props?.auth?.user;
    const __tokens = user?.__tokens;
    if (!__tokens) throw new Error('Not authenticated. Bearer token required for AI Helpbot.');
    const token = typeof __tokens === 'string' ? __tokens : (__tokens.express ?? __tokens['express'] ?? __tokens.fluent ?? __tokens['fluent'] ?? Object.values(__tokens)[0]);
    if (!token) throw new Error('No bearer token available for AI Helpbot.');
    return token;
}

const props = defineProps({
    open: {
        type: Boolean,
        default: false
    }
});

// Map sidebar labels to Neo4j :Page {name} values within EXPRESS
const PAGE_MAP = {
    'Questionnaire': 'questionnaire',
    'Question Templates': 'question templates',
    'Themes': 'themes',
    'Gallery': 'gallery',
    'Settings': 'express settings',
    'Advanced': 'advanced',
    'Full Preview': 'preview',
    'gallery': 'gallery',
    'settings': 'express settings',
    'advanced': 'advanced',
};

function detectWebsitePositionFromSidebar() {
    const active =
        document.querySelector('[data-nav-section][aria-current="page"]') ||
        document.querySelector('[data-nav-section].active') ||
        document.querySelector('.sidebar .active') ||
        document.querySelector('[aria-current="page"]');

    if (active) {
        const label = active.getAttribute('data-nav-section') || active.textContent?.trim();
        if (label) {
            const mapped = PAGE_MAP[label] || PAGE_MAP[(label || '').replace(/\s+/g, ' ').trim()];
            if (mapped) return mapped;
        }
    }
    return null;
}

function detectWebsitePositionFromPath() {
    const pathSegments = window.location.pathname.split('/').filter(Boolean);
    let firstSegment = pathSegments[0] || '';
    let secondSegment = pathSegments[1] || '';
    let thirdSegment = pathSegments[2] || '';

    //url: survey/{code}/{page}
    if (firstSegment === 'survey' && secondSegment && thirdSegment) {
        const pageMapping = {
            'questions': 'questionnaire',
            'templates': 'question templates',
            'themes': 'themes',
            'gallery': 'gallery',
            'settings': 'express settings',
            'advanced': 'advanced',
            'preview': 'preview'
        };
        
        const mappedPage = pageMapping[thirdSegment];
        if (mappedPage) {
            return mappedPage;
        }
        
        return `${firstSegment} ${thirdSegment}`;
    }

    if (firstSegment === 'folders' && pathSegments.length === 1) {
        firstSegment = 'project';
    } else if (firstSegment === 'folders' || firstSegment === 'surveys') {
        firstSegment = firstSegment.replace(/s$/, '');
    } else if (firstSegment === 'projects') {
        firstSegment = 'dashboard';
    } else if (firstSegment === 'teams') {
        firstSegment = 'project settings';
    }

    if (firstSegment === 'survey' && secondSegment && !thirdSegment) {
        return 'questionnaire'; // Default to questionnaire for /survey/{code}
    }

    return firstSegment || 'EXPRESS';
}

function detectActiveSidebar() {
    const galleryContainer = document.getElementById('slideover-container');
    if (galleryContainer && !galleryContainer.classList.contains('invisible')) {
        return 'gallery';
    }
    
    const settingsContainer = document.getElementById('slideover-container-settings');
    if (settingsContainer && !settingsContainer.classList.contains('invisible')) {
        return 'settings';
    }
    
    const advancedContainer = document.getElementById('slideover-container-advanced');
    if (advancedContainer && !advancedContainer.classList.contains('invisible')) {
        return 'advanced';
    }
    
    return null;
}

function getSidebarState() {
    const state = [];
    
    const galleryContainer = document.getElementById('slideover-container');
    if (galleryContainer && !galleryContainer.classList.contains('invisible')) {
        state.push('gallery');
    }
    
    const settingsContainer = document.getElementById('slideover-container-settings');
    if (settingsContainer && !settingsContainer.classList.contains('invisible')) {
        state.push('settings');
    }
    
    const advancedContainer = document.getElementById('slideover-container-advanced');
    if (advancedContainer && !advancedContainer.classList.contains('invisible')) {
        state.push('advanced');
    }
    
    return state;
}

const folder = ref();
const survey = ref();
const platform = ref();
const websitePosition = ref();
const sessionId = ref(null);
const user = ref();
const conversationId = ref(null);
const currentQuestionnaireName = ref(null);
/** Single in-flight create so we never create multiple docs for one chat */
let createConversationPromise = null;

let storageKey = '';
const getActiveConversationStorageKey = () => `express-chat-active:${sessionId.value || 'anon'}`;
const saveActiveConversationId = (convId) => {
    try {
        const key = getActiveConversationStorageKey();
        if (convId) {
            localStorage.setItem(key, convId);
        } else {
            localStorage.removeItem(key);
        }
    } catch (e) {
        console.warn('Failed to save active conversation ID', e);
    }
};
const getActiveConversationId = () => {
    try {
        const key = getActiveConversationStorageKey();
        return localStorage.getItem(key) || null;
    } catch (e) {
        return null;
    }
};
/** Remove a conversation's cached messages from localStorage (saves memory after delete). */
const removeConversationFromLocalStorage = (convId) => {
    if (!convId) return;
    try {
        const key = `express-chat:${sessionId.value || 'anon'}:conv:${convId}`;
        console.log('removeConversationFromLocalStorage', key);
        localStorage.removeItem(key);
    } catch (e) {
        console.warn('Failed to remove conversation from localStorage', e);
    }
};
const storageListener = (e) => {
    if (!storageKey || e.key !== storageKey) return;
    try {
        const parsed = e.newValue ? JSON.parse(e.newValue) : null;
        if (Array.isArray(parsed)) {
            messages.value = parsed.map((message) => stampMessage(message));
        }
    } catch {}
};

const tokenPercent = ref(0);
const tokenUsed = ref(0);
const tokenLimit = ref(25000);
let tokenUsageChannel = null;

// Interface-only mode flag and fallbacks
const INTERFACE_ONLY = (window.__CHATBOT_INTERFACE_ONLY__ ?? false);
const toast = window.toast ?? {
    success: (m) => console.log('[toast.success]', m),
    error: (m) => console.error('[toast.error]', m),
    info: (m) => console.log('[toast.info]', m),
};
const { showTranslateToast } = AITranslateToast();

const resolveMessageTimestamp = (payload = {}) => {
    return (
        payload?.timestamp ||
        payload?.created_at ||
        payload?.createdAt ||
        payload?.metadata?.timestamp ||
        null
    );
};

const stampMessage = (payload = {}) => {
    const timestamp = resolveMessageTimestamp(payload) || new Date().toISOString();
    return {
        ...payload,
        timestamp,
        createdAt: timestamp,
    };
};

const getMessageTimestamp = (message = {}) => {
    return resolveMessageTimestamp(message);
};

const formatMessageTimestamp = (timestamp) => {
    if (!timestamp) return '';
    try {
        return new Intl.DateTimeFormat(undefined, {
            hour: 'numeric',
            minute: '2-digit'
        }).format(new Date(timestamp));
    } catch (e) {
        console.warn('Failed to format timestamp', e);
        return '';
    }
};
const route = (typeof window !== 'undefined' && typeof window.route === 'function')
    ? window.route
    : ((name, ...params) => {
        // Fallback explicit paths when Ziggy route helper is unavailable
        switch (name) {
            case 'api.chat.message':
                return '/api/chat/message';
            case 'api.chat.message.stream.express':
                return '/api/chat/message/stream/express';
            case 'api.chat.message.stream':
                return '/api/chat/message/stream';
            case 'api.chat.clear':
                return '/api/chat/clear';
            case 'api.chat.token-usage':
                return '/api/chat/token-usage';
            case 'api.ai.execute':
                return `/api/ai/execute/${params?.[0] ?? ''}`;
            case 'api.chat.analyze-script':
                return '/api/chat/analyzeScript';
            case 'api.chat.get-question-part-data':
                return '/api/chat/getQuestionPartData';
            case 'api.chat.get-questions-for-selection':
                return '/api/chat/get-questions-for-selection';
            case 'api.chat.duplicate-question':
                return '/api/chat/duplicate-question';
            case 'api.chat.new':
                return '/api/chat/new';
            case 'api.chat.confirm-action':
                return '/api/chat/confirm-action';
            case 'api.chat.cancel-action':
                return '/api/chat/cancel-action';
            case 'api.chat.history':
                return '/api/chat/history';
            case 'api.chat.conversation':
                return '/api/chat/conversation';
            case 'api.chat.append-assistant-message':
                return '/api/chat/append-assistant-message';
            case 'api.chat.utility-prompt':
                return '/api/chat/utility-prompt';
            default:
                return name;
        }
    });


const fetchTokenUsage = async () => {
    try {
        const { data } = await axios.get(route('api.chat.token-usage'));
        const newUsed = data?.used ?? 0;
        const newLimit = data?.limit ?? 25000;
        
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

const setupTokenUsageWebsocket = () => {
    const userId = getUserId();
    if (!userId || !window.Echo) {
        console.warn('Cannot setup token usage websocket: user not authenticated or Echo not available');
        return;
    }

    try {
        // Leave existing channel if any
        if (tokenUsageChannel) {
            window.Echo.leave(`user.${userId}.token-usage`);
        }

        // Subscribe to user's private token usage channel
        tokenUsageChannel = window.Echo.private(`user.${userId}.token-usage`)
            .listen('.token-usage.updated', (data) => {
                console.log('Token usage updated via websocket:', data);
                tokenUsed.value = data.used ?? 0;
                tokenLimit.value = data.limit ?? 25000;
                tokenPercent.value = data.percent ?? 0;
            });

        console.log('Token usage websocket listener started');
    } catch (e) {
        console.error('Failed to setup token usage websocket:', e);
    }
};

const stopTokenUsageWebsocket = () => {
    const userId = getUserId();
    if (tokenUsageChannel && userId && window.Echo) {
        try {
            window.Echo.leave(`user.${userId}.token-usage`);
            tokenUsageChannel = null;
            console.log('Token usage websocket listener stopped');
        } catch (e) {
            console.error('Failed to stop token usage websocket:', e);
        }
    }
};

function updateWebsitePositionFromSidebar() {
    const activeSidebar = detectActiveSidebar();
    const fromSidebar = detectWebsitePositionFromSidebar();
    const fromPath = detectWebsitePositionFromPath();
    
    websitePosition.value = fromSidebar || fromPath || 'EXPRESS';
    
    if (activeSidebar) {
        console.log('Sidebar opened:', activeSidebar, 'Main page:', websitePosition.value);
    }
}

let sidebarObserver = null;

const loadQuestions = () => {
    const pageProps = usePage().props;
    const questions = pageProps?.questions || [];
    const questionnaire = pageProps?.questionnaire;
    const variableStart = questionnaire?.setup?.variable_start || 1;
    
    const allQuestions = questions.map((question, index) => {
        const questionNumber = parseInt(question.order) + parseInt(variableStart) - 1;
        return {
            id: question.id || index,
            order: question.order,
            number: questionNumber,
            text: question.question || '',
            fullText: `Q${questionNumber}: ${question.question || ''}`
        };
    });
    
    const addedQuestionIds = new Set(addedQuestions.value.map(q => q.id));
    availableQuestions.value = allQuestions.filter(q => !addedQuestionIds.has(q.id));
};

const handleClickOutside = (event) => {
    if (showQuestionDropdown.value && !event.target.closest('.question-dropdown-container')) {
        showQuestionDropdown.value = false;
    }
};

onMounted(async () => {
    console.log(usePage().props);
    folder.value = usePage().props?.folder;
    survey.value = usePage().props?.survey;
    sessionId.value = usePage().props?.session_id || null;
    user.value = usePage().props?.auth?.user;
    
    // Initialize current questionnaire name
    currentQuestionnaireName.value = getQuestionnaireName();
    
    loadQuestions();
    
    // Expose scrollToQuestion globally so it can be called from HTML buttons
    window.__scrollToQuestion = (questionId) => {
        scrollToQuestion(questionId);
    };

    console.log('folder id: ' + (folder.value?.id ?? survey.value?.folder?.id));
    console.log('survey id: ' + survey.value?.id);
    console.log('survey uuid: ' + survey.value?.uuid);

    // Compute platform for EXPRESS chatbot
    // For now EXPRESS AI always uses the 'express' platform.
    // If you later need subdomain-based detection, restore the logic below.
    // const hostParts = window.location.hostname.split('.');
    // platform.value = hostParts.length >= 3 ? hostParts[0] : null;
    platform.value = 'express';

    updateWebsitePositionFromSidebar();

    console.log('website position:', websitePosition.value)

    const activeConvId = getActiveConversationId();
    if (activeConvId) {
        conversationId.value = activeConvId;
        storageKey = `express-chat:${sessionId.value || 'anon'}:conv:${activeConvId}`;
        messages.value = [];
        try {
            const url = `${route('api.chat.conversation')}?conversation_id=${encodeURIComponent(activeConvId)}`;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
            });
            if (response.ok) {
                const data = await response.json();
                const conv = data.conversation;
                if (conv && conv.messages && Array.isArray(conv.messages) && conv.messages.length > 0) {
                    messages.value = conv.messages.map(mapHistoryMessage).map(stampMessage);
                    try {
                        localStorage.setItem(storageKey, JSON.stringify(messages.value));
                    } catch (e) {}
                }
            }
        } catch (e) {
            console.warn('Failed to fetch conversation on restore, trying localStorage', e);
            try {
                const saved = localStorage.getItem(storageKey);
                if (saved) {
                    const parsed = JSON.parse(saved);
                    if (Array.isArray(parsed) && parsed.length) {
                        messages.value = parsed.map((message) => stampMessage(message));
                    }
                }
            } catch (e2) {
                console.warn('Failed to restore chat from localStorage', e2);
            }
        }
    } else {
        storageKey = `express-chat:${sessionId.value || 'anon'}`;
        try {
            const saved = localStorage.getItem(storageKey);
            if (saved) {
                const parsed = JSON.parse(saved);
                if (Array.isArray(parsed) && parsed.length) {
                    messages.value = parsed.map((message) => stampMessage(message));
                }
            }
        } catch (e) {
            console.warn('Failed to restore chat from localStorage', e);
        }
    }

    window.addEventListener('storage', storageListener);
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('click', handleKebabMenuClickOutside);
    
    sidebarObserver = new MutationObserver(() => {
        updateWebsitePositionFromSidebar();
    });
    
    const galleryContainer = document.getElementById('slideover-container');
    const settingsContainer = document.getElementById('slideover-container-settings');
    const advancedContainer = document.getElementById('slideover-container-advanced');
    
    if (galleryContainer) {
        sidebarObserver.observe(galleryContainer, {
            attributes: true,
            attributeFilter: ['class']
        });
    }
    if (settingsContainer) {
        sidebarObserver.observe(settingsContainer, {
            attributes: true,
            attributeFilter: ['class']
        });
    }
    if (advancedContainer) {
        sidebarObserver.observe(advancedContainer, {
            attributes: true,
            attributeFilter: ['class']
        });
    }
    
    window.addEventListener('sidebar-state-change', () => {
        updateWebsitePositionFromSidebar();
    });
    
    nextTick(() => {
        scrollToBottom();
        autoResize();
    });
    
    // Fetch initial token usage
    fetchTokenUsage();
    
    // Setup websocket listener for real-time token usage updates
    setupTokenUsageWebsocket();
});

onUnmounted(() => {
    window.removeEventListener('storage', storageListener);
    window.removeEventListener('sidebar-state-change', updateWebsitePositionFromSidebar);
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('click', handleKebabMenuClickOutside);
    if (sidebarObserver) {
        sidebarObserver.disconnect();
    }
    stopTokenUsageWebsocket();
    stopTypingEffect();
});

const emit = defineEmits(['update:open']);

const CHATLIST_STORAGE_KEY = 'chatlist';
const getStoredChatListOpen = () => {
    try {
        const v = localStorage.getItem(CHATLIST_STORAGE_KEY);
        return v === 'false' ? false : true;
    } catch (e) {
        return true;
    }
};
const setStoredChatListOpen = (open) => {
    try {
        localStorage.setItem(CHATLIST_STORAGE_KEY, open ? 'true' : 'false');
    } catch (e) {}
};

const showSidebar = ref(getStoredChatListOpen());
const chatListRef = ref(null);
const MAIN_CONTENT_WIDTH = '28rem';
const SIDEBAR_WIDTH = '12rem';
const COLLAPSED_MODAL_WIDTH = '28rem';
const EXPANDED_MODAL_WIDTH = '40rem';
const modalMaxWidth = computed(() => {
    if (isOnboardingState.value) {
        return COLLAPSED_MODAL_WIDTH; 
    }
    return showSidebar.value ? EXPANDED_MODAL_WIDTH : COLLAPSED_MODAL_WIDTH;
});
const isSidebarClosing = ref(false);
const showKebabMenu = ref(false);
const kebabMenuRef = ref(null);

// Temporary for chatbot related kay tiring ang individual delete question
const showDeleteAllQuestionDebug = computed(() => usePage().props?.auth?.user?.email === 'mark.yaba@splitsecondresearch.com');

const toggleSidebar = () => {
    showSidebar.value = !showSidebar.value;
    setStoredChatListOpen(showSidebar.value);
};

const toggleKebabMenu = () => {
    showKebabMenu.value = !showKebabMenu.value;
};

const closeKebabMenu = () => {
    showKebabMenu.value = false;
};

const handleContactSupport = () => {
    closeKebabMenu();
    // Set the message to trigger customer support detection
    newMessage.value = 'Contact Support';
    // Send the message which will detect customer support request and show the card
    sendMessage();
};

// ============================================================================
// CUSTOMER SUPPORT HANDLERS
// ============================================================================

const showCustomerSupportCard = () => {
    customerSupportData.value = {
        show: true
    };
    
    persistCustomerSupportState(true);
    // CustomerSupport component will handle its own initialization via onMounted
};

const handleSupportSubmitSuccess = (result) => {
    try {
        skipCustomerSupportTransition.value = true;
        
        const messageIndex = messages.value.findIndex(m => m.hasCustomerSupport);
        if (messageIndex !== -1) {
            messages.value.splice(messageIndex, 1);
        }
        
        if (result && result.message) {
            messages.value.push(result.message);
            nextTick(() => {
                scrollToBottom();
            });
        }
        
        customerSupportData.value = null;
        persistCustomerSupportState(false);
        
        setTimeout(() => {
            skipCustomerSupportTransition.value = false;
        }, 100);
    } catch (error) {
        console.error('Failed to handle support submit success:', error);
        toast.error('Failed to process support request response.');
    }
};

const handleSupportKeepChatting = (result) => {
    skipCustomerSupportTransition.value = true;
    
    const messageIndex = messages.value.findIndex(m => m.hasCustomerSupport);
    if (messageIndex !== -1) {
        messages.value.splice(messageIndex, 1);
    }
    
    if (result.message) {
        messages.value.push(result.message);
    }
    
    customerSupportData.value = null;
    persistCustomerSupportState(false);
    
    setTimeout(() => {
        skipCustomerSupportTransition.value = false;
    }, 100);
};

// Handle initial message from CustomerSupport component
const handleCustomerSupportInitialMessage = (message) => {
    // Check if a message with hasCustomerSupport already exists to prevent duplicates
    const existingMessage = messages.value.find(m => m.hasCustomerSupport);
    if (!existingMessage) {
        messages.value.push(message);
    }
};

// Close kebab menu when clicking outside
const handleKebabMenuClickOutside = (event) => {
    if (kebabMenuRef.value && !kebabMenuRef.value.contains(event.target)) {
        showKebabMenu.value = false;
    }
};

const isConversationProcessing = computed(() => loading.value || messages.value.some(m => m.isStreaming));

// Onboarding state helpers
const imageError = ref(false);
const userName = computed(() => {
    return user.value?.name?.trim().split(' ')[0] + '!' || null;
});

const onboardingGreetings = [
    'Hi there',
    'Welcome back',
    'Good to see you',
    'Hello',
    'Hey there',
    'Nice to see you',
    'Hey',
    'Glad you\'re here',
    'Thanks for stopping by'
];

const currentGreeting = ref(
    onboardingGreetings[Math.floor(Math.random() * onboardingGreetings.length)]
);

const isOnboardingState = computed(() => {
    return !conversationId.value && (messages.value.length === 0 || (messages.value.length === 1 && messages.value[0]?.role === 'assistant'));
});

const showOnboardingContent = computed(() => {
    return messages.value.length === 0 || (messages.value.length === 1 && messages.value[0]?.role === 'assistant');
});

const handleImageError = (event) => {
    imageError.value = true;
    event.target.style.display = 'none';
};

const mapHistoryMessage = (msg) => ({
    ...msg,
    role: msg.role || 'assistant',
    content: msg.content || '',
    type: msg.type || 'normal',
    liked: msg.liked || false,
    disliked: msg.disliked || false,
    feedbackSubmitted: msg.feedbackSubmitted || false,
    feedbackDisabled: msg.feedbackDisabled || false,
    isStreaming: false,
    timestamp: msg.timestamp || msg.created_at || msg.createdAt || new Date().toISOString(),
});

const handleConversationSelected = async (conversation) => {
    const convId = conversation.conversation_id || conversation.id;
    if (!convId) {
        toast.error('Invalid conversation');
        return;
    }
    if (conversationId.value && messages.value.length > 0) {
        try {
            localStorage.setItem(storageKey, JSON.stringify(messages.value));
        } catch (e) {
            console.warn('Failed to save current conversation', e);
        }
    }
    conversationId.value = convId;
    saveActiveConversationId(convId);
    storageKey = `express-chat:${sessionId.value || 'anon'}:conv:${convId}`;
    messages.value = [];
    try {
        const url = `${route('api.chat.conversation')}?conversation_id=${encodeURIComponent(convId)}`;
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        if (response.ok) {
            const data = await response.json();
            const conv = data.conversation;
            if (conv && conv.messages && Array.isArray(conv.messages) && conv.messages.length > 0) {
                messages.value = conv.messages.map(mapHistoryMessage).map(stampMessage);
            }
        }
    } catch (e) {
        console.warn('Failed to fetch conversation, using list data', e);
    }
    if (messages.value.length === 0 && conversation.messages && Array.isArray(conversation.messages) && conversation.messages.length > 0) {
        messages.value = conversation.messages.map(mapHistoryMessage).map(stampMessage);
    }
    try {
        localStorage.removeItem(storageKey);
    } catch (e) {}
    if (messages.value.length > 0) {
        try {
            localStorage.setItem(storageKey, JSON.stringify(messages.value));
        } catch (e) {}
    }
    actionData.value = null;
    if (window.innerWidth >= 768) showSidebar.value = true;
    nextTick(() => scrollToBottom());
    if (window.innerWidth < 768) showSidebar.value = false;
};

/**
 * Ask backend to create a real, empty MongoDB conversation and
 * return its ID. Only one create runs at a time so we never get
 * multiple documents for a single chat (e.g. from double-clicks or
 * concurrent send + new-conversation).
 */
const createBackendConversation = async () => {
    if (createConversationPromise) {
        return createConversationPromise;
    }
    createConversationPromise = (async () => {
        let newConversationId = null;
        try {
            const response = await fetch(route('api.chat.new'), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
                body: JSON.stringify({
                    platform: platform.value || 'express',
                    title: null,
                }),
            });

            if (response.ok) {
                const data = await response.json();
                newConversationId =
                    data?.conversation?.conversation_id || data?.conversation?._id || null;
            } else {
                console.error('[Chatbot.vue] Failed to create new conversation', response.status);
            }
        } catch (e) {
            console.error('[Chatbot.vue] Error creating new conversation', e);
        } finally {
            createConversationPromise = null;
        }
        return newConversationId;
    })();
    return createConversationPromise;
};

const handleNewConversation = async () => {
    if (conversationId.value && messages.value.length > 0) {
        try {
            localStorage.setItem(storageKey, JSON.stringify(messages.value));
        } catch (e) {
            console.warn('Failed to save current conversation', e);
        }
    }

    // Create a brand‑new MongoDB conversation for this chat
    const newConversationId = await createBackendConversation();

    conversationId.value = newConversationId || null;
    saveActiveConversationId(newConversationId || null);

    storageKey = `express-chat:${sessionId.value || 'anon'}`;
    messages.value = [];
    actionData.value = null;
    imageError.value = false; // Reset image error for onboarding

    showSidebar.value = true;
    setStoredChatListOpen(true);

    nextTick(() => scrollToBottom());
    if (chatListRef.value && typeof chatListRef.value.refresh === 'function') {
        setTimeout(() => chatListRef.value.refresh(), 300);
    }
};

const handleAllConversationsCleared = () => {
    // Do NOT create a new backend conversation here.
    // Reset to onboarding state: no active conversation and no messages,
    conversationId.value = null;
    saveActiveConversationId(null);
    storageKey = `express-chat:${sessionId.value || 'anon'}`;
    messages.value = [];
    actionData.value = null;
    imageError.value = false;
    if (window.innerWidth >= 768) {
        showSidebar.value = false;
        setStoredChatListOpen(false);
    }
};

const detectMethod = false;

// User sends first message; no initial AI greeting for now
// const INITIAL_GREETING = getGreeting();
const messages = ref([]);
const newMessage = ref('');
const loading = ref(false);
const chatContainer = ref(null);
const messageInput = ref(null);
const actionData = ref(null);
const showConfirmClear = ref(false);
const showConversationRating = ref(false);
const conversationRating = ref(0);
const conversationRatingComment = ref('');
const conversationToDeleteFromList = ref(null); // Store conversation when deleting from ChatList
const showFeedbackDialog = ref(false);
const feedbackMessageIndex = ref(null);
const feedbackReasons = ref([]);
const feedbackText = ref('');
const showFeedbackThankYou = ref(false);
const RESEARCH_MODE = 'research-mode';
const researchMode = ref((() => {
    try {
        return typeof localStorage !== 'undefined' && localStorage.getItem(RESEARCH_MODE) === 'true';
    } catch {
        return false;
    }
})());
const showQuestionDropdown = ref(false);
const selectedQuestions = ref([]);
const availableQuestions = ref([]);
const addedQuestions = ref([]);
const attachedFiles = ref([]);
const fileInputRef = ref(null);
const showTermsModal = ref(false);
const scriptAnalysisData = ref(null);
const analyzingScript = ref(false);
const selectedPart = ref(null);
const partSuggestions = ref([]);
const partSuggestionMessageIndex = ref(null);
const partSuggestionsDisabled = ref(false);
const partExtractionAbortController = ref(null);
const questionSuggestions = ref([]);
const questionSuggestionMessageIndex = ref(null);
const questionSuggestionsDisabled = ref(false);
const selectedQuestionForDuplicate = ref(null);
const duplicateConfirmationData = ref(null);
/** When set, the next user message is treated as the answer to "How many questions?" for a plural question request. */
const pendingPluralQuestionMessage = ref(null);
/** When set (>1), executeAction will request creating this many questions instead of just one. */
const pluralQuestionRequestedCount = ref(null);
/** Original message for topic extraction when creating multiple distinct questions (e.g. "create text selection questions about favorite activities"). */
const pluralQuestionOriginalMessage = ref(null);
const typingEffectActive = ref(false);
let typingEffectTimeout = null;

// Helper functions for customer support state persistence
const persistCustomerSupportState = (value) => {
    try {
        if (value) {
            localStorage.setItem('express-chat:customer-support-state', 'true');
        } else {
            localStorage.removeItem('express-chat:customer-support-state');
        }
    } catch (e) {
        console.warn('Failed to persist customer support state', e);
    }
};

const restoreCustomerSupportState = () => {
    try {
        return localStorage.getItem('express-chat:customer-support-state') === 'true';
    } catch (e) {
        console.warn('Failed to restore customer support state', e);
        return false;
    }
};

// Customer support state
const customerSupportData = ref(restoreCustomerSupportState() ? { show: true } : null); // Track customer support request data
const skipCustomerSupportTransition = ref(false); // Track if customer support transition should be skipped

// Watch customer support data to persist state
watch(customerSupportData, (value) => {
    persistCustomerSupportState(!!value);
});

// When actionData has preview_data + plural question request, fetch all N questions for list preview
watch([actionData, pluralQuestionRequestedCount], async ([ad, count]) => {
    if (!ad?.preview_data?.title_and_question_text || !count || count <= 1 || ad.survey_data?.questions ||
        !ad.action?.includes('_create') || ad.scope === 'survey' || isFetchingMultipleQuestions.value) {
        return;
    }
    const questionnaireCode = ad.questionnaire_code || survey.value?.code || survey.value?.questionnaire_code;
    let topic = pluralQuestionOriginalMessage.value || (ad.preview_data?.title_and_question_text?.question_text || ad.preview_data?.title_and_question_text?.['question text'] || 'survey topic');
    const aboutMatch = String(topic).match(/\b(?:about|on|regarding)\s+(.+?)(?:\s*\.|$)/i);
    if (aboutMatch) topic = aboutMatch[1].trim();
    const scope = ad.scope || 'descriptive';
    const requestCount = Math.min(Math.max(count, 2), 20);

    isFetchingMultipleQuestions.value = true;
    actionData.value = { ...ad, loading: true };

    try {
        const res = await axios.post(route('api.chat.preview-multiple-questions'), {
            scope,
            topic,
            first_question: ad.preview_data,
            count: requestCount,
            questionnaire_code: questionnaireCode,
        });
        if (res.data?.success && res.data?.questions?.length > 0) {
            actionData.value = {
                ...ad,
                action: 'express_survey_create',
                survey_data: { questions: res.data.questions },
                total_questions: res.data.questions.length,
                questionnaire_code: res.data.questionnaire_code || questionnaireCode,
                loading: false,
            };
        } else {
            actionData.value = { ...ad, loading: false };
        }
    } catch (err) {
        console.error('Failed to fetch multiple questions preview:', err);
        actionData.value = { ...ad, loading: false };
        toast.error('Could not load question preview. You can still create one question.');
    } finally {
        isFetchingMultipleQuestions.value = false;
    }
}, { flush: 'post' });

const pushAnalysisErrorMessage = (message) => {
    messages.value.push(stampMessage({
        role: 'assistant',
        type: 'analysis-error',
        content: message || 'Something went wrong while analyzing your script. Please try again.'
    }));
};

const pushPartExtractionErrorMessage = (partIdentifier, message) => {
    messages.value.push(stampMessage({
        role: 'assistant',
        type: 'part-error',
        content: message || `I couldn't find any questions in ${partIdentifier}.`,
        partIdentifier
    }));
};
const isPreparingQuestion = ref(false);
const isPreparingSurvey = ref(false);
const isCreatingQuestions = ref(false);
const creatingQuestionsCount = ref(0);
const isFetchingMultipleQuestions = ref(false);
const surveyProgress = ref({
    stage: '',
    message: '',
    current: 0,
    total: 0
});
let progressSimulationInterval = null;
let progressStartTime = null;
const abortController = ref(null);
const streamReader = ref(null);

// Simulate progress during survey generation
const startProgressSimulation = (total) => {
    if (progressSimulationInterval) {
        clearInterval(progressSimulationInterval);
    }
    
    progressStartTime = Date.now();
    const estimatedDuration = total * 8000; // ~8 seconds per question
    
    progressSimulationInterval = setInterval(() => {
        if (!isPreparingSurvey.value || surveyProgress.value.stage !== 'generating') {
            clearInterval(progressSimulationInterval);
            progressSimulationInterval = null;
            return;
        }
        
        const elapsed = Date.now() - progressStartTime;
        const estimatedProgress = Math.min(
            Math.floor((elapsed / estimatedDuration) * total) + 1,
            total
        );
        
        if (estimatedProgress > surveyProgress.value.current && estimatedProgress <= total) {
            surveyProgress.value = {
                ...surveyProgress.value,
                current: estimatedProgress
            };
        }
        
        if (estimatedProgress >= total) {
            clearInterval(progressSimulationInterval);
            progressSimulationInterval = null;
        }
    }, 500); // Update every 500ms
};

const stopProgressSimulation = () => {
    if (progressSimulationInterval) {
        clearInterval(progressSimulationInterval);
        progressSimulationInterval = null;
    }
    progressStartTime = null;
};

const normalizePartLabel = (part) => {
    if (!part) return '';
    if (typeof part === 'string') return part;
    if (typeof part === 'object') {
        return (
            part?.label ||
            part?.name ||
            part?.title ||
            part?.part ||
            part?.value ||
            ''
        );
    }
    return String(part);
};

const upsertPartSuggestionMessage = (parts) => {
    const normalizedParts = parts
        .map((part) => normalizePartLabel(part))
        .filter((label, index, self) => label && self.indexOf(label) === index);

    if (normalizedParts.length === 0) {
        removePartSuggestionMessage();
        return;
    }

    const fileName = attachedFiles.value.length > 0 ? attachedFiles.value[0].file.name : 'the uploaded script';
    const fileNameStyled = attachedFiles.value.length > 0 
        ? `<strong style="color: var(--mcolor);">${fileName}</strong>`
        : 'the uploaded script';
    const payload = stampMessage({
        role: 'assistant',
        type: 'part-suggestion',
        content: `Select the part below you want to create from ${fileNameStyled}.`,
        parts: normalizedParts
    });

    if (
        partSuggestionMessageIndex.value !== null &&
        messages.value[partSuggestionMessageIndex.value]
    ) {
        messages.value[partSuggestionMessageIndex.value] = payload;
    } else {
        messages.value.push(payload);
        partSuggestionMessageIndex.value = messages.value.length - 1;
    }
};

const removePartSuggestionMessage = () => {
    if (
        partSuggestionMessageIndex.value !== null &&
        messages.value[partSuggestionMessageIndex.value]
    ) {
        messages.value.splice(partSuggestionMessageIndex.value, 1);
    }
    partSuggestionMessageIndex.value = null;
};

const upsertQuestionSuggestionMessage = (questions) => {
    if (!Array.isArray(questions) || questions.length === 0) {
        removeQuestionSuggestionMessage();
        return;
    }

    const payload = stampMessage({
        role: 'assistant',
        type: 'question-suggestion',
        content: 'Select the question below you want to duplicate.',
        questions: questions
    });

    if (
        questionSuggestionMessageIndex.value !== null &&
        messages.value[questionSuggestionMessageIndex.value]
    ) {
        messages.value[questionSuggestionMessageIndex.value] = payload;
    } else {
        messages.value.push(payload);
        questionSuggestionMessageIndex.value = messages.value.length - 1;
    }
};

const removeQuestionSuggestionMessage = () => {
    if (
        questionSuggestionMessageIndex.value !== null &&
        messages.value[questionSuggestionMessageIndex.value]
    ) {
        messages.value.splice(questionSuggestionMessageIndex.value, 1);
    }
    questionSuggestionMessageIndex.value = null;
};

const isFeedbackValid = computed(() => {
    return feedbackReasons.value.length > 0;
});

const inputsDisabled = computed(() => loading.value || analyzingScript.value);

const clearConversation = () => {
    try {
        const key = storageKey || `express-chat:${sessionId.value || 'anon'}`;
        localStorage.removeItem(key);
    } catch (e) {
        console.warn('Failed to clear chat in localStorage', e);
    }
    // No initial AI greeting; user sends first message
    messages.value = [];
    actionData.value = null;
    conversationId.value = null;
    saveActiveConversationId(null);
    addedQuestions.value = [];
    attachedFiles.value = [];
    isPreparingQuestion.value = false;
    isPreparingSurvey.value = false;
    partSuggestions.value = [];
    removePartSuggestionMessage();
    partSuggestionsDisabled.value = false;
    questionSuggestions.value = [];
    removeQuestionSuggestionMessage();
    questionSuggestionsDisabled.value = false;
    selectedQuestionForDuplicate.value = null;
    duplicateConfirmationData.value = null;
    loading.value = false;
    toast.success('Conversation cleared');
};

const requestClearConversation = () => {
    showConfirmClear.value = true;
};

const confirmClearConversation = () => {
    showConfirmClear.value = false;
    showConversationRating.value = true;
};

const cancelClearConversation = () => {
    showConfirmClear.value = false;
};

// TODO: temporary debug - remove this later (deletes all survey questions in current questionnaire)
async function deleteAllQuestionDebug() {
    closeKebabMenu();
    const questionnaire = usePage().props?.questionnaire;
    const questions = usePage().props?.questions || [];
    const questionnaireCode = questionnaire?.code;
    if (!questionnaireCode || !questions.length) {
        toast.error('No questionnaire or no questions to delete');
        return;
    }
    loading.value = true;
    try {
        for (const q of [...questions]) {
            await axios.delete(route('questions.destroy', [questionnaireCode, q.id]));
        }
        router.reload({ only: ['questions'] });
        toast.success(`Deleted all ${questions.length} question(s) (debug)`);
    } catch (e) {
        console.error('Delete all questions failed', e);
        toast.error('Failed to delete all questions');
    } finally {
        loading.value = false;
    }
}

const submitConversationRating = async () => {
    const ratingConvId = conversationToDeleteFromList.value 
        ? (conversationToDeleteFromList.value.conversation_id || conversationToDeleteFromList.value.id)
        : conversationId.value;
    
    if (conversationRating.value > 0 && ratingConvId && !INTERFACE_ONLY) {
        try {
            await axios.post('/api/chat/conversation-rating', {
                conversation_id: ratingConvId,
                rating: conversationRating.value,
                comment: conversationRatingComment.value.trim() || null
            });
        } catch (error) {
            console.error('Failed to submit conversation rating:', error);
        }
    }
    
    if (conversationToDeleteFromList.value) {
        performDeleteFromList(conversationToDeleteFromList.value);
    } else {
        performClearConversation();
    }
};

const skipConversationRating = () => {
    if (conversationToDeleteFromList.value) {
        performDeleteFromList(conversationToDeleteFromList.value);
    } else {
        performClearConversation();
    }
};

const cancelConversationRating = () => {
    showConversationRating.value = false;
    conversationRating.value = 0;
    conversationRatingComment.value = '';
    conversationToDeleteFromList.value = null;
};

const handleRequestDeleteWithRating = (conversation) => {
    conversationToDeleteFromList.value = conversation;
    showConversationRating.value = true;
};

const performDeleteFromList = async (conversation) => {
    const deletedConversationId = conversation.conversation_id || conversation.id;
    if (!deletedConversationId) {
        conversationToDeleteFromList.value = null;
        return;
    }
    
    // Find next conversation BEFORE deletion
    let nextConversation = null;
    try {
        const response = await fetch(route('api.chat.history'), {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        
        if (response.ok) {
            const data = await response.json();
            const allConversations = (data.conversations || [])
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
            
            // Deduplicate
            const seen = new Set();
            const deduped = allConversations.filter(conv => {
                const id = conv.conversation_id || conv.id;
                if (seen.has(id)) return false;
                seen.add(id);
                return true;
            });
            
            const currentIndex = deduped.findIndex(c => 
                (c.conversation_id || c.id) === deletedConversationId
            );
            
            const remaining = deduped.filter(c => 
                (c.conversation_id || c.id) !== deletedConversationId
            );
            
            if (remaining.length > 0) {
                const nextIndex = Math.min(currentIndex, remaining.length - 1);
                nextConversation = remaining[nextIndex];
            }
        }
    } catch (e) {
        console.error('Failed to fetch conversations before deletion', e);
    }
    
    // Perform deletion
    try {
        await axios.post(route('api.chat.clear'), {
            platform: platform.value || 'express',
            conversation_id: deletedConversationId,
            scope: null,
        });
    } catch (e) {
        console.error('Failed to delete conversation', e);
        toast.error('Failed to delete conversation');
    }

    // Remove deleted conversation's cache from localStorage to free memory
    removeConversationFromLocalStorage(deletedConversationId);
    
    showConversationRating.value = false;
    conversationRating.value = 0;
    conversationRatingComment.value = '';
    conversationToDeleteFromList.value = null;
    
    if (chatListRef.value && typeof chatListRef.value.refresh === 'function') {
        chatListRef.value.refresh();
    }
    
    if (nextConversation) {
        handleConversationSelected(nextConversation);
    } else {
        handleAllConversationsCleared();
    }
};

const performClearConversation = async () => {
    const deletedConversationId = conversationId.value;
    
    // Fetch conversations BEFORE deletion to find next conversation
    let nextConversation = null;
    try {
        const response = await fetch(route('api.chat.history'), {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
        });
        
        if (response.ok) {
            const data = await response.json();
            const allConversations = (data.conversations || [])
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
            
            // Deduplicate
            const seen = new Set();
            const deduped = allConversations.filter(conv => {
                const id = conv.conversation_id || conv.id;
                if (seen.has(id)) return false;
                seen.add(id);
                return true;
            });
            
            const currentIndex = deduped.findIndex(c => 
                (c.conversation_id || c.id) === deletedConversationId
            );
            
            const remaining = deduped.filter(c => 
                (c.conversation_id || c.id) !== deletedConversationId
            );
            
            if (remaining.length > 0) {
                const nextIndex = Math.min(currentIndex, remaining.length - 1);
                nextConversation = remaining[nextIndex];
            }
        }
    } catch (e) {
        console.error('Failed to fetch conversations before deletion', e);
    }
    
    if (INTERFACE_ONLY) {
        clearConversation();
        showConversationRating.value = false;
        conversationRating.value = 0;
        conversationRatingComment.value = '';
        return;
    }
    
    try {
        await axios.post(route('api.chat.clear'), {
            platform: platform.value,
            conversation_id: deletedConversationId,
            scope: null,
        });
    } catch (e) {
        console.error('Failed to clear conversation', e);
    } finally {
        clearConversation();
        showConversationRating.value = false;
        conversationRating.value = 0;
        conversationRatingComment.value = '';
        
        if (chatListRef.value && typeof chatListRef.value.refresh === 'function') {
            chatListRef.value.refresh();
        }
        
        if (nextConversation) {
            handleConversationSelected(nextConversation);
        } else {
            handleAllConversationsCleared();
        }
    }
};

const disablePreviousFeedback = () => {
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
            
            if (feedbackMessageIndex.value === messageIndex) {
                showFeedbackDialog.value = false;
                feedbackMessageIndex.value = null;
                feedbackReasons.value = [];
                feedbackText.value = '';
            }
            
            if (conversationId.value && !INTERFACE_ONLY) {
                try {
                    await axios.post('/api/chat/feedback', {
                        conversation_id: conversationId.value,
                        message_id: message._id || null,
                        type: 'like'
                    });
                    console.log('Like feedback sent successfully');
                } catch (error) {
                    console.error('Failed to send like feedback:', error);
                }
            }
            
            showFeedbackThankYou.value = true;
            setTimeout(() => {
                showFeedbackThankYou.value = false;
            }, 3000);
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
            showFeedbackDialog.value = true;
            feedbackMessageIndex.value = messageIndex;
            feedbackReasons.value = [];
            feedbackText.value = '';
        }
    }
};

const isGreetingMessage = (message) => {
    if (message.role !== 'assistant') return false;
    
    const greetingContent = message.content.toLowerCase();
    return greetingContent.includes('hello') && 
           (greetingContent.includes('express ai assistant') || 
            greetingContent.includes('how can i help you today'));
};

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
    
    if (feedbackMessageIndex.value !== null) {
        const message = messages.value[feedbackMessageIndex.value];
        if (message) {
            message.feedbackSubmitted = true;
        }
        
        const categoryMap = {
            'Harmful': 'harmful',
            'Not accurate': 'not_accurate',
            'Not useful or incomplete': 'not_useful_or_incomplete',
            'Something else': 'something_else'
        };
        
        const backendCategory = feedbackReasons.value.length > 0 
            ? categoryMap[feedbackReasons.value[0]] 
            : 'something_else';
        
        if (conversationId.value && !INTERFACE_ONLY) {
            try {
                await axios.post('/api/chat/feedback', {
                    conversation_id: conversationId.value,
                    message_id: message._id || null,
                    type: 'dislike',
                    category: backendCategory,
                    comment: feedbackText.value.trim() || null
                });
                console.log('Dislike feedback sent successfully');
                toast.success('Feedback submitted successfully');
                
                showFeedbackThankYou.value = true;
                setTimeout(() => {
                    showFeedbackThankYou.value = false;
                }, 3000);
            } catch (error) {
                console.error('Failed to send dislike feedback:', error);
                toast.error('Failed to submit feedback, but your response has been noted');
            }
        } else {
            console.log('Feedback submitted (interface only):', {
                messageIndex: feedbackMessageIndex.value,
                reasons: feedbackReasons.value,
                category: backendCategory,
                text: feedbackText.value
            });
            toast.success('Feedback submitted successfully');
            showFeedbackThankYou.value = true;
            setTimeout(() => {
                showFeedbackThankYou.value = false;
            }, 3000); 
        }
    }
    
    showFeedbackDialog.value = false;
    feedbackMessageIndex.value = null;
    feedbackReasons.value = [];
    feedbackText.value = '';
};

const cancelFeedback = () => {
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

const isJsonString = (str) => {
    try {
        const json = JSON.parse(str);
        return typeof json === 'object' && json !== null;
    } catch (e) {
        return false;
    }
};

const detectAction = (message) => {
    console.log('[FRONTEND DEBUG] detectAction called with:', message);
    
    try {
        if (message.show_confirmation && message.confirmation_buttons) {
            console.log('🔍 [FRONTEND DEBUG] Detected confirmation format');
            return {
                ...message,
                actionable_id: null,
                message: message.message,
                action: message.action,
                scope: message.scope,
                intent: message.intent,
                preview_data: message.preview_data,
                questionnaire_code: message.questionnaire_code,
                confirmation_buttons: message.confirmation_buttons
            };
        }
        
        if (message.actionable_id) {
            console.log('[FRONTEND DEBUG] Detected legacy actionable_id format');
            return message;
        }
        
        console.log('[FRONTEND DEBUG] No action detected');
    } catch (e) {
        console.log('[FRONTEND DEBUG] Error in detectAction:', e);
    }
    return null;
};

const executeAction = async (filteredActionData = null) => {
    console.log(' [RONTEND DEBUG] executeAction called');
    console.log(' [FRONTEND DEBUG] actionData.value:', actionData.value);
    
    // Use filtered actionData if provided, otherwise use the original
    const dataToUse = filteredActionData || actionData.value;
    
    if (!dataToUse) {
        console.log('[FRONTEND DEBUG] No actionData, returning');
        return;
    }
    
    console.log(' [FRONTEND DEBUG] Executing action:', actionData.value);
    
    try {
        if (INTERFACE_ONLY) {
            console.log('Interface only mode - executing mock action');
            messages.value.push(stampMessage({
                role: 'assistant',
                content: '✅ Action executed (mock).',
                type: 'normal'
            }));
            toast.success('Action executed (mock)');
        } else {
            if (dataToUse.action === 'express_question_duplicate' && dataToUse.preview_data?.question_id) {
                await executeQuestionDuplication(
                    dataToUse.preview_data.question_id,
                    dataToUse.questionnaire_code
                );
                actionData.value = null;
                return;
            }
            
            if (dataToUse.confirmation_buttons) {
                const isSingleQuestionCreateAction =
                    dataToUse.action &&
                    dataToUse.action.startsWith('express_') &&
                    dataToUse.action.endsWith('_create') &&
                    dataToUse.scope !== 'survey';

                const repeatCount =
                    isSingleQuestionCreateAction &&
                    pluralQuestionRequestedCount.value &&
                    pluralQuestionRequestedCount.value > 1
                        ? Math.min(Math.max(pluralQuestionRequestedCount.value, 1), 20)
                        : 1;

                const confirmData = {
                    action: dataToUse.action,
                    scope: dataToUse.scope,
                    intent: dataToUse.intent,
                    preview_data: dataToUse.preview_data,
                    survey_data: dataToUse.survey_data,
                    questionnaire_code: dataToUse.questionnaire_code || survey.value?.code || survey.value?.questionnaire_code,
                    ...(repeatCount > 1 && !dataToUse.survey_data?.questions && {
                        questions_count: repeatCount,
                        user_message: pluralQuestionOriginalMessage.value || '',
                    }),
                };

                const isMultiQuestionCreate = (dataToUse.action?.includes('_create') || dataToUse.action === 'express_survey_create') &&
                    (dataToUse.survey_data?.questions?.length > 1 || (repeatCount > 1 && !dataToUse.survey_data?.questions));
                const createCount = dataToUse.survey_data?.questions?.length || repeatCount || 1;

                if (isMultiQuestionCreate && createCount > 1) {
                    actionData.value = null;
                    isCreatingQuestions.value = true;
                    creatingQuestionsCount.value = createCount;
                }

                console.log('Confirming action with data:', confirmData);

                const response = await axios.post(route('api.chat.confirm-action'), confirmData);
                
                console.log('Confirmation response:', response.data);
                
                const questionId = response.data?.question_id;
                const questionnaireCode = confirmData.questionnaire_code;
                
                // Get question number for display
                let questionNumber = '';
                if (questionId && (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/'))) {
                    const questions = usePage().props.questions || [];
                    const createdQuestion = questions.find(q => q.id === questionId);
                    if (createdQuestion) {
                        const variableStart = usePage().props?.questionnaire?.setup?.variable_start || 1;
                        questionNumber = `Q${parseInt(createdQuestion.order) + parseInt(variableStart) - 1}`;
                    }
                }
                
                const successMessage = (createCount > 1 || dataToUse.survey_data?.questions?.length > 1)
                    ? `${createCount} questions created successfully!`
                    : (response.data.message || 'Question created successfully!');
                let content = `✅ ${successMessage}`;
                const isSurveyCreation = dataToUse?.action === 'express_survey_create' || dataToUse?.survey_data;
                
                // If we have a questionId and we're on the questions page, use scroll functionality
                if (questionId && (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/'))) {
                    const scrollFunction = `window.__scrollToQuestion && window.__scrollToQuestion(${questionId})`;
                    const linkLabel = repeatCount > 1 ? 'questions' : (questionNumber || 'question');
                    content += `<br><span onclick="${scrollFunction}" class="link cursor-pointer hover:underline inline-block" style="color: var(--mcolor); text-decoration: underline;">Go to ${linkLabel}</span>`;
                } else if (response.data?.route) {
                    // For survey creation, if we're on the questions page, show "Go to" and will scroll after reload
                    if (isSurveyCreation && (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/'))) {
                        content += `<br><span class="text-xs text-gray-500">Scrolling to created questions...</span>`;
                    } else if ((window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/')) && questionId) {
                        const scrollFunction = `window.__scrollToQuestion && window.__scrollToQuestion(${questionId})`;
                        content += `<br><span onclick="${scrollFunction}" class="link cursor-pointer hover:underline inline-block" style="color: var(--mcolor); text-decoration: underline;">Go to ${questionNumber || 'question'}</span>`;
                    } else if (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/')) {
                        // On questions page but no questionId - try to scroll to last question
                        const questions = usePage().props.questions || [];
                        if (questions.length > 0) {
                            const lastQuestion = questions[questions.length - 1];
                            const variableStart = usePage().props?.questionnaire?.setup?.variable_start || 1;
                            const lastQuestionNumber = `Q${parseInt(lastQuestion.order) + parseInt(variableStart) - 1}`;
                            const scrollFunction = `window.__scrollToQuestion && window.__scrollToQuestion(${lastQuestion.id})`;
                            content += `<br><span onclick="${scrollFunction}" class="link cursor-pointer hover:underline inline-block" style="color: var(--mcolor); text-decoration: underline;">Go to ${lastQuestionNumber}</span>`;
                        } else {
                            content += `<br><span class="text-xs text-gray-500">Scrolling to created questions...</span>`;
                        }
                    } else {
                        // Not on questions page - navigate first, then scroll will happen after reload
                        content += `<br><span class="text-xs text-gray-500">Navigating to questions...</span>`;
                    }
                }
                
                messages.value.push(stampMessage({
                    role: 'assistant',
                    content: content,
                    type: 'normal'
                }));
                
                toast.success('Action executed successfully');
                
                // Reset plural question state after execution
                pluralQuestionRequestedCount.value = null;
                pluralQuestionOriginalMessage.value = null;
                
                // Reload questions page for create and move actions so new questions appear immediately
                if ((dataToUse?.action?.startsWith('express_') && dataToUse?.action?.endsWith('_create')) ||
                    dataToUse?.action === 'express_question_move') {
                    if (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/')) {
                        // Full reload ensures new questions are visible (only: ['questions'] can miss updates)
                        router.reload({
                            onSuccess: () => {
                                // Check if this is survey creation (multiple questions)
                                const isSurveyCreation = dataToUse?.action === 'express_survey_create';
                                
                                const questions = usePage().props.questions || [];
                                const lastMessage = messages.value[messages.value.length - 1];
                                
                                if (isSurveyCreation) {
                                    if (questions.length > 0) {
                                        const lastQuestion = questions[questions.length - 1];
                                        if (lastQuestion && questionnaireCode) {
                                            showTranslateToast(questionnaireCode, lastQuestion.id, lastQuestion.order, 'added');
                                            
                                            const variableStart = usePage().props?.questionnaire?.setup?.variable_start || 1;
                                            const lastQuestionNumber = `Q${parseInt(lastQuestion.order) + parseInt(variableStart) - 1}`;
                                            const scrollFunction = `window.__scrollToQuestion && window.__scrollToQuestion(${lastQuestion.id})`;
                                            
                                            if (lastMessage) {
                                                if (lastMessage.content.includes('Scrolling to created questions')) {
                                                    lastMessage.content = lastMessage.content.replace(
                                                        'Scrolling to created questions...',
                                                        `<span onclick="${scrollFunction}" class="link cursor-pointer hover:underline inline-block" style="color: var(--mcolor); text-decoration: underline;">Go to ${lastQuestionNumber}</span>`
                                                    );
                                                } else if (lastMessage.content.includes('Navigating to questions')) {
                                                    lastMessage.content = lastMessage.content.replace(
                                                        'Navigating to questions...',
                                                        `<span onclick="${scrollFunction}" class="link cursor-pointer hover:underline inline-block" style="color: var(--mcolor); text-decoration: underline;">Go to ${lastQuestionNumber}</span>`
                                                    );
                                                }
                                            }
                                        }
                                    }
                                } else if (questionId && dataToUse?.action?.endsWith('_create')) {
                                    const questions = usePage().props.questions || [];
                                    const createdQuestion = questions.find(q => q.id === questionId);
                                    if (createdQuestion && questionnaireCode) {
                                        showTranslateToast(questionnaireCode, createdQuestion.id, createdQuestion.order, 'added');
                                    }
                                }
                            }
                        });
                    } else if (response.data?.route) {
                        const isSurveyCreation = dataToUse?.action === 'express_survey_create';
                        if (isSurveyCreation) {
                            router.visit(response.data.route);
                        } else if (questionId) {
                            router.visit(response.data.route);
                        }
                    }
                }
                
                // Reload page for move actions to reflect new question order
                if (dataToUse?.action === 'express_question_move') {
                    if (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/')) {
                        router.reload({ 
                            only: ['questions']
                        });
                    }
                }
                
                // Reload page for question options update to reflect changes in edit modal
                // Note: Options/layout updates don't change question text, so no auto-translate needed
                if (dataToUse?.action === 'express_question_options_update') {
                    if (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/')) {
                        const questionnaireCode = confirmData.questionnaire_code;
                        const questionId = response.data?.question_id;
                        
                        router.reload({ 
                            only: ['questions'],
                            preserveScroll: true,
                            onSuccess: () => {
                                // No auto-scroll - user can click "Go to" link if needed
                            }
                        });
                    }
                }
                
                // Reload page for question choice update to reflect changes in edit modal
                // Note: Choice updates don't change question text, so no auto-translate needed
                if (dataToUse?.action === 'express_question_choice_update' || 
                    dataToUse?.action?.startsWith('express_question_choice') ||
                    dataToUse?.action?.endsWith('_choice')) {
                    if (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/')) {
                        const questionnaireCode = confirmData.questionnaire_code;
                        const questionId = response.data?.question_id;
                        
                        router.reload({ 
                            only: ['questions'],
                            preserveScroll: true,
                            onSuccess: () => {
                                // No auto-scroll - user can click "Go to" link if needed
                            }
                        });
                    }
                }
                
                // Reload page for general question update (text changes) - should trigger auto-translate
                if (dataToUse?.action === 'express_question_update' || 
                    dataToUse?.action === 'update_question') {
                    if (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/')) {
                        const questionnaireCode = confirmData.questionnaire_code;
                        const questionId = response.data?.question_id;
                        
                        router.reload({ 
                            only: ['questions'],
                            preserveScroll: true,
                            onSuccess: () => {
                                if (questionId) {
                                    const questions = usePage().props.questions || [];
                                    const updatedQuestion = questions.find(q => q.id === questionId);
                                    if (updatedQuestion && questionnaireCode) {
                                        // Question text was updated, trigger auto-translate
                                        showTranslateToast(questionnaireCode, updatedQuestion.id, updatedQuestion.order, 'updated');
                                    }
                                }
                            }
                        });
                    }
                }
                
            } else if (dataToUse.actionable_id) {
                let questionnaireCode = survey.value?.code || 
                                      survey.value?.questionnaire_code || 
                                      survey.value?.questionnaire?.code ||
                                      null;
                
                if (!questionnaireCode) {
                    const urlParams = new URLSearchParams(window.location.search);
                    questionnaireCode = urlParams.get('questionnaire_code') || 
                                      urlParams.get('code') || 
                                      null;
                }
                
                // The route structure is /survey/{questionnaire_code}/
                if (!questionnaireCode) {
                    const pathSegments = window.location.pathname.split('/');
                    const surveyIndex = pathSegments.indexOf('survey');
                    if (surveyIndex !== -1 && pathSegments[surveyIndex + 1]) {
                        questionnaireCode = pathSegments[surveyIndex + 1];
                    }
                }
                
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
                
                const response = await axios.get(route('api.ai.execute', dataToUse.actionable_id), {
                    params: requestParams
                });
                
                console.log('Legacy response:', response.data);
                
                let content = `✅ ${response.data.message}`;
                if (response.data?.route) {
                    // Check if we can extract questionId from response or if we're on questions page
                    const questionId = response.data?.question_id;
                    if (questionId && (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/'))) {
                        // Show "Go to" link (no auto-scroll)
                        const scrollFunction = `window.__scrollToQuestion && window.__scrollToQuestion(${questionId})`;
                        content += `<br><span onclick="${scrollFunction}" class="link cursor-pointer hover:underline inline-block" style="color: var(--mcolor); text-decoration: underline;">Go to question</span>`;
                    } else if (questionId) {
                        router.visit(response.data.route);
                        content += `<br><span class="text-xs text-gray-500">Navigating to question...</span>`;
                    } else if (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/')) {
                        // On questions page but no questionId - try to scroll to last question
                        const questions = usePage().props.questions || [];
                        if (questions.length > 0) {
                            const lastQuestion = questions[questions.length - 1];
                            const variableStart = usePage().props?.questionnaire?.setup?.variable_start || 1;
                            const lastQuestionNumber = `Q${parseInt(lastQuestion.order) + parseInt(variableStart) - 1}`;
                            const scrollFunction = `window.__scrollToQuestion && window.__scrollToQuestion(${lastQuestion.id})`;
                            content += `<br><span onclick="${scrollFunction}" class="link cursor-pointer hover:underline inline-block" style="color: var(--mcolor); text-decoration: underline;">Go to ${lastQuestionNumber}</span>`;
                        } else {
                            content += `<br><span class="text-xs text-gray-500">Scrolling to created questions...</span>`;
                        }
                    } else {
                        // Not on questions page - navigate first, then scroll will happen after reload
                        content += `<br><span class="text-xs text-gray-500">Navigating to questions...</span>`;
                    }
                }
                
                messages.value.push(stampMessage({
                    role: 'assistant',
                    content: content,
                    type: 'normal'
                }));
                
                toast.success('Action executed successfully');
            }
        }
    } catch (error) {
        console.error('Error executing action:', error);
        console.error('Error response:', error.response?.data);
        console.error('Error status:', error.response?.status);
        
        messages.value.push(stampMessage({
            role: 'assistant',
            content: 'Sorry, I couldn\'t complete that action.',
            type: 'normal'
        }));
        toast.error('Failed to execute action');
    } finally {
        stopProgressSimulation();
        actionData.value = null;
        isPreparingQuestion.value = false;
        isPreparingSurvey.value = false;
        isCreatingQuestions.value = false;
        creatingQuestionsCount.value = 0;
        focusMessageInput();
    }
};

const cancelAction = async () => {
    try {
        if (actionData.value?.confirmation_buttons) {
            await axios.post(route('api.chat.cancel-action'));
        }
        
        messages.value.push(stampMessage({
            role: 'assistant',
            content: ' Action cancelled',
            type: 'normal'
        }));
    } catch (error) {
        console.error('Error cancelling action:', error);
        messages.value.push(stampMessage({
            role: 'assistant',
            content: ' Action cancelled',
            type: 'normal'
        }));
    } finally {
        stopProgressSimulation();
        actionData.value = null;
        isPreparingQuestion.value = false;
        isPreparingSurvey.value = false;
        isCreatingQuestions.value = false;
        creatingQuestionsCount.value = 0;
    }
};

const cancelPreparing = () => {
    stopProgressSimulation();
    isPreparingQuestion.value = false;
    isPreparingSurvey.value = false;
    isCreatingQuestions.value = false;
    creatingQuestionsCount.value = 0;
    actionData.value = null;
    loading.value = false;
    
    messages.value = messages.value.filter(msg => !msg.isStreaming);
    
    messages.value.push(stampMessage({
        role: 'assistant',
        content: 'Creation cancelled',
        type: 'normal'
    }));
    
    if (abortController.value) {
        abortController.value.abort();
        abortController.value = null;
    }
    
    if (streamReader.value) {
        streamReader.value.cancel().catch(() => {
        });
        streamReader.value = null;
    }
    
    axios.post(route('api.chat.cancel-action')).catch(() => {
    });
};

const handleKeydown = (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        if (!inputsDisabled.value) {
            sendMessage();
        }
    }
};

const autoResize = () => {
    if (messageInput.value) {
        messageInput.value.style.height = 'auto';
        messageInput.value.style.height = Math.min(messageInput.value.scrollHeight, 120) + 'px';
    }
};

const focusMessageInput = () => {
    if (props.open && messageInput.value) {
        nextTick(() => {
            if (messageInput.value && !inputsDisabled.value) {
                messageInput.value.focus();
            }
        });
    }
};

// Get questionnaire name from page props
const getQuestionnaireName = () => {
    const pageProps = usePage().props;
    return pageProps?.questionnaire?.setup?.name || 
           pageProps?.questionnaire?.name ||
           survey.value?.name || 
           null;
};

// Check and insert questionnaire indicator if questionnaire changed
// TEMPORARY: Always show indicator for debugging
const checkAndInsertQuestionnaireIndicator = () => {
    const questionnaireName = getQuestionnaireName();
    
    if (questionnaireName) {
        // TEMPORARY: Always show indicator for debugging
        // Check if we already have an indicator for this questionnaire at the end
        const lastIndicator = [...messages.value].reverse().find(msg => 
            msg.type === 'questionnaire_indicator' && msg.content === questionnaireName
        );
        
        // Only add if we don't already have this indicator at the end
        if (!lastIndicator) {
            messages.value.push(stampMessage({
                role: 'system',
                type: 'questionnaire_indicator',
                content: questionnaireName,
                timestamp: new Date().toISOString()
            }));
        }
        currentQuestionnaireName.value = questionnaireName;
    }
};

const typeTextWithEffect = (fullText, messageIndex, delay = 8) => {
    if (!messages.value[messageIndex]) return;
    
    if (!fullText || fullText.length === 0) {
        if (messages.value[messageIndex]) {
            messages.value[messageIndex].isStreaming = false;
        }
        return;
    }
    
    typingEffectActive.value = true;
    messages.value[messageIndex].content = '';
    messages.value[messageIndex].isStreaming = true;
    let currentIndex = 0;
    
    const typeNextChar = () => {
        if (currentIndex < fullText.length && messages.value[messageIndex]) {
            messages.value[messageIndex].content = fullText.substring(0, currentIndex + 1);
            currentIndex++;
            typingEffectTimeout = setTimeout(typeNextChar, delay);
        } else {
            typingEffectActive.value = false;
            if (messages.value[messageIndex]) {
                messages.value[messageIndex].isStreaming = false;
                messages.value[messageIndex].content = fullText;
            }
        }
    };
    
    if (typingEffectTimeout) {
        clearTimeout(typingEffectTimeout);
    }
    
    typeNextChar();
};

const stopTypingEffect = () => {
    if (typingEffectTimeout) {
        clearTimeout(typingEffectTimeout);
        typingEffectTimeout = null;
    }
    typingEffectActive.value = false;
};

const sendMessage = async (options = {}) => {
    const retryPrompt = options.retryPrompt;
    const skipUserMessage = !!retryPrompt;
    if (!skipUserMessage && (newMessage.value.trim() === '' || loading.value || analyzingScript.value)) return;
    
    // Reset plural-request count for this new message; it may be set again below
    pluralQuestionRequestedCount.value = null;
    
    // Check for questionnaire change before sending message
    checkAndInsertQuestionnaireIndicator();
    
    // Ensure this run is always tied to a dedicated MongoDB conversation
    // document. If we don't yet have a conversation_id (e.g. first message
    // from onboarding), create one explicitly before sending so that each
    // visible chat thread corresponds to its own conversation document.
    if (!conversationId.value) {
        const maybeNewId = await createBackendConversation();
        if (maybeNewId) {
            conversationId.value = maybeNewId;
            saveActiveConversationId(maybeNewId);
        }
    }
    
    if (partSuggestionMessageIndex.value !== null) {
        partSuggestionsDisabled.value = true;
    }

    const userQuery = skipUserMessage ? retryPrompt : newMessage.value;
    if (!skipUserMessage) newMessage.value = '';
    let effectiveQuery = userQuery;
    const hadPendingPlural = skipUserMessage ? false : !!pendingPluralQuestionMessage.value;
    let displayContent = userQuery;

    if (!skipUserMessage && pendingPluralQuestionMessage.value) {
        const count = parseQuestionCountFromReply(userQuery);
        if (count != null && count >= 1 && count <= 100) {
            const combined = buildCombinedPluralQuestionMessage(pendingPluralQuestionMessage.value, count);
            effectiveQuery = combined;
            displayContent = userQuery; // Show only the number user selected/typed; AI receives full context via effectiveQuery
            pluralQuestionRequestedCount.value = count;
            pluralQuestionOriginalMessage.value = pendingPluralQuestionMessage.value;
        } else {
            pluralQuestionRequestedCount.value = null;
            pluralQuestionOriginalMessage.value = null;
        }
        pendingPluralQuestionMessage.value = null;
    }

    if (!skipUserMessage) {
        messages.value.push(stampMessage({
            role: 'user',
            content: displayContent,
            type: 'normal'
        }));
    }

    disablePreviousFeedback();

    const filesToSend = researchMode.value ? [] : [...attachedFiles.value];
    if (!researchMode.value) {
        attachedFiles.value = [];
    }
    addedQuestions.value = [];

    // Plural question detection:
    // - If plural with NO number: ask "How many?" and store original prompt
    // - If plural WITH number: remember the requested count so we can run the action multiple times
    if (!hadPendingPlural) {
        const { isPlural, count } = detectPluralQuestionRequest(effectiveQuery);
        if (isPlural && count == null) {
            messages.value.push(stampMessage({
                role: 'assistant',
                content: 'How many questions would you like to create?',
                type: 'plural-question-count',
                options: [2, 3, 4, 5]
            }));
            pendingPluralQuestionMessage.value = effectiveQuery;
            scrollToBottom();
            return;
        } else if (isPlural && count != null && count >= 1 && count <= 100) {
            pluralQuestionRequestedCount.value = count;
            pluralQuestionOriginalMessage.value = effectiveQuery;
        }
    }
    
    // Check if user wants to cancel/nevermind
    const isNevermindRequest = detectNevermindRequest(effectiveQuery);
    
    // If user wants to cancel, handle it immediately
        if (isNevermindRequest) {
        pendingPluralQuestionMessage.value = null;
        pluralQuestionRequestedCount.value = null;
        pluralQuestionOriginalMessage.value = null;
        let hasCancelledSomething = false;
        
        // Cancel any ongoing actions
        if (actionData.value) {
            await cancelAction();
            hasCancelledSomething = true;
        }
        
        // Cancel part extraction if in progress
        if (loading.value && selectedPart.value) {
            cancelPartExtraction();
            hasCancelledSomething = true;
        }
        
        // Cancel script analysis if in progress
        if (analyzingScript.value) {
            analyzingScript.value = false;
            hasCancelledSomething = true;
        }
        
        // Cancel preparing dialogs
        if (isPreparingQuestion.value || isPreparingSurvey.value) {
            cancelPreparing();
            hasCancelledSomething = true;
        }
        
        // Stop any streaming messages
        stopTypingEffect();
        messages.value = messages.value.filter(msg => !msg.isStreaming);
        
        // Stop any loading state
        if (loading.value) {
            loading.value = false;
        }
        
        // Abort any ongoing requests
        if (abortController.value) {
            abortController.value.abort();
            abortController.value = null;
        }
        
        // Add acknowledgment message only if we actually cancelled something or if there's nothing to cancel
        if (!hasCancelledSomething) {
            messages.value.push(stampMessage({
                role: 'assistant',
                content: 'No problem! What would you like to do instead?',
                type: 'normal'
            }));
        }
        
        // Save user message to backend
        if (conversationId.value) {
            try {
                await axios.post(route('api.chat.utility-prompt'), {
                    type: 'user_message',
                    conversation_id: conversationId.value,
                    platform: platform.value,
                    user_message: effectiveQuery,
                    websitePosition: websitePosition.value || 'dashboard'
                }, {
                    headers: getAiHelpbotHeaders(),
                });
            } catch (error) {
                console.error('Failed to save user message:', error);
            }
        }
        
        scrollToBottom();
        return; // Don't send to backend
    }
    
    // Check if user is requesting customer support
    const isCustomerSupportRequest = detectCustomerSupportRequest(effectiveQuery);
    
    // If customer support is requested, show the card immediately and bypass intent detection
    if (isCustomerSupportRequest) {
        const userMessageContent = effectiveQuery;
        
        // Save user message to backend before showing support card
        if (conversationId.value) {
            try {
                await axios.post(route('api.chat.utility-prompt'), {
                    type: 'user_message',
                    conversation_id: conversationId.value,
                    platform: platform.value,
                    user_message: userMessageContent,
                    websitePosition: websitePosition.value || 'dashboard'
                }, {
                    headers: getAiHelpbotHeaders(),
                });
            } catch (error) {
                console.error('Failed to save user message for customer support:', error);
                // Continue even if save fails
            }
        }
        
        // Remove the streaming message if it was added
        const streamingIndex = messages.value.findIndex(m => m.isStreaming);
        if (streamingIndex !== -1) {
            messages.value.splice(streamingIndex, 1);
        }
        
        // Show customer support card after a brief delay for better UX
        setTimeout(() => {
            showCustomerSupportCard();
        }, 500);
        
        return; // Don't send to backend - bypass intent detection and out-of-topic checker
    }
    
    loading.value = true;
    
    stopTypingEffect();
    
    const streamingMessageIndex = messages.value.length;
    messages.value.push(stampMessage({
        role: 'assistant',
        content: '',
        type: 'normal',
        liked: false,
        disliked: false,
        feedbackSubmitted: false,
        feedbackDisabled: false,
        isStreaming: true
    }));
    
    abortController.value = new AbortController();
    streamReader.value = null;
    
    const userMessageLower = effectiveQuery.toLowerCase().trim();
    
    const informationalPatterns = [
        /^how\s+(to|do|can|should|would|could)/i,
        /^(what|why|when|where|can|should|could|would)\s+/i, 
        /tell\s+me\s+(how|what|about)/i,  
        /show\s+me\s+(how|what)/i, 
        /explain\s+(how|what|why)/i, 
        /^is\s+it\s+possible/i, 
        /^what\s+(is|are|does|do)/i, 
    ];
    
    const isInformationalQuery = informationalPatterns.some(pattern => pattern.test(userMessageLower));
    
    // Don't set preparing dialog based on keyword detection alone
    // Only show it when we actually receive progress updates or action_data indicating creation
    // This prevents false positives for informational queries like "what kinds of questions can I create?"
    
    try {
        let questionnaireCode = survey.value?.code || 
                              survey.value?.questionnaire_code || 
                              survey.value?.questionnaire?.code ||
                              null;
        
        if (!questionnaireCode) {
            const urlParams = new URLSearchParams(window.location.search);
            questionnaireCode = urlParams.get('questionnaire_code') || 
                              urlParams.get('code') || 
                              null;
        }
        
        // The route structure is /survey/{questionnaire_code}/
        if (!questionnaireCode) {
            const pathSegments = window.location.pathname.split('/');
            const surveyIndex = pathSegments.indexOf('survey');
            if (surveyIndex !== -1 && pathSegments[surveyIndex + 1]) {
                questionnaireCode = pathSegments[surveyIndex + 1];
            }
        }
        
        if (!questionnaireCode) {
            const pageProps = usePage().props;
            questionnaireCode = pageProps?.questionnaire?.code || 
                              pageProps?.questionnaire_code ||
                              null;
        }
        
        const activeSidebar = detectActiveSidebar();
        const sidebarState = getSidebarState();
        const mainPage = detectWebsitePositionFromPath() || detectWebsitePositionFromSidebar() || websitePosition.value;
        
        const requestData = {
            message: effectiveQuery,
            user_message_display: displayContent !== effectiveQuery ? displayContent : undefined,
            platform: platform.value,
            websitePosition: mainPage,
            folder: folder.value?.id ?? survey.value?.folder?.id,
            survey: survey.value?.id,
            surveyuuid: survey.value?.uuid,
            questionnaire_code: questionnaireCode,
            active_sidebar: activeSidebar,
            sidebar_state: sidebarState,
            main_page: mainPage,
            current_url: window.location.href,
            research_mode: researchMode.value,
            // Pass current conversation id so the backend can either
            // continue the existing conversation or create a fresh one
            // when this is null (e.g. after clicking "New conversation").
            conversation_id: conversationId.value || null,
            // When retrying, skip adding a new user message to the conversation
            ...(skipUserMessage && { is_retry: true }),
        };
        
        if (researchMode.value) {
            console.log('[Research Mode] Sending message with research mode enabled');
        }

        console.log('[Chatbot.vue] 1 received prompt');
        console.log('[Chatbot.vue] 2 sending to backend (websitePosition: ' + (mainPage || 'dashboard') + ')');

        // Express-first (same as Fluent): EXPRESS handles only actions and quick greetings; other intents go to ai-helpbot.
        let response;
        let fromHelpbotStream = false;
        if (filesToSend.length > 0 && !researchMode.value) {
            const formData = new FormData();
            formData.append('message', effectiveQuery);
            if (displayContent !== effectiveQuery) {
                formData.append('user_message_display', displayContent);
            }
            formData.append('platform', platform.value || '');
            formData.append('websitePosition', mainPage || '');
            formData.append('folder', (folder.value?.id ?? survey.value?.folder?.id) || '');
            formData.append('survey', survey.value?.id || '');
            formData.append('surveyuuid', survey.value?.uuid || '');
            formData.append('questionnaire_code', questionnaireCode || '');
            formData.append('active_sidebar', activeSidebar || '');
            formData.append('sidebar_state', JSON.stringify(sidebarState));
            formData.append('main_page', mainPage || '');
            formData.append('current_url', window.location.href);
            formData.append('research_mode', researchMode.value ? '1' : '0');
            formData.append('conversation_id', conversationId.value || '');
            if (skipUserMessage) {
                formData.append('is_retry', '1');
            }
            
            filesToSend.forEach((fileObj, index) => {
                formData.append(`attachments[${index}]`, fileObj.file);
            });
            
            response = await fetch(route('api.chat.message.stream.express'), {
                method: 'POST',
                headers: {
                    'Accept': 'text/event-stream',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
                body: formData,
                signal: abortController.value.signal
            });
        } else {
            response = await fetch(route('api.chat.message.stream.express'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
                body: JSON.stringify(requestData),
                signal: abortController.value.signal
            });
        }

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // If EXPRESS returned JSON route_to_helpbot, stream via Express proxy (same-origin) with Bearer token forwarded to ai-helpbot
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            const data = await response.json();
            if (data.route_to_helpbot) {
                if (data.conversation_id) {
                    conversationId.value = data.conversation_id;
                    saveActiveConversationId(data.conversation_id);
                }
                console.log('[Chatbot.vue] 5 passing to ai helpbot');
                fromHelpbotStream = true;
                const token = getAuthTokenForHelpbot();
                const helpbotPayload = {
                    websitePosition: requestData.websitePosition ?? requestData.main_page ?? 'dashboard',
                    platform: requestData.platform ?? 'express',
                    intent: data.intent ?? 'conversation',
                    prompt: requestData.message,
                    ...(requestData.research_mode && { research_mode: true }),
                };
                response = await fetch(route('api.chat.message.stream'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/event-stream',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Authorization': `Bearer ${token}`,
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    credentials: 'include',
                    body: JSON.stringify(helpbotPayload),
                    signal: abortController.value.signal,
                });
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
            }
        }

        const reader = response.body.getReader();
        streamReader.value = reader;
        const decoder = new TextDecoder();
        let buffer = '';
        let firstChunkReceived = false;
        let receivedActionData = null;
        let accumulatedText = '';
        let chunkCount = 0;
        let totalTextLength = 0;
        let useTypingEffect = fromHelpbotStream;
        const LARGE_CHUNK_THRESHOLD = 100;

        try {
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';
                for (const line of lines) {
                    if (line.startsWith('data: ')) {
                        try {
                            const data = JSON.parse(line.slice(6));
                            // Handle progress updates for survey generation
                            if (data.progress) {
                                surveyProgress.value = data.progress;
                                
                                // Stop any existing progress simulation
                                stopProgressSimulation();
                                
                                // Keep preparing dialog visible during progress
                                if (data.progress.stage === 'planning' || data.progress.stage === 'generating' || 
                                    data.progress.stage === 'plan_complete' || data.progress.stage === 'finalizing' ||
                                    data.progress.stage === 'complete') {
                                    isPreparingQuestion.value = true;
                                    isPreparingSurvey.value = true;
                                    
                                    // Simulate progress during generation if we have a total
                                    if (data.progress.stage === 'generating' && data.progress.total > 0) {
                                        startProgressSimulation(data.progress.total);
                                    }
                                    
                                    // Stop simulation and show completion
                                    if (data.progress.stage === 'complete') {
                                        stopProgressSimulation();
                                        // Ensure progress shows 100%
                                        surveyProgress.value = {
                                            ...surveyProgress.value,
                                            current: surveyProgress.value.total
                                        };
                                        // Hide after showing completion message
                                        setTimeout(() => {
                                            isPreparingQuestion.value = false;
                                            isPreparingSurvey.value = false;
                                        }, 2000);
                                    }
                                } else if (data.progress.stage === 'error') {
                                    // Hide preparing dialog on error
                                    stopProgressSimulation();
                                    isPreparingQuestion.value = false;
                                    isPreparingSurvey.value = false;
                                    
                                    const errorMessage = data.progress.message || 'An error occurred during survey generation. Please try again.';
                                    if (messages.value[streamingMessageIndex]) {
                                        messages.value[streamingMessageIndex].content = errorMessage;
                                        messages.value[streamingMessageIndex].isStreaming = false;
                                        messages.value[streamingMessageIndex].retryPrompt = effectiveQuery;
                                    } else {
                                        messages.value.push(stampMessage({
                                            role: 'assistant',
                                            content: errorMessage,
                                            type: 'normal',
                                            isStreaming: false,
                                            retryPrompt: effectiveQuery
                                        }));
                                    }
                                    
                                    toast.error('Survey generation failed');
                                    loading.value = false;
                                } else if (data.progress.stage === 'questions_complete') {
                                    // Stop simulation and keep showing completion for a moment
                                    stopProgressSimulation();
                                    // Ensure progress shows 100%
                                    surveyProgress.value = {
                                        ...surveyProgress.value,
                                        current: surveyProgress.value.total
                                    };
                                    setTimeout(() => {
                                        isPreparingQuestion.value = false;
                                        isPreparingSurvey.value = false;
                                    }, 1500);
                                }
                            }
                            
                            // Handle errors - also hide preparing dialog
                            if (data.error) {
                                stopProgressSimulation();
                                isPreparingQuestion.value = false;
                                isPreparingSurvey.value = false;
                                
                                // Display error message to user
                                if (messages.value[streamingMessageIndex]) {
                                    messages.value[streamingMessageIndex].content = data.error;
                                    messages.value[streamingMessageIndex].isStreaming = false;
                                    messages.value[streamingMessageIndex].retryPrompt = effectiveQuery;
                                } else {
                                    messages.value.push(stampMessage({
                                        role: 'assistant',
                                        content: data.error,
                                        type: 'normal',
                                        isStreaming: false,
                                        retryPrompt: effectiveQuery
                                    }));
                                }
                                
                                toast.error('Survey generation failed');
                                loading.value = false;
                            }
                            
                            if (data.action_data) {
                                receivedActionData = data.action_data;
                                
                                // Check if intent is 'create'
                                const isCreateIntent = receivedActionData.intent === 'create' || 
                                    (receivedActionData.action && receivedActionData.action.includes('_create'));
                                
                                // Determine if it's a survey creation
                                const isSurveyCreate = receivedActionData.action === 'express_survey_create' || 
                                    (receivedActionData.action && receivedActionData.action.includes('survey'));
                                
                                // Remove streaming message if it exists
                                if (messages.value[streamingMessageIndex]) {
                                    messages.value.splice(streamingMessageIndex, 1);
                                }
                                loading.value = false;
                                
                                // Check if still loading/preparing
                                const isStillLoading = receivedActionData.loading === true || 
                                    (receivedActionData.preview_data && Object.keys(receivedActionData.preview_data).length === 0);
                                
                                // If it's a create intent and still loading, show preparing dialog
                                if (isCreateIntent && isStillLoading) {
                                    isPreparingQuestion.value = true;
                                    isPreparingSurvey.value = isSurveyCreate;
                                } else {
                                    // Hide preparing dialog when action_data arrives and not loading
                                    setTimeout(() => {
                                        isPreparingQuestion.value = false;
                                        isPreparingSurvey.value = false;
                                    }, 500);
                                }
                                
                                // Set loading state for action_data
                                if (receivedActionData.preview_data && Object.keys(receivedActionData.preview_data).length > 0) {
                                    receivedActionData.loading = receivedActionData.loading !== undefined ? receivedActionData.loading : false;
                                } else {
                                    receivedActionData.loading = isStillLoading;
                                }
                                
                                // Handle duplicate question selection
                                if (receivedActionData.show_question_selection && receivedActionData.intent === 'duplicate') {
                                    let questionnaireCode = survey.value?.code || 
                                                          survey.value?.questionnaire_code || 
                                                          survey.value?.questionnaire?.code ||
                                                          receivedActionData.questionnaire_code ||
                                                          null;
                                    
                                    if (!questionnaireCode) {
                                        const pathSegments = window.location.pathname.split('/');
                                        const surveyIndex = pathSegments.indexOf('survey');
                                        if (surveyIndex !== -1 && pathSegments[surveyIndex + 1]) {
                                            questionnaireCode = pathSegments[surveyIndex + 1];
                                        }
                                    }
                                    
                                    if (!questionnaireCode) {
                                        const pageProps = usePage().props;
                                        questionnaireCode = pageProps?.questionnaire?.code || 
                                                          pageProps?.questionnaire_code ||
                                                          null;
                                    }
                                    
                                    if (questionnaireCode) {
                                        try {
                                            const questionsResponse = await axios.post(route('api.chat.get-questions-for-selection'), {
                                                questionnaire_code: questionnaireCode
                                            });
                                            
                                            if (questionsResponse.data.success && questionsResponse.data.questions) {
                                                questionSuggestions.value = questionsResponse.data.questions;
                                                upsertQuestionSuggestionMessage(questionsResponse.data.questions);
                                            }
                                        } catch (error) {
                                            console.error('Failed to fetch questions for selection:', error);
                                            toast.error('Failed to load questions. Please try again.');
                                        }
                                    }
                                } else {
                                    // Set actionData - this will trigger the action confirmation dialog to show
                                    actionData.value = receivedActionData;
                                    const ad = receivedActionData;
                                    console.log('[Chatbot.vue] 6A action executed and saved', ad?.action || ad?.intent || ad);
                                    console.log('[Chatbot.vue] 7 shown response to the user');
                                    
                                    // Only reset preparing flags if not still loading
                                    // If still loading, the preparing dialog will show until loading completes
                                    if (!isStillLoading) {
                                        // Reset preparing flags so the action confirmation dialog shows instead of preparing dialog
                                        isPreparingQuestion.value = false;
                                        isPreparingSurvey.value = false;
                                    }
                                }
                            }
                            
                            if (data.text && !receivedActionData && messages.value[streamingMessageIndex]) {
                                if (!firstChunkReceived) {
                                    loading.value = false;
                                    firstChunkReceived = true;
                                    isPreparingQuestion.value = false;
                                    isPreparingSurvey.value = false;
                                }
                                
                                chunkCount++;
                                totalTextLength += data.text.length;
                                
                                if (fromHelpbotStream && useTypingEffect) {
                                    accumulatedText += data.text;
                                } else {
                                    messages.value[streamingMessageIndex].content += data.text;
                                }
                            }
                            
                            if (data.conversation_id && !conversationId.value) {
                                conversationId.value = data.conversation_id;
                                saveActiveConversationId(data.conversation_id);
                                console.log('Conversation ID received:', conversationId.value);
                                if (window.innerWidth >= 768) showSidebar.value = true;
                            }
                            if (data.message_id && messages.value[streamingMessageIndex]) {
                                messages.value[streamingMessageIndex]._id = data.message_id;
                                console.log('Message ID received:', data.message_id);
                            }
                            if (data.timestamp && messages.value[streamingMessageIndex]) {
                                messages.value[streamingMessageIndex].timestamp = data.timestamp;
                                messages.value[streamingMessageIndex].createdAt = data.timestamp;
                            }
                            if (data.done) {
                                if (messages.value[streamingMessageIndex]) {
                                    // For helpbot responses, always use typing effect
                                    if (useTypingEffect && fromHelpbotStream) {
                                        stopTypingEffect(); // Clear any existing typing effect
                                        
                                        // Make sure we have accumulated text (handle case where text and done come together)
                                        if (data.text && !accumulatedText) {
                                            accumulatedText = data.text;
                                        }
                                        
                                        const finalContent = accumulatedText || messages.value[streamingMessageIndex].content || '';
                                        
                                        if (finalContent && finalContent.trim().length > 0) {
                                            messages.value[streamingMessageIndex].content = '';
                                            typeTextWithEffect(finalContent, streamingMessageIndex, 8);
                                            
                                            setTimeout(() => {
                                                if (conversationId.value && messages.value[streamingMessageIndex]) {
                                                    const content = messages.value[streamingMessageIndex].content || '';
                                                    if (content) {
                                                        fetch(route('api.chat.append-assistant-message'), {
                                                            method: 'POST',
                                                            headers: {
                                                                'Content-Type': 'application/json',
                                                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                                                'X-Requested-With': 'XMLHttpRequest',
                                                            },
                                                            credentials: 'include',
                                                            body: JSON.stringify({ conversation_id: conversationId.value, content }),
                                                        }).catch(() => {});
                                                    }
                                                }
                                            }, finalContent.length * 5 + 500);
                                        } else {
                                            messages.value[streamingMessageIndex].isStreaming = false;
                                        }
                                    } else {
                                        messages.value[streamingMessageIndex].isStreaming = false;
                                        const content = messages.value[streamingMessageIndex].content || '';
                                        if (fromHelpbotStream && content && conversationId.value) {
                                            fetch(route('api.chat.append-assistant-message'), {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                                    'X-Requested-With': 'XMLHttpRequest',
                                                },
                                                credentials: 'include',
                                                body: JSON.stringify({ conversation_id: conversationId.value, content }),
                                            }).catch(() => {});
                                        }
                                    }
                                }
                                console.log('[Chatbot.vue] 7 shown to the user DONE');
                                isPreparingQuestion.value = false;
                                isPreparingSurvey.value = false;
                                focusMessageInput();
                                break;
                            }
                            
                            // Handle errors - hide preparing dialog
                            if (data.error) {
                                stopProgressSimulation();
                                isPreparingQuestion.value = false;
                                isPreparingSurvey.value = false;
                                
                                // Display error message to user
                                if (messages.value[streamingMessageIndex]) {
                                    messages.value[streamingMessageIndex].content = data.error;
                                    messages.value[streamingMessageIndex].isStreaming = false;
                                    messages.value[streamingMessageIndex].retryPrompt = effectiveQuery;
                                } else {
                                    messages.value.push(stampMessage({
                                        role: 'assistant',
                                        content: data.error,
                                        type: 'normal',
                                        isStreaming: false,
                                        retryPrompt: effectiveQuery
                                    }));
                                }
                                
                                toast.error('An error occurred');
                                loading.value = false;
                            }
                        } catch (e) {
                            console.warn('Failed to parse SSE data:', e);
                        }
                    }
                }
            }
        } catch (streamError) {
            if (streamError.name === 'AbortError' || abortController.value?.signal.aborted) {
                console.log('Stream cancelled by user');
                stopTypingEffect();
            } else {
                console.error('Error in streaming:', streamError);
                stopTypingEffect();
                
                if (isPreparingQuestion.value || isPreparingSurvey.value) {
                    stopProgressSimulation();
                    const errorMessage = 'The request timed out or encountered an error. Please try again.';
                    
                    if (messages.value[streamingMessageIndex]) {
                        messages.value[streamingMessageIndex].content = errorMessage;
                        messages.value[streamingMessageIndex].isStreaming = false;
                        messages.value[streamingMessageIndex].retryPrompt = effectiveQuery;
                    } else {
                        messages.value.push(stampMessage({
                            role: 'assistant',
                            content: errorMessage,
                            type: 'normal',
                            isStreaming: false,
                            retryPrompt: effectiveQuery
                        }));
                    }
                    
                    toast.error('Request failed. Please try again.');
                }
            }
            isPreparingQuestion.value = false;
            isPreparingSurvey.value = false;
        } finally {
            streamReader.value = null;
            if (!abortController.value?.signal.aborted) {
                abortController.value = null;
            }
        }
    } catch (error) {
        const isAborted = error.name === 'AbortError' || abortController.value?.signal.aborted;
        
        if (isAborted) {
            // User cancelled, don't show error message
            console.log('Request cancelled by user');
            // Clean up state
            stopProgressSimulation();
            isPreparingQuestion.value = false;
            isPreparingSurvey.value = false;
            if (messages.value[streamingMessageIndex] && messages.value[streamingMessageIndex].content === '') {
                messages.value.splice(streamingMessageIndex, 1);
            }
            abortController.value = null;
            streamReader.value = null;
            return;
        }
        
        console.error('Error sending message:', error);
        stopProgressSimulation();
        isPreparingQuestion.value = false;
        isPreparingSurvey.value = false;
        if (messages.value[streamingMessageIndex] && messages.value[streamingMessageIndex].content === '') {
            messages.value.splice(streamingMessageIndex, 1);
        }
        
        let errorMessage = 'Sorry, I encountered an error. Please try again later.';
        
        if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
            errorMessage = 'I\'m sorry, the request timed out. Please try again with a shorter message or try again later.';
        } else if (error.response) {
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
            errorMessage = 'I\'m sorry, I couldn\'t connect to the server. Please check your internet connection and try again.';
        } else {
            errorMessage = 'I\'m sorry, something unexpected happened. Please try again.';
        }
        
        messages.value.push(stampMessage({
            role: 'assistant',
            content: errorMessage,
            liked: false,
            disliked: false,
            feedbackSubmitted: false,
            feedbackDisabled: false,
            isStreaming: false,
            retryPrompt: effectiveQuery
        }));
        toast.error('Failed to get response from the AI assistant');
        
        // Clean up references
        abortController.value = null;
        streamReader.value = null;
    } finally {
        loading.value = false;
        isPreparingQuestion.value = false;
        isPreparingSurvey.value = false;
        focusMessageInput();
    }
    fetchTokenUsage();
    if (chatListRef.value && typeof chatListRef.value.refresh === 'function') {
        setTimeout(() => chatListRef.value.refresh(), 500);
    }
};

const scrollToBottom = () => {
    if (chatContainer.value) {
        setTimeout(() => {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }, 50);
    }
};

const scrollToQuestion = (questionId) => {
    if (!questionId) return;
    
    // Wait for DOM to be ready and page to reload if needed
    const attemptScroll = (attempts = 0) => {
        if (attempts > 10) {
            console.warn('Failed to find question element after multiple attempts:', questionId);
            return;
        }
        
        const pageProps = usePage().props;
        const questions = pageProps?.questions || [];
        const questionIndex = questions.findIndex(q => q.id === questionId);
        
        if (questionIndex < 0) {
            // If question not found yet, wait a bit more (might still be loading)
            if (attempts < 5) {
                setTimeout(() => attemptScroll(attempts + 1), 200);
            } else {
                console.warn('Question not found in questions array:', questionId);
            }
            return;
        }
        
        // Find all question containers - try multiple selectors
        let questionElement = null;
        const selectors = [
            '.bg-white.rounded-lg.overflow-hidden.min-h-\\[300px\\]',
            '.bg-white.rounded-lg.overflow-hidden',
            '.bg-white.rounded-lg',
            'div[class*="bg-white"][class*="rounded-lg"]'
        ];
        
        for (const selector of selectors) {
            try {
                const containers = document.querySelectorAll(selector);
                if (containers.length > questionIndex) {
                    questionElement = containers[questionIndex];
                    break;
                }
            } catch (e) {
                // Invalid selector, try next
            }
        }
        
        if (questionElement) {
            // Scroll to the question element
            questionElement.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center',
                inline: 'nearest'
            });
            
            // Add a highlight effect
            const originalBoxShadow = questionElement.style.boxShadow;
            questionElement.style.transition = 'box-shadow 0.3s ease';
            questionElement.style.boxShadow = '0 0 0 3px rgba(238, 146, 12, 0.5)';
            setTimeout(() => {
                questionElement.style.boxShadow = originalBoxShadow || '';
            }, 1000);
        } else {
            // Retry if element not found yet
            if (attempts < 5) {
                setTimeout(() => attemptScroll(attempts + 1), 200);
            } else {
                console.warn('Question element not found at index:', questionIndex);
            }
        }
    };
    
    // Start attempting to scroll
    setTimeout(() => attemptScroll(), 100);
};

watch(messages, (newMessages, oldMessages) => {
    scrollToBottom();
    try {
        if (storageKey) {
            localStorage.setItem(storageKey, JSON.stringify(messages.value));
        }
    } catch (e) {
        console.warn('Failed to persist chat to localStorage', e);
    }
    
    if (newMessages.length > 0 && oldMessages && oldMessages.length > 0) {
        const lastMessage = newMessages[newMessages.length - 1];
        const wasStreaming = oldMessages.some(msg => msg.isStreaming);
        const isNowDone = lastMessage.role === 'assistant' && !lastMessage.isStreaming;
        
        if (wasStreaming && isNowDone) {
            focusMessageInput();
        }
    }
}, { deep: true });

watch(partSuggestions, (parts) => {
    if (Array.isArray(parts) && parts.length > 0) {
        partSuggestionsDisabled.value = false;
        upsertPartSuggestionMessage(parts);
    } else {
        partSuggestionsDisabled.value = false;
        removePartSuggestionMessage();
    }
});

watch(questionSuggestions, (questions) => {
    if (Array.isArray(questions) && questions.length > 0) {
        questionSuggestionsDisabled.value = false;
        upsertQuestionSuggestionMessage(questions);
    } else {
        questionSuggestionsDisabled.value = false;
        removeQuestionSuggestionMessage();
    }
});

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        nextTick(() => {
            scrollToBottom();
            focusMessageInput();
        });
        loadQuestions();
        // Websocket is already set up in onMounted, no need to restart
        fetchTokenUsage(); // Refresh token usage when opening
    } else {
        // Keep websocket connection active even when closed
        // stopTokenUsageWebsocket(); // Don't disconnect when closing, only on unmount
    }
});

// Watch for questionnaire changes when navigating between questionnaires
watch(() => {
    const pageProps = usePage().props;
    return pageProps?.questionnaire?.setup?.name || 
           pageProps?.questionnaire?.name ||
           survey.value?.name || 
           null;
}, (newQuestionnaireName) => {
    if (newQuestionnaireName) {
        // TEMPORARY: Always show indicator for debugging
        // Check if we already have an indicator for this questionnaire at the end
        const lastIndicator = [...messages.value].reverse().find(msg => 
            msg.type === 'questionnaire_indicator' && msg.content === newQuestionnaireName
        );
        
        // Only add if we don't already have this indicator at the end
        if (!lastIndicator && messages.value.length > 0) {
            messages.value.push(stampMessage({
                role: 'system',
                type: 'questionnaire_indicator',
                content: newQuestionnaireName,
                timestamp: new Date().toISOString()
            }));
        }
        currentQuestionnaireName.value = newQuestionnaireName;
    }
});

watch(() => usePage().props.questions, () => {
    loadQuestions();
}, { deep: true });

const toggle = () => {
    emit('update:open', !props.open);
};

const toggleResearchMode = () => {
    researchMode.value = !researchMode.value;
    try {
        localStorage.setItem(RESEARCH_MODE, researchMode.value ? 'true' : 'false');
    } catch (_) {}
    console.log('[Research Mode] Toggled:', researchMode.value ? 'ON' : 'OFF');
    if (researchMode.value) {
        attachedFiles.value = [];
    } else {
        addedQuestions.value = [];
    }
};

const toggleQuestionDropdown = () => {
    showQuestionDropdown.value = !showQuestionDropdown.value;
    if (showQuestionDropdown.value) {
        loadQuestions();
        selectedQuestions.value = selectedQuestions.value.filter(selected => 
            availableQuestions.value.some(available => available.id === selected.id)
        );
    }
};

const toggleQuestionSelection = (question) => {
    const index = selectedQuestions.value.findIndex(q => q.id === question.id);
    if (index > -1) {
        selectedQuestions.value.splice(index, 1);
    } else {
        selectedQuestions.value.push(question);
    }
};

const addSelectedQuestionsToChat = () => {
    if (selectedQuestions.value.length === 0) return;
    
    const sortedQuestions = [...selectedQuestions.value].sort((a, b) => a.number - b.number);
    
    addedQuestions.value = [...addedQuestions.value, ...sortedQuestions];
    selectedQuestions.value = [];
    showQuestionDropdown.value = false;
    loadQuestions();
    
    if (messageInput.value) {
        messageInput.value.focus();
        autoResize();
    }
};

const isQuestionSelected = (question) => {
    return selectedQuestions.value.some(q => q.id === question.id);
};

const areAllQuestionsSelected = computed(() => {
    return availableQuestions.value.length > 0 && 
           selectedQuestions.value.length === availableQuestions.value.length;
});

const toggleSelectAll = () => {
    if (areAllQuestionsSelected.value) {
        selectedQuestions.value = [];
    } else {
        selectedQuestions.value = [...availableQuestions.value];
    }
};

const displayedQuestionBadges = computed(() => {
    if (addedQuestions.value.length === 0) return [];
    if (addedQuestions.value.length <= 2) {
        return addedQuestions.value.sort((a, b) => a.number - b.number);
    }
    const sorted = [...addedQuestions.value].sort((a, b) => a.number - b.number);
    return sorted.slice(0, 2);
});

const remainingQuestionsCount = computed(() => {
    return addedQuestions.value.length > 2 ? addedQuestions.value.length - 2 : 0;
});

const removeAddedQuestion = (questionToRemove) => {
    const index = addedQuestions.value.findIndex(q => q.id === questionToRemove.id);
    if (index > -1) {
        addedQuestions.value.splice(index, 1);
        loadQuestions();
        
        if (messageInput.value) {
            autoResize();
        }
    }
};

const handleFileSelect = (event) => {
    const remainingSlots = Math.max(0, 1 - attachedFiles.value.length);
    if (remainingSlots <= 0) {
        toast.info('Please remove the existing script before attaching a new one.');
        if (fileInputRef.value) {
            fileInputRef.value.value = '';
        }
        return;
    }

    const files = Array.from(event.target.files || []);
    const allowedTypes = ['application/pdf'];
    const allowedExtensions = ['.pdf'];
    
    files.slice(0, remainingSlots).forEach(file => {
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        const isValidType = allowedTypes.includes(file.type) || allowedExtensions.includes(fileExtension);
        
        if (!isValidType) {
            toast.error(`File "${file.name}" is not supported for now. Please upload PDF files only.`);
            return;
        }
        
        if (file.size > 10 * 1024 * 1024) {
            toast.error(`File "${file.name}" is too large. Maximum size is 10MB.`);
            return;
        }
        
        const fileId = Date.now() + Math.random();
        attachedFiles.value.push({
            id: fileId,
            file: file,
            name: file.name,
            size: file.size,
            type: file.type
        });
    });
    
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const removeAttachedFile = (fileToRemove) => {
    if (analyzingScript.value || loading.value) return;
    const index = attachedFiles.value.findIndex(f => f.id === fileToRemove.id);
    if (index > -1) {
        attachedFiles.value.splice(index, 1);
    }

    if (attachedFiles.value.length === 0 && partSuggestionMessageIndex.value !== null) {
        partSuggestionsDisabled.value = true;
    }
};

const openFileDialog = () => {
    if (attachedFiles.value.length >= 1) {
        toast.info('For now only one script can be attached. Remove the existing one to add a new one.');
        return;
    }
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

const displayedFileBadges = computed(() => {
    if (attachedFiles.value.length === 0) return [];
    if (attachedFiles.value.length <= 2) {
        return attachedFiles.value;
    }
    return attachedFiles.value.slice(0, 2);
});

const remainingFilesCount = computed(() => {
    return attachedFiles.value.length > 2 ? attachedFiles.value.length - 2 : 0;
});

const attachmentLimitReached = computed(() => attachedFiles.value.length >= 1);

const aiSuggestionIndexes = computed(() => {
    if (!researchMode.value && attachedFiles.value.length > 0) {
        return [0];
    }
    return [];
});

const dynamicSuggestions = computed(() => {
    return [];
});

const analyzeScript = async () => {
    if (attachedFiles.value.length === 0) {
        toast.error('Please attach a script file first');
        return;
    }

    analyzingScript.value = true;
    try {
        const file = attachedFiles.value[0].file;
        const formData = new FormData();
        formData.append('file', file);

        const response = await axios.post(route('api.chat.analyze-script'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            }
        });

        if (response.data.success && response.data.parts && response.data.parts.length > 0) {
            scriptAnalysisData.value = response.data;
            partSuggestions.value = response.data.parts;
            toast.success(`Found ${response.data.parts.length} EXPRESS part(s)`);
        } else {
            toast.error('No EXPRESS parts found in the script');
            scriptAnalysisData.value = null;
            partSuggestions.value = [];
        }
    } catch (error) {
        console.error('Script analysis failed:', error);
        toast.error('Failed to analyze script. Please try again.');
        scriptAnalysisData.value = null;
        partSuggestions.value = [];
        pushAnalysisErrorMessage('We couldn\'t analyze the uploaded script. Please check the file and try again.');
    } finally {
        analyzingScript.value = false;
    }
};

const handlePartSelection = async (partIdentifier) => {
    if (attachedFiles.value.length === 0) {
        toast.error('Script file not found');
        partSuggestionsDisabled.value = true;
        return;
    }

    // Cancel any existing extraction
    if (partExtractionAbortController.value) {
        partExtractionAbortController.value.abort();
    }

    selectedPart.value = partIdentifier;
    loading.value = true;
    partSuggestionsDisabled.value = false; // Re-enable so user can cancel

    // Create new abort controller for this extraction
    partExtractionAbortController.value = new AbortController();

    try {
        const file = attachedFiles.value[0].file;
        const formData = new FormData();
        formData.append('file', file);
        formData.append('part', partIdentifier);

        const response = await axios.post(route('api.chat.get-question-part-data'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            signal: partExtractionAbortController.value.signal
        });

        if (response.data.success && response.data.questions && response.data.questions.length > 0) {
            const questions = response.data.questions;
            
            let questionnaireCode = survey.value?.code || 
                                  survey.value?.questionnaire_code || 
                                  survey.value?.questionnaire?.code ||
                                  null;
            
            if (!questionnaireCode) {
                const pathSegments = window.location.pathname.split('/');
                const surveyIndex = pathSegments.indexOf('survey');
                if (surveyIndex !== -1 && pathSegments[surveyIndex + 1]) {
                    questionnaireCode = pathSegments[surveyIndex + 1];
                }
            }

            if (!questionnaireCode) {
                const pageProps = usePage().props;
                questionnaireCode = pageProps?.questionnaire?.code || 
                                  pageProps?.questionnaire_code ||
                                  null;
            }

            if (!questionnaireCode) {
                toast.error('Questionnaire context required');
                return;
            }

            const confirmData = {
                action: 'express_survey_create',
                scope: 'survey',
                intent: 'create',
                survey_data: {
                    questions: questions
                },
                questionnaire_code: questionnaireCode
            };

            const confirmResponse = await axios.post(route('api.chat.confirm-action'), confirmData);
            
            const questionId = confirmResponse.data?.question_id;
            let questionNumber = '';
            
            if (questionId && (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/'))) {
                const questions = usePage().props.questions || [];
                const createdQuestion = questions.find(q => q.id === questionId);
                if (createdQuestion) {
                    const variableStart = usePage().props?.questionnaire?.setup?.variable_start || 1;
                    questionNumber = `Q${parseInt(createdQuestion.order) + parseInt(variableStart) - 1}`;
                }
            }
            
            let content = `${confirmResponse.data.message}`;
            if (questionId && (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/'))) {
                const scrollFunction = `window.__scrollToQuestion && window.__scrollToQuestion(${questionId})`;
                content += `<br><span onclick="${scrollFunction}" class="link cursor-pointer hover:underline inline-block" style="color: var(--mcolor); text-decoration: underline;">Go to ${questionNumber || 'question'}</span>`;
            } else if (confirmResponse.data?.route) {
                if (questionId) {
                    router.visit(confirmResponse.data.route);
                    content += `<br><span class="text-xs text-gray-500">Navigating to question...</span>`;
                } else if (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/')) {
                    // On questions page but no questionId - try to scroll to last question
                    const questions = usePage().props.questions || [];
                    if (questions.length > 0) {
                        const lastQuestion = questions[questions.length - 1];
                        const variableStart = usePage().props?.questionnaire?.setup?.variable_start || 1;
                        const lastQuestionNumber = `Q${parseInt(lastQuestion.order) + parseInt(variableStart) - 1}`;
                        const scrollFunction = `window.__scrollToQuestion && window.__scrollToQuestion(${lastQuestion.id})`;
                        content += `<br><span onclick="${scrollFunction}" class="link cursor-pointer hover:underline inline-block" style="color: var(--mcolor); text-decoration: underline;">Go to ${lastQuestionNumber}</span>`;
                    } else {
                        content += `<br><span class="text-xs text-gray-500">Scrolling to created questions...</span>`;
                    }
                } else {
                    // Not on questions page - navigate first, then scroll will happen after reload
                    content += `<br><span class="text-xs text-gray-500">Navigating to questions...</span>`;
                }
            }
            
            messages.value.push(stampMessage({
                role: 'assistant',
                content: content,
                type: 'normal'
            }));
            
            toast.success('Questions created successfully');
            
            if (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/')) {
                router.reload({ 
                    only: ['questions'],
                    preserveScroll: true,
                    onSuccess: () => {
                        const questions = usePage().props.questions || [];
                        if (questionId && questionnaireCode) {
                            const createdQuestion = questions.find(q => q.id === questionId);
                            if (createdQuestion) {
                                showTranslateToast(questionnaireCode, createdQuestion.id, createdQuestion.order, 'added');
                            }
                        } else if (questions.length > 0 && questionnaireCode) {
                            const latestQuestion = questions[questions.length - 1];
                            showTranslateToast(questionnaireCode, latestQuestion.id, latestQuestion.order, 'added');
                        }
                    }
                });
            } else if (confirmResponse.data?.route && questionId) {
                router.visit(confirmResponse.data.route);
            }

            partSuggestions.value = [];
            scriptAnalysisData.value = null;
        } else {
            toast.error('No questions found in the selected part');
            pushPartExtractionErrorMessage(partIdentifier, `I couldn't find any questions in ${partIdentifier}. Please try another part or retry.`);
        }
    } catch (error) {
        // Don't show error if it was cancelled
        if (axios.isCancel(error) || error.code === 'ERR_CANCELED' || error.name === 'CanceledError' || error.name === 'AbortError') {
            toast.info('Part extraction cancelled');
            return;
        }
        console.error('Part question extraction failed:', error);
        toast.error('Failed to extract questions from part. Please try again.');
        pushPartExtractionErrorMessage(partIdentifier, `Something went wrong while processing ${partIdentifier}. Please try again.`);
    } finally {
        loading.value = false;
        selectedPart.value = null;
        partExtractionAbortController.value = null;
    }
};

const cancelPartExtraction = () => {
    if (partExtractionAbortController.value) {
        partExtractionAbortController.value.abort();
        partExtractionAbortController.value = null;
    }
    loading.value = false;
    selectedPart.value = null;
    partSuggestionsDisabled.value = false; // Re-enable part selection
};

const retryAnalyzeScript = async (messageIndex = null) => {
    if (attachedFiles.value.length === 0) {
        return;
    }
    if (typeof messageIndex === 'number' && messages.value[messageIndex]) {
        messages.value.splice(messageIndex, 1);
    }
    if (!analyzingScript.value) {
        await analyzeScript();
    }
};

const retryPartExtraction = async (partIdentifier, messageIndex = null) => {
    if (!partIdentifier) return;
    if (typeof messageIndex === 'number' && messages.value[messageIndex]) {
        messages.value.splice(messageIndex, 1);
    }
    await handlePartSelection(partIdentifier);
};

const retryFailedRequest = async (messageIndex, retryPrompt) => {
    if (!retryPrompt || loading.value) return;
    if (typeof messageIndex === 'number' && messages.value[messageIndex]) {
        messages.value.splice(messageIndex, 1);
    }
    await sendMessage({ retryPrompt });
};

const recordSuggestionInteraction = (suggestionText) => {
    if (!suggestionText) return;
    
    let content = suggestionText;
    
    // Replace the generic message with file name when script is attached
    if (suggestionText === 'Create a survey based from the uploaded script.' && attachedFiles.value.length > 0) {
        const fileName = attachedFiles.value[0].file.name;
        content = `Create a survey based from <strong>${fileName}</strong>.`;
    }
    
    messages.value.push(stampMessage({
        role: 'user',
        content: content,
        type: 'normal'
    }));
};

const handleSuggestionSelect = async (suggestion) => {
    const suggestionText = typeof suggestion === 'string' ? suggestion : normalizePartLabel(suggestion);
    if (!suggestionText) return;
    
    if (suggestionText === 'Create a survey based from the uploaded script.') {
        recordSuggestionInteraction(suggestionText);
        await analyzeScript();
        return;
    }
    
    if (suggestionText.startsWith('Part ')) {
        recordSuggestionInteraction(suggestionText);
        await handlePartSelection(suggestionText);
        return;
    }
    
    if (newMessage.value.trim()) {
        newMessage.value = `${newMessage.value.trim()}\n\n${suggestionText}`;
    } else {
        newMessage.value = suggestionText;
    }
    nextTick(() => {
        if (messageInput.value) {
            messageInput.value.focus();
            autoResize();
        }
    });
};

const handleQuestionSelection = async (question) => {
    if (!question || !question.id) {
        toast.error('Invalid question selected');
        return;
    }

    selectedQuestionForDuplicate.value = question;
    questionSuggestionsDisabled.value = true;
    loading.value = true;

    try {
        // Get questionnaire code
        let questionnaireCode = survey.value?.code || 
                              survey.value?.questionnaire_code || 
                              survey.value?.questionnaire?.code ||
                              null;
        
        if (!questionnaireCode) {
            const pathSegments = window.location.pathname.split('/');
            const surveyIndex = pathSegments.indexOf('survey');
            if (surveyIndex !== -1 && pathSegments[surveyIndex + 1]) {
                questionnaireCode = pathSegments[surveyIndex + 1];
            }
        }
        
        if (!questionnaireCode) {
            const pageProps = usePage().props;
            questionnaireCode = pageProps?.questionnaire?.code || 
                              pageProps?.questionnaire_code ||
                              null;
        }

        if (!questionnaireCode) {
            toast.error('Questionnaire context required');
            return;
        }

        // Execute duplication
        await executeQuestionDuplication(question.id, questionnaireCode);
        
        // Remove question suggestion message after successful duplication
        removeQuestionSuggestionMessage();
        questionSuggestions.value = [];
        selectedQuestionForDuplicate.value = null;
    } catch (error) {
        console.error('Failed to handle question selection:', error);
        toast.error('Failed to duplicate question. Please try again.');
    } finally {
        loading.value = false;
        questionSuggestionsDisabled.value = false;
    }
};

const executeQuestionDuplication = async (questionId, questionnaireCode) => {
    try {
        const response = await axios.post(route('api.chat.duplicate-question'), {
            questionnaire_code: questionnaireCode,
            question_id: questionId
        });

        if (response.data.success) {
            const questionId = response.data?.question_id;
            let questionNumber = '';
            
            if (questionId && (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/'))) {
                const questions = usePage().props.questions || [];
                const duplicatedQuestion = questions.find(q => q.id === questionId);
                if (duplicatedQuestion) {
                    const variableStart = usePage().props?.questionnaire?.setup?.variable_start || 1;
                    questionNumber = `Q${parseInt(duplicatedQuestion.order) + parseInt(variableStart) - 1}`;
                }
            }
            
            let content = `✅ ${response.data.message}`;
            if (questionId && (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/'))) {
                const scrollFunction = `window.__scrollToQuestion && window.__scrollToQuestion(${questionId})`;
                content += `<br><span onclick="${scrollFunction}" class="link cursor-pointer hover:underline inline-block" style="color: var(--mcolor); text-decoration: underline;">Go to ${questionNumber || 'question'}</span>`;
            } else if (response.data?.route) {
                if (questionId) {
                    router.visit(response.data.route);
                    content += `<br><span class="text-xs text-gray-500">Navigating to question...</span>`;
                } else if (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/')) {
                    // On questions page but no questionId - try to scroll to last question
                    const questions = usePage().props.questions || [];
                    if (questions.length > 0) {
                        const lastQuestion = questions[questions.length - 1];
                        const variableStart = usePage().props?.questionnaire?.setup?.variable_start || 1;
                        const lastQuestionNumber = `Q${parseInt(lastQuestion.order) + parseInt(variableStart) - 1}`;
                        const scrollFunction = `window.__scrollToQuestion && window.__scrollToQuestion(${lastQuestion.id})`;
                        content += `<br><span onclick="${scrollFunction}" class="link cursor-pointer hover:underline inline-block" style="color: var(--mcolor); text-decoration: underline;">Go to ${lastQuestionNumber}</span>`;
                    } else {
                        content += `<br><span class="text-xs text-gray-500">Scrolling to created questions...</span>`;
                    }
                } else {
                    // Not on questions page - navigate first, then scroll will happen after reload
                    content += `<br><span class="text-xs text-gray-500">Navigating to questions...</span>`;
                }
            }
            
            messages.value.push(stampMessage({
                role: 'assistant',
                content: content,
                type: 'normal'
            }));
            
            toast.success('Question duplicated successfully');
            
            // Reload questions if on questions page
            if (window.location.pathname.includes('/questions') || window.location.pathname.includes('/survey/')) {
                router.reload({ 
                    only: ['questions'],
                    preserveScroll: true,
                    onSuccess: () => {
                        if (questionId && questionnaireCode) {
                            const questions = usePage().props.questions || [];
                            const duplicatedQuestion = questions.find(q => q.id === questionId);
                            if (duplicatedQuestion) {
                                showTranslateToast(questionnaireCode, duplicatedQuestion.id, duplicatedQuestion.order, 'added');
                            }
                        }
                    }
                });
            }
        } else {
            throw new Error(response.data.message || 'Failed to duplicate question');
        }
    } catch (error) {
        console.error('Error duplicating question:', error);
        const errorMessage = error.response?.data?.message || error.message || 'Failed to duplicate question';
        toast.error(errorMessage);
        throw error;
    }
};

const handleQuestionSuggestionSelect = async (question) => {
    await handleQuestionSelection(question);
};

/** When user clicks a number (2,3,4,5) on the plural-question-count bubble, auto-send that count. */
const onPluralQuestionCountSelect = (num) => {
    if (pendingPluralQuestionMessage.value == null || loading.value) return;
    newMessage.value = String(num);
    sendMessage();
};

const closeModal = () => {
    emit('update:open', false);
};

const truncateText = (text, maxLength) => {
    if (!text || text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
};

const stripHtml = (html) => {
    if (!html) return '';
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    let text = tmp.textContent || tmp.innerText || '';
    return text;
};
</script>

<template>
    <div class="fixed bottom-5 right-5 z-[1000]">
        <!-- Chat button -->
        <button 
            @click="toggle" 
            class="flex items-center justify-center w-10 h-10 rounded-full shadow-lg"
            :style="open ? 'background-color: #323232;' : 'background-color: var(--mcolor);'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" 
                width="25" height="25" 
                viewBox="0 0 24 24" 
                class="text-white">
                <path fill="currentColor" d="m20.713 8.128l-.246.566a.506.506 0 0 1-.934 0l-.246-.566a4.36 4.36 0 0 0-2.22-2.25l-.759-.339a.53.53 0 0 1 0-.963l.717-.319a4.37 4.37 0 0 0 2.251-2.326l.253-.611a.506.506 0 0 1 .942 0l.253.61a4.37 4.37 0 0 0 2.25 2.327l.718.32a.53.53 0 0 1 0 .962l-.76.338a4.36 4.36 0 0 0-2.219 2.251M10 3h4v2h-4a6 6 0 0 0-6 6c0 3.61 2.462 5.966 8 8.48V17h2a6 6 0 0 0 6-6h2a8 8 0 0 1-8 8v3.5c-5-2-12-5-12-11.5a8 8 0 0 1 8-8" />
            </svg>
        </button>

		<div v-show="open" class="fixed bottom-20 right-4 z-[1000] max-w-[90vw] rounded-xl border border-base-300 bg-white shadow-xl overflow-hidden transition-all duration-200" :style="{ width: modalMaxWidth }">
			<div class="flex items-center gap-3 border-b border-base-300 pb-3 pt-4 px-4">
				<button 
					v-if="!isOnboardingState"
					type="button" 
					@click.stop="toggleSidebar" 
					class="flex items-center justify-center w-8 h-8 rounded hover:bg-base-200 transition-colors text-base-content/70 hover:text-base-content"
					:title="showSidebar ? 'Hide Chat List' : 'Show Chat List'"
				>
					<svg v-if="showSidebar" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48" class="flex-shrink-0">
						<g fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="4">
							<path d="M6 9a3 3 0 0 1 3-3h30a3 3 0 0 1 3 3v30a3 3 0 0 1-3 3H9a3 3 0 0 1-3-3z" />
							<path stroke-linecap="round" d="M32 6v36M16 20l4 4l-4 4M26 6h12M26 42h12" />
						</g>
					</svg>
					<svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48" class="flex-shrink-0" style="transform: scaleX(-1);">
						<g fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="4">
							<path d="M6 9a3 3 0 0 1 3-3h30a3 3 0 0 1 3 3v30a3 3 0 0 1-3 3H9a3 3 0 0 1-3-3z" />
							<path stroke-linecap="round" d="M32 6v36M16 20l4 4l-4 4M26 6h12M26 42h12" />
						</g>
					</svg>
				</button>
				<div class="flex-1 flex items-center justify-center gap-3" :style="isOnboardingState ? 'margin-left: 0;' : 'margin-left: 0.5rem;'">
					<div class="avatar">
						<div class="w-8 h-8 rounded-full flex items-center justify-center">
							<svg xmlns="http://www.w3.org/2000/svg" width="28px" height="28px" viewBox="0 0 24 24" fill="none" stroke="var(--mcolor)" stroke-linejoin="round" stroke-width="2"><path d="M15 19c1.2-3.678 2.526-5.005 6-6c-3.474-.995-4.8-2.322-6-6c-1.2 3.678-2.526 5.005-6 6c3.474.995 4.8 2.322 6 6Zm-8-9c.6-1.84 1.263-2.503 3-3c-1.737-.497-2.4-1.16-3-3c-.6 1.84-1.263 2.503-3 3c1.737.497 2.4 1.16 3 3Zm1.5 10c.3-.92.631-1.251 1.5-1.5c-.869-.249-1.2-.58-1.5-1.5c-.3.92-.631 1.251-1.5 1.5c.869.249 1.2.58 1.5 1.5Z" /></svg>
						</div>
					</div>
					<h3 class="text-md font-medium" style="color: var(--mcolor);">EXPRESS AI</h3>
				</div>
				<div class="flex items-center gap-1">
					<!-- Kebab menu button - hidden in onboarding -->
					<div v-if="!isOnboardingState" class="relative" ref="kebabMenuRef">
						<button 
							type="button" 
							@click.stop="toggleKebabMenu" 
							class="flex items-center justify-center w-8 h-8 rounded hover:bg-base-200 transition-colors text-base-content/70 hover:text-base-content"
							title="More options"
						>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
                            <path fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="5" d="M12.01 12v.01H12V12zm7 0v.01H19V12zm-14 0v.01H5V12z" />
                        </svg>
						</button>
						<!-- Kebab menu dropdown -->
						<transition
							enter-active-class="transition ease-out duration-100"
							enter-from-class="transform opacity-0 scale-95"
							enter-to-class="transform opacity-100 scale-100"
							leave-active-class="transition ease-in duration-75"
							leave-from-class="transform opacity-100 scale-100"
							leave-to-class="transform opacity-0 scale-95"
						>
							<div 
								v-if="showKebabMenu"
								class="absolute right-0 top-full mt-1 w-48 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 z-50"
								@click.stop
							>
							<div class="py-1" role="menu">
								<button
									@click="handleContactSupport()"
									class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 flex items-center gap-2"
									role="menuitem"
								>
									<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 32 32" class="flex-shrink-0">
										<path fill="currentColor" d="M9 11a7 7 0 1 1 14 0a1 1 0 1 0 2 0a9 9 0 1 0-14.385 7.212a9 9 0 0 0 3.558 1.602a2 2 0 1 0 .216-2A7 7 0 0 1 9 11m1 0a6 6 0 1 1 7.913 5.689A3 3 0 0 0 16 16c-.727 0-1.393.259-1.913.689A6 6 0 0 1 10 11m6 11a3 3 0 0 0 2.83-4h5.67a3.5 3.5 0 0 1 3.5 3.5v.5c0 2.393-1.523 4.417-3.685 5.793C22.141 29.177 19.198 30 16 30s-6.14-.823-8.315-2.207C5.523 26.417 4 24.393 4 22v-.5A3.5 3.5 0 0 1 7.5 18h1.359a10 10 0 0 0 4.662 2.69c.54.791 1.45 1.31 2.479 1.31" />
									</svg>
									Contact Support
								</button>
								<button
									@click="requestClearConversation(); closeKebabMenu()"
									class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 flex items-center gap-2"
									role="menuitem"
								>
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
									</svg>
									Clear conversation
								</button>
								<!-- TODO: temporary debug - remove this later -->
								<button
                                v-if="showDeleteAllQuestionDebug"
									@click="deleteAllQuestionDebug()"
									class="w-full text-left px-4 py-2 text-xs text-red-700 hover:bg-red-300 flex items-center gap-2"
									role="menuitem"
								>
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
									</svg>
									Delete All Question (Debug)
								</button>
							</div>
							</div>
						</transition>
					</div>
					<!-- Close/Minimize button - always visible -->
					<button 
						type="button" 
						@click="closeModal" 
						class="flex items-center justify-center w-8 h-8 rounded hover:bg-base-200 transition-colors text-base-content/70 hover:text-base-content"
						:title="isOnboardingState ? 'Minimize' : 'Close'"
					>
						<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
							<path d="M5 12h14"/>
						</svg>
					</button>
				</div>
			</div>

			<!-- Two-column: sidebar (ChatList) + main chat -->
			<div class="flex h-[65vh] relative overflow-hidden">
				<!-- Sidebar: expands to left, increasing modal width - hidden in onboarding -->
				<div 
					v-if="!isOnboardingState"
					class="flex-shrink-0 overflow-hidden bg-white border-r border-base-300 transition-all duration-200 ease-in-out"
					:style="{ 
						width: showSidebar ? SIDEBAR_WIDTH : '0',
						minWidth: showSidebar ? SIDEBAR_WIDTH : '0'
					}"
				>
					<transition 
						enter-active-class="transition-opacity duration-200 ease-out"
						enter-from-class="opacity-0"
						enter-to-class="opacity-100"
						leave-active-class="transition-opacity duration-200 ease-in"
						leave-from-class="opacity-100"
						leave-to-class="opacity-0"
					>
						<div v-if="showSidebar" class="w-full h-full" style="width: 12rem;">
							<ChatList
								ref="chatListRef"
								:current-conversation-id="conversationId"
								:is-open="open"
								:is-processing="isConversationProcessing"
								@conversation-selected="handleConversationSelected"
								@new-conversation="handleNewConversation"
								@all-conversations-cleared="handleAllConversationsCleared"
								@request-delete-with-rating="handleRequestDeleteWithRating"
							/>
						</div>
					</transition>
				</div>
				<!-- Main chat: full width in onboarding, fixed width otherwise -->
				<div class="flex flex-col flex-shrink-0 min-w-0" :style="isOnboardingState ? { width: '100%', maxWidth: '100%', padding: '0 1rem 0 0' } : { width: MAIN_CONTENT_WIDTH, maxWidth: MAIN_CONTENT_WIDTH, padding: '0 0 0 0' }">
			<div class="flex flex-col h-full flex-1 min-h-0">
				<!-- Chat messages -->
				<div ref="chatContainer" class="flex-1 overflow-y-auto p-6 mt-2 space-y-6">
					<!-- Welcome content: show when no user messages yet (new conversation or first load) -->
					<div v-if="showOnboardingContent" class="flex flex-col items-center justify-center h-full -mt-12">
						<!-- AI Character Image -->
						<div class="mb-6 flex-shrink-0">
							<img 
								:src="aiImg"
								alt="EXPRESS AI" 
								class="w-60 h-60 object-contain"
								@error="handleImageError"
							/>
							<div v-if="imageError" class="w-48 h-48 bg-base-200 rounded-full flex items-center justify-center">
								<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24">
                                    <path fill="none" stroke="#ee920c" stroke-linejoin="round" stroke-width="2" d="M15 19c1.2-3.678 2.526-5.005 6-6c-3.474-.995-4.8-2.322-6-6c-1.2 3.678-2.526 5.005-6 6c3.474.995 4.8 2.322 6 6Zm-8-9c.6-1.84 1.263-2.503 3-3c-1.737-.497-2.4-1.16-3-3c-.6 1.84-1.263 2.503-3 3c1.737.497 2.4 1.16 3 3Zm1.5 10c.3-.92.631-1.251 1.5-1.5c-.869-.249-1.2-.58-1.5-1.5c-.3.92-.631 1.251-1.5 1.5c.869.249 1.2.58 1.5 1.5Z" />
                                </svg>
							</div>
						</div>
						
						<!-- Welcome Message -->
						<div class="text-center max-w-md px-4">
							<h2 class="text-xl font-semibold text-base-content mb-2">
								{{ currentGreeting }}, <span class="text-[var(--mcolor)]">{{ userName || 'there' }}</span>
							</h2>
							<p class="text-base text-base-content/70">
								What can I help you with today?
							</p>
						</div>
					</div>
					
					<!-- Regular Messages -->
					<template v-else-if="!showOnboardingContent" v-for="(message, index) in messages" :key="index">
					<!-- Questionnaire Indicator -->
					<div 
						v-if="message.type === 'questionnaire_indicator'"
						class="relative flex items-center justify-center my-4 w-full"
					>
						<div class="absolute left-0 right-0 top-1/2 h-0 border-t border-solid border-[var(--mcolor)] opacity-25"></div>
						<div class="relative px-2 py-1 bg-white text-xs text-[var(--mcolor)]">
							{{ message.content }} Questionnaire
						</div>
					</div>
						<!-- Regular Messages -->
						<div 
							v-else-if="message.type !== 'questionnaire_indicator'"
							class="bubble-speech" 
							:class="message.role === 'user' ? 'bubble-right' : 'bubble-left'"
						>
                        <div v-if="message.type === 'normal' && message.content.trim() !== ''" class="chat-bubble" :class="message.role === 'user' ? 'bubble-right user-bubble' : 'bubble-left'">
							<div class="prose prose-sm text-xs text-primary-content [&_*]:!text-xs [&_*]:!text-primary-content" v-html="message.content.replace(/\n/g, '<br>')"></div>
                            <div
                                v-if="message.role === 'assistant' && (!isGreetingMessage(message) || getMessageTimestamp(message)) && !message.isStreaming"
                                class="mt-2 flex items-center justify-between gap-4"
                            >
                                <div class="flex items-center gap-2 flex-wrap">
                                    <div
                                        v-if="getMessageTimestamp(message)"
                                        class="text-[10px] text-gray-400 text-left"
                                    >
                                        {{ formatMessageTimestamp(getMessageTimestamp(message)) }}
                                    </div>
                                    <button
                                        v-if="message.retryPrompt"
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--mcolor)] px-2.5 py-1.5 text-xs font-semibold text-[var(--mcolor)] hover:bg-[var(--mcolor)] hover:text-white transition disabled:opacity-50 disabled:cursor-not-allowed"
                                        :disabled="loading"
                                        @click="retryFailedRequest(index, message.retryPrompt)"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                                            <path d="M3 3v5h5"/>
                                        </svg>
                                        Retry
                                    </button>
                                </div>
                                <div v-if="!isGreetingMessage(message)" class="like-dislike-buttons flex-shrink-0">								
                                    <button 
                                        @click="toggleLike(index)" 
                                        class="dislike-btn"
                                        :class="{ 'active': message.liked, 'disabled': message.feedbackSubmitted || message.feedbackDisabled }"
                                        :disabled="message.feedbackSubmitted || message.feedbackDisabled"
                                        title="Good Response"
                                    >
                                        <svg v-if="message.liked" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                            <path fill="#ee920c" fill-rule="evenodd" d="M11.277 4.781A4 4 0 0 1 14.606 3h.213a2 2 0 0 1 1.973 2.329L16.18 9h2.38a3 3 0 0 1 2.942 3.588l-1.2 6A3 3 0 0 1 17.36 21H6a3 3 0 0 1-3-3v-8a1 1 0 0 1 1-1h3.93a1 1 0 0 0 .832-.445zM7 11H5v7a1 1 0 0 0 1 1h1z" clip-rule="evenodd" />
                                        </svg>
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
                                        <svg v-if="message.disliked" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                            <path fill="#ee920c" fill-rule="evenodd" d="M11.277 19.219A4 4 0 0 0 14.606 21h.213a2 2 0 0 0 1.973-2.329L16.18 15h2.38a3 3 0 0 0 2.942-3.588l-1.2-6A3 3 0 0 0 17.36 3H6a3 3 0 0 0-3 3v8a1 1 0 0 0 1 1h3.93a1 1 0 0 1 .832.445zM7 5v8H5V6a1 1 0 0 1 1-1z" clip-rule="evenodd" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                            <g fill="none">
                                                <path fill="#9ca3af" d="m15 14l-.986.164A1 1 0 0 1 15 13zM4 14v1a1 1 0 0 1-1-1zm16.522-2.392l.98-.196zM6 3h11.36v2H6zm12.56 12H15v-2h3.56zm-2.573-1.164l.805 4.835L14.82 19l-.806-4.836zM14.82 21h-.214v-2h.214zm-3.543-1.781l-2.515-3.774l1.664-1.11l2.516 3.774zM7.93 15H4v-2h3.93zM3 14V6h2v8zm17.302-8.588l1.2 6l-1.96.392l-1.2-6zM8.762 15.445A1 1 0 0 0 7.93 15v-2a3 3 0 0 1 2.496 1.336zm8.03 3.226A2 2 0 0 1 14.82 21v-2zM18.56 13a1 1 0 0 0 .981-1.196l1.961-.392A3 3 0 0 1 18.561 15zm-1.2-10a3 3 0 0 1 2.942 2.412l-1.96-.392A1 1 0 0 0 17.36 5zm-2.754 18a4 4 0 0 1-3.328-1.781l1.664-1.11a2 2 0 0 0 1.664.891zM6 5a1 1 0 0 0-1 1H3a3 3 0 0 1 3-3z" />
                                                <path stroke="#9ca3af" stroke-width="2" d="M8 14V4" />
                                            </g>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <!-- <div
                                v-else-if="getMessageTimestamp(message)"
                                class="mt-2 text-[10px] text-gray-400 text-left"
                            >
                                {{ formatMessageTimestamp(getMessageTimestamp(message)) }}
                            </div> -->
						</div>
                        <div
                            v-else-if="message.type === 'part-suggestion' && Array.isArray(message.parts) && message.parts.length"
                        >
                            <PartSuggestionBubble
                                :content="message.content"
                                :parts="message.parts"
                                :timestamp="formatMessageTimestamp(getMessageTimestamp(message))"
                                :selected-part="selectedPart"
                                :disabled="partSuggestionsDisabled"
                                :loading="loading"
                                @select="handleSuggestionSelect"
                                @cancel="cancelPartExtraction"
                            />
                        </div>
                        <div
                            v-else-if="message.type === 'question-suggestion' && Array.isArray(message.questions) && message.questions.length"
                        >
                            <QuestionSuggestionBubble
                                :content="message.content"
                                :questions="message.questions"
                                :timestamp="formatMessageTimestamp(getMessageTimestamp(message))"
                                :selected-question="selectedQuestionForDuplicate"
                                :disabled="questionSuggestionsDisabled"
                                :loading="loading"
                                @select="handleQuestionSuggestionSelect"
                            />
                        </div>
                        <div
                            v-else-if="message.type === 'plural-question-count'"
                        >
                            <PluralQuestionCountBubble
                                :content="message.content"
                                :options="message.options || [2, 3, 4, 5]"
                                :timestamp="formatMessageTimestamp(getMessageTimestamp(message))"
                                :disabled="loading"
                                :loading="loading"
                                @select="onPluralQuestionCountSelect"
                            />
                        </div>
                        <div
                            v-else-if="message.type === 'part-error'"
                            class="chat-bubble bubble-left bg-white border border-red-200 shadow-sm flex flex-col gap-3"
                        >
                            <p class="text-sm text-gray-800">{{ message.content }}</p>
                            <button
                                type="button"
                                class="mt-3 inline-flex items-center justify-center rounded-lg border border-[var(--mcolor)] px-3 py-2 text-xs font-semibold text-[var(--mcolor)] hover:bg-[var(--mcolor)] hover:text-white transition disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="inputsDisabled || !message.partIdentifier"
                                @click="retryPartExtraction(message.partIdentifier, index)"
                            >
                                Retry Part Extraction
                            </button>
                            <div
                                v-if="getMessageTimestamp(message)"
                                class="text-[10px] text-gray-400 text-left"
                            >
                                {{ formatMessageTimestamp(getMessageTimestamp(message)) }}
                            </div>
                        </div>
                        <div
                            v-else-if="message.type === 'analysis-error'"
                            class="chat-bubble bubble-left bg-white border border-red-200 shadow-sm flex flex-col gap-3"
                        >
                            <p class="text-sm text-gray-800">{{ message.content }}</p>
                            <button
                                v-if="attachedFiles.length > 0"
                                type="button"
                                class="mt-3 inline-flex items-center justify-center rounded-lg border border-[var(--mcolor)] px-3 py-2 text-xs font-semibold text-[var(--mcolor)] hover:bg-[var(--mcolor)] hover:text-white transition disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="analyzingScript"
                                @click="retryAnalyzeScript(index)"
                            >
                                Retry Analysis
                            </button>
                            <div
                                v-if="getMessageTimestamp(message)"
                                class="text-[10px] text-gray-400 text-left"
                            >
                                {{ formatMessageTimestamp(getMessageTimestamp(message)) }}
                            </div>
                        </div>
					</div>
					</template>
					
					<!-- Customer Support Request Card (aligned with chat messages) -->
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
							<div v-if="customerSupportData" class="mt-4 flex justify-start">
								<div class="w-[24rem]">
									<CustomerSupport
										:conversation-id="conversationId"
										:platform="platform"
										@submit-success="handleSupportSubmitSuccess"
										@keep-chatting="handleSupportKeepChatting"
										@initial-message="handleCustomerSupportInitialMessage"
									/>
								</div>
							</div>
						</transition>
					</template>
					<!-- Customer Support Request Card without transition (when Keep Chatting is clicked) -->
					<div v-else-if="customerSupportData" class="px-2 mt-4 flex justify-start">
						<div class="w-[24rem]">
							<CustomerSupport
								:conversation-id="conversationId"
								:platform="platform"
								@submit-success="handleSupportSubmitSuccess"
								@keep-chatting="handleSupportKeepChatting"
								@initial-message="handleCustomerSupportInitialMessage"
							/>
						</div>
					</div>
					
					<!-- Action confirmation bubble -->
                    <ConfirmationBubble
                        v-if="actionData"
                        :action-data="actionData"
                        :strip-html="stripHtml"
                        :truncate-text="truncateText"
                        :loading="loading"
                        @confirm="(filteredData) => { if (filteredData) actionData = filteredData; executeAction(filteredData); }"
                        @cancel="cancelAction"
                    />

					<FeedbackDialog
                        :show="showFeedbackDialog"
                        :feedback-reasons="feedbackReasons"
                        :feedback-text="feedbackText"
                        :is-feedback-valid="isFeedbackValid"
                        @toggle-reason="toggleFeedbackReason"
                        @cancel="cancelFeedback"
                        @submit="submitFeedback"
                        @update:feedbackText="(val) => feedbackText = val"
                    />
					
                    <!-- Preparing Question/Survey Dialog (when intent is create) -->
                    <!-- Show when:
                         1. isCreatingQuestions (after user confirmed create - request in flight), OR
                         2. isPreparingQuestion is true and actionData hasn't been set yet, OR
                         When actionData.loading: ConfirmationBubble handles it (single dialog) -->
                    <PreparingDialog 
                        v-if="isCreatingQuestions || (isPreparingQuestion && !actionData)" 
                        :is-survey="!isCreatingQuestions && isPreparingSurvey" 
                        :is-creating="isCreatingQuestions"
                        :creating-count="creatingQuestionsCount"
                        :progress="surveyProgress"
                        @cancel="cancelPreparing"
                    />
                    
                    <!-- Regular Typing Indicator (when not preparing question and not showing action confirmation) -->
                    <div v-else-if="!isPreparingQuestion && !actionData && (loading || messages.some(msg => msg.isStreaming && msg.content.trim() === ''))" class="bubble-speech bubble-left">
                        <div class="chat-bubble typing-bubble">
                            <span class="dot"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                        </div>
                    </div>
				</div>
				
				<!-- Thank You Message-->
				<ThankYouMessage v-if="showFeedbackThankYou" />
				
				<!-- Chat input -->
                <div class="p-2 mx-0">
					<AISuggestive
						v-if="(aiSuggestionIndexes.length || dynamicSuggestions.length || analyzingScript) && partSuggestionMessageIndex === null"
						:suggestion-indexes="aiSuggestionIndexes"
						:dynamic-suggestions="dynamicSuggestions"
						:is-analyzing="analyzingScript"
						@select="handleSuggestionSelect"
						class="mb-3"
					/>
					<div class="border border-base-300 rounded-2xl bg-base-100">
						<form @submit.prevent="sendMessage" class="relative">
							<textarea 
								ref="messageInput"
								v-model="newMessage"
								@keydown="handleKeydown"
								rows="1"
								placeholder="Ask or command EXPRESS AI to do task for you..."
								class="w-full text-xs resize-none px-4 pt-3 pb-2 bg-transparent border-0 focus:outline-none focus:ring-0 scrollbar-hide overflow-y-auto"
								:class="{ 'opacity-50 cursor-not-allowed': inputsDisabled }"
								:disabled="inputsDisabled"
								style="max-height: 150px; min-height: 2.5rem;"
							></textarea>
							
							<div class="flex items-center gap-2 px-3 pb-2 border-base-300/50">
								<Tooltip 
									:label="`Token Usage: ${tokenUsed.toLocaleString()}/${tokenLimit.toLocaleString()}`"
									hoverWidth="auto"
									xTransformClass="translate(-50%,-115%)"
								>
									<template #svg>
										<div class="relative cursor-pointer token-indicator">
											<div class="absolute inset-0 rounded-full opacity-20 animate-pulse"
												 :class="{
													 'bg-success': tokenPercent < 50,
													 'bg-warning': tokenPercent >= 50 && tokenPercent < 80,
													 'bg-error': tokenPercent >= 80
												 }">
											</div>
											
											<div class="relative w-8 h-8 rounded-full border-2 border-[var(--mcolor)] overflow-hidden bg-white shadow-sm">
													<div class="absolute inset-0 water-container"
														 :style="`--fill-percentage: ${tokenPercent > 5 ? tokenPercent : 0};`">
														<svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
															<defs>
																<linearGradient id="waterGradient" x1="0%" y1="0%" x2="0%" y2="100%">
																	<stop offset="0%" :style="`stop-color: var(--water-color-light); stop-opacity: 0.8`" />
																	<stop offset="100%" :style="`stop-color: var(--water-color-dark); stop-opacity: 1`" />
																</linearGradient>
															</defs>													
															<rect x="0" y="0" width="100" height="100" fill="white" opacity="0" />												
															<rect x="0" :y="`${100 - tokenPercent}`" width="100" :height="`${tokenPercent}`" :fill="`url(#waterGradient)`" />													
															<path class="wave wave-1" v-if="tokenPercent > 0" :d="`M 0,${100 - tokenPercent} Q 12.5,${97 - tokenPercent} 25,${100 - tokenPercent} T 50,${100 - tokenPercent} T 75,${100 - tokenPercent} T 100,${100 - tokenPercent} L 100,${100} L 0,${100} Z`" :fill="`url(#waterGradient)`" opacity="0.9" />												
															<path class="wave wave-2" v-if="tokenPercent > 0" :d="`M 0,${100 - tokenPercent + 2} Q 12.5,${97 - tokenPercent} 25,${100 - tokenPercent + 2} T 50,${100 - tokenPercent + 2} T 75,${100 - tokenPercent + 2} T 100,${100 - tokenPercent + 2} L 100,${100} L 0,${100} Z`" :fill="`url(#waterGradient)`" opacity="0.6" />													
															<path class="wave wave-3" v-if="tokenPercent > 0" :d="`M 0,${100 - tokenPercent + 4} Q 12.5,${97 - tokenPercent} 25,${100 - tokenPercent + 4} T 50,${100 - tokenPercent + 4} T 75,${100 - tokenPercent + 4} T 100,${100 - tokenPercent + 4} L 100,${100} L 0,${100} Z`" :fill="`url(#waterGradient)`" opacity="0.3" />
														</svg>
														
														<div class="absolute inset-0 wave-shimmer"></div>
													</div>
													
													<div class="absolute inset-0 flex flex-col items-center justify-center z-10">
														<span class="text-[10px] font-medium text-base-content drop-shadow">{{ parseInt(tokenPercent) }}%</span>
													</div>
												</div>
												
											<div v-if="tokenPercent >= 80" class="absolute inset-0 pointer-events-none">
												<div class="particle particle-1"></div>
												<div class="particle particle-2"></div>
												<div class="particle particle-3"></div>
											</div>
										</div>
									</template>
								</Tooltip>
								
								<div class="flex items-center gap-2">
									<Tooltip 
										label="Enables advanced research insights and survey expertise. Provides guidance only and cannot perform actions on the platform."
										hoverWidth="20vmin"
										xTransformClass="translate(-50%,-115%)"
									>
										<template #svg>
											<button 
												@click="toggleResearchMode"
												class="flex items-center gap-1 px-2 py-2 rounded-full transition-all duration-200 text-xs whitespace-nowrap h-8 border border-[var(--mcolor)] hover:bg-base-200"
												:class="researchMode ? 'border-[var(--mcolor)] bg-[var(--mcolor)] text-white shadow-lg' : 'text-[var(--mcolor)] bg-white'"
												:disabled="loading"
											>
												<svg v-if="researchMode" xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24">
													<path fill="currentColor" d="M16 21a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2v-1h8zM12.339 2.009A6 6 0 0 0 19.99 9.66q.009.17.01.34a7.97 7.97 0 0 1-1.756 5.001c-.55.686-1.889 1.727-2.185 2.999H7.94c-.297-1.273-1.637-2.315-2.187-3.001a8 8 0 0 1 6.585-12.99m5.19-1.68a.507.507 0 0 1 .942 0l.253.61a4.37 4.37 0 0 0 2.25 2.327l.718.32a.53.53 0 0 1 0 .962l-.76.338a4.36 4.36 0 0 0-2.218 2.25l-.247.566a.506.506 0 0 1-.934 0l-.246-.565a4.36 4.36 0 0 0-2.22-2.251l-.76-.338a.53.53 0 0 1 0-.963l.718-.32A4.37 4.37 0 0 0 17.276.94z" />
												</svg>
												<svg v-else xmlns="http://www.w3.org/2000/svg" width="16px" height="16px" viewBox="0 0 24 24">
													<path fill="currentColor" d="M12.339 2.009L12 4a6 6 0 0 0-4.686 9.748c.086.107.806.853.917.974c.997 1.084 1.61 2.076 1.742 3.278h4.054c.132-1.201.745-2.193 1.74-3.277c.113-.122.83-.866.917-.973A5.97 5.97 0 0 0 18 10l1.99-.34q.009.17.01.34a7.97 7.97 0 0 1-1.756 5.001C17.624 15.774 16 17 16 18.5V21a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2v-2.5c0-1.5-1.625-2.727-2.246-3.501a8 8 0 0 1 6.585-12.99M10 21h4v-1h-4zM17.53.328a.507.507 0 0 1 .94 0l.254.612a4.37 4.37 0 0 0 2.25 2.326l.718.32a.53.53 0 0 1 0 .962l-.76.338a4.36 4.36 0 0 0-2.218 2.251l-.247.565a.506.506 0 0 1-.934 0l-.246-.565a4.36 4.36 0 0 0-2.22-2.251l-.76-.338a.53.53 0 0 1 0-.963l.718-.32A4.37 4.37 0 0 0 17.276.94z" />
												</svg>
												<span :class="researchMode ? 'text-white' : 'text-[var(--mcolor)]'" class="text-[10px]">Research Mode</span>
											</button>
										</template>
									</Tooltip>
									
									<div v-if="researchMode && addedQuestions.length > 0" class="flex items-center gap-1.5">
										<span 
											v-for="question in displayedQuestionBadges" 
											:key="question.id"
											class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-[var(--mcolor)]/10 text-[var(--mcolor)] border border-[var(--mcolor)]/20 group hover:bg-[var(--mcolor)]/20 transition-colors"
										>
											Q{{ question.number }}
											<button 
												@click.stop="removeAddedQuestion(question)"
												class="opacity-60 hover:opacity-100 transition-opacity"
												type="button"
											>
												<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24">
													<path fill="currentColor" d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/>
												</svg>
											</button>
										</span>
										<span 
											v-if="remainingQuestionsCount > 0"
											class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-[var(--mcolor)]/10 text-[var(--mcolor)] border border-[var(--mcolor)]/20"
										>
											<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" class="mr-0.5">
												<path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6z"/>
											</svg>
											{{ remainingQuestionsCount }}
										</span>
									</div>
									
									<div v-if="!researchMode && attachedFiles.length > 0" class="flex items-center gap-1.5">
										<Tooltip 
											v-for="file in displayedFileBadges" 
											:key="file.id"
											:label="file.name"
											hoverWidth="20vmin"
											xTransformClass="translate(-50%,-115%)"
										>
											<template #svg>
												<span 
													class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-[var(--mcolor)]/10 text-[var(--mcolor)] border border-[var(--mcolor)]/20 group hover:bg-[var(--mcolor)]/20 transition-colors"
												>
													<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" class="mr-0.5">
														<path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/>
													</svg>
													<span class="max-w-[100px] truncate">{{ file.name }}</span>
													<button 
														@click.stop="!analyzingScript && !loading && removeAttachedFile(file)"
														class="opacity-60 hover:opacity-100 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
														:disabled="analyzingScript || loading"
														type="button"
													>
														<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24">
															<path fill="currentColor" d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/>
														</svg>
													</button>
												</span>
											</template>
										</Tooltip>
										<span 
											v-if="remainingFilesCount > 0"
											class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-[var(--mcolor)]/10 text-[var(--mcolor)] border border-[var(--mcolor)]/20"
										>
											<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" class="mr-0.5">
												<path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6z"/>
											</svg>
											{{ remainingFilesCount }}
										</span>
									</div>
								</div>
								
								<div class="flex-1"></div>
								
								<div class="flex items-center gap-2 relative question-dropdown-container">
									<Tooltip 
										v-if="researchMode"
										label="Add question for context"
										hoverWidth="10vmin"
										xTransformClass="translate(-50%,-115%)"
									>
										<template #svg>
											<button 
												type="button"
												@click="toggleQuestionDropdown"
												class="p-1.5 hover:bg-base-200 rounded transition-colors relative"
												:disabled="loading"
												:class="{ 'opacity-50': loading, 'bg-base-200': showQuestionDropdown }"
											>
												<svg xmlns="http://www.w3.org/2000/svg" width="28px" height="28px" viewBox="0 0 24 24">
													<path fill="var(--mcolor)" d="M11.96 21q-2.271 0-3.846-1.595t-1.575-3.867v-8.73q0-1.587 1.09-2.697Q8.722 3 10.309 3t2.678 1.11t1.091 2.698V13.5q0 .213-.144.356t-.357.144t-.356-.144t-.143-.356V6.789q-.006-1.166-.802-1.977T10.308 4q-1.163 0-1.966.821q-.804.821-.804 1.987v8.73q-.005 1.853 1.283 3.157Q10.11 20 11.961 20q.426 0 .839-.088t.783-.231q.188-.073.387.003t.278.264q.073.189-.012.39q-.086.202-.274.275q-.468.2-.972.294q-.505.093-1.03.093m4.886-1q-.213 0-.356-.144t-.144-.356v-2.116h-2.115q-.213 0-.356-.143q-.144-.144-.144-.357t.144-.356t.356-.144h2.115V14.27q0-.212.144-.356t.357-.144t.356.144t.143.356v2.115h2.116q.212 0 .356.144t.143.357t-.143.356t-.357.143h-2.115V19.5q0 .213-.144.356t-.357.144m-6.538-3.116q-.213 0-.356-.143t-.143-.357V7.27q0-.212.144-.356q.143-.144.356-.144t.356.144t.144.356v9.116q0 .212-.144.356t-.357.144m6.538-5.5q-.212 0-.355-.144t-.144-.356V7.269q0-.212.144-.356t.357-.144t.356.144t.143.356v3.616q0 .212-.144.356t-.357.143" />
												</svg>
											</button>
										</template>
									</Tooltip>
									
									<Tooltip 
										v-if="!researchMode"
										:label="attachmentLimitReached ? 'For now only one script can be attached. Remove the existing one to add a new one.' : 'Attach Survey Script (PDF only)'"
										hoverWidth="10vmin"
										xTransformClass="translate(-50%,-115%)"
									>
										<template #svg>
											<button 
												type="button"
												@click="openFileDialog"
												class="p-1.5 hover:bg-base-200 rounded transition-colors relative text-[var(--mcolor)]"
												:disabled="loading || attachmentLimitReached"
												:class="{ 'opacity-50 cursor-not-allowed': loading || attachmentLimitReached }"
											>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="25px" height="25px" viewBox="0 0 24 24">
                                                <g fill="none" stroke="#EE920C" stroke-linecap="square" stroke-width="1">
                                                    <path d="M20 11V7l-5-5H4v20h8m2-20v6h6" />
                                                    <path d="M19 19v-3.5a1.5 1.5 0 0 0-3 0V20a3 3 0 1 0 6 0v-3.5" />
                                                </g>
                                            </svg>
											</button>
										</template>
									</Tooltip>
									
									<input 
										ref="fileInputRef"
										type="file"
										accept=".pdf,application/pdf"
										multiple
										@change="handleFileSelect"
										class="hidden"
									>
									
									<div v-if="showQuestionDropdown" 
										 class="absolute bottom-full right-0 mb-2 w-80 max-h-96 bg-white border border-gray-200 rounded-lg shadow-xl z-50 overflow-hidden">
										<div class="p-3 border-b border-gray-200 bg-gray-50">
											<div class="flex items-center justify-between mb-2">
												<h3 class="text-sm font-semibold text-gray-800">Add Question Context</h3>
												<button @click="showQuestionDropdown = false" class="text-gray-400 hover:text-gray-600">
													<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
														<path fill="currentColor" d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/>
													</svg>
												</button>
											</div>
											<div class="flex items-center justify-between">
												<p class="text-xs text-gray-600">{{ selectedQuestions.length }} selected</p>
												<button 
													@click="toggleSelectAll"
													class="text-xs text-[var(--mcolor)] hover:text-[var(--mcolor2)] font-medium transition-colors"
												>
													{{ areAllQuestionsSelected ? 'Deselect All' : 'Select All' }}
												</button>
											</div>
										</div>
										<div class="overflow-y-auto max-h-64">
											<div v-if="availableQuestions.length === 0" class="p-4 text-center text-sm text-gray-500">
												No questions available
											</div>
											<div v-else class="divide-y divide-gray-100">
												<label v-for="question in availableQuestions" 
													   :key="question.id"
													   class="flex items-start p-3 hover:bg-gray-50 cursor-pointer transition-colors">
													<input 
														type="checkbox" 
														:checked="isQuestionSelected(question)"
														@change="toggleQuestionSelection(question)"
														class="mt-1 rounded border-gray-300 text-[var(--mcolor)] focus:ring-[var(--mcolor)]"
													>
													<div class="ml-3 flex-1">
														<div class="flex items-center gap-2 mb-1">
															<span class="text-xs font-semibold text-[var(--mcolor)] bg-[var(--mcolor)]/10 px-2 py-0.5 rounded">
																Q{{ question.number }}
															</span>
														</div>
														<p class="text-xs text-gray-700 line-clamp-2">{{ question.text }}</p>
													</div>
												</label>
											</div>
										</div>
										<div class="p-3 border-t border-gray-200 bg-gray-50">
											<button 
												@click="addSelectedQuestionsToChat"
												:disabled="selectedQuestions.length === 0"
												class="w-full bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white font-medium py-2 px-4 rounded-lg transition-colors text-sm disabled:opacity-50 disabled:cursor-not-allowed">
												Add {{ selectedQuestions.length > 0 ? `${selectedQuestions.length} ` : '' }}Question{{ selectedQuestions.length !== 1 ? 's' : '' }} to Chat
											</button>
										</div>
									</div>
									
                                    <button 
										type="submit" 
										class="p-2 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
										:disabled="inputsDisabled || newMessage.trim() === ''"
									>
                                    <svg v-if="!loading" xmlns="http://www.w3.org/2000/svg" width="28px" height="28px" viewBox="0 0 24 24">
                                        <g fill="none">
                                            <path d="m12.594 23.258l-.012.002l-.071.035l-.02.004l-.014-.004l-.071-.036q-.016-.004-.024.006l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.016-.018m.264-.113l-.014.002l-.184.093l-.01.01l-.003.011l.018.43l.005.012l.008.008l.201.092q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.003-.011l.018-.43l-.003-.012l-.01-.01z" />
                                            <path fill="#EE920C" d="M20.235 5.686c.432-1.195-.726-2.353-1.921-1.92L3.709 9.048c-1.199.434-1.344 2.07-.241 2.709l4.662 2.699l4.163-4.163a1 1 0 0 1 1.414 1.414L9.544 15.87l2.7 4.662c.638 1.103 2.274.957 2.708-.241z" />
                                        </g>
                                    </svg>
										<span v-else class="w-5 h-5 loading loading-spinner loading-xs"></span>
									</button>
								</div>
							</div>
						</form>
					</div>
					<p class="text-[10px] py-1 text-gray-500 py-2 text-center">By using this you agree to our <a href="#" @click.prevent="showTermsModal = true" class="text-[var(--mcolor)] hover:underline">terms and conditions</a>.</p>
				</div>
			</div>
				</div>
			</div>

			<TermsModal :show="showTermsModal" @close="showTermsModal = false" />
		</div>

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
					<div @click="cancelConversationRating" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
					<div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
						<!-- Decorative Header -->
						<div class="bg-gradient-to-r from-orange-50 to-amber-50 px-6 py-5 border-b border-orange-100">
							<div class="flex items-center justify-between">
								<div class="flex items-center space-x-3">
									<div class="flex-shrink-0">
										<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-orange-500" viewBox="0 0 24 24">
											<path fill="currentColor" d="m9.94 12.646l-2.248-.749c-2.353-.784-3.53-1.176-3.53-1.897c0-.72 1.177-1.113 3.53-1.897l8.513-2.838c1.656-.552 2.484-.828 2.921-.391s.161 1.265-.39 2.92l-2.839 8.514c-.784 2.353-1.176 3.53-1.897 3.53c-.72 0-1.113-1.177-1.897-3.53l-.75-2.247l4.354-4.354a1 1 0 0 0-1.414-1.414z" />
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
						<div class="px-6 py-6">
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
								<!-- Selected rating text -->
								<p v-if="conversationRating > 0" class="text-center mt-4 text-sm font-medium text-gray-700 animate-fade-in">
									{{ conversationRating === 1 ? '😞 Poor' : conversationRating === 2 ? '😐 Fair' : conversationRating === 3 ? '🙂 Good' : conversationRating === 4 ? '😊 Very Good' : '🌟 Excellent' }}
								</p>
							</div>
							<div class="mb-6">
								<label class="block text-sm font-medium text-gray-700 mb-2">
									Tell us more (optional)
								</label>
								<textarea 
									v-model="conversationRatingComment"
									rows="3"
									class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent resize-none text-sm"
									placeholder="What did you like or what could we improve?"
								></textarea>
								<p class="mt-2 text-xs text-gray-500">Your feedback helps us improve EXPRESS AI</p>
							</div>
						</div>
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
									:class="conversationRating > 0 ? 'bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 focus:ring-orange-500' : 'bg-gray-300 cursor-not-allowed'"
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
    </div>
</template>
