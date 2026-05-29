<template>
    <Head title="Survey Editor" />
    <div class="editor-layout bg-slate-200" @click="handleEditorClick">
        <div class="preview-panel" ref="previewPanel">
            <!-- Loading indicator for preview -->
            <div
                v-if="isPreviewLoading"
                class="absolute inset-0 flex items-center justify-center bg-gray-100 z-10 rounded-lg"
            >
                <!-- <div class="loader-track">
                    <div class="loader-thumb"></div>
                </div> -->
            </div>

            <div class="w-full h-full flex flex-col">
                <!-- device toolbar -->
                <div
                    class="flex-shrink-0 h-[50px] flex items-center justify-center"
                >
                    <div>
                        <ControlPanel
                            :isToolbar="true"
                            :brc="brcRef"
                            :maxTotalVariables="maxTotalVariables"
                            :survey="survey"
                            @set-resolution="handleSetResolution"
                            @update-brc="handleUpdateBrc"
                        />
                    </div>
                </div>
                <!-- Preview iframe with optimized loading -->
                <div
                    ref="frameWrapper"
                    class="preview-frame-wrapper flex-1 flex items-center justify-center"
                    :class="{
                        'pt-10': editorStore.activeDevice === 'mobile',
                    }"
                >
                    <iframe
                        ref="previewFrame"
                        :src="previewUrl"
                        class="border-0 shadow-lg rounded-lg transition-all duration-500"
                        :class="[
                            {
                                'opacity-0': isPreviewLoading,
                                'opacity-100': !isPreviewLoading,
                            },
                            iframeClipPathClass,
                        ]"
                        @load="handlePreviewLoad"
                        loading="lazy"
                        allow="fullscreen"
                        sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-presentation"
                    ></iframe>
                </div>
            </div>
        </div>
        <div class="controls-panel">
            <ControlPanel
                ref="controlPanelRef"
                :isToolbar="false"
                :brc="brcRef"
                :maxTotalVariables="maxTotalVariables"
                :survey="survey"
                @set-resolution="handleSetResolution"
                @update-brc="handleUpdateBrc"
            />
        </div>
    </div>
</template>

<script setup>
import { Head } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted, ref, watch, nextTick } from "vue";
import { editorStore } from "./editor/editorStore";
import ControlPanel from "./editor/ControlPanel.vue";
import { DESKTOP_RESOLUTIONS } from "./editor/resolutions";

const controlPanelRef = ref(null);

const props = defineProps({
    survey: {
        type: Object,
        required: true,
    },
    maxTotalVariables: {
        type: Number,
        required: true,
    },
});

const iframeClipPathClass = computed(() => {
    const { activeDevice, selectedMobile, activeBrowser, selectedTablet } =
        editorStore;
    const isChromeLike = ["chrome", "firefox", "edge"].includes(activeBrowser);

    if (activeDevice === "mobile") {
        if (selectedMobile === "iPhone") {
            if (isChromeLike) return "[clip-path:inset(0_0_95px_0)]";
            if (activeBrowser === "safari")
                return "[clip-path:inset(0_0_45px_0)]";
        } else if (selectedMobile === "not-iPhone") {
            if (isChromeLike) return "[clip-path:inset(0_0_50px_0)]";
        }
    }

    if (activeDevice === "tablet") {
        if (selectedTablet === "iPad") {
            if (isChromeLike) return "[clip-path:inset(0_0_50px_0)]";
            if (activeBrowser === "safari") {
                if (!editorStore.isRotated) {
                    return "[clip-path:inset(0_0_50px_0)]";
                } else {
                    return "[clip-path:inset(0_0_75px_0)]";
                }
            }
        } else if (selectedTablet === "not-iPad") {
            if (isChromeLike) return "[clip-path:inset(0_0_60px_0)]";
        }
    }

    return ""; // No clip-path by default
});

