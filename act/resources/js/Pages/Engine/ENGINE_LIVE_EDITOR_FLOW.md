# Engine Live Editor Flow Documentation

## [Verified] Architecture Overview

The Engine Live Editor is a three-layer iframe architecture that enables real-time visual editing of survey styles:

1. **Parent Window** (`Create2.vue`) - Main survey creation/editing interface
2. **Editor Iframe** (`SurveyEditor.vue`) - Live preview and style controls
3. **Preview Iframe** (`EngineTest.vue`) - Actual survey engine running inside the editor

```
┌─────────────────────────────────────────────────────┐
│  Create2.vue (Parent Window)                        │
│  - Left Panel: Test configuration tabs              │
│  - Right Panel: Editor iframe (#app1)               │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│  SurveyEditor.vue (Editor Iframe)                   │
│  - Left: Preview Panel (with preview iframe)        │
│  - Right: Control Panel (style controls)            │
└───────┬───────────────────────────┬─────────────────┘
        │                           │
        ▼                           ▼
┌──────────────────────┐  ┌─────────────────────┐
│ EngineTest.vue       │  │ ControlPanel.vue    │
│ (Preview Iframe)     │  │ - Style controls    │
│ - Actual survey      │  │ - Device selector   │
│ - Live preview       │  │ - Navigation        │
└──────────────────────┘  └─────────────────────┘
```

## [Verified] Component Hierarchy

### 1. Create2.vue (Entry Point)

**Location**: `resources/js/Pages/Surveys2/Create2.vue`

**Responsibilities**:

-   Manages survey/test data using Pinia store (`useTestsStore`)
-   Provides tab interface for editing Primes, Targets, Settings, Instructions
-   Hosts the editor iframe (`#app1`)
-   Handles save/discard operations for test data
-   Coordinates between left panel (data editing) and right panel (live preview)

**Key Features**:

-   Watches for test data changes and marks as dirty
-   Communicates with editor iframe via `postMessage`
-   Handles unsaved changes modal
-   Supports navigation between tests without reloading

**Editor Iframe URL**:

```javascript
`/surveys/${surveyID}/editor?tid=${testId}`;
```

### 2. SurveyEditor.vue (Editor Container)

**Location**: `resources/js/Pages/Engine/SurveyEditor.vue`

**Responsibilities**:

-   Manages the live preview iframe and control panel side-by-side
-   Handles device/resolution preview scaling
-   Applies CSS variables to preview iframe in real-time
-   Manages editor store state
-   Coordinates navigation between tests/trials

**Key Components**:

-   **Preview Panel**: Contains iframe showing live survey preview
-   **Controls Panel**: Contains `ControlPanel.vue` component
-   **Device Toolbar**: Device/browser selector (desktop/tablet/mobile)

**Preview URL**:

```javascript
`/surveys/${survey.id}/preview?brc=${brcRef}&test=${testIndex}&trial=${trialIndex}`;
```

### 3. EngineTest.vue (Preview Engine)

**Location**: `resources/js/Pages/Engine/EngineTest.vue`

**Responsibilities**:

-   Renders the actual survey/trial interface
-   Detects editor mode (`editor_start_test_index` in config)
-   Listens for navigation messages from editor
-   Reports position changes back to editor
-   Applies styles from CSS variables injected by editor

**Editor Mode Detection**:

```javascript
const isEditorMode = computed(
    () => props.surveyData?.config?.editor_start_test_index !== undefined
);
```

### 4. ControlPanel.vue (Style Controls)

**Location**: `resources/js/Pages/Engine/editor/ControlPanel.vue`

**Responsibilities**:

-   Provides UI controls for all CSS variables
-   Groups controls by category (General, Layout, Prime, Targets, etc.)
-   Manages device/browser selection
-   Handles test/trial navigation
-   Saves/discards style changes

**Key Features**:

-   Theme-aware controls (Theme 1, 2, 3)
-   Device-specific controls (desktop, tablet, mobile)
-   Orientation-specific controls (landscape, portrait, tabletPortrait)
-   Browser-specific controls (Safari, Chrome, Firefox, Edge)

### 5. editorStore.js (State Management)

**Location**: `resources/js/Pages/Engine/editor/editorStore.js`

**State Structure**:

