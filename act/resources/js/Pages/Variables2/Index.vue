<script setup>
import Layout from "@/Layouts/Layout.vue";
import SmallButton from "../../Components/SmallButton.vue";
import ProjectDropdown from "@/Extra/ProjectDropdown.vue";
import JetButton from "@/Componentsv2/Button.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetInput from "@/Jetstream/Input.vue";
import JetLabel from "@/Jetstream/Label.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, onBeforeMount, computed, watch, nextTick } from "vue";
import { Link, useForm, Head } from "@inertiajs/vue3";
import ActionButton from "@/Components/ActionButton.vue";
import CreateCriteria from "./CreateCriteria.vue";
import Helpers from "../../Mixins/Helpers";
import Loader from "@/Components/Loader.vue";
import JetConfirmationModal from "@/Jetstream/ConfirmationModal.vue";
import EmptyPlaceholder from "@/Pages/PrimesTargets/EmptyPlaceholder.vue";

import { PrismEditor } from "vue-prism-editor";
import "vue-prism-editor/dist/prismeditor.min.css"; // import the styles somewhere
// import highlighting library (you can use any library you want just return html string)
import { highlight, languages } from "prismjs/components/prism-core";
import "prismjs/components/prism-clike";
import "prismjs/components/prism-javascript";
import "prismjs/components/prism-markup";
import "prismjs/components/prism-css";
import "prismjs/themes/prism-tomorrow.css"; // import syntax highlighting styles
import { setProxiedHtml } from "@/utils/setProxiedHtml";

import CustomSwitch from "@/Components/Switch.vue";

const enableQuillEditor = ref(true);

// Image URL modal state
const showImageUrlModal = ref(false);
const imageUrlsText = ref("");
const activeQuillEditor = ref(null);
const quillInstances = ref({});

import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.css";
import QuillContainer from "@/Components/QuillEditor.vue";
import {
    addImageInsertionToolbarButton,
    enableImageResize,
} from "@/Components/QuillEditor.vue";
import Quill from "quill";

const props = defineProps({
    project: { type: Object, default: () => ({}) },
    survey: { type: Object, default: () => ({}) },
    // When true (embedded in TestInstruction1 modal), enable the Apply Variable action
    enableApply: { type: Boolean, default: false },
    // Custom event name to dispatch when clicking "Apply Variable"
    applyEventName: { type: String, default: "variable-insert-request" },
});

const project = computed(() => ({
    ...props.project,
    variables: props.project?.variables ?? [],
}));

const { assign, toast, $varState } = Helpers();

const highlighter = (code) => {
    return highlight(code, languages.markup);
};

const AddVariableModal = ref(false);
const copyVariableModal = ref(false);
const deleteVariableModal = ref(false);
const deleteAllVariablesModal = ref(false);
const selectedCriteriaIndex = ref(-1);
const selected_action = ref("create");
const selected_variable = ref();

const form = useForm({
    name: "",
    config: {
        criteria: [],
    },
    criteria: {},
});

const showAddVariableModal = () => {
    selected_action.value = "create";
    form.reset();
    AddVariableModal.value = true;
};

const showEditVariableModal = (variable) => {
    selected_action.value = "update";
    selected_variable.value = assign(variable);
    form.name = selected_variable.value.name;
    form.config = selected_variable.value.config;
    form.criteria = {};
    $varState.create = form;
    AddVariableModal.value = true;
};

const addCriteria = () => {
    if (form.criteria.conditions != undefined) {
        if (selectedCriteriaIndex.value === -1) {
            form.config.criteria.push(assign(form.criteria));
        } else {
            form.config.criteria[selectedCriteriaIndex.value] = assign(
                form.criteria
            );
        }
        resetCriteria();
    } else {
        form.criteria = {
            label: "",
            value: "",
            conditions: [],
            equivalent: "",
        };
    }
};

const resetCriteria = () => {
    form.criteria = {};
    selectedCriteriaIndex.value = -1;
};

const addSegment = () => {
    $varState.addSegment();
};

onBeforeMount(() => {
    $varState.create = form;
});

