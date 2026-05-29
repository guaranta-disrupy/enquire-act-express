<script setup>
import JetLabel from "@/Jetstream/Label.vue";
import WarningModal from "../../../../S4/Warning.vue";
import S4Button from "@/S4/Button.vue";
import ImageInput from "@/S4/ImageInput.vue";
import S4Checkbox from "@/S4/Checkbox.vue";
import S4Input from "@/S4/Input.vue";
import S4InputSelect from "@/S4/InputSelect.vue";
import S4GalleryModal from "@/S4/modals/Gallery.vue";
import S4TextArea from "@/S4/TextArea.vue";
import S4InputSlider2 from "@/S4/InputSlider2.vue";
import VueFormMultiselect from "@/S4/vueform/Multiselect.vue";
import BorderSetup from "@/S4/BorderSetup.vue";
import question from "/public/images/question.svg";
import Tooltip from "@/S4/Tooltip.vue";
import helpers from "@/helpers.js";
import idGenerator from "@/idGenerator";

import {
    computed,
    nextTick,
    reactive,
    watch,
    inject,
    ref,
    defineExpose,
    onBeforeMount,
    onMounted,
    onUnmounted,
} from "vue";
import ButtonIconsVue from "../../../../S4/ButtonIcons.vue";
import { SlickList, SlickItem } from "vue-slicksort";
import TE2 from "@/S4/TE2.vue";
// import { set } from "lodash";
const add_edit_form = inject("add_edit_form");
// const $VariableName = inject("$VariableName");
const showRowTextArea = ref(false);
const MultipleInputElem = ref(null);
const rowArea = ref(null);
const imgArea = ref(null);
const equivalentArea = ref(null);

// Local state for rowArea to prevent lag during typing
// TE2 maintains its own internal state, we only sync to rowArea when needed
const rowAreaLocal = ref(null);
let rowAreaUpdateTimer = null;
const handleRowAreaUpdate = (val) => {
    // Update local state immediately for TE2 to stay responsive
    rowAreaLocal.value = val;
    
    // Debounce the reactive update to reduce overhead during rapid typing
    // This prevents Vue from triggering reactivity checks on every keystroke
    if (rowAreaUpdateTimer) {
        clearTimeout(rowAreaUpdateTimer);
    }
    rowAreaUpdateTimer = setTimeout(() => {
        rowArea.value = val;
        rowAreaUpdateTimer = null;
    }, 150); // 150ms debounce - balances responsiveness with performance
};
const imgAreaWarning = ref(""); //for image source warning
const warning = reactive({
    cond: false,
    value_idx: [],
    src_idx: [],
});

// Local error state for image selection choices (not used anymore - showing individual errors)
// const choicesError = ref("");

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
            const el = itemRefs?.name?.[index]?.$el || itemRefs?.name?.[index];
            el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            nextTick(() => { el?.focus?.(); });
        } else if (field === 'src') {
            const el = itemRefs?.src?.[index]?.$el || itemRefs?.src?.[index];
            el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            nextTick(() => { el?.focus?.(); });
        }
    } catch (_) { }
};

const scrollToFirstEmpty = () => {
    const res = validateRequiredFields();
    if (res?.hasError) {
        scrollToField(res.index, res.field);
    }
    return res;
};

// Function to scroll to the first empty choice when validation is triggered
const scrollToFirstEmptyChoice = () => {
    const choicesList = add_edit_form?.choices || [];
    for (let i = 0; i < choicesList.length; i++) {
        const ch = choicesList[i] || {};
        if (!ch.name) {
            // Scroll to the choice input field
            const choiceElement = itemRefs?.name?.[i]?.$el || itemRefs?.name?.[i];
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
            const choiceElement = itemRefs?.name?.[i]?.$el || itemRefs?.name?.[i];
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
    scrollToFirstEmpty,
    scrollToFirstEmptyChoice,
    scrollToField,
});

// Match write-in types with MultipleChoice.vue to avoid type/value conflicts
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
    // { value: 11, text: "Pricing" },
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
    { value: "12-hour", text: "12-Hour (e.g., 2:30 PM)" },
    { value: "24-hour", text: "24-Hour (e.g., 14:30)" },
    { value: "12-hour-seconds", text: "12-Hour with Seconds (e.g., 2:30:45 PM)" },
    { value: "24-hour-seconds", text: "24-Hour with Seconds (e.g., 14:30:45)" },
];

const datetime_formats = [
    { value: "dd-mmm-yyyy hh:mm", text: "dd-mmm-yyyy hh:mm" },
    { value: "dd-mmm-yyyy hh:mm:ss.s", text: "dd-mmm-yyyy hh:mm:ss.s" },
    { value: "dd-mmm-yyyy hh:mm:ss", text: "dd-mmm-yyyy hh:mm:ss" },
    { value: "yyyy-mm-dd hh:mm", text: "yyyy-mm-dd hh:mm" },
    { value: "yyyy-mm-dd hh:mm:ss", text: "yyyy-mm-dd hh:mm:ss" },
    { value: "yyyy-mm-dd hh:mm:ss.s", text: "yyyy-mm-dd hh:mm:ss.s" },
];

const list_type = reactive([
    { value: 1, text: "Grid View" },
    { value: 2, text: "List View" },
]);

// Keep write-in config structure in sync with MultipleChoice.vue
const write_in_choice = reactive({
    enable: 1,
    setup: {
        placeholder: "",
        "max-width": "",
        validate: {
            type: 1, //[Specific Length, Whole Number, Decimal Number, Email Address, Date, Time, Date Time, Pricing]
            min: 1, //For SL, WN, DN
            max: 10, //For SL, WN, DN
            errorMsg: "This question requires an answer",
            dateFormat: "mm-dd-yyyy", // Default date format
            timeFormat: "12-hour", // Default time format
            datetimeFormat: "dd-mmm-yyyy hh:mm", // Default datetime format
            currencyFormat: "USD", // Default currency format (for Pricing)
        },
    },
});

// Use ref instead of reactive for better performance - matches MultipleChoice.vue pattern
const new_choice_elem = ref(null);
const new_choice = ref("");
const new_choice_src = ref("");
const new_choice_score = ref("");
const new_choice_equivalent = ref("");

const itemRefs = reactive({
    name: [],
    src: [],
    score: [],
});

const selected_choice = ref({});
const gallery = reactive({
    show: false,
    index: -1,
});

onBeforeMount(() => {
    findDuplicateIndices(add_edit_form.choices, warning);
    add_edit_form.items = [];
    // Initialize local state for existing choices
    add_edit_form.choices.forEach((choice, index) => {
        if (choice && choice.name) {
            choiceNameLocalState.set(index, choice.name);
        }
    });
    // console.log("tesssss ", add_edit_form.choices)
});

const findDuplicateIndices = (choices, warning) => {
    if (!choices || !Array.isArray(choices)) {
        console.warn("Invalid input: choices must be an array.");
        warning.cond = false;
        warning.value_idx = [];
        warning.src_idx = [];
        return;
    }

    const scores = choices.map((choice) => parseInt(choice?.score, 10)); // Handle potential null/undefined
    const img_src = choices.map((choice) => choice?.src); // Handle potential null/undefined

    const value_duplicates = new Set();
    const src_duplicates = new Set();

    const scoreMap = new Map();
    const srcMap = new Map();

    // Detect duplicate scores
    scores.forEach((score, index) => {
        if (!isNaN(score)) {
            // Ensure valid scores
            if (scoreMap.has(score)) {
                value_duplicates.add(index);
                value_duplicates.add(scoreMap.get(score));
            } else {
                scoreMap.set(score, index);
            }
        }
    });

    // Detect duplicate image sources
    img_src.forEach((source, index) => {
        if (source) {
            // Ensure non-null sources
            if (srcMap.has(source)) {
                src_duplicates.add(index);
                src_duplicates.add(srcMap.get(source));
            } else {
                srcMap.set(source, index);
            }
        }
    });

    // Update the warning object
    warning.cond = value_duplicates.size > 0 || src_duplicates.size > 0;
    warning.value_idx = Array.from(value_duplicates);
    warning.src_idx = Array.from(src_duplicates);

    // console.log("Duplicate Value indices:", warning.value_idx);
    // console.log("Duplicate Src indices:", warning.src_idx);
};

// Debounce timer for duplicate detection
let duplicateCheckTimer = null;

// Optimized watch with debouncing to batch rapid changes
// Skip duplicate checking during bulk operations (length changes > 5 at once)
// Initialize lastLength as undefined to track actual previous state from watcher
let lastLength = undefined;
watch(
    () => add_edit_form.choices?.length,
    (newLength, oldLength) => {
        // Use oldLength from watcher if available, otherwise use tracked lastLength
        const previousLength = oldLength !== undefined ? oldLength : (lastLength !== undefined ? lastLength : (add_edit_form.choices?.length || 0));
        const lengthChange = Math.abs(newLength - previousLength);
        lastLength = newLength;
        
        // Skip duplicate checking for large bulk operations
        if (lengthChange > 5) {
            // For bulk operations, use longer debounce
            if (duplicateCheckTimer) {
                clearTimeout(duplicateCheckTimer);
            }
            duplicateCheckTimer = setTimeout(() => {
                findDuplicateIndices(add_edit_form.choices, warning);
                duplicateCheckTimer = null;
            }, 500); // Longer debounce for bulk operations
            return;
        }
        
        // Clear existing timer
        if (duplicateCheckTimer) {
            clearTimeout(duplicateCheckTimer);
        }
        
        // Debounce duplicate checking - optimized for 10+ choices
        duplicateCheckTimer = setTimeout(() => {
            findDuplicateIndices(add_edit_form.choices, warning);
            duplicateCheckTimer = null;
        }, 150); // 150ms debounce - optimized for smooth performance
    }
);

// Optimized: Use signature-based watch instead of deep watch for better performance
const duplicateSignature = computed(() => {
    if (!add_edit_form.choices || !Array.isArray(add_edit_form.choices)) return '';
    // Create a lightweight signature of only the fields we care about for duplicate detection
    return add_edit_form.choices.map((choice, idx) => 
        `${choice?.score ?? ''}|${choice?.src ?? ''}|${idx}`
    ).join('||');
});

watch(duplicateSignature, () => {
    if (duplicateCheckTimer) {
        clearTimeout(duplicateCheckTimer);
    }
    duplicateCheckTimer = setTimeout(() => {
        findDuplicateIndices(add_edit_form.choices, warning);
        duplicateCheckTimer = null;
    }, 150); // Slightly longer debounce for signature changes
}, { immediate: true });

