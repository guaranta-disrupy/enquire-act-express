<!-- 
<script setup>
import QuestionLayout from "../../Layouts/QuestionLayout.vue";

import {
    AddEditQuestionModal,
    CopyQuestionModal,
    DeleteQuestionModal,
    SaveAsTemplateModal,
    SelectTemplateModal,
    SkipLogicModal,
    MoveQuestionModal,
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
} from "../Questions";
import { provide, ref, onMounted, onUpdated } from "vue";
import helpers from "@/helpers.js";
import { useForm } from "@inertiajs/vue3";
import ButtonIconsVue from "../../S4/ButtonIcons.vue";
import Setting from "../Questions/Setting.vue";
import GalleryVue from "./Gallery/Gallery.vue";
import moment from "moment";

const { assign, random_str } = helpers();
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
const showSetting = ref(false);
const showGallery = ref(false);

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
});

onUpdated(() => {
    //added on update to update question list after adding question
    prepareList();
});

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
    } else {
        modals.value[table].data = null;
    }

    modals.value[table][modal] = true;
};
const closeModal = (table, modal) => {
    console.log(modal, table);
    if (modal === "select_template") modals.value[table].add_edit = false;
    modals.value[table][modal] = false;
    modals.value[table].data = null;
};

const badge = (question) => {
    return (
        question.setup?.advanceSkiplogic?.enter.length +
        question.setup?.advanceSkiplogic?.leave.length
    );
};
const showQuestion = (index) => {
    if (listShowHide.value[index].visible == true) {
        listShowHide.value[index].visible = false;
    } else {
        listShowHide.value[index].visible = true;
    }
    console.log(listShowHide.value);
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
const diffenceTime = (dateFromDatabase) => {
    const newdate = new Date(dateFromDatabase);
    const timestamp = moment(
        newdate,
        "ddd MMM DD YYYY HH:mm:ss GMT Z"
    ).fromNow();
    console.log(timestamp);
    return timestamp;
};
</script>

<template>
    <question-layout title="Questions">
        <template #content-question>
            <template v-if="$page.props.questions.length === 0">
                <div
                    class="border-t pt-8 grid grid-cols-1 gap-4 place-items-center w-full h-1/3"
                >
                    <div class="italic text-gray-400 text-md">
                        There are no questions available right now.
                    </div>
                </div>
            </template>
            <div class="overflow-hidden container">
                <div
                    v-for="(question, index) in $page.props.questions"
                    :key="index"
                >
                    <div
                        class="relative qCont flex flex-col flex-grow bg-gray-0 pr-5"
                        :class="{
                            'mb-4': $page.props.questions.length != index + 1,
                        }"
                    >
                        <div
                            class="absolute left-5 w-0.5 h-screen bg-slate-200"
                        ></div>
                        <div class="relative flex flex-row">
                            <div class="ml-1">
                                <span
                                    class="hover:scale-110 hover:ease-in duration-200 px-2 py-2 flex justify-center font-bold text-ssr-blue2 text-md border rounded-md p-1 bg-white cursor-pointer"
                                    @click="showQuestion(index)"
                                >
                                    Q{{ question.order }}
                                </span>
                            </div>
                            <div
                                class="w-full ml-2 bg-slate-50 rounded-lg border"
                            >
                                <div
                                    class="bg-white rounded-t-lg flex px-3 justify-items-center items-center gap-4"
                                >
                                    <div
                                        class="flex justify-center font-bold text-slate-400 text-sm border rounded-md p-1 bg-slate-100"
                                    >
                                        {{
                                            $page.props.questionnaire.setup
                                                .variable + question.order
                                        }}
                                    </div>
                                    <div
                                        class="text-sx rounded-md p-1 px-3 bg-slate-100 italic"
                                    >
                                        Last modified
                                        {{ diffenceTime(question.updated_at) }}
                                    </div>
                                    <div class="grow h-14"></div>
                                    <div
                                        class="relative float-right flex justify-center items-center gap-3"
                                    >
                                        <div
                                            class="relative float-right flex justify-center items-center gap-4 mx-4 text-slate-400 text-sm"
                                        >
                                            <a
                                                :href="
                                                    $page.props.engine_url +
                                                    '?code=' +
                                                    $page.props.questionnaire
                                                        .code +
                                                    '&preview=1&gtq=' +
                                                    (index + 1) +
                                                    '&id=test-' +
                                                    random_str(5)
                                                "
                                                target="_blank"
                                                class="hover:text-slate-700"
                                                href="javascript:void(0);"
                                                >Preview</a
                                            >

                                            <a
                                                @click="
                                                    showModal(
                                                        'question',
                                                        'copy',
                                                        'copy',
                                                        index
                                                    )
                                                "
                                                class="hover:text-slate-700"
                                                href="#"
                                                >Copy</a
                                            >

                                            <a
                                                @click="
                                                    showModal(
                                                        'question',
                                                        'add_edit',
                                                        'edit',
                                                        index
                                                    )
                                                "
                                                class="hover:text-slate-700"
                                                href="#"
                                                >Edit</a
                                            >

                                            <div class="relative group">
                                                <a
                                                    @click="
                                                        showModal(
                                                            'question',
                                                            'skip_logic',
                                                            'edit',
                                                            index
                                                        )
                                                    "
                                                    class="hover:text-slate-700"
                                                    href="#"
                                                    >Skip Logic
                                                </a>
                                                <div
                                                    v-if="badge(question)"
                                                    class="absolute text-white w-5 h-5 rounded-full bg-red-500 text-md flex justify-center items-center -top-3 -right-3 group-hover:scale-125 transition duration-250 ease-out hover:ease-in"
                                                >
                                                    {{ badge(question) }}
                                                </div>
                                            </div>

                                            <a
                                                @click="
                                                    showModal(
                                                        'question',
                                                        'delete',
                                                        'delete',
                                                        index
                                                    )
                                                "
                                                class="hover:text-slate-700"
                                                href="#"
                                                >Delete</a
                                            >

                                            <a
                                                @click="
                                                    showModal(
                                                        'question',
                                                        'move_question',
                                                        'move',
                                                        index
                                                    )
                                                "
                                                class="hover:text-slate-700"
                                                href="#"
                                                >Move</a
                                            >
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-if="listShowHide[index]?.visible == true"
                                    class="mx-8 mt-4 text-lg text-gray-600 qImage leading-normal"
                                    v-html="question.question"
                                ></div>
                                <div
                                    v-else
                                    class="ml-3 my-3 cursor-pointer pr-6 hover:scale-95 hover:ease-in duration-200"
                                    @click="showQuestion(index)"
                                >
                                    <span
                                        class="px-1 py-1 flex justify-center font-bold text-slate-300 text-xs border rounded-full p-1 bg-white"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M9 5l7 7-7 7"
                                            /></svg></span>
                                </div>
                                <div
                                    class="relative flex flex-row my-4 mx-8"
                                    v-if="listShowHide[index]?.visible == true"
                                >
                                    <div
                                        class="flex flex-col"
                                        style="width: 100% !important"
                                    >
                                        <span>
                                            <div
                                                :class="[
                                                    question.question_type_id !=
                                                    1
                                                        ? 'text-slate-500 border rounded-lg'
                                                        : '',
                                                ]"
                                            >
                                                <div
                                                    class="answer_key bg-zinc-50 rounded-lg"
                                                >
                                                    <multiple-choice-list
                                                        v-if="
                                                            question.question_type_id ==
                                                            2
                                                        "
                                                        :choices="
                                                            question.choices
                                                        "
                                                        :setup="question.setup"
                                                    />
                                                    <image-selection-list
                                                        v-else-if="
                                                            question.question_type_id ==
                                                            3
                                                        "
                                                        :choices="
                                                            question.choices
                                                        "
                                                        :setup="question.setup"
                                                    />
                                                    <single-textbox-list
                                                        v-else-if="
                                                            question.question_type_id ==
                                                            4
                                                        "
                                                        :setup="question.setup"
                                                    />
                                                    <multiple-textbox-list
                                                        v-else-if="
                                                            question.question_type_id ==
                                                            5
                                                        "
                                                        :setup="question.setup"
                                                        :items="question.items"
                                                    />
                                                    <open-ended-list
                                                        v-else-if="
                                                            question.question_type_id ==
                                                            6
                                                        "
                                                        :setup="question.setup"
                                                    />
                                                    <constant-sum-list
                                                        v-else-if="
                                                            question.question_type_id ==
                                                            7
                                                        "
                                                        :items="question.items"
                                                        :setup="question.setup"
                                                    />
                                                    <slider-list
                                                        v-else-if="
                                                            question.question_type_id ==
                                                            8
                                                        "
                                                        :items="question.items"
                                                        :values="
                                                            question.values
                                                        "
                                                        :setup="question.setup"
                                                    />
                                                    <ranking-list
                                                        v-else-if="
                                                            question.question_type_id ==
                                                            9
                                                        "
                                                        :setup="question.setup"
                                                        :items="question.items"
                                                    />
                                                    <star-rating-list
                                                        v-else-if="
                                                            question.question_type_id ==
                                                            10
                                                        "
                                                        :items="question.items"
                                                        :setup="question.setup"
                                                    />
                                                    <matrix-choice-list
                                                        v-else-if="
                                                            question.question_type_id ==
                                                            11
                                                        "
                                                        :columns="
                                                            question.columns
                                                        "
                                                        :rows="question.rows"
                                                        :setup="question.setup"
                                                    />
                                                    <matrix-multi-list
                                                        v-else-if="
                                                            question.question_type_id ==
                                                            12
                                                        "
                                                        :items="question.items"
                                                        :rows="question.rows"
                                                        :setup="question.setup"
                                                    />
                                                    <memory-test-desc-list
                                                        v-else-if="
                                                            question.question_type_id ==
                                                            13
                                                        "
                                                        :items="question.items"
                                                        :setup="question.setup"
                                                    />
                                                    <memory-test-comparison-list
                                                        v-else-if="
                                                            question.question_type_id ==
                                                            14
                                                        "
                                                        :itemsA="question.item1"
                                                        :itemsB="question.item2"
                                                        :setup="question.setup"
                                                    />
                                                </div>
                                            </div>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="absolute top-0 left-0 z-50 duration-300 transition-all w-screen"
                :class="!showSetting ? 'invisible' : ' visible'"
            >
                <Setting
                    :questions="$page.props.questions"
                    :showSetting="showSetting"
                    @showSettingHandler="showSettingHandler"
                />
            </div>
            <div
                class="absolute top-0 left-0 z-50 duration-300 transition-all w-screen"
                :class="!showGallery ? 'invisible' : ' visible'"
            >
                <GalleryVue
                    :showGallery="showGallery"
                    @showGalleryHandler="showGalleryHandler"
                />
            </div>
        </template>
        <template #modals-question>
            <add-edit-question-modal
                :modal="modals.question"
                @showModal="showModal"
                @close="closeModal('question', 'add_edit')"
            />

            <copy-question-modal
                :modal="modals.question"
                @close="closeModal('question', 'copy')"
            />

            <delete-question-modal
                :modal="modals.question"
                @close="closeModal('question', 'delete')"
            />

            <skip-logic-modal
                :modal="modals.question"
                @close="closeModal('question', 'skip_logic')"
            />

            <move-question-modal
                :modal="modals.question"
                @close="closeModal('question', 'move_question')"
            />

            <save-as-template-modal
                :modal="modals.question"
                @close="closeModal('question', 'save_template')"
            />

            <select-template-modal
                :modal="modals.question"
                @close="closeModal('question', 'select_template')"
            />
        </template>
    </question-layout>
</template>

<style scoped>
.varNum {
    opacity: 0;
    transition: 0.3s;
}
.qCont:hover .varNum {
    opacity: 1;
}

.qImage :deep() img {
    margin-top: 1vmin;
    margin-bottom: 1vmin;
    height: 15rem;
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
    padding: 1rem;
    padding-left: 2.7rem;
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
</style>
 -->
 <script></script>
 <template></template>