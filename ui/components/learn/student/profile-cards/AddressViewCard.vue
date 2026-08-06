<script setup lang="ts">
import { ref, computed } from 'vue'
import type { StudentAddress } from '~/composables/useStudentProfile'
import { useStudentEdit } from '~/composables/useStudentEdit'

const props = withDefaults(defineProps<{
  addresses?: StudentAddress[]
  accessLevel?: string
  studentId?: number
  academyId?: number
}>(), {
  addresses: () => [],
  accessLevel: '',
  studentId: 0,
  academyId: 0
})

const emit = defineEmits<{
  (e: 'saved'): void
}>()

const toast = useToast()
const { addAddress, updateAddress, deleteAddress, setCurrent, isSubmitting } = useStudentEdit(props.academyId, props.studentId)

// Editing state
const isAdding = ref(false)
const editingAddressId = ref<number | null>(null)

// Form state
const form = ref({
  address_type: 'current',
  house_number: '',
  village_number: '',
  village_name: '',
  alley: '',
  road: '',
  subdistrict: '',
  district: '',
  province: '',
  postal_code: '',
  is_current: false
})

const typeText = (type: string) => {
  const map: Record<string, string> = {
    current: 'ที่อยู่ปัจจุบัน',
    permanent: 'ที่อยู่ตามทะเบียนบ้าน',
    temporary: 'ที่อยู่ชั่วคราว',
  }
  return map[type] || type
}

const typeColor = (type: string) => {
  const map: Record<string, string> = {
    current: 'bg-green-100 text-green-800',
    permanent: 'bg-blue-100 text-blue-800',
    temporary: 'bg-amber-100 text-amber-800',
  }
  return map[type] || 'bg-gray-100 text-gray-800'
}

const formatAddress = (addr: StudentAddress) => {
  const parts = [
    addr.house_number ? `บ้านเลขที่ ${addr.house_number}` : null,
    addr.village_number ? `หมู่ ${addr.village_number}` : null,
    addr.village_name ? `หมู่บ้าน${addr.village_name}` : null,
    addr.alley ? `ซอย ${addr.alley}` : null,
    addr.road ? `ถนน ${addr.road}` : null,
    addr.subdistrict ? `ต.${addr.subdistrict}` : null,
    addr.district ? `อ.${addr.district}` : null,
    addr.province ? `จ.${addr.province}` : null,
    addr.postal_code || null,
  ].filter(Boolean)
  return parts.join(' ') || '-'
}

const canEdit = computed(() => {
  return ['self', 'admin', 'homeroom'].includes(props.accessLevel)
})

const startAdd = () => {
  form.value = {
    address_type: 'current',
    house_number: '',
    village_number: '',
    village_name: '',
    alley: '',
    road: '',
    subdistrict: '',
    district: '',
    province: '',
    postal_code: '',
    is_current: false
  }
  isAdding.value = true
  editingAddressId.value = null
}

const startEdit = (addr: StudentAddress) => {
  form.value = {
    address_type: addr.address_type,
    house_number: addr.house_number || '',
    village_number: addr.village_number || '',
    village_name: addr.village_name || '',
    alley: addr.alley || '',
    road: addr.road || '',
    subdistrict: addr.subdistrict || '',
    district: addr.district || '',
    province: addr.province || '',
    postal_code: addr.postal_code || '',
    is_current: addr.is_current
  }
  editingAddressId.value = addr.id
  isAdding.value = false
}

const cancelForm = () => {
  isAdding.value = false
  editingAddressId.value = null
}