// Watch for enableQuillEditor changes to initialize/destroy Quill
watch(enableQuillEditor, (newValue) => {
    if (newValue) {
        // Initialize Quill when editor is enabled
        nextTick(() => {
            setTimeout(() => {
                if (form.criteria?.conditions != undefined) {
                    initializeQuill(
                        "criteria-value-quill",
                        form.criteria.value
                    );
                }
            }, 200);
        });
    } else {
        // Clean up Quill instances when editor is disabled
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
    }
});

// Watch for criteria form changes to initialize Quill when criteria is being edited
watch(
    () => form.criteria?.conditions,
    (newVal) => {
        if (newVal !== undefined && enableQuillEditor.value) {
            nextTick(() => {
                setTimeout(() => {
                    initializeQuill(
                        "criteria-value-quill",
                        form.criteria.value
                    );
                }, 200);
            });
        }
    },
    { immediate: true }
);

const editCriteria = (criterion, index) => {
    form.criteria = assign(criterion);
    selectedCriteriaIndex.value = index;
};

const copyCriteria = (criterion) => {
    if (criterion.conditions == "none") {
        return false;
    } else {
        let tmp_criterion = assign(criterion);
        tmp_criterion.label += " Copy";
        form.config.criteria.push(assign(tmp_criterion));
    }
};

const deleteCriteria = (index) => {
    if (form.config.criteria[index].conditions == "none") {
        return false;
    } else {
        form.config.criteria.splice(index, 1);
        resetCriteria();
    }
};

const executeAction = () => {
    if (selected_action.value == "create") {
        createVariable();
    } else if (selected_action.value == "update") {
        updateVariable();
    }
};

const createVariable = () => {
    form.post(route("variables.store", props.project), {
        preserveScroll: true,
        resetOnSuccess: true,
        errorBag: "createVariable",
        onSuccess: () => {
            toast.success("Variable successfully created");
            form.reset();
            AddVariableModal.value = false;
        },
    });
};

const updateVariable = () => {
    form.put(
        route("variables.update", [props.project, selected_variable.value]),
        {
            preserveScroll: true,
            resetOnSuccess: true,
            errorBag: "updateVariable",
            onSuccess: () => {
                toast.success("Variable successfully updated");
                form.reset();
                AddVariableModal.value = false;
            },
        }
    );
};

const copyVariableConfirmation = (variable) => {
    selected_variable.value = assign(variable);
    copyVariableModal.value = true;
};

const copyVariable = () => {
    form.get(
        route("variables.copy", [props.project, selected_variable.value]),
        {
            preserveScroll: true,
            resetOnSuccess: true,
            errorBag: "updateVariable",
            onSuccess: () => {
                toast.success("Variable successfully copied");
                form.reset();
                copyVariableModal.value = false;
            },
        }
    );
};

const deleteVariableConfirmation = (variable) => {
    selected_variable.value = assign(variable);
    deleteVariableModal.value = true;
};

const deleteVariable = () => {
    form.delete(
        route("variables.destroy", [props.project, selected_variable.value]),
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Variable successfully deleted");
                deleteVariableModal.value = false;
            },
        }
    );
};

