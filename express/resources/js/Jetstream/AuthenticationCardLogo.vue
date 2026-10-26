<script setup>
import { Link } from "@inertiajs/vue3";
import { withBase } from "@/utils/basePath";
</script>

<template>
    <Link :href="'/'"> 
        <img :src="withBase('/img/nextsensus/nextsensus-escuta.svg')" alt="nextsensus" />
    </Link>
</template>
