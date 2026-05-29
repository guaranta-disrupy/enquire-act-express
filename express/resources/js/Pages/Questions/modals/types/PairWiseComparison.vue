<script setup>
import JetLabel from "@/Jetstream/Label.vue";
import WarningModal from "../../../../S4/Warning.vue";
import S4Checkbox from "@/S4/Checkbox.vue";
import S4TextArea from "@/S4/TextArea.vue";
import S4Input from "@/S4/Input.vue";
import S4Button from "@/S4/Button.vue";
import BorderSetup from "@/S4/BorderSetup.vue";
import question from "/public/images/question.svg";
import Tooltip from "@/S4/Tooltip.vue";
import S4InputSlider2 from "@/S4/InputSlider2.vue";
import TE2 from "@/S4/TE2.vue";
import { computed, nextTick, reactive, ref, watch, inject, onBeforeMount, defineExpose } from "vue";

const add_edit_form = inject("add_edit_form");
const showRowTextArea = ref(false);

const warning = reactive({
    cond: false,
    idx: []
})

defineExpose({
    warning: warning
})

onBeforeMount(() => {
    findDuplicateIndices(add_edit_form.values, warning);
    // Build initial value-to-indices map for syncFormatting optimization
    rebuildValueIndexMap();
    
    // Initialize cached max values
    if (add_edit_form.values && add_edit_form.values.length > 0) {
        cachedMaxValueIndex.value = Math.max(...add_edit_form.values.map((o) => o.index || 0));
        cachedMaxValueScore.value = Math.max(...add_edit_form.values.map((o) => o.score || 0));
    }
    if (add_edit_form.items && add_edit_form.items.length > 0) {
        cachedMaxItemIndex.value = Math.max(...add_edit_form.items.map((o) => o.index || 0));
    }
});

// Performance optimization: Cache max index values
const cachedMaxItemIndex = ref(0);
const cachedMaxValueIndex = ref(0);
const cachedMaxValueScore = ref(0);

// Performance optimization: Value-to-indices map for O(1) lookups in syncFormatting
const valueToIndicesMap = reactive({
    valueA: new Map(), // Map<value, Set<itemIndex>>
    valueB: new Map()   // Map<value, Set<itemIndex>>
});

// Rebuild the value-to-indices map (call after items array changes)
const rebuildValueIndexMap = () => {
    valueToIndicesMap.valueA.clear();
    valueToIndicesMap.valueB.clear();
    
    add_edit_form.items.forEach((item, idx) => {
        if (item.valueA != null) {
            if (!valueToIndicesMap.valueA.has(item.valueA)) {
                valueToIndicesMap.valueA.set(item.valueA, new Set());
            }
            valueToIndicesMap.valueA.get(item.valueA).add(idx);
        }
        if (item.valueB != null) {
            if (!valueToIndicesMap.valueB.has(item.valueB)) {
                valueToIndicesMap.valueB.set(item.valueB, new Set());
            }
            valueToIndicesMap.valueB.get(item.valueB).add(idx);
        }
    });
};

// Optimized function to get max index without iterating all items
const getMaxItemIndex = () => {
    if (add_edit_form.items.length === 0) return 0;
    
    // If we have a cached value and items haven't changed much, use it
    const lastItem = add_edit_form.items[add_edit_form.items.length - 1];
    if (lastItem?.index && lastItem.index >= cachedMaxItemIndex.value) {
        cachedMaxItemIndex.value = lastItem.index;
        return lastItem.index;
    }
    
    // Otherwise, find max (but only if cache seems stale)
    const maxIndex = Math.max(...add_edit_form.items.map((o) => o.index || 0));
    cachedMaxItemIndex.value = maxIndex;
    return maxIndex;
};

const new_item = ref("");
const rowArea = ref(null);
const rowAreaElem = ref('');
const rowAreaEquiv = ref('');

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
watch(new_item, async (val) => {
    if (val != "") {
        let len = add_edit_form.items.length;

        if (len) {
            if (add_edit_form.items[len - 1]?.index) {
                indexedItems();
            } else {
                add_edit_form.items.push({
                    statementA: val,
                    statementB: "",
                });
                rebuildValueIndexMap();
            }
        } else {
            indexedItems();
        }

        function indexedItems() {
            const newIndex = cachedMaxItemIndex.value + 1;
            add_edit_form.items.push({
                statementA: val,
                statementB: "",
                index: newIndex,
            });
            cachedMaxItemIndex.value = newIndex;
            rebuildValueIndexMap();
        }

        await nextTick();
        itemRefs.statementA[add_edit_form.items.length - 1].focus();
        new_item.value = "";
    }
});

