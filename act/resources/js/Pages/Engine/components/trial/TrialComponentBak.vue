<template>
    <component :is="'style'">
        {{
            `
        .trial-descriptive .descriptive-image-1 {
            width: var(--descriptive-img-1-width, 20vmin);
            height: auto;
            margin-top: var(--descriptive-img-1-margin-top, 1vmin);
            margin-bottom: var(--descriptive-img-1-margin-bottom, 1vmin);
            margin-left: var(--descriptive-img-1-margin-horizontal, 0);
            margin-right: var(--descriptive-img-1-margin-horizontal, 0);
            display: var(--descriptive-img-1-display, block);
        }
        .trial-descriptive .descriptive-image-1[data-display="block"] {
            margin-left: auto;
            margin-right: auto;
        }
        .trial-descriptive .descriptive-image-2 {
            width: var(--descriptive-img-2-width, 20vmin);
            height: auto;
            margin-top: var(--descriptive-img-2-margin-top, 1vmin);
            margin-bottom: var(--descriptive-img-2-margin-bottom, 1vmin);
            margin-left: var(--descriptive-img-2-margin-horizontal, 0);
            margin-right: var(--descriptive-img-2-margin-horizontal, 0);
            display: var(--descriptive-img-2-display, block);
        }
        .trial-descriptive .descriptive-image-2[data-display="block"] {
            margin-left: auto;
            margin-right: auto;
        }
        .trial-descriptive .descriptive-image-3 {
            width: var(--descriptive-img-3-width, 20vmin);
            height: auto;
            margin-top: var(--descriptive-img-3-margin-top, 1vmin);
            margin-bottom: var(--descriptive-img-3-margin-bottom, 1vmin);
            margin-left: var(--descriptive-img-3-margin-horizontal, 0);
            margin-right: var(--descriptive-img-3-margin-horizontal, 0);
            display: var(--descriptive-img-3-display, block);
        }
        .trial-descriptive .descriptive-image-3[data-display="block"] {
            margin-left: auto;
            margin-right: auto;
        }
        .trial-descriptive .descriptive-image-4 {
            width: var(--descriptive-img-4-width, 20vmin);
            height: auto;
            margin-top: var(--descriptive-img-4-margin-top, 1vmin);
            margin-bottom: var(--descriptive-img-4-margin-bottom, 1vmin);
            margin-left: var(--descriptive-img-4-margin-horizontal, 0);
            margin-right: var(--descriptive-img-4-margin-horizontal, 0);
            display: var(--descriptive-img-4-display, block);
        }
        .trial-descriptive .descriptive-image-4[data-display="block"] {
            margin-left: auto;
            margin-right: auto;
        }
        .trial-descriptive .descriptive-image-5 {
            width: var(--descriptive-img-5-width, 20vmin);
            height: auto;
            margin-top: var(--descriptive-img-5-margin-top, 1vmin);
            margin-bottom: var(--descriptive-img-5-margin-bottom, 1vmin);
            margin-left: var(--descriptive-img-5-margin-horizontal, 0);
            margin-right: var(--descriptive-img-5-margin-horizontal, 0);
            display: var(--descriptive-img-5-display, block);
        }
        .trial-descriptive .descriptive-image-5[data-display="block"] {
            margin-left: auto;
            margin-right: auto;
        }
    `
        }}
    </component>
    <div class="w-full h-full relative">
        <!-- Fullscreen Prompt Overlay -->
        <TrialFullscreenModal
            v-if="!isEditorMode"
            :show="showFullscreenPrompt"
            :message="fullscreen?.message"
            :buttonText="fullscreen?.button"
            @close="showFullscreenPrompt = false"
            @enterFullscreen="enterFullscreen"
        />

        <Transition name="test-fade" mode="out-in">
            <div
                :key="currentTestKey"
                class="w-full trial-container h-screen flex flex-col items-center justify-center"
                v-if="surveyTestState.currentTest"
            >
                <!-- Descriptive Trial Container - Using flex to create space between title and button -->
                <div
                    v-if="isDescriptive"
                    class="flex flex-col h-screen w-full overflow-scroll py-20"
                >
                    <div class="flex-1"></div>
                    <!-- Descriptive Title in the middle -->
                    <div
                        class="trial-descriptive-container flex items-center justify-center mx-auto"
                        :style="trialDescriptiveContainerStyle"
                    >
                        <div
                            class="trial-descriptive"
                            :style="trialDescriptiveStyle"
                            v-if="
                                surveyTestState.currentTest.config.title
                                    .length > 0
                            "
                            v-html="processedDescriptiveTitle"
                        ></div>
                    </div>

                    <!-- <div class="h-10"></div> -->

                    <!-- Next Button -->
                    <div
                        class="flex items-center justify-center"
                        v-if="isPrimeAsNext"
                        :style="trialNextButtonContainerStyle(isLandscape)"
                    >
                        <TrialNextButton
                            :is-last="isLastTrial"
                            @click="handleDescriptiveNext()"
                        />
                    </div>
                    <div class="flex-1"></div>
                </div>

                <!-- Main Trial Area -->
                <div
                    class="flex flex-col h-screen w-full"
                    v-if="!isDescriptive"
                >
                    <!-- Three-section grid layout -->
                    <div
                        class="h-full w-full grid"
                        :style="getGridLayout(isLandscape, isIphoneLandscape)"
                    >
                        <!-- Header Text - Adjusts based on available space -->
                        <div class="w-full flex items-center justify-center">
                            <div
                                ref="trialHeader"
                                class="trial-header landscape:text-[2.3vmin] text-[3.2vmin] text-center"
                                :style="trialHeaderStyle"
                                v-if="config.main_header"
                                v-html="getProxiedHtml(config.main_header)"
                            ></div>
                        </div>

                        <!-- Prime & Target / Attribute Circles - Fixed at 50% height (portrait) or 70% height (landscape) -->
                        <div class="w-full flex items-center justify-center">
                            <Transition
                                name="trial-fade"
                                mode="out-in"
                                @enter="onTrialEnter"
                                @leave="onTrialLeave"
                            >
                                <div
                                    v-if="surveyTestState.currentTrial"
                                    :key="trialPrime"
                                    class="trial-prime-target-container w-full h-full flex items-center justify-center"
                                    :style="trialPrimeTargetContainerStyle"
                                >
                                    <div
                                        :style="[
                                            trialPrimeTargetContainer2Style,
                                            {
                                                width: 'var(--wrapper-width)',
                                                height: 'var(--wrapper-height)',
                                            },
                                        ]"
                                        class="trial-prime-target-container-2 relative"
                                    >
                                        <!-- Prime Display -->
                                        <div
                                            class="trial-prime-container"
                                            v-if="
                                                displayTrial?.prime?.display !==
                                                false
                                            "
                                        >
                                            <div
                                                class="trial-prime-container-2"
                                                :class="{
                                                    'w-[40vmin] h-[40vmin]':
                                                        surveyTestState.theme ==
                                                        2,
                                                    'w-[68vmin] h-[68vmin] landscape:w-[55vmin] landscape:h-[55vmin]':
                                                        surveyTestState.theme ==
                                                        1,
                                                    'cursor-pointer':
                                                        !isSingleTarget,
                                                    'landscape:w-[45vmin] landscape:h-[45vmin]':
                                                        isIphoneLandscape,
                                                }"
                                            >
                                                <div
                                                    id="trial-prime-handle"
                                                    :key="currentTrialKey"
                                                    role="button"
                                                    @touchstart="
                                                        handlePrimeTouchStart
                                                    "
                                                    @touchend="
                                                        handlePrimeTouchEnd
                                                    "
                                                    @mouseover="
                                                        handlePrimeMouseOver
                                                    "
                                                    @mouseout="
                                                        handlePrimeMouseOut
                                                    "
                                                    @click="
                                                        isPrimeAsNext &&
                                                        !isClickDisabled &&
                                                        isTrialReady
                                                            ? handlePrimeClick()
                                                            : null
                                                    "
                                                    class="trial-prime text-black text-[2.7vmin] m-[13vmin]"
                                                    :class="{
                                                        'text-[2.2vmin] landscape:text-[2.5vmin] m-[2vmin] max-w-[80%]':
                                                            surveyTestState.theme ===
                                                            2,
                                                        'flex text-center items-center justify-center':
                                                            surveyTestState.theme ===
                                                            3,
                                                        'active:opacity-100 active:scale-[1.05] landscape:active:scale-110':
                                                            isPrimeAsNext,
                                                        'active:opacity-80 active:scale-[1.15]':
                                                            isPrimeAsNext &&
                                                            isMobile,
                                                        'hover:opacity-70':
                                                            !isMobile &&
                                                            isPrimeAsNext,
                                                        'hover:text-green-600':
                                                            isPrimeAsNext &&
                                                            !isIphone,
                                                        'landscape:text-[2vmin]':
                                                            isIphoneLandscape,
                                                        'landscape:text-[2.5vmin]':
                                                            !isIphoneLandscape,
                                                        'active:text-green-600':
                                                            isPrimeActive,
                                                        'active:text-black':
                                                            !isPrimeActive,
                                                    }"
                                                    v-html="
                                                        getProxiedHtml(
                                                            displayTrial?.prime
                                                                ?.prime
                                                        )
                                                    "
                                                ></div>
                                            </div>
                                        </div>
                                        <!-- Targets -->
                                        <TransitionGroup name="target-fade">
                                            <div
                                                :class="{
                                                    'grid grid-cols-3 gap-4':
                                                        surveyTestState.theme ==
                                                        3,
                                                }"
                                            >
                                                <div
                                                    v-for="(
                                                        target, index
                                                    ) in displayTrial?.targets ||
                                                    []"
                                                    :key="target.value"
                                                    class="trial-target-container"
                                                >
                                                    <div
                                                        @touchstart=""
                                                        @touchend=""
                                                        v-if="isTrialReady"
                                                        class="trial-target-container-2 landscape:border-[0.95vmin] border-[1.2vmin] text-black border-gray-300/70 bg-white"
                                                        :class="{
                                                            'w-[30vmin] h-[30vmin] landscape:w-[23vmin] landscape:h-[23vmin]':
                                                                surveyTestState.theme ==
                                                                1,
                                                            'w-[24vmin] h-[24vmin] landscape:w-[20vmin] landscape:h-[20vmin] border-[0.8vmin]':
                                                                surveyTestState.theme ==
                                                                2,
                                                            'border-[0.8vmin] rounded-xl flex text-center items-center justify-center landscape:h-[13vmin]':
                                                                surveyTestState.theme ==
                                                                3,
                                                            'hover:scale-105 transition-all duration-300 ease-in-out':
                                                                surveyTestState.theme ==
                                                                    2 &&
                                                                !isMobile &&
                                                                !isTargetSelected(
                                                                    target
                                                                ),
                                                            'landscape:w-[20vmin] landscape:h-[20vmin] landscape:text-[1.6vmin]':
                                                                isIphoneLandscape,
                                                            'landscape:text-[2vmin]':
                                                                !isIphoneLandscape,
                                                            'active:scale-[0.90] hover:shadow-xl':
                                                                !isClickDisabled ||
                                                                isMobile,
                                                        }"
                                                        @click="
                                                            !isClickDisabled &&
                                                            isTrialReady
                                                                ? handleTargetClick(
                                                                      target
                                                                  )
                                                                : null
                                                        "
                                                        @mouseover="
                                                            !isClickDisabled &&
                                                            isTrialReady
                                                                ? (hoveredTarget =
                                                                      target.value)
                                                                : null
                                                        "
                                                        @mouseout="
                                                            !isClickDisabled &&
                                                            isTrialReady
                                                                ? (hoveredTarget =
                                                                      null)
                                                                : null
                                                        "
                                                    >
                                                        <div
                                                            class="trial-target"
                                                            v-html="
                                                                getProxiedHtml(
                                                                    target.target
                                                                )
                                                            "
                                                        ></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </TransitionGroup>
                                    </div>
                                </div>
                            </Transition>

                            <!-- Error Message -->
                            <div class="absolute">
                                <TrialError
                                    :show="showError"
                                    :message="errorMessage"
                                    @close="showError = false"
                                    :cssStyle="{}"
                                    :delayDuration="2000"
                                />
                            </div>
                        </div>

                        <!-- Bottom Text - Adjusts based on available space -->
                        <div
                            class="w-full flex items-center justify-center w-full"
                            :style="trialBottomTextContainerStyle"
                        >
                            <div
                                v-if="config.bottom_text && isPrimeAsNext"
                                class="trial-bottom-text max-w-[75%] text-center"
                                :style="trialBottomTextStyle"
                                v-html="getProxiedHtml(config.bottom_text)"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from "vue";
