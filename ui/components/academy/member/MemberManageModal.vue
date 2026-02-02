<script setup lang="ts">
import { ref, computed, watch } from 'vue'

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
  academyId: number
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

function close() {
  isOpen.value = false
}
</script>

<template>
  <UModal v-model="isOpen" :ui="{ width: 'sm:max-w-lg' }">
    <UCard>
      <template #header>
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            จัดการสมาชิก
          </h3>
          <UButton
            icon="i-heroicons-x-mark"
            color="gray"
            variant="ghost"
            size="sm"
            @click="close"
          />
        </div>
      </template>

      <div class="space-y-4">
        <!-- Member Header -->
        <div v-if="member" class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
          <UAvatar
            :src="member.member_avatar"
            :alt="member.member_name"
            size="xl"
          />
          <div class="flex-1">
            <h4 class="font-semibold text-gray-900 dark:text-white text-lg">
              {{ member.member_name }}
            </h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ member.member_code || member.user?.email || '-' }}
            </p>
            <div class="mt-2 flex items-center gap-2">
              <UBadge :color="statusBadge.color" size="sm">
                {{ statusBadge.label }}
              </UBadge>
              <UBadge 
                v-if="member.academy_role" 
                :color="member.academy_role.color || 'gray'" 
                variant="outline"
                size="sm"
              >
                {{ member.academy_role.display_name_th }}
              </UBadge>
            </div>
          </div>
        </div>

        <!-- Tabs -->
        <UTabs 
          v-model="activeTab"
          :items="[
            { label: 'ข้อมูล', slot: 'info' },
            { label: 'แก้ไข', slot: 'edit' },
            { label: 'การดำเนินการ', slot: 'actions' },
          ]"
        >
          <template #info>
            <div class="space-y-3 pt-4">
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
          </template>

          <template #edit>
            <div class="space-y-4 pt-4">
              <UFormGroup label="รหัสสมาชิก">
                <UInput v-model="editForm.member_code" placeholder="เช่น STD001" />
              </UFormGroup>
              
              <UFormGroup label="วันที่เข้าเรียน">
                <UInput v-model="editForm.enrollment_date" type="date" />
              </UFormGroup>
              
              <UFormGroup label="วันที่จบการศึกษา">
                <UInput v-model="editForm.graduation_date" type="date" />
              </UFormGroup>
              
              <UFormGroup label="หมายเหตุ">
                <UTextarea 
                  v-model="editForm.note_comment" 
                  placeholder="บันทึกข้อมูลเพิ่มเติม..."
                  :rows="3"
                />
              </UFormGroup>

              <div class="flex justify-end">
                <UButton
                  color="primary"
                  :loading="loading"
                  @click="updateMember"
                >
                  บันทึกการเปลี่ยนแปลง
                </UButton>
              </div>
            </div>
          </template>

          <template #actions>
            <div class="space-y-3 pt-4">
              <!-- Suspend/Unsuspend -->
              <div v-if="member?.status === 5" class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                <div class="flex items-center justify-between">
                  <div>
                    <h5 class="font-medium text-orange-800 dark:text-orange-200">
                      สมาชิกถูกระงับ
                    </h5>
                    <p class="text-sm text-orange-600 dark:text-orange-300">
                      {{ member.note_comment || 'ไม่ระบุเหตุผล' }}
                    </p>
                  </div>
                  <UButton
                    color="green"
                    variant="soft"
                    :loading="loading"
                    @click="unsuspendMember"
                  >
                    ยกเลิกการระงับ
                  </UButton>
                </div>
              </div>

              <div v-else class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                <div class="flex items-center justify-between">
                  <div>
                    <h5 class="font-medium text-orange-800 dark:text-orange-200">
                      ระงับสมาชิกชั่วคราว
                    </h5>
                    <p class="text-sm text-orange-600 dark:text-orange-300">
                      สมาชิกจะไม่สามารถเข้าถึงเนื้อหาได้ชั่วคราว
                    </p>
                  </div>
                  <UButton
                    color="orange"
                    variant="soft"
                    :loading="loading"
                    @click="suspendMember"
                  >
                    ระงับ
                  </UButton>
                </div>
              </div>

              <!-- Remove -->
              <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <div class="flex items-center justify-between">
                  <div>
                    <h5 class="font-medium text-red-800 dark:text-red-200">
                      ลบสมาชิกออกจากโรงเรียน
                    </h5>
                    <p class="text-sm text-red-600 dark:text-red-300">
                      การดำเนินการนี้ไม่สามารถย้อนกลับได้
                    </p>
                  </div>
                  <UButton
                    color="red"
                    variant="soft"
                    :loading="loading"
                    @click="removeMember"
                  >
                    ลบสมาชิก
                  </UButton>
                </div>
              </div>
            </div>
          </template>
        </UTabs>
      </div>

      <template #footer>
        <div class="flex justify-end">
          <UButton color="gray" variant="ghost" @click="close">
            ปิด
          </UButton>
        </div>
      </template>
    </UCard>
  </UModal>
</template>
