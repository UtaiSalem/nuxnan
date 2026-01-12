/**
 * Vue 3 Composable สำหรับจัดการวันที่
 * ใช้งานง่ายใน Vue components
 */

import { computed, ref } from 'vue'
import {
  formatDateForInput,
  formatDateThai,
  calculateAge,
  calculateDetailedAge,
  formatTimeAgo,
  formatDate,
  isValidDate,
  getCurrentDate,
  getCurrentDateThai
} from '../utils/dateUtils'

/**
 * Composable หลักสำหรับจัดการวันที่
 * @param {string|ref} dateValue - วันที่เริ่มต้น
 */
export const useDate = (dateValue = null) => {
  const date = ref(dateValue)
  
  // Computed properties สำหรับการแสดงผลต่างๆ
  const dateForInput = computed(() => formatDateForInput(date.value))
  const dateThai = computed(() => formatDateThai(date.value))
  const dateThaiShort = computed(() => formatDateThai(date.value, { format: 'short' }))
  const dateThaiLong = computed(() => formatDateThai(date.value, { format: 'full' }))
  const age = computed(() => calculateAge(date.value))
  const detailedAge = computed(() => calculateDetailedAge(date.value))
  const timeAgo = computed(() => formatTimeAgo(date.value))
  const isValid = computed(() => isValidDate(date.value))
  
  // Methods
  const setDate = (newDate) => {
    date.value = newDate
  }
  
  const clear = () => {
    date.value = null
  }
  
  const setToday = () => {
    date.value = getCurrentDate()
  }
  
  return {
    date,
    dateForInput,
    dateThai,
    dateThaiShort,
    dateThaiLong,
    age,
    detailedAge,
    timeAgo,
    isValid,
    setDate,
    clear,
    setToday
  }
}

/**
 * Composable สำหรับจัดการวันเกิดโดยเฉพาะ
 * @param {string|ref} birthDate - วันเกิด
 */
export const useBirthDate = (birthDate = null) => {
  const dateComposable = useDate(birthDate)
  
  // เพิ่ม computed properties เฉพาะสำหรับวันเกิด
  const ageInYears = computed(() => {
    const ageResult = calculateAge(dateComposable.date.value)
    return typeof ageResult === 'number' ? ageResult : 0
  })
  
  const isAdult = computed(() => ageInYears.value >= 18)
  const isMinor = computed(() => ageInYears.value < 18)
  const isChild = computed(() => ageInYears.value < 12)
  const isTeen = computed(() => ageInYears.value >= 12 && ageInYears.value < 18)
  
  // คำนวณวันเกิดครั้งถัดไป
  const nextBirthday = computed(() => {
    if (!dateComposable.date.value) return null
    
    try {
      const dateParts = dateComposable.date.value.split('T')[0].split('-')
      const birthMonth = parseInt(dateParts[1]) - 1
      const birthDay = parseInt(dateParts[2])
      
      const today = new Date()
      const currentYear = today.getFullYear()
      let nextBirthday = new Date(currentYear, birthMonth, birthDay)
      
      // ถ้าวันเกิดปีนี้ผ่านไปแล้ว ให้นับปีหน้า
      if (nextBirthday < today) {
        nextBirthday = new Date(currentYear + 1, birthMonth, birthDay)
      }
      
      const diffTime = nextBirthday - today
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      
      return {
        date: nextBirthday,
        daysUntil: diffDays,
        text: `อีก ${diffDays} วัน`
      }
    } catch (error) {
      return null
    }
  })
  
  // ตรวจสอบว่าวันนี้เป็นวันเกิดหรือไม่
  const isBirthdayToday = computed(() => {
    if (!dateComposable.date.value) return false
    
    try {
      const dateParts = dateComposable.date.value.split('T')[0].split('-')
      const birthMonth = parseInt(dateParts[1])
      const birthDay = parseInt(dateParts[2])
      
      const today = new Date()
      const currentMonth = today.getMonth() + 1
      const currentDay = today.getDate()
      
      return birthMonth === currentMonth && birthDay === currentDay
    } catch (error) {
      return false
    }
  })
  
  return {
    ...dateComposable,
    ageInYears,
    isAdult,
    isMinor,
    isChild,
    isTeen,
    nextBirthday,
    isBirthdayToday
  }
}

/**
 * Composable สำหรับจัดรูปแบบวันที่หลายๆ แบบ
 */
export const useDateFormatter = () => {
  return {
    toInput: formatDateForInput,
    toThai: formatDateThai,
    toTimeAgo: formatTimeAgo,
    format: formatDate,
    validate: isValidDate,
    getToday: getCurrentDate,
    getTodayThai: getCurrentDateThai,
    formatRelative: formatTimeAgo
  }
}

export default {
  useDate,
  useBirthDate,
  useDateFormatter
}
