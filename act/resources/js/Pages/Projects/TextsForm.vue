<template>
	<jet-form-section @submitted="saveSettings">
		<template #title>
			Project Texts and Buttons
		</template>

		<template #description>
			Configure your project texts and buttons
		</template>

		<template #form>
            <div class="col-span-12" v-if="form.config.progress">
            	<jet-label>
					<div class="flex items-center">
                        <jet-checkbox v-model:checked="form.config.progress.show" />

                        <div class="ml-2">
                            Enable progress information
                        </div>
                    </div>
				</jet-label>
				<CustomInput label="Progress text" v-model="form.config.progress.text" />
				<CustomInput label="Next button" v-model="form.config.buttons.next" />
				<jet-input-error :message="form.errors['config.buttons.next']" class="mt-2" />
				<CustomInput label="Final button" v-model="form.config.buttons.final" />
				<jet-input-error :message="form.errors['config.buttons.final']" class="mt-2" />
				<CustomInput label="Saving progress text" v-model="form.config.texts.saving" />
				<jet-input-error :message="form.errors['config.texts.saving']" class="mt-2" />
				<CustomInput label="Redirecting text" v-model="form.config.texts.redirecting" />
				<jet-input-error :message="form.errors['config.texts.redirecting']" class="mt-2" />
				<CustomInput label="Error starting the app" v-model="form.config.texts.error_start" />
				<jet-input-error :message="form.errors['config.texts.error_start']" class="mt-2" />
				<CustomInput label="Error saving progress" v-model="form.config.texts.error_saving" />
				<jet-input-error :message="form.errors['config.texts.error_saving']" class="mt-2" />
            </div>
			<div class="col-span-12">
				<jet-label value="Custom CSS"></jet-label>
				<prism-editor class="my-editor h-52" v-model="form.config.css" :highlight="highlighter2" line-numbers></prism-editor>
			</div>
			<div class="col-span-12">
				<jet-label value="Custom JS"></jet-label>
				<prism-editor class="my-editor h-52" v-model="form.config.js" :highlight="highlighter" line-numbers></prism-editor>
			</div>
		</template>

		<template #actions>
			<jet-action-message class="mr-3 mt-3" :on="form.recentlySuccessful">
				Saved.
			</jet-action-message>

			<SecondaryButtonVue class="mt-3" size="md" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="saveSettings">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>&nbsp;save
			</SecondaryButtonVue>
		</template>
	</jet-form-section>
</template>
<script setup>
import JetFormSection from '@/Components/FormSection.vue';
import SecondaryButtonVue from '../../Components/SecondaryButton.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetInputError from '@/Jetstream/InputError.vue';
import JetActionMessage from '@/Jetstream/ActionMessage.vue';
import JetCheckbox from '@/Jetstream/Checkbox.vue';
import CustomInput from '@/Components/Input.vue';

import { PrismEditor } from 'vue-prism-editor';
import 'vue-prism-editor/dist/prismeditor.min.css'; // import the styles somewhere

// import highlighting library (you can use any library you want just return html string)
import { highlight, languages } from 'prismjs/components/prism-core';
import 'prismjs/components/prism-clike';
import 'prismjs/components/prism-javascript';
import 'prismjs/components/prism-css';
import 'prismjs/themes/prism-tomorrow.css'; // import syntax highlighting styles

import { onBeforeMount } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Helpers from '../../Mixins/Helpers';

const props = defineProps(['project']);

const { toast } = Helpers();

const form = useForm({
	name: '',
	config: {}
});

onBeforeMount(() => {
	form.name = props.project.name;
	form.config = props.project.config;
});

const saveSettings = () => {
	form.put(route('projects.saveSettings', props.project), {
		preserveScroll: true,
		onSuccess: () => {
			toast.success("Settings saved")
		}
	})
};

const highlighter = (code) => {
	return highlight(code, languages.js);
};

const highlighter2 = (code) => {
	return highlight(code, languages.css);
};

const updateCode = () => {
	let str = form.config.js;
	let result = '';
	let allstr = form.config.js.split("\n");
	allstr.forEach(element => {
		str = element;
		if (str.length > 90) {
			while (str.length > 0) {
				result += str.substring(0, 90) + '\n';
				str = str.substring(90);
			}
		}else {
			result += str + '\n';
		}
		
	});
	form.config.js = result;
};

</script>