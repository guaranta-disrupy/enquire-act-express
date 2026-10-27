<script setup>
// Import CK Editor Components
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import { component as CKEditor } from "@ckeditor/ckeditor5-vue";
import UploadAdapterPlugin from "@/Functions/UploadAdapter";
import { PrismEditor } from "vue-prism-editor";
import "vue-prism-editor/dist/prismeditor.min.css"; // import the styles somewhere
// import highlighting library (you can use any library you want just return html string)
import { highlight, languages } from "prismjs/components/prism-core";
import "prismjs/components/prism-clike";
import "prismjs/components/prism-javascript";
import "prismjs/components/prism-markup";
import "prismjs/components/prism-css";
import "prismjs/themes/prism-tomorrow.css"; // import syntax highlighting styles
// Ck Editor end
import { QuillEditor } from "@vueup/vue-quill";
import "quill/dist/quill.snow.css";
import "quill/dist/quill.bubble.css";

import { inject, ref, provide, onBeforeMount, onMounted, onUnmounted, computed } from "vue";
import CustomSwitch from "@/Components/Switch.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimeCard from "@/Components/PrimeCard.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
// import JetButton from "@/Components/Button.vue";
import CriteriaContents from "./Criteria.vue";
import CustomInput from "@/Components/Input.vue";
import TextArea from "@/Components/TextArea.vue";
import Toggle from "@/Jetstream/Toggle.vue";
import JetButton from "@/Componentsv2/Button.vue";
import JetLabel from "@/Jetstream/Label.vue";
import JetInput from "@/Jetstream/Input.vue";
import TestCriteria from "./TestCriteria.vue";
import IndexVariables from "@/Pages/Variables2/Index.vue";

import Helpers from "@/Mixins/Helpers";
import { useForm, usePage } from "@inertiajs/vue3";
import maxBy from "lodash/maxBy";
import forEach from "lodash/forEach";
import { setProxiedHtml } from "@/utils/setProxiedHtml";

const { sanitize, capitalize, hasImage, $state, $testState, assign, toast } =
    Helpers();

const props = defineProps(["templatePrimes", "project", "survey", "variables"]);

const selectedTemplate = ref(null);
const saveAsTemplate = ref(false);
const showTemplatePanel = ref(false);

// Variables functionality
const showVariablesModal = ref(false);
const activeQuillEditor = ref(null);
const page = usePage();
const variables = computed(() => {
    const pageVars = page?.props?.variables;
    const propVars = props.variables;
    if (Array.isArray(pageVars)) return pageVars;
    if (Array.isArray(propVars)) return propVars;
    return [];
});

const templateForm = useForm({
    name: "",
    template_data: [],
});

const loadTemplatePrimes = () => {
    if (selectedTemplate.value) {
        const template = props.templatePrimes.find(
            (t) => t.id === selectedTemplate.value
        );
        if (template) {
            form.primes = template.data;
        }
    }
    showTemplatePanel.value = false;
};

const saveTemplatePrimes = async () => {
    templateForm.template_data = form.primes;
    await templateForm.post(
        route("templates.primes.store", {
            team: props.project.team_id,
            project: props.project.uuid,
            survey: props.survey.id,
        }),
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                saveAsTemplate.value = false;
                templateForm.reset("name");
            },
        }
    );
};

// Helper that only saves when toggled and has a name
const saveTemplatePrimesIfNeeded = async () => {
    if (saveAsTemplate.value && templateForm.name) {
        await saveTemplatePrimes();
    }
};

// Expose the saveTemplate function to parent component
defineExpose({
    saveTemplatePrimes,
    saveTemplatePrimesIfNeeded,
    saveAsTemplate,
});

// const deleteTemplate = () => {
//     if (selectedTemplate.value) {
//         router.delete(
//             route("templates.primes.destroy", {
//                 templatePrime: selectedTemplate.value,
//             }),
//             {
//                 preserveScroll: true,
//                 onSuccess: () => {
//                     selectedTemplate.value = null;
//                 },
//             }
//         );
//     }
// };

provide("state", $state);
provide("testState", $testState);

const enableCK = inject("ck");
const ckeditor = CKEditor.component;
const editor = ref(ClassicEditor);
const primeQuillRef = ref(null);
const bulkPrimesQuill = ref(null);
const selectedIndex = ref(false);

const form = inject("form");

const editorConfig = ref({
    extraPlugins: [UploadAdapterPlugin],
});
const modals = ref({
    createPrime: false,
});
const _prime = ref({
    prime: "",
    equivalent: "",
    value: 1,
    config: {
        conditions: [],
    },
});

// For bulk editing in modal
const editingPrimes = ref([]);
const selectedItemsInModal = ref(new Set());
const selectAllInModal = ref(false);
const bulkPrimesText = ref("");
const bulkEquivalentsText = ref("");
const newItemInModal = ref({
    prime: "",
    equivalent: "",
    value: 1,
});
const quillEditorKey = ref(0);
const bulkQuillEditorKey = ref(0);
const showTextToImageModal = ref(false);
const textToImageText = ref("");
const textToImageImageUrl = ref("");
const textToImageFeedback = ref("");
const canUseTextToImage = computed(() => editingPrimes.value.length > 0);

onBeforeMount(() => {
    $state.criteria = _prime.value;
    $testState.criteria = form;
});

const highlighter = (code) => {
    return highlight(code, languages.markup);
};

const stripParagraphsAroundImages = (html) => {
    if (!html) return html;
    try {
        // Only strip <p> tags that contain ONLY images (and whitespace)
        // Preserve <p> tags that contain images AND other text content
        return String(html).replace(/<p([^>]*)>([\s\S]*?)<\/p>/gi, (match, attrs, content) => {
            // Remove all image tags and whitespace to check if there's any other content
            const contentWithoutImages = content.replace(/<(?:img|picture)\b[^>]*>/gi, '').trim();
            
            // If there's any non-whitespace content besides images, preserve the <p> tag
            if (contentWithoutImages) {
                return match;
            }
            
            // Only images (and whitespace), strip the <p> tag but keep the images
            return content.trim();
        });
    } catch (_) {
        return html;
    }
};

// Convert {{variable_name}} tokens to styled spans for visual editing
const parseVariablesToElements = (htmlContent) => {
    if (!htmlContent) return htmlContent;
    try {
        const normalized = stripParagraphsAroundImages(String(htmlContent));
        return normalized.replace(/\{\{([^}]+)\}\}/g, (_m, name) => {
            const variableName = String(name).trim();
            return `<span class="ql-variable" data-name="${variableName}" contenteditable="false" style="background: #257556; color: #fff; padding: 4px 8px; border-radius: 6px; font-weight: 600; display: inline; margin: 0 2px; cursor: not-allowed; user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; box-shadow: 2px 2px 4px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.3); border: 1px solid #1d5e45;">${variableName}</span>`;
        });
    } catch (e) {
        return htmlContent;
    }
};

// Convert styled spans back to {{variable_name}} tokens for saving
const parseElementsToVariables = (htmlContent) => {
    if (!htmlContent) return htmlContent;
    try {
        const normalized = stripParagraphsAroundImages(String(htmlContent));
        return normalized.replace(
            /<span class="ql-variable"[^>]*data-name="([^"]*)"[^>]*>.*?<\/span>/g,
            (_m, name) => `{{${name}}}`
        );
    } catch (e) {
        return htmlContent;
    }
};
// Image helpers for update modal
const extractImageSrc = (html) => {
    try {
        const match = String(html).match(/<img[^>]+src=["']([^"']+)["']/i);
        return match ? match[1] : "";
    } catch (_) {
        return "";
    }
};

const buildImageHtmlFromUrl = (url) => {
    const u = String(url || "").trim();
    if (!u) return "";
    return `<img src="${u}">`;
};

const replaceTextWithImage = (content, searchText, imageUrl) => {
    if (!content || !searchText || !imageUrl) return content;
    return String(content).split(searchText).join(buildImageHtmlFromUrl(imageUrl));
};

const openTextToImageModal = () => {
    if (!canUseTextToImage.value) return;
    textToImageText.value = "";
    textToImageImageUrl.value = "";
    textToImageFeedback.value = "";
    showTextToImageModal.value = true;
};

const closeTextToImageModal = () => {
    showTextToImageModal.value = false;
};

const convertTextToImageInPrimeItems = () => {
    textToImageFeedback.value = "";
    const searchText = textToImageText.value.trim();
    const imageUrl = textToImageImageUrl.value.trim();

    if (!searchText || !imageUrl) {
        textToImageFeedback.value = "Both fields are required.";
        return;
    }

    let replacements = 0;
    editingPrimes.value.forEach((item, index) => {
        if (!item || typeof item.prime !== "string") return;
        if (!item.prime.includes(searchText)) return;
        const updatedPrime = stripParagraphsAroundImages(
            replaceTextWithImage(item.prime, searchText, imageUrl)
        );
        if (updatedPrime !== item.prime) {
            editingPrimes.value[index].prime = updatedPrime;
            replacements++;
        }
    });

    if (!replacements) {
        textToImageFeedback.value = "No matches found in current primes.";
        return;
    }

    textToImageText.value = "";
    textToImageImageUrl.value = "";
    showTextToImageModal.value = false;
};

