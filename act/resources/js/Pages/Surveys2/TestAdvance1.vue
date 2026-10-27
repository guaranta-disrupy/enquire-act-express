<script setup>
// Import CK Editor Components
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import CKeditor from "@ckeditor/ckeditor5-vue";
// import Font from '@ckeditor/ckeditor5-font/src/font';
import UploadAdapterPlugin from "@/Functions/UploadAdapter";
import { PrismEditor } from "vue-prism-editor";
import "vue-prism-editor/dist/prismeditor.min.css"; // import the styles somewhere
// import highlighting library (you can use any library you want just return html string)
import { highlight, languages } from "prismjs/components/prism-core";
import "prismjs/components/prism-clike";
import "prismjs/components/prism-javascript";
import "prismjs/components/prism-markup";
import "prismjs/components/prism-css";
import "prismjs/themes/prism-tomorrow.css"; // import syntax highlighting styles
// Ck Editor end

import CustomSwitch from "@/Components/Switch.vue";
import JetLabel from "@/Jetstream/Label.vue";
import JetCheckbox from "@/Jetstream/Checkbox.vue";
import CustomInput from "@/Components/Input.vue";
import { inject, ref, watch, provide, onBeforeMount } from "vue";
import JetButton from "@/Componentsv2/Button.vue";

import JetDialogModal from "@/Jetstream/DialogModal.vue";
import Toggle from "@/Jetstream/Toggle.vue";

import Helpers from "@/Mixins/Helpers";

import TestCriteria from "./TestCriteria.vue";

// import QuillEditor from '@/Extra/Quill.vue'
// import 'quill/dist/quill.snow.css';

const props = defineProps({
  survey: {
    type: Object,
    default: () => ({})
  },
  selectedTrialMode: {
    type: String,
    default: null
  }
});

const emit = defineEmits(['trialModeChanged']);

const { $testState, assign } = Helpers();

const enableCKEditor = inject("ck");
const ckeditor = CKeditor.component;
const editor = ref(ClassicEditor);

const form = inject("form");

const editorConfig = ref({
  extraPlugins: [UploadAdapterPlugin],
});

// Add new state for trial mode selection
const showTrialModeModal = ref(!props.selectedTrialMode);
const selectedTrialMode = ref(props.selectedTrialMode);

const addSegment = () => {
  // $state.criteria = assign(form);
  return $testState.addSegment();
};

onBeforeMount(() => {
  $testState.criteria = form;
  
  // If a trial mode was passed from the parent, set the configuration
  if (props.selectedTrialMode) {
    if (props.selectedTrialMode === 'descriptive') {
      form.config.practice_trial = false;
      form.config.main_trial = false;
    } else if (props.selectedTrialMode === 'practice') {
      form.config.practice_trial = true;
      form.config.main_trial = false;
    } else if (props.selectedTrialMode === 'main') {
      form.config.practice_trial = false;
      form.config.main_trial = true;
    }
  }
  
  // $testState.criteria = {
  //     config: form.config
  // }
  //console.log($state.criteria)
});

provide("testState", $testState);

// const attributors = ref([
//     // 'class/background',
//     // 'class/color',
//     'style/size',
//     'style/align',
// ]);

// const toolbar = [
//     ['bold', 'italic', 'underline', 'strike'],
//     ['blockquote', 'code-block'],
//     [{ header: 1 }, { header: 2 }],
//     [{ list: 'ordered' }, { list: 'bullet' }],
//     [{ script: 'sub' }, { script: 'super' }],
//     [{ direction: 'rtl' }],
//     [{ header: [1, 2, 3, 4, 5, 6, false] }],
//     [{ color: [] }, { background: [] }],
//     [{ align: [] }],
//     ['image'],
//     ['clean'], // remove formatting button
// ];

const highlighter = (code) => {
  return highlight(code, languages.markup);
};

// Function to handle trial mode selection
const selectTrialMode = (mode) => {
  selectedTrialMode.value = mode;
  showTrialModeModal.value = false;
  
  // Set the appropriate trial configuration based on selection
  if (mode === 'descriptive') {
    form.config.practice_trial = false;
    form.config.main_trial = false;
  } else if (mode === 'practice') {
    form.config.practice_trial = true;
    form.config.main_trial = false;
  } else if (mode === 'main') {
    form.config.practice_trial = false;
    form.config.main_trial = true;
  }
  
  // Emit the trial mode change to parent component
  emit('trialModeChanged', mode);
};

