<script setup>
import JetLabel from "@/Jetstream/Label.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import WarningModal from "../../../../S4/Warning.vue";
import S4Checkbox from "@/S4/Checkbox.vue";
import S4TextArea from "@/S4/TextArea.vue";
import S4Input from "@/S4/Input.vue";
import S4Button from "@/S4/Button.vue";
import S4InputSelect from "@/S4/InputSelect.vue";
import S4InputSlider2 from "@/S4/InputSlider2.vue";
import VueFormMultiselect from "@/S4/vueform/Multiselect.vue";
import BorderSetup from "@/S4/BorderSetup.vue";
import question from "/public/images/question.svg";
import Tooltip from "@/S4/Tooltip.vue";
import { computed, nextTick, ref, watch, inject, reactive, onBeforeMount, defineExpose } from "vue";
import ButtonIconsVue from "../../../../S4/ButtonIcons.vue";
import { SlickList, SlickItem } from "vue-slicksort";
import helpers from "@/helpers.js";
import TE2 from "@/S4/TE2.vue";
import idGenerator from "@/idGenerator";

const add_edit_form = inject("add_edit_form");

const itemRefs = ref([]);

// Validation trigger - only show errors when save is attempted
const showValidationErrors = ref(false);

// Function to check if a specific choice is empty
const isChoiceEmpty = (choice) => {
    if (!choice) return true;
    if (!choice.name) return true;

    // Remove HTML tags and check if content is empty
    const cleanName = choice.name.replace(/<[^>]*>/g, "").trim();
    return (
        cleanName === "" ||
        cleanName === "<br>" ||
        cleanName === "<p><br></p>" ||
        choice.name === "<br>" ||
        choice.name === "<p><br></p>"
    );
};

// Function version of the multiple-choice validation logic, adapted for MultipleTextbox
const validateRequiredFields = () => {
    const items = add_edit_form?.items || [];
    for (let i = 0; i < items.length; i++) {
        const ch = items[i] || {};
        if (!ch.name) {
            return { hasError: true, field: "name", index: i };
        }

        const cleanName = ch.name.replace(/<[^>]*>/g, "").trim();
        if (
            cleanName === "" ||
            cleanName === "<br>" ||
            cleanName === "<p><br></p>" ||
            ch.name === "<br>" ||
            ch.name === "<p><br></p>"
        ) {
            return { hasError: true, field: "name", index: i };
        }
    }

    return { hasError: false };
};

// Scroll to the first empty textbox choice
const scrollToFirstEmptyChoice = () => {
    const items = add_edit_form?.items || [];
    for (let i = 0; i < items.length; i++) {
        const ch = items[i] || {};
        if (!ch.name) {
            const el = itemRefs?.value?.[i]?.$el || itemRefs?.value?.[i];
            if (el) {
                el.scrollIntoView({ behavior: "smooth", block: "center" });
                el.focus();
            }
            return;
        }

        const cleanName = ch.name.replace(/<[^>]*>/g, "").trim();
        if (
            cleanName === "" ||
            cleanName === "<br>" ||
            cleanName === "<p><br></p>" ||
            ch.name === "<br>" ||
            ch.name === "<p><br></p>"
        ) {
            const el = itemRefs?.value?.[i]?.$el || itemRefs?.value?.[i];
            if (el) {
                el.scrollIntoView({ behavior: "smooth", block: "center" });
                el.focus();
            }
            return;
        }
    }
};

// Expose validation helpers so parent (AddEditQuestion.vue) can use them
defineExpose({
    showValidationErrors,
    validateRequiredFields,
    scrollToFirstEmptyChoice,
});

const types = [
    {
        value: 1,
        text: "Open Text",
    },
    {
        value: 8,
        text: "Letters Only (A-Z, a-z)",
    },
    {
        value: 9,
        text: "Alphanumeric",
    },
    {
        value: 2,
        text: "Whole Number",
    },
    {
        value: 3,
        text: "Decimal Number",
    },
    {
        value: 4,
        text: "Email Address",
    },
    {
        value: 5,
        text: "Date",
    },
    {
        value: 6,
        text: "Time",
    },
    {
        value: 7,
        text: "Date & Time",
    },
    {
        value: 10,
        text: "Pricing",
    }
];

