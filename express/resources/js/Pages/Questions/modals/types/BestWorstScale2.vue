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
import SingleSelect from '@/S4/SingleSelect.vue';
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

// Dimensions management
// const dimensions = ref([
//     "Quality",
//     "Performance", 
//     "Reliability",
//     "Usability",
//     "Efficiency",
//     "Satisfaction"
// ]);




const newDimension = ref("");
const newDimensionChoice = ref("");
const showAddDimensionModal = ref(false);
const duplicateDimensionWarning = ref("");

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

    let REPETITIONS_PER_ITEM = add_edit_form.setup.bestWostScale.repetitionsPerStatement;
    let usedDimensions = add_edit_form.choices.map(choice => choice.dimension).filter(dimension => dimension && dimension.trim() !== '');
    
    let countEachDimension = usedDimensions.reduce((acc, dimension) => {
        acc[dimension] = (acc[dimension] || 0) + 1;
        return acc;
    }, {});
    let countEachDimensionArray = Object.entries(countEachDimension).map(([dimension, count]) => ({ dimension, count }));
    
    let getDimensionCount = countEachDimensionArray.length;
    let getMaxCount = Math.max(...countEachDimensionArray.map(item => item.count));

    let getTotalItems = (getMaxCount * getDimensionCount) * REPETITIONS_PER_ITEM;

    // console.log("usedDimensions", usedDimensions);
    // console.log("countEachDimension", countEachDimensionArray);
    // console.log("getMaxCount", getMaxCount);

    let SETS = getTotalItems / getDimensionCount;
    let ITEMS_PER_SET = getDimensionCount;
    


    //console.log(`Sets: ${sets}, Items per Set: ${itemsPerSet}`);  

    add_edit_form.setup.bestWostScale.itemsPerSet = ITEMS_PER_SET
    add_edit_form.setup.bestWostScale.sets = SETS
    //alert(itemsPerSet)
    return { 'itemsPerSet': ITEMS_PER_SET, 'sets': SETS }
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
                    dimension: ""
                });
            }
        });
    }

    MultipleInput.value = "";
    MultipleInputElem.value.forceClear();
    equivalentArea.value = "";
    MultipleInput.show = false;

};

