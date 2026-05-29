<script setup>
import JetButton from "@/Componentsv2/Button.vue";
import CustomCard from "@/Components/Card.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetSecondaryButton from "@/Components/SecondaryButton.vue";
import JetDangerButton from "@/Jetstream/DangerButton.vue";
import SmallButton from "@/Components/SmallButton.vue";
import JetLabel from "@/Jetstream/Label.vue";
// import SettingsForm from './SettingsForm.vue';
import SurveyDropdown from "@/Extra/SurveyDropdown.vue";
import head from "lodash/head";

import isEmpty from "lodash/isEmpty";
import Tab1 from "@/Components/Tab1.vue";
import CustomForm from "@/Components/Form2.vue";
import TestSettings1 from "./TestSettings1.vue";
import TestPrimes1 from "./TestPrimes1.vue";
import TestTargets1 from "./TestTargets1.vue";
import TestInstruction1 from "./TestInstruction1.vue";
import TestAdvance1 from "./TestAdvance1.vue";

import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref, watch, onBeforeMount, provide, computed } from "vue";

import Helpers from "@/Mixins/Helpers";

const props = defineProps([
    "survey",
    "tests",
    "action",
    "test",
    "userPermissions",
    "selectedTrialMode",
    "project",
    "templatePrimes",
    "templateTargets",
]);



const { toast, assign } = Helpers();

const active_tab = ref("instruction");
const slideDirection = ref("forward"); // 'forward' or 'reverse'
const enableCK = ref(
    !localStorage.getItem("ck") ? true : localStorage.getItem("ck") === "true"
);

// Local ref to track selectedTrialMode changes
const selectedTrialMode = ref(props.selectedTrialMode);

// Add trial mode modal state
const showTrialModeModal = ref(false);
// Add confirmation modal state
const showConfirmChangeModal = ref(false);
// Track a pending mode change that requires confirmation
const pendingMode = ref(null);

// Template refs for child components
const testPrimesRef = ref(null);
const testTargetsRef = ref(null);

const tab_data = ref([
    {
        icon: `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10.6667 3.3335H2" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M7.33333 8H2" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10.6667 12.6665H2" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M14 8H10" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>`,
        text: "Instruction",
        active: active_tab.value === "instruction",
        key: "instruction",
        disabled: false,
    },
    {
        icon: `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_668_14944)">
                <path d="M4.66602 6.6665H11.3327" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M4.66602 9.3335H11.3327" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8.00065 14.6668C11.6825 14.6668 14.6673 11.6821 14.6673 8.00016C14.6673 4.31826 11.6825 1.3335 8.00065 1.3335C4.31875 1.3335 1.33398 4.31826 1.33398 8.00016C1.33398 11.6821 4.31875 14.6668 8.00065 14.6668Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </g>
                <defs>
                <clipPath id="clip0_668_14944">
                <rect width="16" height="16" fill="white"/>
                </clipPath>
                </defs>
                </svg>`,
        text: "Primes",
        active: active_tab.value === "primes",
        key: "primes",
        disabled: selectedTrialMode.value === "descriptive",
    },
    {
        icon: `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="3.5" cy="3.5" r="2.75" stroke="white" stroke-width="1.5"/>
                <circle cx="12.5" cy="3.5" r="2.75" stroke="white" stroke-width="1.5"/>
                <circle cx="3.5" cy="12.5" r="2.75" stroke="white" stroke-width="1.5"/>
                <circle cx="12.5" cy="12.5" r="2.75" stroke="white" stroke-width="1.5"/>
                </svg>`,
        text: "Targets",
        active: active_tab.value === "targets",
        key: "targets",
        disabled: selectedTrialMode.value === "descriptive",
    },
    {
        icon: `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9.33398 11.3335H3.33398" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12.666 4.6665H6.66602" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M11.334 13.3335C12.4386 13.3335 13.334 12.4381 13.334 11.3335C13.334 10.2289 12.4386 9.3335 11.334 9.3335C10.2294 9.3335 9.33398 10.2289 9.33398 11.3335C9.33398 12.4381 10.2294 13.3335 11.334 13.3335Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M4.66602 6.6665C5.77059 6.6665 6.66602 5.77107 6.66602 4.6665C6.66602 3.56193 5.77059 2.6665 4.66602 2.6665C3.56145 2.6665 2.66602 3.56193 2.66602 4.6665C2.66602 5.77107 3.56145 6.6665 4.66602 6.6665Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>`,
        text: "Settings",
        active: active_tab.value === "settings",
        key: "settings",
        disabled: selectedTrialMode.value === "descriptive",
    },
    // {
    //     text: "advance",
    //     active: active_tab.value === "advance",
    //     key: "advance",
    //     disabled: false,
    // },
]);

