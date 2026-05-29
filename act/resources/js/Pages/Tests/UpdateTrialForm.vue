<template>
	<custom-form @submitted="updateTrial">
		<template #title>
			Trial
		</template>

		<template #description>
			Update trial
		</template>

		<template #form>
			<div class="col-span-6">
				<jet-label class="mb-1">
					Trial title
					<custom-switch v-model:checked="enableCKEditor" class="float-right">
						Editor
					</custom-switch>
				</jet-label>
				<div v-if="enableCKEditor">
					<ckeditor :editor="editor" v-model="form.config.title" :config="editorConfig"></ckeditor>
				</div>
				<!-- <textarea v-else class="block w-full form-input" v-model="form.config.title" rows="6"></textarea> -->
				<prism-editor v-else class="my-editor editor-height" v-model="form.config.title" :highlight="highlighter" line-numbers></prism-editor>
				<template v-if="!form.config.title">
					<jet-label class="mt-4">
						<div class="flex items-center">
							<jet-checkbox v-model:checked="form.config.main_trial" />

							<div class="ml-2">
								Main Trial
							</div>
						</div>
					</jet-label>
					<jet-label class="mt-3">
						<div class="flex items-center">
							<jet-checkbox v-model:checked="form.config.random" />

							<div class="ml-2">
								Randomize targets
							</div>
						</div>
					</jet-label>
					<jet-label class="mt-3">
						<div class="flex items-center">
							<jet-checkbox v-model:checked="form.config.random_prime" />

							<div class="ml-2">
								Randomize primes
							</div>
						</div>
					</jet-label>
					<jet-label class="mt-3">
						<div class="flex items-center">
							<jet-checkbox v-model:checked="form.config.prime_as_next" />

							<div class="ml-2">
								Prime as next button
							</div>
						</div>
					</jet-label>
					<!-- <action-button type="button" class="mt-2">
						<i class="fa fa-cog"></i>&nbsp;trial logic
					</action-button> -->
					<div class="mt-4 flex justify-items-stretch">
						<h2 class="text-lg font-semibold">
							<u>Primes ({{ form.primes.length }})</u>
						</h2>
						<action-button type="button" @click="showAddDialog" class="ml-2">add prime</action-button>
						<a href="javascript:;" class="justify-self-end self-end" @click="tableView=!tableView">
							<span v-if="!tableView">
								<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
									<path fill-rule="evenodd" d="M5 4a3 3 0 00-3 3v6a3 3 0 003 3h10a3 3 0 003-3V7a3 3 0 00-3-3H5zm-1 9v-1h5v2H5a1 1 0 01-1-1zm7 1h4a1 1 0 001-1v-1h-5v2zm0-4h5V8h-5v2zM9 8H4v2h5V8z" clip-rule="evenodd" />
								</svg>
							</span>
							<span v-else>
								<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
									<path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" />
									<path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" />
								</svg>
							</span>
						</a>
					</div>
					<div class="mt-4 mx-auto container grid grid-cols-3 gap-3" v-if="form.primes.length > 0 && !tableView">
						<PrimeCard v-for="(prime, i) in form.primes" :key="i">
							<template #n>
								{{ i + 1 }}
							</template>
							<template #index>
								{{ prime.index }}
							</template>
							<template #prime>
								<span v-html="prime.prime">
								</span>
							</template>
							<template #equivalent>
								{{ prime.equivalent }}
							</template>
							<template #logic>
								<span v-if="prime.hasOwnProperty('config')">
									{{ criteriaToString(prime.config.conditions) }}
								</span>
								<span v-else>
									None
								</span>
							</template>
							<template #actions>
								<a href="javascript:;" class="text-sm text-blue-400 mr-2 inline-flex items-center" @click="deletePrime(i)">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
										<path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
									</svg> Delete
								</a>
								<a href="javascript:;" class="text-sm text-blue-400 inline-flex items-center" @click="updatePrimeDialog(i)">
									<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
										<path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
										<path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
									</svg> Edit
								</a>
							</template>
						</PrimeCard>
					</div>
					<div class="overflow-auto mt-3 max-h-80" v-if="tableView">
						<table class="min-w-full divide-y divide-gray-200 table-fixed" v-if="form.primes.length > 0">
							<thead class="bg-gray-100">
								<tr>
									<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
										#
									</th>
									<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
										Index
									</th>
									<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
										Prime
									</th>
									<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
										Logic
									</th>
									<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
										Equivalent
									</th>
									
									<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
										Actions
									</th>
								</tr>
							</thead>
							<tbody class="bg-white divide-y divide-gray-200">
								<tr class="hover:bg-gray-100" v-for="(prime, i) in form.primes" :key="i">
									<td class="py-2 px-6 text-xs font-medium text-gray-500 whitespace-nowrap">
										{{ i + 1 }}
									</td>
									<td class="py-2 px-6 text-xs font-medium text-gray-500 whitespace-nowrap">
										{{ prime.index }}
									</td>
									<td class="py-2 px-6 text-xs font-medium text-gray-500 whitespace-nowrap" :class="{ 'w-28': hasImage(prime.prime) }">
										<template v-if="prime.hasOwnProperty('config')">
											<strong v-if="prime.config.conditions.length > 0" class="text-gray-900">
												*
											</strong>
										</template>
										<span v-html="prime.prime">
										</span>
									</td>
									<td class="py-2 px-6 text-xs font-medium text-gray-500 whitespace-nowrap">
										<template v-if="prime.hasOwnProperty('config')">
											{{ criteriaToString(prime.config.conditions) }}
										</template>
									</td>
									<td class="py-2 px-6 text-xs font-medium text-gray-500 whitespace-nowrap">
										<template v-if="prime.hasOwnProperty('config')">
											<strong v-if="prime.config.conditions.length > 0" class="text-gray-900">
												*
											</strong>
										</template>
										{{ prime.equivalent }}
									</td>
									
									<td class="py-2 px-6 text-xs font-medium text-gray-500 whitespace-nowrap">
										<a href="javascript:;" class="text-xs text-blue-400 mr-1" @click="deletePrime(i)">
											<i class="fa fa-trash-o"></i> Delete
										</a>
										<a href="javascript:;" class="text-xs text-blue-400" @click="updatePrimeDialog(i)">
											<i class="fa fa-pencil"></i> Edit
										</a>
									</td>
								</tr>
							</tbody>
						</table>
					</div>

					<div class="mt-6 flex flex-inline">
						<h2 class="text-lg font-semibold">
							<u>
								Targets ({{ form.targets.length }})
							</u>
						</h2>
						<action-button class="ml-2" type="button" @click="addTargetDialog">
							add target
						</action-button>
					</div>
					<div class="overflow-auto mt-3 max-h-80 pt-2">
							<table class="min-w-full divide-y divide-gray-200 table-fixed" v-if="form.targets.length > 0">
								<thead class="bg-gray-100">
									<tr>
										<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
											#
										</th>
										<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
											Index
										</th>
										<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
											Target
										</th>
										<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
											Equivalent
										</th>
										<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
											Value
										</th>
										<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
											Colours
										</th>
										<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
											Actions
										</th>
									</tr>
								</thead>
								<tbody class="bg-white divide-y divide-gray-200">
									<tr class="hover:bg-gray-100" v-for="(target, i) in form.targets" :key="'tr' + i">
										<td class="py-3 px-6 text-xs font-medium text-gray-500 whitespace-nowrap">
											{{ i + 1 }}
										</td>
										<td class="py-3 px-6 text-xs font-medium text-gray-500 whitespace-nowrap">
											{{ target.index }}
										</td>
										<td class="py-3 px-6 text-xs font-medium text-gray-500 whitespace-nowrap" :class="{ 'w-28': hasImage(target.target) }">
											<div v-html="target.target">
											</div>
										</td>
										<td class="py-3 px-6 text-xs font-medium text-gray-500 whitespace-nowrap" >
											{{ target.equivalent }}
											<span class="bg-gray-500 text-xs text-gray-50 rounded-md px-1 ml-1" v-if="target.can_unselect">
												Unselect
											</span>
										</td>
										<td class="py-3 px-6 text-xs font-medium text-gray-500 whitespace-nowrap" >
											{{ target.value }}
										</td>
										<td class="py-3 px-6 text-xs font-medium text-gray-500 whitespace-nowrap">
											<span class="text-xs text-gray-50 rounded-md px-1 ml-1" v-if="target.colors.normal" :style="'background-color: ' + target.colors.normal">	
												N
											</span>
											<span class="text-xs text-gray-50 rounded-md px-1 ml-1" v-if="target.colors.hover" :style="'background-color: ' + target.colors.hover">	
												H
											</span>
											<span class="text-xs text-gray-50 rounded-md px-1 ml-1" v-if="target.colors.active" :style="'background-color: ' + target.colors.active">	
												A
											</span>
										</td>
										<td class="py-3 px-6 text-xs font-medium text-gray-500 whitespace-nowrap">
											<a href="javascript:;" class="text-xs text-blue-400 mr-1" @click="deleteTarget(i)">
												<i class="fa fa-trash-o"></i> Delete
											</a>
											<a href="javascript:;" class="text-xs text-blue-400" @click="updateTargetDialog(i)">
												<i class="fa fa-pencil"></i> Edit
											</a>
										</td>
									</tr>
								</tbody>
							</table>
						</div>

					<div class="grid grid-cols-1 gap-4">
						<!-- <div class="mt-6 hidden">
							<jet-label class="mb-1">
								Prime Header 
								<custom-switch v-model:checked="enableCKEditor" class="float-right">
									Editor
								</custom-switch>
							</jet-label>
							<div v-if="enableCKEditor">
								<ckeditor :editor="editor" v-model="form.config.prime_header" :config="editorConfig"></ckeditor>
							</div>
							<textarea v-else class="block w-full form-input" v-model="form.config.prime_header" rows="10"></textarea> 
							<prism-editor v-else class="my-editor h-52" style="height: 14rem !important;" v-model="form.config.prime_header" :highlight="highlighter" line-numbers></prism-editor>
						</div> -->
						<div class="mt-6">
							<jet-label class="mb-1">
								Main Header 
								<custom-switch v-model:checked="enableCKEditor" class="float-right">
									Editor
								</custom-switch>
							</jet-label>
							<div v-if="enableCKEditor">
								<ckeditor :editor="editor" v-model="form.config.main_header" :config="editorConfig"></ckeditor>
							</div>
							<!-- <textarea v-else class="block  w-full form-input" v-model="form.config.main_header" rows="10"></textarea> -->
							<prism-editor v-else class="my-editor editor-height" v-model="form.config.main_header" :highlight="highlighter" line-numbers></prism-editor>
						</div>
					</div>

					<div class="mt-2">
						<jet-label>Empty selection text</jet-label>
						<jet-input class="w-1/2" type="text" v-model="form.config.empty_selection"></jet-input>
					</div>
					<div class="mt-2">
						<jet-label>Bottom text</jet-label>
						<jet-input class="w-1/2" type="text" v-model="form.config.bottom_text"></jet-input>
					</div>
					<jet-label class="mt-3" value="Selection limit (0 = can select all)">
					</jet-label>
					<jet-input type="number" v-model="form.config.limit" class="w-1/6"></jet-input>
					<jet-label class="mt-3" value="Display targets by">
					</jet-label>
					<jet-input type="number" v-model="form.config.display_by" class="w-1/6"></jet-input>
					<jet-label class="mt-3">
						<div class="flex items-center">
							<jet-checkbox v-model:checked="form.config.single_select" />

							<div class="ml-2">
								Single select
							</div>
						</div>
					</jet-label>
					<jet-label class="mt-3">
						<div class="flex items-center">
							<jet-checkbox v-model:checked="form.config.auto_next" />

							<div class="ml-2">
								Auto next trial
							</div>
						</div>
					</jet-label>
				</template>
			</div>
		</template>

		<template #actions>
			<jet-action-message :on="form.recentlySuccessful" class="mr-1">
				Saved.
			</jet-action-message>

			<Link :href="route('tests.index', survey)" class="mr-2">
				<action-button variant="primary" size="md">
					<i class="fa fa-arrow-left"></i>&nbsp;back
				</action-button>
			</Link>
			<template v-if="navigator.hasPrevious">
				<Link :href="navigator.previous" class="mr-2">
					<action-button :disabled="!navigator.hasPrevious" variant="primary" size="md">
						<i class="fa fa-chevron-left"></i>&nbsp;Previous Trial
					</action-button>
				</Link>
			</template>
			<template v-if="navigator.hasNext">
				<Link :href="navigator.next" class="mr-2">
					<action-button :disabled="!navigator.hasNext" variant="primary" size="md">
						Next Trial&nbsp;<i class="fa fa-chevron-right"></i>
					</action-button>
				</Link>
			</template>

			<action-button v-if="false" variant="primary" size="md" :class="{ 'opacity-25': form.processing}" :disabled="form.processing" @click="updateTrial">
				<i class="fa fa-floppy-o"></i>&nbsp;save
			</action-button>
		</template>
	</custom-form>

	<jet-dialog-modal :show="modals.createPrimeModal" @close="modals.createPrimeModal = false">
		<template #title>
			Add Prime
		</template>

		<template #content>
			<jet-label class="mb-1">
				Prime
				<custom-switch v-model:checked="enableCKEditor" class="float-right">
					Editor
				</custom-switch>
			</jet-label>
			<div v-if="enableCKEditor">
				<ckeditor :editor="editor" v-model="prime_form.prime" @input="unformatContent(1)" :config="editorConfig"></ckeditor>
			</div>
			<!-- <textarea v-else class="block w-full form-input" v-model="prime_form.prime" @keyup="unformatContent(1)"></textarea> -->
			<prism-editor v-else class="my-editor editor-height" v-model="prime_form.prime" :highlight="highlighter" line-numbers @keyup="unformatContent(1)"></prism-editor>
			<h2 class="text-lg mt-3">Equivalent</h2>
			<jet-input type="text" class="w-full" v-model="prime_form.equivalent"></jet-input>
		</template>

		<template #footer>
			<jet-secondary-button @click="modals.createPrimeModal = false">
                nevermind
            </jet-secondary-button>
            <jet-button class="ml-2" @click="addPrimeList">
            	Add prime
            </jet-button>
		</template>
	</jet-dialog-modal>

	<jet-dialog-modal :show="modals.updatePrimeModal" @close="modals.updatePrimeModal = false">
		<template #title>
			Update Prime
		</template>

		<template #content>
			<jet-label class="mb-1">
				Prime
				<custom-switch v-model:checked="enableCKEditor" class="float-right">
					Editor
				</custom-switch>
			</jet-label>
			<div v-if="enableCKEditor">
				<ckeditor :editor="editor" v-model="prime_form.prime" :config="editorConfig"></ckeditor>
			</div>
			<!-- <textarea v-else class="block w-full form-input" v-model="prime_form.prime"></textarea> -->
			<prism-editor v-else class="my-editor editor-height" v-model="prime_form.prime" :highlight="highlighter" line-numbers></prism-editor>
			<div class="grid grid-cols-4 gap-1">
				<div class="col-span-3">
					<h2 class="text-lg mt-3">Equivalent</h2>
					<jet-input type="text" class="w-full" v-model="prime_form.equivalent"></jet-input>
				</div>
				<div>
					<h2 class="text-lg mt-3">Index</h2>
					<jet-input type="text" class="w-full" v-model="prime_form.index"></jet-input>
				</div>
			</div>
			<div class="flex items-stretch">
				<div class="flex-1 w-full pt-4">
					<div class="relative p-2 border border-gray-300 border-solid rounded h-full">
						<div class="grp-header bg-white p-2 uppercase text-xs text-gray-500">
							skip logic
						</div>
						<div class="p-2 mt-3">
							<div class="col-span-6 mb-2">
								<jet-button type="button" variant="secondary" @click="addSegment" class="border-gray-600 py-1 text-xs bg-gray-300 text-gray-900 hover:bg-gray-200 px-1">
									<span v-if="prime_form.config.conditions.length > 0">Add Segment</span><span v-else>Add Condition</span>
								</jet-button>
							</div>
							<div class="col-span-6" v-for="(condition, index) in prime_form.config.conditions" :key="index">
								<criteria-contents :deep="0" :condition="condition" :indexes="[index]"/>
							</div>
							<div class="col-span-6 mt-3">
								<code class="text-black-500 text-xs bg-gray-200">{{ criteriaToString(prime_form.config.conditions) }}</code>
							</div>
						</div>
					</div>
				</div>
			</div>
		</template>

		<template #footer>
			<jet-secondary-button @click="modals.updatePrimeModal = false">
                nevermind
            </jet-secondary-button>
            <jet-button class="ml-2" @click="updatePrimeList">
            	update prime
            </jet-button>
		</template>
	</jet-dialog-modal>

	<jet-dialog-modal :show="modals.createTargetModal" @close="modals.createTargetModal = false">
		<template #title>
			Add Target
		</template>

		<template #content>
			<jet-label class="mb-1">
				Target
				<custom-switch v-model:checked="enableCKEditor" class="float-right">
					Editor
				</custom-switch>
			</jet-label>
			<div v-if="enableCKEditor">
				<ckeditor :editor="editor" v-model="target_form.target" @input="unformatContent(2)" :config="editorConfig"></ckeditor>
			</div>
			<!-- <textarea v-else class="block w-full form-input" v-model="target_form.target" @keyup="unformatContent(2)" rows="7"></
			textarea> -->
			<prism-editor v-else class="my-editor editor-height" v-model="target_form.target" :highlight="highlighter" line-numbers @keyup="unformatContent(2)"></prism-editor>
			<div class="grid grid-cols-3 gap-1">
				<div class="col-span-2">
					<h2 class="text-lg mt-3">Equivalent</h2>
					<jet-input type="text" class="w-full" v-model="target_form.equivalent"></jet-input>
				</div>
				<div>
					<h2 class="text-lg mt-3">Value</h2>
					<jet-input class="w-full" v-model="target_form.value" type="number"></jet-input>
				</div>
			</div>
			<jet-label class="mt-4">
				<div class="flex items-center">
                    <jet-checkbox v-model:checked="target_form.can_unselect" />
                    <div class="ml-2">
                        Can unselect other targets
                    </div>
                </div>
			</jet-label>
			<div class="grid grid-cols-3 gap-1">
				<div>
					<jet-label class="mt-4" value="Colors: Normal"></jet-label>
					<jet-input type="color" class="w-full" v-model="target_form.colors.normal"></jet-input>
				</div>
				<div>
					<jet-label class="mt-4" value="Colors: Hover"></jet-label>
					<jet-input type="color" class="w-full" v-model="target_form.colors.hover"></jet-input>
				</div>
				<div>
					<jet-label class="mt-4" value="Colors: Active"></jet-label>
					<jet-input type="color" class="w-full" v-model="target_form.colors.active"></jet-input>
				</div>
			</div>
		</template>

		<template #footer>
			<jet-secondary-button @click="modals.createTargetModal = false">
                nevermind
            </jet-secondary-button>
            <jet-button class="ml-2" @click="addTargetList">
            	Add target
            </jet-button>
		</template>
	</jet-dialog-modal>

	<jet-dialog-modal :show="modals.updateTargetModal" @close="modals.updateTargetModal = false">
		<template #title>
			Update Target
		</template>

		<template #content>
			<jet-label class="mb-1">
				Target
				<custom-switch v-model:checked="enableCKEditor" class="float-right">
					Editor
				</custom-switch>
			</jet-label>
			<div v-if="enableCKEditor">
				<ckeditor :editor="editor" v-model="target_form.target" :config="editorConfig"></ckeditor>
			</div>
			<!-- <textarea v-else class="block w-full form-input" v-model="target_form.target"></textarea> -->
			<prism-editor v-else class="my-editor editor-height" v-model="target_form.target" :highlight="highlighter" line-numbers></prism-editor>
			<div class="grid grid-cols-4 gap-1">
				<div class="col-span-2">
					<h2 class="text-lg mt-3">Equivalent</h2>
					<jet-input type="text" class="w-full" v-model="target_form.equivalent"></jet-input>
				</div>
				<div>
					<h2 class="text-lg mt-3">Value</h2>
					<jet-input class="w-full" v-model="target_form.value" type="number"></jet-input>
				</div>
				<div>
					<h2 class="text-lg mt-3">Index</h2>
					<jet-input class="w-full" v-model="target_form.index" type="number"></jet-input>
				</div>
			</div>
			<jet-label class="mt-4">
				<div class="flex items-center">
                    <jet-checkbox v-model:checked="target_form.can_unselect" />
                    <div class="ml-2">
                        Can unselect other targets
                    </div>
                </div>
			</jet-label>
			<div class="grid grid-cols-3 gap-1">
				<div>
					<jet-label class="mt-4" value="Colors: Normal"></jet-label>
					<jet-input type="color" class="w-full" v-model="target_form.colors.normal"></jet-input>
				</div>
				<div>
					<jet-label class="mt-4" value="Colors: Hover"></jet-label>
					<jet-input type="color" class="w-full" v-model="target_form.colors.hover"></jet-input>
				</div>
				<div>
					<jet-label class="mt-4" value="Colors: Active"></jet-label>
					<jet-input type="color" class="w-full" v-model="target_form.colors.active"></jet-input>
				</div>
			</div>
		</template>

		<template #footer>
			<jet-secondary-button @click="modals.updateTargetModal = false">
                nevermind
            </jet-secondary-button>
            <jet-button class="ml-2" @click="updateTargetList">
            	update target
            </jet-button>
		</template>
	</jet-dialog-modal>
	<teleport to="body">
		<button @click="updateTrial" class="uppercase text-white px-5 mr-1 inline-flex items-center py-2.5 text-sm tracking-widest rounded-lg shadow-sm font-sans focus:ring-blue-200 transition ease-in-out duration-150 font-medium bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 h40 col-flex-1 j-center floating-button tracking-wide"
		>
			<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
				<path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
			</svg>&nbsp;save
		</button>
    </teleport>
	<Loader :show="form.processing">
		Please wait...
	</Loader>
