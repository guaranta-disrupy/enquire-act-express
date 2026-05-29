<template>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center">
        <ExternalRedirectProgress
            :is-visible="true"
            :text="redirectText"
            :target-url="redirectUrl"
            :use-dynamic-loading="useDynamicLoading"
            @redirect="handleRedirect"
        />
    </div>
</template>

<script setup>
import { defineProps } from "vue";
import ExternalRedirectProgress from "../Components/ExternalRedirectProgress.vue";

const props = defineProps({
    redirectUrl: {
        type: String,
        required: true,
    },
    redirectText: {
        type: String,
        default: "Redirecting...",
    },
    useDynamicLoading: {
        type: Boolean,
        default: true,
    },
});

const handleRedirect = (url) => {
    window.location.href = url;
};
</script>
