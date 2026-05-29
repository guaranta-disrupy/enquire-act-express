<script setup>
// Import CK Editor Components
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import { component as CKEditor } from "@ckeditor/ckeditor5-vue";
// import Font from '@ckeditor/ckeditor5-font/src/font';
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
    nextTick,
    computed,
} from "vue";
import { useTestsStore } from "@/stores/tests";
import JetButton from "@/Componentsv2/Button.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import Helpers from "@/Mixins/Helpers";
import TestCriteria from "./TestCriteria.vue";
import Toggle from "@/Jetstream/Toggle.vue";
import TestAdvanceSkiplogic from "./TestAdvanceSkiplogic.vue";

// import QuillEditor from '@/Extra/Quill.vue'
// import 'quill/dist/quill.snow.css';
// const props = defineProps(['survey']);

const { $testState, assign, toast } = Helpers();
const dataStore = useTestsStore();

const enableCKEditor = inject("ck");
const editor = ref(ClassicEditor);

const props = defineProps(["survey", "tests"]);
const form = ref(props.tests || { targets: [], primes: [], config: {} });

// Keep local reference in sync without deep reactive loops
watch(
    () => props.tests,
    (newVal) => {
    if (newVal && newVal !== form.value) {
        form.value = newVal;
    }
    },
    { immediate: true }
);

const editorConfig = ref({
    extraPlugins: [UploadAdapterPlugin],
});

onBeforeMount(() => {
    $testState.criteria = form.value;
});

provide("testState", $testState);

const highlighter = (code) => {
	return highlight(code, languages.markup);
};

const macroToBeCreatedOrUpdated = ref(false);
const selectedMacro = ref(false);

const macro = ref({
    placeholder: null,
    formula: "",
    type: "tabular",
});

const selectedDisplayStyle = ref("rounded"); // Default to rounded targets

const selectDisplayStyle = (style) => {
    selectedDisplayStyle.value = style;
    form.value.config.display_style = style;
};

const saveMacro = async () => {
    // Ensure analysis_options exists (local display)
    if (!form.value.config.analysis_options) {
        form.value.config.analysis_options = [];
    }

    const macroPayload = assign(macro.value);
    
    if (selectedMacro.value === false) {
        // create new analysis option (store-only to avoid double push)
        dataStore.addAnalysisOption(macroPayload);
    } else {
        // update existing analysis option by index (store-only)
        dataStore.updateAnalysisOption(selectedMacro.value, macroPayload);
    }

    // Persist to backend so the analysis option is saved
    try {
        await dataStore.saveTest();
    } catch (e) {
        // noop; saving is best-effort here
    }

    macroToBeCreatedOrUpdated.value = false;
    selectedMacro.value = false;
    resetMacroForm();
    toast.success("Analysis option saved successfully");
};

const targetOptions = computed(() => {
    const items = form.value?.targets || [];
    return items.map((t, i) => {
        const label = t?.target || `Target ${t?.index ?? i + 1}`;
        const value = Number(t?.value ?? t?.index ?? i + 1);
        const equivalent = t?.equivalent || "";
        return { label, value, equivalent };
    });
});

const selectedTargetValues = ref([]);
const isTargetDropdownOpen = ref(false);

const updateFormulaFromSelection = () => {
    const parts = (selectedTargetValues.value || [])
        .slice()
        .sort((a, b) => Number(a) - Number(b))
        .map((v) => `t${Number(v)}`)
        .join(" + ");
    macro.value.formula = parts;
};

const syncSelectionFromFormula = () => {
    const formula = String(macro.value.formula || "");
    const matches = Array.from(formula.matchAll(/t(\d+)/g)).map((m) =>
        Number(m[1])
    );
    selectedTargetValues.value = matches;
};

const removeSelectedValue = (val) => {
    const next = (selectedTargetValues.value || []).filter(
        (v) => Number(v) !== Number(val)
    );
    selectedTargetValues.value = next;
    updateFormulaFromSelection();
};

const resetMacroForm = () => {
    macro.value = {
        placeholder: null,
        formula: "",
        type: "tabular",
    };
    selectedTargetValues.value = [];
    isTargetDropdownOpen.value = false;
};

