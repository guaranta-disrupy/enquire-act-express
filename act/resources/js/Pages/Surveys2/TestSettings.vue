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
import Tooltip from "./Tooltip.vue";

// import QuillEditor from '@/Extra/Quill.vue'
// import 'quill/dist/quill.snow.css';
// const props = defineProps(['survey']);

const { $testState, assign } = Helpers();
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

// watch the form.config to mark the data as dirty
watch(
    () => form.value.config,
    (newVal, oldVal) => {
        dataStore.isDirty = true;
    },
    { deep: true }
);

const editorConfig = ref({
    extraPlugins: [UploadAdapterPlugin],
});

const addSegment = () => {
    // $state.criteria = assign(form);
    return $testState.addSegment();
};

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

const themeValue = computed(() => {
    const candidates = [
        form.value?.config2?.theme,
        form.value?.config?.theme,
        props.tests?.config2?.theme,
        props.tests?.config?.theme,
        props.survey?.config2?.theme,
        props.survey?.config?.theme,
        props.survey?.theme,
    ];

    const value = candidates.find(
        (candidate) =>
            candidate !== undefined && candidate !== null && candidate !== ""
    );

    return value === undefined ? null : Number(value);
});

const isThemeTwo = computed(() => themeValue.value === 2);

const handleDisplayByChange = (value) => {
    const numValue = parseInt(value, 10);
    if (!Number.isNaN(numValue)) {
        form.value.config.display_by = numValue;
    }
};

const MIN_TARGET_DELAY = 300;

const enforceMinimumTargetDelay = () => {
    const numericValue = Number(form.value.config.target_delay);
    if (Number.isNaN(numericValue) || numericValue < MIN_TARGET_DELAY) {
        form.value.config.target_delay = MIN_TARGET_DELAY;
    }
};

const saveMacro = () => {
    // Ensure macros array exists
    if (!form.value.config.macros) {
        form.value.config.macros = [];
    }

    if (selectedMacro.value === false) {
        form.value.config.macros.push(assign(macro.value));
    } else {
        form.value.config.macros[selectedMacro.value] = assign(macro.value);
    }
    macroToBeCreatedOrUpdated.value = false;
    selectedMacro.value = false;
};

const editMacro = (key) => {
    selectedMacro.value = key;
    macroToBeCreatedOrUpdated.value = form.value.config.macros[key];
    macro.value.placeholder = macroToBeCreatedOrUpdated.value.placeholder;
    macro.value.formula = macroToBeCreatedOrUpdated.value.formula;
    macro.value.type = macroToBeCreatedOrUpdated.value.type;
};

