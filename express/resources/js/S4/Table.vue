<script setup>
const props = defineProps(['items','headers'])
 
</script>

<template> 
    <div class="not-prose relative rounded-xl  border"> 
        <div class="shadow-sm mb-8">
            <table class="border-collapse table-fixed w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-slate-400 font-strong">
                    <th  v-for="(column, index) in props.headers" :class="column.class">
                        {{column.label}}
                    </th> 
                </tr>
            </thead>
            <tbody class="[&>*:nth-child(even)]:bg-white [&>*:nth-child(odd)]:bg-slate-50">
                <slot name="body"></slot> 
           
            </tbody>
            </table>
        </div> 
    <div class="absolute inset-0 pointer-events-none border border-black/5 rounded-xl dark:border-white/5"></div>
    </div>
</template>
