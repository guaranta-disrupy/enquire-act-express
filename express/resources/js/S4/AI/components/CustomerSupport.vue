<script>
/**
 * Detect if user is requesting customer support
 * @param {string} userMessage - The user's message
 * @returns {boolean} - True if customer support is requested
 */
export const detectCustomerSupportRequest = (userMessage) => {
    const supportKeywords = [
        'technical support',
        'customer service',
        'customer support',
        'need support',
        'contact support',
        'help from support',
    ];
    
    const lowerMessage = userMessage.toLowerCase();
    return supportKeywords.some(keyword => lowerMessage.includes(keyword));
};
</script>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const route = (name, ...params) => {
    if (typeof window !== 'undefined' && typeof window.route === 'function') {
        try {
            return window.route(name, ...params);
        } catch (error) {
            console.warn(`Route '${name}' not found in Ziggy, using fallback`);
        }
    }
    switch (name) {
        case 'api.chat.utility-prompt':
            return '/api/chat/utility-prompt';
        default:
            return `/${name.replace(/\./g, '/')}`;
    }
};

// Customer support message content
const CUSTOMER_SUPPORT_MESSAGES = {
    initial: 'How would you like to proceed?',
    submitSuccess: 'Thank you for your interest! This customer support feature is currently a demo and will be fully implemented soon. Your request has been noted, and we appreciate your patience as we work on bringing you the complete support experience.',
    keepChatting: 'Sure thing! We\'ll continue our conversation here. What would you like help with next?'
};

const toast = window.toast ?? {
    success: (m) => console.log('[toast.success]', m),
    error: (m) => console.error('[toast.error]', m),
    info: (m) => console.log('[toast.info]', m),
};

const props = defineProps({
    conversationId: {
        type: String,
        default: null
    },
    platform: {
        type: String,
        default: 'express'
    }
});

const emit = defineEmits(['submit-success', 'keep-chatting', 'initial-message']);

const hasInitialized = ref(false); // Track if initial message has been sent
const showForm = ref(false);
const formData = ref({
    name: '',
    email: '',
    category: '',
    problem: ''
});
const isSubmitting = ref(false);

// Image attachment state
const selectedImages = ref([]);
const imageObjectUrls = ref([]); // Store object URLs for preview
const fileInputRef = ref(null);
const maxFileSize = 10 * 1024 * 1024; // 10MB
const maxImageCount = 5;
const allowedImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

// Category options
const categories = [
    { value: '', label: 'Select a category' },
    { value: 'technical', label: 'Technical Issue' },
    { value: 'billing', label: 'Billing Question' },
    { value: 'feature', label: 'Feature Request' },
    { value: 'bug', label: 'Bug Report' },
    { value: 'account', label: 'Account Issue' },
    { value: 'other', label: 'Other' }
];

const callUtilityPrompt = async (type) => {
    try {
        const response = await axios.post(route('api.chat.utility-prompt'), {
            type: type,
            conversation_id: props.conversationId,
            platform: props.platform
        });

        if (response.data.success && response.data.message) {
            return {
                success: true,
                message: {
                    role: response.data.message.role || 'assistant',
                    content: response.data.message.content,
                    type: response.data.message.type || 'customer_support',
                    timestamp: response.data.message.timestamp || new Date().toISOString()
                }
            };
        }
        throw new Error('API call failed');
    } catch (error) {
        console.error('Failed to call utility prompt API:', error);
        throw error;
    }
};

const handleSendEmailClick = () => {
    showForm.value = true;
};

const handleBack = () => {
    showForm.value = false;
    formData.value = {
        name: '',
        email: '',
        category: '',
        problem: ''
    };
    // Clean up object URLs before clearing images
    imageObjectUrls.value.forEach(url => {
        if (url && typeof URL !== 'undefined' && URL.revokeObjectURL) {
            URL.revokeObjectURL(url);
        }
    });
    selectedImages.value = [];
    imageObjectUrls.value = [];
};

