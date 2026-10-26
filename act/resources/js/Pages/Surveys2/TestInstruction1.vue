<script setup>
import { BASE_PATH } from "@/utils/basePath";
// Import Native Quill
import Quill from "quill";
import { QuillEditor } from "@vueup/vue-quill";
import "quill/dist/quill.snow.css";
import { PrismEditor } from "vue-prism-editor";
import "vue-prism-editor/dist/prismeditor.min.css"; // import the styles somewhere
// import highlighting library (you can use any library you want just return html string)
import { highlight, languages } from "prismjs/components/prism-core";
import "prismjs/components/prism-clike";
import "prismjs/components/prism-javascript";
import "prismjs/components/prism-markup";
import "prismjs/components/prism-css";
import "prismjs/themes/prism-tomorrow.css"; // import syntax highlighting styles

import CustomSwitch from "@/Components/Switch.vue";
import JetLabel from "@/Jetstream/Label.vue";
import JetCheckbox from "@/Jetstream/Checkbox.vue";
import CustomInput from "@/Components/Input.vue";
import IndexVariables from "@/Pages/Variables2/Index.vue";
import Tooltip from "./Tooltip.vue";

import {
    inject,
    ref,
    watch,
    provide,
    onBeforeMount,
    onMounted,
    nextTick,
    reactive,
    computed,
} from "vue";
import { usePage } from "@inertiajs/vue3";
import { debounce } from "lodash";
import JetButton from "@/Componentsv2/Button.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import Toggle from "@/Jetstream/Toggle.vue";
import Helpers from "@/Mixins/Helpers";
import TestCriteria from "./TestCriteria.vue";
import QuillContainer from "@/Components/QuillEditor.vue";
import {
    addVariablesToolbarButton,
    addImageInsertionToolbarButton,
    insertPlaceholderAtCursor,
    PLACEHOLDER_TOKEN,
} from "@/Components/QuillEditor.vue";

const props = defineProps({
    survey: {
        type: Object,
        default: () => ({}),
    },
    selectedTrialMode: {
        type: String,
        default: null,
    },
    variables: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["trialModeChanged"]);

const { $testState, assign } = Helpers();

const enableQuillEditor = inject("ck");
enableQuillEditor.value = true;
const quillEditor = ref(null);
const quillInstances = ref({});
const lastSelectionByEditor = ref({});
const activeEditorId = ref(null);
// Image URL modal state
const showImageUrlModal = ref(false);
const imageUrlsText = ref("");
const activeQuillEditor = ref(null);

const form = inject("form");
console.log("form", form);

const themeValue = computed(() => {
    const candidates = [
        form?.config2?.theme,
        form?.config?.theme,
        props?.survey?.config2?.theme,
        props?.survey?.config?.theme,
        props?.survey?.theme,
    ];

    const value = candidates.find(
        (candidate) =>
            candidate !== undefined && candidate !== null && candidate !== ""
    );

    return value === undefined ? null : Number(value);
});

watch(
    themeValue,
    (newTheme, previousTheme) => {
        if (Number(newTheme) === 2 && Number(previousTheme) !== 2) {
            form.config.bottom_text = "";
            const footerQuill = quillInstances.value?.["footer-quill"];
            if (footerQuill) {
                try {
                    footerQuill.root.innerHTML = "";
                    footerQuill.update();
                } catch (_) {}
            }
        }
    },
    { immediate: true }
);

// Resolve variables from page props (preferred) or passed props
const page = usePage();
const availableVariables = computed(() => {
    const pageVars = page?.props?.variables;
    const propVars = props.variables;
    const resolved = Array.isArray(pageVars)
        ? pageVars
        : Array.isArray(propVars)
        ? propVars
        : [];
    return resolved;
});

// Normalize HTML to use proxy for <img src> so previews hit cache
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
                        `${BASE_PATH}/gallery-proxy?u=${encodeURIComponent(u.href)}`
                    );
                }
            } catch (_) {}
        });
        return doc.body.innerHTML;
    } catch (_) {
        return html;
    }
}

// Debounce the emitUpdate function to avoid firing it on every keystroke
const debouncedEmitUpdate = debounce(() => {
    // Deep clone from the live form to ensure latest content is emitted
    let nextForm;
    try {
        nextForm =
            typeof structuredClone === "function"
                ? structuredClone(form)
                : JSON.parse(JSON.stringify(form));
    } catch (e) {
        nextForm = JSON.parse(JSON.stringify(form));
    }
    // Debug: log new instructions before emit
    try {
        console.log("Instruction update (debounced emit):", {
            title: form?.config?.title,
            main_header: form?.config?.main_header,
            bottom_text: form?.config?.bottom_text,
        });
    } catch (e) {}
    // Note: No emit needed here as form is injected and reactive
}, 500); // Wait for 500ms of inactivity before firing

const emitUpdate = () => {
    // This function can be used for immediate, non-debounced updates if needed elsewhere.
    // For now, we'll route everything through the debounced version.
    debouncedEmitUpdate();
};

const quillToolbar = [
    // Inline styles
    ["bold", "italic", "underline", "strike"],
    // Headers and font size
    [{ header: [1, 2, 3, false] }],
    [{ size: ["small", false, "large", "huge"] }],
    // Lists
    [{ list: "ordered" }, { list: "bullet" }],
    // Script (sub/super)
    [{ script: "sub" }, { script: "super" }],
    // Indent
    [{ indent: "-1" }, { indent: "+1" }],
    // Text direction and alignment
    [{ direction: "rtl" }],
    [{ align: [] }],
    // Color pickers
    [{ color: [] }, { background: [] }],
];

