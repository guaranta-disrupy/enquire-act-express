<script setup>
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import S4Button from '@/S4/Button.vue';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSpinner } from '@fortawesome/free-solid-svg-icons'
import { useForm, usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js'

const { toast } = helpers()

const props = defineProps({
    modal:{
        default:{
            copy:false,
            method:'copy',
            data:null,
        }
    },
})
const faIcon = {
    spinner: faSpinner
}

const form = useForm(
    {questionToCopy:null}, 
    {resetOnSuccess:true}
)

const emit = defineEmits(['close','scrollToBottom'])
const close = () => {
    form.reset()
    emit('close')
    emit('scrollToBottom')
}
const close2 = () => {
    form.reset()
    emit('close')
}
const copyQuestion = () => {
    var params = [
        usePage().props.questionnaire.code,
        props.modal.data.id,
    ]
    form.post(route('question.copy',params),{
        preserveScroll:true,
        onSuccess:() => {
            toast.success('Question Copied',{
                timeout:2000
            });
            close()
        },
    })
}
</script>

<template>
    <jet-dialog-modal :show="modal.copy" :closeable="false" @close="close()">
        <template #title>
            <span>Copy Question</span>
        </template>
        
        <template #content>
            <span>Are you sure you want to copy question?</span>
        </template>

        <template #footer>
            <s4-button :theme="'ssrCancel'" color="ssrGray" class="mr-3" @click="close2()">
                Cancel
            </s4-button>
            <s4-button :theme="'ssrOrange'" :disabled="form.processing" @click="copyQuestion()">
                <template v-if="form.processing">
                    <span class="pl-3 pr-3"><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span>
                </template>
                <template v-else-if="modal.method == 'copy'">
                    <span class="pl-3 pr-3">
                        Copy
                    </span>
                </template>
            </s4-button>
        </template>
    </jet-dialog-modal>
</template>
