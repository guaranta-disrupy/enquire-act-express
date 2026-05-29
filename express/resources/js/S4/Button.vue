<script setup> 
import {computed} from 'vue'; 
    const props = defineProps(
        {
            color: {
                default: 'blue'
            },
            type: {
                type: String,
                default: 'button',
            },
            size: {
                default: 'md'
            },
            theme:{
                 default: 'theme1'
            } 
        }
    )
    const sizes = computed(()=>{
        return {
            'sm': " px-1 py-1 text-sm",
            'md': " px-3 py-2",
            'lg': " px-4 py-4"
        }
    },[props.size]);
    
    const colors = computed(()=>{
        return {
            blue: "text-white bg-blue-500 border-blue-500 hover:bg-blue-600 hover:border-blue-600",
            gray: "text-white bg-gray-400 border-gray-400 hover:bg-gray-500 hover:border-gray-500",
            yellow: "text-white bg-yellow-400 border-yellow-400 hover:bg-yellow-500 hover:border-yellow-500",
            orange: "text-white bg-orange-400 border-orange-400 hover:bg-orange-500 hover:border-orange-500",
            red: "text-white bg-red-400 border-red-400 hover:bg-red-500 hover:border-red-500",
            "outline-blue": "text-gray-700 bg-white border-blue-500 hover:border-blue-600",
            "outline-gray": "text-gray-700 bg-white border-gray-400 hover:border-gray-500",
            "outline-yellow": "text-gray-700 bg-white border-yellow-400 hover:border-yellow-500",
            "outline-orange": "text-gray-700 bg-white border-orange-400 hover:border-orange-500",
            "outline-red": "text-gray-700 bg-white border-red-400 hover:border-red-500",
            ssrBlueIcon: "text-gray-400 hover:border-ssr-blue2 hover:text-ssr-blue2 ssrBT",
            ssrRedIcon: "text-gray-400 hover:border-ssr-red2 hover:text-ssr-red2 ssrBT",
            ssrOrangeIcon: "text-gray-400 hover:border-ssr-orange2 hover:text-ssr-orange2 ssrBT",
            ssrBlueIconCret: "text-white bg-ssr-blue4 rounded-full border border-ssr-blue4 ssrBTX",
        }
    },[props.color]);
    const styleBtn = computed(()=>{
        return "relative inline-flex items-center px-4 py-2 bg-blue-800 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-yellow-500 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:shadow-outline-yellow transition ease-in-out duration-50";
    });
    const ssrBlue = computed(()=>{
        return "relative inline-flex items-center px-4 py-2 bg-ssr-blue2 border border-transparent rounded-md font-bold text-sm text-white tracking-widest hover:bg-ssr-blue1 focus:outline-none !bg-[var(--txtcolor)] hover:!bg-black";
    });
    const ssrOrange = computed(()=>{
        return "relative inline-flex items-center px-4 py-2 bg-ssr-orange2 border border-transparent rounded-md font-bold text-sm text-white tracking-widest hover:bg-ssr-orangehover focus:outline-none focus:border-ssr-orangehover !bg-[var(--mcolor)] !hover:bg-[var(--mcolor2)] !text-black";
    });
    const ssrYellow = computed(()=>{
        return "relative inline-flex items-center px-4 py-2 bg-ssr-yellow1 border border-transparent rounded-md font-bold text-sm text-white tracking-widest focus:outline-none";
    });
    const ssrCancel = computed(()=>{
        return "relative inline-flex items-center px-4 py-2 border border-transparent rounded-md font-bold text-sm text-gray-700 tracking-widest hover:text-gray-400";
    });
    const ssrAddColRow = computed(()=>{
        return "relative inline-flex items-center bg-gray-200 border-transparent rounded-md text-xs text-gray-900 tracking-widest hover:bg-gray-300 focus:outline-none";
    });
    const ssrTemplate = computed(()=>{
        return "relative inline-flex items-center bg-white border border-transparent rounded-md font-bold text-sm text-ssr-blue2 tracking-widest hover:bg-white hover:text-ssr-blue2 focus:outline-none -mr-3";
    }); 
    const floatingBTN = computed(()=>{
        return "items-center px-4 py-2 bg-ssr-orange2 border border-transparent rounded-md font-semibold text-white uppercase fixed z-50 bottom-8 right-10 drop-shadow-lg flex justify-center text-white text-md hover:bg-ssr-orangehover hover:drop-shadow-2xl";
    }); 
</script>


<template>
    <button v-if="theme == 'theme2'" :type="type" 
        :class="ssrBlue">
        <slot></slot>
    </button>
    <button v-else-if="theme == 'ssrOrange'" :type="type"
        :class="ssrOrange">
        <slot></slot>
    </button>
    <button v-else-if="theme == 'ssrYellow'" :type="type"
        :class="ssrYellow">
        <slot></slot>
    </button>
    <button v-else-if="theme == 'ssrCancel'" :type="type" 
        :class="ssrCancel">
        <slot></slot>
    </button>
    <button v-else-if="theme == 'ssrAddColRow'" :type="type" 
        :class="ssrAddColRow">
        <slot></slot>
    </button>
    <button v-else-if="theme == 'ssrTemplate'" :type="type" 
        :class="ssrTemplate">
        <slot></slot>
    </button>
    <button v-else-if="theme == 'floatingBTN'" :type="type" 
        :class="floatingBTN">
        <slot></slot>
    </button>
    <button v-else :type="type"
        class="inline-flex items-center rounded-sm border-2 focus:outline-none transition ease-in-out duration-150 font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
        :class="colors+sizes">
        <slot></slot>
    </button> 
</template>
