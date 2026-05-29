
<template>
    <div :class="['w-full grid grid-cols-[1fr_60px] bg-[#f3f4f6] border-[1.9px] border-gray-300 rounded-md overflow-hidden border-1',
    (isActive || gallery.show) ? '!border-[var(--mcolor2)]  !ring-1 !ring-[var(--mcolor2)]' : '']">
        <div>
        <s4-input color="ssrGray"
            ref="inputElem"
            class="!ring-[0] hover:!ring-[0] shadow-[0] !border-[0]"
            :placeholder="$Props.placeholder" 
            v-model="content"
            @input="selectGallery(false)"
            @focus="handleFocus"
            @blur="handleBlur"
            >
        </s4-input>    
         </div>     
         <div class="border-l-[1.9px] border-gray-300 cursor-pointer"
            @mousedown.stop @click="showGallery"
            @focus="handleFocus"
         >
            <div v-if="(!content)">
                <ButtonIconsVue class="!bg-[transparent] w-[60px] h-[40px] ring-0 shadow-[0] border-[0] rounded-[0] hover:border-[0] hover:shadow-[0] hover:ring-0"
                icon="gallery"/>
            </div>
            <img v-show="content && (!isBroken)" :src="content" class="w-full h-[40px] object-contain" 
                @error="handleImageError" 
                @load="handleImageLoad"
            />
            <div v-show="content && isBroken" class="flex h-[100%] align-middle items-center justify-center">
                <svg  xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="24px" fill="red"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm40-337 160-160 160 160 160-160 40 40v-183H200v263l40 40Zm-40 257h560v-264l-40-40-160 160-160-160-160 160-40-40v184Zm0 0v-264 80-376 560Z"/></svg>
            </div>
         </div>   
         <s4-gallery-modal :type="'image'" :modal="gallery" @close="gallery.show = false" @selectGallery="($event)=>{selectGallery($event)}" />
    </div>
    
</template>

<script setup>
import {
    computed,
    nextTick,
    reactive,
    watch,
    inject,
    ref,
    defineExpose,
    onBeforeMount,
    onMounted
} from "vue";
import S4Input from "@/S4/Input.vue";
import ButtonIconsVue from "@/S4/ButtonIcons.vue";
import S4GalleryModal from "@/S4/modals/Gallery.vue";
import { usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js';
const { ImageWithHeaders } = helpers();
const $Props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    placeholder: {
        type: String,
        default: "Image source",
    }
});
const isActive = ref(false);
const isBroken = ref(false);

watch(
    () => $Props.modelValue,
    (newValue) => {
        content.value = $Props.modelValue;
    },
    { deep : true }
)
const inputElem = ref(null);
const content = ref($Props.modelValue);
const emit = defineEmits(["update:modelValue"]);

const gallery = reactive({
    show: false,
    index: -1,
});

const handleFocus = () => {
    isActive.value = true;
};

const handleBlur = () => {
    isActive.value = false;
};

const showGallery = (index = -1) => {
    gallery.show = true;
};

const handleImageError = () => {
    isBroken.value = true
}

const handleImageLoad = () => {
    isBroken.value = false
}

const selectGallery = (el) => {
    if (!el) {
        emit("update:modelValue", content.value);
    } else {
        const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;
        const imageUrl = el.file_path
            ? `${fluentBase}/storage/${el.file_path}`
            : ImageWithHeaders(usePage().props.app_url + '/gallery/' + (el.filename || el.file_name));
        content.value = imageUrl;
        gallery.show = false;
        emit("update:modelValue", imageUrl);
    }
   
};

defineExpose({
    focus : ()=>{
        inputElem.value.focus();
    }
})

</script>