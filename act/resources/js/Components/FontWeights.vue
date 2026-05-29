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


// Font Weight
const fontWeightOptions = [
  { label: 'Normal', value: 'Normal' },
  { label: 'Semibold', value: 'Semibold' },
  { label: 'Bold', value: 'Bold' },
];

const selectedFontWeight = ref(null);
const isFontWeightDropdownOpen = ref(false);

// Cleaned up function
function selectFontWeight(option) {
  selectedFontWeight.value = option;
  isFontWeightDropdownOpen.value = false;
  console.log(`Selected:`, option.value); // Just log it, no dropdowns
}


</script>


<template>




<!-- Font Weight -->
 <div class="flex">
        <div class="relative inline-block w-full cursor-pointer">
  
  <!-- Dropdown trigger -->
  <div @click="isFontWeightDropdownOpen = !isFontWeightDropdownOpen" class="p-2 flex justify-left">
    <div v-if="selectedFontWeight" class="w-full text-center">
      {{ selectedFontWeight.label }}
    </div>
    <span v-else class="w-full text-center">Select</span>
    <svg class="w-5 h-5 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
    </svg>
  </div>

  <!-- Dropdown options -->
  <div v-if="isFontWeightDropdownOpen" class="absolute z-10 mt-1 bg-white border border-gray-300 rounded-md w-full shadow-md">
    <div
      v-for="option in fontWeightOptions"
      :key="option.value"
      @click="selectFontWeight(option)"
      class="p-2 hover:bg-gray-100 flex items-center cursor-pointer"
    >
      <span>{{ option.label }}</span>
    </div>
  </div>
</div>

</div>

</template>

<style>
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
</style>