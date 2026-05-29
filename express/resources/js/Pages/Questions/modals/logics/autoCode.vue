<template>
    <div class="flex gap-1 flex-wrap">
        <div class="min-w-[300px] max-w-[400px]">
            <SingleSelect 
                size="sm"
                :options="computedQlist" 
                :modelValue="selected.q"
                @update:modelValue="(res)=>{
                    selected.q = res
                }"
            ></SingleSelect>
        </div>
        <div class="min-w-[200px] max-w-[300px]" v-if="(selected.q) || selected.q != ''">
            <div v-if="computedOptions">
                <SingleSelect 
                    size="sm"
                    v-if="(qChoiceInfo.type=='choice')"
                    :options="computedOptions" 
                    :modelValue="selected.c"
                    @update:modelValue="(res)=>{
                        selected.c = res
                    }"
                ></SingleSelect>
            </div>
            <div v-else>
                <s4-input color="ssrGray" class="w-full mb-1 text-sm" :theme="'theme3'"
                    :placeholder="'e.g. 1'" v-model="selected.n">
                </s4-input>
            </div>

        </div>
    </div>
</template>


<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import SingleSelect from '@/S4/SingleSelect.vue';
import S4Input from "@/S4/Input.vue";
import { useQuestionData, useQuestionChoices } from '@/Pages/Questions/modals/logics/composables/questions.js'

const selected = ref({
    q : '',
    c : '',
    n : '',
    n2 : ''
})

const qChoiceInfo = ref(null)

const form = ref({
    TAG : '',
    Question : '',
    text : '',
    value : ''
})


watch(()=>selected.value.q,()=>{
    setQChoiceInfo()
},{deep:true})

watch(()=>selected.value.c,()=>{
    generateForm()
})

const computedQlist = computed(()=>{
    return useQuestionData()
})

const setQChoiceInfo = ()=>{
    let choice = useQuestionChoices(selected.value.q)
    qChoiceInfo.value = choice
    return 'Done'
}

const computedOptions = computed(()=>{
    let res = null
    switch (qChoiceInfo.value.type){
        case "choice":
        case "rating":
            res = qChoiceInfo.value.choices
            break
        case "input":
            if(qChoiceInfo.value.isMultiple){
                res = qChoiceInfo.value.choices
            }
            break

    }
    return res
})

const computedSelection2 = ()=>{
    let res = null
    

}


const generateForm = ()=>{
        let data = {
            TAG : '',
            Question : qChoiceInfo.value.TAG,
            value : '',
            text : ''
        }
      if(qChoiceInfo.value.type == "choice"){
          if(qChoiceInfo.value.isMultiple){
            data.TAG = qChoiceInfo.value.TAG+"_"+selected.value.c
            data.value = 1
          }else{
            data.TAG = qChoiceInfo.value.TAG
            data.value = selected.value.c
          }
      }else if(qChoiceInfo.value.type == "input"){
            if(qChoiceInfo.value.isMultiple){
                data.TAG = qChoiceInfo.value.TAG+"_"+selected.value.c
                data.value = ''
            }else{
                data.TAG = qChoiceInfo.value.TAG
                data.value = selected.value.n.trim()
            }
      }else if(qChoiceInfo.value.type == "rating"){
            data.TAG = qChoiceInfo.value.TAG+"_"+selected.value.c
            data.value = ''
      }
    


}

// onMounted(()=>{
//     console.log("QLLLLIIIISSST",Qlist)
// })

</script>