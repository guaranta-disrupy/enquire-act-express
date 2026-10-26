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
import Toggle from "@/Jetstream/Toggle.vue";

import CustomSwitch from "@/Components/Switch.vue";
import JetLabel from "@/Jetstream/Label.vue";
import JetCheckbox from "@/Jetstream/Checkbox.vue";
import CustomInput from "@/Components/Input.vue";

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
import { useTestsStore } from "@/stores/tests";
import { useDataStore } from "@/stores/data";
import JetButton from "@/Componentsv2/Button.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetInput from "@/Jetstream/Input.vue";
import Helpers from "@/Mixins/Helpers";
import TestCriteria from "./TestCriteria.vue";
import QuillContainer from "@/Components/QuillEditor.vue";
import IndexVariables from "@/Pages/Variables2/Index.vue";
import {
    addVariablesToolbarButton,
    addImageInsertionToolbarButton,
    PLACEHOLDER_TOKEN,
} from "@/Components/QuillEditor.vue";
const dataStore = useTestsStore();

const props = defineProps(["tests", "variables", "project"]);
console.log("props", props);
const showInstructions = reactive({
    title: false,
    main_header: false,
    bottom_text: false,
});
const emit = defineEmits(["update:tests"]);

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
    let nextTests;
    try {
        nextTests =
            typeof structuredClone === "function"
                ? structuredClone(props.tests)
                : JSON.parse(JSON.stringify(props.tests));
    } catch (e) {
        nextTests = JSON.parse(JSON.stringify(props.tests));
    }
    // Debug: log new instructions before emit
    try {
        console.log("Instruction update (debounced emit):", {
            title: props.tests?.config?.title,
            main_header: props.tests?.config?.main_header,
            bottom_text: props.tests?.config?.bottom_text,
        });
    } catch (e) {}
    emit("update:tests", nextTests);

    // Use the store's action to update the config, which will set the dirty flag
    if (nextTests.config && nextTests.config.type !== "descriptive") {
        dataStore.updateConfig(nextTests.config);
    }
}, 500); // Wait for 500ms of inactivity before firing

const emitUpdate = () => {
    // This function can be used for immediate, non-debounced updates if needed elsewhere.
    // For now, we'll route everything through the debounced version.
    dataStore.isDirty = true;
    debouncedEmitUpdate();
};

// VariableBlot is registered globally in QuillEditor component

const { $testState, assign } = Helpers();

const enableQuillEditor = inject("ck");
enableQuillEditor.value = true;
const quillEditor = ref(null);
const quillInstances = ref({});
const lastSelectionByEditor = ref({});
// const dataStore = useDataStore();
const activeEditorId = ref(null);
// Image URL modal state
const showImageUrlModal = ref(false);
const imageUrlsText = ref("");
const activeQuillEditor = ref(null);

const form = props.tests;

// Resolve variables from page props (preferred) or passed props
const page = usePage();

const availableVariables = computed(() => {
    // Get variables from page props or passed props
    const pageVars = page?.props?.variables;
    const projectVars = page?.props?.project?.variables;
    const propVars = props.variables;

    const resolved = Array.isArray(pageVars)
        ? pageVars
        : Array.isArray(projectVars)
        ? projectVars
        : Array.isArray(propVars)
        ? propVars
        : [];

    return resolved;
});

console.log("availableVariables", availableVariables.value);

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

// Toolbar button creation is imported from QuillEditor component

// (Removed old Testing button; unified into Variables button)

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

