<script setup>
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import S4Button from '@/S4/Button.vue';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEdit, faSpinner, faSave, faTrashAlt } from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue';
import { useForm, usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js'

const {  toast } = helpers()


const props= defineProps({
     modal:{
        default:{
            apply_theme:false,
            method:'add',
            data:null,
        }
    },
    // custom: {
    //     type: Object,
    //     default: {}
    // }
})

const emit = defineEmits(['close'])



const faIcon = ref({
    save: faSave,
    spinner: faSpinner,
    edit: faEdit,
    trash: faTrashAlt
})
const form = useForm({
    name: usePage().props.questionnaire.name,
    setup: usePage().props.questionnaire.setup,
    theme_design_id: null
}, {
    resetOnSuccess:true,
})

const close = () => {
    form.reset()
    emit('close')
}
const ApplyTheme = () => {
    // console.log("custom", props.custom)
    let form2 = useForm({
        name: usePage().props.questionnaire.name,
        setup: usePage().props.questionnaire.setup,
        theme_design_id: props.modal.data.setup.design_id
    }, {
        resetOnSuccess:true,
    })
    console.log("Applied TEMP", form2)

    let params = [
       
        usePage().props.questionnaire.code,
        props.modal.data.setup.design_id             
    ];
    form2.post(route('apply.theme',params),{
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Theme applied successfully`, {
                timeout: 2000
            });
            close()
        }
    })
}
</script>
<template>
    <jet-dialog-modal :show="modal.apply_theme" @close="close()">
        <template #title>
            <span>Apply Theme</span>
        </template>

        <template #content>
            <div class="col-span-6 text-lg font-semibold">
                Are you sure you want to apply <span class="text-ssr-orangehover">{{modal.data.text}}</span> theme?
            </div>
        </template>

        <template #footer>
            <s4-button :theme="'ssrCancel'" color="ssrGray" class="mr-3" @click="close()">
                Cancel
            </s4-button>
            <s4-button :theme="'ssrOrange'" :disabled="form.processing" @click="ApplyTheme()">
                <template v-if="form.processing">
                    <span class="pl-3 pr-3"><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span>
                </template>
                <template v-else>
                    <span class="pl-3 pr-3 text-white">
                        <!--<font-awesome-icon :icon="faIcon.trash" />--> 
                        Apply
                    </span>
                </template>
            </s4-button>
        </template>
    </jet-dialog-modal>
</template>

