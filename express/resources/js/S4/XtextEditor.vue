<template>
    <div class="editor-container">
        <!-- Toolbar -->
        <div class="toolbar relative">
            <div class="border-r mr-1 border-gray-300 h-[3vmin]">
                <button @click="_fn.undo()" :class="{ 'disabled-btn': !canUndo }" :disabled="!canUndo" title="Undo">
                    <svg viewBox="0 0 76.01 76.01" xmlns="http://www.w3.org/2000/svg" height="100%" width="100%"
                        xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" baseProfile="full"
                        enable-background="new 0 0 76.01 76.01" xml:space="preserve">
                        <g id="SVGRepo_iconCarrier">
                            <path :fill="canUndo ? 'black' : 'lightgray'"
                                d="M 25.3362,20.5864L 25.3348,29.2137C 28.5107,25.8499 33.0116,23.7507 38.0029,23.7507C 47.6232,23.7507 55.422,31.5494 55.422,41.1698C 55.422,45.9799 53.4723,50.3347 50.32,53.4869L 46.401,49.5679C 48.5503,47.4187 49.8796,44.4495 49.8796,41.1699C 49.8796,34.6106 44.5623,29.2932 38.003,29.2932C 34.4855,29.2932 31.3251,30.8224 29.1504,33.2522L 38.0029,33.2531L 33.2529,38.0031L 20.5862,38.0031L 20.5862,25.3364L 25.3362,20.5864 Z ">
                            </path>
                        </g>
                    </svg>
                </button>

                <button @click="_fn.redo()" :class="{ 'disabled-btn': !canRedo }" :disabled="!canRedo" title="Redo">
                    <svg viewBox="0 0 76.01 76.01" xmlns="http://www.w3.org/2000/svg" height="100%" width="100%"
                        xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" baseProfile="full"
                        enable-background="new 0 0 76.01 76.01" xml:space="preserve">
                        <g id="SVGRepo_iconCarrier">
                            <path :fill="canRedo ? 'black' : 'lightgray'"
                                d="M 50.672,20.5864L 55.4219,25.3364L 55.422,38.0031L 42.7553,38.0031L 38.0053,33.2531L 46.8578,33.2522C 44.6831,30.8224 41.5227,29.2932 38.0052,29.2932C 31.4459,29.2932 26.1285,34.6106 26.1285,41.1699C 26.1285,44.4495 27.4579,47.4187 29.6071,49.5679L 25.6881,53.4869C 22.5359,50.3347 20.5862,45.9799 20.5862,41.1698C 20.5862,31.5494 28.385,23.7507 38.0053,23.7507C 42.9966,23.7507 47.4975,25.8499 50.6734,29.2137L 50.672,20.5864 Z ">
                            </path>
                        </g>
                    </svg>
                </button>
            </div>
            <select
                class="px-3 py-2 rounded-lg bg-white/70 border-2 border-purple-200 hover:border-purple-400 transition-all focus:outline-none focus:ring-2 focus:ring-yellow-500"
                @change="_fn.format('fontFamily', $event.target.value)" title="Font Family">
                <option value="default" disabled>Please Select</option>
                <option value="Arial" style="font-family: Arial;" selected>Default</option>
                <option value="Comic Sans MS" style="font-family: 'Comic Sans MS';">Comic Sans MS</option>
                <option value="Courier New" style="font-family: 'Courier New';">Courier New</option>
                <option value="Crimson Text" style="font-family: 'Crimson Text';">Crimson Text</option>
                <option value="Georgia" style="font-family: Georgia;">Georgia</option>
                <option value="Lato" style="font-family: 'Lato';">Lato</option>
                <option value="Lucida Console" style="font-family: 'Lucida Console';">Lucida Console</option>
                <option value="Merriweather" style="font-family: 'Merriweather';">Merriweather</option>
                <option value="Montserrat" style="font-family: 'Montserrat';">Montserrat</option>
                <option value="Mulish" style="font-family: 'Mulish';">Mulish</option>
                <option value="Nunito" style="font-family: 'Nunito';">Nunito</option>
                <option value="Open Sans" style="font-family: 'Open Sans';">Open Sans</option>
                <option value="Oswald" style="font-family: 'Oswald';">Oswald</option>
                <option value="Playfair Display" style="font-family: 'Playfair Display';">Playfair Display</option>
                <option value="Poppins" style="font-family: 'Poppins';">Poppins</option>
                <option value="Raleway" style="font-family: 'Raleway';">Raleway</option>
                <option value="Roboto" style="font-family: 'Roboto';">Roboto</option>
                <option value="Tahoma" style="font-family: 'Tahoma';">Tahoma</option>
                <option value="Times New Roman" style="font-family: 'Times New Roman';">Times New Roman</option>
                <option value="Ubuntu" style="font-family: 'Ubuntu';">Ubuntu</option>
                <option value="Verdana" style="font-family: 'Verdana';">Verdana</option>
            </select>

            <div class="ml-1 border-l border-gray-300 h-[3vmin]"> <button @click="_fn.increaseFontSize()"
                    title="Increase Font Size"><svg class="pointer-events-none touch-none" width="100%" height="100%"
                        fill="#ffffff" stroke="#ffffff" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <g fill="none" fill-rule="evenodd" stroke="none">
                            <g fill="black" fill-rule="nonzero">
                                <path
                                    d="m12.81 4.4106 0.067436 0.10872 0.053157 0.11509 5.5 14.005c0.20189 0.51406-0.051181 1.0945-0.56524 1.2963-0.47734 0.18747-1.0119-0.017349-1.2474-0.45915l-0.048893-0.10609-1.3242-3.3711h-6.491l-1.3232 3.3711c-0.18747 0.47734-0.7013 0.72965-1.1853 0.60161l-0.11101-0.036369c-0.47734-0.18747-0.72965-0.7013-0.60161-1.1853l0.036369-0.11101 5.5-14.005c0.29975-0.76325 1.3036-0.83785 1.741-0.22381zm-0.8102 3.3234-2.461 6.265h4.921l-2.46-6.265zm5.6835-4.6636c0.20104-0.094004 0.43401-0.094004 0.63505 0l0.097563 0.054836 2.251 1.4958 0.087807 0.068112c0.27157 0.24478 0.33017 0.65801 0.12177 0.97164-0.20841 0.31363-0.61207 0.41968-0.94294 0.26414l-0.096808-0.054566-1.8359-1.2197-1.8359 1.2197-0.096808 0.054566c-0.33087 0.15555-0.73453 0.049489-0.94294-0.26414-0.20841-0.31363-0.14981-0.72686 0.12177-0.97164l0.087807-0.068112 2.251-1.4958 0.097563-0.054836z">
                                </path>
                            </g>
                        </g>
                    </svg>
                </button>
                <button @click="_fn.decreaseFontSize()" title="Decrease Font Size"><svg
                        class="pointer-events-none touch-none" width="100%" height="100%" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <g fill="none" fill-rule="evenodd">
                            <g fill="black" fill-rule="nonzero">
                                <path
                                    d="m12.798 7.3938 0.068308 0.10462 0.057231 0.11692 3.75 9c0.21242 0.5098-0.02866 1.0953-0.53846 1.3077-0.47339 0.19724-1.012 0.0034657-1.2566-0.4334l-0.051064-0.10506-0.99392-2.3846h-3.667l-0.99292 2.3846c-0.19724 0.47339-0.71616 0.71507-1.1975 0.57711l-0.11024-0.038644c-0.47339-0.19724-0.71507-0.71616-0.57711-1.1975l0.038644-0.11024 3.75-9c0.30769-0.73846 1.28-0.81231 1.7206-0.22154zm-0.79754 3.2062-1 2.4h2l-1-2.4zm8.8767-7.2632c0.20841 0.31363 0.14981 0.72686-0.12177 0.97164l-0.087807 0.068112-2.251 1.4958c-0.22006 0.14623-0.49807 0.16451-0.73261 0.054836l-0.097563-0.054836-2.251-1.4958c-0.34499-0.22925-0.43882-0.69476-0.20957-1.0397 0.20841-0.31363 0.61207-0.41968 0.94294-0.26414l0.096808 0.054566 1.8359 1.2188 1.8359-1.2188c0.34499-0.22925 0.8105-0.13542 1.0397 0.20957z">
                                </path>
                            </g>
                        </g>
                    </svg>
                </button>
            </div>
            <!-- color changer -->
            <!-- <div class="border-l border-gray-300 h-[3vmin]">
                <button @click="_fn.changeTextColor()" title="Change Text Color">
                    <svg class="pointer-events-none touch-none" width="100%" height="100%" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <g fill="none" fill-rule="evenodd">
                            <g fill="black" fill-rule="nonzero">
                                <path
                                    d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 18a8 8 0 110-16 8 8 0 010 16zm-1-13h2v6h-2zm0 8h2v2h-2z">
                                </path>
                            </g>
                        </g>
                    </svg>
                </button>
            </div> -->


            <!-- Bold, Italic, Underline buttons -->
            <div class="border-l border-gray-300 h-[3vmin]"> <button @click="_fn.format('bold')" title="Bold"><svg
                        class="pointer-events-none touch-none" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 -960 960 960" width="100%" height="100%">
                        <path fill="black"
                            d="M272-200v-560h221q65 0 120 40t55 111q0 51-23 78.5T602-491q25 11 55.5 41t30.5 90q0 89-65 124.5T501-200H272Zm121-112h104q48 0 58.5-24.5T566-372q0-11-10.5-35.5T494-432H393v120Zm0-228h93q33 0 48-17t15-38q0-24-17-39t-44-15h-95v109Z" />
                    </svg></button>

                <button @click="_fn.format('italic')" title="Italic"><svg class="pointer-events-none touch-none"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="100%" height="100%">
                        <path fill="black" d="M200-200v-100h160l120-360H320v-100h400v100H580L460-300h140v100H200Z" />
                    </svg></button>
                <button @click="_fn.format('underline')" title="Underline"><svg class="pointer-events-none touch-none"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="100%" height="100%">
                        <path fill="black"
                            d="M200-120v-80h560v80H200Zm280-160q-101 0-157-63t-56-167v-330h103v336q0 56 28 91t82 35q54 0 82-35t28-91v-336h103v330q0 104-56 167t-157 63Z" />
                    </svg></button>
            </div>
            <div class="border-l border-gray-300 h-[3vmin]">
                <button @click="toggleColorPicker" title="Change Text Color">
                    <!-- <svg class="pointer-events-none touch-none" width="100%" height="100%" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <g fill="none" fill-rule="evenodd">
                            <g fill="black" fill-rule="nonzero">
                                <path
                                    d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 18a8 8 0 110-16 8 8 0 010 16zm-1-13h2v6h-2zm0 8h2v2h-2z">
                                </path>
                            </g>
                        </g>
                    </svg> -->
                    <svg class="pointer-events-none touch-none" width="100%" height="100%" viewBox="0 0 300 300"
                        fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M99.5 194L115.5 156.5H184.5L200 194H228L166.5 46.5C166.5 46.5 157.5 44 150.5 44C143.5 44 133.5 46.5 133.5 46.5L71 194H99.5ZM126.5 131L150.5 74.5L173.5 131H126.5Z"
                            fill="black" stroke="black" />
                        <path d="M62 255V219H237V256L62 255Z" fill="url(#paint0_linear_326_1809)" />
                        <defs>
                            <linearGradient id="paint0_linear_326_1809" x1="63.8648" y1="219" x2="235.135" y2="256"
                                gradientUnits="userSpaceOnUse">
                                <stop stop-color="#E32335" />
                                <stop offset="0.2" stop-color="#FDE10E" />
                                <stop offset="0.4" stop-color="#6ABE46" />
                                <stop offset="0.6" stop-color="#3EC4F2" />
                                <stop offset="0.8" stop-color="#4050A0" />
                                <stop offset="1" stop-color="#DB5056" />
                            </linearGradient>
                        </defs>
                    </svg>
                </button>

                <!-- <color-picker ref="colorPickerRef" v-show="showColorPicker" @mousedown="startDrag"
                    class="fixed z-[999] w-[220px!important] cursor-move" theme="light" :color="color"
                    :sucker-hide="false" :sucker-canvas="suckerCanvas" :sucker-area="suckerArea"
                    @changeColor="_fn.changeTextColor" @openSucker="openSucker" /> -->

            </div>
            <div class="border-l border-gray-300 h-[3vmin]">
                <button @click="_fn.format('justifyLeft')" title="Align Left"><svg
                        class="pointer-events-none touch-none" viewBox="0 0 24 24" height="100%" width="100%"
                        xmlns="http://www.w3.org/2000/svg" fill="#000000">
                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                        <g id="SVGRepo_iconCarrier">
                            <g>
                                <path fill="none" d="M0 0h24v24H0z"></path>
                                <path d="M3 4h18v2H3V4zm0 15h14v2H3v-2zm0-5h18v2H3v-2zm0-5h14v2H3V9z"></path>
                            </g>
                        </g>
                    </svg></button>
                <button @click="_fn.format('justifyCenter')" title="Center"><svg class="pointer-events-none touch-none"
                        viewBox="0 0 24 24" height="100%" width="100%" xmlns="http://www.w3.org/2000/svg"
                        fill="#000000">
                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                        <g id="SVGRepo_iconCarrier">
                            <g>
                                <path fill="none" d="M0 0h24v24H0z"></path>
                                <path d="M3 4h18v2H3V4zm2 15h14v2H5v-2zm-2-5h18v2H3v-2zm2-5h14v2H5V9z"></path>
                            </g>
                        </g>
                    </svg></button>
                <button @click="_fn.format('justifyRight')" title="Align Right"><svg
                        class="pointer-events-none touch-none" viewBox="0 0 24 24" height="100%" width="100%"
                        xmlns="http://www.w3.org/2000/svg" fill="#000000">
                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                        <g id="SVGRepo_iconCarrier">
                            <g>
                                <path fill="none" d="M0 0h24v24H0z"></path>
                                <path d="M3 4h18v2H3V4zm4 15h14v2H7v-2zm-4-5h18v2H3v-2zm4-5h14v2H7V9z"></path>
                            </g>
                        </g>
                    </svg></button>
                <button @click="_fn.format('justifyFull')" title="Justify"><svg class="pointer-events-none touch-none"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" height="100%" width="100%"
                        fill="#000000">
                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                        <g id="SVGRepo_iconCarrier">
                            <g>
                                <path fill="none" d="M0 0h24v24H0z"></path>
                                <path d="M3 4h18v2H3V4zm0 15h18v2H3v-2zm0-5h18v2H3v-2zm0-5h18v2H3V9z"></path>
                            </g>
                        </g>
                    </svg>
                </button>
                <button @click="_fn.format('insertUnorderedList')" title="Bullet List">
                    <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24"
                        style="fill: rgba(0, 0, 0, 1);">
                        <path d="M4 6h2v2H4zm0 5h2v2H4zm0 5h2v2H4zm16-8V6H8.023v2H18.8zM8 11h12v2H8zm0 5h12v2H8z">
                        </path>
                    </svg>
                </button>
                <button @click="_fn.format('insertOrderedList')" title="Numbered List">
                    <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24"
                        style="fill: rgba(0, 0, 0, 1);">
                        <path
                            d="M5.282 12.064c-.428.328-.72.609-.875.851-.155.24-.249.498-.279.768h2.679v-.748H5.413c.081-.081.152-.151.212-.201.062-.05.182-.142.361-.27.303-.218.511-.42.626-.604.116-.186.173-.375.173-.578a.898.898 0 0 0-.151-.512.892.892 0 0 0-.412-.341c-.174-.076-.419-.111-.733-.111-.3 0-.537.038-.706.114a.889.889 0 0 0-.396.338c-.094.143-.159.346-.194.604l.894.076c.025-.188.074-.317.147-.394a.375.375 0 0 1 .279-.108c.11 0 .2.035.272.108a.344.344 0 0 1 .108.258.55.55 0 0 1-.108.297c-.074.102-.241.254-.503.453zm.055 6.386a.398.398 0 0 1-.282-.105c-.074-.07-.128-.195-.162-.378L4 18.085c.059.204.142.372.251.506.109.133.248.235.417.306.168.069.399.103.692.103.3 0 .541-.047.725-.14a1 1 0 0 0 .424-.403c.098-.175.146-.354.146-.544a.823.823 0 0 0-.088-.393.708.708 0 0 0-.249-.261 1.015 1.015 0 0 0-.286-.11.943.943 0 0 0 .345-.299.673.673 0 0 0 .113-.383.747.747 0 0 0-.281-.596c-.187-.159-.49-.238-.909-.238-.365 0-.648.072-.847.219-.2.143-.334.353-.404.626l.844.151c.023-.162.067-.274.133-.338s.151-.098.257-.098a.33.33 0 0 1 .241.089c.059.06.087.139.087.238 0 .104-.038.193-.117.27s-.177.112-.293.112a.907.907 0 0 1-.116-.011l-.045.649a1.13 1.13 0 0 1 .289-.056c.132 0 .237.041.313.126.077.082.115.199.115.352 0 .146-.04.266-.119.354a.394.394 0 0 1-.301.134zm.948-10.083V5h-.739a1.47 1.47 0 0 1-.394.523c-.168.142-.404.262-.708.365v.754a2.595 2.595 0 0 0 .937-.48v2.206h.904zM9 6h11v2H9zm0 5h11v2H9zm0 5h11v2H9z">
                        </path>
                    </svg>
                </button>
            </div>



            <!-- <div class="flex border-l border-gray-300 h-[3vmin] pl-2 justify-center items-center">
                <div class="flex hover:bg-gray-300 cursor-pointer w-full" draggable="true" v-if="selectedQuestion"
                    @dragstart="handleDragStart" @dragend="handleDragEnd">{{ selectedQuestion[0] }}</div>

            </div> -->

            <div v-if="props.editorType == 'question'"
                class="border-l group relative border-gray-300 h-[3vmin] flex items-center">
                <button class="flex" @click="handlePipe()"
                    title="Uses a respondent's previous answer into upcoming questions to personalise the survey.">
                    <svg fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="100%"
                        height="100%">
                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                        <g id="SVGRepo_iconCarrier">
                            <path
                                d="M16,9V11H8V9H10V8H4V10H2V2H4V4H12A2,2 0 0,1 14,6V9H16M10,15V18A2,2 0 0,0 12,20H20V22H22V14H20V16H14V15H16V13H8V15H10Z">
                            </path>
                        </g>
                    </svg>
                </button>
                <!-- <span
                    class="sticky left-1/2 -translate-x-1/2 bottom-full mb-2 w-max max-w-xs px-2 py-1 text-xs text-white bg-black rounded shadow-lg opacity-0 transition-opacity duration-300 group-hover:opacity-100 mt-[8vmin]">
                    Uses a respondent's previous answer into upcoming questions to personalise the survey.
                </span> -->
                <!-- <Tooltip class="z-[9999]"
                    label="Uses a respondent's previous answer into upcoming questions to personalise the survey.">
                </Tooltip> -->
            </div>

        </div>

        <!-- Text Editor -->
        <div class="text-editor !bg-gray-100" contenteditable="true" @mouseup="handleMouseUp" @input="handleInput"
            @paste="handlePaste" ref="editorRef" @dragover="handleDragOver" @drop="handleDrop"
            @keyup="updateCursorPosition" @click="updateCursorPosition" :data-placeholder="props.placeholderText">
        </div>
        <teleport to="body">
            <div v-if="showPicker">
                <div @click="toggleColorPicker"
                    class="absolute top-[50%] left-[50%] translate-x-[-50%] translate-y-[-50%] z-[9999] bg-slate-500 bg-opacity-25 h-[100vh] w-[100vw]">

                </div>
                <div class="absolute top-[50%] left-[50%] translate-x-[-50%] translate-y-[-50%] z-[9999]">
                    <Vue3ColorPicker v-model:pureColor="color" :isWidget="showPicker" @change="applyFontColor" />
                </div>
            </div>
        </teleport>


    </div>