const form = useForm({
    config: {
        title: "",
        main_trial: true,
        random: true,
        prime_header: "",
        main_header: "",
        trial_unselect: false,
        default_trial_unselect: false,
        trial_unselect_label: "none of these",
        empty_selection: "Please select at least 1 item",
        main_header_timeout: 3000,
        type: null,
        single_select: false,
        limit: 0,
        auto_next: false,
        display_by: 1,
        random_prime: false,
        random_prime_subsets: false,
        mobile_vertical_targets: false,
        prime_as_next: true,
        bottom_text:
            "<em>Press the text in the centre to proceed to the next trial</em> (Click the center, if none of these apply)",
        conditions: [],
        macros: [],
        target_delay: 1500,
        limit_reach: "You have reached the maximum selection limit",
        dummy_trial: false,
        auto_next_when: 0,
    },
    primes: [],
    targets: [],
});

provide("form", form);
provide("ck", enableCK);

watch(enableCK, (newval) => {
    localStorage.setItem("ck", newval);
});

watch(
    () => form.config.prime_as_next,
    (newVal) => {
        if (newVal === true) {
            form.config.auto_next = false;
        } else if (newVal === false) {
            form.config.auto_next = true;
        }
    }
);

watch(
    () => form.config.auto_next,
    (newVal) => {
        if (newVal === true) {
            form.config.prime_as_next = false;
        } else if (newVal === false) {
            form.config.prime_as_next = true;
        }
    }
);

