<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue'
    import JetDropdown from '@/Jetstream/Dropdown.vue';
    import { S4Input, S4Button, S4Loading, S4Pagination,S4Table } from '@/S4'
    import AddEditProjectModal from './modals/AddEditProject.vue'
    import {  ref, computed } from 'vue';
    import {  Link } from "@inertiajs/vue3";
    import helpers from '@/helpers.js'
    

    const { assign, moment } = helpers()

    const props = defineProps(['projects']);
    const modals = ref({
                project: {
                    //add: false
                    add_edit: false,
                    method: 'add',
                    data: null
                }
            });
    const searchQ = ref("");

    const headers = ref([
    {
        label:'NAME',
        class:"pl-6 py-5 font-semibold w-1/3 rounded-tl-xl"
    },
    {
        label:'DETAILS',
        class:"pr-6 py-2 font-semibold w-1/4"
    },
    {
        label:'CREATED BY',
        class:"px-6 py-2 font-semibold w-1/5"
    },
    {
        label:'MODIFIED',
        class:" py-2 font-semibold w-1/6"
    },
    {
        label:'',
        class:"w-1/8 rounded-tr-xl"
    }
])
    
    const searchProject = computed(() => props.projects.data.filter(projects => {
                    return projects.name.toLowerCase().includes(searchQ.value.toLowerCase());
                })
        );
  
    //component,variable,action,index
    const showModal = (table, modal, method, index = -1)=> {
       
        modals.value[table][modal] = true;
        modals.value[table].method = method
        if (index >= 0) {
            modals.value[table].data = assign(props.projects.data[index])
        }else {
            modals.value[table].data = null;
        }  

        // console.log(modals)

    }

    const closeModal = (table, modal) => {
            modals.value[table][modal] = false
            modals.value[table].data = null
    }
 
</script>
<template>
    <app-layout title="Projects">  
        <template #topbar> 
                <div class="flex mt-3 mb-2">
                    <form class="group relative">
                        <svg width="20" height="20" fill="#c0c0c0" class="absolute left-3 top-1/2 -mt-2 text-slate-400 pointer-events-none group-focus-within:text-blue-500" aria-hidden="true">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" />
                        </svg>
                        <s4-input color="ssrGray" type="text" class="block text-sm w-60 py-2 pl-10" placeholder="Search project" v-model="searchQ"/>
                    </form>

                    <div style="" class="absolute right-0 bottom-0 p-4 z-50">
                        <s4-button class="drop-shadow-lg" size="md" @click="showModal('project',  'add_edit', 'add')" :theme="'floatingBTN'">
                            <span class="pl-3 pr-3">ADD PROJECT</span>
                        </s4-button>
                    </div>
                </div>                   
        </template>

        <template #content>
            <s4-table :items="searchProject" :headers="headers">
                <template #body> 
                    <tr class="text-md border-b border-l border-r border-gray-100 text-slate-500 hover:bg-slate-50" v-for="(project, index) in searchProject" :key="index"> 
                        <td class="truncate">
                            <Link class="px-6 py-4 items-center"
                                :href="route('questionnaires.index', project.code)">
                                <span class="">
                                        <h1>Name</h1>{{ project.name }}
                                </span>
                            </Link>
                        </td>
                        <td class="truncate">
                            <Link class="pr-6 py-4 items-center"
                                :href="route('questionnaires.index', project.code)">
                                <span class="group-hover:text-gray-700">
                                    <h1>Details</h1>{{ project.details }}
                                </span>
                            </Link>
                        </td>
                        <td>
                            <Link class="px-6 py-4 flex items-center"
                                :href="route('questionnaires.index', project.code)">
                                <span class="group-hover:text-gray-700">
                                    <h1>Created by</h1>{{ project.user?.name }}
                                </span>
                            </Link>
                        </td>
                        <td>
                            <Link class="pr-6 py-4 flex items-center" 
                                :href="route('questionnaires.index', project.code)">
                                <span class="group-hover:text-gray-700">
                                    <h1>Modified</h1>{{moment(new Date(project.updated_at)).format('MM/DD/YY LT')}}
                                </span>
                            </Link>
                        </td>
                        <!-- ACTIONS -->
                        <td class="pr-4">
                            <JetDropdown align="right" width="48">
                                <template #trigger>
                                    <!-- <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                        <img class="h-8 w-8 rounded-full object-cover" :src="$page.props.user.profile_photo_url" :alt="$page.props.user.name">
                                    </button> -->

                                    <span  class="hover:scale-125 hover:ease-in duration-200 bg-slate-50 px-2 py-2 text-slate-500 rounded-full inline-flex items-center cursor-pointer hover:bg-blue-50"> 
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots" viewBox="0 0 16 16"> <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/> </svg>
                                    </span>
                                </template>

                                <template #content>
                                    <!-- Account Management -->
                                    <div class="block px-4 py-2 text-xs text-slate-400">
                                        Action
                                    </div>
                                    <a @click="showModal('project', 'add_edit', 'edit',index)" class=" text-sm text-slate-500 rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap" href="#">Edit</a>
                                    <a @click="showModal('project', 'add_edit', 'copy',index)" class=" text-sm text-slate-500 rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap" href="#">Copy</a>
                                    <a @click="showModal('project', 'add_edit', 'delete',index)" class="text-sm text-slate-500 rounded-t bg-white hover:bg-gray-100 py-2 px-4 block whitespace-no-wrap" href="#">Delete</a>
                                </template>
                            </JetDropdown>
                        </td> 
                    </tr> 
                    <tr v-if="props.projects.data.length === 0">
                        <td class="border-t px-6 py-4 text-gray-500 text-sm group-hover:text-gray-700 bg-white" colspan="5" style="border-radius:1rem!important;">
                            <div class="italic text-gray-400 text-md">There are no projects available right now.</div>
                        </td>
                    </tr>                    
                </template>        
            </s4-table> 
            <s4-pagination :links="props.projects.links"/>
            <add-edit-project-modal :modal="modals.project" @close="closeModal('project', 'add_edit')" /> 
        </template>  
    </app-layout>
</template>

<style scoped>
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
    }
        td span h1{
        display:block;
        text-align: center;
        text-decoration: underline;
        font-size: 1rem;
        color: rgb(226, 135, 51);
        
}
  }
.fade-enter-active,
.fade-leave-active {
  transition: opacity .4s linear;
}

.fade-enter,
.fade-leave-to {
  opacity: 0;
}

/*.prjList {
    border-top:0.1rem solid #F3F4F6;
}*/

/* .prjList:nth-child(even) {
    background: #FAFBFB; 
} */


.ft {
    font-size:1.5rem;
    padding:0.2rem;
}

.bt {
    border-radius:50%;
    width:2rem;
    height:2rem;
}

.addPrj {
    margin-left: auto;
}

.prjText {
    font-weight:900;
    font-size:1.5rem;
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

/*tooltip delete*/
.toolTipDel {
    opacity: 0;
    transition: 0.3s;
}
.prjBtnDel:hover > .toolTipDel {
    opacity: 1;
}

/*tooltip copy*/
.toolTipCopy {
    opacity: 0;
    transition: 0.3s;
}
.prjBtnCopy:hover > .toolTipCopy {
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
.dropdown:hover .dropdown-menu {
  display: block;
}

</style>
