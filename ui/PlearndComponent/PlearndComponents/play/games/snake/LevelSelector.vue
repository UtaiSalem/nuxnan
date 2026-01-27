<template>
    <div class="mt-6">
        <h2 class="text-xl font-semibold mb-4 text-center text-gray-900 dark:text-white">Select Difficulty Level:</h2>
        <div class="grid grid-cols-5 gap-3">
            <button
                v-for="l in 5"
                :key="l"
                @click="$emit('select-level', l)"
                class="px-4 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-blue-500/30 transition-all transform hover:scale-105"
            >
                Level {{ l }}
            </button>
        </div>
        <div class="mt-4 text-sm text-gray-500 dark:text-gray-400 text-center">
            Higher levels are faster and more challenging!
        </div>
    </div>
</template>

<script setup>
import { defineEmits } from 'vue';

defineEmits(['select-level']);
</script>
