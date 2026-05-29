<script setup>
import { QuillEditor } from "@vueup/vue-quill";
import "quill/dist/quill.snow.css";
import { useTestsStore } from "@/stores/tests";
// Import CK Editor Components
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import CKEditor from "@ckeditor/ckeditor5-vue";
import UploadAdapterPlugin from "@/Functions/UploadAdapter";
import { PrismEditor } from "vue-prism-editor";
import "vue-prism-editor/dist/prismeditor.min.css"; // import the styles somewhere
import { highlight, languages } from "prismjs/components/prism-core";
import "prismjs/components/prism-clike";
import "prismjs/components/prism-javascript";
import "prismjs/components/prism-markup";
import "prismjs/components/prism-css";
import "prismjs/themes/prism-tomorrow.css"; // import syntax highlighting styles
// Ck Editor end
import QuillContainer from "@/Components/QuillEditor.vue";
import IndexVariables from "@/Pages/Variables2/Index.vue";
import { inject, ref, onBeforeMount, onMounted, onUnmounted, computed, watch } from "vue";
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

import maxBy from "lodash/maxBy";
import forEach from "lodash/forEach";

const { sanitize, capitalize, hasImage, assign, toast } = Helpers();

function proxiedHtml(html) {
    try {
        if (!html || typeof html !== "string") return html;
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        const imgs = doc.querySelectorAll("img[src]");
        imgs.forEach((img) => {
            try {
                const u = new URL(
                    img.getAttribute("src"),
                    window.location.origin
                );
                if (u.origin !== window.location.origin) {
                    img.setAttribute(
                        "src",
                        `/gallery-proxy?u=${encodeURIComponent(u.href)}`
                    );
                }
            } catch (_) {}
        });
        return doc.body.innerHTML;
    } catch (_) {
        return html;
    }
}

const enableCK = inject("ck");
const ckeditor = CKEditor.component;
const editor = ref(ClassicEditor);
const targetQuillRef = ref(null);
const quillInstance = ref(null);
const selectedIndex = ref(false);

const props = defineProps(["tests", "variables"]);
const dataStore = useTestsStore();
const emit = defineEmits(["update:tests"]);

const form = computed(
    () => props.tests || { targets: [], primes: [], config: {} }
);

const editorConfig = ref({
    extraPlugins: [UploadAdapterPlugin],
});

const modal = ref(false);
const showDuplicateValueModal = ref(false);
const duplicateValueMessage = ref("");

const _target = ref({
    target: "",
    equivalent: "",
    can_unselect: false,
    colors: {
        normal: null,
        hover: null,
        active: null,
    },
    properties: {
        bg: null,
    },
    value: 0,
    index: null,
});

// Bulk add/edit state (ported from TestTargets1 process)
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

// Variables functionality (optional)
const showVariablesModal = ref(false);
const activeQuillEditor = ref(null);
const variables = props.variables || [];

// Image URL modal state
const showImageUrlModal = ref(false);
const imageUrlsText = ref("");

const highlighter = (code) => {
    return highlight(code, languages.markup);
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

const stripParagraphsAroundImages = (html) => {
    if (!html) return html;
    try {
        // Strip <p> only when it wraps solely images/whitespace; keep mixed content paragraphs
        return String(html).replace(
            /<p([^>]*)>([\s\S]*?)<\/p>/gi,
            (match, attrs, content) => {
                const contentWithoutImages = content
                    .replace(/<(?:img|picture)\b[^>]*>/gi, "")
                    .trim();
                if (contentWithoutImages) {
                    return match;
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
            return `<span class="ql-variable" data-name="${variableName}" contenteditable="false" style="background: #15919A; color: #fff; padding: 4px 8px; border-radius: 6px; font-weight: 600; display: inline; margin: 0 2px; cursor: not-allowed; user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; box-shadow: 2px 2px 4px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.3); border: 1px solid #0C7C84;">${variableName}</span>`;
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
        if (hasImage(_target.value.target)) return;
        // Apply proxy transformation before sanitizing to prevent cross-origin image loads
        const proxiedTarget = proxiedHtml(_target.value.target);
        _target.value.equivalent = sanitize(proxiedTarget)
            .split(" i ")
            .join(" I ");
    } else {
        // Apply proxy transformation before sanitizing to prevent cross-origin image loads
        const proxiedVal = proxiedHtml(val);
        return sanitize(proxiedVal).split(" i ").join(" I ");
    }
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
        properties: {
            bg: null,
        },
        rawTargetElement: null,
        value: 0,
        index: null,
    };
    selectedIndex.value = false;
    // Reset bulk modal state
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
    modal.value = false;
};

const addTargetDialog = () => {
    resetForm();
    // Initialize with empty editing array
    editingTargets.value = [];
    modal.value = true;
};

// Helpers to support bulk modal operations
const toggleSelectAllInModal = () => {
    if (selectAllInModal.value) {
        selectedItemsInModal.value = new Set(
            editingTargets.value.map((_, index) => index)
        );
    } else {
        selectedItemsInModal.value = new Set();
    }
};

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

const deleteSelectedItemsInModal = () => {
    const indices = Array.from(selectedItemsInModal.value).sort((a, b) => b - a);
    indices.forEach((i) => editingTargets.value.splice(i, 1));
    selectedItemsInModal.value = new Set();
    selectAllInModal.value = false;
};

const deleteItemFromModal = (index) => {
    editingTargets.value.splice(index, 1);
    const newSelected = new Set();
    selectedItemsInModal.value.forEach((i) => {
        if (i > index) newSelected.add(i - 1);
        else if (i < index) newSelected.add(i);
    });
    selectedItemsInModal.value = newSelected;
    selectAllInModal.value = false;
};

const updateItemFieldInModal = (index, field, value) => {
    if (editingTargets.value[index]) {
        editingTargets.value[index][field] = value;
    }
};

const addNewItemInModal = () => {
    if (!newItemInModal.value.target) return;
    const existingMaxIndex = getMaxIndex(form.value?.targets || []);
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
        properties: { bg: null },
        rawTargetElement: newItemInModal.value.target,
            selected: false,
    });
    newItemInModal.value = {
        target: "",
        equivalent: "",
        colors: { normal: null, hover: null, active: null },
        value: 0,
    };
    quillEditorKey.value++;
};

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
        // Preserve paragraph wrappers so text and images stay grouped
        processed = htmlToEditorNoP(bulkTargetsText.value, {
            preserveParagraphs: true,
        });
    } else {
        // If it's plain text, use it directly
        processed = bulkTargetsText.value;
    }

    const targetLines = processed.split("\n");
    const equivalentLines = String(bulkEquivalentsText.value || "").split("\n");

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
    const existingMaxIndex = getMaxIndex(form.value?.targets || []);
    const editingMaxIndex = getMaxIndex(editingTargets.value);
    let maxIndex = Math.max(existingMaxIndex, editingMaxIndex);
    targets.forEach((t, i) => {
        maxIndex++;
        const trimmed = t.trim();
        // For plain text, just convert newlines to <br>; for HTML, preserve formatting
        const targetHtml = trimmed.includes("<")
            ? trimmed
            : editorToHtmlNoP(trimmed);
        const eq = equivalents[i]?.trim() || sanitize(trimmed).split(" i ").join(" I ");
        editingTargets.value.push({
            target: targetHtml,
            equivalent: eq,
            index: maxIndex,
            can_unselect: false,
            colors: { normal: null, hover: null, active: null },
            value: maxIndex,
            properties: { bg: null },
            rawTargetElement: targetHtml,
            selected: false,
        });
    });
    bulkTargetsText.value = "";
    bulkEquivalentsText.value = "";
    bulkQuillEditorKey.value++;
};

