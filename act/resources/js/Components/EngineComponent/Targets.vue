<template>
  <div class="targets-container">
    <div v-if="hasTargets && rawTargetElement">
      <!-- Target Circle -->
      <div
        class="absolute rounded-full flex items-center p-4 text-black target-circle"
        :style="{
          width: (dataStore.tests.config.properties.Targets?.CircleSize || 20) + 'vmin',
          height: (dataStore.tests.config.properties.Targets?.CircleSize || 20) + 'vmin',
          transform: 'translate(-50%, -50%)',
          backgroundColor: dataStore.tests.config.properties.Targets?.bg || '#ffffff',
          borderStyle: 'solid',
          borderColor: dataStore.tests.config.properties.Targets?.borderColor || '#D1D5DB',
          borderWidth: (dataStore.tests.config.properties.Targets?.borderSize || 1.2) + 'vmin',
        }"
        @click="showModal = true"
      >
        <div class="circle-quill-container">
          <div>
            <p ref="targetContentHtml" contenteditable="true" @blur="getRawhtml()" :style="{
              fontSize: (rawTargetElement.properties?.fontSize || 2) + 'vmin',
              fontFamily: rawTargetElement.properties?.fontFamily || 'Arial',
              fontWeight: rawTargetElement.properties?.fontWeight || 'normal',
              fontStyle: rawTargetElement.properties?.fontStyle || 'normal',
              color: rawTargetElement.properties?.fontColor || '#000000',
              textAlign: rawTargetElement.properties?.fontAlignment || 'center',
              textDecoration: rawTargetElement.properties?.fontDecoration || 'none',
              backgroundColor: rawTargetElement.properties?.fontBackground || 'transparent',
            }">
              {{ rawTargetElement.rawTargetElement }}
            </p>
          </div>
        </div>
      </div>

      <!-- Target Detail Modal -->
      <Teleport to="body">
        <div v-show="showModal" class="fixed z-[9999] bg-gradient-to-br from-white to-gray-50 border-2 border-blue-200 rounded-xl shadow-2xl min-w-[120px] max-w-[450px] draggable-modal backdrop-blur-sm" :class="{ dragging: isDragging }" :style="{
          top: modalPosition.y + 'px',
          left: modalPosition.x + 'px',
          transform: 'translate(100%, -100%)',
        }">

          <div class="flex items-center justify-between bg-gradient-to-r from-blue-500 to-blue-600 rounded-t-xl px-4 py-2 draggable-header" @mousedown="startDrag">
            <h3 class="text-white font-semibold text-sm">Edit Target</h3>
            <button
              @click="showModal = false"
              @mousedown.stop
              class="text-xl px-2 py-1 font-bold text-white hover:text-red-200 hover:bg-white/20 rounded-full transition-all duration-200 cursor-pointer"
            >
              ×
            </button>
          </div>
          
          <div class="p-6 mt-10">
            <div class="w-full mb-4 bg-gray-50 rounded-lg p-3 border border-gray-200" :id="`toolbar-targets${rawTargetElement.index}`">
              <div class="flex flex-wrap items-center justify-center gap-2">
                <!-- Circle Size Range -->
                <div class="flex flex-col">
                  <label class="text-xs text-gray-600 mb-1">Circle Size: {{ dataStore.tests.config.properties.Targets?.CircleSize || 20 }}vmin</label>
                  <input
                    type="range"
                    :value="dataStore.tests.config.properties.Targets?.CircleSize || 20"
                    @input="updateConfigProperty('CircleSize', Number($event.target.value))"
                    min="10"
                    max="100"
                    step="1"
                    class="w-[120px] h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
                  />
                </div>

                <!-- Background Color -->
                <div class="flex flex-col">
                  <label class="text-xs text-gray-600 mb-1">Background</label>
                  <ColorPicker
                    @pureColorChange="(color) => updateConfigProperty('bg', color)"
                    :pureColor="dataStore.tests.config.properties.Targets?.bg"
                    style="z-index: 10001;"
                  />
                </div>

                <!-- Border Color -->
                <div class="flex flex-col">
                  <label class="text-xs text-gray-600 mb-1">Border Color</label>
                  <ColorPicker
                    @pureColorChange="(color) => updateConfigProperty('borderColor', color)"
                    :pureColor="dataStore.tests.config.properties.Targets?.borderColor"
                    style="z-index: 10001;"
                  />
                </div>

                <!-- Border Size Range -->
                <div class="flex flex-col">
                  <label class="text-xs text-gray-600 mb-1">Border Size: {{ dataStore.tests.config.properties.Targets?.borderSize || 1.2 }}vmin</label>
                  <input
                    type="range"
                    :value="dataStore.tests.config.properties.Targets?.borderSize || 1.2"
                    @input="updateConfigProperty('borderSize', Number($event.target.value))"
                    min="0"
                    max="5"
                    step="0.1"
                    class="w-[120px] h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
                  />
                </div>

                <!-- Font Size Range -->
                <div class="flex flex-col">
                  <label class="text-xs text-gray-600 mb-1">Font Size: {{ rawTargetElement.properties?.fontSize || 2 }}vmin</label>
                  <input
                    type="range"
                    :value="rawTargetElement.properties?.fontSize || 2"
                    @input="updateTargetProperty('fontSize', Number($event.target.value))"
                    min="1"
                    max="100"
                    step="0.1"
                    class="w-[120px] h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
                  />
                </div>

                <!-- Font Family -->
                <div class="flex flex-col">
                  <label class="text-xs text-gray-600 mb-1">Font Family</label>
                  <select
                    :value="rawTargetElement.properties?.fontFamily || 'Arial'"
                    @change="updateTargetProperty('fontFamily', $event.target.value)"
                    class="w-[120px] px-3 py-2 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all duration-200"
                  >
                    <option value="Arial">Arial</option>
                    <option value="Helvetica">Helvetica</option>
                    <option value="Times New Roman">Times New Roman</option>
                    <option value="Georgia">Georgia</option>
                    <option value="Verdana">Verdana</option>
                    <option value="Courier New">Courier New</option>
                    <option value="Comic Sans MS">Comic Sans MS</option>
                    <option value="Impact">Impact</option>
                  </select>
                </div>

                <!-- Font Weight -->
                <div class="flex flex-col">
                  <label class="text-xs text-gray-600 mb-1">Font Weight</label>
                  <select
                    :value="rawTargetElement.properties?.fontWeight || 'normal'"
                    @change="updateTargetProperty('fontWeight', $event.target.value)"
                    class="w-[120px] px-3 py-2 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all duration-200"
                  >
                    <option value="normal">Normal</option>
                    <option value="bold">Bold</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                    <option value="300">300</option>
                    <option value="400">400</option>
                    <option value="500">500</option>
                    <option value="600">600</option>
                    <option value="700">700</option>
                    <option value="800">800</option>
                    <option value="900">900</option>
                  </select>
                </div>

                <!-- Font Style -->
                <div class="flex flex-col">
                  <label class="text-xs text-gray-600 mb-1">Font Style</label>
                  <select
                    :value="rawTargetElement.properties?.fontStyle || 'normal'"
                    @change="updateTargetProperty('fontStyle', $event.target.value)"
                    class="w-[120px] px-3 py-2 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all duration-200"
                  >
                    <option value="normal">Normal</option>
                    <option value="italic">Italic</option>
                    <option value="oblique">Oblique</option>
                  </select>
                </div>

                <!-- Font Color -->
                <div class="flex flex-col">
                  <label class="text-xs text-gray-600 mb-1">Font Color</label>
                  <ColorPicker
                    @pureColorChange="(color) => updateTargetProperty('fontColor', color)"
                    :pureColor="rawTargetElement.properties?.fontColor"
                    style="z-index: 10001;"
                  />
                </div>

                <!-- Font Alignment -->
                <div class="flex flex-col">
                  <label class="text-xs text-gray-600 mb-1">Text Alignment</label>
                  <select
                    :value="rawTargetElement.properties?.fontAlignment || 'center'"
                    @change="updateTargetProperty('fontAlignment', $event.target.value)"
                    class="w-[120px] px-3 py-2 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all duration-200"
                  >
                    <option value="left">Left</option>
                    <option value="center">Center</option>
                    <option value="right">Right</option>
                    <option value="justify">Justify</option>
                  </select>
                </div>

                <!-- Font Decoration -->
                <div class="flex flex-col">
                  <label class="text-xs text-gray-600 mb-1">Text Decoration</label>
                  <select
                    :value="rawTargetElement.properties?.fontDecoration || 'none'"
                    @change="updateTargetProperty('fontDecoration', $event.target.value)"
                    class="w-[120px] px-3 py-2 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all duration-200"
                  >
                    <option value="none">None</option>
                    <option value="underline">Underline</option>
                    <option value="line-through">Strikethrough</option>
                    <option value="overline">Overline</option>
                  </select>
                </div>

                <!-- Font Background -->
                <div class="flex flex-col">
                  <label class="text-xs text-gray-600 mb-1">Font Background</label>
                  <ColorPicker
                    @pureColorChange="(color) => updateTargetProperty('fontBackground', color)"
                    :pureColor="rawTargetElement.properties?.fontBackground"
                    style="z-index: 10001;"
                  />
                </div>

              </div>
            </div>

            <!-- Pagination Controls in Modal -->
            <div class="modal-pagination flex items-center justify-between bg-gray-50 rounded-lg p-3 border border-gray-200">
              <button
                @click="goToPrevious"
                :disabled="!canGoToPrevious"
                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed transition-all duration-200 font-medium text-sm"
              >
                ← Previous
              </button>
              
              <div class="pagination-info bg-white px-3 py-1 rounded-full border border-gray-300 text-sm font-semibold text-gray-700">
                {{ currentPage + 1 }} of {{ totalPages }}
              </div>
              
              <button
                @click="goToNext"
                :disabled="!canGoToNext"
                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:bg-gray-300 disabled:cursor-not-allowed transition-all duration-200 font-medium text-sm"
              >
                Next →
              </button>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </div>