// Convert HTML to editor-friendly string without <p> tags, preserving other tags
const htmlToEditorNoP = (html, options = {}) => {
    const { preserveParagraphs = false } = options;
    if (!html) return "";
    try {
        let result = String(html)
            .replace(/<p>\s*<br\s*\/?>(\s*)<\/p>/gi, "\n")
            .replace(/<br\s*\/?>(?=<\/p>)/gi, "")
            .replace(/<\/p>\s*<p>/gi, preserveParagraphs ? "</p>\n<p>" : "\n");
        if (!preserveParagraphs) {
            result = result.replace(/<\/?p>/gi, "");
        }
        result = result.replace(/<br\s*\/?>/gi, "\n");
        return result;
    } catch (_) {
        return html;
    }
};

// Convert editor string (no <p>) back to HTML without adding <p>; keep <br>
const editorToHtmlNoP = (text) => {
    if (!text) return "";
    return String(text).replace(/\r\n/g, "\n").replace(/\n/g, "<br>");
};

const unformatContent = (t, val = null) => {
    if (t == 1) {
        if (hasImage(_prime.value.prime)) return;
        _prime.value.equivalent = sanitize(_prime.value.prime)
            .split(" i ")
            .join(" I ");
    } else {
        return sanitize(val).split(" i ").join(" I ");
    }
};

const addSegment = () => {
    if (selectedIndex.value && editingPrimes.value.length > 0) {
        // When updating a prime, add to the editing prime's conditions
        if (!editingPrimes.value[0].config) {
            editingPrimes.value[0].config = {};
        }
        if (!editingPrimes.value[0].config.conditions) {
            editingPrimes.value[0].config.conditions = [];
        }
        editingPrimes.value[0].config.conditions.push({
            method: "process",
            left_operator: "and",
            var: "",
            operator: "==",
            val: "",
            conditions: [],
        });
    } else {
        // For new primes, use the test state
        return $testState.addSegment();
    }
};

const deleteCondition = (indexes) => {
    if (selectedIndex.value && editingPrimes.value.length > 0) {
        // When updating a prime, delete from the editing prime's conditions
        if (indexes.length === 1) {
            // Delete top-level condition
            editingPrimes.value[0].config.conditions.splice(indexes[0], 1);
        } else {
            // Delete nested condition - navigate through the condition tree
            let currentConditions = editingPrimes.value[0].config.conditions;
            for (let i = 0; i < indexes.length - 1; i++) {
                currentConditions = currentConditions[indexes[i]].conditions;
            }
            currentConditions.splice(indexes[indexes.length - 1], 1);
        }
    }
};

const addPrimeList = () => {
    let primes = assign(_prime.value.prime.split("\n"));
    let eqs = assign(_prime.value.equivalent.split("\n"));
    let currentMaxIndex = getMaxIndex(form.primes);

    forEach(primes, (p, i) => {
        if (p.trim()) {
            // Only add non-empty primes
            currentMaxIndex++;
            let prime = {
                prime: p,
                equivalent: _prime.value.equivalent
                    ? eqs[i] ?? unformatContent(3, p)
                    : unformatContent(3, p),
                index: currentMaxIndex,
                value: _prime.value.value,
                config: assign(_prime.value.config),
            };
            form.primes.push(prime);
        }
    });
    resetPrimeForm();
};

const resetPrimeForm = () => {
    _prime.value = {
        prime: "",
        equivalent: "",
        value: 1,
        config: {
            conditions: [],
        },
    };
    selectedIndex.value = false;
    editingPrimes.value = [];
    selectedItemsInModal.value = new Set();
    selectAllInModal.value = false;
    bulkPrimesText.value = "";
    bulkEquivalentsText.value = "";
    newItemInModal.value = {
        prime: "",
        equivalent: "",
        value: 1,
    };
    quillEditorKey.value++;
    bulkQuillEditorKey.value++;
    modals.value.createPrime = false;
};

const addPrimeDialog = () => {
    showTemplatePanel.value = false;
    resetPrimeForm();
    // Initialize with empty editing array
    editingPrimes.value = [];
    $state.criteria = _prime.value;
    modals.value.createPrime = true;
};

// Toggle select all items in modal
const toggleSelectAllInModal = () => {
    if (selectAllInModal.value) {
        selectedItemsInModal.value = new Set(
            editingPrimes.value.map((_, index) => index)
        );
    } else {
        selectedItemsInModal.value = new Set();
    }
};

// Toggle individual item selection in modal
const toggleItemSelectionInModal = (index) => {
    if (selectedItemsInModal.value.has(index)) {
        selectedItemsInModal.value.delete(index);
    } else {
        selectedItemsInModal.value.add(index);
    }
    selectAllInModal.value =
        selectedItemsInModal.value.size === editingPrimes.value.length &&
        editingPrimes.value.length > 0;
};

// Delete selected items in modal
const deleteSelectedItemsInModal = () => {
    const indices = Array.from(selectedItemsInModal.value).sort(
        (a, b) => b - a
    );
    indices.forEach((index) => {
        editingPrimes.value.splice(index, 1);
    });
    selectedItemsInModal.value = new Set();
    selectAllInModal.value = false;
};

// Delete individual item from modal
const deleteItemFromModal = (index) => {
    editingPrimes.value.splice(index, 1);
    // Remove from selection if it was selected
    selectedItemsInModal.value.delete(index);
    // Update selection indices after deletion
    const newSelectedItems = new Set();
    selectedItemsInModal.value.forEach((selectedIndex) => {
        if (selectedIndex > index) {
            newSelectedItems.add(selectedIndex - 1);
        } else if (selectedIndex < index) {
            newSelectedItems.add(selectedIndex);
        }
    });
    selectedItemsInModal.value = newSelectedItems;
    selectAllInModal.value = false;
};

// Update item field in modal
const updateItemFieldInModal = (index, field, value) => {
    if (editingPrimes.value[index]) {
        editingPrimes.value[index][field] = value;
    }
};

// Add new item in modal
const addNewItemInModal = () => {
    if (!newItemInModal.value.prime) return;

    // Get the maximum index from both existing primes and current editing primes
    const existingMaxIndex = getMaxIndex(form.primes);
    const editingMaxIndex = getMaxIndex(editingPrimes.value);
    const maxIndex = Math.max(existingMaxIndex, editingMaxIndex);

    editingPrimes.value.push({
        prime: newItemInModal.value.prime,
        equivalent:
            newItemInModal.value.equivalent ||
            sanitize(newItemInModal.value.prime).split(" i ").join(" I "),
        index: maxIndex + 1,
        value: newItemInModal.value.value || 1,
        config: {
            conditions: [],
        },
    });

    // Clear the input fields
    newItemInModal.value = {
        prime: "",
        equivalent: "",
        value: 1,
    };

    // Force QuillEditor to re-render by incrementing the key
    quillEditorKey.value++;
};

// Add bulk primes in modal
const addBulkPrimesInModal = () => {
    if (!bulkPrimesText.value || !String(bulkPrimesText.value).trim()) {
        try {
            toast.error("Need to input character to add a prime");
        } catch (_) {}
        return;
    }

    // Helper to determine if a line actually has any visible text or images
    const hasVisibleContent = (line) => {
        if (!line) return false;
        const raw = String(line);

        // Any <img> tag counts as valid content
        if (/<img\b[^>]*>/i.test(raw)) return true;

        // Replace &nbsp; with regular space, strip HTML tags, then trim
        const withoutNbsp = raw.replace(/&nbsp;/gi, " ");
        const textOnly = withoutNbsp.replace(/<[^>]*>/g, "").trim();
        return textOnly.length > 0;
    };

    // Handle both plain text and HTML content
    let processed;
    const containsHtml =
        typeof bulkPrimesText.value === "string" &&
        /<[^>]+>/.test(bulkPrimesText.value);
    if (containsHtml) {
        // Preserve paragraph wrappers so text and images stay grouped together
        processed = htmlToEditorNoP(bulkPrimesText.value, {
            preserveParagraphs: true,
        });
    } else {
        // Plain text input can be used directly
        processed = bulkPrimesText.value;
    }

    const primeLines = processed.split("\n");
    const equivalentLines = bulkEquivalentsText.value.split("\n");

    // Filter out lines that have no visible content (no text and no image)
    const primes = [];
    const equivalents = [];
    primeLines.forEach((line, idx) => {
        if (hasVisibleContent(line)) {
            primes.push(line);
            equivalents.push(equivalentLines[idx] ?? "");
        }
    });

    if (primes.length === 0) {
        try {
            toast.error("Need to input character to add a prime");
        } catch (_) {}
        return;
    }

    // Get the maximum index from both existing primes and current editing primes
    const existingMaxIndex = getMaxIndex(form.primes);
    const editingMaxIndex = getMaxIndex(editingPrimes.value);
    let maxIndex = Math.max(existingMaxIndex, editingMaxIndex);

    primes.forEach((prime, i) => {
        maxIndex++;
        const trimmedPrime = prime.trim();
        // For plain text, just convert newlines to <br>; for HTML, preserve formatting
        const primeHtml = trimmedPrime.includes('<') ? trimmedPrime : editorToHtmlNoP(trimmedPrime);
        const equivalent =
            equivalents[i]?.trim() ||
            sanitize(trimmedPrime).split(" i ").join(" I ");

        editingPrimes.value.push({
            prime: primeHtml,
            equivalent: equivalent,
            index: maxIndex,
            value: 1,
            config: {
                conditions: [],
            },
        });
    });

    bulkPrimesText.value = "";
    bulkEquivalentsText.value = "";

    // Force bulk QuillEditor to re-render by incrementing the key
    bulkQuillEditorKey.value++;
};

