# Engine Execution Flow Documentation

## [Verified] Overview

The Engine is the runtime system that executes surveys and collects participant responses. It handles trial progression, response tracking, state management, and submission to the backend API.

## [Verified] Architecture Components

```
┌─────────────────────────────────────────────────────┐
│  EngineTest.vue (Entry Point)                      │
│  - Detects editor/preview/production mode           │
│  - Manages overall survey lifecycle                 │
│  - Handles image caching and loading states         │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│  ActSurvey.vue (Survey Container)                   │
│  - Renders current test/trial                       │
│  - Coordinates between test components              │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│  TrialComponent.vue (Trial Renderer)                │
│  - Displays trial UI (Prime + Targets)              │
│  - Handles user interactions                        │
│  - Manages trial-specific logic                     │
└──────────────────┬──────────────────────────────────┘
                   │
                   ├──► useTrialLogic() - Trial state & logic
                   ├──► surveyTestStore - Global state
                   └──► trialStyling - Dynamic CSS styling
```

## [Verified] Key Files

| File                 | Purpose                                    |
| -------------------- | ------------------------------------------ |
| `EngineTest.vue`     | Main engine entry point, handles lifecycle |
| `ActSurvey.vue`      | Survey container component                 |
| `TrialComponent.vue` | Individual trial rendering and interaction |
| `surveyTestStore.js` | Centralized reactive state management      |
| `trialLogic.js`      | Trial-specific logic and state composable  |
| `submitResponses.js` | Response submission and retry logic        |
| `trialStyling.js`    | Dynamic CSS style generators               |
| `trialCounter.js`    | Trial counting utilities                   |

## [Verified] State Management (surveyTestStore.js)

### Reactive State Object

```javascript
export const surveyTestState = reactive({
    // Navigation indices
    surveyIndex: 0, // Current survey index
    surveyItemIndex: 0, // Current test index
    trialItemIndex: 0, // Current trial index

    // Counts
    surveyCount: 0, // Total surveys
    surveyItemCount: 0, // Total tests in current survey
    trialCount: 0, // Total trials

    // Survey data
    surveyData: {}, // Complete survey structure
    currentSurvey: computed, // Current survey object
    currentTest: computed, // Current test object
    currentTrial: computed, // Current trial object

    // State flags
    testState: "testing", // Current state: "testing", "saving", "redirecting", etc.
    isSurveyComplete: false, // Survey completion flag
    isOffline: false, // Network status

    // Configuration
    token: null, // Authentication token
    theme: 2, // Visual theme (1, 2, or 3)
    currentSurveyConfig: {}, // Survey configuration

    // Progress tracking
    progress: {
        show: true,
        text: null,
        count: 0, // Completed trials count
    },

    // UI Configuration
    buttons: {
        final: "CONTINUE",
        next: "NEXT",
        button_delay: 0,
        is_countdown_visible: false,
    },

    // Redirect configuration
    redirects: {
        complete: null,
        pass_variable: false,
        quality_control: null,
    },

    // Response collection
    responses: [], // Array of collected responses
});
```

### Key Functions

**`initializeSurveyStore(surveyData)`**

-   Initializes the engine with survey data
-   Resets all indices and state
-   Finds first displayable trial
-   Handles editor mode initialization

**`nextTrial()`**

-   Advances to next trial in current test
-   Skips trials with `display: false`
-   Auto-creates responses for skipped trials
-   Moves to next test if no more trials

**`nextSurveyItem()`**

-   Moves to next test in current survey
-   Resets trial index to 0
-   Triggers next survey if at end

**`jumpToTest(testIndex, trialIndex)`**

-   Jumps directly to specific test/trial (used in editor)
-   Clamps indices to valid ranges
-   Updates progress count

**`addResponse(response)`**

-   Adds or merges response to collection
-   Handles multiple primes per test
-   Merges by test ID and prime index

## [Verified] Initialization Flow

### 1. EngineTest.vue Mounts

**Flow**:

1. Component receives `surveyData` prop from backend
2. Checks for editor mode (`editor_start_test_index` in config)
3. Sets up viewport meta tag for mobile scaling
4. Initializes orientation tracking (if not editor mode)
5. Sets up postMessage listeners (if editor mode)
6. Calls `initializeSurveyStore(surveyData)`

**Code**:

```javascript
// EngineTest.vue - onMounted
if (props.surveyData && props.status === "not_started") {
    // Check for failed submissions first
    const hasFailedSubmission = checkForFailedSubmissions();
    const savedData = localStorage.getItem(storageKey);

    if (hasFailedSubmission && savedData) {
        // Restore from localStorage
        surveyTestState.restoreFromStorage();
        initializeSurveySubmission();
    } else {
        // Fresh initialization
        initializeSurveyStore(props.surveyData);
    }
}
```

### 2. Store Initialization

**Flow**:

1. Reset all state values
2. Store survey data structure
3. Extract configuration (theme, buttons, progress, redirects)
4. Check for editor mode → jump to specific test/trial
5. Otherwise → find first displayable trial

**Finding First Displayable Trial**:

```javascript
const findFirstDisplayableTrial = () => {
    // Iterate through all surveys
    for (let sIdx = 0; sIdx < surveys.length; sIdx++) {
        // Iterate through all tests
        for (let tIdx = 0; tIdx < survey.tests.length; tIdx++) {
            const test = survey.tests[tIdx];

            // Descriptive tests are always displayable
            if (test.config?.type === "descriptive") {
                setIndices(sIdx, tIdx, 0);
                return true;
            }

            // Check trials
            for (let trIdx = 0; trIdx < test.trials.length; trIdx++) {
                const trial = test.trials[trIdx];

                // Skip trials with display: false
                if (trial.prime?.display === false) {
                    createSkippedTrialResponse(test, trial);
                    surveyTestState.progress.count++;
                    continue;
                }

                // Found displayable trial
                setIndices(sIdx, tIdx, trIdx);
                return true;
            }
        }
    }
    return false; // No displayable trials
};
```

### 3. Trial Rendering

**Flow**:

1. `surveyTestState.currentTest` computed property updates
2. `ActSurvey.vue` receives new test data
3. `TrialComponent.vue` mounts with current trial
4. `useTrialLogic()` composable initializes trial state
5. `startTrial()` processes trial data and checks for skip

## [Verified] Trial Execution Flow

### TrialComponent.vue Lifecycle

**1. Component Mounts**

```javascript
// TrialComponent.vue - onMounted
const {
    startTrial,
    selectTarget,
    proceedToNextTrial,
    // ... other methods
} = useTrialLogic(
    computed(() => surveyTestState.currentTrial),
    computed(() => surveyTestState.currentTest?.config),
    computed(() => surveyTestState.currentTest?.id)
);

onMounted(() => {
    startTrial(); // Initialize trial
});
```

**2. useTrialLogic Initialization**

**State Setup**:

```javascript
const selectedTargets = ref([]); // Selected target items
const startTime = ref(0); // Trial start timestamp
const endTime = ref(0); // Trial end timestamp
const showNextButton = ref(false); // Next button visibility
const currentPrimeData = ref(null); // Current prime + targets data
```

**startTrial() Function**:

```javascript
const startTrial = () => {
    // Reset state
    startTime.value = 0;
    selectedTargets.value = [];
    showNextButton.value = false;

    // Build prime data structure
    currentPrimeData.value = {
        prime: trialData.value.prime?.prime || trialData.value.prime,
        equivalent: trialData.value.prime?.equivalent || trialData.value.prime,
        index: trialData.value.prime?.index || 1,
        display: trialData.value.prime?.display !== false,
        data: (trialData.value.targets || []).map((target, index) => ({
            target: target.target,
            equivalent: target.equivalent || target.target,
            index: target.index,
            can_unselect: target.can_unselect !== false,
            colors: target.colors || {},
            value: target.value,
            batch: target.batch,
            rt: 0, // Response time (ms)
            rank: 0, // Selection order
            selected: false,
            slot: index + 1,
        })),
    };

    // Check for auto-skip
    if (trialData.value.prime?.display === false) {
        proceedToNextTrial(true); // Skip immediately
        return;
    }
};
```

