<template>
    <div class="don-tiptap-editor w-full bg-[#d1d5db]" v-if="editor">
         <div class="don-tiptap-editor-toolbars">

            <!-- Font Size -->
            <div class="toolbar-group">
                <span class="color-label">FONT SIZE</span>
                <select 
                    class="font-size-dropdown"
                    @change="changeFontSize($event.target.value)"
                    v-model="currentFontSize"
                    title="Font Size"
                >
                    <option value="">Default</option>
                    <option value="Small">Small</option>
                    <option value="Normal">Medium</option>
                    <option value="Large">Large</option>
                    <option value="Huge">Huge</option>
                </select>
                <!-- <button 
                    @click="editor.chain().focus().unsetFontSize().run()"
                    class="toolbar-btn"
                    title="Reset Font Size"
                >
                    ↺
                </button> -->
            </div>


             <!-- Text Formatting (Available with StarterKit) -->
             <div class="toolbar-group">
                 <button 
                     @click="editor.chain().focus().toggleBold().run()"
                     :class="{ 'is-active': editor.isActive('bold') }"
                     class="toolbar-btn"
                     title="Bold"
                 >
                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                    <path stroke-linejoin="round" d="M6.75 3.744h-.753v8.25h7.125a4.125 4.125 0 0 0 0-8.25H6.75Zm0 0v.38m0 16.122h6.747a4.5 4.5 0 0 0 0-9.001h-7.5v9h.753Zm0 0v-.37m0-15.751h6a3.75 3.75 0 1 1 0 7.5h-6m0-7.5v7.5m0 0v8.25m0-8.25h6.375a4.125 4.125 0 0 1 0 8.25H6.75m.747-15.38h4.875a3.375 3.375 0 0 1 0 6.75H7.497v-6.75Zm0 7.5h5.25a3.75 3.75 0 0 1 0 7.5h-5.25v-7.5Z" />
                </svg>

                 </button>
                 <button 
                     @click="editor.chain().focus().toggleItalic().run()"
                     :class="{ 'is-active': editor.isActive('italic') }"
                     class="toolbar-btn"
                     title="Italic"
                 >
                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.248 20.246H9.05m0 0h3.696m-3.696 0 5.893-16.502m0 0h-3.697m3.697 0h3.803" />
                    </svg>

                 </button>
                 <button 
                     @click="editor.chain().focus().toggleStrike().run()"
                     :class="{ 'is-active': editor.isActive('strike') }"
                     class="toolbar-btn"
                     title="Strike"
                 >
                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a8.912 8.912 0 0 1-.318-.079c-1.585-.424-2.904-1.247-3.76-2.236-.873-1.009-1.265-2.19-.968-3.301.59-2.2 3.663-3.29 6.863-2.432A8.186 8.186 0 0 1 16.5 5.21M6.42 17.81c.857.99 2.176 1.812 3.761 2.237 3.2.858 6.274-.23 6.863-2.431.233-.868.044-1.779-.465-2.617M3.75 12h16.5" />
                </svg>

                 </button>
                 <button 
                    @click="editor.chain().focus().toggleUnderline().run()"
                    :class="{ 'is-active': editor.isActive('underline') }"
                    class="toolbar-btn"
                    title="Underline"
                >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
  <path stroke-linecap="round" stroke-linejoin="round" d="M17.995 3.744v7.5a6 6 0 1 1-12 0v-7.5m-2.25 16.502h16.5" />
</svg>

                </button>


             </div>

             <!-- Lists (Available with StarterKit) -->
             <div class="toolbar-group">
                 <button 
                     @click="editor.chain().focus().toggleBulletList().run()"
                     :class="{ 'is-active': editor.isActive('bulletList') }"
                     class="toolbar-btn"
                     title="Bullet List"
                 >
                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>

                 </button>
                 <button 
                     @click="editor.chain().focus().toggleOrderedList().run()"
                     :class="{ 'is-active': editor.isActive('orderedList') }"
                     class="toolbar-btn"
                     title="Ordered List"
                 >
                 <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
  <path stroke-linecap="round" stroke-linejoin="round" d="M8.242 5.992h12m-12 6.003H20.24m-12 5.999h12M4.117 7.495v-3.75H2.99m1.125 3.75H2.99m1.125 0H5.24m-1.92 2.577a1.125 1.125 0 1 1 1.591 1.59l-1.83 1.83h2.16M2.99 15.745h1.125a1.125 1.125 0 0 1 0 2.25H3.74m0-.002h.375a1.125 1.125 0 0 1 0 2.25H2.99" />
