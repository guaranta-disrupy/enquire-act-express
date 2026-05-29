<template>
  <custom-form @submitted="createTrial">
    <template #form>
      <div class="col-span-6">
        <jet-label class="mb-1">
          Trial title
          <custom-switch v-model:checked="enableCKEditor" class="float-right">
            Editor
          </custom-switch>
        </jet-label>
        <div v-if="enableCKEditor">
          <ckeditor
            :editor="editor"
            v-model="form.config.title"
            :config="editorConfig"
          ></ckeditor>
        </div>
        <!-- <textarea v-else class="block w-full form-input" v-model="form.config.title" rows="6"></textarea> -->
        <prism-editor
          v-else
          class="my-editor editor-height"
          v-model="form.config.title"
          :highlight="highlighter"
          line-numbers
        ></prism-editor>
        <template v-if="!form.config.title">
          <jet-label class="mt-4">
            <div class="flex items-center">
              <jet-checkbox v-model:checked="form.config.main_trial" />

              <div class="ml-2">Main trial</div>
            </div>
          </jet-label>
          <jet-label class="mt-3">
            <div class="flex items-center">
              <jet-checkbox v-model:checked="form.config.random" />

              <div class="ml-2">Randomize targets</div>
            </div>
          </jet-label>
          <jet-label class="mt-3">
            <div class="flex items-center">
              <jet-checkbox v-model:checked="form.config.random_prime" />

              <div class="ml-2">Randomize primes</div>
            </div>
          </jet-label>
          <jet-label class="mt-3">
            <div class="flex items-center">
              <jet-checkbox v-model:checked="form.config.prime_as_next" />

              <div class="ml-2">Prime as next button</div>
            </div>
          </jet-label>
          <div class="flex mt-4 flex-inline">
            <h2 class="text-lg font-semibold">
              <u>Primes ({{ form.primes.length }})</u>
            </h2>
            <action-button type="button" @click="addPrimeDialog" class="ml-2"
              >add prime</action-button
            >
          </div>
          <div class="mt-3 overflow-auto lg:overflow-visible">
            <table
              class="table w-full space-y-6 text-sm text-gray-400 whitespace-no-wrap border-separate"
              v-if="form.primes.length > 0"
            >
              <thead class="text-gray-500 bg-gray-100">
                <tr>
                  <th class="p-3 text-left">#</th>
                  <th class="p-3 text-left">Prime</th>
                  <th class="p-3 text-left">Equivalent</th>
                  <th class="p-3 text-left">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  class="bg-gray-100 rounded"
                  v-for="(prime, i) in form.primes"
                  :key="i"
                >
                  <td class="p-3">
                    <div class="text-gray-600">
                      {{ i + 1 }}
                    </div>
                  </td>
                  <td class="p-3" :class="{ 'w-28': hasImage(prime.prime) }">
                    <div
                      class="text-sm text-gray-600"
                      v-html="prime.prime"
                    ></div>
                  </td>
                  <td class="p-3">
                    <div class="text-xs text-gray-500">
                      {{ prime.equivalent }}
                    </div>
                  </td>
                  <td class="p-3">
                    <a
                      href="javascript:;"
                      class="mr-1 text-xs text-blue-400"
                      @click="deletePrime(i)"
                    >
                      <i class="fa fa-trash-o"></i> Delete
                    </a>
                    <a
                      href="javascript:;"
                      class="text-xs text-blue-400"
                      @click="updatePrimeDialog(i)"
                    >
                      <i class="fa fa-pencil"></i> Edit
                    </a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="flex mt-6 flex-inline">
            <h2 class="text-lg font-semibold">
              <u> Targets ({{ form.targets.length }}) </u>
            </h2>
            <action-button
              class="ml-2"
              type="button"
              @click="modals.createTargetModal = true"
            >
              add target
            </action-button>
          </div>

          <div class="mt-3 overflow-auto lg:overflow-visible">
            <table
              class="table w-full space-y-6 text-sm text-gray-400 whitespace-no-wrap border-separate"
              v-if="form.targets.length > 0"
            >
              <thead class="text-gray-500 bg-gray-100">
                <tr>
                  <th class="p-3 text-left">#</th>
                  <th class="p-3 text-left">Target</th>
                  <th class="p-3 text-left">Equivalent</th>
                  <th class="p-3 text-left">Value</th>
                  <th class="p-3 text-left">Colours</th>
                  <th class="p-3 text-left">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  class="bg-gray-100 rounded"
                  v-for="(target, i) in form.targets"
                  :key="'tr' + i"
                >
                  <td class="p-3">
                    <div class="text-gray-600">
                      {{ i + 1 }}
                    </div>
                  </td>
                  <td class="p-3" :class="{ 'w-28': hasImage(target.target) }">
                    <span class="text-sm text-gray-600" v-html="target.target">
                    </span>
                  </td>
                  <td class="p-3">
                    <div class="text-xs text-gray-500">
                      {{ target.equivalent }}
                      <span
                        class="px-1 ml-1 text-xs bg-gray-500 rounded-md text-gray-50"
                        v-if="target.can_unselect"
                      >
                        Unselect
                      </span>
                    </div>
                  </td>
                  <td class="p-3">
                    <span class="text-xs text-gray-500">
                      {{ target["value"] ?? "-" }}
                    </span>
                  </td>
                  <td class="p-3">
                    <span
                      class="px-1 ml-1 text-xs rounded-md text-gray-50"
                      v-if="target.colors.normal"
                      :style="'background-color: ' + target.colors.normal"
                    >
                      N
                    </span>
                    <span
                      class="px-1 ml-1 text-xs rounded-md text-gray-50"
                      v-if="target.colors.hover"
                      :style="'background-color: ' + target.colors.hover"
                    >
                      H
                    </span>
                    <span
                      class="px-1 ml-1 text-xs rounded-md text-gray-50"
                      v-if="target.colors.active"
                      :style="'background-color: ' + target.colors.active"
                    >
                      A
                    </span>
                  </td>
                  <td class="p-3">
                    <a
                      href="javascript:;"
                      class="mr-1 text-xs text-blue-400"
                      @click="deleteTarget(i)"
                    >
                      <i class="fa fa-trash-o"></i> Delete
                    </a>
                    <a
                      href="javascript:;"
                      class="text-xs text-blue-400"
                      @click="updateTargetDialog(i)"
                    >
                      <i class="fa fa-pencil"></i> Edit
                    </a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="grid grid-cols-1 gap-4">
            <div class="hidden mt-6">
              <jet-label class="mb-1">
                Prime Header
                <custom-switch
                  v-model:checked="enableCKEditor"
                  class="float-right"
                >
                  Editor
                </custom-switch>
              </jet-label>
              <div v-if="enableCKEditor">
                <ckeditor
                  :editor="editor"
                  v-model="form.config.prime_header"
                  :config="editorConfig"
                ></ckeditor>
              </div>
              <textarea
                v-else
                class="block w-full form-input"
                v-model="form.config.prime_header"
                rows="10"
              ></textarea>
            </div>
            <div class="mt-6">
              <jet-label class="mb-1">
                Main Header
                <custom-switch
                  v-model:checked="enableCKEditor"
                  class="float-right"
                >
                  Editor
                </custom-switch>
              </jet-label>
              <div v-if="enableCKEditor">
                <ckeditor
                  :editor="editor"
                  v-model="form.config.main_header"
                  :config="editorConfig"
                ></ckeditor>
              </div>
              <!-- <textarea v-else class="block w-full form-input" v-model="form.config.main_header" rows="10"></textarea> -->
              <prism-editor
                v-else
                class="my-editor editor-height"
                v-model="form.config.main_header"
                :highlight="highlighter"
                line-numbers
              ></prism-editor>
            </div>
          </div>

          <div class="mt-2">
            <jet-label>Empty selection text</jet-label>
            <jet-input
              class="w-1/2"
              type="text"
              v-model="form.config.empty_selection"
            ></jet-input>
          </div>
          <div class="mt-2">
            <jet-label>Bottom text</jet-label>
            <jet-input
              class="w-1/2"
              type="text"
              v-model="form.config.bottom_text"
            ></jet-input>
          </div>
          <jet-label class="mt-3" value="Selection limit (0 = can select all)">
          </jet-label>
          <jet-input
            type="number"
            v-model="form.config.limit"
            class="w-1/6"
          ></jet-input>
          <jet-label class="mt-3" value="Display targets by"> </jet-label>
          <jet-input
            type="number"
            v-model="form.config.display_by"
            class="w-1/6"
          ></jet-input>
          <jet-label class="mt-3">
            <div class="flex items-center">
              <jet-checkbox v-model:checked="form.config.single_select" />

              <div class="ml-2">Single select</div>
            </div>
          </jet-label>
          <jet-label class="mt-3">
            <div class="flex items-center">
              <jet-checkbox v-model:checked="form.config.auto_next" />

              <div class="ml-2">Auto next trial</div>
            </div>
          </jet-label>
        </template>
      </div>
    </template>

    <template #actions>
      <Link :href="route('tests.index', survey)">
        <action-button variant="primary" size="md" class="mr-4">
          <i class="fa fa-arrow-left"></i>&nbsp;back
        </action-button>
      </Link>

      <action-button
        v-if="false"
        variant="primary"
        size="md"
        :class="{ 'opacity-25': form.processing }"
        :disabled="form.processing"
        @click="createTrial"
      >
        <i class="fa fa-floppy-o"></i>&nbsp;save
      </action-button>
    </template>
  </custom-form>

  <jet-dialog-modal
    :show="modals.createPrimeModal"
    @close="modals.createPrimeModal = false"
  >
    <template #title> Add Prime </template>

    <template #content>
      <jet-label class="mb-1">
        Prime
        <custom-switch v-model:checked="enableCKEditor" class="float-right">
          Editor
        </custom-switch>
      </jet-label>
      <div v-if="enableCKEditor">
        <ckeditor
          :editor="editor"
          v-model="prime_form.prime"
          @input="unformatContent(1)"
          :config="editorConfig"
        ></ckeditor>
      </div>
      <!-- <textarea v-else class="block w-full form-input" v-model="prime_form.prime" @keyup="unformatContent(1)"></textarea> -->
      <prism-editor
        v-else
        class="my-editor editor-height"
        v-model="prime_form.prime"
        :highlight="highlighter"
        line-numbers
        @keyup="unformatContent(1)"
      ></prism-editor>
      <h2 class="mt-3 text-lg">Equivalent</h2>
      <jet-input
        type="text"
        class="w-full"
        v-model="prime_form.equivalent"
      ></jet-input>
      <div class="flex items-stretch">
        <div class="flex-1 w-full pt-4">
          <div
            class="relative h-full p-2 border border-gray-300 border-solid rounded"
          >
            <div
              class="p-2 text-xs text-gray-500 uppercase bg-white grp-header"
            >
              skip logic
            </div>
            <div class="p-2 mt-3">
              <div class="col-span-6 mb-2">
                <jet-button
                  type="button"
                  variant="secondary"
                  @click="addSegment"
                  class="px-1 py-1 text-xs text-gray-900 bg-gray-300 border-gray-600 hover:bg-gray-200"
                >
                  <span v-if="prime_form.config.conditions.length > 0"
                    >Add Segment</span
                  ><span v-else>Add Condition</span>
                </jet-button>
              </div>
              <div
                class="col-span-6"
                v-for="(condition, index) in prime_form.config.conditions"
                :key="index"
              >
                <criteria-contents
                  :deep="0"
                  :condition="condition"
                  :indexes="[index]"
                />
              </div>
              <div class="col-span-6 mt-3">
                <code class="text-xs bg-gray-200 text-black-500">{{
                  criteriaToString(prime_form.config.conditions)
                }}</code>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template #footer>
      <jet-secondary-button @click="modals.createPrimeModal = false">
        nevermind
      </jet-secondary-button>
      <jet-button class="ml-2" @click="addPrimeList"> Add prime </jet-button>
    </template>
  </jet-dialog-modal>

  <jet-dialog-modal
    :show="modals.updatePrimeModal"
    @close="modals.updatePrimeModal = false"
  >
    <template #title> Update Prime </template>

    <template #content>
      <jet-label class="mb-1">
        Prime
        <custom-switch v-model:checked="enableCKEditor" class="float-right">
          Editor
        </custom-switch>
      </jet-label>
      <div v-if="enableCKEditor">
        <ckeditor
          :editor="editor"
          v-model="prime_form.prime"
          @input="unformatContent(1)"
          :config="editorConfig"
        ></ckeditor>
      </div>
      <!-- <textarea v-else class="block w-full form-input" v-model="prime_form.prime" @keyup="unformatContent(1)"></textarea> -->
      <prism-editor
        v-else
        class="my-editor editor-height"
        v-model="prime_form.prime"
        :highlight="highlighter"
        line-numbers
        @keyup="unformatContent(1)"
      ></prism-editor>
      <h2 class="mt-3 text-lg">Equivalent</h2>
      <jet-input
        type="text"
        class="w-full"
        v-model="prime_form.equivalent"
      ></jet-input>
      <div class="flex items-stretch">
        <div class="flex-1 w-full pt-4">
          <div
            class="relative h-full p-2 border border-gray-300 border-solid rounded"
          >
            <div
              class="p-2 text-xs text-gray-500 uppercase bg-white grp-header"
            >
              skip logic
            </div>
            <div class="p-2 mt-3">
              <div class="col-span-6 mb-2">
                <jet-button
                  type="button"
                  variant="secondary"
                  @click="addSegment"
                  class="px-1 py-1 text-xs text-gray-900 bg-gray-300 border-gray-600 hover:bg-gray-200"
                >
                  <span v-if="prime_form.config.conditions.length > 0"
                    >Add Segment</span
                  ><span v-else>Add Condition</span>
                </jet-button>
              </div>
              <div
                class="col-span-6"
                v-for="(condition, index) in prime_form.config.conditions"
                :key="index"
              >
                <criteria-contents
                  :deep="0"
                  :condition="condition"
                  :indexes="[index]"
                />
              </div>
              <div class="col-span-6 mt-3">
                <code class="text-xs bg-gray-200 text-black-500">{{
                  criteriaToString(prime_form.config.conditions)
                }}</code>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template #footer>
      <jet-secondary-button @click="modals.updatePrimeModal = false">
        nevermind
      </jet-secondary-button>
      <jet-button class="ml-2" @click="updatePrimeList">
        update prime
      </jet-button>
    </template>
  </jet-dialog-modal>

  <jet-dialog-modal
    :show="modals.createTargetModal"
    @close="modals.createTargetModal = false"
  >
    <template #title> Add Target </template>

    <template #content>
      <jet-label class="mb-1">
        Target
        <custom-switch v-model:checked="enableCKEditor" class="float-right">
          Editor
        </custom-switch>
      </jet-label>
      <div v-if="enableCKEditor">
        <ckeditor
          :editor="editor"
          v-model="target_form.target"
          @input="unformatContent(2)"
          :config="editorConfig"
        ></ckeditor>
      </div>
      <!-- <textarea v-else class="block w-full form-input" v-model="target_form.target" @keyup="unformatContent(2)" rows="7"></textarea> -->
      <prism-editor
        v-else
        class="my-editor editor-height"
        v-model="target_form.target"
        :highlight="highlighter"
        line-numbers
        @keyup="unformatContent(2)"
      ></prism-editor>
      <div class="grid grid-cols-3 gap-1">
        <div class="col-span-2">
          <h2 class="mt-3 text-lg">Equivalent</h2>
          <jet-input
            type="text"
            class="w-full"
            v-model="target_form.equivalent"
          ></jet-input>
        </div>
        <div>
          <h2 class="mt-3 text-lg">Value</h2>
          <jet-input
            class="w-full"
            v-model="target_form.value"
            type="number"
          ></jet-input>
        </div>
      </div>
      <jet-label class="mt-4">
        <div class="flex items-center">
          <jet-checkbox v-model:checked="target_form.can_unselect" />
          <div class="ml-2">Can unselect other targets</div>
        </div>
      </jet-label>

      <div class="grid grid-cols-3 gap-1">
        <div>
          <jet-label class="mt-4" value="Colors: Normal"></jet-label>
          <jet-input
            type="color"
            class="w-full"
            v-model="target_form.colors.normal"
          ></jet-input>
        </div>
        <div>
          <jet-label class="mt-4" value="Colors: Hover"></jet-label>
          <jet-input
            type="color"
            class="w-full"
            v-model="target_form.colors.hover"
          ></jet-input>
        </div>
        <div>
          <jet-label class="mt-4" value="Colors: Active"></jet-label>
          <jet-input
            type="color"
            class="w-full"
            v-model="target_form.colors.active"
          ></jet-input>
        </div>
      </div>
    </template>

    <template #footer>
      <jet-secondary-button @click="modals.createTargetModal = false">
        nevermind
      </jet-secondary-button>
      <jet-button class="ml-2" @click="addTargetList"> Add target </jet-button>
    </template>
  </jet-dialog-modal>

  <jet-dialog-modal
    :show="modals.updateTargetModal"
    @close="modals.updateTargetModal = false"
  >
    <template #title> Update Target </template>

    <template #content>
      <jet-label class="mb-1">
        Target
        <custom-switch v-model:checked="enableCKEditor" class="float-right">
          Editor
        </custom-switch>
      </jet-label>
      <div v-if="enableCKEditor">
        <ckeditor
          :editor="editor"
          v-model="target_form.target"
          @input="unformatContent(2)"
          :config="editorConfig"
        ></ckeditor>
      </div>
      <!-- <textarea v-else class="block w-full form-input" v-model="target_form.target" @keyup="unformatContent(2)" rows="7"></textarea> -->
      <prism-editor
        v-else
        class="my-editor editor-height"
        v-model="target_form.target"
        :highlight="highlighter"
        line-numbers
        @keyup="unformatContent(2)"
      ></prism-editor>
      <div class="grid grid-cols-3 gap-1">
        <div class="col-span-2">
          <h2 class="mt-3 text-lg">Equivalent</h2>
          <jet-input
            type="text"
            class="w-full"
            v-model="target_form.equivalent"
          ></jet-input>
        </div>
        <div>
          <h2 class="mt-3 text-lg">Value</h2>
          <jet-input
            class="w-full"
            v-model="target_form.value"
            type="number"
          ></jet-input>
        </div>
      </div>
      <jet-label class="mt-4">
        <div class="flex items-center">
          <jet-checkbox v-model:checked="target_form.can_unselect" />
          <div class="ml-2">Can unselect other targets</div>
        </div>
      </jet-label>
      <div class="grid grid-cols-3 gap-1">
        <div>
          <jet-label class="mt-4" value="Colors: Normal"></jet-label>
          <jet-input
            type="color"
            class="w-full"
            v-model="target_form.colors.normal"
          ></jet-input>
        </div>
        <div>
          <jet-label class="mt-4" value="Colors: Hover"></jet-label>
          <jet-input
            type="color"
            class="w-full"
            v-model="target_form.colors.hover"
          ></jet-input>
        </div>
        <div>
          <jet-label class="mt-4" value="Colors: Active"></jet-label>
          <jet-input
            type="color"
            class="w-full"
            v-model="target_form.colors.active"
          ></jet-input>
        </div>
      </div>
    </template>

    <template #footer>
      <jet-secondary-button @click="modals.updateTargetModal = false">
        nevermind
      </jet-secondary-button>
      <jet-button class="ml-2" @click="updateTargetList">
        update target
      </jet-button>
    </template>
  </jet-dialog-modal>
  <teleport to="body">
    <button
      @click="createTrial"
      class="uppercase text-white px-5 mr-1 inline-flex items-center py-2.5 text-sm tracking-widest rounded-lg shadow-sm font-sans focus:ring-blue-200 transition ease-in-out duration-150 font-medium bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 h40 col-flex-1 j-center floating-button tracking-wide"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="w-6 h-6"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"
        /></svg
      >&nbsp;save
    </button>
  </teleport>
  <Loader :show="form.processing"> Please wait... </Loader>
