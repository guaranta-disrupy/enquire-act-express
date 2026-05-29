<script setup>
// Import CK Editor Components
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import CKEditor from "@ckeditor/ckeditor5-vue";
// import Font from '@ckeditor/ckeditor5-font/src/font';
import UploadAdapterPlugin from "@/Functions/UploadAdapter";
import { defineAsyncComponent } from "vue";
// import { PrismEditor } from 'vue-prism-editor';
import "vue-prism-editor/dist/prismeditor.min.css"; // import the styles somewhere
// import highlighting library (you can use any library you want just return html string)
import { highlight, languages } from "prismjs/components/prism-core";
import "prismjs/components/prism-clike";
import "prismjs/components/prism-javascript";
import "prismjs/components/prism-markup";
import "prismjs/components/prism-css";
import "prismjs/themes/prism-tomorrow.css"; // import syntax highlighting styles
// Ck Editor end

import CustomSwitch from "@/Components/Switch.vue";
import JetLabel from "@/Jetstream/Label.vue";
import JetCheckbox from "@/Jetstream/Checkbox.vue";
import CustomInput from "@/Components/Input.vue";
import JetButton from "@/Components/Button.vue";
import { inject, ref, watch, provide, onMounted } from "vue";

const PrismEditor = defineAsyncComponent(() =>
    import("vue-prism-editor").then((module) => module.PrismEditor)
);

import Helpers from "@/Mixins/Helpers";

import TestCriteria from "./TestCriteria.vue";
import { onBeforeUpdate } from "vue";
import { onBeforeMount } from "vue";
// import { onMounted } from 'vue';

// import QuillEditor from '@/Extra/Quill.vue'
// import 'quill/dist/quill.snow.css';

// const props = defineProps(['survey']);

const { $testState, assign } = Helpers();

const addSegment = () => {
    // $state.criteria = assign(form);
    return $testState.addSegment();
};

const enableCKEditor = inject("ck");
const ckeditor = CKEditor.component;
const editor = ref(ClassicEditor);

const form = inject("form");

provide("testState", $testState);

const editorConfig = ref({
    extraPlugins: [UploadAdapterPlugin],
});

onBeforeMount(() => {
    $testState.criteria = form;
    // $testState.criteria = {
    //     config: form.config
    // }
    //console.log($state.criteria)
});

// const attributors = ref([
//     // 'class/background',
//     // 'class/color',
//     'style/size',
//     'style/align',
// ]);

// const toolbar = [
//     ['bold', 'italic', 'underline', 'strike'],
//     ['blockquote', 'code-block'],
//     [{ header: 1 }, { header: 2 }],
//     [{ list: 'ordered' }, { list: 'bullet' }],
//     [{ script: 'sub' }, { script: 'super' }],
//     [{ direction: 'rtl' }],
//     [{ header: [1, 2, 3, 4, 5, 6, false] }],
//     [{ color: [] }, { background: [] }],
//     [{ align: [] }],
//     ['image'],
//     ['clean'], // remove formatting button
// ];

const highlighter = (code) => {
    return highlight(code, languages.markup);
};

watch(form, (newVal) => {
    if (newVal.config.random_prime === false) {
        form.config.random_prime_subsets = false;
    }
});

// Handle display_by change event
</script>

