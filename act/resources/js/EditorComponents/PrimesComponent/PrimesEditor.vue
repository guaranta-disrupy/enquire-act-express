<template>
  <div class="flex flex-col w-full h-full gap-3 overflow-y-auto bg-gray-50">
    <div
      class="bg-[#15919A] shadow p-4 mb-2 w-[full] justify-between flex flex-row text-white text-[20px] font-bold"
    >
      Primes

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
          class="px-3 py-1 font-bold text-black text-[16px] bg-white rounded-full"
          @click="deleteSelectedPrimes"
        >
          Delete
        </button>

        <button
          class="px-3 py-1 font-bold text-black text-[16px] bg-white rounded-full"
          @click="toggleExpand"
        >
          {{ props.isExpanded ? "Shrink" : "Expand" }}
        </button>
      </div>
    </div>

    <!-- Prime Card container start-->
    <div
      class="w-full overflow-y-auto h-[calc(100vh-160px)] custom-scrollbar"
      :class="
        props.isExpanded
          ? 'grid grid-cols-4 gap-6 p-6 expanded-grid'
          : 'flex flex-col gap-2 pl-3'
      "
    >
      <div class="" :class="props.isExpanded ? 'col-span-1' : 'mb-4'">
        <button
          @click="showModal = true"
          class="group uppercase shadow flex flex-col border border-gray-300 h-[237px] w-full max-w-[352px] rounded-lg justify-center items-center text-[#323232] hover:bg-[#15919A] hover:text-white font-bold"
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
          Add Prime
        </button>
      </div>

      <!-- Prime card start-->
      <div
        v-for="(primes, index) in dataStore.tests.primes"
        :key="index"
        class="flex h-[237px] w-full max-w-[352px] flex-col transition-all duration-300 bg-white rounded-lg shadow hover:shadow-lg p-2 border border-gray-300"
        :class="props.isExpanded ? 'col-span-1' : 'mb-3'"
      >
        <div class="flex flex-row items-center justify-between">
          <div class="checkbox-container">
            <label class="ios-checkbox blue">
              <input
                type="checkbox"
                :checked="selectedPrimes.has(index)"
                @change="togglePrimeSelection(index)"
              />
              <div class="checkbox-wrapper">
                <div class="checkbox-bg"></div>
                <svg fill="none" viewBox="0 0 24 24" class="checkbox-icon">
                  <path
                    stroke-linejoin="round"
                    stroke-linecap="round"
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
                  @click="editPrime(index)"
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
                  @click="deletePrimes(index)"
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

        <!-- Primes -->
        <div class="px-2 text-[#323232]">
          <div class="text-[3vmin] font-bold py-6 text-center">
            <span v-html="primes.prime"></span>
          </div>

          <div
            class="text-[1.5vmin] text-gray-500 py-3 border-t border-gray-300"
          >
            <strong>Equivalent:</strong>
            <span v-html="primes.equivalent"></span>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-2 py-2 text-center rounded-b-lg">
          <!-- Footer content removed - actions now in dropdown -->
        </div>

        <!-- index -->
        <div class="flex justify-end p-2">
          <div
            class="font-bold text-center text-[#323232] text-[12px] w-[70px] p-1 rounded-full bg-[#15919A] text-white"
          >
            Index: {{ index + 1 }}
          </div>
        </div>
      </div>
    </div>
    s
    <!-- Prime Card end-->

    <!-- Modal start-->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50"
      >
        <div class="max-w-md p-6 bg-white rounded-lg w-96">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ isEditing ? "Edit Prime" : "Add New Prime" }}
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

          <form @submit.prevent="isEditing ? updatePrime() : addPrime()">
            <div class="mb-4">
              <label
                for="prime"
                class="block mb-2 text-sm font-medium text-gray-700"
                >Prime</label
              >
              <textarea v-model="newPrime.prime">
              <!-- <input
                id="prime"
                v-model="newPrime.prime"
                type="text"
                class="w-full px-3 py-2 text-black border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter prime value"
                required
              /> -->
              </textarea>
              <textarea v-model="newPrime.equivalent"></textarea>
            </div>

            <!-- <div class="mb-6">
              <label
                for="equivalent"
                class="block mb-2 text-sm font-medium text-gray-700"
                >Equivalent</label
              >
              <input
                id="equivalent"
                v-model="newPrime.equivalent"
                type="text"
                class="w-full px-3 py-2 text-black border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter equivalent value"
                required
              />
            </div> -->

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
                {{ isEditing ? "Update Prime" : "Add Prime" }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
    <!-- Modal end-->
  </div>
</template>

<script setup>
import { useDataStore } from "@/stores/data";
import { storeToRefs } from "pinia";
import { ref, onMounted } from "vue";

const emit = defineEmits(["toggle-expand"]);

const props = defineProps({
  isExpanded: {
    type: Boolean,
    default: false,
  },
});

const dataStore = useDataStore();

// Reactive data for modal
const showModal = ref(false);
const isEditing = ref(false);
const editingIndex = ref(-1);
const newPrime = ref({
  prime: [],
  equivalent: [],
});

// Reactive data for selection
const selectedPrimes = ref(new Set());
const allSelected = ref(false);

// Reactive data for dropdown
const openDropdown = ref(-1);

// Methods
const stripHtmlTags = (htmlString) => {
  if (!htmlString) return "";
  return htmlString.replace(/<[^>]*>/g, "");
};

const closeModal = () => {
  showModal.value = false;
  isEditing.value = false;
  editingIndex.value = -1;
  // Reset form
  newPrime.value = {
    prime: "",
    equivalent: "",
  };
};

const editPrime = (index) => {
  isEditing.value = true;
  editingIndex.value = index;
  // Populate form with existing data, stripping HTML tags
  newPrime.value = {
    prime: stripHtmlTags(dataStore.tests.primes[index].prime),
    equivalent: stripHtmlTags(dataStore.tests.primes[index].equivalent),
  };
  showModal.value = true;
};

const addPrime = () => {
  // Extract the string from newPrime.value
  const primeString =
    newPrime.value.str || newPrime.value.prime || newPrime.value;
  const equivalentString = newPrime.value.equivalent || "";

  // Split into arrays and clean up
  const primeArray = primeString
    .split("\n")
    .map((line) => line.trim())
    .filter((line) => line.length > 0);

  const equivalentArray = equivalentString
    .split("\n")
    .map((line) => line.trim())
    .filter((line) => line.length > 0);

  console.log("Prime Array:", primeArray);
  console.log("Equivalent Array:", equivalentArray);

  // Handle single prime
  if (primeArray.length === 1) {
    const addedPrime = dataStore.addPrime({
      prime: primeArray[0],
      equivalent: equivalentArray[0] || "", // Use first equivalent if exists
    });
    console.log("Single prime added:", addedPrime);
  }
  // Handle multiple primes
  else if (primeArray.length > 1) {
    const addedPrimes = primeArray.map((primeText, index) =>
      dataStore.addPrime({
        prime: primeText,
        equivalent: equivalentArray[index] || "", // Match by index or empty
      })
    );
    console.log("Multiple primes added:", addedPrimes);
  }

  console.log("Current primes in store:", dataStore.tests.primes);
  closeModal();
  
  // Notify parent window of changes if in iframe
  if (window.parent !== window) {
    window.parent.postMessage({
      type: 'LIVE_EDITOR_UPDATE',
      data: dataStore.tests
    }, '*');
  }
};

const updatePrime = () => {
  console.log(
    "Updating prime at index:",
    editingIndex.value,
    "with data:",
    newPrime.value
  );

  // Update the existing prime in the store
  dataStore.updatePrime(editingIndex.value, {
    prime: newPrime.value.prime,
    equivalent: newPrime.value.equivalent,
  });

  console.log("Prime updated in store");
  console.log("Current primes in store:", dataStore.tests.primes);

  // Close modal and reset form
  closeModal();
  
  // Notify parent window of changes if in iframe
  if (window.parent !== window) {
    window.parent.postMessage({
      type: 'LIVE_EDITOR_UPDATE',
      data: dataStore.tests
    }, '*');
  }
};

const deletePrimes = (index) => {
  try {
    dataStore.deletePrimes(index);
    // Remove from selected set if it was selected
    selectedPrimes.value.delete(index);
    updateAllSelectedState();
    
    // Notify parent window of changes if in iframe
    if (window.parent !== window) {
      window.parent.postMessage({
        type: 'LIVE_EDITOR_UPDATE',
        data: dataStore.tests
      }, '*');
    }
    
    console.log('Prime deleted successfully, updated store:', dataStore.tests.primes);
  } catch (error) {
    console.error('Error deleting prime:', error);
  }
};

// Selection methods
const toggleSelectAll = () => {
  if (allSelected.value) {
    // Unselect all
    selectedPrimes.value.clear();
    allSelected.value = false;
  } else {
    // Select all
    selectedPrimes.value = new Set(
      dataStore.tests.primes.map((_, index) => index)
    );
    allSelected.value = true;
  }
  console.log("Selected primes:", selectedPrimes.value);
};

const togglePrimeSelection = (index) => {
  if (selectedPrimes.value.has(index)) {
    selectedPrimes.value.delete(index);
  } else {
    selectedPrimes.value.add(index);
  }
  updateAllSelectedState();
  console.log("Selected primes:", selectedPrimes.value);
};

const toggleDropdown = (index) => {
  if (openDropdown.value === index) {
    openDropdown.value = -1; // Close dropdown
  } else {
    openDropdown.value = index; // Open dropdown
  }
};

const updateAllSelectedState = () => {
  allSelected.value =
    selectedPrimes.value.size === dataStore.tests.primes.length &&
    dataStore.tests.primes.length > 0;
};

const deleteSelectedPrimes = () => {
  if (selectedPrimes.value.size === 0) {
    return;
  }

  try {
    // Convert to array and sort in descending order to avoid index shifting issues
    const sortedIndices = Array.from(selectedPrimes.value).sort((a, b) => b - a);

    // Delete each selected prime
    sortedIndices.forEach((index) => {
      dataStore.deletePrimes(index);
    });

    // Clear selection
    selectedPrimes.value.clear();
    
    // Notify parent window of changes if in iframe
    if (window.parent !== window) {
      window.parent.postMessage({
        type: 'LIVE_EDITOR_UPDATE',
        data: dataStore.tests
      }, '*');
    }
    
    console.log('Selected primes deleted successfully, updated store:', dataStore.tests.primes);
  } catch (error) {
    console.error('Error deleting selected primes:', error);
  }
};

const toggleExpand = () => {
  emit("toggle-expand");
};

// Close dropdown when clicking outside
const closeDropdown = () => {
  openDropdown.value = -1;
};

// Add click outside listener
onMounted(() => {
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".dropdown-container")) {
      closeDropdown();
    }
  });
});
</script>

