<script setup>
import { defineProps } from "vue";
import Quill from "quill";

import { ColorPicker } from "vue3-colorpicker"
import "vue3-colorpicker/style.css"

// Register VariableBlot once globally to support {{variable}} tokens styling
let hasRegisteredVariableBlot = false;
try {
    if (!hasRegisteredVariableBlot) {
        const Inline = Quill.import("blots/inline");
        class VariableBlot extends Inline {
            static blotName = "variable";
            static tagName = "p";
            static className = "ql-variable";

            static create(value) {
                const node = super.create();
                if (value) {
                    node.setAttribute("data-name", value);
                    node.innerText = `{{${value}}}`;
                    node.classList.add("ql-variable");
                    node.style.backgroundColor = "#fca5a5";
                    node.style.color = "#000";
                    node.style.padding = "2px 6px";
                    node.style.borderRadius = "4px";
                    node.style.fontWeight = "500";
                    node.style.display = "inline-block";
                    node.style.margin = "0 2px";
                    node.style.cursor = "default";
                }
                return node;
            }

            static value(node) {
                return node.getAttribute("data-name");
            }

            static formats(node) {
                return node.getAttribute("data-name");
            }
        }
        Quill.register(VariableBlot);
        hasRegisteredVariableBlot = true;
    }
} catch (_) {}

const props = defineProps({
    editorId: {
        type: String,
        required: true,
    },
    height: {
        type: String,
        default: "200px",
    },
});
</script>

<script>
// Named exports to help parent files integrate the custom toolbar button behavior
export const PLACEHOLDER_TOKEN = "{{variale_name}}";

export function addColorPickerToolbarButton(quill, editorId) {
    const toolbarEl = quill?.container?.previousSibling;
    if (!toolbarEl) return;
    if (
        toolbarEl.querySelector(
            `.ql-color-picker[data-editor-id="${editorId}"]`
        )
    ) {
        return;
    }

    // Create a group for the color picker button
    const group = document.createElement("span");
    group.className = "ql-formats";

    // Create the color picker button
    const button = document.createElement("button");
    button.type = "button";
    button.className = "ql-color-picker";
    button.setAttribute("data-editor-id", editorId);
    button.title = "Text Color";
    button.style.cssText =
        "background: #fff; border: 1px solid #ccc; border-radius: 4px; padding: 0 8px; margin-right: 4px; cursor: pointer;";
    button.innerHTML = "🎨";

    // Create a container for the color picker popup
    const colorPickerContainer = document.createElement("div");
    colorPickerContainer.style.position = "absolute";
    colorPickerContainer.style.zIndex = "1000";
    colorPickerContainer.style.display = "none";
    colorPickerContainer.style.marginTop = "8px";
    colorPickerContainer.style.background = "#fff";
    colorPickerContainer.style.border = "1px solid #e5e7eb";
    colorPickerContainer.style.borderRadius = "8px";
    colorPickerContainer.style.boxShadow = "0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1)";
    colorPickerContainer.style.padding = "8px";

    let pickerApp = null;

    function mountPicker() {
        if (pickerApp) return;
        pickerApp = createApp({
            data() {
                return { color: "#000000" };
            },
            render() {
                return h(ColorPicker, {
                    pureColor: this.color,
                    format: "hex",
                    isWidget: true,
                    'onUpdate:pureColor': (newColor) => {
                        this.color = newColor;
                        try {
                            quill.format('color', newColor);
                        } catch (_) {}
                    },
                    onChange: (c) => {
                        const hex = typeof c === 'string' ? c : (c?.hex || this.color);
                        try {
                            quill.format('color', hex);
                        } catch (_) {}
                    }
                });
            }
        });
        pickerApp.mount(colorPickerContainer);
    }

    button.addEventListener("click", (e) => {
        e.preventDefault();
        const rect = button.getBoundingClientRect();
        const toolbarRect = toolbarEl.getBoundingClientRect();
        colorPickerContainer.style.left = `${rect.left - toolbarRect.left}px`;
        colorPickerContainer.style.top = `${rect.bottom - toolbarRect.top}px`;
        if (colorPickerContainer.style.display === "none") {
            colorPickerContainer.style.display = "block";
            mountPicker();
        } else {
            colorPickerContainer.style.display = "none";
        }
    });

    // Hide the color picker if clicking outside
    document.addEventListener("mousedown", (event) => {
        if (
            colorPickerContainer.style.display === "block" &&
            !colorPickerContainer.contains(event.target) &&
            event.target !== button
        ) {
            colorPickerContainer.style.display = "none";
        }
    });

    // Append the button and color picker container to the group
    group.appendChild(button);
    group.appendChild(colorPickerContainer);

    // Insert the group into the toolbar
    const targetFormats = toolbarEl.querySelectorAll(".ql-formats");
    if (targetFormats.length > 0) {
        targetFormats[targetFormats.length - 1].after(group);
    } else {
        toolbarEl.appendChild(group);
    }
}

