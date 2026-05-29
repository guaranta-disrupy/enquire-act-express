<script setup>
import { transform } from 'lodash';
import { computed } from 'vue'

const props = defineProps({
    modelValue: Number,
    color: { default: 'ssrGray' },
    label: { default: '' },
    size: { default: 'medium' },
    disabled: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue'])

// Computed property to sync `modelValue` with local state
const checked = computed({
    get: () => props.modelValue === 1,
    set: (value) => {
        if (!props.disabled) {
            emit('update:modelValue', value ? 1 : 0) // Emit 1 or 0
        }
    }
});

const switchSize = computed(() => {
    switch (props.size) {
        case 'small':
            return { width: '30px', height: '16px', sliderSize: '12px', sliderTranslate: '11px', bottom: '2px', initialTranslate: '-1px' };
        case 'large':
            return { width: '70px', height: '30px', sliderSize: '30px', sliderTranslate: '38px', bottom: '0px', initialTranslate: '-6px' };
        default:
            return { width: '40px', height: '20px', sliderSize: '16px', sliderTranslate: '17px', bottom: '2px', initialTranslate: '-1px' };
    }
});
</script>

<template>
    <div class="relative z-0 w-full group">
        <label class="block text-sm text-slate-400 mb-1 transform -translate-y-0 scale-70 origin-[0]">{{ label
            }}</label>
        <label class="switch" :style="{ width: switchSize.width, height: switchSize.height }" :class="{ 'cursor-not-allowed': disabled, 'cursor-pointer': !disabled }">
            <input type="checkbox" v-model="checked" :disabled="disabled" class="absolute w-full h-full" />
            <span class="slider round"
                :style="{ height: switchSize.height, width: switchSize.width, '--slider-size': switchSize.sliderSize, '--slider-translate': switchSize.sliderTranslate, '--bottom': switchSize.bottom, '--initial-translate': switchSize.initialTranslate, '--slider-color': switchSize.sliderColor }"
                :class="{ 'opacity-50': disabled, 'cursor-not-allowed': disabled, 'cursor-pointer': !disabled }"></span>
        </label>
    </div>
</template>

<style scoped>
.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;

}

.switch input {
    opacity: 0;
    width: 50px;
    height: 24px;
    position: absolute;
}

.switch.cursor-pointer input {
    cursor: pointer;
}

.switch.cursor-not-allowed input {
    cursor: not-allowed;
}

.slider {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #e2e2e2;
    transition: background-color 0.2s ease-in-out;
}

.slider:before {
    position: absolute;
    content: "";
    height: var(--slider-size);
    width: var(--slider-size);
    left: 4px;
    bottom: var(--bottom);
    background-color: #8f8d8d;
    transition: transform 0.5s ease-in-out;
    will-change: transform;
    transform: translateX(var(--initial-translate)) scale(1.1);

}

input:checked+.slider {
    /* background-color: var(--mcolor); */
    background-color: #f8e2c0;
    box-shadow: 0px 0px 0px 1px rgba(0, 45, 2, 0.1), 5px 2px 4px 0px rgba(0, 0, 0, 0.06);
}

input:focus+.slider {
    box-shadow: 0 0 1px var(--mcolor);
    box-shadow: 0px 0px 0px 1px rgba(0, 45, 2, 0.1), 5px 2px 4px 0px rgba(0, 0, 0, 0.06);
}

input:checked+.slider:before {
    transform: translateX(var(--slider-translate)) scale(1.1);
    /* background-color: #fdbe0f; */
     background-color: var(--mcolor);
}

.slider.round {
    border-radius: 34px;
}

.slider.round:before {
    border-radius: 50%;
}
</style>
