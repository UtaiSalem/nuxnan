<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CouponCard from '~/components/coupons/CouponCard.vue'
import jsPDF from 'jspdf'
import QRCode from 'qrcode'
import QrcodeVue3 from 'qrcode-vue3'

definePageMeta({
  layout: 'main',
  middleware: 'auth'
})

const api = useApi()
const toast = useToast()
const authStore = useAuthStore()

// State
const coupons = ref<any[]>([])
const statistics = ref<any>(null)
const isLoading = ref(true)
const isDownloading = ref(false)
const activeFilter = ref<'all' | 'active' | 'redeemed' | 'expired' | 'cancelled'>('all')
const activeType = ref<'all' | 'points' | 'wallet'>('all')
const activeView = ref<'card' | 'table'>('card')
const showRedeemModal = ref(false)
const redeemCode = ref('')
const isRedeeming = ref(false)
const showQrModal = ref(false)
const selectedCouponForQr = ref<any>(null)

// Fetch coupons
const fetchCoupons = async () => {
  isLoading.value = true
  try {
    const params: any = {}
    if (activeFilter.value !== 'all') params.status = activeFilter.value
    if (activeType.value !== 'all') params.type = activeType.value
    
    const response = await api.get('/api/coupons', { params })
    if (response.success) {
      coupons.value = response.data.coupons
    }
  } catch (error) {
    console.error('Error fetching coupons:', error)
    toast.error('ไม่สามารถโหลดคูปองได้')
  } finally {
    isLoading.value = false
  }
}

// Fetch statistics
const fetchStatistics = async () => {
  try {
    const response = await api.get('/api/coupons/statistics')
    if (response.success) {
      statistics.value = {
        total: response.data.total_coupons || 0,
        active: response.data.active_coupons || 0,
        total_points: response.data.total_points_in_coupons || 0,
        total_wallet: response.data.total_wallet_in_coupons || 0,
      }
    }
  } catch (error) {
    console.error('Error fetching statistics:', error)
  }
}

// Handle coupon cancelled
const handleCouponCancelled = (couponId: number) => {
  const index = coupons.value.findIndex(c => c.id === couponId)
  if (index !== -1) {
    coupons.value[index].status = 'cancelled'
  }
  fetchStatistics()
}

// Show QR Code modal
const showQrCode = (coupon: any) => {
  selectedCouponForQr.value = coupon
  showQrModal.value = true
}

// Cancel coupon (for table view)
const cancelCoupon = async (coupon: any) => {
  if (!confirm('คุณต้องการยกเลิกคูปองนี้ใช่หรือไม่?')) return
  
  try {
    const response = await api.post(`/api/coupons/${coupon.id}/cancel`)
    if (response.success) {
      toast.success('ยกเลิกคูปองสำเร็จ!')
      handleCouponCancelled(coupon.id)
    } else {
      toast.error(response.message || 'ไม่สามารถยกเลิกคูปองได้')
    }
  } catch (error) {
    console.error('Error canceling coupon:', error)
    toast.error('ไม่สามารถยกเลิกคูปองได้')
  }
}

// Copy coupon code to clipboard
const copyCouponCode = async (code: string) => {
  try {
    await navigator.clipboard.writeText(code)
    toast.success('คัดลอกรหัสคูปองแล้ว!')
  } catch (error) {
    console.error('Error copying to clipboard:', error)
    toast.error('ไม่สามารถคัดลอกได้')
  }
}

// Redeem coupon code to receive points
const redeemCoupon = async () => {
  if (!redeemCode.value.trim()) {
    toast.error('กรุณากรอกรหัสคูปอง')
    return
  }
  
  isRedeeming.value = true
  try {
    const response = await api.post('/api/coupons/redeem', { code: redeemCode.value.trim() })
    if (response.success) {
      toast.success('แลกคูปองสำเร็จ! ได้รับแต้มแล้ว')
      showRedeemModal.value = false
      redeemCode.value = ''
      // Refresh coupons and statistics
      fetchCoupons()
      fetchStatistics()
    } else {
      toast.error(response.message || 'ไม่สามารถแลกคูปองได้')
    }
  } catch (error) {
    console.error('Error redeeming coupon:', error)
    toast.error('ไม่สามารถแลกคูปองได้')
  } finally {
    isRedeeming.value = false
  }
}

// Watch filters
watch([activeFilter, activeType], () => {
  fetchCoupons()
})

