<script setup>
import { useForm } from '@inertiajs/vue3';
import JetActionMessage from '@/Jetstream/ActionMessage.vue';
import JetFormSection from '@/Components/FormSection.vue';
import JetInput from '@/Jetstream/Input.vue';
import JetInputError from '@/Jetstream/InputError.vue';
import JetLabel from '@/Jetstream/Label.vue';
import SecondaryButtonVue from '../../Components/SecondaryButton.vue';

const props = defineProps({
    team: Object
});

const form = useForm({
    name: props.team.name
});

const updateTeamName = () => {
    form.put(route('teams2.update', props.team), {
        errorBag: 'updateTeamName',
        preserveScroll: true
    })
}
</script>

<template>
    <JetFormSection @submitted="updateTeamName">
        <template #title>
            Team Name
        </template>

        <template #description>
            The team's name and owner information.
        </template>

        <template #form>
            <!-- Team Owner Information -->
            <div class="col-span-6">
                <JetLabel value="Team Owner" />

                <div class="flex items-center mt-2">
                    <img class="w-12 h-12 rounded-full object-cover" :src="team.owner.profile_photo_url" :alt="team.owner.name">

                    <div class="ml-4 leading-tight">
                        <div>{{ team.owner.name }}</div>
                        <div class="text-gray-700 text-sm">{{ team.owner.email }}</div>
                    </div>
                </div>
            </div>

            <!-- Team Name -->
            <div class="col-span-6 sm:col-span-4">
                <JetLabel for="name" value="Team Name" />

                <JetInput id="name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.name" />

                <JetInputError :message="form.errors.name" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <JetActionMessage :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </JetActionMessage>

            <SecondaryButtonVue :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>&nbsp;Save
            </SecondaryButtonVue>
        </template>
    </JetFormSection>
</template>
