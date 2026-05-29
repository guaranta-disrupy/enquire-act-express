<template>
    <teleport to="body">
        <Transition name="slide-left">
            <div class="right-sidebar fixed flex flex-col top-0 right-0 w-[620px] h-full bg-white z-[500] shadow-lg" v-if="$Props.show">
                <div class="rs-title pt-8 px-6 pb-6 text-xl font-bold" v-if="slots.title">
                    <slot name="title"></slot>
                </div>
                    <div class="rs-content flex-grow px-6 py-0" v-if="slots.content">
                        <slot name="content"></slot>
                    </div>
                <div class="rs-footer px-6 py-6" v-if="slots.footer">
                    <slot name="footer"></slot>
                </div>
                <div class="rs-close absolute top-3 right-4">
                    <button class="rs-close-button" @click="emits('close')">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </Transition>
    </teleport>
</template>
<script setup>
import { ref, useSlots, defineEmits } from 'vue';
const slots = useSlots();
const emits = defineEmits(['close']);

const $Props = defineProps({
    show : {
        type: Boolean,
        default: false
    }
})
</script>
<style scoped>
.slide-left-enter-active,
.slide-left-leave-active {
    transition: transform 0.3s ease-in-out;
}

.slide-left-enter-from {
    transform: translateX(100%);
}

.slide-left-enter-to {
    transform: translateX(0);
}

.slide-left-leave-from {
    transform: translateX(0);
}

.slide-left-leave-to {
    transform: translateX(100%);
}
</style>