// Save primes from modal
const savePrimesFromModal = () => {
    // Validate that no prime in the table is effectively empty (no text and no image)
    const hasEmptyPrime = editingPrimes.value.some((item) => {
        if (!item) return true;
        const content = item.prime;
        if (!content) return true;

        const raw = String(content);
        // If there's an <img>, treat as non-empty
        if (/<img\b[^>]*>/i.test(raw)) return false;

        // Strip HTML tags (including &nbsp;) and check for real text
        const withoutNbsp = raw.replace(/&nbsp;/gi, " ");
        const textOnly = withoutNbsp.replace(/<[^>]*>/g, "").trim();
        return textOnly.length === 0;
    });

    if (hasEmptyPrime) {
        try {
            toast.error("Prime should not be empty");
        } catch (_) {}
        return;
    }

    if (selectedIndex.value) {
        // Update existing prime
        if (editingPrimes.value.length > 0) {
            const updatedPrime = editingPrimes.value[0];
            updatedPrime.rawPrimeElement = updatedPrime.prime;
            // Ensure conditions are properly saved
            if (updatedPrime.config && updatedPrime.config.conditions) {
                updatedPrime.config.conditions = [
                    ...updatedPrime.config.conditions,
                ];
            }
            form.primes[selectedIndex.value - 1] = updatedPrime;
        }
    } else {
        // Add new primes - ensure they get proper sequential indices
        const currentMaxIndex = getMaxIndex(form.primes);
        editingPrimes.value.forEach((item, index) => {
            item.index = currentMaxIndex + index + 1;
            // Ensure conditions array exists for new primes
            if (!item.config) {
                item.config = {};
            }
            if (!item.config.conditions) {
                item.config.conditions = [];
            }
            form.primes.push(item);
        });
    }
    // Show success toast after saving primes (create or update)
    // try {
    //     toast.success("Prime added successfully");
    // } catch (_) {}
    resetPrimeForm();
};

const getMaxIndex = (values) => {
    if (!values || values.length === 0) return 0;
    return maxBy(values, "index")?.index ?? 0;
};

// Function to renumber all primes sequentially (optional - not used by default)
// This preserves the original indices when deleting primes
const renumberPrimes = () => {
    const primes = form.primes || [];
    primes.forEach((prime, index) => {
        prime.index = index + 1;
    });
};

// Toggle for Prime editor (QuillEditor vs PrismEditor)
const enablePrimeQuillEditor = ref(true);

const deletePrime = (index) => {
    form.primes.splice(index, 1);
    // After deletion, renumber indices to keep them sequential
    renumberPrimes();
};

const updatePrimeDialog = (index) => {
    selectedIndex.value = index;
    _prime.value = assign(form.primes[index - 1]);
    _prime.value.prime = _prime.value.rawPrimeElement ?? _prime.value.prime;
    $state.criteria = _prime.value;

    // Populate editingPrimes with the current prime for editing in the modal
    editingPrimes.value = [assign(form.primes[index - 1])];
    editingPrimes.value[0].prime =
        editingPrimes.value[0].rawPrimeElement ?? editingPrimes.value[0].prime;

    // Ensure config.conditions array exists for skip logic
    if (!editingPrimes.value[0].config) {
        editingPrimes.value[0].config = {};
    }
    if (!editingPrimes.value[0].config.conditions) {
        editingPrimes.value[0].config.conditions = [];
    }

    // Update testState criteria to point to the editing prime
    $testState.criteria = editingPrimes.value[0];

    // Reset modal selection state
    selectedItemsInModal.value = new Set();
    selectAllInModal.value = false;
    bulkPrimesText.value = "";
    bulkEquivalentsText.value = "";
    newItemInModal.value = {
        prime: "",
        equivalent: "",
        value: 1,
    };
    quillEditorKey.value++;
    bulkQuillEditorKey.value++;

    modals.value.createPrime = true;
};

const updatePrimeList = () => {
    // ensure the raw HTML is kept in rawPrimeElement when saving edits
    _prime.value.rawPrimeElement = _prime.value.prime;
    form.primes[selectedIndex.value - 1] = _prime.value;
    resetPrimeForm();
};

const primeAction = () => {
    if (selectedIndex.value) {
        // update
        updatePrimeList();
    } else {
        addPrimeList();
    }
};

const showButtons = ref(false);

const toggleButtons = (index) => {
    const primes = form.primes || [];
    primes.forEach((p, i) => {
        p.showButtons = i === index ? !p.showButtons : false;
    });
};

// Hide all floating buttons when clicking outside
const hideAllButtons = () => {
    const primes = form.primes || [];
    primes.forEach((p) => {
        p.showButtons = false;
    });
};

// Add click outside listener
onMounted(() => {
    document.addEventListener('click', (event) => {
        // Check if the click is outside any floating button container
        const floatingButtons = document.querySelectorAll('[data-floating-buttons]');
        let clickedInside = false;
        
        floatingButtons.forEach(button => {
            if (button.contains(event.target)) {
                clickedInside = true;
            }
        });
        
        if (!clickedInside) {
            hideAllButtons();
        }
    });
});

onUnmounted(() => {
    document.removeEventListener('click', hideAllButtons);
});

// select and delete button function start
onBeforeMount(() => {
    $state.criteria = _prime.value;
    const primes = form.primes || [];
    primes.forEach((p) => {
        if (p.selected === undefined) p.selected = false;
        if (p.showButtons === undefined) p.showButtons = false;
    });
});

const csvInput = ref(null);
const csvInputModal = ref(null);

// Image URL modal state
const showImageUrlModal = ref(false);
const imageUrlsText = ref("");

// Use Variable as Primes modal state
const showUseVariableModal = ref(false);

const openUseVariableModal = () => {
    showUseVariableModal.value = true;
};

const createPrimeFromVariable = (variable) => {
    if (!variable) return;
    const variableName = variable.name || variable;
    const currentMaxIndex = getMaxIndex(form.primes);

    const prime = {
        prime: `{{${variableName}}}`,
        equivalent: `{{${variableName}}}`,
        index: currentMaxIndex + 1,
        value: 1,
        config: {
            conditions: [],
        },
    };

    form.primes.push(prime);
    showUseVariableModal.value = false;
};

// Listen for Variables modal apply event scoped to primes
function onPrimeVariableInsertRequest(e) {
    try {
        const raw = e?.detail?.name;
        if (!raw) return;
        createPrimeFromVariable(raw);
        showUseVariableModal.value = false;
    } catch (_) {}
}

if (typeof window !== "undefined") {
    try {
        window.addEventListener(
            "prime-variable-insert-request",
            onPrimeVariableInsertRequest
        );
    } catch (_) {}
}

const handleCSVUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    try {
        const text = await file.text();
        const lines = text.trim().split("\n");
        // Skip header row
        if (lines.length > 0) lines.shift();
        const formatted = lines
            .map((line) => line.split(",").join("\t")) // Tab-separated
            .join("\n");

        // Wrap in <pre> for CKEditor to preserve format
        _prime.value.prime = `<pre>${formatted}</pre>`;
    } catch (err) {
        console.error("Error reading CSV file:", err);
    }

    // Reset file input
    event.target.value = "";
};

// Trigger CSV upload for modal
const triggerCSVUpload = () => {
    csvInputModal.value?.click();
};

// Handle CSV upload for modal
const handleCSVUploadModal = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    try {
        const text = await file.text();
        const lines = text.trim().split("\n");
        // Skip header row
        if (lines.length > 0) lines.shift();

        // Proper CSV parsing function that handles quoted fields with commas
        const parseCSVLine = (line) => {
            const result = [];
            let current = "";
            let inQuotes = false;
            let i = 0;

            while (i < line.length) {
                const char = line[i];
                const nextChar = line[i + 1];

                if (char === '"') {
                    if (inQuotes && nextChar === '"') {
                        // Escaped quote
                        current += '"';
                        i += 2;
                    } else {
                        // Toggle quote state
                        inQuotes = !inQuotes;
                        i++;
                    }
                } else if (char === "," && !inQuotes) {
                    // End of field
                    result.push(current.trim());
                    current = "";
                    i++;
                } else {
                    current += char;
                    i++;
                }
            }

            // Add the last field
            result.push(current.trim());
            return result;
        };

        // Process CSV data - assume first column is primes, second column is equivalents
        const csvData = lines.map((line) => {
            const columns = parseCSVLine(line);
            return {
                prime: columns[0] || "",
                equivalent: columns[1] || "",
            };
        });

        // Separate primes and equivalents
        const primes = csvData
            .map((item) => item.prime)
            .filter((prime) => prime.trim());
        const equivalents = csvData.map((item) => item.equivalent);

        // Convert to plain text format for Quill editor (avoid HTML conversion)
        const primesText = primes.join("\n");

        const equivalentsText = equivalents.join("\n");

        // Populate the bulk input fields
        bulkPrimesText.value = primesText;
        bulkEquivalentsText.value = equivalentsText;
    } catch (err) {
        console.error("Error reading CSV file:", err);
        alert(
            "Error reading CSV file. Please make sure it's a valid CSV file."
        );
    }

    // Reset file input
    event.target.value = "";
};