export function insertPlaceholderAtCursor(quill) {
    const range = quill.getSelection(true);
    const index = range ? range.index : quill.getLength();
    quill.insertText(index, PLACEHOLDER_TOKEN);
    quill.setSelection(index + PLACEHOLDER_TOKEN.length, 0);
}

export function addVariablesToolbarButton(quill, editorId) {
    const toolbarEl = quill?.container?.previousSibling;
    if (!toolbarEl) return;
    if (
        !toolbarEl.querySelector(
            `.ql-open-variables[data-editor-id="${editorId}"]`
        )
    ) {
        const group = document.createElement("span");
        group.className = "ql-formats";

        const button = document.createElement("button");
        button.type = "button";
        button.className = "ql-open-variables";
        button.setAttribute("data-editor-id", editorId);
        button.title = "Variables";
        button.style.cssText =
            "background-color: #e0f7fa; color: #15919a; border-radius: 9999px; padding: 0px 70px 0px 10px;";
        button.addEventListener("mouseenter", () => {
            button.style.backgroundColor = "#b2ebf2";
            button.style.color = "#0c7c84";
        });
        button.addEventListener("mouseleave", () => {
            button.style.backgroundColor = "#e0f7fa";
            button.style.color = "#15919a";
        });
        button.textContent = "Variables";
        button.addEventListener("click", () => {
            try {
                insertPlaceholderAtCursor(quill);
            } catch (_) {}
            const clickEvent = new CustomEvent("quill-open-variables", {
                detail: { editorId },
                bubbles: true,
            });
            quill.container.dispatchEvent(clickEvent);
        });

        group.appendChild(button);

        const targetFormats = toolbarEl.querySelectorAll(".ql-formats");
        if (targetFormats.length > 0) {
            targetFormats[targetFormats.length - 1].after(group);
        } else {
            toolbarEl.appendChild(group);
        }
    }
}

export function addImageInsertionToolbarButton(quill, editorId, insertImageCallback) {
    const toolbarEl = quill?.container?.previousSibling;
    if (!toolbarEl) return;
    if (
        !toolbarEl.querySelector(
            `.ql-insert-image[data-editor-id="${editorId}"]`
        )
    ) {
        const group = document.createElement("span");
        group.className = "ql-formats";

        const button = document.createElement("button");
        button.type = "button";
        button.className = "ql-insert-image";
        button.setAttribute("data-editor-id", editorId);
        button.title = "Insert image by URL";
        // button.style.cssText =
        //     "background: #fff; border: 1px solid #ccc; border-radius: 4px; padding: 4px 8px; margin-right: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center;";
        
        // Add the SVG icon
        button.innerHTML = `
            <svg width="51" height="51" viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M40.7027 6.66406H9.45273C7.79568 6.66587 6.20702 7.32493 5.03531 8.49664C3.8636 9.66835 3.20454 11.257 3.20273 12.9141V37.9141C3.20454 39.5711 3.8636 41.1598 5.03531 42.3315C6.20702 43.5032 7.79568 44.1623 9.45273 44.1641H40.7027C42.3598 44.1623 43.9484 43.5032 45.1201 42.3315C46.2919 41.1598 46.9509 39.5711 46.9527 37.9141V12.9141C46.9509 11.257 46.2919 9.66835 45.1201 8.49664C43.9484 7.32493 42.3598 6.66587 40.7027 6.66406ZM32.8902 12.9141C33.8173 12.9141 34.7236 13.189 35.4945 13.704C36.2653 14.2191 36.8661 14.9512 37.2209 15.8077C37.5757 16.6643 37.6685 17.6068 37.4877 18.516C37.3068 19.4253 36.8604 20.2606 36.2048 20.9161C35.5492 21.5717 34.714 22.0181 33.8047 22.199C32.8954 22.3799 31.9529 22.287 31.0964 21.9322C30.2399 21.5775 29.5078 20.9767 28.9927 20.2058C28.4776 19.4349 28.2027 18.5287 28.2027 17.6016C28.204 16.3588 28.6983 15.1672 29.5771 14.2884C30.4559 13.4096 31.6474 12.9154 32.8902 12.9141ZM9.45273 41.0391C8.62393 41.0391 7.82907 40.7098 7.24302 40.1238C6.65697 39.5377 6.32773 38.7429 6.32773 37.9141V31.3096L15.5894 23.0771C16.483 22.2846 17.6452 21.8626 18.8391 21.8971C20.033 21.9316 21.1689 22.4199 22.0152 23.2627L28.358 29.5918L16.9107 41.0391H9.45273ZM43.8277 37.9141C43.8277 38.7429 43.4985 39.5377 42.9124 40.1238C42.3264 40.7098 41.5315 41.0391 40.7027 41.0391H21.3307L33.1881 29.1816C34.0276 28.4677 35.093 28.0744 36.195 28.0715C37.297 28.0687 38.3644 28.4564 39.2076 29.166L43.8277 33.0156V37.9141Z" fill="black"/>
            </svg>

        `;

        button.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (insertImageCallback) {
                insertImageCallback(editorId);
            }
        });

        group.appendChild(button);

        const targetFormats = toolbarEl.querySelectorAll(".ql-formats");
        if (targetFormats.length > 0) {
            targetFormats[targetFormats.length - 1].after(group);
        } else {
            toolbarEl.appendChild(group);
        }
    }
}

