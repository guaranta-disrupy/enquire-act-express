<template>
	<app-layout :title="'Filters - ' + project.name">
		<template #header>
			<h2 class="font-medium text-lg text-gray-800 leading-tight">
                Create Filter
            </h2>
		</template>

		<div>
			<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
				<create-filter-form :project="project"></create-filter-form>
			</div>
		</div>
	</app-layout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CreateFilterForm from './CreateFilterForm.vue';

const props = defineProps(['project']);
</script>