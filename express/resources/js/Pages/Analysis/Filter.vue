<script setup>

import { faTrashAlt, faSync, faEdit } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import JetDropdown from '@/Jetstream/Dropdown.vue';
import AddEditDeleteFilterModal from './modal/AddEditDeleteFilter.vue'
import { S4Button, S4Table } from '@/S4'
import axios from 'axios';
import { ref } from 'vue';
import { useForm, usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js'
import Pagination from '../../S4/Pagination.vue';

const { assign, toast, moment } = helpers()

const thHeader = ref(['Name','ID(s)','Date Created','Date Modified', 'Actions'])
const faIcon = ref({ 
    trash: faTrashAlt, 
    sync:faSync,
    edit:faEdit
})
const modals = ref({
    filter: { 
        add_edit: false,
        method: 'add',
        data: null
    }
})

const form = useForm({
    id: -1
}, { 
    resetOnSuccess: true,
})

const filterCount = (index)=>{
            if (usePage().props.filters[index].filtered_ids != null) {
                return usePage().props.filters[index].filtered_ids.split(',').length
            }else {
                return 0
            }            
        }
const closeModal = (table, modal) =>{
    modals.value[table][modal] = false
    modals.value[table].data = null
    }
const showModal = (table, modal, method, index = -1) =>{ 
    console.log(method,index)
    modals.value[table][modal] = true;
    modals.value[table].method = method
    // console.log(this.modals[table][modal])
    if (index >= 0) {
        modals.value[table].data = assign(usePage().props.filters[index])
    }else {
        modals.value[table].data = null;
    }   
}
const syncIDs = (id)=> {
    form.id = assign(id)
    form.post(route('filters.syncIDs',usePage().props.project.code),{
        preserveScroll: true,
        onSuccess: (status) => {
            // console.log(status)
            toast.success(`Filter IDs updated`, {
                timeout: 2000
            })
        },
        onError: () => {
            
        },
        onFinish: (err) => {
            console.log(err)
            form.reset()
        }
    })
}
const syncAll = ()=> {
    form.id = 0
    form.post(route('filters.syncIDs', usePage().props.project.code),{
        preserveScroll: true,
        onSuccess: (status) => {
            // console.log(status)
            toast.success(`All Filter IDs updated`, {
                timeout: 2000
            })
        },
        onError: () => {
            
        },
        onFinish: (err) => {
            console.log(err)
            form.reset()
        }
    })
}

    const headers = ref([
        {
            label:'NAME',
            class:"pl-6 py-5 font-semibold w-1/3 rounded-tl-xl"
        },
        {
            label:'ID(S)',
            class:"pr-6 py-2 font-semibold w-1/6"
        },
        {
            label:'DATE CREATED',
            class:"px-6 py-2 font-semibold w-1/5"
        },
        {
            label:'DATE MODIFIED',
            class:" py-2 font-semibold w-1/5"
        },
        {
            label:'',
            class:"w-1/8 rounded-tr-xl"
        }
    ])


 
</script>
<template>
    <div class="bg-white rounded-md py-3">

        <div class="float-clear flex-initial w-full">

            <!-- ###
            ###
            ###
            temporarily hidden sync button
            ###
            ###
            ### -->
            <!-- <s4-button class="float-left -mt-5 mb-3 bg-ssr-orange3 text-gray-600 hover:text-gray-400 hover:bg-ssr-orange3" 
                @click="syncAll()" :theme="'theme2'">
                <span class="pl-3 pr-3">Sync All</span>
            </s4-button> -->

            <div style="" class="absolute right-0 bottom-0 p-4">
                <s4-button size="md" class="drop-shadow-lg z-10" color="" @click="showModal('filter', 'add_edit', 'add')" :theme="'ssrOrange'">
                    <span class="pl-3 pr-3">ADD FILTER</span>
                </s4-button>
            </div> 


            <s4-table :headers="headers" class="mt-3">
                <template #body>                    
                    <tr class="text-md border-b border-l border-r border-gray-100 text-slate-500 hover:bg-slate-50" v-for="(filter,index) in $page.props.filters" :key="index">
                        <td class="py-3 px-5 whitespace-nowrap hover:text-ssr-blue2 cursor-pointer">{{filter.name}}</td>
                        <td class="py-3 px-0 text-left whitespace-nowrap ">{{filterCount(index)}}</td>
                        <td class="py-3 px-6 whitespace-nowrap ">{{moment(new Date(filter.created_at)).format('MM/DD/YY LT')}}</td>
                        <td class="py-3 px-0 whitespace-nowrap ">{{moment(new Date(filter.updated_at)).format('MM/DD/YY LT')}}</td>
                        <td class="py-2 pr-3 whitespace-nowrap flex flex-row-reverse">                    
                        </td>
                        <!-- ACTIONS -->
                        <td class="pr-4">
                            <JetDropdown align="right" width="48">
                                <template #trigger>

                                    <span  class="hover:scale-125 hover:ease-in duration-200 bg-slate-50 px-2 py-2 text-slate-500 rounded-full inline-flex items-center cursor-pointer hover:bg-blue-50"> 
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots" viewBox="0 0 16 16"> <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/> </svg>
                                    </span>
                                </template>

                                <template #content>
                                    <div class="block px-4 py-2 text-xs text-slate-400">
                                        Action
                                    </div>
                                    <a @click="syncIDs(filter.id)" class=" text-sm text-slate-500 rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap" href="#" :disabled="form.processing && filter.id == form.id">Sync</a>
                                    <a @click="showModal('filter',  'add_edit', 'edit', index)" class=" text-sm text-slate-500 rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap" href="#">Edit</a>
                                    <a @click="showModal('filter',  'add_edit', 'delete', index)" class="text-sm text-slate-500 rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap" href="#">Delete</a>
                                </template>
                            </JetDropdown>
                        </td> 
                    </tr>
                    <tr v-if="$page.props.filters.length=== 0">
                        <td class="border-t px-6 py-4 text-gray-500 text-sm group-hover:text-gray-700 bg-white" colspan="5" style="border-radius:1rem!important;">
                            <div class="italic text-gray-400 text-md">There are no filters available right now.</div>
                        </td>
                    </tr>                        
                </template>
            </s4-table>
        </div>
        <add-edit-delete-filter-modal :modal="modals.filter" @close="closeModal('filter', 'add_edit')" @updates="syncIDs"/>
    </div>
</template>


<style scoped>
.ft {
    font-size:1.5rem;
    padding:0.2rem;
}

.bt {
    border-radius:50%;
    width:2rem;
    height:2rem;
}
.thN {
    background-color: #F0F8FF;
}

/*tooltip delete*/
.toolTipDel {
    opacity: 0;
    transition: 0.3s;
}
.prjBtnDel:hover > .toolTipDel {
    opacity: 1;
}

/*tooltip edit*/
.toolTipEdit {
    opacity: 0;
    transition: 0.3s;
}
.prjBtnEdit:hover > .toolTipEdit {
    opacity: 1;
}

/*tooltip sync*/
.toolTipSync {
    opacity: 0;
    transition: 0.3s;
}
.prjBtnSync:hover > .toolTipSync {
    opacity: 1;
}
</style>
