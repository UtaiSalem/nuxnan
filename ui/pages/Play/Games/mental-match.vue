<script setup>
import { ref, computed, onMounted } from 'vue';

definePageMeta({
  layout: 'game-layout'
})

const cards = ref([]);
const flippedCards = ref([]);
const matchedPairs = ref(0);
const moves = ref(0);
const isGameActive = ref(false);
const gameDifficulty = ref('easy'); // easy, medium, hard

const emojis = {
  easy: ['🍎', '🎸', '🎺', '🎻', '🎨', '🎭'],
  medium: ['🍎', '🎸', '🎺', '🎻', '🎨', '🎭', '🏀', '🏈'],
  hard: ['🍎', '🎸', '🎺', '🎻', '🎨', '🎭', '🏀', '🏈', '⚽', '🏀']
};

const difficultySettings = {
  easy: { pairs: 6, gridCols: 3 },
  medium: { pairs: 8, gridCols: 4 },
  hard: { pairs: 10, gridCols: 5 }
};

const currentEmojis = computed(() => emojis[gameDifficulty.value]);
const currentSettings = computed(() => difficultySettings[gameDifficulty.value]);

const initializeGame = () => {
  const selectedEmojis = currentEmojis.value.slice(0, currentSettings.value.pairs);
  const gameCards = [];
  
  // Create pairs
  selectedEmojis.forEach((emoji, index) => {
    gameCards.push({ id: index * 2, emoji, isFlipped: false, isMatched: false });
    gameCards.push({ id: index * 2 + 1, emoji, isFlipped: false, isMatched: false });
  });
  
  // Shuffle cards
  cards.value = gameCards.sort(() => Math.random() - 0.5);
  flippedCards.value = [];
  matchedPairs.value = 0;
  moves.value = 0;
  isGameActive.value = true;
};

const flipCard = (card) => {
  if (!isGameActive.value || card.isFlipped || card.isMatched || flippedCards.value.length >= 2) {
    return;
  }
  
  card.isFlipped = true;
  flippedCards.value.push(card);
  moves.value++;
  
  if (flippedCards.value.length === 2) {
    checkForMatch();
  }
};

const checkForMatch = () => {
  const [card1, card2] = flippedCards.value;
  
  if (card1.emoji === card2.emoji) {
    // Match found
    setTimeout(() => {
      card1.isMatched = true;
      card2.isMatched = true;
      matchedPairs.value++;
      flippedCards.value = [];
      
      if (matchedPairs.value === currentSettings.value.pairs) {
        endGame();
      }
    }, 500);
  } else {
    // No match
    setTimeout(() => {
      card1.isFlipped = false;
      card2.isFlipped = false;
      flippedCards.value = [];
    }, 1000);
  }
};

const endGame = () => {
  isGameActive.value = false;
  // You could add a celebration animation or modal here
};

const changeDifficulty = (difficulty) => {
  gameDifficulty.value = difficulty;
  initializeGame();
};

onMounted(() => {
  initializeGame();
});
</script>

<template>
  <div class="min-h-screen flex flex-col p-4 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-4xl mx-auto w-full">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-500 to-pink-500 mb-2 drop-shadow-sm">Mental Match Game</h1>
        <p class="text-gray-600 dark:text-gray-400 text-lg">Find all matching pairs!</p>
      </div>
      
      <!-- Game Stats -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-gray-100 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
          <div class="text-center">
            <p class="text-gray-500 dark:text-gray-400 text-sm uppercase tracking-wider font-semibold">Moves</p>
            <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ moves }}</p>
          </div>
          <div class="text-center">
            <p class="text-gray-500 dark:text-gray-400 text-sm uppercase tracking-wider font-semibold">Pairs Found</p>
            <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ matchedPairs }}/{{ currentSettings.pairs }}</p>
          </div>
          <div class="text-center">
            <p class="text-gray-500 dark:text-gray-400 text-sm uppercase tracking-wider font-semibold mb-2">Difficulty</p>
            <div class="flex justify-center space-x-2">
              <button
                @click="changeDifficulty('easy')"
                :class="gameDifficulty === 'easy' ? 'bg-green-500 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all transform active:scale-95"
              >
                Easy
              </button>
              <button
                @click="changeDifficulty('medium')"
                :class="gameDifficulty === 'medium' ? 'bg-yellow-500 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all transform active:scale-95"
              >
                Medium
              </button>
              <button
                @click="changeDifficulty('hard')"
                :class="gameDifficulty === 'hard' ? 'bg-red-500 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all transform active:scale-95"
              >
                Hard
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Game Board -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 border border-gray-100 dark:border-gray-700 mb-8">
        <div
          class="grid gap-4 mx-auto"
          :style="`grid-template-columns: repeat(${currentSettings.gridCols}, minmax(0, 1fr)); max-width: ${currentSettings.gridCols * 100}px;`"
        >
          <div
            v-for="card in cards"
            :key="card.id"
            @click="flipCard(card)"
            class="aspect-square flex items-center justify-center text-4xl rounded-xl cursor-pointer transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md"
            :class="{
              'bg-gradient-to-br from-green-400 to-blue-500 border-2 border-green-300 dark:border-green-600': card.isFlipped || card.isMatched,
              'bg-gradient-to-br from-indigo-400 to-purple-500': !card.isFlipped && !card.isMatched,
              'opacity-60 cursor-default ring-4 ring-green-400/30': card.isMatched,
              'rotate-y-180': card.isFlipped || card.isMatched
            }"
          >
            <div v-if="card.isFlipped || card.isMatched" class="animate-appear">
              {{ card.emoji }}
            </div>
            <div v-else class="text-white/80 font-bold text-3xl">
              ?
            </div>
          </div>
        </div>
      </div>
      
      <!-- Game Controls -->
      <div class="text-center">
        <button
          @click="initializeGame"
          class="bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-purple-500/30 hover:from-purple-700 hover:to-pink-700 transition-all transform hover:-translate-y-1 active:scale-95 flex items-center justify-center mx-auto space-x-2"
        >
           <Icon name="fluent:arrow-counterclockwise-24-filled" class="w-5 h-5" />
           <span>New Game</span>
        </button>
      </div>
      
      <!-- Victory Message -->
      <div v-if="!isGameActive && matchedPairs === currentSettings.pairs" class="fixed inset-0 bg-black/60 dark:bg-black/80 flex items-center justify-center z-50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 max-w-sm w-full text-center shadow-2xl border border-gray-200 dark:border-gray-700 transform scale-100 animate-bounce-in">
          <div class="text-6xl mb-4">🎉</div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Congratulations!</h2>
          <p class="text-lg text-gray-600 dark:text-gray-300 mb-2">You found all pairs!</p>
          <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-3 mb-6">
               <p class="text-gray-700 dark:text-gray-200 font-medium">Completed in <span class="text-indigo-600 dark:text-indigo-400 font-bold">{{ moves }}</span> moves</p>
          </div>
         
          <button
            @click="initializeGame"
            class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold py-3 px-6 rounded-xl hover:from-purple-700 hover:to-pink-700 transition-all shadow-lg hover:shadow-purple-500/25"
          >
            Play Again
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.rotate-y-180 {
  transform: rotateY(180deg);
}

.animate-appear {
    animation: appear 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes appear {
    0% { transform: scale(0.5); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes bounceIn {
  0% {
    transform: scale(0.8);
    opacity: 0;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

.animate-bounce-in {
  animation: bounceIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
</style>
