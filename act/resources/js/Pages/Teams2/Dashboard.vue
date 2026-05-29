<template>
    <Layout :title="'Team: ' + current_team.name">
        <template #header>
			<div class="inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
					<path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
				</svg>&nbsp;Teams > {{ current_team.name }}
            </div>
		</template>
        <div>
            <div class="mx-w-8xl mx-auto px-2">
                <div class="flex justify-between mb-2 rounded-xl shadow-md py-2 px-2 bg-white">
					<div class="inline-flex items-center">
						<Link :href="route('teams2.index')" class="inline-flex items-center text-sm text-sky-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Back
                        </Link>
					</div>
				</div>
				<div class="overflow-y-auto c shadow rounded-xl bg-white">
					<div class="p-4">
						<UpdateTeamForm :team="current_team" />

                        <TeamMemberManager :team="current_team" />

                        <ProjectManager :team="current_team" />
					</div>
					
				</div>
            </div>
        </div>
    </Layout>
</template>
<script setup>
import Layout from '@/Layouts/Layout.vue';
import UpdateTeamForm from './UpdateTeamForm.vue';
import TeamMemberManager from './TeamMemberManager.vue';
import ProjectManager from './ProjectManager.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps(['current_team']);

</script>

<style scoped="true">
    .c {
        height: calc(100vh - 115px);
    }
    
</style>