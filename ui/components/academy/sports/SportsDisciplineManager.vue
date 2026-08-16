<template>
  <div class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700">
    <div class="p-3 sm:p-6 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
      <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">จัดการรายการแข่ง</h3>
      <button v-if="canManage && !showForm" @click="openCreateForm" class="bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed p-3 sm:px-4 sm:py-2 text-sm w-full sm:w-auto flex items-center justify-center gap-2 min-h-[44px] sm:min-h-0">
        <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
        เพิ่มรายการ
      </button>
    </div>

    <!-- แถบแสดง error -->
    <div v-if="errorMessage" class="bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 p-3 sm:p-4 text-sm font-medium border-b border-rose-100 dark:border-rose-900/50">
      {{ errorMessage }}
    </div>

    <div v-if="showForm && canManage" class="p-3 sm:p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
      <form @submit.prevent="submitForm" class="space-y-4">
        <div>
          <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">ชื่อรายการ <span class="text-rose-500">*</span></label>
          <input v-model="form.name" type="text" required maxlength="150" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none min-h-[44px]" placeholder="เช่น วิ่ง 100 เมตร ชาย" />
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">ประเภท</label>
          <div class="flex flex-col sm:flex-row gap-2">
            <label class="flex-1 min-h-[44px] flex items-center p-3 border rounded-lg cursor-pointer transition-colors" :class="form.type === 'team' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300' : 'border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300'">
              <input type="radio" v-model="form.type" value="team" class="sr-only" @change="handleTypeChange" />
              <div class="flex items-center gap-2">
                <Icon icon="fluent:people-team-24-regular" class="w-5 h-5" />
                <span>ทีม</span>
              </div>
            </label>
            <label class="flex-1 min-h-[44px] flex items-center p-3 border rounded-lg cursor-pointer transition-colors" :class="form.type === 'individual' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300' : 'border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300'">
              <input type="radio" v-model="form.type" value="individual" class="sr-only" @change="handleTypeChange" />
              <div class="flex items-center gap-2">
                <Icon icon="fluent:person-24-regular" class="w-5 h-5" />
                <span>เดี่ยว</span>
              </div>
            </label>
            <label class="flex-1 min-h-[44px] flex items-center p-3 border rounded-lg cursor-pointer transition-colors" :class="form.type === 'judged' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300' : 'border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300'">
              <input type="radio" v-model="form.type" value="judged" class="sr-only" @change="handleTypeChange" />
              <div class="flex items-center gap-2">
                <Icon icon="fluent:star-24-regular" class="w-5 h-5" />
                <span>กรรมการให้คะแนน</span>
              </div>
            </label>
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">ลำดับการแสดง</label>
          <input v-model.number="form.display_order" type="number" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none min-h-[44px]" />
        </div>

        <div v-if="form.type !== 'judged'">
          <div class="flex justify-between items-center mb-2">
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-0">ตารางคะแนน</label>
            <button type="button" @click="resetScoringTable" class="text-xs text-purple-600 dark:text-purple-400 hover:underline min-h-[44px] px-2">ใช้ค่าตั้งต้น</button>
          </div>
          <div class="space-y-2">
            <div v-for="(item, index) in form.scoringRows" :key="index" class="flex items-center gap-2">
              <span class="text-sm text-slate-600 dark:text-slate-400 flex-shrink-0">อันดับที่</span>
              <input v-model.number="item.placing" type="number" min="1" required class="w-20 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none min-h-[44px]" />
              <span class="text-sm text-slate-600 dark:text-slate-400 flex-shrink-0">ได้</span>
              <input v-model.number="item.points" type="number" step="0.01" required class="flex-1 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none min-h-[44px]" />
              <span class="text-sm text-slate-600 dark:text-slate-400 flex-shrink-0 hidden sm:inline">คะแนน</span>
              <button type="button" @click="removeScoringRow(index)" class="text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 p-2 rounded-lg transition-colors flex-shrink-0 min-h-[44px] min-w-[44px] flex items-center justify-center">
                <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
              </button>
            </div>
          </div>
          <button type="button" @click="addScoringRow" class="mt-2 text-sm text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 px-3 py-2 rounded-lg transition-colors flex items-center gap-1 min-h-[44px]">
            <Icon icon="fluent:add-circle-24-regular" class="w-4 h-4" />
            เพิ่มอันดับ
          </button>
        </div>

        <div v-if="form.type === 'judged'">
          <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">คะแนนเต็ม</label>
          <input v-model.number="form.max_score" type="number" step="0.01" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none min-h-[44px]" />
        </div>

        <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-700">
          <button type="button" @click="closeForm" class="border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 px-4 py-2 rounded-vikinger transition-all min-h-[44px] sm:min-h-0">
            ยกเลิก
          </button>
          <button type="submit" :disabled="isSubmitting" class="bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed px-4 py-2 min-h-[44px] sm:min-h-0 flex items-center justify-center gap-2">
            <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
            บันทึก
          </button>
        </div>
      </form>
    </div>

    <!-- รายการ -->
    <div v-if="disciplines.length === 0" class="p-8 text-center text-slate-500 dark:text-slate-400">
      <Icon icon="fluent:trophy-24-regular" class="w-12 h-12 mx-auto mb-3 opacity-50" />
      <p class="mb-1">ยังไม่มีรายการแข่ง</p>
      <p class="text-sm">ต้องสร้างรายการแข่งก่อนจึงจะบันทึกอันดับได้</p>
    </div>
    <div v-else class="divide-y divide-slate-200 dark:divide-slate-700">
      <div v-for="d in disciplines" :key="d.id" class="p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
        <div class="flex-1 min-w-0 break-words">
          <div class="flex items-center gap-2 mb-1">
            <h4 class="font-semibold text-slate-900 dark:text-white text-base truncate">{{ d.name }}</h4>
            <span v-if="d.type === 'team'" class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">ทีม</span>
            <span v-else-if="d.type === 'individual'" class="px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">เดี่ยว</span>
            <span v-else-if="d.type === 'judged'" class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">กรรมการ</span>
          </div>
          <div class="text-sm text-slate-500 dark:text-slate-400">
            <template v-if="d.type === 'judged'">
              เต็ม {{ num(d.max_score || DEFAULT_JUDGED_MAX_SCORE) }} คะแนน
            </template>
            <template v-else>
              {{ getScoringSummary(d.scoring_table) }}
            </template>
          </div>
        </div>
        
        <div v-if="canManage" class="flex items-center gap-2 flex-shrink-0 justify-end sm:justify-start">
          <button @click="openEditForm(d)" class="p-2 text-slate-500 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center">
            <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
          </button>
          <button @click="handleDelete(d)" class="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center">
            <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'
