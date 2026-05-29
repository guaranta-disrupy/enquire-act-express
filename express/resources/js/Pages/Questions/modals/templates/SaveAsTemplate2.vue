<script setup>
import WarningModal from '../../../../S4/Warning.vue';
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

<teleport to="body">
 <WarningModal :show="modal.save_template" @close="close()" icon="saveTemplate">
        <template #header>
            <div class="mt-2">Save as Template</div>
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
            <button :disabled="form.processing" @click="SaveTemplate()"
                class="inline-flex w-full justify-center rounded-md bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] !text-black px-3 py-2 text-sm font-semibold shadow-sm  sm:ml-3 sm:w-auto"
                type="button">
                <template v-if="form.processing">
                    <span class="pl-3 pr-3 text-white"><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span>
                </template>
                <template v-else>
                    <span class="pl-3 pr-3 text-white">
                        Save
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
</teleport>
   
</template>
