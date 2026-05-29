<template>
    <div class="flex gap-1">
        <div v-if="choicesList.length > 0" class="min-w-[150px]">
            <jet-label class="text-xs" value="Choice" />
            <SingleSelect 
                size="sm"
                :options="choicesList" 
                :modelValue="form.choice"
                @update:modelValue="(res)=>{
                    form.choice = res
                }"
            ></SingleSelect>          
        </div>

        <div v-if="(choices2List.length > 0 && props.question.data.setup.selectMultiple)" class="min-w-[150px]">
            <jet-label class="text-xs" value="Value" />
            <SingleSelect 
                size="sm"
                :options="choices2List" 
                :modelValue="form.choice2"
                @update:modelValue="(res)=>{
                    form.choice2 = res
                }"
            ></SingleSelect>    
        </div>  
    </div>
</template>

<script setup>
import {ref,onMounted,watch} from 'vue'
import SingleSelect from '@/S4/SingleSelect.vue';
import S4Input from "@/S4/Input.vue";
import JetLabel from "@/Jetstream/Label.vue";

const props = defineProps({
    question : {required:true},
    modelValue : {required:true}
})

const emits = defineEmits("update:modelValue")

const choicesList = ref([])
const choices2List = ref([])

let form = ref({
    choice : '',
    choice2 : '',
})


watch(()=>props.question,()=>{
    form.value.choice = ""
    form.value.choice2 = ""
    setup()
},{deep:true})

watch(()=>form.value.choice,()=>{
    choices2List.value = generateChoices2()
    updateValues()
})
watch(()=>form.value.choice2,()=>{
    updateValues()
})

const generateChoices = ()=>{
    let list = []
    props.question.data.choices.forEach((choice)=>{
        console.log("choice",choice)
        let c = {
            value:(choice?.score) ? choice.score : choice.index,
            text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(choice?.score || choice.index || index + 1)+"</span> "+(choice?.name || 'Choice'),
            name : choice.name
        }           
        c['choice2'] = [
            {
                value: choice.checkboxScore.active,
                text:  "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+choice.checkboxScore.active+"</span> Selected",
                name : "Selected"
            },
            {
                value: choice.checkboxScore.inactive,
                text:  "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+choice.checkboxScore.inactive+"</span> Unselected",
                name : "Unselected"
            }
        ]
        list.push(c)
    })

    return list
}


const generateChoices2 = ()=>{
    if(form.value.choice == ""){
        return []
    }
    let choice = choicesList.value.find((obj)=>{
        return obj.value == form.value.choice
    })
    return choice.choice2
}


const updateValues = ()=>{
    let variable = props.question.TAG
    let value = ''
    let text = ''
    if(props.question.data.setup.selectMultiple){
        variable = variable+"_"+form.value.choice
        value = form.value.choice2
        let choice = choices2List.value.find((obj)=>{
            return obj.value == form.value.choice2
        })     
        text = choice?.name ?? ''
    }else{
        value = form.value.choice
        let choice = choicesList.value.find((obj)=>{
            return obj.value == form.value.choice
        })     
        text = choice?.name ?? ''

    }
    emits("update:modelValue",{
        variable : variable,
        value : value,
        text : text
    })
}

const setup = ()=>{
    if(props.question.data.setup.selectMultiple){
        let vars = props.modelValue.variable.split("_") ?? []
        form.value.choice = vars[1] ?? ''
        form.value.choice2 = props.modelValue.value

    }else{
        form.value.choice = props.modelValue.value
    }
    choicesList.value = generateChoices()
    console.log("choicesList.value",choicesList.value)
   
}


onMounted(()=>{
    setup()
    console.log("choicesList",choicesList.value)
})


</script>