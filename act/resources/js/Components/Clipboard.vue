<template>
    <div class="col-span-6 sm:col-span-4">
        <div class="flex flex-wrap items-stretch w-full mb-4 relative">
            <div class="flex -mr-px">
                <span class="flex items-center leading-normal bg-grey-lighter rounded-md rounded-r-none border border-r-0 border-grey-light px-3 whitespace-no-wrap text-gray-500 text-sm">
                    <slot name="label" />
                </span>
            </div>				
            <input type="text" class="flex-shrink flex-grow flex-auto leading-normal w-px flex-1 h-10 px-3 relative form-input" :value="value">
            <div class="flex -mr-px">
                <button class="background-blue flex items-center leading-normal bg-grey-lighter rounded-md rounded-l-none border border-l-0 border-grey-light px-3 whitespace-no-wrap text-white text-sm uppercase" v-clipboard:copy="value" v-clipboard:success="copied">
                    <span v-if="copy">Copied</span>
                    <span v-else>Copy</span>
                </button>
            </div>	
        </div>
    </div>
</template>
<script>
    import { defineComponent } from "vue"
    export default defineComponent({
        props: ['value'],
        data() {
            return {
                copy: false,
            }
        },

        methods: {
            copied() {
                this.copy = true
                let t = setTimeout(() => {
                    this.copy = false
                    clearTimeout(t)
                }, 5000)
                
            }
        }
    })
</script>

<style scoped>
    .form-input {
        border-color: rgb(229, 231, 235);
        border-width: 1px;
        border-radius: 0;
    }

    .form-input:active {
        border-color: #d2d6dc;
        border-width: 1px;
    }
</style>