// Download CSV template
const downloadCSVTemplate = () => {
    const exampleData = [
        {
            prime: "If I were completely satisfied, I would feel…",
            equivalent: "If I were completely satisfied, I would feel…",
        },
        {
            prime: "If I were completely satisfied, the features and benefits I would need are…",
            equivalent:
                "If I were completely satisfied, the features and benefits I would need are…",
        },
    ];

    // Convert data to CSV format
    const csvContent = exampleData
        .map((item) => `"${item.prime}","${item.equivalent}"`)
        .join("\n");

    // Add CSV header
    const csvWithHeader = `"Prime","Equivalent"\n${csvContent}`;

    // Create and download the file
    const blob = new Blob([csvWithHeader], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", "prime_template.csv");
    link.style.visibility = "hidden";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// Open modal to insert multiple image URLs
const openImageUrlModal = (quillInstance = null) => {
    try {
        if (quillInstance) {
            activeQuillEditor.value = quillInstance;
        } else {
            const editorComponent = primeQuillRef.value;
            if (
                editorComponent &&
                typeof editorComponent.getEditor === "function"
            ) {
                activeQuillEditor.value = editorComponent.getEditor();
            }
        }
        imageUrlsText.value = "";
        showImageUrlModal.value = true;
    } catch (_) {}
};

// // Helper to insert multiple images, one per line, removing selection
// const insertImagesFromModal = () => {
//     try {
//         const quill = activeQuillEditor.value;
//         if (!quill) {
//             showImageUrlModal.value = false;
//             imageUrlsText.value = "";
//             return;
//         }

//         const rawLines = String(imageUrlsText.value || "")
//             .split(/\r?\n/)
//             .map((s) => s.trim())
//             .filter(Boolean);
//         if (rawLines.length === 0) {
//             showImageUrlModal.value = false;
//             imageUrlsText.value = "";
//             return;
//         }

//         const range = quill.getSelection(true);
//         let insertIndex = range ? range.index : quill.getLength();

//         // Remove highlighted text if any
//         if (range && range.length > 0) {
//             quill.deleteText(range.index, range.length, "user");
//             insertIndex = range.index;
//         }

//         rawLines.forEach((url) => {
//             let finalUrl = url;
//             try {
//                 const u = new URL(url, window.location.origin);
//                 if (u.origin !== window.location.origin) {
//                     finalUrl = `/gallery-proxy?u=${encodeURIComponent(u.href)}`;
//                 }
//             } catch (_) {}

//             quill.insertEmbed(insertIndex, "image", finalUrl, "user");
//             insertIndex += 1;
//             // Ensure next image goes on a new line
//             quill.insertText(insertIndex, "\n", "user");
//             insertIndex += 1;
//         });

//         quill.setSelection(insertIndex, 0, "user");
//     } catch (_) {}
//     showImageUrlModal.value = false;
//     imageUrlsText.value = "";
// };

// Helper to insert multiple images, one per line, removing selection
const insertImagesFromModal = () => {
    try {
        const quill = activeQuillEditor.value;
        if (!quill) {
            showImageUrlModal.value = false;
            imageUrlsText.value = "";
            return;
        }

        const rawLines = String(imageUrlsText.value || "").split(/\r?\n/).map(s => s.trim()).filter(Boolean);
        if (rawLines.length === 0) {
            showImageUrlModal.value = false;
            imageUrlsText.value = "";
            return;
        }

        const range = quill.getSelection(true);
        let insertIndex = range ? range.index : quill.getLength();

        // Remove highlighted text if any
        if (range && range.length > 0) {
            quill.deleteText(range.index, range.length, "user");
            insertIndex = range.index;
        }

        rawLines.forEach((url) => {
            // let finalUrl = openImageUrlModal;
            // try {
            //     const u = new URL(url, window.location.origin);
            //     if (u.origin !== window.location.origin) {
            //         finalUrl = `/gallery-proxy?u=${encodeURIComponent(u.href)}`;
            //     }
            // } catch (_) {}

            quill.insertEmbed(insertIndex, "image", url, "user");
            insertIndex += 1;
            // Ensure next image goes on a new line
            quill.insertText(insertIndex, "\n", "user");
            insertIndex += 1;
        });

        quill.setSelection(insertIndex, 0, "user");
    } catch (_) {}
    showImageUrlModal.value = false;
    imageUrlsText.value = "";
};

// Override Quill toolbar positioning to start at the beginning of selection
const setupQuillToolbarPositioning = (quillInstance) => {
    if (!quillInstance) return;

    // Add custom image insertion button
    const toolbar =
        quillInstance.theme.tooltip.root.querySelector(".ql-toolbar");
    if (toolbar) {
        // Check if the custom button already exists
        if (!toolbar.querySelector(".ql-insertImageByUrl")) {
            const insertImageBtn = document.createElement("button");
            insertImageBtn.className = "ql-insertImageByUrl";
            insertImageBtn.type = "button";
            insertImageBtn.title = "Insert image by URL";

            insertImageBtn.style.cssText = `
                hover : color:white;

                color: #cccccc;
                border: none;
                border-radius: 4px;
                padding: 0px 5px;
                margin-left: 11px;
                margin-top: 9px;
                margin-right: -10px;
                cursor: pointer;
                font-size: 13px;
                font-weight: 500;
            `;
            insertImageBtn.innerHTML = `
             <svg viewBox="0 0 22 22"> <rect class="ql-stroke" height="10" width="12" x="3" y="4"></rect> <circle class="ql-fill" cx="6" cy="7" r="1"></circle> <polyline class="ql-even ql-fill" points="5 12 5 11 7 9 8 10 11 7 13 9 13 12 5 12"></polyline> </svg>
            `;
            insertImageBtn.addEventListener("click", () => {
                openImageUrlModal(quillInstance);
            });
            toolbar.appendChild(insertImageBtn);
        }
    }

    // Override the toolbar positioning method
    const originalShow = quillInstance.theme.tooltip.show;
    quillInstance.theme.tooltip.show = function () {
        originalShow.call(this);

        // Force toolbar to start at the left edge of the selection
        const toolbar = this.root.querySelector(".ql-toolbar");
        if (toolbar) {
            toolbar.style.position = "absolute";
            toolbar.style.right = "-451px";
            toolbar.style.top = "-40px";
            toolbar.style.backgroundColor = "#374151";
            toolbar.style.zIndex = "10000";
        }
    };

    // Also override the positioning on selection change
    quillInstance.on("selection-change", (range) => {
        if (range && range.length > 0) {
            // Small delay to ensure toolbar is rendered
            setTimeout(() => {
                const toolbar =
                    quillInstance.theme.tooltip.root.querySelector(
                        ".ql-toolbar"
                    );
                if (toolbar) {
                    toolbar.style.position = "absolute";
                    toolbar.style.left = "-30px";
                    toolbar.style.top = "0px";
                    toolbar.style.borderRadius = "5px";
                    toolbar.style.backgroundColor = "#374151";
                    toolbar.style.zIndex = "10000";
                }
            }, 10);
        }
    });
};

// Variables functionality for bubble theme
const addVariablesButtonToBubble = (quillInstance) => {
    if (!quillInstance) return;

    // Override the bubble toolbar to add Variables button
    const originalShow = quillInstance.theme.tooltip.show;
    quillInstance.theme.tooltip.show = function () {
        originalShow.call(this);

        const toolbar = this.root.querySelector(".ql-toolbar");
        if (toolbar && !toolbar.querySelector(".ql-variables-bubble")) {
            // Create Variables button
            const variablesButton = document.createElement("button");
            variablesButton.className = "ql-variables-bubble";
            variablesButton.type = "button";
            variablesButton.title = "Variables";
            variablesButton.innerHTML = "Variables";
            variablesButton.style.cssText = `
                hover : color:white;

                color: #cccccc;
                border: none;
                border-radius: 4px;
                padding: 10px 10px;
                margin-left: 4px;
                margin-right: 26px;
                cursor: pointer;
                font-size: 13px;
                font-weight: 500;
            `;

            // Add hover effects
            variablesButton.addEventListener("mouseenter", () => {
                variablesButton.style.backgroundColor = "";
            });
            variablesButton.addEventListener("mouseleave", () => {
                variablesButton.style.backgroundColor = "";
            });

            // Add click handler
            variablesButton.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();
                activeQuillEditor.value = quillInstance;
                showVariablesModal.value = true;
            });

            toolbar.appendChild(variablesButton);
        }
    };
};

const insertVariableIntoQuill = (variableName) => {
    if (!activeQuillEditor.value) return;

    const quill = activeQuillEditor.value;
    const range = quill.getSelection(true);
    const index = range ? range.index : quill.getLength();

    // Insert token text; the editor content is fed through parse on next render
    const token = `{{${variableName}}}`;
    quill.insertText(index, token, "user");
    quill.setSelection(index + token.length, 0);

    // Also update the equivalent field for the current editing item
    // Find which item is being edited by checking if this is the bulk editor or individual item editor
    if (quill.container && quill.container.closest('tbody')) {
        // This is an individual item editor in the table
        const row = quill.container.closest('tr');
        if (row) {
            const rowIndex = Array.from(row.parentNode.children).indexOf(row);
            if (editingPrimes.value[rowIndex]) {
                // Update the equivalent field with the same variable token
                editingPrimes.value[rowIndex].equivalent = token;
            }
        }
    } else if (quill.container && quill.container.closest('.quill-bubble-wrapper')) {
        // This is the bulk primes editor
        // Determine which line is being edited by analyzing the current cursor position
        const currentContent = bulkPrimesText.value || '';
        const primeLines = currentContent.split('\n');
        
        // Count lines up to the cursor position to determine which line we're on
        const textBeforeCursor = quill.getText(0, index);
        const linesBeforeCursor = textBeforeCursor.split('\n');
        const currentLineIndex = linesBeforeCursor.length - 1;
        
        // Update bulkEquivalentsText to include the variable token on the corresponding line
        const currentEquivalents = bulkEquivalentsText.value || '';
        const equivalentLines = currentEquivalents.split('\n');
        
        // Ensure we have enough lines in equivalents to match primes
        while (equivalentLines.length < primeLines.length) {
            equivalentLines.push('');
        }
        
        // Add the variable token to the current line
        if (equivalentLines[currentLineIndex] === '' || equivalentLines[currentLineIndex] === undefined) {
            equivalentLines[currentLineIndex] = token;
        } else {
            equivalentLines[currentLineIndex] += token;
        }
        
        bulkEquivalentsText.value = equivalentLines.join('\n');
    }

    showVariablesModal.value = false;
};

