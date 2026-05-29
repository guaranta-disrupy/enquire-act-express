<template>
	<jet-form-section @submitted="renameProject">
		<template #title>
			Project Details
		</template>

		<template #description>
			Configure your project that suits to your needs
		</template>

		<template #form>
			<div class="col-span-6">
                <jet-label value="Project Owner" />

                <div class="flex items-center mt-2">
                    <img :src="project.user.profile_photo_url" :alt="project.user.name" class="w-12 h-12 rounded-full object-cover">

                    <div class="ml-4 leading-tight">
                        <div>{{ project.user.name }}</div>
                        <div class="text-gray-700 text-sm">
                            {{ project.user.email }}
                        </div>
                    </div>
                </div>
            </div>

			<div class="col-span-6 sm:col-span-4">
                <jet-label for="name" value="Project Name" />
                <jet-input id="name" type="text" class="mt-1 block w-full" v-model="form.name" autofocus />
                <jet-input-error :message="form.errors.name" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4 mt-3" v-if="form.config.redirects">
                <jet-label for="completes" value="Complete Redirect" />
                <jet-input id="completes" type="text" class="mt-1 block w-full" v-model="form.config.redirects.complete" />
                <jet-input-error :message="form.errors['config.redirects.complete']" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4 mt-3" v-if="form.config.progress">
            	<jet-label>
					<div class="flex items-center">
                        <jet-checkbox v-model:checked="form.config.progress.continue" />

                        <div class="ml-2">
                            Continue where respondent left off
                        </div>
                    </div>
				</jet-label>
            </div>

            <div class="col-span-6 sm:col-span-6" v-if="form.config.records">
            	<jet-label>
					<div class="flex items-center">
                        <jet-checkbox v-model:checked="form.config.records.show_completes" />

                        <div class="ml-2">
                            Display the number of completes <em>(may slow down the loading time of surveys list)</em>
                        </div>
                    </div>
				</jet-label>
            </div>

			<div class="col-span 6 sm:col-span-6" v-if="form.config.theme">
				<jet-label value="Theme"></jet-label>
				<select class="form-select w-3/4 xs:w-full" v-model="form.config.theme">
					<option :value="1">Default</option>
					<option :value="2">Colorful (Single select)</option>
				</select>
			</div>

		</template>

		<template #actions>
			<jet-action-message class="mr-3" :on="form.recentlySuccessful">
				Saved.
			</jet-action-message>

			<SecondaryButtonVue variant="primary" size="md" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="renameProject">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>&nbsp;save
			</SecondaryButtonVue>
		</template>
	</jet-form-section>
</template>

<script setup>
import JetFormSection from '@/Components/FormSection.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetInput from '@/Jetstream/Input.vue';
import JetInputError from '@/Jetstream/InputError.vue';
import JetActionMessage from '@/Jetstream/ActionMessage.vue';
import SecondaryButtonVue from '../../Components/SecondaryButton.vue';
import JetCheckbox from '@/Jetstream/Checkbox.vue';
import { useForm } from '@inertiajs/vue3';
import { onBeforeMount } from 'vue';
import Helpers from '../../Mixins/Helpers';

const props = defineProps(['project']);

const { toast } = Helpers();

const form = useForm({
	name: '',
	config: {}
});

onBeforeMount(() => {
	form.name = props.project.name;
	form.config = props.project.config;
});

const renameProject = () => {
	form.put(route('projects.saveSettings', props.project), {
		preserveScroll: true,
		onSuccess: () => {
			toast.success("Settings saved")
		}
	})
};

</script>