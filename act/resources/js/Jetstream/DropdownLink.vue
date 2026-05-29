<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    href: String,
    as: String,
});
</script>

<template>
    <div>
        <button
            v-if="as == 'button'"
            type="submit"
            class="block w-full px-4 py-2 text-sm leading-5 text-gray-700 text-left hover:bg-[#15919A20] focus:outline-none focus:bg-[#15919A20] transition"
        >
            <slot />
        </button>

        <a
            v-else-if="as == 'a'"
            :href="href"
            class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-[#15919A20] focus:outline-none focus:bg-[#15919A20] transition"
        >
            <slot />
        </a>

        <Link
            v-else
            :href="href"
            class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:[#15919A20] focus:outline-none focus:[#15919A20] transition"
        >
            <slot />
        </Link>
    </div>
</template>
