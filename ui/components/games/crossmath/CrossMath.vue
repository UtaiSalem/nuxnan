<template>
  <div class="cross-math-game min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 overflow-hidden">
    <!-- 3D Classroom Background with Props -->
    <div class="classroom-scene fixed inset-0 overflow-hidden pointer-events-none">
      <!-- Volumetric Light Effect -->
      <div class="absolute inset-0 bg-gradient-to-b from-amber-500/10 via-transparent to-transparent"></div>
      <div class="absolute top-0 left-1/4 w-96 h-full bg-gradient-to-r from-yellow-400/5 to-transparent rotate-12"></div>
      
      <!-- Floor with Perspective -->
      <div class="absolute bottom-0 left-0 right-0 h-1/3 bg-gradient-to-t from-amber-900/40 to-transparent"></div>
      
      <!-- Floating Clock -->
      <div class="absolute top-8 right-8 text-6xl animate-pulse-slow filter drop-shadow-lg">
        ⏰
      </div>
      
      <!-- Books -->
      <div class="absolute bottom-20 left-4 text-7xl filter drop-shadow-xl">
        📚
      </div>
      
      <!-- Plant -->
      <div class="absolute bottom-16 right-4 text-8xl filter drop-shadow-xl">
        🪴
      </div>
    </div>

    <!-- Login Screen -->
    <div v-if="gameState === 'login'" class="relative z-10 min-h-screen flex flex-col items-center justify-center p-4">
      <div class="login-card bg-gradient-to-br from-amber-600 to-amber-800 p-8 rounded-3xl shadow-2xl border-4 border-amber-400 transform perspective-1000 rotate-x-2">
        <h1 class="text-4xl md:text-5xl font-bold text-white text-center mb-2 drop-shadow-lg">
          🦉 Cross Math
        </h1>
        <p class="text-amber-100 text-center mb-6 text-lg">ปริศนาตัวเลขสำหรับเด็กฉลาด</p>
        
        <div class="space-y-4">
          <div>
            <label class="block text-white font-semibold mb-2 text-lg">ชื่อนักเรียน / Student Name</label>
            <input 
              v-model="playerName" 
              type="text" 
              class="w-full px-4 py-3 rounded-xl bg-white/90 text-slate-800 font-bold text-xl border-2 border-amber-300 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/30 outline-none transition-all"
              :placeholder="$t('common.enterName')"
              @keyup.enter="startGame"
            >
          </div>
          
          <div>
            <label class="block text-white font-semibold mb-2 text-lg">เลือกเวลา / Time Limit</label>
            <div class="grid grid-cols-2 gap-3">
              <button 
                v-for="option in timeOptions" 
                :key="option.value"
                @click="selectedTime = option.value"
                :class="[
                  'py-3 px-4 rounded-xl font-bold text-lg transition-all transform',
                  selectedTime === option.value 
                    ? 'bg-emerald-500 text-white scale-105 shadow-lg ring-4 ring-emerald-300' 
                    : 'bg-white/80 text-slate-700 hover:bg-white'
                ]"
              >
                {{ option.label }}
              </button>
            </div>
          </div>
          
          <button 
            @click="startGame"
            :disabled="!playerName.trim()"
            class="w-full py-4 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white text-2xl font-bold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          >
            🎮 เริ่มเกม / Start
          </button>
        </div>
      </div>
    </div>

    <!-- Game Screen -->
    <div v-else-if="gameState === 'playing'" class="relative z-10 min-h-screen flex flex-col items-center p-2 md:p-4">
      <!-- Header -->
      <div class="w-full max-w-2xl flex justify-between items-center mb-2 md:mb-4">
        <div class="bg-slate-800/90 px-4 py-2 rounded-xl border-2 border-amber-500/50 shadow-lg">
          <span class="text-amber-400 font-bold">{{ playerName }}</span>
        </div>
        <div class="bg-slate-800/90 px-4 py-2 rounded-xl border-2 border-emerald-500/50 shadow-lg">
          <span class="text-emerald-400 font-bold">ด่าน {{ currentLevel }}</span>
        </div>
        <div class="bg-slate-800/90 px-4 py-2 rounded-xl border-2 border-yellow-500/50 shadow-lg">
          <span class="text-yellow-400 font-bold">คะแนน: {{ score }}</span>
        </div>
      </div>

      <!-- Timer Bar -->
      <div class="w-full max-w-2xl mb-2 md:mb-4">
        <div class="h-4 md:h-6 bg-slate-700 rounded-full overflow-hidden shadow-inner border-2 border-slate-600">
          <div 
            class="h-full transition-all duration-1000 ease-linear rounded-full"
            :class="[
              timeLeft > timeLimit / 3 ? 'bg-gradient-to-r from-emerald-500 to-emerald-400' :
              timeLeft > timeLimit / 6 ? 'bg-gradient-to-r from-yellow-500 to-yellow-400' :
              'bg-gradient-to-r from-red-500 to-red-400'
            ]"
            :style="{ width: `${(timeLeft / timeLimit) * 100}%` }"
          ></div>
        </div>
        <p class="text-center text-white font-bold mt-1 text-sm">{{ timeLeft }}s</p>
      </div>

      <!-- Game Board Container -->
      <div class="relative mb-3 md:mb-6">
        <!-- 3D Wood Board Frame -->
        <div class="absolute -inset-4 md:-inset-6 bg-gradient-to-b from-amber-700 via-amber-600 to-amber-800 rounded-2xl shadow-2xl transform translate-y-2"></div>
        <div class="absolute -inset-3 md:-inset-5 bg-gradient-to-b from-amber-500 via-amber-400 to-amber-500 rounded-xl"></div>
        
        <!-- Main Game Board -->
        <div class="relative bg-gradient-to-br from-emerald-800 via-emerald-700 to-emerald-900 p-3 md:p-6 rounded-xl shadow-inner border-4 border-emerald-600">
          <!-- Inner glow effect -->
          <div class="absolute inset-2 bg-gradient-to-br from-emerald-500/20 to-transparent rounded-lg pointer-events-none"></div>
          
          <!-- Grid -->
          <div 
            class="grid gap-1 md:gap-2"
            :style="{ 
              gridTemplateColumns: `repeat(${gridSize}, minmax(0, 1fr))`,
              maxWidth: `${gridSize * 60}px`
            }"
          >
            <template v-for="(cell, index) in gridCells" :key="cell.id">
              <!-- Number Cell (Input) -->
              <div 
                v-if="cell.type === 'number'"
                @click="selectCell(cell.id)"
                @keyup.enter="selectCell(cell.id)"
                :ref="el => { if (el) cellRefs[cell.id] = el }"
                :class="[
                  'relative flex items-center justify-center aspect-square rounded-lg cursor-pointer transition-all duration-200',
                  activeCellId === cell.id 
                    ? 'bg-white ring-4 ring-amber-400 scale-105 shadow-lg shadow-amber-400/50' 
                    : 'bg-slate-100 hover:bg-white shadow-md',
                  cell.value ? 'text-slate-800' : 'text-slate-400'
                ]"
                :tabindex="0"
                :aria-label="`ช่องตัวเลข ${cell.positionText}`"
              >
                <span class="text-2xl md:text-3xl font-bold">{{ cell.value || '' }}</span>
              </div>
              
              <!-- Operator Cell -->
              <div 
                v-else-if="cell.type === 'operator'"
                class="flex items-center justify-center aspect-square rounded-lg bg-slate-800/80 text-white"
              >
                <span class="text-2xl md:text-3xl font-bold">{{ cell.value }}</span>
              </div>
              
              <!-- Result Cell -->
              <div 
                v-else-if="cell.type === 'result'"
                class="flex items-center justify-center aspect-square rounded-lg"
                :class="[
                  cell.status === 'correct' ? 'bg-emerald-500 shadow-lg shadow-emerald-500/50' :
                  cell.status === 'incorrect' ? 'bg-red-500 animate-pulse shadow-lg shadow-red-500/50' :
                  'bg-slate-700'
                ]"
              >
                <span class="text-2xl md:text-3xl font-bold text-white">{{ cell.value }}</span>
              </div>
            </template>
          </div>
        </div>

        <!-- Owl Character -->
        <div class="absolute -top-12 md:-top-16 left-1/2 transform -translate-x-1/2 text-5xl md:text-7xl filter drop-shadow-xl animate-bounce-slow">
          🦉
        </div>
        <div class="absolute -bottom-8 md:-bottom-10 left-1/2 transform -translate-x-1/2 -translate-y-full bg-slate-800 px-3 md:px-4 py-1 md:py-2 rounded-full border-2 border-amber-400 whitespace-nowrap">
          <span class="text-white text-xs md:text-sm font-bold">{{ owlMessage }}</span>
        </div>
      </div>

      <!-- Numpad -->
      <div class="w-full max-w-md">
        <div class="grid grid-cols-5 gap-2 md:gap-3">
          <button 
            v-for="digit in 9" 
            :key="digit"
            @click="handleDigitInput(digit)"
            class="numpad-btn aspect-square rounded-xl text-2xl md:text-3xl font-bold transition-all active:scale-95"
            :class="[
              'bg-gradient-to-b from-slate-100 to-slate-300 text-slate-800 shadow-lg shadow-slate-500/50 border-2 border-slate-400',
              'hover:from-white hover:to-slate-200'
            ]"
            :aria-label="`ปุ่มตัวเลข ${digit}`"
          >
            {{ digit }}
          </button>
          <button 
            @click="handleDelete"
            class="numpad-btn aspect-square rounded-xl text-2xl md:text-3xl font-bold transition-all active:scale-95 bg-gradient-to-b from-red-400 to-red-600 text-white shadow-lg shadow-red-500/50 border-2 border-red-500 hover:from-red-300 hover:to-red-500"
            aria-label="ปุ่มลบ"
          >
            ⌫
          </button>
        </div>
      </div>

      <!-- Keyboard Hints -->
      <div class="mt-3 md:mt-4 text-center text-slate-400 text-xs md:text-sm">
        <p>⌨️ ใช้ปุ่มลูกศรเพื่อย้าย | Tab เพื่อไปถัดไป | 1-9 ใส่ตัวเลข</p>
      </div>
    </div>

    <!-- Result Modals -->
    <div v-if="gameState === 'levelComplete' || gameState === 'gameOver' || gameState === 'gameWin'" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
      
      <div 
        class="relative bg-gradient-to-br from-slate-800 to-slate-900 p-6 md:p-8 rounded-3xl shadow-2xl border-4 border-amber-400 max-w-md w-full animate-bounce-in"
      >
        <!-- Result Icon -->
        <div class="text-center mb-4">
          <span v-if="gameState === 'levelComplete'" class="text-6xl md:text-7xl">🎉</span>
          <span v-else-if="gameState === 'gameWin'" class="text-6xl md:text-7xl">🏆</span>
          <span v-else class="text-6xl md:text-7xl">⏰</span>
        </div>
        
        <!-- Result Title -->
        <h2 class="text-2xl md:text-3xl font-bold text-center mb-2" :class="[
          gameState === 'levelComplete' || gameState === 'gameWin' ? 'text-emerald-400' : 'text-red-400'
        ]">
          <span v-if="gameState === 'levelComplete'">ผ่านด่านแล้ว! / Level Complete!</span>
          <span v-else-if="gameState === 'gameWin'">ชนะแล้ว! / You Win!</span>
          <span v-else>หมดเวลา! / Time's Up!</span>
        </h2>
        
        <!-- Score Summary -->
        <div class="bg-slate-700/50 p-4 rounded-xl mb-4">
          <div class="flex justify-between text-white mb-2">
            <span>คะแนนด่าน:</span>
            <span class="font-bold text-yellow-400">{{ levelScore }}</span>
          </div>
          <div v-if="timeBonus > 0" class="flex justify-between text-white mb-2">
            <span>โบนัสเวลา:</span>
            <span class="font-bold text-emerald-400">+{{ timeBonus }}</span>
          </div>
          <div class="flex justify-between text-white border-t border-slate-600 pt-2 mt-2">
            <span>คะแนนรวม:</span>
            <span class="font-bold text-amber-400 text-xl">{{ score }}</span>
          </div>
        </div>
        
        <!-- Buttons -->
        <div class="flex gap-3">
          <button 
            v-if="gameState === 'levelComplete'"
            @click="nextLevel"
            class="flex-1 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white text-lg font-bold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 transition-all"
          >
            ด่านถัดไป →
          </button>
          <button 
            v-if="gameState === 'gameWin'"
            @click="restartGame"
            class="flex-1 py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-white text-lg font-bold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 transition-all"
          >
            เล่นใหม่ 🔄
          </button>
          <button 
            v-if="gameState === 'gameOver'"
            @click="restartGame"
            class="flex-1 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white text-lg font-bold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 transition-all"
          >
            เล่นใหม่ 🔄
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';

