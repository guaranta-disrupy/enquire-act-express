<template>
    <jet-dropdown align="left" width="auto">
        <template #trigger>
            <button class="inline-flex items-center py-1 px-3 text-sm font-medium text-gray-600 bg-transparent hover:bg-sky-200 rounded-lg focus:z-10 focus:bg-sky-300 transition duration-150 ease-in-out">
                <div class="uppercase max-w-xs whitespace-nowrap">
                    <p class="truncate">
                        {{ $page.props.survey.name }}
                    </p>
                </div>

                <div class="ml-1">
                    <svg
                        class="ml-2 -mr-0.5 h-4 w-4"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
            </button>
        </template>
        <template #content>
            <div class="block px-4 py-2 text-xs text-gray-400">
                Surveys
            </div>
            <template v-for="(survey, key) in $page.props.allSurveys" :key="key">
                <jet-dropdown-link :href="route(destination, survey.id)">
                    <span class="text-xs" :class="{'text-blue-500': survey.id === $page.props.survey.id}">
                        {{ survey.name }}
                    </span>
                </jet-dropdown-link>
            </template>
        </template>
    </jet-dropdown>
</template>
<script setup>

import JetDropdown from '@/Jetstream/Dropdown.vue';
import JetDropdownLink from '@/Jetstream/DropdownLink.vue';

defineProps(['destination']);

</script>
