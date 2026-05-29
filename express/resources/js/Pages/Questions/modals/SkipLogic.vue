<script setup>
// FIXED: Automatic variable rebasing was causing user's logic conditions to change
// from "Q2!=1" to "Q1!=1" when the modal was opened/closed. This has been
// disabled to preserve user's intended logic. Users should manually control
// when variables need to be rebased.

import JetDialogModal from '@/Jetstream/DialogModal.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetDropdown from '@/Jetstream/Dropdown.vue';
import { S4InputSelect, S4Button, S4List, S4Table, S4Input, S4Checkbox } from '@/S4';
import HideSelected from './logics/HideSelected.vue';
import autoCode from './logics/autocode/index.vue'
import JumpTo from './logics/JumpTo.vue';
import CriteriaParent from './logics/Criteria.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEdit, faSpinner, faSave, faTrashAlt, faCopy, faPlus, faTimesCircle } from '@fortawesome/free-solid-svg-icons'
import { onMounted, onBeforeUnmount, ref, provide, watch, computed } from 'vue'
import { useForm, usePage, router } from "@inertiajs/vue3";
import helpers from '@/helpers.js'
import prism_editor from '@/prism_editor.js'
import SingleSelect from '@/S4/SingleSelect.vue';
import question from "/public/images/question.svg";
import Tooltip from "@/S4/Tooltip.vue";
import Switch from '@/S4/Switch.vue';
import skiplogicList from './logics/skiplogicList.vue';
import DeleteLogic from './DeleteLogic.vue';
import DeleteAllLogicModal from './DeleteAllLogicModal.vue';


import { add } from 'lodash';


const emit = defineEmits(['close'])
const props = defineProps({
    modal: Object
})

/* helpers start */
const { assign, toast, random_str } = helpers()
const { highlighter, PrismEditor } = prism_editor()
/* helpers end */

/* data start */
const initialLogic = {
    trigger: 'leave',
    jump_to: 1,
    action: 'screenout',
    conditioning: 'conditions',// or custom
    value: [],
    conditions: [],
    custom: '',
    custom_action: '',
    autocode : {
        variable : '',
        value : '',
        text : '' 
    }
}

const initialSelected = {
    index: -1,
    trigger: 'leave'
}

const logic = ref({});
// Add current question order to logic context for skip filtering
const updateLogicContext = () => {
    if (props.modal?.data?.order) {
        logic.value.currentQuestionOrder = props.modal.data.order;
    }
    // Ensure trigger is also available in logic context
    if (logic.value?.trigger) {
        logic.value.trigger = logic.value.trigger;
    }
};
provide('logic', logic)

const selected = ref({});
const logicChoices = ref({});
const enterSelected = ref([]);
const enterSelectedFinal = ref([]);
const leaveSelected = ref([]);
const leaveSelectedFinal = ref([]);
const isClicked = ref(true)
//const warningMessage = ref(false)
const hasNull = ref(false)
const hasNoChoice = ref(false)

const faIcon = {
    save: faSave,
    spinner: faSpinner,
    edit: faEdit,
    trash: faTrashAlt,
    copy: faCopy,
    add : faPlus,
    eks: faTimesCircle,
}

const list = ref({
    trigger: [{ value: 'enter', text: 'Enter' }, { value: 'leave', text: 'Leave' }],
    conditioning: [{ value: 'conditions', text: 'Conditions' }, { value: 'custom', text: 'Advanced Logic' }],
    action: [
        { value: 'hide_choices', text: 'Hide Choices', category: 'enter' },
        { value: 'hide_columns', text: 'Hide Columns', category: 'enter' },
        { value: 'hide_rows', text: 'Hide Rows', category: 'enter' },
        { value: 'hide_items', text: 'Hide Items', category: 'enter' },
        { value: 'skip', text: 'Skip Question', category: 'enter' },
        { value: 'jump_to', text: 'Jump to', category: 'enter' },
        { value: 'screenout', text: 'Disqualify', category: 'enter' },
        { value: 'quality_control', text: 'Quality control', category: 'enter' },
        { value: 'complete', text: 'Finish Questionnaire', category: 'enter' },
        { value: 'auto_code', text: 'Autocode', category: 'enter' },
        //{value: 'custom', text: 'Custom', category: 'enter'},
        /* {value: 'hide_item1', text: 'Hide Items 1', category: 'enter'},
        {value: 'hide_item2', text: 'Hide Items 2', category: 'enter'}, */

        { value: 'jump_to', text: 'Jump to', category: 'leave' },
        { value: 'screenout', text: 'Disqualify', category: 'leave' },
        { value: 'quality_control', text: 'Quality control', category: 'leave' },
        { value: 'complete', text: 'Finish Questionnaire', category: 'leave' },
        { value: 'auto_code', text: 'Autocode', category: 'leave' },
        //{value: 'custom', text: 'Custom', category: 'leave'},
    ]
})
const form = useForm({
    enter: [],
    leave: []
}, {
    resetOnSuccess: true,
})

const getEditIndex = ref(null)
const getLogicKey = ref(null)
const setActionDisable = ref(false)
const enableHideChoices = ref(false)
const enableHideItems = ref(false)
const fillSeriesVal = ref(false)
const fillSeriesVar = ref(false)
const hc_error = ref(false)
const edit_activated = ref()
const edit_activatedLKey = ref()

// Track and rebase variables when questionnaire variable_start changes
const previousVariableStart = ref(parseInt(usePage().props.questionnaire.setup.variable_start));
function isNumeric(str) {
    return !isNaN(str) && !isNaN(parseFloat(str));
}
function getQuestionVar(str) {
    if (!str) return null;
    const ch = str.split('_')[0];
    const q = ch.match(/^Q\d/) ? ch.slice(1) : ch;
    return isNumeric(q) ? parseInt(q) : null;
}
function getVarSuffix(str) {
    if (!str) return '';
    const parts = str.split('_');
    if (parts.length <= 1) return '';
    return '_' + parts.slice(1).join('_');
}
function rebaseVarString(varStr, oldStart, newStart) {
    const currentVarNum = getQuestionVar(varStr);
    if (!currentVarNum || isNaN(oldStart) || isNaN(newStart)) {
        console.log(`    ⏭️ Skipping rebase of "${varStr}" (currentVarNum: ${currentVarNum}, oldStart: ${oldStart}, newStart: ${newStart})`);
        return varStr;
    }
    const suffix = getVarSuffix(varStr);
    const targetOrder = currentVarNum - oldStart + 1;
    if (!isNumeric(targetOrder) || targetOrder < 1) {
        console.log(`    ⏭️ Skipping rebase of "${varStr}" (invalid targetOrder: ${targetOrder})`);
        return varStr;
    }
    const newVarNum = newStart + targetOrder - 1;
    const newVarStr = 'Q' + newVarNum + suffix;
    console.log(`    🔄 Rebasing "${varStr}" → "${newVarStr}" (order: ${targetOrder})`);
    return newVarStr;
}
function rebaseConditionsArray(conditions, oldStart, newStart) {
    if (!Array.isArray(conditions)) return;
    conditions.forEach((cond) => {
        if (!cond) return;
        if (cond.method === 'process' && typeof cond.var === 'string' && cond.var) {
            cond.var = rebaseVarString(cond.var, oldStart, newStart);
        }
        if (Array.isArray(cond.conditions) && cond.conditions.length) {
            rebaseConditionsArray(cond.conditions, oldStart, newStart);
        }
    });
}
function rebaseAllSkipLogicVariables(oldStart, newStart, saveToBackend = false) {
    console.log(`🔄 Rebasing skip logic variables from ${oldStart} to ${newStart}, saveToBackend: ${saveToBackend}`);
    
    if (!props?.modal?.data?.setup?.advanceSkiplogic) {
        console.log('❌ No advanceSkiplogic data found');
        return;
    }
    
    let hasChanges = false;
    let changeCount = 0;
    
    ['enter', 'leave'].forEach((trigger) => {
        const list = props.modal.data?.setup?.advanceSkiplogic?.[trigger];
        if (!Array.isArray(list)) return;
        
        console.log(`Checking ${trigger} logic (${list.length} items):`);
        
        list.forEach((logicItem, index) => {
            if (logicItem?.conditioning === 'conditions') {
                const oldConditions = JSON.stringify(logicItem.conditions);
                console.log(`  Before rebase [${trigger}][${index}]:`, JSON.stringify(logicItem.conditions, null, 2));
                
                rebaseConditionsArray(logicItem.conditions, oldStart, newStart);
                
                const newConditions = JSON.stringify(logicItem.conditions);
                console.log(`  After rebase [${trigger}][${index}]:`, JSON.stringify(logicItem.conditions, null, 2));
                
                if (oldConditions !== newConditions) {
                    hasChanges = true;
                    changeCount++;
                    console.log(`  ✅ Changes detected in ${trigger}[${index}]`);
                } else {
                    console.log(`  ➖ No changes in ${trigger}[${index}]`);
                }
            }

            // Also rebase autocode target variable (e.g., Q6 → Q10) when start changes
            if (logicItem?.action === 'auto_code' && logicItem?.autocode?.variable) {
                const before = logicItem.autocode.variable;
                const currentNum = getQuestionVar(before);
                if (currentNum) {
                    const targetOrder = currentNum - oldStart + 1; // 1-based order
                    if (isNumeric(targetOrder) && targetOrder >= 1) {
                        const newVarNum = newStart + targetOrder - 1;
                        const newTAG = 'Q' + newVarNum;
                        if (newTAG !== before) {
                            logicItem.autocode.variable = newTAG;
                            hasChanges = true;
                            changeCount++;
                            console.log(`  🔄 Autocode variable rebase: ${before} → ${newTAG} [${trigger}][${index}]`);
                        }
                    }
                }
            }
        });
    });
    
    console.log(`🔍 Rebase summary: ${changeCount} items changed, hasChanges: ${hasChanges}`);
    
    // Save to backend if requested and there are changes
    if (saveToBackend && hasChanges && props?.modal?.data?.id) {
        
        // Use the same form structure as the normal save logic function
        const rebaseForm = useForm({
            enter: props.modal.data.setup.advanceSkiplogic.enter || [],
            leave: props.modal.data.setup.advanceSkiplogic.leave || []
        });
        
        const params = [
            usePage().props.questionnaire.code,
            props.modal.data.id
        ];
        
        // Use the same route and method as addEditLogic function
        rebaseForm.post(route('questions.logic', params), {
            preserveScroll: true,
            onStart: () => {
                console.log('Starting backend save for rebased logic...');
            },
            onSuccess: (page) => {
                console.log('✅ Backend save successful for rebased logic');
                console.log('Response:', page);
            },
            onError: (errors) => {
                console.error('❌ Failed to save rebased skip logic to backend:', errors);
            },
            onFinish: () => {
                console.log('Backend save request finished');
            }
        });
    } else {
        if (!saveToBackend) {
            console.log('Backend save skipped - saveToBackend is false');
        } else if (!hasChanges) {
            console.log('Backend save skipped - no changes detected');
        } else if (!props?.modal?.data?.id) {
            console.log('Backend save skipped - no question ID available');
        }
    }
}

