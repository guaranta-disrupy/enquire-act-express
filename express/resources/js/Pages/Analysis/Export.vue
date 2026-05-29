<script setup>   
import { JetLabel, JetCheckbox } from '@/Jetstream'
import { faTrashAlt, faDownload } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { S4Input, S4TextArea, S4InputSelect, S4Checkbox, S4Button, S4DropDown, VueFormMultiselect, S4Table } from '@/S4';
import DeleteExportModal from './modal/DeleteExport.vue';
import { computed, onBeforeMount, onMounted, ref, watch } from 'vue';
import { useForm, usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js'

const { assign, toast, moment } = helpers()
const props = defineProps(['files','questions','folder_id'])
const show_CTE = ref(false)
const show = ref(true)
const reportOption = ref({"value":"Disaplay as Numbers", "text":"Display as Text"})
const crossTabExport = ref(["None","Export data on this questionnaire","Export from different questionnaire"])
const reportType =  ref({".xlsx": "XLS format (.xlsx)",".csv":"CSV format (.csv)"})
const selectedFilter = ref('0')
const faIcon = ref({ 
    trash: faTrashAlt, 
    download:faDownload
})
const crosstabSettings = ref({
    qVariable:'',
    qValue:'',
    qHeader:'',
})
const multi = ref({
    value : [],
    options: props.questions,
})
const multiOption = ref([])
const mergedList = ref([])
const exportedData = ref([{eFile:'All Sample.xlsx',eName:"Osmer U. Vinarao", eDate:"Jun 30, 2021 1:17 PM"}])
const thName = ref(["File Name","Time","Action"])
const selectAll = ref(0)
const form =  useForm({
    // display : 1,
    uuid : '',
    qvariable:'',
    qvalue:'',
    tabheader:'',
    filter_name:'',
    setup: {
        name : '',
        display: 'value',
        extension : '.xlsx',
        export_filter: 1,
        filters : [],
        merge:[],
        includeID: '',
    },
    answer_key:1
}, { 
    resetOnSuccess: true,
})

const modals = ref({
    export: { 
        delete: false,
        method: 'delete',
        data: null
    }
})
const filters = ref([])



const mergeChoices = computed(() => {
    let list = []
    for (const key in usePage().props.questionnaires) {
        if (usePage().props.questionnaire.code != usePage().props.questionnaires[key].code) {
            const val = usePage().props.questionnaires[key].code
            let data = {
                value: val,
                label: usePage().props.questionnaires[key].name,
            }
            
            list.push({...data}) 
        }
    }
    return list
})

onMounted(()=>{
    multiOption.value = props.questions;
})

const showModal = (table, modal, method, index = -1) => { 
    modals.value[table][modal] = true;
    modals.value[table].method = method
    if (index >= 0) {
        modals.value[table].data = assign(usePage().props.exports[index])
    }else {
        modals.value[table].data = null;
    }   
}
const closeModal = (table, modal) => {
    modals.value[table][modal] = false
    modals.value[table].data = null
}
const exportData = () => { 
    if(form.setup.filters.length === 0 && form.setup.export_filter == 1){
        toast.warning(`Empty filter`, {
                timeout: 2000
            })
        return;
    }
    form.post(route('exports.store', [usePage().props.questionnaire.code]),{
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Exports added`, {
                timeout: 2000
            })
        },
        onError: () => {
            
        },
        onFinish: () => {
            // this.form.reset()
        }
    })
}
const selectFilters = (selected, id)=> {
    
    let index_in_filters = form.setup.filters.indexOf(id)
  
    if(selected === 1 &&  index_in_filters == -1)
        form.setup.filters.push(id)
    else if(selected === 0 && index_in_filters > -1)
        form.setup.filters.splice(index_in_filters,1)

    // console.log(form.setup.filters)
}
const selectAllFilters = () => {
    form.setup.filters = []
    filters.value = []
    if (selectAll.value == 1) {
            usePage().props.filters.forEach((filter,index) => {
                selectFilters(1,filter.id)
                filters.value[index] = 1
            // form.setup.filters.push(filter.id)
        })
    }
}

watch(
    ()=> form.setup.filters,
    (newVal) => {
        // console.log(newVal)
        if (newVal.length != usePage().props.filters.length) {
            selectAll.value = 0
        }else {
            selectAll.value = 1
        }
    },
)

const headers = ref([
    {
        label:'EXPORT DATA',
        class:"pl-6 py-5 font-semibold w-1/3 rounded-tl-xl"
    }
])

const headers2 = ref([
    {
        label:'FILE NAME',
        class:"pl-6 py-5 font-semibold w-5/12 rounded-tl-xl"
    },
    {
        label:'CREATED AT',
        class:"pl-6 py-5 font-semibold w-4/12 rounded-tl-xl"
    },
    {
        label:'',
        class:"pl-6 py-5 font-semibold w-3/12 rounded-tl-xl"
    }
])

</script>


<template>
    <div class="flex containerBox mt-3 w-full">

        <s4-table :headers="headers" class="mt-3 w-4/12">
                <template #body>

                    <tr class="flex">
                        <div class="ml-5 mt-4">
                            <jet-label class="font-bold text-xs mb-1">Report Type:</jet-label>
                            <s4-drop-down :list="reportType" v-model:value="form.setup.extension" class="text-xs"></s4-drop-down>
                        </div>
                        <div class="ml-5 mt-4">
                            <jet-label class="font-bold text-xs mb-1">Report Options:</jet-label>
                            <s4-drop-down :list="reportOption" v-model:value="form.setup.display" class="text-xs"></s4-drop-down>
                        </div>
                    </tr>
                    <tr class="">
                        <div class="mx-5 mt-4">
                            <jet-label class="font-bold text-xs mb-1">Merge report:</jet-label>
                            <vue-form-multiselect :options="mergeChoices" placeholder="Click to select questionnaire" v-model="form.setup.merge"/>
                        </div>
                    </tr>
                    <tr class="flex">
                        <div class="ml-5 mt-4">
                            <jet-label class="font-bold text-xs mb-1">Export by:</jet-label>
                            <s4-drop-down :list="['ID', 'Filter', 'All Data']" v-model:value="form.setup.export_filter" class="text-xs w-40"></s4-drop-down>
                        </div>
                        <div class="ml-5 mt-4 grid grid-1 content-center">
                            <div>
                                <s4-checkbox class="cursor-pointer" v-model:checked="form.answer_key" />
                                <span class="ml-2 text-gray-600 select-none font-bold text-xs">Answer Keys</span>
                            </div>
                        </div>
                    </tr>
                    <tr v-if="form.setup.export_filter == 0" class="">
                        <div class="mx-5 mt-4 border-t border-slate-200">
                            <jet-label class="font-bold text-xs mb-1 mt-3">File Name:</jet-label>
                            <s4-input color="ssrGray" placeholder="Enter name" class="mt-1 block w-full text-xs" v-model="form.setup.name"></s4-input>
                            <jet-label class="font-bold text-xs mb-1 mt-5">Enter ID:</jet-label>
                            <s4-text-area color="ssrGray" class="w-full h-28 text-xs" 
                                v-model="form.setup.includeID" 
                                placeholder="Enter Participant ID and use comma(, for multiple criteria)" >
                            </s4-text-area>
                        </div>
                    </tr>
                    <tr v-else-if="form.setup.export_filter == 1">
                        <div class="mx-5 my-4 border-t border-slate-200 max-h-72 overflow-y-scroll">
                            <jet-label class="font-bold text-xs my-3">Filters:</jet-label>
                            <label class="items-center cursor-pointer">
                                <s4-checkbox class="cursor-pointer" @change="selectAllFilters()" v-model:checked="selectAll" />
                                <span class="ml-2 text-sm text-gray-600 select-none">Select All</span>
                            </label>
                            <div class="grid grid-cols-6 gap-1">
                                <template v-for="(filter, index) in $page.props.filters" :key="index">
                                    <div class="col-span-6">
                                        <label class="items-center cursor-pointer">
                                            <s4-checkbox class="cursor-pointer" v-model:checked="filters[index]" @change="selectFilters(filters[index],filter.id)"/>
                                            <span class="ml-2 text-sm text-gray-600 select-none">{{ filter.name }}</span>
                                        </label>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </tr>
                    <tr>
                        <div class="mx-5 mt-6 mb-3">
                            <s4-button size="md" class="drop-shadow-lg z-10" color="" @click="exportData()" :theme="'ssrOrange'">
                                <span class="pl-3 pr-3">EXPORT DATA</span>
                            </s4-button>
                        </div>
                    </tr>

                </template>
        </s4-table>  
        
        <s4-table :headers="headers2" class="mt-3 ml-3 w-8/12 overflow-auto" style="max-height: 55vmin;">
            <template #body>
                <tr class="text-md border-b border-l border-r border-gray-100 text-slate-500 hover:bg-slate-50" v-for="(item, index) in $page.props.exports" :key="index"> 
                    <td class="px-5 text-left overflow-auto">
                        <div class="flex items-center">{{ item.name + item.setup.extension }}</div>
                    </td>
                    <td class="pl-5"><div class="flex items-center">{{moment(new Date(item.created_at)).format('MM/DD/YY LT')}}</div> </td>
                    <td style="border-top-right-radius:1rem!important; border-bottom-right-radius:1rem!important;" class="flex flex-row-reverse pr-5 py-3 text-slate-400">

                        <a @click="showModal('export', 'delete', 'delete', index)" class="hover:text-slate-800" href="#">Delete</a>

                        <a :href="route('exports.download',[$page.props.questionnaire.code, item.id])" class="hover:text-slate-800 hover:underline mr-5" href="#">Download</a>
                        
                    </td>
                </tr>
            </template>
        </s4-table>

        <!-- <div class="h-5/6 max-w-md w-full max-w-xs bg-white rounded-lg p-5">
            <div class=""> 
                <div class="-mt-5 -ml-5 flex items-center flex-row">	
                    <div class="">
                        <span class="flex justify-center font-semibold text-gray-500 bg-ssr-orange3 rounded-sm QQ">
                            Export Data 
                        </span>
                    </div>		
                </div>

                <div class="grid grid-cols-6 gap-1 mt-5">
                    <div class="col-span-6">
                        <jet-label class="font-bold text-xs">Report Type:</jet-label>
                        <s4-drop-down :list="reportType" v-model:value="form.setup.extension"></s4-drop-down>
                    </div>
                    <div class="col-span-6">
                        <jet-label>Report Options:</jet-label>
                        <s4-drop-down :list="reportOption" v-model:value="form.setup.display"></s4-drop-down>
                    </div>
            
                    <div class="col-span-6">
                        <jet-label class="font-bold text-xs">Merge report:</jet-label>
                        <vue-form-multiselect 
                            size="min-h-32"
                            placeholder="Click to select questionnaire"
                            :options="mergeChoices"
                            v-model="form.setup.merge"
                            />
                    </div>
                    <div class="col-span-6">
                        <jet-label class="font-bold text-xs">Export by:</jet-label>
                        <s4-drop-down :list="['ID', 'Filter', 'All Data']" v-model:value="form.setup.export_filter"></s4-drop-down>
                    </div>
                    <div class="col-span-6">
                        <s4-checkbox class="cursor-pointer" v-model:checked="form.answer_key" />
                        <span class="ml-2 text-gray-600 select-none font-bold text-sm">Answer Keys</span>
                        
                    </div>  
                    <div class="col-span-6 mt-5" v-if="form.setup.export_filter == 0">
                        <jet-label class="font-bold text-xs">File Name:</jet-label>
                        <s4-input color="ssrGray" placeholder="Enter name" class="mt-1 block w-full" v-model="form.setup.name"></s4-input>
                        <jet-label class="font-bold text-xs mt-5">Enter ID:</jet-label>
                        <s4-text-area color="ssrGray" class="w-full h-28" 
                            v-model="form.setup.includeID" 
                            placeholder="Enter Participant ID and use comma(, for multiple criteria)" >
                        </s4-text-area>
                    </div>
                    <div class="col-span-6 mt-5" v-else-if="form.setup.export_filter == 1">
                        <jet-label class="font-bold text-xs">Filters:</jet-label>
                        <label class="items-center cursor-pointer">
                            <s4-checkbox class="cursor-pointer" @change="selectAllFilters()" v-model:checked="selectAll" />
                            <span class="ml-2 text-sm text-gray-600 select-none">Select All</span>
                        </label>
                        <div class="grid grid-cols-6 gap-1">
                            <template v-for="(filter, index) in $page.props.filters" :key="index">
                                <div class="col-span-6">
                                    <label class="items-center cursor-pointer">
                                        <s4-checkbox class="cursor-pointer" v-model:checked="filters[index]" @change="selectFilters(filters[index],filter.id)"/>
                                        <span class="ml-2 text-sm text-gray-600 select-none">{{ filter.name }}</span>
                                    </label>
                                </div>
                            </template>
                        </div>
                    </div>   
                    <div class="col-span-6" v-if="form.setup.export_filter == 2"></div>   
                </div>
            </div> 
            <div class="mt-5 text-center">
                <div class="float-left w-full text-center">
                    <p
                        class="expBtn w-full text-center"
                        :disabled="form.processing"
                        @click="exportData()"
                        >
                        Export Data                         
                    </p>
                </div>
            </div>
        </div>  -->


        <!-- <div class="bg-white ml-3 w-full max-w-6xl p-5 rounded-lg min-h-4/5 reports">
            <div class="-mt-5 -ml-5 flex items-center flex-row">	
                <div class="">
                    <span class="flex justify-center font-semibold text-gray-500 bg-ssr-orange3 rounded-sm QQ">
                        Reports
                    </span>
                </div>		
            </div>

            <div class="mt-6 rounded-lg">
                <table class="min-w-full leading-normal sm:rounded-lg">
                        <thead class="rounded-lg" v-if="$page.props.exports.length != 0"> 
                      

                            <tr class="thN">
                                <th class="py-2 px-3 text-left font-bold uppercase text-gray-600 hidden lg:table-cell" :class="key === 2 ? 'text-right pr-5' : ''" v-for="(value,key) in thName" :key="key">{{value}}
                                </th>
                            </tr>
                        </thead>
                        
                        <tbody class="text-gray-600 text-sm font-light">
                        <tr v-if="$page.props.exports.length == 0">
                            No records found
                        </tr>
                        <tr v-else class="rep rounded-lg border-b border-gray-200 hover:bg-gray-50" v-for="(item, index) in $page.props.exports" :key="index"> 
                                <td class="px-3 text-left overflow-auto text-gray-600 hover:text-ssr-blue2 cursor-pointer hover:underline">
                                    <a :href="route('exports.download', 
                                        [$page.props.questionnaire.code, item.id])">
                                        <div class="flex items-center">{{ item.name + item.setup.extension }}</div>
                                    </a>
                                </td>
                                <td class="text-left pl-3"><div class="flex items-center">{{moment(new Date(item.created_at)).format('MM/DD/YY LT')}}</div> </td>
                                <td style="border-top-right-radius:1rem!important; border-bottom-right-radius:1rem!important;" class="flex flex-row-reverse pr-3 py-2">


                                    <div class="relative prjBtnDel">
                                        <s4-button size="sm" color="outline-transparent" class="ml-3 bt hover:border-ssr-red2"
                                        @click="showModal('export', 'delete', 'delete', index)">
                                            <font-awesome-icon :icon="faIcon.trash" class="w-5 h-5 fill-current text-gray-400 hover:text-ssr-red2 ft" />
                                        </s4-button>
                                        <span class="toolTipDel absolute -right-7 px-2 py-1 text-md text-white leading-none transform bg-gray-600 rounded-md h-5 pb-1 pt-1 pl-5 pr-5 -mt-6">
                                            Delete
                                        </span> 
                                    </div>


                                    <div class="relative prjBtnDL">
                                        <a :href="route('exports.download', 
                                        [$page.props.questionnaire.code, item.id])" class="">
                                            <s4-button size="sm" color="outline-transparent" class="ml-3 bt hover:border-green-500">
                                                <font-awesome-icon :icon="faIcon.download" class="w-5 h-5 fill-current text-gray-400 hover:text-green-500 ft" />
                                            </s4-button>
                                        </a>
                                        <span class="toolTipDL absolute -right-8 px-2 py-1 text-md text-white leading-none transform bg-gray-600 rounded-md h-5 pb-1 pt-1 pl-5 pr-5 -mt-6">
                                            Download
                                        </span> 
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                </table>
            </div>
        </div>  -->
        <delete-export-modal :modal="modals.export" @close="closeModal('export', 'delete')" />
    </div>
</template>


<style scoped>

@media (max-width: 880px) {  
        .containerBox{
            display: flex; 
            flex-wrap: wrap; 
            justify-content: center;
            max-width: 100%;
            flex-direction: column;
        }
        .reports{ 
            margin: auto;
            margin-top:5px;
          
        }
  }
.QQ {
    padding-left: 1.5rem;
    padding-right: 2rem;
    border-bottom-right-radius: 1rem;
    font-size:1rem;
}

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
.expBtn {
    background-color: #F0F8FF;
    color:rgb(87, 87, 87);
    padding:0.5rem 1rem;
    margin:unset;
    margin-top: 2rem;
    cursor: pointer;
    border-radius: 0.4rem;
    font-weight: bold;
}

.expBtn:hover {
    background-color: #E3F0FB;
}

/*tooltip delete*/
.toolTipDel {
    opacity: 0;
    transition: 0.3s;
}
.prjBtnDel:hover > .toolTipDel {
    opacity: 1;
}

/*tooltip download*/
.toolTipDL {
    opacity: 0;
    transition: 0.3s;
}
.prjBtnDL:hover > .toolTipDL {
    opacity: 1;
}

</style>