const updateConfigFromQuill = (editorId, quill) => {
    const key = getConfigKeyForEditor(editorId);
    if (!key) return;
    const newValue = readQuillContent(quill);
    if (props.tests.config[key] !== newValue) {
        props.tests.config[key] = newValue;
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

            const quill = new Quill(editorElement, {
                theme: "snow",
                modules: {
                    toolbar: quillToolbar,
                },
            });

            quillInstances.value[editorId] = quill;
            // Global button initialization for all editors (Variables and Image buttons)
            addVariablesToolbarButton(quill, editorId);
            addImageInsertionToolbarButton(quill, editorId, insertImageByUrl);

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

            const textChangeHandler = () =>
                updateConfigFromQuill(editorId, quill);
            // Set initial content and parse variables
            if (content) {
                // Parse {{variable_name}} patterns to styled elements
                const parsedContent = parseVariablesToElements(content);
                quill.root.innerHTML = parsedContent;
            }

            // Watch for content changes (simplified)
            // quill.on("text-change", () => updateConfigFromQuill(editorId, quill));
            setTimeout(() => {
                quill.on("text-change", textChangeHandler);
            }, 500);

            console.log(`Quill initialized for ${editorId}`);
        } else {
            console.log(`Element ${editorId} not found`);
        }
    });
};

// Legacy modal functions removed (unified Variables flow)

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

const onVariableSelect = () => {};

// Accept insert requests from Variables2/Index.vue "Apply Variable" action
function onVariableInsertRequest(e) {
    try {
        const raw = e?.detail?.name;
        if (!raw) return;
        const editorId =
            activeEditorId.value ||
            (props.tests?.config?.type === "descriptive"
                ? "title-quill"
                : "header-quill");
        // Prefer Quill editor if enabled and instance exists
        const quill = quillInstances.value[editorId];
        if (enableQuillEditor.value && quill) {
            insertVariable(raw);
        } else {
            // Fallback to appending into underlying config fields so plain inputs get the token
            const token = `{{${normalizeVariableName(raw)}}}`;
            if (props.tests?.config?.type === "descriptive") {
                const base = props.tests.config.title || "";
                props.tests.config.title = base.includes(token)
                    ? base
                    : (base ? base + " " : "") + token;
            } else {
                const base = props.tests.config.main_header || "";
                props.tests.config.main_header = base.includes(token)
                    ? base
                    : (base ? base + " " : "") + token;
            }
            emitUpdate();
            showReplaceModal.value = false;
        }
    } catch (_) {}
}

if (typeof window !== "undefined") {
    window.addEventListener("variable-insert-request", onVariableInsertRequest);
}

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
    props.tests.config.title = appendToken(props.tests.config.title);
    props.tests.config.main_header = appendToken(
        props.tests.config.main_header
    );
    emitUpdate();

    // Don't close modal automatically - let user insert multiple variables
};

onBeforeMount(() => {
    $testState.criteria = form;
    // $testState.criteria = {
    //     config: form.config
    // }
    //console.log($state.criteria)
});

onMounted(() => {
    window.addEventListener("message", (event) => {
        // [Verified] Only handle messages from the same origin for security
        // if (event.origin !== window.location.origin) {
        //     return;
        // }
        // if (event.data && event.data.type === "UPDATE_TEST") {
        //     // [Verified] Emit a custom event to notify parent or other components
        //     // This assumes you have an emit function available in setup()
        //     // If not, replace 'emit' with the correct event bus or parent communication
        //     console.log("UPDATE_TEST", event.data);
        //     enableQuillEditor.value = false;
        //     setTimeout(() => {
        //         enableQuillEditor.value = true;
        //     }, 50);
        // }
    });

    // Ensure cross-component variable apply works if listener attached after mount
    try {
        window.addEventListener(
            "variable-insert-request",
            onVariableInsertRequest
        );
    } catch (_) {}

    // Wait for DOM to be fully rendered before initializing Quill
    setTimeout(() => {
        if (enableQuillEditor.value) {
            initializeQuill("title-quill", props.tests.config.title);
            initializeQuill("header-quill", props.tests.config.main_header);
            initializeQuill("footer-quill", props.tests.config.bottom_text);
        }
    }, 200);
});

