/**
 * Text Utility Functions
 * ฟังก์ชันจัดการข้อความ
 */

/**
 * ลบ HTML tags ออกจากข้อความ แล้วคืนค่าเป็น plain text
 * ใช้สำหรับแสดงคำตอบของนักเรียนที่อาจมี HTML จากการคัดลอก
 * @param {string} html - ข้อความที่อาจมี HTML tags
 * @returns {string} - ข้อความ plain text
 */
export const stripHtml = (html) => {
  if (!html) return ''
  if (typeof html !== 'string') return String(html)
  
  // Replace <br>, <br/>, <p>, <div> with newlines before stripping
  let text = html
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/<\/p>/gi, '\n')
    .replace(/<\/div>/gi, '\n')
    .replace(/<\/li>/gi, '\n')
    .replace(/<\/tr>/gi, '\n')
    .replace(/<\/h[1-6]>/gi, '\n')
  
  // Strip all remaining HTML tags
  text = text.replace(/<[^>]*>/g, '')
  
  // Decode common HTML entities
  text = text
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#039;/g, "'")
    .replace(/&nbsp;/g, ' ')
  
  // Collapse multiple consecutive newlines into max 2
  text = text.replace(/\n{3,}/g, '\n\n')
  
  return text.trim()
}