const showReplaceModal = ref(false);

// Map editors to their corresponding config keys
const editorConfigMap = {
    "title-quill": "title",
    "header-quill": "main_header",
    "footer-quill": "bottom_text",
    "modal-quill": "title",
};

const getConfigKeyForEditor = (editorId) => editorConfigMap[editorId];

const readQuillContent = (quill) =>
    parseElementsToVariables(quill.root.innerHTML);

const hasHtmlContent = (html) => {
    if (!html || typeof html !== "string") return false;
    const normalized = html.replace(/&nbsp;/gi, " ").trim();
    if (!normalized) return false;

    try {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        const textContent =
            doc.body?.textContent?.replace(/\s+/g, "").length || 0;
        if (textContent > 0) {
            return true;
        }
        return Boolean(
            doc.body?.querySelector(
                "img, picture, video, svg, iframe, object, canvas"
            )
        );
    } catch (_) {
        return normalized.length > 0;
    }
};

const sanitizeHtmlValue = (html) => {
    if (!html || typeof html !== "string") return "";
    return hasHtmlContent(html) ? html : "";
};

const updateConfigFromQuill = (editorId, quill) => {
    const key = getConfigKeyForEditor(editorId);
    if (!key) return;
    const newValue = sanitizeHtmlValue(readQuillContent(quill));
    if (form.config[key] !== newValue) {
        form.config[key] = newValue;
        emitUpdate();
    }
};

// Helpers for placeholder insert/cleanup/replace
const cleanupPlaceholders = (editorId) => {
    const quill = editorId ? quillInstances.value[editorId] : null;
    if (!quill) return;
    const html = quill.root.innerHTML || "";
    const cleaned = html.replaceAll(PLACEHOLDER_TOKEN, "");
    if (cleaned !== html) {
        quill.root.innerHTML = cleaned;
        updateConfigFromQuill(editorId, quill);
    }
};

// Initialize Quill instances
const initializeQuill = (editorId, content) => {
    nextTick(() => {
        const editorElement = document.getElementById(editorId);

        if (editorElement) {
            // Properly destroy the existing instance and its toolbar
            if (quillInstances.value[editorId]) {
                const quill = quillInstances.value[editorId];
                const toolbar = quill.container.previousSibling;
                if (toolbar && toolbar.classList.contains("ql-toolbar")) {
                    toolbar.remove();
                }
                quillInstances.value[editorId] = null;
            }

            // Clear the editor element content
            editorElement.innerHTML = "";

            try {
                const quill = new Quill(editorElement, {
                    theme: "snow",
                    modules: {
                        toolbar: quillToolbar,
                    },
                });

                quillInstances.value[editorId] = quill;

                // Global button initialization for all editors (Variables and Image buttons)
                try {
                    addVariablesToolbarButton(quill, editorId);
                } catch (error) {
                    console.warn(
                        `Failed to add variables button to ${editorId}:`,
                        error
                    );
                }

                try {
                    addImageInsertionToolbarButton(
                        quill,
                        editorId,
                        insertImageByUrl
                    );
                } catch (error) {
                    console.warn(
                        `Failed to add image insertion button to ${editorId}:`,
                        error
                    );
                }

                // Track the last known selection per editor to support modal insertions
                quill.on("selection-change", (range) => {
                    if (range && typeof range.index === "number") {
                        lastSelectionByEditor.value[editorId] = range;
                        activeEditorId.value = editorId;
                    }
                });

                // Listen for toolbar custom event to open the replace modal
                try {
                    quill.container.addEventListener(
                        "quill-open-variables",
                        (e) => {
                            try {
                                activeEditorId.value = editorId;
                                openReplaceModal(editorId);
                            } catch (_) {}
                        }
                    );
                } catch (_) {}

                // Set initial content and parse variables
                if (content) {
                    // Parse {{variable_name}} patterns to styled elements
                    const parsedContent = parseVariablesToElements(content);
                    quill.root.innerHTML = parsedContent;
                }

                // Watch for content changes (simplified)
                quill.on("text-change", () =>
                    updateConfigFromQuill(editorId, quill)
                );

                console.log(`Quill initialized for ${editorId}`);
            } catch (error) {
                console.error(
                    `Failed to initialize Quill for ${editorId}:`,
                    error
                );
            }
        } else {
            console.log(`Element ${editorId} not found, retrying...`);
            // Retry after a short delay if element not found
            setTimeout(() => {
                const retryElement = document.getElementById(editorId);
                if (retryElement) {
                    initializeQuill(editorId, content);
                }
            }, 100);
        }
    });
};

// Add new state for trial mode selection
const selectedTrialMode = ref(props.selectedTrialMode);

const addSegment = () => {
    // $state.criteria = assign(form);
    return $testState.addSegment();
};

// Variables modal functions
const openReplaceModal = (editorId = null) => {
    activeEditorId.value = editorId || activeEditorId.value;
    showReplaceModal.value = true;
};

const closeReplaceModal = () => {
    showReplaceModal.value = false;
    const editorId = activeEditorId.value;
    const quill = editorId ? quillInstances.value[editorId] : null;
    if (quill) {
        try {
            const html = quill.root.innerHTML || "";
            // Remove all placeholder tokens if user cancels
            const cleaned = html.replaceAll("{{variale_name}}", "");
            if (cleaned !== html) {
                quill.root.innerHTML = cleaned;
                updateConfigFromQuill(editorId, quill);
            }
        } catch (_) {}
    }
};

