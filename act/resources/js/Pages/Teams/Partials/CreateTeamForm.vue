<template>
    <jet-form-section @submitted="createTeam">
        <template #title>
            Team Details
        </template>

        <template #description>
            Create a new team to collaborate with others on projects.
        </template>

        <template #form>
            <div class="col-span-6">
                <jet-label value="Team Owner" />

                <div class="flex items-center mt-2">
                    <img class="object-cover w-12 h-12 rounded-full" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">

                    <div class="ml-4 leading-tight">
                        <div>{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm text-gray-700">{{ $page.props.auth.user.email }}</div>
                    </div>
                </div>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <jet-label for="name" value="Team Name" />
                <jet-input id="name" type="text" class="block w-full mt-1" v-model="form.name" autofocus />
                <jet-input-error :message="form.errors.name" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <jet-button :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Create
            </jet-button>
        </template>
    </jet-form-section>
</template>
<script setup>
import JetButton from '@/Jetstream/Button.vue';
import JetFormSection from '@/Jetstream/FormSection.vue';
import JetInput from '@/Jetstream/Input.vue';
import JetInputError from '@/Jetstream/InputError.vue';
import JetLabel from '@/Jetstream/Label.vue';
import { useForm } from '@inertiajs/vue3';
import Helpers from '../../../Mixins/Helpers';

const form = useForm({
    name: ''
});

const { toast } = Helpers();

const createTeam = () => {
    form.post(route('teams.store'), {
        errorBag: 'createTeam',
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Team successfully created");
        }
    });
};
</script>
