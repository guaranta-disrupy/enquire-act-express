<script setup>
import MainLayout from "@/Layouts/MainLayout.vue";
import S4Button from "@/S4/Button.vue";
import S4Input from "@/S4/Input.vue";
import S4InputSelect from "@/S4/InputSelect.vue";
import JetLabel from "@/Jetstream/Label.vue";
import S4Loading from "@/S4/Loading.vue";
import { computed, onMounted, ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import helpers from "@/helpers.js";
import prism_editor from "@/prism_editor.js";
import { S4CardTable } from "@/S4";
import FullScreen from "../Questionnaires/FullScreen.vue";

const { toast } = helpers();
const { highlighter, PrismEditor } = prism_editor();

const design = computed(() => {
    var themeData = [];
    usePage().props.themes.forEach((design, index) => {
        var textD, designData;
        textD = design.text;
        designData = design.setup.design;
        themeData.push({ text: textD, value: design.id });
    });
    return themeData;
});

const root = ref();
const setting = ref([]);
const switchValues = ref([
    { text: "Enable", value: 1 },
    { text: "Disable", value: 0 },
]);
const themes = ref([
    { text: "SSR", value: 0 },
    { text: "Blue", value: 1 },
]);
const form = useForm({
    settings: {
        name: usePage().props.questionnaire.name,
        setup: usePage().props.questionnaire.setup,
    },
});
const loading = ref(false);

const saveSettings = () => {
    loading.value = true;
    let params = [usePage().props.questionnaire.code];
    form.post(route("questionnaireSettings.update", params), {
        onSuccess: () => {
            toast.success(`Questionnaire settings successfully updated`, {
                timeout: 2000,
            });
            loading.value = false;
        },
    });
};
const buttonHeader = ref([
    "Next Button",
    "Loading Message",
    "Saving Message",
    "Previous Button",
    "Finish Button",
]);
const redirectsHeader = ref(["Duration", "Pass Internal", "Pass External"]);
const variableButton = ref([
    "Display Logo Only",
    "Variable name to pass",
    "Variable starting number",
    "Display Footer",
    "Display Asterisk",
]);
const variableButton2 = ref([
    "Hide Previous Button",
    "Logo",
    "Show Progressbar",
]);
const variableOther = ref([
    "Invalid Project",
    "Content",
    "Survey Closed",
    "Content",
]);
const variableOther2 = ref([
    "No ID Assigned",
    "Content",
    "Connection Lost",
    "Content",
]);

onMounted(() => {
    if (form.settings.setup.hasOwnProperty("variable_start") == false) {
        form.settings.setup["variable_start"] = 1; // set default value
    }
});
</script>

<template>
    <main-layout title="Settings">
        <template #header>
            <div style="" class="absolute right-0 bottom-0 p-4 z-10">
                <s4-button size="md" class="drop-shadow-lg" color="" @click="saveSettings()" :theme="'ssrOrange'">
                    <span class="pl-3 pr-3">SAVE</span>
                </s4-button>
            </div>
        </template>
        <template #content>
            <s4-loading v-show="loading" :label="'Saving'" :theme="'theme1'"
                class="h-screen z-50 overflow-hidden bg-gray-700/50" :color="'text-white'"></s4-loading>
            <div class="relative">
                <div class="absolute left-6 w-0.5 h-screen bg-slate-200"></div>
                <div class="relative flex items-center flex-row">
                    <div class="ml-1">
                        <span
                            class="flex justify-center font-bold text-ssr-blue2 text-md border rounded-md p-1.5 bg-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="ml-2">General</div>
                </div>
                <S4CardTable>
                    <template #header>
                        <div class="grid grid-cols-3 gap-1 px-4">
                            <div class="col-span-1 mt-3">
                                <jet-label class="font-bold text-xs text-slate-500">Survey Name</jet-label>
                            </div>
                            <div class="mt-3">
                                <jet-label class="font-bold text-xs text-slate-500">Randomise Questions</jet-label>
                            </div>
                            <div class="mt-3">
                                <jet-label class="font-bold text-xs text-slate-500">Survey Link</jet-label>
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-3 gap-1 px-4">
                            <div class="col-span-1 mt-2">
                                <s4-input type="text" color="ssrGray" class="block w-full"
                                    v-model="form.settings.name" />
                            </div>
                            <div class="mt-2">
                                <s4-input-select class="w-full" color="ssrGray" :lists="switchValues"
                                    v-model.number="form.settings.setup.random"></s4-input-select>
                            </div>
                            <div class="mt-2">
                                <s4-input type="text" color="ssrGray" class="block w-full" readonly
                                    :value="$page.props.engine_url + '?code=' + $page.props.questionnaire.code" />
                            </div>
                        </div>
                        <div class="mt-5 mx-4" v-if="form.settings.setup.random == 0">
                            <div>
                                <jet-label class="font-bold text-xs">Randomise selected question</jet-label>
                                <select
                                    class="form-control w-full selSet rounded-lg focus:border-ssr-blue3 focus:ring-2 focus:ring-ssr-blue3"
                                    multiple v-model="form.settings.setup.randomiseSQ">
                                    <option v-for="(item, index) in $page.props
                                        .questions" :value="item.id" :key="index">
                                        {{ item.question }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </template>
                </S4CardTable>
                <!-- Button  -->
                <div class="absolute left-6 w-0.5 h-screen bg-slate-200"></div>
                <div class="relative flex items-center flex-row mt-5">
                    <div class="ml-1">
                        <span
                            class="flex justify-center font-bold text-ssr-blue2 text-md border rounded-md p-1.5 bg-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                            </svg>
                        </span>
                    </div>
                    <div class="ml-2">Buttons</div>
                </div>
                <S4CardTable>
                    <template #header>
                        <div class="grid grid-cols-5 gap-5 px-4">
                            <div class="mt-3" v-for="(item, index) in buttonHeader" :key="[index]">
                                <jet-label class="font-bold text-xs text-slate-500">{{ item }}</jet-label>
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-5 gap-3 mt-1 mb-3 mx-4">
                            <div class="mt-1">
                                <s4-input class="w-full" color="ssrGray" v-model="form.settings.setup.messages.nextBtn
                                    " />
                            </div>
                            <div class="mt-1">
                                <s4-input class="w-full" color="ssrGray" v-model="form.settings.setup.messages.loading
                                    " />
                            </div>
                            <div class="mt-1">
                                <s4-input class="w-full" color="ssrGray" v-model="form.settings.setup.messages.saving
                                    " />
                            </div>
                            <div class="mt-1">
                                <s4-input class="w-full" color="ssrGray" v-model="form.settings.setup.messages.prevBtn
                                    " />
                            </div>
                            <div class="mt-1">
                                <s4-input class="w-full" color="ssrGray" v-model="form.settings.setup.messages.finalBtn
                                    " />
                            </div>
                        </div>
                    </template>
                </S4CardTable>
                <!-- Redirects -->
                <div class="relative">
                    <div class="absolute left-6 w-0.5 h-screen bg-slate-200"></div>
                    <div class="relative flex items-center flex-row mt-5">
                        <div class="ml-1">
                            <span
                                class="flex justify-center font-bold text-ssr-blue2 text-md border rounded-md p-1.5 bg-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </span>
                        </div>
                        <div class="ml-2">Redirects</div>
                    </div>
                    <S4CardTable>
                        <template #header>
                            <div class="grid grid-cols-2 gap-1 completes mx-4">
                                <div class="mt-5">
                                    <jet-label class="font-bold text-xs text-slate-500">URL</jet-label>
                                </div>
                                <div class="grid grid-cols-2 gap-1 mt-5">
                                    <div class="grid grid-cols-3 gap-1">
                                        <div v-for="(
                                                item, index
                                            ) in redirectsHeader" :key="index">
                                            <jet-label class="font-bold text-xs text-slate-500">{{ item }}</jet-label>
                                        </div>
                                    </div>
                                    <div>
                                        <jet-label class="font-bold text-xs text-slate-500">Message</jet-label>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-2 gap-1 completes mx-4">
                                <div class="">
                                    <jet-label class="mt-3 font-bold text-ssr-blue2">Completes</jet-label>
                                    <div>
                                        <s4-input color="ssrGray" type="text" class="block w-full" v-model="form.settings.setup.redirects
                                                .complete.url
                                            " />
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-1 mt-5 childCompletes">
                                    <div class="grid grid-cols-3 gap-1 mt-3 childCompletes">
                                        <div>
                                            <s4-input color="ssrGray" id="duration" class="block w-full" v-model="form.settings.setup
                                                    .redirects.complete
                                                    .duration
                                                " />
                                        </div>

                                        <div class="">
                                            <s4-input-select class="w-full" color="ssrGray" :lists="switchValues"
                                                v-model.number="form.settings.setup
                                                        .redirects.complete
                                                        .passInternalVars
                                                    "></s4-input-select>
                                        </div>
                                        <div class="">
                                            <s4-input-select class="w-full" color="ssrGray" :lists="switchValues"
                                                v-model.number="form.settings.setup
                                                        .redirects.complete
                                                        .passExternalVars
                                                    "></s4-input-select>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <s4-input color="ssrGray" type="text" class="block w-full" v-model="form.settings.setup.redirects
                                                .complete.content
                                            " />
                                    </div>
                                </div>
                            </div>
                            <!-- SCREENOUT -->
                            <div class="grid grid-cols-2 gap-1 completes mx-4">
                                <div class="">
                                    <jet-label class="mt-3 font-bold text-ssr-blue2">Screenout</jet-label>

                                    <div>
                                        <s4-input color="ssrGray" type="text" class="block w-full" v-model="form.settings.setup.redirects
                                                .screenout.url
                                            " />
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-1 mt-5 childCompletes">
                                    <div class="grid grid-cols-3 gap-1 mt-3 childCompletes">
                                        <div>
                                            <s4-input color="ssrGray" id="duration" class="block w-full" v-model="form.settings.setup
                                                    .redirects.screenout
                                                    .duration
                                                " />
                                        </div>

                                        <div class="">
                                            <s4-input-select class="w-full" color="ssrGray" :lists="switchValues"
                                                v-model.number="form.settings.setup
                                                        .redirects.screenout
                                                        .passInternalVars
                                                    "></s4-input-select>
                                        </div>
                                        <div class="">
                                            <s4-input-select class="w-full" color="ssrGray" :lists="switchValues"
                                                v-model.number="form.settings.setup
                                                        .redirects.screenout
                                                        .passExternalVars
                                                    "></s4-input-select>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <s4-input color="ssrGray" type="text" class="block w-full" v-model="form.settings.setup.redirects
                                                .screenout.content
                                            " />
                                    </div>
                                </div>
                            </div>
                            <!-- qualityControl -->
                            <div class="grid grid-cols-2 gap-1 completes mx-4">
                                <div class="">
                                    <jet-label class="mt-3 font-bold text-ssr-blue2">Quality Control</jet-label>

                                    <div>
                                        <s4-input color="ssrGray" type="text" class="block w-full" v-model="form.settings.setup.redirects
                                                .qualityControl.url
                                            " />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-1 mt-5 childCompletes">
                                    <div class="grid grid-cols-3 gap-1 mt-3 childCompletes">
                                        <div>
                                            <s4-input color="ssrGray" id="duration" class="block w-full" v-model="form.settings.setup
                                                    .redirects
                                                    .qualityControl.duration
                                                " />
                                        </div>

                                        <div class="">
                                            <s4-input-select class="w-full" color="ssrGray" :lists="switchValues"
                                                v-model.number="form.settings.setup
                                                        .redirects
                                                        .qualityControl
                                                        .passInternalVars
                                                    "></s4-input-select>
                                        </div>
                                        <div class="">
                                            <s4-input-select class="w-full" color="ssrGray" :lists="switchValues"
                                                v-model.number="form.settings.setup
                                                        .redirects
                                                        .qualityControl
                                                        .passExternalVars
                                                    "></s4-input-select>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <s4-input color="ssrGray" type="text" class="block w-full" v-model="form.settings.setup.redirects
                                                .qualityControl.content
                                            " />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </S4CardTable>
                </div>
                <!-- THEMES AND VARIABLES -->
                <div class="relative">
                    <div class="absolute left-6 w-0.5 h-screen bg-slate-200"></div>
                    <div class="relative flex items-center flex-row mt-5">
                        <div class="ml-1">
                            <span
                                class="flex justify-center font-bold text-ssr-blue2 text-md border rounded-md p-1.5 bg-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4.871 4A17.926 17.926 0 003 12c0 2.874.673 5.59 1.871 8m14.13 0a17.926 17.926 0 001.87-8c0-2.874-.673-5.59-1.87-8M9 9h1.246a1 1 0 01.961.725l1.586 5.55a1 1 0 00.961.725H15m1-7h-.08a2 2 0 00-1.519.698L9.6 15.302A2 2 0 018.08 16H8" />
                                </svg>
                            </span>
                        </div>
                        <div class="ml-2">Variable</div>
                    </div>
                    <S4CardTable>
                        <template #header>
                            <div class="grid grid-cols-5 gap-1 px-4">
                                <div class="mt-3" v-for="(item, index) in variableButton" :key="[index]">
                                    <jet-label class="font-bold text-xs text-slate-500">{{ item }}</jet-label>
                                </div>
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-5 gap-1 px-4">
                                <div class="mt-3">
                                    <s4-input-select class="w-full" color="ssrGray" :lists="switchValues"
                                        v-model.number="form.settings.setup.display_logo
                                            "></s4-input-select>
                                </div>
                                <div class="mt-3">
                                    <s4-input class="w-full" color="ssrGray" v-model="form.settings.setup.variable" />
                                </div>
                                <div class="mt-3">
                                    <s4-input type="number" class="w-full" color="ssrGray" v-model="form.settings.setup.variable_start
                                        " />
                                </div>
                                <div class="mt-3">
                                    <s4-input-select class="w-full" color="ssrGray" :lists="switchValues"
                                        v-model.number="form.settings.setup.footer.enable
                                            "></s4-input-select>
                                </div>
                                <div class="mt-3">
                                    <s4-input-select class="w-full" color="ssrGray" :lists="switchValues"
                                        v-model.number="form.settings.setup.display_asterisk
                                            "></s4-input-select>
                                </div>
                            </div>
                        </template>
                    </S4CardTable>
                    <S4CardTable>
                        <template #header>
                            <div class="grid grid-cols-3 gap-1 px-4">
                                <div class="mt-3" v-for="(item, index) in variableButton2" :key="[index]">
                                    <jet-label class="font-bold text-xs text-slate-500">{{ item }}</jet-label>
                                </div>
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-3 gap-1 px-4">
                                <div class="mt-3">
                                    <s4-input-select class="w-full" color="ssrGray" :lists="switchValues"
                                        v-model.number="form.settings.setup.hidePrevBtn
                                            "></s4-input-select>
                                </div>
                                <div class="mt-3">
                                    <s4-input class="w-full" color="ssrGray" v-model="form.settings.setup.footer.logo
                                        " />
                                </div>
                                <div class="mt-3">
                                    <s4-input-select class="w-full" color="ssrGray" :lists="switchValues"
                                        v-model.number="form.settings.setup.showProgressBar
                                            "></s4-input-select>
                                </div>
                            </div>
                        </template>
                    </S4CardTable>
                </div>

                <div class="relative">
                    <div class="absolute left-6 w-0.5 bg-slate-200"></div>
                    <div class="relative flex items-center flex-row mt-5">
                        <div class="ml-1">
                            <span
                                class="flex justify-center font-bold text-ssr-blue2 text-md border rounded-md p-1.5 bg-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z" />
                                </svg>
                            </span>
                        </div>
                        <div class="ml-2">Others</div>
                    </div>
                    <S4CardTable>
                        <template #header>
                            <div class="grid grid-cols-4 gap-1 px-4">
                                <div class="mt-3" v-for="(item, index) in variableOther" :key="[index]">
                                    <jet-label class="font-bold text-xs text-slate-500">{{ item }}</jet-label>
                                </div>
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-4 gap-1 px-4">
                                <div class="mt-3">
                                    <s4-input class="w-full" color="ssrGray" v-model="form.settings.setup.modals
                                            .invalidProject.title
                                        " />
                                </div>
                                <div class="mt-3">
                                    <s4-input class="w-full" color="ssrGray" v-model="form.settings.setup.modals
                                            .noIdAssigned.content
                                        " />
                                </div>
                                <div class="mt-3">
                                    <s4-input class="w-full" color="ssrGray" v-model="form.settings.setup.modals
                                            .surveyClosed.title
                                        " />
                                </div>
                                <div class="mt-3">
                                    <s4-input class="w-full" color="ssrGray" v-model="form.settings.setup.modals
                                            .surveyClosed.content
                                        " />
                                </div>
                            </div>
                        </template>
                    </S4CardTable>
                    <S4CardTable>
                        <template #header>
                            <div class="grid grid-cols-4 gap-1 px-4">
                                <div class="mt-3" v-for="(item, index) in variableOther2" :key="[index]">
                                    <jet-label class="font-bold text-xs text-slate-500">{{ item }}</jet-label>
                                </div>
                            </div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-4 gap-1 px-4">
                                <div class="mt-3">
                                    <s4-input class="w-full" color="ssrGray" v-model="form.settings.setup.modals
                                            .noIdAssigned.title
                                        " />
                                </div>
                                <div class="mt-3">
                                    <s4-input class="w-full" color="ssrGray" v-model="form.settings.setup.modals
                                            .noIdAssigned.content
                                        " />
                                </div>
                                <div class="mt-3">
                                    <s4-input class="w-full" color="ssrGray" v-model="form.settings.setup.modals
                                            .connectionLost.title
                                        " />
                                </div>
                                <div class="mt-3">
                                    <s4-input class="w-full" color="ssrGray" v-model="form.settings.setup.modals
                                            .connectionLost.content
                                        " />
                                </div>
                            </div>
                        </template>
                    </S4CardTable>
                </div>
                <div class="relative">
                    <div class="absolute left-6 w-0.5 h-full bg-slate-200"></div>
                    <div class="relative flex items-center flex-row mt-5">
                        <div class="ml-1">
                            <span
                                class="flex justify-center font-bold text-ssr-blue2 text-md border rounded-md p-1.5 bg-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                            </span>
                        </div>
                        <div class="ml-2">Code</div>
                    </div>
                    <div class="bg-white px-5">
                        <div class="ml-8">
                            <jet-label class="font-bold text-xs mb-2">Customize CSS</jet-label>
                            <FullScreen>
                                <template #content>
                                    <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                        v-model="form.settings.setup.customCSS">
                                    </prism-editor>
                                </template>
                            </FullScreen>
                        </div>
                        <div class="ml-8 mt-6">
                            <jet-label class="font-bold text-xs mb-2">Customize Script</jet-label>
                            <!-- <s4-text-area color="ssrGray" class="w-full h-screen" v-model="form.settings.setup.customScript"></s4-text-area> -->
                            <FullScreen>
                                <template #content>
                                    <prism-editor class="my-editor" :highlight="highlighter" line-numbers
                                        v-model="form.settings.setup.customJS">
                                    </prism-editor>
                                </template>
                            </FullScreen>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </main-layout>
</template>

<style scoped>
.cnt {
    border: 2px solid #dce0e370;
}

@media (max-width: 880px) {

    .generalButtons,
    .completes,
    .childCompletes {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        max-width: 100%;
        flex-direction: column;
    }
}

.QQ {
    padding-left: 1.5rem;
    padding-right: 2rem;
    border-bottom-right-radius: 1rem;
    font-size: 1rem;
}

.selSet {
    border-color: lightgrey !important;
    min-height: 200px;
    overflow: hidden;
}
</style>
