<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faTrashAlt,faStar } from '@fortawesome/free-solid-svg-icons';

import JetLabel from '@/Jetstream/Label.vue';
import JetCheckbox from '@/Jetstream/Checkbox.vue';
import S4TextArea from '@/S4/TextArea.vue';
import S4Input from '@/S4/Input.vue';
import { computed, ref, inject } from 'vue';

const props = defineProps(['items','setup'])
const getTranslatedItem = inject('getTranslatedItem', (item) => item?.name || '');
const isRTL = inject('isRTL', false);

const faIcon = ref({
    trash: faTrashAlt,
    star: faStar
})

const convertSize = computed(() =>{
    return parseInt(props.setup.size)
})

const isImageTag = (str) => {
    // Check if the string contains an <img> tag
    const imgTagRegex = /<img\s+[^>]*src=["'][^"']+["'][^>]*>/i;
    return imgTagRegex.test(str);
};
</script>


<template>
    <div :dir="isRTL ? 'rtl' : 'ltr'" class="">
        
        <!-- <div class="cont">
            <div class="p-2 addBG pl-5" v-for="(item,index) in items" :key="index">
                <span>{{item.name}}</span>
            </div>
        </div>

        <div class="cont">
            <div class="p-2 addBG pr-5" v-for="(el,index) in items" :key="index">
                <span v-for="(el2,index2) in setup.size" :key="index2">
                    <font-awesome-icon class="fa-w-20 fa-h-20" :class="'text-slate-200'" :icon="faIcon.star" />
                </span>
            </div>
        </div> -->

        <div class="cont">
            <!-- Iterate through items -->
            <div class="p-2 addBG flex items-center justify-between pl-5 gap-2" v-for="(item, index) in items" :key="index">
                <!-- Left: Render Image if item.name contains an <img> tag -->
                <div v-if="isImageTag(getTranslatedItem(item))" style="max-width: 150px; max-height: 150px; overflow: hidden;">
                    <span v-html="getTranslatedItem(item)" style="display: block;"></span>
                </div>

                <!-- Left: Render Text if no <img> tag -->
                <span v-else class="text-gray-700" v-html="getTranslatedItem(item)"></span>

                <!-- Right: Star Icons -->
                <div class="flex gap-1">
                    <font-awesome-icon v-for="(el2, index2) in setup.size" :key="index2"
                        class="fa-w-20 fa-h-20 text-slate-400" :icon="faIcon.star" />
                </div>
            </div>
        </div>


    </div>
</template>



<style scoped>
.cont {
    display:inline-block;
    
}
/*.addBG:nth-child(odd) {
    background: #F9FAFB;    
}*/
.star {
    text-align:center;
}
</style>