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

import { inject, ref, onBeforeMount, onMounted } from 'vue';
import CustomSwitch from '@/Components/Switch.vue';
import SecondaryButton from '../../Components/SecondaryButton.vue';
import TargetCard from '../../Components/TargetCard.vue';
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import JetButton from '@/Components/Button.vue';
import CustomInput from '@/Components/Input.vue';
import JetCheckbox from '@/Jetstream/Checkbox.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetInput from '@/Jetstream/Input.vue';
import TextArea from '@/Components/TextArea.vue';

import Helpers from '../../Mixins/Helpers';

import maxBy from 'lodash/maxBy';
import forEach from 'lodash/forEach'

const { sanitize, capitalize, hasImage, assign } = Helpers();


const enableCK = inject('ck');
const ckeditor = CKEditor.component;
const editor = ref(ClassicEditor);
const selectedIndex = ref(false);

const form = inject('form');

const editorConfig = ref({
	extraPlugins: [UploadAdapterPlugin]
});

const modal = ref(false);
const _target = ref({
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

const highlighter = (code) => {
	return highlight(code, languages.markup);
};

const unformatContent = (t, val = null) => {
	if (t == 1) {
		if (hasImage(_target.value.target)) return
		_target.value.equivalent = sanitize(_target.value.target).split(' i ').join(' I ')
	} else {
		return sanitize(val).split(' i ').join(' I ')
	}
};

const resetForm = () => {
    _target.value = {
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
    };
    selectedIndex.value = false;
    modal.value = false;
};

const addTargetDialog = () => {
    resetForm();
    modal.value = true;
};

const addTargetList = () => {
    const t = (_target.value.target || '').trim();
    if (!t) return;
    let target_length = getMaxIndex(form.targets) + 1;
    const target = {
        target: t,
        equivalent: _target.value.equivalent ? _target.value.equivalent : unformatContent(3, t),
        index: target_length,
        can_unselect: _target.value.can_unselect,
        colors: assign(_target.value.colors),
        value: _target.value.value !== 0 ? _target.value.value : target_length
    };
    form.targets.push(target);
    searchDuplicates();
    resetForm();
};

const findDuplicates = arry => arry.filter((item, index) => arry.indexOf(item) !== index);

const detectDuplicates = (arry, key = 'target') => {
	let new_arry = arry.map((item) => sanitize(item[key]));
	return findDuplicates(new_arry);
};

const duplicates = ref([]);

onMounted(() => {
	searchDuplicates();
});

const searchDuplicates = () => {
	duplicates.value = detectDuplicates(form.targets);
};

const getMaxIndex = (values) => {
	return maxBy(values, 'index')?.index ?? 0;
};

const deleteTarget = (index) => {
    form.targets.splice(index, 1);
	searchDuplicates();
};

const updateTargetDialog = (index) => {
    selectedIndex.value = index;
    _target.value = assign(form.targets[index-1]);
    modal.value = true;
	searchDuplicates();
};

const updateTargetList = () => {
    form.targets[selectedIndex.value-1] = _target.value;
	searchDuplicates();
    resetForm();
};

const targetAction = () => {
    if (selectedIndex.value) {
        updateTargetList();
    } else {
        addTargetList();
    }
};
</script>
<template>
    <div class="flex flex-col px-4">
        <div class="ml-2">
            <SecondaryButton class="uppercase" @click="addTargetDialog">
				Add targets
			</SecondaryButton>
        </div>
		<div class="shadow">
			<div class="bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4 m-2 w-full absolute" role="alert" v-if="duplicates.length > 0">
				<p class="font-bold mb-2">Warning! Duplicate targets</p>
				<p v-for="(d, i) in duplicates" :key="'dup-' + i">{{ d }}</p>
			</div>
		</div>
        <div class="mt-2 gap-3 c overflow-y-auto overflow-x-hidden" v-if="form.targets.length">
            <TargetCard v-for="(target, i) in form.targets" :key="'targ' + i" class="m-2 md:col-span-1 col-span-3">
                <template #n>
					{{ i + 1 }}
				</template>
				<template #index>
					{{ target.index }}
				</template>
                <template #value>
					{{ target.value }}
				</template>
                <template #target>
					<span v-html="target.target">
					</span>
				</template>
				<template #equivalent>
					{{ target.equivalent }}
				</template>
                <template #colours>
                    <div class="inline-flex items-center">
                        <span class="text-xs text-gray-50 rounded-md px-1 ml-1" v-if="target.colors.normal" :style="'background-color: ' + target.colors.normal">	
                            N
                        </span>
                        <span class="text-xs text-gray-50 rounded-md px-1 ml-1" v-if="target.colors.hover" :style="'background-color: ' + target.colors.hover">	
                            H
                        </span>
                        <span class="text-xs text-gray-50 rounded-md px-1 ml-1" v-if="target.colors.active" :style="'background-color: ' + target.colors.active">	
                            A
                        </span>
                    </div>
                </template>

                <template #actions>
					<a href="javascript:;" class="text-sm text-blue-400 mr-2 inline-flex items-center" @click="deleteTarget(i)">
						<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
							<path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
						</svg> Delete
					</a>
					<a href="javascript:;" class="text-sm text-blue-400 inline-flex items-center" @click="updateTargetDialog(i+1)">
						<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
							<path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
							<path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
						</svg> Edit
					</a>
				</template>
            </TargetCard>
        </div>
        <div v-else class="flex items-center h-[65vh] mx-auto text-gray-600 prose-2xl font-medium">
			No record found
		</div>
    </div>
    <JetDialogModal :show="modal" @close="modal = false" maxWidth="2xl">
		<template #title>
			<span v-if="selectedIndex">
				Update Target
			</span>
			<span v-else>
				Add Target
			</span>
		</template>

		<template #content>
			<div class="mb-2 w-full">
				<CustomSwitch v-model:checked="enableCK">
					Editor
				</CustomSwitch>
			</div>
			<div v-if="enableCK">
				<!-- <ckeditor :editor="editor" v-model="_target.target" @input="unformatContent(1)" :config="editorConfig"></ckeditor> -->
				<ckeditor :editor="editor" v-model="_target.target" :config="editorConfig"></ckeditor>
			</div>
			<PrismEditor v-else class="my-editor editor-height" v-model="_target.target" :highlight="highlighter" line-numbers />
            <div class="grid grid-cols-3 gap-1">
				<div class="col-span-2">
					<!-- <CustomInput class="w-full" v-model="_target.equivalent" label="Equivalent" /> -->
					<TextArea label="Equivalent" v-model="_target.equivalent"></TextArea>
				</div>
				<div>
                    <CustomInput class="w-full" type="number" v-model="_target.value" label="Value" />
				</div>
			</div>
			<jet-label class="mt-4">
				<div class="flex items-center">
                    <jet-checkbox v-model:checked="_target.can_unselect" />
                    <div class="ml-2">
                        Can unselect other targets
                    </div>
                </div>
			</jet-label>
            <div class="grid grid-cols-3 gap-1 mt-4">
				<div>
					<jet-label class="mt-4" value="Colors: Normal"></jet-label>
					<jet-input type="color" class="w-full" v-model="_target.colors.normal"></jet-input>
				</div>
				<div>
					<jet-label class="mt-4" value="Colors: Hover"></jet-label>
					<jet-input type="color" class="w-full" v-model="_target.colors.hover"></jet-input>
				</div>
				<div>
					<jet-label class="mt-4" value="Colors: Active"></jet-label>
					<jet-input type="color" class="w-full" v-model="_target.colors.active"></jet-input>
				</div>
			</div>
		</template>

		<template #footer>
			<SecondaryButton @click="resetForm">
                nevermind
            </SecondaryButton>
            <JetButton class="ml-2" @click="targetAction">
            	<span v-if="selectedIndex">
					Update Target
				</span>
				<span v-else>
					Add Target
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