watch(() => usePage().props.questionnaire.setup.variable_start, (newStart) => {
    const oldStart = previousVariableStart.value;
    const parsedNew = parseInt(newStart);
    const parsedOld = parseInt(oldStart);
    
    console.log(`🔄 Variable start changed: ${parsedOld} → ${parsedNew}`);
    
    // Only rebase if modal is open and there's an actual change
    if (!isNaN(parsedNew) && !isNaN(parsedOld) && parsedNew !== parsedOld && props.modal?.skip_logic) {
        console.log(`✅ Rebasing variables from ${parsedOld} to ${parsedNew} (modal is open)`);
        rebaseAllSkipLogicVariables(parsedOld, parsedNew, true); // Save to backend
        previousVariableStart.value = parsedNew;
    } else {
        if (!props.modal?.skip_logic) {
            console.log(`⏭️ Skipping rebase - modal is not open`);
        } else {
            console.log(`⏭️ Skipping rebase - newStart: ${parsedNew}, oldStart: ${parsedOld}, equal: ${parsedNew === parsedOld}`);
        }
        // Still update the reference even if we don't rebase
        previousVariableStart.value = parsedNew;
    }
});

// When opening the modal, ensure variables are at the current start
watch(() => props.modal?.skip_logic, (open) => {
    if (open) {
        // Update the previous variable start reference to current value
        // This prevents incorrect rebasing when modal reopens
        const currentStart = parseInt(usePage().props.questionnaire.setup.variable_start);
        if (!isNaN(currentStart)) {
            previousVariableStart.value = currentStart;
            console.log(`📝 Modal opened - Updated previousVariableStart to: ${currentStart}`);
        }
    }
});


const AddUpdateModalForm = ref({
    show : false,
})

const deleteLogicModal = ref({
    delete: false,
    data: null,
    processing: false
})

const deleteAllLogicModal = ref({
    delete: false,
    processing: false
})

const AdEditLogicModal_fn = {
    close : ()=>{
        AddUpdateModalForm.value.show = false  
        // Only reset if we're not editing existing logic
        if (selected.value.index < 0) {
            resetAll()
        }
    },
    open : ()=>{
        AddUpdateModalForm.value.show = true  
        // If we were editing logic, restore the editing state
        if (selected.value.index >= 0) {
            // Restore the logic data from the selected logic
            const trigger = selected.value.trigger;
            const index = selected.value.index;
            if (props.modal?.data?.setup?.advanceSkiplogic?.[trigger]?.[index]) {
                let oldLogicData = assign(props.modal.data.setup.advanceSkiplogic[trigger][index]);
                Object.assign(logic.value, oldLogicData);
                updateSelect(oldLogicData.action);
                updateLogicContext();
            }
        }
    }
}

/* data end */

onMounted(() => {
    // Early return if modal data is not available
    if (!props.modal?.data) {
        return
    }
    
    resetLogic()
    resetSelected()
    updateLogicContext()
    
    // Ensure advanceSkiplogic structure exists
    if (!props.modal?.data?.setup) {
        props.modal.data.setup = {}
    }
    if (!props.modal?.data?.setup?.advanceSkiplogic) {
        props.modal.data.setup.advanceSkiplogic = {
            enter: [],
            leave: []
        }
        console.log('🔧 Initialized empty advanceSkiplogic structure')
    } else {
        // Ensure both arrays exist
        if (!Array.isArray(props.modal.data.setup.advanceSkiplogic.enter)) {
            props.modal.data.setup.advanceSkiplogic.enter = []
            console.log('🔧 Initialized empty enter array')
        }
        if (!Array.isArray(props.modal.data.setup.advanceSkiplogic.leave)) {
            props.modal.data.setup.advanceSkiplogic.leave = []
            console.log('🔧 Initialized empty leave array')
        }
    }
    
    
    if (props.modal?.data?.setup?.advanceSkiplogic) {
        
        // Log sample logic items for debugging
        if (props.modal.data.setup.advanceSkiplogic.enter?.length > 0) {
           
        }
        if (props.modal.data.setup.advanceSkiplogic.leave?.length > 0) {
           
        }
        
        // Check for data integrity issues
        ['enter', 'leave'].forEach(trigger => {
            const logicArray = props.modal.data.setup.advanceSkiplogic[trigger]
            if (Array.isArray(logicArray)) {
                logicArray.forEach((logic, index) => {
                    if (!logic || typeof logic !== 'object') {
                        console.warn(`⚠️ Invalid logic at ${trigger}[${index}]:`, logic)
                    } else if (!logic.action || !logic.trigger) {
                        console.warn(`⚠️ Logic missing required fields at ${trigger}[${index}]:`, logic)
                    }
                })
            } else {
                console.warn(`⚠️ ${trigger} logic is not an array:`, logicArray)
            }
        })    
    } else {
        console.warn('❌ No advanceSkiplogic data found in modal')
    }
    
    // Disable automatic rebasing to preserve user's logic
    // The user should manually control when variables are rebased
    console.log('Automatic rebasing disabled on mount to preserve user logic');
    
    // Only update the previous variable start reference without rebasing
    const currentStart = parseInt(usePage().props.questionnaire.setup.variable_start);
    if (!isNaN(currentStart)) {
        previousVariableStart.value = currentStart;
    }
})


/* methods start */
const resetLogic = () => {
    edit_activated.value = null
    edit_activatedLKey.value = null
    logic.value = assign(initialLogic)
    isClicked.value = true
    
    // Clear criteria row refs when logic is reset
    criteriaRowRefs.value = []
    
    // Only update context if modal data is available
    if (props.modal?.data) {
        updateLogicContext() // Update context when logic is reset
    }
    //resetSelected()
}

const resetAll = ()=>{
    resetLogic()
    resetSelected()
}

const resetCriteriaLogic = () => {
    // edit_activated.value = null
    // edit_activatedLKey.value = null
    logic.value.condition = []
    //logic.value = assign(initialLogic)
    isClicked.value = true
    //resetSelected()
}


const resetSelected = () => {
    selected.value = assign(initialSelected)
}

const enterSel = () => {
    enterSelectedFinal.value = []
    for (let index = 0; index < enterSelected.value.length; index++) {
        if (enterSelected.value[index] == 1) enterSelectedFinal.value.push(index)
    }
}
const leaveSel = () => {
    leaveSelectedFinal.value = []
    for (let index = 0; index < leaveSelected.value.length; index++) {
        if (leaveSelected.value[index] == 1) leaveSelectedFinal.value.push(index)
    }
}
const clearSel = () => {
    enterSelected.value = []
    leaveSelected.value = []
    enterSelectedFinal.value = []
    leaveSelectedFinal.value = []
}
const delSel = () => {
    props.modal.data.setup.advanceSkiplogic["enter"] = props.modal.data.setup.advanceSkiplogic["enter"].filter(function (value, index) {
        return enterSelectedFinal.value.indexOf(index) == -1;
    })
    for (let index = 0; index < enterSelectedFinal.value.length; index++) toast.error(`Remove from the list`, { timeout: 2000 })

    props.modal.data.setup.advanceSkiplogic["leave"] = props.modal.data.setup.advanceSkiplogic["leave"].filter(function (value, index) {
        return leaveSelectedFinal.value.indexOf(index) == -1;
    })
    for (let index = 0; index < leaveSelectedFinal.value.length; index++) toast.error(`Remove from the list`, { timeout: 2000 })
    clearSel()
}
const delAll = () => {
    const totLogic = props.modal.data.setup.advanceSkiplogic["enter"].length + props.modal.data.setup.advanceSkiplogic["leave"].length
    props.modal.data.setup.advanceSkiplogic["enter"] = []
    props.modal.data.setup.advanceSkiplogic["leave"] = []
    for (let index = 0; index < totLogic; index++) toast.error(`Remove from the list`, { timeout: 2000 })
    clearSel()
}

const addCondtion = () => {
    isClicked.value = false
    logic.value.conditions.push({
        method: 'process',
        left_operator: '&&',
        var: '',
        operator: '==',
        val: '',
        conditions: [],
        source: '1' // Default to question source
    })
    // trigger criteria bridge update
    scheduleCriteriaBridges()
}