</template>
<script setup>
import JetActionMessage from '@/Jetstream/ActionMessage.vue';
import JetButton from '@/Jetstream/Button.vue';
import JetInput from '@/Jetstream/Input.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue';
import JetCheckbox from '@/Jetstream/Checkbox.vue';
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import ActionButton from '@/Components/ActionButton.vue';
import CustomSwitch from '@/Components/Switch.vue';
import Loader from "@/Components/Loader.vue";
import PrimeCard from '../../Components/PrimeCard.vue';

import CustomForm from '@/Components/Form.vue'

import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import CKEditor from '@ckeditor/ckeditor5-vue';

// import sanitizeHtml from 'sanitize-html'

import capitalize from 'lodash/capitalize'
import forEach from 'lodash/forEach'
import maxBy from 'lodash/maxBy'

import UploadAdapterPlugin from '@/Functions/UploadAdapter'

import { PrismEditor } from 'vue-prism-editor';
import 'vue-prism-editor/dist/prismeditor.min.css'; // import the styles somewhere

// import highlighting library (you can use any library you want just return html string)
import { highlight, languages } from 'prismjs/components/prism-core';
import 'prismjs/components/prism-clike';
import 'prismjs/components/prism-javascript';
import 'prismjs/components/prism-markup';
import 'prismjs/components/prism-css';
import 'prismjs/themes/prism-tomorrow.css'; // import syntax highlighting styles

