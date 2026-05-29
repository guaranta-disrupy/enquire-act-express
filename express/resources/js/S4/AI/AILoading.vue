<template>
    <div class="loader-wrapper" :style="wrapperStyle">
        <span 
            v-for="(letter, index) in letters" 
            :key="index"
            class="loader-letter"
            :style="{
                animationDelay: `${index * 0.1}s`,
                marginRight: letter === ' ' ? '0.35em' : '0'
            }"
        >
            {{ letter === ' ' ? '\u00A0' : letter }}
        </span>
        <div class="loader"></div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    text: {
        type: String,
        default: 'Translating'
    },
    size: {
        type: String,
        default: '180px'
    },
    fontSize: {
        type: String,
        default: '1.2em'
    },
    color: {
        type: String,
        default: 'white'
    }
});

const letters = computed(() => props.text.split(''));

const wrapperStyle = computed(() => ({
    width: props.size,
    height: props.size,
    fontSize: props.fontSize,
    color: props.color
}));
</script>

<style scoped>
.loader-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: "Inter", sans-serif;
    font-weight: 300;
    border-radius: 50%;
    background-color: transparent;
    user-select: none;
}

.loader {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    aspect-ratio: 1 / 1;
    border-radius: 50%;
    background-color: transparent;
    animation: loader-rotate 2s linear infinite;
    z-index: 0;
}

@keyframes loader-rotate {
    0% {
        transform: rotate(90deg);
        box-shadow:
            0 10px 20px 0 #fff inset,
            0 20px 30px 0 #ffa733 inset,
            0 60px 60px 0 #ee920c inset;
    }

    50% {
        transform: rotate(270deg);
        box-shadow:
            0 10px 20px 0 #fff inset,
            0 20px 10px 0 #d1810b inset,
            0 40px 60px 0 #b86d0a inset;
    }

    100% {
        transform: rotate(450deg);
        box-shadow:
            0 10px 20px 0 #fff inset,
            0 20px 30px 0 #ffa733 inset,
            0 60px 60px 0 #ee920c inset;
    }
}

.loader-letter {
    display: inline-block;
    opacity: 0.4;
    transform: translateY(0);
    animation: loader-letter-anim 2s infinite;
    z-index: 1;
    border-radius: 50ch;
    border: none;
}

@keyframes loader-letter-anim {
    0%,
    100% {
        opacity: 0.4;
        transform: translateY(0);
    }

    20% {
        opacity: 1;
        transform: scale(1.15);
    }

    40% {
        opacity: 0.7;
        transform: translateY(0);
    }
}
</style>
