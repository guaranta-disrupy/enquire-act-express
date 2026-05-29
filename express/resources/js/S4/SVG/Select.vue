<script setup>
import SvgCopy from '@/S4/SVG/Copy.vue';
import SvgEdit from '@/S4/SVG/Edit.vue';
import SvgDelete from '@/S4/SVG/Delete.vue';
import SvgUpDown from '@/S4/SVG/UpDown.vue';

const props = defineProps({
        icon: {
            default: "close"
        }
    })
</script>
<template>
    <svg-copy v-if="icon == 'copy'" />
    <svg-edit v-else-if="icon == 'edit'" />
    <svg-delete v-else-if="icon == 'delete'" />
    <svg-up-down v-else />
</template>
