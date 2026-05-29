<script setup>
import JetLabel from "@/Jetstream/Label.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import WarningModal from "../../../../S4/Warning.vue";
import S4Checkbox from "@/S4/Checkbox.vue";
import S4TextArea from "@/S4/TextArea.vue";
import S4Input from "@/S4/Input.vue";
import S4InputSelect from "@/S4/InputSelect.vue";
import S4Button from "@/S4/Button.vue";
import S4InputSlider2 from "@/S4/InputSlider2.vue";
import VueFormMultiselect from "@/S4/vueform/Multiselect.vue";
import BorderSetup from "@/S4/BorderSetup.vue";
import question from "/public/images/question.svg";
import Tooltip from "@/S4/Tooltip.vue";
import { SlickList, SlickItem } from "vue-slicksort";
import TE2 from "@/S4/TE2.vue";
import idGenerator from "@/idGenerator";
import OverwriteModal from "@/Pages/Questions/modals/OverwriteModal.vue";

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
// import { Warning } from "postcss";

// const { apply_disabled } = helpers();
const add_edit_form = inject("add_edit_form");
// const $VariableName = inject("$VariableName");
// const $VariableSwitch = ref(true)
const wr_types = [
    { value: 1, text: "Open Text" },
    { value: 9, text: "Letters Only (A-Z, a-z)" },
    { value: 10, text: "Alphanumeric" },
    { value: 2, text: "Whole Number" },
    { value: 3, text: "Decimal Number" },
    { value: 4, text: "Email Address" },
    { value: 5, text: "Date" },
    { value: 6, text: "Time" },
    { value: 7, text: "Date & Time" },
    { value: 11, text: "Pricing" },
    // { value: 8, text: "Date Range" },
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

const write_in_choice = reactive({
    enable: 1,
    setup: {
        placeholder: "",
        placeholderB: "",
        "max-width": "",
        validate: {
            type: 1, //[Specific Length, Whole Number, Decimal Number, Email Address, Date, Time, Date Time]
            min: 1, //For SL, WN, DN
            max: 10, //For SL, WN, DN
            errorMsg: "This question requires an answer",
            dateFormat: "mm-dd-yyyy", // Default date format
            timeFormat: "12-hour", // Default time format
            datetimeFormat: "dd-mmm-yyyy hh:mm", // Default datetime format
            currencyFormat: "USD", // Default currency format
        },
    },
});

const warning = reactive({
    cond: false,
    idx: [],
});

// Validation trigger - only show errors when save is attempted
const showValidationErrors = ref(false);

// Function to check if a specific choice is empty
const isChoiceEmpty = (choice) => {
    if (!choice) return true;
    if (!choice.name) return true;

    // Remove HTML tags and check if content is empty
    const cleanName = choice.name.replace(/<[^>]*>/g, '').trim();
    return cleanName === '' || cleanName === '<br>' || cleanName === '<p><br></p>' || choice.name === '<br>' || choice.name === '<p><br></p>';
};

// Expose warning and helper methods to parent
const validateRequiredFields = () => {
    const choicesList = add_edit_form?.choices || [];
    for (let i = 0; i < choicesList.length; i++) {
        const ch = choicesList[i] || {};
        if (!ch.name) return { hasError: true, field: 'name', index: i };

        // Remove HTML tags and check if content is empty
        const cleanName = ch.name.replace(/<[^>]*>/g, '').trim();
        if (cleanName === '' || cleanName === '<br>' || cleanName === '<p><br></p>' || ch.name === '<br>' || ch.name === '<p><br></p>') {
            return { hasError: true, field: 'name', index: i };
        }
    }
    return { hasError: false };
};

const scrollToField = (index, field) => {
    try {
        if (field === 'name') {
            const el = itemRefs?.value?.[index]?.$el || itemRefs?.value?.[index];
            if (el) {
                el.scrollIntoView({ behavior: "smooth", block: "center" });
                el.focus();
            }
        }
    } catch (error) {
        console.error("Error scrolling to field:", error);
    }
};

const scrollToFirstEmptyChoice = () => {
    const choicesList = add_edit_form?.choices || [];
    for (let i = 0; i < choicesList.length; i++) {
        const ch = choicesList[i] || {};
        if (!ch.name) {
            // Scroll to the choice input field
            const choiceElement = itemRefs?.value?.[i]?.$el || itemRefs?.value?.[i];
            if (choiceElement) {
                choiceElement.scrollIntoView({ behavior: "smooth", block: "center" });
                choiceElement.focus();
            }
            return;
        }

        // Remove HTML tags and check if content is empty
        const cleanName = ch.name.replace(/<[^>]*>/g, '').trim();
        if (cleanName === '' || cleanName === '<br>' || cleanName === '<p><br></p>' || ch.name === '<br>' || ch.name === '<p><br></p>') {
            // Scroll to the choice input field
            const choiceElement = itemRefs?.value?.[i]?.$el || itemRefs?.value?.[i];
            if (choiceElement) {
                choiceElement.scrollIntoView({ behavior: "smooth", block: "center" });
                choiceElement.focus();
            }
            return;
        }
    }
};

defineExpose({
    warning: warning,
    showValidationErrors: showValidationErrors,
    validateRequiredFields,
    scrollToFirstEmptyChoice,
});
const itemRefs = ref([]);
// const setItemRef = (el) => {
//     if (el) {
//         itemRefs.value.push(el);
//     }
// };

// const list_type = reactive([
//     { value: 1, text: "Grid View" },
//     { value: 2, text: "List View" },
// ]);

// Set default values for layout options
//console.log("editedIdx",editedIdx)
if (!add_edit_form.setup.layout_desktop) {
    add_edit_form.setup.layout_desktop = 1; // Default to "Grid View"
}
if (!add_edit_form.setup.layout_tab) {
    add_edit_form.setup.layout_tab = 1; // Default to "Grid View"
}
if (!add_edit_form.setup.layout_mobile) {
    add_edit_form.setup.layout_mobile = 1; // Default to "Grid View"
}

const new_choice_elem = ref(null)
const new_choice = ref("");
watch(new_choice, async (val) => {
    // console.log("add_edit_form: ", add_edit_form)
    if (val !== "" && val !== "<p><br></p>") {
        add_edit_form.choices.push({
            name: val,
            equivalent: "",
            enable_wr: 0,
            CUUID: idGenerator.UUID("C"),
            index: idGenerator.index(add_edit_form.choices, 'index'),
            score: idGenerator.index(add_edit_form.choices, 'score'),
            points: 0,
            locked: false,
            checkboxScore: {
                active: 1,
                inactive: 0,
            },
        });
        await nextTick();
        itemRefs.value[add_edit_form.choices.length - 1].focus();
        new_choice.value = "";
        new_choice_elem.value.forceClear();
    }
});

watch(
    () => {
        add_edit_form.setup.selectMultiple;
    },
    () => {
        if (add_edit_form.setup.selectMultiple === 1) {
            add_edit_form.choices.forEach((value, key) => {
                if (!value.checkboxScore) {
                    value.checkboxScore = {
                        active: 1,
                        inactive: 0,
                    };
                }
                // Ensure locked flag exists and is boolean when multi-select is enabled
                if (value.locked === undefined || value.locked === null) {
                    value.locked = false;
                } else {
                    value.locked = !!value.locked;
                }
            });
        }
        // console.log("multiple Select:> ", add_edit_form.choices)
    },

    { deep: true }
);

const showRowTextArea = ref(false);
const showRangeArea = ref(false);
const MultipleInputElem = ref(null);
const rowArea = ref(null);
const equivalentArea = ref(null);
const range1 = ref(null);
const range2 = ref(null);
const isDescending = ref(false);

const parseAsInt = (value) => {
    const parsed = parseInt(value, 10);
    return isNaN(parsed) ? String(value) : parsed;
};

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

watch(range1, (newValue) => {
    range1.value = parseAsInt(newValue);
});

watch(range2, (newValue) => {
    range2.value = parseAsInt(newValue);
});

const processRandomChoices = () => {
    let list = {
        write_in: [],
        random: [],
        select_all: [],
        unselect_all: [],
    };
    // let choices = JSON.parse(JSON.stringify(add_edit_form.choices)); // Object.create(add_edit_form.choices);
    let choices = { ...add_edit_form.choices }; // Object.create(add_edit_form.choices);
    // console.log(choices);
    for (const key in choices) {
        const val = parseInt(key) + 1;
        var choice_label = "Choice " + val + ": ";
        if (choices[key]?.name != null) {
            if (choices[key].name.length > 60) {
                choice_label += choices[key]?.name.substring(0, 60);
            } else {
                choice_label += choices[key]?.name;
            }
        }

        let data = {
            value: choices[key]?.index,
            label: choice_label,
        };

        list.random.push({ ...data });
        list.write_in.push({
            value: choices[key]?.index,
            text: choice_label,
        });
        //disable some select
        if (add_edit_form.setup.selectAll.includes(val)) {
            list.unselect_all.push({ ...data, ...{ disabled: true } });
        } else {
            list.unselect_all.push({ ...data });
        }
        if (add_edit_form.setup.unSelectAll.includes(val)) {
            list.select_all.push({ ...data, ...{ disabled: true } });
        } else {
            list.select_all.push({ ...data });
        }
    }
    return list;
};

// Optimized computed property with better performance
const randomChoices = computed(() => {
    const choices = add_edit_form.choices;
    const choicesLength = choices.length;
    
    // Pre-allocate arrays with known size for better performance
    const list = {
        write_in: [],
        random: [],
        select_all: [],
        unselect_all: [],
    };
    
    // Use Set for O(1) lookups instead of array.includes() which is O(n)
    const selectAllSet = new Set(add_edit_form.setup.selectAll || []);
    const unSelectAllSet = new Set(add_edit_form.setup.unSelectAll || []);

    // Use for loop instead of for...in for better performance
    for (let i = 0; i < choicesLength; i++) {
        const choice = choices[i];
        if (!choice) continue;
        
        const val = choice.index;
        const choice_label = "<span>" + val + ". </span>" + (choice.name || "");
        
        const data = {
            value: val,
            label: choice_label,
        };

        list.random.push(data);
        list.write_in.push({
            value: val,
            text: choice_label,
        });
        
        // Use Set.has() for O(1) lookup instead of array.includes()
        if (selectAllSet.has(val)) {
            list.unselect_all.push({ ...data, disabled: true });
        } else {
            list.unselect_all.push(data);
        }
        
        if (unSelectAllSet.has(val)) {
            list.select_all.push({ ...data, disabled: true });
        } else {
            list.select_all.push(data);
        }
    }
    
    return list;
});

onBeforeMount(() => {
    findDuplicateIndices(add_edit_form.choices, warning);
    initialized();
    add_edit_form.items = [];
});

const initialized = () => {
    // console.log("initialized");
    for (const key in add_edit_form.choices) {
        //add enable_wr for old choices
        if ("enable_wr" in add_edit_form.choices[key] == false) {
            if ("wc" in add_edit_form.choices[key]) {
                add_edit_form.choices[key]["enable_wr"] = 1;
            } else {
                add_edit_form.choices[key]["enable_wr"] = 0;
            }
        }
        if ("index" in add_edit_form.choices[key] == false) {
            add_edit_form.choices[key]["index"] = parseInt(key) + 1;
        }
        // Ensure write-in choices have dateFormat property
        if ("wc" in add_edit_form.choices[key] && add_edit_form.choices[key].wc.setup.validate.type === 5) {
            if (!("dateFormat" in add_edit_form.choices[key].wc.setup.validate)) {
                add_edit_form.choices[key].wc.setup.validate.dateFormat = "mm-dd-yyyy";
            }
        }
        // Ensure write-in choices have timeFormat property
        if ("wc" in add_edit_form.choices[key] && add_edit_form.choices[key].wc.setup.validate.type === 6) {
            if (!("timeFormat" in add_edit_form.choices[key].wc.setup.validate)) {
                add_edit_form.choices[key].wc.setup.validate.timeFormat = "12-hour";
            }
        }
        // Ensure write-in choices have datetimeFormat property
        if ("wc" in add_edit_form.choices[key] && add_edit_form.choices[key].wc.setup.validate.type === 7) {
            if (!("datetimeFormat" in add_edit_form.choices[key].wc.setup.validate)) {
                add_edit_form.choices[key].wc.setup.validate.datetimeFormat = "dd-mmm-yyyy hh:mm";
            }
        }
        if ("wc" in add_edit_form.choices[key] && add_edit_form.choices[key].wc.setup.validate.type === 11) {
            if (!("currencyFormat" in add_edit_form.choices[key].wc.setup.validate)) {
                add_edit_form.choices[key].wc.setup.validate.currencyFormat = "USD";
            }
        }
    }
};

const deleteChoice = async (index) => {
    // Clear pending sync timers to prevent stale index access
    if (randomChoicesSyncTimer) {
        clearTimeout(randomChoicesSyncTimer);
        randomChoicesSyncTimer = null;
    }
    if (duplicateCheckTimer) {
        clearTimeout(duplicateCheckTimer);
        duplicateCheckTimer = null;
    }
    
    // Get the choice index before deletion to use stable identifier
    const choice = add_edit_form.choices[index];
    if (choice) {
        deleteMultipleSelected("randomChoices", choice.index);
        deleteMultipleSelected("unSelectAll", choice.index);
        deleteMultipleSelected("selectAll", choice.index);
    }

    // Directly splice - no need for reAssignChoices which creates unnecessary reactive wrappers
    add_edit_form.choices.splice(index, 1);
    
    // Only filter if there are actually null/undefined items (rare case)
    // Most of the time, splice is sufficient
    const hasNulls = add_edit_form.choices.some(item => item === null || item === undefined);
    if (hasNulls) {
        // Use requestAnimationFrame for smoother UI updates
        requestAnimationFrame(() => {
            add_edit_form.choices = reAssignChoices();
        });
    }
};

// Optimized: Don't create new reactive objects unnecessarily
const reAssignChoices = () => {
    // Filter in place if possible, or return filtered array without reactive wrapper
    // The choices array is already reactive, so we don't need to wrap it again
    return add_edit_form.choices.filter((element) => {
        return element !== null && element !== undefined;
    });
};

const deleteMultipleSelected = (selected_key, choiceIndex) => {
    // Use the stable choice.index property instead of array index + 1
    // This ensures we match the values stored in randomChoices which use choice.index
    if (!add_edit_form.setup[selected_key] || !Array.isArray(add_edit_form.setup[selected_key])) {
        return;
    }
    
    const val = choiceIndex; // Use the actual choice.index value
    const exist_index = add_edit_form.setup[selected_key].findIndex(
        (value) => value == val
    );

    for (const key in add_edit_form.setup[selected_key]) {
        // re assign values to selected random  choices
        // Only decrement if the stored value is greater than the deleted choice's index
        if (add_edit_form.setup[selected_key][key] > val) {
            add_edit_form.setup[selected_key][key] -= 1;
        }
    }
    //delete after re assigning
    if (exist_index != -1) {
        add_edit_form.setup[selected_key].splice(exist_index, 1);
    }
};

const addWC = (index) => {
    if (add_edit_form.choices[index].enable_wr) {
        add_edit_form.choices[index]["wc"] = _.cloneDeep(write_in_choice);
    } else {
        delete add_edit_form.choices[index].wc;
    }
};

const showRowArea = () => {
    showRowTextArea.value = !showRowTextArea.value;
};

const showRange = () => {
    showRangeArea.value = !showRangeArea.value;
};

const revertRow = () => {
    showRowTextArea.value = false;
};

const cancelRange = () => {
    showRangeArea.value = false;
    // Clear the range inputs
    range1.value = null;
    range2.value = null;
    isDescending.value = false;
};

const cancelMultipleInputs = () => {
    showRowTextArea.value = false;
    // Clear the text areas
    rowArea.value = '<p><br></p>';
    equivalentArea.value = '';
    // Force clear the TE2 component if it has a forceClear method
    if (MultipleInputElem.value && MultipleInputElem.value.forceClear) {
        MultipleInputElem.value.forceClear();
    }
};

const modal = ref({ show: false, onConfirm: null });
const generateRange = () => {
    if (range1.value === null || range2.value === null) {
        console.error("Both range1 and range2 must be set.");
        return;
    }

    if (add_edit_form.choices.length > 0) {
        modal.value = {
            show: true,
            onConfirm: doGenerateRange,
        };
    } else {
        doGenerateRange();
    }
};

const doGenerateRange = async () => {
    // Give the browser time to render the spinner before starting heavy operation
    await new Promise(resolve => setTimeout(resolve, 50));

    const start = Math.min(range1.value, range2.value);
    const end = Math.max(range1.value, range2.value);

    // Build the entire array first, then assign it once to avoid triggering reactivity on each push
    const newChoices = [];
    let index = 1;

    if (isDescending.value) {
        for (let i = end; i >= start; i--) {
            newChoices.push({
                checkboxScore: { active: 1, inactive: 0 },
                enable_wr: 0,
                index: index++,
                name: i.toString(),
                score: i.toString(),
            });
        }
    } else {
        for (let i = start; i <= end; i++) {
            newChoices.push({
                checkboxScore: { active: 1, inactive: 0 },
                enable_wr: 0,
                index: index++,
                name: i.toString(),
                score: i.toString(),
            });
        }
    }

    // Assign the entire array at once - much faster than pushing one by one
    add_edit_form.choices = newChoices;

    // Wait for Vue to process the reactivity and DOM updates before closing
    await nextTick();

    showRangeArea.value = false;
    modal.value.show = false; // close modal
};


const splitForRow = () => {
    if (rowArea.value != null) {
        const newEq = equivalentArea.value?.split("\n") ?? [];
        const newRow =
            rowArea.value.match(/<p>(.*?)<\/p>/g)?.map((p) =>
                p.replace(/<\/?p>/g, "").trim()
            ) || [];

        _.forEach(newRow, function (row, index) {
            // Ignore empty lines
            if (!row) {
                return;
            }

            // If no equivalent is provided for a line, default it to the choice text
            const eqRaw = newEq[index]?.trim();
            const rawEquivalent = eqRaw && eqRaw !== "" ? eqRaw : row;
            const equivalent = htmlToTextWithImageAlt(rawEquivalent);

            add_edit_form.choices.push({
                name: "<p>" + row + "</p>",
                equivalent,
                enable_wr: 0,
                CUUID: idGenerator.UUID("C"),
                index: idGenerator.index(add_edit_form.choices, "index"),
                score: idGenerator.index(add_edit_form.choices, "score"),
                points: 0,
                checkboxScore: {
                    active: 1,
                    inactive: 0,
                },
            });
        });
    }

    rowArea.value = "<p><br></p>";
    equivalentArea.value = "";
    MultipleInputElem.value.forceClear();
    showRowTextArea.value = false;
};

const findDuplicateIndices = (choices, warning) => {
    if (!choices || !Array.isArray(choices)) {
        console.warn("Invalid input: choices must be an array.");
        warning.cond = false;
        warning.idx = [];
        return;
    }

    // Use a Map for O(n) performance instead of O(n²)
    const scoreMap = new Map();
    const duplicateIndices = new Set();

    // First pass: build a map of scores to their indices
    choices.forEach((choice, index) => {
        const score = parseInt(choice.score, 10);
        if (!scoreMap.has(score)) {
            scoreMap.set(score, []);
        }
        scoreMap.get(score).push(index);
    });

    // Second pass: find all indices with duplicate scores
    scoreMap.forEach((indices) => {
        if (indices.length > 1) {
            indices.forEach(idx => duplicateIndices.add(idx));
        }
    });

    // Convert Set to Array
    const uniqueIndices = Array.from(duplicateIndices);

    // Update the warning object
    warning.cond = uniqueIndices.length > 0;
    warning.idx = uniqueIndices;
};

// Debounce timer for duplicate detection
let duplicateCheckTimer = null;

// Optimized watch with debouncing to batch rapid changes
watch(
    () => add_edit_form.choices?.length,
    () => {
        // Clear existing timer
        if (duplicateCheckTimer) {
            clearTimeout(duplicateCheckTimer);
        }
        
        // Debounce duplicate checking
        duplicateCheckTimer = setTimeout(() => {
            findDuplicateIndices(add_edit_form.choices, warning);
            duplicateCheckTimer = null;
        }, 100); // 100ms debounce
    }
);

// Separate deep watch for duplicate detection (runs less frequently)
watch(
    () => add_edit_form.choices,
    (newChoices) => {
        findDuplicateIndices(newChoices, warning);
    },
    { deep: true }
);

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
});


