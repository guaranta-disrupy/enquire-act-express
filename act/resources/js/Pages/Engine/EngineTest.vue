<template>
    <Head :title="surveyData?.surveys?.[0]?.name" />
    <div
        class="fixed inset-0 flex items-center justify-center"
        :style="[customStyles, generalStyles]"
    >
        <div v-if="status === 'not_started'" class="w-full h-full">
            <!-- Progress Bar -->
            <TrialProgressBar v-show="showSurvey" />

            <!-- image cacher -->
            <ActImageCacher
                :isEditorMode="isEditorMode"
                :dataObject="surveyData"
                @loading-complete="handleLoadingComplete"
            />

            <!-- Background Logo -->
            <img :src="engine_img_src" class="logo-background" />

            <!-- survey main page with transition -->
            <Transition
                name="survey-fade"
                mode="out-in"
                @enter="onSurveyEnter"
                @leave="onSurveyLeave"
            >
                <ActSurvey
                    v-if="
                        showSurvey &&
                        !surveyTestState.isSurveyComplete &&
                        !isErrorDialogVisible
                    "
                    :key="'survey'"
                    :surveyData="surveyData"
                />

                <!-- survey done - redirect -->
                <TrialRedirect
                    v-else-if="
                        surveyTestState.isSurveyComplete &&
                        !isErrorDialogVisible
                    "
                    :key="'redirect'"
                    type="closing"
                    :isEditorMode="isEditorMode"
                />
            </Transition>

            <!-- error dialog -->
            <ActErrorDialog
                @show="handleErrorDialogShow"
                @hide="handleErrorDialogHide"
            />

            <!-- offline indicator -->
            <DatabaseError
                v-if="surveyTestState.isOffline"
                :isOffline="surveyTestState.isOffline"
                error="No Internet Connection"
                message="Please check your network connection and try again."
                @retry="handleRetrySubmission"
            />

            <!-- preview banner -->
            <Transition name="preview-banner">
                <div v-if="isPreview && showSurvey" class="preview-banner">
                    Preview mode
                </div>
            </Transition>
        </div>
        <div v-else-if="status === 'complete'">
            <TrialRedirect :key="'test_complete'" type="test_complete" />
        </div>
    </div>
</template>

<script setup>
import { Head } from "@inertiajs/vue3";
import {
    defineProps,
    onMounted,
    onUnmounted,
    ref,
    computed,
    provide,
    onBeforeMount,
    watch,
    toRaw,
} from "vue";
import TrialProgressBar from "./components/trial/TrialProgressBar.vue";
import ActImageCacher from "./components/ActImageCacher.vue";
import ActSurvey from "./components/ActSurvey.vue";
import ActErrorDialog from "./components/ActErrorDialog.vue";
import TrialRedirect from "./components/trial/TrialRedirect.vue";
import DatabaseError from "./DatabaseError.vue";
import { logoOutlineSvg } from "./utils/s4bg.js";
import { generalStyles } from "./utils/trialStyling.js";
import { useDynamicStyles } from "./utils/dynamicStyling";
import {
    surveyTestState,
    initializeSurveyStore,
    jumpToTest,
    getStorageKey,
} from "./utils/surveyTestStore.js";
import { getTrialCounter } from "./utils/trialCounter.js";
import {
    checkForFailedSubmissions,
    initializeSurveySubmission,
    requestRedirect,
} from "./utils/submitResponses.js";
import { useLogger } from "./utils/logger.js";
import { useDynamicJs } from "./utils/useDynamicJs.js";
import { getDefaultStyles } from "./utils/styleDefaults.js";

const { _log } = useLogger();

const props = defineProps({
    status: String,
    surveyData: Object,
    query: Object,
});

provide("status", props.status);

const isEditorMode = computed(
    () => props.surveyData?.config?.editor_start_test_index !== undefined
);

// Inject dynamic CSS from survey config
const engineCss = computed(() => {
    return props.surveyData?.surveys?.[0]?.config?.css || "";
});
useDynamicStyles(engineCss);

// Custom JS from survey config
const customJs = computed(() => {
    if (isEditorMode.value) {
        return null;
    }
    const jsString = props.surveyData.surveys[0].config?.js || null;
    return jsString;
});
useDynamicJs(customJs);

// trial background image
const engine_img_src = computed(() => {
    return (
        props.surveyData.surveys[0].config?.engine_bg_image || logoOutlineSvg
    );
});

const isTablet = computed(() => {
    if (typeof navigator === "undefined") return false;
    const userAgent = navigator.userAgent;
    // Detects iPad and Android tablets.
    return (
        /iPad/i.test(userAgent) ||
        (/Android/i.test(userAgent) && !/Mobile/i.test(userAgent))
    );
});

// Track current orientation for non-editor mode
const currentOrientation = ref("landscape");

