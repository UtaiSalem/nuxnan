<template>
  <div class="wallet-dashboard">
    <!-- Stats Overview -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">
          <Icon name="fluent:wallet-24-filled" />
        </div>
        <div class="stat-content">
          <h3 class="stat-value">{{ formatMoney(stats?.total_balance || 0) }}</h3>
          <p class="stat-label">ยอดเงินรวมทั้งหมด</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon deposit">
          <Icon name="fluent:add-circle-24-filled" />
        </div>
        <div class="stat-content">
          <h3 class="stat-value">{{ formatMoney(stats?.today?.deposits || 0) }}</h3>
          <p class="stat-label">ฝากวันนี้</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon withdraw">
          <Icon name="fluent:subtract-circle-24-filled" />
        </div>
        <div class="stat-content">
          <h3 class="stat-value">{{ formatMoney(stats?.today?.withdrawals || 0) }}</h3>
          <p class="stat-label">ถอนวันนี้</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon pending">
          <Icon name="fluent:clock-24-filled" />
        </div>
        <div class="stat-content">
          <h3 class="stat-value">{{ stats?.pending_withdrawals?.count || 0 }}</h3>
          <p class="stat-label">รออนุมัติถอน</p>
        </div>
      </div>
    </div>

    <!-- Pending Withdrawals -->
    <div class="section-card">
      <h2>คำขอถอนเงินที่รออนุมัติ</h2>
      <div v-if="pendingWithdrawals.length > 0" class="withdrawals-list">
        <div
          v-for="withdrawal in pendingWithdrawals"
          :key="withdrawal.id"
          class="withdrawal-item"
        >
          <img :src="withdrawal.user?.avatar" :alt="withdrawal.user?.name" />
          <div class="withdrawal-info">
            <h4>{{ withdrawal.user?.name }}</h4>
            <p>{{ withdrawal.user?.email }}</p>
            <p class="amount">{{ formatMoney(withdrawal.amount) }}</p>
            <p class="date">{{ formatDate(withdrawal.created_at) }}</p>
          </div>
          <div class="withdrawal-actions">
            <button
              @click="approveWithdrawal(withdrawal.id)"
              class="btn-approve"
            >
              <Icon name="fluent:checkmark-circle-24-filled" />
              อนุมัติ
            </button>
            <button
              @click="showRejectModal(withdrawal.id)"
              class="btn-reject"
            >
              <Icon name="fluent:dismiss-circle-24-filled" />
              ปฏิเสธ
            </button>
          </div>
        </div>
      </div>
      <div v-else class="empty-state">
        <Icon name="fluent:checkmark-circle-24-regular" />
        <p>ไม่มีคำขอถอนเงินที่รออนุมัติ</p>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="section-card">
      <h2>การจัดการด่วน</h2>
      <div class="quick-actions">
        <button @click="showAdjustWalletModal = true" class="action-btn">
          <Icon name="fluent:edit-24-regular" />
          ปรับยอดเงินผู้ใช้
        </button>
        <button @click="showAnalyticsModal = true" class="action-btn">
          <Icon name="fluent:chart-multiple-24-regular" />
          ดูรายงานวิเคราะห์
        </button>
        <button @click="exportTransactions" class="action-btn">
          <Icon name="fluent:download-24-regular" />
          ส่งออกข้อมูล
        </button>
      </div>
    </div>

    <!-- Adjust Wallet Modal -->
    <div v-if="showAdjustWalletModal" class="modal-overlay" @click="showAdjustWalletModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>ปรับยอดเงินผู้ใช้</h2>
          <button @click="showAdjustWalletModal = false" class="btn-close">
            <Icon name="fluent:dismiss-24-regular" />
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>ค้นหาผู้ใช้:</label>
            <input
              v-model="searchQuery"
              @input="searchUsers"
              type="text"
              placeholder="พิมพ์ชื่อผู้ใช้หรืออีเมล"
            />
            <div v-if="searchResults.length > 0" class="search-results">
              <div
                v-for="user in searchResults"
                :key="user.id"
                @click="selectUser(user)"
                class="search-result-item"
              >
                <img :src="user.avatar" :alt="user.name" />
                <span>{{ user.name }}</span>
                <span>{{ user.email }}</span>
              </div>
            </div>
          </div>

          <div v-if="selectedUser" class="user-info">
            <img :src="selectedUser.avatar" :alt="selectedUser.name" />
            <div class="user-details">
              <h3>{{ selectedUser.name }}</h3>
              <p>{{ selectedUser.email }}</p>
              <p>ยอดเงินปัจจุบัน: {{ formatMoney(selectedUser.wallet) }}</p>
            </div>
          </div>

          <div v-if="selectedUser" class="form-group">
            <label>ประเภทการปรับ:</label>
            <div class="radio-group">
              <label class="radio-option">
                <input v-model="adjustmentType" type="radio" value="add" />
                <span>เพิ่มเงิน</span>
              </label>
              <label class="radio-option">
                <input v-model="adjustmentType" type="radio" value="deduct" />
                <span>หักเงิน</span>
              </label>
              <label class="radio-option">
                <input v-model="adjustmentType" type="radio" value="set" />
                <span>ตั้งค่าเงิน</span>
              </label>
            </div>
          </div>

          <div v-if="selectedUser" class="form-group">
            <label>จำนวนเงิน:</label>
            <input v-model.number="adjustmentAmount" type="number" min="0" step="0.01" />
          </div>

          <div v-if="selectedUser" class="form-group">
            <label>เหตุผล:</label>
            <textarea v-model="adjustmentReason" placeholder="ระบุเหตุผลการปรับยอดเงิน"></textarea>
          </div>

          <div v-if="selectedUser && adjustmentAmount > 0" class="preview">
            <h4>ตัวอย่างผลลัพธ์:</h4>
            <div class="preview-item">
              <span>ยอดเงินปัจจุบัน:</span>
              <span>{{ formatMoney(selectedUser.wallet) }}</span>
            </div>
            <div class="preview-item" v-if="adjustmentType === 'add'">
              <span>เพิ่ม:</span>
              <span>+{{ formatMoney(adjustmentAmount) }}</span>
            </div>
            <div class="preview-item" v-if="adjustmentType === 'deduct'">
              <span>หัก:</span>
              <span>-{{ formatMoney(adjustmentAmount) }}</span>
            </div>
            <div class="preview-item" v-if="adjustmentType === 'set'">
              <span>ตั้งค่า:</span>
              <span>{{ formatMoney(adjustmentAmount) }}</span>
            </div>
            <div class="preview-item total">
              <span>ยอดเงินใหม่:</span>
              <span>{{ formatMoney(newBalance) }}</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button
            @click="submitAdjustment"
            class="btn-submit"
            :disabled="!canSubmit"
          >
            ยืนยันการปรับ
          </button>
        </div>
      </div>
    </div>

    <!-- Reject Withdrawal Modal -->
    <div v-if="showRejectWithdrawalModal" class="modal-overlay" @click="showRejectWithdrawalModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>ปฏิเสธคำขอถอนเงิน</h2>
          <button @click="showRejectWithdrawalModal = false" class="btn-close">
            <Icon name="fluent:dismiss-24-regular" />
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>เหตุผลการปฏิเสธ:</label>
            <textarea
              v-model="rejectionReason"
              placeholder="ระบุเหตุผลที่ชัดเจน"
              rows="4"
            ></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button
            @click="confirmRejection"
            class="btn-reject-confirm"
            :disabled="!rejectionReason"
          >
            ยืนยันการปฏิเสธ
          </button>
        </div>
      </div>
    </div>

    <!-- Analytics Modal -->
    <div v-if="showAnalyticsModal" class="modal-overlay" @click="showAnalyticsModal = false">
      <div class="modal-content large" @click.stop>
        <div class="modal-header">
          <h2>รายงานวิเคราะห์ Wallet</h2>
          <button @click="showAnalyticsModal = false" class="btn-close">
            <Icon name="fluent:dismiss-24-regular" />
          </button>
        </div>
        <div class="modal-body">
          <div class="date-range">
            <input
              v-model="analyticsFilters.start_date"
              type="date"
              @change="loadAnalytics"
            />
            <span>ถึง</span>
            <input
              v-model="analyticsFilters.end_date"
              type="date"
              @change="loadAnalytics"
            />
          </div>

          <div v-if="analytics" class="analytics-content">
            <div class="analytics-section">
              <h3>แนวโน้มเงินรายวัน</h3>
              <div class="trend-chart">
                <div
                  v-for="day in analytics.daily_trend"
                  :key="day.date"
                  class="trend-item"
                >
                  <span class="date">{{ formatDate(day.date) }}</span>
                  <span class="deposited positive">+{{ formatMoney(day.deposited) }}</span>
                  <span class="withdrawn negative">-{{ formatMoney(day.withdrawn) }}</span>
                </div>
              </div>
            </div>

            <div class="analytics-section">
              <h3>ประเภทธุรกรรม</h3>
              <div class="transaction-types">
                <div
                  v-for="type in analytics.transaction_types"
                  :key="type.transaction_type"
                  class="type-item"
                >
                  <span class="type">{{ type.transaction_type }}</span>
                  <span class="count">{{ type.count }} ครั้ง</span>
                  <span class="amount">{{ formatMoney(type.total_amount) }}</span>
                </div>
              </div>
            </div>

            <div class="analytics-section">
              <h3>ผู้ใช้ที่ฝากเงินสูงสุด</h3>
              <div class="top-users">
                <div
                  v-for="(user, index) in analytics.top_depositors"
                  :key="user.id"
                  class="top-user-item"
                >
                  <span class="rank">#{{ index + 1 }}</span>
                  <img :src="user.avatar" :alt="user.name" />
                  <span class="name">{{ user.name }}</span>
                  <span class="balance">{{ formatMoney(user.wallet) }}</span>
                </div>
              </div>
            </div>

            <div class="analytics-section">
              <h3>ผู้ใช้ที่ถอนเงินสูงสุด</h3>
              <div class="top-users">
                <div
                  v-for="(user, index) in analytics.top_withdrawers"
                  :key="user.user_id"
                  class="top-user-item"
                >
                  <span class="rank">#{{ index + 1 }}</span>
                  <img :src="user.user?.avatar" :alt="user.user?.name" />
                  <span class="name">{{ user.user?.name }}</span>
                  <span class="balance">{{ formatMoney(user.total_withdrawn) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAdminWallet } from '~/composables/useAdminWallet'

const { getStats, getPendingWithdrawals, approveWithdrawal, rejectWithdrawal, adjustWallet, getAnalytics, formatMoney } = useAdminWallet()

const stats = ref<any>(null)
const pendingWithdrawals = ref<any[]>([])
const showAdjustWalletModal = ref(false)
const showRejectWithdrawalModal = ref(false)
const showAnalyticsModal = ref(false)
const searchQuery = ref('')
const searchResults = ref<any[]>([])
const selectedUser = ref<any>(null)
const adjustmentType = ref('add')
const adjustmentAmount = ref(0)
const adjustmentReason = ref('')
const rejectionReason = ref('')
const analytics = ref<any>(null)
const analyticsFilters = ref({
  start_date: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
})
const rejectingWithdrawalId = ref<number | null>(null)

const newBalance = computed(() => {
  if (!selectedUser.value) return 0
  switch (adjustmentType.value) {
    case 'add':
      return selectedUser.value.wallet + adjustmentAmount.value
    case 'deduct':
      return Math.max(0, selectedUser.value.wallet - adjustmentAmount.value)
    case 'set':
      return adjustmentAmount.value
    default:
      return selectedUser.value.wallet
  }
})

const canSubmit = computed(() => {
  return selectedUser.value && adjustmentAmount.value > 0 && adjustmentReason.value.length > 0
})

onMounted(async () => {
  await loadStats()
  await loadPendingWithdrawals()
})

const loadStats = async () => {
  const { data } = await getStats()
  if (data) {
    stats.value = data
  }
}

const loadPendingWithdrawals = async () => {
  const { data } = await getPendingWithdrawals()
  if (data) {
    pendingWithdrawals.value = data.data || []
  }
}

const searchUsers = async () => {
  // Implement user search logic
  // This would typically call an API endpoint
}

const selectUser = (user: any) => {
  selectedUser.value = user
  searchQuery.value = ''
  searchResults.value = []
}

const submitAdjustment = async () => {
  if (!selectedUser.value || !canSubmit.value) return

  const { data, error } = await adjustWallet(selectedUser.value.id, {
    action: adjustmentType.value,
    amount: adjustmentAmount.value,
    reason: adjustmentReason.value,
  })

  if (data) {
    alert('ปรับยอดเงินสำเร็จ!')
    showAdjustWalletModal.value = false
    resetForm()
    await loadStats()
  } else {
    alert('เกิดข้อผิดพลาด: ' + error)
  }
}

const showRejectModal = (withdrawalId: number) => {
  rejectingWithdrawalId.value = withdrawalId
  showRejectWithdrawalModal.value = true
}

const confirmRejection = async () => {
  if (!rejectionReason.value || !rejectingWithdrawalId.value) return

  const { data, error } = await rejectWithdrawal(rejectingWithdrawalId.value, rejectionReason.value)

  if (data) {
    alert('ปฏิเสธคำขอถอนเงินสำเร็จ!')
    showRejectWithdrawalModal.value = false
    rejectionReason.value = ''
    rejectingWithdrawalId.value = null
    await loadPendingWithdrawals()
    await loadStats()
  } else {
    alert('เกิดข้อผิดพลาด: ' + error)
  }
}

const loadAnalytics = async () => {
  const { data } = await getAnalytics(analyticsFilters.value)
  if (data) {
    analytics.value = data
  }
}

const exportTransactions = () => {
  // Implement export logic
  alert('ฟีเจอร์นี้ยังไม่ได้ถูกนำไปใช้งาน')
}

const resetForm = () => {
  selectedUser.value = null
  adjustmentType.value = 'add'
  adjustmentAmount.value = 0
  adjustmentReason.value = ''
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
</script>

<style scoped>
.wallet-dashboard {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 50px;
  height: 50px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
}

.stat-icon.deposit {
  background: rgba(76, 175, 80, 0.2);
}

.stat-icon.withdraw {
  background: rgba(244, 67, 54, 0.2);
}

.stat-icon.pending {
  background: rgba(255, 193, 7, 0.2);
}

.stat-content h3 {
  margin: 0 0 5px 0;
  font-size: 28px;
  font-weight: bold;
  color: white;
}

.stat-content p {
  margin: 0;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.9);
}

.section-card {
  background: white;
  border-radius: 12px;
  padding: 25px;
  margin-bottom: 20px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.section-card h2 {
  margin: 0 0 20px 0;
  font-size: 20px;
  color: #333;
}

.withdrawals-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.withdrawal-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px;
  background: #f5f5f5;
  border-radius: 8px;
}

.withdrawal-item img {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  object-fit: cover;
}

.withdrawal-info {
  flex: 1;
}

.withdrawal-info h4 {
  margin: 0 0 5px 0;
  font-size: 16px;
  color: #333;
}

.withdrawal-info p {
  margin: 0 0 3px 0;
  font-size: 14px;
  color: #666;
}

.withdrawal-info .amount {
  font-weight: bold;
  color: #667eea;
  font-size: 18px;
}

.withdrawal-actions {
  display: flex;
  gap: 10px;
}

.btn-approve,
.btn-reject {
  padding: 10px 15px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 5px;
}

.btn-approve {
  background: #4caf50;
  color: white;
}

.btn-reject {
  background: #f44336;
  color: white;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #999;
}

.empty-state svg {
  font-size: 48px;
  margin-bottom: 10px;
}

.quick-actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
}