</template>

<script setup>
import { ref, watch, defineProps, defineEmits, onMounted, nextTick, onUnmounted } from "vue";
import DOMPurify from 'dompurify';
import colorPicker from '@caohenghu/vue-colorpicker'
import Tooltip from "@/S4/Tooltip.vue";


const showPicker = ref(false);
const selectedText = ref()
const toggleColorPicker = () => {
    showPicker.value = !showPicker.value;
};


const color = ref('#000000');

watch(() => color.value, (newColor) => {
    console.log("Selected color:", newColor);
    applyFontColor();
});

const applyFontColor = () => {
    if (color.value) {
        document.execCommand("foreColor", false, color.value);
        selectedText.value.style.color = color.value;
        handleInput()
        color.value = null;
        console.log(editorRef.value);

    }
};
const handleMouseUp = () => {
    const selection = window.getSelection();

    if (!selection || selection.rangeCount === 0) return;

    const range = selection.getRangeAt(0);

    // Ignore if nothing is selected
    if (range.collapsed) return;

    // Extract the selected content
    const contents = range.extractContents();

    // Create a span wrapper
    const span = document.createElement('span');
    selectedText.value = span;
    //   span.style.color = 'red';

    // If the selection is already a span (or contains spans), preserve it
    span.appendChild(contents);

    // Insert the new colored span back into the same place
    range.insertNode(span);


    // Optional: clear selection for UX

    //   selection.removeAllRanges();
};



