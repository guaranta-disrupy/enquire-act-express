<template>
    <div class="skiplogic-list" :set="allLogics = computedCollections">
        <!-- <jet-label class="font-bold text-xl px-2"><strong>Logics</strong></jet-label> -->

        <div v-if="allLogics.length > 0" class="w-full">
            <!-- <TransitionGroup name="listItem" tag="div" class="list flex flex-wrap gap-2 pt-2 max-h-[calc(100vh-350px)] overflow-y-auto overflow-x-hidden px-2">
              
                            <div class="flex flex-col w-[350px] min-h-[150px] border border-gray-300 rounded-lg text-xl text-gray-500 bg-gray-100 font-bold flex items-center justify-center cursor-pointer hover:bg-gray-200" @click="emits('add:sk')">
                                 <font-awesome-icon
                                 class="text-4xl"
                                :icon="faIcon.add" />
                                <div>Add new logic</div>
                            </div>
                            <div v-for="(sk,index) in allLogics" :key="sk.data?.uuid || index" class="w-[350px] sk-card relative border p-4 rounded-lg relative bg-gray-100" :data-theme="sk.data.action">
                               
                                <div class="sk-card-num text-sm italic opacity-[.6] font-bold">#{{ index + 1 }}</div>
                                <div class=" grid grid-cols-3 gap-1 absolute right-2 top-2 text-xs">
                                    <button @click="emits('update:sk',{sk:sk.data,index:sk.index,trigger:sk.data.trigger})">Edit</button>
                                    <button @click="emits('copy:sk',{sk:sk.data})">Copy</button>
                                    <button @click="emits('delete:sk',{sk:sk.data,index:sk.index,trigger:sk.data.trigger})">Delete</button>
                                </div>
                                <div>
                                    
                                    <div class="text-sm">If respondent is about to <span class="italic underline">{{(sk.data.trigger == 'leave') ? 'leave' : 'enter'}}</span> <span class="bg-black text-white text-xs p-[2px] text-center rounded-sm">{{ 'Q'+(parseInt(props.data.order) + parseInt($page.props.questionnaire.setup.variable_start) - 1) }}</span>,</div>
                        
                                    <div v-if="sk.data.action == 'screenout'" class="text-sm"><span class="text-red-500 italic font-bold">disqualify</span> when condition is </div>
                                    <div v-else-if="sk.data.action == 'quality_control'" class="text-sm"><span class="text-red-500 italic font-bold">quality control</span> when condition is </div>
                                    <div v-else-if="sk.data.action == 'skip'" class="text-sm"><span class="text-purple-500 italic font-bold">skip</span> this question when condition is </div>
                                    <div v-else-if="sk.data.action == 'complete'" class="text-sm"><span class="text-green-700 italic font-bold">finish questionnaire</span> when condition is </div>
                                    <div v-else-if="sk.data.action == 'jump_to'" class="text-sm"><span class="text-blue-700 italic font-bold">jump to </span> 
                                        <span class="bg-black text-white text-xs p-[2px] text-center rounded-sm">{{ 'Q'+(parseInt(sk.data.jump_to) + parseInt($page.props.questionnaire.setup.variable_start) - 1) }}</span>
                                        when condition is </div>
                                    
                                    <div v-else-if="sk.data.action == 'hide_choices'" class="text-sm"><span class="text-teal-600 italic font-bold">hide choice{{ ((sk.data.value.length > 1) ? 's ' : ' ') }} </span> 
                                        <div v-for="val in sk.data.value" class="inline-flex flex-col flex-wrap gap-2">
                                            <span class="bg-black text-white text-xs p-[2px] min-w-5 text-center rounded-sm relative mr-1">{{ val }}</span> 
                                        </div>when condition is </div>

                                    <div v-else-if="sk.data.action == 'hide_items'" class="text-sm"><span class="text-teal-600 italic font-bold">hide item{{ ((sk.data.value.length > 1) ? 's ' : ' ') }} </span> 
                                        <div v-for="val in sk.data.value" class="inline-flex flex-col flex-wrap gap-2">
                                            <span class="bg-black text-white text-xs p-[2px] min-w-5 text-center rounded-sm relative mr-1">{{ val }}</span> 
                                        </div>when condition is </div>


                                    <div v-else-if="sk.data.action == 'hide_columns'" class="text-sm"><span class="text-teal-600 italic font-bold">hide column{{ ((sk.data.value.length > 1) ? 's ' : ' ') }} </span> 
                                        <div v-for="val in sk.data.value" class="inline-flex flex-col flex-wrap gap-2">
                                            <span class="bg-black text-white text-xs p-[2px] min-w-5 text-center rounded-sm relative mr-1">{{ val }}</span> 
                                        </div>when condition is </div>

                                    <div v-else-if="sk.data.action == 'hide_rows'" class="text-sm"><span class="text-teal-600 italic font-bold">hide row{{ ((sk.data.value.length > 1) ? 's ' : ' ') }} </span> 
                                        <div v-for="val in sk.data.value" class="inline-flex flex-col flex-wrap gap-2">
                                            <span class="bg-black text-white text-xs p-[2px] min-w-5 text-center rounded-sm relative mr-1">{{ val }}</span> 
                                        </div>when condition is </div>


                                    <div v-if="sk.data.conditioning != 'custom'" class="bg-orange-100 border border-orange-300 text-black font-normal mt-2 rounded-lg p-2 py-1 text-center text-xs italic"> {{ criteriaToString(sk.data.conditions) }}</div>
                                     <div v-else class="bg-orange-100 border border-orange-300 text-black font-normal mt-2 rounded-lg p-2 py-1 text-center text-xs italic"> {{ sk.data.custom }}</div>
                                </div>
                            </div>

                </TransitionGroup> -->



            <div class="overflow-x-auto shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                <table class="min-w-full divide-y divide-gray-200" style="table-layout: auto;">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-2 py-3 whitespace-nowrap text-sm font-medium text-gray-500 max-w-[50px] text-center">
                                #</th>
                            <th scope="col"
                                class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider max-w-[150px]">
                                If Condition Is</th>
                            <th scope="col"
                                class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider max-w-[100px]">
                                Action</th>
                            <th scope="col"
                                class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider max-w-[120px]">
                                Target</th>
                            <th scope="col"
                                class="px-3 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider max-w-[80px] text-center">
                                Trigger</th>
                            <th scope="col"
                                class="px-2 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider w-[140px] text-center">
                                    <span>Actions</span>
                            </th>
                            <th scope="col"
                                class="text-xs font-medium text-gray-500 uppercase tracking-wider w-[90px] pr-2">
                                <button v-if="allLogics.length > 0" @click="emits('deleteAll:sk')" 
                                        class="text-xs bg-red-600 text-white hover:bg-red-700 font-semibold px-2 py-1 rounded shadow-sm transition-colors"
                                        title="Delete all skip logic">
                                        Delete All
                                    </button></th>
                        </tr>
                    </thead>
                    <tbody id="skipLogicTable" class="bg-white divide-y divide-gray-200">
                        <TransitionGroup name="listItem" tag="tr" v-for="(sk, index) in allLogics"
                            :key="sk.data?.uuid || index">
                            <!-- <tr v-for="(sk,index) in allLogics" :key="sk.data?.uuid || index"> -->
                            <td
                                class="px-2 py-4 whitespace-nowrap text-sm font-medium text-gray-900 max-w-[50px] text-center">
                                {{ (index + 1) }}.</td>
                            <td v-if="sk.data.conditioning != 'custom'"
                                class="px-3 py-4 text-sm font-medium text-gray-900 max-w-[150px] break-words">
                                {{ criteriaToString(sk.data.conditions) }}</td>
                            <td v-else class="px-3 py-4 text-sm font-medium text-gray-900 max-w-[150px] break-words">{{
                                sk.data.custom }}</td>

                            <td class="px-3 py-4 text-sm font-medium text-gray-900 max-w-[100px] break-words"
                                v-html="_fn.generateAction(sk.data.action)"></td>
                            <td class="px-3 py-4 text-sm font-medium text-gray-900 max-w-[120px] break-words"
                                v-html="_fn.generateValues(sk)"></td>

                            <td class="px-3 py-4 text-sm font-medium text-gray-900 max-w-[80px] text-center"
                                v-html="_fn.generateTrigger(sk.data.trigger)"></td>

                            <td class="px-2 py-4 text-sm font-medium text-gray-900 max-w-[140px] text-center">
                                <div class="flex flex-row gap-2 justify-center items-center w-full">
                                    <button class="text-gray-500 hover:text-gray-700 text-md"
                                        @click="emits('update:sk', { sk: sk.data, index: sk.index, trigger: sk.data.trigger })">Edit</button>
                                    <button class="text-gray-500 hover:text-gray-700 text-md"
                                        @click="emits('copy:sk', { sk: sk.data })">Copy</button>
                                    <button class="text-red-500 hover:text-red-700 text-md"
                                        @click="emits('delete:sk', { sk: sk.data, index: sk.index, trigger: sk.data.trigger })">Delete</button>
                                </div>

                            </td>
                            <!-- </tr> -->
                        </TransitionGroup>
                    </tbody>
                </table>
            </div>



        </div>
        <div class="p-2" v-else>
            <div class="flex flex-col items-center italic text-slate-400 text-md content-center p-[20px_20px]">
                <div class="w-full h-auto"><img src="@/../assets/icons/emptyL.svg"
                        class="w-[100px] h-auto !max-h-[unset] pointer-events-none" /></div>
                <div class="self-center w-full font-bold pt-[12px] mb-[24px] text-center text-balance"> No logic added
                    yet! </div>
                <!-- <button @click="emits('add:sk')" class="cursor-pointer transition flex justify-center items-center text-sm font-semibold px-3 py-2 rounded-lg bg-[var(--mcolor)] border-2 border-transparent text-[var(--txtcolor)]"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 mr-1 self-center"><path data-v-2da10074="" stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path></svg> Add Logic </button> -->
            </div>
        </div>









    </div>

