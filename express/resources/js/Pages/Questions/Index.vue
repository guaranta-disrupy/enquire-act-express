<script setup>

import icons from "@/qt_icons.js"
const qt_icons = icons.qt_icons

import MainLayout from "@/Layouts/MainLayout.vue";
import JetDropdown from '@/Jetstream/Dropdown.vue'
import JetDropdownLink from '@/Jetstream/DropdownLink.vue'
import JetSectionBorder from '@/Jetstream/SectionBorder.vue';
import MultilingualQuestionnaire from "./MultiLanguage/MultiLanguage.vue";
import {
    AddEditQuestionModal,
    CopyQuestionModal,
    CopyQuestionModal2,
    DeleteQuestionModal,
    DeleteQuestionModal2,
    SaveAsTemplateModal,
    SaveAsTemplateModal2,
    SelectTemplateModal,
    SkipLogicModal,
    MoveQuestionModal,
    MoveQuestionModal2,
    MultipleChoiceList,
    ImageSelectionList,
    MatrixChoiceList,
    SingleTextboxList,
    MultipleTextboxList,
    ConstantSumList,
    SliderList,
    OpenEndedList,
    MatrixMultiList,
    MemoryTestComparisonList,
    MemoryTestDescList,
    RankingList,
    StarRatingList,
    SegmentationList,
    PairwiseComparisonList,
    BestWorstScaleList,
    BarChartList,
    DeleteLogic
} from "../Questions";
import { provide, ref, onMounted, onUpdated, onUnmounted, watch, computed, nextTick, defineComponent } from "vue";
import helpers from "@/helpers.js";
import { useForm, usePage, router } from "@inertiajs/vue3";
import axios from 'axios';
import { useQuestionnaireStore } from "../../stores/questionnaireStore.js";
import ButtonIconsVue from "../../S4/ButtonIcons.vue";
import Setting from "../Questions/Setting.vue";
import GalleryVue from "./Gallery/Gallery.vue";
import Tooltip from "@/S4/Tooltip.vue";
import moment from "moment";
import LanguageJson from "@/../json/language.json";
import SortAndSearch from "@/S4/SortAndSearch.vue";
import { faSpinner } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const $MultilingualQuestionnaire = ref(null);

const faIcon = {
    spinner: faSpinner,
};

const retranslateLoadingQuestionId = ref(null);


// window.addEventListener("scroll", function(event) {  
//     console.log(event);
// }, true);


// function debounce(func, timeout = 300){
//   let timer;
//   return (...args) => {
//     clearTimeout(timer);
//     timer = setTimeout(() => { func.apply(this, args); }, timeout);
//   };
// }

// window.addEventListener("mousewheel", e => {
//   document.querySelector(".shown").classList.remove("hidden");
// });

// window.addEventListener("mousewheel", debounce(e => {
//   document.querySelector(".shown").classList.add("hidden");
// }));



window.addEventListener("scroll", function (event) {
    const el = event.target;
    clearTimeout(el._scrolling);

    el.classList.add("is-scrolling");

    el._scrolling = setTimeout(() => {
        el.classList.remove("is-scrolling");
    }, 2000);
}, true);



const { assign, random_str, VideoWithHeaders } = helpers();
provide(
    "add_edit_form",
    useForm(
        {
            question_type_id: 1,
            question: "",
            setup: {},
            choices: [],
            rows: [],
            columns: [],
            items: [],
            item1: [],
            item2: [],
            values: [],
        },
        {
            resetOnSuccess: true,
        }
    )
);
const form = useForm({}, {
    resetOnSuccess: true,
})
const { toast } = helpers()

const page = usePage();
const LanguageList = LanguageJson.languages || [];

// Sort option for Languages dropdown
const sortOption = ref('name-asc');

// Split screen mode for comparing original and translated languages
// Initialize from URL params so it persists across reloads
const getInitialSplitScreen = () => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('split') === '1';
};
const isSplitScreen = ref(getInitialSplitScreen());

// Use a reactive ref for current language to avoid page reloads
// Initialize from URL params or page props
const getInitialLanguageCode = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const langc = urlParams.get('langc');
    if (langc) return langc;
    return page.props?.language?.code || ' ';
};
const currentLanguageCode = ref(getInitialLanguageCode());
const currentLanguageInfo = computed(() => {
    const code = currentLanguageCode.value;
    if (code === ' ' || !code || code.trim() === '') {
        return { EnglishName: 'Original', Code: ' ', NativeName: '' };
    }
    return LanguageList.find(item => item.Code === code) || { EnglishName: code, Code: code, NativeName: code };
});
// Treat empty string as the "Original" default displayed label in the UI
const originalCode = ' ';
const currentLanguageLabel = computed(() => {
    const code = currentLanguageInfo.value.Code;
    if (code === ' ' || !code || code.trim() === '') {
        return 'Original';
    }
    return currentLanguageInfo.value.EnglishName;
});
const appliedLanguages = computed(() => {
    const langObj = page.props?.questionnaire?.setup?.language || {};
    return Object.keys(langObj)
        .filter(code => {
            // Skip empty string (Original language) - it's shown separately
            return code !== ' ' && code && code.trim() !== '';
        })
        .map((code, index) => {
            const info = LanguageList.find(item => item.Code === code) || { EnglishName: code, Code: code, NativeName: code };
            return {
                ...info,
                order: index // original insertion order
            };
        });
    // Languages are now ordered by when they were first added as translations
});

// Applied languages sorted based on sortOption (no search)
const sortedAppliedLanguages = computed(() => {
    const list = [...appliedLanguages.value];

    switch (sortOption.value) {
        case 'name-asc':
            list.sort((a, b) => {
                return (a.EnglishName || a.Code).localeCompare(b.EnglishName || b.Code);
            });
            break;
        case 'name-desc':
            list.sort((a, b) => {
                return (b.EnglishName || b.Code).localeCompare(a.EnglishName || a.Code);
            });
            break;
        case 'date-asc':
            // Oldest first (lower order number = older)
            list.sort((a, b) => a.order - b.order);
            break;
        case 'date-desc':
            // Newest first (higher order number = newer)
            list.sort((a, b) => b.order - a.order);
            break;
    }

    return list;
});

// Check if a language code is the currently active one
const isCurrentLanguage = (code) => {
    return currentLanguageCode.value === code;
};

// Get the langc parameter for preview URLs if a translated language is active
const previewLangcParam = computed(() => {
    const lang = currentLanguageCode.value;
    // If it's the original language (empty string or space), return empty string
    if (!lang || lang === ' ' || lang.trim() === '') {
        return '';
    }
    // Otherwise, return the langc parameter
    return '&langc=' + lang;
});

// Listen for browser back/forward buttons to update language
let handlePopState = null;
let handleLanguageChanged = null;
let handleRemoteChange = null;

// Format English name with country code in parentheses
const formatEnglishName = (code, englishName) => {
    if (code === ' ' || !code || code.trim() === '') {
        return 'Original';
    }
    if (englishName !== 'English') return englishName;

    const countryMap = {
        'en-GB': 'UK',
        'en': 'US',
        'en-US': 'US',
        'en-AU': 'AU',
        'en-CA': 'CA'
    };

    const country = countryMap[code];
    return country ? `English (${country})` : englishName;
};

const switchLanguage = (code) => {
    try {
        // If switching back to Original while in split screen, turn off split screen
        if (isOriginalLanguage(code) && isSplitScreen.value) {
            isSplitScreen.value = false;
        }

        // Update the reactive ref immediately (no page reload)
        currentLanguageCode.value = code;

        // Update the URL without reloading the page
        const base = route(page.props.pageSTR, {
            questionnaire_code: page.props.questionnaire.code
        });

        // Build the new URL with langc parameter
        const url = new URL(base, window.location.origin);
        if (code && code !== ' ' && code.trim() !== '') {
            url.searchParams.set('langc', code);
        } else {
            // Remove langc parameter for original language
            url.searchParams.delete('langc');
        }

        // Preserve split screen state in the URL so it survives reloads
        if (isSplitScreen.value) {
            url.searchParams.set('split', '1');
        } else {
            url.searchParams.delete('split');
        }

        // Update URL without page reload
        window.history.pushState({}, '', url.pathname + url.search);

        // Dispatch custom event to notify other components (like SideMenuLayout)
        window.dispatchEvent(new CustomEvent('language-changed', {
            detail: { languageCode: code }
        }));
    } catch (e) {
        console.error('Failed to switch language', e);
    }
};

const getQuestionContent = (question, lang = null) => {
    lang = lang || currentLanguageCode.value;
    if (lang === ' ' || !lang || lang.trim() === '') return question?.question || '';
    const translated = question?.setup?.language?.[lang]?.question;
    return translated && translated.length ? translated : question?.question || '';
};

const getChartTitle = (question, lang = null) => {
    lang = lang || currentLanguageCode.value;
    if (lang === ' ' || !lang || lang.trim() === '') return question?.setup?.chartTitle || '';
    const translated = question?.setup?.language?.[lang]?.chartTitle;
    return translated && translated.length ? translated : question?.setup?.chartTitle || '';
};

// Helper function to check if language is original (empty string)
const isOriginalLanguage = (lang) => {
    return lang === ' ' || !lang || lang.trim() === '';
};

// Computed property to check if split screen toggle should be shown
const showSplitScreenToggle = computed(() => {
    return !isOriginalLanguage(currentLanguageCode.value);
});

// When a translated language is removed, reset to Original if it was active
watch(appliedLanguages, (newLanguages) => {
    const code = currentLanguageCode.value;
    if (isOriginalLanguage(code)) return;

    const stillExists = newLanguages.some(lang => lang.Code === code);
    if (!stillExists) {
        switchLanguage(originalCode);
    }
});

// Toggle split screen mode
const toggleSplitScreen = () => {
    isSplitScreen.value = !isSplitScreen.value;

    try {
        // Update the URL to persist split screen state
        const base = route(page.props.pageSTR, {
            questionnaire_code: page.props.questionnaire.code
        });

        const url = new URL(base, window.location.origin);

        // Preserve current language in the URL
        const langCode = currentLanguageCode.value;
        if (langCode && langCode !== ' ' && langCode.trim() !== '') {
            url.searchParams.set('langc', langCode);
        } else {
            url.searchParams.delete('langc');
        }

        // Set or remove split flag
        if (isSplitScreen.value) {
            url.searchParams.set('split', '1');
        } else {
            url.searchParams.delete('split');
        }

        window.history.pushState({}, '', url.pathname + url.search);
    } catch (e) {
        console.error('Failed to toggle split screen', e);
    }
};

// Translation helper functions
const getTranslatedChoice = (choice, lang = null) => {
    lang = lang || currentLanguageCode.value;
    if (isOriginalLanguage(lang)) return choice?.name || '';
    return choice?.language?.[lang]?.name || choice?.name || '';
};

const getTranslatedItem = (item, lang = null) => {
    lang = lang || currentLanguageCode.value;
    if (isOriginalLanguage(lang)) return item?.name || '';
    return item?.language?.[lang]?.name || item?.name || '';
};

const getTranslatedItemStatementA = (item, lang = null) => {
    lang = lang || currentLanguageCode.value;
    if (isOriginalLanguage(lang)) return item?.statementA || '';
    return item?.language?.[lang]?.statementA || item?.statementA || '';
};

const getTranslatedItemStatementB = (item, lang = null) => {
    lang = lang || currentLanguageCode.value;
    if (isOriginalLanguage(lang)) return item?.statementB || '';
    return item?.language?.[lang]?.statementB || item?.statementB || '';
};

const getTranslatedValue = (value, lang = null) => {
    lang = lang || currentLanguageCode.value;
    if (isOriginalLanguage(lang)) return value?.name || '';
    return value?.language?.[lang]?.name || value?.name || '';
};

const getTranslatedSetupLabel = (setup, labelKey, lang = null) => {
    lang = lang || currentLanguageCode.value;
    if (isOriginalLanguage(lang)) {
        if (labelKey === 'statementA') return setup?.labels?.statementA || '';
        if (labelKey === 'statementB') return setup?.labels?.statementB || '';
        return '';
    }
    const translated = setup?.language?.[lang]?.labels?.[labelKey];
    if (translated) return translated;
    if (labelKey === 'statementA') return setup?.labels?.statementA || '';
    if (labelKey === 'statementB') return setup?.labels?.statementB || '';
    return '';
};

const getTranslatedSetupHeader = (setup, lang = null) => {
    lang = lang || currentLanguageCode.value;
    if (isOriginalLanguage(lang)) return setup?.header || '';
    return setup?.language?.[lang]?.header || setup?.header || '';
};

const getTranslatedSetupFooter = (setup, lang = null) => {
    lang = lang || currentLanguageCode.value;
    if (isOriginalLanguage(lang)) return setup?.footer || '';
    return setup?.language?.[lang]?.footer || setup?.footer || '';
};

const getTranslatedBestWorstLabel = (setup, labelKey, lang = null) => {
    lang = lang || currentLanguageCode.value;
    if (isOriginalLanguage(lang)) {
        if (labelKey === 'labelA') return setup?.bestWostScale?.labelA || '';
        if (labelKey === 'labelB') return setup?.bestWostScale?.labelB || '';
        return '';
    }
    const translated = setup?.language?.[lang]?.labels?.[labelKey === 'labelA' ? 'statementA' : 'statementB'];
    if (translated) return translated;
    if (labelKey === 'labelA') return setup?.bestWostScale?.labelA || '';
    if (labelKey === 'labelB') return setup?.bestWostScale?.labelB || '';
    return '';
};

