<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue";

const obj = ref({
    input : null,
    unit : null
})

const selectWrapper = ref(null);
const measureSpan = ref(null);
const selectWidth = ref(null);

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

function updateSelectWidth() {
    if (obj.value.unit === "auto") {
        selectWidth.value = null;
        return;
    }
    nextTick(() => {
        if (!measureSpan.value) return;
        const paddingLeft = 8;   // pl-2
        const paddingRight = 30; // pr-7 (dropdown arrow space)
        const w = measureSpan.value.offsetWidth + paddingLeft + paddingRight;
        selectWidth.value = Math.max(w, 48);
    });
}

onMounted(()=>{
    obj.value.input = parseFloat(props.modelValue);
    obj.value.unit = _fn.getUnit(props.modelValue);
    updateSelectWidth();
})

watch(()=>props.modelValue, (value)=>{
    obj.value.input = parseFloat(value);
    obj.value.unit = _fn.getUnit(value);
    updateSelectWidth();
})

watch(() => obj.value.unit, () => {
    updateSelectWidth();
});


const $emit = defineEmits(["update:modelValue", "update:unitValue"]);

//const input = ref(null);

const _fn = {
    onUpdate : (u = null)=>{

        if(u){
            obj.value.unit = u
        }


        let res = obj.value.input + obj.value.unit;

        if(obj.value.unit == "auto"){
            res = "auto";
        }else{
            if(!obj.value.input){
                res = obj.value.unit;
            }
        }
        $emit('update:modelValue', res)
    },
    getUnit : (value)=>{
        try{
            const match = value.match(/[a-zA-Z%]+/); // Regular expression to match the unit (letters or %)
            return match ? match[0] : null;
        }catch{
            
            return ''
        }        
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

watch(
  () => [obj.value.input, obj.value.unit],
  ([input, unit]) => {
    if (unit === '%' && input > 100) {
      obj.value.input = 100;
      _fn.onUpdate();
    }
  }
);
</script>
<template>
    <div class="flex items-center border-1 border-gray-300  focus-within:!border-1 focus-within:ring-2 focus-within:!ring-[var(--mcolor)] rounded-md overflow-hidden">
        <input  v-if="obj.unit !== 'auto'" type="number" :placeholder="placeholder"
            class="w-full text-sm text-gray-700 !border-1 rounded-[5px_0px_0px_5px] !border-gray-300 !bg-gray-100"
            v-model="obj.input" :min="min" :max="obj.unit === '%' ? 100 : max" @input="_fn.onUpdate()"
            ref="input" />
        <div
            ref="selectWrapper"
            class="relative flex-shrink-0"
            :class="{ 'w-full': obj.unit === 'auto' }"
            :style="obj.unit !== 'auto' && selectWidth ? { width: selectWidth + 'px' } : undefined">
            <span
                ref="measureSpan"
                class="invisible absolute whitespace-nowrap text-sm font-bold text-gray-700 pointer-events-none"
                aria-hidden="true">{{ obj.unit }}</span>
            <select
                class="rounded-[0px_5px_5px_0px] w-full min-w-0 pl-2 pr-7 text-sm text-gray-700 bg-gray-100 font-bold border-gray-300 border-[0px_5px_5px_10px] focus:!bg-gray-200 focus:!border-[var(--mcolor)] focus:ring-[0px] focus:outline-none"
                :class="{
                    'rounded-[5px_5px_5px_5px]': (obj.unit === 'auto')
                }"
                :value="obj.unit" @change="_fn.onUpdate($event.target.value)">
                <option v-for="unit in units" :key="unit" :value="unit">
                    {{ unit }}
                </option>
            </select>
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
