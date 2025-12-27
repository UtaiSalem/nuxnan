<template>
  <div
    class="bg-gray-200 grid gap-0 border-2 border-gray-400 mx-auto"
    :style="{
      width: `min(90vw, 90vh, 400px)`,
      height: `min(90vw, 90vh, 400px)`,
      gridTemplateColumns: `repeat(${gridSize}, 1fr)`
    }"
    tabindex="0"
    @keydown="$emit('keydown', $event)"
  >
    <template v-for="y in gridSize" :key="`row-${y}`">
      <div
        v-for="x in gridSize"
        :key="`${x}-${y}`"
        class="aspect-square"
        :class="{
          'bg-green-500': isSnakeSegment(x - 1, y - 1),
          'bg-red-500': isFood(x - 1, y - 1)
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