const saveTargetsFromModal = () => {
    // Prevent saving if duplicates exist in the modal list
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
            const updated = editingTargets.value[0];
            // Prevent updating when another target already uses the same value
            try {
                const targets = form.value?.targets || [];
                const currentIdx = selectedIndex.value - 1;
                const newValue = Number(updated.value);
                const hasDuplicateValue = targets.some((t, idx) => idx !== currentIdx && Number(t?.value) === newValue);
                if (hasDuplicateValue) {
                    duplicateValueMessage.value = `The value ${newValue} is already used by another target. Please choose a unique value.`;
                    showDuplicateValueModal.value = true;
                    return;
                }
            } catch (_) {}
            const nextTests = assign(
                props.tests || { targets: [], primes: [], config: {} }
            );
            if (Array.isArray(nextTests.targets)) {
                nextTests.targets[selectedIndex.value - 1] = assign(updated);
                emit("update:tests", nextTests);
                dataStore.setTests(nextTests);
                dataStore.isDirty = true;
            }
        }
    } else {
        // Add many via centralized store
        const currentMaxIndex = getMaxIndex(form.value?.targets || []);
        const bulk = editingTargets.value.map((item, idx) => ({
            target: item.target,
            equivalent: item.equivalent,
            can_unselect: item.can_unselect || false,
            colors: assign(item.colors || { normal: null, hover: null, active: null }),
            value: item.value || currentMaxIndex + idx + 1,
            selected: false,
            properties: { bg: null },
            rawTargetElement: item.target,
            index: currentMaxIndex + idx + 1,
        }));
        if (bulk.length > 0) {
    dataStore.addTargetsBulk(bulk);
    const nextTests = assign(
        props.tests || { targets: [], primes: [], config: {} }
    );
    nextTests.targets = dataStore.tests.targets;
    emit("update:tests", nextTests);
        }
    }
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

const findDuplicates = (values) => {
    const seen = new Set();
    const duplicates = new Set();

    values.forEach((value) => {
        if (!value) return;
        if (seen.has(value)) {
            duplicates.add(value);
        } else {
            seen.add(value);
        }
    });

    return Array.from(duplicates);
};

