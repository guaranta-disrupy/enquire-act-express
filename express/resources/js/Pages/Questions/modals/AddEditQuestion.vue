<script setup>

import icons from "@/qt_icons.js"
const qt_icons = icons.qt_icons

import Descriptive from "./types/Descriptive.vue";
import MultipleChoice from "./types/MultipleChoice.vue";
import ImageSelection from "./types/ImageSelection.vue";
import SingleTextbox from "./types/SingleTextbox.vue";
import MultipleTextbox from "./types/MultipleTextbox.vue";
import OpenEnded from "./types/OpenEnded.vue";
import ConstantSum from "./types/ConstantSum.vue";
import Slider from "./types/Slider.vue";
import ButtonRating from "./types/ButtonRating.vue";
import Ranking from "./types/Ranking.vue";
import StarRating from "./types/StarRating.vue";
import MatrixChoice from "./types/MatrixChoice.vue";
import MatrixMulti from "./types/MatrixMulti.vue";
import MemoryTestDesc from "./types/MemoryTestDesc.vue";
import MemoryTestComparison from "./types/MemoryTestComparison.vue";
import Segmentation from "./types/Segmentation.vue";
import VideoPlayer from "./types/VideoPlayer.vue";
import PairWiseComparison from "./types/PairWiseComparison.vue";
import BestWorstScale from "./types/BestWorstScale.vue";
import BestWorstScale2 from "./types/BestWorstScale2.vue";
import BarChart from "./types/BarChart.vue";
import Tooltip from "@/S4/Tooltip.vue";
import question from "/public/images/question.svg";
import TE3 from "@/S4/TE3.vue";
import TipTapEditor from "@/S4/TipTapEditor.vue";
import SingleSelect from '@/S4/SingleSelect.vue';
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import VueFormMultiselect from "@/S4/vueform/Multiselect.vue";
import JetLabel from "@/Jetstream/Label.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import S4Button from "@/S4/Button.vue";
import S4Input from "@/S4/Input.vue";
import DOMPurify from "dompurify";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import prism_editor from "../../../prism_editor";
import {
    faEdit,
    faSpinner,
    faSave,
    faCode,
} from "@fortawesome/free-solid-svg-icons";
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
// import CKEditor from "@ckeditor/ckeditor5-vue";
import QuestionPiping from "@/S4/QuestionPiping.vue";
import {
    onBeforeMount,
    ref,
    watch,
    reactive,
    inject,
    computed,
    onMounted,
    provide,
    nextTick
} from "vue";
import { usePage, useForm, router } from "@inertiajs/vue3";
import helpers from "@/helpers.js";
import UploadAdapterPlugin from "@/Functions/UploadAdapter";
import axios from 'axios';
import { AITranslateToast } from '@/composables/AITranslateToast.js';

const { highlighter, PrismEditor } = prism_editor();
const { assign, toast, val_exists } = helpers();
const page = usePage();
const { showTranslateToast } = AITranslateToast();
const $VariableName = ref(null)
provide('$VariableName', $VariableName);
let form = inject("add_edit_form");
const props = defineProps({
    modal: {
        default: {
            add_edit: false,
            method: "add",
            data: null,
        },
    },
    questions_data: [],
    editedIdx: {
        type: Number,
        default: null,
    },
    isUpdated: {
        type: Number,
        default: null,
    },
});
watch(() => props.modal.add_edit, (state) => {
    if (state) {
        //let variable = usePage().props.questionnaire.setup.variable
        let variable = "Q"
        let numStart = parseInt(usePage().props.questionnaire.setup.variable_start - 1)
        if (props.modal.method == "add") {
            let order = parseInt(usePage().props.questions.length) + 1
            // alert(variable+(order + numStart))
            $VariableName.value = variable + (order + numStart)
        } else {
            let order = parseInt(usePage().props.questions[props.editedIdx].order)
            //alert(variable+(order + numStart))
            $VariableName.value = variable + (order + numStart)
        }

    }
}, {
    deep: true
});



const _questions = ref([]);

const all_language = ref({
    language: [],
});


const pipe_modal = ref({
    show: false,
    target: null,
    user: null
})

const selectedPipingOption = ref(null);
// const pipingSelect = ref([
//     { text: "Question Only", value: "QO" },
//     { text: "Question with Values", value: "QV" },
// ]);

const language_id = ref(1);



const mc_warn = ref(null);
const is_warn = ref(null);
const mt_warn = ref(null);
const rs_warn = ref(null);
const br_warn = ref(null);
const sg_warn = ref(null);
const vp_warn = ref(null);

const emit = defineEmits(["showModal", "close", "scrollToBottom"]);

const faIcon = ref({
    save: faSave,
    spinner: faSpinner,
    edit: faEdit,
    code: faCode,
});

const currentTemplate = ref();
const testValue = ref("");

const templateShow = ref(false);
const template_filters = ref({
    text: "",
    qtype: null,
})
const template_qtype = computed(() => {

    let list = JSON.parse(JSON.stringify(question_types.values))
    list.unshift({
        text: 'All question types',
        value: null
    })

    return list
})

// watch(()=>form.question_type_id,(change)=>{
//     template_filters.value.qtype = change
// })



watch(templateShow, (m) => {
    if (m) {
        document.querySelectorAll(".donModal").forEach((modal) => {
            modal.dispatchEvent(new CustomEvent("sidebar", { detail: { open: true, target: 'template' } }))
        })
    } else {
        document.querySelectorAll(".donModal").forEach((modal) => {
            modal.dispatchEvent(new CustomEvent("sidebar", { detail: { open: false, target: 'template' } }))
        })
    }
})


const pipingShow = ref(false);

const editorConfig = ref({
    extraPlugins: [UploadAdapterPlugin],
});

const formTemplate = useForm(
    {
        q_data: {},
    },
    {
        resetOnSuccess: true,
    }
);



const category = ref("Description");
const dataTypeList = ["nominal", "binary", "ordinal", "interval", "ratio","continuous","discrete","qualitative","derived"];

const selected_order = ref(null);
const selected_question = ref(null);
const addedTemplate = ref(null);
const valueList = ref(null);

const selected_templates = () => {
    // updateCategoryList(form.question_type_id)
    // category.value = usePage().props.question_templates[form.question_type_id].text
    //console.log("temp",usePage().props.question_templates)
    let list = usePage().props.question_templates.filter(
        (v) => v.question_language_id == language_id.value
    );
    // console.log("list", list);
    if (template_filters.value.text != "") {
        list = list.filter(
            (v) => v.name.toLowerCase().includes(template_filters.value.text.toLowerCase())
        );
    }
    if (!template_filters.value.qtype) {
        return list.sort((a, b) => a.name.localeCompare(b.name))
    } else {
        return list.filter(
            (v) => v.question_type_id == template_filters.value.qtype
        ).sort((a, b) => a.name.localeCompare(b.name))
    }
};

const question_types = reactive({
    values: [],
    categories: [],
});

const sanitizedQuestion = (question) => {
    return DOMPurify.sanitize(question, { ALLOWED_TAGS: [] });
};

const selectedValue = ref(null);
const editor = ref(ClassicEditor);
//const ckeditor = CKEditor.component;
const enableEditor = ref(true);
const editorType = ref("question");

// Track the last modal state to detect when it opens
let lastModalAddEdit = false;

watch(
    () => props.modal,
    (modal, oldModal) => {
        //assign values to edit
        // Only assign when modal first opens (add_edit changes from false to true)
        // Don't reassign if modal is already open to prevent overwriting user edits
        const wasClosed = !lastModalAddEdit;
        const isNowOpen = modal.add_edit == true;
        const isModalOpening = wasClosed && isNowOpen;

        // Update the tracked state
        lastModalAddEdit = modal.add_edit;

        // Reset flag when modal closes
        if (!isNowOpen) {
            lastModalAddEdit = false;
        }

        if (modal.method == "edit" && isModalOpening) {
            for (const key in form) {
                // prevent assigning erros from ths.form
                if (Object.hasOwnProperty.call(modal.data, key)) {
                    form[key] = assign(modal.data[key]);
                }
            }
            // console.log('watch IF')
            // When current language is not original, display translated content in the editor and lists
            const urlParams = new URLSearchParams(window.location.search);
            const currentLang = urlParams.get('langc') || (page.props?.language?.code || ' ');
            const isOriginalLang = currentLang === ' ' || !currentLang || currentLang.trim() === '';
            if (currentLang && !isOriginalLang) {
                try {
                    // Chart Title
                    const tChartTitle = modal.data?.setup?.language?.[currentLang]?.chartTitle;
                    if (tChartTitle) form.setup.chartTitle = tChartTitle;

                    // Question text
                    const tQuestion = modal.data?.setup?.language?.[currentLang]?.question;
                    if (tQuestion) form.question = tQuestion;
                    // Choices
                    if (Array.isArray(form.choices)) {
                        form.choices = form.choices.map((c) => {
                            const tName = c?.language?.[currentLang]?.name;
                            if (tName) return { ...c, name: tName };
                            return c;
                        });
                    }
                    // Items
                    if (Array.isArray(form.items)) {
                        form.items = form.items.map((i) => {
                            const t = i?.language?.[currentLang];
                            if (t) {
                                return {
                                    ...i,
                                    name: t.name ?? i.name,
                                    statementA: t.statementA ?? i.statementA,
                                    statementB: t.statementB ?? i.statementB,
                                };
                            }
                            return i;
                        });
                    }
                    // Values
                    if (Array.isArray(form.values)) {
                        form.values = form.values.map((v) => {
                            const tName = v?.language?.[currentLang]?.name;
                            if (tName) return { ...v, name: tName };
                            return v;
                        });
                    }
                    // Labels/header/footer
                    const tLabels = modal.data?.setup?.language?.[currentLang]?.labels;
                    if (tLabels) {
                        form.setup.labels = form.setup.labels || {};
                        form.setup.labels.statementA = tLabels.statementA ?? form.setup.labels.statementA;
                        form.setup.labels.statementB = tLabels.statementB ?? form.setup.labels.statementB;
                    }
                    const tHeader = modal.data?.setup?.language?.[currentLang]?.header;
                    if (tHeader) form.setup.header = tHeader;
                    const tFooter = modal.data?.setup?.language?.[currentLang]?.footer;
                    if (tFooter) form.setup.footer = tFooter;
                } catch (e) {
                    // fail silently
                }
            }
            updateCategoryList(form.question_type_id);
        } else {
            updateCategoryList(1);
        }
    },
    { deep: true }
);

