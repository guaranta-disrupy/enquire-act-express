<script setup>
import { ref } from "vue";
import { useDataStore } from "@/stores/data";
import { storeToRefs } from "pinia";

const dataStore = useDataStore();
import DesignDisplayLayout from "@/Components/DesignDisplayLayout.vue";
import DesignPrimes from "@/Components//DesignPrimes.vue";
import DesignTargets from "@/Components/DesignTargets.vue";
import DesignHeader from "@/Components/DesignHeader.vue";
import DesignFooter from "@/Components/DesignFooter.vue";
import DesignWarning from "@/Components/DesignWarning.vue";

const dropdowns = ref({
  layout: false,
  primes: false,
  targets: false,
  header: false,
  footer: false,
  warning: false,
});

const toggleDropdown = (key) => {
  // Close all others
  for (const k in dropdowns.value) {
    if (k !== key) dropdowns.value[k] = false;
  }
  // Toggle selected
  dropdowns.value[key] = !dropdowns.value[key];
};

const selectLayout = (layout, key) => {
  console.log(`Selected ${key}:`, layout);
  dropdowns.value[key] = false;
};

const isHoveringLayout = ref(false);
const isHoveringPrimes = ref(false);
const isHoveringTargets = ref(false);
const isHoveringHeader = ref(false);
const isHoveringFooter = ref(false);
const isHoveringWarning = ref(false);
</script>


