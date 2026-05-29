<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
 import { S4Input } from '@/S4'
import { Projects, Questionnaires, Questions, TrashModal  } from './';
import { computed, onMounted, onUpdated, provide, ref } from 'vue';
import helpers from '@/helpers.js'
import { useForm } from "@inertiajs/vue3";
    

const { assign } = helpers()

const props = defineProps(['projects','questionnaires','questions']);

const currentTab = ref(localStorage.getItem('currentTab'))
const tab = ref(["Questions","Questionnaires","Projects"])
const searchQ  = ref('')
const form = useForm({
    name: '',
    type: '',
    }, { 
    resetOnSuccess: true
})

const modals = ref({
    trash: {
        //add: false
        delete_restore: false,
        method: 'update',
        data: null,
        type : 'projects'
    }
});

const headers = ref([
    {
        label:'PROJECT NAME',
        class:"pl-6 py-5 font-semibold w-1/2 rounded-tl-xl"
    },
    {
        label:'DATE DELETED',
        class:"px-6 py-2 font-semibold w-1/5"
    },
    {
        label:'',
        class:"w-1/8 rounded-tr-xl"
    }
])

    

const changeTab = (name)=>{  
    localStorage.setItem('currentTab',name)
    currentTab.value = name; 
}

//component,variable,action,index
const showModal = (table, modal, method, index = -1,tab)=> {

    console.log(table, modal,method,index)

    modals.value[table][modal] = true;
    modals.value[table].method = method
    modals.value[table].type = tab
    console.log(modals)
    if (index >= 0) {
        modals.value[table].data = assign(props[tab].data[index])
    }else {
        modals.value[table].data = null;
    }  

    console.log(modals)

}



const closeModal = (table, modal) => {
    modals.value[table][modal] = false
    modals.value[table].data = null
}


onMounted(()=>{
    console.log('mounted')
    if(!localStorage.getItem('currentTab')){
        localStorage.setItem('currentTab', 'Projects');
        currentTab.value = 'Projects'
    }else{

         currentTab.value = localStorage.getItem('currentTab')
    }
        
})


const currentTabComponent = computed(()=>{
   
    switch(currentTab.value){
        case "Projects":
            return Projects;
            break;
        case "Questionnaires":
            return Questionnaires;
            break;
        case "Questions":
            return Questions;
            break;
    }
    
})

const digIn = () => {
    form.name = searchQ.value
    form.type = localStorage.getItem('currentTab')

    form.get(route('trash.show',[searchQ.value]),
        {
            preserveScroll: true,
            onSuccess: () => { 
                
            },
            onError: () => {
                //code
                
            },
            onFinish: () => {
                //code
            }
        }) 
        console.log(searchQ.value)
}



///provide variables and methods to child components
provide('showModal',showModal)
provide('headers',headers)


</script>
<template>
    <app-layout title="Trash"> 
        <template #topbar>
            
        <div class="flex mt-3 mb-2 items-center justify-between flex-row-reverse"> 
            <div class="flex gap-2 flex-row-reverse">
                <div class="hidden space-x-8 sm:-my-px sm:flex" v-for="(value,key) in tab" :key="key" >
                <div class="cursor-pointer p-2" :class="currentTab === value ? 'border-b-4 border-ssr-blue2 bg-gray-50' : 'text-gray-500 bg-white hover:bg-ssr-transparent hover:rounded-lg'" @click="changeTab(value)">
                    {{value}}
                </div>   
            </div>
            </div>    
             <div class="">
                <form class="group relative" @submit="digIn" onsubmit="event.preventDefault();">
                    <svg width="20" height="20" fill="#c0c0c0" class="absolute left-3 top-1/2 -mt-2 text-slate-400 pointer-events-none group-focus-within:text-blue-500" aria-hidden="true">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" />
                    </svg>
                    <s4-input color="ssrGray" type="text" class="mt-1 block text-sm w-60 py-2 pl-10" placeholder="Search name" v-model="searchQ"/>
                </form>
            </div>           
        </div>
        </template>
        <template #content>
            <s4-loading v-show="loading" :label="messageLoading" :color="'text-white'" class="h-screen z-50 overflow-hidden bg-gray-700"></s4-loading>
            <component :is="currentTabComponent" ref="analysis" @updateList="updateList"></component>
            <trash-modal :modal="modals.trash" @updateList="updateList"  @close="closeModal('trash', 'delete_restore')" />
        </template>
        
    </app-layout>
</template>