// Get RTL value from questionnaire setup - check language-specific RTL or language.json
const getRTL = (setup, lang = null) => {
    lang = lang || currentLanguageCode.value;
    const questionnaireSetup = setup || page.props?.questionnaire?.setup || {};

    // If translation language is active, check language-specific RTL or language.json
    if (lang && !isOriginalLanguage(lang)) {
        // First check if RTL is stored in language-specific translation data
        const langRTL = questionnaireSetup?.language?.[lang]?.rtl;
        if (langRTL === 1) return true;
        if (langRTL === 0) return false;

        // If not in translation data, check language.json
        const languageInfo = LanguageList.find(item => item.Code === lang);
        if (languageInfo?.RTL === 1) return true;
    }

    // For original language or fallback, check global RTL setting
    return questionnaireSetup?.rtl === 1;
};

const isRTL = computed(() => {
    return getRTL(page.props?.questionnaire?.setup);
});

// Retranslate a single question using AI
const retranslateQuestion = async (question) => {
    if (!question) return;

    const questionLoadingKey = question.id ?? question.order ?? null;
    if (questionLoadingKey === null) return;

    // If another re-translation is already in progress for this question, do nothing
    if (retranslateLoadingQuestionId.value === questionLoadingKey) {
        return;
    }

    retranslateLoadingQuestionId.value = questionLoadingKey;

    let loadingToastId = null;

    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            sessionId: 'debug-session',
            runId: 'initial',
            hypothesisId: 'H1',
            location: 'Questions/Index.vue:retranslateQuestion:entry',
            message: 'Retranslate question invoked',
            data: {
                questionOrder: question?.order ?? null
            },
            timestamp: Date.now()
        })
    }).catch(() => {});
    // #endregion

    try {
        const urlParams = new URLSearchParams(window.location.search);
        const langc = urlParams.get('langc');

        if (!langc || langc === ' ' || langc.trim() === '') {
            toast.error('Please select a translation language first');
            return;
        }

        const variableStart = parseInt(page.props.questionnaire.setup?.variable_start || 1);
        const questionCode = `Q${parseInt(question.order) + variableStart - 1}`;

        const languageInfo = LanguageList.find(item => item.Code === langc);
        const languageName = languageInfo?.EnglishName || langc;
        const rtl = languageInfo?.RTL || 0;

        // #region agent log
        fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                sessionId: 'debug-session',
                runId: 'initial',
                hypothesisId: 'H2',
                location: 'Questions/Index.vue:retranslateQuestion:before-toast',
                message: 'Before showing loading toast',
                data: {
                    langc,
                    questionCode,
                },
                timestamp: Date.now()
            })
        }).catch(() => {});
        // #endregion

        loadingToastId = toast.info(`Re-translating question ${questionCode}...`, {
            timeout: false,
            closeOnClick: false,
            hideProgressBar: true
        });

        const singleQuestionUrl = (typeof route === 'function' && route().has && route().has('ai.translate.question.single'))
            ? route('ai.translate.question.single', { code: page.props.questionnaire.code })
            : `/ai-translate-single-question/${page.props.questionnaire.code}`;

        // #region agent log
        fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                sessionId: 'debug-session',
                runId: 'initial',
                hypothesisId: 'H3',
                location: 'Questions/Index.vue:retranslateQuestion:before-request',
                message: 'Before axios translation request',
                data: {
                    url: singleQuestionUrl,
                    langc,
                    questionCode,
                },
                timestamp: Date.now()
            })
        }).catch(() => {});
        // #endregion

        const response = await axios.post(
            singleQuestionUrl,
            {
                language: langc,
                languageName: languageName,
                questionKey: questionCode,
                includeSystemMessages: false,
                RTL: rtl
            },
            {
                timeout: 300000,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        );

        // #region agent log
        fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                sessionId: 'debug-session',
                runId: 'initial',
                hypothesisId: 'H4',
                location: 'Questions/Index.vue:retranslateQuestion:after-response',
                message: 'After axios translation response',
                data: {
                    success: !!response?.data?.success,
                },
                timestamp: Date.now()
            })
        }).catch(() => {});
        // #endregion

        // Check response
        if (response.data.success) {
            // #region agent log
            fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    sessionId: 'debug-session',
                    runId: 'post-fix',
                    hypothesisId: 'H5',
                    location: 'Questions/Index.vue:retranslateQuestion:before-router-reload',
                    message: 'Before router.reload after successful translation',
                    data: {
                        questionCode,
                    },
                    timestamp: Date.now()
                })
            }).catch(() => {});
            // #endregion

            // Refresh questions via Inertia; complete the toast only after reload finishes
            router.reload({
                only: ['questions'],
                preserveScroll: true,
                onFinish: () => {
                    // #region agent log
                    fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            sessionId: 'debug-session',
                            runId: 'post-fix',
                            hypothesisId: 'H6',
                            location: 'Questions/Index.vue:retranslateQuestion:after-router-reload',
                            message: 'router.reload finished after successful translation',
                            data: {
                                questionCode,
                            },
                            timestamp: Date.now()
                        })
                    }).catch(() => {});
                    // #endregion

                    toast.dismiss(loadingToastId);
                    toast.success(`Question ${questionCode} re-translated successfully`, { timeout: 3000 });
                    retranslateLoadingQuestionId.value = null;
                },
            });
        } else {
            toast.dismiss(loadingToastId);
            toast.error(response.data.error || 'Failed to re-translate question', { timeout: 5000 });
            retranslateLoadingQuestionId.value = null;
        }
    } catch (error) {
        console.error('Error retranslating question:', error);
        if (loadingToastId) {
            toast.dismiss(loadingToastId);
        }
        toast.error(error.response?.data?.error || 'An error occurred while re-translating the question', { timeout: 5000 });
        retranslateLoadingQuestionId.value = null;
    }
};

// Provide translation utilities to child components (default: currentLanguageCode)
provide('currentLanguageCode', currentLanguageCode);
provide('getTranslatedChoice', getTranslatedChoice);
provide('getTranslatedItem', getTranslatedItem);
provide('getTranslatedItemStatementA', getTranslatedItemStatementA);
provide('getTranslatedItemStatementB', getTranslatedItemStatementB);
provide('getTranslatedValue', getTranslatedValue);
provide('getTranslatedSetupLabel', getTranslatedSetupLabel);
provide('getTranslatedSetupHeader', getTranslatedSetupHeader);
provide('getTranslatedSetupFooter', getTranslatedSetupFooter);
provide('getTranslatedBestWorstLabel', getTranslatedBestWorstLabel);
provide('isRTL', isRTL);

// Scope component to force ORIGINAL language for its subtree (used for left pane in split view)
const OriginalLanguageScope = defineComponent({
    name: 'OriginalLanguageScope',
    setup(_, { slots }) {
        const lang = ' ';
        const langRef = ref(lang);
        const questionnaireSetup = page.props?.questionnaire?.setup || {};

        // Override language + helpers for everything inside this scope
        provide('currentLanguageCode', langRef);

        provide('getTranslatedChoice', (choice) => {
            if (isOriginalLanguage(lang)) return choice?.name || '';
            return choice?.language?.[lang]?.name || choice?.name || '';
        });

        provide('getTranslatedItem', (item) => {
            if (isOriginalLanguage(lang)) return item?.name || '';
            return item?.language?.[lang]?.name || item?.name || '';
        });

        provide('getTranslatedItemStatementA', (item) => {
            if (isOriginalLanguage(lang)) return item?.statementA || '';
            return item?.language?.[lang]?.statementA || item?.statementA || '';
        });

        provide('getTranslatedItemStatementB', (item) => {
            if (isOriginalLanguage(lang)) return item?.statementB || '';
            return item?.language?.[lang]?.statementB || item?.statementB || '';
        });

        provide('getTranslatedValue', (value) => {
            if (isOriginalLanguage(lang)) return value?.name || '';
            return value?.language?.[lang]?.name || value?.name || '';
        });

        provide('getTranslatedSetupLabel', (setup, labelKey) => {
            const s = setup || {};
            if (isOriginalLanguage(lang)) {
                if (labelKey === 'statementA') return s?.labels?.statementA || '';
                if (labelKey === 'statementB') return s?.labels?.statementB || '';
                return '';
            }
            const translated = s?.language?.[lang]?.labels?.[labelKey];
            if (translated) return translated;
            if (labelKey === 'statementA') return s?.labels?.statementA || '';
            if (labelKey === 'statementB') return s?.labels?.statementB || '';
            return '';
        });

        provide('getTranslatedSetupHeader', (setup) => {
            const s = setup || {};
            if (isOriginalLanguage(lang)) return s?.header || '';
            return s?.language?.[lang]?.header || s?.header || '';
        });

        provide('getTranslatedSetupFooter', (setup) => {
            const s = setup || {};
            if (isOriginalLanguage(lang)) return s?.footer || '';
            return s?.language?.[lang]?.footer || s?.footer || '';
        });

        provide('getTranslatedBestWorstLabel', (setup, labelKey) => {
            const s = setup || {};
            if (isOriginalLanguage(lang)) {
                if (labelKey === 'labelA') return s?.bestWostScale?.labelA || '';
                if (labelKey === 'labelB') return s?.bestWostScale?.labelB || '';
                return '';
            }
            const translated = s?.language?.[lang]?.labels?.[labelKey === 'labelA' ? 'statementA' : 'statementB'];
            if (translated) return translated;
            if (labelKey === 'labelA') return s?.bestWostScale?.labelA || '';
            if (labelKey === 'labelB') return s?.bestWostScale?.labelB || '';
            return '';
        });

        const isRTLOriginal = computed(() => getRTL(questionnaireSetup, lang));
        provide('isRTL', isRTLOriginal);

        // Return Fragment to avoid creating a wrapper element that might block interactions
        return () => {
            const defaultSlot = slots.default?.();
            if (!defaultSlot) return null;
            // Return as array (Fragment) to avoid wrapper element that could block pointer events
            // Vue 3 treats arrays returned from render functions as Fragments
            return Array.isArray(defaultSlot) ? defaultSlot : [defaultSlot];
        };
    }
});

//provide("QuestionnaireSetup", props.questionnaire.setup);

const showSetting = ref(false);
const showGallery = ref(false);
const pipingData = ref([]);

// Remote change tracking for this questionnaire
const hasRemoteChanges = ref(false);


const props = defineProps([
    "questions",
    "question_templates",
    "questionnaire",
    "question_types",
    "project",
    "projects",
    "questionnaires",
    "gallery",
    "parts",
    "themes",
    "pageSTR",
    "app_url",
    "engine_url",
    "global_setup",
]);
let listShowHide = ref([]);
const modals = ref({
    question: {
        add_edit: false,
        copy: false,
        delete: false,
        skip_logic: false,
        move_question: false,
        save_template: false,
        select_template: false,
        method: "add",
        data: null,
    },
});

onMounted(() => {
    prepareList();
    // console.log("Questions>> ", props.questions)
    hasPiping();
    reQuestion();
    // Set up browser back/forward button handler for language switching
    handlePopState = () => {
        const urlParams = new URLSearchParams(window.location.search);
        const langc = urlParams.get('langc');
        currentLanguageCode.value = langc || page.props?.language?.code || ' ';
    };

    window.addEventListener('popstate', handlePopState);

    // Listen for language-changed events (e.g., after AI translation or manual upload)
    // This ensures the display updates when translations are loaded
    handleLanguageChanged = (event) => {
        const newLang = event.detail.languageCode;
        // Update current language code to trigger reactive updates
        currentLanguageCode.value = newLang;
    };

    window.addEventListener('language-changed', handleLanguageChanged);

    // Listen for remote questionnaire changes coming from other users
    const handleRemoteUpdated = (event) => {
        const detail = event?.detail || {};
        const code = detail.questionnaire_code || detail.code || null;

        // Ignore updates for a different questionnaire
        if (code && code !== props.questionnaire.code) {
            return;
        }

        // Auto-sync without page reload
        const updateType = detail.update_type || '';
        const userName = detail.user?.name || 'Another user';
        const questionId = detail.data?.question_id;
        const questionOrder = detail.data?.question_order;

        // Generate notification message based on update type
        let notificationMessage = '';
        const variableStart = parseInt(props.questionnaire.setup?.variable_start || 1);
        
        if (updateType === 'question_created') {
            const questionNum = questionOrder ? `Q${parseInt(questionOrder) + variableStart - 1}` : '';
            notificationMessage = questionNum 
                ? `${userName} added question ${questionNum}`
                : `${userName} added a question`;
        } else if (updateType === 'question_updated') {
            const questionNum = questionOrder ? `Q${parseInt(questionOrder) + variableStart - 1}` : (questionId ? `ID ${questionId}` : '');
            notificationMessage = questionNum 
                ? `${userName} updated question ${questionNum}`
                : `${userName} updated a question`;
        } else if (updateType === 'question_deleted') {
            const questionNum = questionOrder ? `Q${parseInt(questionOrder) + variableStart - 1}` : (questionId ? `ID ${questionId}` : '');
            notificationMessage = questionNum 
                ? `${userName} deleted question ${questionNum}`
                : `${userName} deleted a question`;
        } else if (updateType === 'question_restored') {
            const questionNum = questionOrder ? `Q${parseInt(questionOrder) + variableStart - 1}` : (questionId ? `ID ${questionId}` : '');
            notificationMessage = questionNum 
                ? `${userName} restored question ${questionNum}`
                : `${userName} restored a question`;
        } else {
            notificationMessage = `${userName} updated questionnaire`;
        }

        // Show notification
        if (notificationMessage) {
            toast.info(notificationMessage, { timeout: 4000 });
        }

        // Auto-sync by reloading only questions and questionnaire data
        router.reload({
            only: ['questions', 'questionnaire'],
            preserveScroll: updateType !== 'question_created', // Don't preserve scroll if question was added
            onSuccess: async () => {
                // Data has been synced
                hasRemoteChanges.value = false;
                
                // If a question was added, scroll to bottom after DOM updates
                if (updateType === 'question_created') {
                    await nextTick();
                    scrollToBottom();
                }
                
                // If a question was updated and its modal is open, update the modal data
                if (updateType === 'question_updated' && questionId) {
                    await nextTick();
                    
                    // Find the updated question in the questions array
                    const updatedQuestionIndex = props.questions.findIndex(q => q.id === questionId);
                    if (updatedQuestionIndex !== -1) {
                        const updatedQuestion = props.questions[updatedQuestionIndex];
                        
                        // Check if edit modal is open for this question
                        if (modals.value.question.add_edit && 
                            modals.value.question.data?.id === questionId) {
                            modals.value.question.data = assign(updatedQuestion);
                            editedIdx.value = updatedQuestionIndex;
                        }
                        
                        // Check if skip logic modal is open for this question
                        if (modals.value.question.skip_logic && 
                            modals.value.question.data?.id === questionId) {
                            modals.value.question.data = assign(updatedQuestion);
                        }
                    }
                }
                
                // If questionnaire was updated and settings panel is open, refresh the store and form
                if (updateType !== 'question_created' && 
                    updateType !== 'question_updated' && 
                    updateType !== 'question_deleted' && 
                    updateType !== 'question_restored') {
                    // This is a general questionnaire update
                    await nextTick();
                    
                    // Refresh the questionnaire store
                    const { initializeFromPageData } = useQuestionnaireStore();
                    initializeFromPageData(page.props);
                    
                    // Dispatch event to notify Setting and Advanced components to refresh their forms
                    window.dispatchEvent(new CustomEvent('questionnaire-data-updated', {
                        detail: {
                            questionnaire: page.props.questionnaire
                        }
                    }));
                }
            },
            onError: () => {
                // If reload fails, show error
                toast.error('Failed to sync changes. Please refresh the page.', { timeout: 5000 });
            }
        });
    };

    window.addEventListener('questionnaire-remote-updated', handleRemoteUpdated);

    // Store handler so we can remove it on unmount
    handleRemoteChange = handleRemoteUpdated;
});

