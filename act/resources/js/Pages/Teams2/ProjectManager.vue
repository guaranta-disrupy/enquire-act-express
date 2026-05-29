<template>
    <div>
        <jet-section-border />
        <div v-if="team.projects.length > 0">
            <jet-action-section class="mt-10 sm:mt-0">
                <template #title>
                    Projects
                </template>

                <template #description>
                    All of the projects that belong to this team.
                </template>

                <template #content>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between" v-for="(project, key) in team.projects" :key="project.id">
                            <div class="flex items-center">
                                <div class="ml-4">
                                    <small>
                                        {{ key + 1 }}. {{ project.name }}
                                    </small>
                                </div>
                            </div>

                            <div class="flex items-center">

                                <!-- Remove Team Member -->
                                <!-- <button class="cursor-pointer ml-6 text-sm text-red-500 focus:outline-none">
                                    Delete
                                </button> -->
                            </div>
                        </div>
                    </div>
                </template>
            </jet-action-section>
        </div>
    </div>
</template>
<script setup>
import JetActionSection from '@/Components/ActionSection.vue';
import JetSectionBorder from '@/Jetstream/SectionBorder.vue';

const props = defineProps(['team']);
</script>