// const suckerCanvas = ref(null)
// const suckerArea = ref([])
// const isSucking = ref(false)

// // function sa color picker
// const showColorPicker = ref(false);

// const toggleColorPicker = () => {
//     if (!color.value) {
//         color.value = '#000000';
//     }
//     showColorPicker.value = !showColorPicker.value;

//     if (showColorPicker.value) {
//         nextTick(() => {
//             const saturation = document.querySelector('.color-picker .saturation');
//             const slide = document.querySelector('.color-picker .slide');

//             console.log("Saturation:", saturation);
//             console.log("Slide:", slide);

//             if (saturation && typeof saturation.renderColor === 'function') {
//                 saturation.renderColor();
//             }
//             if (slide && typeof slide.renderSlide === 'function') {
//                 slide.renderSlide();
//             }
//         });
//     }
// };

// const openSucker = (isOpen) => {
//     if (isOpen) {
//         // ... canvas logic here
//         // suckerCanvas.value = canvas
//         // suckerArea.value = [x1, y1, x2, y2]
//     } else {
//         // if (suckerCanvas.value) suckerCanvas.value.remove()
//     }
// }

// const colorPickerRef = ref(null);
// let isDragging = false;
// let startX = 0, startY = 0;
// let offsetX = 0, offsetY = 0;

