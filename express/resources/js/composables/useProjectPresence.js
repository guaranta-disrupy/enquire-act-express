import { ref, watch, computed } from 'vue';
import { usePresenceChannel } from './usePresenceChannel';

/**
 * Composable specifically for project presence tracking
 * Wraps usePresenceChannel with project-specific logic
 * 
 * @param {Object|Ref} project - Project object or ref containing project data
 * @param {Object} options - Additional options
 * @returns {Object} - Project presence state and methods
 */
export function useProjectPresence(project, options = {}) {
    const {
        showJoinNotifications = true,
        showLeaveNotifications = false,
        autoJoin = true,
    } = options;

    // Determine channel name based on project
    const channelName = computed(() => {
        const proj = project.value || project;
        if (proj && proj.id) {
            return `project.${proj.id}`;
        }
        return null;
    });

    const projectId = computed(() => {
        const proj = project.value || project;
        return proj?.id || null;
    });

    console.log('[ProjectPresence] Channel name:', channelName.value, 'Project:', project);

    // Setup presence channel with callbacks
    const presence = usePresenceChannel(
        channelName.value || 'project.0',
        {
            autoJoin: autoJoin && !!channelName.value,
            onJoining: (user) => {
                console.log('[ProjectPresence] User joined:', user);
                if (showJoinNotifications && window.toast) {
                    window.toast.info(`${user.name} joined the project`, {
                        timeout: 2000,
                    });
                }
            },
            onLeaving: (user) => {
                console.log('[ProjectPresence] User left:', user);
                if (showLeaveNotifications && window.toast) {
                    window.toast.info(`${user.name} left the project`, {
                        timeout: 2000,
                    });
                }
            },
            onError: (error) => {
                console.error('[ProjectPresence] Error:', error);
                if (window.toast) {
                    window.toast.error('Connection error. Real-time updates may be delayed.');
                }
            },
        }
    );

    return {
        ...presence,
        projectId,
        channelName,
    };
}

