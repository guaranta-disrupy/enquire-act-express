<script setup>
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import WarningModal from '../../../S4/Warning.vue';
import S4Button from '@/S4/Button.vue';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEdit, faSpinner, faSave, faTrashAlt } from '@fortawesome/free-solid-svg-icons'
import { useForm, usePage } from "@inertiajs/vue3";
import { ref } from 'vue';
import helpers from '@/helpers.js'

const { toast } = helpers()

const props = defineProps({
    modal:{
        default:{
            delete:false,
            method:'add',
            data:null,
        }
    },
})

const emits = defineEmits(['close'])
const faIcon = ref({
                save: faSave,
                spinner: faSpinner,
                edit: faEdit,
                trash: faTrashAlt
            })
const form = useForm({}, {
    resetOnSuccess:true,
})

const close = ()=>{
    form.reset()
    emits('close')
}
const DeleteQuestion = ()=>{ 
    form.delete(route('templates.destroy', [ 
        usePage().props.questionnaire.code,
        props.modal.data.id
        ]), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Template deleted`, {
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
    <!-- <jet-dialog-modal :show="modal.delete" @close="close()">
        <template #title>
            <span>Delete Question</span>
        </template>

        <template #content>
            <div class="col-span-6 text-lg font-semibold">
                Are you sure you want to delete <span class="text-ssr-orangehover">{{modal.data.name}}</span> Template?
            </div>
        </template>

        <template #footer>
            <s4-button :theme="'ssrCancel'" color="ssrGray" class="mr-3" @click="close()">
                Cancel
            </s4-button>
            <s4-button :theme="'ssrOrange'" :disabled="form.processing" @click="DeleteQuestion()">
                <template v-if="form.processing">
                    <span class="pl-3 pr-3"><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span>
                </template>
                <template v-else>
                    <span class="pl-3 pr-3 text-white">
                        <font-awesome-icon :icon="faIcon.trash" /> 
                        Delete
                    </span>
                </template>
            </s4-button>
        </template>
    </jet-dialog-modal> -->
    <WarningModal :show="modal.delete" @close="close()">
        <template #header>
            Delete Question
        </template>
        <template #content>
            Are you sure you want to delete <span class="text-ssr-orangehover">{{modal.data.name}}</span>?
        </template>
        <template #footer>
            <button :disabled="form.processing" @click="DeleteQuestion()"
                class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
                type="button">
                <template v-if="form.processing">
                    <span class="pl-3 pr-3"><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span>
                </template>
                <template v-else>
                    <span class="pl-3 pr-3">
                        Delete
                    </span>
                </template>
            </button>
            <button @click="close()"
                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:text-gray-400 sm:mt-0 sm:w-auto"
                type="button">
                Cancel
            </button>
        </template>
    </WarningModal>
</template>

