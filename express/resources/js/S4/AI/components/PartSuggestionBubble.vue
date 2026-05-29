<script setup>
const props = defineProps({
    content: { type: String, default: '' },
    parts: { type: Array, default: () => [] },
    timestamp: { type: String, default: '' },
    selectedPart: { type: [String, Number, Object], default: null },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['select', 'cancel']);

const getPartDisplayLabel = (partLabel) => {
    if (!partLabel) return '';
    return String(partLabel).replace(/^Part\s*/i, '').trim() || partLabel;
};

const handleSelect = (part) => {
    if (props.disabled || props.loading) return;
    emit('select', part);
};
</script>

<template>
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
            v-if="loading && selectedPart"
            class="mt-2 flex items-center gap-2"
        >
            <div class="flex-1 flex items-center gap-2 text-xs text-gray-600">
                <span class="inline-block w-2 h-2 bg-[var(--mcolor)] rounded-full animate-pulse"></span>
                <span>Extracting questions from {{ getPartDisplayLabel(selectedPart) }}...</span>
            </div>
            <button
                type="button"
                @click="emit('cancel')"
                class="px-3 py-1.5 text-xs font-semibold text-red-600 border border-red-300 rounded-lg hover:bg-red-50 transition-colors"
            >
                Cancel
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