</template>

<script setup>
import { useDataStore } from "@/stores/data";
import { ref, computed } from "vue";
import { ColorPicker } from "vue3-colorpicker";

const dataStore = useDataStore();
const targetContentHtml = ref(null);

const getRawhtml = () => {
  console.log(targetContentHtml.value.innerHTML);
  dataStore.updateTargetByIndex(rawTargetElement.value.index, {
    rawTargetElement: targetContentHtml.value.innerText,
  });
}

// Pagination state
const currentPage = ref(0);

// Modal state
const showModal = ref(false);

// Draggable modal functionality
const modalPosition = ref({ x: 500, y: 800 });
const isDragging = ref(false);
const dragOffset = ref({ x: 0, y: 0 });

// Computed property to get current target
const rawTargetElement = computed(() => {
  if (!dataStore.tests.targets || dataStore.tests.targets.length === 0) {
    return null;
  }
  return dataStore.tests.targets[currentPage.value];
});

// Computed property to check if there are targets
const hasTargets = computed(() => {
  return dataStore.tests.targets && dataStore.tests.targets.length > 0;
});

// Computed property for total pages
const totalPages = computed(() => {
  return dataStore.tests.targets ? dataStore.tests.targets.length : 0;
});

// Navigation functions
const goToPrevious = () => {
  if (currentPage.value > 0) {
    currentPage.value--;
  }
};

