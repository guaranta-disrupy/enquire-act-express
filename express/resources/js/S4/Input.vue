<script setup>
import { computed, onMounted, ref } from "vue";
const props = defineProps({
    modelValue: {
        default: "",
    },
    color: {
        default: "blue",
    },
    label: {
        default: "Label",
    },
    theme: {
        default: "theme1",
    },
    borderColor: {
        default: "bg-white",
    },
});

defineEmits(["update:modelValue"]);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute("autofocus")) {
        input.value.focus();
    }
});

const colors = computed(() => {
    return {
        blue: "border-blue-500 focus:border-blue-600",
        gray: "border-gray-400 focus:border-blue-500",
        yellow: "border-yellow-400 focus:border-yellow-500",
        orange: "border-orange-500 focus:border-orange-600",
        red: "border-red-400 focus:border-red-500",
        ssrGray:
            "!border-gray-300 !bg-gray-100 focus:!border-[var(--mcolor2)] focus:ring-1 focus:!ring-[var(--mcolor)]",
        ssrGray2:
            "border-gray-300 focus:border-ssr-blue3 focus:ring-1 focus:ring-ssr-blue3 placeholder-ssr-blue3",
        ssrOrange:
            "border-ssr-orange2 focus:border-ssr-orange2 focus:ring-1 focus:ring-ssr-orange1",
        themeInput:
            "border-none focus:border-ssr-blue3 focus:ring-0 focus:ring-ssr-blue3 bg-transparent"
    }[props.color];
});

defineExpose({ focus: () => input.value.focus() });
</script>
<template>
    <div
        class="relative z-0 w-full mt-6 group border-none"
        v-if="theme == 'theme2'"
    >
        <input
            type="text"
            name="text"
            class="block py-2.5 px-0 w-full text-sm text-slate-600 bg-transparent border-[1.9px] appearance-none rounded-md px-[15px] border-slate-300 focus:border-slate-200 focus:ring-1 focus:ring-slate-400"
            placeholder=""
            :class="colors"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            ref="input"
        />

        <label
            for="text"
            class="peer-focus:font-medium absolute text-sm text-slate-400 duration-300 transform -translate-y-6 scale-75 top-1 origin-[0] peer-focus:left-0 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-85 peer-focus:-translate-y-6 ml-0 px-0 peer-placeholder-shown:-z-10 peer-focus:z-20 rounded-lg bg-white/0"
            :class="borderColor"
            >{{ label }}</label
        >
    </div>

    <input
        v-else-if="theme == 'theme3'"
        type="text"
        name="text"
        class="block py-1 px-0 w-full text-xs text-slate-600 bg-transparent border-[1.9px] appearance-none rounded-md px-[15px] border-slate-300 focus:border-slate-200 focus:ring-1 focus:ring-slate-400"
        placeholder=""
        :class="colors"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        ref="input"
    />

    <input
        v-else
        type="text"
        class="block py-2.5 px-0 w-full text-sm bg-transparent border-[1.9px] appearance-none rounded-md px-[15px] border-slate-300 focus:border-slate-200 focus:ring-1 focus:ring-slate-400"
        :class="colors"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        ref="input"
    />
</template>

<style scoped>
input[disabled] {
    /* background-color: #759fd180!important; */
    opacity:.5!important;
    cursor:not-allowed;
}
input {
    resize: both;
}
</style>
