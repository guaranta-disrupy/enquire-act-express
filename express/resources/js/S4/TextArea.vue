<script setup>
import { computed } from '@vue/runtime-core'
const props = defineProps({
        modelValue: {
            default: ''
        },
        color: {
            default: 'blue'
        },
        filter : {
            default: false
        },
    })
const emits = defineEmits(['update:modelValue'])
        
const colors = computed(() =>  {
        return {
            blue: "border-blue-500 focus:border-blue-600",
            gray: "border-gray-400 focus:border-gray-500",
            yellow: "border-yellow-400 focus:border-yellow-500",
            orange: "border-orange-500 focus:border-orange-600",
            red: "border-red-400 focus:border-red-500",
            ssrGray: "!border-gray-300 !bg-gray-100 focus:!border-[var(--mcolor2)] focus:ring-1 focus:!ring-[var(--mcolor)]",
        }[props.color]
    })
const focus = () => {
        $refs.input.focus()
    }
   

const handlePaste = (event) => {
        if (!props.filter) {
            return;
        }

        event.preventDefault();
        const clipboardData = event.clipboardData || window.clipboardData;
        const pastedData = clipboardData.getData("text");

        const processedData = pastedData
            .split("\n")
            .map((line) => line.trim())
            .filter((line) => line !== "")
            .join("\n");

        // Append to existing content instead of replacing it,
        // so multiple paste actions accumulate as multiple lines.
        const current = (props.modelValue || "").toString();
        const needsNewline =
            current.length > 0 && !current.endsWith("\n") ? "\n" : "";

        const nextValue = current + needsNewline + processedData;

        emits("update:modelValue", nextValue);
    };

</script>

<template>
    <textarea 
    :class="colors" 
    class="text-black rounded-md border-1 focus:ring-1  placeholder-[#757575] placeholder:italic placeholder:font-sans-serif focus:ring-ssr-blue3 text-sm" 
    :value="modelValue" 
    @input="$emit('update:modelValue', $event.target.value)" 
    @paste="handlePaste" 
    ref="input"></textarea>
</template>


