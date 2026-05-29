<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
const modalElem = ref(null)
const sidebar = ref({
    open : false,
    targets : []
})

const props = defineProps({
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

const emit = defineEmits(['close']);

watch(() => props.show, () => {
    if (props.show) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = null;
    }
});

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => {
    document.addEventListener('keydown', closeOnEscape)
    modalElem.value.addEventListener("sidebar", (e)=> {
        if(e.detail.open){
            if (!sidebar.value.targets.includes(e.detail.target)) {
                sidebar.value.targets.push(e.detail.target);
            }
            sidebar.value.open = true
        }else{
            if (sidebar.value.targets.includes(e.detail.target)) {
                sidebar.value.targets = sidebar.value.targets.filter(target => target !== e.detail.target);
            }      
            if(sidebar.value.targets.length <= 0){
                sidebar.value.open = false
            }   
        }

        
    })
});

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = null;
});

const maxWidthClass = computed(() => {
    return {
        'sm': 'sm:max-w-sm',
        'md': 'sm:max-w-md',
        'lg': 'sm:max-w-lg',
        'xl': 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[props.maxWidth];
});

const handleClickOutside = (event) => {
    if (!closeOnClickAway) return; // Do nothing if closeOnClickAway is false

    const modalContent = event.target.closest('.modal-content');
    if (!modalContent) {
        emit('close');
    }
};
</script>

<template>
    <teleport to="body">
        <transition name="modalBoxAnimation">
            <div
                v-show="show"
                ref="modalElem"
                class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50 jetStreamModal donModal"
                :style="{ zIndex: props.zIndex }"
                scroll-region
            >
                <transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-show="show" class="fixed inset-0 transform transition-all" @click="handleClickOutside">
                        <div class="absolute inset-0 bg-gray-500 opacity-75" />
                    </div>
                </transition>

                <!-- <transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                > -->

                <transition
                    name="modalAnimation"
                >
                    <div v-if="show"  class="mb-6 jetStreamModalContent bg-white rounded-lg shadow-xl transform transition-all sm:w-full sm:mx-auto" 
                    :class="[maxWidthClass,
                    (sidebar.open)?'opensidebar':'']">
                        <slot/>
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>


<style scoped>
.jetStreamModalContent {
    margin: 0px !important;
    transform: translate(500px, 60px);
    width: calc(100% - 550px) !important;
    left: 0px !important;
    transition-duration: .5s;
}



.jetStreamModalContent {
    transform: translate(200px, 60px);
    width: calc(100% - 250px) !important;
}



.jetStreamModalContent.opensidebar{
    transform: translate(500px, 60px);
    width: calc(100% - 550px) !important;   
}

@media only screen and (max-width: 1200px) {
    .jetStreamModalContent {
        transform: translate(200px, 60px);
        width: calc(100% - 250px) !important;
    }
    .jetStreamModalContent.opensidebar {
        transform: translate(200px, 60px);
        width: calc(100% - 250px) !important;
    }
}

.modalAnimation-enter-active:not(.opensidebar) {
  animation: modalAnimation-in 0.5s;
}
.modalAnimation-leave-active:not(.opensidebar) {
  animation: modalAnimation-in 0.5s reverse;
}


.modalAnimation-enter-active.opensidebar {
  animation: modalAnimation-in2 0.5s;
}
.modalAnimation-leave-active.opensidebar {
  animation: modalAnimation-in2 0.5s reverse;
}
/* @keyframes modalAnimation-in {
  0% {
    opacity:0;
    transform: translate(100%,60px);
  }
  100% {
    opacity:1;
    transform: translate(500px,60px);
  }
} */


@keyframes modalAnimation-in {
        0% {
            opacity:0;
            transform: translate(100%,60px);
        }
        100% {
            opacity:1;
            transform: translate(200px,60px);
        }
}

@keyframes modalAnimation-in2 {
        0% {
            opacity:0;
            transform: translate(100%,60px);
        }
        100% {
            opacity:1;
            transform: translate(500px,60px);
        }
}

@media only screen and (max-width: 1200px) {
    @keyframes modalAnimation-in2 {
        0% {
            opacity:0;
            transform: translate(100%,60px);
        }
        100% {
            opacity:1;
            transform: translate(200px,60px);
        }
    }
} 

.modalBoxAnimation-enter-active {
  animation: modalBoxAnimation-in 0.5s;
}
.modalBoxAnimation-leave-active {
  animation: modalBoxAnimation-in 0.5s reverse;
}
@keyframes modalBoxAnimation-in {
  0% {
    opacity:0;

  }
  100% {
    opacity:1;

  }
}
</style>
