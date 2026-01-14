<template>
  <div class="points-display">
    <!-- Points Card -->
    <div class="points-card">
      <div class="points-header">
        <div class="points-icon">
          <i class="lni lni-star"></i>
        </div>
        <div class="points-info">
          <h3 class="points-title">แต้มสะสม</h3>
          <p class="points-subtitle">Points</p>
        </div>
      </div>
      
      <div class="points-amount">
        <span class="points-value">{{ formatPoints(points) }}</span>
        <span class="points-label">แต้ม</span>
      </div>
      
      <!-- Level Progress -->
      <div class="level-progress" v-if="user">
        <div class="level-info">
          <span class="level-text">เลเวล {{ user.level || 1 }}</span>
          <span class="level-xp">{{ user.current_xp || 0 }} / {{ user.xp_for_next_level || 100 }} XP</span>
        </div>
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: levelProgress + '%' }"></div>
        </div>
      </div>
      
      <!-- Action Buttons -->
      <div class="points-actions">
        <button 
          class="btn btn-primary btn-sm"
          @click="showConversionModal = true"
        >
          <i class="lni lni-exchange"></i>
          แลกเป็นเงิน
        </button>
        <button 
          class="btn btn-outline-primary btn-sm"
          @click="showHistoryModal = true"
        >
          <i class="lni lni-list"></i>
          ประวัติ
        </button>
      </div>
    </div>
    
    <!-- Conversion Modal -->
    <div v-if="showConversionModal" class="modal-overlay" @click="showConversionModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>แลกแต้มเป็นเงิน</h3>
          <button class="close-btn" @click="showConversionModal = false">
            <i class="lni lni-close"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <div class="conversion-info">
            <p class="exchange-rate">อัตราแลกเปลี่ยน: <strong>1,080 แต้ม = 1 บาท</strong></p>
            <p class="current-balance">แต้มที่มี: <strong>{{ formatPoints(points) }} แต้ม</strong></p>
          </div>
          
          <div class="conversion-form">
            <label for="points-amount">จำนวนแต้มที่ต้องการแลก:</label>
            <input 
              type="number" 
              id="points-amount"
              v-model.number="conversionAmount"
              :max="points"
              :min="1080"
              step="1080"
              class="form-control"
            >
            
            <div class="conversion-preview" v-if="conversionAmount > 0">
              <p>จะได้รับเงิน: <strong class="text-success">{{ formatMoney(conversionAmount / 1080) }}</strong></p>
            </div>
          </div>
          
          <div class="modal-footer">
            <button 
              class="btn btn-secondary"
              @click="showConversionModal = false"
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
                กำลังแลก...
              </span>
              <span v-else>แลกแต้ม</span>
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- History Modal -->
    <div v-if="showHistoryModal" class="modal-overlay" @click="showHistoryModal = false">
      <div class="modal-content modal-lg" @click.stop>
        <div class="modal-header">
          <h3>ประวัติแต้ม</h3>
          <button class="close-btn" @click="showHistoryModal = false">
            <i class="lni lni-close"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <!-- Filters -->
          <div class="transaction-filters">
            <select 
              v-model="filters.type" 
              class="form-control form-control-sm"
              @change="loadTransactions"
            >
              <option value="">ทั้งหมด</option>
              <option value="earn">รับแต้ม</option>
              <option value="spend">ใช้แต้ม</option>
              <option value="refund">คืนแต้ม</option>
              <option value="transfer">โอนแต้ม</option>
              <option value="conversion">แลกแต้ม</option>
            </select>
            
            <select 
              v-model="filters.source_type" 
              class="form-control form-control-sm"
              @change="loadTransactions"
            >
              <option value="">ทุกแหล่งที่มา</option>
              <option value="login">เข้าสู่ระบบ</option>
              <option value="course">คอร์สเรียน</option>
              <option value="lesson">บทเรียน</option>
              <option value="quiz">แบบทดสอบ</option>
              <option value="assignment">การบ้าน</option>
              <option value="post">โพสต์</option>
              <option value="comment">คอมเมนต์</option>
              <option value="like">ถูกใจ</option>
              <option value="share">แชร์</option>
              <option value="referral">แนะนำเพื่อน</option>
            </select>
          </div>
          
          <!-- Transactions List -->
          <div class="transactions-list" v-if="!isLoadingTransactions">
            <div 
              v-for="transaction in transactions" 
              :key="transaction.id"
              class="transaction-item"
            >
              <div class="transaction-icon" :class="transaction.type">
                <i :class="getTransactionIcon(transaction.type)"></i>
              </div>
              
              <div class="transaction-details">
                <h4 class="transaction-title">{{ transaction.description || getTypeLabel(transaction.type) }}</h4>
                <p class="transaction-meta">
                  {{ formatDate(transaction.created_at) }}
                  <span v-if="transaction.source_type"> • {{ getSourceLabel(transaction.source_type) }}</span>
                </p>
              </div>
              
              <div class="transaction-amount" :class="transaction.type">
                <span v-if="transaction.type === 'earn' || transaction.type === 'refund'">+</span>
                <span v-else>-</span>
                {{ formatPoints(transaction.amount) }}
              </div>
            </div>
            
            <!-- Empty State -->
            <div v-if="transactions.length === 0" class="empty-state">
              <i class="lni lni-empty-file"></i>
              <p>ไม่มีประวัติแต้ม</p>
            </div>
          </div>
          
          <!-- Loading State -->
          <div v-else class="loading-state">
            <i class="lni lni-spinner lni-spin"></i>
            <p>กำลังโหลด...</p>
          </div>
          
          <!-- Pagination -->
          <div class="pagination" v-if="pagination.total_pages > 1">
            <button 
              class="btn btn-outline-primary btn-sm"
              @click="loadPage(pagination.current_page - 1)"
              :disabled="pagination.current_page <= 1"
            >
              <i class="lni lni-chevron-left"></i>
            </button>
            
            <span class="page-info">
              หน้า {{ pagination.current_page }} / {{ pagination.total_pages }}
            </span>
            
            <button 
              class="btn btn-outline-primary btn-sm"
              @click="loadPage(pagination.current_page + 1)"
              :disabled="pagination.current_page >= pagination.total_pages"
            >
              <i class="lni lni-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { usePoints } from '~/composables/usePoints'
