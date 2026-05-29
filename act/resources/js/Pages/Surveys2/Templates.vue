<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import Helpers from "@/Mixins/Helpers.js";
const { toast } = Helpers();
const props = defineProps([
    "survey",
    "templatePrimes",
    "templateTargets",
]);

// Local reactive state instead of store
const survey = ref(props.survey);
const templatePrimes = ref([...props.templatePrimes]);
const templateTargets = ref([...props.templateTargets]);
function editTemplatePrimeById(id, partial) {
  const index = templatePrimes.value.findIndex(p => p.id === id);
  if (index !== -1) {
    templatePrimes.value[index] = { ...templatePrimes.value[index], ...partial };
    toast.success("Prime updated successfully");
  }
}

function removeTemplatePrimeById(id) {
    if (id === undefined || id === null) return;
    const prime = templatePrimes.value.find(p => p.id === id);
    if (prime) {
      templatePrimes.value = templatePrimes.value.filter(p => p.id !== id);
      toast.error("Prime deleted successfully");
    }
}



function editTemplateTargetById(id, partial) {
    const index = templateTargets.value.findIndex(t => t.id === id);
    if (index !== -1) {
        templateTargets.value[index] = { ...templateTargets.value[index], ...partial };
        toast.success("Target updated successfully");
    }
}

function removeTemplateTargetById(id) {
    if (id === undefined || id === null) return;
    const target = templateTargets.value.find(t => t.id === id);
    if (target) {
      templateTargets.value = templateTargets.value.filter(t => t.id !== id);
      toast.error("Target deleted successfully");
    }
}



// Per-row quick edit state for primes/targets
const primeEditNameById = ref({});
const targetEditNameById = ref({});

function startPrimeEdit(id, currentName) {
    primeEditNameById.value[id] = currentName ?? "";
}

function applyPrimeEdit(id) {
    const name = primeEditNameById.value[id];
    if (name === undefined) return;
    editTemplatePrimeById(id, { name });
}

function deletePrime(id) {
    removeTemplatePrimeById(id);
}

function startTargetEdit(id, currentName) {
    targetEditNameById.value[id] = currentName ?? "";
}

function applyTargetEdit(id) {
    const name = targetEditNameById.value[id];
    if (name === undefined) return;
    editTemplateTargetById(id, { name });
}

function deleteTarget(id) {
    removeTemplateTargetById(id);
}

// Row-level edit buffers for table data
const primeRowEdits = ref({}); // key: `${primeId}:${rowIndex}` -> { prime, equivalent, value }
const targetRowEdits = ref({});

function rowKey(id, index) {
    return `${id}:${index}`;
}

// Prime row handlers
function startPrimeRowEdit(id, index, row) {
    const key = rowKey(id, index);
    primeRowEdits.value[key] = {
        prime: row.prime ?? "",
        equivalent: row.equivalent ?? "",
        value: row.value ?? "",
    };
}

function ensurePrimeRowBuffer(id, index, row) {
    const key = rowKey(id, index);
    if (!primeRowEdits.value[key]) {
        startPrimeRowEdit(id, index, row ?? { prime: "", equivalent: "", value: "" });
    }
    return key;
}

function applyPrimeRowEdit(id, index) {
    const key = rowKey(id, index);
    const buf = primeRowEdits.value[key];
    if (!buf) return;
    const item = templatePrimes.value.find(p => p.id === id);
    if (!item || !Array.isArray(item.data)) return;
    const newData = item.data.map((r, i) => i === index ? { ...r, ...buf } : r);
    editTemplatePrimeById(id, { data: newData });
    toast.success("Prime row updated");
}

function deletePrimeRow(id, index) {
    const prime = templatePrimes.value.find(p => p.id === id);
    if (!prime) return;
    const data = Array.isArray(prime.data) ? prime.data : [];
    const updated = data.filter((_, i) => i !== index);
    editTemplatePrimeById(id, { data: updated });
    toast.error("Prime row deleted");
}


function addPrimeRow(id) {
    const prime = templatePrimes.value.find(p => p.id === id);
    const data = Array.isArray(prime?.data) ? prime.data : [];
    const updated = data.concat([{ prime: "", equivalent: "", value: "" }]);
    editTemplatePrimeById(id, { data: updated });
    toast.success("Prime row added");
}

// Target row handlers
function startTargetRowEdit(id, index, row) {
    const key = rowKey(id, index);
    targetRowEdits.value[key] = {
        target: row.target ?? "",
        equivalent: row.equivalent ?? "",
        value: row.value ?? "",
    };
}

function ensureTargetRowBuffer(id, index, row) {
    const key = rowKey(id, index);
    if (!targetRowEdits.value[key]) {
        startTargetRowEdit(id, index, row ?? { target: "", equivalent: "", value: "" });
    }
    return key;
}

