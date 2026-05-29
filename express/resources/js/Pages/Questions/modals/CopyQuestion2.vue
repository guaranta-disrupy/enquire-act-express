<script setup>
import WarningModal from '../../../S4/Warning.vue';
import { faSpinner } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { useForm, usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js'

const { toast } = helpers()

const props = defineProps({
    modal: {
        default: {
            copy: false,
            method: 'copy',
            data: null,
        }
    },
})
const faIcon = {
    spinner: faSpinner
}

const form = useForm(
    { questionToCopy: null },
    { resetOnSuccess: true }
)

const emit = defineEmits(['close', 'scrollToBottom'])
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
    const page = usePage();

    const params = [
        page.props.questionnaire.code,
        props.modal.data.id,
    ];

    // Use templates.copy when we're on the Templates page, otherwise use question.copy
    const routeName = page.component === 'Questions/Template'
        ? 'templates.copy'
        : 'question.copy';

    form.post(route(routeName, params), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Question Duplicated', {
                timeout: 2000
            });
            close()
        },
    })
}
</script>

<template>
    <WarningModal :show="modal.copy" :closeable="false" @close="close()" icon="copy">
        <template #header>
            Duplicate Question
        </template>
        <template #content>
            <span>Are you sure you want to duplicate this question?</span>
        </template>
        <template #footer>
            <button :disabled="form.processing" @click="copyQuestion()"
                class="inline-flex w-full justify-center rounded-md bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] !text-black px-3 py-2 text-sm font-semibold shadow-sm  sm:ml-3 sm:w-auto"
                type="button">
                <template v-if="form.processing">
                    <span class="pl-3 pr-3 text-white"><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span>
                </template>
                <template v-else-if="modal.method == 'copy'">
                    <span class="pl-3 pr-3 text-white">
                        Duplicate
                    </span>
                </template>
            </button>
            <button @click="close2()"
                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:text-gray-400 sm:mt-0 sm:w-auto"
                type="button">
                Cancel
            </button>
        </template>
    </WarningModal>
</template>
