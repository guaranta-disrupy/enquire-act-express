<script setup>
// Import CK Editor Components
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import CKEditor from '@ckeditor/ckeditor5-vue';
import UploadAdapterPlugin from '@/Functions/UploadAdapter';
import { PrismEditor } from 'vue-prism-editor';
import 'vue-prism-editor/dist/prismeditor.min.css'; // import the styles somewhere
// import highlighting library (you can use any library you want just return html string)
import { highlight, languages } from 'prismjs/components/prism-core';
import 'prismjs/components/prism-clike';
import 'prismjs/components/prism-javascript';
import 'prismjs/components/prism-markup';
import 'prismjs/components/prism-css';
import 'prismjs/themes/prism-tomorrow.css'; // import syntax highlighting styles
// Ck Editor end

import { inject, ref, provide, onBeforeMount,computed } from 'vue';
import CustomSwitch from '@/Components/Switch.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimeCard from '@/Components/PrimeCard.vue';
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import JetButton from '@/Components/Button.vue';
import CriteriaContents from './Criteria.vue';
import CustomInput from '@/Components/Input.vue';
import TextArea from '@/Components/TextArea.vue';

import Helpers from '@/Mixins/Helpers';

import GalleryCard from '@/Components/GalleryCard.vue';

import maxBy from 'lodash/maxBy';
import forEach from 'lodash/forEach'


const { sanitize, capitalize, hasImage, $state, assign } = Helpers();

provide('state', $state);

const enableCK = inject('ck');
const ckeditor = CKEditor.component;
const editor = ref(ClassicEditor);
const selectedIndex = ref(false);

const form = inject('form');

const editorConfig = ref({
	extraPlugins: [UploadAdapterPlugin]
});
const modals = ref({
	createPrime: false
});
const _prime = ref({
	prime: '',
	equivalent: '',
	config: {
		conditions: []
	}
});

onBeforeMount(() => {
	$state.criteria = _prime.value;
});

const highlighter = (code) => {
	return highlight(code, languages.markup);
};

const unformatContent = (t, val = null) => {
	if (t == 1) {
		if (hasImage(_prime.value.prime)) return
		_prime.value.equivalent = sanitize(_prime.value.prime).split(' i ').join(' I ')
	} else {
		return sanitize(val).split(' i ').join(' I ')
	}
};

const addSegment = () => {
	return $state.addSegment();
};

// const addPrimeList = () => {
// 	let primes = assign(_prime.value.prime.split('\n'));
// 	let prime_length = getMaxIndex(form.primes);
// 	let eqs = assign(_prime.value.equivalent.split('\n'));
// 	forEach (primes, (p, i) => {
// 		prime_length++;
// 		let prime = {
// 			prime: p,
// 			equivalent: _prime.value.equivalent ? (eqs[i] ?? unformatContent(3, p)) : unformatContent(3, p),
// 			index: prime_length,
// 			config: assign(_prime.value.config)
// 		}
// 		form.primes.push(prime)
// 	});
// 	resetPrimeForm();
// };

const addPrimeList = () => {
  let primes = assign(_prime.value.prime.split('\n'));
  let prime_length = getMaxIndex(form.primes);
  let eqs = assign(_prime.value.equivalent.split('\n'));

  forEach(primes, (p, i) => {
    prime_length++;
    let prime = {
      prime: p,
      equivalent: _prime.value.equivalent ? (eqs[i] ?? unformatContent(3, p)) : unformatContent(3, p),
      index: prime_length,
      config: assign(_prime.value.config),
      selected: false,              // <── add this
    };
    form.primes.push(prime);
  });

  resetPrimeForm();
};


const resetPrimeForm = () => {
	_prime.value = {
		prime: '',
		equivalent: '',
		config: {
			conditions: []
		}
	};
	selectedIndex.value = false;
	modals.value.createPrime = false;
};

const addPrimeDialog = () => {
	resetPrimeForm();
	$state.criteria = _prime.value;
	modals.value.createPrime = true;
};

const getMaxIndex = (values) => {
	return maxBy(values, 'index')?.index ?? 0;
};

const deletePrime = (index) => {
	form.primes.splice(index, 1)
};

const updatePrimeDialog = (index) => {
	selectedIndex.value = index;
	_prime.value = assign(form.primes[index-1]);
	$state.criteria = _prime.value;
	modals.value.createPrime = true;
};

const updatePrimeList = () => {
	form.primes[selectedIndex.value-1] = _prime.value;
	resetPrimeForm();
};

const primeAction = () => {
	if (selectedIndex.value) {
		// update
		updatePrimeList();
	} else {
		addPrimeList();
	}
};

const showButtons = ref(false);

// const toggleButtons = () => {
//   showButtons.value = !showButtons.value;
// };

form.primes = form.primes.map(p => ({
  ...p,
  showButtons: false
}));