// Scoped event handler to support applying variables from IndexVariables inside Quill bubble
function onPrimeQuillVariableInsertRequest(e) {
    try {
        const raw = e?.detail?.name;
        if (!raw) return;
        insertVariableIntoQuill(raw);
    } catch (_) {}
}

if (typeof window !== "undefined") {
    try {
        window.addEventListener(
            "prime-quill-variable-insert-request",
            onPrimeQuillVariableInsertRequest
        );
    } catch (_) {}
}

const closeVariablesModal = () => {
    showVariablesModal.value = false;
    activeQuillEditor.value = null;
};

const allSelected = computed(() => {
    const primes = form.primes || [];
    return primes.length > 0 && primes.every((p) => p.selected);
});

const anySelected = computed(() => {
    const primes = form.primes || [];
    return primes.some((p) => p.selected);
});

const toggleSelectAll = () => {
    const val = !allSelected.value;
    const primes = form.primes || [];
    primes.forEach((p) => (p.selected = val));
};

const deleteSelected = () => {
    const primes = form.primes || [];
    for (let i = primes.length - 1; i >= 0; i--) {
        if (primes[i].selected) {
            primes.splice(i, 1);
        }
    }
    // After bulk deletion, renumber indices to keep them sequential
    renumberPrimes();
};
</script>

