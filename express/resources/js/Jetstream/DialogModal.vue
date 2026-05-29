<script setup>
import Modal from './Modal.vue';

const emit = defineEmits(['close']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    zIndex: {
        type: [Number, String],
        default: 50,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    closeOnClickAway: {
        type: Boolean,
        default: true,
    },
});

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal
        :show="show"
        :z-index="zIndex"
        :max-width="maxWidth"
        :closeable="closeable"
        :close-on-click-away="closeOnClickAway"
        @close="close" 
    >
    <div class="max-h-[calc(100vh-120px)] h-[auto] flex flex-col rounded-md overflow-hidden">
        <!-- <div class="px-6 py-4 flex flex-col h-full"> -->
            <div class="text-2xl font-bold p-[12px_24px]">
                
                <slot name="title" />
            </div>

            <div class="grow-[1] overflow-y-auto overflow-x-hidden scroll-smooth p-[12px_24px]">
                <slot name="content" />
            </div>
        <!-- </div> -->
        
        <div class="flex flex-row justify-between p-[12px_24px] bg-gray-100 text-right relative">
            <slot name="footer" />
        </div>
    </div>
        
    </Modal>
</template>


