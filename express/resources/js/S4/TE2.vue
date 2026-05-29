<template>
    <div ref="rootRef" :class="[
        'don-editor relative w-full block bg-transparent border-[1.9px] appearance-none rounded-md focus:ring-1 focus:ring-slate-400 !border-gray-300 !bg-gray-100',
        isActive ? '!border-[var(--mcolor2)]  !ring-1 !ring-[var(--mcolor2)]' : 'border-slate-300'
    ]" :style="{
            '--mheight': props.height,
        }">
        <quill-editor ref="quillEditorElem" @focus="handleFocus" @blur="handleBlur"
            @selection-change="handleSelectionChange" v-model:content="content" theme="bubble" contentType="html"
            :readOnly="props.readonly"
            :options="editorOptions" :placeholder="props.placeholder"></quill-editor>
        <s4-gallery-modal :type="'image'" :modal="gallery" :zIndex="9999" @close="gallery.show = false"
            @selectGallery="($event) => { selectGallery($event) }" />
        <TE2IMG ref="imageModal" @update:image="forceEmit"></TE2IMG>
    </div>

</template>

<script setup>
import { ref, reactive, watch, defineProps, defineEmits, onMounted, onBeforeUnmount, computed } from "vue";
import S4GalleryModal from "@/S4/modals/Gallery.vue";
import { QuillEditor, Quill } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import '@vueup/vue-quill/dist/vue-quill.bubble.css';
import { usePage } from "@inertiajs/vue3";
import TE2IMG from "./TE2IMG.vue";
import helpers from '@/helpers.js';
const { ImageWithHeaders } = helpers();


// Register custom image format with a class
Quill.import('ui/icons').ssrImage = `
<svg viewBox="0 0 18 18"> <rect class="ql-stroke" height="10" width="12" x="3" y="4"></rect> <circle class="ql-fill" cx="6" cy="7" r="1"></circle> <polyline class="ql-even ql-fill" points="5 12 5 11 7 9 8 10 11 7 13 9 13 12 5 12"></polyline> </svg>
`;


const ImageFormat = Quill.import('formats/image');
class CustomImage extends ImageFormat {
    static formats(domNode) {
//console.log(domNode)   

        return {
            ...super.formats(domNode),
            class: domNode.getAttribute('class'),
        };
    }

    format(name, value) {
        if (name === 'class' && value) {
            this.domNode.setAttribute('class', value);
        } else {
            super.format(name, value);
        }
    }
}
Quill.register('formats/image', CustomImage, true);



const Parchment = Quill.import("parchment");
const CustomAttribute = new Parchment.Attributor.Attribute("data-fs", {
    scope: Parchment.Scope.INLINE,
});

Quill.register(CustomAttribute, true);


const Inline = Quill.import('blots/inline');

class FontInlineBlot extends Inline {
    static create(value) {
        const node = super.create();
        node.setAttribute('data-fs', value);
        return node;
    }

    static formats(node) {
        return node.getAttribute('data-fs');
    }

    format(name, value) {
        if (name === 'data-fs' && value) {
            this.domNode.setAttribute('data-fs', value);
        } else {
            super.format(name, value);
        }
    }
}

FontInlineBlot.blotName = 'data-fs';
FontInlineBlot.tagName = 'FONT';

Quill.register(FontInlineBlot, true);


class DragModule {
  constructor(quill, options) {
    this.quill = quill;
    this.options = options;

    document.addEventListener('drop', (e) => {

      if (this.hasImage(e)) {
        //e.preventDefault();
        //this.insertImage(e);
      }
    });
  }

  hasImage(e) {
    const hasImageFile = Array.from(e.dataTransfer.files).some(file => file.type.startsWith('image/'));
    if (hasImageFile) {
      const target = e.target.closest('.ql-editor');
      if (target) {
        // Create a MutationObserver to watch for newly added images
        const observer = new MutationObserver((mutations) => {
          mutations.forEach((mutation) => {
            if (mutation.type === 'childList') {
              mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                  // Check if the node itself is an image
                  if (node.tagName === 'IMG') {
                    this.cleanupDraggedImage(node);
                  }
                  // Check for images within the added node
                  const images = node.querySelectorAll && node.querySelectorAll('img');
                  if (images) {
                    images.forEach(img => this.cleanupDraggedImage(img));
                  }
                }
              });
            }
          });
          // Stop observing after processing
          observer.disconnect();
        });
        
