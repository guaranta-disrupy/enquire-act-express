<template>
    <jet-form-section @submitted="registerUser">
        <template #title>
            User Details
        </template>

        <template #description>
            Register new user
        </template>

        <template #form>
            <div class="col-span-4">
                <jet-label for="name" value="Name" />
                <jet-input id="name" type="text" class="mt-1 block w-full" v-model="form.name" placeholder="Enter name" autofocus />
                <JetInputError :message="form.errors.name" class="mt-2" />
            </div>

            <div class="col-span-4">
                <jet-label for="email" value="Email" />
                <jet-input id="email" type="text" class="mt-1 block w-full" v-model="form.email" placeholder="Enter email" />
                <JetInputError :message="form.errors.email" class="mt-2" />
            </div>

            <div class="col-span-4">
                <jet-label for="password" value="Password" />
                <jet-input id="password" type="password" class="mt-1 block w-full" v-model="form.password" placeholder="Enter password" />
                <JetInputError :message="form.errors.password" class="mt-2" />
            </div>
            <div class="col-span-4">
                <jet-label for="password_confirmation" value="Re-type password" />
                <jet-input id="password_confirmation" type="password" class="mt-1 block w-full" v-model="form.password_confirmation" placeholder="Re-type password" />
                <JetInputError :message="form.errors.password_confirmation" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <jet-label value="Account valid until" />
                <jet-input type="date" class="mt-1 block w-full" v-model="form.valid_until" />
                <!-- <JetInputError :message="form.error('quota.max')" class="mt-2" /> -->
            </div>

            <div class="col-span-4">
                <jet-label value="Role" />
                <select class="form-select mt-1 block w-full" v-model="form.role">
                    <option value="user">Standard User</option>
                    <option value="admin">Administrator</option>
                    <option value="root">Root Admin</option>
                </select>
            </div>
        </template>

        <template #actions>
            <jet-action-message :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </jet-action-message>

            <SecondaryButtonVue :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click.prevent="registerUser">
                Register
            </SecondaryButtonVue>
        </template>
    </jet-form-section>
</template> 

<script setup>
import JetActionMessage from '@/Jetstream/ActionMessage.vue';
import JetFormSection from '@/Components/FormSection.vue';
import JetInput from '@/Jetstream/Input.vue';
import JetInputError from '@/Jetstream/InputError.vue';
import JetLabel from '@/Jetstream/Label.vue';
import SecondaryButtonVue from '../../Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    valid_until: '',
    role: 'user'
});

const toast = useToast();

const registerUser = () => {
    form.post(route('accounts.store'), {
        preserveScroll: true,
        errorBag: 'registerUser',
        onSuccess: () => {
            toast.success("Account successfully registered")
        }
    })
};
</script>
