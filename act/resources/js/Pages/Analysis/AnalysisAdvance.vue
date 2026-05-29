<template>
	<app-layout>
		<template #header>
			<div class="flex">
				<div class="flex-initial">
					<div class="mr-4">
						<projects-dropdown destination="groups.index"></projects-dropdown>
					</div>
				</div>
				<div class="flex-initial">
					<div class="mr-4 text-gray-500 text-md">
						/
					</div>
				</div>
				<div class="flex-initial">
					<div class="mr-4">
						<surveys-dropdown destination="analysis.index"></surveys-dropdown>
					</div>
				</div>
				
			</div>
			
		</template>
		<div>
			<div class="max-w-7xl mx-auto py-4 sm:px-6 lg:px-8">
				<Link class="mr-1" :href="route('analysis.index', survey)">
                    <action-button variant="primary" size="md">
                        <i class="fa fa-arrow-left"></i>&nbsp; Back
                    </action-button>
                </Link>
                <Link class="mr-1" :href="route('filters.index', project)">
                    <action-button variant="primary" size="md">
                        <i class="fa fa-filter" aria-hidden="true"></i>&nbsp;Filters
                    </action-button>
                </Link>

                <div class="mt-3 bg-white shadow rounded-lg overflow-hidden px-4 py-5">
                	<div class="grid grid-cols-12 gap-6">
                		<div class="col-span-12 border-b">
                			<strong class="text">
                				Respondent Level Data Merger: {{ survey.name }}
                			</strong>
                		</div>
                		<div class="col-span-5">
                			<div class="grid grid-cols-6 gap-6">
								<div class="col-span-6">
									<div class="bg-white">
										<nav class="flex flex-col sm:flex-row">
											<button class="text-gray-600 py-2 px-6 block hover:text-blue-500 focus:outline-none" :class="activeTab === 0 ? 'text-blue-500 border-b-4 font-medium border-blue-500' : ''" @click="activeTab=0">
												Surveys
											</button>
											<button class="text-gray-600 py-2 px-6 block hover:text-blue-500 focus:outline-none" :class="activeTab === 1 ? 'text-blue-500 border-b-4 font-medium border-blue-500' : ''" @click="activeTab=1">
												Filters
											</button>
											<button class="text-gray-600 py-2 px-6 block hover:text-blue-500 focus:outline-none" :class="activeTab === 2 ? 'text-blue-500 border-b-4 font-medium border-blue-500' : ''" @click="activeTab=2">
												Settings
											</button>
										</nav>
										
									</div>
									<div class="w-full bg-white mt-2 p-1 mx-auto">
										<div v-show="activeTab===0">
											<div class="h-96 overflow-auto">
												<template v-for="(s, kk) in allSurveys" :key="'s' + kk">
													<jet-label class="mt-2 ml-1">
														<div class="flex items-center">
															<jet-checkbox :value="s.id" v-model:checked="selected_surveys" />
															<div class="ml-2">
																{{ s.name }}
															</div>
														</div>
													</jet-label>
												</template>
											</div>
										</div>
										<div v-show="activeTab===1">
											<div class="h-96 overflow-auto">
												<template v-for="(filter, k) in filters" :key="'fl' + k">
													<jet-label class="mt-2 ml-1">
														<div class="flex items-center">
															<!-- <jet-checkbox v-model:checked="selected_filters" :value="filter.id" /> -->
															<input type="radio" :value="filter.id" v-model="selected_filter" />
															<div class="ml-2">
																{{ filter.name }} ({{ filter.count }})
															</div>
														</div>
													</jet-label>
												</template>
											</div>
										</div>
										<div v-show="activeTab===2">
											<div class="h-96">
												<jet-label class="mt-4">
													<div class="flex items-center">
														<jet-checkbox v-model:checked="options.use_attributes"></jet-checkbox>
														<div class="ml-2">
															Original texts as placeholder
														</div>
													</div>
												</jet-label>
											</div>
										</div>
									</div>
								</div>
                                
		                		<div class="col-span-6">
		                			<action-button size="md" class="mr-2" :class="{ 'opacity-25': processing }" :disabled="processing || selected_filter === null || selected_surveys.length === 0" @click="process_export_caching()" >
		                				Merge respondent data
		                			</action-button>
									<span v-if="processing" class="text-gray-400 text-sm">
										{{ progress }}
									</span>
		                		</div>
                			</div>
                		</div>
                		

                		<div class="col-span-7">
                			<Link v-if="exports.length > 0" :href="route('exports.destroyAll', survey)" class="pull-right text-xs text-blue-400">
                				<i class="fa fa-trash-o"></i> Delete All
                			</Link>
                			<div class="overflow-auto lg:overflow-visible">
    							<table class="w-full whitespace-no-wrap table text-gray-400 border-separate space-y-6 text-sm" v-if="exports.length > 0">
    								<thead class="bg-gray-100 text-gray-500">
    									<tr>
    										<!-- <th class="p-2 text-left">
    											<jet-label>
													<div class="flex items-center">
								                        <jet-checkbox />
								                    </div>
												</jet-label>
    										</th>	 -->
    										<th class="p-3 text-left">
    											File
    										</th>
    										<th class="p-3 text-left">
    											Type
    										</th>
    										<th class="p-3 text-left">
    											Owner
    										</th>
    										<th class="p-3 text-left">
    											Date
    										</th>
    										<th class="p-3 text-left">
    											Delete
    										</th>
    									</tr>
    								</thead>
    								<tbody>
    									<tr class="rounded bg-gray-100" v-for="(exportable, i) in exports" :key="'tr' + i">
    										<!-- <td class="pl-2">
    											<jet-label>
													<div class="flex items-center">
								                        <jet-checkbox />
								                    </div>
												</jet-label>
    										</td> -->
    										<td class="p-2">
    											<span class="text-xs bg-gray-600 rounded-md px-2 py-1 text-gray-50">
    												<a :href="route('exports.download', exportable)">
				                						{{ exportable.file }}
				                					</a>
    											</span>
    										</td>
    										<td class="p-2">
    											<div class="text-xs text-gray-500">
	                								{{ exportable.file_type }}
	                							</div>
    										</td>
    										<td class="p-2">
    											<div class="text-xs text-gray-500">
	                								{{ exportable.user.name }}
	                							</div>
    										</td>
    										<td class="p-2">
    											<div class="text-xs text-gray-500">
		                							{{ datetime(exportable.created_at) }}
		                						</div>
    										</td>
    										<td class="p-2">
    											<Link :href="route('exports.destroy', exportable)" class="text-xs text-blue-400">
    												<i class="fa fa-trash-o"></i>
    											</Link>
    										</td>
    									</tr>
    								</tbody>
    							</table>
    							<div v-else class="text-gray-500">
    								No exported files found in this survey.
    							</div>
    						</div>
                		</div>
                	</div>
                </div>
			</div>
			
			<div v-show="processing">
				<ssr-circle-loading data-title="Please wait..." v-pre></ssr-circle-loading>
			</div>
			<jet-dialog-modal :show="modals.namesDialog" @close="modals.namesDialog = false">
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
				</template>

				<template #footer>
					<action-button variant="primary" size="md" class="ml-2" @click="modals.namesDialog=false">
		            	Close
		            </action-button>
				</template>
			</jet-dialog-modal>
		</div>
	</app-layout>
