import { ref, computed, toRaw } from "vue";
import { useLogger } from "./logger.js";
const { _log } = useLogger();
import {
    surveyTestState,
    nextTrial as storeNextTrial,
    addResponse,
} from "./surveyTestStore";

export function useTrialLogic(trialData, config, id) {
    const store = surveyTestState;

    // State
    const selectedTargets = ref([]);
    const startTime = ref(0);
    const endTime = ref(0);
    const showNextButton = ref(false);
    const currentPrimeData = ref(null);

    // Computed
    const canProceed = computed(() => {
        _log("config", config.value);
        if (config.value.single_select) {
            return selectedTargets.value.length === 1;
        }

        return selectedTargets.value.length > 0;
    });

    const selectedCount = computed(() => selectedTargets.value.length);

    const isLastTrial = computed(() => {
        // Last trial of the last test of the last survey.
        const isLastSurvey = store.surveyIndex >= store.surveyCount - 1;
        const isLastTestInSurvey =
            store.surveyItemIndex >=
            (store.currentSurvey?.tests?.length || 1) - 1;
        const isLastTrialInTest =
            store.trialItemIndex >=
            (store.currentTest?.trials?.length || 1) - 1;

        return isLastSurvey && isLastTestInSurvey && isLastTrialInTest;
    });

    // Track if we're currently processing a skip to prevent infinite loops
    const isProcessingSkip = ref(false);

    // Methods
    const startTrial = () => {
        // Reset the timer - wait until trial is ready for interaction
        startTime.value = 0;
        selectedTargets.value = [];
        showNextButton.value = false;

        // Initialize prime data structure based on the survey response format
        if (trialData.value) {
            currentPrimeData.value = {
                prime: trialData.value.prime?.prime || trialData.value.prime,
                equivalent:
                    trialData.value.prime?.equivalent || trialData.value.prime,
                index: trialData.value.prime?.index || 1,
                display: trialData.value.prime?.display !== false,
                data: (trialData.value.targets || []).map((target, index) => ({
                    target: target.target,
                    equivalent: target.equivalent || target.target,
                    index: target.index,
                    can_unselect: target.can_unselect !== false,
                    colors: target.colors || {
                        normal: null,
                        hover: null,
                        active: null,
                    },
                    value: target.value,
                    batch: target.batch,
                    rt: 0,
                    rank: 0,
                    selected: false,
                    slot: index + 1,
                })),
            };

            // Check if trial should be skipped AFTER initializing prime data
            if (
                trialData.value.prime?.display === false &&
                !isProcessingSkip.value
            ) {
                _log("Prime display is false, skipping trial automatically");
                isProcessingSkip.value = true;

                // Skip immediately without delay to ensure proper index progression
                // This ensures the trialItemIndex is updated before any UI re-renders
                proceedToNextTrial(true); // Pass isSkipped = true
                isProcessingSkip.value = false;
                return;
            }
        }

        _log("startTrial -- started!", currentPrimeData.value);
    };

    // performance.now(): relógio monotônico com resolução abaixo de 1 ms
    // (Date.now() pode pular com ajuste de hora do aparelho).
    const startResponseTimer = () => {
        startTime.value = performance.now();
        _log("Response timer started");
    };

    const selectTarget = (target) => {
        // Start the response timer on first interaction if not already started
        if (startTime.value === 0) {
            startResponseTimer();
        }
        const targetIndex = selectedTargets.value.findIndex(
            (t) => t.value === target.value
        );

        if (targetIndex > -1) {
            // Deselect if already selected
            selectedTargets.value.splice(targetIndex, 1);
            updateTargetData(target, false, 0);

            // Update ranks for remaining selected targets (preserve their original response times)
            selectedTargets.value.forEach((selectedTarget, index) => {
                // Find the existing target data to preserve its response time
                const existingTargetData = currentPrimeData.value?.data?.find(
                    (item) =>
                        item.target === selectedTarget.target ||
                        item.value === selectedTarget.value
                );
                const originalResponseTime = existingTargetData?.rt || 0;

                updateTargetData(
                    selectedTarget,
                    true,
                    originalResponseTime,
                    index + 1
                );
            });
        } else {
            // Select target
            const currentTime = performance.now();
            const responseTime = Math.round(currentTime - startTime.value);
            const newRank = selectedTargets.value.length + 1;

            if (config.value.single_select) {
                // Clear previous selections for single select
                selectedTargets.value.forEach((prevTarget) => {
                    updateTargetData(prevTarget, false, 0);
                });
                selectedTargets.value = [target];
            } else {
                selectedTargets.value.push(target);
            }

            updateTargetData(target, true, responseTime, newRank);
        }

        // Check if we can proceed
        if (canProceed.value) {
            showNextButton.value = true;
        } else {
            showNextButton.value = false;
        }
    };

    const updateTargetData = (target, selected, responseTime = 0, rank = 0) => {
        if (!currentPrimeData.value || !currentPrimeData.value.data) return;

        const targetData = currentPrimeData.value.data.find(
            (item) =>
                item.target === target.target || item.value === target.value
        );

        if (targetData) {
            targetData.selected = selected;
            targetData.rt = responseTime;
            targetData.rank = rank;
        }
    };

    const completeTrial = () => {
        endTime.value = performance.now();

        // Create response object matching the survey response format
        const response = {
            id: toRaw(id.value),
            primes: currentPrimeData.value
                ? [toRaw(currentPrimeData.value)]
                : [],
        };

        return response;
    };

    const proceedToNextTrial = () => {
        const isDescriptive = store.currentTest.config?.type === "descriptive";
        if (currentPrimeData.value && !isDescriptive) {
            const response = completeTrial();
            addResponse(response);
        }
        storeNextTrial();
    };

    const isTargetSelected = (target) => {
        return selectedTargets.value.some((t) => t.value === target.value);
    };

    return {
        // State
        selectedTargets,
        startTime,
        endTime,
        showNextButton,
        currentPrimeData,
        isProcessingSkip,

        // Computed
        canProceed,
        selectedCount,
        isLastTrial,

        // Methods
        startTrial,
        startResponseTimer,
        selectTarget,
        completeTrial,
        proceedToNextTrial,
        isTargetSelected,
    };
}
