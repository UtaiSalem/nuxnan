<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import { useAdminPoints } from '~/composables/useAdminPoints'
import BaseCard from '~/components/atoms/BaseCard.vue'

definePageMeta({
  layout: 'main',
  middleware: ['auth', 'admin']
})

useHead({
  title: 'จัดการแต้ม - Admin'
})

const authStore = useAuthStore()
const { 
  getStats, 
  getRules, 
  createRule, 
  updateRule, 
  deleteRule,
  adjustPoints,
  getLeaderboard,
  getAnalytics,
  isLoading 
} = useAdminPoints()

// State
const activeTab = ref('overview') // 'overview' | 'rules' | 'users' | 'analytics'
const stats = ref<any>(null)
const rules = ref<any[]>([])
const leaderboard = ref<any[]>([])
const analytics = ref<any>(null)

// Form states
const showRuleModal = ref(false)
const editingRule = ref<any>(null)
const ruleForm = ref({
  name: '',
  action_type: '',
  points_earned: 0,
  points_spent: 0,
  daily_limit: 0,
  is_active: true,
  description: ''
})

const showAdjustModal = ref(false)
const adjustForm = ref({
  user_id: '',
  amount: 0,
  type: 'add', // 'add' | 'deduct' | 'set'
  reason: ''
})

// Action types
const actionTypes = [
  { value: 'view_ad', label: 'ดูโฆษณา' },
  { value: 'post', label: 'โพสต์' },
  { value: 'comment', label: 'คอมเมนต์' },
  { value: 'like', label: 'กดถูกใจ' },
  { value: 'share', label: 'แชร์' },
  { value: 'donation', label: 'บริจาค' },
  { value: 'referral', label: 'แนะนำเพื่อน' },
  { value: 'quiz', label: 'ทำแบบทดสอบ' },
  { value: 'course', label: 'เรียนคอร์ส' },
  { value: 'poll', label: 'โหวตโพล' },
  { value: 'streak', label: 'เข้าติดต่อกัน' },
  { value: 'daily_login', label: 'ล็อกอินรายวัน' }
]

// Load data
const loadData = async () => {
  try {
    const [statsData, rulesData, leaderboardData, analyticsData] = await Promise.all([
      getStats(),
      getRules(),
      getLeaderboard({ limit: 10 }),
      getAnalytics()
    ])
    
    if (statsData) stats.value = statsData
    if (rulesData) rules.value = rulesData.rules || rulesData
    if (leaderboardData) leaderboard.value = leaderboardData.leaderboard || leaderboardData
    if (analyticsData) analytics.value = analyticsData
  } catch (error) {
    console.error('Failed to load admin data:', error)
  }
}

// Rule management
const openRuleModal = (rule?: any) => {
  if (rule) {
    editingRule.value = rule
    ruleForm.value = { ...rule }
  } else {
    editingRule.value = null
    ruleForm.value = {
      name: '',
      action_type: '',
      points_earned: 0,
      points_spent: 0,
      daily_limit: 0,
      is_active: true,
      description: ''
    }
  }
  showRuleModal.value = true
}

const saveRule = async () => {
  try {
    if (editingRule.value) {
      await updateRule(editingRule.value.id, ruleForm.value)
    } else {
      await createRule(ruleForm.value)
    }
    showRuleModal.value = false
    await loadData()
  } catch (error) {
    console.error('Failed to save rule:', error)
  }
}

const handleDeleteRule = async (ruleId: number) => {
  if (confirm('ต้องการลบกฎนี้หรือไม่?')) {
    try {
      await deleteRule(ruleId)
      await loadData()
    } catch (error) {
      console.error('Failed to delete rule:', error)
    }
  }
}

// Adjust points
const handleAdjustPoints = async () => {
  try {
    await adjustPoints(parseInt(adjustForm.value.user_id), {
      amount: adjustForm.value.amount,
      type: adjustForm.value.type,
      reason: adjustForm.value.reason
    })
    showAdjustModal.value = false
    adjustForm.value = { user_id: '', amount: 0, type: 'add', reason: '' }
    await loadData()
  } catch (error) {
    console.error('Failed to adjust points:', error)
  }
}

