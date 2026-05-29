<script setup>
import JetActionSection from '@/Components/ActionSection.vue';
import JetButton from '@/Jetstream/Button.vue';
import SecondaryButtonVue from '../../Components/SecondaryButton.vue';
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import JetInput from '@/Jetstream/Input.vue';
import JetInputError from '@/Jetstream/InputError.vue';
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps(['project']);

const confirmingProjectDeletion = ref(false);
const password = ref(null);
const form = useForm({
	password: ''
});

const deleteProjectDialog = () => {
	form.reset();
	confirmingProjectDeletion.value = true;
	setTimeout(() => password.value.focus(), 250)
};

const deleteProject = () => {
	form.post(route('projects.destroy', props.project), {
		preserveScroll: true,
		onError: () => password.value.focus(),
	})
};
</script>

<template>
	<jet-action-section>
		<template #title>
			Delete Project
		</template>

		<template #description>
			Permanently delete this project.
		</template>

		<template #content>
			<div class="max-w-xl text-sm text-gray-600">
                Once this project is deleted, all of its resources and data will be permanently deleted. Before deleting this project, please download any data or information that you wish to retain.
            </div>

            <div class="mt-5">
                <SecondaryButtonVue size="md" @click="deleteProjectDialog">
					<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
						<path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
					</svg>&nbsp;
                    Delete project
                </SecondaryButtonVue>
            </div>
		</template>
	</jet-action-section>

	<jet-dialog-modal :show="confirmingProjectDeletion" @close="confirmingProjectDeletion = false">
		<template #title>
			
			Delete Project
		</template>

		<template #content>
			Are you sure you want to delete this project? Once this project is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete this project.

			<div class="mt-4">
				<JetInput type="password" class="mt-1 block w-3/4" placeholder="Password" ref="password" v-model="form.password" @keyup.enter="deleteProject" />
				<JetInputError :message="form.errors.password" class="mt-2" />
			</div>
		</template>

		<template #footer>
			<JetSecondaryButton variant="primary" size="md" @click="confirmingProjectDeletion = false">
                Cancel
            </JetSecondaryButton>

            <JetButton size="md" class="ml-2" @click="deleteProject" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Delete project
            </JetButton>
		</template>
	</jet-dialog-modal>
</template>