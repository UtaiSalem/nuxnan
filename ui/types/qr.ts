/**
 * Universal QR Code System Types
 * 
 * QR Code Format: PREFIX:DATA
 * Examples:
 * - COUPON:12345678          → Redeem coupon
 * - CHECKIN:class_123:sess_1 → Check-in to class
 * - EVENT:event_123          → Check-in to event
 * - POLL:poll_123            → Answer poll
 * - SHARE:user_ref_code      → View user profile
 * - COURSE:course_123        → View course
 * - ACADEMY:academy_123      → View academy
 * - REWARD:reward_123        → Claim reward
 */

// QR Code Types
export type QRCodeType = 
  | 'coupon'      // Redeem points/wallet coupon
  | 'checkin'     // Class attendance check-in
  | 'event'       // Event attendance
  | 'poll'        // Answer poll/survey
  | 'share'       // User profile sharing
  | 'course'      // View course
  | 'academy'     // View academy
  | 'reward'      // Claim reward
  | 'unknown'     // Unknown QR type

// QR Type Configuration
export interface QRTypeConfig {
  type: QRCodeType
  prefix: string
  label: string
  icon: string
  color: string
  bgColor: string
  description: string
}

// QR Types Registry
export const QR_TYPES: Record<QRCodeType, QRTypeConfig> = {
  coupon: {
    type: 'coupon',
    prefix: 'COUPON',
    label: 'รับคูปอง',
    icon: 'fluent:ticket-diagonal-24-filled',
    color: 'text-purple-600',
    bgColor: 'bg-purple-100',
    description: 'รับแต้มหรือเงินจากคูปอง'
  },
  checkin: {
    type: 'checkin',
    prefix: 'CHECKIN',
    label: 'เช็คชื่อเข้าเรียน',
    icon: 'fluent:person-available-24-filled',
    color: 'text-blue-600',
    bgColor: 'bg-blue-100',
    description: 'เช็คชื่อเข้าเรียนในชั้นเรียน'
  },
  event: {
    type: 'event',
    prefix: 'EVENT',
    label: 'เข้าร่วมกิจกรรม',
    icon: 'fluent:calendar-checkmark-24-filled',
    color: 'text-green-600',
    bgColor: 'bg-green-100',
    description: 'เช็คชื่อเข้าร่วมกิจกรรม'
  },
  poll: {
    type: 'poll',
    prefix: 'POLL',
    label: 'ตอบแบบสำรวจ',
    icon: 'fluent:poll-24-filled',
    color: 'text-orange-600',
    bgColor: 'bg-orange-100',
    description: 'ตอบแบบสำรวจหรือโพล'
  },
  share: {
    type: 'share',
    prefix: 'SHARE',
    label: 'โปรไฟล์ผู้ใช้',
    icon: 'fluent:person-24-filled',
    color: 'text-cyan-600',
    bgColor: 'bg-cyan-100',
    description: 'ดูโปรไฟล์ผู้ใช้'
  },
  course: {
    type: 'course',
    prefix: 'COURSE',
    label: 'ดูรายวิชา',
    icon: 'fluent:book-24-filled',
    color: 'text-indigo-600',
    bgColor: 'bg-indigo-100',
    description: 'เข้าดูรายละเอียดรายวิชา'
  },
  academy: {
    type: 'academy',
    prefix: 'ACADEMY',
    label: 'ดูโรงเรียน',
    icon: 'fluent:building-24-filled',
    color: 'text-rose-600',
    bgColor: 'bg-rose-100',
    description: 'เข้าดูรายละเอียดโรงเรียน'
  },
  reward: {
    type: 'reward',
    prefix: 'REWARD',
    label: 'รับรางวัล',
    icon: 'fluent:gift-24-filled',
    color: 'text-amber-600',
    bgColor: 'bg-amber-100',
    description: 'รับรางวัลพิเศษ'
  },
  unknown: {
    type: 'unknown',
    prefix: '',
    label: 'ไม่รู้จัก',
    icon: 'fluent:question-circle-24-filled',
    color: 'text-gray-600',
    bgColor: 'bg-gray-100',
    description: 'QR Code ที่ไม่รู้จัก'
  }
}

// Parsed QR Data
export interface ParsedQRData {
  type: QRCodeType
  config: QRTypeConfig
  rawData: string
  data: string[]  // Split data parts
  isValid: boolean
}

// QR Action Result
export interface QRActionResult {
  success: boolean
  type: QRCodeType
  message: string
  data?: any
}

// Parse QR code string to structured data
export function parseQRCode(qrString: string): ParsedQRData {
  const trimmed = qrString.trim().toUpperCase()
  
  // Try to match known prefixes
  for (const [key, config] of Object.entries(QR_TYPES)) {
    if (key === 'unknown') continue
    
    if (trimmed.startsWith(config.prefix + ':')) {
      const dataString = qrString.trim().substring(config.prefix.length + 1)
      const dataParts = dataString.split(':')
      
      return {
        type: config.type,
        config,
        rawData: qrString,
        data: dataParts,
        isValid: dataParts.length > 0 && dataParts[0].length > 0
      }
    }
  }
  
  // Check if it's a pure numeric code (legacy coupon format)
  if (/^\d{8}$/.test(trimmed)) {
    return {
      type: 'coupon',
      config: QR_TYPES.coupon,
      rawData: qrString,
      data: [trimmed],
      isValid: true
    }
  }
  
  // Unknown QR code
  return {
    type: 'unknown',
    config: QR_TYPES.unknown,
    rawData: qrString,
    data: [qrString],
    isValid: false
  }
}

// Generate QR code content string
export function generateQRContent(type: QRCodeType, ...data: string[]): string {
  const config = QR_TYPES[type]
  if (!config || type === 'unknown') {
    throw new Error(`Invalid QR type: ${type}`)
  }
  
  return `${config.prefix}:${data.join(':')}`
}
