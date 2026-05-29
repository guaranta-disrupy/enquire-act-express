<template>
    <div class="questionnaire-presence">
        <ActiveUsers
            :active-users="activeUsers"
            :current-user="currentUser"
            :active-user-count="activeUserCount"
            :is-connected="isConnected"
            :is-joining="isJoining"
            :error="error"
            :compact="compact"
        />
    </div>
</template>

<script setup>
import { toRefs } from 'vue';
import { useQuestionnairePresence } from '../composables/useQuestionnairePresence';
import ActiveUsers from './ActiveUsers.vue';

const props = defineProps({
    questionnaire: {
        type: Object,
        required: true,
    },
    compact: {
        type: Boolean,
        default: false,
    },
    showJoinNotifications: {
        type: Boolean,
        default: true,
    },
    showLeaveNotifications: {
        type: Boolean,
        default: false,
    },
});

// Use questionnaire presence composable
const {
    activeUsers,
    activeUserCount,
    otherUsers,
    isConnected,
    isJoining,
    error,
    currentUser,
} = useQuestionnairePresence(toRefs(props).questionnaire, {
    showJoinNotifications: props.showJoinNotifications,
    showLeaveNotifications: props.showLeaveNotifications,
    autoJoin: true,
});
</script>

<style scoped>
.questionnaire-presence {
    /* Add any additional custom styles here */
}
</style>

