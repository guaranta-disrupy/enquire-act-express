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

// Export customer support message content
export const CUSTOMER_SUPPORT_MESSAGES = {
    initial: 'How would you like to proceed?',
    submitSuccess: '✅ Thank you for contacting us! We\'ve received your support request and will get back to you within 24 hours.',
    keepChatting: 'Sure thing! We\'ll continue our conversation here. What would you like help with next?'
};
</script>

<script setup>
import { ref, onMounted } from 'vue';
import Helpers from '@/Mixins/Helpers.js';
import axios from 'axios';

// Customer support message content
const CUSTOMER_SUPPORT_MESSAGES = {
    initial: 'How would you like to proceed?',
    submitSuccess: '✅ Thank you for contacting us! We\'ve received your support request and will get back to you within 24 hours.',
    keepChatting: 'Sure thing! We\'ll continue our conversation here. What would you like help with next?'
};

const { toast } = Helpers();

const props = defineProps({
    conversationId: {
        type: String,
        default: null
    },
    platform: {
        type: String,
        default: 'fluent'
    }
});

const emit = defineEmits(['submit-success', 'keep-chatting']);

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

/**
 * Call tokenless utility prompt API endpoint
 * Backend returns the message, frontend just displays it
 */
const callUtilityPrompt = async (type) => {
    try {
        const response = await axios.post(route('api.chat.utility-prompt'), {
            type: type,
            conversation_id: props.conversationId,
            platform: props.platform
        });

        if (response.data.success && response.data.message) {
            // Use message from backend response (backend sets type: 'customer_support' for support responses)
            // This ensures the message comes from utility-prompt API, NOT from Prism
            return {
                success: true,
                message: {
                    role: response.data.message.role || 'assistant',
                    content: response.data.message.content, // Backend generates: "Sure thing! We'll continue our conversation here. What would you like help with next?"
                    type: 'customer_support', // Explicitly set to prevent Prism processing
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
    selectedImages.value = [];
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
    }
    
    selectedImages.value = [...selectedImages.value, ...validImages];
    
    // Reset input so same file can be selected again
    if (event.target) {
        event.target.value = '';
    }
};

const removeImage = (index) => {
    selectedImages.value.splice(index, 1);
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
        // The actual form submission to support system would go here
        // For now, we just log it
        console.log('Support form submitted:', {
            name: formData.value.name.trim() || null,
            email: formData.value.email.trim() || null,
            category: formData.value.category || null,
            problem: formData.value.problem.trim(),
            images: selectedImages.value
        });
        
        // Call tokenless API for support submit response
        // Backend generates and returns the message
        const result = await callUtilityPrompt('support_submit');
        
        // Emit success event with message data for parent to handle
        emit('submit-success', result);
        
        toast.success('Support request submitted successfully');
        
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
            selectedImages.value = [];
        }, 500);
    } catch (error) {
        console.error('Failed to submit support form:', error);
        toast.error('Failed to submit support request. Please try again.');
        isSubmitting.value = false;
    }
};

const handleKeepChatting = async () => {
    try {
        // Call tokenless API for keep chatting response
        // Backend generates and returns the message
        const result = await callUtilityPrompt('support_keep_chatting');
        
        // Emit keep chatting event with message data for parent to handle
        emit('keep-chatting', result);
    } catch (error) {
        console.error('Failed to handle keep chatting:', error);
        toast.error('Failed to continue conversation. Please try again.');
    }
};

// Initialize customer support: Component initialization
// Note: The initial message is now handled in Chatbot.vue sendMessage function
// This component just renders the UI when mounted
onMounted(async () => {
    // Prevent duplicate initialization if component remounts
    if (hasInitialized.value) {
        return;
    }

    if (!props.conversationId) {
        console.warn('CustomerSupport: No conversation ID provided');
        return;
    }

    hasInitialized.value = true;
    // The initial message is already added by Chatbot.vue sendMessage function
    // No need to emit initial-message here anymore
});
</script>

