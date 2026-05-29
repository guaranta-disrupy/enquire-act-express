import { reactive, computed, ref } from "vue";
import { useLogger } from "./logger.js";
const { _log } = useLogger();

// Local storage keys
const STORAGE_KEYS = {
    SURVEY_PAYLOAD: "survey_save_payload",
};

// Function to get the dynamic storage key for completed survey data
export const getStorageKey = () => {
    const params = new URLSearchParams(window.location.search);
    const id = params.get("id");
    const surveyId = params.get("survey_id");
    if (id && surveyId) {
        return `completed_survey_${id}_${surveyId}`;
    }
    _log(
        "Could not generate a unique storage key. Missing 'id' or 'survey_id' URL parameter."
    );
    return null; // Return null if parameters are missing
};

export const surveyTestState = reactive({
    testState: "testing",
    isSurveyComplete: false,
    isOffline: false,
    surveyIndex: 0,
    surveyItemIndex: 0,
    surveyCount: 0,
    surveyItemCount: 0,
    trialItemIndex: 0,
    trialCount: 0,
    token: null,
    buttons: {
        final: "CONTINUE",
        next: "NEXT",
        button_delay: 0,
        is_countdown_visible: false,
    },
    progress: {
        show: true,
        text: null,
        count: 0,
    },
    redirects: {
        complete: null,
        pass_variable: false,
        quality_control: null,
    },
    texts: {
        error_saving:
            "Something went wrong while we were saving your progress. We'll try again! If this keeps on happening please refresh the page.",
        error_start:
            "Something went wrong while we were loading the app. Please refresh the page.",
        redirecting: "Preparing the next part...",
        saving: "Please wait while we save your progress...",
    },
    total: 0,
    theme: 2,
    currentSurveyConfig: {},
    query: {},
    surveyData: {},
    responses: [],
    currentSurvey: computed(() => {
        return (
            surveyTestState.surveyData.surveys?.[surveyTestState.surveyIndex] ||
            null
        );
    }),
    currentTest: computed(() => {
        return (
            surveyTestState.currentSurvey?.tests?.[
                surveyTestState.surveyItemIndex
            ] || null
        );
    }),
    currentTrial: computed(() => {
        return (
            surveyTestState.currentTest?.trials?.[
                surveyTestState.trialItemIndex
            ] || null
        );
    }),

    // Save completed survey data to localStorage
    saveCompletedSurvey(savePayload) {
        try {
            const storageKey = getStorageKey();
            if (!storageKey) {
                _log(
                    "Cannot save survey data: no unique storage key available."
                );
                return false;
            }

            // Save the entire surveyTestState
            const stateToSave = {
                ...this,
                savePayload: savePayload,
                timestamp: Date.now(),
            };

            localStorage.setItem(storageKey, JSON.stringify(stateToSave));

            _log(
                "Completed survey data saved to localStorage with key:",
                storageKey
            );
            _log("Saved data:", stateToSave);
            return true;
        } catch (error) {
            _log("Failed to save completed survey to localStorage:", error);
            return false;
        }
    },

    // Load completed survey data from localStorage
    loadCompletedSurvey() {
        try {
            const storageKey = getStorageKey();
            if (!storageKey) return null;

            const savedData = localStorage.getItem(storageKey);
            if (savedData) {
                const parsed = JSON.parse(savedData);
                // Only restore if data is less than 24 hours old
                if (Date.now() - parsed.timestamp < 24 * 60 * 60 * 1000) {
                    _log(
                        "Loaded completed survey from localStorage with key:",
                        storageKey,
                        parsed
                    );
                    return parsed;
                } else {
                    _log("Saved data expired, clearing localStorage");
                    this.clearCompletedSurvey();
                }
            } else {
                _log(
                    "No saved survey data found in localStorage for key:",
                    storageKey
                );
            }
        } catch (error) {
            _log("Failed to load completed survey from localStorage:", error);
        }
        return null;
    },

    // Restore entire survey state from localStorage
    restoreFromStorage() {
        try {
            const storageKey = getStorageKey();
            if (!storageKey) return false;

            const savedData = localStorage.getItem(storageKey);
            if (savedData) {
                const parsed = JSON.parse(savedData);
                // Only restore if data is less than 24 hours old
                if (Date.now() - parsed.timestamp < 24 * 60 * 60 * 1000) {
                    // Remove the extra fields we added for storage and computed properties
                    const {
                        savePayload,
                        timestamp,
                        currentSurvey,
                        currentTest,
                        currentTrial,
                        ...stateData
                    } = parsed;

                    // Restore all the state properties (excluding computed properties)
                    Object.assign(this, stateData);

                    _log("Survey state restored from localStorage");
                    return true;
                } else {
                    this.clearCompletedSurvey();
                }
            }
        } catch (error) {
            _log("Failed to restore survey state from localStorage:", error);
        }
        return false;
    },

    // Clear completed survey data
    clearCompletedSurvey() {
        try {
            const storageKey = getStorageKey();
            if (storageKey) {
                localStorage.removeItem(storageKey);
                _log("Completed survey data cleared for key:", storageKey);
            }
            localStorage.removeItem(STORAGE_KEYS.SURVEY_PAYLOAD);
        } catch (error) {
            _log("Failed to clear localStorage:", error);
        }
    },

    // Check if there's a completed survey waiting to be submitted
    hasCompletedSurvey() {
        return this.loadCompletedSurvey() !== null;
    },
});