<template>
    <div class="flex flex-col">
        <div
            v-if="form.primes.length"
            class="w-full mt-2 bg-[#25755620] rounded-lg justify-center flex items-center"
        >
            <div class="flex items-center gap-2 p-2 py-3">
                <JetButton
                    class="uppercase"
                    @click="addPrimeDialog"
                    variant="default"
                    size="xs"
                >
                    Add custom primes
                </JetButton>
                <JetButton
                    class="uppercase"
                    @click="openUseVariableModal"
                    variant="default"
                    size="xs"
                >
                    Use variable as primes
                </JetButton>

                <JetButton
                    class="uppercase"
                    @click="showTemplatePanel = true"
                    variant="default"
                    size="xs"
                >
                    Load prime template
                </JetButton>

                <div class="flex flex-row gap-2">
                    <!-- Select Button -->
                    <JetButton
                        type="button"
                        class="px-3 py-1 font-bold rounded-full uppercase"
                        @click="toggleSelectAll"
                        variant="default"
                        size="xs"
                    >
                        {{ allSelected ? "Unselect All" : "Select All" }}
                    </JetButton>
                    <!-- Delete Button -->
                    <JetButton
                        type="button"
                        class="px-5 py-1 font-bold rounded-full uppercase"
                        :class="{
                            'opacity-50 cursor-not-allowed': !anySelected,
                        }"
                        :disabled="!anySelected"
                        @click="deleteSelected"
                        variant="danger"
                        size="xs"
                    >
                        Delete
                    </JetButton>

                    <!-- Expand and Shrink Button -->
                    <!-- <Button1 class="px-3 py-1 font-bold bg-white rounded-full " @click="toggleExpand">
					
					{{ isExpanded ? 'Shrink' : 'Expand' }}
				</Button1> -->
                </div>
            </div>
        </div>
        <div v-if="showTemplatePanel" class="mt-2">
            <div class="flex items-center gap-2">
                <select
                    v-model="selectedTemplate"
                    class="border-gray-300 rounded-md shadow-sm w-full"
                >
                    <option :value="null" disabled>Select a template</option>
                    <option
                        v-for="template in templatePrimes"
                        :key="template.id"
                        :value="template.id"
                    >
                        {{ template.name }}
                    </option>
                </select>
                <JetButton
                    variant="gray-100"
                    size="normal"
                    class="px-4 uppercase"
                    @click="loadTemplatePrimes"
                >
                    Load
                </JetButton>
            </div>
        </div>
        <div
            class="grid grid-cols-3 gap-3 mt-2 max-h-[63vmin] overflow-x-hidden overflow-y-auto c"
            v-if="form.primes.length"
        >
            <PrimeCard
                v-for="(prime, i) in form.primes"
                :key="i"
                class="col-span-3 m-2 md:col-span-1"
                :has-logic="
                    prime.hasOwnProperty('config') &&
                    prime.config &&
                    prime.config.conditions &&
                    prime.config.conditions.length > 0
                "
            >
                <template #n>
                    {{ i + 1 }}
                </template>

                <template #index>
                    {{ prime.index }}
                </template>

                <template #prime>
                    <span v-html="setProxiedHtml(prime.prime)"> </span>
                </template>

                <template #equivalent>
                    {{ prime.equivalent }}
                </template>

                <template #logic>
                    <span v-if="prime.hasOwnProperty('config')">
                        {{ $state.criteriaToString(prime.config.conditions) }}
                    </span>
                    <span v-else> None </span>
                </template>
                <template #actions>
                    <!-- Radio Button -->
                    <div class="checkbox-container">
                        <label class="ios-checkbox blue">
                            <input type="checkbox" v-model="prime.selected" />
                            <div class="checkbox-wrapper">
                                <div class="checkbox-bg"></div>
                                <svg
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    class="checkbox-icon"
                                >
                                    <path
                                        stroke-linejoin="round"
                                        stroke-linecap="round"
                                        stroke-width="3"
                                        stroke="currentColor"
                                        d="M4 12L10 18L20 6"
                                        class="check-path"
                                    />
                                </svg>
                            </div>
                        </label>
                    </div>

                    <div class="relative flex inline-block space-between">
                        <!-- Button -->
                        <button type="button" data-floating-buttons @click="toggleButtons(i)">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="w-6 h-6"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
                                ></path>
                            </svg>
                        </button>

                        <!-- Floating Hide Buttons -->
                        <div
                            v-show="prime.showButtons"
                            data-floating-buttons
                            class="absolute right-0 z-50 flex flex-col bg-white border rounded-lg shadow-lg top-6 w-36 animate-fade-in"
                        >
                            <a
                                href="javascript:;"
                                class="text-base px-2 py-1 gap-1 font-bold rounded-t-lg inline-flex items-center hover:bg-[#25755630]"
                                @click="updatePrimeDialog(i + 1)"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-5 opacity-80"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"
                                    />
                                    <path
                                        fill-rule="evenodd"
                                        d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Update
                            </a>
                            <a
                                href="javascript:;"
                                class="text-base px-2 py-1 gap-1 text-[#ef4444] font-bold rounded-b-lg inline-flex items-center hover:bg-[#ef444430]"
                                @click="deletePrime(i)"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-5 opacity-80"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Delete
                            </a>
                        </div>
                    </div>
                </template>
            </PrimeCard>
        </div>
        <!-- start if empty primes -->
        <div
            v-else
            class="flex items-center h-[65vmin] mx-auto text-gray-600 prose-2xl font-medium"
        >
            <div class="flex flex-col w-[60vmin] items-center gap-4">
                <JetButton
                    class="uppercase w-full flex flex-col items-center justify-center gap-1 py-4 text-center"
                    @click="addPrimeDialog"
                    variant="primary-light"
                    size="sm"
                >
                    <span class="font-semibold">Add custom primes</span>
                    <span
                        class="text-xs normal-case leading-snug opacity-80 px-6"
                    >
                        Create primes manually. Use this when you want full
                        control over each prime's content, order, and
                        equivalents.
                    </span>
                </JetButton>
                <JetButton
                    class="uppercase w-full flex flex-col items-center justify-center gap-1 py-4 text-center"
                    @click="openUseVariableModal"
                    variant="primary-dark"
                    size="sm"
                >
                    <span class="font-semibold">Use variable as primes</span>
                    <span
                        class="text-xs normal-case leading-snug opacity-80 px-6"
                    >
                        Select an existing variable to auto-generate primes from
                        its criteria. Each criterion becomes a prime with its
                        label and value.
                    </span>
                </JetButton>
                <JetButton
                    class="uppercase w-full flex flex-col items-center justify-center gap-1 py-4 text-center"
                    @click="showTemplatePanel = true"
                    variant="orange-light"
                    size="sm"
                >
                    <span class="font-semibold">Load prime template</span>
                    <span
                        class="text-xs normal-case leading-snug opacity-80 px-6"
                    >
                        Start from a ready-made prime set. Load a template and
                        adjust content as needed instead of building from
                        scratch.
                    </span>
                </JetButton>
            </div>
        </div>
        <!-- Template Management -->
        <div
            class="bg-white px-2 absolute bottom-7 w-full"
            v-if="form.primes.length"
        >
            <div class="flex items-center">
                <Toggle v-model="saveAsTemplate" />
                <div class="ml-1 flex flex-row gap-2">
                    <div
                        :class="{
                            'pointer-events-none opacity-80': !saveAsTemplate,
                        }"
                    >
                        <JetInput
                            v-model="templateForm.name"
                            :placeholder="
                                saveAsTemplate
                                    ? 'Template Name'
                                    : 'Save primes as template'
                            "
                            class="w-full text-sm"
                            :class="{
                                'border-transparent shadow-none':
                                    !saveAsTemplate,
                            }"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <JetDialogModal
        :show="modals.createPrime"
        @close="modals.createPrime = false"
        maxWidth="6xl"
    >
        <template #title>
            <span class="text-[#257556] font-bold">
                {{ selectedIndex ? "Update Prime" : "Add Custom Primes" }}
            </span>
        </template>

        <template #content>
            <div class="space-y-6 mt-6">
                <!-- Items Table -->
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <JetLabel value="Prime Items" />
                        <JetButton
                            variant="gray-100"
                            size="xs"
                            class="uppercase text-xs tracking-wide"
                            :disabled="!canUseTextToImage"
                            :class="{
                                'opacity-50 cursor-not-allowed': !canUseTextToImage,
                            }"
                            @click="openTextToImageModal"
                        >
                            Text to Image
                        </JetButton>
                        <div class="flex items-center gap-2">
                            <Toggle v-model="enablePrimeQuillEditor" />
                            <span class="text-sm text-gray-600">Editor</span>
                        </div>
                    </div>

                    <!-- Bulk Delete Actions - Show always when not updating -->
                    <div
                        v-if="!selectedIndex"
                        class="flex items-center justify-between bg-gray-100/80 p-4 rounded-lg h-12"
                    >
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="selectAllInModal"
                                    @change="toggleSelectAllInModal"
                                    :disabled="editingPrimes.length === 0"
                                    class="rounded border-gray-300 text-[#257556] focus:ring-[#257556] disabled:opacity-50 disabled:cursor-not-allowed"
                                />
                                <span
                                    class="ml-2 text-sm font-medium text-gray-700"
                                >
                                    Select All ({{ selectedItemsInModal.size }}
                                    selected)
                                </span>
                            </label>
                        </div>
                        <div class="flex space-x-2">
                            <JetButton
                                v-if="selectedItemsInModal.size > 0"
                                variant="danger"
                                size="sm"
                                @click="deleteSelectedItemsInModal"
                            >
                                Delete Selected ({{
                                    selectedItemsInModal.size
                                }})
                            </JetButton>
                        </div>
                    </div>

                    <div
                        class="mt-2 max-h-96 overflow-y-auto border border-gray-200 rounded-md"
                    >
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-[#D4ECEF] text-base sticky top-0 z-10">
                                <tr>
                                    <th
                                        v-if="!selectedIndex"
                                        class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-1"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="selectAllInModal"
                                            @change="toggleSelectAllInModal"
                                            :disabled="
                                                editingPrimes.length === 0
                                            "
                                            class="rounded border-gray-300 text-[#257556] focus:ring-[#257556] disabled:opacity-50 disabled:cursor-not-allowed"
                                        />
                                    </th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-1/12"
                                    >
                                        Index
                                    </th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-7/12"
                                    >
                                        Prime
                                    </th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-4/12"
                                    >
                                        Equivalent
                                    </th>
                                    <th
                                        v-if="!selectedIndex"
                                        class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-1/12"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <tr
                                    v-for="(item, index) in editingPrimes"
                                    :key="index"
                                    :class="{
                                        'bg-[#25755630]':
                                            !selectedIndex &&
                                            selectedItemsInModal.has(index),
                                    }"
                                >
                                    <td v-if="!selectedIndex" class="px-3 py-1">
                                        <input
                                            type="checkbox"
                                            :checked="
                                                selectedItemsInModal.has(index)
                                            "
                                            @change="
                                                toggleItemSelectionInModal(
                                                    index
                                                )
                                            "
                                            class="rounded border-gray-300 text-[#257556] focus:ring-[#257556]"
                                        />
                                    </td>
                                    <td class="px-3 py-1">
                                        <div
                                            :model-value="item.index"
                                            @update:model-value="
                                                updateItemFieldInModal(
                                                    index,
                                                    'index',
                                                    $event
                                                )
                                            "
                                            type="text"
                                            class="w-full h-[34px] text-sm bg-gray-50 border border-gray-300 rounded-md px-3 py-2 flex items-center"
                                            placeholder="Index"
                                        >
                                        {{ item.index }}
                                    </div>
                                    </td>
                                    <td class="px-3 py-1 w-7/12">
                                        <div v-if="enablePrimeQuillEditor" class="quill-bubble-wrapper border border-gray-300 rounded-md">
                                            <QuillEditor
                                                theme="bubble"
                                                :content="parseVariablesToElements(item.prime)"
                                                @update:content="
                                                    updateItemFieldInModal(
                                                        index,
                                                        'prime',
                                                        parseElementsToVariables($event)
                                                    )
                                                "
                                                @ready="
                                                    (quill) => {
                                                        setupQuillToolbarPositioning(
                                                            quill
                                                        );
                                                        addVariablesButtonToBubble(
                                                            quill
                                                        );
                                                    }
                                                "
                                                contentType="html"
                                                placeholder="Click to edit prime content..."
                                                :toolbar="[
                                                    [
                                                        {
                                                            header: [
                                                                1,
                                                                2,
                                                                3,
                                                                false,
                                                            ],
                                                        },
                                                    ],
                                                    [
                                                        'bold',
                                                        'italic',
                                                        'underline',
                                                    ],
                                                    [{ align: [] }],
                                                    [
                                                        { script: 'sub' },
                                                        { script: 'super' },
                                                    ],
                                                    [
                                                        { color: [] },
                                                        { background: [] },
                                                    ],
                                                ]"
                                                class="min-h-[34px]"
                                            />
                                        </div>
                                        <PrismEditor
                                            v-else
                                            class="my-editor editor-height border border-gray-300 rounded-md"
                                            :model-value="parseElementsToVariables(item.prime)"
                                            @update:model-value="
                                                updateItemFieldInModal(
                                                    index,
                                                    'prime',
                                                    $event
                                                )
                                            "
                                            :highlight="highlighter"
                                            line-numbers
                                        />
                                    </td>
                                    <td class="px-3 py-1 w-4/12">
                                        <JetInput
                                            :model-value="item.equivalent"
                                            @update:model-value="
                                                updateItemFieldInModal(
                                                    index,
                                                    'equivalent',
                                                    $event
                                                )
                                            "
                                            type="text"
                                            class="w-full text-sm"
                                            placeholder="Equivalent"
                                        />
                                    </td>
                                    <td
                                        v-if="!selectedIndex"
                                        class="px-3 py-1 w-1/12"
                                    >
                                        <div
                                            class="bg-white p-2 border shadow-sm rounded-md justify-center items-center flex"
                                        >
                                            <button
                                                type="button"
                                                @click="
                                                    deleteItemFromModal(index)
                                                "
                                                class="text-red-600 hover:text-red-800 transition-colors duration-200"
                                                title="Delete this prime"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-6 h-6"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Show empty state when no primes -->
                                <tr v-if="editingPrimes.length === 0">
                                    <td
                                        :colspan="selectedIndex ? 3 : 4"
                                        class="px-3 py-8 text-center text-gray-500"
                                    >
                                        No primes added yet. Use the form below
                                        to add primes.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Bulk Delete Actions -->
                <!-- <div
                    v-if="editingPrimes.length > 0"
                    class="flex items-center justify-between bg-gray-100/80 p-4 rounded-lg h-12"
                >
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="selectAllInModal"
                                @change="toggleSelectAllInModal"
                                class="rounded border-gray-300 text-[#257556] focus:ring-[#257556]"
                            />
                            <span class="ml-2 text-sm font-medium text-gray-700">
                                Select All ({{ selectedItemsInModal.size }} selected)
                            </span>
                        </label>
                    </div>
                    <div class="flex space-x-2">
                        <JetButton
                            v-if="selectedItemsInModal.size > 0"
                            variant="danger"
                            size="xs"
                            @click="deleteSelectedItemsInModal"
                        >
                            Delete Selected ({{ selectedItemsInModal.size }})
                        </JetButton>
                    </div>
                </div> -->

                <!-- Add New Item / Bulk Add - Only show when not updating -->
                <div v-if="!selectedIndex" class="">
                    <!-- <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-medium text-gray-900">Add Prime</h4>
                    </div> -->

                    <!-- Single add row -->
                    <!-- <div class="grid grid-cols-11 gap-3 mb-4">
                        <div class="col-span-5">
                            <div class="quill-bubble-wrapper border border-gray-300 rounded-md">
                                <QuillEditor
                                    :key="quillEditorKey"
                                    theme="bubble"
                                    :content="newItemInModal.prime"
                                    @update:content="newItemInModal.prime = $event"
                                    @ready="(quill) => { setupQuillToolbarPositioning(quill); addVariablesButtonToBubble(quill); }"
                                    contentType="html"
                                    placeholder="Click to edit prime content..."
                                    :toolbar="[
                                        [{ 'header': [1, 2, 3, false] }],
                                        ['bold', 'italic', 'underline'],
                                        [{ 'align': [] }],
                                        [{ 'script': 'sub'}, { 'script': 'super' }],
                                        [{ 'color': [] }, { 'background': [] }]
                                    
                                      
                                    ]"
                                    class="min-h-[40px] text-sm"
                                />
                            </div>
                        </div>
                        <div class="col-span-5">
                            <JetInput
                                v-model="newItemInModal.equivalent"
                                type="text"
                                placeholder="Equivalent (optional)"
                                class="w-full min-h-[40px] text-sm border border-gray-300 rounded-md focus:ring-[#257556] focus:border-[#257556]"
                            />
                        </div>
                        <div class="col-span-1">
                            <JetButton
                                @click="addNewItemInModal"
                                variant="slate"
                                class="text-sm py-1 h-[40px] w-full text-center justify-center"
                            >
                                Add
                            </JetButton>
                        </div>
                    </div> -->

                    <!-- Bulk add for primes -->
                    <div class="mt-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="flex items-center justify-between">
                                    
                                    <JetLabel value="Primes (one per line)" />
                            
                                    <div>
                                       
                                        <Buttons @click="openImageUrlModal(bulkPrimesQuill)">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M16.25 3.125H3.75C2.71447 3.125 1.875 3.96447 1.875 5V15C1.875 16.0355 2.71447 16.875 3.75 16.875H16.25C17.2855 16.875 18.125 16.0355 18.125 15V5C18.125 3.96447 17.2855 3.125 16.25 3.125Z" stroke="#323232" stroke-linejoin="round"/>
                                            <path d="M13.125 8.125C13.8154 8.125 14.375 7.56536 14.375 6.875C14.375 6.18464 13.8154 5.625 13.125 5.625C12.4346 5.625 11.875 6.18464 11.875 6.875C11.875 7.56536 12.4346 8.125 13.125 8.125Z" stroke="#323232" stroke-miterlimit="10"/>
                                            <path d="M11.875 13.1166L8.33359 9.58184C8.10824 9.35654 7.80535 9.22581 7.48684 9.21637C7.16832 9.20693 6.85822 9.3195 6.61992 9.53106L1.875 13.7498M8.75 16.8748L13.568 12.0568C13.7883 11.8361 14.0832 11.7056 14.3948 11.6912C14.7064 11.6767 15.0121 11.7793 15.252 11.9787L18.125 14.3748" stroke="#323232" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </Buttons>
                                    
                                    </div>

                                </div>



                                <div
                                    class="quill-bubble-wrapper border border-gray-300 rounded-md mt-1 c h-[120px]"
                                >
                                    <QuillEditor
                                        :key="bulkQuillEditorKey"
                                        theme="bubble"
                                        :content="bulkPrimesText"
                                        @update:content="
                                            bulkPrimesText = $event
                                        "
                                        @ready="
                                            (quill) => {
                                                    bulkPrimesQuill = quill;
                                                setupQuillToolbarPositioning(
                                                    quill
                                                );
                                                addVariablesButtonToBubble(
                                                    quill
                                                );
                                            }
                                        "
                                        contentType="html"
                                        placeholder="Enter primes, one per line..."
                                        :toolbar="[
                                            [{ header: [1, 2, 3, false] }],
                                            ['bold', 'italic', 'underline'],
                                            [{ align: [] }],
                                            [
                                                { script: 'sub' },
                                                { script: 'super' },
                                            ],
                                            [{ color: [] }, { background: [] }],
                                        ]"
                                        class="min-h-[120px] text-sm flex flex-col"
                                    />
                                </div>
                            </div>
                            <div>
                                <JetLabel
                                    value="Equivalents (align by line with primes)"
                                />
                                        <textarea
                                            v-model="bulkEquivalentsText"
                                    placeholder=""
                                    class="mt-1 w-full min-h-[120px] text-sm border border-gray-300 rounded-md focus:ring-[#257556] focus:border-[#257556]"
                                ></textarea>
                                <p class="mt-1 text-xs text-gray-500">
                                    Leave a line blank if a prime has no
                                    equivalent.
                                </p>
                            </div>
                        </div>
                        <div class="flex justify-start -mt-4 gap-2">
                            <JetButton
                                variant="slate"
                                @click="addBulkPrimesInModal"
                                class="text-sm"
                            >
                                Add Primes
                            </JetButton>

                            <div class="relative group">
                            <JetButton
                                variant="gray-100"
                                @click="triggerCSVUpload"
                                class="text-sm"
                            >
                                Upload CSV
                            </JetButton>

                            <div
                                class="absolute bottom-full left-[-90px] mb-1.5 px-2 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                role="tooltip"
                                >
                                Use this option to import a list of primes from a file.<br/> The CSV file must be formatted correctly to match<br/> the platform's required structure.                                </div>
                            </div>
                      


                            <div class="relative group">
                            <JetButton
                                variant="gray-100"
                                @click="downloadCSVTemplate"
                                class="text-sm"
                            >
                            Download CSV Template
                            </JetButton>

                            <div
                                class="absolute bottom-full left-[-30px] mb-1.5 px-2 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                role="tooltip"
                                >
                                Click this to download a blank template file.<br/>Use this template to ensure your primes and <br/> equivalents are structured correctly before <br/>using the Upload CSV function. 
                                </div>
                            </div>


                            <input
                                ref="csvInputModal"
                                type="file"
                                accept=".csv"
                                @change="handleCSVUploadModal"
                                class="hidden"
                            />
                        </div>
                    </div>
                </div>

                <!-- Skip Logic Section - Only show when updating a prime -->
                <div v-if="selectedIndex" class="mt-6">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-medium text-gray-900">
                            Skip Logic
                        </h4>
                        <JetButton
                            type="button"
                            variant="rose"
                            size="xs"
                            @click.prevent="addSegment"
                        >
                            <span
                                v-if="
                                    editingPrimes[0]?.config?.conditions
                                        ?.length > 0
                                "
                                >Add Segment</span
                            >
                            <span v-else>Add Condition</span>
                        </JetButton>
                    </div>

                    <div
                        v-for="(condition, index) in editingPrimes[0]?.config
                            ?.conditions || []"
                        :key="index"
                        class="relative mb-4"
                    >
                        <TestCriteria
                            :deep="0"
                            :condition="condition"
                            :indexes="[index]"
                            @delete="deleteCondition"
                        />
                    </div>

                    <div
                        v-if="editingPrimes[0]?.config?.conditions?.length > 0"
                        class="mt-3"
                    >
                        <div class="text-xs text-gray-600 mb-1">
                            Current logic:
                        </div>
                        <code
                            class="text-xs bg-gray-200 text-black-500 p-2 rounded block"
                        >
                            HIDDEN IF:
                            {{
                                $testState.criteriaToString(
                                    editingPrimes[0]?.config?.conditions || []
                                )
                            }}
                        </code>
                    </div>

                    <div v-else class="text-gray-500 text-sm">
                        <p>
                            No skip logic conditions set. Click "Add Condition"
                            to create skip logic for this prime.
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <JetButton variant="default" @click="resetPrimeForm" class="mr-2">
                CANCEL
            </JetButton>
            <JetButton 
                variant="primary" 
                @click="savePrimesFromModal"
                :disabled="editingPrimes.length === 0"
                :class="{
                    'opacity-50 cursor-not-allowed': editingPrimes.length === 0
                }"
            >
                {{ selectedIndex ? "UPDATE PRIME" : "SAVE PRIMES" }}
            </JetButton>
        </template>
    </JetDialogModal>

    <!-- Variables Modal -->
    <JetDialogModal :show="showVariablesModal" @close="closeVariablesModal" maxWidth="4xl">
        <!-- <template #title>Variables</template> -->
        <template #content>
            <IndexVariables :project="$page.props.project" :survey="survey" :enable-apply="true" apply-event-name="prime-quill-variable-insert-request" />
        </template>
        <template #footer>
            <JetButton
                variant="default"
                class="uppercase"
                size="sm"
                @click="closeVariablesModal"
            >
                Close
            </JetButton>
        </template>
    </JetDialogModal>

    <!-- Use Variable as Primes Modal -->
    <JetDialogModal :show="showUseVariableModal" @close="() => { showUseVariableModal = false; }" maxWidth="2xl">
        <!-- <template #title>Select a variable</template> -->
        <template #content>
            <IndexVariables :project="$page.props.project" :survey="survey" :enable-apply="true" apply-event-name="prime-variable-insert-request" />
        </template>
        <template #footer>
            <JetButton
                variant="default"
                class="uppercase"
                size="sm"
                @click="() => { showUseVariableModal = false; }"
            >
                Close
            </JetButton>
        </template>
    </JetDialogModal>

    <JetDialogModal
        :show="showTextToImageModal"
        @close="closeTextToImageModal"
        maxWidth="md"
    >
        <template #title>
            <span class="text-[#257556] font-bold">Convert Text to Image</span>
        </template>
        <template #content>
            <div class="space-y-4">
                <div>
                    <JetLabel value="Text to find" />
                    <JetInput
                        v-model="textToImageText"
                        type="text"
                        class="w-full mt-1"
                        placeholder="Enter the exact text you want to convert"
                    />
                </div>
                <div>
                    <JetLabel value="Image URL" />
                    <JetInput
                        v-model="textToImageImageUrl"
                        type="text"
                        class="w-full mt-1"
                        placeholder="https://example.com/image.png"
                    />
                </div>
                <p v-if="textToImageFeedback" class="text-sm text-red-600">
                    {{ textToImageFeedback }}
                </p>
            </div>
        </template>
        <template #footer>
            <JetButton variant="default" class="mr-2" @click="closeTextToImageModal">
                Cancel
            </JetButton>
            <JetButton variant="primary" @click="convertTextToImageInPrimeItems">
                Convert
            </JetButton>
        </template>
    </JetDialogModal>

    <!-- Image URLs Modal -->
    <JetDialogModal
        :show="showImageUrlModal"
        @close="
            () => {
                showImageUrlModal = false;
            }
        "
        maxWidth="lg"
    >
        <template #title>
            <span class="text-[#257556] font-bold">Insert image URLs</span>
        </template>
        <template #content>
            <div class="space-y-3">
                <JetLabel value="Image URLs (one per line)" />
                <textarea
                    v-model="imageUrlsText"
                    placeholder="https://exemplo.com/imagem.png"
                    class="mt-1 w-full min-h-[140px] text-sm border border-gray-300 rounded-md focus:ring-[#257556] focus:border-[#257556]"
                ></textarea>
                <p class="text-xs text-gray-500">
                    Each non-empty line will be inserted as an image on its own
                    line.
                </p>
            </div>
        </template>
        <template #footer>
            <JetButton
                variant="default"
                class="mr-2"
                @click="
                    () => {
                        showImageUrlModal = false;
                    }
                "
                >CANCEL</JetButton
            >
            <JetButton variant="primary" @click="insertImagesFromModal"
                >INSERT</JetButton
            >
        </template>
    </JetDialogModal>
