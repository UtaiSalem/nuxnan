<script setup>
import { ref, onMounted, watch } from 'vue';
import confetti from 'canvas-confetti'

definePageMeta({
  layout: 'game-layout'
})

const board = ref(Array(9).fill(''));
const currentPlayer = ref('X');
const scores = ref({ X: 0, O: 0 });
const gameMode = ref('single');
const showFireworks = ref(false);
const backgroundMusic = ref(null);
const winningCombos = [
    [0, 1, 2], [3, 4, 5], [6, 7, 8], // แนวนอน
    [0, 3, 6], [1, 4, 7], [2, 5, 8], // แนวตั้ง
    [0, 4, 8], [2, 4, 6] // แนวทแยง
];
const winner = ref(null);

const makeMove = (index) => {
    if (board.value[index] === '' && !winner.value) {
        board.value[index] = currentPlayer.value;
        checkWinner();
        if (!winner.value) {
             currentPlayer.value = currentPlayer.value === 'X' ? 'O' : 'X';
            if (gameMode.value === 'single' && currentPlayer.value === 'O') {
                setTimeout(makeComputerMove, 500);
            }
        }
    }
};

const makeComputerMove = () => {
    if (winner.value) return;
    const emptySpots = board.value.reduce((acc, cell, index) => {
        if (cell === '') acc.push(index);
        return acc;
    }, []);

    if (emptySpots.length > 0) {
        const randomSpot = emptySpots[Math.floor(Math.random() * emptySpots.length)];
        makeMove(randomSpot);
    }
};

const checkWinner = () => {
    for (let combo of winningCombos) {
        if (combo.every(index => board.value[index] === currentPlayer.value)) {
            winner.value = currentPlayer.value;
            scores.value[currentPlayer.value]++;
            showFireworks.value = true;
            showConfetti();
            setTimeout(() => {
                showFireworks.value = false;
                resetBoard();
            }, 3000);
            return;
        }
    }

    if (board.value.every(cell => cell !== '')) {
        winner.value = 'draw';
        setTimeout(() => {
            resetBoard();
        }, 2000);
    }
};

const resetBoard = () => {
    board.value = Array(9).fill('');
    currentPlayer.value = 'X';
    winner.value = null;
};

const resetGame = () => {
    resetBoard();
    scores.value = { X: 0, O: 0 };
};

onMounted(() => {
    // backgroundMusic.value.play();
});

const showConfetti = () => {
  confetti({
    particleCount: 150,
    spread: 80,
    origin: { y: 0.6 }
  })
}

watch(gameMode, () => {
    resetGame();
});
</script>
<template>
    <div class="min-h-[calc(100vh-100px)] py-6 flex flex-col justify-center sm:py-12">
        <div class="relative py-3 sm:max-w-xl sm:mx-auto w-full px-4">
            <div class="absolute inset-0 bg-gradient-to-r from-cyan-400 to-light-blue-500 shadow-lg transform -skew-y-6 sm:skew-y-0 sm:-rotate-6 sm:rounded-3xl opacity-75">
            </div>
            <div class="relative px-4 py-10 bg-white dark:bg-vikinger-dark-100 shadow-lg rounded-3xl sm:p-20 border border-gray-100 dark:border-vikinger-dark-50">
                <h1 class="text-4xl font-bold mb-8 text-center bg-gradient-to-r from-cyan-500 to-blue-500 bg-clip-text text-transparent">เกม XO</h1>

                <div class="mb-8 flex justify-center">
                    <div class="relative inline-flex items-center space-x-4 bg-gray-100 dark:bg-vikinger-dark-200 p-2 rounded-xl">
                        <span class="text-gray-600 dark:text-gray-300 font-medium pl-2">โหมด:</span>
                        <select v-model="gameMode" class="bg-white dark:bg-vikinger-dark-100 border border-gray-200 dark:border-vikinger-dark-50 text-gray-700 dark:text-gray-200 py-1.5 px-4 pr-8 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 shadow-sm">
                            <option value="single">เล่นคนเดียว (vs AI)</option>
                            <option value="multi">เล่นสองคน</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-between items-center mb-8 px-4 bg-gray-50 dark:bg-vikinger-dark-200 py-4 rounded-2xl">
                     <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">ผู้เล่น X</p>
                        <p class="text-3xl font-bold text-cyan-600">{{ scores.X }}</p>
                     </div>
                     <div class="text-center px-4">
                        <div v-if="!winner" class="text-xl font-bold text-gray-400">VS</div>
                         <div v-else-if="winner === 'draw'" class="text-lg font-bold text-orange-500 animate-bounce">เสมอ!</div>
                         <div v-else class="text-lg font-bold text-green-500 animate-bounce">ผู้ชนะ: {{ winner }}</div>
                     </div>
                     <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">ผู้เล่น O</p>
                        <p class="text-3xl font-bold text-pink-500">{{ scores.O }}</p>
                     </div>
                </div>

                <div class="grid grid-cols-3 gap-3 mb-8 mx-auto max-w-[300px]">
                    <button v-for="(cell, index) in board" :key="index" @click="makeMove(index)"
                        :disabled="cell !== '' || !!winner"
                        class="w-24 h-24 bg-gray-100 dark:bg-vikinger-dark-200 text-5xl font-black flex items-center justify-center rounded-2xl shadow-sm hover:shadow-md hover:bg-gray-200 dark:hover:bg-vikinger-dark-300 transition-all duration-200 disabled:cursor-not-allowed"
                        :class="{
                            'text-cyan-500': cell === 'X',
                            'text-pink-500': cell === 'O'
                        }">
                        <transition name="pop">
                            <span v-if="cell">{{ cell }}</span>
                        </transition>
                    </button>
                </div>

                <div class="text-center mb-6" v-if="!winner">
                    <p class="text-lg font-semibold text-gray-600 dark:text-gray-300">
                        ตาของ: <span class="text-2xl font-bold" :class="currentPlayer === 'X' ? 'text-cyan-500' : 'text-pink-500'">{{ currentPlayer }}</span>
                    </p>
                </div>

                <button @click="resetGame"
                    class="w-full bg-gradient-to-r from-gray-700 to-gray-900 text-white font-bold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200 flex items-center justify-center space-x-2">
                    <Icon name="fluent:arrow-counterclockwise-24-filled" class="w-5 h-5" />
                    <span>เริ่มเกมใหม่</span>
                </button>
            </div>
        </div>
    </div>
</template>


<style scoped>
.pop-enter-active {
  animation: pop-in 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes pop-in {
  0% { transform: scale(0); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}
</style>
