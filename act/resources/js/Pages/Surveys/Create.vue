<template>
	<app-layout title="New Survey">
		<template #header>
            <h2 class="font-medium text-lg text-gray-800 leading-tight">
                Create Survey
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <create-survey-form :group="group" :project="project" />
            </div>
        </div>
	</app-layout>
</template>
<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import CreateSurveyForm from './CreateSurveyForm.vue';

const props = defineProps(['group', 'project']);
</script>