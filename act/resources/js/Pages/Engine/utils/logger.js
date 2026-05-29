import { ref } from "vue";

export function useLogger() {
    function _log(...args) {
        if (import.meta.env.VITE_APP_DEBUG === "true") {
            console.log(...args);
        }
    }
    return {
        _log,
    };
}
