<script setup>
// No props needed for this component
</script>

<template>
    <div class="flex justify-center items-center py-6">
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl px-6 py-4 shadow-sm animate-fade-in">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <div class="text-center">
                    <p class="text-green-800 font-semibold text-sm">Thank you for your feedback!</p>
                    <p class="text-green-600 text-xs mt-1">Your input helps us improve</p>
                </div>
            </div>
        </div>
    </div>
</template>