.action-btn {
  padding: 15px 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: transform 0.2s;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
}

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
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-content.large {
  max-width: 900px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e0e0e0;
}

.modal-header h2 {
  margin: 0;
  font-size: 24px;
  color: #333;
}

.btn-close {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 24px;
  color: #666;
}

.modal-body {
  padding: 20px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #333;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

.search-results {
  margin-top: 10px;
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #ddd;
  border-radius: 6px;
}

.search-result-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  border-bottom: 1px solid #eee;
  cursor: pointer;
}

.search-result-item:hover {
  background: #f5f5f5;
}

.search-result-item img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.user-info {
  display: flex;
  gap: 15px;
  padding: 15px;
  background: #f5f5f5;
  border-radius: 8px;
  margin-bottom: 20px;
}

.user-info img {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
}

.user-details h3 {
  margin: 0 0 5px 0;
  font-size: 18px;
  color: #333;
}

.user-details p {
  margin: 0 0 5px 0;
  font-size: 14px;
  color: #666;
}

.radio-group {
  display: flex;
  gap: 20px;
}

.radio-option {
  display: flex;
  align-items: center;
  gap: 5px;
  cursor: pointer;
}

.preview {
  background: #f5f5f5;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.preview h4 {
  margin: 0 0 10px 0;
  font-size: 16px;
  color: #333;
}

