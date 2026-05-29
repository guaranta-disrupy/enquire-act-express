<script setup>
import { ref, provide, watch, onMounted, onUnmounted, computed } from 'vue';
import { useForm, usePage } from "@inertiajs/vue3";
import prism_editor from "../../prism_editor.js";
import helpers from "../../helpers.js";
import S4Loading from "../../S4/Loading.vue";
import { useQuestionnaireStore } from "../../stores/questionnaireStore.js";
import WarningModal from "../../S4/Warning.vue";

const { toast } = helpers();
const { highlighter, PrismEditor } = prism_editor();
const { updateFromForm, initializeFromPageData } = useQuestionnaireStore();
const tabs = ref([
    { name: 'Custom JS', key: 'js' },
    { name: 'Custom CSS', key: 'css' }
]);
const form = useForm({
    settings: {
        name: usePage().props.questionnaire.name,
        setup: usePage().props.questionnaire.setup,
    },
});

provide("form", form);
const js_pos = ref('default')
const css_pos = ref('default')

// Track initial values to detect unsaved changes
// Get from page props to ensure we have the latest saved state
const pageProps = usePage().props;
const initialCustomCSS = ref(pageProps.questionnaire?.setup?.customCSS || form.settings.setup.customCSS || '');
const initialCustomJS = ref(pageProps.questionnaire?.setup?.customJS || form.settings.setup.customJS || '');

// Track pending update
const pendingUpdate = ref(null);
const showSyncConfirmModal = ref(false);

// Check if there are unsaved changes
const hasUnsavedChanges = computed(() => {
    const currentCSS = form.settings.setup.customCSS || '';
    const currentJS = form.settings.setup.customJS || '';
    return currentCSS !== initialCustomCSS.value || currentJS !== initialCustomJS.value;
});

// Watch for changes in page props and update form data
watch(() => usePage().props.questionnaire, (newQuestionnaire) => {
    if (newQuestionnaire) {
        form.settings.name = newQuestionnaire.name;
        // Only update setup if it's different to avoid overriding user changes
        // But only if there are no unsaved changes (to avoid conflicts)
        if (!hasUnsavedChanges.value && JSON.stringify(form.settings.setup) !== JSON.stringify(newQuestionnaire.setup)) {
            form.settings.setup = { ...newQuestionnaire.setup };
            // Update initial values to reflect the new state
            initialCustomCSS.value = form.settings.setup.customCSS || '';
            initialCustomJS.value = form.settings.setup.customJS || '';
        } else if (hasUnsavedChanges.value) { }
    }
}, { deep: true });

// Initialize shared store with page data
initializeFromPageData(usePage().props);

// Event handler for questionnaire data updates
let handleQuestionnaireUpdate = null;

// Function to actually apply the update
const applyUpdate = (updatedQuestionnaire) => {
    if (updatedQuestionnaire) {
        // Update form with new questionnaire data
        form.settings.name = updatedQuestionnaire.name;
        
        // Only update setup if it's different to avoid overriding user changes
        if (JSON.stringify(form.settings.setup) !== JSON.stringify(updatedQuestionnaire.setup)) {
            form.settings.setup = { ...updatedQuestionnaire.setup };
            
            // Update initial values to reflect the new saved state
            initialCustomCSS.value = form.settings.setup.customCSS || '';
            initialCustomJS.value = form.settings.setup.customJS || '';
            
            // Refresh store
            initializeFromPageData(usePage().props);
        }
    }
};

onMounted(() => {
    // Listen for questionnaire data updates from remote changes
    handleQuestionnaireUpdate = (event) => {
        const updatedQuestionnaire = event.detail?.questionnaire || usePage().props.questionnaire;
        
        if (updatedQuestionnaire) {
            // Check if user has unsaved changes
            if (hasUnsavedChanges.value) {
                // Store the pending update and show confirmation modal
                pendingUpdate.value = updatedQuestionnaire;
                showSyncConfirmModal.value = true;
            } else {
                // No unsaved changes, apply update immediately
                applyUpdate(updatedQuestionnaire);
            }
        }
    };
    
    window.addEventListener('questionnaire-data-updated', handleQuestionnaireUpdate);
});

