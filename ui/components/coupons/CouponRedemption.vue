<template>
  <div class="coupon-redemption">
    <h2 class="page-title">รับคูปอง</h2>
    
    <div class="redemption-methods">
      <div class="method-selector">
        <button 
          @click="method = 'scan'" 
          :class="{ active: method === 'scan' }"
          class="method-btn"
        >
          <span class="btn-icon">📷</span>
          <span>สแกน QR Code</span>
        </button>
        <button 
          @click="method = 'code'" 
          :class="{ active: method === 'code' }"
          class="method-btn"
        >
          <span class="btn-icon">⌨️</span>
          <span>ป้อนรหัส</span>
        </button>
      </div>

      <div class="scan-section" v-if="method === 'scan'">
        <div class="scanner-container" :class="{ scanning: isScanning }">
          <video 
            ref="videoRef" 
            class="scanner-video"
            autoplay 
            playsinline
          ></video>
          <canvas ref="canvasRef" class="scanner-canvas"></canvas>
          
          <div class="scanner-overlay" v-if="!isScanning">
            <div class="scan-icon">📷</div>
            <p class="scan-text">คลิกเพื่อเริ่มสแกน QR Code</p>
          </div>
          
          <div class="scan-line" v-if="isScanning"></div>
        </div>
        
        <button 
          @click="toggleScanning" 
          class="scan-toggle-btn"
          :class="{ scanning: isScanning }"
        >
          {{ isScanning ? 'หยุดสแกน' : 'เริ่มสแกน' }}
        </button>
      </div>

      <div class="code-section" v-if="method === 'code'">
        <div class="code-input-container">
          <input 
            v-model="couponCode" 
            type="text" 
            placeholder="ป้อนรหัสคูปอง 12 ตัวอักษร"
            maxlength="12"
            class="code-input"
            @keyup.enter="redeemCoupon"
            :disabled="isLoading"
          />
          <button 
            @click="pasteCode" 
            class="paste-btn"
            title="วางจากคลิปบอร์ด"
          >
            📋
          </button>
        </div>
        
        <button 
          @click="redeemCoupon" 
          :disabled="!couponCode || isLoading"
          class="redeem-btn"
        >
          <span v-if="isLoading" class="spinner"></span>
          <span v-else>รับคูปอง</span>
        </button>
      </div>
    </div>

    <div class="result-section" v-if="result">
      <div :class="['result-card', result.success ? 'success' : 'error']">
        <div class="result-icon">
          {{ result.success ? '✅' : '❌' }}
        </div>
        <div class="result-content">
          <h3 class="result-title">{{ result.success ? 'สำเร็จ!' : 'ล้มเหลว' }}</h3>
          <p class="result-message">{{ result.message }}</p>
          
          <div class="result-details" v-if="result.success">
            <div class="detail-item">
              <span class="detail-label">ประเภท:</span>
              <span class="detail-value">
                {{ result.type === 'points' ? 'คูปองแต้ม' : 'คูปองเงิน' }}
              </span>
            </div>
            <div class="detail-item">
              <span class="detail-label">จำนวน:</span>
              <span class="detail-value">{{ result.amount }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">ยอด{{ result.type === 'points' ? 'แต้ม' : 'เงิน' }}ใหม่:</span>
              <span class="detail-value">{{ result.new_balance }}</span>
            </div>
          </div>
        </div>
        
        <button 
          @click="reset" 
          class="reset-btn"
        >
          รับคูปองอีก
        </button>
      </div>
    </div>

    <div class="info-section">
      <div class="info-card">
        <span class="info-icon">ℹ️</span>
        <div class="info-content">
          <h4>ข้อมูลเพิ่มเติม</h4>
          <ul class="info-list">
            <li>คูปองสามารถใช้งานได้เพียง 1 ครั้ง</li>
            <li>ไม่สามารถใช้คูปองของตัวเองได้</li>
            <li>คูปองจะหมดอายุหลังจากวันที่ระบุไว้</li>
            <li>ตรวจสอบประวัตคูปองได้จากประวัตตัวเลือก</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import { useApi } from '~/composables/useApi'
import { useToast } from '~/composables/useToast'
import jsQR from 'jsqr'

const api = useApi()
const toast = useToast()
const swal = useSweetAlert()

const method = ref<'scan' | 'code'>('code')
const couponCode = ref<string>('')
const isLoading = ref(false)
const isScanning = ref(false)
const result = ref<any>(null)

const videoRef = ref<HTMLVideoElement | null>(null)
const canvasRef = ref<HTMLCanvasElement | null>(null)

let barcodeDetector: any = null
let animationFrameId: number | null = null

async function toggleScanning() {
  if (isScanning.value) {
    stopScanning()
  } else {
    startScanning()
  }
}

async function startScanning() {
  if (!videoRef.value || !canvasRef.value) return
  
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facing: 'environment' }
    })
    
    if (videoRef.value) {
      videoRef.value.srcObject = stream
      isScanning.value = true
      
      // Initialize barcode detector
      await initializeBarcodeDetector()
      
      // Start scanning loop
      scanLoop()
    }
  } catch (err: any) {
    toast.error('ไม่สามารถเข้าถึงกล้องได้: ' + err.message)
  }
}

