<script setup>
import Quill from 'quill';
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';

const quill = ref();

const toolbarOptions = ref({
    essential: [
        [{ header: [1, 2, 3, 4, 5, 6, false] }],
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }, { align: [] }],
        ['blockquote', 'code-block', 'link'],
        [{ color: [] }, 'clean'],
    ],
    minimal: [
        [{ header: 1 }, { header: 2 }],
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }, { align: [] }],
    ],
    full: [
        ['bold', 'italic', 'underline', 'strike'],
        ['blockquote', 'code-block'],
        [{ header: 1 }, { header: 2 }],
        [{ list: 'ordered' }, { list: 'bullet' }],
        [{ script: 'sub' }, { script: 'super' }],
        [{ indent: '-1' }, { indent: '+1' }],
        [{ direction: 'rtl' }],
        [{ size: ['small', false, 'large', 'huge'] }],
        [{ header: [1, 2, 3, 4, 5, 6, false] }],
        [{ color: [] }, { background: [] }],
        [{ font: [] }],
        [{ align: [] }],
        ['link', 'video', 'image'],
        ['clean'], // remove formatting button
    ],
});

const props = defineProps({
    content: {
        type: [String, Object],
        default: () => { },
    },
    contentType: {
        type: String,
        default: 'delta',
        validator: (value) => {
            return ['delta', 'html', 'text'].includes(value);
        },
    },
    enable: {
        type: Boolean,
        default: true,
    },
    readOnly: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        required: false,
    },
    theme: {
        type: String,
        default: 'snow',
        validator: (value) => {
            return ['snow', 'bubble', ''].includes(value);
        },
    },
    toolbar: {
        type: [String, Array, Object],
        required: false
    },
    modules: {
        type: Object,
        required: false,
    },
    attributors: {
        type: [Object, Array],
        required: false
    },
    options: {
        type: Object,
        required: false,
    },
    globalOptions: {
        type: Object,
        required: false,
    }
});

const emit = defineEmits(['textChange',
        'selectionChange',
        'editorChange',
        'update:content',
        'focus',
        'blur',
        'ready']);
const editor = ref();

onBeforeUnmount(() => {
    quill.value = null;
});

onMounted(() => {
    initialize();
});

const initialize = () => {
    let _a;
    if (!editor.value)
        return;
    let options = composeOptions();

    if (props.modules) {
        if (Array.isArray(props.modules)) {
            for (const module of props.modules) {
                Quill.register(`modules/${module.name}`, module.module);
            }
        }
        else {
            Quill.register(`modules/${props.modules.name}`, props.modules.module);
        }
    }
    
    if (props.attributors) {
        if (Array.isArray(props.attributors)) {
            for (const attributor of props.attributors) {
                Quill.register(Quill.import(`attributors/${attributor}`), false);
            }
        }
    }

    quill.value = new Quill(editor.value, options);
    setContents(props.content);
    quill.value.on('text-change', (delta, oldContents, source) => {
        emit('update:content', getContents());
    });
    if (props.theme !== 'bubble')
        editor.value.classList.remove('ql-bubble');
    if (props.theme !== 'snow')
        editor.value.classList.remove('ql-snow');
    // Fix clicking the quill toolbar is detected as blur event
    (_a = quill.value
        .getModule('toolbar')) === null || _a === void 0 ? void 0 : _a.container.addEventListener('mousedown', (e) => {
        e.preventDefault();
    });
};

const composeOptions = () => {
    const clientOptions = {};
    if (props.theme !== '')
        clientOptions.theme = props.theme;
    if (props.readOnly)
        clientOptions.readOnly = props.readOnly;
    if (props.placeholder)
        clientOptions.placeholder = props.placeholder;
    if (props.toolbar && props.toolbar !== '') {
        clientOptions.modules = {
            toolbar: (() => {
                if (typeof props.toolbar === 'object') {
                    return props.toolbar;
                }
                else if (typeof props.toolbar === 'string') {
                    const str = props.toolbar;
                    return str.charAt(0) === '#'
                        ? props.toolbar
                        : toolbarOptions.value[props.toolbar];
                }
                return;
            })(),
        };
    }
    if (props.modules) {
        const modules = (() => {
            var _a, _b;
            const modulesOption = {};
            if (Array.isArray(props.modules)) {
                for (const module of props.modules) {
                    modulesOption[module.name] = (_a = module.options) !== null && _a !== void 0 ? _a : {};
                }
            }
            else {
                modulesOption[props.modules.name] = (_b = props.modules.options) !== null && _b !== void 0 ? _b : {};
            }
            return modulesOption;
        })();
        clientOptions.modules = Object.assign({}, clientOptions.modules, modules);
    }
    return Object.assign({}, props.globalOptions, props.options, clientOptions);
};

const setContents = (content, source = 'api') => {
    if (props.contentType === 'html') {
        setHTML(content);
    }
    else if (props.contentType === 'text') {
        setText(content, source);
    }
    else {
        quill.value === null || quill.value === void 0 ? void 0 : quill.value.setContents(content, source);
    }
};

const setHTML = (html) => {
    if (quill.value)
        quill.value.root.innerHTML = html;
};

const setText = (text, source = 'api') => {
    quill.value === null || quill.value === void 0 ? void 0 : quill.value.setText(text, source);
};

const getContents = (index, length) => {
    if (props.contentType === 'html') {
        return getHTML();
    }
    else if (props.contentType === 'text') {
        return getText(index, length);
    }
    return quill.value === null || quill.value === void 0 ? void 0 : quill.value.getContents(index, length);
};

const getHTML = () => {
    let _a;
    return (_a = quill.value === null || quill.value === void 0 ? void 0 : quill.value.root.innerHTML) !== null && _a !== void 0 ? _a : '';
};

const getText = (index, length) => {
    let _a;
    return (_a = quill.value === null || quill.value === void 0 ? void 0 : quill.value.getText(index, length)) !== null && _a !== void 0 ? _a : '';
};

watch(() => props.enable, (newVal) => {
    if (quill.value)
        quill.value.enable(newVal);
});
</script>

<template>
  <div ref="editor"></div>
</template>
