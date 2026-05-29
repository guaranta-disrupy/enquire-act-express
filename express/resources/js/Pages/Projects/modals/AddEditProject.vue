<script setup>
import { JetInputError, JetDialogModal }  from '@/Jetstream/'
import { S4Button, S4Input, S4Loading } from '@/S4'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSpinner } from '@fortawesome/free-solid-svg-icons';
import {  watch, ref } from 'vue';
import { useForm  } from "@inertiajs/vue3"
import helpers from '@/helpers.js';
import IconsVue from '../../../S4/Icons.vue';

const { assign, toast } = helpers()

const props =  defineProps({
    modal : {
        type: Object,
        default : {
            add_edit: false,
            method: 'add',
            data: null 
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


const emit = defineEmits(['close']);

const colors = ref("blue");
const loading = ref(false);
const messageLoading = ref(null);
const faIcon = ref({
    spinner: faSpinner
});

watch(
    ()=> props.modal,
    (modal) => {
        
        if (modal.method == 'edit' && modal.add_edit == true) {
            form.name = assign(modal.data.name) 
            form.details = assign(modal.data.details)
        }
        if (modal.method == 'copy' && modal.add_edit == true) {
            form.name = assign(modal.data.name) + ' COPY'
            form.details = assign(modal.data.details) + ' COPY'
        } 
        
        colors.value = modal.method === 'delete' ? 'red' : 'blue';//change color button
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

const AddEditProject = () =>{
    loading.value = true;

    if(props.modal.method == 'delete'){ 
        messageLoading.value = 'Deleting Project';
        form.processing = true;
        form.delete(route('projects.destroy',props.modal.data.code),{
            preserveScroll: true,
            onSuccess: () => { 
                toast.warning(`Project successfully deleted`, {
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
    } else if(props.modal.method == 'copy'){ 
                form.code = props.modal.data.code
                form.post(route('projects.copy'), {
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.success(`Project replicated'}`, {
                            timeout: 2000
                        })
                        form.reset()
                        close();
                        
                    },
                    onError: () => {
                        //code 
                        loading.value = false;
                    },
                    onFinish: () => {
                        //code
                    }
                })
            }
    else{
        messageLoading.value = props.modal.method == 'add' ? 'Adding Project' : 'Updating Project'; 
        form.code = props.modal.method == 'edit' ? props.modal.data.code : null; //used form code to edit
        let web_route = props.modal.method == 'add' ? 'projects.store': 'projects.update'
        form.post(route(web_route), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Project successfully ${props.modal.method == 'add' ? 'added': 'updated'}`, {
                    timeout: 2000
                })
                form.reset('name')
                form.reset('details')
                close(); 

            },
            onError: () => {
                //code 
                loading.value = false;
            },
            onFinish: () => {
                //code
            }
    })
    }
    
    
    
}
</script>
<template>
    <jet-dialog-modal :show="modal.add_edit" @close="close()">
        <template #title>
            <s4-loading v-show="loading?.value" :label="messageLoading?.value"></s4-loading>
            <div class="mx-auto flex items-center mt-2 bg-slate-100  rounded-l-full rounded-r-lg">
                <img :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name" class="mx-0 relative w-12 h-12 rounded-full object-cover border">
                <div class="ml-4 leading-tight">
                    <div>{{ $page.props.auth.user.name }}</div>
                    <div class="text-gray-700 text-sm">
                        {{ $page.props.auth.user.email }}
                    </div>
                </div>
            </div>
        </template>
        <template #content> 
             <div class="col-span-6 text-lg md:container md:mx-auto border mx-2 rounded-lg mt-8 bg-slate-50 text-justify px-4 py-8" v-if="modal.method == 'delete'">
                <span class="text-red-500 inline-block mr-2"><IconsVue icon="delete"/></span>    Are you sure you want to delete this <span class="text-red-500 font-semibold inline"> {{modal.data.name}} </span> project ?
            </div>
            <div class="grid grid-cols-6 gap-1 md:container md:mx-auto px-14" v-else>
                <div class="col-span-6 mx-auto "> 
                    <span class="text-lg font-semibold text-xl" v-if="modal.method == 'add'">Add Project</span>
                    <span class="text-lg font-semibold text-xl"  v-if="modal.method == 'edit'">Update Project</span>
                    <span class="text-lg font-semibold text-xl"  v-if="modal.method == 'copy'">Copy Project</span>
                </div> 
                <div class="col-span-6">
                    <!-- <jet-label value="Project Name" />
                    <s4-input type="text" color="ssrGray" placeholder="Input Project Name" class="mt-1 block w-full"
                        v-model="form.name" /> -->
                    <s4-input theme="theme2" label="Project Name" v-model="form.name"></s4-input>
                    <jet-input-error :message="form.errors.name" color="ssrGray"/>
                    <!-- <jet-label value="Project Details" class="mt-5" /> -->
                    <!-- <s4-input type="text" color="ssrGray" placeholder="Input Project Details" class="mt-1 block w-full" 
                        v-model="form.details" /> -->
                    <s4-input theme="theme2" label="Project Details" v-model="form.details"></s4-input>
                    <jet-input-error :message="form.errors.details" color="ssrGray"/>
                </div> 
            </div> 
            <br/>
            <br/>
        </template>
      
        <template #footer>
            <s4-button :theme="'ssrCancel'" class="mr-1" @click="close()">
                Cancel
            </s4-button>
            <s4-button :theme="'ssrOrange'"  :color="colors.value" :disabled="form.processing" @click="AddEditProject()">
                <template v-if="form.processing">
                    <span class="pl-3 pr-3"><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span>
                </template>
                <template v-else-if="modal.method == 'edit'">
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.edit" />--> 
                        Update
                    </span>
                </template>
                <template v-else-if="modal.method == 'delete'">
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.trash" />-->
                         Delete
                    </span>
                </template>
                <template v-else-if="modal.method == 'copy'">
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.copy" />-->
                        Copy
                    </span>
                </template>
                <template v-else>
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.save" /> -->
                        Save
                    </span>
                </template>
            </s4-button>
        </template>
    </jet-dialog-modal>
</template>