const previewFrame = ref(null);
const previewPanel = ref(null);
const activeResolution = ref(DESKTOP_RESOLUTIONS[0]);
let resizeObserver = null;
const frameWrapper = ref(null);
// Manual crop (in CSS pixels) to reduce the visible iframe height in the editor only
// Permanent default is 200px, can be overridden by localStorage key "editorCropBottom"
const DEFAULT_EDITOR_CROP_BOTTOM = 0;
const manualCropBottom = ref(
    Number(localStorage.getItem("editorCropBottom")) ||
        DEFAULT_EDITOR_CROP_BOTTOM
);

// Fixed visual margin around the preview (in pixels)
const PREVIEW_MARGIN = 24;

const brcRef = ref(props.maxTotalVariables > 0 ? 1 : "");
const testIndexRef = ref(props.survey.editor_start_test_index);
const trialIndexRef = ref(props.survey.editor_start_trial_index);

// const tID = ref(0); // Initialize to 0, will be updated from URL in onMounted
const isPreviewLoading = ref(true);

const previewUrl = computed(() => {
    return `/surveys/${props.survey.id}/preview?brc=${brcRef.value}&test=${testIndexRef.value}&trial=${trialIndexRef.value}`;
});

const reloadPreviewFrame = (testIndex = 0, trialIndex = 0) => {
    isPreviewLoading.value = true;
    if (previewFrame.value) {
        const url = new URL(
            previewUrl.value + `&test=${testIndex}&trial=${trialIndex}`,
            window.location.origin
        );
        // Allow cache reuse by avoiding per-reload cache-busting params
        previewFrame.value.src = url.href;
    }
};

const handleSetResolution = (resolution) => {
    activeResolution.value = resolution;
    nextTick(scalePreview); // Rescale on device change
};

const handleUpdateBrc = (value) => {
    brcRef.value = value[0];
    testIndexRef.value = value[1];
    trialIndexRef.value = value[2];
};

// Handle clicks anywhere in the editor to close open panels
const handleEditorClick = () => {
    // Send message to parent to close any open panels
    window.top.postMessage({ type: "CLOSE_OPEN_PANEL" }, "*");
};

// Handle preview iframe load
const handlePreviewLoad = () => {
    isPreviewLoading.value = false;
    applyStylesToIframe();
    sendDeviceConfigToPreview(); // Ensure device state is synced on every load

    // Add click listener to iframe content to close panels
    const iframeDocument = previewFrame.value?.contentWindow?.document;
    if (iframeDocument) {
        iframeDocument.addEventListener("click", () => {
            window.top.postMessage({ type: "CLOSE_OPEN_PANEL" }, "*");
        });
    }

    // Re-run scaling after iframe content is loaded to ensure dimensions are correct
    nextTick(scalePreview);
};

const scalePreview = () => {
    if (!previewPanel.value || !previewFrame.value || !frameWrapper.value)
        return;

    const panelWidth = previewPanel.value.clientWidth;
    const panelHeight = previewPanel.value.clientHeight;
    const virtual = activeResolution.value;

    const availableWidth = Math.max(panelWidth - PREVIEW_MARGIN * 2, 0);
    const availableHeight = Math.max(panelHeight - PREVIEW_MARGIN * 2, 0);

    // Auto subtract browser chrome for non-desktop resolutions
    const { top: chromeTop, bottom: chromeBottom } =
        getAutoChromeHeights(virtual);
    const effectiveHeight = Math.max(
        virtual.height - (chromeTop + chromeBottom),
        1
    );

    // Scale-to-fit (contain) with aspect ratio preserved
    const scale = Math.min(
        availableWidth / virtual.width,
        availableHeight / effectiveHeight
    );

    const scaledWidth = Math.max(virtual.width * scale, 0);
    const scaledHeight = Math.max(effectiveHeight * scale, 0);

    frameWrapper.value.style.width = `${scaledWidth}px`;
    const isPortrait = (virtual?.height || 0) >= (virtual?.width || 0);
    const cropPx = isPortrait ? manualCropBottom.value : 0;
    const croppedHeight = Math.max(scaledHeight - cropPx, 0);
    frameWrapper.value.style.height = `${croppedHeight}px`;
    frameWrapper.value.style.margin = "auto";
    frameWrapper.value.style.overflow = isPortrait ? `hidden` : "unset";

    // Keep the engine rendering at full scaled height; only wrapper clips the view
    previewFrame.value.style.width = `${scaledWidth}px`;
    previewFrame.value.style.height = `${scaledHeight}px`;
    previewFrame.value.style.display = `block`;
    previewFrame.value.style.transform = ``;

    previewPanel.value.style.overflowY = isPortrait ? "hidden" : "unset";
};