watch(enableQuillEditor, (newValue) => {
    if (newValue) {
        setTimeout(() => {
            initializeQuill("title-quill", props.tests.config.title);
            initializeQuill("header-quill", props.tests.config.main_header);
            initializeQuill("footer-quill", props.tests.config.bottom_text);
        }, 200);
    } else {
        Object.keys(quillInstances.value).forEach((editorId) => {
            if (quillInstances.value[editorId]) {
                const quill = quillInstances.value[editorId];
                const cleanContent = parseElementsToVariables(
                    quill.root.innerHTML
                );

                if (editorId === "title-quill") {
                    props.tests.config.title = cleanContent;
                } else if (editorId === "header-quill") {
                    props.tests.config.main_header = cleanContent;
                } else if (editorId === "footer-quill") {
                    props.tests.config.bottom_text = cleanContent;
                }
                emitUpdate();

                quillInstances.value[editorId] = null;
            }
        });
    }
});

// Watch for changes in the test data and re-initialize Quill editors
watch(
    () => props.tests?.id,
    (newId, oldId) => {
        if (newId !== oldId && newId !== undefined) {
            if (enableQuillEditor.value) {
                nextTick(() => {
                    initializeQuill("title-quill", props.tests.config.title);
                    initializeQuill(
                        "header-quill",
                        props.tests.config.main_header
                    );
                    initializeQuill(
                        "footer-quill",
                        props.tests.config.bottom_text
                    );
                });
            }
        }
    }
);

provide("testState", $testState);

const highlighter = (code) => {
    return highlight(code, languages.markup);
};

watch(
    form,
    (newVal) => {
        console.log("form instructions", newVal);
        if (newVal.config.random_prime === false) {
            props.tests.config.random_prime_subsets = false;
        }
    },
    { deep: true, immediate: true }
);

watch(
    () => props.tests.config?.dummy_trial,
    (newVal, oldVal) => {
        if (newVal !== oldVal) {
            dataStore.isDirty = true;
        }
    },
    { deep: true }
);

const macroToBeCreatedOrUpdated = ref(false);
const selectedMacro = ref(false);
const roundTitleModal = ref(false);

const macro = ref({
    placeholder: null,
    formula: "",
    type: "tabular",
});

const saveMacro = () => {
    if (selectedMacro.value === false) {
        props.tests.config.macros.push(assign(macro.value));
    } else {
        props.tests.config.macros[selectedMacro.value] = assign(macro.value);
    }
    emitUpdate();
    macroToBeCreatedOrUpdated.value = false;
    selectedMacro.value = false;
};

const editMacro = (key) => {
    selectedMacro.value = key;
    macroToBeCreatedOrUpdated.value = props.tests.config.macros[key];
    macro.value.placeholder = macroToBeCreatedOrUpdated.value.placeholder;
    macro.value.formula = macroToBeCreatedOrUpdated.value.formula;
    macro.value.type = macroToBeCreatedOrUpdated.value.type;
};

const deleteMacro = (key) => {
    props.tests.config.macros.splice(key, 1);
    emitUpdate();
};

const openRoundTitleModal = () => {
    roundTitleModal.value = true;
    nextTick(() => {
        initializeQuill("modal-quill", props.tests.config.title);
    });
};

const handleContentUpdate = (target, newContent) => {
    if (props.tests.config[target] !== newContent) {
        props.tests.config[target] = newContent;
        debouncedEmitUpdate();
    }
};

