import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import { usePresenceChannel } from './usePresenceChannel';

/**
 * Composable specifically for questionnaire presence tracking
 * Wraps usePresenceChannel with questionnaire-specific logic
 * 
 * Simple auth: Express to Express only - authentication is handled automatically
 * via session cookies. Echo.private() handles auth automatically.
 * 
 * @param {Object|Ref} questionnaire - Questionnaire object or ref containing questionnaire data
 * @param {Object} options - Additional options
 * @returns {Object} - Questionnaire presence state and methods
 */
export function useQuestionnairePresence(questionnaire, options = {}) {
    const {
        showJoinNotifications = true,
        showLeaveNotifications = false,
        autoJoin = true,
    } = options;

    // Determine channel name based on questionnaire code
    const channelName = computed(() => {
        const q = questionnaire.value || questionnaire;
        if (q && q.code) {
            return `questionnaire.${q.code}`;
        }
        return null;
    });

    const questionnaireCode = computed(() => {
        const q = questionnaire.value || questionnaire;
        return q?.code || null;
    });

    const questionnaireId = computed(() => {
        const qValue = questionnaire?.value ?? questionnaire;
        return qValue?.id ?? qValue?.questionnaire_id ?? qValue?.questionnaireId ?? null;
    });

    // Local state for remote change tracking
    const hasRemoteChanges = ref(false);
    const lastRemoteChange = ref(null);

    // Setup presence channel with callbacks
    const presence = usePresenceChannel(
        channelName.value || 'questionnaire.unknown',
        {
            autoJoin: autoJoin && !!channelName.value,
            onJoining: (user) => {
                if (showJoinNotifications && window.toast) {
                    window.toast.info(`${user.name} is now editing this questionnaire`, {
                        timeout: 2000,
                    });
                }
            },
            onLeaving: (user) => {
                if (showLeaveNotifications && window.toast) {
                    window.toast.info(`${user.name} stopped editing`, {
                        timeout: 2000,
                    });
                }
            },
            onError: (error) => {
                console.error('[QuestionnairePresence] Error:', error);
                if (window.toast) {
                    window.toast.error('Connection error. Real-time updates may be delayed.');
                }
            },
        }
    );

    const { currentUser } = presence;

    // Private updates channel subscription (server-broadcasted)
    let updatesChannel = null;

    const cleanupUpdatesChannel = () => {
        if (updatesChannel && Echo && questionnaireId.value) {
            try {
                Echo.leave(`questionnaire.${questionnaireId.value}.updates`);
            } catch (e) {
                console.error('[QuestionnairePresence] Failed to leave updates channel:', e);
            }
        }
        updatesChannel = null;
    };

    /**
     * Subscribe to private updates channel
     * Simple auth: Echo.private() handles authentication automatically via session cookies
     */
    const subscribeToUpdates = () => {
        if (updatesChannel) {
            return;
        }

        const qid = questionnaireId.value;
        if (!qid || !Echo) {
            return;
        }

        try {
            // Echo.private() automatically handles auth via session cookies (Express to Express)
            updatesChannel = Echo.private(`questionnaire.${qid}.updates`);

            updatesChannel.listen('.questionnaire.updated', (payload) => {
                if (!payload) {
                    return;
                }

                // Ensure event is for this questionnaire
                const qidFromEvent = payload.questionnaire_id || null;
                if (qidFromEvent && qidFromEvent !== qid) {
                    return;
                }

                // Ignore notifications for the user who triggered the update
                const triggeringUserId = payload.user?.id ?? payload.user_id ?? null;
                const currentUserId = currentUser?.value?.id ?? currentUser?.id ?? null;

                if (triggeringUserId && currentUserId && triggeringUserId === currentUserId) {
                    return;
                }

                hasRemoteChanges.value = true;
                lastRemoteChange.value = payload;

                const userName = payload.user?.name || 'Someone';

                if (window.toast) {
                    window.toast.info(
                        `${userName} updated this questionnaire. Click "Sync" to see the latest changes.`,
                        { timeout: 5000 }
                    );
                }

                // Bubble up to any listeners (e.g., Questions page) via a window event
                try {
                    window.dispatchEvent(new CustomEvent('questionnaire-remote-updated', {
                        detail: {
                            questionnaire_id: payload.questionnaire_id,
                            questionnaire_code: questionnaireCode.value,
                            update_type: payload.update_type,
                            data: payload.data,
                            user: payload.user,
                            timestamp: payload.timestamp,
                        },
                    }));
                } catch (e) {
                    console.error('[QuestionnairePresence] Failed to dispatch event:', e);
                }
            });

            updatesChannel.error((error) => {
                console.error('[QuestionnairePresence] Updates channel error:', error);
            });
        } catch (e) {
            console.error('[QuestionnairePresence] Failed to subscribe to updates channel:', e);
        }
    };

    onMounted(() => {
        subscribeToUpdates();
    });

    watch(questionnaireId, (newVal, oldVal) => {
        if (newVal !== oldVal) {
            // Questionnaire changed; cleanup previous channel and subscribe again
            cleanupUpdatesChannel();
            subscribeToUpdates();
        } else if (newVal && !updatesChannel) {
            subscribeToUpdates();
        }
    });

    onUnmounted(() => {
        cleanupUpdatesChannel();
    });

    return {
        ...presence,
        questionnaireCode,
        channelName,
        hasRemoteChanges,
        lastRemoteChange,
    };
}

