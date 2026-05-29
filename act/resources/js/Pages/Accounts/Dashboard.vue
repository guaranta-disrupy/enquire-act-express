<template>
	<Layout :title="current_user.name">
		<template #header>
			<div class="inline-flex items-center">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
					<path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
				</svg>&nbsp;
                Accounts > {{ current_user.name }}
			</div>
		</template>
		<div>
			<div class="max-w-8xl mx-auto px-2">
				<div class="flex justify-between mb-2 rounded-xl shadow-md py-2 px-2 bg-white">
					<div class="inline-flex items-center">
						<Link :href="route('accounts.index')" class="inline-flex items-center text-sm text-sky-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Back
                        </Link>
					</div>
				</div>
				<div class="overflow-y-auto c shadow rounded-xl bg-white">
					<div class="p-4">
						<update-form :user="current_user" />
						<jet-section-border />
						<teams :teams="current_user.teams" :user="current_user" />
						<jet-section-border />

                		<status :user="current_user" />
					</div>
					
				</div>
			</div>
		</div>
	</Layout>
</template>
<script setup>
import Layout from '@/Layouts/Layout.vue';
import UpdateForm from './UpdateForm.vue';
import Teams from './Teams.vue';
import JetSectionBorder from '@/Jetstream/SectionBorder.vue';
import Status from './Status.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps(['current_user']);

</script>

<style scoped="true">
    .c {
        height: calc(100vh - 100px);
    }
    
</style>