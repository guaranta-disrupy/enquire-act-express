<script setup>
import JetLabel from '@/Jetstream/Label.vue';
import JetCheckbox from '@/Jetstream/Checkbox.vue';
import S4TextArea from '@/S4/TextArea.vue';
import S4Input from '@/S4/Input.vue';
import { inject } from 'vue';

const props = defineProps(['setup', "items"])
const getTranslatedItem = inject('getTranslatedItem', (item) => item?.name || '');
const isRTL = inject('isRTL', false);
const isImageTag = (name) => {
    return /<img[^>]+src="([^"]+)"[^>]*>/i.test(name);
};
//console.log("setup",props.setup)
</script>

<template>
    <div :dir="isRTL ? 'rtl' : 'ltr'">
    <!-- <div class="flex text-slate-400"> 
        <div class="py-2 m-2 px-4 bg-white rounded-lg border border-slate-200">
            <ul class="justify-center w-80">
                <li class="p-2 flex justify-center bg-slate-50 rounded-lg text-sm m-1 cursor-pointer" v-for="(item,index) in items" :key="index">{{item.name}}</li> 
            </ul>
        </div>   
        <div class="w-80 py-2 m-2 px-4 bg-white rounded-lg border border-slate-200">
            <div class="flex justify-center text-xs">Drop Here</div> 
        </div>  
   </div> -->

    <div class="grid gap-2  text-black "
    :class="[(props.setup.mode == 1) ? 'grid-cols-1 max-w-[unset]' : 'grid-cols-2 max-w-[900px]']"
    >
        <div class=" py-2 px-2 bg-white rounded-lg border">
            <ul class="justify-center w-full grid gap-1 ssrlist items-stretch"
            :class="[(props.setup.numberOfColumns >= 1 && props.setup.mode == 1) ? 'grid-cols-'+props.setup.numberOfColumns : 'grid-cols-1']"
            >
                <li class="w-full justify-left  text-sm cursor-pointer h-full grid-cols-1"
                    v-for="(item, index) in items" :key="index"
                    
                    >
                    <!-- <div v-if="isImageTag(item.name)" class="flex justify-center">
                        <span v-html="item.name" class="max-w-[100px] max-h-[100px] object-contain"></span>
                    </div> -->
                    <div class="grid gap-1 bg-white border-gray-200 border rounded-md overflow-hidden h-full"
                        :class="[(props.setup.mode != 1) ? 'grid-cols-1 px-2' : 'grid-cols-[auto_1fr]' ]"
                    >
                        <div v-if="props.setup.mode == 1" class="bg-[#2378bc] text-white min-w-[25px] text-center flex justify-center items-center"><div>{{ index + 1 }}</div></div>
                        <div  class="py-2 flex justify-start items-center"><div v-html="getTranslatedItem(item)"></div></div>
                    </div>
                </li>
            </ul>
        </div>

        <div class="py-2 px-2 bg-white rounded-lg border border-slate-200" v-if="props.setup.mode != 1">
            <div class="flex justify-center items-center text-xs h-full"><div>Drop Here</div></div>
        </div>
    </div>
    </div>

</template>

<style lang="css" scoped>
.ssrlist /deep/ img{
  width:80px!important;
  display: inline-block;
  vertical-align: middle;
}

</style>
