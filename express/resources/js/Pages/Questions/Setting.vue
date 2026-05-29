<script setup>
// import { S4Input } from "../../S4";
import S4Input from "@/S4/Input.vue";
import JetLabel from '@/Jetstream/Label.vue';
import { ref, onMounted, onUnmounted, provide, watch, nextTick, inject, computed } from "vue";
import prism_editor from "@/prism_editor.js";
import S4InputSelect from "@/S4/InputSelect.vue";
import S4ToggleBtn from "@/S4/ToggleBtn.vue";
import ImageInput from "@/S4/ImageInput.vue";
import { useForm, usePage, router } from "@inertiajs/vue3";
import helpers from "@/helpers.js";
import S4Loading from "@/S4/Loading.vue";
import SliderBarVue from "../../S4/SliderBar.vue";
import Redirects from "./Settings/Redirects.vue";
import Tooltip from "../../S4/Tooltip.vue";
import question from "/public/images/question.svg";
import TE2 from "@/S4/TE2.vue";
import { useQuestionnaireStore } from "../../stores/questionnaireStore.js";
import LanguageJson from "@/../json/language.json";

const props = defineProps(["showSetting", "questions"]);
const LanguageList = LanguageJson.languages || [];
const emit = defineEmits(["showSettingHandler", "closeSettings"]);
const { highlighter, PrismEditor } = prism_editor();
const { toast, initProperty } = helpers();
const { questionnaireData, getCustomCSS, getCustomJS, initializeFromPageData } = useQuestionnaireStore();

const displayMain = ref({
    main: true,
});

const switchValues = ref([
    { text: "Enable", value: 1 },
    { text: "Disable", value: 0 },
]);
// Get customCSS and customJS from shared store
const initialSetup = { ...usePage().props.questionnaire.setup };
initialSetup.customCSS = getCustomCSS();
initialSetup.customJS = getCustomJS();

// Ensure language data structure is synced with page props (important for latest translations)
if (usePage().props.questionnaire?.setup?.language) {
    initialSetup.language = JSON.parse(JSON.stringify(usePage().props.questionnaire.setup.language));
}

// Apply global express_messages only if setup still has the original defaults
const globalExpress = usePage().props.global_setup?.express_messages || null;

if (globalExpress) {
    if (!initialSetup.messages) initialSetup.messages = {};
    if (!initialSetup.redirects) initialSetup.redirects = {};
    if (!initialSetup.modals) initialSetup.modals = {};

    // Helper: only override if value is still the original default (i.e., effectively "new" questionnaire)
    const applyIfDefault = (current, hardDefault, newVal) => {
        if (newVal === undefined) return current;
        if (
            current === undefined ||
            current === null ||
            current === "" ||
            current === hardDefault
        ) {
            return newVal;
        }
        return current;
    };

    // ----- Buttons (defaults from AddEditQuestionnaire.vue) -----
    initialSetup.messages.nextBtn = applyIfDefault(
        initialSetup.messages.nextBtn,
        "NEXT",
        globalExpress.buttons?.next
    );
    initialSetup.messages.prevBtn = applyIfDefault(
        initialSetup.messages.prevBtn,
        "PREVIOUS",
        globalExpress.buttons?.previous
    );
    initialSetup.messages.finalBtn = applyIfDefault(
        initialSetup.messages.finalBtn,
        "CONTINUE",
        globalExpress.buttons?.finish
    );

    // ----- System messages -----
    initialSetup.messages.loading = applyIfDefault(
        initialSetup.messages.loading,
        "Loading...",
        globalExpress.systemMessages?.loading
    );
    initialSetup.messages.saving = applyIfDefault(
        initialSetup.messages.saving,
        "Saving data...",
        globalExpress.systemMessages?.saving
    );

    // ----- Warning messages (defaults from Setting.vue initProperty calls) -----
    const wm = globalExpress.warningMessages || {};

    initialSetup.messages.required_warning = applyIfDefault(
        initialSetup.messages.required_warning,
        "This question requires an answer",
        wm.required
    );
    initialSetup.messages.input1_warning_min = applyIfDefault(
        initialSetup.messages.input1_warning_min,
        "Please input at least @min characters",
        wm.minCharacters
    );
    initialSetup.messages.input1_warning_max = applyIfDefault(
        initialSetup.messages.input1_warning_max,
        "Please input no more than @max characters",
        wm.maxCharacters
    );
    initialSetup.messages.input2_warning_minmax = applyIfDefault(
        initialSetup.messages.input2_warning_minmax,
        "Please input a number between @min and @max",
        wm.numberRange
    );
    initialSetup.messages.input2_warning_min = applyIfDefault(
        initialSetup.messages.input2_warning_min,
        "Please input a number greater than or equal to @min",
        wm.numberTooLow
    );
    initialSetup.messages.input2_warning_max = applyIfDefault(
        initialSetup.messages.input2_warning_max,
        "Please input a number less than or equal to @max",
        wm.numberTooHigh
    );
    initialSetup.messages.input9_warning_alphanumeric = applyIfDefault(
        initialSetup.messages.input9_warning_alphanumeric,
        "Please input alphanumeric characters only. Special symbols and spaces are not allowed.",
        wm.alphanumericOnly
    );
    initialSetup.messages.input8_warning_letters = applyIfDefault(
        initialSetup.messages.input8_warning_letters,
        "Please input letters only. Numbers and special characters are not allowed.",
        wm.lettersOnly
    );
    initialSetup.messages.input4_warning_invalidemail = applyIfDefault(
        initialSetup.messages.input4_warning_invalidemail,
        "Please input a valid email address",
        wm.invalidEmail
    );

    // ----- Connection lost modal -----
    if (!initialSetup.modals.connectionLost) {
        initialSetup.modals.connectionLost = {};
    }
    initialSetup.modals.connectionLost.title = applyIfDefault(
        initialSetup.modals.connectionLost.title,
        "Application Error",
        globalExpress.errorMessages?.connectionLost
    );
    initialSetup.modals.connectionLost.content = applyIfDefault(
        initialSetup.modals.connectionLost.content,
        "Unable to connect with server. Please check your internet connection.",
        globalExpress.errorMessages?.content
    );

    // ----- Redirect messages (only if still creation defaults) -----
    if (!initialSetup.redirects.complete) initialSetup.redirects.complete = {};
    if (!initialSetup.redirects.qualityControl) initialSetup.redirects.qualityControl = {};
    if (!initialSetup.redirects.screenout) initialSetup.redirects.screenout = {};

    initialSetup.redirects.complete.content = applyIfDefault(
        initialSetup.redirects.complete.content,
        "Preparing the next part...",
        globalExpress.redirectMessages?.complete
    );
    initialSetup.redirects.qualityControl.content = applyIfDefault(
        initialSetup.redirects.qualityControl.content,
        "Prease wait while we load the next step.",
        globalExpress.redirectMessages?.qualityControl
    );
    initialSetup.redirects.screenout.content = applyIfDefault(
        initialSetup.redirects.screenout.content,
        "Prease wait while we load the next step.",
        globalExpress.redirectMessages?.screenout
    );
}

const form = useForm({
    settings: {
        name: usePage().props.questionnaire.name,
        setup: initialSetup,
    },
});

provide("form", form);
provide("switchValues", switchValues);
const loading = ref(false);

// Check if we're on Templates or Themes page to disable Randomise Questions toggle
const isRandomiseDisabled = computed(() => {
    return route().current('templates.index') || route().current('themes.index');
});

// Function to save current language's unsaved edits before switching languages
const saveCurrentLanguageEdits = (currentLang) => {
    try {
        const isOriginalLang = currentLang === ' ' || !currentLang || currentLang.trim() === '';
        if (isOriginalLang) return; // Don't save for original language

        // Ensure language object exists
        if (!form.settings.setup.language) {
            form.settings.setup.language = {};
        }
        if (!form.settings.setup.language[currentLang]) {
            form.settings.setup.language[currentLang] = {};
        }

        const target = form.settings.setup.language[currentLang];
        const messages = form.settings.setup.messages || {};

        // Save button edits if they exist and differ from page props
        const pageSetup = usePage().props.questionnaire?.setup || {};
        const pageLangData = pageSetup.language?.[currentLang] || {};

        // Save button values if they exist in form messages
        // We save them if they differ from page props OR if page props don't have them yet
        const buttonKeys = ['nextBtn', 'prevBtn', 'finalBtn'];
        buttonKeys.forEach((k) => {
            if (messages[k] !== undefined && messages[k] !== null) {
                // Save if it differs from page props (unsaved edit) or if page props don't have it
                const pageValue = pageLangData[k];
                if (messages[k] !== pageValue || (pageValue === undefined && messages[k] !== '')) {
                    const sanitizeBtn = (s) => {
                        if (!s) return s;
                        try {
                            const plain = stripHtmlTags(s);
                            return (plain || s).replace(/;>/g, '').trim();
                        } catch (e) { return s; }
                    };
                    target[k] = sanitizeBtn(messages[k]);
                }
            }
        });

        // Save other message keys if they exist
        const msgKeys = [
            'loading', 'saving',
            'input1_warning_min', 'input1_warning_max',
            'input2_warning_minmax', 'input2_warning_min', 'input2_warning_max',
            'input9_warning_alphanumeric', 'input8_warning_letters',
            'input4_warning_invalidemail', 'required_warning'
        ];
        msgKeys.forEach((k) => {
            if (messages[k] !== undefined && messages[k] !== null) {
                // Save if it differs from page props or if page props don't have it
                const pageValue = pageLangData[k];
                if (messages[k] !== pageValue || (pageValue === undefined && messages[k] !== '')) {
                    target[k] = messages[k];
                }
            }
        });

        // Save redirect edits
        const redirects = form.settings.setup.redirects || {};
        if (redirects.complete?.content !== undefined && redirects.complete.content !== pageLangData.complete) {
            target.complete = redirects.complete.content;
        }
        if (redirects.qualityControl?.content !== undefined && redirects.qualityControl.content !== pageLangData.qualityControl) {
            target.qualityControl = redirects.qualityControl.content;
        }
        if (redirects.screenout?.content !== undefined && redirects.screenout.content !== pageLangData.screenout) {
            target.screenout = redirects.screenout.content;
        }

        // Save modal edits
        const modals = form.settings.setup.modals || {};
        if (modals.connectionLost?.title !== undefined && modals.connectionLost.title !== pageLangData.connectionLostTitle) {
            target.connectionLostTitle = modals.connectionLost.title;
        }
        if (modals.connectionLost?.content !== undefined && modals.connectionLost.content !== pageLangData.connectionLostContent) {
            target.connectionLostContent = modals.connectionLost.content;
        }
    } catch (e) {
        // Error saving current language edits
    }
};

