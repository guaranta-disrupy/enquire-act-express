<script setup>
import JetButton from "@/Componentsv2/Button.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetInput from "@/Jetstream/Input.vue";
import JetLabel from "@/Jetstream/Label.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import JetConfirmationModal from "@/Jetstream/ConfirmationModal.vue";
import JetCheckbox from "@/Jetstream/Checkbox.vue";
import SmallButton from "@/Components/SmallButton.vue";
import { ref, computed, watch } from "vue";
import { Link, useForm, Head, router, usePage } from "@inertiajs/vue3";
import Helpers from "@/Mixins/Helpers";
import EmptyPlaceholder from "./EmptyPlaceholder.vue";
import { setProxiedHtml } from "@/utils/setProxiedHtml";
import { QuillEditor } from "@vueup/vue-quill";
import "quill/dist/quill.snow.css";
import "quill/dist/quill.bubble.css";
import { PrismEditor } from "vue-prism-editor";
import "vue-prism-editor/dist/prismeditor.min.css";
import { highlight, languages } from "prismjs/components/prism-core";
import "prismjs/components/prism-markup";
import "prismjs/components/prism-clike";
import "prismjs/components/prism-javascript";
import "prismjs/themes/prism-tomorrow.css";
import Toggle from "@/Jetstream/Toggle.vue";
import IndexVariables from "@/Pages/Variables2/Index.vue";

const props = defineProps([
    "project",
    "survey",
    "templatePrimes",
    "templateTargets",
]);

const { assign, toast, sanitize } = Helpers();

// Variables functionality
const showVariablesModal = ref(false);
const activeQuillEditor = ref(null);
const page = usePage();
const usePlainTextEditor = ref(false);
const variables = computed(() => {
    const pageVars = page?.props?.variables;
    if (Array.isArray(pageVars)) return pageVars;
    return [];
});

// Image URL modal state
const showImageUrlModal = ref(false);
const imageUrlsText = ref("");

// Quill editor refs
const bulkPrimesQuill = ref(null);
const bulkTargetsQuill = ref(null);
const bulkQuillEditorKey = ref(0);
const showTextToImageModal = ref(false);
const textToImageText = ref("");
const textToImageImageUrl = ref("");
const textToImageFeedback = ref("");
const canUseTextToImage = computed(() => (editForm.template_data || []).length > 0);

// CSV input refs
const csvInputPrimes = ref(null);
const csvInputTargets = ref(null);

// Tab management
const activeTab = ref("primes");

// Modal states
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showCreateModal = ref(false);
const showDeleteAllModal = ref(false);
const isCreating = ref(false);
const selectedTemplate = ref(null);
const selectedType = ref("prime"); // 'prime' or 'target'
watch(selectedType, () => {
    usePlainTextEditor.value = false;
});

// Dropdown states for showing template contents
const expandedTemplates = ref(new Set());

// Forms
const editForm = useForm({
    name: "",
    template_data: [],
});

const deleteForm = useForm({});
const createForm = useForm({
    name: "",
    template_data: [],
});
const createType = ref("prime");

// Bulk operations
const selectedItems = ref(new Set());
const selectAll = ref(false);
const newItem = ref({
    index: "",
    prime: "",
    target: "",
    equivalent: "",
    value: "",
});

// Bulk add (for primes)
const bulkPrimes = ref("");
const bulkEquivalents = ref("");
// Bulk add (for targets)
const bulkTargets = ref("");
const bulkTargetsEquivalents = ref("");

// Computed properties
const currentTemplates = computed(() => {
    return activeTab.value === "primes"
        ? props.templatePrimes
        : props.templateTargets;
});

// Helper functions
function getInitials(name) {
    if (!name) return "";
    const parts = name.trim().split(" ");
    const first = parts[0]?.[0] || "";
    const last = parts.length > 1 ? parts[parts.length - 1][0] : "";
    return (first + last).toUpperCase();
}

function toggleExpanded(templateId) {
    const newExpanded = new Set(expandedTemplates.value);
    if (newExpanded.has(templateId)) {
        newExpanded.delete(templateId);
    } else {
        newExpanded.add(templateId);
    }
    expandedTemplates.value = newExpanded;
}

function isExpanded(templateId) {
    return expandedTemplates.value.has(templateId);
}

// Modal functions
function showEditTemplateModal(template, type) {
    selectedTemplate.value = assign(template);
    selectedType.value = type;
    editForm.name = template.name;
    editForm.template_data = assign(template.data);
    isCreating.value = false;
    selectedItems.value = new Set();
    selectAll.value = false;
    resetNewItem();
    bulkPrimes.value = "";
    bulkEquivalents.value = "";
    bulkTargets.value = "";
    bulkTargetsEquivalents.value = "";
    bulkQuillEditorKey.value++;
    showEditModal.value = true;
}

function showDeleteTemplateModal(template, type) {
    selectedTemplate.value = assign(template);
    selectedType.value = type;
    showDeleteModal.value = true;
}

function openCreateTemplateModal() {
    // Open the edit modal in "create" mode with empty content
    selectedType.value = activeTab.value === "primes" ? "prime" : "target";
    isCreating.value = true;
    selectedTemplate.value = null;
    editForm.reset();
    editForm.name = "";
    editForm.template_data = [];
    selectedItems.value = new Set();
    selectAll.value = false;
    resetNewItem();
    bulkPrimes.value = "";
    bulkEquivalents.value = "";
    bulkTargets.value = "";
    bulkTargetsEquivalents.value = "";
    bulkQuillEditorKey.value++;
    showEditModal.value = true;
}

function createTemplate() {
    const routeName =
        createType.value === "prime"
            ? "templates.primes.store"
            : "templates.targets.store";
    const params = [props.project.team_id, props.project.uuid, props.survey.id];
    createForm.post(route(routeName, params), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(
                `${
                    createType.value === "prime" ? "Prime" : "Target"
                } template created`
            );
            showCreateModal.value = false;
            createForm.reset();
        },
    });
}

function confirmDeleteAll() {
    showDeleteAllModal.value = true;
}

