<template>
    <Head :title="survey.name" />

    <div class="bg-slate-200 min-h-[100vh] text-black">
        <div class="flex flex-col max-w-7xl mx-auto">
            <div class="flex flex-col sticky top-0 bg-slate-200 py-2">
                <div class="flex items-center justify-between py-2">
                    <div class="flex items-center gap-2">
                        <div>
                            <a :href="route('redirect.fluent', survey.config.survey_id)">
                                <JetButton variant="emerald" size="sm" shadow="2xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15m0 0l6.75 6.75M4.5 12l6.75-6.75" />
                                    </svg>
                                    Back to Fluent
                                </JetButton>
                            </a>
                        </div>
                        <div class="text-lg">
                            <!-- {{ survey.name }} -->
                            <Dropdown align="left" width="60">
                                <template #trigger>
                                    <JetButton variant="default" size="sm">
                                        {{ survey.name }}&nbsp;
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                        stroke-width="2" stroke="currentColor" class="w-3 h-3 font-bold">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </JetButton>
                                </template>
                                <template #content>
                                    <div v-for="survey in surveys">
                                        <DropdownLink as="a" :href="route('analysis.index2', survey)">
                                            {{ survey.name }}
                                        </DropdownLink>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                    <div class="text-lg tracking-wide">
                        Logged in as {{ $page.props.auth.user.name }}
                    </div>
                </div>
                <div class="flex item-center justify-end mt-2 gap-2">
                    <Link :href="route('survey2.index', survey)" class="mr-1 text-xs text-blue-600 uppercase">
                        <JetButton variant="amber" size="sm" class="px-4 uppercase" shadow="xl">
                            trial settings
                        </JetButton>
                    </Link>
                </div>
            </div>
            <div class="overflow-y-auto mt-4">
                <div class="max-w-7xl mx-auto bg-white rounded-lg shadow-lg">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-5">
                            <div class="flex justify-start p-2">
                                <JetButton size="xs" variant="slate" class="mr-1 uppercase" :class="{ 'opacity-25': processing }" :disabled="processing || selected_filters.length === 0" @click="process_export_caching('scores')">
                                    export results
                                </JetButton>
                                <JetButton size="xs" variant="slate" class="uppercase" :class="{ 'opacity-25': processing }" :disabled="processing || selected_filters.length === 0" @click="process_export_caching()">
                                    export respondent data
                                </JetButton>
                            </div>
                            <div class="h-[76vh] overflow-y-auto">
                                <div class="p-2">
                                    <CustomSelect class="w-2/3" label="Result option" size="md" v-model="options.compute_by">
										<option value="count">Computed by count</option>
										<option value="rank">Computed by order</option>
										<option value="rt">Computed by reaction time</option>
										<option value="new_algo1">New Algorithm 1</option>
									</CustomSelect>
                                    <jet-label class="mt-4">
										<div class="flex items-center">
											<jet-checkbox v-model:checked="options.export_results"></jet-checkbox>
											<div class="ml-2">
												Export filter results
											</div>
										</div>
									</jet-label>
                                    <jet-label class="mt-2">
										<div class="flex items-center">
											<jet-checkbox v-model:checked="options.iq_scores"></jet-checkbox>
											<div class="ml-2">
												Export tabulated IQ scores
											</div>
										</div>
									</jet-label>
									<jet-label class="mt-2">
										<div class="flex items-center">
											<jet-checkbox v-model:checked="options.use_attributes"></jet-checkbox>
											<div class="ml-2">
												Original texts as placeholder
											</div>
										</div>
									</jet-label>
									<jet-label class="mt-2">
										<div class="flex items-center">
											<jet-checkbox v-model:checked="options.modified_rank"></jet-checkbox>
											<div class="ml-2">
												Modified rank
											</div>
										</div>
									</jet-label>
									<jet-label class="mt-2">
										<div class="flex items-center">
											<jet-checkbox v-model:checked="options.minimal_respondent_level"></jet-checkbox>
											<div class="ml-2">
												Minimal Respondent Level Data
											</div>
										</div>
									</jet-label>
                                    <div class="mt-3">
										<button class="text-xs text-blue-500" @click="modals.namesDialog = true">
											<i class="fa fa-cog"></i>&nbsp;Settings
										</button>
									</div>
									<div class="mt-1">
										<button class="text-xs text-blue-500" @click="modals.testsDialog = true">
											<i class="fa fa-cog"></i>&nbsp;Select trial to export
										</button>
									</div>
                                    <template v-if="didUseVariables">
										<div class="mt-2" v-if="variables.length > 0">
											<JetLabel value="Variable" />
											<select v-model="selected_variable" class="form-select w-2/3">
												<option :value="0">None</option>
												<option v-for="variable in variables" :value="variable">{{ variable.name }}</option>
											</select>
										</div>
										<div class="mt-2" v-if="variables.length > 0 && selected_variable != 0">
											<JetLabel value="Variable value" />
											<select class="w-2/3 form-select" v-model="options.variable_value" @change="reflectToVariableOptions">
												<option v-for="(cri, key) in selected_variable.config.criteria" :value="key + 1">{{ cri.label }}</option>
											</select>
										</div>
									</template>

                                    <div class="mt-4">
                                        <jet-label value="Select filters" class="mb-2"></jet-label>
										<jet-label class="mt-2 ml-1">
											<div class="flex items-center">
												<jet-checkbox v-model:checked="selectedAll"></jet-checkbox>
												<div class="ml-2">
													Select all
												</div>
											</div>
										</jet-label>
                                        <div>
											<template v-for="(filter, k) in filters" :key="'fl' + k">
												<jet-label class="mt-2 ml-1">
													<div class="flex items-center">
														<jet-checkbox v-model:checked="selected_filters" :value="filter.id" />
														<div class="ml-2">
															{{ filter.name }} ({{ filter.count }})
														</div>
													</div>
												</jet-label>
											</template>
										</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-span-7">
                            <div class="flex justify-end my-2">
								<JetButton size="xs" variant="rose" class="mr-1" v-if="exports.length > 0" @click="deleteAllExports">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
										<path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
									</svg>Delete All
								</JetButton>
							</div>
                            <div class="h-[75vh] overflow-y-auto" :class="{'flex items-center': exports.length == 0 }">
								<table class="w-full whitespace-no-wrap table text-gray-400 border-separate space-y-6 text-sm" v-if="exports.length > 0">
    								<thead class="bg-gray-100 text-gray-500">
    									<tr>
    										<th class="p-2 text-left">
    											File
    										</th>
    										<th class="p-2 text-left">
    											Type
    										</th>
    										<th class="p-2 text-left">
    											Owner
    										</th>
    										<th class="p-2 text-left">
    											Date
    										</th>
    										<th class="p-2 text-left">
    											Delete
    										</th>
    									</tr>
    								</thead>
    								<tbody>
    									<tr class="rounded hover:bg-gray-300" v-for="(exportable, i) in exports" :key="'tr' + i">
    										<td class="px-1">
    											<span class="text-xs bg-gray-600 rounded px-2 text-white">
    												<a :href="route('exports.download', exportable)">
				                						{{ exportable.file }}
				                					</a>
    											</span>
    										</td>
    										<td class="px-1">
    											<div class="text-xxs text-gray-500">
	                								{{ exportable.file_type }}
	                							</div>
    										</td>
    										<td class="px-1">
    											<div class="text-xxs text-gray-500">
	                								{{ exportable.user.name }}
	                							</div>
    										</td>
    										<td class="px-1">
    											<div class="text-xxs text-gray-500">
		                							{{ datetime(exportable.created_at) }}
		                						</div>
    										</td>
    										<td class="px-1">
    											<span @click.prevent="deleteExport(exportable)" class="text-xs text-blue-400 cursor-pointer">
    												<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
														<path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
													</svg>
    											</span>
    										</td>
    									</tr>
    								</tbody>
    							</table>
								<div class="mx-auto text-gray-600 prose-2xl font-medium" v-else>
									No exported files found in this survey
								</div>
    							
							</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
	<Layout :title="survey.name + ' Responses'" class="hidden">
		<template #header>
			<div class="inline-flex items-center">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>&nbsp;
                {{ project.name }} > {{ survey.name }} > Responses
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
						<ProjectDropdown destination="groups.index" class="mx-3" />
                        <SurveyDropdown class="" destination="analysis.index" />
					</div>
					<div class="inline-flex items-center">
						<Link class="mr-1" :href="route('filters.index', project)">
							<SmallButton variant="primary" size="md">
								<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
                                </svg>
								Filters
							</SmallButton>
						</Link>
						<Link class="mr-1 hidden" :href="route('analysis.advance', survey)">
							<SmallButton variant="primary" size="md">
								Respondent Level Data Merger
							</SmallButton>
						</Link>
					</div>
				</div>
				<div class="bg-white shadow-md rounded-xl p-4">
					<div class="grid grid-cols-12 gap-4">
						<div class="col-span-5">
							<div class="flex justify-start">
								<SmallButton size="sm" class="mr-1" :class="{ 'opacity-25': processing }" :disabled="processing || selected_filters.length === 0" @click="process_export_caching('scores')">
									export results
								</SmallButton>
								<SmallButton size="sm" :class="{ 'opacity-25': processing }" :disabled="processing || selected_filters.length === 0" @click="process_export_caching()">
									export respondent data
								</SmallButton>
							</div>
							<div class="h-[76vh] overflow-y-auto">
								<div class="p-2">
									<CustomSelect class="w-2/3" label="Result option" size="md" v-model="options.compute_by">
										<option value="count">Computed by count</option>
										<option value="rank">Computed by order</option>
										<option value="rt">Computed by reaction time</option>
										<option value="new_algo1">New Algorithm 1</option>
									</CustomSelect>
									<jet-label class="mt-4">
										<div class="flex items-center">
											<jet-checkbox v-model:checked="options.export_results"></jet-checkbox>
											<div class="ml-2">
												Export filter results
											</div>
										</div>
									</jet-label>
									<jet-label class="mt-2">
										<div class="flex items-center">
											<jet-checkbox v-model:checked="options.iq_scores"></jet-checkbox>
											<div class="ml-2">
												Export tabulated IQ scores
											</div>
										</div>
									</jet-label>
									<jet-label class="mt-2">
										<div class="flex items-center">
											<jet-checkbox v-model:checked="options.use_attributes"></jet-checkbox>
											<div class="ml-2">
												Original texts as placeholder
											</div>
										</div>
									</jet-label>
									<jet-label class="mt-2">
										<div class="flex items-center">
											<jet-checkbox v-model:checked="options.modified_rank"></jet-checkbox>
											<div class="ml-2">
												Modified rank
											</div>
										</div>
									</jet-label>
									<jet-label class="mt-2">
										<div class="flex items-center">
											<jet-checkbox v-model:checked="options.minimal_respondent_level"></jet-checkbox>
											<div class="ml-2">
												Minimal Respondent Level Data
											</div>
										</div>
									</jet-label>
									<div class="mt-3">
										<button class="text-xs text-blue-500" @click="modals.namesDialog = true">
											<i class="fa fa-cog"></i>&nbsp;Settings
										</button>
									</div>
									<div class="mt-1">
										<button class="text-xs text-blue-500" @click="modals.testsDialog = true">
											<i class="fa fa-cog"></i>&nbsp;Select trial to export
										</button>
									</div>
									<template v-if="didUseVariables">
										<div class="mt-2" v-if="variables.length > 0">
											<JetLabel value="Variable" />
											<select v-model="selected_variable" class="form-select w-2/3">
												<option :value="0">None</option>
												<option v-for="variable in variables" :value="variable">{{ variable.name }}</option>
											</select>
										</div>
										<div class="mt-2" v-if="variables.length > 0 && selected_variable != 0">
											<JetLabel value="Variable value" />
											<select class="w-2/3 form-select" v-model="options.variable_value" @change="reflectToVariableOptions">
												<option v-for="(cri, key) in selected_variable.config.criteria" :value="key + 1">{{ cri.label }}</option>
											</select>
										</div>
									</template>
									
									<div class="mt-4">
										<jet-label value="Select filters" class="mb-2"></jet-label>
										<jet-label class="mt-2 ml-1">
											<div class="flex items-center">
												<jet-checkbox v-model:checked="selectedAll"></jet-checkbox>
												<div class="ml-2">
													Select all
												</div>
											</div>
										</jet-label>
										<div>
											<template v-for="(filter, k) in filters" :key="'fl' + k">
												<jet-label class="mt-2 ml-1">
													<div class="flex items-center">
														<jet-checkbox v-model:checked="selected_filters" :value="filter.id" />
														<div class="ml-2">
															{{ filter.name }} ({{ filter.count }})
														</div>
													</div>
												</jet-label>
											</template>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-span-7">
							<div class="flex justify-start">
								<SmallButton size="sm" class="mr-1" v-if="exports.length > 0" @click="deleteAllExports">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
										<path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
									</svg>Delete All
								</SmallButton>
							</div>
							<div class="h-[75vh] overflow-y-auto" :class="{'flex items-center': exports.length == 0 }">
								<table class="w-full whitespace-no-wrap table text-gray-400 border-separate space-y-6 text-sm" v-if="exports.length > 0">
    								<thead class="bg-gray-100 text-gray-500">
    									<tr>
    										<th class="p-2 text-left">
    											File
    										</th>
    										<th class="p-2 text-left">
    											Type
    										</th>
    										<th class="p-2 text-left">
    											Owner
    										</th>
    										<th class="p-2 text-left">
    											Date
    										</th>
    										<th class="p-2 text-left">
    											Delete
    										</th>
    									</tr>
    								</thead>
    								<tbody>
    									<tr class="rounded hover:bg-gray-300" v-for="(exportable, i) in exports" :key="'tr' + i">
    										<td class="px-1">
    											<span class="text-xs bg-gray-600 rounded px-2 text-white">
    												<a :href="route('exports.download', exportable)">
				                						{{ exportable.file }}
				                					</a>
    											</span>
    										</td>
    										<td class="px-1">
    											<div class="text-xxs text-gray-500">
	                								{{ exportable.file_type }}
	                							</div>
    										</td>
    										<td class="px-1">
    											<div class="text-xxs text-gray-500">
	                								{{ exportable.user.name }}
	                							</div>
    										</td>
    										<td class="px-1">
    											<div class="text-xxs text-gray-500">
		                							{{ datetime(exportable.created_at) }}
		                						</div>
    										</td>
    										<td class="px-1">
    											<span @click.prevent="deleteExport(exportable)" class="text-xs text-blue-400 cursor-pointer">
    												<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
														<path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
													</svg>
    											</span>
    										</td>
    									</tr>
    								</tbody>
    							</table>
								<div class="mx-auto text-gray-600 prose-2xl font-medium" v-else>
									No exported files found in this survey
								</div>
    							
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</Layout>
	<JetDialogModal :show="modals.namesDialog" @close="modals.namesDialog = false">
		<template #title>
			Export Naming Settings
		</template>

		<template #content>
			<div class="grid grid-cols-6 gap-6">
				<div class="col-span-6">
					<label class="block font-medium text-sm text-gray-700" for="">Responses: <code>$count$</code> = filter count, <code>$filter$</code> = filter name</label>
					<jet-input class="block w-full mt-2" type="text" v-model="options.responses_name"></jet-input>
				</div>
			</div>
			<div class="grid grid-cols-6 gap-6">
				<div class="col-span-6 mt-3">
					<jet-label value="Tabular"></jet-label>
					<jet-input class="block w-full mt-2" type="text" v-model="options.tabular_name"></jet-input>
				</div>
			</div>
			<div class="grid grid-cols-6 gap-6">
				<div class="col-span-6 mt-3">
					<label class="block font-medium text-sm text-gray-700" for="">Computed: <code>$count$</code> = filter count, <code>$filter$</code> = filter name</label>
					<jet-input class="block w-full mt-2" type="text" v-model="options.computed_name"></jet-input>
				</div>
			</div>
			<div class="grid grid-cols-6 gap-6">
				<div class="col-span-6 mt-3">
					<label class="block font-medium text-sm text-gray-700" for="">Minimum Number of Respondents to export</label>
					<jet-input class="block w-full mt-2" type="text" v-model="options.minimum_sample"></jet-input>
				</div>
			</div>
			<div class="grid grid-cols-6 gap-6">
				<div class="col-span-6 mt-3 w-2/3">
					<label class="block font-medium text-sm text-gray-700" for="">Target IDs</label>
					<textarea class="block p-2.5 w-full text-xs text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" v-model="options.target_ids" placeholder="Enter target IDs"></textarea>
				</div>
			</div>
		</template>

		<template #footer>
			<action-button variant="primary" size="md" class="ml-2" @click="modals.namesDialog=false">
				Close
			</action-button>
		</template>
	</JetDialogModal>

	<JetDialogModal :show="modals.testsDialog" @close="modals.testsDialog = false">
		<template #title>
			Select Trials
		</template>

		<template #content>
			<div class="h-96 overflow-auto pl-2">
				<jet-label class="mt-1">
					<div class="flex items-center">
						<jet-checkbox v-model:checked="selectedAllTest"></jet-checkbox>
						<div class="ml-2">
							Select all
						</div>
					</div>
				</jet-label>
				<template v-for="(test, k) in tests" :key="'fl' + k">
					<jet-label class="mt-1">
						<div class="flex items-center">
							<jet-checkbox v-model:checked="selected_tests" :value="test.id" :disabled="!test.isTrial" />
							<div class="ml-2 text-xs" :class="{'text-gray-400': !test.isTrial}">
								Trial {{ test.part }}
							</div>
						</div>
					</jet-label>
				</template>
			</div>
		</template>

		<template #footer>
			<action-button variant="primary" size="md" class="ml-2" @click="modals.testsDialog=false">
				Close
			</action-button>
		</template>
	</JetDialogModal>
	<Loader :show="processing" :text="displayCurrentProcessingFilter">
		Please wait...
	</Loader>