const new_value = ref("");
watch(new_value, async (val) => {
    if (val != "") {
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
            const newIndex = cachedMaxValueIndex.value + 1;
            const newScore = cachedMaxValueScore.value + 1;
            add_edit_form.values.push({
                name: val,
                color: vColor,
                index: newIndex,
                score: newScore,
            });
            cachedMaxValueIndex.value = newIndex;
            cachedMaxValueScore.value = newScore;
        }

        nextTick(() => {
            itemRefs.val[add_edit_form.values.length - 1].focus();
            new_value.value = "";
        });
    }
});

const itemRefs = reactive({
    statementA: [],
    statementB: [],
    val: [],
});

// Lazy-mount editor state per row/side
const editingState = reactive({ A: {}, B: {} });

// Use full TE2 editors unless the list is large (lowered threshold for better performance)
const shouldLazyEditors = computed(() => add_edit_form.items.length > 20);

// Debounce timer for syncFormatting to batch rapid updates
let syncFormattingTimer = null;
// Store updates by stable identifier (valueA/valueB) instead of array index to prevent stale index issues
const pendingSyncUpdates = new Map(); // Map<`${value}-${source}`, {value, source, updatedValue, updatedEquivalent}>

// Optimized syncFormatting using value-to-indices map for O(1) lookups instead of O(n)
const syncFormatting = (index, source) => {
    // Validate index is still valid before storing update
    if (index < 0 || index >= add_edit_form.items.length) {
        return; // Index is invalid, skip this update
    }
    
    const item = add_edit_form.items[index];
    if (!item) {
        return; // Item doesn't exist, skip this update
    }
    
    // Get the stable identifier (valueA or valueB) instead of array index
    const valueKey = source === "A" ? "valueA" : "valueB";
    const matchingValue = item[valueKey];
    
    if (matchingValue == null) {
        return; // No matching value, can't sync
    }
    
    // Capture the current values at the time of edit (before debounce)
    const updatedValue =
        source === "A"
            ? item.statementA
            : item.statementB;

    const updatedEquivalent =
        source === "A"
            ? item.equivalentA
            : item.equivalentB;
    
    // Use stable identifier (value) as key instead of array index
    const key = `${matchingValue}-${source}`;
    pendingSyncUpdates.set(key, { 
        value: matchingValue, 
        source, 
        updatedValue, 
        updatedEquivalent 
    });
    
    if (syncFormattingTimer) {
        clearTimeout(syncFormattingTimer);
    }
    
    syncFormattingTimer = setTimeout(() => {
        // Process all pending updates in a single batch
        const updatesToProcess = Array.from(pendingSyncUpdates.values());
        pendingSyncUpdates.clear();
        
        // Use a Set to track which items need updating to avoid duplicates
        const itemsToUpdate = new Set();
        
        updatesToProcess.forEach(({ value: matchingValue, source: src, updatedValue, updatedEquivalent }) => {
            if (matchingValue == null) return;

            // Use the optimized map for O(1) lookups instead of iterating all items
            // Check both maps since a value can appear in either valueA or valueB position
            const indicesSetA = valueToIndicesMap.valueA.get(matchingValue);
            const indicesSetB = valueToIndicesMap.valueB.get(matchingValue);
            
            // Update items where valueA matches
            if (indicesSetA) {
                indicesSetA.forEach(itemIdx => {
                    // Validate index is still valid before accessing
                    if (itemIdx < 0 || itemIdx >= add_edit_form.items.length) {
                        return; // Skip invalid indices
                    }
                    
                    itemsToUpdate.add(itemIdx);
                    const item = add_edit_form.items[itemIdx];
                    if (item && item.valueA === matchingValue) {
                        item.statementA = updatedValue;
                        item.equivalentA = updatedEquivalent;
                    }
                });
            }
            
            // Update items where valueB matches
            if (indicesSetB) {
                indicesSetB.forEach(itemIdx => {
                    // Validate index is still valid before accessing
                    if (itemIdx < 0 || itemIdx >= add_edit_form.items.length) {
                        return; // Skip invalid indices
                    }
                    
                    itemsToUpdate.add(itemIdx);
                    const item = add_edit_form.items[itemIdx];
                    if (item && item.valueB === matchingValue) {
                        item.statementB = updatedValue;
                        item.equivalentB = updatedEquivalent;
                    }
                });
            }
        });
        
        syncFormattingTimer = null;
    }, 50); // 50ms debounce - batches rapid edits
};