watch(
    () => language_id,
    (id) => {
        currentTemplate.value = null;
    },
    { deep: true }
);


// watch(() =>
//     form.question_type_id,
//     (change) => {
//         if (change && !templateShow) {
//             form.choices = []
//             form.items = []
//         }
//         console.log("testetsetse>>>>>>", props.modal)
//         // console.log("gdbdfvbdf", props.editedIdx)
//         // console.log("QUESTIONSS", _questions.value)

//     },
//     { deep: true }
// )

watch(
    () => enableEditor.value,
    (change) => {
        if (selected_question.value && selected_order.value) {
            // Determine the correct replacement based on the editor's enabled/disabled state
            let newContent = form.question;

            if (change === false) {
                // Replace selected_question with selected_order when editor is disabled
                newContent = newContent.replace(
                    new RegExp(escapeRegExp(selected_question.value[0]), "g"),
                    selected_order.value[0]
                );
            } else {
                // Replace selected_order with selected_question when editor is enabled
                newContent = newContent.replace(
                    new RegExp(escapeRegExp(selected_order.value[0]), "g"),
                    selected_question.value[0]
                );
            }

            form.question = newContent; // Update the editor content
        }
        // Check if form.question is not null or undefined
        if (form.question) {
            // Get the current content of the question
            let newContent = form.question;

            // Define the regular expression to match the span with dynamic text
            const customElementRegex =
                /<span contenteditable="false" id="custom" class="non-movable bg-gray-100 border border-gray-300 border-l-8 border-r-8 px-2 py-1 font-semibold inline-block whitespace-nowrap cursor-default select-none text-gray-800 relative rounded">\s*<span class="absolute inset-0 border-1 border-gray-500 rounded-none pointer-events-none"><\/span>\s*(.*?)\s*<\/span>/g;

            if (!change) {
                // Replace the custom element with <b>{{dynamic text}}</b> when editor is disabled
                newContent = newContent.replace(
                    customElementRegex,
                    "<b>$1</b>"
                );
            } else {
                // Replace <b>{{dynamic text}}</b> back with the original custom element when editor is enabled
                newContent = newContent.replace(
                    /<b>(\[PIPE:.*?\]|\[QUERY:.*?\])<\/b>/g,
                    `<span contenteditable="false" id="custom" class="non-movable bg-gray-100 border border-gray-300 border-l-8 border-r-8 px-2 py-1 font-semibold inline-block whitespace-nowrap cursor-default select-none text-gray-800 relative rounded">
              <span class="absolute inset-0 border-1 border-gray-500 rounded-none pointer-events-none"></span>
              $1
           </span>`
                );
            }

            // Update the form content
            form.question = newContent;
        }
    },
    { deep: true }
);

// Utility function to escape special characters for RegExp
function escapeRegExp(string) {
    return string.replace(/[.*+?^=!:${}()|\[\]\/\\]/g, "\\$&");
}

// watch(() =>                 [PIPE:VB]
//     templateShow.value,
//     (x) => {
//         console.log('templateShow',x)
//         if(x) updateCategoryList(form.question_type_id)
//         else updateCategoryList(1)
//     }
// )

onBeforeMount(() => {
    initialized();
    // console.log("BEFOREQWERTY>> ", props.isUpdated)
});

onMounted(() => {

    // console.log("QWERTY>> ", props.isUpdated)
    if (props.editedIdx == null) [(_questions.value = props.questions_data)];
    if (form.setup && form.setup.hasOwnProperty("piping")) {
        if (form.setup.piping != null) {
            form.question = form.setup.piping.question;
        }
    }
    selectedPipingOption.value = "QO";
    enableEditor.value = true;
    if (
        form.question &&
        (form.question.includes("<b>[PIPE:") ||
            form.question.includes("<b>[QUERY:"))
    ) {
        const customElementRegex = /<b>\[(PIPE:|QUERY:)(.*?)\]/g;
        form.question = form.question.replace(
            customElementRegex,
            (match, type, content) => {
                return `<span contenteditable="false" id="custom" class="non-movable bg-gray-100 border border-gray-300 border-l-8 border-r-8 px-2 py-1 font-semibold inline-block whitespace-nowrap cursor-default select-none text-gray-800 relative rounded">
                        <span class="absolute inset-0 border-1 border-gray-500 rounded-none pointer-events-none"></span>
                        ${content}
                    </span>`;
            }
        );
    }

    // if (props.modal.method == 'add') {
    //     _questions.value = props.questions_data
    // }
    // console.log("QWERTY>> ", props.questions_data)
});

watch(
    () => selectedValue.value,
    (change) => {
        let vals = selectedValue.value.split(",");
        // console.log("TRIGGERED", props.questions_data)
        // Slice the questions data based on the updated index
        if (change) {
            selected_order.value = [vals[0]];

            // Store only the first 10 characters and append "..." if the text exceeds 10 characters
            selected_question.value = [vals[1]];
        }
        // console.log("test >>", selected_question.value);
    }
);

watch(
    () => pipingShow.value,
    (change) => {
        // console.log("TRIGGERED", props.questions_data)
        // Slice the questions data based on the updated index
        if (change && props.modal.method == "add") {
            _questions.value = props.questions_data;
        }
    }
);

watch(
    () => props.editedIdx,
    (newIdx) => {
        // Slice the questions data based on the updated index
        if (props.questions_data && Array.isArray(props.questions_data)) {
            _questions.value = props.questions_data.slice(0, newIdx);
        }
    }
);


const initialized = () => {
    usePage().props.question_types.forEach((type) => {
        // type lists
        question_types.values.push({
            icon: qt_icons[type.id],
            value: type.id,
            text: type.name,
            category: type.category, //for filtering lists
        });
        // category list
        if (
            !val_exists(question_types.categories, {
                key: "value",
                value: type.category,
            })
        ) {
            question_types.categories.push({

                value: type.category,
                text: type.category,
            });
        }
    });

    usePage().props.language_all.forEach((lang) => {
        all_language.value.language.push({
            value: lang.id,
            text: lang.name,
        });
    });
};

const updateSetup = () => {

    // console.log('update setup')
    //for edit old questions
    if ("boxStyle" in form.setup == false) {
        form.setup["boxStyle"] = {
            size: "md",
            // "max-width": "100vmin",
        };
        form.setup["style"] = {
            "max-width": "",
            width: "100%",
            height: "200px",
        };
        //moved from descriptive onMounted
    }

    // console.log(form,"add styles for old question")
    var default_setup = usePage().props.question_types.find(
        (question_type) => question_type.id == form.question_type_id
    ).setup;
    // console.log(default_setup)
    for (const key in default_setup) {
        //add setting if not exist
        if (!Object.hasOwnProperty.call(form.setup, key)) {
            form.setup[key] = default_setup[key];
        } else {
            if (
                typeof form.setup[key] != "number" ||
                typeof form.setup[key] != "string"
            ) {
                for (const skey in default_setup[key]) {
                    // console.log($questionvar.add_edit.setup[key], skey,key
                    try {
                        if (!Object.hasOwnProperty.call(form.setup[key], skey)) {
                            form.setup[key][skey] = default_setup[key][skey];
                        }
                    } catch (e) {
                        console.log('error', e)
                    }
                }
            }
        }
    }

    // console.log(form.setup)
};

