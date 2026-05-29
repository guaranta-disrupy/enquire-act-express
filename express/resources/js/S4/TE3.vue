<template>
    <div :class="[
        'don-editor relative w-full block bg-transparent border-[1.9px] appearance-none rounded-md focus:ring-2 focus:ring-slate-400 !bg-gray-100',
        isActive ? '!border-[var(--mcolor2)]  !ring-1 !ring-[var(--mcolor2)]' : 'border-slate-300',
        props.disabled ? 'opacity-60 pointer-events-none' : ''
    ]" type="2" :style="{
            '--mheight': props.height,
        }">
        <quill-editor ref="quillEditorElem" @focus="handleFocus" @blur="handleBlur"
            @selection-change="handleSelectionChange" v-model:content="content" theme="snow" contentType="html"
            :options="editorOptions" :placeholder="props.placeholder" :readOnly="props.disabled"></quill-editor>
        <s4-gallery-modal :type="'image'" :modal="gallery" @close="gallery.show = false"
            @selectGallery="($event) => { selectGallery($event) }" />
        <TE2IMG ref="imageModal" @update:image="forceEmit"></TE2IMG>
    </div>

</template>

<script setup>
import { ref, reactive, watch, defineProps, defineEmits, onMounted, onBeforeUnmount } from "vue";
import S4GalleryModal from "@/S4/modals/Gallery.vue";
import { QuillEditor, Quill } from '@vueup/vue-quill';
import helpers from '@/helpers.js';
const { ImageWithHeaders } = helpers();


import '@vueup/vue-quill/dist/vue-quill.snow.css';
import '@vueup/vue-quill/dist/vue-quill.bubble.css';

import { usePage } from "@inertiajs/vue3";
import TE2IMG from "./TE2IMG.vue";



// Register custom image format with a class
Quill.import('ui/icons').ssrImage = `
<svg viewBox="0 0 18 18"> <rect class="ql-stroke" height="10" width="12" x="3" y="4"></rect> <circle class="ql-fill" cx="6" cy="7" r="1"></circle> <polyline class="ql-even ql-fill" points="5 12 5 11 7 9 8 10 11 7 13 9 13 12 5 12"></polyline> </svg>
`;
Quill.import('ui/icons').ssrPipe = `
<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" >
        title='Piping'
        <path class="ql-stroke"
            d="M16,9V11H8V9H10V8H4V10H2V2H4V4H12A2,2 0 0,1 14,6V9H16M10,15V18A2,2 0 0,0 12,20H20V22H22V14H20V16H14V15H16V13H8V15H10Z">
        </path>

</svg>`;



