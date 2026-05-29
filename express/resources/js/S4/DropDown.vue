<script setup> 
const props = defineProps(["list","value"])
const emits  = defineEmits(['update:value'])
</script>
<template> 
    <select class="form-select block w-full rounded-md focus:ring-1 focus:ring-ssr-blue3 border-gray-300 focus:border-ssr-blue3 text-gray-700 border-1" :value="value" @input="$emit('update:value', $event.target.value)">
        <option v-for="(value,index) in list" :key="index" :value="index">{{value.name || value}}</option>
    </select> 
</template>




<style scoped>
</style>