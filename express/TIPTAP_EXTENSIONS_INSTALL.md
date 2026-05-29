# TipTap Editor Extensions Installation Guide

Your TipTap editor is currently working with basic formatting features. To unlock all the advanced features you requested, you need to install additional TipTap extensions.

## 🚨 Permission Issue Fix

First, fix the node_modules ownership issue:

```bash
# Change ownership of node_modules back to your user
sudo chown -R $USER:$USER node_modules/
sudo chown -R $USER:$USER package-lock.json
```

## 📦 Install Required Extensions

After fixing permissions, run:

```bash
npm install @tiptap/extension-text-align @tiptap/extension-underline @tiptap/extension-subscript @tiptap/extension-superscript @tiptap/extension-text-style @tiptap/extension-color @tiptap/extension-highlight @tiptap/extension-table @tiptap/extension-table-row @tiptap/extension-table-header @tiptap/extension-table-cell --save
```

## 🔧 Enable Extensions

After installation, uncomment the imports and extensions in `/resources/js/S4/TipTapEditor.vue`:

1. **Uncomment the imports** (lines 212-222):
```javascript
import TextAlign from '@tiptap/extension-text-align'
import Underline from '@tiptap/extension-underline'
import Subscript from '@tiptap/extension-subscript'
import Superscript from '@tiptap/extension-superscript'
import TextStyle from '@tiptap/extension-text-style'
import Color from '@tiptap/extension-color'
import Highlight from '@tiptap/extension-highlight'
import Table from '@tiptap/extension-table'
import TableRow from '@tiptap/extension-table-row'
import TableHeader from '@tiptap/extension-table-header'
import TableCell from '@tiptap/extension-table-cell'
```

2. **Replace the extensions array** (lines 226-229) with:
```javascript
extensions: [
  StarterKit,
  Underline,
  Subscript,
  Superscript,
  TextStyle,
  Color,
  Highlight.configure({
    multicolor: true,
  }),
  TextAlign.configure({
    types: ['heading', 'paragraph'],
  }),
  Table.configure({
    resizable: true,
  }),
  TableRow,
  TableHeader,
  TableCell,
],
```

3. **Replace the disabled toolbar section** with the full toolbar (which I can provide after installation).

## ✅ Currently Working Features

The editor currently supports:
- ✅ **Bold** (B)
- ✅ **Italic** (I) 
- ✅ **Strikethrough** (S)
- ✅ **Bullet Lists** (•)
- ✅ **Numbered Lists** (1.)
- ✅ **Headings** (H1, H2, H3)

## 🔄 Features Requiring Installation

After installing extensions, you'll get:
- 🔲 **Underline** (U)
- 🔲 **Subscript** (X₂)
- 🔲 **Superscript** (X²)
- 🔲 **Text Alignment** (left, center, right, justify)
- 🔲 **Text Colors**
- 🔲 **Background Colors**
- 🔲 **Tables** with full editing capabilities

## 🚀 After Installation

Once you've installed the extensions and updated the code, restart your development server and you'll have a fully-featured rich text editor!




