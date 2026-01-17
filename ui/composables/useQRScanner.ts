/**
 * Universal QR Scanner Composable
 * 
 * Provides unified QR code scanning functionality with:
 * - Camera scanning
 * - QR type detection
 * - Action routing based on QR type
 */

import { ref, onUnmounted } from 'vue'
import jsQRLib from 'jsqr'
import { 
  parseQRCode, 
  type ParsedQRData, 
  type QRCodeType, 
  type QRActionResult,
  QR_TYPES 
} from '~/types/qr'

// API Response type
interface ApiResponse {
  success: boolean
  message?: string
  data?: any
}

export function useQRScanner() {
  const api = useApi()
  const toast = useToast()
  const router = useRouter()
  const authStore = useAuthStore()

  // State
  const isScanning = ref(false)
  const isProcessing = ref(false)
  const lastScannedData = ref<ParsedQRData | null>(null)
  const actionResult = ref<QRActionResult | null>(null)
  const error = ref<string | null>(null)

  // Camera refs
  const videoRef = ref<HTMLVideoElement | null>(null)
  const canvasRef = ref<HTMLCanvasElement | null>(null)
  let stream: MediaStream | null = null
  let animationId: number | null = null

  // Start camera scanning
  const startScanning = async (video: HTMLVideoElement, canvas: HTMLCanvasElement) => {
    videoRef.value = video
    canvasRef.value = canvas
    error.value = null

    try {
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' }
      })
      
      video.srcObject = stream
      await video.play()
      isScanning.value = true
      
      // Start scanning loop
      scanFrame()
    } catch (err: any) {
      error.value = 'ไม่สามารถเปิดกล้องได้'
      toast.error('ไม่สามารถเปิดกล้องได้: ' + err.message)
      throw err
    }
  }

  // Stop camera scanning
  const stopScanning = () => {
    if (stream) {
      stream.getTracks().forEach(track => track.stop())
      stream = null
    }
    if (animationId) {
      cancelAnimationFrame(animationId)
      animationId = null
    }
    isScanning.value = false
  }

  // Scan single frame
  const scanFrame = () => {
    if (!videoRef.value || !canvasRef.value || !isScanning.value) return

    const video = videoRef.value
    const canvas = canvasRef.value
    const ctx = canvas.getContext('2d')

    if (!ctx || video.readyState !== video.HAVE_ENOUGH_DATA) {
      animationId = requestAnimationFrame(scanFrame)
      return
    }

    canvas.width = video.videoWidth
    canvas.height = video.videoHeight
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height)

    // Try to detect QR code using jsQR
    try {
      const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height)
      const code = jsQRLib(imageData.data, imageData.width, imageData.height)

      if (code) {
        // QR Code detected!
        handleQRDetected(code.data)
        return
      }
    } catch (e) {
      // jsQR parsing error, continue scanning
    }

    animationId = requestAnimationFrame(scanFrame)
  }

  // Handle QR code detection
  const handleQRDetected = async (qrString: string) => {
    if (isProcessing.value) return

    stopScanning()
    await processQRCode(qrString)
  }

  // Process QR code manually (from input)
  const processQRCode = async (qrString: string): Promise<QRActionResult> => {
    isProcessing.value = true
    error.value = null
    actionResult.value = null

    try {
      // Parse QR code
      const parsed = parseQRCode(qrString)
      lastScannedData.value = parsed

      if (!parsed.isValid) {
        const result: QRActionResult = {
          success: false,
          type: 'unknown',
          message: 'QR Code ไม่ถูกต้องหรือไม่รู้จัก'
        }
        actionResult.value = result
        return result
      }

      // Execute action based on type
      const result = await executeQRAction(parsed)
      actionResult.value = result
      return result

    } catch (err: any) {
      const result: QRActionResult = {
        success: false,
        type: 'unknown',
        message: err.message || 'เกิดข้อผิดพลาด'
      }
      actionResult.value = result
      error.value = result.message
      return result
    } finally {
      isProcessing.value = false
    }
  }

  // Execute action based on QR type
  const executeQRAction = async (parsed: ParsedQRData): Promise<QRActionResult> => {
    switch (parsed.type) {
      case 'coupon':
        return await handleCouponQR(parsed)
      
      case 'checkin':
        return await handleCheckinQR(parsed)
      
      case 'event':
        return await handleEventQR(parsed)
      
      case 'poll':
        return await handlePollQR(parsed)
      
      case 'share':
        return handleShareQR(parsed)
      
      case 'course':
        return handleCourseQR(parsed)
      
      case 'academy':
        return handleAcademyQR(parsed)
      
      case 'reward':
        return await handleRewardQR(parsed)
      
      default:
        return {
          success: false,
          type: 'unknown',
          message: 'ไม่รู้จักประเภท QR Code นี้'
        }
    }
  }

  // === QR Type Handlers ===

  // Handle Coupon QR
  const handleCouponQR = async (parsed: ParsedQRData): Promise<QRActionResult> => {
    const couponCode = parsed.data[0]
    
    try {
      const response = await api.post('/api/coupons/redeem', {
        coupon_code: couponCode.replace(/\D/g, '')
      }) as ApiResponse

      if (response.success) {
        // Update user balance
        if (response.data?.type === 'points') {
          authStore.setPoints(response.data.new_balance)
        }

        return {
          success: true,
          type: 'coupon',
          message: response.message || 'รับคูปองสำเร็จ!',
          data: {
            couponType: response.data?.type,
            amount: response.data?.amount,
            newBalance: response.data?.new_balance
          }
        }
      } else {
        return {
          success: false,
          type: 'coupon',
          message: response.message || 'ไม่สามารถใช้คูปองได้'
        }
      }
    } catch (err: any) {
      // $fetch throws error for 4xx responses, extract message from response data
      const errorMessage = err.data?.message || err.message || 'เกิดข้อผิดพลาดในการใช้คูปอง'
      return {
        success: false,
        type: 'coupon',
        message: errorMessage
      }
    }
  }

  // Handle Class Check-in QR
  const handleCheckinQR = async (parsed: ParsedQRData): Promise<QRActionResult> => {
    const [classId, sessionId] = parsed.data

    try {
      const response = await api.post('/api/classes/checkin', {
        class_id: classId,
        session_id: sessionId
      }) as ApiResponse

      if (response.success) {
        return {
          success: true,
          type: 'checkin',
          message: response.message || 'เช็คชื่อสำเร็จ!',
          data: response.data
        }
      } else {
        return {
          success: false,
          type: 'checkin',
          message: response.message || 'ไม่สามารถเช็คชื่อได้'
        }
      }
    } catch (err: any) {
      return {
        success: false,
        type: 'checkin',
        message: err.message || 'เกิดข้อผิดพลาดในการเช็คชื่อ'
      }
    }
  }

  // Handle Event Check-in QR
  const handleEventQR = async (parsed: ParsedQRData): Promise<QRActionResult> => {
    const eventId = parsed.data[0]

    try {
      const response = await api.post('/api/events/checkin', {
        event_id: eventId
      }) as ApiResponse

      if (response.success) {
        return {
          success: true,
          type: 'event',
          message: response.message || 'เข้าร่วมกิจกรรมสำเร็จ!',
          data: response.data
        }
      } else {
        return {
          success: false,
          type: 'event',
          message: response.message || 'ไม่สามารถเข้าร่วมกิจกรรมได้'
        }
      }
    } catch (err: any) {
      return {
        success: false,
        type: 'event',
        message: err.message || 'เกิดข้อผิดพลาด'
      }
    }
  }

  // Handle Poll QR
  const handlePollQR = async (parsed: ParsedQRData): Promise<QRActionResult> => {
    const pollId = parsed.data[0]

    // Navigate to poll page
    router.push(`/polls/${pollId}`)

    return {
      success: true,
      type: 'poll',
      message: 'กำลังเปิดแบบสำรวจ...',
      data: { pollId }
    }
  }

  // Handle Share/Profile QR
  const handleShareQR = (parsed: ParsedQRData): QRActionResult => {
    const userRefCode = parsed.data[0]

    // Navigate to user profile
    router.push(`/profile/${userRefCode}`)

    return {
      success: true,
      type: 'share',
      message: 'กำลังเปิดโปรไฟล์...',
      data: { userRefCode }
    }
  }

  // Handle Course QR
  const handleCourseQR = (parsed: ParsedQRData): QRActionResult => {
    const courseId = parsed.data[0]

    // Navigate to course page
    router.push(`/learn/courses/${courseId}`)

    return {
      success: true,
      type: 'course',
      message: 'กำลังเปิดรายวิชา...',
      data: { courseId }
    }
  }

  // Handle Academy QR
  const handleAcademyQR = (parsed: ParsedQRData): QRActionResult => {
    const academyId = parsed.data[0]

    // Navigate to academy page
    router.push(`/academies/${academyId}`)

    return {
      success: true,
      type: 'academy',
      message: 'กำลังเปิดโรงเรียน...',
      data: { academyId }
    }
  }

  // Handle Reward QR
  const handleRewardQR = async (parsed: ParsedQRData): Promise<QRActionResult> => {
    const rewardId = parsed.data[0]

    try {
      const response = await api.post('/api/rewards/claim', {
        reward_id: rewardId
      }) as ApiResponse

      if (response.success) {
        return {
          success: true,
          type: 'reward',
          message: response.message || 'รับรางวัลสำเร็จ!',
          data: response.data
        }
      } else {
        return {
          success: false,
          type: 'reward',
          message: response.message || 'ไม่สามารถรับรางวัลได้'
        }
      }
    } catch (err: any) {
      return {
        success: false,
        type: 'reward',
        message: err.message || 'เกิดข้อผิดพลาด'
      }
    }
  }

  // Reset state
  const reset = () => {
    lastScannedData.value = null
    actionResult.value = null
    error.value = null
    isProcessing.value = false
  }

  // Cleanup on unmount
  onUnmounted(() => {
    stopScanning()
  })

  return {
    // State
    isScanning,
    isProcessing,
    lastScannedData,
    actionResult,
    error,

    // Methods
    startScanning,
    stopScanning,
    processQRCode,
    handleQRDetected,
    reset,

    // Utilities
    parseQRCode,
    QR_TYPES
  }
}
