<script setup>
import { computed } from "vue";
const ICONS = {
    HIDE: "hide",
    UNHIDE: "unhide",
    WRITE: "write",
    EN_WRITE: "en_write",
    GALLERY: "gallery",
    DELETE: "delete",
    SETTING: "setting",
    EDIT: "edit",
    COPY: "copy",
    ARROWRIGHT: "arrowright",
    ARROWLEFT: "arrowleft",
    LINK: "link",
    CLICK: "click",
    VARIABLE: "variable",
    TERMINAL: "terminal",
    OTHER: "other",
    QUESTION: "question",
    APPLY: "apply",
    FOLDER: "folder",
    PLUS: "plus",
    PHOTO: "photo",
    VIDEO: "video",
    CODE: "code",
    PENCIL: "pencil",
    UPLOAD: "upload",
    CLOSE: "close",
    MOVE: "move",
    LOCK: "lock",
    UNLOCK: "unlock"
};
const props = defineProps({
    icon: {
        default: null,
    },
    size: {
        default: "sm",
    },
});

const size = computed(() => {
    return {
        md: "h-8 w-8",
        sm: "h-5 w-5",
    }[props.size];
});
</script>
<template>
    <svg v-if="props.icon === ICONS.MOVE" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
        fill="none" stroke="currentColor">
        <path d="M18 11h-5V6h3l-4-4-4 4h3v5H6V8l-4 4 4 4v-3h5v5H8l4 4 4-4h-3v-5h5v3l4-4-4-4z"></path>
    </svg>
    <svg v-if="props.icon === ICONS.HIDE" width="24" height="24" viewBox="0 0 2407 2579" fill="none"
        xmlns="http://www.w3.org/2000/svg">
        <path
            d="M1201.44 564.52C835.186 564.52 459.483 791.655 163.715 1245.93C155.272 1259.04 150.646 1274.53 150.42 1290.46C150.194 1306.38 154.379 1322.02 162.446 1335.4C389.7 1716.56 760.375 2014.64 1201.44 2014.64C1637.72 2014.64 2016.01 1715.66 2243.68 1333.64C2251.56 1320.52 2255.75 1305.26 2255.75 1289.66C2255.75 1274.06 2251.56 1258.79 2243.68 1245.68C2015.49 868.039 1634.43 564.52 1201.44 564.52Z"
            stroke="currentColor" stroke-width="180" stroke-linecap="round" stroke-linejoin="round" />
        <path
            d="M1203.04 1692.39C1410.67 1692.39 1578.98 1512.05 1578.98 1289.58C1578.98 1067.11 1410.67 886.77 1203.04 886.77C995.416 886.77 827.103 1067.11 827.103 1289.58C827.103 1512.05 995.416 1692.39 1203.04 1692.39Z"
            stroke="currentColor" stroke-width="180" stroke-miterlimit="10" />
    </svg>
    <svg v-if="props.icon === ICONS.UNHIDE" width="24" height="24" viewBox="0 0 2407 2579" fill="currentColor"
        background-color="currentColor" style="background-color: currentColor; border-radius: 50%"
        xmlns="http://www.w3.org/2000/svg">
        <path
            d="M1201.44 564.52C835.186 564.52 459.483 791.655 163.715 1245.93C155.272 1259.04 150.646 1274.53 150.42 1290.46C150.194 1306.38 154.379 1322.02 162.446 1335.4C389.7 1716.56 760.375 2014.64 1201.44 2014.64C1637.72 2014.64 2016.01 1715.66 2243.68 1333.64C2251.56 1320.52 2255.75 1305.26 2255.75 1289.66C2255.75 1274.06 2251.56 1258.79 2243.68 1245.68C2015.49 868.039 1634.43 564.52 1201.44 564.52Z"
            stroke="white" stroke-width="180" stroke-linecap="round" stroke-linejoin="round" />
        <path
            d="M1203.04 1692.39C1410.67 1692.39 1578.98 1512.05 1578.98 1289.58C1578.98 1067.11 1410.67 886.77 1203.04 886.77C995.416 886.77 827.103 1067.11 827.103 1289.58C827.103 1512.05 995.416 1692.39 1203.04 1692.39Z"
            stroke="white" stroke-width="180" stroke-miterlimit="10" />
        <!-- Slash Line -->
        <path d="M300 300L2100 2280" stroke="white" stroke-width="180" stroke-linecap="round" />
    </svg>
    <svg v-if="props.icon === ICONS.WRITE" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
        fill="none">
        <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1" />
        <path
            d="M7 17.013L11.413 16.998L21.045 7.458C21.423 7.08 21.631 6.578 21.631 6.044C21.631 5.51 21.423 5.008 21.045 4.63L19.459 3.044C18.703 2.288 17.384 2.292 16.634 3.041L7 12.583V17.013Z"
            stroke="currentColor" stroke-width="1" fill="none" stroke-linecap="round" />
        <path d="M18.045 4.458L19.634 6.041L18.037 7.623L16.451 6.038L18.045 4.458Z" stroke="currentColor"
            stroke-width="1" fill="none" stroke-linecap="round" />
        <path d="M9 13.417L15.03 7.444L16.616 9.03L10.587 15.001L9 15.006V13.417Z" stroke="currentColor"
            stroke-width="1" fill="none" stroke-linecap="round" />
        <!-- <path
            d="M5 21H19C20.103 21 21 20.103 21 19V10.332L19 12.332V19H8.158C8.132 19 8.105 19.01 8.079 19.01C8.046 19.01 8.013 19.001 7.98 19H5V5H11.847L13.847 3H5C3.897 3 3 3.897 3 5V19C3 20.103 3.897 21 5 21Z"
            stroke="currentColor"
            stroke-width="1"
            fill="none"
            stroke-linecap="round"
        /> -->
    </svg>
    <svg v-if="props.icon === ICONS.EN_WRITE" width="26" height="26" viewBox="0 0 26 26" fill="none"
        xmlns="http://www.w3.org/2000/svg">
        <rect width="26" height="26" rx="13" fill="#E28733" />
        <path
            d="M20 4H6C4.89543 4 4 4.89543 4 6V20C4 21.1046 4.89543 22 6 22H20C21.1046 22 22 21.1046 22 20V6C22 4.89543 21.1046 4 20 4Z"
            stroke="white" />
        <path
            d="M8 18.0129L12.413 17.9979L22.045 8.4579C22.423 8.0799 22.631 7.5779 22.631 7.0439C22.631 6.5099 22.423 6.0079 22.045 5.6299L20.459 4.0439C19.703 3.2879 18.384 3.2919 17.634 4.0409L8 13.5829V18.0129Z"
            stroke="white" stroke-linecap="round" />
        <path d="M19.0452 5.45801L20.6342 7.04101L19.0372 8.62301L17.4512 7.03801L19.0452 5.45801Z" stroke="white"
            stroke-linecap="round" />
        <path d="M10 14.4171L16.03 8.44409L17.616 10.0301L11.587 16.0011L10 16.0061V14.4171Z" stroke="white"
            stroke-linecap="round" />
    </svg>

    <svg v-if="props.icon === ICONS.GALLERY" xmlns="http://www.w3.org/2000/svg" :class="size" fill="none"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
    </svg>
    <svg v-if="props.icon === ICONS.DELETE" xmlns="http://www.w3.org/2000/svg" :class="size" fill="none"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
    </svg>
    <svg v-if="props.icon === ICONS.SETTING" xmlns="http://www.w3.org/2000/svg" :class="size" fill="none"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
    <svg v-if="props.icon === ICONS.EDIT" xmlns="http://www.w3.org/2000/svg" :class="size" fill="none"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
    </svg>
    <svg v-if="props.icon === ICONS.COPY" xmlns="http://www.w3.org/2000/svg" :class="size" fill="none"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
    </svg>
    <svg v-if="props.icon === ICONS.ARROWRIGHT" xmlns="http://www.w3.org/2000/svg" :class="size" fill="none"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
    </svg>
    <svg v-if="props.icon === ICONS.ARROWLEFT" xmlns="http://www.w3.org/2000/svg" :class="size" fill="none"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
    </svg>
    <svg v-if="props.icon === ICONS.LINK" xmlns="http://www.w3.org/2000/svg" :class="size" fill="none"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
    </svg>
    <svg v-if="props.icon === ICONS.CLICK" xmlns="http://www.w3.org/2000/svg" :class="size" fill="none"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
    </svg>
    <svg v-if="props.icon === ICONS.VARIABLE" xmlns="http://www.w3.org/2000/svg" :class="size" fill="none"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M4.871 4A17.926 17.926 0 003 12c0 2.874.673 5.59 1.871 8m14.13 0a17.926 17.926 0 001.87-8c0-2.874-.673-5.59-1.87-8M9 9h1.246a1 1 0 01.961.725l1.586 5.55a1 1 0 00.961.725H15m1-7h-.08a2 2 0 00-1.519.698L9.6 15.302A2 2 0 018.08 16H8" />
    </svg>
    <svg v-if="props.icon === ICONS.TERMINAL" xmlns="http://www.w3.org/2000/svg" :class="size" fill="none"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
    </svg>
    <svg v-if="props.icon === ICONS.OTHER" xmlns="http://www.w3.org/2000/svg" :class="size" fill="none"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z" />
    </svg>
    <svg v-if="props.icon === ICONS.QUESTION" xmlns="http://www.w3.org/2000/svg" :class="size" fill="none"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    <svg v-if="props.icon === ICONS.APPLY" xmlns="http://www.w3.org/2000/svg" :class="size" fill="none"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    <svg v-if="props.icon === ICONS.FOLDER" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
        stroke-width="1.5" stroke="currentColor" :class="size">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 10.5v6m3-3H9m4.06-7.19l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
    </svg>
    <svg v-if="props.icon === ICONS.PLUS" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
        stroke-width="1.5" stroke="currentColor" :class="size">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
    </svg>
    <svg v-if="props.icon === ICONS.PHOTO" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
        stroke-width="1.5" stroke="currentColor" :class="size">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
    </svg>
    <svg v-if="props.icon === ICONS.VIDEO" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
        stroke-width="1.5" stroke="currentColor" :class="size">
        <path stroke-linecap="round"
            d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
    </svg>
    <svg v-if="props.icon === ICONS.CODE" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
        stroke-width="1.5" stroke="currentColor" :class="size">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M14.25 9.75L16.5 12l-2.25 2.25m-4.5 0L7.5 12l2.25-2.25M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />
    </svg>
    <svg v-if="props.icon === ICONS.PENCIL" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
        stroke-width="1.5" stroke="currentColor" :class="size">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
    </svg>
    <svg v-if="props.icon === ICONS.UPLOAD" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
        stroke-width="1.5" stroke="currentColor" :class="size">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
    </svg>
    <svg v-if="props.icon === ICONS.CLOSE" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
        stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
    </svg>
    <svg v-if="props.icon === ICONS.LOCK" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
        viewBox="0 0 24 24">
        <path
            d="M6 22h12c1.1 0 2-.9 2-2v-9c0-1.1-.9-2-2-2h-1V7c0-2.76-2.24-5-5-5S7 4.24 7 7v2H6c-1.1 0-2 .9-2 2v9c0 1.1.9 2 2 2M9 7c0-1.65 1.35-3 3-3s3 1.35 3 3v2H9zm-3 4h12v9H6z">
        </path>
    </svg>
    <svg v-if="props.icon === ICONS.UNLOCK" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
        <path
            d="M20 9h-8V7c0-2.76-2.24-5-5-5S2 4.24 2 7h2c0-1.65 1.35-3 3-3s3 1.35 3 3v2H8c-1.1 0-2 .9-2 2v9c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-9c0-1.1-.9-2-2-2M8 20v-9h12v9z">
        </path>
    </svg>
</template>
