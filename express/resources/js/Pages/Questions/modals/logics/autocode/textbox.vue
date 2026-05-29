<template>
    <div class="flex gap-1">

        <div class="flex gap-1" v-if="(choicesList.length > 0 && props.question.data.question_type_id == 5)">
            <SingleSelect 
                size="sm"
                :options="choicesList" 
                :modelValue="form.choice"
                @update:modelValue="(res)=>{
                    form.choice = res
                }"
            ></SingleSelect>  
            <s4-input color="ssrGray" class="w-full mb-1 text-sm" :theme="'theme3'"
                :placeholder="'e.g. 1'" v-model="form.input2">
            </s4-input>
            
        </div>  
        <div v-else>
            <s4-input color="ssrGray" class="w-full mb-1 text-sm" :theme="'theme3'"
                :placeholder="'e.g. 1'" v-model="form.input">
            </s4-input>

        </div>

    </div>
</template>

<script setup>
import {ref,onMounted,watch} from 'vue'
import SingleSelect from '@/S4/SingleSelect.vue';
import S4Input from "@/S4/Input.vue";

const props = defineProps({
    question : {required:true},
    modelValue : {required:true}
})

const emits = defineEmits("update:modelValue")

const choicesList = ref([])

let form = ref({
    input : '',
    choice : '',
    input2 : '',
})


watch(()=>props.question,()=>{
    form.value.choice = ""
    setup()
},{deep:true})
watch(()=>form.value.choice,()=>{
    updateValues()
})
watch(()=>form.value.input,()=>{
    updateValues()
})
watch(()=>form.value.input2,()=>{
    updateValues()
})

const generateChoices = ()=>{
    let list = []
    props.question.data.items.forEach((choice)=>{
        let c = {
            value:(choice?.index) ? choice.index : index + 1,
            text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(choice?.index || index + 1)+"</span> "+(choice?.name || 'Choice'),
            name : choice.name
        }
        list.push(c)
    })

    return list
}


const updateValues = ()=>{
    let variable = props.question.TAG
    let value = ''
    let text = ''
    if(props.question.data.question_type_id == 5){
        variable = variable+"_"+form.value.choice
        value = form.value.input2
        text = form.value.input2
    }else{
        value = form.value.input
        text = form.value.input

    }
    emits("update:modelValue",{
        variable : variable,
        value : value,
        text : text
    })
}

const setup = ()=>{
    if(props.question.data.question_type_id == 5){
        let vars = props.modelValue.variable.split("_") ?? []
        form.value.choice = vars[1] ?? ''
        form.value.input2 = props.modelValue.value
        choicesList.value = generateChoices()
    }else{
        form.value.input = props.modelValue.value
    }
}


onMounted(()=>{
    setup()
})


</script>