// Function to load translations for a given language code
const loadTranslations = (currentLang) => {
    try {
        // Helper to check if language is original (empty string)
        const isOriginalLang = currentLang === ' ' || !currentLang || currentLang.trim() === '';

        // Get language info from language.json to check RTL
        const languageInfo = LanguageList.find(item => item.Code === currentLang);
        const isRTL = languageInfo?.RTL === 1;

        // Always read from page props to get the latest translations (especially after manual upload)
        const pageSetup = usePage().props.questionnaire?.setup || {};
        const pageLangData = pageSetup.language?.[currentLang] || {};

        // Get unsaved edits from form data for the CURRENT language
        // These are edits that haven't been saved yet but should be preserved
        const formLangData = form.settings.setup?.language?.[currentLang] || {};

        // Merge: prefer unsaved form edits over page props (to preserve user's unsaved changes)
        // This ensures unsaved edits are preserved when switching languages
        const langData = { ...pageLangData, ...formLangData };

        // Set RTL based on language.json if translation language is active
        if (currentLang && !isOriginalLang && isRTL) {
            form.settings.setup.rtl = 1;
        } else if (isOriginalLang || !isRTL) {
            // Reset to original RTL setting for non-RTL languages or original language
            const originalRTL = pageSetup.rtl || 0;
            form.settings.setup.rtl = originalRTL;
        }

        if (currentLang && !isOriginalLang) {
            // FIRST: Reset to Original values to prevent carrying over from previous language
            const originalSetup = pageSetup;

            // Reset messages to Original first
            if (originalSetup.messages) {
                form.settings.setup.messages = { ...originalSetup.messages };
            } else if (!form.settings.setup.messages) {
                form.settings.setup.messages = {};
            }

            // Reset redirects to Original first
            if (originalSetup.redirects) {
                form.settings.setup.redirects = JSON.parse(JSON.stringify(originalSetup.redirects));
            } else if (!form.settings.setup.redirects) {
                form.settings.setup.redirects = {};
            }

            // Reset modals to Original first
            if (originalSetup.modals) {
                form.settings.setup.modals = JSON.parse(JSON.stringify(originalSetup.modals));
            } else if (!form.settings.setup.modals) {
                form.settings.setup.modals = {};
            }

            // THEN: Only apply translations that exist for the current language
            // Buttons & System messages - update properties individually to avoid triggering reactivity loops
            const msgKeys = [
                'loading', 'saving', 'nextBtn', 'prevBtn', 'finalBtn',
                'input1_warning_min', 'input1_warning_max',
                'input2_warning_minmax', 'input2_warning_min', 'input2_warning_max',
                'input9_warning_alphanumeric', 'input8_warning_letters',
                'input4_warning_invalidemail', 'required_warning'
            ];
            const sanitizeBtn = (s) => {
                if (!s) return s;
                try {
                    const plain = stripHtmlTags(s);
                    return (plain || s).replace(/;>/g, '').trim();
                } catch (e) { return s; }
            };

            msgKeys.forEach((k) => {
                // Only apply if translation exists for this specific language
                if (typeof langData[k] !== 'undefined' && langData[k] !== null && langData[k] !== '') {
                    if (k === 'nextBtn' || k === 'prevBtn' || k === 'finalBtn') {
                        form.settings.setup.messages[k] = sanitizeBtn(langData[k]);
                    } else {
                        form.settings.setup.messages[k] = langData[k];
                    }
                }
                // If not defined, it will keep the Original value we set above
            });

            // Redirect contents (Complete, Quality Control, Screenout)
            // Only apply if translation exists for this specific language
            if (form.settings.setup.redirects.complete && typeof langData.complete !== 'undefined' && langData.complete !== null && langData.complete !== '') {
                form.settings.setup.redirects.complete.content = langData.complete;
            }
            if (form.settings.setup.redirects.qualityControl && typeof langData.qualityControl !== 'undefined' && langData.qualityControl !== null && langData.qualityControl !== '') {
                form.settings.setup.redirects.qualityControl.content = langData.qualityControl;
            }
            if (form.settings.setup.redirects.screenout && typeof langData.screenout !== 'undefined' && langData.screenout !== null && langData.screenout !== '') {
                form.settings.setup.redirects.screenout.content = langData.screenout;
            }

            // Modals (connection lost)
            // Only apply if translation exists for this specific language
            if (!form.settings.setup.modals.connectionLost) {
                form.settings.setup.modals.connectionLost = {};
            }
            if (typeof langData.connectionLostTitle !== 'undefined' && langData.connectionLostTitle !== null && langData.connectionLostTitle !== '') {
                form.settings.setup.modals.connectionLost.title = langData.connectionLostTitle;
            }
            if (typeof langData.connectionLostContent !== 'undefined' && langData.connectionLostContent !== null && langData.connectionLostContent !== '') {
                form.settings.setup.modals.connectionLost.content = langData.connectionLostContent;
            }
        } else {
            // For original language, load from original setup
            const originalSetup = pageSetup;

            // Load original messages
            if (originalSetup.messages) {
                form.settings.setup.messages = { ...originalSetup.messages };
            }

            // Load original redirects
            if (originalSetup.redirects) {
                form.settings.setup.redirects = JSON.parse(JSON.stringify(originalSetup.redirects));
            }

            // Load original modals
            if (originalSetup.modals) {
                form.settings.setup.modals = JSON.parse(JSON.stringify(originalSetup.modals));
            }
        }
    } catch (e) {
        // Error loading translations
    }
};

// Track current language to preserve edits when switching
const currentLanguageCode = ref(' ');

// Event handlers for language changes
let handleLanguageChange = null;
let handlePopState = null;
let handleQuestionnaireUpdateRef = null;

onMounted(() => {
    // Initialize shared store with page data
    initializeFromPageData(usePage().props);

    if (form.settings.setup.hasOwnProperty("variable_start") == false) {
        form.settings.setup["variable_start"] = 1; // set default value
    }

    // Load translated overlay for the currently selected language (non-original only)
    // Use nextTick to ensure all reactive updates happen after component is fully mounted
    nextTick(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const currentLang = urlParams.get('langc') || (usePage().props?.language?.code || ' ');
        currentLanguageCode.value = currentLang; // Track initial language
        loadTranslations(currentLang);

        // Set initial settings after loading translations to capture the current state
        nextTick(() => {
            initialSettings.value = JSON.parse(JSON.stringify(form.settings.setup));
            // Mark initialization as complete after all updates are done
            nextTick(() => {
                isInitializing.value = false;
            });
        });
    });

    // Listen for language change events from Index.vue
    handleLanguageChange = (event) => {
        const newLang = event.detail.languageCode;

        // Save current language's unsaved edits before switching (if different)
        if (currentLanguageCode.value !== newLang && currentLanguageCode.value !== ' ') {
            saveCurrentLanguageEdits(currentLanguageCode.value);
        }

        // Update tracked language
        currentLanguageCode.value = newLang;

        isInitializing.value = true;

        // Load translations immediately (will read from latest page props and unsaved edits)
        nextTick(() => {
            loadTranslations(newLang);
            nextTick(() => {
                // Update initial settings to reflect the new language state
                initialSettings.value = JSON.parse(JSON.stringify(form.settings.setup));
                isInitializing.value = false;
            });
        });
    };

    window.addEventListener('language-changed', handleLanguageChange);

    // Also listen for popstate (browser back/forward)
    handlePopState = () => {
        const urlParams = new URLSearchParams(window.location.search);
        const newLang = urlParams.get('langc') || (usePage().props?.language?.code || ' ');

        // Save current language's unsaved edits before switching (if different)
        if (currentLanguageCode.value !== newLang && currentLanguageCode.value !== ' ') {
            saveCurrentLanguageEdits(currentLanguageCode.value);
        }

        // Update tracked language
        currentLanguageCode.value = newLang;

        isInitializing.value = true;
        nextTick(() => {
            loadTranslations(newLang);
            nextTick(() => {
                initialSettings.value = JSON.parse(JSON.stringify(form.settings.setup));
                isInitializing.value = false;
            });
        });
    };

    window.addEventListener('popstate', handlePopState);

    // Listen for questionnaire data updates from remote changes
    const handleQuestionnaireUpdate = (event) => {
        if (props.showSetting && !isDiscardingChanges.value && !isInitializing.value) {
            const updatedQuestionnaire = event.detail?.questionnaire || usePage().props.questionnaire;
            if (updatedQuestionnaire) {
                // Update form with new questionnaire data
                form.settings.name = updatedQuestionnaire.name;

                // Update setup while preserving unsaved edits for current language
                const currentLang = currentLanguageCode.value;
                const currentLangData = form.settings.setup.language?.[currentLang] || {};

                // Merge new setup with current form setup to preserve unsaved edits
                const newSetup = { ...updatedQuestionnaire.setup };

                // Preserve current language's unsaved edits
                if (currentLang && currentLang !== ' ' && currentLangData) {
                    if (!newSetup.language) newSetup.language = {};
                    if (!newSetup.language[currentLang]) newSetup.language[currentLang] = {};
                    // Merge unsaved edits with new data
                    newSetup.language[currentLang] = {
                        ...newSetup.language[currentLang],
                        ...currentLangData
                    };
                }

                // Update customCSS and customJS from store
                newSetup.customCSS = getCustomCSS();
                newSetup.customJS = getCustomJS();

                form.settings.setup = newSetup;

                // Update initial settings to reflect the new state
                initialSettings.value = JSON.parse(JSON.stringify(form.settings.setup));

                // Refresh store
                initializeFromPageData(usePage().props);
            }
        }
    };

    window.addEventListener('questionnaire-data-updated', handleQuestionnaireUpdate);

    // Store handler for cleanup
    handleQuestionnaireUpdateRef = handleQuestionnaireUpdate;
});

onUnmounted(() => {
    if (handleLanguageChange) {
        window.removeEventListener('language-changed', handleLanguageChange);
    }
    if (handlePopState) {
        window.removeEventListener('popstate', handlePopState);
    }
    if (handleQuestionnaireUpdateRef) {
        window.removeEventListener('questionnaire-data-updated', handleQuestionnaireUpdateRef);
    }
});

// Watch for changes in shared store and sync customCSS/customJS
watch(() => questionnaireData.customCSS, (newValue) => {
    if (newValue !== undefined) {
        form.settings.setup.customCSS = newValue;
    }
});

watch(() => questionnaireData.customJS, (newValue) => {
    if (newValue !== undefined) {
        form.settings.setup.customJS = newValue;
    }
});

// Watch for changes in questionnaire props (e.g., from remote updates)
watch(() => usePage().props.questionnaire, (newQuestionnaire) => {
    if (newQuestionnaire && props.showSetting) {
        // Only update if settings panel is open and we're not discarding changes
        if (!isDiscardingChanges.value && !isInitializing.value) {
            // Update form with new questionnaire data
            form.settings.name = newQuestionnaire.name;

            // Update setup while preserving unsaved edits for current language
            const currentLang = currentLanguageCode.value;
            const currentLangData = form.settings.setup.language?.[currentLang] || {};

            // Merge new setup with current form setup to preserve unsaved edits
            const newSetup = { ...newQuestionnaire.setup };

            // Preserve current language's unsaved edits
            if (currentLang && currentLang !== ' ' && currentLangData) {
                if (!newSetup.language) newSetup.language = {};
                if (!newSetup.language[currentLang]) newSetup.language[currentLang] = {};
                // Merge unsaved edits with new data
                newSetup.language[currentLang] = {
                    ...newSetup.language[currentLang],
                    ...currentLangData
                };
            }

            // Update customCSS and customJS from store
            newSetup.customCSS = getCustomCSS();
            newSetup.customJS = getCustomJS();

            form.settings.setup = newSetup;

            // Update initial settings to reflect the new state
            initialSettings.value = JSON.parse(JSON.stringify(form.settings.setup));

            // Refresh store
            initializeFromPageData(usePage().props);
        }
    }
}, { deep: true });

const showSettingHandler = () => {
    emit("showSettingHandler");
};

const showTerminal = (value) => {
    if (value === "redirects") expandHandler.value = true;

    displayMain.value.main = value;
};

