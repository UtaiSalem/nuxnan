<template>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 px-8 py-6 rounded-xl shadow-lg text-center max-w-md mx-4">
        <div class="text-6xl mb-4">🎉</div>
        <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Congratulations!</h2>
        <p class="mb-6 text-gray-600 dark:text-gray-400">You've completed level {{ level }}!</p>
        <p class="mb-6 text-sm text-green-600 dark:text-green-400 font-medium">+{{ level * 20 }} Points Earned!</p>
        <div class="flex justify-center space-x-3">
          <button
            v-if="level < 5"
            @click="$emit('next-level')"
            class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl font-medium shadow-lg hover:shadow-green-500/30 transition-all"
          >
            Next Level
          </button>
          <button
            @click="$emit('quit-game')"
            class="px-6 py-2.5 bg-gray-600 hover:bg-gray-700 text-white rounded-xl font-medium shadow-lg hover:shadow-gray-500/30 transition-all"
          >
            Quit Game
          </button>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { defineProps, defineEmits } from 'vue';
  
  defineProps(['level']);
  defineEmits(['next-level', 'quit-game']);
  </script>
