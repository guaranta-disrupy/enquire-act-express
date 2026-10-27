<template>
    <!-- <div class="bg-white shadow border-gray-300 border flex flex-col col-span-1 rounded-xl">
        <div class="border-b border-gray-300 p-2">
            <span class="text-xs text-gray-600 bg-gray-100 rounded p-1 font-semibold">
                #<slot name="n"></slot>
            </span>
            <span class="text-xs text-gray-600 bg-gray-100 rounded p-1 ml-1 font-semibold">
                Index: <slot name="index"></slot>
            </span>
            <span class="text-xs text-gray-600 bg-gray-100 rounded p-1 ml-1 font-semibold">
                Value: <slot name="value"></slot>
            </span>
        </div>
        <div class="p-3 mt-auto">
            <div class="text-sm py-1">
                <slot name="target"></slot>
            </div>
            <div class="hidden sm:block">
                <div class="pb-3 mt-3">
                    <div class="border-t border-gray-200" />
                </div>
            </div>
            <div class="grid grid-cols-4 gap-4">
                <div class="text-xs text-gray-500">
                    Equivalent
                </div>
                <div class="col-span-3 text-sm text-gray-600 pt-2 pb-3">
                    <slot name="equivalent"></slot>
                </div>
                <div class="text-xs text-gray-500">
                    Colour
                </div>
                <div class="col-span-3 text-sm text-gray-600 pt-2 pb-3">
                    <code>
                        <slot name="colours"></slot>
                    </code>
                </div>
            </div>
        </div>
        <div class="border-t border-gray-300 p-2 mt-auto">
            <slot name="actions"></slot>
        </div>
    </div> -->

    <div
        class="bg-white shadow border-gray-300 border flex flex-col col-span-1 rounded-xl "
    >
        <!-- Action Buttons -->
        <div class="border-gray-300 p-2 justify-between flex">
            <slot name="actions"></slot>
        </div>

        <!-- Targets -->
        <div class="mt-auto">
            <div class="text-2xl py-1 text-center px-2 break-words">
                <slot name="target"></slot>
            </div>
            <div class="hidden sm:block">
                <div class="mt-3">
                    <div class="border-t border-gray-200" />
                </div>
            </div>

            <div class=" ">
                <!-- Equivalent -->
                <div class="grid grid-cols-4 gap-2 p-3">
                    <div class="text-[1.50vmin] text-gray-500">Equivalent:</div>
                    <div class="col-span-3 text-sm text-gray-600 break-words">
                        <slot name="equivalent"></slot>
                    </div>

                    <!-- Color -->
                    <!-- <div class="text-xs text-gray-500">Colour:</div> -->
                    <!-- <div class="col-span-3 text-sm text-gray-600">
                        <code>
                            <slot name="colours"></slot>
                        </code>
                    </div> -->
                </div>
            </div>
        </div>

        <!-- Index and Value -->
        <div
            class="border-gray-300 p-2 my-2 flex justify-between gap-1 relative"
        >

        <div class="relative group">
            <span
                class="text-sm text-gray-800 py-1 rounded-full font-semibold absolute left-2 -bottom-1"
            >
                #<slot name="n"></slot>
            </span>
            <div
                class="absolute bottom-full left-0 mb-6 px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                role="tooltip"
                >
                    This value are the position /<br/> order of the target in the list.
                </div>
            </div>

            <div class="gap-1 flex absolute right-2 bottom-0">

                <div class="relative group">
                    <span
                        class="text-xs text-[#257556] bg-[#25755630] px-3 py-1 ml-1 rounded-full font-semibold"
                    >
                        Index: <slot name="index"></slot>
                    </span>
                    <div
                        class="absolute bottom-full right-0 mb-2 px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                        role="tooltip"
                    >
                        This value affects index<br/> ranking or scoring.
                    </div>
                </div>

                <div class="relative group">
                    <span
                        class="text-xs text-[#257556] bg-[#25755630] px-3 py-1 ml-1 rounded-full font-semibold"
                    >
                        Value: <slot name="value"></slot>
                    </span>
                    <div
                        class="absolute bottom-full right-0 mb-2 px-1.5 py-2 rounded text-xs text-white bg-[#257556] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                        role="tooltip"
                    >
                        This is the value of <br/>the target in the list.
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
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
