<template>
    <Layout :title="survey.name">
        <template #header>
            <div class="inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>&nbsp;
                {{ project.name }} > {{ survey.name }}
            </div>
        </template>
        <div>
            <div class="max-w-7xl mx-auto px-2">
                <div class="flex justify-between mb-2 rounded-xl shadow-md py-2 px-2 bg-white">
                    <div class="inline-flex items-center">
                        <Link :href="route('groups.index', project)" class="inline-flex items-center text-sm text-sky-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Back
                        </Link>
                        <ProjectDropdown destination="groups.index" class="mx-3" />
                        <SurveyDropdown class="" destination="tests.index" />
                    </div>
                    <div class="inline-flex items-center">
                        <Link :href="route('tests.create', survey)" class="mr-1">
                            <SmallButton>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                add Trial ({{ tests.length }})
                            </SmallButton>
                        </Link>
                        <!-- <Link :href="route('tests.settings', survey)" class="mr-1"> -->
                            <SmallButton @click="settingModal=true" class="mr-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                                </svg>
                                Settings
                            </SmallButton>
                        <!-- </Link> -->
                        <div>
                            <a target="_blank" :href="app.engine_preview + 'survey_id=' + survey.id + '&preview=1&id=test'" class="inline-flex items-center px-2 py-1 bg-white border border-gray-300 rounded-md font-medium text-xs text-gray-700 uppercase tracking-wide shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring focus:ring-blue-200 active:text-gray-800 active:bg-gray-50 disabled:opacity-25 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                    <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                                </svg>
                                Preview
                            </a>
                        </div>
                    </div>
                </div>
                <div class="h-[70vh] flex items-center" v-if="tests.length === 0">
                    <div class="mx-auto text-gray-600 prose-2xl font-medium">
                        No record found
                    </div>
                </div>
                <div class="overflow-y-auto overflow-x-hidden c pb-5 rounded-xl">
                    <custom-card v-for="(test, i) in tests" :key="i" class="mt-4">
                        <template #header>
                            <div class="lg:flex lg:items-center lg:justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-1 text-gray-700">
                                        <div class="flex items-center text-sm rounded-lg bg-sky-100 px-2 font-medium">
                                            Trial {{ i + 1 }}
                                        </div>
                                        <template v-if="test.config.type == 'descriptive'">
                                            <span class="flex items-center text-sm rounded-lg bg-sky-100 px-2 font-medium">
                                                Descriptive
                                            </span>
                                        </template>
                                        <template v-if="test.config.type == 'trial'">
                                            <span class="flex items-center text-sm rounded-lg bg-sky-100 px-2 font-medium">
                                                <template v-if="test.config.main_trial">
                                                    Main Trial
                                                </template>
                                                <template v-else>
                                                    Practice Trial
                                                </template>
                                            </span>
                                        </template>
                                        <div class="flex items-center text-sm rounded-lg bg-sky-100 px-2 font-medium">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            &nbsp;<em>{{ test.editor.name }}</em>&nbsp;{{ test.updated_readable }}
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-5 flex lg:mt-0 lg:ml-4">
                                    <Link :href="route('tests.edit', [survey, test])" class="mr-1">
                                        <SmallButton>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                            update
                                        </SmallButton>
                                    </Link>
                                    <div>
                                        <SmallButton @click="copyTrialDialog(test)" class="mr-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            copy
                                        </SmallButton>
                                    </div>
                                    <div>
                                        <SmallButton @click="copyToTrialDialog(test)" class="mr-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            copy to
                                        </SmallButton>
                                    </div>
                                    <div>
                                        <SmallButton @click="moveTrialDialog(test)" type="button" class="mr-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                                Move
                                        </SmallButton>
                                    </div>
                                    <div>
                                        <SmallButton @click="deleteTrialDialog(test)" class="mr-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            delete
                                        </SmallButton>
                                    </div>
                                </div>
                            </div>
                            <div class="p-2 mt-3 overflow-auto lg:overflow-visible sm:rounded-lg p-6 border-dashed py-2 border" v-if="test.config.title">
                                <span class="header text-sm font-medium text-gray-700" v-html="test.config.title"></span>
                            </div>
                        </template>

                        <template #content>
                            <div class="px-4 pb-4 overflow-auto lg:overflow-visible mt-3 sm:rounded-lg mx-2 my-1 border-dashed border py-2" v-if="test.config.main_header">
                                <span class="header text-sm text-gray-700" v-html="test.config.main_header"></span>
                            </div>
                            <div class="overflow-auto p-3 max-h-[30vh]">
                                <table class="min-w-full divide-y divide-gray-200 table-fixed" v-if="test.primes.length > 0">
                                    <thead>
                                        <tr>
                                            <th class="py-2 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                #
                                            </th>
                                            <th class="py-2 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                Index
                                            </th>
                                            <th class="py-2 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                Prime
                                            </th>
                                            <th class="py-2 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                Equivalent
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr class="hover:bg-gray-100" v-for="(prime, i) in test.primes" :key="i">
                                            <td class="py-2 px-6 text-sm text-gray-500 whitespace-nowrap">
                                                {{ i + 1 }}
                                            </td>
                                            <td class="py-2 px-6 text-sm text-gray-500 whitespace-nowrap">
                                                {{ prime.index }}
                                            </td>
                                            <td class="py-2 px-6 text-sm text-gray-500" :class="{ 'w-28': hasImage(prime.prime) }">
                                                <template v-if="prime.hasOwnProperty('config')">
                                                    <strong v-if="prime.config.conditions.length > 0" class="text-gray-900">
                                                        *
                                                    </strong>
                                                </template>
                                                <span v-html="prime.prime">
                                                </span>
                                            </td>
                                            <td class="py-2 px-6 text-sm text-gray-500" >
                                                {{ prime.equivalent }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="overflow-y-auto overflow-x-hidden mt-3 pt-2 px-2 max-h-[30vh]">
                                <table class="min-w-full divide-y divide-gray-200 table-fixed" v-if="test.targets.length > 0">
                                    <thead class="">
                                        <tr>
                                            <th class="py-2 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                #
                                            </th>
                                            <th class="py-2 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                Index
                                            </th>
                                            <th class="py-2 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                Target
                                            </th>
                                            <th class="py-2 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                Equivalent
                                            </th>
                                            <th class="py-2 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                Value
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr class="hover:bg-gray-100" v-for="(target, i) in test.targets" :key="'tr' + i">
                                            <td class="py-1 px-6 text-xs font-medium text-gray-500 whitespace-nowrap">
                                                {{ i + 1 }}
                                            </td>
                                            <td class="py-1 px-6 text-sm text-gray-700 whitespace-nowrap">
                                                {{ target.index }}
                                            </td>
                                            <td class="py-1 px-6 text-sm text-gray-700" :class="{ 'w-28': hasImage(target.target) }">
                                                <div v-html="target.target">
                                                </div>
                                            </td>
                                            <td class="py-1 px-6 text-sm text-gray-700" >
                                                {{ target.equivalent }}
                                                <span class="bg-gray-500 text-xs text-gray-50 rounded-md px-1 ml-1" v-if="target.can_unselect">
                                                    Unselect
                                                </span>
                                            </td>
                                            <td class="py-1 px-6 text-sm text-gray-700" >
                                                {{ target.value }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-4 flex flex-col" v-if="test.config?.conditions">
                                <div class="text-sm">
                                    Logic: <span class="font-mono text-xs pl-4">
                                        {{ $state.criteriaToString(test.config.conditions) }}
                                    </span>
                                </div>
                            </div>
                        </template>
                    </custom-card>
                </div>
            </div>
        </div>
        <jet-dialog-modal :show="confirmingTrialDeletion" @close="confirmingTrialDeletion = false">
        	<template #title>
        		Delete Trial
        	</template>

        	<template #content>
        		Are you sure you want to delete this trial?
        	</template>

        	<template #footer>
        		<jet-secondary-button @click="confirmingTrialDeletion = false">
        			nevermind
        		</jet-secondary-button>

        		<jet-danger-button @click="deleteTrial" class="ml-2" :class="{ 'opacity-25' : form.processing}" :disabled="form.processing">
        			delete trial
        		</jet-danger-button>
        	</template>
        </jet-dialog-modal>

        <jet-dialog-modal :show="confirmingTrialCopy" @close="confirmingTrialCopy = false">
        	<template #title>
        		Copy Trial
        	</template>

        	<template #content>
        		Are you sure you want to copy this trial?
        	</template>

        	<template #footer>
        		<jet-secondary-button @click="confirmingTrialCopy = false">
        			nevermind
        		</jet-secondary-button>

        		<jet-button type="button" @click="copyTrial" class="ml-2" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
        			copy trial
        		</jet-button>
        	</template>
        </jet-dialog-modal>

        <jet-dialog-modal :show="confirmingTrialCopyTo" @close="confirmingTrialCopyTo = false">
        	<template #title>
        		Copy to other survey
        	</template>

        	<template #content>
        		<jet-label value="Survey"></jet-label>
        		<select class="form-select w-3/4 xs:w-full" v-model="selectedSurvey">
        			<option v-for="survey in allSurveys" :value="survey" :key="survey.id">
        				{{ survey.name }}
        			</option>
        		</select>
        	</template>
        	<template #footer>
        		<jet-secondary-button @click="confirmingTrialCopyTo = false">
        			nevermind
        		</jet-secondary-button>

        		<jet-button type="button" @click="copyTrial(false)" class="ml-2" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
        			copy trial
        		</jet-button>
        	</template>
        </jet-dialog-modal>

        <jet-dialog-modal :show="confirmingTrialMove" @close="confirmingTrialMove = false">
        	<template #title>
        		Move round order to
        	</template>

        	<template #content>
        		<select class="form-select w-3/4 xs:w-full" v-model="selectedPart">
        			<option v-for="test in tests" :value="test.part" :key="test.id">
        				Trial {{ test.part }}
        			</option>
        		</select>
        	</template>
            
        	<template #footer>
        		<jet-secondary-button @click="confirmingTrialMove = false">
        			nevermind
        		</jet-secondary-button>

        		<jet-button type="button" @click="moveTrial" class="ml-2" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
        			Move Round
        		</jet-button>
        	</template>
        </jet-dialog-modal>

        <JetDialogModal :show="settingModal" @close="settingModal = false" maxWidth="7xl">
            <template #title>
                Settings
            </template>

            <template #content>
                <SettingsForm :survey="survey" />
            </template>

            <template #footer>
        		<jet-secondary-button @click="settingModal = false">
        			nevermind
        		</jet-secondary-button>

        		<jet-button type="button" @click="saveSettings" class="ml-2" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
        			save settings
        		</jet-button>
        	</template>
            
        </JetDialogModal>
    </Layout>
</template>
<script setup>
import Layout from '@/Layouts/Layout.vue';
import ProjectDropdown from '@/Extra/ProjectDropdown.vue';
import CustomCard from '@/Components/Card.vue';
import JetButton from '@/Components/Button.vue';
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import JetSecondaryButton from '@/Components/SecondaryButton.vue';
import JetDangerButton from '@/Jetstream/DangerButton.vue';
import SmallButton from '@/Components/SmallButton.vue';
import JetLabel from '@/Jetstream/Label.vue';
import SettingsForm from './SettingsForm.vue';
import { Link, useForm } from '@inertiajs/vue3';
import SurveyDropdown from '@/Extra/SurveyDropdown.vue';
import head from 'lodash/head';
import Helpers from '../../Mixins/Helpers';
import { ref, onMounted, onBeforeMount, provide } from 'vue';

const { toast, assign, $state } = Helpers();

const props = defineProps(['project', 'surveys', 'tests', 'survey', 'allSurveys', 'app']);

const confirmingTrialDeletion = ref(false);
const confirmingTrialCopy = ref(false);
const confirmingTrialCopyTo = ref(false);
const selectedTest = ref(null);
const selectedSurvey = ref(null);
const confirmingTrialMove = ref(false);
const selectedPart = ref(null);
const settingModal = ref(false);

const form = useForm({
    config: {}
});

onMounted(() => {
	selectedSurvey.value = head(props.allSurveys);
});

const deleteTrialDialog = (test) =>  {
	selectedTest.value = assign(test);
	confirmingTrialDeletion.value = true;
};

const deleteTrial = () => {
	form.delete(route('tests.destroy', [props.survey, selectedTest.value]), {
		preserveScroll: true,
		onSuccess: () => {
			confirmingTrialDeletion.value = false;
			selectedTest.value = null;
			toast.success("Trial successfully deleted");
		}
	});
};

const copyTrialDialog = (test) => {
	selectedTest.value = assign(test);
	confirmingTrialCopy.value = true;
};

const copyTrial = (copy = true) => {
	form.get(route('tests.copy', [copy ? props.survey : selectedSurvey.value, selectedTest.value]), {
		preserveScroll: true,
		onSuccess: () => {
			confirmingTrialCopy.value = false;
			selectedTest.value = null;
			confirmingTrialCopyTo.value = false;
		}
	});
};

const copyToTrialDialog = (test) => {
	selectedTest.value = assign(test);
	confirmingTrialCopyTo.value = true;
};

const moveTrialDialog = (test) => {
	selectedTest.value = assign(test);
	selectedPart.value = selectedTest.value.part;
	confirmingTrialMove.value = true;
};

const moveTrial = () => {
	form.get(route('tests.move', [props.survey, selectedTest.value, selectedPart.value]), {
		preserveScroll: true,
		onSuccess: () => {
			confirmingTrialMove.value = false;
			selectedTest.value = null;
			selectedPart.value = null;
			toast.success("Trial successfully moved");
		}
	});
};

onBeforeMount(() => {
    form.config = props.survey.config;
    if (form.config == null) {
        form.config = {
            'css': "",
            'js': "",
            'forward_response': false,
            'response_var': 'ast',
            'swipe': false,
            'left_key': 'f',
            'right_key': 'j',
             'fullscreen_text': "For the best experience, please enter fullscreen.",
            'engine_bg_image': null,
            'fullscreen_button': "Enter Fullscreen",
            'dummy_trial': false,
            'click_instructions': 'Click Yes or No.',
            'swipe_instructions': ' Or SWIPE <strong>LEFT</strong> or <strong>RIGHT</strong>',
            'keypress_instructions': "Press <strong>F</strong> if you disagree or <strong>J</strong> if otherwise.",
            'keypress_click': false,
            'theme': 0,
            'redirect': null
        };
    }
    if (form.config?.theme == undefined) {
        form.config.theme = 0;
    }
    if (form.config?.redirect == undefined) {
        form.config.redirect = null;
    }
});

provide('form', form);

const saveSettings = () => {
    form.put(route('tests.saveSettings', props.survey), {
        preserveScroll: true,
        errorBag: 'saveSettings',
        onSuccess: () => {
            toast.success("Settings successfully saved");
            settingModal.value = false;
        }
    });
};

</script>

<style scoped="true">
    .c {
        height: calc(100vh - 120px);
        /* max-width: calc(100vw - 15rem); */
    }

</style>
