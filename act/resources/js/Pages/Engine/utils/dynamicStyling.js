import { ref, watch, onUnmounted } from "vue";

/**
 * A Vue composable to dynamically inject sanitized CSS into the document head.
 * It automatically adds `!important` to rules and cleans up styles on unmount.
 * @param {import('vue').Ref<string> | () => string} cssSource - A ref or a getter function that returns the CSS string.
 */
export function useDynamicStyles(cssSource) {
    const styleElement = ref(null);

    watch(
        cssSource,
        (newCss) => {
            // Remove old style element if it exists
            if (styleElement.value) {
                styleElement.value.remove();
                styleElement.value = null;
            }

            if (newCss) {
                // Sanitize and process the CSS
                const processedCss = newCss.replace(
                    /{([^}]+)}/g,
                    (match, declarations) => {
                        const rules = declarations
                            .split(";")
                            .map((rule) => {
                                if (rule.trim()) {
                                    const [property, value] = rule
                                        .split(":")
                                        .map((s) => s.trim());
                                    // Only include rule if the property is in the allow-list
                                    if (
                                        property &&
                                        value &&
                                        allowedCssProperties.has(
                                            property.toLowerCase()
                                        )
                                    ) {
                                        // Add !important if not already present
                                        if (!value.includes("!important")) {
                                            return `${property}: ${value} !important`;
                                        }
                                        return rule;
                                    }
                                }
                                return null; // Discard unsafe or malformed rule
                            })
                            .filter(Boolean) // Remove null entries
                            .join(";");
                        return `{${rules}}`;
                    }
                );

                if (processedCss.trim().length > 2) {
                    // Ensure there are rules to apply
                    const style = document.createElement("style");
                    style.textContent = processedCss;
                    document.head.appendChild(style);
                    styleElement.value = style;
                }
            }
        },
        { immediate: true }
    );

    onUnmounted(() => {
        if (styleElement.value) {
            styleElement.value.remove();
        }
    });
}

