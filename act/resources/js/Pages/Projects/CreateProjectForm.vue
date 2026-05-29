<script setup>
import JetActionMessage from '@/Jetstream/ActionMessage.vue';
import JetFormSection from '@/Jetstream/FormSection.vue';
import JetInput from '@/Jetstream/Input.vue';
import JetInputError from '@/Jetstream/InputError.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue';
import JetButton from '@/Jetstream/Button.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    name: ''
});

const createProject = () => {
    form.post(route('projects.store'), {
        preserveScroll: true,
    });
};
</script>
<template>
    <JetFormSection @submitted="createProject">

        <template #title>
            Project Details
        </template>

        <template #description>
            Create a new project
        </template>

        <template #form>
            <div class="col-span-6">
                <JetLabel value="Project Owner" />

                <div class="flex items-center mt-2">
                    <img :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name" class="w-12 h-12 rounded-full object-cover">

                    <div class="ml-4 leading-tight">
                        <div>{{ $page.props.auth.user.name }}</div>
                        <div class="text-gray-700 text-sm">
                            {{ $page.props.auth.user.email }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <JetLabel for="name" value="Project Name" />
                <JetInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" autofocus />
                <JetInputError :message="form.errors.name" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <JetActionMessage :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </JetActionMessage>

            
            <Link :href="route('projects.index')">
                <JetSecondaryButton class="mr-4">
                    <i class="fa fa-arrow-left"></i>&nbsp;back
                </JetSecondaryButton>
            </Link>
            

            <JetButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                <i class="fa fa-floppy-o"></i>&nbsp;save
            </JetButton>
        </template>
        
    </JetFormSection>
</template>