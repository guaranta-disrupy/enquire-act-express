<script setup>
import { inject, onBeforeMount, ref, watch } from 'vue';
import { Link, usePage } from "@inertiajs/vue3";
import { S4InputSelect, S4Button, S4Svg, S4Pagination, S4Table } from '@/S4'
import AppLayout from '@/Layouts/AppLayout.vue'
import JetDropdown from '@/Jetstream/Dropdown.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSync } from '@fortawesome/free-solid-svg-icons'
import helpers from '@/helpers.js'

const { moment } = helpers()
const faIcon = ref({sync: faSync});
const questions = usePage().props.questions
const headers = inject('headers')
const showModal = inject('showModal')
const list = ref([])

watch(
    ()=> { usePage().props.questions },
    ()=> { 
        list.value = usePage().props.questions?.data; 
    },
    { deep: true }
)

onBeforeMount(()=>{
    list.value = questions.data
})


</script>

<template>
    <div v-if="questions">
        <div class=" gap-2">
                <s4-table :items="list" :headers="headers">
                <template #body>
                    <tr class="text-md border-b border-l border-r border-gray-100 text-slate-500 hover:bg-slate-50 overflow-hidden relative"  v-for="(questions,index) in list" :key="index">
                        <td class="">
                            <div class="px-6 py-4 items-center truncate w-11/12">{{ questions.question.replace(/(&lt;([^>]+)>)/gi, "") }}</div>
                        </td>
                        
                        <td class="truncate"> 
                            <div class="px-6 py-4">{{ moment(new Date(questions.deleted_at)).format('MM/DD/YY LT') }}</div> 
                        </td> 
                        <td>
                            <div class="absolute right-6 top-2">
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
                                        <a @click="showModal('trash', 'delete_restore', 'restore',index, 'questions')" class=" text-sm text-slate-500 rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap" href="#">Restore</a>
                                        <a @click="showModal('trash', 'delete_restore', 'delete',index, 'questions')" class="text-sm text-slate-500 rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap" href="#">Delete Permanently</a>
                                    </template>
                                </JetDropdown>
                            </div>
                        </td>
                    </tr>                 
                </template>
            </s4-table> 
        </div> 
        <s4-pagination :links="questions.links"/>
    </div>
    <div v-else>
        No data
    </div>

    
</template>