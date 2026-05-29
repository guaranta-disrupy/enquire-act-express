<template>
    <button :type="type" :class="getClass" class="inline-flex items-center bg-sky-300 border border-gray-300 rounded-md font-medium text-gray-800 uppercase tracking-wide shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring focus:ring-blue-200 active:text-gray-800 active:bg-gray-50 disabled:opacity-25 transition">
        <slot />
    </button>

</template>

<script setup>
import { computed } from 'vue';
const props = defineProps({
    type: {
        type: String,
        default: 'button',
    },
    size: {
        type: String,
        default: 'md'
    }
});

const getClass = computed(() => {
    let size;
    switch (props.size) {
        case 'md':
            size = 'py-2 px-4 text-sm ';
            break
        case 'sm':
            size = 'py-1 px-2 text-xs ';
            break
    }
    return size;
});
</script>
