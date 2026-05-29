<template>
    <jet-action-section class="mt-10 sm:mt-0">
        <template #title>
            Teams
        </template>

        <template #description>
            All the teams that the user have joined.
        </template>

        <template #content>
            <div class="space-y-6">
                <div class="flex items-center justify-between" v-for="team in teams" :key="team.id">
                    <div class="flex items-center">
                        <div class="ml-4">
                            {{ team.name }}
                        </div>
                    </div>
                    <div class="flex items-center">
                        <button class="cursor-pointer ml-6 text-sm text-red-500 focus:outline-none" @click="leaveDialog(team.id)">
                            Remove
                        </button>
                    </div>
                </div>
                <span class="text-gray-500" v-if="teams.length == 0">
                    This user doesn't belong to any outside team.
                </span>

            </div>
            <confirm-dialog :show="confirmTeamRemoval" @cancelled="confirmTeamRemoval=false" @confirmed="leaveTeam">
                <template #body>
                    Are you sure you want to remove this user from this team?
                </template>
            </confirm-dialog>
        </template>
    </jet-action-section>
</template>

<script setup>
import JetActionSection from '@/Components/ActionSection.vue';
import ConfirmDialog from '@/Components/Confirm.vue';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps(['teams', 'user']);

const confirmTeamRemoval = ref(false);

const selectedTeamId = ref(null);

const leaveTeamForm = useForm({});

const leaveDialog = (id) => {
    selectedTeamId.value = id;
    confirmTeamRemoval.value = true;
};

const leaveTeam = () => {
    leaveTeamForm.delete(route('accounts.leave_team', [props.user.ud, selectedTeamId.value]), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            confirmTeamRemoval.value = false;
        }
    });
};
</script>
