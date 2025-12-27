<template>
    <div class="mb-4 flex justify-between space-x-4 items-center">
        <div class="text-xl font-semibold">Score: {{ score }}</div>
        <div class="text-xl font-semibold">Level: {{ level }}</div>
    </div>
</template>

<script setup>
import { defineProps } from 'vue';

defineProps(['score', 'level']);
</script>
