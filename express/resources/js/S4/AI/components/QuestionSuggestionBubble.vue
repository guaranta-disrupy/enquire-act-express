<script setup>
const props = defineProps({
    content: { type: String, default: '' },
    questions: { type: Array, default: () => [] },
    timestamp: { type: String, default: '' },
    selectedQuestion: { type: [String, Number, Object], default: null },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['select']);

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
</script>

<template>
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
</template>
