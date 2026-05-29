<script setup>
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import JetLabel from '@/Jetstream/Label.vue';
import S4Button from '@/S4/Button.vue';
import S4Input from '@/S4/Input.vue';
import S4InputSelect from '@/S4/InputSelect.vue';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEdit, faSpinner, faSave, faTrashAlt } from '@fortawesome/free-solid-svg-icons'
import { inject, ref, onBeforeMount } from 'vue';
import { useForm, usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js'

const { assign, toast } = helpers()
const form_data = inject('add_edit_form')
const emit = defineEmits(["close"])
const props = defineProps({
    modal: {
        default: {
            save_template: false,
            method: 'save',
            data: null,
        }
    },
})

onBeforeMount(() => {
    usePage().props.language_all.forEach(lang => {
        all_language.value.language.push({
            value: lang.id,
            text: lang.name
        })
    })
})

const all_language = ref({
    language: []
})

const faIcon = ref({
    save: faSave,
    spinner: faSpinner,
    edit: faEdit,
    trash: faTrashAlt
})
const form = useForm({
    name: '',
    language_id: null,
    q_data: {}
}, {
    resetOnSuccess: true,
})
const close = () => {
    form.reset()
    emit('close')
}
const SaveTemplate = () => {
    // console.log(form_data)
    form.q_data = assign(form_data)
    form.post(route('templates.store', [
        usePage().props.questionnaire.code
    ]), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Template saved`, {
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
    <jet-dialog-modal :show="modal.save_template" @close="close()">
        <template #title>
            <span>Save as Template</span>
        </template>

        <template #content>
            <div class="grid grid-cols-2 gap-2 my-8">
                <div>
                    <jet-label>
                        Name
                        <s4-input color="ssrGray" class="w-full mt-1" placeholder="Enter Name" v-model="form.name">
                        </s4-input>
                    </jet-label>
                </div>
                <div>
                    <jet-label class="">
                        Language
                        <s4-input-select class="w-full mt-1" color="ssrGray" :lists="all_language.language"
                            v-model="form.language_id"></s4-input-select>
                    </jet-label>
                </div>
            </div>
        </template>

        <template #footer>
            <s4-button :theme="'ssrCancel'" color="ssrGray" class="mr-3" @click="close()">
                Cancel
            </s4-button>
            <s4-button :theme="'ssrOrange'" :disabled="form.processing" @click="SaveTemplate()">
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

