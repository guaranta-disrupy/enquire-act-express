<script setup>
import JetLabel from '@/Jetstream/Label.vue';
import JetCheckbox from '@/Jetstream/Checkbox.vue';
import S4TextArea from '@/S4/TextArea.vue';
import S4Input from '@/S4/Input.vue';
import { inject } from 'vue';

const props = defineProps(['itemsA','itemsB','setup'])
const getTranslatedItem = inject('getTranslatedItem', (item) => item?.name || '');
const isRTL = inject('isRTL', false);

</script>


<template>
    <div :dir="isRTL ? 'rtl' : 'ltr'" class="grid grid-cols-12 gap-1 mt-3">

        <div class="col-span-3">
            <p class="text-xs block w-full text-center">ITEMS 1</p>
            <div class="p-2" v-for="(item,index) in itemsA" :key="index">
                <div class="overflow-auto h-6/6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1">
                    <div class="border-slate-300 p-2 rounded-lg border container flex justify-center">
                        <!-- <img class="itemImg" :src="item.name"/> -->
                        <div class="imgCont" v-html="getTranslatedItem(item)"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-3">
            <p class="text-xs block w-full text-center">ITEMS 2</p>
            <div class="p-2" v-for="(item,index) in itemsB" :key="index">
                <div class="overflow-auto h-6/6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1">
                    <div class="border-slate-300 p-2 rounded-lg border container flex justify-center">
                        <!-- <img class="itemImg" :src="item.name"/> -->
                        <div class="imgCont" v-html="getTranslatedItem(item)"></div>
                    </div>
                </div>
            </div>
        </div>     

    </div>
</template>



<style scoped>
.itemImg{
    height:10vmin;
}
.itemCont {
    width:30vmin;
}
.imgCont {
    margin:auto auto;
}
.imgCont :deep() img {
    max-height:7vmin;
    position:relative;    
}
</style>