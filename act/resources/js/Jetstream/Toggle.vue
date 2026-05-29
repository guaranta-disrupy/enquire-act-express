<!-- Toggle.vue -->
<template>
    <button
      type="button"
      @click="modelValue = !modelValue"
      :class="[
        modelValue ? 'bg-[#15919a]' : 'bg-gray-300',
        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none'
      ]"
    >
      <span
        :class="[
          modelValue ? 'translate-x-6' : 'translate-x-1',
          'inline-block h-4 w-4 transform rounded-full bg-white transition-transform'
        ]"
      />
    </button>
  </template>
  
  <script setup>
  const modelValue = defineModel({ type: Boolean, default: false })
  </script>
  