<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  courseId: Number,
  memberId: Number
})

const quizScores = ref([])
const totalScore = ref(0)
const loading = ref(true)

const loadGrades = async () => {
  try {
    const res = await axios.get(`/courses/${props.courseId}/members/${props.memberId}/process-grades`)
    quizScores.value = res.data.quiz_scores.sort((a, b) => a.quiz_id - b.quiz_id) // เรียงลำดับตาม quiz_id
    totalScore.value = res.data.total_score
  } catch (error) {
    console.error('เกิดข้อผิดพลาดในการโหลดข้อมูล:', error)
  } finally {
    loading.value = false
  }
}

onMounted(loadGrades)
</script>


<template>
  <div class="p-6">
    <h2 class="text-xl font-bold mb-4">สรุปผลคะแนนรายวิชา</h2>

    <div v-if="loading" class="text-gray-500">กำลังโหลดข้อมูล...</div>

    <div v-else>
      <table class="w-full border border-gray-300 rounded-lg">
        <thead class="bg-gray-100">
          <tr>
            <th v-for="i in quizScores.length" class="p-2 border">#{{i}}</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td  v-for="quiz in quizScores" :key="quiz.quiz_id" class="p-2 border text-center">{{ quiz.score }}</td>
          </tr>
        </tbody>
      </table>

      <div class="mt-4 p-4 bg-blue-50 rounded-lg text-center">
        <h3 class="text-lg font-semibold">คะแนนรวมทั้งหมด</h3>
        <p class="text-2xl font-bold text-blue-600">{{ totalScore }}</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
table {
  border-collapse: collapse;
}
</style>
