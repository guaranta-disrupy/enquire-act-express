<script setup>
import JetLabel from "@/Jetstream/Label.vue";
import WarningModal from "../../../../S4/Warning.vue";
import S4Checkbox from "@/S4/Checkbox.vue";
import S4TextArea from "@/S4/TextArea.vue";
import S4Input from "@/S4/Input.vue";
import S4InputSelect from "@/S4/InputSelect.vue";
import S4Button from "@/S4/Button.vue";
import VueFormMultiselect from "@/S4/vueform/Multiselect.vue";
import BorderSetup from "@/S4/BorderSetup.vue";
import question from "/public/images/question.svg";
import Tooltip from "@/S4/Tooltip.vue";
import helpers from "@/helpers.js";
import ButtonIconsVue from "../../../../S4/ButtonIcons.vue";
import {
    computed,
    nextTick,
    reactive,
    ref,
    watch,
    inject,
    onBeforeMount,
    defineExpose,
} from "vue";
import TE2 from "@/S4/TE2.vue";
import S4InputSlider2 from "@/S4/InputSlider2.vue";

const add_edit_form = inject("add_edit_form");
const showRowTextArea = ref(false);
const wr_types = [
    { value: 1, text: "Character Length" },
    { value: 2, text: "Whole Number" },
    { value: 3, text: "Decimal Number" },
    { value: 4, text: "Email Address" },
    { value: 5, text: "Date" },
    { value: 6, text: "Time" },
    { value: 7, text: "Date & Time" },
    // { value: 8, text: "Date Range" },
];

// const layout_types = [
//     { value: 1, text: "Text Only" },
//     { value: 2, text: "Image Only (Circular Display)" },
//     { value: 3, text: "Image Only (Box Display)" },
//     { value: 4, text: "Text with Image" },
// ];

// const write_in_choice = reactive({
//     enable: 1,
//     placeholder: "",
//     "max-width": "",
//     validate: {
//         type: 1, // [Specific Length, Whole Number, Decimal Number, Email Address, Date, Time, Date Time]
//         min: 1, // For SL, WN, DN
//         max: 10, // For SL, WN, DN
//         errorMsg: "This question requires an answer",
//     },
// });
const warning = reactive({
    cond: false,
    idx: [],
});

// Local error state for segmentation values
const segmentationError = ref("");

defineExpose({
    warning: warning,
    segmentationError: segmentationError,
});

// Watch for changes to segmentationError
watch(segmentationError, () => { });

// Watch for random statements checkbox changes
watch(
    () => add_edit_form.setup.validate.randomItems,
    async (checked) => {
        if (checked) {
            // Wait for DOM update to ensure the multiselect is rendered
            await nextTick()
            // Force reactivity by creating a new array
            const allValues = randomItems.value.map(item => item.value)
            add_edit_form.setup.randomItems = [...allValues]
        } else {
            // Clear selections when unchecked
            add_edit_form.setup.randomItems = []
        }
    }
)

// Also watch for changes in items to update randomItems when statements are added/removed
watch(
    () => add_edit_form.items,
    () => {
        // If random is enabled, refresh the selection to include new items
        if (add_edit_form.setup.validate.randomItems && add_edit_form.setup.randomItems) {
            const allValues = randomItems.value.map(item => item.value)
            add_edit_form.setup.randomItems = [...allValues]
        }
    },
    { deep: true }
)

onBeforeMount(() => {
    findDuplicateIndices(add_edit_form.values, warning);
});

const new_item = ref("");
const rowArea = ref(null);
const rowAreaB = ref(null);
const equivalentAreaA = ref(null);
const equivalentAreaB = ref(null);
const equivalentAreaC = ref(null);
const rowAreaElem = ref(null);
const rowAreaBElem = ref(null);
const valueAreaElem = ref(null);
watch(new_item, async (val) => {
    if (val !== "" && val !== "<p><br></p>") {
        let len = add_edit_form.items.length;

        if (len) {
            if (add_edit_form.items[len - 1]?.index) {
                indexedItems();
            } else {
                add_edit_form.items.push({
                    statementA: val,
                    statementB: "",
                });
            }
        } else {
            indexedItems();
        }

        function indexedItems() {
            add_edit_form.items.push({
                statementA: val,
                statementB: "",
                index:
                    add_edit_form.items.length === 0
                        ? 1
                        : Math.max(...add_edit_form.items.map((o) => o.index)) +
                        1,
            });
        }

        await nextTick();
        itemRefs.statementA[add_edit_form.items.length - 1].focus();
        new_item.value = "<p><br></p>";
    }
});

