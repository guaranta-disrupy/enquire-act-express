<script setup>
import { computed, ref } from 'vue'
const props = defineProps({
    modelValue: {
        default: ''
    },
    color: {
        default: 'ssrGray'
    },
    lists: {
        default: []
    },
    width_size: {
        default: ''
    },
    label: {
        default: ''
    },
    theme: {
        default: 'theme2'
    },
})
const emits = defineEmits(['update:modelValue'])

const colors = computed(() => {
    return {
        blue: "border-blue-500 focus:border-blue-600",
        gray: "border-gray-400 focus:border-gray-500",
        yellow: "border-yellow-400 focus:border-yellow-500",
        orange: "border-orange-500 focus:border-orange-600",
        red: "border-red-400 focus:border-red-500",
        ssrGray: "border-gray-200 !bg-gray-100 focus:!border-[--var(--mcolor)]",
    }[props.color]
})

const focus = () => {
    $refs.input.focus()
}



</script>


<template>
    <div class="relative z-0 w-full group " v-if="theme == 'theme2'">
        <select
            class="!border-gray-300 !bg-gray-100 focus:!border-[var(--mcolor2)] focus:ring-1 focus:!ring-[var(--mcolor)] block py-2.5 w-full text-sm text-slate-600 bg-transparent border-[1.9px] appearance-none rounded-md px-[15px] border-slate-300 focus:border-slate-200 focus:ring-1 focus:ring-slate-400"
            :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" ref="input">
            <option class="text-sm text-slate-500 hover:bg-slate-100" :value="list.value" :disabled="list.disabled"
                v-for="(list, index) in lists" :key="index" v-html="list.text"></option>
        </select>
        <label for="floating_email"
            class="peer-focus:font-medium absolute text-sm text-slate-400 duration-300 transform -translate-y-6 scale-75 top-1 origin-[0] peer-focus:left-0 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-85 peer-focus:-translate-y-6 ml-0 px-0 peer-placeholder-shown:-z-10 peer-focus:z-20 rounded-md bg-white/0">{{ label }}</label>
    </div>

    <select v-else-if="theme == 'theme3'"
        class="block py-1 min-w-[90px] w-full text-xs text-slate-600 bg-transparent border-[1.9px] appearance-none rounded-md px-[15px] pr-7 border-slate-300 focus:border-slate-200 focus:ring-1 focus:ring-slate-400"
        :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" ref="input">
        <option class="text-sm text-slate-500 hover:bg-slate-100" :value="list.value" :disabled="list.disabled"
            v-for="(list, index) in lists" :key="index" v-html="list.text"></option>
    </select>

    <select v-else-if="theme == 'theme4'"
        class="block py-1 w-full text-xs text-slate-600 bg-transparent border-[1.9px] appearance-none rounded-md px-[15px] pr-7 border-slate-300 focus:border-slate-200 focus:ring-1 focus:ring-slate-400"
        :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" ref="input">
        <option class="text-sm text-slate-500 hover:bg-slate-100" :value="list.value" :disabled="list.disabled"
            v-for="(list, index) in lists" :key="index" v-html="list.text"></option>
    </select>

    <select v-else
        class="truncate form-select cursor-pointer rounded-md border-1 placeholder-gray-300 disabled:cursor-not-allowed border-slate-300 text-sm focus:border-slate-200 focus:ring-0 focus:ring-slate-400 text-slate-400"
        :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" ref="input"
        :class="[colors, width_size]">
        <option :value="list.value" :disabled="list.disabled" v-for="(list, index) in lists" :key="index" v-html="list.text">
        </option>
    </select>
</template>


<style scoped>

</style>