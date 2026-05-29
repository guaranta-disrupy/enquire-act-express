<template>
    <jet-action-section>
        <template #title>
            Account Status
        </template>

        <template #description>
            Activate/Deactivate account
        </template>

        <template #content>
            <div v-if="user.is_active === true">
                <div class="max-w-xl text-sm text-gray-600">
                    Once your account is deactivated, all of its resources and data will be temporarily unaccessible. To gain access to the account you may need to activate it again.
                </div>

                <div class="mt-5">
                    <SecondaryButton class="bg-red-600 text-white" @click.native="confirmingStatusChange=true">
                        deactivate
                    </SecondaryButton>
                </div>
            </div>

            <div v-else>
                <div class="max-w-xl text-sm text-gray-600">
                    Once your account is activated, all of its resources and data will be accessible again by the account owner. Please proceed with caution.
                </div>

                <div class="mt-5">
                    <!-- <jet-button variant="success" @click.native="confirmingStatusChange=true">
                        activate
                    </jet-button> -->
                    <SecondaryButton class="bg-green-600 text-white" @click.native="confirmingStatusChange=true">
                        activate
                    </SecondaryButton>
                </div>
            </div>
            <confirm-dialog :show="confirmingStatusChange" @cancelled="confirmingStatusChange=false" @confirmed="changeStatus">
                <template #body>
                    Are you sure you want to <strong>
                        <span v-if="user.is_active === true">
                            DEACTIVATE
                        </span>
                        <span v-else>
                            ACTIVATE
                        </span>
                        </strong>
                     this user?
                </template>
            </confirm-dialog>
        </template>
    </jet-action-section>
</template>

<script setup>
import JetActionSection from '@/Components/ActionSection.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ConfirmDialog from '@/Components/Confirm.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Helpers from '../../Mixins/Helpers';

const props = defineProps(['user']);

const confirmingStatusChange = ref(false);

const { toast } = Helpers();

const form = useForm({});

const changeStatus = () => {
    form.put(route('accounts.status', props.user.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmingStatusChange.value = false;
            toast.success("Status successfully changed");
        }
    })
};
</script>