// Update orientation based on window size
const updateOrientation = () => {
    currentOrientation.value =
        window.innerWidth > window.innerHeight ? "landscape" : "portrait";
};

// Custom styles from survey config
const customStyles = computed(() => {
    // In editor mode, styles are injected by the editor parent window to allow for live updates.
    // Outside of the editor, we apply the styles saved in the survey's config, falling back to defaults.
    if (isEditorMode.value) {
        return {};
    }

    const saved = props.surveyData?.config?.styles;
    const theme = props.surveyData?.config?.theme;
    const orientation = currentOrientation.value;

    // Get defaults for the current orientation
    const defaults = getDefaultStyles(theme, orientation, isTablet.value);

    // Handle different format versions
    if (saved && typeof saved === "object") {
        // Newest format: theme-based with landscape/portrait
        if (saved.theme1 || saved.theme2) {
            const themeKey = `theme${theme}`;
            const themeStyles = saved[themeKey];
            if (
                themeStyles &&
                (themeStyles.landscape ||
                    themeStyles.portrait ||
                    themeStyles.tabletPortrait)
            ) {
                let orientationStyles = themeStyles[orientation] || {};

                // Apply tablet-specific portrait styles if available
                if (
                    isTablet.value &&
                    orientation === "portrait" &&
                    themeStyles.tabletPortrait
                ) {
                    orientationStyles = {
                        ...orientationStyles,
                        ...themeStyles.tabletPortrait,
                    };
                }

                return { ...defaults, ...orientationStyles };
            }
        }
        // Previous format: direct landscape/portrait objects
        else if (saved.landscape || saved.portrait) {
            const orientationStyles =
                orientation === "landscape" ? saved.landscape : saved.portrait;
            return { ...defaults, ...orientationStyles };
        }
        // Oldest format: single styles object
        else {
            return { ...defaults, ...saved };
        }
    }

    return defaults;
});

// Reactive state to control survey visibility
const showSurvey = ref(false);
const isErrorDialogVisible = ref(false);

// Handle loading complete event from ActImageCacher
const handleLoadingComplete = () => {
    _log("Image caching complete, showing survey...");
    showSurvey.value = true;
};

// Handle error dialog show/hide events
const handleErrorDialogShow = () => {
    isErrorDialogVisible.value = true;
};

const handleErrorDialogHide = () => {
    isErrorDialogVisible.value = false;
};

const handleRetrySubmission = () => {
    _log("Retry submission triggered from error modal.");
    // Hide modal and attempt submission again.
    // If it fails, the modal will reappear via the logic in `fetchWithRetry`.
    surveyTestState.isOffline = false;
    initializeSurveySubmission();
};

// Survey transition handlers
const onSurveyEnter = (el) => {
    _log("Survey/Redirect component entering");
    _log(
        "Survey/Redirect component entering - Test state:",
        surveyTestState.testState
    );
};

const onSurveyLeave = (el) => {
    _log("Survey/Redirect component leaving");
};

// computed
const isPreview = computed(() => {
    return props.query?.preview ? true : false;
});

const isSimulation = computed(() => {
    return props.query?.simulate ? true : false;
});

provide("isSimulation", isSimulation);

onBeforeMount(() => {
    if (props.status === "complete") {
        surveyTestState.testState = "survey_completed";
        surveyTestState.isSurveyComplete = true;
    }
});