const addEditLogic = () => {
    return new Promise((resolve)=>{


            let params = [
                usePage().props.questionnaire.code,
                props.modal.data.id
            ];
            // console.log("formmmm", props.modal.data.setup.advanceSkiplogic)
            form.enter = props.modal.data.setup.advanceSkiplogic.enter;
            form.leave = props.modal.data.setup.advanceSkiplogic.leave;
            // console.log("QWERT::::: ", form)
            if (form.enter.includes(null) || form.leave.includes(null)) {
                // console.log("TRUE::::: ")
                if(form.enter.includes(null)){
                    form.enter = form.enter.filter(item => item !== null)
                }
                if(form.leave.includes(null)){
                    form.leave = form.enter.filter(item => item !== null)
                }
                //warningMessage.value = true
                finalSave(form, params).then(()=>{
                    resolve()
                })
            } else {
                // console.log("FALSE::::: ")
                finalSave(form, params).then(()=>{
                    resolve()
                })
            }
            // finalSave(form, params)
    })
}

const finalSave = (form, params) => {
    return new Promise((resolve)=>{
        form.post(route('questions.logic', params), {
            onSuccess: () => {
                //toast.success(`Question ${props.modal.method == 'add' ? 'added' : 'updated'}`, {
                // toast.success('Skiplogic updated', {
                //     timeout: 2000
                // })
                resolve(1)
            // close()
            },
        })
        isClicked.value = false

    })

}

const close = () => {

    form.reset();
    resetLogic()
    emit('close')
    getEditIndex.value = null
    getLogicKey.value = null
    setActionDisable.value = false
    enableHideChoices.value = false
    fillSeriesVal.value = false
    fillSeriesVar.value = false
    logicChoices.value = {}
    hc_error.value = false
    clearSel()
}

const updatActionDefault = () => {
      
    enableHideChoices.value = false
    fillSeriesVal.value = false
    fillSeriesVar.value = false
    logicChoices.value = {}
    hc_error.value = false
    if (logic.value.trigger == 'leave') {
        logic.value.action = 'screenout'
    } else {
        logic.value.action = 'hide_choices'
    }
}
const editLogic = (trigger, index) => {
    showEdit(index, trigger)
     
    // logic.value.conditions = assign(props.modal.data.setup.advanceSkiplogic[trigger][index].conditions);
    // logic.value.custom = assign(props.modal.data.setup.advanceSkiplogic[trigger][index].custom);
    // logic.value.custom_action = assign(props.modal.data.setup.advanceSkiplogic[trigger][index].custom_action);
    // logic.value.conditioning = assign(props.modal.data.setup.advanceSkiplogic[trigger][index].conditioning);
    // logic.value.trigger = trigger;
    // updateSelect(props.modal.data.setup.advanceSkiplogic[trigger][index].action)
    // logic.value.value = assign(props.modal.data.setup.advanceSkiplogic[trigger][index].value);
    // logic.value.jump_to = assign(props.modal.data.setup.advanceSkiplogic[trigger][index].jump_to);
    // logic.value.timestamp = assign(props.modal.data.setup.advanceSkiplogic[trigger][index]?.timestamp);
    // logic.value.uuid = assign(props.modal.data.setup.advanceSkiplogic[trigger][index]?.uuid);

    // console.log("index",index)
    // console.log("sssskiiipp",props.modal.data.setup.advanceSkiplogic[trigger])
    let oldLogicData = assign(props.modal.data.setup.advanceSkiplogic[trigger][index])
    Object.assign(logic.value,oldLogicData)
    updateSelect(oldLogicData.action)

    //alert(JSON.stringify(oldLogicData))

    //alert(logic.value.jump_to)
    selected.value.index = index;
    selected.value.trigger = trigger;

    AddUpdateModalForm.value.show = true

    // Set selected logic and show modal
    //selectedLogic.value = { trigger, index };
 
    // isEditModalVisible.value = true;
    updateLogicContext() // Update context when editing logic
}
const updateSelect = (action) => { //force to update the action selected
    enableHideChoices.value = false
    logic.value.action = action;
    fillSeriesVal.value = false
    fillSeriesVar.value = false
    logicChoices.value = {}
    hc_error.value = false
    updateLogicContext() // Update context when action changes
}

const openDeleteLogicModal = (trigger, index) => {
    deleteLogicModal.value.data = {
        trigger: trigger,
        index: index
    }
    deleteLogicModal.value.delete = true
}

const handleDeleteLogicConfirmed = ({trigger, index}) => {
    deleteLogicModal.value.processing = true
    const data = props.modal.data.setup.advanceSkiplogic[trigger].indexOf(props.modal.data.setup.advanceSkiplogic[trigger][index]);
    props.modal.data.setup.advanceSkiplogic[trigger].splice(data, 1);
    //toast.error(`Remove from the list`, { timeout: 2000 })
    addEditLogic().then(()=>{
        toast.error('Remove from the list', {
            timeout: 2000
        })
        resetLogic()
        resetSelected()
        deleteLogicModal.value.delete = false
        deleteLogicModal.value.processing = false
    }).catch(()=>{
        deleteLogicModal.value.processing = false
    })
}

const deleteForm = (trigger, index) => {
    openDeleteLogicModal(trigger, index)
}

const openDeleteAllLogicModal = () => {
    deleteAllLogicModal.value.delete = true
}

const handleDeleteAllLogicConfirmed = () => {
    deleteAllLogicModal.value.processing = true
    const totLogic = props.modal.data.setup.advanceSkiplogic["enter"].length + props.modal.data.setup.advanceSkiplogic["leave"].length
    props.modal.data.setup.advanceSkiplogic["enter"] = []
    props.modal.data.setup.advanceSkiplogic["leave"] = []
    
    addEditLogic().then(()=>{
        toast.error(`Removed ${totLogic} logic${totLogic !== 1 ? 's' : ''} from the list`, {
            timeout: 2000
        })
        resetLogic()
        resetSelected()
        clearSel()
        deleteAllLogicModal.value.delete = false
        deleteAllLogicModal.value.processing = false
    }).catch(()=>{
        deleteAllLogicModal.value.processing = false
    })
}

const valueDisplay = (logic) => {
    var displayValue = []
    // hide items
    if (logic.action == 'hide_items') {
        let selectedLogic = null;
        for (let index = 0; index < props.modal.data.items.length; index++) {
            if (props.modal.data.items[index]?.index) {
                if (props.modal.data.items[index].index == logic.value[0]) {
                    selectedLogic = props.modal.data.items[index];
                    break;
                }
            } else {
                if (index + 1 == logic.value[0]) {
                    selectedLogic = props.modal.data.items[index];
                    break;
                }
            }

        }

        if (selectedLogic?.validate !== undefined) { // this is used by qtype - 5, textbox multiple textbox
            displayValue.push({ label: `Choice ${selectedLogic.index}:`, value: selectedLogic.name });
        } else { // else

            if (selectedLogic?.equivalent !== undefined) {
                if (selectedLogic.equivalent == null) {
                    displayValue.push({ label: `Choice ${selectedLogic.index}:`, value: `<img class="float-left" src="${selectedLogic.name}" alt="${selectedLogic.name}" />` });
                } else {
                    displayValue.push({ label: `Choice ${selectedLogic.index}:`, value: selectedLogic.equivalent });
                }
            } else {
                let sIndex = selectedLogic?.index ? selectedLogic.index : "no index"
                if (selectedLogic?.statementA !== undefined) {
                    displayValue.push({ label: `Choice ${sIndex}:`, value: selectedLogic.statementA.substring(0, 40) + '...' });
                } else {
                    displayValue.push({ label: `Item ${sIndex}:`, value: selectedLogic.name.substring(0, 40) + '...' });
                }
            }

        }
    }
    // hide rows
    else if (logic.action == 'hide_rows') {
        for (var x in logic.value) {
            displayValue.push({ label: `Row ${logic.value[x]}:`, value: props.modal.data.rows[logic.value[x] - 1].name });

        }
    }
    // hide columns
    else if (logic.action == 'hide_columns') {
        for (var x in logic.value) {
            displayValue.push({ label: `Col ${logic.value[x]}:`, value: props.modal.data.columns[logic.value[x] - 1].name });

        }
    }
    // hide choices
    else if (logic.action == 'hide_choices') {
        let selectedLogic = null;
        for (let index = 0; index < props.modal.data.choices.length; index++) {
            if (props.modal.data.choices[index]?.index) {
                if (props.modal.data.choices[index].index == logic.value[0]) {
                    selectedLogic = props.modal.data.choices[index];
                    break;
                }
            } else {
                if (index + 1 == logic.value[0]) {
                    selectedLogic = props.modal.data.choices[index];
                    break;
                }
            }
        }

        if (selectedLogic && selectedLogic.name == null) {
            displayValue.push({ label: `Choice ${selectedLogic.index}:`, value: `<img class="float-left" src="${selectedLogic.src}" alt="${selectedLogic.src}" />` });
        } else if (selectedLogic && selectedLogic.name != null) {
            displayValue.push({ label: `Choice ${selectedLogic.index}:`, value: selectedLogic.name });
        } else {
            // hasNoChoice.value = true
            // console.log("hashasNoChoice")
        }
    }
    // jump to
    else if (logic.action == 'jump_to') {
        displayValue.push({ label: ``, value: `Question ${logic.jump_to}` });


    }
    // else etc.
    else {
        displayValue = [{ label: ``, value: "No data available" }]
    }
    return displayValue
}

