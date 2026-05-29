<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEdit, faSave, faTrashAlt, faPlus, faCopy } from '@fortawesome/free-solid-svg-icons'

import S4Button from '@/S4/Button.vue';
import { ref } from 'vue';


const faIcon = ref({
    edit: faEdit,
    trash: faTrashAlt,
    plus: faPlus,
    save: faSave,
    copy: faCopy
})
</script>

<template>
    <div class="flex items-center flex-row mt-1 min-h-36px">
        <div class="-mt-7 flex items-center flex-row p-1" v-if="$slots.header">
            <slot name="header"></slot>
            <!-- <div class="border-2 border-gray-400 ml-1 w-44">
                <span class="flex justify-center object-cover font-semibold text-gray-500 bg-white rounded-sm">Hide Choice</span>
            </div>    -->          
        </div>
        <div class="w-full flex items-center flex-row-reverse p-1">
            <slot name="controls"></slot>
            <!-- <s4-button color="outline-red" class="group" size="sm">
                <font-awesome-icon :icon="faIcon.trash" class="fill-current text-red-400 group-hover:text-red-500"/>
                <span class="ml-1 text-xs">delete</span>
            </s4-button> 
            <s4-button color="outline-yellow" class="mx-1 group" size="sm">
                <font-awesome-icon :icon="faIcon.copy" class="fill-current text-yellow-400 group-hover:text-yellow-500"/>
                <span class="ml-1 text-xs">copy</span>
            </s4-button> 
            <s4-button color="outline-orange" class="group" size="sm">
                <font-awesome-icon :icon="faIcon.edit" class="fill-current text-orange-400 group-hover:text-orange-500"/>
                <span class="ml-1 text-xs">edit</span>
            </s4-button> -->
        </div>
    </div>
    <div class="p-4 w-full mb-2">
        <slot name="content"></slot>
    </div>
</template>