const showDeleteAllModal = ref(false);
const deleteAllChoices = () => {
    // Clear all pending timers to prevent stale index access
    if (randomChoicesSyncTimer) {
        clearTimeout(randomChoicesSyncTimer);
        randomChoicesSyncTimer = null;
    }
    if (duplicateCheckTimer) {
        clearTimeout(duplicateCheckTimer);
        duplicateCheckTimer = null;
    }
    if (randomChoicesTimer) {
        clearTimeout(randomChoicesTimer);
        randomChoicesTimer = null;
    }
    if (selectAllTimer) {
        clearTimeout(selectAllTimer);
        selectAllTimer = null;
    }
    if (unSelectAllTimer) {
        clearTimeout(unSelectAllTimer);
        unSelectAllTimer = null;
    }
    
    // Clear choices array
    add_edit_form.choices = [];
    
    // Explicitly clear randomChoices, selectAll, and unSelectAll arrays since there are no choices
    if (add_edit_form.setup.randomChoices) {
        add_edit_form.setup.randomChoices = [];
    }
    if (add_edit_form.setup.selectAll) {
        add_edit_form.setup.selectAll = [];
    }
    if (add_edit_form.setup.unSelectAll) {
        add_edit_form.setup.unSelectAll = [];
    }
    
    showDeleteAllModal.value = false;
};

