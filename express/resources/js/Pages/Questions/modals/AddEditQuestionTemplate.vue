<script setup>
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetInputError from '@/Jetstream/InputError.vue'
import S4Button from '@/S4/Button.vue';
import S4InputSelect from '@/S4/InputSelect.vue';
import Descriptive from './types/Descriptive.vue';
import MultipleChoice from './types/MultipleChoice.vue';
import ImageSelection from './types/ImageSelection.vue';
import SingleTextbox from './types/SingleTextbox.vue';
import MatrixChoice from './types/MatrixChoice.vue';
import MatrixMulti from './types/MatrixMulti.vue';
import ConstantSum from './types/ConstantSum.vue';
import MemoryTestComparison from './types/MemoryTestComparison.vue';
import MemoryTestDesc from './types/MemoryTestDesc.vue';
import MultipleTextbox from './types/MultipleTextbox.vue';
import OpenEnded from './types/OpenEnded.vue';
import Ranking from './types/Ranking.vue';
import Slider from './types/Slider.vue';
import StarRating from './types/StarRating.vue';
import ButtonRating from './types/ButtonRating.vue';
import Segmentation from './types/Segmentation.vue';
import PairWiseComparison from './types/PairWiseComparison.vue';
import BestWorstScale from './types/BestWorstScale.vue';
import BestWorstScale2 from './types/BestWorstScale2.vue';
import BarChart from './types/BarChart.vue';
import VideoPlayer from './types/VideoPlayer.vue';
import S4Input from '@/S4/Input.vue';
import SingleSelect from '@/S4/SingleSelect.vue';
import prism_editor from "../../../prism_editor";
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEdit, faSpinner, faSave, faCode } from '@fortawesome/free-solid-svg-icons'
import ClassicEditor from '@ckeditor/ckeditor5-build-classic'
import CKEditor from '@ckeditor/ckeditor5-vue'
import UploadAdapterPlugin from '@/Functions/UploadAdapter'
import Tooltip from "@/S4/Tooltip.vue";
import question from "/public/images/question.svg";
import { computed, inject, onBeforeMount, ref, watch } from 'vue';
import { useForm, usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js'
import TE3 from "@/S4/TE3.vue";
import icons from "@/qt_icons.js";
const { val_exists, assign, toast } = helpers()

const { highlighter, PrismEditor } = prism_editor();
const qt_icons = icons.qt_icons;
const props = defineProps({
    modal: {
        default: {
            add_edit: false,
            method: 'add',
            data: null,
            name: null,
            language_id: null,
        }
    }
})


// const enableCK = computed(() => {
//     return "relative inline-flex items-center px-2 py-1 bg-orange-400 border border-orange-400 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-500 active:bg-yellow-900 focus:outline-none focus:border-yellow-900 focus:shadow-outline-yellow transition ease-in-out duration-150";
// })

const emits = defineEmits(["showModal", "close"])
const form = inject('add_edit_form')
const faIcon = ref({
    save: faSave,
    spinner: faSpinner,
    edit: faEdit,
    code: faCode,
})
const formSubmit = useForm({
    name: null,
    language_id: null,
    q_data: {}
}, {
    resetOnSuccess: true,
})
const name = ref(null)

const category = ref('Description')
const question_types = ref({
    values: [],
    categories: []
})

const questionTypesForCurrentCategory = computed(() => {
    return question_types.value.values.filter((type) => type.category === category.value)
})

// Show Question Type dropdown only when there is more than one type
// for the selected category (mirrors AddEditQuestion.vue behaviour and
// is driven by QuestionTypeSeeder-defined categories/types)
const showQuestionTypeSelect = computed(() => {
    return questionTypesForCurrentCategory.value.length > 1
})

const all_language = ref({
    language: []
})

const copyData = useForm({
    questionToCopy: null,
    copyQuestionTo: null,
})
const copyProj_id = ref(null)
const copyDataToProject = useForm({
    copyCodeTo: null, //Where qnr will be copied into what project
    codeToCopy: null, //Code to copy
    qnrCodeTo: null, //where question will be copied to what questionnaire
    qnrCodeToCopy: null, //question code to copy
})
const copyToQuestionnaire = ref([])
const editor = ref(ClassicEditor)
const ckeditor = CKEditor.component
const enableEditor = ref(true)
const editorConfig = ref({
    extraPlugins: [UploadAdapterPlugin]
});

const editorType = ref("template")
const selected_question = ref(null)


onBeforeMount(() => {
    initialized()
})


const initialized = () => {
    usePage().props.question_types.forEach(type => {
        //type lists
        question_types.value.values.push({
            icon: qt_icons[type.id],
            value: type.id,
            text: type.name,
            category: type.category //for filtering lists
        })
        // category list
        if (!val_exists(question_types.value.categories, { key: 'value', value: type.category })) {
            question_types.value.categories.push({
                value: type.category,
                text: type.category
            })
        }
    })

    usePage().props.language_all.forEach(lang => {
        all_language.value.language.push({
            value: lang.id,
            text: lang.name
        })
    })
}
const updateSetup = () => {
    //for edit old questions
    if (('boxStyle' in form.setup) == false) {
        form.setup['boxStyle'] = {
            'size': 'md',
            'max-width': ''
        }
        form.setup['style'] = {
            'max-width': '',
            'width': '100%',
            'height': '200px'
        }
    }


    var default_setup = usePage().props.question_types.find(question_type => question_type.id == form.question_type_id).setup
    for (const key in default_setup) {
        //add setting if not exist
        if (!Object.hasOwnProperty.call(form.setup, key)) {
            form.setup[key] = default_setup[key]
        } else {
            if (typeof (form.setup[key]) != 'number' || typeof (form.setup[key]) != 'string') {
                //add setting deep
                for (const skey in default_setup[key]) {
                    if (!Object.hasOwnProperty.call(form.setup[key], skey)) {
                        form.setup[key][skey] = default_setup[key][skey]
                    }
                }
            }
        }
    }

}
const AddEditQuestionTemplate = () => {

    if (props.modal.method == 'add') {
        var web_route = 'templates.store'
        var params = [
            usePage().props.questionnaire.code
        ]
    }
    else if (props.modal.method == 'copy') {
        var params = [
            usePage().props.questionnaire.code,
            props.modal.data.id,
        ]
        copyData.post(route('templates.copy', params), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Template Copied', {
                    timeout: 2000
                });
                close()
            },
        })
    }
    else {
        var web_route = 'templates.update'
        var params = [
            usePage().props.questionnaire.code,
            props.modal.data.id
        ]
    }
    // console.log(route(web_route, params))
    formSubmit.q_data = form;
    formSubmit.name = props.modal.name;
    formSubmit.language_id = props.modal.language_id;
    formSubmit.post(route(web_route, params), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Template ${props.modal.method == 'add' ? 'added' : 'updated'}`, {
                timeout: 2000
            })
            close()
        },
        onError: () => {
            //code
        },
        onFinish: () => {
            //code
        }
    })
}
const updateCategoryList = (type_id = -1) => {
    if (type_id == -1) {
        // nextTick(() => {
        form.question_type_id = usePage().props.question_types.find(type => type.category == category.value).id
        // })
    } else {
        category.value = usePage().props.question_types.find(type => type.id == type_id).category
        // console.log(category.value)
    }

    //update setup
    updateSetup()
}
const showModal = (table, modal, method, index = -1) => {
    emits('showModal', table, modal, method, index)
}
const close = () => {
    form.reset()
    emits('close')
    if (props.modal.method == 'add') {
        emits('scrollToBottom')
    }
}
const close2 = () => {
    form.reset()
    emits('close')
}
const enableCKEditor = () => {
    enableEditor.value = !enableEditor.value;
}
const getQnr = () => {
    //console.log(this.copyProj_id+1,'id')

    let copyId = copyProj_id.value + 1;
    _.map(usePage().props.questionnaires, (questionnaire, qindex) => {
        if (questionnaire.project_id == copyId) {
            copyDataToProject.copyCodeTo = usePage().props.projects[copyProj_id.value].code;
            copyToQuestionnaire.value.push(questionnaire)
        }
    })
}

// Only react to modal open/method/data changes, not to language_id etc.
watch(
    () => [props.modal.add_edit, props.modal.method, props.modal.data],
    ([add_edit, method, data]) => {
        // Only run initialization when the modal is actually open
        if (!add_edit) return;

        if (method === 'edit' && data) {
            // Assign values to edit
            for (const key in form) {
                if (Object.hasOwnProperty.call(data, key)) {
                    form[key] = assign(data[key]);
                }
            }
            updateCategoryList(form.question_type_id);
        } else if (method === 'add') {
            // For new templates, default category/type once when opened
            updateCategoryList(1);
        }
    }
)
</script>

<template>
    <jet-dialog-modal :show="modal.add_edit" :closeable="false" maxWidth="7xl" @close="close()">
        <template #title>
            <div v-if="modal.method == 'add'" class="flex items-center gap-2 pb-2">
                <div class="enhanced-icon w-12 h-12 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                </div>
                <span class="text-gray-700">Add Template</span>
            </div>

            <span v-else-if="modal.method == 'copy'">Copy Template</span>
            <span v-else-if="modal.method == 'copy_to'">Copy Template To</span>
            <div v-else class="flex items-center gap-2 pb-2">
                <div class="update-icon w-12 h-12 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                </div>
                <span class="text-gray-700">Update Template</span>
            </div>

            <div class="border-b-2 border-[var(--mcolor)] mr-1"></div>

        </template>

        <template #content v-if="modal.method == 'copy'">
            <span>Are you sure you want to continue this action?</span>
        </template>

        <template #content v-else-if="modal.method == 'copy_to'">
            <span>Project Name:</span>
            <select v-model="copyProj_id" @change="getQnr()"
                class="w-full truncate form-select cursor-pointer text-gray-500 rounded-md border-1 border-gray-300 focus:border-ssr-blue3  focus:ring-1 focus:ring-ssr-blue3">
                <option v-for="(proj, index) in $page.props.projects" :key="index" :value="index">{{ proj.name }}
                </option>
            </select>

            <span v-if="copyToQuestionnaire.length > 0">Questionnaire:</span>
            <select v-if="copyToQuestionnaire.length > 0" v-model="copyDataToProject.qnrCodeToCopy"
                class="w-full truncate form-select cursor-pointer text-gray-500 rounded-md border-1 border-gray-300 focus:border-ssr-blue3  focus:ring-1 focus:ring-ssr-blue3">
                <option v-for="(qnr, index) in copyToQuestionnaire" :key="index" :value="qnr.code">{{ qnr.name }}
                </option>
            </select>

        </template>
        <template #content v-else>
            <div class="grid grid-cols-2 gap-2">
                <jet-label class="mb-4">
                    Name
                    <s4-input color="ssrGray" class="w-full mt-1" placeholder="Enter Name" v-model="modal.name">
                    </s4-input>
                </jet-label>
                <jet-label class="mb-4">
                    Language
                    <s4-input-select class="w-full mt-1" color="ssrGray" :lists="all_language.language"
                        v-model="modal.language_id"></s4-input-select>
                </jet-label>
            </div>
            <jet-label class="mb-2">
                Question Instruction

                <div class="float-right mb-2 !pointer-events-auto">
                    <label for="toogleA" class="flex items-center"
                        :class="form.setup.hideQuestionFrame ? 'cursor-not-allowed opacity-60' : 'cursor-pointer'">
                        <div class="ml-3 text-gray-600">Text Editor</div>
                        <Tooltip :imgSrc="question"
                            label="When turned off, HTML tags will be visible and editable directly.">
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

            <!-- <ckeditor :editor="editor" v-model="form.question" v-if="enableEditor == true" :config="editorConfig">
            </ckeditor> -->
            <!-- <S4TextEditor :editorType="editorType" :modelValue="form.question"
                @update:modelValue="form.question = $event" :selectedQuestion="selected_question"
                :selectedOrder="selected_order" v-if="enableEditor == true"></S4TextEditor>
            <s4-text-area v-else v-model="form.question" class="w-full h-40" color="ssrGray"></s4-text-area> -->
            <TE3 v-if="enableEditor == true" placeholder="Add your question here..." :modelValue="form.question"
                :selectedQuestion="selected_question" :enablePiping="false" @update:modelValue="(res) => {
                    form.question = res
                }"></TE3>

            <div v-else class="relative w-full h-[30vh]">
                <prism-editor class="my-editor flex w-full" :highlight="highlighter" line-numbers
                    v-model="form.question" :readonly="form.setup.hideQuestionFrame">
                </prism-editor>
            </div>


            <jet-input-error :message="form.errors.question" />
            <div class="grid grid-cols-6 gap-1">
                <div class="mt-3 col-span-3">
                    <jet-label>Question Category</jet-label>
                    <SingleSelect
                        :options="question_types.categories"
                        :modelValue="category"
                        @update:modelValue="(res) => {
                            category = res
                            updateCategoryList()
                        }"
                    />
                </div>
                <div
                    class="mt-3 col-span-3"
                    v-if="showQuestionTypeSelect"
                >
                    <jet-label>Question Type</jet-label>
                    <div class="flex gap-1">
                        <SingleSelect
                            :options="questionTypesForCurrentCategory"
                            :modelValue="form.question_type_id"
                            @update:modelValue="(res) => {
                                form.question_type_id = res
                                updateSetup()
                            }"
                        />
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <multiple-choice :modal="modal" v-if="form.question_type_id == 2" />
                <descriptive :modal="modal" v-else-if="form.question_type_id == 1" />
                <image-selection :modal="modal" v-else-if="form.question_type_id == 3" />
                <single-textbox :modal="modal" v-else-if="form.question_type_id == 4" />
                <multiple-textbox :modal="modal" v-else-if="form.question_type_id == 5" />
                <open-ended :modal="modal" v-else-if="form.question_type_id == 6" />
                <constant-sum :modal="modal" v-else-if="form.question_type_id == 7" />
                <slider :modal="modal" v-else-if="form.question_type_id == 8" />
                <ranking :modal="modal" v-else-if="form.question_type_id == 9" />
                <star-rating :modal="modal" v-else-if="form.question_type_id == 10" />
                <matrix-choice :modal="modal" v-else-if="form.question_type_id == 11" />
                <matrix-multi :modal="modal" v-else-if="form.question_type_id == 12" />
                <memory-test-desc :modal="modal" v-else-if="form.question_type_id == 13" />
                <memory-test-comparison :modal="modal" v-else-if="form.question_type_id == 14" />
                <button-rating :modal="modal" v-else-if="form.question_type_id == 15" />
                <segmentation :modal="modal" v-else-if="form.question_type_id == 16" />
                <video-player :modal="modal" v-else-if="form.question_type_id == 18" />
                <pair-wise-comparison :modal="modal" v-else-if="form.question_type_id == 19" />
                <best-worst-scale :modal="modal" v-else-if="form.question_type_id == 20" />
                <bar-chart :modal="modal" v-else-if="form.question_type_id == 21" />
                <best-worst-scale2 :modal="modal" v-else-if="form.question_type_id == 22" />
            </div>
        </template>

        <template #footer>
            <s4-button :theme="'ssrCancel'" color="ssrGray" class="mr-3" @click="close2()">
                Cancel
            </s4-button>
            <s4-button :theme="'ssrOrange'" :disabled="form.processing" @click="AddEditQuestionTemplate()">
                <template v-if="form.processing">
                    <span class="pl-3 pr-3 text-white"><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span>
                </template>
                <template v-else-if="modal.method == 'edit'">
                    <span class="pl-3 pr-3 text-white">
                        <!--<font-awesome-icon :icon="faIcon.edit" />-->
                        Update
                    </span>
                </template>
                <template v-else-if="modal.method == 'copy'">
                    <span class="pl-3 pr-3 text-white">
                        <!--<font-awesome-icon :icon="faIcon.copy" />-->
                        Copy
                    </span>
                </template>
                <template v-else>
                    <span class="pl-3 pr-3 text-white">
                        <!--<font-awesome-icon :icon="faIcon.save" /> -->
                        Save
                    </span>
                </template>
            </s4-button>
        </template>
    </jet-dialog-modal>
</template>



<style>
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
</style>