### User Interaction Flow

**0. Quando o tempo de resposta começa (conferido no código em 2026-10-27)**

O relógio NÃO começa no primeiro clique. `TrialComponent.enableTrialInteraction()`
espera o atraso da rodada (`config.target_delay`, padrão 1500 ms, na primeira
rodada do teste ou quando a frase-prime muda; 300 ms nas demais), então mostra as
opções (`isTrialReady`, que controla o `v-if` dos targets), libera os cliques e
chama `startResponseTimer()` no mesmo instante. Logo, `rt` = clique − aparecimento
das opções. As opções entram com transição de ~200 ms (posição/opacidade), que
fica dentro do `rt`. O "start on first interaction" abaixo é só reserva, para o
caso de o timer não ter sido iniciado. Relógio: `performance.now()`.

**1. Target Selection**

```javascript
const selectTarget = (target) => {
    // Start timer on first interaction
    if (startTime.value === 0) {
        startResponseTimer(); // reserva: normalmente já iniciado em enableTrialInteraction
    }

    const targetIndex = selectedTargets.value.findIndex(
        (t) => t.value === target.value
    );

    if (targetIndex > -1) {
        // Deselect
        selectedTargets.value.splice(targetIndex, 1);
        updateTargetData(target, false, 0);

        // Recalculate ranks for remaining selections
        selectedTargets.value.forEach((selectedTarget, index) => {
            updateTargetData(selectedTarget, true, originalRT, index + 1);
        });
    } else {
        // Select
        const currentTime = Date.now();
        const responseTime = currentTime - startTime.value;
        const newRank = selectedTargets.value.length + 1;

        if (config.value.single_select) {
            // Clear previous selections
            selectedTargets.value.forEach((prevTarget) => {
                updateTargetData(prevTarget, false, 0);
            });
            selectedTargets.value = [target];
        } else {
            selectedTargets.value.push(target);
        }

        updateTargetData(target, true, responseTime, newRank);
    }

    // Show/hide next button based on selection requirements
    showNextButton.value = canProceed.value;
};
```

**2. Update Target Data**

```javascript
const updateTargetData = (target, selected, responseTime = 0, rank = 0) => {
    const targetData = currentPrimeData.value.data.find(
        (item) => item.target === target.target || item.value === target.value
    );

    if (targetData) {
        targetData.selected = selected;
        targetData.rt = responseTime; // Response time in ms
        targetData.rank = rank; // Selection order (1, 2, 3...)
    }
};
```

**3. Next Button Click**

```javascript
const proceedToNextTrial = () => {
    const isDescriptive = store.currentTest.config?.type === "descriptive";

    if (currentPrimeData.value && !isDescriptive) {
        // Create response object
        const response = completeTrial();
        addResponse(response); // Add to global responses array
    }

    storeNextTrial(); // Advance to next trial
};
```

**4. Complete Trial Response**

```javascript
const completeTrial = () => {
    endTime.value = Date.now();

    return {
        id: toRaw(id.value), // Test ID
        primes: [toRaw(currentPrimeData.value)], // Prime + targets data
    };
};
```

## [Verified] Response Data Structure

### Response Format

```javascript
{
    id: 123,  // Test ID
    primes: [
        {
            prime: "Prime text or object",
            equivalent: "Equivalent text",
            index: 1,
            display: true,
            data: [
                {
                    target: "Target 1",
                    equivalent: "Equivalent 1",
                    index: 1,
                    can_unselect: true,
                    colors: {
                        normal: null,
                        hover: null,
                        active: null,
                    },
                    value: "value1",
                    batch: 1,
                    rt: 1250,        // Response time in milliseconds
                    rank: 1,         // Selection order (1 = first selected)
                    selected: true,
                    slot: 1,
                },
                // ... more targets
            ]
        }
    ]
}
```

### Merging Responses

When multiple trials exist in the same test:

```javascript
export const addResponse = (response) => {
    // Check if response with same ID exists
    const existingIndex = surveyTestState.responses.findIndex(
        (r) => r.id === response.id
    );

    if (existingIndex !== -1) {
        // Merge primes by index
        response.primes.forEach((newPrime) => {
            const existingPrimeIndex = existingResponse.primes.findIndex(
                (p) => p.index === newPrime.index
            );

            if (existingPrimeIndex !== -1) {
                // Merge data arrays
                existingPrime.data.push(...newPrime.data);
                existingPrime.data.sort((a, b) => a.index - b.index);
            } else {
                // Add new prime
                existingResponse.primes.push(newPrime);
            }
        });
    } else {
        // Add as new response
        surveyTestState.responses.push(response);
    }
};
```

## [Verified] Trial Progression Flow

### nextTrial() Function

```javascript
export function nextTrial() {
    const currentTest = surveyTestState.currentTest;

    // Increment progress (except descriptive trials)
    if (currentTest.config?.type !== "descriptive") {
        surveyTestState.progress.count++;
    }

    const currentTestTrialCount = currentTest.trials?.length || 0;
    let nextTrialIndex = surveyTestState.trialItemIndex + 1;

    // Loop to find next displayable trial
    while (nextTrialIndex < currentTestTrialCount) {
        const nextTrial = currentTest.trials[nextTrialIndex];

        if (nextTrial.prime?.display !== false) {
            // Found displayable trial
            surveyTestState.trialItemIndex = nextTrialIndex;
            return;
        } else {
            // Skip this trial, create response
            const response = createSkippedTrialResponse(currentTest, nextTrial);
            if (response) {
                addResponse(response);
            }
        }

        surveyTestState.progress.count++;
        nextTrialIndex++;
    }

    // No more trials in current test, move to next test
    nextSurveyItem();
}
```

### nextSurveyItem() Function

```javascript
export const nextSurveyItem = () => {
    const currentSurvey = surveyTestState.currentSurvey;
    const currentSurveyTestCount = currentSurvey?.tests?.length || 0;

    if (surveyTestState.surveyItemIndex < currentSurveyTestCount - 1) {
        // Move to next test
        surveyTestState.surveyItemIndex++;
        surveyTestState.trialItemIndex = 0;
    } else {
        // Move to next survey
        nextSurvey();
    }
};
```

### nextSurvey() Function

```javascript
const nextSurvey = () => {
    if (surveyTestState.surveyIndex < surveyTestState.surveyCount - 1) {
        surveyTestState.surveyIndex++;
        surveyTestState.surveyItemIndex = 0;
        surveyTestState.trialItemIndex = 0;

        // Check if new survey should be skipped
        checkAndSkipEntireSurvey();
    } else {
        completeSurvey(); // All surveys complete
    }
};
```

### completeSurvey() Function

```javascript
const completeSurvey = () => {
    const isEditorMode =
        surveyTestState.currentSurveyConfig?.editor_start_test_index !==
        undefined;

    if (isEditorMode) {
        // Editor mode: stop here, don't submit
        surveyTestState.isSurveyComplete = true;
        surveyTestState.testState = "editor_survey_end";
        return;
    }

    // Production mode: submit responses
    surveyTestState.isSurveyComplete = true;
    surveyTestState.testState = "saving";

    import("./submitResponses.js").then(({ initializeSurveySubmission }) => {
        initializeSurveySubmission();
    });
};
```

## [Verified] Response Submission Flow

### Submission Process

**1. Initialize Submission**

```javascript
export const initializeSurveySubmission = () => {
    surveyTestState.isSurveyComplete = true;
    submitSurveyResponses();
};
```

**2. Build Response Payload**

```javascript
export const submitSurveyResponses = async () => {
    const response = {
        detail: {
            survey_id: surveyTestState.surveyData.surveys[0].survey_id,
            responses: surveyTestState.responses, // All collected responses
            variables: [],
            url: window.location.href,
            started_at: new Date().toUTCString(),
            ended_at: new Date().toUTCString(),
        },
    };

    // Save to localStorage before submitting (for retry on failure)
    surveyTestState.saveCompletedSurvey(response);

    await onSurveyItemEnd(response);
};
```