const insertVariable = (rawVariableName) => {
    const variableName = normalizeVariableName(rawVariableName);
    const editorId = activeEditorId.value;
    const quill = quillInstances.value[editorId];
    if (!quill) {
        showReplaceModal.value = false;
        return;
    }
    try {
        let html = quill.root.innerHTML || "";
        // Replace all instances of {{Hello}} with the chosen variable token
        const updated = html.replaceAll(
            "{{variale_name}}",
            `{{${variableName}}}`
        );
        // Re-parse to styled elements in the editor view
        quill.root.innerHTML = parseVariablesToElements(updated);
        // Update underlying config
        updateConfigFromQuill(editorId, quill);
    } catch (_) {}
    showReplaceModal.value = false;
};

// Accept insert requests from Variables2/Index.vue "Insert" button
function onVariableInsertRequest(e) {
    try {
        const raw = e?.detail?.name;
        if (!raw) return;
        const editorId = activeEditorId.value || (selectedTrialMode.value === "descriptive" ? "title-quill" : "header-quill");
        // Prefer Quill editor if enabled and instance exists
        const quill = quillInstances.value[editorId];
        if (enableQuillEditor.value && quill) {
            insertVariable(raw);
        } else {
            // Fallback to appending into underlying config fields so plain inputs get the token
            const token = `{{${normalizeVariableName(raw)}}}`;
            if (selectedTrialMode.value === "descriptive") {
                form.config.title = (form.config.title || "").includes(token)
                    ? form.config.title
                    : ((form.config.title || "") + (form.config.title ? " " : "") + token);
            } else {
                form.config.main_header = (form.config.main_header || "").includes(token)
                    ? form.config.main_header
                    : ((form.config.main_header || "") + (form.config.main_header ? " " : "") + token);
            }
            emitUpdate();
            showReplaceModal.value = false;
        }
    } catch (_) {}
}

if (typeof window !== "undefined") {
    window.addEventListener("variable-insert-request", onVariableInsertRequest);
}

const onVariableSelect = () => {};

const stripParagraphsAroundImages = (html) => {
    if (!html) return html;
    try {
        return String(html).replace(
            /<p>\s*(<(?:img|picture)\b[^>]*>.*?)\s*<\/p>/gis,
            "$1"
        );
    } catch (_) {
        return html;
    }
};

// Convert {{variable_name}} patterns to styled elements
const parseVariablesToElements = (htmlContent) => {
    if (!htmlContent) return htmlContent;

    const normalized = stripParagraphsAroundImages(htmlContent);
    // Replace {{variable_name}} with styled spans
    let parsedContent = normalized.replace(
        /\{\{([^}]+)\}\}/g,
        (match, variableName) => {
            return `<span class="ql-variable" data-name="${variableName}" contenteditable="false" style="background: #15919A; color: #fff; padding: 4px 8px; border-radius: 6px; font-weight: 600; display: inline; margin: 0 2px; cursor: not-allowed; user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; box-shadow: 2px 2px 4px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.3); border: 1px solid #0C7C84;">${variableName}</span>`;
        }
    );

    // Apply proxy transformation to image URLs for caching
    return proxiedHtml(parsedContent);
};

// Convert styled elements back to {{variable_name}} format for saving
const parseElementsToVariables = (htmlContent) => {
    if (!htmlContent) return htmlContent;

    const normalized = stripParagraphsAroundImages(htmlContent);
    // Replace styled spans with {{variable_name}}
    return normalized.replace(
        /<span class="ql-variable"[^>]*data-name="([^"]*)"[^>]*>.*?<\/span>/g,
        "{{$1}}"
    );
};

const normalizeVariableName = (name) => {
    if (!name) return "";
    return String(name)
        .replace(/^\{\{\s*/, "")
        .replace(/\s*\}\}$/, "")
        .trim();
};