const { t } = useI18n();

const gameState = ref('login');
const playerName = ref('');
const selectedTime = ref(60);
const currentLevel = ref(1);
const score = ref(0);
const timeLeft = ref(60);
const activeCellId = ref(null);
const cellRefs = ref({});
const gridCells = ref([]);
const gridSize = computed(() => {
  if (currentLevel.value <= 3) return 2;
  if (currentLevel.value <= 10) return 3;
  return 4;
});

const timeOptions = [
  { value: 30, label: '30 วินาที' },
  { value: 60, label: '1 นาที' },
  { value: 90, label: '1.30 นาที' },
  { value: 120, label: '2 นาที' }
];

const levelScore = ref(0);
const timeBonus = ref(0);

const owlMessages = {
  welcome: 'มาเริ่มกันเลย!',
  correct: 'เยี่ยมมาก! 🎉',
  incorrect: 'ลองใหม่นะ! 💪',
  levelComplete: 'เก่งมาก! 🌟',
  timeRunning: 'เร็วๆ นะ! ⏱️',
  gameWin: 'ยอดเยี่ยม! 🏆',
  gameOver: 'ไม่เป็นไร! 🔄'
};

const owlMessage = ref(owlMessages.welcome);

let timerInterval = null;

const timeLimit = computed(() => selectedTime.value);