import { Link, useForm } from '@inertiajs/vue3';
import { ref, onBeforeMount, provide } from 'vue';
import Helpers from '../../Mixins/Helpers';

import CriteriaContents from './Criteria.vue';

const props = defineProps(['survey', 'test', 'navigator']);

const { datetime, toast, hasString, hasImage, assign, $state, sanitize } = Helpers();

const form = useForm({
	config: {},
	primes: [],
	targets: [],
	type: null
});

const code = ref('.test{ width: 50; }")');

const modals = ref({
	createPrimeModal: false,
	createTargetModal: false,
	updatePrimeModal: false,
	updateTargetModal: false
});

const ckeditor = CKEditor.component;

const editor = ref(ClassicEditor);

const prime_form = ref({
	prime: '',
	equivalent: '',
	config: {
		conditions: []
	},
});

const target_form = ref({
	target: '',
	equivalent: '',
	can_unselect: false,
	colors: {
		normal: null,
		hover: null,
		active: null
	},
	value: 0,
	index: null
});

const enableCKEditor = ref(true);
const selectedIndex = ref(null);
const editorConfig = ref({
	extraPlugins: [UploadAdapterPlugin]
});
const tableView = ref(false);

onBeforeMount(() => {
	form.config = assign(props.test.config);
	form.primes = assign(props.test.primes);
	form.targets = assign(props.test.targets);
});