import { useWallet } from '~/composables/useWallet'

const { 
  points, 
  user, 
  isLoading, 
  earn, 
  spend, 
  convertToWallet, 
  getTransactions,
  formatPoints,
  getLevelProgress,
} = usePoints()

const { formatMoney } = useWallet()

// Modal states
const showConversionModal = ref(false)
const showHistoryModal = ref(false)

// Conversion
const conversionAmount = ref(0)
const isConverting = ref(false)

// Transactions
const transactions = ref<any[]>([])
const isLoadingTransactions = ref(false)
const filters = ref({
  type: '',
  source_type: '',
  date_from: '',
  date_to: '',
})
const pagination = ref({
  current_page: 1,
  total_pages: 1,
  per_page: 20,
  total: 0,
})

// Computed
const canConvert = computed(() => {
  return conversionAmount.value >= 1080 && conversionAmount.value <= points.value
})

const levelProgress = computed(() => {
  return getLevelProgress()
})

// Methods
const convertPoints = async () => {
  if (!canConvert.value) return
  
  try {
    isConverting.value = true
    await convertToWallet(conversionAmount.value)
    
    // Show success notification
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'success',
        title: 'แลกแต้มสำเร็จ!',
        html: `
          <p>แลก ${formatPoints(conversionAmount.value)} แต้ม</p>
          <p>ได้รับเงิน <strong>${formatMoney(conversionAmount.value / 1080)}</strong></p>
        `,
        confirmButtonText: 'รับทราบ',
        confirmButtonColor: '#10b981',
      })
    }
    
    // Reset and close modal
    conversionAmount.value = 0
    showConversionModal.value = false
  } catch (error: any) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'error',
        title: 'แลกแต้มไม่สำเร็จ',
        text: error.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่',
        confirmButtonText: 'ตกลง',
      })
    }
  } finally {
    isConverting.value = false
  }
}

const loadTransactions = async () => {
  try {
    isLoadingTransactions.value = true
    const response = await getTransactions({
      ...filters.value,
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
    })
    
    transactions.value = response.transactions || []
    pagination.value = {
      current_page: response.current_page || 1,
      total_pages: response.total_pages || 1,
      per_page: response.per_page || 20,
      total: response.total || 0,
    }
  } catch (error) {
    console.error('Load transactions error:', error)
  } finally {
    isLoadingTransactions.value = false
  }
}

const loadPage = (page: number) => {
  pagination.value.current_page = page
  loadTransactions()
}

const getTransactionIcon = (type: string): string => {
  const icons: Record<string, string> = {
    earn: 'lni-plus',
    spend: 'lni-minus',
    refund: 'lni-refresh',
    transfer: 'lni-exchange',
    conversion: 'lni-money',
  }
  return icons[type] || 'lni-star'
}

const getTypeLabel = (type: string): string => {
  const labels: Record<string, string> = {
    earn: 'รับแต้ม',
    spend: 'ใช้แต้ม',
    refund: 'คืนแต้ม',
    transfer: 'โอนแต้ม',
    conversion: 'แลกแต้ม',
    admin_adjust: 'ปรับแต้มจากระบบ',
  }
  return labels[type] || type
}

