<script setup>
const props = defineProps({
    show: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);
</script>

<template>
    <div v-if="show" class="absolute inset-0 z-[1100] flex items-center justify-center p-4 overflow-y-auto">
        <div @click="$emit('close')" class="absolute inset-0 bg-black bg-opacity-75 rounded-xl"></div>
        <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all w-full max-w-lg z-10 my-auto max-h-[90%] flex flex-col">
            <div class="px-6 py-6 overflow-y-auto flex-1">
                <h2 class="text-2xl font-semibold text-center mb-6" style="color: #ee920c;">
                    Chatbot Terms and Conditions
                </h2>
                <div class="text-gray-900 space-y-4">
                    <p class="text-sm">
                        By using this chatbot, you acknowledge and agree that your conversations may be recorded, monitored, and reviewed by authorized administrators for the purposes of:
                    </p>
                    <ul class="list-disc list-inside text-sm space-y-2 ml-4">
                        <li>Improving chatbot accuracy and user experience,</li>
                        <li>Ensuring service quality and compliance, and</li>
                        <li>Supporting management and troubleshooting activities.</li>
                    </ul>
                    <p class="text-sm">
                        All data collected will be handled in accordance with our Privacy Policy and will not be shared with unauthorized third parties. By continuing to interact with this chatbot, you consent to this data usage and monitoring.
                    </p>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end flex-shrink-0">
                <button 
                    @click="$emit('close')"
                    class="px-6 py-2.5 text-sm font-semibold text-white rounded-lg transition-colors hover:opacity-90"
                    style="background-color: #ee920c;"
                >
                    Acknowledge
                </button>
            </div>
        </div>
    </div>
</template>
