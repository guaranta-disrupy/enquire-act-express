<script setup>
import { inject, onBeforeMount, onMounted, ref, watch } from 'vue';
import { Link, usePage } from "@inertiajs/vue3";
 import { S4InputSelect, S4Button, S4Svg, S4Pagination, S4Table } from '@/S4'
import AppLayout from '@/Layouts/AppLayout.vue'
import JetDropdown from '@/Jetstream/Dropdown.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSync } from '@fortawesome/free-solid-svg-icons'
import helpers from '@/helpers.js'

const { moment } = helpers()
const faIcon = ref({sync: faSync});
const questionnaire = usePage().props.questionnaires
const headers = inject('headers')
const showModal = inject('showModal')
const list = ref([])

watch(
    ()=> { usePage().props.questionnaires },
    ()=> { 
              list.value = usePage().props.questionnaires?.data; 
    },
    { deep: true }
)

onBeforeMount(()=>{
    list.value = questionnaire.data
})

</script>

<template>
 
        <div v-if="questionnaire">
            <div class=" gap-2">
                 <s4-table :items="list" :headers="headers">
                    <template #body >
                        <tr class="text-md border-b border-l border-r border-gray-100 text-slate-500 hover:bg-slate-50"  v-for="(questionnaire,index) in list" :key="index">
                            <td class="truncate">
                                <Link class="px-6 py-4 items-center"
                                    href="#">
                                    <span class="">
                                        <h1>Project name</h1>{{ questionnaire.name }}
                                    </span>
                                </Link>
                            </td>
                             <td class="truncate">
                                <Link class="px-6 py-4 items-center"
                                    href="#">
                                    <span class="">
                                        <h1>Date Deleted</h1>{{ moment(new Date(questionnaire.deleted_at)).format('MM/DD/YY LT') }}
                                    </span>
                                </Link>
                            </td>
                            <td class="flex justify-end items-center">
                                <div class="my-[10px] mr-4">
                                    <JetDropdown align="right" width="48">
                                        <template #trigger>  
                                            <span class="bg-slate-50 px-2 py-2 text-slate-500 rounded-full inline-flex items-center cursor-pointer hover:bg-blue-50"> 
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots" viewBox="0 0 16 16"> <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/> </svg>
                                            </span>
                                        </template>

                                        <template #content>
                                            <!-- Account Management -->
                                            <div class="block px-4 py-2 text-xs text-slate-400">
                                                Action
                                            </div>
                                            <a @click="showModal('trash', 'delete_restore', 'restore',index, 'questionnaires')" class=" text-sm text-slate-500 rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap" href="#">Restore</a>
                                            <a @click="showModal('trash', 'delete_restore', 'delete',index, 'questionnaires')" class="text-sm text-slate-500 rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap" href="#">Delete Permanently</a>
                                        </template>
                                    </JetDropdown>
                                </div>
                            </td>
                        </tr>                 
                    </template>
                </s4-table> 
            </div> 
            <s4-pagination :links="questionnaire.links"/>
        </div>
        <div v-else>
            No data
        </div>

    
</template>