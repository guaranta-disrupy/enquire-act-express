<template>
	<jet-form-section @submitted="saveSettings">
		<template #title>
			Group Criteria
		</template>

		<template #form>

			<div class="col-span-6">
				<h3 class="text-lg text-gray-900 border-b">
					Criteria: {{group.name}}
				</h3>

				<div class="grid grid-cols-6 gap-1 mt-2">
					<div class="col-span-6">
						<jet-button type="button" variant="secondary" @click="addSegment()" class="border-gray-600">
							<span v-if="form.config.conditions.length > 0">Add Segment</span><span v-else>Add Condition</span>
						</jet-button>
					</div>
					<div class="col-span-6" v-for="(condition, index) in form.config.conditions" :key="index">
						<criteria-contents :deep="0" :condition="condition" :indexes="[index]"/>
					</div>
					<div class="col-span-6 mt-3">
						<code class="text-black-500 text-md bg-gray-200">{{ criteriaToString(group.config.conditions) }}</code>
					</div>
				</div>

				<jet-section-border />

				<jet-label>
					<div class="flex items-center">
                        <jet-checkbox v-model:checked="form.config.random" />

                        <div class="ml-2">
                            Randomize surveys
                        </div>
                    </div>
				</jet-label>
			</div>

		</template>

		<template #actions>
			<jet-action-message :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </jet-action-message>

            <jet-button :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                <i class="fa fa-floppy-o"></i>&nbsp;save
            </jet-button>
		</template>
	</jet-form-section>
</template>
<script setup>
import JetFormSection from '@/Jetstream/FormSection.vue';
import JetActionMessage from '@/Jetstream/ActionMessage.vue';
import JetButton from '@/Jetstream/Button.vue';
import CriteriaContents from './Criteria.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetSectionBorder from '@/Jetstream/SectionBorder.vue';
import JetCheckbox from '@/Jetstream/Checkbox.vue';

import { onBeforeMount, provide } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Helpers from '@/Mixins/Helpers.js';

const { $state, assign } = Helpers();

const props = defineProps(['group', 'project']);

const form = useForm({
    config: {}
});

onBeforeMount(() => {
    form.config = props.group.config;
    $state.criteria = {config: form.config};
});

provide('state', $state);

const saveSettings = () => {
    form.put(route('groups.saveSettings', [props.project, props.group]), {
        preserveScroll: true
    });
};

const addSegment = () => {
    $state.addSegment();
};

const criteriaToString = (str) => {
    return $state.criteriaToString(str);
};
</script>
