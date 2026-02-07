<script setup lang="ts">
/**
 * Academy Grade Scales Management
 * หน้าจัดการเกณฑ์การให้เกรดและหมวดหมู่การประเมิน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: false
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const academyId = ref<number | null>(null)
const isLoading = ref(true)
const isSaving = ref(false)
const activeTab = ref<'scales' | 'categories'>('scales')

// Data
const gradeScales = ref<any[]>([])
const assessmentCategories = ref<any[]>([])

// Modal
const showScaleModal = ref(false)
const showCategoryModal = ref(false)
const editingScale = ref<any>(null)
const editingCategory = ref<any>(null)

const scaleForm = ref({
  name: '',
  is_default: false,
  items: [] as any[],
})

const categoryForm = ref({
  name: '',
  weight: 0,
  description: '',
})

const { isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${encodeURIComponent(academyName.value)}`)
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
    fetchGradeScales(),
    fetchAssessmentCategories(),
  ])
}

const fetchGradeScales = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/grade-scales`)
    if (res.success) {
      gradeScales.value = res.gradeScales || []
    }
  } catch (err) {
    console.error('Failed to fetch grade scales:', err)
  }
}

const fetchAssessmentCategories = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/assessment-categories`)
    if (res.success) {
      assessmentCategories.value = res.categories || []
    }
  } catch (err) {
    console.error('Failed to fetch assessment categories:', err)
  }
}

// Grade Scale Functions
const defaultScaleItems = [
  { letter_grade: 'A', grade_points: 4.0, min_score: 80, max_score: 100 },
  { letter_grade: 'B+', grade_points: 3.5, min_score: 75, max_score: 79 },
  { letter_grade: 'B', grade_points: 3.0, min_score: 70, max_score: 74 },
  { letter_grade: 'C+', grade_points: 2.5, min_score: 65, max_score: 69 },
  { letter_grade: 'C', grade_points: 2.0, min_score: 60, max_score: 64 },
  { letter_grade: 'D+', grade_points: 1.5, min_score: 55, max_score: 59 },
  { letter_grade: 'D', grade_points: 1.0, min_score: 50, max_score: 54 },
  { letter_grade: 'F', grade_points: 0.0, min_score: 0, max_score: 49 },
]

const openCreateScaleModal = () => {
  editingScale.value = null
  scaleForm.value = {
    name: '',
    is_default: false,
    items: [...defaultScaleItems],
  }
  showScaleModal.value = true
}

const openEditScaleModal = (scale: any) => {
  editingScale.value = scale
  scaleForm.value = {
    name: scale.name,
    is_default: scale.is_default || false,
    items: scale.items?.length ? [...scale.items] : [...defaultScaleItems],
  }
  showScaleModal.value = true
}

const addScaleItem = () => {
  scaleForm.value.items.push({
    letter_grade: '',
    grade_points: 0,
    min_score: 0,
    max_score: 0,
  })
}

const removeScaleItem = (index: number) => {
  scaleForm.value.items.splice(index, 1)
}

const saveGradeScale = async () => {
  if (!scaleForm.value.name) {
    alert('กรุณากรอกชื่อเกณฑ์การให้เกรด')
    return
  }

  isSaving.value = true
  try {
    if (editingScale.value) {
      await api.put(`/api/academies/${academyId.value}/grade-scales/${editingScale.value.id}`, scaleForm.value)
    } else {
      await api.post(`/api/academies/${academyId.value}/grade-scales`, scaleForm.value)
    }
    
    showScaleModal.value = false
    await fetchGradeScales()
  } catch (err: any) {
    console.error('Failed to save:', err)
    alert(err.message || 'เกิดข้อผิดพลาด')
  } finally {
    isSaving.value = false
  }
}

const deleteGradeScale = async (scale: any) => {
  if (!confirm(`ต้องการลบเกณฑ์ "${scale.name}" หรือไม่?`)) return
  
  try {
    await api.delete(`/api/academies/${academyId.value}/grade-scales/${scale.id}`)
    await fetchGradeScales()
  } catch (err) {
    console.error('Failed to delete:', err)
    alert('ไม่สามารถลบได้')
  }
}

const setDefaultScale = async (scale: any) => {
  try {
    await api.post(`/api/academies/${academyId.value}/grade-scales/${scale.id}/set-default`)
    await fetchGradeScales()
  } catch (err) {
    console.error('Failed to set default:', err)
  }
}

// Assessment Category Functions
const openCreateCategoryModal = () => {
  editingCategory.value = null
  categoryForm.value = {
    name: '',
    weight: 0,
    description: '',
  }
  showCategoryModal.value = true
}

const openEditCategoryModal = (category: any) => {
  editingCategory.value = category
  categoryForm.value = {
    name: category.name,
    weight: category.weight || 0,
    description: category.description || '',
  }
  showCategoryModal.value = true
}

const saveCategory = async () => {
  if (!categoryForm.value.name) {
    alert('กรุณากรอกชื่อหมวดหมู่')
    return
  }

  isSaving.value = true
  try {
    if (editingCategory.value) {
      await api.put(`/api/academies/${academyId.value}/assessment-categories/${editingCategory.value.id}`, categoryForm.value)
    } else {
      await api.post(`/api/academies/${academyId.value}/assessment-categories`, categoryForm.value)
    }
    
    showCategoryModal.value = false
    await fetchAssessmentCategories()
  } catch (err: any) {
    console.error('Failed to save:', err)
    alert(err.message || 'เกิดข้อผิดพลาด')
  } finally {
    isSaving.value = false
  }
}

const deleteCategory = async (category: any) => {
  if (!confirm(`ต้องการลบหมวดหมู่ "${category.name}" หรือไม่?`)) return
  
  try {
    await api.delete(`/api/academies/${academyId.value}/assessment-categories/${category.id}`)
    await fetchAssessmentCategories()
  } catch (err) {
    console.error('Failed to delete:', err)
    alert('ไม่สามารถลบได้')
  }
}

const createDefaultCategories = async () => {
  try {
    await api.post(`/api/academies/${academyId.value}/assessment-categories/create-defaults`)
    await fetchAssessmentCategories()
  } catch (err) {
    console.error('Failed to create defaults:', err)
  }
}

const totalCategoryWeight = computed(() => {
  return assessmentCategories.value.reduce((sum, c) => sum + (c.weight || 0), 0)
})

const getGradeColor = (grade: string) => {
  const colors: Record<string, string> = {
    'A': 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400',
    'B+': 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-400',
    'B': 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-400',
    'C+': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400',
    'C': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400',
    'D+': 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-400',
    'D': 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-400',
    'F': 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400',
  }
  return colors[grade] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
}
</script>

<template>
  <NuxtLayout name="academy-admin" :academy-name="academyName">
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
              <Icon icon="fluent:star-settings-24-filled" class="w-7 h-7 text-purple-500" />
              เกณฑ์การประเมิน
            </h1>
          </div>
          <p class="text-gray-600 dark:text-gray-400">จัดการเกณฑ์การให้เกรดและหมวดหมู่การประเมิน</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex gap-2">
        <button
          @click="activeTab = 'scales'"
          :class="[
            'px-4 py-2 rounded-lg font-medium transition-colors',
            activeTab === 'scales'
              ? 'bg-primary-500 text-white'
              : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
          ]"
        >
          <Icon icon="fluent:data-bar-vertical-24-regular" class="w-5 h-5 inline mr-2" />
          เกณฑ์การให้เกรด
        </button>
        <button
          @click="activeTab = 'categories'"
          :class="[
            'px-4 py-2 rounded-lg font-medium transition-colors',
            activeTab === 'categories'
              ? 'bg-primary-500 text-white'
              : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
          ]"
        >
          <Icon icon="fluent:folder-24-regular" class="w-5 h-5 inline mr-2" />
          หมวดหมู่การประเมิน
        </button>
      </div>

      <!-- Grade Scales Tab -->
      <div v-if="activeTab === 'scales'" class="space-y-4">
        <div class="flex justify-end">
          <button
            @click="openCreateScaleModal"
            class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors flex items-center gap-2"
          >
            <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
            สร้างเกณฑ์ใหม่
          </button>
        </div>

        <div v-if="gradeScales.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-sm border border-gray-200 dark:border-gray-700">
          <Icon icon="fluent:data-bar-vertical-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีเกณฑ์การให้เกรด</h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">สร้างเกณฑ์การให้เกรดสำหรับสถาบัน</p>
          <button
            @click="openCreateScaleModal"
            class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors"
          >
            <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
            สร้างเกณฑ์
          </button>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="scale in gradeScales"
            :key="scale.id"
            class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
          >
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-3">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ scale.name }}</h3>
                <span v-if="scale.is_default" class="px-2 py-0.5 text-xs font-medium rounded-full bg-primary-100 text-primary-800 dark:bg-primary-900/50 dark:text-primary-400">
                  ค่าเริ่มต้น
                </span>
              </div>
              <div class="flex items-center gap-2">
                <button
                  v-if="!scale.is_default"
                  @click="setDefaultScale(scale)"
                  class="px-3 py-1.5 text-sm text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/30 rounded-lg transition-colors"
                >
                  ตั้งเป็นค่าเริ่มต้น
                </button>
                <button
                  @click="openEditScaleModal(scale)"
                  class="p-2 text-gray-500 hover:text-primary-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                >
                  <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
                </button>
                <button
                  v-if="!scale.is_default"
                  @click="deleteGradeScale(scale)"
                  class="p-2 text-gray-500 hover:text-red-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                >
                  <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
                </button>
              </div>
            </div>

            <div class="flex flex-wrap gap-3">
              <div
                v-for="item in scale.items"
                :key="item.id || item.letter_grade"
                :class="['px-4 py-2 rounded-lg text-sm', getGradeColor(item.letter_grade)]"
              >
                <span class="font-bold">{{ item.letter_grade }}</span>
                <span class="mx-2">=</span>
                <span>{{ item.grade_points }}</span>
                <span class="text-xs opacity-75 ml-2">({{ item.min_score }}-{{ item.max_score }}%)</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Assessment Categories Tab -->
      <div v-if="activeTab === 'categories'" class="space-y-4">
        <div class="flex justify-between items-center">
          <div v-if="assessmentCategories.length > 0" class="text-sm text-gray-600 dark:text-gray-400">
            น้ำหนักรวม: 
            <span :class="totalCategoryWeight === 100 ? 'text-green-600 font-bold' : 'text-orange-600 font-bold'">
              {{ totalCategoryWeight }}%
            </span>
            <span v-if="totalCategoryWeight !== 100" class="text-orange-600"> (ควรรวมเป็น 100%)</span>
          </div>
          <div v-else></div>
          <div class="flex gap-2">
            <button
              v-if="assessmentCategories.length === 0"
              @click="createDefaultCategories"
              class="px-4 py-2 border border-primary-500 text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/30 rounded-lg font-medium transition-colors flex items-center gap-2"
            >
              <Icon icon="fluent:flash-24-filled" class="w-5 h-5" />
              สร้างหมวดหมู่พื้นฐาน
            </button>
            <button
              @click="openCreateCategoryModal"
              class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors flex items-center gap-2"
            >
              <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
              เพิ่มหมวดหมู่
            </button>
          </div>
        </div>

        <div v-if="assessmentCategories.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-sm border border-gray-200 dark:border-gray-700">
          <Icon icon="fluent:folder-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีหมวดหมู่การประเมิน</h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">สร้างหมวดหมู่สำหรับจัดประเภทการประเมิน เช่น การสอบกลางภาค การสอบปลายภาค</p>
          <button
            @click="createDefaultCategories"
            class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors"
          >
            <Icon icon="fluent:flash-24-filled" class="w-5 h-5" />
            สร้างหมวดหมู่พื้นฐาน
          </button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="category in assessmentCategories"
            :key="category.id"
            class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700"
          >
            <div class="flex items-start justify-between mb-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                  <Icon icon="fluent:folder-24-filled" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                  <h4 class="font-medium text-gray-900 dark:text-white">{{ category.name }}</h4>
                  <p v-if="category.description" class="text-xs text-gray-500 dark:text-gray-400">{{ category.description }}</p>
                </div>
              </div>
              <div class="flex gap-1">
                <button
                  @click="openEditCategoryModal(category)"
                  class="p-1.5 text-gray-500 hover:text-primary-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
                >
                  <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
                </button>
                <button
                  @click="deleteCategory(category)"
                  class="p-1.5 text-gray-500 hover:text-red-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
                >
                  <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
                </button>
              </div>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-gray-600 dark:text-gray-400 text-sm">น้ำหนัก</span>
              <span class="text-lg font-bold text-primary-600 dark:text-primary-400">{{ category.weight }}%</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Grade Scale Modal -->
    <Teleport to="body">
      <div v-if="showScaleModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="showScaleModal = false"></div>
        
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
          <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
              {{ editingScale ? 'แก้ไขเกณฑ์การให้เกรด' : 'สร้างเกณฑ์การให้เกรดใหม่' }}
            </h2>
          </div>
          
          <form @submit.prevent="saveGradeScale" class="p-6 space-y-4">
            <div class="flex gap-4">
              <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อเกณฑ์ *</label>
                <input
                  v-model="scaleForm.name"
                  type="text"
                  required
                  placeholder="เช่น เกณฑ์มาตรฐาน 8 ระดับ"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
              <div class="flex items-end">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input
                    v-model="scaleForm.is_default"
                    type="checkbox"
                    class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                  />
                  <span class="text-sm text-gray-700 dark:text-gray-300">ตั้งเป็นค่าเริ่มต้น</span>
                </label>
              </div>
            </div>
            
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">รายละเอียดเกรด</label>
                <button
                  type="button"
                  @click="addScaleItem"
                  class="text-sm text-primary-600 hover:text-primary-700 flex items-center gap-1"
                >
                  <Icon icon="fluent:add-24-filled" class="w-4 h-4" />
                  เพิ่มเกรด
                </button>
              </div>
              
              <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <table class="w-full">
                  <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                      <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">เกรด</th>
                      <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">คะแนน</th>
                      <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">คะแนนต่ำสุด</th>
                      <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">คะแนนสูงสุด</th>
                      <th class="px-4 py-2"></th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="(item, index) in scaleForm.items" :key="index">
                      <td class="px-4 py-2">
                        <input
                          v-model="item.letter_grade"
                          type="text"
                          class="w-16 px-2 py-1 text-center rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm"
                          placeholder="A"
                        />
                      </td>
                      <td class="px-4 py-2">
                        <input
                          v-model.number="item.grade_points"
                          type="number"
                          step="0.5"
                          min="0"
                          max="4"
                          class="w-20 px-2 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm"
                        />
                      </td>
                      <td class="px-4 py-2">
                        <input
                          v-model.number="item.min_score"
                          type="number"
                          min="0"
                          max="100"
                          class="w-20 px-2 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm"
                        />
                      </td>
                      <td class="px-4 py-2">
                        <input
                          v-model.number="item.max_score"
                          type="number"
                          min="0"
                          max="100"
                          class="w-20 px-2 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm"
                        />
                      </td>
                      <td class="px-4 py-2">
                        <button
                          type="button"
                          @click="removeScaleItem(index)"
                          class="p-1 text-gray-400 hover:text-red-500"
                        >
                          <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            
            <div class="flex items-center gap-3 pt-4">
              <button
                type="button"
                @click="showScaleModal = false"
                class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                ยกเลิก
              </button>
              <button
                type="submit"
                :disabled="isSaving"
                class="flex-1 px-4 py-2 bg-primary-500 hover:bg-primary-600 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
              >
                <Icon v-if="isSaving" icon="fluent:spinner-ios-20-filled" class="w-5 h-5 animate-spin" />
                {{ isSaving ? 'กำลังบันทึก...' : 'บันทึก' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Category Modal -->
    <Teleport to="body">
      <div v-if="showCategoryModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="showCategoryModal = false"></div>
        
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md mx-4">
          <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
              {{ editingCategory ? 'แก้ไขหมวดหมู่' : 'เพิ่มหมวดหมู่ใหม่' }}
            </h2>
          </div>
          
          <form @submit.prevent="saveCategory" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อหมวดหมู่ *</label>
              <input
                v-model="categoryForm.name"
                type="text"
                required
                placeholder="เช่น การสอบกลางภาค"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">น้ำหนัก (%)</label>
              <input
                v-model.number="categoryForm.weight"
                type="number"
                min="0"
                max="100"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
              <textarea
                v-model="categoryForm.description"
                rows="2"
                placeholder="คำอธิบายเพิ่มเติม..."
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              ></textarea>
            </div>
            
            <div class="flex items-center gap-3 pt-4">
              <button
                type="button"
                @click="showCategoryModal = false"
                class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                ยกเลิก
              </button>
              <button
                type="submit"
                :disabled="isSaving"
                class="flex-1 px-4 py-2 bg-primary-500 hover:bg-primary-600 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
              >
                <Icon v-if="isSaving" icon="fluent:spinner-ios-20-filled" class="w-5 h-5 animate-spin" />
                {{ isSaving ? 'กำลังบันทึก...' : 'บันทึก' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </NuxtLayout>
</template>
