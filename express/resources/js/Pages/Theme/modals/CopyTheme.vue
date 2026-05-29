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
    {
        name: '',
        setup: {},
    },
    { resetOnSuccess: true },
)

const emit = defineEmits(['close'])

const close = () => {
    form.reset()
    emit('close')
}

const copyTheme = () => {
    // mirror AddEditTheme.vue copy behavior: send name + setup
    if (props.modal?.data) {
        form.name = props.modal.data.text
        form.setup = props.modal.data.setup
    }

    const params = [
        usePage().props.questionnaire.code,
        props.modal.data.id,
    ]

    form.post(route('themes.copy', params), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Theme Copied', {
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
            Duplicate Theme
        </template>

        <template #content>
            <span>Are you sure you want to duplicate this theme?</span>
        </template>

        <template #footer>
            <button
                :disabled="form.processing"
                @click="copyTheme()"
                class="inline-flex w-full justify-center rounded-md bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] !text-black px-3 py-2 text-sm font-semibold shadow-sm sm:ml-3 sm:w-auto"
                type="button"
            >
                <template v-if="form.processing">
                    <span class="pl-3 pr-3 text-white">
                        <font-awesome-icon :icon="faIcon.spinner" spin /> Wait
                    </span>
                </template>
                <template v-else-if="modal.method == 'copy'">
                    <span class="pl-3 pr-3 text-white">
                        Duplicate
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