```javascript
{
  styles: {},                    // Current CSS variables
  surveyStructure: null,         // Survey/test data
  currentTestIndex: 0,           // Current test index
  currentTrialIndex: 0,          // Current trial index
  currentOrientation: "landscape",
  activeDevice: "desktop",
  activeBrowser: "safari",
  theme1Styles: { landscape: {}, portrait: {}, tabletPortrait: {} },
  theme2Styles: { landscape: {}, portrait: {}, tabletPortrait: {} },
  theme3Styles: { landscape: {}, portrait: {}, tabletPortrait: {} },
  areStylesDirty: false,
  isDataDirty: false
}
```

**Key Methods**:

-   `initialize(survey, orientation)` - Loads survey and initializes styles
-   `updateStyle(key, value)` - Updates a CSS variable and marks as dirty
-   `saveCurrentStylesToThemeOrientation()` - Saves current styles to theme/orientation
-   `getAllStylesForSaving()` - Returns all styles formatted for API save
-   `setNavigation(testIndex, trialIndex)` - Updates navigation indices

## [Verified] Communication Flow (PostMessage)

### Parent → Editor Iframe

**From Create2.vue to SurveyEditor.vue**:

| Message Type          | Purpose                                 | Payload                     |
| --------------------- | --------------------------------------- | --------------------------- |
| `PARENT_NAVIGATE`     | Navigate to specific test/trial         | `{ testIndex, trialIndex }` |
| `DATA_DIRTY_STATE`    | Notify if test data has unsaved changes | `{ isDirty: boolean }`      |
| `RELOAD_PREVIEW`      | Force reload preview (after data save)  | `{ testIndex, trialIndex }` |
| `SAVE_TEST_STYLES`    | Trigger style save in editor            | `{}`                        |
| `DISCARD_TEST_STYLES` | Discard unsaved style changes           | `{}`                        |

**Example**:

```javascript
// Create2.vue
editorIframeEl.contentWindow.postMessage(
    { type: "PARENT_NAVIGATE", testIndex: 0, trialIndex: 0 },
    "*"
);
```

### Editor → Preview Iframe

**From SurveyEditor.vue to EngineTest.vue**:

| Message Type           | Purpose                         | Payload                               |
| ---------------------- | ------------------------------- | ------------------------------------- |
| `EDITOR_NAVIGATE`      | Navigate to specific test/trial | `{ testIndex, trialIndex }`           |
| `EDITOR_DEVICE_CHANGE` | Update device configuration     | `{ device, browser, mobile, tablet }` |

**Example**:

```javascript
// SurveyEditor.vue
previewFrame.value.contentWindow.postMessage(
    {
        type: "EDITOR_NAVIGATE",
        testIndex: editorStore.currentTestIndex,
        trialIndex: editorStore.currentTrialIndex,
    },
    "*"
);
```

### Preview → Editor

**From EngineTest.vue to SurveyEditor.vue**:

| Message Type      | Purpose                            | Payload                            |
| ----------------- | ---------------------------------- | ---------------------------------- |
| `ENGINE_POSITION` | Report current test/trial position | `{ testIndex, trialIndex, tests }` |

**Example**:

```javascript
// EngineTest.vue
window.parent?.postMessage(
    {
        type: "ENGINE_POSITION",
        testIndex: surveyTestState.surveyItemIndex,
        trialIndex: surveyTestState.trialItemIndex,
        tests: toRaw(props.surveyData.surveys[0].tests),
    },
    "*"
);
```

### Editor → Parent

**From SurveyEditor.vue to Create2.vue**:

| Message Type           | Purpose                                 | Payload                     |
| ---------------------- | --------------------------------------- | --------------------------- |
| `EDITOR_READY`         | Editor iframe loaded and ready          | `{}`                        |
| `SAVE_CHANGES`         | Trigger save in parent                  | `{ testIndex, trialIndex }` |
| `UPDATE_TEST`          | Request parent to update test selection | `{ testId }`                |
| `CLOSE_OPEN_PANEL`     | Request parent to close open panel      | `{}`                        |
| `DISCARD_DATA_CHANGES` | Discard unsaved data changes            | `{}`                        |
| `HAS_UNSAVED_CHANGES`  | Report unsaved changes status           | `{ status: boolean }`       |

**Example**:

```javascript
// SurveyEditor.vue (via ControlPanel)
window.top.postMessage(
    { type: "SAVE_CHANGES", testIndex: 0, trialIndex: 0 },
    "*"
);
```

## [Verified] Style Application Flow

### 1. Style Initialization

**Flow**:

1. `SurveyEditor.vue` mounts and calls `editorStore.initialize(survey, "landscape")`
2. `editorStore` loads styles from `survey.config.styles` (theme-based structure)
3. Styles are organized by theme (1, 2, 3) and orientation (landscape, portrait, tabletPortrait)
4. Default styles are merged with saved styles for current theme/orientation

