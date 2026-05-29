<script setup>
import Layout from '@/Layouts/Layout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Link } from '@inertiajs/vue3';
import Helpers from '../../Mixins/Helpers';
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps(['teams', 'keyw']);

const keyword = ref(null);

onMounted(() => {
    keyword.value = props.keyw;
});

const { datetime } = Helpers();

const search = () => {
    router.get(route('teams2.index'), {
        keyword: keyword.value
    }, {
        onSuccess: () => {
            keyword.value = props.keyw;
            toast.success(props.teams.data.length + ' search results');
        },
        preserveState: true,
    })
}
</script>
<template>
    <Layout title="Teams">
        <template #header>
			<div class="inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                </svg>&nbsp;Teams
            </div>
		</template>
        <div>
            <div class="mx-w-8xl mx-auto px-2">
                <div class="flex flex-col">
                    <div class="overflow-x-auto overflow-y-hidden sm:rounded-lg">
                        <div class="inline-block min-w-full align-middle">
                            <div class="flex justify-between">
								<div class="ml-1">
                                    <label for="table-search" class="sr-only"></label>
                                    <div class="relative mt-1">
                                        <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                                        </div>
                                        <input type="text" id="table-search" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-80 pl-10 p-2.5" placeholder="Search team" @keydown.enter="search" v-model="keyword">
                                    </div>
                                </div>
							</div>
                            <div class="mt-2 rounded-xl shadow border overflow-auto c bg-white">
                                <table class="min-w-full divide-y divide-gray-200 table-fixed">
                                    <thead class="bg-white">
                                        <tr>
                                            <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                #
                                            </th>
                                            <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                Name
                                            </th>
                                            <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                Owner
                                            </th>
                                            <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                Personal Team
                                            </th>
                                            <th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                                Last Updated
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr class="hover:bg-gray-100" v-for="(team, key) in teams.data" :key="team.id">
                                            <td class="py-3 px-6 text-sm font-medium text-gray-900 whitespace-nowrap">
                                                <span class="text-gray-500">
                                                    <strong>
                                                        {{ key + 1 }}
                                                    </strong>
                                                </span>
                                            </td>
                                            <td class="py-3 px-6 text-sm font-medium text-gray-900 whitespace-nowrap">
                                                <Link class="flex items-center text-blue-500" :href="route('teams2.show', team.id)">
                                                    <span class="text-blue-500 font-medium">
                                                        {{ team.name }}
                                                    </span>
                                                </Link>
                                            </td>
                                            <td class="py-3 px-6 text-sm font-medium text-gray-900 whitespace-nowrap">
                                                <span class="text-gray-500">
                                                    {{ team.owner.name }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-6 text-sm font-medium text-gray-900 whitespace-nowrap">
                                                <span class="text-xs text-green-500" v-if="team.personal_team">
                                                    Yes
                                                </span>
                                                <span class="text-xs text-gray-500" v-else>
                                                    No
                                                </span>
                                            </td>
                                            <td class="py-3 px-6 text-sm font-medium text-gray-900 whitespace-nowrap">
                                                <span class="text-xs text-gray-500">
                                                    {{ datetime(team.created_at) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr v-if="teams.data.length == 0">
                                            <td colspan="7" class="uppercase py-4 px-6 text-sm font-medium text-gray-900 whitespace-nowrap text-center">
                                                No result found
												<span v-if="keyw">
													for <em class="text-gray-500"> {{ keyw }}</em>
												</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <Pagination :links="teams.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
<style scoped="true">
    .c {
        height: calc(100vh - 160px);
    }
</style>