import { onMounted, watch } from 'vue';
import { useDataStore } from '@/stores/data';

/**
 * Composable for automatic form synchronization
 * 
 * This composable provides automatic synchronization of form data with default values.
 * It ensures that all required fields are present and properly initialized.
 * 
 * Usage:
 * ```javascript
 * import { useFormSync } from '@/Composables/useFormSync';
 * 
 * const { syncForm, syncOnChange, initializeForm } = useFormSync();
 * 
 * // Initialize form on mount
 * onMounted(() => {
 *   initializeForm();
 *   syncOnChange(form);
 * });
 * ```
 */
export function useFormSync() {
  const dataStore = useDataStore();

  // Auto-sync form data on mount
  onMounted(() => {
    dataStore.ensureFormSync();
  });

  // Auto-sync when form data changes
  const syncOnChange = (form) => {
    watch(form, () => {
      dataStore.syncFormData();
    }, { deep: true });
  };

  // Manual sync function
  const syncForm = () => {
    return dataStore.syncFormData();
  };

  // Initialize form with defaults
  const initializeForm = () => {
    return dataStore.initializeForm();
  };

  return {
    syncForm,
    syncOnChange,
    initializeForm,
    ensureFormSync: () => dataStore.ensureFormSync()
  };
}
