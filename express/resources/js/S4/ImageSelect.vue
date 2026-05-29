<template>
    <div class="relative">
      <!-- Dropdown trigger -->
      <div
        class="flex items-center justify-center border bg-gray-100 border-gray-300 px-[30px] rounded-md cursor-pointer "
        :class="{'border-[var(--mcolor)] ring-1 ring-[var(--mcolor)]':(isOpen)}"
        @click="toggleDropdown"
      >
        <img
          v-if="selectedOption"
          :src="selectedOption.image"
          alt="Selected Layout"
          class="h-[40px] w-full max-w-[120px] w-auto mx-auto"
        />
        <span v-else class="text-gray-500">Select Layout</span>
        <svg
          class="absolute right-3 h-4 w-4 text-gray-600 transform"
          :class="{ 'rotate-180': isOpen }"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </div>
  
      <!-- Dropdown options -->
      <div
        v-if="isOpen"
        class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-md max-h-56 overflow-y-auto"
      >
        <ul class="gap-[0px] p-[0px] flex">
          <li
            v-for="option in lists"
            :key="option.value"
            @click="selectOption(option)"
            class="cursor-pointer hover:bg-gray-300 flex flex-col items-center text-center p-2"
          >
            <img :src="option.image" :alt="option.text" class="h-[20px] w-full" />
            <!-- <span class="text-gray-700 text-sm font-medium">{{ option.text }}</span> -->
          </li>
        </ul>
      </div>
    </div>
  </template>
  
  
  
  <script setup>
  import { ref, computed } from "vue";
  
  const props = defineProps({
    modelValue: {
      default: "",
    },
    lists: {
      type: Array,
      required: true,
    },
  });
  
  const emits = defineEmits(["update:modelValue"]);
  
  const isOpen = ref(false);
  const selectedOption = computed(() =>
    props.lists.find((item) => item.value === props.modelValue)
  );
  
  const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
  };
  
  const selectOption = (option) => {
    emits("update:modelValue", option.value);
    isOpen.value = false;
  };
  </script>
  