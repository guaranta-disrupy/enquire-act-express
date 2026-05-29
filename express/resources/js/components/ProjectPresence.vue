<template>
    <div class="project-presence">
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
import { useProjectPresence } from '../composables/useProjectPresence';
import ActiveUsers from './ActiveUsers.vue';

const props = defineProps({
    project: {
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

// Use project presence composable
const {
    activeUsers,
    activeUserCount,
    otherUsers,
    isConnected,
    isJoining,
    error,
    currentUser,
} = useProjectPresence(toRefs(props).project, {
    showJoinNotifications: props.showJoinNotifications,
    showLeaveNotifications: props.showLeaveNotifications,
    autoJoin: true,
});
</script>

<style scoped>
.project-presence {
    /* Add any additional custom styles here */
}
</style>