const new_value = ref("");
watch(new_value, async (val) => {
    if (val !== "<p><br></p>" && val !== "<br>" && val !== "") {
        if (
            add_edit_form.question_type_id == 15 &&
            add_edit_form.values.length >= 8
        )
            return;

        let len = add_edit_form.values.length;
        let col_index = len < 5 ? len : len % 5;
        let vColor =
            add_edit_form.question_type_id == 15
                ? "#000000"
                : colors.value[col_index];

        if (len) {
            if (add_edit_form.values[len - 1]?.index) {
                indexedValues();
            } else {
                add_edit_form.values.push({
                    name: val,
                    color: vColor,
                });
            }
        } else {
            indexedValues();
        }

        function indexedValues() {
            let vScore = len;
            add_edit_form.values.push({
                name: val,
                color: vColor,
                index:
                    add_edit_form.values.length === 0
                        ? 1
                        : Math.max(
                            ...add_edit_form.values.map((o) => o.index)
                        ) + 1,
                score:
                    add_edit_form.values.length === 0
                        ? 1
                        : Math.max(
                            ...add_edit_form.values.map((o) => o.score)
                        ) + 1,
            });
        }

        nextTick(() => {
            itemRefs.val[add_edit_form.values.length - 1].focus();
            new_value.value = "<p><br></p>";
        });
    }
});

const itemRefs = reactive({
    statementA: [],
    statementB: [],
    val: [],
});

const findDuplicateIndices = (values, warning) => {
    if (!values || !Array.isArray(values)) {
        console.warn("Invalid input: choices must be an array.");
        warning.cond = false;
        warning.idx = [];
        return;
    }

    const scores = values.map((value) => parseInt(value.score, 10));
    const duplicates = [];

    // Loop through the scores and track duplicate indices
    scores.forEach((score, index) => {
        // Find all indices with the same score
        const duplicateIndices = scores
            .map((s, idx) => (s === score && idx !== index ? idx : -1))
            .filter((idx) => idx !== -1);

        if (duplicateIndices.length > 0) {
            duplicates.push(...duplicateIndices, index); // Add both the current index and duplicates
        }
    });

    // Remove duplicates in the indices array
    const uniqueIndices = [...new Set(duplicates)];

    // Update the warning object
    warning.cond = uniqueIndices.length > 0;
    warning.idx = uniqueIndices;
};

watch(
    () => add_edit_form.values,
    (newChoices) => {
        // Pass `newChoices` and `warning` to the function
        findDuplicateIndices(newChoices, warning);
    },
    { deep: true }
);

const colors = ref(["#b71c1c", "#ef5350", "#ff9800", "#8bc34a", "#009688"]);
// const showItemTextArea = ref(false);
// const itemArea = ref(null);
const showValueTextArea = ref(false);
const valueArea = ref(null);

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

const randomItems = computed(() => {
    let items = [];
    for (const key in add_edit_form.items) {
        const val = parseInt(key) + 1;
        let data = {
            value: add_edit_form.items[key]?.index
                ? add_edit_form.items[key].index
                : val,
            label: `${add_edit_form.items[key]?.index
                ? add_edit_form.items[key].index
                : val
                }. ${add_edit_form.items[key].statementA} ---  ${add_edit_form.items[key].statementB
                }`,
        };

        items.push({ ...data });
    }
    return items;
});

// const randomValues = computed(() => {
//     let items = [];
//     for (const key in add_edit_form.values) {
//         const val = parseInt(key) + 1;
//         let data = {
//             value: val,
//             label: add_edit_form.values[key].name,
//         };

//         items.push({ ...data });
//     }
//     return items;
// });

const deleteChoice = (choice, index) => {
    add_edit_form[choice].splice(index, 1);
};

// const addWC = (index) => {
//     if (add_edit_form.items[index].enable_wr) {
//         add_edit_form.items[index]["wc"] = _.cloneDeep(write_in_choice);
//     } else {
//         delete add_edit_form.items[index].wc;
//     }
// };

// const showItemArea = () => {
//     showItemTextArea.value = !showItemTextArea.value;
// };

// const revertItem = () => {
//     showItemTextArea.value = false;
// };

// question type 15 = button rating
// const splitForItem = () => {
//     if (itemArea.value != null) {
//         let newRow = itemArea.value.split("\n");
//         _.forEach(newRow, (row, index) => {
//             if (row !== "") {
//                 add_edit_form.items.push({
//                     name: row,
//                     enable_wr: 0,
//                     optional: 0,
//                 });
//             }
//         });
//     }

//     itemArea.value = null;
//     showItemTextArea.value = false;
// };

const showValueArea = () => {
    showValueTextArea.value = !showValueTextArea.value;
};

const revertValue = () => {
    showValueTextArea.value = false;
    // Clear all text areas
    valueArea.value = '<p><br></p>';
    equivalentAreaC.value = null;
    // Force clear the TE2 component if it has a forceClear method
    if (valueAreaElem.value && valueAreaElem.value.forceClear) {
        valueAreaElem.value.forceClear();
    }
};

