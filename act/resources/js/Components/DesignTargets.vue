<!-- <script setup>
import { ref } from 'vue';
import RangeSlider from '@/Components/RangeSlider.vue';

    
    const toggleDropdown = (key) => {
  // Close all others
  for (const k in dropdowns.value) {
    if (k !== key) dropdowns.value[k] = false;
  }
  // Toggle selected
  dropdowns.value[key] = !dropdowns.value[key];
};

const selectLayout = (layout, key) => {
  console.log(`Selected ${key}:`, layout);
  dropdowns.value[key] = false;
};

const range = document.getElementById('range');
const label = document.getElementById('range-label');

function updateLabel() {
  const percent =
    ((range.value - range.min) / (range.max - range.min)) * 100;
  label.style.left = `${percent}%`;
  label.textContent = range.value;
}




</script> -->

<script setup>

import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import CKEditor from '@ckeditor/ckeditor5-vue';
// import Font from '@ckeditor/ckeditor5-font/src/font';
import UploadAdapterPlugin from '@/Functions/UploadAdapter';
import { PrismEditor } from 'vue-prism-editor';
import 'vue-prism-editor/dist/prismeditor.min.css'; // import the styles somewhere
// import highlighting library (you can use any library you want just return html string)
import { highlight, languages } from 'prismjs/components/prism-core';
import 'prismjs/components/prism-clike';
import 'prismjs/components/prism-javascript';
import 'prismjs/components/prism-markup';
import 'prismjs/components/prism-css';
import 'prismjs/themes/prism-tomorrow.css'; // import syntax highlighting styles
// Ck Editor end
import RangeSlider from '@/Components/RangeSlider.vue';

import ContainerUpText from '@/Components/ContainerUpText.vue';
import FontSize from '@/Components/FontSize.vue';
import FontWeights from '@/Components/FontWeights.vue';



import CustomInput from '@/Components/Input.vue';

import { inject, ref, watch, provide, onMounted } from 'vue';

import Helpers from '@/Mixins/Helpers';

import { onBeforeMount } from 'vue';
// import FontWeight from './FontWeight.vue';

const { $testState, assign } = Helpers();


const addSegment = () => {

	return $testState.addSegment();
};

const enableCKEditor = inject('ck');
const ckeditor = CKEditor.component;
const editor = ref(ClassicEditor);

const form = inject('form');

provide('testState', $testState);

const editorConfig = ref({
	extraPlugins: [UploadAdapterPlugin],
});

onBeforeMount(() => {
    
	$testState.criteria = form;

});


const highlighter = (code) => {
	return highlight(code, languages.markup);
};

watch(form, (newVal) => {
    if (newVal.config.random_prime === false) {
        form.config.random_prime_subsets = false;
    }
});

const value = ref(50)

const logValue = () => {
  console.log('Slider changed:', value.value)
}

// Optional: also log on every value change
watch(value, (newVal) => {
  console.log('Watcher detected change:', newVal)
})

</script>


<template>

    <div class="px-2 pt-2  font-black text-[20px]">
      Typography
    </div>

