<script setup>
import AppLayout from "@/Layouts/AppLayout.vue"
import CreateGroupForm from './CreateGroupForm.vue'

const props = defineProps(['project']);
</script>

<template>
    <AppLayout title="New group">
        <template #header>
            <h2 class="font-medium text-lg text-gray-800 leading-tight">
                Create Group
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <CreateGroupForm :project="project" />
            </div>
        </div>
    </AppLayout>
</template>