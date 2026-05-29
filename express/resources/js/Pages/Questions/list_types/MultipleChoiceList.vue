
<script setup>
import JetLabel from '@/Jetstream/Label.vue';
import JetCheckbox from '@/Jetstream/Checkbox.vue';
import S4TextArea from '@/S4/TextArea.vue';
import S4Input from '@/S4/Input.vue';
import S4InputSelect from '@/S4/InputSelect.vue';
import { computed, nextTick, onBeforeUpdate, ref, watch, inject } from 'vue';


const props = defineProps(['choices', 'setup'])
const getTranslatedChoice = inject('getTranslatedChoice', (choice) => choice?.name || '');
const isRTL = inject('isRTL', false);
          
const new_choice = ref('')
const itemRefs = ref([])  
    
const convertDp = computed(() =>{
    let dp = props.choices.map((item) => {
        return { text: getTranslatedChoice(item) }
    })
    return dp;
})
    
   
const filterWC = (choice) =>{ 
    let checkWriteChoice;
    if(!!!choice.wc){
        checkWriteChoice = false;
    }
    else{
        checkWriteChoice = choice.wc.enable === 1 ?  true : false;   
    } 
    return checkWriteChoice
}
const setItemRef = (el) =>{
    if (el) {
        itemRefs.value.push(el)
    }
}
const gridCols = ()=>{
    return (props.setup?.textSelection?.grid) ? 'grid-cols-'+ props.setup.textSelection.grid : '1'
}

onBeforeUpdate(()=>{
    itemRefs.value = []
})
  
  
// watch(()=>{
//     new_choice,
//     (new_choice)=>{
//         if (new_choice.value != '') {
//             $questionvar.add_edit.choices.push({name: new_choice})
//             $nextTick(() => {
//                 itemRefs.value[$questionvar.add_edit.choices.length - 1].focus();
//                 new_choice.value = ''
//             })
//         }
//     }
// })

watch(new_choice, (value) => {
    if (value !== '') {
        $questionvar.add_edit.choices.push({ name: value });
        nextTick(() => {
            itemRefs.value[$questionvar.add_edit.choices.length - 1].focus();
            new_choice.value = '';
        });
    }
});
</script>

<template>
    <div :dir="isRTL ? 'rtl' : 'ltr'">
    <!-- <div class="grid gap-1" :class="gridCols()">
        <template v-for="(choice, index) in choices" :key="index">
            <p><jet-checkbox :class="{'rounded-lg': setup.selectMultiple == 0}" disabled /> <span v-html="choices[index].name"></span></p>
            <div class="col-span-6" v-if="filterWC(choice)">
                <s4-input color="ssrGray">
                </s4-input>
            </div>
        </template>
    </div> -->
    <div v-if="setup.isDropdown" class="w-96">
        <s4-input-select class="w-1/4 border-slate-300 text-sm focus:border-slate-200 focus:ring-1 focus:ring-slate-400 text-slate-400" :lists="convertDp" >
        </s4-input-select>
    </div>
    <div v-else class="text-sm">
        <div class="col-span-12">
            <table class="table-fixed mt-3">
                <tbody class="grid gap-1" :class="gridCols()">
                    <tr v-for="(choice, index) in choices" :key="index" class="">
                        <td>
                            <p class="flex items-center">
                                <div>
                                    <jet-checkbox style="" :class="{'rounded-lg': setup.selectMultiple == 0}" disabled />
                                </div> 
                                <div>
                                    <span class="ml-2 mr-1 MChoice" v-html="getTranslatedChoice(choices[index])"></span>
                                </div> 
                                <span>
                                    <s4-input class="border-slate-300 text-sm focus:border-slate-200 focus:ring-1 focus:ring-slate-400 text-slate-400" type="text" style="height: 1.8rem;" v-if="filterWC(choice)" :placeholder="choice.wc.setup.placeholder"></s4-input>
                                </span>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table> 
        </div>
    </div>
    </div>
</template>



<style scoped>
.MChoice :deep() img {
    width: 10vmin!important;
    border: 1px solid lightgray;
}

.MChoice :deep() p {
    padding: 0px!important;
    padding-right: 10px!important;
    margin: 0px!important;
    margin-block-start: 0em!important;
    margin-block-end: 0em!important;
    display: inline-block!important;
    width:100%!important;
    /* width: 100%; */
}

</style>