<template>
	<Layout :title="'Update Filter: ' + filter.name">
		<template #header>
			<div class="inline-flex items-center">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>&nbsp;
                {{ project.name }} > Filters
			</div>
		</template>
		<div>
			<div class="max-w-8xl mx-auto px-2">
				<div class="flex justify-between mb-2">
					<div class="inline-flex items-center">
						<Link :href="route('filters.index', project)" class="inline-flex items-center text-sm text-sky-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Back
                        </Link>
						<ProjectDropdown destination="filters.index" class="mx-3" />
					</div>
					<div class="inline-flex items-center">
						<template v-if="navigator.hasPrevious">
							<Link :href="navigator.previous" class="mr-2">
								<SecondaryButton :disabled="!navigator.hasPrevious" variant="primary" size="md">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
										<path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
									</svg>
									&nbsp;Previous Filter
								</SecondaryButton>
							</Link>
						</template>
						<template v-if="navigator.hasNext">
							<Link :href="navigator.next" class="mr-2">
								<SecondaryButton :disabled="!navigator.hasNext" variant="primary" size="md">
									Next Filter&nbsp;
									<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
										<path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
									</svg>
								</SecondaryButton>
							</Link>
						</template>
						<SecondaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" variant="primary" size="md" @click="updateFilter">
							<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
								<path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
							</svg>
							&nbsp;Save
						</SecondaryButton>
					</div>
				</div>
				<div class="overflow-y-auto c rounded-xl shadow bg-white">
					<div class="flex flex-col p-4">
						<CustomInput label="Name" v-model="form.name" class="w-3/4 pb-1" />
						<CustomTextArea :rows="5" v-model="form.src" class="w-3/4 flex-1 pb-1" label="Source URL" />
						<CustomSelect v-model="form.operator" label="Operator" class="w-3/4 pb-1">
							<option value="in">Include</option>
							<option value="not_in">Exclude</option>
						</CustomSelect>
						<CustomTextArea :rows="10" v-model="form.ids" label="Respondent IDs" class="w-3/4 pb-1" />
					</div>
					
				</div>
			</div>
		</div>
	</Layout>
</template>
<script setup>
import Layout from '@/Layouts/Layout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ProjectDropdown from '@/Extra/ProjectDropdown.vue';
import CustomInput from '@/Components/Input.vue';
import CustomSelect from '@/Components/Select.vue';
import CustomTextArea from '@/Components/TextArea.vue';

import { useForm, Link } from '@inertiajs/vue3';
import { onBeforeMount } from 'vue';

const props = defineProps(['project', 'filter', 'navigator']);

const form = useForm({
	name: '',
	src: '',
	description: '',
	operator: 'in',
	ids: ''
});

onBeforeMount(() => {
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

<style scoped="true">
    .c {
        height: calc(100vh - 120px);
    }
    
</style>