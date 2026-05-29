<script setup>
import { Link } from "@inertiajs/vue3";
const props = defineProps(['links']);
</script>

<template>
    <div class="-mt-10 -mb-1 flex flex-wrap relative px-2">
        <template v-for="(link, key) in links" :key="key">
            <div v-if="link.url === null" 
            class="mr-1 mb-1 px-3 py-1 text-md border rounded-full text-gray-400" 
            :class="{ 'ml-auto': links.length === (key+1) }">
                <span v-html="link.label"></span>
            </div>
            <Link v-else 
                class="mr-1 mb-1 px-3 py-1 text-md text-gray-700  border rounded-md hover:bg-ssr-blue3 hover:text-white" 
                :class="{ 'text-white bg-ssr-blue2': link.active, 'ml-auto': links.length === (key+1) }" 
                :href="link.url">
                <span v-html="link.label"></span>
            </Link> 
        </template>
    </div>
</template>

