/**
 * Base path of the app when it is served behind a reverse proxy under a
 * sub-path (e.g. https://app.example.com/act). Derived from VITE_APP_URL at
 * build time; empty when the app is served from the domain root.
 */
export const BASE_PATH = (() => {
    try {
        return new URL(import.meta.env.VITE_APP_URL).pathname.replace(/\/+$/, "");
    } catch (_) {
        return "";
    }
})();

/**
 * Prefixes a root-relative path ("/foo") with BASE_PATH. Absolute URLs,
 * protocol-relative URLs and already-prefixed paths are returned untouched.
 */
export const withBase = (path) => {
    if (
        !BASE_PATH ||
        typeof path !== "string" ||
        !path.startsWith("/") ||
        path.startsWith("//") ||
        path === BASE_PATH ||
        path.startsWith(BASE_PATH + "/")
    ) {
        return path;
    }
    return BASE_PATH + path;
};

/** Absolute origin + base path, e.g. "https://app.example.com/act". */
export const appBase = () => window.location.origin + BASE_PATH;
