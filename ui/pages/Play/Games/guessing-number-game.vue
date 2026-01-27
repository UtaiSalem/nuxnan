<script setup>
import { ref, watch } from 'vue'
import confetti from 'canvas-confetti'

definePageMeta({
  layout: 'game-layout'
})

const secretNumber = ref(generateRandomNumber())
const guess = ref(null)
const message = ref('')
const isGameOver = ref(false)
const attempts = ref(0)
const score = ref(0)

function generateRandomNumber() {
  return Math.floor(Math.random() * 100) + 1
}

const checkGuess = () => {
  if (!guess.value) return
  
  const guessNumber = parseInt(guess.value, 10)
  if (isNaN(guessNumber)) return

  attempts.value++
  
  // ล้าง message ก่อนที่จะตรวจสอบการทาย
  message.value = ''
  calculateScore()

  // เพิ่ม setTimeout เพื่อให้ message ถูกล้างก่อนที่จะแสดงผลลัพธ์ใหม่
  setTimeout(() => {
    if (guessNumber === secretNumber.value) {
      message.value = 'ยินดีด้วย! คุณทายถูก!'
      isGameOver.value = true
      showConfetti()
    } else if (guessNumber < secretNumber.value) {
      message.value = 'น้อยเกินไป ลองใหม่อีกครั้ง'
    } else {
      message.value = 'มากเกินไป ลองใหม่อีกครั้ง'
    }
  }, 10) // ใช้เวลาน้อยมากเพื่อให้ดูเหมือนเป็นการอัพเดททันที

  // ล้างค่า input หลังจากทาย
  guess.value = null
}

const resetGame = () => {
  secretNumber.value = generateRandomNumber()
  guess.value = null
  message.value = ''
  isGameOver.value = false
  attempts.value = 0
  score.value = 0
}

const showConfetti = () => {
  confetti({
    particleCount: 100,
    spread: 70,
    origin: { y: 0.6 }
  })
}

const calculateScore = () => {
  score.value = Math.max(100 - (attempts.value - 1) * 10, 10)
}

watch(isGameOver, (newValue) => {
  if (newValue) {
    showConfetti()
  }
})
</script>
<template>
    <div class="w-full h-full flex items-center justify-center min-h-[50vh]">
        <div class="bg-white dark:bg-vikinger-dark-100 rounded-2xl shadow-xl max-w-md w-full border border-gray-100 dark:border-vikinger-dark-50 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-6 text-center">
                 <h1 class="text-3xl font-bold text-white mb-2">เกมทายตัวเลข</h1>
                 <p class="text-blue-100">ทายตัวเลขระหว่าง 1-100</p>
            </div>
            
            <div class="px-8 py-8">
                
                <div class="text-center mb-6 p-4 bg-gray-50 dark:bg-vikinger-dark-200 rounded-xl">
                    <p class="text-gray-600 dark:text-gray-300">
                        คุณทาย <span class="font-bold text-blue-600 dark:text-blue-400 text-xl mx-1">{{ attempts }}</span> ครั้ง
                    </p>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                        คะแนนปัจจุบัน: <span class="font-bold text-green-500">{{ score }}</span>
                    </p>
                </div>

                <div v-if="message" class="mb-6 p-4 rounded-lg text-center font-bold text-lg animate-fade-in" 
                    :class="{
                    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': message.includes('ยินดีด้วย'),
                    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': message.includes('น้อยเกินไป') || message.includes('มากเกินไป')
                }">
                    {{ message }}
                </div>
                <div v-else class="h-[60px]"></div>

                <div class="mb-6 relative">
                    <input 
                        v-model="guess" 
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        :disabled="isGameOver"
                        @keyup.enter="checkGuess"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-vikinger-dark-200 border border-gray-300 dark:border-vikinger-dark-50 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-center text-2xl font-bold tracking-widest text-gray-800 dark:text-gray-100 disabled:opacity-50 transition-all"
                        placeholder="???"
                    />
                </div>
                <div class="flex space-x-3">
                    <button @click="checkGuess" :disabled="isGameOver || !guess"
                        class="flex-1 bg-gradient-to-t from-blue-600 to-blue-500 text-white py-3 px-4 rounded-xl hover:from-blue-700 hover:to-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-blue-500/30 transition-all transform active:scale-95 font-semibold">
                        ทายเลย
                    </button>
                    <button @click="resetGame"
                        class="flex-1 bg-gray-100 dark:bg-vikinger-dark-200 text-gray-700 dark:text-gray-300 py-3 px-4 rounded-xl hover:bg-gray-200 dark:hover:bg-vikinger-dark-300 focus:outline-none focus:ring-2 focus:ring-gray-300 font-semibold transition-all">
                        เล่นใหม่
                    </button>
                </div>


            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap');

body, input, button {
  font-family: 'Sarabun', sans-serif;
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* เพิ่ม CSS นี้เพื่อแก้ปัญหาการแสดงผลสระและวรรณยุกต์ */
input::placeholder {
  font-family: 'Sarabun', sans-serif;
  letter-spacing: normal;
  font-weight: normal;
}
</style>