<template>
    <!-- Customer Support Request Card -->
    <div class="bg-gray-100 rounded-md border border-gray-300 shadow-lg overflow-hidden ml-3">
        <!-- Initial Card View -->
        <div v-if="!showForm" class="overflow-hidden">
            <!-- Card Header -->
            <div class="px-4 py-3 border-b border-gray-300 bg-gray-200/30">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" style="color: #257556;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <h4 class="font-semibold text-gray-900">Customer Support Request</h4>
                </div>
            </div>
            
            <!-- Card Description -->
            <div class="px-4 pt-3 pb-2">
                <p class="text-xs text-gray-600 leading-relaxed">
                    Our support team is here to help! Choose how you'd like to get assistance:
                </p>
            </div>
            
            <!-- Card Body with Action Buttons -->
            <div class="px-4 pb-4 space-y-2">
                <!-- Send Email Button -->
                <button 
                    @click="handleSendEmailClick"
                    class="w-full flex items-center justify-between gap-3 h-auto py-3 px-4 rounded-md text-sm font-medium text-white transition-all duration-200 hover:scale-[1.02] group"
                    style="background-color: #257556;"
                    :style="{ 'opacity': isSubmitting ? '0.6' : '1' }"
                >
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <div class="text-left">
                            <div class="font-semibold text-sm">Send an Email</div>
                            <div class="text-xs font-light opacity-80">Get detailed help via email</div>
                        </div>
                    </div>
                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                
                <!-- Keep Chatting Button -->
                <button 
                    @click="handleKeepChatting"
                    class="w-full flex items-center justify-between gap-3 h-auto py-3 px-4 rounded-md text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:scale-[1.02] transition-all duration-200 group"
                    :disabled="isSubmitting"
                >
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <div class="text-left">
                            <div class="font-semibold text-sm">Keep Chatting</div>
                            <div class="text-xs font-light opacity-70">Continue with AI assistant</div>
                        </div>
                    </div>
                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
            
            <!-- Support Info Footer -->
            <div class="px-4 py-3 bg-gray-100/30 border-t border-gray-300">
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Typical response time: Within 24 hours</span>
                </div>
            </div>
        </div>
        
        <!-- Contact Form View -->
        <div v-else class="overflow-hidden">
            <!-- Form Header -->
            <div class="px-4 py-3 border-b border-gray-300 bg-gray-200/30">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" style="color: #257556;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <h4 class="font-semibold text-gray-800">Contact Support</h4>
                    </div>
                    <button 
                        @click="handleBack"
                        class="p-1 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isSubmitting"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Form Description -->
            <div class="px-4 pt-3 pb-2">
                <p class="text-xs text-gray-600 leading-relaxed">
                    Please describe your issue and we'll get back to you as soon as possible.
                </p>
            </div>
            
            <!-- Form Body -->
            <form @submit.prevent="handleSubmit" class="px-4 pb-4 space-y-3">
                <!-- Name Input (Optional) -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
                        Name <span class="text-gray-500 font-normal">(optional)</span>
                    </label>
                    <input
                        v-model="formData.name"
                        type="text"
                        placeholder="Your name"
                        class="w-full px-3 py-1.5 text-sm bg-white border border-gray-300 rounded-md text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-200 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isSubmitting"
                    />
                </div>
                
                <!-- Email Input (Optional) -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
                        Email <span class="text-gray-500 font-normal">(optional)</span>
                    </label>
                    <input
                        v-model="formData.email"
                        type="email"
                        placeholder="your.email@example.com"
                        class="w-full px-3 py-1.5 text-sm bg-white border border-gray-300 rounded-md text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-200 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isSubmitting"
                    />
                </div>
                
                <!-- Category Dropdown -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
                        Category <span class="text-gray-500 font-normal">(optional)</span>
                    </label>
                    <select
                        v-model="formData.category"
                        class="w-full px-3 py-1.5 text-sm bg-white border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-yellow-200 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isSubmitting"
                    >
                        <option v-for="category in categories" :key="category.value" :value="category.value">
                            {{ category.label }}
                        </option>
                    </select>
                </div>
                
                <!-- Problem Description (Required) -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
                        Problem Description <span class="text-red-600">*</span>
                    </label>
                    <textarea
                        v-model="formData.problem"
                        rows="4"
                        maxlength="1000"
                        placeholder="Please describe your issue or question..."
                        class="w-full px-3 py-1.5 text-sm bg-white border border-gray-300 rounded-md text-gray-900 placeholder:text-gray-400 resize-none focus:outline-none focus:ring-2 focus:ring-yellow-200 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isSubmitting"
                        required
                    ></textarea>
                    <p class="text-xs text-gray-500 mt-1" :class="{ 'text-orange-600': formData.problem.length > 900 }">
                        {{ formData.problem.length }}/1000 characters
                    </p>
                </div>
                
                <!-- Image Attachments -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
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
                        class="w-full flex items-center justify-center gap-2 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isSubmitting || selectedImages.length >= maxImageCount"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ selectedImages.length >= maxImageCount ? 'Maximum images reached' : 'Add Images' }}
                    </button>
                    
                    <!-- File size and count note -->
                    <p class="text-xs text-gray-500 mt-1.5">
                        Maximum {{ maxImageCount }} images, 10MB per image (JPG, PNG, GIF, WEBP)
                    </p>
                    
                    <!-- Selected images preview -->
                    <div v-if="selectedImages.length > 0" class="mt-3 space-y-2">
                        <div
                            v-for="(image, index) in selectedImages"
                            :key="index"
                            class="flex items-center gap-2 p-2 bg-white rounded-md border border-gray-300"
                        >
                            <div class="flex-shrink-0 w-12 h-12 rounded overflow-hidden bg-gray-100">
                                <img
                                    :src="URL.createObjectURL(image)"
                                    :alt="image.name"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-900 truncate">{{ image.name }}</p>
                                <p class="text-xs text-gray-500">{{ formatFileSize(image.size) }}</p>
                            </div>
                            <button
                                type="button"
                                @click="removeImage(index)"
                                class="p-1 rounded-md text-red-600 hover:bg-red-50 transition-colors flex-shrink-0 disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="isSubmitting"
                                title="Remove image"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="flex gap-2 pt-2">
                    <button
                        type="button"
                        @click="handleBack"
                        class="flex-1 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isSubmitting"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="flex-1 px-3 py-1.5 text-sm font-medium rounded-md text-white transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        style="background-color: #257556;"
                        :disabled="!formData.problem.trim() || isSubmitting"
                    >
                        <span v-if="!isSubmitting">Send Message</span>
                        <span v-else class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Sending...
                        </span>
                    </button>
                </div>
            </form>
            
            <!-- Form Footer -->
            <div class="px-4 py-3 bg-gray-100/30 border-t border-gray-300">
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>Your information is secure and confidential</span>
                </div>
            </div>
        </div>
    </div>
</template>
