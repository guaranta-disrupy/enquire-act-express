<script setup>
import Layout from '@/Layouts/Layout.vue';
import CustomCard from '@/Components/Card.vue';
import JetButton from "@/Jetstream/Button.vue";
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import JetSecondaryButton from '@/Components/SecondaryButton.vue';
import JetDangerButton from '@/Jetstream/DangerButton.vue';
import SmallButton from '@/Components/SmallButton.vue';
import JetLabel from '@/Jetstream/Label.vue';
import ProjectDropdown from '@/Extra/ProjectDropdown.vue';
import JetConfirmationModal from '@/Jetstream/ConfirmationModal.vue';

import head from 'lodash/head';
import { copyText } from 'vue3-clipboard';

import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Helpers from '../../Mixins/Helpers';

const props = defineProps(['groups', 'project', 'app', 'teams']);

const confirmingGroupDeletion = ref(false);
const confirmingSurveyDeletion = ref(false);
const form = useForm({});
const selectedGroup = ref(null);
const selectedSurvey = ref(null);
const modals = ref({ copySurvey: false });

const { toast } = Helpers();


onMounted(() => {
    selectedGroup.value = head(props.groups);
    // getTeams();
    copyForm.team = usePage().props.auth.user.current_team_id;
    copyForm.project = props.project.uuid;
    getProjects();
});

const copyLink = (val) => {
    copyText(val, undefined, (error, event) => {
        if (error) {
            toast.error("Error copying the link");
        } else {
            toast.success("Link copied");
        }
    });
};

const deleteGroupDialog = (group) => {
    selectedGroup.value = group;
    confirmingGroupDeletion.value = true;
};

const deleteGroup = () => {
    form.delete(route('groups.destroy', [props.project, selectedGroup.value]), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingGroupDeletion.value = false;
            selectedGroup.value = null;
        }
    })
};

const deleteSurveyDialog = (group, survey) => {
    selectedSurvey.value = survey;
    selectedGroup.value = group;
    confirmingSurveyDeletion.value = true;
};

const deleteSurvey = () => {
    form.delete(route('surveys.destroy', [selectedGroup.value, selectedSurvey.value]), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingSurveyDeletion.value = false;
            selectedSurvey.value = null;
            selectedGroup.value = null;
            toast.success("Survey successfully deleted");
        }
    })
};

const copySurveyDialog = (survey) => {
    selectedSurvey.value = survey;
    modals.value = { copySurvey: true };
};

const copySurvey = () => {
    form.get(route('groups.copySurvey', [props.project, copyForm.group, selectedSurvey.value]), {
        preserveScroll: true,
        onSuccess: () => {
            selectedGroup.value = null;
            selectedSurvey.value = null;
            modals.value = { copySurvey: false };
            toast.success('Survey successfully copied');
        }
    });
};

const teamsList = ref([]);
const getTeams = () => {
    if (teamsList.value.length === 0) {
        axios.get('v1/teams').then((response) => {
            teamsList.value = response.data;
            copyForm.team = usePage().props.auth.user.current_team_id;
            copyForm.project = props.project.uuid;
            getProjects();
        });
    }

};

const copyForm = useForm({
    team: null,
    project: null,
    group: null
});
const projectsList = ref([]);
const getProjects = () => {
    axios.get('v1/projects/' + copyForm.team).then((response) => {
        projectsList.value = response.data;
        if (groupsList.value.length === 0) {
            getGroups();
        }
    });
};

const groupsList = ref([]);
const getGroups = () => {
    if (copyForm.project) {
        axios.get('v1/groups/' + copyForm.project).then((response) => {
            groupsList.value = response.data;
        });
    }
};
</script>