// Pang Drag sa color picker
const startDrag = (event) => {
    if (!colorPickerRef.value || !colorPickerRef.value.$el) return;

    const el = colorPickerRef.value.$el;

    isDragging = true;
    startX = event.clientX - offsetX;
    startY = event.clientY - offsetY;

    document.addEventListener("mousemove", (e) => onDrag(e, el));
    document.addEventListener("mouseup", stopDrag);
};

// Function to drag the element
const onDrag = (event, el) => {
    if (!isDragging) return;

    offsetX = event.clientX - startX;
    offsetY = event.clientY - startY;

    el.style.transform = `translate(${offsetX}px, ${offsetY}px)`;
};

// Function to stop dragging
const stopDrag = () => {
    isDragging = false;
    document.removeEventListener("mousemove", onDrag);
    document.removeEventListener("mouseup", stopDrag);
};

// Cleanup event listeners when unmounting
onUnmounted(() => {
    document.removeEventListener("mousemove", onDrag);
    document.removeEventListener("mouseup", stopDrag);
});
// Pang Drag sa color picker taman dri

const _fn = {
    // changeTextColor: (color) => {
    //     console.log("mao ni loy",color.hex)
    //     const colorHex = color.hex;
    //     document.execCommand("foreColor", false, colorHex);
    //     showColorPicker.value = true;
    // },

    format: (command, value = null) => {
        if (["justifyLeft", "justifyCenter", "justifyRight", "justifyFull"].includes(command)) {
            // Handle alignment commands
            document.execCommand(command, false, value);
        }
        let elem = window.getSelection();
        switch (command) {
            case 'fontFamily': // Change font family
                document.execCommand('fontName', false, value);
                break;
            default: // Handle bold, italic, underline, etc.
                document.execCommand(command, true, elem);
        }
    },
    increaseFontSize: () => {
        let elem = window.getSelection();
        let size = elem.baseNode.parentNode.getAttribute("size")
        let nSize = parseInt(size) + 1
        if (isNaN(nSize)) {
            nSize = 4
        }
        if (nSize > 7) {
            nSize = 7
        }
        // console.log(nSize)
        document.execCommand("fontSize", false, nSize);

        // let highlight = document.querySelector('.ssr-text-editor')
        // console.log(highlight.innerHTML)
    },
    decreaseFontSize: () => {
        let elem = window.getSelection();
        let size = elem.baseNode.parentNode.getAttribute("size")
        let nSize = parseInt(size) - 1
        if (isNaN(nSize)) {
            nSize = 2
        }
        if (nSize > 7) {
            nSize = 7
        }
        // console.log(nSize)
        document.execCommand("fontSize", false, nSize);
    },
    undo: () => {
        document.execCommand("undo");
    },
    redo: () => {
        document.execCommand("redo");
    },
};