const filterAdvanceSkipLogic = (advanceSkipLogic) => {
    let logic = {};
    logic["enter"] = [];

    for (var eLogic in advanceSkipLogic["enter"]) {
        let action_type = advanceSkipLogic["enter"][eLogic].action
        let logic_value = advanceSkipLogic["enter"][eLogic].value[0]
        let len = advanceSkipLogic["enter"][eLogic].value.length
        let push_value = advanceSkipLogic["enter"][eLogic]

        if (len) {
            if (action_type == "hide_choices") {
                for (var eChoice in props.modal.data.choices) {
                    if (props.modal.data.choices[eChoice]?.index) {
                        if (props.modal.data.choices[eChoice].index == logic_value) {
                            logic.enter.push(push_value);
                            break;
                        }
                    } else {
                        if (parseInt(eChoice) + 1 == parseInt(logic_value)) {
                            logic.enter.push(push_value);
                            break;
                        }
                    }
                }
            } else if (action_type == "hide_items") {
                for (var eItems in props.modal.data.items) {
                    if (props.modal.data.items[eItems]?.index) {
                        if (props.modal.data.items[eItems].index == logic_value) {
                            logic.enter.push(push_value);
                            break;
                        }
                    } else {
                        if (parseInt(eItems) + 1 == parseInt(logic_value)) {
                            logic.enter.push(push_value);
                            break;
                        }
                    }
                }
            } else if (action_type == "hide_rows") {
                for (var eRows in props.modal.data.rows) {

                    // using array index
                    if (parseInt(eRows) + 1 == parseInt(logic_value)) {
                        logic.enter.push(push_value);
                        break;
                    }

                    // using original index
                    // if (props.modal.data.rows[eRows]?.index) {
                    //     if(props.modal.data.rows[eRows].index == logic_value) {
                    //         logic.enter.push(push_value);
                    //         break;
                    //     }
                    // } else {
                    //     if(parseInt(eRows)+1 == parseInt(logic_value)) {
                    //         logic.enter.push(push_value);
                    //         break;
                    //     }
                    // }
                }
            } else if (action_type == "hide_columns") {
                for (var eCols in props.modal.data.columns) {

                    // using array index
                    if (parseInt(eCols) + 1 == parseInt(logic_value)) {
                        logic.enter.push(push_value);
                        break;
                    }

                    // using original index
                    // if (props.modal.data.columns[eCols]?.index) {
                    //     if(props.modal.data.columns[eCols].index == logic_value) {
                    //         logic.enter.push(push_value);
                    //         break;
                    //     }
                    // } else {
                    //     if(parseInt(eCols)+1 == parseInt(logic_value)) {
                    //         logic.enter.push(push_value);
                    //         break;
                    //     }
                    // }
                }
            }

        } else {
            logic.enter.push(push_value);
        }
    }
    logic["leave"] = advanceSkipLogic["leave"];
    return logic;
}

const copyLogic = (trigger, logic) => {
    let refLogic = assign(logic)
    Object.assign(refLogic,{uuid:random_str(6)})
    props.modal.data.setup.advanceSkiplogic[trigger].push(assign(refLogic));
    //toast.success(`Logic copied`, { timeout: 2000 })
    //addEditLogic()
    addEditLogic().then(()=>{
        toast.success('Logic copied', {
            timeout: 2000
        })
        resetLogic()
        resetSelected()
    })


}

// watch (logic.value, (change) => {
//     if (change || logicChoices.var == null || logicChoices.val == null) {
//         hasNull.value = true
//         console.log("hasNull !!")
//     } else {
//         hasNull.value = false
//     }
// })
watch(hasNull, (newVal) => {
    if (newVal) {
        setTimeout(() => {
            hasNull.value = false;
        }, 5000);
    }
});

watch(hasNoChoice, (newVal) => {
    if (newVal) {
        setTimeout(() => {
            hasNoChoice.value = false;
        }, 5000);
    }
});

// Watch for changes in modal data to update logic context
watch(() => props.modal?.data, () => {
    updateLogicContext()
}, { deep: true });

// Watch for changes in logic action to update context
watch(() => logic.value?.action, () => {
    updateLogicContext()
});

// Watch for changes in logic trigger to update context
watch(() => logic.value?.trigger, () => {
    updateLogicContext()
});


// Validation function to check for empty, blank, or null values
const validateLogicValues = (logicData) => {
    // Check if custom logic is empty
    if (logicData.conditioning === 'custom') {
        if (!logicData.custom || logicData.custom.trim() === '') {
            return { isValid: false, message: 'Custom logic cannot be empty' };
        }
    }
    
    // Check if conditions array is empty
    if (logicData.conditioning === 'conditions') {
        if (!Array.isArray(logicData.conditions) || logicData.conditions.length === 0) {
            return { isValid: false, message: 'At least one condition is required' };
        }
        
        // Recursively validate all conditions
        const validateCondition = (condition) => {
            if (!condition) return false;
            
            // Check if condition has empty values
            if (condition.method === 'process') {
                if (!condition.var || condition.var.trim() === '' || 
                    !condition.val || condition.val.toString().trim() === '') {
                    return false;
                }
            } else if (condition.method === 'segment') {
                // For segment conditions, validate all nested conditions
                if (!Array.isArray(condition.conditions) || condition.conditions.length === 0) {
                    return false;
                }
                return condition.conditions.every(validateCondition);
            }
            
            return true;
        };
        
        const hasInvalidCondition = logicData.conditions.some(condition => !validateCondition(condition));
        if (hasInvalidCondition) {
            return { isValid: false, message: 'All condition values must be filled' };
        }
    }
    
    return { isValid: true };
};

const saveLogic = () => {
     //console.log("THIS>>> ", logic.value)
 
     if(!logic.value.timestamp){
        Object.assign(logic.value,{timestamp:Date.now()})
     }
     if(!logic.value.uuid){
        Object.assign(logic.value,{uuid:random_str(6)})
     }
    
    // Validate logic values before saving
    const validation = validateLogicValues(logic.value);
    if (!validation.isValid) {
        hasNull.value = true;
        toast.error(validation.message, {
            timeout: 3000
        });
        return;
    }

    // Reset validation state
    hasNull.value = false;

    if (selected.value.index != -1) {
        if (logic.value.trigger != selected.value.trigger) {
            props.modal.data.setup.advanceSkiplogic[selected.value.trigger].splice(selected.value.index, 1)
            props.modal.data.setup.advanceSkiplogic[logic.value.trigger].push(assign(logic.value))
        } else {
            props.modal.data.setup.advanceSkiplogic[selected.value.trigger][selected.value.index] = assign(logic.value)
        }
    } else {
        props.modal.data.setup.advanceSkiplogic[logic.value.trigger].push(assign(logic.value));
    }

    edit_activated.value = null
    isClicked.value = true

    addEditLogic().then(()=>{
        toast.success('Skip Logic added', {
            timeout: 2000
        })

        resetLogic()
        resetSelected()
        AdEditLogicModal_fn.close()
    })
}

const multiAddLogic = (toAdd)=>{
    // Validate all logic items before adding
    const invalidItems = [];
    toAdd.forEach((choiceLogic, index) => {
        const validation = validateLogicValues(choiceLogic);
        if (!validation.isValid) {
            invalidItems.push({ index, message: validation.message });
        }
    });
    
    if (invalidItems.length > 0) {
        hasNull.value = true;
        toast.error(`Cannot add logic: ${invalidItems[0].message}`, {
            timeout: 3000
        });
        return;
    }
    
    toAdd.forEach((choiceLogic) => {
        let refLogic = assign(choiceLogic)
        Object.assign(refLogic,{uuid:random_str(6),timestamp:Date.now()})
        props.modal.data.setup.advanceSkiplogic[choiceLogic.trigger].push(refLogic);
    })
    addEditLogic().then(()=>{
        toast.success('Skip Logic added', {
            timeout: 2000
        })
        resetLogic()
        resetSelected()
    })

}

const hideSelectedRef = ref(null)

const handleGenerate = () => {
    // Call the actual generate function from the HideSelected component
    if (hideSelectedRef.value) {
        if (logic.value.action === 'hide_choices') {
            hideSelectedRef.value.generate()
        } else if (logic.value.action === 'hide_items') {
            hideSelectedRef.value.generateItems()
        }
    }
}

// ------------------------------
// Criteria top-level bridge connectors (Single -> Single outside groups)
// ------------------------------
const criteriaContainerRef = ref(null)
const criteriaRowRefs = ref([])
const criteriaSvgHeight = ref(0)
const criteriaTrunkPath = ref("")
const criteriaConnectorPaths = ref([])
const criteriaBranchDots = ref([])
let criteriaRO = null
let criteriaRafId = null
let criteriaPendingUpdate = false

function scheduleCriteriaBridges(){
    if(criteriaPendingUpdate) return
    criteriaPendingUpdate = true
    
    if(criteriaRafId){
        cancelAnimationFrame(criteriaRafId)
    }
    
    criteriaRafId = requestAnimationFrame(() => {
        computeCriteriaBridges()
        criteriaPendingUpdate = false
        criteriaRafId = null
    })
}

function setCriteriaRowRef(el, index){
    if(!el){
        // Element is being unmounted, remove from refs
        if(criteriaRowRefs.value[index]){
            criteriaRowRefs.value[index] = null
        }
        // Trigger bridge update when element is removed
        scheduleCriteriaBridges()
        return
    }
    criteriaRowRefs.value[index] = el
    scheduleCriteriaBridges() // immediate scheduling
}

