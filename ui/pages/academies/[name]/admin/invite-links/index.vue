<template>
  <div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          <Icon name="mdi:link-variant" class="w-7 h-7 mr-2 inline text-indigo-600" />
          ลิงก์เชิญเข้าร่วม
        </h1>
        <p class="text-gray-500 mt-1">สร้างลิงก์เชิญหรือ QR Code สำหรับเชิญสมาชิกใหม่</p>
      </div>
      <button
        @click="showCreateModal = true"
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center"
      >
        <Icon name="mdi:plus" class="w-5 h-5 mr-1" />
        สร้างลิงก์ใหม่
      </button>
    </div>

    <!-- Link List -->
    <div class="space-y-4">
      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
      </div>

      <!-- Empty -->
      <div v-else-if="links.length === 0" class="bg-white rounded-xl shadow-sm border p-12 text-center">
        <Icon name="mdi:link-variant-off" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
        <h3 class="text-lg font-medium text-gray-700 mb-2">ยังไม่มีลิงก์เชิญ</h3>
        <p class="text-gray-500 mb-4">สร้างลิงก์เชิญเพื่อให้ผู้ใช้เข้าร่วมได้ง่ายขึ้น</p>
        <button
          @click="showCreateModal = true"
          class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
        >
          <Icon name="mdi:plus" class="w-5 h-5 mr-1 inline" />
          สร้างลิงก์แรก
        </button>
      </div>

      <!-- Links -->
      <div v-else v-for="link in links" :key="link.id" class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="p-4 sm:p-6">
          <div class="flex flex-col sm:flex-row sm:items-start gap-4">
            <!-- QR Code -->
            <div class="flex-shrink-0">
              <img
                :src="link.qr_code_url"
                :alt="link.name"
                class="w-24 h-24 sm:w-28 sm:h-28 rounded-lg border"
              />
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <h3 class="font-semibold text-gray-900">{{ link.name || 'ลิงก์เชิญ' }}</h3>
                <span
                  :class="getStatusClass(link.status)"
                  class="px-2 py-0.5 text-xs rounded-full"
                >
                  {{ getStatusLabel(link.status) }}
                </span>
              </div>

              <p v-if="link.description" class="text-sm text-gray-500 mb-2">{{ link.description }}</p>

              <!-- Invite URL -->
              <div class="flex items-center gap-2 mb-3">
                <input
                  :value="link.invite_url"
                  readonly
                  class="flex-1 px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-600 truncate"
                />
                <button
                  @click="copyToClipboard(link.invite_url)"
                  class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                  title="คัดลอก"
                >
                  <Icon name="mdi:content-copy" class="w-5 h-5" />
                </button>
              </div>

              <!-- Stats -->
              <div class="flex flex-wrap gap-4 text-sm">
                <div class="flex items-center text-gray-500">
                  <Icon name="mdi:account-group" class="w-4 h-4 mr-1" />
                  ใช้แล้ว: {{ link.use_count }}{{ link.max_uses ? ` / ${link.max_uses}` : '' }}
                </div>
                <div v-if="link.role" class="flex items-center text-gray-500">
                  <Icon name="mdi:badge-account" class="w-4 h-4 mr-1" />
                  บทบาท: {{ link.role.display_name || link.role.name }}
                </div>
                <div v-if="link.expires_at" class="flex items-center text-gray-500">
                  <Icon name="mdi:clock-outline" class="w-4 h-4 mr-1" />
                  หมดอายุ: {{ formatDate(link.expires_at) }}
                </div>
                <div v-if="link.require_approval" class="flex items-center text-orange-500">
                  <Icon name="mdi:check-circle-outline" class="w-4 h-4 mr-1" />
                  ต้องอนุมัติ
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex sm:flex-col gap-2">
              <button
                @click="downloadQR(link)"
                class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                title="ดาวน์โหลด QR"
              >
                <Icon name="mdi:download" class="w-5 h-5" />
              </button>
              <button
                @click="toggleActive(link)"
                :class="link.is_active ? 'hover:text-orange-600 hover:bg-orange-50' : 'hover:text-green-600 hover:bg-green-50'"
                class="p-2 text-gray-400 rounded-lg transition-colors"
                :title="link.is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน'"
              >
                <Icon :name="link.is_active ? 'mdi:pause-circle' : 'mdi:play-circle'" class="w-5 h-5" />
              </button>
              <button
                @click="confirmDelete(link)"
                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                title="ลบ"
              >
                <Icon name="mdi:delete-outline" class="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-4 sm:px-6 py-3 bg-gray-50 border-t text-xs text-gray-500 flex items-center justify-between">
          <span>สร้างโดย {{ link.created_by?.name || 'ระบบ' }}</span>
          <span>{{ formatDate(link.created_at) }}</span>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showCreateModal = false"></div>
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-md bg-white rounded-xl shadow-xl">
          <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-semibold">สร้างลิงก์เชิญใหม่</h3>
            <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
              <Icon name="mdi:close" class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="createLink" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อลิงก์</label>
              <input
                v-model="createForm.name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="เช่น ลิงก์สำหรับนักเรียนใหม่"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">คำอธิบาย</label>
              <textarea
                v-model="createForm.description"
                rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="รายละเอียดเพิ่มเติม (ไม่จำเป็น)"
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">บทบาทที่กำหนด</label>
              <select
                v-model="createForm.academy_role_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option :value="null">-- ไม่กำหนด (ให้ admin เลือกภายหลัง) --</option>
                <option v-for="role in availableRoles" :key="role.id" :value="role.id">
                  {{ role.display_name || role.name }}
                </option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">จำนวนใช้งานสูงสุด</label>
                <input
                  v-model.number="createForm.max_uses"
                  type="number"
                  min="1"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="ไม่จำกัด"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">หมดอายุใน (วัน)</label>
                <input
                  v-model.number="createForm.expires_in_days"
                  type="number"
                  min="1"
                  max="365"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="ไม่หมดอายุ"
                />
              </div>
            </div>

            <div class="flex items-center">
              <input
                v-model="createForm.require_approval"
                type="checkbox"
                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
              />
              <label class="ml-2 text-sm text-gray-700">ต้องรออนุมัติจาก admin ก่อนเข้าร่วม</label>
            </div>
          </form>

          <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50 rounded-b-xl">
            <button
              @click="showCreateModal = false"
              class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              ยกเลิก
            </button>
            <button
              @click="createLink"
              :disabled="creating"
              class="px-4 py-2 text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
            >
              <Icon v-if="creating" name="mdi:loading" class="w-4 h-4 mr-1 inline animate-spin" />
              สร้างลิงก์
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import Swal from 'sweetalert2'

