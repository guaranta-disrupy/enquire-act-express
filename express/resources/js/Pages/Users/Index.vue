<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue' 
    import S4Pagination from '@/S4/Pagination.vue' 
    import S4InputSelect from '@/S4/InputSelect.vue'
    import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
    import { faChevronRight, faCopy, faEdit, faSync, faTrashAlt } from '@fortawesome/free-solid-svg-icons'
    import { onMounted, ref } from 'vue';
    import { useForm, usePage, Link } from "@inertiajs/vue3";
    import { useToast } from "vue-toastification";
    import { S4Table } from '@/S4'
    import helpers from '@/helpers.js'

    const { toast, moment } = helpers()
   
    const props = defineProps(['user','users']);
    const modals = ref({project: {
                            //add: false
                            add_edit: false,
                            method: 'add',
                            data: null
                        }});
    const faIcon = ref({
                        cheveron_right: faChevronRight,
                        trash: faTrashAlt,
                        copy: faCopy,
                        edit: faEdit,
                        sync: faSync
                    });
    const form = useForm({
        default_team_member:false,
        deleted_at: null,
        system_user : '',
        id:'',
    },{
         resetOnSuccess: true,
    });

    const form2 = useForm();
    
    onMounted(()=>{
        props.users.data.forEach((u)=>{
            u['isActive'] = u.deleted_at == null ? 1:0;
        });
    });

    const disableRoleUpdate = (system_user,role = false) => {
        // disable dropdown function

        // disable role dropdown when system user is admin
        // if(role === true && props.user.system_user === 'admin')
        //     return true

        // disable dropdown when user is admin and to update user is also admin or root admin
        if(props.user.system_user == 'admin' && (system_user == 'admin' || system_user == 'root_admin')) {
            return true
        }else {
            return false
        }
    };

    const adminLists = () => {
        if (props.user.system_user == 'root_admin') {
            // set disable false if root admin is true
            return [
                {text: 'Root Admin', value: 'root_admin', disabled: false }, 
                {text: 'Admin', value: 'admin' }, 
                {text: 'User', value: 'user' }
            ]
        }else {
            // set disable true if root admin is true
            return [
                {text: 'Admin', value: 'root_admin', disabled: true }, 
                {text: 'Admin', value: 'admin' }, 
                {text: 'User', value: 'user' }
            ]
        }
    }; 
        
    const updateStatus = (user) => {
        // check if root user
        rootUserOnly(user)
        // deactivate user request
        if(user.deleted_at === null){
            form2.delete(route('users.delete',user.id),{
                preserveScroll: true,
                onSuccess: () => { 
                    toast.warning(`User status successfully deactivated`, {
                    timeout: 2000
                    })
                    form.processing = false; 
                },
                onError: () => {
                }
            }) 
        }else{
            // activate user request
            form.id = user.id
            form.post(route('users.update'), {
                preserveScroll: true,
                onSuccess: () => {
                   toast.success(`User status successfully activated`, {
                    timeout: 2000
                    })
                    form.reset('deleted_at')
                    form.reset('id')
                },
                onError: () => {
                }
            })
        }
    };        
    const updateRole = (user, member = 0) => {
        // set parameters to update
        let role = member == 1 ? 'team member' : 'role'
        form.id = user.id
        form.system_user = user.system_user    
        form.default_team_member  = parseInt(user.default_team_member)
            // check if root user
        rootUserOnly(user,true)
        // update role request
        form.post(route('users.update'), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`User ${role} successfully updated`, {
                timeout: 2000
                })
                form.reset('system_user')
                form.reset('id')
            },
            onError: () => {
            }
        })
    };
   
    const rootUserOnly = (user,role=false) => {
        // check root user function
        if((props.user.system_user === "user" && role === false )){ //  || (props.user.system_user !== "root_admin" && role === true)
            toast.warning(`You are now allowed to do this function. \n Please contact the system administrator.`, {
                timeout: 2000
            })
            throw "you are not a super admin!";
        }
    };
    const headers = ref([
    {
        label:'NAME',
        class:"pl-6 py-5 font-semibold w-1/3 rounded-tl-xl"
    },
    {
        label:'EMAIL',
        class:"pr-6 py-2 font-semibold w-1/3"
    },
    {
        label:'REGESTERED BY',
        class:"px-6 py-2 font-semibold w-1/4"
    },
    {
        label:'STATUS',
        class:" py-2 font-semibold w-1/6"
    },
    {
        label:'ROLE',
        class:"w-1/6 rounded-tr-xl"
    },
    {
        label:'PRIORITY MEMBER',
        class:"w-1/6 rounded-tr-xl"
    }
])
</script>

