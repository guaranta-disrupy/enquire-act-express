<script setup>
import Layout from '@/Layouts/Layout.vue';
import SmallButton from '../../Components/SmallButton.vue';
import ProjectDropdown from '@/Extra/ProjectDropdown.vue';
import JetButton from '@/Components/Button.vue';
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import JetInput from '@/Jetstream/Input.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetInputError from '@/Jetstream/InputError.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onBeforeMount } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import ActionButton from '@/Components/ActionButton.vue';
import CreateCriteria from './CreateCriteria.vue';
import Helpers from '../../Mixins/Helpers';
import Loader from "@/Components/Loader.vue";
import JetConfirmationModal from '@/Jetstream/ConfirmationModal.vue';

import { PrismEditor } from 'vue-prism-editor';
import 'vue-prism-editor/dist/prismeditor.min.css'; // import the styles somewhere
// import highlighting library (you can use any library you want just return html string)
import { highlight, languages } from 'prismjs/components/prism-core';
import 'prismjs/components/prism-clike';
import 'prismjs/components/prism-javascript';
import 'prismjs/components/prism-markup';
import 'prismjs/components/prism-css';
import 'prismjs/themes/prism-tomorrow.css'; // import syntax highlighting styles

import CustomSwitch from '@/Components/Switch.vue';

const enableCKEditor = ref(true);
const ckeditor = CKEditor.component;
const editor = ref(ClassicEditor);

const editorConfig = ref({
	extraPlugins: [UploadAdapterPlugin],
});

import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import CKEditor from '@ckeditor/ckeditor5-vue';
// import Font from '@ckeditor/ckeditor5-font/src/font';
import UploadAdapterPlugin from '@/Functions/UploadAdapter';

const props = defineProps(['project']);

const { assign, toast, $varState } = Helpers();

const highlighter = (code) => {
	return highlight(code, languages.markup);
};

const AddVariableModal = ref(false);
const copyVariableModal = ref(false);
const deleteVariableModal = ref(false);
const selectedCriteriaIndex = ref(-1);
const selected_action = ref('create');
const selected_variable = ref();

const form = useForm({
    name: '',
    config: {
        criteria: []
    },
    criteria: {}
});

const showAddVariableModal = () => {
    selected_action.value = 'create';
    form.reset();
    AddVariableModal.value = true;
};

const showEditVariableModal = (variable) => {
    selected_action.value = 'update';
    selected_variable.value = assign(variable);
    form.name = selected_variable.value.name;
    form.config = selected_variable.value.config;
    form.criteria = {};
    $varState.create = form;
    AddVariableModal.value = true;
};

const addCriteria = () => {
    if (form.criteria.conditions != undefined) {
        if (selectedCriteriaIndex.value === -1) {
            form.config.criteria.push(assign(form.criteria));
        } else {
            form.config.criteria[selectedCriteriaIndex.value] = assign(form.criteria);
        }
        resetCriteria();
    } else {
        form.criteria = {
            label: '',
            value: '',
            conditions: [],
        };
    }
};

const resetCriteria = () => {
    form.criteria = {};
    selectedCriteriaIndex.value = -1
};

const addSegment = () => {
    $varState.addSegment();
};

onBeforeMount(() => {
    $varState.create = form;
});

const editCriteria = (criterion, index) => {
    form.criteria = assign(criterion);
    selectedCriteriaIndex.value = index;;
};

const copyCriteria = (criterion) => {
    if (criterion.conditions == 'none') {
        return false;
    }else {
        let tmp_criterion = assign(criterion);
        tmp_criterion.label += ' Copy';
        form.config.criteria.push(assign(tmp_criterion));
    }
};

const deleteCriteria = (index) => {
    if (form.config.criteria[index].conditions == 'none') {
        return false;
    }else {
        form.config.criteria.splice(index, 1);
        resetCriteria();
    }
};

const executeAction = () => {
    if (selected_action.value == 'create') {
        createVariable();
    } else if (selected_action.value == 'update') {
        updateVariable();
    }
};