</template>

<script setup>
import JetLabel from '@/Jetstream/Label.vue';
import { ref, defineEmits, computed } from 'vue'
import helpers from '@/helpers.js'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEdit, faSpinner, faSave, faTrashAlt, faCopy, faPlus, faTimesCircle } from '@fortawesome/free-solid-svg-icons'
import { usePage } from "@inertiajs/vue3";


const { assign } = helpers()
const props = defineProps({
    collection: { required: true },
    data: { required: true }
})
const triggers = ref(['enter', 'leave'])

const faIcon = {
    save: faSave,
    spinner: faSpinner,
    edit: faEdit,
    trash: faTrashAlt,
    copy: faCopy,
    add: faPlus,
    eks: faTimesCircle,
}


const emits = defineEmits(['update:sk', 'delete:sk', 'copy:sk', 'add:sk', 'deleteAll:sk'])

const computedCollections = computed(() => {

    let list = []
    let triggers = ['enter', 'leave']
    triggers.forEach((trigger) => {
        props.collection[trigger].forEach((sk, index) => {
            if (sk) {
                list.push({
                    index: index,
                    data: sk
                })
            }
        })
    })

    list.sort((a, b) => {
        if (a.data?.timestamp === null) return -1; // Push items with no timestamp to the top
        if (b.data?.timestamp === null) return 1;
        return a.data?.timestamp - b.data?.timestamp; // Regular sort for items with timestamps
    })

    //list.sort((a, b) => b.timestamp - a.timestamp)


    // console.log("lllist",list)
    return list

})


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