        // Start observing
        observer.observe(target, { childList: true, subtree: true });
        
        // Disconnect observer after a short timeout as fallback
        setTimeout(() => observer.disconnect(), 1000);
      }
    }
    
    return hasImageFile;
  }

  cleanupDraggedImage(img) {
    // Remove all existing classes and styles
    img.removeAttribute('class');
    img.removeAttribute('style');
    // Add the ssr-import-image class
    img.classList.add('ssr-import-image');
  }

  insertImage(e) {
    const file = Array.from(e.dataTransfer.files).find(f => f.type.startsWith('image/'));
    if (!file) return;

    const reader = new FileReader();
    reader.onload = () => {
      const range = this.quill.getSelection(true);
      this.quill.insertEmbed(range.index, 'image', reader.result);
      this.quill.setSelection(range.index + 1);
     
    };
    reader.readAsDataURL(file);
  }
}

// Only register the drag module once globally to avoid overwriting warnings
// Use a global flag to track registration across all component instances
if (!window.__quillDragModuleRegistered) {
  Quill.register('modules/drag', DragModule);
  window.__quillDragModuleRegistered = true;
}


// Define props and emits
const props = defineProps({
    modelValue: {
        type: String,
        required: true,
    },
    readonly: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: 'Write something...',
    },
    height: {
        type: String,
        default: null,
    },
    disablePasteColor: {
        type: Boolean,
        default: false,
    },
    disableImage: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['update:modelValue']);

// Reactive state
const content = ref(props.modelValue);
const isActive = ref(false)
const selectedRange = ref(null)
const quillEditorElem = ref(null);
const rootRef = ref(null);
/** @type {HTMLElement|null} scrollable/bounds container for the bubble tooltip */
let tooltipBoundary = null;
const gallery = reactive({
    show: false,
    index: -1,
});
const imageModal = ref(null)

const editorOptions = computed(() => {
    const clipboard = {
        matchVisual: false,
        matchers: [
            ['span', (node, delta) => {
                if (delta.ops) {
                    delta.ops.forEach((op) => {
                        if (op.attributes && op.attributes.background) {
                            delete op.attributes.background;
                        }
                        if (op.attributes && op.attributes.fontSize) {
                            delete op.attributes.fontSize;
                        }
                        // Remove color if disablePasteColor is true
                        if (props.disablePasteColor && op.attributes && op.attributes.color) {
                            delete op.attributes.color;
                        }
                    });
                }
                return delta;
            }],
            ['div', (node, delta) => {
                if (delta.ops) {
                    delta.ops.forEach((op) => {
                        if (op.attributes && op.attributes.background) {
                            delete op.attributes.background;
                        }
                        if (op.attributes && op.attributes.fontSize) {
                            delete op.attributes.fontSize;
                        }
                        // Remove color if disablePasteColor is true
                        if (props.disablePasteColor && op.attributes && op.attributes.color) {
                            delete op.attributes.color;
                        }
                    });
                }
                return delta;
            }],
            ['img', (node, delta) => {
                node.classList.add("ssr-import-image");
                if (delta.ops) {
                    delta.ops.forEach((op) => {
                        if (op.attributes && op.attributes.width) {
                            delete op.attributes.width;
                        }
                        if (op.attributes && op.attributes.height) {
                            delete op.attributes.height;
                        }
                    });
                }
                return delta;
            }],
        ],
    };

    const toolbar = props.readonly
        ? false
        : (() => {
            const container = [
                [{ 'ssrFonts': ['Default', 'Small', 'Normal', 'Large', 'Huge'] }],
                ['bold', 'italic', 'underline'],
                [{ align: [] }],
                [{ script: 'sub' }, { script: 'super' }],
                [{ color: [] }, { background: [] }],
            ];

            if (!props.disableImage) {
                container.push(['ssrImage']);
            }

            const handlers = {
                'ssrFonts': function (opt) {
                    const editor = quillEditorElem.value.getQuill()
                    const range = editor.getSelection();
                    const toolbar = editor.getModule("toolbar");
                    const label = toolbar?.container?.querySelector('.ssrFonts-label');

                    const currentLabel = opt || 'Default';

                    if (label) {
                        label.textContent = currentLabel;
                    }
                    updateSsrFontsPickerState(currentLabel);

                    if (!range) {
                        return;
                    }

                    if (!opt || opt === 'Default') {
                        if (range.length === 0) {
                            editor.format('data-fs', false);
                        } else {
                            editor.formatText(range.index, range.length, 'data-fs', false);
                        }
                        return;
                    }

                    if (range.length === 0) {
                        const index = range.index;
                        editor.insertText(index, '\u200B', 'data-fs', opt);
                    } else {
                        editor.formatText(range.index, range.length, 'data-fs', opt);
                    }
                }
            };

            if (!props.disableImage) {
                handlers['ssrImage'] = function () {
                    const editor = quillEditorElem.value.getQuill()
                    const range = editor.getSelection()
                    selectedRange.value = range;
                    gallery.show = true;
                };
            }

            return {
                container,
                handlers,
            };
        })();

    return {
        modules: {
            toolbar,
            clipboard,
            drag: {}
        },
        theme: 'bubble',
    };
});


