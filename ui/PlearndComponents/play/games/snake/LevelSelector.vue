<template>
    <div class="mt-4">
        <h2 class="text-xl font-semibold mb-2">Select Level:</h2>
        <div class="flex space-x-2">
            <button v-for="l in 5" :key="l" @click="$emit('select-level', l)"
                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                {{ l }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { defineEmits } from 'vue';

defineEmits(['select-level']);
</script>