**Style Structure**:

```javascript
survey.config.styles = {
  theme1: {
    landscape: { "--prime-font-size": "24px", ... },
    portrait: { "--prime-font-size": "20px", ... },
    tabletPortrait: { "--prime-font-size": "22px", ... }
  },
  theme2: { ... },
  theme3: { ... }
}
```

### 2. Real-Time Style Updates

**Flow**:

1. User adjusts control in `ControlPanel.vue`
2. `editorStore.updateStyle(key, value)` is called
3. `editorStore.styles` reactive object updates
4. `SurveyEditor.vue` watches `editorStore.styles` changes
5. `applyStylesToIframe()` injects CSS variables into preview iframe's `documentElement`
6. Preview iframe instantly reflects style changes

**Code**:

```javascript
// ControlPanel.vue
editorStore.updateStyle("--prime-font-size", "28px");

// SurveyEditor.vue
watch(
    () => editorStore.styles,
    () => {
        applyStylesToIframe(); // Injects into preview iframe
    },
    { deep: true }
);

// Actual injection
const applyStylesToIframe = () => {
    const iframeDocument = previewFrame.value?.contentWindow?.document;
    if (iframeDocument?.documentElement) {
        Object.entries(editorStore.styles).forEach(([key, value]) => {
            iframeDocument.documentElement.style.setProperty(key, value);
        });
    }
};
```

### 3. Style Persistence

**Flow**:

1. User clicks "Save Changes" in `ControlPanel.vue`
2. `editorStore.saveCurrentStylesToThemeOrientation()` saves current styles to theme/orientation
3. `editorStore.getAllStylesForSaving()` formats all theme styles for API
4. API call to `/api/surveys/${survey.id}/styles` (PUT request)
5. If data is also dirty, sends `SAVE_CHANGES` message to parent
6. Parent saves test data separately

**API Format**:

```javascript
{
  styles: {
    theme1: {
      landscape: { "--prime-font-size": "28px", ... },
      portrait: { ... },
      tabletPortrait: { ... }
    },
    theme2: { ... },
    theme3: { ... }
  }
}
```

## [Verified] Navigation Flow

### Test/Trial Navigation

**Flow**:

1. User selects test/trial in `ControlPanel.vue` navigation dropdowns
2. `editorStore.setNavigation(testIndex, trialIndex)` is called
3. Editor sends `EDITOR_NAVIGATE` message to preview iframe
4. Preview iframe receives message and calls `jumpToTest(testIndex, trialIndex)`
5. Preview updates without reload (uses state management)
6. Preview reports new position back via `ENGINE_POSITION` message
7. Editor syncs `editorStore` with reported position

**Alternative Flow (Parent Navigation)**:

1. User changes test in left panel (`Create2.vue`)
2. Parent sends `PARENT_NAVIGATE` to editor iframe
3. Editor receives and calls `editorStore.setNavigation()`
4. Editor forwards to preview iframe as above

**Key Methods**:

-   `jumpToTest(testIndex, trialIndex)` - From `surveyTestStore.js`, updates survey state
-   `editorStore.setNavigation(testIndex, trialIndex)` - Updates editor store indices

## [Verified] Device/Resolution Preview

### Device Selection Flow

1. User selects device type (Desktop/Tablet/Mobile) in `ControlPanel.vue`
2. `editorStore.activeDevice` updates
3. `ControlPanel.vue` emits `set-resolution` event
4. `SurveyEditor.vue` receives event and updates `activeResolution`
5. `scalePreview()` function calculates scaled dimensions based on:
    - Panel available space
    - Selected resolution dimensions
    - Browser chrome heights (for mobile/tablet)
6. Preview iframe wrapper is resized and scaled
7. Preview iframe receives `EDITOR_DEVICE_CHANGE` message with device config

### Resolution Scaling

**Calculation**:

```javascript
const scalePreview = () => {
    const panelWidth = previewPanel.value.clientWidth;
    const panelHeight = previewPanel.value.clientHeight;
    const virtual = activeResolution.value; // Selected resolution

    // Account for browser chrome on mobile/tablet
    const { top: chromeTop, bottom: chromeBottom } =
        getAutoChromeHeights(virtual);
    const effectiveHeight = virtual.height - (chromeTop + chromeBottom);

    // Scale to fit (maintain aspect ratio)
    const scale = Math.min(
        availableWidth / virtual.width,
        availableHeight / effectiveHeight
    );

    // Apply scaled dimensions
    frameWrapper.value.style.width = `${virtual.width * scale}px`;
    frameWrapper.value.style.height = `${effectiveHeight * scale}px`;
};
```