const insertVariableCard = (rawVariableName, targetEditor = null) => {
    const variableName = normalizeVariableName(rawVariableName);

    // Try to find the target quill instance
    const resolvedTargetEditor =
        targetEditor || activeEditorId.value || "header-quill";
    const quill = quillInstances.value[resolvedTargetEditor];

    if (quill) {
        try {
            // Get current selection or default to end
            let range = quill.getSelection();
            if (!range || range.index === null) {
                const length = quill.getLength();
                range = { index: length - 1, length: 0 };
            }

            // Create the variable element directly in the DOM
            const variableElement = document.createElement("span");
            variableElement.className = "ql-variable";
            variableElement.setAttribute("data-name", variableName);
            variableElement.setAttribute("contenteditable", false);
            variableElement.style.cssText =
                "background: #15919A; color: #fff; padding: 4px 8px; border-radius: 6px; font-weight: 600; display: inline; margin: 0 2px; cursor: not-allowed; user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; box-shadow: 2px 2px 4px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.3); border: 1px solid #0C7C84;";
            variableElement.textContent = variableName; // Display just the variable name, not {{variableName}}

            // Insert the element directly into the Quill editor's DOM at the current cursor position
            const editorElement = quill.root;

            // Find the current paragraph or create one if needed
            let currentParagraph = editorElement.querySelector("p");
            if (!currentParagraph) {
                currentParagraph = document.createElement("p");
                editorElement.appendChild(currentParagraph);
            }

            // Get the current cursor position within the paragraph
            const selection = window.getSelection();
            let insertNode = null;
            let insertOffset = 0;

            if (selection.rangeCount > 0) {
                const selRange = selection.getRangeAt(0);
                const container = selRange.commonAncestorContainer;

                // If we're inside the current paragraph, find the exact insertion point
                if (currentParagraph.contains(container)) {
                    insertNode = container;
                    insertOffset = selRange.startOffset;
                }
            }

            if (insertNode && insertNode.nodeType === Node.TEXT_NODE) {
                // Split the text node and insert the variable
                const beforeText = insertNode.textContent.substring(
                    0,
                    insertOffset
                );
                const afterText =
                    insertNode.textContent.substring(insertOffset);

                // Update the text node with the before text
                insertNode.textContent = beforeText;

                // Insert the variable element
                insertNode.parentNode.insertBefore(
                    variableElement,
                    insertNode.nextSibling
                );

                // Insert a space
                const spaceNode = document.createTextNode(" ");
                insertNode.parentNode.insertBefore(
                    spaceNode,
                    insertNode.nextSibling
                );

                // Create a new text node for the after text if needed
                if (afterText) {
                    const afterTextNode = document.createTextNode(afterText);
                    insertNode.parentNode.insertBefore(
                        afterTextNode,
                        insertNode.nextSibling
                    );
                }
            } else {
                // Fallback: append to the end of the current paragraph
                currentParagraph.appendChild(variableElement);
                const spaceNode = document.createTextNode(" ");
                currentParagraph.appendChild(spaceNode);
            }

            // Update Quill's content
            quill.update();

            // Update form data after insertion via shared helper
            updateConfigFromQuill(resolvedTargetEditor, quill);
        } catch (error) {
            console.error("Error inserting variable from card:", error);
        }
    } else {
        // Editor disabled: fall back to appending tokens directly
    }

    // Preserve original behavior: append token to title and header only
    const token = `{{${variableName}}}`;
    const appendToken = (val) => {
        const base = val || "";
        return base.includes(token) ? base : base ? base + " " + token : token;
    };
    form.config.title = appendToken(form.config.title);
    form.config.main_header = appendToken(form.config.main_header);
    emitUpdate();

    // Don't close modal automatically - let user insert multiple variables
};

onBeforeMount(() => {
    $testState.criteria = form;

    // Set the initial trial mode from props
    if (props.selectedTrialMode) {
        selectedTrialMode.value = props.selectedTrialMode;
        
        // Set the appropriate trial configuration based on selection
        if (props.selectedTrialMode === "descriptive") {
            form.config.practice_trial = false;
            form.config.main_trial = false;
        } else if (props.selectedTrialMode === "practice") {
            form.config.practice_trial = true;
            form.config.main_trial = false;
        } else if (props.selectedTrialMode === "main") {
            form.config.practice_trial = false;
            form.config.main_trial = true;
        }
    }

    // $testState.criteria = {
    //     config: form.config
    // }
    //console.log($state.criteria)
});

onMounted(() => {
    // Wait for DOM to be fully rendered before initializing Quill
    setTimeout(() => {
        if (enableQuillEditor.value && selectedTrialMode.value) {
            if (selectedTrialMode.value === "descriptive") {
                initializeQuill("title-quill", form.config.title);
            } else {
                initializeQuill("header-quill", form.config.main_header);
                initializeQuill("footer-quill", form.config.bottom_text);
            }
        }
    }, 200);
});

provide("testState", $testState);

watch(enableQuillEditor, (newValue) => {
    if (newValue) {
        setTimeout(() => {
            if (selectedTrialMode.value === "descriptive") {
                initializeQuill("title-quill", form.config.title);
            } else {
                initializeQuill("header-quill", form.config.main_header);
                initializeQuill("footer-quill", form.config.bottom_text);
            }
        }, 200);
    } else {
        Object.keys(quillInstances.value).forEach((editorId) => {
            if (quillInstances.value[editorId]) {
                const quill = quillInstances.value[editorId];
                const cleanContent = parseElementsToVariables(
                    quill.root.innerHTML
                );

                if (editorId === "title-quill") {
                    form.config.title = cleanContent;
                } else if (editorId === "header-quill") {
                    form.config.main_header = cleanContent;
                } else if (editorId === "footer-quill") {
                    form.config.bottom_text = cleanContent;
                }
                emitUpdate();

                quillInstances.value[editorId] = null;
            }
        });
    }
});

// Watch for trial mode changes and re-initialize editors
watch(selectedTrialMode, (newMode, oldMode) => {
    if (newMode !== oldMode && newMode) {
        // Clean up existing editors first
        Object.keys(quillInstances.value).forEach((editorId) => {
            if (quillInstances.value[editorId]) {
                const quill = quillInstances.value[editorId];
                const toolbar = quill.container.previousSibling;
                if (toolbar && toolbar.classList.contains("ql-toolbar")) {
                    toolbar.remove();
                }
                quillInstances.value[editorId] = null;
            }
        });

        // Re-initialize editors after a short delay to ensure DOM is updated
        if (enableQuillEditor.value) {
            nextTick(() => {
                setTimeout(() => {
                    if (newMode === "descriptive") {
                        initializeQuill("title-quill", form.config.title);
                    } else {
                        initializeQuill(
                            "header-quill",
                            form.config.main_header
                        );
                        initializeQuill(
                            "footer-quill",
                            form.config.bottom_text
                        );
                    }
                }, 300);
            });
        }
    }
});

