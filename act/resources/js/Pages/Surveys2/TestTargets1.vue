<script setup>
import { QuillEditor } from "@vueup/vue-quill";
import "quill/dist/quill.snow.css";
// Import CK Editor Components
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import CKEditor from "@ckeditor/ckeditor5-vue";
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

import { inject, ref, onBeforeMount, onMounted, onUnmounted, computed } from "vue";
import CustomSwitch from "@/Components/Switch.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TargetCard from "@/Components/TargetCard.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetButton from "@/Componentsv2/Button.vue";
import CustomInput from "@/Components/Input.vue";
import JetCheckbox from "@/Jetstream/Checkbox.vue";
import JetLabel from "@/Jetstream/Label.vue";
import JetInput from "@/Jetstream/Input.vue";
import TextArea from "@/Components/TextArea.vue";
import Toggle from "@/Jetstream/Toggle.vue";

import Helpers from "@/Mixins/Helpers";
import { useForm } from "@inertiajs/vue3";
import maxBy from "lodash/maxBy";
import forEach from "lodash/forEach";
import { setProxiedHtml } from "@/utils/setProxiedHtml";
import {
    addVariablesToolbarButton,
    PLACEHOLDER_TOKEN,
} from "@/Components/QuillEditor.vue";
import IndexVariables from "@/Pages/Variables2/Index.vue";

const { sanitize, capitalize, hasImage, assign, toast } = Helpers();

const props = defineProps([
    "templateTargets",
    "project",
    "survey",
    "variables",
]);

const selectedTemplate = ref(null);
const saveAsTemplate = ref(false);
const showTemplatePanel = ref(false);

// Variables functionality
const showVariablesModal = ref(false);
const activeQuillEditor = ref(null);
const variables = props.variables || [];

const templateForm = useForm({
    name: "",
    template_data: [],
});

const loadTemplateTargets = () => {
    if (selectedTemplate.value) {
        const template = props.templateTargets.find(
            (t) => t.id === selectedTemplate.value
        );
        if (template) {
            form.targets = template.data;
        }
    }
    showTemplatePanel.value = false;
};