onUnmounted(() => {
    if (handleQuestionnaireUpdate) {
        window.removeEventListener('questionnaire-data-updated', handleQuestionnaireUpdate);
    }
});

// Watch for changes in form data and sync with shared store
watch(() => form.settings.setup.customCSS, (newValue) => {
    if (newValue !== undefined) {
        updateFromForm(form);
    }
}, { immediate: true });

watch(() => form.settings.setup.customJS, (newValue) => {
    if (newValue !== undefined) {
        updateFromForm(form);
    }
}, { immediate: true });

function setPosJs(pos) {
    if (pos == 'default') {
        if (css_pos.value == 'side-right') css_pos.value = 'side-left'
        js_pos.value = pos
    } else if (pos == 'side') {
        if (css_pos.value == 'default') js_pos.value = 'side-left'
        else {
            js_pos.value = 'side-right'
        }
    } else {
        js_pos.value = pos
        css_pos.value = 'default'
    }
}

function setPosCss(pos) {
    if (pos == 'default') {
        if (js_pos.value == 'side-right') js_pos.value = 'side-left'
        css_pos.value = pos
    } else if (pos == 'side') {
        if (js_pos.value == 'default') css_pos.value = 'side-left'
        else {
            css_pos.value = 'side-right'
        }
    } else {
        css_pos.value = pos
        js_pos.value = 'default'
    }
}

const currentTab = ref('JS');
const openIndex = ref(null);

const on_Enter = (el) => {
    el.style.height = '0px'
    el.style.transition = 'height 0.3s ease'

    requestAnimationFrame(() => {
        const height = el.scrollHeight + 'px'
        el.style.height = height

        el.addEventListener('transitionend', () => {
            el.style.height = 'auto'
        }, { once: true })
    })
}

const on_Leave = (el) => {
    const height = el.scrollHeight + 'px'
    el.style.height = height
    el.style.transition = 'height 0.3s ease'

    requestAnimationFrame(() => {
        el.style.height = '0px'
    })
}

const onAfterLeave = (el) => {
    el.style.height = ''
}

const toggleTab = (selectedTab) => {
    currentTab.value = selectedTab.toUpperCase();
    openIndex.value = openIndex.value === selectedTab ? null : selectedTab;
}
const loading = ref(false);
const saveSettings = () => {
    loading.value = true;
    let params = [usePage().props.questionnaire.code];
    
    // Only send CSS and JS data, don't affect other settings
    const advancedForm = useForm({
        customCSS: form.settings.setup.customCSS || '',
        customJS: form.settings.setup.customJS || ''
    });
    
    advancedForm.post(route("questionnaireAdvanced.update", params), {
        onSuccess: () => {
            // Update initial values after successful save
            initialCustomCSS.value = form.settings.setup.customCSS || '';
            initialCustomJS.value = form.settings.setup.customJS || '';
            
            toast.success(`Advanced settings (CSS/JS) successfully updated`, {
                timeout: 2000,
            });
            loading.value = false;
        },
        onError: (errors) => {
            console.error('Advanced.vue save error:', errors);
            toast.error(`Failed to update advanced settings: ${Object.values(errors).flat().join(', ')}`, {
                timeout: 5000,
            });
            loading.value = false;
        },
    });
};

// Handle sync confirmation
const confirmSync = () => {
    if (pendingUpdate.value) {
        applyUpdate(pendingUpdate.value);
        pendingUpdate.value = null;
    }
    showSyncConfirmModal.value = false;
};

// Handle sync cancellation
const cancelSync = () => {
    pendingUpdate.value = null;
    showSyncConfirmModal.value = false;
};
</script>

<template>
    <div class="fixed bottom-20 right-6 z-50">
            <div class="text-slate-800 font-medium text-sm">
                <div class="flex gap-3 my-2 w-full">
                    <button @click="saveSettings" type='button'
                        class='mt-0.5 flex break-inside bg-[var(--mcolor)] w-32 rounded-lg px-4 py-2 hover:bg-[var(--mcolor2)]'>
                        <div class='flex items-center justify-center flex-1 text-[var(--txtcolor)]'>
                            <span class='text-sm font-semibold text-white'>Save</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    <s4-loading v-show="loading" :label="'Saving'" :theme="'theme1'"
            :class="[
                'h-screen z-50 overflow-hidden bg-gray-700/50',
                {
                    '!w-[100vw] left-[100px]': js_pos === 'full' || css_pos === 'full',
                    '!w-[55.2vw]': js_pos.startsWith('side-') || css_pos.startsWith('side-'),
                    'left-[100px]': js_pos === 'side-left' || css_pos === 'side-left',
                    'right-0': js_pos === 'side-right' || css_pos === 'side-right',
                    'w-full': js_pos === 'default' && css_pos === 'default'
                }
            ]"
            :color="'text-white'">
