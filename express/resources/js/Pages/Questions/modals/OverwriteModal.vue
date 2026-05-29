<script setup>
import WarningModal from '../../../S4/Warning.vue';
import { faSpinner } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { ref, nextTick } from 'vue';

const faIcon = {
    spinner: faSpinner
}

const processing = ref(false);

const props = defineProps({
  modal: {
    default: {
      show: false,       // control visibility
      data: null,        // optional extra data
      onConfirm: null,   // callback when user confirms
    }
  }
});

const emit = defineEmits(['close']);

const close = () => {
  if (!processing.value) {
    emit('close');
  }
};

const confirmAction = async () => {
  if (props.modal.onConfirm && typeof props.modal.onConfirm === "function") {
    processing.value = true;
    
    // Wait for Vue to update the DOM and show the spinner
    await nextTick();
    
    try {
      await props.modal.onConfirm();
      close();
    } finally {
      processing.value = false;
    }
  } else {
    close();
  }
};
</script>

<template>
     <WarningModal :show="modal.show" :closeable="false" @close="close()" icon="overwrite">
    <template #header>
      Overwrite Choices?
    </template>

    <template #content>
      <span>There are already existing choices. Do you want to overwrite them?</span>
    </template>

    <template #footer>
      <button
        :disabled="processing"
        @click="confirmAction"
        class="inline-flex w-full justify-center rounded-md bg-[var(--mcolor)] hover:bg-[var(--mcolor2)] px-3 py-2 text-sm font-semibold text-white shadow-sm disabled:opacity-50 disabled:cursor-not-allowed sm:ml-3 sm:w-auto"
        type="button"
      >
        <template v-if="processing">
          <span class="pl-3 pr-3"><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span>
        </template>
        <template v-else>
          Confirm
        </template>
      </button>

      <button
        :disabled="processing"
        @click="close"
        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:text-gray-400 disabled:opacity-50 disabled:cursor-not-allowed sm:mt-0 sm:w-auto"
        type="button"
      >
        Cancel
      </button>
    </template>
  </WarningModal>
</template>