async function deleteAllTemplates() {
    const list = currentTemplates.value || [];
    const routeBase =
        activeTab.value === "primes"
            ? "templates.primes.destroy"
            : "templates.targets.destroy";
    for (const t of list) {
        await router.delete(
            route(routeBase, [
                props.project.team_id,
                props.project.uuid,
                props.survey.id,
                t.id,
            ]),
            { preserveScroll: true }
        );
    }
    toast.success(
        `${
            activeTab.value === "primes" ? "All prime" : "All target"
        } templates deleted`
    );
    showDeleteAllModal.value = false;
}

function updateTemplate() {
    // Prevent saving when any item has an effectively empty prime/target
    const items = editForm.template_data || [];
    if (selectedType.value === "prime") {
        const hasEmptyPrime = items.some((item) => !hasVisibleContent(item?.prime));
        if (hasEmptyPrime) {
            try {
                toast.error("Prime should not be empty");
            } catch (_) {}
            return;
        }
    } else {
        const hasEmptyTarget = items.some(
            (item) => !hasVisibleContent(item?.target)
        );
        if (hasEmptyTarget) {
            try {
                toast.error("Target should not be empty");
            } catch (_) {}
            return;
        }
    }

    if (isCreating.value) {
        const storeRoute =
            selectedType.value === "prime"
                ? "templates.primes.store"
                : "templates.targets.store";
        const storeParams = [
            props.project.team_id,
            props.project.uuid,
            props.survey.id,
        ];
        editForm.post(route(storeRoute, storeParams), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    `${
                        selectedType.value === "prime" ? "Prime" : "Target"
                    } template created`
                );
                isCreating.value = false;
                showEditModal.value = false;
                // Refresh template lists
                router.reload({ only: ["templatePrimes", "templateTargets"] });
            },
        });
        return;
    }

    const routeName =
        selectedType.value === "prime"
            ? "templates.primes.update"
            : "templates.targets.update";
    const routeParams = [
        props.project.team_id,
        props.project.uuid,
        props.survey.id,
        selectedTemplate.value.id,
    ];

    editForm
        .transform((form) => ({
            name: form.name,
            data: form.template_data,
        }))
        .put(route(routeName, routeParams), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    `${
                        selectedType.value === "prime" ? "Prime" : "Target"
                    } template successfully updated`
                );
                showEditModal.value = false;
                editForm.reset();
            },
        });
}

async function copyTemplate(template, type) {
    try {
        const isPrime = type === "prime";
        const routeName = isPrime
            ? "templates.primes.copy"
            : "templates.targets.copy";
        const params = [
            props.project.team_id,
            props.project.uuid,
            props.survey.id,
            template.id,
        ];
        await router.post(
            route(routeName, params),
            {},
            { preserveScroll: true }
        );
        toast.success(`${isPrime ? "Prime" : "Target"} copied`);
        router.reload({ only: ["templatePrimes", "templateTargets"] });
    } catch (e) {
        toast.error("Failed to copy template");
    }
}

function deleteTemplate() {
    const routeName =
        selectedType.value === "prime"
            ? "templates.primes.destroy"
            : "templates.targets.destroy";
    const routeParams = [
        props.project.team_id,
        props.project.uuid,
        props.survey.id,
        selectedTemplate.value.id,
    ];

    deleteForm.delete(route(routeName, routeParams), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(
                `${
                    selectedType.value === "prime" ? "Prime" : "Target"
                } template successfully deleted`
            );
            showDeleteModal.value = false;
        },
    });
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