const cancelMultipleInputs = () => {
    MultipleInput.show = false;
    // Clear all text areas
    MultipleInput.value = '';
    equivalentArea.value = '';
    // Force clear the TE2 component if it has a forceClear method
    if (MultipleInputElem.value && MultipleInputElem.value.forceClear) {
        MultipleInputElem.value.forceClear();
    }
};
const showDeleteAllStatements = ref(false);
const showDeleteAllDimensions = ref(false);
const deleteAllStatements = () => {
    add_edit_form.choices = [];
    showDeleteAllStatements.value = false;
};
const deleteAllDimensions = () => {
    add_edit_form.columns = [];
    add_edit_form.choices.forEach(choice => {
        choice.dimension = "";
    });
    showDeleteAllDimensions.value = false;
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
                dimension: ""
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


const dimensionList = computed(() => {
    let list = [{value: "", text: "Select Dimension"}]
    list.push(...add_edit_form.columns.map(dim => ({ value: dim.name, text: dim.name })))
    return list
})

// Dimension management methods
const addNewDimension = () => {
    const name = newDimension.value.trim();

    // Clear any previous warning
    duplicateDimensionWarning.value = "";

    if (!name) {
        duplicateDimensionWarning.value = "Please enter a dimension name.";
        return;
    }

    const existingNames = add_edit_form.columns.map(dim => (dim.name || "").toLowerCase());
    if (existingNames.includes(name.toLowerCase())) {
        duplicateDimensionWarning.value = "This dimension already exists. Please select it from the Dimension dropdown.";
        return;
    }

    add_edit_form.columns.push({
        name,
        type: "text",
        index: add_edit_form.columns.length === 0
            ? 1
            : Math.max(...add_edit_form.columns.map((o) => o.index)) + 1
    });
    newDimensionChoice.value.dimension = name;
    showAddDimensionModal.value = false;
};

// const removeDimension = (dimension) => {
//     const index = dimensions.value.indexOf(dimension);
//     if (index > -1) {
//         dimensions.value.splice(index, 1);
//     }
// };

const openAddDimensionModal = (choice) => {
    showAddDimensionModal.value = true;
    newDimension.value = "";
    duplicateDimensionWarning.value = "";
    newDimensionChoice.value = choice;
};

const add_edit_form = inject("add_edit_form");
const $VariableName = inject("$VariableName");

if (!add_edit_form.setup.bestWostScale['sets_enabler']) {
    add_edit_form.setup.bestWostScale['sets_enabler'] = true;
}

// Initialize dimensions from existing choices
onBeforeMount(() => {
    if (add_edit_form.choices && add_edit_form.choices.length > 0) {
        // const existingDimensions = add_edit_form.choices
        //     .map(choice => choice.equivalent)
        //     .filter(equivalent => equivalent && equivalent.trim() !== '')
        //     .filter((value, index, self) => self.indexOf(value) === index); // Remove duplicates
        
        // existingDimensions.forEach(dimension => {
        //     if (!dimensions.value.includes(dimension)) {
        //         dimensions.value.push(dimension);
        //     }
        // });
    }
});
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
        <div class="grid grid-cols-1 gap-2">
            <div class="text-right w-full pt-6" v-if="add_edit_form.choices.length >= 1">

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

                    <button @click="showDeleteAllDimensions = true"
                        class="inline-flex items-center px-4 py-2 mb-2 hover:bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-[1.1]">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 mr-2 transition-all duration-300 ease-in-out hover:text-red-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>

                        <span class="transition-all duration-300 ease-in-out hover:text-red-600">
                            Delete All Dimensions
                        </span>
                    </button>
            </div>

            <div class="grid grid-cols-[40px_1fr_250px_80px_250px] gap-1 items-center"
                v-if="add_edit_form.choices.length > 0">
                <!-- <jet-label class="font-bold w-[20px]"> </jet-label> -->
                <jet-label class="font-bold w-[60px]">Index</jet-label>
                <jet-label class="font-bold w-full">Item</jet-label>
                <jet-label class="font-bold w-full">Equivalent</jet-label>
                <jet-label class="font-bold w-full">Dimension</jet-label>
                <div><br><br></div>
            </div>
            <div class="mt-[-20px]">
                <SlickList axis="y" v-model:list="add_edit_form.choices">
                    <SlickItem v-for="(choice, index) in add_edit_form.choices" :key="choice" :index="index"
                        class="draggable-list select-none" handle=".drag-handle2">
                        <div v-if="choice" class="grid grid-cols-[auto_1fr_250px_250px_40px_40px] gap-1 items-center pt-2">
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
                            <div>
                                <div class="flex items-end">
                                    <div class="z-1 relative w-full">
                                    <SingleSelect  :options="dimensionList" :modelValue="add_edit_form.choices[index].dimension" @update:modelValue="(res) => {
                                        add_edit_form.choices[index].dimension = res
                                    }" @mousedown.stop @click.stop></SingleSelect>
                                    </div>
                                    <button 
                                        @click="openAddDimensionModal(add_edit_form.choices[index])"
                                        class="rounded-[0px_4px_4px_0px] relative inline-flex items-center px-2 py-[11px] text-sm bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white -ml-1 z-2  transition-colors duration-200 whitespace-nowrap"
                                        :title="`${add_edit_form.columns.length} dimensions available`"
                                    >
                                        Add
                                    </button>
                                </div>
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
                            @click="cancelMultipleInputs()">Cancel</s4-button>

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
                                            <s4-input color="ssrGray" class="w-full" placeholder="4 (default)" :disabled="true"
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


            <WarningModal :show="showDeleteAllDimensions" @close="showDeleteAllDimensions = false">
                <template #header>
                    Confirm Deletion
                </template>
                <template #content>
                    Are you sure you want to delete <span class="font-semibold">all dimensions</span>?
                    This action is irreversible.
                </template>
                <template #footer>
                    <button @click="deleteAllDimensions"
                        class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
                        type="button">
                        Delete
                    </button>
                    <button @click="showDeleteAllDimensions = false"
                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:text-gray-400 sm:mt-0 sm:w-auto"
                        type="button">
                        Cancel
                    </button>
                </template>
            </WarningModal>


            
            <!-- Add New Dimension Modal -->
            <div v-if="showAddDimensionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 w-96 max-w-md">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Add New Dimension</h3>
                        <button @click="showAddDimensionModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Dimension Name</label>
                        <s4-input 
                            color="ssrGray" 
                            class="w-full" 
                            placeholder="Enter dimension name"
                            v-model="newDimension"
                            @keyup.enter="addNewDimension"
                        >
                        </s4-input>
                        <p v-if="duplicateDimensionWarning" class="mt-1 text-xs text-red-600">
                            {{ duplicateDimensionWarning }}
                        </p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button 
                            @click="showAddDimensionModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors duration-200"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="addNewDimension"
                            class="px-4 py-2 text-sm font-medium text-white bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] rounded-md transition-colors duration-200"
                        >
                            Add Dimension
                        </button>
                    </div>
                </div>
            </div>
        </teleport>
    </div>


</template>