// Optimized computed property with better performance - optimized for 10+ choices
const randomChoices = computed(() => {
    const choices = add_edit_form.choices;
    if (!choices || choices.length === 0) {
        return { random: [], select_all: [], unselect_all: [] };
    }
    
    const choicesLength = choices.length;
    
    // Pre-allocate arrays with known size for better performance
    const list = {
        random: [],
        select_all: [],
        unselect_all: [],
    };
    
    // Use Set for O(1) lookups instead of array.includes() which is O(n)
    const selectAllSet = new Set(add_edit_form.setup.selectAll || []);
    const unSelectAllSet = new Set(add_edit_form.setup.unSelectAll || []);

    // Use for loop instead of for...in for better performance
    // Cache string operations to reduce overhead
    for (let i = 0; i < choicesLength; i++) {
        const choice = choices[i];
        if (!choice) continue;
        
        const val = choice.index;
        // Build label more efficiently
        let choice_label = "<span>" + val + ". </span>";
        if (choice.src) {
            choice_label += "<img src='" + choice.src + "'/>";
        }
        if (choice.name != null) {
            choice_label += choice.name;
        }
        
        const data = {
            value: val,
            label: choice_label,
        };
        
        // Use Set.has() for O(1) lookup instead of array.includes()
        // Cache lookups to avoid duplicate checks
        const isInSelectAll = selectAllSet.has(val);
        const isInUnSelectAll = unSelectAllSet.has(val);
        
        if (isInSelectAll) {
            list.unselect_all.push({ ...data, disabled: true });
        } else {
            list.unselect_all.push(data);
        }
        
        if (isInUnSelectAll) {
            list.select_all.push({ ...data, disabled: true });
        } else {
            list.select_all.push(data);
        }
        
        list.random.push(data);
    }
    
    return list;
});

// Set default values for layout options
if (!add_edit_form.setup.layout_desktop) {
    add_edit_form.setup.layout_desktop = 1; // Default to "Grid View"
}
if (!add_edit_form.setup.layout_tab) {
    add_edit_form.setup.layout_tab = 1; // Default to "Grid View"
}
if (!add_edit_form.setup.layout_mobile) {
    add_edit_form.setup.layout_mobile = 1; // Default to "Grid View"
}

//this allow to turn off random choices when random choices is not selected
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
        const lengthChange = Math.abs(newLength - oldLength);
        
        if (newLength > oldLength) {
            // If not enabled yet, enable it (another watcher will populate the values)
            if (!add_edit_form.setup.validate.randomChoices) {
                add_edit_form.setup.validate.randomChoices = true;
                return;
            }
            
            // For bulk operations (>5 choices), use longer debounce
            const debounceTime = lengthChange > 5 ? 500 : 150;
            
            // Debounce the sync operation
            if (randomChoicesSyncTimer) clearTimeout(randomChoicesSyncTimer);
            randomChoicesSyncTimer = setTimeout(async () => {
                await nextTick();
                // Validate choices still exist before syncing
                if (add_edit_form.choices?.length > 0 && randomChoices.value?.random) {
                    // Use requestIdleCallback for non-critical updates when available
                    const syncFn = () => {
                        add_edit_form.setup.randomChoices = randomChoices.value.random.map(choice => choice.value);
                        randomChoicesSyncTimer = null;
                    };
                    
                    if (window.requestIdleCallback) {
                        requestIdleCallback(syncFn, { timeout: 1000 });
                    } else {
                        syncFn();
                    }
                } else {
                    randomChoicesSyncTimer = null;
                }
            }, debounceTime);
        } else if (newLength === 0 && oldLength > 0) {
            // When all choices are deleted, clear randomChoices immediately
            if (add_edit_form.setup.randomChoices) {
                add_edit_form.setup.randomChoices = [];
            }
        }
    }
);

// Optimized: Use signature-based watch instead of deep watch for randomChoices sync
const choiceStructureSignature = computed(() => {
    if (!add_edit_form.choices || !Array.isArray(add_edit_form.choices)) return '';
    // Create signature based on structure changes (index, CUUID) - not content changes
    return add_edit_form.choices.map((choice, idx) => 
        `${choice?.index ?? idx}|${choice?.CUUID ?? idx}`
    ).join('||');
});

// Keep Random Choices selection in sync with any changes to choices (renumber, edits, deletes)
// Optimized with signature-based watch and debouncing to avoid excessive updates
// Track previous length using a separate watcher to get accurate oldLength
let lastSignatureLength = undefined;
watch(
    () => add_edit_form.choices?.length,
    (newLength, oldLength) => {
        // Update tracked length when choices length changes
        if (oldLength !== undefined) {
            lastSignatureLength = oldLength;
        } else if (lastSignatureLength === undefined) {
            lastSignatureLength = newLength;
        }
    }
);
watch(choiceStructureSignature, async (newSignature) => {
    if (add_edit_form?.setup?.validate?.randomChoices) {
        // Detect bulk operations by checking signature length change
        const currentLength = add_edit_form.choices?.length || 0;
        const previousLength = lastSignatureLength !== undefined ? lastSignatureLength : currentLength;
        const lengthChange = Math.abs(currentLength - previousLength);
        lastSignatureLength = currentLength;
        
        // Use longer debounce for bulk operations
        const debounceTime = lengthChange > 5 ? 500 : 200;
        
        // Debounce to batch rapid changes
        if (randomChoicesSyncTimer) clearTimeout(randomChoicesSyncTimer);
        randomChoicesSyncTimer = setTimeout(async () => {
            await nextTick();
            // Clear randomChoices if no choices exist, otherwise sync with current choices
            if (add_edit_form.choices?.length === 0) {
                add_edit_form.setup.randomChoices = [];
                randomChoicesSyncTimer = null;
            } else if (add_edit_form.choices?.length > 0 && randomChoices.value?.random) {
                // Use requestIdleCallback for non-critical updates when available (better for 10+ choices)
                const syncFn = () => {
                    add_edit_form.setup.randomChoices = randomChoices.value.random.map((choice) => choice.value);
                    randomChoicesSyncTimer = null;
                };
                
                if (window.requestIdleCallback) {
                    requestIdleCallback(syncFn, { timeout: 1000 });
                } else {
                    syncFn();
                }
            } else {
                randomChoicesSyncTimer = null;
            }
        }, debounceTime); // Debounce for structure changes
    }
});

//this allow to turn off select all when select all is not selected
watch(
    () => add_edit_form.setup.validate.selectAll,
    (newValue) => {
        if (!newValue) {
            add_edit_form.setup.selectAll = [];
        }
    }
);

//this allow to turn off unselect all when unselect all is not selected
watch(
    () => add_edit_form.setup.validate.unSelectAll,
    (newValue) => {
        if (!newValue) {
            add_edit_form.setup.unSelectAll = [];
        }
    }
);

watch(
    () => add_edit_form.setup.selectMultiple,
    (newValue) => {
        if (!newValue) {
            add_edit_form.setup.validate.selectAll = false;
            add_edit_form.setup.validate.unSelectAll = false;
        }
    }
);