const props = defineProps({
    modelValue: String,
    selectedQuestion: Array,
    selectedOrder: Array,
    addedTemplate: Number,
    editorType: String,
    placeholderText: { type: String, default: "Add your question here..." }
    // pairWiseText: { type: Boolean, default: false }
});

const emit = defineEmits(["update:modelValue", "showPipe", "validationError"]); // Emits changes back to parent

const editorRef = ref(null);
const canUndo = ref(false);
const canRedo = ref(false);
const isDragged = ref(false)
const cursorPosition = ref(null);
const hasBracket = ref([])
const pipeText = ref(null)
const placeholderText = "Add your question here...";

const handleInput = () => {
    if (editorRef.value) {
        // Get the editor content
        let content = editorRef.value.innerText.trim();

        // If empty, clear the editor and update modelValue as empty
        if (content === "") {
            editorRef.value.innerHTML = ""; // Clears any extra HTML tags
            emit("update:modelValue", ""); // Ensure the parent receives an empty string
        }
        // if (props.pairWiseText) {
        //    emit("update:modelValue", content);
        // }
        else {
            emit("update:modelValue", editorRef.value.innerHTML); // Keep formatted content
        }

        // Check undo/redo state
        checkUndoRedoState();
    }



};

const handlePipe = () => {
    emit('showPipe')
}

