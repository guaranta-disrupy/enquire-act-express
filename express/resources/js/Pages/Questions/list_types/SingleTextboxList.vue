<script setup>
import JetLabel from '@/Jetstream/Label.vue';
import JetCheckbox from '@/Jetstream/Checkbox.vue';
import S4TextArea from '@/S4/TextArea.vue';
import S4Input from '@/S4/Input.vue';

import { ref, computed, inject } from 'vue';
// import { registerRendererType } from 'highcharts';

const props = defineProps(['setup'])
const isRTL = inject('isRTL', false);
const inputWidth = ref(props.setup.style?.maxWidth || "")
const gridCols = ()=>{
    return 'grid-cols-'+props.setup.grid
}
const retType = computed(()=>{
            var type
            var valType = parseInt(props.setup.validate.type,10)


            if(valType == 1) type = "Text"
            else if(valType == 2) type = "Whole Number"
            else if(valType == 3) type = "Decimal Number"
            else if(valType == 4) type = "Email Address"
            else if(valType == 5) type = "Date"
            else if(valType == 6) type = "Time"
            else if(valType == 7) type = "Date Time"
            else if(valType == 8) type = "Letters Only"
            else if(valType == 9) type = "Alphanumeric"

            return type 
            // switch(this.setup.validate.type) {
            //     case "1":
            //         type = "Text"
            //         break;
            //     case "2":
            //         type = "Whole Number"
            //         break;
            //     case "3":
            //         type = "Decimal Number"
            //         break;
            //     case "4":
            //         type = "Email Address"
            //         break;
            //     case "5":
            //         type = "Date"
            //         break;
            //     case "6":
            //         type = "Time"
            //         break;
            //     case "7":
            //         type = "Date Time"
            //         break;
            // }            
        })
</script>

<template>
    <div :dir="isRTL ? 'rtl' : 'ltr'" class="grid gap-1">
        <div class="col-span-6 flex">
            <span class="mr-3 self-center" v-html="setup.prependString" v-if="setup.prependString"></span>
            <s4-input v-bind:style="{ maxWidth: '300px' }" :placeholder="retType" class="border-slate-300 text-sm focus:border-slate-200 focus:ring-1 focus:ring-slate-400 text-slate-400">
            </s4-input><span class="ml-3 self-center" v-html="setup.appendString" v-if="setup.appendString"></span>
            <span class="border border-slate-300 rounded-r-md bg-slate-200 text-slate-700 px-2 py-2.5 ml-[270px] absolute" v-if="props.setup.validate.filter_domain != null && props.setup.validate.type == 4" v-html="props.setup.validate.filter_domain"></span>
        </div>
    </div>
</template>