const goToNext = () => {
  if (currentPage.value < totalPages.value - 1) {
    currentPage.value++;
  }
};

// Check if navigation buttons should be disabled
const canGoToPrevious = computed(() => currentPage.value > 0);
const canGoToNext = computed(() => currentPage.value < totalPages.value - 1);

// Draggable modal functions
const startDrag = (event) => {
  isDragging.value = true;
  dragOffset.value = {
    x: event.clientX - modalPosition.value.x,
    y: event.clientY - modalPosition.value.y
  };
  
  document.addEventListener('mousemove', onDrag);
  document.addEventListener('mouseup', stopDrag);
};

const onDrag = (event) => {
  if (!isDragging.value) return;
  
  modalPosition.value = {
    x: event.clientX - dragOffset.value.x,
    y: event.clientY - dragOffset.value.y
  };
};

const stopDrag = () => {
  isDragging.value = false;
  document.removeEventListener('mousemove', onDrag);
  document.removeEventListener('mouseup', stopDrag);
};

// Function to update target properties
const updateTargetProperty = (property, value) => {
  if (!rawTargetElement.value) {
    return;
  }
  
  // Ensure properties object exists
  if (!rawTargetElement.value.properties) {
    rawTargetElement.value.properties = {};
  }
  
  // Update the property
  rawTargetElement.value.properties[property] = value;
  
  // Update in datastore
  const arrayIndex = dataStore.tests.targets.findIndex(t => t.index === rawTargetElement.value.index);
  if (arrayIndex !== -1) {
    dataStore.tests.targets[arrayIndex].properties[property] = value;
  }
};

// Function to update config properties
const updateConfigProperty = (property, value) => {
  // Ensure config properties object exists
  if (!dataStore.tests.config.properties) {
    dataStore.tests.config.properties = {};
  }
  if (!dataStore.tests.config.properties.Targets) {
    dataStore.tests.config.properties.Targets = {};
  }
  
  // Update the config property
  dataStore.tests.config.properties.Targets[property] = value;
};
</script>

