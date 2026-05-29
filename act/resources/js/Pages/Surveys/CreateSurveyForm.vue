<template>
	<jet-form-section @submitted="createSurvey">
		<template #title>
			Survey Details
		</template>

		<template #description>
			Create new survey for group <em>{{ project.name }}/{{ group.name }}</em>
		</template>

		<template #form>
			<div class="col-span-6">
				<jet-label value="Survey Name"></jet-label>
				<jet-input type="text" class="mt-1 block w-full" v-model="form.name" autofocus></jet-input>
				<jet-input-error :message="form.errors.name" class="mt-2"></jet-input-error>
			</div>
		</template>

		<template #actions>
			<jet-action-message :on="form.recentlySuccessful">
				Saved.
			</jet-action-message>

			
			<Link :href="route('groups.index', project)">
				<JetSecondaryButton class="mr-4">
					<i class="fa fa-arrow-left"></i>&nbsp;back
				</JetSecondaryButton>
			</Link>
			

			<JetButton variant="primary" size="md" type="submit" :class="{ 'opacity-25': form.processing }">
				<i class="fa fa-floppy-o"></i>&nbsp;save
			</JetButton>
		</template>
	</jet-form-section>
</template>
<script setup>
import JetActionMessage from '@/Jetstream/ActionMessage.vue';
import JetFormSection from '@/Jetstream/FormSection.vue';
import JetInput from '@/Jetstream/Input.vue';
import JetInputError from '@/Jetstream/InputError.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue';
import JetButton from '@/Jetstream/Button.vue';

import { useForm, Link } from '@inertiajs/vue3';
import Helpers from '../../Mixins/Helpers';

const props = defineProps(['group', 'project']);

const { toast } = Helpers();

const form = useForm({
	name: ''
});

const createSurvey = () => {
	form.post(route('surveys.store', props.group), {
		errorBag: 'createSurvey',
		preserveScroll: true,
		onSuccess: () => {
			toast.success("Survey successfully created");
		}
	})
};
</script>