</template>

<script setup>
import JetButton from "@/Jetstream/Button.vue";
import JetInput from "@/Jetstream/Input.vue";
import JetLabel from "@/Jetstream/Label.vue";
import JetSecondaryButton from "@/Jetstream/SecondaryButton.vue";
import JetCheckbox from "@/Jetstream/Checkbox.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import ActionButton from "@/Components/ActionButton.vue";
import CustomSwitch from "@/Components/Switch.vue";
import CustomForm from "@/Components/Form.vue";
import CriteriaContents from "./Criteria.vue";
import Loader from "@/Components/Loader.vue";

import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import CKEditor from "@ckeditor/ckeditor5-vue";

// import sanitizeHtml from 'sanitize-html'

import capitalize from "lodash/capitalize";
import forEach from "lodash/forEach";
import maxBy from "lodash/maxBy";

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

import { Link, useForm } from "@inertiajs/vue3";
import { ref, onBeforeMount, provide } from "vue";

import Helpers from "../../Mixins/Helpers";

const props = defineProps(["survey"]);

const { datetime, toast, hasString, hasImage, assign, $state, sanitize } =
  Helpers();

const code = ref(".test{ width: 50; }");

const form = useForm({
  config: {
    title: "",
    main_trial: true,
    random: true,
    prime_header: "",
    main_header: "",
    empty_selection: "Please select at least 1 item",
    main_header_timeout: 3000,
    type: null,
    single_select: false,
    limit: 0,
    auto_next: false,
    display_by: 1,
    random_prime: false,
    prime_as_next: false,
  },
  primes: [],
  targets: [],
});

