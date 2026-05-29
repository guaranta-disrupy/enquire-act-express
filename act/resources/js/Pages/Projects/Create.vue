<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import CreateProjectForm from './CreateProjectForm.vue';
</script>

<template>
    <app-layout title="New project">
        <template #header>
            <h2 class="font-medium text-lg text-gray-800 leading-tight">
                Create Project
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <create-project-form />
            </div>
        </div>
    </app-layout>
</template>