// Convert HTML to editor-friendly string without <p> tags, preserving other tags
const htmlToEditorNoP = (html) => {
    if (!html) return "";
    try {
        let result = String(html);
        // Normalize different paragraph patterns
        result = result
            .replace(/<p>\s*<br\s*\/?>(\s*)<\/p>/gi, "\n") // empty paragraphs -> newline
            .replace(/<br\s*\/?>(?=<\/p>)/gi, "") // trailing br before </p>
            .replace(/<\/p>\s*<p>/gi, "\n") // paragraph separator -> newline
            .replace(/<\/?p>/gi, ""); // strip remaining <p> tags
        // Convert <br> to newline for editor display
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

// Insert image by URL into Quill editor
const insertImageByUrl = (editorId) => {
    const quill = quillInstances.value[editorId];
    if (!quill) {
        alert("Quill editor is not ready. Please wait a moment and try again.");
        return;
    }

    // Set the active editor and open modal
    activeQuillEditor.value = quill;
    showImageUrlModal.value = true;
    imageUrlsText.value = "";
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

        // Get current selection or default to end
        let range = quill.getSelection(true);
        if (!range || range.index === null) {
            const length = quill.getLength();
            range = { index: length - 1, length: 0 };
        }

        // Insert each image
        let currentIndex = range.index;
        for (const url of rawLines) {
            let finalUrl = url;
            try {
                const u = new URL(url, window.location.origin);
                if (u.origin !== window.location.origin) {
                    finalUrl = `${BASE_PATH}/gallery-proxy?u=${encodeURIComponent(u.href)}`;
                }
            } catch (e) {
                // If URL parsing fails, just use the original URL
            }

            try {
                quill.insertEmbed(currentIndex, "image", finalUrl, "user");
                currentIndex += 1; // Move past the inserted image
                // Add a space after each image to keep them inline
                quill.insertText(currentIndex, " ", "user");
                currentIndex += 1;
            } catch (err) {
                console.error("Failed to insert image:", url, err);
            }
        }

        // Move cursor after the last image
        quill.setSelection(currentIndex, 0, "user");
        // Update the form data
        const editorId = Object.keys(quillInstances.value).find(
            (id) => quillInstances.value[id] === quill
        );
        if (editorId) {
            updateConfigFromQuill(editorId, quill);
        }

        // Close modal and reset
        showImageUrlModal.value = false;
        imageUrlsText.value = "";
        activeQuillEditor.value = null;
    } catch (error) {
        console.error("Error inserting images from modal:", error);
        showImageUrlModal.value = false;
        imageUrlsText.value = "";
        activeQuillEditor.value = null;
    }
};

// Emit updates when editing via PrismEditor (non-Quill path)
// watch(() => props.tests.config.title, debouncedEmitUpdate);
// watch(() => props.tests.config.main_header, debouncedEmitUpdate);
// watch(() => props.tests.config.bottom_text, debouncedEmitUpdate);
// watch(() => props.tests.config.empty_selection, debouncedEmitUpdate);
</script>

<template>
    <div
        class="bg-[#15919A] text-white shadow p-4 mb-2 w-[100vh] text-center uppercase"
    >
        Instruction
    </div>
    <div
        class="flex flex-col gap-2 overflow-y-auto overflow-x-hidden c px-4 w-[100vh]"
    >
        <!-- <CustomSwitch v-model:checked="enableQuillEditor"> Editor </CustomSwitch> -->
        <!-- Trial Title -->
        <!-- <div class="col-span-4" v-if="!form.config.main_header"> -->

        <div
            class="col-span-4"
            v-if="props.tests.config.type === 'descriptive'"
        >
            <div class="flex flex-row gap-2">
                <div class="flex items-center gap-0 py-2 text-sm">
                    <Toggle v-model="enableQuillEditor" />
                    <div class="ml-2">Editor</div>
                </div>

                <div
                    class="flex items-center gap-0 py-2 text-sm relative group"
                >
                    <Toggle v-model="props.tests.config.dummy_trial" />
                    <div class="ml-2">Dummy round</div>
                    <div
                        class="absolute bottom-[-10px] left-[150px] mb-0 px-1.5 py-2 rounded text-xs text-white bg-[#15919a] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                        role="tooltip"
                    >
                        Toggle this switch ON if the instruction you are
                        currently writing is intended for a Practice Round.<br />
                        Instructions for main round should typically have this
                        switch toggled OFF.
                    </div>
                </div>
            </div>
            <JetLabel value="Header" class="mb-1" />
            <div v-if="enableQuillEditor">
                <QuillContainer
                    editorId="title-quill"
                    :height="'500px'"
                    aria-label="Round Title Editor"
                    title="Round Title Editor"
                />
            </div>

            <PrismEditor
                v-else
                class="my-editor editor-height"
                :modelValue="htmlToEditorNoP(props.tests.config.title)"
                @update:modelValue="
                    handleContentUpdate('title', editorToHtmlNoP($event))
                "
                :highlight="highlighter"
                line-numbers
            />
        </div>
        <!-- </div> -->

        <!-- Main Header and Bottom Text -->
        <!-- <template v-if="!form.config.title"> -->

        <div v-if="props.tests.config.type === 'trial'">
            <div class="col-span-4">
                <div class="flex items-center gap-0 py-2 text-sm">
                    <Toggle v-model="enableQuillEditor" />
                    <div class="ml-2">Editor</div>
                </div>

                <div class="flex items-center justify-between">
                    <JetLabel value="Header" class="mb-1" />
                    <!-- Variable Button for Title - Always visible -->
                    <!-- <div class="mb-2">
                        <button
                            @click="openTitleModal"
                            class="px-3 py-1 bg-[#15919A30] hover:bg-[#15919A90] hover:text-white text-[#15919A] rounded-full text-sm transition-colors"
                        >
                            Variables
                        </button>
                    </div> -->
                </div>
                <!-- Variable Button for Header - Always visible -->

                <div v-if="enableQuillEditor">
                    <!-- Quill Editor -->
                    <QuillContainer editorId="header-quill" :height="'300px'" />

                    <!-- Modal -->
                    <div
                        v-if="showModal"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40"
                    >
                        <div
                            class="p-6 bg-white rounded shadow-md w-[500px] max-h-[600px] overflow-y-auto"
                        >
                            <h2 class="mb-4 font-bold">Select Variable</h2>

                            <!-- Variable Cards -->
                            <div
                                v-if="availableVariables.length > 0"
                                class="flex flex-wrap gap-2 mb-4"
                            >
                                <div
                                    v-for="(
                                        variable, index
                                    ) in availableVariables"
                                    :key="variable.id || variable.name || index"
                                    class="inline-flex items-center px-3 py-2 transition-all duration-200 bg-white border border-gray-300 rounded-lg shadow-sm cursor-pointer hover:bg-green-50 hover:border-green-400"
                                >
                                    <span
                                        class="text-xs font-medium text-gray-600 whitespace-nowrap"
                                        >{{ variable.name || variable }}
                                    </span>

                                    <div
                                        class="flex-shrink-0 w-2 h-2 ml-2 bg-green-500 rounded-full"
                                    ></div>
                                </div>
                            </div>

                            <!-- No Variables Message -->
                            <div v-else class="py-8 text-center text-gray-500">
                                <p>No variables available</p>
                            </div>

                            <div class="flex justify-end mt-4">
                                <button
                                    @click="closeModal"
                                    class="px-4 py-2 transition-colors bg-gray-200 rounded hover:bg-gray-300"
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <prism-editor
                    v-else
                    class="my-editor editor-height"
                    :modelValue="
                        htmlToEditorNoP(props.tests.config.main_header)
                    "
                    @update:modelValue="
                        handleContentUpdate(
                            'main_header',
                            editorToHtmlNoP($event)
                        )
                    "
                    :highlight="highlighter"
                    line-numbers
                ></prism-editor>
            </div>

            <div class="col-span-4 mt-4">
                <JetLabel value="Bottom text" class="mb-1" />
                <div v-if="enableQuillEditor">
                    <QuillContainer editorId="footer-quill" :height="'200px'" />
                </div>
                <PrismEditor
                    v-else
                    class="my-editor editor-height"
                    :modelValue="
                        htmlToEditorNoP(props.tests.config.bottom_text)
                    "
                    @update:modelValue="
                        handleContentUpdate(
                            'bottom_text',
                            editorToHtmlNoP($event)
                        )
                    "
                    :highlight="highlighter"
                    line-numbers
                />
            </div>

            <!-- <div class="col-span-8">
                <div class="w-1/2 mt-6">
                    <CustomInput
                        label="Empty selection text"
                        :modelValue="props.tests.config.empty_selection"
                        @update:modelValue="
                            handleContentUpdate('empty_selection', $event)
                        "
                    />
                </div>
            </div> -->
        </div>

        <div class="hidden col-span-8">
            <div class="block mt-5">Responses settings</div>
            <div class="w-1/4">
                <jet-label class="mt-3">
                    <div class="flex items-center">
                        <jet-checkbox
                            v-model:checked="
                                props.tests.config.download_as_values
                            "
                        />

                        <div class="ml-2">Download responses as values</div>
                    </div>
                </jet-label>
            </div>
        </div>
    </div>

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

    <!-- Round Title Modal -->
    <JetDialogModal
        :show="roundTitleModal"
        @close="roundTitleModal = false"
        maxWidth="4xl"
    >
        <template #title> Edit Round Title </template>
        <template #content>
            <div class="p-4">
                <JetLabel value="Round Title" class="block mb-2" />
                <QuillContainer editorId="modal-quill" :height="'200px'" />
            </div>
        </template>
        <template #footer>
            <JetButton
                variant="default"
                @click="roundTitleModal = false"
                class="uppercase"
                size="sm"
            >
                Cancel
            </JetButton>
            <JetButton
                @click="roundTitleModal = false"
                variant="primary"
                class="ml-2 uppercase"
                size="sm"
            >
                Save
            </JetButton>
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
            <IndexVariables
                :project="props.project"
                :survey="$page.props.survey || props.tests"
                :enable-apply="true"
            />
            <!-- <div class="mt-4">
                <div v-if="availableVariables.length > 0" class="flex flex-wrap gap-2">
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

    <!-- Title Modal -->
    <div
        v-if="showTitleModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40"
    >
        <div
            class="p-6 bg-white rounded shadow-md w-[500px] max-h-[600px] overflow-y-auto"
        >
            <h2 class="mb-4 font-bold">Select Variable for Title</h2>

            <!-- Variable Cards -->
            <div
                v-if="availableVariables.length > 0"
                class="flex flex-wrap gap-2 mb-4"
            >
                <div
                    v-for="(variable, index) in availableVariables"
                    :key="variable.id || variable.name || index"
                    @click="
                        insertVariableCard(
                            variable.name || variable,
                            'title-quill'
                        );
                        closeTitleModal();
                    "
                    class="inline-flex items-center px-3 py-2 transition-all duration-200 bg-white border border-gray-300 rounded-lg shadow-sm cursor-pointer hover:bg-green-50 hover:border-green-400"
                >
                    <span
                        class="text-xs font-medium text-gray-600 whitespace-nowrap"
                        >{{ variable.name || variable }}</span
                    >
                    <div
                        class="flex-shrink-0 w-2 h-2 ml-2 bg-green-500 rounded-full"
                    ></div>
                </div>
            </div>

            <!-- No Variables Message -->
            <div v-else class="py-8 text-center text-gray-500">
                <p>No variables available</p>
            </div>

            <div class="flex justify-end mt-4">
                <button
                    @click="closeTitleModal"
                    class="px-4 py-2 transition-colors bg-gray-200 rounded hover:bg-gray-300"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

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
                    Enter one image URL per line. Each URL will be inserted as
                    an image in the editor.
                </p>
            </div>
        </template>
        <template #footer>
            <JetButton
                variant="default"
                @click="
                    () => {
                        showImageUrlModal = false;
                    }
                "
                class="uppercase"
                size="sm"
            >
                CANCEL
            </JetButton>
            <JetButton
                @click="insertImagesFromModal"
                variant="primary"
                class="ml-2 uppercase"
                size="sm"
            >
                INSERT IMAGES
            </JetButton>
        </template>
    </JetDialogModal>
</template>

<style scoped="true">
:deep(img) {
    width: 22vmin !important;
}

.c {
    max-height: calc(100vh - 140px);
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

/* Removed separate Testing button; Variables button handles behavior */
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

/* Variables table styling to match Variables2/Index.vue */
.variable_value :deep() img {
    max-width: 60% !important;
    height: auto;
}

.variable_value {
    display: inline-block;
    text-align: left;
}

/* Custom scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #15919a;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #0f6b72;
}

/* Firefox scrollbar */
.overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: #15919a #f1f5f9;
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
