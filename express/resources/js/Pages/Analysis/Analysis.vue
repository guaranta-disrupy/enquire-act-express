<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { S4Button, S4Input, S4MultiSelect, S4Loading } from '@/S4';
import { Export, Summary, Filter, AddEditDeleteFilterModal,ExportSummary  } from  '@/Pages/Analysis/index.js'
import { computed, ref } from 'vue';
import { useForm, usePage } from "@inertiajs/vue3";

const tab = ref(["Filter","Export","Summary"])
const currentTab = ref("Export")
const filterData = ref(['Filter 1','Filter 2','1 Ageing Millennials CHANEL'])
const selectFilter = ref(null)
const modals = ref({
    filter: { 
        add_edit: false,
        method: 'add',
        data: null
    }
})
const modalsSummary = ref({
    summary: { 
        add_edit: false,
        method: 'add',
        data: null
    }
})

const show = ref(false)
const reportOption = ref(["Disaplay as Numbers","Disaplay as Text","Disaplay as Equivalent","Disaplay as Computed by Count","Disaplay as Computed by Order","Disaplay as Computed by RT"])
const crossTabExport = ref(["None","Export data on this questionnaire","Export from different questionnaire"])
const reportType  = ref(["XLS format (.xlsx)"])
const selectedReportOption = ref(null)
const selectedCrosTabExport = ref('0')
const selectReportType = ref(null)
const testSelected = ref([])
const multiOption = ref([{name:'Option 1',selected:false},{name:'Option 2',selected:false},{name:'Option 3',selected:false}])
const loading = ref(false)
const messageLoading = ref('Exporting Data')
const answerCount = ref(null)
const form = useForm({})

const changeTab = (name)=>{  
    currentTab.value = name; 
}

const closeModal = (table, modal) =>{
    modals.value[table][modal] = false
    modals.value[table].data = null
}
const showExportData = () => {
    if(!!selectFilter.value)
        show.value = show.value == true ? false : true;
    else alert('Select filter first')
}
const download = (table)=>{
    loading.value = false
    modalsSummary.value[table].add_edit = true; 
}
const closeSummaryModal = (table)=>{ 
    modalsSummary.value[table].add_edit = false
}
const exportData = ()=>{
    loading.value = true;
    setTimeout(() =>  download('summary'), 2000); 
}

const currentTabComponent = computed(()=>{
    switch(currentTab.value){
        case "Filter":
            return Filter;
            break;
        case "Export":
            return Export;
            break;
        case "Summary":
            return Summary;
            break;
    }
    
})
const addFilter = computed(()=> "relative inline-flex items-center px-4 py-2 bg-blue-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-500 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:shadow-outline-yellow transition ease-in-out duration-150")
</script>

<template>
    <main-layout title="Analysis"> 

        <template #header>
            <!-- <div class="flex items-center flex-row mt-3" style="background-color: #F3F4F6;">
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex" v-for="(value,key) in tab" :key="key" >
                    <div class="cursor-pointer" :class="currentTab === value ? 'classes1' : 'classes2'" @click="changeTab(value)">
                        {{value}}
                    </div>   
                </div>               
            </div> -->
        </template>

        <template #content>
            <div class="flex items-center flex-row mb-0 pl-3">
                <div class="hidden space-x-8 sm:-my-px sm:mr-12 sm:flex text-sm text-slate-400" v-for="(value,key) in tab" :key="key" >
                    <div class="cursor-pointer flex" :class="currentTab === value ? 'classes1 font-semibold text-blue-400' : 'classes2 font-semibold'" @click="changeTab(value)">
                        {{value}}                        

                        <div v-if="value != 'Summary'">
                            <div v-if="value == 'Filter'" :class="currentTab === value ? 'text-blue-400 bg-blue-100' : 'text-gray-800'" class="ml-3 text-xs px-3 py-0.5 bg-slate-100 rounded-full">{{$page.props.filters.length}}</div>
                            <div v-if="value == 'Export'" :class="currentTab === value ? 'text-blue-400 bg-blue-100' : 'text-gray-800'" class="ml-3 text-xs px-3 py-0.5 bg-slate-100 rounded-full">{{$page.props.exports.length}}</div>
                        </div>
                    </div>                    
                </div>               
            </div>
            <s4-loading v-show="loading" :label="messageLoading" :color="'text-white'" class="h-screen z-50 overflow-hidden bg-gray-700"></s4-loading>
            <component :is="currentTabComponent" ref="analysis"></component>
        </template> 
        <template #modals>
            <add-edit-delete-filter-modal :modal="modals.filter" @close="closeModal('filter', 'add_edit')" />
            <ExportSummary :modal="modalsSummary.summary" @close="closeSummaryModal('summary')"></ExportSummary>
        </template>
    </main-layout>
</template>

<style scoped>
.slide-fade-enter-active {
   transition: all .3s ease-in;  
   opacity: 0;
   position: relative;
   left: 60px;  
}

.slide-fade-leave-active {
  transition: all .3s cubic-bezier(1.0, 0.5, 0.8, 1.0);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateX(20px);
  opacity: 0;
}

/* .classes1 {
    font-weight: 700;
    color: #1e71b8;
}

.classes2 {
    font-weight: bold;
    color: rgb(136, 135, 135);
} */

.sumFilter {
    background-color: #F6FAFF;
}

.QQ {
    padding-left: 1.5rem;
    padding-right: 2rem;
    border-bottom-right-radius: 1rem;
    font-size:1rem;
}
 
</style>