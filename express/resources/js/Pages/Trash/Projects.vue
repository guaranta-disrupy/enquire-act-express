<script setup>
import { inject, onMounted, ref, watch } from 'vue';
import { Link, usePage } from "@inertiajs/vue3";
import JetDropdown from '@/Jetstream/Dropdown.vue';
import { S4Sidebar, S4Input, S4InputSelect, S4Button, S4Svg, S4Pagination, S4Table } from '@/S4'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSpinner, faTrashAlt,faSync } from '@fortawesome/free-solid-svg-icons'
import helpers from '@/helpers.js'

const { moment } = helpers()

const projects = usePage().props.projects
const emit = defineEmits(['updateList'])
const faIcon = ref({
    spinner: faSpinner,
    trash: faTrashAlt,
    sync: faSync
});
const sort = ref([
    {
        text: 'All',
        value : 'All'
    },
    {
        text: 'Name',
        value : 'name'
    },
    {
        text: 'Created by',
        value : 'user_id'
    },
    {
        text: 'Date deleted',
        value : 'deleted_at'
    }
]);
const sort_by = ref(0);
const list = ref([]);

const headers = inject('headers')
const updateProjects = () => {
    
    switch(sort_by.value){
        case "name":
            list.value.sort(sortByName)
            break;
        case "user_id":
            list.value.sort(sortByName)
            break;
        case "deleted_at":
            list.value.sort(sortByDate)
            break;
        default:
             list.value = projects.data
            break;
    }

    
};

const sortByName = (a,b) => {
    let key = 'name'
    let ifByUserId = sort_by.value === "user_id"
    let fa = ifByUserId ? a.user[key].toLowerCase() : a[sort_by.value].toLowerCase(),
    fb = ifByUserId ? b.user[key].toLowerCase() : b[sort_by.value].toLowerCase();

    if (fa < fb) {
        return -1;
    }
    if (fa > fb) {
        return 1;
    }
    return 0;
}

const showModal = inject('showModal')

const sortByDate = (a,b)=>{
    let da = new Date(a.deleted_at),
        db = new Date(b.deleted_at);
    return da - db;
}

const updateList = () => {
     list.value = projects.data
}
     
onMounted(()=>{
    updateList()   
});

watch(
    () => { usePage().props.projects?.data },
    () => {
        list.value = usePage().props.projects?.data
    },
    {deep:true}

)

    
</script>

<template> 
        <div v-if="list">
            <div class=" gap-2">
                 <s4-table :items="list" :headers="headers">
                    <template #body>
                        <tr class="text-md border-b border-l border-r border-gray-100 text-slate-500 hover:bg-slate-50"  v-for="(log,index) in list" :key="index">
                            <td class="truncate">
                                <div class="px-6 py-4 items-center">
                                   {{ log.name }} 
                                </div>
                            </td>
                             <td class="truncate">
                                <Link class="px-6 py-4 items-center"
                                    href="#">
                                    <span class="">
                                        <h1>Date Deleted</h1>{{ moment(new Date(log.deleted_at)).format('MM/DD/YY LT') }}
                                    </span>
                                </Link>
                            </td>
                            <td class="flex justify-end items-center">
                                <div class="mt-2 mr-4">
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
                                            <a @click="showModal('trash', 'delete_restore', 'restore',index,'projects')" class=" text-sm text-slate-500 rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap" href="#">Restore</a>
                                            <a @click="showModal('trash', 'delete_restore', 'delete',index,'projects')" class="text-sm text-slate-500 rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap" href="#">Delete Permanently</a>
                                        </template>
                                    </JetDropdown>
                                </div>
                                
                            </td>
                        </tr>                 
                    </template>
                </s4-table> 
            </div> 
            <s4-pagination :links="projects.links"/>
        </div>
        <div v-else>
            No data
        </div>
   
</template>



<style>

td span h1{
    display: none;
    color:black;
}
@media (max-width: 768px) {
    th{
        display: none; 
    }
    td{
        display: flex; 
        flex-wrap: wrap; 
        justify-content: center;
        width: 100%;
        text-align: center;
    }
    td span h1{
        display:block;
        text-align: center;
        text-decoration: underline;
        font-size: 1.2rem;
        color: rgb(226, 135, 51);
        
    }
  }
.col {
    width:20vmin;
}
.colRow {
    width:30vmin;
}
.colRow {
    max-height:15vmin;
    margin:auto auto;
    position:relative;
}
.prjList {
    border-top:0.2rem solid #F3F4F6;
    border-radius:0.9rem;    
}

/* .prjList:nth-child(even) {
    background: #FAFBFB; 
} */

.prjList:hover {
    background: #F3F4F6;
}

.prjText {
    font-weight:900; 
    justify-content: center;
    align-items: center;
    margin-left:0.5rem;
    display:flex;
}
.headerBtn {
    width:3.25rem;
}

.headerBtn:hover {
     border-color:lightgrey;
}

</style>