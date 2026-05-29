<script setup>
import JetLabel from '@/Jetstream/Label.vue';
import VueFormMultiselect from '@/S4/vueform/Multiselect.vue';
import Switch from '@/S4/Switch.vue';
import { onMounted, ref, inject, watch, defineEmits } from 'vue'
import { usePage } from "@inertiajs/vue3";
import SingleSelect from '@/S4/SingleSelect.vue';
import helpers from '@/helpers.js'
import { S4Button } from '@/S4';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus } from '@fortawesome/free-solid-svg-icons'


const faIcon = {
    add : faPlus,
}


const { assign } = helpers()

const props = defineProps({
    logic_key: {
        default: 'choices'
    },
    label: {
        default: 'Select choices to hide'
    },
    placeholder: {
        default: 'Click to select choices'
    },
    question: Object
})

const list = ref([])

const logic = inject('logic')
const LogicData = ref([]) 

const settings = ref({
    enableHideAll : false,
    enableHideItemsAll : false
})

const hideall_data = ref({
    questions : []
})
const hideall_form = ref({
    question : null
})

const hideitems_data = ref({
    questions : []
})
const hideitems_form = ref({
    question : null
})

const emits = defineEmits(['addmulti:skiplogic'])

// Expose the generate function to parent component
defineExpose({
    generate: () => hideall_fn.generate(),
    generateItems: () => hideitems_fn.generate()
})
const hideall_fn = {
    generateQlist : ()=>{
        let list = []

        let pqs = usePage().props.questions.filter((res)=>{
            return ((res.order < props.question.order) && 
               (res.question_type_id == 2 || res.question_type_id == 3)
            )
        })

        pqs.forEach((question, index)=> {
            //let val = parseInt(question.order) + parseInt(parseInt(usePage().props.questionnaire.setup.variable_start)) - 1
            let val = question.order
            list.push({
                value: val,
                text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs'>Q"+(parseInt(question.order) + parseInt(parseInt(usePage().props.questionnaire.setup.variable_start)) - 1) + "</span> : " +question.question
            })
        });
        hideall_data.value.questions = list
        if (hideall_form.value.question == null && list.length > 0) {
            hideall_form.value.question = list[list.length - 1].value
        }
    },
    generate : ()=>{
        let targetQ = usePage().props.questions.find((obj)=>{
            return obj.order == hideall_form.value.question
        })
        

        //question_type_id
        //setup.selectMultiple
            // {
            // method: 'process',
            // left_operator: '',
            // var: '',
            // operator: '==',
            // val: '',
            // conditions: []
            // } 
        let choicesList = []
        let qOrder = parseInt(targetQ.order) + parseInt(parseInt(usePage().props.questionnaire.setup.variable_start) - 1)

        targetQ.choices.forEach((choice, index)=> {

         //if multiplechoice
         
            let variable = ''
            let val = ''
            let choiceIndex = choice?.index || index + 1
            let choiceValue = ''
            if(targetQ.setup.selectMultiple) {//if multiselect
                choiceValue = choice?.checkboxScore.active || 1
                variable = "Q"+qOrder+"_"+choiceIndex
                val = choiceValue 
            }else{
                choiceValue = choice?.score || choice?.index || index + 1
                variable = "Q"+qOrder//if single select
                val = choiceValue 
            }

            let conditions = [{
                method: 'process',
                left_operator: '&&',
                var: variable,
                operator: '!=',
                val: val,
                conditions: [],
                source: '1' // Default to question source
            }]

            let dataLogic = assign({
                action : 'hide_choices',
                conditioning : 'conditions',
                conditions: conditions,
                custom : null,
                custom_action : null,
                jump_to: 1,
                trigger: 'enter',
                value : [choiceIndex]
            })
            choicesList.push(dataLogic)

        })
        console.log("ChoiceList", choicesList)
        emits('multiadd:skiplogic', choicesList)
        settings.value.enableHideAll = false
    }
}

