<script setup>
const props = defineProps({
    content: { type: String, default: 'How many questions would you like to create?' },
    options: { type: Array, default: () => [2, 3, 4, 5] },
    timestamp: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['select']);

const handleSelect = (num) => {
    if (props.disabled || props.loading) return;
    emit('select', num);
};
</script>

<template>
    <div class="chat-bubble bubble-left bg-white border border-gray-200 shadow-sm flex flex-col gap-3 w-full max-w-full overflow-hidden">
        <p class="text-sm font-medium text-gray-800">{{ content }}</p>
        <div class="mt-3 flex flex-wrap gap-2 w-full max-w-full">
            <button
                v-for="num in options"
                :key="num"
                type="button"
                @click="handleSelect(num)"
                :disabled="loading || disabled"
                class="w-10 h-10 flex items-center justify-center rounded-lg border-2 border-[var(--mcolor)] text-[var(--mcolor)] font-semibold bg-white transition hover:bg-[var(--mcolor)] hover:text-white disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ num }}
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