</svg>

                 </button>
             </div>

             <!-- Headings (Available with StarterKit) -->
             <!-- <div class="toolbar-group">
                 <button 
                     @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                     :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }"
                     class="toolbar-btn"
                     title="Heading 1"
                 >
                     H1
                 </button>
                 <button 
                     @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                     :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
                     class="toolbar-btn"
                     title="Heading 2"
                 >
                     H2
                 </button>
                 <button 
                     @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                     :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }"
                     class="toolbar-btn"
                     title="Heading 3"
                 >
                     H3
                 </button>
             </div> -->

            <!-- Additional Features -->
            <div class="toolbar-group">

                <button 
                    @click="editor.chain().focus().toggleSubscript().run()"
                    :class="{ 'is-active': editor.isActive('subscript') }"
                    class="toolbar-btn"
                    title="Subscript"
                >
                    X<sub>₂</sub>
                </button>
                <button 
                    @click="editor.chain().focus().toggleSuperscript().run()"
                    :class="{ 'is-active': editor.isActive('superscript') }"
                    class="toolbar-btn"
                    title="Superscript"
                >
                    X<sup>²</sup>
                </button>
            </div>

            <!-- Text Alignment -->
            <div class="toolbar-group">
                <button 
                    @click="editor.chain().focus().setTextAlign('left').run()"
                    :class="{ 'is-active': editor.isActive({ textAlign: 'left' }) }"
                    class="toolbar-btn"
                    title="Align Left"
                >
                    ⬅
                </button>
                <button 
                    @click="editor.chain().focus().setTextAlign('center').run()"
                    :class="{ 'is-active': editor.isActive({ textAlign: 'center' }) }"
                    class="toolbar-btn"
                    title="Align Center"
                >
                    ↔
                </button>
                <button 
                    @click="editor.chain().focus().setTextAlign('right').run()"
                    :class="{ 'is-active': editor.isActive({ textAlign: 'right' }) }"
                    class="toolbar-btn"
                    title="Align Right"
                >
                    ➡
                </button>
            </div>



            <!-- Colors and Highlights -->
            <div class="toolbar-group">
                <div class="color-group">
                    <span class="color-label">TEXT COLOR</span>
                    <input 
                        type="color" 
                        class="color-picker"
                        v-model="currentTextColor"
                        @input="changeTextColor($event.target.value)"
                        title="Text Color"
                    />
                    <button 
                        @click="removeTextColor()"
                        class="toolbar-btn"
                        title="Remove Text Color"
                    >
                    ↺
                    </button>


                </div>
                <div class="color-group">
                    <span class="color-label">Highlight</span>
                    <input 
                        type="color" 
                        class="color-picker"
                        v-model="currentHighlightColor"
                        @input="changeHighlightColor($event.target.value)"
                        title="Highlight Color"
                    />
                    <button 
                    @click="removeHighlight()"
                    class="toolbar-btn"
                    title="Remove Highlight"
                >
                ↺
                </button>

                </div>


            </div>

            <!-- Custom Element -->
            <div class="toolbar-group">
                <button 
                    @click="insertCustomElement()"
                    class="toolbar-btn"
                    title="Insert Custom Element"
                >
                    📦
                </button>
            </div>

            <!-- Table Operations -->
            <!-- <div class="toolbar-group">
                <button 
                    @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()"
                    class="toolbar-btn"
                    title="Insert Table"
                >
                    ⊞
                </button>
                <button 
                    @click="editor.chain().focus().addColumnBefore().run()"
                    class="toolbar-btn"
                    title="Add Column Before"
                >
                    ⊣
                </button>
                <button 
                    @click="editor.chain().focus().addColumnAfter().run()"
                    class="toolbar-btn"
                    title="Add Column After"
                >
                    ⊢
                </button>
                <button 
                    @click="editor.chain().focus().deleteColumn().run()"
                    class="toolbar-btn danger"
                    title="Delete Column"
                >
                    ⊟
                </button>
                <button 
                    @click="editor.chain().focus().addRowBefore().run()"
                    class="toolbar-btn"
                    title="Add Row Before"
                >
                    ⊤
                </button>
                <button 
                    @click="editor.chain().focus().addRowAfter().run()"
                    class="toolbar-btn"
                    title="Add Row After"
                >
                    ⊥
                </button>
                <button 
                    @click="editor.chain().focus().deleteRow().run()"
                    class="toolbar-btn danger"
                    title="Delete Row"
                >
                    ⊠
                </button>
                <button 
                    @click="editor.chain().focus().deleteTable().run()"
                    class="toolbar-btn danger"
                    title="Delete Table"
                >
                    ✗⊞
                </button>
            </div> -->
         </div>
        <div class="don-tiptap-editor-content">
            <editor-content :editor="editor" />
        </div>
        
    </div>