</template>



<script setup>
import Layout from '@/Layouts/Layout.vue';
import SurveyDropdown from '@/Extra/SurveyDropdown.vue';
import ProjectDropdown from '@/Extra/ProjectDropdown.vue';
import CustomSelect from '@/Components/Select.vue';
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import JetInput from '@/Jetstream/Input.vue';
import ActionButton from '@/Components/ActionButton.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetCheckbox from '@/Jetstream/Checkbox.vue';
import Loader from "@/Components/Loader2.vue";
import forEach from 'lodash/forEach';
import head from 'lodash/head';
import indexOf from 'lodash/indexOf';
import { Link, useForm, Head } from '@inertiajs/vue3';
import { watch, ref, onBeforeMount, onMounted, onUpdated, computed } from 'vue';
import Helpers from '@/Mixins/Helpers';
import SmallButton from '@/Components/SmallButton.vue';
import JetButton from '@/Componentsv2/Button.vue';
import Dropdown from '@/Jetstream/Dropdown.vue';
import DropdownLink from '@/Jetstream/DropdownLink.vue';

const { toast, assign } = Helpers();

const props = defineProps(['filters', 'survey', 'project', 'exportables', 'tests', 'variables', 'didUseVariables', 'pre_selected_variable', 'surveys']);

const form = useForm({});

