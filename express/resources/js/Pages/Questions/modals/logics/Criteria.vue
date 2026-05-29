<template>
<!-- <div class="criteria"> -->
        <div class="criteria flex gap-1 flex-row" ref="rowRef">
                <div>
                    <jet-label class="text-xs" value="Logic Type" />
                    <SingleSelect
                        size="sm"
                        width="150px"
                        :options="[
                        { value: 'process', text: 'Single' },
                        { value: 'segment', text: 'Group Segment' },
                    ]"
                        :modelValue="condition.method"
                        @update:modelValue="(res) => {
                            condition.method = res
                        }"
                    ></SingleSelect>
                </div>
                <div v-if="indexes[indexes.length - 1] != 0" class="flex flex-col">
                    <jet-label class="text-xs w-[100px]" value="Logical Operator" />
                    <SingleSelect size="sm" :options="[
                        { value: '&&', text: 'AND' },
                        { value: '||', text: 'OR' },
                    ]" :modelValue="condition.left_operator" @update:modelValue="(res) => {
                        condition.left_operator = res
                    }"></SingleSelect>
                </div>

                <template v-if="condition.method == 'process'">
                <!-- 1A. SOURCE VARIABLE -->
                    <div class="flex flex-col">
                        <jet-label class="text-xs" value="Source" />
                        <SingleSelect size="sm" :options="[
                            { value: '1', text: 'Question' },
                            { value: '2', text: 'Custom' },
                            { value: '3', text: 'Language' },
                            { value: '4', text: 'Score' }
                        ]" :modelValue="source" @update:modelValue="(res) => {
                            source = res
                            props.condition.source = res // Persist source to condition data
                        }"></SingleSelect>
                    </div>
                

                <!-- 1B. QUESTION SOURCE -->
                <template v-if="source == 1">
                    <div class="flex flex-col">
                        <jet-label class="text-xs" value="Question" />
                        <SingleSelect size="sm" width="500px" :options="QuestionSource_fn?.getList?.() ?? []" :modelValue="QuestionSource"
                            @update:modelValue="(res) => {
                                QuestionSource = res
                        }"></SingleSelect>
                        <!-- Note: For 'skip' action, only questions before the current question are shown -->
                        <div v-if="logic.value?.action === 'skip'" class="text-xs text-blue-600 mt-1">
                            <i class="fas fa-info-circle"></i> Only showing questions before Q{{ props.question?.order }}
                        </div>
                        <!-- Note: For 'jump_to' action with 'enter' trigger, only questions before the current question are shown -->
                        <div v-if="logic.value?.action === 'jump_to' && logic.value?.trigger === 'enter'" class="text-xs text-blue-600 mt-1">
                            <i class="fas fa-info-circle"></i> Only showing questions before Q{{ props.question?.order }}
                        </div>
                        <!-- Note: For 'jump_to' action with 'leave' trigger, only questions from current question onwards are shown -->
                        <div v-if="logic.value?.action === 'jump_to' && logic.value?.trigger === 'leave'" class="text-xs text-blue-600 mt-1">
                            <i class="fas fa-info-circle"></i> Only showing questions from Q{{ props.question?.order }} onwards
                        </div>
                        <!-- Note: For 'screenout' action with 'enter' trigger, only questions before the current question are shown -->
                        <div v-if="logic.value?.action === 'screenout' && logic.value?.trigger === 'enter'" class="text-xs text-blue-600 mt-1">
                            <i class="fas fa-info-circle"></i> Only showing questions before Q{{ props.question?.order }}
                        </div>
                        <!-- Note: For 'hide_choices' action with 'enter' trigger, only questions before the current question are shown -->
                        <div v-if="logic.value?.action === 'hide_choices' && logic.value?.trigger === 'enter'" class="text-xs text-blue-600 mt-1">
                            <i class="fas fa-info-circle"></i> Only showing questions before Q{{ props.question?.order }}
                        </div>
                        <!-- Note: For 'quality_control' action with 'enter' trigger, only questions before the current question are shown -->
                        <div v-if="logic.value?.action === 'quality_control' && logic.value?.trigger === 'enter'" class="text-xs text-blue-600 mt-1">
                            <i class="fas fa-info-circle"></i> Only showing questions before Q{{ props.question?.order }}
                        </div>
                        <!-- Note: For 'auto_code' action with 'enter' trigger, only questions before the current question are shown -->
                        <div v-if="logic.value?.action === 'auto_code' && logic.value?.trigger === 'enter'" class="text-xs text-blue-600 mt-1">
                            <i class="fas fa-info-circle"></i> Only showing questions before Q{{ props.question?.order }}
                        </div>
                        <!-- Note: For 'complete' action with 'enter' trigger, only questions before current question are shown -->
                        <div v-if="logic.value?.action === 'complete' && logic.value?.trigger === 'enter'" class="text-xs text-blue-600 mt-1">
                            <i class="fas fa-info-circle"></i> Only showing questions before Q{{ props.question?.order }} (questionnaire will finish when condition is met)
                        </div>
                        <!-- Note: For 'complete' action with 'leave' trigger, questions before and including current question are shown -->
                        <div v-if="logic.value?.action === 'complete' && logic.value?.trigger === 'leave'" class="text-xs text-blue-600 mt-1">
                            <i class="fas fa-info-circle"></i> Only showing questions before and including Q{{ props.question?.order }} (questionnaire will finish when condition is met)
                        </div>
                        <!-- Note: For other actions, all questions are shown -->
                        <div v-if="logic.value?.action !== 'skip' && logic.value?.action !== 'jump_to' && logic.value?.action !== 'screenout' && logic.value?.action !== 'complete' && logic.value?.action !== 'hide_choices' && logic.value?.action !== 'quality_control' && logic.value?.action !== 'auto_code'" class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle"></i> Showing all available questions
                        </div>
                    </div> 
                    <div v-if="QuestionSourceChoices.length>0" class="flex flex-col">
                        <jet-label class="text-xs" value="Choice" />
                        <SingleSelect size="sm" class="" :options="QuestionSourceChoices ?? []" :modelValue="condition.var" @update:modelValue="(res) => {
                            condition.var = res
                        }"></SingleSelect>
                        <!-- <s4-input color="ssrGray" class="text-sm" :theme="'theme3'"
                            :placeholder="'e.g. Q1'" v-model="condition.var">
                        </s4-input>               -->
                    </div>
                    <div class="flex flex-col">
                        <jet-label class="text-xs" value="Operator" />
                        <SingleSelect size="sm" width="200px" :options="Operators" :modelValue="condition.operator" @update:modelValue="(res) => {
                            condition.operator = res
                        }"></SingleSelect>
                    </div>
                    <div v-if="QuestionSourceInfo" class="flex flex-col">
                        <jet-label class="text-xs" value="Value" />
                        <SingleSelect v-if="(QuestionSourceValues?.length ?? 0) > 0" width="auto" maxWidth="300px"
                            size="sm" :options="QuestionSourceValues ?? []" :modelValue="condition.val" @update:modelValue="(res) => {
                                condition.val = res
                        }"></SingleSelect>
                        <s4-input v-else color="ssrGray" class="text-sm" :theme="'theme3'"
                            :placeholder="'e.g. 1'" v-model="condition.val">
                        </s4-input>
                    </div>
                </template>
                <template v-if="source == 2">

                    <div class="w-[100px] flex flex-col">
                        <jet-label class="text-xs" value="Variable" />
                        <s4-input color="ssrGray" class="w-full mb-1 text-sm" :theme="'theme3'"
                            :placeholder="'e.g. Q1'" v-model="condition.var">
                        </s4-input>
                    </div>
                    <div class="flex flex-col">
                        <jet-label class="text-xs" value="Operator" />
                        <SingleSelect size="sm" width="200px" :options="Operators" :modelValue="condition.operator" @update:modelValue="(res) => {
                            condition.operator = res
                        }"></SingleSelect>
                    </div>
                    <div class="w-[100px] flex flex-col">
                        <jet-label class="text-xs" value="Value" />
                        <s4-input color="ssrGray" class=" mb-1 text-sm" :theme="'theme3'"
                            :placeholder="'e.g. 1'" v-model="condition.val">
                        </s4-input>
                    </div>


                </template>
                <!-- 1C. LANGUAGE SOURCE -->
                <template v-if="source == 3">
                    <div class="flex flex-col">
                        <jet-label class="text-xs" value="Operator" />
                        <SingleSelect
                            size="sm"
                            width="200px"
                            :options="LanguageOperators"
                            :modelValue="condition.operator"
                            @update:modelValue="(res) => {
                                condition.operator = res
                            }"
                        ></SingleSelect>
                    </div>
                    <div class="flex flex-col">
                        <jet-label class="text-xs" value="Language" />
                        <SingleSelect
                            size="sm"
                            width="260px"
                            :options="LanguageValues"
                            :modelValue="LanguageModelValue"
                            @update:modelValue="(res) => (condition.var = 'langc', condition.val = JSON.stringify(res))"
                        ></SingleSelect>
                    </div>
                </template>
                <!-- 1D. SCORE SOURCE -->
                <template v-if="source == 4">
                    <div class="flex flex-col">
                        <jet-label class="text-xs" value="Scoring Operator" />
                        <SingleSelect
                            size="sm"
                            width="200px"
                            :options="Operators"
                            :modelValue="condition.operator"
                            @update:modelValue="(res) => {
                                condition.operator = res
                            }"
                        ></SingleSelect>
                    </div>
                    <div class="flex flex-col">
                        <jet-label class="text-xs" value="Value" />
                        <s4-input
                            type="number"
                            color="ssrGray"
                            class="text-sm"
                            :theme="'theme3'"
                            :placeholder="'e.g. 20'"
                            :modelValue="condition.val"
                            @update:modelValue="(res) => {
                                // Use fixed variable name gscore so expression becomes gscore == 20, etc.
                                condition.var = 'gscore';
                                condition.val = res;
                            }"
                        ></s4-input>
                    </div>
                </template>
                    <div class="flex justify-center items-center">
                            <font-awesome-icon @click="deleteSegment()"
                                class="fa-w-20 fa-h-20 text-red-300 hover:text-red-500 cursor-pointer mt-2 ml-1"
                                :icon="faIcon.eks" />

                    </div>

                </template>

        </div>
        <template v-if="condition.method == 'segment'">
            <div class="relative w-full">
                <!-- SVG overlay for connectors -->
                <svg class="absolute left-0 mb-10 top-0 pointer-events-none" :style="{width:'100%', height: svgHeight + 'px'}">
                    <path v-if="parentConnectorPath" :d="parentConnectorPath" stroke="#CBD5E1" stroke-linecap="round" stroke-width="2" fill="none" />
                    <path v-if="trunkPath" :d="trunkPath" stroke="#CBD5E1" stroke-linecap="round" stroke-width="2" fill="none" />
                    <path v-for="(p, i) in connectorPaths" :key="'c-'+i" :d="p" stroke="#CBD5E1" stroke-linecap="round" stroke-width="2" fill="none" />
                    <circle v-for="(bp, i) in branchPoints" :key="'b-'+i" :cx="bp.x" :cy="bp.y" r="3" fill="#CBD5E1" />
                    <circle v-if="parentDot" :cx="parentDot.x" :cy="parentDot.y" r="3" fill="#CBD5E1" />
                </svg>

                <div class="w-full pl-10 py-1" ref="segmentContainerRef">
                    <div class="w-full">
                        <div
                            v-for="(condtn, index) in condition.conditions" :key="index" :ref="el => setChildRef(el, index)" class="relative">
                        <criteria class="mt-1" :deep="deep + 1" :condition="condtn" :indexes="pushIndex(index)"
                                    :questions="questions" :question="question" :variableStart="props.variableStart" />
                        </div>   
                        
                        <div class="mt-1">
                            <s4-button :theme="'ssrAddColRow'" class="!bg-[var(--txtcolor)] text-white text-xs px-2 py-1"
                                @click="addSegment()">
                                <font-awesome-icon
                                class="mr-1"
                                :icon="faIcon.add" /> Add Segment
                            </s4-button>
                            <s4-button :theme="'ssrAddColRow'" class="font-bold !bg-transparent text-red-400 hover:text-red-500 text-xs px-2 py-1"
                                @click="deleteSegment()">
                                <font-awesome-icon
                                class="mr-1"
                                :icon="faIcon.trash" />
                                Delete
                            </s4-button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