const toggleButtons = (index) => {
  form.primes.forEach((p, i) => {
    p.showButtons = i === index ? !p.showButtons : false;
  });
};

// select and delete button function start
onBeforeMount(() => {
  $state.criteria = _prime.value;
  form.primes.forEach(p => {
    if (p.selected === undefined) p.selected = false;
  });
});

const allSelected = computed(() =>
  form.primes.length > 0 && form.primes.every(p => p.selected)
);

const anySelected = computed(() =>
  form.primes.some(p => p.selected)
);


const toggleSelectAll = () => {
  const val = !allSelected.value;
  form.primes.forEach(p => (p.selected = val));
};

const deleteSelected = () => {
  for (let i = form.primes.length - 1; i >= 0; i--) {
    if (form.primes[i].selected) form.primes.splice(i, 1);
  }
};
// select and delete button function End
const isExpanded = ref(false);

const toggleExpand = () => {
  isExpanded.value = !isExpanded.value;
};
</script>
<template>
	
	<div :class="['flex flex-col transition-all duration-300', isExpanded ? 'w-[160vh]' : 'w-[40vh]']">
		<div class="bg-[#257556] shadow p-4 mb-2 w-[full] justify-between flex flex-row">
			<div class="text-white text-[20px] font-bold ">
				Gallery
			</div>

			<!-- Select and Delete All button -->
			<div class="flex flex-row gap-2">

				<!-- Select Button -->
				<button class="px-3 py-1 font-bold bg-white rounded-full"
						@click="toggleSelectAll">
					{{ allSelected ? 'Unselect All' : 'Select All' }}
				</button>
				<!-- Delete Button -->
				<button class="px-3 py-1 font-bold bg-white rounded-full"
						:class="{'opacity-50 cursor-not-allowed': !anySelected}"
						:disabled="!anySelected"
						@click="deleteSelected">
					Delete
				</button>

				<!-- Expand and Shrink Button -->
				<!-- <Button1 class="px-3 py-1 font-bold bg-white rounded-full " @click="toggleExpand">
					
					{{ isExpanded ? 'Shrink' : 'Expand' }}
				</Button1> -->
			</div>

		</div>
		
	<!-- Expand and Shrink Button -->
			 <div class=" rounded-full px-2 font-bold flex justify-end bottom-[-48.50rem] relative right-[-1.5rem] shadow-lg">
				<button
				class="bg-[#257556] rounded-lg py-[28px] absolute bottom-[25.25rem]  shadow-lg"
				@click="toggleExpand"
				>
				<!-- SVG rotates when expanded -->
				<svg
					:class="isExpanded ? 'rotate-180 transition-transform duration-300' : 'transition-transform duration-300'"
					width="30"
					height="30"
					viewBox="0 0 55 50"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
				>
					<path
					d="M27.2578 40.4296L43.1875 26.7793C43.4441 26.5592 43.6501 26.2863 43.7914 25.9792C43.9326 25.672 44.0057 25.338 44.0057 25C44.0057 24.6619 43.9326 24.3279 43.7914 24.0207C43.6501 23.7136 43.4441 23.4407 43.1875 23.2207L27.2578 9.57026C25.7373 8.26753 23.3887 9.34761 23.3887 11.3496V38.6543C23.3887 40.6562 25.7373 41.7363 27.2578 40.4296Z"
					fill="white"
					/>
					<rect x="12" y="9" width="5" height="32" rx="2.5" fill="white" />
				</svg>
				</button>

		    </div>

		
		<div :class="['m-2 c overflow-y-auto custom-scrollbar overflow-x-hidden transition-all duration-300', isExpanded ? 'grid grid-cols-4' : 'grid grid-cols-1']" v-if="form.primes.length">

			<div class="text-center">
			  <button
					@click="addPrimeDialog"
					class="group uppercase m-2 shadow flex flex-col border border-gray-300  h-[237px] rounded-lg justify-center items-center text-[#323232] hover:bg-[#257556] hover:text-white font-bold"
				>
					<svg
					width="80"
					height="80"
					viewBox="0 0 80 80"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
					>
					<path
						d="M40 17.5V62.5M62.5 40H17.5"
						stroke="#323232"
						stroke-width="8"
						stroke-linecap="round"
						stroke-linejoin="round"
						class="transition-colors duration-200 group-hover:stroke-white"
					/>
					</svg>
					Add Image
				</button>
			</div>

			<GalleryCard v-for="(prime, i) in form.primes" :key="i" class="col-span-3 m-2 md:col-span-1">

				<!-- Radio and Setting button -->
				<template #actions>
					<!-- Radio Button -->
					<div class="checkbox-container">
					<label class="ios-checkbox blue">
						<input type="checkbox" v-model="prime.selected" />
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
					
					<div class="relative flex inline-block space-between ">
						<!-- Button -->
						<button @click="toggleButtons(i)">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
								<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"></path>
							</svg>
						</button>

						<!-- Floating Hide Buttons -->
						<div 
							v-show="prime.showButtons" 
							class="absolute right-0 z-50 flex flex-col bg-white border rounded-lg shadow-lg top-6 w-36 animate-fade-in"
							>

						<a href="javascript:;" class="text-base px-2 py-1 gap-1 text-[#323232] font-bold rounded-t-lg inline-flex items-center hover:bg-[#257556] hover:text-white" @click="updatePrimeDialog(i+1)">
							<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
							<path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
							<path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
							</svg> Update
						</a>
						<a href="javascript:;" class="text-base px-2 py-1 gap-1 text-[#ef4444] font-bold rounded-b-lg inline-flex items-center hover:bg-[#ef4444] hover:text-white" @click="deletePrime(i)">
							<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
							<path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
							</svg> Delete
						</a>
						
						</div>
					</div>

				</template>

				<!-- Gallery Image-->
				<template #galleryimage>
					<img
					:src="prime.prime"
					alt="Gallery"
					class="object-cover w-full h-[200px] rounded-b-lg"
					v-if="prime.prime"
					/>
				</template>

			</GalleryCard>

		</div>


		<div v-else class="flex items-center h-[65vh] flex-col mx-auto justify-center text-gray-600 prose-2xl font-medium ">
			The gallery is empty!
			<div class="ml-2 mr-2 text-center">
				<SecondaryButton class="uppercase" @click="addPrimeDialog">
					Add Image
				</SecondaryButton>
			</div>
		</div>

	</div>


	<!-- Upload and Update Image Modal -->
	<JetDialogModal :show="modals.createPrime" @close="modals.createPrime = false" maxWidth="md" class="upload-modal">
		<template #title>
			<div class="flex items-center justify-center p-2 rounded-lg upload-modal-header">
				<span v-if="selectedIndex">
					Update Image
				</span>
				<span v-else>
					Upload Image
				</span>
			</div>
		</template>

		<template #content>
			<div class="upload-container">
				<label for="file" class="upload-area">
					<div class="upload-icon">
						<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="cloud-upload-icon">
							<path d="M7 16a4 4 0 01-.88-7.903A5.5 5.5 0 0115.9 9L16 9a5 5 0 011 9.9M15 16l-3-3m0 0l-3 3m3-3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</div>
					<div class="upload-text">
						<p class="upload-main-text">Click to upload or <strong>drag and drop</strong></p>
						<p class="upload-sub-text">SVG, PNG, JPG or GIF</p>
					</div>
				</label>
				<input class="file-input" name="file" id="file" type="file" accept=".svg,.png,.jpg,.jpeg,.gif" />
			</div>
		</template>

		<template #footer>
			<div class="upload-footer">
				<button @click="resetPrimeForm" class="cancel-btn">
					Cancel
				</button>
				<button class="upload-btn" @click="primeAction">
					<span v-if="selectedIndex">
						Update
					</span>
					<span v-else>
						Upload
					</span>
				</button>
			</div>
		</template>
	</JetDialogModal>