export function jumpToTest(testIndex, trialIndex = 0) {
    const survey = surveyTestState.currentSurvey;
    if (!survey) return;

    const tests = survey.tests || [];
    if (!tests.length) return;

    const targetTestIndex = Number(testIndex) || 0;
    const targetTrialIndex = Number(trialIndex) || 0;

    const clampedTest = Math.max(
        0,
        Math.min(tests.length - 1, targetTestIndex)
    );

    const test = tests[clampedTest];
    if (!test) return;

    const isDescriptive = test?.config?.type === "descriptive";
    const maxTrial = Math.max(0, (test?.trials?.length || 1) - 1);

    const clampedTrial = isDescriptive
        ? 0
        : Math.max(0, Math.min(maxTrial, targetTrialIndex));

    surveyTestState.surveyItemIndex = clampedTest;
    surveyTestState.trialItemIndex = clampedTrial;

    // Recalculate progress based on the new position
    let progressCount = 0;
    const getTestStepCount = (t) => {
        if (!t) return 0;
        return t?.config?.type === "descriptive" ? 0 : t?.trials?.length || 0;
    };

    for (let i = 0; i < clampedTest; i++) {
        progressCount += getTestStepCount(tests[i]);
    }

    if (!isDescriptive) {
        progressCount += clampedTrial;
    }

    surveyTestState.progress.count = progressCount;
}

