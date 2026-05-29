<template>
    <div>
        <Variables2 :project="project" :survey="survey" />
    </div>
</template>

<script setup>
import Variables2 from '../Variables2/Index.vue'

const props = defineProps({
    project: {
        type: Object,
        required: true
    },
    survey: {
        type: Object,
        required: true
    }
})



</script>

<style scoped>

</style>