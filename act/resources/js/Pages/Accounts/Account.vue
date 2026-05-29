<script setup>
import Layout from '@/Layouts/Layout.vue';
import SecondaryButton from '../../Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { Link } from '@inertiajs/vue3';
import Helpers from '../../Mixins/Helpers';
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps(['users', 'keyw']);

const keyword = ref(null);

const { datetime } = Helpers();

onMounted(() => {
    keyword.value = props.keyw;
});

const search = () => {
    router.get(route('accounts.index'), {
        keyword: keyword.value
    }, {
        onSuccess: () => {
            keyword.value = props.keyw;
            toast.success(props.projects.data.length + ' search results');
        },
        preserveState: true,
    })
}
</script>

<template>
	<Layout title="Accounts">
		<template #header>
			<div class="inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
					<path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
				</svg>&nbsp;Accounts
            </div>
		</template>
		<div>
			<div class="mx-w-8xl mx-auto px-2">
				<div class="flex flex-col">
					<div class="overflow-x-auto sm:rounded-lg">
						<div class="inline-block min-w-full align-middle">
							<div class="flex justify-between">
								<div class="ml-1">
                                    <label for="table-search" class="sr-only"></label>
                                    <div class="relative mt-1">
                                        <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                                        </div>
                                        <input type="text" id="table-search" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-80 pl-10 p-2.5" placeholder="Search user" @keydown.enter="search" v-model="keyword">
                                    </div>
                                </div>
								<div>
                                    <Link :href="route('accounts.create')">
                                        <SecondaryButton>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
												<path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
											</svg>
                                            &nbsp;Create account
                                        </SecondaryButton>
                                    </Link>
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
												Email
											</th>
											<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
												Created
											</th>
											<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
												Status
											</th>
											<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
												Role
											</th>
											<th class="py-3 px-6 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
												Valid until
											</th>
										</tr>
									</thead>
									<tbody class="bg-white divide-y divide-gray-200">
										<tr class="hover:bg-gray-100" v-for="(user, key) in users.data" :key="key">
											<td class="py-4 px-6 text-sm font-medium text-gray-900 whitespace-nowrap">
												<span class="text-gray-500">
													<strong>
														{{ user.id }}
													</strong>
												</span>
											</td>
											<td class="py-4 px-6 text-sm font-medium text-gray-900 whitespace-nowrap">
												<Link :href="route('accounts.dashboard', user)">
													<span class="text-blue-500 font-medium">
														{{ user.name }}
													</span>
												</Link>
											</td>
											<td class="py-4 px-6 text-sm font-medium text-gray-900 whitespace-nowrap">
												<span class="text-gray-500">
													<code>
														{{ user.email }}
													</code>
												</span>
											</td>
											<td class="py-4 px-6 text-sm font-medium text-gray-900 whitespace-nowrap">
												<span class="text-gray-500 text-xs">
													{{ datetime(user.created_at) }}
												</span>
											</td>
											<td class="py-4 px-6 text-sm font-medium text-gray-900 whitespace-nowrap">
												<span class="text-xs text-green-500" v-if="user.is_active">
													Active
												</span>
												<span class="text-xs text-red-500" v-else>
													Inactive
												</span>
											</td>
											<td class="py-4 px-6 text-sm font-medium text-gray-900 whitespace-nowrap">
												<span class="text-xs text-gray-500">
													{{ user.role }}
												</span>
											</td>
											<td class="py-4 px-6 text-sm font-medium text-gray-900 whitespace-nowrap">
												<span class="text-xs text-gray-500">
													<template v-if="user.role === 'root'">
														Indifinite
													</template>
													<template v-else>
														{{ datetime(user.expired_at) }}
													</template>
												</span>
											</td>
										</tr>
										<tr v-if="users.data.length == 0">
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
						</div>
					</div>
				</div>
				<Pagination :links="users.links" />
			</div>
		</div>
	</Layout>
</template>

<style scoped="true">
    .c {
        height: calc(100vh - 160px);
    }
</style>