<template>
    <Layout :title="project.name">
        <template #header>
            <div class="inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>&nbsp;
                {{ project.name }}
            </div>
        </template>
        <div>
            <div class="max-w-8xl mx-auto px-2">
                <div class="flex justify-between mb-2 rounded-xl shadow-md py-2 px-2 bg-white">
                    <div class="inline-flex items-center">
                        <Link :href="route('projects.index')" class="inline-flex items-center text-sm text-sky-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                              <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Back
                        </Link>
                        <project-dropdown destination="groups.index" class="ml-3" />
                    </div>
                    <div class="inline-flex items-center">
                        <Link :href="route('groups.create', project)" class="mr-1">
                            <SmallButton>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                        			<path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        		</svg>
                                Create Group
                            </SmallButton>
                        </Link>
                        <Link :href="route('projects.settings', project)" class="mr-1">
                            <SmallButton>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                                            </svg>
                                Project Settings
                            </SmallButton>
                        </Link>
                        <Link :href="route('variables.index', project)" class="mr-1">
                            <SmallButton>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.649 3.084A1 1 0 015.163 4.4 13.95 13.95 0 004 10c0 1.993.416 3.886 1.164 5.6a1 1 0 01-1.832.8A15.95 15.95 0 012 10c0-2.274.475-4.44 1.332-6.4a1 1 0 011.317-.516zM12.96 7a3 3 0 00-2.342 1.126l-.328.41-.111-.279A2 2 0 008.323 7H8a1 1 0 000 2h.323l.532 1.33-1.035 1.295a1 1 0 01-.781.375H7a1 1 0 100 2h.039a3 3 0 002.342-1.126l.328-.41.111.279A2 2 0 0011.677 14H12a1 1 0 100-2h-.323l-.532-1.33 1.035-1.295A1 1 0 0112.961 9H13a1 1 0 100-2h-.039zm1.874-2.6a1 1 0 011.833-.8A15.95 15.95 0 0118 10c0 2.274-.475 4.44-1.332 6.4a1 1 0 11-1.832-.8A13.949 13.949 0 0016 10c0-1.993-.416-3.886-1.165-5.6z" clip-rule="evenodd" />
                                </svg>Variables
                            </SmallButton>
                        </Link>
                        <Link :href="route('filters.index', project)" class="mr-1">
                            <SmallButton>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
                                </svg>
                                Filters
                            </SmallButton>
                        </Link>
                    </div>
                </div>
                <div class="overflow-auto c pb-5">
                    <custom-card v-for="(group, key) in groups" :key="'785' + key" class="mt-3">
                        <template #header>
                            <div class="lg:flex lg:items-center lg:justify-between">
                                <div class="flex-1 min-w-0">
                                    <h2 class="prose prose-xl font-bold text-gray-700  sm:truncate">
                                        {{ group.name }}
                                    </h2>
                                    <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                                        <div class="mt-2 flex items-center text-gray-500 text-xs">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-auto" viewBox="0 0 20 20" fill="currentColor">
                                              <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                            </svg>
                                            {{ group.user.name }}
                                        </div>
                                        <div class="mt-2 flex items-center text-gray-500 text-xs">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ datetime(group.created_at) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-5 flex lg:mt-0 lg:ml-4">
                                    <Link :href="route('surveys.create', group)" class="mr-1">
                                        <SmallButton class="inline-flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                            </svg>
                                            Add Survey
                                        </SmallButton>
                                    </Link>
                                    <Link :href="route('groups.settings', [project, group])" class="mr-1">
                                        <SmallButton class="inline-flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                                            </svg>
                                            Settings
                                        </SmallButton>
                                    </Link>
                                    <div class="mr-1">
                                        <SmallButton class="flex items-center" @click="deleteGroupDialog(group)">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        delete
                                        </SmallButton>
                                    </div>

                                </div>
                            </div>
                        </template>
                        <template #content>
                             <div v-if="group.surveys.length > 0" class="pb-3">
                                 <table class="min-w-full divide-y divide-gray-200 table-fixed">
                                     <thead class="bg-white">
                                         <tr>
                                             <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                 ID
                                             </th>
                                             <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                 Survey Name
                                             </th>
                                             <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                 trials
                                             </th>
                                             <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                 Last Modified
                                             </th>
                                             <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                 Created by
                                             </th>
                                             <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase" v-if="project.config.records.show_completes">
                                                 Completes
                                             </th>
                                             <th scope="col" class="p-4">
                                                <span class="sr-only">Edit</span>
                                            </th>
                                         </tr>
                                     </thead>
                                     <tbody class="bg-white divide-y divide-gray-200">
                                         <tr class="hover:bg-gray-100" v-for="(survey) in group.surveys" :key="survey.id">
                                             <td class="py-3 px-6 text-xs font-medium text-gray-900 whitespace-nowrap">
                                                 <a title="Copy link for fluent" class="inline-flex items-center" href="javascript:;" @click="copyLink(app.engine_preview + 'survey_id=' + survey.id + '&')">
                                                    {{ survey.id }}
                                                </a>
                                             </td>
                                             <td class="py-3 px-6 text-sm font-medium text-gray-900 whitespace-nowrap max-w-xs">
                                                 <Link :href="route('tests.index', survey)" :title="survey.name">
                                                    <span class="">
                                                        <p class="truncate">
                                                            {{ survey.name }}
                                                        </p>
                                                    </span>
                                                </Link>
                                             </td>
                                             <td class="py-3 px-6 text-xs font-medium text-gray-600 whitespace-nowrap">
                                                 <small>
                                                     {{ survey.test_count }}
                                                 </small>

                                             </td>
                                             <td class="py-3 px-6 text-xs font-medium text-gray-600 whitespace-nowrap">
                                                 <small>
                                                     {{ datetime(survey.updated_at) }}
                                                 </small>
                                             </td>
                                             <td class="py-3 px-6 text-xs font-medium text-gray-600 whitespace-nowrap">
                                                 <small>
                                                     {{ survey.user.name }}
                                                 </small>
                                             </td>
                                             <td class="py-3 px-6 text-xs font-medium text-green-500 whitespace-nowrap" v-if="project.config.records.show_completes">
                                                 {{ survey.completes }}
                                             </td>
                                             <td class="py-3 px-6 text-xs font-medium text-gray-900 whitespace-nowrap uppercase">

                                                 <a :href="app.engine_preview + 'survey_id=' + survey.id + '&preview=1&id=test'" target="_blank" class="mr-1 text-blue-600 text-xs">
                                                    Preview
                                                </a>

                                                <Link :href="route('analysis.index', survey)" class="mr-1 text-xs text-blue-600 uppercase">
                                                        Responses
                                                </Link>

                                                <Link :href="route('surveys.edit', [group, survey])" class="mr-1 text-xs text-blue-600 uppercase">
                                                    Rename
                                                </Link>

                                                <a @click="deleteSurveyDialog(group, survey)" class="mr-1 text-xs text-blue-600 uppercase" href="javascript:;">
                                                    Delete
                                                </a>

                                                <a href="javascript:;" class="mr-1 text-xs text-blue-600 uppercase" @click="copySurveyDialog(survey)">
                                                    Copy
                                                </a>

                                             </td>
                                         </tr>
                                     </tbody>
                                 </table>
                             </div>
                            <div class="px-4 py-3 text-gray-500" v-else>
                                No surveys found.
                            </div>
                        </template>
                    </custom-card>
                </div>

                <div v-if="groups.length === 0" class="mt-3">
                    No groups found.
                </div>
            </div>
        </div>

    </Layout>
    <JetConfirmationModal :show="confirmingGroupDeletion" @close="confirmingGroupDeletion = false">
        <template #title>
            Delete Group
        </template>

        <template #content>
            Are you sure you want to delete this group? Once this group is deleted, all of its resources and data will be permanently deleted.
        </template>

        <template #footer>
            <jet-secondary-button @click="confirmingGroupDeletion = false">
                nevermind
            </jet-secondary-button>

            <jet-danger-button class="ml-2" @click="deleteGroup" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                delete group
            </jet-danger-button>
        </template>
    </JetConfirmationModal>

    <JetConfirmationModal :show="confirmingSurveyDeletion" @close="confirmingSurveyDeletion = false">
        <template #title>
            Delete Survey
        </template>

        <template #content>
            Are you sure you want to delete this survey?
        </template>

        <template #footer>
            <jet-secondary-button @click="confirmingSurveyDeletion = false">
                nevermind
            </jet-secondary-button>

            <jet-danger-button class="ml-2" @click="deleteSurvey" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                delete survey
            </jet-danger-button>
        </template>
    </JetConfirmationModal>

    <jet-dialog-modal :show="modals.copySurvey" @close="modals.copySurvey = false">
        <template #title>
            Copy Survey
        </template>

        <template #content>
            <!-- <jet-label value="Group" />
            <select v-model="selectedGroup" class="form-select w-3/4 xs:w-full">
                <option v-for="group in groups" :key="group.id" :value="group">
                    {{ group.name }}
                </option>
            </select> -->
            <template v-if="teamsList">
                <jet-label value="Team"></jet-label>
                <select v-model="copyForm.team" class="form-select w-full xs:w-full" @change="getProjects">
                    <option v-for="team in teams" :value="team.id" :key="team.id">
                        {{ team.name }}
                    </option>
                </select>
            </template>
            <template v-if="projectsList">
                <jet-label value="Project"></jet-label>
                <select v-model="copyForm.project" class="form-select w-full xs:w-full" @change="getGroups">
                    <option v-for="project in projectsList" :value="project.uuid" :key="project.id">
                        {{ project.name }}
                    </option>
                </select>
            </template>
            <template v-if="groupsList">
                <jet-label value="Group"></jet-label>
                <select v-model="copyForm.group" class="form-select w-full xs:w-full">
                    <option v-for="group in groupsList" :value="group.id" :key="group.id">
                        {{ group.name }}
                    </option>
                </select>
            </template>
        </template>

        <template #footer>
            <jet-secondary-button @click="modals.copySurvey = false">
                nevermind
            </jet-secondary-button>

            <jet-button type="button" class="ml-2" @click="copySurvey">
                copy survey
            </jet-button>
        </template>
    </jet-dialog-modal>
</template>

<style scoped="true">
    .c {
        height: calc(100vh - 120px);
    }
    
</style>