// Format large indices with thousand separators for readability
const formatNumber = (num) => {
    try {
        return new Intl.NumberFormat().format(num);
    } catch (e) {
        return String(num);
    }
};

const findDuplicateIndices = (values, warning) => {
    if (!values || !Array.isArray(values)) {
        console.warn("Invalid input: choices must be an array.");
        warning.cond = false;
        warning.idx = [];
        return;
    }

    const scores = values.map(value => parseInt(value.score, 10));
    const duplicates = [];

    // Loop through the scores and track duplicate indices
    scores.forEach((score, index) => {
        // Find all indices with the same score
        const duplicateIndices = scores
            .map((s, idx) => (s === score && idx !== index ? idx : -1))
            .filter(idx => idx !== -1);

        if (duplicateIndices.length > 0) {
            duplicates.push(...duplicateIndices, index); // Add both the current index and duplicates
        }
    });

    // Remove duplicates in the indices array
    const uniqueIndices = [...new Set(duplicates)];

    // Update the warning object
    warning.cond = uniqueIndices.length > 0;
    warning.idx = uniqueIndices;

    //   console.log("Duplicate indices:", warning.idx);
};


// Optimized watch: update cache on length changes, deep watch for duplicate detection
watch(
    () => add_edit_form.values?.length,
    () => {
        // Update cached max values when array length changes
        if (add_edit_form.values && add_edit_form.values.length > 0) {
            const maxIndex = Math.max(...add_edit_form.values.map((o) => o.index || 0));
            const maxScore = Math.max(...add_edit_form.values.map((o) => o.score || 0));
            cachedMaxValueIndex.value = maxIndex;
            cachedMaxValueScore.value = maxScore;
        } else {
            cachedMaxValueIndex.value = 0;
            cachedMaxValueScore.value = 0;
        }
    }
);

// Deep watch for duplicate detection (runs less frequently due to debouncing in findDuplicateIndices)
watch(
    () => add_edit_form.values,
    (newChoices) => {
        findDuplicateIndices(newChoices, warning);
    },
    { deep: true }
);


const deleteChoice = (choice, index) => {
    // Clear pending sync updates when deleting items to prevent stale index access
    if (choice === 'items') {
        // Clear all pending updates since indices will shift
        pendingSyncUpdates.clear();
        if (syncFormattingTimer) {
            clearTimeout(syncFormattingTimer);
            syncFormattingTimer = null;
        }
    }
    
    add_edit_form[choice].splice(index, 1);
    if (choice === 'items') {
        rebuildValueIndexMap();
        // Reset cache if we deleted the last item
        if (add_edit_form.items.length === 0) {
            cachedMaxItemIndex.value = 0;
        }
    }
};

const showDeleteAllStatements = ref(false);
const deleteAllStatements = () => {
    // Clear pending sync updates before deleting all items
    pendingSyncUpdates.clear();
    if (syncFormattingTimer) {
        clearTimeout(syncFormattingTimer);
        syncFormattingTimer = null;
    }
    
    add_edit_form.items = [];
    cachedMaxItemIndex.value = 0;
    valueToIndicesMap.valueA.clear();
    valueToIndicesMap.valueB.clear();
    showDeleteAllStatements.value = false;
};

// Loading/progress state for pairwise generation
const isGenerating = ref(false);
const genProgress = ref(0);
const genTotal = ref(0);

