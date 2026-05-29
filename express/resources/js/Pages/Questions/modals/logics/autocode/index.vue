<template>
    <div class="-mt-3">
    <div class="flex gap-1 flex-wrap pb-4">
        <div class="min-w-[300px] max-w-[400px]">
            <jet-label class="text-xs" value="Please select question to autocode" />
            <SingleSelect size="sm" :options="generateQListOpt" :modelValue="inputData.qTAG" @update:modelValue="(res) => {
                inputData.qTAG = res
            }"></SingleSelect>
        </div>
        
        <div v-if="Question && (Question?.data.question_type_id == 2 || Question?.data.question_type_id == 3)" >
            
            <MultipleChoice_AC :modelValue="form" :question="Question" @update:modelValue="(res) => {
                form.value = res
            }"></MultipleChoice_AC>
        </div>

        <div v-if="Question && (Question?.data.question_type_id == 4 || Question?.data.question_type_id == 5)" >
            <jet-label class="text-xs" value="Value" />
            <textbox_AC :modelValue="form" :question="Question" @update:modelValue="(res) => {
                form.value = res
            }"></textbox_AC>
        </div>
    </div>
</div>
</template>

<script setup>
import JetLabel from "@/Jetstream/Label.vue";
import { ref, onMounted, computed, watch } from 'vue'
import SingleSelect from '@/S4/SingleSelect.vue';
import MultipleChoice_AC from '@/Pages/Questions/modals/logics/autocode/multipleChoice.vue'
import textbox_AC from '@/Pages/Questions/modals/logics/autocode/textbox.vue'
import { convertVariable, getQsInfo } from '@/Pages/Questions/modals/logics/composables/questionDetails.js'

const props = defineProps({
    logic: { required: true }
})

const Question = ref(null)

const form = ref({
    variable: '',
    value: '',
    text: ''
})

const emits = defineEmits(["update:autocode"])

watch(() => form.value, () => {
    emits("update:autocode", JSON.parse(JSON.stringify(form.value)))
}, { deep: true })

const inputData = ref({
    qTAG: null
})

const generateQListOpt = computed(() => {
    let list = []
    getQsInfo([1, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21]).forEach((obj) => {
        list.push({
            value: obj.TAG,
            text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs'>" + obj.TAG + "</span> " + obj.data.question
        })
    })
    return list
})

watch(() => inputData.value.qTAG, () => {
    Question.value = getQsInfo().find((obj) => {
        return obj.TAG == inputData.value.qTAG
    })
    // Update autocode payload when target question changes
    if (inputData.value.qTAG) {
        form.value.variable = inputData.value.qTAG
        // Reset dependent values to avoid stale state from previous question
        form.value.value = ''
        form.value.text = ''
    }
})

onMounted(() => {
    if (props.logic.autocode) {
        Object.assign(form.value, props.logic.autocode)
    }

    if (form.value.variable != "") {
        Question.value = convertVariable(form.value)
        inputData.value.qTAG = Question.value ? Question.value.TAG : ""
    } else {
        Question.value = getQsInfo().find((obj) => obj.TAG == inputData.value.qTAG) || null
        inputData.value.qTAG = ""
    }
})







</script>