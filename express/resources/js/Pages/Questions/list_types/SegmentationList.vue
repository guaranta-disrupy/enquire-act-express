<script setup>

import { computed, ref, inject } from 'vue';

const props = defineProps(['items','setup','values'])
const getTranslatedItemStatementA = inject('getTranslatedItemStatementA', (item) => item?.statementA || '');
const getTranslatedItemStatementB = inject('getTranslatedItemStatementB', (item) => item?.statementB || '');
const getTranslatedValue = inject('getTranslatedValue', (value) => value?.name || '');
const getTranslatedSetupLabel = inject('getTranslatedSetupLabel', (setup, key) => {
    if (key === 'statementA') return setup?.labels?.statementA || '';
    if (key === 'statementB') return setup?.labels?.statementB || '';
    return '';
});
const isRTL = inject('isRTL', false);

const convertSize = computed(() =>{
    return parseInt(props.setup.size)
})
</script>


<template>
    <div :dir="isRTL ? 'rtl' : 'ltr'" class="">

        <div class="">
            <div class="grid grid-cols-3 text-xs mr-20 mb-2 text-slate-400 font-semibold">
                <div class="text-right" v-html="getTranslatedSetupLabel(setup, 'statementA')"></div>
                <div></div>
                <div class="" v-html="getTranslatedSetupLabel(setup, 'statementB')"></div>
            </div>
            <div class="mr-20 leading-4 mb-3" v-for="(item,index) in items" :key="index">
                <div class="grid grid-cols-3">
                    <div class="grid border-white place-content-center justify-end">
                        <div class="text-right border-white" v-html="getTranslatedItemStatementA(item)"></div>
                    </div>
                    <div class="flex justify-between mx-5 items-center relative" v-if="values && values.length > 0">                            
                        <div v-for="(value) in values" class="border-2 rounded-full w-5 h-5 bg-slate-200"></div>
                        <div class="h-1.5 w-full bg-slate-200 absolute"></div>
                    </div>                   
                    <div class="grid border-white place-content-center justify-start">
                        <div class="border-white" v-html="getTranslatedItemStatementB(item)"></div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 text-xs mr-20 mt-3 text-slate-400 font-semibold" v-if="values && values.length > 0">
                <div class="grid justify-end -mr-20">
                    <div class="w-28 text-center" v-html="getTranslatedValue(values[0]) || ''"></div>
                </div>
                <div></div>
                <div class="grid justify-start -ml-20">
                    <div class="w-28 text-center" v-html="getTranslatedValue(values[values.length-1]) || ''">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- <div class="cont">
            <div class="p-2 addBG pl-5" v-for="(item,index) in items" :key="index">
                <span>{{item.name}}</span>
            </div>
        </div>

        <div class="cont">
            <div class="p-2 addBG pr-5" v-for="(el,index) in items" :key="index">
                <span v-for="(el2,index2) in setup.size" :key="index2">
                </span>
            </div>
        </div> -->

    </div>
</template>



<style scoped>
</style>