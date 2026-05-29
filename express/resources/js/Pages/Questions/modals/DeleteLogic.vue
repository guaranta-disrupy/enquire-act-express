<script setup>
import WarningModal from '../../../S4/Warning.vue';
import { faSpinner } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
    modal: {
        default: {
            delete: false,
            method: 'add',
            data: null,
            processing: false
        }
    },
})

const faIcon = {
    spinner: faSpinner
}

const emit = defineEmits(['close', 'confirmed'])

const close = () => {
    if (!props.modal.processing) {
        emit('close')
    }
}

const deleteLogic = () => {
    if (!props.modal.processing) {
        emit('confirmed', {
            trigger: props.modal.data.trigger,
            index: props.modal.data.index
        })
    }
}
</script>
<template>
    <!-- <jet-dialog-modal :show="modal.delete" @close="close()">
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
</jet-dialog-modal> -->
    <WarningModal :show="modal.delete" @close="close()">
        <template #header>
            Delete Logic
        </template>
        <template #content>
            Are you sure you want to delete this skip logic?
        </template>
        <template #footer>
            <button :disabled="modal.processing" @click="deleteLogic()"
                class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 disabled:opacity-50 disabled:cursor-not-allowed sm:ml-3 sm:w-auto"
                type="button">
                <template v-if="modal.processing">
                    <span class="pl-3 pr-3"><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span>
                </template>
                <template v-else>
                    <span class="pl-3 pr-3">
                        Delete
                    </span>
                </template>
            </button>
            <button :disabled="modal.processing" @click="close()"
                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:text-gray-400 disabled:opacity-50 disabled:cursor-not-allowed sm:mt-0 sm:w-auto"
                type="button">
                Cancel
            </button>
        </template>
    </WarningModal>
</template>
