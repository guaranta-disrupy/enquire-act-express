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
import ButtonIconsVue from "../../../../S4/ButtonIcons.vue";
import { usePage } from "@inertiajs/vue3";
import S4GalleryModal from "@/S4/modals/Gallery.vue";
import helpers from "@/helpers.js";
import ButtonRatingAnalysis from "./buttonRatingComp/buttonRatingAnalysis.vue";
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
import { SlickList, SlickItem } from "vue-slicksort";
import S4InputSlider2 from "@/S4/InputSlider2.vue";
import SingleSelect from "@/S4/SingleSelect.vue";
const add_edit_form = inject("add_edit_form");

const wr_types = [
    { value: 1, text: "Character Length" },
    { value: 2, text: "Whole Number" },
    { value: 3, text: "Decimal Number" },
    { value: 4, text: "Email Address" },
    { value: 5, text: "Date" },
    { value: 6, text: "Time" },
    { value: 7, text: "Date Time" },
    { value: 8, text: "Date Range" },
];

// const layout_types = [
//     { value: 1, text: "Text Only" },
//     { value: 2, text: "Image Only (Circular Display)" },
//     { value: 3, text: "Image Only (Box Display)" },
//     { value: 4, text: "Text with Image" },
// ];


const button_warning = reactive({
    cond: false,
    equivalent_idx: [],
    value_idx: [],
});

defineExpose({
    button_warning: button_warning,
});

const write_in_choice = reactive({
    enable: 1,
    placeholder: "",
    "max-width": "",
    validate: {
        type: 1, //[Specific Length, Whole Number, Decimal Number, Email Address, Date, Time, Date Time]
        min: 1, //For SL, WN, DN
        max: 10, //For SL, WN, DN
        errorMsg: "This question requires an answer",
    },
});

// Computed property to force reactivity
const hiddenInstructionText = computed(() => {
    if (add_edit_form.setup.hideQuestionFrame && (!add_edit_form.question || add_edit_form.question.trim() === "")) {
        return "Button rating instruction (Hidden)";
    }
    return add_edit_form.question;
});

// Watch the computed property to update the question
watch(
    hiddenInstructionText,
    (newVal) => {
        if (add_edit_form.setup.hideQuestionFrame && newVal === "Button rating instruction (Hidden)") {
            add_edit_form.question = newVal;
        }
    },
    { immediate: true }
);

const handleHideInstructionChange = () => {
    // When checkbox is checked, always set the hidden instruction text
    if (add_edit_form.setup.hideQuestionFrame) {
        // Force set the hidden instruction text regardless of current question content
        add_edit_form.question = "Button rating instruction (Hidden)";

        // Use nextTick to ensure the DOM updates properly
        nextTick(() => {
            // Double-check that the text is set
            if (add_edit_form.setup.hideQuestionFrame && (!add_edit_form.question || add_edit_form.question.trim() === "")) {
                add_edit_form.question = "Button rating instruction (Hidden)";
            }
        });
    }
};

const new_item = ref("");
watch(new_item, async (val) => {
    if (val !== "<p><br></p>" && val !== "<br>" && val !== "") {
        add_edit_form.items.push({
            name: val,
            equivalent: "",
            enable_wr: 0,
            optional: 0,
            index:
                add_edit_form.items.length === 0
                    ? 1
                    : Math.max(...add_edit_form.items.map((o) => o.index)) + 1,
        });
        await nextTick();
        itemRefs.item[add_edit_form.items.length - 1].focus();
        new_item.value = "<p><br></p>";
    }
});

