<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'

// Types defined locally
interface AcademyRole {
  id: number
  name: string
  display_name_th: string
  display_name_en?: string
  color: string
}

interface AcademyMember {
  id: number
  member_name: string
  member_avatar: string
  member_code?: string
  status: number
  academy_role_id?: number | null
  academy_role?: AcademyRole | null
  enrollment_date?: string
  graduation_date?: string
  note_comment?: string
  created_at?: string
  user?: { email?: string } | null
  student?: { student_id: string; current_classroom?: string; class_level?: string; class_section?: string } | null
  inviter?: { name: string } | null
}

interface Props {
  modelValue: boolean
  member: AcademyMember | null
  academyId: number | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'member-removed': [memberId: number]
  'member-suspended': [member: AcademyMember]
  'member-updated': [member: AcademyMember]
}>()

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const { $axios, $swal } = useNuxtApp() as { $axios: any; $swal: any }

const loading = ref(false)
const activeTab = ref('info')
const editForm = ref({
  member_code: '',
  note_comment: '',
  enrollment_date: '',
  graduation_date: '',
})

// Sync form with member data
watch(() => props.member, (member) => {
  if (member) {
    editForm.value = {
      member_code: member.member_code || '',
      note_comment: member.note_comment || '',
      enrollment_date: member.enrollment_date || '',
      graduation_date: member.graduation_date || '',
    }
  }
}, { immediate: true })

const statusBadge = computed(() => {
  const statuses: Record<number, { label: string; color: string }> = {
    1: { label: 'รอการอนุมัติ', color: 'yellow' },
    2: { label: 'สมาชิก', color: 'green' },
    3: { label: 'ถูกปฏิเสธ', color: 'red' },
    4: { label: 'ได้รับเชิญ', color: 'blue' },
    5: { label: 'ระงับ', color: 'orange' },
  }
  return statuses[props.member?.status || 0] || { label: 'ไม่ทราบ', color: 'gray' }
})

const getStatusBadgeClass = (color: string) => {
  const colors: Record<string, string> = {
    yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200',
    green: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200',
    red: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200',
    blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200',
    orange: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-200',
    gray: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
  }
  return colors[color] || colors.gray
}

const getRoleBadgeClass = (color: string) => {
  const colors: Record<string, string> = {
    purple: 'border-purple-300 bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-200',
    blue: 'border-blue-300 bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200',
    green: 'border-green-300 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-200',
    gray: 'border-gray-300 bg-gray-50 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
  }
  return colors[color] || colors.gray
}

async function updateMember() {
  if (!props.member) return

  loading.value = true
  try {
    const response = await $axios.patch(
      `/academies/${props.academyId}/members/${props.member.id}`,
      editForm.value
    )

    if (response.data.success) {
      $swal?.fire('สำเร็จ', 'อัพเดทข้อมูลสมาชิกเรียบร้อยแล้ว', 'success')
      emit('member-updated', response.data.member)
      isOpen.value = false
    }
  } catch (error) {
    console.error('Error updating member:', error)
    $swal?.fire('เกิดข้อผิดพลาด', 'ไม่สามารถอัพเดทข้อมูลได้', 'error')
  } finally {
    loading.value = false
  }
}

async function suspendMember() {
  if (!props.member) return

  const result = await $swal?.fire({
    title: 'ยืนยันการระงับสมาชิก',
    text: `คุณต้องการระงับ ${props.member.member_name} หรือไม่?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#f59e0b',
    cancelButtonText: 'ยกเลิก',
    confirmButtonText: 'ระงับสมาชิก',
    input: 'textarea',
    inputLabel: 'เหตุผล (ไม่บังคับ)',
    inputPlaceholder: 'ระบุเหตุผลในการระงับ...',
  })

  if (!result?.isConfirmed) return

  loading.value = true
  try {
    const response = await $axios.post(
      `/academies/${props.academyId}/members/${props.member.id}/suspend`,
      { reason: result.value || '' }
    )

    if (response.data.success) {
      $swal?.fire('สำเร็จ', 'ระงับสมาชิกเรียบร้อยแล้ว', 'success')
      emit('member-suspended', response.data.member)
      isOpen.value = false
    }
  } catch (error) {
    console.error('Error suspending member:', error)
    $swal?.fire('เกิดข้อผิดพลาด', 'ไม่สามารถระงับสมาชิกได้', 'error')
  } finally {
    loading.value = false
  }
}

async function unsuspendMember() {
  if (!props.member) return

  const result = await $swal?.fire({
    title: 'ยกเลิกการระงับสมาชิก',
    text: `คุณต้องการยกเลิกการระงับ ${props.member.member_name} หรือไม่?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#10b981',
    cancelButtonText: 'ยกเลิก',
    confirmButtonText: 'ยกเลิกการระงับ',
  })

  if (!result?.isConfirmed) return

  loading.value = true
  try {
    const response = await $axios.post(
      `/academies/${props.academyId}/members/${props.member.id}/unsuspend`
    )

    if (response.data.success) {
      $swal?.fire('สำเร็จ', 'ยกเลิกการระงับสมาชิกเรียบร้อยแล้ว', 'success')
      emit('member-updated', response.data.member)
      isOpen.value = false
    }
  } catch (error) {
    console.error('Error unsuspending member:', error)
    $swal?.fire('เกิดข้อผิดพลาด', 'ไม่สามารถยกเลิกการระงับได้', 'error')
  } finally {
    loading.value = false
  }
}

