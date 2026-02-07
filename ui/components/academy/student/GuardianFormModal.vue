<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="$emit('close')"></div>

    <!-- Modal -->
    <div class="flex min-h-full items-center justify-center p-4">
      <div class="relative w-full max-w-lg bg-white rounded-xl shadow-xl transform transition-all">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="text-lg font-semibold text-gray-900">
            <Icon :name="isEdit ? 'mdi:pencil' : 'mdi:account-plus'" class="w-5 h-5 mr-2 inline text-blue-600" />
            {{ isEdit ? 'แก้ไขผู้ปกครอง' : 'เพิ่มผู้ปกครอง' }}
          </h3>
          <button @click="$emit('close')" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
            <Icon name="mdi:close" class="w-5 h-5" />
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
          <!-- Guardian Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">ประเภทผู้ปกครอง <span class="text-red-500">*</span></label>
            <div class="grid grid-cols-4 gap-2">
              <button
                v-for="typeOpt in guardianTypes"
                :key="typeOpt.value"
                type="button"
                @click="form.guardian_type = typeOpt.value"
                :class="[
                  'p-2 text-center rounded-lg border-2 transition-all text-sm',
                  form.guardian_type === typeOpt.value
                    ? 'border-blue-500 bg-blue-50 text-blue-700'
                    : 'border-gray-200 hover:border-gray-300'
                ]"
              >
                <div class="text-xl mb-1">{{ typeOpt.icon }}</div>
                <div>{{ typeOpt.label }}</div>
              </button>
            </div>
          </div>

          <!-- Name Fields -->
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">คำนำหน้า</label>
              <select
                v-model="form.title_prefix"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">เลือก</option>
                <option value="นาย">นาย</option>
                <option value="นาง">นาง</option>
                <option value="นางสาว">นางสาว</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ <span class="text-red-500">*</span></label>
              <input
                v-model="form.first_name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="ชื่อ"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">นามสกุล <span class="text-red-500">*</span></label>
              <input
                v-model="form.last_name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="นามสกุล"
              />
            </div>
          </div>

          <!-- Citizen ID -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">เลขบัตรประชาชน</label>
            <input
              v-model="form.citizen_id"
              type="text"
              maxlength="13"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="เลขบัตรประชาชน 13 หลัก"
            />
          </div>

          <!-- Phone & Email -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์</label>
              <input
                v-model="form.phone"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="0xx-xxx-xxxx"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
              <input
                v-model="form.email"
                type="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="email@example.com"
              />
            </div>
          </div>

          <!-- Occupation & Workplace -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">อาชีพ</label>
              <input
                v-model="form.occupation"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="อาชีพ"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">สถานที่ทำงาน</label>
              <input
                v-model="form.workplace"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="สถานที่ทำงาน"
              />
            </div>
          </div>

          <!-- Monthly Income -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">รายได้ต่อเดือน (บาท)</label>
            <input
              v-model.number="form.monthly_income"
              type="number"
              min="0"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="0"
            />
          </div>

          <!-- Checkboxes -->
          <div class="flex flex-wrap gap-4 pt-2">
            <label class="flex items-center space-x-2 cursor-pointer">
              <input
                v-model="form.is_primary_contact"
                type="checkbox"
                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
              />
              <span class="text-sm text-gray-700">ผู้ติดต่อหลัก</span>
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
              <input
                v-model="form.is_emergency_contact"
                type="checkbox"
                class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500"
              />
              <span class="text-sm text-gray-700">ติดต่อกรณีฉุกเฉิน</span>
            </label>
          </div>
        </form>

        <!-- Footer -->
        <div class="flex items-center justify-end space-x-3 px-6 py-4 border-t bg-gray-50 rounded-b-xl">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
          >
            ยกเลิก
          </button>
          <button
            type="button"
            @click="handleSubmit"
            :disabled="saving"
            class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
          >
            <Icon v-if="saving" name="mdi:loading" class="w-4 h-4 mr-2 animate-spin" />
            <Icon v-else :name="isEdit ? 'mdi:check' : 'mdi:plus'" class="w-4 h-4 mr-2" />
            {{ isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มผู้ปกครอง' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import Swal from 'sweetalert2'

const props = defineProps<{
  guardian?: any
  studentId: number
  academyId: number
}>()

const emit = defineEmits(['close', 'saved'])

const { $api } = useNuxtApp()

const isEdit = computed(() => !!props.guardian)
const saving = ref(false)

const guardianTypes = [
  { value: 'father', label: 'บิดา', icon: '👨' },
  { value: 'mother', label: 'มารดา', icon: '👩' },
  { value: 'grandfather', label: 'ปู่/ตา', icon: '👴' },
  { value: 'grandmother', label: 'ย่า/ยาย', icon: '👵' },
  { value: 'uncle', label: 'ลุง/อา', icon: '👨' },
  { value: 'aunt', label: 'ป้า/น้า', icon: '👩' },
  { value: 'sibling', label: 'พี่/น้อง', icon: '🧑' },
  { value: 'other', label: 'อื่นๆ', icon: '👤' },
]

const form = ref({
  guardian_type: 'father',
  title_prefix: '',
  first_name: '',
  last_name: '',
  citizen_id: '',
  phone: '',
  email: '',
  occupation: '',
  workplace: '',
  monthly_income: null as number | null,
  is_primary_contact: false,
  is_emergency_contact: false,
})

// Populate form if editing
onMounted(() => {
  if (props.guardian) {
    form.value = {
      guardian_type: props.guardian.guardian_type || 'father',
      title_prefix: props.guardian.title_prefix || '',
      first_name: props.guardian.first_name || '',
      last_name: props.guardian.last_name || '',
      citizen_id: props.guardian.citizen_id || '',
      phone: props.guardian.primary_phone || '',
      email: props.guardian.contacts?.find((c: any) => c.contact_type === 'email')?.contact_value || '',
      occupation: props.guardian.occupation || '',
      workplace: props.guardian.workplace || '',
      monthly_income: props.guardian.monthly_income || null,
      is_primary_contact: props.guardian.is_primary_contact || false,
      is_emergency_contact: props.guardian.is_emergency_contact || false,
    }
  }
})

// Submit form
const handleSubmit = async () => {
  if (!form.value.first_name || !form.value.last_name) {
    await Swal.fire({
      icon: 'warning',
      title: 'กรุณากรอกข้อมูล',
      text: 'กรุณากรอกชื่อและนามสกุลของผู้ปกครอง',
    })
    return
  }

  saving.value = true
  try {
    let response
    if (isEdit.value) {
      response = await $api.patch(`/academies/${props.academyId}/guardians/${props.guardian.id}`, form.value)
    } else {
      response = await $api.post(`/academies/${props.academyId}/students/${props.studentId}/guardians`, form.value)
    }

    if (response.data.success) {
      await Swal.fire({
        icon: 'success',
        title: isEdit.value ? 'แก้ไขเรียบร้อย' : 'เพิ่มเรียบร้อย',
        timer: 1500,
        showConfirmButton: false,
      })
      emit('saved')
    }
  } catch (error: any) {
    await Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: error.response?.data?.message || 'ไม่สามารถบันทึกได้',
    })
  } finally {
    saving.value = false
  }
}
</script>