const toggleTargetValue = (val) => {
    const exists = (selectedTargetValues.value || []).some(
        (v) => Number(v) === Number(val)
    );
    if (exists) {
        selectedTargetValues.value = selectedTargetValues.value.filter(
            (v) => Number(v) !== Number(val)
        );
    } else {
        selectedTargetValues.value = [
            ...selectedTargetValues.value,
            Number(val),
        ];
    }
    updateFormulaFromSelection();
};

const editMacro = (key) => {
    selectedMacro.value = key;
    const src = (form.value.config.analysis_options || [])[key];
    if (!src) return;
    macroToBeCreatedOrUpdated.value = src;
    macro.value.placeholder = src.placeholder;
    macro.value.formula = src.formula;
    macro.value.type = src.type;
    syncSelectionFromFormula();
};

const openNewMacroModal = () => {
    resetMacroForm();
    macroToBeCreatedOrUpdated.value = true;
};

const deleteMacro = async (key) => {
    // delete from store analysis options by index and persist (store-only)
    // Show a confirmation modal before deleting
    if (
        window.confirm(
            "Are you sure you want to delete this analysis option? This action cannot be undone."
        )
    ) {
        dataStore.deleteAnalysisOption(key);
        try {
            await dataStore.saveTest();
            toast.success("Analysis option deleted successfully");
        } catch (e) {
            toast.error("Failed to delete analysis option");
        }
    } else {
    }
};

const handlePrimeAsNextChange = (value) => {
    if (value === true) {
        form.value.config.auto_next = false;
    } else {
        form.value.config.auto_next = true;
    }
};

const handleAutoNextChange = (value) => {
    if (value === true) {
        form.value.config.prime_as_next = false;
    } else {
        form.value.config.prime_as_next = true;
    }
};
</script>

