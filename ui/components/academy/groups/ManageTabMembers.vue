<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

interface Props {
  academyId: number | string
  group: any
}

const props = defineProps<Props>()

const { listMembers, addMember, removeMember, updateMemberRole } = useAcademyGroups()

const members = ref<any[]>([])
const filterText = ref('')
const isLoading = ref(false)
const isAdding = ref(false)

const roleOptions = [
  { value: 'student', label: 'สมาชิก' },
  { value: 'teacher', label: 'ผู้ดูแล' },
  { value: 'admin', label: 'แอดมินกลุ่ม' },
]

const normalizeMember = (item: any) => ({
  id: item?.id,
  userId: item?.id,
  name: item?.name ?? `User #${item?.id ?? 'Unknown'}`,
  email: item?.email ?? '',
  avatar: item?.profile_photo_url ?? item?.profile_photo_path ?? null,
  role: item?.pivot?.role ?? item?.role ?? 'student',
})

const memberIds = computed(() => members.value.map((item) => item.userId))

const filteredMembers = computed(() => {
  const keyword = filterText.value.trim().toLowerCase()
  if (!keyword) return members.value

  return members.value.filter((member) =>
    member.name.toLowerCase().includes(keyword)
      || member.email.toLowerCase().includes(keyword),
  )
})

const fetchMembers = async () => {
  if (!props.group?.id) return

  isLoading.value = true
  try {
    const response: any = await listMembers(props.group.id)
    members.value = (response?.members ?? []).map(normalizeMember)
  } finally {
    isLoading.value = false
  }
}

watch(() => props.group?.id, () => {
  void fetchMembers()
}, { immediate: true })

const handleAdd = async (member: any) => {
  if (!props.group?.id || isAdding.value) return

  isAdding.value = true
  try {
    await addMember(props.group.id, member.userId, 'student')
    members.value.unshift(normalizeMember(member.raw?.user ?? member.raw))
    members.value[0].userId = member.userId
    members.value[0].role = 'student'
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'เพิ่มสมาชิกไม่สำเร็จ',
      text: error?.data?.message || 'ไม่สามารถเพิ่มสมาชิกเข้ากลุ่มได้',
    })
  } finally {
    isAdding.value = false
  }
}

const handleRoleChange = async (member: any, role: string) => {
  const previousRole = member.role
  member.role = role

  try {
    await updateMemberRole(props.group.id, member.userId, role as 'student' | 'teacher' | 'admin')
  } catch (error: any) {
    member.role = previousRole
    Swal.fire({
      icon: 'error',
      title: 'เปลี่ยนบทบาทไม่สำเร็จ',
      text: error?.data?.message || '',
    })
  }
}

const handleRemove = async (member: any) => {
  try {
    await removeMember(props.group.id, member.userId)
    members.value = members.value.filter((item) => item.userId !== member.userId)
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'ลบสมาชิกไม่สำเร็จ',
      text: error?.data?.message || '',
    })
  }
}
</script>

<template>
  <div class="space-y-5">
    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_260px] gap-4">
      <AcademyGroupsMemberAutocompleteInput
        :academy-id="academyId"
        :exclude-ids="memberIds"
        placeholder="ค้นหาสมาชิกเพื่อเพิ่มเข้ากลุ่ม"
        @selected="handleAdd"
      />
      <div class="relative">
        <Icon icon="fluent:filter-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
        <input
          v-model="filterText"
          type="text"
          placeholder="กรองรายชื่อในกลุ่ม"
          class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50"
        />
      </div>
    </div>

    <div v-if="isLoading" class="py-10 flex items-center justify-center">
      <Icon icon="svg-spinners:ring-resize" class="w-8 h-8 text-vikinger-purple" />
    </div>

    <div v-else-if="members.length === 0" class="rounded-xl border border-dashed border-gray-200 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">
      ยังไม่มีสมาชิกในส่วนงานนี้
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="member in filteredMembers"
        :key="member.userId"
        class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-4"
      >
        <img
          v-if="member.avatar"
          :src="member.avatar"
          :alt="member.name"
          class="w-11 h-11 rounded-full object-cover"
        />
        <div
          v-else
          class="w-11 h-11 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-300 font-semibold"
        >
          {{ member.name.charAt(0) }}
        </div>
        <div class="flex-1 min-w-0">
          <div class="font-medium text-gray-900 dark:text-white truncate">{{ member.name }}</div>
          <div class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ member.email }}</div>
        </div>
        <select
          :value="member.role"
          class="px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-700 dark:text-gray-300"
          @change="handleRoleChange(member, ($event.target as HTMLSelectElement).value)"
        >
          <option v-for="role in roleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
        </select>
        <button
          type="button"
          class="p-2 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20"
          @click="handleRemove(member)"
        >
          <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
        </button>
      </div>
    </div>
  </div>
</template>
