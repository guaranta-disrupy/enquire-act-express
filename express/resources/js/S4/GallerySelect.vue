<script setup>
import { computed, onMounted, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
const props = defineProps({
    modelValue: {
        default: "",
    },
    showGallery: Function,
    placeholder: {
        type: String,
    }
});

defineEmits(["update:modelValue"]);

const input = ref(null);

</script>
<template>
   <div  class="flex items-center border-[1px] rounded-md border-gray-300 overflow-hidden focus-within:!border-[var(--mcolor2)] focus-within:ring-1 focus-within:!ring-[var(--mcolor)]">
    <input :placeholder="props.placeholder"
        type="text"
        class="w-full h-[40px] py-2 px-3 text-sm !bg-gray-100 border-0 focus:!border-0 focus:!ring-[0px]"
        :class="colors"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        ref="input" 
    />
    <!-- Add gallery button -->
    <div
        class="w-22 py-2.5 px-2.5 h-full text-sm text-gray-700 bg-gray-100 border-l-[2px] border-gray-300 focus:ring-1 focus:ring-blue-500 focus:outline-none"
        @click="props.showGallery"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5 group-hover:scale-125 hover:ease-in duration-300 hover:scale-125 cursor-pointer"
            fill="none"
            viewBox="0 0 24 24"
            stroke="#e28733"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
        </svg>
    </div>
</div>

</template>

<style scoped>
input[disabled] {
    background-color: #759fd180;
}

input {
    resize: both;
}
</style>
