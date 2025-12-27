<template>
  <div class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">จัดการโซนการเยี่ยมบ้าน</h2>
        <p class="mt-1 text-sm text-gray-600">
          จัดการโซนพื้นที่สำหรับการเยี่ยมบ้านนักเรียน
        </p>
      </div>
      <button
        @click="openCreateModal"
        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        <i class="fas fa-plus mr-2"></i>
        เพิ่มโซนใหม่
      </button>
    </div>

    <!-- Search and Filter -->
    <div class="bg-white shadow rounded-lg p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
          <label for="search" class="sr-only">ค้นหาโซน</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="fas fa-search text-gray-400"></i>
            </div>
            <input
              v-model="searchQuery"
              type="text"
              id="search"
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="ค้นหาจากชื่อโซน หรือรหัสโซน..."
              @input="debouncedSearch"
            />
          </div>
        </div>
        <div>
          <label for="status-filter" class="sr-only">กรองตามสถานะ</label>
          <select
            v-model="statusFilter"
            id="status-filter"
            class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
            @change="loadZones"
          >
            <option value="">ทั้งหมด</option>
            <option value="1">เปิดใช้งาน</option>
            <option value="0">ปิดใช้งาน</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Zones List -->
    <div v-if="loading" class="text-center py-12">
      <i class="fas fa-spinner fa-spin text-4xl text-indigo-500"></i>
      <p class="mt-2 text-gray-600">กำลังโหลดข้อมูล...</p>
    </div>

    <div v-else-if="zones.data && zones.data.length > 0" class="space-y-4">
      <div
        v-for="zone in zones.data"
        :key="zone.id"
        class="bg-white shadow rounded-lg overflow-hidden hover:shadow-md transition-shadow duration-200"
        :class="{ 'opacity-60': !zone.is_active }"
      >
        <div class="p-6">
          <div class="flex items-start justify-between">
            <!-- Zone Info -->
            <div class="flex items-start space-x-4 flex-1">
              <!-- Color Indicator -->
              <div
                class="w-16 h-16 rounded-lg flex-shrink-0"
                :style="{ backgroundColor: zone.color || '#6B7280' }"
              ></div>

              <!-- Zone Details -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-3">
                  <h3 class="text-lg font-semibold text-gray-900">
                    {{ zone.zone_name }}
                  </h3>
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="zone.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                  >
                    {{ zone.is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                  </span>
                </div>
                <p v-if="zone.description" class="mt-2 text-sm text-gray-600">
                  {{ zone.description }}
                </p>
                <div class="mt-3 flex items-center text-sm text-gray-500">
                  <i class="fas fa-home mr-2"></i>
                  <span>จำนวนการเยี่ยมบ้าน: <strong>{{ zone.home_visits_count || 0 }}</strong> ครั้ง</span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center space-x-2 ml-4">
              <button
                @click="toggleZoneStatus(zone)"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                :class="zone.is_active ? 'hover:bg-yellow-50' : 'hover:bg-green-50'"
              >
                <i :class="zone.is_active ? 'fas fa-toggle-on text-green-600' : 'fas fa-toggle-off text-gray-400'" class="mr-2"></i>
                {{ zone.is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}
              </button>
              <button
                @click="editZone(zone)"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <i class="fas fa-edit mr-2"></i>
                แก้ไข
              </button>
              <button
                @click="deleteZone(zone)"
                class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                :disabled="zone.home_visits_count > 0"
                :class="{ 'opacity-50 cursor-not-allowed': zone.home_visits_count > 0 }"
              >
                <i class="fas fa-trash mr-2"></i>
                ลบ
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white shadow rounded-lg p-12 text-center">
      <i class="fas fa-map-marked-alt text-6xl text-gray-300 mb-4"></i>
      <h3 class="text-lg font-medium text-gray-900 mb-2">ยังไม่มีโซน</h3>
      <p class="text-gray-500 mb-6">เริ่มต้นสร้างโซนการเยี่ยมบ้านเพื่อจัดการพื้นที่</p>
      <button
        @click="openCreateModal"
        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
      >
        <i class="fas fa-plus mr-2"></i>
        เพิ่มโซนแรก
      </button>
    </div>

    <!-- Pagination -->
    <div v-if="zones.data && zones.data.length > 0" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 rounded-lg shadow">
      <div class="flex-1 flex justify-between sm:hidden">
        <button
          @click="loadZones(zones.current_page - 1)"
          :disabled="zones.current_page === 1"
          class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
        >
          ก่อนหน้า
        </button>
        <button
          @click="loadZones(zones.current_page + 1)"
          :disabled="zones.current_page === zones.last_page"
          class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
        >
          ถัดไป
        </button>
      </div>
      <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
          <p class="text-sm text-gray-700">
            แสดง
            <span class="font-medium">{{ zones.from }}</span>
            ถึง
            <span class="font-medium">{{ zones.to }}</span>
            จาก
            <span class="font-medium">{{ zones.total }}</span>
            รายการ
          </p>
        </div>
        <div>
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <button
              @click="loadZones(zones.current_page - 1)"
              :disabled="zones.current_page === 1"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
            >
              <i class="fas fa-chevron-left"></i>
            </button>
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="loadZones(page)"
              :class="[
                page === zones.current_page
                  ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                  : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
              ]"
            >
              {{ page }}
            </button>
            <button
              @click="loadZones(zones.current_page + 1)"
              :disabled="zones.current_page === zones.last_page"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
            >
              <i class="fas fa-chevron-right"></i>
            </button>
          </nav>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <transition name="modal">
      <div v-if="showModal" class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>

          <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

          <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form @submit.prevent="saveZone">
              <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                  <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                    <i class="fas fa-map-marked-alt text-indigo-600"></i>
                  </div>
                  <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                      {{ editingZone ? 'แก้ไขโซน' : 'เพิ่มโซนใหม่' }}
                    </h3>
                    <div class="mt-4 space-y-4">
                      <!-- Zone Name -->
                      <div>
                        <label for="zone_name" class="block text-sm font-medium text-gray-700">
                          ชื่อโซน <span class="text-red-500">*</span>
                        </label>
                        <input
                          v-model="formData.zone_name"
                          type="text"
                          id="zone_name"
                          required
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                          placeholder="เช่น โซนเหนือ, โซนกลาง"
                        />
                        <p v-if="errors.zone_name" class="mt-1 text-sm text-red-600">{{ errors.zone_name[0] }}</p>
                      </div>

                      <!-- Description -->
                      <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">
                          รายละเอียด
                        </label>
                        <textarea
                          v-model="formData.description"
                          id="description"
                          rows="3"
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                          placeholder="คำอธิบายเกี่ยวกับโซนนี้"
                        ></textarea>
                      </div>

                      <!-- Color -->
                      <div>
                        <label for="color" class="block text-sm font-medium text-gray-700">
                          สีประจำโซน
                        </label>
                        <div class="mt-1 flex items-center space-x-3">
                          <input
                            v-model="formData.color"
                            type="color"
                            id="color"
                            class="h-10 w-20 border-gray-300 rounded-md cursor-pointer"
                          />
                          <input
                            v-model="formData.color"
                            type="text"
                            class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="#3B82F6"
                          />
                        </div>
                        <div class="mt-2 flex space-x-2">
                          <button
                            v-for="presetColor in presetColors"
                            :key="presetColor"
                            type="button"
                            @click="formData.color = presetColor"
                            class="w-8 h-8 rounded-full border-2 border-gray-300 hover:border-gray-400"
                            :style="{ backgroundColor: presetColor }"
                          ></button>
                        </div>
                      </div>

                      <!-- Display Order -->
                      <div>
                        <label for="display_order" class="block text-sm font-medium text-gray-700">
                          ลำดับการแสดง
                        </label>
                        <input
                          v-model.number="formData.display_order"
                          type="number"
                          id="display_order"
                          min="0"
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                          placeholder="0"
                        />
                        <p class="mt-1 text-xs text-gray-500">ตัวเลขน้อยแสดงก่อน</p>
                      </div>

                      <!-- Is Active -->
                      <div class="flex items-center">
                        <input
                          v-model="formData.is_active"
                          type="checkbox"
                          id="is_active"
                          class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        />
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">
                          เปิดใช้งาน
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button
                  type="submit"
                  :disabled="saving"
                  class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                >
                  <i v-if="saving" class="fas fa-spinner fa-spin mr-2"></i>
                  {{ saving ? 'กำลังบันทึก...' : 'บันทึก' }}
                </button>
                <button
                  type="button"
                  @click="closeModal"
                  :disabled="saving"
                  class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                >
                  ยกเลิก
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'ZoneManagement',

  setup() {
    const zones = ref({ data: [], current_page: 1, last_page: 1, from: 0, to: 0, total: 0 })
    const loading = ref(false)
    const searchQuery = ref('')
    const statusFilter = ref('')
    const showModal = ref(false)
    const editingZone = ref(null)
    const saving = ref(false)
    const errors = ref({})

    const formData = ref({
      zone_name: '',
      description: '',
      color: '#3B82F6',
      is_active: true,
      display_order: 0
    })

    const presetColors = [
      '#3B82F6', // Blue
      '#EF4444', // Red
      '#10B981', // Green
      '#F59E0B', // Orange
      '#8B5CF6', // Purple
      '#EC4899', // Pink
      '#06B6D4', // Cyan
      '#84CC16', // Lime
    ]

    const visiblePages = computed(() => {
      const pages = []
      const current = zones.value.current_page
      const last = zones.value.last_page
      const delta = 2

      for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        pages.push(i)
      }

      if (current - delta > 2) {
        pages.unshift('...')
      }
      if (current + delta < last - 1) {
        pages.push('...')
      }

      pages.unshift(1)
      if (last > 1) {
        pages.push(last)
      }

      return pages.filter((v, i, a) => a.indexOf(v) === i)
    })

    let searchTimeout = null
    const debouncedSearch = () => {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        loadZones(1)
      }, 500)
    }

    const loadZones = async (page = 1) => {
      loading.value = true
      try {
        const params = {
          page,
          per_page: 10
        }

        if (searchQuery.value) {
          params.search = searchQuery.value
        }

        if (statusFilter.value !== '') {
          params.is_active = statusFilter.value
        }

        const response = await axios.get('/home-visit/admin/zones', { params })
        zones.value = response.data.zones
      } catch (error) {
        console.error('Error loading zones:', error)
        alert('เกิดข้อผิดพลาดในการโหลดข้อมูล')
      } finally {
        loading.value = false
      }
    }

    const openCreateModal = () => {
      editingZone.value = null
      formData.value = {
        zone_name: '',
        description: '',
        color: '#3B82F6',
        is_active: true,
        display_order: 0
      }
      errors.value = {}
      showModal.value = true
    }

    const editZone = (zone) => {
      editingZone.value = zone
      formData.value = {
        zone_name: zone.zone_name,
        description: zone.description || '',
        color: zone.color || '#3B82F6',
        is_active: zone.is_active,
        display_order: zone.display_order || 0
      }
      errors.value = {}
      showModal.value = true
    }

    const closeModal = () => {
      showModal.value = false
      editingZone.value = null
      errors.value = {}
    }

    const saveZone = async () => {
      saving.value = true
      errors.value = {}

      try {
        if (editingZone.value) {
          // Update existing zone
          await axios.put(`/home-visit/admin/zones/${editingZone.value.id}`, formData.value)
          alert('อัพเดทโซนเรียบร้อยแล้ว')
        } else {
          // Create new zone
          await axios.post('/home-visit/admin/zones', formData.value)
          alert('เพิ่มโซนเรียบร้อยแล้ว')
        }

        closeModal()
        loadZones(zones.value.current_page)
      } catch (error) {
        console.error('Error saving zone:', error)
        if (error.response && error.response.data.errors) {
          errors.value = error.response.data.errors
        } else {
          alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล')
        }
      } finally {
        saving.value = false
      }
    }

    const toggleZoneStatus = async (zone) => {
      try {
        const response = await axios.put(`/home-visit/admin/zones/${zone.id}/toggle-status`)
        alert(response.data.message)
        loadZones(zones.value.current_page)
      } catch (error) {
        console.error('Error toggling zone status:', error)
        alert('เกิดข้อผิดพลาดในการเปลี่ยนสถานะ')
      }
    }

    const deleteZone = async (zone) => {
      if (zone.home_visits_count > 0) {
        alert('ไม่สามารถลบโซนที่มีการเยี่ยมบ้านอยู่ได้ กรุณาย้ายหรือลบการเยี่ยมบ้านก่อน')
        return
      }

      if (!confirm(`คุณแน่ใจหรือไม่ที่จะลบโซน "${zone.zone_name}"?`)) {
        return
      }

      try {
        await axios.delete(`/home-visit/admin/zones/${zone.id}`)
        alert('ลบโซนเรียบร้อยแล้ว')
        loadZones(zones.value.current_page)
      } catch (error) {
        console.error('Error deleting zone:', error)
        alert(error.response?.data?.message || 'เกิดข้อผิดพลาดในการลบโซน')
      }
    }

    onMounted(() => {
      loadZones()
    })

    return {
      zones,
      loading,
      searchQuery,
      statusFilter,
      showModal,
      editingZone,
      saving,
      errors,
      formData,
      presetColors,
      visiblePages,
      debouncedSearch,
      loadZones,
      openCreateModal,
      editZone,
      closeModal,
      saveZone,
      toggleZoneStatus,
      deleteZone
    }
  }
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>

