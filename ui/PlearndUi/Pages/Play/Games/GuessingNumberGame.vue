<script setup>
import { ref, watch } from 'vue'
import confetti from 'canvas-confetti'

import GameLayout from '@/Layouts/GameLayout.vue'

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
    <GameLayout>
        <div class="w-full h-full flex items-center justify-center">
            <div class="bg-white rounded-lg shadow-md max-w-md w-full">
                <div class="px-8 py-8">
                    <h1 class="text-3xl font-bold text-center mb-6 text-blue-600">เกมทายตัวเลข</h1>
                    <p class="text-gray-600 text-center mb-4">ทายตัวเลขระหว่าง 1-100</p>
                    <!-- เปลี่ยนจาก type="number" เป็น type="text" -->
                    <!-- เพิ่ม inputmode="numeric" เพื่อแสดงแป้นพิมพ์ตัวเลขบนมือถือ -->
                    <!-- เพิ่ม pattern เพื่อจำกัดให้รับเฉพาะตัวเลข -->
                    <p class="text-center text-gray-600 m-4" >
                        คุณทาย {{ attempts }} ครั้ง และได้คะแนน {{ score }} คะแนน
                    </p>
                    <p class="text-center text-lg font-semibold" :class="{
                        'text-green-600': message.includes('ยินดีด้วย'),
                        'text-red-600': message.includes('น้อยเกินไป') || message.includes('มากเกินไป')
                    }">
                        {{ message }}
                    </p>
                    <div class="mb-4">
                        <input 
                            v-model="guess" 
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            :disabled="isGameOver"
                            @keyup.enter="checkGuess"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="ใส่ตัวเลขที่คุณทาย"
                        />
                    </div>
                    <div class="flex space-x-2 mb-4">
                        <button @click="checkGuess" :disabled="isGameOver || !guess"
                            class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50">
                            ทาย
                        </button>
                        <button @click="resetGame"
                            class="flex-1 bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500">
                            เล่นใหม่
                        </button>
                    </div>


                </div>
            </div>
        </div>
    </GameLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap');

body, input, button {
  font-family: 'Sarabun', sans-serif;
}

input {
  font-size: 16px;
  line-height: 2;
}

/* เพิ่ม CSS นี้เพื่อแก้ปัญหาการแสดงผลสระและวรรณยุกต์ */
input::-webkit-input-placeholder {
  font-family: 'Sarabun', sans-serif;
  line-height: 1.5;
}
input::-moz-placeholder {
  font-family: 'Sarabun', sans-serif;
  line-height: 1.5;
}
input:-ms-input-placeholder {
  font-family: 'Sarabun', sans-serif;
  line-height: 1.5;
}
input::placeholder {
  font-family: 'Sarabun', sans-serif;
  line-height: 1.5;
}
</style>