const date_formats = [
    { value: "mm-dd-yyyy", text: "MM/DD/YYYY" },
    { value: "dd-mm-yyyy", text: "DD/MM/YYYY" },
    { value: "yyyy-mm-dd", text: "YYYY-MM-DD" },
    { value: "dd-mmm-yyyy", text: "DD-MMM-YYYY" },
    { value: "mmm-dd-yyyy", text: "MMM DD, YYYY" },
    { value: "dd-month-yyyy", text: "DD Month YYYY" },
];

const time_formats = [
    { value: "12-hour", text: "12-Hour (2:30 PM)" },
    { value: "24-hour", text: "24-Hour (14:30)" },
    { value: "12-hour-seconds", text: "12-Hour with Seconds (2:30:45 PM)" },
    { value: "24-hour-seconds", text: "24-Hour with Seconds (14:30:45)" },
];

const datetime_formats = [
    { value: "dd-mmm-yyyy hh:mm", text: "dd-mmm-yyyy hh:mm" },
    { value: "dd-mmm-yyyy hh:mm:ss.s", text: "dd-mmm-yyyy hh:mm:ss.s" },
    { value: "dd-mmm-yyyy hh:mm:ss", text: "dd-mmm-yyyy hh:mm:ss" },
    { value: "yyyy-mm-dd hh:mm", text: "yyyy-mm-dd hh:mm" },
    { value: "yyyy-mm-dd hh:mm:ss", text: "yyyy-mm-dd hh:mm:ss" },
    { value: "yyyy-mm-dd hh:mm:ss.s", text: "yyyy-mm-dd hh:mm:ss.s" },
];

const currency_formats = [
    { value: "USD", text: "USD - US Dollar" },
    { value: "EUR", text: "EUR - Euro" },
    { value: "INR", text: "INR - Indian Rupee" },
    { value: "GBP", text: "GBP - British Pound Sterling" },
    { value: "CAD", text: "CAD - Canadian Dollar" },
    { value: "JPY", text: "JPY - Japanese Yen" },
    { value: "PHP", text: "PHP - Philippine Peso" },
    { value: "CNY", text: "CNY - Chinese Yuan" },
];

const new_choice = ref("");
watch(new_choice, async (val) => {
    if (val != "") {
        add_edit_form.items.push({
            name: val,
            validate: {
                type: 1,
                min: 0,
                max: 5,
                errorMsg: "This question requires an answer",
                dateFormat: "mm-dd-yyyy", // Default date format
                timeFormat: "12-hour", // Default time format
                datetimeFormat: "dd-mmm-yyyy hh:mm", // Default datetime format
                currencyFormat: "USD", // Default currency format
            },
            CUUID: idGenerator.UUID("C"),
            index: idGenerator.index(add_edit_form.items, 'index'),
        });
        await nextTick();
        itemRefs.value[add_edit_form.items.length - 1].focus();
        new_choice.value = "";
    }
    // console.log("cvcx>> ", add_edit_form)
});

