<script setup>
import JetLabel from "@/Jetstream/Label.vue";
import WarningModal from "../../../../S4/Warning.vue";
import S4Checkbox from "@/S4/Checkbox.vue";
import S4TextArea from "@/S4/TextArea.vue";
import S4Input from "@/S4/Input.vue";
import S4InputSelect from "@/S4/InputSelect.vue";
import S4Button from "@/S4/Button.vue";
import S4InputSlider2 from "@/S4/InputSlider2.vue";
import question from "/public/images/question.svg";
import Tooltip from "@/S4/Tooltip.vue";
import VueFormMultiselect from "@/S4/vueform/Multiselect.vue";
import BorderSetup from "@/S4/BorderSetup.vue";
import { SlickList, SlickItem } from "vue-slicksort";
import TE2 from "@/S4/TE2.vue";
import BestWorstScaleAnalysis from "./BestWorstScaleComp/BestWorstScaleAnalysis.vue";


import {
    reactive,
    watch,
    ref,
    computed,
    inject,
    onBeforeMount,
    nextTick,
    defineExpose,
} from "vue";
import ButtonIconsVue from "../../../../S4/ButtonIcons.vue";
import helpers from "@/helpers.js";
const new_choice_elem = ref(null)
const new_choice = ref("");
const itemRefs = ref([]);
const MultipleInputElem = ref("");
const MultipleInput = reactive({
    show: false,
    value: "",
});

const equivalentArea = ref("");
const RepetitionPerItem = ref(0)

// Convert HTML with <img> tags into plain text where images are replaced
// by their alt attribute, and all other HTML tags are stripped.
const htmlToTextWithImageAlt = (html) => {
    if (!html) return "";

    // Replace <img ...> with its alt text (if any)
    let replaced = html.replace(/<img\b[^>]*>/gi, (imgTag) => {
        const altMatchDouble = imgTag.match(/\balt\s*=\s*"([^"]*)"/i);
        const altMatchSingle = imgTag.match(/\balt\s*=\s*'([^']*)'/i);
        const altText =
            (altMatchDouble && altMatchDouble[1]) ||
            (altMatchSingle && altMatchSingle[1]) ||
            "";
        return altText;
    });

    // Strip remaining HTML tags
    replaced = replaced.replace(/<\/?[^>]+(>|$)/g, "");

    // Normalize whitespace
    return replaced.replace(/\s+/g, " ").trim();
};

const RepetitionPerItemCounter = computed(() => {
    let totalItems = add_edit_form.setup.bestWostScale.sets * add_edit_form.setup.bestWostScale.itemsPerSet
    let count = (totalItems / add_edit_form.choices.length)
    if (isWholeNumber(count)) {
        count = parseInt(count).toFixed(0)
    } else {
        count = parseFloat(count).toFixed(2)
    }
    RepetitionPerItem.value = count
    return count;
})

const GenerateNumOfSets = computed(() => {

    let choicesLength = add_edit_form.choices.length;
    add_edit_form.setup.bestWostScale.repetitionsPerStatement = add_edit_form.setup.bestWostScale.repetitionsPerStatement ?? 3
    let repetitions = add_edit_form.setup.bestWostScale.repetitionsPerStatement; // defined number of repetitions

    let totalItems = repetitions * choicesLength;

    if(repetitions == 1){
        add_edit_form.setup.bestWostScale.itemsPerSet = choicesLength
        add_edit_form.setup.bestWostScale.sets = 1
        return { 'itemsPerSet': choicesLength, 'sets': 1 }
    }

    // Determine sets and items per set dynamically
    // Finding valid `itemsPerSet` that ensures an exact whole number for `sets`
    let itemsPerSet;
    for (let i = Math.min(4, choicesLength); i >= 1; i--) {
        if (totalItems % i === 0) {
            itemsPerSet = i;
            break;
        }
    }

    let sets = totalItems / itemsPerSet;

    //console.log(`Sets: ${sets}, Items per Set: ${itemsPerSet}`);  
    add_edit_form.setup.bestWostScale.itemsPerSet = itemsPerSet
    add_edit_form.setup.bestWostScale.sets = sets
    //alert(itemsPerSet)
    return { 'itemsPerSet': itemsPerSet, 'sets': sets }
})

