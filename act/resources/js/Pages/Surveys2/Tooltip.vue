<script setup>
const props = defineProps({
  text: {
    type: String,
    required: false,
    default: ""
  },
  width: {
    type: [Number, String],
    required: false,
    default: 120
  }
});
</script>


<template>
<buttons class="faq-button">
  <svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 320 512">
    <path
      d="M80 160c0-35.3 28.7-64 64-64h32c35.3 0 64 28.7 64 64v3.6c0 21.8-11.1 42.1-29.4 53.8l-42.2 27.1c-25.2 16.2-40.4 44.1-40.4 74V320c0 17.7 14.3 32 32 32s32-14.3 32-32v-1.4c0-8.2 4.2-15.8 11-20.2l42.2-27.1c36.6-23.6 58.8-64.1 58.8-107.7V160c0-70.7-57.3-128-128-128H144C73.3 32 16 89.3 16 160c0 17.7 14.3 32 32 32s32-14.3 32-32zm80 320a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"
    ></path>
  </svg>
 
</buttons>
</template>


<style scoped>
/* From Uiverse.io by Shell0110 */ 
.faq-button {
  width: 17px;
  height: 17px;
  border-radius: 100%;
  border: none;
  background-color: #ffffff00;
  background-image: linear-gradient(147deg, #ffe53b00 0%, #4d4c4c00 74%);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  position: relative;
  border: 2px solid #686868;
  z-index:  10;
}

.faq-button:hover {
  animation: jello-vertical 1s both;
}
.faq-button svg {
  height: 0.7em;
  fill: rgb(46, 46, 46);
}

@keyframes jello-vertical {
  0% {
    transform: scale3d(1, 1, 1);
  }
  30% {
    transform: scale3d(0.75, 1.25, 1);
  }
  40% {
    transform: scale3d(1.25, 0.75, 1);
  }
  50% {
    transform: scale3d(0.85, 1.15, 1);
  }
  65% {
    transform: scale3d(1.05, 0.95, 1);
  }
  75% {
    transform: scale3d(0.95, 1.05, 1);
  }
  100% {
    transform: scale3d(1, 1, 1);
  }
}



.tooltip {
  position: absolute;
  top: -20px;
  opacity: 0;
  background-color: #257556;
  color: white;
  border-radius: 5px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition-duration: 0.2s;
  pointer-events: none;
  letter-spacing: 0.5px;
  bottom: -100%;
  left: 250%;
  z-index: 9999999999999999;
  font-size: 8px;
  width: 140px;
  height: 60px;
  text-align: center;
}

.tooltip::before {
  position: absolute;
  content: "";
  width: 10px;
  height: 10px;
  background-color: #257556;
  background-size: 1000%;
  background-position: center;
  transform: rotate(45deg);
  bottom: 40%;
  left: -4%;
  transition-duration: 0.3s;
}

.faq-button:hover .tooltip {
  top: -25px;
  opacity: 1;
  transition-duration: 0.3s;
}

</style>