function applyTargetRowEdit(id, index) {
    const key = rowKey(id, index);
    const buf = targetRowEdits.value[key];
    if (!buf) return;
    const target = templateTargets.value.find(t => t.id === id);
    if (!target) return;
    const data = Array.isArray(target.data) ? target.data : [];
    if (index < 0 || index >= data.length) return;
    const updated = data.map((row, i) => i === index ? { ...row, ...buf } : row);
    editTemplateTargetById(id, { data: updated });
    toast.success("Target row updated");
}

function deleteTargetRow(id, index) {
    const target = templateTargets.value.find(t => t.id === id);
    if (!target) return;
    const data = Array.isArray(target.data) ? target.data : [];
    const updated = data.filter((_, i) => i !== index);
    editTemplateTargetById(id, { data: updated });
    toast.error("Target row deleted");
}

function addTargetRow(id) {
    const target = templateTargets.value.find(t => t.id === id);
    const data = Array.isArray(target?.data) ? target.data : [];
    const updated = data.concat([{ target: "", equivalent: "", value: "" }]);
    editTemplateTargetById(id, { data: updated });
    toast.success("Target row added");
}



async function saveAllTemplates() {
   try {
       if (!survey.value || !survey.value.id) {
           console.error('No survey to save templates against');
           return;
       }
       await router.post(
           route('templates.save', { 
               team: survey.value.group.project.team_id, 
               project: survey.value.group.project.uuid, 
               survey: survey.value.id 
           }),
           {
               templatePrimes: templatePrimes.value,
               templateTargets: templateTargets.value,
           },
           { preserveScroll: true, preserveState: true }
       );
       toast.success("Templates saved successfully");
   } catch (e) {
       console.error('Failed to save templates', e);
       toast.error("Failed to save templates");
   }
}


function getInitials(name) {
    if (!name) return "";
    const parts = name.trim().split(" ");
    const first = parts[0]?.[0] || "";
    const last = parts.length > 1 ? parts[parts.length - 1][0] : "";
    return (first + last).toUpperCase();
}
</script>

<template>
        <div class="bg-slate-200 min-h-[100vh] text-[#323232] page-container">
        <div class="flex flex-col max-w-full mx-auto">
            <div class="sticky top-0 flex flex-col bg-slate-200">
                <div class="flex justify-between h-[70px] py-2 px-4 bg-white shadow-md header-container"
                  >
                    <div class="flex items-center gap-4 px-1">
                  
                        <div
                            class="active:scale-[0.85] transition-all duration-200"
                        >
                            <Link
                                :href="route('survey2.index', survey)"
                                class=""
                            >
                                <svg
                                    data-v-8cc99868=""
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    class="w-[60px] hover:stroke-[#15919A] transition-colors duration-200"
                                >
                                    <path
                                        data-v-8cc99868=""
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m11.25 9-3 3m0 0 3 3m-3-3h7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                    ></path>
                                </svg>
                            </Link>
                        </div>

                        <div
                            class="text-[24px] rounded-lg w-auto h-[40px] justify-center items-center flex font-bold text-[#323232]"
                        >
                         Back to survey
                        </div>
                    </div>

                    <div
                        class="w-[50px] h-[50px] flex items-center justify-center rounded-full bg-[#15919A] text-white text-[20px] cursor-default"
                        :title="$page.props.auth.user.name"
                    >
                        {{ getInitials($page.props.auth.user.name) }}
                    </div>

                    </div>

                    
                </div>
            </div>
        </div>
        <div class="fixed translate-y-[-50%] translate-x-[-50%] top-[50%] left-[50%]">
        
<div class="w-[1000px]">
<div class="tabs tabs-lift ">
  <label class="tab">
    <input type="radio" name="my_tabs_1" checked="checked" />
    Primes
  </label>
  <div class="tab-content bg-base-100 border-base-300 p-6">
    <div v-for="prime in templatePrimes" :key="prime.id">
     
      <ul class="list bg-base-100 rounded-box shadow-md">
  
  
  <li class="list-row">
      {{ prime.name }}
  </li>
  <details>
    <summary class="cursor-pointer font-semibold py-2 px-4 bg-base-200 rounded hover:bg-base-300 transition-colors duration-150 mb-2">
      Show/Hide Prime Data Table
    </summary>
    <div class="overflow-x-auto overflow-y-auto max-h-[400px] mt-2 rounded-lg border border-base-300">
      <table class="table table-zebra w-full">
        <thead class="bg-base-200 sticky top-0 z-10">
          <tr>
            <th class="w-20">#</th>
            <th>Prime</th>
            <th>Equivalent</th>
            <th class="min-w-40">Value</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!prime.data || prime.data.length === 0">
            <td colspan="4" class="text-center text-sm text-base-content/60 py-4">No rows</td>
          </tr>
          <tr v-for="(row, index) in prime.data" :key="index">
            <td class="font-medium align-top">{{ index + 1 }}</td>
            <td class="align-top">
              <input class="input input-bordered input-xs w-full" :value="primeRowEdits[rowKey(prime.id, index)]?.prime ?? row.prime" @focus="startPrimeRowEdit(prime.id, index, row)" @input="primeRowEdits[ensurePrimeRowBuffer(prime.id, index, row)].prime = $event.target.value" />
            </td>
            <td class="align-top">
              <input class="input input-bordered input-xs w-full" :value="primeRowEdits[rowKey(prime.id, index)]?.equivalent ?? row.equivalent" @focus="startPrimeRowEdit(prime.id, index, row)" @input="primeRowEdits[ensurePrimeRowBuffer(prime.id, index, row)].equivalent = $event.target.value" />
            </td>
            <td class="align-top">
              <input class="input input-bordered input-xs w-full" :value="primeRowEdits[rowKey(prime.id, index)]?.value ?? row.value" @focus="startPrimeRowEdit(prime.id, index, row)" @input="primeRowEdits[ensurePrimeRowBuffer(prime.id, index, row)].value = $event.target.value" />
            </td>
            <td class="align-top w-40">
              <div class="flex gap-2">
                <button class="btn btn-xs btn-primary" @click="applyPrimeRowEdit(prime.id, index)">Update</button>
                <button class="btn btn-xs btn-error" @click="deletePrimeRow(prime.id, index)">Delete</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="mt-3 flex items-center gap-2">
      <input class="input input-bordered input-sm" :value="prime.name" @input="startPrimeEdit(prime.id, prime.name); primeEditNameById[prime.id] = $event.target.value" placeholder="New name" />
      <button class="btn btn-sm btn-primary" @click="applyPrimeEdit(prime.id)">Update</button>
      <button class="btn btn-sm btn-error" @click="deletePrime(prime.id)">Delete</button>
    </div>
    <div class="mt-2">
      <button class="btn btn-xs" @click="addPrimeRow(prime.id)">Add Row</button>
    </div>
  </details>
