<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    modelValue: [String, Number],
    label: String,
    size: {
        type: String,
        default: 'md',
        required: false
    }
});

const emit = defineEmits(['update:modelValue']);

const input = ref(null);


const updateValue = (event) => {
    emit('update:modelValue', event.target.value);
};

const get_class = () => {
    let _class = '';
    if (props.size == 'md') {
        _class = {
            select: 'pb-2 pt-3 text-sm',
            label: 'text-sm'
        };
    } else if (props.size == 'sm') {
        _class = {
            select: 'pb-1 pt-1 text-xs',
            label: 'text-xs'
        };
    }
    return _class;

};
</script>

<template>
    <div class="relative mt-5">
        <label :class="get_class().label" class="absolute cursor-text text-gray-500 duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1 z-0">
            {{ label }}
        </label>
        <select :value="modelValue" ref="input" :class="get_class().select" class="block px-2 w-full text-gray-900 bg-transparent rounded-lg border-1 border-gray-300 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer" placeholder=" "
        @input="updateValue">
            <slot />
        </select>
    </div>
</template>
