<template>
	<Layout title="Filters">
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
				<div class="flex justify-between mb-2 rounded-xl shadow-md py-2 px-2 bg-white">
					<div class="inline-flex items-center">
						<Link :href="route('groups.index', project)" class="inline-flex items-center text-sm text-sky-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                Back
                        </Link>
						<ProjectDropdown destination="filters.index" class="mx-3" />
					</div>
					<div class="inline-flex items-center">
						<Link class="hidden mr-1 btn-indigo" :href="route('filters.create', project)">
							
						</Link>
						<SmallButtonVue variant="primary" size="md" class="inline-flex items-center mr-1" @click="newFilterModal=true">
							<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
								<path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
							</svg>Create filter
						</SmallButtonVue>

						<Link :href="route('filters.syncAll', project)">
							<SmallButtonVue variant="primary" size="md">
								<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
									<path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
								</svg>Sync all
							</SmallButtonVue>
						</Link>
						<SmallButtonVue @click="deleteAll" variant="primary" class="ml-1">
							<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
								<path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
							</svg>Delete All
						</SmallButtonVue>
					</div>
				</div>
				<div class="overflow-y-auto c rounded-xl shadow bg-white" :class="{'flex items-center': filters.length === 0}">
					<div v-if="filters.length > 0">
						<table class="min-w-full divide-y divide-gray-200 table-fixed">
							<thead>
								<tr>
									<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
										#
									</th>
									<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
										Name
									</th>
									<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
										Count
									</th>
									<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
										Owner
									</th>
									<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
										Modified
									</th>
									<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
										Actions
									</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-gray-200">
								<tr class="hover:bg-gray-100" v-for="(filter, key) in filters" :key="key">
									<td class="pl-6 text-gray-500 whitespace-no-wrap text-xs">
										<strong>
											{{ key + 1 }}
										</strong>
									</td>
									<td class="whitespace-nowrap text-xs py-2 px-3">
										{{ filter.name }}
									</td>
									<td class="whitespace-nowrap text-xs py-2 px-3">
										{{ filter.count }}
									</td>
									<td class="whitespace-nowrap text-xs py-2 px-3">
										{{ filter.user.name }}
									</td>
									<td class="whitespace-nowrap text-xs py-2 px-3">
										{{ datetime(filter.updated_at) }}
									</td>
									<td class="py-2">
										<div class="flex justify-stretch text-sm text-blue-400">
											<Link class="mx-2" :href="route('filters.sync', [project, filter])">
												<i class="fa fa-refresh"></i> Sync
											</Link>
											<a class="mx-1" href="javascript:;" @click="deleteFilterDialog(filter)">
												<i class="fa fa-trash"></i>&nbsp;Delete
											</a>
											
											<Link class="mx-2" :href="route('filters.edit', [project, filter])">
												<i class="fa fa-pencil"></i> Update
											</Link>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					
					<div class="mx-auto text-gray-600 prose-2xl font-medium" v-else>
						No record found
					</div>
				</div>
			</div>
		</div>
	</Layout>

	<ConfirmationModal :show="confirmingFilterDeletion" @close="confirmingFilterDeletion = false">
		<template #title>
			Delete Filter
		</template>

		<template #content>
			Are you sure you want to delete this filter?
		</template>

		<template #footer>
			<JetSecondaryButton @click="confirmingFilterDeletion = false">
                nevermind
            </JetSecondaryButton>

            <JetDangerButton class="ml-2" @click="deleteFilter" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                delete filter
            </JetDangerButton>
		</template>
	</ConfirmationModal>

	<JetDialogModal :show="newFilterModal" @close="newFilterModal=false" maxWidth="2xl">
		<template #title>
			New Filter
		</template>
		<template #content>
			<CustomTextArea label="Filter name" :rows="3" v-model="form.name" />
			<CustomTextArea label="Source URL" :rows="3" v-model="form.src" />
			<CustomSelect label="Operator" class="w-2/3" v-model="form.operator">
				<option value="in">Include</option>
				<option value="not_in">Exclude</option>
			</CustomSelect>
			<CustomTextArea label="Respondent IDs" :rows="3" v-model="form.ids" />
		</template>
		<template #footer>
			<JetSecondaryButton @click="newFilterModal=false" class="mr-2">
				nevermind
			</JetSecondaryButton>
			<CustomButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="createFilter">
				Save
			</CustomButton>
		</template>
	</JetDialogModal>
</template>
<script setup>
import Layout from '@/Layouts/Layout.vue';
import ProjectDropdown from '@/Extra/ProjectDropdown.vue';
import JetSecondaryButton from '@/Components/SecondaryButton.vue';
import JetDangerButton from '@/Jetstream/DangerButton.vue';
import ConfirmationModal from '../../Jetstream/ConfirmationModal.vue';
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import CustomTextArea from '@/Components/TextArea.vue';
import CustomSelect from '@/Components/Select.vue';
import CustomButton from '@/Components/Button.vue';
import SmallButtonVue from '../../Components/SmallButton.vue';

import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Helpers from '@/Mixins/Helpers';

const props = defineProps(['project', 'filters', 'projects']);

const { assign, toast, datetime }  = Helpers();

const confirmingFilterDeletion = ref(false);
const selectedFilter = ref(null);
const newFilterModal = ref(false);


const form = useForm({
	name: '',
	src: '',
	description: '',
	operator: 'in',
	ids: ''
});

const deleteFilterDialog = (filter) => {
	selectedFilter.value = assign(filter);
	confirmingFilterDeletion.value = true;
};

const deleteFilter = () => {
	form.delete(route("filters.destroy", [props.project, selectedFilter.value]), {
		preserveScroll: true,
		onSuccess: () => {
			selectedFilter.value = null;
			confirmingFilterDeletion.value = false;
			toast.success("Filter successfully deleted");
		}
	});
};

const deleteAll = () => {
	form.delete(route('filters.deleteAll', props.project), {
		preserveScroll: true,
		onSuccess: () => {
			selectedFilter.value = null;
			confirmingFilterDeletion.value = false;
			toast.success("Filters successfully deleted");
		}
	});
};

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
<style scoped="true">
    .c {
        height: calc(100vh - 120px);
    }
    
</style>