<script setup>
import { JetInputError, JetDialogModal }  from '@/Jetstream/'
import { S4Button, S4Input, S4Loading } from '@/S4'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSpinner } from '@fortawesome/free-solid-svg-icons';
import {  watch, ref } from 'vue';
import { useForm  } from "@inertiajs/vue3"
import helpers from '@/helpers.js'

const { assign, toast } = helpers()

const props =  defineProps({
    modal : {
        type: Object,
        default : {
            delete_restore: false,
            method: 'restore',
            data: null,
            type : 'projects'
        }
    },

});

const form = useForm({
    name: '',
    details: '',
    code:''
    }, { 
    resetOnSuccess: true
});


const emit = defineEmits(['close', 'updateList']);

const colors = ref("blue");
const loading = ref(false);
const messageLoading = ref(null);
const faIcon = ref({
    spinner: faSpinner
});

watch(
    ()=> props.modal,
    (modal) => {
        console.log(modal)
        // if(modal.method == 'restore' && modal.restore == true)
            modal.delete_restore == true
        if (modal.method == 'edit' && modal.add_edit == true) {
            form.name = assign(modal.data.name) 
            form.details = assign(modal.data.details)
        }
        if (modal.method == 'copy' && modal.add_edit == true) {
            form.name = assign(modal.data.name) + ' COPY'
            form.details = assign(modal.data.details) + ' COPY'
        } 
        
        colors.value = modal.method == 'delete' ? 'red' : 'blue';//change color button
        console.log(colors)
    },
    { deep: true }
)
 const close = () => {
    form.reset('name')
    form.reset('details')
    emit('close')
    loading.value = false;
}

const restoreDelete = () =>{
    let params = {}
    let id_code = props.modal.type == 'questions' ? props.modal.data.id : props.modal.data.code
    loading.value = true;
    form.processing = true;    
    switch(props.modal.method){
        case "restore":
            params = { id: id_code , type : props.modal.type}
            messageLoading.value = `Restoring ${props.modal.type.charAt(0).toUpperCase() + props.modal.type.slice(1)}`;    
            form.put(route('trash.update',params),{
            preserveScroll: true,
            onSuccess: () => { 
                emit('updateList',props.modal.type)
                toast.warning(`${props.modal.type.charAt(0).toUpperCase() + props.modal.type.slice(1)} successfully restored`, {
                    timeout: 2000
                })
                close()
                form.processing = false; 
            },
            onError: () => {
                //code
                loading.value = false;
            },
            onFinish: () => {
                //code
            }
        }) 
            break;
        case "delete":
            params = { id:  id_code , type : props.modal.type}
            messageLoading.value = `Deleting ${props.modal.type.charAt(0).toUpperCase() + props.modal.type.slice(1)}`;    
            form.delete(route('trash.delete',params),{
                preserveScroll: true,
                onSuccess: () => { 
                    emit('updateList',props.modal.type)
                    toast.warning(`Project permanently deleted`, {
                        timeout: 2000
                    })
                    close()
                    form.processing = false; 
                },
                onError: () => {
                    //code
                    loading.value = false;
                },
                onFinish: () => {
                    //code
                }
            })
            break;
    }
    
    
    
}
</script>
<template>
    <jet-dialog-modal :show="modal.delete_restore" @close="close()">
        <template #title>
            <s4-loading v-show="loading?.value" :label="messageLoading?.value"></s4-loading>
        </template>
        <template #content> 
             <div class="col-span-6 text-lg md:container md:mx-auto px-12" >
                    Are you sure you want to {{ modal.method }} this <span class="text-ssr-blue2 font-semibold">{{modal.data.name ? modal.data.name : modal.data.question.replace(/<\/?[^>]+(>|$)/g, "").substring(0,10)+'...' }}</span> {{ modal.type.substr(0, modal.type.length - 1) }}?
            </div>
        </template>
        <template #footer>
            <s4-button :theme="'ssrCancel'" class="mr-1" @click="close()">
                Cancel
            </s4-button>
            <s4-button :theme="'ssrOrange'"  :color="colors.value" :disabled="form.processing" @click="restoreDelete()">
                <template v-if="form.processing">
                    <span class="pl-3 pr-3"><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span>
                </template>
                <template v-else-if="modal.method == 'restore'">
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.edit" />--> 
                        Restore
                    </span>
                </template>
                <template v-else-if="modal.method == 'delete'">
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.trash" />-->
                         Delete
                    </span>
                </template>
            </s4-button>
        </template>
    </jet-dialog-modal>
</template>
