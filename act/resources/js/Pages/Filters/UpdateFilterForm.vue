<template>
	<jet-form-section @submitted="updateFilter">
		<template #title>
            Filter Details
        </template>
        <template #description>
            Update filter <em>{{ filter.name }}</em>
        </template>
		<template #form>
			<div class="col-span-6">
				<jet-label value="Filter Name" />
                <jet-input type="text" class="mt-1 block w-full" v-model="form.name" autofocus />
                <jet-input-error :message="form.errors.name" class="mt-2" />
			</div>

			<div class="col-span-6">
				<jet-label value="Source URL (delimited by new line for multiple sources)" />
                <textarea rows="3" class="form-input w-full" v-model="form.src"></textarea>
                <jet-input-error :message="form.errors.src" class="mt-2" />
			</div>

			<div class="col-span-6">
				<jet-label value="Description" />
                <jet-input type="text" class="mt-1 block w-full" v-model="form.description" autofocus />
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
			<jet-action-message :on="form.recentlySuccessful" class="mr-2">
                Saved.
            </jet-action-message>
			<Link :href="route('filters.index', project)" class="mr-2">
				<action-button variant="primary" size="md">
					<i class="fa fa-arrow-left"></i>&nbsp;back
				</action-button>
			</Link>
			<template v-if="navigator.hasPrevious">
				<Link :href="navigator.previous" class="mr-2">
					<action-button :disabled="!navigator.hasPrevious" variant="primary" size="md">
						<i class="fa fa-chevron-left"></i>&nbsp;Previous Filter
					</action-button>
				</Link>
			</template>
			<template v-if="navigator.hasNext">
				<Link :href="navigator.next" class="mr-2">
					<action-button :disabled="!navigator.hasNext" variant="primary" size="md">
						Next Filter&nbsp;<i class="fa fa-chevron-right"></i>
					</action-button>
				</Link>
			</template>
            <action-button :class="{ 'opacity-25': form.processing }" :disabled="form.processing" variant="primary" size="md" @click="updateFilter">
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

import { Link, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import Helpers from '../../Mixins/Helpers';

const props = defineProps(['project', 'filter', 'survey', 'navigator']);

const { toast } = Helpers();

const form = useForm({
	name: '',
	src: '',
	description: '',
	operator: 'in',
	ids: ''
});

onMounted(() => {
	form.name = props.filter.name;
	form.description = props.filter.description;
	form.src = props.filter.src;
	form.operator = props.filter.data.operator;
	form.ids = props.filter.data.ids;
});

const updateFilter = () => {
	form.put(route('filters.update', [props.project, props.filter]), {
		preserveScroll: true,
		errorBag: 'updateFilter',
		onSuccess: () => {
			toast.success("Filter successfully updated");
		}
	});
};
</script>