const createVariable = () => {
    form.post(route('variables.store', props.project), {
        preserveScroll: true,
        resetOnSuccess: true,
        errorBag: 'createVariable',
        onSuccess: () => {
            toast.success("Variable successfully created");
            form.reset();
            AddVariableModal.value = false;
        }
    });
}

const updateVariable = () => {
    form.put(route('variables.update', [props.project, selected_variable.value]), {
        preserveScroll: true,
        resetOnSuccess: true,
        errorBag: 'updateVariable',
        onSuccess: () => {
            toast.success("Variable successfully updated");
            form.reset();
            AddVariableModal.value = false;
        }
    });
};

const copyVariableConfirmation = (variable) => {
    selected_variable.value = assign(variable);
    copyVariableModal.value = true;
};

const copyVariable = () => {
    form.get(route('variables.copy', [props.project, selected_variable.value]), {
        preserveScroll: true,
        resetOnSuccess: true,
        errorBag: 'updateVariable',
        onSuccess: () => {
            toast.success("Variable successfully copied");
            form.reset();
            copyVariableModal.value = false;
        }
    });
};

const deleteVariableConfirmation = (variable) => {
    selected_variable.value = assign(variable);
    deleteVariableModal.value = true;
};

const deleteVariable = () => {
    form.delete(route('variables.destroy', [props.project, selected_variable.value]), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Variable successfully deleted");
            deleteVariableModal.value = false;
        }
    });
};

</script>

