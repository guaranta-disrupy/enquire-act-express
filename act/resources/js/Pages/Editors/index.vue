<template>
  <div class="bg-slate-200 min-h-[100vh] text-[#323232]">
    <!-- Live Editor Preview -->
    <Teleport to="body">
      <div class="absolute -translate-x-1/2 -translate-y-1/2 left-1/2 top-1/2">
        <div
          class="relative bg-gray-100 w-[170vmin] h-[80vmin] text-black z-50 rounded-2xl top-[calc(4.50rem)]"
        >
          <LiveEditorContent />
          <!-- <iframe
            :src="liveUrl"
            class="fixed w-full h-full"
            frameborder="0"
            allowfullscreen
          ></iframe> -->
        </div>
      </div>
    </Teleport>

    <div class="flex flex-col">s
      <!-- Main Header -->
      <header
        class="flex items-center justify-between h-[70px] py-2 bg-white shadow-lg p-4 z-50"
      >
        <button
          class="bg-[#257556] text-white rounded-full text-[16px] hover:bg-[#1d5e45] w-[200px] h-[44px] flex gap-2 items-center justify-center"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="2.5"
            stroke="currentColor"
            class="w-5 h-5"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M19.5 12h-15m0 0l6.75 6.75M4.5 12l6.75-6.75"
            />
          </svg>
          Back to Fluent
        </button>
      </header>

      <!-- Secondary Header -->
      <header
        class="flex items-center justify-end gap-2 w-full top-[4.47rem] h-[50px] bg-white shadow-md p-2 z-[10] absolute"
      >
        <button
          @click="saveTest"
          class="px-4 bg-[#257556] text-white font-bold rounded-full text-[20px] hover:bg-[#1d5e45] w-[110px] h-[44px] flex items-center justify-center"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="currentColor"
            viewBox="0 0 24 24"
            class="w-6 h-6 mr-1"
          >
            <path
              fill-rule="evenodd"
              d="M10.5 3.75a6 6 0 00-5.98 6.496A5.25 5.25 0 006.75 20.25H18a4.5 4.5 0 002.206-8.423 3.75 3.75 0 00-4.133-4.303A6.001 6.001 0 0010.5 3.75zm2.03 5.47a.75.75 0 00-1.06 0l-3 3a.75.75 0 101.06 1.06l1.72-1.72v4.94a.75.75 0 001.5 0v-4.94l1.72 1.72a.75.75 0 101.06-1.06l-3-3z"
              clip-rule="evenodd"
            />
          </svg>
          Save
        </button>
      </header>

      <!-- Main Content -->
      <div class="flex flex-row">
        <!-- Sidebar Navigation -->
        <nav class="flex flex-col z-[30] w-[6rem] h-[500vh] bg-white shadow-lg">
          <button
            v-for="item in sidebarItems"
            :key="item.tab"
            @click="switchTab(item.tab)"
            :class="[
              'w-full py-5 gap-2 flex flex-col items-center text-[14px] font-bold border border-gray-200',
              activeTab === item.tab
                ? 'bg-[#257556] text-white'
                : 'bg-white text-[#323232] hover:bg-[#257556] hover:text-white',
            ]"
          >
            <span v-html="item.icon"></span>
            {{ item.label }}
          </button>
        </nav>

        <!-- Sidebar Content -->
        <aside
          v-show="showSidebarContent"
          class="z-30 flex transition-all duration-300 bg-white border"
          :class="[
            showSidebarContent ? 'translate-x-0' : '-translate-x-full',
            isSidebarExpanded ? 'w-[160vh]' : 'w-[40vh]',
          ]"
        >
          <component
            :is="currentComponent"
            @toggle-expand="toggleSidebarExpansion"
            :is-expanded="isSidebarExpanded"
          />
        </aside>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, provide, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { useDataStore } from "@/stores/data";

