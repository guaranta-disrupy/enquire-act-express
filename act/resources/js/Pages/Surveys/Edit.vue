<template>
	<AppLayout title="Edit Survey">
		<template #header>
			<h2 class="font-medium text-lg text-gray-800 leading-tight">
				Edit Survey
			</h2>
		</template>

		<div>
			<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
				<EditSurveyForm :project="project" :survey="survey" :group="group" />
			</div>
		</div>
	</AppLayout>
</template>
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import EditSurveyForm from './EditSurveyForm.vue';

const props = defineProps(['project', 'survey', 'group']);
</script>