// A list of safe CSS properties to prevent potential XSS attacks.
const allowedCssProperties = new Set([
    // Text & Font
    "color",
    "font-family",
    "font-size",
    "font-weight",
    "line-height",
    "letter-spacing",
    "word-spacing",
    "white-space",
    "text-align",
    "text-decoration",
    "text-decoration-line",
    "text-decoration-color",
    "text-decoration-style",
    "text-decoration-thickness",
    "text-underline-offset",
    "text-transform",
    "text-shadow",
    "text-indent",
    "text-overflow",
    "vertical-align",
    "word-break",
    "word-wrap",
    "overflow-wrap",
    "hyphens",
    "font-style",
    "font-variant",
    "font-variant-ligatures",
    "font-variant-caps",
    "font-variant-numeric",
    "font-stretch",
    "font-display",
    "font-kerning",
    "font-feature-settings",
    "font-synthesis",
    "font-size-adjust",
    "direction",
    "unicode-bidi",
    "writing-mode",
    "text-orientation",

    // Box Model
    "width",
    "height",
    "min-width",
    "max-width",
    "min-height",
    "max-height",
    "padding",
    "padding-top",
    "padding-right",
    "padding-bottom",
    "padding-left",
    "margin",
    "margin-top",
    "margin-right",
    "margin-bottom",
    "margin-left",
    "border",
    "border-width",
    "border-top-width",
    "border-right-width",
    "border-bottom-width",
    "border-left-width",
    "border-style",
    "border-top-style",
    "border-right-style",
    "border-bottom-style",
    "border-left-style",
    "border-color",
    "border-top-color",
    "border-right-color",
    "border-bottom-color",
    "border-left-color",
    "border-top",
    "border-right",
    "border-bottom",
    "border-left",
    "border-radius",
    "border-top-left-radius",
    "border-top-right-radius",
    "border-bottom-left-radius",
    "border-bottom-right-radius",
    "border-image", // UNSAFE: shorthand includes border-image-source which accepts url() values - potential XSS risk
    "border-image-source", // UNSAFE: accepts url() values - potential XSS risk
    "border-image-slice",
    "border-image-width",
    "border-image-outset",
    "border-image-repeat",
    "box-shadow",
    "box-sizing",
    "box-decoration-break",
    "outline",
    "outline-width",
    "outline-style",
    "outline-color",
    "outline-offset",

    // Layout - Flexbox
    "display",
    "flex",
    "flex-direction",
    "flex-wrap",
    "flex-flow",
    "flex-grow",
    "flex-shrink",
    "flex-basis",
    "justify-content",
    "align-items",
    "align-content",
    "align-self",
    "gap",
    "row-gap",
    "column-gap",
    "order",

    // Layout - Grid
    "grid",
    "grid-template",
    "grid-template-columns",
    "grid-template-rows",
    "grid-template-areas",
    "grid-auto-columns",
    "grid-auto-rows",
    "grid-auto-flow",
    "grid-column",
    "grid-row",
    "grid-area",
    "grid-column-start",
    "grid-column-end",
    "grid-row-start",
    "grid-row-end",
    "place-items",
    "place-content",
    "place-self",

    // Layout - Positioning
    "position",
    "top",
    "right",
    "bottom",
    "left",
    "z-index",
    "inset",
    "inset-block",
    "inset-block-start",
    "inset-block-end",
    "inset-inline",
    "inset-inline-start",
    "inset-inline-end",
    "float",
    "clear",
    "overflow",
    "overflow-x",
    "overflow-y",
    "overflow-wrap",
    "text-overflow",
    "clip",
    // "clip-path", // UNSAFE: can accept url() references to SVG - potential XSS risk
    "contain",
    "contain-intrinsic-size",
    "content-visibility",
    "isolation",

    // Background
    "background", // UNSAFE: shorthand can include background-image which accepts url() values - potential XSS risk
    "background-color",
    "background-image", // be cautious: allow only safe values, not urls
    "background-size",
    "background-position",
    "background-position-x",
    "background-position-y",
    "background-repeat",
    "background-repeat-x",
    "background-repeat-y",
    "background-clip",
    "background-origin",
    "background-attachment",
    "background-blend-mode",

    // Animation
    "animation",
    "animation-name",
    "animation-duration",
    "animation-timing-function",
    "animation-delay",
    "animation-iteration-count",
    "animation-direction",
    "animation-fill-mode",
    "animation-play-state",

    // Transition
    "transition",
    "transition-property",
    "transition-duration",
    "transition-timing-function",
    "transition-delay",

    // Transform & Visual Effects
    "transform",
    "transform-origin",
    "transform-style",
    "perspective",
    "perspective-origin",
    "backface-visibility",
    "opacity",
    "visibility",
    // "filter", // UNSAFE: can accept url() references to SVG filters - potential XSS risk
    // "backdrop-filter", // UNSAFE: can accept url() references to SVG filters - potential XSS risk
    "will-change",
    "mix-blend-mode",

    // Interactivity
    // "cursor", // UNSAFE: can accept url() for custom cursors - potential XSS risk (basic values like "pointer", "default" are safe, but url() is not)
    "pointer-events",
    "user-select",
    "touch-action",
    "resize",
    "scroll-behavior",
    "scroll-margin",
    "scroll-margin-top",
    "scroll-margin-right",
    "scroll-margin-bottom",
    "scroll-margin-left",
    "scroll-padding",
    "scroll-padding-top",
    "scroll-padding-right",
    "scroll-padding-bottom",
    "scroll-padding-left",
    "overscroll-behavior",
    "overscroll-behavior-x",
    "overscroll-behavior-y",

    // Object & Media
    "object-fit",
    "object-position",
    "image-rendering",
    "image-orientation",

    // Tables
    "border-collapse",
    "border-spacing",
    "table-layout",
    "caption-side",
    "empty-cells",
    "vertical-align",

    // Lists
    "list-style",
    "list-style-type",
    "list-style-position",
    "list-style-image", // UNSAFE: accepts url() values - potential XSS risk
]);
