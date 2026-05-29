<template>
    <div class="w-full">
        <div class="flex items-stretch">
            <div class="flex-1 w-1/4 p-3">
                <div class="relative p-2 border border-gray-300 border-solid rounded-xl h-full">
                    <div class="grp-header bg-white p-2 uppercase text-xs text-gray-500">
                        response forwarding
                    </div>
                    <div class="p-2 mt-3">
                        <jet-label>
                            <div class="flex items-center">
                                <jet-checkbox v-model:checked="form.config.forward_response" />
                                <div class="ml-2">
                                    Forward responses
                                </div>
                            </div>
                        </jet-label>
                        <jet-label value="Response variable" class="mt-2" />
                        <jet-input type="text" class="mt-1 block w-full" v-model="form.config.response_var" :disabled="!form.config.forward_response" />
                        <jet-label value="Data type" class="mt-2" />
                        <select class="form-select w-3/4 xs:w-full mt-2" v-model="form.config.response_type">
                            <option value="selection">Selection</option>
                            <option value="rt">RT</option>
                            <option value="rank">Rank</option>
                            <option value="refined">Refined</option>
                            <option value="coded">Coded</option>
                        </select>
                    </div>
                    <div class="p-2 mt-3">
                        <JetLabel value="Override theme" />
                        <select class="form-select w-3/4 xs:w-full mt-2" v-model="form.config.theme">
                            <option value="0">Inherit</option>
                            <option :value="1">Default</option>
                            <option :value="2">Colorful (Single select)</option>
                        </select>
                    </div>
                    <div class="p-2 mt-3">
                        <JetLabel value="Override redirect URL" />
                        <JetInput type="text" class="mt-1 block w-full" v-model="form.config.redirect" placeholder="Override project redirect" />
                    </div>
                </div>
            </div>
            <div class="flex-1 w-1/4  p-3">
                <div class="relative p-2 border border-gray-300 border-solid rounded-xl h-full">
                    <div class="grp-header bg-white p-2 uppercase text-xs text-gray-500">
                        click settings (2 targets)
                    </div>
                    <div class="p-2">
                        <div class="py-1">
                            <div class="mt-2">
                                <jet-label>
                                    <div class="flex items-center">
                                        <jet-checkbox v-model:checked="form.config.keypress_click" />
                                        <div class="ml-2">
                                            Keypress/Click gesture
                                        </div>
                                    </div>
                                </jet-label>
                            </div>
                            <div class="col-span-3 mt-2">
                                <jet-label value="Click instructions"></jet-label>
                                <prism-editor class="my-editor h-52" v-model="form.config.click_instructions" :highlight="highlighter3" line-numbers></prism-editor>
                            </div>
                        </div>
                        <div class="py-1">
                            <div class="col-span-3 mt-2">
                                <jet-label value="Keypress instructions"></jet-label>
                                <prism-editor class="my-editor h-52" v-model="form.config.keypress_instructions" :highlight="highlighter3" line-numbers></prism-editor>
                            </div>
                        </div>
                        <div class="py-1">
                            <jet-label value="Right key"></jet-label>
                            <jet-input type="text" class="mt-1 block w-full" v-model="form.config.right_key" />
                        </div>
                        <div class="py-1">
                            <jet-label value="Left key"></jet-label>
                            <jet-input type="text" class="mt-1 block w-full" v-model="form.config.left_key" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex-1 w-1/4  p-3">
                <div class="relative p-2 border border-gray-300 border-solid rounded-xl h-full">
                    <div class="grp-header bg-white p-2 uppercase text-xs text-gray-500">
                        swipe settings (2 targets)
                    </div>
                    <div class="p-2 mt-3">
                        <jet-label>
                            <div class="flex items-center">
                                <jet-checkbox v-model:checked="form.config.swipe" />
                                <div class="ml-2">
                                    Swipe gesture on touch screen devices
                                </div>
                            </div>
                        </jet-label>
                        <div class="col-span-3 mt-2">
                            <jet-label value="Swipe instructions"></jet-label>
                            <prism-editor class="my-editor h-52" v-model="form.config.swipe_instructions" :highlight="highlighter3" line-numbers></prism-editor>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-span-6">
        <div class="flex items-stretch">
            <div class="flex-1 w-1/2 p-3">
                <div class="relative p-2 border border-gray-300 border-solid rounded-xl h-full">
                    <div class="grp-header bg-white p-2 uppercase text-xs text-gray-500">
                        Custom CSS
                    </div>
                    <div class="p-2 mt-3">
                        <prism-editor class="my-editor h-52" v-model="form.config.css" :highlight="highlighter2" line-numbers></prism-editor>
                    </div>
                </div>
            </div>
            <div class="flex-1 w-1/2 p-3">
                <div class="relative p-2 border border-gray-300 border-solid rounded-xl h-full">
                    <div class="grp-header bg-white p-2 uppercase text-xs text-gray-500">
                        Custom JS
                    </div>
                    <div class="p-2 mt-3">
                        <prism-editor class="my-editor h-52" v-model="form.config.js" :highlight="highlighter" line-numbers></prism-editor>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import JetCheckbox from '@/Jetstream/Checkbox.vue'
import JetLabel from '@/Jetstream/Label.vue'
import JetInput from '@/Jetstream/Input.vue'

import { PrismEditor } from 'vue-prism-editor';
import 'vue-prism-editor/dist/prismeditor.min.css'; // import the styles somewhere

// import highlighting library (you can use any library you want just return html string)
import { highlight, languages } from 'prismjs/components/prism-core';
import 'prismjs/components/prism-clike';
import 'prismjs/components/prism-javascript';
import 'prismjs/components/prism-css';
import 'prismjs/components/prism-markup';
import 'prismjs/themes/prism-tomorrow.css'; // import syntax highlighting styles

import { inject } from 'vue';

const form = inject('form');

const highlighter = (code) => {
    return highlight(code, languages.js);
};

const highlighter2 = (code) => {
    return highlight(code, languages.css);
};

const highlighter3 = (code) => {
    return highlight(code, languages.markup);
};

</script>

<style>
.grp-header {
    position: absolute;
    top: 0px;
    left: 16px;
    transform: translateY(-50%);
}
</style>