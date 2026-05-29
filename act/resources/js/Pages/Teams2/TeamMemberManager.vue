<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import JetActionMessage from '@/Jetstream/ActionMessage.vue';
import JetActionSection from '@/Components/ActionSection.vue';
import SecondaryButtonVue from '../../Components/SecondaryButton.vue';
import JetConfirmationModal from '@/Jetstream/ConfirmationModal.vue';
import JetDangerButton from '@/Jetstream/DangerButton.vue';
import JetFormSection from '@/Components/FormSection.vue';
import JetInput from '@/Jetstream/Input.vue';
import JetInputError from '@/Jetstream/InputError.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue';
import JetSectionBorder from '@/Jetstream/SectionBorder.vue';

const props = defineProps({
    team: Object
});

// Variables
const addTeamMemberForm = useForm({
    email: '',
    role: null
});
const removeTeamMemberForm = useForm({});
const teamMemberBeingRemoved = ref(null);

// methods
const addTeamMember = () => {
    addTeamMemberForm.post(route('teams2.add', props.team), {
        errorBag: 'addTeamMember',
        preserveScroll: true,
        onSuccess: () => addTeamMemberForm.reset()
    });
};
const confirmTeamMemberRemoval = (teamMember) => {
    teamMemberBeingRemoved.value = teamMember;
};
const removeTeamMember = () => {
    removeTeamMemberForm.delete(route('teams2.remove', [props.team, teamMemberBeingRemoved.value]), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => teamMemberBeingRemoved.value = null,
    });
};

</script>

<template>
    <div>
        <JetSectionBorder />

        <JetFormSection @submitted="addTeamMember">
            <template #title>
                Add Team Member
            </template>

            <template #description>
                Add a new team member to your team, allowing them to collaborate with you.
            </template>

            <template #form>
                <div class="col-span-6">
                    <div class="max-w-xl text-sm text-gray-600">
                        Please provide the email address of the person you would like to add to this team. The email address must be associated with an existing account.
                    </div>
                </div>

                <!-- Member Email -->
                <div class="col-span-6 sm:col-span-4">
                    <JetLabel for="email" value="Email" />
                    <JetInput id="email" type="text" class="mt-1 block w-full" v-model="addTeamMemberForm.email" />
                    <JetInputError :message="addTeamMemberForm.errors.email" class="mt-2" />
                </div>
            </template>

            <template #actions>
                <JetActionMessage :on="addTeamMemberForm.recentlySuccessful" class="mr-3">
                    Added.
                </JetActionMessage>

                <SecondaryButtonVue :class="{ 'opacity-25': addTeamMemberForm.processing }" :disabled="addTeamMemberForm.processing">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-auto" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    &nbsp;Add
                </SecondaryButtonVue>
            </template>
        </JetFormSection>

        <div v-if="team.users.length > 0">
            <JetSectionBorder />

            <!-- Manage Team Members -->
            <JetActionSection class="mt-10 sm:mt-0">
                <template #title>
                    Team Member
                </template>

                <template #description>
                    All of the people that are part of this team.
                </template>

                <!-- Team Member List -->
                <template #content>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between" v-for="user in team.users" :key="user.id">
                            <div class="flex items-center">
                                <img class="w-8 h-8 rounded-full" :src="user.profile_photo_url" :alt="user.name">
                                <div class="ml-4">{{ user.name }}</div>
                            </div>

                            <div class="flex items-center">

                                <!-- Remove Team Member -->
                                <button class="cursor-pointer ml-6 text-sm text-red-500 focus:outline-none"
                                                    @click="confirmTeamMemberRemoval(user)">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </JetActionSection>
        </div>
        <JetConfirmationModal :show="teamMemberBeingRemoved" @close="teamMemberBeingRemoved = null">
            <template #title>
                Remove Team Member
            </template>
            <template #content>
                Are you sure you would like to remove this person from the team?
            </template>
            <template #footer>
                <JetSecondaryButton @click="teamMemberBeingRemoved = null">
                    Cancel
                </JetSecondaryButton>

                <JetDangerButton
                    class="ml-3"
                    :class="{ 'opacity-25': removeTeamMemberForm.processing }"
                    :disabled="removeTeamMemberForm.processing"
                    @click="removeTeamMember"
                >
                    Remove
                </JetDangerButton>
            </template>
        </JetConfirmationModal>
    </div>
</template>


<style lang="css" scoped>
</style>
