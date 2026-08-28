<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">ระบบแต้มรางวัล & Leaderboard</h2>
      <div class="flex gap-2">
        <button class="min-h-[44px] sm:min-h-0"
          v-for="section in sections"
          :key="section.id"
          @click="activeSection = section.id"
          :class="[
            activeSection === section.id
              ? 'bg-primary-500 text-white'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300',
            'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors'
          ]"
        >
          {{ section.name }}
        </button>
      </div>
    </div>

    <!-- Point Rules Section -->
    <div v-if="activeSection === 'rules'" class="space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">นโยบายการมอบแต้ม</h3>
        <button
          @click="showRuleModal = true; ruleForm = { rule_type: 'attendance', points: 10, name: '', is_active: true, requires_approval: false }"
          class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
        >
          <Icon icon="heroicons:plus" class="h-5 w-5" />
          <span>เพิ่มกฎ</span>
        </button>
      </div>

      <div v-if="loadingRules" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="rule in pointRules"
          :key="rule.id"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow"
        >
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
              <div :class="getRuleIconClass(rule.rule_type)" class="p-2 rounded-lg text-white">
                <Icon :icon="getRuleIcon(rule.rule_type)" class="h-5 w-5" />
              </div>
              <div>
                <h4 class="font-bold text-gray-900 dark:text-white">{{ rule.name }}</h4>
                <p class="text-xs text-gray-500 uppercase font-black tracking-widest">{{ rule.rule_type }}</p>
              </div>
            </div>
            <div class="flex flex-col items-end gap-2">
              <span :class="rule.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="px-2 py-0.5 text-[10px] font-black rounded-full uppercase">
                {{ rule.is_active ? 'เปิด' : 'ปิด' }}
              </span>
            </div>
          </div>
          
          <div class="mt-4 flex items-end justify-between border-t border-gray-50 dark:border-gray-700 pt-4">
            <div>
              <span class="text-3xl font-black text-primary-600">+{{ rule.points }}</span>
              <span class="text-xs text-gray-400 ml-1 font-bold">แต้ม</span>
            </div>
            <div class="flex gap-2">
              <button @click="editRule(rule)" class="p-1.5 text-gray-400 hover:text-primary-500 hover:bg-primary-50 rounded-lg transition-colors">
                <Icon icon="heroicons:pencil-square" class="h-5 w-5" />
              </button>
              <button @click="deleteRule(rule)" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                <Icon icon="heroicons:trash" class="h-5 w-5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Leaderboard Section -->
    <div v-if="activeSection === 'leaderboard'" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- House Leaderboard -->
      <div class="space-y-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
          <Icon icon="heroicons:shield-check" class="h-6 w-6 text-blue-500" />
          House Leaderboard
        </h3>
        
        <div v-if="loadingLeaderboards" class="animate-pulse space-y-3">
          <div v-for="i in 4" :key="i" class="h-16 bg-gray-100 dark:bg-gray-800 rounded-xl"></div>
        </div>
        
        <div v-else class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div 
            v-for="(house, index) in houseLeaderboard" 
            :key="house.id"
            class="flex items-center justify-between p-4 border-b last:border-0 border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/40 transition-colors"
          >
            <div class="flex items-center gap-4">
              <div class="w-8 text-center font-black text-gray-300">{{ index + 1 }}</div>
              <div :style="{ backgroundColor: house.color }" class="w-12 h-12 rounded-xl flex items-center justify-center text-white shadow-lg">
                <Icon :icon="house.icon" class="h-7 w-7" />
              </div>
              <div>
                <p class="font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ house.name }}</p>
                <p class="text-xs text-gray-500">{{ house.member_count }} สมาชิก</p>
              </div>
            </div>
            <div class="text-right">
              <p class="text-xl font-black text-primary-600">{{ formatNumber(house.points) }}</p>
              <p class="text-[10px] text-gray-400 font-bold uppercase">Points</p>
            </div>
          </div>
          <div v-if="houseLeaderboard.length === 0" class="p-12 text-center text-gray-400">
            <Icon icon="heroicons:face-frown" class="h-12 w-12 mx-auto mb-2 opacity-20" />
            <p>ยังไม่มีข้อมูลทีม/บ้าน</p>
          </div>
        </div>
      </div>

      <!-- Classroom Leaderboard -->
      <div class="space-y-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
          <Icon icon="heroicons:academic-cap" class="h-6 w-6 text-purple-500" />
          คะแนนเฉลี่ยรายห้อง
        </h3>
        
        <div v-if="loadingLeaderboards" class="animate-pulse space-y-3">
          <div v-for="i in 4" :key="i" class="h-16 bg-gray-100 dark:bg-gray-800 rounded-xl"></div>
        </div>
        
        <div v-else class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
          <div 
            v-for="(room, index) in classroomLeaderboard" 
            :key="room.id"
            class="flex items-center justify-between p-4 border-b last:border-0 border-gray-100 dark:border-gray-700"
          >
            <div class="flex items-center gap-4">
              <div class="w-6 text-center font-bold text-gray-400">{{ index + 1 }}</div>
              <div>
                <p class="font-bold text-gray-900 dark:text-white">{{ room.name }}</p>
                <p class="text-xs text-gray-500">{{ room.student_count }} นักเรียน</p>
              </div>
            </div>
            <div class="text-right">
              <p class="text-lg font-black text-purple-600">{{ formatNumber(room.points) }}</p>
              <p class="text-[10px] text-gray-400 font-bold uppercase">รวมแต้ม</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Rule Modal -->
    <SchoolModal v-model="showRuleModal" :title="editingRuleId ? 'แก้ไขกฎ' : 'เพิ่มกฎการมอบแต้ม'" size="md">
      <form @submit.prevent="saveRule" class="space-y-6 p-2">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2 sm:col-span-1">
            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">ประเภทกิจกรรม</label>
            <select v-model="ruleForm.rule_type" required class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 outline-none dark:text-white">
              <option value="attendance">การเข้าเรียน</option>
              <option value="behavior">พฤติกรรม</option>
              <option value="event">การร่วมกิจกรรม</option>
              <option value="milestone">ความสำเร็จ</option>
            </select>
          </div>
          <div class="col-span-2 sm:col-span-1">
            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">จำนวนแต้ม</label>
            <input v-model.number="ruleForm.points" type="number" required class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 outline-none dark:text-white" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">ชื่อกฎ/ชื่อกิจกรรม</label>
          <input v-model="ruleForm.name" type="text" required placeholder="เช่น มาเรียนเช้า, ช่วยเหลือเพื่อน" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 outline-none dark:text-white" />
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">รายละเอียด (Optional)</label>
          <textarea v-model="ruleForm.description" rows="2" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 outline-none dark:text-white resize-none"></textarea>
        </div>

        <div class="flex flex-col gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-700">
          <label class="flex items-center gap-3 cursor-pointer group">
            <div class="relative inline-flex items-center">
              <input type="checkbox" v-model="ruleForm.is_active" class="sr-only peer" />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
            </div>
            <span class="text-sm font-bold text-gray-700 dark:text-gray-300 group-hover:text-primary-600 transition-colors">เปิดใช้งานกฎนี้</span>
          </label>
          <label class="flex items-center gap-3 cursor-pointer group">
            <div class="relative inline-flex items-center">
              <input type="checkbox" v-model="ruleForm.requires_approval" class="sr-only peer" />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
            </div>
            <span class="text-sm font-bold text-gray-700 dark:text-gray-300 group-hover:text-primary-600 transition-colors">ต้องได้รับการอนุมัติก่อนมอบแต้ม</span>
          </label>
        </div>

        <div class="flex gap-4 pt-4 border-t border-gray-100 dark:border-gray-700">
          <button type="button" @click="showRuleModal = false" class="flex-1 px-4 sm:px-6 py-4 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-2xl font-bold hover:bg-gray-200 transition-all">
            ยกเลิก
          </button>
          <button type="submit" :disabled="savingRule" class="flex-[2] px-6 py-4 bg-primary-500 text-white rounded-2xl font-bold shadow-xl shadow-primary-200 dark:shadow-none hover:bg-primary-600 active:scale-95 transition-all disabled:opacity-50">
            {{ savingRule ? 'กำลังบันทึก...' : (editingRuleId ? 'อัปเดตกฎ' : 'บันทึกกฎใหม่') }}
          </button>
        </div>
      </form>
    </SchoolModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  academyId: number
}>()

