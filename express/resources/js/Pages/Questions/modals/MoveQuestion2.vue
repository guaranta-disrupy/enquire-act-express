<script setup>
import WarningModal from '../../../S4/Warning.vue';
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
  
    <WarningModal :show="modal.move_question" @close="close()" icon="move">
        <template #header>
           Question Order
        </template>
        <template #content>
             <div class="grid grid-cols-[350px]">
                <div class="text-sm, font-semibold">
                    <div>Make this Question Number:</div>
                    <s4-input-select class="w-full" color="ssrGray" 
                        v-model="form.order"
                        :lists="lists">
                    </s4-input-select>
                </div>
            </div>
        </template>
        <template #footer>
            <button :disabled="form.processing" @click="moveQuestion()"
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
</template>

