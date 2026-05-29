
<script setup>
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import S4Button from '@/S4/Button.vue';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEdit, faSpinner, faSave, faTrashAlt } from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue';
import { useForm, usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js'

const {  toast } = helpers()

const props = defineProps(['modal'])
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
const DeleteImage = ()=>{
    form.delete(route('gallery.destroy', [
        usePage().props.questionnaire.code,
        props.modal.data.id
        ]), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Image deleted`, {
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
    <jet-dialog-modal :show="modal.delete" @close="close()">
        <template #title>
            <span>Delete Image</span>
        </template>

        <template #content>
            <div class="col-span-6 text-lg font-semibold">
                Are you sure you want to delete this image?
            </div>
        </template>

        <template #footer>
            <s4-button :theme="'ssrCancel'" class="mr-3" @click="close()">
                Cancel
            </s4-button>
            <s4-button :theme="'ssrOrange'" :disabled="form.processing" @click="DeleteImage()">
                <template v-if="form.processing">
                    <span class="pl-3 pr-3">
                    <font-awesome-icon :icon="faIcon.spinner" spin /> Wait
                    </span>
                </template>
                <template v-else>
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.trash" />-->
                        Delete
                    </span>
                </template>
            </s4-button>
        </template>
    </jet-dialog-modal>
</template>