const showRowTextArea = ref(false);
const rowArea = ref(null);
const equivalentArea = ref(null);
const MultipleInputElem = ref(null);

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
const randomChoices = computed(() => {
    let list = {
        random: [],
    };
    let items = { ...reAssignItems() };
    for (const key in items) {
        const val = parseInt(key) + 1;
        let data = {
            value: items[key]?.index,
            label: `Choice ${val}: ${items[key].name}`,
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

const showRowArea = () => {
    showRowTextArea.value = !showRowTextArea.value;
};
const revertRow = () => {
    showRowTextArea.value = false;
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

            const eqRaw = newEq[index]?.trim?.();
            const rawEquivalent = eqRaw && eqRaw !== "" ? eqRaw : row;
            const equivalent = htmlToTextWithImageAlt(rawEquivalent);

            add_edit_form.items.push({
                name: "<p>" + row + "</p>",
                equivalent,
                validate: {
                    type: 1,
                    min: 0,
                    max: 5,
                    errorMsg: "This question requires an answer",
                    dateFormat: "mm-dd-yyyy", // Default date format
                    timeFormat: "12-hour", // Default time format
                    datetimeFormat: "dd-mmm-yyyy hh:mm", // Default datetime format
                    currencyFormat: "USD", // Default currency format
                },
                CUUID: idGenerator.UUID("C"),
                index: idGenerator.index(add_edit_form.items, "index"),
            });
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

const boxStyleValue = ref('');
const boxStyleUnit = ref('%');
//const for box style max-width
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
    // console.log("teqwewqe: ", add_edit_form)
    const hasValidateKey = add_edit_form.items.some(item => 'validate' in item);

    if (hasValidateKey == false) {
        add_edit_form.items = []
    }
    add_edit_form.choices = []

    // Initialize format properties for existing items
    add_edit_form.items.forEach(item => {
        if (item.validate && item.validate.type) {
            // Date formats
            if (item.validate.type === 5 && !item.validate.dateFormat) {
                item.validate.dateFormat = "mm-dd-yyyy";
            }
            if (item.validate.type === 6 && !item.validate.timeFormat) {
                item.validate.timeFormat = "12-hour";
            }
            if (item.validate.type === 7 && !item.validate.datetimeFormat) {
                item.validate.datetimeFormat = "dd-mmm-yyyy hh:mm";
            }

            // Currency format
            if (item.validate.type === 10 && !item.validate.currencyFormat) {
                add_edit_form.setup.validate.currencyFormat = "USD";
            }

            // Email length defaults
            // If the field is configured as Email Address (type 4) and still has the
            // original defaults (min: 0, max: 5), update them to the desired
            // email-friendly limits.
            if (
                Number(item.validate.type) === 4 &&
                Number(item.validate.min ?? 0) === 0 &&
                Number(item.validate.max ?? 5) === 5
            ) {
                item.validate.min = 6;
                item.validate.max = 254;
            }
        }
    });
})

onBeforeMount(() => {
    if (!add_edit_form.setup.multipleTextSelection) {
        add_edit_form.setup.multipleTextSelection = {
            layout_switch: 0,
            grid: 1,
            grid_tab: 1,
            grid_mobile: 1,
        };
    }
});

// If Edit Columns is off on load, cache existing grid values and clear inputs
onBeforeMount(() => {
    const mts = add_edit_form.setup.multipleTextSelection;
    if (mts && mts['layout_switch'] === 0) {
        if (mts['_grid_backup'] === undefined || mts['_grid_backup'] === '') mts['_grid_backup'] = mts['grid'];
        if (mts['_grid_tab_backup'] === undefined || mts['_grid_tab_backup'] === '') mts['_grid_tab_backup'] = mts['grid_tab'];
        if (mts['_grid_mobile_backup'] === undefined || mts['_grid_mobile_backup'] === '') mts['_grid_mobile_backup'] = mts['grid_mobile'];
        mts['grid'] = '';
        mts['grid_tab'] = '';
        mts['grid_mobile'] = '';
    }
});

// Handle Edit Columns toggle: cache on disable, restore on enable
watch(
    () => add_edit_form.setup.multipleTextSelection && add_edit_form.setup.multipleTextSelection['layout_switch'],
    (enabled) => {
        const mts = add_edit_form.setup.multipleTextSelection;
        if (!mts) return;
        if (!enabled) {
            mts['_grid_backup'] = mts['grid'];
            mts['_grid_tab_backup'] = mts['grid_tab'];
            mts['_grid_mobile_backup'] = mts['grid_mobile'];
            mts['grid'] = '';
            mts['grid_tab'] = '';
            mts['grid_mobile'] = '';
        } else {
            if ((mts['grid'] === '' || mts['grid'] == null) && mts['_grid_backup'] !== undefined && mts['_grid_backup'] !== '') {
                mts['grid'] = mts['_grid_backup'];
            }
            if ((mts['grid_tab'] === '' || mts['grid_tab'] == null) && mts['_grid_tab_backup'] !== undefined && mts['_grid_tab_backup'] !== '') {
                mts['grid_tab'] = mts['_grid_tab_backup'];
            }
            if ((mts['grid_mobile'] === '' || mts['grid_mobile'] == null) && mts['_grid_mobile_backup'] !== undefined && mts['_grid_mobile_backup'] !== '') {
                mts['grid_mobile'] = mts['_grid_mobile_backup'];
            }
        }
    }
);
const showDeleteAllModal = ref(false);
const deleteAllChoices = () => {
    add_edit_form.items = [];
    showDeleteAllModal.value = false;
};

// Watch for type changes and initialize format properties
watch(() => add_edit_form.items, (newItems) => {
    newItems.forEach(item => {
        if (item.validate && item.validate.type) {
            // Date / time / datetime formats
            if (item.validate.type === 5 && !item.validate.dateFormat) {
                item.validate.dateFormat = "mm-dd-yyyy";
            }
            if (item.validate.type === 6 && !item.validate.timeFormat) {
                item.validate.timeFormat = "12-hour";
            }
            if (item.validate.type === 7 && !item.validate.datetimeFormat) {
                item.validate.datetimeFormat = "dd-mmm-yyyy hh:mm";
            }

            // Email length defaults: when user switches a field to "Email Address"
            // (type 4) and it still has the initial default limits (min: 0, max: 5),
            // automatically set them to min: 6, max: 254 for better usability.
            if (
                Number(item.validate.type) === 4 &&
                Number(item.validate.min ?? 0) === 0 &&
                Number(item.validate.max ?? 5) === 5
            ) {
                item.validate.min = 6;
                item.validate.max = 254;
            }
        }
    });
}, { deep: true });

// Handle X SVG click in Random Choices multiselect
const handleRandomChoicesTagRemoved = (option, event) => {
    // This function is kept for potential future use
    // The actual logic is now handled by the watcher above
    console.log('Random Choices tag removed event triggered!', option, event);
};

// Computed properties for Format and Currency visibility
const hasFormatType = computed(() => {
    return add_edit_form.items.some(
        (item) =>
            item.validate?.type == 5 ||
            item.validate?.type == 6 ||
            item.validate?.type == 7
    );
});

const hasCurrencyType = computed(() => {
    return add_edit_form.items.some((item) => item.validate?.type == 10);
});
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
                Delete All Choices
            </span>
        </button>
    </div>

    <div class="grid grid-cols-12 gap-1">
        <div class="col-span-12 mt-1">
            <!-- Column headers:
                 - If any Date/Time/Date&Time/Pricing fields exist (hasFormatType/hasCurrencyType),
                   always show the original table header.
                 - Otherwise, only show headers on lg+; below that we use list view labels. -->
            <div
                class="choices-header gap-1 w-full hidden xl:grid"
                :class="hasFormatType || hasCurrencyType ? 'choices-grid--with-format' : 'choices-grid--simple'"
                v-if="add_edit_form.items.length"
            >
                <!-- <jet-label class="font-bold ">Order</jet-label> -->
                <jet-label class="font-bold">Index</jet-label>
                <jet-label class="font-bold">Choices</jet-label>
                <jet-label class="font-bold">Selection Type</jet-label>
                <jet-label class="font-bold" v-if="hasFormatType || hasCurrencyType">
                    <span v-if="hasFormatType && !hasCurrencyType">Format</span>
                    <span v-else-if="hasCurrencyType && !hasFormatType">Currency</span>
                    <!-- Empty when both are present (fused) -->
                </jet-label>
                <jet-label class="font-bold">Equivalent</jet-label>
                <jet-label class="font-bold ">Minimum</jet-label>
                <jet-label class="font-bold ">Maximum</jet-label>
                <!-- <jet-label class="font-bold ">Required Field Alert</jet-label> -->
            </div>
            <SlickList axis="y" v-model:list="add_edit_form.items">
                <SlickItem v-for="(choice, index) in add_edit_form.items" :key="choice" :index="index"
                    class="draggable-list select-none">
                    <div
                        class="gap-1 w-full choices-grid"
                        :class="hasFormatType || hasCurrencyType ? 'choices-grid--with-format' : 'choices-grid--simple'"
                        v-if="choice"
                    >
                        <!-- Index -->
                        <div class="flex flex-col">
                            <jet-label class="font-bold choices-label">Index</jet-label>
                            <span class="flex items-center pl-3">
                                {{
                                    (add_edit_form.items[index]?.index
                                        ? add_edit_form.items[index]?.index
                                        : index + 1)
                                }}
                            </span>
                        </div>

                        <!-- Choices -->
                        <div class="flex flex-col">
                            <jet-label class="font-bold choices-label">Choices</jet-label>
                            <TE2 color="ssrGray" class="w-full mb-1" placeholder="Enter choice"
                                :modelValue="add_edit_form.items[index].name"
                                @update:modelValue="(val) => { add_edit_form.items[index].name = val }" :ref="(el) => {
                                    itemRefs[index] = el;
                                }" @mousedown.stop>
                            </TE2>
                        </div>

                        <!-- Selection Type -->
                        <div class="flex flex-col" v-if="add_edit_form.items.length">
                            <jet-label class="font-bold choices-label">Selection Type</jet-label>
                            <s4-input-select class="w-full mb-1" color="ssrGray" :lists="types"
                                v-model="add_edit_form.items[index].validate.type">
                            </s4-input-select>
                        </div>

                        <!-- Fused Format and Currency column -->
                        <div class="flex flex-col" v-if="hasFormatType || hasCurrencyType">
                            <jet-label class="font-bold choices-label">
                                <span v-if="add_edit_form.items[index].validate.type == 10">Currency</span>
                                <span v-else>Format</span>
                            </jet-label>
                            <!-- Date Format -->
                            <s4-input-select v-if="add_edit_form.items[index].validate.type == 5"
                                class="w-full mb-1" color="ssrGray" :lists="date_formats"
                                v-model="add_edit_form.items[index].validate.dateFormat">
                            </s4-input-select>
                            <!-- Time Format -->
                            <s4-input-select v-if="add_edit_form.items[index].validate.type == 6"
                                class="w-full mb-1" color="ssrGray" :lists="time_formats"
                                v-model="add_edit_form.items[index].validate.timeFormat">
                            </s4-input-select>
                            <!-- Date & Time Format -->
                            <s4-input-select v-if="add_edit_form.items[index].validate.type == 7"
                                class="w-full mb-1" color="ssrGray" :lists="datetime_formats"
                                v-model="add_edit_form.items[index].validate.datetimeFormat">
                            </s4-input-select>
                            <!-- Currency Format -->
                            <s4-input-select v-if="add_edit_form.items[index].validate.type == 10"
                                class="w-full mb-1" color="ssrGray" :lists="currency_formats"
                                v-model="add_edit_form.items[index].validate.currencyFormat">
                            </s4-input-select>
                        </div>

                        <!-- Equivalent -->
                        <div class="flex flex-col">
                            <jet-label class="font-bold choices-label">Equivalent</jet-label>
                            <s4-input color="ssrGray" class="w-full" placeholder="Enter Equivalent"
                                v-model="add_edit_form.items[index].equivalent">
                            </s4-input>
                        </div>

                        <!-- Minimum -->
                        <div class="flex flex-col" v-if="add_edit_form.items.length">
                            <jet-label class="font-bold choices-label">Minimum</jet-label>
                            <div class="flex">
                                <s4-input color="ssrGray" class="w-full mb-1" placeholder="Enter choice" type="number"
                                    v-model.number="add_edit_form.items[index].validate.min">
                                </s4-input>
                            </div>
                        </div>

                        <!-- Maximum -->
                        <div class="flex flex-col" v-if="add_edit_form.items.length">
                            <jet-label class="font-bold choices-label">Maximum</jet-label>
                            <div class="flex">
                                <s4-input color="ssrGray" class="w-full mb-1" type="number"
                                    v-model.number="add_edit_form.items[index].validate.max">
                                </s4-input>
                            </div>
                        </div>

                        <!-- Delete button -->
                        <div class="" v-if="add_edit_form.items.length">
                            <div class="flex gap-1">
                                <ButtonIconsVue icon="delete" @click="deleteChoice(index)" />
                            </div>
                        </div>
                        <!-- Show validation error for empty choices -->
                        <div v-if="showValidationErrors && isChoiceEmpty(choice)" class="col-span-full -mt-2 mb-2 ml-16">
                            <jet-input-error message="This field cannot be left empty." />
                        </div>
                    </div>
                </SlickItem>
            </SlickList>
        </div>
        <div class="col-span-12">
            <s4-input color="ssrGray" class="w-full mb-2" placeholder="Input new choice"
                v-model="new_choice"></s4-input>

        </div>
        <div class="grid grid-cols-[170px_1fr] gap-2 w-full col-span-12">
            <button v-show="showRowTextArea == false" :theme="'ssrAddColRow'"
                @click="showRowTextArea = !showRowTextArea"
                class="inline-flex w-full items-center px-2 py-1 mb-2 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-black text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
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

                <span class="transition-all duration-300 ease-in-out hover:text-white text-white">
                    Add Multiple Inputs
                </span>
            </button>
            <div class="grid grid-cols-2 gap-1 w-full col-span-12">

                <JetLabel class="ml-1" v-show="showRowTextArea">Enter Choices</JetLabel>
                <JetLabel v-show="showRowTextArea">Enter Equivalent</JetLabel>

                <!-- <s4-text-area color="ssrGray" v-show="showRowTextArea" v-model="rowArea" class="w-full h-40"
                placeholder="Enter choices separated by new line..."></s4-text-area> -->
                <TE2 v-show="showRowTextArea" ref="MultipleInputElem"
                    placeholder="Enter choices separated by new line..." :modelValue="rowArea" height="160px"
                    @update:modelValue="(val) => { rowArea = val }"></TE2>
                <s4-text-area :filter="true" v-show="showRowTextArea" color="ssrGray" v-model="equivalentArea"
                    class="w-full h-40" placeholder="Enter equivalent separated by new line..."></s4-text-area>
            </div>
            <!-- <div class="flex items-start justify-start">
                <button v-if="showRowTextArea == false && showRangeArea == false" :theme="'ssrAddColRow'" color="ssrB"
                    @click="showRange()"
                    class="inline-flex w-[170px] items-center px-2 py-2 mb-2 bg-[var(--mcolor)] hover:bg-yellow-400 text-black text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        style="fill: rgba(0, 0, 0, 1);">
                        <path d="M19 11h-6V5h-2v6H5v2h6v6h2v-6h6z"></path>
                    </svg>

                    <span class="transition-all duration-300 ease-in-out hover:text-gray-800">
                        Add Range Inputs
                    </span>
                </button>
                <div v-show="showRowTextArea" class="w-full">
                    <s4-text-area color="ssrGray" v-model="rowArea" class="w-full h-40"
                        placeholder="Enter choices separated by new line..."></s4-text-area>
                </div>


                <div>

                    <div class="grid grid-cols-[180px_80px_80px_100px_130px_100px] max-w-[350px] items-center gap-2"
                        v-show="showRangeArea">
                        <div>Please enter the range</div>
                        <s4-input color="ssrGray" v-model="range1" placeholder="From">
                        </s4-input>

                        <s4-input color="ssrGray" v-model="range2" placeholder="To">
                        </s4-input>
                        <label class="items-center cursor-pointer flex">
                            <s4-checkbox class="cursor-pointer" v-model:checked="isDescending" />
                            <span class="ml-2 mr-5 text-sm text-gray-600 select-none">Descending</span>
                        </label>

                        <button :theme="'ssrAddColRow'"
                            class="py-1 px-2 mt-2 relative inline-flex items-center bg-gray-200 border-transparent rounded-md text-xs text-gray-900 tracking-widest hover:bg-gray-300 focus:outline-none"
                            @click="generateRange()" :disabled="typeof range1 === 'string' ||
                                typeof range2 === 'string' ||
                                range1 > range2
                                ">
                            Generate Range
                        </button>

                        <s4-button :theme="'ssrAddColRow'" class="py-1 px-2 ml-1 mt-2"
                            @click="cancelRange()">Cancel</s4-button>


                    </div>

                </div>


            </div> -->
        </div>
        <div class="col-span-12">


            <div class="flex mb-5" v-show="showRowTextArea">
                <s4-button :theme="'ssrAddColRow'"
                    class="py-1 px-2 mt-2 !bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg"
                    @click="splitForRow()">Add</s4-button>
                <s4-button :theme="'ssrAddColRow'"
                    class="py-1 px-2 ml-1 mt-2 bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-125 hover:bg-transparent"
                    @click="cancelMultipleInputs()">Cancel</s4-button>
            </div>
            <div class="flex mb-5" v-show="showRangeArea">

            </div>
        </div>
    </div>

    <BorderSetup></BorderSetup>

    <!-- Options & Layout: stack as list view below xl, split into two columns only on xl+ -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-1 mt-5">
        <div class="col-span-1 xl:col-span-6">
            <div class="grid grid-cols-6 gap-1">
                <div class="col-span-6">
                    <label class="flex items-center cursor-pointer w-[110px]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.required
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Required</span>
                        <Tooltip :imgSrc="question" preset="Required">
                        </Tooltip>
                    </label>
                    <!-- <div class="mt-2 pl-5" v-if="add_edit_form.setup.validate.required">
                        <s4-input color="ssrGray" class="w-full mb-1" :disabled="add_edit_form.setup.validate.required == 0
                            " v-model="add_edit_form.setup.validate.errorMsg">
                        </s4-input>
                        <TE2 color="ssrGray" class="w-full mb-1" placeholder=""
                            :disabled="add_edit_form.setup.validate.required == 0"
                            :modelValue="add_edit_form.setup.validate.errorMsg"
                            @update:modelValue="(val) => { add_edit_form.setup.validate.errorMsg = val }"></TE2>
                    </div> -->
                </div>

                <!-- <div class="col-span-3 pl-5" v-if="add_edit_form.items?.some(i => Number(i?.validate?.type) === 10)">
                    <span class="text-sm text-gray-600 select-none">Before Textbox</span>
                  
                    <TE2 color="ssrGray" class="w-[100%] mb-1" placeholder="Enter text"
                        :modelValue="add_edit_form.setup.prependString" @update:modelValue="
                            (val) => {
                                add_edit_form.setup.prependString = val;
                            }
                        " @mousedown.stop></TE2>
                </div>
                <div class="col-span-3" v-if="add_edit_form.items?.some(i => Number(i?.validate?.type) === 10)">
                    <span class="text-sm text-gray-600 select-none">After Textbox</span>
                    <TE2 color="ssrGray" class="w-[100%] mb-1" placeholder="Enter text"
                        :modelValue="add_edit_form.setup.appendString" @update:modelValue="
                            (val) => {
                                add_edit_form.setup.appendString = val;
                            }
                        " @mousedown.stop></TE2>
                </div> -->

                <div class="col-span-6">
                    <label class="items-center cursor-pointer flex max-w-[160px]">
                        <s4-checkbox class="cursor-pointer" :disabled="add_edit_form.items.length === 0" v-model:checked="add_edit_form.setup.validate.randomChoices
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Random Choices</span>
                        <Tooltip :imgSrc="question" preset="RandomChoices">
                        </Tooltip>
                    </label>
                </div>
                <div class="col-span-6 ml-5" v-if="add_edit_form.setup.validate.randomChoices">
                    <!-- <jet-label>Random Choices</jet-label> -->
                    <vue-form-multiselect
                        placeholder="Click to select choices"
                        :options="randomChoices.random"
                        v-model="add_edit_form.setup.randomChoices"
                        @tag-removed="handleRandomChoicesTagRemoved"
                    />
                </div>
                <div class="col-span-6" v-if="add_edit_form.items?.some(i => Number(i?.validate?.type) === 1)"
                    :set="helpers().initProperty(add_edit_form.setup, 'detectGibberish', 0)">
                    <label class="items-center cursor-pointer flex max-w-[203px]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.detectGibberish
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Detect Gibberish Text</span>
                        <Tooltip :imgSrc="question" preset="Gibberish">
                        </Tooltip>
                    </label>
                </div>
                <div class="col-span-6" v-if="add_edit_form.items?.some(i => Number(i?.validate?.type) === 10)"
                    :set="helpers().initProperty(add_edit_form.setup, 'minorUnitSymbol', 0)">
                    <label class="items-center cursor-pointer flex max-w-[203px]">
                        <s4-checkbox class="cursor-pointer"
                            v-model:checked="add_edit_form.setup.minorUnitSymbol"
                            :true-value="1"
                            :false-value="0"
                        />
                        <span class="ml-2 text-sm text-gray-600 select-none">Minor Unit Symbol</span>
                        <Tooltip :imgSrc="question" preset="EditTextboxDimension">
                        </Tooltip>
                    </label>
                </div>
            </div>
        </div>
        <div class="col-span-1 xl:col-span-6">
            <div class="grid grid-cols-6 gap-1">
                <div class="col-span-6"
                    :set="helpers().initProperty(add_edit_form.setup.multipleTextSelection, 'layout_switch', 0)">
                    <label class="items-center cursor-pointer">
                        <s4-checkbox class="cursor-pointer"
                            v-model:checked="add_edit_form.setup.multipleTextSelection['layout_switch']" />
                        <span class="ml-2 text-sm text-gray-600 select-none">Edit Display Type</span>
                    </label>
                </div>
                <div class="col-span-2 flex flex-col gap-2 pl-5"
                    v-if="add_edit_form.setup.multipleTextSelection['layout_switch']">
                
                    <div :set="helpers().initProperty(add_edit_form.setup.multipleTextSelection, 'grid', 5)">
                        <jet-label>Columns (Desktop)</jet-label>
                        <s4-input type="number" color="ssrGray" class="w-full" placeholder="Input number of columns"
                            :modelValue="add_edit_form.setup.multipleTextSelection['grid']" @update:modelValue="
                                (res) => {
                                    add_edit_form.setup.multipleTextSelection['grid'] = res;
                                }
                            ">
                        </s4-input>
                    </div>
                </div>

                <div class="col-span-2 flex flex-col gap-2"
                    v-if="add_edit_form.setup.multipleTextSelection['layout_switch']">
                
                    <div :set="helpers().initProperty(add_edit_form.setup.multipleTextSelection, 'grid_tab', 3)">
                        <jet-label>Columns (Tablet)</jet-label>
                        <s4-input type="number" color="ssrGray" class="w-full" placeholder="Input number of columns"
                            :modelValue="add_edit_form.setup.multipleTextSelection['grid_tab']" @update:modelValue="
                                (res) => {
                                    add_edit_form.setup.multipleTextSelection['grid_tab'] = res;
                                }
                            ">
                        </s4-input>
                    </div>
                </div>

                <div class="col-span-2 flex flex-col gap-2"
                    v-if="add_edit_form.setup.multipleTextSelection['layout_switch']">
                
                    <div :set="helpers().initProperty(add_edit_form.setup.multipleTextSelection, 'grid_mobile', 2)">
                        <jet-label>Columns (Mobile)</jet-label>
                        <s4-input type="number" color="ssrGray" class="w-full" placeholder="Input number of columns"
                            :modelValue="add_edit_form.setup.multipleTextSelection['grid_mobile']" @update:modelValue="
                                (res) => {
                                    add_edit_form.setup.multipleTextSelection['grid_mobile'] = res;
                                }
                            ">
                        </s4-input>
                    </div>
                </div>
                <!-- <div class="col-span-2 mt-5">
                    <jet-label>Box style size:</jet-label>
                    <s4-input
                        color="ssrGray"
                        class="w-full mb-1"
                        placeholder="e.g. md"
                        v-model="add_edit_form.setup.boxStyle.size"
                    >
                    </s4-input>
                </div> -->
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
                <div class="col-span-2 pl-5" v-if="add_edit_form.setup.boxStyle['max-width_enabler']">
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
                
                <div class="col-span-2" v-if="add_edit_form.setup.boxStyle['max-width_enabler']">
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
                <div class="col-span-2" v-if="add_edit_form.setup.boxStyle['max-width_enabler']">
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
        <teleport to="body">
            <WarningModal :show="showDeleteAllModal" @close="showDeleteAllModal = false">
                <template #header>
                    Confirm Deletion
                </template>
                <template #content>
                    Are you sure you want to delete <span class="font-semibold">all choices</span>?
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

/* Responsive layout for the choices table/list */
.choices-grid {
    display: grid;
    grid-template-columns: 1fr;
}

.choices-label {
    display: block;
}
/* Switch to multi-column table layout when viewport is wide enough.
   Match Tailwind's xl breakpoint (1280px) so header and rows stay in sync. */
@media (min-width: 1280px) {
    .choices-label {
        display: none;
    }

    .choices-grid.choices-grid--with-format,
    .choices-header.choices-grid--with-format {
        grid-template-columns: 60px minmax(0, 4.05fr) 1fr 1fr 400px 80px 80px 40px;
    }

    .choices-grid.choices-grid--simple,
    .choices-header.choices-grid--simple {
        grid-template-columns: 60px minmax(0, 2fr) 1fr 400px 80px 80px 40px;
    }
}
</style>