const updateCategoryList = (type_id = -1) => {
    if (type_id == -1) {
        form.question_type_id = usePage().props.question_types.find(
            (type) => type.category == category.value
        ).id;

    } else {
        // console.log('Update Category List ELSE')
        category.value = usePage().props.question_types.find(
            (type) => type.id == type_id
        ).category;
    }
    //update setup
    updateSetup();
};

const showModal = (table, modal, method, index = -1) => {
    emit("showModal", table, modal, method, index);
};
const close = () => {
    currentTemplate.value = null;
    templateShow.value = false;
    pipingShow.value = false;
    addedTemplate.value = 0;
    form.reset();
    lastModalAddEdit = false; // Reset tracked state when modal closes
    emit("close");
    if (props.modal.method == "add") {
        emit("scrollToBottom");
    }
};
const close2 = () => {

    currentTemplate.value = null;
    templateShow.value = false;
    addedTemplate.value = 0;
    pipingShow.value = false;
    pipe_modal.value.show = false
    form.reset();
    lastModalAddEdit = false; // Reset tracked state when modal closes
    emit("close");

};
const chartTitleInput = ref(null);
const questionInput = ref(null);

const AddEditQuestion = () => {
    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ location: 'AddEditQuestion.vue:626', message: 'AddEditQuestion called', data: { method: props.modal.method, questionTypeId: form.question_type_id, choicesLength: form.choices?.length, formProcessing: form.processing, isWarnExists: !!is_warn?.value, warningCond: is_warn?.value?.warning?.cond, warningSrcIdx: is_warn?.value?.warning?.src_idx }, timestamp: Date.now(), sessionId: 'debug-session', runId: 'run1', hypothesisId: 'C' }) }).catch(() => { });
    // #endregion

    form.errors.chartTitle = null;
    form.errors.question = null;

    // Clear segmentation error
    if (sg_warn && sg_warn.value) {
        sg_warn.value.segmentationError = "";
    }

    // Clear image selection validation errors
    if (is_warn && is_warn.value) {
        is_warn.value.showValidationErrors = false;
    }

    // Clear multiple choice validation errors
    if (mc_warn && mc_warn.value) {
        mc_warn.value.showValidationErrors = false;
    }

    // Clear multiple textbox validation errors
    if (mt_warn && mt_warn.value) {
        mt_warn.value.showValidationErrors = false;
    }

    // Clear video player validation errors
    if (vp_warn && vp_warn.value) {
        vp_warn.value.videoSourceError = "";
    }

    let firstErrorRef = null;
    // Validate chartTitle
    if (!form.setup.chartTitle || form.setup.chartTitle.trim() === "") {
        form.errors.chartTitle = "The title field is required.";
        firstErrorRef = chartTitleInput;
    }

    if (!form.question || form.question.trim() === "") {
        form.errors.question = "The question field is required.";
        if (!firstErrorRef) firstErrorRef = questionInput;
    }

    // Validate multiple choice choices (question type 2) - only when save button is clicked
    let hasEmptyMultipleChoices = false;
    if (form.question_type_id === 2) {
        if (!form.choices || !Array.isArray(form.choices) || form.choices.length === 0) {
            hasEmptyMultipleChoices = true;
            // Trigger validation errors display and scroll to first empty
            if (mc_warn && mc_warn.value) {
                mc_warn.value.showValidationErrors = true;
                mc_warn.value.scrollToFirstEmptyChoice();
            }
        } else {
            // Check for empty choices
            const emptyChoices = form.choices.filter(choice => {
                if (!choice || !choice.name) return true;
                const cleanName = choice.name.replace(/<[^>]*>/g, '').trim();
                return cleanName === '' || cleanName === '<br>' || cleanName === '<p><br></p>' || choice.name === '<br>' || choice.name === '<p><br></p>';
            });

            if (emptyChoices.length > 0) {
                hasEmptyMultipleChoices = true;
                // Trigger validation errors display and scroll to first empty
                if (mc_warn && mc_warn.value) {
                    mc_warn.value.showValidationErrors = true;
                    mc_warn.value.scrollToFirstEmptyChoice();
                }
            }
        }
    }

    // Validate multiple textbox choices (question type 5) - only when save button is clicked
    let hasEmptyMultipleTextboxChoices = false;
    if (form.question_type_id === 5 && mt_warn && mt_warn.value) {
        const validationResult = mt_warn.value.validateRequiredFields?.();
        if (validationResult?.hasError) {
            hasEmptyMultipleTextboxChoices = true;
            mt_warn.value.showValidationErrors = true;
            mt_warn.value.scrollToFirstEmptyChoice?.();
        }
    }

    // Validate image selection choices (question type 3) - only when save button is clicked
    let hasEmptyImageChoices = false;
    if (form.question_type_id === 3) {
        // #region agent log
        fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ location: 'AddEditQuestion.vue:693', message: 'Image selection validation start', data: { choicesLength: form.choices?.length, choicesIsArray: Array.isArray(form.choices), choices: form.choices?.map(c => ({ name: c?.name, src: c?.src })) }, timestamp: Date.now(), sessionId: 'debug-session', runId: 'run1', hypothesisId: 'D' }) }).catch(() => { });
        // #endregion
        if (!form.choices || !Array.isArray(form.choices) || form.choices.length === 0) {
            hasEmptyImageChoices = true;
            // #region agent log
            fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ location: 'AddEditQuestion.vue:695', message: 'Image selection validation - no choices', data: { hasEmptyImageChoices: true }, timestamp: Date.now(), sessionId: 'debug-session', runId: 'run1', hypothesisId: 'D' }) }).catch(() => { });
            // #endregion
            // Trigger validation errors display and scroll to first empty
            if (is_warn && is_warn.value) {
                is_warn.value.showValidationErrors = true;
                is_warn.value.scrollToFirstEmptyChoice();
            }
        } else {
            // Filter out null/undefined/empty objects before validation
            // This prevents validation from failing due to corrupted array entries
            // IMPORTANT: Preserve choices that have translation data (language property) or index
            // to maintain AI translation compatibility
            const validChoices = form.choices.filter(choice => {
                // Remove null, undefined, or completely empty objects
                if (!choice || typeof choice !== 'object') return false;
                // Check for meaningful properties
                const hasName = choice.name !== undefined && choice.name !== null;
                const hasSrc = choice.src !== undefined && choice.src !== null;
                const hasIndex = choice.index !== undefined && choice.index !== null;
                const hasLanguage = choice.language && typeof choice.language === 'object' && Object.keys(choice.language).length > 0;
                const hasCUUID = choice.CUUID !== undefined && choice.CUUID !== null;
                // Keep choice if it has at least one meaningful property
                // This preserves choices with translation data even if name/src are temporarily empty
                return hasName || hasSrc || hasIndex || hasLanguage || hasCUUID;
            });

            // Check for empty choices (choices with empty name)
            const emptyChoices = validChoices.filter(choice => {
                if (!choice || !choice.name) return true;
                const cleanName = choice.name.replace(/<[^>]*>/g, '').trim();
                return cleanName === '' || cleanName === '<br>' || cleanName === '<p><br></p>' || choice.name === '<br>' || choice.name === '<p><br></p>';
            });

            // #region agent log
            fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ location: 'AddEditQuestion.vue:703', message: 'Image selection validation - empty choices check', data: { totalChoices: form.choices.length, validChoices: validChoices.length, emptyChoicesCount: emptyChoices.length, hasEmptyImageChoices: emptyChoices.length > 0 }, timestamp: Date.now(), sessionId: 'debug-session', runId: 'run1', hypothesisId: 'D' }) }).catch(() => { });
            // #endregion

            if (emptyChoices.length > 0) {
                hasEmptyImageChoices = true;
                // Trigger validation errors display and scroll to first empty
                if (is_warn && is_warn.value) {
                    is_warn.value.showValidationErrors = true;
                    is_warn.value.scrollToFirstEmptyChoice();
                }
            }
        }
    }

    // Validate segmentation values (question type 16) - only when save button is clicked
    if (form.question_type_id === 16) {

        if (!form.values || !Array.isArray(form.values)) {
            if (sg_warn && sg_warn.value) {
                sg_warn.value.segmentationError = "Segmentation requires at least 3 values.";
            }
            // Don't set firstErrorRef for segmentation since we want to show the error message
        } else {
            // Filter out empty values
            const validValues = form.values.filter(value => {
                if (!value || !value.name) return false;
                const cleanName = value.name.replace(/<[^>]*>/g, '').trim();
                return cleanName !== '' && cleanName !== '<br>' && cleanName !== '<p><br></p>';
            });

            if (validValues.length < 3) {
                if (sg_warn && sg_warn.value) {
                    sg_warn.value.segmentationError = `Segmentation requires at least 3 values with content. Currently have ${validValues.length} valid values.`;
                }
                // Don't set firstErrorRef for segmentation since we want to show the error message
            } else {
                if (sg_warn && sg_warn.value) {
                    sg_warn.value.segmentationError = ""; // Clear error if validation passes
                }
            }
        }
    }

    // Validate video player video source (question type 18) - only when save button is clicked
    if (form.question_type_id === 18) {
        const videoSource = form.setup?.layout_video?.src;
        if (!videoSource || videoSource.trim() === "") {
            if (vp_warn && vp_warn.value) {
                vp_warn.value.videoSourceError = "Video source is required. Please select or enter a video source before saving.";
            }
        } else {
            if (vp_warn && vp_warn.value) {
                vp_warn.value.videoSourceError = ""; // Clear error if validation passes
            }
        }
    }

    if (firstErrorRef) {
        nextTick(() => {
            firstErrorRef.value?.$el?.scrollIntoView({ behavior: "smooth", block: "center" });
            firstErrorRef.value?.$el?.focus?.();
        });
        return; // Stop the save process
    }

    // Check for segmentation errors and stop save process
    if (form.question_type_id === 16 && sg_warn?.value?.segmentationError) {
        return; // Stop the save process but keep the error message visible
    }

    // Check for multiple choice errors and stop save process
    if (form.question_type_id === 2 && hasEmptyMultipleChoices) {
        return; // Stop the save process but keep the individual error messages visible
    }

    // Check for multiple textbox errors and stop save process
    if (form.question_type_id === 5 && hasEmptyMultipleTextboxChoices) {
        return; // Stop the save process but keep the individual error messages visible
    }

    // Check for image selection errors and stop save process
    if (form.question_type_id === 3 && hasEmptyImageChoices) {
        // #region agent log
        fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ location: 'AddEditQuestion.vue:782', message: 'AddEditQuestion blocked by empty image choices', data: { hasEmptyImageChoices: true }, timestamp: Date.now(), sessionId: 'debug-session', runId: 'run1', hypothesisId: 'D' }) }).catch(() => { });
        // #endregion
        return; // Stop the save process but keep the individual error messages visible
    }

    // Check for video player errors and stop save process
    if (form.question_type_id === 18 && vp_warn?.value?.videoSourceError) {
        // #region agent log
        fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ location: 'AddEditQuestion.vue:787', message: 'AddEditQuestion blocked by video player error', data: { videoSourceError: vp_warn?.value?.videoSourceError }, timestamp: Date.now(), sessionId: 'debug-session', runId: 'run1', hypothesisId: 'E' }) }).catch(() => { });
        // #endregion
        return; // Stop the save process but keep the error message visible
    }

    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ location: 'AddEditQuestion.vue:791', message: 'AddEditQuestion passed all validation checks', data: { method: props.modal.method, questionTypeId: form.question_type_id, hasEmptyImageChoices, hasEmptyMultipleChoices, firstErrorRef: !!firstErrorRef }, timestamp: Date.now(), sessionId: 'debug-session', runId: 'run1', hypothesisId: 'E' }) }).catch(() => { });
    // #endregion



    pipe_modal.value.show = false
    if (selected_question.value == null) {
        form.setup.piping = selected_question.value; // You can set a fallback value here if needed
    } else {
        form.setup.piping = {
            question: selected_question.value[0].replace(/\\/, ""),
            order: selected_order.value[0].replace(/\\/, ""),
        };
    }

    if (selected_question.value != null && selected_order.value != null) {
        if (
            form.question != null &&
            form.question.includes(selected_question.value[0])
        ) {
            let newContent = form.question.replace(
                new RegExp(escapeRegExp(selected_question.value[0]), "g"),
                selected_order.value[0]
            );
            form.question = newContent;
        } else {
            form.question = form.question;
        }
    }

    if (props.modal.method == "add") {
        var web_route = "questions.store";
        var params = [usePage().props.questionnaire.code];
    } else {
        var web_route = "questions.update";
        var params = [usePage().props.questionnaire.code, props.modal.data.id];
    }

    // Transform payload before submit to preserve previous grid values when Edit Columns is disabled
    form.transform((data) => {
        try {
            const payload = JSON.parse(JSON.stringify(data));
            // Determine current language code (from URL ?langc= or page props)
            const urlParams = new URLSearchParams(window.location.search);
            const currentLang = urlParams.get('langc') || (page.props?.language?.code || ' ');
            const isOriginalLang = currentLang === ' ' || !currentLang || currentLang.trim() === '';

            // If not original language, route edited content into language-specific fields
            if (currentLang && !isOriginalLang) {
                // Ensure setup.language structure
                payload.setup = payload.setup || {};
                payload.setup.language = payload.setup.language || {};
                payload.setup.language[currentLang] = payload.setup.language[currentLang] || {};

                // Chart Title - store in language-specific field and preserve original
                if (payload.setup.chartTitle !== undefined) {
                    const translatedChartTitle = payload.setup.chartTitle;
                    // Store translated chartTitle in language-specific field
                    if (translatedChartTitle && typeof translatedChartTitle === 'string') {
                        payload.setup.language[currentLang].chartTitle = translatedChartTitle;
                    }
                    // Always preserve the original chartTitle from existing data
                    if (props.modal?.data?.setup?.chartTitle !== undefined) {
                        payload.setup.chartTitle = props.modal.data.setup.chartTitle;
                    } else {
                        // If no original exists, remove chartTitle from setup to avoid overwriting
                        delete payload.setup.chartTitle;
                    }
                }

                // Question text
                if (payload.question && typeof payload.question === 'string') {
                    payload.setup.language[currentLang].question = payload.question;
                }

                // Header/footer (for types that use them)
                if (payload.setup.header) {
                    payload.setup.language[currentLang].header = payload.setup.language[currentLang].header || payload.setup.header;
                }
                if (payload.setup.footer) {
                    payload.setup.language[currentLang].footer = payload.setup.language[currentLang].footer || payload.setup.footer;
                }

                // Labels (Segmentation and similar: statementA/statementB)
                if (payload.setup.labels) {
                    payload.setup.language[currentLang].labels = payload.setup.language[currentLang].labels || {};
                    if (payload.setup.labels.statementA) {
                        payload.setup.language[currentLang].labels.statementA = payload.setup.labels.statementA;
                    }
                    if (payload.setup.labels.statementB) {
                        payload.setup.language[currentLang].labels.statementB = payload.setup.labels.statementB;
                    }
                }

                // Choices translation
                if (Array.isArray(payload.choices)) {
                    // Filter out null/undefined/empty objects before processing translations
                    // This prevents translation errors while preserving valid choices with translation data
                    payload.choices = payload.choices
                        .filter(choice => {
                            if (!choice || typeof choice !== 'object') return false;
                            // Keep choices that have at least one meaningful property
                            const hasName = choice.name !== undefined && choice.name !== null;
                            const hasSrc = choice.src !== undefined && choice.src !== null;
                            const hasIndex = choice.index !== undefined && choice.index !== null;
                            const hasLanguage = choice.language && typeof choice.language === 'object' && Object.keys(choice.language).length > 0;
                            const hasCUUID = choice.CUUID !== undefined && choice.CUUID !== null;
                            return hasName || hasSrc || hasIndex || hasLanguage || hasCUUID;
                        })
                        .map((choice) => {
                            const c = choice || {};
                            c.language = c.language || {};
                            if (c.name) {
                                c.language[currentLang] = c.language[currentLang] || {};
                                c.language[currentLang].name = c.name;
                            }
                            // Preserve base name by restoring from original when available
                            try {
                                if (props.modal?.data?.choices) {
                                    const orig = props.modal.data.choices.find((o) => (o.index ?? null) === (c.index ?? null));
                                    if (orig && orig.name) c.name = orig.name;
                                }
                            } catch (e) { }
                            return c;
                        });
                }

                // Items translation (covers many types)
                if (Array.isArray(payload.items)) {
                    payload.items = payload.items.map((item) => {
                        const i = item || {};
                        i.language = i.language || {};
                        // name based items
                        if (i.name) {
                            i.language[currentLang] = i.language[currentLang] || {};
                            i.language[currentLang].name = i.name;
                        }
                        // statementA/statementB based items (pairwise/segmentation)
                        if (i.statementA || i.statementB) {
                            i.language[currentLang] = i.language[currentLang] || {};
                            if (i.statementA) i.language[currentLang].statementA = i.statementA;
                            if (i.statementB) i.language[currentLang].statementB = i.statementB;
                        }
                        // Restore base fields from original when available
                        try {
                            if (props.modal?.data?.items) {
                                const orig = props.modal.data.items.find((o) => (o.index ?? null) === (i.index ?? null));
                                if (orig) {
                                    if (orig.name) i.name = orig.name;
                                    if (orig.statementA) i.statementA = orig.statementA;
                                    if (orig.statementB) i.statementB = orig.statementB;
                                }
                            }
                        } catch (e) { }
                        return i;
                    });
                }

                // Values translation (slider/button rating/segmentation)
                if (Array.isArray(payload.values)) {
                    payload.values = payload.values.map((val) => {
                        const v = val || {};
                        v.language = v.language || {};
                        if (v.name) {
                            v.language[currentLang] = v.language[currentLang] || {};
                            v.language[currentLang].name = v.name;
                        }
                        // Restore base name from original when available
                        try {
                            if (props.modal?.data?.values) {
                                const orig = props.modal.data.values.find((o) => (o.index ?? null) === (v.index ?? null));
                                if (orig && orig.name) v.name = orig.name;
                            }
                        } catch (e) { }
                        return v;
                    });
                }
                // For non-EN, avoid overwriting base 'question' string; restore original if available
                try {
                    if (props.modal?.data?.question) {
                        payload.question = props.modal.data.question;
                    }
                } catch (e) { }
            } else {
                // When editing in original language (empty string), preserve existing language translations
                // to prevent overwriting other language translations when updating the original question
                try {
                    // Merge the existing setup with the new one, preserving language translations
                    if (props.modal?.data?.setup) {
                        const originalSetup = JSON.parse(JSON.stringify(props.modal.data.setup));
                        const newSetup = payload.setup || {};

                        // Preserve all language-specific chartTitles when updating original
                        const preservedLanguage = originalSetup.language || {};

                        // Merge: keep new setup properties but preserve language from original
                        payload.setup = {
                            ...originalSetup,  // Start with original setup (includes language)
                            ...newSetup,       // Override with new setup values (including chartTitle)
                            language: preservedLanguage  // Explicitly preserve all language-specific data including chartTitles
                        };
                    }
                } catch (e) {
                    console.error('Error preserving language structure in original language:', e);
                }
            }

            const ts = payload?.setup?.textSelection;
            // Multiple Choice = 2
            if (payload?.question_type_id === 2 && ts) {
                // If Edit Columns is disabled, avoid sending cleared values
                if (ts.layout_switch === false || ts.layout_switch === 0) {
                    delete ts.grid;
                    delete ts.grid_tab;
                    delete ts.grid_mobile;
                }
            }
            return payload;
        } catch (e) {
            return data; // Fallback: send original data if transform fails
        }
    });

    // #region agent log
    fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ location: 'AddEditQuestion.vue:998', message: 'Form post called', data: { web_route, params, choicesLength: form.choices?.length, choices: form.choices?.map(c => ({ name: c?.name, src: c?.src })) }, timestamp: Date.now(), sessionId: 'debug-session', runId: 'run1', hypothesisId: 'E' }) }).catch(() => { });
    // #endregion

    form.post(route(web_route, params), {
        preserveScroll: true,
        onSuccess: (page) => {
            // #region agent log
            fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ location: 'AddEditQuestion.vue:1000', message: 'Form post onSuccess', data: { method: props.modal.method }, timestamp: Date.now(), sessionId: 'debug-session', runId: 'run1', hypothesisId: 'E' }) }).catch(() => { });
            // #endregion
            const isNewQuestion = props.modal.method == "add";
            const questionnaireCode = usePage().props.questionnaire.code;

            toast.success(
                `Question ${isNewQuestion ? "added" : "updated"}`,
                {
                    timeout: 2000,
                }
            );

            if (isNewQuestion) {
                router.reload({
                    only: ['questions'],
                    preserveScroll: true,
                    onSuccess: () => {
                        const questions = usePage().props.questions || [];
                        if (questions.length > 0) {
                            const latestQuestion = questions[questions.length - 1];
                            showTranslateToast(questionnaireCode, latestQuestion.id, latestQuestion.order, 'added');
                        }
                    }
                });
            } else {
                const updatedQuestionId = props.modal.data?.id;
                if (updatedQuestionId) {
                    router.reload({
                        only: ['questions'],
                        preserveScroll: true,
                        onSuccess: () => {
                            const questions = usePage().props.questions || [];
                            const updatedQuestion = questions.find(q => q.id === updatedQuestionId);
                            if (updatedQuestion) {
                                showTranslateToast(questionnaireCode, updatedQuestion.id, updatedQuestion.order, 'updated');
                            }
                        }
                    });
                }
            }

            close();
        },
        onError: (errors) => {
            // #region agent log
            fetch('http://127.0.0.1:7242/ingest/28b4f211-6c06-4fe1-babc-6304bf84f851', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ location: 'AddEditQuestion.vue:1042', message: 'Form post onError', data: { errors: Object.keys(errors || {}), formErrors: Object.keys(form.errors || {}) }, timestamp: Date.now(), sessionId: 'debug-session', runId: 'run1', hypothesisId: 'E' }) }).catch(() => { });
            // #endregion
        },
        onFinish: () => {
            //code
        },
    });
    addedTemplate.value = 0;
    // _questions.value = []
};

