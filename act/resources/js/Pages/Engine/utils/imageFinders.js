import { BASE_PATH } from "@/utils/basePath";
export function findImageUrls(obj, isEditorMode = false) {
    let imageUrls = new Set(); // Using a Set to store unique URLs

    // Regex to match common image file extensions directly
    const directImageUrlRegex = /\.(jpeg|jpg|gif|png|svg|webp)$/i;
    // Regex to extract 'src' attribute from <img> tags in HTML.
    // It handles attributes with double quotes, single quotes, or no quotes.
    const htmlImageSrcRegex = /<img[^>]+src=(?:"([^"]*)"|'([^']*)'|([^ >]*))/gi;
    // Regex to find image URLs within JSON string values (e.g., "url": "path/to/image.jpg")
    const jsonImageUrlRegex =
        /"(https?:\/\/[^"'>]+?\.(?:jpeg|jpg|gif|png|svg|webp))"/gi;

    // Helper function to wrap cross-origin URLs through proxy
    function toProxyIfCrossOrigin(url) {
        if (!isEditorMode) {
            return url;
        }
        try {
            const u = new URL(url, window.location.origin);
            if (u.origin !== window.location.origin) {
                return `${BASE_PATH}/gallery-proxy?u=${encodeURIComponent(u.href)}`;
            }
            return u.href;
        } catch (_) {
            return url;
        }
    }

    function traverse(currentObj, isEditorMode) {
        if (typeof currentObj !== "object" || currentObj === null) {
            return;
        }

        // Handle arrays by iterating over their elements
        if (Array.isArray(currentObj)) {
            currentObj.forEach((item) => traverse(item));
        } else {
            // Handle objects by iterating over their properties
            for (const key in currentObj) {
                if (Object.prototype.hasOwnProperty.call(currentObj, key)) {
                    const value = currentObj[key];

                    if (typeof value === "string") {
                        // 1. Check for direct image URLs (strings that end with image extensions)
                        if (directImageUrlRegex.test(value)) {
                            imageUrls.add(toProxyIfCrossOrigin(value));
                        }

                        // 2. Check for image URLs embedded in HTML strings
                        let match;
                        while (
                            (match = htmlImageSrcRegex.exec(value)) !== null
                        ) {
                            // The URL can be in one of three capture groups
                            const url = match[1] || match[2] || match[3];
                            if (url) {
                                imageUrls.add(toProxyIfCrossOrigin(url));
                            }
                        }

                        // 3. Check for image URLs in JSON-like strings
                        while (
                            (match = jsonImageUrlRegex.exec(value)) !== null
                        ) {
                            imageUrls.add(toProxyIfCrossOrigin(match[1]));
                        }
                    } else if (typeof value === "object") {
                        traverse(value); // Recurse into nested objects/arrays
                    }
                }
            }
        }
    }

    traverse(obj);
    return Array.from(imageUrls); // Convert Set to Array for easier use
}