import { useSportsScoring, DEFAULT_JUDGED_MAX_SCORE } from '~/composables/useSportsScoring'
import type { SportsDiscipline } from '~/composables/useSportsScoring'

const props = defineProps<{
  academyId: number
  editionId: number
  disciplines: SportsDiscipline[]
  canManage: boolean
}>()

const emit = defineEmits<{
  (e: 'refresh'): void
}>()

// DEFAULT_JUDGED_MAX_SCORE เป็น export ระดับโมดูล ไม่ได้อยู่ในค่าที่ useSportsScoring() คืนมา
// ถ้าไป destructure จากตัว composable จะได้ undefined แล้ว num(undefined) = 0 → จอโชว์ "เต็ม 0 คะแนน"
const {
  createDiscipline,
  updateDiscipline,
  deleteDiscipline,
  defaultScoringTable,
  num
} = useSportsScoring()

const showForm = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const editingId = ref<number | null>(null)

const form = ref({
  name: '',
  type: 'team' as 'team' | 'individual' | 'judged',
  display_order: 0,
  max_score: DEFAULT_JUDGED_MAX_SCORE as number | null,
  scoringRows: [] as { placing: number; points: number }[]
})

const getScoringSummary = (table: Record<string, number> | null) => {
  if (!table) return 'ไม่มีตารางคะแนน'
  
  const placings = Object.keys(table).map(Number).sort((a, b) => a - b)
  if (placings.length === 0) return 'ไม่มีตารางคะแนน'
  
  const parts = placings.slice(0, 4).map(p => `ที่ ${p} = ${table[p]}`)
  if (placings.length > 4) {
    parts.push('…')
  }
  return parts.join(' · ')
}