import { useTrialLogic } from "../../utils/trialLogic";
import { surveyTestState } from "../../utils/surveyTestStore";
import { useDynamicStyles } from "../../utils/dynamicStyling";
import TrialNextButton from "./TrialNextButton.vue";
import TrialError from "./TrialError.vue";
import TrialFullscreenModal from "./TrialFullscreenModal.vue";
import {
    getCircularLayoutStyles,
    primeStyle,
    targetBaseStyle,
    trialDescriptiveContainerStyle,
    trialDescriptiveStyle,
    trialHeaderContainerStyle,
    trialHeaderStyle,
    trialPrimeTargetContainerStyle,
    trialPrimeTargetContainer2Style,
    trialPrimeContainerStyle,
    trialPrimeStyle,
    trialTargetContainerStyle,
    trialTargetStyle,
    targetSelectedStyle,
    targetHoverStyle,
    targetInteractiveStyle,
    getThemeTargetStyle,
    trialBottomTextContainerStyle,
    trialBottomTextStyle,
    trialNextButtonContainerStyle,
    getGridLayout,
} from "../../utils/trialStyling";
import { useLogger } from "../../utils/logger.js";
const { _log } = useLogger();
// Transform all <img src> inside provided HTML to proxy URLs (editor mode only)
function getProxiedHtml(html) {
    try {
        if (!html || typeof html !== "string") return html;
        if (!isEditorMode.value) return html;
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

// Props
const props = defineProps({
    config: {
        type: Object,
        required: true,
    },
    id: {
        type: Number,
        required: true,
    },
});

const showFullscreenPrompt = ref(false);
const isLandscape = ref(window.innerWidth > window.innerHeight);

const hoveredTarget = ref(null);
const trialHeader = ref(null);
const trialStartTimer = ref(props.config?.target_delay || 1500);

const isIphone = ref(/iPhone/.test(navigator.userAgent) && !window.MSStream);
const isMobile = ref(/Android|iPhone|iPad|iPod/i.test(navigator.userAgent));

const buttonDelay = ref(0);

// Check if we are in the editor context
const isEditorMode = computed(() => {
    return (
        surveyTestState.currentSurveyConfig?.editor_start_test_index !==
        undefined
    );
});

// Inject dynamic CSS from survey config
useDynamicStyles(() => surveyTestState.currentSurvey?.config?.css);

const customStyles = computed(() => {
    return surveyTestState.currentSurveyConfig?.styles || {};
});

const circularTargetStyles = computed(() => {
    const numTargets = displayTrial.value?.targets?.length || 0;
    if (numTargets === 0) return [];
    // Pass the current theme and orientation to the layout function
    const theme = surveyTestState.theme || 1;
    return getCircularLayoutStyles(numTargets, 40, theme, isLandscape.value);
});

const fullscreen = computed(() => {
    return (
        surveyTestState.surveyData.surveys[surveyTestState.surveyIndex].config
            ?.fullscreen || null
    );
});

const targetLimitReached = computed(() => {
    return props.config?.limit_reach || "Select limit";
});

function getTargetStyle(target, index) {
    const style = { ...targetBaseStyle };
    const theme = surveyTestState.theme;

    // For theme 2, apply color styling based on position
    if (theme === 2) {
        const targets = displayTrial.value?.targets || [];
        const totalTargets = targets.length;
        const targetIndex = targets.findIndex((t) => t.value === target.value);

        if (targetIndex !== -1) {
            Object.assign(
                style,
                getThemeTargetStyle(targetIndex, totalTargets, theme)
            );
        } else {
            Object.assign(style, getThemeTargetStyle(0, totalTargets, theme));
        }
    }

    // Apply selected styles if needed
    if (isTargetSelected(target)) {
        // Pass the current theme to get appropriate selected style
        Object.assign(
            style,
            targetSelectedStyle(theme, index, props.config.display_by)
        );
    }

    // Apply hover style if needed
    if (
        hoveredTarget.value === target.value &&
        !isMobile.value &&
        !isTargetSelected(target)
    ) {
        Object.assign(style, targetHoverStyle);
    }

    // Make targets interactive when ready
    if (!isClickDisabled.value && isTrialReady.value) {
        Object.assign(style, targetInteractiveStyle);
    }

    return style;
}

const getInitialTargetStyle = (index) => {
    if (!circularTargetStyles.value[index]) return {};

    const finalStyle = circularTargetStyles.value[index];
    const theme = surveyTestState.theme || 1;

    if (theme === 2) {
        // For theme 2, animate from outside-in, similar to theme 1
        const centerX = isLandscape.value ? 35 : 44;
        const centerY = isLandscape.value ? 35 : 44;

        const finalLeft = parseFloat(finalStyle.left);
        const finalTop = parseFloat(finalStyle.top);

        // Vector from center to final position
        const dx = finalLeft - centerX;
        const dy = finalTop - centerY;

        // Extend the vector by a factor to place it "outside"
        const extensionFactor = 1.08;
        const initialLeft = centerX + dx * extensionFactor;
        const initialTop = centerY + dy * extensionFactor;

        return {
            left: `${initialLeft}vmin`,
            top: `${initialTop}vmin`,
            opacity: 0,
        };
    } else {
        // Original theme 1 circular animation
        const angle = finalStyle.angle;
        const radius = 43; // Start farther out
        return {
            left: `${50 + radius * Math.cos(angle)}%`,
            top: `${50 + radius * Math.sin(angle)}%`,
            opacity: 0,
        };
    }
};

const getExitTargetStyle = (index) => {
    if (!circularTargetStyles.value[index]) return {};

    const finalStyle = circularTargetStyles.value[index];
    const theme = surveyTestState.theme || 1;

    if (theme === 2) {
        // For theme 2, exit to the same "outside" position
        const centerX = isLandscape.value ? 35 : 44;
        const centerY = isLandscape.value ? 35 : 44;

        const finalLeft = parseFloat(finalStyle.left);
        const finalTop = parseFloat(finalStyle.top);

        const dx = finalLeft - centerX;
        const dy = finalTop - centerY;

        const extensionFactor = 1.1;
        const exitLeft = centerX + dx * extensionFactor;
        const exitTop = centerY + dy * extensionFactor;

        return {
            left: `${exitLeft}vmin`,
            top: `${exitTop}vmin`,
        };
    } else {
        // Original theme 1 circular exit animation
        const angle = finalStyle.angle;
        const radius = 43; // Move farther out
        return {
            left: `${50 + radius * Math.cos(angle)}%`,
            top: `${50 + radius * Math.sin(angle)}%`,
        };
    }
};

// Get current trial from store
const currentTrial = computed(() => {
    return surveyTestState.currentTrial;
});

const trialPrime = computed(() => {
    return currentTrial.value?.prime?.prime;
});

const selectedCount = ref(0);
const errorMessage = ref("");
const shuffledTrial = ref(null);

// Transition state
const isTransitioning = ref(false);

// Prevent multiple clicks
const isClickDisabled = ref(true); // Start disabled for new trials

// Track if trial is fully loaded and ready for interaction
const isTrialReady = ref(false);

// Use shuffled trial if random is enabled, otherwise use normal trial
const displayTrial = computed(() => {
    return props.config.random && shuffledTrial.value
        ? shuffledTrial.value
        : currentTrial.value;
});

const currentTrialID = computed(() => {
    return props.id;
});

// Unique key for each trial to trigger transitions
const currentTrialKey = computed(() => {
    return `${surveyTestState.surveyIndex}-${surveyTestState.surveyItemIndex}-${surveyTestState.trialItemIndex}`;
});

const currentTestKey = computed(() => {
    return `${surveyTestState.surveyIndex}-${surveyTestState.surveyItemIndex}`;
});

const reactiveConfig = computed(() => props.config);

// Use trial logic composable with display trial (handles random shuffling)
const {
    canProceed,
    startTrial,
    startResponseTimer,
    selectTarget,
    proceedToNextTrial,
    isTargetSelected,
    isLastTrial,
} = useTrialLogic(displayTrial, reactiveConfig, currentTrialID);

const isDescriptive = computed(() => {
    return surveyTestState.currentTest.config?.type === "descriptive";
});

const isPrimeAsNext = computed(() => {
    return surveyTestState.currentTest.config?.prime_as_next;
});

const isSingleTarget = computed(() => {
    return surveyTestState.currentTest.config?.single_select;
});

const showError = ref(false);

// Emits
const emit = defineEmits(["trial-complete"]);

// Transition methods
const onTrialEnter = (el) => {
    isTransitioning.value = true;
};

const onTrialLeave = (el) => {
    isTransitioning.value = false;
};
const isPrimeActive = ref(false);
const isPrimeHovered = ref(false);
// Methods
const handlePrimeTouchStart = () => {
    if (canProceed.value && !isClickDisabled.value && isTrialReady.value) {
        isPrimeActive.value = true;
    }
};
const handlePrimeTouchEnd = () => {
    setTimeout(() => {
        isPrimeActive.value = false;
    }, 500);
};

const handlePrimeMouseOver = () => {
    isPrimeHovered.value = true;
};

const handlePrimeMouseOut = () => {
    isPrimeHovered.value = false;
};

const handlePrimeClick = () => {
    if (canProceed.value && !isClickDisabled.value && isTrialReady.value) {
        isClickDisabled.value = true; // Disable all clicks immediately
        showError.value = false;
        setTimeout(() => {
            selectedCount.value = 0;
            proceedToNextTrial();
        }, 300);
    } else {
        showError.value = true;
        errorMessage.value = props.config.empty_selection;
    }
};

const handleTargetClick = (target) => {
    if (isClickDisabled.value || !isTrialReady.value) return; // Early return if clicks are disabled or trial not ready

    if (isSingleTarget.value) {
        selectTarget(target);
        isClickDisabled.value = true;
        setTimeout(() => {
            proceedToNextTrial();
        }, 300);
    } else {
        // Special handling for targets with can_unselect = true
        if (target.can_unselect === true) {
            // If clicking on a can_unselect target, unselect all other targets first
            const currentlySelectedTargets =
                displayTrial.value?.targets?.filter((t) =>
                    isTargetSelected(t)
                ) || [];

            // Unselect all currently selected targets
            currentlySelectedTargets.forEach((selectedTarget) => {
                if (selectedTarget.value !== target.value) {
                    selectTarget(selectedTarget); // This will unselect it
                }
            });

            // Update selectedCount to reflect only the can_unselect targets
            const canUnselectTargetsSelected = currentlySelectedTargets.filter(
                (t) => t.can_unselect === true
            );
            selectedCount.value = canUnselectTargetsSelected.length;

            // Now handle the clicked can_unselect target
            if (isTargetSelected(target)) {
                selectedCount.value--;
                selectTarget(target);
            } else {
                selectedCount.value++;
                selectTarget(target);
            }
        } else {
            // Normal target handling - but first check if we need to unselect can_unselect targets
            if (!isTargetSelected(target)) {
                // If selecting a normal target, unselect all can_unselect targets first
                const currentlySelectedTargets =
                    displayTrial.value?.targets?.filter((t) =>
                        isTargetSelected(t)
                    ) || [];
                const canUnselectTargetsSelected =
                    currentlySelectedTargets.filter(
                        (t) => t.can_unselect === true
                    );

                // Unselect all can_unselect targets
                canUnselectTargetsSelected.forEach((canUnselectTarget) => {
                    selectTarget(canUnselectTarget); // This will unselect it
                    selectedCount.value--;
                });
            }

            if (
                selectedCount.value < props.config.limit ||
                props.config.limit == 0
            ) {
                if (isTargetSelected(target)) {
                    selectedCount.value--;
                    selectTarget(target);
                } else {
                    selectedCount.value++;
                    selectTarget(target);
                }
            } else {
                if (isTargetSelected(target)) {
                    selectedCount.value--;
                    selectTarget(target);
                } else {
                    errorMessage.value = `${targetLimitReached.value} ${props.config.limit}`;
                    showError.value = true;
                    return;
                }
            }
        }
    }
};

const handleDescriptiveNext = () => {
    if (isClickDisabled.value || !isTrialReady.value) return;
    isClickDisabled.value = true;
    setTimeout(() => {
        proceedToNextTrial();
    }, 100);
};

// Function to enable trial interaction after delay
// Start the response timer now that the trial is ready for interaction
const enableTrialInteraction = () => {
    // uses trialStartTimer.value
    // For non-descriptive trials, apply 1500ms delay
    // For descriptive trials, apply 500ms delay
    setTimeout(
        () => {
            isTrialReady.value = true;
            isClickDisabled.value = false;
            startResponseTimer();
        },
        isDescriptive.value ? 500 : parseInt(trialStartTimer.value)
    );
};

const updateOrientation = () => {
    isLandscape.value = window.innerWidth > window.innerHeight;
};

const handleFullscreenChange = () => {
    // When the fullscreen state changes, we need to manually trigger an orientation
    // update. This is crucial because some mobile browsers do not reliably fire
    // 'resize' or 'orientationchange' events after entering fullscreen, which
    // would leave the layout in its old (portrait) state.
    updateOrientation();
};

const enterFullscreen = () => {
    const el = document.documentElement;
    const requestMethod =
        el.requestFullscreen ||
        el.webkitRequestFullscreen ||
        el.mozRequestFullScreen ||
        el.msRequestFullscreen;

    if (requestMethod) {
        requestMethod.call(el).catch((err) => {
            _log(
                `Error attempting to enable full-screen mode: ${err.message} (${err.name})`
            );
        });
    }
    showFullscreenPrompt.value = false;
};

const exitFullscreen = () => {
    const exitMethod =
        document.exitFullscreen ||
        document.webkitExitFullscreen ||
        document.mozCancelFullScreen ||
        document.msExitFullscreen;
    if (exitMethod) {
        exitMethod.call(document);
    }
};

const isIphoneLandscape = ref(false);

watch(isLandscape, (newIsLandscape) => {
    // Check for iPhone. The Fullscreen API for documentElement is not supported on iPhone.
    const isIphone = /iPhone/.test(navigator.userAgent) && !window.MSStream;

    if (newIsLandscape) {
        if (isIphone) {
            // On iPhone, we can't use the API. Try to scroll to hide the address bar.
            // This is not guaranteed to work on all iOS versions, but it's the best alternative.
            // setTimeout(() => window.scrollTo(0, 1), 300);
            isIphoneLandscape.value = true;
        } else {
            // On Android, iPad, and desktop, show the fullscreen prompt if not already fullscreen.
            const isCurrentlyFullscreen =
                document.fullscreenElement || document.webkitFullscreenElement;
            if (!isCurrentlyFullscreen) {
                showFullscreenPrompt.value = true;
            }
        }
    } else {
        isIphoneLandscape.value = false;
        showFullscreenPrompt.value = false;
        // If the user rotates back to portrait, automatically exit fullscreen on supported devices.
        const isCurrentlyFullscreen =
            document.fullscreenElement || document.webkitFullscreenElement;
        if (isCurrentlyFullscreen) {
            exitFullscreen();
        }
    }
});

// Apply styles to images inside prime content
const applyPrimeImageStyles = () => {
    try {
        const primeEl = document.getElementById("trial-prime-handle");
        if (!primeEl) return;
        const imgs = primeEl.querySelectorAll("img");
        if (!imgs || imgs.length === 0) return;
        imgs.forEach((img) => {
            // Rewrite cross-origin src through proxy for cacheability
            try {
                const u = new URL(img.src, window.location.origin);
                if (u.origin !== window.location.origin) {
                    img.src = `/gallery-proxy?u=${encodeURIComponent(u.href)}`;
                }
            } catch (_) {}

            img.style.width = "var(--prime-img-width, 20vmin)";
            img.style.height = "var(--prime-img-height, auto)";
            img.style.marginTop = "var(--prime-img-margin-top, 1vmin)";
            img.style.marginBottom = "var(--prime-img-margin-bottom, 1vmin)";
            img.style.marginLeft = "auto";
            img.style.marginRight = "auto";
            img.style.display = "block";
        });
    } catch (_) {}
};

watch(
    () => [
        getComputedStyle(document.documentElement).getPropertyValue(
            "--prime-img-width"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--prime-img-height"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--prime-img-margin-top"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--prime-img-margin-bottom"
        ),
    ],
    () => setTimeout(applyPrimeImageStyles, 0),
    { deep: true }
);

// Apply styles to images inside each target content
const applyTargetImageStyles = () => {
    try {
        const targetEls = document.querySelectorAll(
            ".trial-target-container-2 .trial-target"
        );
        targetEls.forEach((container) => {
            const imgs = container.querySelectorAll("img");
            imgs.forEach((img) => {
                try {
                    const u = new URL(img.src, window.location.origin);
                    if (u.origin !== window.location.origin) {
                        img.src = `/gallery-proxy?u=${encodeURIComponent(
                            u.href
                        )}`;
                    }
                } catch (_) {}

                // Remove conflicting intrinsic attributes/styles
                img.removeAttribute("width");
                img.removeAttribute("height");
                // Apply styles with strong precedence
                img.style.setProperty(
                    "width",
                    "var(--target-img-width, 18vmin)",
                    "important"
                );
                img.style.setProperty(
                    "height",
                    "var(--target-img-height, auto)"
                );
                img.style.setProperty("max-width", "none", "important");
                img.style.setProperty(
                    "margin-top",
                    "var(--target-img-margin-top, 0vmin)"
                );
                img.style.setProperty(
                    "margin-bottom",
                    "var(--target-img-margin-bottom, 0vmin)"
                );
                img.style.setProperty("display", "block");
                img.style.setProperty("margin-left", "auto");
                img.style.setProperty("margin-right", "auto");
            });
        });
    } catch (_) {}
};

watch(
    () => [
        getComputedStyle(document.documentElement).getPropertyValue(
            "--target-img-width"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--target-img-height"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--target-img-margin-top"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--target-img-margin-bottom"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--target-line-height"
        ),
    ],
    () => setTimeout(applyTargetImageStyles, 0),
    { deep: true }
);

// Apply styles to images inside Bottom Text
const applyBottomTextImageStyles = () => {
    try {
        const bottomTextEl = document.querySelector(".trial-bottom-text");
        if (!bottomTextEl) return;
        const imgs = bottomTextEl.querySelectorAll("img");
        if (!imgs || imgs.length === 0) return;
        const root = getComputedStyle(document.documentElement);
        const display =
            root.getPropertyValue("--bottom-img-display").trim() || "inline";
        imgs.forEach((img) => {
            img.style.display = display;
            img.style.width = "var(--bottom-img-width, 12vmin)";
            img.style.marginTop = "var(--bottom-img-margin-top, 0vmin)";
            img.style.marginBottom = "var(--bottom-img-margin-bottom, 0vmin)";
            img.style.marginLeft = "var(--bottom-img-margin-left, 0vmin)";
            img.style.marginRight = "var(--bottom-img-margin-right, 0vmin)";
        });
    } catch (_) {}
};

watch(
    () => [
        getComputedStyle(document.documentElement).getPropertyValue(
            "--bottom-img-display"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--bottom-img-width"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--bottom-img-margin-top"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--bottom-img-margin-bottom"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--bottom-img-margin-left"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--bottom-img-margin-right"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--bottom-text-line-height"
        ),
    ],
    () => setTimeout(applyBottomTextImageStyles, 0),
    { deep: true }
);

// Lifecycle
onMounted(() => {
    isTrialReady.value = false; // Start with trial not ready
    isClickDisabled.value = true; // Start with clicks disabled
    startTrial();
    enableTrialInteraction(); // Start the delay timer

    // Add event listeners for orientation changes
    window.addEventListener("resize", updateOrientation);
    window.addEventListener("orientationchange", updateOrientation);
    document.addEventListener("fullscreenchange", handleFullscreenChange);
    document.addEventListener("webkitfullscreenchange", handleFullscreenChange); // For Safari
    updateOrientation(); // Initial check
    setTimeout(applyPrimeImageStyles, 0);
    setTimeout(applyTargetImageStyles, 0);
    setTimeout(applyBottomTextImageStyles, 0);
});

onUnmounted(() => {
    window.removeEventListener("resize", updateOrientation);
    window.removeEventListener("orientationchange", updateOrientation);
    document.removeEventListener("fullscreenchange", handleFullscreenChange);
    document.removeEventListener(
        "webkitfullscreenchange",
        handleFullscreenChange
    );
});

// Watch for changes in the store's currentTrial
watch(
    () => surveyTestState.currentTrial,
    (newTrial, oldTrial) => {
        if (newTrial) {
            // Determine delay: long for the first trial of a test or when the prime content changes.
            const newPrimeText = newTrial?.prime?.prime;
            const oldPrimeText = oldTrial?.prime?.prime;
            if (
                surveyTestState.trialItemIndex === 0 ||
                newPrimeText !== oldPrimeText
            ) {
                trialStartTimer.value = props.config?.target_delay || 1500;
            } else {
                trialStartTimer.value = 300;
            }

            // Shuffle targets if random is enabled and we are not in editor mode
            if (
                props.config.random &&
                !isEditorMode.value &&
                newTrial.targets
            ) {
                const unselectableTargets = newTrial.targets.filter(
                    (target) => target.can_unselect === true
                );
                const selectableTargets = newTrial.targets.filter(
                    (target) => target.can_unselect !== true
                );
                const shuffledSelectableTargets = [...selectableTargets].sort(
                    () => Math.random() - 0.5
                );
                const shuffledTargets = [
                    ...shuffledSelectableTargets,
                    ...unselectableTargets,
                ];
                shuffledTrial.value = {
                    ...newTrial,
                    targets: shuffledTargets,
                };
            } else {
                shuffledTrial.value = null;
            }
            // Reset the trial after data preparation
            resetTrial();
        }
    },
    { immediate: true }
);

function resetTrial() {
    isTrialReady.value = false;
    isClickDisabled.value = true;
    startTrial();
    enableTrialInteraction();
}

// Function to apply header image styles
const applyHeaderImageStyles = () => {
    if (trialHeader.value) {
        const imgElements = trialHeader.value.querySelectorAll("img");
        if (imgElements) {
            _log("Applying header image styles", imgElements);
            imgElements.forEach((img) => {
                // Apply configurable header image styles
                img.style.width = "var(--header-img-width, 20vmin)";
                img.style.marginLeft = "var(--header-img-margin-x, 0.5vmin)";
                img.style.marginRight = "var(--header-img-margin-x, 0.5vmin)";
                img.style.marginTop = "var(--header-img-margin-y, 0.5vmin)";
                img.style.marginBottom = "var(--header-img-margin-y, 0.5vmin)";
                img.style.display = "inline";
                img.style.height = "auto";
            });
        }
    }
};

// If the trial header has an image, set the image to the trial header and make it fit the container
watch(
    () => trialHeader.value,
    (newHeader) => {
        if (newHeader) {
            applyHeaderImageStyles();
        }
    }
);

// Watch for changes in header image CSS variables and reapply styles
watch(
    () => [
        getComputedStyle(document.documentElement).getPropertyValue(
            "--header-img-width"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--header-img-margin-x"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--header-img-margin-y"
        ),
    ],
    () => {
        applyHeaderImageStyles();
    },
    { deep: true }
);

// Watch for changes in descriptive image display CSS variables and reapply styles
watch(
    () => [
        getComputedStyle(document.documentElement).getPropertyValue(
            "--descriptive-img-1-display"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--descriptive-img-2-display"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--descriptive-img-3-display"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--descriptive-img-4-display"
        ),
        getComputedStyle(document.documentElement).getPropertyValue(
            "--descriptive-img-5-display"
        ),
    ],
    () => {
        // Reapply descriptive image display attributes
        const descriptiveContainer =
            document.querySelector(".trial-descriptive");
        if (descriptiveContainer) {
            const images = descriptiveContainer.querySelectorAll("img");
            images.forEach((img, index) => {
                if (index < 5) {
                    const displayVar = `--descriptive-img-${index + 1}-display`;
                    const displayValue = getComputedStyle(
                        document.documentElement
                    )
                        .getPropertyValue(displayVar)
                        .trim();
                    img.setAttribute("data-display", displayValue || "block");
                }
            });
        }
    },
    { deep: true }
);

// Watch for descriptive trials (no special handling needed now - handled by main system)
watch(
    () => surveyTestState.currentTest,
    (newTest) => {
        if (newTest?.config?.type === "descriptive") {
            resetTrial();
            buttonDelay.value = props.config?.button_delay || 0;
        }
    },
    { immediate: true }
);

watch(trialPrime, (newPrime) => {
    if (newPrime) {
        trialStartTimer.value = 1500;
    }
});

const processedDescriptiveTitle = computed(() => {
    const titleHtml = surveyTestState.currentTest.config.title;
    if (!titleHtml || typeof document === "undefined") {
        return titleHtml;
    }

    try {
        const parser = new DOMParser();
        const doc = parser.parseFromString(titleHtml, "text/html");
        const images = doc.querySelectorAll("img");

        images.forEach((img, index) => {
            // In editor mode, rewrite cross-origin sources via proxy for cacheability
            if (isEditorMode.value) {
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
            }
            if (index < 5) {
                // Max 5 images
                img.classList.add(`descriptive-image-${index + 1}`);

                // Apply display attribute for automatic centering when display is block
                const displayVar = `--descriptive-img-${index + 1}-display`;
                const displayValue = getComputedStyle(document.documentElement)
                    .getPropertyValue(displayVar)
                    .trim();
                img.setAttribute("data-display", displayValue || "block");
            }
        });

        return doc.body.innerHTML;
    } catch (e) {
        console.error("Error processing descriptive title HTML:", e);
        return titleHtml; // fallback to original HTML
    }
});
</script>

<style scoped>
/* Disable text selection and highlighting for all elements */
* {
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    -webkit-touch-callout: none;
    -webkit-tap-highlight-color: transparent;
}

.trial-container {
    font-family: var(--font-family, "Inter", sans-serif);
}

/* Test fade transitions */
.test-fade-enter-active,
.test-fade-leave-active {
    transition: opacity 0.2s ease-in-out, transform 0.2s ease-in-out;
}

.test-fade-enter-from {
    opacity: 0;
    transform: translateY(5px) scale(0.99);
}

.test-fade-leave-to {
    opacity: 0;
    transform: translateY(-5px) scale(0.99);
}

.test-fade-enter-to,
.test-fade-leave-from {
    opacity: 1;
    transform: translateY(0) scale(1);
}

/* Trial fade transitions */
.trial-fade-enter-active,
.trial-fade-leave-active {
    transition: opacity 0.2s ease-in-out, transform 0.2s ease-in-out;
}

.trial-fade-enter-from {
    opacity: 0;
    transform: translateY(5px) scale(0.99);
}

.trial-fade-leave-to {
    opacity: 0;
    transform: translateY(-5px) scale(0.99);
}

.trial-fade-enter-to,
.trial-fade-leave-from {
    opacity: 1;
    transform: translateY(0) scale(1);
}

/* Target fade transitions for leaving */
.target-fade-leave-active {
    transition: left 0.3s ease-out, top 0.3s ease-out, opacity 0.3s ease-out;
    position: absolute; /* Important for layout during transition */
}

.target-fade-leave-to {
    opacity: 0;
    left: var(--target-exit-left);
    top: var(--target-exit-top);
}

/* Smooth visibility transitions */
.max-w-3xl {
    transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
}

/* Additional smooth transitions for interactive elements */
.target-transition {
    transition: all 0.2s ease-in-out;
}

:deep(img) {
    /* width: 22vmin;
    margin-inline: auto; */
    border-radius: 1vmin;
}

.trial-prime :deep(img) {
    width: var(--prime-img-width);
    height: var(--prime-img-height);
    margin-top: var(--prime-img-margin-top);
    margin-bottom: var(--prime-img-margin-bottom);
    display: block;
    margin-inline: auto;
    object-fit: fit;
}
</style>