</template>



<style scoped="true">
    .c {
        max-height: calc(100vh - 166px);
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
  --checkbox-color: #257556;

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
  --checkbox-color: #257556;
  --checkbox-bg: #dbeafe;
  --checkbox-border: #257556;
}

.ios-checkbox.green {
  --checkbox-color: #10b981;
  --checkbox-bg: #257556;
  --checkbox-border: #257556;
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
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
  animation: fade-in 0.2s ease-out;
}

.custom-scrollbar {
  scrollbar-width: thin;              /* For Firefox */
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

/* Upload Modal Styles */
.upload-modal {
  border-radius: 8px;
  overflow: hidden;
  width: 100%;
  max-width: 500px;
}

.upload-modal-header {
  background: #257556;
  color: white;
  font-weight: 600;
  font-size: 18px;
}

.upload-container {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
}

.upload-area {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  max-width: 100%;
  height: 180px;
  border: 2px dotted #d1d5db;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;
  padding: 20px;
}

.upload-area:hover {
  border-color: #257556;
  background: #f8fafc;
}

.upload-icon {
  margin-bottom: 16px;
}

.cloud-upload-icon {
  width: 60px;
  height: 60px;
  color: #6b7280;
}

.upload-text {
  text-align: center;
}

.upload-main-text {
  font-size: 16px;
  color: #374151;
  margin-bottom: 8px;
  line-height: 1.5;
}

.upload-main-text strong {
  font-weight: 600;
}

.upload-sub-text {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

.file-input {
  display: none;
}

.upload-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
 
}

.cancel-btn {
  background: #ef4444;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.cancel-btn:hover {
  background: #dc2626;
}

.upload-btn {
  background: #257556;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.upload-btn:hover {
  background: #0f7a82;
}


</style>