watch(
    () => add_edit_form.setup.isDropdown,
    (newVal) => {
        if (newVal) {
            // When Dropdown is enabled, just turn off the Edit Columns toggle.
            // We no longer clear the existing column values so defaults are preserved.
            if (add_edit_form.setup.textSelection) {
                add_edit_form.setup.textSelection['layout_switch'] = false;
            }
        }
    }
);

watch(
    () => add_edit_form.setup.validate.randomChoices,
    async (checked) => {
        if (checked) {
            // Wait for DOM update to ensure the multiselect is rendered
            await nextTick();
            // Select all options by extracting the values from randomChoices
            add_edit_form.setup.randomChoices = randomChoices.value.random.map((choice) => choice.value);
        } else {
            // Clear selections when unchecked
            add_edit_form.setup.randomChoices = [];
        }
    }
);

// Keep Apply Select All config but disable its effect when unchecked
watch(
    () => add_edit_form.setup.validate.selectAll,
    (checked) => {
        const setup = add_edit_form.setup;
        if (!setup) return;

        if (checked) {
            // Restore from backup when re-enabled and current list is empty
            if ((!setup.selectAll || setup.selectAll.length === 0) && Array.isArray(setup._selectAll_backup) && setup._selectAll_backup.length) {
                setup.selectAll = [...setup._selectAll_backup];
            }
        } else {
            // Cache current config then clear so runtime logic no longer applies it
            if (Array.isArray(setup.selectAll) && setup.selectAll.length) {
                setup._selectAll_backup = [...setup.selectAll];
                setup.selectAll = [];
            }
        }
    }
);

// Keep Apply Unselect All config but disable its effect when unchecked
watch(
    () => add_edit_form.setup.validate.unSelectAll,
    (checked) => {
        const setup = add_edit_form.setup;
        if (!setup) return;

        if (checked) {
            if ((!setup.unSelectAll || setup.unSelectAll.length === 0) && Array.isArray(setup._unSelectAll_backup) && setup._unSelectAll_backup.length) {
                setup.unSelectAll = [...setup._unSelectAll_backup];
            }
        } else {
            if (Array.isArray(setup.unSelectAll) && setup.unSelectAll.length) {
                setup._unSelectAll_backup = [...setup.unSelectAll];
                setup.unSelectAll = [];
            }
        }
    }
);

// Debounce timers for array watchers
let randomChoicesTimer = null;
let selectAllTimer = null;
let unSelectAllTimer = null;

// Optimized watchers with debouncing and shallow checks
watch(
    () => add_edit_form.setup.randomChoices?.length,
    (newLength) => {
        if (randomChoicesTimer) clearTimeout(randomChoicesTimer);
        randomChoicesTimer = setTimeout(() => {
            if (newLength === 0) {
                add_edit_form.setup.validate.randomChoices = false;
            }
            randomChoicesTimer = null;
        }, 150);
    }
);

watch(
    () => add_edit_form.setup.selectAll?.length,
    (newLength) => {
        if (selectAllTimer) clearTimeout(selectAllTimer);
        selectAllTimer = setTimeout(() => {
            if (newLength === 0) {
                add_edit_form.setup.validate.selectAll = false;
            }
            selectAllTimer = null;
        }, 150);
    }
);

