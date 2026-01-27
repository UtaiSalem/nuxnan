/**
 * Date Utility Functions
 * จัดการปัญหา timezone offset และการแปลงวันที่ต่างๆ
 */

/**
 * แปลง date string เป็นรูปแบบ YYYY-MM-DD สำหรับ input[type="date"]
 * แก้ปัญหา timezone offset โดยใช้ string manipulation แทน Date object
 * @param {string} dateString - วันที่ในรูปแบบ ISO string หรือ YYYY-MM-DD
 * @returns {string} - วันที่ในรูปแบบ YYYY-MM-DD
 */
export const formatDateForInput = (dateString) => {
  if (!dateString) return ''
  
  try {
    // ดึงเฉพาะส่วน YYYY-MM-DD จาก ISO string (เช่น "2010-01-08T00:00:00.000Z")
    const dateParts = dateString.split('T')[0]
    return dateParts
  } catch (error) {
    console.warn('Date formatting error:', error)
    return ''
  }
}

/**
 * แปลงวันที่เป็นรูปแบบไทย (วัน เดือน ปี พ.ศ.)
 * @param {string} dateString - วันที่ในรูปแบบ YYYY-MM-DD
 * @param {Object} options - ตัวเลือกการแสดงผล
 * @returns {string} - วันที่ในรูปแบบไทย
 */
