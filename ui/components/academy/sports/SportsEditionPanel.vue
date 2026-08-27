<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { ref, computed, watch, onMounted } from 'vue'
import type { SportsEdition } from '~/composables/useHouseAssignments'
import { useHouseAssignments } from '~/composables/useHouseAssignments'

const props = defineProps<{
  academyId: number
  academyName: string
  houseGroups: any[]
  modelValue: number | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', val: number | null): void
  /** ชุดคณะสีของครั้งที่เลือก — หน้าที่ใช้ component นี้ต้องรู้จากตรงนี้ ไม่ใช่จากยอดนับ */
  (e: 'update:houseIds', val: number[]): void
  (e: 'changed'): void
}>()

const houses = useHouseAssignments()
const editions = ref<SportsEdition[]>([])
const academicYears = ref<any[]>([])

const isLoading = ref(true)
const isWorking = ref(false)
const errorMessage = ref('')
const isFormOpen = ref(false)
const editingEdition = ref<SportsEdition | null>(null)

const form = ref({
  name: '',
  academic_year_id: null as number | null,
  sequence: null as number | null,
  starts_on: '',
  ends_on: '',
  house_group_ids: [] as number[]
})

const selectedEditionId = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const selectedEdition = computed(() => {
  return editions.value.find(e => e.id === selectedEditionId.value) || null
})

/** ชุดคณะสีของครั้งที่แบ่งนักเรียนไปแล้วย้ายไม่ได้ — ทั้งช่องติ๊กและ payload ต้องเคารพข้อนี้ */
const housesLocked = computed(() => !!(editingEdition.value && editingEdition.value.students_count))

watch(
  selectedEdition,
  (edition) => emit('update:houseIds', edition ? houses.editionHouseIds(edition) : []),
  { immediate: true },
)

const loadData = async () => {
  try {
    isLoading.value = true
    const [editionsRes, yearsRes] = await Promise.all([
      houses.listEditions(props.academyId),
      houses.listAcademicYears(props.academyId)
    ])
    editions.value = (editionsRes as any)?.data || (editionsRes as any) || [] // Sometimes array is direct
    // Handle both wrapped and unwrapped array
    if (!Array.isArray(editions.value)) {
      editions.value = []
    }
    academicYears.value = (yearsRes as any)?.academicYears || []
    
    // Auto-select first active or latest if nothing selected
    if (!selectedEditionId.value && editions.value.length > 0) {
      const active = editions.value.find(e => e.status === 'active')
      selectedEditionId.value = active ? active.id : editions.value[0].id
    }
  } catch (e) {
    console.error('Failed to load editions', e)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  if (props.academyId) loadData()
})

watch(() => props.academyId, () => {
  if (props.academyId) loadData()
})

const yearName = (id: number) => {
  return academicYears.value.find(y => y.id === id)?.name || id
}

const openCreateForm = () => {
  editingEdition.value = null
  const currentYear = academicYears.value.find(y => y.is_current) || academicYears.value[0]
  form.value = {
    name: '',
    academic_year_id: currentYear?.id || null,
    sequence: null,
    starts_on: '',
    ends_on: '',
    house_group_ids: []
  }
  errorMessage.value = ''
  isFormOpen.value = true
}

const openEditForm = (edition: SportsEdition) => {
  editingEdition.value = edition
  form.value = {
    name: edition.name,
    academic_year_id: edition.academic_year_id,
    sequence: edition.sequence,
    starts_on: edition.starts_on || '',
    ends_on: edition.ends_on || '',
    house_group_ids: houses.editionHouseIds(edition)
  }
  errorMessage.value = ''
  isFormOpen.value = true
}

const closeForm = () => {
  isFormOpen.value = false
}

const saveForm = async () => {
  if (!form.value.name || !form.value.academic_year_id) return
  isWorking.value = true
  errorMessage.value = ''
  try {
    const payload: Record<string, any> = {
      name: form.value.name,
      academic_year_id: form.value.academic_year_id,
      sequence: form.value.sequence || undefined,
      starts_on: form.value.starts_on || undefined,
      ends_on: form.value.ends_on || undefined,
    }

    // ส่ง house_group_ids เฉพาะตอนที่แก้ชุดคณะสีได้จริง — API ปฏิเสธ 422 ทันทีที่คีย์นี้
    // ไม่ใช่ null และครั้งนั้นมีนักเรียนแล้ว ไม่ว่าค่าจะเปลี่ยนหรือไม่ ถ้าส่งไปด้วยเสมอ
    // การแก้แค่ชื่อหรือวันที่ของครั้งที่แบ่งนักเรียนไปแล้วจะพังทุกครั้งด้วยข้อความเรื่องคณะสี
    if (!housesLocked.value) {
      payload.house_group_ids = form.value.house_group_ids
    }
    
    if (editingEdition.value) {
      await houses.updateEdition(props.academyId, editingEdition.value.id, payload)
    } else {
      const res = await houses.createEdition(props.academyId, payload) as any
      if (res && res.id) {
        selectedEditionId.value = res.id
      }
    }
    await loadData()
    emit('changed')
    closeForm()
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'บันทึกไม่สำเร็จ'
  } finally {
    isWorking.value = false
  }
}

