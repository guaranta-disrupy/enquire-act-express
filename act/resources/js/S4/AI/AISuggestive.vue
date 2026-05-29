<script setup>
import { computed } from 'vue';

const SUGGESTION_TEXTS = [
    'Create a survey based from the uploaded script.',
    'Duplicate Questions'
];

const props = defineProps({
    suggestionIndexes: {
        type: Array,
        default: () => []
    },
    dynamicSuggestions: {
        type: Array,
        default: () => []
    },
    isAnalyzing: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['select']);

const visibleSuggestions = computed(() => {
    if (props.dynamicSuggestions && props.dynamicSuggestions.length > 0) {
        return props.dynamicSuggestions;
    }
    return (props.suggestionIndexes || [])
        .map((index) => SUGGESTION_TEXTS[index])
        .filter((text) => typeof text === 'string' && text.length);
});

const handleSelect = (suggestion) => {
    if (!suggestion) return;
    emit('select', suggestion);
};
</script>

<template>
    <div
        :class="[
            'flex flex-col w-full',
            isAnalyzing ? 'items-center text-center' : 'items-start'
        ]"
    >
        <template v-if="isAnalyzing === true">
            <div
                class="inline-flex w-full max-w-full flex-col items-center justify-center gap-3 px-4 py-3 text-xs text-[var(--yellow-200)]"
            >
                <span class="Buttons" aria-hidden="true"></span>
                <span class="font-md leading-snug">
                    Analyzing File Please Wait
                </span>
            </div>
        </template>
        <template v-else>
            <button
                v-for="(suggestion, index) in visibleSuggestions"
                :key="index"
                type="button"
                class="inline-flex w-auto max-w-full self-start items-center gap-2 rounded-3xl border border-[var(--yellow-200)] px-4 py-3 text-left text-xs text-gray-800 shadow-sm transition hover:bg-[#EEE3D3] hover:text-[var(--yellow-200)] hover:text-bold"
                @click="handleSelect(suggestion)"
            >
                <span class="flex-nowrap font-md">
                    {{ suggestion }}
                </span>
            </button>
        </template>
    </div>
</template>

<style>
.Buttons {
  color: #000;
  width: 3px;
    aspect-ratio: 1;
    margin-right: 0;
    margin-bottom: 4px;
  border-radius: 50%;
  animation: l37-1 0.75s infinite linear alternate, l37-2 1.5s infinite linear;
  color: var(--mcolor);
}
@keyframes l37-1 {
  0%,
  20% {
    box-shadow: 15px 0 0 1px, 5px 0 0 1px, -5px 0 0 1px, -15px 0 0 1px;
  }
  60%,
  100% {
    box-shadow: 6px 0 0 1px, 7px 0 0 2px, -7px 0 0 2px, -6px 0 0 1px;
  }
}

@keyframes l37-2 {
  0%,
  25% {
    transform: rotate(0);
  }
  50%,
  100% {
    transform: rotate(0.5turn);
  }
}
</style>