// Watchers
watch(() => props.modelValue, (newValue) => {
    content.value = newValue;
}, { deep: true })


watch(content, (newValue) => {
    emit('update:modelValue', newValue);
});



// Methods
const forceEmit = () => {
    let newValue = quillEditorElem.value.getQuill().root.innerHTML
    emit('update:modelValue', newValue);
}


const handleFocus = () => {
    isActive.value = true;
};

const handleBlur = () => {
    isActive.value = false;

    // Hide global active tooltip if it belongs to this editor
    const editor = quillEditorElem.value?.getQuill?.();
    const tooltip = editor?.theme?.tooltip;
    if (tooltip && window.__activeQuillBubbleTooltip === tooltip) {
        tooltip.hide?.();
        window.__activeQuillBubbleTooltip = null;
    }
};

const handleSelectionChange = () => {
    if (props.readonly) return;
    const toolbar = quillEditorElem.value.getQuill().getModule("toolbar");
    const quill = quillEditorElem.value.getQuill(); // Access the Quill instance
    const range = quill.getSelection()
    if (range) {
        const [leaf] = quill.getLeaf(range.index + range.length);
        if (leaf && leaf.domNode) {
            const attributeValue = leaf.parent.domNode.getAttribute('data-fs');
            const currentLabel = attributeValue || 'Default';
            toolbar.container.querySelector('.ssrFonts-label').textContent = currentLabel;
            updateSsrFontsPickerState(currentLabel);
        }
    }
}


const selectGallery = (el) => {

    console.log("selectGallery",el)
    const fluentBase = import.meta.env.VITE_APP_FLUENT_URL;
    const imageUrl = el.file_path
        ? `${fluentBase}/storage/${el.file_path}`
        : ImageWithHeaders(usePage().props.app_url + '/gallery/' + (el.filename || el.file_name));
    gallery.show = false;

    if (quillEditorElem.value) {
        const editor = quillEditorElem.value.getQuill(); // Access the Quill instance
        // const range = editor.getSelection(); // Get the current cursor position or selection range
        const range = selectedRange.value;
        if (range) {
            // If text is selected, delete the selected text
            if (range.length > 0) {
                editor.deleteText(range.index, range.length);
            }

            // Insert the image at the current cursor position
            editor.insertEmbed(range.index, 'image', imageUrl, Quill.sources.USER);
            const [image] = editor.scroll.descendants(CustomImage, range.index, 1);
            if (image) {
                image.format('class', 'ssr-import-image'); // Add the custom class
                image.format('alt', el.config.title);
            }


            editor.setSelection(range.index + 1); // Move the cursor after the image
        } else {
            // If no selection, append the image at the end
            editor.insertEmbed(editor.getLength() - 1, 'image', imageUrl);
            const [image] = editor.scroll.descendants(CustomImage, range.index, 1);
            if (image) {
                image.format('class', 'ssr-import-image'); // Add the custom class
                image.format('alt', el.config.title);
            }

        }
    }
};


