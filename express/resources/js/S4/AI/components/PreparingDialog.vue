<script setup>
const props = defineProps({
    isSurvey: { type: Boolean, default: false },
    isCreating: { type: Boolean, default: false },
    creatingCount: { type: Number, default: 0 },
    progress: { type: Object, default: () => ({ stage: '', message: '', current: 0, total: 0 }) }
});

const emit = defineEmits(['cancel']);
</script>

<template>
    <div class="bubble-speech bubble-left">
        <div class="chat-bubble bg-white border border-gray-200 shadow-lg">
            <div class="confirmation-content p-4">
                <div class="text-center py-8">
                    <div class="typing-bubble inline-flex">
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                    </div>
                    <p class="text-sm text-gray-500 mt-4">
                        <span v-if="isCreating">
                            Creating {{ creatingCount }} question{{ creatingCount !== 1 ? 's' : '' }}...
                        </span>
                        <span v-else-if="isSurvey">
                            <span v-if="progress.stage === 'planning'">Thinking...</span>
                            <span v-else-if="progress.stage === 'plan_complete'">{{ progress.message }}</span>
                            <span v-else-if="progress.stage === 'generating'">
                                <span v-if="progress.current > 0 && progress.total > 0">
                                    Preparing Question {{ progress.current }} of {{ progress.total }}...
                                </span>
                                <span v-else>{{ progress.message }}</span>
                            </span>
                            <span v-else-if="progress.stage === 'questions_complete'">{{ progress.message }}</span>
                            <span v-else-if="progress.stage === 'finalizing'">{{ progress.message }}</span>
                            <span v-else-if="progress.stage === 'complete'">{{ progress.message }}</span>
                            <span v-else-if="progress.stage === 'error'">{{ progress.message }}</span>
                            <span v-else>Preparing Survey<br>Please Wait.</span>
                        </span>
                        <span v-else>Preparing Question<br>Please Wait.</span>
                    </p>
                    <div v-if="isSurvey && progress.total > 0" class="mt-4 max-w-md mx-auto">
                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                            <div class="bg-gradient-to-r from-[var(--mcolor)] to-[var(--mcolor2)] h-3 rounded-full transition-all duration-500 ease-out flex items-center justify-end pr-2" 
                                 :style="{ width: Math.min((progress.current / progress.total * 100), 100) + '%' }">
                            </div>
                        </div>
                        <p v-if="progress.total > 0" class="text-xs text-gray-400 mt-2 text-center">
                            <span v-if="progress.current > 0 && progress.current < progress.total">
                                Question {{ progress.current }} of {{ progress.total }}
                            </span>
                            <span v-else-if="progress.current >= progress.total">
                                All {{ progress.total }} questions prepared
                            </span>
                            <span v-else>
                                Preparing {{ progress.total }} questions...
                            </span>
                        </p>
                    </div>
                    <div v-if="!isCreating" class="mt-6 flex justify-center">
                        <button
                            @click="emit('cancel')"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
                        >
                            Cancel
                        </button>
                    </div>
                    <p v-else class="mt-4 text-xs text-gray-400">Please wait...</p>
                </div>
            </div>
        </div>
    </div>
</template>
