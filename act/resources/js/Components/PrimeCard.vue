<template>
    <div
        class="bg-white shadow border-gray-300 border flex flex-col col-span-1 rounded-xl"
    >
        <!-- Value and Index -->
        <!-- <div class="border-b border-gray-300 p-2">
            <span class="text-xs text-gray-600 bg-gray-100 rounded p-1 font-semibold">
                #<slot name="n"></slot>
            </span>
            <span class="text-xs text-gray-600 bg-gray-100 rounded p-1 ml-1 font-semibold">
                Index: <slot name="index"></slot>
            </span>
        </div> -->

        <!-- Prime, Equivalent, and Logic -->
        <!-- <div class="p-3 mt-auto"> -->
        <!-- Prime Name -->
        <!-- <div class="text-sm py-1">
                <slot name="prime"></slot>
            </div>
            <div class="hidden sm:block">
                <div class="pb-3 mt-3">
                    <div class="border-t border-gray-200" />
                </div>
            </div> -->

        <!-- Equivalent and Logic -->
        <!-- <div class="grid grid-cols-4 gap-4">
                <div class="text-xs text-gray-500">
                    Equivalent
                </div>
                <div class="col-span-3 text-sm text-gray-600 pt-2 pb-3">
                    <slot name="equivalent"></slot>
                </div>
                <div class="text-xs text-gray-500">
                    Logic
                </div>
                <div class="col-span-3 text-sm text-gray-600 pt-2 pb-3">
                    <code>
                        <slot name="logic"></slot>
                    </code>
                </div>
            </div>
        </div> -->

        <!-- Action button -->
        <!-- <div class="border-t border-gray-300 p-2 mt-auto">
            <slot name="actions"></slot>
        </div> -->

        <!-- Action button -->
        <div class="border-gray-300 p-2 justify-between flex">
            <slot name="actions"></slot>
        </div>

        <!-- Prime, Equivalent, and Logic -->
        <div class="mt-auto">
            <!-- Prime Name -->
            <div class="text-[3vmin] py-1 text-center px-2 break-words ">
                <slot name="prime"></slot>
            </div>
            <div class="hidden sm:block">
                <div class="mt-3">
                    <div class="border-t border-gray-200" />
                </div>
            </div>

            <div class=" ">
                <!-- Equivalent and Logic -->
                <div class="grid grid-cols-4 gap-2 p-3">
                    <div class="text-[1.50vmin] text-gray-500">Equivalent:</div>
                    <div class="col-span-3 text-sm text-gray-600 break-words">
                        <slot name="equivalent"></slot>
                    </div>
                    <div class="text-xs text-gray-500">Logic:</div>
                    <div class="col-span-3 text-sm text-gray-600 pb-3">
                        <code>
                            <template v-if="hasLogic">
                                HIDDEN IF:
                            </template>
                            <slot name="logic"></slot>
                        </code>
                    </div>
                </div>

                <!-- Value and Index -->
                <div
                    class="border-gray-300 p-2 flex justify-between relative my-2"
                >
                    <div class="relative group">
                        <span
                            class="text-sm text-gray-800 py-1 rounded-full font-semibold absolute left-2 -bottom-1"
                        >
                            #<slot name="n"></slot>
                        </span>
                        <div
                            class="absolute bottom-full left-0 mb-6 px-1.5 py-2 rounded text-xs text-white bg-[#15919a] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                            role="tooltip"
                        >
                            This value are the position /<br/> 
                            order of the prime in the list.
                        </div>
                    </div>

                    <div class="flex gap-2 absolute right-2 bottom-0">
                        <div class="relative group">
                            <span
                                class="text-xs text-[#15919A] bg-[#15919A30] px-3 py-1 rounded-full font-semibold cursor-default"
                            >
                                Index: <slot name="index"></slot>
                            </span>
                            <div
                                class="absolute bottom-full right-0 mb-2 px-1.5 py-2 rounded text-xs text-white bg-[#15919a] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                                role="tooltip"
                            >
                                This value affects index<br/> 
                                ranking or scoring.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    hasLogic: {
        type: Boolean,
        default: false
    }
});
</script>

<style scoped>

:deep(img) {
    display: inline-block;
    max-width: 150px;
    max-height: 150px;
    margin: 0 4px;
    vertical-align: middle;
    border-radius: 4px;
}
</style>