## [Verified] Orientation Handling

### Theme and Orientation Separation

Styles are stored separately for each orientation:

-   `landscape` - Desktop landscape or mobile/tablet landscape
-   `portrait` - Mobile/tablet portrait
-   `tabletPortrait` - iPad-specific portrait styles

### Orientation-Common Keys

Some CSS variables are shared across orientations (stored in `orientationCommonKeys` Set):

-   `--font-family`
-   `--prime-border-color`
-   `--target-bg-color`
-   Theme 2 palette colors
-   etc.

When updating an orientation-common key:

1. Update is saved to current orientation
2. Value is automatically mirrored to other orientations
3. Ensures consistent styling across device rotations

### Orientation Switching

**Flow**:

1. User switches device/orientation (e.g., desktop → mobile portrait)
2. `editorStore.updateOrientation("portrait")` is called
3. Current styles are saved to previous orientation
4. Styles for new orientation are loaded
5. Preview iframe receives updated styles automatically

## [Verified] Data vs Style Changes

The system tracks two types of changes separately:

### Data Changes (Test Configuration)

-   **Location**: Left panel in `Create2.vue` (Primes, Targets, Settings)
-   **Store**: Pinia `useTestsStore`
-   **Flag**: `dataStore.isDirty`
-   **Save**: Via `dataStore.saveTest()` → API endpoint

### Style Changes (Visual Styling)

-   **Location**: Right panel `ControlPanel.vue`
-   **Store**: `editorStore` (reactive object)
-   **Flag**: `editorStore.areStylesDirty`
-   **Save**: Via `/api/surveys/${survey.id}/styles` endpoint

### Combined Save Flow

When saving:

1. If styles dirty → Save styles first via API
2. If data dirty → Send `SAVE_CHANGES` to parent
3. Parent saves data and reloads preview
4. Both are tracked independently

## [Verified] Key Files Reference

| File                 | Purpose                                       |
| -------------------- | --------------------------------------------- |
| `Create2.vue`        | Main survey editing page, hosts editor iframe |
| `SurveyEditor.vue`   | Editor container with preview and controls    |
| `EngineTest.vue`     | Preview engine (survey execution)             |
| `ControlPanel.vue`   | Style controls UI component                   |
| `editorStore.js`     | Centralized state management for editor       |
| `dynamicStyling.js`  | CSS injection utilities (not used in editor)  |
| `trialLogic.js`      | Trial interaction logic                       |
| `surveyTestStore.js` | Survey/test state management                  |
| `styleDefaults.js`   | Default CSS variable values                   |

## [Verified] API Endpoints

### Save Styles

```
PUT /api/surveys/{surveyId}/styles
Body: { styles: { theme1: {...}, theme2: {...}, theme3: {...} } }
```

### Save Test Data

```
PUT /surveys/{surveyId}/update
Body: Test configuration data
```

## [Speculation] Performance Optimizations

1. **Iframe Scaling**: Uses CSS transforms for hardware acceleration
2. **Style Injection**: Direct DOM manipulation (no React/Vue overhead in preview)
3. **Navigation Without Reload**: Uses state management instead of iframe reloads
4. **ResizeObserver**: Efficiently handles preview panel resizing
5. **Deep Watching**: Only watches `editorStore.styles` object for changes

## [Verified] Security Considerations

1. **PostMessage Origin Check**: All message handlers verify `event.origin`
2. **Sandbox Attributes**: Preview iframe uses `sandbox` attribute with limited permissions
3. **CSS Sanitization**: `dynamicStyling.js` has allow-list of safe CSS properties (not used in editor flow)
4. **XSS Prevention**: Styles are applied via `setProperty()` which escapes values

## [Verified] Initialization Sequence

1. User navigates to `/surveys/{id}/edit` → `Create2.vue` loads
2. `Create2.vue` creates iframe pointing to `/surveys/{id}/editor?tid={testId}`
3. `SurveyEditor.vue` loads in iframe
4. `SurveyEditor.vue` creates preview iframe pointing to `/surveys/{id}/preview?...`
5. `EngineTest.vue` loads in preview iframe
6. `editorStore.initialize()` loads survey and styles
7. Styles are applied to preview iframe
8. `EDITOR_READY` message sent to parent
9. Parent may send initial navigation command
10. System is ready for editing

---

**Last Updated**: Based on codebase analysis as of current date
**Architecture Version**: Three-layer iframe system with postMessage communication