// Watch for changes in the selectedTrialMode prop from parent
watch(
    () => props.selectedTrialMode,
    (newMode) => {
        if (newMode && newMode !== selectedTrialMode.value) {
            selectedTrialMode.value = newMode;
        }
    },
    { immediate: true }
);

// const attributors = ref([
//     // 'class/background',
//     // 'class/color',
//     'style/size',
//     'style/align',
// ]);

// const toolbar = [
//     ['bold', 'italic', 'underline', 'strike'],
//     ['blockquote', 'code-block'],
//     [{ header: 1 }, { header: 2 }],
//     [{ list: 'ordered' }, { list: 'bullet' }],
//     [{ script: 'sub' }, { script: 'super' }],
//     [{ direction: 'rtl' }],
//     [{ header: [1, 2, 3, 4, 5, 6, false] }],
//     [{ color: [] }, { background: [] }],
//     [{ align: [] }],
//     ['image'],
//     ['clean'], // remove formatting button
// ];

const highlighter = (code) => {
    return highlight(code, languages.markup);
};

// Function to handle trial mode selection
const selectTrialMode = (mode) => {
    selectedTrialMode.value = mode;

    // Set the appropriate trial configuration based on selection
    if (mode === "descriptive") {
        form.config.practice_trial = false;
        form.config.main_trial = false;
    } else if (mode === "practice") {
        form.config.practice_trial = true;
        form.config.main_trial = false;
    } else if (mode === "main") {
        form.config.practice_trial = false;
        form.config.main_trial = true;
    }

    // Emit the trial mode change to parent component
    emit("trialModeChanged", mode);

    // Force re-initialization of editors after mode change
    nextTick(() => {
        setTimeout(() => {
            if (enableQuillEditor.value) {
                // Clean up existing editors first
                Object.keys(quillInstances.value).forEach((editorId) => {
                    if (quillInstances.value[editorId]) {
                        const quill = quillInstances.value[editorId];
                        const toolbar = quill.container.previousSibling;
                        if (
                            toolbar &&
                            toolbar.classList.contains("ql-toolbar")
                        ) {
                            toolbar.remove();
                        }
                        quillInstances.value[editorId] = null;
                    }
                });

                // Initialize appropriate editors based on mode
                if (mode === "descriptive") {
                    initializeQuill("title-quill", form.config.title);
                } else {
                    initializeQuill("header-quill", form.config.main_header);
                    initializeQuill("footer-quill", form.config.bottom_text);
                }
            }
        }, 200);
    });
};

watch(form, (newVal) => {
    if (newVal.config.random_prime === false) {
        form.config.random_prime_subsets = false;
    }
});

watch(
    () => form.config.main_header,
    (value) => {
        if (typeof value !== "string") return;
        const sanitized = sanitizeHtmlValue(value);
        if (sanitized !== value) {
            form.config.main_header = sanitized;
        }
    }
);

// Watch for main trial changes to automatically disable practice trial
watch(
    () => form.config.main_trial,
    (newVal) => {
        if (newVal === true) {
            form.config.practice_trial = false;
        }
    }
);

// Watch for practice trial changes to automatically disable main trial
watch(
    () => form.config.practice_trial,
    (newVal) => {
        if (newVal === true) {
            form.config.main_trial = false;
        }
    }
);

// watch(() => (form.config.main_header), (new_val) => {
//     nextTick(() => {
//         form.config.main_header = new_val.replace(/&nbsp;/g, ' ');
//     })

// });

const macroToBeCreatedOrUpdated = ref(false);
const selectedMacro = ref(false);

const macro = ref({
    placeholder: null,
    formula: "",
    type: "tabular",
});

const saveMacro = () => {
    if (selectedMacro.value === false) {
        form.config.macros.push(assign(macro.value));
    } else {
        form.config.macros[selectedMacro.value] = assign(macro.value);
    }
    macroToBeCreatedOrUpdated.value = false;
    selectedMacro.value = false;
};

const editMacro = (key) => {
    selectedMacro.value = key;
    macroToBeCreatedOrUpdated.value = form.config.macros[key];
    macro.value.placeholder = macroToBeCreatedOrUpdated.value.placeholder;
    macro.value.formula = macroToBeCreatedOrUpdated.value.formula;
    macro.value.type = macroToBeCreatedOrUpdated.value.type;
};

const deleteMacro = (key) => {
    form.config.macros.splice(key, 1);
};

// Handle display_by change event
const handleDisplayByChange = (value) => {
    // Ensure the value is a valid number, but allow values less than 1
    const numValue = parseInt(value);
    if (!isNaN(numValue)) {
        form.config.display_by = numValue;
    }
};

// Insert image by URL into Quill editor
const insertImageByUrl = (editorId) => {
    const quill = quillInstances.value[editorId];
    if (!quill) {
        alert("Quill editor is not ready. Please wait a moment and try again.");
        return;
    }
    activeQuillEditor.value = quill;
    showImageUrlModal.value = true;
};

