<script setup>
import { ref, onMounted, watch } from 'vue';
import confetti from 'canvas-confetti'

import GameLayout from '@/Layouts/GameLayout.vue'

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

const makeMove = (index) => {
    if (board.value[index] === '') {
        board.value[index] = currentPlayer.value;
        checkWinner();
        currentPlayer.value = currentPlayer.value === 'X' ? 'O' : 'X';

        if (gameMode.value === 'single' && currentPlayer.value === 'O') {
            setTimeout(makeComputerMove, 500);
        }
    }
};

const makeComputerMove = () => {
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
        resetBoard(); // เสมอ
    }
};

const resetBoard = () => {
    board.value = Array(9).fill('');
    currentPlayer.value = 'X';
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
    particleCount: 100,
    spread: 70,
    origin: { y: 0.6 }
  })
}

watch(gameMode, () => {
    resetGame();
});
</script>
<template>
    <GameLayout>
        <div class="min-h-screen bg-gray-100 py-6 flex flex-col justify-center sm:py-12">
            <div class="relative py-3 sm:max-w-xl sm:mx-auto">
                <div class="absolute inset-0 bg-gradient-to-r from-cyan-400 to-light-blue-500 shadow-lg transform -skew-y-6 sm:skew-y-0 sm:-rotate-6 sm:rounded-3xl">
                </div>
                <div class="relative px-4 py-10 bg-white shadow-lg sm:rounded-3xl sm:p-20">
                    <h1 class="text-4xl font-bold mb-5 text-center text-gray-800">เกม XO</h1>

                    <div class="mb-5">
                        <label class="mr-3">โหมดเกม:</label>
                        <select v-model="gameMode" class="border p-2 rounded">
                            <option value="single">เล่นคนเดียว</option>
                            <option value="multi">เล่นสองคน</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-3 gap-3 mb-5">
                        <button v-for="(cell, index) in board" :key="index" @click="makeMove(index)"
                            class="w-20 h-20 bg-blue-200 text-4xl font-bold flex items-center justify-center rounded">
                            {{ cell }}
                        </button>
                    </div>

                    <div class="text-center mb-5">
                        <p class="text-xl font-semibold">ผู้เล่น {{ currentPlayer }} กำลังเล่น</p>
                        <p class="text-lg">คะแนน: X - {{ scores.X }}, O - {{ scores.O }}</p>
                    </div>

                    <button @click="resetGame"
                        class="bg-green-500 text-white px-4 py-2 rounded w-full">เริ่มเกมใหม่</button>
                </div>
            </div>
        </div>

        <div v-if="showFireworks" class="xfireworks">
            <!-- เพิ่ม animation พลุริบบิ้นที่นี่ -->

        </div>

        <!-- <audio ref="backgroundMusic" loop>
            <source src="/path/to/your/music.mp3" type="audio/mpeg">
        </audio> -->
    </GameLayout>
</template>


<style scoped>
.fireworks {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 1000;
    /* เพิ่ม animation ของพลุริบบิ้นที่นี่ */
}
</style>