const selected_filters = ref([]);

const selected_variable = ref({});

const options = ref({
	compute_by: 'count',
	export_results: false,
	iq_scores: true,
	count: 1,
	tabular_name: props.survey.name + ' Tabular IQ Scores',
	computed_name: '$count$ - $filter$',
	responses_name: '$filter$-' + props.survey.name + ' Respondent Level Data',
	use_attributes: true,
	variable_name: head(props.variables)?.name,
	variable_value: null,
	variable_value_text: null,
    variables: [],
	modified_rank: false,
	minimum_sample: 10,
	minimal_respondent_level: false,
	target_ids: '',
});
const currentIndex = ref(0);

const processing = ref(false);

const exports = ref([]);

const selectedAll = ref(false);

const selectedAllTest = ref(true);

const cache_keys = ref([]);

const modals = ref({
	namesDialog: false,
	testsDialog: false,
});

const selected_tests = ref([]);

onBeforeMount(() => {
	exports.value = assign(props.exportables);
	forEach(props.tests, test => {
		if (test.isTrial) {
			selected_tests.value.push(test.id);
		}
	});
	// selected_variable.value = head(props.variables);
	selected_variable.value = 0;
	options.value.minimum_sample = !localStorage.getItem('minimum_sample') ? 10 : localStorage.getItem('minimum_sample');
	options.value.compute_by = !localStorage.getItem('compute_by') ? 'count' : localStorage.getItem('compute_by');
	selected_filters.value = !localStorage.getItem('selected_filters') ? [] : JSON.parse(localStorage.getItem('selected_filters'));
	options.value.use_attributes = !localStorage.getItem('use_attributes') ? true : localStorage.getItem('use_attributes') === 'true';
	options.value.minimal_respondent_level = !localStorage.getItem('minimal_respondent_level') ? false : localStorage.getItem('minimal_respondent_level') === 'true';
	options.value.modified_rank = !localStorage.getItem('modified_rank') ? false : localStorage.getItem('modified_rank') === 'true';
	options.value.target_ids = !localStorage.getItem('target_ids') ? '' : localStorage.getItem('target_ids');

	// Clear the stored filter IDs from localStorage() when changing other project
	if (!props.filters.map((x) => x.id).includes(selected_filters.value[0])) {
		selected_filters.value = [];
	}
});