// Initialize
onMounted(() => {
  fetchCoupons()
  fetchStatistics()
})

// Format number
const formatNumber = (num: number): string => {
  if (num >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K'
  return num.toLocaleString()
}

// Helper function to load image as base64
const loadImageAsBase64 = async (url: string): Promise<string> => {
  return new Promise((resolve, reject) => {
    const img = new Image()
    img.crossOrigin = 'anonymous'
    img.onload = () => {
      const canvas = document.createElement('canvas')
      canvas.width = img.width
      canvas.height = img.height
      const ctx = canvas.getContext('2d')
      if (ctx) {
        ctx.drawImage(img, 0, 0)
        resolve(canvas.toDataURL('image/png'))
      } else {
        reject(new Error('Could not get canvas context'))
      }
    }
    img.onerror = () => reject(new Error('Could not load image'))
    img.src = url
  })
}

// Helper function to convert SVG to base64 PNG
const svgToBase64Png = async (svgUrl: string, couponCode?: string): Promise<string> => {
  return new Promise(async (resolve, reject) => {
    try {
      // Add timestamp to prevent caching issues
      const cleanUrl = svgUrl.split('?')[0] + '?t=' + new Date().getTime()
      
      console.log('Fetching SVG for PDF:', cleanUrl)
      
      const response = await fetch(cleanUrl)
      if (!response.ok) {
        throw new Error(`Failed to fetch SVG: ${response.status} ${response.statusText}`)
      }
      
      let svgText = await response.text()
      
      // Basic validation
      if (!svgText.includes('<svg') || !svgText.includes('</svg>')) {
        throw new Error('Content is not a valid SVG')
      }

      // Inject dimensions if missing to ensure canvas rendering works
      if (!svgText.includes('width=') || !svgText.includes('height=')) {
        const parser = new DOMParser()
        const doc = parser.parseFromString(svgText, 'image/svg+xml')
        const svgElement = doc.querySelector('svg')
        if (svgElement) {
          if (!svgElement.hasAttribute('width')) svgElement.setAttribute('width', '300')
          if (!svgElement.hasAttribute('height')) svgElement.setAttribute('height', '300')
          // Ensure viewbox exists for scaling
          if (!svgElement.hasAttribute('viewBox')) {
            svgElement.setAttribute('viewBox', '0 0 300 300') // Assumption based on generation
          }
          svgText = new XMLSerializer().serializeToString(svgElement)
        }
      }
      
      const img = new Image()
      img.crossOrigin = 'anonymous' // Important for CORS if applicable
      
      const svgBlob = new Blob([svgText], { type: 'image/svg+xml;charset=utf-8' })
      const url = URL.createObjectURL(svgBlob)
      
      img.onload = () => {
        const canvas = document.createElement('canvas')
        canvas.width = 300 // Higher resolution for print
        canvas.height = 300
        const ctx = canvas.getContext('2d')
        if (ctx) {
          // Fill white background (transparent SVGs might look bad in PDF)
          ctx.fillStyle = '#FFFFFF'
          ctx.fillRect(0, 0, canvas.width, canvas.height)
          ctx.drawImage(img, 0, 0, 300, 300)
          URL.revokeObjectURL(url)
          resolve(canvas.toDataURL('image/png'))
        } else {
          reject(new Error('Could not get canvas context'))
        }
      }
      
      img.onerror = (e) => {
        console.error('Image load error, trying fallback:', e)
        URL.revokeObjectURL(url)
        // Fallback to local generation if image load fails
        generateLocalQr(couponCode).then(resolve).catch(reject)
      }
      
      img.src = url
    } catch (error) {
      console.error('SVG conversion error, trying fallback:', error)
      if (couponCode) {
         generateLocalQr(couponCode).then(resolve).catch((err) => {
            console.error('Fallback generation failed:', err)
            resolve('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=')
         })
      } else {
         // Return a transparent 1x1 pixel as fallback to prevent PDF crash
         resolve('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=')
      }
    }
  })
}

// Fallback QR generator using qrcode package
const generateLocalQr = async (text?: string): Promise<string> => {
  if (!text) throw new Error('No text for QR generation')
  try {
     return await QRCode.toDataURL(text, { width: 300, margin: 2 })
  } catch (e) {
     console.error('QRCode library not found', e)
     throw e
  }
}

// Download PDF with coupon cards
const downloadPDF = async () => {
  if (isDownloading.value || coupons.value.length === 0) return
  
  isDownloading.value = true
  try {
    const doc = new jsPDF()
    const apiBaseUrl = useRuntimeConfig().public.apiBase || ''
    const now = new Date()
    
    // Set font (using default font)
    doc.setFont('helvetica')
    
    // Filter active coupons
    const filteredCoupons = coupons.value.filter(coupon => {
      if (activeFilter.value !== 'all' && coupon.status !== activeFilter.value) return false
      if (activeType.value !== 'all' && coupon.coupon_type !== activeType.value) return false
      return true
    })
    
    // Card dimensions (5 cards per row, 10 rows per page = 50 cards per page)
    // Optimized for 50 cards with print-safe margins
    const cardWidth = 36
    const cardHeight = 26
    const marginX = 10  // Print-safe margin
    const marginY = 10  // Print-safe margin
    const gapX = 2
    const gapY = 1
    const cardsPerRow = 5
    const rowsPerPage = 10
    const cardsPerPage = cardsPerRow * rowsPerPage // 50 cards per page
    
    let cardIndex = 0
    
    for (const coupon of filteredCoupons) {
      // Calculate position
      const pageIndex = Math.floor(cardIndex / cardsPerPage)
      const positionOnPage = cardIndex % cardsPerPage
      const row = Math.floor(positionOnPage / cardsPerRow)
      const col = positionOnPage % cardsPerRow
      
      // Add new page if needed
      if (cardIndex > 0 && positionOnPage === 0) {
        doc.addPage()
      }
      
      const x = marginX + col * (cardWidth + gapX)
      const y = marginY + row * (cardHeight + gapY)
      
      // Draw card border
      doc.setDrawColor(160, 160, 160)
      doc.setLineWidth(0.2)
      doc.rect(x, y, cardWidth, cardHeight, 'S')
      
      // Content positioned with minimal padding
      const verticalOffset = 2 // Minimal top padding
      
      // QR Code section (left side)
      const qrSize = 16
      const qrX = x + 2
      const qrY = y + verticalOffset
      
      // Try to load QR code
      if (coupon.qr_code_path) {
        try {
          const qrUrl = `${apiBaseUrl}/storage/${coupon.qr_code_path}`
          // Pass coupon code for fallback generation if fetch fails
          // Format based on backend: COUPON:code
          const qrData = `COUPON:${coupon.coupon_code}`
          const qrBase64 = await svgToBase64Png(qrUrl, qrData)
          doc.addImage(qrBase64, 'PNG', qrX, qrY, qrSize, qrSize)
        } catch (error) {
          // Draw placeholder if QR fails to load
          doc.setDrawColor(180, 180, 180)
          doc.setFillColor(250, 250, 250)
          doc.rect(qrX, qrY, qrSize, qrSize, 'FD')
          doc.setFontSize(4)
          doc.setTextColor(150, 150, 150)
          doc.text('QR', qrX + qrSize/2, qrY + qrSize/2 + 1, { align: 'center' })
        }
      } else {
        // Draw placeholder
        doc.setDrawColor(180, 180, 180)
        doc.setFillColor(250, 250, 250)
        doc.rect(qrX, qrY, qrSize, qrSize, 'FD')
        doc.setFontSize(4)
        doc.setTextColor(150, 150, 150)
        doc.text('N/A', qrX + qrSize/2, qrY + qrSize/2 + 1, { align: 'center' })
      }
      
      // Coupon details (right side) - centered vertically
      const detailX = x + 20
      const detailY = y + verticalOffset + 3
      
      // Amount
      doc.setFontSize(7)
      doc.setTextColor(88, 28, 135)
      const amountText = coupon.coupon_type === 'wallet' 
        ? `฿${coupon.amount.toLocaleString()}` 
        : `${coupon.amount.toLocaleString()}`
      doc.text(amountText, detailX, detailY)
      
      // Coupon code
      doc.setFontSize(6)
      doc.setTextColor(30, 30, 30)
      doc.text(coupon.coupon_code, detailX, detailY + 5)
      
      // Expiry date
      doc.setFontSize(4)
      doc.setTextColor(100, 100, 100)
      const expiryText = coupon.expires_at 
        ? new Date(coupon.expires_at).toLocaleDateString('en-US', { month: 'numeric', day: 'numeric' })
        : '-'
      doc.text(expiryText, detailX, detailY + 9)
      
      // Type indicator (small text)
      doc.setFontSize(4)
      doc.setTextColor(120, 120, 120)
      doc.text(coupon.coupon_type === 'points' ? 'PTS' : 'THB', detailX + 16, detailY, { align: 'right' })
      
      cardIndex++
    }
    
    // Save PDF
    const fileName = `coupons_${now.getTime()}.pdf`
    doc.save(fileName)
    
    toast.success('ดาวน์โหลด PDF สำเร็จ!')
  } catch (error) {
    console.error('PDF download error:', error)
    toast.error('ไม่สามารถดาวน์โหลด PDF ได้')
  } finally {
    isDownloading.value = false
  }
}
</script>

<template>
  <div class="container mx-auto px-4 py-6 max-w-6xl">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center shadow-lg">
            <Icon icon="fluent:ticket-diagonal-24-filled" class="w-6 h-6 text-white" />
          </div>
          คูปองของฉัน
        </h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">จัดการคูปองแต้มและเงินของคุณ</p>
      </div>
      
      <div class="flex flex-wrap gap-2 md:gap-3">
        <button
          @click="showRedeemModal = true"
          class="p-2.5 md:px-4 md:py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-white rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all flex items-center gap-2 font-bold"
        >
          <Icon icon="fluent:qr-code-24-regular" class="w-5 h-5" />
          <span class="hidden md:inline">แลกคูปอง</span>
        </button>
        <button
          @click="downloadPDF"
          :disabled="isDownloading || coupons.length === 0"
          :class="[
            'p-2.5 md:px-4 md:py-2.5 bg-emerald-500 text-white rounded-xl hover:bg-emerald-600 transition-all flex items-center gap-2 font-bold',
            (isDownloading || coupons.length === 0) ? 'opacity-50 cursor-not-allowed' : ''
          ]"
        >
          <Icon v-if="isDownloading" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
          <Icon v-else icon="fluent:document-pdf-24-regular" class="w-5 h-5" />
          <span class="hidden md:inline">{{ isDownloading ? 'กำลังดาวน์โหลด...' : 'ดาวน์โหลด PDF' }}</span>
        </button>
        <NuxtLink 
          to="/earn/coupons/create"
          class="p-2.5 md:px-4 md:py-2.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-xl hover:opacity-90 transition-all flex items-center gap-2 font-bold shadow-lg"
        >
          <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
          <span class="hidden md:inline">สร้างคูปอง</span>
        </NuxtLink>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-4 mb-6" v-if="statistics">
      <div class="vikinger-card !p-3 sm:!p-4 flex items-center gap-2 sm:gap-3">
        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-md flex-shrink-0">
          <Icon icon="fluent:ticket-diagonal-24-filled" class="w-5 h-5 sm:w-6 sm:h-6 text-white" />
        </div>
        <div class="min-w-0">
          <p class="text-lg sm:text-2xl font-black text-gray-900 dark:text-white truncate">{{ statistics.total || 0 }}</p>
          <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 truncate">คูปองทั้งหมด</p>
        </div>
      </div>
      
      <div class="vikinger-card !p-3 sm:!p-4 flex items-center gap-2 sm:gap-3">
        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center shadow-md flex-shrink-0">
          <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 sm:w-6 sm:h-6 text-white" />
        </div>
        <div class="min-w-0">
          <p class="text-lg sm:text-2xl font-black text-gray-900 dark:text-white truncate">{{ statistics.active || 0 }}</p>
          <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 truncate">ใช้งานได้</p>
        </div>
      </div>
      
      <div class="vikinger-card !p-3 sm:!p-4 flex items-center gap-2 sm:gap-3">
        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-md flex-shrink-0">
          <Icon icon="fluent:star-24-filled" class="w-5 h-5 sm:w-6 sm:h-6 text-white" />
        </div>
        <div class="min-w-0">
          <p class="text-lg sm:text-2xl font-black text-gray-900 dark:text-white truncate">{{ formatNumber(statistics.total_points || 0) }}</p>
          <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 truncate">แต้มทั้งหมด</p>
        </div>
      </div>
      
      <div class="vikinger-card !p-3 sm:!p-4 flex items-center gap-2 sm:gap-3">
        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center shadow-md flex-shrink-0">
          <Icon icon="fluent:money-24-filled" class="w-5 h-5 sm:w-6 sm:h-6 text-white" />
        </div>
        <div class="min-w-0">
          <p class="text-lg sm:text-2xl font-black text-gray-900 dark:text-white truncate">฿{{ formatNumber(statistics.total_wallet || 0) }}</p>
          <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 truncate">เงินทั้งหมด</p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="vikinger-card !p-3 sm:!p-4 mb-6">
      <div class="flex flex-col gap-3 sm:gap-4">
        <!-- Status Filter -->
        <div>
          <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 mb-2 font-semibold uppercase tracking-wider">สถานะ</p>
          <div class="flex gap-1.5 sm:gap-2 overflow-x-auto pb-1 -mx-1 px-1 scrollbar-hide">
            <button
              v-for="status in [
                { key: 'all', label: 'ทั้งหมด', icon: 'fluent:apps-24-regular' },
                { key: 'active', label: 'ใช้งานได้', icon: 'fluent:checkmark-circle-24-regular' },
                { key: 'redeemed', label: 'ใช้แล้ว', icon: 'fluent:gift-24-regular' },
                { key: 'expired', label: 'หมดอายุ', icon: 'fluent:clock-24-regular' },
                { key: 'cancelled', label: 'ยกเลิก', icon: 'fluent:dismiss-circle-24-regular' },
              ]"
              :key="status.key"
              @click="activeFilter = status.key as any"
              :class="[
                'px-2.5 py-1.5 sm:px-3 rounded-lg text-[10px] sm:text-xs font-bold flex items-center gap-1 sm:gap-1.5 transition-all whitespace-nowrap flex-shrink-0',
                activeFilter === status.key
                  ? 'bg-vikinger-purple text-white shadow-md'
                  : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
              ]"
            >
              <Icon :icon="status.icon" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
              {{ status.label }}
            </button>
          </div>
        </div>
        
        <!-- Type Filter & View Toggle Row -->
        <div class="flex flex-wrap items-end gap-3 sm:gap-4">
          <!-- Type Filter -->
          <div class="flex-1 min-w-[120px]">
            <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 mb-2 font-semibold uppercase tracking-wider">ประเภท</p>
            <div class="flex gap-1.5 sm:gap-2">
              <button
                v-for="type in [
                  { key: 'all', label: 'ทั้งหมด' },
                  { key: 'points', label: '🎯 แต้ม' },
                  { key: 'wallet', label: '💰 เงิน' },
                ]"
                :key="type.key"
                @click="activeType = type.key as any"
                :class="[
                  'px-2.5 py-1.5 sm:px-3 rounded-lg text-[10px] sm:text-xs font-bold transition-all whitespace-nowrap',
                  activeType === type.key
                    ? 'bg-vikinger-cyan text-white shadow-md'
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
                ]"
              >
                {{ type.label }}
              </button>
            </div>
          </div>

          <!-- View Toggle -->
          <div>
            <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 mb-2 font-semibold uppercase tracking-wider">มุมมอง</p>
            <div class="flex gap-1.5 sm:gap-2">
              <button
                @click="activeView = 'card'"
                :class="[
                  'p-1.5 sm:px-3 sm:py-1.5 rounded-lg text-[10px] sm:text-xs font-bold flex items-center gap-1 sm:gap-1.5 transition-all',
                  activeView === 'card'
                    ? 'bg-vikinger-purple text-white shadow-md'
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
                ]"
              >
                <Icon icon="fluent:grid-24-regular" class="w-4 h-4" />
                <span class="hidden sm:inline">การ์ด</span>
              </button>
              <button
                @click="activeView = 'table'"
                :class="[
                  'p-1.5 sm:px-3 sm:py-1.5 rounded-lg text-[10px] sm:text-xs font-bold flex items-center gap-1 sm:gap-1.5 transition-all',
                  activeView === 'table'
                    ? 'bg-vikinger-purple text-white shadow-md'
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'
                ]"
              >
                <Icon icon="fluent:table-24-regular" class="w-4 h-4" />
                <span class="hidden sm:inline">ตาราง</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="i" class="vikinger-card animate-pulse">
        <div class="h-40 bg-gray-200 dark:bg-gray-700 rounded-xl mb-4"></div>
        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2"></div>
        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
      </div>
    </div>

    <!-- Coupons Grid (Card View) -->
    <div v-else-if="coupons.length > 0 && activeView === 'card'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <CouponCard
        v-for="coupon in coupons"
        :key="coupon.id"
        :coupon="coupon"
        @cancelled="handleCouponCancelled"
      />
    </div>

    <!-- Coupons Table (Table View) -->
    <div v-else-if="coupons.length > 0 && activeView === 'table'" class="vikinger-card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700">
              <th class="text-center px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-16">ลำดับ</th>
              <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">รหัสคูปอง</th>
              <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ประเภท</th>
              <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">จำนวน</th>
              <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
              <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">วันหมดอายุ</th>
              <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">QR Code</th>
              <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ดำเนินการ</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="(coupon, index) in coupons" 
              :key="coupon.id"
              class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
            >
              <td class="px-4 py-3 text-center">
                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ index + 1 }}</span>
              </td>
              <td class="px-4 py-3">
                <span class="font-mono text-sm font-bold text-gray-900 dark:text-white">{{ coupon.coupon_code }}</span>
              </td>
              <td class="px-4 py-3">
                <span 
                  :class="[
                    'inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold',
                    coupon.coupon_type === 'points' 
                      ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' 
                      : 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400'
                  ]"
                >
                  <Icon :icon="coupon.coupon_type === 'points' ? 'fluent:star-24-filled' : 'fluent:money-24-filled'" class="w-3.5 h-3.5" />
                  {{ coupon.coupon_type === 'points' ? 'แต้ม' : 'เงิน' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span class="text-sm font-bold text-gray-900 dark:text-white">
                  {{ coupon.coupon_type === 'wallet' ? '฿' : '' }}{{ coupon.amount.toLocaleString() }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span 
                  :class="[
                    'inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-bold',
                    coupon.status === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' :
                    coupon.status === 'redeemed' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' :
                    coupon.status === 'expired' ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' :
                    coupon.status === 'cancelled' ? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400' :
                    'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400'
                  ]"
                >
                  <Icon 
                    :icon="coupon.status === 'active' ? 'fluent:checkmark-circle-24-filled' :
                           coupon.status === 'redeemed' ? 'fluent:gift-24-filled' :
                           coupon.status === 'expired' ? 'fluent:clock-24-filled' :
                           coupon.status === 'cancelled' ? 'fluent:dismiss-circle-24-filled' : 'fluent:question-circle-24-filled'" 
                    class="w-3.5 h-3.5" 
                  />
                  {{ coupon.status === 'active' ? 'ใช้งานได้' :
                     coupon.status === 'redeemed' ? 'ใช้แล้ว' :
                     coupon.status === 'expired' ? 'หมดอายุ' :
                     coupon.status === 'cancelled' ? 'ยกเลิก' : coupon.status }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                  {{ coupon.expires_at ? new Date(coupon.expires_at).toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' }) : '-' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <button 
                  v-if="coupon.qr_code_path"
                  @click="showQrCode(coupon)"
                  class="text-vikinger-purple hover:text-vikinger-cyan transition-colors"
                  title="แสดง QR Code"
                >
                  <Icon icon="fluent:qr-code-24-regular" class="w-5 h-5" />
                </button>
                <span v-else class="text-gray-400 dark:text-gray-600">
                  <Icon icon="fluent:qr-code-24-regular" class="w-5 h-5" />
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex gap-2">
                  <button 
                    v-if="coupon.status === 'active'"
                    @click="cancelCoupon(coupon)"
                    class="text-red-500 hover:text-red-600 transition-colors"
                    title="ยกเลิกคูปอง"
                  >
                    <Icon icon="fluent:dismiss-circle-24-regular" class="w-5 h-5" />
                  </button>
                  <button 
                    @click="copyCouponCode(coupon.coupon_code)"
                    class="text-gray-500 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300 transition-colors"
                    title="คัดลอกรหัส"
                  >
                    <Icon icon="fluent:copy-24-regular" class="w-5 h-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="vikinger-card !py-16 text-center">
      <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
        <Icon icon="fluent:ticket-diagonal-24-regular" class="w-10 h-10 text-gray-400 dark:text-gray-500" />
      </div>
      <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300 mb-2">ไม่มีคูปอง</h3>
      <p class="text-gray-500 dark:text-gray-400 mb-6">คุณยังไม่มีคูปองในขณะนี้</p>
      <NuxtLink 
        to="/earn/coupons/create"
        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-xl font-bold shadow-lg hover:opacity-90 transition-all"
      >
        <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
        สร้างคูปองใหม่
      </NuxtLink>
    </div>

    <!-- Redeem Coupon Modal -->
    <div v-if="showRedeemModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-3 sm:p-4" @click.self="showRedeemModal = false">
      <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-6 max-w-md w-full shadow-2xl max-h-[90vh] overflow-y-auto" @click.stop>
        <div class="flex justify-between items-center mb-4 sm:mb-6">
          <h3 class="text-base sm:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center shadow-md flex-shrink-0">
              <Icon icon="fluent:qr-code-24-filled" class="w-4 h-4 sm:w-5 sm:h-5 text-white" />
            </div>
            แลกคูปองรับแต้ม
          </h3>
          <button @click="showRedeemModal = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors p-1">
            <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 sm:w-6 sm:h-6" />
          </button>
        </div>
        
        <div class="space-y-3 sm:space-y-4">
          <div>
            <label class="block text-xs sm:text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">รหัสคูปอง</label>
            <div class="flex flex-col sm:flex-row gap-2">
              <input
                v-model="redeemCode"
                type="text"
                placeholder="กรอกรหัสคูปองที่ต้องการแลก"
                class="flex-1 px-3 sm:px-4 py-2.5 sm:py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white font-mono text-sm focus:outline-none focus:ring-2 focus:ring-vikinger-purple focus:border-transparent transition-all"
                @keyup.enter="redeemCoupon"
              />
              <button
                @click="redeemCoupon"
                :disabled="isRedeeming || !redeemCode.trim()"
                :class="[
                  'px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-bold transition-all flex items-center justify-center gap-2 w-full sm:w-auto',
                  (isRedeeming || !redeemCode.trim()) 
                    ? 'bg-gray-300 dark:bg-gray-700 text-gray-500 cursor-not-allowed' 
                    : 'bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white hover:opacity-90 shadow-lg'
                ]"
              >
                <Icon v-if="isRedeeming" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
                <Icon v-else icon="fluent:checkmark-24-regular" class="w-5 h-5" />
                <span class="text-sm">{{ isRedeeming ? 'กำลังแลก...' : 'แลกคูปอง' }}</span>
              </button>
            </div>
          </div>
          
          <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400">
            <Icon icon="fluent:info-24-regular" class="w-3.5 h-3.5 sm:w-4 sm:h-4 inline mr-1" />
            ป้อนรหัสคูปองที่ต้องการแลกเพื่อรับแต้ม ระบบจะตรวจสอบและเพิ่มแต้มให้โดยอัตโนมี
          </p>
        </div>
      </div>
    </div>

    <!-- QR Code Modal -->
    <div v-if="showQrModal && selectedCouponForQr" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-3 sm:p-4" @click.self="showQrModal = false">
      <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-6 max-w-[280px] sm:max-w-sm w-full shadow-2xl" @click.stop>
        <div class="flex justify-between items-center mb-3 sm:mb-4">
          <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center">
              <Icon icon="fluent:qr-code-24-filled" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" />
            </div>
            QR Code
          </h3>
          <button @click="showQrModal = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors p-1">
            <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 sm:w-6 sm:h-6" />
          </button>
        </div>
        <div class="flex flex-col items-center">
          <div class="p-3 sm:p-4 bg-white rounded-xl shadow-inner mb-3 sm:mb-4">
            <QrcodeVue3
              :value="selectedCouponForQr.coupon_code"
              :width="160"
              :height="160"
              class="sm:!w-[200px] sm:!h-[200px]"
              :qr-options="{ errorCorrectionLevel: 'H' }"
              :dots-options="{
                type: 'rounded',
                color: '#581c87'
              }"
              :corners-square-options="{
                type: 'extra-rounded',
                color: '#0891b2'
              }"
              :corners-dot-options="{
                type: 'dot',
                color: '#581c87'
              }"
              :background-options="{
                color: '#ffffff'
              }"
              image-options="{ hideBackgroundDots: true, imageSize: 0.4, margin: 0 }"
            />
          </div>
          <p class="text-xs sm:text-sm font-mono font-bold text-gray-900 dark:text-white mb-1 sm:mb-2">{{ selectedCouponForQr.coupon_code }}</p>
          <p class="text-base sm:text-lg font-bold text-vikinger-purple dark:text-vikinger-cyan">
            {{ selectedCouponForQr.coupon_type === 'wallet' ? '฿' : '' }}{{ selectedCouponForQr.amount?.toLocaleString() }}
            <span class="text-[10px] sm:text-xs text-gray-500">{{ selectedCouponForQr.coupon_type === 'points' ? 'แต้ม' : 'บาท' }}</span>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