function computeCriteriaBridges(){
    try{
        const wrapper = criteriaContainerRef.value
        if(!wrapper){
            criteriaTrunkPath.value = ""
            criteriaConnectorPaths.value = []
            criteriaBranchDots.value = []
            return
        }
        const wrect = wrapper.getBoundingClientRect()
        
        // Clean up the refs array to match the current conditions length
        const currentConditionsLength = logic.value.conditions?.length || 0
        if(criteriaRowRefs.value.length > currentConditionsLength){
            criteriaRowRefs.value.splice(currentConditionsLength)
        }
        
        const validRows = criteriaRowRefs.value.filter(Boolean)
        
        // Draw only when there are at least two rows (a bracket makes sense for top-level criteria)
        if(validRows.length < 2){
            criteriaTrunkPath.value = ""
            criteriaConnectorPaths.value = []
            criteriaBranchDots.value = []
            criteriaSvgHeight.value = wrect.height
            return
        }
        
        const rows = validRows.map(el=>{
            const inner = el.querySelector('.criteria') || el
            return inner.getBoundingClientRect()
        })
        
        // Additional safety check after DOM measurements
        if(rows.length < 2){
            criteriaTrunkPath.value = ""
            criteriaConnectorPaths.value = []
            criteriaBranchDots.value = []
            criteriaSvgHeight.value = wrect.height
            return
        }
        // Cache computed style and optimize calculations
        const containerPadding = parseInt(getComputedStyle(wrapper).paddingLeft || 24)
        const trunkX = Math.max(12, containerPadding - 6)
        
        // Single pass to compute all centers and bounds
        let minY = Infinity, maxY = -Infinity
        const centers = rows.map(r => {
            const y = (r.top - wrect.top) + r.height/2
            if(y < minY) minY = y
            if(y > maxY) maxY = y
            return { x: trunkX, y }
        })
        
        // Batch DOM writes
        criteriaTrunkPath.value = `M ${trunkX},${minY} L ${trunkX},${maxY}`
        criteriaBranchDots.value = centers
        
        // Pre-compute tick position and build all paths at once
        const tickX = trunkX + 16
        criteriaConnectorPaths.value = centers.map(c => `M ${trunkX},${c.y} L ${tickX},${c.y}`)
        criteriaSvgHeight.value = Math.max(wrect.height, maxY + 24)
    }catch(e){
        criteriaTrunkPath.value = ""
        criteriaConnectorPaths.value = []
        criteriaBranchDots.value = []
    }
}

// Watch for condition changes to trigger bridge updates
watch(() => logic.value.conditions, scheduleCriteriaBridges, { deep: true })
watch(() => logic.value.conditioning, scheduleCriteriaBridges)

onMounted(()=>{
    if(typeof ResizeObserver !== 'undefined'){
        criteriaRO = new ResizeObserver(scheduleCriteriaBridges)
        if(criteriaContainerRef.value){ criteriaRO.observe(criteriaContainerRef.value) }
    }
    window.addEventListener('resize', scheduleCriteriaBridges)
})

onBeforeUnmount(()=>{
    window.removeEventListener('resize', scheduleCriteriaBridges)
    if(criteriaRO){ try{ criteriaRO.disconnect() }catch(_){} }
    if(criteriaRafId){ cancelAnimationFrame(criteriaRafId) }
})


// const logicHideChoices = () => {

//     const hc_count = Object.keys(logicChoices.value).length

//     if (hc_count == 3) {

//         hc_error.value = false
//         const choiceLogic = {}

//         choiceLogic.action = "hide_choices"
//         choiceLogic.conditioning = "conditions"
//         choiceLogic.custom = ""
//         choiceLogic.custom_action = ""
//         choiceLogic.jump_to = 1
//         choiceLogic.trigger = "enter"


//         for (let index = 1; index <= props.modal.data.choices.length; index++) {

//             const varChoiceFillSeries = fillSeriesVar.value ? index : ''
//             const valChoiceFillSeries = fillSeriesVal.value ? logicChoices.value.val++ : logicChoices.value.val

//             choiceLogic.conditions = []
//             choiceLogic.conditions.push({
//                 method: 'process',
//                 left_operator: '&&',
//                 var: `${logicChoices.value.var}${varChoiceFillSeries}`,
//                 operator: `${logicChoices.value.operator}`,
//                 val: `${valChoiceFillSeries}`,
//                 conditions: []
//             })
//             choiceLogic.value = [index]
//             props.modal.data.setup.advanceSkiplogic[choiceLogic.trigger].push(assign(choiceLogic));
//         }

//         enableHideChoices.value = false
//         fillSeriesVal.value = false
//         fillSeriesVar.value = false
//         logicChoices.value = {}
//         resetLogic()
//         resetSelected()
//         toast.success(`Logic saved`, {
//             timeout: 2000
//         })

//     } else {
//         hc_error.value = true
//     }
// }

const editActivated = (index, logic_key) => {
    edit_activated.value = index
    edit_activatedLKey.value = logic_key
}

const removeCriteria = (index) => {
    const data = logic.value.conditions.indexOf(logic.value.conditions[index]);
    logic.value.conditions.splice(data, 1);
    
    // Clean up criteria refs when conditions are removed
    if(criteriaRowRefs.value.length > logic.value.conditions.length){
        criteriaRowRefs.value.splice(logic.value.conditions.length)
    }
    
    // Trigger bridge update
    scheduleCriteriaBridges()
}
const retAction = (x, y) => {
    var retA
    if (x == 'enter') {
        switch (y) {
            case 'hide_choices':
                retA = "Hide choices"
                break;
            case 'hide_columns':
                retA = "Hide columns"
                break;
            case 'hide_rows':
                retA = "Hide rows"
                break;
            case 'hide_items':
                retA = "Hide items"
                break;
            case 'skip':
                retA = "Skip question"
                break;
            case 'jump_to':
                retA = "Jump to"
                break;
            case 'screenout':
                retA = "Screenout"
                break;
            case 'quality_control':
                retA = "Quality control"
                break;
            case 'complete':
                retA = "Complete"
            case 'custom':
                retA = "Custom"
                break;
        }
    } else {
        switch (y) {
            case 'jump_to':
                retA = "Jump to"
                break;
            case 'screenout':
                retA = "Screenout"
                break;
            case 'quality_control':
                retA = "Quality control"
                break;
            case 'complete':
                retA = "Complete"
                break;
            case 'custom':
                retA = "Custom"
                break;
        }
    }
    return retA
}
const showEdit = (ind, key) => {
    getEditIndex.value = ind
    getLogicKey.value = key
    setActionDisable.value = true
}
const criteriaDel = () => {
    if (!logic.value.conditions.length) {
        getEditIndex.value = null
        getLogicKey.value = null
        setActionDisable.value = false
    }
}

const criteriaToString = (conditions) => {
    var string = ''
    for (const key in conditions) {
        if (key == 0) {
            string += '('
        } else {
            string += ' ' + conditions[key].left_operator + ' ('
        }
        if (conditions[key].method == 'process') {
            string += conditions[key].var + conditions[key].operator + conditions[key].val
        } else {
            string += criteriaToString(conditions[key].conditions)
        }
        string += ')'
    }
    return string
}

const headers = ref([
    {
        label: '#',
        class: "pl-10 py-2 font-normal w-1/12 rounded-tl-xl text-gray-700"
    },
    {
        label: 'TRIGGER',
        class: "pl-6 py-2 font-normal w-2/12 rounded-tl-xl"
    },
    {
        label: 'ACTION',
        class: "pl-6 py-2 font-normal w-3/12 rounded-tl-xl"
    },
    {
        label: 'VALUE(S)',
        class: "pl-6 py-2 font-normal w-5/12 rounded-tl-xl"
    },
    {
        label: 'CONDITION',
        class: "pl-6 py-2 font-normal w-4/12 rounded-tl-xl"
    },
    {
        label: '',
        class: "pl-6 py-2 font-normal w-2/12 rounded-tl-xl"
    }
])

const hc_headers = ref([
    {
        label: 'Conditions',
        class: "pl-6 py-2 font-semibold w-full rounded-tl-xl"
    }
])

// watch(form, (change) => {
//     if (change) {
//         console.log("FORMFORM:::: ", form)
//     }
// });

/* methods end */
const closeOnClickAway = false;
const filteredActions = computed(() => {
    return list.value.action.filter((action) => {
        if (action.value === 'hide_items') {
            return (
                props.modal.data.question_type_id === 4 ||
                props.modal.data.question_type_id === 5 ||
                props.modal.data.question_type_id === 6 ||
                props.modal.data.question_type_id === 7 ||
                props.modal.data.question_type_id === 8 ||
                props.modal.data.question_type_id === 9 ||
                props.modal.data.question_type_id === 10 ||
                props.modal.data.question_type_id === 15 ||

                props.modal.data.question_type_id === 16
            );
        }
        if (action.value === 'hide_choices') {
            return (
                props.modal.data.question_type_id === 2 ||
                props.modal.data.question_type_id === 3
            );
        }
        if (action.value === 'hide_columns' || action.value === 'hide_rows') {
            return props.modal.data.question_type_id === 11;
        }
        return true; // Include all other actions
    });
});