<div class="flex-col flex text-center justify-center items-center">


  
    <div class="flex flex-row justify-center gap-2 px-2 w-[344px]">
        
        <div class="cursor-pointer">
             <ContainerUpText type="text" label="Font Size"><FontSize/></ContainerUpText>
        </div>

       
        <div class="cursor-pointer">
             <ContainerUpText type="text" label="Font Weight"><FontWeights/></ContainerUpText>
        </div>
    

     </div>




    <div class="flex flex-row space-between gap-2">
        <div class="cursor-pointer">
             <ContainerUpText type="text" label="Highlight"> 
            <div class="">
            <div  @change="selectLayout(selectedFontSize, 'layout')" class=" justify-center text-center flex">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 35H36" stroke="#FF0000" stroke-width="3" stroke-linecap="round"/>
                <path d="M33.5 28.5C34.7 26.5 32 23.3333 30.5 22C27.3 25.2 27.1667 27.6667 27.5 28.5C28.3 30.1 29.8334 30.5 30.5 30.5C32.1 30.5 33.1667 29.1667 33.5 28.5Z" fill="#FF0000" stroke="#FF0000"/>
                <g filter="url(#filter0_d_513_11151)">
                <path d="M17.5 5L19.5 7M19.5 7L28.5 16L23.5 21M19.5 7L9.5 17M23.5 21L16.5 28L7.5 19L9.5 17M23.5 21L9.5 17" stroke="black" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </g>
                <path d="M8.5 20L10.5 18L22.5 21.5L16.5 27.5L8.5 20Z" fill="#FF0000" stroke="black"/>
                <defs>
                <filter id="filter0_d_513_11151" x="2" y="3.5" width="32" height="34" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                <feOffset dy="4"/>
                <feGaussianBlur stdDeviation="2"/>
                <feComposite in2="hardAlpha" operator="out"/>
                <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.25 0"/>
                <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_513_11151"/>
                <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_513_11151" result="shape"/>
                </filter>
                </defs>
                </svg>
            </div>
            </div>
            </ContainerUpText>
        </div>

             <div class="cursor-pointer">
             <ContainerUpText type="text" label="Font Color"> 
            <div class="">
            <div  @change="selectLayout(selectedFontSize, 'layout')" class=" justify-center text-center flex">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 35H36" stroke="#FF0000" stroke-width="3" stroke-linecap="round"/>
                <path d="M33.5 28.5C34.7 26.5 32 23.3333 30.5 22C27.3 25.2 27.1667 27.6667 27.5 28.5C28.3 30.1 29.8334 30.5 30.5 30.5C32.1 30.5 33.1667 29.1667 33.5 28.5Z" fill="#FF0000" stroke="#FF0000"/>
                <g filter="url(#filter0_d_513_11151)">
                <path d="M17.5 5L19.5 7M19.5 7L28.5 16L23.5 21M19.5 7L9.5 17M23.5 21L16.5 28L7.5 19L9.5 17M23.5 21L9.5 17" stroke="black" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </g>
                <path d="M8.5 20L10.5 18L22.5 21.5L16.5 27.5L8.5 20Z" fill="#FF0000" stroke="black"/>
                <defs>
                <filter id="filter0_d_513_11151" x="2" y="3.5" width="32" height="34" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                <feOffset dy="4"/>
                <feGaussianBlur stdDeviation="2"/>
                <feComposite in2="hardAlpha" operator="out"/>
                <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.25 0"/>
                <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_513_11151"/>
                <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_513_11151" result="shape"/>
                </filter>
                </defs>
                </svg>
            </div>
            </div>
            </ContainerUpText>
        </div>
    </div>
    </div>


<!-- Prime Display -->
    <div class="px-2 pt-2 font-black text-[20px]">
	    Display
    </div>
     


     <div class="flex flex-row space-between gap-2">
            <div class="cursor-pointer w-full">
                <div class="px-3">
                    
                    <div class=" text-[12px] font-bold">
                      Target Size:
                      <RangeSlider></RangeSlider>
                    </div>

                    <div class=" text-[12px] font-bold">
                      Image Size:
                      <RangeSlider></RangeSlider>
                    </div>

                    <div class=" text-[12px] font-bold">
                     Image Border Radius:
                      <RangeSlider></RangeSlider>
                    </div>

                </div>
            </div>
        </div>


</template>

<style scoped>
.range-container {
  position: relative;
  width: 300px;
  margin: 2rem auto;
}

input[type="range"] {
  width: 100%;
}

#range-label {
  position: absolute;
  top: -30px;
  transform: translateX(-50%);
  background-color: #f0f0f0;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 12px;
}

input[type='range']::-webkit-slider-thumb {
  @apply h-4 w-4 bg-blue-500 rounded-full shadow-md transition-all;
  appearance: none;
  margin-top: -6px;
}

input[type='range']::-moz-range-thumb {
  @apply h-4 w-4 bg-blue-500 rounded-full shadow-md transition-all;
}

input[type='range']::-webkit-slider-runnable-track {
  @apply bg-blue-300 h-2 rounded-full;
}

input[type='range']::-moz-range-track {
  @apply bg-blue-300 h-2 rounded-full;
}
</style>