onUnmounted(() => {
    if (handlePopState) {
        window.removeEventListener('popstate', handlePopState);
    }
    if (handleLanguageChanged) {
        window.removeEventListener('language-changed', handleLanguageChanged);
    }
    if (handleRemoteChange) {
        window.removeEventListener('questionnaire-remote-updated', handleRemoteChange);
    }
});

onUpdated(() => {
    //added on update to update question list after adding question
    prepareList();
    hasPiping();
    reQuestion();
    // console.log("UPDATE Questions>> ", props.questions)
});

const hasPiping = () => {

    // Iterate through the questions array
    props.questions.forEach(question => {
        if (question.setup && question.setup.piping != null) {
            pipingData.value.push(question.setup.piping);
        }
    });

    //   console.log("Has Piping key with non-null values", pipingData.value);

};

const reQuestion = () => {
    props.questions.forEach(question => {
        if (pipingData.value && Array.isArray(pipingData.value)) {
            // Check if the question string contains an order and replace it with the corresponding pipingData
            pipingData.value.forEach(piping => {
                if (question.question && question.question.includes(piping.order)) {
                    // Replace the order in question with the corresponding question in pipingData
                    question.question = question.question.replace(piping.order, piping.question);
                }
            });
        }
    });
};



function scrollToBottom() {
    document.querySelector('#mainLayout').dispatchEvent(new CustomEvent("PageScrollTo", {
        detail: { target: document.querySelector('#mainLayout') }
    }))
}


const prepareList = () => {
    let tempStore = [];
    props.questions.map((item) => {
        let renew = { visible: true };
        tempStore.push(renew);
    });
    listShowHide.value = tempStore;
};

const showModal = (table, modal, method, index = -1) => {

    modals.value[table].method = method;
    if (index >= 0) {
        modals.value[table].data = assign(props.questions[index]);
        editedIdx.value = index
    } else {
        modals.value[table].data = null;
    }
    modals.value[table][modal] = true;
};
const closeModal = (table, modal) => {
    // console.log(modal, table);
    if (modal === "select_template") modals.value[table].add_edit = false;
    modals.value[table][modal] = false;
    modals.value[table].data = null;
};

const filterAdvanceSkipLogic = (question) => {
    let logic = {};
    logic["enter"] = [];

    for (var eLogic in question.setup.advanceSkiplogic.enter) {
        let action_type = question.setup.advanceSkiplogic.enter[eLogic].action
        let logic_value = question.setup.advanceSkiplogic.enter[eLogic].value[0]
        let len = question.setup.advanceSkiplogic.enter[eLogic].value.length
        let push_value = question.setup.advanceSkiplogic.enter[eLogic]

        if (len) {
            if (action_type == "hide_choices") {
                for (var eChoice in question.choices) {
                    if (question.choices[eChoice]?.index) {
                        if (question.choices[eChoice].index == logic_value) {
                            logic.enter.push(push_value);
                            break;
                        }
                    } else {
                        if (parseInt(eChoice) + 1 == parseInt(logic_value)) {
                            logic.enter.push(push_value);
                            break;
                        }
                    }
                }
            } else if (action_type == "hide_items") {
                for (var eItems in question.items) {
                    if (question.items[eItems]?.index) {
                        if (question.items[eItems].index == logic_value) {
                            logic.enter.push(push_value);
                            break;
                        }
                    } else {
                        if (parseInt(eItems) + 1 == parseInt(logic_value)) {
                            logic.enter.push(push_value);
                            break;
                        }
                    }
                }
            } else if (action_type == "hide_rows") {
                for (var eRows in question.rows) {

                    // using array index
                    if (parseInt(eRows) + 1 == parseInt(logic_value)) {
                        logic.enter.push(push_value);
                        break;
                    }

                    // using original index
                    // if (question.rows[eRows]?.index) {
                    //     if(question.rows[eRows].index == logic_value) {
                    //         logic.enter.push(push_value);
                    //         break;
                    //     }
                    // } else {
                    //     if(parseInt(eRows)+1 == parseInt(logic_value)) {
                    //         logic.enter.push(push_value);
                    //         break;
                    //     }
                    // }
                }
            } else if (action_type == "hide_columns") {
                for (var eCols in question.columns) {

                    // using array index
                    if (parseInt(eCols) + 1 == parseInt(logic_value)) {
                        logic.enter.push(push_value);
                        break;
                    }

                    // using original index
                    // if (question.columns[eCols]?.index) {
                    //     if(question.columns[eCols].index == logic_value) {
                    //         logic.enter.push(push_value);
                    //         break;
                    //     }
                    // } else {
                    //     if(parseInt(eCols)+1 == parseInt(logic_value)) {
                    //         logic.enter.push(push_value);
                    //         break;
                    //     }
                    // }
                }
            }
        } else {
            logic.enter.push(push_value);
        }
    }
    logic["leave"] = question.setup.advanceSkiplogic.leave;
    return logic;
}

const editedIdx = ref(null);
const badge = (question) => {
    let cLogic = filterAdvanceSkipLogic(question)
    return (cLogic.enter.length + cLogic.leave.length);
};

const showQuestion = (index) => {
    if (listShowHide.value[index].visible == true) {
        listShowHide.value[index].visible = false;
    } else {
        listShowHide.value[index].visible = true;
    }
    // console.log(listShowHide.value);
};

const showSettingHandler = () => {
    if (showSetting.value == false) {
        showSetting.value = true;
    } else {
        showSetting.value = false;
    }
};
const showGalleryHandler = () => {
    if (showGallery.value == false) {
        showGallery.value = true;
    } else {
        showGallery.value = false;
    }
};
// const diffenceTime = (dateFromDatabase) => {
//     const newdate = new Date(dateFromDatabase);
//     const timestamp = moment(
//         newdate,
//         "ddd MMM DD YYYY HH:mm:ss GMT Z"
//     ).fromNow();
//     // console.log(timestamp);
//     return timestamp;
// };
const differenceTime = (dateFromDatabase) => {
    const newDate = new Date(dateFromDatabase);
    const exactTime = moment(newDate).format("dddd, MMMM Do YYYY, h:mm:ss A");
    const relativeTime = moment(newDate).fromNow();
    return `on ${exactTime} (${relativeTime})`;
};


const offsetScroll = ref(0)

function handleScroll(event) {
    offsetScroll.value = parseInt(event.currentTarget.scrollTop)
}

// function toggleSlideover() {

//     document.getElementById('slideover-container').classList.toggle('invisible');
//     document.getElementById('slideover-bg').classList.toggle('opacity-0');
//     document.getElementById('slideover-bg').classList.toggle('opacity-50');
//     document.getElementById('slideover').classList.toggle('translate-x-[0%]');
// }
// function toggleSlideoverSettings() {
//     document.getElementById('slideover-container-settings').classList.toggle('invisible');
//     document.getElementById('slideover-bg-settings').classList.toggle('opacity-0');
//     document.getElementById('slideover-bg-settings').classList.toggle('opacity-50');
//     document.getElementById('slideover-settings').classList.toggle('translate-x-[0%]');
// }

// watch(() =>
//     props.questions,
//     (change) => {
//         if (change) {
//             console.log("testetsetse>>>>>>", props.questions)
//         }

//     },
//     { deep: true }
// )
const parts = props.parts;