// Handle the paste event
const handlePaste = (event) => {
    event.preventDefault(); // Prevent the default paste behavior

    // Get the clipboard data
    const clipboardData = event.clipboardData || window.clipboardData;
    const htmlData = clipboardData.getData('text/html'); // Get HTML content
    const plainText = clipboardData.getData('text/plain'); // Get plain text if no HTML

    // Detect if the content is from Google Docs
    const isGoogleDocsContent = htmlData && htmlData.includes('docs-internal-guid');

    if (isGoogleDocsContent) {
        console.log("Detected Google Docs content (raw):", htmlData);

        const cleanedContent = sanitizeGdocsHTML(htmlData || plainText);
        insertContent(cleanedContent);
    } else {
        console.log("Non-Google Docs content detected:", htmlData || plainText);

        // Insert sanitized content for non-Google Docs input
        const sanitizedContent = sanitizeHTML(htmlData || plainText);
        insertContent(sanitizedContent);
    }
};



// Function to insert content at the cursor position
const insertContent = (content) => {
    const selection = window.getSelection();
    if (!selection.rangeCount) return;

    const range = selection.getRangeAt(0);
    range.deleteContents(); // Remove the current selection
    const fragment = range.createContextualFragment(content);
    range.insertNode(fragment); // Insert cleaned HTML
    selection.collapseToEnd(); // Move the cursor to the end

    // Trigger an input event to update the content
    handleInput();
};

// General sanitization function for non-Google Docs content
const sanitizeHTML = (html) => {
    // Use DOMPurify for sanitization
    const allowedTags = ['b', 'strong', 'br', 'p', 'ul', 'ol', 'li', 'u', 'i'];

    // Sanitize the content using DOMPurify, allowing only specific tags
    const sanitizedHTML = DOMPurify.sanitize(html, {
        ALLOWED_TAGS: allowedTags, // Allow only these tags
        ALLOWED_ATTR: [] // Disallow all attributes
    });

    return sanitizedHTML;
};

const sanitizeGdocsHTML = (html) => {
    let pasteContent = html;
    if (pasteContent == "") {
        pasteContent = html;
    }

    let tempDiv = document.createElement('div');
    tempDiv.innerHTML = pasteContent;

    tempDiv.querySelectorAll('table, tr, td, th').forEach((element) => {
        while (element.firstChild) {
            element.parentNode.insertBefore(element.firstChild, element);
        }
        element.remove();
    });
    tempDiv.querySelectorAll('div').forEach((element) => {
        while (element.firstChild) {
            element.parentNode.insertBefore(element.firstChild, element);
        }
        element.remove();
    });

    tempDiv.querySelectorAll('*').forEach(element => {
        console.log(element.nodeName);
        element.style.fontSize = null;
        element.style.fontFamily = null;
        element.style.lineHeight = null;
        element.style.margin = null;
        element.style.padding = null;
        element.style.color = null;
        element.style.background = null;
        element.style.backgroundColor = null;

        if (element.getAttribute('style')) {
            let style = element.getAttribute('style');
            style = style.replace(/font-variant: normal;/g, '');
            style = style.replace(/text-decoration: none;/g, '');
            style = style.replace(/vertical-align: baseline;/g, '');
            style = style.replace(/white-space: pre-wrap;/g, '');
            style = style.replace(/font-style: normal;/g, '');
            style = style.replace(/font-weight: 400;/g, '');
            style = style.replace(/&nbsp;/g, '');
            style = style.replace("  ", '');
            element.setAttribute('style', style.trim());

            if (element.nodeName === 'SPAN' && element.getAttribute('style') === '') {
                element.removeAttribute('style');
            }

        }
    });


    const excludeTags = ['<!--StartFragment-->', '<meta charset="utf-8">', '<!--EndFragment-->'];
    let newDocument = tempDiv.innerHTML;

    excludeTags.forEach((word) => {
        newDocument = newDocument.replace(word, '');
    });

    var elements = splitHTMLString(newDocument);

    elements.forEach((element, index) => {
        if (element.includes("id=\"docs-internal-guid-") || element.includes("</b>")) {
            elements.splice(index, 1);
        }

    });

    elements = elements.join('');

    let formattedText = elements.replace(/<\/p><p/g, '</p>\n<p');
    formattedText = formattedText.replace(/<p[^>]*>/g, '\n<p>');
    formattedText = formattedText.replace(/<\/p>/g, '</p>\n');

    return formattedText.trim();
};


const splitHTMLString = (html) => {
    // Split the HTML string into an array of tags and text
    const regex = /(<[^>]+>|[^<]+)/g;
    const matches = html.match(regex);

    // Filter out empty strings and return the array
    return matches.filter((match) => match.trim() !== "");
};

const checkUndoRedoState = () => {
    canUndo.value = document.queryCommandEnabled("undo");
    canRedo.value = document.queryCommandEnabled("redo");
};

onMounted(() => {
    if (editorRef.value.innerText.trim() === "") {
        editorRef.value.innerHTML = "";
    }

    if (props.modelValue?.trim()) {
        editorRef.value.innerText = props.modelValue;
    }
    editorRef.value.innerHTML = props.modelValue
    checkUndoRedoState();
    isDragged.value = false;

})

const handleDragOver = (event) => {
    event.preventDefault();
};