watch(form, (newVal) => {
  if (newVal.config.random_prime === false) {
    form.config.random_prime_subsets = false;
  }
});

// Watch for main trial changes to automatically disable practice trial
watch(() => form.config.main_trial, (newVal) => {
  if (newVal === true) {
    form.config.practice_trial = false;
  }
});

// Watch for practice trial changes to automatically disable main trial
watch(() => form.config.practice_trial, (newVal) => {
  if (newVal === true) {
    form.config.main_trial = false;
  }
});

// Watch for selectedTrialMode prop changes to update form configuration
watch(() => props.selectedTrialMode, (newMode) => {
  if (newMode) {
    selectedTrialMode.value = newMode;
    
    // Update the form configuration based on the new trial mode
    if (newMode === 'descriptive') {
      form.config.practice_trial = false;
      form.config.main_trial = false;
    } else if (newMode === 'practice') {
      form.config.practice_trial = true;
      form.config.main_trial = false;
    } else if (newMode === 'main') {
      form.config.practice_trial = false;
      form.config.main_trial = true;
    }
  }
});


// watch(() => (form.config.main_header), (new_val) => {
//     nextTick(() => {
//         form.config.main_header = new_val.replace(/&nbsp;/g, ' ');
//     })

// });

const macroToBeCreatedOrUpdated = ref(false);
const selectedMacro = ref(false);

const macro = ref({
  placeholder: null,
  formula: "",
  type: "tabular",
});

const saveMacro = () => {
  if (selectedMacro.value === false) {
    form.config.macros.push(assign(macro.value));
  } else {
    form.config.macros[selectedMacro.value] = assign(macro.value);
  }
  macroToBeCreatedOrUpdated.value = false;
  selectedMacro.value = false;
};

const editMacro = (key) => {
  selectedMacro.value = key;
  macroToBeCreatedOrUpdated.value = form.config.macros[key];
  macro.value.placeholder = macroToBeCreatedOrUpdated.value.placeholder;
  macro.value.formula = macroToBeCreatedOrUpdated.value.formula;
  macro.value.type = macroToBeCreatedOrUpdated.value.type;
};

const deleteMacro = (key) => {
  form.config.macros.splice(key, 1);
};

// Handle display_by change event
const handleDisplayByChange = (value) => {
  // Ensure the value is a valid number, but allow values less than 1
  const numValue = parseInt(value);
  if (!isNaN(numValue)) {
    form.config.display_by = numValue;
  }
};

const selectedDisplayStyle = ref('rounded'); // Default to rounded targets

const selectDisplayStyle = (style) => {
    selectedDisplayStyle.value = style;
    form.value.config.display_style = style;
};
</script>