</template>


<script setup>
import { ref, defineProps, defineEmits, watch } from 'vue'
const props = defineProps({
    modelValue: String,
    placeholder: String,
    disabled: Boolean
})
const editing = ref(false)
const currentFontSize = ref('')
const currentTextColor = ref('#000000')
const currentHighlightColor = ref('#ffffff') // Transparent/white by default

const emit = defineEmits(['update:modelValue', 'showPipe'])
  import { useEditor, EditorContent } from '@tiptap/vue-3'
  import StarterKit from '@tiptap/starter-kit'
  import { Node, Mark, mergeAttributes, Extension } from '@tiptap/core'
  // Note: Additional extensions need to be installed
  import { TextAlign } from '@tiptap/extension-text-align'
  import { Underline } from '@tiptap/extension-underline'
  import { Subscript } from '@tiptap/extension-subscript'
  import { Superscript } from '@tiptap/extension-superscript'
  import { TextStyle } from '@tiptap/extension-text-style'
  import { Color } from '@tiptap/extension-color'
  import { Highlight } from '@tiptap/extension-highlight'
  import { Table } from '@tiptap/extension-table'
  import { TableRow } from '@tiptap/extension-table-row'
  import { TableHeader } from '@tiptap/extension-table-header'
  import { TableCell } from '@tiptap/extension-table-cell'

  // Custom Div Extension
  const CustomDiv = Node.create({
    name: 'customDiv',
    
    group: 'block',
    
    content: 'inline*',
    
    parseHTML() {
      return [
        {
          tag: 'div',
        },
      ]
    },
    
    renderHTML({ HTMLAttributes }) {
      return ['div', mergeAttributes(HTMLAttributes), 0]
    },
    
    addCommands() {
      return {
        insertCustomDiv: (attributes) => ({ commands }) => {
          return commands.insertContent({
            type: this.name,
            attrs: attributes,
            content: [
              {
                type: 'text',
                text: 'CustomElementAdded',
              },
            ],
          })
        },
      }
    },
  })

  // Custom Font Size Extension with semantic labels
  const FontSize = Extension.create({
    name: 'fontSize',

    addCommands() {
      return {
        setFontSize: (size) => ({ commands }) => {
          if (!size || size === 'Normal') {
            return commands.unsetFontSize()
          }
          return commands.setMark('fontTag', { 'data-fs': size })
        },
        unsetFontSize: () => ({ commands }) => {
          return commands.unsetMark('fontTag')
        },
      }
    },
  })

  // Font Tag Mark
  const FontTag = Mark.create({
    name: 'fontTag',
    
    addAttributes() {
      return {
        'data-fs': {
          default: null,
          parseHTML: element => element.getAttribute('data-fs'),
          renderHTML: attributes => {
            if (!attributes['data-fs']) {
              return {}
            }
            return {
              'data-fs': attributes['data-fs'],
            }
          },
        },
      }
    },
    
    parseHTML() {
      return [
        {
          tag: 'font[data-fs]',
        },
      ]
    },
    
    renderHTML({ HTMLAttributes }) {
      return ['font', mergeAttributes(HTMLAttributes), 0]
    },
  })

  const editor = useEditor({
    content: props.modelValue,
    extensions: [
      StarterKit,
      CustomDiv,
      TextAlign.configure({
        types: ['heading', 'paragraph'],
      }),
      Underline,
      Subscript,
      Superscript,
      TextStyle,
      FontSize,
      FontTag,
      Color,
      Highlight.configure({
        multicolor: true,
      }),
      Table.configure({
        resizable: true,
      }),
      TableRow,
      TableHeader,
      TableCell,
    ],
    onUpdate: ({ editor }) => {
        editing.value = true
        emit('update:modelValue', editor.getHTML())
    },
    onSelectionUpdate: ({ editor }) => {
        updateCurrentFontSize(editor)
        updateCurrentColors(editor)
    },
    onFocus: ({ editor }) => {
        editing.value = true
        updateCurrentFontSize(editor)
        updateCurrentColors(editor)
    },
    onBlur: () => {
        editing.value = false
    },
    editable: !props.disabled
  })

