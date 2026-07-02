<script setup lang="ts">
import { ref, computed } from 'vue'
import type { StudentContact } from '~/composables/useStudentProfile'
import { useStudentEdit } from '~/composables/useStudentEdit'
import { useToast } from 'primevue/usetoast'

const props = withDefaults(defineProps<{
  contacts?: StudentContact[]
  accessLevel?: string
  studentId?: number
  academyId?: number
}>(), {
  contacts: () => [],
  accessLevel: '',
  studentId: 0,
  academyId: 0
})

const emit = defineEmits<{
  (e: 'saved'): void
}>()

const toast = useToast()
const { addContact, updateContact, deleteContact, isSubmitting } = useStudentEdit(props.academyId, props.studentId)

// Editing state
const isAdding = ref(false)
const editingContactId = ref<number | null>(null)

// Form state
const form = ref({
  contact_type: 'phone',
  contact_value: '',
  is_primary: false
})

const typeText = (type: string) => {
  const map: Record<string, string> = {
    phone: 'โทรศัพท์',
    mobile: 'มือถือ',
    email: 'อีเมล',
    line: 'LINE',
    facebook: 'Facebook',
    other: 'อื่นๆ',
  }
  return map[type] || type
}

const typeIcon = (type: string) => {
  const map: Record<string, string> = {
    phone: '📞',
    mobile: '📱',
    email: '✉️',
    line: '💬',
    facebook: '👤',
    other: '📋',
  }
  return map[type] || '📋'
}

const typeColor = (type: string) => {
  const map: Record<string, string> = {
    phone: 'bg-blue-100 text-blue-800',
    mobile: 'bg-green-100 text-green-800',
    email: 'bg-purple-100 text-purple-800',
    line: 'bg-emerald-100 text-emerald-800',
    facebook: 'bg-indigo-100 text-indigo-800',
    other: 'bg-gray-100 text-gray-800',
  }
  return map[type] || 'bg-gray-100 text-gray-800'
}

const canEdit = computed(() => {
  return props.accessLevel === 'self' || props.accessLevel === 'admin'
})

const startAdd = () => {
  form.value = {
    contact_type: 'phone',
    contact_value: '',
    is_primary: false
  }
  isAdding.value = true
  editingContactId.value = null
}

const startEdit = (contact: StudentContact) => {
  form.value = {
    contact_type: contact.contact_type,
    contact_value: contact.contact_value,
    is_primary: contact.is_primary
  }
  editingContactId.value = contact.id
  isAdding.value = false
}

const cancelForm = () => {
  isAdding.value = false
  editingContactId.value = null
}

const save = async () => {
  try {
    let res;
    if (editingContactId.value) {
      res = await updateContact(editingContactId.value, form.value)
    } else {
      res = await addContact(form.value)
    }

    if (res?.status === 'success' || res?.success) {
      toast.add({
        severity: 'success',
        summary: 'สำเร็จ',
        detail: res.message || 'บันทึกข้อมูลการติดต่อเรียบร้อยแล้ว',
        life: 3000
      })
      cancelForm()
      emit('saved')
    } else {
      toast.add({
        severity: 'error',
        summary: 'เกิดข้อผิดพลาด',
        detail: res?.message || 'ไม่สามารถบันทึกข้อมูลได้',
        life: 3000
      })
    }
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'เกิดข้อผิดพลาด',
      detail: err?.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล',
      life: 3000
    })
  }
}

const remove = async (id: number) => {
  if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลการติดตอนนี้?')) return
  try {
    const res = await deleteContact(id)
    if (res?.status === 'success' || res?.success) {
      toast.add({
        severity: 'success',
        summary: 'สำเร็จ',
        detail: 'ลบข้อมูลการติดต่อเรียบร้อยแล้ว',
        life: 3000
      })
      emit('saved')
    }
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'เกิดข้อผิดพลาด',
      detail: err?.message || 'ไม่สามารถลบข้อมูลได้',
      life: 3000
    })
  }
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-teal-500 to-cyan-600 px-5 py-4 flex items-center justify-between">
      <div class="flex items-center">
        <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
          </svg>
        </div>
        <h3 class="ml-3 text-lg font-semibold text-white">ข้อมูลติดต่อ</h3>
      </div>
      <button v-if="canEdit && !isAdding && !editingContactId" @click="startAdd"
              class="px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white rounded-xl text-xs font-semibold backdrop-blur-sm transition-colors flex items-center gap-1">
        + เพิ่มข้อมูลติดต่อ
      </button>
    </div>

    <!-- Add/Edit Inline Form -->
    <div v-if="isAdding || editingContactId" class="p-5 space-y-4">
      <h4 class="text-sm font-semibold text-gray-700 mb-2">
        {{ editingContactId ? 'แก้ไขข้อมูลการติดต่อ' : 'เพิ่มข้อมูลการติดต่อใหม่' }}
      </h4>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ช่องทางการติดต่อ</label>
          <select v-model="form.contact_type" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
            <option value="phone">โทรศัพท์</option>
            <option value="mobile">มือถือ</option>
            <option value="email">อีเมล</option>
            <option value="line">LINE</option>
            <option value="facebook">Facebook</option>
            <option value="other">อื่นๆ</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ข้อมูลติดต่อ (เบอร์โทร, อีเมล, ID)</label>
          <input v-model="form.contact_value" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500" required />
        </div>
      </div>

      <div class="flex items-center">
        <label class="inline-flex items-center cursor-pointer">
          <input type="checkbox" v-model="form.is_primary" class="rounded text-teal-600 focus:ring-teal-500 w-4 h-4" />
          <span class="ml-2 text-sm text-gray-600 font-medium">ตั้งเป็นข้อมูลติดต่อหลัก</span>
        </label>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
        <button @click="cancelForm" :disabled="isSubmitting"
                class="px-4 py-2 border border-gray-200 text-gray-600 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-colors disabled:opacity-50">
          ยกเลิก
        </button>
        <button @click="save" :disabled="isSubmitting"
                class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-sm font-semibold transition-colors disabled:opacity-50 flex items-center gap-1.5">
          <svg v-if="isSubmitting" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
          </svg>
          บันทึก
        </button>
      </div>
    </div>

    <!-- Read Mode List -->
    <div v-else class="p-5">
      <div v-if="!contacts || contacts.length === 0" class="text-center py-8">
        <p class="text-sm text-gray-500">ยังไม่มีข้อมูลติดต่อ</p>
      </div>
      <div v-else class="space-y-3">
        <div v-for="contact in contacts" :key="contact.id" class="flex items-center gap-3 rounded-xl border border-gray-100 p-3 relative group">
          <span class="text-xl flex-shrink-0">{{ typeIcon(contact.contact_type) }}</span>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', typeColor(contact.contact_type)]">
                {{ typeText(contact.contact_type) }}
              </span>
              <span v-if="contact.is_primary" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-teal-600 text-white">หลัก</span>
            </div>
            <p class="text-sm font-medium text-gray-900 mt-1 truncate pr-16">{{ contact.contact_value }}</p>
          </div>

          <!-- Actions Hover Menu -->
          <div v-if="canEdit" class="absolute top-1/2 -translate-y-1/2 right-4 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity bg-white pl-2">
            <button @click="startEdit(contact)"
                    title="แก้ไข"
                    class="p-1 hover:bg-gray-100 text-gray-600 rounded transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
              </svg>
            </button>
            <button @click="remove(contact.id)"
                    title="ลบ"
                    class="p-1 hover:bg-red-50 text-red-600 rounded transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>