<template>
  <!-- Trial Mode Selection Modal -->
  <JetDialogModal
    :show="showTrialModeModal"
    @close="showTrialModeModal = false"
    :closeable="true"
  >
    <template #title>Select Trial Mode</template>
    <template #content>
      <div class="space-y-4">
        <p class="text-gray-600 text-sm">
          Choose the type of trial you want to create:
        </p>
        
        <div class="grid grid-cols-1 gap-3">
          <button
            @click="selectTrialMode('descriptive')"
            class="p-4 text-left border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-colors"
          >
            <div class="font-semibold text-green-600">Descriptive Trial</div>
            <div class="text-sm text-gray-600 mt-1">
              Create a descriptive trial with only a title. No interactive elements.
            </div>
          </button>
          
          <button
            @click="selectTrialMode('practice')"
            class="p-4 text-left border-2 border-gray-200 rounded-lg hover:border-red-500 hover:bg-red-50 transition-colors"
          >
            <div class="font-semibold text-red-600">Practice Trial</div>
            <div class="text-sm text-gray-600 mt-1">
              Create a practice trial with full configuration options and interactive elements.
            </div>
          </button>
          
          <button
            @click="selectTrialMode('main')"
            class="p-4 text-left border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors"
          >
            <div class="font-semibold text-blue-600">Main Trial</div>
            <div class="text-sm text-gray-600 mt-1">
              Create a main trial with full configuration options and interactive elements.
            </div>
          </button>
        </div>
      </div>
    </template>

    <template #footer>
      <JetButton
        variant="default"
        @click="showTrialModeModal = false"
        size="sm"
      >
        Close
      </JetButton>
    </template>
  </JetDialogModal>

  <!-- Main Content (only shown after mode selection) -->
  <div v-if="selectedTrialMode" class="grid grid-cols-8 gap-3 p-4 overflow-x-hidden overflow-y-auto c">
    <div class="col-span-8 flex justify-between items-center">
      <!-- <CustomSwitch v-model:checked="enableCKEditor"> Editor </CustomSwitch> -->
      <div class="flex items-center gap-2">
        <!-- <jet-label>
          <div class="flex items-center">
            <Toggle v-model="enableCKEditor" />
            <div class="ml-2">Editor</div>
          </div>
        </jet-label> -->

        <!-- <jet-label v-if="selectedTrialMode === 'descriptive'">
          <div class="flex items-center">
            <Toggle  />
            <div class="ml-2">Dummy round</div>
          </div>
        </jet-label> -->
      </div>
      <!-- Current Trial Mode Indicator and Change Button -->
      <div class="flex items-center gap-3">
        <!-- Current Trial Mode Badge -->
        <!-- <div class="flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium" 
             :class="{
               'bg-green-100 text-green-800 border border-green-200': selectedTrialMode === 'descriptive',
               'bg-red-100 text-red-800 border border-red-200': selectedTrialMode === 'practice',
               'bg-blue-100 text-blue-800 border border-blue-200': selectedTrialMode === 'main'
             }">
          <svg v-if="selectedTrialMode === 'descriptive'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <svg v-else-if="selectedTrialMode === 'practice'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
          </svg>
          <svg v-else-if="selectedTrialMode === 'main'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2zm0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
          </svg>
          <span class="capitalize">{{ selectedTrialMode }} Trial</span>
        </div> -->
        
        <!-- Change Trial Mode Button -->
        <!-- <JetButton
          type="button"
          variant="default"
          size="sm"
          @click="showTrialModeModal = true"
          class="flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
          Change
        </JetButton> -->
      </div>
    </div>


    <!-- Main Header and Bottom Text - Only shown for practice/main trials -->
    <template v-if="selectedTrialMode !== 'descriptive'">
      <!-- <div class="col-span-4">
        <JetLabel value="Main Header" />
        <div v-if="enableCKEditor">
          <ckeditor
            :editor="editor"
            v-model="form.config.main_header"
            :config="editorConfig"
          ></ckeditor>
        </div>
        <prism-editor
          v-else
          class="my-editor editor-height"
          v-model="form.config.main_header"
          :highlight="highlighter"
          line-numbers
        ></prism-editor>
      </div>

      <div class="col-span-4">
        <JetLabel value="Bottom text" />
        <div v-if="enableCKEditor">
          <ckeditor
            :editor="editor"
            v-model="form.config.bottom_text"
            :config="editorConfig"
          ></ckeditor>
        </div>
        <PrismEditor
          v-else
          class="my-editor editor-height"
          v-model="form.config.bottom_text"
          :highlight="highlighter"
          line-numbers
        />
      </div> -->

      <!-- Toggle Buttons and Configuration - Only shown for practice/main trials -->
      <div class="col-span-8">
  <!-- <div class="flex flex-row w-full">
    <div class=" w-full">
      
    <div class="flex flex-row gap-2 mb-2 items-center">
        <svg width="20" height="20" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g clip-path="url(#clip0_621_14998)">
            <path d="M41.1415 0H8.8584C3.96572 0 0 3.96582 0 8.85772V15.625H50V8.85772C50 3.96582 46.0342 0 41.1415 0Z" fill="#257556"/>
            <path d="M0 41.1423C0 46.0342 3.96572 50 8.8584 50H28.9062V21.0938H0V41.1423Z" fill="#257556"/>
            <path d="M34.375 50H41.1415C46.0342 50 50 46.0342 50 41.1423V21.0938H34.375V50Z" fill="#257556"/>
            </g>
            <defs>
            <clipPath id="clip0_621_14998">
            <rect width="50" height="50" fill="white"/>
            </clipPath>
            </defs>
            </svg>
        <h2 class="text-lg font-bold text-[#323232]"> 
            Select Display Layout
        </h2>
    </div>

        <div class="flex flex-row gap-2 w-full">
        <div  class="w-full px-4 py-2 border-2 cursor-pointer text-center justify-center items-center shadow-lg flex transition-colors rounded-lg"
              :class="selectedDisplayStyle === 'rounded' ? 'border-[#257556] bg-[#257556]/10' : 'border-gray-100 hover:border-[#257556]'"
              @click="selectDisplayStyle('rounded')">
          <button1 class="flex flex-col items-center justify-center gap-2 font-extrabold text-center">
            Rounded Targets

            <svg width="82" height="82" viewBox="0 0 82 82" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="9.9082" y="11.9766" width="58.1836" height="58.1836" rx="29.0918" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="30.9688" y="2.5" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="2.53613" y="18.7158" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="2.53613" y="46.3066" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="30.9688" y="62.5225" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="59.4043" y="46.3066" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="59.4043" y="18.7158" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            </svg>
          </button1>
        </div>

        <div  class="w-full px-4 py-2 border-2 cursor-pointer text-center justify-center items-center shadow-lg flex transition-colors rounded-lg"
              :class="selectedDisplayStyle === 'outside' ? 'border-[#257556] bg-[#257556]/10' : 'border-gray-100 hover:border-[#257556]'"
              @click="selectDisplayStyle('outside')">
          <button1 class="flex flex-col items-center justify-center gap-2 font-extrabold text-center">
            Outside Targets

            <svg width="110" height="85" viewBox="0 0 110 85" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="26.499" y="2.5" width="57.2673" height="57.2673" rx="28.6336" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="2.50098" y="29.8135" width="15.7554" height="15.7554" rx="7.87769" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="9.67188" y="54.5146" width="15.7554" height="15.7554" rx="7.87769" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="33.5771" y="65.6699" width="15.7554" height="15.7554" rx="7.87769" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="59.207" y="65.8594" width="15.7554" height="15.7554" rx="7.87769" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="83.7764" y="54.5146" width="15.7554" height="15.7554" rx="7.87769" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            <rect x="91.7441" y="29.8135" width="15.7554" height="15.7554" rx="7.87769" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
            </svg>
          </button1>
        </div>

        <div  class="w-full px-4 py-2 border-2 cursor-pointer text-center justify-center items-center shadow-lg flex transition-colors rounded-lg"
              :class="selectedDisplayStyle === 'box' ? 'border-[#257556] bg-[#257556]/10' : 'border-gray-100 hover:border-[#257556]'"
              @click="selectDisplayStyle('box')">
          <button1 class="flex flex-col items-center justify-center gap-2 font-extrabold text-center">
            Box Targets

            <svg width="144" height="80" viewBox="0 0 144 80" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="38.25" y="4.25" width="69.5" height="8.5" rx="4.25" fill="#D9D9D9" stroke="#ADADAD" stroke-width="8.5"/>
            <rect x="1.5" y="26" width="43" height="24" rx="2" fill="#D9D9D9" stroke="#ADADAD" stroke-width="2"/>
            <rect x="1.5" y="55" width="43" height="24" rx="2" fill="#D9D9D9" stroke="#ADADAD" stroke-width="2"/>
            <rect x="50.5" y="27" width="43" height="24" rx="2" fill="#D9D9D9" stroke="#ADADAD" stroke-width="2"/>
            <rect x="50.5" y="55" width="43" height="24" rx="1" fill="#D9D9D9" stroke="#ADADAD" stroke-width="2"/>
            <rect x="99.5" y="55" width="43" height="24" rx="1" fill="#D9D9D9" stroke="#ADADAD" stroke-width="2"/>
            <mask id="path-8-inside-1_312_11230" fill="white">
            <rect x="98.5" y="26" width="45" height="26" rx="1"/>
            </mask>
            <rect x="98.5" y="26" width="45" height="26" rx="1" fill="#D9D9D9" stroke="#ADADAD" stroke-width="4" mask="url(#path-8-inside-1_312_11230)"/>
            </svg>
          </button1>
        </div>
    </div>