// Helpers
const formatNumber = (value: number): string => {
  return new Intl.NumberFormat('th-TH').format(value)
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

onMounted(loadData)
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <!-- Page Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">จัดการระบบแต้ม</h1>
          <p class="text-gray-600 dark:text-gray-400">Admin Points Management</p>
        </div>
        <div class="flex gap-3">
          <button
            class="px-4 py-2 bg-primary-500 text-white rounded-xl hover:bg-primary-600 transition-colors flex items-center gap-2"
            @click="showAdjustModal = true"
          >
            <Icon icon="mdi:account-edit" class="w-5 h-5" />
            ปรับแต้มผู้ใช้
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div v-if="stats" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <BaseCard>
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
              <Icon icon="mdi:star-circle" class="w-7 h-7 text-amber-500" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(stats.total_points || 0) }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">แต้มทั้งหมดในระบบ</p>
            </div>
          </div>
        </BaseCard>
        
        <BaseCard>
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
              <Icon icon="mdi:trending-up" class="w-7 h-7 text-green-500" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(stats.total_points_earned || 0) }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">แต้มที่แจกไป</p>
            </div>
          </div>
        </BaseCard>
        
        <BaseCard>
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
              <Icon icon="mdi:trending-down" class="w-7 h-7 text-red-500" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(stats.total_points_spent || 0) }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">แต้มที่ใช้ไป</p>
            </div>
          </div>
        </BaseCard>
        
        <BaseCard>
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
              <Icon icon="mdi:account-group" class="w-7 h-7 text-blue-500" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(stats.active_users || 0) }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ผู้ใช้ที่มีแต้ม</p>
            </div>
          </div>
        </BaseCard>
      </div>

      <!-- Tab Navigation -->
      <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
        <button 
          v-for="tab in [
            { key: 'overview', label: 'ภาพรวม', icon: 'mdi:view-dashboard' },
            { key: 'rules', label: 'กฎการได้แต้ม', icon: 'mdi:cog' },
            { key: 'users', label: 'ผู้ใช้', icon: 'mdi:account-group' },
            { key: 'analytics', label: 'วิเคราะห์', icon: 'mdi:chart-bar' },
          ]"
          :key="tab.key"
          class="px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap flex items-center gap-2"
          :class="activeTab === tab.key 
            ? 'bg-primary-500 text-white shadow' 
            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
          @click="activeTab = tab.key"
        >
          <Icon :icon="tab.icon" class="w-4 h-4" />
          {{ tab.label }}
        </button>
      </div>

      <!-- Tab Content -->
      <div v-if="isLoading" class="py-12 text-center">
        <Icon icon="mdi:loading" class="w-12 h-12 text-primary-500 animate-spin mx-auto" />
      </div>
      
      <div v-else>
        <!-- Overview Tab -->
        <div v-if="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Daily Stats -->
          <BaseCard>
            <div class="p-2">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">สถิติรายวัน</h3>
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <span class="text-gray-600 dark:text-gray-400">แต้มแจกวันนี้</span>
                  <span class="font-bold text-green-500">+{{ formatNumber(stats?.daily_earnings || 0) }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-gray-600 dark:text-gray-400">แต้มใช้วันนี้</span>
                  <span class="font-bold text-red-500">-{{ formatNumber(stats?.daily_spending || 0) }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-gray-600 dark:text-gray-400">แต้มแจกสัปดาห์นี้</span>
                  <span class="font-bold text-green-500">+{{ formatNumber(stats?.weekly_earnings || 0) }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-gray-600 dark:text-gray-400">แต้มแจกเดือนนี้</span>
                  <span class="font-bold text-green-500">+{{ formatNumber(stats?.monthly_earnings || 0) }}</span>
                </div>
              </div>
            </div>
          </BaseCard>
          
          <!-- Top Earners -->
          <BaseCard>
            <div class="p-2">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ผู้ใช้แต้มสูงสุด</h3>
              <div v-if="leaderboard.length > 0" class="space-y-3">
                <div 
                  v-for="(user, index) in leaderboard.slice(0, 5)" 
                  :key="user.id"
                  class="flex items-center gap-3"
                >
                  <div 
                    class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm"
                    :class="{
                      'bg-amber-500 text-white': index === 0,
                      'bg-slate-400 text-white': index === 1,
                      'bg-orange-400 text-white': index === 2,
                      'bg-sky-400 text-white': index === 3,
                      'bg-emerald-400 text-white': index === 4,
                      'bg-slate-300 dark:bg-gray-700 text-gray-600 dark:text-gray-300': index > 4
                    }"
                  >
                    {{ index + 1 }}
                  </div>
                  <img 
                    :src="user.avatar || '/images/default-avatar.png'" 
                    class="w-10 h-10 rounded-full object-cover"
                  >
                  <div class="flex-grow">
                    <p class="font-medium text-gray-900 dark:text-white">{{ user.name }}</p>
                  </div>
                  <span class="font-bold text-amber-500">{{ formatNumber(user.points) }}</span>
                </div>
              </div>
              <div v-else class="text-center py-4 text-gray-500">
                ไม่มีข้อมูล
              </div>
            </div>
          </BaseCard>
        </div>

        <!-- Rules Tab -->
        <div v-if="activeTab === 'rules'">
          <div class="flex justify-end mb-4">
            <button
              class="px-4 py-2 bg-primary-500 text-white rounded-xl hover:bg-primary-600 transition-colors flex items-center gap-2"
              @click="openRuleModal()"
            >
              <Icon icon="mdi:plus" class="w-5 h-5" />
              เพิ่มกฎใหม่
            </button>
          </div>
          
          <BaseCard>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">ชื่อกฎ</th>
                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">ประเภท</th>
                    <th class="text-center py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">แต้มที่ได้</th>
                    <th class="text-center py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">จำกัด/วัน</th>
                    <th class="text-center py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">สถานะ</th>
                    <th class="text-right py-3 px-4 text-sm font-medium text-gray-500 dark:text-gray-400">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr 
                    v-for="rule in rules" 
                    :key="rule.id"
                    class="border-b border-gray-100 dark:border-gray-700"
                  >
                    <td class="py-3 px-4">
                      <p class="font-medium text-gray-900 dark:text-white">{{ rule.name }}</p>
                      <p class="text-xs text-gray-500">{{ rule.description }}</p>
                    </td>
                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">
                      {{ actionTypes.find(t => t.value === rule.action_type)?.label || rule.action_type }}
                    </td>
                    <td class="py-3 px-4 text-center">
                      <span class="font-bold text-green-500">+{{ rule.points_earned }}</span>
                    </td>
                    <td class="py-3 px-4 text-center text-gray-600 dark:text-gray-400">
                      {{ rule.daily_limit || '∞' }}
                    </td>
                    <td class="py-3 px-4 text-center">
                      <span 
                        class="px-2 py-1 rounded-full text-xs font-medium"
                        :class="rule.is_active 
                          ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' 
                          : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'"
                      >
                        {{ rule.is_active ? 'เปิดใช้' : 'ปิด' }}
                      </span>
                    </td>
                    <td class="py-3 px-4 text-right">
                      <button 
                        class="p-2 text-gray-500 hover:text-primary-500"
                        @click="openRuleModal(rule)"
                      >
                        <Icon icon="mdi:pencil" class="w-5 h-5" />
                      </button>
                      <button 
                        class="p-2 text-gray-500 hover:text-red-500"
                        @click="handleDeleteRule(rule.id)"
                      >
                        <Icon icon="mdi:delete" class="w-5 h-5" />
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </BaseCard>
        </div>

        <!-- Users Tab -->
        <div v-if="activeTab === 'users'">
          <BaseCard>
            <div class="p-2">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ผู้ใช้ทั้งหมด</h3>
              <div v-if="leaderboard.length > 0" class="space-y-3">
                <div 
                  v-for="user in leaderboard" 
                  :key="user.id"
                  class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-xl"
                >
                  <img 
                    :src="user.avatar || '/images/default-avatar.png'" 
                    class="w-12 h-12 rounded-full object-cover"
                  >
                  <div class="flex-grow">
                    <p class="font-medium text-gray-900 dark:text-white">{{ user.name }}</p>
                    <p class="text-sm text-gray-500">ID: {{ user.id }} | เลเวล {{ user.level }}</p>
                  </div>
                  <div class="text-right">
                    <p class="font-bold text-amber-500">{{ formatNumber(user.points) }} แต้ม</p>
                  </div>
                  <button 
                    class="px-3 py-1 bg-primary-100 text-primary-600 rounded-lg text-sm hover:bg-primary-200"
                    @click="adjustForm.user_id = user.id.toString(); showAdjustModal = true"
                  >
                    ปรับแต้ม
                  </button>
                </div>
              </div>
            </div>
          </BaseCard>
        </div>

        <!-- Analytics Tab -->
        <div v-if="activeTab === 'analytics'">
          <BaseCard>
            <div class="p-4 text-center text-gray-500 dark:text-gray-400">
              <Icon icon="mdi:chart-bar" class="w-16 h-16 mx-auto mb-4" />
              <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">กราฟวิเคราะห์</h3>
              <p>กำลังพัฒนา - จะแสดงกราฟแนวโน้มและการวิเคราะห์ข้อมูลแต้ม</p>
            </div>
          </BaseCard>
        </div>
      </div>

      <!-- Rule Modal -->
      <Teleport to="body">
        <div v-if="showRuleModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click="showRuleModal = false">
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full p-6" @click.stop>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
              {{ editingRule ? 'แก้ไขกฎ' : 'เพิ่มกฎใหม่' }}
            </h3>
            
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อกฎ</label>
                <input 
                  v-model="ruleForm.name"
                  type="text"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภทกิจกรรม</label>
                <select 
                  v-model="ruleForm.action_type"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                >
                  <option value="">เลือกประเภท</option>
                  <option v-for="type in actionTypes" :key="type.value" :value="type.value">
                    {{ type.label }}
                  </option>
                </select>
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">แต้มที่ได้</label>
                  <input 
                    v-model.number="ruleForm.points_earned"
                    type="number"
                    min="0"
                    class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำกัด/วัน</label>
                  <input 
                    v-model.number="ruleForm.daily_limit"
                    type="number"
                    min="0"
                    placeholder="0 = ไม่จำกัด"
                    class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                  >
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                <textarea 
                  v-model="ruleForm.description"
                  rows="2"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                ></textarea>
              </div>
              
              <div class="flex items-center gap-2">
                <input type="checkbox" id="is_active" v-model="ruleForm.is_active" class="rounded">
                <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน</label>
              </div>
            </div>
            
            <div class="flex gap-3 mt-6">
              <button 
                class="flex-1 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl"
                @click="showRuleModal = false"
              >
                ยกเลิก
              </button>
              <button 
                class="flex-1 py-2 bg-primary-500 text-white rounded-xl"
                @click="saveRule"
              >
                บันทึก
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Adjust Points Modal -->
      <Teleport to="body">
        <div v-if="showAdjustModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click="showAdjustModal = false">
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6" @click.stop>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">ปรับแต้มผู้ใช้</h3>
            
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">User ID</label>
                <input 
                  v-model="adjustForm.user_id"
                  type="text"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                  placeholder="ระบุ User ID"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
                <div class="flex gap-2">
                  <button 
                    v-for="t in [{ value: 'add', label: 'เพิ่ม', color: 'green' }, { value: 'deduct', label: 'หัก', color: 'red' }, { value: 'set', label: 'ตั้งค่า', color: 'blue' }]"
                    :key="t.value"
                    class="flex-1 py-2 rounded-xl font-medium transition-colors"
                    :class="adjustForm.type === t.value 
                      ? `bg-${t.color}-500 text-white` 
                      : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'"
                    @click="adjustForm.type = t.value"
                  >
                    {{ t.label }}
                  </button>
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวน</label>
                <input 
                  v-model.number="adjustForm.amount"
                  type="number"
                  min="0"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เหตุผล</label>
                <input 
                  v-model="adjustForm.reason"
                  type="text"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                  placeholder="ระบุเหตุผล"
                >
              </div>
            </div>
            
            <div class="flex gap-3 mt-6">
              <button 
                class="flex-1 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl"
                @click="showAdjustModal = false"
              >
                ยกเลิก
              </button>
              <button 
                class="flex-1 py-2 bg-primary-500 text-white rounded-xl"
                @click="handleAdjustPoints"
              >
                บันทึก
              </button>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </div>
</template>