async function initializeBarcodeDetector() {
  // jsQR is imported at the top - no dynamic import needed
  barcodeDetector = true // Flag that we're ready to scan
}

function scanLoop() {
  if (!videoRef.value || !canvasRef.value || !barcodeDetector) return
  
  const video = videoRef.value
  const canvas = canvasRef.value
  const ctx = canvas.getContext('2d')
  
  if (!ctx) return
  
  function scan() {
    if (!isScanning.value) return
    
    // Draw video frame to canvas
    canvas.width = video.videoWidth
    canvas.height = video.videoHeight
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height)
    
    // Detect QR code using jsQR
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height)
    const code = jsQR(imageData.data, imageData.width, imageData.height)
    
    if (code && code.data) {
      // QR code detected
      stopScanning()
      couponCode.value = code.data
      toast.success('ตรวจพบ QR Code!')
      return
    }
    
    animationFrameId = requestAnimationFrame(scan)
  }
  
  scan()
}

function stopScanning() {
  isScanning.value = false
  
  if (animationFrameId !== null) {
    cancelAnimationFrame(animationFrameId)
    animationFrameId = null
  }
  
  if (videoRef.value && videoRef.value.srcObject) {
    const tracks = videoRef.value.srcObject.getTracks()
    tracks.forEach((track: any) => track.stop())
    videoRef.value.srcObject = null
  }
}

async function pasteCode() {
  try {
    const text = await navigator.clipboard.readText()
    if (text) {
      couponCode.value = text.toUpperCase().trim()
      toast.success('วางรหัสคูปองแล้ว')
    }
  } catch (err: any) {
    toast.error('ไม่สามารถวางจากคลิปบอร์ดได้')
  }
}

async function redeemCoupon() {
  if (!couponCode.value || isLoading.value) return
  
  isLoading.value = true
  result.value = null
  
  try {
    const response = await api.post('/api/coupons/redeem', {
      coupon_code: couponCode.value.toUpperCase().trim()
    })
    
    result.value = response
    
    if (response.success) {
      toast.success(`รับ${response.type === 'points' ? 'แต้ม' : 'เงิน'}สำเร็จ!`)
      
      // Clear input after successful redemption
      couponCode.value = ''
    } else {
      swal.error(response.message || 'ไม่สามารถรับคูปองได้')
    }
  } catch (err: any) {
    result.value = {
      success: false,
      message: err.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่'
    }
    swal.error(result.value.message)
  } finally {
    isLoading.value = false
  }
}

function reset() {
  result.value = null
  couponCode.value = ''
}

onUnmounted(() => {
  stopScanning()
})
</script>

<style scoped>
.coupon-redemption {
  max-width: 600px;
  margin: 0 auto;
  padding: 24px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  text-align: center;
  margin-bottom: 32px;
  color: #1a1a2e;
}