const getSourceLabel = (sourceType: string): string => {
  const labels: Record<string, string> = {
    login: 'เข้าสู่ระบบ',
    course: 'คอร์สเรียน',
    lesson: 'บทเรียน',
    quiz: 'แบบทดสอบ',
    assignment: 'การบ้าน',
    post: 'โพสต์',
    comment: 'คอมเมนต์',
    like: 'ถูกใจ',
    share: 'แชร์',
    referral: 'แนะนำเพื่อน',
  }
  return labels[sourceType] || sourceType
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// Load transactions when history modal opens
const openHistoryModal = () => {
  showHistoryModal.value = true
  loadTransactions()
}

// Lifecycle
onMounted(() => {
  // Load points balance
  getBalance()
})
</script>

<style scoped>
.points-display {
  width: 100%;
}

.points-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  padding: 24px;
  color: white;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.points-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}

.points-icon {
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.points-title {
  font-size: 18px;
  font-weight: 600;
  margin: 0;
}

.points-subtitle {
  font-size: 12px;
  opacity: 0.8;
  margin: 0;
}

.points-amount {
  text-align: center;
  margin-bottom: 24px;
}

.points-value {
  font-size: 48px;
  font-weight: 700;
  display: block;
  line-height: 1;
}

.points-label {
  font-size: 14px;
  opacity: 0.8;
}

.level-progress {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 20px;
}

.level-info {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  margin-bottom: 8px;
}

.level-text {
  font-weight: 600;
}

.level-xp {
  opacity: 0.8;
}

.progress-bar {
  height: 8px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%);
  border-radius: 4px;
  transition: width 0.3s ease;
}

.points-actions {
  display: flex;
  gap: 12px;
}

.points-actions .btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

/* Modal Styles */
.modal-overlay {
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

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-lg {
  max-width: 700px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.close-btn {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  padding: 4px;
}

.modal-body {
  padding: 20px;
}

.conversion-info {
  background: #f9fafb;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 20px;
}

.exchange-rate,
.current-balance {
  margin: 0 0 8px 0;
  font-size: 14px;
}

.conversion-form {
  margin-bottom: 20px;
}

.conversion-form label {
  display: block;
  font-weight: 500;
  margin-bottom: 8px;
  font-size: 14px;
}

.conversion-form input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 16px;
}

.conversion-preview {
  margin-top: 12px;
  padding: 12px;
  background: #ecfdf5;
  border-radius: 6px;
  border-left: 4px solid #10b981;
}

.conversion-preview p {
  margin: 0;
  font-size: 14px;
}

.text-success {
  color: #10b981;
}

.modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.modal-footer .btn {
  padding: 8px 20px;
}

/* Transaction Filters */
.transaction-filters {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.transaction-filters select {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

/* Transactions List */
.transactions-list {
  max-height: 400px;
  overflow-y: auto;
}

.transaction-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-bottom: 1px solid #e5e7eb;
  transition: background-color 0.2s;
}

.transaction-item:hover {
  background: #f9fafb;
}

.transaction-item:last-child {
  border-bottom: none;
}

.transaction-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.transaction-icon.earn {
  background: #ecfdf5;
  color: #10b981;
}

.transaction-icon.spend {
  background: #fef2f2;
  color: #ef4444;
}

.transaction-icon.refund {
  background: #eff6ff;
  color: #3b82f6;
}

.transaction-icon.transfer {
  background: #fff7ed;
  color: #f97316;
}

.transaction-icon.conversion {
  background: #faf5ff;
  color: #a855f7;
}

.transaction-details {
  flex: 1;
  min-width: 0;
}

.transaction-title {
  font-size: 14px;
  font-weight: 500;
  margin: 0 0 4px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.transaction-meta {
  font-size: 12px;
  color: #6b7280;
  margin: 0;
}

.transaction-amount {
  font-size: 16px;
  font-weight: 600;
  flex-shrink: 0;
}

.transaction-amount.earn,
.transaction-amount.refund {
  color: #10b981;
}

.transaction-amount.spend,
.transaction-amount.transfer,
.transaction-amount.conversion {
  color: #ef4444;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #9ca3af;
}

.empty-state i {
  font-size: 48px;
  margin-bottom: 12px;
  display: block;
}

.empty-state p {
  margin: 0;
  font-size: 14px;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 40px 20px;
  color: #6b7280;
}

.loading-state i {
  font-size: 32px;
  margin-bottom: 12px;
  display: block;
}

.loading-state p {
  margin: 0;
  font-size: 14px;
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.page-info {
  font-size: 14px;
  color: #6b7280;
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