// UI: expand/collapse like Templates list
const expandedVariables = ref(new Set());
function toggleExpandedVar(id) {
    const next = new Set(expandedVariables.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    expandedVariables.value = next;
}

// Delete All modal handlers
function confirmDeleteAll() {
    deleteAllVariablesModal.value = true;
}

async function deleteAllVariables() {
    try {
        const list = project.value?.variables || [];
        for (const v of list) {
            // Delete each variable
            // Using the same form instance sequentially
            // eslint-disable-next-line no-await-in-loop
            await form.delete(route("variables.destroy", [props.project, v]), {
                preserveScroll: true,
            });
        }
        toast.success("All variables deleted");
    } catch (e) {
        toast.error("Failed to delete all variables");
    } finally {
        deleteAllVariablesModal.value = false;
    }
}
function isExpandedVar(id) {
    return expandedVariables.value.has(id);
}

function getInitials(name) {
    if (!name) return "";
    const parts = name.trim().split(" ");
    const first = parts[0]?.[0] || "";
    const last = parts.length > 1 ? parts[parts.length - 1][0] : "";
    return (first + last).toUpperCase();
}

// Cross-component insert: ask TestInstruction1.vue to insert and close modal
function insertVariableFromIndex(variable) {
    try {
        const name = variable?.name || variable;
        window.dispatchEvent(
            new CustomEvent(props.applyEventName || "variable-insert-request", {
                detail: { name },
            })
        );
    } catch (_) {}
}

// Handle Quill content changes and remove <p> tags
function handleQuillContentChange(content) {
    // Remove <p> tags and their content, keeping only the inner text
    // Handle both single line and multiline <p> tags
    let cleanContent = content.replace(/<p[^>]*>(.*?)<\/p>/gi, "$1");

    // Also handle cases where content might be wrapped in <p> tags with attributes
    cleanContent = cleanContent.replace(/<p[^>]*>([\s\S]*?)<\/p>/gi, "$1");

    // Remove any remaining empty <p> tags
    cleanContent = cleanContent.replace(/<p[^>]*><\/p>/gi, "");

    // Trim whitespace
    cleanContent = cleanContent.trim();

    form.criteria.value = cleanContent;
}

// Initialize Quill instances for criteria value editor
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
                        toolbar: [
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
                        ],
                    },
                });

                quillInstances.value[editorId] = quill;

                // Add image insertion toolbar button
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

                // Enable image resize functionality
                try {
                    enableImageResize(quill);
                } catch (error) {
                    console.warn(
                        `Failed to enable image resize for ${editorId}:`,
                        error
                    );
                }

                // Set initial content
                if (content) {
                    quill.root.innerHTML = content;
                }

                // Watch for content changes
                quill.on("text-change", () => {
                    const html = quill.root.innerHTML;
                    // Remove <p> tags when content changes
                    let cleanContent = html.replace(
                        /<p[^>]*>(.*?)<\/p>/gi,
                        "$1"
                    );
                    cleanContent = cleanContent.replace(
                        /<p[^>]*>([\s\S]*?)<\/p>/gi,
                        "$1"
                    );
                    cleanContent = cleanContent.replace(/<p[^>]*><\/p>/gi, "");
                    cleanContent = cleanContent.trim();

                    form.criteria.value = cleanContent;
                });

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
                    finalUrl = `/gallery-proxy?u=${encodeURIComponent(u.href)}`;
                }
            } catch (_) {}

            quill.insertEmbed(insertIndex, "image", finalUrl, "user");
            insertIndex += 1;
            // Add a space after each image to keep them inline
            quill.insertText(insertIndex, " ", "user");
            insertIndex += 1;
        });

        quill.setSelection(insertIndex, 0, "user");

        // Update form data after insertion
        const html = quill.root.innerHTML;
        let cleanContent = html.replace(/<p[^>]*>(.*?)<\/p>/gi, "$1");
        cleanContent = cleanContent.replace(/<p[^>]*>([\s\S]*?)<\/p>/gi, "$1");
        cleanContent = cleanContent.replace(/<p[^>]*><\/p>/gi, "");
        cleanContent = cleanContent.trim();

        form.criteria.value = cleanContent;
    } catch (_) {}
    showImageUrlModal.value = false;
    imageUrlsText.value = "";
};
</script>
<template>
    <Head :title="survey.name" />
    <div
        class="text-[#323232]"
        :class="enableApply ? 'bg-transparent' : 'bg-slate-200 min-h-[100vh]'"
    >
        <!-- <div class="flex flex-col max-w-7xl mx-auto"> -->
        <div class="flex flex-col max-w-full mx-auto">
            <!-- Headers Parent - Only show when not in modal context -->
            <div
                v-if="!enableApply"
                class="flex flex-col sticky top-0 bg-slate-200"
            >
                <!-- Header Start -->
                <!-- <div class="flex items-center justify-between py-2"> -->
                <div
                    class="flex items-center bg-white justify-between h-[70px] shadow-md p-4 z-100"
                >
                    <div class="flex items-center px-1 gap-4">
                        <!-- <div>
                            <a :href="route('redirect.fluent', survey.config.survey_id)">
                                <Button class="bg-[#15919A] text-white rounded-full text-[16px] hover:bg-[#0C7C84] w-[200px] h-[44px] justify-center items-center flex gap-2" size="sm" shadow="2xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15m0 0l6.75 6.75M4.5 12l6.75-6.75" />
                                    </svg>
                                    Back to Fluent
                                </Button>
                            </a>
                        </div> -->
                        <!-- <div>
                            <Link class="mr-1 inline-flex items-center text-sm text-sky-500" :href="route('survey2.index', survey)">
                                <Button class="bg-[#15919A] text-white rounded-full text-[16px] hover:bg-[#0C7C84] w-[200px] h-[44px] justify-center items-center flex gap-2" size="sm" shadow="2xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15m0 0l6.75 6.75M4.5 12l6.75-6.75" />
                                    </svg>
                                    Back to survey
                                </Button>
                            </Link>
                        </div> -->

                        <div
                            class="active:scale-[0.85] transition-all duration-200"
                        >
                            <Link
                                :href="route('survey2.index', survey)"
                                class=""
                            >
                                <svg
                                    data-v-8cc99868=""
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    class="w-[60px] hover:stroke-[#15919A] transition-colors duration-200"
                                >
                                    <path
                                        data-v-8cc99868=""
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m11.25 9-3 3m0 0 3 3m-3-3h7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                    ></path>
                                </svg>
                            </Link>
                        </div>

                        <div
                            class="text-[24px] rounded-lg w-auto h-[40px] justify-center items-center flex font-bold text-[#323232]"
                        >
                            {{ props.project.name }} / Variables
                        </div>
                    </div>

                    <!-- <div class="text-lg tracking-wide">
                        Logged in as {{ $page.props.auth.user.name }}
                    </div> -->

                    <div
                        class="w-[50px] h-[50px] flex items-center justify-center rounded-full bg-[#15919A30] text-[#15919A] text-[20px] cursor-default"
                        :title="$page.props.auth.user.name"
                    >
                        {{ getInitials($page.props.auth.user.name) }}
                    </div>
                </div>
                <!-- Header End -->
            </div>
            <!-- Headers Parent end-->

            <!-- Container -->
            <div
                class="flex flex-col mx-auto bg-white"
                :class="
                    enableApply
                        ? 'w-full mt-0 shadow-none rounded-none'
                        : 'w-[110vh] min-h-[85vmin] mt-5 shadow-lg rounded-xl'
                "
            >
                <div
                    class=" px-4 w-full"
                    :class="
                        enableApply
                            ? 'max-h-[85vh]'
                            : 'max-h-[calc(100vh-200px)]'
                    "
                >
                    <!-- Tabs -->
                    <div class="bg-white border-b border-gray-200">
                        <nav class="-mb-px flex items-center justify-between">
                            <div class="flex">
                                <button
                                    class="px-4"
                                    :class="[
                                        1 === 1
                                            ? 'border-[#15919A] text-[#15919A]'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-md',
                                    ]"
                                >
                                    Variables
                                    <span
                                        :class="[
                                            1 === 1
                                                ? 'bg-[#15919A] text-white'
                                                : 'bg-gray-100 text-gray-900',
                                            'ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium',
                                        ]"
                                    >
                                        {{ project?.variables?.length || 0 }}
                                    </span>
                                </button>
                            </div>
                            <div
                                class="flex items-center space-x-2"
                                v-if="project?.variables?.length > 0"
                            >
                                <JetButton
                                    size="sm"
                                    variant="primary"
                                    @click="showAddVariableModal"
                                    >Add New Variable</JetButton
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


                <div class="overflow-y-auto max-h-[77vh]">
                    <!-- Empty State -->
                    <div
                        v-if="
                            !project?.variables ||
                            project?.variables.length === 0
                        "
                        class="flex flex-col h-[50vmin] items-center justify-center align-items-center mt-8 mb-8"
                    >
                        <EmptyPlaceholder
                            label="Variable"
                            @create="showAddVariableModal"
                        />
                    </div>

                    <template
                        v-for="(variable, index) in project?.variables.sort(
                            (a, b) => b.id - a.id
                        ) || []"
                        :key="variable.id || index"
                    >
                        <div
                            class="mt-3 hover:bg-slate-200/90 rounded-xl transform transition-all w-full bg-slate-100"
                        >
                            <!-- Row Header -->
                            <div class="px-6 py-4 w-full">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div>
                                            <div
                                                class="flex items-center space-x-2 cursor-pointer select-none"
                                                @click="
                                                    toggleExpandedVar(
                                                        variable.id || index
                                                    )
                                                "
                                            >
                                                <svg
                                                    :class="{
                                                        'rotate-180':
                                                            isExpandedVar(
                                                                variable.id ||
                                                                    index
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
                                                    {{ variable.name }}
                                                </h3>
                                            </div>
                                            <p
                                                class="text-[0.7rem] text-gray-500 ml-6"
                                            >
                                                {{
                                                    variable?.config?.criteria
                                                        ?.length || 0
                                                }}
                                                items
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <JetButton
                                            size="xs"
                                            variant="primary-light"
                                            class="font-medium"
                                            :disabled="!props.enableApply"
                                            :class="{
                                                'opacity-50 cursor-not-allowed pointer-events-none':
                                                    !props.enableApply,
                                            }"
                                            @click="
                                                props.enableApply &&
                                                    insertVariableFromIndex(
                                                        variable
                                                    )
                                            "
                                        >
                                            Apply Variable
                                        </JetButton>
                                        <JetButton
                                            size="xs"
                                            variant="primary-light"
                                            class="font-medium"
                                            @click="
                                                copyVariableConfirmation(
                                                    variable
                                                )
                                            "
                                        >
                                            Duplicate
                                        </JetButton>
                                        <JetButton
                                            size="xs"
                                            variant="primary-light"
                                            class="font-medium"
                                            @click="
                                                showEditVariableModal(variable)
                                            "
                                        >
                                            Update
                                        </JetButton>
                                        <JetButton
                                            size="xs"
                                            variant="danger-light"
                                            class="font-medium"
                                            @click="
                                                deleteVariableConfirmation(
                                                    variable
                                                )
                                            "
                                        >
                                            Delete
                                        </JetButton>
                                    </div>
                                </div>
                            </div>

                            <!-- Collapsible Content -->
                            <div
                                v-if="isExpandedVar(variable.id || index)"
                                class=""
                            >
                                <div class="overflow-x-auto">
                                    <table
                                        class="min-w-full divide-y divide-gray-200 table-fixed border border-t-0"
                                    >
                                        <thead class="bg-white">
                                            <tr>
                                                <th
                                                    class="pl-7 px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8"
                                                >
                                                    #
                                                </th>
                                                <th
                                                    class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3"
                                                >
                                                    Criteria
                                                </th>
                                                <th
                                                    class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                >
                                                    Condition
                                                </th>
                                                <th
                                                    class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3"
                                                >
                                                    Value
                                                </th>
                                                <th
                                                    class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3"
                                                >
                                                    Equivalent
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="bg-white divide-y divide-gray-200"
                                        >
                                            <tr
                                                v-for="(cr, key2) in variable
                                                    .config.criteria"
                                                :key="key2"
                                                class="hover:bg-gray-50"
                                            >
                                                <td
                                                    class="pl-7 px-3 py-2 whitespace-nowrap text-sm text-gray-500"
                                                >
                                                    {{ key2 + 1 }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-sm text-gray-900 whitespace-normal break-words max-w-[420px]"
                                                >
                                                    {{ cr.label }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-sm text-gray-900"
                                                >
                                                    {{
                                                        $varState.criteriaToString(
                                                            cr.conditions
                                                        )
                                                    }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-sm text-gray-900 w-1/3 text-center align-middle"
                                                >
                                                    <div
                                                        class="variable_value text-xs w-full h-full grid place-items-center"
                                                        v-html="
                                                            setProxiedHtml(
                                                                cr.value
                                                            )
                                                        "
                                                    ></div>
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-sm text-gray-900 w-1/3 whitespace-normal break-words"
                                                >
                                                    {{ cr.equivalent }}
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
            <!-- Container end -->
        </div>

        <JetDialogModal :show="AddVariableModal" maxWidth="5xl">
            <template #title> Project Variable </template>
            <template #content>
                <div class="grid grid-cols-6 gap-4">
                    <div class="col-span-6">
                        <JetLabel value="Variable Name" />
                        <JetInput
                            v-model="form.name"
                            type="text"
                            class="w-full"
                            placeholder="Variable Name"
                        />
                        <JetInputError
                            :message="form.errors.name"
                            class="mt-2"
                        />
                    </div>
                    <div class="col-span-6 max-h-[60vh] overflow-y-auto">
                        <table
                            class="min-w-full divide-y divide-gray-200 table-fixed"
                        >
                            <thead class="bg-white">
                                <tr>
                                    <th
                                        class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase"
                                    >
                                        Criteria
                                    </th>
                                    <th
                                        class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase"
                                    >
                                        Condition
                                    </th>
                                    <th
                                        class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase"
                                    >
                                        Value
                                    </th>
                                    <th
                                        class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase"
                                    >
                                        Equivalent
                                    </th>
                                    <th
                                        class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase"
                                    >
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(cr, key2) in form.config.criteria">
                                    <td class="pl-6">
                                        {{ cr.label }}
                                    </td>
                                    <td class="pl-6 text-xs">
                                        {{
                                            $varState.criteriaToString(
                                                cr.conditions
                                            )
                                        }}
                                    </td>
                                    <td class="pl-6">
                                        <div
                                            class="variable_value"
                                            v-html="setProxiedHtml(cr.value)"
                                        ></div>
                                    </td>
                                    <td class="pl-6">
                                        {{ cr.equivalent }}
                                    </td>
                                    <td class="pl-6">
                                        <button
                                            class="mr-2 cursor-pointer text-xs text-blue-400 underline focus:outline-none"
                                            @click="editCriteria(cr, key2)"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            @click="copyCriteria(cr)"
                                            class="mr-2 cursor-pointer text-xs text-blue-400 underline focus:outline-none"
                                        >
                                            Duplicate
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteCriteria(key2)"
                                            class="text-xs focus:outline-none cursor-pointer text-red-500 underline"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <JetInputError
                            :message="form.errors['config.criteria']"
                            class="mt-2"
                        />
                    </div>
                    <div class="col-span-6 flex">
                        <JetButton
                            size="xs"
                            @click="addCriteria"
                            variant="slate"
                        >
                            <span v-if="form.criteria?.conditions != undefined">
                                Save Criteria
                            </span>
                            <span v-else> Add Criteria </span>
                        </JetButton>
                        <JetButton
                            variant="slate"
                            class="ml-1"
                            size="xs"
                            v-if="form.criteria?.conditions != undefined"
                            @click="resetCriteria"
                        >
                            Cancel
                        </JetButton>
                    </div>
                    <template v-if="form.criteria?.conditions != undefined">
                        <div class="col-span-6">
                            <JetLabel value="Criteria Label" />
                            <JetInput
                                type="text"
                                class="w-full"
                                v-model="form.criteria.label"
                                placeholder="Enter criteria label"
                            />
                        </div>
                        <div class="col-span-6">
                            <CustomSwitch v-model:checked="enableQuillEditor">
                                Editor
                            </CustomSwitch>
                            <JetLabel value="Criteria Value" />

                            <div v-if="enableQuillEditor">
                                <QuillContainer
                                    editorId="criteria-value-quill"
                                    :height="'300px'"
                                    aria-label="Criteria Value Editor"
                                    title="Criteria Value Editor"
                                />
                            </div>
                            <prism-editor
                                v-else
                                class="my-editor editor-height"
                                v-model="form.criteria.value"
                                :highlight="highlighter"
                                line-numbers
                            ></prism-editor>
                            <!-- <textarea v-else class="block  w-full form-input" v-model="form.criteria.value" rows="3"></textarea> -->
                        </div>
                        <div class="col-span-6">
                            <JetLabel value="Criteria Equivalent" />
                            <textarea
                                class="rounded-lg border-gray-400 resize-y mt-1 block w-full text-xs"
                                v-model="form.criteria.equivalent"
                                rows="2"
                            ></textarea>
                        </div>
                        <template v-if="form.criteria.conditions != 'none'">
                            <div class="col-span-6">
                                <JetButton
                                    @click="addSegment"
                                    variant="slate"
                                    size="xs"
                                >
                                    <span
                                        v-if="
                                            form.criteria.conditions.length > 0
                                        "
                                        >Add Segment</span
                                    >
                                    <span v-else>Add Condition</span>
                                </JetButton>
                            </div>
                            <div
                                class="col-span-6"
                                v-for="(condition, index) in form.criteria
                                    .conditions"
                                :key="index"
                            >
                                <CreateCriteria
                                    :deep="0"
                                    :condition="condition"
                                    :indexes="[index]"
                                />
                            </div>
                        </template>
                    </template>
                </div>
            </template>
            <template #footer>
                <JetButton
                    variant="default"
                    @click="AddVariableModal = false"
                    class="uppercase"
                    size="sm"
                >
                    nevermind
                </JetButton>
                <jet-button
                    class="ml-2 uppercase"
                    @click="executeAction"
                    variant="primary"
                    size="sm"
                >
                    <span v-if="selected_action == 'create'">
                        create variable
                    </span>
                    <span v-else> save changes </span>
                </jet-button>
            </template>
        </JetDialogModal>
        <JetConfirmationModal
            :show="deleteAllVariablesModal"
            @close="deleteAllVariablesModal = false"
            @confirmed="deleteAllVariables"
        >
            <template #title> Delete All Variables </template>
            <template #content>
                Are you sure you want to delete all variables? This action
                cannot be undone.
            </template>
            <template #footer>
                <JetButton
                    variant="default"
                    @click="deleteAllVariablesModal = false"
                    class="mr-1"
                    >Cancel</JetButton
                >
                <JetButton variant="danger" @click="deleteAllVariables"
                    >Delete All</JetButton
                >
            </template>
        </JetConfirmationModal>
        <JetConfirmationModal :show="copyVariableModal">
            <template #title> Duplicate Variable </template>
            <template #content>
                Are you sure you want to duplicate
                <strong>{{ selected_variable.name }}</strong
                >?
            </template>
            <template #footer>
                <JetButton
                    variant="default"
                    @click="copyVariableModal = false"
                    class="mr-1"
                >
                    NEVERMIND
                </JetButton>
                <JetButton @click="copyVariable" variant="primary">
                    DUPLICATE
                </JetButton>
            </template>
        </JetConfirmationModal>
        <JetConfirmationModal :show="deleteVariableModal">
            <template #title> Delete Variable </template>
            <template #content>
                Are you sure you want to delete
                <strong>{{ selected_variable.name }}</strong
                >?
            </template>
            <template #footer>
                <JetButton
                    variant="default"
                    @click="deleteVariableModal = false"
                    class="mr-1"
                >
                    Nevermind
                </JetButton>
                <JetButton @click="deleteVariable" variant="danger">
                    Delete Variable
                </JetButton>
            </template>
        </JetConfirmationModal>

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
                        placeholder="https://example.com/a.jpg\nhttps://example.com/b.png"
                        class="mt-1 w-full min-h-[140px] text-sm border border-gray-300 rounded-md focus:ring-[#15919A] focus:border-[#15919A]"
                    ></textarea>
                    <p class="text-xs text-gray-500">
                        Each non-empty line will be inserted as an image on its
                        own line.
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
                >
                    Cancel
                </JetButton>
                <JetButton variant="primary" @click="insertImagesFromModal">
                    Insert
                </JetButton>
            </template>
        </JetDialogModal>
    </div>
</template>
<style scoped>
.variable_value :deep() img {
    max-width: 10% !important;
    height: auto;
}
.variable_value {
    display: inline-block;
    text-align: left;
}

.c {
    height: calc(100vh - 120px);
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
    max-width: 50px;
    max-height: 50px;
    margin: 0 4px;
    vertical-align: middle;
    border-radius: 4px;
}
</style>