onMounted(() => {
	if (props.didUseVariables) {
		selected_variable.value = assign(props.pre_selected_variable);
		reflectToVariableOptions();
	}
});

watch (options.value, (newval) => {
	localStorage.setItem('minimum_sample', newval.minimum_sample);
	localStorage.setItem('compute_by', newval.compute_by);
	localStorage.setItem('use_attributes', newval.use_attributes);
	localStorage.setItem('minimal_respondent_level', newval.minimal_respondent_level);
	localStorage.setItem('modified_rank', newval.modified_rank);
	localStorage.setItem('target_ids', newval.target_ids);
});

watch (selected_filters,  (newval) => {
	localStorage.setItem('selected_filters', JSON.stringify(newval));
	
});

watch(selectedAll, (newVal, oldVal) => {
	selected_filters.value = [];
	if (newVal === true) {
		forEach(props.filters, filter => {
			selected_filters.value.push(filter.id);
		});
	}
});

watch(selectedAllTest, (newVal, oldVal) => {
	selected_tests.value = [];
	if (newVal === true) {
		forEach(props.tests, test => {
			if (test.isTrial) {
				selected_tests.value.push(test.id);
			}
		});
	}
});


watch(selected_variable, (newVal) => {
    if (typeof newVal === "object") {
        let variable_index = indexOf(newVal.config.criteria, head(newVal.config.criteria));
        options.value.variable_name = newVal.name;
        options.value.variable_value = variable_index + 1;
        refreshOptionVariablesList(variable_index);
    } else {
        options.value.variable_name = false;
    }

});

