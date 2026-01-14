<template>
  <div class="points-dashboard">
    <!-- Stats Overview -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">
          <Icon name="fluent:star-24-filled" />
        </div>
        <div class="stat-content">
          <h3 class="stat-value">{{ formatPoints(stats?.total_points || 0) }}</h3>
          <p class="stat-label">แต้มรวมทั้งหมด</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon earned">
          <Icon name="fluent:add-circle-24-filled" />
        </div>
        <div class="stat-content">
          <h3 class="stat-value">{{ formatPoints(stats?.total_points_earned || 0) }}</h3>
          <p class="stat-label">แต้มที่ออกแล้ว</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon spent">
          <Icon name="fluent:subtract-circle-24-filled" />
        </div>
        <div class="stat-content">
          <h3 class="stat-value">{{ formatPoints(stats?.total_points_spent || 0) }}</h3>
          <p class="stat-label">แต้มที่ใช้ไป</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon users">
          <Icon name="fluent:people-24-filled" />
        </div>
        <div class="stat-content">
          <h3 class="stat-value">{{ stats?.active_users || 0 }}</h3>
          <p class="stat-label">ผู้ใช้ที่มีแต้ม</p>
        </div>
      </div>
    </div>

    <!-- Today's Stats -->
    <div class="section-card">
      <h2>สถิติวันนี้</h2>
      <div class="today-stats">
        <div class="today-item">
          <span class="label">ได้รับ:</span>
          <span class="value positive">+{{ formatPoints(stats?.today?.earned || 0) }}</span>
        </div>
        <div class="today-item">
          <span class="label">ใช้ไป:</span>
          <span class="value negative">-{{ formatPoints(stats?.today?.spent || 0) }}</span>
        </div>
        <div class="today-item">
          <span class="label">ธุรกรรม:</span>
          <span class="value">{{ stats?.today?.transactions || 0 }}</span>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="section-card">
      <h2>การจัดการด่วน</h2>
      <div class="quick-actions">
        <button @click="showAdjustPointsModal = true" class="action-btn">
          <Icon name="fluent:edit-24-regular" />
          ปรับแต้มผู้ใช้
        </button>
        <button @click="showRulesModal = true" class="action-btn">
          <Icon name="fluent:settings-24-regular" />
          จัดการกฎแต้ม
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

    <!-- Adjust Points Modal -->
    <div v-if="showAdjustPointsModal" class="modal-overlay" @click="showAdjustPointsModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>ปรับแต้มผู้ใช้</h2>
          <button @click="showAdjustPointsModal = false" class="btn-close">
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
              <p>แต้มปัจจุบัน: {{ formatPoints(selectedUser.pp) }}</p>
            </div>
          </div>

          <div v-if="selectedUser" class="form-group">
            <label>ประเภทการปรับ:</label>
            <div class="radio-group">
              <label class="radio-option">
                <input v-model="adjustmentType" type="radio" value="add" />
                <span>เพิ่มแต้ม</span>
              </label>
              <label class="radio-option">
                <input v-model="adjustmentType" type="radio" value="deduct" />
                <span>หักแต้ม</span>
              </label>
              <label class="radio-option">
                <input v-model="adjustmentType" type="radio" value="set" />
                <span>ตั้งค่าแต้ม</span>
              </label>
            </div>
          </div>

          <div v-if="selectedUser" class="form-group">
            <label>จำนวนแต้ม:</label>
            <input v-model.number="adjustmentAmount" type="number" min="0" />
          </div>

          <div v-if="selectedUser" class="form-group">
            <label>เหตุผล:</label>
            <textarea v-model="adjustmentReason" placeholder="ระบุเหตุผลการปรับแต้ม"></textarea>
          </div>

          <div v-if="selectedUser && adjustmentAmount > 0" class="preview">
            <h4>ตัวอย่างผลลัพธ์:</h4>
            <div class="preview-item">
              <span>แต้มปัจจุบัน:</span>
              <span>{{ formatPoints(selectedUser.pp) }}</span>
            </div>
            <div class="preview-item" v-if="adjustmentType === 'add'">
              <span>เพิ่ม:</span>
              <span>+{{ formatPoints(adjustmentAmount) }}</span>
            </div>
            <div class="preview-item" v-if="adjustmentType === 'deduct'">
              <span>หัก:</span>
              <span>-{{ formatPoints(adjustmentAmount) }}</span>
            </div>
            <div class="preview-item" v-if="adjustmentType === 'set'">
              <span>ตั้งค่า:</span>
              <span>{{ formatPoints(adjustmentAmount) }}</span>
            </div>
            <div class="preview-item total">
              <span>แต้มใหม่:</span>
              <span>{{ formatPoints(newBalance) }}</span>
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

    <!-- Analytics Modal -->
    <div v-if="showAnalyticsModal" class="modal-overlay" @click="showAnalyticsModal = false">
      <div class="modal-content large" @click.stop>
        <div class="modal-header">
          <h2>รายงานวิเคราะห์แต้ม</h2>
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
              <h3>แนวโน้มแต้มรายวัน</h3>
              <div class="trend-chart">
                <div
                  v-for="day in analytics.daily_trend"
                  :key="day.date"
                  class="trend-item"
                >
                  <span class="date">{{ formatDate(day.date) }}</span>
                  <span class="earned positive">+{{ formatPoints(day.earned) }}</span>
                  <span class="spent negative">-{{ formatPoints(day.spent) }}</span>
                </div>
              </div>
            </div>

            <div class="analytics-section">
              <h3>การกระจายแต้มตามกิจกรรม</h3>
              <div class="distribution-chart">
                <div
                  v-for="source in analytics.source_distribution"
                  :key="source.source_type"
                  class="distribution-item"
                >
                  <span class="source">{{ source.source_type }}</span>
                  <span class="amount">{{ formatPoints(source.total_amount) }}</span>
                  <span class="count">{{ source.count }} ครั้ง</span>
                </div>
              </div>
            </div>

            <div class="analytics-section">
              <h3>ผู้ใช้ที่ได้แต้มสูงสุด</h3>
              <div class="top-users">
                <div
                  v-for="(user, index) in analytics.top_earners"
                  :key="user.id"
                  class="top-user-item"
                >
                  <span class="rank">#{{ index + 1 }}</span>
                  <img :src="user.avatar" :alt="user.name" />
                  <span class="name">{{ user.name }}</span>
                  <span class="points">{{ formatPoints(user.total_points_earned) }}</span>
                </div>
              </div>
            </div>

            <div class="analytics-section">
              <h3>ผู้ใช้ที่ใช้แต้มสูงสุด</h3>
              <div class="top-users">
                <div
                  v-for="(user, index) in analytics.top_spenders"
                  :key="user.id"
                  class="top-user-item"
                >
                  <span class="rank">#{{ index + 1 }}</span>
                  <img :src="user.avatar" :alt="user.name" />
                  <span class="name">{{ user.name }}</span>
                  <span class="points">{{ formatPoints(user.total_points_spent) }}</span>
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
import { useAdminPoints } from '~/composables/useAdminPoints'

