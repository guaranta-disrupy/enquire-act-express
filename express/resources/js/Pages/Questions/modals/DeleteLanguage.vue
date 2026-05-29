<script setup>
import WarningModal from '../../../S4/Warning.vue';
import { faSpinner } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useForm, usePage } from '@inertiajs/vue3';
import helpers from '@/helpers.js';

const { toast } = helpers();

const props = defineProps({
    modal: {
        default: {
            delete: false,
            data: null, // will contain language object or key
        },
    },
});

const faIcon = {
    spinner: faSpinner,
};

const form = useForm(
    {
        language: null,
    },
    {
        resetOnSuccess: true,
    }
);

const emit = defineEmits(['close', 'deleted']);

const close = () => {
    form.reset();
    emit('close');
};

const deleteLanguage = () => {
    const page = usePage();
    const languageKey =
        (props.modal?.data && props.modal.data.key) ||
        props.modal?.data ||
        null;

    if (!languageKey) {
        toast.error('Invalid language selected.');
        return;
    }

    form.language = languageKey;

    form.delete(
        route('delete.language', {
            code: page.props.questionnaire.code,
        }),
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success('Language deleted', {
                    timeout: 2000,
                });
                emit('deleted', languageKey);
                close();
            },
            onError: () => {
                toast.error('Failed to delete language. Please try again.');
            },
        }
    );
};
</script>

<template>
    <WarningModal :show="modal.delete" @close="close()">
        <template #header>
            Remove Language
        </template>
        <template #content>
            Are you sure you want to remove
            <span class="text-ssr-orangehover font-semibold">
                {{ modal.data?.languageInfo?.EnglishName || modal.data?.key || modal.data }}
            </span>
            from this questionnaire?
        </template>
        <template #footer>
            <button
                :disabled="form.processing"
                @click="deleteLanguage()"
                class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"
                type="button"
            >
                <template v-if="form.processing">
                    <span class="pl-3 pr-3">
                        <FontAwesomeIcon :icon="faIcon.spinner" spin /> Wait
                    </span>
                </template>
                <template v-else>
                    <span class="pl-3 pr-3">
                        Remove
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


