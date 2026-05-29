<script setup>
import JetLabel from "@/Jetstream/Label.vue";
import WarningModal from "../../../../S4/Warning.vue";
import S4Checkbox from "@/S4/Checkbox.vue";
import S4TextArea from "@/S4/TextArea.vue";
import S4Input from "@/S4/Input.vue";
import S4Button from "@/S4/Button.vue";
import VueFormMultiselect from "@/S4/vueform/Multiselect.vue";
import BorderSetup from "@/S4/BorderSetup.vue";
import question from "/public/images/question.svg";
import Tooltip from "@/S4/Tooltip.vue";
import { computed, nextTick, ref, watch, inject, reactive, onBeforeMount } from "vue";
import ButtonIconsVue from "../../../../S4/ButtonIcons.vue";
import { SlickList, SlickItem } from "vue-slicksort";
import TE2 from "@/S4/TE2.vue";
import S4InputSlider2 from "@/S4/InputSlider2.vue";

const add_edit_form = inject("add_edit_form");

const itemRefs = ref([]);

const new_choice = ref("");
watch(new_choice, async (val) => {
    if (val !== "") {
        // Determine the next index based on existing items
        let nextIndex = add_edit_form.items.length === 0
            ? 1
            : Math.max(...add_edit_form.items.map((o) => o.index || 0)) + 1;

        // Add a new item with the correct index
        add_edit_form.items.push({
            name: val,
            equivalent: "",
            index: nextIndex, // Always assign a valid index
        });

        await nextTick();

        // Focus the newly added item's input field
        itemRefs.value[add_edit_form.items.length - 1].focus();

        // Reset the new choice value
        new_choice.value = "";
    }

    // console.log("Updated items: ", add_edit_form);
});


const showRowTextArea = ref(false);
const rowArea = ref(null);
const equivalentArea = ref(null);

const randomChoices = computed(() => {
    let list = {
        random: [],
    };
    let items = { ...reAssignItems() };
    for (const key in items) {
        const val = parseInt(key) + 1;
        let iItem = items[key]?.index ? items[key].index : val;
        let data = {
            value: items[key]?.index,
            label: `${iItem}: ${items[key].name}`,
        };
        list.random.push({ ...data });
    }
    return list;
});

const deleteChoice = (index) => {
    deleteMultipleSelected("randomChoices", index);
    add_edit_form.items.splice(index, 1);
    setTimeout(() => {
        add_edit_form.items = reAssignItems();
    }, 500);
};

const reAssignItems = () => {
    // console.log(add_edit_form.items);
    return reactive(
        add_edit_form.items.filter((element) => {
            return element !== null && element !== undefined;
        })
    );
};

const deleteMultipleSelected = (selected_key, index) => {
    const val = index + 1;
    const exist_index = add_edit_form.setup[selected_key].findIndex(
        (value) => value == val
    );
    for (const key in add_edit_form.setup[selected_key]) {
        // re assign values to selected random  choices
        if (add_edit_form.setup[selected_key][key] > val) {
            add_edit_form.setup[selected_key][key] -= 1;
        }
    }
    //delete after re assigning
    if (exist_index != -1) {
        add_edit_form.setup[selected_key].splice(exist_index, 1);
    }
};

const revertRow = () => {
    showRowTextArea.value = false;
    // Clear all text areas
    rowArea.value = '<p><br></p>';
    equivalentArea.value = '';
    // Force clear the TE2 component if it has a forceClear method
    if (MultipleInputElem.value && MultipleInputElem.value.forceClear) {
        MultipleInputElem.value.forceClear();
    }
};
const splitForRow = () => {
    if (rowArea != null) {
        const newEq = equivalentArea.value?.split("\n") ?? [];
        const newRow =
            rowArea.value.match(/<p>(.*?)<\/p>/g)?.map((p) =>
                p.replace(/<\/?p>/g, "").trim()
            ) || [];

        _.forEach(newRow, (row, index) => {
            if (!row) {
                return;
            }

            const eqRaw = newEq[index]?.trim();
            const equivalent = eqRaw && eqRaw !== "" ? eqRaw : row;

            add_edit_form.items.push({
                name: "<p>" + row + "</p>",
                equivalent,
                validate: {
                    type: 1,
                    min: 0,
                    max: 5,
                    errorMsg: "This question requires an answer",
                },
                index:
                    add_edit_form.items.length === 0
                        ? 1
                        : Math.max(
                            ...add_edit_form.items.map((o) => o.index)
                        ) + 1,
            });
        });
    }

    rowArea.value = "<p><br></p>";
    equivalentArea.value = "";
    showRowTextArea.value = false;
};
//const for box style max-width
const boxStyleValue = ref('');
const boxStyleUnit = ref('%');

