<template>
  <div class="flex flex-col w-full h-full gap-3 overflow-y-auto bg-gray-50">
    <div
      class="bg-[#15919A] shadow p-4 mb-2 w-[full] justify-between flex flex-row text-white text-[20px] font-bold"
    >
      Targets

      <div class="flex flex-row gap-2">
        <!-- Select Button -->
        <button
          class="px-3 py-1 font-bold text-black text-[16px] bg-white rounded-full"
          @click="toggleSelectAll"
        >
          {{ allSelected ? "Unselect All" : "Select All" }}
        </button>
        <!-- Delete Button -->
        <button
          @click="deleteSelectedTargets"
          class="px-3 py-1 font-bold text-black text-[16px] bg-white rounded-full"
        >
          Delete
        </button>
      </div>
    </div>

    <!-- Target Card container start-->
    <div
      class="flex flex-col w-full gap-3 pl-3 overflow-y-auto h-[calc(100vh-160px)] custom-scrollbar"
    >
      <div class="">
        <button
          @click="showModal = true"
          class="group uppercase shadow flex flex-col border border-gray-300 h-[237px] w-[352px] rounded-lg justify-center items-center text-[#323232] hover:bg-[#15919A] hover:text-white font-bold"
        >
          <svg
            data-v-9889b466=""
            width="80"
            height="80"
            viewBox="0 0 80 80"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              data-v-9889b466=""
              d="M40 17.5V62.5M62.5 40H17.5"
              stroke="#323232"
              stroke-width="8"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="transition-colors duration-200 group-hover:stroke-white"
            ></path>
          </svg>
          Add Target
        </button>
      </div>

      <!-- Target card start-->
      <div
        v-for="(target, index) in dataStore.targets"
        :key="index"
        class="flex h-[237px] w-[352px] flex-col transition-shadow duration-200 bg-white rounded-lg shadow hover:shadow-lg p-2 border border-gray-300"
      >
        <div class="flex flex-row items-center justify-between">
          <div class="checkbox-container">
            <label class="ios-checkbox blue">
              <input 
                type="checkbox" 
                :checked="selectedTargets.includes(index)"
                @change="toggleTargetSelection(index)" 
              />
              <div class="checkbox-wrapper">
                <div class="checkbox-bg"></div>
                <svg fill="none" viewBox="0 0 24 24" class="checkbox-icon">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="3"
                    stroke="currentColor"
                    d="M4 12L10 18L20 6"
                    class="check-path"
                  />
                </svg>
              </div>
            </label>
          </div>

          <!-- Dropdown button -->
          <button
            @click="toggleDropdown(index)"
            class="relative dropdown-container"
          >
            <svg
              data-v-9889b466=""
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
              class="w-6 h-6"
            >
              <path
                data-v-9889b466=""
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
              ></path>
            </svg>

            <!-- Dropdown Menu -->
            <div
              v-if="openDropdown === index"
              class="absolute right-0 z-10 w-32 bg-white border border-gray-200 rounded-lg shadow-lg top-8"
            >
              <div class="py-1">
                <button
                  @click="editTarget(index)"
                  class="flex items-center w-full gap-2 px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100"
                >
                  <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                    ></path>
                  </svg>
                  Edit
                </button>
                <button
                  @click="deleteTarget(index)"
                  class="flex items-center w-full gap-2 px-4 py-2 text-sm text-left text-red-600 hover:bg-red-50"
                >
                  <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    ></path>
                  </svg>
                  Delete
                </button>
              </div>
            </div>
          </button>
        </div>

        <!-- Targets -->
        <div class="px-2 text-[#323232]">
          <div class="text-[3vmin] font-bold py-6 text-center">
            <span v-html="target.target"></span>
          </div>

          <div
            class="text-[1.5vmin] text-gray-500 py-3 border-t border-gray-300"
          >
            <strong>Equivalent:</strong>
            <span v-html="target.equivalent"></span>
          </div>

          <div class="text-[1.5vmin] text-gray-500">
            <strong>Can Unselect:</strong>
            <span>{{ target.can_unselect ? "Yes" : "No" }}</span>
          </div>
        </div>

        <!-- index -->
        <div class="flex justify-end gap-2">
          <div
            class="font-bold text-center text-[#323232] text-[12px] w-[70px] p-1 rounded-full bg-[#15919A] text-white"
          >
            <strong>Value:</strong> <span>{{ target.value }}</span>
          </div>

          <div
            class="font-bold text-center text-[#323232] text-[12px] w-[70px] p-1 rounded-full bg-[#15919A] text-white"
          >
            Index: {{ index + 1 }}
          </div>
        </div>
      </div>
    </div>
    <!-- Target Card end-->

    <!-- Modal start-->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50"
      >
        <div class="max-w-md p-6 bg-white rounded-lg w-96">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ isEditing ? "Edit Target" : "Add New Target" }}
            </h3>
            <button
              @click="closeModal"
              class="text-gray-400 transition-colors duration-200 hover:text-gray-600"
            >
              <svg
                class="w-6 h-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                ></path>
              </svg>
            </button>
          </div>

          <form @submit.prevent="isEditing ? updateTarget() : addTarget()">
            <div class="mb-4">
              <label
                for="target"
                class="block mb-2 text-sm font-medium text-gray-700"
                >Target</label
              >

              <textarea
                v-model="newTarget.target"
                placeholder="Enter target value(s), one per line"
                rows="3"
              ></textarea>
            </div>

            <div class="mb-4">
              <label
                for="equivalent"
                class="block mb-2 text-sm font-medium text-gray-700"
                >Equivalent</label
              >

              <textarea
                v-model="newTarget.equivalent"
                placeholder="Enter equivalent value(s), one per line"
                rows="3"
              ></textarea>
              <!-- <input
                id="equivalent"
                v-model="newTarget.equivalent"
                type="text"
                class="w-full px-3 py-2 text-black border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter equivalent value"
                required
              /> -->
            </div>

            <div class="mb-6">
              <label class="flex items-center">
                <input
                  v-model="newTarget.can_unselect"
                  type="checkbox"
                  class="w-4 h-4 mr-2 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700"
                  >Can Unselect</span
                >
              </label>
            </div>

            <div class="flex justify-end space-x-3">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 text-gray-700 transition-colors duration-200 bg-gray-200 rounded-md hover:bg-gray-300"
              >
                Nevermind
              </button>
              <button
                type="submit"
                class="px-4 py-2 text-white transition-colors duration-200 bg-blue-500 rounded-md hover:bg-blue-600"
              >
                {{ isEditing ? "Update Target" : "Add Target" }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useDataStore } from "@/stores/data";

