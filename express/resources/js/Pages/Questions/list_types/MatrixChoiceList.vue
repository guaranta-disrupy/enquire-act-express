<script setup>
import JetLabel from '@/Jetstream/Label.vue';
import JetCheckbox from '@/Jetstream/Checkbox.vue';
import S4TextArea from '@/S4/TextArea.vue';
import S4Input from '@/S4/Input.vue';
import { inject } from 'vue';

const props = defineProps(['rows','columns','setup'])
const getTranslatedItem = inject('getTranslatedItem', (item) => item?.name || '');
const isRTL = inject('isRTL', false);
const filterWC = (row)=>{ 
    let checkWriteChoice;
    if(!!!row.wc){
        checkWriteChoice = false;
    }
    else{
        checkWriteChoice = row.wc.enable === 1 ?  true : false;   
    } 
    return checkWriteChoice
}

</script>


<template>
    <div :dir="isRTL ? 'rtl' : 'ltr'" class="grid grid-cols-12 gap-1">
        <div class="col-span-12">
            
            <table class="table-fixed mt-3 border">
                <thead>
                    <tr class="border">
                        <th class="col border"></th>
                        <th class="col p-2 border" v-for="(colT, indexCOlT) in columns" :key="indexCOlT">
                             <div v-html="getTranslatedItem(colT)" class="text-xs font-normal"></div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, indexR) in rows" :key="indexR" class="border">
                        <!--<td class="colRow p-2">{{row.name}}</td>-->
                        <td class="colRow p-2 border">
                            <div v-html="getTranslatedItem(row)" class="text-center text-xs"></div>
                            <span>
                                <s4-input type="text" class="border-slate-300 text-sm focus:border-slate-200 focus:ring-1 focus:ring-slate-400 text-slate-400 w-28" style="height: 1.8rem;" v-if="filterWC(row)" :placeholder="row.wc.placeholder"></s4-input>
                            </span>
                        </td>
                        <td class="colVal border" v-for="(col, indexCOl) in columns" :key="indexCOl">
                            <p v-if="setup.selectMultiple == 0" class="text-center"><jet-checkbox class="rounded-lg colInput" disabled /></p>
                            <p v-else class="text-center"><jet-checkbox class="square-lg colInput SQ" disabled /></p>
                        </td>
                    </tr>
                    <!-- <tr v-if="setup.writeinRow">
                        <td class="colRow p-2 others">
                            {{setup.writeinRowChoice}}
                            <s4-input type="text" color="lightgrey" class="w-3/4 mt-1 othersInput" disabled>
                            </s4-input>
                        </td>
                        <td class="colVal" v-for="(col, indexCOl) in columns" :key="indexCOl">
                            <p v-if="setup.selectMultiple == 0"><jet-checkbox class="rounded-lg colInput" disabled /></p>
                            <p v-else><jet-checkbox class="square-lg colInput SQ" disabled /></p>
                        </td>
                    </tr> -->
                </tbody>
            </table>
        </div>
    </div>
</template>



<style scoped>
/* table, tr, td, th {
    border:1px solid lightgrey;
    font-size:1.1vmin;
}
.colVal {
    text-align:center;
}
.col {
    width:11vmin;
}
.colRow {
    width:20vmin;
    text-align:center;
}
.colRow span input {
    width: 70% !important;
}
.colRow :deep() img {
    max-height:5vmin;
    margin:auto auto;
    position:relative;
}
.colInput {
    height:1.2vmin!important; 
    width:1.2vmin!important;
    border-color:#ACABAB!important;
}
.SQ {
    border-radius:1px!important;
}
.others {
    font-size:0.8vmin;
}
.othersInput {
    height:1vmin;
    border:1px solid grey;
} */
.colRow :deep() img {
    max-height:7vmin;
    min-height:7vmin;
    margin:auto auto;
    position:relative;
}
</style>