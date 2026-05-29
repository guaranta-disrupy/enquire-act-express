<template>
    <div>
        <!-- <ActLoadingBar :isVisible="loading" :progress="progress" /> -->
        <div
            v-if="loading"
            class="w-full h-full flex items-center justify-center fixed inset-0"
        >
            <TrialRedirect type="opening" />
        </div>
        <div v-else>
            <div v-show="false" class="cached-images-preview">
                <img
                    v-for="url in displaySampleImages"
                    :key="url"
                    :src="url"
                    alt="Cached Image"
                    style="
                        max-width: 100px;
                        height: auto;
                        margin: 8px;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                    "
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, defineProps, computed, watch } from "vue";
import { findImageUrls } from "../utils/imageFinders";
import TrialRedirect from "./trial/TrialRedirect.vue";
import { useLogger } from "../utils/logger.js";
import { ENGINE_FONT_FAMILIES } from "../utils/fontOptions.js";
const { _log } = useLogger();

// Define component props
const props = defineProps({
    dataObject: {
        type: Object,
        required: true,
    },
    isEditorMode: {
        type: Boolean,
        required: true,
    },
});

// Define emits
const emit = defineEmits(["loading-complete"]);

// Reactive state variables
const imageUrls = ref([]);
const loading = ref(true);
const loadedCount = ref(0);
const totalImages = ref(0);
const fontsLoaded = ref(false);

// Computed property for progress bar
const progress = computed(() => {
    if (totalImages.value === 0) return 100; // No images, so consider it 100% loaded
    return Math.round((loadedCount.value / totalImages.value) * 100);
});

// Helper: get the currently configured Engine font-family (first family in --font-family)
const getActiveEngineFont = () => {
    if (typeof window === "undefined" || typeof document === "undefined") {
        return "Nunito";
    }

    try {
        const rootStyles = getComputedStyle(document.documentElement);
        const raw = rootStyles.getPropertyValue("--font-family") || "";

        // Take the first font in the comma-separated list and strip quotes/whitespace
        let first = raw
            .split(",")[0]
            .trim()
            .replace(/^["']|["']$/g, "");

        if (!first) {
            first = "Nunito";
        }

        return first;
    } catch (e) {
        _log("Failed to read --font-family, falling back to Nunito", e);
        return "Nunito";
    }
};

// Check if fonts are loaded
const checkFontsLoaded = () => {
    if ("fonts" in document) {
        const weights = ["300", "400", "500", "700", "900"];

        // In editor mode, preload all available font families so switching
        // between fonts is instant. In runtime mode, only preload the active font.
        if (props.isEditorMode) {
            const allFontPromises = ENGINE_FONT_FAMILIES.flatMap((font) =>
                weights.map((w) =>
                    document.fonts.load(`${w} 1em ${font.value}`)
                )
            );

            Promise.all(allFontPromises)
                .then(() => {
                    fontsLoaded.value = true;
                    _log(
                        `All Engine fonts preloaded for editor mode (${ENGINE_FONT_FAMILIES.length} families)`
                    );
                    checkAllResourcesLoaded();
                })
                .catch((error) => {
                    _log("Font loading failed:", error);
                    fontsLoaded.value = true; // Continue anyway
                    checkAllResourcesLoaded();
                });
        } else {
            // Runtime mode: only preload the currently configured font
            const family = getActiveEngineFont();

            Promise.all(
                weights.map((w) => document.fonts.load(`${w} 1em ${family}`))
            )
                .then(() => {
                    fontsLoaded.value = true;
                    _log(`Engine fonts loaded for family: ${family}`);
                    checkAllResourcesLoaded();
                })
                .catch((error) => {
                    _log("Font loading failed:", error);
                    fontsLoaded.value = true; // Continue anyway
                    checkAllResourcesLoaded();
                });
        }
    } else {
        // Fallback for older browsers
        setTimeout(() => {
            fontsLoaded.value = true;
            checkAllResourcesLoaded();
        }, 1000); // Give fonts 1 second to load
    }
};

// Computed property to display only a sample of loaded images
const displaySampleImages = computed(() => {
    return imageUrls.value; // Show first 5 for preview
});

// Check if all resources (images + fonts) are loaded
const checkAllResourcesLoaded = () => {
    if (fontsLoaded.value && loadedCount.value >= totalImages.value) {
        loading.value = false;
        emit("loading-complete");
        _log("All resources (images and fonts) loaded successfully!");
    }
};

// Function to preload images
const preloadImages = () => {
    const urlsToPreload = imageUrls.value;
    totalImages.value = urlsToPreload.length;
    _log("Total images to preload:", totalImages.value);

    if (totalImages.value === 0) {
        // No images to load, just check fonts
        checkFontsLoaded();
        return;
    }

    urlsToPreload.forEach((url) => {
        const img = new Image();
        img.src = url;
        img.onload = () => {
            loadedCount.value++;
            checkAllResourcesLoaded();
        };
        img.onerror = (e) => {
            _log(`Failed to preload image: ${url}`, e);
            // Even if it fails, increment count to ensure loading state resolves
            loadedCount.value++;
            checkAllResourcesLoaded();
        };
    });
};

// Watch for loading state changes
watch(loading, (newLoadingState) => {
    if (!newLoadingState) {
        emit("loading-complete");
    }
});

// Lifecycle hook: runs after the component is mounted
onMounted(() => {
    // 1. Find all image URLs from the deep object
    imageUrls.value = findImageUrls(props.dataObject, props.isEditorMode);
    _log("Found image URLs:", imageUrls.value);

    // 2. Start preloading images and checking fonts
    preloadImages();
    checkFontsLoaded();
});
</script>

<style scoped>
.progress-bar-container {
    width: 100%;
    background-color: #f3f3f3;
    border-radius: 5px;
    overflow: hidden;
    margin-top: 10px;
    height: 20px;
}

.progress-bar {
    height: 100%;
    background-color: #4caf50;
    width: 0%;
    transition: width 0.4s ease-in-out;
    border-radius: 5px;
}

.cached-images-preview {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    margin-top: 20px;
}
</style>
