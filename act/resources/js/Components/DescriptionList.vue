<template>
<div class="border-t border-gray-200">
    <dl>
        <slot name="desc">
            <div class="bg-gray-50 px-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">
                    <slot></slot>
                </dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                    <slot name="description"></slot>
                </dd>
            </div>
        </slot>
        
    </dl>
</div>
    
</template>