const schoolApi = useSchoolManagement()

// State
const activeSection = ref('rules')
const sections = [
  { id: 'rules', name: 'นโยบายแต้ม' },
  { id: 'leaderboard', name: 'Leaderboard' },
]

// Point Rules
const pointRules = ref<any[]>([])
const loadingRules = ref(false)
const showRuleModal = ref(false)
const savingRule = ref(false)
const editingRuleId = ref<number | null>(null)
const ruleForm = ref({
  rule_type: 'attendance',
  name: '',
  description: '',
  points: 10,
  is_active: true,
  requires_approval: false,
})

// Leaderboards
const houseLeaderboard = ref<any[]>([])
const classroomLeaderboard = ref<any[]>([])
const loadingLeaderboards = ref(false)

// Helpers
const formatNumber = (num: number) => {
  return new Intl.NumberFormat('th-TH').format(num || 0)
}

const getRuleIcon = (type: string) => {
  switch (type) {
    case 'attendance': return 'heroicons:calendar-days'
    case 'behavior': return 'heroicons:heart'
    case 'event': return 'heroicons:star'
    case 'milestone': return 'heroicons:trophy'
    default: return 'heroicons:sparkles'
  }
}

const getRuleIconClass = (type: string) => {
  switch (type) {
    case 'attendance': return 'bg-blue-500'
    case 'behavior': return 'bg-rose-500'
    case 'event': return 'bg-amber-500'
    case 'milestone': return 'bg-purple-500'
    default: return 'bg-gray-500'
  }
}