function evaluateExpression(numbers, operators) {
  if (!numbers || numbers.length === 0 || !operators || operators.length === 0) return null;
  
  let result = numbers[0];
  
  for (let i = 0; i < operators.length; i++) {
    const num = numbers[i + 1];
    const op = operators[i];
    
    switch (op) {
      case '+':
        result = result + num;
        break;
      case '-':
        result = result - num;
        break;
      case '*':
        result = result * num;
        break;
      case '/':
        if (num === 0 || result % num !== 0) return null;
        result = result / num;
        break;
    }
    
    if (result < 0) return null;
  }
  
  return result;
}

function checkLine(lineType, lineIndex) {
  const size = gridSize.value;
  const cells = gridCells.value;
  const cellCount = size * 2 + 1;
  
  let numbers = [];
  let operators = [];
  
  if (lineType === 'horizontal') {
    let currentNumbers = [];
    let currentOperators = [];
    let hasEmpty = false;
    
    for (let col = 0; col < cellCount; col++) {
      const cellIndex = lineIndex * cellCount + col;
      const cell = cells[cellIndex];
      
      if (cell.type === 'number') {
        if (!cell.value) {
          hasEmpty = true;
          break;
        }
        currentNumbers.push(cell.value);
      } else if (cell.type === 'operator') {
        currentOperators.push(cell.value);
      } else if (cell.type === 'result') {
        currentNumbers.push(cell.value);
      }
    }
    
    if (hasEmpty) return null;
    numbers = currentNumbers;
    operators = currentOperators;
  } else {
    let currentNumbers = [];
    let currentOperators = [];
    let hasEmpty = false;
    
    for (let row = 0; row < cellCount; row++) {
      const cellIndex = row * cellCount + lineIndex;
      const cell = cells[cellIndex];
      
      if (cell.type === 'number') {
        if (!cell.value) {
          hasEmpty = true;
          break;
        }
        currentNumbers.push(cell.value);
      } else if (cell.type === 'operator') {
        currentOperators.push(cell.value);
      } else if (cell.type === 'result') {
        currentNumbers.push(cell.value);
      }
    }
    
    if (hasEmpty) return null;
    numbers = currentNumbers;
    operators = currentOperators;
  }
  
  return evaluateExpression(numbers, operators);
}