const selectTemplate = (template, index) => {
    updateCategoryList(template.question_type_id);
    // console.log('template selected', template)
    // console.log(template.question_type_id);
    form.question_type_id = template.question_type_id;
    form.question = template.question;
    form.choices = template.choices || [];
    form.setup = template.setup;
    form.rows = template.rows;
    form.items = template.items;
    form.item1 = template.item1;
    form.item2 = template.item2;
    form.values = template.values;
    form.setup.chartTitle = template.name;
    currentTemplate.value = index;
    addedTemplate.value++;
};
const showTemplate = () => {
    if (templateShow.value == false) {
        templateShow.value = true;
    } else {
        templateShow.value = false;
    }
};

const changedTemplate = () => {
    addedTemplate.value = false;
};
const showPiping = () => {
    if (pipingShow.value == false) {
        pipingShow.value = true;
    } else {
        pipingShow.value = false;
    }
};

const clickedQuestion = ref(null);

const selectQuestion = (order) => {
    const question = props.questions_data
        .map((q) => (q.order === order ? q : null))
        .filter(Boolean)[0];
    clickedQuestion.value = order;

    if (question) {
        selected_order.value = [
            "<b>[PIPE:Q" + question.order + "]</b>",
            question.order,
        ];

        // Store only the first 10 characters and append "..." if the text exceeds 10 characters
        selected_question.value = [
            '<span contenteditable="false" id="pipe" class="non-movable bg-gray-100 border border-gray-300 border-l-8 border-r-8 px-2 py-1 font-semibold inline-block whitespace-nowrap cursor-default select-none text-gray-800 relative rounded"><span class="absolute inset-0 border-1 border-gray-500 rounded-none pointer-events-none"></span>Question ' +
            question.order +
            "</span>",
            question.order,
        ];
        // console.log("selected: ", selected_order.value);
    } else {
        console.log("Question not found");
    }
};