<template>
    <Layout :title="'Variables - ' + project.name">
        <template #header>
            <div class="inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>&nbsp;
                {{ project.name }} > Variables
            </div>
        </template>
        <div>
            <div class="max-w-8xl mx-auto px-2">
                <div class="flex justify-between mb-2 rounded-xl shadow-md py-2 px-2 bg-white">
                    <div class="inline-flex items-center">
                        <Link class="mr-1 inline-flex items-center text-sm text-sky-500" :href="route('groups.index', project)">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                            </svg>Back
                        </Link>
                        <ProjectDropdown destination="variables.index" class="mx-3" />
                    </div>
                    <SmallButton class="ml-1" @click="showAddVariableModal">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        New Variable
                    </SmallButton>
                </div>
                <div class="overflow-y-auto c">
                    <template v-for="(variable, index) in project.variables" :key="index">
                        <div class="mb-6 bg-white rounded-xl overflow-hidden shadow transform transition-all sm:w-full sm:mx-auto border mt-2">
                            <div class="px-6 py-2">
                                <div class="grid grid-cols-2">
                                    <div class="text-lg">
                                        <code>
                                            {{ variable.name }}
                                        </code>
                                    </div>
                                    <div class="flex flex-row justify-end">
                                        <SmallButton size="sm" variant="info" class="font-medium mr-1" @click="copyVariableConfirmation(variable)">
                                            Copy
                                        </SmallButton>
                                        <SmallButton size="sm" variant="info" class="font-medium mr-1" @click="showEditVariableModal(variable)">
                                            Update
                                        </SmallButton>
                                        <SmallButton size="sm" variant="danger" class="font-medium" @click="deleteVariableConfirmation(variable)">
                                            Delete
                                        </SmallButton>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <table class="min-w-full divide-y divide-gray-200 table-auto">
                                        <thead class="bg-white">
                                            <tr>
                                                <th class="py-3 px-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                    #
                                                </th>
                                                <th class="py-3 px-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                    Criteria
                                                </th>
                                                <th class="py-3 px-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                    Condition
                                                </th>
                                                <th class="py-3 px-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                    Value
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="(cr, key2) in variable.config.criteria">
                                                <td class="text-xs text-gray-500 pl-3">
                                                    {{ key2 + 1 }}
                                                </td>
                                                <td class="pl-3">
                                                    {{ cr.label }}
                                                </td>
                                                <td class="pl-3 text-xs">
                                                    {{ $varState.criteriaToString(cr.conditions) }}
                                                </td>
                                                <td class="pl-6">
                                                    <div class="variable_value text-xs" v-html="cr.value"></div>
                                                </td>
                                            </tr>

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </Layout>
    <AppLayout :title="'Variables - ' + project.name">
        <template #header>
			<!-- <projects-dropdown destination="projects.settings"></projects-dropdown> -->
		</template>
        <div>
            <div class="max-w-7xl mx-auto py-4 sm:px-6 lg:px-8">
                <Link class="mr-1" :href="route('groups.index', project)">
                    <ActionButton variant="primary" size="md" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>Back
                    </ActionButton>
                </Link>
                <JetButton class="ml-1" @click="showAddVariableModal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    New Variable
                </JetButton>
                <template v-for="(variable, index) in project.variables" :key="index">
                    <div class="mb-6 bg-white rounded-lg overflow-hidden shadow transform transition-all sm:w-full sm:mx-auto border mt-2">
                        <div class="px-6 py-4">
                            <div class="grid grid-cols-2">
                                <div class="text-lg">
                                    <code>
                                        {{ variable.name }}
                                    </code>
                                </div>
                                <div class="flex flex-row justify-end">
                                    <JetButton size="sm" variant="info" class="font-medium mr-1" @click="copyVariableConfirmation(variable)">
                                        Copy
                                    </JetButton>
                                    <JetButton size="sm" variant="info" class="font-medium mr-1" @click="showEditVariableModal(variable)">
                                        Update
                                    </JetButton>
                                    <JetButton size="sm" variant="danger" class="font-medium" @click="deleteVariableConfirmation(variable)">
                                        Delete
                                    </JetButton>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <table class="min-w-full divide-y divide-gray-200 table-fixed">
                                    <thead class="bg-white">
                                        <tr>
                                            <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                #
                                            </th>
                                            <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                Criteria
                                            </th>
                                            <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                Condition
                                            </th>
                                            <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                Value
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="(cr, key2) in variable.config.criteria">
                                            <td class="text-xs text-gray-500 pl-6">
                                                {{ key2 + 1 }}
                                            </td>
                                            <td class="pl-6">
                                                {{ cr.label }}
                                            </td>
                                            <td class="pl-6 text-xs">
                                                {{ $varState.criteriaToString(cr.conditions) }}
                                            </td>
                                            <td class="variable-value pl-6">
                                                <span v-html="cr.value"></span>
                                            </td>
                                        </tr>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>
                <p class="text-2xl text-center mt-6" v-if="!project.variables">
                    No record found.
                </p>
            </div>
        </div>
        <JetDialogModal :show="AddVariableModal" maxWidth="5xl">
            <template #title>
                Project Variable
            </template>
            <template #content>
                <div class="grid grid-cols-6 gap-4">
                    <div class="col-span-6">
                        <JetLabel value="Variable Name" />
                        <JetInput v-model="form.name" type="text" class="w-full" placeholder="Variable Name" />
                        <JetInputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div class="col-span-6 max-h-[60vh] overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 table-fixed">
                            <thead class="bg-white">
                                <tr>
                                    <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                        Criteria
                                    </th>
                                    <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                        Condition
                                    </th>
                                    <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                        Value
                                    </th>
                                    <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(cr, key2) in form.config.criteria">
                                    <td class="pl-6">
                                        {{ cr.label }}
                                    </td>
                                    <td class="pl-6 text-xs">
                                        {{ $varState.criteriaToString(cr.conditions) }}
                                    </td>
                                    <td class=" pl-6">
                                        <div class="variable_value" v-html="cr.value"></div>
                                    </td>
                                    <td class="pl-6">
                                        <button class="mr-2 cursor-pointer text-xs text-blue-400 underline focus:outline-none" @click="editCriteria(cr, key2)">
                                            Edit
                                        </button>
                                        <button type="button" @click="copyCriteria(cr)"
                                            class="mr-2 cursor-pointer text-xs text-blue-400 underline focus:outline-none">
                                            Copy
                                        </button>
                                        <button type="button" @click="deleteCriteria(key2)"
                                            class="text-xs focus:outline-none cursor-pointer text-red-500 underline">
                                            Delete
                                        </button>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                        <JetInputError :message="form.errors['config.criteria']" class="mt-2" />
                    </div>
                    <div class="col-span-6">
                        <JetButton size="sm" @click="addCriteria">
                            <span v-if="form.criteria?.conditions != undefined">
                                Save Criteria
                            </span>
                            <span v-else>
                                Add Criteria
                            </span>
                        </JetButton>
                        <JetButton variant="secondary" class="ml-2" v-if="form.criteria?.conditions != undefined" size="sm" @click="resetCriteria">
                            Cancel
                        </JetButton>
                    </div>
                    <template v-if="form.criteria?.conditions != undefined">
                        <div class="col-span-2">
                            <JetLabel value="Criteria Label" />
                            <JetInput type="text" class="w-full" v-model="form.criteria.label" placeholder="Enter criteria label" />
                        </div>
                        <div class="col-span-4">
                            <CustomSwitch v-model:checked="enableCKEditor">
                                Editor
                            </CustomSwitch>
                            <JetLabel value="Criteria Value" />
                            
                            <div v-if="enableCKEditor">
                                <ckeditor :editor="editor" v-model="form.criteria.value" :config="editorConfig"></ckeditor>
                            </div>
                            <prism-editor v-else class="my-editor editor-height" v-model="form.criteria.value" :highlight="highlighter" line-numbers></prism-editor>
                            <!-- <textarea v-else class="block  w-full form-input" v-model="form.criteria.value" rows="3"></textarea> -->
                        </div>
                        <template v-if="form.criteria.conditions != 'none'">
                            <div class="col-span-6">
                                <JetButton @click="addSegment" variant="secondary" size="sm">
                                    <span v-if="form.criteria.conditions.length > 0">Add Segment</span>
                                    <span v-else>Add Condition</span>
                                </JetButton>
                            </div>
                            <div class="col-span-6" v-for="(condition, index) in form.criteria.conditions" :key="index">
                                <CreateCriteria :deep="0" :condition="condition" :indexes="[index]" />
                            </div>
                        </template>
                    </template>
                </div>
            </template>
            <template #footer>
                <JetButton variant="secondary" @click="AddVariableModal = false">
                    nevermind
                </JetButton>
                <jet-button class="ml-2" @click="executeAction">
                    <span v-if="selected_action == 'create'">
                        create variable
                    </span>
                    <span v-else>
                        save changes
                    </span>
                </jet-button>
            </template>
        </JetDialogModal>
        
    </AppLayout>
    <Loader :show="form.processing">
		Please wait...
	</Loader>
    <JetConfirmationModal :show="copyVariableModal">
        <template #title>
            Copy Variable
        </template>
        <template #content>
            Are you sure you want to copy <strong>{{ selected_variable.name }}</strong>?
        </template>
        <template #footer>
            <JetButton variant="secondary" @click="copyVariableModal = false" class="mr-1">
                nevermind
            </JetButton>
            <JetButton @click="copyVariable">
                copy variable
            </JetButton>
        </template>
    </JetConfirmationModal>
    <JetConfirmationModal :show="deleteVariableModal">
        <template #title>
            Delete Variable
        </template>
        <template #content>
            Are you sure you want to delete <strong>{{ selected_variable.name }}</strong>?
        </template>
        <template #footer>
            <JetButton variant="secondary" @click="deleteVariableModal = false" class="mr-1">
                nevermind
            </JetButton>
            <JetButton @click="deleteVariable">
                delete variable
            </JetButton>
        </template>
    </JetConfirmationModal>
</template>
<style scoped="true">


.variable_value :deep() img {
    max-width: 40% !important;
}
.c {
        height: calc(100vh - 120px);
    }
</style>