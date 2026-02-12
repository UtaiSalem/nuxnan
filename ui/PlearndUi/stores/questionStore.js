// stores/useQuestionStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useQuestionStore = defineStore('question', () => {
  const selectedOption = ref(null)
  const correctAnswer = ref(null)
  const showConfirmAnswerButton = ref(false)
  const canEditAnswer = ref(false)
  const isLoading = ref(false)
  const isImageLoading = ref(false)
  const showEditModal = ref(false)

  const initQuestion = (question) => {
    selectedOption.value = question.isAnsweredByAuth
    showConfirmAnswerButton.value = !question.isAnsweredByAuth
    canEditAnswer.value = !!question.isAnsweredByAuth
    // Initialize other state as needed
  }

  const confirmAnswer = async (question) => {
    try {
      isLoading.value = true
      const url = question.isAnsweredByAuth === null
        ? `/quizs/${question.questionable_id}/questions/${question.id}/answers`
        : `/quizs/${question.questionable_id}/questions/${question.id}/answers/${question.authAnswerQuestion}`

      const method = question.isAnsweredByAuth === null ? 'post' : 'patch'

      const response = await axios[method](url, {
        course_id: question.course_id,
        answer_id: selectedOption.value,
      })

      if (response.data.success) {
        question.authAnswerQuestion = response.data.authAnswerQuestion
        question.isAnsweredByAuth = true
        showConfirmAnswerButton.value = false
        canEditAnswer.value = true
        // Show success message
      } else {
        // Show error message
      }
    } catch (error) {
      console.error(error)
      // Show error message
    } finally {
      isLoading.value = false
    }
  }

  const deleteOption = async (questionId, optionId, idx) => {
    await axios.delete(`/options/${optionId}`)
    // Update the options array in the question object
  }

  const setCorrectAnswer = async (questionId, answerId) => {
    if (answerId) {
      await axios.patch(`/questions/${questionId}/set_correct_option`, { answer: answerId })
      correctAnswer.value = answerId
    } else {
      // Show error message
    }
  }

  const setCanEditAnswer = (ppFine) => {
    // Logic for setting canEditAnswer
  }

  const setSelectedOption = (answerId) => {
    selectedOption.value = answerId
  }

  const deleteImage = async (questionId, index) => {
    // Logic for deleting an image
  }

  return {
    selectedOption,
    correctAnswer,
    showConfirmAnswerButton,
    canEditAnswer,
    isLoading,
    isImageLoading,
    showEditModal,
    initQuestion,
    confirmAnswer,
    deleteOption,
    setCorrectAnswer,
    setCanEditAnswer,
    setSelectedOption,
    deleteImage
  }
})