const route = useRoute()
const { $api } = useNuxtApp()

const academyName = route.params.name as string
const academyId = ref<number | null>(null)

// State
const links = ref<any[]>([])
const availableRoles = ref<any[]>([])
const loading = ref(false)
const showCreateModal = ref(false)
const creating = ref(false)

// Create form
const createForm = ref({
  name: '',
  description: '',
  academy_role_id: null as number | null,
  max_uses: null as number | null,
  expires_in_days: null as number | null,
  require_approval: true,
})

// Load academy info
const loadAcademyInfo = async () => {
  try {
    const response = await $api.get(`/academies/${academyName}`)
    if (response.data) {
      academyId.value = response.data.id
    }
  } catch (error) {
    console.error('Error loading academy:', error)
  }
}

// Load links
const loadLinks = async () => {
  if (!academyId.value) return
  
  loading.value = true
  try {
    const response = await $api.get(`/academies/${academyId.value}/invite-links`)
    if (response.data.success) {
      links.value = response.data.links
    }
  } catch (error) {
    console.error('Error loading links:', error)
  } finally {
    loading.value = false
  }
}

// Load roles
const loadRoles = async () => {
  if (!academyId.value) return
  
  try {
    const response = await $api.get(`/academies/${academyId.value}/roles`)
    if (response.data.success) {
      availableRoles.value = response.data.roles
    }
  } catch (error) {
    console.error('Error loading roles:', error)
  }
}

// Create link
const createLink = async () => {
  if (!academyId.value) return
  
  creating.value = true
  try {
    const response = await $api.post(`/academies/${academyId.value}/invite-links`, createForm.value)
    if (response.data.success) {
      await Swal.fire({
        icon: 'success',
        title: 'สร้างลิงก์เรียบร้อย',
        html: `<div class="text-center">
          <img src="${response.data.link.qr_code_url}" class="w-40 h-40 mx-auto mb-2" />
          <p class="text-sm text-gray-500">${response.data.link.invite_url}</p>
        </div>`,
        confirmButtonText: 'ปิด',
      })
      showCreateModal.value = false
      resetCreateForm()
      loadLinks()
    }
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: error.response?.data?.message || 'ไม่สามารถสร้างลิงก์ได้',
    })
  } finally {
    creating.value = false
  }
}

// Reset form
const resetCreateForm = () => {
  createForm.value = {
    name: '',
    description: '',
    academy_role_id: null,
    max_uses: null,
    expires_in_days: null,
    require_approval: true,
  }
}

// Toggle active
const toggleActive = async (link: any) => {
  try {
    const response = await $api.post(`/academies/${academyId.value}/invite-links/${link.id}/toggle-active`)
    if (response.data.success) {
      link.is_active = response.data.is_active
      link.status = response.data.is_active ? 'active' : 'inactive'
    }
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: error.response?.data?.message || 'ไม่สามารถเปลี่ยนสถานะได้',
    })
  }
}

// Confirm delete
const confirmDelete = async (link: any) => {
  const result = await Swal.fire({
    title: 'ยืนยันการลบ',
    text: 'ลบลิงก์เชิญนี้? การกระทำนี้ไม่สามารถย้อนกลับได้',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
  })

  if (result.isConfirmed) {
    try {
      const response = await $api.delete(`/academies/${academyId.value}/invite-links/${link.id}`)
      if (response.data.success) {
        loadLinks()
        Swal.fire({
          icon: 'success',
          title: 'ลบเรียบร้อย',
          timer: 1500,
          showConfirmButton: false,
        })
      }
    } catch (error: any) {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: error.response?.data?.message || 'ไม่สามารถลบได้',
      })
    }
  }
}

// Copy to clipboard
const copyToClipboard = async (text: string) => {
  try {
    await navigator.clipboard.writeText(text)
    Swal.fire({
      icon: 'success',
      title: 'คัดลอกแล้ว',
      timer: 1000,
      showConfirmButton: false,
      toast: true,
      position: 'top-end',
    })
  } catch (error) {
    console.error('Copy failed:', error)
  }
}

// Download QR
const downloadQR = (link: any) => {
  const a = document.createElement('a')
  a.href = link.qr_code_url
  a.download = `invite-qr-${link.code}.png`
  a.click()
}

// Helper functions
const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    active: 'bg-green-100 text-green-700',
    inactive: 'bg-gray-100 text-gray-600',
    expired: 'bg-red-100 text-red-700',
    exhausted: 'bg-orange-100 text-orange-700',
  }
  return classes[status] || 'bg-gray-100 text-gray-600'
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    active: 'ใช้งานได้',
    inactive: 'ปิดใช้งาน',
    expired: 'หมดอายุ',
    exhausted: 'ใช้ครบแล้ว',
  }
  return labels[status] || status
}

onMounted(async () => {
  await loadAcademyInfo()
  loadLinks()
  loadRoles()
})
</script>