function generatePuzzle(level) {
  const size = gridSize.value;
  const cellCount = size * 2 + 1;
  
  let operators = [];
  let allowedOps = [];
  
  if (level <= 3) {
    allowedOps = ['+', '-'];
  } else if (level <= 7) {
    allowedOps = ['+', '-', '*'];
  } else {
    allowedOps = ['+', '-', '*', '/'];
  }
  
  const cells = [];
  const emptyPositions = [];
  
  for (let row = 0; row < cellCount; row++) {
    for (let col = 0; col < cellCount; col++) {
      const isRowEven = row % 2 === 0;
      const isColEven = col % 2 === 0;
      
      if (isRowEven && isColEven) {
        const isEmpty = Math.random() < 0.4;
        cells.push({
          id: `cell-${row}-${col}`,
          type: 'number',
          row,
          col,
          value: isEmpty ? null : Math.floor(Math.random() * 9) + 1,
          status: null
        });
        if (isEmpty) {
          emptyPositions.push(cells.length - 1);
        }
      } else if (!isRowEven && !isColEven) {
        const op = allowedOps[Math.floor(Math.random() * allowedOps.length)];
        cells.push({
          id: `cell-${row}-${col}`,
          type: 'operator',
          row,
          col,
          value: op,
          status: null
        });
      } else {
        cells.push({
          id: `cell-${row}-${col}`,
          type: 'result',
          row,
          col,
          value: null,
          status: null
        });
      }
    }
  }
  
  if (emptyPositions.length < 2) {
    const firstCell = cells.find(c => c.type === 'number' && c.value !== null);
    if (firstCell) {
      firstCell.value = null;
    }
  }
  
  gridCells.value = cells;
  
  setTimeout(() => {
    const firstEmpty = cells.find(c => c.type === 'number' && c.value === null);
    if (firstEmpty) {
      selectCell(firstEmpty.id);
    }
  }, 100);
}

