<script setup>
import JetLabel from '@/Jetstream/Label.vue';
import JetCheckbox from '@/Jetstream/Checkbox.vue';
import S4TextArea from '@/S4/TextArea.vue';
import S4Input from '@/S4/Input.vue';
import { inject } from 'vue';

const props = defineProps(['setup', 'items'])
const getTranslatedItem = inject('getTranslatedItem', (item) => item?.name || '');
const isRTL = inject('isRTL', false);
const retType = (type = parseInt(type, 10)) => {
    var indType
    // switch(type) {
    //     case 1:
    //         indType = "Text"
    //         break;
    //     case 2:
    //         indType = "Whole Number"
    //         break;
    //     case 3:
    //         indType = "Decimal Number"
    //         break;
    //     case 4:
    //         indType = "Email Address"
    //         break;
    //     case 5:
    //         indType = "Date"
    //         break;
    //     case 6:
    //         indType = "Time"
    //         break;
    //     case 7:
    //         indType = "Date Time"
    //         break;
    // }

    if (type == 1) indType = "Text"
    else if (type == 2) indType = "Whole Number"
    else if (type == 3) indType = "Decimal Number"
    else if (type == 4) indType = "Email Address"
    else if (type == 5) indType = "Date"
    else if (type == 6) indType = "Time"
    else if (type == 7) indType = "Date & Time"
    else if (type == 8) indType = "Letters Only (A-Z, a-z)"
    else if (type == 9) indType = "Alphanumeric"
    
    return indType
}
const gridCols = () => {
    //console.log("Test",props.setup)
    return 'grid-cols-' + props.setup.multipleTextSelection.grid || props.setup.grid
}

</script>
<template>
    <div :dir="isRTL ? 'rtl' : 'ltr'" class="col-span-12">
        <div class="grid gap-4 w-2/3" :class="gridCols()">
            <div v-for="(item, index) in items" :key="index" class="mb-3" :class="{'w-2/5':(props.setup.multipleTextSelection.grid == 1)}">
                <div class="block imgCont">
                    <span v-html="getTranslatedItem(item)"></span>
                </div>
                <s4-input class="h-8 mt-2 border-slate-300 text-sm focus:border-slate-200 focus:ring-1 focus:ring-slate-400 "
                    :placeholder="retType(item.validate.type)"></s4-input>
            </div>
        </div>
    </div>
</template>

<style scoped>
.imgCont span :deep() img {
    max-height: 10vmin!important;
    border-radius: 7px;
}
/* .imgCont span {
    border: 1px solid lightgray;
    width: 15vh;
    height: 15vh;
    overflow: hidden;
    display: flex;
    border-radius: 7px;
}
.imgCont span :deep() img {    
    max-width: inherit;
    max-height: inherit;
    height: inherit;
    width: inherit;
    object-fit: contain;
} */
</style>

