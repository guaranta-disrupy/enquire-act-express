<script setup>
import WarningModal from '../../../S4/Warning.vue';
import { faSpinner } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { useForm, usePage } from "@inertiajs/vue3";
import helpers from '@/helpers.js'

const { toast } = helpers()

const emit = defineEmits(['close'])

const props = defineProps({
    modal: {
        default: {
            delete: false,
            method: 'add',
            data: null,
        }
    },
})

const faIcon = {
    spinner: faSpinner
}

const form = useForm({}, {
    resetOnSuccess: true,
})

const close = () => {
    form.reset()
    emit('close')
}

const deleteTheme = () => {
    form.delete(route('themes.destroy', [
        usePage().props.questionnaire.code,
        props.modal.data.id
    ]), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Theme deleted`, {
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
    <WarningModal :show="modal.delete" @close="close()">
        <template #header>
            Delete Theme
        </template>
        <template #content>
            Are you sure you want to delete
            <span class="text-ssr-orangehover">{{ modal.data.text }}</span>?
        </template>
        <template #footer>
            <button
                :disabled="form.processing"
                @click="deleteTheme()"
                class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
                type="button"
            >
                <template v-if="form.processing">
                    <span class="pl-3 pr-3">
                        <font-awesome-icon :icon="faIcon.spinner" spin /> Wait
                    </span>
                </template>
                <template v-else>
                    <span class="pl-3 pr-3">
                        Delete
                    </span>
                </template>
            </button>
            <button
                @click="close()"
                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:text-gray-400 sm:mt-0 sm:w-auto"
                type="button"
            >
                Cancel
            </button>
        </template>
    </WarningModal>
</template>