</template>
<style scoped="true">
/* .c {
    max-height: calc(100vh - 260px);
} */

/* From Uiverse.io by kyle1dev */
.checkbox-container {
    display: flex;
    gap: 3px;
    padding: 3px;

    border-radius: 5px;
    /* box-shadow:
    0 4px 6px -1px rgba(0, 0, 0, 0.1),
    0 2px 4px -2px rgba(0, 0, 0, 0.05); */
}

.ios-checkbox {
    --checkbox-size: 25px;
    --checkbox-color: #257556;

    --checkbox-border: #93c5fd;

    position: relative;
    display: inline-block;
    cursor: pointer;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
}

.ios-checkbox input {
    display: none;
}

.checkbox-wrapper {
    position: relative;
    width: var(--checkbox-size);
    height: var(--checkbox-size);
    border-radius: 8px;
    transition: transform 0.2s ease;
}

.checkbox-bg {
    position: absolute;
    inset: 0;
    border-radius: 20px;
    border: 2px solid var(--checkbox-border);
    background: white;
    transition: all 0.2s ease;
}

.checkbox-icon {
    position: absolute;
    inset: 0;
    margin: auto;
    width: 70%;
    height: 70%;
    color: white;
    transform: scale(0);
    transition: all 0.2s ease;
}

