<template>
    <div class="relative inline-block" :set="SID='SW-'+Math.floor(1000000000 + Math.random() * 9000000000)">
    <label :for="SID" class="flex relative gap-2 items-center cursor-pointer text-sm">
        <!-- toggle -->
        <div class="relative">
            <!-- input -->
            <input :id="SID" type="checkbox" v-model="checkbox" class="sr-only" />
            <!-- line -->
            <div class="line w-10 h-5 bg-gray-300 rounded-full shadow-inner"></div>
            <!-- dot -->
            <div class="dot absolute w-4 h-4 bg-white rounded-full shadow-md left-0.5 top-0.5 transition"></div>
        </div>
        <!-- label -->
        <div class="text-gray-600" v-html="props.label" v-if="props.label"></div>
    </label>
    </div>
</template>
<script setup>
import { ref, watch } from 'vue'
const checkbox = ref(null)
const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    label : {
        type: String,
        default: null
    }
})

const emit = defineEmits(['update:modelValue'])

watch(() => props.modelValue, (newVal) => {
    checkbox.value = newVal
})

watch(checkbox, (newVal) => {
    emit('update:modelValue', newVal)
})


</script>

<style scoped>
input[type="checkbox"].sr-only {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border-width: 0 !important;
    display: none !important;
}

input:checked~.dot {
    transform: translateX(20px); /* 36px - 16px (dot width) = 20px */
    /* background-color: var(--mcolor2); */
}

input:checked~.line {
    background-color: var(--mcolor2);
}
</style>
