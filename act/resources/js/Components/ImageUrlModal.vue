<template>
  <div v-if="show" class="image-url-modal-overlay" @click="closeModal">
    <div class="image-url-modal-container" @click.stop>
      <div class="image-url-modal-header">
        <h3 class="image-url-modal-title">Insert Image</h3>
        <button @click="closeModal" class="image-url-modal-close-btn">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      
      <div class="image-url-modal-content">
        <div class="form-group">
          <label for="imageUrl" class="form-label">Image URL:</label>
          <input
            id="imageUrl"
            v-model="imageUrl"
            type="url"
            placeholder="https://example.com/image.jpg"
            class="form-input"
            @keyup.enter="insertImage"
          />
        </div>
        
        <div v-if="imageUrl" class="image-preview">
          <p class="preview-label">Preview:</p>
          <img :src="imageUrl" alt="Preview" class="preview-image" @error="handleImageError" />
        </div>
        
        <div v-if="errorMessage" class="error-message">
          {{ errorMessage }}
        </div>
      </div>
      
      <div class="image-url-modal-footer">
        <button @click="closeModal" class="cancel-btn">
          Cancel
        </button>
        <button @click="insertImage" class="insert-btn" :disabled="!imageUrl">
          Insert Image
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'insert']);

const imageUrl = ref('');
const errorMessage = ref('');

const closeModal = () => {
  imageUrl.value = '';
  errorMessage.value = '';
  emit('close');
};

const insertImage = () => {
  if (imageUrl.value) {
    emit('insert', imageUrl.value);
    closeModal();
  }
};

const handleImageError = () => {
  errorMessage.value = 'Unable to load image. Please check the URL.';
};

watch(() => props.show, (newVal) => {
  if (newVal) {
    imageUrl.value = '';
    errorMessage.value = '';
  }
});
</script>

<style scoped>
.image-url-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 10000;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgba(0, 0, 0, 0.5);
}

.image-url-modal-container {
  background-color: white;
  border-radius: 12px;
  padding: 24px;
  width: 500px;
  max-width: 90vw;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.image-url-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.image-url-modal-title {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.image-url-modal-close-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  color: #6b7280;
  transition: color 0.2s;
}

.image-url-modal-close-btn:hover {
  color: #374151;
}

.image-url-modal-content {
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 16px;
}

.form-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  margin-bottom: 8px;
}

.form-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.image-preview {
  margin-top: 16px;
  padding: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background-color: #f9fafb;
}

.preview-label {
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  margin: 0 0 8px 0;
}

.preview-image {
  max-width: 100%;
  max-height: 200px;
  border-radius: 4px;
  display: block;
}

.error-message {
  margin-top: 12px;
  padding: 8px 12px;
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 6px;
  color: #dc2626;
  font-size: 14px;
}

.image-url-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.cancel-btn {
  padding: 8px 16px;
  background-color: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  color: #374151;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.cancel-btn:hover {
  background-color: #e5e7eb;
}

.insert-btn {
  padding: 8px 16px;
  background-color: #3b82f6;
  border: 1px solid #3b82f6;
  border-radius: 6px;
  color: white;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.insert-btn:hover:not(:disabled) {
  background-color: #2563eb;
}

.insert-btn:disabled {
  background-color: #9ca3af;
  border-color: #9ca3af;
  cursor: not-allowed;
}
</style>
