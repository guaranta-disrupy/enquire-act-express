<script setup>
import JetLabel from "@/Jetstream/Label.vue";

import S4Button from "@/S4/Button.vue";
import S4InputSelect from "@/S4/InputSelect.vue";
import S4Input from "@/S4/Input.vue";
import Criteria from "./Criteria.vue";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faTrashAlt, faTimesCircle, faPlus } from "@fortawesome/free-solid-svg-icons";
import { inject, ref, watch } from "vue";
import helpers from "@/helpers.js";
import SingleSelect from '@/S4/SingleSelect.vue';

/* helpers start */
const { assign } = helpers();
/* helpers end */

const props = defineProps([
    "deep",
    "condition",
    "indexes",
    "questions",
    "question",
    'variableStart',
]);

const logicEnterAcion = [
    'screenout',
    'skip',
    'jump_to',
    'quality_control',
    'complete'
]

const emit = defineEmits(["resetLogicFromChild"]);
const faIcon = {
    trash: faTrashAlt,
    eks: faTimesCircle,
    add : faPlus
};

const logic = inject("logic");

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
    });
    // console.log('segment added',str_condtn)
};

const deleteSegment = () => {
    let str_condtn = logic.value.conditions;
    for (let index = 0; index < props.indexes.length; index++) {
        if (index + 1 == props.indexes.length) {
            str_condtn.splice(props.indexes[index], 1);
        } else {
            str_condtn = str_condtn[props.indexes[index]].conditions;
        }
    }
    if (str_condtn.length <= 0) {
        emit("resetLogicFromChild");
    }
};

const pushIndex = (index) => {
    var tmp_index = assign(props.indexes);
    tmp_index.push(index);
    return tmp_index;
};

const source = ref(1)
const otherQuestionSource = ref(null)
const otherQuestionSourceGet = ref(null)
const placeHolder = ref("e.g. Q1")
const matrixType = ref(null)
const setSelfQuestionMatrixCol = ref([])
const MatrixCol = ref(null)
const MatrixRow = ref(null)