watch(
    () => add_edit_form.setup.unSelectAll?.length,
    (newLength) => {
        if (unSelectAllTimer) clearTimeout(unSelectAllTimer);
        unSelectAllTimer = setTimeout(() => {
            if (newLength === 0) {
                add_edit_form.setup.validate.unSelectAll = false;
            }
            unSelectAllTimer = null;
        }, 150);
    }
);

// Debounce timer for random choices sync
let randomChoicesSyncTimer = null;

// Auto-enable Random Choices and include all choices when new choices are added
// Also clear randomChoices when all choices are deleted
watch(
    () => add_edit_form.choices.length,
    async (newLength, oldLength) => {
        if (newLength > oldLength) {
            // If not enabled yet, enable it (another watcher will populate the values)
            if (!add_edit_form.setup.validate.randomChoices) {
                add_edit_form.setup.validate.randomChoices = true
                return
            }
            // Debounce the sync operation
            if (randomChoicesSyncTimer) clearTimeout(randomChoicesSyncTimer);
            randomChoicesSyncTimer = setTimeout(async () => {
                await nextTick()
                // Validate choices still exist before syncing
                if (add_edit_form.choices?.length > 0 && randomChoices.value?.random) {
                    add_edit_form.setup.randomChoices = randomChoices.value.random.map(choice => choice.value)
                }
                randomChoicesSyncTimer = null;
            }, 150);
        } else if (newLength === 0 && oldLength > 0) {
            // When all choices are deleted, clear randomChoices immediately
            if (add_edit_form.setup.randomChoices) {
                add_edit_form.setup.randomChoices = [];
            }
        }
    }
)

// Keep Random Choices selection in sync with any changes to choices (renumber, edits, deletes)
// Optimized with debouncing to avoid excessive updates
watch(
    () => add_edit_form.choices,
    async () => {
        if (add_edit_form?.setup?.validate?.randomChoices) {
            // Debounce to batch rapid changes
            if (randomChoicesSyncTimer) clearTimeout(randomChoicesSyncTimer);
            randomChoicesSyncTimer = setTimeout(async () => {
                await nextTick();
                // Clear randomChoices if no choices exist, otherwise sync with current choices
                if (add_edit_form.choices?.length === 0) {
                    add_edit_form.setup.randomChoices = [];
                } else if (add_edit_form.choices?.length > 0 && randomChoices.value?.random) {
                    add_edit_form.setup.randomChoices = randomChoices.value.random.map((choice) => choice.value);
                }
                randomChoicesSyncTimer = null;
            }, 200); // Slightly longer debounce for deep changes
        }
    },
    { deep: true }
)

const getRowGridClass = (index) => {
    if (add_edit_form.setup.selectMultiple === 1 && add_edit_form.setup.enablePoint === 1) {
        return 'grid grid-cols-[auto_7fr_8fr_1fr_80px_80px_30px_30px_30px] gap-1 items-center' + (index !== 0 ? ' mt-2 mb-1' : '');
    }
    if (add_edit_form.setup.enablePoint) {
        return 'grid grid-cols-[40px_7fr_6fr_1fr_80px_30px_30px_30px] gap-1 items-center' + (index !== 0 ? ' mt-2 mb-1' : '');
    }
    if (add_edit_form.setup.selectMultiple === 1) {
        return 'grid grid-cols-[auto_7fr_7fr_80px_80px_30px_30px_30px] gap-1 items-center' + (index !== 0 ? ' mt-2 mb-1' : '');
    }
    return 'grid grid-cols-[auto_8fr_8fr_80px_30px_30px_30px] gap-1 items-center' + (index !== 0 ? ' mt-2 mb-1' : '');
};

const gridClass = computed(() => {
    if (add_edit_form.setup.selectMultiple === 1 && add_edit_form.setup.enablePoint === 1) {
        return 'grid-cols-[auto_7fr_8fr_1fr_80px_80px_30px_30px_30px]';
    }
    if (add_edit_form.setup.enablePoint) {
        return 'grid-cols-[40px_7fr_6fr_1fr_60px_30px_30px_30px]';
    }
    if (add_edit_form.setup.selectMultiple === 1) {
        return 'grid-cols-[40px_8fr_8fr_80px_80px_30px_30px_30px]';
    }
    return 'grid-cols-[40px_8fr_8fr_80px_30px_30px_30px]';
});

if (!add_edit_form.setup.boxStyle['max-width_enabler']) {
    add_edit_form.setup.boxStyle['max-width_enabler'] = true;
}

// Prevent reorders that cross over locked choices
const isValidReorder = (previousList, proposedList) => {
    if (!Array.isArray(previousList) || !Array.isArray(proposedList)) return false;

    // Build index maps by stable key (prefer CUUID)
    const getKey = (item) => item?.CUUID ?? item?.index ?? JSON.stringify(item);
    const prevIndexOf = new Map(previousList.map((item, idx) => [getKey(item), idx]));
    const lockedKeys = previousList.filter((c) => !!c.locked).map((c) => getKey(c));

    // Quick accept if no locked items
    if (lockedKeys.length === 0) return true;

    // For each unlocked item, ensure it does not cross any locked item
    for (const item of previousList) {
        const key = getKey(item);
        if (lockedKeys.includes(key)) continue; // skip locked items themselves

        const prevPos = prevIndexOf.get(key);
        const newPos = proposedList.findIndex((i) => getKey(i) === key);
        if (newPos === -1) return false;

        for (const lockedKey of lockedKeys) {
            const prevLockedPos = prevIndexOf.get(lockedKey);
            const newLockedPos = proposedList.findIndex((i) => getKey(i) === lockedKey);
            if (newLockedPos === -1) return false;

            const wasLeft = prevPos < prevLockedPos;
            const isLeft = newPos < newLockedPos;
            if (wasLeft !== isLeft) {
                return false; // crossed over a locked item
            }
        }
    }

    return true;
};

const onChoicesReorder = async (newList) => {
    const previous = Array.isArray(add_edit_form?.choices) ? [...add_edit_form.choices] : [];
    // Always accept the proposed list to keep vue-slicksort internals in sync
    add_edit_form.choices = newList;

    if (!isValidReorder(previous, newList)) {
        // Revert on next tick to avoid node/index mismatch inside vue-slicksort
        await nextTick();
        add_edit_form.choices = previous;
    }
};

const shouldCancelDragStart = (event) => {
    if (!event || !event.target) return true; // default to cancel unless we detect valid handle
    const target = event.target;
    // Only allow drag when starting from the drag-handle and the item is not locked
    if (typeof target.closest === 'function') {
        const handle = target.closest('.drag-handle');
        if (!handle) return true;

        // If handle exists, ensure its row isn't locked
        const row = handle.closest('[data-choice-index]');
        if (!row) return true;
        const idxAttr = row.getAttribute('data-choice-index');
        const idx = idxAttr != null ? Number(idxAttr) : NaN;
        if (Number.isNaN(idx)) return true;
        const choice = Array.isArray(add_edit_form?.choices) ? add_edit_form.choices[idx] : null;
        if (!choice || !!choice.locked) return true;

        // allow drag
        return false;
    }
    return true;
};

// Handle X SVG click in Random Choices multiselect
const handleRandomChoicesTagRemoved = (option, event) => {
    // This function is kept for potential future use
    // The actual logic is now handled by the watcher above
    console.log('Random Choices tag removed event triggered!', option, event);
};

// Handle X SVG click in Select All multiselect
const handleSelectAllTagRemoved = (option, event) => {
    // This function is kept for potential future use
    // The actual logic is now handled by the watcher above
    console.log('Select All tag removed event triggered!', option, event);
};

// Handle X SVG click in Unselect All multiselect
const handleUnselectAllTagRemoved = (option, event) => {
    // This function is kept for potential future use
    // The actual logic is now handled by the watcher above
    console.log('Unselect All tag removed event triggered!', option, event);
};
</script>

