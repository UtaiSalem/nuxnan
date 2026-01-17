<template>
  <div class="coupon-list">
    <div class="filters">
      <select v-model="filterType" class="filter-select">
        <option value="all">ทุกประเภท</option>
        <option value="points">คูปองแต้ม</option>
        <option value="wallet">คูปองเงิน</option>
      </select>
      
      <select v-model="filterStatus" class="filter-select">
        <option value="all">ทุกสถานะ</option>
        <option value="active">ใช้งานได้</option>
        <option value="redeemed">ใช้แล้ว</option>
        <option value="expired">หมดอายุ</option>
        <option value="cancelled">ยกเลิก</option>
      </select>
    </div>

    <div class="stats-bar" v-if="statistics">
      <div class="stat-item">
        <span class="stat-value">{{ statistics.total_coupons }}</span>
        <span class="stat-label">คูปองทั้งหมด</span>
      </div>
      <div class="stat-item">
        <span class="stat-value">{{ statistics.active_coupons }}</span>
        <span class="stat-label">ใช้งานได้</span>
      </div>
      <div class="stat-item">
        <span class="stat-value">{{ statistics.redeemed_coupons }}</span>
        <span class="stat-label">ใช้แล้ว</span>
      </div>
      <div class="stat-item">
        <span class="stat-value">{{ statistics.total_points_in_coupons }}</span>
        <span class="stat-label">แต้มในคูปอง</span>
      </div>
      <div class="stat-item">
        <span class="stat-value">{{ statistics.total_wallet_in_coupons.toFixed(2) }}</span>
        <span class="stat-label">บาทในคูปอง</span>
      </div>
    </div>

    <div class="coupons-grid" v-if="filteredCoupons.length > 0">
      <CouponCard
        v-for="coupon in filteredCoupons"
        :key="coupon.id"
        :coupon="coupon"
        @cancelled="handleCancelled"
        @printed="handlePrinted"
      />
    </div>

    <div class="empty-state" v-else>
      <div class="empty-icon">🎟</div>
      <h3 class="empty-title">ไม่พบคูปอง</h3>
      <p class="empty-message">
        {{ emptyMessage }}
      </p>
      <button @click="resetFilters" class="reset-btn" v-if="hasActiveFilters">
        รีเซ็ตตัวกรอง
      </button>
    </div>

    <div class="loading-state" v-if="isLoading">
      <div class="spinner"></div>
      <p>กำลังโหลด...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useApi } from '~/composables/useApi'
import CouponCard from './CouponCard.vue'

const api = useApi()

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

interface Statistics {
  total_coupons: number
  active_coupons: number
  redeemed_coupons: number
  cancelled_coupons: number
  expired_coupons: number
  points_coupons: number
  wallet_coupons: number
  total_points_in_coupons: number
  total_wallet_in_coupons: number
}

const coupons = ref<Coupon[]>([])
const statistics = ref<Statistics | null>(null)
const isLoading = ref(false)
const filterType = ref<'all' | 'points' | 'wallet'>('all')
const filterStatus = ref<'all' | 'active' | 'redeemed' | 'expired' | 'cancelled'>('all')

const filteredCoupons = computed(() => {
  let result = [...coupons.value]
  
  if (filterType.value !== 'all') {
    result = result.filter(c => c.coupon_type === filterType.value)
  }
  
  if (filterStatus.value !== 'all') {
    result = result.filter(c => c.status === filterStatus.value)
  }
  
  return result
})

const hasActiveFilters = computed(() => {
  return filterType.value !== 'all' || filterStatus.value !== 'all'
})

const emptyMessage = computed(() => {
  if (hasActiveFilters.value) {
    return 'ไม่พบคูปองที่ตรงกับตัวกรอง ลองเปลี่ยนตัวกรองหรือรีเซ็ตตัวกรอง'
  }
  return 'คุณยังไม่ได้สร้างคูปอง เริ่มสร้างคูปองแรกของคุณได้เลย!'
})

onMounted(() => {
  loadCoupons()
})

async function loadCoupons() {
  isLoading.value = true
  
  try {
    const [couponsResponse, statsResponse]: [any, any] = await Promise.all([
      api.get('/api/coupons'),
      api.get('/api/coupons/statistics')
    ])
    
    if (couponsResponse.success) {
      coupons.value = couponsResponse.data.coupons
    }
    
    if (statsResponse.success) {
      statistics.value = statsResponse.data
    }
  } catch (err: any) {
    console.error('Failed to load coupons:', err)
  } finally {
    isLoading.value = false
  }
}

function resetFilters() {
  filterType.value = 'all'
  filterStatus.value = 'all'
}

function handleCancelled(coupon: Coupon) {
  const index = coupons.value.findIndex(c => c.id === coupon.id)
  if (index !== -1) {
    coupons.value[index] = { ...coupon, status: 'cancelled' }
  }
  
  // Reload statistics
  loadStatistics()
}

function handlePrinted(coupon: Coupon) {
  // Just log the print action
  console.log('Coupon printed:', coupon.coupon_code)
}

async function loadStatistics() {
  try {
    const response: any = await api.get('/api/coupons/statistics')
    if (response.success) {
      statistics.value = response.data
    }
  } catch (err: any) {
    console.error('Failed to load statistics:', err)
  }
}
</script>

<style scoped>
.coupon-list {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.filters {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.filter-select {
  flex: 1;
  min-width: 200px;
  padding: 12px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  background: white;
  cursor: pointer;
  transition: border-color 0.3s ease;
}

.filter-select:focus {
  outline: none;
  border-color: #6366f1;
}

.stats-bar {
  display: flex;
  gap: 16px;
  padding: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  flex-wrap: wrap;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px 20px;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  min-width: 120px;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: white;
}

.stat-label {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.9);
  text-align: center;
  margin-top: 4px;
}

.coupons-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
}

@media (max-width: 480px) {
  .coupons-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: #f9fafb;
  border-radius: 12px;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 16px;
}

.empty-title {
  font-size: 24px;
  font-weight: 600;
  color: #1a1a2e;
  margin-bottom: 8px;
}

.empty-message {
  font-size: 16px;
  color: #6b7280;
  margin-bottom: 24px;
}

.reset-btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  background: #6366f1;
  color: white;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.reset-btn:hover {
  background: #4f46e5;
}

.loading-state {
  text-align: center;
  padding: 60px 20px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e5e7eb;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-state p {
  color: #6b7280;
  font-size: 16px;
}

@media (max-width: 768px) {
  .stats-bar {
    flex-direction: column;
  }
  
  .stat-item {
    width: 100%;
  }
}
</style>
