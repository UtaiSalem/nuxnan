<template>
  <div class="points-conversion-modal">
    <div class="modal-overlay" @click="$emit('close')">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>แลกแต้มเป็นเงิน</h3>
          <button class="close-btn" @click="$emit('close')">
            <i class="lni lni-close"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <!-- Exchange Rate Info -->
          <div class="exchange-rate-info">
            <div class="rate-card">
              <div class="rate-icon">
                <i class="lni lni-exchange"></i>
              </div>
              <div class="rate-details">
                <p class="rate-title">อัตราแลกเปลี่ยน</p>
                <p class="rate-value"><strong>1,080 แต้ม = 1 บาท</strong></p>
              </div>
            </div>
          </div>
          
          <!-- Current Balance -->
          <div class="balance-info">
            <div class="balance-item">
              <span class="balance-label">แต้มที่มี:</span>
              <span class="balance-value">{{ formatPoints(points) }} แต้ม</span>
            </div>
            <div class="balance-item">
              <span class="balance-label">แต้มที่สามารถแลก:</span>
              <span class="balance-value">{{ formatPoints(availablePoints) }} แต้ม</span>
            </div>
          </div>
          
          <!-- Conversion Form -->
          <div class="conversion-form">
            <label for="points-amount">จำนวนแต้มที่ต้องการแลก:</label>
            <div class="amount-input-group">
              <input 
                type="number" 
                id="points-amount"
                v-model.number="pointsAmount"
                :min="minPoints"
                :max="availablePoints"
                :step="1080"
                class="amount-input"
                placeholder="ระบุจำนวนแต้ม (เป็นจำนวนที่เป็นจำนวนของ 1,080)"
              >
              <span class="input-suffix">แต้ม</span>
            </div>
            
            <!-- Quick Amount Buttons -->
            <div class="quick-amounts">
              <button 
                v-for="amount in quickAmounts"
                :key="amount"
                class="btn-quick-amount"
                @click="pointsAmount = amount"
                :disabled="amount > availablePoints"
              >
                {{ formatPoints(amount) }}
              </button>
            </div>
          </div>
          
          <!-- Conversion Preview -->
          <div class="conversion-preview" v-if="pointsAmount > 0">
            <div class="preview-card">
              <div class="preview-icon">
                <i class="lni lni-money"></i>
              </div>
              <div class="preview-details">
                <p class="preview-title">จะได้รับเงิน:</p>
                <p class="preview-amount">{{ formatMoney(walletAmount) }}</p>
                <p class="preview-note" v-if="pointsAmount < minPoints">
                  <i class="lni lni-warning"></i>
                  จำนวนแต้มต้องเป็นจำนวนของ 1,080 หรือมากกว่า
                </p>
              </div>
            </div>
          </div>
          
          <!-- Terms -->
          <div class="terms-info">
            <p class="terms-text">
              <i class="lni lni-info"></i>
              การแลกแต้มเป็นเงินไม่สามารถยกเลิก และแต้มที่แลกไปจะถูกหักออกจากยอดแต้มของคุณทันที
            </p>
          </div>
        </div>
        
        <div class="modal-footer">
          <button 
            class="btn btn-secondary"
            @click="$emit('close')"
          >
            ยกเลิก
          </button>
          <button 
            class="btn btn-primary"
            @click="convertPoints"
            :disabled="!canConvert || isConverting"
          >
            <span v-if="isConverting">
              <i class="lni lni-spinner lni-spin"></i>
              กำลังแลกแต้ม...
            </span>
            <span v-else>
              แลกแต้มเป็นเงิน
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePoints } from '~/composables/usePoints'
import { useWallet } from '~/composables/useWallet'

const { points, earn, convertToWallet, formatPoints } = usePoints()
const { formatMoney } = useWallet()

// Props
interface Props {
  points: number
  availablePoints: number
}

const props = withDefaults(defineProps<Props>(), {
  points: 0,
  availablePoints: 0,
})

// Emits
const emit = defineEmits<{
  close: []
  converted: [walletAmount: number]
}>()

// Reactive state
const pointsAmount = ref(0)
const isConverting = ref(false)

// Configuration
const exchangeRate = 1080 // 1 THB = 1080 points
const minPoints = 1080 // Minimum conversion amount

const quickAmounts = computed(() => {
  const amounts: number[] = []
  const maxAmount = Math.min(props.availablePoints, 50000)
  
  // Add amounts in increments of 1080
  for (let i = minPoints; i <= maxAmount; i += exchangeRate) {
    amounts.push(i)
  }
  
  return amounts
})

