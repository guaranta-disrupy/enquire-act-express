<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import DeleteTeamForm from '@/Pages/Teams2/Partials/DeleteTeamForm.vue';
    import JetSectionBorder from '@/Jetstream/SectionBorder.vue';
    import TeamMemberManager from '@/Pages/Teams2/Partials/TeamMemberManager.vue';
    import UpdateTeamNameForm from '@/Pages/Teams2/Partials/UpdateTeamNameForm.vue';

    defineProps({
    team: Object,
    availableRoles: Array,
    permissions: Object,
    });
</script>
<template>
    <app-layout>
        <template #topbar>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Team Settings
            </h2>
        </template>
        <template #content>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <update-team-name-form :team="team" :permissions="permissions" />

                <team-member-manager class="mt-10 sm:mt-0"
                            :team="team"
                            :available-roles="availableRoles"
                            :user-permissions="permissions" />

                <template v-if="permissions.canDeleteTeam && ! team.personal_team">
                    <jet-section-border />

                    <delete-team-form class="mt-10 sm:mt-0" :team="team" />
                </template>
            </div>
        </template>
    </app-layout>
</template>