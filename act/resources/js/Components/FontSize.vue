<script setup>
import { ref } from 'vue';

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
  selectLayout(option.value, 'layout'); // Call external handler
}

function selectLayout(value, type) {
  console.log('Selected:', value);
  // Your logic to apply layout settings
}


</script>

<template>



<div class="flex ">
    <!-- Font Size -->
    <div class="relative inline-block w-full cursor-pointer">

  <div @click="isFontSizeDropdownOpen = !isFontSizeDropdownOpen" class=" p-2 flex  justify-left">
    <div v-if="selectedFontSize" class="w-full ">
      {{ selectedFontSize.label }}
    </div>
    <span v-else class="w-full ">Select</span>
    <svg class="w-5 h-5 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
    </svg>
  </div>

  <!-- Dropdown options -->
  <div v-if="isFontSizeDropdownOpen" class="absolute z-10 mt-1 bg-white border border-gray-300 rounded-md w-full shadow-md">
    <div
      v-for="option in fontSizeOptions"
      :key="option.value"
      @click="selectFontSize(option)"
      class="p-2 hover:bg-gray-100 flex items-center cursor-pointer"
    >
      <span>{{ option.label }}</span>
    </div>
  </div>
</div>


</div>
    

</template>