const hideitems_fn = {
    generateQlist : ()=>{
        let list = []

        let allQuestions = usePage().props.questions || []
        console.log("Hide items - Current question order:", props.question.order)
        
        let pqs = allQuestions.filter((res)=>{
            const isBefore = res.order < props.question.order
            // Use the same filtering logic as Criteria.vue for consistency
            const hasValidType = (res.question_type_id >= 2 && res.question_type_id <= 5) ||
                res.question_type_id == 16 ||
                res.question_type_id == 15 ||
                res.question_type_id == 8 ||
                res.question_type_id == 7
            
            // Check if question has items (for textbox, rating types) OR choices (for multiple choice)
            const hasItems = !!(res.items && res.items.length > 0)
            const hasChoices = !!(res.choices && res.choices.length > 0)
            const hasContent = hasItems || hasChoices
            
            if (isBefore) {
                console.log(`Q${res.order}: type=${res.question_type_id}, hasValidType=${hasValidType}, hasItems=${hasItems}, hasChoices=${hasChoices}`)
            }
            
            // Only include questions that have either items or choices
            return isBefore && hasValidType && hasContent
        })
        
        console.log("Filtered questions for hide items:", pqs.length)

        pqs.forEach((question, index)=> {
            let val = question.order
            list.push({
                value: val,
                text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs'>Q"+(parseInt(question.order) + parseInt(parseInt(usePage().props.questionnaire.setup.variable_start)) - 1) + "</span> : " +question.question
            })
        });
        hideitems_data.value.questions = list
        console.log("Hide items questions generated:", list.length, "questions found")
        if (hideitems_form.value.question == null && list.length > 0) {
            hideitems_form.value.question = list[list.length - 1].value
        }
    },
    generate : ()=>{
        let targetQ = usePage().props.questions.find((obj)=>{
            return obj.order == hideitems_form.value.question
        })
        
        if (!targetQ) {
            console.error("Target question not found")
            return
        }
        
        // Check if question has items or choices
        const hasItems = !!(targetQ.items && targetQ.items.length > 0)
        const hasChoices = !!(targetQ.choices && targetQ.choices.length > 0)
        
        if (!hasItems && !hasChoices) {
            console.error("Target question has no items or choices:", targetQ)
            return
        }
        
        let itemsList = []
        let qOrder = parseInt(targetQ.order) + parseInt(parseInt(usePage().props.questionnaire.setup.variable_start) - 1)

        // Handle items-based questions (textbox, rating types)
        if (hasItems) {
            targetQ.items.forEach((item, index)=> {
            let variable = ''
            let val = ''
            let itemIndex = item?.index || index + 1
            let itemValue = ''
            
            if(targetQ.question_type_id == 5 || targetQ.question_type_id == 7) { // Multiple textbox or constant sum
                itemValue = item?.score || item?.index || index + 1
                variable = "Q"+qOrder+"_"+itemIndex
                val = itemValue 
            } else if(targetQ.question_type_id == 8 || targetQ.question_type_id == 15 || targetQ.question_type_id == 16) { // Rating types
                itemValue = item?.score || item?.index || index + 1
                variable = "Q"+qOrder+"_"+itemIndex
                val = itemValue 
            } else if(targetQ.question_type_id == 4) { // Single textbox
                variable = "Q"+qOrder
                val = itemValue 
            } else { // Other types with items
                itemValue = item?.score || item?.index || index + 1
                variable = "Q"+qOrder+"_"+itemIndex
                val = itemValue 
            }

            let conditions = [{
                method: 'process',
                left_operator: '&&',
                var: variable,
                operator: '!=',
                val: val,
                conditions: [],
                source: '1' // Default to question source
            }]

            let dataLogic = assign({
                action : 'hide_items',
                conditioning : 'conditions',
                conditions: conditions,
                custom : null,
                custom_action : null,
                jump_to: 1,
                trigger: 'enter',
                value : [itemIndex]
            })
            itemsList.push(dataLogic)
            })
        }
        
        // Handle choices-based questions (multiple choice)
        // For multiple choice questions, we generate logic to hide items based on choice selections
        if (hasChoices) {
            // Get the current question's items to hide
            const currentQuestionItems = props.question.items || []
            
            if (currentQuestionItems.length === 0) {
                console.error("Current question has no items to hide")
                return
            }
            
            // For each choice in the previous question, create logic to hide all items
            targetQ.choices.forEach((choice, index)=> {
                let variable = ''
                let val = ''
                let choiceIndex = choice?.index || index + 1
                let choiceValue = ''
                
                if(targetQ.setup.selectMultiple) { // Multiple choice - multiple answer
                    choiceValue = choice?.checkboxScore?.active || 1
                    variable = "Q"+qOrder+"_"+choiceIndex
                    val = choiceValue 
                } else { // Multiple choice - single answer
                    choiceValue = choice?.score || choice?.index || index + 1
                    variable = "Q"+qOrder
                    val = choiceValue 
                }

                // Create logic for each item in the current question
                currentQuestionItems.forEach((item, itemIndex) => {
                    let itemIndexValue = item?.index || itemIndex + 1
                    
                    let conditions = [{
                        method: 'process',
                        left_operator: '&&',
                        var: variable,
                        operator: '!=',
                        val: val,
                        conditions: [],
                        source: '1' // Default to question source
                    }]

                    let dataLogic = assign({
                        action : 'hide_items',
                        conditioning : 'conditions',
                        conditions: conditions,
                        custom : null,
                        custom_action : null,
                        jump_to: 1,
                        trigger: 'enter',
                        value : [itemIndexValue]
                    })
                    itemsList.push(dataLogic)
                })
            })
        }
        
        console.log("ItemsList", itemsList)
        emits('multiadd:skiplogic', itemsList)
        settings.value.enableHideItemsAll = false
    }
}


watch(LogicData,()=>{
    //alert("changing...")
    logic.value.value = LogicData.value 
})

watch(()=>logic.value,()=>{
    LogicData.value = logic.value.value || []
},{deep: true})

