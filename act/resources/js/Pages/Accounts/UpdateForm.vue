<template>
    <jet-form-section @submitted="updateUser">
        <template #title>
            Profile
        </template>

        <template #description>
            User basic information
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <CustomInput v-model="form.email" label="Email" />
                <jet-input-error :message="form.errors.email" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <CustomInput v-model="form.name" label="Name" />
                <jet-input-error :message="form.errors.name" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <CustomInput type="date" v-model="form.valid_until" label="Account valid until" />
                <!-- <jet-input-error :message="form.error('quota.max')" class="mt-2" /> -->
            </div>

            <div class="col-span-4">
                <CustomSelect v-model="form.role" label="Role">
                    <option value="user">Standard User</option>
                    <option value="admin">Administrator</option>
                    <option value="root">Root Admin</option>
                </CustomSelect>
            </div>

        </template>

        <template #actions>
            <jet-action-message :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </jet-action-message>

            <SecondaryButton @click="updateUser" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>&nbsp;Save
            </SecondaryButton>
        </template>
    </jet-form-section>
</template>
<script setup>

import JetActionMessage from '@/Jetstream/ActionMessage.vue';
import JetFormSection from '@/Components/FormSection.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import CustomInput from '@/Components/Input.vue';
import JetInputError from '@/Jetstream/InputError.vue';
import CustomSelect from '@/Components/Select.vue';

import { useForm } from '@inertiajs/vue3';
import { onBeforeMount } from 'vue'

const props = defineProps(['user']);

const form = useForm({
    email: props.user.email,
    name: props.user.name,
    valid_until: props.user.expired_at,
    role: props.user.role,
});

onBeforeMount(() => {
	form.valid_until = moment(form.valid_until ?? new Date(new Date().setFullYear(new Date().getFullYear() + 1))).tz(timezone).format("YYYY-MM-DD")
});

const updateUser = () => {
    form.put(route('accounts.update', props.user.id), {
        preserveScroll: true
    });
};
</script>