// Helper to insert multiple images, one per line, removing selection
const insertImagesFromModal = () => {
    try {
        const quill = activeQuillEditor.value;
        if (!quill) {
            showImageUrlModal.value = false;
            imageUrlsText.value = "";
            return;
        }

        const rawLines = String(imageUrlsText.value || "")
            .split(/\r?\n/)
            .map((s) => s.trim())
            .filter(Boolean);
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
            let finalUrl = url;
            try {
                const u = new URL(url, window.location.origin);
                if (u.origin !== window.location.origin) {
                    finalUrl = `${BASE_PATH}/gallery-proxy?u=${encodeURIComponent(u.href)}`;
                }
            } catch (_) {}

            quill.insertEmbed(insertIndex, "image", finalUrl, "user");
            insertIndex += 1;
            // Add a space after each image to keep them inline
            quill.insertText(insertIndex, " ", "user");
            insertIndex += 1;
        });

        quill.setSelection(insertIndex, 0, "user");
        // Update underlying config for whichever editor is active
        const editorId = activeEditorId.value;
        if (editorId && quillInstances.value[editorId] === quill) {
            updateConfigFromQuill(editorId, quill);
        }
    } catch (_) {}
    showImageUrlModal.value = false;
    imageUrlsText.value = "";
};
</script>

