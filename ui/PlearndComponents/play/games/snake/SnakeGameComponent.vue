
<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import GameBoard from './GameBoard.vue';
import ScoreBoard from './ScoreBoard.vue';
import LevelSelector from './LevelSelector.vue';
import CongratulationsModal from './CongratulationsModal.vue';
import GameOverModal from './GameOverModal.vue';

const gridSize = 20;
const snake = ref([{ x: 10, y: 10 }]);
const food = ref({ x: 5, y: 5 });
const direction = ref('right');
const gameOver = ref(false);
const score = ref(0);
const level = ref(1);
const gameStarted = ref(false);
const showCongrats = ref(false);

let gameLoop;

const speeds = {
    1: 300,
    2: 200,
    3: 150,
    4: 120,
    5: 90
};

const startGame = (selectedLevel) => {
    level.value = selectedLevel;
    gameStarted.value = true;
    resetGame();
    gameLoop = setInterval(updateGame, speeds[level.value]);
};

const resetGame = () => {
    snake.value = [{ x: 10, y: 10 }];
    food.value = getRandomFood();
    direction.value = 'right';
    gameOver.value = false;
    score.value = 0;
};

const restartGame = () => {
    clearInterval(gameLoop);
    resetGame();
    gameLoop = setInterval(updateGame, speeds[level.value]);
};

const quitGame = () => {
    gameStarted.value = false;
    showCongrats.value = false;
    resetGame();
    clearInterval(gameLoop);
};

const updateGame = () => {

    if (gameOver.value) {
        clearInterval(gameLoop);
        return;
    }

    const head = { ...snake.value[0] };

    switch (direction.value) {
        case 'up': head.y--; break;
        case 'down': head.y++; break;
        case 'left': head.x--; break;
        case 'right': head.x++; break;
    }

    if (checkCollision(head)) {
        gameOver.value = true;
        return;
    }

    snake.value.unshift(head);

    if (head.x === food.value.x && head.y === food.value.y) {
        score.value += 10;
        food.value = getRandomFood();
        if (score.value >= level.value * 100) {
            showCongrats.value = true;
            clearInterval(gameLoop);
        }
    } else {
        snake.value.pop();
    }
};

const checkCollision = (head) => {
  return (
    head.x < 0 || head.x >= gridSize ||
    head.y < 0 || head.y >= gridSize ||
    snake.value.some(segment => segment.x === head.x && segment.y === head.y)
  );
};

const getRandomFood = () => {
  return {
    x: Math.floor(Math.random() * gridSize),
    y: Math.floor(Math.random() * gridSize)
  };
};

const handleKeyDown = (e) => {
    switch (e.key) {
        case "ArrowUp":
            if (direction.value !== "down") direction.value = "up";
            break;
        case "ArrowDown":
            if (direction.value !== "up") direction.value = "down";
            break;
        case "ArrowLeft":
            if (direction.value !== "right") direction.value = "left";
            break;
        case "ArrowRight":
            if (direction.value !== "left") direction.value = "right";
            break;
    }
};

const nextLevel = () => {
    if (level.value < 5) {
        level.value++;
        showCongrats.value = false;
        resetGame();
        gameLoop = setInterval(updateGame, speeds[level.value]);
    }
};

onMounted(() => {
    window.addEventListener("keydown", handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeyDown);
    clearInterval(gameLoop);
});
</script>
<template>
  <div class="min-h-screen flex flex-col bg-white p-4">
    <h1 class="text-3xl font-bold mb-4 text-center">Snake Game</h1>
    <div class="flex-col items-center justify-center">
        <div v-if="gameStarted" class="flex justify-center max-w-full items-center mb-4">
            <div class="flex w-[400px] justify-between items-center">
            <ScoreBoard :score="score" :level="level" />
            <button
                @click="restartGame"
                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition duration-300"
            >
                Restart Game
            </button>
            </div>
        </div>
        <GameBoard
            v-if="gameStarted"
            :snake="snake"
            :food="food"
            :gameOver="gameOver"
            :gridSize="gridSize"
            @keydown="handleKeyDown"
        />
        <div v-else class="flex items-center justify-center mb-4 ">
            <div>
                <p class="text-xl mb-4">Welcome to Snake Game!</p>
                <LevelSelector @select-level="startGame" />
            </div>
        </div>
    </div>
        <CongratulationsModal v-if="showCongrats" :level="level" @next-level="nextLevel" @quit-game="quitGame" />
        <GameOverModal v-if="gameOver" :score="score" @restart="restartGame" @quit="quitGame" />
    </div>
</template>
