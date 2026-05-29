<script setup>

import { computed } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'submit'
    },
    class: {
        type: String,
        default: ''
    },
    size: {
        type: String,
        default: 'sm'
    }
});

const getClasses = computed(() => {
    let size;
    switch (props.size) {
        case 'md':
            size = 'py-2 px-4 ';
            break;
        case 'sm':
            size = 'py-1 px-2 ';
            break;
    }
    return size + props.class + ' ' + props.variant;
    });
</script>

<template>
	<button :type="props.type" :class="getClasses" class="uppercase inline-flex items-center bg-gray-800 border border-transparent rounded-md font-medium text-xs text-white tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
        <slot></slot>
    </button>
</template>