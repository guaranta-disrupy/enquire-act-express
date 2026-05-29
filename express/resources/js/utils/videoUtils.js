import { usePage } from "@inertiajs/vue3";

/**
 * Generate a cached video URL with proper headers
 * @param {string} path - Video path (can be relative or full URL)
 * @returns {string} Cached video URL
 */
export const videoWithHeaders = (path) => {
    const page = usePage();
    // URL-encode the path to handle full URLs and special characters
    return page.props.app_url + '/video?path=' + encodeURIComponent(path);
};

/**
 * Alias for videoWithHeaders (camelCase version)
 */
export const VideoWithHeaders = videoWithHeaders;

/**
 * Default export
 */
export default videoWithHeaders;