const createSkippedTrialResponse = (test, trial) => {
    if (!test || !trial) return null;

    const primeData = {
        prime: trial.prime?.prime || trial.prime,
        equivalent: trial.prime?.equivalent || trial.prime,
        index: trial.prime?.index || 1,
        display: false,
        data: (trial.targets || []).map((target, index) => ({
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

    return {
        id: test.id,
        primes: [primeData],
    };
};

const findFirstDisplayableTrial = () => {
    const surveys = surveyTestState.surveyData.surveys;
    if (!surveys) return false;

    for (let sIdx = 0; sIdx < surveys.length; sIdx++) {
        const survey = surveys[sIdx];
        if (!survey.tests) continue;

        for (let tIdx = 0; tIdx < survey.tests.length; tIdx++) {
            const test = survey.tests[tIdx];

            // If the test is descriptive, it's considered displayable.
            if (test.config?.type === "descriptive") {
                surveyTestState.surveyIndex = sIdx;
                surveyTestState.surveyItemIndex = tIdx;
                surveyTestState.trialItemIndex = 0;
                return true;
            }

            if (!test.trials) continue;

            for (let trIdx = 0; trIdx < test.trials.length; trIdx++) {
                const trial = test.trials[trIdx];
                if (trial.prime?.display !== false) {
                    // Found the first displayable trial. Set the indices.
                    surveyTestState.surveyIndex = sIdx;
                    surveyTestState.surveyItemIndex = tIdx;
                    surveyTestState.trialItemIndex = trIdx;
                    return true;
                }
                // Create response
                if (test.config?.type !== "descriptive") {
                    const response = createSkippedTrialResponse(test, trial);
                    if (response) {
                        addResponse(response);
                    }
                    surveyTestState.progress.count++;
                }
            }
        }
    }

    return false; // No displayable trials found in the entire survey
};

export function initializeSurveyStore(surveyData) {
    if (!surveyData) {
        _log("Survey data is required to initialize the store");
        return;
    }

    // Reset state
    surveyTestState.surveyIndex = 0;
    surveyTestState.surveyItemIndex = 0;
    surveyTestState.trialItemIndex = 0;
    surveyTestState.progress.count = 0;
    surveyTestState.responses = [];

    // Basic survey info
    surveyTestState.surveyData = surveyData;
    surveyTestState.surveyCount = surveyData.surveys?.length || 0;
    surveyTestState.total = surveyData.total || 0;
    surveyTestState.token = surveyData.token || null;
    surveyTestState.theme = surveyData.config?.theme || 1;
    surveyTestState.trialCount = surveyData.total || 0;

    // Update configuration objects if they exist
    if (surveyData.config?.texts) {
        surveyTestState.texts = {
            ...surveyTestState.texts,
            ...surveyData.config.texts,
        };
    }
    if (surveyData.config?.buttons) {
        const configButtons = surveyData.surveys[0].config.buttons;
        surveyTestState.buttons = {
            ...surveyTestState.buttons,
            ...configButtons,
            final: configButtons?.final || surveyTestState.buttons?.final,
            next: configButtons?.next || surveyTestState.buttons?.next,
            button_delay:
                configButtons?.button_delay ||
                surveyTestState.buttons?.button_delay,
            is_countdown_visible:
                configButtons?.is_countdown_visible ||
                surveyTestState.buttons?.is_countdown_visible,
        };
    }
    if (surveyData.config?.progress) {
        surveyTestState.progress = {
            ...surveyTestState.progress,
            ...surveyData.config.progress,
        };
    }
    if (surveyData.config?.redirects) {
        surveyTestState.redirects = {
            ...surveyTestState.redirects,
            ...surveyData.config.redirects,
        };
    }

    // Store the current survey configuration for reference
    surveyTestState.currentSurveyConfig = surveyData.config || {};

    // In the editor, jump directly to the specified test/trial index
    if (surveyData.config?.editor_start_test_index !== undefined) {
        jumpToTest(
            surveyData.config.editor_start_test_index,
            surveyData.config.editor_start_trial_index || 0
        );
        return;
    }

    // Find the first displayable trial and update progress for any initial skipped trials
    const foundTrial = findFirstDisplayableTrial();
    if (!foundTrial) {
        // If no displayable trials are found, the survey is effectively complete.
        completeSurvey();
    }
}

export const addResponse = (response) => {
    // Check if response with same ID already exists
    const existingResponseIndex = surveyTestState.responses.findIndex(
        (existingResponse) => existingResponse.id === response.id
    );

    if (existingResponseIndex !== -1) {
        // If ID exists, merge the primes into the existing response
        const existingResponse =
            surveyTestState.responses[existingResponseIndex];

        // Add new primes to the existing response, merging by index
        if (response.primes && response.primes.length > 0) {
            response.primes.forEach((newPrime) => {
                // Find if a prime with the same index already exists
                const existingPrimeIndex = existingResponse.primes.findIndex(
                    (p) => p.index === newPrime.index
                );

                if (existingPrimeIndex !== -1) {
                    // Prime with this index exists, merge the data arrays
                    const existingPrime =
                        existingResponse.primes[existingPrimeIndex];
                    if (newPrime.data && newPrime.data.length > 0) {
                        existingPrime.data.push(...newPrime.data);
                        // Sort the data array by index (smallest to largest)
                        existingPrime.data.sort((a, b) => a.index - b.index);
                    }
                } else {
                    // Prime with this index doesn't exist, add it as new
                    existingResponse.primes.push(newPrime);
                }
            });
        }
    } else {
        // If ID doesn't exist, add as new response
        surveyTestState.responses.push(response);
    }
};

export function nextTrial() {
    const currentTest = surveyTestState.currentTest;
    if (!currentTest) return; // Should not happen if called correctly

    // Do not increment progress for descriptive trials
    if (currentTest.config?.type !== "descriptive") {
        surveyTestState.progress.count++;
    }

    const currentTestTrialCount = currentTest.trials?.length || 0;
    let nextTrialIndex = surveyTestState.trialItemIndex + 1;

    // Loop to find the next trial that should be displayed
    while (nextTrialIndex < currentTestTrialCount) {
        const nextTrial = currentTest.trials[nextTrialIndex];
        if (nextTrial.prime?.display !== false) {
            // Found a displayable trial
            surveyTestState.trialItemIndex = nextTrialIndex;
            return;
        } else {
            const response = createSkippedTrialResponse(currentTest, nextTrial);
            if (response) {
                addResponse(response);
            }
        }
        // This trial is skipped, so we increment progress again and continue.
        surveyTestState.progress.count++;
        nextTrialIndex++;
    }

    // If no more displayable trials are found in the current test, move to the next survey item
    nextSurveyItem();
}

export const nextSurveyItem = () => {
    const currentSurvey = surveyTestState.currentSurvey;
    const currentSurveyTestCount = currentSurvey?.tests?.length || 0;

    if (surveyTestState.surveyItemIndex < currentSurveyTestCount - 1) {
        _log("Moving to next test in current survey");
        surveyTestState.surveyItemIndex++;
        surveyTestState.trialItemIndex = 0;
    } else {
        _log("Moving to next survey");
        nextSurvey();
    }
};

const nextSurvey = () => {
    if (surveyTestState.surveyIndex < surveyTestState.surveyCount - 1) {
        surveyTestState.surveyIndex++;
        surveyTestState.surveyItemIndex = 0;
        surveyTestState.trialItemIndex = 0;

        // Check if the new survey should be skipped entirely
        checkAndSkipEntireSurvey();
    } else {
        completeSurvey();
    }
};

// Check if all trials in current survey are skipped and handle accordingly
export const checkAndSkipEntireSurvey = () => {
    const currentSurvey = surveyTestState.currentSurvey;
    if (!currentSurvey || !currentSurvey.tests) return;

    let allTrialsSkipped = true;

    // Check all tests in current survey
    for (let test of currentSurvey.tests) {
        if (test.trials && test.trials.length > 0) {
            // Check if any trial in this test should be displayed
            for (let trial of test.trials) {
                if (trial.prime?.display !== false) {
                    allTrialsSkipped = false;
                    break;
                }
            }
        }
        if (!allTrialsSkipped) break;
    }

    // If all trials are skipped, move to next survey
    if (allTrialsSkipped) {
        _log("All trials in current survey are skipped, moving to next survey");

        // Increment progress count for all skipped trials in this survey
        for (let test of currentSurvey.tests) {
            if (test.trials && test.trials.length > 0) {
                surveyTestState.progress.count += test.trials.length;
            }
        }

        // Move to next survey
        nextSurvey();
    }
};

const completeSurvey = () => {
    const isEditorMode =
        surveyTestState.currentSurveyConfig?.editor_start_test_index !==
        undefined;

    if (isEditorMode) {
        _log("Survey completed in Editor Mode - submission disabled.");
        surveyTestState.isSurveyComplete = true;
        surveyTestState.testState = "editor_survey_end"; // A neutral state for the editor
        return; // Stop here and do not proceed with saving/redirecting.
    }

    _log("Survey completed");
    surveyTestState.isSurveyComplete = true;
    surveyTestState.testState = "saving";

    // Import and call the submission function
    import("./submitResponses.js").then(({ initializeSurveySubmission }) => {
        initializeSurveySubmission();
    });
};