const dataStore = useDataStore();
const showModal = ref(false);
const isEditing = ref(false);
const editIndex = ref(null);
const openDropdown = ref(null);
const selectedTargets = ref([]);
const allSelected = ref(false);

const newTarget = ref({
  target: "",
  equivalent: "",
  can_unselect: false,
  colors: {
    normal: null,
    hover: null,
    active: null,
  },
});

// Single target addition
const addSingleTarget = () => {
  dataStore.addTarget({
    target: newTarget.value.target,
    equivalent: newTarget.value.equivalent,
    can_unselect: newTarget.value.can_unselect,
    colors: { ...newTarget.value.colors },
  });

  console.log("Added target:", dataStore.targets);
};

// Multiple targets addition (from textarea with newlines)
const addMultipleTargets = () => {
  const targetLines = newTarget.value.target
    .split("\n")
    .map((line) => line.trim())
    .filter((line) => line.length > 0);

  const equivalentLines = newTarget.value.equivalent
    .split("\n")
    .map((line) => line.trim())
    .filter((line) => line.length > 0);

  targetLines.forEach((target, index) => {
    dataStore.addTarget({
      target: target,
      equivalent: equivalentLines[index] || "",
      can_unselect: newTarget.value.can_unselect,
      colors: { ...newTarget.value.colors },
    });
  });

  console.log("Added multiple targets:", dataStore.targets);
};

// Combined add function
const addTarget = () => {
  if (
    newTarget.value.target.includes("\n") ||
    newTarget.value.equivalent.includes("\n")
  ) {
    addMultipleTargets();
  } else {
    addSingleTarget();
  }
  
  // Notify parent window of changes if in iframe
  if (window.parent !== window) {
    window.parent.postMessage({
      type: 'LIVE_EDITOR_UPDATE',
      data: dataStore.tests
    }, '*');
  }
};

// Update existing target
const updateTarget = () => {
  if (editIndex.value !== null) {
    dataStore.updateTarget({
      index: editIndex.value,
      ...newTarget.value,
    });
    closeModal();
    
    // Notify parent window of changes if in iframe
    if (window.parent !== window) {
      window.parent.postMessage({
        type: 'LIVE_EDITOR_UPDATE',
        data: dataStore.tests
      }, '*');
    }
  }
};

// Close modal and reset form
const closeModal = () => {
  showModal.value = false;
  isEditing.value = false;
  editIndex.value = null;
  newTarget.value = {
    target: "",
    equivalent: "",
    can_unselect: false,
    colors: {
      normal: null,
      hover: null,
      active: null,
    },
  };
};

// Delete single target
const deleteTarget = (index) => {
  try {
    dataStore.deleteTarget(index);
    // Remove from selected if it was selected
    const selectedIndex = selectedTargets.value.indexOf(index);
    if (selectedIndex !== -1) {
      selectedTargets.value.splice(selectedIndex, 1);
    }
    openDropdown.value = null;
    
    // Notify parent window of changes if in iframe
    if (window.parent !== window) {
      window.parent.postMessage({
        type: 'LIVE_EDITOR_UPDATE',
        data: dataStore.tests
      }, '*');
    }
    
    console.log('Target deleted successfully, updated store:', dataStore.tests.targets);
  } catch (error) {
    console.error('Error deleting target:', error);
  }
};

