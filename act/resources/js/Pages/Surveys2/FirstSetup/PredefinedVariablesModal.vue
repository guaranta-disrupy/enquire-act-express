<template>
    <JetDialogModal :show="show" @close="closeModal" maxWidth="4xl">
        <template #title> Predefined Variable Names </template>

        <template #content>
            <div class="space-y-6 max-h-[60vh] overflow-y-auto pt-4">
                <div
                    v-for="topic in predefinedVariables"
                    :key="topic.topic"
                    class="py-0.5 border-b border-gray-100 pb-4"
                >
                    <h3 class="text-md font-semibold mb-2 text-gray-700">
                        {{ topic.topic }}
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="name in topic.names"
                            :key="name"
                            @click="selectName(name)"
                            class="cursor-pointer text-xs font-semibold px-3 py-1 rounded-full transition-transform transform hover:shadow-md"
                            :style="{
                                backgroundColor: topic.color + '20',
                                color: topic.color,
                                border: '1px solid ' + topic.color + '50',
                            }"
                        >
                            {{ name }}
                        </span>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <JetButton variant="default" @click="closeModal"> Close </JetButton>
        </template>
    </JetDialogModal>
</template>

<script setup>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetButton from "@/Componentsv2/Button.vue";

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(["close", "select-name"]);

const closeModal = () => {
    emit("close");
};

const selectName = (name) => {
    emit("select-name", name);
    closeModal();
};

const predefinedVariables = [
    {
        topic: "Brand & Product",
        color: "#4A90E2",
        names: [
            "brand_slogan",
            "product_name",
            "competitor_brand",
            "product_image",
            "packaging_design",
            "service_name",
            "product_feature",
            "product_benefit",
            "logo",
            "brand_logo",
        ],
    },
    {
        topic: "Marketing & Advertising",
        color: "#50E3C2",
        names: [
            "ad_copy",
            "headline",
            "call_to_action",
            "discount_offer",
            "promo_code",
            "celebrity_endorser",
            "campaign_name",
        ],
    },
    {
        topic: "Context & Scenarios",
        color: "#787878",
        names: [
            "usage_scenario",
            "purchase_location",
            "time_of_day",
            "season",
            "competitor_logo",
            "store_name",
        ],
    },
    {
        topic: "Categories",
        color: "#BD10E0",
        names: [
            "category_name",
            "subcategory",
            "collection",
            "theme",
            "type",
            "segment",
            "industry",
            "niche",
        ],
    },
    {
        topic: "Occasions",
        color: "#257556",
        names: [
            "event",
            "holiday",
            "seasonality",
            "moment",
            "use_case",
            "timeframe",
            "context",
            "occasion_detail",
        ],
    },
    {
        topic: "Goals",
        color: "#4A90E2",
        names: [
            "objective",
            "intent",
            "purpose",
            "motivation",
            "desired_outcome",
            "kpi",
            "metric",
            "success_criteria",
        ],
    },
    {
        topic: "Other",
        color: "#787878",
        names: [
            "audience",
            "persona",
            "mood",
            "tone",
            "style",
            "priority",
            "urgency",
            "budget",
            "channel",
            "platform",
            "device",
            "region",
            "language",
            "locale",
            "age_bracket",
            "life_stage",
            "household_status",
            "relationship_status",
            "purchase_stage",
            "familiarity_level",
            "loyalty_tier",
            "satisfaction_level",
            "confidence_level",
            "preference_rank",
            "frequency",
            "recency",
            "usage_level",
        ],
    },
];
</script>