const { getStats, adjustPoints, getAnalytics, formatPoints } = useAdminPoints()

const stats = ref<any>(null)
const showAdjustPointsModal = ref(false)
const showRulesModal = ref(false)
const showAnalyticsModal = ref(false)
const searchQuery = ref('')
const searchResults = ref<any[]>([])
const selectedUser = ref<any>(null)
const adjustmentType = ref('add')
const adjustmentAmount = ref(0)
const adjustmentReason = ref('')
const analytics = ref<any>(null)
const analyticsFilters = ref({
  start_date: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
})

const newBalance = computed(() => {
  if (!selectedUser.value) return 0
  switch (adjustmentType.value) {
    case 'add':
      return selectedUser.value.pp + adjustmentAmount.value
    case 'deduct':
      return Math.max(0, selectedUser.value.pp - adjustmentAmount.value)
    case 'set':
      return adjustmentAmount.value
    default:
      return selectedUser.value.pp
  }
})

const canSubmit = computed(() => {
  return selectedUser.value && adjustmentAmount.value > 0 && adjustmentReason.value.length > 0
})

onMounted(async () => {
  await loadStats()
})

const loadStats = async () => {
  const { data } = await getStats()
  if (data) {
    stats.value = data
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

  const { data, error } = await adjustPoints(selectedUser.value.id, {
    action: adjustmentType.value,
    amount: adjustmentAmount.value,
    reason: adjustmentReason.value,
  })

  if (data) {
    alert('ปรับแต้มสำเร็จ!')
    showAdjustPointsModal.value = false
    resetForm()
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
.points-dashboard {
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

.stat-icon.earned {
  background: rgba(76, 175, 80, 0.2);
}

.stat-icon.spent {
  background: rgba(244, 67, 54, 0.2);
}

.stat-icon.users {
  background: rgba(33, 150, 243, 0.2);
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

.today-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
}

.today-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background: #f5f5f5;
  border-radius: 8px;
}

.today-item .label {
  font-weight: 500;
  color: #666;
}

.today-item .value {
  font-weight: bold;
  font-size: 18px;
}

.today-item .value.positive {
  color: #4caf50;
}

.today-item .value.negative {
  color: #f44336;
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

.form-group textarea {
  min-height: 80px;
  resize: vertical;
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
.distribution-chart,
.top-users {
  max-height: 300px;
  overflow-y: auto;
}

.trend-item,
.distribution-item {
  display: flex;
  justify-content: space-between;
  padding: 10px;
  border-bottom: 1px solid #e0e0e0;
}

.trend-item .date {
  font-weight: 500;
  color: #333;
}

.trend-item .earned {
  color: #4caf50;
}

.trend-item .spent {
  color: #f44336;
}

.distribution-item .source {
  font-weight: 500;
  color: #333;
}

.distribution-item .amount {
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

.top-user-item .points {
  font-weight: bold;
  color: #667eea;
}
</style>
