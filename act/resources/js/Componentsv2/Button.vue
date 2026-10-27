<script setup>
defineProps({
    type: {
        type: String,
        default: "button",
    },
    variant: {
        type: String,
        default: "primary",
    },
    size: {
        type: String,
        default: "normal",
    },
    rounded: {
        type: String,
        default: "full",
    },
    isDropdown: {
        type: Boolean,
        default: false,
    },
    shadow: {
        type: String,
        default: "sm",
    },
});
</script>

<template>
    <button
        :type="type"
        class="antialiased flex items-center font-medium hover:shadow transition ease-in-out duration-150 disabled:opacity-25 focus:outline-none dark:focus:ring-offset-gray-800 tracking-wide active:scale-[0.95] px-3"
        :class="{
            'px-4 py-2 text-base focus:ring-2 focus:ring-offset-2':
                size == 'normal',
            'px-4 py-2 text-sm focus:ring-1 focus:ring-offset-1': size == 'sm',
            'px-3 py-1 text-xs focus:ring-1 focus:ring-offset-1': size == 'xs',
            'px-5 py-2 text-lg focus:ring-2 focus:ring-offset-2': size == 'lg',
            'bg-gray-200 hover:bg-gray-300 text-gray-600 focus:ring-gray-500':
                variant == 'secondary',
            'bg-[#257556] hover:bg-[#1d5e45] text-white focus:ring-[#1d5e45]':
                variant == 'primary',
            'bg-[#25755620] hover:bg-[#25755650] text-[#257556] focus:ring-[#1d5e45]':
                variant == 'primary-light',
            'bg-[#25755640] hover:bg-[#25755620] text-[#257556] focus:ring-[#1d5e45]':
                variant == 'primary-dark',
            'bg-orange-400 hover:bg-orange-500 text-white focus:ring-orange-500':
                variant == 'warning',
            'bg-green-600 hover:bg-green-600 text-white focus:ring-green-500':
                variant == 'success',
            'bg-rose-500 hover:bg-rose-600 text-white focus:ring-rose-500':
                variant == 'danger',
            'bg-rose-200 hover:bg-rose-300 text-rose-600 focus:ring-rose-500':
                variant == 'danger-light',
            'bg-pink-500 hover:bg-pink-600 text-white focus:ring-pink-500':
                variant == 'pink',
            'bg-rose-500 hover:bg-rose-600 text-white focus:ring-rose-500 ':
                variant == 'rose',
            'bg-[#257556] hover:bg-[#257556] text-white focus:ring-[#257556]':
                variant == 'orange',
            'bg-[#25755620] hover:bg-[#25755640] text-[#257556] focus:ring-[#257556]':
                variant == 'orange-light',
            'bg-[#257556] hover:bg-[#257556] text-white focus:ring-[#257556]':
                variant == 'orange-2',
            'bg-emerald-500 hover:bg-emerald-600 text-white focus:ring-emerald-500':
                variant == 'emerald',
            'bg-teal-500 hover:bg-teal-600 text-white focus:ring-teal-500':
                variant == 'teal',
            'bg-sky-500 hover:bg-sky-600 text-white focus:ring-sky-500':
                variant == 'sky',
            'bg-blue-500 hover:bg-blue-600 text-white focus:ring-blue-500':
                variant == 'blue',
            'bg-amber-500 hover:bg-amber-600 text-white focus:ring-amber-500':
                variant == 'amber',
            'bg-purple-500 hover:bg-purple-600 text-white focus:ring-purple-500':
                variant == 'purple',
            'bg-gray-500 hover:bg-gray-600 text-white focus:ring-gray-500':
                variant == 'gray',
            'bg-gray-100 hover:bg-gray-200 text-gray-500 focus:ring-gray-200':
                variant == 'gray-100',
            'bg-slate-600 hover:bg-slate-500 text-white focus:ring-slate-500':
                variant == 'slate',
            'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:ring-[#257556]':
                variant == 'default',
            'rounded-full': rounded == 'full',
            'rounded-lg': rounded == 'lg',
            rounded: rounded == 'normal',
            'rounded-sm': rounded == 'sm',
            'rounded-none': rounded == 'none',
            'shadow-sm': shadow == 'sm',
            'shadow-md': shadow == 'md',
            'shadow-lg': shadow == 'lg',
            'shadow-xl': shadow == 'xl',
            'shadow-2xl': shadow == '2xl',
        }"
        s
    >
        <slot />
        <div class="ml-1" v-if="isDropdown">
            <svg
                class="fill-current h-4 w-4"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
            >
                <path
                    fill-rule="evenodd"
                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                    clip-rule="evenodd"
                />
            </svg>
        </div>
    </button>
</template>
