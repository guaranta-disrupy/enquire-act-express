<script setup>
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

// Text Alignment
// SVG icon components
const IconCenter = {
  template: `<svg width="20" height="20" fill="none" viewBox="0 0 20 20"><path d="M14.1663 15H5.83301M16.6663 11.6667H3.33301M14.1663 8.33333H5.83301M16.6663 5H3.33301" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`
};

const IconLeft = {
  template: `<svg width="20" height="20" fill="none" viewBox="0 0 20 20"><path d="M3.33301 15H11.6663M3.33301 11.6667H16.6663M3.33301 8.33333H11.6663M3.33301 5H16.6663" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`
};

const IconRight = {
  template: `<svg width="20" height="20" fill="none" viewBox="0 0 20 20"><path d="M16.6663 15H8.33301M16.6663 11.6667H3.33301M16.6663 8.33333H8.33301M16.6663 5H3.33301" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`
};

const IconJustify = {
  template: `<svg width="20" height="20" fill="none" viewBox="0 0 20 20"><path d="M16.6663 15H3.33301M16.6663 11.6667H3.33301M16.6663 8.33333H3.33301M16.6663 5H3.33301" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`
};

// Options
const options = [
  { label: 'Center', value: 'center', icon: IconCenter },
  { label: 'Left', value: 'left', icon: IconLeft },
  { label: 'Right', value: 'right', icon: IconRight },
  { label: 'Justify', value: 'justify', icon: IconJustify }
];

const selectedOption = ref(null);
const isOpen = ref(false);

function selectOption(option) {
  selectedOption.value = option;
  isOpen.value = false;
  console.log('Selected:', option.value);
}

// Font Style
const optionFontStyle = [
  { label: 'Normal', value: 'Normal' },
  { label: 'Italic', value: 'Italic' },
];

const selectedFontStyle = ref(null);
const isOpenFontStyle = ref(false);

function selectFontStyle(option) {
  selectedFontStyle.value = option;
  isOpenFontStyle.value = false;
  console.log('Selected:', option.value);
}

// Font Size
const fontSizeOptions = [
  { label: 'Small', value: 'Small' },
  { label: 'Medium', value: 'Medium' },
  { label: 'Large', value: 'Large' },
];

const selectedFontSize = ref(null);
const isFontSizeDropdownOpen = ref(false);

function selectFontSize(option) {
  selectedFontSize.value = option;
  isFontSizeDropdownOpen.value = false;
  selectLayout(option.value, 'layout'); // Call external handler (as per your original code)
}

// Font Weight
const fontWeightOptions = [
  { label: 'Normal', value: 'Normal' },
  { label: 'Semibold', value: 'Semibold' },
  { label: 'Bold', value: 'Bold' },
];

const selectedFontWeight = ref(null);
const isFontWeightDropdownOpen = ref(false);

function selectFontWeight(option) {
  selectedFontWeight.value = option;
  isFontWeightDropdownOpen.value = false;
  selectLayout(option.value, 'layout'); // Same behavior as your original <select @change>
}

</script>


<template>


  
<div class="flex">
    <div class="relative inline-block w-[150px]">
  <!-- Dropdown button -->
  <div @click="isOpenFontStyle = !isOpenFontStyle" class="p-2 flex justify-left">
    <div v-if="selectedFontStyle" class="flex justify-center items-center w-full">
      <span class="w-full text-center">{{ selectedFontStyle.label }}</span>
    </div>
    <span v-else class="w-full text-center">Select</span>
    <svg class="w-5 h-5 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
    </svg>
  </div>

  <!-- Dropdown menu -->
  <div v-if="isOpenFontStyle" class="absolute z-10 mt-1 bg-white border border-gray-300 rounded-md w-full shadow-md">
    <div 
      v-for="option in optionFontStyle" 
      :key="option.value" 
      @click="selectFontStyle(option)" 
      class="p-2 hover:bg-gray-100 flex items-center cursor-pointer"
    >
      <span>{{ option.label }}</span>
    </div>
  </div>
</div>

</div>
    


</template>