const deleteChoice = async (choiceToDelete) => {
    // Resolve the current index from the actual choice object to avoid any index drift
    const index = add_edit_form.choices.indexOf(choiceToDelete);
    if (index === -1) {
        return;
    }
    
    // Clear pending sync timers to prevent stale index access
    if (randomChoicesSyncTimer) {
        clearTimeout(randomChoicesSyncTimer);
        randomChoicesSyncTimer = null;
    }
    if (duplicateCheckTimer) {
        clearTimeout(duplicateCheckTimer);
        duplicateCheckTimer = null;
    }
    
    // Clean up TE2 update timers
    cleanupChoiceTimer(index);
    
    // Reindex remaining timers
    const timersToReindex = new Map();
    const localStateToReindex = new Map();
    
    choiceNameUpdateTimers.forEach((timer, oldIndex) => {
        if (oldIndex > index) {
            timersToReindex.set(oldIndex - 1, timer);
        } else if (oldIndex < index) {
            timersToReindex.set(oldIndex, timer);
        }
    });
    
    choiceNameLocalState.forEach((val, oldIndex) => {
        if (oldIndex > index) {
            localStateToReindex.set(oldIndex - 1, val);
        } else if (oldIndex < index) {
            localStateToReindex.set(oldIndex, val);
        }
    });
    
    choiceNameUpdateTimers.clear();
    choiceNameLocalState.clear();
    timersToReindex.forEach((timer, newIndex) => {
        choiceNameUpdateTimers.set(newIndex, timer);
    });
    localStateToReindex.forEach((val, newIndex) => {
        choiceNameLocalState.set(newIndex, val);
    });
    
    // Get the choice index before deletion to use stable identifier
    const choice = add_edit_form.choices[index];
    if (choice) {
        deleteMultipleSelected("randomChoices", choice.index);
        deleteMultipleSelected("unSelectAll", choice.index);
        deleteMultipleSelected("selectAll", choice.index);
    }

    // Directly splice - no need for reAssignChoices which creates unnecessary reactive wrappers
    add_edit_form.choices.splice(index, 1);
    
    // Clean up any null/undefined/empty object entries that might have been added
    // This prevents validation from failing due to corrupted array entries
    // IMPORTANT: Preserve choices that have translation data (language property) or index
    // to maintain AI translation compatibility
    for (let i = add_edit_form.choices.length - 1; i >= 0; i--) {
        const choice = add_edit_form.choices[i];
        // Remove null, undefined, or completely empty objects
        if (!choice || typeof choice !== 'object') {
            add_edit_form.choices.splice(i, 1);
            continue;
        }
        // Check for meaningful properties
        const hasName = choice.name !== undefined && choice.name !== null;
        const hasSrc = choice.src !== undefined && choice.src !== null;
        const hasIndex = choice.index !== undefined && choice.index !== null;
        const hasLanguage = choice.language && typeof choice.language === 'object' && Object.keys(choice.language).length > 0;
        const hasCUUID = choice.CUUID !== undefined && choice.CUUID !== null;
        
        // Keep choice if it has:
        // - name, src, or index (core properties)
        // - language data (for translations)
        // - CUUID (unique identifier)
        // Only remove if it has NONE of these properties
        if (!hasName && !hasSrc && !hasIndex && !hasLanguage && !hasCUUID) {
            add_edit_form.choices.splice(i, 1);
        }
    }
    
    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'ImageSelection.vue:703',message:'deleteChoice after splice and cleanup',data:{newChoicesLength:add_edit_form.choices.length,remainingChoicesSrc:add_edit_form.choices.map(c=>c?.src),warningCond:warning.cond},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
    // #endregion
    
    // Store the target length before modifying ref arrays
    // This prevents async unmount callbacks from extending arrays with null entries
    const targetLength = add_edit_form.choices.length;
    
    // Clean up and reindex itemRefs to prevent memory leaks
    // Use splice to properly remove the deleted index and shift remaining refs
    // This avoids creating intermediate null entries
    if (index < itemRefs.name.length) {
        itemRefs.name.splice(index, 1);
    }
    if (index < itemRefs.src.length) {
        itemRefs.src.splice(index, 1);
    }
    if (index < itemRefs.score.length) {
        itemRefs.score.splice(index, 1);
    }
    
    // Trim arrays to match actual choices length (remove any trailing nulls or undefined)
    // This ensures arrays stay in sync with choices array
    // IMPORTANT: Do this immediately to prevent async unmount callbacks from extending arrays
    if (itemRefs.name.length > targetLength) {
        itemRefs.name.length = targetLength;
    }
    if (itemRefs.src.length > targetLength) {
        itemRefs.src.length = targetLength;
    }
    if (itemRefs.score.length > targetLength) {
        itemRefs.score.length = targetLength;
    }
    
    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'ImageSelection.vue:733',message:'deleteChoice end - triggering duplicate check',data:{choicesLength:add_edit_form.choices.length,willTriggerDuplicateCheck:true},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
    // #endregion
    
    // Trigger duplicate check immediately after deletion to update warning state
    findDuplicateIndices(add_edit_form.choices, warning);
    
    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'ImageSelection.vue:740',message:'deleteChoice after duplicate check',data:{warningCond:warning.cond,warningSrcIdx:warning.src_idx,warningValueIdx:warning.value_idx},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
    // #endregion
};
const hideChoice = async (index) => {
    add_edit_form.choices[index].hide_name =
        !add_edit_form.choices[index].hide_name;
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
const showGallery = (index) => {
    if (index >= 0) {
        selected_choice.value = add_edit_form.choices[index];
    }
    gallery.index = index;
    gallery.show = true;

    // Emit global event to close other galleries
    window.dispatchEvent(new CustomEvent('gallery-state-change', {
        detail: {
            action: 'open',
            source: 'image-selection',
            type: 'image'
        }
    }))
};
const closeGallery = () => {
    gallery.show = false;
};

//show image as button after selecting
const selectGallery = (el) => {
    const { ImageWithHeaders } = helpers();
    const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;
    const imageUrl = el.file_path
        ? `${fluentBase}/storage/${el.file_path}`
        : ImageWithHeaders(usePage().props.app_url + '/gallery/' + (el.filename || el.file_name));
    if (gallery.index >= 0) {
        add_edit_form.choices[gallery.index].src = imageUrl;
    } else {
        new_choice_src.value = imageUrl;
    }
    gallery.show = false;
};

// Global gallery event listener to close this gallery when others open
const handleGlobalGalleryEvent = (event) => {
    const { action, source, type } = event.detail

    // If another gallery is opening and it's not this component, close this gallery
    if (action === 'open' && source !== 'image-selection' && gallery.show) {
        closeGallery()
    }
}

onMounted(() => {
    // Listen for global gallery events
    window.addEventListener('gallery-state-change', handleGlobalGalleryEvent)
})

onUnmounted(() => {
    // Clean up event listener
    window.removeEventListener('gallery-state-change', handleGlobalGalleryEvent);
    
    // Clean up all pending timers to prevent memory leaks
    if (duplicateCheckTimer) {
        clearTimeout(duplicateCheckTimer);
        duplicateCheckTimer = null;
    }
    if (randomChoicesSyncTimer) {
        clearTimeout(randomChoicesSyncTimer);
        randomChoicesSyncTimer = null;
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
    if (rowAreaUpdateTimer) {
        clearTimeout(rowAreaUpdateTimer);
        rowAreaUpdateTimer = null;
    }
    
    // Clean up all choice name update timers
    choiceNameUpdateTimers.forEach((timer) => {
        clearTimeout(timer);
    });
    choiceNameUpdateTimers.clear();
    choiceNameLocalState.clear();
})

const addWC = (index) => {
    if (add_edit_form.choices[index].enable_wr) {
        add_edit_form.choices[index]["wc"] = _.cloneDeep(write_in_choice);
    } else {
        delete add_edit_form.choices[index].wc;
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

// Accept only full URLs like http(s)://domain/storage/galleries/814/uuid.webp
const IMAGE_SOURCE_REGEX = /^https?:\/\/[^\s/]+\/storage\/galleries\/\d+\/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\.(webp|jpg|jpeg|png|gif)$/;
const isValidImageSourceUrl = (url) => {
    const trimmed = (url || "").trim();
    return trimmed !== "" && IMAGE_SOURCE_REGEX.test(trimmed);
};

const validateImgArea = () => {
    if (!imgArea.value || imgArea.value.trim() === "") {
        imgAreaWarning.value = "Atleast one image source is required.";
        return false;
    }

    const lines = imgArea.value.split("\n");
    let validCount = 0;

    for (let i = 0; i < lines.length; i++) {
        const line = (lines[i] || "").trim();
        if (!line) {
            continue;
        }

        if (!isValidImageSourceUrl(line)) {
            imgAreaWarning.value =
                `Line ${i + 1}: Invalid image source. Use format: http(s)://domain/storage/galleries/123/uuid.webp`;
            return false;
        }

        validCount++;
    }

    if (validCount === 0) {
        imgAreaWarning.value =
            "Atleast one valid image source is required. Use format: http(s)://domain/storage/galleries/123/uuid.webp";
        return false;
    }

    imgAreaWarning.value = "";
    return true;
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

watch(imgArea, (newValue) => {
    if (newValue && newValue.trim() !== "") {
        imgAreaWarning.value = "";
    }

});

const splitForRow = () => {
    if (!validateImgArea()) {
        return;
    }
    if (rowArea.value != null && imgArea.value != null) {
        // Extract choice texts from the rich text editor
        let newRow =
            rowArea.value
                ?.match(/<p>(.*?)<\/p>/g)
                ?.map((p) => p.replace(/<\/?p>/g, "").trim()) || [];
        // Extract image URLs: one URL per non-empty line
        let imgRow = imgArea.value
            .split("\n")
            .map((line) => line.trim())
            .filter((line) => line !== "");
        // Split equivalents by line (plain textarea)
        let newEq = equivalentArea.value?.split("\n") ?? [];

        // Clean up empty rows
        const cleanedRows = newRow.filter(
            (row) =>
                row &&
                row !== "<p><br></p>" &&
                row !== "<br>" &&
                row !== ""
        );

        // We want to support:
        // - 1 choice text + N image URLs => N choices
        // - M choice texts + N image URLs => max(M, N) choices
        const totalChoices = Math.max(
            cleanedRows.length || newRow.length,
            imgRow.length
        );

        // Batch create all choices at once to minimize watcher triggers
        const newChoices = [];
        const startIndex = add_edit_form.choices.length;
        // Calculate starting index/score to match idGenerator.index behavior
        // idGenerator.index returns 1 for empty array, or max + 1 for non-empty
        let currentMaxIndex;
        let currentMaxScore;
        if (add_edit_form.choices.length === 0) {
            // Match idGenerator.index behavior: returns 1 for empty array
            // Initialize to 0 so first increment gives us 1
            currentMaxIndex = 0;
            currentMaxScore = 0;
        } else {
            // Get max values safely - handle case where some choices might be null/undefined
            const safeChoices = add_edit_form.choices.filter(
                (c) => c && (c.index !== undefined || c.score !== undefined)
            );
            const indices = safeChoices
                .map((c) => parseInt(c.index, 10))
                .filter((v) => !isNaN(v));
            const scores = safeChoices
                .map((c) => parseInt(c.score, 10))
                .filter((v) => !isNaN(v));
            // Initialize to max value (not max+1) since we'll increment before using
            currentMaxIndex = indices.length > 0 ? Math.max(...indices) : 0;
            currentMaxScore = scores.length > 0 ? Math.max(...scores) : 0;
        }
        
        for (let index = 0; index < totalChoices; index++) {
            // Use the row at this index if present, otherwise reuse the last non-empty row
            const rowIndex =
                index < newRow.length && newRow[index]
                    ? index
                    : Math.max(cleanedRows.length - 1, 0);
            const row = newRow[rowIndex] || cleanedRows[0] || "";
            const rowHasText =
                row && row !== "<p><br></p>" && row !== "<br>" && row !== "";

            // Map each choice to a specific image URL; if there are fewer URLs than choices,
            // reuse the last URL so we don't create empty src values.
            const imgSrc =
                (imgRow[index] ??
                    imgRow[imgRow.length - 1] ??
                    "").trim();

            // Equivalent text: prefer line at same index, otherwise reuse last non-empty equivalent,
            // and finally fall back to the choice text.
            const eqLine = newEq[index];
            const eqRaw =
                (eqLine && eqLine.trim && eqLine.trim()) ||
                (newEq.length
                    ? newEq[newEq.length - 1]?.trim?.()
                    : undefined);
            const rawEquivalent = eqRaw && eqRaw !== "" ? eqRaw : row;
            const equivalent = htmlToTextWithImageAlt(rawEquivalent);
            if (rowHasText) {
                // Increment first, then use the value
                // This ensures first choice gets index 1 when array is empty
                currentMaxIndex += 1;
                currentMaxScore += 1;
                newChoices.push({
                    name: "<p>" + row + "</p>",
                    enable_wr: 0,
                    src: imgSrc,
                    equivalent,
                    CUUID: idGenerator.UUID("C"),
                    index: currentMaxIndex,
                    score: currentMaxScore,
                    points: 0,
                    checkboxScore: {
                        active: 1,
                        inactive: 0,
                    },
                });
            }
        }
        
        // Batch push all choices at once - triggers watchers only once
        if (newChoices.length > 0) {
            // Temporarily disable duplicate checking for bulk operation
            if (duplicateCheckTimer) {
                clearTimeout(duplicateCheckTimer);
                duplicateCheckTimer = null;
            }
            
            add_edit_form.choices.push(...newChoices);
            
            // Initialize local state for all new choices in batch
            newChoices.forEach((choice, relativeIndex) => {
                const absoluteIndex = startIndex + relativeIndex;
                choiceNameLocalState.set(absoluteIndex, choice.name);
            });
            
            // Re-enable duplicate checking after a delay
            duplicateCheckTimer = setTimeout(() => {
                findDuplicateIndices(add_edit_form.choices, warning);
                duplicateCheckTimer = null;
            }, 300); // Longer debounce for bulk operations
        }
    } else return;

    rowArea.value = '<p><br></p>';
    rowAreaLocal.value = null; // Reset local state after adding
    imgArea.value = null;
    equivalentArea.value = null;
    
    // Clear any pending update timer
    if (rowAreaUpdateTimer) {
        clearTimeout(rowAreaUpdateTimer);
        rowAreaUpdateTimer = null;
    }

    showRowTextArea.value = false;
};

// for image source warning if image source is empty
watch(imgArea, () => {
    imgAreaWarning.value = null;
});

const resetImgAreaWarnings = () => {
    imgAreaWarning.value = "";
    imgArea.value = null;

};

const cancelMultipleInputs = () => {
    showRowTextArea.value = false;
    // Clear all text areas
    rowArea.value = '<p><br></p>';
    rowAreaLocal.value = null; // Reset local state
    imgArea.value = null;
    equivalentArea.value = null;
    // Clear warnings
    imgAreaWarning.value = "";
    // Clear any pending update timer
    if (rowAreaUpdateTimer) {
        clearTimeout(rowAreaUpdateTimer);
        rowAreaUpdateTimer = null;
    }
    // Force clear the TE2 component if it has a forceClear method
    if (MultipleInputElem.value && MultipleInputElem.value.forceClear) {
        MultipleInputElem.value.forceClear();
    }
};
// Optimized: Watch string ref directly instead of reactive object with deep watch
watch(new_choice, async (val) => {
    if (val !== "" && val !== "<p><br></p>") {
        // Safely compute next index and score (handle null/undefined choices)
        const safeChoices = add_edit_form.choices.filter(
            (c) => c && (c.index !== undefined || c.score !== undefined)
        );
        const indices = safeChoices
            .map((c) => parseInt(c.index, 10))
            .filter((v) => !isNaN(v));
        const scores = safeChoices
            .map((c) => parseInt(c.score, 10))
            .filter((v) => !isNaN(v));
        const nextIndex = (indices.length > 0 ? Math.max(...indices) : 0) + 1;
        const nextScore = new_choice_score.value
            ? Number(new_choice_score.value)
            : (scores.length > 0 ? Math.max(...scores) : 0) + 1;

        add_edit_form.choices.push({
            name: val,
            equivalent: new_choice_equivalent.value || "",
            src: new_choice_src.value || "",
            CUUID: idGenerator.UUID("C"),
            index: nextIndex,
            score: nextScore,
            points: 0,
            checkboxScore: {
                active: 1,
                inactive: 0,
            },
            enable_wr: 0,
            hide_name: false,
        });
        // Initialize local state for the new choice
        const newIndex = add_edit_form.choices.length - 1;
        choiceNameLocalState.set(newIndex, val);
        await nextTick();
        itemRefs.name[newIndex]?.focus();
        new_choice.value = "";
        new_choice_src.value = "";
        new_choice_score.value = "";
        new_choice_equivalent.value = "";
        if (new_choice_elem.value && new_choice_elem.value.forceClear) {
            new_choice_elem.value.forceClear();
        }
    }
});

// Watch for image source or score changes to add choice (only if name is empty)
watch([new_choice_src, new_choice_score], async ([src, score]) => {
    // Only add if name is empty (to avoid duplicate additions)
    if (new_choice.value === "" || new_choice.value === "<p><br></p>") {
        if ((src && src !== "") || (score && score !== "")) {
            // Safely compute next index and score (handle null/undefined choices)
            const safeChoices = add_edit_form.choices.filter(
                (c) => c && (c.index !== undefined || c.score !== undefined)
            );
            const indices = safeChoices
                .map((c) => parseInt(c.index, 10))
                .filter((v) => !isNaN(v));
            const scores = safeChoices
                .map((c) => parseInt(c.score, 10))
                .filter((v) => !isNaN(v));
            const nextIndex = (indices.length > 0 ? Math.max(...indices) : 0) + 1;
            const nextScore = score
                ? Number(score)
                : (scores.length > 0 ? Math.max(...scores) : 0) + 1;

            const newIndex = add_edit_form.choices.length;
            add_edit_form.choices.push({
                name: "<p><br></p>",
                equivalent: new_choice_equivalent.value || "",
                src: src || "",
                CUUID: idGenerator.UUID("C"),
                index: nextIndex,
                score: nextScore,
                points: 0,
                checkboxScore: {
                    active: 1,
                    inactive: 0,
                },
                enable_wr: 0,
                hide_name: false,
            });
            // Initialize local state for the new choice
            choiceNameLocalState.set(newIndex, "<p><br></p>");
            await nextTick();
            if (src && src !== "") {
                itemRefs.src[newIndex]?.focus();
            } else if (score && score !== "") {
                itemRefs.score[newIndex]?.focus();
            }
            new_choice.value = "";
            new_choice_src.value = "";
            new_choice_score.value = "";
            new_choice_equivalent.value = "";
            if (new_choice_elem.value && new_choice_elem.value.forceClear) {
                new_choice_elem.value.forceClear();
            }
        }
    }
});

//for deleting all choices
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
    
    // Clear all TE2 update timers - optimized for bulk operations
    choiceNameUpdateTimers.forEach((timer) => clearTimeout(timer));
    choiceNameUpdateTimers.clear();
    
    // Flush any pending updates before clearing - batch operation
    if (choiceNameLocalState.size > 0) {
        const updates = [];
        choiceNameLocalState.forEach((val, index) => {
            if (add_edit_form.choices[index]) {
                updates.push({ index, val });
            }
        });
        // Batch apply updates
        updates.forEach(({ index, val }) => {
            add_edit_form.choices[index].name = val;
        });
    }
    choiceNameLocalState.clear();
    
    // Clear all itemRefs to prevent memory leaks - optimized: direct array clearing
    // Directly set length to 0 for better performance than forEach
    itemRefs.name.length = 0;
    itemRefs.src.length = 0;
    itemRefs.score.length = 0;
    
    // Clear choices array - use splice for better performance with reactive arrays
    add_edit_form.choices.splice(0, add_edit_form.choices.length);
    
    // Reset length tracking to prevent stale state
    lastSignatureLength = 0;
    
    // Explicitly clear randomChoices, selectAll, and unSelectAll arrays since there are no choices
    // Use direct assignment for better performance
    add_edit_form.setup.randomChoices = [];
    add_edit_form.setup.selectAll = [];
    add_edit_form.setup.unSelectAll = [];
    
    showDeleteAllModal.value = false;
};

//hide all labels

// const hideAllLabels = ref(false);

// const toggleHideLabels = () => {
//     hideAllLabels.value = !hideAllLabels.value;
// };

// const hideAllLabels = () => {
//     add_edit_form.choices.forEach((choice) => {
//         choice.hide_name = true;
//     });
// };

// const unhideAllLabels = () => {
//     add_edit_form.choices.forEach((choice) => {
//         choice.hide_name = false;
//     });
// };

//hide all labels
const hideLabels = computed(() => {
    if (!add_edit_form.choices || add_edit_form.choices.length === 0) {
        return false;
    }
    // When all choice labels are hidden, treat the global state as hidden
    return add_edit_form.choices.every((choice) => choice && choice.hide_name);
});

const toggleHideLabels = () => {
    // If all labels are currently hidden (whether by this button or individually),
    // clicking should unhide them; otherwise, hide them all.
    const shouldHide = !hideLabels.value;
    add_edit_form.choices.forEach((choice) => {
        if (choice) {
            choice.hide_name = shouldHide;
        }
    });
};

// Debounced update timers for TE2 components to reduce reactivity overhead
const choiceNameUpdateTimers = new Map();
const choiceNameLocalState = new Map(); // Local state for immediate UI updates

// Optimized handler for choice name updates with debouncing
// Updates local state immediately for responsive UX, but debounces parent updates
const handleChoiceNameUpdate = (index, val) => {
    // Update local state immediately for responsive UI
    choiceNameLocalState.set(index, val);
    
    // Clear existing timer for this choice
    if (choiceNameUpdateTimers.has(index)) {
        clearTimeout(choiceNameUpdateTimers.get(index));
    }
    
    // Debounce the parent update to reduce reactivity overhead
    const timer = setTimeout(() => {
        if (add_edit_form.choices[index]) {
            add_edit_form.choices[index].name = choiceNameLocalState.get(index) || val;
        }
        choiceNameUpdateTimers.delete(index);
    }, 150); // 150ms debounce - optimized for smooth performance with 10+ choices
    
    choiceNameUpdateTimers.set(index, timer);
};

// Clean up timers when choice is deleted
const cleanupChoiceTimer = (index) => {
    if (choiceNameUpdateTimers.has(index)) {
        clearTimeout(choiceNameUpdateTimers.get(index));
        choiceNameUpdateTimers.delete(index);
    }
    choiceNameLocalState.delete(index);
};

// Handle X SVG click in Random Choices multiselect
const handleRandomChoicesTagRemoved = (option, event) => {
    // This function is kept for potential future use
    // The actual logic is now handled by the watcher above
};

// Handle X SVG click in Select All multiselect
const handleSelectAllTagRemoved = (option, event) => {
    // This function is kept for potential future use
    // The actual logic is now handled by the watcher above
};

// Handle X SVG click in Unselect All multiselect
const handleUnselectAllTagRemoved = (option, event) => {
    // This function is kept for potential future use
    // The actual logic is now handled by the watcher above
};

const gridClass = computed(() => {
    if (add_edit_form.setup.enablePoint === 1 && add_edit_form.setup.selectMultiple === 1) {
        return 'grid-cols-[auto_6fr_4fr_4fr_1fr_1fr_60px_40px_40px_40px_40px] gap-1 w-full items-center';
    }
    if (add_edit_form.setup.enablePoint === 1) {
        return 'grid-cols-[auto_7fr_7fr_7fr_1fr_60px_40px_40px_40px_40px] gap-1 w-full items-center';
    }
    if (add_edit_form.setup.selectMultiple) {
        return 'grid-cols-[auto_1fr_1fr_1fr_70px_70px_40px_40px_40px_40px]';
    }
    return 'grid-cols-[auto_1fr_1fr_1fr_60px_40px_40px_40px_40px] gap-1 w-full items-center';
});

// Ensure boxStyle exists before using its properties
if (!add_edit_form.setup.boxStyle) {
    add_edit_form.setup.boxStyle = {};
}
if (add_edit_form.setup.boxStyle['max-width_enabler'] === undefined) {
    add_edit_form.setup.boxStyle['max-width_enabler'] = true;
}

// Ensure imageSelection config exists before using its properties
if (!add_edit_form.setup.imageSelection) {
    add_edit_form.setup.imageSelection = {};
}
if (add_edit_form.setup.imageSelection['image_dimension_switch'] === undefined) {
    add_edit_form.setup.imageSelection['image_dimension_switch'] = true;
}

if (add_edit_form.setup.imageSelection['layout_switch'] === undefined) {
    add_edit_form.setup.imageSelection['layout_switch'] = true;
}


</script>

<template>
    <div class="flex justify-end outline-offset-2 outline-sky-500 focus:outline-2"
        v-if="add_edit_form.choices.length > 0">
        <button @click="showDeleteAllModal = true"
            class="inline-flex items-center px-4 py-2 mb-2 bg-transparent text-red-500 text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-[1.1]">
            <svg xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 mr-2 transition-all duration-300 ease-in-out hover:text-red-600" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>

            <span>
                Delete All Choices
            </span>
        </button>
        <button @click="toggleHideLabels" :class="[
            'inline-flex items-center pr-1 py-2 mb-2 text-black text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-[1.1]',
            hideLabels ? ' pl-1 bg-[var(--mcolor)]' : ''
        ]">
            <svg v-if="hideLabels" width="20" height="20" viewBox="0 0 2407 2579" fill="none"
                xmlns="http://www.w3.org/2000/svg" class="transition-all duration-300 ease-in-out hover:text-red-600">
                <path
                    d="M1201.44 564.52C835.186 564.52 459.483 791.655 163.715 1245.93C155.272 1259.04 150.646 1274.53 150.42 1290.46C150.194 1306.38 154.379 1322.02 162.446 1335.4C389.7 1716.56 760.375 2014.64 1201.44 2014.64C1637.72 2014.64 2016.01 1715.66 2243.68 1333.64C2251.56 1320.52 2255.75 1305.26 2255.75 1289.66C2255.75 1274.06 2251.56 1258.79 2243.68 1245.68C2015.49 868.039 1634.43 564.52 1201.44 564.52Z"
                    stroke="currentColor" stroke-width="250" stroke-linecap="round" stroke-linejoin="round" />
                <path
                    d="M1203.04 1692.39C1410.67 1692.39 1578.98 1512.05 1578.98 1289.58C1578.98 1067.11 1410.67 886.77 1203.04 886.77C995.416 886.77 827.103 1067.11 827.103 1289.58C827.103 1512.05 995.416 1692.39 1203.04 1692.39Z"
                    stroke="currentColor" stroke-width="250" stroke-miterlimit="10" />
                <!-- Slash Line -->
                <path d="M300 300L2100 2280" stroke="currentColor" stroke-width="250" stroke-linecap="round" />
            </svg>
            <svg v-else width="20px" height="20px" viewBox="0 0 2407 2579" fill="none"
                xmlns="http://www.w3.org/2000/svg" class="transition-all duration-300 ease-in-out hover:text-red-600">
                <path
                    d="M1201.44 564.52C835.186 564.52 459.483 791.655 163.715 1245.93C155.272 1259.04 150.646 1274.53 150.42 1290.46C150.194 1306.38 154.379 1322.02 162.446 1335.4C389.7 1716.56 760.375 2014.64 1201.44 2014.64C1637.72 2014.64 2016.01 1715.66 2243.68 1333.64C2251.56 1320.52 2255.75 1305.26 2255.75 1289.66C2255.75 1274.06 2251.56 1258.79 2243.68 1245.68C2015.49 868.039 1634.43 564.52 1201.44 564.52Z"
                    stroke="black" stroke-width="250" stroke-linecap="round" stroke-linejoin="round" />
                <path
                    d="M1203.04 1692.39C1410.67 1692.39 1578.98 1512.05 1578.98 1289.58C1578.98 1067.11 1410.67 886.77 1203.04 886.77C995.416 886.77 827.103 1067.11 827.103 1289.58C827.103 1512.05 995.416 1692.39 1203.04 1692.39Z"
                    stroke="black" stroke-width="250" stroke-miterlimit="10" />
            </svg>
            <span class="transition-all duration-300 ease-in-out ml-1">
                {{ hideLabels ? "Unhide All Labels" : "Hide All Labels" }}
            </span>
        </button>
    </div>

    <div class="grid grid-cols-12 gap-1 mb-5">
        <div class="col-span-12" v-if="add_edit_form.choices.length > 0">
            <div class="grid grid-cols-12 gap-1">
                <div class="col-span-12" v-if="add_edit_form.setup.selectMultiple !== 1">
                    <div class="grid grid-cols-[40px_1fr_1fr_1fr_60px_40px_40px_40px_40px] gap-1"
                        :class="{ 'grid grid-cols-[40px_7fr_7fr_7fr_58px_60px_40px_40px_40px_40px] gap-1': (add_edit_form.setup.enablePoint === 1) }">
                        <!-- <jet-label class="font-bold flex"></jet-label> -->
                        <jet-label class="font-bold flex">Index</jet-label>
                        <jet-label class="font-bold float">Choices</jet-label>
                        <jet-label class="font-bold"> Image source </jet-label>
                        <jet-label class="font-bold"> Equivalent </jet-label>
                        <jet-label class="font-bold flex w-[23%] mr-12" :class="{
                            'text-red-500':
                                warning.cond &&
                                warning.value_idx.length > 0,
                        }">
                            Value
                        </jet-label>
                        <jet-label class="font-bold" v-if="add_edit_form.setup.enablePoint === 1"> Points </jet-label>
                    </div>
                </div>
                <div class="col-span-12" v-if="add_edit_form.setup.selectMultiple === 1">
                    <div class="grid grid-cols-[40px_1fr_1fr_1fr_70px_70px_40px_40px_40px_40px] gap-1"
                        :class="{ 'grid grid-cols-[40px_6fr_4fr_4fr_68px_70px_50px_110px_20px_20px_20px] gap-1': (add_edit_form.setup.enablePoint === 1) }">
                        <!-- <jet-label class="font-bold flex"></jet-label> -->
                        <jet-label class="font-bold flex">Index</jet-label>
                        <jet-label class="font-bold float">Choices</jet-label>
                        <jet-label class="font-bold flex" :class="{
                            'text-red-500':
                                warning.cond && warning.src_idx.length > 0,
                        }">
                            Image source
                        </jet-label>
                        <jet-label class="font-bold"> Equivalent </jet-label>

                        <jet-label class="text-xs font-bold flex w-[17%] mr-10" :class="{
                            'text-red-500':
                                warning.cond &&
                                warning.value_idx.length > 0,
                        }">
                            Selected Value
                        </jet-label>
                        <jet-label class="text-xs font-bold flex w-[18%] mr-10">
                            Unselected Value
                        </jet-label>
                        <jet-label class="font-bold" v-if="add_edit_form.setup.enablePoint === 1"> Points </jet-label>
                    </div>
                </div>
            </div>

            <SlickList axis="y" v-model:list="add_edit_form.choices">
            <SlickItem v-for="(choice, index) in add_edit_form.choices"
                :key="(choice && choice.CUUID) ? choice.CUUID : ('idx-' + index)" :index="index"
                    class="draggable-list select-none">
                    <div class="w-full grid grid-cols-12 gap-1 mb-3" v-if="choice">
                        <div class="col-span-12 w-full">
                            <!-- <div class="flex gap-1">
                                <jet-label class="font-bold w-12" v-if="index == 0">Order:</jet-label>
                                <jet-label class="font-bold w-12" v-if="index == 0">Index:</jet-label>
                                <jet-label class="font-bold w-full" v-if="index == 0">Choices:</jet-label>
                                <jet-label class="font-bold w-full"
                                    :class="{ 'text-red-500': warning.cond && warning.value_idx.length > 0 }" v-if="
                                        index == 0 &&
                                        add_edit_form.setup.selectMultiple !== 1
                                    ">Value:</jet-label>
                                <jet-label class="font-bold w-full" v-if="
                                    index == 0 &&
                                    add_edit_form.setup.selectMultiple === 1
                                ">Active Value:</jet-label>
                                <jet-label class="font-bold w-full" v-if="
                                    index == 0 &&
                                    add_edit_form.setup.selectMultiple === 1
                                ">Inactive Value:</jet-label>
                                <jet-label class="font-bold w-full"
                                    :class="{ 'text-red-500': warning.cond && warning.src_idx.length > 0 }"
                                    v-if="index == 0">Image source:</jet-label>
                                <div class="flex" :class="{ 'mt-2': index != 0 }"></div> 
                            </div>-->
                            <div class="grid grid-cols-[auto_1fr_1fr_1fr_60px_40px_40px_40px_40px] gap-1 w-full items-center"
                                :class="gridClass">
                                <!-- <span class="w-[200px]">{{ index + 1 }}</span> -->
                                <!-- <span class="w-[160px]">{{
                                    "#" +
                                    (add_edit_form.choices[index]?.index
                                        ? add_edit_form.choices[index]?.index
                                        : index + 1)
                                }}</span> -->
                                <div class="min-w-[40px] text-center">
                                    <span>
                                        {{ choice.index }}
                                    </span>
                                </div>
                                <div class="flex gap-1">
                                    <svg v-if="
                                        (warning.cond &&
                                            warning.value_idx.includes(
                                                index
                                            )) ||
                                        warning.src_idx.includes(index)
                                    " xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2.5" stroke="red" class="w-6">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"
                                            fill="#fff7f6" />
                                    </svg>
                                    <!-- <s4-input color="ssrGray" class="w-full" placeholder="Enter choice"
                                        v-model="add_edit_form.choices[index].name" :ref="(el) => {
                                            itemRefs.name[index] = el;
                                        }
                                            ">
                                    </s4-input> -->

                                    <TE2 
                                        placeholder="Enter choice" 
                                        :modelValue="choiceNameLocalState.has(index) ? choiceNameLocalState.get(index) : add_edit_form.choices[index].name"
                                        @update:modelValue="(val) => handleChoiceNameUpdate(index, val)" 
                                        :ref="(el) => {
                                            // Handle ref updates safely to prevent race conditions
                                            // When el is null (unmount), check if we should nullify
                                            if (el === null) {
                                                // Only nullify if:
                                                // 1. Index is beyond current choices length (component truly removed), AND
                                                // 2. Index is within or at the current ref array length (not already trimmed)
                                                // This prevents async callbacks from extending trimmed arrays with null entries
                                                const currentLength = add_edit_form.choices.length;
                                                const refLength = itemRefs.name.length;
                                                if (index >= currentLength && index < refLength) {
                                                    itemRefs.name[index] = null;
                                                }
                                                // If index >= refLength, the array was already trimmed, so don't extend it
                                            } else {
                                                // Always update when we get a valid ref
                                                itemRefs.name[index] = el;
                                            }
                                        }" 
                                        @mousedown.stop
                                        @blur="() => {
                                            // Flush pending updates on blur to ensure data is saved
                                            if (choiceNameUpdateTimers.has(index)) {
                                                clearTimeout(choiceNameUpdateTimers.get(index));
                                                if (add_edit_form.choices[index] && choiceNameLocalState.has(index)) {
                                                    add_edit_form.choices[index].name = choiceNameLocalState.get(index);
                                                }
                                                choiceNameUpdateTimers.delete(index);
                                            }
                                        }"
                                    ></TE2>

                                </div>
                                <ImageInput :ref="(el) => {
                                    // Handle ref updates safely to prevent race conditions
                                    if (el === null) {
                                        // Only nullify if index is beyond current choices length AND within ref array length
                                        // This prevents async callbacks from extending trimmed arrays
                                        const currentLength = add_edit_form.choices.length;
                                        const refLength = itemRefs.src.length;
                                        if (index >= currentLength && index < refLength) {
                                            itemRefs.src[index] = null;
                                        }
                                    } else {
                                        itemRefs.src[index] = el;
                                    }
                                }" :modelValue="add_edit_form.choices[index].src"
                                    @update:modelValue="(val) => { add_edit_form.choices[index].src = val }">
                                </ImageInput>

                                <s4-input color="ssrGray" class="w-full" placeholder="Enter Equivalent"
                                    v-model="add_edit_form.choices[index].equivalent">
                                </s4-input>

                                <s4-input v-if="
                                    add_edit_form.setup.selectMultiple !== 1
                                " color="ssrGray" class="w-[800px]" :class="{
                                    'text-red-500':
                                        warning.cond &&
                                        warning.value_idx.includes(index),
                                }" placeholder="Enter value" v-model="add_edit_form.choices[index].score" :ref="(el) => {
                                    // Handle ref updates safely to prevent race conditions
                                    if (el === null) {
                                        // Only nullify if index is beyond current choices length AND within ref array length
                                        // This prevents async callbacks from extending trimmed arrays
                                        const currentLength = add_edit_form.choices.length;
                                        const refLength = itemRefs.score.length;
                                        if (index >= currentLength && index < refLength) {
                                            itemRefs.score[index] = null;
                                        }
                                    } else {
                                        itemRefs.score[index] = el;
                                    }
                                }
                                    ">
                                </s4-input>
                                <s4-input type="number" v-if="
                                    add_edit_form.setup.selectMultiple !== 1 && add_edit_form.setup.enablePoint === 1
                                " color="ssrGray" class="w-full !p-[10px]" :class="{
                                    'text-red-500':
                                        warning.cond &&
                                        warning.idx.includes(index),
                                }" placeholder="Enter Points" v-model="add_edit_form.choices[index].points">
                                </s4-input>
                                <s4-input v-if="
                                    add_edit_form.setup.selectMultiple ===
                                    1 &&
                                    add_edit_form.choices[index]
                                        .checkboxScore
                                " color="ssrGray" class="w-[45%]" placeholder="Enter Score" v-model="add_edit_form.choices[index]
                                    .checkboxScore.active
                                    " />

                                <s4-input v-if="
                                    add_edit_form.setup.selectMultiple ===
                                    1 &&
                                    add_edit_form.choices[index]
                                        .checkboxScore
                                " color="ssrGray" class="w-[45%]" placeholder="Enter Score" v-model="add_edit_form.choices[index]
                                    .checkboxScore.inactive
                                    " />
                                <s4-input type="number" v-if="
                                    add_edit_form.setup.selectMultiple === 1 && add_edit_form.setup.enablePoint === 1
                                " color="ssrGray" class="w-full !px-[10px]" :class="{
                                    'text-red-500':
                                        warning.cond &&
                                        warning.idx.includes(index),
                                }" placeholder="Enter Points" v-model.number="add_edit_form.choices[index].points">
                                </s4-input>

                                <div class="relative group">
                                    <ButtonIconsVue icon="delete" @click="deleteChoice(choice)" />
                                    <!-- <span
                                        class="absolute left-1/2 top-[-10px] transform translate-y-[-100%] -translate-x-1/2 mt-2 z-[200] w-full px-1.5 py-1 bg-yellow-500 text-white text-[10px] rounded leading-[1.5] hidden group-hover:block transition-opacity shadow-md">
                                        Delete
                                    </span> -->
                                    <Tooltip label="Delete" hoverWidth="auto" xTransformClass="translate(-50%,-270%)">
                                    </Tooltip>
                                </div>
                                <div class="relative group">
                                    <ButtonIconsVue :icon="add_edit_form.choices[index]
                                        .hide_name
                                        ? 'unhide'
                                        : 'hide'
                                        " @click="hideChoice(index)" />
                                    <!-- <span
                                        class="absolute bottom-[40px] left-1/2 text-center transform -translate-x-1/2 px-1 py-1 text-[10px] text-white bg-yellow-500 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        {{
                                            add_edit_form.choices[index]
                                                .hide_name
                                                ? "Unhide Label"
                                                : "Hide Label"
                                        }}
                                    </span> -->
                                    <Tooltip
                                        :label="add_edit_form.choices[index].hide_name ? 'Unhide Label' : 'Hide Label'"
                                        hoverWidth="auto" xTransformClass="translate(-50%,-200%)">
                                    </Tooltip>
                                </div>
                                <div class="relative group">
                                    <ButtonIconsVue
                                        :icon="add_edit_form.choices[index].enable_wr ? 'en_write' : 'write'" @click="
                                            add_edit_form.choices[index].enable_wr = !add_edit_form.choices[index].enable_wr;
                                        addWC(index);
                                        " />
                                    <!-- <span
                                        class="absolute bottom-[40px] left-1/2 transform -translate-x-1/2 mb-0 px-2 py-1 text-[10px] text-white bg-yellow-500 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        Write
                                    </span> -->
                                    <Tooltip label="Write" hoverWidth="auto" xTransformClass="translate(-50%,-270%)">
                                    </Tooltip>
                                </div>
                                <div class="relative group">
                                    <ButtonIconsVue icon="move" />
                                    <!-- <span
                                        class="absolute bottom-[40px] text-center text-[10px] left-1/2 transform -translate-x-1/2 mb-0 px-1 py-1 text-white bg-yellow-500 bg-opacity-100 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        Hold to drag
                                    </span> -->
                                    <Tooltip label="Hold to drag" hoverWidth="76px"
                                        xTransformClass="translate(-50%,-270%)">
                                    </Tooltip>
                                </div>
                            </div>
                            <!-- <label class="items-center cursor-pointer ml-5">
                                <s4-checkbox
                                    class="cursor-pointer border-ssr-blue4"
                                    v-model:checked="
                                        add_edit_form.choices[index].hide_name
                                    "
                                />
                                <span
                                    class="ml-1 text-xs text-gray-500 select-none"
                                    >Hide Label</span
                                >
                            </label> -->
                        </div>
                        <!-- <div class="col-span-12 -mt-1">
                            <label class="items-center cursor-pointer ml-12">
                                <s4-checkbox
                                    class="cursor-pointer border-ssr-blue4"
                                    v-model:checked="
                                        add_edit_form.choices[index].enable_wr
                                    "
                                    @change="addWC(index)"
                                />
                                <span
                                    class="ml-1 text-xs text-gray-500 select-none"
                                    >Write-in Choice</span
                                >
                            </label>
                        </div> -->
                        <!-- Match write-in layout/spacing to MultipleChoice.vue -->
                        <div class="col-span-12 mb-2" v-if="'wc' in choice">
                            <div class="ml-[110px] flex flex-wrap gap-1">
                                <div class="w-[200px]">
                                    <span class="text-xs text-gray-500">Write-in Type</span>
                                    <s4-input-select
                                        color="ssrGray"
                                        class="w-full"
                                        :lists="wr_types"
                                        v-model.number="choice.wc.setup.validate.type"
                                    >
                                    </s4-input-select>
                                </div>
                                <div
                                    class="w-[170px]"
                                    v-if="
                                        choice.wc.setup.validate.type !== 5 &&
                                        choice.wc.setup.validate.type !== 6 &&
                                        choice.wc.setup.validate.type !== 7
                                    "
                                >
                                    <span class="text-xs text-gray-500">
                                        {{
                                            choice.wc.setup.validate.type === 2 ||
                                            choice.wc.setup.validate.type === 3 ||
                                            choice.wc.setup.validate.type === 11
                                                ? "Minimum Number"
                                                : "Minimum Character"
                                        }}
                                    </span>
                                    <s4-input
                                        color="ssrGray"
                                        type="number"
                                        class="w-full"
                                        v-model.number="choice.wc.setup.validate.min"
                                    ></s4-input>
                                </div>
                                <div
                                    class="w-[170px]"
                                    v-if="
                                        choice.wc.setup.validate.type !== 5 &&
                                        choice.wc.setup.validate.type !== 6 &&
                                        choice.wc.setup.validate.type !== 7
                                    "
                                >
                                    <span class="text-xs text-gray-500">
                                        {{
                                            choice.wc.setup.validate.type === 2 ||
                                            choice.wc.setup.validate.type === 3 ||
                                            choice.wc.setup.validate.type === 11
                                                ? "Maximum Number"
                                                : "Maximum Character"
                                        }}
                                    </span>
                                    <s4-input
                                        color="ssrGray"
                                        type="number"
                                        class="w-full"
                                        v-model.number="choice.wc.setup.validate.max"
                                    ></s4-input>
                                </div>
                                <div class="w-[180px]" v-if="choice.wc.setup.validate.type === 5">
                                    <span class="text-xs text-gray-500">Date Format</span>
                                    <s4-input-select
                                        color="ssrGray"
                                        class="w-full"
                                        :lists="date_formats"
                                        v-model="choice.wc.setup.validate.dateFormat"
                                    >
                                    </s4-input-select>
                                </div>
                                <div class="w-[auto]" v-if="choice.wc.setup.validate.type === 6">
                                    <span class="text-xs text-gray-500">Time Format</span>
                                    <s4-input-select
                                        color="ssrGray"
                                        class="w-full"
                                        :lists="time_formats"
                                        v-model="choice.wc.setup.validate.timeFormat"
                                    >
                                    </s4-input-select>
                                </div>
                                <div class="w-[auto]" v-if="choice.wc.setup.validate.type === 7">
                                    <span class="text-xs text-gray-500">Date & Time Format</span>
                                    <s4-input-select
                                        color="ssrGray"
                                        class="w-full"
                                        :lists="datetime_formats"
                                        v-model="choice.wc.setup.validate.datetimeFormat"
                                    >
                                    </s4-input-select>
                                </div>
                                <div class="w-[350px]">
                                    <span class="text-xs text-gray-500">Placeholder</span>
                                    <s4-input
                                        color="ssrGray"
                                        class="w-full"
                                        v-model="choice.wc.setup.placeholder"
                                    />
                                </div>
                                <div class="w-[170px]">
                                    <span class="text-xs text-gray-500">Set Max Width</span>
                                    <s4-input
                                        color="ssrGray"
                                        placeholder="e.g. 100%"
                                        class="w-full"
                                        v-model="choice.wc.setup['max-width']"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Show validation error for empty choice - only when save is attempted -->
                        <div v-if="showValidationErrors && isChoiceEmpty(choice)" class="col-span-12 -mt-2">
                            <div class="text-red-600 text-sm mt-1 ml-11">This field cannot be left empty.</div>
                        </div>
                    </div>
                </SlickItem>
            </SlickList>
        </div>
        <div class="col-span-12 grid grid-cols-2 gap-1 gap-y-2" v-show="!showRowTextArea">
            <div>
                <!-- <s4-input color="ssrGray" class="w-full" placeholder="Input New Choice"
                    v-model="new_choice"></s4-input> -->
                <TE2 ref="new_choice_elem" placeholder="Input New Choice" :modelValue="new_choice"
                    @update:modelValue="(val) => { new_choice = val }"></TE2>


            </div>

            <div>
                <!-- <div class="flex items-center border border-gray-300 rounded-md overflow-hidden">
                    <s4-input color="ssrGray"
                        class="w-full py-2 px-3 text-sm text-gray-700 focus:ring-1 focus:ring-blue-500 focus:outline-none border-none"
                        placeholder="Image source" v-model="new_choice.src"></s4-input>
                    <div class="w-22 py-2 px-3 text-sm text-gray-700 bg-gray-50 border-l border-gray-300 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                        @click="showGallery(-1)">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 group-hover:scale-125 hover:ease-in duration-300 hover:scale-125 cursor-pointer"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <s4-button color="ssrOrangeIcon" @click="showGallery(-1)"  class="mb-1 ml-1 text-ssr-orange2" style="border:1px solid;">
                        <font-awesome-icon class="fa-w-20 fa-h-20" :icon="faIcon.image" />
                    </s4-button> 
                </div> -->

                <ImageInput :modelValue="new_choice_src" @update:modelValue="(val) => { new_choice_src = val }">
                </ImageInput>

            </div>

            <!-- <div>
                <s4-input color="ssrGray" class="w-full" placeholder="Input Equivalent"
                    :modelValue="new_choice.equivalent" @update:modelValue="(val) => { new_choice.equivalent = val }"></s4-input>
            </div> -->

            <div class="col-span-2">
                <button v-show="showRowTextArea == false" :theme="'ssrAddColRow'"
                    @click="showRowTextArea = !showRowTextArea"
                    class="inline-flex items-center px-2 py-1 mb-2 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
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
            </div>
        </div>
        <!-- <s4-button
            v-show="showRowTextArea == false"
            :theme="'ssrAddColRow'"
            class="py-1 px-3 mb-10 w-32"
            color="ssrB"
            icon = "plus"
            @click="showRowTextArea = !showRowTextArea"
        >
            
        </s4-button> -->

        <div class="col-span-6 grid grid-cols-1" v-if="showRowTextArea">
            <!-- <s4-text-area color="ssrGray" v-show="showRowTextArea" v-model="rowArea" class="w-full h-40"
                placeholder="Enter choices separated by new line..."></s4-text-area> -->
            <div>
                <jet-label>Enter Choices</jet-label>
                <TE2 v-show="showRowTextArea" ref="MultipleInputElem"
                    placeholder="Enter choices separated by new line..." 
                    :modelValue="rowAreaLocal !== null ? rowAreaLocal : rowArea" 
                    height="158px"
                    @update:modelValue="handleRowAreaUpdate"></TE2>
            </div>

        </div>
        <div class="col-span-6 grid grid-cols-2 gap-1" v-if="showRowTextArea">
            <div>
                <jet-label>Enter Image Source</jet-label>
                <s4-text-area color="ssrGray" v-show="showRowTextArea" v-model="imgArea" class="w-full h-40"
                    :filter="true" placeholder="One URL per line, e.g. http://domain/storage/galleries/814/uuid.webp"></s4-text-area>
                <div
                    class="text-sm text-red-600 whitespace-pre-line"
                    v-if="imgAreaWarning"
                >
                    {{ imgAreaWarning }}
                </div>
            </div>


            <div>
                <jet-label>Enter Equivalent</jet-label>
                <s4-text-area :filter="true" color="ssrGray" v-show="showRowTextArea" v-model="equivalentArea"
                    class="w-full h-40" placeholder="Enter equivalent separated by new line..."></s4-text-area>
            </div>

        </div>



        <div class="flex mb-5" v-show="showRowTextArea">
            <s4-button :theme="'ssrAddColRow'"
                class="py-1 px-2 mt-2 !bg-[var(--mcolor)] !hover:bg-[var(--mcolor2)] text-white text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg"
                @click="splitForRow()">Add</s4-button>
            <s4-button :theme="'ssrAddColRow'"
                class="py-1 px-2 ml-1 mt-2 bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-125 hover:bg-transparent"
                @click="cancelMultipleInputs()">Cancel</s4-button>
        </div>
    </div>

    <BorderSetup></BorderSetup>

    <!-- <div class="mt-4 grid grid-cols-12 max-xl:!grid-cols-6 gap-1"> -->
    <div class="mt-4 grid grid-cols-12 max-xl:!grid-cols-6 gap-4"
        :set="helpers().initProperty(add_edit_form.setup, 'imageSelection')">
        <div class="col-span-6">
            <div class="grid grid-cols-6 gap-1 col-span-6">
                <div class="col-span-6">
                    <label class="flex items-center cursor-pointer w-[110px]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.required
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Required</span>
                        <Tooltip :imgSrc="question" preset="Required"></Tooltip>
                    </label>
                </div>
                <div class="col-span-6">
                    <label class="items-center cursor-pointer">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.enablePoint
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Enable Scoring</span>
                    </label>
                </div>
                <!-- <div class="col-span-6 pl-6" v-if="add_edit_form.setup.validate.required">
                    <jet-label>Error Message</jet-label>
                    <s4-input color="ssrGray" class="w-[97%] h-[45px] mb-1 ml-5"
                        v-model="add_edit_form.setup.validate.errorMsg">
                    </s4-input>

                    <TE2 placeholder="" :modelValue="add_edit_form.setup.validate.errorMsg"
                        @update:modelValue="(val) => { add_edit_form.setup.validate.errorMsg = val }"></TE2>

                </div> -->
                <div class="col-span-6">
                    <label class="items-center cursor-pointer flex max-w-[360px]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.randomChoices
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Random Choices</span>
                        <Tooltip :imgSrc="question" preset="RandomChoices">
                        </Tooltip>
                    </label>
                </div>
                <div class="col-span-6 ml-5" v-if="add_edit_form.setup.validate.randomChoices">
                    <vue-form-multiselect
                        placeholder="Click to select choices"
                        :options="randomChoices.random"
                        v-model="add_edit_form.setup.randomChoices"
                        @tag-removed="handleRandomChoicesTagRemoved"
                    />
                </div>
                <div class="col-span-6">
                    <label class="items-center cursor-pointer mr-4">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.selectMultiple
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Apply Multiple Selection</span>
                    </label>
                </div>
                <div class="col-span-6 ml-5" v-if="add_edit_form.setup.selectMultiple">
                    <div class="flex flex-col gap-2">
                        <jet-label>Selection Type</jet-label>
                        <s4-input-select
                            :set="helpers().initProperty(add_edit_form.setup.imageSelection, 'multiSelect_type', 1)"
                            class="w-full" color="ssrGray" :lists="[
                                { text: 'Unlimited', value: 1 },
                                { text: 'Range', value: 2 },
                                { text: 'Fixed', value: 3 }
                            ]" v-model="add_edit_form.setup.imageSelection.multiSelect_type">
                        </s4-input-select>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-2">
                        <div class="flex flex-col gap-2"
                            v-if="add_edit_form.setup.imageSelection.multiSelect_type == 2">
                            <jet-label>Minimum Selection</jet-label>
                            <s4-input
                                :set="helpers().initProperty(add_edit_form.setup.imageSelection, 'multiSelect_min', '1')"
                                type="number" color="ssrGray" class="w-full"
                                v-model="add_edit_form.setup.imageSelection.multiSelect_min">
                            </s4-input>
                        </div>
                        <div class="flex flex-col gap-2"
                            v-if="add_edit_form.setup.imageSelection.multiSelect_type == 2 || add_edit_form.setup.imageSelection.multiSelect_type == 3">
                            <jet-label v-if="add_edit_form.setup.imageSelection.multiSelect_type == 2">Maximum
                                Selection</jet-label>
                            <jet-label v-else>Fixed Selection</jet-label>
                            <s4-input
                                :set="helpers().initProperty(add_edit_form.setup.imageSelection, 'multiSelect_max', '5')"
                                type="number" color="ssrGray" class="w-full"
                                v-model="add_edit_form.setup.imageSelection.multiSelect_max">
                            </s4-input>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 mt-2"
                        v-if="add_edit_form.setup.imageSelection.multiSelect_type == 2">
                        <jet-label>Error Message</jet-label>
                        <TE2 :set="helpers().initProperty(add_edit_form.setup.imageSelection, 'multiSelect_errorMsg_type2', 'Please select at least @min_select to @max_select item(s)')"
                            placeholder="e.g. Please select at least @min_select to @max_select item(s)"
                            :modelValue="add_edit_form.setup.imageSelection.multiSelect_errorMsg_type2"
                            @update:modelValue="(val) => { add_edit_form.setup.imageSelection.multiSelect_errorMsg_type2 = val }"
                            @mousedown.stop></TE2>
                    </div>
                    <div class="flex flex-col gap-2 mt-2"
                        v-if="add_edit_form.setup.imageSelection.multiSelect_type == 3">
                        <jet-label>Error Message</jet-label>
                        <TE2 :set="helpers().initProperty(add_edit_form.setup.imageSelection, 'multiSelect_errorMsg_type3', 'You have to select @max_select items')"
                            placeholder="e.g. You have to select @max_select items"
                            :modelValue="add_edit_form.setup.imageSelection.multiSelect_errorMsg_type3"
                            @update:modelValue="(val) => { add_edit_form.setup.imageSelection.multiSelect_errorMsg_type3 = val }"
                            @mousedown.stop></TE2>
                    </div>
                </div>
                <div class="col-span-6" v-if="add_edit_form.setup.selectMultiple === 1">
                    <label class="items-center cursor-pointer flex max-w-[151px]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.selectAll
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Apply Select All</span>
                        <Tooltip :imgSrc="question" preset="ApplySelectAll">
                        </Tooltip>
                    </label>
                </div>
                <div class="col-span-6 ml-5" v-if="add_edit_form.setup.selectMultiple === 1 && add_edit_form.setup.validate.selectAll">
                    <!-- <jet-label>Select All <small></small></jet-label> -->
                    <vue-form-multiselect placeholder="Select an option to auto select all items"
                        :options="randomChoices.select_all" v-model="add_edit_form.setup.selectAll"
                        @tag-removed="handleSelectAllTagRemoved" />
                </div>
                <div class="col-span-6" v-if="add_edit_form.setup.selectMultiple === 1">
                    <label class="items-center cursor-pointer flex max-w-[170px]">
                        <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.unSelectAll
                            " />
                        <span class="ml-2 text-sm text-gray-600 select-none">Apply Unselect All</span>
                        <Tooltip :imgSrc="question" preset="ApplyUnselectAll">
                        </Tooltip>
                    </label>
                </div>
                <div class="col-span-6 ml-5" v-if="add_edit_form.setup.selectMultiple === 1 && add_edit_form.setup.validate.unSelectAll">
                    <!-- <jet-label>Unselect All <small></small></jet-label> -->
                    <vue-form-multiselect placeholder="Select an option to auto unselect all items"
                        :options="randomChoices.unselect_all" v-model="add_edit_form.setup.unSelectAll"
                        @tag-removed="handleUnselectAllTagRemoved" />
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
            </div>
        </div>

        <div class="col-span-6">
            <div class="grid grid-cols-6 gap-1">
                <div class="grid grid-cols-6 gap-1 col-span-12">
                    <div class="col-span-6"
                        :set="helpers().initProperty(add_edit_form.setup.imageSelection, 'layout_switch', 0)">
                        <label class="items-center cursor-pointer">
                            <s4-checkbox class="cursor-pointer"
                                v-model:checked="add_edit_form.setup.imageSelection['layout_switch']" />
                            <span class="ml-2 text-sm text-gray-600 select-none">Edit Display Type</span>
                        </label>
                    </div>
                    <div class="col-span-6 grid grid-cols-3 gap-1 pl-6">
                        <div class=" flex flex-col gap-2" v-if="add_edit_form.setup.imageSelection['layout_switch']">
                            <div :set="helpers().initProperty(add_edit_form.setup.imageSelection, 'layout_desktop', 1)">
                                <jet-label>Desktop</jet-label>
                                <s4-input-select color="ssrGray" :lists="list_type" class="w-full mt-1"
                                    v-model="add_edit_form.setup.imageSelection['layout_desktop']">
                                </s4-input-select>
                            </div>
                            <div :set="helpers().initProperty(add_edit_form.setup.imageSelection, 'grid', 5)"
                                v-if="add_edit_form.setup.imageSelection['layout_desktop'] == 1">
                                <jet-label>Columns (Desktop)</jet-label>
                                <s4-input type="number" color="ssrGray" class="w-full"
                                    placeholder="Input number of columns"
                                    :modelValue="add_edit_form.setup.imageSelection['grid']" @update:modelValue="
                                        (res) => {
                                            add_edit_form.setup.imageSelection['grid'] = res;
                                        }
                                    ">
                                </s4-input>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2" v-if="add_edit_form.setup.imageSelection['layout_switch']">
                            <div :set="helpers().initProperty(add_edit_form.setup.imageSelection, 'layout_tab', 1)">
                                <jet-label>Tablet</jet-label>
                                <s4-input-select color="ssrGray" :lists="list_type" class="w-full mt-1"
                                    v-model="add_edit_form.setup.imageSelection['layout_tab']">
                                </s4-input-select>
                            </div>
                            <div :set="helpers().initProperty(add_edit_form.setup.imageSelection, 'grid_tab', 3)" v-if="
                                add_edit_form.setup.imageSelection['layout_tab'] == 1
                            ">
                                <jet-label>Columns (Tablet)</jet-label>
                                <s4-input type="number" color="ssrGray" class="w-full"
                                    placeholder="Input number of columns"
                                    :modelValue="add_edit_form.setup.imageSelection['grid_tab']" @update:modelValue="
                                        (res) => {
                                            add_edit_form.setup.imageSelection['grid_tab'] = res;
                                        }
                                    ">
                                </s4-input>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2" v-if="add_edit_form.setup.imageSelection['layout_switch']">
                            <div :set="helpers().initProperty(add_edit_form.setup.imageSelection, 'layout_mobile', 1)">
                                <jet-label>Mobile</jet-label>
                                <s4-input-select color="ssrGray" :lists="list_type" class="w-full mt-1"
                                    v-model="add_edit_form.setup.imageSelection['layout_mobile']">
                                </s4-input-select>
                            </div>
                            <div :set="helpers().initProperty(add_edit_form.setup.imageSelection, 'grid_mobile', 2)"
                                v-if="
                                    add_edit_form.setup.imageSelection['layout_mobile'] == 1
                                ">
                                <jet-label>Column (Mobile)</jet-label>
                                <s4-input type="number" color="ssrGray" class="w-full"
                                    placeholder="Input number of columns"
                                    :modelValue="add_edit_form.setup.imageSelection['grid_mobile']" @update:modelValue="
                                        (res) => {
                                            add_edit_form.setup.imageSelection['grid_mobile'] = res;
                                        }
                                    ">
                                </s4-input>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-6" v-if="
                        add_edit_form.setup.layout &&
                        (add_edit_form.setup.layout_desktop == 1 ||
                            add_edit_form.setup.layout_tab == 1 ||
                            add_edit_form.setup.layout_mobile == 1)
                    ">
                        <!-- <label class="items-center cursor-pointer">
                            <s4-checkbox
                                class="cursor-pointer"
                                v-model:checked="add_edit_form.setup.grid"
                            />
                            <span class="ml-2 text-sm text-gray-600 select-none"
                                ></span
                            >
                        </label> -->
                    </div>

                    <!-- Apply Image Dimension -->
                    <div class="col-span-6"
                        :set="helpers().initProperty(add_edit_form.setup.imageSelection, 'image_dimension_switch', 0)">
                        <label class="items-center cursor-pointer">
                            <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.imageSelection['image_dimension_switch']
                                " />
                            <span class="ml-2 text-sm text-gray-600 select-none">Edit Image Dimension</span>
                        </label>
                    </div>
                    <div class="col-span-6 grid grid-cols-3 gap-1 pl-6">
                        <div v-if="add_edit_form.setup.imageSelection['image_dimension_switch'] == 1">
                            <jet-label>Desktop</jet-label>
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
                                :placeholder="'e.g. 50'"
                                :units="['%', 'vmin', 'vw', 'auto']"
                                v-model.number="itemMaxWidthValue_desktop"
                                v-model:unitValue="itemMaxWidthUnit"
                                :allowAuto="true"
                                min="0"
                                v-model="add_edit_form.setup.itemMaxWidth"
                            >
                            </S4InputSlider> -->
                            <!-- <div>{{ add_edit_form.setup }}</div> -->
                            <S4InputSlider2
                                :set="helpers().initProperty(add_edit_form.setup.imageSelection, 'image_dimension', '20vmin')"
                                color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                                :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.imageSelection['image_dimension']
                                    " :allowAuto="true" @update:modelValue="
                                        (val) => {
                                            add_edit_form.setup.imageSelection['image_dimension'] =
                                                val;
                                        }
                                    ">
                            </S4InputSlider2>
                        </div>

                        <div v-if="add_edit_form.setup.imageSelection['image_dimension_switch'] == 1">
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
                                :placeholder="'e.g. 50'"
                                :units="['%', 'vmin', 'vw', 'auto']"
                                v-model.number="itemMaxWidthValue_tab"
                                v-model:unitValue="itemMaxWidthUnit"
                                :allowAuto="true"
                                min="0"
                                v-model="add_edit_form.setup.itemMaxWidth"
                            >
                            </S4InputSlider> -->

                            <S4InputSlider2
                                :set="helpers().initProperty(add_edit_form.setup.imageSelection, 'image_dimension_tab', '20vmin')"
                                color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                                :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.imageSelection['image_dimension_tab']
                                    " :allowAuto="true" @update:modelValue="
                                        (val) => {
                                            add_edit_form.setup.imageSelection['image_dimension_tab'] =
                                                val;
                                        }
                                    ">
                            </S4InputSlider2>
                        </div>
                        <div v-if="add_edit_form.setup.imageSelection['image_dimension_switch'] == 1">
                            <jet-label>Mobile</jet-label>
                            <!-- <s4-input
                                color="ssrGray"
                                class="w-full mb-1"
                                placeholder="e.g. 100%"
                                v-model="add_edit_form.setup.boxStyle['max-width']"
                            >
                            </s4-input> -->
                            <S4InputSlider2
                                :set="helpers().initProperty(add_edit_form.setup.imageSelection, 'image_dimension_mobile', '20vmin')"
                                color="ssrGray" class="w-full mt-1" :placeholder="'e.g. 100'"
                                :units="['%', 'vmin', 'vw', 'auto']" :modelValue="add_edit_form.setup.imageSelection['image_dimension_mobile']
                                    " :allowAuto="true" @update:modelValue="
                                        (val) => {
                                            add_edit_form.setup.imageSelection[
                                                'image_dimension_mobile'
                                            ] = val;
                                        }
                                    ">
                            </S4InputSlider2>
                        </div>
                    </div>
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
                    <div class="col-span-6 grid grid-cols-3 gap-1 pl-6">
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
                                :modelValue="add_edit_form.setup.boxStyle['max-width'] || '70%'" :allowAuto="true"
                                @update:modelValue="
                                    (val) => {
                                        add_edit_form.setup.boxStyle['max-width'] =
                                            val;
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
                                            add_edit_form.setup.boxStyle[
                                                'max-width-tab'
                                            ] = val;
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
        <s4-gallery-modal :type="'image'" :modal="gallery" @close="closeGallery()" @selectGallery="selectGallery" />
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
</style>