// Selection methods
const toggleSelectAll = () => {
  if (allSelected.value) {
    // Unselect all
    selectedTargets.value = [];
    allSelected.value = false;
  } else {
    // Select all
    selectedTargets.value = dataStore.targets.map((_, index) => index);
    allSelected.value = true;
  }
  console.log("Selected targets:", selectedTargets.value);
};

const toggleTargetSelection = (index) => {
  const selectedIndex = selectedTargets.value.indexOf(index);
  if (selectedIndex !== -1) {
    selectedTargets.value.splice(selectedIndex, 1);
  } else {
    selectedTargets.value.push(index);
  }
  updateAllSelectedState();
  console.log("Selected targets:", selectedTargets.value);
};

const updateAllSelectedState = () => {
  allSelected.value =
    selectedTargets.value.length === dataStore.targets.length &&
    dataStore.targets.length > 0;
};

const deleteSelectedTargets = () => {
  if (selectedTargets.value.length === 0) {
    return;
  }

  try {
    // Convert to array and sort in descending order to avoid index shifting issues
    const sortedIndices = [...selectedTargets.value].sort((a, b) => b - a);

    // Delete each selected target
    sortedIndices.forEach((index) => {
      dataStore.deleteTarget(index);
    });

    // Clear selection
    selectedTargets.value = [];
    
    // Notify parent window of changes if in iframe
    if (window.parent !== window) {
      window.parent.postMessage({
        type: 'LIVE_EDITOR_UPDATE',
        data: dataStore.tests
      }, '*');
    }
    
    console.log('Selected targets deleted successfully, updated store:', dataStore.tests.targets);
  } catch (error) {
    console.error('Error deleting selected targets:', error);
  }
};

const toggleDropdown = (index) => {
  if (openDropdown.value === index) {
    openDropdown.value = null; // Close dropdown
  } else {
    openDropdown.value = index; // Open dropdown
  }
};

const editTarget = (index) => {
  const target = dataStore.targets[index];
  newTarget.value = {
    target: target.target,
    equivalent: target.equivalent,
    can_unselect: target.can_unselect,
    colors: { ...target.colors },
  };
  editIndex.value = index;
  isEditing.value = true;
  showModal.value = true;
  openDropdown.value = null;
};
</script>

<style>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.checkbox-wrapper {
  position: relative;
  width: var(--checkbox-size);
  height: var(--checkbox-size);
  border-radius: 8px;
  transition: transform 0.2s ease;
}

.checkbox-bg {
  position: absolute;
  inset: 0;
  border-radius: 20px;
  border: 2px solid var(--checkbox-border);
  background: white;
  transition: all 0.2s ease;
}

.checkbox-icon {
  position: absolute;
  inset: 0;
  margin: auto;
  width: 70%;
  height: 70%;
  color: white;
  transform: scale(0);
  transition: all 0.2s ease;
}

.check-path {
  stroke-dasharray: 40;
  stroke-dashoffset: 40;
  transition: stroke-dashoffset 0.3s ease 0.1s;
}

/* Checked State */
.ios-checkbox input:checked + .checkbox-wrapper .checkbox-bg {
  background: var(--checkbox-color);
  border-color: var(--checkbox-color);
}

.ios-checkbox input:checked + .checkbox-wrapper .checkbox-icon {
  transform: scale(1);
}

.ios-checkbox input:checked + .checkbox-wrapper .check-path {
  stroke-dashoffset: 0;
}

/* Hover Effects */
.ios-checkbox:hover .checkbox-wrapper {
  transform: scale(1.05);
}

/* Active Animation */
.ios-checkbox:active .checkbox-wrapper {
  transform: scale(0.95);
}

/* Focus Styles */
.ios-checkbox input:focus + .checkbox-wrapper .checkbox-bg {
  box-shadow: 0 0 0 4px var(--checkbox-bg);
}

/* Color Themes */
.ios-checkbox.blue {
  --checkbox-color: #15919a;
  --checkbox-bg: #dbeafe;
  --checkbox-border: #15919a;
}

.ios-checkbox.green {
  --checkbox-color: #10b981;
  --checkbox-bg: #15919a;
  --checkbox-border: #15919a;
}

.ios-checkbox.purple {
  --checkbox-color: #8b5cf6;
  --checkbox-bg: #ede9fe;
  --checkbox-border: #c4b5fd;
}

.ios-checkbox.red {
  --checkbox-color: #ef4444;
  --checkbox-bg: #fee2e2;
  --checkbox-border: #fca5a5;
}

/* Animation */
@keyframes bounce {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

.ios-checkbox input:checked + .checkbox-wrapper {
  animation: bounce 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.2s ease-out;
}
</style>