const splitForValue = () => {
    if (valueArea.value != null) {
        const newEq = equivalentAreaC.value?.split("\n") ?? [];
        const newRow =
            valueArea.value
                .match(/<p>(.*?)<\/p>/g)
                ?.map((p) => p.replace(/<\/?p>/g, "").trim()) || [];
        _.forEach(newRow, (row, index) => {
            if (!row || row === "<p><br></p>" || row === "<br>") {
                return;
            }

            const len = add_edit_form.values.length;
            const col_index = len < 5 ? len : len % 5;
            const vColor =
                add_edit_form.question_type_id == 15
                    ? "#000000"
                    : colors.value[col_index];

            const eqRaw = newEq[index]?.trim?.();
            const rawEquivalent = eqRaw && eqRaw !== "" ? eqRaw : row;
            const equivalent = htmlToTextWithImageAlt(rawEquivalent);

            if (add_edit_form.values.length <= 7) {
                if (len) {
                    if (add_edit_form.values[len - 1]?.index) {
                        indexedValues();
                    } else {
                        add_edit_form.values.push({
                            name: "<p>" + row + "</p>",
                            equivalent,
                            color: vColor,
                        });
                    }
                } else {
                    indexedValues();
                }
            }

            function indexedValues() {
                add_edit_form.values.push({
                    name: "<p>" + row + "</p>",
                    equivalent,
                    color: vColor,
                    index:
                        add_edit_form.values.length === 0
                            ? 1
                            : Math.max(
                                ...add_edit_form.values.map(
                                    (o) => o.index
                                )
                            ) + 1,
                    score:
                        add_edit_form.values.length === 0
                            ? 1
                            : Math.max(
                                ...add_edit_form.values.map(
                                    (o) => o.score
                                )
                            ) + 1,
                });
            }
        });
    }

    valueArea.value = "<p><br></p>";
    equivalentAreaC.value = null;
    showValueTextArea.value = false;
};

const splitForItem = () => {
    if (rowArea.value != null && rowAreaB.value != null) {
        const newEqA = equivalentAreaA.value?.split("\n") ?? [];
        const newEqB = equivalentAreaB.value?.split("\n") ?? [];
        const newRow =
            rowArea.value
                .match(/<p>(.*?)<\/p>/g)
                ?.map((p) => p.replace(/<\/?p>/g, "").trim()) || [];
        const newRowB =
            rowAreaB.value
                .match(/<p>(.*?)<\/p>/g)
                ?.map((p) => p.replace(/<\/?p>/g, "").trim()) || [];

        _.forEach(newRow, function (row, index) {
            if (!row || row === "<p><br></p>" || row === "<br>") {
                return;
            }

            const len = add_edit_form.items.length;

            const rowB = newRowB[index] || "";
            const eqRawA = newEqA[index]?.trim?.();
            const eqRawB = newEqB[index]?.trim?.();
            const rawEquivalentA = eqRawA && eqRawA !== "" ? eqRawA : row;
            const rawEquivalentB = eqRawB && eqRawB !== "" ? eqRawB : rowB;
            const equivalentA = htmlToTextWithImageAlt(rawEquivalentA);
            const equivalentB = htmlToTextWithImageAlt(rawEquivalentB);

            if (len) {
                if (add_edit_form.items[len - 1]?.index) {
                    indexedItems();
                } else {
                    add_edit_form.items.push({
                        statementA: "<p>" + row + "</p>",
                        statementB: "<p>" + rowB + "</p>",
                        equivalentA,
                        equivalentB,
                    });
                }
            } else {
                indexedItems();
            }

            function indexedItems() {
                add_edit_form.items.push({
                    statementA: row,
                    statementB: rowB,
                    equivalentA,
                    equivalentB,
                    index:
                        add_edit_form.items.length === 0
                            ? 1
                            : Math.max(
                                ...add_edit_form.items.map((o) => o.index)
                            ) + 1,
                });
            }
        });
    } else return;

    rowArea.value = "<p><br></p>";
    rowAreaB.value = "<p><br></p>";
    equivalentAreaA.value = null;
    equivalentAreaB.value = null;
    showRowTextArea.value = false;
};

const cancelSegmentationInputs = () => {
    showRowTextArea.value = false;
    // Clear all text areas
    rowArea.value = '<p><br></p>';
    rowAreaB.value = '<p><br></p>';
    equivalentAreaA.value = null;
    equivalentAreaB.value = null;
    // Force clear the TE2 components if they have a forceClear method
    if (rowAreaElem.value && rowAreaElem.value.forceClear) {
        rowAreaElem.value.forceClear();
    }
    if (rowAreaBElem.value && rowAreaBElem.value.forceClear) {
        rowAreaBElem.value.forceClear();
    }
};

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
});

const showDeleteAllStatements = ref(false);
const deleteAllItems = () => {
    add_edit_form.items = [];
    showDeleteAllStatements.value = false;
};
const showDeleteAllValues = ref(false);
const deleteAllValues = () => {
    add_edit_form.values = [];
    showDeleteAllValues.value = false;
};