<template>
    <!-- Main Content (only shown after mode selection) -->
    <div
        v-if="selectedTrialMode"
        class="grid grid-cols-8 gap-3 pt-4 overflow-x-hidden overflow-y-auto c"
    >
        <div class="col-span-8 flex justify-between items-center">
            <!-- <CustomSwitch v-model:checked="enableCKEditor"> Editor </CustomSwitch> -->
            <div class="flex items-center gap-2">
                <jet-label>
                    <div class="flex items-center ">
                        <Toggle v-model="enableQuillEditor" />
                        <div class="ml-2">Editor</div>
                    </div>
                </jet-label>

                <jet-label v-if="selectedTrialMode === 'descriptive'">
                    <div class="flex items-center gap-2 relative group">
                        <Toggle v-model="form.config.dummy_trial" />
                        <div class="">Dummy round</div>
                        <div
                        class="absolute bottom-[-10px] left-[150px] mb-0 px-1.5 py-2 rounded text-xs text-white bg-[#15919a] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                        role="tooltip"
                        >
                        Toggle this switch ON if the instruction you are currently writing is intended for a Practice Round.<br/> Instructions for main round should typically have this switch toggled OFF.
                        </div>
                    </div>
                </jet-label>
            </div>
            <!-- Current Trial Mode Indicator and Change Button -->
            <!-- now hidden by default -->
            <div
                class="flex items-center gap-3 absolute -mt-32 right-10 hidden"
            >
                <!-- Current Trial Mode Badge -->
                <div
                    class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium"
                    :class="{
                        'bg-green-100 text-green-800':
                            selectedTrialMode === 'descriptive',
                        'bg-red-100 text-red-800':
                            selectedTrialMode === 'practice',
                        'bg-blue-100 text-blue-800':
                            selectedTrialMode === 'main',
                    }"
                >
                    <svg
                        v-if="selectedTrialMode === 'descriptive'"
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        ></path>
                    </svg>
                    <svg
                        v-else-if="selectedTrialMode === 'practice'"
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                        ></path>
                    </svg>
                    <svg
                        v-else-if="selectedTrialMode === 'main'"
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2zm0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        ></path>
                    </svg>
                    <span class="capitalize"
                        >{{ selectedTrialMode }} Trial</span
                    >
                </div>

                <!-- Change Trial Mode Button -->
                <JetButton
                    type="button"
                    variant="default"
                    size="sm"
                    @click="showTrialModeModal = true"
                    class="flex items-center gap-1"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                        ></path>
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        ></path>
                    </svg>
                    Change
                </JetButton>
            </div>
        </div>

        <!-- Trial Title - Only shown for descriptive mode -->
        <div v-if="selectedTrialMode === 'descriptive'" class="col-span-8">
            <JetLabel value="Round Title" />
            <div v-if="enableQuillEditor">
                <QuillContainer
                    editorId="title-quill"
                    :height="'450px'"
                    aria-label="Trial Title Editor"
                    title="Trial Title Editor"
                />
            </div>

            <PrismEditor
                v-else
                class="my-editor editor-height"
                v-model="form.config.title"
                :highlight="highlighter"
                line-numbers
            />
        </div>

        <!-- Main Header and Bottom Text - Only shown for practice/main trials -->
        <template v-if="selectedTrialMode !== 'descriptive'">
            <div class="col-span-8">
                <div class="col-span-4 mt-2 mb-2">
                    <JetLabel value="Main Header" />
                    <div v-if="enableQuillEditor">
                        <QuillContainer editorId="header-quill" />
                    </div>
                    <prism-editor
                        v-else
                        class="my-editor editor-height"
                        v-model="form.config.main_header"
                        :highlight="highlighter"
                        line-numbers
                    ></prism-editor>
                </div>

                <div class="col-span-4 mt-2 mb-2">
                    <JetLabel value="Bottom text" />
                    <div v-if="enableQuillEditor">
                        <QuillContainer
                            editorId="footer-quill"
                            :height="'200px'"
                        />
                    </div>
                    <PrismEditor
                        v-else
                        class="my-editor editor-height"
                        v-model="form.config.bottom_text"
                        :highlight="highlighter"
                        line-numbers
                    />
                </div>
            </div>

            <!-- Toggle Buttons and Configuration - Only shown for practice/main trials -->
            <!-- <div class="col-span-8">
        <jet-label class="">
          <div class="flex items-center">
            <Toggle v-model="form.config.practice_trial" />
            <div class="ml-2">Practice trial</div>
          </div>
        </jet-label>

        <jet-label class="mt-3">
          <div class="flex items-center">
            <Toggle v-model="form.config.main_trial" />
            <div class="ml-2">Main trial</div>
          </div>
        </jet-label>

        <jet-label class="mt-3">
          <div class="flex items-center">
            <Toggle v-model="form.config.random" />
            <div class="ml-2">Randomize targets</div>
          </div>
        </jet-label>

        <jet-label class="mt-3">
          <div class="flex items-center">
            <Toggle v-model="form.config.random_prime" />
            <div class="ml-2">Randomize primes</div>
          </div>
        </jet-label>

        <jet-label class="mt-3">
          <div class="flex items-center">
            <Toggle v-model="form.config.random_prime_subsets" :disabled="!form.config.random_prime" />
            <div class="ml-2">Randomize prime subsets</div>
          </div>
        </jet-label>

        <jet-label class="mt-3">
          <div class="flex items-center">
            <Toggle v-model="form.config.prime_as_next" />
            <div class="ml-2">Prime as next button</div>
          </div>
        </jet-label>

        <jet-label class="mt-3">
          <div class="flex items-center">
            <Toggle v-model="form.config.single_select" />
            <div class="ml-2">Single select</div>
          </div>
        </jet-label>

        <jet-label class="mt-3">
          <div class="flex items-center">
            <Toggle v-model="form.config.auto_next" />
            <div class="ml-2">Auto next trial</div>
          </div>
        </jet-label>

        <div class="flex flex-row gap-4 items-end mt-6">
          <div class="w-1/3">
            <CustomInput
              label="Empty selection text"
              v-model="form.config.empty_selection"
            />
          </div>

          <div class="w-1/3">
            <CustomInput
              type="number"
              label="Selection limit (0 = can select all)"
              v-model="form.config.limit"
            />
          </div>
          <div class="w-1/3">
            <CustomInput
              type="number"
              label="Display targets by"
              v-model="form.config.display_by"
              @change="handleDisplayByChange"
            />
          </div>
        </div>
      </div>

      <div class="hidden col-span-8">
        <div class="block mt-5">Responses settings</div>
        <div class="w-1/4">
          <jet-label class="mt-3">
            <div class="flex items-center">
              <jet-checkbox v-model:checked="form.config.download_as_values" />
              <div class="ml-2">Download responses as values</div>
            </div>
          </jet-label>
        </div>
      </div> -->

            <!-- Response Recoding - Only shown for practice/main trials -->
            <!-- <div class="col-span-8">
        <div class="block mt-5">Response recoding</div>
        <div class="mb-2">
          <JetButton
            type="button"
            variant="rose"
            size="xs"
            @click.prevent="macroToBeCreatedOrUpdated = true"
          >
            Add macro code
          </JetButton>
        </div>
        <div>
          <div
            v-for="(mc, k) in form.config.macros"
            :key="k"
            class="flex items-center p-2 my-1 text-xs border rounded-lg bg-slate-100"
          >
            <div class="w-4/12">
              {{ mc.placeholder }}
            </div>
            <div class="w-5/12 font-mono">
              {{ mc.formula }}
            </div>
            <div class="w-1/12">
              <div
                class="flex items-center p-1 text-gray-500 bg-white border rounded-full"
              >
                <div class="mx-auto">
                  <span
                    v-if="mc.type == 'tabular'"
                    class="text-xs font-semibold"
                  >
                    Tabular
                  </span>
                  <span v-else class="text-xs font-semibold"> Respondent </span>
                </div>
              </div>
            </div>
            <div class="flex items-center justify-end w-2/12 gap-1">
              <JetButton
                variant="orange"
                size="xs"
                @click.prevent="editMacro(k)"
              >
                Update
              </JetButton>
              <JetButton
                variant="orange"
                size="xs"
                @click.prevent="deleteMacro(k)"
              >
                Delete
              </JetButton>
            </div>
          </div>
        </div>
      </div> -->

            <!-- Skip Logic - Only shown for practice/main trials -->
            <!-- <div class="col-span-8 mt-5">
        Skip logic
        <div class="mb-2">
          <JetButton
            type="button"
            variant="rose"
            size="xs"
            @click.prevent="addSegment"
          >
            <span v-if="form.config.conditions.length > 0">Add Segment</span
            ><span v-else>Add Condition</span>
          </JetButton>
        </div>
        <div
          v-for="(condition, index) in form.config.conditions"
          :key="index"
          class="relative"
        >
          <TestCriteria :deep="0" :condition="condition" :indexes="[index]" />
        </div>
        <div class="mt-3">
          <code class="text-xs bg-gray-200 text-black-500">{{
            $testState.criteriaToString(form.config.conditions)
          }}</code>
        </div>
      </div> -->
        </template>
    </div>

    <!-- Macro Modal -->
    <JetDialogModal
        :show="macroToBeCreatedOrUpdated"
        @close="macroToBeCreatedOrUpdated = false"
    >
        <template #title> Add macro code </template>

        <template #content>
            <div class="flex gap-2">
                <div class="w-full p-2">
                    <CustomInput
                        type="text"
                        label="Placeholder"
                        v-model="macro.placeholder"
                    />
                </div>
            </div>
            <div class="flex gap-2">
                <div class="w-full p-2">
                    <JetLabel value="Applied to" />
                    <select
                        v-model="macro.type"
                        class="block w-full p-2 mt-1 text-xs border-gray-300 rounded-lg form-select dark:bg-gray-900 dark:border-gray-500"
                    >
                        <option value="tabular">Tabular</option>
                        <option value="respondent">Respondent Level</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2">
                <div class="w-full p-2">
                    <PrismEditor
                        class="my-editor editor-height"
                        v-model="macro.formula"
                        :highlight="highlighter"
                        line-numbers
                    />
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">
                            Use the following syntax:
                            <span
                                class="p-0.5 px-2 rounded-lg bg-teal-600 font-mono font-semibold text-white"
                                >t1</span
                            >
                            = Target 1,
                            <span
                                class="p-0.5 px-2 rounded-lg bg-teal-600 font-mono font-semibold text-white"
                                >t2</span
                            >
                            for Target 2 and so on
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <JetButton
                variant="default"
                class="uppercase"
                @click="macroToBeCreatedOrUpdated = false"
                size="sm"
            >
                nevermind
            </JetButton>

            <jet-button
                @click.prevent="saveMacro"
                size="sm"
                type="button"
                class="ml-2 uppercase"
                variant="primary"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Save changes
            </jet-button>
        </template>
    </JetDialogModal>

    <!-- Replace Variables Modal -->
    <JetDialogModal
        :show="showReplaceModal"
        @close="closeReplaceModal"
        max-width="4xl"
    >
        <!-- <template #title> Variables </template> -->
        <template #content>
            <IndexVariables :project="$page.props.project" :survey="survey" :enable-apply="true" />
            <!-- <div>
                <div
                    v-if="availableVariables.length > 0"
                    class="flex flex-wrap gap-2"
                >
                    <button
                        v-for="(variable, index) in availableVariables"
                        :key="variable.id || variable.name || index"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg hover:bg-green-50 hover:border-green-400 transition-all duration-200 bg-white shadow-sm text-xs"
                        @click="insertVariable(variable.name || variable)"
                    >
                        {{ variable.name || variable }}
                    </button>
                </div>
                <div v-else class="text-gray-500 text-sm">
                    No variables available
                </div>
            </div> -->
        </template>
        <template #footer>
            <JetButton
                variant="default"
                class="uppercase"
                size="sm"
                @click="closeReplaceModal"
            >
                Close
            </JetButton>
        </template>
    </JetDialogModal>

    <!-- Image URLs Modal -->
    <JetDialogModal
        :show="showImageUrlModal"
        @close="() => { showImageUrlModal = false; }"
        maxWidth="lg"
    >
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
                <p class="text-xs text-gray-500">
                    Each non-empty line will be inserted as an image on its own line.
                </p>
            </div>
        </template>
        <template #footer>
            <JetButton
                variant="default"
                class="mr-2"
                @click="() => { showImageUrlModal = false; }"
            >
                CANCEL
            </JetButton>
            <JetButton variant="primary" @click="insertImagesFromModal">
                INSERT
            </JetButton>
        </template>
    </JetDialogModal>
