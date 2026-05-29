<script setup>
import JetLabel from '@/Jetstream/Label.vue';
import JetCheckbox from '@/Jetstream/Checkbox.vue';
import S4TextArea from '@/S4/TextArea.vue';
import S4Input from '@/S4/Input.vue';
import { inject } from 'vue';

const props = defineProps(['items','setup'])
const getTranslatedItem = inject('getTranslatedItem', (item) => item?.name || '');
const isRTL = inject('isRTL', false);

</script>

<template>
    <div :dir="isRTL ? 'rtl' : 'ltr'" class="grid gap-1 mt-4">
        <div class=" overflow-auto h-6/6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-5">
            <div class="p-2 border border-slate-300 rounded-lg container flex justify-center"  v-for="(item,index) in items" :key="index">               
                <div class="imgCont" v-html="getTranslatedItem(item)"></div>
            </div>
        </div>
    </div>
</template>



<style scoped>
.imgCont {
    margin:auto auto;
}
.imgCont :deep() img {
     max-height:8vmin;
     position:relative;
}
.itemImg{
    max-height:10vmin;
}
</style>