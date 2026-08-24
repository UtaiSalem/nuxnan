<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({ layout: 'main' })

const route = useRoute()
const router = useRouter()
const api = useApi()

const academyName = computed(() => String(route.params.name))
const departmentId = computed(() => Number(route.params.id))

const academy = ref<any>(null)
const department = ref<any>(null)
const head = ref<any>(null)
const tree = ref<{ parent: any | null, children: any[] }>({ parent: null, children: [] })
const members = ref<any[]>([])
const permissions = ref<any[]>([])
const activeTab = ref<string>('overview')
const isLoading = ref(true)
const isMembersLoading = ref(false)
const errorMessage = ref('')
const showPicker = ref(false)

const tabs = [
  { key: 'overview', label: 'ภาพรวม', icon: 'fluent:apps-24-regular' },
  { key: 'members', label: 'สมาชิก', icon: 'fluent:people-24-regular' },
  { key: 'permissions', label: 'บทบาทและสิทธิ์', icon: 'fluent:lock-closed-key-24-regular' },
  { key: 'workspace', label: 'งานและเอกสาร', icon: 'fluent:clipboard-task-24-regular' },
  { key: 'settings', label: 'ตั้งค่าฝ่าย', icon: 'fluent:settings-24-regular' },
  { key: 'activity', label: 'ประวัติการใช้งาน', icon: 'fluent:history-24-regular' }
]

const setTab = (key: string) => {
  activeTab.value = key
  router.replace({ query: { ...route.query, tab: key } })
}

const membersCount = computed(() => department.value?.members_count ?? members.value.length)
const headUserId = computed(() => {
  const id = head.value?.id ?? department.value?.settings?.head_user_id ?? null
  return id ? Number(id) : null
})
const memberUserIds = computed(() => members.value.map(m => m.id).filter(Boolean))

const formatDate = (value: string | null) => value ? new Date(value).toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' }) : '-'

const loadDepartment = async () => {
  try {
    const response: any = await api.get(`/api/academies/${academy.value.id}/departments/${departmentId.value}`, { query: { with_tree: 1 } })
    if (!response.success) throw new Error('ไม่พบฝ่ายงาน')
    department.value = response.data?.department || response.department
    head.value = response.data?.head || null
    tree.value = response.data?.tree || { parent: null, children: [] }
  } catch (error: any) {
    throw error
  }
}

const load = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const school: any = await api.get(`/api/academies/${academyName.value}`)
    if (!school.success) throw new Error('ไม่พบโรงเรียน')
    academy.value = school.academy
    
    await loadDepartment()

    const validTabs = tabs.map(t => t.key)
    if (route.query.tab && validTabs.includes(String(route.query.tab))) {
      activeTab.value = String(route.query.tab)
    }

    await Promise.all([loadMembers(), loadPermissions()])
  } catch (error: any) {
    errorMessage.value = error?.message || 'ไม่สามารถโหลดข้อมูลฝ่ายได้'
  } finally {
    isLoading.value = false
  }
}

const loadMembers = async () => {
  if (!academy.value) return
  isMembersLoading.value = true
  try {
    const response: any = await api.get(`/api/academies/${academy.value.id}/departments/${departmentId.value}/members`)
    members.value = response.data?.members || response.members || []
  } finally {
    isMembersLoading.value = false
  }
}

const loadPermissions = async () => {
  if (!academy.value) return
  try {
    const response: any = await api.get(`/api/academies/${academy.value.id}/departments/${departmentId.value}/permissions`)
    permissions.value = response.data?.permissions || response.data?.enabled_keys || []
  } catch {
    permissions.value = []
  }
}

const onChangeRole = async ({ userId, role }: { userId: number, role: string }) => {
  try {
    await api.patch(`/api/academies/${academy.value.id}/departments/${departmentId.value}/members/role`, { user_id: userId, role })
    await loadMembers()
    Swal.fire({ icon: 'success', title: 'อัปเดตบทบาทแล้ว', timer: 1500, showConfirmButton: false })
  } catch (err: any) {
    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: err?.data?.message || err?.response?.data?.message || err?.message || 'ไม่สามารถอัปเดตบทบาทได้' })
  }
}

