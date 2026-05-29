<script setup>
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import JetLabel from '@/Jetstream/Label.vue';
import S4Button from '@/S4/Button.vue';
import S4InputSelect from '@/S4/InputSelect.vue';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSpinner } from '@fortawesome/free-solid-svg-icons'
import { useForm, usePage } from "@inertiajs/vue3";
import { computed, watch } from 'vue';
import helpers from '@/helpers.js'

const { toast } = helpers()

const props = defineProps({
    modal:{
        default:{
            skip_logic:false,
            method:'edit',
            data:null,
        }
    }
})

const faIcon = {
    spinner: faSpinner
}

const form = useForm({
    order: 0
}, {
    resetOnSuccess:true,
})

const lists = computed(() => {
    let tmp_list = []
    usePage().props.questions.forEach(question => {
        tmp_list.push({
            value: question.order,
            text: "Q"+question.order
        })
    })
    return tmp_list
})

const emit = defineEmits(['close'])

const close = () =>{
    form.reset()
    emit('close')
}
const moveQuestion = () => {
    form.post(route('questions.move', [
        usePage().props.questionnaire.code,
        props.modal.data.id
        ]), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Question moved`, {
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

watch(
    ()=>props.modal,
    (modal) => {
        if (modal.move_question == true) {
            form.order = modal.data.order
        }   
    },
    { deep: true, }
)

</script>
<template>
    <jet-dialog-modal :show="modal.move_question" @close="close()">
        <template #title>
            <span>Question Order</span>
        </template>

        <template #content>
            <div class="grid grid-cols-6">
                <div class="col-span-3 text-lg font-semibold">
                    <jet-label>Move Question</jet-label>
                    <s4-input-select class="w-full" color="ssrGray" 
                        v-model="form.order"
                        :lists="lists">
                    </s4-input-select>
                </div>
            </div>
        </template>

        <template #footer>
            <s4-button :theme="'ssrCancel'" color="ssrGray" class="mr-3" @click="close()">
                Cancel
            </s4-button>
            <s4-button :theme="'ssrOrange'" :disabled="form.processing" @click="moveQuestion()">
                <template v-if="form.processing">
                    <span class="pl-3 pr-3"><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span>
                </template>
                <template v-else>
                    <span class="pl-3 pr-3">
                        Save
                    </span>
                </template>
            </s4-button>
        </template>
    </jet-dialog-modal>
</template>