watch(() => props.modelValue, (newVal) => {
    if(editing.value || !editor.value || !editor.value.commands) return
    editor.value.commands.setContent(newVal || '')
})

// Watch for editor initialization to set up initial values
watch(() => editor.value, (newEditor) => {
    if (newEditor) {
        updateCurrentFontSize(newEditor)
        updateCurrentColors(newEditor)
    }
})

// Function to insert custom element
const insertCustomElement = () => {
    if (!editor.value) return
    
    console.log('Inserting custom div element...')
    
    // Use the custom div extension command
    editor.value
        .chain()
        .focus()
        .insertCustomDiv()
        .run()
}

// Function to update current font size based on selection
const updateCurrentFontSize = (editorInstance) => {
    if (!editorInstance) return
    
    // Check if the current selection has a font tag with data-fs attribute
    const fontMark = editorInstance.getAttributes('fontTag')
    
    if (fontMark && fontMark['data-fs']) {
        currentFontSize.value = fontMark['data-fs']
    } else {
        currentFontSize.value = ''
    }
}

// Function to update current colors based on selection
const updateCurrentColors = (editorInstance) => {
    if (!editorInstance) return
    
    // Get text style attributes (for text color)
    const textStyleAttrs = editorInstance.getAttributes('textStyle')
    if (textStyleAttrs && textStyleAttrs.color) {
        currentTextColor.value = textStyleAttrs.color
    } else {
        currentTextColor.value = '#000000' // Default black
    }
    
    // Get highlight attributes
    const highlightAttrs = editorInstance.getAttributes('highlight')
    if (highlightAttrs && highlightAttrs.color) {
        currentHighlightColor.value = highlightAttrs.color
    } else {
        currentHighlightColor.value = '#ffffff' // Default transparent/white
    }
}

// Function to change font size
const changeFontSize = (size) => {
    if (!editor.value) return
    
    if (!size) {
        // Reset font size if empty value selected
        editor.value.chain().focus().unsetFontSize().run()
        currentFontSize.value = ''
        return
    }
    
    editor.value
        .chain()
        .focus()
        .setFontSize(size)
        .run()
    
    // Update the current font size immediately
    currentFontSize.value = size
}

// Function to change text color
const changeTextColor = (color) => {
    if (!editor.value) return
    
    editor.value
        .chain()
        .focus()
        .setColor(color)
        .run()
    
    // Update the current text color immediately
    currentTextColor.value = color
}

// Function to change highlight color
const changeHighlightColor = (color) => {
    if (!editor.value) return
    
    editor.value
        .chain()
        .focus()
        .setHighlight({ color })
        .run()
    
    // Update the current highlight color immediately
    currentHighlightColor.value = color
}

// Function to remove text color
const removeTextColor = () => {
    if (!editor.value) return
    
    editor.value.chain().focus().unsetColor().run()
    currentTextColor.value = '#000000' // Reset to default black
}

// Function to remove highlight
const removeHighlight = () => {
    if (!editor.value) return
    
    editor.value.chain().focus().unsetHighlight().run()
    currentHighlightColor.value = '#ffffff' // Reset to transparent/white
}

</script>

<style scoped>
.don-tiptap-editor {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
    background: #f3f4f6;
}

.don-tiptap-editor-toolbars {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 12px;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    align-items: center;
}

.toolbar-group {
    display: flex;
    gap: 4px;
    align-items: center;
    border-right: 1px solid #e5e7eb;
    padding-right: 8px;
    margin-right: 4px;
}

.toolbar-group:last-child {
    border-right: none;
    padding-right: 0;
    margin-right: 0;
}