const reflectToVariableOptions = () => {
    refreshOptionVariablesList();
};

const refreshOptionVariablesList = (index = null) => {
    options.value.variables = [];
    let v_index = index ? index : (options.value.variable_value - 1);
    forEach(props.variables, (v) => {
        let v_ = {
            'variable': v.name,
            'value': v.config.criteria[v_index]?.value,
            'name': v.config.criteria[v_index]?.label,
            'key': v_index + 1,
			'equivalent': v.config.criteria[v_index]?.equivalent
        };
        options.value.variables.push(v_);
    });
	options.value.variable_value_text = selected_variable.value.config.criteria[options.value.variable_value - 1]?.label;
};

const displayCurrentProcessingFilter = computed(() => {
	let filter = props.filters[currentIndex.value];
	if (filter) {
		return 'Exporting ' + filter?.name;
	} else {
		return 'Please wait...';
	}
		
});

const process_export_caching = (action = 'raw') => {
	if (selected_filters.value.length === 0) return;
	if (typeof(selected_filters.value[currentIndex.value]) !== 'undefined') {
		processing.value = true;
		axios.post(route('exports.caching', [props.survey, selected_filters.value[currentIndex.value]]), {
			selected_tests: selected_tests.value,
			variable_value: options.value.variable_value,
			variable_name: options.value.variable_name,
			minimum_sample: options.value.minimum_sample,
			minimal_respondent_level: options.value.minimal_respondent_level,
			target_ids: options.value.target_ids
		}).then(response => {
			if (action === 'raw') {
				exportData(response.data.key)
			} else if (action === 'scores') {
				exportResult(response.data.key)
			}
		})
	}
};

