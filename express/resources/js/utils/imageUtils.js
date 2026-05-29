import { usePage } from "@inertiajs/vue3";

/**
 * Generate a cached image URL with proper headers
 * @param {string} path - Image path (can be relative or full URL)
 * @returns {string} Cached image URL or empty string if path is invalid
 */
export const imageWithHeaders = (path) => {
    // Return empty string if path is null, undefined, or empty
    if (!path || path === '' || path === null || path === undefined) {
        return '';
    }
    
    const page = usePage();
    // URL-encode the path to handle full URLs and special characters
    return page.props.app_url + '/image?path=' + encodeURIComponent(path);
};

/**
 * Alias for imageWithHeaders (camelCase version)
 */
export const ImageWithHeaders = imageWithHeaders;

/**
 * Default export
 */
export default imageWithHeaders;

