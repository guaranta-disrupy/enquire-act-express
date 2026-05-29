<script setup>
    import { onMounted, ref } from 'vue';
    import { Link, router } from "@inertiajs/vue3";
    import AppLayout from '@/Layouts/AppLayout.vue'
    import S4Sidebar from '@/S4/Sidebar.vue'
    import S4Input from '@/S4/Input.vue'
    import S4InputSelect from '@/S4/InputSelect.vue'
    import S4Button from '@/S4/Button.vue'
    import S4Svg from '@/S4/SVG/Select.vue'
    import S4Pagination from '@/S4/Pagination.vue';
    import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
    import { faSpinner, faTrashAlt,faSync } from '@fortawesome/free-solid-svg-icons'
    import helpers from '@/helpers.js';
    import { S4Table } from '@/S4'

    const { moment } = helpers()

    const props = defineProps(['logs','actions']);
    const faIcon = ref({
                        spinner: faSpinner,
                        trash: faTrashAlt,
                        sync: faSync
                    });
    const sort = ref(null);
    const sort_by = ref(0);
    const list = ref([]);
    
    const updateLogs = () => {
        if(sort_by.value === "All"){
            list.value = props.logs.data
            return;
        }
                    
        list.value = props.logs.data.filter((o)=>{ return o.action.toLowerCase() === sort_by.value })
    };
     
    onMounted(()=>{
        list.value = props.logs?.data
            //fill sort variable
            sort.value = []
            sort.value.push( {text : 'ALL', value : 'All' })
            props.actions.forEach(element => {
                  sort.value.push( {text : element.toUpperCase(), value : element })
            });
    });

    const headers = ref([
        {
            label:'MESSAGE',
            class:"pl-6 py-5 font-semibold w-2/3 rounded-tl-xl"
        },
        {
            label:'ACTION',
            class:"pr-6 py-2 font-semibold w-1/6"
        },
        {
            label:'DATE',
            class:"px-6 py-2 font-semibold w-1/6"
        }
    ])
          
    
</script>

<template>
    <app-layout title="Logs"> 
        <template #topbar>
            <div>
                <div class="flex flex mt-3 mb-2">
                        <p class="float-right font-bold text-gray-700 prjText">
                            Sort by </p>
                            <s4-input-select  class="ml-3 form-select w-1/6 h-10"
                                @change="updateLogs"
                                :lists="sort"
                                v-model="sort_by"  
                                /> 
                </div>  
            </div>
        </template>
        <template #content>
            <S4Table :headers="headers">
                <template #body> 
                    <tr class="text-md border-b border-l border-r border-gray-100 text-slate-500 hover:bg-slate-50" v-for="(log,index) in list" :key="index">
                        <td class="px-6 py-4 truncate">
                            <span class="hover:text-gray-700 ">
                                <h1 class="text-ssr-orange2">Message</h1> {{ log.details }}
                            </span>
                        </td>
                        <td class="">
                            <span class="font-bold  hover:text-gray-700">
                                <h1 class="text-ssr-orange2">Action</h1><div class="uppercase">{{ log.action }}</div>
                            </span>
                        </td>
                        <td class="">
                            <span class="hover:text-gray-700">
                                <h1 class="text-ssr-orange2">Date</h1> {{ moment(new Date(log.created_at)).format('MM/DD/YY LT') }}
                            </span>
                        </td> 
                    </tr>        
                </template>
            </S4Table>  
            <s4-pagination :links="props.logs.links"/>
        </template>
    </app-layout>
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
    border-top:0.1rem solid #F3F4F6; 
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