</ul>
    </div>

</div>

  <label class="tab">
    <input type="radio" name="my_tabs_1"  />
    Targets
  </label>
  <div class="tab-content bg-base-100 border-base-300 p-6">
    <div v-for="target in templateTargets" :key="target.id">
    
          <ul class="list bg-base-100 rounded-box shadow-md">
  
  <li class="list-row">
      {{ target.name }}
  </li>
  <details>
    <summary class="cursor-pointer font-semibold py-2 px-4 bg-base-200 rounded hover:bg-base-300 transition-colors duration-150 mb-2">
      Show/Hide Target Data Table
    </summary>
    <div class="overflow-x-auto overflow-y-auto max-h-[400px] mt-2 rounded-lg border border-base-300">
      <table class="table table-zebra w-full">
        <thead class="bg-base-200 sticky top-0 z-10">
          <tr>
            <th class="w-20">#</th>
            <th>Target</th>
            <th>Equivalent</th>
            <th class="min-w-40">Value</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!target.data || target.data.length === 0">
            <td colspan="4" class="text-center text-sm text-base-content/60 py-4">No rows</td>
          </tr>
          <tr v-for="(row, index) in target.data" :key="index">
            <td class="font-medium align-top">{{ index + 1 }}</td>
            <td class="align-top">
              <input class="input input-bordered input-xs w-full" :value="targetRowEdits[rowKey(target.id, index)]?.target ?? row.target" @focus="startTargetRowEdit(target.id, index, row)" @input="targetRowEdits[ensureTargetRowBuffer(target.id, index, row)].target = $event.target.value" />
            </td>
            <td class="align-top">
              <input class="input input-bordered input-xs w-full" :value="targetRowEdits[rowKey(target.id, index)]?.equivalent ?? row.equivalent" @focus="startTargetRowEdit(target.id, index, row)" @input="targetRowEdits[ensureTargetRowBuffer(target.id, index, row)].equivalent = $event.target.value" />
            </td>
            <td class="align-top">
              <input class="input input-bordered input-xs w-full" :value="targetRowEdits[rowKey(target.id, index)]?.value ?? row.value" @focus="startTargetRowEdit(target.id, index, row)" @input="targetRowEdits[ensureTargetRowBuffer(target.id, index, row)].value = $event.target.value" />
            </td>
            <td class="align-top w-40">
              <div class="flex gap-2">
                <button class="btn btn-xs btn-primary" @click="applyTargetRowEdit(target.id, index)">Save</button>
                <button class="btn btn-xs btn-error" @click="deleteTargetRow(target.id, index)">Delete</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="mt-3 flex items-center gap-2">
      <input class="input input-bordered input-sm" :value="target.name" @input="startTargetEdit(target.id, target.name); targetEditNameById[target.id] = $event.target.value" placeholder="New name" />
      <button class="btn btn-sm btn-primary" @click="applyTargetEdit(target.id)">Update</button>
      <button class="btn btn-sm btn-error" @click="deleteTarget(target.id)">Delete</button>
    </div>
    <div class="mt-2">
      <button class="btn btn-xs" @click="addTargetRow(target.id)">Add Row</button>
    </div>
  </details>


</ul>
    </div>
  </div>

 
 
</div>
  <div class="mt-4 flex justify-end">
    <button class="btn btn-primary" @click="saveAllTemplates">Save</button>
  </div>
</div>
        </div>
</template>

<style scoped>
</style>