// Enable simple drag-to-resize for <img> embeds inside a Quill instance
// Adds a small handle at the bottom-right corner of the selected image
export function enableImageResize(quill) {
    try {
        const container = quill?.container;
        const root = quill?.root;
        if (!container || !root) return;

        // Ensure container can host absolutely positioned elements
        if (getComputedStyle(container).position === "static") {
            container.style.position = "relative";
        }

        let activeImg = null;
        let handleEl = null;
        let frameEl = null;
        let startX = 0;
        let startY = 0;
        let startWidth = 0;
        let aspect = 1;

        function removeHandles() {
            if (handleEl && handleEl.parentNode) handleEl.parentNode.removeChild(handleEl);
            if (frameEl && frameEl.parentNode) frameEl.parentNode.removeChild(frameEl);
            handleEl = null;
            frameEl = null;
            activeImg = null;
        }

        function positionHandles() {
            if (!activeImg || !handleEl || !frameEl) return;
            const imgRect = activeImg.getBoundingClientRect();
            const contRect = container.getBoundingClientRect();
            const left = imgRect.left - contRect.left + container.scrollLeft;
            const top = imgRect.top - contRect.top + container.scrollTop;

            frameEl.style.left = `${left}px`;
            frameEl.style.top = `${top}px`;
            frameEl.style.width = `${imgRect.width}px`;
            frameEl.style.height = `${imgRect.height}px`;

            handleEl.style.left = `${left + imgRect.width - 8}px`;
            handleEl.style.top = `${top + imgRect.height - 8}px`;
        }

        function startDrag(e) {
            if (!activeImg) return;
            e.preventDefault();
            e.stopPropagation();
            startX = e.clientX;
            startY = e.clientY;
            startWidth = activeImg.getBoundingClientRect().width;
            const rect = activeImg.getBoundingClientRect();
            aspect = rect.width / Math.max(1, rect.height);

            document.addEventListener("mousemove", onDrag);
            document.addEventListener("mouseup", stopDrag, { once: true });
        }

        function onDrag(e) {
            if (!activeImg) return;
            const dx = e.clientX - startX;
            const newWidth = Math.max(20, Math.round(startWidth + dx));
            activeImg.style.width = `${newWidth}px`;
            activeImg.style.height = "auto"; // preserve aspect via auto height
            positionHandles();
        }

        function stopDrag() {
            document.removeEventListener("mousemove", onDrag);
            // Best-effort notify Quill that content effectively changed
            try { quill.update(); } catch (_) {}
        }

        function selectImage(img) {
            if (activeImg === img) return;
            activeImg = img;

            if (!frameEl) {
                frameEl = document.createElement("div");
                frameEl.style.position = "absolute";
                frameEl.style.border = "1px dashed #15919A";
                frameEl.style.pointerEvents = "none";
                frameEl.style.zIndex = "5";
                container.appendChild(frameEl);
            }

            if (!handleEl) {
                handleEl = document.createElement("div");
                handleEl.style.position = "absolute";
                handleEl.style.width = "12px";
                handleEl.style.height = "12px";
                handleEl.style.background = "#15919A";
                handleEl.style.border = "2px solid #fff";
                handleEl.style.borderRadius = "2px";
                handleEl.style.boxShadow = "0 1px 3px rgba(0,0,0,0.2)";
                handleEl.style.cursor = "nwse-resize";
                handleEl.style.zIndex = "10";
                handleEl.addEventListener("mousedown", startDrag);
                container.appendChild(handleEl);
            }

            positionHandles();
        }

        function onRootClick(e) {
            const target = e.target;
            if (target && target.tagName === "IMG") {
                selectImage(target);
            } else if (target !== handleEl) {
                removeHandles();
            }
        }

        root.addEventListener("click", onRootClick);
        root.addEventListener("scroll", positionHandles, { passive: true });
        window.addEventListener("resize", positionHandles);
        quill.on("text-change", positionHandles);
        quill.on("selection-change", positionHandles);

        // Cleanup when Quill is destroyed or module disabled (best effort)
        try {
            const origDisable = quill.disable.bind(quill);
            quill.disable = function() {
                removeHandles();
                root.removeEventListener("click", onRootClick);
                root.removeEventListener("scroll", positionHandles);
                window.removeEventListener("resize", positionHandles);
                return origDisable();
            };
        } catch (_) {}
    } catch (_) {}
}
</script>

<template>
    <div :id="props.editorId" :style="{ height: props.height }" v-bind="$attrs"></div>
    <!-- This component intentionally renders only the container div.
         Parent components are responsible for initializing and managing Quill instances. -->
  </template>