const saveTemplateTargets = async () => {
    templateForm.template_data = form.targets;
    await templateForm.post(
        route("templates.targets.store", {
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
const saveTemplateTargetsIfNeeded = async () => {
    if (saveAsTemplate.value && templateForm.name) {
        await saveTemplateTargets();
    }
};

// Expose the saveTemplate function to parent component
defineExpose({
    saveTemplateTargets,
    saveTemplateTargetsIfNeeded,
    saveAsTemplate,
});

// const deleteTemplate = () => {
//     if (selectedTemplate.value) {
//         router.delete(
//             route("templates.targets.destroy", {
//                 templateTarget: selectedTemplate.value,
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

const enableCK = inject("ck");
const ckeditor = CKEditor.component;
const editor = ref(ClassicEditor);
const targetQuillRef = ref(null);
const selectedIndex = ref(false);

const form = inject("form");

const editorConfig = ref({
    extraPlugins: [UploadAdapterPlugin],
});

const modals = ref({
    createTarget: false,
});
const _target = ref({
    target: "",
    equivalent: "",
    can_unselect: false,
    colors: {
        normal: null,
        hover: null,
        active: null,
    },
    value: 0,
    index: null,
});

// For bulk editing in modal
const editingTargets = ref([]);
const selectedItemsInModal = ref(new Set());
const selectAllInModal = ref(false);
const bulkTargetsText = ref("");
const bulkEquivalentsText = ref("");
const newItemInModal = ref({
    target: "",
    equivalent: "",
    colors: {
        normal: null,
        hover: null,
        active: null,
    },
    value: 0,
});
const quillEditorKey = ref(0);
const bulkQuillEditorKey = ref(0);
const bulkTargetsQuill = ref(null);
const showTextToImageModal = ref(false);
const textToImageText = ref("");
const textToImageImageUrl = ref("");
const textToImageFeedback = ref("");
const canUseTextToImage = computed(() => editingTargets.value.length > 0);
const highlighter = (code) => {
    return highlight(code, languages.markup);
};

const stripParagraphsAroundImages = (html) => {
    if (!html) return html;
    try {
        // Only strip <p> tags that contain images (and whitespace) with no other content
        return String(html).replace(
            /<p([^>]*)>([\s\S]*?)<\/p>/gi,
            (match, attrs, content) => {
                const contentWithoutImages = content
                    .replace(/<(?:img|picture)\b[^>]*>/gi, "")
                    .trim();
                if (contentWithoutImages) {
                    return match; // keep <p> when there is text or other elements
                }
                return content.trim();
            }
        );
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

const convertTextToImageInTargetItems = () => {
    textToImageFeedback.value = "";
    const searchText = textToImageText.value.trim();
    const imageUrl = textToImageImageUrl.value.trim();

    if (!searchText || !imageUrl) {
        textToImageFeedback.value = "Both fields are required.";
        return;
    }

    let replacements = 0;
    editingTargets.value.forEach((item, index) => {
        if (!item || typeof item.target !== "string") return;
        if (!item.target.includes(searchText)) return;
        const updatedTarget = stripParagraphsAroundImages(
            replaceTextWithImage(item.target, searchText, imageUrl)
        );
        if (updatedTarget !== item.target) {
            editingTargets.value[index].target = updatedTarget;
            replacements++;
        }
    });

    if (!replacements) {
        textToImageFeedback.value = "No matches found in current targets.";
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
        result = result.replace(/<br\s*\/?>(?!\n)/gi, "\n");
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
        if (hasImage(_target.value.target)) return;
        _target.value.equivalent = sanitize(_target.value.target)
            .split(" i ")
            .join(" I ");
    } else {
        return sanitize(val).split(" i ").join(" I ");
    }
};

// Helper to determine if HTML content has any visible text or images
const hasVisibleContent = (html) => {
    if (!html) return false;
    const raw = String(html);

    // Any <img> tag counts as valid content
    if (/<img\b[^>]*>/i.test(raw)) return true;

    // Replace &nbsp; with regular space, strip HTML tags, then trim
    const withoutNbsp = raw.replace(/&nbsp;/gi, " ");
    const textOnly = withoutNbsp.replace(/<[^>]*>/g, "").trim();
    return textOnly.length > 0;
};

const resetForm = () => {
    _target.value = {
        target: "",
        equivalent: "",
        can_unselect: false,
        colors: {
            normal: null,
            hover: null,
            active: null,
        },
        value: 0,
        index: null,
    };
    selectedIndex.value = false;
    editingTargets.value = [];
    selectedItemsInModal.value = new Set();
    selectAllInModal.value = false;
    bulkTargetsText.value = "";
    bulkEquivalentsText.value = "";
    newItemInModal.value = {
        target: "",
        equivalent: "",
        colors: {
            normal: null,
            hover: null,
            active: null,
        },
        value: 0,
    };
    quillEditorKey.value++;
    bulkQuillEditorKey.value++;
    modals.value.createTarget = false;
};

const addTargetDialog = () => {
    showTemplatePanel.value = false;
    resetForm();
    // Initialize with empty editing array
    editingTargets.value = [];
    modals.value.createTarget = true;
};

const addTargetList = () => {
    let targets = _target.value.target.split("\n");
    let target_length = getMaxIndex(form.targets);
    let eqs = assign(_target.value.equivalent.split("\n"));
    forEach(targets, (t, i) => {
        target_length++;
        let target = {
            target: t,
            equivalent: _target.value.equivalent
                ? eqs[i] ?? unformatContent(3, t)
                : unformatContent(3, t),
            index: target_length,
            can_unselect: _target.value.can_unselect,
            colors: assign(_target.value.colors),
            value:
                _target.value.value !== 0 ? _target.value.value : target_length,
        };
        form.targets.push(target);
    });
    searchDuplicates();
    resetForm();
};

const normalizeEquivalent = (value) => {
    try {
        return sanitize(String(value ?? "")).trim();
    } catch (_) {
        return "";
    }
};

const findDuplicates = (arry) => {
    const seen = new Set();
    const duplicates = new Set();

    arry.forEach((item) => {
        if (!item) return;
        if (seen.has(item)) {
            duplicates.add(item);
        } else {
            seen.add(item);
        }
    });

    return Array.from(duplicates);
};

const detectDuplicates = (arry, key = "equivalent") => {
    const normalizedValues = arry.map((item) => {
        const rawValue = item?.[key];
        const normalized = normalizeEquivalent(rawValue);
        return {
            normalized,
            display: rawValue ?? normalized,
        };
    });

    const normalizedList = normalizedValues
        .filter(({ normalized }) => Boolean(normalized))
        .map(({ normalized }) => normalized);

    const duplicateKeys = findDuplicates(normalizedList);

    return Array.from(
        new Set(
            normalizedValues
                .filter(({ normalized }) => duplicateKeys.includes(normalized))
                .map(({ display }) => display)
        )
    );
};

const duplicates = ref([]);

// Duplicate detection for modal editing targets
const modalDuplicates = computed(() => {
    const duplicates = new Set();

    // Duplicates within the editing list itself
    detectDuplicates(editingTargets.value).forEach((dup) =>
        duplicates.add(dup)
    );

    // Compare against existing targets (excluding the one being updated)
    const editingNormalized = new Set(
        editingTargets.value
            .map((item) => normalizeEquivalent(item?.equivalent))
            .filter(Boolean)
    );

    if (editingNormalized.size > 0) {
        (form.targets || []).forEach((item, index) => {
            if (selectedIndex.value && index === selectedIndex.value - 1) {
                return;
            }

            const normalized = normalizeEquivalent(item?.equivalent);
            const displayValue = item?.equivalent ?? normalized;

            if (normalized && editingNormalized.has(normalized)) {
                duplicates.add(displayValue);
            }
        });
    }

    return Array.from(duplicates);
});

const hasModalDuplicates = computed(() => {
    return modalDuplicates.value.length > 0;
});

onMounted(() => {
    searchDuplicates();
});

const searchDuplicates = () => {
    duplicates.value = detectDuplicates(form.targets);
};

const getMaxIndex = (values) => {
    return maxBy(values, "index")?.index ?? 0;
};

// Toggle for Target editor (QuillEditor vs PrismEditor)
const enableTargetQuillEditor = ref(true);

const deleteTarget = (index) => {
    form.targets.splice(index, 1);
    // Renumber remaining targets sequentially and keep value in sync if auto
    renumberTargets(form.targets);
    searchDuplicates();
};

const updateTargetDialog = (index) => {
    selectedIndex.value = index;
    _target.value = assign(form.targets[index - 1]);

    // Populate editingTargets with the current target for editing in the modal
    editingTargets.value = [assign(form.targets[index - 1])];

    // Reset modal selection state
    selectedItemsInModal.value = new Set();
    selectAllInModal.value = false;
    bulkTargetsText.value = "";
    bulkEquivalentsText.value = "";

    // Reset newItemInModal for the form
    newItemInModal.value = {
        target: "",
        equivalent: "",
        colors: {
            normal: null,
            hover: null,
            active: null,
        },
        value: 0,
    };

    modals.value.createTarget = true;
    searchDuplicates();
};

const updateTargetList = () => {
    form.targets[selectedIndex.value - 1] = _target.value;
    searchDuplicates();
    resetForm();
};

const targetAction = () => {
    if (selectedIndex.value) {
        updateTargetList();
    } else {
        addTargetList();
    }
};

const showButtons = ref(false);

const toggleButtons = (index) => {
    const targets = form.targets || [];
    targets.forEach((p, i) => {
        p.showButtons = i === index ? !p.showButtons : false;
    });
};

// Hide all floating buttons when clicking outside
const hideAllButtons = () => {
    const targets = form.targets || [];
    targets.forEach((p) => {
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

// Toggle select all items in modal
const toggleSelectAllInModal = () => {
    if (selectAllInModal.value) {
        selectedItemsInModal.value = new Set(
            editingTargets.value.map((_, index) => index)
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
        selectedItemsInModal.value.size === editingTargets.value.length &&
        editingTargets.value.length > 0;
};

// Delete selected items in modal
const deleteSelectedItemsInModal = () => {
    const indices = Array.from(selectedItemsInModal.value).sort(
        (a, b) => b - a
    );
    indices.forEach((index) => {
        editingTargets.value.splice(index, 1);
    });
    selectedItemsInModal.value = new Set();
    selectAllInModal.value = false;
};

// Delete individual item from modal
const deleteItemFromModal = (index) => {
    editingTargets.value.splice(index, 1);
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
    if (editingTargets.value[index]) {
        editingTargets.value[index][field] = value;
    }
};

// Add new item in modal
const addNewItemInModal = () => {
    if (!newItemInModal.value.target) return;

    // Get the maximum index from both existing targets and current editing targets
    const existingMaxIndex = getMaxIndex(form.targets);
    const editingMaxIndex = getMaxIndex(editingTargets.value);
    const maxIndex = Math.max(existingMaxIndex, editingMaxIndex);

    editingTargets.value.push({
        target: newItemInModal.value.target,
        equivalent:
            newItemInModal.value.equivalent ||
            sanitize(newItemInModal.value.target).split(" i ").join(" I "),
        index: maxIndex + 1,
        can_unselect: false,
        colors: assign(newItemInModal.value.colors),
        value: newItemInModal.value.value || maxIndex + 1,
    });

    // Clear the input fields after adding
    newItemInModal.value = {
        target: "",
        equivalent: "",
        colors: {
            normal: null,
            hover: null,
            active: null,
        },
        value: 0,
    };

    // Force QuillEditor to re-render by incrementing the key
    quillEditorKey.value++;
};

// Add bulk targets in modal (preserve images and formatting)
const addBulkTargetsInModal = () => {
    if (!bulkTargetsText.value || !String(bulkTargetsText.value).trim()) {
        try {
            toast.error("Need to input character to add a target");
        } catch (_) {}
        return;
    }

    // Handle both plain text and HTML content
    let processed;
    const containsHtml =
        typeof bulkTargetsText.value === "string" &&
        /<[^>]+>/.test(bulkTargetsText.value);
    if (containsHtml) {
        // HTML content - preserve paragraph wrappers to keep mixed content together
        processed = htmlToEditorNoP(bulkTargetsText.value, {
            preserveParagraphs: true,
        });
    } else {
        // Plain text content - use as is
        processed = bulkTargetsText.value;
    }

    const targetLines = processed.split("\n");
    const equivalentLines = bulkEquivalentsText.value.split("\n");

    // Filter out lines that have no visible content (no text and no image)
    const targets = [];
    const equivalents = [];
    targetLines.forEach((line, idx) => {
        if (hasVisibleContent(line)) {
            targets.push(line);
            equivalents.push(equivalentLines[idx] ?? "");
        }
    });

    if (targets.length === 0) {
        try {
            toast.error("Need to input character to add a target");
        } catch (_) {}
        return;
    }

    // Get the maximum index from both existing targets and current editing targets
    const existingMaxIndex = getMaxIndex(form.targets);
    const editingMaxIndex = getMaxIndex(editingTargets.value);
    let maxIndex = Math.max(existingMaxIndex, editingMaxIndex);

    targets.forEach((target, i) => {
        maxIndex++;
        const trimmedTarget = target.trim();
        const targetHtml = trimmedTarget.includes("<")
            ? trimmedTarget
            : editorToHtmlNoP(trimmedTarget);
        const equivalent =
            equivalents[i]?.trim() ||
            sanitize(trimmedTarget).split(" i ").join(" I ");

        editingTargets.value.push({
            target: targetHtml,
            equivalent: equivalent,
            index: maxIndex,
            can_unselect: false,
            colors: {
                normal: null,
                hover: null,
                active: null,
            },
            value: maxIndex,
        });
    });

    // Clear the bulk input fields after adding
    bulkTargetsText.value = "";
    bulkEquivalentsText.value = "";

    // Force bulk QuillEditor to re-render by incrementing the key
    bulkQuillEditorKey.value++;
};

// Save targets from modal
const saveTargetsFromModal = () => {
    // Prevent saving if there are duplicates in the modal
    if (hasModalDuplicates.value) {
        return;
    }

    // Prevent saving if any target is effectively empty (no text and no image)
    const hasEmptyTarget = editingTargets.value.some(
        (item) => !hasVisibleContent(item?.target)
    );
    if (hasEmptyTarget) {
        try {
            toast.error("Target should not be empty");
        } catch (_) {}
        return;
    }
    
    if (selectedIndex.value) {
        // Update existing target
        if (editingTargets.value.length > 0) {
            const updatedTarget = editingTargets.value[0];
            form.targets[selectedIndex.value - 1] = updatedTarget;
        }
    } else {
        // Add new targets - ensure they get proper sequential indices
        const currentMaxIndex = getMaxIndex(form.targets);
        editingTargets.value.forEach((item, index) => {
            item.index = currentMaxIndex + index + 1;
            form.targets.push(item);
        });
    }
    searchDuplicates();
    resetForm();
};

const csvInput = ref(null);
const csvInputModal = ref(null);

const handleCSVUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    try {
        const text = await file.text();
        const lines = text.trim().split("\n").slice(1); // skip header row
        const formatted = lines
            .map((line) => line.split(",").join("\t")) // Tab-separated
            .join("\n");

        // Wrap in <pre> for CKEditor to preserve format
        _target.value.target = `<pre>${formatted}</pre>`;
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
        const lines = text.trim().split("\n").slice(1); // skip header row

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

        // Process CSV data - assume first column is targets, second column is equivalents
        const csvData = lines.map((line) => {
            const columns = parseCSVLine(line);
            return {
                target: columns[0] || "",
                equivalent: columns[1] || "",
            };
        });

        // Separate targets and equivalents
        const targets = csvData
            .map((item) => item.target)
            .filter((target) => target.trim());
        const equivalents = csvData.map((item) => item.equivalent);

        // Convert to plain text format for Quill editor (avoid HTML conversion)
        const targetsText = targets.join("\n");

        const equivalentsText = equivalents.join("\n");

        // Populate the bulk input fields
        bulkTargetsText.value = targetsText;
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
            target: "calm",
            equivalent: "calm",
        },
        {
            target: "protected",
            equivalent: "protected",
        },
        {
            target: "secure",
            equivalent: "secure",
        },
        {
            target: "safe",
            equivalent: "safe",
        },
        {
            target: "comforted",
            equivalent: "comforted",
        },
        {
            target: "supported",
            equivalent: "supported",
        },
    ];

    // Convert data to CSV format
    const csvContent = exampleData
        .map((item) => `"${item.target}","${item.equivalent}"`)
        .join("\n");

    // Add CSV header
    const csvWithHeader = `"Target","Equivalent"\n${csvContent}`;

    // Create and download the file
    const blob = new Blob([csvWithHeader], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", "target_template.csv");
    link.style.visibility = "hidden";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

// Image URL modal state and handlers
const showImageUrlModal = ref(false);
const imageUrlsText = ref("");

const openImageUrlModal = (quillInstance = null) => {
    try {
        if (quillInstance) {
            activeQuillEditor.value = quillInstance;
        } else {
            const editorComponent = targetQuillRef.value;
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

    // Also update the equivalent field appropriately
    if (quill.container && quill.container.closest('tbody')) {
        // Individual item editor in table
        const row = quill.container.closest('tr');
        if (row) {
            const rowIndex = Array.from(row.parentNode.children).indexOf(row);
            if (editingTargets.value[rowIndex]) {
                editingTargets.value[rowIndex].equivalent = token;
            }
        }
    } else if (quill.container && quill.container.closest('.quill-bubble-wrapper')) {
        // Bulk targets editor
        const currentContent = bulkTargetsText.value || '';
        const targetLines = currentContent.split('\n');
        const textBeforeCursor = quill.getText(0, index);
        const linesBeforeCursor = textBeforeCursor.split('\n');
        const currentLineIndex = linesBeforeCursor.length - 1;

        const currentEquivalents = bulkEquivalentsText.value || '';
        const equivalentLines = currentEquivalents.split('\n');
        while (equivalentLines.length < targetLines.length) {
            equivalentLines.push('');
        }
        if (equivalentLines[currentLineIndex] === '' || equivalentLines[currentLineIndex] === undefined) {
            equivalentLines[currentLineIndex] = token;
        } else {
            equivalentLines[currentLineIndex] += token;
        }
        bulkEquivalentsText.value = equivalentLines.join('\n');
    }

    showVariablesModal.value = false;
};

// Scoped event handler to support applying variables from IndexVariables inside Quill bubble (Targets scope)
function onTargetQuillVariableInsertRequest(e) {
    try {
        const raw = e?.detail?.name;
        if (!raw) return;
        insertVariableIntoQuill(raw);
    } catch (_) {}
}

if (typeof window !== "undefined") {
    try {
        window.addEventListener(
            "target-quill-variable-insert-request",
            onTargetQuillVariableInsertRequest
        );
    } catch (_) {}
}

const closeVariablesModal = () => {
    showVariablesModal.value = false;
    activeQuillEditor.value = null;
};

const allSelected = computed(() => {
    const targets = form.targets || [];
    return targets.length > 0 && targets.every((t) => t.selected);
});

const anySelected = computed(() => {
    const targets = form.targets || [];
    return targets.some((t) => t.selected);
});

const toggleSelectAll = () => {
    const val = !allSelected.value;
    const targets = form.targets || [];
    targets.forEach((t) => (t.selected = val));
};

const deleteSelected = () => {
    const targets = form.targets || [];
    let removed = false;
    for (let i = targets.length - 1; i >= 0; i--) {
        if (targets[i].selected) {
            targets.splice(i, 1);
            removed = true;
        }
    }
    if (removed) {
        // Renumber remaining targets sequentially
        renumberTargets(targets);
        searchDuplicates();
    }
};

// Ensure indices are sequential and keep value in sync for auto-valued items
const renumberTargets = (targets) => {
    (targets || []).forEach((t, i) => {
        const wasAuto = t.value === t.index;
        t.index = i + 1;
        if (wasAuto) t.value = t.index;
    });
};

onBeforeMount(() => {
    const targets = form.targets || [];
    targets.forEach((t) => {
        if (t.selected === undefined) t.selected = false;
    });
});
</script>
<template>
    <div class="flex flex-col">
        <div
            v-if="form.targets.length"
            class="w-full mt-2 bg-[#25755620] rounded-lg justify-center flex items-center"
        >
            <div class="flex items-center gap-2 p-2 py-3">
                <JetButton
                    class="uppercase"
                    @click="addTargetDialog"
                    variant="default"
                    size="xs"
                >
                    Add custom targets
                </JetButton>
                <JetButton
                    class="uppercase"
                    @click="showTemplatePanel = true"
                    variant="default"
                    size="xs"
                >
                    Load targets template
                </JetButton>

                <div class="flex flex-row gap-2">
                    <JetButton
                        type="button"
                        class="px-3 py-1 font-bold bg-white rounded-full uppercase"
                        variant="default"
                        size="xs"
                        @click="toggleSelectAll"
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
                        v-for="template in templateTargets"
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
                    @click="loadTemplateTargets"
                >
                    Load
                </JetButton>
            </div>
        </div>
        <div class="shadow">
            <div
                class="bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4 m-2 w-full absolute"
                role="alert"
                v-if="duplicates.length > 0"
            >
                <p class="font-bold mb-2">Warning! Duplicate equivalents</p>
                <p v-for="d in duplicates">{{ d }}</p>
            </div>
        </div>
        <div
            class="grid grid-cols-3 gap-3 mt-2 max-h-[63vmin] overflow-x-hidden overflow-y-auto"
            v-if="form.targets.length"
        >
            <TargetCard
                v-for="(target, i) in form.targets"
                :key="'targ' + i"
                class="m-2 md:col-span-1 col-span-3"
            >
                <template #n>
                    {{ i + 1 }}
                </template>
                <template #index>
                    {{ target.index }}
                </template>
                <template #value>
                    {{ target.value }}
                </template>
                <template #target>
                    <span v-html="setProxiedHtml(target.target)"> </span>
                </template>
                <template #equivalent>
                    {{ target.equivalent }}
                </template>
                <template #colours>
                    <div class="inline-flex items-center">
                        <span
                            class="text-xs text-gray-50 rounded-md px-1 ml-1"
                            v-if="target.colors.normal"
                            :style="'background-color: ' + target.colors.normal"
                        >
                            N
                        </span>
                        <span
                            class="text-xs text-gray-50 rounded-md px-1 ml-1"
                            v-if="target.colors.hover"
                            :style="'background-color: ' + target.colors.hover"
                        >
                            H
                        </span>
                        <span
                            class="text-xs text-gray-50 rounded-md px-1 ml-1"
                            v-if="target.colors.active"
                            :style="'background-color: ' + target.colors.active"
                        >
                            A
                        </span>
                    </div>
                </template>

                <template #actions>
                    <!-- Radio Button -->
                    <div class="checkbox-container">
                        <label class="ios-checkbox blue">
                            <input type="checkbox" v-model="target.selected" />
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

                    <div class="relative inline-block space-between">
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

                        <div
                            v-show="target.showButtons"
                            data-floating-buttons
                            class="absolute right-0 z-50 flex flex-col bg-white border rounded-lg shadow-lg top-6 w-36 animate-fade-in"
                        >
                            <a
                                href="javascript:;"
                                class="text-base px-2 py-1 gap-1 font-bold rounded-t-lg inline-flex items-center hover:bg-[#25755630]"
                                @click="updateTargetDialog(i + 1)"
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
                                @click="deleteTarget(i)"
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
            </TargetCard>
        </div>
        <!-- start no record found -->
        <div
            v-else
            class="flex items-center h-[65vmin] mx-auto text-gray-600 prose-2xl font-medium"
        >
            <div class="flex flex-col w-[60vmin] items-center gap-4">
                <JetButton
                    class="uppercase w-full flex flex-col items-center justify-center gap-1 py-4 text-center"
                    @click="addTargetDialog"
                    variant="primary-light"
                    size="sm"
                >
                    <span class="font-semibold">Add custom targets</span>
                    <span
                        class="text-xs normal-case leading-snug opacity-80 px-6"
                    >
                        Create targets manually with full control over each
                        target's content, order, value, and selection options.
                    </span>
                </JetButton>
                <JetButton
                    class="uppercase w-full flex flex-col items-center justify-center gap-1 py-4 text-center"
                    @click="showTemplatePanel = true"
                    variant="orange-light"
                    size="sm"
                >
                    <span class="font-semibold">Load targets template</span>
                    <span
                        class="text-xs normal-case leading-snug opacity-80 px-6"
                    >
                        Start from a ready-made target set. Load a template and
                        adjust as needed instead of building from scratch.
                    </span>
                </JetButton>
            </div>
        </div>
        <!-- Template Management -->
        <div
            class="bg-white px-2 absolute bottom-7 w-full"
            v-if="form.targets.length"
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
                                    : 'Save targets as template'
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
        :show="modals.createTarget"
        @close="modals.createTarget = false"
        maxWidth="6xl"
    >
        <template #title>
            <span class="text-[#257556] font-bold">
                {{ selectedIndex ? "Update Target" : "Add Custom Targets" }}
            </span>
        </template>

        <template #content>
            <div class="space-y-6 mt-6">
                <!-- Duplicate Warning for Modal -->
                <div
                    v-if="hasModalDuplicates"
                    class="bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4 rounded-md"
                    role="alert"
                >
                    <p class="font-bold mb-2">Warning! Duplicate equivalents detected</p>
                    <p class="text-sm">Please remove duplicate equivalents before saving:</p>
                    <ul class="list-disc list-inside mt-1 text-sm">
                        <li v-for="(dup, idx) in modalDuplicates" :key="idx">{{ dup }}</li>
                    </ul>
                </div>
                
                <!-- Items Table -->
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <JetLabel value="Target Items" />
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
                            <Toggle v-model="enableTargetQuillEditor" />
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
                                    :disabled="editingTargets.length === 0"
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
                                                editingTargets.length === 0
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
                                        class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-5/12"
                                    >
                                        Target
                                    </th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-3/12"
                                    >
                                        Equivalent
                                    </th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-1/12"
                                    >
                                        Value
                                    </th>
                                    <th
                                        class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-1/12"
                                    >
                                        Unselect
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
                                    v-for="(item, index) in editingTargets"
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
                                            class="w-full h-[34px] text-sm bg-gray-50 border border-gray-300 rounded-md px-3 py-2 flex items-center"
                                        >
                                            {{ item.index }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-1 w-5/12">
                                        <div v-if="enableTargetQuillEditor" class="quill-bubble-wrapper border border-gray-300 rounded-md">
                                            <QuillEditor
                                                theme="bubble"
                                                :content="parseVariablesToElements(item.target)"
                                                @update:content="
                                                    updateItemFieldInModal(
                                                        index,
                                                        'target',
                                                        parseElementsToVariables($event)
                                                    )
                                                "
                                                @ready="
                                                    (quill) => {
                                                        bulkTargetsQuill = quill;
                                                        setupQuillToolbarPositioning(
                                                            quill
                                                        );
                                                        addVariablesButtonToBubble(
                                                            quill
                                                        );
                                                    }
                                                "
                                                contentType="html"
                                                placeholder="Click to edit target content..."
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
                                            :model-value="parseElementsToVariables(item.target)"
                                            @update:model-value="
                                                updateItemFieldInModal(
                                                    index,
                                                    'target',
                                                    $event
                                                )
                                            "
                                            :highlight="highlighter"
                                            line-numbers
                                        />
                                    </td>
                                    <td class="px-3 py-1 w-3/12">
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
                                    <td class="px-3 py-1 w-1/12">
                                        <JetInput
                                            :model-value="item.value"
                                            @update:model-value="
                                                updateItemFieldInModal(
                                                    index,
                                                    'value',
                                                    $event
                                                )
                                            "
                                            type="number"
                                            class="w-full h-[34px] text-sm focus:ring-[#257556] focus:border-[#257556]"
                                            placeholder="Value"
                                        />
                                    </td>
                                    <td class="px-3 py-1 w-1/12">
                                        <div
                                            class="flex items-center justify-center"
                                        >
                                            <JetCheckbox
                                                :checked="item.can_unselect"
                                                @update:checked="
                                                    updateItemFieldInModal(
                                                        index,
                                                        'can_unselect',
                                                        $event
                                                    )
                                                "
                                                class="rounded border-gray-300 text-[#257556] focus:ring-[#257556]"
                                            />
                                        </div>
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
                                                title="Delete this target"
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
                                <!-- Show empty state when no targets -->
                                <tr v-if="editingTargets.length === 0">
                                    <td
                                        :colspan="selectedIndex ? 5 : 6"
                                        class="px-3 py-8 text-center text-gray-500"
                                    >
                                        No targets added yet. Use the form below
                                        to add targets.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add New Item / Bulk Add - Only show when not updating -->
                <div v-if="!selectedIndex" class="">
                    <!-- <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-medium text-gray-900">Add Target</h4>
                    </div> -->

                    <!-- Single add row -->
                    <!-- <div class="grid grid-cols-12 gap-3 mb-4">
                        <div class="col-span-4">
                            <div class="quill-bubble-wrapper border border-gray-300 rounded-md">
                                <QuillEditor
                                    :key="quillEditorKey"
                                    theme="bubble"
                                    :content="newItemInModal.target"
                                    @update:content="newItemInModal.target = $event"
                                    @ready="(quill) => { setupQuillToolbarPositioning(quill); addVariablesButtonToBubble(quill); }"
                                    contentType="html"
                                    placeholder="Click to edit target content..."
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
                        <div class="col-span-3">
                            <JetInput
                                v-model="newItemInModal.equivalent"
                                type="text"
                                placeholder="Equivalent (optional)"
                                class="w-full min-h-[40px] text-sm border border-gray-300 rounded-md focus:ring-[#257556] focus:border-[#257556]"
                            />
                        </div>
                        <div class="col-span-3">
                            <JetInput
                                v-model="newItemInModal.value"
                                type="number"
                                placeholder="Value"
                                class="w-full min-h-[40px] text-sm border border-gray-300 rounded-md focus:ring-[#257556] focus:border-[#257556]"
                            />
                        </div>
                        <div class="col-span-2">
                            <JetButton
                                @click="addNewItemInModal"
                                variant="slate"
                                class="text-sm py-1 h-[40px] w-full text-center justify-center"
                            >
                                Add
                            </JetButton>
                        </div>
                    </div> -->

                    <!-- Bulk add for targets -->
                    <div class="mt-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="flex items-center justify-between">
                                    
                                    <JetLabel value="Targets (one per line)" />
                            
                                    <div>
                                       
                                        <Buttons @click="openImageUrlModal(bulkTargetsQuill)">
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
                                        :content="bulkTargetsText"
                                        @update:content="
                                            bulkTargetsText = $event
                                        "
                                        @ready="
                                            (quill) => {
                                                bulkTargetsQuill = quill;
                                                setupQuillToolbarPositioning(
                                                    quill
                                                );
                                                addVariablesButtonToBubble(
                                                    quill
                                                );
                                            }
                                        "
                                        contentType="html"
                                        placeholder="Enter targets, one per line..."
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
                                        class="min-h-[120px] text-sm"
                                    />
                                </div>
                            </div>
                            <div>
                                <JetLabel
                                    value="Equivalents (align by line with targets)"
                                />
                                <textarea
                                    v-model="bulkEquivalentsText"
                                    placeholder=""
                                    class="mt-1 w-full min-h-[120px] text-sm border border-gray-300 rounded-md focus:ring-[#257556] focus:border-[#257556]"
                                ></textarea>
                                <p class="mt-1 text-xs text-gray-500">
                                    Leave a line blank if a target has no
                                    equivalent.
                                </p>
                            </div>
                        </div>
                        <div class="flex justify-start -mt-4 gap-2">
                            <JetButton
                                variant="slate"
                                @click="addBulkTargetsInModal"
                                class="text-sm"
                            >
                                Add Targets
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
                                Use this option to import a list of targets from a file.<br/> The CSV file must be formatted correctly to match<br/> the platform's required structure.                                </div>
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
                                Click this to download a blank template file.<br/>Use this template to ensure your targets and <br/> equivalents are structured correctly before <br/>using the Upload CSV function. 
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
            </div>
        </template>

        <template #footer>
            <JetButton variant="default" @click="resetForm" class="mr-2">
                CANCEL
            </JetButton>
            <JetButton 
                variant="primary" 
                @click="saveTargetsFromModal"
                :disabled="editingTargets.length === 0 || hasModalDuplicates"
                :class="{
                    'opacity-50 cursor-not-allowed': editingTargets.length === 0 || hasModalDuplicates
                }"
            >
                {{ selectedIndex ? "UPDATE TARGET" : "SAVE TARGETS" }}
            </JetButton>
        </template>
    </JetDialogModal>

    <!-- Variables Modal -->
    <JetDialogModal :show="showVariablesModal" @close="closeVariablesModal" maxWidth="4xl">
        <!-- <template #title>Variables</template> -->
        <template #content>
            <IndexVariables :project="$page.props.project" :survey="survey" :enable-apply="true" apply-event-name="target-quill-variable-insert-request" />
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
            <JetButton variant="primary" @click="convertTextToImageInTargetItems">
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
.c {
    max-height: calc(100vh - 260px);
}

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
    max-height: calc(100vh - 156px);
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

.slide-right-enter-active {
    transition: all 0.3s ease;
}
.slide-right-leave-active {
    transition: all 0.2s ease;
    opacity: 0;
}
.slide-right-enter-from {
    transform: translateX(100%);
    opacity: 0;
}
.slide-right-enter-to {
    transform: translateX(0);
    opacity: 1;
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

:deep(.ql-editor img) {
  display: inline-block;
  max-width: 50px !important;
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

/* PrismEditor styles for Target column */
.editor-height {
    min-height: 34px;
    max-height: 80px;
    font-size: 12px;
    max-width: 410px;
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
