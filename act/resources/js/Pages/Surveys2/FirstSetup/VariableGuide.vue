<template>
    <div
        class="w-full p-5 border-2 border-[#15919A70] rounded-lg relative animate-fade-up"
    >
        <div
            class="flex bg-[#15919A] text-white rounded-full inline-flex items-center justify-center w-8 h-8 cursor-pointer hover:bg-[#33A7B0] transition-opacity duration-100 active:scale-95 absolute -top-3 -right-3"
        >
            <CloseIcon @click="emit('close-guide')" />
        </div>
        <div class="space-y-4 mb-6">
            <h3 class="text-lg font-semibold text-gray-800">
                Variables Usage in Survey Rounds
            </h3>

            <p class="text-gray-700 leading-relaxed">
                Variables allow you to personalize survey content dynamically by inserting
                <code class="bg-gray-100 px-2 py-1 rounded font-bold"
                    >variables</code
                >
                placeholders that display specific values based on participant characteristics or experimental conditions. The system uses predefined criteria to decide which content each participant sees and automatically fills in the correct values during the survey round.
            </p>

        
            <p class="text-gray-700 leading-relaxed">
                You can use 
                <code class="bg-gray-100 px-2 py-1 rounded font-bold"
                    >variables</code
                >
                anywhere in your survey content -
                <em
                    >titles, headers, questions, primes, targets, and
                    instructions</em
                >.  Simply insert a variable where you want dynamic content.
            </p>

            <p class="text-gray-700 leading-relaxed">
                Example: "Rate
                <code class="bg-[#15919A] text-white p-1 px-2 rounded text-sm">
                    brand_name</code
                >" might appear as "<strong>Rate Nike</strong>" for young participants
                and "<strong>Rate Adidas</strong>" for older participants, depending on your variable criteria.
            </p>

            <p class="text-gray-700 leading-relaxed">
                Before each round begins, the system processes all variables so participants always see personalized content. Variables are also integrated with analysis and exports, recording which values were assigned to each participant.            </p>

            <p class="text-gray-700 leading-relaxed">
                <strong>This makes variables ideal for:</strong>
                Brand testing,
                Age-specific content,
                Regional customization,
                Experimental condition assignment.
            </p>
        </div>

        <div class="flex justify-start gap-3">
            <JetButton variant="slate" @click="$emit('proceed')" size="xs">
                Proceed to Create Variable
            </JetButton>
            <div class="text-[#15919A] text-sm">
                you can add variables later or reuse existing variables.
            </div>
        </div>
    </div>
</template>

<script setup>
import CloseIcon from "./assets/CloseIcon.vue";
import JetButton from "@/Componentsv2/Button.vue";

const emit = defineEmits(["close-guide", "proceed"]);
</script>

<style scoped>
@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-up {
    animation: fadeUp 0.3s ease-out forwards;
}
</style>
