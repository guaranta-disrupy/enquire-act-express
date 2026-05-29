<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CreateTeamForm from '@/Pages/Teams/Partials/CreateTeamForm.vue';
</script>

<template>
    <AppLayout title="Create Team">
        <template #topbar>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Team
            </h2>
        </template>

        <template #content>
            <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <CreateTeamForm />
            </div>
        </div>
        </template>
    </AppLayout>
</template>
