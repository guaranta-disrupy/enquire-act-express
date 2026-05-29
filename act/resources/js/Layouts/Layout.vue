<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import JetBanner from '@/Jetstream/Banner.vue';
import JetDropdown from '@/Jetstream/Dropdown.vue';
import JetDropdownLink from '@/Jetstream/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import JetResponsiveNavLink from '@/Jetstream/ResponsiveNavLink.vue';
import SsrLogo from '@/Components/Logo.vue';
import Helpers from '../Mixins/Helpers.js';

const { hasString } = Helpers();

defineProps({
    title: String,
});

const showingNavigationDropdown = ref(false);

const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div>
        <Head :title="title" />

        <JetBanner />


        <div class="min-h-screen flex">
            <aside class="w-64 bg-white">
                <div class="overflow-y-auto py-4 px-3 rounded">
                    <div class="flex items-center">
                        <Link :href="route('projects.index')">
                            <SsrLogo class="block h-12 w-auto" />
                        </Link>
                    </div>
                    <ul class="space-y-2 mt-4">
                        <li>
                            <NavLink :active="hasString(route().current(), ['projects.', 'groups.', 'tests.', 'filters.', 'analysis.', 'variables.'])" :href="route('projects.index')" class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100">
                                <svg aria-hidden="true" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path><path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path></svg>
                                <span class="ml-3">Projects</span>
                            </NavLink>
                        </li>
                        <li v-if="$page.props.auth.user.role === 'root'">
                            <NavLink :href="route('accounts.index')" :active="hasString(route().current(), ['accounts.'])" class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                  <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                </svg>
                                <span class="ml-3">Accounts</span>
                            </NavLink>
                        </li>
                        <li v-if="$page.props.auth.user.role === 'root'">
                            <NavLink :href="route('teams2.index')" :active="hasString(route().current(), ['teams2.'])" class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                  <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                                </svg>
                                <span class="ml-3">Teams</span>
                            </NavLink>
                        </li>
                    </ul>
                    <hr>
                    <ul class="space-y-2 mt-4">
                        <li>
                            <NavLink :href="route('dashboard')" class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-2 0c0 .993-.241 1.929-.668 2.754l-1.524-1.525a3.997 3.997 0 00.078-2.183l1.562-1.562C15.802 8.249 16 9.1 16 10zm-5.165 3.913l1.58 1.58A5.98 5.98 0 0110 16a5.976 5.976 0 01-2.516-.552l1.562-1.562a4.006 4.006 0 001.789.027zm-4.677-2.796a4.002 4.002 0 01-.041-2.08l-.08.08-1.53-1.533A5.98 5.98 0 004 10c0 .954.223 1.856.619 2.657l1.54-1.54zm1.088-6.45A5.974 5.974 0 0110 4c.954 0 1.856.223 2.657.619l-1.54 1.54a4.002 4.002 0 00-2.346.033L7.246 4.668zM12 10a2 2 0 11-4 0 2 2 0 014 0z" clip-rule="evenodd" />
                                </svg>
                                <span class="ml-3">Support</span>
                            </NavLink>
                        </li>
                        <li>
                            <NavLink :href="route('dashboard')" class="flex items-center p-2 text-base font-normal text-gray-900 rounded-lg hover:bg-gray-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                                <span class="ml-3">Knowledge Base</span>
                            </NavLink>
                        </li>
                    </ul>
                </div>
            </aside>
            <div class="w-full bg-sky-100">
                <nav class="bg-white">
                    <!-- Primary Navigation Menu -->
                    <div class="max-w-8xl mx-auto pl-2 pr-4 sm:pr-6 lg:pr-8">
                        <div class="flex justify-between h-12 items-center">
                            <div class="flex mt-2">
                                <!-- Logo -->
                                <div class="flex items-center">
                                    <header v-if="$slots.header">
                                        <div class="font-medium prose-md uppercase text-gray-600">
                                            <slot name="header" />
                                        </div>
                                    </header>
                                </div>

                                <!-- Navigation Links -->
                                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">

                                </div>
                            </div>

                            <div class="hidden sm:flex sm:items-center sm:ml-6">
                                <div class="ml-3 relative">
                                    <!-- Teams Dropdown -->
                                    <JetDropdown v-if="$page.props.jetstream.hasTeamFeatures" align="right" width="60">
                                        <template #trigger>
                                            <span class="inline-flex rounded-md">
                                                <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:bg-sky-100 hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition">
                                                    {{ $page.props.auth.user.current_team.name }}

                                                    <svg
                                                        class="ml-2 -mr-0.5 h-4 w-4"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor"
                                                    >
                                                        <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>

                                        <template #content>
                                            <div class="w-full">
                                                <!-- Team Management -->
                                                <template v-if="$page.props.jetstream.hasTeamFeatures">
                                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                                        Manage Team
                                                    </div>

                                                    <!-- Team Settings -->
                                                    <JetDropdownLink :href="route('teams.show', $page.props.auth.user.current_team)">
                                                        Team Settings
                                                    </JetDropdownLink>

                                                    <JetDropdownLink v-if="$page.props.jetstream.canCreateTeams" :href="route('teams.create')">
                                                        Create New Team
                                                    </JetDropdownLink>

                                                    <div class="border-t border-gray-100" />

                                                    <!-- Team Switcher -->
                                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                                        Switch Teams
                                                    </div>

                                                    <template v-for="team in $page.props.auth.user.all_teams" :key="team.id">
                                                        <form @submit.prevent="switchToTeam(team)">
                                                            <JetDropdownLink as="button">
                                                                <div class="flex items-center">
                                                                    <svg
                                                                        v-if="team.id == $page.props.auth.user.current_team_id"
                                                                        class="mr-2 h-5 w-5 text-green-400"
                                                                        fill="none"
                                                                        stroke-linecap="round"
                                                                        stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        stroke="currentColor"
                                                                        viewBox="0 0 24 24"
                                                                    ><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                                    <div>{{ team.name }}</div>
                                                                </div>
                                                            </JetDropdownLink>
                                                        </form>
                                                    </template>
                                                </template>
                                            </div>
                                        </template>
                                    </JetDropdown>
                                </div>

                                <!-- Settings Dropdown -->
                                <div class="ml-3 relative">
                                    <JetDropdown align="right" width="48">
                                        <template #trigger>
                                            <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                                <img class="h-8 w-8 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                                            </button>

                                            <span v-else class="inline-flex rounded-md">
                                                <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition">
                                                    {{ $page.props.auth.user.name }}
                                                    <svg
                                                        class="ml-2 -mr-0.5 h-4 w-4"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor"
                                                    >
                                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>

                                        <template #content>
                                            <!-- Account Management -->
                                            <div class="block px-4 py-2 text-xs text-gray-400">
                                                Manage Account
                                            </div>

                                            <JetDropdownLink :href="route('profile.show')">
                                                Profile
                                            </JetDropdownLink>

                                            <JetDropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">
                                                API Tokens
                                            </JetDropdownLink>

                                            <div class="border-t border-gray-100" />

                                            <!-- Authentication -->
                                            <form @submit.prevent="logout">
                                                <JetDropdownLink as="button">
                                                    Log Out
                                                </JetDropdownLink>
                                            </form>
                                        </template>
                                    </JetDropdown>
                                </div>
                            </div>

                            <!-- Hamburger -->
                            <div class="-mr-2 flex items-center sm:hidden">
                                <button class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition" @click="showingNavigationDropdown = ! showingNavigationDropdown">
                                    <svg
                                        class="h-6 w-6"
                                        stroke="currentColor"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 6h16M4 12h16M4 18h16"
                                        />
                                        <path
                                            :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Responsive Navigation Menu -->
                    <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">
                        <div class="pt-2 pb-3 space-y-1">
                            <JetResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                Projects
                            </JetResponsiveNavLink>
                        </div>

                        <!-- Responsive Settings Options -->
                        <div class="pt-4 pb-1 border-t border-gray-200">
                            <div class="flex items-center px-4">
                                <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 mr-3">
                                    <img class="h-10 w-10 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                                </div>

                                <div>
                                    <div class="font-medium text-base text-gray-800">
                                        {{ $page.props.auth.user.name }}
                                    </div>
                                    <div class="font-medium text-sm text-gray-500">
                                        {{ $page.props.auth.user.email }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 space-y-1">
                                <JetResponsiveNavLink :href="route('profile.show')" :active="route().current('profile.show')">
                                    Profile
                                </JetResponsiveNavLink>

                                <JetResponsiveNavLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')" :active="route().current('api-tokens.index')">
                                    API Tokens
                                </JetResponsiveNavLink>

                                <!-- Authentication -->
                                <form method="POST" @submit.prevent="logout">
                                    <JetResponsiveNavLink as="button">
                                        Log Out
                                    </JetResponsiveNavLink>
                                </form>

                                <!-- Team Management -->
                                <template v-if="$page.props.jetstream.hasTeamFeatures">
                                    <div class="border-t border-gray-200" />

                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                        Manage Team
                                    </div>

                                    <!-- Team Settings -->
                                    <JetResponsiveNavLink :href="route('teams.show', $page.props.auth.user.current_team)" :active="route().current('teams.show')">
                                        Team Settings
                                    </JetResponsiveNavLink>

                                    <JetResponsiveNavLink v-if="$page.props.jetstream.canCreateTeams" :href="route('teams.create')" :active="route().current('teams.create')">
                                        Create New Team
                                    </JetResponsiveNavLink>

                                    <div class="border-t border-gray-200" />

                                    <!-- Team Switcher -->
                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                        Switch Teams
                                    </div>

                                    <template v-for="team in $page.props.auth.user.all_teams" :key="team.id">
                                        <form @submit.prevent="switchToTeam(team)">
                                            <JetResponsiveNavLink as="button">
                                                <div class="flex items-center">
                                                    <svg
                                                        v-if="team.id == $page.props.auth.user.current_team_id"
                                                        class="mr-2 h-5 w-5 text-green-400"
                                                        fill="none"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    ><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    <div>{{ team.name }}</div>
                                                </div>
                                            </JetResponsiveNavLink>
                                        </form>
                                    </template>
                                </template>
                            </div>
                        </div>
                    </div>
                </nav>
                <main class="max-w-8xl py-4">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