const new_value = ref("");
watch(new_value, async (val) => {
    if (val !== "" && val !== "<p><br></p>") {
        if (
            add_edit_form.question_type_id == 15 &&
            add_edit_form.values.length >= 8
        )
            return;

        let len = add_edit_form.values.length;
        let col_index = len < 5 ? len : len % 5;
        let vColor = colors.value[col_index];

        if (len) {
            if (add_edit_form.values[len - 1]?.index) {
                indexedValues();
            } else {
                add_edit_form.values.push({
                    name: val,
                    color: vColor,
                    index:
                        add_edit_form.values.length === 0
                            ? 1
                            : Math.max(
                                ...add_edit_form.values.map((o) => o.index)
                            ) + 1,
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
    item: [],
    label: [],
    val: [],
});

onBeforeMount(() => {
    findEquivalentDuplicates(
        add_edit_form.items,
        button_warning
    );
    findValuesDuplicates(add_edit_form.values, button_warning);
    add_edit_form.choices = [];
});

const colors = ref(["#b71c1c", "#ef5350", "#ff9800", "#8bc34a", "#009688"]);
const showItemTextArea = ref(false);
const showValueTextArea = ref(false);
const itemArea = ref(null);
const equivalentArea = ref(null);
const labelArea = ref(null);
const equivalentChoicesArea = ref(null);
const valueArea = ref(null);
const valueAreaElem = ref(null);

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
    const useRandom = add_edit_form.setup.validate.randomItems;

    let items = [];
    let form_items = reAssignItems();

    for (const key in form_items) {
        const val = parseInt(key) + 1;

        const index =
            useRandom && form_items[key]?.index
                ? form_items[key].index
                : val;

        const data = {
            value: index,
            label: `${index}. ${form_items[key].name}`,
        };

        items.push({ ...data });
    }
    return items;
});

const randomValues = computed(() => {
    const useRandom = add_edit_form.setup.validate.randomValues;

    let items = [];
    for (const key in add_edit_form.values) {
        const val = parseInt(key) + 1;

        const index =
            useRandom && add_edit_form.values[key]?.index
                ? add_edit_form.values[key].index
                : val;

        const data = {
            value: index,
            label: `${index}. ${add_edit_form.values[key].name}`,
        };

        items.push({ ...data });
    }
    return items;
});

// Clear Random Items when disabled
watch(
    () => add_edit_form.setup.validate.randomItems,
    (enabled) => {
        if (!enabled) {
            add_edit_form.setup.randomItems = [];
        }
    }
);

// Clear Random Choices when disabled
watch(
    () => add_edit_form.setup.validate.randomValues,
    (enabled) => {
        if (!enabled) {
            add_edit_form.setup.randomValues = [];
        }
    }
);

// const randomItems = computed(() => {
//     let items = [];
//     let form_items = reAssignItems();
//     for (const key in form_items) {
//         const val = parseInt(key) + 1;
//         let data = {
//             value: form_items[key]?.index ? form_items[key].index : val,
//             label: `${form_items[key]?.index ? form_items[key].index : val}. ${form_items[key].name
//                 }`,
//         };

//         items.push({ ...data });
//     }
//     return items;
// });

// const randomValues = computed(() => {
//     let items = [];
//     for (const key in add_edit_form.values) {
//         const val = parseInt(key) + 1;
//         let data = {
//             value: val,
//             label: `${add_edit_form.values[key]?.index
//                 ? add_edit_form.values[key].index
//                 : val
//                 }. ${add_edit_form.values[key].name}`,
//         };

//         items.push({ ...data });
//     }
//     return items;
// });

const deleteChoice = (choice, index) => {
    add_edit_form[choice].splice(index, 1);
    setTimeout(() => {
        add_edit_form.items = reAssignItems();
    }, 500);
};

const reAssignItems = () => {
    return reactive(
        add_edit_form.items.filter((element) => {
            return element !== null && element !== undefined;
        })
    );
};

const addWC = (index) => {
    if (add_edit_form.items[index].enable_wr) {
        add_edit_form.items[index]["wc"] = _.cloneDeep(write_in_choice);
    } else {
        delete add_edit_form.items[index].wc;
    }
};

const showItemArea = () => {
    showItemTextArea.value = !showItemTextArea.value;
};

const revertItem = () => {
    showItemTextArea.value = false;
    // Clear all text areas
    itemArea.value = '<p><br></p>';
    equivalentArea.value = '';
    labelArea.value = '<p><br></p>';
};

const splitForItem = (item = 0) => {
    if (itemArea.value != null) {
        const newEq = equivalentArea.value?.split("\n") ?? [];
        const newLabel =
            labelArea.value
                ?.match(/<p>(.*?)<\/p>/g)
                ?.map((p) => p.replace(/<\/?p>/g, "").trim()) || [];
        const newRow =
            itemArea.value
                .match(/<p>(.*?)<\/p>/g)
                ?.map((p) => p.replace(/<\/?p>/g, "").trim()) || [];
        _.forEach(newRow, (row, index) => {
            if (!row) {
                return;
            }

            const eqRaw = newEq[index]?.trim?.();
            const rawEquivalent = eqRaw && eqRaw !== "" ? eqRaw : row;
            const equivalent = htmlToTextWithImageAlt(rawEquivalent);

            add_edit_form.items.push({
                name: "<p>" + row + "</p>",
                label: newLabel[index] ? "<p>" + newLabel[index] + "</p>" : "",
                equivalent,
                enable_wr: 0,
                optional: 0,
                index:
                    add_edit_form.items.length === 0
                        ? 1
                        : Math.max(
                              ...add_edit_form.items.map((o) => o.index)
                          ) + 1,
            });
        });
    }

    itemArea.value = "<p><br></p>";
    equivalentArea.value = "";
    labelArea.value = "<p><br></p>";
    showItemTextArea.value = false;
};

const showValueArea = () => {
    showValueTextArea.value = !showValueTextArea.value;
};

const revertValue = () => {
    showValueTextArea.value = false;
    // Clear all text areas
    valueArea.value = '<p><br></p>';
    equivalentChoicesArea.value = '';
};

const cancelValueInputs = () => {
    showValueTextArea.value = false;
    // Clear all text areas
    valueArea.value = '<p><br></p>';
    equivalentChoicesArea.value = '';
    // Force clear the TE2 component if it has a forceClear method
    if (valueAreaElem.value && valueAreaElem.value.forceClear) {
        valueAreaElem.value.forceClear();
    }
};

const splitForValue = () => {
    if (valueArea.value != null) {
        const newEq = equivalentChoicesArea.value?.split("\n") ?? [];
        const newRow =
            valueArea.value
                .match(/<p>(.*?)<\/p>/g)
                ?.map((p) => p.replace(/<\/?p>/g, "").trim()) || [];
        _.forEach(newRow, (row, index) => {
            if (!row || row === "<p><br></p>" || row === "<br>") {
                return;
            }

            if (
                add_edit_form.question_type_id == 15 &&
                add_edit_form.values.length >= 8
            ) {
                return;
            }

            const len = add_edit_form.values.length;
            const col_index = len < 5 ? len : len % 5;
            const vColor = colors.value[col_index];

            const eqRaw = newEq[index]?.trim?.();
            const rawEquivalent = eqRaw && eqRaw !== "" ? eqRaw : row;
            const equivalent = htmlToTextWithImageAlt(rawEquivalent);

            if (len) {
                if (add_edit_form.values[len - 1]?.index) {
                    // Existing values already indexed: add a fully indexed entry
                    add_edit_form.values.push({
                        name: row,
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
                } else {
                    // No indices yet: push a simple value (will be indexed later)
                    add_edit_form.values.push({
                        name: "<p>" + row + "</p>",
                        equivalent,
                        color: vColor,
                    });
                }
            } else {
                // First value: start indexing at 1
                add_edit_form.values.push({
                    name: row,
                    equivalent,
                    color: vColor,
                    index: 1,
                    score: 1,
                });
            }
        });
    }

    valueArea.value = "<p><br></p>";
    equivalentChoicesArea.value = "";
    showValueTextArea.value = false;
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

const fontSizeItemsValue = ref(""); // Numeric value
const fontSizeItemsUnit = ref("vmin"); // Unit

const fontSizeValuesValue = ref(""); // Numeric value
const fontSizeValuesUnit = ref("vmin"); // Unit

const fontSizeItemsComputed = computed({
    get() {
        return fontSizeItemsUnit.value === "auto"
            ? "auto"
            : `${fontSizeItemsValue.value}${fontSizeItemsUnit.value}`;
    },
    set(newValue) {
        if (newValue === "auto") {
            fontSizeItemsValue.value = null;
            fontSizeItemsUnit.value = "auto";
        } else {
            const match = newValue.match(/^(\d+)([a-z%]+)$/);
            if (match) {
                fontSizeItemsValue.value = parseFloat(match[1]);
                fontSizeItemsUnit.value = match[2];
            }
        }
    },
});

const fontSizeValuesComputed = computed({
    get() {
        return fontSizeValuesUnit.value === "auto"
            ? "auto"
            : `${fontSizeValuesValue.value}${fontSizeValuesUnit.value}`;
    },
    set(newValue) {
        if (newValue === "auto") {
            fontSizeValuesValue.value = null;
            fontSizeValuesUnit.value = "auto";
        } else {
            const match = newValue.match(/^(\d+)([a-z%]+)$/);
            if (match) {
                fontSizeValuesValue.value = parseFloat(match[1]);
                fontSizeValuesUnit.value = match[2];
            }
        }
    },
});

watch(fontSizeItemsComputed, (newValue) => {
    add_edit_form.setup.fontStyle.items = newValue;
});

watch(fontSizeValuesComputed, (newValue) => {
    add_edit_form.setup.fontStyle.values = newValue;
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

    if (!("fontStyle" in add_edit_form.setup)) {
        add_edit_form.setup["fontStyle"] = { items: "2vmin", values: "2vmin" };
    }

    // Initialize fontStyle items
    const fontItems = add_edit_form.setup.fontStyle.items || "2vmin"; // Default to 2vmin
    const matchItems = fontItems.match(/^(\d+)([a-z%]+)$/);
    if (matchItems) {
        fontSizeItemsValue.value = parseFloat(matchItems[1]);
        fontSizeItemsUnit.value = matchItems[2];
    } else {
        fontSizeItemsValue.value = 2; // Default value
        fontSizeItemsUnit.value = "vmin"; // Default unit
    }

    // Initialize fontStyle values
    const fontValues = add_edit_form.setup.fontStyle.values || "2vmin";
    const matchValues = fontValues.match(/^(\d+)([a-z%]+)$/);
    if (matchValues) {
        fontSizeValuesValue.value = parseFloat(matchValues[1]);
        fontSizeValuesUnit.value = matchValues[2];
    } else {
        fontSizeValuesValue.value = 2; // Default value
        fontSizeValuesUnit.value = "vmin"; // Default unit
    }
});

const findEquivalentDuplicates = (items, button_warning) => {
    if (add_edit_form.question_type_id == 15) {
        if (!items || !Array.isArray(items)) {
            console.warn("Invalid input: choices must be an array.");
            button_warning.cond = false;
            button_warning.equivalent_idx = [];
            return;
        }
    }

    const equivalents = items.map((item) => item.equivalent);
    const eqDuplicates = [];

    // Loop through the scores and track duplicate indices
    equivalents.forEach((item, index) => {
        if (item !== "") {
            // Filter out empty strings
            // Find all indices with the same score
            const duplicateIdx = equivalents
                .map((s, equivalent_idx) =>
                    s === item && equivalent_idx !== index ? equivalent_idx : -1
                )
                .filter((equivalent_idx) => equivalent_idx !== -1);

            if (duplicateIdx.length > 0) {
                eqDuplicates.push(...duplicateIdx, index); // Add both the current index and duplicates
            }
        }
    });

    // Remove duplicates in the indices array
    const uniqueIdx = [...new Set(eqDuplicates)];

    // Update the warning object
    if (add_edit_form.question_type_id == 15) {
        button_warning.cond = uniqueIdx.length > 0;
        button_warning.equivalent_idx = uniqueIdx;
        // console.log("Duplicate button equivalent indices:", button_warning.equivalent_idx);
    }
};

watch(
    () => add_edit_form.items,
    (newChoices) => {
        // Pass `newChoices` and `warning` to the function
        findEquivalentDuplicates(newChoices, button_warning);
    },
    { deep: true }
);

const findValuesDuplicates = (values, button_warning) => {
    if (add_edit_form.question_type_id == 15) {
        if (!values || !Array.isArray(values)) {
            console.warn("Invalid input: choices must be an array.");
            button_warning.cond = false;
            button_warning.value_idx = [];
            return;
        }
    }

    const scores = values.map((value) => parseInt(value.score, 10));
    const valDuplicates = [];

    // Loop through the scores and track duplicate indices
    scores.forEach((value, index) => {
        // Find all indices with the same score
        const _duplicateIdx = scores
            .map((s, value_idx) =>
                s === value && value_idx !== index ? value_idx : -1
            )
            .filter((value_idx) => value_idx !== -1);

        if (_duplicateIdx.length > 0) {
            valDuplicates.push(..._duplicateIdx, index); // Add both the current index and duplicates
        }
    });

    // Remove duplicates in the indices array
    const uniqueIndex = [...new Set(valDuplicates)];

    // Update the warning object
    if (add_edit_form.question_type_id == 15) {
        button_warning.cond = uniqueIndex.length > 0;
        button_warning.value_idx = uniqueIndex;
    }
};

watch(
    () => add_edit_form.values,
    (newChoices) => {
        // Pass `newChoices` and `warning` to the function
        findValuesDuplicates(newChoices, button_warning);
    },
    { deep: true }
);

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

// const selectGallery = (el) => {
//     const imageTag = `<img src="${usePage().props.app_url}/gallery/${el.filename}" />`;
//     if (gallery.index >= 0) {
//         // Update existing item
//         add_edit_form.items[gallery.index].name = imageTag;
//     } else {
//         // Populate `new_item`
//         new_item.value = imageTag; // Trigger the watcher for `new_item`
//     }
//     console.log('===>', add_edit_form)
//     closeGallery();
// };
const selectGallery = (el) => {
    const { ImageWithHeaders } = helpers();
    const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;
    const imageUrl = el.file_path
        ? `${fluentBase}/storage/${el.file_path}`
        : ImageWithHeaders(`${usePage().props.app_url}/gallery/${el.filename}`);
    const imageTag = `<img src="${imageUrl}" />`;

    if (gallery.index >= 0) {
        // Update an existing item at the specified index
        add_edit_form.items[gallery.index].name = imageTag;
    } else {
        // Determine whether to update `new_item` or `new_value`
        if (someConditionToDetermineNewItem()) {
            new_item.value = imageTag; // Will trigger `new_item` watcher
        } else {
            new_value.value = imageTag; // Will trigger `new_value` watcher
        }
    }

    closeGallery();
};

// Helper function to determine whether to update `new_item` or `new_value`
const someConditionToDetermineNewItem = () => {
    // Example: Return `true` if we need to populate `new_item`, otherwise `false`.
    return add_edit_form.items.length === 0; // Adjust this condition as per your use case
};
const showDeleteAllModal = ref(false);
const deleteAllItems = () => {
    add_edit_form.items = [];
    showDeleteAllModal.value = false;
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
    <div class="grid grid-cols-12 gap-1">
        <div class="col-span-6 flex justify-end">
            <button v-if="add_edit_form.items.length > 0" @click="showDeleteAllModal = true"
                class="inline-flex w-[170px] items-center px-4 py-2 hover:bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-[1.1]">
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
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-1">
        <div class="col-span-12 lg:col-span-6 mt-3">
            <div v-if="
                add_edit_form.items.length > 0
            " class="hidden md:grid grid-cols-[54px_300px_1fr_1fr_0px_0px_0px_7px_10px] gap-1">
                <jet-label class="font-bold">Index</jet-label>
                <jet-label class="font-bold">Items</jet-label>
                <jet-label class="font-bold">Label</jet-label>
                <jet-label class="font-bold">Equivalent</jet-label>
            </div>
            <div v-if="add_edit_form.question_type_id == 15" class="grid grid-cols-[50px_50px_1fr] gap-1">
            </div>
            <SlickList axis="y" v-model:list="add_edit_form.items">
                <SlickItem v-for="(item, index) in add_edit_form.items" :key="item" :index="index"
                    class="draggable-list select-none">
                    <div class="mt-1 mb-1" v-if="item">
                        <div :class="[
                            'gap-1 grid grid-cols-1',
                            button_warning.equivalent_idx.length > 0 &&
                                button_warning.equivalent_idx.includes(
                                    index
                                )
                                ? 'md:grid md:grid-cols-[10px_40px_300px_1fr_1fr_0px_0px_25px]'
                                : 'md:grid md:grid-cols-[54px_300px_1fr_1fr_0px_0px_0px_7px_10px]',
                        ]">
                            <svg v-if="
                                add_edit_form.question_type_id == 15 &&
                                button_warning.equivalent_idx.length > 0 &&
                                button_warning.equivalent_idx.includes(
                                    index
                                )
                            " xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 34 24" stroke-width="2.5"
                                stroke="red" class="w-[25px] h-[35px]">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"
                                    fill="#fff7f6" />
                            </svg>
                            <span class="mt-2 pl-2 text-sm text-gray-500">{{
                                add_edit_form.items[index].index
                            }}</span>
                            <TE2 :ref="(el) => {
                                itemRefs.item[index] = el;
                            }
                                " placeholder="Enter item" :modelValue="add_edit_form.items[index].name"
                                @update:modelValue="
                                    (val) => {
                                        add_edit_form.items[index].name = val;
                                    }
                                " @mousedown.stop></TE2>

                            <TE2 :ref="(el) => {
                                itemRefs.label[index] = el;
                            }
                                " placeholder="Enter Label" :modelValue="add_edit_form.items[index].label"
                                @update:modelValue="
                                    (val) => {
                                        add_edit_form.items[index].label = val;
                                    }
                                " @mousedown.stop></TE2>

                            <s4-input color="ssrGray" class="w-full text-xs col-span-3 pl-2" :class="{
                                'text-red-500':
                                    button_warning.equivalent_idx.length >
                                    0 &&
                                    button_warning.equivalent_idx.includes(
                                        index
                                    ),
                            }" placeholder="Enter equivalent" v-model="add_edit_form.items[index].equivalent">
                            </s4-input>

                            <ButtonIconsVue class="w-6 shadow-none border-none" @click="deleteChoice('items', index)"
                                icon="delete" />
                        </div>
                        <div class="mb-5 mr-6 ml-16 pl-20 grid grid-cols-4 gap-1" v-if="'wc' in item">
                            <div class="col-span-2">
                                <span class="text-[10px] text-gray-500">Write-in Type</span>
                                <s4-input-select color="ssrGray" class="w-full wnr" :lists="wr_types"
                                    v-model.number="item.wc.validate.type">
                                </s4-input-select>
                            </div>
                            <div class="col-span-1">
                                <span class="text-[10px] text-gray-500">Min Character</span>
                                <s4-input color="ssrGray" type="number" class="w-full wnrt"
                                    v-model.number="item.wc.validate.min"></s4-input>
                            </div>
                            <div class="col-span-1">
                                <span class="text-[10px] text-gray-500">Max Character</span>
                                <s4-input color="ssrGray" type="number" class="w-full wnrt"
                                    v-model.number="item.wc.validate.max"></s4-input>
                            </div>
                            <div class="col-span-2">
                                <span class="text-[10px] text-gray-500">Error Message - Write in</span>
                                <TE2 :modelValue="item.wc.validate.errorMsg" @update:modelValue="
                                    (val) => {
                                        item.wc.validate.errorMsg = val;
                                    }
                                "></TE2>
                            </div>
                            <div class="col-span-1">
                                <span class="text-[10px] text-gray-500">Placeholder:</span>
                                <s4-input color="ssrGray" class="w-full wnrt" v-model="item.wc.placeholder" />
                            </div>
                            <div class="col-span-1">
                                <span class="text-[10px] text-gray-500">Max-width:</span>
                                <s4-input color="ssrGray" placeholder="e.g. 100%" class="w-full wnrt"
                                    v-model="item.wc['max-width']" />
                            </div>
                        </div>
                    </div>
                </SlickItem>
            </SlickList>
            <div class="col-span-6 py-1">
                <TE2 v-if="showItemTextArea == false" placeholder="Input new item" :modelValue="new_item"
                    @update:modelValue="
                        (val) => {
                            new_item = val;
                        }
                    "></TE2>
            </div>
            <button v-if="showItemTextArea == false" :theme="'ssrAddColRow'" color="ssrB" @click="showItemArea()"
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
                    Add Multiple Items
                </span>
            </button>
            <div class="">
                <div class="grid grid-cols-3 gap-1 w-full col-span-12">
                    <!-- <s4-text-area color="ssrGray"  v-model="itemArea" class="w-full h-40"
                        placeholder="Enter items separated by new line..."></s4-text-area> -->

                    <div v-show="showItemTextArea" class="flex flex-col gap-1">
                        <jet-label class="text-xs font-semibold text-gray-600">Enter Items</jet-label>
                        <TE2 placeholder="Enter items separated by new line..." :modelValue="itemArea" height="158px"
                            @update:modelValue="
                                (val) => {
                                    itemArea = val;
                                }
                            "></TE2>
                    </div>
                    <div v-show="showItemTextArea" class="flex flex-col gap-1">
                        <jet-label class="text-xs font-semibold text-gray-600">Enter Labels</jet-label>
                        <TE2 placeholder="Enter labels separated by new line..." :modelValue="labelArea" height="158px"
                            @update:modelValue="
                                (val) => {
                                    labelArea = val;
                                }
                            "></TE2>
                    </div>
                    <div v-show="showItemTextArea" class="flex flex-col gap-1">
                        <jet-label class="text-xs font-semibold text-gray-600">Enter Equivalent</jet-label>
                        <s4-text-area v-model="equivalentArea" :filter="true" color="ssrGray" class="w-full h-40"
                            placeholder="Enter equivalent separated by new line..."></s4-text-area>
                    </div>


                </div>
                <div class="flex mb-10" v-show="showItemTextArea">
                    <s4-button :theme="'ssrAddColRow'"
                        class="py-1 px-2 mt-2 !bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg"
                        @click="splitForItem()">Add</s4-button>
                    <s4-button :theme="'ssrAddColRow'"
                        class="py-1 px-2 ml-1 mt-2 bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-125 hover:bg-transparent"
                        @click="revertItem()">Cancel</s4-button>
                </div>
            </div>
        </div>

        <div class="col-span-6 mt-3">
            <div class="grid grid-cols-[80px_1fr_1fr_100px_10px] gap-1 pl-2 pb-1" v-if="
                add_edit_form.question_type_id == 15 &&
                add_edit_form.values.length > 0
            ">
                <jet-label class="font-bold" :class="{
                    'text-red-500':
                        button_warning.value_idx.length,
                }">Values</jet-label>
                <jet-label class="font-bold">Choices</jet-label>
                <jet-label class="font-bold">Equivalent</jet-label>
                <jet-label class="font-bold">Colours</jet-label>
            </div>

            <template v-for="(value, index) in add_edit_form.values" :key="index">
                <div class="grid grid-cols-[80px_1fr_1fr_100px_10px] gap-1 pl-2 pb-1">

                    <div v-if="
                        add_edit_form.question_type_id == 15 &&
                        value?.index
                    " class="relative flex items-center gap-1">
                        <svg v-if="
                            add_edit_form.question_type_id == 15 &&
                            button_warning.value_idx.length > 0 &&
                            button_warning.value_idx.includes(index)
                        " xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                            stroke="red" class="w-[30px] h-[auto]">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"
                                fill="#fff7f6" />
                        </svg>

                        <s4-input color="ssrGray" class="w-full text-xs h-full text-center" :class="{
                            'text-red-500':
                                add_edit_form.question_type_id == 15 &&
                                button_warning.value_idx.length > 0 &&
                                button_warning.value_idx.includes(
                                    index
                                ),
                        }" placeholder="Values" v-model="add_edit_form.values[index].score" :ref="(el) => {
                            itemRefs.val[index] = el;
                        }
                            ">
                        </s4-input>
                    </div>
                    <div :class="{
                        '': value?.index,
                        '': !value?.index,
                    }">
                        <TE2 :ref="(el) => {
                            itemRefs.val[index] = el;
                        }
                            " placeholder="Enter choice" :modelValue="add_edit_form.values[index].name"
                            @update:modelValue="
                                (val) => {
                                    add_edit_form.values[index].name = val;
                                }
                            " @mousedown.stop></TE2>
                    </div>

                    <s4-input color="ssrGray" class="w-full" placeholder="Enter Equivalent"
                        v-model="add_edit_form.values[index].equivalent">
                    </s4-input>
                    <div class="relative w-[100px] h-10">
                        <!-- Input box -->
                        <s4-input color="ssrGray" class="w-full h-full text-xs" :style="{ color: value.color }"
                            placeholder="Enter hex code" v-model="add_edit_form.values[index].color"></s4-input>

                        <label
                            class="absolute top-1 ml-[73%] w-6 h-6 rounded-full border border-gray-300 cursor-pointer z-10 mt-[5px]"
                            :style="{ backgroundColor: value.color }">
                            <input type="color" v-model="add_edit_form.values[index].color" class="sr-only" />
                        </label>
                    </div>

                    <div class="" v-if="index == 0">
                        <ButtonIconsVue class="w-6 shadow-none border-none" icon="delete"
                            @click="deleteChoice('values', index)" />
                    </div>
                    <div v-else>
                        <ButtonIconsVue class="w-6 shadow-none border-none" icon="delete"
                            @click="deleteChoice('values', index)" />
                    </div>
                </div>
            </template>
            <div class="col-span-6 py-1" v-if="add_edit_form.values.length <= 7">
                <TE2 v-if="showValueTextArea == false" placeholder="Input new choice" :modelValue="new_value"
                    @update:modelValue="
                        (val) => {
                            new_value = val;
                        }
                    "></TE2>
            </div>
            <div v-if="add_edit_form.values.length <= 7">
                <button v-show="showValueTextArea == false" :theme="'ssrAddColRow'"
                    @click="showValueTextArea = !showValueTextArea"
                    class="inline-flex w-[175px] items-center px-2 py-1 mb-2 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
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
                        Add Multiple Choices
                    </span>
                </button>
                <div class="">
                    <div class="grid grid-cols-2 gap-1 w-full col-span-12">
                        <div v-show="showValueTextArea" class="flex flex-col gap-1">
                            <jet-label class="text-xs font-semibold text-gray-600">Enter Choices</jet-label>
                            <TE2 ref="valueAreaElem"
                                placeholder="Enter choices separated by new line..."
                                :modelValue="valueArea"
                                height="158px"
                                @update:modelValue="
                                    (val) => {
                                        valueArea = val;
                                    }
                                "></TE2>
                        </div>

                        <div v-show="showValueTextArea" class="flex flex-col gap-1">
                            <jet-label class="text-xs font-semibold text-gray-600">Enter Equivalent</jet-label>
                            <s4-text-area
                                color="ssrGray"
                                :filter="true"
                                v-model="equivalentChoicesArea"
                                class="w-full h-40"
                                placeholder="Enter equivalent separated by new line..."></s4-text-area>
                        </div>
                    </div>
                    <div class="flex mb-10" v-show="showValueTextArea">
                        <s4-button :theme="'ssrAddColRow'"
                            class="py-1 px-2 mt-2 !bg-[var(--mcolor)] !hover:bg-[var(--mcolor2)] text-white text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg"
                            @click="splitForValue()">Add</s4-button>
                        <s4-button :theme="'ssrAddColRow'"
                            class="py-1 px-2 ml-1 mt-2 bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-125 hover:bg-transparent"
                            @click="cancelValueInputs()">Cancel</s4-button>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="grid grid-cols-1 2xl:grid-cols-3 gap-4 pt-10 gap-y-10">
        <!-- Left: Validation & Layout -->
        <div class="col-span-2 flex flex-col gap-4 bg-white rounded-lg shadow p-4">
            <BorderSetup :class="['!-mb-8 -mt-4 -mx-4 translate-y-[-20%]']"></BorderSetup>
            <div class="grid grid-cols-1 2xl:grid-cols-2 gap-4">
                <div class="flex flex-col flex-wrap gap-2">
                    <!-- Required -->
                    <label class="flex items-center cursor-pointer max-w-[fit-content]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.required" />
                        <span class="ml-2 text-sm text-gray-600 select-none">Required</span>
                        <Tooltip :imgSrc="question" preset="Required" />
                    </label>
                     <!-- Track Reaction Time -->
                     <div class="flex flex-col flex-wrap gap-2 w-full">
                        <label class="flex items-center cursor-pointer max-w-[fit-content]">
                            <s4-checkbox v-model:checked="add_edit_form.setup.passRTToURL" />
                            <span class="ml-2 text-sm text-gray-600">Track Reaction Time</span>
                        </label>
                    </div>
                    <!-- Hide Question Frame -->
                    <div class="flex flex-col flex-wrap gap-2 w-full">
                        <label class="flex items-center cursor-pointer max-w-[fit-content]">
                            <s4-checkbox v-model:checked="add_edit_form.setup.hideQuestionFrame"
                                @update:checked="handleHideInstructionChange" />
                            <span class="ml-2 text-sm text-gray-600">Hide Instruction</span>
                            <Tooltip :imgSrc="question" preset="HideQuestionFrame" />
                        </label>
                    </div>
                    <!-- Random Items -->
                    <div class="flex flex-col flex-wrap gap-2 w-full">
                        <label class="flex items-center cursor-pointer max-w-[fit-content]">
                            <s4-checkbox class="cursor-pointer"
                                v-model:checked="add_edit_form.setup.validate.randomItems" />
                            <span class="ml-2 text-sm text-gray-600 select-none">Random Items</span>
                            <Tooltip :imgSrc="question" preset="RandomItems" />
                        </label>

                        <!-- Random Items Multiselect -->
                        <div v-if="add_edit_form.setup.validate.randomItems" class="ml-6">
                            <vue-form-multiselect placeholder="Click to select items" :options="randomItems"
                                v-model="add_edit_form.setup.randomItems" />
                        </div>

                    </div>
                    <!-- Random Values (only for type 15) -->
                    <div class="flex flex-col flex-wrap gap-2 w-full">
                        <label class="flex items-center cursor-pointer max-w-[fit-content]">
                            <s4-checkbox class="cursor-pointer"
                                v-model:checked="add_edit_form.setup.validate.randomValues" />
                            <span class="ml-2 text-sm text-gray-600 select-none">Random Choices</span>
                            <Tooltip :imgSrc="question" preset="RandomValues" />
                        </label>
                        <!-- Random Values Multiselect -->
                        <div v-if="add_edit_form.setup.validate.randomValues" class="ml-6">
                            <vue-form-multiselect placeholder="Click to select items" :options="randomValues"
                                v-model="add_edit_form.setup.randomValues" />
                        </div>
                    </div>

                    


                </div>
                <div class=" flex flex-col flex-wrap gap-2 w-full">
                    <div class="ml-6">
                        <jet-label>Header</jet-label>
                        <TE2 placeholder="Enter your text here..." :modelValue="add_edit_form.setup.header"
                             @update:modelValue="val => add_edit_form.setup.header = val" />
                    </div>
                    <div class="ml-6">
                        <jet-label>Footer</jet-label>
                        <TE2 placeholder="Enter your text here..." :modelValue="add_edit_form.setup.footer"
                             @update:modelValue="val => add_edit_form.setup.footer = val" />
                    </div>
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
                            class="flex flex-col sm:flex-row gap-4 ml-6 relative z-10">
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
        <div class="col-span-2 lg:col-span-1 flex-1 bg-white rounded-lg shadow p-4 grid grid-cols-1 gap-4 justify-start items-start"
            v-if="add_edit_form.question_type_id == 15">
            <div>
                <BorderSetup :class="['!-mb-8 -mt-4 -mx-4 translate-y-[-100%]']">
                    <template #header>
                        Analysis Options
                    </template>
                </BorderSetup>
            </div>
            <ButtonRatingAnalysis />
            <!-- <div>
                <jet-label>Calculation Type</jet-label>
                <SingleSelect
                    :options="[{text:'Percentage',value:'percentage'},{text:'Count',value:'count'}]"
                    :modelValue="add_edit_form.setup.analysis?.calculation_type ?? 'percentage'"
                    @update:modelValue="res => {
                        if (!add_edit_form.setup.analysis) add_edit_form.setup.analysis = {};
                        add_edit_form.setup.analysis.calculation_type = res || 'percentage';
                    }"
                />
            </div>
            <div>
                <jet-label>Aggregation Method</jet-label>
                <SingleSelect
                    :options="[{text:'Sum',value:'sum'},{text:'Average',value:'average'},{text:'Median',value:'median'}]"
                    :modelValue="add_edit_form.setup.analysis?.aggregation_method ?? 'sum'"
                    @update:modelValue="res => {
                        if (!add_edit_form.setup.analysis) add_edit_form.setup.analysis = {};
                        add_edit_form.setup.analysis.aggregation_method = res || 'sum';
                    }"
                />
            </div>
            <div>
                <jet-label>Aggregation Method</jet-label>
                <SingleSelect
                    :options="[{text:'Sum',value:'sum'},{text:'Average',value:'average'},{text:'Median',value:'median'}]"
                    :modelValue="add_edit_form.setup.analysis?.aggregation_method ?? 'sum'"
                    @update:modelValue="res => {
                        if (!add_edit_form.setup.analysis) add_edit_form.setup.analysis = {};
                        add_edit_form.setup.analysis.aggregation_method = res || 'sum';
                    }"
                />
            </div> -->


        </div>

        <!-- Font Style (if present) -->
        <template v-if="Object.hasOwn(add_edit_form.setup, 'fontStyle')">
            <!-- Place for future font style controls, if needed -->
        </template>
    </div>
    <!-- delete all modal for items -->
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
    <s4-gallery-modal :type="'image'" :modal="gallery" @close="closeGallery()" @selectGallery="selectGallery" />
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

.wnr :deep() select {
    padding: 5px;
    font-size: 12px;
    margin: 0px 0px;
}

.wnrt {
    padding: 5px;
    font-size: 12px;
    margin: 0px 0px;
    padding-left: 10px;
}
</style>