<template>
    <div class="grid grid-cols-8 gap-3 overflow-y-auto overflow-x-auto c p-4">
        <div class="col-span-8">
            <CustomSwitch v-model:checked="enableCKEditor">
                Editor
            </CustomSwitch>
        </div>
        <div class="col-span-8" v-if="!form.config.main_header">
            <JetLabel value="Trial Title" />
            <div v-if="enableCKEditor">
                <!-- <QuillEditor :toolbar="toolbar" theme="snow" :attributors="attributors" v-model:content="form.config.title" toolbar="full" contentType="html" /> -->
                <ckeditor
                    :editor="editor"
                    v-model="form.config.title"
                    :config="editorConfig"
                ></ckeditor>
            </div>
            <Suspense v-else>
                <PrismEditor
                    class="my-editor editor-height"
                    v-model="form.config.title"
                    :highlight="highlighter"
                    line-numbers
                />
            </Suspense>
        </div>
        <template v-if="!form.config.title">
            <div class="col-span-8">
                <JetLabel value="Main Header" />
                <div v-if="enableCKEditor">
                    <ckeditor
                        :editor="editor"
                        v-model="form.config.main_header"
                        :config="editorConfig"
                    ></ckeditor>
                </div>
                <Suspense v-else>
                    <prism-editor
                        class="my-editor editor-height"
                        v-model="form.config.main_header"
                        :highlight="highlighter"
                        line-numbers
                    ></prism-editor>
                </Suspense>
            </div>
            <div class="col-span-8"></div>
            <div class="col-span-8">
                <jet-label class="">
                    <div class="flex items-center">
                        <jet-checkbox
                            v-model:checked="form.config.main_trial"
                        />

                        <div class="ml-2">Main trial</div>
                    </div>
                </jet-label>
                <jet-label class="mt-3">
                    <div class="flex items-center">
                        <jet-checkbox v-model:checked="form.config.random" />

                        <div class="ml-2">Randomize targets</div>
                    </div>
                </jet-label>
                <jet-label class="mt-3">
                    <div class="flex items-center">
                        <jet-checkbox
                            v-model:checked="form.config.random_prime"
                        />

                        <div class="ml-2">Randomize primes</div>
                    </div>
                </jet-label>
                <jet-label class="mt-3">
                    <div class="flex items-center">
                        <jet-checkbox
                            v-model:checked="form.config.random_prime_subsets"
                            :disabled="!form.config.random_prime"
                        />
                        <div class="ml-2">Randomize prime subsets</div>
                    </div>
                </jet-label>
                <jet-label class="mt-3">
                    <div class="flex items-center">
                        <jet-checkbox
                            v-model:checked="form.config.prime_as_next"
                        />

                        <div class="ml-2">Prime as next button</div>
                    </div>
                </jet-label>
                <jet-label class="mt-3">
                    <div class="flex items-center">
                        <jet-checkbox
                            v-model:checked="form.config.single_select"
                        />

                        <div class="ml-2">Single select</div>
                    </div>
                </jet-label>
                <jet-label class="mt-3">
                    <div class="flex items-center">
                        <jet-checkbox v-model:checked="form.config.auto_next" />

                        <div class="ml-2">Auto next trial</div>
                    </div>
                </jet-label>
                <div class="mt-6 w-1/2">
                    <CustomInput
                        label="Empty selection text"
                        v-model="form.config.empty_selection"
                    />
                </div>
                <div class="w-1/2">
                    <CustomInput
                        label="Bottom text"
                        v-model="form.config.bottom_text"
                    />
                </div>
                <div class="w-1/3">
                    <CustomInput
                        type="number"
                        label="Selection limit (0 = can select all)"
                        v-model="form.config.limit"
                    />
                </div>
                <div class="w-1/3">
                    <CustomInput
                        type="number"
                        label="Display targets by"
                        v-model="form.config.display_by"
                    />
                </div>
            </div>
        </template>
        <div class="col-span-8 pl-2">
            Skip logic
            <div class="mb-2">
                <JetButton type="button" size="sm" @click.prevent="addSegment">
                    <span v-if="form.config.conditions.length > 0"
                        >Add Segment</span
                    ><span v-else>Add Condition</span>
                </JetButton>
            </div>
            <div
                v-for="(condition, index) in form.config.conditions"
                :key="index"
                class="relative"
            >
                <TestCriteria
                    :deep="0"
                    :condition="condition"
                    :indexes="[index]"
                />
            </div>
            <div class="mt-3">
                <code class="text-black-500 text-xs bg-gray-200">{{
                    $testState.criteriaToString(form.config.conditions)
                }}</code>
            </div>
        </div>
    </div>
</template>

<style scoped="true">
.c {
    max-height: calc(100vh - 214px);
}
</style>
