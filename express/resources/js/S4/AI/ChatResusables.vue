<script>
import { defineComponent } from 'vue';

const getPartDisplayLabel = (partLabel) => {
    if (!partLabel) return '';
    return String(partLabel).replace(/^Part\s*/i, '').trim() || partLabel;
};

export const PartSuggestionBubble = defineComponent({
    name: 'PartSuggestionBubble',
    props: {
        content: { type: String, default: '' },
        parts: { type: Array, default: () => [] },
        timestamp: { type: String, default: '' },
        selectedPart: { type: [String, Number, Object], default: null },
        disabled: { type: Boolean, default: false },
        loading: { type: Boolean, default: false },
    },
    emits: ['select'],
    setup(props, { emit }) {
        const handleSelect = (part) => {
            if (props.disabled || props.loading) return;
            emit('select', part);
        };
        return { handleSelect, getPartDisplayLabel };
    },
    template: `
        <div class="chat-bubble bubble-left bg-white border border-gray-200 shadow-sm flex flex-col gap-3 w-full max-w-full overflow-hidden">
            <p class="text-sm font-medium text-gray-800" v-html="content"></p>
            <p class="text-xs text-gray-500 mt-2">Part:</p>
            <div class="mt-3 flex flex-wrap gap-2 w-full max-w-full">
                <button
                    v-for="part in parts"
                    :key="part"
                    type="button"
                    @click="handleSelect(part)"
                    :disabled="loading || disabled"
                    class="w-10 h-10 flex items-center justify-center rounded-lg border-2 border-[var(--mcolor)] text-[var(--mcolor)] font-semibold bg-white transition hover:bg-[var(--mcolor)] hover:text-white disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="selectedPart === part ? 'bg-[#EE920C] text-white' : ''"
                >
                    {{ getPartDisplayLabel(part) }}
                </button>
            </div>
            <div
                v-if="timestamp"
                class="text-[10px] text-gray-400 text-left"
            >
                {{ timestamp }}
            </div>
        </div>
    `,
});