const maxWidthComputed = computed({
    get() {
        return boxStyleUnit.value === "auto"
            ? "auto"
            : `${boxStyleValue.value}${boxStyleUnit.value}`;
    },
    set(newValue) {
        if (newValue === "auto") {
            boxStyleValue.value = null; // Clear the numeric value
            boxStyleUnit.value = "auto";
        } else {
            const match = newValue.match(/^(\d+)([a-z%]+)$/);
            if (match) {
                boxStyleValue.value = parseFloat(match[1]);
                boxStyleUnit.value = match[2];
            }
        }
    }
});

watch(maxWidthComputed, (newValue) => {
    add_edit_form.setup.boxStyle['max-width'] = newValue;
});

// Auto-select all choices when random choices checkbox is checked
watch(
    () => add_edit_form.setup.validate.randomChoices,
    async (newValue) => {
        if (newValue) {
            // Wait for DOM update to ensure the multiselect is rendered
            await nextTick()
            // Select all options by extracting the values from randomChoices
            add_edit_form.setup.randomChoices = randomChoices.value.random.map(choice => choice.value)
        } else {
            add_edit_form.setup.randomChoices = [];
        }
    }
);

// Auto-enable Random Choices and include all items when new items are added
watch(
    () => add_edit_form.items.length,
    async (newLength, oldLength) => {
        if (newLength > oldLength) {
            if (!add_edit_form.setup.validate.randomChoices) {
                add_edit_form.setup.validate.randomChoices = true
                return
            }
            await nextTick()
            add_edit_form.setup.randomChoices = randomChoices.value.random.map(choice => choice.value)
        }
    }
);

// Keep Random Choices selection in sync with any changes to items (renumber, edits, deletes)
watch(
    () => add_edit_form.items,
    async () => {
        if (add_edit_form?.setup?.validate?.randomChoices) {
            await nextTick();
            add_edit_form.setup.randomChoices = randomChoices.value.random.map((choice) => choice.value);
        }
    },
    { deep: true }
)

// Watch for changes in randomChoices array to disable checkbox when empty
watch(
    () => add_edit_form.setup.randomChoices,
    (newValue) => {
        console.log('Random choices array changed:', newValue);
        if (Array.isArray(newValue) && newValue.length === 0) {
            console.log('Random choices array is empty, disabling checkbox');
            add_edit_form.setup.validate.randomChoices = false;
        }
    },
    { deep: true }
);

onBeforeMount(() => {
    if (!('boxStyle' in add_edit_form.setup)) {
        add_edit_form.setup['boxStyle'] = { 'max-width': '100%' };
    }
    const maxWidth = add_edit_form.setup.boxStyle['max-width'] || '100%';
    if (maxWidth === "auto") {
        boxStyleValue.value = null;
        boxStyleUnit.value = "auto";
    } else {
        const match = maxWidth.match(/^(\d+)([a-z%]+)$/);
        if (match) {
            boxStyleValue.value = parseFloat(match[1]);
            boxStyleUnit.value = match[2];
        }
    }
    // const hasValidateKey = add_edit_form.items.some(item => 'validate' in item);

    // if (hasValidateKey == true) {
    //     add_edit_form.items = []
    // }
    // add_edit_form.choices = []

});
const showDeleteAllModal = ref(false);
const deleteAllChoices = () => {
    add_edit_form.items = [];
    showDeleteAllModal.value = false;
};

