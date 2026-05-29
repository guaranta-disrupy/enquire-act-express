<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import S4Button from "@/S4/Button.vue";
import S4Pagination from "@/S4/Pagination.vue";
import AddEditQuestionnaireModal from "./modals/AddEditQuestionnaire.vue";
import JetDropdown from "@/Jetstream/Dropdown.vue";
import JetDropdownLink from "@/Jetstream/DropdownLink.vue";
import { S4Table } from "@/S4";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
    faArrowLeft,
    faChartBar,
    faChevronDown,
    faChevronRight,
    faCopy,
    faEdit,
    faExternalLinkAlt,
    faSortDown,
    faTrashAlt,
    faCogs,
    faEye,
} from "@fortawesome/free-solid-svg-icons";
import { ref, onBeforeMount, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import helpers from "@/helpers.js";

const { assign, toast, random_str } = helpers();

const props = defineProps([
    "questionnaires",
    "project",
    "projects",
    "engine_url",
    "themes",
    "jetstream",
]);




const modals = ref({
    questionnaire: {
        add_edit: false,
        method: "add",
        data: null,
    },
});

const copy = ref({
    open: false,
    data: null,
});

const faIcon = ref({
    cheveron_right: faChevronRight,
    cheveron_down: faChevronDown,
    trash: faTrashAlt,
    copy: faCopy,
    edit: faEdit,
    chart: faChartBar,
    link: faExternalLinkAlt,
    arrow_left: faArrowLeft,
    sort_down: faSortDown,
    cogs: faCogs,
    eye: faEye,
});
const searchQ = ref("");

const searchQuestionnaire = computed(() => {
    return props.questionnaires.data.filter((questionnaires) => {
        return questionnaires.name
            .toLowerCase()
            .includes(searchQ.value.toLowerCase());
    });
});

onBeforeMount(() => {
    // console.log( props.questionnaires.data)
    // $state.currentQuestionnaires = props.questionnaires.data
});

const showModal = (table, modal, method, index = -1) => {

    modals.value[table][modal] = true;
    modals.value[table].method = method;
    if (index >= 0) {
        modals.value[table].data = assign(props.questionnaires.data[index]);
    } else {
        modals.value[table].data = null;
    }

};
const closeModal = (table, modal) => {
    modals.value[table][modal] = false;
    modals.value[table].data = null;
};
const headers = ref([
    {
        label: "CODE",
        class: "pl-6 py-5 font-semibold w-1/6 rounded-tl-xl",
    },
    {
        label: "NAME",
        class: "pr-6 py-2 font-semibold w-1/4",
    },
    {
        label: "DETAILS",
        class: "px-6 py-2 font-semibold w-1/3",
    },
    {
        label: "CREATED BY",
        class: " py-2 font-semibold w-1/5",
    },
    {
        label: "",
        class: "w-1/8 rounded-tr-xl",
    },
]);

const copyURL = (code) => {
    try {
        var data = document.querySelector(`#${code}`);
        data.setAttribute("type", "text");
        data.focus();
        data.select();
        data.setSelectionRange(0, 99999); // For mobile devices
        console.log(data);
        document.execCommand("copy");
        toast.success(`Copied`, {
            timeout: 2000,
        });
    } catch (e) {
        console.log(e);
        alert("Cannot copy");
    }
    data.setAttribute("type", "hidden");
    window.getSelection().removeAllRanges();
};
</script>
<template>
    <app-layout title="Questionnaires">
        <template #topbar>
            <div class="flex mb-3 mt-4">
                <Link class="max-w-sm" :href="route('projects.index')">
                    <p
                        class="truncate cursor-pointer font-semibold text-gray-700 hover:text-gray-500 text-lg"
                    >
                        <font-awesome-icon
                            class="mr-1 text-gray-400 font-thin"
                            :icon="faIcon.arrow_left"
                        />
                        {{ props.project.name }}
                    </p>
                </Link>

                <!-- Projects Dropdown -->
                <div class="w-80">
                    <div class="relative">
                        <jet-dropdown
                            align="left"
                            width="60"
                            v-if="props.jetstream.hasTeamFeatures"
                        >
                            <template #trigger>
                                <span class="inline-flex rounded-md">
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-1 py-1 border border-transparent text-sm leading-4 font-medium rounded-full text-gray-500 bg-white hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition"
                                    >
                                        <svg
                                            class="h-5 w-5"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </span>
                            </template>

                            <template #content>
                                <div
                                    class="w-80 overflow-y-scroll"
                                    style="height: 70vmin"
                                >
                                    <div
                                        class="block px-4 py-2 text-xs text-gray-400"
                                    >
                                        Projects
                                    </div>
                                    <template
                                        v-for="(proj, index) in props.projects"
                                        :key="index"
                                    >
                                        <jet-dropdown-link
                                            v-bind:class="[
                                                proj.name === props.project.name
                                                    ? 'bg-gray-100'
                                                    : '',
                                            ]"
                                            v-if="
                                                proj.team_id ===
                                                props.project.team_id
                                            "
                                            :href="
                                                route(
                                                    'questionnaires.index',
                                                    proj.code
                                                )
                                            "
                                        >
                                            <div class="flex items-center">
                                                <svg
                                                    v-if="
                                                        proj.name ==
                                                        props.project.name
                                                    "
                                                    class="mr-2 h-5 w-5 text-green-400"
                                                    fill="none"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    ></path>
                                                </svg>
                                                <div class="w-80 truncate">
                                                    {{ proj.name }}
                                                </div>
                                            </div>
                                        </jet-dropdown-link>
                                    </template>
                                </div>
                            </template>
                        </jet-dropdown>
                    </div>
                </div>

                <div>
                    <!--<div class="col-span-6">
                        <s4-input color="ssrGray" type="text" class="ml-5 mt-1 block w-60" placeholder="Search questionnaire..." v-model="searchQ"/>
                    </div>-->

                    <!-- <form class="group relative">
                        <svg width="20" height="20" fill="#c0c0c0" class="absolute left-8 top-1/2 -mt-2.5 text-slate-400 pointer-events-none group-focus-within:text-blue-500" aria-hidden="true">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" />
                        </svg>
                        <s4-input color="ssrGray" type="text" class="ml-5 mt-1 block w-60 py-2 pl-10" placeholder="Search questionnaire..." v-model="searchQ"/>
                    </form> -->

                    <div style="" class="absolute right-0 bottom-0 p-4">
                        <s4-button
                            size="md"
                            class="drop-shadow-lg"
                            color="orange"
                            @click="
                                showModal('questionnaire', 'add_edit', 'add')
                            "
                            :theme="'floatingBTN'"
                        >
                            <span class="pl-3 pr-3">ADD QUESTIONNAIRE</span>
                        </s4-button>
                    </div>
                </div>
            </div>
        </template>

        <template #content>
            <S4Table :headers="headers">
                <template #body>
                    <tr
                        v-for="(questionnaire, index) in searchQuestionnaire"
                        :key="index"
                        class="text-md border-b border-l border-r border-gray-100 text-slate-500 hover:bg-slate-50"
                    >
                        <td class="">
                            <div class="cursor-pointer">
                                <input
                                    type="hidden"
                                    :id="'a' + questionnaire.code"
                                    :value="
                                        props.engine_url +
                                        '?code=' +
                                        questionnaire.code +
                                        '&'
                                    "
                                />
                                <a
                                    class="px-6 flex items-center"
                                    @click="copyURL('a' + questionnaire.code)"
                                >
                                    <span class="text-sm">
                                        {{ questionnaire.code }}
                                    </span>
                                </a>
                            </div>
                        </td>
                        <td class="truncate">
                            <Link
                                class="pr-6 py-4 items-center"
                                :href="
                                    route('questions.index', {
                                        project_code: props.project.code,
                                        questionnaire_code: questionnaire.code,
                                    })
                                "
                            >
                                <span class="">
                                    <h1>Name</h1>
                                    {{ questionnaire.name }}
                                </span>
                            </Link>
                        </td>
                        <td class="truncate">
                            <Link
                                class="px-6 py-4 items-center"
                                :href="
                                    route('questions.index', {
                                        project_code: props.project.code,
                                        questionnaire_code: questionnaire.code,
                                    })
                                "
                            >
                                <span class="group-hover:text-gray-700">
                                    <h1 class="">Details</h1>
                                    {{ questionnaire.details }}
                                </span>
                            </Link>
                        </td>
                        <td class="">
                            <Link
                                class="pr-6 py-4 flex items-center"
                                :href="
                                    route('questions.index', {
                                        project_code: props.project.code,
                                        questionnaire_code: questionnaire.code,
                                    })
                                "
                            >
                                <span class="group-hover:text-gray-700">
                                    <h1>Created by</h1>
                                    {{
                                        questionnaire.user?.name || "Undefined"
                                    }}
                                </span>
                            </Link>
                        </td>

                        <!-- ACTIONS -->
                        <td class="">
                            <JetDropdown width="48">
                                <template #trigger>
                                    <span
                                        class="hover:scale-110 hover:ease-in duration-200 cursor-pointer bg-slate-50 px-2 py-2 text-slate-500 rounded-full inline-flex items-center hover:bg-blue-50"
                                    >
                                        <!--<svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>-->
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="16"
                                            height="16"
                                            fill="currentColor"
                                            class="bi bi-three-dots"
                                            viewBox="0 0 16 16"
                                        >
                                            <path
                                                d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"
                                            />
                                        </svg>
                                    </span>
                                </template>

                                <template #content>
                                    <div
                                        class="block px-4 py-2 text-xs text-slate-400"
                                    >
                                        Action
                                    </div>

                                    <!-- <JetDropdownLink :href="route('questionnaires.settings', {'project_code': props.project.code, 'questionnaire_code':questionnaire.code})">
                                        <font class="text-slate-500">Settings</font>
                                    </JetDropdownLink> -->

                                    <JetDropdownLink
                                        :href="
                                            route('analysis.index', {
                                                project_code:
                                                    props.project.code,
                                                questionnaire_code:
                                                    questionnaire.code,
                                            })
                                        "
                                    >
                                        <font class="text-slate-500"
                                            >Analysis</font
                                        >
                                    </JetDropdownLink>

                                    <a
                                        @click="
                                            showModal(
                                                'questionnaire',
                                                'add_edit',
                                                'copy',
                                                index
                                            )
                                        "
                                        class="rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap text-sm text-slate-500"
                                        href="#"
                                        >Copy</a
                                    >

                                    <a
                                        @click="
                                            showModal(
                                                'questionnaire',
                                                'add_edit',
                                                'edit',
                                                index
                                            )
                                        "
                                        class="rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap text-sm text-slate-500"
                                        href="#"
                                        >Edit</a
                                    >

                                    <a
                                        @click="
                                            showModal(
                                                'questionnaire',
                                                'add_edit',
                                                'delete',
                                                index
                                            )
                                        "
                                        class="rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap text-sm text-slate-500"
                                        href="#"
                                        >Delete</a
                                    >

                                    <div class="border-t border-gray-100" />

                                    <a
                                        @click="
                                            copyURL('a' + questionnaire.code)
                                        "
                                        class="rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap text-sm text-slate-500 cursor-pointer"
                                        >Copy Link</a
                                    >

                                    <a
                                        :href="
                                            props.engine_url +
                                            '?code=' +
                                            questionnaire.code +
                                            '&preview=1&id=test-' +
                                            random_str(5)
                                        "
                                        target="_blank"
                                        class="rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap text-sm text-slate-500"
                                        href="#"
                                        >Preview</a
                                    >
                                </template>
                            </JetDropdown>
                        </td>
                    </tr>

                    <tr v-if="props.questionnaires.data.length === 0">
                        <td
                            class="border-t px-6 py-4 text-gray-500 text-sm group-hover:text-gray-700 bg-white"
                            colspan="5"
                            style="border-radius: 1rem !important"
                        >
                            <div class="italic text-gray-400 text-md">
                                There are no questionnaires available right now.
                            </div>
                        </td>
                    </tr>
                </template>
            </S4Table>

            <s4-pagination :links="props.questionnaires.links" />
            <add-edit-questionnaire-modal
                :modal="modals.questionnaire"
                @close="closeModal('questionnaire', 'add_edit')"
            />
        </template>
    </app-layout>
</template>

<style scoped>
td span h1 {
    display: none;
    color: black;
}
@media (max-width: 950px) {
    th {
        display: none;
    }
    td {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        width: 100%;
    }
    td span h1 {
        display: block;
        text-align: center;
        text-decoration: underline;
        font-size: 1rem;
        color: rgb(226, 135, 51);
    }
    .mobilePD {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-left: 25px;
    }
    .prjText {
        font-weight: 900;
        font-size: 1rem;
        justify-content: center;
        align-items: center;
        margin-left: 0.5rem;
        display: flex;
    }
}
.ft {
    font-size: 1.5rem;
    padding: 0.2rem;
}

.bt {
    border-radius: 50%;
    width: 2rem;
    height: 2rem;
}

.addQr {
    margin-left: auto;
    order: 2;
}

.headerBtn {
    width: 3.25rem;
}

.headerBtn:hover {
    border-color: lightgrey;
}
</style>
