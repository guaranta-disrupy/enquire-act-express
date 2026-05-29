<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    actionData: { type: Object, required: true },
    stripHtml: { type: Function, required: true },
    truncateText: { type: Function, required: true },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'cancel']);

const removedQuestionIndices = ref(new Set());
const filteredQuestions = computed(() => {
    if (!props.actionData.survey_data?.questions) return [];
    return props.actionData.survey_data.questions.filter((_, index) => !removedQuestionIndices.value.has(index));
});

const visibleQuestionCount = computed(() => {
    return filteredQuestions.value.length;
});

const removeQuestion = (index) => {
    removedQuestionIndices.value.add(index);
};

const handleConfirm = () => {
    if (props.actionData.survey_data?.questions && removedQuestionIndices.value.size > 0) {
        const filteredActionData = {
            ...props.actionData,
            survey_data: {
                ...props.actionData.survey_data,
                questions: filteredQuestions.value
            },
            total_questions: visibleQuestionCount.value
        };
        emit('confirm', filteredActionData);
    } else {
        emit('confirm');
    }
};
</script>

<template>
    <div class="bubble-speech bubble-left">
        <div class="chat-bubble bg-white border border-gray-200 shadow-lg">
            <div class="confirmation-content p-4">
                <div v-if="actionData.loading && actionData.action && (actionData.action.includes('_create') || actionData.action === 'express_survey_create')" class="text-center py-8">
                    <div class="typing-bubble inline-flex">
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                    </div>
                    <p class="text-sm text-gray-500 mt-4">
                        <span v-if="actionData.action === 'express_survey_create'">Preparing Survey<br>Please Wait.</span>
                        <span v-else>Preparing Question<br>Please Wait.</span>
                    </p>
                </div>
                
                <div v-else>
                    <p class="text-gray-800 mb-4 text-sm">{{ actionData.message }}</p>
                    
                    <div v-if="actionData.survey_data && actionData.survey_data.questions" 
                         class="survey-preview bg-white p-4 rounded-lg border-2 border-[#e5e7eb] mb-4 max-h-96 overflow-y-auto">
                        <h4
                            v-if="actionData.action === 'express_survey_create'"
                            class="text-sm font-bold text-gray-800 mb-3"
                        >
                            Survey Preview ({{ visibleQuestionCount }} of {{ actionData.total_questions || actionData.survey_data.questions.length }} questions)
                        </h4>
                        <h4
                            v-else
                            class="text-sm font-bold text-gray-800 mb-3"
                        >
                            Questions Preview ({{ visibleQuestionCount }} of {{ actionData.total_questions || actionData.survey_data.questions.length }} questions)
                        </h4>
                        <div v-for="(question, index) in actionData.survey_data.questions" 
                             :key="index"
                             v-show="!removedQuestionIndices.has(index)"
                             class="mb-3 pb-3 border-b border-gray-200 last:border-0 relative group">
                            <div class="flex items-start gap-2">
                                <span class="text-xs font-semibold text-[var(--mcolor)] mt-1">{{ index + 1 }}.</span>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-700 font-medium mb-1">
                                        {{ stripHtml(question.title_and_question_text?.['question text'] || question.title_and_question_text?.question_text || '') }}
                                    </p>
                                    <span class="text-xs text-gray-500 italic">
                                        Type: {{ question.question_type || 'descriptive' }}
                                    </span>
                                    <div v-if="(question.text_options && question.text_options.length > 0) || (question.choices && question.choices.length > 0)" 
                                         class="mt-1 text-xs text-gray-600">
                                        <span class="font-medium">{{ (question.text_options?.length || question.choices?.length || 0) }} options</span>
                                    </div>
                                </div>
                                <button 
                                    @click="removeQuestion(index)"
                                    class="ml-2 flex-shrink-0 p-1 hover:bg-red-50 rounded-full transition-colors"
                                    title="Remove question">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else-if="actionData.preview_data && actionData.preview_data.title_and_question_text" 
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
                                <input type="radio" class="text-[var(--mcolor)] focus:ring-[var(--mcolor)]" checked disabled>
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
                                <input type="checkbox" class="text-[var(--mcolor)] focus:ring-[var(--mcolor)]" checked disabled>
                                <span class="text-gray-700 truncate">
                                    {{ truncateText(
                                        item.statementA && item.statementB 
                                            ? stripHtml(item.statementA) + ' vs ' + stripHtml(item.statementB)
                                            : (item.text || item.name || item), 
                                        70
                                    ) }}
                                </span>
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
                                <input type="checkbox" class="text-[var(--mcolor)] focus:ring-[var(--mcolor)]" checked disabled>
                                <span class="text-gray-700 truncate">{{ truncateText(value.text || value.value || value.name || value, 70) }}</span>
                            </div>
                            <div v-if="actionData.preview_data.values.length > 6" 
                                 class="text-xs text-gray-400 italic pl-6">
                                ... and {{ actionData.preview_data.values.length - 6 }} more
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-2 mt-4">
                        <button @click="handleConfirm" 
                                class="flex-1 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white font-medium py-1.5 px-3 rounded-lg transition-colors duration-200 text-xs">
                            {{ actionData.survey_data ? 'Yes, Create All Questions' : 'Yes' }}
                        </button>
                        <button @click="$emit('cancel')" 
                                class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-1.5 px-3 rounded-lg transition-colors duration-200 text-xs">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
