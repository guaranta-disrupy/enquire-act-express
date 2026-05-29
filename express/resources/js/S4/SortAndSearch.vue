<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    sortValue: {
        type: String,
        default: 'name-asc'
    },
    searchValue: {
        type: String,
        default: ''
    },
    sortOptions: {
        type: Array,
        default: () => [
            { value: 'name-asc', label: 'Name (Ascending)' },
            { value: 'name-desc', label: 'Name (Descending)' },
            { value: 'date-asc', label: 'Date Uploaded (Oldest First)' },
            { value: 'date-desc', label: 'Date Uploaded (Newest First)' }
        ]
    },
    searchPlaceholder: {
        type: String,
        default: 'Search existing language'
    },
    sortWidth: {
        type: String,
        default: '200px'
    },
    searchWidth: {
        type: String,
        default: '220px'
    },
    gap: {
        type: String,
        default: '1'
    },
    showSearch: {
        type: Boolean,
        default: true
    }
})

const emits = defineEmits(['update:sortValue', 'update:searchValue'])

const sortModel = computed({
    get: () => props.sortValue,
    set: (value) => emits('update:sortValue', value)
})

const searchModel = computed({
    get: () => props.searchValue,
    set: (value) => emits('update:searchValue', value)
})

// Custom dropdown state
const isDropdownOpen = ref(false)
const dropdownRef = ref(null)

const selectedOption = computed(() => {
    return props.sortOptions.find(opt => opt.value === props.sortValue) || props.sortOptions[0]
})

const toggleDropdown = () => {
    isDropdownOpen.value = !isDropdownOpen.value
}

const selectOption = (option) => {
    sortModel.value = option.value
    isDropdownOpen.value = false
}

const closeDropdown = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isDropdownOpen.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', closeDropdown)
})

onUnmounted(() => {
    document.removeEventListener('click', closeDropdown)
})
</script>

<template>
    <div class="flex items-center gap-1">
        <!-- Sort Dropdown -->
        <div class="relative" ref="dropdownRef">
            <button
                @click="toggleDropdown"
                :style="{ width: sortWidth }"
                type="button"
                class="cursor-pointer transition flex justify-center items-center text-xs font-semibold px-3 py-2 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50">
                <!-- Replaced base64 pattern icon with a simple vector icon using currentColor
                     so it renders reliably in all parent components -->
                <svg
                    class="w-5 h-5 text-[var(--mcolor)] mr-2"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="none"
                >
                    <!-- Rounded square background -->
                    <rect
                        x="2.5"
                        y="2.5"
                        width="11"
                        height="11"
                        rx="2"
                        fill="currentColor"
                        opacity="0.15"
                    />
                    <!-- Horizontal sort lines -->
                    <path
                        d="M5 6.5H13"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                    />
                    <path
                        d="M5 10H11"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                    />
                    <path
                        d="M5 13.5H9"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                    />
                </svg>
                <span class="truncate max-w-[140px]">{{ selectedOption.label }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" 
                    :class="['w-4 h-4 ml-2 text-gray-400 transition-transform duration-200', { 'rotate-180': isDropdownOpen }]"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
            
            <!-- Dropdown Menu -->
            <transition name="dropdown">
                <div
                    v-if="isDropdownOpen"
                    :style="{ width: sortWidth }"
                    class="absolute z-50 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                    <div
                        v-for="option in sortOptions"
                        :key="option.value"
                        @click="selectOption(option)"
                        class="px-3 py-2 text-sm flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors"
                        :class="option.value === sortValue ? 'text-gray-900' : 'text-gray-700'"
                        :style="option.value === sortValue ? { backgroundColor: 'rgba(209, 129, 11, 0.1)' } : {}">
                        <span class="font-normal text-xs"
                            :style="option.value === sortValue ? { color: 'var(--mcolor2)' } : {}">
                            {{ option.label }}
                        </span>
                    </div>
                </div>
            </transition>
        </div>
        <!-- Search Input -->
        <div v-if="props.showSearch" class="relative">
            <input 
                v-model="searchModel"
                type="text" 
                :placeholder="searchPlaceholder"
                :style="{ width: searchWidth }"
                class="bg-white border border-gray-200 rounded-lg overflow-hidden px-4 py-2.5 pl-10 pr-4 text-black text-xs font-medium placeholder-[text-gray-700] placeholder-opacity-60 focus:outline-none focus:!border-[var(--mcolor2)] focus:ring-1 focus:!ring-[var(--mcolor)] hover:border-[var(--mcolor2)] transition-all duration-200 shadow-sm hover:shadow-md" />
            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-[var(--mcolor)] opacity-70"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
    </div>
</template>

<style scoped>
/* Dropdown transition animations */
.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.2s ease;
    transform-origin: top;
}

.dropdown-enter-from {
    opacity: 0;
    transform: translateY(-10px) scale(0.95);
}

.dropdown-enter-to {
    opacity: 1;
    transform: translateY(0) scale(1);
}

.dropdown-leave-from {
    opacity: 1;
    transform: translateY(0) scale(1);
}

.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-10px) scale(0.95);
}
</style>