export const ConfirmationBubble = defineComponent({
    name: 'ConfirmationBubble',
    props: {
        actionData: { type: Object, required: true },
        stripHtml: { type: Function, required: true },
        truncateText: { type: Function, required: true },
        loading: { type: Boolean, default: false },
    },
    emits: ['confirm', 'cancel'],
    setup() {
        return {};
    },
    template: `
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
                            <h4 class="text-sm font-bold text-gray-800 mb-3">
                                Survey Preview ({{ actionData.total_questions || actionData.survey_data.questions.length }} questions)
                            </h4>
                            <div v-for="(question, index) in actionData.survey_data.questions" 
                                 :key="index"
                                 class="mb-3 pb-3 border-b border-gray-200 last:border-0">
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
                        
                        <div class="flex gap-3 mt-4">
                            <button @click="$emit('confirm')" 
                                    class="flex-1 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white font-medium py-2.5 px-4 rounded-lg transition-colors duration-200 text-sm">
                                {{ actionData.survey_data ? 'Yes, Create All Questions' : 'Yes' }}
                            </button>
                            <button @click="$emit('cancel')" 
                                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2.5 px-4 rounded-lg transition-colors duration-200 text-sm">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
});

export const FeedbackDialog = defineComponent({
    name: 'FeedbackDialog',
    props: {
        show: { type: Boolean, default: false },
        feedbackReasons: { type: Array, default: () => [] },
        feedbackText: { type: String, default: '' },
        isFeedbackValid: { type: Boolean, default: false },
    },
    emits: ['toggle-reason', 'cancel', 'submit', 'update:feedbackText'],
    setup(props, { emit }) {
        const isChecked = (reason) => props.feedbackReasons.includes(reason);
        const onTextInput = (event) => {
            emit('update:feedbackText', event.target.value);
        };
        return { isChecked, onTextInput };
    },
    template: `
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
    `,
});

export const PreparingDialog = defineComponent({
    name: 'PreparingDialog',
    props: {
        isSurvey: { type: Boolean, default: false },
        progress: { type: Object, default: () => ({ stage: '', message: '', current: 0, total: 0 }) }
    },
    setup() {
        return {};
    },
    template: `
        <div class="bubble-speech bubble-left">
            <div class="chat-bubble bg-white border border-gray-200 shadow-lg">
                <div class="confirmation-content p-4">
                    <div class="text-center py-8">
                        <div class="typing-bubble inline-flex">
                            <span class="dot"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                        </div>
                        <p class="text-sm text-gray-500 mt-4">
                            <span v-if="isSurvey">
                                <span v-if="progress.stage === 'planning'">Thinking...</span>
                                <span v-else-if="progress.stage === 'plan_complete'">{{ progress.message }}</span>
                                <span v-else-if="progress.stage === 'generating'">
                                    <span v-if="progress.current > 0 && progress.total > 0">
                                        Preparing Question {{ progress.current }} of {{ progress.total }}...
                                    </span>
                                    <span v-else>{{ progress.message }}</span>
                                </span>
                                <span v-else-if="progress.stage === 'questions_complete'">{{ progress.message }}</span>
                                <span v-else-if="progress.stage === 'finalizing'">{{ progress.message }}</span>
                                <span v-else-if="progress.stage === 'complete'">{{ progress.message }}</span>
                                <span v-else-if="progress.stage === 'error'">{{ progress.message }}</span>
                                <span v-else>Preparing Survey<br>Please Wait.</span>
                            </span>
                            <span v-else>Preparing Question<br>Please Wait.</span>
                        </p>
                        <div v-if="isSurvey && progress.total > 0" class="mt-4 max-w-md mx-auto">
                            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                <div class="bg-gradient-to-r from-[var(--mcolor)] to-[var(--mcolor2)] h-3 rounded-full transition-all duration-500 ease-out flex items-center justify-end pr-2" 
                                     :style="{ width: Math.min((progress.current / progress.total * 100), 100) + '%' }">
                                </div>
                            </div>
                            <p v-if="progress.total > 0" class="text-xs text-gray-400 mt-2 text-center">
                                <span v-if="progress.current > 0 && progress.current < progress.total">
                                    Question {{ progress.current }} of {{ progress.total }}
                                </span>
                                <span v-else-if="progress.current >= progress.total">
                                    All {{ progress.total }} questions prepared
                                </span>
                                <span v-else>
                                    Preparing {{ progress.total }} questions...
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
});

export const ThankYouMessage = defineComponent({
    name: 'ThankYouMessage',
    setup() {
        return {};
    },
    template: `
        <div class="flex justify-center items-center py-6">
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
    `,
});