const splitForItem = async () => {
    await nextTick();

    if (rowArea.value == null) return;

    isGenerating.value = true;
    genProgress.value = 0;
    genTotal.value = 0;

    // Extract lines: prefer HTML <p> blocks; fallback to plain text by newline
    let paragraphs = rowArea.value.match(/<p>(.*?)<\/p>/g);
    let newRow = [];
    if (paragraphs && paragraphs.length) {
        newRow = paragraphs.map((p) => p.replace(/<\/?p>/g, "").trim());
    } else {
        // Strip HTML and split by newlines for plain text fallback
        const plain = rowArea.value.replace(/<[^>]*>/g, "");
        newRow = plain.split(/\r?\n/).map((s) => s.trim());
    }
    // Clean lines, keep only allowed inline tags (including img), and drop empties
    newRow = newRow
        .map((row) => row.replace(/<(?!\/?(b|i|u|em|strong|br|p|img)\b)[^>]*>/g, ""))
        .filter((row) => row !== "");

    // Prepare equivalents (optional, 1:1 with input rows)
    const equivalents = rowAreaEquiv.value
        ? rowAreaEquiv.value.split('\n').map((e) => e.trim())
        : [];

    // Precompute valid indices and total pairs for progress
    const validIndices = [];
    for (let idx = 0; idx < newRow.length; idx++) {
        if (newRow[idx]) validIndices.push(idx);
    }
    const n = validIndices.length;
    genTotal.value = n > 1 ? (n * (n - 1)) / 2 : 0;

    // Compute the starting index only once (using cached value)
    const existingItems = add_edit_form.items;
    const startIndex = existingItems.length
        ? (existingItems[existingItems.length - 1]?.index
            ? getMaxItemIndex()
            : existingItems.length)
        : 0;

    // Build and store the single statements list as choices[]
    // Ensure choices array exists
    if (!('choices' in add_edit_form) || !Array.isArray(add_edit_form.choices)) {
        add_edit_form.choices = [];
    }
    // Map entered rows to choices: 1:1 with input order, keep HTML <p> wrapper
    const generatedChoices = validIndices.map((i) => {
        const text = newRow[i];
        const eqRaw = equivalents[i]?.trim?.();
        const rawEquivalent = eqRaw && eqRaw !== "" ? eqRaw : text;
        const equivalent = htmlToTextWithImageAlt(rawEquivalent);

        return {
            name: `<p>${text}</p>`,
            equivalent,
            index: i + 1,
        };
    });
    // Replace choices with the newly generated list
    add_edit_form.choices = generatedChoices;

    // Build new items in a local array to avoid repeated reactive pushes
    const batchedItems = [];
    let runningIndex = startIndex;
    let progressCount = 0;

    for (let a = 0; a < n; a++) {
        const i = validIndices[a];
        const statementI = newRow[i];
        const eqRawI = equivalents[i]?.trim?.();
        const rawEquivalentI = eqRawI && eqRawI !== "" ? eqRawI : statementI;
        const equivalentI = htmlToTextWithImageAlt(rawEquivalentI);
        for (let b = a + 1; b < n; b++) {
            const j = validIndices[b];
            const statementJ = newRow[j];
            const eqRawJ = equivalents[j]?.trim?.();
            const rawEquivalentJ = eqRawJ && eqRawJ !== "" ? eqRawJ : statementJ;
            const equivalentJ = htmlToTextWithImageAlt(rawEquivalentJ);

            runningIndex += 1;
            batchedItems.push({
                statementA: `<p>${statementI}</p>`,
                statementB: `<p>${statementJ}</p>`,
                valueA: i + 1,
                valueB: j + 1,
                index: runningIndex,
                equivalentA: equivalentI,
                equivalentB: equivalentJ,
            });

            progressCount += 1;
            if (progressCount % 50 === 0) {
                genProgress.value = progressCount;
            }
        }
    }

    if (batchedItems.length) {
        // Single batched update to minimize re-rendering
        add_edit_form.items = add_edit_form.items.concat(batchedItems);
        // Update cached max index
        cachedMaxItemIndex.value = runningIndex;
        // Rebuild value-to-indices map after batch addition
        rebuildValueIndexMap();
    }

    // Reset inputs and UI
    rowArea.value = '';
    rowAreaEquiv.value = '';
    if (rowAreaElem.value && rowAreaElem.value.forceClear) {
        rowAreaElem.value.forceClear();
    }
    showRowTextArea.value = false;

    genProgress.value = genTotal.value;
    isGenerating.value = false;
};

