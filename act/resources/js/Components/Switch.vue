<template>
    <label class="inline-flex relative items-center mb-2 cursor-pointer">
        <input
            type="checkbox"
            class="sr-only peer"
            :value="props.value"
            v-model="proxyChecked"
            :checked="proxyChecked === null ? true : undefined"
        >
        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
        <span class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
            <slot />
        </span>
    </label>
</template>

<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type:  Boolean,
        default: true,
    },
    value: {
        type: String,
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>
