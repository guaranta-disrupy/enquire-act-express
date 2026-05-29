import { ref, watch, onUnmounted } from "vue";

/**
 * A Vue composable to dynamically inject a script into the document head.
 * It automatically creates and cleans up the script on unmount.
 * @param {import('vue').Ref<string> | () => string} jsSource - A ref or a getter function that returns the JS string.
 */
export function useDynamicJs(jsSource) {
    const scriptElement = ref(null);

    watch(
        jsSource,
        (newJs) => {
            // Remove old script element if it exists
            if (scriptElement.value) {
                scriptElement.value.remove();
                scriptElement.value = null;
            }

            if (newJs) {
                const script = document.createElement("script");
                script.textContent = newJs;
                document.head.appendChild(script);
                scriptElement.value = script;
            }
        },
        { immediate: true }
    );

    onUnmounted(() => {
        if (scriptElement.value) {
            scriptElement.value.remove();
        }
    });
}