// Components
// import DesignComponent from "@/EditorComponents/DesignComponent/DesignEditor.vue";
import PrimesComponent from "@/EditorComponents/PrimesComponent/PrimesEditor.vue";
import TargetsComponent from "@/EditorComponents/TargetsComponent/TargetsEditor.vue";
import InstructionComponent from "@/EditorComponents/InstructionComponent/InstructionEditor.vue";
// import GalleryComponent from "@/EditorComponents/GalleryComponent/GalleryEditor.vue";
import SettingsComponent from "@/EditorComponents/SettingsComponent/SettingsEditor.vue";
// import AdvanceComponent from "@/EditorComponents/AdvanceComponent/AdvanceEditor.vue";
// import LiveEditorContent from "@/Components/LiveEditorComponents/index.vue";

// State
const activeTab = ref("Design");
const showSidebarContent = ref(true);
const isSidebarExpanded = ref(false);
const props = defineProps({ tests: Object });
// console.log(props.tests);
const liveUrl = computed(() => {
  // Assuming your LiveEditorContent is served at '/live/{id}'
  return `/live/${props.tests?.id}`;

  // Or if you need the full URL:
  // return window.location.origin + `/live/${props.tests?.id}`;
});
// Pinia Store
const dataStore = useDataStore();

// Initialize store with props
if (props.tests) {
  dataStore.setTests(props.tests);
}
// console.log(props.tests);
// Watch for changes in tests
watch(
  () => dataStore.tests,
  (newTargets) => {
    // console.log("Targets changed:", newTargets);
  },
  { deep: true }
);

// Form Handling
const form = useForm({
  id: props.tests?.id,
  tests: dataStore.tests,
});

const saveTest = async () => {
  try {
    await dataStore.saveTest();
    // Show success message
  } catch (error) {
    // Handle error
  }
};

// Provide store to child components
provide("dataStore", dataStore);

// Component Mapping
const componentsMap = {
  Design: DesignComponent,
  Primes: PrimesComponent,
  Targets: TargetsComponent,
  Instruction: InstructionComponent,
  Gallery: GalleryComponent,
  Settings: SettingsComponent,
  Advance: AdvanceComponent,
};

const currentComponent = computed(() => componentsMap[activeTab.value]);

// Navigation
function switchTab(tabName) {
  if (activeTab.value === tabName) {
    showSidebarContent.value = !showSidebarContent.value;
  } else {
    showSidebarContent.value = true;
    activeTab.value = tabName;
  }
}

function toggleSidebarExpansion() {
  isSidebarExpanded.value = !isSidebarExpanded.value;
}

// Sidebar Items
const sidebarItems = [
  { label: "Design", tab: "Design", icon: designIcon },
  { label: "Primes", tab: "Primes", icon: primesIcon },
  { label: "Targets", tab: "Targets", icon: targetsIcon },
  { label: "Instruction", tab: "Instruction", icon: instructionIcon },
  { label: "Gallery", tab: "Gallery", icon: galleryIcon },
  { label: "Settings", tab: "Settings", icon: settingsIcon },
  { label: "Advance", tab: "Advance", icon: advanceIcon },
];

// SVG Icons (extracted for better readability)
const designIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>`;
const primesIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9 12l2 2 4-4"/></svg>`;
const targetsIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/></svg>`;
const instructionIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M12 4h9"/><path d="M4 9h16"/><path d="M4 15h16"/></svg>`;
const galleryIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>`;
const settingsIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06A1.65 1.65 0 0 0 15 19.4V21a2 2 0 0 1-4 0v-1.6a1.65 1.65 0 0 0-1.82-.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15H3a2 2 0 0 1 0-4h1.6a1.65 1.65 0 0 0 .33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6V3a2 2 0 1 1 4 0v1.6a1.65 1.65 0 0 0 1.82.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9H21a2 2 0 0 1 0 4h-1.6z"/></svg>`;
const advanceIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6" viewBox="0 0 24 24"><path d="M12 19V6"/><path d="M5 12l7-7 7 7"/></svg>`;
</script>