const deleteMacro = (key) => {
    if (form.value.config.macros && form.value.config.macros.length > key) {
        form.value.config.macros.splice(key, 1);
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

const handlePracticeTrialChange = (value) => {
    if (value === true) {
        form.value.config.main_trial = false;
    } else {
        form.value.config.main_trial = true;
    }
};

const handleMainTrialChange = (value) => {
    if (value === true) {
        form.value.config.practice_trial = false;
    } else {
        form.value.config.practice_trial = true;
    }
};

const handleTrialUnselectChange = (value) => {
    if (value === true) {
        form.value.config.default_trial_unselect = false;
    }
};

const handleDefaultTrialUnselectChange = (value) => {
    if (value === true) {
        form.value.config.trial_unselect = false;
    }
};
</script>

<template>
    <div
        class="bg-[#257556] text-white shadow p-4 mb-2 w-[80vh] text-center uppercase"
    >
        Settings
    </div>

    <div
        class="flex flex-col gap-2 overflow-y-auto overflow-x-hidden c px-4 w-[80vh] custom-scrollbar"
    >
        <!-- <div class="col-span-8">
                <jet-label class="">
                    <div class="flex items-center">
                        <jet-checkbox v-model:checked="form.config.main_trial" />

                        <div class="ml-2">
                            Main trial
                        </div>
                    </div>
                </jet-label>
                <jet-label class="mt-3">
                    <div class="flex items-center">
                        <jet-checkbox v-model:checked="form.config.random" />

                        <div class="ml-2">
                            Randomize targets
                        </div>
                    </div>
                </jet-label>
                <jet-label class="mt-3">
                    <div class="flex items-center">
                        <jet-checkbox v-model:checked="form.config.random_prime" />

                        <div class="ml-2">
                            Randomize primes
                        </div>
                    </div>
                </jet-label>
                <jet-label class="mt-3">
                    <div class="flex items-center">
                        <jet-checkbox v-model:checked="form.config.random_prime_subsets" :disabled="!form.config.random_prime" />
                        <div class="ml-2">
                            Randomize prime subsets
                        </div>
                    </div>
                </jet-label>
                <jet-label class="mt-3">
                    <div class="flex items-center">
                        <jet-checkbox v-model:checked="form.config.prime_as_next" />

                        <div class="ml-2">
                            Prime as next button
                        </div>
                    </div>
                </jet-label>
                <jet-label class="mt-3">
                    <div class="flex items-center">
                        <jet-checkbox v-model:checked="form.config.single_select" />

                        <div class="ml-2">
                            Single select
                        </div>
                    </div>
                </jet-label>
                <jet-label class="mt-3">
                    <div class="flex items-center">
                        <jet-checkbox v-model:checked="form.config.auto_next" />

                        <div class="ml-2">
                            Auto next trial
                        </div>
                    </div>
                </jet-label>
                <div class="mt-6 w-1/2">
                    <CustomInput label="Empty selection text" v-model="form.config.empty_selection" />
                </div>
                
                <div class="w-1/3">
                    <CustomInput type="number" label="Selection limit (0 = can select all)" v-model="form.config.limit" />
                </div>


                <div class="w-1/3">
                    <CustomInput  type="number" label="Display targets by" v-model="form.config.display_by" />
                </div>

            </div>
            <div class="col-span-8 hidden">
                <div class="mt-5 block">
                    Responses settings
                </div>
                <div class="w-1/4">
                    <jet-label class="mt-3">
                        <div class="flex items-center">
                            <jet-checkbox v-model:checked="form.config.download_as_values" />

                            <div class="ml-2">
                                Download responses as values
                            </div>
                        </div>
                    </jet-label>
                </div>
            </div>-->

        <!-- <div class="flex flex-row w-full">
    <div class=" w-full">
    <div class="flex flex-row gap-2 mb-2 items-center">
        <svg width="20" height="20" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g clip-path="url(#clip0_621_14998)">
            <path d="M41.1415 0H8.8584C3.96572 0 0 3.96582 0 8.85772V15.625H50V8.85772C50 3.96582 46.0342 0 41.1415 0Z" fill="#257556"/>
            <path d="M0 41.1423C0 46.0342 3.96572 50 8.8584 50H28.9062V21.0938H0V41.1423Z" fill="#257556"/>
            <path d="M34.375 50H41.1415C46.0342 50 50 46.0342 50 41.1423V21.0938H34.375V50Z" fill="#257556"/>
            </g>
            <defs>
            <clipPath id="clip0_621_14998">
            <rect width="50" height="50" fill="white"/>
            </clipPath>
            </defs>
            </svg>
        <h2 class="text-lg font-bold text-[#323232]"> 
            Select Display Layout
        </h2>
    </div>

        <div class="flex flex-row gap-2 w-full">
        <div  class="w-full px-4 py-2 border-2 cursor-pointer text-center justify-center items-center shadow-lg flex transition-colors rounded-lg"
              :class="selectedDisplayStyle === 'rounded' ? 'border-[#257556] bg-[#257556]/10' : 'border-gray-100 hover:border-[#257556]'"
              @click="selectDisplayStyle('rounded')">
          <button1 class="flex flex-col items-center justify-center gap-2 font-extrabold text-center">
            Rounded Targets

            <svg width="82" height="82" viewBox="0 0 82 82" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="9.9082" y="11.9766" width="58.1836" height="58.1836" rx="29.0918" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="30.9688" y="2.5" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="2.53613" y="18.7158" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="2.53613" y="46.3066" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="30.9688" y="62.5225" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="59.4043" y="46.3066" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="59.4043" y="18.7158" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            </svg>
          </button1>
        </div>

        <div  class="w-full px-4 py-2 border-2 cursor-pointer text-center justify-center items-center shadow-lg flex transition-colors rounded-lg"
              :class="selectedDisplayStyle === 'outside' ? 'border-[#257556] bg-[#257556]/10' : 'border-gray-100 hover:border-[#257556]'"
              @click="selectDisplayStyle('outside')">
          <button1 class="flex flex-col items-center justify-center gap-2 font-extrabold text-center">
            Outside Targets

            <svg width="110" height="85" viewBox="0 0 110 85" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="26.499" y="2.5" width="57.2673" height="57.2673" rx="28.6336" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="2.50098" y="29.8135" width="15.7554" height="15.7554" rx="7.87769" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="9.67188" y="54.5146" width="15.7554" height="15.7554" rx="7.87769" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="33.5771" y="65.6699" width="15.7554" height="15.7554" rx="7.87769" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="59.207" y="65.8594" width="15.7554" height="15.7554" rx="7.87769" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="83.7764" y="54.5146" width="15.7554" height="15.7554" rx="7.87769" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="91.7441" y="29.8135" width="15.7554" height="15.7554" rx="7.87769" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            </svg>
          </button1>
        </div>

        <div  class="w-full px-4 py-2 border-2 cursor-pointer text-center justify-center items-center shadow-lg flex transition-colors rounded-lg"
              :class="selectedDisplayStyle === 'box' ? 'border-[#257556] bg-[#257556]/10' : 'border-gray-100 hover:border-[#257556]'"
              @click="selectDisplayStyle('box')">
          <button1 class="flex flex-col items-center justify-center gap-2 font-extrabold text-center">
            Box Targets

            <svg width="144" height="80" viewBox="0 0 144 80" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="38.25" y="4.25" width="69.5" height="8.5" rx="4.25" fill="#D9D9D9" stroke="#ADADAD" stroke-width="8.5"/>
            <rect x="1.5" y="26" width="43" height="24" rx="2" fill="#D9D9D9" stroke="#ADADAD" stroke-width="2"/>
            <rect x="1.5" y="55" width="43" height="24" rx="2" fill="#D9D9D9" stroke="#ADADAD" stroke-width="2"/>
            <rect x="50.5" y="27" width="43" height="24" rx="2" fill="#D9D9D9" stroke="#ADADAD" stroke-width="2"/>
            <rect x="50.5" y="55" width="43" height="24" rx="1" fill="#D9D9D9" stroke="#ADADAD" stroke-width="2"/>
            <rect x="99.5" y="55" width="43" height="24" rx="1" fill="#D9D9D9" stroke="#ADADAD" stroke-width="2"/>
            <mask id="path-8-inside-1_312_11230" fill="white">
            <rect x="98.5" y="26" width="45" height="26" rx="1"/>
            </mask>
            <rect x="98.5" y="26" width="45" height="26" rx="1" fill="#D9D9D9" stroke="#ADADAD" stroke-width="4" mask="url(#path-8-inside-1_312_11230)"/>
            </svg>
          </button1>
        </div>
    </div>
</div>
</div> -->

        <div>
            <div v-if="!isThemeTwo" class="flex flex-col gap-1">
                <div class="flex flex-row gap-2">
                    <fieldset
                        class="fieldset bg-base-100 border-base-300 rounded-box w-xs border p-2 w-full"
                    >
                        <legend class="fieldset-legend">Round Mode:</legend>
                        <div class="flex flex-col pb-2">
                            <jet-label class="mt-0">
                                <div
                                    class="flex items-center gap-2 hover:text-[#257556] cursor-pointer"
                                >
                                    <Toggle
                                        v-model="form.config.main_trial"
                                        @update:modelValue="
                                            handleMainTrialChange
                                        "
                                    />
                                    <div class="ml-2">Main Trial</div>

                                    <div class="relative group">
                                        <Tooltip
                                            text="By toggling it i will switch to main trial mode."
                                        />
                                        <div
                                            class="absolute bottom-full left--100 mb-[-30px] px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                            role="tooltip"
                                            style="right: -160px"
                                        >
                                            By toggling it i will switch<br />
                                            to main trial mode.
                                        </div>
                                    </div>
                                </div>
                            </jet-label>
                            <jet-label class="mt-2">
                                <div
                                    class="flex items-center gap-2 hover:text-[#257556] cursor-pointer"
                                >
                                    <Toggle
                                        v-model="form.config.practice_trial"
                                        @update:modelValue="
                                            handlePracticeTrialChange
                                        "
                                    />
                                    <div class="ml-2">Practice Trial</div>

                                    <div class="relative group">
                                        <Tooltip
                                            text="By toggling it i will switch to practice trial mode."
                                        />
                                        <div
                                            class="absolute bottom-full left--100 mb-1 px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                            role="tooltip"
                                            style="right: -70px"
                                        >
                                            By toggling it i will switch<br />
                                            to practice trial mode.
                                        </div>
                                    </div>
                                </div>
                            </jet-label>
                        </div>
                    </fieldset>

                    <fieldset
                        class="fieldset bg-base-100 border-base-300 rounded-box w-xs border p-2 w-full"
                    >
                        <legend class="fieldset-legend">Randomization:</legend>
                        <div class="flex flex-col pb-2">
                            <jet-label
                                class="mt-0 hover:text-[#257556] cursor-pointer"
                            >
                                <div class="flex items-center gap-2">
                                    <Toggle
                                        v-model="form.config.random_prime"
                                    />
                                    <div class="ml-2">Randomize Primes</div>

                                    <div class="relative group">
                                        <Tooltip
                                            text="By toggling it i will randomize the primes."
                                        />
                                        <div
                                            class="absolute bottom-full left--100 mb-[-34px] px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                            role="tooltip"
                                            style="right: -135px"
                                        >
                                            By toggling it i will <br/> switch
                                            to randomize<br/> the primes.
                                        </div>
                                    </div>
                                </div>
                            </jet-label>
                            <jet-label
                                class="mt-2 hover:text-[#257556] cursor-pointer"
                            >
                                <div class="flex items-center gap-2">
                                    <Toggle v-model="form.config.random" />
                                    <div class="ml-2">Randomize Targets</div>

                                    <div class="relative group">
                                        <Tooltip
                                            text="By toggling it i will randomize the targets."
                                        />
                                        <div
                                            class="absolute bottom-full left--100 mb-1 px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                            role="tooltip"
                                            style="right: -65px"
                                        >
                                            By toggling it i will <br />
                                            randomize the targets.
                                        </div>
                                    </div>
                                </div>
                            </jet-label>
                        </div>
                    </fieldset>
                </div>

                <div class="flex flex-row gap-2">
                    <fieldset
                        class="fieldset bg-base-100 border-base-300 rounded-box w-xs border p-2 w-full"
                    >
                        <legend class="fieldset-legend">
                            Response Behaviour:
                        </legend>
                        <jet-label
                            class="mt-0 hover:text-[#257556] cursor-pointer"
                        >
                            <div class="flex items-center gap-2">
                                <Toggle
                                    v-model="form.config.prime_as_next"
                                    @update:modelValue="handlePrimeAsNextChange"
                                />
                                <div class="ml-2">Prime as Next Button</div>

                                <div class="relative group">
                                    <Tooltip
                                        text="Treat the prime as a button to continue to the next screen."
                                    />
                                    <div
                                        class="absolute bottom-full left--100 mb-1 px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                        role="tooltip"
                                        style="right: -90px"
                                    >
                                        Treat the prime as a button to
                                        <br />continue to the next screen.
                                    </div>
                                </div>
                            </div>
                        </jet-label>

                        <!-- <jet-label class="mt-3">
                    <div class="flex items-center gap-2">
                        <Toggle v-model="form.config.single_select" />
                        <div class="ml-2">Single select</div>
                        <div>
                            <Tooltip text="By toggling it i will make the targets a single select." />
                        </div>
                    </div>
                </jet-label> -->

                        <jet-label
                            class="mt-1 hover:text-[#257556] cursor-pointer"
                        >
                            <div class="flex items-center gap-2">
                                <Toggle
                                    v-model="form.config.auto_next"
                                    @update:modelValue="handleAutoNextChange"
                                />
                                <div class="ml-2">Auto Next Round</div>

                                <div class="relative group">
                                    <Tooltip
                                        text="Automatically advance to the next round after a response is selected."
                                    />
                                    <div
                                        class="absolute bottom-full left--100 mb-1 px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                        role="tooltip"
                                        style="right: -95px"
                                    >
                                        Automatically advance to the next<br />
                                        round after a response is selected.
                                    </div>
                                </div>
                            </div>
                        </jet-label>

                        <jet-label
                            class="mt-1 hover:text-[#257556] cursor-pointer"
                        >
                            <div class="flex items-center gap-2">
                                <Toggle
                                    v-model="form.config.trial_unselect"
                                    @update:modelValue="handleTrialUnselectChange"
                                />
                                <div class="ml-2">Instant None Response</div>

                                <div class="relative group">
                                    <Tooltip
                                        text="Clicking 'None of these' unselects other selections. "
                                    />
                                    <div
                                        class="absolute bottom-full left--100 mb-1 px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                        role="tooltip"
                                        style="right: -75px"
                                    >
                                        Clicking "None of these"<br />
                                        unselects other selections.
                                    </div>
                                </div>
                            </div>
                        </jet-label>

                        <jet-label
                            class="mt-1 mb-2 hover:text-[#257556] cursor-pointer"
                        >
                            <div class="flex items-center gap-2">
                                <Toggle
                                    v-model="form.config.default_trial_unselect"
                                    @update:modelValue="handleDefaultTrialUnselectChange"
                                />
                                <div class="ml-2">Confirm Empty Selection</div>

                                <div class="relative group">
                                    <Tooltip
                                        text="Requires users to confirm their choice if they attempt to proceed without selecting any options"
                                    />
                                    <div
                                        class="absolute bottom-full left--100 mb-1 px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                        role="tooltip"
                                        style="right: -90px"
                                    >
                                        Requires users to confirm their<br />choice
                                        if they attempt to proceed<br />
                                        without selecting any options
                                    </div>
                                </div>
                            </div>
                        </jet-label>
                    </fieldset>
                    <fieldset
                        class="fieldset bg-base-100 border-base-300 rounded-box w-xs border p-2 w-full"
                    >
                        <legend class="fieldset-legend">Targets Layout:</legend>
                        <jet-label
                            class="mt-0 hover:text-[#257556] cursor-pointer"
                        >
                            <div class="flex items-center gap-1">
                                <Toggle
                                    v-model="
                                        form.config.mobile_vertical_targets
                                    "
                                />
                                <div class="ml-2">
                                    Vertical Targets View (Mobile)
                                </div>

                                <div class="relative group">
                                    <Tooltip
                                        text="By toggling it i will randomize the primes."
                                    />
                                    <div
                                        class="absolute bottom-full left--100 mb-1 px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                        role="tooltip"
                                        style="right: -85px"
                                    >
                                        Switch display from circular layout<br />
                                        to vertical view for easier reading
                                        <br />on mobile device.
                                    </div>
                                </div>
                            </div>
                        </jet-label>
                    </fieldset>
                </div>
            </div>
            <div v-else class="flex flex-col gap-1">
                <jet-label class="mt-3 hover:text-[#257556] cursor-pointer">
                    <div class="flex items-center gap-2">
                        <Toggle v-model="form.config.random_prime" />
                        <div class="ml-2">Randomize primes</div>
                        <div>
                            <Tooltip
                                text="By toggling it i will randomize the primes."
                            />
                        </div>
                    </div>
                </jet-label>
            </div>

            <div class="flex flex-col gap-2">
                <div
                    v-if="!isThemeTwo"
                    class="flex flex-col gap-1 items-end mt-3"
                >
                    <div
                        class="w-full relative group"
                        v-if="form.config.trial_unselect"
                    >
                        <CustomInput
                            label="Trial Unselect Label"
                            type="text"
                            v-model="form.config.trial_unselect_label"
                        />
                        <div
                            class="absolute bottom-full left-0 mb-0 px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                            role="tooltip"
                        >
                            Label for the Instant None Response option.
                        </div>
                    </div>

                    <div
                        v-if="form.config.auto_next"
                        class="w-full relative group"
                    >
                        <CustomInput
                            type="number"
                            label="Auto Next Trial At"
                            v-model="form.config.auto_next_when"
                            @change="handleDisplayByChange"
                            placeholder="selection"
                        />
                        <div
                            class="absolute bottom-full left-0 mb-0 px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                            role="tooltip"
                        >
                            Number of targets to be selected to auto next.
                        </div>
                    </div>

                    <div class="w-full relative group">
                        <CustomInput
                            label="Empty Selection Warning"
                            v-model="form.config.empty_selection"
                        />
                        <div
                            class="absolute bottom-full left-0 mb-0 px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                            role="tooltip"
                        >
                            Warning message when no target is selected.
                        </div>
                    </div>
                    <div v-if="!isThemeTwo" class="w-full relative group">
                        <CustomInput
                            label="Selection Limit Warning"
                            type="text"
                            v-model="form.config.limit_reach"
                        />
                        <div
                            class="absolute bottom-full left-0 mb-0 px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                            role="tooltip"
                        >
                            Warning message when the selection limit is reached.
                        </div>
                    </div>
                </div>

                <div class="w-full flex flex-row gap-2">
                    <div v-if="!isThemeTwo" class="w-full relative group w-1/2">
                        <CustomInput
                            type="number"
                            label="Selection Limit (0 = can select all)"
                            v-model="form.config.limit"
                        />
                        <div
                            class="absolute bottom-full left-0 mb-0 px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                            role="tooltip"
                        >
                            Maximum number of targets that can be selected.
                        </div>
                    </div>
                    <div class="w-2/3 relative group">
                        <CustomInput
                            type="number"
                            label="Display Targets by"
                            v-model="form.config.display_by"
                            @change="handleDisplayByChange"
                        />
                        <div
                            class="absolute bottom-full left-0 mb-0 px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                            role="tooltip"
                        >
                            Number of targets to be displayed.
                        </div>
                    </div>
                    <div class="w-2/3 relative group">
                        <CustomInput
                            label="Target Delay (ms)"
                            type="number"
                            v-model="form.config.target_delay"
                            @blur="enforceMinimumTargetDelay"
                            @mouseleave="enforceMinimumTargetDelay"
                        />
                        <div
                            class="absolute bottom-full left-0 mb-0 px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                            role="tooltip"
                        >
                            Delay time in milliseconds before the targets are
                            displayed.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- <div class="col-span-8">
                <div class="mt-5 block">
                    Response recoding
                </div>
                <div class="mb-2">
                    <JetButton type="button" variant="rose" size="xs" @click.prevent="macroToBeCreatedOrUpdated=true">
                        Add macro code
                    </JetButton>
                </div>
                <div>
                    <div v-for="(mc, k) in (form.config.macros || [])" :key="k" class="bg-slate-100 p-2 my-1 rounded-lg border flex text-xs items-center">
                        <div class="w-3/12">
                            {{ mc.placeholder }}
                        </div>
                        <div class="w-3/12 font-mono">
                            {{ mc.formula }}
                        </div>
                        <div class="w-3/12">
                            <div class="rounded-full bg-white flex items-center p-1 text-gray-500 border">
                                <div class="mx-auto">
                                    <span v-if="mc.type =='tabular'" class="text-xs font-semibold">
                                        Tabular
                                    </span>
                                    <span v-else class="text-xs font-semibold">
                                        Respondent
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="w-4/12 flex items-center gap-1 justify-end">
                            <JetButton variant="orange" size="xs" @click.prevent="editMacro(k)">
                                Update
                            </JetButton>
                            <JetButton variant="orange" size="xs" @click.prevent="deleteMacro(k)">
                                Delete
                            </JetButton>
                        </div>
                    </div>
                </div>
            </div> -->
        <!-- </template> -->

        <!-- <div class="col-span-8 mt-5">
            Skip logic
            <div class="mb-2">
                <JetButton type="button" variant="rose" size="xs" @click.prevent="addSegment">
                    <span v-if="form.config.conditions && form.config.conditions.length > 0">Add Segment</span><span v-else>Add Condition</span>
                </JetButton>
            </div>
            <div v-for="(condition, index) in (form.config.conditions || [])" :key="index" class="relative">
                <TestCriteria :deep="0" :condition="condition" :indexes="[index]"/>
            </div>
            <div class=" mt-3">
                <code class="text-black-500 text-xs bg-gray-200">{{ $testState.criteriaToString(form.config.conditions || []) }}</code>
            </div>
        </div> -->
    </div>

    <!-- <JetDialogModal :show="macroToBeCreatedOrUpdated" @close="macroToBeCreatedOrUpdated = false">
        	<template #title>
        		Add macro code
        	</template>

        	<template #content>
        		<div class="flex gap-2">
                    <div class="p-2 w-full">
                        <CustomInput type="text" label="Placeholder" v-model="macro.placeholder" />
                    </div>
                </div>
                <div class="flex gap-2">
                    <div class="p-2 w-full">
                        <JetLabel value="Applied to" />
                        <select v-model="macro.type" class="form-select mt-1 block w-full text-xs dark:bg-gray-900 rounded-lg p-2 border-gray-300 dark:border-gray-500">
                            <option value="tabular">Tabular</option>
                            <option value="respondent">Respondent Level</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-2">
                    <div class="p-2 w-full">
                        <PrismEditor class="my-editor editor-height" v-model="macro.formula" :highlight="highlighter" line-numbers />
                        <div class="mt-2">
                            <p class="text-gray-500 text-sm">
                                Use the following syntax: <span class="p-0.5 px-2 rounded-lg bg-teal-600 font-mono font-semibold text-white">t1</span> = Target 1, <span class="p-0.5 px-2 rounded-lg bg-teal-600 font-mono font-semibold text-white">t2</span> for Target 2 and so on
                            </p>
                        </div>
                    </div>
                </div>
        	</template>

        	<template #footer>
        		<JetButton variant="default" class="uppercase" @click="macroToBeCreatedOrUpdated = false" size="sm">
        			nevermind
        		</JetButton>

        		<jet-button @click.prevent="saveMacro" size="sm" type="button" class="ml-2 uppercase" variant="primary" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
        			Save changes
        		</jet-button>
        	</template>
        </JetDialogModal> -->
</template>

<style scoped="true">
.c {
    max-height: calc(100vh - 156px);
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
</style>