.check-path {
    stroke-dasharray: 40;
    stroke-dashoffset: 40;
    transition: stroke-dashoffset 0.3s ease 0.1s;
}

/* Checked State */
.ios-checkbox input:checked + .checkbox-wrapper .checkbox-bg {
    background: var(--checkbox-color);
    border-color: var(--checkbox-color);
}

.ios-checkbox input:checked + .checkbox-wrapper .checkbox-icon {
    transform: scale(1);
}

.ios-checkbox input:checked + .checkbox-wrapper .check-path {
    stroke-dashoffset: 0;
}

/* Hover Effects */
.ios-checkbox:hover .checkbox-wrapper {
    transform: scale(1.05);
}

/* Active Animation */
.ios-checkbox:active .checkbox-wrapper {
    transform: scale(0.95);
}

/* Focus Styles */
.ios-checkbox input:focus + .checkbox-wrapper .checkbox-bg {
    box-shadow: 0 0 0 4px var(--checkbox-bg);
}

/* Color Themes */
.ios-checkbox.blue {
    --checkbox-color: #257556;
    --checkbox-bg: #dbeafe;
    --checkbox-border: #257556;
}

.ios-checkbox.green {
    --checkbox-color: #10b981;
    --checkbox-bg: #257556;
    --checkbox-border: #257556;
}

.ios-checkbox.purple {
    --checkbox-color: #8b5cf6;
    --checkbox-bg: #ede9fe;
    --checkbox-border: #c4b5fd;
}

.ios-checkbox.red {
    --checkbox-color: #ef4444;
    --checkbox-bg: #fee2e2;
    --checkbox-border: #fca5a5;
}

/* Animation */
@keyframes bounce {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

.ios-checkbox input:checked + .checkbox-wrapper {
    animation: bounce 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.2s ease-out;
}

.custom-scrollbar {
    scrollbar-width: thin; /* For Firefox */
    scrollbar-color: #257556 transparent; /* thumb and track color */
    max-height: calc(100vh - 166px);
}

/* For WebKit (Chrome, Safari, Edge) */
.custom-scrollbar::-webkit-scrollbar {
    width: 1px; /* Make scrollbar thin */
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #257556; /* light gray thumb */
    border-radius: 1px;
}

/* Quill Bubble Editor Custom Styles */
.quill-bubble-wrapper {
    position: relative;
}

.ql-toolbar {
    background-color: #374151 !important;
    color: #050505 !important;
}

.quill-bubble-wrapper .ql-toolbar {
    position: absolute !important;
    top: -40px !important;
    left: 0 !important;
    background: #374151;
    border-radius: 8px;
    padding: 8px 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -2px rgba(0, 0, 0, 0.05);
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 8px;
    transform: none !important;
}

.quill-bubble-wrapper .ql-toolbar::before {
    content: "";
    position: absolute;
    top: 100%;
    left: 0px; /* Start at the very left edge */
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid #374151;
}

.quill-bubble-wrapper .ql-toolbar .ql-formats {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 4px;
}

.quill-bubble-wrapper .ql-toolbar button {
    color: #d1d5db;
    background: transparent;
    border: none;
    padding: 4px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.quill-bubble-wrapper .ql-toolbar button:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.quill-bubble-wrapper .ql-toolbar button.ql-active {
    background: #257556;
    color: white;
}

.quill-bubble-wrapper .ql-toolbar .ql-picker {
    color: #d1d5db;
}

.quill-bubble-wrapper .ql-toolbar .ql-picker-label {
    color: #d1d5db;
    background: transparent;
    border: none;
    padding: 4px 8px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.quill-bubble-wrapper .ql-toolbar .ql-picker-label:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.quill-bubble-wrapper .ql-toolbar .ql-picker.ql-expanded .ql-picker-label {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.quill-bubble-wrapper .ql-toolbar .ql-picker-options {
    background: #374151;
    border: 1px solid #4b5563;
    border-radius: 6px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.quill-bubble-wrapper .ql-toolbar .ql-picker-item {
    color: #d1d5db;
    padding: 8px 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.quill-bubble-wrapper .ql-toolbar .ql-picker-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.quill-bubble-wrapper .ql-editor {
    min-height: 40px;
    padding: 8px 12px;
    border: none;
    outline: none;
    font-size: 14px;
    line-height: 1.5;
}

.quill-bubble-wrapper .ql-editor.ql-blank::before {
    color: #9ca3af;
    font-style: normal;
    left: 12px;
    right: 12px;
}

.quill-bubble-wrapper .ql-container {
    border: none;
    font-family: inherit;
}

.quill-bubble-wrapper .ql-container.ql-bubble {
    border: none;
}

/* Ensure the toolbar appears above other elements */
.quill-bubble-wrapper .ql-toolbar {
    position: absolute !important;
    top: -50px !important;
    left: 0 !important;
    z-index: 10000;
    transform: none !important;
}

/* Override Quill's default positioning for bubble toolbar */
.quill-bubble-wrapper .ql-bubble .ql-tooltip {
    position: absolute !important;
    left: 0 !important;
    transform: none !important;
    z-index: 10000 !important;
}

.quill-bubble-wrapper .ql-bubble .ql-tooltip .ql-toolbar {
    position: absolute !important;
    left: 0 !important;
    top: -40px !important;
    z-index: 10000 !important;
}

/* Bring active/focused editor above neighbors */
.quill-bubble-wrapper:focus-within {
    z-index: 10000;
}

/* Custom image insertion button styling */
.quill-bubble-wrapper .ql-toolbar .ql-insertImageByUrl {
    color: #d1d5db;
    background: transparent;
    border: none;
    padding: 4px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.quill-bubble-wrapper .ql-toolbar .ql-insertImageByUrl:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.quill-bubble-wrapper .ql-toolbar .ql-insertImageByUrl svg {
    width: 16px;
    height: 16px;
}

/* Variables button styling in bubble toolbar */
.quill-bubble-wrapper .ql-toolbar .ql-variables-bubble {
    color: white !important;
    border: none !important;
    border-radius: 4px !important;
    padding: 4px 8px !important;
    margin-left: 4px !important;
    cursor: pointer !important;
    font-size: 12px !important;
    font-weight: 500 !important;
    transition: all 0.2s ease !important;
}

.quill-bubble-wrapper .ql-toolbar .ql-variables-bubble:hover {
    color: white !important;
}

.quill-bubble-wrapper .ql-toolbar .ql-variables-bubble:focus {
    outline: none !important;
}


::deep(.ql-variable) {
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: 600;
    background: #257556;
    color: #fff;
    display: inline;
    margin: 0 2px;
    cursor: not-allowed !important;
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    box-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    border: 1px solid #1d5e45;
    transition: all 0.2s ease;
}

:deep(.ql-editor img) {
  display: inline-block;
  max-width: 50px;
  max-height: 50px;
  margin: 0 4px;
  vertical-align: middle;
  border-radius: 4px;
}

:deep(.ql-editor p) {
    display: inline;
}


:deep(.ql-editor) {
    display: flex;
    flex-direction: column;
}

/* PrismEditor styles for Prime column */
.editor-height {
    min-height: 34px;
    max-height: 80px;
    font-size: 12px;
    max-width: 550px;
}

:deep(.my-editor) {
    border-radius: 6px;
}

:deep(.my-editor .prism-editor__textarea) {
    font-size: 12px;
    line-height: 1.5;
    min-height: 34px;
}

:deep(.my-editor .prism-editor__line-numbers) {
    padding: 8px 4px;
    font-size: 12px;
}
</style>