<template>
    <div class="flex justify-end" v-if="add_edit_form.choices.length > 0">
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
    <div class="grid grid-cols-12 gap-1 mb-5">
        <div class="col-span-12" v-if="add_edit_form.choices.length > 0">
            <div class="grid gap-1" :class="gridClass">
                <!-- <jet-label class="font-bold w-[20px]"> </jet-label> -->
                <jet-label class="font-bold w-[60px]">Index</jet-label>
                <jet-label class="font-bold w-full">Choices</jet-label>
                <jet-label class="font-bold w-full">Equivalent</jet-label>
                <jet-label class="font-bold w-full" :class="{ 'text-red-500': warning.cond }"
                    v-if="add_edit_form.setup.selectMultiple === 0">Value
                </jet-label>
                <jet-label class="font-bold w-full" :class="{ 'text-red-500': warning.cond }"
                    v-if="add_edit_form.setup.selectMultiple === 0 && add_edit_form.setup.enablePoint === 1">Points
                </jet-label>
                <jet-label class="text-xs font-bold w-full" v-if="add_edit_form.setup.selectMultiple === 1">Selected
                    Value</jet-label>
                <jet-label class="text-xs font-bold w-full" v-if="add_edit_form.setup.selectMultiple === 1">Unselected
                    Value</jet-label>
                <jet-label class="text-xs font-bold w-full"
                    v-if="add_edit_form.setup.selectMultiple === 1 && add_edit_form.setup.enablePoint === 1">
                    Points</jet-label>
                <!-- <jet-label class="font-bold w-full">Pionts</jet-label> -->
                <div class="w-[160px]"></div>
            </div>
            <SlickList axis="y" :list="add_edit_form.choices" @update:list="onChoicesReorder" :lockAxis="'y'"
                :helperClass="'drag-helper'" :pressDelay="120" :shouldCancelStart="shouldCancelDragStart">
                <SlickItem v-for="(choice, index) in add_edit_form.choices" :key="choice.CUUID ?? ('idx-' + index)"
                    :index="index" class="draggable-list select-none" :data-choice-index="index">
                    <div class="grid grid-cols-6 gap-1" v-show="choice">
                        <div class="col-span-6">


                            <div :class="getRowGridClass(index)">
                                <!-- <span class="w-[20px]">{{ index + 1 }}</span> -->
                                <div class="min-w-[40px] text-center">
                                    <!-- <span v-if="add_edit_form.setup.selectMultiple != 1">
                                        {{ ((choice.score) ? choice.score : choice.index) }}
                                    </span> -->
                                    <span>
                                        {{ choice.index }}
                                    </span>
                                </div>
                                <!-- <span class="w-[60px]">{{
                                    "#" +
                                    (add_edit_form.choices[index]?.index
                                        ? add_edit_form.choices[index]?.index
                                        : index + 1)
                                }}</span> -->

                                <div class="w-full flex gap-1 items-center">
                                    <svg v-if="
                                        warning.cond &&
                                        warning.idx.includes(index)
                                    " xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2.5" stroke="red" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"
                                            fill="#fff7f6" />
                                    </svg>

                                    <!-- <s4-input color="ssrGray" class="w-full" placeholder="Enter choice"
                                        v-model="add_edit_form.choices[index].name" :ref="(el) => {
                                            itemRefs[index] = el;
                                        }
                                            ">
                                    </s4-input> -->

                                    <TE2 placeholder="Enter choice" :modelValue="add_edit_form.choices[index].name"
                                        @update:modelValue="(val) => { add_edit_form.choices[index].name = val }" :ref="(el) => {
                                            itemRefs[index] = el;
                                        }" @mousedown.stop></TE2>

                                </div>

                                <s4-input color="ssrGray" class="w-full" placeholder="Enter Equivalent"
                                    v-model="add_edit_form.choices[index].equivalent">
                                </s4-input>
                                <s4-input v-if="
                                    add_edit_form.setup.selectMultiple !== 1
                                " color="ssrGray" class="w-full" :class="{
                                    'text-red-500':
                                        warning.cond &&
                                        warning.idx.includes(index),
                                }" placeholder="Enter Scores" v-model="add_edit_form.choices[index].score">
                                </s4-input>
                                <s4-input v-if="
                                    add_edit_form.setup.selectMultiple !== 1 && add_edit_form.setup.enablePoint === 1
                                " color="ssrGray" class="w-full" type="number" :class="{
                                    'text-red-500':
                                        warning.cond &&
                                        warning.idx.includes(index),
                                }" placeholder="Enter Points" v-model.number="add_edit_form.choices[index].points">
                                </s4-input>
                                <s4-input v-if="
                                    add_edit_form.setup.selectMultiple ===
                                    1 &&
                                    add_edit_form.choices[index]
                                        .checkboxScore
                                " color="ssrGray" class="w-full" placeholder="Enter Score" v-model="add_edit_form.choices[index]
                                    .checkboxScore.active
                                    ">
                                </s4-input>
                                <s4-input v-if="
                                    add_edit_form.setup.selectMultiple ===
                                    1 &&
                                    add_edit_form.choices[index]
                                        .checkboxScore
                                " color="ssrGray" class="w-full" placeholder="Enter Score" v-model="add_edit_form.choices[index]
                                    .checkboxScore.inactive
                                    ">
                                </s4-input>
                                <s4-input type="number" v-if="
                                    add_edit_form.setup.selectMultiple == 1 && add_edit_form.setup.enablePoint === 1
                                " color="ssrGray" class="w-full" :class="{
                                    'text-red-500':
                                        warning.cond &&
                                        warning.idx.includes(index),
                                }" placeholder="Enter Points" v-model.number="add_edit_form.choices[index].points">
                                </s4-input>
                                <!-- <s4-input color="ssrGray" class="w-full" :class="{
                                    'text-red-500':
                                        warning.cond &&
                                        warning.idx.includes(index),
                                }" placeholder="Enter Scores" v-model="add_edit_form.choices[index].score">
                                </s4-input> -->

                                <div class="relative group">
                                    <ButtonIconsVue icon="delete" @click="deleteChoice(index)" />
                                    <!-- <span
                                        class="absolute bottom-[40px] left-1/2 transform -translate-x-1/2 mb-0 px-2 py-1 text-[10px] text-white bg-yellow-500 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        Delete
                                    </span> -->
                                    <Tooltip label="Delete" hoverWidth="auto" xTransformClass="translate(-50%,-270%)">
                                    </Tooltip>

                                </div>
                                <div class="relative group">
                                    <ButtonIconsVue
                                        :icon="add_edit_form.choices[index].enable_wr ? 'en_write' : 'write'" @click="
                                            add_edit_form.choices[
                                                index
                                            ].enable_wr =
                                            !add_edit_form.choices[index]
                                                .enable_wr;
                                        addWC(index);
                                        " />
                                    <Tooltip label="Write" hoverWidth="auto" xTransformClass="translate(-50%,-270%)">
                                    </Tooltip>
                                </div>
                                <div class="relative group">
                                    <ButtonIconsVue icon="move" class="drag-handle"
                                        :class="{ 'opacity-50 cursor-not-allowed': choice.locked }" />
                                    <Tooltip :label="choice.locked ? 'Dragging disabled' : 'Hold to drag'"
                                        hoverWidth="76px" xTransformClass="translate(-50%,-270%)" />
                                </div>
                            </div>
                        </div>

                        <!-- Show validation error for empty choices -->
                        <div v-if="showValidationErrors && isChoiceEmpty(choice)" class="col-span-full -mt-2 ml-11">
                            <jet-input-error message="This field cannot be left empty." />
                        </div>

                        <!-- <div class="col-span-6 -mt-2 ml-12">
                            <label class="items-center cursor-pointer ml-4">
                                <s4-checkbox class="cursor-pointer border-ssr-blue4" v-model:checked="add_edit_form.choices[index].enable_wr
                                    " @change="addWC(index)" />
                                <span class="ml-1 text-xs text-gray-500 select-none">Write-in Choice</span>
                            </label>
                        </div> -->
                        <div class="col-span-6 grid grid-cols-6 gap-1 mb-2 ml-12" v-if="'wc' in choice">
                            <div class="col-span-6 ml-[60px] flex flex-wrap gap-1">
                                <div class="w-[200px]">
                                    <span class="text-xs text-gray-500">Write-in Type</span>
                                    <s4-input-select color="ssrGray" class="w-full" :lists="wr_types" v-model.number="choice.wc.setup.validate.type
                                        ">
                                    </s4-input-select>
                                </div>
                                <div class="w-[200px]" v-if="choice.wc.setup.validate.type === 11">
                                    <span class="text-xs text-gray-500">Currency</span>
                                    <s4-input-select color="ssrGray" class="w-full" :lists="currency_formats" v-model.number="choice.wc.setup.validate.currencyFormat">
                                    </s4-input-select>
                                </div>
                                <div class="w-[170px]"
                                    v-if="choice.wc.setup.validate.type !== 5 && choice.wc.setup.validate.type !== 6 && choice.wc.setup.validate.type !== 7">
                                    <span class="text-xs text-gray-500">{{ (choice.wc.setup.validate.type === 2 ||
                                        choice.wc.setup.validate.type === 3 || choice.wc.setup.validate.type === 11) ? 'Minimum Number' : 'Minimum Character'
                                        }}</span>
                                    <s4-input color="ssrGray" type="number" class="w-full" v-model.number="choice.wc.setup.validate.min
                                        "></s4-input>
                                </div>
                                <div class="w-[170px]"
                                    v-if="choice.wc.setup.validate.type !== 5 && choice.wc.setup.validate.type !== 6 && choice.wc.setup.validate.type !== 7">
                                    <span class="text-xs text-gray-500">{{ (choice.wc.setup.validate.type === 2 ||
                                        choice.wc.setup.validate.type === 3 || choice.wc.setup.validate.type === 11) ? 'Maximum Number' : 'Maximum Character'
                                        }}</span>
                                    <s4-input color="ssrGray" type="number" class="w-full" v-model.number="choice.wc.setup.validate.max
                                        "></s4-input>
                                </div>
                                <div class="w-[180px]" v-if="choice.wc.setup.validate.type === 5">
                                    <span class="text-xs text-gray-500">Date Format</span>
                                    <s4-input-select color="ssrGray" class="w-full" :lists="date_formats"
                                        v-model="choice.wc.setup.validate.dateFormat">
                                    </s4-input-select>
                                </div>
                                <div class="w-[auto]" v-if="choice.wc.setup.validate.type === 6">
                                    <span class="text-xs text-gray-500">Time Format</span>
                                    <s4-input-select color="ssrGray" class="w-full" :lists="time_formats"
                                        v-model="choice.wc.setup.validate.timeFormat">
                                    </s4-input-select>
                                </div>
                                <div class="w-[auto]" v-if="choice.wc.setup.validate.type === 7">
                                    <span class="text-xs text-gray-500">Date & Time Format</span>
                                    <s4-input-select color="ssrGray" class="w-full" :lists="datetime_formats"
                                        v-model="choice.wc.setup.validate.datetimeFormat">
                                    </s4-input-select>
                                </div>
                                <!-- <div class="grow-[1]">
                                    <span class="text-xs text-gray-500">Error Message</span>
                                    <s4-input color="ssrGray" class="w-full" v-model="choice.wc.setup.validate.errorMsg
                                        "></s4-input>

                                    <TE2 placeholder="" :modelValue="choice.wc.setup.validate.errorMsg"
                                        @update:modelValue="(val) => { choice.wc.setup.validate.errorMsg = val }"
                                        @mousedown.stop></TE2>
                                </div> -->
                                <div class="w-[350px]">
                                    <span class="text-xs text-gray-500">Placeholder</span>
                                    <s4-input color="ssrGray" class="w-full" v-model="choice.wc.setup.placeholder" />
                                </div>
                                <div class="w-[170px]">
                                    <span class="text-xs text-gray-500">Set Max Width</span>
                                    <s4-input color="ssrGray" placeholder="e.g. 100%" class="w-full"
                                        v-model="choice.wc.setup['max-width']" />
                                </div>
                            </div>
                        </div>
                    </div>
                </SlickItem>
            </SlickList>
            <template v-for="(choice, index) in add_edit_form.choices" :key="choice.CUUID ?? ('idx-tmp-' + index)">
            </template>
        </div>
        <div class="col-span-12" v-show="!showRowTextArea && !showRangeArea">
            <!-- <s4-input color="ssrGray" class="w-full mb-1" placeholder="Input New Choice"
                v-model="new_choice"></s4-input> -->

            <TE2 ref="new_choice_elem" placeholder="Input New Choice" :modelValue="new_choice"
                @update:modelValue="(val) => { new_choice = val }"></TE2>


        </div>
        <div class="grid grid-cols-[170px_1fr] gap-2 w-full col-span-12"
            :class="{ 'grid-cols-[1fr]': showRowTextArea }">
            <button v-show="!showRowTextArea && !showRangeArea" :theme="'ssrAddColRow'"
                @click="showRowTextArea = !showRowTextArea"
                class="inline-flex w-full items-center px-2 py-1 mb-2 bg-[var(--mcolor)] hover:[bg-yellow-400] text-white text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
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
            <div class="flex items-start justify-start">
                <button v-if="showRowTextArea == false && showRangeArea == false" :theme="'ssrAddColRow'" color="ssrB"
                    @click="showRange()"
                    class="inline-flex w-[150px] items-center px-2 py-2 mb-2 bg-[var(--mcolor)] hover:[bg-yellow-400] text-white text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                        style="fill: white;">
                        <path d="M19 11h-6V5h-2v6H5v2h6v6h2v-6h6z"></path>
                    </svg>

                    <span class="transition-all duration-300 ease-in-out hover:text-white text-white">
                        Add Range Inputs
                    </span>
                </button>

                <div v-show="showRowTextArea" class="w-full grid grid-cols-2 gap-1">
                    <!-- <s4-text-area color="ssrGray" v-model="rowArea" class="w-full h-40"
                        placeholder="Enter choices separated by new line..."></s4-text-area> -->
                    <div>
                        <jet-label>Enter Choices</jet-label>
                        <TE2 ref="MultipleInputElem" placeholder="Enter choices separated by new line..."
                            :modelValue="rowArea" height="158px" @update:modelValue="(val) => { rowArea = val }"></TE2>
                    </div>
                    <div>
                        <jet-label>Enter Equivalent</jet-label>
                        <s4-text-area :filter="true" color="ssrGray" v-model="equivalentArea" class="w-full h-40"
                            placeholder="Enter equivalent separated by new line..."></s4-text-area>

                    </div>


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
                            class="py-1 px-2 mt-2 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg"
                            @click="generateRange()" :disabled="typeof range1 === 'string' ||
                                typeof range2 === 'string' ||
                                range1 > range2
                                ">
                            Generate Range
                        </button>

                        <s4-button :theme="'ssrAddColRow'"
                            class="py-1 px-2 ml-1 mt-2 bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-125 hover:bg-transparent"
                            @click="cancelRange()">Cancel</s4-button>


                    </div>

                </div>


            </div>
            <!-- <s4-button
            v-if="showRowTextArea == false && showRangeArea == false"
            :theme="'ssrAddColRow'"
            class="py-1 px-5 ml-8 mb-10 w-32"
            color="ssrB"
            @click="showRange()"
        >
            Add Range Input
        </s4-button> -->
        </div>
        <div class="col-span-12">


            <div class="flex mb-5" v-show="showRowTextArea">
                <s4-button :theme="'ssrAddColRow'"
                    class="py-1 px-2 mt-2 !bg-[var(--mcolor)] hover:!bg-[var(--mcolor2)] text-white text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg"
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


    <div class="mt-4 grid grid-cols-12 max-xl:!grid-cols-6 gap-x-4 gap-y-1"
        :set="helpers().initProperty(add_edit_form.setup, 'textSelection')">
        <div class="col-span-6">

            <div class="grid grid-cols-6 gap-1 col-span-6">
                <div class="col-span-6">
                    <label class="flex items-center cursor-pointer w-[110px]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.required
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Required</span>
                        <Tooltip :imgSrc="question" preset="Required">
                        </Tooltip>
                    </label>

                </div>
                <div class="col-span-6">
                    <label class="items-center cursor-pointer">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.enablePoint
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Enable Scoring</span>
                    </label>
                </div>

                <div class="col-span-6">
                    <label class="items-center cursor-pointer mt-2 w-full">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.isDropdown" />
                        <span class="ml-2 text-sm text-gray-600 select-none">Dropdown</span>
                    </label>
                </div>


                <div class="col-span-2 flex flex-col gap-2 pl-6" v-if="add_edit_form.setup.isDropdown">
                    <jet-label>Placeholder</jet-label>
                    <!-- <s4-input type="text" color="ssrGray" class="w-full mb-1"
                            :disabled="!add_edit_form.setup.isDropdown" placeholder="e.g. Select choice"
                            v-model="add_edit_form.setup.placeholder">
                        </s4-input> -->
                    <TE2 placeholder="e.g. Select choice" :modelValue="add_edit_form.setup.placeholder"
                        @update:modelValue="(val) => { add_edit_form.setup.placeholder = val }"></TE2>
                </div>

                <!-- <div class="col-span-6 w-full h-[45px] pl-6" v-if="add_edit_form.setup.validate.required">
                    <jet-label>Error Message</jet-label>
                    <s4-input color="ssrGray" class=" mb-1 ml-5"
                        v-model="add_edit_form.setup.validate.errorMsg">
                    </s4-input>
                    <TE2 placeholder="" :modelValue="add_edit_form.setup.validate.errorMsg"
                        @update:modelValue="(val) => { add_edit_form.setup.validate.errorMsg = val }"></TE2>

                </div> -->
                <div class="col-span-6">
                    <label class="items-center cursor-pointer flex max-w-[160px]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.randomChoices
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Random Choices</span>
                        <Tooltip :imgSrc="question" preset="RandomChoices">
                        </Tooltip>
                    </label>
                </div>
                <div class="col-span-6 pl-6" v-if="add_edit_form.setup.validate.randomChoices">
                    <vue-form-multiselect
                        placeholder="Click to select choices"
                        :options="randomChoices.random"
                        v-model="add_edit_form.setup.randomChoices"
                        @tag-removed="handleRandomChoicesTagRemoved"
                    />
                </div>
                <div class="col-span-6">
                    <label class="flex items-center cursor-pointer">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.selectMultiple" />
                        <span class="ml-2 text-sm text-gray-600 select-none">Apply Multiple Selection</span>
                    </label>
                </div>

                <div class="col-span-6" v-if="add_edit_form.choices?.some(c => c?.wc && [1, 9].includes(Number(c?.wc?.setup?.validate?.type)))"
                    :set="helpers().initProperty(add_edit_form.setup, 'detectGibberish', 0)">
                    <label class="items-center cursor-pointer flex max-w-[203px]">
                        <s4-checkbox class="шением-pointer" v-model:checked="add_edit_form.setup.detectGibberish
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Detect Gibberish Text</span>
                        <Tooltip :imgSrc="question" preset="Gibberish">
                        </Tooltip>
                    </label>
                </div>
                <div class="col-span-3 pl-6" v-if="add_edit_form.choices?.some(c => Number(c?.wc?.setup?.validate?.type) === 11)"
                    :set="helpers().initProperty(add_edit_form.setup, 'prependString', '')">
                    <span class="text-sm text-gray-600 select-none">Before Textbox</span>
                    <TE2 color="ssrGray" class="w-[100%] mb-1" placeholder="Enter text"
                        :modelValue="add_edit_form.setup.prependString" @update:modelValue="
                            (val) => {
                                add_edit_form.setup.prependString = val;
                            }
                        " @mousedown.stop></TE2>
                </div>
                <div class="col-span-3" v-if="add_edit_form.choices?.some(c => Number(c?.wc?.setup?.validate?.type) === 11)"
                    :set="helpers().initProperty(add_edit_form.setup, 'appendString', '')">
                    <span class="text-sm text-gray-600 select-none">After Textbox</span>
                    <TE2 color="ssrGray" class="w-[100%] mb-1" placeholder="Enter text"
                        :modelValue="add_edit_form.setup.appendString" @update:modelValue="
                            (val) => {
                                add_edit_form.setup.appendString = val;
                            }
                        " @mousedown.stop></TE2>
                </div>
                <div class="col-span-6" v-if="add_edit_form.choices?.some(c => Number(c?.wc?.setup?.validate?.type) === 11)"
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

        <!-- Edit Columns & Edit Layout Width (top-right, before multi-select row) -->
        <div class="col-span-6">
            <div class="grid grid-cols-6 gap-1 col-span-6">

                <div class="col-span-6"
                    :set="helpers().initProperty(add_edit_form.setup.textSelection, 'layout_switch', 0)">
                    <label class="items-center cursor-pointer">
                        <s4-checkbox class="cursor-pointer"
                            v-model:checked="add_edit_form.setup.textSelection['layout_switch']"
                            :disabled="add_edit_form.setup.isDropdown" />
                        <span class="ml-2 text-sm select-none"
                            :class="add_edit_form.setup.isDropdown ? 'text-gray-300' : 'text-gray-600'">Edit Display Type</span>
                    </label>
                </div>
                <div class="col-span-6 w-full grid grid-cols-3 gap-1 pl-6"
                    v-if="add_edit_form.setup.textSelection['layout_switch']">

                    <div class="flex flex-col gap-2">
                        <div :set="helpers().initProperty(add_edit_form.setup.textSelection, 'grid', '')">
                            <jet-label>Columns (Desktop)</jet-label>
                            <s4-input type="number" color="ssrGray" class="w-full"
                                placeholder="Input number of columns"
                                :modelValue="add_edit_form.setup.textSelection['grid']" @update:modelValue="
                                    (res) => {
                                        add_edit_form.setup.textSelection['grid'] = res;
                                    }
                                ">
                            </s4-input>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <div :set="helpers().initProperty(add_edit_form.setup.textSelection, 'grid_tab', '')">
                            <jet-label>Columns (Tablet)</jet-label>
                            <s4-input type="number" color="ssrGray" class="w-full"
                                placeholder="Input number of columns"
                                :modelValue="add_edit_form.setup.textSelection['grid_tab']" @update:modelValue="
                                    (res) => {
                                        add_edit_form.setup.textSelection['grid_tab'] = res;
                                    }
                                ">
                            </s4-input>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <div :set="helpers().initProperty(add_edit_form.setup.textSelection, 'grid_mobile', '')">
                            <jet-label>Columns (Mobile)</jet-label>
                            <s4-input type="number" color="ssrGray" class="w-full"
                                placeholder="Input number of columns"
                                :modelValue="add_edit_form.setup.textSelection['grid_mobile']" @update:modelValue="
                                    (res) => {
                                        add_edit_form.setup.textSelection['grid_mobile'] = res;
                                    }
                                ">
                            </s4-input>
                        </div>
                    </div>
                </div>

                <!-- Apply Layout width -->
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
        </div>

        <!-- Apply Multiple Selection controls laid out in a balanced grid -->
        <div class="col-span-12" v-if="add_edit_form.setup.selectMultiple">
            <div class="grid grid-cols-12 gap-2">

                <!-- Left column: Selection Type, then Min/Max, then Error Message -->
                <div class="col-span-3">
                    <div class="flex flex-col gap-2 ml-6">
                        <jet-label>Selection Type</jet-label>
                        <s4-input-select
                            :set="helpers().initProperty(add_edit_form.setup.textSelection, 'multiSelect_type', 1)"
                            class="w-full" color="ssrGray" :lists="[
                                { text: 'Unlimited', value: 1 },
                                { text: 'Range', value: 2 },
                                { text: 'Fixed', value: 3 }
                            ]" v-model="add_edit_form.setup.textSelection.multiSelect_type">
                        </s4-input-select>
                    </div>

                    <!-- Minimum / Maximum Selection directly under Selection Type -->
                    <div class="mt-2 grid grid-cols-2 gap-2"
                        v-if="add_edit_form.setup.textSelection.multiSelect_type == 2 || add_edit_form.setup.textSelection.multiSelect_type == 3">

                        <div class="flex flex-col gap-2"
                            v-if="add_edit_form.setup.textSelection.multiSelect_type == 2">
                            <jet-label>Minimum Selection</jet-label>
                            <s4-input
                                :set="helpers().initProperty(add_edit_form.setup.textSelection, 'multiSelect_min', '1')"
                                type="number" color="ssrGray" class="w-full"
                                v-model="add_edit_form.setup.textSelection.multiSelect_min">
                            </s4-input>
                        </div>

                        <div class="flex flex-col gap-2">
                            <jet-label v-if="add_edit_form.setup.textSelection.multiSelect_type == 2">Maximum
                                Selection</jet-label>
                            <jet-label v-else>Fixed Selection</jet-label>
                            <s4-input
                                :set="helpers().initProperty(add_edit_form.setup.textSelection, 'multiSelect_max', '5')"
                                type="number" color="ssrGray" class="w-full"
                                v-model="add_edit_form.setup.textSelection.multiSelect_max">
                            </s4-input>
                        </div>
                    </div>

                    <!-- Error Message directly under Min/Max -->
                    <div class="mt-2 flex flex-col gap-2"
                        v-if="add_edit_form.setup.textSelection.multiSelect_type == 2">
                        <jet-label class="-mb-1">Error Message</jet-label>
                        <TE2 :set="helpers().initProperty(add_edit_form.setup.textSelection, 'multiSelect_errorMsg_type2', 'Please select between @min_select to @max_select items')"
                            placeholder="e.g. Please select between @min_select to @max_select items"
                            :modelValue="add_edit_form.setup.textSelection.multiSelect_errorMsg_type2"
                            @update:modelValue="(val) => { add_edit_form.setup.textSelection.multiSelect_errorMsg_type2 = val }">
                        </TE2>
                    </div>

                    <div class="mt-2 flex flex-col gap-2"
                        v-if="add_edit_form.setup.textSelection.multiSelect_type == 3">
                        <jet-label class="-mb-1">Error Message</jet-label>
                        <TE2 :set="helpers().initProperty(add_edit_form.setup.textSelection, 'multiSelect_errorMsg_type3', 'You must select @max_select items')"
                            placeholder="e.g. You must select @max_select items"
                            :modelValue="add_edit_form.setup.textSelection.multiSelect_errorMsg_type3"
                            @update:modelValue="(val) => { add_edit_form.setup.textSelection.multiSelect_errorMsg_type3 = val }">
                        </TE2>
                    </div>
                </div>

                <div class="col-span-3 flex flex-col gap-1" v-if="add_edit_form.setup.selectMultiple === 1">
                    <label class="items-center cursor-pointer flex max-w-[160px]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.selectAll
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Apply Select All</span>
                        <Tooltip :imgSrc="question" preset="ApplySelectAll">
                        </Tooltip>
                    </label>
                    <div class="ml-6" v-if="add_edit_form.setup.validate.selectAll">
                        <vue-form-multiselect placeholder="Select an option to auto select all items"
                            :options="randomChoices.select_all" v-model="add_edit_form.setup.selectAll"
                            @tag-removed="handleSelectAllTagRemoved" />
                    </div>
                </div>

                <div class="col-span-3 flex flex-col gap-1" v-if="add_edit_form.setup.selectMultiple === 1">
                    <label class="items-center cursor-pointer flex max-w-[170px]">
                        <s4-checkbox class="cursor-pointer"
                            v-model:checked="add_edit_form.setup.validate.unSelectAll" />
                        <span class="ml-2 text-sm text-gray-600 select-none">Apply Unselect All</span>
                        <Tooltip :imgSrc="question" preset="ApplyUnselectAll">
                        </Tooltip>
                    </label>
                    <div class="ml-6" v-if="add_edit_form.setup.validate.unSelectAll">
                        <vue-form-multiselect placeholder="Select an option to auto unselect all items"
                            :options="randomChoices.unselect_all" v-model="add_edit_form.setup.unSelectAll"
                            @tag-removed="handleUnselectAllTagRemoved" />
                    </div>
                </div>

                <div class="col-span-3 flex flex-col gap-1">
                    <div class="flex items-center">
                        <s4-checkbox class="cursor-pointer"
                            v-model:checked="add_edit_form.setup.displayChoices" />
                        <span class="ml-2 text-sm text-gray-600 select-none">Display Choices by</span>
                        <Tooltip :imgSrc="question" preset="DisplayChoices">
                        </Tooltip>
                    </div>
                    <div class="ml-6" v-if="add_edit_form.setup.displayChoices">
                        <s4-input type="number" color="ssrGray" class="w-full" placeholder="e.g. 5"
                            v-model.number="add_edit_form.setup.itemsPerPage" min="1"
                            :max="add_edit_form.choices.length" />
                    </div>
                </div>

            </div>
        </div>

        <div class="col-span-6">
            <div class="grid grid-cols-6 gap-1">

                <div class="grid grid-cols-6 gap-1 col-span-3">

                    <!-- Apply Image Dimension -->
                    <!-- <div class="col-span-6">
                        <label class="items-center cursor-pointer">
                            <s4-checkbox
                                class="cursor-pointer"
                                v-model:checked="
                                    add_edit_form.setup[
                                        'image_dimension_enabler'
                                    ]
                                "
                            />
                            <span class="ml-2 text-sm text-gray-600 select-none"
                                >Edit Image Dimension</span
                            >
                        </label>
                    </div>
                    <div
                        class="col-span-2"
                        v-if="add_edit_form.setup['image_dimension_enabler']"
                    >
                        <jet-label>Desktop</jet-label>
                        
                        <S4InputSlider2
                            color="ssrGray"
                            class="w-full mt-1"
                            :placeholder="'e.g. 100'"
                            :units="['%', 'vmin', 'vw', 'auto']"
                            :modelValue="
                                add_edit_form.setup['image_dimension'] ??
                                '50vmin'
                            "
                            :allowAuto="true"
                            @update:modelValue="
                                (val) => {
                                    add_edit_form.setup['image_dimension'] =
                                        val;
                                }
                            "
                        >
                        </S4InputSlider2>
                    </div>

                    <div
                        class="col-span-2"
                        v-if="add_edit_form.setup['image_dimension_enabler']"
                    >
                        <jet-label>Tablet</jet-label>
                        

                        <S4InputSlider2
                            color="ssrGray"
                            class="w-full mt-1"
                            :placeholder="'e.g. 100'"
                            :units="['%', 'vmin', 'vw', 'auto']"
                            :modelValue="
                                add_edit_form.setup['image_dimension_tab'] ??
                                '50vmin'
                            "
                            :allowAuto="true"
                            @update:modelValue="
                                (val) => {
                                    add_edit_form.setup['image_dimension_tab'] =
                                        val;
                                }
                            "
                        >
                        </S4InputSlider2>
                    </div>
                    <div
                        class="col-span-2"
                        v-if="add_edit_form.setup['image_dimension_enabler']"
                    >
                        <jet-label>Mobile</jet-label>
                    
                        <S4InputSlider2
                            color="ssrGray"
                            class="w-full mt-1"
                            :placeholder="'e.g. 100'"
                            :units="['%', 'vmin', 'vw', 'auto']"
                            :modelValue="
                                add_edit_form.setup['image_dimension_mobile'] ??
                                '50vmin'
                            "
                            :allowAuto="true"
                            @update:modelValue="
                                (val) => {
                                    add_edit_form.setup[
                                        'image_dimension_mobile'
                                    ] = val;
                                }
                            "
                        >
                        </S4InputSlider2>
                    </div> -->

                    <!-- <div class="col-span-3 mt-1">
                        <jet-label>Item max width:</jet-label> -->
                    <!-- <s4-input
                            type="number"
                            color="ssrGray"
                            class="w-full"
                            placeholder="Enter choice"
                            v-model="add_edit_form.setup.itemMaxWidth"
                        >
                        </s4-input> -->
                    <!-- <S4InputSlider
                            color="ssrGray"
                            :placeholder="'e.g. 100'"
                            :units="['%', 'vmin', 'vw', 'auto']"
                            v-model.number="itemMaxWidthValue"
                            v-model:unitValue="itemMaxWidthUnit"
                            :allowAuto="true"
                            min="0"
                        ></S4InputSlider>
                    </div> -->
                    <!-- <div class="col-span-3 mt-1">
                        <jet-label>Box style size:</jet-label>
                        <s4-input
                            color="ssrGray"
                            class="w-full mb-1"
                            placeholder="e.g. md"
                            v-model="add_edit_form.setup.boxStyle.size"
                        >
                        </s4-input>
                    </div> -->

                    <!-- Apply Layout width (moved above Apply Multiple Selection) -->
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

        <OverwriteModal :modal="modal" @close="modal.show = false" />

    </div>
</template>

<style scoped>
.ssrBT {
    border: none;
    border-radius: 50%;
    width: 2.65rem;
}

.ssrBT:hover {
    border: 1px solid;
    border-radius: 50%;
}

.ssrBT {
    border: none;
    border-radius: 50%;
    width: 2.65rem;
}

.ssrBT:hover {
    border: 1px solid;
    border-radius: 50%;
}

/* Base disabled styling */
button[disabled] {
    pointer-events: none;
    /* Disable interactions */
    opacity: 0.6;
    color: red;
    /* Visual cue for disabled state */
}

/* Prevent browser from needing scroll-blocking touch listeners during drag */
.drag-handle {
    touch-action: none;
    -ms-touch-action: none;
}
</style>