const openCreateForm = () => {
  editingId.value = null
  form.value = {
    name: '',
    type: 'team',
    display_order: 0,
    max_score: DEFAULT_JUDGED_MAX_SCORE,
    scoringRows: []
  }
  handleTypeChange()
  errorMessage.value = ''
  showForm.value = true
}

const openEditForm = (d: SportsDiscipline) => {
  editingId.value = d.id
  form.value = {
    name: d.name,
    type: d.type,
    display_order: d.display_order,
    max_score: d.max_score ? num(d.max_score) : DEFAULT_JUDGED_MAX_SCORE,
    scoringRows: []
  }
  
  if (d.scoring_table) {
    form.value.scoringRows = Object.keys(d.scoring_table).map(Number).sort((a, b) => a - b).map(placing => ({
      placing,
      points: d.scoring_table![placing]
    }))
  }
  
  errorMessage.value = ''
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
  errorMessage.value = ''
}

const handleTypeChange = () => {
  if (editingId.value === null) {
    resetScoringTable()
  }
}

const resetScoringTable = () => {
  const table = defaultScoringTable(form.value.type)
  if (table) {
    form.value.scoringRows = Object.keys(table).map(Number).sort((a, b) => a - b).map(placing => ({
      placing,
      points: table[placing]
    }))
  } else {
    form.value.scoringRows = []
  }
}

const addScoringRow = () => {
  const nextPlacing = form.value.scoringRows.length > 0 
    ? Math.max(...form.value.scoringRows.map(r => r.placing)) + 1 
    : 1
  form.value.scoringRows.push({ placing: nextPlacing, points: 0 })
}

const removeScoringRow = (index: number) => {
  form.value.scoringRows.splice(index, 1)
}

const submitForm = async () => {
  if (isSubmitting.value) return
  
  if (form.value.type !== 'judged') {
    const placings = form.value.scoringRows.map(r => r.placing)
    const uniquePlacings = new Set(placings)
    if (placings.length !== uniquePlacings.size) {
      errorMessage.value = 'อันดับต้องไม่ซ้ำกัน'
      return
    }
  }

  isSubmitting.value = true
  errorMessage.value = ''
  
  try {
    const payload: any = {
      name: form.value.name,
      type: form.value.type,
      display_order: form.value.display_order
    }
    
    if (form.value.type === 'judged') {
      payload.max_score = form.value.max_score
      payload.scoring_table = null
    } else {
      payload.max_score = null
      const table: Record<string, number> = {}
      for (const row of form.value.scoringRows) {
        table[row.placing.toString()] = row.points
      }
      payload.scoring_table = table
    }

    if (editingId.value) {
      await updateDiscipline(props.academyId, props.editionId, editingId.value, payload)
    } else {
      await createDiscipline(props.academyId, props.editionId, payload)
    }
    
    emit('refresh')
    closeForm()
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'บันทึกไม่สำเร็จ'
  } finally {
    isSubmitting.value = false
  }
}

const handleDelete = async (d: SportsDiscipline) => {
  if (confirm(`คุณต้องการลบรายการ "${d.name}" ใช่หรือไม่?\n\nคะแนนที่เคยให้ผ่านรายการนี้จะไม่ถูกลบ แต่จะไม่ผูกกับรายการอีกต่อไป`)) {
    try {
      await deleteDiscipline(props.academyId, props.editionId, d.id)
      emit('refresh')
    } catch (e: any) {
      alert(e?.data?.message || 'ลบไม่สำเร็จ')
    }
  }
}
</script>