**3. Submit to API**

```javascript
export const onSurveyItemEnd = async (response) => {
    try {
        surveyTestState.testState = "saving";

        const apiResponse = await fetchWithRetry(
            "/api/v1/save",
            {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${surveyTestState.token}`,
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify(response.detail),
            },
            2, // retries
            1000 // delay (ms)
        );

        const result = await apiResponse.json();

        // Success: clear localStorage, update token, request redirect
        surveyTestState.clearCompletedSurvey();
        surveyTestState.token = result.token;
        surveyTestState.surveyIndex++;
        requestRedirect();
    } catch (err) {
        if (err.message === "Offline") {
            surveyTestState.isOffline = true;
            // Error modal shown by DatabaseError component
            return;
        }
        surveyTestState.testState = "error_saving";
    }
};
```

### Retry Logic

**fetchWithRetry() Function**:

```javascript
const fetchWithRetry = async (url, options, retries = 2, delay = 1000) => {
    for (let i = 0; i < retries; i++) {
        try {
            const response = await fetch(url, options);
            if (response.status === 500 || response.status === 503) {
                throw new Error("Server error");
            }
            return response;
        } catch (err) {
            // Check for offline
            if (!navigator.onLine) {
                surveyTestState.isOffline = true;
                throw new Error("Offline");
            }

            if (i < retries - 1) {
                await new Promise((res) => setTimeout(res, delay));
            }
        }
    }
    throw lastError;
};
```

### Offline Handling

**LocalStorage Backup**:

-   Response payload saved before submission
-   Retrieved on page reload if submission failed
-   Automatic retry when connection restored

**Storage Key Format**:

```javascript
`completed_survey_${id}_${surveyId}`;
```

**Restoration Flow**:

```javascript
// On page load
const hasFailedSubmission = checkForFailedSubmissions();
if (hasFailedSubmission) {
    const restored = surveyTestState.restoreFromStorage();
    if (restored) {
        initializeSurveySubmission(); // Retry submission
    }
}
```

## [Verified] Redirect Flow

### requestRedirect() Function

```javascript
export const requestRedirect = async () => {
    if (surveyTestState.surveyIndex >= surveyTestState.surveyCount) {
        // All surveys complete, request redirect URL
        surveyTestState.testState = "redirecting";

        const params = new URLSearchParams();
        for (const key in surveyTestState.query) {
            params.append(key, surveyTestState.query[key]);
        }

        try {
            const response = await fetch(
                `/api/v1/goWhere?${params.toString()}`,
                {
                    method: "GET",
                    headers: {
                        Authorization: `Bearer ${surveyTestState.token}`,
                        "Content-Type": "application/json",
                        Accept: "application/json",
                    },
                }
            );

            const data = await response.json();

            if (data.external === true) {
                // External redirect (2 second delay)
                setTimeout(() => {
                    window.location.href = data.link;
                }, 2000);
            } else {
                // Internal redirect (immediate)
                window.location.href = data.link;
            }
        } catch (err) {
            surveyTestState.testState = "error_redirecting";
        }
    }
};
```

## [Verified] Trial Types

### Standard Trial

**Structure**:

-   Prime (question/stimulus)
-   Multiple targets (options/answers)
-   User selects one or more targets
-   Response time and rank tracked
-   Next button appears after selection

### Descriptive Trial

**Structure**:

-   Title/content (HTML)
-   Images (optional, up to 5)
-   No target selection required
-   Next button always visible
-   Auto-advances after button click

**Handling**:

```javascript
const isDescriptive = computed(() => {
    return surveyTestState.currentTest?.config?.type === "descriptive";
});