export const formatDateThai = (dateString, options = {}) => {
  if (!dateString) return options.defaultText || 'ไม่ระบุ'
  
  try {
    // Parse date string without timezone conversion
    const dateParts = dateString.split('T')[0].split('-')
    const year = parseInt(dateParts[0])
    const month = parseInt(dateParts[1]) - 1 // Month is 0-indexed
    const day = parseInt(dateParts[2])
    
    const thaiMonths = [
      'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
      'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ]
    
    const thaiMonthsShort = [
      'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
      'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'
    ]
    
    const monthNames = options.shortMonth ? thaiMonthsShort : thaiMonths
    const thaiMonth = monthNames[month]
    const buddhistYear = year + 543 // Convert to Buddhist Era
    
    // ตัวเลือกการแสดงผล
    if (options.format === 'full') {
      const thaiDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์']
      const date = new Date(year, month, day)
      const dayName = thaiDays[date.getDay()]
      return `วัน${dayName}ที่ ${day} ${thaiMonth} พ.ศ. ${buddhistYear}`
    }
    
    if (options.format === 'short') {
      return `${day}/${month + 1}/${buddhistYear}`
    }
    
    // Default format
    return `${day} ${thaiMonth} ${buddhistYear}`
  } catch (error) {
    console.warn('Date formatting error:', error)
    return dateString
  }
}

/**
 * คำนวณอายุจากวันเกิด
 * @param {string} dateString - วันเกิดในรูปแบบ YYYY-MM-DD
 * @returns {number|string} - อายุเป็นตัวเลข หรือ '-' ถ้าไม่สามารถคำนวณได้
 */
export const calculateAge = (dateString) => {
  if (!dateString) return '-'
  
  try {
    // Parse date string without timezone conversion
    const dateParts = dateString.split('T')[0].split('-')
    const birthYear = parseInt(dateParts[0])
    const birthMonth = parseInt(dateParts[1]) - 1 // Month is 0-indexed
    const birthDay = parseInt(dateParts[2])
    
    const today = new Date()
    const currentYear = today.getFullYear()
    const currentMonth = today.getMonth()
    const currentDay = today.getDate()
    
    let age = currentYear - birthYear
    
    // ตรวจสอบว่าวันเกิดในปีนี้ผ่านไปแล้วหรือยัง
    if (currentMonth < birthMonth || (currentMonth === birthMonth && currentDay < birthDay)) {
      age--
    }
    
    return age
  } catch (error) {
    console.warn('Age calculation error:', error)
    return '-'
  }
}

/**
 * คำนวณอายุแบบละเอียด (ปี เดือน วัน)
 * @param {string} dateString - วันเกิดในรูปแบบ YYYY-MM-DD
 * @returns {Object} - { years, months, days, text }
 */
export const calculateDetailedAge = (dateString) => {
  if (!dateString) return { years: 0, months: 0, days: 0, text: '-' }
  
  try {
    const dateParts = dateString.split('T')[0].split('-')
    const birthYear = parseInt(dateParts[0])
    const birthMonth = parseInt(dateParts[1]) - 1
    const birthDay = parseInt(dateParts[2])
    
    const today = new Date()
    const birthDate = new Date(birthYear, birthMonth, birthDay)
    
    let years = today.getFullYear() - birthDate.getFullYear()
    let months = today.getMonth() - birthDate.getMonth()
    let days = today.getDate() - birthDate.getDate()
    
    if (days < 0) {
      months--
      const lastMonth = new Date(today.getFullYear(), today.getMonth(), 0)
      days += lastMonth.getDate()
    }
    
    if (months < 0) {
      years--
      months += 12
    }
    
    const text = `${years} ปี ${months} เดือน ${days} วัน`
    
    return { years, months, days, text }
  } catch (error) {
    console.warn('Detailed age calculation error:', error)
    return { years: 0, months: 0, days: 0, text: '-' }
  }
}

/**
 * แปลงวันที่จาก input[type="date"] เป็นรูปแบบที่เก็บในฐานข้อมูล
 * @param {string} dateString - วันที่จาก input (YYYY-MM-DD)
 * @returns {string} - วันที่ในรูปแบบ ISO string
 */
export const formatDateForDatabase = (dateString) => {
  if (!dateString) return null
  
  try {
    // เก็บเป็น YYYY-MM-DD เฉยๆ ไม่ต้องแปลงเป็น ISO
    return dateString
  } catch (error) {
    console.warn('Date formatting for database error:', error)
    return null
  }
}

/**
 * ตรวจสอบว่าวันที่ถูกต้องหรือไม่
 * @param {string} dateString - วันที่ในรูปแบบ YYYY-MM-DD
 * @returns {boolean}
 */
export const isValidDate = (dateString) => {
  if (!dateString) return false
  
  try {
    const dateParts = dateString.split('T')[0].split('-')
    if (dateParts.length !== 3) return false
    
    const year = parseInt(dateParts[0])
    const month = parseInt(dateParts[1])
    const day = parseInt(dateParts[2])
    
    if (year < 1900 || year > 2100) return false
    if (month < 1 || month > 12) return false
    if (day < 1 || day > 31) return false
    
    // ตรวจสอบจำนวนวันในเดือน
    const date = new Date(year, month - 1, day)
    return date.getMonth() === month - 1 && date.getDate() === day
  } catch (error) {
    return false
  }
}

/**
 * คำนวณระยะเวลาที่ผ่านไปจากวันที่ระบุ (time ago)
 * @param {string} dateString - วันที่
 * @returns {string} - ระยะเวลาที่ผ่านไป
 */
export const formatTimeAgo = (dateString) => {
  if (!dateString) return 'ไม่ระบุเวลา'
  
  try {
    const date = new Date(dateString)
    const now = new Date()
    const diffInSeconds = Math.floor((now - date) / 1000)
    
    if (diffInSeconds < 60) return 'เมื่อสักครู่'
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} นาทีที่แล้ว`
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} ชั่วโมงที่แล้ว`
    if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} วันที่แล้ว`
    if (diffInSeconds < 2592000) return `${Math.floor(diffInSeconds / 604800)} สัปดาห์ที่แล้ว`
    if (diffInSeconds < 31536000) return `${Math.floor(diffInSeconds / 2592000)} เดือนที่แล้ว`
    
    return `${Math.floor(diffInSeconds / 31536000)} ปีที่แล้ว`
  } catch (error) {
    console.warn('Time ago formatting error:', error)
    return 'ไม่ระบุเวลา'
  }
}

/**
 * แปลงวันที่เป็นรูปแบบสำหรับแสดงผล
 * @param {string} dateString - วันที่
 * @param {string} format - รูปแบบ ('thai', 'short', 'full', 'iso')
 * @returns {string}
 */
export const formatDate = (dateString, format = 'thai') => {
  if (!dateString) return ''
  
  switch (format) {
    case 'thai':
      return formatDateThai(dateString)
    case 'short':
      return formatDateThai(dateString, { format: 'short' })
    case 'full':
      return formatDateThai(dateString, { format: 'full' })
    case 'iso':
      return formatDateForInput(dateString)
    default:
      return formatDateThai(dateString)
  }
}

/**
 * เปรียบเทียบวันที่ 2 วัน
 * @param {string} date1 - วันที่ 1
 * @param {string} date2 - วันที่ 2
 * @returns {number} - -1 (date1 < date2), 0 (เท่ากัน), 1 (date1 > date2)
 */
export const compareDates = (date1, date2) => {
  if (!date1 || !date2) return 0
  
  try {
    const d1 = new Date(date1)
    const d2 = new Date(date2)
    
    if (d1 < d2) return -1
    if (d1 > d2) return 1
    return 0
  } catch (error) {
    console.warn('Date comparison error:', error)
    return 0
  }
}

/**
 * ดึงวันที่ปัจจุบันในรูปแบบ YYYY-MM-DD
 * @returns {string}
 */
export const getCurrentDate = () => {
  const today = new Date()
  const year = today.getFullYear()
  const month = String(today.getMonth() + 1).padStart(2, '0')
  const day = String(today.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

/**
 * ดึงวันที่ปัจจุบันในรูปแบบไทย
 * @returns {string}
 */
export const getCurrentDateThai = () => {
  return formatDateThai(getCurrentDate())
}

// Export ทั้งหมดเป็น default object
export default {
  formatDateForInput,
  formatDateThai,
  calculateAge,
  calculateDetailedAge,
  formatDateForDatabase,
  isValidDate,
  formatTimeAgo,
  formatDate,
  compareDates,
  getCurrentDate,
  getCurrentDateThai
}
