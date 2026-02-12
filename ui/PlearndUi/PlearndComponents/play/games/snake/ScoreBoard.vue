<template>
    <div class="mb-6 flex justify-between items-center bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
        <div class="text-center">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ score }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Score</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ level }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Level</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ Math.floor(score / 2) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Points</div>
        </div>
    </div>
</template>

<script setup>
import { defineProps } from 'vue';

defineProps(['score', 'level']);
</script>