const saveSettings = () => {
    loading.value = true;
    let params = [usePage().props.questionnaire.code];

    // Determine current language
    const urlParams = new URLSearchParams(window.location.search);
    const currentLang = urlParams.get('langc') || (usePage().props?.language?.code || ' ');
    const isOriginalLang = currentLang === ' ' || !currentLang || currentLang.trim() === '';
    const isTranslationLanguage = currentLang && !isOriginalLang;

    // Get customCSS and customJS from shared store (only for original language)
    if (!isTranslationLanguage) {
        form.settings.setup.customCSS = getCustomCSS();
        form.settings.setup.customJS = getCustomJS();
    }

    // Record migration info so other components can rebase on next open (only for original language)
    if (!isTranslationLanguage) {
        try {
            const oldStart = parseInt(usePage().props.questionnaire.setup.variable_start);
            const newStart = parseInt(form.settings.setup.variable_start);
            if (!isNaN(oldStart) && !isNaN(newStart) && oldStart !== newStart) {
                const key = `vs_migration_${usePage().props.questionnaire.code}`;
                sessionStorage.setItem(key, JSON.stringify({ old: oldStart, new: newStart, ts: Date.now() }));
            }
        } catch (e) { }
    }

    // Transform payload based on active language
    form.transform((data) => {
        try {
            const payload = JSON.parse(JSON.stringify(data));

            if (isTranslationLanguage) {
                // When translation language is active, only save translation data
                const originalSetup = usePage().props.questionnaire.setup || {};

                // Preserve all original settings (including existing language translations)
                payload.settings.setup = JSON.parse(JSON.stringify(originalSetup));

                // Ensure language object exists and preserve existing translations
                payload.settings.setup.language = payload.settings.setup.language || {};
                // Preserve existing translations for other languages
                const existingLangData = originalSetup.language || {};
                Object.keys(existingLangData).forEach(langCode => {
                    if (langCode !== currentLang) {
                        payload.settings.setup.language[langCode] = existingLangData[langCode];
                    }
                });

                // Only update the language-specific translation data for current language
                payload.settings.setup.language[currentLang] = payload.settings.setup.language[currentLang] || {};
                const target = payload.settings.setup.language[currentLang];

                // Buttons & system messages - get from form data (use form object directly to ensure latest values)
                const msgs = form.settings.setup.messages || data.settings.setup.messages || {};
                const msgKeys = [
                    'loading', 'saving', 'nextBtn', 'prevBtn', 'finalBtn',
                    'input1_warning_min', 'input1_warning_max',
                    'input2_warning_minmax', 'input2_warning_min', 'input2_warning_max',
                    'input9_warning_alphanumeric', 'input8_warning_letters',
                    'input4_warning_invalidemail', 'required_warning'
                ];
                const sanitizeBtn = (s) => {
                    if (!s) return s;
                    try {
                        const plain = stripHtmlTags(s);
                        return (plain || s).replace(/;>/g, '').trim();
                    } catch (e) { return s; }
                };
                msgKeys.forEach((k) => {
                    // Always save button values if they exist in the form (even if empty string)
                    // For other messages, only save if defined
                    if (k === 'nextBtn' || k === 'prevBtn' || k === 'finalBtn') {
                        // For buttons, always save if they exist in form messages (user may have edited them)
                        // Check form first (most up-to-date), then fallback to data
                        const formMsgs = form.settings.setup.messages || {};
                        const dataMsgs = data.settings.setup.messages || {};
                        const btnValue = formMsgs[k] !== undefined ? formMsgs[k] : (dataMsgs[k] !== undefined ? dataMsgs[k] : msgs[k]);

                        // Save button if it exists in any of the message sources
                        if (k in formMsgs || k in dataMsgs || k in msgs || btnValue !== undefined) {
                            const sanitized = sanitizeBtn(btnValue ?? '');
                            target[k] = sanitized;
                        } else {
                        }
                    } else {
                        // For other messages, only save if defined
                        if (typeof msgs[k] !== 'undefined') {
                            target[k] = msgs[k];
                        }
                    }
                });

                // Redirect contents - get from form data (use form object directly)
                const redirects = form.settings.setup.redirects || data.settings.setup.redirects || {};
                if (redirects.complete?.content !== undefined) target.complete = redirects.complete.content;
                if (redirects.qualityControl?.content !== undefined) target.qualityControl = redirects.qualityControl.content;
                if (redirects.screenout?.content !== undefined) target.screenout = redirects.screenout.content;

                // Modals - get from form data (use form object directly)
                const modals = form.settings.setup.modals || data.settings.setup.modals || {};
                if (modals.connectionLost?.title !== undefined) target.connectionLostTitle = modals.connectionLost.title;
                if (modals.connectionLost?.content !== undefined) target.connectionLostContent = modals.connectionLost.content;

                // Save RTL value to language-specific data (based on language.json)
                const languageInfo = LanguageList.find(item => item.Code === currentLang);
                if (languageInfo?.RTL === 1) {
                    target.rtl = 1;
                }

            } else {
                // When original language is active, save all settings as normal
                const setup = payload.settings.setup || {};
                setup.language = setup.language || {};
                setup.language[currentLang] = setup.language[currentLang] || {};
                const target = setup.language[currentLang];
                // Buttons & system messages
                const msgs = setup.messages || {};
                const msgKeys = [
                    'loading', 'saving', 'nextBtn', 'prevBtn', 'finalBtn',
                    'input1_warning_min', 'input1_warning_max',
                    'input2_warning_minmax', 'input2_warning_min', 'input2_warning_max',
                    'input9_warning_alphanumeric', 'input8_warning_letters',
                    'input4_warning_invalidemail', 'required_warning'
                ];
                const sanitizeBtn = (s) => {
                    if (!s) return s;
                    try {
                        const plain = stripHtmlTags(s);
                        return (plain || s).replace(/;>/g, '').trim();
                    } catch (e) { return s; }
                };
                msgKeys.forEach((k) => {
                    if (typeof msgs[k] !== 'undefined') {
                        if (k === 'nextBtn' || k === 'prevBtn' || k === 'finalBtn') {
                            target[k] = sanitizeBtn(msgs[k]);
                        } else {
                            target[k] = msgs[k];
                        }
                    }
                });
                // Redirect contents
                const redirects = setup.redirects || {};
                if (redirects.complete?.content) target.complete = redirects.complete.content;
                if (redirects.qualityControl?.content) target.qualityControl = redirects.qualityControl.content;
                if (redirects.screenout?.content) target.screenout = redirects.screenout.content;
                // Modals
                const modals = setup.modals || {};
                if (modals.connectionLost?.title) target.connectionLostTitle = modals.connectionLost.title;
                if (modals.connectionLost?.content) target.connectionLostContent = modals.connectionLost.content;
                payload.settings.setup = setup;
            }
            return payload;
        } catch (e) {
            return data;
        }
    });

    form.post(route("questionnaireSettings.update", params), {
        onSuccess: () => {
            toast.success(`Questionnaire settings successfully updated`, {
                timeout: 2000,
            });
            loading.value = false;
            hasUnsavedChanges.value = false;
            // Update initial settings after successful save
            isDiscardingChanges.value = true;
            initialSettings.value = JSON.parse(JSON.stringify(form.settings.setup));
            isDiscardingChanges.value = false;
            // Ensure updated $page.props (questionnaire and questions) are reloaded
            router.reload({ only: ['questionnaire', 'questions'] });

            // Close the settings panel after successful save
            emit("closeSettings");
        },
        onError: (errors) => {
            toast.error(`Failed to update settings: ${Object.values(errors).flat().join(', ')}`, {
                timeout: 5000,
            });
            loading.value = false;
        },
    });
};
const expandHandler = ref(false);

// const tab = ref(1);
// const currentTab = (tabNumber) => (tab.value = tabNumber);

const js_pos = ref('default')
const css_pos = ref('default')

function setPosJs(pos) {
    if (pos == 'default') {
        if (css_pos.value == 'side-right') css_pos.value = 'side-left'
        js_pos.value = pos
    } else if (pos == 'side') {
        if (css_pos.value == 'default') js_pos.value = 'side-left'
        else {
            js_pos.value = 'side-right'
        }
    } else {
        js_pos.value = pos
        css_pos.value = 'default'
    }
}

function setPosCss(pos) {
    if (pos == 'default') {
        if (js_pos.value == 'side-right') js_pos.value = 'side-left'
        css_pos.value = pos
    } else if (pos == 'side') {
        if (js_pos.value == 'default') css_pos.value = 'side-left'
        else {
            css_pos.value = 'side-right'
        }
    } else {
        css_pos.value = pos
        js_pos.value = 'default'
    }
}


function stripHtmlTags(html) {
    const div = document.createElement("div");
    div.innerHTML = html;

    // Check if the content contains an <img> tag
    const imgTag = div.querySelector("img");
    if (imgTag && imgTag.src) {
        return imgTag.src; // Return the image link
    }

    // Return plain text if no <img> tag is found
    return div.textContent || div.innerText || "";
}

const hasUnsavedChanges = ref(false);
const isDiscardingChanges = ref(false); // Flag to prevent watcher from triggering during discard
const isInitializing = ref(true); // Flag to prevent watcher from triggering during initial load

watch(() => props.showSetting, (newValue, oldValue) => {
    if (!newValue && hasUnsavedChanges.value) {
        // Automatically discard changes when switching tabs
        isDiscardingChanges.value = true;

        // Force the badge to disappear immediately
        hasUnsavedChanges.value = false;

        nextTick(() => {
            form.settings.setup = JSON.parse(JSON.stringify(initialSettings.value));

            nextTick(() => {
                isDiscardingChanges.value = false;
            });
        });

        toast.info('Changes discarded - switched to another tab', { timeout: 2000 });
    }
});

watch(
    () => ({
        rtl: form.settings.setup.rtl,
        hidePrevBtn: form.settings.setup.hidePrevBtn,
        footerEnable: form.settings.setup.footer.enable,
        displayAsterisk: form.settings.setup.display_asterisk,
        displayLogo: form.settings.setup.display_logo,
        showProgressBar: form.settings.setup.showProgressBar,
        random: form.settings.setup.random,
    }),
    (newValues, oldValues) => {
        // Skip if we're discarding changes or initializing to prevent watcher from triggering
        if (isDiscardingChanges.value || isInitializing.value) {
            return;
        }

        // Set hasUnsavedChanges to true if any property changes
        hasUnsavedChanges.value = true;

        // Double-check: if current settings match initial settings, don't show badge
        nextTick(() => {
            const currentSettings = JSON.stringify(form.settings.setup);
            const initialSettingsStr = JSON.stringify(initialSettings.value);
            if (currentSettings === initialSettingsStr) {
                hasUnsavedChanges.value = false;
            }
        });
    },
    { deep: true }
);




const initialSettings = ref({});
const willRebaseVariables = computed(() => {
    const oldStart = parseInt(initialSettings.value?.variable_start ?? 1);
    const newStart = parseInt(form.settings.setup?.variable_start ?? oldStart);
    return !isNaN(oldStart) && !isNaN(newStart) && oldStart !== newStart;
});

const tabs = ref([
    { name: 'General', key: 'general' },
    { name: 'Redirects', key: 'redirects' }
]);

const currentTab = ref('General');
const openIndex = ref('general');

const toggleTab = (selectedTab) => {
    currentTab.value = selectedTab.toUpperCase();
    openIndex.value = openIndex.value === selectedTab ? null : selectedTab;
}

const on_Enter = (el) => {
    el.style.height = '0px'
    el.style.transition = 'all 0.3s ease'
    el.style.overflow = 'hidden'
    requestAnimationFrame(() => {
        const height = el.scrollHeight + 'px'
        el.style.height = height

        el.addEventListener('transitionend', () => {
            el.style.height = 'auto'
            el.style.overflow = 'unset'
        }, { once: true })
    })
}

const on_Leave = (el) => {
    const height = el.scrollHeight + 'px'
    el.style.height = height
    el.style.transition = 'all 0.3s ease'
    el.style.overflow = 'hidden'

    requestAnimationFrame(() => {
        el.style.height = '0px'
    })
}

