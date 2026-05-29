<script setup>
    import { ref } from 'vue';
    import {  Link, useForm } from "@inertiajs/vue3";
    import AppLayout from '@/Layouts/AppLayout.vue'
    import S4Input from '@/S4/Input.vue'
    import S4Sidebar from '@/S4/Sidebar.vue'
    import S4Button from '@/S4/Button.vue'
    import S4Pagination from '@/S4/Pagination.vue'
    import S4DropDown from '@/S4/DropDown.vue'
    import S4InputSelect from '@/S4/InputSelect.vue'
    import { S4Table } from '@/S4'
    import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
    import { faChevronRight, faCopy, faEdit, faSync, faTrashAlt } from '@fortawesome/free-solid-svg-icons'
    import helpers from '@/helpers.js'
    
    const { moment, toast } = helpers()
    const props = defineProps(['teams']);
    const faIcon = ref({
                cheveron_right: faChevronRight,
                trash: faTrashAlt,
                copy: faCopy,
                edit: faEdit,
                sync: faSync
            });
    const headers = ref([
        {
            label:'NAME',
             class:"pl-6 py-5 font-semibold w-1/3 rounded-tl-xl"
        },
        {
            label:'OWNER',
            class:"pr-6 py-2 font-semibold w-1/6"
        },
        {
            label:'CREATED',
            class:"px-6 py-2 font-semibold w-1/6"
        },
        {
            label:'PERSONAL TEAM',
            class:"px-6 py-2 font-semibold w-1/6"
        },
        {
            label:'LAST UPDATED',
            class:"px-6 py-2 font-semibold w-1/6"
        },
        {
            label:'AUTO ADD',
            class:"px-6 py-2 font-semibold w-1/6"
        }
    ])

    const form  = useForm({})

    const addPriorityUsers = (team) => {
        console.log(route('teams.add_members',[team]))
        form.post(route('teams.add_members',[team.id]), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Successfully updated`, {
                timeout: 2000
                })
            },
            onError: () => {
            }
        })

    }

</script>

<template> 
     <app-layout title="Teams">
        <template #topbar>
            <div class="ml-4 mr-4 py-4 px-4">
                <div class="flex">
                    <Link :href="route('teams.index')">
                        <s4-button size="md" class="headerBtn" color="outline-transparent">
                            <span class="pt-1">
                                <font-awesome-icon :icon="faIcon.sync" style="font-size:1.5rem;" class="text-ssr-blue2" />
                            </span>
                        </s4-button>                
                    </Link>
                    <p class="font-bold text-gray-700 prjText">
                        Teams
                    </p> 
                </div>                      
            </div>
        </template>
        <template #content>    
                <div class="" v-if=" props.teams.data.length > 0">
                <S4Table :headers="headers">
                        <template #body>
                            <tr class="text-md border-b border-l border-r border-gray-100 text-slate-500 hover:bg-slate-50"  v-for="(team, index) in props?.teams.data" :key="index">
                                <td class="px-6 py-4 truncate" >
                                    <Link :href="route('teams.show2', team.id)">
                                        <span class="hover:text-gray-700 ">
                                            {{ team.name }}
                                        </span>
                                    </Link>
                                </td>
                                <td class="px-6 py-4 truncate" >
                                    <span class="hover:text-gray-700 ">
                                    {{ team.user?.name }}
                                    </span>
                                </td>
                               <td class="px-6 py-4 truncate" >
                                    <span class="hover:text-gray-700 ">
                                        {{ moment(new Date(team.created_at)).format('MM/DD/YY LT') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 truncate" >
                                     <span :class=" team.personal_team ? 'text-green-600' : 'text-orange-600'">
                                        {{ team.personal_team ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 truncate" >
                                    <span class="hover:text-gray-700 ">
                                        {{ moment(new Date(team.updated_at)).format('MM/DD/YY LT') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 truncate" >
                                    <button @click="addPriorityUsers(team)">Add Priority Users</button>
                                </td>
                            </tr>                 
                        </template>
                    </S4Table>
                </div>
                <s4-pagination :links="props.teams.links"/>
        </template>
     </app-layout>
</template>


<style scoped>



</style>