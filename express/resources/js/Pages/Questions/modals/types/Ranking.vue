<script setup>
import JetLabel from "@/Jetstream/Label.vue";
import WarningModal from "../../../../S4/Warning.vue";
import S4Checkbox from "@/S4/Checkbox.vue";
import S4Input from "@/S4/Input.vue";
import S4Button from "@/S4/Button.vue";
import S4TextArea from "@/S4/TextArea.vue";
import VueFormMultiselect from "@/S4/vueform/Multiselect.vue";
import SingleSelect from "@/S4/SingleSelect.vue";
import BorderSetup from "@/S4/BorderSetup.vue";
import question from "/public/images/question.svg";
import Tooltip from "@/S4/Tooltip.vue";
import { usePage } from "@inertiajs/vue3";
import S4GalleryModal from "@/S4/modals/Gallery.vue";
import ButtonIconsVue from "../../../../S4/ButtonIcons.vue";
import helpers from "@/helpers.js";
import S4InputSlider2 from "@/S4/InputSlider2.vue";

import {
    computed,
    nextTick,
    ref,
    watch,
    inject,
    onBeforeMount,
    reactive,
} from "vue";
import TE2 from "@/S4/TE2.vue";
import RankingAnalysis from "./rankingComp/rankingAnalysis.vue";

const add_edit_form = inject("add_edit_form");

const itemRefs = ref([]);
const showRowTextArea = ref(false);

const new_item = ref("");
watch(new_item, async (val) => {
    if (val !== "" && val !== "<p><br></p>") {
        add_edit_form.items.push({
            name: val,
            equivalent: "",
            index:
                add_edit_form.items.length === 0
                    ? 1
                    : Math.max(...add_edit_form.items.map((o) => o.index)) + 1,
            score:
                add_edit_form.items.length === 0
                    ? 1
                    : Math.max(...add_edit_form.items.map((o) => o.score)) + 1,
        });
        await nextTick();
        itemRefs.value[add_edit_form.items.length - 1].focus();
        new_item.value = "<p><br></p>";
    }
});

const modeList = [
    {
        value: 0,
        text: "Rank",
    },
    {
        value: 1,
        text: "Sort",
    },
];

const positionList = [
    {
        value: 1,
        text: "Right",
    },
    {
        value: 2,
        text: "Left",
    },
    {
        value: 3,
        text: "Bottom",
    },
    {
        value: 4,
        text: "Top",
    },
];

const setupMI = {
    close: () => {
        showRowTextArea.value = false;
    },
    splitForRow: (newRow) => {
        add_edit_form.items = [...add_edit_form.items, ...newRow];
        showRowTextArea.value = false;
    },
};

const rowArea = ref("");
const equivalentArea = ref("");

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
const splitForRow = () => {
    if (rowArea.value != null) {
        const newEq = equivalentArea.value.split("\n");
        const newRow =
            rowArea.value
                .match(/<p>(.*?)<\/p>/g)
                ?.map((p) => p.replace(/<\/?p>/g, "").trim()) || [];

        _.forEach(newRow, function (row, index) {
            if (row !== "<p><br></p>" && row !== "<br>" && row !== "") {
                const eqRaw = newEq[index]?.trim?.();
                const rawEquivalent = eqRaw && eqRaw !== "" ? eqRaw : row;
                const equivalent = htmlToTextWithImageAlt(rawEquivalent);

                add_edit_form.items.push({
                    name: "<p>" + row + "</p>",
                    equivalent,
                    enable_wr: 0,
                    index:
                        add_edit_form.items.length === 0
                            ? 1
                            : Math.max(
                                  ...add_edit_form.items.map((o) => o.index)
                              ) + 1,
                    score:
                        add_edit_form.items.length === 0
                            ? 1
                            : Math.max(
                                  ...add_edit_form.items.map((o) => o.score)
                              ) + 1,
                });
            }
        });
    }

    rowArea.value = "<p><br></p>";
    equivalentArea.value = "";
    showRowTextArea.value = false;
};

const cancelMultipleInputs = () => {
    showRowTextArea.value = false;
    // Clear all text areas
    rowArea.value = '<p><br></p>';
    equivalentArea.value = '';
    // Force clear the TE2 component if it has a forceClear method
    if (MultipleInputElem.value && MultipleInputElem.value.forceClear) {
        MultipleInputElem.value.forceClear();
    }
};

