<script setup>
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import S4Button from '@/S4/Button.vue';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSpinner} from '@fortawesome/free-solid-svg-icons'
import { useForm, usePage } from "@inertiajs/vue3";
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
const faIcon = {
    spinner: faSpinner
}
const form = useForm({}, {
    resetOnSuccess:true,
})
const emit = defineEmits(['close'])
const close = () => {
    form.reset()
    emit('close')
}
const deleteQuestion = () => {
    form.delete(route('questions.destroy', [
        usePage().props.questionnaire.code,
        props.modal.data.id
        ]), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Question deleted`, {
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
            <span>Delete Question</span>
        </template>

        <template #content>
            <div class="col-span-6 text-lg font-semibold">
                Are you sure you want to delete <span class="text-ssr-orangehover">Q{{modal.data.order}}</span>?
            </div>
        </template>

        <template #footer>
            <s4-button :theme="'ssrCancel'" color="ssrGray" class="mr-3" @click="close()">
                Cancel
            </s4-button>
            <s4-button :theme="'ssrOrange'" :disabled="form.processing" @click="deleteQuestion()">
                <template v-if="form.processing">
                    <span class="pl-3 pr-3"><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span>
                </template>
                <template v-else>
                    <span class="pl-3 pr-3">
                        Delete
                    </span>
                </template>
            </s4-button>
        </template>
    </jet-dialog-modal>
</template>

