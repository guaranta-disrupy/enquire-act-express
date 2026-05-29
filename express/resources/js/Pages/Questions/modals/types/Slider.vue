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

const slider_warning = reactive({
    cond: false,
    equivalent_idx: [],
    value_idx: [],
});

const button_warning = reactive({
    cond: false,
    equivalent_idx: [],
    value_idx: [],
});

defineExpose({
    slider_warning: slider_warning,
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
    // console.log("ITEMSS>>", add_edit_form)
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
    val: [],
});

onBeforeMount(() => {
    findEquivalentDuplicates(
        add_edit_form.items,
        slider_warning,
        button_warning
    );
    findValuesDuplicates(add_edit_form.values, slider_warning, button_warning);
    add_edit_form.choices = [];
});

const colors = ref(["#b71c1c", "#ef5350", "#ff9800", "#8bc34a", "#009688"]);
const showItemTextArea = ref(false);
const showValueTextArea = ref(false);
const itemArea = ref(null);
const equivalentArea = ref(null);
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
    let items = [];
    let form_items = reAssignItems();
    for (const key in form_items) {
        const val = parseInt(key) + 1;
        let data = {
            value: form_items[key]?.index ? form_items[key].index : val,
            label: `${form_items[key]?.index ? form_items[key].index : val}. ${form_items[key].name
                }`,
        };

        items.push({ ...data });
    }
    return items;
});

const randomValues = computed(() => {
    let items = [];
    for (const key in add_edit_form.values) {
        const val = parseInt(key) + 1;
        let data = {
            value: val,
            label: `${add_edit_form.values[key]?.index
                ? add_edit_form.values[key].index
                : val
                }. ${add_edit_form.values[key].name}`,
        };

        items.push({ ...data });
    }
    return items;
});

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
};