const handleContextMenu = (event) => {
    event.preventDefault(); // Prevent the default browser context menu

    const editor = quillEditorElem.value.getQuill();
    let range = editor.getSelection();

    // If no text is selected, simulate a selection at the current cursor/end
    if (!range) {
        const position = editor.getLength();
        editor.setSelection(position, 0);
        range = editor.getSelection();
    }
    if (!range) return;

    const bounds = editor.getBounds(range);

    // Use Quill's own BubbleTooltip positioning (respects boundsContainer)
    const themeTooltip = editor.theme?.tooltip;
    if (themeTooltip) {
        // Ensure only one bubble tooltip is visible across all editors
        const prevTooltip = window.__activeQuillBubbleTooltip;
        if (prevTooltip && prevTooltip !== themeTooltip) {
            prevTooltip.hide?.();
        }
        window.__activeQuillBubbleTooltip = themeTooltip;

        themeTooltip.show();
        themeTooltip.position(bounds);
        setTimeout(constrainTooltipToBoundary, 10);
    }
};

const assignCustomFontSize = () => {
    const toolbar = quillEditorElem.value.getQuill().getModule("toolbar");
    const pickerLabel = toolbar?.container?.querySelector(".ql-ssrFonts .ql-picker-label");
    if (!pickerLabel) return;

    // Remove any default icon Quill may have added
    const defaultIcon = pickerLabel.querySelector("svg");
    if (defaultIcon) {
        defaultIcon.remove();
    }

    const label = document.createElement("span");
    label.innerHTML = "Default";
    label.classList.add("ssrFonts-label");
    pickerLabel.prepend(label);

    // Add a custom dropdown chevron icon
    const iconWrapper = document.createElement("span");
    iconWrapper.classList.add("ssrFonts-dropdown-icon");
    iconWrapper.innerHTML = `
        <svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path d="M5 7l5 5 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    `;
    pickerLabel.appendChild(iconWrapper);

    const select = toolbar.container.querySelector(".ql-picker.ql-ssrFonts");
    select.querySelector(".ql-picker-options").querySelectorAll(".ql-picker-item").forEach((opt) => {
        opt.innerHTML = opt.getAttribute("data-value");
    });
    // Default selected state
    updateSsrFontsPickerState("Default");

    // Re-apply active state every time the dropdown is opened,
    // because the Bubble theme may reset option classes on toggle.
    pickerLabel.addEventListener("click", () => {
        const text =
            pickerLabel.querySelector(".ssrFonts-label")?.textContent?.trim() ||
            "Default";
        updateSsrFontsPickerState(text);
    });
};

function updateSsrFontsPickerState(value) {
    const quill = quillEditorElem.value?.getQuill?.();
    if (!quill) return;
    const toolbar = quill.getModule("toolbar");
    const picker = toolbar?.container?.querySelector(".ql-picker.ql-ssrFonts");
    if (!picker) return;

    const normalized = !value ? "Default" : value;

    // Toggle selected class on options
    picker.querySelectorAll(".ql-picker-item").forEach((item) => {
        const itemValue = item.getAttribute("data-value") || "Default";
        const isSelected = itemValue === normalized;
        item.classList.toggle("ql-selected", isSelected);
        if (isSelected) {
            item.setAttribute("aria-selected", "true");
        } else {
            item.removeAttribute("aria-selected");
        }
    });
}



// Constrain bubble tooltip to stay inside the detected parent container and fit width
function constrainTooltipToBoundary() {
    if (!tooltipBoundary || props.readonly) return;
    const quill = quillEditorElem.value?.getQuill();
    if (!quill?.theme?.tooltip?.root) return;
    const tooltipRoot = quill.theme.tooltip.root;
    if (tooltipRoot.classList.contains('ql-hidden')) return;

    const boundaryRect = tooltipBoundary.getBoundingClientRect();
    const tooltipRect = tooltipRoot.getBoundingClientRect();

    // Constrain width to the container
    tooltipRoot.style.maxWidth = Math.max(200, Math.floor(boundaryRect.width)) + 'px';
    tooltipRoot.style.minWidth = ''; // allow shrink to fit

    // Nudge horizontally so the tooltip doesn't get clipped on left/right
    const currentLeft = parseFloat(tooltipRoot.style.left || '0');
    const padding = 6; // small inner margin

    // If tooltip overflows on the right, move it left
    if (tooltipRect.right > boundaryRect.right - padding) {
        const overflowRight = tooltipRect.right - boundaryRect.right + padding;
        tooltipRoot.style.left = `${currentLeft - overflowRight}px`;
    }

    // If tooltip overflows on the left, move it right
    const updatedRect = tooltipRoot.getBoundingClientRect();
    if (updatedRect.left < boundaryRect.left + padding) {
        const overflowLeft = boundaryRect.left + padding - updatedRect.left;
        const newLeft = parseFloat(tooltipRoot.style.left || '0') + overflowLeft;
        tooltipRoot.style.left = `${newLeft}px`;
    }
}