</div>
</div> -->

<!-- 
<div>
        <div class="flex flex-row gap-36">
          <div class="">
            <jet-label class="mt-3">
              <div class="flex items-center">
                <Toggle v-model="form.config.random" />
                <div class="ml-2">Randomize targets</div>
              </div>
            </jet-label>

            <jet-label class="mt-3">
              <div class="flex items-center">
                <Toggle v-model="form.config.random_prime" />
                <div class="ml-2">Randomize primes</div>
              </div>
            </jet-label>
          </div>

          <div class="">
            <jet-label class="mt-3">
              <div class="flex items-center">
                <Toggle v-model="form.config.random_prime_subsets" :disabled="!form.config.random_prime" />
                <div class="ml-2">Randomize prime subsets</div>
              </div>
            </jet-label>

            <jet-label class="mt-3">
              <div class="flex items-center">
                <Toggle v-model="form.config.prime_as_next" />
                <div class="ml-2">Prime as next button</div>
              </div>
            </jet-label>
          </div>

          <div class="">
            <jet-label class="mt-3">
              <div class="flex items-center">
                <Toggle v-model="form.config.single_select" />
                <div class="ml-2">Single select</div>
              </div>
            </jet-label>

            <jet-label class="mt-3">
              <div class="flex items-center">
                <Toggle v-model="form.config.auto_next" />
                <div class="ml-2">Auto next trial</div>
              </div>
            </jet-label>
          </div>

          <div class="">
          <jet-label class="mt-3">
              <div class="flex items-center">
                <Toggle v-model="form.config.trial_unselect" />
                <div class="ml-2">Trial unselect</div>
              </div>
            </jet-label>
            </div>
      </div>
    


        <div class="flex flex-row gap-4 items-end mt-6">

          <div class="w-full flex flex-col gap-4">
            <div class="w-full" v-if="form.config.trial_unselect">
            <CustomInput
              label="Trial unselect label"
              v-model="form.config.trial_unselect_label"
            />
          </div>

          <div class="w-full">
            <CustomInput
              label="Empty selection text"
              v-model="form.config.empty_selection"
            />
          </div>
        </div>

          <div class="w-full flex flex-col gap-4">
            <div class="w-full">
              <CustomInput
                type="number"
                label="Selection limit (0 = can select all)"
                v-model="form.config.limit"
              />
            </div>
            <div class="w-full">
              <CustomInput
                type="number"
                label="Display targets by"
                v-model="form.config.display_by"
                @change="handleDisplayByChange"
              />
            </div>
          </div>