// Handle X SVG click in Random Choices multiselect
const handleRandomChoicesTagRemoved = (option, event) => {
    // This function is kept for potential future use
    // The actual logic is now handled by the watcher above
    console.log('Random Choices tag removed event triggered!', option, event);
};
</script>
<template>
    <div class="flex justify-end" v-if="add_edit_form.items.length > 0">
        <button @click="showDeleteAllModal = true"
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
    <div class="grid grid-cols-12 gap-1">
        <div class="col-span-12">
            <div class="grid grid-cols-[60px_1fr_1fr_5px] gap-1" v-if="add_edit_form.items.length > 0">
                <!-- <jet-label class="font-bold">Order</jet-label> -->
                <jet-label class="font-bold">Index</jet-label>
                <jet-label class="font-bold">Items</jet-label>
                <jet-label class="font-bold">Equivalent</jet-label>
            </div>

            <SlickList axis="y" v-model:list="add_edit_form.items">
                <SlickItem v-for="(choice, index) in add_edit_form.items" :key="choice" :index="index">
                    <div class="grid grid-cols-[60px_1fr_1fr_0fr] gap-1 mb-1" v-if="choice">

                        <!-- <span class="mt-2 pl-4">{{ index + 1 }}</span> -->
                        <span class="mt-2 pl-4">{{
                            (add_edit_form.items[index]?.index
                                ? add_edit_form.items[index]?.index
                                : index + 1)
                        }}</span>
                        <!-- <s4-input color="ssrGray" class="w-full mb-1" placeholder="Enter choice"
                            v-model="add_edit_form.items[index].name" :ref="(el) => {
                                itemRefs[index] = el;
                            }
                                ">
                        </s4-input> -->

                        <TE2 color="ssrGray" class="w-full" placeholder="Enter items"
                            :modelValue="add_edit_form.items[index].name"
                            @update:modelValue="(val) => { add_edit_form.items[index].name = val }" :ref="(el) => {
                                itemRefs[index] = el;
                            }" @mousedown.stop></TE2>

                        <s4-input color="ssrGray" class="w-full" placeholder="Enter Equivalent"
                            v-model="add_edit_form.items[index].equivalent">
                        </s4-input>


                        <ButtonIconsVue icon="delete" @click="deleteChoice(index)" />
                        <!-- <s4-button color="ssrRedIcon" class="mb-1 ml-1" @click="deleteChoice(index)">
                        <font-awesome-icon class="fa-w-20 fa-h-20" :icon="faIcon.trash" />
                    </s4-button> -->
                    </div>
                </SlickItem>
            </SlickList>
        </div>
        <div class="col-span-12">
            <s4-input color="ssrGray" class="w-full mb-2" placeholder="Input new item" v-model="new_choice"></s4-input>
        </div>
        <div class="grid grid-cols-6 gap-2 w-full col-span-12"></div>
        <button v-show="showRowTextArea == false" :theme="'ssrAddColRow'" @click="showRowTextArea = !showRowTextArea"
            class="inline-flex w-[165px] items-center px-2 py-1 mb-2 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
            <svg width="32" height="32" viewBox="0 0 980 980" fill="none" xmlns="http://www.w3.org/2000/svg">
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

            <span class="transition-all duration-300 ease-in-out hover:text-white">
                Add Multiple Inputs
            </span>
        </button>
        <div class="grid grid-cols-2 gap-1 col-span-12">
            <!-- <div>
                <s4-text-area color="ssrGray" v-show="showRowTextArea" v-model="rowArea" class="w-full h-40"
                    placeholder="Enter choices separated by new line..."></s4-text-area>
            </div> -->
            <jet-label v-show="showRowTextArea">Enter Items</jet-label>
            <jet-label v-show="showRowTextArea">Enter Equivalent</jet-label>
            <TE2 v-show="showRowTextArea" ref="MultipleInputElem" placeholder="Enter items separated by new line..."
                :modelValue="rowArea" height="160px" @update:modelValue="(val) => { rowArea = val }"></TE2>

            <s4-text-area v-show="showRowTextArea" color="ssrGray" v-model="equivalentArea" :filter="true"
                class="w-full h-40" placeholder="Enter equivalent separated by new line..."></s4-text-area>


            <div class="flex mb-10" v-show="showRowTextArea">
                <s4-button :theme="'ssrAddColRow'"
                    class="py-1 px-2 mt-2 !bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg"
                    @click="splitForRow()">Add</s4-button>
                <s4-button :theme="'ssrAddColRow'"
                    class="py-1 px-2 ml-1 mt-2 bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-125 hover:bg-transparent"
                    @click="revertRow()">Cancel</s4-button>
            </div>
        </div>
    </div>

    <BorderSetup></BorderSetup>

    <div class="mt-4 grid grid-cols-1 lg:grid-cols-6 gap-1">
        <div class="col-span-1 lg:col-span-3">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-1">
                <div class="col-span-6">
                    <label class="flex items-center cursor-pointer w-[110px]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.required" />
                        <span class="ml-2 text-sm text-gray-600 select-none">Required</span>
                        <Tooltip :imgSrc="question" preset="Required">
                        </Tooltip>
                    </label>
                    <!-- <div class="mt-2 pl-6" v-if="add_edit_form.setup.validate.required">
                        <s4-input color="ssrGray" class="w-full mb-1" :disabled="add_edit_form.setup.validate.required == 0
                            " v-model="add_edit_form.setup.validate.errorMsg">
                        </s4-input>
                        <TE2 color="ssrGray" class="w-full mb-1" placeholder=""
                            :disabled="add_edit_form.setup.validate.required == 0"
                            :modelValue="add_edit_form.setup.validate.errorMsg"
                            @update:modelValue="(val) => { add_edit_form.setup.validate.errorMsg = val }"></TE2>
                    </div> -->
                </div>
                <div class="col-span-6">
                    <label class="items-center cursor-pointer flex">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.enableDecimal
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Allow Decimal</span>
                    </label>
                </div>
                <!-- <div class="grid grid-cols-6 gap-1 col-span-6" v-if="Object.hasOwn(add_edit_form.setup, 'totalLabel')">


                    <div class="col-span-6 mt-1 pl-3" v-if="add_edit_form.setup.chart.enable">

                        <s4-input placeholder="e.g. Not set" color="ssrGray" class="w-full mb-1" :disabled="add_edit_form.setup.chart.enable == 0
                                " v-model="add_edit_form.setup.chart.noDataLabel">
                            </s4-input>
                        <TE2 color="ssrGray" class="w-full mb-1" placeholder=""
                                :disabled="add_edit_form.setup.chart.enable == 0"
                                :modelValue="add_edit_form.setup.chart.noDataLabel"
                                @update:modelValue="(val) => { add_edit_form.setup.chart.noDataLabel = val }"></TE2>
                    </div>

                </div> -->
                <div class="col-span-2">
                    <label class="items-center cursor-pointer">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.enableSlider
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Slider</span>
                    </label>
                </div>
                <div class="grid grid-cols-6 gap-1 col-span-6 pl-6" v-if="Object.hasOwn(add_edit_form.setup, 'range')">

                    <div class="col-span-3" v-if="add_edit_form.setup.enableSlider">
                        <jet-label>Minimum Range</jet-label>
                        <s4-input type="number" color="ssrGray" class="w-full mb-1" min="0" :disabled="add_edit_form.setup.enableSlider == 0
                            " v-model.number="add_edit_form.setup.range.min">
                        </s4-input>
                    </div>
                    <div class="col-span-3" v-if="add_edit_form.setup.enableSlider">
                        <jet-label>Maximum Range</jet-label>
                        <s4-input type="number" color="ssrGray" class="w-full mb-1" min="0"
                            :disabled="add_edit_form.setup.enableSlider == 0"
                            v-model.number="add_edit_form.setup.range.max">
                        </s4-input>
                    </div>

                </div>
                
                <div class="col-span-6">
                    <label class="items-center cursor-pointer flex max-w-[160px]">
                        <s4-checkbox class="cursor-pointer"
                            v-model:checked="add_edit_form.setup.validate.randomChoices" />
                        <span class="ml-2 text-sm text-gray-600 select-none">Random Items</span>
                        <Tooltip :imgSrc="question" preset="RandomChoices">
                        </Tooltip>
                    </label>
                </div>
                <div class="col-span-6 pl-6" v-if="add_edit_form.setup.validate.randomChoices">
                    <!-- <jet-label>Random Choices</jet-label> -->
                    <vue-form-multiselect placeholder="Click to select choices" :options="randomChoices.random"
                        :disabled="add_edit_form.setup.validate.required == 0"
                        v-model="add_edit_form.setup.randomChoices" @tag-removed="handleRandomChoicesTagRemoved">
                    </vue-form-multiselect>
                </div>
            </div>
        </div>

        <div class="col-span-1 lg:col-span-3">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-1">
                <!-- <div class="grid grid-cols-12 gap-1 col-span-6">

                    <div class="col-span-3">
                        <div class="col-span-2">
                            <label class="items-center cursor-pointer">
                                <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.enableDecimal
                                    " />
                                <span class="ml-2 text-sm text-gray-600 select-none">Decimal</span>
                            </label>
                        </div>
                    </div>

                    <div class="col-span-3">
                        <div class="col-span-1">
                            <label class="items-center cursor-pointer">
                                <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.fixedValue" />
                                <span class="ml-2 text-sm text-gray-600 select-none">Fixed Value</span>
                            </label>
                        </div>

                    </div>

                </div> -->
                <div class="grid grid-cols-6 gap-1 col-span-6 pl-6">
                    <div class="col-span-6 grid grid-cols-3 gap-1 mt-3 ml-6">
                        <div>
                            <jet-label>Default Value Per Item</jet-label>
                            <s4-input type="number" color="ssrGray" class="w-full mb-1"
                                v-model.number="add_edit_form.setup.range.origin">
                            </s4-input>
                        </div>
                        <div :set="add_edit_form.setup.prefix || ''">
                            <jet-label>Prefix</jet-label>
                            <s4-input type="text" color="ssrGray" class="w-full mb-1"
                                v-model="add_edit_form.setup.prefix">
                            </s4-input>
                        </div>
                        <div :set="add_edit_form.setup.suffix || ''">
                            <jet-label>Suffix</jet-label>
                            <s4-input type="text" color="ssrGray" class="w-full mb-1"
                                v-model="add_edit_form.setup.suffix">
                            </s4-input>
                        </div>

                    </div>
                    <!-- <div class="col-span-6 pl-6 mt-1">
                        <label class="items-center cursor-pointer">
                            <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate2
                                " />
                            <span class="ml-2 text-sm text-gray-600 select-none">Enable Validation</span>
                        </label>
                    </div>       -->
                    <div class="col-span-3 mt-1 ml-6">
                        <jet-label>Minimum Value Per Item</jet-label>
                        <s4-input type="number" color="ssrGray" class="w-full mb-1" v-model.number="add_edit_form.setup.validate2.minValue
                            ">
                        </s4-input>
                    </div>
                    <div class="col-span-3 mt-1 ml-6">
                        <jet-label>Error Message (<i>Minimum</i>)</jet-label>
                        <!-- <s4-input type="text" color="ssrGray" class="w-full mb-1"
                            v-model="add_edit_form.setup.validate2.errorMsg">
                        </s4-input> -->
                        <TE2 color="ssrGray" class="w-full mb-1" placeholder=""
                            :modelValue="add_edit_form.setup.validate2.errorMsg"
                            @update:modelValue="(val) => { add_edit_form.setup.validate2.errorMsg = val }"></TE2>
                    </div>

                </div>

                <div class="grid grid-cols-6 gap-1 col-span-6 mt-1 pl-6"
                    v-if="Object.hasOwn(add_edit_form.setup, 'validate2')">


                    <div class="col-span-3 mt-1">
                        <div class="col-span-3">
                            <label class="items-center cursor-pointer">
                                <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.fixedValue
                                    " />
                                <span class="ml-2 text-sm text-gray-600 select-none">Total Value</span>
                            </label>
                        </div>
                        <div class="col-span-3 ml-6" v-if="add_edit_form.setup.fixedValue">
                            <s4-input type="number" color="ssrGray" class="w-full mb-1" min="0"
                                v-model.number="add_edit_form.setup.validate3.total">
                            </s4-input>
                        </div>
                        <div class="col-span-3 mt-1 ml-6" v-if="add_edit_form.setup.fixedValue">
                            <jet-label>Error Message</jet-label>
                            <!-- <s4-input type="text" color="ssrGray" class="w-full mb-1"
                                v-model="add_edit_form.setup.validate3.errorMsg">
                            </s4-input> -->
                            <TE2 color="ssrGray" class="w-full mb-1" placeholder=""
                                :modelValue="add_edit_form.setup.validate3.errorMsg"
                                @update:modelValue="(val) => { add_edit_form.setup.validate3.errorMsg = val }"></TE2>
                        </div>
                    </div>

                    <div class="col-span-3 mt-1">
                        <div class="col-span-3">
                            <label class="items-center cursor-pointer">
                                <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.totalLabel.enable
                                    " />
                                <span class="ml-2 text-sm text-gray-600 select-none ">Display Total</span>
                            </label>
                        </div>
                        <div class="col-span-3 ml-6" v-if="add_edit_form.setup.totalLabel.enable">
                            <!-- <jet-label class="ml-3">Label</jet-label> -->
                            <!-- <s4-input color="ssrGray" class="w-full mb-1" :disabled="add_edit_form.setup.totalLabel.enable == 0
                                " v-model="add_edit_form.setup.totalLabel.text">
                            </s4-input> -->
                            <TE2 color="ssrGray" class="w-full" placeholder=""
                                :disabled="add_edit_form.setup.totalLabel.enable == 0"
                                :modelValue="add_edit_form.setup.totalLabel.text"
                                @update:modelValue="(val) => { add_edit_form.setup.totalLabel.text = val }"></TE2>
                        </div>
                    </div>

                    <div class="col-span-6">
                    <label class="cursor-pointer flex items-center gap-0.5 w-[170px]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.boxStyle[
                            'max-width_enabler'
                        ]
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Edit Layout Width</span>
                        <Tooltip :imgSrc="question" preset="EditLayoutWidth">
                        </Tooltip>
                    </label>
                </div>
                <div class="col-span-6 w-full grid grid-cols-3 gap-1 pl-6">
                    <div class="" v-if="add_edit_form.setup.boxStyle['max-width_enabler']">
                        <jet-label>Desktop</jet-label>
                        <!-- <s4-input
                                color="ssrGray"
                                class="w-full mb-1"
                                placeholder="e.g. 100%"
                                v-model="add_edit_form.setup.boxStyle['max-width']"
                            >
                            </s4-input> -->
                        <S4InputSlider2 color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                            :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.boxStyle['max-width']
                                " :allowAuto="true" @update:modelValue="
                                    (val) => {
                                        add_edit_form.setup.boxStyle['max-width'] =
                                            val;
                                    }
                                ">
                        </S4InputSlider2>
                    </div>

                    <div class="" v-if="add_edit_form.setup.boxStyle['max-width_enabler']">
                        <jet-label>Tablet</jet-label>
                        <!-- <s4-input
                                color="ssrGray"
                                class="w-full mb-1"
                                placeholder="e.g. 100%"
                                v-model="add_edit_form.setup.boxStyle['max-width']"
                            >
                            </s4-input> -->

                        <!-- <S4InputSlider
                                color="ssrGray"
                                class="w-full mt-1"
                                :placeholder="'e.g. 100'"
                                :units="['%', 'vmin', 'vw', 'auto']"
                                v-model.number="boxStyleValue_tab"
                                v-model:unitValue="boxStyleUnit"
                                :allowAuto="true"
                                min="0"
                                v-model="add_edit_form.setup.boxStyle"
                            >
                            </S4InputSlider> -->

                        <S4InputSlider2 color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                            :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.boxStyle['max-width-tab'] ??
                                '100%'
                                " :allowAuto="true" @update:modelValue="
                                    (val) => {
                                        add_edit_form.setup.boxStyle[
                                            'max-width-tab'
                                        ] = val;
                                    }
                                ">
                        </S4InputSlider2>
                    </div>
                    <div class="" v-if="add_edit_form.setup.boxStyle['max-width_enabler']">
                        <jet-label>Mobile</jet-label>
                        <!-- <s4-input
                                color="ssrGray"
                                class="w-full mb-1"
                                placeholder="e.g. 100%"
                                v-model="add_edit_form.setup.boxStyle['max-width']"
                            >
                            </s4-input> -->

                        <S4InputSlider2 color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                            :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.boxStyle[
                                'max-width-mobile'
                            ] ?? '100%'
                                " :allowAuto="true" @update:modelValue="
                                    (val) => {
                                        add_edit_form.setup.boxStyle[
                                            'max-width-mobile'
                                        ] = val;
                                    }
                                ">
                        </S4InputSlider2>
                    </div>
                </div>
                </div>


                <!-- <div class="col-span-6">
                    <jet-label>Random Items</jet-label>
                    <vue-form-multiselect style="min-height: 7rem" placeholder="Click to select choices"
                        :options="randomChoices.random" v-model="add_edit_form.setup.randomChoices" />
                </div> -->
            </div>

        </div>
        <teleport to="body">
            <WarningModal :show="showDeleteAllModal" @close="showDeleteAllModal = false">
                <template #header>
                    Confirm Deletion
                </template>
                <template #content>
                    Are you sure you want to delete <span class="font-semibold">all items</span>?
                    This action is irreversible.
                </template>
                <template #footer>
                    <button @click="deleteAllChoices"
                        class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
                        type="button">
                        Delete
                    </button>
                    <button @click="showDeleteAllModal = false"
                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:text-gray-400 sm:mt-0 sm:w-auto"
                        type="button">
                        Cancel
                    </button>
                </template>
            </WarningModal>
        </teleport>
    </div>
</template>

<style scoped>
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
</style>
