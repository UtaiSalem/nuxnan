<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

interface Props {
  academyId: number | string
  group: any
}

const props = defineProps<Props>()

const { listAdmins, addAdmin, removeAdmin, updateAdminRole } = useAcademyGroups()

const admins = ref<any[]>([])
const isLoading = ref(false)
const isAdding = ref(false)

const roleOptions = [
  { value: 'leader', label: 'หัวหน้า' },
  { value: 'co_leader', label: 'รองหัวหน้า' },
  { value: 'advisor', label: 'ที่ปรึกษา' },
]

const normalizeAdmin = (item: any) => {
  const user = item?.user ?? {}
  return {
    id: item?.id ?? user?.id,
    userId: item?.user_id ?? user?.id,
    name: user?.name ?? `User #${item?.user_id ?? 'Unknown'}`,
    email: user?.email ?? '',
    avatar: user?.profile_photo_url ?? user?.profile_photo_path ?? null,
    role: item?.role ?? 'leader',
    appointer: item?.appointer ?? null,
    created_at: item?.created_at ?? null,
  }
}

const adminIds = computed(() => admins.value.map((item) => item.userId))

const fetchAdmins = async () => {
  if (!props.group?.id) return

  isLoading.value = true
  try {
    const response: any = await listAdmins(props.group.id)
    admins.value = (response?.admins ?? []).map(normalizeAdmin)
  } finally {
    isLoading.value = false
  }
}

watch(() => props.group?.id, () => {
  void fetchAdmins()
}, { immediate: true })

const handleAdd = async (member: any) => {
  if (!props.group?.id || isAdding.value) return

  isAdding.value = true
  try {
    const response: any = await addAdmin(props.group.id, member.userId, 'leader')
    admins.value.unshift(normalizeAdmin(response?.admin ?? { user_id: member.userId, role: 'leader', user: member.raw?.user ?? member.raw }))

    Swal.fire({
      icon: 'success',
      title: 'เพิ่มหัวหน้าส่วนงานแล้ว',
      timer: 1500,
      showConfirmButton: false,
    })
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'เพิ่มหัวหน้าไม่สำเร็จ',
      text: error?.data?.message || 'ไม่สามารถเพิ่มหัวหน้าส่วนงานได้',
    })
  } finally {
    isAdding.value = false
  }
}

const handleRoleChange = async (admin: any, role: string) => {
  const previousRole = admin.role
  admin.role = role

  try {
    await updateAdminRole(props.group.id, admin.userId, role as 'leader' | 'co_leader' | 'advisor')
  } catch (error: any) {
    admin.role = previousRole
    Swal.fire({
      icon: 'error',
      title: 'เปลี่ยนบทบาทไม่สำเร็จ',
      text: error?.data?.message || '',
    })
  }
}

const handleRemove = async (admin: any) => {
  try {
    await removeAdmin(props.group.id, admin.userId)
    admins.value = admins.value.filter((item) => item.userId !== admin.userId)
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'ลบหัวหน้าไม่สำเร็จ',
      text: error?.data?.message || '',
    })
  }
}

const relativeDate = (iso?: string) => {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<template>
  <div class="space-y-5">
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        เพิ่มหัวหน้าส่วนงาน
      </label>
      <AcademyGroupsMemberAutocompleteInput
        :academy-id="academyId"
        :exclude-ids="adminIds"
        placeholder="ค้นหาสมาชิกเพื่อแต่งตั้งเป็นหัวหน้า"
        @selected="handleAdd"
      />
    </div>

    <div v-if="isLoading" class="py-10 flex items-center justify-center">
      <Icon icon="svg-spinners:ring-resize" class="w-8 h-8 text-vikinger-purple" />
    </div>

    <div v-else-if="admins.length === 0" class="rounded-xl border border-dashed border-gray-200 dark:border-gray-700 p-8 text-center text-gray-500 dark:text-gray-400">
      ยังไม่มีหัวหน้าส่วนงาน
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="admin in admins"
        :key="admin.id"
        class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-4"
      >
        <img
          v-if="admin.avatar"
          :src="admin.avatar"
          :alt="admin.name"
          class="w-11 h-11 rounded-full object-cover"
        />
        <div
          v-else
          class="w-11 h-11 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-300 font-semibold"
        >
          {{ admin.name.charAt(0) }}
        </div>
        <div class="flex-1 min-w-0">
          <div class="font-medium text-gray-900 dark:text-white truncate">{{ admin.name }}</div>
          <div class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ admin.email }}</div>
          <div v-if="admin.appointer" class="text-[10px] text-gray-400 mt-0.5">
            แต่งตั้งโดย {{ admin.appointer.name }} · {{ relativeDate(admin.created_at) }}
          </div>
        </div>
        <select
          :value="admin.role"
          class="px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-700 dark:text-gray-300"
          @change="handleRoleChange(admin, ($event.target as HTMLSelectElement).value)"
        >
          <option v-for="role in roleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
        </select>
        <button
          type="button"
          class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20"
          @click="handleRemove(admin)"
        >
          <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
        </button>
      </div>
    </div>
  </div>
</template>
