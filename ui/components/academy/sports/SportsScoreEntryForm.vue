<template>
  <div class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-3 sm:p-6">
    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mb-4">ให้คะแนนคณะสี</h3>

    <div v-if="!canManage" class="text-center p-4 text-slate-500 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
      คุณไม่มีสิทธิ์ให้คะแนน
    </div>
    
    <div v-else-if="houses.length === 0" class="text-center p-4 text-slate-500 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
      ยังไม่มีคณะสีในครั้งนี้
    </div>

    <div v-else-if="disciplines.length === 0" class="text-center p-4 text-slate-500 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
      ต้องสร้างรายการแข่งก่อนจึงจะบันทึกคะแนนได้
    </div>

    <form v-else @submit.prevent="submitForm" class="space-y-5">
      <!-- 1. เลือกที่มาของคะแนน -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">ที่มาของคะแนน <span class="text-rose-500">*</span></label>
        <div class="flex flex-col sm:flex-row gap-2">
          <label class="flex-1 min-h-[44px] flex items-center p-3 border rounded-lg cursor-pointer transition-colors" :class="form.source === 'placing' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300' : 'border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300'">
            <input type="radio" v-model="form.source" value="placing" class="sr-only" @change="resetFieldsForSource" />
            <div class="flex flex-col">
              <span class="font-medium">บันทึกอันดับการแข่ง</span>
              <span class="text-xs opacity-70">ระบบแปลงอันดับเป็นคะแนนตามตารางของรายการนั้น</span>
            </div>
          </label>
          <label class="flex-1 min-h-[44px] flex items-center p-3 border rounded-lg cursor-pointer transition-colors" :class="form.source === 'judged' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300' : 'border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300'">
            <input type="radio" v-model="form.source" value="judged" class="sr-only" @change="resetFieldsForSource" />
            <div class="flex flex-col">
              <span class="font-medium">คะแนนกรรมการ</span>
              <span class="text-xs opacity-70">พาเหรด กองเชียร์ สแตนด์ ให้เป็นคะแนนตรง ๆ</span>
            </div>
          </label>
          <label class="flex-1 min-h-[44px] flex items-center p-3 border rounded-lg cursor-pointer transition-colors" :class="form.source === 'manual' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300' : 'border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300'">
            <input type="radio" v-model="form.source" value="manual" class="sr-only" @change="resetFieldsForSource" />
            <div class="flex flex-col">
              <span class="font-medium">ให้/หักด้วยมือ</span>
              <span class="text-xs opacity-70">ใส่ค่าติดลบเพื่อหักคะแนน</span>
            </div>
          </label>
        </div>
      </div>

      <!-- 2. เลือกคณะสี -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">คณะสี <span class="text-rose-500">*</span></label>
        <div class="flex flex-wrap gap-2">
          <button v-for="h in houses" :key="h.id" type="button" @click="form.house_group_id = h.id" class="min-h-[44px] px-4 py-2 rounded-full border transition-all flex items-center gap-2" :class="form.house_group_id === h.id ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 font-medium' : 'border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300'">
            <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: h.color }"></span>
            {{ h.name }}
          </button>
        </div>
      </div>

      <!-- 3. ตามที่มา -->
      <div v-if="form.source === 'placing'" class="space-y-4 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-lg border border-slate-200 dark:border-slate-700">
        <div>
          <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">รายการแข่ง (ทีม / เดี่ยว) <span class="text-rose-500">*</span></label>
          <select v-model="form.discipline_id" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none min-h-[44px]">
            <option :value="null" disabled>-- เลือกรายการ --</option>
            <option v-for="d in placingDisciplines" :key="d.id" :value="d.id">
              {{ d.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">อันดับที่ <span class="text-rose-500">*</span></label>
          <input v-model.number="form.placing" type="number" min="1" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none min-h-[44px]" placeholder="เช่น 1" />
          <div class="mt-2 text-sm">
            <div v-if="selectedDiscipline && form.placing" class="font-medium" :class="previewPoints === 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'">
              จะได้ {{ previewPoints }} คะแนน
              <span v-if="previewPoints === 0" class="block mt-1 text-xs font-normal">อันดับนี้ไม่มีในตารางคะแนนของรายการนี้ จะได้ 0 คะแนน</span>
            </div>
            <div class="mt-1 text-slate-500 dark:text-slate-400 text-xs">
              อันดับร่วมให้ใส่เลขอันดับเดียวกันได้ทุกคณะ
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="form.source === 'judged'" class="space-y-4 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-lg border border-slate-200 dark:border-slate-700">
        <div>
          <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">รายการแข่ง (กรรมการ) <span class="text-rose-500">*</span></label>
          <select v-model="form.discipline_id" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none min-h-[44px]">
            <option :value="null" disabled>-- เลือกรายการ --</option>
            <option v-for="d in judgedDisciplines" :key="d.id" :value="d.id">
              {{ d.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">คะแนนที่ได้ <span class="text-rose-500">*</span></label>
          <input v-model.number="form.points" type="number" step="0.01" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none min-h-[44px]" placeholder="คะแนน" />
          <div v-if="selectedDiscipline" class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            เต็ม {{ num(selectedDiscipline.max_score || DEFAULT_JUDGED_MAX_SCORE) }} คะแนน
            <span v-if="form.points && form.points > num(selectedDiscipline.max_score || DEFAULT_JUDGED_MAX_SCORE)" class="block mt-1 text-amber-600 dark:text-amber-400 text-xs">
              เตือน: คะแนนที่กรอกเกินคะแนนเต็ม
            </span>
          </div>
        </div>
      </div>

      <div v-else-if="form.source === 'manual'" class="space-y-4 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-lg border border-slate-200 dark:border-slate-700">
        <div>
          <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">คะแนน (ติดลบได้) <span class="text-rose-500">*</span></label>
          <input v-model.number="form.points" type="number" step="0.01" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none min-h-[44px]" placeholder="เช่น 5 หรือ -5" />
        </div>
      </div>

      <!-- 4. หมายเหตุ -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">
          หมายเหตุ <span v-if="form.source === 'manual'" class="text-rose-500">*</span>
        </label>
        <input v-model="form.note" type="text" maxlength="255" :required="form.source === 'manual'" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none min-h-[44px]" placeholder="อธิบายที่มาของคะแนน..." />
      </div>

      <!-- แถบแสดง error -->
      <div v-if="errorMessage" class="bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 p-3 sm:p-4 rounded-lg text-sm font-medium border border-rose-100 dark:border-rose-900/50">
        {{ errorMessage }}
      </div>
      
      <!-- แถบสำเร็จ -->
      <div v-if="successMessage" class="bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 p-3 sm:p-4 rounded-lg text-sm font-medium border border-emerald-100 dark:border-emerald-900/50">
        {{ successMessage }}
      </div>

      <button type="submit" :disabled="isSubmitting || !isFormValid" class="w-full bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed px-4 py-3 min-h-[44px] flex items-center justify-center gap-2">
        <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
        <Icon v-else icon="fluent:save-24-filled" class="w-5 h-5" />
        บันทึกคะแนน
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useSportsScoring, DEFAULT_JUDGED_MAX_SCORE } from '~/composables/useSportsScoring'
import type { SportsDiscipline, SportsScoreEntry, AwardEntryPayload } from '~/composables/useSportsScoring'

const props = defineProps<{
  academyId: number
  editionId: number
  disciplines: SportsDiscipline[]
  houses: { id: number; name: string; color: string }[]
  canManage: boolean
}>()

const emit = defineEmits<{
  (e: 'awarded', entry: SportsScoreEntry): void
}>()

// DEFAULT_JUDGED_MAX_SCORE เป็น export ระดับโมดูล ไม่ได้อยู่ในค่าที่ useSportsScoring() คืนมา
const {
  awardEntry,
  pointsForPlacing,
  num
} = useSportsScoring()

const form = ref({
  source: 'placing' as 'placing' | 'judged' | 'manual',
  house_group_id: null as number | null,
  discipline_id: null as number | null,
  placing: null as number | null,
  points: null as number | null,
  note: ''
})

const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const placingDisciplines = computed(() => props.disciplines.filter(d => d.type === 'team' || d.type === 'individual'))
const judgedDisciplines = computed(() => props.disciplines.filter(d => d.type === 'judged'))

const selectedDiscipline = computed(() => {
  if (!form.value.discipline_id) return null
  return props.disciplines.find(d => d.id === form.value.discipline_id) || null
})

const previewPoints = computed(() => {
  if (form.value.source === 'placing' && selectedDiscipline.value && form.value.placing) {
    return pointsForPlacing(selectedDiscipline.value, form.value.placing)
  }
  return 0
})

const isFormValid = computed(() => {
  if (!form.value.house_group_id) return false
  if (form.value.source === 'placing') {
    return !!form.value.discipline_id && !!form.value.placing
  }
  if (form.value.source === 'judged') {
    return !!form.value.discipline_id && form.value.points !== null
  }
  if (form.value.source === 'manual') {
    // คะแนนติดลบและศูนย์ต้องผ่าน จึงเช็ค null ตรง ๆ ห้ามใช้ falsy check
    return form.value.points !== null && !!form.value.note.trim()
  }
  return false
})

const resetFieldsForSource = () => {
  form.value.discipline_id = null
  form.value.placing = null
  form.value.points = null
  errorMessage.value = ''
  successMessage.value = ''
}

const submitForm = async () => {
  if (!isFormValid.value || isSubmitting.value) return
  
  isSubmitting.value = true
  errorMessage.value = ''
  successMessage.value = ''
  
  try {
    const payload: AwardEntryPayload = {
      house_group_id: form.value.house_group_id!,
      source: form.value.source,
      note: form.value.note || undefined
    }

    if (form.value.source === 'placing') {
      payload.discipline_id = form.value.discipline_id!
      payload.placing = form.value.placing!
    } else if (form.value.source === 'judged') {
      payload.discipline_id = form.value.discipline_id!
      payload.points = form.value.points!
    } else if (form.value.source === 'manual') {
      payload.points = form.value.points!
      // DO NOT set discipline_id for manual
    }

    const entry = await awardEntry(props.academyId, props.editionId, payload)
    
    // Find house name for success message
    const house = props.houses.find(h => h.id === form.value.house_group_id)
    const houseName = house ? house.name : 'คณะ'
    const pts = num(entry.points)
    const ptsStr = pts > 0 ? `+${pts}` : `${pts}`
    
    successMessage.value = `บันทึกแล้ว: ${houseName} ${ptsStr} คะแนน`
    
    emit('awarded', entry)
    
    // Reset specific fields
    form.value.house_group_id = null
    form.value.placing = null
    form.value.points = null
    form.value.note = ''
    
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'บันทึกไม่สำเร็จ'
  } finally {
    isSubmitting.value = false
  }
}
</script>