</s4-loading>
    <div class="w-full h-full flex flex-col shadow-lg">
        <div
            class="flex justify-left pt-10 pb-5 pl-5 w-full bg-[var(--mcolor)] text-center text-[20px] text-white font-bold">
            <h1>Advanced</h1>
        </div>

        <!-- Loop through tabs -->
        <div v-for="tab in tabs" :key="tab.key" class="mt-2 mx-5">
            <div @click="toggleTab(tab.key)"
                class="relative flex flex-row font-bold justify-between rounded-lg cursor-pointer p-4 text-lg text-[#454545] hover:bg-[var(--mcolor2)] transition-all duration-500 ease-in-out"
                :class="openIndex === tab.key ? 'bg-[var(--mcolor)] !text-white rounded-b-none hover:bg-[var(--mcolor2)]' : 'bg-[#E7E7E7]'">
                <span>{{ tab.name }}</span>
                <svg :class="{ 'rotate-180 ': openIndex === tab.key }" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke-width="1.5" :stroke="openIndex === tab.key ? 'white' : '#323232'"
                    class="transition-transform duration-[.5s] w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </div>

            <!-- Tab content -->
            <Transition @enter="on_Enter" @leave="on_Leave" @after-leave="onAfterLeave">
                <div v-show="openIndex === tab.key" class="submenu text-sm h-auto overflow-hidden shadow-lg rounded-lg]"
                    ref="submenu">
                    <div class="flex flex-col p-[12px] rounded-b-lg">
                        <div v-if="tab.name === 'Custom JS'"
                            class="rounded-t-lg flex h-full justify-center font-medium text-sm p-2">
                            <div v-if="js_pos == 'default'" class="relative w-full h-[30vh] mb-7">
                                <div class="flex justify-between">
                                    <div
                                        class="text-sm font-semibold text-slate-700 bg-amber-200 w-20 justify-center grid py-1 rounded-t-lg">
                                        JS</div>
                                    <div class="flex self-center text-xs mt-1 text-slate-800 gap-2 cursor-pointer mr-2">
                                        <div @click="setPosJs('side')"
                                            class="bg-slate-200 px-4 py-1 rounded-t-lg hover:bg-slate-300">
                                            Side
                                            Mount
                                        </div>
                                        <div @click="setPosJs('full')"
                                            class="bg-slate-200 px-4 py-1 rounded-t-lg hover:bg-slate-300">
                                            Full
                                            Screen
                                        </div>
                                    </div>
                                </div>
                                <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                    v-model="form.settings.setup.customJS">
                                </prism-editor>
                            </div>

                            <div v-if="js_pos == 'full'"
                                class="fixed top-0 left-[100px] w-[95vw] h-[100vh] p-10 z-50 backdrop-blur-md">
                                <div class="flex justify-between">
                                    <div
                                        class="text-sm font-semibold text-slate-700 bg-amber-200 w-20 justify-center grid py-1 rounded-t-lg">
                                        JS</div>
                                    <div class="flex self-center text-sm text-slate-800 gap-2 cursor-pointer mr-2">
                                        <div @click="setPosJs('side')"
                                            class="bg-slate-400 text-white px-4 py-1 rounded-t-lg hover:bg-slate-500">
                                            Side Mount
                                        </div>
                                        <div @click="setPosJs('default')"
                                            class="bg-slate-600 text-white px-4 py-1 rounded-t-lg hover:bg-slate-700">
                                            Close</div>
                                        <div @click="saveSettings"
                                            class="bg-[var(--mcolor)] w-24 grid justify-center text-white px-4 py-1 rounded-t-lg hover:bg-[var(--mcolor2)]">
                                            Save</div>
                                    </div>
                                </div>
                                <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                    v-model="form.settings.setup.customJS">
                                </prism-editor>
                            </div>

                            <div v-if="js_pos == 'side-left'"
                                class="fixed top-0 left-[100px] w-[50vw] h-[100vh] p-10 z-50 backdrop-blur-md">
                                <div class="flex justify-between">
                                    <div
                                        class="text-sm font-semibold text-slate-700 bg-amber-200 w-20 justify-center grid py-1 rounded-t-lg">
                                        JS</div>
                                    <div class="flex self-center text-sm text-slate-800 gap-2 mr-2 cursor-pointer">
                                        <div @click="setPosJs('full')"
                                            class="bg-slate-400 text-white px-4 py-1 rounded-t-lg hover:bg-slate-500">
                                            Full Screen
                                        </div>
                                        <div @click="setPosJs('default')"
                                            class="bg-slate-600 text-white px-4 py-1 rounded-t-lg hover:bg-slate-700">
                                            Close</div>
                                        <div @click="saveSettings"
                                            class="bg-[var(--mcolor)] w-24 grid justify-center text-white px-4 py-1 rounded-t-lg hover:bg-[var(--mcolor2)]">
                                            Save</div>
                                    </div>
                                </div>
                                <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                    v-model="form.settings.setup.customJS">
                                </prism-editor>
                            </div>

                            <div v-if="js_pos == 'side-right'"
                                class="fixed top-0 right-0 w-[50vw] h-[100vh] p-10 z-50 backdrop-blur-md">
                                <div class="flex justify-between">
                                    <div
                                        class="text-sm font-semibold text-slate-700 bg-amber-200 w-20 justify-center grid py-1 rounded-t-lg">
                                        JS</div>
                                    <div class="flex self-center text-sm text-slate-800 gap-2 mr-2 cursor-pointer">
                                        <div @click="setPosJs('full')"
                                            class="bg-slate-400 text-white px-4 py-1 rounded-t-lg hover:bg-slate-500">
                                            Full Screen
                                        </div>
                                        <div @click="setPosJs('default')"
                                            class="bg-slate-600 text-white px-4 py-1 rounded-t-lg hover:bg-slate-700">
                                            Close</div>
                                        <div @click="saveSettings"
                                            class="bg-[var(--mcolor)] w-24 grid justify-center text-white px-4 py-1 rounded-t-lg hover:bg-[var(--mcolor2)]">
                                            Save</div>
                                    </div>
                                </div>
                                <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                    v-model="form.settings.setup.customJS">
                                </prism-editor>
                            </div>

                        </div>

                        <!-- CSS TAB -->
                        <div v-if="tab.name === 'Custom CSS'"
                            class="rounded-t-lg flex justify-center text-[var(--black)] font-medium text-sm p-2 pb-9">
                            <div v-if="css_pos == 'default'" class="relative w-full h-[30vh]">
                                <div class="flex justify-between">
                                    <div
                                        class="text-sm font-semibold text-slate-700 bg-sky-200 w-20 justify-center grid py-1 rounded-t-lg">
                                        CSS</div>
                                    <div class="flex self-center text-xs mt-1 text-slate-800 gap-2 cursor-pointer mr-2">
                                        <div @click="setPosCss('side')"
                                            class="bg-slate-200 px-4 py-1 rounded-t-lg hover:bg-slate-300">
                                            Side
                                            Mount
                                        </div>
                                        <div @click="setPosCss('full')"
                                            class="bg-slate-200 px-4 py-1 rounded-t-lg hover:bg-slate-300">
                                            Full
                                            Screen
                                        </div>
                                    </div>
                                </div>
                                <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                    v-model="form.settings.setup.customCSS">
                                </prism-editor>
                            </div>

                            <div v-if="css_pos == 'full'"
                                class="fixed top-0 left-[100px] w-[95vw] h-[100vh] p-10 pb-24 z-50 backdrop-blur-md">
                                <div class="flex justify-between">
                                    <div
                                        class="text-sm font-semibold text-slate-700 bg-sky-200 w-20 justify-center grid py-1 rounded-t-lg">
                                        CSS</div>
                                    <div class="flex self-center text-sm text-slate-800 gap-2 cursor-pointer mr-2">
                                        <div @click="setPosCss('side')"
                                            class="bg-slate-400 text-white px-4 py-1 rounded-t-lg hover:bg-slate-500">
                                            Side Mount
                                        </div>
                                        <div @click="setPosCss('default')"
                                            class="bg-slate-600 text-white px-4 py-1 rounded-t-lg hover:bg-slate-700">
                                            Close</div>
                                        <div @click="saveSettings"
                                            class="bg-[var(--mcolor)] w-24 grid justify-center text-white px-4 py-1 rounded-t-lg hover:bg-[var(--mcolor2)]">
                                            Save</div>
                                    </div>
                                </div>
                                <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                    v-model="form.settings.setup.customCSS">
                                </prism-editor>
                            </div>

                            <div v-if="css_pos == 'side-left'"
                                class="fixed top-0 left-[100px] w-[50vw] h-[100vh] p-10 pb-24 z-50 backdrop-blur-md">
                                <div class="flex justify-between">
                                    <div
                                        class="text-sm font-semibold text-slate-700 bg-sky-200 w-20 justify-center grid py-1 rounded-t-lg">
                                        CSS</div>
                                    <div class="flex self-center text-sm text-slate-800 gap-2 mr-2 cursor-pointer">
                                        <div @click="setPosCss('full')"
                                            class="bg-slate-400 text-white px-4 py-1 rounded-t-lg hover:bg-slate-500">
                                            Full Screen
                                        </div>
                                        <div @click="setPosCss('default')"
                                            class="bg-slate-600 text-white px-4 py-1 rounded-t-lg hover:bg-slate-700">
                                            Close</div>
                                        <div @click="saveSettings"
                                            class="bg-[var(--mcolor)] w-24 grid justify-center text-white px-4 py-1 rounded-t-lg hover:bg-[var(--mcolor2)]">
                                            Save</div>
                                    </div>
                                </div>
                                <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                    v-model="form.settings.setup.customCSS">
                                </prism-editor>
                            </div>

                            <div v-if="css_pos == 'side-right'"
                                class="fixed top-0 right-0 w-[50vw] h-[100vh] p-10 z-50 backdrop-blur-md">
                                <div class="flex justify-between">
                                    <div
                                        class="text-sm font-semibold text-slate-700 bg-sky-200 w-20 justify-center grid py-1 rounded-t-lg">
                                        CSS</div>
                                    <div class="flex self-center text-sm text-slate-800 gap-2 mr-2 cursor-pointer">
                                        <div @click="setPosCss('full')"
                                            class="bg-slate-400 text-white px-4 py-1 rounded-t-lg hover:bg-slate-500">
                                            Full Screen
                                        </div>
                                        <div @click="setPosCss('default')"
                                            class="bg-slate-600 text-white px-4 py-1 rounded-t-lg hover:bg-slate-700">
                                            Close</div>
                                        <div @click="saveSettings"
                                            class="bg-[var(--mcolor)] w-24 grid justify-center text-white px-4 py-1 rounded-t-lg hover:bg-[var(--mcolor2)]">
                                            Save</div>
                                    </div>
                                </div>
                                <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                    v-model="form.settings.setup.customCSS">
                                </prism-editor>
                            </div>


                        </div>

                    </div>
                </div>
            </Transition>
        </div>
        
    </div>

    <!-- Sync Confirmation Modal -->
    <WarningModal :show="showSyncConfirmModal" @close="cancelSync" icon="overwrite">
        <template #header>
            Sync Custom JS/CSS?
        </template>
        <template #content>
            <p>You have unsaved changes in your Custom JS or CSS. Another user has updated the questionnaire.</p>
            <p class="mt-2 font-semibold">Do you want to sync and overwrite your unsaved changes?</p>
            <p class="mt-2 text-xs text-gray-500">Your current unsaved changes will be lost if you choose to sync.</p>
        </template>
        <template #footer>
            <button
                type="button"
                @click="cancelSync"
                class="inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:ml-3 sm:w-auto"
            >
                Cancel
            </button>
            <button
                type="button"
                @click="confirmSync"
                class="inline-flex w-full justify-center rounded-md bg-[var(--mcolor)] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[var(--mcolor2)] sm:ml-3 sm:w-auto"
            >
                Sync Now
            </button>
        </template>
    </WarningModal>
    
</template>

<style scoped>
/* Your advanced settings styles go here */
</style>