const onAfterLeave = (el) => {
    el.style.height = ''
}
</script>
<template>
    <div>
        <!-- Fixed Bottom Left - Unsaved Changes Notification -->
        <div v-if="hasUnsavedChanges"
            class="fixed bottom-20 left-50 ml-5 z-50 flex items-center gap-2 bg-red-100 text-red-800 border border-red-200 px-3 py-2 rounded-full text-xs font-semibold animate-pulse shadow-lg">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd" />
            </svg>
            Unsaved Changes
        </div>

        <!-- Fixed Bottom Right - Save Button -->
        <div class="fixed bottom-20 right-6 z-50">
            <button @click="saveSettings" type='button'
                class='flex bg-[var(--mcolor)] w-32 rounded-lg px-4 py-2 hover:bg-[var(--mcolor2)] shadow-lg transition-all duration-200 hover:scale-105'>
                <div class='flex items-center justify-center flex-1 text-[var(--txtcolor)]'>
                    <span class='text-sm font-semibold text-white'>Save</span>
                </div>
            </button>
        </div>

        <s4-loading v-show="loading" :label="'Saving'" :theme="'theme1'"
            class="h-screen z-50 overflow-hidden bg-gray-700/50" :color="'text-white'"></s4-loading>




        <SliderBarVue @showSettingHandler="showSettingHandler" @expandHandler="expandHandler" title="Settings"
            :showSlideBar="props.showSetting" :expand="false" class="w-full">

            <!-- <template #topRightSection>
                <ButtonIconsVue icon="link" @click="showTerminal('redirects')" />
                <ButtonIconsVue icon="setting" @click="showTerminal(true)" />
                <ButtonIconsVue icon="terminal" @click="showTerminal(false)" />
                <div class="flex justify-center rounded-full py-1 relative bg-ssr-orange2 cursor-pointer group hover:shadow-md"
                    @click="saveSettings">
                    <div
                        class="text-white flex justify-center duration-300 transition-all easy-in-out w-28 group-hover:scale-105">
                        Save
                    </div>
                </div>
            </template> -->

            <!-- <template #sidebar v-if="0">
                <div class="mt-14 duration-300 transition-all absolute duration-300 transition-all top-1 flex gap-6 w-full h-full border-t pt-8"
                    :class="displayMain.main
                            ? displayMain.main === 'redirects'
                                ? 'translate-x-0'
                                : '-translate-x-[95vw]'
                            : '-translate-x-[95vw]'
                        ">
                    <Redirects />
                </div>
                <div class="duration-300 transition-all" :class="displayMain.main
                        ? displayMain.main === 'redirects'
                            ? '-translate-x-[95vw]'
                            : 'translate-x-0'
                        : '-translate-x-[95vw]'
                    ">
                    <div class="relative border rounded-lg p-2 bg-white shadow-xl">
                        <div class="absolute -top-5 left-1 px-2 bg-white gap-1 rounded-full justify-between mx-1 shadow-md">
                            <div class="flex items-center relative p-1 w-28 justify-end">
                                <div class="text-gray-500 border-2 rounded-full p-1 absolute -left-2">
                                    <IconsVue icon="click" />
                                </div>
                                <div class="text-slate-500">BUTTONS</div>
                            </div>
                        </div>
                        <div>
                            <s4-input theme="theme2" label="Next Button"
                                v-model="form.settings.setup.messages.nextBtn"></s4-input>
                            <s4-input theme="theme2" label="Loading Message"
                                v-model="form.settings.setup.messages.loading"></s4-input>
                            <s4-input theme="theme2" label="Saving Message"
                                v-model="form.settings.setup.messages.saving"></s4-input>
                            <s4-input theme="theme2" label="Previous Button"
                                v-model="form.settings.setup.messages.prevBtn"></s4-input>
                            <s4-input theme="theme2" label="Finish Button"
                                v-model="form.settings.setup.messages.finalBtn"></s4-input>
                        </div>
                    </div>
                    <div class="relative border rounded-lg p-2 shadow-xl bg-white">
                        <div class="absolute -top-5 left-1 px-2 bg-white gap-1 rounded-full justify-between mx-1 shadow-md">
                            <div class="flex items-center relative p-1 w-32 justify-end">
                                <div class="text-gray-500 border-2 rounded-full p-1 absolute -left-2">
                                    <IconsVue icon="link" />
                                </div>
                                <div class="text-slate-500">REDIRECTS</div>
                            </div>
                        </div>
                        <div>
                            <s4-input theme="theme2" label="Complete" v-model="form.settings.setup.redirects.complete.url
                                "></s4-input>
                            <s4-input theme="theme2" label="Quality Control" v-model="form.settings.setup.redirects.qualityControl
                                    .url
                                "></s4-input>
                            <s4-input theme="theme2" label="Screenout" v-model="form.settings.setup.redirects.screenout.url
                                "></s4-input>
                        </div>
                    </div>
                </div>
                <div class="absolute left-0 duration-300 transition-all flex flex-col gap-6 w-full" :class="displayMain.main
                        ? 'translate-x-[95vw]'
                        : 'translate-x-0'
                    ">
                    <div class="relative rounded-lg p-2 shadow-xl bg-white border pt-6 w-full h-[42vh]">
                        <div class="absolute -top-5 left-1 px-2 bg-white gap-1 rounded-full justify-between mx-1 shadow-md">
                            <div class="flex items-center relative p-1 w-36 justify-end">
                                <div class="text-gray-500 border-2 rounded-full p-1 absolute -left-2">
                                    <IconsVue icon="terminal" />
                                </div>
                                <div class="text-slate-500">CUSTOME JS</div>
                            </div>
                        </div>
                        <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                            v-model="form.settings.setup.customJS">
                        </prism-editor>
                    </div>
                    <div class="relative rounded-lg p-2 shadow-xl bg-white border pt-6 w-full h-[42vh]">
                        <div class="absolute -top-5 left-1 px-2 bg-white gap-1 rounded-full justify-between mx-1 shadow-md">
                            <div class="flex items-center relative p-1 w-40 justify-end">
                                <div class="text-gray-500 border-2 rounded-full p-1 absolute -left-2">
                                    <IconsVue icon="terminal" />
                                </div>
                                <div class="text-slate-500">CUSTOME CSS</div>
                            </div>
                        </div>
                        <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                            v-model="form.settings.setup.customCSS">
                        </prism-editor>
                    </div>
                </div>
            </template> -->

            <template #expand class="!bg-white">
                <div class="flex justify-center items-center w-full bg-[var(--mcolor)] pt-[30px] p-5">
                    <div class="flex flex-col justify-start items-start w-full pt-[10px]">
                        <div class="flex items-center gap-3">
                            <div class="text-[20px] text-white font-bold">Settings</div>

                        </div>
                        <div class="text-sm text-white font-medium">settings such as
                            buttons, redirects and etc.
                        </div>
                    </div>
                </div>
                <div class="w-full pl-5 pr-5 pt-3 pb-3">
                    <!-- --------------------------------------------- -->

                    <div v-for="tab in tabs" :key="tab.key" class="pb-2">
                        <div class="flex flex-col cursor-pointer font-medium text-sm">

                            <div @click="toggleTab(tab.key)"
                                class="relative flex flex-row font-bold justify-between rounded-lg cursor-pointer p-4 text-lg text-[#454545] hover:bg-[var(--mcolor2)] transition-all duration-500 ease-in-out"
                                :class="openIndex === tab.key ? 'bg-[var(--mcolor)] !text-white rounded-b-none hover:bg-[var(--mcolor2)]' : 'bg-[#E7E7E7]'">
                                <span>{{ tab.name }}</span>
                                <svg :class="{ 'rotate-180 ': openIndex === tab.key }"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" :stroke="openIndex === tab.key ? 'white' : '#323232'"
                                    class="transition-transform duration-[.5s] w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div>
                            <!-- <div @click="currentTab(1)"
                                class="rounded relative flex flex-row font-bold justify-between rounded-lg cursor-pointer p-4 text-lg text-[#454545] hover:bg-[var(--mcolor2)] transition-all duration-500 ease-in-out"
                                :class="{ 'bg-[#E7E7E7] rounded-b-none hover:bg-[var(--mcolor2)]': 'bg-[#E7E7E7]' }">
                                General
                                <svg :class="{ 'rotate-180 ': openIndex === tab.key }"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor"
                                    class="transition-transform duration-[.5s] w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div>
                            <div @click="currentTab(2)"
                                class="rounded relative flex flex-row font-bold justify-between rounded-lg cursor-pointer p-4 text-lg text-[#454545] hover:bg-[var(--mcolor2)] transition-all duration-500 ease-in-out"
                                :class="{ 'bg-[#E7E7E7] rounded-b-none hover:bg-[var(--mcolor2)]': 'bg-[#E7E7E7]' }">
                                Redirects
                                <svg :class="{ 'rotate-180 ': openIndex === tab.key }"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor"
                                    class="transition-transform duration-[.5s] w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div> -->


                            <!-- class="pl-7 shadow-[1px_-1px_2px_rgba(0,0,0,.2)] flex justify-center py-3 border-0 border-black/0 text-lg rounded-[0px_30px_0px_0px] bg-[#E7E7E7] rounded-[0px_30px_0px_0px] ml-[-30px]"
                                :class="{ '!bg-[#454545]  border-[var(--mcolor2)] text-[var(--txtcolor)] font-bold ': tab == 3, 'border-b-slate-200 text-black': tab == 3, ' border-b-slate-200 text-[#454545]': tab != 3 }"> -->
                            <!-- <div @click="currentTab(3)"
                                class="rounded shadow-lg relative flex flex-row font-bold justify-between rounded-lg cursor-pointer p-4 text-lg text-[#454545] hover:bg-[var(--mcolor2)] transition-all duration-500 ease-in-out"
                                :class="{ 'bg-[#E7E7E7] rounded-b-none hover:bg-[var(--mcolor2)]': 'bg-[#E7E7E7]' }">
                                Custom <span class="italic">&nbsp;&nbsp;(JS/CSS)</span>
                                <svg :class="{ 'rotate-180 ': openIndex === tab.key }"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor"
                                    class="transition-transform duration-[.5s] w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div> -->
                        </div>
                        <Transition @enter="on_Enter" @leave="on_Leave" @after-leave="onAfterLeave">
                            <div v-show="openIndex === tab.key" class="bg-white shadow-lg">
                                <div class="w-full h-full p-4">
                                    <div v-if="tab.name === 'General'">
                                        <div class="overflow-y-auto overflow-x-hidden h-[55vh] w-full pr-2 pl-2">
                                            <!-- ---------- general ---------- -->
                                            <div
                                                class="grid grid-cols-1 pb-5 pt-2 border border-black/0 border-b-slate-100">
                                                <div class="text-slate-800 font-medium text-md mt-3">Survey Details
                                                </div>
                                                <div class="group col-span-3">
                                                    <div class="text-slate-400 text-sm">
                                                        <div>
                                                            <div class="flex gap-3">
                                                                <s4-input class="pointer-events-none opacity-50"
                                                                    color="ssrGray" theme="theme2" label="Survey Name"
                                                                    v-model="form.settings.name"></s4-input>
                                                                <!-- <s4-input-select theme="theme2" class="mt-6"
                                                            label="Randomise Questions" :lists="switchValues"
                                                            v-model.number="form.settings.setup.random"></s4-input-select> -->

                                                            </div>
                                                            <!-- <div class="flex mt-2">
                                                        <s4-input color="ssrGray" label="Survey Link" readonly
                                                            :value="$page.props.engine_url + '?code=' + $page.props.questionnaire.code">
                                                        </s4-input>

                                                    </div> -->

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="pb-5 pt-2 border border-black/0 border-b-slate-100">
                                                <div class="text-slate-800 font-medium text-sm mt-3">Basic</div>
                                                <div class="col-span-3">
                                                    <div class="text-slate-400 text-sm">
                                                        <div
                                                            class="grid grid-cols-2 mt-2 items-center gap-2 gap-x-[30px]">
                                                            <div class="flex justify-between items-center">
                                                                <div class="flex items-center gap-0.5">
                                                                    <span>Set to RTL Format</span>
                                                                    <Tooltip :imgSrc="question" preset="RTL"></Tooltip>
                                                                </div>

                                                                <div>
                                                                    <S4ToggleBtn v-model="form.settings.setup.rtl">
                                                                    </S4ToggleBtn>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="flex justify-between items-center">
                                                                <div>
                                                                    <span>Hide Previous Button</span>
                                                                </div>
                                                                <div>
                                                                    <S4ToggleBtn label=""
                                                                        v-model="form.settings.setup.hidePrevBtn">
                                                                    </S4ToggleBtn>
                                                                </div>
                                                            </div>
                                                            <div class="flex justify-between items-center">
                                                                <div class="flex items-center gap-0.5">
                                                                    <span>Display Footer</span>
                                                                    <Tooltip :imgSrc="question" preset="DisplayFooter">
                                                                    </Tooltip>
                                                                </div>

                                                                <div>
                                                                    <S4ToggleBtn label=""
                                                                        v-model.number="form.settings.setup.footer.enable">
                                                                    </S4ToggleBtn>
                                                                </div>
                                                            </div>
                                                            <div class="flex justify-between items-center">
                                                                <div class="flex items-center gap-0.5">
                                                                    <span>Display Asterisk (<span
                                                                            style="color: red;">*</span>)</span>
                                                                    <Tooltip :imgSrc="question" preset="ShowAsterisk">
                                                                    </Tooltip>
                                                                </div>
                                                                <div>
                                                                    <S4ToggleBtn label=""
                                                                        v-model="form.settings.setup.display_asterisk">
                                                                    </S4ToggleBtn>
                                                                </div>
                                                            </div>

                                                            <div class="flex justify-between items-center">
                                                                <div class="flex items-center gap-0.5">
                                                                    <span>Display Logo Only</span>
                                                                    <Tooltip :imgSrc="question"
                                                                        preset="DisplayLogoOnly">
                                                                    </Tooltip>
                                                                </div>

                                                                <div>
                                                                    <S4ToggleBtn label=""
                                                                        v-model.number="form.settings.setup.display_logo">
                                                                    </S4ToggleBtn>
                                                                </div>
                                                            </div>
                                                            <div class="flex justify-between items-center">
                                                                <div>
                                                                    <span>Display Progress Bar</span>
                                                                </div>
                                                                <div>
                                                                    <S4ToggleBtn label="" v-model="form.settings.setup.showProgressBar
                                                                        ">
                                                                    </S4ToggleBtn>
                                                                </div>
                                                            </div>
                                                            <!-- <s4-input v-if="form.settings.setup.display_logo === 1"
                                                                color="ssrGray" theme="theme2" class="mt-6" label="Logo"
                                                                v-model="form.settings.setup.footer.logo
                                                                    "></s4-input> -->
                                                            <div class="flex flex-col">
                                                                <jet-label class="text-xs">Logo</jet-label>
                                                                <ImageInput
                                                                    v-if="form.settings.setup.display_logo === 1"
                                                                    class="mt-1 text-black"
                                                                    :modelValue="form.settings.setup.footer.logo"
                                                                    @update:modelValue="(val) => { form.settings.setup.footer.logo = val }">
                                                                </ImageInput>
                                                            </div>


                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ---------- button ---------- -->
                                            <div
                                                class="grid grid-cols-1 pb-5 pt-2 border border-black/0 border-b-slate-100">
                                                <div class="text-slate-800 font-medium text-sm mt-3">Buttons</div>
                                                <div class="group col-span-3">
                                                    <div class="text-slate-400 text-sm">
                                                        <div class="">
                                                            <div class="flex gap-3 buttons-te2-group">
                                                                <!-- <s4-input color="ssrGray" theme="theme2" label="Next Button"
                                                            v-model="form.settings.setup.messages.nextBtn
                                                                "></s4-input>
                                                        <s4-input color="ssrGray" theme="theme2" label="Previous Button"
                                                            v-model="form.settings.setup.messages.prevBtn
                                                                "></s4-input>
                                                        <s4-input color="ssrGray" theme="theme2" label="Finish Button"
                                                            v-model="form.settings.setup.messages.finalBtn
                                                                "></s4-input> -->

                                                                <div class="flex flex-col w-full pt-2">
                                                                    <jet-label class="text-xs">Next Button</jet-label>
                                                                    <TE2 class="text-slate-600"
                                                                        placeholder="Next Button"
                                                                        :modelValue="form.settings.setup.messages.nextBtn"
                                                                        @update:modelValue="(res) => { form.settings.setup.messages.nextBtn = res }"
                                                                        :disablePasteColor="true"
                                                                        :disableImage="true">
                                                                    </TE2>
                                                                </div>
                                                                <div class="flex flex-col w-full pt-2">
                                                                    <jet-label class="text-xs">Previous
                                                                        Button</jet-label>
                                                                    <TE2 class="text-slate-600"
                                                                        placeholder="Previous Button"
                                                                        :modelValue="form.settings.setup.messages.prevBtn"
                                                                        @update:modelValue="(res) => { form.settings.setup.messages.prevBtn = res }"
                                                                        :disablePasteColor="true"
                                                                        :disableImage="true">
                                                                    </TE2>
                                                                </div>
                                                                <div class="flex flex-col w-full pt-2">
                                                                    <jet-label class="text-xs">Finish Button</jet-label>
                                                                    <TE2 class="text-slate-600"
                                                                        placeholder="Finish Button"
                                                                        :modelValue="form.settings.setup.messages.finalBtn"
                                                                        @update:modelValue="(res) => { form.settings.setup.messages.finalBtn = res }"
                                                                        :disablePasteColor="true"
                                                                        :disableImage="true">
                                                                    </TE2>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ---------- randomize questions ---------- -->
                                            <div
                                                class="grid grid-cols-3 pb-8 pt-8 border border-black/0 border-b-slate-100">
                                                <div class="text-slate-800 font-medium text-sm mt-1">Randomise Questions
                                                </div>
                                                <S4ToggleBtn class="pl-1" size="small" label=""
                                                    v-model.number="form.settings.setup.random"
                                                    :disabled="isRandomiseDisabled">
                                                </S4ToggleBtn>
                                                <div class="group col-span-3">
                                                    <div class="text-slate-400 text-sm">
                                                        <div class="" v-if="form.settings.setup.random == 1">
                                                            <select id="option-selection"
                                                                class="mb-2 checked:bg-blue-500 overflow-x-auto form-control w-full h-[22vh] rounded-lg border-slate-300 text-sm focus:border-slate-200 focus:ring-1 focus:ring-slate-400 text-slate-400"
                                                                multiple v-model="form.settings.setup.randomiseSQ">
                                                                <option
                                                                    class="py-1 mb-1 hover:bg-[#bae6fd70] px-1 rounded-lg text-slate-500"
                                                                    v-for="(item, index) in props.questions"
                                                                    :value="item.id" :key="index">
                                                                    <!-- {{ item.question }} -->
                                                                    {{ stripHtmlTags(item.question) }}

                                                                </option>
                                                            </select>
                                                            <span class="text-xs font-medium ml-2">NOTE: <span
                                                                    class="italic">(Ctrl
                                                                    +
                                                                    click) to select for question randomization</span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ---------- variables ---------- -->
                                            <div
                                                class="grid grid-cols-1 pb-8  border border-black/0 border-b-slate-100">
                                                <div class="text-slate-800 font-medium text-sm mt-3">Variables</div>
                                                <div class="group col-span-3">
                                                    <div class="text-slate-400 text-sm">
                                                        <div class="">
                                                            <div>
                                                                <div class="grid grid-cols-2 gap-3">
                                                                    <!-- <s4-input-select theme="theme2" class="mt-6"
                                                                :lists="switchValues" label="Display Logo Only"
                                                                v-model.number="form.settings.setup.display_logo
                                                                    "></s4-input-select> -->
                                                                    <s4-input color="ssrGray" theme="theme2"
                                                                        label="Variable name to pass"
                                                                        v-model="form.settings.setup.variable"></s4-input>
                                                                    <s4-input color="ssrGray" theme="theme2"
                                                                        label="Variable starting number" v-model="form.settings.setup.variable_start
                                                                            "></s4-input>
                                                                    <!-- <s4-input-select theme="theme2" class="mt-6"
                                                                :lists="switchValues" label="Display Footer"
                                                                v-model.number="form.settings.setup.footer.enable
                                                                    "></s4-input-select> -->
                                                                    <!-- <s4-input-select theme="theme2" class="mt-6"
                                                                :lists="switchValues" label="Display Asterisk" v-model="form.settings.setup.display_asterisk
                                                                    "></s4-input-select> -->

                                                                    <!-- <s4-input-select theme="theme2" class="mt-6"
                                                                :lists="switchValues" label="Hide Previous Button"
                                                                v-model="form.settings.setup.hidePrevBtn
                                                                    "></s4-input-select> -->

                                                                    <!-- <s4-input color="ssrGray" theme="theme2" class="mt-6"
                                                                label="Logo" v-model="form.settings.setup.footer.logo
                                                                    "></s4-input> -->

                                                                    <!-- <s4-input-select theme="theme2" class="mt-6"
                                                                :lists="switchValues" label="Show Progressbar" v-model="form.settings.setup.showProgressBar
                                                                    "></s4-input-select> -->

                                                                </div>
                                                                <div v-if="willRebaseVariables"
                                                                    class="mt-3 rounded-md border border-amber-300 bg-amber-50 text-amber-800 px-3 py-2 text-xs">
                                                                    Changing Variable starting number will rebase all
                                                                    skip logic variables on save.
                                                                </div>
                                                                <div class="grid grid-cols-2 mt-2 items-center">
                                                                    <!-- <S4ToggleBtn label="Display Footer" v-model.number="form.settings.setup.footer.enable
                                                                "></S4ToggleBtn>
                                                            <S4ToggleBtn label="Display Asterisk"
                                                                v-model="form.settings.setup.display_asterisk">
                                                            </S4ToggleBtn>
                                                            <S4ToggleBtn label="Hide Previous Button"
                                                                v-model="form.settings.setup.hidePrevBtn"></S4ToggleBtn>
                                                            <S4ToggleBtn label="Show Progressbar"
                                                                Show Progressbar>
                                                            </S4ToggleBtn> -->
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ---------- others ---------- -->
                                            <div class="grid grid-cols-1 pb-8 border border-black/0 border-b-slate-100">
                                                <div class="text-slate-800 font-medium text-sm mt-3 text-center">System
                                                    Messages
                                                </div>
                                                <div class="group col-span-3">
                                                    <div class="text-slate-400 text-sm">
                                                        <div class="">
                                                            <div>
                                                                <!-- ---------- Loading and Saving ---------- -->
                                                                <div class="grid grid-cols-1 border-b-slate-100">
                                                                    <div
                                                                        class="text-slate-800 font-medium text-sm mt-3">
                                                                        Loading
                                                                        and Saving</div>
                                                                    <div class="group col-span-3">
                                                                        <div class="text-slate-400 text-sm">
                                                                            <div class="">
                                                                                <div class="flex gap-3 mt-2">
                                                                                    <div class="flex-1">
                                                                                        <jet-label
                                                                                            class="block text-left text-sm font-normal text-slate-400 origin-left scale-75 mb-1">
                                                                                            Loading Message
                                                                                        </jet-label>
                                                                                        <TE2 class="text-slate-600"
                                                                                            placeholder="Loading Message"
                                                                                            :modelValue="form.settings.setup.messages.loading"
                                                                                            @update:modelValue="(val) => { form.settings.setup.messages.loading = val }"
                                                                                            :disablePasteColor="true">
                                                                                        </TE2>
                                                                                    </div>
                                                                                    <div class="flex-1">
                                                                                        <jet-label
                                                                                            class="block text-left text-sm font-normal text-slate-400 origin-left scale-75 mb-1">
                                                                                            Saving Message
                                                                                        </jet-label>
                                                                                        <TE2 class="text-slate-600"
                                                                                            placeholder="Saving Message"
                                                                                            :modelValue="form.settings.setup.messages.saving"
                                                                                            @update:modelValue="(val) => { form.settings.setup.messages.saving = val }"
                                                                                            :disablePasteColor="true">
                                                                                        </TE2>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                                <div class="text-slate-800 font-medium text-sm mt-3">
                                                                    Error
                                                                    Messages</div>
                                                                <div class="grid grid-cols-1 gap-3">
                                                                    <!-- <s4-input color="ssrGray" theme="theme2"
                                                                label="Invalid Project" v-model="form.settings.setup.modals
                                                                    .invalidProject.title
                                                                    "></s4-input> -->
                                                                    <!-- <s4-input color="ssrGray" theme="theme2" label="Content"
                                                                v-model="form.settings.setup.modals
                                                                    .noIdAssigned.content
                                                                    "></s4-input> -->
                                                                    <!-- <s4-input color="ssrGray" theme="theme2"
                                                                label="Survey Closed" v-model="form.settings.setup.modals
                                                                    .surveyClosed.title
                                                                    "></s4-input> -->
                                                                    <!-- <s4-input color="ssrGray" theme="theme2" label="Content"
                                                                v-model="form.settings.setup.modals
                                                                    .surveyClosed.content
                                                                    "></s4-input> -->
                                                                    <!-- <s4-input color="ssrGray" theme="theme2"
                                                                label="No ID Assigned" v-model="form.settings.setup.modals
                                                                    .noIdAssigned.title
                                                                    "></s4-input> -->
                                                                    <!-- <s4-input color="ssrGray" theme="theme2" label="Content"
                                                                v-model="form.settings.setup.modals
                                                                    .noIdAssigned.content
                                                                    "></s4-input> -->
                                                                    <div class="grid grid-cols-2 gap-3">
                                                                        <div class="flex-1">
                                                                            <jet-label
                                                                                class="block text-left text-sm font-normal text-slate-400 origin-left scale-75 mb-1">
                                                                                Connection Lost
                                                                            </jet-label>
                                                                            <TE2 class="text-slate-600"
                                                                                placeholder="Connection Lost"
                                                                                :modelValue="form.settings.setup.modals.connectionLost.title"
                                                                                @update:modelValue="(val) => { form.settings.setup.modals.connectionLost.title = val }"
                                                                                :disablePasteColor="true">
                                                                            </TE2>
                                                                        </div>
                                                                        <div class="flex-1">
                                                                            <jet-label
                                                                                class="block text-left text-sm font-normal text-slate-400 origin-left scale-75 mb-1">
                                                                                Content
                                                                            </jet-label>
                                                                            <TE2 class="text-slate-600"
                                                                                placeholder="Content"
                                                                                :modelValue="form.settings.setup.modals.connectionLost.content"
                                                                                @update:modelValue="(val) => { form.settings.setup.modals.connectionLost.content = val }"
                                                                                :disablePasteColor="true">
                                                                            </TE2>
                                                                        </div>
                                                                    </div>

                                                                </div>

                                                                <div class="grid grid-cols-1 border-b-slate-100">
                                                                    <div
                                                                        class="text-slate-800 font-medium text-sm mt-6">
                                                                        Warnings</div>
                                                                    <div class="group col-span-3">
                                                                        <div class="text-slate-400 text-sm">
                                                                            <div class="">
                                                                                <div class="flex flex-col mt-2">
                                                                                    <!-- <s4-input color="ssrGray" theme="theme2"
                                                                                label="Required Field Message"
                                                                                :set="initProperty(form.settings.setup.messages, 'required_warning', 'This question requires an answer')"
                                                                                v-model="form.settings.setup.messages['required_warning']"></s4-input> -->
                                                                                    <jet-label class="text-xs">Required
                                                                                        Field
                                                                                        Message</jet-label>
                                                                                    <TE2 class="mb-4 text-slate-600"
                                                                                        :set="initProperty(form.settings.setup.messages, 'required_warning', 'This question requires an answer')"
                                                                                        :modelValue="form.settings.setup.messages['required_warning']"
                                                                                        @update:modelValue="(val) => { form.settings.setup.messages['required_warning'] = val }"
                                                                                        :disablePasteColor="true">
                                                                                    </TE2>
                                                                                    <!-- <s4-input color="ssrGray" theme="theme2"
                                                                                label="If characters less than minimum"
                                                                                :set="initProperty(form.settings.setup.messages, 'input1_warning_min', 'Please input at least @min characters')"
                                                                                v-model="form.settings.setup.messages['input1_warning_min']">
                                                                            </s4-input> -->
                                                                                    <jet-label class="text-xs">If
                                                                                        characters
                                                                                        less than minimum</jet-label>
                                                                                    <TE2 class="mb-4 text-slate-600"
                                                                                        :set="initProperty(form.settings.setup.messages, 'input1_warning_min', 'Please input at least @min characters')"
                                                                                        :modelValue="form.settings.setup.messages['input1_warning_min']"
                                                                                        @update:modelValue="(val) => { form.settings.setup.messages['input1_warning_min'] = val }"
                                                                                        :disablePasteColor="true">
                                                                                    </TE2>
                                                                                    <!-- <s4-input color="ssrGray" theme="theme2"
                                                                                label="If characters greater than maximum"
                                                                                :set="initProperty(form.settings.setup.messages, 'input1_warning_max', 'Please input no more than @max characters')"
                                                                                v-model="form.settings.setup.messages['input1_warning_max']"></s4-input> -->
                                                                                    <jet-label class="text-xs">If
                                                                                        characters
                                                                                        greater than maximum</jet-label>
                                                                                    <TE2 class="mb-4 text-slate-600"
                                                                                        :set="initProperty(form.settings.setup.messages, 'input1_warning_max', 'Please input no more than @max characters')"
                                                                                        :modelValue="form.settings.setup.messages['input1_warning_max']"
                                                                                        @update:modelValue="(val) => { form.settings.setup.messages['input1_warning_max'] = val }"
                                                                                        :disablePasteColor="true">
                                                                                    </TE2>

                                                                                    <!-- <s4-input color="ssrGray" theme="theme2"
                                                                                label="If number exceed set min and max limits"
                                                                                :set="initProperty(form.settings.setup.messages, 'input2_warning_minmax', 'Please input a number between @min and @max')"
                                                                                v-model="form.settings.setup.messages['input2_warning_minmax']"></s4-input> -->
                                                                                    <jet-label class="text-xs">If number
                                                                                        exceed
                                                                                        set min and max
                                                                                        limits</jet-label>
                                                                                    <TE2 class="mb-4 text-slate-600"
                                                                                        :set="initProperty(form.settings.setup.messages, 'input2_warning_minmax', 'Please input a number between @min and @max')"
                                                                                        :modelValue="form.settings.setup.messages['input2_warning_minmax']"
                                                                                        @update:modelValue="(val) => { form.settings.setup.messages['input2_warning_minmax'] = val }"
                                                                                        :disablePasteColor="true">
                                                                                    </TE2>

                                                                                    <!-- <s4-input color="ssrGray" theme="theme2"
                                                                                label="If the number entered is too low"
                                                                                :set="initProperty(form.settings.setup.messages, 'input2_warning_min', 'Please input a number greater than or equal to @min')"
                                                                                v-model="form.settings.setup.messages['input2_warning_min']"></s4-input> -->
                                                                                    <jet-label class="text-xs">If the
                                                                                        number
                                                                                        entered is too low</jet-label>
                                                                                    <TE2 class="mb-4 text-slate-600"
                                                                                        :set="initProperty(form.settings.setup.messages, 'input2_warning_min', 'Please input a number greater than or equal to @min')"
                                                                                        :modelValue="form.settings.setup.messages['input2_warning_min']"
                                                                                        @update:modelValue="(val) => { form.settings.setup.messages['input2_warning_min'] = val }"
                                                                                        :disablePasteColor="true">
                                                                                    </TE2>

                                                                                    <!-- <s4-input color="ssrGray" theme="theme2"
                                                                                label="If the number entered is too high"
                                                                                :set="initProperty(form.settings.setup.messages, 'input2_warning_max', 'Please input a number less than or equal to @max')"
                                                                                v-model="form.settings.setup.messages['input2_warning_max']"></s4-input> -->
                                                                                    <jet-label class="text-xs">If the
                                                                                        number
                                                                                        entered is too high</jet-label>
                                                                                    <TE2 class="mb-4 text-slate-600"
                                                                                        :set="initProperty(form.settings.setup.messages, 'input2_warning_max', 'Please input a number less than or equal to @max')"
                                                                                        :modelValue="form.settings.setup.messages['input2_warning_max']"
                                                                                        @update:modelValue="(val) => { form.settings.setup.messages['input2_warning_max'] = val }"
                                                                                        :disablePasteColor="true">
                                                                                    </TE2>

                                                                                    <!-- <s4-input color="ssrGray" theme="theme2"
                                                                                label="If only accepting alphanumeric characters"
                                                                                :set="initProperty(form.settings.setup.messages, 'input9_warning_alphanumeric', 'Please input alphanumeric characters only. Special symbols and spaces are not allowed.')"
                                                                                v-model="form.settings.setup.messages['input9_warning_alphanumeric']"></s4-input> -->
                                                                                    <jet-label class="text-xs">If only
                                                                                        accepting
                                                                                        alphanumeric
                                                                                        characters</jet-label>
                                                                                    <TE2 class="mb-4 text-slate-600"
                                                                                        :set="initProperty(form.settings.setup.messages, 'input9_warning_alphanumeric', 'Please input alphanumeric characters only. Special symbols and spaces are not allowed.')"
                                                                                        :modelValue="form.settings.setup.messages['input9_warning_alphanumeric']"
                                                                                        @update:modelValue="(val) => { form.settings.setup.messages['input9_warning_alphanumeric'] = val }"
                                                                                        :disablePasteColor="true">
                                                                                    </TE2>

                                                                                    <!-- <s4-input color="ssrGray" theme="theme2"
                                                                                label="If only accepting letters"
                                                                                :set="initProperty(form.settings.setup.messages, 'input8_warning_letters', 'Please input letters only. Numbers and special characters are not allowed.')"
                                                                                v-model="form.settings.setup.messages['input8_warning_letters']"></s4-input> -->
                                                                                    <jet-label class="text-xs">If only
                                                                                        accepting
                                                                                        letters</jet-label>
                                                                                    <TE2 class="mb-4 text-slate-600"
                                                                                        :set="initProperty(form.settings.setup.messages, 'input8_warning_letters', 'Please input letters only. Numbers and special characters are not allowed.')"
                                                                                        :modelValue="form.settings.setup.messages['input8_warning_letters']"
                                                                                        @update:modelValue="(val) => { form.settings.setup.messages['input8_warning_letters'] = val }"
                                                                                        :disablePasteColor="true">
                                                                                    </TE2>

                                                                                    <!-- <s4-input color="ssrGray" theme="theme2"
                                                                                label="If invalid email address"
                                                                                :set="initProperty(form.settings.setup.messages, 'input4_warning_invalidemail', 'Please input a valid email address')"
                                                                                v-model="form.settings.setup.messages['input4_warning_invalidemail']"></s4-input> -->
                                                                                    <jet-label class="text-xs">If
                                                                                        invalid
                                                                                        email
                                                                                        address</jet-label>
                                                                                    <TE2 class="text-slate-600"
                                                                                        :set="initProperty(form.settings.setup.messages, 'input4_warning_invalidemail', 'Please input a valid email address')"
                                                                                        :modelValue="form.settings.setup.messages['input4_warning_invalidemail']"
                                                                                        @update:modelValue="(val) => { form.settings.setup.messages['input4_warning_invalidemail'] = val }"
                                                                                        :disablePasteColor="true">
                                                                                    </TE2>



                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                </div>


                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ---------- save ---------- -->


                                        </div>
                                        <!-- <div class="grid grid-cols-4 ">
                                            <div class="text-slate-800 font-medium text-sm">
                                                <div class="flex gap-3 my-2 w-full">
                                                    <button @click="saveSettings" type='button'
                                                        class='mt-0.5 flex break-inside bg-[var(--mcolor)] w-32 rounded-lg px-4 py-2 hover:bg-[var(--mcolor2)]'>
                                                        <div
                                                            class='flex items-center justify-center flex-1 text-[var(--txtcolor)]'>
                                                            <span class='text-sm font-semibold text-white'>Save</span>
                                                        </div>
                                                    </button>
                                                </div>
                                            </div>
                                        </div> -->
                                    </div>
                                    <div v-if="tab.name === 'Redirects'">
                                        <div class="overflow-y-auto overflow-x-hidden h-[55vh] w-full pr-2 pl-2">
                                            <!-- ---------- complete ---------- -->
                                            <div
                                                class="grid grid-cols-1 pb-10 border border-black/0 border-b-slate-100">
                                                <div class="text-slate-800 font-medium text-sm mt-3">Complete</div>
                                                <div class="group col-span-3">
                                                    <div class="text-slate-400 text-sm">
                                                        <div>
                                                            <div class="grid grid-cols-1">
                                                                <div class="col-span-2">
                                                                    <!-- <s4-input color="ssrGray" theme="theme2"
                                                                        label="Redirect Message" v-model="form.settings.setup.redirects
                                                                            .complete.content
                                                                            "></s4-input> -->
                                                                    <jet-label
                                                                        class="block text-left text-sm font-normal text-slate-400 origin-left scale-75">
                                                                        Redirect Message
                                                                    </jet-label>
                                                                    <TE2 class="text-slate-600 text-left"
                                                                        placeholder="Redirect Message"
                                                                        :modelValue="form.settings.setup.redirects.complete.content"
                                                                        @update:modelValue="(val) => { form.settings.setup.redirects.complete.content = val }"
                                                                        :disablePasteColor="true">
                                                                    </TE2>
                                                                </div>
                                                                <div class="col-span-3">
                                                                    <s4-input color="ssrGray" theme="theme2" label="URL"
                                                                        v-model="form.settings.setup.redirects
                                                                            .complete.url
                                                                            "></s4-input>
                                                                </div>
                                                            </div>
                                                            <div class="flex gap-3 mt-2">
                                                                <s4-input color="ssrGray" theme="theme2"
                                                                    label="Delay Duration" v-model="form.settings.setup.redirects
                                                                        .complete.duration
                                                                        "></s4-input>
                                                                <s4-input-select theme="theme2" class="mt-6"
                                                                    :lists="switchValues" label="Pass Internal"
                                                                    v-model.number="form.settings.setup.redirects
                                                                        .complete.passInternalVars
                                                                        "></s4-input-select>
                                                                <s4-input-select theme="theme2" class="mt-6"
                                                                    :lists="switchValues" label="Pass External"
                                                                    v-model.number="form.settings.setup.redirects
                                                                        .complete.passExternalVars
                                                                        "></s4-input-select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ---------- quality control ---------- -->
                                            <div
                                                class="grid grid-cols-1 pb-8 pt-2 border border-black/0 border-b-slate-100">
                                                <div class="text-slate-800 font-medium text-sm mt-3 mb-3 ">Quality
                                                    Control
                                                </div>
                                                <div class="group col-span-3">
                                                    <div class="text-slate-400 text-sm">
                                                        <div>
                                                            <div class="grid grid-cols-2">
                                                                <div class="col-span-2">
                                                                    <jet-label
                                                                        class="block text-left text-sm font-normal text-slate-400 origin-left scale-75">
                                                                        Redirect Message
                                                                    </jet-label>
                                                                    <TE2 class="text-slate-600"
                                                                        placeholder="Redirect Message"
                                                                        :modelValue="form.settings.setup.redirects.qualityControl.content"
                                                                        @update:modelValue="(val) => { form.settings.setup.redirects.qualityControl.content = val }"
                                                                        :disablePasteColor="true">
                                                                    </TE2>
                                                                </div>
                                                                <div class="col-span-3">
                                                                    <s4-input color="ssrGray" theme="theme2" label="URL"
                                                                        v-model="form.settings.setup.redirects
                                                                            .qualityControl.url
                                                                            "></s4-input>
                                                                </div>
                                                            </div>
                                                            <div class="flex gap-3 mt-2">
                                                                <s4-input color="ssrGray" theme="theme2"
                                                                    label="Delay Duration" v-model="form.settings.setup.redirects
                                                                        .qualityControl.duration
                                                                        "></s4-input>
                                                                <s4-input-select theme="theme2" class="mt-6"
                                                                    :lists="switchValues" label="Pass Internal"
                                                                    v-model.number="form.settings.setup.redirects
                                                                        .qualityControl.passInternalVars
                                                                        "></s4-input-select>
                                                                <s4-input-select theme="theme2" class="mt-6"
                                                                    :lists="switchValues" label="Pass External"
                                                                    v-model.number="form.settings.setup.redirects
                                                                        .qualityControl.passExternalVars
                                                                        "></s4-input-select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ---------- screenout ---------- -->
                                            <div
                                                class="grid grid-cols-1 pb-8 pt-2 border border-black/0 border-b-slate-100">
                                                <div class="text-slate-800 font-medium text-sm mt-3 mb-3">Screenout
                                                </div>
                                                <div class="group col-span-3">
                                                    <div class="text-slate-400 text-sm">
                                                        <div>
                                                            <div class="grid grid-cols-1">
                                                                <div class="col-span-2">
                                                                    <jet-label
                                                                        class="block text-left text-sm font-normal text-slate-400 origin-left scale-75">
                                                                        Redirect Message
                                                                    </jet-label>
                                                                    <TE2 class="text-slate-600"
                                                                        placeholder="Redirect Message"
                                                                        :modelValue="form.settings.setup.redirects.screenout.content"
                                                                        @update:modelValue="(val) => { form.settings.setup.redirects.screenout.content = val }"
                                                                        :disablePasteColor="true">
                                                                    </TE2>
                                                                </div>
                                                                <div class="col-span-3">
                                                                    <s4-input color="ssrGray" theme="theme2" label="URL"
                                                                        v-model="form.settings.setup.redirects
                                                                            .screenout.url
                                                                            "></s4-input>
                                                                </div>
                                                            </div>
                                                            <div class="flex gap-3 mt-2">
                                                                <s4-input color="ssrGray" theme="theme2"
                                                                    label="Delay Duration" v-model="form.settings.setup.redirects
                                                                        .screenout.duration
                                                                        "></s4-input>
                                                                <s4-input-select theme="theme2" class="mt-6"
                                                                    :lists="switchValues" label="Pass Internal"
                                                                    v-model.number="form.settings.setup.redirects
                                                                        .screenout.passInternalVars
                                                                        "></s4-input-select>
                                                                <s4-input-select theme="theme2" class="mt-6"
                                                                    :lists="switchValues" label="Pass External"
                                                                    v-model.number="form.settings.setup.redirects
                                                                        .screenout.passExternalVars
                                                                        "></s4-input-select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ---------- CUSTOM REDIRECTS ---------- -->
                                            <div
                                                class="grid grid-cols-4 pb-8 pt-8 border border-black/0 border-b-slate-100 hidden">
                                                <div class="font-medium text-sm">
                                                    <div class="text-slate-800 font-medium text-sm -mt-1">Custom
                                                        Redirects
                                                    </div>
                                                </div>
                                                <div class="group col-span-3">
                                                    <div class="text-slate-400 text-sm">
                                                        <div>
                                                            <div class="grid grid-cols-1 gap-3">
                                                                <Redirects />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ---------- save ---------- -->


                                        </div>
                                        <!-- <div class="grid grid-cols-4">
                                            <div class="text-slate-800 font-medium text-sm">
                                                <div class="flex gap-3 my-2 w-full">
                                                    <button @click="saveSettings" type='button'
                                                        class='mt-0.5 flex break-inside bg-[var(--mcolor)] w-32 rounded-lg px-4 py-2 hover:bg-[var(--mcolor2)]'>
                                                        <div
                                                            class='flex items-center justify-center flex-1 text-[var(--txtcolor)]'>
                                                            <span class='text-sm font-semibold text-white'>Save</span>
                                                        </div>
                                                    </button>
                                                </div>
                                            </div>
                                        </div> -->
                                    </div>
                                    <!-- <div v-if="tab === 3"> -->

                                    <!-- JS -->

                                    <!-- <div v-if="js_pos == 'default'" class="relative w-full h-[30vh]">
                                        <div class="flex justify-between">
                                            <div
                                                class="text-sm font-semibold text-slate-700 bg-amber-200 w-20 justify-center grid py-1 rounded-t-lg">
                                                JS</div>
                                            <div
                                                class="flex self-center text-xs mt-1 text-slate-800 gap-2 cursor-pointer mr-2">
                                                <div @click="setPosJs('side')"
                                                    class="bg-slate-200 px-4 py-1 rounded-t-lg hover:bg-slate-300">
                                                    Side
                                                    Mount
                                                </div>
                                                <div @click="setPosJs('full')"
                                                    class="bg-slate-200 px-4 py-1 rounded-t-lg hover:bg-slate-300">
                                                    Full
                                                    Screen
                                                </div>
                                            </div>
                                        </div>
                                        <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                            v-model="form.settings.setup.customJS">
                                        </prism-editor>
                                    </div>

                                    <div v-if="js_pos == 'full'"
                                        class="fixed top-0 left-[100px] w-[95vw] h-[100vh] p-10 z-50 backdrop-blur-md">
                                        <div class="flex justify-between">
                                            <div
                                                class="text-sm font-semibold text-slate-700 bg-amber-200 w-20 justify-center grid py-1 rounded-t-lg">
                                                JS</div>
                                            <div
                                                class="flex self-center text-sm text-slate-800 gap-2 cursor-pointer mr-2">
                                                <div @click="setPosJs('side')"
                                                    class="bg-slate-400 text-white px-4 py-1 rounded-t-lg hover:bg-slate-500">
                                                    Side Mount
                                                </div>
                                                <div @click="setPosJs('default')"
                                                    class="bg-slate-600 text-white px-4 py-1 rounded-t-lg hover:bg-slate-700">
                                                    Close</div>
                                                <div @click="saveSettings"
                                                    class="bg-[var(--mcolor)] w-24 grid justify-center text-white px-4 py-1 rounded-t-lg hover:bg-[var(--mcolor2)]">
                                                    Save</div>
                                            </div>
                                        </div>
                                        <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                            v-model="form.settings.setup.customJS">
                                        </prism-editor>
                                    </div>

                                    <div v-if="js_pos == 'side-left'"
                                        class="fixed top-0 left-[100px] w-[50vw] h-[100vh] p-10 z-50 backdrop-blur-md">
                                        <div class="flex justify-between">
                                            <div
                                                class="text-sm font-semibold text-slate-700 bg-amber-200 w-20 justify-center grid py-1 rounded-t-lg">
                                                JS</div>
                                            <div
                                                class="flex self-center text-sm text-slate-800 gap-2 mr-2 cursor-pointer">
                                                <div @click="setPosJs('full')"
                                                    class="bg-slate-400 text-white px-4 py-1 rounded-t-lg hover:bg-slate-500">
                                                    Full Screen
                                                </div>
                                                <div @click="setPosJs('default')"
                                                    class="bg-slate-600 text-white px-4 py-1 rounded-t-lg hover:bg-slate-700">
                                                    Close</div>
                                                <div @click="saveSettings"
                                                    class="bg-[var(--mcolor)] w-24 grid justify-center text-white px-4 py-1 rounded-t-lg hover:bg-[var(--mcolor2)]">
                                                    Save</div>
                                            </div>
                                        </div>
                                        <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                            v-model="form.settings.setup.customJS">
                                        </prism-editor>
                                    </div>

                                    <div v-if="js_pos == 'side-right'"
                                        class="fixed top-0 right-0 w-[50vw] h-[100vh] p-10 z-50 backdrop-blur-md">
                                        <div class="flex justify-between">
                                            <div
                                                class="text-sm font-semibold text-slate-700 bg-amber-200 w-20 justify-center grid py-1 rounded-t-lg">
                                                JS</div>
                                            <div
                                                class="flex self-center text-sm text-slate-800 gap-2 mr-2 cursor-pointer">
                                                <div @click="setPosJs('full')"
                                                    class="bg-slate-400 text-white px-4 py-1 rounded-t-lg hover:bg-slate-500">
                                                    Full Screen
                                                </div>
                                                <div @click="setPosJs('default')"
                                                    class="bg-slate-600 text-white px-4 py-1 rounded-t-lg hover:bg-slate-700">
                                                    Close</div>
                                                <div @click="saveSettings"
                                                    class="bg-[var(--mcolor)] w-24 grid justify-center text-white px-4 py-1 rounded-t-lg hover:bg-[var(--mcolor2)]">
                                                    Save</div>
                                            </div>
                                        </div>
                                        <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                            v-model="form.settings.setup.customJS">
                                        </prism-editor>
                                    </div> -->

                                    <!-- CSS -->
                                    <!-- 
                                    <div v-if="css_pos == 'default'" class="relative w-full h-[30vh]"
                                        :class="{ 'mt-12': js_pos == 'default' }">
                                        <div class="flex justify-between">
                                            <div
                                                class="text-sm font-semibold text-slate-700 bg-sky-200 w-20 justify-center grid py-1 rounded-t-lg">
                                                CSS</div>
                                            <div
                                                class="flex self-center text-xs mt-1 text-slate-800 gap-2 cursor-pointer mr-2">
                                                <div @click="setPosCss('side')"
                                                    class="bg-slate-200 px-4 py-1 rounded-t-lg hover:bg-slate-300">
                                                    Side
                                                    Mount
                                                </div>
                                                <div @click="setPosCss('full')"
                                                    class="bg-slate-200 px-4 py-1 rounded-t-lg hover:bg-slate-300">
                                                    Full
                                                    Screen
                                                </div>
                                            </div>
                                        </div>
                                        <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                            v-model="form.settings.setup.customCSS">
                                        </prism-editor>
                                    </div>

                                    <div v-if="css_pos == 'full'"
                                        class="fixed top-0 left-[100px] w-[95vw] h-[100vh] p-10 z-50 backdrop-blur-md">
                                        <div class="flex justify-between">
                                            <div
                                                class="text-sm font-semibold text-slate-700 bg-sky-200 w-20 justify-center grid py-1 rounded-t-lg">
                                                CSS</div>
                                            <div
                                                class="flex self-center text-sm text-slate-800 gap-2 cursor-pointer mr-2">
                                                <div @click="setPosCss('side')"
                                                    class="bg-slate-400 text-white px-4 py-1 rounded-t-lg hover:bg-slate-500">
                                                    Side Mount
                                                </div>
                                                <div @click="setPosCss('default')"
                                                    class="bg-slate-600 text-white px-4 py-1 rounded-t-lg hover:bg-slate-700">
                                                    Close</div>
                                                <div @click="saveSettings"
                                                    class="bg-[var(--mcolor)] w-24 grid justify-center text-white px-4 py-1 rounded-t-lg hover:bg-[var(--mcolor2)]">
                                                    Save</div>
                                            </div>
                                        </div>
                                        <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                            v-model="form.settings.setup.customCSS">
                                        </prism-editor>
                                    </div>

                                    <div v-if="css_pos == 'side-left'"
                                        class="fixed top-0 left-[100px] w-[50vw] h-[100vh] p-10 z-50 backdrop-blur-md">
                                        <div class="flex justify-between">
                                            <div
                                                class="text-sm font-semibold text-slate-700 bg-sky-200 w-20 justify-center grid py-1 rounded-t-lg">
                                                CSS</div>
                                            <div
                                                class="flex self-center text-sm text-slate-800 gap-2 mr-2 cursor-pointer">
                                                <div @click="setPosCss('full')"
                                                    class="bg-slate-400 text-white px-4 py-1 rounded-t-lg hover:bg-slate-500">
                                                    Full Screen
                                                </div>
                                                <div @click="setPosCss('default')"
                                                    class="bg-slate-600 text-white px-4 py-1 rounded-t-lg hover:bg-slate-700">
                                                    Close</div>
                                                <div @click="saveSettings"
                                                    class="bg-[var(--mcolor)] w-24 grid justify-center text-white px-4 py-1 rounded-t-lg hover:bg-[var(--mcolor2)]">
                                                    Save</div>
                                            </div>
                                        </div>
                                        <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                            v-model="form.settings.setup.customCSS">
                                        </prism-editor>
                                    </div>

                                    <div v-if="css_pos == 'side-right'"
                                        class="fixed top-0 right-0 w-[50vw] h-[100vh] p-10 z-50 backdrop-blur-md">
                                        <div class="flex justify-between">
                                            <div
                                                class="text-sm font-semibold text-slate-700 bg-sky-200 w-20 justify-center grid py-1 rounded-t-lg">
                                                CSS</div>
                                            <div
                                                class="flex self-center text-sm text-slate-800 gap-2 mr-2 cursor-pointer">
                                                <div @click="setPosCss('full')"
                                                    class="bg-slate-400 text-white px-4 py-1 rounded-t-lg hover:bg-slate-500">
                                                    Full Screen
                                                </div>
                                                <div @click="setPosCss('default')"
                                                    class="bg-slate-600 text-white px-4 py-1 rounded-t-lg hover:bg-slate-700">
                                                    Close</div>
                                                <div @click="saveSettings"
                                                    class="bg-[var(--mcolor)] w-24 grid justify-center text-white px-4 py-1 rounded-t-lg hover:bg-[var(--mcolor2)]">
                                                    Save</div>
                                            </div>
                                        </div>
                                        <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                            v-model="form.settings.setup.customCSS">
                                        </prism-editor>
                                    </div> -->


                                    <!-- ---------- save ---------- -->
                                    <!-- <div class="grid grid-cols-4 pb-2 pt-8 border border-black/0 border-b-slate-100">
                                        <div class="text-slate-800 font-medium text-sm">
                                            <div class="flex gap-3 my-2 w-full">
                                                <button @click="saveSettings" type='button'
                                                    class='mt-0.5 flex break-inside bg-[var(--mcolor)] w-32 rounded-lg px-4 py-2 hover:bg-[var(--mcolor2)]'>
                                                    <div
                                                        class='flex items-center justify-center flex-1 text-[var(--txtcolor)]'>
                                                        <span class='text-sm font-semibold text-white'>Save</span>
                                                    </div>
                                                </button>
                                            </div>
                                        </div>
                                    </div> -->

                                    <!-- </div> -->
                                </div>
                            </div>
                        </Transition>

                    </div>



                    <!-- --------------------------------------------- -->

                    <!-- <div class="duration-300 transition-all absolute duration-300 transition-all top-1 flex gap-6 w-full h-full border-t pt-8">
                    <Redirects />
                </div> -->

                    <div v-if="0" class="duration-300 transition-all">
                        <div class="flex gap-4 flex-row mb-6">

                            <!-- <div class="relative border rounded-lg p-2 min-w-[18vw] w-[22vw] shadow-xl bg-white">
                            <div
                                class="absolute -top-5 left-1 px-2 bg-white gap-1 rounded-full justify-between mx-1 shadow-md">
                                <div class="flex items-center relative p-1 w-28 justify-end">
                                    <div class="text-gray-500 border-2 rounded-full p-1 absolute -left-2">
                                        <IconsVue icon="setting" />
                                    </div>
                                    <div class="text-slate-500">GENERAL</div>
                                </div>
                            </div>
                            <div>
                                <s4-input theme="theme2" label="Survey Name" v-model="form.settings.name"></s4-input>
                                <s4-input-select theme="theme2" label="Randomise Questions" :lists="switchValues"
                                    v-model.number="form.settings.setup.random"></s4-input-select>
                                <s4-input theme="theme2" label="Survey Link" readonly :value="$page.props.engine_url +
                                    '?code=' +
                                    $page.props.questionnaire.code
                                    "></s4-input>
                            </div>
                        </div> -->

                            <!-- <div class="relative rounded-lg p-2 grow shadow-xl bg-white border">
                            <div
                                class="absolute -top-5 left-1 px-2 bg-white gap-1 rounded-full justify-between mx-1 shadow-md">
                                <div class="flex items-center relative p-1 w-32 justify-end">
                                    <div class="text-gray-500 border-2 rounded-full p-1 absolute -left-2">
                                        <IconsVue icon="link" />
                                    </div>
                                    <div class="text-slate-500">REDIRECTS</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="grow w-[20vw]">
                                    <s4-input theme="theme2" label="Complete" v-model="form.settings.setup.redirects
                                        .complete.url
                                        "></s4-input>
                                    <s4-input theme="theme2" label="Quality Control" v-model="form.settings.setup.redirects
                                        .qualityControl.url
                                        "></s4-input>
                                    <s4-input theme="theme2" label="Screenout" v-model="form.settings.setup.redirects
                                        .screenout.url
                                        "></s4-input>
                                </div>
                                <div class="w-[8vw]">
                                    <s4-input theme="theme2" label="Duration" v-model="form.settings.setup.redirects
                                        .complete.duration
                                        "></s4-input>
                                    <s4-input theme="theme2" label="Duration" v-model="form.settings.setup.redirects
                                        .qualityControl.duration
                                        "></s4-input>
                                    <s4-input theme="theme2" label="Duration" v-model="form.settings.setup.redirects
                                        .screenout.duration
                                        "></s4-input>
                                </div>
                                <div class="w-[8vw]">
                                    <s4-input-select theme="theme2" :lists="switchValues" label="Pass Internal"
                                        v-model.number="form.settings.setup.redirects
                                            .complete.passInternalVars
                                            "></s4-input-select>
                                    <s4-input-select theme="theme2" :lists="switchValues" label="Pass Internal"
                                        v-model.number="form.settings.setup.redirects
                                            .qualityControl.passInternalVars
                                            "></s4-input-select>
                                    <s4-input-select theme="theme2" :lists="switchValues" label="Pass Internal"
                                        v-model.number="form.settings.setup.redirects
                                            .screenout.passInternalVars
                                            "></s4-input-select>
                                </div>
                                <div class="w-[8vw]">
                                    <s4-input-select theme="theme2" :lists="switchValues" label="Pass External"
                                        v-model.number="form.settings.setup.redirects
                                            .complete.passExternalVars
                                            "></s4-input-select>
                                    <s4-input-select theme="theme2" :lists="switchValues" label="Pass External" v-model="form.settings.setup.redirects
                                        .qualityControl.passExternalVars
                                        "></s4-input-select>
                                    <s4-input-select theme="theme2" :lists="switchValues" label="Pass External" v-model="form.settings.setup.redirects
                                        .screenout.passExternalVars
                                        "></s4-input-select>
                                </div>
                                <div class="grow w-[20vw]">
                                    <s4-input theme="theme2" label="Message" v-model="form.settings.setup.redirects
                                        .complete.content
                                        "></s4-input>
                                    <s4-input theme="theme2" label="Message" v-model="form.settings.setup.redirects
                                        .qualityControl.content
                                        "></s4-input>
                                    <s4-input theme="theme2" label="Message" v-model="form.settings.setup.redirects
                                        .screenout.content
                                        "></s4-input>
                                </div>
                            </div>
                        </div> -->

                        </div>
                        <div class="flex gap-4 flex-row">
                            <!-- <div class="relative shadow-xl bg-white border rounded-lg p-2 grow">
                            <div
                                class="absolute -top-5 left-1 px-2 bg-white gap-1 rounded-full justify-between mx-1 shadow-md">
                                <div class="flex items-center relative p-1 w-28 justify-end">
                                    <div class="text-gray-500 border-2 rounded-full p-1 absolute -left-2">
                                        <IconsVue icon="click" />
                                    </div>
                                    <div class="text-slate-500">BUTTONS</div>
                                </div>
                            </div>
                            <div>
                                <s4-input theme="theme2" label="Next Button" v-model="form.settings.setup.messages.nextBtn
                                    "></s4-input>
                                <s4-input theme="theme2" label="Loading Message" v-model="form.settings.setup.messages.loading
                                    "></s4-input>
                                <s4-input theme="theme2" label="Saving Message" v-model="form.settings.setup.messages.saving
                                    "></s4-input>
                                <s4-input theme="theme2" label="Previous Button" v-model="form.settings.setup.messages.prevBtn
                                    "></s4-input>
                                <s4-input theme="theme2" label="Finish Button" v-model="form.settings.setup.messages.finalBtn
                                    "></s4-input>
                            </div>
                        </div> -->
                            <!-- <div class="relative rounded-lg p-2 grow shadow-xl bg-white border">
                            <div
                                class="absolute -top-5 left-1 px-2 bg-white gap-1 rounded-full justify-between mx-1 shadow-md">
                                <div class="flex items-center relative p-1 w-24 justify-end">
                                    <div class="text-gray-500 border-2 rounded-full p-1 absolute -left-2">
                                        <IconsVue icon="other" />
                                    </div>
                                    <div class="text-slate-500">OTHER</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="grow">
                                    <s4-input theme="theme2" label="Invalid Project" v-model="form.settings.setup.modals
                                        .invalidProject.title
                                        "></s4-input>
                                    <s4-input theme="theme2" label="Content" v-model="form.settings.setup.modals
                                        .noIdAssigned.content
                                        "></s4-input>
                                    <s4-input theme="theme2" label="Survey Closed" v-model="form.settings.setup.modals
                                        .surveyClosed.title
                                        "></s4-input>
                                    <s4-input theme="theme2" label="Content" v-model="form.settings.setup.modals
                                        .surveyClosed.content
                                        "></s4-input>
                                </div>
                                <div class="grow">
                                    <s4-input theme="theme2" label="No ID Assigned" v-model="form.settings.setup.modals
                                        .noIdAssigned.title
                                        "></s4-input>
                                    <s4-input theme="theme2" label="Content" v-model="form.settings.setup.modals
                                        .noIdAssigned.content
                                        "></s4-input>
                                    <s4-input theme="theme2" label="Connection Lost" v-model="form.settings.setup.modals
                                        .connectionLost.title
                                        "></s4-input>
                                    <s4-input theme="theme2" label="Content" v-model="form.settings.setup.modals
                                        .connectionLost.content
                                        "></s4-input>
                                </div>
                            </div>
                        </div> -->
                            <!-- <div class="relative rounded-lg p-2 grow shadow-xl bg-white border">
                            <div
                                class="absolute -top-5 left-1 px-2 bg-white gap-1 rounded-full justify-between mx-1 shadow-md">
                                <div class="flex items-center relative p-1 w-32 justify-end">
                                    <div class="text-gray-500 border-2 rounded-full p-1 absolute -left-2">
                                        <IconsVue icon="variable" />
                                    </div>
                                    <div class="text-slate-500">VARIABLES</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="grow">
                                    <s4-input-select theme="theme2" :lists="switchValues" label="Display Logo Only"
                                        v-model.number="form.settings.setup.display_logo
                                            "></s4-input-select>
                                    <s4-input theme="theme2" label="Variable name to pass"
                                        v-model="form.settings.setup.variable"></s4-input>
                                    <s4-input theme="theme2" label="Variable starting number" v-model="form.settings.setup.variable_start
                                        "></s4-input>
                                    <s4-input-select theme="theme2" :lists="switchValues" label="Display Footer"
                                        v-model.number="form.settings.setup.footer.enable
                                            "></s4-input-select>
                                </div>
                                <div class="grow">
                                    <s4-input-select theme="theme2" :lists="switchValues" label="Display Asterisk" v-model="form.settings.setup.display_asterisk
                                        "></s4-input-select>
                                    <s4-input-select theme="theme2" :lists="switchValues" label="Hide Previous Button"
                                        v-model="form.settings.setup.hidePrevBtn
                                            "></s4-input-select>
                                    <s4-input theme="theme2" label="Logo" v-model="form.settings.setup.footer.logo
                                        "></s4-input>
                                    <s4-input-select theme="theme2" :lists="switchValues" label="Show Progressbar" v-model="form.settings.setup.showProgressBar
                                        "></s4-input-select>
                                </div>
                            </div>
                        </div> -->
                        </div>

                        <!-- <div>
                        <div class="mt-7 mx-4" v-if="form.settings.setup.random == 0">
                            <div class="relative">
                                <div
                                    class="absolute -top-5 left-1 px-2 bg-white gap-1 border rounded-full justify-between mx-1">
                                    <div class="flex items-center relative p-1 w-64 justify-end">
                                        <div class="text-gray-500 border-2 rounded-full p-1 absolute -left-2">
                                            <IconsVue icon="question" />
                                        </div>
                                        <div class="text-slate-500">
                                            Randomise selected question
                                        </div>
                                    </div>
                                </div>
                                <select
                                    class="form-control w-full h-[22vh] selSet rounded-lg focus:border-ssr-blue3 focus:ring-1 border-ssr-blue3 focus:ring-ssr-blue3"
                                    multiple v-model="form.settings.setup.randomiseSQ">
                                    <option v-for="(item, index) in props.questions" :value="item.id" :key="index">
                                        {{ item.question }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div> -->

                    </div>

                    <!-- <div class="absolute duration-300 transition-all top-1 flex gap-6 w-full h-full border-t pt-8">
                    <div class="relative rounded-lg p-2 grow shadow-xl bg-white border pt-6 w-[10vw]">
                        <div class="absolute -top-5 left-1 px-2 bg-white gap-1 rounded-full justify-between mx-1 shadow-md">
                            <div class="flex items-center relative p-1 w-36 justify-end">
                                <div class="text-gray-500 border-2 rounded-full p-1 absolute -left-2">
                                    <IconsVue icon="terminal" />
                                </div>
                                <div class="text-slate-500">CUSTOME JS</div>
                            </div>
                        </div>
                        <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                            v-model="form.settings.setup.customJS">
                        </prism-editor>
                    </div>
                    <div class="relative rounded-lg p-2 grow shadow-xl bg-white border pt-6 w-[10vw]">
                        <div class="absolute -top-5 left-1 px-2 bg-white gap-1 rounded-full justify-between mx-1 shadow-md">
                            <div class="flex items-center relative p-1 w-40 justify-end">
                                <div class="text-gray-500 border-2 rounded-full p-1 absolute -left-2">
                                    <IconsVue icon="terminal" />
                                </div>
                                <div class="text-slate-500">CUSTOME CSS</div>
                            </div>
                        </div>
                        <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                            v-model="form.settings.setup.customCSS">
                        </prism-editor>
                    </div>
                </div> -->



                </div>

            </template>
        </SliderBarVue>

    </div>
</template>

<style scoped>
option:checked {
    box-shadow: 0 0 10px 100px #0369a170 inset;
    color: white;
}

.dot {
    transition: transform 0.3s ease;
}

/* Make TE2 button editors' tooltip wider (Next/Previous/Finish buttons) */
.buttons-te2-group :deep(.don-editor .ql-tooltip) {
    min-width: 370px !important;
}
</style>