const cancelPairwiseInputs = () => {
    showRowTextArea.value = false;
    // Clear all text areas
    rowArea.value = '';
    rowAreaEquiv.value = '';
    // Force clear the TE2 component if it has a forceClear method
    if (rowAreaElem.value && rowAreaElem.value.forceClear) {
        rowAreaElem.value.forceClear();
    }
};


const equivalentA = ref('');
const equivalentB = ref('');



// const splitForItem = async () => {
//     await nextTick();
//     if (rowArea.value != null) {
//         // Handle multi-line input
//         let newRow = rowArea.value.split("\n").filter(row => row.trim() !== ""); // Filter out empty lines

//         // Handle plain text input (comma-separated)
//         let plainTextRow = rowArea.value
//             .replace(/<\/?[^>]+(>|$)/g, "") // Remove HTML tags
//             .split(",") // Split by commas for plain text
//             .map(item => item.trim()) // Trim whitespace
//             .filter(item => item !== "");

//         // Choose which input to process (prioritize multi-line if both exist)
//         let rowsToProcess = newRow.length > 1 ? newRow : plainTextRow;

//         // Generate all possible pairwise comparisons
//         for (let i = 0; i < rowsToProcess.length; i++) {
//             for (let j = i + 1; j < rowsToProcess.length; j++) {
//                 if (rowsToProcess[i] !== "" && rowsToProcess[j] !== "") {
//                     let len = add_edit_form.items.length;

//                     if (len) {
//                         if (add_edit_form.items[len - 1]?.index) {
//                             indexedItems(rowsToProcess[i], rowsToProcess[j], i + 1, j + 1);
//                         } else {
//                             add_edit_form.items.push({
//                                 statementA: rowsToProcess[i],
//                                 statementB: rowsToProcess[j],
//                                 valueA: i + 1, // Value for statementA (1-based index)
//                                 valueB: j + 1, // Value for statementB (1-based index)
//                                 index: len + 1, // Index for the pair
//                             });
//                         }
//                     } else {     
//                         indexedItems(rowsToProcess[i], rowsToProcess[j], i + 1, j + 1);
//                     }
//                 }
//             }
//         }

//         function indexedItems(statementA, statementB, valueA, valueB) {
//             let newIndex = add_edit_form.items.length === 0
//                 ? 1
//                 : Math.max(...add_edit_form.items.map((o) => o.index)) + 1;

//             add_edit_form.items.push({
//                 statementA: statementA,
//                 statementB: statementB,
//                 valueA: valueA, // Value for statementA (1-based index)
//                 valueB: valueB, // Value for statementB (1-based index)
//                 index: newIndex, // Index for the pair
//             });
//         }

//     } else return;

//     rowArea.value = null;       
//     showRowTextArea.value = false;
// };

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

// onBeforeMount(() => {
//     if (!('boxStyle' in add_edit_form.setup)) {
//         add_edit_form.setup['boxStyle'] = { 'max-width': '100%' };
//     }
//     const maxWidth = add_edit_form.setup.boxStyle['max-width'] || '100%';
//     if (maxWidth === "auto") {
//         boxStyleValue.value = null;
//         boxStyleUnit.value = "auto";
//     } else {
//         const match = maxWidth.match(/^(\d+)([a-z%]+)$/);
//         if (match) {
//             boxStyleValue.value = parseFloat(match[1]);
//             boxStyleUnit.value = match[2];
//         }
//     }
// });
</script>

