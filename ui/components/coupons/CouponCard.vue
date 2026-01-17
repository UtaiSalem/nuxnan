<template>
  <div class="coupon-card" :class="statusClass">
    <!-- Header -->
    <div class="coupon-header">
      <div class="type-badge">
        <span class="icon">{{ coupon.coupon_type === 'points' ? '🎯' : '💰' }}</span>
        <span class="type-label">{{ coupon.type_label }}</span>
      </div>
      <span class="status-badge" :class="coupon.status">
        {{ coupon.status_label }}
      </span>
    </div>

    <!-- Compact Body with side-by-side layout -->
    <div class="coupon-body">
      <div class="body-main">
        <!-- Left: QR Code -->
        <div class="qr-section" v-if="coupon.qr_code_path">
          <img 
            :src="qrCodeUrl" 
            alt="QR Code" 
            class="qr-image"
          />
        </div>
        
        <!-- Right: Details -->
        <div class="details-col">
          <div class="amount-section">
            <span class="amount">{{ formatAmount }}</span>
            <span class="unit">{{ coupon.coupon_type === 'points' ? 'แต้ม' : 'บาท' }}</span>
          </div>
          
          <div class="code-section">
            <div class="code-container">
              <code class="coupon-code">{{ coupon.coupon_code }}</code>
              <button 
                @click="copyCode" 
                class="copy-btn"
                :class="{ copied: copied }"
                :title="copied ? 'คัดลอกแล้ว' : 'คัดลอกรหัส'"
              >
                <span class="btn-text-full">{{ copied ? '✓ สำเร็จ' : 'คัดลอก' }}</span>
                <span class="btn-text-short">{{ copied ? '✓' : 'คัด' }}</span>
              </button>
            </div>
          </div>
          
          <div class="dates-compact">
            <span v-if="coupon.expires_at" class="date-item">
              <span class="date-label">หมดอายุ:</span> {{ formatDateShort(coupon.expires_at) }}
            </span>
            <span v-else class="date-item no-expiry">ไม่มีวันหมดอายุ</span>
          </div>
        </div>
      </div>
      
      <!-- Description if exists -->
      <div class="description-section" v-if="coupon.description">
        <p class="description">{{ coupon.description }}</p>
      </div>
    </div>

    <!-- Footer -->
    <div class="coupon-footer">
      <button 
        @click="printCoupon" 
        class="action-btn print-btn"
        :disabled="!coupon.qr_code_path"
      >
        <span class="btn-icon">🖨️</span>
        <span>พิมพ์</span>
      </button>
      <button 
        @click="cancelCoupon" 
        class="action-btn cancel-btn"
        v-if="canCancel"
      >
        <span class="btn-icon">❌</span>
        <span>ยกเลิก</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useApi } from '~/composables/useApi'
import { useToast } from '~/composables/useToast'

interface Coupon {
  id: number
  coupon_code: string
  coupon_type: 'points' | 'wallet'
  amount: number
  status: 'active' | 'redeemed' | 'expired' | 'cancelled'
  status_label: string
  type_label: string
  is_expired: boolean
  description?: string
  qr_code_path?: string
  created_at: string
  expires_at?: string
  redeemed_at?: string
}

const props = defineProps<{
  coupon: Coupon
}>()

const emit = defineEmits(['cancelled', 'printed'])

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string
const api = useApi()
const toast = useToast()

const copied = ref(false)

const statusClass = computed(() => {
  return {
    'status-active': props.coupon.status === 'active',
    'status-redeemed': props.coupon.status === 'redeemed',
    'status-expired': props.coupon.status === 'expired',
    'status-cancelled': props.coupon.status === 'cancelled',
  }
})

const canCancel = computed(() => {
  return props.coupon.status === 'active' && !props.coupon.is_expired
})

const formatAmount = computed(() => {
  return props.coupon.coupon_type === 'wallet'
    ? props.coupon.amount.toFixed(2)
    : Math.floor(props.coupon.amount).toString()
})