const detectDuplicates = (array, key = "equivalent") => {
    const normalizedValues = array.map((item) => {
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

// Duplicate detection for modal editing targets (including existing targets)
const modalDuplicates = computed(() => {
    const duplicates = new Set();

    detectDuplicates(editingTargets.value).forEach((dup) => duplicates.add(dup));

    const editingNormalized = new Set(
        editingTargets.value
            .map((item) => normalizeEquivalent(item?.equivalent))
            .filter(Boolean)
    );

    if (editingNormalized.size > 0) {
        (form.value?.targets || []).forEach((item, index) => {
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

const hasModalDuplicates = computed(() => modalDuplicates.value.length > 0);

onMounted(() => {
    searchDuplicates();
});

const searchDuplicates = () => {
    duplicates.value = detectDuplicates(form.value?.targets || []);
};

const getMaxIndex = (values) => {
    return maxBy(values, "index")?.index ?? 0;
};

// Toggle for Target editor (QuillEditor vs PrismEditor)
const enableTargetQuillEditor = ref(true);

const deleteTarget = (index) => {
    const targets = form.value?.targets || [];
    if (index >= 0 && index < targets.length) {
        targets.splice(index, 1);
        // Renumber remaining targets sequentially
        targets.forEach((t, i) => {
            const wasAuto = t.value === t.index;
            t.index = i + 1;
            if (wasAuto) t.value = t.index;
        });
        const nextTests = assign(
            props.tests || { targets: [], primes: [], config: {} }
        );
        emit("update:tests", nextTests);
        dataStore.setTests(nextTests);
        dataStore.isDirty = true;
        searchDuplicates();
    }
};

const updateTargetDialog = (index) => {
    selectedIndex.value = index;

    const targets = form.value?.targets || [];
    if (targets[index - 1]) {
        _target.value = assign(targets[index - 1]);
        // Populate bulk editor with the current target for editing
        editingTargets.value = [assign(targets[index - 1])];
        selectedItemsInModal.value = new Set();
        selectAllInModal.value = false;
        bulkTargetsText.value = "";
        bulkEquivalentsText.value = "";
        newItemInModal.value = { target: "", equivalent: "", colors: { normal: null, hover: null, active: null }, value: 0 };
        modal.value = true;
        searchDuplicates();
    }
};

const updateTargetList = () => {
    const nextTests = assign(
        props.tests || { targets: [], primes: [], config: {} }
    );
    if (Array.isArray(nextTests.targets) && selectedIndex.value) {
        nextTests.targets[selectedIndex.value - 1] = assign(_target.value);
        emit("update:tests", nextTests);
        dataStore.setTests(nextTests);
        dataStore.isDirty = true;
    }
    searchDuplicates();
    resetForm();
};

const targetAction = () => saveTargetsFromModal();

const showButtons = ref(false);

const toggleButtons = (index) => {
    const targets = form.value?.targets || [];
    targets.forEach((p, i) => {
        p.showButtons = i === index ? !p.showButtons : false;
    });
};

// Hide all floating buttons when clicking outside
const hideAllButtons = () => {
    const targets = form.value?.targets || [];
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

// select and delete button function start
onBeforeMount(() => {
    const targets = form.value?.targets || [];
    targets.forEach((t) => {
        if (t.selected === undefined) t.selected = false;
    });
});

const allSelected = computed(() => {
    const targets = form.value?.targets || [];
    return targets.length > 0 && targets.every((t) => t.selected);
});

const anySelected = computed(() => {
    const targets = form.value?.targets || [];
    return targets.some((t) => t.selected);
});

const toggleSelectAll = () => {
    const val = !allSelected.value;
    const targets = form.value?.targets || [];
    targets.forEach((t) => (t.selected = val));
};

const deleteSelected = () => {
    const targets = form.value?.targets || [];
    let removed = false;
    for (let i = targets.length - 1; i >= 0; i--) {
        if (targets[i].selected) {
            targets.splice(i, 1);
            removed = true;
        }
    }
    if (removed) {
        // Renumber remaining targets sequentially
        targets.forEach((t, i) => {
            const wasAuto = t.value === t.index;
            t.index = i + 1;
            if (wasAuto) t.value = t.index;
        });
        const nextTests = assign(
            props.tests || { targets: [], primes: [], config: {} }
        );
        emit("update:tests", nextTests);
        dataStore.setTests(nextTests);
        dataStore.isDirty = true;
        searchDuplicates();
    }
};
// select and delete button function End
const isExpanded = ref(false);

const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
};

const csvInput = ref(null);
const csvInputModal = ref(null);

const handleCSVUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    try {
        const text = await file.text();
        const lines = text.trim().split("\n");
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

// CSV upload for bulk modal: populate bulk editors
const triggerCSVUpload = () => {
    csvInputModal.value?.click();
};

const handleCSVUploadModal = async (event) => {
    const file = event.target.files[0];
    if (!file) return;
    try {
        const text = await file.text();
        const rawLines = text.trim().split("\n");

        // Robust CSV line parser that supports quoted fields, commas, and escaped quotes
        const parseCSVLine = (line) => {
            const cells = [];
            let current = '';
            let inQuotes = false;
            let i = 0;
            while (i < line.length) {
                const ch = line[i];
                const next = line[i + 1];
                if (ch === '"') {
                    if (inQuotes && next === '"') {
                        current += '"';
                        i += 2;
                        continue;
                    }
                    inQuotes = !inQuotes;
                    i++;
                    continue;
                }
                if (ch === ',' && !inQuotes) {
                    cells.push(current);
                    current = '';
                    i++;
                    continue;
                }
                current += ch;
                i++;
            }
            cells.push(current);
            return cells.map((c) => c.trim());
        };

        // Skip header row if present
        const lines = rawLines.length > 0 ? rawLines.slice(1) : [];

        const csvData = lines
            .filter(l => l.trim().length > 0)
            .map((line) => {
                const columns = parseCSVLine(line);
                const unwrap = (s) => (s ?? '').replace(/^"|"$/g, '');
                return { target: unwrap(columns[0]), equivalent: unwrap(columns[1]) };
            });
        const targets = csvData.map((i) => i.target).filter((t) => t.trim());
        const equivalents = csvData.map((i) => i.equivalent);
        // Convert to plain text format for Quill editor (avoid HTML conversion)
        const targetsText = targets.join("\n");
        const equivalentsText = equivalents.join("\n");
        bulkTargetsText.value = targetsText;
        bulkEquivalentsText.value = equivalentsText;
    } catch (err) {
        console.error("Error reading CSV file:", err);
        alert("Error reading CSV file. Please make sure it's a valid CSV file.");
    }
    event.target.value = "";
};

// Download CSV template
const downloadCSVTemplate = () => {
    const exampleData = [
        { target: "calm", equivalent: "calm" },
        { target: "protected", equivalent: "protected" },
        { target: "secure", equivalent: "secure" },
        { target: "safe", equivalent: "safe" },
        { target: "comforted", equivalent: "comforted" },
        { target: "supported", equivalent: "supported" },
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

// Store Quill instance when editor is ready
const onQuillReady = (quill) => {
    quillInstance.value = quill;
};

// Insert image by URL into Quill editor (supports a provided instance)
const insertImageByUrlTarget = (instance = null) => {
    const url = window.prompt("Enter image URL");
    if (!url) return;

    // Prefer the provided instance; fallback to stored ref if ever used elsewhere
    const quill = instance || quillInstance.value;
    if (!quill) {
        alert("Quill editor is not ready. Please wait a moment and try again.");
        return;
    }

    let finalUrl = url;
    try {
        const u = new URL(url, window.location.origin);
        if (u.origin !== window.location.origin) {
            finalUrl = `/gallery-proxy?u=${encodeURIComponent(u.href)}`;
        }
    } catch (e) {
        // If URL parsing fails, just use the original URL
    }

    const range = quill.getSelection(true) || { index: quill.getLength() };
    try {
        quill.insertEmbed(range.index, "image", finalUrl, "user");
        // Move cursor after image
        quill.setSelection(range.index + 1, 0, "user");
    } catch (err) {
        alert("Failed to insert image. Please try again.");
        console.error("Quill insertEmbed error:", err);
    }
};

// Open modal to insert multiple image URLs
const openImageUrlModal = (quill = null) => {
    if (quill) {
        activeQuillEditor.value = quill;
    } else if (quillInstance.value) {
        activeQuillEditor.value = quillInstance.value;
    }
    imageUrlsText.value = "";
    showImageUrlModal.value = true;
};

// // Insert multiple images, one per line; clear selection
// const insertImagesFromModal = () => {
//     const quill = activeQuillEditor.value || quillInstance.value;
//     if (!quill) {
//         showImageUrlModal.value = false;
//         imageUrlsText.value = "";
//         return;
//     }
//     const lines = String(imageUrlsText.value || "").split(/\r?\n/).map(s => s.trim()).filter(Boolean);
//     if (lines.length === 0) {
//         showImageUrlModal.value = false;
//         imageUrlsText.value = "";
//         return;
//     }
//     const range = quill.getSelection(true);
//     let insertIndex = range ? range.index : quill.getLength();
//     if (range && range.length > 0) {
//         quill.deleteText(range.index, range.length, "user");
//         insertIndex = range.index;
//     }
//     lines.forEach((url) => {
//         let finalUrl = url;
//         try {
//             const u = new URL(url, window.location.origin);
//             if (u.origin !== window.location.origin) {
//                 finalUrl = `/gallery-proxy?u=${encodeURIComponent(u.href)}`;
//             }
//         } catch (_) {}
//         quill.insertEmbed(insertIndex, "image", finalUrl, "user");
//         insertIndex += 1;
//         quill.insertText(insertIndex, "\n", "user");
//         insertIndex += 1;
//     });
//     quill.setSelection(insertIndex, 0, "user");
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

// Quill bubble toolbar positioning (match TestTargets1 behavior)
const setupQuillToolbarPositioning = (quillInstance) => {
    if (!quillInstance || !quillInstance.theme || !quillInstance.theme.tooltip) return;
    const originalShow = quillInstance.theme.tooltip.show;
    quillInstance.theme.tooltip.show = function() {
        originalShow.call(this);
        const toolbar = this.root.querySelector('.ql-toolbar');
        if (toolbar) {
            toolbar.style.position = 'absolute';
            toolbar.style.right = '-451px';
            toolbar.style.top = '-40px';
            toolbar.style.backgroundColor = '#374151';
            toolbar.style.zIndex = '10000';
        }
    };
    quillInstance.on('selection-change', (range) => {
        if (range && range.length >= 0) {
            setTimeout(() => {
                const toolbar = quillInstance.theme?.tooltip?.root?.querySelector?.('.ql-toolbar');
                if (toolbar) {
                    toolbar.style.position = 'absolute';
                    toolbar.style.left = '-30px';
                    toolbar.style.top = '0px';
                    toolbar.style.borderRadius = '5px';
                    toolbar.style.backgroundColor = '#374151';
                    toolbar.style.zIndex = '10000';
                }
            }, 10);
        }
    });
};

// Add custom image insertion button to bubble toolbar
const addInsertImageButtonToBubble = (quillInstance) => {
    if (!quillInstance || !quillInstance.theme || !quillInstance.theme.tooltip) return;
    const toolbar = quillInstance.theme.tooltip.root.querySelector('.ql-toolbar');
    if (toolbar && !toolbar.querySelector('.ql-insertImageByUrl')) {
        const insertImageBtn = document.createElement('button');
        insertImageBtn.className = 'ql-insertImageByUrl';
        insertImageBtn.type = 'button';
        insertImageBtn.title = 'Insert image by URL';
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
        insertImageBtn.innerHTML = '<svg viewBox="0 0 22 22"> <rect class="ql-stroke" height="10" width="12" x="3" y="4"></rect> <circle class="ql-fill" cx="6" cy="7" r="1"></circle> <polyline class="ql-even ql-fill" points="5 12 5 11 7 9 8 10 11 7 13 9 13 12 5 12"></polyline> </svg>';
        insertImageBtn.addEventListener('click', () => {
            openImageUrlModal(quillInstance);
        });
        toolbar.appendChild(insertImageBtn);
    }
};

// Variables button in bubble toolbar
const addVariablesButtonToBubble = (quillInstance) => {
    if (!quillInstance || !quillInstance.theme || !quillInstance.theme.tooltip) return;
    const originalShow = quillInstance.theme.tooltip.show;
    quillInstance.theme.tooltip.show = function() {
        originalShow.call(this);
        const toolbar = this.root.querySelector('.ql-toolbar');
        if (toolbar && !toolbar.querySelector('.ql-variables-bubble')) {
            const variablesButton = document.createElement('button');
            variablesButton.className = 'ql-variables-bubble';
            variablesButton.type = 'button';
            variablesButton.title = 'Variables';
            variablesButton.innerHTML = 'Variables';
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
            variablesButton.addEventListener('click', (e) => {
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
    const token = `{{${variableName}}}`;
    quill.insertText(index, token, 'user');
    quill.setSelection(index + token.length, 0);

    // If inserting in items table row, we could also mirror token to equivalent if empty
    try {
        if (quill.container && quill.container.closest('tbody')) {
            const row = quill.container.closest('tr');
            if (row) {
                const rowIndex = Array.from(row.parentNode.children).indexOf(row);
                if (editingTargets.value[rowIndex]) {
                    const currentEq = String(editingTargets.value[rowIndex].equivalent || '');
                    editingTargets.value[rowIndex].equivalent = currentEq ? currentEq : token;
                }
            }
        } else if (quill.container && quill.container.closest('.quill-bubble-wrapper')) {
            // Bulk editor: align equivalent line with target line
            const textBeforeCursor = quill.getText(0, index);
            const currentLineIndex = textBeforeCursor.split('\n').length - 1;
            const targetLines = String(bulkTargetsText.value || '').split('\n');
            const equivalentLines = String(bulkEquivalentsText.value || '').split('\n');
            while (equivalentLines.length < targetLines.length) equivalentLines.push('');
            if (!equivalentLines[currentLineIndex]) {
                equivalentLines[currentLineIndex] = token;
            } else {
                equivalentLines[currentLineIndex] += token;
            }
            bulkEquivalentsText.value = equivalentLines.join('\n');
        }
    } catch (_) {}

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

if (typeof window !== 'undefined') {
    try {
        window.addEventListener('target-quill-variable-insert-request', onTargetQuillVariableInsertRequest);
    } catch (_) {}
}

const closeVariablesModal = () => {
    showVariablesModal.value = false;
    activeQuillEditor.value = null;
};
</script>
<template>
    <div
        :class="[
            'flex flex-col transition-all duration-300',
            isExpanded ? 'w-[150vh]' : 'w-[40vh]',
        ]"
    >
        <div
            class="bg-[#15919A] shadow p-4 mb-2 w-[full] justify-between flex flex-row"
        >
            <div class="text-white text-[20px] font-bold">Targets</div>
        </div>

        <!-- Expand and Shrink Button -->
        <div
            class="rounded-full px-2 font-bold flex justify-end bottom-[-51.50rem] relative right-[-1.5rem] shadow-lg"
        >
            <button
                type="button"
                class="bg-[#15919A] rounded-full py-[28px] absolute bottom-[25.25rem] shadow-lg"
                @click="toggleExpand"
            >
                <!-- SVG rotates when expanded -->
                <svg
                    :class="
                        isExpanded
                            ? 'rotate-180 transition-transform duration-300'
                            : 'transition-transform duration-300'
                    "
                    width="30"
                    height="30"
                    viewBox="0 0 55 50"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M27.2578 40.4296L43.1875 26.7793C43.4441 26.5592 43.6501 26.2863 43.7914 25.9792C43.9326 25.672 44.0057 25.338 44.0057 25C44.0057 24.6619 43.9326 24.3279 43.7914 24.0207C43.6501 23.7136 43.4441 23.4407 43.1875 23.2207L27.2578 9.57026C25.7373 8.26753 23.3887 9.34761 23.3887 11.3496V38.6543C23.3887 40.6562 25.7373 41.7363 27.2578 40.4296Z"
                        fill="white"
                    />
                    <rect
                        x="12"
                        y="9"
                        width="5"
                        height="32"
                        rx="2.5"
                        fill="white"
                    />
                </svg>
            </button>
        </div>

        <!-- Warning -->
        <div class="shadow">
            <transition name="slide-right">
                <div
                    v-if="duplicates.length > 0"
                    class="bg-red-500 text-orange-700 text-white p-4 rounded-lg w-[300px] absolute right-[8px] top-[75px]"
                    role="alert"
                >
                    <p
                        class="font-bold mb-2 flex flex-row gap-1 text-[18px] justify-center text-center"
                    >
                        <svg
                            width="25"
                            height="25"
                            viewBox="0 0 25 25"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M4.17746 21.7896H20.821C21.0913 21.7895 21.357 21.7193 21.5921 21.5859C21.8272 21.4524 22.0236 21.2603 22.1622 21.0282C22.3008 20.7961 22.3768 20.532 22.3828 20.2618C22.3889 19.9915 22.3246 19.7243 22.1965 19.4863L13.8752 4.03223C13.2849 2.93652 11.7136 2.93652 11.1233 4.03223L2.80197 19.4863C2.67382 19.7243 2.60961 19.9915 2.61562 20.2618C2.62163 20.532 2.69765 20.7961 2.83625 21.0282C2.97485 21.2603 3.17129 21.4524 3.40637 21.5859C3.64145 21.7193 3.90714 21.7895 4.17746 21.7896Z"
                                stroke="white"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M12.0003 10.2325L12.5003 15L12.9995 10.2345C13.0026 10.204 12.9918 10.1736 12.9678 10.1451C12.9437 10.1165 12.907 10.0905 12.8598 10.0685C12.8126 10.0466 12.7559 10.0292 12.6932 10.0174C12.6305 10.0056 12.5631 9.99971 12.4951 10C12.4283 10.0003 12.3623 10.0066 12.3011 10.0185C12.2398 10.0304 12.1845 10.0477 12.1383 10.0694C12.0922 10.0911 12.0562 10.1167 12.0325 10.1447C12.0088 10.1727 11.9979 10.2026 12.0003 10.2325Z"
                                stroke="white"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M12.5 19.397C12.3069 19.397 12.118 19.3397 11.9575 19.2324C11.7969 19.1251 11.6717 18.9726 11.5978 18.7941C11.5239 18.6157 11.5045 18.4193 11.5422 18.2299C11.5799 18.0405 11.6729 17.8665 11.8095 17.7299C11.946 17.5933 12.12 17.5003 12.3095 17.4626C12.4989 17.4249 12.6953 17.4443 12.8737 17.5182C13.0522 17.5921 13.2047 17.7173 13.312 17.8779C13.4193 18.0385 13.4766 18.2273 13.4766 18.4204C13.4766 18.6794 13.3737 18.9278 13.1905 19.1109C13.0074 19.2941 12.759 19.397 12.5 19.397Z"
                                fill="white"
                            />
                        </svg>
                        Warning! Duplicate Equivalents
                    </p>
                    <p v-for="d in duplicates" :key="d">{{ d }}</p>
                </div>
            </transition>
        </div>

        <div
            :class="[
                'mr-4 ml-2 mt-2 mb-2 c overflow-y-auto custom-scrollbar overflow-x-hidden transition-all duration-300 gap-2',
                isExpanded ? 'grid grid-cols-4' : 'grid grid-cols-1',
            ]"
            v-if="props.tests?.targets && props.tests.targets.length"
        >
            <div class="bg-white shadow border-gray-300 border flex flex-col col-span-1 rounded-xl py-14  group uppercase shadow flex flex-col border border-gray-300 rounded-lg justify-center items-center text-[#323232] hover:bg-[#15919A] hover:text-white font-bold">
                <button
                    type="button"
                    class="w-full h-full text-center items-center justify-center uppercase flex flex-col"
                    @click="addTargetDialog"
                >
                    <svg
                        width="80"
                        height="80"
                        viewBox="0 0 80 80"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M40 17.5V62.5M62.5 40H17.5"
                            stroke="#323232"
                            stroke-width="8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="transition-colors duration-200 group-hover:stroke-white"
                        />
                    </svg>

                    Add targets
                </button>
            </div>

            <TargetCard
                v-for="(target, i) in props.tests?.targets || []"
                :key="'targ' + i"
                class="col-span-3 md:col-span-1"
            >
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
                                class="text-base px-2 py-1 gap-1 font-bold rounded-t-lg inline-flex items-center hover:bg-[#15919A30]"
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

                <template #equivalent>
                    {{ target.equivalent }}
                </template>

                <template #colours>
                    <div class="inline-flex items-center">
                        <span
                            class="px-1 ml-1 text-xs rounded-md text-gray-50"
                            v-if="target.colors.normal"
                            :style="'background-color: ' + target.colors.normal"
                        >
                            N
                        </span>
                        <span
                            class="px-1 ml-1 text-xs rounded-md text-gray-50"
                            v-if="target.colors.hover"
                            :style="'background-color: ' + target.colors.hover"
                        >
                            H
                        </span>
                        <span
                            class="px-1 ml-1 text-xs rounded-md text-gray-50"
                            v-if="target.colors.active"
                            :style="'background-color: ' + target.colors.active"
                        >
                            A
                        </span>
                    </div>
                </template>

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
                    <span v-html="proxiedHtml(target.target)"> </span>
                </template>
            </TargetCard>
        </div>

        
        <div
            v-else
            class="flex items-center h-[65vh] flex-col mx-auto justify-center text-gray-600 prose-2xl font-medium"
        >
            The target is empty!
            <div class="ml-2 mr-2">
                <SecondaryButton class="uppercase" @click="addTargetDialog">
                    Add targets
                </SecondaryButton>
            </div>
        </div>
    </div>


<!-- Hidden Select and Delete All button -->
<transition name="slide-up">
        <div
                v-show="anySelected"
                :class="[
                    'bg-[#15919A] shadow p-4 w-[full] items-center justify-center text-center flex flex-row fixed left-0 right-0 bottom-0 z-50'
                ]"
            >
                <!-- Select and Delete All button -->
                <div class="flex flex-row gap-2 items-center justify-center text-center">
                    <!-- Select Button -->
                    <button
                        type="button"
                        class="px-3 py-1 font-bold bg-white rounded-full"
                        @click="toggleSelectAll"
                    >
                        {{ allSelected ? "Unselect All" : "Select All" }}
                    </button>
                    <!-- Delete Button -->
                    <button
                        type="button"
                        class="px-3 py-1 font-bold bg-white rounded-full"
                        :class="{ 'opacity-50 cursor-not-allowed': !anySelected }"
                        :disabled="!anySelected"
                        @click="deleteSelected"
                    >
                        Delete All
                    </button>

                    <!-- Expand and Shrink Button -->
                    <!-- <Button1 class="px-3 py-1 font-bold bg-white rounded-full " @click="toggleExpand">
                        
                        {{ isExpanded ? 'Shrink' : 'Expand' }}
                    </Button1> -->
                </div>
            </div>
    </transition>

    <JetDialogModal :show="modal" @close="modal = false" maxWidth="6xl">
        <template #title>
            <span class="text-[#15919A] font-bold">
                {{ selectedIndex ? 'Update Target' : 'Add Custom Targets' }}
            </span>
        </template>

        <template #content>
            <div class="space-y-6 mt-2">
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

                <!-- Bulk selection header -->
                <div v-if="!selectedIndex" class="flex items-center justify-between bg-gray-100/80 p-3 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <label class="flex items-center">
                <input
                                type="checkbox"
                                v-model="selectAllInModal"
                                @change="toggleSelectAllInModal"
                                :disabled="editingTargets.length === 0"
                                class="rounded border-gray-300 text-[#15919A] focus:ring-[#15919A] disabled:opacity-50 disabled:cursor-not-allowed"
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
                            size="sm"
                            @click="deleteSelectedItemsInModal"
                        >
                            Delete Selected ({{ selectedItemsInModal.size }})
                        </JetButton>
                    </div>
            </div>

                <!-- Items table -->
                <div class="mt-2 max-h-96 overflow-y-auto border border-gray-200 rounded-md">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-[#D4ECEF] text-base sticky top-0 z-10">
                            <tr>
                                <th v-if="!selectedIndex" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-1">
                                    <input
                                        type="checkbox"
                                        v-model="selectAllInModal"
                                        @change="toggleSelectAllInModal"
                                        :disabled="editingTargets.length === 0"
                                        class="rounded border-gray-300 text-[#15919A] focus:ring-[#15919A] disabled:opacity-50 disabled:cursor-not-allowed"
                                    />
                                </th>
                                <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-1/12">Index</th>
                                <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-5/12">Target</th>
                                <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-1/12">Value</th>
                                <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-3/12">Equivalent</th>
                                <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-1/12">Unselect</th>
                                <th v-if="!selectedIndex" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-1/12">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <tr
                                v-for="(item, index) in editingTargets"
                                :key="index"
                                :class="{ 'bg-[#15919A30]': !selectedIndex && selectedItemsInModal.has(index) }"
                            >
                                <td v-if="!selectedIndex" class="px-3 py-1">
                                    <input
                                        type="checkbox"
                                        :checked="selectedItemsInModal.has(index)"
                                        @change="toggleItemSelectionInModal(index)"
                                        class="rounded border-gray-300 text-[#15919A] focus:ring-[#15919A]"
                                    />
                                </td>
                                <td class="px-3 py-1">
                                    <div class="w-full h-[34px] text-sm bg-gray-50 border border-gray-300 rounded-md px-3 py-2 flex items-center">
                                        {{ item.index }}
                                    </div>
                                </td>
                                <td class="px-3 py-1 w-5/12">
                                    <div v-if="enableTargetQuillEditor" class="quill-bubble-wrapper border border-gray-300 rounded-md">
                                        <QuillEditor
                                            theme="bubble"
                                            :content="parseVariablesToElements(item.target)"
                                            @update:content="updateItemFieldInModal(index, 'target', parseElementsToVariables($event))"
                                            @ready="(quill) => { bulkTargetsQuill = quill; setupQuillToolbarPositioning(quill); addInsertImageButtonToBubble(quill); addVariablesButtonToBubble(quill); }"
                                            contentType="html"
                                            placeholder="Click to edit target content..."
                                            :toolbar="[
                                                [{ 'header': [1, 2, 3, false] }],
                                                ['bold', 'italic', 'underline'],
                                                [{ 'align': [] }],
                                                [{ 'script': 'sub'}, { 'script': 'super' }],
                                                [{ 'color': [] }, { 'background': [] }]
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
                                <td class="px-3 py-1 w-1/12">
                                    <JetInput
                                        :model-value="item.value"
                                        @update:model-value="updateItemFieldInModal(index, 'value', $event)"
                                        type="number"
                                        class="w-full h-[34px] text-sm focus:ring-[#15919A] focus:border-[#15919A]"
                                        placeholder="Value"
                                    />
                                </td>
                                <td class="px-3 py-1 w-3/12">
                                    <JetInput
                                        :model-value="item.equivalent"
                                        @update:model-value="updateItemFieldInModal(index, 'equivalent', $event)"
                                        type="text"
                                        class="w-full text-sm"
                                        placeholder="Equivalent"
                                    />
                                </td>
                                <td class="px-3 py-1 w-1/12">
                                    <div class="flex items-center justify-center">
                                        <JetCheckbox
                                            :checked="item.can_unselect"
                                            @update:checked="updateItemFieldInModal(index, 'can_unselect', $event)"
                                            class="rounded border-gray-300 text-[#15919A] focus:ring-[#15919A]"
                                        />
                                    </div>
                                </td>
                                <td v-if="!selectedIndex" class="px-3 py-1 w-1/12">
                                    <div class="bg-white p-2 border shadow-sm rounded-md justify-center items-center flex">
                                        <button
                                            type="button"
                                            @click="deleteItemFromModal(index)"
                                            class="text-red-600 hover:text-red-800 transition-colors duration-200"
                                            title="Delete this target"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                                </td>
                            </tr>
                            <tr v-if="editingTargets.length === 0">
                                <td :colspan="selectedIndex ? 5 : 7" class="px-3 py-8 text-center text-gray-500">
                                    No targets added yet. Use the form below to add targets.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                </div>

                <!-- Single add row -->
                <!-- <div v-if="!selectedIndex" class="grid grid-cols-12 gap-3 mb-2">
                    <div class="col-span-4">
                        <div class="quill-bubble-wrapper border border-gray-300 rounded-md">
                            <QuillEditor
                                :key="quillEditorKey"
                                theme="bubble"
                                :content="newItemInModal.target"
                                @update:content="newItemInModal.target = $event"
                                @ready="(quill) => { setupQuillToolbarPositioning(quill); addInsertImageButtonToBubble(quill); addVariablesButtonToBubble(quill); }"
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
                            class="w-full min-h-[40px] text-sm border border-gray-300 rounded-md focus:ring-[#15919A] focus:border-[#15919A]"
                        />
                </div>
                    <div class="col-span-3">
                        <JetInput
                            v-model="newItemInModal.value"
                        type="number"
                            placeholder="Value"
                            class="w-full min-h-[40px] text-sm border border-gray-300 rounded-md focus:ring-[#15919A] focus:border-[#15919A]"
                    />
                </div>
                    <div class="col-span-2">
                        <JetButton @click="addNewItemInModal" class="text-sm py-1 h-[40px] w-full text-center justify-center">
                            Add
                        </JetButton>
            </div>
                </div> -->

                <!-- Bulk add -->
                <div v-if="!selectedIndex" class="mt-2">
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
                            <div class="quill-bubble-wrapper border border-gray-300 rounded-md mt-1 c h-[120px]">
                                <QuillEditor
                                    :key="bulkQuillEditorKey"
                                    theme="bubble"
                                    :content="bulkTargetsText"
                                    @update:content="bulkTargetsText = $event"
                                    @ready="(quill) => { bulkTargetsQuill = quill; setupQuillToolbarPositioning(quill); addInsertImageButtonToBubble(quill); addVariablesButtonToBubble(quill); }"
                                    contentType="html"
                                    placeholder="Enter targets, one per line..."
                                    :toolbar="[
                                        [{ 'header': [1, 2, 3, false] }],
                                        ['bold', 'italic', 'underline'],
                                        [{ 'align': [] }],
                                        [{ 'script': 'sub'}, { 'script': 'super' }],
                                        [{ 'color': [] }, { 'background': [] }]
                                    ]"
                                    class="min-h-[120px] text-sm"
                                />
                </div>
                </div>
                <div>
                            <JetLabel value="Equivalents (align by line with targets)" />
                            <textarea
                                v-model="bulkEquivalentsText"
                                class="mt-1 w-full min-h-[120px] text-sm border border-gray-300 rounded-md focus:ring-[#15919A] focus:border-[#15919A]"
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500">Leave a line blank if a target has no equivalent.</p>
                </div>
                    </div>
                    <div class="flex justify-start -mt-2 gap-2">

                        <JetButton @click="addBulkTargetsInModal" class="text-sm">Add Targets</JetButton>

                        <div class="relative group">
                        <JetButton variant="gray-100" @click="triggerCSVUpload" class="text-sm">Upload CSV</JetButton>
                        <div
                                class="absolute bottom-full left-[-90px] mb-1.5 px-2 py-2 rounded text-xs text-white bg-[#15919a] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                role="tooltip"
                                >
                                Use this option to import a list of targets from a file.<br/> The CSV file must be formatted correctly to match<br/> the platform's required structure.                                </div>
                        </div>


                     <div class="relative group">
                        <JetButton variant="gray-100" @click="downloadCSVTemplate" class="text-sm">Download CSV Template</JetButton>
                        <div
                                class="absolute bottom-full left-[-30px] mb-1.5 px-2 py-2 rounded text-xs text-white bg-[#15919a] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                role="tooltip"
                                >
                                Click this to download a blank template file.<br/>Use this template to ensure your targets and <br/> equivalents are structured correctly before <br/>using the Upload CSV function. 
                                </div>
                        </div>

                        <input ref="csvInputModal" type="file" accept=".csv" @change="handleCSVUploadModal" class="hidden" />
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <JetButton variant="default" @click="resetForm" class="mr-2">CANCEL</JetButton>
            <JetButton 
                variant="primary" 
                @click="targetAction"
                :disabled="editingTargets.length === 0 || hasModalDuplicates"
                :class="{
                    'opacity-50 cursor-not-allowed': editingTargets.length === 0 || hasModalDuplicates
                }"
            >
                {{ selectedIndex ? 'UPDATE TARGET' : 'SAVE TARGETS' }}
            </JetButton>
        </template>
    </JetDialogModal>

    <!-- Variables Modal -->
    <JetDialogModal :show="showVariablesModal" @close="closeVariablesModal" maxWidth="4xl">
        <!-- <template #title>Variables</template> -->
        <template #content>
            <IndexVariables :project="$page.props.project" :survey="$page.props.survey" :enable-apply="true" apply-event-name="target-quill-variable-insert-request" />
        </template>
        <template #footer>
            <JetButton variant="default" class="uppercase" size="sm" @click="closeVariablesModal">
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
            <span class="text-[#15919A] font-bold">Convert Text to Image</span>
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

    <!-- Duplicate Value Modal -->
    <JetDialogModal :show="showDuplicateValueModal" @close="showDuplicateValueModal = false" maxWidth="md">
        <template #title>
            <span class="text-red-600 font-bold">Duplicate value</span>
        </template>
        <template #content>
            <div class="space-y-2">
                <p class="text-sm text-gray-700">{{ duplicateValueMessage }}</p>
            </div>
        </template>
        <template #footer>
            <JetButton variant="primary" @click="showDuplicateValueModal = false">OK</JetButton>
        </template>
    </JetDialogModal>

    <!-- Image URLs Modal -->
    <JetDialogModal :show="showImageUrlModal" @close="() => { showImageUrlModal = false }" maxWidth="lg">
        <template #title>
            <span class="text-[#15919A] font-bold">Insert image URLs</span>
        </template>
        <template #content>
            <div class="space-y-3">
                <JetLabel value="Image URLs (one per line)" />
                <textarea
                    v-model="imageUrlsText"
                    placeholder="https://express.connoisseur-suite.com/gallery/UNAbU9NR0CJ7U6gUlG0Y3nBezz0gjWao.png"
                    class="mt-1 w-full min-h-[140px] text-sm border border-gray-300 rounded-md focus:ring-[#15919A] focus:border-[#15919A]"
                ></textarea>
                <p class="text-xs text-gray-500">Each non-empty line will be inserted as an image on its own line.</p>
            </div>
        </template>
        <template #footer>
            <JetButton variant="default" class="mr-2" @click="() => { showImageUrlModal = false }">CANCEL</JetButton>
            <JetButton variant="primary" @click="insertImagesFromModal">INSERT</JetButton>
        </template>
    </JetDialogModal>
</template>
<style scoped="true">
.c {
    max-height: calc(100vh - 180px);
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
    --checkbox-color: #15919a;

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
    --checkbox-color: #15919a;
    --checkbox-bg: #dbeafe;
    --checkbox-border: #15919a;
}

.ios-checkbox.green {
    --checkbox-color: #10b981;
    --checkbox-bg: #15919a;
    --checkbox-border: #15919a;
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
    scrollbar-color: #15919a transparent; /* thumb and track color */
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
    background-color: #15919a; /* light gray thumb */
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

/* Quill Bubble Editor Custom Styles (scoped) */
.quill-bubble-wrapper { position: relative; }
.ql-toolbar { background-color: #374151 !important; color: #050505 !important; }
.quill-bubble-wrapper .ql-toolbar { position: absolute !important; top: -40px !important; left: 0 !important; background: #374151; border-radius: 8px; padding: 8px 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.05); z-index: 10000; display: flex; align-items: center; gap: 8px; transform: none !important; }
.quill-bubble-wrapper .ql-toolbar::before { content: ''; position: absolute; top: 100%; left: 0px; width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent; border-top: 8px solid #374151; }
.quill-bubble-wrapper .ql-toolbar .ql-formats { margin: 0; display: flex; align-items: center; gap: 4px; }
.quill-bubble-wrapper .ql-toolbar button { color: #d1d5db; background: transparent; border: none; padding: 4px; border-radius: 4px; cursor: pointer; transition: all 0.2s ease; }
.quill-bubble-wrapper .ql-toolbar button:hover { background: rgba(255,255,255,0.1); color: #fff; }
.quill-bubble-wrapper .ql-toolbar button.ql-active { background: #15919a; color: #fff; }
.quill-bubble-wrapper .ql-toolbar .ql-picker { color: #d1d5db; }
.quill-bubble-wrapper .ql-toolbar .ql-picker-label { color: #d1d5db; background: transparent; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; transition: all 0.2s ease; }
.quill-bubble-wrapper .ql-toolbar .ql-picker-label:hover { background: rgba(255,255,255,0.1); color: #fff; }
.quill-bubble-wrapper .ql-toolbar .ql-picker.ql-expanded .ql-picker-label { background: rgba(255,255,255,0.1); color: #fff; }
.quill-bubble-wrapper .ql-toolbar .ql-picker-options { background: #374151; border: 1px solid #4b5563; border-radius: 6px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
.quill-bubble-wrapper .ql-toolbar .ql-picker-item { color: #d1d5db; padding: 8px 12px; cursor: pointer; transition: all 0.2s ease; }
.quill-bubble-wrapper .ql-toolbar .ql-picker-item:hover { background: rgba(255,255,255,0.1); color: #fff; }
.quill-bubble-wrapper .ql-editor { min-height: 40px; padding: 8px 12px; border: none; outline: none; font-size: 14px; line-height: 1.5; }
.quill-bubble-wrapper .ql-editor.ql-blank::before { color: #9ca3af; font-style: normal; left: 12px; right: 12px; }
.quill-bubble-wrapper .ql-container { border: none; font-family: inherit; }
.quill-bubble-wrapper .ql-container.ql-bubble { border: none; }

/* Bring active/focused editor above neighbors */
.quill-bubble-wrapper:focus-within { z-index: 10000; }

/* Variables and Insert Image buttons styling */
.quill-bubble-wrapper .ql-toolbar .ql-variables-bubble {
    color: #ffffff;
    border: none;
    border-radius: 4px;
    padding: 4px 8px;
    margin-left: 4px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.2s ease;
}
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
.quill-bubble-wrapper .ql-toolbar .ql-insertImageByUrl:hover { background: rgba(255,255,255,0.1); color: #fff; }
.quill-bubble-wrapper .ql-toolbar .ql-insertImageByUrl svg { width: 16px; height: 16px; }

.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 200ms ease-out;
}
.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(20px);
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