<style scoped>
.circle-quill-editor .ql-toolbar.ql-snow {
  position: absolute !important;
  z-index: 9000;
}

.circle-quill-editor .ql-tooltip {
  min-width: 300px;
  font-size: 16px;
  border-radius: 6px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
    0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
  position: fixed !important; /* escape circle overflow clipping */
  z-index: 100000 !important;
}

/* Ensure line breaks are properly displayed */
.circle-quill-editor .ql-editor {
  white-space: pre-wrap;
  word-wrap: break-word;
  word-break: keep-all;
  line-height: 1.2;
  text-align: center;
  hyphens: none;
  overflow-wrap: break-word;
}

.circle-quill-editor .ql-editor p {
  margin: 0;
  padding: 0;
  display: block;
  word-break: keep-all;
  hyphens: none;
}

.circle-quill-editor .ql-editor p:empty {
  min-height: 1.2em;
}

.circle-quill-editor .ql-editor br {
  display: block;
  content: "";
  margin-top: 0.3em;
  line-height: 1.2;
}

/* Handle empty paragraphs with br tags */
.circle-quill-editor .ql-editor p:has(br:only-child) {
  min-height: 1.5em;
}

/* Alternative for browsers that don't support :has() */
.circle-quill-editor .ql-editor p br:only-child {
  display: block;
  height: 1.2em;
  content: "";
}

/* Ensure the circular container handles multi-line content properly */
.qlContainer {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  text-align: center !important;
  overflow: hidden !important; /* keep content inside center circle */
  word-break: keep-all;
  hyphens: none;
}

.circle-quill-container {
  width: 100%;
  height: auto;
  display: grid;
  place-items: center;
  overflow: hidden; /* keep content inside center circle */
  word-break: keep-all;
  hyphens: none;
}

.circle-quill-editor {
  width: 100%;
  height: auto;
  word-break: keep-all;
  hyphens: none;
}

.circle-quill-editor .ql-container {
  height: auto !important;
  word-break: keep-all;
  hyphens: none;
}

/* Target circle styling */
.target-circle {
  cursor: pointer;
  transition: all 0.2s ease;
}

.target-circle .circle-quill-editor {
  font-weight: normal !important;
}

.target-circle .circle-quill-editor * {
  font-weight: normal !important;
  font-style: normal !important;
  text-decoration: none !important;
}

/* Override font properties with higher specificity */
.target-circle .circle-quill-editor[style*="font-size"] {
  font-size: inherit !important;
}

.target-circle .circle-quill-editor[style*="font-family"] {
  font-family: inherit !important;
}

.target-circle .circle-quill-editor[style*="font-weight"] {
  font-weight: inherit !important;
}

.target-circle .circle-quill-editor[style*="font-style"] {
  font-style: inherit !important;
}

.target-circle .circle-quill-editor[style*="color"] {
  color: inherit !important;
}

.target-circle .circle-quill-editor[style*="text-align"] {
  text-align: inherit !important;
}

.target-circle .circle-quill-editor[style*="text-decoration"] {
  text-decoration: inherit !important;
}

.target-circle .circle-quill-editor[style*="background-color"] {
  background-color: inherit !important;
}

.target-circle:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Modal pagination controls */
.modal-pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.pagination-button {
  padding: 0.5rem 1rem;
  background-color: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
  transition: background-color 0.2s;
  font-size: 0.875rem;
}

.pagination-button:hover {
  background-color: #2563eb;
}

.pagination-button:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

.pagination-info {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}
</style>

<style>
ql-container {
  height: auto;
}
.ql-editor {
  height: auto;
}

/* Draggable modal styles */
.draggable-modal {
  transition: none !important;
}

.draggable-modal.dragging {
  transition: none !important;
  user-select: none;
}

.draggable-header {
  cursor: move;
  user-select: none;
}

.draggable-header:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

/* Range slider styling */
.slider::-webkit-slider-thumb {
  appearance: none;
  height: 16px;
  width: 16px;
  border-radius: 50%;
  background: #3b82f6;
  cursor: pointer;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.slider::-moz-range-thumb {
  height: 16px;
  width: 16px;
  border-radius: 50%;
  background: #3b82f6;
  cursor: pointer;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.slider::-webkit-slider-track {
  background: #e5e7eb;
  border-radius: 8px;
}

.slider::-moz-range-track {
  background: #e5e7eb;
  border-radius: 8px;
  height: 8px;
}
</style>