// Watch for changes in logic_key to regenerate questions list
watch(()=>props.logic_key,()=>{
    if (props.logic_key === 'items') {
        hideitems_fn.generateQlist()
    } else if (props.logic_key === 'choices') {
        hideall_fn.generateQlist()
    }
})

// Also watch for changes in the injected logic action
watch(()=>logic.value?.action, (newAction) => {
    if (newAction === 'hide_items' && props.logic_key === 'items') {
        hideitems_fn.generateQlist()
    } else if (newAction === 'hide_choices' && props.logic_key === 'choices') {
        hideall_fn.generateQlist()
    }
})

onMounted(()=>{
    console.log("logic Val", logic.value)
    // Emit initial value
    emits('update:hideAll', settings.value.enableHideAll)
    if (props.logic_key == "choices") {
        props.question[props.logic_key].forEach((choice, index)=> {
            list.value.push({
                value: choice?.index ? choice.index : index++,
                // label: choice.name == null ? `${choice?.index ? choice.index : index++}. ${choice.src}` : `${choice?.index ? choice.index : index++}. ${choice.name}`
                label : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(choice?.index || index + 1)+'</span> '+(choice.name || 'Choice')
            })
        });
    } else if (props.logic_key == "items") {
        props.question[props.logic_key].forEach((items, index)=> {
            let eIndex = items?.index ? items.index : parseInt(index) + 1
            list.value.push({
                value: eIndex,
                label: items?.equivalent !== undefined
                        ? items.equivalent == null || items.equivalent == ''
                            ? `${eIndex}. ${items.name}`
                            : `${eIndex}. ${items.equivalent}`
                        : items?.statementA
                            ? `${eIndex}. ${items.statementA + '---' + items.statementB}`
                            : `${eIndex}. ${items.name}`
            })
        });
    } else {        
        props.question[props.logic_key].forEach((choice, index)=> {

            // using array index for matrix rows and columns
            if (props.logic_key == "rows" || props.logic_key == "columns") {
                list.value.push({
                    value: index+1,
                    //label: `${index+1}. ${choice.name}`
                    label : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(choice?.index || index + 1)+'</span> '+(choice.name || 'Choice')
                })
            } else {
                list.value.push({
                    value: choice?.index ? choice.index : index+1,
                    //label: `${index+1}. ${choice.name}`
                    label : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(choice?.index || index + 1)+'</span> '+(choice.name || 'Choice')
                })
            } 
        });
    }

    LogicData.value = logic.value?.value || []
    hideall_fn.generateQlist()
    hideitems_fn.generateQlist()
})

</script>
<template>
    <div class="grid grid-cols-1 gap-2 pb-4">
        <div v-if="!settings.enableHideAll">
            <jet-label class="text-lg font-normal pb-1" v-html="label"></jet-label>
            <vue-form-multiselect size="sm"
                :placeholder="placeholder"
                :options="list"
                v-model="LogicData"
            />
        </div>

        <div v-if="hideall_data.questions.length > 0 && (props.logic_key == 'choices')">
            <Switch
            :modelValue="settings.enableHideAll" 
            @update:modelValue="(res)=>{settings.enableHideAll = res; emits('update:hideAll', res)}"
            label="Hide choices based on previous question"></Switch>
        </div>

        <div v-if="hideitems_data.questions.length > 0 && (props.logic_key == 'items')">
            <Switch
            :modelValue="settings.enableHideItemsAll" 
            @update:modelValue="(res)=>{settings.enableHideItemsAll = res; emits('update:hideItemsAll', res)}"
            label="Hide items based on previous question"></Switch>
        </div>
        
        
        <div v-if="settings.enableHideAll" class="pl-[43px] -mt-2 pb-2">
            <!-- <jet-label class="font-bold text-xl">Criteria</jet-label> -->
            <div  class="flex items-end gap-1 max-w-[800px] ">
                <div>
                <div><jet-label class="text-sm whitespace-nowrap" >Select Question : </jet-label></div>
                <SingleSelect 
                    size="sm"
                    :options="hideall_data.questions" 
                    :modelValue="hideall_form.question"
                    @update:modelValue="(res)=>{
                        hideall_form.question = res
                    }"
                ></SingleSelect>
                </div>
                <!-- <s4-button :theme="'ssrAddColRow'" class="!bg-[var(--txtcolor)] text-white text-xs px-2 py-1"
                    @click="hideall_fn.generate">
                        <font-awesome-icon
                        class="mr-1"
                        :icon="faIcon.add" />
                        <span>Generate</span>
                </s4-button> -->

            </div>
        </div>

        <div v-if="settings.enableHideItemsAll" class="pl-[43px] -mt-2 pb-2">
            <div  class="flex items-end gap-1 max-w-[800px] ">
                <div>
                <div><jet-label class="text-sm whitespace-nowrap" >Select Question : </jet-label></div>
                <SingleSelect 
                    size="sm"
                    :options="hideitems_data.questions" 
                    :modelValue="hideitems_form.question"
                    @update:modelValue="(res)=>{
                        hideitems_form.question = res
                    }"
                ></SingleSelect>
                </div>
            </div>
        </div>
    </div>
</template>