function selectCell(cellId) {
  const cell = gridCells.value.find(c => c.id === cellId);
  if (cell && cell.type === 'number') {
    activeCellId.value = cellId;
    nextTick(() => {
      const el = cellRefs.value[cellId];
      if (el) el.focus();
    });
  }
}

function handleDigitInput(digit) {
  if (!activeCellId.value) {
    const firstEmpty = gridCells.value.find(c => c.type === 'number' && !c.value);
    if (firstEmpty) {
      selectCell(firstEmpty.id);
    } else {
      return;
    }
  }
  
  const cell = gridCells.value.find(c => c.id === activeCellId.value);
  if (cell && cell.type === 'number') {
    cell.value = digit;
    owlMessage.value = 'ทันที! ⏱️';
    
    checkAllLines();
    moveToNextCell();
  }
}

function handleDelete() {
  if (!activeCellId.value) return;
  
  const cell = gridCells.value.find(c => c.id === activeCellId.value);
  if (cell && cell.type === 'number') {
    cell.value = null;
    cell.status = null;
    resetResultStatuses();
  }
}

function checkAllLines() {
  const size = gridSize.value;
  const cellCount = size * 2 + 1;
  
  let correctCount = 0;
  let totalLines = cellCount * 2;
  
  for (let row = 0; row < cellCount; row += 2) {
    const result = checkLine('horizontal', row / 2);
    if (result !== null) {
      const resultCellIndex = row * cellCount + cellCount - 1;
      const resultCell = gridCells.value[resultCellIndex];
      if (resultCell) {
        resultCell.value = result;
        resultCell.status = 'correct';
        correctCount++;
      }
    }
  }
  
  for (let col = 0; col < cellCount; col += 2) {
    const result = checkLine('vertical', col / 2);
    if (result !== null) {
      const resultCellIndex = (cellCount - 1) * cellCount + col;
      const resultCell = gridCells.value[resultCellIndex];
      if (resultCell) {
        resultCell.value = result;
        resultCell.status = 'correct';
        correctCount++;
      }
    }
  }
  
  if (correctCount === totalLines) {
    levelComplete();
  }
}

function resetResultStatuses() {
  gridCells.value.forEach(cell => {
    if (cell.type === 'result') {
      cell.status = null;
    }
  });
}

function moveToNextCell() {
  const currentIndex = gridCells.value.findIndex(c => c.id === activeCellId.value);
  const numberCells = gridCells.value.filter(c => c.type === 'number');
  const currentNumberIndex = numberCells.findIndex(c => c.id === activeCellId.value);
  
  if (currentNumberIndex < numberCells.length - 1) {
    const nextCell = numberCells[currentNumberIndex + 1];
    selectCell(nextCell.id);
  } else {
    const firstEmpty = numberCells.find(c => !c.value);
    if (firstEmpty) {
      selectCell(firstEmpty.id);
    }
  }
}

