import { ref, onMounted, onUnmounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Composable for managing presence channels
 * Tracks active users in a specific channel (project, team, questionnaire)
 * 
 * Simple auth: Express to Express only - authentication is handled automatically
 * via session cookies. No complex token management needed.
 * 
 * @param {string} channelName - The presence channel name (e.g., 'project.123')
 * @param {Object} options - Configuration options
 * @param {boolean} options.autoJoin - Automatically join on mount (default: true)
 * @param {Function} options.onJoining - Callback when a user joins
 * @param {Function} options.onLeaving - Callback when a user leaves
 * @param {Function} options.onError - Callback on error
 * @returns {Object} - Presence channel state and methods
 */
export function usePresenceChannel(channelName, options = {}) {
    const {
        autoJoin = true,
        onJoining = null,
        onLeaving = null,
        onError = null
    } = options;

    // State
    const activeUsers = ref([]);
    const isConnected = ref(false);
    const isJoining = ref(false);
    const error = ref(null);
    const channel = ref(null);
    const currentUser = computed(() => usePage().props.auth?.user || null);

    /**
     * Get count of active users (excluding current user)
     */
    const activeUserCount = computed(() => {
        if (!currentUser.value) return activeUsers.value.length;
        return activeUsers.value.filter(user => user.id !== currentUser.value.id).length;
    });

    /**
     * Get list of other users (excluding current user)
     */
    const otherUsers = computed(() => {
        if (!currentUser.value) return activeUsers.value;
        return activeUsers.value.filter(user => user.id !== currentUser.value.id);
    });

    /**
     * Check if a specific user is present
     */
    const isUserPresent = (userId) => {
        return activeUsers.value.some(user => user.id === userId);
    };

    /**
     * Join the presence channel
     * Simple auth: Echo handles authentication automatically via session cookies
     */
    const join = () => {
        if (!Echo) {
            const errMsg = 'Echo is not initialized';
            error.value = errMsg;
            console.error('[Presence]', errMsg);
            if (onError) onError(new Error(errMsg));
            return;
        }

        if (channel.value) {
            console.warn('[Presence] Already joined channel:', channelName);
            return;
        }

        isJoining.value = true;
        error.value = null;

        try {
            // Echo.join() automatically handles auth via session cookies (Express to Express)
            channel.value = Echo.join(channelName);

            // Initial list of users already in the channel
            channel.value.here((users) => {
                activeUsers.value = users;
                isConnected.value = true;
                isJoining.value = false;
                console.log(`[Presence] Connected to ${channelName}, users:`, users.length);
            });

            // Someone joined the channel
            channel.value.joining((user) => {
                if (!activeUsers.value.find(u => u.id === user.id)) {
                    activeUsers.value.push(user);
                }
                console.log(`[Presence] User joined ${channelName}:`, user.name);
                if (onJoining) onJoining(user);
            });

            // Someone left the channel
            channel.value.leaving((user) => {
                const index = activeUsers.value.findIndex(u => u.id === user.id);
                if (index !== -1) {
                    activeUsers.value.splice(index, 1);
                }
                console.log(`[Presence] User left ${channelName}:`, user.name);
                if (onLeaving) onLeaving(user);
            });

            // Listen for errors (including auth errors)
            channel.value.error((err) => {
                const errMsg = err.message || 'Connection error';
                console.error(`[Presence] Error on ${channelName}:`, errMsg);
                error.value = errMsg;
                isConnected.value = false;
                isJoining.value = false;
                if (onError) onError(err);
            });

        } catch (err) {
            console.error('[Presence] Failed to join channel:', channelName, err);
            error.value = err.message || 'Failed to join channel';
            isJoining.value = false;
            if (onError) onError(err);
        }
    };

    /**
     * Leave the presence channel
     */
    const leave = () => {
        if (channel.value && Echo) {
            try {
                Echo.leave(channelName);
                console.log(`[Presence] Left channel: ${channelName}`);
            } catch (err) {
                console.error('[Presence] Failed to leave channel:', err);
            }
        }
        channel.value = null;
        activeUsers.value = [];
        isConnected.value = false;
        error.value = null;
    };

    /**
     * Listen to custom events on the presence channel
     */
    const listen = (eventName, callback) => {
        if (!channel.value) {
            console.warn('Cannot listen to event. Channel not joined yet.');
            return;
        }
        channel.value.listen(eventName, callback);
    };

    /**
     * Stop listening to a custom event
     */
    const stopListening = (eventName) => {
        if (!channel.value) return;
        channel.value.stopListening(eventName);
    };

    /**
     * Whisper a message to other users in the channel
     */
    const whisper = (eventName, data) => {
        if (!channel.value) {
            console.warn('Cannot whisper. Channel not joined yet.');
            return;
        }
        channel.value.whisper(eventName, data);
    };

    /**
     * Listen to whispers from other users
     */
    const listenForWhisper = (eventName, callback) => {
        if (!channel.value) {
            console.warn('Cannot listen for whisper. Channel not joined yet.');
            return;
        }
        channel.value.listenForWhisper(eventName, callback);
    };

    // Lifecycle
    onMounted(() => {
        if (autoJoin) {
            join();
        }
    });

    onUnmounted(() => {
        leave();
    });

    return {
        // State
        activeUsers,
        activeUserCount,
        otherUsers,
        isConnected,
        isJoining,
        error,
        currentUser,

        // Methods
        join,
        leave,
        listen,
        stopListening,
        whisper,
        listenForWhisper,
        isUserPresent,
    };
}

