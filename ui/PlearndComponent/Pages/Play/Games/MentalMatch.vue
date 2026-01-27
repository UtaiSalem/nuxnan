<script setup>
import { ref, computed, onMounted } from 'vue';

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
  <div class="min-h-screen bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 p-4">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-white mb-2 drop-shadow-lg">Mental Match Game</h1>
        <p class="text-white text-lg drop-shadow">Find all matching pairs!</p>
      </div>
      
      <!-- Game Stats -->
      <div class="bg-white rounded-lg shadow-xl p-4 mb-6 border-2 border-purple-200">
        <div class="flex justify-between items-center">
          <div class="text-center">
            <p class="text-gray-600 text-sm">Moves</p>
            <p class="text-2xl font-bold text-indigo-600">{{ moves }}</p>
          </div>
          <div class="text-center">
            <p class="text-gray-600 text-sm">Pairs Found</p>
            <p class="text-2xl font-bold text-purple-600">{{ matchedPairs }}/{{ currentSettings.pairs }}</p>
          </div>
          <div class="text-center">
            <p class="text-gray-600 text-sm">Difficulty</p>
            <div class="flex space-x-2 mt-1">
              <button
                @click="changeDifficulty('easy')"
                :class="gameDifficulty === 'easy' ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-700'"
                class="px-3 py-1 rounded text-sm font-medium transition-all hover:scale-105"
              >
                Easy
              </button>
              <button
                @click="changeDifficulty('medium')"
                :class="gameDifficulty === 'medium' ? 'bg-yellow-500 text-white' : 'bg-gray-200 text-gray-700'"
                class="px-3 py-1 rounded text-sm font-medium transition-all hover:scale-105"
              >
                Medium
              </button>
              <button
                @click="changeDifficulty('hard')"
                :class="gameDifficulty === 'hard' ? 'bg-red-500 text-white' : 'bg-gray-200 text-gray-700'"
                class="px-3 py-1 rounded text-sm font-medium transition-all hover:scale-105"
              >
                Hard
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Game Board -->
      <div class="bg-white rounded-lg shadow-xl p-6 border-2 border-purple-200">
        <div
          class="grid gap-4 mx-auto"
          :style="`grid-template-columns: repeat(${currentSettings.gridCols}, minmax(0, 1fr)); max-width: ${currentSettings.gridCols * 100}px;`"
        >
          <div
            v-for="card in cards"
            :key="card.id"
            @click="flipCard(card)"
            class="aspect-square flex items-center justify-center text-4xl rounded-lg cursor-pointer transition-all duration-300 transform hover:scale-105 shadow-md"
            :class="{
              'bg-gradient-to-br from-green-400 to-blue-400 border-2 border-green-200': card.isFlipped || card.isMatched,
              'bg-gradient-to-br from-indigo-400 to-purple-400': !card.isFlipped && !card.isMatched,
              'opacity-50 cursor-not-allowed': card.isMatched,
              'rotate-y-180': card.isFlipped || card.isMatched
            }"
          >
            <div v-if="card.isFlipped || card.isMatched" class="animate-pulse">
              {{ card.emoji }}
            </div>
            <div v-else class="text-white font-bold text-2xl">
              ?
            </div>
          </div>
        </div>
      </div>
      
      <!-- Game Controls -->
      <div class="text-center mt-6">
        <button
          @click="initializeGame"
          class="bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold py-3 px-6 rounded-lg shadow-xl hover:from-purple-700 hover:to-pink-700 transition-all transform hover:scale-105"
        >
          New Game
        </button>
      </div>
      
      <!-- Victory Message -->
      <div v-if="!isGameActive && matchedPairs.value === currentSettings.pairs" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 backdrop-blur-sm">
        <div class="bg-gradient-to-br from-purple-100 to-pink-100 rounded-xl p-8 max-w-md mx-4 text-center border-2 border-purple-300 shadow-2xl transform scale-100 animate-bounce">
          <h2 class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600 mb-4">🎉 Congratulations! 🎉</h2>
          <p class="text-lg mb-2 text-gray-700">You found all pairs!</p>
          <p class="text-gray-600 mb-6">Completed in {{ moves }} moves</p>
          <button
            @click="initializeGame"
            class="bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold py-3 px-6 rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all transform hover:scale-105 shadow-lg"
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

.drop-shadow {
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.drop-shadow-lg {
  text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

@keyframes bounce {
  0%, 100% {
    transform: translateY(-25%);
    animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
  }
  50% {
    transform: translateY(0);
    animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
  }
}

.animate-bounce {
  animation: bounce 1s infinite;
}
</style>