const handleDrop = (event) => {
    event.preventDefault();
    isDragged.value = true;

    const data = event.dataTransfer;

    // Save the current cursor position
    const selection = window.getSelection();
    let range = null;
    if (selection.rangeCount > 0) {
        range = selection.getRangeAt(0).cloneRange();
    }

    // Check if the dropped content is an image
    if (data.items) {
        for (let i = 0; i < data.items.length; i++) {
            if (data.items[i].kind === "file" && data.items[i].type.startsWith("image/")) {
                const file = data.items[i].getAsFile();
                const reader = new FileReader();

                reader.onload = (e) => {
                    const img = document.createElement("img");
                    img.src = e.target.result; // Set the image source to the file's data URL
                    img.style.maxWidth = "20vmin"; // Ensure the image uses the full width of the editor
                    img.style.height = "auto"; // Maintain aspect ratio
                    img.style.display = "inline"; // Prevent the image from breaking into a new line
                    img.draggable = true; // Make the image draggable

                    // Add dragstart event to allow repositioning
                    img.addEventListener("dragstart", (dragEvent) => {
                        dragEvent.dataTransfer.setData("text/html", img.outerHTML);
                    });

                    if (range) {
                        range.deleteContents(); // Remove any selected content before dropping
                        range.insertNode(img); // Insert the image into the editor

                        // Move the cursor after the inserted image
                        const space = document.createTextNode("\u00A0"); // Add a space after the image
                        range.insertNode(space); // Insert the space
                        range.setStartAfter(space); // Set the cursor after the space
                        range.setEndAfter(space);

                        // Restore the updated range to the selection
                        selection.removeAllRanges();
                        selection.addRange(range);
                    }

                    // Trigger an input event to update the content
                    handleInput();
                };

                reader.readAsDataURL(file); // Read the file as a data URL
                return;
            }
        }
    }

    // Handle repositioning of dragged images
    const htmlData = data.getData("text/html");
    if (htmlData && range) {
        range.deleteContents(); // Remove any selected content before dropping
        const div = document.createElement("div");
        div.innerHTML = htmlData;
        const fragment = document.createDocumentFragment();
        let node;
        while ((node = div.firstChild)) {
            fragment.appendChild(node);
        }
        range.insertNode(fragment); // Insert the dropped HTML as a "paste"
        selection.removeAllRanges();
        selection.addRange(range); // Restore the cursor position
    }
};

// Track cursor position
const updateCursorPosition = () => {
    const selection = window.getSelection();
    if (selection.rangeCount > 0) {
        cursorPosition.value = selection.getRangeAt(0).cloneRange();
    }
};


// Insert non-movable text at the cursor position
const insertNonMovableText = (text) => {
    if (!editorRef.value) return;

    // Check if text is in HTML format (i.e., selectedQuestion[0] is a span element)
    if (text && text.includes('<span contenteditable="false" id="pipe" class="non-movable')) {
        // Check if the non-movable text already exists
        const editorContent = editorRef.value.innerHTML;
        if (editorContent.includes(text)) return;

        // Remove existing non-movable text (if any)
        const existingSpan = editorRef.value.querySelector(".non-movable#pipe");
        if (existingSpan) {
            existingSpan.remove();
        }

        if (cursorPosition.value) {
            const range = cursorPosition.value;
            range.deleteContents(); // Remove any existing selection

            // Create a temporary div to insert the HTML content directly
            const div = document.createElement('div');
            div.innerHTML = text; // Insert the HTML content

            // Insert the div content (the non-movable span) at the cursor position
            const fragment = document.createDocumentFragment();
            while (div.firstChild) {
                fragment.appendChild(div.firstChild);
            }
            range.insertNode(fragment); // Insert the non-movable content into the editor

            // Append a space after the span to allow users to type after it
            const space = document.createTextNode("\u00A0");
            range.insertNode(space); // Insert a space after the inserted content

            // Move the cursor after the inserted space
            const selection = window.getSelection();
            const newRange = document.createRange();
            newRange.setStartAfter(space); // Set the new range after the space
            newRange.setEndAfter(space); // Set the end of the range at the same position
            selection.removeAllRanges(); // Clear any existing selections
            selection.addRange(newRange); // Add the new range with the cursor after the inserted text

            // Emit the updated content
            emit("update:modelValue", editorRef.value.innerHTML);
        }
    }
};

// Watch for changes in selectedQuestion
watch(
    () => props.selectedQuestion,
    (newVal) => {
        if (newVal && newVal.length > 0) {
            insertNonMovableText(newVal[0]); // Insert selectedQuestion[0] as non-movable text
            //   console.log('nonnn>>', newVal[0])
        }
    },
    { immediate: false, deep: true }
);

watch(
    () => props.addedTemplate,
    (newVal) => {
        if (newVal) {
            editorRef.value.innerHTML = props.modelValue;
            //   console.log('nonnn>>', newVal)
        }
    },
    { immediate: false, deep: true }
);