if (!add_edit_form.setup.boxStyle['max-width_enabler']) {
    add_edit_form.setup.boxStyle['max-width_enabler'] = true;
}
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-6 lg:grid-cols-12 gap-1">
        <div class="col-span-6 flex justify-end">
            <button v-if="add_edit_form.items.length > 0" @click="showDeleteAllStatements = true"
                class="inline-flex w-[auto] items-center px-4 py-2 hover:bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-[1.1]">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 mr-2 transition-all duration-300 ease-in-out hover:text-red-600" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>

                <span class="transition-all duration-300 ease-in-out hover:text-red-600">
                    Delete All Statements
                </span>
            </button>
        </div>
        <div class="col-span-6 flex justify-end">
            <button v-if="add_edit_form.values.length > 0" @click="showDeleteAllValues = true"
                class="inline-flex w-[auto] items-center px-4 py-2 hover:bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-[1.1]">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 mr-2 transition-all duration-300 ease-in-out hover:text-red-600" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>

                <span class="transition-all duration-300 ease-in-out hover:text-red-600">
                    Delete All Choices
                </span>
            </button>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-6 lg:grid-cols-12 gap-1">
        <div class=" mt-3" v-if="add_edit_form.setup.labels">
            <div class="grid grid-cols-1 md:grid-cols-[50px_1fr_1fr_auto] lg:grid-cols-[50px_360px_360px_1fr] gap-1">
                <div></div>
                <div class="rounded-lg">
                    <jet-label class="font-bold text-xs bg-white">Statement A Label</jet-label>
                    <!-- <s4-input color="ssrGray" type="text" class="w-full text-xs text-slate-700"
                        v-model="add_edit_form.setup.labels.statementA" /> -->
                    <TE2 :modelValue="add_edit_form.setup.labels?.statementA || '<p><br></p>'" @update:modelValue="
                        (val) => {
                            add_edit_form.setup.labels.statementA = val;
                        }
                    "></TE2>
                </div>
                <div class="rounded-lg">
                    <jet-label class="font-bold text-xs bg-white">Statement B Label</jet-label>
                    <!-- <s4-input color="ssrGray" type="text" class="w-full text-xs text-slate-700"
                        v-model="add_edit_form.setup.labels.statementB" />-->
                    <TE2 :modelValue="add_edit_form.setup.labels?.statementB || '<p><br></p>'" @update:modelValue="
                        (val) => {
                            add_edit_form.setup.labels.statementB = val;
                        }
                    "></TE2>
                </div>
            </div>
        </div>
        <div class="col-span-6 mt-3" v-if="add_edit_form.setup.labels"></div>
        <div class="col-span-6 mt-3">
            <div class="grid grid-cols-[50px_1fr_1fr_1fr_200px] gap-1" v-if="add_edit_form.items.length > 0">
                <jet-label class="font-bold">Index</jet-label>
                <jet-label class="font-bold">Statement A</jet-label>
                <jet-label class="font-bold">Equivalent A</jet-label>
                <jet-label class="font-bold">Statement B</jet-label>
                <jet-label class="font-bold">Equivalent B</jet-label>
            </div>
            <template v-for="(item, index) in add_edit_form.items" :key="index">
                <div class="mt-1 mb-1 text-xs">
                    <div class="grid grid-cols-[50px_1fr_1fr_1fr_1fr_25px] gap-1 pb-1">
                        <div class="grid place-items-center">
                            <span class="text-sm font-semibold text-slate-600">{{
                                item?.index ? item.index : index + 1
                                }}</span>
                        </div>

                        <!-- <s4-input color="ssrGray" class="w-full mb-1 text-xs" placeholder="Enter Statement A"
                            v-model="add_edit_form.items[index].statementA" :ref="(el) => {
                                itemRefs.statementA[index] = el;
                            }
                                " /> -->

                        <TE2 :modelValue="add_edit_form.items[index]?.statementA || '<p><br></p>'" @update:modelValue="
                            (val) => {
                                add_edit_form.items[index].statementA = val;
                            }
                        " :placeholder="'Enter Statement A'" :ref="(el) => {
                            itemRefs.statementA[index] = el;
                        }
                            "></TE2>

                        <s4-input color="ssrGray" class="w-full" placeholder="Enter Equivalent"
                            v-model="add_edit_form.items[index].equivalentA">
                        </s4-input>

                        <!-- 
                        <s4-input color="ssrGray" class="w-full mb-1 text-xs" placeholder="Enter Statement B"
                            v-model="add_edit_form.items[index].statementB" :ref="(el) => {
                                itemRefs.statementB[index] = el;
                            }
                                " /> -->

                        <TE2 :modelValue="add_edit_form.items[index]?.statementB || '<p><br></p>'" @update:modelValue="
                            (val) => {
                                add_edit_form.items[index].statementB = val;
                            }
                        " :placeholder="'Enter Statement B'" :ref="(el) => {
                            itemRefs.statementB[index] = el;
                        }
                            "></TE2>

                        <s4-input color="ssrGray" class="w-full" placeholder="Enter Equivalent"
                            v-model="add_edit_form.items[index].equivalentB">
                        </s4-input>

                        <ButtonIconsVue class="w-6 shadow-none border-none" @click="deleteChoice('items', index)"
                            icon="delete" />
                        <!-- <s4-button color="ssrRedIcon" class="mb-1 ml-1" @click="deleteChoice('items', index)">
                            <font-awesome-icon class="fa-w-20 fa-h-20" :icon="faIcon.trash" />
                        </s4-button> -->
                    </div>
                    <label class="ml-3 items-center cursor-pointer pl-10">
                        <s4-checkbox class="cursor-pointer border-ssr-blue4" v-model:checked="add_edit_form.items[index].optional
                            " />
                        <span class="ml-2 text-xs text-gray-500 select-none">Optional Item</span>
                    </label>

                    <div class="mb-5 ml-10 mr-12 grid grid-cols-4 gap-1" v-if="'wc' in item">
                        <div class="col-span-2">
                            <span class="text-xs text-gray-500">Write-in Type</span>
                            <s4-input-select color="ssrGray" class="w-full" :lists="wr_types"
                                v-model.number="item.wc.validate.type">
                            </s4-input-select>
                        </div>
                        <div class="col-span-1">
                            <span class="text-xs text-gray-500">Minimum Character</span>
                            <s4-input color="ssrGray" type="number" class="w-full"
                                v-model.number="item.wc.validate.min"></s4-input>
                        </div>
                        <div class="col-span-1">
                            <span class="text-xs text-gray-500">Maximum Character</span>
                            <s4-input color="ssrGray" type="number" class="w-full"
                                v-model.number="item.wc.validate.max"></s4-input>
                        </div>
                        <div class="col-span-2">
                            <span class="text-xs text-gray-500">Error Message - Write in</span>
                            <s4-input color="ssrGray" class="w-full" v-model="item.wc.validate.errorMsg"></s4-input>
                        </div>
                        <div class="col-span-1">
                            <span class="text-xs text-gray-500">Placeholder:</span>
                            <s4-input color="ssrGray" class="w-full" v-model="item.wc.placeholder" />
                        </div>
                        <div class="col-span-1">
                            <span class="text-xs text-gray-500">Max-width:</span>
                            <s4-input color="ssrGray" placeholder="e.g. 100%" class="w-full"
                                v-model="item.wc['max-width']" />
                        </div>
                    </div>
                </div>
            </template>

            <!-- this section is for question type 16 = segmentation -->
            <div class="col-span-6 pt-2 w-full">
                <!-- <s4-input color="ssrGray" class="w-full mb-2" placeholder="Input new statement A"
                    v-model="new_item"></s4-input> -->

                <TE2 v-if="!showRowTextArea" :modelValue="new_item || '<p><br></p>'" @update:modelValue="
                    (val) => {
                        new_item = val;
                    }
                " :placeholder="'Input new statement'"></TE2>

                <!-- <s4-button v-show="showRowTextArea == false" :theme="'ssrAddColRow'" class="py-1 px-2 mb-10"
                    color="ssrB" @click="showRowTextArea = !showRowTextArea">
                    Multiple Items
                </s4-button> -->
                <div class="pt-2">
                    <button v-if="showRowTextArea == false" :theme="'ssrAddColRow'" color="ssrB"
                        @click="showRowTextArea = !showRowTextArea"
                        class="inline-flex w-[190px] items-center px-2 py-1 mb-2 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
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

                        <span class="transition-all duration-300 ease-in-out hover:text-white">
                            Add Multiple Statements
                        </span>
                    </button>
                </div>
                <div class="grid grid-cols-6 gap-2 pt-1">
                    <div class="col-span-3">
                        <!-- <s4-text-area color="ssrGray" v-show="showRowTextArea" v-model="rowArea" class="w-full h-40"
                            placeholder="Enter Statement A separated by new line..."></s4-text-area> -->

                        <div v-show="showRowTextArea" class="flex flex-col gap-1 mb-2">
                            <jet-label class="text-xs font-semibold text-gray-600">Enter Statement A</jet-label>
                            <TE2 ref="rowAreaElem" :modelValue="rowArea || '<p><br></p>'" @update:modelValue="
                                (val) => {
                                    rowArea = val;
                                }
                            " height="157px" :placeholder="'Enter Statement A separated by new line...'"></TE2>
                        </div>

                        <div v-show="showRowTextArea" class="flex flex-col gap-1">
                            <jet-label class="text-xs font-semibold text-gray-600">Enter Statement B</jet-label>
                            <TE2 ref="rowAreaBElem" :modelValue="rowAreaB || '<p><br></p>'" @update:modelValue="
                                (val) => {
                                    rowAreaB = val;
                                }
                            " height="157px" :placeholder="'Enter Statement B separated by new line...'"></TE2>
                        </div>
                    </div>
                    <div class="col-span-3">
                        <!-- <s4-text-area color="ssrGray" v-show="showRowTextArea" v-model="rowAreaB" class="w-full h-40"
                            placeholder="Enter Statement B separated by new line..."></s4-text-area> -->

                        <div v-show="showRowTextArea" class="flex flex-col gap-1 mb-2">
                            <jet-label class="text-xs font-semibold text-gray-600">Enter Equivalent A</jet-label>
                            <s4-text-area color="ssrGray" v-model="equivalentAreaA" :filter="true" class="w-full h-40"
                                placeholder="Enter equivalent A separated by new line..."></s4-text-area>
                        </div>
                        <div v-show="showRowTextArea" class="flex flex-col gap-1">
                            <jet-label class="text-xs font-semibold text-gray-600">Enter Equivalent B</jet-label>
                            <s4-text-area color="ssrGray" v-model="equivalentAreaB" :filter="true" class="w-full h-40"
                                placeholder="Enter equivalent B separated by new line..."></s4-text-area>
                        </div>
                    </div>
                </div>
                <!-- <div class="flex mb-5" v-show="showRowTextArea">
                    <s4-button :theme="'ssrAddColRow'" class="py-1 px-2 mt-2" @click="splitForItem()">Add</s4-button>
                    <s4-button :theme="'ssrAddColRow'" class="py-1 px-2 ml-1 mt-2"
                        @click="showRowTextArea = false">Cancel</s4-button>
                </div> -->
                <div class="flex mb-10" v-show="showRowTextArea">
                    <s4-button :theme="'ssrAddColRow'"
                        class="py-1 px-2 mt-2 !bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg"
                        @click="splitForItem()">Add</s4-button>
                    <s4-button :theme="'ssrAddColRow'"
                        class="py-1 px-2 ml-1 mt-2 bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-125 hover:bg-transparent"
                        @click="cancelSegmentationInputs()">Cancel</s4-button>
                </div>
            </div>
            <!-- <s4-button
                v-if="showItemTextArea == false"
                :theme="'ssrAddColRow'"
                class="py-1 px-3 mb-10 w-32 hidden"
                color="ssrB"
                @click="showItemArea()">
                Multiple Items
            </s4-button> -->

            <!-- <div class="col-span-12 bg-red-600">
                <div>
                    <s4-text-area
                        color="ssrGray"
                        v-show="showItemTextArea"
                        v-model="itemArea"
                        class="w-full h-40"
                        placeholder="Enter items separated by new line..."
                    ></s4-text-area>
                </div>
                <div class="flex mb-10" v-show="showItemTextArea">
                    <s4-button
                        :theme="'ssrAddColRow'"
                        class="py-1 px-2 mt-2"
                        @click="splitForItem()">Add</s4-button>
                    <s4-button
                        :theme="'ssrAddColRow'"
                        class="py-1 px-2 ml-1 mt-2"
                        @click="revertItem()">Cancel</s4-button>
                </div>
            </div> -->
        </div>
        <div class="col-span-6 mt-3">
            <div class="grid grid-cols-[80px_1fr_1fr_100px_20px] gap-1 pl-2 pb-1"
                v-if="add_edit_form.values.length > 0">
                <jet-label class="font-bold" :class="{ 'text-red-500': warning.cond }">Values</jet-label>
                <jet-label class="font-bold">Choices</jet-label>
                <jet-label class="font-bold">Equivalent</jet-label>
                <jet-label class="font-bold">Colours</jet-label>
            </div>
            <template v-for="(value, index) in add_edit_form.values" :key="index">
                <div class="grid grid-cols-[80px_1fr_1fr_100px_20px] gap-1 pb-1">
                    <div class="relative flex items-center gap-1">
                        <svg v-if="warning.cond && warning.idx.includes(index)" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="red" class="w-[40px] h-[auto]">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"
                                fill="#fff7f6" />
                        </svg>
                        <s4-input color="ssrGray" class="w-full text-center" :class="{
                            'text-red-500':
                                warning.cond && warning.idx.includes(index),
                        }" placeholder="Value" v-model="add_edit_form.values[index].score" :ref="(el) => {
                            itemRefs.val[index] = el;
                        }
                            ">
                        </s4-input>
                    </div>

                    <!-- <span v-if="index == 0"
                                        class="absolute -mt-4 text-[11px] text-slate-500">Name</span> -->
                    <!-- <s4-input color="ssrGray" class="w-full text-xs" placeholder="Enter value" v-model="add_edit_form.values[index].name
                                        " :ref="(el) => {
                                            itemRefs.val[index] = el;
                                        }
                                            ">
                                    
                                    </s4-input> -->
                    <div>
                        <TE2 :modelValue="add_edit_form.values[index]?.name || '<p><br></p>'" @update:modelValue="
                            (val) => {
                                add_edit_form.values[index].name = val;
                            }
                        " :placeholder="'Enter choice'" :ref="(el) => {
                            itemRefs.val[index] = el;
                        }
                            "></TE2>
                    </div>

                    <div>
                        <s4-input color="ssrGray" class="w-full" placeholder="Enter Equivalent"
                            v-model="add_edit_form.values[index].equivalent"></s4-input>
                    </div>

                    <div class="relative w-[100px] h-10">
                        <!-- Input box -->
                        <s4-input color="ssrGray" class="w-full h-full text-xs" :style="{ color: value.color }"
                            placeholder="Enter hex code" v-model="add_edit_form.values[index].color"></s4-input>

                        <label
                            class="absolute top-1 ml-[73%] w-6 h-6 rounded-full border border-gray-300 cursor-pointer z-10 mt-[5px]"
                            :style="{
                                backgroundColor: value.color,
                            }">
                            <input type="color" v-model="add_edit_form.values[index].color" class="sr-only" />
                        </label>
                    </div>
                    <div>
                        <div class="" v-if="index == 0">
                            <ButtonIconsVue class="w-6 shadow-none border-none" icon="delete"
                                @click="deleteChoice('values', index)" />
                        </div>
                        <div v-else>
                            <ButtonIconsVue class="w-6 shadow-none border-none" icon="delete"
                                @click="deleteChoice('values', index)" />
                        </div>
                    </div>
                </div>
            </template>

            <div class="col-span-6 mt-2" v-if="add_edit_form.values.length <= 7">
                <!-- <s4-input color="ssrGray" class="w-full mb-2 mr-1" placeholder="Input new value"
                    v-model="new_value"></s4-input> -->
                <TE2 v-if="showValueTextArea == false" :modelValue="new_value || '<p><br></p>'" @update:modelValue="
                    (val) => {
                        new_value = val;
                    }
                " :placeholder="'Input new choice'"></TE2>
                <jet-input-error :message="segmentationError" />
                <div v-if="segmentationError" class="text-red-600 text-sm mt-1">{{ segmentationError }}</div>
                <!-- <s4-button
                    v-if="showValueTextArea == false"
                    :theme="'ssrAddColRow'"
                    class="py-1 px-2 mb-10"
                    color="ssrB"
                    @click="showValueArea()">
                    Multiple Values
                </s4-button> -->
                <div class="pt-2">
                    <button v-if="showValueTextArea == false" :theme="'ssrAddColRow'" color="ssrB"
                        @click="showValueArea()"
                        class="inline-flex w-[175px] items-center px-2 py-1 mb-2 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
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

                        <span class="transition-all duration-300 ease-in-out hover:text-white">
                            Add Multiple Choices
                        </span>
                    </button>
                </div>
            </div>

            <div class="col-span-12 pt-1">
                <div class="grid grid-cols-2 gap-1 col-span-12 w-full">
                    <!-- <s4-text-area color="ssrGray" v-show="showValueTextArea" v-model="valueArea" class="w-full h-40"
                        placeholder="Enter values separated by new line..."></s4-text-area> -->
                    <div v-show="showValueTextArea" class="flex flex-col gap-1">
                        <jet-label class="text-xs font-semibold text-gray-600">Enter Choices</jet-label>
                        <TE2 ref="valueAreaElem" :modelValue="valueArea || '<p><br></p>'" @update:modelValue="
                            (val) => {
                                valueArea = val;
                            }
                        " height="158px" :placeholder="'Enter choices separated by new line...'">
                        </TE2>
                    </div>
                    <div v-show="showValueTextArea" class="flex flex-col gap-1">
                        <jet-label class="text-xs font-semibold text-gray-600">Enter Equivalent</jet-label>
                        <s4-text-area color="ssrGray" v-model="equivalentAreaC" :filter="true" class="w-full h-40"
                            placeholder="Enter equivalent separated by new line..."></s4-text-area>
                    </div>

                </div>
                <div class="flex mb-10" v-show="showValueTextArea">
                    <s4-button :theme="'ssrAddColRow'"
                        class="py-1 px-2 mt-2 !bg-[var(--mcolor)] !hover:bg-[var(--mcolor2)] text-white text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg"
                        @click="splitForValue()">Add</s4-button>
                    <s4-button :theme="'ssrAddColRow'"
                        class="py-1 px-2 ml-1 mt-2 bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-125 hover:bg-transparent"
                        @click="revertValue()">Cancel</s4-button>
                </div>
            </div>
        </div>
    </div>

    <BorderSetup></BorderSetup>

    <div class="grid grid-cols-1 md:grid-cols-6 lg:grid-cols-12 gap-1">
        <div class="col-span-6 grid grid-cols-6 gap-1">
            <!-- <label class="items-center cursor-pointer">
                    <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.required" />
                    <span class="ml-2 text-sm text-gray-600 select-none">Required</span>
                </label> -->
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
                        :modelValue="add_edit_form.setup.validate.errorMsg" @update:modelValue="
                            (val) => {
                                add_edit_form.setup.validate.errorMsg = val;
                            }
                        "></TE2>
                </div> -->
                <div class="col-span-6">
                <label class="flex items-center cursor-pointer w-[200px]">
                    <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.randomItems
                        " />
                    <span class="ml-2 text-sm text-gray-600 select-none">Random Statements</span>
                    <Tooltip :imgSrc="question" preset="RandomItems">
                    </Tooltip>
                </label>
            </div>
            <div class="col-span-6 pl-6" v-if="add_edit_form.setup.validate.randomItems">
                <vue-form-multiselect :key="`random-${add_edit_form.setup.validate.randomItems}-${randomItems.length}`"
                    placeholder="Click to select statements" :options="randomItems"
                    v-model="add_edit_form.setup.randomItems">
                </vue-form-multiselect>
            </div>
                <label class="items-center cursor-pointer ml-6" v-if="add_edit_form.question_type_id == 15">
                    <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.hideQuestionFrame" />
                    <span class="ml-2 text-sm text-gray-600 select-none">Hide Question Frame</span>
                </label>
                <!-- <div class="col-span-6">
                    <jet-label class="mt-2">Error Message:</jet-label>
                    
                    <TE2 :modelValue="add_edit_form.setup.validate.errorMsg"
                        @update:modelValue="(val) => {
                            add_edit_form.setup.validate.errorMsg = val;
                        }"
                        :placeholder="'Error Message'"></TE2>
                </div> -->
            </div>

            
        </div>

        <div class="col-span-6 mt-1">
            <div class="grid grid-cols-6 gap-1">
                <!-- <div class="col-span-6">
                    <jet-label>Random Items:</jet-label>
                    <vue-form-multiselect style="min-height: 4rem" placeholder="Click to select items"
                        :options="randomItems" v-model="add_edit_form.setup.randomItems" />
                </div> -->
                <div class="col-span-6"
                    :set="helpers().initProperty(add_edit_form.setup.boxStyle, 'max-width_enabler', 0)">
                    <label class="cursor-pointer flex items-center gap-0.5 w-[170px]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.boxStyle['max-width_enabler']
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Edit Layout Width</span>
                        <Tooltip :imgSrc="question" preset="EditLayoutWidth">
                        </Tooltip>
                    </label>
                </div>
                <div class="col-span-6 w-full grid grid-cols-3 gap-1 pl-6">
                    <div class="" v-if="add_edit_form.setup.boxStyle['max-width_enabler'] == 1">
                        <jet-label>Desktop</jet-label>
                        <!-- <s4-input
                                color="ssrGray"
                                class="w-full mb-1"
                                placeholder="e.g. 100%"
                                v-model="add_edit_form.setup.boxStyle['max-width']"
                            >
                            </s4-input> -->
                        <S4InputSlider2 :set="helpers().initProperty(add_edit_form.setup.boxStyle, 'max-width', '100%')"
                            color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                            :units="['%', 'vmin', 'vw', 'auto']"
                            :modelValue="add_edit_form.setup.boxStyle['max-width'] = '100%'" :allowAuto="true"
                            @update:modelValue="
                                (val) => {
                                    add_edit_form.setup.boxStyle['max-width'] = val;
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
                                v-model="add_edit_form.setup.boxStyle">
                            </S4InputSlider> -->

                        <S4InputSlider2 color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                            :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.boxStyle['max-width-tab'] ??
                                '100%'
                                " :allowAuto="true" @update:modelValue="
                                (val) => {
                                    add_edit_form.setup.boxStyle['max-width-tab'] =
                                        val;
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
                            :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.boxStyle['max-width-mobile'] ??
                                '100%'
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
            <!-- <div
                class="grid grid-cols-6 gap-1 mt-4"
                v-if="add_edit_form.question_type_id == 15"
            >
                <div class="col-span-6">
                    <jet-label>Random Values:</jet-label>
                    <vue-form-multiselect
                        style="min-height: 4rem"
                        placeholder="Click to select items"
                        :options="randomValues"
                        v-model="add_edit_form.setup.randomValues"
                        class=""
                    />
                </div>
                <template
                    v-if="Object.hasOwn(add_edit_form.setup, 'fontStyle')"
                >
                    <div class="col-span-3 mt-4">
                        <jet-label>Font size Items:</jet-label>
                        <s4-input
                            color="ssrGray"
                            class="w-full mb-1"
                            placeholder="e.g. 100%"
                            v-model="add_edit_form.setup.fontStyle.items">
                        </s4-input>
                    </div>
                    <div class="col-span-3 mt-4">
                        <jet-label>Font size Values:</jet-label>
                        <s4-input
                            color="ssrGray"
                            class="w-full mb-1"
                            placeholder="e.g. 100%"
                            v-model="add_edit_form.setup.fontStyle.values">
                        </s4-input>
                    </div>
                </template>
            </div> -->
        </div>
        <!-- delete all modal for statements -->
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
                    <button @click="deleteAllItems"
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
        <!-- delete all modal for choices -->
        <teleport to="body">
            <WarningModal :show="showDeleteAllValues" @close="showDeleteAllValues = false">
                <template #header>
                    Confirm Deletion
                </template>
                <template #content>
                    Are you sure you want to delete <span class="font-semibold">all choices</span>?
                    This action is irreversible.
                </template>
                <template #footer>
                    <button @click="deleteAllValues"
                        class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
                        type="button">
                        Delete
                    </button>
                    <button @click="showDeleteAllValues = false"
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