const extractArray = (response: any): any[] => {
  if (!response) return []
  if (Array.isArray(response)) return response
  if (response.success && response.data) return response.data
  return []
}

// Actions
const loadPointRules = async () => {
  loadingRules.value = true
  try {
    const response = await schoolApi.getPointRules(props.academyId)
    pointRules.value = extractArray(response)
  } catch (err) {
    console.error('Failed to load point rules:', err)
  } finally {
    loadingRules.value = false
  }
}

const loadLeaderboards = async () => {
  loadingLeaderboards.value = true
  try {
    const [houses, rooms] = await Promise.all([
      schoolApi.getHouseLeaderboard(props.academyId),
      schoolApi.getClassroomLeaderboard(props.academyId)
    ])
    houseLeaderboard.value = extractArray(houses)
    classroomLeaderboard.value = extractArray(rooms)
  } catch (err) {
    console.error('Failed to load leaderboards:', err)
  } finally {
    loadingLeaderboards.value = false
  }
}

const saveRule = async () => {
  savingRule.value = true
  try {
    let response: any
    if (editingRuleId.value) {
      response = await schoolApi.updatePointRule(props.academyId, editingRuleId.value, ruleForm.value)
    } else {
      response = await schoolApi.createPointRule(props.academyId, ruleForm.value)
    }
    
    if (response.success) {
      showRuleModal.value = false
      editingRuleId.value = null
      await loadPointRules()
    }
  } catch (err) {
    console.error('Failed to save rule:', err)
  } finally {
    savingRule.value = false
  }
}

const editRule = (rule: any) => {
  editingRuleId.value = rule.id
  ruleForm.value = {
    rule_type: rule.rule_type,
    name: rule.name,
    description: rule.description || '',
    points: rule.points,
    is_active: rule.is_active,
    requires_approval: rule.requires_approval,
  }
  showRuleModal.value = true
}

const deleteRule = async (rule: any) => {
  if (!confirm(`คุณต้องการลบกฎ "${rule.name}" ใช่หรือไม่?`)) return
  try {
    const response: any = await schoolApi.deletePointRule(props.academyId, rule.id)
    if (response.success) {
      await loadPointRules()
    }
  } catch (err) {
    console.error('Failed to delete rule:', err)
  }
}

watch(activeSection, (section) => {
  if (section === 'rules' && pointRules.value.length === 0) loadPointRules()
  if (section === 'leaderboard' && houseLeaderboard.value.length === 0) loadLeaderboards()
}, { immediate: true })

onMounted(() => {
  loadPointRules()
  loadLeaderboards()
})
</script>