const triggerImageSelect = () => {
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

const handleImageSelect = (event) => {
    const files = Array.from(event.target.files || []);
    
    // Check if adding these files would exceed the limit
    if (selectedImages.value.length + files.length > maxImageCount) {
        alert(`You can only attach up to ${maxImageCount} images. Please remove some images first.`);
        if (event.target) {
            event.target.value = '';
        }
        return;
    }
    
    // Validate files
    const validImages = [];
    const newObjectUrls = [];
    
    for (const file of files) {
        // Check file size
        if (file.size > maxFileSize) {
            alert(`Image "${file.name}" is too large. Maximum size is 10MB.`);
            continue;
        }
        
        // Check file type
        const isValidType = allowedImageTypes.includes(file.type) || 
                          ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(file.name.split('.').pop().toLowerCase());
        
        if (!isValidType) {
            alert(`File "${file.name}" is not a supported image format. Please use JPG, PNG, GIF, or WEBP images.`);
            continue;
        }
        
        validImages.push(file);
        // Create object URL for preview
        if (typeof URL !== 'undefined' && URL.createObjectURL) {
            newObjectUrls.push(URL.createObjectURL(file));
        } else {
            newObjectUrls.push(null);
        }
    }
    
    selectedImages.value = [...selectedImages.value, ...validImages];
    imageObjectUrls.value = [...imageObjectUrls.value, ...newObjectUrls];
    
    // Reset input so same file can be selected again
    if (event.target) {
        event.target.value = '';
    }
};

const removeImage = (index) => {
    // Clean up object URL before removing
    if (imageObjectUrls.value[index] && typeof URL !== 'undefined' && URL.revokeObjectURL) {
        URL.revokeObjectURL(imageObjectUrls.value[index]);
    }
    selectedImages.value.splice(index, 1);
    imageObjectUrls.value.splice(index, 1);
};

// Helper function to get object URL safely
const getImageUrl = (index) => {
    return imageObjectUrls.value[index] || '';
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const handleSubmit = async () => {
    if (!formData.value.problem.trim()) {
        toast.error('Problem description is required.');
        return;
    }
    
    isSubmitting.value = true;
    
    try {
        console.log('Support form submitted:', {
            name: formData.value.name.trim() || null,
            email: formData.value.email.trim() || null,
            category: formData.value.category || null,
            problem: formData.value.problem.trim(),
            images: selectedImages.value
        });
        
        let result;
        try {
            result = await callUtilityPrompt('support_submit');
        } catch (apiError) {
            console.warn('Utility prompt API not available, using fallback:', apiError);
            result = {
                success: true,
                message: {
                    role: 'assistant',
                    content: CUSTOMER_SUPPORT_MESSAGES.submitSuccess,
                    type: 'normal',
                    hasCustomerSupport: false,
                    liked: false,
                    disliked: false,
                    feedbackSubmitted: false,
                    feedbackDisabled: false,
                    isStreaming: false,
                    timestamp: new Date().toISOString()
                }
            };
        }
        
        if (result.message && !result.message.hasOwnProperty('hasCustomerSupport')) {
            result.message.hasCustomerSupport = false;
        }
        if (result.message && !result.message.hasOwnProperty('liked')) {
            result.message.liked = false;
        }
        if (result.message && !result.message.hasOwnProperty('disliked')) {
            result.message.disliked = false;
        }
        if (result.message && !result.message.hasOwnProperty('feedbackSubmitted')) {
            result.message.feedbackSubmitted = false;
        }
        if (result.message && !result.message.hasOwnProperty('feedbackDisabled')) {
            result.message.feedbackDisabled = false;
        }
        if (result.message && !result.message.hasOwnProperty('isStreaming')) {
            result.message.isStreaming = false;
        }
        
        emit('submit-success', result);
        
        toast.info('This is a demo feature - full implementation coming soon!');
        
        // Reset form after a brief delay
        setTimeout(() => {
            isSubmitting.value = false;
            showForm.value = false;
            formData.value = {
                name: '',
                email: '',
                category: '',
                problem: ''
            };
            imageObjectUrls.value.forEach(url => {
                if (url && typeof URL !== 'undefined' && URL.revokeObjectURL) {
                    URL.revokeObjectURL(url);
                }
            });
            selectedImages.value = [];
            imageObjectUrls.value = [];
        }, 500);
    } catch (error) {
        console.error('Failed to submit support form:', error);
        toast.error('Failed to submit support request. Please try again.');
        isSubmitting.value = false;
    }
};

const handleKeepChatting = async () => {
    try {
        let result;
        try {
            result = await callUtilityPrompt('support_keep_chatting');
        } catch (apiError) {
            console.warn('Utility prompt API not available, using fallback:', apiError);
            result = {
                success: true,
                message: {
                    role: 'assistant',
                    content: CUSTOMER_SUPPORT_MESSAGES.keepChatting,
                    type: 'normal',
                    hasCustomerSupport: false,
                    liked: false,
                    disliked: false,
                    feedbackSubmitted: false,
                    feedbackDisabled: false,
                    isStreaming: false,
                    timestamp: new Date().toISOString()
                }
            };
        }
        
        if (result.message && !result.message.hasOwnProperty('hasCustomerSupport')) {
            result.message.hasCustomerSupport = false;
        }
        if (result.message && !result.message.hasOwnProperty('liked')) {
            result.message.liked = false;
        }
        if (result.message && !result.message.hasOwnProperty('disliked')) {
            result.message.disliked = false;
        }
        if (result.message && !result.message.hasOwnProperty('feedbackSubmitted')) {
            result.message.feedbackSubmitted = false;
        }
        if (result.message && !result.message.hasOwnProperty('feedbackDisabled')) {
            result.message.feedbackDisabled = false;
        }
        if (result.message && !result.message.hasOwnProperty('isStreaming')) {
            result.message.isStreaming = false;
        }
        
        emit('keep-chatting', result);
    } catch (error) {
        console.error('Failed to handle keep chatting:', error);
        toast.error('Failed to continue conversation. Please try again.');
    }
};

onMounted(async () => {
    if (hasInitialized.value) {
        return;
    }

    if (!props.conversationId) {
        console.warn('CustomerSupport: No conversation ID provided, skipping initial message save');
        return;
    }

    hasInitialized.value = true;

    try {
        try {
            const response = await axios.post(route('api.chat.utility-prompt'), {
                type: 'support_initial',
                conversation_id: props.conversationId,
                platform: props.platform
            });

            if (response.data?.success && response.data?.message) {
                emit('initial-message', {
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
                return;
            }
        } catch (apiError) {
            console.warn('Utility prompt API not available, using fallback:', apiError);
        }
        
        emit('initial-message', {
            role: 'assistant',
            content: CUSTOMER_SUPPORT_MESSAGES.initial,
            type: 'normal',
            hasCustomerSupport: true,
            liked: false,
            disliked: false,
            feedbackSubmitted: false,
            feedbackDisabled: false,
            isStreaming: false,
            timestamp: new Date().toISOString()
        });
    } catch (error) {
        console.error('Failed to save customer support initial message:', error);
        emit('initial-message', {
            role: 'assistant',
            content: CUSTOMER_SUPPORT_MESSAGES.initial,
            type: 'normal',
            hasCustomerSupport: true,
            liked: false,
            disliked: false,
            feedbackSubmitted: false,
            feedbackDisabled: false,
            isStreaming: false,
            timestamp: new Date().toISOString()
        });
    }
});
</script>

<template>
    <!-- Customer Support Request Card -->
    <div class="bg-white rounded border border-gray-200 shadow-sm overflow-hidden">
        <!-- Initial Card View -->
        <div v-if="!showForm" class="overflow-hidden">
            <!-- Card Header -->
            <div class="px-3 py-2">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center" style="background-color: var(--mcolor);">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h4 class="font-medium text-sm text-gray-800">Customer Support Request</h4>
                </div>
            </div>
            
            <!-- Card Description -->
            <div class="px-3 pb-2">
                <p class="text-xs text-gray-600">
                    Our support team is here to help! Choose how you'd like to get assistance:
                </p>
            </div>
            
            <!-- Card Body with Action Buttons -->
            <div class="px-3 pb-3 space-y-1.5">
                <!-- Send Email Button -->
                <button 
                    @click="handleSendEmailClick"
                    class="w-full flex items-center justify-between gap-2 py-2 px-3 rounded hover:opacity-90 transition-opacity"
                    style="background-color: var(--mcolor);"
                >
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <div class="text-left">
                            <div class="font-medium text-xs text-white">Send an Email</div>
                            <div class="text-xs text-white/80">Get detailed help via email.</div>
                        </div>
                    </div>
                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                
                <!-- Keep Chatting Button -->
                <button 
                    @click="handleKeepChatting"
                    class="w-full flex items-center justify-between gap-2 py-2 px-3 rounded bg-white border border-gray-300 hover:bg-gray-50 transition-colors"
                >
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <div class="text-left">
                            <div class="font-medium text-xs text-gray-800">Keep Chatting</div>
                            <div class="text-xs text-gray-600">Continue with AI assistant.</div>
                        </div>
                    </div>
                    <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
            
            <!-- Support Info Footer -->
            <div class="px-3 py-2 border-t border-gray-200">
                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Typical response time: Within 24 hours</span>
                </div>
            </div>
        </div>
        
        <!-- Contact Form View -->
        <div v-else class="overflow-hidden">
            <!-- Form Header -->
            <div class="px-3 py-2 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4" style="color: var(--mcolor);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <h4 class="font-medium text-sm text-gray-800">Contact Support</h4>
                    </div>
                    <button 
                        @click="handleBack"
                        class="p-1 hover:bg-gray-100 rounded"
                        :disabled="isSubmitting"
                    >
                        <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Form Description -->
            <div class="px-3 pt-2 pb-2">
                <p class="text-xs text-gray-600">
                    Please describe your issue and we'll get back to you as soon as possible.
                </p>
            </div>
            
            <!-- Form Body -->
            <form @submit.prevent="handleSubmit" class="px-3 pb-3 space-y-2">
                <!-- Name Input (Optional) -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Name <span class="text-gray-500 font-normal">(optional)</span>
                    </label>
                    <input
                        v-model="formData.name"
                        type="text"
                        placeholder="Your name"
                        class="w-full px-2.5 py-1.5 text-xs border border-gray-300 rounded bg-white text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-300"
                        :disabled="isSubmitting"
                        :class="{ 'opacity-50 cursor-not-allowed': isSubmitting }"
                    />
                </div>
                
                <!-- Email Input (Optional) -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Email <span class="text-gray-500 font-normal">(optional)</span>
                    </label>
                    <input
                        v-model="formData.email"
                        type="email"
                        placeholder="your.email@example.com"
                        class="w-full px-2.5 py-1.5 text-xs border border-gray-300 rounded bg-white text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-300"
                        :disabled="isSubmitting"
                        :class="{ 'opacity-50 cursor-not-allowed': isSubmitting }"
                    />
                </div>
                
                <!-- Category Dropdown -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Category <span class="text-gray-500 font-normal">(optional)</span>
                    </label>
                    <select
                        v-model="formData.category"
                        class="w-full px-2.5 py-1.5 text-xs border border-gray-300 rounded bg-white text-gray-800 focus:outline-none focus:ring-1 focus:ring-gray-300"
                        :disabled="isSubmitting"
                        :class="{ 'opacity-50 cursor-not-allowed': isSubmitting }"
                    >
                        <option v-for="category in categories" :key="category.value" :value="category.value">
                            {{ category.label }}
                        </option>
                    </select>
                </div>
                
                <!-- Problem Description (Required) -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Problem Description <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="formData.problem"
                        rows="3"
                        maxlength="1000"
                        placeholder="Please describe your issue or question..."
                        class="w-full px-2.5 py-1.5 text-xs border border-gray-300 rounded bg-white text-gray-800 placeholder:text-gray-400 resize-none focus:outline-none focus:border-gray-400 focus:ring-1 focus:ring-gray-300"
                        :disabled="isSubmitting"
                        required
                        :class="{ 'opacity-50 cursor-not-allowed': isSubmitting }"
                    ></textarea>
                    <p class="text-xs text-gray-500 mt-0.5" :class="{ 'text-orange-500': formData.problem.length > 900 }">
                        {{ formData.problem.length }}/1000 characters
                    </p>
                </div>
                
                <!-- Image Attachments -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Attach Images <span class="text-gray-500 font-normal">(optional)</span>
                    </label>
                    
                    <!-- Hidden file input -->
                    <input
                        ref="fileInputRef"
                        type="file"
                        multiple
                        accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                        @change="handleImageSelect"
                        class="hidden"
                        :disabled="isSubmitting || selectedImages.length >= maxImageCount"
                    />
                    
                    <!-- File select button -->
                    <button
                        type="button"
                        @click="triggerImageSelect"
                        class="w-full px-2.5 py-1.5 text-xs border border-gray-300 rounded bg-gray-50 hover:bg-gray-100 text-gray-700 flex items-center justify-center gap-1.5"
                        :disabled="isSubmitting || selectedImages.length >= maxImageCount"
                        :class="{ 'opacity-50 cursor-not-allowed': selectedImages.length >= maxImageCount || isSubmitting }"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ selectedImages.length >= maxImageCount ? 'Maximum images reached' : 'Add Images' }}
                    </button>
                    
                    <!-- File size and count note -->
                    <p class="text-xs text-gray-500 mt-0.5">
                        Maximum {{ maxImageCount }} images, 10MB per image (JPG, PNG, GIF, WEBP)
                    </p>
                    
                    <!-- Selected images preview -->
                    <div v-if="selectedImages.length > 0" class="mt-2 space-y-1.5">
                        <div
                            v-for="(image, index) in selectedImages"
                            :key="index"
                            class="flex items-center gap-2 p-1.5 bg-gray-50 rounded border border-gray-200"
                        >
                            <div class="flex-shrink-0 w-10 h-10 rounded overflow-hidden bg-gray-200">
                                <img
                                    :src="getImageUrl(index)"
                                    :alt="image.name"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-700 truncate">{{ image.name }}</p>
                                <p class="text-xs text-gray-500">{{ formatFileSize(image.size) }}</p>
                            </div>
                            <button
                                type="button"
                                @click="removeImage(index)"
                                class="p-1 hover:bg-gray-200 rounded flex-shrink-0"
                                :disabled="isSubmitting"
                                title="Remove image"
                            >
                                <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="flex gap-2 pt-1">
                    <button
                        type="button"
                        @click="handleBack"
                        class="flex-1 px-3 py-1.5 text-xs border border-gray-300 rounded bg-white hover:bg-gray-50 text-gray-700"
                        :disabled="isSubmitting"
                        :class="{ 'opacity-50 cursor-not-allowed': isSubmitting }"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="flex-1 px-3 py-1.5 text-xs rounded text-white"
                        style="background-color: var(--mcolor);"
                        :disabled="!formData.problem.trim() || isSubmitting"
                        :class="{ 'opacity-50 cursor-not-allowed': !formData.problem.trim() || isSubmitting }"
                    >
                        <span v-if="!isSubmitting">Send Message</span>
                        <span v-else>Sending...</span>
                    </button>
                </div>
            </form>
            
            <!-- Form Footer -->
            <div class="px-3 py-2 border-t border-gray-200">
                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>Your information is secure and confidential</span>
                </div>
            </div>
        </div>
    </div>
</template>