const save = async () => {
  try {
    let res;
    if (editingAddressId.value) {
      res = await updateAddress(editingAddressId.value, form.value)
    } else {
      res = await addAddress(form.value)
    }

    if (res?.status === 'success' || res?.success) {
      toast.add({
        severity: 'success',
        summary: 'สำเร็จ',
        detail: res.message || 'บันทึกข้อมูลที่อยู่เรียบร้อยแล้ว',
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
  if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการลบที่อยู่นี้?')) return
  try {
    const res = await deleteAddress(id)
    if (res?.status === 'success' || res?.success) {
      toast.add({
        severity: 'success',
        summary: 'สำเร็จ',
        detail: 'ลบข้อมูลที่อยู่เรียบร้อยแล้ว',
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

const markCurrent = async (addr: StudentAddress) => {
  try {
    const res = await setCurrent(addr.id)
    if (res?.status === 'success' || res?.success) {
      toast.add({
        severity: 'success',
        summary: 'สำเร็จ',
        detail: 'ตั้งเป็นที่อยู่ปัจจุบันเรียบร้อยแล้ว',
        life: 3000
      })
      emit('saved')
    }
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'เกิดข้อผิดพลาด',
      detail: err?.message || 'ไม่สามารถเปลี่ยนที่อยู่ได้',
      life: 3000
    })
  }
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-amber-500 to-orange-600 px-5 py-4 flex items-center justify-between">
      <div class="flex items-center">
        <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <h3 class="ml-3 text-lg font-semibold text-white">ที่อยู่</h3>
      </div>
      <button v-if="canEdit && !isAdding && !editingAddressId" @click="startAdd"
              class="px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white rounded-xl text-xs font-semibold backdrop-blur-sm transition-colors flex items-center gap-1">
        + เพิ่มที่อยู่
      </button>
    </div>

    <!-- Add/Edit Form Inline -->
    <div v-if="isAdding || editingAddressId" class="p-5 space-y-4">
      <h4 class="text-sm font-semibold text-gray-700 mb-2">
        {{ editingAddressId ? 'แก้ไขข้อมูลที่อยู่' : 'เพิ่มข้อมูลที่อยู่ใหม่' }}
      </h4>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ประเภทที่อยู่</label>
          <select v-model="form.address_type" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
            <option value="current">ที่อยู่ปัจจุบัน</option>
            <option value="permanent">ที่อยู่ตามทะเบียนบ้าน</option>
            <option value="temporary">ที่อยู่ชั่วคราว</option>
          </select>
        </div>
        <div class="flex items-center pt-5">
          <label class="inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="form.is_current" class="rounded text-amber-600 focus:ring-amber-500 w-4 h-4" />
            <span class="ml-2 text-sm text-gray-600 font-medium">ตั้งเป็นที่อยู่หลัก/ปัจจุบัน</span>
          </label>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">บ้านเลขที่</label>
          <input v-model="form.house_number" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" required />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">หมู่ที่</label>
          <input v-model="form.village_number" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ชื่อหมู่บ้าน</label>
          <input v-model="form.village_name" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" />
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ตรอก/ซอย</label>
          <input v-model="form.alley" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ถนน</label>
          <input v-model="form.road" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" />
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ตำบล/แขวง</label>
          <input v-model="form.subdistrict" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" required />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">อำเภอ/เขต</label>
          <input v-model="form.district" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" required />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">จังหวัด</label>
          <input v-model="form.province" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" required />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">รหัสไปรษณีย์</label>
          <input v-model="form.postal_code" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" />
        </div>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
        <button @click="cancelForm" :disabled="isSubmitting"
                class="px-4 py-2 border border-gray-200 text-gray-600 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-colors disabled:opacity-50">
          ยกเลิก
        </button>
        <button @click="save" :disabled="isSubmitting"
                class="px-4 py-2 bg-amber-600 hover:bg-amber-755 text-white rounded-xl text-sm font-semibold transition-colors disabled:opacity-50 flex items-center gap-1.5">
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
      <div v-if="!addresses || addresses.length === 0" class="text-center py-8">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
        </svg>
        <p class="text-sm text-gray-500">ยังไม่มีข้อมูลที่อยู่</p>
      </div>
      <div v-else class="space-y-3">
        <div v-for="addr in addresses" :key="addr.id" class="rounded-xl border border-gray-100 p-4 relative group">
          
          <!-- Actions Menu on Hover -->
          <div v-if="canEdit" class="absolute top-4 right-4 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity bg-white pl-2">
            <button v-if="!addr.is_current" @click="markCurrent(addr)"
                    title="ตั้งเป็นปัจจุบัน"
                    class="p-1 hover:bg-green-50 text-green-600 rounded transition-colors">
              ✓
            </button>
            <button @click="startEdit(addr)"
                    title="แก้ไข"
                    class="p-1 hover:bg-gray-100 text-gray-600 rounded transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
              </svg>
            </button>
            <button @click="remove(addr.id)"
                    title="ลบ"
                    class="p-1 hover:bg-red-50 text-red-600 rounded transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>

          <div class="flex items-center gap-2 mb-2">
            <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', typeColor(addr.address_type)]">
              {{ typeText(addr.address_type) }}
            </span>
            <span v-if="addr.is_current" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-600 text-white">
              ปัจจุบัน
            </span>
          </div>
          <p class="text-sm text-gray-700 leading-relaxed pr-16">{{ formatAddress(addr) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