.toolbar-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    transition: all 0.15s ease;
}

.toolbar-btn:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.toolbar-btn:active {
    background: #e5e7eb;
}

.toolbar-btn.is-active {
    background:  var(--mcolor);
    border-color:  var(--mcolor);
    color: white;
}

/* .toolbar-btn.danger {
    color: #dc2626;
} */

.toolbar-btn.danger:hover {
    background: #fef2f2;
    border-color: #fca5a5;
}

.toolbar-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.toolbar-btn:disabled:hover {
    background: white;
    border-color: #d1d5db;
}

.toolbar-btn.disabled {
    opacity: 0.4;
    cursor: not-allowed;
    pointer-events: none;
}

.disabled-group {
    opacity: 0.6;
}

.toolbar-note {
    font-size: 11px;
    color: #6b7280;
    font-style: italic;
    white-space: nowrap;
    margin-right: 8px;
}

.color-group {
    display: flex;
    align-items: center;
    gap: 4px;
}

.color-label {
    font-size: 11px;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.color-picker {
    width: 32px;
    height: 32px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    cursor: pointer;
    padding: 0;
    background: none;
}

.color-picker::-webkit-color-swatch-wrapper {
    padding: 0;
    border: none;
    border-radius: 3px;
}

.color-picker::-webkit-color-swatch {
    border: none;
    border-radius: 3px;
}

.font-size-dropdown {
    padding: 4px 8px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    background: white;
    color: #374151;
    font-size: 12px;
    cursor: pointer;
    min-width: 70px;
    margin-right: 4px;
}

.font-size-dropdown:hover {
    border-color: #9ca3af;
    background: #f9fafb;
}

.font-size-dropdown:focus {
    outline: none;
    border-color: var(--mcolor, #3b82f6);
    box-shadow: 0 0 0 1px var(--mcolor, #3b82f6);
}

.don-tiptap-editor-content {
    padding: 16px;
    min-height: 100px;
}

.don-tiptap-editor-content .ProseMirror {
    outline: none;
    line-height: 1.6;
}

.don-tiptap-editor-content .ProseMirror p {
    margin: 0 0 8px 0;
}

.don-tiptap-editor-content .ProseMirror p:last-child {
    margin-bottom: 0;
}

.don-tiptap-editor-content .ProseMirror ul,
.don-tiptap-editor-content .ProseMirror ol {
    padding-left: 20px;
    margin: 8px 0;
}

.don-tiptap-editor-content .ProseMirror table {
    border-collapse: collapse;
    table-layout: fixed;
    width: 100%;
    margin: 16px 0;
    overflow: hidden;
}

.don-tiptap-editor-content .ProseMirror table td,
.don-tiptap-editor-content .ProseMirror table th {
    min-width: 1em;
    border: 2px solid #e5e7eb;
    padding: 8px;
    vertical-align: top;
    box-sizing: border-box;
    position: relative;
}

.don-tiptap-editor-content .ProseMirror table th {
    font-weight: bold;
    text-align: left;
    background-color: #f9fafb;
}

.don-tiptap-editor-content .ProseMirror table .selectedCell:after {
    z-index: 2;
    position: absolute;
    content: "";
    left: 0; right: 0; top: 0; bottom: 0;
    background: rgba(200, 200, 255, 0.4);
    pointer-events: none;
}

.don-tiptap-editor-content .ProseMirror table .column-resize-handle {
    position: absolute;
    right: -2px;
    top: 0;
    bottom: -2px;
    width: 4px;
    background-color: var(--mcolor);
    pointer-events: none;
}


.don-tiptap-editor-content /deep/ .ProseMirror-focused{
    outline: none;
}

.don-tiptap-editor-content /deep/ .ProseMirror{
    min-height: 150px;
}

/* Font size styling for semantic labels */
.don-tiptap-editor-content /deep/ font[data-fs="Small"] {
    font-size: 12px;
}

.don-tiptap-editor-content /deep/ font[data-fs="Normal"] {
    font-size: 16px;
}

.don-tiptap-editor-content /deep/ font[data-fs="Large"] {
    font-size: 20px;
}

.don-tiptap-editor-content /deep/ font[data-fs="Huge"] {
    font-size: 24px;
}
</style>