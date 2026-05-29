<script setup>
import { router } from '@inertiajs/vue3';
import Layout from '@/Layouts/Layout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Helpers from '../../Mixins/Helpers';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import JetLabel from '@/Jetstream/Label.vue';
import CustomInput from '@/Components/Input.vue';
import CustomButton from '@/Components/Button.vue';

const { toast, datetime } = Helpers();

const props = defineProps({
    projects: Object,
    keyw: String,
});
const keyword = ref('');
const newProjectModal = ref(false);

const search = () => {
    router.get(route('projects.index'), {
        keyword: keyword.value
    }, {
        onSuccess: () => {
            keyword.value = props.keyw;
            toast.success(props.projects.data.length + ' search results');
        },
        preserveState: true,
    })
}

onMounted(() => {
    keyword.value = props.keyw;
});

const form = useForm({
    name: ''
});

const createProject = () => {
    form.post(route('projects.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.success("Project successfully created");
            newProjectModal.value = false;
        }
    });
};

</script>

<template>
    <Layout title="Projects">
        <template #header>
            <div class="inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>&nbsp;Projects
            </div>
        </template>
        <div>
            <div class="max-w-8xl mx-auto px-2">
                <div class="flex flex-col">
                    <div class="overflow-x-auto sm:rounded-lg">
                        <div class="inline-block min-w-full align-middle">
                            <div class="flex justify-between">
                                <div>
                                    <label for="table-search" class="sr-only"></label>
                                    <div class="relative mt-1">
                                        <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                                        </div>
                                        <input type="text" id="table-search" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-80 pl-10 p-2.5" placeholder="Search projects" @keydown.enter="search" v-model="keyword">
                                    </div>
                                </div>
                                <div>
                                    <!-- <Link class="btn-indigo mt-1" :href="route('projects.create')"> -->
                                        <SecondaryButton @click="newProjectModal=true">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-auto" viewBox="0 0 20 20" fill="currentColor">
                                              <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                            </svg>
                                            &nbsp;Create Project
                                        </SecondaryButton>
                                    <!-- </Link> -->
                                </div>
                            </div>
                            <div class="mt-1 rounded-xl border overflow-hidden shadow bg-white">
                                <div class="c overflow-auto">
                                    <table class="min-w-full divide-y divide-gray-200 table-fixed" >
                                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                            <tr>
                                                <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                    Name
                                                </th>
                                                <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                    Code
                                                </th>
                                                <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                    Owner
                                                </th>
                                                <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                    Modified
                                                </th>
                                                <th scope="col" class="p-4">
                                                    <span class="sr-only">Edit</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr class="hover:bg-gray-100" v-for="project in projects.data" :key="project.code">
                                                <td class="py-4 px-6 text-sm font-medium text-gray-900 whitespace-nowrap">
                                                    <Link class="focus:text-indigo-500 inline-flex items-center" :href="route('groups.index', project.uuid)">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                        </svg>&nbsp;{{ project.name }}
                                                    </Link>
                                                </td>
                                                <td class="py-4 px-6 text-sm font-medium text-gray-500 whitespace-nowrap">
                                                    <code>{{ project.code }}</code>
                                                </td>
                                                <td class="py-4 px-6 text-sm font-medium text-gray-900 whitespace-nowrap">
                                                    <span class="text-xs text-gray-500">
                                                        {{ project.user.name }}
                                                    </span>
                                                </td>
                                                <td class="py-4 px-6 text-sm font-medium text-gray-900 whitespace-nowrap">
                                                    <span class="text-xs text-gray-500">
                                                        {{ datetime(project.updated_at) }}
                                                    </span>
                                                </td>
                                                <td class="py-4 px-6 text-sm font-medium text-right whitespace-nowrap">
                                                    <Link class="text-gray-500 hover:underline" :href="route('projects.settings', project)">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                                                        </svg>
                                                    </Link>
                                                </td>
                                            </tr>
                                            <tr v-if="projects.data.length == 0">
                                                <td colspan="5" class="uppercase py-4 px-6 text-sm font-medium text-gray-900 whitespace-nowrap text-center">
                                                    No result found
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <pagination class="p-2" :links="projects.links" />
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </Layout>
    <JetDialogModal :show="newProjectModal" @close="newProjectModal=false" maxWidth="2xl">
        <template #title>
            New Project
        </template>
        <template #content>
            <div class="grid grid-cols-10 gap-6">
                <div class="col-span-8">
                    <JetLabel value="Project Owner" />

                    <div class="flex items-center mt-2">
                        <img :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name" class="w-12 h-12 rounded-full object-cover">

                        <div class="ml-4 leading-tight">
                            <div>{{ $page.props.auth.user.name }}</div>
                            <div class="text-gray-700 text-sm">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-8">
                    <CustomInput label="Name" type="text" class="mt-1 block w-full" v-model="form.name" autofocus />
                    <JetInputError :message="form.errors.name" class="mt-2" />
                </div>
            </div>
            
        </template>
        <template #footer>
            <SecondaryButton @click="newProjectModal=false" class="mr-2">
				nevermind
			</SecondaryButton>
			<CustomButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="createProject">
				<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>&nbsp;Save
			</CustomButton>
        </template>
    </JetDialogModal>
</template>

<style scoped="true">
    .c {
        height: calc(100vh - 175px);
    }
</style>