const onRemoveMember = async (userId: number) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: 'ยืนยันการนำออก',
    text: 'ต้องการนำสมาชิกคนนี้ออกจากฝ่ายหรือไม่',
    showCancelButton: true,
    confirmButtonText: 'นำออก',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })
  if (!result.isConfirmed) return

  try {
    await api.delete(`/api/academies/${academy.value.id}/departments/${departmentId.value}/members`, { body: { user_id: userId } })
    await Promise.all([loadMembers(), loadDepartment()])
  } catch (err: any) {
    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: err?.data?.message || err?.response?.data?.message || err?.message || 'ไม่สามารถนำออกได้' })
  }
}

const onSetHead = async (userId: number) => {
  const result = await Swal.fire({
    icon: 'question',
    title: 'ตั้งเป็นหัวหน้าฝ่าย',
    text: 'ต้องการตั้งสมาชิกคนนี้เป็นหัวหน้าฝ่ายหรือไม่',
    showCancelButton: true,
    confirmButtonText: 'ยืนยัน',
    cancelButtonText: 'ยกเลิก'
  })
  if (!result.isConfirmed) return

  try {
    await api.patch(`/api/academies/${academy.value.id}/departments/${departmentId.value}`, { head_user_id: userId })
    await loadDepartment()
    Swal.fire({ icon: 'success', title: 'ตั้งหัวหน้าฝ่ายแล้ว', timer: 1500, showConfirmButton: false })
  } catch (err: any) {
    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: err?.data?.message || err?.response?.data?.message || err?.message || 'ไม่สามารถตั้งหัวหน้าได้' })
  }
}

const onClearHead = async () => {
  try {
    await api.patch(`/api/academies/${academy.value.id}/departments/${departmentId.value}`, { head_user_id: null })
    await loadDepartment()
    Swal.fire({ icon: 'success', title: 'ยกเลิกหัวหน้าฝ่ายแล้ว', timer: 1500, showConfirmButton: false })
  } catch (err: any) {
    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: err?.data?.message || err?.response?.data?.message || err?.message || 'ไม่สามารถยกเลิกหัวหน้าได้' })
  }
}

const onMembersAdded = async () => {
  showPicker.value = false
  await Promise.all([loadMembers(), loadDepartment()])
}

const onDeleteDepartment = async () => {
  const result = await Swal.fire({
    icon: 'warning',
    title: 'ยืนยันการลบฝ่าย',
    text: `คุณต้องการลบฝ่ายนี้หรือไม่? (ต้องย้ายสมาชิกออกให้หมดก่อน)`,
    showCancelButton: true,
    confirmButtonText: 'ลบฝ่าย',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })
  if (!result.isConfirmed) return

  try {
    const res: any = await api.delete(`/api/academies/${academy.value.id}/departments/${departmentId.value}`)
    if (res.success) {
      navigateTo(`/academies/${academyName.value}/admin/departments`)
    } else {
      Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: res.message || 'ไม่สามารถลบฝ่ายได้' })
    }
  } catch (err: any) {
    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: err?.data?.message || err?.response?.data?.message || err?.message || 'ไม่สามารถลบฝ่ายได้' })
  }
}

const openPicker = () => {
  showPicker.value = true
}

