<template>
  <div
    class="absolute rounded-full flex items-center justify-center border-gray-300 bg-white font-bold text-center p-4 border-[1.2vmin] text-black text-[3.5vmin]"
    :style="{
      width: centerSize + 'vmin',
      height: centerSize + 'vmin',
      top: '50%',
      left: '50%',
      transform: 'translate(-50%, -50%)',
    }"
    v-html="Primes || 'Loading...'"
  ></div>
</template>

<script setup>
const props = defineProps({
  Primes: {
    type: Object,
    required: false,
  },
  centerSize: {
    type: Number,
    required: false,
  },
});
</script>