provide('state', $state);

// methods
const highlighter = (code) => {
	return highlight(code, languages.markup);
};

const updateTrial = () => {
	if (form.targets.length == 0 || form.primes.length == 0) {
		form.config.type = "descriptive";
	} else {
		form.config.type = "trial";
	}
	form.put(route('tests.update', [props.survey, props.test]), {
		preserveState: true,
		preserveScroll: true,
		onSuccess: () => {
			toast.success("Trial successfully updated");
		}
	})
};

const unformatContent = (t, val = null) => {
	if (t == 1) {
		if (hasImage(prime_form.prime)) return;
		prime_form.value.equivalent = capitalize(sanitize(prime_form.value.prime)).split(' i ').join(' I ')
	
	} else if (t == 2) {
		if (hasImage(target_form.value.target)) return;
		target_form.value.equivalent = capitalize(sanitize(target_form.value.target)).split(' i ').join(' I ')
	} else {
		return sanitize(val).split(' i ').join(' I ')
	}
};

const showAddDialog = () => {
	prime_form.value = assign(prime_form);
	clearPrimeForm();
	modals.value.createPrimeModal = true;
};

const addPrimeList = () => {
	let primes = assign(prime_form.value.prime.split("\n"));
	let prime_length = getMaxIndex(form.primes);
	forEach (primes, (p, i) => {
		prime_length++;
		let prime = {
			prime: p,
			equivalent: primes.length > 1 ? unformatContent(3, p) : prime_form.value.equivalent,
			index: prime_length,
		}
		form.primes.push(prime);
	});
	modals.value.createPrimeModal = false;
	clearPrimeForm();
};

