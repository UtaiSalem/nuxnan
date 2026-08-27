<script setup lang="ts">
/**
 * Academy Admin - Home Visit Zones
 * หน้าจัดการโซนเยี่ยมบ้าน
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
const zones = ref<any[]>([])
const isLoading = ref(true)
const isSaving = ref(false)

// Modal
const showModal = ref(false)
const editingZone = ref<any>(null)
const showDeleteModal = ref(false)
const deletingZone = ref<any>(null)

// Form
const form = ref({
  name: '',
  description: '',
  color: '#3B82F6'
})

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value && !can('home_visits.manage')) {
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
  if (!academyId.value) return
  try {
    const zonesRes: any = await api.get(`/api/academies/${academyId.value}/home-visits/zones`)
    zones.value = zonesRes.zones || zonesRes.data || []
  } catch (err) {
    console.error('Failed to fetch data:', err)
  }
}

const openCreateModal = () => {
  editingZone.value = null
  form.value = {
    name: '',
    description: '',
    color: '#3B82F6'
  }
  showModal.value = true
}

const openEditModal = (zone: any) => {
  editingZone.value = zone
  form.value = {
    name: zone.zone_name || '',
    description: zone.description || '',
    color: zone.color || '#3B82F6'
  }
  showModal.value = true
}

const saveZone = async () => {
  if (!form.value.name.trim() || !academyId.value) return
  
  isSaving.value = true
  try {
    const payload = {
      zone_name: form.value.name,
      description: form.value.description,
      color: form.value.color
    }
    if (editingZone.value) {
      await api.put(`/api/academies/${academyId.value}/home-visits/zones/${editingZone.value.id}`, payload)
    } else {
      await api.post(`/api/academies/${academyId.value}/home-visits/zones`, payload)
    }
    showModal.value = false
    await fetchData()
  } catch (err) {
    console.error('Failed to save zone:', err)
  } finally {
    isSaving.value = false
  }
}

const confirmDelete = (zone: any) => {
  deletingZone.value = zone
  showDeleteModal.value = true
}

const deleteZone = async () => {
  if (!deletingZone.value || !academyId.value) return
  
  try {
    await api.delete(`/api/academies/${academyId.value}/home-visits/zones/${deletingZone.value.id}`)
    showDeleteModal.value = false
    deletingZone.value = null
    await fetchData()
  } catch (err) {
    console.error('Failed to delete zone:', err)
  }
}

const colors = [
  '#3B82F6', // Blue
  '#10B981', // Green
  '#F59E0B', // Yellow
  '#EF4444', // Red
  '#8B5CF6', // Purple
  '#EC4899', // Pink
  '#06B6D4', // Cyan
  '#F97316', // Orange
]
</script>

<template>
  <div>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
          <NuxtLink 
            :to="`/academies/${academyName}/admin/home-visits`"
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
          >
            <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
          </NuxtLink>
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการโซนเยี่ยมบ้าน</h1>
            <p class="text-gray-600 dark:text-gray-400">แบ่งพื้นที่รับผิดชอบของครู</p>
          </div>
        </div>
        
        <button
          @click="openCreateModal"
          class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2"
        >
          <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
          <span>เพิ่มโซน</span>
        </button>
      </div>

      <!-- Zones List -->
      <div v-if="isLoading" class="flex items-center justify-center py-12">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 animate-spin text-primary-600" />
      </div>

      <div v-else-if="zones.length === 0" class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <Icon icon="fluent:map-24-regular" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
        <p class="text-gray-500 dark:text-gray-400 mb-4">ยังไม่มีโซนเยี่ยมบ้าน</p>
        <button
          @click="openCreateModal"
          class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
        >
          เพิ่มโซนแรก
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="zone in zones"
          :key="zone.id"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
        >
          <!-- Color Bar -->
          <div class="h-2" :style="{ backgroundColor: zone.color || '#3B82F6' }"></div>
          
          <div class="p-4">
            <div class="flex items-start justify-between mb-3">
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                  <span 
                    class="w-3 h-3 rounded-full" 
                    :style="{ backgroundColor: zone.color || '#3B82F6' }"
                  ></span>
                  {{ zone.zone_name }}
                </h3>
                <p v-if="zone.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  {{ zone.description }}
                </p>
              </div>
              
              <div class="flex gap-1">
                <button
                  @click="openEditModal(zone)"
                  class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-gray-400 hover:text-primary-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                >
                  <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
                </button>
                <button
                  @click="confirmDelete(zone)"
                  class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-gray-400 hover:text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                >
                  <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
                </button>
              </div>
            </div>
            
            <div class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 mb-3">
              <Icon icon="fluent:home-24-regular" class="w-4 h-4" />
              {{ zone.home_visits_count || 0 }} รายการเยี่ยมบ้าน
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
          <div class="p-6 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ editingZone ? 'แก้ไขโซน' : 'เพิ่มโซนใหม่' }}
            </h3>
          </div>
          
          <div class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                ชื่อโซน <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.name"
                type="text"
                placeholder="เช่น โซน A, เขตเหนือ"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                คำอธิบาย
              </label>
              <textarea
                v-model="form.description"
                rows="2"
                placeholder="รายละเอียดพื้นที่ที่รับผิดชอบ"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              ></textarea>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                สี
              </label>
              <div class="flex gap-2 flex-wrap">
                <button
                  v-for="color in colors"
                  :key="color"
                  @click="form.color = color"
                  class="w-8 h-8 rounded-full transition-transform"
                  :style="{ backgroundColor: color }"
                  :class="form.color === color ? 'ring-2 ring-offset-2 ring-gray-400 scale-110' : 'hover:scale-105'"
                ></button>
              </div>
            </div>
            
          </div>
          
          <div class="p-6 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
            <button
              @click="showModal = false"
              class="min-h-[44px] sm:min-h-0 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300"
            >
              ยกเลิก
            </button>
            <button
              @click="saveZone"
              :disabled="!form.name.trim() || isSaving"
              class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 flex items-center gap-2"
            >
              <Icon v-if="isSaving" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
              <span>{{ isSaving ? 'กำลังบันทึก...' : 'บันทึก' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Delete Modal -->
    <Teleport to="body">
      <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-md w-full">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ยืนยันการลบ</h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">
            คุณต้องการลบโซน "{{ deletingZone?.name }}" หรือไม่?
          </p>
          <div class="flex justify-end gap-3">
            <button
              @click="showDeleteModal = false"
              class="min-h-[44px] sm:min-h-0 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300"
            >
              ยกเลิก
            </button>
            <button
              @click="deleteZone"
              class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
            >
              ลบ
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