const ImageFormat = Quill.import('formats/image');
class CustomImage extends ImageFormat {
    static formats(domNode) {
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
const CustomAttribute = new Parchment.Attributor.Attribute("data-fs", "data-fs", {
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
    console.log("QUILL",this.quill.container)

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

Quill.register('modules/drag', DragModule);

class PipeBlot extends Inline {
    static create(value) {
        let node = super.create();
        node.setAttribute('contenteditable', false);
        node.setAttribute('data-pipe', value.tag);
        node.innerHTML = value.label;
        return node;
    }

    static formats(node) {
        return node.getAttribute('data-pipe');
    }

    static value(node) {
        return node.getAttribute('data-pipe'); // Preserve value on copy
    }

}

PipeBlot.blotName = 'pipe';
PipeBlot.tagName = 'span';
PipeBlot.className = 'pipe-tag';

Quill.register(PipeBlot, true);




// Define props and emits
const props = defineProps({
    modelValue: {
        type: String,
        required: true,
    },
    placeholder: {
        type: String,
        default: 'Write something...',
    },
    height: {
        type: String,
        default: "150px",
    },
    enablePiping: {
        default: true
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue', 'show:piping']);

// Reactive state
const content = ref(props.modelValue);
const isActive = ref(false)
const selectedRange = ref(null)
const quillEditorElem = ref(null);
const gallery = reactive({
    show: false,
    index: -1,
});
const imageModal = ref(null)

const editorOptions = ref({
    modules: {

        toolbar: {
            container: [
                // Dropdowns
                // Inline styles
                [{ 'ssrFonts': ['Default', 'Small', 'Normal', 'Large', 'Huge'] }],
                ['bold', 'italic', 'underline'], // Formatting options
                [{ align: [] }], // Text alignment
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ script: 'sub' }, { script: 'super' }], // Subscript and superscript
                [{ color: [] }, { background: [] }], // Text and background colorsty
                ['ssrImage'],
                ['ssrPipe']
            ],
            handlers: {
                // Define the behavior of the custom button
             
                'ssrImage': function () {
                    const editor = quillEditorElem.value.getQuill()
                    const range = editor.getSelection()
                    selectedRange.value = range;
                    gallery.show = true;
                },
                'ssrFonts': function (opt) {
                    const editor = quillEditorElem.value.getQuill()
                    const range = editor.getSelection();
                    const toolbar = editor.getModule("toolbar");
                    const label = toolbar.container.querySelector('.ssrFonts-label');

                    const currentLabel = opt || 'Default';

                    // Update label text
                    if (label) {
                        label.textContent = currentLabel;
                    }
                    updateSsrFontsPickerState(currentLabel);

                    if (!range) {
                        return;
                    }

                    // "Default" option removes any data-fs so text uses base font size
                    if (!opt || opt === 'Default') {
                        if (range.length === 0) {
                            editor.format('data-fs', false);
                        } else {
                            editor.formatText(range.index, range.length, 'data-fs', false);
                        }
                        return;
                    }

                    // Other options apply a specific data-fs
                    if (range.length === 0) {
                        const index = range.index;
                        editor.insertText(index, '\u200B', 'data-fs', opt); // Insert new text
                    } else {
                        // Format selected text
                        editor.formatText(range.index, range.length, 'data-fs', opt);
                    }
                },
                ssrPipe: function () {
                    emit("show:piping", {
                        target: quillEditorElem.value,
                        user: Quill.sources.USER
                    })

                    // const editor = quillEditorElem.value.getQuill()
                    // const range = editor.getSelection();
                    // editor.insertText(range.index, '[PIPE:Q1]')
                    // editor.insertEmbed(range.index, 'pipe', 'My Custom Div');
                    // editor.setSelection(range.index + 16)

                }
            },
        },
        clipboard: {
            matchVisual: false, // Ensures pasted content retains its styles
            matchers: [
                // Remove background-color from pasted content
                ['span', (node, delta) => {
                    if (delta.ops) {
                        delta.ops.forEach((op) => {
                            if (op.attributes && op.attributes.background) {
                                delete op.attributes.background; // Remove background attribute
                            }
                            if (op.attributes && op.attributes.fontSize) {
                                delete op.attributes.fontSize; // Remove font size attribute
                            }
                        });
                    }
                    return delta;
                }],
                ['div', (node, delta) => {
                    if (delta.ops) {
                        delta.ops.forEach((op) => {
                            if (op.attributes && op.attributes.background) {
                                delete op.attributes.background; // Remove background attribute
                            }
                            if (op.attributes && op.attributes.fontSize) {
                                delete op.attributes.fontSize; // Remove font size attribute
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
                                delete op.attributes.width; // Remove background attribute
                            }
                            if (op.attributes && op.attributes.height) {
                                delete op.attributes.height; // Remove font size attribute
                            }

                        });
                    }
                    return delta;
                }],

            ],
        },
        drag: {},

       //table: false,  // disable table module
    },
    theme: 'snow', // Use the "bubble" theme
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
};

const handleSelectionChange = () => {
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


//const handleTextChange = ()=>{
//if (source === 'user') {
//console.log("typing...")
// const editor = quillEditorElem.value.getQuill()
// let text = editor.getText();
// let matchIndex = text.indexOf('[PIPE:Q1]');
// console.log(matchIndex)
// if (matchIndex !== -1) {

//     let range = editor.getSelection();
//     if (range) {
//         editor.insertEmbed(range.index, 'pipe', '[PIPE:Q1]');
//     }


// }
// }    
//}


const selectGallery = (el) => {
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


// const handleContextMenu = (event) => {
//     event.preventDefault(); // Prevent the default context menu

//     const editor = quillEditorElem.value.getQuill();
//     const range = editor.getSelection();

//     // If no text is selected, simulate a selection at the cursor position
//     if (!range) {
//         const position = editor.getLength(); // Get the current content length
//         editor.setSelection(position, 0); // Set the cursor position
//     }

//     const bounds = editor.getBounds(range ? range.index : 0);

//     // Position the toolbar near the cursor
//     const tooltip = editor.container.querySelector(".ql-tooltip");
//     // console.log(event)
//     // console.log("Tooltip",tooltip)

//     if (tooltip) {
//         let T_info = tooltip.getBoundingClientRect()
//         tooltip.classList.remove("ql-hidden")
//         tooltip.style.left = `${(bounds.left - 200)}px`;
//         tooltip.style.top = `${bounds.top + bounds.height}px`;

//         // tooltip.style.top = `${event.clientY}px`;
//         // tooltip.style.visibility = "visible";
//         // tooltip.classList.add("ql-flip");
//     }
// };

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
    // because the Snow theme may reset option classes on toggle.
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

    // Sync label state (text only, no active styling on label)
    const labelEl = picker.querySelector(".ql-picker-label");
    if (labelEl) {
        if (!value || value === "Default") {
            labelEl.removeAttribute("data-value");
        } else {
            labelEl.setAttribute("data-value", value);
        }
        // Ensure the label itself never gets the active highlight styles
        labelEl.classList.remove("ql-active");
    }
}



// Lifecycle hooks
onMounted(() => {
    if (quillEditorElem.value && quillEditorElem.value.getQuill) {
        quillEditorElem.value.getQuill().enable(!props.disabled);
    }

    const editorContainer = quillEditorElem.value.getQuill().container

    if (!props.enablePiping) {
        editorOptions.value.modules.toolbar.container.push(["ssrPipe"])
        quillEditorElem.value.getQuill().getModule('toolbar').container.querySelector(".ql-ssrPipe").closest(".ql-formats").style.display = "none"
    }

    // console.log(editorContainer)
    // if (editorContainer) {
    //     editorContainer.addEventListener("contextmenu", (event)=>{handleContextMenu(event)});
    // }
    //document.addEventListener("click", hideToolbar);

    editorContainer.addEventListener("click", (event) => {
        if (event.target.tagName === "IMG") {
            // event.target.style.border = "2px solid red";
            // let newValue = quillEditorElem.value.getQuill().root.innerHTML

            imageModal.value._fn.setup({ target: event.target })

            //emit('update:modelValue', newValue)
        }


    });

    assignCustomFontSize()

    // Add native browser tooltips to common toolbar buttons
    const toolbarModule = quillEditorElem.value.getQuill().getModule("toolbar");
    const toolbarContainer = toolbarModule && toolbarModule.container;

    if (toolbarContainer) {
        const buttonTitles = {
            ".ql-bold": "Bold",
            ".ql-italic": "Italic",
            ".ql-underline": "Underline",
            '.ql-list[value="ordered"]': "Numbered list",
            '.ql-list[value="bullet"]': "Bulleted list",
            '.ql-script[value="sub"]': "Subscript",
            '.ql-script[value="super"]': "Superscript",
            ".ql-align": "Align",
            ".ql-color": "Text color",
            ".ql-background": "Background color",
            ".ql-ssrImage": "Insert image",
            ".ql-ssrPipe": "Piping",
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

});
onBeforeUnmount(() => {
    const editorContainer = quillEditorElem.value.getQuill().container
    // if (editorContainer) {
    //     editorContainer.removeEventListener("contextmenu", handleContextMenu);
    // }
    //document.removeEventListener("click", hideToolbar);
    emit("show:piping", {
        target: null
    }, true)


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


watch(
    () => props.disabled,
    (newVal) => {
        if (quillEditorElem.value && quillEditorElem.value.getQuill) {
            quillEditorElem.value.getQuill().enable(!newVal);
        }
    }
);
</script>

<style>
:root {
    --fs1: 0.75rem;
    --fs2: 0.875rem;
    --fs3: 1.125rem;
    --fs4: 1.5rem;
    --fsl: 1.25rem;
}

.don-editor[type='2'] font[data-fs='Small'] {
    font-size: calc(var(--fs1) + .2rem);
}

.don-editor[type='2'] font[data-fs='Normal'] {
    font-size: calc(var(--fs2) + .2rem);
}

.don-editor[type='2'] font[data-fs='Large'] {
    font-size: calc(var(--fs3) + .2rem);
}

.don-editor[type='2'] font[data-fs='Huge'] {
    font-size: calc(var(--fs4) + .2rem);
}

.don-editor[type='2'] .ql-container {
    font-size: calc(var(--fs2) + .2rem);
    line-height: 1;
}

.don-editor .ql-editor {
    padding: 10px 15px;
    min-height: var(--mheight, unset);


}

.don-editor .ql-bubble.ql-toolbar svg {
    filter: brightness(0.1) !important;
}

.don-editor .ql-tooltip {
    z-index: 9999;
}

.don-editor .ql-bubble .ql-tooltip {
    /* background-color: #f9c837; */
    border-radius: 5px;
    transform: translateX(calc(50% - 50px)) translateY(10px);
    color: #fff;
}

.don-editor .ql-bubble .ql-tooltip:not(.ql-flip) .ql-tooltip-arrow {
    /* border-bottom: 6px solid #f9c837; */
    left: 40px;
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

/* .don-editor[type='2']{
    --maxWidth:150px;
} */

.don-editor img.ssr-import-image {
    width: var(--imgwidth,150px);
    float: var(--imgfloat,none);
    max-width: 150px;
    height: auto;
    object-fit: contain;
    display: inline-block;
}


.don-editor .ql-tooltip {
    min-width: 420px;
}


.don-editor .ql-ssrFonts .ql-picker-label {
    display: grid;
    grid-template-columns: 1fr 15px;
    gap: 2px;
    align-items: center;
}

.don-editor .ql-ssrFonts .ql-picker-label::before,
.don-editor .ql-ssrFonts .ql-picker-label::after {
    content: none !important;
}

.don-editor .ql-ssrFonts .ql-picker-label .ssrFonts-dropdown-icon {
    display: flex;
    align-items: center;
    justify-content: center;
}

.don-editor .ql-ssrFonts .ql-picker-label .ssrFonts-dropdown-icon svg {
    width: 12px;
    height: 12px;
    display: block;
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

.ql-snow.ql-toolbar button.ql-active,
.ql-snow .ql-toolbar button.ql-active,
.ql-snow.ql-toolbar .ql-picker-item.ql-selected,
.ql-snow .ql-toolbar .ql-picker-item.ql-selected {
    background-color: var(--mcolor);
    color: white;
}

.ql-snow.ql-toolbar button.ql-active .ql-stroke,
.ql-snow .ql-toolbar button.ql-active .ql-stroke,
.ql-snow.ql-toolbar .ql-picker-item.ql-selected .ql-stroke,
.ql-snow .ql-toolbar .ql-picker-item.ql-selected .ql-stroke,
.ql-snow.ql-toolbar button.ql-active .ql-stroke-miter,
.ql-snow .ql-toolbar button.ql-active .ql-stroke-miter,
.ql-snow.ql-toolbar .ql-picker-item.ql-selected .ql-stroke-miter,
.ql-snow .ql-toolbar .ql-picker-item.ql-selected .ql-stroke-miter {
    stroke: black;
}



.pipe-tag {
    background-color: #e0f7fa;
    border: 1px solid #00acc1;
    color: #000000;
    padding: 2px 4px;
    border-radius: 3px;
    cursor: pointer;
}
</style>