function startGame() {
  if (!playerName.value.trim()) return;
  
  gameState.value = 'playing';
  currentLevel.value = 1;
  score.value = 0;
  timeLeft.value = selectedTime.value;
  
  generatePuzzle(currentLevel.value);
  startTimer();
  
  owlMessage.value = owlMessages.welcome;
}

function startTimer() {
  if (timerInterval) clearInterval(timerInterval);
  
  timerInterval = setInterval(() => {
    timeLeft.value--;
    
    if (timeLeft.value <= 0) {
      clearInterval(timerInterval);
      gameOver();
    }
  }, 1000);
}

function levelComplete() {
  clearInterval(timerInterval);
  
  const baseScore = gridSize.value * gridSize.value * 10;
  levelScore.value = baseScore;
  timeBonus.value = Math.floor(timeLeft.value * 2);
  score.value += baseScore + timeBonus.value;
  
  owlMessage.value = owlMessages.levelComplete;
  
  if (currentLevel.value >= 16) {
    gameState.value = 'gameWin';
    owlMessage.value = owlMessages.gameWin;
  } else {
    gameState.value = 'levelComplete';
  }
}

function nextLevel() {
  currentLevel.value++;
  timeLeft.value = selectedTime.value;
  
  gameState.value = 'playing';
  generatePuzzle(currentLevel.value);
  startTimer();
  
  owlMessage.value = owlMessages.welcome;
}

function gameOver() {
  clearInterval(timerInterval);
  gameState.value = 'gameOver';
  owlMessage.value = owlMessages.gameOver;
}

function restartGame() {
  gameState.value = 'login';
  playerName.value = '';
  currentLevel.value = 1;
  score.value = 0;
  timeLeft.value = selectedTime.value;
  activeCellId.value = null;
}

function handleKeydown(event) {
  if (gameState.value !== 'playing') return;
  
  const numberCells = gridCells.value.filter(c => c.type === 'number');
  const currentIndex = numberCells.findIndex(c => c.id === activeCellId.value);
  
  switch (event.key) {
    case 'ArrowRight':
      if (currentIndex < numberCells.length - 1) {
        selectCell(numberCells[currentIndex + 1].id);
      }
      event.preventDefault();
      break;
    case 'ArrowLeft':
      if (currentIndex > 0) {
        selectCell(numberCells[currentIndex - 1].id);
      }
      event.preventDefault();
      break;
    case 'ArrowDown':
      if (currentIndex < numberCells.length - gridSize.value) {
        selectCell(numberCells[currentIndex + gridSize.value].id);
      }
      event.preventDefault();
      break;
    case 'ArrowUp':
      if (currentIndex >= gridSize.value) {
        selectCell(numberCells[currentIndex - gridSize.value].id);
      }
      event.preventDefault();
      break;
    case 'Tab':
      event.preventDefault();
      moveToNextCell();
      break;
    case 'Delete':
    case 'Backspace':
      handleDelete();
      event.preventDefault();
      break;
    default:
      if (event.key >= '1' && event.key <= '9') {
        handleDigitInput(parseInt(event.key));
      }
      break;
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
  if (timerInterval) clearInterval(timerInterval);
});

watch(activeCellId, (newVal) => {
  if (newVal) {
    nextTick(() => {
      const el = cellRefs.value[newVal];
      if (el) el.focus();
    });
  }
});
</script>

<style scoped>
@keyframes bounce-in {
  0% {
    transform: scale(0) translateY(-50%);
    opacity: 0;
  }
  50% {
    transform: scale(1.1) translateY(0);
  }
  100% {
    transform: scale(1) translateY(0);
    opacity: 1;
  }
}

@keyframes pulse-slow {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

@keyframes bounce-slow {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.animate-bounce-in {
  animation: bounce-in 0.5s ease-out forwards;
}

.animate-pulse-slow {
  animation: pulse-slow 3s ease-in-out infinite;
}

.animate-bounce-slow {
  animation: bounce-slow 2s ease-in-out infinite;
}

.perspective-1000 {
  perspective: 1000px;
}

.rotate-x-2 {
  transform: rotateX(2deg);
}

.numpad-btn {
  -webkit-tap-highlight-color: transparent;
  user-select: none;
}

.numpad-btn:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.5);
}
</style>