<template>
  <div class="flex flex-col w-full">
    <div
      class="bg-[#257556] text-white shadow p-4 w-full text-center uppercase"
    >
      Design
    </div>

    <div class="flex flex-col p-2 overflow-y-auto custom-scrollbar c">
      <!-- Dropdown Display Layout -->
      <div class="flex flex-col justify-center w-full">
        <button
          @click="toggleDropdown('layout')"
          @mouseover="isHoveringLayout = true"
          @mouseleave="isHoveringLayout = false"
          :class="[
            'group text-center items-center px-4 py-2 rounded-md ml-2 mr-2 mt-2 hover:bg-[#1d5e45] focus:outline-none flex flex-row justify-between',
            dropdowns.layout
              ? 'bg-[#257556] text-white'
              : 'bg-[#E7E7E7] text-[#454545]',
          ]"
        >
          <span
            :class="[
              dropdowns.layout
                ? 'text-white'
                : 'group-hover:text-white text-[#454545] font-bold',
            ]"
          >
            Display Layout
          </span>

          <svg
            :class="[
              'ml-2 transition-transform duration-300 ease-in-out',
              dropdowns.layout ? 'rotate-180' : 'rotate-0',
            ]"
            width="30"
            height="30"
            viewBox="0 0 31 30"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M7.12695 10.7812L15.5645 19.2188L24.002 10.7812"
              :stroke="
                dropdowns.layout || isHoveringLayout ? '#FFFFFF' : '#323232'
              "
              stroke-width="3"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </button>

        <div
          v-if="dropdowns.layout"
          class="z-10 mt-2 ml-2 mr-2 bg-white border rounded shadow-lg"
        >
          <ul class="text-left">
            <DesignDisplayLayout></DesignDisplayLayout>
          </ul>
        </div>
      </div>

      <!-- Dropdown Primes -->
      <div class="flex flex-col justify-center w-full">
        <button
          @click="toggleDropdown('primes')"
          @mouseover="isHoveringPrimes = true"
          @mouseleave="isHoveringPrimes = false"
          :class="[
            'group text-center items-center px-4 py-2 rounded-md ml-2 mr-2 mt-2 hover:bg-[#1d5e45] focus:outline-none flex flex-row justify-between',
            dropdowns.primes
              ? 'bg-[#257556] text-white'
              : 'bg-[#E7E7E7] text-[#454545]',
          ]"
        >
          <span
            :class="[
              dropdowns.primes
                ? 'text-white'
                : 'group-hover:text-white text-[#454545] font-bold',
            ]"
          >
            Primes
          </span>

          <!-- Icon -->
          <svg
            :class="[
              'ml-2 transition-transform duration-300 ease-in-out',
              dropdowns.primes ? 'rotate-180' : 'rotate-0',
            ]"
            width="30"
            height="30"
            viewBox="0 0 31 30"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M7.12695 10.7812L15.5645 19.2188L24.002 10.7812"
              :stroke="
                dropdowns.primes || isHoveringPrimes ? '#FFFFFF' : '#323232'
              "
              stroke-width="3"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </button>

        <div
          v-if="dropdowns.primes"
          class="z-10 mt-2 ml-2 mr-2 bg-white border rounded shadow-lg"
        >
          <ul class="text-left">
            <div>
              <DesignPrimes></DesignPrimes>
            </div>
          </ul>
        </div>
      </div>

      <!-- Dropdown Targets -->
      <div class="flex flex-col justify-center w-full">
        <div class="flex flex-col justify-center w-full">
          <button
            @click="toggleDropdown('targets')"
            @mouseover="isHoveringTargets = true"
            @mouseleave="isHoveringTargets = false"
            :class="[
              'group text-center items-center px-4 py-2 rounded-md ml-2 mr-2 mt-2 hover:bg-[#1d5e45] focus:outline-none flex flex-row justify-between',
              dropdowns.targets
                ? 'bg-[#257556] text-white'
                : 'bg-[#E7E7E7] text-[#454545]',
            ]"
          >
            <span
              :class="[
                dropdowns.targets
                  ? 'text-white'
                  : 'group-hover:text-white text-[#454545] font-bold',
              ]"
            >
              Targets
            </span>

            <!-- Icon -->
            <svg
              :class="[
                'ml-2 transition-transform duration-300 ease-in-out',
                dropdowns.targets ? 'rotate-180' : 'rotate-0',
              ]"
              width="30"
              height="30"
              viewBox="0 0 31 30"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M7.12695 10.7812L15.5645 19.2188L24.002 10.7812"
                :stroke="
                  dropdowns.targets || isHoveringTargets ? '#FFFFFF' : '#323232'
                "
                stroke-width="3"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </button>
        </div>

        <div
          v-if="dropdowns.targets"
          class="z-10 mt-2 ml-2 mr-2 bg-white border rounded shadow-lg"
        >
          <ul class="text-left">
            <DesignTargets></DesignTargets>
          </ul>
        </div>
      </div>

      <!-- Dropdown Header -->
      <div class="flex flex-col justify-center w-full">
        <button
          @click="toggleDropdown('header')"
          @mouseover="isHoveringHeader = true"
          @mouseleave="isHoveringHeader = false"
          :class="[
            'group text-center items-center px-4 py-2 rounded-md ml-2 mr-2 mt-2 hover:bg-[#1d5e45] focus:outline-none flex flex-row justify-between',
            dropdowns.header
              ? 'bg-[#257556] text-white'
              : 'bg-[#E7E7E7] text-[#454545]',
          ]"
        >
          <span
            :class="[
              dropdowns.header
                ? 'text-white'
                : 'group-hover:text-white text-[#454545] font-bold',
            ]"
          >
            Header
          </span>

          <!-- Icon -->
          <svg
            :class="[
              'ml-2 transition-transform duration-300 ease-in-out',
              dropdowns.header ? 'rotate-180' : 'rotate-0',
            ]"
            width="30"
            height="30"
            viewBox="0 0 31 30"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M7.12695 10.7812L15.5645 19.2188L24.002 10.7812"
              :stroke="
                dropdowns.header || isHoveringHeader ? '#FFFFFF' : '#323232'
              "
              stroke-width="3"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </button>

        <div
          v-if="dropdowns.header"
          class="z-10 mt-2 ml-2 mr-2 bg-white border rounded shadow-lg"
        >
          <ul class="text-left">
            <DesignHeader></DesignHeader>
          </ul>
        </div>
      </div>

      <!-- Dropdown Footer -->
      <div class="flex flex-col justify-center w-full">
        <button
          @click="toggleDropdown('footer')"
          @mouseover="isHoveringFooter = true"
          @mouseleave="isHoveringFooter = false"
          :class="[
            'group text-center items-center px-4 py-2 rounded-md ml-2 mr-2 mt-2 hover:bg-[#1d5e45] focus:outline-none flex flex-row justify-between',
            dropdowns.footer
              ? 'bg-[#257556] text-white'
              : 'bg-[#E7E7E7] text-[#454545]',
          ]"
        >
          <span
            :class="[
              dropdowns.footer
                ? 'text-white'
                : 'group-hover:text-white text-[#454545] font-bold',
            ]"
          >
            Footer
          </span>

          <!-- Icon -->
          <svg
            :class="[
              'ml-2 transition-transform duration-300 ease-in-out',
              dropdowns.footer ? 'rotate-180' : 'rotate-0',
            ]"
            width="30"
            height="30"
            viewBox="0 0 31 30"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M7.12695 10.7812L15.5645 19.2188L24.002 10.7812"
              :stroke="
                dropdowns.footer || isHoveringFooter ? '#FFFFFF' : '#323232'
              "
              stroke-width="3"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </button>

        <div
          v-if="dropdowns.footer"
          class="z-10 mt-2 ml-2 mr-2 bg-white border rounded shadow-lg"
        >
          <ul class="text-left">
            <DesignFooter></DesignFooter>
          </ul>
        </div>
      </div>

      <!-- Dropdown Warning -->
      <div class="flex flex-col justify-center w-full">
        <button
          @click="toggleDropdown('warning')"
          @mouseover="isHoveringWarning = true"
          @mouseleave="isHoveringWarning = false"
          :class="[
            'group text-center items-center px-4 py-2 rounded-md ml-2 mr-2 mt-2 hover:bg-[#1d5e45] focus:outline-none flex flex-row justify-between',
            dropdowns.warning
              ? 'bg-[#257556] text-white'
              : 'bg-[#E7E7E7] text-[#454545]',
          ]"
        >
          <span
            :class="[
              dropdowns.warning
                ? 'text-white'
                : 'group-hover:text-white text-[#454545] font-bold',
            ]"
          >
            Warning
          </span>

          <!-- Icon -->
          <svg
            :class="[
              'ml-2 transition-transform duration-300 ease-in-out',
              dropdowns.warning ? 'rotate-180' : 'rotate-0',
            ]"
            width="30"
            height="30"
            viewBox="0 0 31 30"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M7.12695 10.7812L15.5645 19.2188L24.002 10.7812"
              :stroke="
                dropdowns.warning || isHoveringWarning ? '#FFFFFF' : '#323232'
              "
              stroke-width="3"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </button>

        <div
          v-if="dropdowns.warning"
          class="z-10 mt-2 ml-2 mr-2 bg-white border rounded shadow-lg"
        >
          <ul class="text-left">
            <DesignWarning></DesignWarning>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.c {
  max-height: calc(100vh - 230px);
}

svg {
  transform-origin: center;
}

body {
  overflow: visible !important;
}

.c {
  max-height: calc(100vh - 120px);
}

.main-color {
  background-color: #018880;
}

.custom-scrollbar {
  scrollbar-width: thin; /* For Firefox */
  scrollbar-color: #257556 transparent; /* thumb and track color */
}

/* For WebKit (Chrome, Safari, Edge) */
.custom-scrollbar::-webkit-scrollbar {
  width: 1px; /* Make scrollbar thin */
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #257556; /* light gray thumb */
  border-radius: 1px;
}
</style>