</script>
<template>

    <jet-dialog-modal :show="modal.skip_logic" @close="close()" maxWidth="7xl" :close-on-click-away="closeOnClickAway">
        <template #title>
            <span class="rounded-lg text-white text-lg font-bold bg-black p-2 -ml-3">Q{{
                parseInt(parseInt($page.props.questionnaire.setup.variable_start) + (parseInt(modal.data.order) - 1))
                }}</span> <span class="text-base text-[var(--txtcolor)] text-lg pl-1"> Skip Logic</span>
        </template>

        <template #content>
            



            <!-- <s4-table
                v-if="modal.data.setup.advanceSkiplogic.enter.length || modal.data.setup.advanceSkiplogic.leave.length"
                :headers="headers" class="max-h-[40%] overflow-y-auto w-full mb-5">
                <template #body>
                    <template
                        v-for="(advanceSkiplogic, logic_key) in filterAdvanceSkipLogic(modal.data.setup.advanceSkiplogic, modal.data)"
                        :key="logic_key" class="">
                        <tr :class="{ 'bg-blue-100': edit_activated == index && edit_activatedLKey == logic_key, '': '' }"
                            class="text-xs border-l border-r border-gray-100 text-slate-600 skip-list"
                            v-for="(logic, index) in advanceSkiplogic" :key="index">
                            <td class="pl-5 py-3" v-if="logic.trigger == 'enter'">
                                <div class=" flex items-center gap-2">
                                    <s4-checkbox class="cursor-pointer border-blue-100"
                                        v-model:checked="enterSelected[index]" @change="enterSel()" />
                                    {{ index + 1 }}
                                </div>
                            </td>
                            <td class="pl-5 py-3" v-else>
                                <div class=" flex items-center gap-2">
                                    <s4-checkbox class="cursor-pointer border-blue-100"
                                        v-model:checked="leaveSelected[index]" @change="leaveSel()" />
                                    {{
                                        filterAdvanceSkipLogic(modal.data.setup.advanceSkiplogic, modal.data).enter.length +
                                        1 + index
                                    }}
                                </div>
                            </td>
                            <td class="pl-6 py-3" v-if="logic.trigger == 'enter'">
                                <span class="bg-green-100 rounded-xl text-green-800 px-4 py-0.5">Enter</span>
                            </td>
                            <td class="pl-6 py-3" v-else>
                                <span class="bg-red-100 rounded-xl text-red-800 px-4 py-0.5">Leave</span>
                            </td>
                            <td class="pl-5 py-3">{{ retAction(logic.trigger, logic.action) }}</td>
                            <td class="pl-6 py-3">
                                <div v-for="(item, key) in valueDisplay(logic)" :key="key">
                                    <ul v-if="item.label === ''">
                                        <li>
                                            <div v-html="item.value"></div>
                                        </li>
                                    </ul>
                                    <ul v-else style="list-style-type: disc;">
                                        <li>
                                            <div v-html="item.value"></div>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td class="pl-5 py-3" v-if="logic.conditioning == 'conditions'">
                                <p style="width: 70%!important;">{{ criteriaToString(logic.conditions) }}</p>
                            </td>
                            <td class="pl-5 py-3" style="overflow-wrap: break-word;" v-else>
                                <p style="width: 70%!important;">{{ logic.custom }}</p>
                            </td>
                          
                            <td class="relative">
                                <div v-if="edit_activated != index || edit_activatedLKey != logic_key"
                                    class="flex items-center gap-3 text-xs cursor-pointer text-gray-400">
                                    <span class="hover:text-gray-500 underline"
                                        @click="editLogic(logic_key, index), showEdit(index, logic_key), editActivated(index, logic_key)">Edit</span>
                                    <span class="hover:text-blue-500 underline"
                                        @click="copyLogic(logic_key, logic)">Copy</span>
                                    <span class="hover:text-red-400 underline"
                                        @click="deleteForm(logic_key, index)">Delete</span>

                                </div>
                            </td>
                        </tr>
                    </template>
                </template>
            </s4-table> -->




            <!-- <div v-if="modal.data.setup.advanceSkiplogic.enter.length || modal.data.setup.advanceSkiplogic.leave.length"
                class="text-xs gap-5 flex ml-2 -mt-16 mb-10 relative z-50 text-slate-400">
                <JetDropdown align="top" width="48">
                    <template #trigger>
                        <span
                            class="px-4 py-1 mt-4 text-slate-500 inline-flex items-center cursor-pointer underline">Delete
                            options</span>
                    </template>

                    <template #content>
                        <a @click="clearSel()"
                            class=" text-sm text-slate-500 rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap"
                            href="#">Unselect all</a>
                        <a @click="delSel()"
                            class=" text-sm text-slate-500 rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap"
                            href="#">Delete selected</a>
                        <a @click="delAll()"
                            class="text-sm text-slate-500 rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap"
                            href="#">Delete all</a>
                    </template>
                </JetDropdown>
            </div> -->
            <!-- <div v-if="warningMessage" class="text-red-500 text-right text-sm">There's an Error Occurred!</div> -->






            <!-- <s4-table :headers="hc_headers" class="w-full mb-5"
                v-if="enableHideChoices == true && logic.conditioning == 'conditions' && logic.action == 'hide_choices'">
                <template #body>
                    <tr class="text-md border-b border-l border-r border-gray-50 text-slate-500">
                        <div class="p-2">
                            <div class="grid grid-cols-6 gap-1">
                                <div class="col-span-2">
                                    <div class="flex justify-between mb-1">
                                        <jet-label class="" value="Variable" />
                                    </div>
                                    <s4-input color="ssrGray" class="w-full mb-2"
                                        :placeholder="'Ex: Q or Q' + (modal.data.order - 1) + '_'" :theme="'theme3'"
                                        v-model="logicChoices.var">
                                    </s4-input>
                                    <div>
                                        <div class="text-sm">
                                            <label for="toogleAA" class="flex items-center cursor-pointer text-sm">
                                           
                                                <div class="relative">
                                              
                                                    <input id="toogleAA" type="checkbox" v-model="fillSeriesVar"
                                                        class="sr-only" />
                                                 
                                                    <div class="w-7 h-3 bg-gray-200 rounded-full shadow-inner"></div>
                                              
                                                    <div
                                                        class="dot absolute w-5 h-5 bg-white rounded-full shadow -left-1 -top-1 transition">
                                                    </div>
                                                </div>
                                           
                                                <div class="ml-3 text-gray-600">
                                                    Fill Series
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-span-2">
                                    <jet-label class=" mb-1" value="Operator" />
                                    <s4-input-select class="w-full text-sm" color="ssrGray" :theme="'theme3'" :lists="[
                                        { value: '==', text: '==' },
                                        { value: '!=', text: '!=' },
                                        { value: '>', text: '>' },
                                        { value: '<', text: '<' },
                                        { value: '>=', text: '>=' },
                                        { value: '<=', text: '<=' }
                                    ]" v-model="logicChoices.operator"></s4-input-select>
                                </div>
                                <div class="col-span-2">
                                    <div class="flex justify-between mb-1">
                                        <jet-label class="" value="Value" />
                                    </div>
                                    <s4-input color="ssrGray" class="w-full mb-2" placeholder="e.g. 1" :theme="'theme3'"
                                        v-model="logicChoices.val">
                                    </s4-input>
                                    <div>
                                        <div class="text-sm">
                                            <label for="toogleBB" class="flex items-center cursor-pointer text-sm">
                                            
                                                <div class="relative">
                                             
                                                    <input id="toogleBB" type="checkbox" v-model="fillSeriesVal"
                                                        class="sr-only" />
                                               
                                                    <div class="w-7 h-3 bg-gray-200 rounded-full shadow-inner"></div>
                                             
                                                    <div
                                                        class="dot absolute w-5 h-5 bg-white rounded-full shadow -left-1 -top-1 transition">
                                                    </div>
                                                </div>
                                             
                                                <div class="ml-3 text-gray-600">
                                                    Fill Series <span class="text-xs text-gray-400">(Only applicable for
                                                        integer value)</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </tr>
                </template>
            </s4-table> -->

            <!-- <div v-if="hc_error && enableHideChoices == true" class="text-xs text-red-400 -mt-3 mb-3 ml-2">Please fill
                out all
                fields</div>
            <s4-button
                v-if="enableHideChoices == true && logic.conditioning == 'conditions' && logic.action == 'hide_choices'"
                :theme="'ssrYellow'" class="mt-3 mb-5 text-xs" @click="logicHideChoices()">
                <span>
                    <span class="text-xs">GENERATE</span>
                </span>
            </s4-button> -->


            <!-- here... -->
             <div class="flex flex-col gap-2 w-full relative">
                <!-- :set="skCollection=filterAdvanceSkipLogic(modal.data.setup.advanceSkiplogic, modal.data)" -->
                <!-- <skiplogicList class="w-[400px]" -->
                <skiplogicList class="w-full"
               
                
                :set="skCollection=modal.data.setup.advanceSkiplogic"
                :collection="skCollection"
                :data="modal.data"
                @update:sk="(res)=>{
                    editLogic(res.trigger,res.index)
                }"
                @copy:sk="(res)=>{
                    copyLogic(res.sk.trigger,res.sk)
                }"
                @delete:sk="(res)=>{
                    deleteForm(res.trigger,res.index)
                }"
                @deleteAll:sk="()=>{
                    openDeleteAllLogicModal()
                }"
                @add:sk="()=>{
                     resetAll()
                     AdEditLogicModal_fn.open()
                }"
                ></skiplogicList>
                <!-- <div class="skiplogic-form relative w-full rounded-lg p-4 border w-[calc(100%-400px)]"> -->

                    <teleport to="body">
                            <jet-dialog-modal :show="AddUpdateModalForm.show" maxWidth="7xl" >
                                <template #title>
                                    <span class="rounded-lg text-white text-lg font-bold bg-black p-2 -ml-3">Q{{
                                        parseInt(parseInt($page.props.questionnaire.setup.variable_start) + (parseInt(modal.data.order) - 1))
                                        }}</span> 
                                        <span class="text-[var(--txtcolor)] text-xl pl-1" v-if="selected.index < 0"> New Logic Form</span>
                                        <span class="text-[var(--txtcolor)] text-xl pl-1" v-else> Update Logic Form</span>
                                </template>

                                <template #content>


                                        <div class="skiplogic-form relative w-full rounded-lg p-4 border w-full">
                                            <!-- <div class="col-span-6 mb-2" v-if="logic.action == 'custom'">
                                                <jet-label class="font-bold text-xs mb-1">Custom Action Script</jet-label>
                                                <prism-editor class="my-editor max-h-40v"
                                                    style="border-radius: 0.4rem!important; padding-top:0.7rem!important; padding-bottom:0.7rem!important; background-color:#02284D!important;"
                                                    :highlight="highlighter" line-numbers v-model="logic.custom_action"></prism-editor>
                                            </div> -->



                                            <div class="relative w-full ">
                                                    <!-- <jet-label class="font-bold text-xl" v-if="selected.index < 0"><strong>Add new logic</strong></jet-label>
                                                    <jet-label class="font-bold text-xl" v-else><strong>Update logic</strong></jet-label> -->

                                                <div class=" relative w-full max-w-full overflow-auto max-h-[calc(100vh-350px)]">

                                                    <div class="ActionGroup w-full">

                                                        <div class="flex flex-row items-end gap-1 mt-3 mb-5">
                                                            <div class="w-[100px]">
                                                                <div class="flex items-end">
                                                                    <jet-label class="font-bold text-xs mb-1">If</jet-label>
                                                                    <!-- <Tooltip :imgSrc="question" preset="Triggers" arrowRotate="180deg" xTransformClass="translate(-50%,40%)" arrowTransform="translate(50%,1070%)"></Tooltip> -->
                                                                </div>
                                                                <!-- <s4-input-select class="w-full text-sm" color="ssrGray" :lists="list.trigger"
                                                                    @change="updatActionDefault()" v-model="logic.trigger"></s4-input-select> -->
                                                                <SingleSelect size="sm" :options="list.trigger" :modelValue="logic.trigger" @update:modelValue="(res) => {
                                                                    logic.trigger = res
                                                                    updatActionDefault()
                                                                }"></SingleSelect>
                                                            </div>
                                                            <div class="w-[180px]">
                                                                <div class="flex items-end">
                                                                    <jet-label class="font-bold text-xs mb-1">Then</jet-label>
                                                                    <!-- <Tooltip :imgSrc="question" preset="Actions" arrowRotate="180deg" xTransformClass="translate(-50%,40%)" arrowTransform="translate(50%,1070%)"></Tooltip> -->
                                                                </div>
                                                                
                                                                <!-- <s4-input-select class="w-full text-sm" color="ssrGray"
                                                                    :lists="filteredActions.filter(action => action.category == logic.trigger)"
                                                                    v-model="logic.action" @change="updateSelect(logic.action)"></s4-input-select> -->
                                                                <SingleSelect size="sm" :options="filteredActions.filter(action => action.category == logic.trigger)"
                                                                    :modelValue="logic.action" @update:modelValue="(res) => {
                                                                        logic.action = res
                                                                        updateSelect(logic.action)
                                                                    }"></SingleSelect>
                                                            </div>
                                                            <div class="col-span-4">
                                                                <!-- <div class="flex items-end">
                                                                    <jet-label class="font-bold text-xs mb-1">Logic Type</jet-label>
                                                                    <Tooltip :imgSrc="question" preset="LogicType" arrowRotate="180deg" xTransformClass="translate(-50%,40%)" arrowTransform="translate(50%,1070%)"></Tooltip>
                                                                </div> -->
                                                                <!-- <s4-input-select class="w-full text-sm" color="ssrGray" :lists="list.conditioning"
                                                                    v-model="logic.conditioning"></s4-input-select> -->
                                                                <!-- <SingleSelect size="sm" :options="list.conditioning" :modelValue="logic.conditioning" @update:modelValue="(res) => {
                                                                    logic.conditioning = res
                                                                }"></SingleSelect> -->

                                                                <Switch
                                                                    :set="logicTypeSwitch = (logic.conditioning != 'conditions') ? true : false"
                                                                    :modelValue="logicTypeSwitch" 
                                                                    @update:modelValue="(res)=>{logic.conditioning = (res) ? 'custom' : 'conditions' }"
                                                                label="Advanced Logic"></Switch>
                                                                
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="Action2Group w-full" >

                                                            <autoCode v-if="logic.action == 'auto_code'" 
                                                                :question="modal.data"
                                                                :logic="logic"
                                                                @update:autocode="(res)=>{logic['autocode']=res}"
                                                            />

                                                            <hide-selected v-if="logic.action == 'hide_choices'" 
                                                                :key="'hide-choices-' + logic.action"
                                                                :question="modal.data"
                                                                :hasNoChoice="hasNoChoice" logic_key="choices" placeholder="Click to select choices"
                                                                @multiadd:skiplogic="(res)=>{multiAddLogic(res)}"
                                                                @update:hideAll="(res)=>{enableHideChoices = res}"
                                                                ref="hideSelectedRef"
                                                            />

                                                            <hide-selected v-else-if="logic.action == 'hide_items'" 
                                                                :key="'hide-items-' + logic.action"
                                                                :question="modal.data"
                                                                logic_key="items" placeholder="Click to select items"
                                                                @multiadd:skiplogic="(res)=>{multiAddLogic(res)}"
                                                                @update:hideItemsAll="(res)=>{enableHideItems = res}"
                                                                ref="hideSelectedRef"
                                                            />




                                                            <!-- <div v-if="logic.action == 'hide_choices' && logic.conditioning == 'conditions'" class="text-sm">
                                                                <Switch class="my-2" 
                                                                :modelValue="enableHideChoices" 
                                                                @update:modelValue="(res)=>{enableHideChoices = res}"
                                                                label="Hide choices based on previous question"></Switch>
                                                            </div> -->


                                                            <hide-selected v-else-if="logic.action == 'hide_columns'" :question="modal.data" logic_key="columns"
                                                                placeholder="Click to select columns" label="Columns <small>[hide all selected]</small>:" />

                                                            <hide-selected v-else-if="logic.action == 'hide_rows'" :question="modal.data" logic_key="rows"
                                                                placeholder="Click to select rows" label="Rows <small>[hide all selected]</small>:" />


                                                            <hide-selected v-else-if="logic.action == 'hide_item1'" :question="modal.data" logic_key="item1"
                                                                placeholder="Click to select items 1" label="Items 1 <small>[hide all selected]</small>:" />

                                                            <hide-selected v-else-if="logic.action == 'hide_item2'" :question="modal.data" logic_key="item2"
                                                                placeholder="Click to select items 2" label="Items 2 <small>[hide all selected]</small>:" />

                                                            <jump-to v-else-if="logic.action == 'jump_to'" :question="modal.data" :questions="$page.props.questions"
                                                                placeholder="Select Question" label="Jump to <small>[jump to selected question]</small>:" />                           
                                                    </div>




                                                    <!-- <div class="break w-full border border-top-[1px] border-gray-100 my-10"></div> -->
                                                    <div class="cretiriaGroup w-full" v-if="logic.conditioning == 'conditions'">
                                                        <jet-label v-if="enableHideChoices != true && enableHideItems != true" class="font-bold text-xl">Criteria</jet-label>
                                                        <div class="relative w-full pl-10" ref="criteriaContainerRef">
                                                            <svg class="absolute left-0 top-0 pointer-events-none" :style="{width:'100%', height: criteriaSvgHeight + 'px', zIndex: 0}">
                                                                <path v-if="criteriaTrunkPath" :d="criteriaTrunkPath" stroke="#CBD5E1" stroke-linecap="round" stroke-width="2" fill="none" />
                                                                <path v-for="(p, i) in criteriaConnectorPaths" :key="'ct-'+i" :d="p" stroke="#CBD5E1" stroke-linecap="round" stroke-width="2" fill="none" />
                                                                <circle v-for="(bp, i) in criteriaBranchDots" :key="'cb-'+i" :cx="bp.x" :cy="bp.y" r="3" fill="#CBD5E1" />
                                                            </svg>
                                                            <div class="relative z-10">
                                                                <div class="" v-for="(condition, index) in logic.conditions" :key="index" :ref="el => setCriteriaRowRef(el, index)">
                                                                    <div class="rounded-lg cretiriaList" style="">
                                                                        
                                                                        <criteria-parent @click="criteriaDel()"
                                                                            :variableStart="parseInt($page.props.questionnaire.setup.variable_start)"
                                                                            :question="modal.data" :questions="$page.props.questions" :deep="0"
                                                                            :condition="condition" :indexes="[index]" @resetLogicFromChild="resetCriteriaLogic" />
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="flex gap-1 pt-2" v-if="logic.conditioning == 'conditions'">
                                                                <s4-button v-if="enableHideChoices != true && enableHideItems != true" :theme="'ssrAddColRow'" class="!bg-[var(--txtcolor)] text-white text-xs px-2 py-1"
                                                                    @click="addCondtion()">
                                                                        <font-awesome-icon
                                                                        class="mr-1"
                                                                        :icon="faIcon.add" />
                                                                        <span v-if="logic.conditions.length > 0">Add Segment</span>
                                                                        <span v-else>Add Condition</span>
                                                                    
                                                                </s4-button>
                                                                <!-- <s4-button v-if="logic.conditions.length > 0" :theme="'ssrAddColRow'" class="font-bold !bg-green-500 text-white text-xs px-4 py-1"
                                                                    :disabled="form.processing"
                                                                    @click="saveLogic(), getEditIndex = null, getLogicKey = null, setActionDisable = false">
                                                                    <font-awesome-icon
                                                                    class="mr-1"
                                                                    :icon="faIcon.save" />

                                                                    <span v-if="getEditIndex == null">Save Logic</span>
                                                                    <span v-else>Update Logic</span>
                                                                </s4-button> -->
                                                                <!-- <s4-button v-if="logic.conditions.length > 0" :theme="'ssrAddColRow'" class="font-bold !bg-transparent text-red-400 hover:text-red-500 text-xs px-2 py-1"
                                                                    @click="resetAll(), getEditIndex = null, getLogicKey = null, setActionDisable = false">
                                                                    <font-awesome-icon
                                                                    class="mr-1"
                                                                    :icon="faIcon.eks" />

                                                                    Cancel
                                                                </s4-button> -->
                                                        </div>
                                                        <div v-if="hasNull == true" class="text-red-600 text-xs">Some Inputs are Empty. Try Again.</div>
                                                    </div>

                                                    <div class="customGroup w-full " v-else>
                                                        <div class="grid grid-cols-1 gap-2">
                                                        <div class="pb-5">
                                                            <jet-label class="font-bold text-xs"><span class="text-sm">Advanced Logic</span> <small>use $Q, e.g. ($Q2_1 + $Q2_2 > 10)</small></jet-label>
                                                            <prism-editor class="my-editor max-h-40v"
                                                                style="border-radius: 0.4rem!important; background-color:#02284D!important;"
                                                                :highlight="highlighter" line-numbers v-model="logic.custom"></prism-editor>
                                                        </div>
                                                        <div>
                                                            <!-- <s4-button :theme="'ssrAddColRow'" class="font-bold !bg-green-500 text-white text-xs px-4 py-1"
                                                            @click="saveLogic(), getEditIndex = null, getLogicKey = null, setActionDisable = false" :disabled="form.processing">
                                                                <font-awesome-icon
                                                                class="mr-1"
                                                                :icon="faIcon.save" />

                                                                Save Logic
                                                            </s4-button> -->
                                                            <!-- <s4-button :theme="'ssrAddColRow'" class="font-bold !bg-transparent text-red-400 hover:text-red-500 text-xs px-2 py-1"
                                                                @click="resetAll(), getEditIndex = null, getLogicKey = null, setActionDisable = false">
                                                                <font-awesome-icon
                                                                class="mr-1"
                                                                :icon="faIcon.eks" />

                                                                Cancel
                                                            </s4-button> -->
                                                        </div>
                                                        </div>

                                                    </div>


                                                    <!-- <div class="FooterGroup w-full">
                                                                <div class="flex gap-1" v-if="logic.conditions.length || logic.conditioning != 'conditions'">
                                                                </div>
                                                    </div> -->

                                                </div>
                                            </div>


                            

                                        </div>

                                </template>
                                <template #footer>


                                        <s4-button v-if="enableHideChoices != true && enableHideItems != true" :theme="'ssrAddColRow'" class="font-bold !bg-[var(--mcolor)] hover!bg-[var(--mcolor2)] disabled:!bg-gray-300 text-[var(--text-color)] text-xs px-4 py-1"
                                            :disabled="(form.processing) || !((logic.conditions.length > 0) || (logic.conditioning != 'conditions'))"
                                            @click="saveLogic(), getEditIndex = null, getLogicKey = null, setActionDisable = false">
                                            <font-awesome-icon
                                            class="mr-1 text-lg text-white"
                                            :icon="faIcon.add" />

                                            <span class="text-white" v-if="selected.index < 0">Add Logic</span>
                                            <span class="text-white" v-else>Update Logic</span>
                                        </s4-button>
                                        
                                        <s4-button v-if="enableHideChoices == true || enableHideItems == true" :theme="'ssrAddColRow'" class="font-bold !bg-[var(--mcolor)] hover!bg-[var(--mcolor2)] disabled:!bg-gray-300 text-[var(--text-color)] text-xs px-4 py-1"
                                            @click="handleGenerate">
                                            <span class="text-white">Generate</span>
                                        </s4-button>
                                        <s4-button :theme="'ssrCancel'" class="mr-3 text-red-500 hover:!text-red-600" @click="AdEditLogicModal_fn.close()">
                                            Cancel
                                        </s4-button>


                                        <!-- <s4-button :theme="'ssrOrange'" :disabled="form.processing || isClicked == false" :style="{
                                            backgroundColor: (form.processing || isClicked == false ? '#FEE592 !important' : '#FCCA27'),
                                            color: (form.processing || isClicked == false ? '#ffffff !important' : '#000000'),
                                            cursor: form.processing || isClicked == false ? 'not-allowed' : 'pointer'
                                        }" :title="form.processing || isClicked == false ? 'Click SAVE LOGIC First or CANCEL' : ''"
                                            @click="addEditLogic()">

                                            <template v-if="form.processing">
                                                <span class="pr-3 pl-3">
                                                    <font-awesome-icon :icon="faIcon.spinner" spin />
                                                    Wait
                                                </span>
                                            </template>
                                            <template v-else>
                                                <span class="pr-3 pl-3">
                                                    Save
                                                </span>
                                            </template>
                                        </s4-button> -->


                               </template>


                          </jet-dialog-modal>
                    </teleport>




            </div>

        </template>

        <template #footer>

        <s4-button :theme="'ssrAddColRow'" class="font-bold !bg-[var(--mcolor)] hover:!bg-[var(--mcolor2)] text-[var(--text-color)] disabled:!bg-gray-300  text-xs w-[170px] px-4 py-1"
            :disabled="(form.processing)"
            @click="()=>{
                resetAll()
             AdEditLogicModal_fn.open()
            }">
            <font-awesome-icon
            class="mr-2 text-lg text-white"
            :icon="faIcon.add" />
            <span class="text-white">Add New Rule</span>
        </s4-button>

                                 <!-- resetAll()
                     AdEditLogicModal_fn.open() -->
            <div class="text-right w-full">
            <s4-button :theme="'ssrCancel'" class="mr-3 !text-red-500 hover:!text-red-600" @click="close()">
                Close
            </s4-button>
            </div>
            <!-- <s4-button :theme="'ssrOrange'" :disabled="form.processing || isClicked == false" :style="{
                backgroundColor: (form.processing || isClicked == false ? '#FEE592 !important' : '#FCCA27'),
                color: (form.processing || isClicked == false ? '#ffffff !important' : '#000000'),
                cursor: form.processing || isClicked == false ? 'not-allowed' : 'pointer'
            }" :title="form.processing || isClicked == false ? 'Click SAVE LOGIC First or CANCEL' : ''"
                @click="addEditLogic()">

                <template v-if="form.processing">
                    <span class="pr-3 pl-3">
                        <font-awesome-icon :icon="faIcon.spinner" spin />
                        Wait
                    </span>
                </template>
                <template v-else>
                    <span class="pr-3 pl-3">
                        Save
                    </span>
                </template>
            </s4-button> -->


        </template>
    </jet-dialog-modal>

    <!-- Delete Logic Confirmation Modal - Teleported to body to appear on top -->
    <teleport to="body">
        <DeleteLogic 
            :modal="deleteLogicModal" 
            @close="deleteLogicModal.delete = false"
            @confirmed="handleDeleteLogicConfirmed"
        />
    </teleport>

    <!-- Delete All Logic Confirmation Modal - Teleported to body to appear on top -->
    <teleport to="body">
        <DeleteAllLogicModal 
            :modal="deleteAllLogicModal" 
            @close="deleteAllLogicModal.delete = false"
            @confirmed="handleDeleteAllLogicConfirmed"
        />
    </teleport>

