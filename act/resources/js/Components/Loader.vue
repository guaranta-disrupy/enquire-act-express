<script setup>
import { onUnmounted } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    }
});

onUnmounted(() => {
    document.body.style.overflow = null;
});
</script>
<template>
    <teleport to='body'>
        <div class="" id="loader-wrapper" v-show="show" >
            <div id="loader"> 
            </div>
            <div class="mt-6 text-lg font-semibold">
                <slot></slot>
            </div>
        </div>
    </teleport>
</template>
<style>
:root {
    --color__accent: #f0911f;
    --color__primary:#024b84;
    --field__background: #f5f5f5;
    --field__background--hover: #e9e9e9;
    --input__border: rgba(0, 0, 0, 0.42);
    --input__border--hover: rgba(0, 0, 0, 0.62);
    --label__color: #36395A;
  
    --font__size: 16px; 
    --font__family: 'Nunito', sans-serif;

  } 


#loader-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1000;
    display: flex;
    align-items: center;
    flex-direction: column;
    justify-content: center;
    transform: translateZ(0px);
    background-color: rgba(255, 255, 255, 0.9);
}

@supports (backdrop-filter: blur(12px)){
    #loader-wrapper {
        backdrop-filter: blur(12px);
        background-color: rgba(255, 255, 255, 0.4);
    }
}

#loader {
    display: block;
    position: relative;
    /* left: 50%;
    top: 50%; */
    width: 150px;
    height: 150px;
    /* margin: -75px 0 0 -75px; */
    border-radius: 50%;
    border: 3px solid transparent;
    border-top-color: var(--color__primary);
    transform: translateZ(0px);
    -webkit-animation: spin 1.5s linear infinite; /* Chrome, Opera 15+, Safari 5+ */
    animation: spin 1.5s linear infinite; /* Chrome, Firefox 16+, IE 10+, Opera */
}

    #loader:before {
        content: "";
        position: absolute;
        top: 5px;
        left: 5px;
        right: 5px;
        bottom: 5px;
        border-radius: 50%;
        border: 3px solid transparent;
        border-top-color: var(--color__accent);

        -webkit-animation: spin 2.5s linear infinite; /* Chrome, Opera 15+, Safari 5+ */
        animation: spin 2.5s linear infinite; /* Chrome, Firefox 16+, IE 10+, Opera */
    }

    #loader:after {
        content: "";
        position: absolute;
        top: 15px;
        left: 15px;
        right: 15px;
        bottom: 15px;
        border-radius: 50%;
        border: 3px solid transparent;
        border-top-color: #f9c922;

        -webkit-animation: spin 1s linear infinite; /* Chrome, Opera 15+, Safari 5+ */
          animation: spin 1s linear infinite; /* Chrome, Firefox 16+, IE 10+, Opera */
    }

    @-webkit-keyframes spin {
        0%   { 
            -webkit-transform: rotateZ(0deg);  /* Chrome, Opera 15+, Safari 3.1+ */
            -ms-transform: rotateZ(0deg);  /* IE 9 */
            transform: rotateZ(0deg);  /* Firefox 16+, IE 10+, Opera */
        }
        100% {
            -webkit-transform: rotateZ(360deg);  /* Chrome, Opera 15+, Safari 3.1+ */
            -ms-transform: rotateZ(360deg);  /* IE 9 */
            transform: rotateZ(360deg);  /* Firefox 16+, IE 10+, Opera */
        }
    }
    @keyframes spin {
        0%   { 
            -webkit-transform: rotateZ(0deg);  /* Chrome, Opera 15+, Safari 3.1+ */
            -ms-transform: rotateZ(0deg);  /* IE 9 */
            transform: rotateZ(0deg);  /* Firefox 16+, IE 10+, Opera */
        }
        100% {
            -webkit-transform: rotateZ(360deg);  /* Chrome, Opera 15+, Safari 3.1+ */
            -ms-transform: rotateZ(360deg);  /* IE 9 */
            transform: rotateZ(360deg);  /* Firefox 16+, IE 10+, Opera */
        }
    }
</style>