const splitForItem = (item = 0) => {
    if (itemArea.value != null) {
        const newEq = equivalentArea.value?.split("\n") ?? [];
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
    valueArea;
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

const findEquivalentDuplicates = (items, slider_warning, button_warning) => {
    if (add_edit_form.question_type_id == 8) {
        if (!items || !Array.isArray(items)) {
            console.warn("Invalid input: choices must be an array.");
            slider_warning.cond = false;
            slider_warning.equivalent_idx = [];
            return;
        }
    }

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
    if (add_edit_form.question_type_id == 8) {
        slider_warning.cond = uniqueIdx.length > 0;
        slider_warning.equivalent_idx = uniqueIdx;
    }

    if (add_edit_form.question_type_id == 15) {
        button_warning.cond = uniqueIdx.length > 0;
        button_warning.equivalent_idx = uniqueIdx;
    }
};

watch(
    () => add_edit_form.items,
    (newChoices) => {
        // Pass `newChoices` and `warning` to the function
        findEquivalentDuplicates(newChoices, slider_warning, button_warning);
    },
    { deep: true }
);

const findValuesDuplicates = (values, slider_warning, button_warning) => {
    if (add_edit_form.question_type_id == 8) {
        if (!values || !Array.isArray(values)) {
            console.warn("Invalid input: choices must be an array.");
            slider_warning.cond = false;
            slider_warning.value_idx = [];
            return;
        }
    }

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
    if (add_edit_form.question_type_id == 8) {
        slider_warning.cond = uniqueIndex.length > 0;
        slider_warning.value_idx = uniqueIndex;
    }

    if (add_edit_form.question_type_id == 15) {
        button_warning.cond = uniqueIndex.length > 0;
        button_warning.value_idx = uniqueIndex;
    }
};

watch(
    () => add_edit_form.values,
    (newChoices) => {
        // Pass `newChoices` and `warning` to the function
        findValuesDuplicates(newChoices, slider_warning, button_warning);
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

// Handle X SVG click in Random Items multiselect
const handleRandomItemsTagRemoved = () => {
    // This function is kept for potential future use
    // The actual logic is now handled by the watcher above
};

if (!add_edit_form.setup.boxStyle['max-width_enabler']) {
    add_edit_form.setup.boxStyle['max-width_enabler'] = true;
}

// Auto-select all items when random items checkbox is checked
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

// Auto-add new items to random items when checkbox is already checked
watch(
    () => add_edit_form.items.length,
    async (newLength, oldLength) => {
        if (add_edit_form.setup.validate.randomItems && newLength > oldLength) {
            // Wait for DOM update to ensure the multiselect is rendered
            await nextTick()
            // Update random items to include all current items
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
    <div class="grid grid-cols-12 gap-1">
        <div class="col-span-6 mt-3">
            <div v-if="
                (add_edit_form.question_type_id == 8 &&
                    add_edit_form.items.length > 0) ||
                (add_edit_form.question_type_id == 15 &&
                    add_edit_form.items.length > 0)
            " class="grid grid-cols-[55px_1.4fr_50px_1fr] gap-1">
                <!-- <jet-label class="font-bold">Order</jet-label> -->
                <jet-label class="font-bold">Index</jet-label>
                <jet-label class="font-bold">Items</jet-label>
                <jet-label class="font-bold">Equivalent</jet-label>
            </div>
            <div v-if="add_edit_form.question_type_id == 15" class="grid grid-cols-[50px_50px_1fr] gap-1">
                <!-- <jet-label
                    class="font-bold"
                    :class="{
                        'text-red-500':
                            button_warning.equivalent_idx.length > 0,
                    }"
                    >Items:</jet-label
                > -->
                <!-- <jet-label class="font-bold">Order:</jet-label> -->
                <!-- <jet-label class="font-bold">Index:</jet-label> -->
            </div>
            <SlickList axis="y" v-model:list="add_edit_form.items">
                <SlickItem v-for="(item, index) in add_edit_form.items" :key="item" :index="index"
                    class="draggable-list select-none">
                    <div class="mt-1 mb-1" v-if="item">
                        <!-- <label class="items-center cursor-pointer ml-12">
                            <s4-checkbox class="cursor-pointer border-ssr-blue4" v-model:checked="add_edit_form.items[index].enable_wr
                                " @change="addWC(index)" />
                            <span class="ml-2 text-xs text-gray-500 select-none">Write-in Choice</span>
                        </label>
                        <label class="ml-2 items-center cursor-pointer">
                            <s4-checkbox class="cursor-pointer border-ssr-blue4" v-model:checked="add_edit_form.items[index].optional
                                " />
                            <span class="ml-2 text-xs text-gray-500 select-none">Optional Item</span>
                        </label> -->
                        <div :class="[
                            'gap-1',
                            (add_edit_form.question_type_id == 8 &&
                                slider_warning.equivalent_idx.length > 0 &&
                                slider_warning.equivalent_idx.includes(
                                    index
                                )) ||
                                (add_edit_form.question_type_id == 15 &&
                                    button_warning.equivalent_idx.length > 0 &&
                                    button_warning.equivalent_idx.includes(
                                        index
                                    ))
                                ? 'grid grid-cols-[10px_40px_400px_1fr_0px_50px_25px]'
                                : 'grid grid-cols-[54px_400px_1fr_0px_0px_0px_7px_10px]',
                        ]">
                            <svg v-if="
                                add_edit_form.question_type_id == 8 &&
                                slider_warning.equivalent_idx.length > 0 &&
                                slider_warning.equivalent_idx.includes(
                                    index
                                )
                            " xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 29 24" stroke-width="2.5"
                                stroke="red" class="w-[25px] h-[35px] pr-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"
                                    fill="#fff7f6" />
                            </svg>
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
                            <!-- <span class="mt-2 pl-3 text-sm">{{
                                `${index + 1}`
                            }}</span> -->
                            <span class="mt-2 pl-2 text-sm text-gray-500">{{
                                add_edit_form.items[index].index
                                }}</span>

                            <!-- <s4-input color="ssrGray" class="w-full mb-1 text-xs col-span-9 pl-2"
                                    placeholder="Enter item" v-model="add_edit_form.items[index].name" :ref="(el) => {
                                        itemRefs.item[index] = el;
                                    }
                                        ">
                                </s4-input> -->

                            <!-- <s4-input color="ssrGray"
                                        class="w-full py-2 px-3 text-xs text-gray-700 focus:ring-1 focus:ring-blue-500 focus:outline-none border-none"
                                        placeholder="Enter item" v-model="add_edit_form.items[index].name" :ref="(el) => {
                                            itemRefs.item[index] = el;
                                        }
                                            ">
                                    </s4-input>
                                    <div
                                        class="w-22 py-2 px-3 text-sm text-gray-700 bg-gray-50 border-l border-gray-300 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                                        <ButtonIconsVue
                                            class="h-4 w-4 group-hover:scale-125 hover:ease-in duration-300 hover:scale-125 cursor-pointer"
                                            icon="gallery" @click="showGallery(index)" />
                                    </div> -->
                            <TE2 :ref="(el) => {
                                itemRefs.item[index] = el;
                            }
                                " placeholder="Enter item" :modelValue="add_edit_form.items[index].name"
                                @update:modelValue="
                                    (val) => {
                                        add_edit_form.items[index].name = val;
                                    }
                                " @mousedown.stop></TE2>

                            <s4-input v-if="add_edit_form.question_type_id == 8" color="ssrGray"
                                class="w-full h-full text-xs col-span-3 pl-2" :class="{
                                    'text-red-500':
                                        slider_warning.equivalent_idx.length >
                                        0 &&
                                        slider_warning.equivalent_idx.includes(
                                            index
                                        ),
                                }" placeholder="Enter equivalent" v-model="add_edit_form.items[index].equivalent">
                            </s4-input>
                            <s4-input v-if="add_edit_form.question_type_id == 15" color="ssrGray"
                                class="w-full text-xs col-span-3 pl-2" :class="{
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
                            <!-- <s4-button color="ssrRedIcon" class="mb-1 ml-1" @click="deleteChoice('items', index)">
                            <font-awesome-icon class="fa-w-20 fa-h-20" :icon="faIcon.trash" />
                        </s4-button> -->
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
                                <!-- <s4-input color="ssrGray" class="w-full wnrt text-gray-600"
                                    v-model="item.wc.validate.errorMsg"></s4-input> -->
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
                <!-- <s4-input color="ssrGray2" class="w-full mb-2" placeholder="Input new item"
                    v-model="new_item"></s4-input> -->
                <!-- <div class="flex mb-2 items-center border border-gray-300 rounded-md overflow-hidden w-full">
                    <s4-input color="ssrGray"
                        class="w-full py-2 px-3 text-sm text-gray-700 focus:ring-1 focus:ring-blue-500 focus:outline-none border-none"
                        placeholder="Input new item" v-model="new_item"></s4-input>
                    <div
                        class="w-22 py-2 px-3 text-sm text-gray-700 bg-gray-50 border-l border-gray-300 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        <ButtonIconsVue
                            class="h-4 w-4 group-hover:scale-125 hover:ease-in duration-300 hover:scale-125 cursor-pointer"
                            icon="gallery" @click="showGallery(-1)" />
                    </div>
                </div> -->
                <TE2 v-if="showItemTextArea == false" placeholder="Input new item" :modelValue="new_item"
                    @update:modelValue="
                        (val) => {
                            new_item = val;
                        }
                    "></TE2>
            </div>
            <!-- <s4-button
                v-if="showItemTextArea == false"
                :theme="'ssrAddColRow'"
                class="py-1 px-2 mb-10"
                color="ssrB"
                @click="showItemArea()"
            >
                Multiple Items
            </s4-button> -->
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
                <div class="grid grid-cols-2 gap-1 w-full col-span-12">
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
                        <jet-label class="text-xs font-semibold text-gray-600">Enter Equivalent</jet-label>
                        <s4-text-area v-model="equivalentArea" :filter="true" color="ssrGray" class="w-full h-40"
                            placeholder="Enter equivalent separated by new line..."></s4-text-area>
                    </div>
                </div>
                <!-- <div class="flex mb-10" v-show="showItemTextArea">
                    <s4-button
                        :theme="'ssrAddColRow'"
                        class="py-1 px-2 mt-2"
                        @click="splitForItem()"
                        >Add</s4-button
                    >
                    <s4-button
                        :theme="'ssrAddColRow'"
                        class="py-1 px-2 ml-1 mt-2"
                        @click="revertItem()"
                        >Cancel</s4-button
                    >
                </div> -->
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
                (add_edit_form.question_type_id == 8 &&
                    add_edit_form.values.length > 0) ||
                (add_edit_form.question_type_id == 15 &&
                    add_edit_form.values.length > 0)
            ">
                <jet-label class="font-bold" :class="{
                    'text-red-500':
                        slider_warning.value_idx.length ||
                        button_warning.value_idx.length,
                }">Values</jet-label>
                <jet-label class="font-bold">Choices</jet-label>
                <jet-label class="font-bold">Equivalent</jet-label>
                <jet-label class="font-bold">Colours</jet-label>
            </div>

            <template v-for="(value, index) in add_edit_form.values" :key="index">
                <div class="grid grid-cols-[80px_1fr_1fr_100px_10px] gap-1 pl-2 pb-1">
                    <!-- <jet-label
                        class="font-bold mb-7"
                        :class="{
                            'text-red-500':
                                button_warning.value_idx.length > 0 &&
                                button_warning.value_idx.length > 0,
                        }"
                        v-if="
                            index == 0 && add_edit_form.question_type_id == 15
                        "
                        >Values:</jet-label
                    > -->

                    <div v-if="
                        (add_edit_form.question_type_id == 8 &&
                            value?.index) ||
                        (add_edit_form.question_type_id == 15 &&
                            value?.index)
                    " class="relative flex items-center gap-1">
                        <svg v-if="
                            add_edit_form.question_type_id == 8 &&
                            slider_warning.value_idx.length > 0 &&
                            slider_warning.value_idx.includes(index)
                        " xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                            stroke="red" class="w-[30px] h-[15px]">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"
                                fill="#fff7f6" />
                        </svg>
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
                                (add_edit_form.question_type_id == 8 &&
                                    slider_warning.value_idx.length > 0 &&
                                    slider_warning.value_idx.includes(
                                        index
                                    )) ||
                                (add_edit_form.question_type_id == 15 &&
                                    button_warning.value_idx.length > 0 &&
                                    button_warning.value_idx.includes(
                                        index
                                    )),
                        }" placeholder="Values" v-model="add_edit_form.values[index].score" :ref="(el) => {
                            itemRefs.val[index] = el;
                        }
                            ">
                        </s4-input>
                    </div>
                    <!-- <div
                        v-if="
                            add_edit_form.question_type_id == 15 && value?.index
                        "
                        class="col-span-2 relative flex items-center gap-1"
                    >
                        <svg
                            v-if="
                                add_edit_form.question_type_id == 15 &&
                                button_warning.value_idx.length > 0 &&
                                button_warning.value_idx.includes(index)
                            "
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="red"
                            class="w-[30px] h-[auto]"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"
                                fill="#fff7f6"
                            />
                        </svg>
                        <s4-input
                            color="ssrGray"
                            class="w-full text-center"
                            :class="{
                                'text-red-500':
                                    button_warning.value_idx.length > 0 &&
                                    button_warning.value_idx.includes(index),
                            }"
                            placeholder="Value"
                            v-model="add_edit_form.values[index].score"
                            :ref="
                                (el) => {
                                    itemRefs.val[index] = el;
                                }
                            "
                        >
                        </s4-input>
                    </div> -->
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
                <!-- <s4-input color="ssrGray2" class="w-full mb-2 mr-1 text-xs" placeholder="Input new value"
                    v-model="new_value"></s4-input> -->
                <!-- <div class="flex mb-2 items-center border border-gray-300 rounded-md overflow-hidden w-full">
                    <s4-input color="ssrGray"
                        class="w-full py-2 px-3 text-sm text-gray-700 focus:ring-1 focus:ring-blue-500 focus:outline-none border-none"
                        placeholder="Input new value" v-model="new_value"></s4-input>
                    <div
                        class="w-22 py-2 px-3 text-sm text-gray-700 bg-gray-50 border-l border-gray-300 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        <ButtonIconsVue
                            class="h-4 w-4 group-hover:scale-125 hover:ease-in duration-300 hover:scale-125 cursor-pointer"
                            icon="gallery" @click="showGallery(-1)" />
                    </div>
                </div> -->

                <TE2 v-if="showValueTextArea == false" placeholder="Input new choice" :modelValue="new_value"
                    @update:modelValue="
                        (val) => {
                            new_value = val;
                        }
                    "></TE2>
            </div>
            <div v-if="add_edit_form.values.length <= 7">
                <!-- <s4-button
                    v-if="showValueTextArea == false"
                    :theme="'ssrAddColRow'"
                    class="py-1 px-2 mb-10"
                    color="ssrB"
                    @click="showValueTextArea = !showValueTextArea"
                >
                    Multiple Values
                </s4-button> -->
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
                        <!-- <s4-text-area color="ssrGray" v-show="showValueTextArea" v-model="valueArea"
                            class="w-full h-40 text-xs"
                            placeholder="Enter values separated by new line..."></s4-text-area> -->

                        <div v-show="showValueTextArea" class="flex flex-col gap-1">
                            <jet-label class="text-xs font-semibold text-gray-600">Enter Choices</jet-label>
                            <TE2 ref="valueAreaElem" placeholder="Enter choices separated by new line..."
                                :modelValue="valueArea" height="158px" @update:modelValue="
                                    (val) => {
                                        valueArea = val;
                                    }
                                "></TE2>
                        </div>

                        <div v-show="showValueTextArea" class="flex flex-col gap-1">
                            <jet-label class="text-xs font-semibold text-gray-600">Enter Equivalent</jet-label>
                            <s4-text-area color="ssrGray" :filter="true" v-model="equivalentChoicesArea"
                                class="w-full h-40"
                                placeholder="Enter equivalent separated by new line..."></s4-text-area>
                        </div>
                    </div>
                    <!-- <div class="flex mb-10" v-show="showValueTextArea">
                        <s4-button
                            :theme="'ssrAddColRow'"
                            class="py-1 px-2 mt-2"
                            @click="splitForValue()"
                            >Add</s4-button
                        >
                        <s4-button
                            :theme="'ssrAddColRow'"
                            class="py-1 px-2 ml-1 mt-2"
                            @click="showValueTextArea = false"
                            >Cancel</s4-button
                        >
                    </div> -->
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

    <BorderSetup></BorderSetup>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-1">
        <div class="col-span-12 md:col-span-6 gap-1">
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
            </div>
            <div class="col-span-6">
                <label class="flex items-center cursor-pointer w-[170px]">
                    <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.randomItems
                        " />
                    <span class="ml-2 text-sm text-gray-600 select-none">Random Items</span>
                    <Tooltip :imgSrc="question" preset="RandomItems">
                    </Tooltip>
                </label>
            </div>
            <div class="col-span-6 pl-6" v-if="add_edit_form.setup.validate.randomItems">
                <vue-form-multiselect placeholder="Click to select items" :options="randomItems"
                    v-model="add_edit_form.setup.randomItems" @tag-removed="handleRandomItemsTagRemoved">
                </vue-form-multiselect>
            </div>


            <div class="col-span-6">
                <!-- <div class="col-span-6">
                    <jet-label>Random Values</jet-label>
                    <vue-form-multiselect
                        style="min-height: 4rem"
                        placeholder="Click to select items"
                        :options="randomValues"
                        v-model="add_edit_form.setup.randomValues"
                        class=""
                    />
                </div> -->
                <div class="col-span-6">
                    <label class="flex items-center cursor-pointer w-[170px]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.randomValues
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Random Choices</span>
                        <Tooltip :imgSrc="question" preset="RandomValues">
                        </Tooltip>
                    </label>
                </div>
                <div class="col-span-6 pl-6" v-if="add_edit_form.setup.validate.randomValues">
                    <vue-form-multiselect placeholder="Click to select items" :options="randomValues"
                        v-model="add_edit_form.setup.randomValues">
                    </vue-form-multiselect>
                </div>

                <div class="col-span-3 ml-6 mt-1" v-if="add_edit_form.question_type_id == 8">
                    <div class="flex items-center">
                        <jet-label class="">Set Default Slider Position</jet-label>
                        <Tooltip :imgSrc="question" preset="Slider">
                        </Tooltip>
                    </div>

                    <s4-input color="ssrGray" class="w-full mb-1 text-sm" type="number"
                        v-model.number="add_edit_form.setup.default">
                    </s4-input>
                </div>
            </div>
        </div>

        <!-- <div class="col-span-6">
                <jet-label>Border Radius</jet-label>
                <s4-input
                    color="ssrGray"
                    class="w-full mb-1"
                    placeholder="e.g. 100%"
                    v-model="add_edit_form.setup.borderRadius"
                >
                </s4-input>
            </div> -->

        <div class="col-span-12 md:col-span-6 grid grid-cols-6 gap-1">
            <!-- <div
                    class="col-span-3"
                    :class="{
                        'mb-4 mt-3': add_edit_form.question_type_id == 8,
                    }"
                >
                    <jet-label>Items Layout</jet-label>
                    <s4-input-select
                        color="ssrGray"
                        class="w-full h-10"
                        :lists="layout_types"
                        v-model="add_edit_form.setup.itemsLayout"
                    >
                    </s4-input-select>
                </div> -->
            <!-- <div
                    class="col-span-3"
                    :class="{
                        'mb-4 mt-3': add_edit_form.question_type_id == 8,
                    }"
                >
                    <jet-label>Values Layout</jet-label>
                    <s4-input-select
                        color="ssrGray"
                        class="w-full h-10"
                        :lists="layout_types"
                        v-model="add_edit_form.setup.valuesLayout"
                    >
                    </s4-input-select>
                </div> -->


            <div class="col-span-6">
                <label class="cursor-pointer flex items-center gap-0.5 w-[170px]">
                    <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.boxStyle['max-width_enabler']
                        " />
                    <span class="ml-2 text-sm text-gray-600 select-none">Edit Layout Width</span>
                    <Tooltip :imgSrc="question" preset="EditLayoutWidth">
                    </Tooltip>
                </label>

                <div class="w-full grid grid-cols-3 gap-x-1 gap-y-0 pl-6 mt-1">
                    <div v-if="add_edit_form.setup.boxStyle['max-width_enabler']">
                        <jet-label>Desktop</jet-label>
                        <!-- <s4-input
                                color="ssrGray"
                                class="w-full mb-1"
                                placeholder="e.g. 100%"
                                v-model="add_edit_form.setup.boxStyle['max-width']"
                            >
                            </s4-input> -->
                        <S4InputSlider2 color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                            :units="['%', 'vmin', 'vw', 'auto']"
                            :modelValue="add_edit_form.setup.boxStyle['max-width'] = '100%'" :allowAuto="true"
                            @update:modelValue="
                                (val) => {
                                    add_edit_form.setup.boxStyle['max-width'] = val;
                                }
                            ">
                        </S4InputSlider2>
                    </div>

                    <div v-if="add_edit_form.setup.boxStyle['max-width_enabler']">
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
                                    add_edit_form.setup.boxStyle['max-width-tab'] =
                                        val;
                                }
                            ">
                        </S4InputSlider2>
                    </div>
                    <div v-if="add_edit_form.setup.boxStyle['max-width_enabler']">
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

            <div v-if="add_edit_form.question_type_id == 15" class="col-span-6">
                <div class="flex gap-4 mb-2">
                    <label class="flex items-center cursor-pointer">
                        <s4-checkbox v-model:checked="add_edit_form.setup.passRTToURL" />
                        <span class="ml-2 text-sm text-gray-600">Track Reaction Time</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <s4-checkbox v-model:checked="add_edit_form.setup.hideQuestionFrame"
                            @update:checked="handleHideInstructionChange" />
                        <span class="ml-2 text-sm text-gray-600">Hide Instruction</span>
                        <Tooltip :imgSrc="question" preset="HideQuestionFrame" />
                    </label>
                </div>
                <div class="mb-2">
                    <jet-label>Header</jet-label>
                    <TE2 placeholder="Enter your text here..." :modelValue="add_edit_form.setup.header" height="65px"
                        @update:modelValue="val => add_edit_form.setup.header = val" />
                </div>
                <div>
                    <jet-label>Bottom Text</jet-label>
                    <TE2 placeholder="Enter your text here..." :modelValue="add_edit_form.setup.footer" height="65px"
                        @update:modelValue="val => add_edit_form.setup.footer = val" />
                </div>
            </div>

            <!--
                    <jet-label class="mt-3">Random Items</jet-label>
                    <vue-form-multiselect style="min-height: 4rem" placeholder="Click to select items"
                        :options="randomItems" v-model="add_edit_form.setup.randomItems" />
                    <div class="col-span-6 mt-5" v-if="add_edit_form.question_type_id == 8">
                        <div class="flex items-center">
                            <jet-label>Layout Width</jet-label>
                            <Tooltip :imgSrc="question"
                                label="Sets the maximum width of the content box. Adjust this setting to control how wide the content can appear on the page.">
                            </Tooltip>
                        </div>
                        <S4InputSlider color="ssrGray" :placeholder="'e.g. 100'" :units="['%', 'vmin', 'vw', 'auto']"
                            v-model.number="boxStyleValue" v-model:unitValue="boxStyleUnit" :allowAuto="true" min="0">
                        </S4InputSlider>
                    </div>
                </div> -->

            <template v-if="Object.hasOwn(add_edit_form.setup, 'fontStyle')">
                <!-- <div class="col-span-3 mt-1 pl-6" v-if="add_edit_form.question_type_id == 15">
                    <jet-label>Font size (Items)</jet-label> -->
                <!-- <s4-input
                            color="ssrGray"
                            class="w-full mb-1"
                            placeholder="e.g. 100%"
                            v-model="add_edit_form.setup.fontStyle.items"
                        >
                        </s4-input> -->
                <!-- <S4InputSlider color="ssrGray" :placeholder="'e.g. 100%'" :units="['vmin']"
                        v-model.number="fontSizeItemsValue" v-model:unitValue="fontSizeItemsUnit" :allowAuto="true"
                        min="0"></S4InputSlider>
                </div> -->
                <!-- <div class="col-span-3 mt-1" v-if="add_edit_form.question_type_id == 15">
                    <jet-label>Font size (Values)</jet-label> -->
                <!-- <s4-input color="ssrGray" class="w-full mb-1" placeholder="e.g. 100%"
                            v-model="add_edit_form.setup.fontStyle.values">
                        </s4-input> -->
                <!-- <S4InputSlider color="ssrGray" :placeholder="'e.g. 100%'" :units="['vmin']"
                        v-model.number="fontSizeValuesValue" v-model:unitValue="fontSizeValuesUnit" :allowAuto="true"
                        min="0"></S4InputSlider>
                </div> -->
                <!-- <div
                        class="col-span-2 mt-[1.1vmin]"
                        v-if="add_edit_form.question_type_id == 15"
                    >
                        <div class="flex items-center">
                            <jet-label>Layout Width</jet-label>
                            <Tooltip
                                :imgSrc="question"
                                label="Sets the maximum width of the content box. Adjust this setting to control how wide the content can appear on the page."
                            >
                            </Tooltip>
                        </div>

                        <S4InputSlider
                            color="ssrGray"
                            :placeholder="'e.g. 100'"
                            :units="['%', 'vmin', 'vw', 'auto']"
                            v-model.number="boxStyleValue"
                            v-model:unitValue="boxStyleUnit"
                            :allowAuto="true"
                            min="0"
                        >
                        </S4InputSlider>
                    </div> -->
            </template>
        </div>
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