<template>
    <app-layout title="Users">
        <template #topbar>
            <div class="">
                <div class="flex flex mt-4 mb-4">
                    <p class="font-bold text-gray-700 prjText">
                        Users
                    </p> 
                </div>                      
            </div>
        </template>
        <template #content>
            <S4Table :headers="headers">
                <template #body> 
                    <tr class="text-md border-b border-l border-r border-gray-100 text-slate-500 hover:bg-slate-50" v-for="(accounts, index) in props.users.data" :key="index" 
                    :class="{ 'line-through text-red-700 font-bold': accounts.deleted_at !== null  }">
                        <td class="px-6 py-4" >
                            <Link :href="route('show.logs', accounts.id)" class="font-bold">
                                <span>
                                    {{ accounts.name }}
                                </span>
                            </Link>
                        </td>
                        <td class="truncate">
                            <span>
                                {{ accounts.email }}
                            </span>
                        </td>
                        <td>
                            <div class="px-6 py-4" >
                                {{ moment(new Date(accounts.created_at)).format('MM/DD/YY LT') }}
                            </div>
                        </td>
                        <template v-if="user.system_user != 'user'">
                            <td>
                                <s4-input-select class="h-10 w-3/4"
                                    @change="updateStatus(accounts)"
                                    :disabled="disableRoleUpdate(accounts.system_user)" 
                                    :lists="[
                                        {text: 'Active', value: 1 }, 
                                        {text: 'Inactive', value: 0 }
                                    ]" v-model="accounts.isActive" />
                                    
                            </td>
                            <td>
                                <s4-input-select class="h-10 w-3/4"
                                    @change="updateRole(accounts)"
                                    :disabled="disableRoleUpdate(accounts.system_user, true)" 
                                    :lists="adminLists()" 
                                    v-model="accounts.system_user" />
                            </td>
                              <td>
                                <s4-input-select class="h-10 w-3/4"
                                    :lists="[
                                        {text: 'Enable', value: 1}, 
                                        {text: 'Disable', value: 0}
                                    ]" 
                                    @change="updateRole(accounts,1)"
                                    v-model="accounts.default_team_member" />
                            </td>
                        </template>
                    </tr>       
                </template>
            </S4Table>
            <s4-pagination :links="users.links"/> 
        </template>
    </app-layout>
</template>



<style scoped>

td span h1{
    display: none;
    color:black;
}

@media (max-width: 768px) {
    th{
        display: none; 
    }
    td{
        display: flex; 
        flex-wrap: wrap; 
        justify-content: center;
        width: 100%;
        text-align: center;
    }
    td span h1{
        display:block;
        text-align: center;
        text-decoration: underline;
        font-size: 1.2rem;
        color: rgb(226, 135, 51);
}
  }
.fade-enter-active,
.fade-leave-active {
  transition: opacity .4s linear;
}

.fade-enter,
.fade-leave-to {
  opacity: 0;
}

.prjList {
    border-top:0.1rem solid #F3F4F6;  
}

/* .prjList:nth-child(even) {
    background: #FAFBFB; 
} */

.prjList:hover {
    background: #F3F4F6;
}

.ft {
    font-size:1.5rem;
    padding:0.2rem;
}

.bt {
    border-radius:50%;
    width:2rem;
    height:2rem;
}

.addPrj {
    margin-left: auto;
    order: 2;
}

.prjText {
    font-weight:900;
    font-size:1rem;
    justify-content: center;
    align-items: center;
    margin-left:0.5rem;
    display:flex;
}

.headerBtn {
    width:3.25rem;
}

.headerBtn:hover {
     border-color:lightgrey;
}

</style>