const showRowArea = () => {
    showRowTextArea.value = !showRowTextArea.value;
};

const randomItems = computed(() => {
    let items = [];
    for (const key in add_edit_form.items) {
        const val = parseInt(key) + 1;
        let data = {
            value: val,
            label: add_edit_form.items[key].name,
        };

        items.push({ ...data });
    }
    return items;
});

const deleteChoice = (choice, index) => {
    add_edit_form[choice].splice(index, 1);
};

//const for box style max-width
const boxStyleValue = ref("");
const boxStyleUnit = ref("%");

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
    },
});

watch(maxWidthComputed, (newValue) => {
    add_edit_form.setup.boxStyle["max-width"] = newValue;
});

onBeforeMount(() => {
    if (!("boxStyle" in add_edit_form.setup)) {
        add_edit_form.setup["boxStyle"] = { "max-width": "100%" };
    }
    const maxWidth = add_edit_form.setup.boxStyle["max-width"] || "100%";
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
    add_edit_form.choices = [];
});

const gallery = reactive({
    show: false,
    index: -1,
});
const showGallery = (index) => {
    gallery.index = index; // Set the index, -1 for `new_item`
    gallery.show = true;
};

const closeGallery = () => {
    gallery.show = false;
};

const selectGallery = (el) => {
    const { ImageWithHeaders } = helpers();
    const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;
    const imageUrl = el.file_path
        ? `${fluentBase}/storage/${el.file_path}`
        : ImageWithHeaders(`${usePage().props.app_url}/gallery/${el.filename}`);
    const imageTag = `<img src="${imageUrl}" />`;
    if (gallery.index >= 0) {
        // Update existing item
        add_edit_form.items[gallery.index].name = imageTag;
    } else {
        // Populate `new_item`
        new_item.value = imageTag; // Trigger the watcher for `new_item`
    }
    closeGallery();
};
const showDeleteAllModal = ref(false);
const deleteAllItems = () => {
    add_edit_form.items = [];
    showDeleteAllModal.value = false;
};

// Auto-select all items when random values checkbox is checked
watch(
    () => add_edit_form.setup.validate.randomItems,
    async (newValue) => {
        if (newValue) {
            // Wait for DOM update to ensure the multiselect is rendered
            await nextTick()
            // Select all options by extracting the values from randomItems
            add_edit_form.setup.randomItems = randomItems.value.map(item => item.value)
        } else {
            add_edit_form.setup.randomItems = [];
        }
    }
);

// Auto-add new items to random values when checkbox is already checked
watch(
    () => add_edit_form.items.length,
    async (newLength, oldLength) => {
        if (add_edit_form.setup.validate.randomItems && newLength > oldLength) {
            // Wait for DOM update to ensure the multiselect is rendered
            await nextTick()
            // Update random values to include all current items
            add_edit_form.setup.randomItems = randomItems.value.map(item => item.value)
        }
    }
);

// Watch for changes in randomItems array to disable checkbox when empty
watch(
    () => add_edit_form.setup.randomItems,
    (newValue) => {
        if (Array.isArray(newValue) && newValue.length === 0) {
            add_edit_form.setup.validate.randomItems = false;
        }
    },
    { deep: true }
);

// Handle X SVG click in Random Values multiselect
const handleRandomValuesTagRemoved = () => {
    // This function is kept for potential future use
    // The actual logic is now handled by the watcher above
};

</script>