const addTargetList = () => {
	let targets = target_form.value.target.split('\n');
	let target_length = getMaxIndex(form.targets)
	forEach(targets, (t, i) => {
		target_length++
		let target = {
			target: t,
			equivalent: targets.length > 1 ? unformatContent(3, t) : target_form.value.equivalent,
			index: target_length,
			can_unselect: target_form.value.can_unselect,
			colors: target_form.value.colors,
            value: targets.length > 1
                ? target_length
                : (typeof target_form.value.value === 'number' && !Number.isNaN(target_form.value.value))
                    ? target_form.value.value
                    : target_length
		}
		form.targets.push(target);
	})
	modals.value.createTargetModal = false;
	target_form.value.target = '';
	target_form.value.equivalent = '';
};

const updatePrimeDialog = (index) => {
	selectedIndex.value = index;
	prime_form.value = assign(form.primes[index]);
	if (typeof prime_form.value.config === 'undefined') {
		prime_form.value.config = {
			conditions: []
		};
	}
	$state.criteria = prime_form.value;
	modals.value.updatePrimeModal = true;
};

const updatePrimeList = () => {
	form.primes[selectedIndex.value] = assign(prime_form.value);
	clearPrimeForm();
	modals.value.updatePrimeModal = false;
};

const updateTargetDialog = (index) => {
	selectedIndex.value = index;
	target_form.value = assign(form.targets[index]);
	modals.value.updateTargetModal = true;
};

const updateTargetList = () => {
	form.targets[selectedIndex.value] = assign(target_form.value);
	modals.value.updateTargetModal = false;
};

const deletePrime = (index) => {
	form.primes.splice(index, 1);
};

const deleteTarget = (index) => {
	form.targets.splice(index, 1);
};

const addTargetDialog = () => {
	target_form.value = {
		target: '',
		equivalent: '',
		can_unselect: false,
		colors: {
			normal: null,
			hover: null,
			active: null
		}
	};
	modals.value.createTargetModal = true;
};

const onEditorChange = (data) => {
	form.config.title = data;
};

const clearPrimeForm = () => {
	prime_form.value.prime = '';
	prime_form.value.equivalent = '';
};

const getMaxIndex = (values) => {
	return maxBy(values, 'index')?.index ?? 0;
};

const addSegment = () => {
	$state.addSegment();
};

const criteriaToString = (str) => {
	return $state.criteriaToString(str);
};
</script>

<style scoped="true">
	.grp-header {
		position: absolute;
		top: 0px;
		left: 16px;
		transform: translateY(-50%);
	}
</style>