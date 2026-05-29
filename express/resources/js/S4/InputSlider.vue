<script setup>
import { computed, onMounted, ref } from "vue";
const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: "",
    },
    color: {
        type: String,
        default: "blue",
    },
    placeholder: {
        type: String,
        default: "Enter value",
    },
    min: {
        type: Number,
        default: null,
    },
    max: {
        type: Number,
        default: null,
    },
    units: {
        type: Array,
        default: () => ['%', 'vmin', 'vw', 'vh', 'px', 'auto'],
    },
    unitValue: {
        type: String,
        default: "%",
    },
    allowAuto: {
      type: Boolean,
      default: false
    },
});

defineEmits(["update:modelValue", "update:unitValue"]);

const input = ref(null);

const _fn = {
    onUpdate : ()=>{
        
    }
}

// onMounted(() => {
//     if (input.value.hasAttribute("autofocus")) {
//         input.value.focus();
//     }
// });

const colors = computed(() => {
    return {
        blue: "border-blue-500 focus:border-blue-600",
        gray: "border-gray-400 focus:border-blue-500",
        yellow: "border-yellow-400 focus:border-yellow-500",
        orange: "border-orange-500 focus:border-orange-600",
        red: "border-red-400 focus:border-red-500",
        ssrGray:
            "border-gray-300 focus:border-ssr-blue3 focus:ring-1 focus:ring-ssr-blue3",
        ssrGray2:
            "border-gray-300 focus:border-ssr-blue3 focus:ring-1 focus:ring-ssr-blue3 placeholder-ssr-blue3",
        ssrOrange:
            "border-ssr-orange2 focus:border-ssr-orange2 focus:ring-1 focus:ring-ssr-orange1",
        themeInput:
            "border-none focus:border-ssr-blue3 focus:ring-0 focus:ring-ssr-blue3 bg-transparent",
    }[props.color];
});

defineExpose({ focus: () => input.value.focus() });
</script>
<template>
    <div class="flex items-center border-1 border-gray-300  focus-within:!border-1 focus-within:ring-2 focus-within:!ring-[var(--mcolor)] rounded-md overflow-hidden">
        <input  v-if="unitValue !== 'auto'" type="number" :placeholder="placeholder"
            class="w-full py-[10px] px-4 text-sm text-gray-700 !border-1 rounded-[5px_0px_0px_5px] !border-gray-300 !bg-gray-100"
            :value="props.modelValue" :min="min" :max="max" @input="$emit('update:modelValue', $event.target.value)"
            ref="input" />
        <select
            class="rounded-[0px_5px_5px_0px] w-24 py-[10px] px-3 text-sm text-gray-700 bg-gray-100 font-bold border-gray-300 border-[0px_5px_5px_10px] focus:!bg-gray-200 focus:!border-[var(--mcolor)] focus:ring-[0px] focus:outline-none"
            :class="{
                'w-full rounded-[5px_5px_5px_5px]':(unitValue == 'auto')
            }"
            :value="unitValue" @change="$emit('update:unitValue', $event.target.value)">
            <option v-for="unit in units" :key="unit" :value="unit">
                {{ unit }}
            </option>
        </select>
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
