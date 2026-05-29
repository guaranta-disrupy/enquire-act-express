<script setup>
// Import CK Editor Components
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import CKEditor from '@ckeditor/ckeditor5-vue';
import UploadAdapterPlugin from '@/Functions/UploadAdapter';
import { PrismEditor } from 'vue-prism-editor';
import 'vue-prism-editor/dist/prismeditor.min.css'; // import the styles somewhere
// import highlighting library (you can use any library you want just return html string)
import { highlight, languages } from 'prismjs/components/prism-core';
import 'prismjs/components/prism-clike';
import 'prismjs/components/prism-javascript';
import 'prismjs/components/prism-markup';
import 'prismjs/components/prism-css';
import 'prismjs/themes/prism-tomorrow.css'; // import syntax highlighting styles
// Ck Editor end

import { inject, ref, provide, onBeforeMount } from 'vue';
import CustomSwitch from '@/Components/Switch.vue';
import SecondaryButton from '../../Components/SecondaryButton.vue';
import PrimeCard from '../../Components/PrimeCard.vue';
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import JetButton from '@/Components/Button.vue';
import CriteriaContents from './Criteria.vue';
import CustomInput from '@/Components/Input.vue';
import TextArea from '@/Components/TextArea.vue';

import Helpers from '../../Mixins/Helpers';

import maxBy from 'lodash/maxBy';
import forEach from 'lodash/forEach'

const { sanitize, capitalize, hasImage, $state, assign } = Helpers();

provide('state', $state);

const enableCK = inject('ck');
const ckeditor = CKEditor.component;
const editor = ref(ClassicEditor);
const selectedIndex = ref(false);

const form = inject('form');

const editorConfig = ref({
	extraPlugins: [UploadAdapterPlugin]
});
const modals = ref({
	createPrime: false
});
const _prime = ref({
	prime: '',
	equivalent: '',
	config: {
		conditions: []
	}
});

onBeforeMount(() => {
	$state.criteria = _prime.value;
});

const highlighter = (code) => {
	return highlight(code, languages.markup);
};

const unformatContent = (t, val = null) => {
	if (t == 1) {
		if (hasImage(_prime.value.prime)) return
		_prime.value.equivalent = sanitize(_prime.value.prime).split(' i ').join(' I ')
	} else {
		return sanitize(val).split(' i ').join(' I ')
	}
};

const addSegment = () => {
	return $state.addSegment();
};

const addPrimeList = () => {
    const p = (_prime.value.prime || '').trim();
    if (!p) return;
    let prime_length = getMaxIndex(form.primes) + 1;
    const prime = {
        prime: p,
        equivalent: _prime.value.equivalent ? _prime.value.equivalent : unformatContent(3, p),
        index: prime_length,
        config: assign(_prime.value.config)
    };
    form.primes.push(prime);
    resetPrimeForm();
};

const resetPrimeForm = () => {
	_prime.value = {
		prime: '',
		equivalent: '',
		config: {
			conditions: []
		}
	};
	selectedIndex.value = false;
	modals.value.createPrime = false;
};

const addPrimeDialog = () => {
	resetPrimeForm();
	$state.criteria = _prime.value;
	modals.value.createPrime = true;
};

const getMaxIndex = (values) => {
	return maxBy(values, 'index')?.index ?? 0;
};

const deletePrime = (index) => {
	form.primes.splice(index, 1)
};

const updatePrimeDialog = (index) => {
	selectedIndex.value = index;
	_prime.value = assign(form.primes[index-1]);
	$state.criteria = _prime.value;
	modals.value.createPrime = true;
};

const updatePrimeList = () => {
	form.primes[selectedIndex.value-1] = _prime.value;
	resetPrimeForm();
};

const primeAction = () => {
	if (selectedIndex.value) {
		// update
		updatePrimeList();
	} else {
		addPrimeList();
	}
};
</script>
<template>
	<div class="flex flex-col px-4">
		<div class="ml-2">
			<SecondaryButton class="uppercase" @click="addPrimeDialog">
				Add primes
			</SecondaryButton>
		</div>
		<div class="mt-2 grid gap-3 c overflow-y-auto overflow-x-hidden" v-if="form.primes.length">
			<PrimeCard v-for="(prime, i) in form.primes" :key="i" class="m-2 md:col-span-1 col-span-3">
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
						{{ $state.criteriaToString(prime.config.conditions) }}
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
					<a href="javascript:;" class="text-sm text-blue-400 inline-flex items-center" @click="updatePrimeDialog(i+1)">
						<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
							<path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
							<path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
						</svg> Edit
					</a>
				</template>
			</PrimeCard>
		</div>
		<div v-else class="flex items-center h-[65vh] mx-auto text-gray-600 prose-2xl font-medium">
			No record found
		</div>
	</div>
	<JetDialogModal :show="modals.createPrime" @close="modals.createPrime = false" maxWidth="5xl">
		<template #title>
			<span v-if="selectedIndex">
				Update Prime
			</span>
			<span v-else>
				Add Prime
			</span>
		</template>

		<template #content>
			<div class="mb-2 w-full">
				<CustomSwitch v-model:checked="enableCK">
					Editor
				</CustomSwitch>
			</div>
			<div v-if="enableCK">
				<!-- <ckeditor :editor="editor" v-model="_prime.prime" @input="unformatContent(1)" :config="editorConfig"></ckeditor> -->
				<ckeditor :editor="editor" v-model="_prime.prime" :config="editorConfig"></ckeditor>
			</div>
			<PrismEditor v-else class="my-editor editor-height" v-model="_prime.prime" :highlight="highlighter" line-numbers />
			

			<!-- <CustomInput class="w-full" v-model="_prime.equivalent" label="Equivalent" /> -->
			<TextArea v-model="_prime.equivalent" label="Equivalent"></TextArea>
			<div class="flex items-stretch">
				<div class="flex-1 w-full pt-4">
					<div class="relative p-2 border border-gray-300 border-solid rounded h-full">
						<div class="grp-header bg-white p-2 uppercase text-xs text-gray-500">
							skip logic
						</div>
						<div class="p-2 mt-3">
							<div class="mb-2">
								<JetButton type="button" size="sm" @click="addSegment">
									<span v-if="_prime.config.conditions.length > 0">Add Segment</span><span v-else>Add Condition</span>
								</JetButton>
							</div>
							<div v-for="(condition, index) in _prime.config.conditions" :key="index">
								<CriteriaContents :deep="0" :condition="condition" :indexes="[index]"/>
							</div>
							<div class=" mt-3">
								<code class="text-black-500 text-xs bg-gray-200">{{ $state.criteriaToString(_prime.config.conditions) }}</code>
							</div>
						</div>
					</div>
				</div>
			</div>
		</template>

		<template #footer>
			<SecondaryButton @click="resetPrimeForm">
                nevermind
            </SecondaryButton>
            <JetButton class="ml-2" @click="primeAction">
            	<span v-if="selectedIndex">
					Update Prime
				</span>
				<span v-else>
					Add Prime
				</span>
            </JetButton>
		</template>
	</JetDialogModal>
</template>
<style scoped="true">
    .c {
        max-height: calc(100vh - 260px);
    }
</style>