.preview-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #e0e0e0;
}

.preview-item.total {
  font-weight: bold;
  color: #667eea;
  border-bottom: none;
}

.modal-footer {
  padding: 20px;
  border-top: 1px solid #e0e0e0;
  display: flex;
  justify-content: flex-end;
}

.btn-submit {
  padding: 12px 30px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 500;
}

.btn-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-reject-confirm {
  padding: 12px 30px;
  background: #f44336;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 500;
}

.btn-reject-confirm:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.date-range {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 20px;
}

.date-range input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
}

.analytics-section {
  margin-bottom: 30px;
}

.analytics-section h3 {
  margin: 0 0 15px 0;
  font-size: 18px;
  color: #333;
}

.trend-chart,
.transaction-types,
.top-users {
  max-height: 300px;
  overflow-y: auto;
}

.trend-item,
.type-item {
  display: flex;
  justify-content: space-between;
  padding: 10px;
  border-bottom: 1px solid #e0e0e0;
}

.trend-item .date {
  font-weight: 500;
  color: #333;
}

.trend-item .deposited {
  color: #4caf50;
}

.trend-item .withdrawn {
  color: #f44336;
}

.type-item .type {
  font-weight: 500;
  color: #333;
}

.type-item .count {
  color: #666;
}

.type-item .amount {
  font-weight: bold;
  color: #667eea;
}

.top-user-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  border-bottom: 1px solid #e0e0e0;
}

.top-user-item .rank {
  font-weight: bold;
  color: #667eea;
  min-width: 30px;
}

.top-user-item img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.top-user-item .name {
  flex: 1;
  font-weight: 500;
  color: #333;
}

.top-user-item .balance {
  font-weight: bold;
  color: #667eea;
}
</style>