<template>
    <div v-if="add_edit_form.items.length > 0" class="flex justify-end">
        <button @click="showDeleteAllModal = true"
            class="inline-flex items-center px-4 py-2 hover:bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-[1.1]">
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
        <div class="col-span-12 mt-3">
            <div class="col-span-6 mt-3">
                <div v-if="add_edit_form.items.length > 0" class="grid grid-cols-[50px_1fr_1fr_40px] gap-1">
                    <jet-label class="font-bold">Index</jet-label>
                    <jet-label class="font-bold">Items</jet-label>
                    <jet-label class="font-bold">Equivalent</jet-label>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-1">
                <template v-for="(item, index) in add_edit_form.items" :key="index">
                    <div class="grid grid-cols-[50px_1fr_1fr_40px] gap-1">
                        <span class="mt-2 mr-2 pl-3">{{ index + 1 }}</span>

                        <div class="w-full">

                            <TE2 placeholder="Enter item" :modelValue="add_edit_form.items[index].name"
                                @update:modelValue="
                                    (val) => {
                                        add_edit_form.items[index].name = val;
                                    }
                                " :ref="(el) => {
                                    itemRefs[index] = el;
                                }
                                    " @mousedown.stop></TE2>


                        </div>
                        <s4-input color="ssrGray" class="w-full" placeholder="Enter Equivalent"
                            v-model="add_edit_form.items[index].equivalent">
                        </s4-input>
                        <ButtonIconsVue icon="delete" @click="deleteChoice('items', index)" />
                    </div>
                </template>
            </div>
            <div class="col-span-6">

                <div class="w-full py-1">

                    <TE2 v-if="!showRowTextArea" placeholder="Input new item" :modelValue="new_item" @update:modelValue="
                        (val) => {
                            new_item = val;
                        }
                    "></TE2>
                </div>
            </div>
            <button v-show="showRowTextArea == false" :theme="'ssrAddColRow'"
                @click="showRowTextArea = !showRowTextArea"
                class="inline-flex w-[160px] items-center px-2 py-1 mb-2 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
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
                <div class="grid grid-cols-2 gap-1 col-span-12" v-show="showRowTextArea">
                <div class="flex flex-col gap-1">
                    <jet-label class="text-xs font-semibold text-gray-600">Enter Items</jet-label>
                    <TE2 ref="MultipleInputElem" placeholder="Enter choices separated by new line..." :modelValue="rowArea"
                        height="158px" @update:modelValue="
                            (val) => {
                                rowArea = val;
                            }
                        "></TE2>
                </div>
                <div class="flex flex-col gap-1">
                    <jet-label class="text-xs font-semibold text-gray-600">Enter Equivalent</jet-label>
                    <s4-text-area :filter="true" color="ssrGray" v-model="equivalentArea" class="w-full h-40"
                        placeholder="Enter equivalent separated by new line..."></s4-text-area>
                </div>
                <div class="flex mb-5" v-show="showRowTextArea">
                    <s4-button :theme="'ssrAddColRow'"
                        class="py-1 px-2 mt-2 !bg-[var(--mcolor)] hover:!bg-[var(--mcolor2)] text-white text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg"
                        @click="splitForRow()">Add</s4-button>
                    <s4-button :theme="'ssrAddColRow'"
                        class="py-1 px-2 ml-1 mt-2 bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-125 hover:bg-transparent"
                        @click="cancelMultipleInputs()">Cancel</s4-button>
                </div>
            </div>
        </div>
    </div>

    <div class="grid ranking-settings-grid gap-4 pt-10 gap-y-10"
        :set="helpers().initProperty(add_edit_form.setup, 'itemDimension')">
        <!-- Left: Validation & Layout -->
        <div class="md:col-span-2 flex flex-col gap-4 bg-white rounded-lg shadow p-4">
            <BorderSetup :class="['!-mb-8 -mt-4 -mx-4 translate-y-[-20%]']"></BorderSetup>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="flex flex-col flex-wrap gap-2">
                    <!-- Required -->
                    <label class="flex items-center cursor-pointer max-w-[fit-content]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.required" />
                        <span class="ml-2 text-sm text-gray-600 select-none">Required</span>
                        <Tooltip :imgSrc="question" preset="Required" />
                    </label>

                    <!-- Random Values -->
                    <div class="flex flex-col flex-wrap gap-2 w-full">
                        <label class="flex items-center cursor-pointer max-w-[fit-content]">
                            <s4-checkbox class="cursor-pointer"
                                v-model:checked="add_edit_form.setup.validate.randomItems" />
                            <span class="ml-2 text-sm text-gray-600 select-none">Random Items</span>
                            <Tooltip :imgSrc="question" preset="RandomValues" />
                        </label>
                        <!-- Random Values Multiselect -->
                        <div v-if="add_edit_form.setup.validate.randomItems" class="ml-6">
                            <vue-form-multiselect placeholder="Click to select items" :options="randomItems"
                                v-model="add_edit_form.setup.randomItems" @tag-removed="handleRandomValuesTagRemoved" />
                        </div>
                    </div>

                    <!-- Ranking Display Mode -->
                    <div class="flex flex-col flex-wrap gap-2 w-full ml-2">
                        <label class="flex items-center cursor-pointer max-w-[fit-content]">
                            <span class="text-sm text-gray-600 select-none">Display Style</span>
                        </label>
                        <SingleSelect class="w-full" :options="modeList" :modelValue="add_edit_form.setup.mode"
                            @update:modelValue="(val) => add_edit_form.setup.mode = Number(val)"></SingleSelect>
                    </div>

                    <div class="flex flex-col flex-wrap gap-2 w-full ml-2" v-if="add_edit_form.setup.mode == 1">
                        <!-- Number of Columns -->
                        <div class="flex flex-col flex-wrap gap-2 w-full">
                            <label class="flex items-center cursor-pointer max-w-[fit-content]">
                                <span class="text-sm text-gray-600 select-none">Number of Columns to Display</span>
                            </label>
                            <s4-input color="ssrGray" class="w-full" type="number"
                                v-model.number="add_edit_form.setup.numberOfColumns">
                            </s4-input>
                        </div>
                    </div>

                    <div class="flex flex-col flex-wrap gap-2 w-full ml-2" v-if="add_edit_form.setup.mode == 0">
                        <!-- Display Position -->
                        <div class="flex flex-col flex-wrap gap-2 w-full">
                            <label class="flex items-center cursor-pointer max-w-[fit-content]">
                                <span class="text-sm text-gray-600 select-none">Ranking Position</span>
                            </label>
                            <SingleSelect class="w-full" :options="positionList"
                                :modelValue="add_edit_form.setup.position"
                                @update:modelValue="(val) => add_edit_form.setup.position = Number(val)"></SingleSelect>
                        </div>



                        <!-- Drop Zone Header-->
                        <div class="flex flex-col flex-wrap gap-2 w-full">
                            <label class="flex items-center cursor-pointer max-w-[fit-content]">
                                <span class="text-sm text-gray-600 select-none">Drop Zone Header</span>
                            </label>
                            <TE2 class="mt-1" :modelValue="add_edit_form.setup.mostPreferred" @update:modelValue="
                                (val) => {
                                    add_edit_form.setup.mostPreferred = val;
                                }
                            "></TE2>
                        </div>


                        <!-- Drop Zone Footer-->
                        <div class="flex flex-col flex-wrap gap-2 w-full">
                            <label class="flex items-center cursor-pointer max-w-[fit-content]">
                                <span class="text-sm text-gray-600 select-none">Drop Zone Footer</span>
                            </label>
                            <TE2 class="mt-1" :modelValue="add_edit_form.setup.leastPreferred" @update:modelValue="
                                (val) => {
                                    add_edit_form.setup.leastPreferred = val;
                                }
                            "></TE2>
                        </div>

                        <!-- Drop Zone Label-->
                        <div class="flex flex-col flex-wrap gap-2 w-full">
                            <label class="flex items-center cursor-pointer max-w-[fit-content]">
                                <span class="text-sm text-gray-600 select-none">Drop Zone Label</span>
                            </label>
                            <TE2 class="mt-1" :modelValue="add_edit_form.setup.dropzone" @update:modelValue="
                                (val) => {
                                    add_edit_form.setup.dropzone = val;
                                }
                            "></TE2>
                        </div>


                        <!-- Max Limit Item Selection -->
                        <div class="flex flex-col flex-wrap gap-2 w-full">
                            <label class="flex items-center cursor-pointer max-w-[fit-content]">
                                <span class="text-sm text-gray-600 select-none">Maximum Items User Can Select</span>
                            </label>
                            <s4-input color="ssrGray" class="w-full" type="number"
                                v-model.number="add_edit_form.setup.limit">
                            </s4-input>
                        </div>


                        <!-- Minimum Item Selection -->
                        <div class="flex flex-col flex-wrap gap-2 w-full">
                            <label class="flex items-center cursor-pointer max-w-[fit-content]">
                                <span class="text-sm text-gray-600 select-none">Minimum Items User Can Select</span>
                            </label>
                            <s4-input color="ssrGray" class="w-full" type="number"
                                v-model.number="add_edit_form.setup.validate2.minValue">
                            </s4-input>
                        </div>


                        <!-- Limit Error Message-->
                        <div class="flex flex-col flex-wrap gap-2 w-full">
                            <label class="flex items-center cursor-pointer max-w-[fit-content]">
                                <span class="text-sm text-gray-600 select-none">Message When Limit Is Exceeded</span>
                            </label>
                            <TE2 class="h-[43px] mt-1" :modelValue="add_edit_form.setup.validate2.errorMsg"
                                @update:modelValue="
                                    (val) => {
                                        add_edit_form.setup.validate2.errorMsg = val;
                                    }
                                "></TE2>
                        </div>


                    </div>


                </div>

                <div class="flex flex-col flex-wrap gap-2">

                    
                    <!-- Edit Layout Width -->
                    <div class="flex flex-col flex-wrap gap-2 w-full relative z-10">
                        <label class="flex items-center cursor-pointer max-w-[fit-content]">
                            <s4-checkbox class="cursor-pointer"
                                v-model:checked="add_edit_form.setup.boxStyle['max-width_enabler']" />
                            <span class="ml-2 text-sm text-gray-600 select-none">Edit Layout Width</span>
                            <Tooltip :imgSrc="question" preset="EditLayoutWidth" />
                        </label>

                        <!-- Layout Width Sliders -->
                        <div v-if="add_edit_form.setup.boxStyle['max-width_enabler']"
                            class="flex flex-col sm:flex-row gap-4 pl-6 sm:pl-6 sm:ml-0 max-w-full relative z-10">
                            <div class="flex-1 min-w-0">
                                <div class="block whitespace-nowrap mb-1">
                                    <jet-label>Desktop</jet-label>
                                </div>
                                <S4InputSlider2 color="ssrGray" class="w-full" :placeholder="'e.g. 100'"
                                    :units="['%', 'vmin', 'vw', 'auto']"
                                    :modelValue="add_edit_form.setup.boxStyle['max-width'] ?? '100%'" :allowAuto="true"
                                    @update:modelValue="val => add_edit_form.setup.boxStyle['max-width'] = val" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="block whitespace-nowrap mb-1">
                                    <jet-label>Tablet</jet-label>
                                </div>
                                <S4InputSlider2 color="ssrGray" class="w-full" :placeholder="'e.g. 100'"
                                    :units="['%', 'vmin', 'vw', 'auto']"
                                    :modelValue="add_edit_form.setup.boxStyle['max-width-tab'] ?? '100%'"
                                    :allowAuto="true"
                                    @update:modelValue="val => add_edit_form.setup.boxStyle['max-width-tab'] = val" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="block whitespace-nowrap mb-1">
                                    <jet-label>Mobile</jet-label>
                                </div>
                                <S4InputSlider2 color="ssrGray" class="w-full" :placeholder="'e.g. 100'"
                                    :units="['%', 'vmin', 'vw', 'auto']"
                                    :modelValue="add_edit_form.setup.boxStyle['max-width-mobile'] ?? '100%'"
                                    :allowAuto="true"
                                    @update:modelValue="val => add_edit_form.setup.boxStyle['max-width-mobile'] = val" />
                            </div>
                        </div>


                    </div>
                </div>

            </div>
        </div>

        <!-- Right: Advanced (type 15 only) -->
        <div
            class="md:col-span-1 flex-1 bg-white rounded-lg shadow p-4 grid grid-cols-1 gap-4 justify-start items-start">
            <div>
                <BorderSetup :class="['!-mb-8 -mt-4 -mx-4 translate-y-[-100%]']">
                    <template #header>
                        Analysis Options
                    </template>
                </BorderSetup>

            </div>
            <RankingAnalysis />
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
                    <button @click="deleteAllItems"
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
        <s4-gallery-modal :type="'image'" :modal="gallery" @close="closeGallery()" @selectGallery="selectGallery" />



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

.ranking-settings-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

@media (max-width: 1600px) {
    .ranking-settings-grid {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
}
</style>
