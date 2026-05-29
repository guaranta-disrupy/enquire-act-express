<script setup>
import JetActionSection from '@/Components/ActionSection.vue';
import SecondaryButtonVue from '../../Components/SecondaryButton.vue';
import JetActionMessage from '@/Jetstream/ActionMessage.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Helpers from '../../Mixins/Helpers';
import { onMounted } from 'vue';

const { toast } = Helpers();

const props = defineProps(['project']);

const form = useForm({
	team_id: null
});

onMounted(() => {
	form.team_id = usePage().props.auth.user.current_team_id;
});

const copyProject = () => {
	form.post(route('projects.copy', props.project), {
		preserveScroll: true,
		onSuccess: () => toast.success('Project successfully copied.')
	});
};
</script>

<template>
	<jet-action-section>
		<template #title>
			Copy Project
		</template>

		<template #description>
			Click the copy button to duplicate the project's contents and settings.
		</template>

		<template #content>

			<div class="mt-5 flex items-center justify-start">
				<select class="form-select mr-2" v-model="form.team_id">
					<option :value="key" v-for="(team, key) in $page.props.teams">
						{{ team }}
					</option>
				</select>
				<SecondaryButtonVue variant="primary" size="md" @click="copyProject">
					<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
						<path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
					</svg>
					&nbsp;Copy project
				</SecondaryButtonVue>
				<jet-action-message :on="form.recentlySuccessful" class="ml-3">
	                Project copied.
	            </jet-action-message>
			</div>
		</template>
	</jet-action-section>
</template>