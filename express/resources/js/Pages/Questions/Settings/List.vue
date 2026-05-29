<script setup>
import JetDropdown from "@/Jetstream/Dropdown.vue";
import { S4Table } from "../../../S4";
import { ref } from "vue";

const props = defineProps(["data"]);
const emit = defineEmits(["onAction"]);

const handleAction = (key, action) => {
    emit("onAction", key, action);
};
const headers = ref([
    {
        label: "Index",
        class: "pl-2 py-5 font-semibold w-1/8",
    },
    {
        label: "Panel Name",
        class: "pl-2 py-5 font-semibold w-1/8 rounded-tl-xl",
    },
    {
        label: "Variable",
        class: "pr-6 py-2 font-semibold w-1/8",
    },
    {
        label: "Complete URL",
        class: "px-6 py-2 font-semibold w-1/4",
    },
    {
        label: "Quality Control URL",
        class: " py-2 font-semibold w-1/4",
    },
    {
        label: "Screen Out URL",
        class: "w-1/8 rounded-tr-xl",
    },
]);
</script>
<template>
    <!-- <S4Table v-if="1" :headers="headers" class="">
        <template #body>
            <tr class="text-md border-b border-l border-r border-gray-100 text-slate-500 hover:bg-slate-50"
                v-for="(list, index) in data" :key="index">
                <td class="pl-2 truncate">
                    <span class="group-hover:text-gray-700">
                        <h1>{{ index }}</h1>
                    </span>
                </td>
                <td class="pl-2 truncate">
                    <span class="group-hover:text-gray-700">
                        <h1>{{ list.panel }}</h1>
                    </span>
                </td>
                <td class="truncate">
                    <span class="group-hover:text-gray-700">
                        <h1>{{ list.variable }}</h1>
                    </span>
                </td>
                <td>
                    <span class="group-hover:text-gray-700">
                        <h1>{{ list.complete?.url }}</h1>
                    </span>
                </td>
                <td>
                    <span class="group-hover:text-gray-700">
                        <h1>
                            {{ list.qualityControl?.url }}
                        </h1>
                    </span>
                </td>
                <td>
                    <span class="group-hover:text-gray-700">
                        <h1>{{ list.screenout?.url }}</h1>
                    </span>
                </td>
                <td class="pr-4">
                    <JetDropdown align="right" width="48">
                        <template #trigger>
                            <span
                                class="hover:scale-125 hover:ease-in duration-200 bg-slate-50 px-2 py-2 text-slate-500 rounded-full inline-flex items-center cursor-pointer hover:bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                    class="bi bi-three-dots" viewBox="0 0 16 16">
                                    <path
                                        d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z" />
                                </svg>
                            </span>
                        </template>

                        <template #content>
                            <div class="block px-4 py-2 text-xs text-slate-400">
                                Action
                            </div>
                            <a @click="handleAction(index, 'edit')"
                                class="text-sm text-slate-500 rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap">Edit</a>
                            <a @click="handleAction(index, 'delete')"
                                class="text-sm text-slate-500 rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap">Delete</a>
                        </template>
                    </JetDropdown>
                </td>
            </tr>
            <tr v-if="data.length === 0">
                <td class="border-t px-6 py-4 text-gray-500 text-sm group-hover:text-gray-700 bg-white" colspan="5"
                    style="border-radius: 1rem !important">
                    <div class="italic text-gray-400 text-md">
                        There are no projects available right now.
                    </div>
                </td>
            </tr>
        </template>
    </S4Table> -->

    <div class="mt-10 transition-all" v-if="Object.keys(data).length">
        <div class="w-full">
            <div class="grid border rounded-lg px-5 divide-y divide-neutral-200 mt-8">

                <div class="py-5" v-for="(list, index) in data" :key="index">
                    <details class="group">
                        <summary class="flex justify-between items-center font-medium cursor-pointer list-none">
                            <span class="text-sky-500">{{ list.panel }}</span>
                            <span class="transition-all group-open:rotate-180">
                                <svg fill="none" height="24" shape-rendering="geometricPrecision" stroke="currentColor"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24"
                                    width="24">
                                    <path d="M6 9l6 6 6-6"></path>
                                </svg>
                            </span>
                        </summary>
                        <div class="text-slate-400 text-xs mt-5 group-open:animate-fadeIn">
                            <div class="grid grid-cols-5 gap-4">
                                <div class="col-span-1 text-slate-600 font-semibold">Variable</div>
                                <div class="col-span-4 grid grid-cols-1 gap-2">
                                    <div class="flex flex-cols-2">
                                        <div class="font-semibold text-slate-500 mr-2">{{ list.variable }}</div>
                                        <div></div>
                                    </div>                                    
                                </div>
                            </div>
                        </div>
                        <div class="text-slate-400 text-xs mt-3 grid grid-cols-1 gap-5 group-open:animate-fadeIn">
                            <div class="grid grid-cols-5 gap-4">
                                <div class="col-span-1 text-slate-600 font-semibold">Complete</div>
                                <div class="col-span-4 grid grid-cols-1 gap-2">
                                    <div class="flex flex-cols-2">
                                        <div class="font-semibold text-slate-500 mr-2">URL :</div>
                                        <div>{{ list.complete?.url }}</div>
                                    </div>
                                    <div class="flex flex-cols-1 gap-3">
                                        <div class="flex flex-cols-2">
                                            <div class="font-semibold text-slate-500 mr-2">Duration :</div>
                                            <div>{{ list.complete?.duration }},</div>
                                        </div>
                                        <div class="flex flex-cols-2">
                                            <div class="font-semibold text-slate-500 mr-2">Pass Internal :</div>
                                            <div>{{ list.complete?.passInternalVars ? 'Enable' : 'Disable' }},</div>
                                        </div>
                                        <div class="flex flex-cols-2">
                                            <div class="font-semibold text-slate-500 mr-2">Pass External :</div>
                                            <div>{{ list.complete?.passExternalVars ? 'Enable' : 'Disable' }},</div>
                                        </div>
                                    </div>
                                    <div class="flex flex-cols-2 border border-black/0 border-b-slate-100 pb-4">
                                        <div class="font-semibold text-slate-500 mr-2">Message :</div>
                                        <div>{{ list.complete?.content }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-5 gap-4">
                                <div class="col-span-1 text-slate-600 font-semibold">Quality Control</div>
                                <div class="col-span-4 grid grid-cols-1 gap-2">
                                    <div class="flex flex-cols-2">
                                        <div class="font-semibold text-slate-500 mr-2">URL :</div>
                                        <div>{{ list.qualityControl?.url }}</div>
                                    </div>
                                    <div class="flex flex-cols-1 gap-3">
                                        <div class="flex flex-cols-2">
                                            <div class="font-semibold text-slate-500 mr-2">Duration :</div>
                                            <div>{{ list.qualityControl?.duration }},</div>
                                        </div>
                                        <div class="flex flex-cols-2">
                                            <div class="font-semibold text-slate-500 mr-2">Pass Internal :</div>
                                            <div>{{ list.qualityControl?.passInternalVars ? 'Enable' : 'Disable' }},</div>
                                        </div>
                                        <div class="flex flex-cols-2">
                                            <div class="font-semibold text-slate-500 mr-2">Pass External :</div>
                                            <div>{{ list.qualityControl?.passExternalVars ? 'Enable' : 'Disable' }},</div>
                                        </div>
                                    </div>
                                    <div class="flex flex-cols-2 border border-black/0 border-b-slate-100 pb-4">
                                        <div class="font-semibold text-slate-500 mr-2">Message :</div>
                                        <div>{{ list.qualityControl?.content }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-5 gap-4">
                                <div class="col-span-1 text-slate-600 font-semibold">Screenout</div>
                                <div class="col-span-4 grid grid-cols-1 gap-2">
                                    <div class="flex flex-cols-2">
                                        <div class="font-semibold text-slate-500 mr-2">URL :</div>
                                        <div>{{ list.screenout?.url }}</div>
                                    </div>
                                    <div class="flex flex-cols-1 gap-3">
                                        <div class="flex flex-cols-2">
                                            <div class="font-semibold text-slate-500 mr-2">Duration :</div>
                                            <div>{{ list.screenout?.duration }},</div>
                                        </div>
                                        <div class="flex flex-cols-2">
                                            <div class="font-semibold text-slate-500 mr-2">Pass Internal :</div>
                                            <div>{{ list.screenout?.passInternalVars ? 'Enable' : 'Disable' }},</div>
                                        </div>
                                        <div class="flex flex-cols-2">
                                            <div class="font-semibold text-slate-500 mr-2">Pass External :</div>
                                            <div>{{ list.screenout?.passExternalVars ? 'Enable' : 'Disable' }},</div>
                                        </div>
                                    </div>
                                    <div class="flex flex-cols-2 border border-black/0 border-b-slate-100 pb-4">
                                        <div class="font-semibold text-slate-500 mr-2">Message :</div>
                                        <div>{{ list.screenout?.content }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-5 gap-4 text-sm">
                                <div class="col-span-1"></div>
                                <div class="col-span-4 grid grid-cols-1 gap-2">
                                    <div class="flex flex-cols-1 gap-3">
                                        <div class="flex gap-5 cursor-pointer">
                                            <div @click="handleAction(index, 'edit')" class="font-medium text-sky-500 hover:text-sky-300 transition">Edit</div>
                                            <div @click="handleAction(index, 'delete')" class="font-medium text-red-300 hover:text-red-500 transition">Delete</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                        
                    </details>
                </div>



            </div>
        </div>
    </div>
</template>