const qrCodeUrl = computed(() => {
  return props.coupon.qr_code_path ? `${apiBase}/storage/${props.coupon.qr_code_path}` : ''
})

function formatDate(dateString: string): string {
  const date = new Date(dateString)
  return date.toLocaleDateString('th-TH', {
    day: '2-digit',
    month: 'short',
    year: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatDateShort(dateString: string): string {
  const date = new Date(dateString)
  return date.toLocaleDateString('th-TH', {
    day: '2-digit',
    month: 'short',
    year: '2-digit',
  })
}

async function copyCode() {
  try {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(props.coupon.coupon_code)
    } else {
      // Fallback for non-secure contexts or older browsers
      const textArea = document.createElement('textarea')
      textArea.value = props.coupon.coupon_code
      textArea.style.position = 'fixed'
      textArea.style.left = '-9999px'
      textArea.style.top = '0'
      document.body.appendChild(textArea)
      textArea.focus()
      textArea.select()
      try {
        document.execCommand('copy')
      } catch (err) {
        throw new Error('Fallback copy failed')
      }
      document.body.removeChild(textArea)
    }
    
    copied.value = true
    toast.success('คัดลอกรหัสคูปองแล้ว')
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    toast.error('ไม่สามารถคัดลอกได้')
    console.error('Clipboard error:', err)
  }
}

function printCoupon() {
  if (!props.coupon.qr_code_path) return
  
  const printWindow = window.open('', '_blank')
  if (printWindow) {
    printWindow.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
        <title>คูปอง ${props.coupon.coupon_code}</title>
        <style>
          body { font-family: 'Sarabun', sans-serif; padding: 20px; text-align: center; }
          .coupon { border: 3px solid #6366f1; border-radius: 16px; padding: 30px; max-width: 400px; margin: 0 auto; }
          .qr-code { width: 200px; height: 200px; margin: 20px auto; }
          .code { font-size: 24px; font-weight: bold; letter-spacing: 2px; margin: 20px 0; }
          .amount { font-size: 36px; font-weight: bold; color: #6366f1; margin: 20px 0; }
          .type { font-size: 18px; color: #666; margin-bottom: 10px; }
        </style>
      </head>
      <body>
        <div class="coupon">
          <div class="type">${props.coupon.type_label}</div>
          <div class="amount">${formatAmount.value} ${props.coupon.coupon_type === 'points' ? 'แต้ม' : 'บาท'}</div>
          <img src="${qrCodeUrl.value}" class="qr-code" />
          <div class="code">${props.coupon.coupon_code}</div>
        </div>
      </body>
      </html>
    `)
    printWindow.document.close()
    printWindow.print()
    emit('printed', props.coupon)
  }
}

async function cancelCoupon() {
  if (!confirm('คุณแน่ใจหรือไม่ที่จะยกเลิกคูปองนี้?')) return

  try {
    const response: any = await api.post(`/api/coupons/${props.coupon.id}/cancel`, {})
    
    if (response.success) {
      toast.success('ยกเลิกคูปองสำเร็จ')
      emit('cancelled', props.coupon)
    } else {
      toast.error(response.message || 'ไม่สามารถยกเลิกคูปองได้')
    }
  } catch (err: any) {
    toast.error(err.message || 'เกิดข้อผิดพลาด')
  }
}
</script>

<style scoped>
.coupon-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid #f3f4f6;
  position: relative;
}

.coupon-card:hover {
  transform: translateY(-4px) scale(1.01);
  box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
}

.coupon-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
  position: relative;
}

.type-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  color: white;
  font-weight: 600;
}

.type-badge .icon {
  font-size: 16px;
}

.type-badge .type-label {
  font-size: 13px;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.status-badge.active {
  background: #10b981;
  color: white;
}

.status-badge.redeemed {
  background: #3b82f6;
  color: white;
}

.status-badge.expired {
  background: #ef4444;
  color: white;
}

.status-badge.cancelled {
  background: #6b7280;
  color: white;
}

.coupon-body {
  padding: 12px 14px;
}

.body-main {
  display: flex;
  gap: 14px;
  align-items: flex-start;
}

.qr-section {
  flex-shrink: 0;
  display: flex;
  justify-content: center;
  align-items: center;
}

.qr-image {
  width: 100px;
  height: 100px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 4px;
}

.details-col {
  flex: 1;
  min-width: 0;
}

.amount-section {
  margin-bottom: 8px;
}

.amount-section .amount {
  font-size: 32px;
  font-weight: 800;
  color: #1a1a2e;
  line-height: 1;
}

.amount-section .unit {
  font-size: 14px;
  color: #6b7280;
  margin-left: 4px;
}

.code-section {
  margin-bottom: 8px;
}

.code-container {
  display: flex;
  gap: 8px;
  align-items: stretch;
}

.coupon-code {
  flex: 1;
  padding: 10px 14px;
  background: #f8fafc;
  border-radius: 12px;
  font-family: 'JetBrains Mono', 'Monaco', 'Courier New', monospace;
  font-size: 15px;
  font-weight: 700;
  letter-spacing: 1.5px;
  color: #1a1a2e;
  border: 1px solid #e5e7eb;
  text-align: center;
}

.copy-btn {
  padding: 0 16px;
  border: none;
  border-radius: 12px;
  background: #6366f1;
  color: white;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  white-space: nowrap;
  display: flex;
  align-items: center;
  justify-content: center;
}

.copy-btn:hover {
  background: #4f46e5;
  transform: scale(1.02);
}

.copy-btn:active {
  transform: scale(0.95);
}

.copy-btn.copied {
  background: #10b981;
}

.btn-text-short {
  display: none;
}

.dates-compact {
  font-size: 11px;
  color: #6b7280;
}

.dates-compact .date-label {
  color: #9ca3af;
}

.dates-compact .no-expiry {
  color: #10b981;
}

.description-section {
  margin-top: 10px;
  padding: 8px 10px;
  background: #f9fafb;
  border-radius: 6px;
}

.description-section .description {
  font-size: 12px;
  color: #6b7280;
  line-height: 1.4;
  margin: 0;
}

.coupon-footer {
  display: flex;
  gap: 8px;
  padding: 10px 14px;
  background: #f9fafb;
  border-top: 1px solid #e5e7eb;
}

.action-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 8px;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.print-btn {
  background: #6366f1;
  color: white;
}

.print-btn:hover:not(:disabled) {
  background: #4f46e5;
}

.cancel-btn {
  background: #ef4444;
  color: white;
}

.cancel-btn:hover {
  background: #dc2626;
}

.btn-icon {
  font-size: 14px;
}

/* Status-specific styles */
.coupon-card.status-active {
  border-left: 3px solid #10b981;
}

.coupon-card.status-redeemed {
  border-left: 3px solid #3b82f6;
  opacity: 0.85;
}

.coupon-card.status-expired {
  border-left: 3px solid #ef4444;
  opacity: 0.7;
}

.coupon-card.status-cancelled {
  border-left: 3px solid #6b7280;
  opacity: 0.7;
}

/* Responsive - adjustment on mobile */
@media (max-width: 480px) {
  .coupon-header {
    padding: 8px 12px;
  }

  .body-main {
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 16px;
  }
  
  .qr-image {
    width: 120px;
    height: 120px;
  }
  
  .details-col {
    width: 100%;
  }

  .amount-section .amount {
    font-size: 36px;
  }

  .code-container {
    flex-direction: row; /* Keep code and copy side-by-side if possible */
  }

  .btn-text-full {
    display: none;
  }

  .btn-text-short {
    display: inline;
    font-size: 16px;
  }

  .copy-btn {
    padding: 0 20px;
  }

  .action-btn {
    padding: 10px 6px;
  }
}

@media (max-width: 360px) {
  .amount-section .amount {
    font-size: 28px;
  }
  
  .code-container {
    flex-direction: column;
  }
  
  .copy-btn {
    width: 100%;
  }
}
</style>
