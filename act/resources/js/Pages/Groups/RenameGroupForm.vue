<template>
	<jet-form-section @submitted="renameGroup">
		<template #title>
			Group details
		</template>

		<template #description>
			Rename group
		</template>

		<template #form>
			<div class="col-span-6">
				<jet-label value="Group Owner" />

                <div class="flex items-center mt-2">
                    <img :src="group.user.profile_photo_url" :alt="group.user.name" class="w-12 h-12 rounded-full object-cover">

                    <div class="ml-4 leading-tight">
                        <div>{{ group.user.name }}</div>
                        <div class="text-gray-700 text-sm">
                            {{ group.user.email }}
                        </div>
                    </div>
                </div>
			</div>

			<div class="col-span-6 sm:col-span-4">
				<jet-label for="name" value="Group Name" />
                <jet-input id="name" type="text" class="mt-1 block w-full" v-model="form.name" autofocus />
                <jet-input-error :message="form.errors.name" class="mt-2" />
			</div>
		</template>

		<template #actions>
			<jet-action-message class="mr-3" :on="form.recentlySuccessful">
				Saved.
			</jet-action-message>

			<jet-button :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
				<i class="fa fa-floppy-o"></i>&nbsp;save
			</jet-button>
		</template>
	</jet-form-section>
</template>

<script setup>
import JetActionMessage from '@/Jetstream/ActionMessage.vue';
import JetFormSection from '@/Jetstream/FormSection.vue';
import JetInput from '@/Jetstream/Input.vue';
import JetInputError from '@/Jetstream/InputError.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetButton from '@/Jetstream/Button.vue';

import { useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { useToast } from 'vue-toastification';

const props = defineProps(['group', 'project']);

const form = useForm({
	name: ''
});

const toast = useToast();

onMounted(() => {
	form.name = props.group.name;
})

const renameGroup = () => {
	form.put(route('groups.rename', [props.project, props.group]), {
		preserveScroll: true,
		onSuccess: () => {
			toast.success("Group successfully renamed")
		}
	});
};
</script>