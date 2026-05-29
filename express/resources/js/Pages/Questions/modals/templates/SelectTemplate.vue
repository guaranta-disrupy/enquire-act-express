<script setup>
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import JetLabel from '@/Jetstream/Label.vue';
import S4Button from '@/S4/Button.vue';
import S4Input from '@/S4/Input.vue';
import S4Checkbox from '@/S4/Checkbox.vue'; 

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEdit, faSpinner, faSave, faTrashAlt } from '@fortawesome/free-solid-svg-icons'
import { computed, ref } from 'vue';
import { useForm, usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js'

const {  toast } = helpers()

const emits = defineEmits(["close"])
const props = defineProps({
        modal:{
            default:{
                select_template:false,
                method:'save',
                data:null,
            }
        },
    })
const faIcon = ref({
                save: faSave,
                spinner: faSpinner,
                edit: faEdit,
                trash: faTrashAlt
            })
            
const form = useForm({ 
                q_data:{},
            }, {
                resetOnSuccess:true,
            })
const radio = computed(()=>{ "rounded-lg border-2 border-ssr-blue2 text-ssr-blue2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed focus:border-ssr-blue2 focus:ring-0 focus:ring-offset-0";})  

const close = () => {
            form.reset()
            emits('close')
        }
        
const selectTemplate = () => {  
    if(Object.keys(form.q_data).length === 0){
        toast.warning(`Please select template.`,{timeout:2000});
        return;
    }  
   
    let params = [
        usePage().props.questionnaire.code
    ];
    var web_route = 'questions.usetemplate'
    form.post(route(web_route, params), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Template added`, {
                timeout: 2000
            })
            close()
        },
        onError: () => {
            //code
        },
        onFinish: () => {
            //code
        }
    })
}
</script>

<template>
    <jet-dialog-modal :show="props.modal.select_template" maxWidth="7xl" @close="close()">
        <template #title>
            <span>Templates</span>
        </template>

        <template #content>
            <div class="col-span-6" v-for="(template,tindex) in $page.props.question_templates" :key="tindex">
                <div class="flex">
                    <span class="mt-3 flex-none">{{tindex+1}}</span>
                    <label class="hover:bg-gray-200 radio-con col-span-6 bg-gray-100 mb-2 ml-1 p-3 text-sm rounded-2xl flex-1 w-64">
                        <div class="radio-in">
                            <input type="radio" :class="radio" :value="template" v-model="form.q_data">
                        </div>
                        <!-- <s4-checkbox :class="{'rounded-lg': template.setup.selectMultiple == 0}" :value="template" v-model="form.q_data" @click="setTempValue()" /> -->
                        <div class="ml-2 text-gray-600 select-none font-bold text-sm anti-lapas"><strong>{{template.name}}</strong></div>
                        <!-- <div class="ml-2 text-gray-600 select-none font-bold text-sm" v-html="template.question"></div>  -->
                    </label> 
                    <!-- <div class="md:items-center">
                        <s4-button color="ssrRedIcon" class="mb-1 ml-1" @click="deleteChoice(index)">
                        <font-awesome-icon class="fa-w-20 fa-h-20" :icon="faIcon.trash" />
                        </s4-button>
                    </div> -->
                </div>
                <!-- <jet-label></jet-label> -->

            </div>
        </template>

        <template #footer>
            <s4-button :theme="'ssrCancel'" color="ssrGray" class="mr-3" @click="close()">
                Cancel
            </s4-button>
            <s4-button :theme="'ssrOrange'" :disabled="form.processing" @click="selectTemplate()">
                <template v-if="form.processing">
                    <span class="pl-3 pr-3"><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span>
                </template>
                <template v-else>
                    <span class="pl-3 pr-3">
                        Confirm
                    </span>
                </template>
            </s4-button>
        </template>
    </jet-dialog-modal>
</template>




<style scoped>
    .radio-con{
        position: relative;
        padding-left: 32px;
        cursor: pointer;
    }

    .radio-con .radio-in{
       position: absolute;
       top: 10px;
       left: 14px;
       transform: scale(1.3);
    }

    .radio-con .anti-lapas{
        overflow: hidden;
        text-overflow: ellipsis;
        /* word-break: break-all; */
    }
</style>