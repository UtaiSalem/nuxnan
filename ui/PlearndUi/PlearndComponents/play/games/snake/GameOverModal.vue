<template>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 px-8 py-6 rounded-xl shadow-lg text-center max-w-md mx-4">
            <div class="text-6xl mb-4">💀</div>
            <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Game Over!</h2>
            <p class="mb-6 text-gray-600 dark:text-gray-400">Your final score: <span class="font-bold text-gray-900 dark:text-white">{{ score }}</span></p>
            <div class="flex justify-center space-x-3">
                <button @click="$emit('restart')"
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-blue-500/30 transition-all">
                    Restart
                </button>
                <button @click="$emit('quit')"
                    class="px-6 py-2.5 bg-gray-600 hover:bg-gray-700 text-white rounded-xl font-medium shadow-lg hover:shadow-gray-500/30 transition-all">
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