const Operators = ref([
    { value: "==", text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>==</span> Equal to" },
    { value: "!=", text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>!=</span> Not equal to" },
    { value: "<", text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'><</span> Less than" },
    { value: "<=", text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'><=</span> Less than or equal to" },
    { value: ">", text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>></span> Greater than" },
    { value: ">=", text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>>=</span> Greater than or equal to" },
]);

const type2_Values = (source) => {

    let list = [];
    let ques = source == 3
        ? props.question
        : source == 2
            ? props.questions[otherQuestionSource.value - 1]
            : null;
    if (ques.question_type_id == 2 || ques.question_type_id == 3) {
        let multiple = ques.setup.selectMultiple;
        if (multiple) {
            // Extract number after underscore
            const varValue = props.condition?.var;
            const varNumber = varValue ? varValue.match(/_(\d+)/)?.[1] : null;
            if (varNumber) {
                let choice = ques.choices[varNumber - 1];
                // console.log("choice", choice)
                list.push({
                    value: "" + choice.checkboxScore.active + "",
                   //text: " Active: " + choice.checkboxScore.active + "",
                    text:  "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+choice.checkboxScore.active+"</span> Selected"
                });
                list.push({
                    value: "" + choice.checkboxScore.inactive + "",
                    //text: " Inactive: " + choice.checkboxScore.inactive + "",
                    text:  "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+choice.checkboxScore.inactive+"</span> Unselected"
                });
            }
        } else {
            ques.choices.forEach((choice, index) => {
                list.push({
                    value: choice?.score || choice?.index || '',
                    // text:
                    //     "" +
                    //     (choice.name != null
                    //         ? (choice?.score)
                    //         : "Choice "
                    //     ) +
                    //     " : " +
                    //     (choice.name != null
                    //         ? choice.name.length > 50
                    //             ? choice.name.substring(0, 40) + "..."
                    //             : choice.name
                    //         : (choice?.score)
                    //     ) +
                    //     "",
                    //text : (choice.name != null ? (choice?.score)+"." : "Choice :")+" "+choice.name
                    text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(choice?.score || choice?.index || index + 1)+"</span> "+(choice?.name || 'Choice')
                });
            });
        }
    } else if (ques.question_type_id == 16 || ques.question_type_id == 15 || ques.question_type_id == 8) {
        ques.values.forEach((value, index) => {
            list.push({
                value: value?.score || value?.index || '',
                // text:
                //     "" +
                //     (value?.score)
                //     +
                //     " : " +
                //     (value.name != null
                //         ? value.name.length > 50
                //             ? value.name.substring(0, 40) + "..."
                //             : value.name
                //         : " - "
                //     ) +
                //     "",
                //text : (value.name != null ? (value?.score)+"." : "Choice :")+" "+value.name
                text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(value?.score || index + 1)+"</span> "+(value?.name || 'Value')

            });
        });
    } else if (ques.question_type_id == 11) {
        if (matrixType.value) {
            for (let index = 0; index < 2; index++) {
                list.push({
                    value: "" + index + "",
                    text: "" + index + "",
                });
            }
        } else {
            ques.columns.forEach((col, index) => {
                // using column original index
                // list.push({
                //     value: "" + (col?.index ? col.index : index + 1) + "",
                //     text:
                //         "" +
                //         (col?.index ? col.index : index + 1)
                //         +
                //         " : " +
                //         (col.name != null
                //             ? col.name.length > 50
                //                 ? col.name.substring(0, 40) + "..."
                //                 : col.name
                //             : " - "
                //         ) +
                //         "",
                // });

                // using column array index
                list.push({
                    value: index + 1,
                    // text:
                    //     "" +
                    //     (index + 1)
                    //     +
                    //     " : " +
                    //     (col.name != null
                    //         ? col.name.length > 50
                    //             ? col.name.substring(0, 40) + "..."
                    //             : col.name
                    //         : " - "
                    //     ) +
                    //     "",
                     //   text : (col.name != null ? (index + 1)+"." : "Choice :")+" "+col.name
                    text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(index + 1)+"</span> "+(col?.name || 'Column')

                });
            });
        }
    } else {

    }
    // console.log("ques", props.condition.var)
    return list;
};


const setSelfQuestion = () => {
    // currently uses ordering index for question variable
    let type = props.question.question_type_id;
    let list = [];
    switch (type) {
        case 2: // MULTIPLE-CHOICE - multiple choice
        case 3: // MULTIPLE-CHOICE - image selection 
            if (props.question.setup.selectMultiple) { // multiple answer
                props.question.choices.forEach((choice, index) => {
                    list.push({
                        value:
                            "Q" +
                            ((props.question.order - 1) + props.variableStart) +
                            "_" +
                            (choice?.index ? choice.index : index + 1) +
                            "",
                        // text:
                        //     "" +
                        //     (choice.name != null
                        //         ? (choice?.index ? choice.index : index + 1)
                        //         : "Choice "
                        //     ) +
                        //     " : " +
                        //     (choice.name != null
                        //         ? choice.name.length > 50
                        //             ? choice.name.substring(0, 40) + "..."
                        //             : choice.name
                        //         : (choice?.index ? choice.index : index + 1)
                        //     ) +
                        //     "",
                       // text : (choice.name != null) ? choice?.index+". "+choice.name : "Choice"
                        text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(choice?.index || index + 1)+"</span> "+(choice?.name || 'Choice')
                    });
                });
            } else {  // single answer
                let selVariable = "Q" + ((props.question.order - 1) + props.variableStart) + "";
                props.condition.var = selVariable;
            }
            break;
        case 4: // TEXTBOX - single textbox
            let selVariable = "Q" + ((props.question.order - 1) + props.variableStart) + "";
            props.condition.var = selVariable;
            break;
        case 5: // TEXTBOX - multiple textbox
        case 7: // TEXTBOX - constant sum
            props.question.items.forEach((item, index) => {
                list.push({
                    value:
                        "Q" +
                        ((props.question.order - 1) + props.variableStart) +
                        "_" +
                        (item?.index ? item.index : index + 1) +
                        "",
                    // text:
                    //     "" +
                    //     (item.name != null
                    //         ? (item?.index ? item.index : index + 1)
                    //         : "Choice "
                    //     ) +
                    //     " : " +
                    //     (item.name != null
                    //         ? item.name.length > 50
                    //             ? item.name.substring(0, 40) + "..."
                    //             : item.name
                    //         : (item?.index ? item.index : index + 1)
                    //     ) +
                    //     "",
                    //text : (item.name != null) ? item?.index+". "+item.name : "Choice"
                    text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(item?.index || index + 1)+"</span> "+(item?.name || 'Item')
                        
                });
            });
            break;
        case 8: // RATING - slider
        case 15: // RATING - button rating
        case 16: // RATING - segmentation                            
            props.question.items.forEach((item, index) => {
                // let text = type == 15 || type == 8
                //     ? item?.equivalent !== undefined
                //         ? item.equivalent != null && item.equivalent != ''
                //             ? item.equivalent.substring(0, 40) + '...'
                //             : item.name.substring(0, 40) + '...'
                //         : item.name.substring(0, 40) + '...'
                //     : item.statementA.substring(0, 40) + '...'

                list.push({
                    value:
                        "Q" +
                        ((props.question.order - 1) + props.variableStart) +
                        "_" +
                        (item?.index ? item.index : index + 1) +
                        "",
                    // text:
                    //     "" +
                    //     (item?.index ? item.index : index + 1)
                    //     +
                    //     " : " + text +
                    //     "",
                        //text : (item.name != null) ? item?.index+". "+item.name : "Choice"
                        text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(item?.index || index + 1)+"</span> "+(item?.name || 'Item')
                });
            });
            break;
        case 11: // MATRIX - matrix  
            matrixType.value = props.question.setup.selectMultiple ? 1 : 0
            if (matrixType.value) {
                setSelfQuestionMatrixCol.value = []
                props.question.columns.forEach((col, index) => {
                    setSelfQuestionMatrixCol.value.push({
                        value: index + 1,
                        // text:
                        //     "" +
                        //     (index + 1)
                        //     +
                        //     " : " +
                        //     (col.name != null
                        //         ? col.name.length > 50
                        //             ? col.name.substring(0, 40) + "..."
                        //             : col.name
                        //         : " - "
                        //     ) +
                        //     "",
                        text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(index + 1)+"</span> "+(col?.name || 'Column')
                    });
                });
            }

            props.question.rows.forEach((row, index) => {

                // using original index
                // list.push({
                //     value:
                //         "Q" +
                //         ((props.question.order-1) + props.variableStart) +
                //         "_" +
                //         (row?.index ? row.index : index + 1) +
                //         "",
                //     text:
                //         "" +
                //         (row.name != null
                //             ? (row?.index ? row.index : index + 1)
                //             : "Choice "
                //         ) +
                //         " : " +
                //         (row.name != null
                //             ? row.name.length > 50
                //                 ? row.name.substring(0, 40) + "..."
                //                 : row.name
                //             : (row?.index ? row.index : index + 1)
                //         ) +
                //         "",
                // });

                // using array index
                list.push({
                    value:
                        "Q" +
                        ((props.question.order - 1) + props.variableStart) +
                        "_" +
                        (index + 1) +
                        "",
                    // text:
                    //     "" +
                    //     (row.name != null
                    //         ? (index + 1)
                    //         : "Choice "
                    //     ) +
                    //     " : " +
                    //     (row.name != null
                    //         ? row.name.length > 50
                    //             ? row.name.substring(0, 40) + "..."
                    //             : row.name
                    //         : (index + 1)
                    //     ) +
                    //     "",
                    text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(index + 1)+"</span> "+(row?.name || 'Row')
                });
            });
            break;
    }
    // console.log("varNumber", props.question)
    return list;
};

const setOtherQuestion = () => {
    // currently uses ordering index for other question source
    let list = []
    // console.log('questions', props.questions)
    // console.log('props', props)
    props.questions.forEach((question, index) => {
        if ((question.question_type_id >= 2 && question.question_type_id <= 5)
            || question.question_type_id == 16
            || question.question_type_id == 15
            || question.question_type_id == 8
            || question.question_type_id == 7
            || question.question_type_id == 11
        ) {
            list.push({
                value: parseInt(question.order),
                text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs'>Q"+((parseInt(question.order) - 1) + parseInt(props.variableStart)) + "</span> " + question.question,
            });
        }
    });
    return list;
};

const setOtherQuestionVar = () => {
    // currently uses ordering index for question variable
    let ques = props.questions[otherQuestionSource.value - 1];
    let list = [];
    switch (ques.question_type_id) {
        case 2: // MULTIPLE-CHOICE - multiple choice
        case 3: // MULTIPLE-CHOICE - image selection     
            if (ques.setup.selectMultiple) { // multiple answer
                ques.choices.forEach((choice, index) => {
                    list.push({
                        value:
                            "Q" +
                            ((ques.order - 1) + props.variableStart) +
                            "_" +
                            (choice?.index ? choice.index : index + 1) +
                            "",
                        // text:
                        //     "" +
                        //     (choice.name != null
                        //         ? (choice?.index ? choice.index : index + 1)
                        //         : "Choice "
                        //     ) +
                        //     " : " +
                        //     (choice.name != null
                        //         ? choice.name.length > 50
                        //             ? choice.name.substring(0, 40) + "..."
                        //             : choice.name
                        //         : (choice?.index ? choice.index : index + 1)
                        //     ) + "",
                        text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(choice?.index || index + 1)+"</span> "+(choice?.name || 'Choice')
                    });
                });
            } else {  // single answer
                let selVariable = "Q" + ((ques.order - 1) + props.variableStart) + "";
                props.condition.var = selVariable;
            }
            break;
        case 4: // TEXTBOX - single textbox
            let selVariable = "Q" + ((ques.order - 1) + props.variableStart) + "";
            props.condition.var = selVariable;
            break;
        case 5: // TEXTBOX - multiple textbox
        case 7: // TEXTBOX - constant sum
            ques.items.forEach((item, index) => {
                list.push({
                    value:
                        "Q" +
                        ((ques.order - 1) + props.variableStart) +
                        "_" +
                        (item?.index ? item.index : index + 1) +
                        "",
                    /*text:
                        "" +
                        (item.name != null
                            ? (item?.index ? item.index : index + 1)
                            : "Choice "
                        ) +
                        " : " +
                        (item.name != null
                            ? item.name.length > 50
                                ? item.name.substring(0, 40) + "..."
                                : item.name
                            : (item?.index ? item.index : index + 1)
                        ) + "",*/
                        text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(item?.index || index + 1)+"</span> "+(item?.name || 'Item')
                });
            });
            break;
        case 8: // RATING - slider
        case 15: // RATING - button rating
        case 16: // RATING - segmentation
            ques.items.forEach((item, index) => {
                let text = ques.question_type_id == 15 || ques.question_type_id == 8 ? item?.equivalent !== undefined
                    ? item.equivalent != null && item.equivalent != '' ? item.equivalent.substring(0, 40) + '...'
                        : item.name.substring(0, 40) + '...'
                    : item.name.substring(0, 40) + '...'
                    : item.statementA.substring(0, 40) + '...'

                list.push({
                    value:
                        "Q" +
                        ((ques.order - 1) + props.variableStart) +
                        "_" +
                        (item?.index ? item.index : index + 1) +
                        "",
                    // text:
                    //     "" +
                    //     (item?.index ? item.index : index + 1)
                    //     +
                    //     " : " + text +
                    //     "",
                    text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(item?.index || index + 1)+"</span> "+(item?.name || 'Item')
                });
            });
            break;
        case 11: // MATRIX - matrix
            matrixType.value = ques.setup.selectMultiple ? 1 : 0
            if (matrixType.value) {
                setSelfQuestionMatrixCol.value = []
                ques.columns.forEach((col, index) => {
                    setSelfQuestionMatrixCol.value.push({
                        value: index + 1,
                        // text:
                        //     "" +
                        //     (index + 1)
                        //     +
                        //     " : " +
                        //     (col.name != null
                        //         ? col.name.length > 50
                        //             ? col.name.substring(0, 40) + "..."
                        //             : col.name
                        //         : " - "
                        //     ) +
                        //     "",
                        text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(index + 1)+"</span> "+(col?.name || 'Column')
                    });
                });
            }

            ques.rows.forEach((row, index) => {

                // using original index
                // list.push({
                //     value:
                //         "Q" +
                //         ((ques.order-1) + props.variableStart) +
                //         "_" +
                //         (row?.index ? row.index : index + 1) +
                //         "",
                //     text:
                //         "" +
                //         (row.name != null
                //             ? (row?.index ? row.index : index + 1)
                //             : "Choice "
                //         ) +
                //         " : " +
                //         (row.name != null
                //             ? row.name.length > 50
                //                 ? row.name.substring(0, 40) + "..."
                //                 : row.name
                //             : (row?.index ? row.index : index + 1)
                //         ) +
                //         "",
                // });

                // using array index
                list.push({
                    value:
                        "Q" +
                        ((ques.order - 1) + props.variableStart) +
                        "_" +
                        (index + 1) +
                        "",
                    // text:
                    //     "" +
                    //     (row.name != null
                    //         ? (index + 1)
                    //         : "Choice "
                    //     ) +
                    //     " : " +
                    //     (row.name != null
                    //         ? row.name.length > 50
                    //             ? row.name.substring(0, 40) + "..."
                    //             : row.name
                    //         : (index + 1)
                    //     ) +
                    //     "",
                    text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(index + 1)+"</span> "+(row?.name || 'Row')
                });
            });




            // ques.rows.forEach((row, index) => {
            //     list.push({
            //         value:
            //             "Q" +
            //             ((ques.order-1) + props.variableStart) +
            //             "_" +
            //             (row?.index ? row.index : index + 1) +
            //             "",
            //         text:
            //             "" +
            //             (row.name != null
            //                 ? (row?.index ? row.index : index + 1)
            //                 : "Choice "
            //             ) +
            //             " : " +
            //             (row.name != null
            //                 ? row.name.length > 50
            //                     ? row.name.substring(0, 40) + "..."
            //                     : row.name
            //                 : (row?.index ? row.index : index + 1)
            //             ) + "",
            //     });
            // });

            break;
    }
    return list;
};

watch(source, (x) => {
    matrixType.value = null
    props.condition.var = null;
    if (x == 3) {
        setSelfQuestion();
    } else if (x == 2) {
        setOtherQuestion();
    }
});

watch(otherQuestionSource, (x) => {
    props.condition.var = null;
    props.condition.val = null;
   
    if (x != null) {
        setOtherQuestionVar();
        otherQuestionSourceGet.value = props.questions[x - 1]
    }
});

watch(MatrixCol, (x) => {
    props.condition.var = MatrixRow.value + "_" + x
});


</script>
<template>
    <div class="grid !grid-cols-[repeat(10,110px)] gap-1">
        <div class="col-span-1">
            <jet-label class="cretLabel" value="Option" />
            <!-- <s4-input-select
                :theme="'theme3'"
                class="w-full text-sm"
                color="ssrGray"
                :lists="[
                    { value: 'segment', text: 'Segment' },
                    { value: 'process', text: 'Process' },
                ]"
                v-model="condition.method"
            ></s4-input-select> -->
            <SingleSelect size="sm" :options="[
                { value: 'segment', text: 'Segment' },
                { value: 'process', text: 'Process' },
            ]" :modelValue="condition.method" @update:modelValue="(res) => {
                condition.method = res
            }"></SingleSelect>
        </div>
        <div class="col-span-1" v-if="indexes[indexes.length - 1] != 0">
            <jet-label class="cretLabel" value="Left Operator" />
            <!-- <s4-input-select :theme="'theme3'" class="w-full font-bold text-ssr-blue3 text-sm" color="ssrGray" :lists="[
                { value: '&&', text: 'AND' },
                { value: '||', text: 'OR' },
            ]" v-model="condition.left_operator"></s4-input-select> -->
            <SingleSelect size="sm" :options="[
                { value: '&&', text: 'AND' },
                { value: '||', text: 'OR' },
            ]" :modelValue="condition.left_operator" @update:modelValue="(res) => {
                condition.left_operator = res
            }"></SingleSelect>
        </div>
        <template v-if="condition.method == 'process'">

            <!-- 1A. SOURCE VARIABLE -->
            <div class="col-span-1">
                <jet-label class="cretLabel" value="Source variable" />
                <!-- <s4-input-select v-if="(
                    (props.question.question_type_id >= 2 && props.question.question_type_id <= 5)
                    || props.question.question_type_id == 16
                    || props.question.question_type_id == 15
                    || props.question.question_type_id == 8
                    || props.question.question_type_id == 7
                    || props.question.question_type_id == 11
                )" :theme="'theme3'" class="w-full text-sm" color="ssrGray" :lists="[
                    { value: '1', text: 'Custom' },
                    { value: '2', text: 'Other questions' },
                    { value: '3', text: 'Self question' },
                ]" v-model="source"></s4-input-select> -->
                <SingleSelect v-if="(
                    (props.question.question_type_id >= 2 && props.question.question_type_id <= 5)
                    || props.question.question_type_id == 16
                    || props.question.question_type_id == 15
                    || props.question.question_type_id == 8
                    || props.question.question_type_id == 7
                    || props.question.question_type_id == 11
                )" size="sm" :options="[
                    { value: '1', text: 'Custom' },
                    { value: '2', text: 'Other questions' },
                    { value: '3', text: 'Self question' },
                ]" :modelValue="source" @update:modelValue="(res) => {
                    source = res
                }"></SingleSelect>
                <!-- <s4-input-select v-else-if="(logic.trigger == 'enter' && logicEnterAcion.includes(logic.action))"
                    :theme="'theme3'" class="w-full text-sm" color="ssrGray" :lists="[
                        { value: '1', text: 'Custom' },
                        { value: '2', text: 'Other questions' },
                    ]" v-model="source"></s4-input-select> -->
                <SingleSelect v-else-if="(logic.trigger == 'enter' && logicEnterAcion.includes(logic.action))" size="sm"
                    :options="[
                        { value: '1', text: 'Custom' },
                        { value: '2', text: 'Other questions' },
                    ]" :modelValue="source" @update:modelValue="(res) => {
                        source = res
                    }"></SingleSelect>
                <!-- <s4-input-select v-else :theme="'theme3'" class="w-full text-sm" color="ssrGray" :lists="[
                    { value: '1', text: 'Custom' }
                ]" v-model="source"></s4-input-select> -->
                <SingleSelect v-else size="sm" :options="[
                    { value: '1', text: 'Custom' }
                ]" :modelValue="source" @update:modelValue="(res) => {
                    source = res
                }"></SingleSelect>
            </div>

            <!-- 1B. OTHER QUESTION -->
            <div class="col-span-2" v-if="source == 2">
                <jet-label class="cretLabel" value="Question" />
                <div>
                    <!-- <s4-input-select :theme="'theme4'" class="w-full" color="ssrGray" :lists="setOtherQuestion()"
                        v-model="otherQuestionSource"></s4-input-select> -->
                    <SingleSelect size="sm" :options="setOtherQuestion()" :modelValue="otherQuestionSource"
                        @update:modelValue="(res) => {
                            otherQuestionSource = res
                        }"></SingleSelect>
                </div>
            </div>

            <!-- 2. VARIABLE INPUTS -->
            <div 
            :set="isSelectMultiple = (
                (source==2) && otherQuestionSource != null && (props.questions[otherQuestionSource - 1]?.question_type_id == 2 || props.questions[otherQuestionSource - 1]?.question_type_id == 3) 
                && props.questions[otherQuestionSource - 1]?.setup.selectMultiple) 
                || (source == 3 && (props.question.question_type_id == 2 || props.question.question_type_id == 3) && props.question.setup.selectMultiple)
                ? true : false"
            class="col-span-2" v-show="
            source == 1 
            || source == 3 
            || (otherQuestionSource != null && props.questions[otherQuestionSource - 1]?.question_type_id > 4)
            || isSelectMultiple
            "> 
                <jet-label v-if="(isSelectMultiple)" class="cretLabel" value="Choices" />
                <jet-label v-else class="cretLabel" value="Variable" />
                <!-- 2.1 CUSTOM -->
                <s4-input v-if="source == 1" color="ssrGray" class="w-full mb-1 text-sm" :theme="'theme3'"
                    :placeholder="placeHolder" v-model="condition.var">
                </s4-input>
               
                <!-- 2.2 OTHER-QUESTION -->
                <div v-else-if="source == 2">
                    <div v-if="otherQuestionSource != null">
                        <div v-if="(!props.questions[otherQuestionSource - 1].setup.selectMultiple) &&
                                props.questions[otherQuestionSource - 1].question_type_id <= 4" >
                            <s4-input color="ssrGray"
                                class="w-full mb-1 text-sm" :theme="'theme3'" :placeholder="placeHolder"
                                v-model="condition.var">
                            </s4-input>
                        </div>
                        <div v-else>
                            <!-- <s4-input-select v-if="matrixType" :theme="'theme3'" class="w-full" color="ssrGray"
                                :lists="setOtherQuestionVar()" v-model="MatrixRow"></s4-input-select>
                            <s4-input-select v-else :theme="'theme3'" class="w-full" color="ssrGray"
                                :lists="setOtherQuestionVar()" v-model="condition.var"></s4-input-select> -->


                            <SingleSelect v-if="matrixType" size="sm" :options="setOtherQuestionVar()" :modelValue="MatrixRow" @update:modelValue="(res) => {
                                MatrixRow = res
                            }"></SingleSelect>
                            <SingleSelect v-else size="sm" :options="setOtherQuestionVar()" :modelValue="condition.var" @update:modelValue="(res) => {
                                condition.var = res
                            }"></SingleSelect>
                        </div>
                    </div>
                    <s4-input v-else color="ssrGray" class="w-full mb-1 text-sm" :theme="'theme3'"
                        :placeholder="placeHolder" v-model="condition.var">
                    </s4-input>
                </div>

                <!-- 2.3 SELF-QUESTION -->
                <div v-else-if="source == 3 && (isSelectMultiple || matrixType)">
                    <div v-if="false">
                        <s4-input  
                            color="ssrGray" class="w-full mb-1 text-sm"
                            :theme="'theme3'" :placeholder="placeHolder" v-model="condition.var">
                        </s4-input>
                    </div>
                    <div>
                        <!-- <s4-input-select v-if="matrixType" :theme="'theme3'" class="w-full" color="ssrGray"
                            :lists="setSelfQuestion()" v-model="MatrixRow"></s4-input-select>
                        <s4-input-select v-else :theme="'theme3'" class="w-full" color="ssrGray"
                            :lists="setSelfQuestion()" v-model="condition.var"></s4-input-select> -->

                        <SingleSelect v-if="matrixType" size="sm" :options="setSelfQuestion()" :modelValue="MatrixRow" @update:modelValue="(res) => {
                            MatrixRow = res
                        }"></SingleSelect>
                        <SingleSelect v-if="isSelectMultiple" size="sm" :options="setSelfQuestion()" :modelValue="condition.var" @update:modelValue="(res) => {
                            condition.var = res
                        }"></SingleSelect>


                    </div>
                </div>
            </div>

            <!-- # MATRIX COLUMN -->
            <div class="col-span-2" v-if="matrixType">
                <jet-label class="cretLabel" value="Column" />
                <div v-if="source >= 2">
                    <!-- <s4-input-select :theme="'theme3'" class="w-full" color="ssrGray" :lists="setSelfQuestionMatrixCol"
                        v-model="MatrixCol"></s4-input-select> -->
                    <SingleSelect size="sm" :options="setSelfQuestionMatrixCol()" :modelValue="MatrixCol" @update:modelValue="(res) => {
                        MatrixCol = res
                    }"></SingleSelect>

                        
                </div>
            </div>

            <!-- 3. OPERATOR / CONDITION INPUTS -->
            <div class="col-span-1">
                <jet-label class="cretLabel" value="Operator" />
                <!-- <s4-input-select :theme="'theme3'" class="w-full text-sm" color="ssrGray" :lists="[
                    { value: '==', text: '==' },
                    { value: '!=', text: '!=' },
                    { value: '>', text: '>' },
                    { value: '<', text: '<' },
                    { value: '>=', text: '>=' },
                    { value: '<=', text: '<=' },
                ]" v-model="condition.operator"></s4-input-select> -->
                <SingleSelect size="sm" :options="Operators" :modelValue="condition.operator" @update:modelValue="(res) => {
                    condition.operator = res
                }"></SingleSelect>

            </div>

            <!-- 4. VALUE INPUTS -->
            <div class="col-span-1">
                <div class="flex justify-between">
                    <jet-label class="cretLabel" value="Value" />
                </div>
                <div class="flex items-center">
                    <!-- 4.1 OTHER QUESTION -->
                    <div v-if="source == 2 && otherQuestionSourceGet">
                        <!-- MULTIPLE CHOICE - single/multiple answer -->
                        <!-- MULTIPLE CHOICE - image selection -->
                        <!-- RATING - segmentation -->
                        <!-- RATING - button rating -->
                        <!-- RATING - slider -->
                        <!-- <s4-input-select v-if="(otherQuestionSourceGet.question_type_id == 2
                            || otherQuestionSourceGet.question_type_id == 3
                            || otherQuestionSourceGet.question_type_id == 11
                            || otherQuestionSourceGet.question_type_id == 16
                            || otherQuestionSourceGet.question_type_id == 15
                            || otherQuestionSourceGet.question_type_id == 8) && condition.var" :theme="'theme3'"
                            class="w-inherit mb-1" color="ssrGray" :lists="type2_Values(2)"
                            v-model="condition.val"></s4-input-select> -->

                        <SingleSelect v-if="(otherQuestionSourceGet.question_type_id == 2
                            || otherQuestionSourceGet.question_type_id == 3
                            || otherQuestionSourceGet.question_type_id == 11
                            || otherQuestionSourceGet.question_type_id == 16
                            || otherQuestionSourceGet.question_type_id == 15
                            || otherQuestionSourceGet.question_type_id == 8) && condition.var"
                             size="sm" :options="type2_Values(2)" :modelValue="condition.val" @update:modelValue="(res) => {
                           condition.val = res
                        }"></SingleSelect>


                        <!-- TEXTBOX - single/multiple textbox  -->
                        <!-- TEXTBOX - constant sum -->
                        <s4-input v-else-if="(otherQuestionSourceGet.question_type_id == 4
                            || otherQuestionSourceGet.question_type_id == 5
                            || otherQuestionSourceGet.question_type_id == 7) && condition.var" color="ssrGray"
                            class="w-full mb-1" :theme="'theme3'" placeholder="e.g. 1" v-model="condition.val">
                        </s4-input>

                        <s4-input v-else-if="!condition.var" color="ssrGray" class="w-full mb-1" :theme="'theme3'"
                            placeholder="e.g. 1" v-model="condition.val">
                        </s4-input>
                    </div>

                    <!-- 4.2 SELF QUESTION -->
                    <div v-else-if="source == 3">
                        <!-- MULTIPLE CHOICE - single/multiple answer -->
                        <!-- MULTIPLE CHOICE - image selection -->
                        <!-- RATING - segmentation -->
                        <!-- RATING - button rating -->
                        <!-- RATING - slider -->
                        <!-- <s4-input-select v-if="question.question_type_id == 2
                            || question.question_type_id == 3
                            || question.question_type_id == 11
                            || question.question_type_id == 16
                            || question.question_type_id == 15
                            || question.question_type_id == 8" :theme="'theme3'" class="w-full mb-1" color="ssrGray"
                            :lists="type2_Values(3)" v-model="condition.val"></s4-input-select> -->


                        <SingleSelect v-if="question.question_type_id == 2
                            || question.question_type_id == 3
                            || question.question_type_id == 11
                            || question.question_type_id == 16
                            || question.question_type_id == 15
                            || question.question_type_id == 8"
                             size="sm" :options="type2_Values(3)" :modelValue="condition.val" @update:modelValue="(res) => {
                           condition.val = res
                        }"></SingleSelect>


                        <!-- TEXTBOX - single/multiple textbox -->
                        <!-- TEXTBOX - constant sum -->
                        <s4-input v-else-if="question.question_type_id == 4
                            || question.question_type_id == 5
                            || question.question_type_id == 7" color="ssrGray" class="w-full mb-1" :theme="'theme3'"
                            placeholder="e.g. 1" v-model="condition.val">
                        </s4-input>
                    </div>

                    <!-- 4.3 CUSTOM -->
                    <s4-input v-else color="ssrGray" class="w-full mb-1" :theme="'theme3'" placeholder="e.g. 1"
                        v-model="condition.val">
                    </s4-input>

                    <font-awesome-icon @click="deleteSegment()"
                        class="fa-w-20 fa-h-20 text-red-300 hover:text-red-500 cursor-pointer ml-2 mb-1"
                        :icon="faIcon.eks" />
                </div>
            </div>
        </template>
        <template v-else>
            <div class="col-start-2 col-span-11 pointer-events-none touch-none">
                <!-- <div class="ml-[80px] mt-[8px] pointer-events-auto touch-auto"> -->

                <div class="col-span-12 mt-5 pointer-events-auto touch-auto"
                    v-for="(condtn, index) in condition.conditions" :key="index">
                    <criteria class="mt-1" :deep="deep + 1" :condition="condtn" :indexes="pushIndex(index)"
                        :questions="questions" :question="question" :variableStart="props.variableStart" />
                </div>

                <div class="mt-5 pointer-events-auto touch-auto flex gap-1">
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
        </template>
    </div>
</template>

<style scoped>
.cretLabel {
    font-size: 0.7rem;
}

.ssrBTX {
    margin-top: 0.3rem;
    height: 2.3rem;
    /* width:2rem;
    height:2rem; */
}

.ssrBTX:hover {
    border: 1px solid #1e71b8;
}

.cretDel {
    right: 0px;
}

.LeftOP {
    background-color: #ceeaff80;
    border-color: #ceeaff80;
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
</style>
