<script setup lang="ts">
/**
 * Academy Subjects Management
 * หน้าจัดการรายวิชา
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const academyId = ref<number | null>(null)
const isLoading = ref(true)
const isSaving = ref(false)

// Data
const subjects = ref<any[]>([])
const subjectGroups = ref<any[]>([])

// Filters
const selectedType = ref<string>('')
const selectedGroup = ref<string>('')
const searchQuery = ref('')

// Modal
const showModal = ref(false)
const editingSubject = ref<any>(null)
const form = ref({
  subject_code: '',
  name_th: '',
  name_en: '',
  description: '',
  credits: 1,
  hours_per_week: 2,
  subject_type: 'required',
  subject_group: '',
  grade_levels: [] as string[],
})

const filteredSubjects = computed(() => {
  let result = subjects.value

  if (selectedType.value) {
    result = result.filter((s: any) => s.subject_type === selectedType.value)
  }

  if (selectedGroup.value) {
    result = result.filter((s: any) => s.subject_group === selectedGroup.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter((s: any) =>
      s.name_th?.toLowerCase().includes(query) ||
      s.name_en?.toLowerCase().includes(query) ||
      s.subject_code?.toLowerCase().includes(query)
    )
  }

  return result
})

const { isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await fetchData()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchData = async () => {
  await Promise.all([
    fetchSubjects(),
    fetchSubjectGroups(),
  ])
}

const fetchSubjects = async () => {
  try {
    const params = new URLSearchParams()
    if (selectedType.value) params.append('type', selectedType.value)
    if (selectedGroup.value) params.append('group', selectedGroup.value)
    
    const res: any = await api.get(`/api/academies/${academyId.value}/subjects?${params}`)
    if (res.success) {
      subjects.value = res.subjects || []
    }
  } catch (err) {
    console.error('Failed to fetch subjects:', err)
  }
}

const fetchSubjectGroups = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/subjects/groups`)
    if (res.success) {
      subjectGroups.value = res.groups || []
    }
  } catch (err) {
    console.error('Failed to fetch subject groups:', err)
  }
}

const openCreateModal = () => {
  editingSubject.value = null
  form.value = {
    subject_code: '',
    name_th: '',
    name_en: '',
    description: '',
    credits: 1,
    hours_per_week: 2,
    subject_type: 'required',
    subject_group: '',
    grade_levels: [],
  }
  showModal.value = true
}

const openEditModal = (subject: any) => {
  editingSubject.value = subject
  form.value = {
    subject_code: subject.subject_code || '',
    name_th: subject.name_th,
    name_en: subject.name_en || '',
    description: subject.description || '',
    credits: subject.credits || 1,
    hours_per_week: subject.hours_per_week || 2,
    subject_type: subject.subject_type || 'required',
    subject_group: subject.subject_group || '',
    grade_levels: subject.grade_levels || [],
  }
  showModal.value = true
}

const saveSubject = async () => {
  if (!form.value.name_th) {
    alert('กรุณากรอกชื่อวิชา')
    return
  }

  isSaving.value = true
  try {
    if (editingSubject.value) {
      await api.put(`/api/academies/${academyId.value}/subjects/${editingSubject.value.id}`, form.value)
    } else {
      await api.post(`/api/academies/${academyId.value}/subjects`, form.value)
    }
    
    showModal.value = false
    await fetchSubjects()
  } catch (err: any) {
    console.error('Failed to save:', err)
    alert(err.message || 'เกิดข้อผิดพลาด')
  } finally {
    isSaving.value = false
  }
}

const deleteSubject = async (subject: any) => {
  if (!confirm(`ต้องการลบวิชา "${subject.name_th}" หรือไม่?`)) return
  
  try {
    await api.delete(`/api/academies/${academyId.value}/subjects/${subject.id}`)
    await fetchSubjects()
  } catch (err) {
    console.error('Failed to delete:', err)
    alert('ไม่สามารถลบได้')
  }
}

const subjectTypes = [
  { value: 'required', label: 'วิชาบังคับ', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-400' },
  { value: 'elective', label: 'วิชาเลือก', color: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400' },
  { value: 'activity', label: 'กิจกรรม', color: 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-400' },
]

const commonGradeLevels = [
  'ป.1', 'ป.2', 'ป.3', 'ป.4', 'ป.5', 'ป.6',
  'ม.1', 'ม.2', 'ม.3', 'ม.4', 'ม.5', 'ม.6',
]

const getTypeInfo = (type: string) => {
  return subjectTypes.find(t => t.value === type) || { label: type, color: 'bg-gray-100 text-gray-800' }
}

const toggleGradeLevel = (level: string) => {
  const index = form.value.grade_levels.indexOf(level)
  if (index === -1) {
    form.value.grade_levels.push(level)
  } else {
    form.value.grade_levels.splice(index, 1)
  }
}
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <NuxtLink :to="`/academies/${academyName}/admin/gradebook`" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
              <Icon icon="fluent:arrow-left-24-filled" class="w-5 h-5" />
            </NuxtLink>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
              <Icon icon="fluent:book-24-filled" class="w-7 h-7 text-green-500" />
              จัดการรายวิชา
            </h1>
          </div>
          <p class="text-gray-600 dark:text-gray-400">สร้างและจัดการรายวิชาในหลักสูตร</p>
        </div>

        <button
          @click="openCreateModal"
          class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors flex items-center gap-2"
        >
          <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
          เพิ่มรายวิชา
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <div class="relative">
              <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="ค้นหารายวิชา..."
                class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
          </div>
          
          <select
            v-model="selectedType"
            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          >
            <option value="">ทุกประเภท</option>
            <option v-for="type in subjectTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
          </select>
          
          <select
            v-model="selectedGroup"
            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          >
            <option value="">ทุกกลุ่มสาระ</option>
            <option v-for="group in subjectGroups" :key="group" :value="group">{{ group }}</option>
          </select>
        </div>
      </div>

      <!-- Subjects Grid -->
      <div v-if="filteredSubjects.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-sm border border-gray-200 dark:border-gray-700">
        <Icon icon="fluent:book-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีรายวิชา</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">เริ่มต้นด้วยการสร้างรายวิชาใหม่</p>
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors"
        >
          <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
          สร้างรายวิชา
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="subject in filteredSubjects"
          :key="subject.id"
          class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-lg bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                <Icon icon="fluent:book-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ subject.name_th }}</h3>
                <p v-if="subject.subject_code" class="text-sm text-gray-600 dark:text-gray-400">{{ subject.subject_code }}</p>
              </div>
            </div>
            
            <div class="flex items-center gap-1">
              <button
                @click="openEditModal(subject)"
                class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-gray-500 hover:text-primary-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
              >
                <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
              </button>
              <button
                @click="deleteSubject(subject)"
                class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-gray-500 hover:text-red-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
              >
                <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
              </button>
            </div>
          </div>

          <div class="space-y-2 text-sm mb-4">
            <div class="flex items-center justify-between text-gray-600 dark:text-gray-400">
              <span>หน่วยกิต</span>
              <span class="font-medium text-gray-900 dark:text-white">{{ subject.credits }}</span>
            </div>
            <div class="flex items-center justify-between text-gray-600 dark:text-gray-400">
              <span>ชั่วโมง/สัปดาห์</span>
              <span class="font-medium text-gray-900 dark:text-white">{{ subject.hours_per_week }}</span>
            </div>
            <div v-if="subject.subject_group" class="flex items-center justify-between text-gray-600 dark:text-gray-400">
              <span>กลุ่มสาระ</span>
              <span class="font-medium text-gray-900 dark:text-white text-right text-xs">{{ subject.subject_group }}</span>
            </div>
          </div>

          <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
            <span :class="['px-2 py-1 text-xs font-medium rounded-full', getTypeInfo(subject.subject_type).color]">
              {{ getTypeInfo(subject.subject_type).label }}
            </span>
            <div v-if="subject.grade_levels?.length" class="text-xs text-gray-500 dark:text-gray-400">
              {{ subject.grade_levels.slice(0, 3).join(', ') }}{{ subject.grade_levels.length > 3 ? '...' : '' }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="showModal = false"></div>
        
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
          <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
              {{ editingSubject ? 'แก้ไขรายวิชา' : 'เพิ่มรายวิชาใหม่' }}
            </h2>
          </div>
          
          <form @submit.prevent="saveSubject" class="p-6 space-y-4">
            <div class="grid grid-cols-3 gap-4">
              <div class="col-span-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รหัสวิชา</label>
                <input
                  v-model="form.subject_code"
                  type="text"
                  placeholder="เช่น ท11101"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
              <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อวิชา (ภาษาไทย) *</label>
                <input
                  v-model="form.name_th"
                  type="text"
                  required
                  placeholder="เช่น ภาษาไทย"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อวิชา (ภาษาอังกฤษ)</label>
              <input
                v-model="form.name_en"
                type="text"
                placeholder="เช่น Thai Language"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หน่วยกิต</label>
                <input
                  v-model.number="form.credits"
                  type="number"
                  min="0"
                  step="0.5"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชั่วโมง/สัปดาห์</label>
                <input
                  v-model.number="form.hours_per_week"
                  type="number"
                  min="0"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภทวิชา</label>
                <select
                  v-model="form.subject_type"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                  <option v-for="type in subjectTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">กลุ่มสาระ</label>
                <select
                  v-model="form.subject_group"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                  <option value="">ไม่ระบุ</option>
                  <option v-for="group in subjectGroups" :key="group" :value="group">{{ group }}</option>
                </select>
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ระดับชั้นที่เรียน</label>
              <div class="flex flex-wrap gap-2">
                <button class="min-h-[44px] sm:min-h-0"
                  v-for="level in commonGradeLevels"
                  :key="level"
                  type="button"
                  @click="toggleGradeLevel(level)"
                  :class="[
                    'px-3 py-1.5 text-sm rounded-lg border transition-colors',
                    form.grade_levels.includes(level)
                      ? 'bg-primary-500 text-white border-primary-500'
                      : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-primary-400'
                  ]"
                >
                  {{ level }}
                </button>
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
              <textarea
                v-model="form.description"
                rows="2"
                placeholder="คำอธิบายรายวิชา..."
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              ></textarea>
            </div>
            
            <div class="flex items-center gap-3 pt-4">
              <button
                type="button"
                @click="showModal = false"
                class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                ยกเลิก
              </button>
              <button
                type="submit"
                :disabled="isSaving"
                class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2 bg-primary-500 hover:bg-primary-600 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
              >
                <Icon v-if="isSaving" icon="fluent:spinner-ios-20-filled" class="w-5 h-5 animate-spin" />
                {{ isSaving ? 'กำลังบันทึก...' : 'บันทึก' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