</template>

<style scoped="true">
.c {
    max-height: calc(100vh - 214px);
}

:deep(.ql-variable) {
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: 600;
    background: #15919a;
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
    border: 1px solid #0c7c84;
    transition: all 0.2s ease;
}

:deep(.ql-variable:hover) {
    background: #0c7c84;
    box-shadow: 3px 3px 6px rgba(0, 0, 0, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.4);
    transform: translateY(-1px);
    cursor: not-allowed !important;
}

/* QuillEditor Styles */
:deep(.ql-editor) {
    min-height: 200px;
    font-size: 14px;
    line-height: 1.6;
}

:deep(.ql-toolbar) {
    background-color: #f8fafc;
    border-radius: 8px 8px 0 0;
    padding: 8px;
    border: 1px solid #e5e7eb;
}

/* Custom Variables button styling inside Quill toolbar */
::deep(.ql-toolbar .ql-open-variables) {
    background-color: rgba(21, 145, 154, 0.19);
    color: #15919a;
    padding: 0px 60px 0px 10px;
    border-radius: 9999px;
    font-size: 12px;
    line-height: 1;
    border: 1px solid rgba(21, 145, 154, 0.25);
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
    gap: 6px;
}

::deep(.ql-toolbar .ql-open-variables:hover) {
    background-color: rgba(21, 145, 154, 0.56);
    color: #ffffff;
    border-color: #15919a;
}

::deep(.ql-toolbar .ql-open-variables:focus) {
    outline: none;
    box-shadow: 0 0 0 2px rgba(21, 145, 154, 0.25);
}

:deep(.ql-container) {
    border-radius: 0 0 8px 8px;
    border: 1px solid #e5e7eb;
    border-top: none;
}

:deep(.ql-editor.ql-blank::before) {
    color: #9ca3af;
    font-style: italic;
}

/* Image insertion button styling */
:deep(.ql-toolbar .ql-insert-image) {
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 4px 8px;
    margin-right: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

:deep(.ql-toolbar .ql-insert-image:hover) {
    background: #f8f9fa;
    border-color: #15919a;
    color: #15919a;
}

:deep(.ql-toolbar .ql-insert-image:focus) {
    outline: none;
    box-shadow: 0 0 0 2px rgba(21, 145, 154, 0.25);
}

/* Make images inline with text */
:deep(.ql-editor img) {
    display: inline-block;
    max-width: 100px;
    max-height: 100px;
    margin: 0 4px;
    vertical-align: middle;
    border-radius: 4px;
}
</style>