const _fn = {
    generateAction: (action) => {
        let res = action
        switch (action) {
            case "jump_to":
                res = "<span class='text-teal-500'>Jump to</span>"
                break;
            case "hide_choices":
                res = "<span class='text-gray-500'>Hide choices</span>"
                break;
            case "hide_items":
                res = "<span class='text-gray-500'>Hide items</span>"
                break;
            case "auto_code":
                res = "<span class='text-gray-500'>Auto Code</span>"
                break;
            case "screenout":
                res = "<span class='text-red-500'>Disqualify</span>"
                break;
            case "quality_control":
                res = "<span class='text-red-500'>Quality control</span>"
                break;
            case "complete":
                res = "<span class='text-green-500'>Finish Questionnaire</span>"
                break;
            case "skip":
                res = "<span class='text-orange-500'>Skip Question</span>"
                break;
        }
        return res
    },
    generateTrigger: (trigger) => {
        if (trigger == "enter") {
            return "<span class='bg-green-700 text-white px-2 text-sm rounded-xl'>Enter</span>";
        } else {
            return "<span class='bg-yellow-700 text-white px-2 text-sm rounded-xl'>Leave</span>";
        }
    },
    generateValues: (sk) => {
        let rs = null;
        //console.log("ssssssskkkkkkkkk",sk)
        let Qs = ""
        let choices = ""
        let elems = ""

        switch (sk.data.action) {
            case "jump_to":
                Qs = _fn.getQuestion(sk.data.jump_to)
                rs = _fn.generateItem({
                    id: "Q" + (parseInt(Qs.order) + parseInt(usePage().props.questionnaire.setup.variable_start) - 1),
                    value: Qs.question
                })
                break;
            case "hide_choices":
                choices = _fn.getChoices({ questionOrder: props.data.order, value: sk.data.value })
                elems = "";
                choices.forEach((c) => {
                    let temp = {
                        id: c.index,
                        value: c.data.name
                    }
                    elems += _fn.generateItem(temp)
                })
                rs = "<div class='flex flex-col flex-wrap gap-1'>" + elems + "</div>"
                break;
            case "hide_items":
                choices = _fn.getItems({ questionOrder: props.data.order, value: sk.data.value })
                elems = "";
                choices.forEach((c) => {
                    let temp = {
                        id: c.index,
                        value: c.data.name
                    }
                    elems += _fn.generateItem(temp)
                })
                rs = "<div class='flex flex-col flex-wrap gap-1'>" + elems + "</div>"
                break;
            case "auto_code":
                if (!sk.data.autocode || !sk.data.autocode.variable) {
                    rs = "N/A";
                    break;
                }

                let vars = sk.data.autocode.variable.split("_");
                let order = parseInt(vars[0].substring(1)) - parseInt(usePage().props.questionnaire.setup.variable_start) + 1;
                Qs = _fn.getQuestion(order);

                elems = _fn.generateItem({
                    id: sk.data.autocode.variable,
                    value: Qs?.question ?? ""
                });

                // Normalize value/text when coming as objects from UI controls
                const rawVal = sk.data.autocode.value;
                const rawText = sk.data.autocode.text;
                const safeId = (rawVal && typeof rawVal === 'object') ? (rawVal.value || rawVal.id || rawVal.index || '') : rawVal;
                let safeText = (rawText && typeof rawText === 'object') ? (rawText.text || rawText.name || rawText.label || '') : rawText;

                if ((safeText === undefined || safeText === null || safeText === '') && Qs) {
                    let found = null;
                    if (Array.isArray(Qs.choices)) {
                        found = Qs.choices.find((c, idx) => {
                            if (c?.index !== undefined && c?.index !== null) {
                                return String(c.index) == String(safeId);
                            }
                            return String(idx + 1) == String(safeId);
                        });
                        if (found) {
                            safeText = found.name ?? found.equivalent ?? found.statementA ?? '';
                        }
                    }
                }

                elems += _fn.generateItem({
                    id: safeId,
                    value: (safeText !== undefined && safeText !== null && safeText !== '') ? safeText : String(safeId ?? '')
                });

                rs = "<div class='flex flex-col flex-wrap gap-1'>" + elems + "</div>";
                break;

            default:
                rs = "N/A";


        }
        return rs;
    },
    getQuestion: (order) => {
        let Qs = usePage().props.questions.find((res) => {
            return res.order == order
        })
        return Qs
    },
    getChoices: (obj) => {
        let Qs = _fn.getQuestion(obj.questionOrder)
        let res = []
        obj.value.forEach((c) => {
            let item = Qs.choices.find((choice, index) => {
                if (choice?.index) {
                    return choice.index == c
                } else {
                    return index == c
                }
            })
            if (item) {
                res.push({ index: c, data: item })
            }
        })
        return res
    },
    getItems: (obj) => {
        let Qs = _fn.getQuestion(obj.questionOrder)
        let res = []
        obj.value.forEach((c) => {
            let item = Qs.items.find((choice, index) => {
                if (choice?.index) {
                    return choice.index == c
                } else {
                    return index == c
                }
            })
            if (item) {
                res.push({ index: c, data: item })
            }
        })
        return res
    },
    generateItem: (obj) => {
        return "<div class='sk-coded-item'><span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>" + obj.id + "</span><div>" + obj.value + "</div></div>"
    }

}









