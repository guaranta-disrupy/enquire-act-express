<script setup>
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import S4Button from '@/S4/Button.vue';
import S4Input from '@/S4/Input.vue';
import JetInputError from '@/Jetstream/InputError.vue';
import JetLabel from '@/Jetstream/Label.vue';
import S4TextArea from '@/S4/TextArea.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faEdit, faSpinner, faSave, faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import S4Loading from '@/S4/Loading.vue';
import { onUpdated, ref, watch } from 'vue';
import { useForm, usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js'

const { assign, toast } = helpers()

const props = defineProps(['modal'])
const emit = defineEmits(['close','updates'])
const faIcon = ref({
    save: faSave,
    spinner: faSpinner,
    edit: faEdit,
    trash: faTrashAlt, 
})
const form = useForm({
    name:'',
    details:'',
    sourceUrl:'',
    filtered_ids:'',
    project_id:null
}, { 
    resetOnSuccess: true,
})
const colors = ref(null)
const loading = ref(false)
const messageLoading = ref('Saving Filter')

const close = () => {
    form.reset()
    emit('close');
    loading.value = false
}
const AddEditFilter = () => { 
    loading.value = true;
    if (props.modal.method == 'add') {
        var params = [
            usePage().props.project.code
        ]
    }else {
        var params = [
           usePage().props.project.code,
            props.modal.data.id
        ]
    }
    if(props.modal.method == 'delete'){ 
        form.delete(route('filters.destroy', params),{
            preserveScroll: true,
            onSuccess: () => { 
                toast.warning(`Filter successfully deleted`, {
                timeout: 2000
                })
                close();
            },
            onError: () => {
                //code
                
            },
            onFinish: () => {
                //code
                loading.value = false;
            }
        }) 
    } else{
        var web_route = props.modal.method == 'add' ? 'filters.store': 'filters.update';

        var numberOfLineBreaks = (form.filtered_ids.replace(/\n|\n,/g,','));
        // var numberOfLineBreaks = form.filtered_ids.replace(/[^\w\s]|_/g, '')
        //              .replace( /(&lt;([^>]+)>)/gi, ' ')
        //             .replace(/\s+/g, ' ')
        //             .replace(/\s+/g,",");
        form.filtered_ids = numberOfLineBreaks;
        console.log(form.filtered_ids); 
        form.post(route(web_route, params), {
            preserveScroll: true,
            onSuccess: () => {
                if(props.modal.method == 'edit')
                    emit('updates',props.modal.data.id)
                toast.success(`Filter ${props.modal.method == 'add' ? 'added': 'updated'}`, {
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
    
}



watch(
    () => props.modal,
    (modal)=>{
    console.log(modal)
        if (modal.method == 'edit' && modal.add_edit == true) {
            form.name = assign(modal.data.name) 
            form.details = assign(modal.data.details)
            form.sourceUrl = assign(modal.data.sourceUrl) 
            form.filtered_ids = assign(modal.data.filtered_ids)
            form.project_id = assign(modal.data.project_id)
        } 
        colors.value = props.modal.method === 'delete' ? 'red' : 'blue';//change color button
    },
    {deep:true}
)
</script>
<template>
    <jet-dialog-modal :show="modal.add_edit" @close="close()">
        <template #content>
            <s4-loading v-show="loading" :label="messageLoading"></s4-loading>
             <div class="col-span-6 text-lg font-semibold " v-if="modal.method == 'delete'">
                    Are you sure you want to delete this filter?
            </div>
            <div class="grid grid-cols-6 gap-1" v-else>
                <div class="col-span-6 mx-auto"> 
                    <span class="text-lg font-semibold" v-if="modal.method == 'add'">Add Filter</span>
                    <span class="text-lg font-semibold"  v-if="modal.method == 'edit'">Update Filter</span>
                 
                     <hr>
                </div> 
                <div class="col-span-6">
                    <jet-label class="" value="Name" />
                    <s4-input type="text" color="ssrGray" placeholder="Input Filter Name" class="mt-1 block w-full rounded-lg" v-model="form.name" />
                    <jet-input-error :message="form.errors.filterName" />
                    
                    <jet-label class="mt-5" value="Description"/>
                    <s4-input type="text" color="ssrGray" placeholder="Input Filter Description" 
                        class="mt-1 block w-full rounded-lg" v-model="form.details" />
                    <jet-label class="mt-5" value="Source URL (delimited by new line for multiple sources)" />
                    <!-- <s4-input type="text" color="ssrGray" 
                        class="mt-1 block w-full rounded-lg" v-model="form.sourceUrl" /> -->
                    <s4-text-area color="ssrGray" v-model="form.sourceUrl" 
                        class="w-full rounded-lg h-24"></s4-text-area>
                    <jet-label class="mt-5" value="Filter ID's:"  />
                    <s4-text-area color="ssrGray" v-model="form.filtered_ids" placeholder="Enter Participant ID and use comma(, for multiple criteria)" 
                        class="w-full rounded-lg h-60"></s4-text-area>
                </div> 
            </div> 
        </template>
        <template #footer>
            <s4-button :theme="'ssrCancel'" class="mr-1" @click="close()">
                Cancel
            </s4-button>
            <s4-button :theme="'ssrOrange'" :disabled="form.processing" @click="AddEditFilter()">
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
                <template v-else>
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.save" />-->
                        Save
                    </span>
                </template>
            </s4-button>
        </template>
    </jet-dialog-modal>
</template>