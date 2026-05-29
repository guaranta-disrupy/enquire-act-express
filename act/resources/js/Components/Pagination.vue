<template>
  <div class="mt-2 -mb-1 flex flex-wrap">
    <template v-for="(link, key) in links" :key="key">
      <div v-if="link.url === null" class="mr-1 mb-1 px-2 py-1 text-sm font-bold text-gray-600 border rounded text-gray-400" :class="{ 'ml-auto': link.label === 'Next' }">{{ link.label }}</div>
      <Link v-else class="mr-1 mb-1 px-2 py-1 text-sm shadow-sm border rounded font-bold text-gray-600 hover:bg-white focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-white': link.active, 'ml-auto': link.label === 'Next' }" :href="link.url">{{ link.label }}</Link>
    </template>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array
});
</script>