</template>




<style scoped>
.test input:focus+label {
    color: red;
}

label:hover+input {
    color: red;
}


div :deep() img {
    max-height: 3vmin;
    margin: auto auto;
    position: relative;
}

.col {
    width: 11vmin;
}

.ssrBT {
    border: none;
    border-radius: 50%;
    width: 2.65rem;
    /* width:2rem;
    height:2rem; */
}

.ssrBT:hover {
    border: 1px solid;
    border-radius: 50%;
}

.cretDel:last-child {
    visibility: hidden;
}

.hideChoice {
    border-bottom-right-radius: 1rem;
    font-size: 0.9rem;
    margin-left: -0.28rem;
}

.ft {
    font-size: 1.5rem;
    padding: 0.2rem;
}

.bt {
    border-radius: 50%;
    width: 2rem;
    height: 2rem;
}


/*tooltip delete*/
.toolTipDel {
    opacity: 0;
    transition: 0.3s;
}

.prjBtnDel:hover>.toolTipDel {
    opacity: 1;
}


/*tooltip copy*/
.toolTipCopy {
    opacity: 0;
    transition: 0.3s;
}

.prjBtnCopy:hover>.toolTipCopy {
    opacity: 1;
}

/*tooltip edit*/
.toolTipEdit {
    opacity: 0;
    transition: 0.3s;
}

.prjBtnEdit:hover>.toolTipEdit {
    opacity: 1;
}

.logicBadge {
    display: inline-block;
    width: 1.38rem;
    height: 1.3rem;
    position: absolute;
    border-radius: 50%;
    text-align: center;
    margin-top: -0.75rem;
    font-size: 0.9rem;
}

.ttop {
    vertical-align: text-top;
    padding-top: 0.5rem;
}

/*blink edit logic list*/

.blinkLogic {
    animation: blinker 1s linear infinite;
}

@keyframes blinker {
    50% {
        opacity: 0;
    }
}

input:checked~.dot {
    transform: translateX(100%);
    /* background-color: var(--mcolor2); */
}

input:checked~.line {
    background-color: var(--mcolor2);
}

.skip-list:hover {
    background-color: rgb(241 245 249) !important;
}


 /* .cretiriaList /deep/ .criteria{
    background:red;
}

 .cretiriaList > /deep/ .criteria{
    background:transparent;
} */
</style>