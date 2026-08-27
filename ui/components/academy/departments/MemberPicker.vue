<template>
  <Teleport to="body">
    <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="emit('close')"></div>
      <div class="relative flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-gray-800">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-100 p-4 dark:border-gray-700">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white">เพิ่มสมาชิกเข้าฝ่าย</h3>
          <button type="button" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-400 hover:bg-gray-50 hover:text-gray-500 dark:hover:bg-gray-700" @click="emit('close')">
            <Icon icon="fluent:dismiss-24-regular" class="h-6 w-6" />
          </button>
        </div>
        
        <!-- Search and filters -->
        <div class="flex flex-col gap-3 border-b border-gray-100 p-4 dark:border-gray-700">
          <div class="flex gap-2">
            <div class="relative min-w-0 flex-1">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <Icon icon="fluent:search-24-regular" class="h-5 w-5 text-gray-400" />
              </div>
              <input v-model="memberSearchQuery" type="text" placeholder="ค้นหาชื่อ, อีเมล..." class="block min-h-[44px] w-full rounded-xl border border-gray-300 bg-white py-2 pl-10 pr-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" @input="scheduleMemberSearch" />
            </div>
          </div>
          <div class="flex gap-3">
            <select v-model="memberRoleFilter" class="min-w-0 block min-h-[44px] w-full flex-1 rounded-xl border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" @change="fetchAvailableMembers(1)">
              <option value="staff">บุคลากร/ครู</option>
              <option value="all">ทุกคน</option>
            </select>
            <select v-model="memberRole" class="min-w-0 block min-h-[44px] w-full flex-1 rounded-xl border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
              <option value="member">สมาชิก</option>
              <option value="staff">เจ้าหน้าที่</option>
              <option value="admin">ผู้ดูแลฝ่าย</option>
              <option value="head">หัวหน้าฝ่าย</option>
            </select>
          </div>
        </div>
        
        <!-- Summary & Bulk actions -->
        <div class="flex items-center justify-between bg-gray-50 px-4 py-2 text-sm dark:bg-gray-900/50">
          <span class="text-gray-600 dark:text-gray-400">พบ {{ memberResultsPagination.total || 0 }} คน • เลือกแล้ว {{ selectedMemberIds.length }} คน</span>
          <div class="flex gap-2">
            <button type="button" class="text-primary-600 hover:text-primary-700 font-medium dark:text-primary-400" @click="selectAllMatchingMembers">เลือกทั้งหมด</button>
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <button type="button" class="text-gray-600 hover:text-gray-800 dark:text-gray-400" @click="clearMemberSelection">ล้าง</button>
          </div>
        </div>
        
        <!-- Results List -->
        <div class="flex-1 overflow-y-auto p-2">
          <div v-if="isLoadingAvailableMembers" class="flex justify-center p-8">
            <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary-500 border-t-transparent"></div>
          </div>
          <div v-else-if="availableMembers.length === 0" class="flex flex-col items-center justify-center p-8 text-gray-500">
            <Icon icon="fluent:search-24-regular" class="mb-2 h-8 w-8 opacity-50" />
            <p>ไม่พบสมาชิกที่ค้นหา</p>
          </div>
          <div v-else class="flex flex-col gap-1">
            <label v-for="member in availableMembers" :key="member.user_id" class="flex cursor-pointer items-start gap-3 rounded-lg p-2 hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <div class="flex h-10 items-center">
                <input v-model="selectedMemberIds" :value="member.user_id" type="checkbox" class="h-5 w-5 flex-shrink-0 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:checked:bg-primary-500" />
              </div>
              <img :src="member.user?.profile_photo_url || member.user?.avatar || member.profile_photo_url || member.avatar || '/images/default-avatar.png'" class="h-10 w-10 flex-shrink-0 rounded-full object-cover border border-gray-200 dark:border-gray-700" alt="" />
              <div class="min-w-0 flex-1 break-words">
                <div class="font-medium text-gray-900 dark:text-white">{{ member.user?.name || member.name }}</div>
                <div class="text-xs text-gray-500">{{ member.user?.email || member.email }}</div>
                <div v-if="member.department_memberships?.length" class="mt-1.5 flex flex-wrap gap-1.5">
                  <span v-for="dept in member.department_memberships" :key="dept.id" class="inline-block max-w-full break-words rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                    {{ dept.name || dept.department?.name }}
                  </span>
                </div>
              </div>
            </label>
          </div>
        </div>
        
        <!-- Pagination -->
        <div v-if="memberResultsPagination.last_page > 1" class="flex items-center justify-between border-t border-gray-100 px-4 py-2 dark:border-gray-700">
          <button type="button" class="min-h-[36px] px-3 text-sm font-medium text-gray-600 hover:text-gray-900 disabled:opacity-50 dark:text-gray-400 dark:hover:text-white" :disabled="memberResultsPagination.current_page <= 1" @click="fetchAvailableMembers(memberResultsPagination.current_page - 1)">
            ก่อนหน้า
          </button>
          <span class="text-sm text-gray-500">หน้า {{ memberResultsPagination.current_page }} / {{ memberResultsPagination.last_page }}</span>
          <button type="button" class="min-h-[36px] px-3 text-sm font-medium text-gray-600 hover:text-gray-900 disabled:opacity-50 dark:text-gray-400 dark:hover:text-white" :disabled="memberResultsPagination.current_page >= memberResultsPagination.last_page" @click="fetchAvailableMembers(memberResultsPagination.current_page + 1)">
            ถัดไป
          </button>
        </div>
        
        <!-- Footer actions -->
        <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/50">
          <button type="button" class="min-h-[44px] rounded-xl px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-700" @click="emit('close')">
            ยกเลิก
          </button>
          <button type="button" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl bg-primary-600 px-6 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50" :disabled="selectedMemberIds.length === 0 || isSubmitting" @click="submitMembers">
            <span v-if="isSubmitting" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
            <span>เพิ่ม {{ selectedMemberIds.length }} คน</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, onBeforeUnmount } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

