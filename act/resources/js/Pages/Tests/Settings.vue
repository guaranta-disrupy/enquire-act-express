<template>
	<AppLayout title="Trial Settings">
		<template #header>
			<SurveysDropdown destination="tests.settings" />
		</template>

		<div>
			<div class="max-w-7xl mx-auto">
				<SettingsForm :survey="survey" />
            </div>
		</div>
	</AppLayout>
</template>
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SettingsForm from './SettingsForm.vue';
import SurveysDropdown from '@/Components/SurveysDropdown.vue';

const props = defineProps(['survey']);
</script>