const QV_list = (qIndex) => {
    // Assuming _questions.value is an array of objects
    const question = _questions.value.find((q) => q.order === qIndex); // Find the question with the matching order
    clickedQuestion.value = qIndex;
    if (question && question.choices.length != 0) {
        const choicesInfo = question.choices.map((choice, index) => ({
            value: [
                "<b>[PIPE:Q" + qIndex + "_" + choice.index + "]</b>",
                '<span contenteditable="false" id="pipe" class="non-movable bg-gray-100 border border-gray-300 border-l-8 border-r-8 px-2 py-1 font-semibold inline-block whitespace-nowrap cursor-default select-none text-gray-800 relative rounded"><span class="absolute inset-0 border-1 border-gray-500 rounded-none pointer-events-none"></span>Question ' +
                qIndex +
                "_" +
                choice.index +
                "</span>",
            ],
            text: "Q" + qIndex + "_" + choice.index + ": " + choice.name, // Assuming 'name' is the key for the name in each choice object
        }));

        // console.log(">>> Choices Info: ", choicesInfo); // Log the index and name of each choice
        valueList.value = choicesInfo; // Return the mapped array
    } else if (question && question.items.length != 0) {
        const choicesInfo = question.items.map((item, index) => ({
            value: [
                "<b>[PIPE:Q" +
                qIndex +
                "_" +
                (item.index ?? index + 1) +
                "]</b>",
                '<span contenteditable="false" id="pipe" class="non-movable bg-gray-100 border border-gray-300 border-l-8 border-r-8 px-2 py-1 font-semibold inline-block whitespace-nowrap cursor-default select-none text-gray-800 relative rounded"><span class="absolute inset-0 border-1 border-gray-500 rounded-none pointer-events-none"></span>Question ' +
                qIndex +
                "_" +
                (item.index ?? index + 1) +
                "</span>",
            ],
            text:
                "Q" +
                qIndex +
                "_" +
                (item.index ?? index + 1) +
                ": " +
                (item.name ?? item.statementA + "<=>" + item.statementB), // Assuming 'name' is the key for the name in each choice object
        }));

        // console.log(">>> items Info: ", choicesInfo); // Log the index and name of each choice
        valueList.value = choicesInfo; // Return the mapped array
    }
};

const projectCode = ref(usePage().props.questionnaire.code);
provide('projectCode', projectCode.value);

const show = ref(true)
</script>