// Lifecycle hooks
onMounted(() => {
    const quill = quillEditorElem.value.getQuill();
    const editorContainer = quill.container;

    // Detect scrollable/bounds parent so the bubble tooltip fits inside it (no overflow)
    if (rootRef.value) {
        tooltipBoundary =
            rootRef.value.closest('[class*="overflow-y-auto"]') ||
            rootRef.value.closest('[class*="overflow-x-hidden"]') ||
            null;
        if (tooltipBoundary && quill.theme?.tooltip) {
            quill.theme.tooltip.boundsContainer = tooltipBoundary;
            quill.on('editor-change', (type, range) => {
                if (type === 'selection-change' && range && range.length > 0)
                    setTimeout(constrainTooltipToBoundary, 10);
            });
        }
    }

    if (editorContainer) {
        editorContainer.addEventListener("contextmenu", (event) => { handleContextMenu(event) });
    }

    editorContainer.addEventListener("click", (event) => {
        if (event.target.tagName === "IMG") {
            imageModal.value._fn.setup({ target: event.target })
        }
    });

    if (!props.readonly) {
        assignCustomFontSize()

        // Add native browser tooltips to common toolbar controls
        const toolbarModule = quill.getModule("toolbar");
        const toolbarContainer = toolbarModule && toolbarModule.container;

        if (toolbarContainer) {
            const buttonTitles = {
                ".ql-bold": "Bold",
                ".ql-italic": "Italic",
                ".ql-underline": "Underline",
                '.ql-script[value="sub"]': "Subscript",
                '.ql-script[value="super"]': "Superscript",
                ".ql-align": "Align",
                ".ql-color": "Text color",
                ".ql-background": "Background color",
                ".ql-ssrImage": "Insert image",
            };

            Object.entries(buttonTitles).forEach(([selector, title]) => {
                toolbarContainer.querySelectorAll(selector).forEach((el) => {
                    if (!el.getAttribute("title")) {
                        el.setAttribute("title", title);
                    }
                });
            });

            const pickerTitles = {
                ".ql-ssrFonts .ql-picker-label": "Font size",
            };

            Object.entries(pickerTitles).forEach(([selector, title]) => {
                toolbarContainer.querySelectorAll(selector).forEach((el) => {
                    if (!el.getAttribute("title")) {
                        el.setAttribute("title", title);
                    }
                });
            });
        }
    }
});
onBeforeUnmount(() => {
    const editorContainer = quillEditorElem.value.getQuill().container
    if (editorContainer) {
        editorContainer.removeEventListener("contextmenu", handleContextMenu);
    }
    //document.removeEventListener("click", hideToolbar);
})

// Expose a focus method to the parent
defineExpose({
    focus() {
        if (quillEditorElem.value) {
            const editor = quillEditorElem.value.getQuill(); // Access the Quill instance
            editor.focus(); // Focus the editor
            const length = editor.getLength(); // Get the length of the content
            editor.setSelection(length, length); // Place the cursor at the end
        }
    },
    forceClear() {
        //alert('forceUpdate')
        const editor = quillEditorElem.value.getQuill()
        editor.setText('')

    }
});



</script>

<style>
:root {
    --fs1: 0.75rem;
    --fs2: 0.875rem;
    --fs3: 1.125rem;
    --fs4: 1.5rem;
    --fsl: 1.25rem;
}

font[data-fs='Small'] {
    font-size: var(--fs1);
}

font[data-fs='Normal'] {
    font-size: var(--fs2);
}

font[data-fs='Large'] {
    font-size: var(--fs3);
}

font[data-fs='Huge'] {
    font-size: var(--fs4);
}



.ql-container {
    font-size: var(--fs2, 0.875rem);
    line-height: 1.25rem;

}

.ql-editor {
    padding: 10px 15px;
    min-height: var(--mheight, unset);


}

.ql-bubble.ql-toolbar svg {
    filter: brightness(0.1) !important;
}