<div class="w-full flex flex-col gap-4">
    <div class="w-full">
      <CustomInput 
      label="Target Delay (ms)"
        type="number" 
        v-model="form.config.target_delay" 
      />
    </div>

    <div class="w-full">
        <CustomInput 
        label="Limit reach"
          type="text" 
          v-model="form.config.limit_reach" 
        />
      </div>
</div>


    </div>
 </div> -->

</div>

      <div class="hidden col-span-8">
        <div class="block ">Responses settings</div>
        <div class="w-1/4">
          <jet-label class="mt-3">
            <div class="flex items-center">
              <jet-checkbox v-model:checked="form.config.download_as_values" />
              <div class="ml-2">Download responses as values</div>
            </div>
          </jet-label>
        </div>
      </div>

    </template>

    <!-- Response Recoding - Shown for all trial modes -->
    <div class="col-span-8">
      <div class="block ">Response recoding</div>
      <div class="mb-2">
        <JetButton
          type="button"
          variant="rose"
          size="xs"
          @click.prevent="macroToBeCreatedOrUpdated = true"
        >
          Add macro code
        </JetButton>
      </div>
      <div>
        <div
          v-for="(mc, k) in form.config.macros"
          :key="k"
          class="flex items-center p-2 my-1 text-xs border rounded-lg bg-slate-100"
        >
          <div class="w-4/12">
            {{ mc.placeholder }}
          </div>
          <div class="w-5/12 font-mono">
            {{ mc.formula }}
          </div>
          <div class="w-1/12">
            <div
              class="flex items-center p-1 text-gray-500 bg-white border rounded-full"
            >
              <div class="mx-auto">
                <span
                  v-if="mc.type == 'tabular'"
                  class="text-xs font-semibold"
                >
                  Tabular
                </span>
                <span v-else class="text-xs font-semibold"> Respondent </span>
              </div>
            </div>
          </div>
          <div class="flex items-center justify-end w-2/12 gap-1">
            <JetButton
              variant="orange"
              size="xs"
              @click.prevent="editMacro(k)"
            >
              Update
            </JetButton>
            <JetButton
              variant="orange"
              size="xs"
              @click.prevent="deleteMacro(k)"
            >
              Delete
            </JetButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Skip Logic - Shown for all trial modes -->
    <div class="col-span-8 mt-5">
      Skip logic
      <div class="mb-2">
        <JetButton
          type="button"
          variant="rose"
          size="xs"
          @click.prevent="addSegment"
        >
          <span v-if="form.config.conditions.length > 0">Add Segment</span
          ><span v-else>Add Condition</span>
        </JetButton>
      </div>
      <div
        v-for="(condition, index) in form.config.conditions"
        :key="index"
        class="relative"
      >
        <TestCriteria :deep="0" :condition="condition" :indexes="[index]" />
      </div>
      <div class="mt-3">
        <code class="text-xs bg-gray-200 text-black-500">{{
          $testState.criteriaToString(form.config.conditions)
        }}</code>
      </div>
    </div>
  </div>

  <!-- Macro Modal -->
  <JetDialogModal
    :show="macroToBeCreatedOrUpdated"
    @close="macroToBeCreatedOrUpdated = false"
  >
    <template #title> Add macro code </template>

    <template #content>
      <div class="flex gap-2">
        <div class="w-full p-2">
          <CustomInput
            type="text"
            label="Placeholder"
            v-model="macro.placeholder"
          />
        </div>
      </div>
      <div class="flex gap-2">
        <div class="w-full p-2">
          <JetLabel value="Applied to" />
          <select
            v-model="macro.type"
            class="block w-full p-2 mt-1 text-xs border-gray-300 rounded-lg form-select dark:bg-gray-900 dark:border-gray-500"
          >
            <option value="tabular">Tabular</option>
            <option value="respondent">Respondent Level</option>
          </select>
        </div>
      </div>
      <div class="flex gap-2">
        <div class="w-full p-2">
          <PrismEditor
            class="my-editor editor-height"
            v-model="macro.formula"
            :highlight="highlighter"
            line-numbers
          />
          <div class="mt-2">
            <p class="text-sm text-gray-500">
              Use the following syntax:
              <span
                class="p-0.5 px-2 rounded-lg bg-teal-600 font-mono font-semibold text-white"
                >t1</span
              >
              = Target 1,
              <span
                class="p-0.5 px-2 rounded-lg bg-teal-600 font-mono font-semibold text-white"
                >t2</span
              >
              for Target 2 and so on
            </p>
          </div>
        </div>
      </div>
    </template>

    <template #footer>
      <JetButton
        variant="default"
        class="uppercase"
        @click="macroToBeCreatedOrUpdated = false"
        size="sm"
      >
        nevermind
      </JetButton>

      <jet-button
        @click.prevent="saveMacro"
        size="sm"
        type="button"
        class="ml-2 uppercase"
        variant="primary"
        :class="{ 'opacity-25': form.processing }"
        :disabled="form.processing"
      >
        Save changes
      </jet-button>
    </template>
  </JetDialogModal>
</template>

<style scoped="true">
.c {
  max-height: calc(100vh - 214px);
}

.ck-editor__editable_inline {
    min-height: 100px;
}

.ck.ck-toolbar {
    background-color: #f8fafc; /* Light gray */
    border-radius: 8px;
    padding: 5px;
}

.ck.ck-button__label {
    font-size: 12px;
}

:deep(.ck-editor__editable_inline) {
	min-height: 100px;
}

:deep(.ck.ck-toolbar) {
	background-color: #f3f4f6;
	border-radius: 6px;
}
</style>