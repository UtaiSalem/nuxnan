<template>
  <div
    class="bg-gray-100 dark:bg-gray-700 grid gap-0 border-4 border-gray-300 dark:border-gray-600 mx-auto rounded-xl shadow-lg"
    :style="{
      width: `min(90vw, 90vh, 400px)`,
      height: `min(90vw, 90vh, 400px)`,
      gridTemplateColumns: `repeat(${gridSize}, 1fr)`
    }"
    tabindex="0"
    @keydown="$emit('keydown', $event)"
  >
    <template v-for="y in gridSize">
      <div
        v-for="x in gridSize"
        :key="`${x}-${y}`"
        class="aspect-square border border-gray-200 dark:border-gray-600"
        :class="{
          'bg-green-500 shadow-lg': isSnakeSegment(x - 1, y - 1),
          'bg-red-500 shadow-lg animate-pulse': isFood(x - 1, y - 1),
          'bg-gray-50 dark:bg-gray-800': !isSnakeSegment(x - 1, y - 1) && !isFood(x - 1, y - 1)
        }"
      ></div>
    </template>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps(['snake', 'food', 'gameOver', 'gridSize']);
defineEmits(['keydown']);

const isSnakeSegment = (x, y) => {
    return props.snake.some(segment => segment.x === x && segment.y === y);
};

const isFood = (x, y) => {
    return props.food.x === x && props.food.y === y;
};
</script>
