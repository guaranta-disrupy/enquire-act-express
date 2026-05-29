<template>
	<JetFormSection @submitted="editSurvey">
		<template #title>
			Survey name
		</template>

		<template #description>
			Rename survey
		</template>

		<template #form>
			<div class="col-span-6">
				<JetLabel for="name" value="Survey" />
				<JetInput id="name" type="text" class="mt-1 w-full" v-model="form.name" autofocus />
				<JetInputError :message="form.errors.name" class="mt-2" />
			</div>
		</template>

		<template #actions>
			<JetActionMessage :on="form.recentlySuccessful" class="mr-3">
				Saved
			</JetActionMessage>

			
			<Link :href="route('groups.index', project)">
				<JetSecondaryButton class="mr-4" variant="primary" size="md">
					<i class="fa fa-arrow-left"></i>&nbsp;back
				</JetSecondaryButton>
			</Link>
			

			<JetButton variant="primary" size="md" :class="{ 'opacity-25': form.processing}" :disabled="form.processing" @click="editSurvey">
				<i class="fa fa-floppy-o"></i>&nbsp;save
			</JetButton>
		</template>
	</JetFormSection>
</template>

<script setup>
import JetActionMessage from '@/Jetstream/ActionMessage.vue';
import JetButton from '@/Jetstream/Button.vue';
import JetFormSection from '@/Jetstream/FormSection.vue';
import JetInput from '@/Jetstream/Input.vue';
import JetInputError from '@/Jetstream/InputError.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue';

import { Link, useForm } from '@inertiajs/vue3';
import { onBeforeMount } from 'vue';

const props = defineProps(['survey', 'project', 'group']);

const form = useForm({
	name: ''
});

onBeforeMount(() => {
	form.name = props.survey.name;
});

const editSurvey = () => {
	form.put(route('surveys.update', [props.group, props.survey]), {
		preserveScroll: true
	})
};
</script>