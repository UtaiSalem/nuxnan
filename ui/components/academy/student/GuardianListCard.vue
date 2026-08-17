<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-800">
        <Icon icon="mdi:account-child" class="w-5 h-5 mr-2 inline text-blue-600" />
        ข้อมูลผู้ปกครอง
      </h3>
      <button
        v-if="canEdit"
        @click="showAddModal = true"
        class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors"
      >
        <Icon icon="mdi:plus" class="w-4 h-4 mr-1 inline" />
        เพิ่มผู้ปกครอง
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="guardians.length === 0" class="text-center py-8 bg-gray-50 rounded-lg border border-dashed border-gray-300">
      <Icon icon="mdi:account-child-outline" class="w-12 h-12 mx-auto text-gray-400 mb-3" />
      <p class="text-gray-500">ยังไม่มีข้อมูลผู้ปกครอง</p>
      <button
        v-if="canEdit"
        @click="showAddModal = true"
        class="mt-3 text-blue-600 hover:text-blue-700 text-sm"
      >
        + เพิ่มผู้ปกครองคนแรก
      </button>
    </div>

    <!-- Guardian List -->
    <div v-else class="space-y-3">
      <div
        v-for="guardian in guardians"
        :key="guardian.id"
        class="bg-white rounded-lg border shadow-sm p-4 hover:shadow-md transition-shadow"
      >
        <div class="flex items-start justify-between">
          <!-- Left: Guardian Info -->
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 rounded-full flex items-center justify-center text-white text-lg font-bold" :class="getTypeColor(guardian.guardian_type)">
              {{ getTypeIcon(guardian.guardian_type) }}
            </div>
            <div>
              <div class="flex items-center space-x-2">
                <span class="font-medium text-gray-900">{{ guardian.full_name }}</span>
                <span v-if="guardian.is_primary_contact" class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">
                  ผู้ติดต่อหลัก
                </span>
                <span v-if="guardian.is_emergency_contact" class="px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full">
                  เหตุฉุกเฉิน
                </span>
              </div>
              <div class="text-sm text-gray-500">
                <span :class="getTypeBadgeClass(guardian.guardian_type)">{{ getTypeLabel(guardian.guardian_type) }}</span>
                <span v-if="guardian.relationship"> · {{ guardian.relationship }}</span>
              </div>
              <div v-if="guardian.primary_phone" class="text-sm text-gray-600 mt-1">
                <Icon icon="mdi:phone" class="w-4 h-4 inline mr-1" />
                {{ guardian.primary_phone }}
              </div>
            </div>
          </div>

          <!-- Right: Actions -->
          <div v-if="canEdit" class="flex items-center space-x-1">
            <button
              @click="editGuardian(guardian)"
              class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
              title="แก้ไข"
            >
              <Icon icon="mdi:pencil-outline" class="w-5 h-5" />
            </button>
            <button
              @click="confirmDelete(guardian)"
              class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
              title="ลบ"
            >
              <Icon icon="mdi:delete-outline" class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Additional Info -->
        <div v-if="guardian.occupation || guardian.workplace" class="mt-3 pt-3 border-t border-gray-100 text-sm text-gray-600">
          <div v-if="guardian.occupation" class="flex items-center">
            <Icon icon="mdi:briefcase-outline" class="w-4 h-4 mr-2 text-gray-400" />
            {{ guardian.occupation }}
            <span v-if="guardian.workplace" class="ml-1 text-gray-400">· {{ guardian.workplace }}</span>
          </div>
        </div>

        <!-- Contacts -->
        <div v-if="guardian.contacts && guardian.contacts.length > 1" class="mt-3 pt-3 border-t border-gray-100">
          <div class="text-xs text-gray-500 mb-2">ช่องทางติดต่ออื่นๆ</div>
          <div class="flex flex-wrap gap-2">
            <div
              v-for="contact in guardian.contacts.filter(c => !c.is_primary)"
              :key="contact.id"
              class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded"
            >
              <Icon :icon="getContactIcon(contact.contact_type)" class="w-3 h-3 inline mr-1" />
              {{ contact.contact_value }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <GuardianFormModal
      v-if="showAddModal"
      :guardian="selectedGuardian"
      :student-id="studentId"
      :academy-id="academyId"
      @close="closeModal"
      @saved="onGuardianSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { ref, onMounted } from 'vue'
import Swal from 'sweetalert2'
import GuardianFormModal from '~/components/academy/student/GuardianFormModal.vue'

const props = defineProps<{
  studentId: number
  academyId: number
  canEdit?: boolean
}>()

const emit = defineEmits(['update'])

const { $api } = useNuxtApp()

const guardians = ref<any[]>([])
const loading = ref(false)
const showAddModal = ref(false)
const selectedGuardian = ref<any>(null)

// Load guardians
const loadGuardians = async () => {
  loading.value = true
  try {
    const response = await $api.get(`/academies/${props.academyId}/students/${props.studentId}/guardians`)
    if (response.data.success) {
      guardians.value = response.data.guardians
    }
  } catch (error) {
    console.error('Error loading guardians:', error)
  } finally {
    loading.value = false
  }
}

// Edit guardian
const editGuardian = (guardian: any) => {
  selectedGuardian.value = guardian
  showAddModal.value = true
}

// Close modal
const closeModal = () => {
  showAddModal.value = false
  selectedGuardian.value = null
}

// On guardian saved
const onGuardianSaved = () => {
  closeModal()
  loadGuardians()
  emit('update')
}

// Confirm delete
const confirmDelete = async (guardian: any) => {
  const result = await Swal.fire({
    title: 'ยืนยันการลบ',
    html: `คุณต้องการลบ <strong>${guardian.full_name}</strong> ออกจากรายการผู้ปกครองหรือไม่?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
  })

  if (result.isConfirmed) {
    await deleteGuardian(guardian.id)
  }
}

// Delete guardian
const deleteGuardian = async (guardianId: number) => {
  try {
    const response = await $api.delete(`/academies/${props.academyId}/guardians/${guardianId}`)
    if (response.data.success) {
      await Swal.fire({
        icon: 'success',
        title: 'ลบเรียบร้อย',
        timer: 1500,
        showConfirmButton: false,
      })
      loadGuardians()
      emit('update')
    }
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: error.response?.data?.message || 'ไม่สามารถลบได้',
    })
  }
}

// Helper functions
const getTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    father: 'บิดา',
    mother: 'มารดา',
    grandfather: 'ปู่/ตา',
    grandmother: 'ย่า/ยาย',
    uncle: 'ลุง/อา',
    aunt: 'ป้า/น้า',
    sibling: 'พี่/น้อง',
    other: 'อื่นๆ',
  }
  return labels[type] || type
}

const getTypeIcon = (type: string) => {
  const icons: Record<string, string> = {
    father: '👨',
    mother: '👩',
    grandfather: '👴',
    grandmother: '👵',
    uncle: '👨',
    aunt: '👩',
    sibling: '🧑',
    other: '👤',
  }
  return icons[type] || '👤'
}

const getTypeColor = (type: string) => {
  const colors: Record<string, string> = {
    father: 'bg-blue-500',
    mother: 'bg-pink-500',
    grandfather: 'bg-gray-500',
    grandmother: 'bg-purple-500',
    uncle: 'bg-indigo-500',
    aunt: 'bg-rose-500',
    sibling: 'bg-teal-500',
    other: 'bg-gray-400',
  }
  return colors[type] || 'bg-gray-400'
}

const getTypeBadgeClass = (type: string) => {
  if (type === 'father' || type === 'mother') {
    return 'text-blue-600 font-medium'
  }
  return 'text-gray-500'
}

const getContactIcon = (type: string) => {
  const icons: Record<string, string> = {
    phone: 'mdi:phone',
    email: 'mdi:email',
    line: 'simple-icons:line',
    facebook: 'mdi:facebook',
  }
  return icons[type] || 'mdi:message'
}

onMounted(() => {
  loadGuardians()
})
</script>
