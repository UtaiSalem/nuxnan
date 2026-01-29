<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationMark from './ApplicationMark.vue';
</script>

<template>
    <Link :href="'/'">
        <figure>
            <img src="/images/plearnd-logo.png" alt="Plearnd Logo" class="w-20 h-20" />
        </figure>
    </Link>
</template>