const themeValue = computed(() => {
    const candidates = [
        form.config?.theme,
        form.config2?.theme,
        props.test?.config?.theme,
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

const applyLikertDefaults = () => {
    form.config.random = false;
    form.config.random_prime = false;
    form.config.random_prime_subsets = false;
    form.config.prime_as_next = false;
    form.config.trial_unselect = false;
    form.config.default_trial_unselect = false;
    form.config.mobile_vertical_targets = false;
    form.config.auto_next = true;
    form.config.single_select = true;
    form.config.bottom_text = "";
};

watch(
    themeValue,
    (newTheme, previousTheme) => {
        if (Number(newTheme) === 2 && Number(previousTheme) !== 2) {
            applyLikertDefaults();
        }
    },
    { immediate: true }
);

onBeforeMount(() => {
    if (props.action === "update") {
        form.config = assign(props.test.config);
        form.primes = assign(props.test.primes);
        form.targets = assign(props.test.targets);
        if (typeof form.config.random_prime_subsets === "undefined") {
            form.config.random_prime_subsets = false;
        }
        if (typeof form.config.conditions === "undefined") {
            form.config.conditions = [];
        }
        if (typeof form.config.macros === "undefined") {
            form.config.macros = [];
        }
    }
});

watch(form, (newVal) => {
    tab_data.value[1].disabled =
        !isEmpty(newVal.config.title) ||
        selectedTrialMode.value === "descriptive";
    tab_data.value[2].disabled =
        !isEmpty(newVal.config.title) ||
        selectedTrialMode.value === "descriptive";
});

// Watch for selectedTrialMode changes to update tab disabled state
watch(
    () => props.selectedTrialMode,
    (newMode) => {
        selectedTrialMode.value = newMode;
        tab_data.value[1].disabled = newMode === "descriptive";
        tab_data.value[2].disabled = newMode === "descriptive";
        // Settings tab should always be enabled
        tab_data.value[3].disabled = newMode === "descriptive";

        // If user is on a disabled tab, redirect to settings
        if (
            newMode === "descriptive" &&
            (active_tab.value === "primes" ||
                active_tab.value === "targets" ||
                active_tab.value === "settings")
        ) {
            active_tab.value = "settings";
        }
    }
);

const selected_tab = (tab_key) => {
    // Find the tab to check if it's disabled
    const tab = tab_data.value.find((t) => t.key === tab_key);
    if (tab && tab.disabled) {
        return; // Don't switch to disabled tab
    }
    
    // Determine slide direction based on tab order
    const tabOrder = ["instruction", "primes", "targets", "settings"];
    const currentIndex = tabOrder.indexOf(active_tab.value);
    const newIndex = tabOrder.indexOf(tab_key);
    
    if (newIndex > currentIndex) {
        slideDirection.value = "forward"; // Moving forward (right to left)
    } else if (newIndex < currentIndex) {
        slideDirection.value = "reverse"; // Moving backward (left to right)
    }
    
    return (active_tab.value = tab_key);
};

const hasNonEmptyEditorContent = (html) => {
    if (!html) return false;
    const trimmed = html.trim();
    if (
        trimmed === "" ||
        trimmed === "<p><br></p>" ||
        trimmed === "<p></p>" ||
        trimmed === "<p><br/></p>"
    ) {
        return false;
    }

    if (typeof window !== "undefined" && typeof document !== "undefined") {
        const container = document.createElement("div");
        container.innerHTML = trimmed;
        const textContent = container.textContent?.trim() || "";
        const hasText = textContent !== "";
        const hasImage = !!container.querySelector("img");
        return hasText || hasImage;
    }

    // Fallback for non-browser contexts
    const textOnly = trimmed.replace(/<[^>]*>/g, "").trim();
    if (textOnly !== "") return true;
    return /<img\b[^>]*>/i.test(trimmed);
};

// Helper function to check if text exists in title, main_header, primes, or targets
const hasInputText = () => {
    const hasTitle = hasNonEmptyEditorContent(form.config.title);
    const hasMainHeader = hasNonEmptyEditorContent(form.config.main_header);
    
    // Check if primes or targets exist
    const hasPrimes = form.primes && form.primes.length > 0;
    const hasTargets = form.targets && form.targets.length > 0;
    
    return hasTitle || hasMainHeader || hasPrimes || hasTargets;
};

// Handle Change Round button click
const handleChangeRoundClick = () => {
    // Always show the round selection modal first
    showTrialModeModal.value = true;
};

// Helper to apply mode change without clearing content
const applyModeChange = (newMode) => {
    selectedTrialMode.value = newMode;

    if (newMode === "descriptive") {
        form.config.practice_trial = false;
        form.config.main_trial = false;
    } else if (newMode === "practice") {
        form.config.practice_trial = true;
        form.config.main_trial = false;
    } else if (newMode === "main") {
        form.config.practice_trial = false;
        form.config.main_trial = true;
    }

    // Update tab disabled states
    tab_data.value[1].disabled = newMode === "descriptive";
    tab_data.value[2].disabled = newMode === "descriptive";
    // Settings tab should always be enabled (ensure false)
    tab_data.value[3].disabled = newMode === "descriptive";

    // Redirect from disabled tabs when switching to descriptive
    if (
        newMode === "descriptive" &&
        (active_tab.value === "primes" ||
            active_tab.value === "targets" ||
            active_tab.value === "settings")
    ) {
        active_tab.value = "settings";
    }
};

// Handle confirmation - proceed with changing round (with clearing)
const confirmChangeRound = () => {
    // Clear editable content only for transitions involving descriptive
    form.config.title = "";
    form.config.main_header = "";
    form.primes = [];
    form.targets = [];

    // Close confirmation modal
    showConfirmChangeModal.value = false;

    // Apply the pending mode change if set
    if (pendingMode.value) {
        applyModeChange(pendingMode.value);
        pendingMode.value = null;
    }
};

// Handle cancellation - stay on current round
const cancelChangeRound = () => {
    showConfirmChangeModal.value = false;
};

// Handle trial mode change from TestSettings1 component
const handleTrialModeChange = (newMode) => {
    if (!newMode || newMode === selectedTrialMode.value) {
        showTrialModeModal.value = false;
        return;
    }

    const involvesDescriptive =
        newMode === "descriptive" || selectedTrialMode.value === "descriptive";

    if (involvesDescriptive) {
        // Require confirmation and clear on confirm
        pendingMode.value = newMode;
        showTrialModeModal.value = false;
        showConfirmChangeModal.value = true;
        return;
    }

    // Directly apply mode change without clearing when not involving descriptive
    applyModeChange(newMode);
    showTrialModeModal.value = false;
};

const trialAction = async () => {
    //form.config.main_header = form.config.main_header.replace(/&nbsp;/g, " ").replace(/\u00A0/g, " ");

    // Helper to ensure each prime has usable content (text or image)
    const hasValidPrimeContent = (prime) => {
        if (!prime) return false;
        return hasNonEmptyEditorContent(prime);
    };

    // Validation logic
    if (selectedTrialMode.value === "descriptive") {
        // For descriptive mode, check if title is empty
        if (!hasNonEmptyEditorContent(form.config.title)) {
            toast.error("Round Title is required");
            return;
        }
    } else if (
        selectedTrialMode.value === "practice" ||
        selectedTrialMode.value === "main"
    ) {
        // Check if primes and targets are empty
        if (form.primes.length === 0 || form.targets.length === 0) {
            toast.error("Primes and Targets are required");
            return;
        }

        // Prevent saving if there are primes with no visible content
        const hasEmptyPrime = form.primes.some(
            (p) => !hasValidPrimeContent(p?.prime)
        );
        if (hasEmptyPrime) {
            toast.error("Need to input character to add a prime");
            return;
        }
    }

    if (props.action === "create") {
        try {
            if (
                testPrimesRef.value &&
                testPrimesRef.value.saveTemplatePrimesIfNeeded
            ) {
                await testPrimesRef.value.saveTemplatePrimesIfNeeded();
            }
            if (
                testTargetsRef.value &&
                testTargetsRef.value.saveTemplateTargetsIfNeeded
            ) {
                await testTargetsRef.value.saveTemplateTargetsIfNeeded();
            }
        } catch (e) {}
        createTrial();
    } else {
        updateTrial();
    }
};

const createTrial = () => {
    if (form.targets.length == 0 || form.primes.length == 0) {
        form.config.type = "descriptive";
    } else {
        form.config.type = "trial";
    }
    form.post(route("tests.store", props.survey), {
        preserveScroll: true,
        errorBag: "createTrial",
        onSuccess: () => {
            toast.success("Round successfully created");
            router.visit(route("survey2.index", props.survey));
        },
    });
};

const updateTrial = () => {
    if (form.targets.length == 0 || form.primes.length == 0) {
        form.config.type = "descriptive";
    } else {
        form.config.type = "trial";
    }
    form.put(route("tests.update", [props.survey, props.test]), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Round successfully updated");
        },
    });
};
</script>

<template>
    <!-- <Head :title="survey.name" /> -->
    <Head :title="survey?.name || 'Survey'" />
    
    <!-- Confirmation Modal for Changing Round -->
    <JetDialogModal
        :show="showConfirmChangeModal"
        @close="cancelChangeRound"
        :closeable="true"
    >
        <template #title>Change Round</template>
        <template #content>
            <div class="space-y-4">
                <p class="text-gray-600">
                    All the changes you made will be cleared if click "YES". Do you want to change round? 
                </p>
            </div>
        </template>

        <template #footer>
            <JetSecondaryButton
                @click="cancelChangeRound"
                size="sm"
            >
                NO, KEEP CURRENT ROUND
            </JetSecondaryButton>
            <JetButton
                variant="primary"
                @click="confirmChangeRound"
                size="sm"
                class="ml-2"
            >
                YES, CHANGE ROUND
            </JetButton>
        </template>
    </JetDialogModal>
    
    <!-- Trial Mode Selection Modal -->
    <JetDialogModal
        :show="showTrialModeModal"
        @close="showTrialModeModal = false"
        :closeable="true"
    >
        <template #title>Select Round Mode</template>
        <template #content>
            <div class="space-y-4">
                <p class="text-gray-600 text-sm">
                    Choose the type of round you want to create:
                </p>

                <div class="grid grid-cols-1 gap-3">
                    <button
                        @click="handleTrialModeChange('descriptive')"
                        class="p-4 text-left border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-colors"
                    >
                        <div class="font-semibold text-green-600">
                            Descriptive Round
                        </div>
                        <div class="text-sm text-gray-600 mt-1">
                            Add a round with just a title-providing context or information. No interactive elements included.
                        </div>
                    </button>

                    <button
                        @click="handleTrialModeChange('practice')"
                        class="p-4 text-left border-2 border-gray-200 rounded-lg hover:border-red-500 hover:bg-red-50 transition-colors"
                    >
                        <div class="font-semibold text-red-600">
                            Practice Round
                        </div>
                        <div class="text-sm text-gray-600 mt-1">
                            Set up a practice round with all configuration options and interactions-perfect for testing before the main round.
                        </div>
                    </button>

                    <button
                        @click="handleTrialModeChange('main')"
                        class="p-4 text-left border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors"
                    >
                        <div class="font-semibold text-blue-600">
                            Main Round
                        </div>
                        <div class="text-sm text-gray-600 mt-1">
                            Create a main round with full configuration options and interactive elements for the actual run.
                        </div>
                    </button>
                </div>
            </div>
        </template>

        <template #footer>
            <JetButton
                variant="default"
                @click="showTrialModeModal = false"
                size="sm"
            >
                Close
            </JetButton>
        </template>
    </JetDialogModal>
    
    <div class="bg-slate-200 min-h-[84vh] bg-white">
        <div class="flex flex-col mx-auto max-w-7xl">
            <div class="sticky top-0 flex flex-col py-2 bg-white hidden">
                <!-- <div class="flex items-center justify-between py-2">
          <div class="flex items-center gap-2">
            <div>
              <a :href="route('redirect.fluent', survey.config.survey_id)">
                <JetButton
                  variant="emerald"
                  size="sm"
                  class="uppercase"
                  shadow="xl"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="w-5 h-5"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M19.5 12h-15m0 0l6.75 6.75M4.5 12l6.75-6.75"
                    />
                  </svg>
                  Back to Fluent
                </JetButton>
              </a>
            </div>
            <div class="text-lg"> -->
                <!-- {{ survey.name }} -->
                <!-- {{ survey?.name || "No Survey" }}
            </div>
          </div>

          <div class="text-lg tracking-wide">
            Logged in as {{ $page.props.auth.user.name }}
          </div>
        </div> -->
                <div class="flex items-center justify-end gap-2">
                    <Link :href="route('survey2.index', survey)">
                        <JetButton
                            variant="default"
                            size="sm"
                            class="px-4 uppercase"
                        >
                            Back to survey
                        </JetButton>
                    </Link>
                    <div class="flex gap-2 item-center">
                        <JetButton
                            variant="primary"
                            size="sm"
                            class="px-4 uppercase"
                            @click="trialAction"
                            v-if="userPermissions.canUpdateSurvey"
                        >
                            Create Round
                        </JetButton>
                        <!-- <JetButton
              variant="default"
              size="sm"
              class="px-4 uppercase"
              shadow="xl"
            >
              preview
            </JetButton> -->
                    </div>
                </div>
            </div>

            <div class="p-2 overflow-y-auto bg-white">
                <div class="mx-auto bg-white rounded-lg max-w-7xl">
                    <!-- Current Trial Mode Indicator and Change Button -->
                    <div
                        class="flex items-center gap-3 absolute right-10 mt-0.5"
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
                                >{{ selectedTrialMode }} Round</span
                            >
                        </div>

                        <!-- Change Trial Mode Button -->
                        <JetButton
                            type="button"
                            variant="default"
                            size="sm"
                            @click="handleChangeRoundClick"
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
                            Change Round
                        </JetButton>
                    </div>
                    <Tab1 :tabs="tab_data" @selected="selected_tab">
                        <CustomForm @submitted="createTrial">
                            <template #form>
                                <!-- Carousel Container -->
                                <div class="relative overflow-hidden mt-4" style="min-height: 600px;">
                                    <transition-group 
                                        :name="slideDirection === 'reverse' ? 'slide-reverse' : 'slide'" 
                                        tag="div" 
                                        class="relative" 
                                        style="min-height: 600px;"
                                    >
                                        <!-- Instruction Tab -->
                                        <div
                                            v-if="active_tab === 'instruction'"
                                            key="instruction"
                                            class="absolute inset-0 "
                                        >
                                            <TestInstruction1
                                                :survey="survey"
                                                :selectedTrialMode="selectedTrialMode"
                                                @trialModeChanged="
                                                    handleTrialModeChange
                                                "
                                            />
                                        </div>

                                        <!-- Primes Tab -->
                                        <div
                                            v-if="active_tab === 'primes'"
                                            key="primes"
                                            class="absolute inset-0"
                                        >
                                            <TestPrimes1
                                                ref="testPrimesRef"
                                                :template-primes="templatePrimes"
                                                :project="project"
                                                :survey="survey"
                                            />
                                        </div>

                                        <!-- Targets Tab -->
                                        <div
                                            v-if="active_tab === 'targets'"
                                            key="targets"
                                            class="absolute inset-0 "
                                        >
                                            <TestTargets1
                                                ref="testTargetsRef"
                                                :template-targets="templateTargets"
                                                :project="project"
                                                :survey="survey"
                                            />
                                        </div>

                                        <!-- Settings Tab -->
                                        <div
                                            v-if="active_tab === 'settings'"
                                            key="settings"
                                            class="absolute inset-0"
                                        >
                                            <TestSettings1
                                                :survey="survey"
                                                :selectedTrialMode="selectedTrialMode"
                                                @trialModeChanged="
                                                    handleTrialModeChange
                                                "
                                            />
                                        </div>

                                        <!-- Advance Tab -->
                                        <div
                                            v-if="active_tab === 'advance'"
                                            key="advance"
                                            class="absolute inset-0 overflow-y-auto"
                                        >
                                            <TestAdvance1
                                                :survey="survey"
                                                :selectedTrialMode="selectedTrialMode"
                                                @trialModeChanged="
                                                    handleTrialModeChange
                                                "
                                            />
                                        </div>
                                    </transition-group>
                                </div>
                            </template>
                        </CustomForm>
                        <div class="fixed bottom-8 right-6 bg-white pr-4">
                            <div class="flex items-center justify-end gap-2">
                                <Link :href="route('survey2.index', survey)">
                                    <JetButton
                                        variant="default"
                                        size="sm"
                                        class="px-4 uppercase"
                                    >
                                        Nevermind
                                    </JetButton>
                                </Link>
                                <div class="flex gap-2 item-center">
                                    <JetButton
                                        variant="primary"
                                        size="sm"
                                        class="px-4 uppercase"
                                        @click="trialAction"
                                        v-if="userPermissions.canUpdateSurvey"
                                    >
                                        Create Round
                                    </JetButton>
                                </div>
                            </div>
                        </div>
                    </Tab1>
                </div>
            </div>
        </div>
    </div>
</template>
<style>
body {
    overflow: visible !important;
}

/* Carousel slide transitions - Forward (right to left) */
.slide-enter-active,
.slide-leave-active {
    transition: all 0.4s ease-in-out;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
}

.slide-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.slide-leave-to {
    opacity: 0;
    transform: translateX(-100%);
}

.slide-enter-to,
.slide-leave-from {
    opacity: 1;
    transform: translateX(0);
}

.slide-move {
    transition: transform 0.4s ease-in-out;
}

/* Carousel slide transitions - Reverse (left to right) */
.slide-reverse-enter-active,
.slide-reverse-leave-active {
    transition: all 0.4s ease-in-out;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
}

.slide-reverse-enter-from {
    opacity: 0;
    transform: translateX(-100%);
}

.slide-reverse-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

.slide-reverse-enter-to,
.slide-reverse-leave-from {
    opacity: 1;
    transform: translateX(0);
}

.slide-reverse-move {
    transition: transform 0.4s ease-in-out;
}
</style>