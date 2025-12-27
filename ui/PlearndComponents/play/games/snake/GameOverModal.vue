<template>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white px-8 py-4 rounded-lg text-center">
            <h2 class="text-2xl font-bold mb-4">Game Over!</h2>
            <p class="mb-4">Your score: {{ score }}</p>
            <div class="flex justify-center space-x-4">
                <button @click="$emit('restart')"
                    class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition duration-300">
                    Restart
                </button>
                <button @click="$emit('quit')"
                    class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition duration-300">
                    Quit
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

defineProps(['score']);
defineEmits(['restart', 'quit']);
</script>