const ckeditor = CKEditor.component;

const editor = ref(ClassicEditor);

const modals = ref({
  createPrimeModal: false,
  createTargetModal: false,
  updatePrimeModal: false,
  updateTargetModal: false,
});

const prime_form = ref({
  prime: "",
  equivalent: "",
  config: {
    conditions: [],
  },
});

const target_form = ref({
  target: "",
  equivalent: "",
  can_unselect: false,
  colors: {
    normal: null,
    hover: null,
    active: null,
  },
  value: 0,
});

const enableCKEditor = ref(true);

const selectedIndex = ref(null);

const editorConfig = ref({
  extraPlugins: [UploadAdapterPlugin],
});

onBeforeMount(() => {
  $state.criteria = prime_form.value;
});

provide("state", $state);

const highlighter = (code) => {
  return highlight(code, languages.markup);
};

const createTrial = () => {
  if (form.targets.length == 0 || form.primes.length == 0) {
    form.config.type = "descriptive";
  } else {
    form.config.type = "trial";
  }
  form.post(route("tests.store", props.survey), {
    preserveScroll: true,
    errorBag: "createTrial",
    onSuccess: () => {
      console.log(props.survey);
      toast.success("Trial successfully created");
    },
  });
};

const unformatContent = (t, val = null) => {
  if (t == 1) {
    if (hasImage(prime_form.value.prime)) return;
    // prime_form.value.equivalent = sanitizeHtml(prime_form.value.prime, {
    // 	allowedTags: []
    // }).split(' i ').join(' I ')
    prime_form.value.equivalent = sanitize(prime_form.value.prime)
      .split(" i ")
      .join(" I ");
  } else if (t == 2) {
    if (hasImage(target_form.value.target)) return;
    target_form.value.equivalent = capitalize(
      sanitize(target_form.value.target)
    )
      .split(" i ")
      .join(" I ");
  } else {
    return sanitize(val).split(" i ").join(" I ");
  }
};