const props = defineProps<{
  visible: boolean
  academyId: number | string
  departmentId: number
  excludeUserIds: number[]
}>()

const emit = defineEmits(['close', 'added'])

const api = useApi()

const memberSearchQuery = ref('')
const memberRoleFilter = ref('staff')
const memberRole = ref('member')
const selectedMemberIds = ref<number[]>([])
const availableMembers = ref<any[]>([])
const isLoadingAvailableMembers = ref(false)
const isSubmitting = ref(false)
const memberResultsPagination = ref({ current_page: 1, last_page: 1, total: 0 })

let memberSearchTimer: any = null

const scheduleMemberSearch = () => {
  if (memberSearchTimer) clearTimeout(memberSearchTimer)
  memberSearchTimer = setTimeout(() => fetchAvailableMembers(1), 300)
}

const fetchAvailableMembers = async (page = 1) => {
  if (!props.academyId) return
  isLoadingAvailableMembers.value = true
  try {
    const query: Record<string, any> = {
      search: memberSearchQuery.value || undefined,
      status: 2,
      page,
      per_page: 25,
      with_departments: 1
    }
    // ofetch serialises arrays as repeated keys (roles=teacher&roles=staff) and PHP keeps
    // only the last one, so the filter silently became role='staff' (0 members).
    // The bracketed key is what PHP parses back into an array.
    if (memberRoleFilter.value === 'staff') query['roles[]'] = ['teacher', 'staff']

    const response: any = await api.get(`/api/academies/${props.academyId}/members/search`, { query })

    if (response.success) {
      availableMembers.value = (response.members || []).filter(
        (m: any) => !props.excludeUserIds.includes(m.user_id)
      )
      memberResultsPagination.value = response.pagination || { current_page: page, last_page: page, total: availableMembers.value.length }
    }
  } catch (err) {
    console.error('Failed to fetch available members:', err)
  } finally {
    isLoadingAvailableMembers.value = false
  }
}

const selectAllMatchingMembers = () => {
  const ids = availableMembers.value.map((member: any) => member.user_id).filter(Boolean)
  selectedMemberIds.value = Array.from(new Set([...selectedMemberIds.value, ...ids]))
}

const clearMemberSelection = () => { selectedMemberIds.value = [] }

const submitMembers = async () => {
  if (selectedMemberIds.value.length === 0 || isSubmitting.value) return
  isSubmitting.value = true
  try {
    const response: any = await api.post(`/api/academies/${props.academyId}/departments/${props.departmentId}/members/bulk`, {
      user_ids: selectedMemberIds.value,
      role: memberRole.value
    })
    
    if (response.success) {
      Swal.fire({
        icon: 'success',
        title: 'สำเร็จ',
        text: response.message,
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '#3b82f6'
      })
      emit('added', response.data || { added: selectedMemberIds.value.length, skipped: 0 })
      emit('close')
    }
  } catch (err: any) {
    const msg = err?.data?.message || err?.response?.data?.message || 'ไม่สามารถเพิ่มสมาชิกได้'
    Swal.fire({
      icon: 'error',
      title: 'ผิดพลาด',
      text: msg,
      confirmButtonText: 'ตกลง',
      confirmButtonColor: '#3b82f6'
    })
  } finally {
    isSubmitting.value = false
  }
}

watch(() => props.visible, (val) => {
  if (val) {
    memberSearchQuery.value = ''
    memberRoleFilter.value = 'staff'
    memberRole.value = 'member'
    selectedMemberIds.value = []
    fetchAvailableMembers(1)
  }
})

onBeforeUnmount(() => {
  if (memberSearchTimer) clearTimeout(memberSearchTimer)
})
</script>