// Heuristic for subtracting browser chrome heights (in CSS px)
function getAutoChromeHeights(resolution) {
    // Treat large widths as desktop (no chrome subtraction)
    if (resolution?.width >= 1200) return { top: 0, bottom: 0 };

    const isPortrait = (resolution?.height || 0) >= (resolution?.width || 0);

    // Generic defaults that approximate iOS/Android chrome
    if (isPortrait) {
        // Mobile/tablet portrait: status/url bar + home/gesture area
        return { top: 44, bottom: 34 };
    }
    // Landscape on smaller devices (URL bar thinner, nav smaller)
    return { top: 24, bottom: 24 };
}

// Listen for engine position updates to sync control panel selection
const handleEngineMessage = (event) => {
    const data = event?.data || {};
    if (data.type === "ENGINE_POSITION") {
        const t = Number(data.testIndex) || 0;
        const tr = Number(data.trialIndex) || 0;
        const tests = data.tests || {};
        // editorStore.setNavigation(t, tr);
        editorStore.currentTestIndex = t;
        editorStore.currentTrialIndex = tr;
        editorStore.surveyStructure.tests = tests;
    }
};

// Initialize the store and set up event listeners when the component mounts
onMounted(() => {
    // Announce readiness to the parent window so it can send initial state
    window.top.postMessage({ type: "EDITOR_READY" }, "*");

    // Initialize with landscape orientation by default
    editorStore.initialize(props.survey, "landscape");

    // Use ResizeObserver to automatically rescale the preview
    resizeObserver = new ResizeObserver(scalePreview);
    if (previewPanel.value) {
        resizeObserver.observe(previewPanel.value);
    }

    nextTick(scalePreview);

    window.addEventListener("message", handleEngineMessage);
    window.__editorMsgHandler = handleEngineMessage;

    // Expose a simple hook to adjust crop from devtools or future controls
    window.setEditorCropBottom = (px) => {
        const n = Math.max(Number(px) || 0, 0);
        manualCropBottom.value = n;
        localStorage.setItem("editorCropBottom", String(n));
        nextTick(scalePreview);
    };

    // Listen for reload command from parent (Create2.vue)
    const handleParentMessage = (event) => {
        if (event.origin !== window.location.origin) {
            return;
        }
        if (event.data.type === "GET_EDITOR_DEVICE_INFO") {
            // Respond with current device and orientation info
            event.source.postMessage(
                {
                    type: "EDITOR_DEVICE_INFO_RESPONSE",
                    payload: {
                        activeDevice: editorStore.activeDevice,
                        currentOrientation: editorStore.currentOrientation,
                    },
                },
                event.origin
            );
            return;
        }
        if (event.data.type === "RELOAD_PREVIEW") {
            let testIndex = event.data.testIndex;
            let trialIndex = event.data.trialIndex;
            reloadPreviewFrame(testIndex, trialIndex);
        }
        if (event.data.type === "DATA_DIRTY_STATE") {
            editorStore.isDataDirty = event.data.isDirty;
            editorStore.hasUnsavedChanges = event.data.isDirty;
        }
        // Listen for navigation commands from the parent window
        if (event.data.type === "PARENT_NAVIGATE") {
            editorStore.setNavigation(
                event.data.testIndex,
                event.data.trialIndex
            );
        }
        // Listen for style updates from AI execution
        if (event.data.type === "STYLES_UPDATED") {
            // Fetch fresh survey data from the server
            fetch(`/api/surveys/${props.survey.id}/editor-data`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'same-origin'
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch survey data');
                    }
                    return response.json();
                })
                .then(data => {
                    // Re-initialize the editor store with fresh data
                    editorStore.initialize(data.survey, editorStore.currentOrientation);
                    // Force reload of current orientation styles to pick up test-specific updates
                    const currentTheme = editorStore.getTheme();
                    editorStore.loadThemeOrientationStyles(currentTheme, editorStore.currentOrientation);
                    // Re-apply styles to iframe
                    applyStylesToIframe();
                })
                .catch(error => {
                    console.error('Failed to reload styles:', error);
                    // Fallback: try to reload from props (may be stale)
                    editorStore.initialize(props.survey, editorStore.currentOrientation);
                    applyStylesToIframe();
                });
        }
        
        // Listen for test data updates (primes, targets, instructions) from AI execution
        if (event.data.type === "TEST_DATA_UPDATED") {
            const testId = event.data.test_id;
            const surveyId = event.data.survey_id || props.survey.id;
            
            // Fetch fresh test data from the server
            fetch(`/api/surveys/${surveyId}/tests/${testId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'same-origin'
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch test data');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.test) {
                        // Find the test index in the current survey data
                        const currentTests = editorStore.surveyStructure?.tests || [];
                        const testIndex = currentTests.findIndex(t => t.id === testId);
                        
                        if (testIndex !== -1 && editorStore.surveyStructure) {
                            // Update the specific test in the store
                            const updatedTests = [...currentTests];
                            updatedTests[testIndex] = data.test;
                            
                            // Update the editor store with the new test data
                            editorStore.surveyStructure.tests = updatedTests;
                            
                            // Forward the test data to parent window (Create2.vue) so it can update the sidebar dataStore
                            if (window.parent && window.parent !== window) {
                                window.parent.postMessage(
                                    {
                                        type: "TEST_DATA_UPDATED",
                                        test_id: testId,
                                        survey_id: surveyId,
                                        test_data: data.test
                                    },
                                    "*"
                                );
                            }
                            
                            // If we're currently viewing this test, reload the preview
                            if (editorStore.currentTestIndex === testIndex) {
                                reloadPreviewFrame(testIndex, editorStore.currentTrialIndex);
                            }
                        } else {
                            // Forward the test data to parent window even if test not found in editorStore
                            if (window.parent && window.parent !== window) {
                                window.parent.postMessage(
                                    {
                                        type: "TEST_DATA_UPDATED",
                                        test_id: testId,
                                        survey_id: surveyId,
                                        test_data: data.test
                                    },
                                    "*"
                                );
                            }
                            
                            // Test not found in current data, reload entire survey
                            fetch(`/api/surveys/${surveyId}/editor-data`, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                                },
                                credentials: 'same-origin'
                            })
                                .then(response => response.json())
                                .then(surveyData => {
                                    editorStore.initialize(surveyData.survey, editorStore.currentOrientation);
                                })
                                .catch(error => {
                                    console.error('Failed to reload survey data:', error);
                                });
                        }
                    }
                })
                .catch(error => {
                    console.error('Failed to reload test data:', error);
                    // Fallback: reload entire survey data
                    fetch(`/api/surveys/${surveyId}/editor-data`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        credentials: 'same-origin'
                    })
                        .then(response => response.json())
                        .then(surveyData => {
                            editorStore.initialize(surveyData.survey, editorStore.currentOrientation);
                        })
                        .catch(err => {
                            console.error('Failed to reload survey data:', err);
                        });
                });
        }
        if (event.data.type === "SAVE_TEST_STYLES") {
            controlPanelRef.value?.saveChanges();
        }
        if (event.data.type === "DISCARD_TEST_STYLES") {
            controlPanelRef.value?.discardChanges();
            editorStore.isDataDirty = false;
            editorStore.hasUnsavedChanges = false;
        }
    };
    window.addEventListener("message", handleParentMessage);
    window.__editorParentMsgHandler = handleParentMessage;
});

// Clean up the event listener when the component is unmounted
onUnmounted(() => {
    if (resizeObserver) {
        resizeObserver.disconnect();
    }
    if (window.__editorMsgHandler) {
        window.removeEventListener("message", window.__editorMsgHandler);
        delete window.__editorMsgHandler;
    }
    if (window.__editorParentMsgHandler) {
        window.removeEventListener("message", window.__editorParentMsgHandler);
        delete window.__editorParentMsgHandler;
    }
});

/**
 * Posts the current device configuration to the preview iframe.
 * This is critical for ensuring the preview uses the correct layout rules,
 * especially after a reload.
 */
const sendDeviceConfigToPreview = () => {
    const win = previewFrame.value?.contentWindow;
    if (win) {
        win.postMessage(
            {
                type: "EDITOR_DEVICE_CHANGE",
                payload: {
                    device: editorStore.activeDevice,
                    browser: editorStore.activeBrowser,
                    mobile: editorStore.selectedMobile,
                    tablet: editorStore.selectedTablet,
                },
            },
            "*"
        );
    }
};

/**
 * Applies the current styles from the editor store to the iframe's root element.
 * Also handles initial navigation to the correct test if tID is provided.
 */
const applyStylesToIframe = () => {
    const iframeDocument = previewFrame.value?.contentWindow?.document;
    if (iframeDocument && iframeDocument.documentElement) {
        Object.entries(editorStore.styles).forEach(([key, value]) => {
            iframeDocument.documentElement.style.setProperty(key, value);
        });
    }
};

// Watch for changes in the editor store and apply them to the iframe in real-time
watch(
    () => editorStore.styles,
    () => {
        applyStylesToIframe();
    },
    { deep: true }
);

// Post navigation messages to iframe to avoid reloading the preview
watch(
    [() => editorStore.currentTestIndex, () => editorStore.currentTrialIndex],
    () => {
        if (!editorStore.isNavigating) {
            return;
        }
        const win = previewFrame.value?.contentWindow;
        if (win && editorStore.isNavigating) {
            win.postMessage(
                {
                    type: "EDITOR_NAVIGATE",
                    testIndex: editorStore.currentTestIndex,
                    trialIndex: editorStore.currentTrialIndex,
                },
                "*"
            );
        }
    },
    { immediate: true }
);

watch(
    () => [
        editorStore.activeDevice,
        editorStore.activeBrowser,
        editorStore.selectedMobile,
        editorStore.selectedTablet,
    ],
    () => {
        sendDeviceConfigToPreview();
    },
    { deep: true, immediate: true }
);
</script>

<style scoped>
.editor-layout {
    display: flex;
    height: 100vh;
    width: 100vw;
}

.preview-panel {
    flex: 1;
    display: flex;
    align-items: center;
    height: 97% !important;
    justify-content: center;
    /* overflow: hidden; */
    position: relative;
    /* padding: 2rem; */
    /* Performance optimizations */
    will-change: transform;
    transform: translateZ(0);
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
}

.preview-frame-wrapper {
    aspect-ratio: 16 / 9; /* will be overridden inline */
    position: relative;
}

/* Subtle shadow rendered OUTSIDE the cropped area (below the wrapper) */
/* .preview-frame-wrapper::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    top: 100%;
    height: 18px;
    pointer-events: none;
    background: linear-gradient(
        to bottom,
        rgba(0, 0, 0, 0),
        rgba(0, 0, 0, 0.12)
    );
    border-bottom-left-radius: 12px;
    border-bottom-right-radius: 12px;
} */

.controls-panel {
    width: 380px;
    flex-shrink: 0;
    overflow-y: auto;
    background-color: #ffffff;
    border-left: 1px solid #d1d5db; /* gray-300 */
    box-shadow: -5px 0 15px rgba(0, 0, 0, 0.05);
    /* Performance optimizations */
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
}

/* Optimize iframe performance */
iframe {
    will-change: transform;
    transform: translateZ(0);
    -webkit-backface-visibility: hidden;
    backface-visibility: hidden;
}

/* Inline loader: 300px track with a green bar moving left↔right */
.loader-track {
    position: absolute;
    top: 0;
    left: 0;
    width: 180vmin;
    height: 2px;
    background: #e5e7eb; /* gray-200 */
    border-radius: 2px;
    overflow: hidden;
}

.loader-thumb {
    position: absolute;
    top: 0;
    left: 0;
    width: 600px;
    height: 100%;
    background: #08c6d4;
    border-radius: 2px;
    animation: loader-slide 1s ease-in-out infinite alternate;
    will-change: transform;
    transform: translateZ(0);
}

@keyframes loader-slide {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(180vmin);
    }
}
</style>
