<template>
  <div class="flex flex-col w-full h-full overflow-y-auto bg-gray-50">
    <div class="bg-[#257556] shadow p-4 mb-2 w-full justify-between flex flex-row text-white text-[20px] font-bold">
      Instructions
    </div>

    <div class="flex flex-col gap-2 p-4">
      <button 
        @click="openModal('title')"
        class="flex items-center justify-between p-4 bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200 border border-gray-200 hover:border-[#257556]"
      >
        <span class="text-lg font-semibold text-gray-700">Round Title</span>
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
        </svg>
      </button>

      <button 
        @click="openModal('main_header')"
        class="flex items-center justify-between p-4 bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200 border border-gray-200 hover:border-[#257556]"
      >
        <span class="text-lg font-semibold text-gray-700">Main Header</span>
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
        </svg>
      </button>

      <button 
        @click="openModal('bottom_text')"
        class="flex items-center justify-between p-4 bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200 border border-gray-200 hover:border-[#257556]"
      >
        <span class="text-lg font-semibold text-gray-700">Bottom Text</span>
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
        </svg>
      </button>

      <button 
        @click="openModal('warning_text')"
        class="flex items-center justify-between p-4 bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200 border border-gray-200 hover:border-[#257556]"
      >
        <span class="text-lg font-semibold text-gray-700">Warning Text</span>
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
        </svg>
      </button>

      <button 
        @click="openModal('warning_text')"
        class="flex items-center justify-between p-4 bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200 border border-gray-200 hover:border-[#257556]"
      >
        <span class="text-lg font-semibold text-gray-700">Progress Text</span>
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
        </svg>
      </button>


      <button 
        @click="openModal('')"
        class="flex items-center justify-between p-4 bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200 border border-gray-200 hover:border-[#257556]"
      >
        <span class="text-lg font-semibold text-gray-700">Saving Progress Text</span>
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
        </svg>
      </button>

      <button 
        @click="openModal('')"
        class="flex items-center justify-between p-4 bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200 border border-gray-200 hover:border-[#257556]"
      >
        <span class="text-lg font-semibold text-gray-700">Redirecting Text</span>
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
        </svg>
      </button>

      <button 
        @click="openModal('')"
        class="flex items-center justify-between p-4 bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200 border border-gray-200 hover:border-[#257556]"
      >
        <span class="text-lg font-semibold text-gray-700">Error Starting the App</span>
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
        </svg>
      </button>

      <button 
        @click="openModal('')"
        class="flex items-center justify-between p-4 bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200 border border-gray-200 hover:border-[#257556]"
      >
        <span class="text-lg font-semibold text-gray-700">Error Saving Progress</span>
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
        </svg>
      </button>


    </div>

    <!-- Instruction Modal - Unique CSS classes to avoid conflicts -->
    <Teleport to="body">
      <div v-if="showModal" class="instruction-modal-overlay">
        <div class="instruction-modal-container">
          <!--  <div class="instruction-modal-header">
            <h3 class="instruction-modal-title">{{ modalTitle }}</h3>
            <button 
              @click="closeModal"
              class="instruction-modal-close-btn"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>-->
          
          <div class="instruction-modal-content">
            <QuillEditor
              v-model:content="editingContent"
              theme="bubble"
              toolbar="full"
              contentType="html"
              :options="quillOptions"
              @ready="onEditorReady"
            />
          </div>
          
          <div class="instruction-modal-footer">
            <button
              type="button"
              @click="closeModal"
              class="instruction-modal-cancel-btn"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="saveContent"
              class="instruction-modal-save-btn"
            >
              Save Changes
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
  
  <!-- Image URL Modal -->
  <ImageUrlModal
    :show="showImageModal"
    @close="showImageModal = false"
    @insert="insertImageFromModal"
  />
</template>

<script setup>
import { useDataStore } from "@/stores/data";
import { storeToRefs } from "pinia";
import { ref, computed } from "vue";
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.bubble.css";
import ImageUrlModal from "@/Components/ImageUrlModal.vue";

const dataStore = useDataStore();





// Modal state
const showModal = ref(false);
const currentField = ref('');
const editingContent = ref('');
const showImageModal = ref(false);
const currentQuill = ref(null);