</template>
<script>

	import AppLayout from '@/Layouts/AppLayout.vue'
	import SurveysDropdown from '@/Components/SurveysDropdown.vue'
	import ProjectsDropdown from '@/Components/ProjectsDropdown.vue'
	import JetButton from '@/Jetstream/Button.vue'
	import JetLabel from '@/Jetstream/Label.vue'
	import JetCheckbox from '@/Jetstream/Checkbox.vue'
	import JetDialogModal from '@/Jetstream/DialogModal.vue'
	import JetInput from '@/Jetstream/Input.vue'
	import ActionButton from '@/Components/ActionButton.vue'

	import forEach from 'lodash/forEach'
	import indexOf from 'lodash/indexOf'
	import find from 'lodash/find'

	import { Link } from '@inertiajs/vue3';
    import { defineComponent } from 'vue';



	export default defineComponent({
		components: {
			AppLayout,
			SurveysDropdown,
			JetButton,
			JetLabel,
			JetCheckbox,
			ProjectsDropdown,
			JetDialogModal,
			JetInput,
			ActionButton,
			Link,
		},

		props: ['filters', 'survey', 'project', 'exportables', 'allSurveys'],

		data() {
			return {
				form: this.$inertia.form({}),
				selected_filter: null,
                selected_surveys: [],
				options: {
					compute_by: 'count',
					export_results: false,
					iq_scores: true,
					count: 1,
					tabular_name: 'Tabular IQ Scores',
					computed_name: '$count$ - $filter$',
					responses_name: 'Respondent Level Data',
					use_attributes: true
				},
				currentIndex: 0,
				processing: false,
				exports: [],
				selectedAll: false,
				cache_keys: [],
				modals: {
					namesDialog: false,
				},
				activeTab: 0,
				cache_keys: [],
			}
		},

		created() {
			this.exports = this.exportables;
		},

		watch: {
			selectedAll(newVal, oldVal) {
				this.selected_filters = []
				if (newVal === true) {
					forEach(this.filters, filter => {
						this.selected_filters.push(filter.id);
					})
				}
			}
		},

		computed: {
			progress: function() {
				let survey_key = indexOf(this.allSurveys, find(this.allSurveys, x => {
					return x.id == this.selected_surveys[this.currentIndex];
				}));
				return "Processing survey: " + this.allSurveys[survey_key].name;
			}
		},

		methods: {
			process_export_caching() {
				if (this.selected_surveys.length === 0) return;

				if (typeof(this.selected_surveys[this.currentIndex]) !== 'undefined') {
					this.processing = true;
					axios.get(route('exports.caching', [this.selected_surveys[this.currentIndex], this.selected_filter])).then(response => {
						if (response.data.key) {
							this.cache_keys.push(response.data.key);
						}
						this.currentIndex++;
						this.process_export_caching();
					});
				} else {
					this.startMerging();
				}
			},
			startMerging() {
				if (this.cache_keys.length > 0) {
					this.processing = true;
					axios.post(route('exports.merging'), {
						keys: this.cache_keys,
						use_attributes: this.options.use_attributes,
					}).then(response => {
						this.currentIndex = 0;
						this.processing = false;
						this.exports.unshift(response.data);
					});
				} else {
					this.currentIndex = 0;
					this.processing = false;
					alert("No sample found");
				}
				
			},
			exportResult(key) {
				this.options.count = this.currentIndex + 1
				axios.post(route('exports.scores') + '?key=' + key, this.options).then(response => {
					if (response.data.hasExports) {
						this.exports.unshift(response.data.export)
					}
					if (response.data.key != 'null') {
						this.cache_keys.push(response.data.key)
					}
					this.currentIndex++
					if (typeof(this.selected_filters[this.currentIndex]) === 'undefined') {
						if (this.options.iq_scores) {
							this.exportIQScores()
						} else {
							this.currentIndex = 0
							this.options.count = 0
							this.processing = false
						}
					} else {
						this.process_export_caching('scores')
					}
				})
			},
			exportIQScores() {
				axios.post(route('exports.tabular'), {
					cache_keys: this.cache_keys,
					tabular_name: this.options.tabular_name,
					use_attributes: this.options.use_attributes
				}).then(response => {
					this.exports.unshift(response.data)
					this.currentIndex = 0
					this.processing = false
					this.cache_keys = []
				})
			}
		}
	})
</script>
<style scoped="true">
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

	
</style>