<template>
    <jet-dialog-modal :show="modal.add_edit" :closeable="false" maxWidth="max-w-5xl" @close="close()">
        <template #title>
            <div class="flex items-center gap-2 pb-2" v-if="modal.method == 'add'">
                <div class="enhanced-icon w-12 h-12 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                </div>
                <span class="flex items-center gap-1 text-gray-700">
                    Add Question
                    <div v-if="
                        mc_warn?.warning.cond == true ||
                        sg_warn?.warning.cond == true ||
                        is_warn?.warning.cond == true ||
                        rs_warn?.slider_warning.equivalent_idx.length > 0 ||
                        rs_warn?.slider_warning.value_idx.length > 0 ||
                        br_warn?.button_warning.equivalent_idx.length > 0 ||
                        br_warn?.button_warning.value_idx.length > 0
                    " class="flex items-center p-1 text-xs text-red-800 dark:text-red-600 dark:border-red-600 group"
                        role="alert">
                        <svg class="flex-shrink-0 inline w-4 h-4 me-1 group-hover:opacity-100" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                        </svg>
                        <div
                            class="hidden group-hover:block absolute bg-white text-red-800 dark:text-red-600 bg-gray-200 rounded-md ml-5 p-2 shadow-md">
                            <span class="font-medium">Duplicate Inputs Detected!</span>
                        </div>
                    </div>
                </span>

            </div>
            <div class="flex items-center gap-2 pb-2" v-else>
                <div class="update-icon w-12 h-12 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                </div>
                <span class="flex items-center gap-1 text-gray-700">

                    Update Question
                    <span class="">
                        {{ $VariableName && $VariableName.slice(1) }}
                    </span>
                    <div v-if="
                        mc_warn?.warning.cond == true ||
                        sg_warn?.warning.cond == true ||
                        is_warn?.warning.cond == true ||
                        rs_warn?.slider_warning.equivalent_idx.length > 0 ||
                        rs_warn?.slider_warning.value_idx.length > 0 ||
                        br_warn?.button_warning.equivalent_idx.length > 0 ||
                        br_warn?.button_warning.value_idx.length > 0
                    " class="flex items-center p-1 text-xs text-red-800 dark:text-red-600 dark:border-red-600 group"
                        role="alert">
                        <svg class="flex-shrink-0 inline w-4 h-4 me-1 group-hover:opacity-100" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                        </svg>
                        <div
                            class="hidden group-hover:block absolute bg-white text-red-800 dark:text-red-600 bg-gray-200 rounded-md ml-5 p-2 shadow-md">
                            <span class="font-medium">Duplicated Inputs Detected!</span>
                        </div>
                    </div>
                </span>
            </div>

            <div class="border-b-2 border-[var(--mcolor)] mr-1"></div>
        </template>

        <template #content>
            <jet-label class="">
                <span>Title</span> <small
                    class="bg-[var(--mcolor)] p-[1px_5px] rounded-[15px] font-bold text-white">Required</small>
                <s4-input ref="chartTitleInput" color="ssrGray" class="w-full mb-1 mt-1 italic-placeholder"
                    v-model="form.setup.chartTitle" placeholder="Add title here...">
                </s4-input>
                <jet-input-error :message="form.errors.chartTitle" />
            </jet-label>
            <jet-label class="mb-2 mt-3 pointer-events-none">
                <span v-if="form.question_type_id == 15">Instruction</span>
                <span v-else>Question</span>


                <div class="float-right mb-2 !pointer-events-auto">
                    <label for="toogleA" class="flex items-center"
                        :class="form.setup.hideQuestionFrame ? 'cursor-not-allowed opacity-60' : 'cursor-pointer'">
                        <div class="ml-3 text-gray-600">Text Editor</div>
                        <Tooltip :imgSrc="question"
                            label="When turned off, HTML tags will be visible and editable directly.">
                        </Tooltip>

                        <div class="relative">
                            <input id="toogleA" type="checkbox" v-model="enableEditor" class="sr-only"
                                :disabled="form.setup.hideQuestionFrame" />
                            <div class="w-10 h-4 bg-gray-200 border-gray-300 border-1 rounded-full shadow-inner"></div>
                            <div
                                class="dot absolute w-3 h-3 !bg-[var(--mcolor)] rounded-full shadow -left-[-10px] top-[2px] transition">
                            </div>
                        </div>
                    </label>
                </div>
            </jet-label>
            <!-- {{ form.question }} -->

            <!-- <div v-if="show" class="grid grid-cols-2 gap-1">
                    <TE3 v-if="enableEditor == true" placeholder="Add your question here..." :modelValue="form.question"
                @update:modelValue="(res) => { form.question = res }" @show:piping="(obj, close = false) => {
                    if (close) {
                        pipe_modal.show = false
                    } else {
                        pipe_modal.show = !pipe_modal.show
                    }
                    pipe_modal.target = obj.target
                    pipe_modal.user = obj.user
                }"></TE3>
                <TE3 v-if="enableEditor == true" placeholder="Add your question here..." :modelValue="form.question"
                @update:modelValue="(res) => { form.question = res }" @show:piping="(obj, close = false) => {
                    if (close) {
                        pipe_modal.show = false
                    } else {
                        pipe_modal.show = !pipe_modal.show
                    }
                    pipe_modal.target = obj.target
                    pipe_modal.user = obj.user
                }"></TE3>
                </div> -->

            <!-- <S4TextEditor :editorType="editorType" @showPipe="showPiping" :addedTemplate="addedTemplate"
                :modelValue="form.question" @update:modelValue="form.question = $event"
                :selectedQuestion="selected_question" :selectedOrder="selected_order" v-if="enableEditor == true">
            </S4TextEditor> -->
            <!-- <ckeditor :editor="editor" v-model="form.question" v-if="enableEditor == true" :config="editorConfig">
            </ckeditor> -->

            <!-- <TipTapEditor v-if="enableEditor == true" 
                :placeholder="form.setup.hideQuestionFrame && form.question ? '' : 'Add your question here...'"
                :modelValue="form.question" :disabled="form.setup.hideQuestionFrame"
                @update:modelValue="(res) => { form.question = res }" @show:piping="(obj, close = false) => {
                    if (close) {
                        pipe_modal.show = false
                    } else {
                        pipe_modal.show = !pipe_modal.show
                    }
                    pipe_modal.target = obj.target
                    pipe_modal.user = obj.user
                }"
            
            /> -->

            <TE3 v-if="enableEditor == true" :key="form.question_type_id"
                :placeholder="form.setup.hideQuestionFrame && form.question ? '' : (form.question_type_id == 15 ? 'Add your instruction here...' : 'Add your question here...')"
                :modelValue="form.question" :disabled="form.setup.hideQuestionFrame"
                @update:modelValue="(res) => { form.question = res }" @show:piping="(obj, close = false) => {
                    if (close) {
                        pipe_modal.show = false
                    } else {
                        pipe_modal.show = !pipe_modal.show
                    }
                    pipe_modal.target = obj.target
                    pipe_modal.user = obj.user
                }"></TE3>

            <div v-else class="relative w-full h-[30vh]">
                <prism-editor class="my-editor flex w-full" :highlight="highlighter" line-numbers
                    v-model="form.question" :readonly="form.setup.hideQuestionFrame">
                </prism-editor>
            </div>

            <!-- <s4-text-area v-else v-model="form.question" class="w-full h-40" color="ssrGray"></s4-text-area> -->
            <jet-input-error ref="questionInput" :message="form.errors.question" />
            <!-- <s4-button class="p-1.5 mt-2" :class="{'bg-sky-100 border-blue-300': pipingShow == true}"
                    @click="showPiping()">
                    <span class="pl-3 pr-3 font-bold">
                        Enable Piping
                    </span>
                </s4-button> -->


            <!-- <div class="mb-6 pr-2 overflow-auto"> -->
            <div class="mb-6 pr-2">
                <div class="flex items-center w-[90px] mt-4 px-2 p-2 border-0 rounded-[5px] cursor-pointer bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] font-bold transition duration-300 z-[2] hover:easy-in text-sm text-white"
                    @click="showTemplate">
                    Templates
                </div>
                <div class="grid grid-cols-6 gap-1">

                    <div class="mt-3 col-span-3">

                        <jet-label>Question Category</jet-label>
                        <!-- <s4-input-select class="w-full" color="ssrGray" @change="updateCategoryList()"
                            :lists="question_types.categories" v-model="category"></s4-input-select> -->

                        <SingleSelect :options="question_types.categories" :modelValue="category" @update:modelValue="(res) => {
                            category = res
                            updateCategoryList()
                            template_filters.qtype = form.question_type_id
                        }"></SingleSelect>

                    </div>
                    <div class="mt-3 col-span-3" v-if="form.question_type_id != 19 && form.question_type_id != 11">
                        <jet-label>Question Type</jet-label>
                        <div class="flex gap-1">
                            <!-- <s4-input-select class="w-full" color="ssrGray" @change="updateSetup()" :lists="question_types.values.filter(
                                (type) => type.category == category
                            )
                                " v-model="form.question_type_id">
                            </s4-input-select> -->

                            <SingleSelect :options="question_types.values.filter(
                                (type) => type.category == category
                            )" :modelValue="form.question_type_id" @update:modelValue="(res) => {
                                form.question_type_id = res
                                template_filters.qtype = form.question_type_id
                                updateSetup()
                            }"></SingleSelect>


                            <!-- <s4-button :theme="'ssrAddColRow'" @click="showModal('question', 'select_template', 'save')"> -->

                            <!-- <s4-button :theme="'ssrAddColRow'" @click="showTemplate">
                            <span class="pl-3 pr-3 font-bold">Templates</span>
                        </s4-button>  -->
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <descriptive v-if="form.question_type_id == 1" />
                    <multiple-choice ref="mc_warn" v-else-if="form.question_type_id == 2" />
                    <image-selection ref="is_warn" v-else-if="form.question_type_id == 3" />
                    <single-textbox v-else-if="form.question_type_id == 4" />
                    <multiple-textbox ref="mt_warn" v-else-if="form.question_type_id == 5" />
                    <open-ended v-else-if="form.question_type_id == 6" />
                    <constant-sum v-else-if="form.question_type_id == 7" />
                    <slider ref="rs_warn" v-else-if="form.question_type_id == 8" />
                    <ranking v-else-if="form.question_type_id == 9" />
                    <star-rating v-else-if="form.question_type_id == 10" />
                    <matrix-choice v-else-if="form.question_type_id == 11" />
                    <matrix-multi v-else-if="form.question_type_id == 12" />
                    <memory-test-desc v-else-if="form.question_type_id == 13" />
                    <memory-test-comparison v-else-if="form.question_type_id == 14" />
                    <!-- <slider ref="br_warn" v-else-if="form.question_type_id == 15" /> -->
                    <button-rating ref="br_warn" v-else-if="form.question_type_id == 15" />
                    <Segmentation ref="sg_warn" v-if="form.question_type_id == 16" />
                    <VideoPlayer ref="vp_warn" v-if="form.question_type_id == 18" />
                    <PairWiseComparison v-if="form.question_type_id == 19" />
                    <BestWorstScale v-if="form.question_type_id == 20" />
                    <BarChart v-if="form.question_type_id == 21" />
                    <BestWorstScale2 v-if="form.question_type_id == 22" />
                </div>
                <!-- <div class="w-[50vmin] mt-2">
                    <jet-label>Data Type</jet-label>
                    <vue-form-multiselect class="testType" placeholder="" :options="dataTypeList"
                        v-model="form.setup.dataType" />
                </div> -->
                <!-- <div class="my-3 grid grid-cols-2">
                    <div class="flex gap-1 col-span-2">
                        <div class="w-full">
                            <jet-label>Question Name</jet-label>
                            <s4-input color="ssrGray" class="w-full mb-1" v-model="form.setup.questionName">
                            </s4-input>
                        </div>
                        <div class="w-full">
                            <jet-label>Data Type</jet-label>
                            <vue-form-multiselect class="h-8 p-1 testType" placeholder="" :options="dataTypeList"
                                v-model="form.setup.dataType" />
                        </div>
                    </div>
                </div> -->
            </div>
            <teleport to="body">
                <transition name="TemplateModalAnimate">
                    <div class="absolute top-[90px] h-[calc(100vh-120px)] w-[320px] rounded-lg overflow-hidden left-[160px] z-[90]"
                        v-show="templateShow">
                        <div
                            class="bg-white duration-300 transition-all box-border relative h-full bg-[#EAEBED] flex flex-col w-full">
                            <div class="grid grid-cols-1 gap-1 px-6 pt-6 pb-2 w-full bg-[var(--mcolor)] text-white">

                                <div class="font-bold text-xl">Templates</div>
                                <!-- <s4-input-select class="w-full mt-1" color="ssrGray" :lists="all_language.language"
                                v-model="language_id"></s4-input-select> -->
                                <div class="grid grid-cols-[1fr_100px] gap-1">
                                    <div class="w-full relative mb-1 col-span-2 min-w-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="gray"
                                            class="size-5 position absolute left-2 top-3">
                                            <path fill-rule="evenodd"
                                                d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z"
                                                clip-rule="evenodd" />
                                        </svg>


                                        <s4-input color="ssrGray" class="pr-6 pl-8 text-[var(--txtcolor)]"
                                            v-model="template_filters.text" placeholder="Search for...">
                                        </s4-input>
                                        <svg v-show="template_filters.text" @click="template_filters.text = ''"
                                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="red"
                                            class="size-5 position absolute right-2 top-3 cursor-pointer">
                                            <path
                                                d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                                        </svg>

                                    </div>

                                    <div class="min-w-0">
                                    <SingleSelect :options="template_qtype" :modelValue="template_filters.qtype"
                                        fixed-dropdown-width
                                        @update:modelValue="(res) => {
                                            template_filters.qtype = res
                                        }"></SingleSelect>
                                    </div>

                                    <div class="min-w-0">
                                    <SingleSelect :options="all_language.language" :modelValue="language_id"
                                        fixed-dropdown-width
                                        @update:modelValue="(res) => {
                                            language_id = res
                                        }"></SingleSelect>
                                    </div>
                                </div>

                            </div>
                            <div class="pb-6 overflow-y-auto grow-[1] relative w-full h-full">
                                <TransitionGroup name="list" tag="div">
                                    <template v-for="(template, tindex) in selected_templates()" :key="template.id">
                                        <div class="col-span-6 box-border ">
                                            <div class="flex items-left">
                                                <label
                                                    class="col-span-6 mb-0 px-6 py-3 text-sm flex-1 w-64 flex items-center justify-left cursor-pointer duration-350 transition-all hover:bg-slate-100 border-t-[.9px] border-gray-200"
                                                    @click="selectTemplate(template, tindex)" :class="currentTemplate === tindex
                                                        ? 'text-sm text-white bg-ssr-blue2w-32 !bg-[var(--mcolor)] font-bold hover:bg-gray-200'
                                                        : 'text-sm w-0 text-slate-600'
                                                        ">
                                                    <div :class="currentTemplate === tindex
                                                        ? 'text-black'
                                                        : 'text-slate-600'
                                                        ">
                                                        <!-- <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                        </svg> -->
                                                        <!-- <img :src="qt_icons[template.question_type_id]"
                                                            class="w-[30px] h-[auto]" /> -->
                                                        <div v-html="qt_icons[template.question_type_id]"></div>
                                                    </div>
                                                    <div class="ml-2 text-sm truncate">
                                                        {{ template.name }}
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </template>
                                </TransitionGroup>
                                <div class="w-full text-center italic text-red-500 px-2 py-8"
                                    v-if="selected_templates().length <= 0">No template found!
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </teleport>
            <!-- <div class="absolute h-screen top-0 -left-72">
                <div class="bg-white h-[calc(100vh-150px)] rounded-lg p-4 duration-300 transition-all box-border relative bg-[#EAEBED] overflow-y-auto"
                    :class="!pipingShow ? 'w-0 opacity-0' : 'w-[280px] opacity-100'
                        ">


                    <div class="grid grid-cols-1 gap-2">
                        <jet-label class="mb-4">
                            Question Piping
                            <s4-input-select class="w-full mt-1" color="ssrGray" :lists="pipingSelect"
                                v-model="selectedPipingOption"></s4-input-select>
                        </jet-label>

                    </div>
                  
                    <template v-if="selectedPipingOption == 'QO'" v-for="question in _questions">
                        <div class="col-span-6 box-border duration-150 transition-all mb-1"
                            :class="!pipingShow ? ' opacity-0' : ' opacity-100'">
                            <div class="flex items-left">
                                <label
                                    class="col-span-6 mb-0 ml-1 p-3 pl-1.5 text-sm flex-1 w-10 flex items-center justify-left cursor-pointer duration-350 transition-all hover:bg-slate-100 rounded-lg h-11 overflow-hidden"
                                    @click="selectQuestion(question.order)" v-if="question.question_type_id != 1"
                                    :class="clickedQuestion === question.order
                                        ? 'text-sm bg-ssr-blue2w-32 rounded-lg text-ssr-blue2 bg-ssr-blue4 hover:bg-ssr-blue4 hover:text-ssr-blue2'
                                        : 'text-sm w-0 text-slate-500'
                                        ">
                                    <div :class="clickedQuestion === question.order
                                        ? ' text-ssr-blue2'
                                        : 'text-slate-400'
                                        ">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <circle cx="6" cy="6" r="2.25" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <circle cx="6" cy="18" r="2.25" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <circle cx="18" cy="6" r="2.25" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <circle cx="18" cy="18" r="2.25" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path
                                                d="M6 3.75c-1.24 0-2.25 1.01-2.25 2.25v2.25c0 1.24 1.01 2.25 2.25 2.25h12c1.24 0 2.25-1.01 2.25-2.25V6c0-1.24-1.01-2.25-2.25-2.25h-12zM6 13.5c-1.24 0-2.25 1.01-2.25 2.25v2.25c0 1.24 1.01 2.25 2.25 2.25h12c1.24 0 2.25-1.01 2.25-2.25V15.75c0-1.24-1.01-2.25-2.25-2.25h-12z"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <span class="ml-1 font-bold">Q{{ question.order }}:</span>
                                    <div class="ml-1 text-sm font-normal truncate w-full">
                                        {{
                                            sanitizedQuestion(question.question)
                                        }}
                                    </div>
                                </label>
                            </div>
                        </div>
                    </template>

                    <template v-if="selectedPipingOption == 'QV'" v-for="question in _questions">
                        <div class="col-span-6 box-border duration-150 transition-all mb-1"
                            :class="!pipingShow ? ' opacity-0' : ' opacity-100'">
                            <div class="flex items-left">
                                <label
                                    class="col-span-6 mb-0 ml-1 p-3 pl-1.5 text-sm flex-1 w-10 flex items-center justify-left cursor-pointer duration-350 transition-all hover:bg-slate-100 rounded-lg h-50 overflow-hidden"
                                    @click="QV_list(question.order)" v-if="
                                        question.question_type_id != 18 &&
                                        question.question_type_id != 14 &&
                                        question.question_type_id != 13 &&
                                        question.question_type_id != 11 &&
                                        question.question_type_id != 1 &&
                                        question.question_type_id != 4 &&
                                        question.question_type_id != 6
                                    " :class="clickedQuestion === question.order
                                        ? 'text-sm bg-ssr-blue2w-32 rounded-lg text-ssr-blue2 bg-ssr-blue4 hover:bg-ssr-blue4 hover:text-ssr-blue2'
                                        : 'text-sm w-0 text-slate-500'
                                        ">
                                    <div :class="clickedQuestion === question.order
                                        ? ' text-ssr-blue2'
                                        : 'text-slate-400'
                                        ">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <circle cx="6" cy="6" r="2.25" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <circle cx="6" cy="18" r="2.25" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <circle cx="18" cy="6" r="2.25" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <circle cx="18" cy="18" r="2.25" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path
                                                d="M6 3.75c-1.24 0-2.25 1.01-2.25 2.25v2.25c0 1.24 1.01 2.25 2.25 2.25h12c1.24 0 2.25-1.01 2.25-2.25V6c0-1.24-1.01-2.25-2.25-2.25h-12zM6 13.5c-1.24 0-2.25 1.01-2.25 2.25v2.25c0 1.24 1.01 2.25 2.25 2.25h12c1.24 0 2.25-1.01 2.25-2.25V15.75c0-1.24-1.01-2.25-2.25-2.25h-12z"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <div class="ml-1 text-sm font-normal truncate w-full"></div>
                                    <div v-for="choice in question.choices" :key="choice.index"
                                        class="mb-2 flex flex-col">
                                        <div class="flex items-center">
                                            <span class="ml-1 font-bold">Q{{ question.order }}:</span>
                                            <div class="ml-1 text-sm font-normal truncate w-[19vmin]">
                                                {{
                                                    sanitizedQuestion(
                                                        question.question
                                                    )
                                                }}
                                            </div>
                                        </div>

                                        <s4-input-select v-if="
                                            clickedQuestion ===
                                            question.order
                                        " style="width: 20vmin !important" class="mt-1 ml-2" color="ssrGray"
                                            :lists="valueList" v-model="selectedValue">
                                        </s4-input-select>
                                    </div>

                                    <div v-for="choice in question.items" :key="choice.index"
                                        class="mb-2 flex flex-col">
                                        <div class="flex items-center">
                                            <span class="ml-1 font-bold">Q{{ question.order }}:</span>
                                            <div class="ml-1 text-sm font-normal truncate w-[19vmin]">
                                                {{
                                                    sanitizedQuestion(
                                                        question.question
                                                    )
                                                }}
                                            </div>
                                        </div>

                                        <s4-input-select v-if="
                                            clickedQuestion ===
                                            question.order
                                        " style="width: 20vmin !important" class="mt-1 ml-2" color="ssrGray"
                                            :lists="valueList" v-model="selectedValue">
                                        </s4-input-select>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </template>


                </div>
            </div> -->

            <!-- <div class="absolute h-screen top-0 -right-72">
            <div class="bg-white max-h-[95%] h-[95%]  rounded-lg p-4 duration-300 transition-all box-border relative bg-[#EAEBED] overflow-y-auto" :class="!templateShow ? 'w-0 opacity-0' : 'w-[280px] opacity-100'">
                <div class="flex justify-center font-bold box-border duration-150 transition-all mb-0 h-10 text-slate-500" :class="!templateShow ? ' opacity-0' : ' opacity-100'">
                    Gallery
                </div>
                <template v-for="(gallery,tindex) in $page.props.gallery" :key="tindex">
                    <div class="col-span-6 box-border duration-150 transition-all" :class="!templateShow ? ' opacity-0' : ' opacity-100'">
                        <div class="p-2 border-2 rounded-md transition ease-out duration-300 border-grey-800 container flex justify-center cursor-pointer hover:border-ssr-blue3">
                            <div class="imgCont ">
                                <img class="relative"
                                    :src="gallery.file_path
                                        ? `${import.meta.env.VITE_APP_FLUENT_URL}/storage/${gallery.file_path}`
                                        : $page.props.app_url + '/gallery/' + (gallery.filename || gallery.file_name)
                                    ">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div> -->

            <!-- Foter customize -->
            <!-- <div class="bg-gray-100 text-right absolute left-0 bottom-0 w-full z-40">

            </div> -->
        </template>
        <template #footer>
            <!-- <div class="relative z-10 flex flex-row items-center h-16 justify-between px-6 "> -->
            <div class="relative z-10 flex flex-row items-center justify-between w-full">
                <s4-button :theme="'theme2'" class="float-left" :disabled="form.processing"
                    @click="showModal('question', 'save_template', 'save')">
                    <span class="pl-3 pr-3"> Save as template </span>
                </s4-button>
                <div>
                    <s4-button :theme="'ssrCancel'" color="ssrGray" class="mr-3" @click="close2()">
                        Cancel
                    </s4-button>
                    <s4-button :theme="'ssrOrange'" :disabled="form.processing" @click="AddEditQuestion()">
                        <template v-if="form.processing">
                            <span class="pl-3 pr-3 text-white"><font-awesome-icon :icon="faIcon.spinner" spin />
                                Wait</span>
                        </template>
                        <template v-else-if="modal.method == 'edit'">
                            <span class="pl-3 pr-3 text-white"> Update </span>
                        </template>
                        <template v-else>
                            <span class="pl-3 pr-3 text-white"> Save </span>
                        </template>
                    </s4-button>
                </div>
            </div>
        </template>

        <!-- <template #footer>
        <div class="w-full">
            <s4-button :theme="'theme2'" class="float-left" :disabled="form.processing" @click="showModal('question', 'save_template', 'save')">
            <span class="pl-3 pr-3">
                Save as template
            </span>
            </s4-button>
            <s4-button :theme="'ssrCancel'" color="ssrGray" class="mr-3" @click="close()">
                Cancel
            </s4-button>
            <s4-button :theme="'ssrOrange'" :disabled="form.processing" @click="AddEditQuestion()">
                <template v-if="form.processing">
                    <span class="pl-3 pr-3"><font-awesome-icon :icon="faIcon.spinner" /> Wait</span>
                </template>
                <template v-else-if="modal.method == 'edit'">
                    <span class="pl-3 pr-3">
                        Update
                    </span>
                </template>
                <template v-else>
                    <span class="pl-3 pr-3">
                        Save
                    </span>
                </template>
            </s4-button>
        </div>
    </template> -->
    </jet-dialog-modal>
    <QuestionPiping :questions="props.questions_data" :pipe_modal="pipe_modal"></QuestionPiping>
