<template>
	<app-layout title="Group Settings">
		<template #header>
			<groups-dropdown destination="groups.settings" />
		</template>

		<div>
			<div class="max-w-7xl mx-auto py-4 sm:px-6 lg:px-8">
				<Link class="mr-1" :href="route('groups.index', project)">
                    <action-button variant="primary" size="md">
                        <i class="fa fa-arrow-left"></i>&nbsp; Back
                    </action-button>
                </Link>

                <jet-section-border />
                
                <rename-group-form :group="group" :project="project" />

                <jet-section-border />

                <!-- <settings-form :group="group" :project="project" /> -->
			</div>
		</div>
	</app-layout>
</template>
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import GroupsDropdown from '@/Components/GroupsDropdown.vue';
import JetSectionBorder from '@/Jetstream/SectionBorder.vue';
import RenameGroupForm from './RenameGroupForm.vue';
import ActionButton from '@/Components/ActionButton.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps(['project', 'group']);
</script>