const activateEdition = async (edition: SportsEdition) => {
  if (houses.editionHouseIds(edition).length === 0) {
    if (!confirm('ครั้งนี้ยังไม่ได้เลือกคณะสี — สมาชิกคณะสีที่แสดงอยู่จะถูกล้าง')) {
      return
    }
  }
  isWorking.value = true
  errorMessage.value = ''
  try {
    await houses.activateEdition(props.academyId, edition.id)
    await loadData()
    emit('changed')
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'ดำเนินการไม่สำเร็จ'
  } finally {
    isWorking.value = false
  }
}

const closeEdition = async (edition: SportsEdition) => {
  isWorking.value = true
  errorMessage.value = ''
  try {
    await houses.closeEdition(props.academyId, edition.id)
    await loadData()
    emit('changed')
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'ดำเนินการไม่สำเร็จ'
  } finally {
    isWorking.value = false
  }
}

const deleteEdition = async (edition: SportsEdition) => {
  isWorking.value = true
  errorMessage.value = ''
  try {
    await houses.deleteEdition(props.academyId, edition.id)
    if (selectedEditionId.value === edition.id) {
      selectedEditionId.value = null
    }
    await loadData()
    emit('changed')
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'ลบไม่สำเร็จ'
  } finally {
    isWorking.value = false
  }
}
</script>

