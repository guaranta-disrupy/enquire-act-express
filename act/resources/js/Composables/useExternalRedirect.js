import { ref } from "vue";
import { redirectWithProgress } from "../utils/externalRedirect.js";

/**
 * Composable for handling external redirects with progress
 * @param {Object} defaultOptions - Default options for redirects
 * @returns {Object} - Methods and state for external redirects
 */
export const useExternalRedirect = (defaultOptions = {}) => {
    const isRedirecting = ref(false);
    const redirectText = ref("Redirecting...");

    /**
     * Redirect to an external URL with progress indicator
     * @param {string} url - The external URL to redirect to
     * @param {Object} options - Configuration options
     */
    const redirect = (url, options = {}) => {
        if (isRedirecting.value) return; // Prevent multiple redirects

        isRedirecting.value = true;
        redirectText.value =
            options.text || defaultOptions.text || "Redirecting...";

        const mergedOptions = {
            ...defaultOptions,
            ...options,
            text: redirectText.value,
        };

        redirectWithProgress(url, mergedOptions);
    };

    /**
     * Redirect to Fluent with progress indicator
     * @param {string|number} surveyId - The survey ID
     * @param {Object} options - Additional options
     */
    const redirectToFluent = (surveyId, options = {}) => {
        const fluentUrl =
            import.meta.env.VITE_APP_FLUENT_URL || process.env.APP_FLUENT_URL;
        const url = `${fluentUrl}/surveys/${surveyId}/components`;

        redirect(url, {
            text: "Redirecting to Fluent...",
            useDynamicLoading: true,
            ...options,
        });
    };

    /**
     * Cancel any pending redirect
     */
    const cancelRedirect = () => {
        isRedirecting.value = false;
    };

    return {
        isRedirecting,
        redirectText,
        redirect,
        redirectToFluent,
        cancelRedirect,
    };
};

export default useExternalRedirect;