const addPrimeList = () => {
  let primes = assign(prime_form.value.prime.split("\n"));
  let prime_length = getMaxIndex(form.primes);
  forEach(primes, (p, i) => {
    prime_length++;
    let prime = {
      prime: p,
      equivalent:
        primes.length > 1 ? unformatContent(3, p) : prime_form.value.equivalent,
      index: prime_length,
      config: assign(prime_form.value.config),
    };
    form.primes.push(prime);
  });
  modals.value.createPrimeModal = false;
  resetPrimeForm();
};

const getMaxIndex = (values) => {
  return maxBy(values, "index")?.index ?? 0;
};

const addTargetList = () => {
  let targets = target_form.value.target.split("\n");
  let target_length = getMaxIndex(form.targets);
  forEach(targets, (t, i) => {
    target_length++;
    let target = {
      target: t,
      equivalent:
        targets.length > 1
          ? unformatContent(3, t)
          : target_form.value.equivalent,
      index: target_length,
      can_unselect: target_form.value.can_unselect,
      colors: assign(target_form.value.colors),
      value:
        targets.length > 1
          ? target_length
          : typeof target_form.value.value === "number" && !Number.isNaN(target_form.value.value)
          ? target_form.value.value
          : target_length,
    };
    form.targets.push(target);
  });
  modals.value.createTargetModal = false;
  target_form.value.target = "";
  target_form.value.equivalent = "";
  target_form.value.can_unselect = false;
};