<!-- </div> -->
</template>

<script setup>
import JetLabel from "@/Jetstream/Label.vue";

import S4Button from "@/S4/Button.vue";
// import S4InputSelect from "@/S4/InputSelect.vue";
import S4Input from "@/S4/Input.vue";
// import Criteria from "./Criteria.vue";
 import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
 import { faTrashAlt, faTimesCircle, faPlus } from "@fortawesome/free-solid-svg-icons";
 import { inject, onMounted, onBeforeUnmount, ref, watch, computed } from "vue";
 import helpers from "@/helpers.js";
 import SingleSelect from '@/S4/SingleSelect.vue';
 import { usePage } from "@inertiajs/vue3";
 import LanguageJson from '@/../json/language.json';

/* helpers start */
const { assign } = helpers();
/* helpers end */

const faIcon = {
    trash: faTrashAlt,
    eks: faTimesCircle,
    add : faPlus
};

const props = defineProps([
    "deep",
    "condition",
    "indexes",
    "questions",
    "question",
    'variableStart',
]);
const emit = defineEmits(["resetLogicFromChild"]);
 const Operators = ref([
     { value: "==", text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>==</span> Equal to" },
     { value: "!=", text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>!=</span> Not equal to" },
     { value: "<", text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'><</span> Less than" },
     { value: "<=", text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'><=</span> Less than or equal to" },
     { value: ">", text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>></span> Greater than" },
     { value: ">=", text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>>=</span> Greater than or equal to" },
 ]);

 // Language specific operators (only equality / inequality)
 const LanguageOperators = ref([
     { value: "==", text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>==</span> Equal to" },
     { value: "!=", text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>!=</span> Not equal to" },
 ]);

// Shared language list (same source as MultiLanguage.vue)
const LanguageList = LanguageJson.languages || [];

// Only show languages that were actually added for this questionnaire
const page = usePage();
const AppliedLanguageCodes = computed(() => {
    const setupLang = page.props.questionnaire?.setup?.language || {};
    return Object.keys(setupLang).filter(code => code && code.trim() !== '' && code !== ' ');
});

const LanguageValues = computed(() => {
    const codes = AppliedLanguageCodes.value;

    // Build map from code -> metadata for quick lookup
    const byCode = new Map(
        (LanguageList ?? []).map(item => [item.Code, item])
    );

    return codes
        .map(code => {
            const meta = byCode.get(code) || { EnglishName: code, NativeName: code, Code: code };
            return {
                value: meta.Code,
                text: `${meta.EnglishName} (${meta.NativeName}) <small class='text-xs px-2' style='border-radius: 50px; background-color: rgba(209, 129, 11, 0.15); color: var(--mcolor2); border: 1px solid rgba(209, 129, 11, 0.3);'>${meta.Code}</small>`
            };
        })
        .sort((a, b) => {
            const aKey = (a?.value || '').toLowerCase();
            const bKey = (b?.value || '').toLowerCase();
            return aKey.localeCompare(bKey);
        });
});

// Normalized model value for language dropdown, so stored values like `"en"`
// still select the correct option and can be changed.
const LanguageModelValue = computed(() => {
    const raw = props.condition?.val;
    if (typeof raw !== 'string') {
        return raw;
    }
    // If it's a JSON-quoted string, unwrap it (e.g. `"en"` -> en)
    if (raw.length >= 2 && raw[0] === '"' && raw[raw.length - 1] === '"') {
        try {
            return JSON.parse(raw);
        } catch {
            // Fallback to stripping quotes manually
            return raw.substring(1, raw.length - 1);
        }
    }
    return raw;
});

const addSegment = () => {
    let str_condtn = logic.value.conditions;
    for (let index = 0; index < props.indexes.length; index++) {
        str_condtn = str_condtn[props.indexes[index]].conditions;
    }
    str_condtn.push({
        method: "process",
        left_operator: "&&",
        var: "",
        operator: "==",
        val: "",
        conditions: [],
        source: "1" // Default to question source
    });
    // console.log('segment added',str_condtn)
}

const deleteSegment = () => {
    let str_condtn = logic.value.conditions;
    for (let index = 0; index < props.indexes.length; index++) {
        if (index + 1 == props.indexes.length) {
            str_condtn.splice(props.indexes[index], 1);
        } else {
            str_condtn = str_condtn[props.indexes[index]].conditions;
        }
    }
    
    // Clean up child refs when deleting segments
    if (props.condition.method === 'segment') {
        childRefs.value = [];
    }
    
    // Trigger connector update after deletion
    scheduleConnectorUpdate();
    
    if (str_condtn.length <= 0) {
        emit("resetLogicFromChild");
    }
};


const pushIndex = (index) => {
    var tmp_index = assign(props.indexes);
    tmp_index.push(index);
    return tmp_index;
};

const logic = inject("logic");
const source = ref('1');

const QuestionSource = ref(null);
const QuestionSourceInfo = ref(null)
const QuestionSourceChoices = ref([]);
const QuestionSourceValues = ref([]);
const previousVariableStart = ref(parseInt(props.variableStart ?? usePage().props.questionnaire.setup.variable_start));
const QuestionSource_fn = {
    /**
     * Get question info for selected question
     */
    getInfo : ()=>{
        let i = props.questions.findIndex((obj)=>{
            return obj['order'] === (parseInt(QuestionSource.value) - parseInt(props.variableStart) + 1)
        })
        let qs = props.questions[i]

        // if (!qs) {
        //     source.value = 2
        // }
        return qs;
    },
    /**
     * Get list of available questions for criteria selection
     * For 'skip' action: shows only questions before the current question
     * For 'jump_to' action with 'enter' trigger: shows only questions before the current question
     * For 'jump_to' action with 'leave' trigger: shows only questions from current question onwards
     * For 'screenout' action with 'enter' trigger: shows only questions before the current question
     * For other actions: shows all questions
     */
    getList : ()=>{
        let list = [];
        
        // Get the current question order from the injected logic context
        const currentQuestionOrder = logic.value?.currentQuestionOrder || props.question?.order;
        const logicAction = logic.value?.action;
        const logicTrigger = logic.value?.trigger;
        
        // Debug logging (can be removed in production)
        // console.log('Criteria getList - Current question order:', currentQuestionOrder);
        // console.log('Criteria getList - Logic action:', logicAction);
        // console.log('Criteria getList - Logic trigger:', logicTrigger);
        // console.log('Criteria getList - Filtering rules:', {
        //     currentQuestionOrder,
        //     logicAction,
        //     logicTrigger,
        //     isSkipAction: logicAction === 'skip',
        //     isJumpToAction: logicAction === 'jump_to',
        //     isScreenoutAction: logicAction === 'screenout',
        //     isEnterTrigger: logicTrigger === 'enter',
        //     isLeaveTrigger: logicTrigger === 'leave'
        // });
        
        (props.questions ?? []).forEach((question, index) => {
            if ((question.question_type_id >= 2 && question.question_type_id <= 5)
                || question.question_type_id == 16
                || question.question_type_id == 15
                || question.question_type_id == 8
                || question.question_type_id == 7
                //|| question.question_type_id == 11
            ) {
                let shouldInclude = true;
                
                // Apply filtering based on action and trigger
                if (currentQuestionOrder) {
                    if (logicAction === 'complete') {
                        if (logicTrigger === 'enter') {
                            // For complete action with enter trigger: only show questions before current question
                            shouldInclude = question.order < currentQuestionOrder;
                        } else if (logicTrigger === 'leave') {
                            // For complete action with leave trigger: show questions before and including current question
                            shouldInclude = question.order <= currentQuestionOrder;
                        }
                    } else if (logicAction === 'skip') {
                        // For skip action: only show questions before current question
                        shouldInclude = question.order < currentQuestionOrder;
                    } else if (logicAction === 'jump_to') {
                        if (logicTrigger === 'enter') {
                            // For jump_to with enter trigger: only show questions before current question
                            shouldInclude = question.order < currentQuestionOrder;
                        } else if (logicTrigger === 'leave') {
                            // For jump_to with leave trigger: show ONLY previous and current questions
                            // (was: from current onwards). This prevents selecting upcoming questions.
                            shouldInclude = question.order <= currentQuestionOrder;
                        }
                    } else if (logicAction === 'hide_choices') {
                        if (logicTrigger === 'enter') {
                            // For hide_choices with enter trigger: only show questions before current question
                            shouldInclude = question.order < currentQuestionOrder;
                        }
                    } else if (logicAction === 'quality_control') {
                        if (logicTrigger === 'enter') {
                            // For quality_control with enter trigger: only show questions before current question
                            shouldInclude = question.order < currentQuestionOrder;
                        }
                    } else if (logicAction === 'auto_code') {
                        if (logicTrigger === 'enter') {
                            // For auto_code with enter trigger: only show questions before current question
                            shouldInclude = question.order < currentQuestionOrder;
                        }
                    } else if (logicAction === 'screenout') {
                        if (logicTrigger === 'enter') {
                            // For screenout with enter trigger: only show questions before current question
                            shouldInclude = question.order < currentQuestionOrder;
                        }
                        // For screenout with leave trigger: show all questions (no filtering)
                    }
                }
                
                if (shouldInclude) {
                    list.push({
                        value: (parseInt(question.order) + parseInt(props.variableStart) - 1),
                        text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs'>Q"+((parseInt(question.order) - 1) + parseInt(props.variableStart)) + "</span> " + question.question,
                    });
                }
            }
        });
        
        // Debug logging (can be removed in production)
        // console.log('Criteria getList - Filtered list length:', list.length);
        
        return list;
    },
    getChoices : ()=>{
            let ques = QuestionSourceInfo.value;
            let list = [];
            if(source.value == 2){
                return list
            }
            
            // Add null check for ques
            if (!ques || !ques.question_type_id) {
                return list;
            }
            
            switch (ques.question_type_id) {
                case 2: // MULTIPLE-CHOICE - multiple choice
                case 3: // MULTIPLE-CHOICE - image selection    
                    if (ques.setup && ques.choices && Array.isArray(ques.choices)) {
                        if (ques.setup.selectMultiple) { // multiple answer
                            ques.choices.forEach((choice, index) => {
                                list.push({
                                    value:"Q" +((ques.order - 1) + props.variableStart) +"_" + ((choice?.index) ? choice.index : index + 1),
                                    text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(choice?.index || index + 1)+"</span> "+(choice?.name || 'Choice')
                                });
                            });
                        }
                        else {  // single answer
                            let selVariable = "Q" + ((ques.order - 1) + props.variableStart) + "";
                            props.condition.var = selVariable;
                        }
                    }
                    break;
                case 4: // TEXTBOX - single textbox
                    let selVariable = "Q" + ((ques.order - 1) + props.variableStart) + "";
                    props.condition.var = selVariable;
                    break;
                case 5: // TEXTBOX - multiple textbox
                case 7: // TEXTBOX - constant sum
                    if (ques.items && Array.isArray(ques.items)) {
                        ques.items.forEach((item, index) => {
                            list.push({
                                value:"Q" +((ques.order - 1) + props.variableStart) +"_" +(item?.index ? item.index : index + 1),
                                text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(item?.index || index + 1)+"</span> "+(item?.name || 'Item')
                                    
                            });
                        });
                    }
                    break;
                case 8: // RATING - slider
                case 15: // RATING - button rating
                case 16: // RATING - segmentation                            
                    if (ques.items && Array.isArray(ques.items)) {
                        ques.items.forEach((item, index) => {
                            list.push({
                                value:"Q" +((ques.order - 1) + props.variableStart) + "_" +(item?.index ? item.index : index + 1),
                                    text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(item?.index || index + 1)+"</span> "+(item?.name || 'Item')
                            });
                        });
                    }
                    break;                   
                
            }
            return list;
    },
    getValues : ()=>{
        let list = [];
        let ques = QuestionSourceInfo.value;
        console.log("ques",ques)
        
        // Add null check for ques
        if (!ques || !ques.question_type_id) {
            return list;
        }
        
        // if(source.value == 2){
        //     return list
        // }
        if (ques.question_type_id == 2 || ques.question_type_id == 3) {
            // Add null checks for setup and choices
            if (!ques.setup || !ques.choices || !Array.isArray(ques.choices)) {
                return list;
            }
            
            let multiple = ques.setup.selectMultiple;
                if (multiple) {

                    // Extract number after underscore
                    const varValue = props.condition.var;
                    let varNumber = varValue ? varValue.match(/_(\d+)/)?.[1] : 1;
                    if(!varNumber){
                        //varNumber = 1
                        varNumber = ques.choices[0]?.order || 1;
                    }
                    if (varNumber) {
                        //let choice = ques.choices[varNumber - 1];
                        let choiceIndex = ques.choices.findIndex((c)=>{
                            return c && c.index == varNumber
                        })
                        let choice = ques.choices[choiceIndex];
                        if (choice && choice.checkboxScore) {
                            list.push({
                                value: "" + choice.checkboxScore.active + "",
                                text:  "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+choice.checkboxScore.active+"</span> Selected"
                            });
                            list.push({
                                value: "" + choice.checkboxScore.inactive + "",
                                text:  "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+choice.checkboxScore.inactive+"</span> Unselected"
                            });
                        }
                    }
                } else {
                    ques.choices.forEach((choice, index) => {
                        list.push({
                            value: choice?.score || choice?.index || '',
                            text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(choice?.score || choice?.index || index + 1)+"</span> "+(choice?.name || 'Choice')
                        });
                    });
                }
        
        }else if(ques.question_type_id == 15){
            // Add null check for values
            if (ques.values && Array.isArray(ques.values)) {
                ques.values.forEach((choice, index) => {
                    list.push({
                        value: choice?.score || choice?.index || '',
                        text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(choice?.score || choice?.index || index + 1)+"</span> "+(choice?.name || 'Choice')
                    });
                });
            }
        }

        //console.log("values",list)
        return list

    }
}

// Scoring values (points) based on CURRENT question's choices when enablePoint is on
const ScoringValues = computed(() => {
    const list = [];
    const ques = props.question;

    if (!ques || !ques.setup || !ques.choices || !Array.isArray(ques.choices)) {
        return list;
    }

    if (!ques.setup.enablePoint) {
        return list;
    }

    const seen = new Set();

    ques.choices.forEach((choice, index) => {
        const pts = choice?.points;
        if (pts === undefined || pts === null || pts === '') {
            return;
        }

        const key = String(pts);
        if (seen.has(key)) {
            return;
        }
        seen.add(key);

        list.push({
            value: pts,
            text:
                "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>" +
                pts +
                "</span> Points"
        });
    });

    return list;
});

function isNumeric(str) {
    return !isNaN(str) && !isNaN(parseFloat(str));
}
function getQuestionVar(str) {
    if(!str){
        return parseInt(usePage().props.questionnaire.setup.variable_start) + (parseInt(props.question.order) - 1)
    }
    let ch = str.split("_")[0];
    let q = ch.match(/^Q\d/) ? ch.slice(1) : ch;
    //console.log("QQQQ",q)
    
    return (isNumeric(q)) ? parseInt(q) : null
}
function getVarSuffix(str) {
    if (!str) {
        return '';
    }
    const parts = str.split('_');
    if (parts.length <= 1) {
        return '';
    }
    return '_' + parts.slice(1).join('_');
}
function updateConditionVarForVariableStartChange() {
    const newStart = parseInt(props.variableStart);
    const oldStart = parseInt(previousVariableStart.value);
    if (isNaN(newStart) || isNaN(oldStart)) {
        return;
    }
    const currentVarNum = getQuestionVar(props.condition.var);
    if (!currentVarNum) {
        previousVariableStart.value = newStart;
        return;
    }
    let targetOrder = null;
    if (Array.isArray(props.questions)) {
        const match = props.questions.find((q) => (oldStart + parseInt(q.order) - 1) === currentVarNum);
        if (match) {
            targetOrder = parseInt(match.order);
        }
    }
    if (!targetOrder && QuestionSourceInfo.value && QuestionSourceInfo.value.order) {
        targetOrder = parseInt(QuestionSourceInfo.value.order);
    }
    if (!targetOrder) {
        previousVariableStart.value = newStart;
        return;
    }
    const suffix = getVarSuffix(props.condition.var);
    const newVarNum = newStart + targetOrder - 1;
    props.condition.var = 'Q' + newVarNum + suffix;
    QuestionSource.value = newVarNum;
    QuestionSourceInfo.value = QuestionSource_fn.getInfo();
    QuestionSourceChoices.value = QuestionSource_fn.getChoices();
    QuestionSourceValues.value = QuestionSource_fn.getValues();
    previousVariableStart.value = newStart;
}


watch(QuestionSource,()=>{
    //props.condition.val = ''
    QuestionSourceInfo.value = QuestionSource_fn.getInfo()
    QuestionSourceChoices.value = QuestionSource_fn.getChoices()
    QuestionSourceValues.value = QuestionSource_fn.getValues()
})



watch(()=>props.condition,()=>{
    // Use persisted source if available, otherwise calculate based on variable
    if (props.condition.source) {
        source.value = props.condition.source;
    } else {
        // Fallback for older conditions without source property
        QuestionSource.value = getQuestionVar(props.condition.var);
        if(QuestionSource.value == null){
            source.value = 2
        }
    }
    
    QuestionSource.value = getQuestionVar(props.condition.var);
    QuestionSourceInfo.value = QuestionSource_fn.getInfo()
    QuestionSourceChoices.value = QuestionSource_fn.getChoices()
    QuestionSourceValues.value = QuestionSource_fn.getValues()
},{deep:true})

watch(()=>props.variableStart,()=>{
    updateConditionVarForVariableStartChange();
})

watch(()=>props.condition.var,()=>{
    QuestionSource.value = getQuestionVar(props.condition.var);
    QuestionSourceInfo.value = QuestionSource_fn.getInfo()
    QuestionSourceChoices.value = QuestionSource_fn.getChoices()
    QuestionSourceValues.value = QuestionSource_fn.getValues()
})

// watch(source,()=>{
//     if(source.value == 2){
//         props.condition.var = ''
//         props.condition.val = ''
//     }
// })


onMounted(()=>{
    // Use persisted source if available, otherwise calculate based on variable
    if (props.condition.source) {
        source.value = props.condition.source;
    } else {
        // Fallback for older conditions without source property
        QuestionSource.value = getQuestionVar(props.condition.var);
        if(QuestionSource.value == null){
            source.value = 2
        }
    }
    
    QuestionSource.value = getQuestionVar(props.condition.var);
    // compute connectors initially after DOM is ready
    scheduleConnectorUpdate();
})

// ------------------------------
// SVG connector paths (native SVG L-lines)
// ------------------------------
const rowRef = ref(null); // the current row container (parent of this segment)
const segmentContainerRef = ref(null); // the block containing child segments
const childRefs = ref([]);
const svgHeight = ref(0);
const trunkPath = ref("");
const connectorPaths = ref([]);
const branchPoints = ref([]);
const parentConnectorPath = ref("");
const parentDot = ref(null);
let ro = null;
let rafId = null;
let pendingUpdate = false;

function scheduleConnectorUpdate(){
    if(pendingUpdate) return;
    pendingUpdate = true;
    
    if(rafId){
        cancelAnimationFrame(rafId);
    }
    
    rafId = requestAnimationFrame(() => {
        computeConnectorPaths();
        pendingUpdate = false;
        rafId = null;
    });
}

function setChildRef(el, index){
    if(!el){
        // Element is being unmounted, remove from refs
        if(childRefs.value[index]){
            childRefs.value[index] = null;
        }
        return;
    }
    childRefs.value[index] = el;
    scheduleConnectorUpdate();
}

function computeConnectorPaths(){
    try{
        const container = segmentContainerRef.value;
        const wrapper = container?.parentElement; // the relative container we placed SVG on
        if(!container || !wrapper){
            trunkPath.value = "";
            connectorPaths.value = [];
            return;
        }

        const wrapperRect = wrapper.getBoundingClientRect();
        const childrenRects = childRefs.value
            .filter(Boolean)
            .map(el => {
                // Prefer the inner .criteria block for accurate baseline
                const inner = el.querySelector('.criteria') || el;
                return inner.getBoundingClientRect();
            });

        if(childrenRects.length === 0){
            trunkPath.value = "";
            connectorPaths.value = [];
            svgHeight.value = wrapperRect.height;
            return;
        }

        // vertical trunk x coordinate (left gutter)
        // Cache computed values
        const computedPl = parseInt(getComputedStyle(container).paddingLeft || 40);
        const trunkX = Math.max(16, computedPl - 16);
        const elbowX = trunkX + 18;
        
        // Single pass to compute centers and bounds
        let minY = Infinity, maxY = -Infinity;
        const centers = childrenRects.map(r => {
            const y = (r.top - wrapperRect.top) + r.height/2;
            const xLeft = (r.left - wrapperRect.left) - 8;
            if(y < minY) minY = y;
            if(y > maxY) maxY = y;
            return { y, xLeft };
        });

        // Batch DOM writes
        trunkPath.value = `M ${trunkX},${minY} L ${trunkX},${maxY}`;
        branchPoints.value = centers.map(c => ({ x: trunkX, y: c.y }));
        
        // Pre-build all connector paths
        connectorPaths.value = centers.map(c => {
            const targetX = Math.max(elbowX, c.xLeft);
            return `M ${trunkX},${c.y} L ${elbowX},${c.y} L ${targetX},${c.y}`;
        });

        svgHeight.value = Math.max(wrapperRect.height, maxY + 24);

        // build connector from this group's title row to the trunk top (connect parent -> segment)
        const parentRow = wrapper.previousElementSibling; // the line where "Logic Type: Group Segment" lives
        if(parentRow){
            const parentCriteria = parentRow.querySelector('.criteria') || parentRow;
            const pr = parentCriteria.getBoundingClientRect();
            const px = trunkX - 18; // slightly to the left of trunk
            const py = (pr.top - wrapperRect.top) + pr.height/2;
            parentConnectorPath.value = `M ${px},${py} L ${trunkX - 2},${py} L ${trunkX - 2},${minY}`;
            parentDot.value = { x: px, y: py };
        }else{
            parentConnectorPath.value = "";
            parentDot.value = null;
        }
    }catch(e){
        // fail-safe: don't crash UI
        trunkPath.value = "";
        connectorPaths.value = [];
    }
}

// Recompute on child mutations
watch(()=> props.condition.conditions, scheduleConnectorUpdate, {deep:true});
watch(()=> [logic?.value?.trigger, logic?.value?.action, source.value], scheduleConnectorUpdate);

// Resize observer to keep lines in sync
onMounted(()=>{
    if(typeof ResizeObserver !== 'undefined'){
        ro = new ResizeObserver(scheduleConnectorUpdate);
        if(segmentContainerRef.value){ ro.observe(segmentContainerRef.value); }
        if(rowRef.value){ ro.observe(rowRef.value); }
    }
    window.addEventListener('resize', scheduleConnectorUpdate);
});

onBeforeUnmount(()=>{
    window.removeEventListener('resize', scheduleConnectorUpdate);
    if(ro){ try{ ro.disconnect(); }catch(_){} }
    if(rafId){ cancelAnimationFrame(rafId); }
});
</script>