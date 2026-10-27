<script setup>
defineProps({
    type: {
        type: String,
        default: "button",
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex justify-center items-center px-4 py-2 bg-white border border-gray-300 rounded-full font-medium text-sm text-gray-700 tracking-wide shadow-sm hover:text-gray-500 focus:outline-none focus:border-[#257556] active:text-gray-800 active:bg-gray-50 disabled:opacity-25 transition"
    >
        <slot />
    </button>
</template>