</template>

<style scoped>
.testType {
    text-transform: capitalize;
}

.ckbtn {
    border: none;
    border-radius: 0.3rem;
}

input[type="checkbox"].sr-only {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border-width: 0 !important;
    display: none !important;
}

input~.dot {
    transform: translateX(-7px);
}

input:checked~.dot {
    transform: translateX(15px);
}

.imgCont {
    margin: auto auto;
}

.imgCont img {
    max-height: 10vmin;
    max-width: 20vmin;
    position: relative;
}

.italic-placeholder::placeholder {
    font-style: italic;
}


.TemplateModalAnimate-enter-active {
    animation: TemplateModalAnimate-in 0.5s;
}

.TemplateModalAnimate-leave-active {
    animation: TemplateModalAnimate-in 0.5s reverse;
}

@keyframes TemplateModalAnimate-in {
    0% {
        opacity: 0;
        transform: translateX(-100%);
    }

    100% {
        opacity: 1;
        transform: translateX(0px);
    }
}

.list-move,
/* apply transition to moving elements */
.list-enter-active,
.list-leave-active {
    transition: all 0.5s ease;
}

.list-enter-from,
.list-leave-to {
    opacity: 0;
    transform: translateX(30px);
}

/* ensure leaving items are taken out of layout flow so that moving
   animations can be calculated correctly. */
.list-leave-active {
    position: absolute;
}
</style>