// Descriptive trials don't create responses
if (currentPrimeData.value && !isDescriptive) {
    const response = completeTrial();
    addResponse(response);
}
```

## [Verified] Skipped Trials

### Auto-Skip Logic

Trials with `prime.display === false` are automatically skipped:

**During Initialization**:

```javascript
if (trial.prime?.display === false) {
    // Create skipped response
    const response = createSkippedTrialResponse(test, trial);
    addResponse(response);
    surveyTestState.progress.count++;
    continue; // Skip to next trial
}
```

**During Trial Start**:

```javascript
if (trialData.value.prime?.display === false) {
    proceedToNextTrial(true); // Skip immediately
    return;
}
```

**Skipped Response Format**:

```javascript
{
    id: test.id,
    primes: [{
        prime: trial.prime.prime,
        equivalent: trial.prime.equivalent,
        index: 1,
        display: false,  // Marked as not displayed
        data: [...]      // All targets with rt: 0, rank: 0, selected: false
    }]
}
```

## [Verified] Progress Tracking

### Progress Calculation

**Formula**:

```javascript
// Progress count = sum of all completed/skipped trials
// Descriptive trials don't count toward progress

progressCount = 0;
for (let i = 0; i < currentTestIndex; i++) {
    const test = tests[i];
    if (test.config?.type !== "descriptive") {
        progressCount += test.trials.length;
    }
}

// Add current trial index (if not descriptive)
if (!isDescriptive) {
    progressCount += currentTrialIndex;
}

surveyTestState.progress.count = progressCount;
```

## [Verified] Editor Mode Integration

### Editor Mode Detection

```javascript
const isEditorMode = computed(() => {
    return props.surveyData?.config?.editor_start_test_index !== undefined;
});
```

### Editor Mode Behavior

1. **No Submission**: Responses collected but not submitted
2. **Direct Navigation**: Jumps to specific test/trial on load
3. **PostMessage Communication**: Reports position changes to parent
4. **Style Injection**: Receives CSS variables from editor

### Editor Navigation

```javascript
// In editor mode, jump to specified position
if (surveyData.config?.editor_start_test_index !== undefined) {
    jumpToTest(
        surveyData.config.editor_start_test_index,
        surveyData.config.editor_start_trial_index || 0
    );
}
```

### Position Reporting

```javascript
// Report current position to editor
const notifyParentOfPosition = () => {
    window.parent?.postMessage(
        {
            type: "ENGINE_POSITION",
            testIndex: surveyTestState.surveyItemIndex,
            trialIndex: surveyTestState.trialItemIndex,
            tests: toRaw(props.surveyData.surveys[0].tests),
        },
        "*"
    );
};

// Watch for changes
watch(() => surveyTestState.surveyItemIndex, notifyParentOfPosition);
watch(() => surveyTestState.trialItemIndex, notifyParentOfPosition);
```

## [Verified] API Endpoints

### Submit Responses

```
POST /api/v1/save
Headers:
  Authorization: Bearer {token}
  Content-Type: application/json
Body: {
  survey_id: number,
  responses: [...],
  variables: [],
  url: string,
  started_at: string,
  ended_at: string
}
```

### Get Redirect URL

```
GET /api/v1/goWhere?{query_params}
Headers:
  Authorization: Bearer {token}
Response: {
  link: string,
  external: boolean
}
```

## [Verified] Error Handling

### Error States

-   **`error_saving`**: Failed to save responses
-   **`error_redirecting`**: Failed to get redirect URL
-   **`error_start`**: Failed to initialize survey

### Offline Detection

```javascript
if (!navigator.onLine) {
    surveyTestState.isOffline = true;
    throw new Error("Offline");
}
```

### Error Modal

Custom event dispatched for error display:

```javascript
window.dispatchEvent(
    new CustomEvent("survey.error", {
        detail: {
            headerText: "Saving failed",
            message: surveyTestState.texts.error_saving,
            buttons: [{ text: "RETRY", onClick: retryFunction }],
        },
    })
);
```

## [Verified] Performance Considerations

1. **Computed Properties**: Used for currentSurvey, currentTest, currentTrial
2. **LocalStorage**: Used for offline backup (24-hour expiry)
3. **Response Merging**: Efficiently combines multiple trial responses
4. **Skip Optimization**: Trials with display:false skip UI rendering
5. **Vue Transitions**: Smooth trial transitions with fade effects

---

**Last Updated**: Based on codebase analysis
**Engine Version**: Vue 3 Composition API with reactive state management