const exportData = (key) => {
    if (key !== null) {
        options.value.count = currentIndex.value + 1;
        axios.post(route('exports.rawdata') + '?key=' + key, options.value).then(response => {
            exports.value.unshift(response.data)
            currentIndex.value++;
            if (typeof(selected_filters.value[currentIndex.value]) === 'undefined') {
                currentIndex.value = 0;
                options.value.count = 0;
                processing.value = false;
                toast.success("Exporting respondent level data completed.");
                rerun_tabular('raw');
            } else {
                process_export_caching('raw');
            }
        });
    } else {
        currentIndex.value++;
        if (typeof(selected_filters.value[currentIndex.value]) === 'undefined') {
            currentIndex.value = 0;
            options.value.count = 0;
            processing.value = false;
            toast.error("Not enough sample");
            // rerun_tabular('raw');
        } else {
            process_export_caching('raw');
        }
    }
	
};

const exportResult = (key) => {
	options.value.count = currentIndex.value + 1
	if (key == null) {
		currentIndex.value++;
		if (typeof(selected_filters.value[currentIndex.value]) === 'undefined') {
			if (options.value.iq_scores) {
				exportIQScores();
			} else {
				currentIndex.value = 0;
				options.value.count = 0;
				processing.value = false;
			}
		} else {
			process_export_caching('scores');
		}

	} else {
		axios.post(route('exports.scores') + '?key=' + key, options.value).then(response => {
			if (response.data.hasExports) {
				exports.value.unshift(response.data.export)
			}
			if (typeof(response.data.key) !== 'undefined') {
				cache_keys.value.push(response.data.key)
			}
			currentIndex.value++;
			if (typeof(selected_filters.value[currentIndex.value]) === 'undefined') {
				if (options.value.iq_scores) {
					exportIQScores();
				} else {
					currentIndex.value = 0;
					options.value.count = 0;
					processing.value = false;
				}
			} else {
				process_export_caching('scores');
			}
		})
	}
};

