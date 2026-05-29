import { ref, watch } from 'vue';

/**
 * Composable for chatbot state persistence
 */

// Storage key constants
const SIDEBAR_STATE_KEY = 'act-chatbot-sidebar-state';

/**
 * Persist state to localStorage
 * @param {string} key - Storage key
 * @param {any} value - Value to persist
 * @param {string} errorMessage - Error message for failures
 */
const persistState = (key, value, errorMessage) => {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (error) {
        console.warn(errorMessage, error);
    }
};

/**
 * Restore state from localStorage
 * @param {string} key - Storage key
 * @param {any} defaultValue - Default value if not found
 * @param {string} errorMessage - Error message for failures
 * @returns {any} Restored value or default
 */
const restoreState = (key, defaultValue, errorMessage) => {
    try {
        const stored = localStorage.getItem(key);
        if (stored !== null) {
            return JSON.parse(stored);
        }
    } catch (error) {
        console.warn(errorMessage, error);
    }
    return defaultValue;
};

/**
 * Persist sidebar state
 * @param {boolean} isOpen - Whether sidebar is open
 */
const persistSidebarState = (isOpen) => {
    persistState(SIDEBAR_STATE_KEY, isOpen, 'Failed to persist sidebar state');
};

/**
 * Restore sidebar state
 * @returns {boolean} Whether sidebar should be open
 */
const restoreSidebarState = () => {
    return restoreState(SIDEBAR_STATE_KEY, false, 'Failed to restore sidebar state');
};

/**
 * Create a reactive sidebar state ref that automatically persists changes
 * @returns {import('vue').Ref<boolean>} Reactive ref for sidebar state
 */
export const useSidebarState = () => {
    const sidebarState = ref(restoreSidebarState());
    
    // Automatically persist changes when sidebar state changes
    watch(sidebarState, (newValue) => {
        persistSidebarState(newValue);
    });
    
    return sidebarState;
};