const sortedQuestionnaires = computed(() => {
    return [...parts].sort((a, b) => a.name.localeCompare(b.name));
});
const showDeleteQuestion = ref(false);
const questionToDeleteIdx = ref(null);
const deleteQuestion = () => {
    if (questionToDeleteIdx.value === null) return;
    const question = props.questions[questionToDeleteIdx.value];
    if (!question?.id) return;

    form.delete(route('questions.destroy', [
        props.questionnaire.code,
        question.id
    ]), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Question deleted`, { timeout: 2000 });
            showDeleteQuestion.value = false;
            questionToDeleteIdx.value = null;
        },
        onError: () => {
            // handle error
        },
        onFinish: () => {
            showDeleteQuestion.value = false;
            questionToDeleteIdx.value = null;
        }
    });
};


</script>

<template>
    <main-layout title="Questions">

        <template #content>


            <!-- <div @click="scrollToTop()"
                class="transition ease-in-out absolute bottom-0 right-0 z-[50] rounded-full bg-sky-700 text-white p-3 drop-shadow-2xl m-8 cursor-pointer hover:bg-sky-800"
                :class="{ 'opacity-1': offsetScroll <= 300, 'opacity-1': offsetScroll > 300 }">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                </svg>
            </div> -->
            <!-- <div class="flex flex-cols-1 place-content-between mr-1 "> -->
            <div
                class="flex flex-cols-1 place-content-between absolute top-[70px] bg-[var(--bgcolor)] left-[0px] pl-[150px] pr-[24px] pt-[24px] w-full z-20">

                <jet-dropdown align="parts" width="" v-if="$page.props.jetstream.hasTeamFeatures">
                    <template #trigger>
                        <div class="group flex flex-col justify-start items-start cursor-pointer max-w-4xl pl-[20px]">
                            <div class="text-2xl text-[var(--txtcolor)] font-bold max-w-lg truncate">
                                {{ $page.props.questionnaire.name }}</div>
                            <div class="flex">
                                <div
                                    class="text-sm text-[var(--txtcolor)] opacity-[50%] transition pr-4 place-self-center font-bold truncate">
                                    {{ $page.props.questionnaire.details }}
                                </div>
                                <div class="grid items-center text-[var(--txtcolor)] opacity-[50%] transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="gray" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #content>
                        <div class="max-w-2xl max-h-[80vh] overflow-hidden flex flex-col">
                            <div
                                class="block px-[24px] py-2 text-xs text-[var(--txtcolor2)] font-bold w-full overflow-hidden truncate">
                                {{ $page.props.project.name }}
                            </div>
                            <div class="grow-[1] overflow-auto flex flex-col">
                                <template v-for="(questionnaire, index) in sortedQuestionnaires" :key="index">
                                    <a class="p-[6px_24px] w-full relative text-md hover:bg-[var(--bgcolor)]"
                                        v-bind:class="[questionnaire.code === $page.props.questionnaire.code ? 'bg-[var(--mcolor)] hover:!bg-[var(--mcolor2)] !font-bold text-white' : '']"
                                        :href="route($page.props.pageSTR, { 'questionnaire_code': questionnaire.code, 'language_id': $page.props.language.id })">

                                        <div class="flex">
                                            <div class="max-w-80 truncate grow-[1]">{{ questionnaire.name }}</div>
                                            <svg v-if="questionnaire.code == $page.props.questionnaire.code"
                                                class="h-6 w-6 text-white" fill="none" stroke-linecap="round"
                                                stroke-linejoin="round" stroke-width="2" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>

                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>
                </jet-dropdown>


                <div class="h-9 flex flex-cols-1 gap-4 items-center">
                    <!-- <div @click="toggleSlideover"
                            class="cursor-pointer hover:opacity-[100%] transition flex text-sm bg-slate/0 text-[var(--txtcolor)] opacity-[60%] font-semibold py-2 rounded-lg">
                            Gallery
                        </div> -->
                    <!-- <div @click="toggleSlideoverSettings"
                            class="cursor-pointer hover:opacity-[100%] transition flex text-sm bg-slate/0 text-[var(--txtcolor)] opacity-[60%] font-semibold py-2 rounded-lg">
                            Settings
                        </div> -->
                    
                    <div class="opacity-[60%] ml-[0px] h-5 self-center"></div>

                    <JetDropdown v-if="$page.props.questions.length >= 1" :align="'right'" width="56">
                        <template #trigger="{ open }">
                            <div
                                class="cursor-pointer transition flex justify-center items-center text-sm font-semibold px-3 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" class="w-5 h-5 mr-2" style="stroke: var(--mcolor2);">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 016-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 01-3.827-5.802" />
                                </svg>
                                <span class="truncate max-w-[140px]">{{ currentLanguageLabel }}</span>
                                <span
                                    class="ml-2 text-[9px] font-medium px-1.5 py-[-1px] rounded-full flex items-center"
                                    style="background-color: rgba(209, 129, 11, 0.15); color: var(--mcolor2); border: 1px solid rgba(209, 129, 11, 0.3);">{{
                                        currentLanguageInfo.Code }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" 
                                    :class="['w-4 h-4 ml-2 text-gray-400 transition-transform duration-200', { 'rotate-180': open }]"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div>
                        </template>
                        <template #content>
                            <div class="w-56">
                                <div class="px-4 py-1 flex items-center justify-between">
                                    <div class="text-xs text-gray-400">Languages</div>
                                    <Tooltip preset="ManageLanguages">
                                        <template #svg>
                                            <button @click="$MultilingualQuestionnaire._fn.open()"
                                                class="p-1.5 transition-all duration-200 rounded focus:outline-none">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="w-5 h-5 text-gray-400 hover:text-gray-600" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </button>
                                        </template>
                                    </Tooltip>

                                </div>
                                
                                <div class="overflow-auto max-h-[50vh]">
                                    <div class="px-3 pb-2" @click.stop>
                                        <!-- Only show the sort dropdown (search hidden by zero width) -->
                                        <SortAndSearch
                                            v-model:sortValue="sortOption"
                                            :showSearch="false"
                                            :gap="'0'" />
                                    </div>
                                    <JetSectionBorder />
                                    <!-- Default (Original) - Always first -->
                                    <div class="px-3 py-2 text-sm flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors"
                                        :class="isCurrentLanguage(originalCode) ? 'border-l-4' : 'text-gray-700'"
                                        :style="isCurrentLanguage(originalCode) ? { backgroundColor: 'rgba(209, 129, 11, 0.1)', borderLeftColor: 'var(--mcolor2)' } : {}"
                                        @click="switchLanguage(originalCode)">
                                        <div class="flex flex-col flex-1 min-w-0">
                                            <span class="font-semibold text-sm"
                                                :style="isCurrentLanguage(originalCode) ? { color: 'var(--mcolor2)' } : {}">Original</span>
                                            <span class="text-xs text-gray-500 mt-0.5"></span>
                                        </div>
                                        <div class="flex items-center gap-2 ml-2 flex-shrink-0">
                                            <span
                                                class="text-[8px] font-medium px-1.5 py-[-1px] rounded-full flex items-center"
                                                style="background-color: rgba(209, 129, 11, 0.15); color: var(--mcolor2); border: 1px dashed rgba(209, 129, 11, 0.3);">Default</span>
                                        </div>
                                    </div>
                                    <!-- Applied Languages -->
                                    <template v-if="sortedAppliedLanguages.length">
                                        <div v-for="(lang, i) in sortedAppliedLanguages" :key="i"
                                            class="px-3 py-2 text-sm flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors"
                                            :class="isCurrentLanguage(lang.Code) ? 'border-l-4' : 'text-gray-700'"
                                            :style="isCurrentLanguage(lang.Code) ? { backgroundColor: 'rgba(209, 129, 11, 0.1)', borderLeftColor: 'var(--mcolor2)' } : {}"
                                            @click="switchLanguage(lang.Code)">
                                            <div class="flex flex-col flex-1 min-w-0">
                                                <span class="font-semibold text-sm"
                                                    :class="isCurrentLanguage(lang.Code) ? '' : ''"
                                                    :style="isCurrentLanguage(lang.Code) ? { color: 'var(--mcolor2)' } : {}">{{
                                                        formatEnglishName(lang.Code, lang.EnglishName) }}</span>
                                                <span class="text-xs text-gray-500 mt-0.5">{{ lang.NativeName ||
                                                    lang.Code }}</span>
                                            </div>
                                            <div class="flex items-center ml-2 flex-shrink-0">
                                                <span
                                                    class="text-[8px] font-medium px-1.5 py-[-1px] rounded-full flex items-center"
                                                    style="background-color: rgba(209, 129, 11, 0.15); color: var(--mcolor2); border: 1px solid rgba(209, 129, 11, 0.3);">{{
                                                        lang.Code }}</span>
                                            </div>
                                        </div>
                                    </template>
                                    <div v-else class="w-full flex flex-col items-center mt-1">
                                        <button
                                            class="flex flex-col p-2 items-center justify-center py-1 border-2 border-dashed rounded-lg border-[var(--mcolor)] hover:bg-[rgba(209,129,11,0.05)] transition-colors group"
                                            @click="$MultilingualQuestionnaire._fn.openAddLanguage()">
                                            <div class="flex items-center justify-center gap-2">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg" class="text-[var(--mcolor)]">
                                                    <circle cx="12" cy="12" r="10" stroke="currentColor"
                                                        stroke-width="1.5" fill="none" />
                                                    <path d="M12 8V16M8 12H16" stroke="currentColor" stroke-width="1.5"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                <span class="text-[var(--mcolor)] font-bold text-sm">Add Language</span>
                                            </div>
                                        </button>
                                        <span class="text-xs text-gray-500 mt-2 text-center px-2">Create a new language
                                            version or add
                                            translations</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </JetDropdown>
                    
                    <!-- Split Screen Toggle Button -->
                    <div v-if="showSplitScreenToggle && $page.props.questions.length >= 1" 
                        @click="toggleSplitScreen"
                        class="cursor-pointer transition flex justify-center items-center text-sm font-semibold px-3 py-2 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                        :class="isSplitScreen ? 'bg-[var(--mcolor2)] text-white border-[var(--mcolor2)]' : 'bg-white'">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" 
                            stroke="currentColor" class="w-5 h-5 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" 
                                d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                        <span>Compare</span>
                    </div>
                    
                    <div v-if="$page.props.questions.length >= 1" @click="() => {
                        $MultilingualQuestionnaire._fn.open()
                    }"
                        class="cursor-pointer transition flex justify-center items-center text-sm font-semibold px-3 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-5 h-5 mr-2" style="color: var(--mcolor2);">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                        <span class="text-[var(--mcolor2);]">Multilingual Option</span>
                    </div>
                    <div v-if="$page.props.questions.length >= 1" @click="showModal('question', 'add_edit', 'add')"
                        class="cursor-pointer transition flex justify-center items-center text-sm font-semibold px-3 py-2 rounded-lg bg-[var(--mcolor)] border-2 border-transparent text-white italic">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="w-4 h-4 mr-1 self-center">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Add New Question
                    </div>
                </div>
            </div>

            <div id="myDIVxxx" @scroll="handleScroll" class="main-content pt-[90px]">
                <div class="flex flex-cols-1 place-content-between mr-1">



                </div>

                <template v-if="$page.props.questions.length === 0">
                    <div class="bg-white grid grid-cols-1 gap-4 place-items-center h-1/3 mx-2 rounded-xl">
                        <div
                            class="flex flex-col items-center italic text-slate-400 text-md content-center p-[80px_20px]">
                            <!-- <div class="text-sky-300 w-10 h-10 bg-sky-50 p-2 rounded-lg mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>
                            </div> -->
                            <!-- <img src="@/../assets/icons/emptyQ.svg" class="w-[120px] h-auto pointer-events-none" /> -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-[120px] h-auto pointer-events-none"
                                width="228" height="238" viewBox="0 0 228 238" fill="none">
                                <g clip-path="url(#clip0_487_2890)">
                                    <path
                                        d="M113.78 237.34C176.619 237.34 227.56 186.399 227.56 123.56C227.56 60.7211 176.619 9.78003 113.78 9.78003C50.941 9.78003 0 60.7211 0 123.56C0 186.399 50.941 237.34 113.78 237.34Z"
                                        fill="#F7F6F4" />
                                    <path
                                        d="M112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59ZM55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901ZM108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49ZM174.43 154.59L159.28 32.3801C159.13 31.4001 158.6 30.5201 157.8 29.9401L120.71 0.800055C120.45 0.600055 120.15 0.430055 119.83 0.320055C119.25 0.0400552 118.6 -0.0499448 117.96 0.0300552L39.9799 9.70006C29.7699 10.9701 22.52 20.2701 23.78 30.5001L41.2099 171.11C42.4899 181.33 51.79 188.58 62 187.33L158.22 175.39C168.43 174.1 175.69 164.8 174.43 154.59ZM147.73 30.1601L136.64 31.5301C130.5 32.2901 124.91 27.9401 124.15 21.8101L122.78 10.7101L147.73 30.1601ZM157.3 168L61.09 179.92C54.96 180.68 49.37 176.33 48.61 170.19L31.18 29.5801C30.42 23.4501 34.78 17.8601 40.9 17.1001L118.62 7.47006L120.45 22.2701C120.46 23.2601 120.59 24.2401 120.82 25.2001C122.53 32.4601 129.82 36.9601 137.09 35.2301L151.89 33.4001L167.03 155.52C167.79 161.65 163.43 167.24 157.3 168ZM142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01ZM58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59ZM55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901ZM108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301ZM112.47 139.74C112.22 137.69 110.36 136.24 108.31 136.49L63.9 142C61.86 142.25 60.41 144.12 60.66 146.16C60.91 148.2 62.77 149.66 64.82 149.4L109.23 143.9C111.27 143.64 112.73 141.78 112.47 139.74ZM146.27 109.25C146.01 107.2 144.15 105.75 142.11 106.01L60.6899 116.1C58.6499 116.35 57.19 118.21 57.45 120.26C57.7 122.3 59.56 123.75 61.61 123.5L143.03 113.41C145.07 113.16 146.52 111.29 146.27 109.25ZM143.06 83.3501C142.8 81.3001 140.94 79.8501 138.9 80.1001L57.4799 90.2001C55.4399 90.4501 53.98 92.3101 54.24 94.3601C54.49 96.4001 56.35 97.84 58.4 97.59L139.81 87.5001C141.86 87.2401 143.31 85.3901 143.06 83.3501ZM139.85 57.4301C139.59 55.3901 137.73 53.9501 135.69 54.2001L54.27 64.2901C52.23 64.5501 50.7699 66.4001 51.0299 68.4401C51.2799 70.4901 53.1399 71.9401 55.1899 71.6901L136.6 61.6001C138.65 61.3401 140.1 59.4801 139.85 57.4301Z"
                                        fill="#9CA3B7" />
                                    <path
                                        d="M147.73 30.1601L136.64 31.5301C130.5 32.2901 124.91 27.9401 124.15 21.8101L122.78 10.7101L147.73 30.1601Z"
                                        fill="#E2E1E0" />
                                    <path
                                        d="M167.03 155.52L151.89 33.4001L137.09 35.2301C129.82 36.9601 122.53 32.4601 120.82 25.2001C120.59 24.2401 120.46 23.2601 120.45 22.2701L118.62 7.47009L40.8998 17.1001C34.7798 17.8601 30.4198 23.4501 31.1798 29.5801L48.6098 170.19C49.3698 176.33 54.9598 180.68 61.0898 179.92L157.3 168C163.43 167.24 167.79 161.65 167.03 155.52ZM51.0298 68.4401C50.7698 66.4001 52.2298 64.5501 54.2698 64.2901L135.69 54.2001C137.73 53.9501 139.59 55.3901 139.85 57.4301C140.1 59.4801 138.65 61.3401 136.6 61.6001L55.1898 71.6901C53.1398 71.9401 51.2798 70.4901 51.0298 68.4401ZM54.2398 94.3601C53.9798 92.3101 55.4398 90.4501 57.4798 90.2001L138.9 80.1001C140.94 79.8501 142.8 81.3001 143.06 83.3501C143.31 85.3901 141.86 87.2401 139.81 87.5001L58.3998 97.5901C56.3498 97.8401 54.4898 96.4001 54.2398 94.3601ZM109.23 143.9L64.8198 149.4C62.7698 149.66 60.9098 148.2 60.6598 146.16C60.4098 144.12 61.8598 142.25 63.8998 142L108.31 136.49C110.36 136.24 112.22 137.69 112.47 139.74C112.73 141.78 111.27 143.64 109.23 143.9ZM143.03 113.41L61.6098 123.5C59.5598 123.75 57.6998 122.3 57.4498 120.26C57.1898 118.21 58.6498 116.35 60.6898 116.1L142.11 106.01C144.15 105.75 146.01 107.2 146.27 109.25C146.52 111.29 145.07 113.16 143.03 113.41Z"
                                        fill="#E2E1E0" />
                                    <path
                                        d="M168.77 97.2801H86.7299C84.6699 97.2801 82.9999 98.9401 82.9999 101C82.9999 103.06 84.6699 104.73 86.7299 104.73H168.77C170.83 104.73 172.5 103.06 172.5 101C172.5 98.9401 170.83 97.2801 168.77 97.2801ZM168.77 123.38H86.7299C84.6699 123.38 82.9999 125.05 82.9999 127.11C82.9999 129.17 84.6699 130.83 86.7299 130.83H168.77C170.83 130.83 172.5 129.17 172.5 127.11C172.5 125.05 170.83 123.38 168.77 123.38ZM168.77 149.48H86.7299C84.6699 149.48 82.9999 151.15 82.9999 153.21C82.9999 155.27 84.6699 156.94 86.7299 156.94H168.77C170.83 156.94 172.5 155.27 172.5 153.21C172.5 151.15 170.83 149.48 168.77 149.48ZM131.48 175.58H86.7299C84.6699 175.58 82.9999 177.25 82.9999 179.31C82.9999 181.37 84.6699 183.04 86.7299 183.04H131.48C133.54 183.04 135.21 181.37 135.21 179.31C135.21 177.25 133.54 175.58 131.48 175.58ZM168.77 97.2801H86.7299C84.6699 97.2801 82.9999 98.9401 82.9999 101C82.9999 103.06 84.6699 104.73 86.7299 104.73H168.77C170.83 104.73 172.5 103.06 172.5 101C172.5 98.9401 170.83 97.2801 168.77 97.2801ZM168.77 123.38H86.7299C84.6699 123.38 82.9999 125.05 82.9999 127.11C82.9999 129.17 84.6699 130.83 86.7299 130.83H168.77C170.83 130.83 172.5 129.17 172.5 127.11C172.5 125.05 170.83 123.38 168.77 123.38ZM168.77 149.48H86.7299C84.6699 149.48 82.9999 151.15 82.9999 153.21C82.9999 155.27 84.6699 156.94 86.7299 156.94H168.77C170.83 156.94 172.5 155.27 172.5 153.21C172.5 151.15 170.83 149.48 168.77 149.48ZM131.48 175.58H86.7299C84.6699 175.58 82.9999 177.25 82.9999 179.31C82.9999 181.37 84.6699 183.04 86.7299 183.04H131.48C133.54 183.04 135.21 181.37 135.21 179.31C135.21 177.25 133.54 175.58 131.48 175.58ZM131.48 175.58H86.7299C84.6699 175.58 82.9999 177.25 82.9999 179.31C82.9999 181.37 84.6699 183.04 86.7299 183.04H131.48C133.54 183.04 135.21 181.37 135.21 179.31C135.21 177.25 133.54 175.58 131.48 175.58ZM168.77 149.48H86.7299C84.6699 149.48 82.9999 151.15 82.9999 153.21C82.9999 155.27 84.6699 156.94 86.7299 156.94H168.77C170.83 156.94 172.5 155.27 172.5 153.21C172.5 151.15 170.83 149.48 168.77 149.48ZM168.77 123.38H86.7299C84.6699 123.38 82.9999 125.05 82.9999 127.11C82.9999 129.17 84.6699 130.83 86.7299 130.83H168.77C170.83 130.83 172.5 129.17 172.5 127.11C172.5 125.05 170.83 123.38 168.77 123.38ZM168.77 97.2801H86.7299C84.6699 97.2801 82.9999 98.9401 82.9999 101C82.9999 103.06 84.6699 104.73 86.7299 104.73H168.77C170.83 104.73 172.5 103.06 172.5 101C172.5 98.9401 170.83 97.2801 168.77 97.2801ZM168.77 97.2801H86.7299C84.6699 97.2801 82.9999 98.9401 82.9999 101C82.9999 103.06 84.6699 104.73 86.7299 104.73H168.77C170.83 104.73 172.5 103.06 172.5 101C172.5 98.9401 170.83 97.2801 168.77 97.2801ZM168.77 123.38H86.7299C84.6699 123.38 82.9999 125.05 82.9999 127.11C82.9999 129.17 84.6699 130.83 86.7299 130.83H168.77C170.83 130.83 172.5 129.17 172.5 127.11C172.5 125.05 170.83 123.38 168.77 123.38ZM168.77 149.48H86.7299C84.6699 149.48 82.9999 151.15 82.9999 153.21C82.9999 155.27 84.6699 156.94 86.7299 156.94H168.77C170.83 156.94 172.5 155.27 172.5 153.21C172.5 151.15 170.83 149.48 168.77 149.48ZM131.48 175.58H86.7299C84.6699 175.58 82.9999 177.25 82.9999 179.31C82.9999 181.37 84.6699 183.04 86.7299 183.04H131.48C133.54 183.04 135.21 181.37 135.21 179.31C135.21 177.25 133.54 175.58 131.48 175.58ZM131.48 175.58H86.7299C84.6699 175.58 82.9999 177.25 82.9999 179.31C82.9999 181.37 84.6699 183.04 86.7299 183.04H131.48C133.54 183.04 135.21 181.37 135.21 179.31C135.21 177.25 133.54 175.58 131.48 175.58ZM168.77 149.48H86.7299C84.6699 149.48 82.9999 151.15 82.9999 153.21C82.9999 155.27 84.6699 156.94 86.7299 156.94H168.77C170.83 156.94 172.5 155.27 172.5 153.21C172.5 151.15 170.83 149.48 168.77 149.48ZM168.77 123.38H86.7299C84.6699 123.38 82.9999 125.05 82.9999 127.11C82.9999 129.17 84.6699 130.83 86.7299 130.83H168.77C170.83 130.83 172.5 129.17 172.5 127.11C172.5 125.05 170.83 123.38 168.77 123.38ZM168.77 97.2801H86.7299C84.6699 97.2801 82.9999 98.9401 82.9999 101C82.9999 103.06 84.6699 104.73 86.7299 104.73H168.77C170.83 104.73 172.5 103.06 172.5 101C172.5 98.9401 170.83 97.2801 168.77 97.2801ZM86.7299 104.73H168.77C170.83 104.73 172.5 103.06 172.5 101C172.5 98.9401 170.83 97.2801 168.77 97.2801H86.7299C84.6699 97.2801 82.9999 98.9401 82.9999 101C82.9999 103.06 84.6699 104.73 86.7299 104.73ZM168.77 123.38H86.7299C84.6699 123.38 82.9999 125.05 82.9999 127.11C82.9999 129.17 84.6699 130.83 86.7299 130.83H168.77C170.83 130.83 172.5 129.17 172.5 127.11C172.5 125.05 170.83 123.38 168.77 123.38ZM168.77 149.48H86.7299C84.6699 149.48 82.9999 151.15 82.9999 153.21C82.9999 155.27 84.6699 156.94 86.7299 156.94H168.77C170.83 156.94 172.5 155.27 172.5 153.21C172.5 151.15 170.83 149.48 168.77 149.48ZM131.48 175.58H86.7299C84.6699 175.58 82.9999 177.25 82.9999 179.31C82.9999 181.37 84.6699 183.04 86.7299 183.04H131.48C133.54 183.04 135.21 181.37 135.21 179.31C135.21 177.25 133.54 175.58 131.48 175.58ZM193.7 75.9201L160.48 42.4401C160.24 42.2101 159.97 42.0101 159.66 41.8601C159.12 41.5101 158.49 41.3401 157.84 41.3401H79.2699C68.9799 41.3501 60.6399 49.6901 60.6299 59.9901V201.68C60.6399 211.98 68.9799 220.32 79.2699 220.33H176.22C186.52 220.3 194.86 211.96 194.87 201.67V78.5301C194.84 77.5401 194.42 76.6001 193.7 75.9201ZM161.31 52.5301L183.68 74.9001H172.5C166.32 74.9001 161.31 69.8901 161.31 63.7201V52.5301ZM187.41 201.68C187.41 207.86 182.4 212.87 176.22 212.87H79.2699C73.0999 212.87 68.0899 207.86 68.0899 201.68V59.9901C68.0899 53.8101 73.0999 48.8001 79.2699 48.8001H157.58V63.7201C157.47 64.7001 157.47 65.6901 157.58 66.6701C158.39 74.0901 165.07 79.4501 172.5 78.6301H187.41V201.68ZM168.77 97.2801H86.7299C84.6699 97.2801 82.9999 98.9401 82.9999 101C82.9999 103.06 84.6699 104.73 86.7299 104.73H168.77C170.83 104.73 172.5 103.06 172.5 101C172.5 98.9401 170.83 97.2801 168.77 97.2801ZM168.77 123.38H86.7299C84.6699 123.38 82.9999 125.05 82.9999 127.11C82.9999 129.17 84.6699 130.83 86.7299 130.83H168.77C170.83 130.83 172.5 129.17 172.5 127.11C172.5 125.05 170.83 123.38 168.77 123.38ZM168.77 149.48H86.7299C84.6699 149.48 82.9999 151.15 82.9999 153.21C82.9999 155.27 84.6699 156.94 86.7299 156.94H168.77C170.83 156.94 172.5 155.27 172.5 153.21C172.5 151.15 170.83 149.48 168.77 149.48ZM131.48 175.58H86.7299C84.6699 175.58 82.9999 177.25 82.9999 179.31C82.9999 181.37 84.6699 183.04 86.7299 183.04H131.48C133.54 183.04 135.21 181.37 135.21 179.31C135.21 177.25 133.54 175.58 131.48 175.58ZM131.48 175.58H86.7299C84.6699 175.58 82.9999 177.25 82.9999 179.31C82.9999 181.37 84.6699 183.04 86.7299 183.04H131.48C133.54 183.04 135.21 181.37 135.21 179.31C135.21 177.25 133.54 175.58 131.48 175.58ZM168.77 149.48H86.7299C84.6699 149.48 82.9999 151.15 82.9999 153.21C82.9999 155.27 84.6699 156.94 86.7299 156.94H168.77C170.83 156.94 172.5 155.27 172.5 153.21C172.5 151.15 170.83 149.48 168.77 149.48ZM168.77 123.38H86.7299C84.6699 123.38 82.9999 125.05 82.9999 127.11C82.9999 129.17 84.6699 130.83 86.7299 130.83H168.77C170.83 130.83 172.5 129.17 172.5 127.11C172.5 125.05 170.83 123.38 168.77 123.38ZM168.77 97.2801H86.7299C84.6699 97.2801 82.9999 98.9401 82.9999 101C82.9999 103.06 84.6699 104.73 86.7299 104.73H168.77C170.83 104.73 172.5 103.06 172.5 101C172.5 98.9401 170.83 97.2801 168.77 97.2801ZM168.77 97.2801H86.7299C84.6699 97.2801 82.9999 98.9401 82.9999 101C82.9999 103.06 84.6699 104.73 86.7299 104.73H168.77C170.83 104.73 172.5 103.06 172.5 101C172.5 98.9401 170.83 97.2801 168.77 97.2801ZM168.77 123.38H86.7299C84.6699 123.38 82.9999 125.05 82.9999 127.11C82.9999 129.17 84.6699 130.83 86.7299 130.83H168.77C170.83 130.83 172.5 129.17 172.5 127.11C172.5 125.05 170.83 123.38 168.77 123.38ZM168.77 149.48H86.7299C84.6699 149.48 82.9999 151.15 82.9999 153.21C82.9999 155.27 84.6699 156.94 86.7299 156.94H168.77C170.83 156.94 172.5 155.27 172.5 153.21C172.5 151.15 170.83 149.48 168.77 149.48ZM131.48 175.58H86.7299C84.6699 175.58 82.9999 177.25 82.9999 179.31C82.9999 181.37 84.6699 183.04 86.7299 183.04H131.48C133.54 183.04 135.21 181.37 135.21 179.31C135.21 177.25 133.54 175.58 131.48 175.58ZM131.48 175.58H86.7299C84.6699 175.58 82.9999 177.25 82.9999 179.31C82.9999 181.37 84.6699 183.04 86.7299 183.04H131.48C133.54 183.04 135.21 181.37 135.21 179.31C135.21 177.25 133.54 175.58 131.48 175.58ZM168.77 149.48H86.7299C84.6699 149.48 82.9999 151.15 82.9999 153.21C82.9999 155.27 84.6699 156.94 86.7299 156.94H168.77C170.83 156.94 172.5 155.27 172.5 153.21C172.5 151.15 170.83 149.48 168.77 149.48ZM168.77 123.38H86.7299C84.6699 123.38 82.9999 125.05 82.9999 127.11C82.9999 129.17 84.6699 130.83 86.7299 130.83H168.77C170.83 130.83 172.5 129.17 172.5 127.11C172.5 125.05 170.83 123.38 168.77 123.38ZM168.77 97.2801H86.7299C84.6699 97.2801 82.9999 98.9401 82.9999 101C82.9999 103.06 84.6699 104.73 86.7299 104.73H168.77C170.83 104.73 172.5 103.06 172.5 101C172.5 98.9401 170.83 97.2801 168.77 97.2801ZM168.77 97.2801H86.7299C84.6699 97.2801 82.9999 98.9401 82.9999 101C82.9999 103.06 84.6699 104.73 86.7299 104.73H168.77C170.83 104.73 172.5 103.06 172.5 101C172.5 98.9401 170.83 97.2801 168.77 97.2801ZM168.77 123.38H86.7299C84.6699 123.38 82.9999 125.05 82.9999 127.11C82.9999 129.17 84.6699 130.83 86.7299 130.83H168.77C170.83 130.83 172.5 129.17 172.5 127.11C172.5 125.05 170.83 123.38 168.77 123.38ZM168.77 149.48H86.7299C84.6699 149.48 82.9999 151.15 82.9999 153.21C82.9999 155.27 84.6699 156.94 86.7299 156.94H168.77C170.83 156.94 172.5 155.27 172.5 153.21C172.5 151.15 170.83 149.48 168.77 149.48ZM131.48 175.58H86.7299C84.6699 175.58 82.9999 177.25 82.9999 179.31C82.9999 181.37 84.6699 183.04 86.7299 183.04H131.48C133.54 183.04 135.21 181.37 135.21 179.31C135.21 177.25 133.54 175.58 131.48 175.58Z"
                                        fill="#9CA3B7" />
                                    <path
                                        d="M183.68 74.9H172.5C166.32 74.9 161.31 69.89 161.31 63.72V52.53L183.68 74.9Z"
                                        fill="white" />
                                    <path
                                        d="M172.5 78.63C165.07 79.45 158.39 74.09 157.58 66.67C157.47 65.69 157.47 64.7001 157.58 63.7201V48.8H79.2698C73.0998 48.8 68.0898 53.8101 68.0898 59.9901V201.68C68.0898 207.86 73.0998 212.87 79.2698 212.87H176.22C182.4 212.87 187.41 207.86 187.41 201.68V78.63H172.5ZM131.48 183.04H86.7299C84.6699 183.04 82.9998 181.37 82.9998 179.31C82.9998 177.25 84.6699 175.58 86.7299 175.58H131.48C133.54 175.58 135.21 177.25 135.21 179.31C135.21 181.37 133.54 183.04 131.48 183.04ZM168.77 156.94H86.7299C84.6699 156.94 82.9998 155.27 82.9998 153.21C82.9998 151.15 84.6699 149.48 86.7299 149.48H168.77C170.83 149.48 172.5 151.15 172.5 153.21C172.5 155.27 170.83 156.94 168.77 156.94ZM168.77 130.83H86.7299C84.6699 130.83 82.9998 129.17 82.9998 127.11C82.9998 125.05 84.6699 123.38 86.7299 123.38H168.77C170.83 123.38 172.5 125.05 172.5 127.11C172.5 129.17 170.83 130.83 168.77 130.83ZM168.77 104.73H86.7299C84.6699 104.73 82.9998 103.06 82.9998 101C82.9998 98.94 84.6699 97.28 86.7299 97.28H168.77C170.83 97.28 172.5 98.94 172.5 101C172.5 103.06 170.83 104.73 168.77 104.73Z"
                                        fill="white" />
                                    <path
                                        d="M178.48 168.4C176.35 168.4 174.63 170.12 174.63 172.25V195.33C174.63 197.45 176.35 199.18 178.48 199.18C180.61 199.18 182.33 197.46 182.33 195.33V183.79V172.25C182.33 170.12 180.61 168.4 178.48 168.4ZM178.48 168.4C176.35 168.4 174.63 170.12 174.63 172.25V195.33C174.63 197.45 176.35 199.18 178.48 199.18C180.61 199.18 182.33 197.46 182.33 195.33V183.79V172.25C182.33 170.12 180.61 168.4 178.48 168.4ZM213.93 176.51C211.99 171.93 209.22 167.81 205.69 164.27C202.16 160.74 198.04 157.96 193.46 156.03C188.71 154.02 183.68 153 178.48 153C173.28 153 168.24 154.02 163.5 156.03C158.92 157.97 154.8 160.74 151.27 164.27C147.73 167.81 144.96 171.92 143.02 176.51C141.01 181.25 140 186.29 140 191.49C140 196.69 141.01 201.72 143.02 206.47C144.96 211.05 147.73 215.17 151.27 218.7C154.8 222.23 158.92 225.01 163.5 226.94C168.24 228.95 173.28 229.97 178.48 229.97C183.68 229.97 188.71 228.95 193.46 226.94C198.04 225 202.16 222.23 205.69 218.7C209.22 215.17 212 211.05 213.93 206.47C215.94 201.72 216.96 196.68 216.96 191.49C216.96 186.3 215.94 181.25 213.93 176.51ZM178.48 222.27C161.5 222.27 147.7 208.46 147.7 191.49C147.7 174.52 161.51 160.71 178.48 160.71C195.45 160.71 209.26 174.52 209.26 191.49C209.26 208.46 195.45 222.27 178.48 222.27ZM178.48 168.4C176.35 168.4 174.63 170.12 174.63 172.25V195.33C174.63 197.45 176.35 199.18 178.48 199.18C180.61 199.18 182.33 197.46 182.33 195.33V183.79V172.25C182.33 170.12 180.61 168.4 178.48 168.4Z"
                                        fill="#ee920c" />
                                    <path
                                        d="M182.13 172.16C179.33 165.76 175.963 169.493 174.63 172.16V195.16C175.63 201.16 182.13 199.16 182.13 195.16V172.16Z"
                                        fill="#ee920c" stroke="#ee920c" />
                                    <path
                                        d="M178.48 160.71C161.5 160.71 147.7 174.52 147.7 191.49C147.7 208.46 161.51 222.27 178.48 222.27C195.45 222.27 209.26 208.46 209.26 191.49C209.26 174.52 195.45 160.71 178.48 160.71ZM178.48 212.65C176.35 212.65 174.63 210.93 174.63 208.8C174.63 206.67 176.35 204.95 178.48 204.95C180.61 204.95 182.33 206.67 182.33 208.8C182.33 210.93 180.61 212.65 178.48 212.65ZM182.33 195.34C182.33 197.46 180.61 199.19 178.48 199.19C176.35 199.19 174.63 197.47 174.63 195.34V172.26C174.63 170.13 176.35 168.41 178.48 168.41C180.61 168.41 182.33 170.13 182.33 172.26V195.34Z"
                                        fill="white" />
                                    <path
                                        d="M182.33 208.81C182.33 210.93 180.61 212.66 178.48 212.66C176.35 212.66 174.63 210.94 174.63 208.81C174.63 206.68 176.35 204.96 178.48 204.96C180.61 204.96 182.33 206.68 182.33 208.81Z"
                                        fill="#ee920c" />
                                </g>
                                <defs>
                                    <clipPath id="clip0_487_2890">
                                        <rect width="227.56" height="237.34" fill="white" />
                                    </clipPath>
                                </defs>

                            </svg>
                            <div class="self-center w-full font-bold pt-[12px] mb-[24px] text-center text-balance">
                                No questions yet!
                            </div>
                            <div @click="showModal('question', 'add_edit', 'add')"
                                class="cursor-pointer transition flex justify-center items-center text-sm font-semibold px-3 py-2 rounded-lg bg-[var(--mcolor)] border-2 border-transparent text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-4 h-4 mr-1 self-center">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Add Question
                            </div>

                        </div>
                    </div>
                </template>



                <div :class="isSplitScreen ? 'grid grid-cols-2 gap-[24px] max-w-[100%] m-[auto]' : 'flex flex-col gap-[24px] max-w-[1200px] m-[auto]'">

                    <template v-for="(question, index) in $page.props.questions" :key="index">
                        <!-- Split Screen Mode: Show Original and Translated side by side -->
                        <template v-if="isSplitScreen">
                            <!-- Original Language (Left) - forced to ORIGINAL language via OriginalLanguageScope -->
                            <OriginalLanguageScope>
                            <div class="bg-white rounded-lg overflow-hidden color-[var(--txtcolor)] min-h-[300px] pb-[20px] relative border-[1px] border-[#e5e7eb]">
                                <div class="relative qCont flex flex-col flex-grow bg-gray-0">
                                    <!-- Read-only left side in split screen: no edit/logic/menu actions -->

                                    <div class="relative grid">
                                        <div class="w-full pt-3">
                                            <div class="flex justify-items-center items-center">
                                                <div class="relative grid grid-cols-[auto_1fr] gap-2 w-auto max-w-[calc(100%-150px)]">
                                                    <div class="flex gap-1 rounded-[0px_0px_15px_0px] px-8">
                                                        <div class="mt-2 mr-2 font-bold text-xs text-[var(--txtcolor)] rounded-full h-8 w-8 flex justify-center">
                                                            <div v-html="qt_icons[question.question_type_id]"></div>
                                                        </div>
                                                        <div class="flex flex-row items-center gap-2">
                                                            <div class="text-secondary text-xs text-white font-medium px-2 py-0.5 rounded-full bg-[#ee920c] bg-opacity-100">
                                                                {{ 'Q' + parseInt(parseInt($page.props.questionnaire.setup.variable_start) + (parseInt(question.order) - 1)) }}
                                                            </div>
                                                            <h3 v-if="getChartTitle(question, ' ')" class="truncate font-medium grow-[1] text-[#ee920c]">
                                                                {{ getChartTitle(question, ' ') }}
                                                            </h3>
                                                            <div class="text-xs font-medium px-2 py-0.5 rounded-full flex items-center bg-[#e0f2fe] text-[#0369a1] border-[#bae6fd] border-[1px]">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                                                </svg>
                                                                {{ $page.props.questionnaire.setup.variable + parseInt(parseInt($page.props.questionnaire.setup.variable_start) + (parseInt(question.order) - 1)) }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="pt-2 mx-8">
                                                <div class="mx-8 text-m text-[var(--txtcolor)] qImage w-[95%] ml-11" :dir="getRTL(question.setup, ' ') ? 'rtl' : 'ltr'" v-html="getQuestionContent(question, ' ')"></div>
                                            </div>
                                            <div class="relative flex flex-row my-4 mx-8">
                                                <div class="flex flex-col" style="width: 100% !important">
                                                    <span class="">
                                                        <div class="pb-5 ml-11" :class="[question.question_type_id != 1 ? 'text-[var(--txtcolor)] text-sm rounded-lg' : '']">
                                                            <multiple-choice-list v-if="question.question_type_id == 2" :choices="question.choices" :setup="question.setup" />
                                                            <image-selection-list v-else-if="question.question_type_id == 3" :choices="question.choices" :setup="question.setup" />
                                                            <single-textbox-list v-else-if="question.question_type_id == 4" :setup="question.setup" />
                                                            <multiple-textbox-list v-else-if="question.question_type_id == 5" :setup="question.setup" :items="question.items" />
                                                            <open-ended-list v-else-if="question.question_type_id == 6" :setup="question.setup" />
                                                            <constant-sum-list v-else-if="question.question_type_id == 7" :items="question.items" :setup="question.setup" />
                                                            <slider-list v-else-if="question.question_type_id == 8 || question.question_type_id == 15" :items="question.items" :values="question.values" :setup="question.setup" :type="question.question_type_id" />
                                                            <ranking-list v-else-if="question.question_type_id == 9" :setup="question.setup" :items="question.items" />
                                                            <star-rating-list v-else-if="question.question_type_id == 10" :items="question.items" :setup="question.setup" />
                                                            <matrix-choice-list v-else-if="question.question_type_id == 11" :columns="question.columns" :rows="question.rows" :setup="question.setup" />
                                                            <matrix-multi-list v-else-if="question.question_type_id == 12" :items="question.items" :rows="question.rows" :setup="question.setup" />
                                                            <memory-test-desc-list v-else-if="question.question_type_id == 13" :items="question.items" :setup="question.setup" />
                                                            <memory-test-comparison-list v-else-if="question.question_type_id == 14" :itemsA="question.item1" :itemsB="question.item2" :setup="question.setup" />
                                                            <segmentation-list v-else-if="question.question_type_id == 16" :items="question.items" :setup="question.setup" :values="question.values" />
                                                            <pairwise-comparison-list v-else-if="question.question_type_id == 19" :items="question.items" :setup="question.setup" />
                                                            <best-worst-scale-list v-else-if="question.question_type_id == 20" :items="question.items" :setup="question.setup" :choices="question.choices" />
                                                            <bar-chart-list v-else-if="question.question_type_id == 21" :items="question.items" :setup="question.setup" />
                                                        </div>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-left items-center text-xs text-slate-400 font-normal italic py-[12px] absolute left-[30px] bottom-[10px] gap-1 border-t-[1px] border-gray-200 w-[calc(100%-60px)]">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <div>Last modified <span v-if="question?.update_info?.name">by <strong>{{ question?.update_info?.name }}</strong></span> {{ differenceTime(question.updated_at) }}</div>
                                </div>
                            </div>
                            </OriginalLanguageScope>
                            <!-- Translated Language (Right) - Note: Child components will use current language from provide/inject -->
                            <div class="bg-white rounded-lg overflow-hidden color-[var(--txtcolor)] min-h-[300px] pb-[20px] relative border-[1px] border-[#e5e7eb]">
                                <div class="relative qCont flex flex-col flex-grow bg-gray-0">
                                    <!-- Actions (Logic + menu) pinned to top-right of the entire card -->
                                    <div class="absolute top-3 right-3 z-10 flex items-center gap-3 text-slate-400 text-sm">
                                        <div class="flex items-center gap-3">
                                                               <!-- AI Translate button -->
                                                            <button
                                                                type="button"
                                                                :disabled="retranslateLoadingQuestionId === (question.id ?? question.order)"
                                                                class="flex items-center gap-1 px-2 py-1 rounded-full border border-sky-300 bg-sky-50 text-sky-700 text-[11px] font-semibold hover:bg-sky-100 disabled:opacity-60 disabled:cursor-not-allowed"
                                                                title="Re-translate using AI"
                                                                @click="retranslateQuestion(question)"
                                                            >
                                                                <template v-if="retranslateLoadingQuestionId === (question.id ?? question.order)">
                                                                    <span class="pl-3 pr-3 flex items-center gap-1">
                                                                        <font-awesome-icon :icon="faIcon.spinner" spin />
                                                                        Wait
                                                                    </span>
                                                                </template>
                                                                <template v-else>
                                                                    <span class="flex items-center gap-1">
                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                            viewBox="0 0 24 24"
                                                                            fill="none" stroke="currentColor" stroke-width="1.5"
                                                                            class="w-4 h-4">
                                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                                d="M4.5 5.25h9m-9 4.5h5.25M4.5 14.25h3m5.25-9h6l-3 7.5-3-7.5Zm0 0L9 19.5" />
                                                                        </svg>
                                                                        <span>Re-translate</span>
                                                                    </span>
                                                                </template>
                                                            </button>
                                                            <button
                                                                type="button"
                                                                class="theme-action cursor-pointer text-slate-400 pt-1 hover:text-slate-500 rounded-full"
                                                                @click="showModal('question', 'add_edit', 'edit', index)"
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                                    fill="none" stroke="currentColor" stroke-width="1.5"
                                                                    class="w-6 h-6">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M16.862 4.487l1.651 1.651a2.25 2.25 0 010 3.182l-7.2 7.2a2.25 2.25 0 01-1.06.592L7.5 17.25l.374-3.37a2.25 2.25 0 01.592-1.06l7.2-7.2a2.25 2.25 0 013.182 0z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M18 13.5V18.75A2.25 2.25 0 0115.75 21H6A2.25 2.25 0 013.75 18.75V9A2.25 2.25 0 016 6.75H11.25" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>

                                    <div class="relative grid">
                                        <div class="w-full pt-3">
                                            <div class="flex justify-items-center items-center">
                                                <div class="relative grid grid-cols-[auto_1fr] gap-2 w-auto max-w-[calc(100%-150px)]">
                                                    <div class="flex gap-1 rounded-[0px_0px_15px_0px] px-8">
                                                        <div class="mt-2 mr-2 font-bold text-xs text-[var(--txtcolor)] rounded-full h-8 w-8 flex justify-center">
                                                            <div v-html="qt_icons[question.question_type_id]"></div>
                                                        </div>
                                                        <div class="flex flex-row items-center gap-2">
                                                            <div class="text-secondary text-xs text-white font-medium px-2 py-0.5 rounded-full bg-[#ee920c] bg-opacity-100">
                                                                {{ 'Q' + parseInt(parseInt($page.props.questionnaire.setup.variable_start) + (parseInt(question.order) - 1)) }}
                                                            </div>
                                                            <h3 v-if="getChartTitle(question, currentLanguageCode)" class="truncate font-medium grow-[1] text-[#ee920c]">
                                                                {{ getChartTitle(question, currentLanguageCode) }}
                                                            </h3>
                                                            <div class="text-xs font-medium px-2 py-0.5 rounded-full flex items-center bg-[#e0f2fe] text-[#0369a1] border-[#bae6fd] border-[1px]">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                                                </svg>
                                                                {{ $page.props.questionnaire.setup.variable + parseInt(parseInt($page.props.questionnaire.setup.variable_start) + (parseInt(question.order) - 1)) }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="pt-2 mx-8">
                                                <div class="mx-8 text-m text-[var(--txtcolor)] qImage w-[95%] ml-11" :dir="getRTL(question.setup, currentLanguageCode) ? 'rtl' : 'ltr'" v-html="getQuestionContent(question, currentLanguageCode)"></div>
                                            </div>
                                            <div class="relative flex flex-row my-4 mx-8">
                                                <div class="flex flex-col" style="width: 100% !important">
                                                    <span class="">
                                                        <div class="pb-5 ml-11" :class="[question.question_type_id != 1 ? 'text-[var(--txtcolor)] text-sm rounded-lg' : '']">
                                                            <multiple-choice-list v-if="question.question_type_id == 2" :choices="question.choices" :setup="question.setup" />
                                                            <image-selection-list v-else-if="question.question_type_id == 3" :choices="question.choices" :setup="question.setup" />
                                                            <single-textbox-list v-else-if="question.question_type_id == 4" :setup="question.setup" />
                                                            <multiple-textbox-list v-else-if="question.question_type_id == 5" :setup="question.setup" :items="question.items" />
                                                            <open-ended-list v-else-if="question.question_type_id == 6" :setup="question.setup" />
                                                            <constant-sum-list v-else-if="question.question_type_id == 7" :items="question.items" :setup="question.setup" />
                                                            <slider-list v-else-if="question.question_type_id == 8 || question.question_type_id == 15" :items="question.items" :values="question.values" :setup="question.setup" :type="question.question_type_id" />
                                                            <ranking-list v-else-if="question.question_type_id == 9" :setup="question.setup" :items="question.items" />
                                                            <star-rating-list v-else-if="question.question_type_id == 10" :items="question.items" :setup="question.setup" />
                                                            <matrix-choice-list v-else-if="question.question_type_id == 11" :columns="question.columns" :rows="question.rows" :setup="question.setup" />
                                                            <matrix-multi-list v-else-if="question.question_type_id == 12" :items="question.items" :rows="question.rows" :setup="question.setup" />
                                                            <memory-test-desc-list v-else-if="question.question_type_id == 13" :items="question.items" :setup="question.setup" />
                                                            <memory-test-comparison-list v-else-if="question.question_type_id == 14" :itemsA="question.item1" :itemsB="question.item2" :setup="question.setup" />
                                                            <segmentation-list v-else-if="question.question_type_id == 16" :items="question.items" :setup="question.setup" :values="question.values" />
                                                            <pairwise-comparison-list v-else-if="question.question_type_id == 19" :items="question.items" :setup="question.setup" />
                                                            <best-worst-scale-list v-else-if="question.question_type_id == 20" :items="question.items" :setup="question.setup" :choices="question.choices" />
                                                            <bar-chart-list v-else-if="question.question_type_id == 21" :items="question.items" :setup="question.setup" />
                                                        </div>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-left items-center text-xs text-slate-400 font-normal italic py-[12px] absolute left-[30px] bottom-[10px] gap-1 border-t-[1px] border-gray-200 w-[calc(100%-60px)]">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <div>Last modified <span v-if="question?.update_info?.name">by <strong>{{ question?.update_info?.name }}</strong></span> {{ differenceTime(question.updated_at) }}</div>
                                </div>
                            </div>
                        </template>
                        <!-- Normal Mode: Single view -->
                        <div v-else
                            class="bg-white rounded-lg overflow-hidden color-[var(--txtcolor)] min-h-[300px] pb-[20px] relative border-[1px] border-[#e5e7eb]">

                        <div class="relative qCont flex flex-col flex-grow bg-gray-0" :class="{
                            '': $page.props.questions.length != index + 1,
                        }">
                            <div class="relative grid">

                                <div class="w-full pt-3">
                                    <!-- <div class="border-b-2 border-[var(--mcolor)] mr-8 ml-8"></div> -->
                                    <!-- <div
                                        class="pb-1 relative font-bold text-md text-[var(--txtcolor)] bg-[var(--mcolor2)] h-6 w-16 rounded-b-[50%] flex justify-center items-center translate-x-[-50%] translate-y-[-50%] left-[50%] top-[-22px]">
                                        {{ 'Q' +
                                            parseInt(parseInt($page.props.questionnaire.setup.variable_start)
                                                +
                                                (parseInt(question.order) - 1)) }}
                                    </div> -->
                                    <div class="flex justify-items-center items-center">
                                        <!-- border-b-2 border-r-2 border-[var(--mcolor)]  rounded-br-[50px] pr-8 -->
                                        <div
                                            class="relative grid grid-cols-[auto_1fr] gap-2 w-auto max-w-[calc(100%-150px)]">

                                            <!-- <div class="w-auto flex justify-center gap-2 font-bold text-md rounded-[0px_0px_15px_0px] p-[12px] items-center bg-[var(--mcolor)] cursor-pointer">
                                               
                                                <div>{{
                                                    $page.props.questionnaire.setup.variable + parseInt(parseInt($page.props.questionnaire.setup.variable_start) + (parseInt(question.order)-1))
                                                }}</div>
                                            </div>
                                            
                                            <div class="flex flex-row gap-2 p-[12px] text-2xl font-[700] text-[--mcolor2] relative" >
                                                <div><img class="w-[30px] h-auto" :src="qt_icons[question.question_type_id]"/></div>
                                                <div v-if="getChartTitle(question)" class="truncate">{{ getChartTitle(question) }}</div>
                                            </div> -->

                                            <div class="flex gap-1 rounded-[0px_0px_15px_0px] px-8">
                                                <div
                                                    class="mt-2 mr-2 font-bold text-xs text-[var(--txtcolor)] rounded-full h-8 w-8 flex justify-center">
                                                    <!-- {{ 'Q' +
                                                        parseInt(parseInt($page.props.questionnaire.setup.variable_start)
                                                            +
                                                            (parseInt(question.order) - 1)) }} -->
                                                    <!-- <img class="w-[35px] h-auto"
                                                        :src="qt_icons[question.question_type_id]" /> -->
                                                    <div v-html="qt_icons[question.question_type_id]"></div>
                                                </div>

                                                <div class="flex flex-row items-center gap-2">
                                                    <div
                                                        class="text-secondary text-xs text-white font-medium px-2 py-0.5 rounded-full bg-[#ee920c] bg-opacity-100">
                                                        {{ 'Q' +
                                                            parseInt(parseInt($page.props.questionnaire.setup.variable_start)
                                                                +
                                                                (parseInt(question.order) - 1)) }}
                                                    </div>
                                                    <h3 v-if="getChartTitle(question)"
                                                        class="truncate font-medium grow-[1] text-[#ee920c]">
                                                        {{
                                                            getChartTitle(question) }}</h3>

                                                    <div
                                                        class="text-xs font-medium px-2 py-0.5 rounded-full flex items-center bg-[#e0f2fe] text-[#0369a1] border-[#bae6fd] border-[1px]">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                                        </svg>

                                                        {{

                                                            $page.props.questionnaire.setup.variable +
                                                            parseInt(parseInt($page.props.questionnaire.setup.variable_start)
                                                                +
                                                                (parseInt(question.order) - 1))
                                                        }}
                                                    </div>

                                                </div>

                                            </div>

                                            <!-- <div class="bg-white   border-l-[2px] top-0 rounded-tl-[70px] border-[var(--mcolor)] h-[70px] w-[10vmin]"></div> -->




                                        </div>


                                        <div class="grid grid-cols-1 items-center absolute right-[12px] top-[6px] ">


                                            <div v-if="1"
                                                class="mt-4 flex justify-end gap-3 mx-4 text-slate-400 text-sm">
                                                <div
                                                    class=" py-0 px-1 rounded-md font-normal hover:text-slate-500 cursor-pointer">
                                                    <a @click="
                                                        showModal(
                                                            'question',
                                                            'skip_logic',
                                                            'edit',
                                                            index
                                                        )
                                                        "
                                                        class="relative inline-flex items-center px-2 py-1 border border-[1px] rounded-md text-xs text-black tracking-widest focus:outline-none !bg-white hover:!bg-gray-50"
                                                        href="#"><svg xmlns="http://www.w3.org/2000/svg"
                                                            class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                                                            </path>
                                                        </svg>Logic
                                                        <span v-if="badge(question)"
                                                            class="absolute -top-2 -right-3 grid text-xs ml-1 place-items-center w-5 h-5 bg-red-500 text-white rounded-full">
                                                            <span>{{ badge(question) }}</span>
                                                        </span>
                                                    </a>
                                                </div>

                                                <JetDropdown :align="'right'" width="100">
                                                    <template #trigger>
                                                        <div type="button" id="menu-button" aria-expanded="true"
                                                            aria-haspopup="true"
                                                            class="theme-action cursor-pointer text-slate-400 pt-1 hover:text-slate-500 rounded-full">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                viewBox="0 0 24 24" stroke-width="1.5"
                                                                stroke="currentColor" class="w-6 h-6">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                                            </svg>
                                                        </div>
                                                    </template>
                                                    <template #content>
                                                        <div class="w-36">
                                                            <div
                                                                class="px-4 pt-2 text-xs text-gray-400 dark:text-gray-100">
                                                                Actions
                                                            </div>
                                                            <div class="overflow-auto max-h-80vh min-w-xl mt-2">
                                                                <JetDropdownLink as="button"
                                                                    @click="showModal('question', 'add_edit', 'edit', index)">
                                                                    <span class="text-slate-500">Edit</span>
                                                                </JetDropdownLink>
                                                                <JetDropdownLink as="button"
                                                                    @click="showModal('question', 'copy', 'copy', index)">
                                                                    <span class="text-slate-500">Duplicate</span>
                                                                </JetDropdownLink>
                                                                <JetDropdownLink as="button"
                                                                    @click="showModal('question', 'move_question', 'move', index)">
                                                                    <span class="text-slate-500">Move</span>
                                                                </JetDropdownLink>
                                                                <JetDropdownLink as="button"
                                                                    @click="showModal('question', 'delete', 'delete', index)">
                                                                    <span class="text-slate-500">Delete</span>
                                                                </JetDropdownLink>
                                                                <JetSectionBorder />
                                                                <!-- <JetDropdownLink as="a">
                                                                    <a :href="$page.props.engine_url +
                                                                        '?code=' +
                                                                        $page.props.questionnaire
                                                                            .code +
                                                                        '&preview=1&gtq=' +
                                                                        (index + 1) +
                                                                        '&id=test-' +
                                                                        random_str(5)
                                                                        " href="javascript:void(0);" target="_blank"
                                                                        class="text-sky-400 flex flex-row items-center liLink hover:text-sky-500">

                                                                        Preview

                                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                            viewBox="0 0 24 24" stroke-width="2.5"
                                                                            stroke="currentColor" class="w-4 h-4 ml-1">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                                                        </svg>
                                                                    </a>
                                                                </JetDropdownLink> -->
                                                                <JetDropdownLink as="a">
                                                                    <a :href="$page.props.engine_url +
                                                                        '?code=' +
                                                                        $page.props.questionnaire
                                                                            .code +
                                                                        '&preview=1&gtq=' +
                                                                        (index + 1) +
                                                                        previewLangcParam +
                                                                        '&id=id-test'" href="javascript:void(0);"
                                                                        target="_blank"
                                                                        class="text-sky-400 flex flex-row items-center liLink hover:text-sky-500">

                                                                        Preview

                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                            fill="none" viewBox="0 0 24 24"
                                                                            stroke-width="2.5" stroke="currentColor"
                                                                            class="w-4 h-4 ml-1">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                                                        </svg>
                                                                    </a>
                                                                </JetDropdownLink>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </JetDropdown>

                                            </div>


                                        </div>

                                    </div>

                                    <div v-if="question.question_type_id == 1" class="text-sm text-gray-500 ml-[75px]">
                                        Descriptive
                                    </div>
                                    <div v-else-if="question.question_type_id == 2"
                                        class="text-sm text-gray-500 ml-[75px]">Text
                                        Selection</div>
                                    <div v-else-if="question.question_type_id == 3"
                                        class="text-sm text-gray-500 ml-[75px]">Image
                                        selection</div>
                                    <div v-else-if="question.question_type_id == 4"
                                        class="text-sm text-gray-500 ml-[75px]">Single
                                        textbox</div>
                                    <div v-else-if="question.question_type_id == 5"
                                        class="text-sm text-gray-500 ml-[75px]">Multiple
                                        textbox</div>
                                    <div v-else-if="question.question_type_id == 6"
                                        class="text-sm text-gray-500 ml-[75px]">Open
                                        ended</div>
                                    <div v-else-if="question.question_type_id == 7"
                                        class="text-sm text-gray-500 ml-[75px]">Pie
                                        Chart</div>
                                    <div v-else-if="question.question_type_id == 8"
                                        class="text-sm text-gray-500 ml-[75px]">Slider
                                    </div>
                                    <div v-else-if="question.question_type_id == 9"
                                        class="text-sm text-gray-500 ml-[75px]">Ranking
                                    </div>
                                    <div v-else-if="question.question_type_id == 10"
                                        class="text-sm text-gray-500 ml-[75px]">Star
                                        rating</div>
                                    <div v-else-if="question.question_type_id == 11"
                                        class="text-sm text-gray-500 ml-[75px]">Matrix
                                    </div>
                                    <div v-else-if="question.question_type_id == 15"
                                        class="text-sm text-gray-500 ml-[75px]">Button
                                        rating</div>
                                    <div v-else-if="question.question_type_id == 16"
                                        class="text-sm text-gray-500 ml-[75px]">
                                        Segmentation</div>
                                    <div v-else-if="question.question_type_id == 18"
                                        class="text-sm text-gray-500 ml-[75px]">Video
                                        Player</div>
                                    <div v-else-if="question.question_type_id == 19"
                                        class="text-sm text-gray-500 ml-[75px]">
                                        Pairwise Comparison</div>
                                    <div v-else-if="question.question_type_id == 20"
                                        class="text-sm text-gray-500 ml-[75px]">Best
                                        Worst Scale</div>
                                    <div v-else-if="question.question_type_id == 21"
                                        class="text-sm text-gray-500 ml-[75px]">Bar
                                        Chart</div>
                                    <div v-else class="text-sm text-gray-500 ml-[75px]">Question Type</div>
                                    <!-- <div class="relative border-b-[1px] border-gray-100 ml-8 mr-8 pt-3"></div> -->
                                    <!-- <div class="pt-8 mx-8"
                                        v-if="Object.hasOwn(question.setup, 'hideQuestionFrame') && question.question_type_id == 15">
                                        <div v-if="!question.setup.hideQuestionFrame"
                                            class="text-sm text-[var(--txtcolor)] qImage w-3/4"
                                            v-html="question.question"></div>
                                    </div> -->

                                    <div class="pt-8 mx-8 flex justify-center items-center" :dir="isRTL ? 'rtl' : 'ltr'"
                                        v-if="question.question_type_id == 19" v-html="getQuestionContent(question)">
                                    </div>
                                    <div class="pt-2 mx-8" v-else>
                                        <!-- {{ question }} -->
                                        <div class="flex gap-2"
                                            v-if="question.question_type_id == 1 && question.setup.layout == 2">
                                            <img :src="question.setup.layout_img.src" alt="" class="w-[300px]">
                                            <div class="" :dir="isRTL ? 'rtl' : 'ltr'"
                                                v-html="getQuestionContent(question)"></div>
                                        </div>
                                        <div class="flex gap-2"
                                            v-else-if="question.question_type_id == 1 && question.setup.layout == 3">
                                            <div class="" :dir="isRTL ? 'rtl' : 'ltr'"
                                                v-html="getQuestionContent(question)"></div>
                                            <img :src="question.setup.layout_img.src" alt="" class="w-[300px]">
                                        </div>
                                        <div class="flex flex-col gap-2 justify-center"
                                            v-else-if="question.question_type_id == 1 && question.setup.layout == 4">
                                            <img class="w-[300px]" :src="question.setup.layout_img.src" alt="">
                                            <div :dir="isRTL ? 'rtl' : 'ltr'" v-html="getQuestionContent(question)">
                                            </div>
                                        </div>

                                        <div class="mb-10 flex flex-col gap-2 justify-center pt-8 mx-11"
                                            v-else-if="question.question_type_id == 18">
                                            <div :dir="isRTL ? 'rtl' : 'ltr'" v-html="getQuestionContent(question)">
                                            </div>
                                            <video class="w-full" controls muted :key="question.setup.layout_video.src">
                                                <source :src="VideoWithHeaders(question.setup.layout_video.src)"
                                                    type="video/mp4" />
                                            </video>

                                        </div>

                                        <div class="mx-8 text-m text-[var(--txtcolor)] qImage w-[95%] ml-11"
                                            :dir="isRTL ? 'rtl' : 'ltr'" v-else v-html="getQuestionContent(question)">
                                        </div>


                                    </div>

                                    <!-- <div class="pt-8 mx-8">
                                        <div class="text-sm text-slate-900 qImage w-3/4">  Chart Title: {{ getChartTitle(question) }}</div>
                                    </div> -->

                                    <div class="relative flex flex-row my-4 mx-8">
                                        <div class="flex flex-col" style="width: 100% !important">
                                            <span class="">
                                                <div class="pb-5 ml-11" :class="[
                                                    question.question_type_id !=
                                                        1
                                                        ? 'text-[var(--txtcolor)] text-sm rounded-lg'
                                                        : '',
                                                ]">
                                                    <multiple-choice-list v-if="question.question_type_id == 2"
                                                        :choices="question.choices" :setup="question.setup" />
                                                    <image-selection-list v-else-if="question.question_type_id == 3"
                                                        :choices="question.choices" :setup="question.setup" />
                                                    <single-textbox-list v-else-if="question.question_type_id == 4"
                                                        :setup="question.setup" />
                                                    <multiple-textbox-list v-else-if="question.question_type_id == 5"
                                                        :setup="question.setup" :items="question.items" />
                                                    <open-ended-list v-else-if="question.question_type_id == 6"
                                                        :setup="question.setup" />
                                                    <constant-sum-list v-else-if="question.question_type_id == 7"
                                                        :items="question.items" :setup="question.setup" />
                                                    <slider-list
                                                        v-else-if="question.question_type_id == 8 || question.question_type_id == 15"
                                                        :items="question.items" :values="question.values"
                                                        :setup="question.setup" :type="question.question_type_id" />
                                                    <ranking-list v-else-if="question.question_type_id == 9"
                                                        :setup="question.setup" :items="question.items" />
                                                    <star-rating-list v-else-if="question.question_type_id == 10"
                                                        :items="question.items" :setup="question.setup" />
                                                    <matrix-choice-list v-else-if="question.question_type_id == 11"
                                                        :columns="question.columns" :rows="question.rows"
                                                        :setup="question.setup" />
                                                    <matrix-multi-list v-else-if="question.question_type_id == 12"
                                                        :items="question.items" :rows="question.rows"
                                                        :setup="question.setup" />
                                                    <memory-test-desc-list v-else-if="question.question_type_id == 13"
                                                        :items="question.items" :setup="question.setup" />
                                                    <memory-test-comparison-list
                                                        v-else-if="question.question_type_id == 14"
                                                        :itemsA="question.item1" :itemsB="question.item2"
                                                        :setup="question.setup" />
                                                    <segmentation-list v-else-if="question.question_type_id == 16"
                                                        :items="question.items" :setup="question.setup"
                                                        :values="question.values" />
                                                    <pairwise-comparison-list
                                                        v-else-if="question.question_type_id == 19"
                                                        :items="question.items" :setup="question.setup" />
                                                    <best-worst-scale-list v-else-if="question.question_type_id == 20"
                                                        :items="question.items" :setup="question.setup"
                                                        :choices="question.choices" />
                                                    <bar-chart-list v-else-if="question.question_type_id == 21"
                                                        :items="question.items" :setup="question.setup" />
                                                </div>
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>
                        </div>
                        <!-- <div class="relative border-b-[1px] border-gray-100 ml-8 mr-8 -top-6 pt-3"></div> -->
                        <div
                            class="flex justify-left items-center text-xs text-slate-400 font-normal italic py-[12px] absolute left-[30px] bottom-[10px] gap-1 border-t-[1px] border-gray-200 w-[calc(100%-60px)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <div>Last modified <span v-if="question?.update_info?.name">by <strong>{{
                                question?.update_info?.name
                                        }}</strong></span> {{ differenceTime(question.updated_at) }}</div>
                        </div>

                    </div>
                    </template>
                </div>
            </div>

            <!-- <div id="slideover-container" class="w-[480px] h-full fixed inset-0 invisible z-[99] pt-[70px]">
                <div @click="toggleSlideover" id="slideover-bg"
                    class="w-full h-full duration-500 ease-out transition-all inset-0 absolute bg-gray-900 opacity-0 hidden poiner-events-none">
                </div>
                <div id="slideover"
                    class="w-[480px] pl-[130px] bg-white h-full absolute left-0 duration-300 ease-out transition-all translate-x-[-100%]">

                    <GalleryVue :showGallery="showGallery" />
                    <div @click="toggleSlideover"
                        class="absolute cursor-pointer text-gray-600 top-0 w-full h-10 flex place-content-end right-0 p-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </div>
                </div>
            </div> -->

            <!-- <div id="slideover-container-settings" class="w-full h-full fixed inset-0 invisible z-[999]">
                <div @click="toggleSlideoverSettings" id="slideover-bg-settings"
                    class="w-full h-full duration-500 ease-out transition-all inset-0 absolute bg-gray-900 opacity-0">
                </div>
                <div id="slideover-settings"
                    class="w-[65vw] bg-white h-full absolute right-0 duration-300 ease-out transition-all translate-x-full">

                    <Setting :questions="$page.props.questions" :showSetting="showSetting"/>

                    <div @click="toggleSlideoverSettings"
                        class="absolute cursor-pointer text-gray-600 top-0 w-full h-10 flex place-content-end right-0 p-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </div>
                </div>
            </div> -->

        </template>
        <template #modals>
            <add-edit-question-modal :modal="modals.question" @showModal="showModal" :questions_data="props.questions"
                :editedIdx="editedIdx" @close="closeModal('question', 'add_edit')" @scrollToBottom="scrollToBottom()" />

            <!-- <copy-question-modal :modal="modals.question" @close="closeModal('question', 'copy')"
                @scrollToBottom="scrollToBottom()" /> -->

            <copy-question-modal-2 :modal="modals.question" @close="closeModal('question', 'copy')"
                @scrollToBottom="scrollToBottom()" />

            <!-- <delete-question-modal :modal="modals.question" @close="closeModal('question', 'delete')" /> -->
            <delete-question-modal-2 :modal="modals.question" @close="closeModal('question', 'delete')" />

            <skip-logic-modal :questions="props.questions" :modal="modals.question"
                @close="closeModal('question', 'skip_logic')" />

            <!-- <move-question-modal :modal="modals.question" @close="closeModal('question', 'move_question')" /> -->
            <move-question-modal-2 :modal="modals.question" @close="closeModal('question', 'move_question')" />

            <!-- <save-as-template-modal :modal="modals.question" @close="closeModal('question', 'save_template')" /> -->
            <save-as-template-modal-2 :modal="modals.question" @close="closeModal('question', 'save_template')" />

            <select-template-modal :modal="modals.question" @close="closeModal('question', 'select_template')" />
        </template>
    </main-layout>

    <!-- <teleport to="body">
        <WarningModal :show="showDeleteQuestion" @close="showDeleteQuestion = false">
            <template #header>
                Delete Question
            </template>
            <template #content>
                Are you sure you want to delete <span class="font-semibold">Q{{
                    props.questions[questionToDeleteIdx]?.order }}</span>?
            </template>
            <template #footer>
                <button @click="deleteQuestion"
                    class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
                    type="button">
                    Delete
                </button>
                <button @click="showDeleteQuestion = false"
                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                    type="button">
                    Cancel
                </button>
            </template>
        </WarningModal>

    </teleport> -->

    <MultilingualQuestionnaire ref="$MultilingualQuestionnaire" />
</template>

<style scoped>
#myDIVxxx {
    scroll-behavior: smooth;
}

#myDIVxxx::-webkit-scrollbar {
    width: 5px;
}

#myDIVxxx::-webkit-scrollbar-track {
    background-color: transparent;
}

#myDIVxxx::-webkit-scrollbar-thumb {
    background-color: transparent;
    border-radius: 10px;
}

#myDIVxxx.is-scrolling::-webkit-scrollbar-track {
    background-color: transparent;
}

#myDIVxxx.is-scrolling::-webkit-scrollbar-thumb {
    background-color: #B6CDDC;
}

#myDIVxxx.is-scrolling::-webkit-scrollbar-thumb:hover {
    background: #829AAB;
}


.qImage :deep() img {
    margin-top: 1vmin;
    margin-bottom: 1vmin;
    height: 10rem;
}

.QQ {
    width: 8.5rem;
    border-bottom-right-radius: 1rem;
    font-size: 0.9rem;
}

.ACT {
    /* background:lightgrey; */
    height: 0.1rem;
    margin-right: 1rem;
}

.btnACT {
    border-radius: 3rem;
    margin-left: 0.3rem;
    height: 1.4rem;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

.cnt {
    margin-top: 0.2rem;
    /* background:#F6F8F915; */
    border: 2px solid #dce0e370;
    border-radius: 1rem;
    /* padding:1rem;
    padding-left:2.7rem; */
}

.answer_key {
    /* padding: 1rem;
    padding-left: 2.7rem; */
}

.badge {
    background: #e94f1d;
    color: white;
    border: none;
    right: 11rem;
}

@media (max-width: 768px) {
    .mobileDis {
        display: flex;
        flex-direction: inherit;
    }
}


.curveMe::after {
    content: "";
    margin-left: -50px;
    position: absolute;
    top: 10px;
    left: 0px;
    right: 0;
    transform: translate(100%, -50%);
    height: 110px;
    /* background: var(--mcolor); */
    border-radius: 50px;
    background-color: white;
    animation-timing-function: cubic-bezier(0.63, 0.01, 0.29, 1);
    animation-duration: 1s;

    /* border-color: var(--mcolor); */
    border: solid 2px var(--mcolor);
    border-bottom: none;
    border-right: none;
    border-top: none;
}
</style>