<style>
/* Remove the scrollbar-hide classes that were preventing scrolling */
.overflow-y-auto {
  overflow-y: auto;
}

/* Custom scrollbar styling for better visibility */
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: #15919a #f3f4f6;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: #f3f4f6;
  border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #15919a;
  border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: #0c7c84;
}

/* From Uiverse.io by kyle1dev */
.checkbox-container {
  display: flex;
  gap: 3px;
  padding: 3px;

  border-radius: 5px;
  /* box-shadow:
    0 4px 6px -1px rgba(0, 0, 0, 0.1),
    0 2px 4px -2px rgba(0, 0, 0, 0.05); */
}

.ios-checkbox {
  --checkbox-size: 25px;
  --checkbox-color: #15919a;

  --checkbox-border: #93c5fd;

  position: relative;
  display: inline-block;
  cursor: pointer;
  user-select: none;
  -webkit-tap-highlight-color: transparent;
}

.ios-checkbox input {
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

/* Expanded Grid Layout Styles */
.expanded-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
  justify-items: center;
  align-items: start;
}

.expanded-grid > div {
  width: 100%;
  max-width: 352px;
  justify-self: center;
}

/* Ensure consistent card spacing in grid */
.expanded-grid > div {
  margin-bottom: 0;
  margin-right: 0;
}

/* Smooth transitions for layout changes */
.expanded-grid > div {
  transition: all 0.3s ease-in-out;
}
</style>