async function removeMember() {
  if (!props.member) return

  const result = await $swal?.fire({
    title: 'ยืนยันการลบสมาชิก',
    text: `คุณต้องการลบ ${props.member.member_name} ออกจากโรงเรียนหรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonText: 'ยกเลิก',
    confirmButtonText: 'ลบสมาชิก',
  })

  if (!result?.isConfirmed) return

  loading.value = true
  try {
    const response = await $axios.delete(
      `/academies/${props.academyId}/members/${props.member.id}`
    )

    if (response.data.success) {
      $swal?.fire('สำเร็จ', 'ลบสมาชิกเรียบร้อยแล้ว', 'success')
      emit('member-removed', props.member.id)
      isOpen.value = false
    }
  } catch (error: any) {
    console.error('Error removing member:', error)
    const message = error.response?.data?.message || 'ไม่สามารถลบสมาชิกได้'
    $swal?.fire('เกิดข้อผิดพลาด', message, 'error')
  } finally {
    loading.value = false
  }
}

function closeModal() {
  if (!loading.value) {
    isOpen.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="closeModal"
      >
        <Transition
          enter-active-class="transition-all duration-200"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition-all duration-200"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div 
            v-if="isOpen"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden"
          >
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                จัดการสมาชิก
              </h3>
              <button 
                @click="closeModal"
                :disabled="loading"
                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
              >
                <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
              </button>
            </div>

            <!-- Body -->
            <div class="space-y-4">
              <!-- Member Header -->
              <div v-if="member" class="flex items-center gap-4 p-4 mx-6 mt-6 bg-gray-50 dark:bg-gray-900 rounded-lg">
                <img
                  :src="member.member_avatar"
                  :alt="member.member_name"
                  class="w-16 h-16 rounded-full object-cover"
                />
                <div class="flex-1">
                  <h4 class="font-semibold text-gray-900 dark:text-white text-lg">
                    {{ member.member_name }}
                  </h4>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ member.member_code || member.user?.email || '-' }}
                  </p>
                  <div class="mt-2 flex items-center gap-2">
                    <span 
                      class="px-2 py-1 rounded text-xs font-medium"
                      :class="getStatusBadgeClass(statusBadge.color)"
                    >
                      {{ statusBadge.label }}
                    </span>
                    <span 
                      v-if="member.academy_role" 
                      class="px-2 py-1 rounded border text-xs font-medium"
                      :class="getRoleBadgeClass(member.academy_role.color || 'gray')"
                    >
                      {{ member.academy_role.display_name_th }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Tabs -->
              <div class="px-6">
                <div class="flex border-b border-gray-200 dark:border-gray-700">
                  <button
                    @click="activeTab = 'info'"
                    :class="[
                      'px-4 py-2 text-sm font-medium transition-colors border-b-2',
                      activeTab === 'info'
                        ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                    ]"
                  >
                    ข้อมูล
                  </button>
                  <button
                    @click="activeTab = 'edit'"
                    :class="[
                      'px-4 py-2 text-sm font-medium transition-colors border-b-2',
                      activeTab === 'edit'
                        ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                    ]"
                  >
                    แก้ไข
                  </button>
                  <button
                    @click="activeTab = 'actions'"
                    :class="[
                      'px-4 py-2 text-sm font-medium transition-colors border-b-2',
                      activeTab === 'actions'
                        ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                    ]"
                  >
                    การดำเนินการ
                  </button>
                </div>
              </div>

              <!-- Tab Content -->
              <div class="px-6 pb-6 min-h-[300px]">
                <!-- Info Tab -->
                <div v-if="activeTab === 'info'" class="space-y-3 pt-4">
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="text-xs text-gray-500 dark:text-gray-400">รหัสสมาชิก</label>
                      <p class="font-medium">{{ member?.member_code || '-' }}</p>
                    </div>
                    <div>
                      <label class="text-xs text-gray-500 dark:text-gray-400">วันที่เข้าร่วม</label>
                      <p class="font-medium">{{ member?.enrollment_date || member?.created_at || '-' }}</p>
                    </div>
                    <div>
                      <label class="text-xs text-gray-500 dark:text-gray-400">บทบาท</label>
                      <p class="font-medium">{{ member?.academy_role?.display_name_th || member?.role || 'ไม่ระบุ' }}</p>
                    </div>
                    <div>
                      <label class="text-xs text-gray-500 dark:text-gray-400">ผู้เชิญ</label>
                      <p class="font-medium">{{ member?.inviter?.name || '-' }}</p>
                    </div>
                  </div>
                  
                  <div v-if="member?.student">
                    <label class="text-xs text-gray-500 dark:text-gray-400">ข้อมูลนักเรียน</label>
                    <div class="mt-1 p-3 bg-white dark:bg-gray-900 rounded border">
                      <p><strong>รหัส:</strong> {{ member.student.student_id }}</p>
                      <p><strong>ชั้น:</strong> {{ member.student.current_classroom || `${member.student.class_level}/${member.student.class_section}` }}</p>
                    </div>
                  </div>

                  <div v-if="member?.note_comment">
                    <label class="text-xs text-gray-500 dark:text-gray-400">หมายเหตุ</label>
                    <p class="mt-1 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded text-sm">
                      {{ member.note_comment }}
                    </p>
                  </div>
                </div>

                <!-- Edit Tab -->
                <div v-if="activeTab === 'edit'" class="space-y-4 pt-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      รหัสสมาชิก
                    </label>
                    <input
                      v-model="editForm.member_code"
                      type="text"
                      placeholder="เช่น STD001"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    />
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      วันที่เข้าเรียน
                    </label>
                    <input
                      v-model="editForm.enrollment_date"
                      type="date"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    />
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      วันที่จบการศึกษา
                    </label>
                    <input
                      v-model="editForm.graduation_date"
                      type="date"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    />
                  </div>
                  
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      หมายเหตุ
                    </label>
                    <textarea
                      v-model="editForm.note_comment"
                      placeholder="บันทึกข้อมูลเพิ่มเติม..."
                      rows="3"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
                    ></textarea>
                  </div>

                  <div class="flex justify-end">
                    <button
                      @click="updateMember"
                      :disabled="loading"
                      class="flex items-center gap-2 px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <div v-if="loading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                      <Icon v-else icon="fluent:save-24-filled" class="w-4 h-4" />
                      <span>{{ loading ? 'กำลังบันทึก...' : 'บันทึกการเปลี่ยนแปลง' }}</span>
                    </button>
                  </div>
                </div>

                <!-- Actions Tab -->
                <div v-if="activeTab === 'actions'" class="space-y-3 pt-4">
                  <!-- Suspend/Unsuspend -->
                  <div v-if="member?.status === 5" class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                    <div class="flex items-center justify-between">
                      <div>
                        <h5 class="font-medium text-orange-800 dark:text-orange-200">
                          สมาชิกถูกระงับ
                        </h5>
                        <p class="text-sm text-orange-600 dark:text-orange-300">
                          {{ member.note_comment || 'ไม่ระบุเหตุผล' }}
                        </p>
                      </div>
                      <button
                        @click="unsuspendMember"
                        :disabled="loading"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors disabled:opacity-50"
                      >
                        ยกเลิกการระงับ
                      </button>
                    </div>
                  </div>

                  <div v-else class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                    <div class="flex items-center justify-between">
                      <div>
                        <h5 class="font-medium text-orange-800 dark:text-orange-200">
                          ระงับสมาชิกชั่วคราว
                        </h5>
                        <p class="text-sm text-orange-600 dark:text-orange-300">
                          สมาชิกจะไม่สามารถเข้าถึงเนื้อหาได้ชั่วคราว
                        </p>
                      </div>
                      <button
                        @click="suspendMember"
                        :disabled="loading"
                        class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors disabled:opacity-50"
                      >
                        ระงับ
                      </button>
                    </div>
                  </div>

                  <!-- Remove -->
                  <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                    <div class="flex items-center justify-between">
                      <div>
                        <h5 class="font-medium text-red-800 dark:text-red-200">
                          ลบสมาชิกออกจากโรงเรียน
                        </h5>
                        <p class="text-sm text-red-600 dark:text-red-300">
                          การดำเนินการนี้ไม่สามารถย้อนกลับได้
                        </p>
                      </div>
                      <button
                        @click="removeMember"
                        :disabled="loading"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors disabled:opacity-50"
                      >
                        ลบสมาชิก
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
              <button
                @click="closeModal"
                :disabled="loading"
                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors disabled:opacity-50"
              >
                ปิด
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
