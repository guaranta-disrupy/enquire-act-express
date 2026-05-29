<template>
    <div class="col-span-6">
        <div class="flex items-stretch">
            <div 
                class="flex-1 w-1/2 p-3"
                :class="{ 'fullscreen-editor': isCssFullscreen }"
            >
                <div
                    class="relative p-2 border border-gray-300 border-solid rounded-xl h-full"
                    :class="{ 'fullscreen-container': isCssFullscreen }"
                >
               
                    <div
                        class="grp-header bg-white p-2 text-xs text-gray-500 flex flex-row gap-2 items-center"
                    >
                        CUSTOM CSS

                        <div class="relative group">
                            <Tooltip text="Add custom styles to modify the look and feel of your project." />
                            <div
                            class="absolute bottom-full left--100 mb-1 px-1.5 py-2 rounded text-xs text-white bg-[#15919a] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                            role="tooltip"
                            style="right: -90px;"
                            >
                            Add custom styles to modify the <br/>look and feel of your project.
                        </div>
                        </div>

                        <button
                            @click="toggleCssFullscreen"
                            class="ml-auto p-1 hover:bg-gray-100 rounded transition-colors"
                            type="button"
                            :title="isCssFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen'"
                        >
                            <svg v-if="!isCssFullscreen" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                    </div>

             


                    <div class="p-2 mt-3 h-[50vh]" :class="{ 'fullscreen-editor-content': isCssFullscreen }">
                        <prism-editor
                            class="my-editor"
                            :class="{ 'fullscreen-prism-editor': isCssFullscreen }"
                            v-model="form.config.css"
                            :highlight="highlighter2"
                            line-numbers
                        ></prism-editor>
                    </div>
                </div>
            </div>
            <div 
                class="flex-1 w-1/2 p-3"
                :class="{ 'fullscreen-editor': isJsFullscreen }"
            >
                <div
                    class="relative p-2 border border-gray-300 border-solid rounded-xl h-full"
                    :class="{ 'fullscreen-container': isJsFullscreen }"
                >
                    <div
                        class="grp-header bg-white p-2 text-xs text-gray-500 flex flex-row gap-2 items-center"
                    >
                        CUSTOM JS 

                        <div class="relative group">
                            <Tooltip text="Add advanced logic or interactivity using custom JavaScript." />
                            <div
                                class="absolute bottom-full left--100 mb-1 px-1.5 py-2 rounded text-xs text-white bg-[#15919a] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                role="tooltip"
                                style="right: -90px;"
                                >
                                Add advanced logic or interactivity <br/>using custom JavaScript.
                            </div>
                        </div>

                        <button
                            @click="toggleJsFullscreen"
                            class="ml-auto p-1 hover:bg-gray-100 rounded transition-colors"
                            type="button"
                            :title="isJsFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen'"
                        >
                            <svg v-if="!isJsFullscreen" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                    </div>



                    <div class="p-2 mt-3 h-[50vh]" :class="{ 'fullscreen-editor-content': isJsFullscreen }">
                        <prism-editor
                            class="my-editor"
                            :class="{ 'fullscreen-prism-editor': isJsFullscreen }"
                            v-model="form.config.js"
                            :highlight="highlighter"
                            line-numbers
                        ></prism-editor>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { PrismEditor } from "vue-prism-editor";
import "vue-prism-editor/dist/prismeditor.min.css"; // import the styles somewhere

// import highlighting library (you can use any library you want just return html string)
import { highlight, languages } from "prismjs/components/prism-core";
import "prismjs/components/prism-clike";
import "prismjs/components/prism-javascript";
import "prismjs/components/prism-css";
import "prismjs/components/prism-markup";
import "prismjs/themes/prism-tomorrow.css";
import Tooltip from "./Tooltip.vue"; // import syntax highlighting styles

import { inject, ref } from "vue";

const form = inject("form");

const isCssFullscreen = ref(false);
const isJsFullscreen = ref(false);

const toggleCssFullscreen = () => {
    isCssFullscreen.value = !isCssFullscreen.value;
    // Exit JS fullscreen if CSS goes fullscreen
    if (isCssFullscreen.value) {
        isJsFullscreen.value = false;
    }
};

const toggleJsFullscreen = () => {
    isJsFullscreen.value = !isJsFullscreen.value;
    // Exit CSS fullscreen if JS goes fullscreen
    if (isJsFullscreen.value) {
        isCssFullscreen.value = false;
    }
};

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

.fullscreen-editor {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100% !important;
    height: 100% !important;
    z-index: 9999 !important;
    padding: 1rem !important;
    background: rgba(255, 255, 255, 0.98);
}

.fullscreen-container {
    height: 100% !important;
}

.fullscreen-editor-content {
    height: calc(100vh - 100px) !important;
}

.my-editor {
    height: 100% !important;
}

.my-editor.fullscreen-prism-editor {
    height: 63vh !important;
}

.my-editor :deep(.prism-editor-wrapper) {
    height: 100% !important;
}

.my-editor.fullscreen-prism-editor :deep(.prism-editor-wrapper) {
    height: 63vh !important;
}
</style>
