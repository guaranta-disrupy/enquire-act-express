<script setup>
const props = defineProps({
    show: { type: Boolean, default: false },
    feedbackReasons: { type: Array, default: () => [] },
    feedbackText: { type: String, default: '' },
    isFeedbackValid: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle-reason', 'cancel', 'submit', 'update:feedbackText']);

const isChecked = (reason) => props.feedbackReasons.includes(reason);

const onTextInput = (event) => {
    emit('update:feedbackText', event.target.value);
};
</script>

<template>
    <div v-if="show" class="bubble-speech bubble-left">
        <div class="chat-bubble bg-white border border-gray-200 shadow-md max-w-md">
            <div class="p-4">
                <h3 class="text-md font-semibold text-gray-900 mb-4">Why is this response bad?</h3>
                
                <div class="space-y-3 mb-6">
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            value="Harmful"
                            :checked="isChecked('Harmful')"
                            @change="$emit('toggle-reason', 'Harmful')"
                            class="rounded border-gray-300 text-orange-600 focus:ring-[var(--mcolor)]"
                        >
                        <span class="ml-2 text-sm text-gray-700">Harmful</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            value="Not accurate"
                            :checked="isChecked('Not accurate')"
                            @change="$emit('toggle-reason', 'Not accurate')"
                            class="rounded border-gray-300 text-orange-600 focus:ring-[var(--mcolor)]"
                        >
                        <span class="ml-2 text-sm text-gray-700">Not accurate</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            value="Not useful or incomplete"
                            :checked="isChecked('Not useful or incomplete')"
                            @change="$emit('toggle-reason', 'Not useful or incomplete')"
                            class="rounded border-gray-300 text-orange-600 focus:ring-[var(--mcolor)]"
                        >
                        <span class="ml-2 text-sm text-gray-700">Not useful or incomplete</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            value="Something else"
                            :checked="isChecked('Something else')"
                            @change="$emit('toggle-reason', 'Something else')"
                            class="rounded border-gray-300 text-orange-600 focus:ring-[var(--mcolor)]"
                        >
                        <span class="ml-2 text-sm text-gray-700">Something else</span>
                    </label>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Provide additional feedback (optional)
                    </label>
                    <textarea 
                        :value="feedbackText"
                        @input="onTextInput"
                        rows="4"
                        class="w-full px-3 py-2 border text-sm border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[var(--mcolor)] focus:border-[var(--mcolor)] resize-none"
                        placeholder="Enter your feedback here..."
                    ></textarea>
                    <p class="mt-1 text-xs text-gray-500">Do not enter personal, confidential, or sensitive information.</p>
                </div>

                <div class="flex justify-end space-x-3">
                    <button 
                        @click="$emit('cancel')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="$emit('submit')"
                        :disabled="!isFeedbackValid"
                        class="px-4 py-2 text-sm font-medium text-white border border-transparent rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--mcolor)]"
                        :class="isFeedbackValid ? 'bg-[var(--mcolor)] hover:bg-[var(--mcolor2)]' : 'bg-gray-400 cursor-not-allowed'"
                    >
                        Submit
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