// Initialize the store when component mounts
onMounted(() => {
    // Ensure the viewport meta tag is present for consistent scaling, especially in the iframe.
    let viewportMeta = document.querySelector('meta[name="viewport"]');
    if (!viewportMeta) {
        viewportMeta = document.createElement("meta");
        viewportMeta.name = "viewport";
        document.head.appendChild(viewportMeta);
    }
    viewportMeta.content =
        "width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no";

    // Initialize orientation tracking for non-editor mode
    if (!isEditorMode.value) {
        updateOrientation();
        window.addEventListener("resize", updateOrientation);
        window.addEventListener("orientationchange", updateOrientation);
    }

    // Enable two-way messaging only in editor mode
    if (isEditorMode.value) {
        // Receive navigation messages from the editor without reloading iframe
        const handleEditorMessage = (event) => {
            const data = event?.data || {};
            if (data.type === "EDITOR_NAVIGATE") {
                const targetTestIndex = Number(data.testIndex) || 0;
                const targetTrialIndex = Number(data.trialIndex) || 0;
                jumpToTest(targetTestIndex, targetTrialIndex);
            }
        };
        window.addEventListener("message", handleEditorMessage);
        window.__engineMsgHandler = handleEditorMessage;

        // Notify parent about current engine position (test/trial)
        const notifyParentOfPosition = () => {
            try {
                window.parent?.postMessage(
                    {
                        type: "ENGINE_POSITION",
                        testIndex: surveyTestState.surveyItemIndex,
                        trialIndex: surveyTestState.trialItemIndex,
                        tests: toRaw(props.surveyData.surveys[0].tests),
                    },
                    "*"
                );
            } catch (_) {}
        };

        // Initial notify after mount
        notifyParentOfPosition();

        // Watch indices and notify parent on changes
        const stopWatchers = [
            watch(
                () => surveyTestState.surveyItemIndex,
                () => notifyParentOfPosition()
            ),
            watch(
                () => surveyTestState.trialItemIndex,
                () => notifyParentOfPosition()
            ),
        ];

        // Store cleanup on window for use in unmounted
        window.__engineWatchCleanup = () =>
            stopWatchers.forEach((stop) => stop && stop());
    }

    surveyTestState.query = props.query;
    if (props.status === "complete") {
        setTimeout(() => {
            requestRedirect();
        }, 2000);
        _log("Trial completed already...", props.status);
    } else if (props.surveyData && props.status === "not_started") {
        // Check for failed survey submissions first
        const hasFailedSubmission = checkForFailedSubmissions();

        const storageKey = getStorageKey();
        const savedData = localStorage.getItem(storageKey);

        if (hasFailedSubmission && savedData) {
            _log("Found failed survey submission");
            // Restore the entire survey state from localStorage
            const restored = surveyTestState.restoreFromStorage();
            if (restored) {
                _log("Survey state restored from localStorage");
                initializeSurveySubmission();
            }
        } else {
            // Initialize store with fresh data
            initializeSurveyStore(props.surveyData);
        }

        // calculate trial count
        const { getTrialCount } = getTrialCounter();
        surveyTestState.trialCount = getTrialCount(props.surveyData);
    }
});

// Clean up event listeners when component unmounts
onUnmounted(() => {
    if (!isEditorMode.value) {
        window.removeEventListener("resize", updateOrientation);
        window.removeEventListener("orientationchange", updateOrientation);
    }

    if (isEditorMode.value) {
        if (window.__engineWatchCleanup) {
            try {
                window.__engineWatchCleanup();
            } catch (_) {}
            delete window.__engineWatchCleanup;
        }
        if (window.__engineMsgHandler) {
            window.removeEventListener("message", window.__engineMsgHandler);
            delete window.__engineMsgHandler;
        }
    }
});
</script>

<style scoped>
/* Global fixes */
:global(body),
:global(html) {
    margin: 0;
    padding: 0;
    height: 100%;
    overflow: hidden;
}

/* Preview banner transitions */
.preview-banner-enter-active {
    transition: transform 0.5s ease-in-out;
}

.preview-banner-enter-from {
    transform: translateY(100%);
}

.preview-banner-leave-to {
    opacity: 0;
    transform: translateX(-100%);
}

/* Survey/Redirect component transitions */
.survey-fade-enter-active,
.survey-fade-leave-active {
    transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
}

.survey-fade-enter-from {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
}

.survey-fade-leave-to {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
}

.survey-fade-enter-to,
.survey-fade-leave-from {
    opacity: 1;
    transform: translateY(0) scale(1);
}

.logo-background {
    /* background-image: v-bind(logoSvgString); */
    background-repeat: no-repeat;
    background-position: center;
    background-size: contain;
    box-sizing: border-box;
    pointer-events: none;
    z-index: 0;
    /* Default styles to prevent glitch */
    max-width: unset;
    position: fixed;
    top: 0vmin;
    bottom: 0vmin;
    left: 0vmin;
    right: 0vmin;
    margin: auto;
    width: 100vmin;
    opacity: 0.025;
    filter: blur(3px);
    /* Configurable styles - these will override the defaults */
    position: var(--logo-position, fixed);
    top: var(--logo-top, 0vmin);
    bottom: var(--logo-bottom, 0vmin);
    left: var(--logo-left, 0vmin);
    right: var(--logo-right, 0vmin);
    margin: var(--logo-margin, auto);
    width: var(--logo-width, 100vmin);
    opacity: var(--logo-opacity, 0.025);
    filter: blur(var(--logo-blur, 3px));
    /* new added styles */
    /* background-repeat: var(--engine-background-repeat, no-repeat);
    background-position: var(--engine-background-position, center);
    background-size: var(--engine-background-size, contain);
    box-sizing: var(--engine-background-box-sizing, border-box); */
}

.preview-banner {
    position: fixed;
    z-index: 99999;
    background-color: #15919a;
    color: #fff;
    padding-left: 56px;
    padding-right: 56px;
    padding-top: 8px;
    padding-bottom: 8px;
    right: -50px;
    top: 16px;
    font-weight: 600;
    font-size: 12px;
    transform: rotate(30deg);
    box-shadow: 0px 2px 6px rgba(0, 0, 0, 0.5);
    text-shadow: 0px 2px 6px rgba(0, 0, 0, 0.5);
}
</style>