const exportIQScores = () => {
	axios.post(route('exports.tabular'), {
		cache_keys: cache_keys.value,
		tabular_name: options.value.tabular_name,
		use_attributes: options.value.use_attributes,
        variables: options.value.variables,
		variable_value_text: options.value.variable_value_text,
		variable_name: options.value.variable_name,
		modified_rank: options.value.modified_rank,
		minimal_respondent_level: options.value.minimal_respondent_level,
		filters: Object.values(props.filters.filter(x => selected_filters.value.includes(x.id)).map(x => x.name))
	}).then(response => {
		exports.value.unshift(response.data);
		currentIndex.value = 0;
		processing.value = false;
		cache_keys.value = [];
		toast.success("Tabular analysis completed.");
		rerun_tabular();
	}).catch(error => {
		processing.value = false;
		currentIndex.value = 0;
		if (cache_keys.value.length == 0) {
			toast.info("Not enough samples.");
		}
		cache_keys.value = [];
	});
};

const rerun_tabular = (type = 'scores') => {
	if (selected_variable.value && props.pre_selected_variable) {
		if (options.value.variable_value < selected_variable.value.config.criteria.length) {
			options.value.variable_value++;
			reflectToVariableOptions();
			if (type === 'scores') {
				process_export_caching('scores');
			} else if (type === 'raw') {
				process_export_caching('raw')
			}
		}
	}
};

const deleteAllExports = () => {
	form.get(route('exports.destroyAll', props.survey), {
		onSuccess: () => {
			toast.success("Exported files deleted");

		}
	});
};

const deleteExport = (exportable) => {
	form.delete(route('exports.destroy', exportable), {
		preserveScroll: true,
		preserverState: false,
		onSuccess: () => {
			toast.success("Exported file deleted");
			exports.value = assign(props.exportables);
		}
	});
};
</script>
<style scoped>
	.table {
		border-spacing: 0 3px;
	}

	i {
		font-size: 1rem !important;
	}

	.table tr {
		border-radius: 20px;
	}

	tr td:last-child,
	tr th:last-child {
		border-radius: 0 .625rem .625rem 0;
	}

	tr td:nth-child(1),
	tr th:nth-child(1) {
		border-radius: .625rem 0 0 .625rem;
	}

	.text-xxs {
		font-size: .6rem;
		line-height: .8rem;
	}
</style>