const stripParagraphsAroundImages = (html) => {
    if (!html) return html;
    try {
        // Preserve <p> wrappers when there's any non-image content; strip only image-only paragraphs
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

const convertTextToImageInTemplateItems = () => {
    textToImageFeedback.value = "";
    const searchText = textToImageText.value.trim();
    const imageUrl = textToImageImageUrl.value.trim();

    if (!searchText || !imageUrl) {
        textToImageFeedback.value = "Both fields are required.";
        return;
    }

    const field = selectedType.value === "prime" ? "prime" : "target";
    const items = editForm.template_data || [];
    let replacements = 0;

    items.forEach((item, index) => {
        if (!item || typeof item[field] !== "string") return;
        if (!item[field].includes(searchText)) return;
        const updated = stripParagraphsAroundImages(
            replaceTextWithImage(item[field], searchText, imageUrl)
        );
        if (updated !== item[field]) {
            editForm.template_data[index][field] = updated;
            replacements++;
        }
    });

    if (!replacements) {
        textToImageFeedback.value = "No matches found in current items.";
        return;
    }

    textToImageText.value = "";
    textToImageImageUrl.value = "";
    showTextToImageModal.value = false;
};

const prismHighlighter = (code) => highlight(code, languages.markup);

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

// Override Quill toolbar positioning to start at the beginning of selection
const setupQuillToolbarPositioning = (quillInstance) => {
    if (!quillInstance) return;

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

            // Add custom image insertion button if it doesn't exist
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

    showVariablesModal.value = false;
};

// Listen for Variables modal apply event
function onQuillVariableInsertRequest(e) {
    try {
        const raw = e?.detail?.name;
        if (!raw) return;
        insertVariableIntoQuill(raw);
    } catch (_) {}
}

if (typeof window !== "undefined") {
    try {
        window.addEventListener(
            "template-quill-variable-insert-request",
            onQuillVariableInsertRequest
        );
    } catch (_) {}
}

const closeVariablesModal = () => {
    showVariablesModal.value = false;
    activeQuillEditor.value = null;
};

// Open modal to insert multiple image URLs
const openImageUrlModal = (quillInstance = null) => {
    try {
        if (quillInstance) {
            activeQuillEditor.value = quillInstance;
        } else {
            // Try to get the active editor from bulk editors
            if (bulkPrimesQuill.value) {
                activeQuillEditor.value = bulkPrimesQuill.value;
            } else if (bulkTargetsQuill.value) {
                activeQuillEditor.value = bulkTargetsQuill.value;
            }
        }
        imageUrlsText.value = "";
        showImageUrlModal.value = true;
    } catch (_) {}
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

// Trigger CSV upload for primes
const triggerCSVUploadPrimes = () => {
    csvInputPrimes.value?.click();
};

// Trigger CSV upload for targets
const triggerCSVUploadTargets = () => {
    csvInputTargets.value?.click();
};

// Handle CSV upload for primes modal
const handleCSVUploadPrimes = async (event) => {
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

        // Convert to HTML format for Quill editor
        const primesText = primes.join("\n");
        const equivalentsText = equivalents.join("\n");

        // Populate the bulk input fields
        bulkPrimes.value = editorToHtmlNoP(primesText);
        bulkEquivalents.value = equivalentsText;
    } catch (err) {
        console.error("Error reading CSV file:", err);
        toast.error("Error reading CSV file. Please make sure it's a valid CSV file.");
    }

    // Reset file input
    event.target.value = "";
};

// Handle CSV upload for targets modal
const handleCSVUploadTargets = async (event) => {
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

        // Convert to HTML format for Quill editor
        const targetsText = targets.join("\n");
        const equivalentsText = equivalents.join("\n");

        // Populate the bulk input fields
        bulkTargets.value = editorToHtmlNoP(targetsText);
        bulkTargetsEquivalents.value = equivalentsText;
    } catch (err) {
        console.error("Error reading CSV file:", err);
        toast.error("Error reading CSV file. Please make sure it's a valid CSV file.");
    }

    // Reset file input
    event.target.value = "";
};

// Download CSV template for primes
const downloadCSVTemplatePrimes = () => {
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

// Download CSV template for targets
const downloadCSVTemplateTargets = () => {
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

// Bulk operations functions
function toggleSelectAll() {
    if (selectAll.value) {
        selectedItems.value = new Set(
            editForm.template_data.map((_, index) => index)
        );
    } else {
        selectedItems.value = new Set();
    }
}

function toggleItemSelection(index) {
    const newSelected = new Set(selectedItems.value);
    if (newSelected.has(index)) {
        newSelected.delete(index);
    } else {
        newSelected.add(index);
    }
    selectedItems.value = newSelected;
    selectAll.value = newSelected.size === editForm.template_data.length;
}

function deleteSelectedItems() {
    const indicesToDelete = Array.from(selectedItems.value).sort(
        (a, b) => b - a
    );
    indicesToDelete.forEach((index) => {
        editForm.template_data.splice(index, 1);
    });
    selectedItems.value = new Set();
    selectAll.value = false;
}

function deleteItem(index) {
    editForm.template_data.splice(index, 1);
    // Remove from selection if it was selected
    selectedItems.value.delete(index);
    // Update selection indices after deletion
    const newSelectedItems = new Set();
    selectedItems.value.forEach((selectedIndex) => {
        if (selectedIndex > index) {
            newSelectedItems.add(selectedIndex - 1);
        } else if (selectedIndex < index) {
            newSelectedItems.add(selectedIndex);
        }
    });
    selectedItems.value = newSelectedItems;
    selectAll.value = false;
}

function resetNewItem() {
    const next = getNextIndex();
    newItem.value = {
        index: "",
        prime: "",
        target: "",
        equivalent: "",
        value: selectedType.value === "target" ? String(next) : "",
    };
}

function getNextIndex() {
    // Determine next index by finding the max current index and adding 1
    const items = editForm.template_data || [];
    if (items.length === 0) {
        return 1;
    }
    const maxIndex = items.reduce((max, item) => {
        const num = Number(item.index) || 0;
        return num > max ? num : max;
    }, 0);
    return maxIndex + 1;
}

function addNewItem() {
    const itemToAdd = { ...newItem.value };
    if (selectedType.value === "prime") {
        if (!itemToAdd.prime) {
            toast.error("Prime content is required");
            return;
        }
        // Auto-assign incremental index for primes
        itemToAdd.index = getNextIndex();
        delete itemToAdd.target;
        delete itemToAdd.value;
    } else {
        if (!itemToAdd.target) {
            toast.error("Target content is required");
            return;
        }
        // Auto-assign incremental index for targets
        const nextIdx = getNextIndex();
        itemToAdd.index = nextIdx;
        if (!itemToAdd.value) {
            itemToAdd.value = String(nextIdx);
        }
        // Initialize can_unselect and colors for targets
        if (itemToAdd.can_unselect === undefined) {
            itemToAdd.can_unselect = false;
        }
        if (!itemToAdd.colors) {
            itemToAdd.colors = {
                normal: null,
                hover: null,
                active: null,
            };
        }
        delete itemToAdd.prime;
    }

    editForm.template_data.push(itemToAdd);
    resetNewItem();
}

function updateItemField(index, field, value) {
    editForm.template_data[index][field] = value;
}

function addBulkPrimes() {
    const primesTextRaw = bulkPrimes.value;
    if (!primesTextRaw || !String(primesTextRaw).trim()) {
        toast.error("Need to input character to add a prime");
        return;
    }

    // Handle both plain text and HTML content
    let processed;
    const containsHtml =
        typeof primesTextRaw === "string" && /<[^>]+>/.test(primesTextRaw);
    if (containsHtml) {
        // Preserve paragraph wrappers so text and images stay grouped
        processed = htmlToEditorNoP(primesTextRaw, {
            preserveParagraphs: true,
        });
    } else {
        // If it's plain text, use it directly
        processed = primesTextRaw;
    }

    const primeLines = processed.split("\n");
    const equivalentLines = (bulkEquivalents.value || "")
        .split(/\r?\n/);

    // Filter out lines that have no visible content (no text and no image)
    const primes = [];
    const equivalents = [];
    primeLines.forEach((line, idx) => {
        if (hasVisibleContent(line)) {
            primes.push(line);
            equivalents.push((equivalentLines[idx] || "").trim());
        }
    });

    if (primes.length === 0) {
        toast.error("Need to input character to add a prime");
        return;
    }

    let next = getNextIndex();
    for (let i = 0; i < primes.length; i += 1) {
        const primeText = primes[i];
        if (!primeText) continue;
        // For plain text, just convert newlines to <br>; for HTML, preserve formatting
        const primeHtml = primeText.includes("<")
            ? primeText
            : editorToHtmlNoP(primeText.trim());
        const eq = equivalents[i] || "";
        const indexValue = next++;
        editForm.template_data.push({
            index: indexValue,
            value: indexValue,
            prime: primeHtml,
            equivalent: eq,
            config: {
                conditions: [],
            },
            showButtons: false,
        });
    }
    bulkPrimes.value = "";
    bulkEquivalents.value = "";
    bulkQuillEditorKey.value++;
}

// TODO: Add conditions to the bulk add for primes
// Example config if has conditions
// Ex; with brc variable
// "config": {
//       "conditions": [
//         {
//           "method": "process",
//           "left_operator": "and",
//           "var": "brc",
//           "operator": "!=",
//           "val": "1",
//           "conditions": []
//         }
//       ]
//     },

function addBulkTargets() {
    const targetsTextRaw = bulkTargets.value;
    if (!targetsTextRaw || !String(targetsTextRaw).trim()) {
        toast.error("Need to input character to add a target");
        return;
    }

    // Handle both plain text and HTML content
    let processed;
    const containsHtml =
        typeof targetsTextRaw === "string" && /<[^>]+>/.test(targetsTextRaw);
    if (containsHtml) {
        // Preserve paragraph wrappers so text and images stay grouped
        processed = htmlToEditorNoP(targetsTextRaw, {
            preserveParagraphs: true,
        });
    } else {
        // If it's plain text, use it directly
        processed = targetsTextRaw;
    }

    const targetLines = processed.split("\n");
    const equivalentLines = (bulkTargetsEquivalents.value || "")
        .split(/\r?\n/);

    // Filter out lines that have no visible content (no text and no image)
    const targets = [];
    const equivalents = [];
    targetLines.forEach((line, idx) => {
        if (hasVisibleContent(line)) {
            targets.push(line);
            equivalents.push((equivalentLines[idx] || "").trim());
        }
    });

    if (targets.length === 0) {
        toast.error("Need to input character to add a target");
        return;
    }

    let next = getNextIndex();
    for (let i = 0; i < targets.length; i += 1) {
        const targetText = targets[i];
        if (!targetText) continue;
        // For plain text, just convert newlines to <br>; for HTML, preserve formatting
        const targetHtml = targetText.includes("<")
            ? targetText
            : editorToHtmlNoP(targetText.trim());
        const eq = equivalents[i] || "";
        const indexValue = next++;
        editForm.template_data.push({
            index: indexValue,
            value: indexValue,
            target: targetHtml,
            equivalent: eq,
            can_unselect: false,
            colors: {
                normal: null,
                hover: null,
                active: null,
            },
            showButtons: false,
        });
    }
    bulkTargets.value = "";
    bulkTargetsEquivalents.value = "";
    bulkQuillEditorKey.value++;
}
</script>

<template>
    <Head title="Templates - Primes & Targets" />
    <div class="bg-slate-200 min-h-[100vh] text-[#323232]">
        <div class="flex flex-col max-w-full mx-auto">
            <!-- Header -->
            <div class="flex flex-col sticky top-0 bg-slate-200">
                <div
                    class="flex items-center bg-white justify-between h-[70px] shadow-md p-4 z-100"
                >
                    <div class="flex items-center px-1 gap-4">
                        <div
                            class="active:scale-[0.85] transition-all duration-200"
                        >
                            <Link
                                :href="route('survey2.index', survey)"
                                class=""
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    class="w-[60px] hover:stroke-[#15919A] transition-colors duration-200"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m11.25 9-3 3m0 0 3 3m-3-3h7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                    />
                                </svg>
                            </Link>
                        </div>

                        <div
                            class="text-[24px] rounded-lg w-auto h-[40px] justify-center items-center flex font-bold text-[#323232]"
                        >
                            {{ props.project.name }} / Templates
                        </div>
                    </div>

                    <div
                        class="w-[50px] h-[50px] flex items-center justify-center rounded-full bg-[#15919A30] text-[#15919A] text-[20px] cursor-default"
                        :title="$page.props.auth.user.name"
                    >
                        {{ getInitials($page.props.auth.user.name) }}
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div
                class="flex flex-col w-[110vh] min-h-[85vmin] mx-auto bg-white mt-5 shadow-lg rounded-xl py-6"
            >
                <!-- Tabs -->
                <div class="bg-white border-b border-gray-200 px-8">
                    <nav class="-mb-px flex items-center justify-between">
                        <div class="flex">
                            <button
                                class="px-4"
                                @click="activeTab = 'primes'"
                                :class="[
                                    activeTab === 'primes'
                                        ? 'border-[#15919A] text-[#15919A]'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-md',
                                ]"
                            >
                                Prime Templates
                                <span
                                    :class="[
                                        activeTab === 'primes'
                                            ? 'bg-[#15919A] text-white'
                                            : 'bg-gray-100 text-gray-900',
                                        'ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium',
                                    ]"
                                >
                                    {{ templatePrimes?.length || 0 }}
                                </span>
                            </button>
                            <button
                                class="px-4"
                                @click="activeTab = 'targets'"
                                :class="[
                                    activeTab === 'targets'
                                        ? 'border-[#15919A] text-[#15919A]'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-md',
                                ]"
                            >
                                Target Templates
                                <span
                                    :class="[
                                        activeTab === 'targets'
                                            ? 'bg-[#15919A] text-white'
                                            : 'bg-gray-100 text-gray-900',
                                        'ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium',
                                    ]"
                                >
                                    {{ templateTargets?.length || 0 }}
                                </span>
                            </button>
                        </div>
                        <div
                            class="flex items-center space-x-2"
                            v-if="currentTemplates?.length > 0"
                        >
                            <JetButton
                                size="sm"
                                variant="primary"
                                @click="openCreateTemplateModal"
                                >{{
                                    activeTab === "primes"
                                        ? "Add Prime"
                                        : "Add Target"
                                }}
                                Template</JetButton
                            >
                            <JetButton
                                size="sm"
                                variant="slate"
                                @click="confirmDeleteAll"
                                >Delete All</JetButton
                            >
                        </div>
                    </nav>
                </div>
                <!-- Empty State -->
                <div
                    v-if="!currentTemplates || currentTemplates.length === 0"
                    class="flex flex-col h-[50vmin] items-center justify-center align-items-center mt-8 mb-8"
                >
                    <EmptyPlaceholder
                        :label="activeTab === 'primes' ? 'Prime' : 'Target'"
                        @create="openCreateTemplateModal"
                    />
                </div>

                <!-- Templates List -->
                <div
                    v-else
                    class="overflow-y-auto w-full px-8 py-4 max-h-[calc(100vh-200px)]"
                >
                    <template
                        v-for="(template, index) in currentTemplates"
                        :key="template.id"
                    >
                        <div
                            class="mb-3 hover:bg-slate-200/90 rounded-xl transform transition-all w-full bg-slate-100"
                        >
                            <!-- Template Header -->
                            <div class="px-6 py-4 w-full">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <!-- <div class="flex-shrink-0">
                                            <div
                                                class="w-10 h-10 bg-[#15919A] rounded-full flex items-center justify-center text-white font-semibold"
                                            >
                                                {{
                                                    activeTab === "primes"
                                                        ? "P"
                                                        : "T"
                                                }}
                                            </div>
                                        </div> -->
                                        <div>
                                            <div
                                                class="flex items-center space-x-2 cursor-pointer select-none"
                                                @click="
                                                    toggleExpanded(template.id)
                                                "
                                            >
                                                <svg
                                                    :class="{
                                                        'rotate-180':
                                                            isExpanded(
                                                                template.id
                                                            ),
                                                    }"
                                                    class="w-4 h-4 text-gray-500 transition-transform"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 9l-7 7-7-7"
                                                    />
                                                </svg>
                                                <h3
                                                    class="text-md font-medium text-gray-900"
                                                >
                                                    {{ template.name }}
                                                </h3>
                                            </div>
                                            <p
                                                class="text-[0.7rem] text-gray-500 ml-6"
                                            >
                                                Created
                                                {{
                                                    formatDate(
                                                        template.created_at
                                                    )
                                                }}
                                                •
                                                {{ template.data?.length || 0 }}
                                                items
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <!-- Copy Buttons -->
                                        <JetButton
                                            size="xs"
                                            variant="primary-light"
                                            class="font-medium"
                                            @click="
                                                copyTemplate(
                                                    template,
                                                    activeTab === 'primes'
                                                        ? 'prime'
                                                        : 'target'
                                                )
                                            "
                                        >
                                            Duplicate
                                        </JetButton>
                                        <!-- Action Buttons -->
                                        <JetButton
                                            size="xs"
                                            variant="primary-light"
                                            class="font-medium"
                                            @click="
                                                showEditTemplateModal(
                                                    template,
                                                    activeTab === 'primes'
                                                        ? 'prime'
                                                        : 'target'
                                                )
                                            "
                                        >
                                            Update
                                        </JetButton>
                                        <JetButton
                                            size="xs"
                                            variant="danger-light"
                                            class="font-medium"
                                            @click="
                                                showDeleteTemplateModal(
                                                    template,
                                                    activeTab === 'primes'
                                                        ? 'prime'
                                                        : 'target'
                                                )
                                            "
                                        >
                                            Delete
                                        </JetButton>
                                    </div>
                                </div>
                            </div>

                            <!-- Template Content (Expandable) -->
                            <div v-if="isExpanded(template.id)" class="">
                                <div class="overflow-x-auto">
                                    <table
                                        class="min-w-full table-fixed divide-y divide-gray-200 border border-t-0"
                                    >
                                        <thead class="bg-white">
                                            <tr class="">
                                                <th
                                                    class="pl-7 px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8"
                                                >
                                                    #
                                                </th>
                                                <th
                                                    class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-14"
                                                >
                                                    Index
                                                </th>
                                                <th
                                                    class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/2"
                                                >
                                                    {{
                                                        activeTab === "primes"
                                                            ? "Prime"
                                                            : "Target"
                                                    }}
                                                </th>
                                                <th
                                                    class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/2"
                                                >
                                                    Equivalent
                                                </th>
                                                <th
                                                    v-if="
                                                        activeTab === 'targets'
                                                    "
                                                    class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20"
                                                >
                                                    Value
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="bg-white divide-y divide-gray-200"
                                        >
                                            <tr
                                                v-for="(
                                                    item, itemIndex
                                                ) in template.data"
                                                :key="itemIndex"
                                                class="hover:bg-gray-50"
                                            >
                                                <td
                                                    class="pl-7 px-3 py-2 whitespace-nowrap text-sm text-gray-500"
                                                >
                                                    {{ itemIndex + 1 }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 whitespace-nowrap text-sm text-gray-900"
                                                >
                                                    {{ item.index }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-sm text-gray-900 align-top"
                                                >
                                                    <div
                                                        v-html="
                                                            setProxiedHtml(
                                                                activeTab ===
                                                                    'primes'
                                                                    ? item.prime
                                                                    : item.target
                                                            )
                                                        "
                                                        class="whitespace-pre-wrap break-words"
                                                    ></div>
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-sm text-gray-900 align-top whitespace-normal break-words"
                                                >
                                                    {{ item.equivalent }}
                                                </td>
                                                <td
                                                    v-if="
                                                        activeTab === 'targets'
                                                    "
                                                    class="px-3 py-2 whitespace-nowrap text-sm text-gray-900"
                                                >
                                                    {{ item.value }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Edit Template Modal -->
        <JetDialogModal
            :show="showEditModal"
            maxWidth="6xl"
            @close="showEditModal = false"
        >
            <template #title>
                <span class="text-[#15919A] font-bold">
                    {{ isCreating ? "Add" : "Edit" }}
                    {{ selectedType === "prime" ? "Prime" : "Target" }} Template
                </span>
            </template>
            <template #content>
                <div class="space-y-6 mt-6">
                    <!-- Template Name -->
                    <div>
                        <JetLabel value="Template Name" />
                        <JetInput
                            v-model="editForm.name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Enter template name"
                        />
                        <JetInputError
                            :message="editForm.errors.name"
                            class="mt-2"
                        />
                    </div>

                    <!-- Items Table -->
                    <div>
                        <div class="flex items-center gap-2">
                            <JetLabel
                                :value="`${
                                    selectedType === 'prime' ? 'Prime' : 'Target'
                                } Items`"
                            />
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
                            <div
                                class="flex items-center space-x-2 text-xs text-gray-600"
                            >
                                <Toggle v-model="usePlainTextEditor" />
                                <span class="text-sm text-gray-600"
                                    > Editor</span
                                >
                            </div>
                        </div>
                        
                        <!-- Select All - Above Table -->
                        <div
                            class="flex items-center justify-between bg-gray-100/80 p-4 rounded-lg h-12 mt-2"
                        >
                            <div class="flex items-center space-x-4">
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="selectAll"
                                        @change="toggleSelectAll"
                                        :disabled="editForm.template_data.length === 0"
                                        class="rounded border-gray-300 text-[#15919A] focus:ring-[#15919A] disabled:opacity-50 disabled:cursor-not-allowed"
                                    />
                                    <span
                                        class="ml-2 text-sm font-medium text-gray-700"
                                    >
                                        Select All ({{ selectedItems.size }}
                                        selected)
                                    </span>
                                </label>
                            </div>
                            <div class="flex space-x-2">
                                <JetButton
                                    v-if="selectedItems.size > 0"
                                    variant="danger"
                                    size="xs"
                                    @click="deleteSelectedItems"
                                >
                                    Delete Selected ({{ selectedItems.size }})
                                </JetButton>
                            </div>
                        </div>

                        <div
                            class="mt-2 max-h-96 overflow-y-auto border border-gray-200 rounded-md"
                        >
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead
                                    class="bg-[#D4ECEF] text-base sticky top-0 z-10"
                                >
                                    <tr>
                                        <th
                                            class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-1"
                                        >
                                            <input
                                                type="checkbox"
                                                v-model="selectAll"
                                                @change="toggleSelectAll"
                                                :disabled="editForm.template_data.length === 0"
                                                class="rounded border-gray-300 text-[#15919A] focus:ring-[#15919A] disabled:opacity-50 disabled:cursor-not-allowed"
                                            />
                                        </th>
                                        <th
                                            class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-1/12"
                                        >
                                            Index
                                        </th>
                                        <th
                                           :class="['px-3','py-3','text-left','text-xs','font-medium','uppercase','tracking-wider', showImageColumn ? 'w-3/12' : 'w-5/12']"
                                        >
                                            {{
                                                selectedType === "prime"
                                                    ? "Prime"
                                                    : "Target"
                                            }}
                                        </th>
                                        <th
                                            class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-2/12"
                                        >
                                            Equivalent
                                        </th>
                                        <th
                                            v-if="selectedType === 'target'"
                                            class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-1/12"
                                        >
                                            Value
                                        </th>
                                        <th
                                            v-if="selectedType === 'target'"
                                            class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-1/12"
                                        >
                                            Unselect
                                        </th>
                                        <th
                                            class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider w-1/12"
                                        >
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <tr
                                        v-for="(
                                            item, index
                                        ) in editForm.template_data"
                                        :key="index"
                                        :class="{
                                            'bg-[#15919A30]':
                                                selectedItems.has(index),
                                        }"
                                    >
                                        <td class="px-3 py-1">
                                            <input
                                                type="checkbox"
                                                :checked="
                                                    selectedItems.has(index)
                                                "
                                                @change="
                                                    toggleItemSelection(index)
                                                "
                                                class="rounded border-gray-300 text-[#15919A] focus:ring-[#15919A]"
                                            />
                                        </td>
                                        <td class="px-3 py-1">
                                            <div 
                                                :model-value="item.index"
                                                @update:model-value="
                                                    updateItemField(
                                                        index,
                                                        'index',
                                                        $event
                                                    )
                                                "
                                                type="text"
                                                class="w-full h-[34px] text-sm bg-gray-50 border border-gray-300 rounded-md px-3 py-2 flex items-center focus:ring-[#15919A] focus:border-[#15919A]"
                                                placeholder="Index"
                                            >
                                            {{ item.index }}
                                            </div>
                                        </td>
                                        <td class="px-3 py-1 w-5/12">
                                            <div
                                                v-if="!usePlainTextEditor"
                                                class="quill-bubble-wrapper border border-gray-300 rounded-md"
                                            >
                                                <QuillEditor
                                                    theme="bubble"
                                                    :content="parseVariablesToElements(
                                                        selectedType === 'prime'
                                                            ? item.prime
                                                            : item.target
                                                    )"
                                                    @update:content="
                                                        updateItemField(
                                                            index,
                                                            selectedType === 'prime'
                                                                ? 'prime'
                                                                : 'target',
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
                                                    placeholder="Click to edit content..."
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
                                            <div v-else class="prism-item-editor">
                                                <PrismEditor
                                                    :model-value="
                                                        htmlToEditorNoP(
                                                            selectedType === 'prime'
                                                                ? item.prime
                                                                : item.target,
                                                            { preserveParagraphs: true }
                                                        )
                                                    "
                                                    @update:model-value="
                                                        (value) =>
                                                            updateItemField(
                                                                index,
                                                                selectedType === 'prime'
                                                                    ? 'prime'
                                                                    : 'target',
                                                                editorToHtmlNoP(value)
                                                            )
                                                    "
                                                    :highlight="prismHighlighter"
                                                    line-numbers
                                                    class="text-sm"
                                                />
                                            </div>
                                        </td>
                                        <td class="px-3 py-1 w-4/12">
                                            <JetInput
                                                :model-value="item.equivalent"
                                                @update:model-value="
                                                    updateItemField(
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
                                            v-if="selectedType === 'target'"
                                            class="px-3 py-1 w-1/12"
                                        >
                                            <JetInput
                                                :model-value="item.value"
                                                @update:model-value="
                                                    updateItemField(
                                                        index,
                                                        'value',
                                                        $event
                                                    )
                                                "
                                                type="number"
                                                class="w-full h-[34px] text-sm focus:ring-[#15919A] focus:border-[#15919A]"
                                                placeholder="Value"
                                            />
                                        </td>
                                        <td
                                            v-if="selectedType === 'target'"
                                            class="px-3 py-1 w-1/12"
                                        >
                                            <div
                                                class="flex items-center justify-center"
                                            >
                                                <JetCheckbox
                                                    :checked="item.can_unselect"
                                                    @update:checked="
                                                        updateItemField(
                                                            index,
                                                            'can_unselect',
                                                            $event
                                                        )
                                                    "
                                                    class="rounded border-gray-300 text-[#15919A] focus:ring-[#15919A]"
                                                />
                                            </div>
                                        </td>
                                        <td class="px-3 py-1 w-1/12">
                                            <div
                                                class="bg-white p-2 border shadow-sm rounded-md justify-center items-center flex"
                                            >
                                                <button
                                                    type="button"
                                                    @click="deleteItem(index)"
                                                    class="text-red-600 hover:text-red-800 transition-colors duration-200"
                                                    :title="`Delete this ${selectedType === 'prime' ? 'prime' : 'target'}`"
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
                                    <!-- Show empty state when no primes or targets -->
                                    <tr v-if="editForm.template_data.length === 0">
                                        <td
                                            :colspan="selectedType === 'target' ? 7 : 5"
                                            class="px-3 py-8 text-center text-gray-500"
                                        >
                                            No {{ selectedType === 'prime' ? 'primes' : 'targets' }} added yet. Use the form below to add {{ selectedType === 'prime' ? 'primes' : 'targets' }}.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Add New Item / Bulk Add -->
                    <div class="">
                        <!-- <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-medium text-gray-900">
                                Add
                                {{
                                    selectedType === "prime"
                                        ? "Prime"
                                        : "Target"
                                }}
                            </h4>
                        </div> -->

                        <!-- Single add row -->
                        <!-- <div class="grid grid-cols-11 gap-3 mb-4">
                            <template v-if="selectedType === 'prime'">
                                <div class="col-span-5">
                                    <JetInput
                                        v-model="newItem.prime"
                                        placeholder="Prime content"
                                        class="w-full min-h-[40px] text-sm border border-gray-300 rounded-md focus:ring-[#15919A] focus:border-[#15919A]"
                                    ></JetInput>
                                </div>
                                <div class="col-span-5">
                                    <JetInput
                                        v-model="newItem.equivalent"
                                        type="text"
                                        placeholder="Equivalent (optional)"
                                        class="w-full min-h-[40px] text-sm border border-gray-300 rounded-md focus:ring-[#15919A] focus:border-[#15919A]"
                                    />
                                </div>
                                <div class="col-span-1">
                                    <JetButton
                                        @click="addNewItem"
                                        variant="slate"
                                        class="text-sm py-1 h-[40px] w-full text-center justify-center"
                                    >
                                        Add
                                    </JetButton>
                                </div>
                            </template>
                            <template v-else>
                                <div class="col-span-5">
                                    <JetInput
                                        v-model="newItem.target"
                                        placeholder="Target content"
                                        class="w-full min-h-[40px] text-sm border border-gray-300 rounded-md focus:ring-[#15919A] focus:border-[#15919A]"
                                    ></JetInput>
                                </div>
                                <div class="col-span-5">
                                    <JetInput
                                        v-model="newItem.equivalent"
                                        type="text"
                                        placeholder="Equivalent (optional)"
                                        class="w-full text-sm"
                                    />
                                </div>
                                <div class="col-span-1">
                                    <JetButton
                                        @click="addNewItem"
                                        variant="slate"
                                        class="w-full text-sm py-2 text-center justify-center"
                                    >
                                        Add
                                    </JetButton>
                                </div>
                            </template>
                        </div> -->

                        <!-- Bulk add for primes -->
                        <div v-if="selectedType === 'prime'" class="mt-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="flex items-center justify-between">
                                    <JetLabel
                                        value="Primes (one per line)"
                                    />
                                    
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
                                            :content="bulkPrimes"
                                            @update:content="bulkPrimes = $event"
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
                                            class="min-h-[120px] text-sm"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <JetLabel
                                        value="Equivalents (align by line with primes)"
                                    />
                                    <textarea
                                        v-model="bulkEquivalents"
                                        placeholder=""
                                        class="mt-1 w-full min-h-[120px] text-sm border border-gray-300 rounded-md focus:ring-[#15919A] focus:border-[#15919A]"
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
                                    @click="addBulkPrimes"
                                    class="text-sm"
                                >
                                    Add Primes
                                </JetButton>

                                <div
                                class="relative group"
                                >
                                <JetButton
                                    variant="gray-100"
                                    @click="triggerCSVUploadPrimes"
                                    class="text-sm"
                                >
                                    Upload CSV
                                </JetButton>
                                <div
                                class="absolute bottom-full left-[-80px] mb-1.5 px-2 py-2 rounded text-xs text-white bg-[#15919a] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                role="tooltip"
                                >
                                Use this option to import a list of primes from a file.<br/> The CSV file must be formatted correctly to match<br/> the platform's required structure.                                </div>

                                </div>


                                <div
                                class="relative group"
                                >
                                <JetButton
                                    variant="gray-100"
                                    @click="downloadCSVTemplatePrimes"
                                    class="text-sm"
                                >
                                    Download CSV Template
                                </JetButton>
                                <div
                                class="absolute bottom-full left-[-30px] mb-1.5 px-2 py-2 rounded text-xs text-white bg-[#15919a] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                role="tooltip"
                                >
                                Click this to download a blank template file. <br/>Use this template to ensure your primes and <br/> equivalents are structured correctly before <br/>using the Upload CSV function. 
                                </div>

                            </div>


                                <input
                                    ref="csvInputPrimes"
                                    type="file"
                                    accept=".csv"
                                    @change="handleCSVUploadPrimes"
                                    class="hidden"
                                />
                            </div>
                        </div>

                        <!-- Bulk add for targets -->
                        <div v-if="selectedType === 'target'" class="mt-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="flex items-center justify-between">
                                    <JetLabel
                                        value="Targets (one per line)"
                                    />

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
                                            :content="bulkTargets"
                                            @update:content="bulkTargets = $event"
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
                                            class="min-h-[120px] text-sm"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <JetLabel
                                        value="Equivalents (align by line with targets)"
                                    />
                                    <textarea
                                        v-model="bulkTargetsEquivalents"
                                        placeholder=""
                                        class="mt-1 w-full min-h-[120px] text-sm border border-gray-300 rounded-md focus:ring-[#15919A] focus:border-[#15919A]"
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
                                    @click="addBulkTargets"
                                    class="text-sm"
                                >
                                    Add Targets
                                </JetButton>

                                <div
                                class="relative group"
                                >
                                <JetButton
                                    variant="gray-100"
                                    @click="triggerCSVUploadTargets"
                                    class="text-sm"
                                >
                                    Upload CSV
                                </JetButton>
                                <div
                                class="absolute bottom-full left-[-80px] mb-1.5 px-2 py-2 rounded text-xs text-white bg-[#15919a] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                role="tooltip"
                                >
                                Use this option to import a list of targets from a file.<br/> The CSV file must be formatted correctly to match<br/> the platform's required structure.                                </div>


                                </div>


                                <div
                                class="relative group"
                                >
                                <JetButton
                                    variant="gray-100"
                                    @click="downloadCSVTemplateTargets"
                                    class="text-sm"
                                >
                                    Download CSV Template
                                </JetButton>
                                <div
                                class="absolute bottom-full left-[-30px] mb-1.5 px-2 py-2 rounded text-xs text-white bg-[#15919a] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                role="tooltip"
                                >
                                Click this to download a blank template file.<br/>Use this template to ensure your targets and <br/> equivalents are structured correctly before <br/>using the Upload CSV function. 
                                </div>
                                </div>

                                <input
                                    ref="csvInputTargets"
                                    type="file"
                                    accept=".csv"
                                    @change="handleCSVUploadTargets"
                                    class="hidden"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <JetButton
                    variant="default"
                    @click="showEditModal = false"
                    class="mr-2"
                >
                    CANCEL
                </JetButton>
                <JetButton
                    variant="primary"
                    @click="updateTemplate"
                    :class="{ 'opacity-25': editForm.processing }"
                    :disabled="editForm.processing"
                >
                    SAVE TEMPLATE
                </JetButton>
            </template>
        </JetDialogModal>

        <!-- Delete Confirmation Modal -->
        <JetConfirmationModal
            :show="showDeleteModal"
            @close="showDeleteModal = false"
            @confirmed="deleteTemplate"
        >
            <template #title>
                Delete
                {{ selectedType === "prime" ? "Prime" : "Target" }} Template
            </template>
            <template #content>
                Are you sure you want to delete the template "{{
                    selectedTemplate?.name
                }}"? This action cannot be undone.
            </template>
            <template #footer>
                <JetButton
                    variant="default"
                    @click="showDeleteModal = false"
                    class="mr-2"
                >
                    Cancel
                </JetButton>
                <JetButton variant="danger" @click="deleteTemplate">
                    Delete
                </JetButton>
            </template>
        </JetConfirmationModal>

        <!-- Create Template Modal -->
        <JetDialogModal
            :show="showCreateModal"
            maxWidth="2xl"
            @close="showCreateModal = false"
        >
            <template #title>
                <span class="text-[#15919A] font-bold"
                    >New
                    {{ createType === "prime" ? "Prime" : "Target" }}
                    Template</span
                >
            </template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <JetLabel value="Template Name" />
                        <JetInput
                            v-model="createForm.name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Enter template name"
                        />
                        <JetInputError
                            :message="createForm.errors.name"
                            class="mt-2"
                        />
                    </div>
                    <p class="text-xs text-gray-500">
                        You can populate items later using Update.
                    </p>
                </div>
            </template>
            <template #footer>
                <JetButton
                    variant="default"
                    @click="showCreateModal = false"
                    class="mr-2"
                    >Cancel</JetButton
                >
                <JetButton
                    variant="primary"
                    @click="createTemplate"
                    :class="{ 'opacity-25': createForm.processing }"
                    :disabled="createForm.processing"
                    >Create</JetButton
                >
            </template>
        </JetDialogModal>

        <!-- Delete All Confirmation Modal -->
        <JetConfirmationModal
            :show="showDeleteAllModal"
            @close="showDeleteAllModal = false"
            @confirmed="deleteAllTemplates"
        >
            <template #title>
                Delete All
                {{ activeTab === "primes" ? "Prime" : "Target" }} Templates
            </template>
            <template #content>
                Are you sure you want to delete all templates in this tab? This
                action cannot be undone.
            </template>
            <template #footer>
                <JetButton
                    variant="default"
                    @click="showDeleteAllModal = false"
                    class="mr-2"
                >
                    Cancel
                </JetButton>
                <JetButton variant="danger" @click="deleteAllTemplates">
                    Delete All
                </JetButton>
            </template>
        </JetConfirmationModal>

        <!-- Variables Modal -->
        <JetDialogModal :show="showVariablesModal" @close="closeVariablesModal" maxWidth="4xl">
            <template #content>
                <IndexVariables
                    :project="project"
                    :survey="survey"
                    :enable-apply="true"
                    apply-event-name="template-quill-variable-insert-request"
                />
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
                <JetButton variant="primary" @click="convertTextToImageInTemplateItems">
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
                <span class="text-[#15919A] font-bold">Insert image URLs</span>
            </template>
            <template #content>
                <div class="space-y-3">
                    <JetLabel value="Image URLs (one per line)" />
                    <textarea
                        v-model="imageUrlsText"
                        placeholder="https://example.com/image.png"
                        class="mt-1 w-full min-h-[140px] text-sm border border-gray-300 rounded-md focus:ring-[#15919A] focus:border-[#15919A]"
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
    </div>
</template>

<style scoped>


.rotate-180 {
    transform: rotate(180deg);
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
    left: 0px;
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
    background: #15919a;
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

::deep(.ql-variable) {
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


.prism-item-editor {
    min-height: 38px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    overflow: hidden;
}

.prism-item-editor :deep(.prism-editor__container) {
    min-height: 38px;
    font-size: 0.875rem;
}
</style>