<template>
    <div
        class="bg-[#15919A] text-white shadow p-4 mb-2 w-[100vh] text-center uppercase"
    >
		Advance
	</div>

    <div
        class="flex flex-col gap-2 overflow-y-auto overflow-x-hidden c p-4 w-full max-w-5xl mx-auto"
    >
        <div class="col-span-8 px-3">
            <div class="w-full mx-auto bg-white mt-3 rounded-xl p-4 border">
                <div class="flex items-center justify-between">
                    <div class="text-base font-semibold">Response recoding</div>
                    <JetButton
                        type="button"
                        variant="primary-dark"
                        size="sm"
                        @click.prevent="openNewMacroModal"
                    >
                        Add analysis option
                    </JetButton>
                </div>
                
                <!-- details of analysis options -->
                <div v-if="form.config.analysis_options.length == 0">
                    <div class="text-gray-500 text-sm space-y-2 mt-5 mb-2">
                        <p>
                            <strong>Analysis options</strong> allow you to
                            define custom ways to process and interpret the
                            responses collected from your survey. These options
                            are especially useful for generating computed
                            variables, recoding answers, or preparing your data
                            for further statistical analysis.
                        </p>
                </div>
                </div>

                <div
                    class="max-h-[50vh] overflow-y-auto"
                    :class="
                        form.config.analysis_options.length > 0
                            ? 'mt-4 mb-1'
                            : ''
                    "
                >
                    <div
                        v-for="(mc, k) in form.config.analysis_options || []"
                        :key="k"
                        class="mt-2 hover:bg-slate-200/90 rounded-xl bg-slate-100 px-4 py-4 flex items-center text-xs transition-all"
                    >
                        <div class="w-2/12 font-bold text-gray-800">
                            {{ mc.placeholder }}
                        </div>
                        <div class="w-5/12 font-mono text-gray-700">
                            {{ mc.formula }}
                        </div>
                        <div class="w-3/12">
                            <div
                                class="flex items-center px-3 py-1 text-gray-600 w-fit mx-auto"
                            >
                                <span class="text-xs font-semibold">
                                    {{
                                        mc.type === "tabular"
                                            ? "Tabular"
                                            : "Respondent"
                                    }}
                                    </span>
                            </div>
                        </div>
                        <div class="w-2/12 flex items-center gap-2 justify-end">
                            <JetButton
                                variant="gray-100"
                                size="xs"
                                @click.prevent="editMacro(k)"
                            >
                                Update
                            </JetButton>
                            <JetButton
                                variant="gray-100"
                                class="text-red-500"
                                size="xs"
                                @click.prevent="deleteMacro(k)"
                            >
                                Delete
                            </JetButton>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        <!-- </template> -->

        <div class="col-span-8 px-3">
            <TestAdvanceSkiplogic :form="form" />
        </div>
    </div>

    <JetDialogModal
        :show="macroToBeCreatedOrUpdated"
        @close="macroToBeCreatedOrUpdated = false"
    >
        <template #title> Add analysis option </template>

        	<template #content>
            <div class="modal-scroll h-[60vmin]">
        		<div class="flex gap-2">
                    <div class="p-2 w-full">
                        <CustomInput
                            class="text-xs"
                            type="text"
                            label="Placeholder"
                            v-model="macro.placeholder"
                        />
                    </div>
                </div>
                <div class="flex gap-2 mb-4">
                    <div class="p-2 w-full">
                        <JetLabel value="Applied to" />
                        <select
                            v-model="macro.type"
                            class="w-full mt-1 px-2 py-1 border border-gray-300 rounded-md shadow-xs focus:ring-[#15919A] focus:border-[#15919A] text-sm dark:text-gray-700 text-base"
                        >
                            <option value="tabular">Tabular</option>
                            <option value="respondent">Respondent Level</option>
                        </select>
                    </div>
                </div>
                <div class="gap-2">
                    <div class="p-2 w-full">
                        <div class="">
                            <p class="text-gray-500 text-sm">
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
                        <PrismEditor
                            class="my-editor mt-3"
                            v-model="macro.formula"
                            :highlight="highlighter"
                            line-numbers
                        />
                    </div>
                    <div class="mt-8 mx-2">
                        <div class="mb-2 h-5 flex items-center justify-between">
                            <JetLabel value="Quick add from targets" />
                            <div
                                class="flex flex-wrap gap-2 mt-2"
                                v-if="
                                    selectedTargetValues &&
                                    selectedTargetValues.length
                                "
                            >
                                <span
                                    v-for="val in selectedTargetValues"
                                    :key="`sel-${val}`"
                                    class="chip text-sm"
                                >
                                    t{{ Number(val) }}
                                    <button
                                        class="chip-x"
                                        @click.prevent="
                                            removeSelectedValue(val)
                                        "
                                    >
                                        ×
                                    </button>
                                </span>
                            </div>
                        </div>
                        <div class="mt-1 relative">
                            <div
                                v-show="true"
                                class="absolute z-50 mt-1 w-full h-56 overflow-auto bg-white border border-gray-200 rounded-md"
                            >
                                <ul class="text-xs p-2">
                                    <li
                                        v-for="opt in targetOptions"
                                        :key="opt.value"
                                        class="px-3 py-1 rounded-full mb-1 flex items-center justify-between hover:bg-gray-100 cursor-pointer"
                                        @click="toggleTargetValue(opt.value)"
                                        :class="{
                                            'bg-[#c2e7e9] hover:bg-[#c2e7e9!important]':
                                                selectedTargetValues.includes(
                                                    opt.value
                                                ),
                                        }"
                                    >
                                        <span class="text-sm"
                                            >{{ opt.value }} -
                                            {{ opt.equivalent }}</span
                                        >
                                    </li>
                                </ul>
                            </div>
                        </div>
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
</template>

<style scoped="true">
    .c {
        max-height: calc(100vh - 180px);
    }

.modal-scroll {
    max-height: 70vh;
    overflow-y: auto;
}

.chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 2px 8px;
    border-radius: 9999px;
    background: #c2e7e9;
    color: #0f757c;
    font-size: 14px;
    line-height: 1.2;
}
.chip:hover {
    background: #a4dada;
}
.chip-x {
    background: transparent;
    border: none;
    color: #158088;
    cursor: pointer;
    font-weight: bold;
}
</style>
