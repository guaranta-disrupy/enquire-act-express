<script setup>
import JetActionMessage from '@/Jetstream/ActionMessage.vue'
import JetFormSection from '@/Jetstream/FormSection.vue'
import JetInput from '@/Jetstream/Input.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import JetLabel from '@/Jetstream/Label.vue'
import ActionButton from '@/Components/ActionButton.vue'
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps(['project']);

const form = useForm({
    name: ''
});

const createGroup = () => {
    form.post(route('groups.store', props.project), {
        preserveScroll: true,
        errorBag: 'createGroup',
    });
};

</script>

<template>
    <jet-form-section @submitted="createGroup">
        <template #title>
            Group Details
        </template>
        <template #description>
            Create new group for <em>{{ project.name }}</em>
        </template>

        <template #form>
            <div class="col-span-6">
                <jet-label value="Group Name" />
                <jet-input type="text" class="mt-1 block w-full" v-model="form.name" autofocus />
                <jet-input-error :message="form.errors.name" class="mt-2" />
            </div>
        </template>
        <template #actions>
            <jet-action-message :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </jet-action-message>

            
            <Link :href="route('groups.index', project)">
                <action-button class="mr-4" variant="primary" size="md">
                    <i class="fa fa-arrow-left"></i>&nbsp;back
                </action-button>
            </Link>
            

            <action-button variant="primary" size="md" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="createGroup">
                <i class="fa fa-floppy-o"></i>&nbsp;save
            </action-button>
        </template>
    </jet-form-section>
</template>