<template>
  <div class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-5 mb-6">
    <div v-if="isLoading" class="animate-pulse space-y-4">
      <div class="h-10 bg-slate-200 dark:bg-slate-700 rounded w-1/2"></div>
    </div>
    
    <div v-else-if="!isFormOpen">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
        <div>
          <h2 class="font-heading font-bold text-slate-900 dark:text-white">งานกีฬาสี</h2>
          <p class="text-sm text-slate-500 dark:text-slate-400">เลือกครั้งที่จัดงานเพื่อดูข้อมูลการแบ่งนักเรียน</p>
        </div>
        <button
          @click="openCreateForm"
          class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg transition-all flex items-center justify-center gap-2"
        >
          <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
          สร้างครั้งใหม่
        </button>
      </div>
      
      <div v-if="errorMessage" class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-vikinger p-4 mb-4 flex items-center gap-3">
        <Icon icon="fluent:error-circle-24-filled" class="w-5 h-5 text-rose-600 dark:text-rose-400" />
        <p class="text-sm text-rose-800 dark:text-rose-300">{{ errorMessage }}</p>
      </div>
      
      <div v-if="editions.length === 0" class="text-center py-6 text-slate-500 dark:text-slate-400">
        ยังไม่มีข้อมูลงานกีฬาสี
      </div>
      
      <div v-else class="space-y-3">
        <select
          v-model="selectedEditionId"
          class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none mb-4"
        >
          <option v-for="ed in editions" :key="ed.id" :value="ed.id">
            {{ ed.name }} (ปีการศึกษา {{ yearName(ed.academic_year_id) }} ครั้งที่ {{ ed.sequence || '-' }})
          </option>
        </select>
        
        <div v-if="selectedEdition" class="p-4 rounded-vikinger border border-slate-200 dark:border-slate-700">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <div class="flex items-center gap-2">
                <h3 class="font-heading font-bold text-lg text-slate-900 dark:text-white">{{ selectedEdition.name }}</h3>
                <span
                  class="px-2 py-0.5 rounded-full text-xs font-semibold"
                  :class="{
                    'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300': selectedEdition.status === 'draft',
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300': selectedEdition.status === 'active',
                    'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300': selectedEdition.status === 'closed'
                  }"
                >
                  {{ selectedEdition.status === 'draft' ? 'ร่าง' : selectedEdition.status === 'active' ? 'กำลังใช้งาน' : 'ปิดแล้ว' }}
                </span>
              </div>
              <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                ปีการศึกษา {{ yearName(selectedEdition.academic_year_id) }} · ครั้งที่ {{ selectedEdition.sequence || '-' }} · 
                <span v-if="selectedEdition.students_count !== undefined">นักเรียน {{ selectedEdition.students_count }} คน</span>
              </p>
            </div>
            
            <div class="flex items-center flex-wrap gap-2">
              <button
                @click="openEditForm(selectedEdition)"
                class="min-h-[44px] sm:min-h-0 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition-all"
              >
                แก้ไข
              </button>
              
              <button
                v-if="selectedEdition.status !== 'active'"
                @click="activateEdition(selectedEdition)"
                :disabled="isWorking"
                class="min-h-[44px] sm:min-h-0 px-3 py-1.5 rounded-lg border border-emerald-300 dark:border-emerald-700 text-sm font-medium hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 transition-all"
              >
                ใช้ครั้งนี้
              </button>
              
              <button
                v-if="selectedEdition.status === 'active'"
                @click="closeEdition(selectedEdition)"
                :disabled="isWorking"
                class="min-h-[44px] sm:min-h-0 px-3 py-1.5 rounded-lg border border-rose-300 dark:border-rose-700 text-sm font-medium hover:bg-rose-50 dark:hover:bg-rose-900/20 text-rose-700 dark:text-rose-300 transition-all"
              >
                ปิด
              </button>
              
              <button
                v-if="!selectedEdition.students_count"
                @click="deleteEdition(selectedEdition)"
                :disabled="isWorking"
                class="min-h-[44px] sm:min-h-0 px-3 py-1.5 rounded-lg text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 text-sm font-medium transition-all"
              >
                ลบ
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else>
      <div class="flex items-center justify-between mb-4 border-b border-slate-200 dark:border-slate-700 pb-4">
        <h2 class="font-heading font-bold text-lg text-slate-900 dark:text-white">
          {{ editingEdition ? 'แก้ไขงานกีฬาสี' : 'สร้างงานกีฬาสีครั้งใหม่' }}
        </h2>
        <button @click="closeForm" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
          <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
        </button>
      </div>
      
      <div v-if="errorMessage" class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-vikinger p-4 mb-4 flex items-center gap-3">
        <Icon icon="fluent:error-circle-24-filled" class="w-5 h-5 text-rose-600 dark:text-rose-400" />
        <p class="text-sm text-rose-800 dark:text-rose-300">{{ errorMessage }}</p>
      </div>
      
      <div class="space-y-4">
        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">ชื่องาน *</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
              placeholder="เช่น กีฬาสีประจำปี 2569"
            />
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">ปีการศึกษา *</label>
            <select
              v-model="form.academic_year_id"
              class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
            >
              <option v-for="y in academicYears" :key="y.id" :value="y.id">
                {{ y.name }}{{ y.is_current ? ' (ปัจจุบัน)' : '' }}
              </option>
            </select>
          </div>
        </div>
        
        <div class="grid md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">ครั้งที่ (ปล่อยว่างให้ระบบนับให้)</label>
            <input
              v-model.number="form.sequence"
              type="number"
              class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
            />
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">วันเริ่ม</label>
            <input
              v-model="form.starts_on"
              type="date"
              class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
            />
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1">วันสิ้นสุด</label>
            <input
              v-model="form.ends_on"
              type="date"
              class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
            />
          </div>
        </div>
        
        <div class="pt-2">
          <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">คณะสีของครั้งนี้</label>
          <div v-if="housesLocked" class="mb-3 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-sm text-amber-800 dark:text-amber-300">
            <Icon icon="fluent:warning-24-filled" class="w-4 h-4 inline-block mr-1 -mt-0.5" />
            ไม่สามารถเปลี่ยนคณะสีได้เนื่องจากมีนักเรียนถูกแบ่งเข้าคณะสีแล้ว
          </div>
          <div class="flex flex-wrap gap-2">
            <label
              v-for="h in houseGroups"
              :key="h.id"
              class="flex items-center gap-2 px-4 py-2 rounded-vikinger border transition-all"
              :class="[
                form.house_group_ids.includes(Number(h.id))
                  ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                  : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800',
                housesLocked ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer hover:border-purple-300'
              ]"
            >
              <input
                v-model="form.house_group_ids"
                type="checkbox"
                :value="Number(h.id)"
                class="accent-purple-600"
                :disabled="housesLocked"
              />
              <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: h.settings?.color || '#8b5cf6' }" />
              <span class="text-sm font-medium text-slate-900 dark:text-white">{{ h.name }}</span>
            </label>
          </div>
        </div>
        
        <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700 mt-6">
          <button
            @click="closeForm"
            class="min-h-[44px] sm:min-h-0 px-5 py-2.5 rounded-vikinger border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-all"
          >
            ยกเลิก
          </button>
          <button
            @click="saveForm"
            :disabled="isWorking || !form.name || !form.academic_year_id"
            class="min-h-[44px] sm:min-h-0 px-6 py-2.5 bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <Icon v-if="isWorking" icon="fluent:spinner-ios-20-filled" class="w-5 h-5 animate-spin" />
            <Icon v-else icon="fluent:save-24-filled" class="w-5 h-5" />
            บันทึก
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