.redemption-methods {
  background: white;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.method-selector {
  display: flex;
  gap: 16px;
  margin-bottom: 32px;
}

.method-btn {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 20px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  background: white;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 16px;
  color: #6b7280;
}

.method-btn:hover {
  border-color: #6366f1;
  color: #6366f1;
  transform: translateY(-2px);
}

.method-btn.active {
  border-color: #6366f1;
  background: #6366f1;
  color: white;
}

.method-btn .btn-icon {
  font-size: 32px;
}

.scan-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.scanner-container {
  position: relative;
  width: 100%;
  max-width: 400px;
  aspect-ratio: 1;
  background: #1a1a2e;
  border-radius: 12px;
  overflow: hidden;
}

.scanner-container.scanning {
  border: 3px solid #10b981;
}

.scanner-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.scanner-canvas {
  display: none;
}

.scanner-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.7);
  color: white;
}

.scan-icon {
  font-size: 64px;
  margin-bottom: 16px;
}

.scan-text {
  font-size: 16px;
  text-align: center;
}

.scan-line {
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, transparent, #10b981, transparent);
  animation: scan 2s linear infinite;
}

@keyframes scan {
  0% { top: 0%; }
  50% { top: 100%; }
  100% { top: 0%; }
}

.scan-toggle-btn {
  padding: 14px 28px;
  border: none;
  border-radius: 8px;
  background: #6366f1;
  color: white;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.scan-toggle-btn:hover {
  background: #4f46e5;
}

.scan-toggle-btn.scanning {
  background: #ef4444;
}

.code-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.code-input-container {
  display: flex;
  gap: 8px;
}

.code-input {
  flex: 1;
  padding: 16px;
  border: 2px solid #d1d5db;
  border-radius: 8px;
  font-size: 18px;
  font-family: 'Courier New', monospace;
  font-weight: 600;
  letter-spacing: 2px;
  text-transform: uppercase;
  text-align: center;
  transition: border-color 0.3s ease;
}

.code-input:focus {
  outline: none;
  border-color: #6366f1;
}

.code-input:disabled {
  background: #f3f4f6;
  cursor: not-allowed;
}

.paste-btn {
  padding: 16px;
  border: 2px solid #d1d5db;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  font-size: 20px;
  transition: all 0.3s ease;
}

.paste-btn:hover {
  border-color: #6366f1;
  background: #f9fafb;
}

.redeem-btn {
  padding: 16px;
  border: none;
  border-radius: 8px;
  background: #6366f1;
  color: white;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.redeem-btn:hover:not(:disabled) {
  background: #4f46e5;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.redeem-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.result-section {
  margin-top: 32px;
}

.result-card {
  padding: 24px;
  border-radius: 12px;
  display: flex;
  gap: 20px;
  align-items: flex-start;
}

.result-card.success {
  background: #d1fae5;
  border-left: 4px solid #10b981;
}

.result-card.error {
  background: #fee2e2;
  border-left: 4px solid #ef4444;
}

.result-icon {
  font-size: 48px;
  flex-shrink: 0;
}

.result-content {
  flex: 1;
}

.result-title {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 8px;
}

.result-card.success .result-title {
  color: #065f46;
}

.result-card.error .result-title {
  color: #991b1b;
}

.result-message {
  font-size: 16px;
  margin-bottom: 16px;
}

.result-details {
  background: rgba(255, 255, 255, 0.5);
  padding: 16px;
  border-radius: 8px;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.detail-item:last-child {
  border-bottom: none;
}

.detail-label {
  font-weight: 500;
  color: #374151;
}

.detail-value {
  font-weight: 600;
  color: #1a1a2e;
}

.reset-btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  background: white;
  color: #6366f1;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  width: 100%;
}

.reset-btn:hover {
  background: #f9fafb;
}

.info-section {
  margin-top: 32px;
}

.info-card {
  background: #eff6ff;
  border: 1px solid #dbeafe;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  gap: 16px;
  align-items: flex-start;
}

.info-icon {
  font-size: 24px;
  flex-shrink: 0;
}

.info-content h4 {
  font-size: 18px;
  font-weight: 600;
  color: #1e40af;
  margin-bottom: 12px;
}

.info-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.info-list li {
  padding: 8px 0;
  color: #374151;
  font-size: 14px;
  line-height: 1.6;
  position: relative;
  padding-left: 24px;
}

.info-list li::before {
  content: '•';
  position: absolute;
  left: 8px;
  color: #1e40af;
  font-weight: bold;
}
</style>