const resetPrimeForm = () => {
  prime_form.value = {
    prime: "",
    equivalent: "",
    config: {
      conditions: [],
    },
  };
};

const addPrimeDialog = () => {
  resetPrimeForm();
  $state.criteria = prime_form.value;
  modals.value.createPrimeModal = true;
};

const updatePrimeDialog = (index) => {
  selectedIndex.value = index;
  prime_form.value = assign(form.primes[index]);
  $state.criteria = prime_form.value;
  modals.value.updatePrimeModal = true;
};

const updatePrimeList = () => {
  form.primes[selectedIndex.value] = prime_form.value;
  modals.value.updatePrimeModal = false;
};

const updateTargetDialog = (index) => {
  selectedIndex.value = index;
  target_form.value = assign(form.targets[index]);
  modals.value.updateTargetModal = true;
};

const updateTargetList = () => {
  form.targets[selectedIndex.value] = assign(target_form.value);
  modals.value.updateTargetModal = false;
};

const deletePrime = (index) => {
  form.primes.splice(index, 1);
};

const deleteTarget = (index) => {
  form.targets.splice(index, 1);
};

const addSegment = () => {
  $state.addSegment();
};

const criteriaToString = (str) => {
  return $state.criteriaToString(str);
};
</script>



<style scoped="true">
.table {
  border-spacing: 0 3px;
}

i {
  font-size: 1rem !important;
}

.table tr {
  border-radius: 20px;
}

tr td:last-child,
tr th:last-child {
  border-radius: 0 0.625rem 0.625rem 0;
}

tr td:nth-child(1),
tr th:nth-child(1) {
  border-radius: 0.625rem 0 0 0.625rem;
}

.grp-header {
  position: absolute;
  top: 0px;
  left: 16px;
  transform: translateY(-50%);
}
</style>