const isWholeNumber = (str) => {
    return /^\d+$/.test(str);
}




const splitForRow = () => {
    if (MultipleInput.value != null) {
        //let newRow = MultipleInput.value.split("\n");
        const newEq = equivalentArea.value.split("\n");
        const newRow = MultipleInput.value.match(/<p>(.*?)<\/p>/g)?.map((p) =>
            p.replace(/<\/?p>/g, "").trim()
        ) || [];
        console.log(newRow);
        _.forEach(newRow, function (row, index) {
            if (row !== "<p><br></p>" && row !== "<br>" && row !== "") {
                const eqRaw = newEq[index]?.trim?.();
                const rawEquivalent = eqRaw && eqRaw !== "" ? eqRaw : row;
                const equivalent = htmlToTextWithImageAlt(rawEquivalent);

                add_edit_form.choices.push({
                    name: "<p>" + row + "</p>",
                    equivalent,
                    enable_wr: 0,
                    index:
                        add_edit_form.choices.length === 0
                            ? 1
                            : Math.max(
                                ...add_edit_form.choices.map((o) => o.index)
                            ) + 1,
                    score:
                        add_edit_form.choices.length === 0
                            ? 1
                            : Math.max(
                                ...add_edit_form.choices.map((o) => o.score)
                            ) + 1,
                    checkboxScore: {
                        active: 1,
                        inactive: 0,
                    },
                });
            }
        });
    }

    MultipleInput.value = "";
    MultipleInputElem.value.forceClear();
    equivalentArea.value = "";
    MultipleInput.show = false;

};
const showDeleteAllStatements = ref(false);
const deleteAllStatements = () => {
    add_edit_form.choices = [];
    showDeleteAllStatements.value = false;
};

watch(new_choice, async (val) => {
    if (val !== "" && val !== "<p><br></p>") {
        try {
            add_edit_form.choices.push({
                name: val,
                enable_wr: 0,
                index:
                    add_edit_form.choices.length === 0
                        ? 1
                        : Math.max(...add_edit_form.choices.map((o) => o.index)) +
                        1,
                score:
                    add_edit_form.choices.length === 0
                        ? 1
                        : Math.max(...add_edit_form.choices.map((o) => o.score)) +
                        1,
                checkboxScore: {
                    active: 1,
                    inactive: 0,
                },
            });
            await nextTick();
            itemRefs.value[add_edit_form.choices.length - 1].focus();
            new_choice_elem.value.forceClear();
        } catch (e) {
            console.log(e);
        }
    }

});


const deleteChoice = async (index) => {
    let target = JSON.parse(JSON.stringify(add_edit_form.choices[index]))
    let items = add_edit_form.choices.filter((element) => {
        return element.index !== target.index;
    })
    await nextTick();
    setTimeout(() => {
        add_edit_form.choices = items
    }, 500);
};

const add_edit_form = inject("add_edit_form");
const $VariableName = inject("$VariableName");

if (!add_edit_form.setup.bestWostScale['sets_enabler']) {
    add_edit_form.setup.bestWostScale['sets_enabler'] = true;
}
</script>

