import { reactive, nextTick } from "vue";
import { getDefaultStyles } from "../utils/styleDefaults";
import {
    isAndroid,
    isIOS,
    isIPad,
    isTablet,
    isDesktop,
    isChrome,
    isFirefox,
    isSafari,
    isEdge,
} from "@basitcodeenv/vue3-device-detect";

export const editorStore = reactive({
    isMobile: isAndroid || isIOS,
    isTablet: isIPad || isTablet,
    isDesktop: isDesktop,
    isChrome: isChrome,
    isFirefox: isFirefox,
    isSafari: isSafari,
    isEdge: isEdge,
    styles: getDefaultStyles, // Initialize with default theme
    surveyStructure: null,
    currentTestIndex: 0,
    currentTrialIndex: 0,
    isNavigating: false,
    currentOrientation: "landscape", // Track current orientation
    activeDevice: "desktop",
    isRotated: false,
    activeBrowser: "safari",
    selectedMobile: "iPhone",
    selectedTablet: "iPad",
    customizedStyles: new Set(), // Track which styles have been customized by user
    theme1Styles: { landscape: {}, portrait: {}, tabletPortrait: {} }, // Store theme 1 styles
    theme2Styles: { landscape: {}, portrait: {}, tabletPortrait: {} }, // Store theme 2 styles
    theme3Styles: { landscape: {}, portrait: {}, tabletPortrait: {} }, // Store theme 3 styles
    savedTheme1Styles: { landscape: {}, portrait: {}, tabletPortrait: {} },
    savedTheme2Styles: { landscape: {}, portrait: {}, tabletPortrait: {} },
    savedTheme3Styles: { landscape: {}, portrait: {}, tabletPortrait: {} },
    isDataDirty: false, // Track if the main survey data has changed
    areStylesDirty: false,
    groupVisibility: {
        Header: true,
        "Background Logo": true,
        Prime: true,
        Targets: true,
        "Footer Text": true,
        Button: true,
        "Descriptive Text": true,
        "Descriptive Images": true,
        "Header Images": true,
        "Prime Image": true,
        "Target Image": true,
        "Footer Images": true,
        "Button Arrow": true,
    },

    // UI state for the control panels, shared between instances
    // activeDevice: "desktop", // Moved to top
    // isRotated: false, // Moved to top

    // Keys that are only for editor visualization and must not be persisted
    editorOnlyKeys: new Set(["--header-show"]),

    // Variables shared across orientations for a given theme
    orientationCommonKeys: new Set([
        "--logo-display",
        "--redirect-text-color",
        "--engine-background-color",
        "--loading-infinity-color",
        "--descriptive-font-weight",
        "--descriptive-color",
        "--descriptive-text-align",
        "--font-family",
        "--logo-opacity",
        "--logo-blur",
        "--prime-border-style",
        "--prime-border-color",
        "--target-font-weight",
        "--target-text-color",
        "--target-bg-color",
        "--target-border-color",
        "--target-hover-border-color",
        "--target-selected-bg-color",
        "--target-selected-border-color",
        "--target-selected-text-color",
        "--target-selected-text-color-theme2",
        "--target-text-transform",
        "--target-border-style",
        "--button-font-color",
        "--button-bg-color",
        "--bottom-text-font-style",
        "--bottom-text-font-weight",
        "--bottom-text-color",
        "--t2-palette-2-1",
        "--t2-palette-2-2",
        "--t2-palette-3-1",
        "--t2-palette-3-2",
        "--t2-palette-3-3",
        "--t2-palette-4-1",
        "--t2-palette-4-2",
        "--t2-palette-4-3",
        "--t2-palette-4-4",
        "--t2-palette-5-1",
        "--t2-palette-5-2",
        "--t2-palette-5-3",
        "--t2-palette-5-4",
        "--t2-palette-5-5",
        "--t2-palette-6-1",
        "--t2-palette-6-2",
        "--t2-palette-6-3",
        "--t2-palette-6-4",
        "--t2-palette-6-5",
        "--t2-palette-6-6",
        "--t2-palette-7-1",
        "--t2-palette-7-2",
        "--t2-palette-7-3",
        "--t2-palette-7-4",
        "--t2-palette-7-5",
        "--t2-palette-7-6",
        "--t2-palette-7-7",
        "--descriptive-img-3-display",
        "--descriptive-img-4-display",
        "--descriptive-img-5-display",
        "--descriptive-img-1-display",
        "--descriptive-img-2-display",
        "--prime-bg-color",
        "--prime-font-weight",
        "--prime-text-wrap-style",
        "--target-text-wrap-style",
        "--button-flex-direction",
        "--button-arrow-display",
        "--button-arrow-transform-x",
        "--header-show",
        "--header-text-color",
        "--header-img-display",
        "--bottom-img-display",
        "--timer-container-bg-color",
        "--timer-circle-stroke-color",
        "--timer-circle-track-stroke-color",
        "--timer-text-color",
        "--unselect-modal-width",
        "--unselect-modal-height",
        "--unselect-modal-border-radius",
        "--unselect-modal-scale",
        "--unselect-modal-message-font-size",
        "--unselect-modal-cancel-font-size",
        "--unselect-modal-submit-font-size",
        "--unselect-modal-actions-flex-direction",
        "--unselect-modal-actions-justify-content",
        "--unselect-modal-message-text-align",
    ]),

    // Populate orientation-common keys for Theme 2 palette variables dynamically
    _initPaletteKeys: (function () {
        try {
            const start = 2;
            const end = 7;
            for (let count = start; count <= end; count++) {
                for (let slot = 1; slot <= count; slot++) {
                    const key = `--t2-palette-${count}-${slot}`;
                    // Add palette vars to orientationCommonKeys so they mirror across orientations
                    // eslint-disable-next-line no-this-before-super
                    this.orientationCommonKeys.add(key);
                }
            }
        } catch (_) {}
        return true;
    })(),

    getTheme() {
        const theme = this.surveyStructure?.config?.theme;
        return theme ? parseInt(theme, 10) : 1;
    },

    toggleGroupVisibility(groupTitle) {
        if (groupTitle in this.groupVisibility) {
            this.groupVisibility[groupTitle] =
                !this.groupVisibility[groupTitle];
            const isVisible = this.groupVisibility[groupTitle];

            const groupToVarPrefix = (title) => {
                return title.toLowerCase().replace(/ /g, "-");
            };

            const prefix = groupToVarPrefix(groupTitle);
            const displayVar = `--${prefix}-display`;
            const showVar = `--${prefix}-show`;

            let visibleValue = "block";
            if (["Header", "Button", "Prime"].includes(groupTitle)) {
                visibleValue = "flex";
            }
            if (
                ["Header Images", "Footer Images", "Button Arrow"].includes(
                    groupTitle
                )
            ) {
                visibleValue = "inline";
            }

            this.updateStyle(displayVar, isVisible ? visibleValue : "none");
            this.updateStyle(showVar, isVisible ? "#39FF14" : "#9ca3af"); // green / gray-400
        }
    },

    /**
     * Initialize the store with styles from the survey's config.
     * Loads separate theme and orientation styles from survey.config.styles.
     * @param {Object} survey - The survey object
     * @param {string} orientation - The initial orientation ('portrait' or 'landscape')
     */
    initialize(survey, orientation = "landscape") {
        this.surveyStructure = survey;
        this.currentOrientation = orientation;
        this.areStylesDirty = false;

        // Load theme-based styles from survey config
        this.theme1Styles = {
            landscape: survey.config?.styles?.theme1?.landscape || {},
            portrait: survey.config?.styles?.theme1?.portrait || {},
            tabletPortrait: survey.config?.styles?.theme1?.tabletPortrait || {},
        };
        this.theme2Styles = {
            landscape: survey.config?.styles?.theme2?.landscape || {},
            portrait: survey.config?.styles?.theme2?.portrait || {},
            tabletPortrait: survey.config?.styles?.theme2?.tabletPortrait || {},
        };
        this.theme3Styles = {
            landscape: survey.config?.styles?.theme3?.landscape || {},
            portrait: survey.config?.styles?.theme3?.portrait || {},
            tabletPortrait: survey.config?.styles?.theme3?.tabletPortrait || {},
        };

        // Merge test-specific styles if available
        this.mergeTestSpecificStyles();

        // Deep copy for the saved state
        this.savedTheme1Styles = JSON.parse(JSON.stringify(this.theme1Styles));
        this.savedTheme2Styles = JSON.parse(JSON.stringify(this.theme2Styles));
        this.savedTheme3Styles = JSON.parse(JSON.stringify(this.theme3Styles));

        // Load the appropriate theme and orientation styles
        this.loadThemeOrientationStyles(this.getTheme(), orientation);
    },

    mergeTestSpecificStyles() {
        if (!this.surveyStructure?.tests || this.currentTestIndex === undefined) {
            return;
        }

        const currentTest = this.surveyStructure.tests[this.currentTestIndex];
        if (!currentTest?.config?.styles) {
            return;
        }

        const testStyles = currentTest.config.styles;

        // Merge test-specific styles for each theme and orientation
        ['theme1', 'theme2', 'theme3'].forEach((themeKey) => {
            if (testStyles[themeKey]) {
                const themeIndex = themeKey.replace('theme', '');
                const themeStyles = themeIndex === '1' 
                    ? this.theme1Styles 
                    : themeIndex === '2' 
                    ? this.theme2Styles 
                    : this.theme3Styles;

                ['landscape', 'portrait', 'tabletPortrait'].forEach((orientation) => {
                    if (testStyles[themeKey][orientation]) {
                        themeStyles[orientation] = {
                            ...themeStyles[orientation],
                            ...testStyles[themeKey][orientation]
                        };
                    }
                });
            }
        });
    },

    /**
     * Load styles for a specific theme and orientation
     * @param {number} theme - The theme (1 or 2)
     * @param {string} orientation - The orientation ('portrait' or 'landscape')
     */
    loadThemeOrientationStyles(theme, orientation) {
        this.currentOrientation = orientation;
        const themeDefaults = getDefaultStyles(theme, orientation);

        // Merge test-specific styles before loading
        this.mergeTestSpecificStyles();

        // Get the saved styles for this theme and orientation
        const themeStyles =
            theme === 1
                ? this.theme1Styles
                : theme === 2
                ? this.theme2Styles
                : this.theme3Styles;
        const savedStyles = themeStyles[orientation];

        // Apply defaults first, then saved styles
        this.styles = { ...themeDefaults, ...savedStyles };

        // Track which styles are customized for this theme and orientation
        this.customizedStyles.clear();
        Object.keys(savedStyles).forEach((key) => {
            if (savedStyles[key] !== themeDefaults[key]) {
                this.customizedStyles.add(key);
            }
        });
    },

    /**
     * Update the orientation and load the appropriate styles
     * @param {string} orientation - The new orientation ('portrait' or 'landscape')
     */
    updateOrientation(orientation) {
        // Save current styles to the appropriate theme and orientation before switching
        this.saveCurrentStylesToThemeOrientation();

        // Load the new theme and orientation styles
        this.loadThemeOrientationStyles(this.getTheme(), orientation);
    },

    /**
     * Save current styles to the appropriate theme and orientation storage
     */
    saveCurrentStylesToThemeOrientation() {
        const theme = this.getTheme();
        const themeStyles =
            theme === 1
                ? this.theme1Styles
                : theme === 2
                ? this.theme2Styles
                : this.theme3Styles;
        // Save current orientation styles
        themeStyles[this.currentOrientation] = { ...this.styles };

        // Also mirror orientation-common keys to the other orientations so that
        // switching devices preserves these values immediately.
        if (this.orientationCommonKeys && this.orientationCommonKeys.size > 0) {
            const orientations = ["landscape", "portrait", "tabletPortrait"];
            orientations.forEach((o) => {
                if (o !== this.currentOrientation) {
                    const otherMap = { ...(themeStyles[o] || {}) };
                    this.orientationCommonKeys.forEach((key) => {
                        if (key in this.styles) {
                            otherMap[key] = this.styles[key];
                        }
                    });
                    themeStyles[o] = otherMap;
                }
            });
        }
    },

    /**
     * Get all styles for saving (both themes with landscape and portrait)
     * @returns {Object} Object containing theme1 and theme2 styles
     */
    getAllStylesForSaving() {
        const stripEditorOnly = (stylesByOrientation) => {
            const copy = {
                landscape: { ...(stylesByOrientation.landscape || {}) },
                portrait: { ...(stylesByOrientation.portrait || {}) },
                tabletPortrait: {
                    ...(stylesByOrientation.tabletPortrait || {}),
                },
            };
            // Remove editor-only keys from all orientations
            ["landscape", "portrait", "tabletPortrait"].forEach((o) => {
                Object.keys(copy[o]).forEach((key) => {
                    if (this.editorOnlyKeys.has(key)) {
                        delete copy[o][key];
                    }
                });
            });
            return copy;
        };

        const result = {
            theme1: stripEditorOnly(this.theme1Styles),
            theme2: stripEditorOnly(this.theme2Styles),
            theme3: stripEditorOnly(this.theme3Styles),
        };
        console.log("Getting all styles for saving:", result);
        return result;
    },

    /**
     * Update a specific style value.
     * @param {string} key - The CSS variable name (e.g., '--prime-font-size').
     * @param {string} value - The new value for the style.
     */
    updateStyle(key, value) {
        if (key in this.styles) {
            this.styles[key] = value;
            this.customizedStyles.add(key);
            this.areStylesDirty = true;

            // Also update the current theme and orientation's stored styles and mirror common keys
            this.saveCurrentStylesToThemeOrientation();
        }
    },

    /**
     * Resets a specific style to its default value.
     * @param {string} key - The CSS variable name to reset.
     */
    resetStyle(key) {
        const theme = this.getTheme();
        const themeDefaults = getDefaultStyles(theme, this.currentOrientation);

        if (key in themeDefaults) {
            this.styles[key] = themeDefaults[key];
            this.customizedStyles.delete(key);
            this.areStylesDirty = true;

            // Also update the current theme and orientation's stored styles
            this.saveCurrentStylesToThemeOrientation();

            // Reset other orientation if key is common
            if (this.orientationCommonKeys.has(key)) {
                const otherOrientation =
                    this.currentOrientation === "landscape"
                        ? "portrait"
                        : "landscape";
                const otherDefaults = getDefaultStyles(theme, otherOrientation);
                const themeStyles =
                    theme === 1
                        ? this.theme1Styles
                        : theme === 2
                        ? this.theme2Styles
                        : this.theme3Styles;
                themeStyles[otherOrientation] = {
                    ...(themeStyles[otherOrientation] || {}),
                    [key]: otherDefaults[key],
                };
            }
        }
    },

    revertStyles() {
        this.theme1Styles = JSON.parse(JSON.stringify(this.savedTheme1Styles));
        this.theme2Styles = JSON.parse(JSON.stringify(this.savedTheme2Styles));
        this.theme3Styles = JSON.parse(JSON.stringify(this.savedTheme3Styles));

        // Reload the current view with the reverted styles
        this.loadThemeOrientationStyles(
            this.getTheme(),
            this.currentOrientation
        );
        this.areStylesDirty = false;
    },

    setStylesPristine() {
        this.areStylesDirty = false;
        // After saving, the current state is the new "saved" state.
        this.savedTheme1Styles = JSON.parse(JSON.stringify(this.theme1Styles));
        this.savedTheme2Styles = JSON.parse(JSON.stringify(this.theme2Styles));
        this.savedTheme3Styles = JSON.parse(JSON.stringify(this.theme3Styles));
    },

    /**
     * Sets the current navigation indices for the preview.
     * @param {number} testIndex
     * @param {number} trialIndex
     */
    setNavigation(testIndex, trialIndex) {
        this.isNavigating = true;
        this.currentTestIndex = testIndex;
        this.currentTrialIndex = trialIndex;
        nextTick(() => {
            this.isNavigating = false;
        });
    },
});