// Computed
const walletAmount = computed(() => {
  return pointsAmount.value / exchangeRate
})

const canConvert = computed(() => {
  return pointsAmount.value >= minPoints && 
         pointsAmount.value <= props.availablePoints &&
         pointsAmount.value % exchangeRate === 0
})

// Methods
const convertPoints = async () => {
  if (!canConvert.value) return
  
  try {
    isConverting.value = true
    await convertToWallet(pointsAmount.value)
    
    // Emit converted event
    emit('converted', walletAmount.value)
    
    // Close modal
    emit('close')
    
    // Show success notification
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'success',
        title: 'แลกแต้มสำเร็จ!',
        html: `
          <div style="text-align: center;">
            <p style="font-size: 24px; margin: 0 0 16px 0;">${formatPoints(pointsAmount.value)} แต้ม</p>
            <p style="font-size: 18px; color: #6b7280; margin: 0;">แลกเป็นเงิน</p>
            <p style="font-size: 28px; font-weight: bold; color: #10b981; margin: 16px 0 0;">${formatMoney(walletAmount.value)}</p>
          </div>
        `,
        confirmButtonText: 'รับทราบ',
        confirmButtonColor: '#10b981',
      })
    }
  } catch (error: any) {
    isConverting.value = false
    
    // Show error notification
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'error',
        title: 'แลกแต้มไม่สำเร็จ',
        text: error.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่',
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '#ef4444',
      })
    }
  }
}
</script>

<style scoped>
.points-conversion-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-overlay {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-content {
  background: white;
  border-radius: 16px;
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #1f2937;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #6b7280;
  padding: 4px;
  transition: color 0.2s;
}

.close-btn:hover {
  color: #4b5563;
}

.modal-body {
  padding: 24px;
}

.exchange-rate-info {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 24px;
  color: white;
}

.rate-card {
  display: flex;
  align-items: center;
  gap: 16px;
}

.rate-icon {
  width: 60px;
  height: 60px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
}

.rate-details {
  flex: 1;
}

.rate-title {
  font-size: 14px;
  margin: 0 0 8px 0;
  opacity: 0.9;
}

.rate-value {
  font-size: 24px;
  font-weight: 700;
  margin: 0;
}

.balance-info {
  background: #f9fafb;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 24px;
}

.balance-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
}

.balance-item:last-child {
  padding-bottom: 0;
}

.balance-label {
  font-size: 14px;
  color: #6b7280;
}

.balance-value {
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.conversion-form {
  margin-bottom: 24px;
}

.conversion-form label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 8px;
  color: #374151;
}

.amount-input-group {
  display: flex;
  align-items: center;
  margin-bottom: 16px;
}

.amount-input {
  flex: 1;
  padding: 12px 16px;
  font-size: 16px;
  border: 2px solid #d1d5db;
  border-radius: 8px;
  transition: border-color 0.2s;
}

.amount-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.input-suffix {
  color: #6b7280;
  font-size: 14px;
  margin-left: 8px;
}

.quick-amounts {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
}

.btn-quick-amount {
  padding: 8px 16px;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-quick-amount:hover:not(:disabled) {
  background: #f3f4f6;
  border-color: #667eea;
  color: #667eea;
}

.btn-quick-amount:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.conversion-preview {
  background: #ecfdf5;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 24px;
}

.preview-card {
  display: flex;
  align-items: center;
  gap: 16px;
}

.preview-icon {
  width: 50px;
  height: 50px;
  background: #10b981;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
}

.preview-details {
  flex: 1;
}

.preview-title {
  font-size: 14px;
  margin: 0 0 8px 0;
  color: #6b7280;
}

.preview-amount {
  font-size: 32px;
  font-weight: 700;
  margin: 0 0 8px 0;
  color: #10b981;
}

.preview-note {
  font-size: 13px;
  color: #f59e0b;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 6px;
}

.terms-info {
  background: #fff7ed;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 24px;
}

.terms-text {
  font-size: 13px;
  color: #92400e;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  line-height: 1.5;
}

.modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding: 20px 24px;
  border-top: 1px solid #e5e7eb;
}

.btn {
  padding: 10px 24px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary {
  background: white;
  border: 1px solid #d1d5db;
  color: #6b7280;
}

.btn-secondary:hover {
  background: #f3f4f6;
  border-color: #9ca3af;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.lni-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
