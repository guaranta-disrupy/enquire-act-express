<!-- <template>
	<AppLayout title="Project Settings">
		<template #header>
			<ProjectsDropdown destination="projects.settings" />
		</template>

		<div>
			<div class="max-w-7xl mx-auto py-4 sm:px-6 lg:px-8">
				<Link class="mr-1" :href="route('groups.index', project)">
                    <ActionButton variant="primary" size="md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>Back
                    </ActionButton>
                </Link>
                <Link :href="route('variables.index', project)">
                    <ActionButton variant="primary" size="md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.649 3.084A1 1 0 015.163 4.4 13.95 13.95 0 004 10c0 1.993.416 3.886 1.164 5.6a1 1 0 01-1.832.8A15.95 15.95 0 012 10c0-2.274.475-4.44 1.332-6.4a1 1 0 011.317-.516zM12.96 7a3 3 0 00-2.342 1.126l-.328.41-.111-.279A2 2 0 008.323 7H8a1 1 0 000 2h.323l.532 1.33-1.035 1.295a1 1 0 01-.781.375H7a1 1 0 100 2h.039a3 3 0 002.342-1.126l.328-.41.111.279A2 2 0 0011.677 14H12a1 1 0 100-2h-.323l-.532-1.33 1.035-1.295A1 1 0 0112.961 9H13a1 1 0 100-2h-.039zm1.874-2.6a1 1 0 011.833-.8A15.95 15.95 0 0118 10c0 2.274-.475 4.44-1.332 6.4a1 1 0 11-1.832-.8A13.949 13.949 0 0016 10c0-1.993-.416-3.886-1.165-5.6z" clip-rule="evenodd" />
                        </svg>Variables
                    </ActionButton>
                </Link>

                <JetSectionBorder />

                <div class="overflow-y-auto h-[65vh]">
                    <SurveyLink :project="project" />

                    <JetSectionBorder />

                    <RenameProjectForm :project="project" />

                    <JetSectionBorder />

                    <TextsForm :project="project" />

                    <JetSectionBorder />

                    <CopyProjectForm :project="project" />

                    <JetSectionBorder />

                    <DeleteProjectForm :project="project" />
                </div>

                
                
			</div>
		</div>
	</AppLayout>
</template> -->

<script setup>
import Layout from '../../Layouts/Layout.vue';
import ProjectDropdown from '@/Extra/ProjectDropdown.vue';
import SmallButtonVue from '../../Components/SmallButton.vue';
import RenameProjectForm from './RenameProjectForm.vue';
import JetSectionBorder from '@/Jetstream/SectionBorder.vue';
import DeleteProjectForm from './DeleteProjectForm.vue';
import SurveyLink from './SurveyLink.vue';
import TextsForm from './TextsForm.vue';
import CopyProjectForm from './CopyProjectForm.vue';

import { Link } from '@inertiajs/vue3';

const props = defineProps(['project']);
</script>

<template>
    <Layout title="Project Settings">
        <template #header>
            <div class="inline-flex items-center">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
				</svg>&nbsp;
                Projects > {{ project.name }} > Settings
			</div>
        </template>
        <div>
            <div class="max-w-8xl mx-auto px-2">
                <div class="flex justify-between mb-2 rounded-xl shadow-md py-2 px-2 bg-white">
                    <div class="inline-flex items-center">
                        <Link :href="route('groups.index', project)" class="inline-flex items-center text-sm text-sky-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Back
                        </Link>
                        <ProjectDropdown destination="projects.settings" class="mx-3" />
                    </div>
                    <div class="inline-flex items-center">
                        <Link :href="route('variables.index', project)">
                            <SmallButtonVue>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.649 3.084A1 1 0 015.163 4.4 13.95 13.95 0 004 10c0 1.993.416 3.886 1.164 5.6a1 1 0 01-1.832.8A15.95 15.95 0 012 10c0-2.274.475-4.44 1.332-6.4a1 1 0 011.317-.516zM12.96 7a3 3 0 00-2.342 1.126l-.328.41-.111-.279A2 2 0 008.323 7H8a1 1 0 000 2h.323l.532 1.33-1.035 1.295a1 1 0 01-.781.375H7a1 1 0 100 2h.039a3 3 0 002.342-1.126l.328-.41.111.279A2 2 0 0011.677 14H12a1 1 0 100-2h-.323l-.532-1.33 1.035-1.295A1 1 0 0112.961 9H13a1 1 0 100-2h-.039zm1.874-2.6a1 1 0 011.833-.8A15.95 15.95 0 0118 10c0 2.274-.475 4.44-1.332 6.4a1 1 0 11-1.832-.8A13.949 13.949 0 0016 10c0-1.993-.416-3.886-1.165-5.6z" clip-rule="evenodd" />
                                </svg>Variables
                            </SmallButtonVue>
                        </Link>
                    </div>
                </div>
                <div class="overflow-y-auto c shadow rounded-xl bg-white">
                    <div class="p-4">
                        <SurveyLink :project="project" />
                        <JetSectionBorder />
                        <RenameProjectForm :project="project" />
                        <JetSectionBorder />
                        <TextsForm :project="project" />
                        <JetSectionBorder />
                        <CopyProjectForm :project="project" />
                        <JetSectionBorder />
                        <DeleteProjectForm :project="project" />
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
<style scoped="true">
    .c {
        height: calc(100vh - 120px);
    }
    
</style>