// QuestionSuggestionBubble Component
export const QuestionSuggestionBubble = defineComponent({
    name: 'QuestionSuggestionBubble',
    props: {
        content: { type: String, default: '' },
        questions: { type: Array, default: () => [] },
        timestamp: { type: String, default: '' },
        selectedQuestion: { type: [String, Number, Object], default: null },
        disabled: { type: Boolean, default: false },
        loading: { type: Boolean, default: false },
    },
    emits: ['select'],
    setup(props, { emit }) {
        const handleSelect = (question) => {
            if (props.disabled || props.loading) return;
            emit('select', question);
        };
        
        const getQuestionDisplayLabel = (question) => {
            if (!question) return '';
            if (typeof question === 'object' && question.number) {
                return `Q${question.number}`;
            }
            if (typeof question === 'object' && question.id) {
                return `Q${question.id}`;
            }
            return String(question);
        };
        
        const getQuestionTitle = (question) => {
            console.log(question);
            console.log(question.setup);
            if (!question || typeof question !== 'object') return '';

            const setupData = question.setup;

            // setup is already an object coming from backend
            if (setupData && setupData.chartTitle) {
                return setupData.chartTitle;
            }

            return question.title || question.name || '';
        };
        
        const getQuestionType = (question) => {
            if (!question || typeof question !== 'object') return '';
            return question.question_type || 'descriptive';
        };
        
        const truncateText = (text, maxLength = 50) => {
            if (!text || text.length <= maxLength) return text;
            return text.substring(0, maxLength) + '...';
        };
        
        return { handleSelect, getQuestionDisplayLabel, getQuestionTitle, getQuestionType, truncateText };
    },
    template: `
        <div class="chat-bubble bubble-left bg-white border border-gray-200 shadow-sm flex flex-col gap-3 w-full max-w-full overflow-hidden">
            <p class="text-sm font-medium text-gray-800">{{ content }}</p>
            <p class="text-xs text-gray-500 mt-2">Question:</p>
            <div class="mt-3 flex flex-col gap-2 w-full max-w-full">
                <button
                    v-for="question in questions"
                    :key="question.id || question.number"
                    type="button"
                    @click="handleSelect(question)"
                    :disabled="loading || disabled"
                    class="flex flex-col items-start gap-1 px-4 py-3 rounded-lg border-2 border-[var(--mcolor)] text-[var(--mcolor)] font-semibold bg-white transition hover:bg-[var(--mcolor)] hover:text-white disabled:opacity-50 disabled:cursor-not-allowed text-left w-full"
                    :class="selectedQuestion && (selectedQuestion.id === question.id || selectedQuestion === question.id) ? 'bg-[var(--mcolor)] text-white' : ''"
                >
                    <div class="flex items-center gap-2 w-full">
                        <span class="font-bold text-sm">{{ getQuestionDisplayLabel(question) }}</span>
                        <span class="text-xs opacity-75">{{ getQuestionTitle(question) }}</span>
                    </div>
                    <span class="text-xs font-normal truncate w-full" :class="selectedQuestion && (selectedQuestion.id === question.id || selectedQuestion === question.id) ? 'text-white' : 'text-gray-700'">
                        {{ getQuestionType(question) }}
                    </span>
                </button>
            </div>
            <div
                v-if="timestamp"
                class="text-[10px] text-gray-400 text-left"
            >
                {{ timestamp }}
            </div>
        </div>
    `,
});

export const TermsModal = defineComponent({
    name: 'TermsModal',
    props: {
        show: { type: Boolean, default: false },
    },
    emits: ['close'],
    setup() {
        return {};
    },
    template: `
        <div v-if="show" class="absolute inset-0 z-[1100] flex items-center justify-center p-4 overflow-y-auto">
            <div @click="$emit('close')" class="absolute inset-0 bg-black bg-opacity-75 rounded-xl"></div>
            <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all w-full max-w-lg z-10 my-auto max-h-[90%] flex flex-col">
                <div class="px-6 py-6 overflow-y-auto flex-1">
                    <h2 class="text-2xl font-semibold text-center mb-6" style="color: #ee920c;">
                        Chatbot Terms and Conditions
                    </h2>
                    <div class="text-gray-900 space-y-4">
                        <p class="text-sm">
                            By using this chatbot, you acknowledge and agree that your conversations may be recorded, monitored, and reviewed by authorized administrators for the purposes of:
                        </p>
                        <ul class="list-disc list-inside text-sm space-y-2 ml-4">
                            <li>Improving chatbot accuracy and user experience,</li>
                            <li>Ensuring service quality and compliance, and</li>
                            <li>Supporting management and troubleshooting activities.</li>
                        </ul>
                        <p class="text-sm">
                            All data collected will be handled in accordance with our Privacy Policy and will not be shared with unauthorized third parties. By continuing to interact with this chatbot, you consent to this data usage and monitoring.
                        </p>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end flex-shrink-0">
                    <button 
                        @click="$emit('close')"
                        class="px-6 py-2.5 text-sm font-semibold text-white rounded-lg transition-colors hover:opacity-90"
                        style="background-color: #ee920c;"
                    >
                        Acknowledge
                    </button>
                </div>
            </div>
        </div>
    `,
});
</script>