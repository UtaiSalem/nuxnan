<template>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
      <div class="bg-white px-8 py-4 rounded-lg text-center">
        <h2 class="text-2xl font-bold mb-4">Congratulations!</h2>
        <p class="mb-4">You've completed level {{ level }}!</p>
        <div class="flex justify-center space-x-4">
          <button
            v-if="level < 5"
            @click="$emit('next-level')"
            class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
          >
            Next Level
          </button>
          <button
            @click="$emit('quit-game')"
            class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
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
