<template>
	<jet-form-section @submitted="createFilter">
		<template #title>
            Filter Details
        </template>
        <template #description>
            Create new filter for <em>{{ project.name }}</em>
        </template>
		<template #form>
			<div class="col-span-6">
				<jet-label value="Filter Name" />
                <jet-input type="text" class="mt-1 block w-full" v-model="form.name" autofocus @keyup.enter="createFilter" />
                <jet-input-error :message="form.errors.name" class="mt-2" />
			</div>

			<div class="col-span-6">
				<jet-label value="Source URL (delimited by new line for multiple sources)" />
                <!-- <jet-input type="text" class="mt-1 block w-full" v-model="form.src" /> -->
                <textarea rows="3" class="form-input w-full" v-model="form.src"></textarea>
                <jet-input-error :message="form.errors.src" class="mt-2" />
			</div>

			<div class="col-span-6">
				<jet-label value="Description" />
                <jet-input type="text" class="mt-1 block w-full" v-model="form.description" />
                <jet-input-error :message="form.errors.description" class="mt-2" />
			</div>

			<div class="col-span-6">
				<jet-label value="Operator" />
				<select class="form-select block w-1/2" v-model="form.operator">
					<option value="in">Include</option>
					<option value="not_in">Exclude</option>
				</select>
			</div>

			<div class="col-span-6">
				<jet-label value="Respondent IDs" />
				<textarea rows="5" class="form-input w-full" v-model="form.ids"></textarea>
			</div>
		</template>

		<template #actions>
			<jet-action-message :on="form.recentlySuccessful" class="mr-1">
                Saved.
            </jet-action-message>

            
            <Link :href="route('filters.index', project)">
            	<action-button class="mr-4" variant="primary" size="md">
            		<i class="fa fa-arrow-left"></i>&nbsp;Back
            	</action-button>
            </Link>
            

            <action-button variant="primary" size="md" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="createFilter">
                <i class="fa fa-floppy-o"></i>&nbsp;Save
            </action-button>
		</template>
	</jet-form-section>
</template>
<script setup>
import JetInput from '@/Jetstream/Input.vue';
import JetInputError from '@/Jetstream/InputError.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetActionMessage from '@/Jetstream/ActionMessage.vue';
import JetFormSection from '@/Jetstream/FormSection.vue';
import ActionButton from '@/Components/ActionButton.vue';
import { Link } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';

const props = defineProps(['project']);

const toast = useToast();

const form = useForm({
	name: '',
	src: '',
	description: '',
	operator: 'in',
	ids: ''
});

const createFilter = () => {
	form.post(route('filters.store', props.project), {
		preserveScroll: true,
		onSuccess: () => {
			form.reset();
			toast.success("Filter successfully created")
		}
	});
};
</script>