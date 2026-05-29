<script setup>
import S4Button from '@/S4/Button.vue';
import S4TextArea from '@/S4/TextArea.vue';
import {ref} from 'vue';

const props = defineProps(['setup'])
const rowArea = ref('');
const splitForRow =(()=>{
    try{
        if(rowArea.value != null){
        const newInputs = [];
        let newRow = rowArea.value.split('\n');
        _.forEach(newRow, function(row,index){
            if(row !== "") {
                newInputs.push({name:row, enable_wr: 0})
            }                     
        }); 
        props.setup.splitForRow(newInputs);
    } 
    }catch(e){
        console.log(e)
    }
    rowArea.value = null; 
})

const revertRow =(()=>{
    console.log(props.setup);
    props.setup.close();
})
</script>

<template>
    <div class="col-span-12">
        <div>
            <s4-text-area color="ssrGray" v-model="rowArea" class="w-full h-40" placeholder="Enter choices separated by new line..."></s4-text-area>
        </div>
        <div class="flex mb-5">
            <s4-button :theme="'ssrAddColRow'" class="py-1 px-2 mt-2" @click="splitForRow()">Add</s4-button>
            <s4-button :theme="'ssrAddColRow'" class="py-1 px-2 ml-1 mt-2" @click="revertRow()">Cancel</s4-button>
        </div>
    </div>
</template>