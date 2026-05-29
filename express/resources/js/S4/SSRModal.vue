<script setup>
import { computed } from 'vue';
const emit = defineEmits(['close']);

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    icon: {
        type: String,
        default: 'delete',
    },
    /**
     * Tailwind max-width size for the modal panel.
     * Allowed: sm, md, lg, xl, 2xl, 4xl, 6xl, 7xl, full, almost-full
     */
    maxWidth: {
        type: String,
        default: 'lg',
    },
});

const close = () => {
    emit('close');
};

const maxWidthClass = computed(() => {
    const map = {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
        '4xl': 'sm:max-w-4xl',
        '6xl': 'sm:max-w-6xl',
        '7xl': 'sm:max-w-7xl',
        'full': 'sm:max-w-[98vw]',
        'almost-full': 'sm:max-w-[98vw]',
    };
    return map[props.maxWidth] || map.lg;
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-black/50 z-[500]"></div>
    <transition appear enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100" leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">

        <div v-if="show" class="fixed inset-0 z-[501] w-screen overflow-y-auto">

            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div
                    :class="[
                        'relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full',
                        maxWidthClass
                    ]">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 border-lg" v-if="icon === 'saveTemplate'">
                        <div class="">


                            <div class="mt-3 text-center sm:mt-0 sm:text-left">
                                <div class="flex gap-2">
                                    <div
                                        class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-[#f8e2c0] sm:mx-0 sm:h-10 sm:w-10">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="#ee920c" class="h-7 w-7">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>

                                    </div>
                                    <h3 id="modal-title" class="text-base font-semibold leading-6 text-gray-900">
                                        <slot name="header" />
                                    </h3>
                                </div>

                                <div class="mt-2 ml-4 mr-4">
                                    <p class="text-sm text-gray-500">
                                        <slot name="content" />
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4" v-else>
                        <div class="sm:flex sm:items-start w-full">
                            <div v-if="icon === 'delete'"
                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg aria-hidden="true" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"
                                    fill="none" class="h-6 w-6 text-red-600">
                                    <path
                                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                                        stroke-linejoin="round" stroke-linecap="round"></path>
                                </svg>
                            </div>
                            <div v-else-if="icon === 'copy'"
                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-[#f8e2c0] sm:mx-0 sm:h-10 sm:w-10">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="#ee920c" viewBox="0 0 24 24"
                                    class="h-6 w-6">
                                    <path
                                        d="M20 2H10c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2m0 12H10V4h10z">
                                    </path>
                                    <path
                                        d="M14 20H4V10h2V8H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2v-2h-2z">
                                    </path>
                                </svg>
                            </div>
                            <div v-else-if="icon === 'move'"
                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-[#f8e2c0] sm:mx-0 sm:h-10 sm:w-10">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="#ee920c" class="h-7 w-7">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
                                </svg>
                            </div>
                            <div v-else-if="icon === 'overwrite'"
                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-[#f8e2c0] sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="overwrite-icon" width="120" height="120" viewBox="0 0 120 120" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <!-- Document base -->
                                    <rect x="25" y="20" width="50" height="65" rx="4" fill="#f8e2c0" stroke="#ee920c"
                                        stroke-width="1" />

                                    <!-- New document overlaying -->
                                    <rect x="35" y="30" width="50" height="65" rx="4" fill="#ee920c" stroke="#d17a00"
                                        stroke-width="1.5" />

                                    <!-- Document corner fold -->
                                    <path d="M75 30 L75 40 L85 40 Z" fill="#d17a00" />

                                    <!-- Overwrite arrow -->
                                    <path d="M45 50 L65 50 M60 45 L65 50 L60 55" stroke="white" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round" />

                                    <!-- Document lines -->
                                    <line x1="42" y1="60" x2="70" y2="60" stroke="rgba(255,255,255,0.9)"
                                        stroke-width="1.5" stroke-linecap="round" />
                                    <line x1="42" y1="67" x2="65" y2="67" stroke="rgba(255,255,255,0.9)"
                                        stroke-width="1.5" stroke-linecap="round" />
                                    <line x1="42" y1="74" x2="68" y2="74" stroke="rgba(255,255,255,0.9)"
                                        stroke-width="1.5" stroke-linecap="round" />

                                    <!-- Warning indicator -->
                                    <circle cx="75" cy="75" r="8" fill="#f39c12" />
                                    <path d="M75 70 L75 76 M75 79 L75 80" stroke="white" stroke-width="2"
                                        stroke-linecap="round" />
                                </svg>
                            </div>

                            <!-- <div v-else-if="icon === 'saveTemplate'"
                                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-[#feefb4] sm:mx-0 sm:h-10 sm:w-10">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="#e6b021" class="h-7 w-7">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>

                            </div> -->
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 id="modal-title" class="text-base font-semibold leading-6 text-gray-900">
                                    <slot name="header" />
                                </h3>
                                <div class="mt-2 w-full">
                                    <slot name="content" />
                                </div>
                            </div>
                        </div>
                    </div>



                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </div>

    </transition>


</template>