// console.log("collections",props.collection)
// // console.log("data",props.data)

// // Add debugging information
// console.log("🔍 skiplogicList component mounted")
// console.log("📊 Props received:", {
//     collection: props.collection,
//     data: props.data
// })
// console.log("📋 Computed collections:", computedCollections.value)
</script>

<style>
.sk-coded-item {
    display: flex;
    flex-direction: row;
    gap: 4px;
}

/* .sk-coded-item > div{
    width:400px!important;
    overflow:hidden;
} */
.sk-coded-item>div * {
    display: inline-block !important;
    font-size: 0.875rem !important;
}

.sk-coded-item>div img,
video {
    /* width:20px!important; */
    width: 500px !important;
    height: auto !important;
}

/* Ensure Actions column is properly centered */
table td:last-child {
    text-align: center !important;
}

table td:last-child>div {
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
}
</style>

<style scoped>
.sk-card[data-theme='screenout'] {
    background-color: #ffdada;
    border-color: #ffb7b7;
}

.sk-card[data-theme='quality_control'] {
    background-color: #ffdada;
    border-color: #ffb7b7;
}

.sk-card[data-theme='complete'] {
    background-color: #e9ffe2;
    border-color: #9fdb9f;
}

.sk-card[data-theme='hide_choices'],
.sk-card[data-theme='hide_items'],
.sk-card[data-theme='hide_columns'],
.sk-card[data-theme='hide_rows'] {
    background-color: #e3fcff;
    border-color: #70c6d1;
}

.sk-card[data-theme='skip'] {
    background-color: #f6ecf7;
    border-color: #f7c6ff;
}

.sk-card[data-theme='jump_to'] {
    background-color: #e3f3ff;
    border-color: #b2ddff;
}

.listItem-enter-active,
.listItem-leave-active {
    transition: all 0.3s ease;
}

.listItem-enter-from,
.listItem-leave-to {
    opacity: 0;
    transform: translateX(30px);
}
</style>