<template>
	<app-layout title="Update Trial">
		<template #header>
			<h2 class="font-medium text-lg text-gray-800 leading-tight">
                Update Trial
            </h2>
		</template>

		<div>
			<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
				<div class="inline-flex rounded-md shadow-sm" role="group">
                    <Link :href="route('tests.index', survey)">
                        <button type="button" class="inline-flex items-center py-1 px-3 text-sm font-medium text-gray-600 bg-transparent rounded-l-md border border-gray-900 hover:bg-gray-900 hover:text-white focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900 focus:text-white uppercase">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
								<path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
							</svg>
							back
                        </button>
                    </Link>
					<template v-if="navigator.hasPrevious">
						<Link :href="navigator.previous">
							<button type="button" class="inline-flex items-center py-1 px-3 text-sm font-medium text-gray-600 bg-transparent border-t border-b border-gray-900 hover:bg-gray-900 hover:text-white focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900 focus:text-white uppercase">
								<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
									<path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
								</svg>
								Previous Trial
							</button>
						</Link>
					</template>
					<template v-if="navigator.hasNext">
						<Link :href="navigator.next">
							<button type="button" class="inline-flex items-center py-1 px-3 text-sm font-medium text-gray-600 bg-transparent border-t border-l border-b border-gray-900 hover:bg-gray-900 hover:text-white focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900 focus:text-white uppercase">
								Next trial
								<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
								</svg>
							</button>
						</Link>
					</template>
					<div>
						<a :href="app.engine_preview + 'survey_id=' + survey.id + '&preview=1&id=test'" target="_blank" class="inline-flex items-center py-1 px-3 text-sm font-medium text-gray-600 bg-transparent rounded-r-md border border-gray-900 hover:bg-gray-900 hover:text-white focus:z-10 focus:ring-2 focus:ring-gray-500 focus:bg-gray-900 focus:text-white uppercase">
							<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
								<path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
								<path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
							</svg>
							Preview
						</a>
					</div>
                </div>


				<!-- <Link :href="route('tests.index', survey)" class="mr-1">
					<action-button variant="primary" size="md">
						<i class="fa fa-arrow-left"></i>&nbsp;back
					</action-button>
				</Link>
				<template v-if="navigator.hasPrevious">
					<Link :href="navigator.previous" class="mr-1">
						<action-button :disabled="!navigator.hasPrevious" variant="primary" size="md">
							<i class="fa fa-chevron-left"></i>&nbsp;Previous Trial
						</action-button>
					</Link>
				</template>
				<template v-if="navigator.hasNext">
					<Link :href="navigator.next">
						<action-button :disabled="!navigator.hasNext" variant="primary" size="md" class="mr-1">
							Next Trial&nbsp;<i class="fa fa-chevron-right"></i>
						</action-button>
					</Link>
				</template>
				<action-button variant="primary" size="md">
                    <a :href="app.engine_preview + 'survey_id=' + survey.id + '&preview=1&id=test'" target="_blank"><i class="fa fa-external-link"></i> Preview</a>
                </action-button> -->
				<div class="mb-2"></div>
				<update-trial-form :survey="survey" :test="test" :navigator="navigator" />
            </div>
		</div>
	</app-layout>
</template>
<script>
	import AppLayout from '@/Layouts/AppLayout.vue'
	import UpdateTrialForm from './UpdateTrialForm.vue'
	import JetButton from '@/Jetstream/Button.vue'
	import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
	import ActionButton from '@/Components/ActionButton.vue'
	import { Link } from '@inertiajs/vue3';
    import { defineComponent } from 'vue';

	export default defineComponent({
		components: {
			AppLayout,
			UpdateTrialForm,
			JetButton,
			JetSecondaryButton,
			ActionButton,
			Link,
		},
		props: ['survey', 'test', 'navigator', 'app']
	})
</script>