<template>
    <div class=" mt-5">

        <!-- <div class="col-span-5" v-if="add_edit_form.setup.labels"></div> -->
        <div class=" mt-3">
            <jet-label class="font-bold">Pairwise Comparison Set:</jet-label>
            <div class="flex justify-end pr-6">
                <button @click="showDeleteAllStatements = true" v-if="add_edit_form.items.length > 0"
                    class="inline-flex items-center px-4 hover:bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-[1.1]">
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
            <div class=" mt-3" v-if="add_edit_form.setup.labels">
                <div class="grid grid-cols-1 gap-4 items-end pairwise-labels-grid-empty" v-if="add_edit_form.items.length == 0">
                    <div class="rounded-lg w-full ml-2">
                        <jet-label class="font-bold text-xs bg-white">Statement A Label</jet-label>
                        <TE2 placeholder="Statement A" :modelValue="add_edit_form.setup.labels.statementA"
                            @update:modelValue="(val) => {
                                add_edit_form.setup.labels.statementA = val
                            }">
                        </TE2>
                    </div>
                    <div class="rounded-lg w-full">
                        <jet-label class="font-bold text-xs bg-white">Statement B Label</jet-label>
                        <TE2 placeholder="Statement B" :modelValue="add_edit_form.setup.labels.statementB"
                            @update:modelValue="(val) => {
                                add_edit_form.setup.labels.statementB = val
                            }">
                        </TE2>
                    </div>


                </div>

                <div class="grid grid-cols-1 pl-4 gap-1 items-end pairwise-labels-grid-header" v-else>
                    <div class="rounded-lg w-full ml-2">
                        <jet-label class="font-bold text-xs bg-white">Statement A Label</jet-label>
                        <TE2 placeholder="Statement A" :modelValue="add_edit_form.setup.labels.statementA"
                            @update:modelValue="(val) => {
                                add_edit_form.setup.labels.statementA = val
                            }">
                        </TE2>
                    </div>
                    <div>
                        <jet-label class="font-bold text-xs bg-white flex justify-center items-center">
                            Equivalent A</jet-label>
                    </div>
                    <div>
                        <jet-label class="font-bold text-xs bg-white flex justify-center items-center">
                            Value A</jet-label>

                    </div>

                    <div class="ml-3.5 font-bold"></div>

                    <div class="rounded-lg w-full">
                        <jet-label class="font-bold text-xs bg-white">Statement B Label</jet-label>
                        <TE2 placeholder="Statement B" :modelValue="add_edit_form.setup.labels.statementB"
                            @update:modelValue="(val) => {
                                add_edit_form.setup.labels.statementB = val
                            }">
                        </TE2>
                    </div>
                    <div>
                        <jet-label class="font-bold text-xs bg-white flex justify-center items-center">
                            Equivalent B</jet-label>
                    </div>
                    <div>
                        <jet-label class="font-bold text-xs bg-white flex justify-center items-center">
                            Value B</jet-label>
                    </div>

                </div>
            </div>

            <template v-if="!isGenerating" v-for="(item, index) in add_edit_form.items" :key="index">
                <div class=" mt-1 mb-1 text-xs">

                    <div class="grid grid-cols-1 gap-2 items-start pairwise-items-grid-row">
                        <div class="place-items-center pr-1">
                            <span class="text-sm font-semibold text-slate-600 tabular-nums whitespace-nowrap text-right block w-full">{{
                                formatNumber(item?.index ? item.index : index + 1)
                                }}</span>
                        </div>
                        <div>
                            <div v-if="shouldLazyEditors && !editingState.A[index]" class="min-h-[38px] border border-gray-300 rounded-md px-3 py-2 text-sm leading-5 text-gray-900 bg-white cursor-text shadow-sm hover:border-gray-400 focus-within:ring-1 focus-within:ring-[var(--mcolor)] focus-within:border-[var(--mcolor)]"
                                @click="editingState.A[index] = true; $nextTick(() => itemRefs.statementA[index]?.focus && itemRefs.statementA[index].focus());">
                                <div v-html="add_edit_form.items[index].statementA || '<span class=\'text-gray-400\'>Enter Statement A</span>'"></div>
                            </div>
                            <TE2 v-else placeholder="Enter Statement A" :modelValue="add_edit_form.items[index].statementA"
                                @blur="editingState.A[index] = false"
                                @update:modelValue="(val) => {
                                    add_edit_form.items[index].statementA = val
                                    syncFormatting(index, 'A')
                                }" :ref="(el) => {
                                    itemRefs.statementA[index] = el;
                                }">
                            </TE2>
                        </div>
                        <div>
                            <s4-input color="ssrGray" placeholder="Equivalent A"
                                v-model="add_edit_form.items[index].equivalentA" @update:modelValue="(val) => {
                                    add_edit_form.items[index].equivalentA = val
                                    syncFormatting(index, 'A')
                                }" />
                        </div>

                        <div>
                            <s4-input color="ssrGray" class="flex justify-center items-center"
                                placeholder="Statement A Value" :value="item.valueA" readonly />

                        </div>
                        <div class="ml-4 font-semibold">VS</div>
                        <div>
                            <div v-if="shouldLazyEditors && !editingState.B[index]" class="min-h-[38px] border border-gray-300 rounded-md px-3 py-2 text-sm leading-5 text-gray-900 bg-white cursor-text shadow-sm hover:border-gray-400 focus-within:ring-1 focus-within:ring-[var(--mcolor)] focus-within:border-[var(--mcolor)]"
                                @click="editingState.B[index] = true; $nextTick(() => itemRefs.statementB[index]?.focus && itemRefs.statementB[index].focus());">
                                <div v-html="add_edit_form.items[index].statementB || '<span class=\'text-gray-400\'>Enter Statement B</span>'"></div>
                            </div>
                            <TE2 v-else placeholder="Enter Statement B" :modelValue="add_edit_form.items[index].statementB"
                                @blur="editingState.B[index] = false"
                                @update:modelValue="(val) => {
                                    add_edit_form.items[index].statementB = val
                                    syncFormatting(index, 'B')
                                }" :ref="(el) => {
                                    itemRefs.statementB[index] = el;
                                }">
                            </TE2>
                        </div>
                        <div>
                            <s4-input color="ssrGray" placeholder="Equivalent B"
                                v-model="add_edit_form.items[index].equivalentB" @update:modelValue="(val) => {
                                    add_edit_form.items[index].equivalentB = val
                                    syncFormatting(index, 'B')
                                }" />
                        </div>
                        <div>
                            <s4-input color="ssrGray" placeholder="Statement B Value" :value="item.valueB" readonly />

                        </div>



                        <!-- <ButtonIconsVue class="w-6 shadow-none border-none" @click="deleteChoice('items', index)"
                            icon="delete" /> -->
                    </div>

                </div>
            </template>
            <div v-else class="text-xs text-gray-500 mt-2 ml-4">Rendering will resume after generation completes…</div>

            <!-- this section is for question type 19 = segmentation -->
            <div class="col-span-6 pt-2">
                <!-- <s4-button v-show="showRowTextArea == false" :theme="'ssrAddColRow'" class="py-1 px-2 mb-10"
                    color="ssrB" @click="showRowTextArea = !showRowTextArea">
                    Add Pairwise Comparison
                </s4-button> -->
                <div class="grid grid-cols-1 pt-5" v-show="showRowTextArea">
                    <div class="grid grid-cols-2">
                        <JetLabel class="ml-1">Enter Statements</JetLabel>
                        <JetLabel>Enter Equivalents</JetLabel>
                    </div>

                    <div class="flex gap-1 ">

                        <TE2 v-show="showRowTextArea" ref="rowAreaElem"
                            placeholder="Enter at least 2 statements, each on a new line, to generate comparison sets..."
                            :modelValue="rowArea" height="150px" @update:modelValue="(val) => { rowArea = val }">
                        </TE2>
                        <!-- <s4-text-area color="ssrGray" v-show="showRowTextArea" v-model="rowArea" class="w-full h-40"
                            placeholder="Enter Statements separated by new line..."></s4-text-area> -->
                        <!-- <S4textEditor
                            placeholderText="Add your statement here separated by comma (,) in plain text. Example: Item 1,Item 2,Item 3"
                            v-show="showRowTextArea" v-model="rowArea" /> -->
                        <s4-text-area color="ssrGray" v-show="showRowTextArea" :filter="true" v-model="rowAreaEquiv"
                            class="w-full h-40" placeholder="Enter equivalents separated by new line..."></s4-text-area>

                    </div>
                </div>
                <div class="flex mb-5 items-center gap-2" v-show="showRowTextArea">
                    <s4-button :theme="'ssrAddColRow'"
                        class="py-1 px-2 mt-2 !bg-[var(--mcolor)] hover:!bg-[var(--mcolor2)] text-white text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg disabled:opacity-60 disabled:cursor-not-allowed"
                        :disabled="isGenerating"
                        @click="splitForItem()">
                        <span v-if="!isGenerating">Add</span>
                        <span v-else class="inline-flex items-center">
                            <svg class="animate-spin h-4 w-4 mr-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            Generating...
                        </span>
                    </s4-button>
                    <s4-button :theme="'ssrAddColRow'"
                        class="py-1 px-2 ml-1 mt-2 bg-transparent text-red-500 text-sm font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-125 hover:bg-transparent"
                        :disabled="isGenerating"
                        @click="cancelPairwiseInputs()">Cancel</s4-button>

                    <div v-if="isGenerating" class="flex flex-col ml-2 mt-2 w-full max-w-sm">
                        <div class="text-xs text-gray-500">Generating {{ genProgress }} / {{ genTotal }} pairs</div>
                        <div class="w-full bg-gray-200 rounded h-2 overflow-hidden">
                            <div class="bg-[var(--mcolor)] h-2" :style="{ width: (genTotal ? Math.round((genProgress / genTotal) * 100) : 0) + '%' }"></div>
                        </div>
                    </div>
                </div>
                <button v-show="showRowTextArea == false" :theme="'ssrAddColRow'"
                    @click="showRowTextArea = !showRowTextArea"
                    class="inline-flex w-[auto] items-center px-2 py-1 mb-2 bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] text-white text-xs font-medium rounded-md transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
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

                    <span class="transition-all duration-300 ease-in-out">
                        Add Pairwise Comparison
                    </span>
                </button>
                
            </div>
        </div>
    </div>

    <BorderSetup></BorderSetup>

    <div class="flex grid grid-cols-2 mt-6">
        <div class="">
            <label class="flex items-center cursor-pointer w-[110px]">
                <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.validate.required" />
                <span class="ml-2 text-sm text-gray-600 select-none">Required</span>
                <Tooltip :imgSrc="question" preset="Required">
                </Tooltip>
            </label>

            <div class="grid grid-cols-3 pr-2">
                <div class="col-span-3 pb-4 ml-6">
                    <jet-label>Footer</jet-label>
                    <TE2 :modelValue="add_edit_form.setup.footer" @update:modelValue="(val) => {
                        add_edit_form.setup.footer = val
                    }">
                    </TE2>
                </div>


                <!-- <label class="items-center cursor-pointer ml-6 flex" v-if="add_edit_form.question_type_id == 19">
                    <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.hideQuestionFrame" />
                    <span class="ml-2 text-sm text-gray-600 select-none">Hide Question</span>
                    <Tooltip :imgSrc="question" label="Hide the question by checking this box.">
                    </Tooltip>
                </label> -->

                <!-- <div class="col-span-2 mt-5" v-if="add_edit_form.setup.validate.required == 1">

                    <s4-input color="ssrGray" class="w-full mb-1" :disabled="add_edit_form.setup.validate.required == 0"
                        v-model="add_edit_form.setup.validate.errorMsg">
                    </s4-input>
                    <TE2 :modelValue="add_edit_form.setup.validate.errorMsg" @update:modelValue="(val) => {
                        add_edit_form.setup.validate.errorMsg = val
                    }">
                    </TE2>
                </div> -->

            </div>
        </div>
        <div>
            <label class="flex items-center cursor-pointer w-[170px]">
                <s4-checkbox class="cursor-pointer" v-model:checked="add_edit_form.setup.boxStyle[
                    'max-width_enabler'
                ]
                    " />
                <span class="ml-2 text-sm text-gray-600 select-none">Edit Layout Width</span>
                <Tooltip :imgSrc="question"
                    label="Set the width of the content box. Check the box to adjust it for different screen sizes.">
                </Tooltip>
            </label>

            <div class="flex gap-2" v-if="add_edit_form.setup.boxStyle['max-width_enabler']">
                <div class="ml-6">
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

@media (min-width: 1100px) {
    .pairwise-labels-grid-empty {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .pairwise-labels-grid-header {
        grid-template-columns: 400px 13fr 50px 2fr 16fr 295px 50px;
    }

    .pairwise-items-grid-row {
        grid-template-columns: 56px 400px 39fr 6fr 50px 400px 36fr 50px;
        gap: 0.25rem; /* equivalent to Tailwind gap-1 */
        align-items: center;
    }
}
</style>
