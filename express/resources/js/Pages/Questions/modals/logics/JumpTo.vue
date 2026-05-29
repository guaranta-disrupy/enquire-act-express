<script setup>
import S4InputSelect from '@/S4/InputSelect.vue';
import S4InputSelectElem from '@/S4/InputSelectElem.vue';
import SingleSelect from '@/S4/SingleSelect.vue';
import JetLabel from '@/Jetstream/Label.vue';
import { usePage } from "@inertiajs/vue3";
import { onBeforeMount, onMounted, ref, inject, watch } from 'vue'
import helpers from '@/helpers.js'

/* helpers start */
const { assign } = helpers()
/* helpers end */

const props = defineProps({
    label: {
        default: 'Choices <small>[hide all selected]</small>:'
    },
    placeholder: {
        default: 'Click to select choices'
    },
    question: Object,
    questions: Object
})

const logic = inject('logic')
//console.log("logic", logic.value)
const logicData = ref(null) // Default to 1 if not set


const list = ref([])


onMounted(()=>{

    let aQs = props.questions.filter((res)=>{
        return res.order > props.question.order
    })

    aQs.forEach((question, index)=> {
        list.value.push({
            //value: index + 1,
            value: question.order,
            text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs'>Q"+(parseInt(question.order) + parseInt(parseInt(usePage().props.questionnaire.setup.variable_start)) - 1) + "</span> : " +question.question
        })
    });
    logicData.value = logic?.value?.jump_to || '1'
    
})
</script>
<template>
    <div class="flex flex-row gap-1 mt-2" v-if="list.length > 0">
        <div class="mb-5">
            <jet-label class="font-bold text-xs mb-1" v-html="label"></jet-label>
            <!-- <s4-input-select class="w-full text-sm" color="ssrGray" 
                :lists="list" 
                v-model="logic.jump_to">
            </s4-input-select> -->
            <SingleSelect v-if="logicData"
                size="sm"
                :options="list" 
                :modelValue="logicData"
                @update:modelValue="(res)=>{
                        logicData = res
                        logic.jump_to = res
                }"
            ></SingleSelect>
        </div>
    </div>
    <div v-else>
        <div class="text-red-500 text-sm -mt-3">No valid destination question is available. Please review your jump logic and ensure a valid target question has been configured.</div>
    </div>
</template>