watch(
    () => props.modelValue,
    (newVal) => {
        // Ensure the content includes balanced brackets and contains PIPE or QUERY
        const content = editorRef.value.innerHTML;

        // Regular expression to match text between brackets containing either PIPE or QUERY
        const bracketRegex = /\[([^\]]*(PIPE|QUERY)[^\]]*)\]/g;
        const matches = [...content.matchAll(bracketRegex)];

        // Check if the number of opening and closing brackets is the same
        const openBracketsCount = (content.match(/\[/g) || []).length;
        const closeBracketsCount = (content.match(/\]/g) || []).length;

        if (openBracketsCount === closeBracketsCount && matches.length > 0) {
            // Extract the text between brackets
            matches.forEach(match => {
                const textInsideBrackets = match[1];

                // Only add text if it doesn't already exist in hasBracket.value
                if (!hasBracket.value.includes(textInsideBrackets)) {
                    hasBracket.value.push(textInsideBrackets);
                }
            });
            console.log(hasBracket.value);
        }
    },
    { immediate: false, deep: true }
);


watch(
    () => hasBracket.value,
    (newVal) => {
        // Check if the content includes text inside brackets []
        if (newVal) {
            let content = editorRef.value.innerHTML;
            let editedText = [];  // Store the edited text spans
            const regex = /\[([^\]]+)\]/g;  // Match content inside brackets []

            let match;
            // Find all text inside brackets and replace it
            while ((match = regex.exec(content)) !== null) {
                const textInsideBracket = match[1];

                // Create the span for each match
                const spanText = `<span contenteditable="false" id="custom" class="non-movable bg-gray-100 border border-gray-300 border-l-8 border-r-8 px-2 py-1 font-semibold inline-block whitespace-nowrap cursor-default select-none text-gray-800 relative rounded">
                            <span class="absolute inset-0 border-1 border-gray-500 rounded-none pointer-events-none"></span>
                            [${textInsideBracket}]
                          </span>`;

                editedText.push(spanText);  // Store the span in the array

            }

            // Replace the bracketed content with the spans in the content
            content = content.replace(regex, (match, p1) => {
                const span = editedText.shift();  // Get the first edited text span
                return span || p1;  // Replace bracketed text with span or keep original if no span found
            });
            //   console.log("why ", content)

            // Update the editor content
            editorRef.value.innerHTML = content;
            emit("update:modelValue", editorRef.value.innerHTML);
        } else {
            console.log("No bracket content found.");
        }
    },
    { immediate: false, deep: true }
);


</script>

<style scoped>
.non-movable {
    background-color: #474747;
    border: 1px solid #ccc;
    padding: 2px 4px;
    border-radius: 4px;
    display: inline-block;
    white-space: nowrap;
    user-select: none;
    /* Prevent text selection */
    cursor: default;
    /* Default cursor */
}

.editor-container {
    width: 100%;
    background: #fff;
    /* box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1); */
    overflow: hidden;
    display: flex;
    flex-direction: column;


}

.text-editor {
    padding: 10px;
    border-left: 1px solid #ddd;
    border-right: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
    outline: none;
    border-radius: 0px 0px 5px 5px;
    min-height: 120px;
}

.toolbar {
    height: auto;
    width: 100%;
    position: relative;
    display: flex;
    justify-content: left;
    padding: 0.3vmin;
    border: 1px solid #ddd;
    align-items: center;
    border-radius: 5px 5px 0px 0px;
}

button {
    padding: 0.5%;
    height: 100%;
    width: 4vmin;
    border: 1px solid transparent;
    cursor: pointer;
    background-color: transparent;
    transition: transform 0.3s;
}


button:hover:not(.disabled-btn) {
    background-color: #e0e0e0;
}

button:hover:not(:disabled) svg {
    transform: scale(1.1);
}



select {
    height: auto;
    width: 110px;
    border: none;
    background: transparent;
    cursor: pointer;
    appearance: auto;
    padding: 0.5vmin;
}

select:hover {
    background-color: #e0e0e0;
}

/* Tooltip container */
.tooltip-container {
    position: relative;
    display: inline-block;
}

/* Tooltip text */
.tooltip-container .tooltip-text {
    visibility: hidden;
    width: 120px;
    background-color: #333;
    color: #fff;
    text-align: center;
    border-radius: 4px;
    padding: 5px;
    position: absolute;
    z-index: 1;
    bottom: 125%;
    /* Position above the element */
    left: 50%;
    transform: translateX(-50%);
    opacity: 0;
    transition: opacity 0.3s;
}

/* Tooltip arrow */
.tooltip-container .tooltip-text::after {
    content: "";
    position: absolute;
    top: 100%;
    /* Arrow points to the element */
    left: 50%;
    margin-left: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: #333 transparent transparent transparent;
}

/* Show tooltip on hover */
.tooltip-container:hover .tooltip-text {
    visibility: visible;
    opacity: 1;
}
</style>

<style>
.hu-color-picker {
    z-index: 200 !important;
}

.text-editor:empty:before {
    content: attr(data-placeholder);
    color: gray;
    font-style: italic;
    pointer-events: none;
}

.text-editor img {
    max-width: 100%;
    /* Ensure the image uses the full width of the editor */
    height: auto;
    /* Maintain aspect ratio */
    display: inline;
    /* Prevent the image from breaking into a new line */
    vertical-align: middle;
    /* Align the image with the text */
}

.text-editor ul {
    list-style-type: disc;
    /* Use bullets for unordered lists */
    margin-left: 20px;
    /* Add some indentation */
    padding-left: 20px;
    /* Add padding for better alignment */
}

.text-editor ol {
    list-style-type: decimal; /* Use numbers for ordered lists */
    margin-left: 20px; /* Add some indentation */
    padding-left: 20px; /* Add padding for better alignment */
}

.text-editor li {
    margin-bottom: 5px;
    /* Add spacing between list items */
}
</style>