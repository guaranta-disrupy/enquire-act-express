<template>
	<button :type="type" class="inline-flex items-center px-2 py-1 bg-[#257556] border border-gray-300 rounded-md font-medium text-xs text-white uppercase tracking-wide shadow-sm hover:bg-[#1d5e45] hover:text-white focus:outline-none focus:border-blue-300 focus:ring focus:ring-blue-200 active:text-gray-800 active:bg-gray-50 disabled:opacity-25 transition" :class="class">
        <slot></slot>
    </button>
</template>

<script>
    import { defineComponent } from "vue";
    export default defineComponent({
        props: {
            type: {
                type: String,
                default: 'button',
            },
            class: {
                type: String,
                default: ''
            }
        }
    })
</script>