<template>

    <div class="pt-4" v-if="add_edit_form.setup?.bestWostScale" :set="generateNumSets = GenerateNumOfSets">
        <div class="grid grid-cols-2 gap-2">
            <div class="grid grid-cols-[1fr_120px] gap-1 items-end">
                <div class="w-full">
                    <jet-label class="font-bold">Label 1 (Best)</jet-label>
                    <!-- <s4-input color="ssrGray" placeholder="e.g. Most enjoyable (Best)" class="w-full" v-model="add_edit_form.setup.bestWostScale.labelA" /> -->
                    <TE2 placeholder="e.g. Most enjoyable (Best)"
                        :modelValue="add_edit_form.setup.bestWostScale?.labelA"
                        @update:modelValue="(val) => { add_edit_form.setup.bestWostScale.labelA = val }"></TE2>
                </div>
                <div class="flex gap-2 items-center relative" v-if="add_edit_form.setup.bestWostScale.labelAColor">
                    <s4-input color="ssrGray" class="w-full" placeholder="Enter color"
                        v-model="add_edit_form.setup.bestWostScale.labelAColor"
                        :style="{ color: (add_edit_form.setup.bestWostScale.labelAColor) }">
                    </s4-input>
                    <div class="absolute right-2">
                        <input type="color" color="ssrGray" class="w-4 h-5 mb-1" placeholder="Enter color" v-model="add_edit_form.setup.bestWostScale.labelAColor
                            " />
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-[1fr_120px] gap-1 items-end">
                <div class="w-full">
                    <jet-label class="font-bold">Label 2 (Worst)</jet-label>
                    <!-- <s4-input color="ssrGray" placeholder="e.g. Least enjoyable (Worst)" class="w-full" v-model="add_edit_form.setup.bestWostScale.labelB" /> -->
                    <TE2 placeholder="e.g. Least enjoyable (Worst)"
                        :modelValue="add_edit_form.setup.bestWostScale?.labelB"
                        @update:modelValue="(val) => { add_edit_form.setup.bestWostScale.labelB = val }"></TE2>


                </div>
                <div class="flex gap-2 items-center relative" v-if="add_edit_form.setup.bestWostScale.labelBColor">
                    <s4-input color="ssrGray" class="w-full" placeholder="Enter color"
                        v-model="add_edit_form.setup.bestWostScale.labelBColor"
                        :style="{ color: (add_edit_form.setup.bestWostScale.labelBColor) }">
                    </s4-input>
                    <div class="absolute right-2">
                        <input type="color" color="ssrGray" class="w-4 h-5 mb-1" placeholder="Enter color" v-model="add_edit_form.setup.bestWostScale.labelBColor
                            " />
                    </div>
                </div>
            </div>
        </div>

        <jet-label class="font-bold pt-6" v-if="add_edit_form.choices.length < 1">Items</jet-label>
        <div class="grid grid-cols-1 gap-2 ">


            <div class="grid grid-cols-[40px_18fr_15fr_205px] gap-1 pt-6 items-center"
                v-if="add_edit_form.choices.length > 0">
                <!-- <jet-label class="font-bold w-[20px]"> </jet-label> -->
                <jet-label class="font-bold w-[60px]">Index</jet-label>
                <jet-label class="font-bold w-full">Item</jet-label>
                <jet-label class="font-bold w-full">Equivalent</jet-label>
                <div class="text-right w-full">
                    <button @click="showDeleteAllStatements = true"
                        class="inline-flex items-center px-4 py-2 mb-2 hover:bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-[1.1]">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 mr-2 transition-all duration-300 ease-in-out hover:text-red-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>

                        <span class="transition-all duration-300 ease-in-out hover:text-red-600">
                            Delete All Items
                        </span>
                    </button>
                </div>
            </div>
            <div class="mt-[-20px]">
                <SlickList axis="y" v-model:list="add_edit_form.choices">
                    <SlickItem v-for="(choice, index) in add_edit_form.choices" :key="choice" :index="index"
                        class="draggable-list select-none" handle=".drag-handle2">
                        <div v-if="choice" class="grid grid-cols-[auto_1fr_1fr_40px_40px] gap-1 items-center pt-2">
                            <!-- <div>{{ (index + 1) }}</div> -->
                            <div class="min-w-[40px] text-center">
                                <span>
                                    {{ choice.index }}
                                </span>
                            </div>
                            <div>
                                <!-- <s4-input color="ssrGray" class="w-full" placeholder="Enter Statement"
                        v-model="add_edit_form.choices[index].name" :ref="(el) => {
                            itemRefs[index] = el;
                        }
                            ">
                    </s4-input> -->

                                <TE2 placeholder="Enter Statement" :modelValue="add_edit_form.choices[index].name"
                                    @update:modelValue="(val) => { add_edit_form.choices[index].name = val }" :ref="(el) => {
                                        itemRefs[index] = el;
                                    }" @mousedown.stop></TE2>


                            </div>
                            <div>
                                <s4-input color="ssrGray" class="w-full" placeholder="Enter Equivalent"
                                    v-model="add_edit_form.choices[index].equivalent">
                                </s4-input>
                            </div>
                            <div class="relative group">
                                <ButtonIconsVue icon="delete" @click="deleteChoice(index)" />
                                <span
                                    class="absolute bottom-[40px] left-1/2 transform -translate-x-1/2 mb-0 px-2 py-1 text-[10px] text-white bg-yellow-500 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    Delete
                                </span>
                            </div>
                            <div class="relative group drag-handle2">
                                <ButtonIconsVue class="" icon="move" />
                                <span
                                    class="whitespace-nowrap absolute bottom-[40px] text-center text-[10px] left-1 transform -translate-x-1/2 mb-0 px-1 py-1 text-white bg-yellow-500 bg-opacity-100 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    Hold to drag
                                </span>
                            </div>

                        </div>
                    </SlickItem>
                </SlickList>
            </div>
            <div class="grid grid-cols-1 gap-2">
                <div v-show="!MultipleInput.show">

                    <TE2 ref="new_choice_elem" placeholder="Input New Item" :modelValue="new_choice"
                        @update:modelValue="(val) => { new_choice = val }"></TE2>

                    <!-- <s4-input color="ssrGray" class="w-full" placeholder="Input New Statement"
                v-model="new_choice"></s4-input> -->

                </div>
                <div>
                    <button v-show="!MultipleInput.show" :theme="'ssrAddColRow'"
                        @click="MultipleInput.show = !MultipleInput.show"
                        class="inline-flex w-[180px] items-center px-2 py-1 mb-2 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
                        <svg width="32" height="32" viewBox="0 0 980 980" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M170.502 340.193H95.0024L95.0024 792.193C94.6024 851.393 145.169 867.86 170.502 868.693H625.002V792.193H170.502V340.193Z"
                                fill="white" />
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M323.002 112.193C299.669 107.193 252.302 115.293 249.502 187.693V640.193C249.169 666.193 263.402 718.193 323.002 718.193H775.002C799.502 721.527 848.702 710.593 849.502 640.193V187.693C851.669 162.527 839.802 112.193 775.002 112.193H323.002ZM323.002 640.193V187.693H775.002V640.193H323.002Z"
                                fill="white" />
                            <path
                                d="M170.502 340.193H95.0024L95.0024 792.193C94.6024 851.393 145.169 867.86 170.502 868.693H625.002V792.193H170.502V340.193Z"
                                stroke="white" />
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M323.002 112.193C299.669 107.193 252.302 115.293 249.502 187.693V640.193C249.169 666.193 263.402 718.193 323.002 718.193H775.002C799.502 721.527 848.702 710.593 849.502 640.193V187.693C851.669 162.527 839.802 112.193 775.002 112.193H323.002ZM323.002 640.193V187.693H775.002V640.193H323.002Z"
                                stroke="white" />
                            <path d="M551 270V558M695 414H407" stroke="white" stroke-width="32" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>

                        <span class="transition-all duration-300 ease-in-out">
                            Add Multiple Inputs
                        </span>
                    </button>

                    <div v-show="MultipleInput.show" class="grid grid-cols-2 gap-1 w-full col-span-12">
                        <TE2 ref="MultipleInputElem" placeholder="Enter items separated by new line..."
                            :modelValue="MultipleInput.value" height="150px"
                            @update:modelValue="(val) => { MultipleInput.value = val }"></TE2>
                        <s4-text-area v-show="MultipleInput.show" v-model="equivalentArea" :filter="true"
                            color="ssrGray" class="w-full h-40"
                            placeholder="Enter equivalent separated by new line..."></s4-text-area>

                        <!--   <s4-text-area color="ssrGray"  v-model="MultipleInput.value" class="w-full h-40"
                            placeholder="Enter statements separated by new line..."></s4-text-area>-->

                    </div>
                    <div v-if="MultipleInput.show">
                        <s4-button :theme="'ssrAddColRow'"
                            class="py-1 px-2 mt-2 !bg-[var(--mcolor)] hover:!bg-[var(--mcolor2)] text-white text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg"
                            @click="splitForRow()">Add</s4-button>



                        <s4-button :theme="'ssrAddColRow'"
                            class="py-1 px-2 ml-1 mt-2 bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-125 hover:bg-transparent"
                            @click="MultipleInput.show = false">Cancel</s4-button>

                    </div>

                </div>

                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 pt-10 gap-y-10">
                    <!-- Left: Validation & Layout -->
                    <div class="col-span-2 flex flex-col gap-4 bg-white rounded-lg shadow p-4">
                        <BorderSetup :class="['!-mb-8 -mt-4 -mx-4 translate-y-[-20%]']"></BorderSetup>
                        <div class="grid grid-cols-1 gap-4 justify-start items-start">

                             <!-- Sets -->
                             <div class="flex flex-col flex-wrap gap-2 w-full">
                                    <label class="flex items-center cursor-pointer max-w-[fit-content]">
                                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.bestWostScale['sets_enabler']" />
                                       <span class="ml-2 text-sm text-gray-600 select-none">Edit Sets</span>
                                    </label>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 ml-6"
                                        v-if="add_edit_form.setup.bestWostScale['sets_enabler']">
                                        <div>
                                            <jet-label class="font-bold">Repetitions</jet-label>
                                            <s4-input color="ssrGray" class="w-full" placeholder="1 (default)" type="number"
                                                v-model="add_edit_form.setup.bestWostScale.repetitionsPerStatement"></s4-input>
                                        </div>
                                        <div>
                                            <jet-label class="font-bold">Sets</jet-label>
                                            <s4-input color="ssrGray" class="w-full" placeholder="1 (default)" :disabled="true"
                                                type="number" v-model="add_edit_form.setup.bestWostScale.sets"></s4-input>
                                        </div>
                                        <div>
                                            <jet-label class="font-bold">Items per set</jet-label>
                                            <s4-input color="ssrGray" class="w-full" placeholder="0 (default)" :disabled="true"
                                                type="number"
                                                v-model="add_edit_form.setup.bestWostScale.itemsPerSet"></s4-input>
                                        </div>
                                    </div>

                            </div>




                            <!-- Edit Layout Width -->
                            <div class="flex flex-col flex-wrap gap-2 w-full">
                                <label class="flex items-center cursor-pointer max-w-[fit-content]">
                                    <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.boxStyle['max-width_enabler']" />
                                    <span class="ml-2 text-sm text-gray-600 select-none">Edit Layout Width</span>
                                    <Tooltip :imgSrc="question" preset="EditLayoutWidth" />
                                </label>

                                <!-- Layout Width Sliders -->
                                <div v-if="add_edit_form.setup.boxStyle['max-width_enabler']" class="grid grid-cols-1 sm:grid-cols-3 gap-4 ml-6">
                                    <div>
                                        <jet-label>Desktop</jet-label>
                                        <S4InputSlider2 color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                                            :units="['%', 'vmin', 'vw', 'auto']"
                                            :modelValue="add_edit_form.setup.boxStyle['max-width'] ?? '100%'"
                                            :allowAuto="true"
                                            @update:modelValue="val => add_edit_form.setup.boxStyle['max-width'] = val" />
                                    </div>
                                    <div>
                                        <jet-label>Tablet</jet-label>
                                        <S4InputSlider2 color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                                            :units="['%', 'vmin', 'vw', 'auto']"
                                            :modelValue="add_edit_form.setup.boxStyle['max-width-tab'] ?? '100%'"
                                            :allowAuto="true"
                                            @update:modelValue="val => add_edit_form.setup.boxStyle['max-width-tab'] = val" />
                                    </div>
                                    <div>
                                        <jet-label>Mobile</jet-label>
                                        <S4InputSlider2 color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                                            :units="['%', 'vmin', 'vw', 'auto']"
                                            :modelValue="add_edit_form.setup.boxStyle['max-width-mobile'] ?? '100%'"
                                            :allowAuto="true"
                                            @update:modelValue="val => add_edit_form.setup.boxStyle['max-width-mobile'] = val" />
                                    </div>
                                </div>

                                
                            </div>




                        </div>
                    </div>

                    <!-- Right: Advanced -->
                    <div class="col-span-2 lg:col-span-1 flex-1 bg-white rounded-lg shadow p-4 grid grid-cols-1 gap-4 justify-start items-start"
                       >
                        <div>
                            <BorderSetup :class="['!-mb-8 -mt-4 -mx-4 translate-y-[-100%]']">
                                <template #header>
                                    Analysis Options
                                </template>
                            </BorderSetup>
                        </div>
                        <BestWorstScaleAnalysis />
                    </div>
                </div>
                
                <!-- <div class="pt-4">
                    <BorderSetup></BorderSetup>
                    <div class="grid grid-cols-2 gap-2 mt-6">
                        <div>
                            <label class="items-center cursor-pointer">
                                <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.bestWostScale[
                                    'sets_enabler'
                                ]
                                    " />
                                <span class="ml-2 text-sm text-gray-600 select-none">Edit Sets</span>
                            </label>

                            <div class="grid grid-cols-3 gap-2"
                                v-if="add_edit_form.setup.bestWostScale['sets_enabler']">
                                <div>
                                    <jet-label class="font-bold">Repetitions</jet-label>
                                    <s4-input color="ssrGray" class="w-full" placeholder="4 (default)" type="number"
                                        v-model="add_edit_form.setup.bestWostScale.repetitionsPerStatement"></s4-input>
                                </div>
                                <div>
                                    <jet-label class="font-bold">Sets</jet-label>
                                    <s4-input color="ssrGray" class="w-full" placeholder="12 (default)" :disabled="true"
                                        type="number" v-model="add_edit_form.setup.bestWostScale.sets"></s4-input>
                                </div>
                                <div>
                                    <jet-label class="font-bold">Items per set</jet-label>
                                    <s4-input color="ssrGray" class="w-full" placeholder="4 (default)" :disabled="true"
                                        type="number"
                                        v-model="add_edit_form.setup.bestWostScale.itemsPerSet"></s4-input>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div>
                                <label class="flex items-center cursor-pointer w-[170px]">
                                    <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.boxStyle[
                                        'max-width_enabler'
                                    ]
                                        " />
                                    <span class="ml-2 text-sm text-gray-600 select-none">Edit Layout Width</span>
                                    <Tooltip :imgSrc="question" preset="EditLayoutWidth">
                                    </Tooltip>
                                </label>

                                <div class="grid grid-cols-3 gap-2"
                                    v-if="add_edit_form.setup.boxStyle['max-width_enabler']">
                                    <div>
                                        <jet-label>Desktop</jet-label>
                                        <S4InputSlider2 color="ssrGray" class="w-full" :placeholder="'e.g. 100'"
                                            :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.boxStyle['max-width']
                                                " :allowAuto="true" @update:modelValue="
                                                    (val) => {
                                                        add_edit_form.setup.boxStyle['max-width'] =
                                                            val;
                                                    }
                                                ">
                                        </S4InputSlider2>
                                    </div>
                                    <div>
                                        <jet-label>Tablet</jet-label>
                                        <S4InputSlider2 color="ssrGray" class="w-full" :placeholder="'e.g. 100'"
                                            :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.boxStyle['max-width-tab']
                                                " :allowAuto="true" @update:modelValue="
                                                    (val) => {
                                                        add_edit_form.setup.boxStyle['max-width-tab'] =
                                                            val;
                                                    }
                                                ">
                                        </S4InputSlider2>
                                    </div>
                                    <div>
                                        <jet-label>Mobile</jet-label>
                                        <S4InputSlider2 color="ssrGray" class="w-full" :placeholder="'e.g. 100'"
                                            :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.boxStyle['max-width-mobile']
                                                " :allowAuto="true" @update:modelValue="
                                                    (val) => {
                                                        add_edit_form.setup.boxStyle['max-width-mobile'] =
                                                            val;
                                                    }
                                                ">
                                        </S4InputSlider2>
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
                </div> -->
            </div>
        </div>
        <teleport to="body">
            <WarningModal :show="showDeleteAllStatements" @close="showDeleteAllStatements = false">
                <template #header>
                    Confirm Deletion
                </template>
                <template #content>
                    Are you sure you want to delete <span class="font-semibold">all statements</span>?
                    This action is irreversible.
                </template>
                <template #footer>
                    <button @click="deleteAllStatements"
                        class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
                        type="button">
                        Delete
                    </button>
                    <button @click="showDeleteAllStatements = false"
                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:text-gray-400 sm:mt-0 sm:w-auto"
                        type="button">
                        Cancel
                    </button>
                </template>
            </WarningModal>
        </teleport>
    </div>


</template>