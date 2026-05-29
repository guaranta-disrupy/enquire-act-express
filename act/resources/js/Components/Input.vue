<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    modelValue: [String, Number],
    label: String,
    type: {
        type: String,
        default: 'text',
        required: false
    },
    size: {
        type: String,
        default: 'md',
        required: false
    },
    disabled: {
        type: Boolean,
        default: false,
        required: false
    }
});

const emit = defineEmits(['update:modelValue', 'blur', 'mouseleave']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

const updateValue = (event) => {
    emit('update:modelValue', event.target.value);
};

defineExpose({ focus: () => input.value.focus() });

const focusToInput = () => {
    input.value.focus();
};

const get_class = () => {
    let _class = '';
    if (props.size == 'md') {
        _class = {
            input: 'pb-2 pt-3 text-sm',
            label: 'text-base'
        };
    } else if (props.size == 'sm') {
        _class = {
            input: 'pb-1 pt-1 text-xs',
            label: 'text-base'
        };
    }
    if (props.disabled) {
        _class.label = _class.label + ' transparent';
    }
    return _class;

};
</script>

<template>
    <div class="relative mt-3">
        <input
        ref="input"
        :type="type"
        :class="get_class().input"
        class="block  w-full text-gray-900 bg-transparent rounded-lg border-1 border-gray-300 appearance-none focus:outline-none focus:ring-0 focus:border-[#15919a] peer"
        placeholder=" "
        :value="modelValue"
        @input="updateValue"
        @blur="emit('blur', $event)"
        @mouseleave="emit('mouseleave', $event)"
        :disabled="disabled" />
        <label @click="focusToInput" :class="get_class().label" class="absolute cursor-text font-bold text-gray-500 duration-300 transform -translate-y-5 scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-focus:text-[#15919a] peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-5 left-1 z-0">
            {{ label }}
        </label>
    </div>
</template>