.ql-tooltip {
    z-index: 9999;
}

.ql-bubble .ql-tooltip {
    border-radius: 5px;
    transform: translateY(10px);
    color: #fff;
}

.ql-bubble .ql-tooltip:not(.ql-flip) .ql-tooltip-arrow {
    left: 50%;
    transform: translateX(100%);
}

/* .ql-bubble.ql-toolbar button, .ql-bubble .ql-toolbar button {
    filter: brightness(50%);
}
.ql-bubble.ql-toolbar button:hover, .ql-bubble .ql-toolbar button:active, .ql-bubble .ql-toolbar button.ql-active {
    filter: brightness(0%);
}
.ql-bubble .ql-picker .ql-picker-label {
    border:1px solid #f9c837;
    background: black;
}

.ql-bubble .ql-active svg{
    filter: unset;
} */

/* .ql-bubble.ql-toolbar button:hover, .ql-bubble .ql-toolbar button:hover, .ql-bubble.ql-toolbar button:focus, .ql-bubble .ql-toolbar button:focus, .ql-bubble.ql-toolbar button.ql-active, .ql-bubble .ql-toolbar button.ql-active, .ql-bubble.ql-toolbar .ql-picker-label:hover, .ql-bubble .ql-toolbar .ql-picker-label:hover, .ql-bubble.ql-toolbar .ql-picker-label.ql-active, .ql-bubble .ql-toolbar .ql-picker-label.ql-active, .ql-bubble.ql-toolbar .ql-picker-item:hover, .ql-bubble .ql-toolbar .ql-picker-item:hover, .ql-bubble.ql-toolbar .ql-picker-item.ql-selected, .ql-bubble .ql-toolbar .ql-picker-item.ql-selected {
    filter: brightness(0%);
} */
/* .ql-background {
    filter: unset!important;
} */

.don-editor img {
    width: 80px;
    height: 80px;
    object-fit: contain;
    display: inline-block;
}




.don-editor .ql-tooltip {
    min-width: 420px;
}


.ql-ssrFonts .ql-picker-label {
    display: grid;
    grid-template-columns: 1fr 15px;
    gap: 2px;
    align-items: center;
}

.ql-ssrFonts .ql-picker-label::before,
.ql-ssrFonts .ql-picker-label::after {
    content: none !important;
}

.ql-ssrFonts .ql-picker-label .ssrFonts-dropdown-icon {
    display: flex;
    align-items: center;
    justify-content: center;
}

.ql-ssrFonts .ql-picker-label .ssrFonts-dropdown-icon svg {
    width: 12px;
    height: 12px;
    display: block;
}


.ql-bubble.ql-toolbar button.ql-active,
.ql-bubble .ql-toolbar button.ql-active,
.ql-bubble.ql-toolbar .ql-picker-item.ql-selected,
.ql-bubble .ql-toolbar .ql-picker-item.ql-selected {
    background-color: var(--mcolor2);
    color: white;
}

.ql-bubble.ql-toolbar button.ql-active .ql-stroke,
.ql-bubble .ql-toolbar button.ql-active .ql-stroke,
.ql-bubble.ql-toolbar .ql-picker-item.ql-selected .ql-stroke,
.ql-bubble .ql-toolbar .ql-picker-item.ql-selected .ql-stroke,
.ql-bubble.ql-toolbar button.ql-active .ql-stroke-miter,
.ql-bubble .ql-toolbar button.ql-active .ql-stroke-miter,
.ql-bubble.ql-toolbar .ql-picker-item.ql-selected .ql-stroke-miter,
.ql-bubble .ql-toolbar .ql-picker-item.ql-selected .ql-stroke-miter {
    stroke: black;
}

/* Ensure long placeholders don't overflow the editor width */
.don-editor .ql-editor.ql-blank::before {
    /* keep Quill's placeholder behavior but constrain text */
    max-width: 100%;
    width: calc(100% - 30px); /* account for left/right padding */
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}


/* .ql-toolbar .ql-picker.ql-ssrImage {
    width: 50px;
    text-align: left;
}

.ql-toolbar .ql-picker.ql-ssrImage .ql-picker-label {
    padding: 5px 10px;
    display: flex;
    align-items: center;
}

.ql-toolbar .ql-picker.ql-ssrImage .ql-picker-options {
    border-radius: 4px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
} */
</style>