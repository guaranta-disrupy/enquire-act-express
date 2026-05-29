<template>
    <jet-dropdown align="left" width="100">
        <template #trigger>
            <button class="flex items-center text-md font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                <div>{{ $page.props.survey.name }}</div>

                <div class="ml-1">
                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
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
<script>

    import JetDropdown from '@/Jetstream/Dropdown.vue';
    import JetDropdownLink from '@/Jetstream/DropdownLink.vue';
    import { defineComponent } from 'vue';

    export default defineComponent({
        props: ['destination'],
        components: {
            JetDropdown,
            JetDropdownLink
        }
    })
</script>
