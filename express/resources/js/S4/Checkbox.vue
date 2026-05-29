<script setup>
const props = defineProps({
        checked: {
            type: [Number, Boolean],
            default: false,
        }
    })
</script>

<template>
    <input type="checkbox" :true-value="1" :false-value="0"
        @input="(event) => $emit('update:checked', event.target.checked ? 1 : 0)"
        :checked="checked"
        class="rounded border-1 border-gray-300 bg-gray-300 text-ssr-blue2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed focus:border-[var(--mcolor)] checked:!bg-[var(--mcolor)] !accent-[var(--mcolor)] focus:ring-0 focus:ring-offset-0">
</template>


