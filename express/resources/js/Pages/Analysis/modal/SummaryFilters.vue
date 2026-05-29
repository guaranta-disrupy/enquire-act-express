<script setup>
import { usePage } from "@inertiajs/vue3"
import { onMounted, ref } from 'vue'

const selected_filter = ref(0)

onMounted(()=>{
    selected_filter.value =  usePage().props.selected_filter
})

const handleChange = () => {
    window.dispatchEvent(new window.CustomEvent('getSelectedFilter', {
        detail: { id : selected_filter.value }
    }));
}


</script>

<template>
   <div  class="grid-cols-1 w-full mt-2 mb-2">
        <select class="h-10 rounded-md focus:ring-1 focus:ring-ssr-blue3 w-full border-gray-300 focus:border-ssr-blue3 text-gray-700" @change="handleChange" v-model="selected_filter">
            <option value="0">--Select Filter--</option>
            <option v-for="(filter,index) in $page.props.filters" :value="filter.id" :key="index" selected="true">{{filter.name}}</option>
        </select>
    </div>
</template>



<style>

</style>