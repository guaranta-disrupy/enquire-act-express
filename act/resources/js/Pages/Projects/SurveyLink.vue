<template>
	<jet-action-section>
		<template #title>
			Project Link
		</template>

		<template #description>
			You can copy this link to manually distribute it to your friends and family. 
		</template>

		<template #content>
			<div class="w-full text-sm text-gray-600">
				<clipboard :value="$page.props.app.url + '/launch/?code=' + project.code">
					<template #label>
						Random ID
					</template>
				</clipboard>
				<clipboard :value="$page.props.app.url + '/launch/?code=' + project.code + '&id=[id_here]'">
					<template #label>
						With Assigned ID
					</template>
				</clipboard>
			</div>
		</template>
	</jet-action-section>
</template>
<script setup>
import JetActionSection from '@/Components/ActionSection.vue';
import Clipboard from '@/Components/Clipboard.vue';

const props = defineProps(['project']);
</script>