// Quill editor options
const quillOptions = {
  modules: {
    toolbar: [
      ['bold', 'italic', 'underline', 'strike'],
      ['blockquote', 'code-block'],
      [{ 'header': 1 }, { 'header': 2 }],
      [{ 'list': 'ordered'}, { 'list': 'bullet' }],
      [{ 'script': 'sub'}, { 'script': 'super' }],
      [{ 'indent': '-1'}, { 'indent': '+1' }],
      [{ 'direction': 'rtl' }],
      [{ 'size': ['small', false, 'large', 'huge'] }],
      [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
      [{ 'color': [] }, { 'background': [] }],
      [{ 'font': [] }],
      [{ 'align': [] }],
      ['link', 'image'],
      ['clean']
    ]
  },
  placeholder: 'Enter your text here...',
  theme: 'bubble'
};

// Computed properties
//const modalTitle = computed(() => {
  //const titles = {
    //'title': 'Edit Round Title',
    //'main_header': 'Edit Main Header',
   // 'bottom_text': 'Edit Bottom Text',
   // 'warning_text': 'Edit Warning Text'
  //};
 // return titles[currentField.value] || 'Edit Content';
//});

// Methods
const openModal = (field) => {
  currentField.value = field;
  
  // Get current content based on field
  const contentMap = {
    'title': dataStore.tests.config.title || '',
    'main_header': dataStore.tests.config.main_header || '',
    'bottom_text': dataStore.tests.config.bottom_text || '',
    'warning_text': dataStore.tests.config.empty_selection || ''
  };
  
  editingContent.value = contentMap[field] || '';
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  currentField.value = '';
  editingContent.value = '';
};

const saveContent = () => {
  // Update the store with new content
  const fieldMap = {
    'title': 'title',
    'main_header': 'main_header',
    'bottom_text': 'bottom_text',
    'warning_text': 'empty_selection'
  };
  
  const fieldName = fieldMap[currentField.value];
  if (fieldName) {
    // Update the store - you might need to implement this method in your store
    if (dataStore.updateConfig) {
      dataStore.updateConfig(fieldName, editingContent.value);
    } else {
      // Fallback: directly update the config object
      if (dataStore.tests && dataStore.tests.config) {
        dataStore.tests.config[fieldName] = editingContent.value;
      }
    }
    
    console.log(`Updated ${fieldName}:`, editingContent.value);
  }
  
  closeModal();
};

const onEditorReady = (quill) => {
  // Custom image handler for URL input
  const toolbar = quill.getModule('toolbar');
  toolbar.addHandler('image', () => {
    currentQuill.value = quill;
    showImageModal.value = true;
  });

  // Editor is ready
  console.log('Quill editor ready');
};

const insertImageFromModal = (imageUrl) => {
  if (currentQuill.value) {
    const range = currentQuill.value.getSelection();
    currentQuill.value.insertEmbed(range.index, 'image', imageUrl);
  }
};
</script>

<style>
/* Instruction Modal - Unique CSS classes to avoid conflicts with targets live editor */

/* Modal overlay */
.instruction-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgba(0, 0, 0, 0.5);
}

/* Modal container */
.instruction-modal-container {
  background-color: white;
  border-radius: 20px;
  padding: 24px;
  width: 900px;
  max-width: 80vw;
  max-height: 80vh;
  overflow: visible;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Modal header */
.instruction-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 16px;
  
}

.instruction-modal-title {
  font-size: 20px;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.instruction-modal-close-btn {
  color: #2a2c2e;
  background: none;

  cursor: pointer;
  padding: 4px;

  transition: all 0.2s;
}

.instruction-modal-close-btn:hover {
  color: #2d2e30;
  background-color: #f3f4f6;
}

/* Modal content */
.instruction-modal-content {
  margin-bottom: 16px;
  min-height: 200px;
  max-height: 200px;
  overflow: visible;
  position: relative;
}

/* Modal footer */
.instruction-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 16px;
 
}

.instruction-modal-cancel-btn {
  padding: 8px 16px;
  color: #374151;
  background-color: #f3f4f6;
 
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
}

.instruction-modal-cancel-btn:hover {
  background-color: #e5e7eb;
}

.instruction-modal-save-btn {
  padding: 8px 16px;
  color: white;
  background-color: #257556;

  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
}

.instruction-modal-save-btn:hover {
  background-color: #1d5e45;
}

/* Quill editor styling - unique classes */
.instruction-modal-content .ql-editor {
  min-height: 200px;
  max-height: 200px;
  overflow-y: auto;
  color: black !important;
}

.instruction-modal-content .ql-bubble .ql-editor {

  padding: 16px;
}

/* removed empty ruleset to satisfy linter */

/* Fix Quill bubble toolbar positioning */
.instruction-modal-content .ql-bubble .ql-tooltip {
  z-index: 10000 !important;
  position: absolute !important;
}

.instruction-modal-content .ql-bubble .ql-tooltip[data-mode="link"]::before {
  content: "Enter link:";
}

.instruction-modal-content .ql-bubble .ql-tooltip a.ql-action,
.instruction-modal-content .ql-bubble .ql-tooltip a.ql-remove {
  color: #257556;
}

.instruction-modal-content .ql-bubble .ql-tooltip a.ql-action:hover,
.instruction-modal-content .ql-bubble .ql-tooltip a.ql-remove:hover {
  color: #1d5e45;
}

/* Ensure toolbar appears above modal */
.instruction-modal-content .ql-bubble .ql-tooltip {
  background: rgb(99, 96, 96) !important;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
  padding: 8px 12px !important;
}

/* Fix any positioning issues with the editor container */
.instruction-modal-content .ql-bubble {
  position: relative;
}

.instruction-modal-content .ql-bubble .ql-editor {
  position: relative;
  z-index: 1;
}

/* Button hover effects */
.instruction-modal-container button:hover {
  transform: translateY(-1px);
}

/* Smooth transitions */
.instruction-modal-container * {
  transition: all 0.2s ease-in-out;
}
</style>