const onDepartmentUpdated = (next: any) => {
  department.value = { ...department.value, ...next }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <NuxtLink :to="`/academies/${academyName}/admin/departments`" class="inline-flex min-h-[44px] items-center gap-2 text-sm font-medium text-gray-500 hover:text-primary-600">
      <Icon icon="fluent:arrow-left-24-regular" class="h-4 w-4" /> กลับรายการฝ่าย
    </NuxtLink>

    <div v-if="isLoading" class="flex justify-center py-20"><div class="h-10 w-10 animate-spin rounded-full border-4 border-primary-500 border-t-transparent" /></div>
    <div v-else-if="errorMessage" class="rounded-2xl bg-red-50 p-8 text-center text-red-700">{{ errorMessage }}</div>
    <template v-else-if="department && academy">
      <AcademyDepartmentsDetailHeader
        :department="department"
        :academy-name="academyName"
        :parent="tree.parent"
        :head="head"
        :members-count="membersCount"
        :member-avatars="members"
        @add-member="openPicker"
      />

      <AcademyDepartmentsStatCards
        :members-count="membersCount"
        :head-name="head?.name || null"
        :permission-count="permissions.length"
        :sub-unit-count="tree.children.length"
      />

      <nav class="flex gap-1 overflow-x-auto rounded-2xl border border-gray-100 bg-white p-2 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          class="flex min-h-[44px] flex-shrink-0 items-center gap-2 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-medium transition sm:px-4"
          :class="activeTab === tab.key ? 'bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-300' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700'"
          @click="setTab(tab.key)"
        >
          <Icon :icon="tab.icon" class="h-5 w-5" /> {{ tab.label }}
        </button>
      </nav>

      <div v-if="activeTab === 'overview'" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:col-span-2">
          <h2 class="mb-4 font-bold text-gray-900 dark:text-white">ข้อมูลฝ่าย</h2>
          <dl class="space-y-4">
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
              <dt class="text-sm text-gray-500">รายละเอียด</dt>
              <dd class="min-w-0 flex-1 break-words font-medium sm:text-right">{{ department.description || 'ยังไม่มีคำอธิบาย' }}</dd>
            </div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
              <dt class="text-sm text-gray-500">ประเภท</dt>
              <dd class="flex-shrink-0 whitespace-nowrap font-medium">ฝ่ายงาน</dd>
            </div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
              <dt class="text-sm text-gray-500">สังกัด</dt>
              <dd class="flex-shrink-0 whitespace-nowrap font-medium">{{ tree.parent?.name || '—' }}</dd>
            </div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
              <dt class="text-sm text-gray-500">สร้างเมื่อ</dt>
              <dd class="flex-shrink-0 whitespace-nowrap font-medium">{{ formatDate(department.created_at) }}</dd>
            </div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
              <dt class="text-sm text-gray-500">จำนวนสมาชิก</dt>
              <dd class="flex-shrink-0 whitespace-nowrap font-medium">{{ membersCount }} คน</dd>
            </div>
            <div class="flex flex-col gap-0.5 sm:flex-row sm:justify-between">
              <dt class="text-sm text-gray-500">สิทธิ์ที่เปิดใช้งาน</dt>
              <dd class="flex-shrink-0 whitespace-nowrap font-medium">{{ permissions.length }} สิทธิ์</dd>
            </div>
          </dl>
        </section>

        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <h2 class="mb-4 font-bold text-gray-900 dark:text-white">หัวหน้าฝ่าย</h2>
          <div v-if="head" class="flex flex-col items-center gap-3 text-center">
            <img :src="head.profile_photo_url || head.avatar || '/images/default-avatar.png'" :alt="head.name" class="h-16 w-16 rounded-full object-cover" />
            <div>
              <p class="font-medium text-gray-900 dark:text-white">{{ head.name }}</p>
              <p class="text-sm text-gray-500">{{ head.email || '-' }}</p>
            </div>
            <button @click="onClearHead" class="mt-2 min-h-[44px] rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">ยกเลิกการเป็นหัวหน้า</button>
          </div>
          <div v-else class="text-center">
            <p class="text-gray-500">ยังไม่กำหนดหัวหน้าฝ่าย</p>
            <p class="mt-1 text-sm text-gray-400">เลือกจากแท็บสมาชิก</p>
            <button @click="setTab('members')" class="mt-4 min-h-[44px] rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">ไปที่แท็บสมาชิก</button>
          </div>
        </section>

        <div class="lg:col-span-2">
          <AcademyDepartmentsSubUnitsCard :children="tree.children" :academy-name="academyName" />
        </div>

        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <h2 class="mb-4 font-bold text-gray-900 dark:text-white">สิทธิ์ที่เปิดใช้อยู่</h2>
          <div v-if="permissions.length === 0" class="text-center">
            <p class="text-gray-500">ฝ่ายนี้ยังไม่ได้รับสิทธิ์ใด</p>
            <button @click="setTab('permissions')" class="mt-4 min-h-[44px] rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700">ไปที่แท็บสิทธิ์</button>
          </div>
          <div v-else class="flex flex-wrap gap-1.5">
            <span v-for="p in permissions" :key="p" class="break-words rounded-lg bg-gray-100 px-2 py-1 text-sm text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ p }}</span>
          </div>
        </section>

        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:col-span-3">
          <h2 class="mb-4 font-bold text-gray-900 dark:text-white">ลิงก์ที่เกี่ยวข้อง</h2>
          <p class="mb-4 text-sm text-gray-500">หมายเหตุ: ประกาศของฝ่ายจะใช้ระบบประกาศกลางของโรงเรียน</p>
          <div class="flex flex-col gap-2 sm:flex-row">
            <NuxtLink :to="`/academies/${academyName}/admin/reports?scope_type=department&scope_id=${departmentId}`" class="flex min-h-[44px] items-center justify-center rounded-xl bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">รายงานของฝ่าย</NuxtLink>
            <NuxtLink :to="`/academies/${academyName}/admin/announcements`" class="flex min-h-[44px] items-center justify-center rounded-xl bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">ประกาศของโรงเรียน</NuxtLink>
            <NuxtLink :to="`/academies/${academyName}/admin/departments`" class="flex min-h-[44px] items-center justify-center rounded-xl bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">รายการฝ่ายทั้งหมด</NuxtLink>
          </div>
        </section>
      </div>

      <div v-else-if="activeTab === 'members'">
        <AcademyDepartmentsMembersPanel
          :members="members"
          :is-loading="isMembersLoading"
          :head-user-id="headUserId"
          @add-member="openPicker"
          @change-role="onChangeRole"
          @remove="onRemoveMember"
          @set-head="onSetHead"
        />
      </div>

      <section v-else-if="activeTab === 'permissions'" class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 rounded-xl bg-blue-50 p-4 text-sm text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">สิทธิ์บางประเภทไม่สามารถมอบให้ฝ่ายได้ตามข้อกำหนด (เช่น สิทธิ์ระดับผู้ดูแลระบบสูงสุด)</div>
        <AcademyGroupsManageTabPermissions :academy-id="academy.id" :group="department" />
      </section>
      
      <div v-else-if="activeTab === 'workspace'">
        <AcademyScopesScopedWorkspace :academy-id="academy.id" scope-type="department" :scope-id="department.id" />
      </div>

      <div v-else-if="activeTab === 'settings'" class="space-y-4">
        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <h2 class="mb-4 font-bold text-gray-900 dark:text-white">แก้ไขข้อมูลฝ่าย</h2>
          <AcademyGroupsManageTabInfo :group="department" @updated="onDepartmentUpdated" />
        </section>
        
        <section class="rounded-2xl border border-red-200 bg-white p-5 shadow-sm dark:border-red-900/50 dark:bg-gray-800">
          <h2 class="mb-2 font-bold text-red-600 dark:text-red-400">พื้นที่อันตราย</h2>
          <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">การลบฝ่ายงานไม่สามารถกู้คืนได้ และต้องย้ายสมาชิกออกให้หมดก่อนลบ</p>
          <button @click="onDeleteDepartment" class="min-h-[44px] rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">ลบฝ่ายงาน</button>
        </section>
      </div>

      <div v-else-if="activeTab === 'activity'">
        <AcademyDepartmentsActivityTab :academy-id="academy.id" :department-id="departmentId" />
      </div>

      <AcademyDepartmentsMemberPicker
        :visible="showPicker"
        :academy-id="academy.id"
        :department-id="departmentId"
        :exclude-user-ids="memberUserIds"
        @close="showPicker = false"
        @added="onMembersAdded"
      />
    </template>
  </div>
</template>
