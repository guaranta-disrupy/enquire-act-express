<script setup>
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import S4Button from '@/S4/Button.vue';
import S4Input from '@/S4/Input.vue';
import JetInputError from '@/Jetstream/InputError.vue';
import JetLabel from '@/Jetstream/Label.vue';
import S4TextArea from '@/S4/TextArea.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faDownload } from '@fortawesome/free-solid-svg-icons';
import { ref } from 'vue';
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
        modal: {
            default:{
                add_edit: false,
                method: 'add',
                data: null
            }
        }
    })
const emit = defineEmits(['close'])
    
const faIcon = ref({
    download: faDownload,  
})
const form = useForm({
    filterName: '',
    filterDescription: '',
    filterURL:'',
    filterIds:''
}, { 
    resetOnSuccess: true,
})
const colors = ref(null)

const close = () => { 
    emit('close')
}

</script>

<template>
    <jet-dialog-modal :show="modal.add_edit" @close="close()"> 
        <template #content>
            
            <div class="grid grid-cols-6 gap-1">
                <div class="col-span-6 mx-auto mb-5"> 
                    <span class="text-lg font-semibold" >Quick Summary</span>
                </div>  
                <div class="col-span-6">
                    <table class="min-w-full leading-normal sm:rounded-lg">
                        <thead> 
                            <!--<tr class="bg-gray-100">
                                <th class="py-3 px-6 text-left font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell">Type</th>
                                <th class="py-3 px-6 text-left font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell">Value</th>
                            </tr>-->

                            <tr class="thN">
                                <th class="py-2 px-3 text-left font-bold uppercase text-gray-600 hidden lg:table-cell">
                                    Type
                                </th>
                                <th class="py-2 px-3 text-left font-bold uppercase text-gray-600 hidden lg:table-cell">
                                    Value
                                </th>
                            </tr>
                        </thead> 
                        <tbody class="text-gray-600 text-sm font-light">
                        <tr class="bg-white border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-3 text-left whitespace-nowrap ">Filter</td>
                            <td class="py-3 px-3 text-left whitespace-nowrap ">Ageing Millennials CHANEL</td>
                        </tr>
                        <tr class="bg-white border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-3 text-left whitespace-nowrap ">Report Type</td>
                            <td class="py-3 px-3 text-left whitespace-nowrap ">xlsx</td>
                        </tr>
                        <tr class="bg-white border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-3 text-left whitespace-nowrap "> Report Name </td>
                            <td class="py-3 px-3 text-left whitespace-nowrap ">Completes</td>
                        </tr>
                        <tr class="bg-white border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-3 text-left whitespace-nowrap "> Report Option </td>
                            <td class="py-3 px-3 text-left whitespace-nowrap ">Disaplay as a Numbers</td>
                        </tr>
                        <tr class="bg-white border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-3 text-left whitespace-nowrap ">Merge report</td>
                            <td class="py-3 px-3 text-left whitespace-nowrap ">None</td>
                        </tr>
                        <tr class="bg-white border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-3 text-left whitespace-nowrap ">Cross tab export</td>
                            <td class="py-3 px-3 text-left whitespace-nowrap ">None</td>
                        </tr>
                        <tr class="bg-white border-b border-gray-200 hover:bg-gray-100">
                            <td class="py-3 px-3 text-left whitespace-nowrap ">Total Respondent</td>
                            <td class="py-3 px-3 text-left whitespace-nowrap ">1250</td>
                        </tr>
                        </tbody>
                </table>
                </div>
            </div> 
        </template>
        <template #footer>
            <div class="text-center flex flex-row-reverse">
                
                <s4-button :theme="'ssrOrange'" class="mr-1" @click="close()"> 
                        <span class="pr-3 pl-3">
                            <!--<font-awesome-icon :icon="faIcon.download" @click="close()"/>-->
                            Download
                        </span>
                </s4-button>

                <s4-button :theme="'ssrCancel'" class="mr-1" @click="close()">
                    Cancel
                </s4-button>
            </div>
        </template>
    </jet-dialog-modal>
</template>


<style scoped>
.thN {
    background-color: #F0F8FF;
}
</style>