<template>
  <div class="coupon-creation-form">
    <h2 class="form-title">สร้างคูปอง</h2>
    
    <div class="coupon-type-selector">
      <button 
        @click="setType('points')" 
        :class="{ active: type === 'points' }"
        class="type-btn"
      >
        <span class="icon">🎯</span>
        <span>คูปองแต้ม</span>
      </button>
      <button 
        @click="setType('wallet')" 
        :class="{ active: type === 'wallet' }"
        class="type-btn"
      >
        <span class="icon">💰</span>
        <span>คูปองเงิน</span>
      </button>
    </div>

    <form @submit.prevent="createCoupon" class="coupon-form">
      <div class="form-group">
        <label for="amount">
          {{ type === 'points' ? 'จำนวนแต้ม' : 'จำนวนเงิน (บาท)' }}
        </label>
        <input 
          v-model.number="amount" 
          type="number" 
          id="amount"
          :min="minAmount"
          :step="type === 'wallet' ? '0.01' : '1'"
          required 
          class="form-input"
        />
        <span class="unit">{{ type === 'points' ? 'แต้ม' : 'บาท' }}</span>
      </div>

      <div class="form-group">
        <label for="expiresInDays">วันหมดอายุ (วัน) - ไม่บังคับ</label>
        <input 
          v-model.number="expiresInDays" 
          type="number" 
          id="expiresInDays"
          min="1"
          max="365"
          class="form-input"
          placeholder="เช่น 30, 60, 90"
        />
      </div>

      <div class="form-group">
        <label for="description">รายละเอียด (ไม่บังคับ)</label>
        <textarea 
          v-model="description" 
          id="description"
          rows="3"
          class="form-textarea"
          placeholder="ระบุรายละเอียดเพิ่มเติมสำหรับคูปองนี้..."
        ></textarea>
      </div>

      <div class="balance-info">
        <div class="balance-card">
          <span class="label">ยอด{{ type === 'points' ? 'แต้ม' : 'เงิน' }}ที่มี:</span>
          <span class="value">{{ availableBalance }} {{ type === 'points' ? 'แต้ม' : 'บาท' }}</span>
        </div>
        <div class="info-card">
          <span class="info-icon">ℹ️</span>
          <span class="info-text">
            คูปองจะถูกหักจาก{{ type === 'points' ? 'แต้ม' : 'ยอดเงิน' }}ของคุณทันที
            และสามารถใช้งานได้โดยผู้อื่นผ่าน QR Code หรือรหัสคูปอง
          </span>
        </div>
      </div>

      <button 
        type="submit" 
        :disabled="!isValid || isLoading"
        class="submit-btn"
      >
        <span v-if="isLoading" class="spinner"></span>
        <span v-else>สร้างคูปอง</span>
      </button>
    </form>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useApi } from '~/composables/useApi'
import { useToast } from '~/composables/useToast'

const emit = defineEmits(['created', 'cancel'])

const api = useApi()
const toast = useToast()

const type = ref<'points' | 'wallet'>('points')
const amount = ref<number>(0)
const description = ref<string>('')
const expiresInDays = ref<number | null>(null)
const isLoading = ref(false)
const error = ref<string>('')

// Mock balance data - in real app, fetch from API
const pointsBalance = ref<number>(5000)
const walletBalance = ref<number>(100.50)

const minAmount = computed(() => type.value === 'points' ? 1 : 10)

const availableBalance = computed(() => {
  return type.value === 'points' ? pointsBalance.value : walletBalance.value
})

const isValid = computed(() => {
  return amount.value >= minAmount.value && 
         amount.value <= availableBalance.value &&
         (!expiresInDays.value || (expiresInDays.value >= 1 && expiresInDays.value <= 365))
})

function setType(newType: 'points' | 'wallet') {
  type.value = newType
  amount.value = 0
  error.value = ''
}

async function createCoupon() {
  if (!isValid.value || isLoading.value) return

  isLoading.value = true
  error.value = ''

  try {
    const response = await api.post('/api/coupons', {
      type: type.value,
      amount: amount.value,
      description: description.value || undefined,
      expires_in_days: expiresInDays.value || undefined,
    })

    if (response.success) {
      toast.success('สร้างคูปองสำเร็จ!')
      
      // Update balance
      if (type.value === 'points') {
        pointsBalance.value -= amount.value
      } else {
        walletBalance.value -= amount.value
      }

      emit('created', response.data.coupon)
      
      // Reset form
      amount.value = 0
      description.value = ''
      expiresInDays.value = null
    } else {
      error.value = response.message || 'ไม่สามารถสร้างคูปองได้'
      toast.error(response.message || 'ไม่สามารถสร้างคูปองได้')
    }
  } catch (err: any) {
    error.value = err.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่'
    toast.error(error.value)
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.coupon-creation-form {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.form-title {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 24px;
  color: #1a1a2e;
}

.coupon-type-selector {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
}

.type-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 16px;
  color: #666;
}

.type-btn:hover {
  border-color: #6366f1;
  color: #6366f1;
}

.type-btn.active {
  border-color: #6366f1;
  background: #6366f1;
  color: white;
}

.type-btn .icon {
  font-size: 24px;
}

.coupon-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-weight: 500;
  color: #374151;
  font-size: 14px;
}

.form-input,
.form-textarea {
  padding: 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.3s ease;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #6366f1;
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.unit {
  font-size: 14px;
  color: #9ca3af;
}

.balance-info {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin: 16px 0;
}

.balance-card,
.info-card {
  padding: 16px;
  border-radius: 8px;
  background: #f9fafb;
  display: flex;
  align-items: center;
  gap: 12px;
}

.balance-card .label {
  font-size: 14px;
  color: #6b7280;
}

.balance-card .value {
  font-size: 18px;
  font-weight: 600;
  color: #1a1a2e;
}

.info-card .info-icon {
  font-size: 20px;
}

.info-card .info-text {
  flex: 1;
  font-size: 13px;
  color: #6b7280;
  line-height: 1.5;
}

.submit-btn {
  padding: 16px;
  border: none;
  border-radius: 8px;
  background: #6366f1;
  color: white;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.submit-btn:hover:not(:disabled) {
  background: #4f46e5;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.submit-btn:disabled {
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

.error-message {
  padding: 16px;
  background: #fee2e2;
  color: #991b1b;
  border-radius: 8px;
  margin-top: 16px;
  font-size: 14px;
}
</style>
