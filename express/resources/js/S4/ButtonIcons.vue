<script setup>
import IconsVue from './Icons.vue';

const props = defineProps(["icon","label","size"])

</script>
<template>
    <div v-if="props.size === 'md'" class="flex items-center w-14 h-14 justify-center cursor-pointer group text-ssr-orange2 relative">  
        <div v-if="!!label" class="-translate-x-[30px] group-hover:-translate-x-[110px] absolute z-10 border 
           rounded-lg text-gray-500 opacity-0 group-hover:opacity-100 hover:ease-in duration-300 w-36 block flex justify-center py-1">
            {{label}}
        </div>
        <div class="group-hover:scale-125 hover:ease-in duration-300 relative z-20">
            <IconsVue :icon="props.icon" size="md"/>
        </div>  
    </div>
    <div v-else class="flex items-center w-10 h-10 justify-center cursor-pointer group text-ssr-orange2 relative">  
        <div v-if="!!label" class="-translate-x-[30px] group-hover:-translate-x-[100px] absolute z-10 border 
           rounded-lg text-gray-500 opacity-0 group-hover:opacity-100 hover:ease-in duration-300 w-36 block flex justify-center py-1">
            {{label}}
        </div>
        <div class="group-hover:scale-125 hover:ease-in duration-300 relative z-20">
            <IconsVue :icon="props.icon" size="sm"/>
        </div>  
    </div>
</template>