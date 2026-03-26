<script setup lang="ts">
import { Icon } from '@iconify/vue'

const route = useRoute()
const api = useApi()
const swal = useSweetAlert()

const course = inject<Ref<any>>('course')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')

const courseId = computed(() => route.params.id as string)
const memberId = computed(() => route.params.memberId as string)

const loading = ref(true)
const saving = ref(false)
const member = ref<any>(null)
const groups = ref<any[]>([])

// Form state
const form = ref({
  member_name: '',
  order_number: null as number | null,
  member_code: '',
  group_id: null as number | null,
  course_member_status: 1
})

const config = useRuntimeConfig()

const memberAvatar = computed(() => {
  const user = member.value?.user
  if (!user) return '/images/default-avatar.png'
  if (user.profile_photo_url) return user.profile_photo_url
  if (user.profile_photo_path) return `${config.public.apiBase}/storage/${user.profile_photo_path}`
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name || 'U')}&background=random`
})

const memberDisplayName = computed(() => {
  return form.value.member_name || member.value?.user?.name || '-'
})

const fetchMember = async () => {
  loading.value = true
  try {
    const response: any = await api.get(`/api/courses/${courseId.value}/members/${memberId.value}/member-settings`)
    
    const m = response.member?.data || response.member
    member.value = m
    groups.value = response.groups?.data || response.groups || []
    
    // Populate form
    form.value = {
      member_name: m.member_name || '',
      order_number: m.order_number ?? null,
      member_code: m.member_code || '',
      group_id: m.group_id || null,
      course_member_status: m.course_member_status ?? 1
    }
  } catch (error: any) {
    console.error('Error fetching member:', error)
    swal.error('ไม่สามารถโหลดข้อมูลสมาชิกได้')
  } finally {
    loading.value = false
  }
}

const saveMember = async () => {
  saving.value = true
  try {
    await api.patch(`/api/courses/${courseId.value}/members/${memberId.value}/update`, {
      member_name: form.value.member_name || null,
      order_number: form.value.order_number,
      member_code: form.value.member_code || null,
      group_id: form.value.group_id,
      course_member_status: form.value.course_member_status
    })
    swal.success('บันทึกข้อมูลสมาชิกเรียบร้อยแล้ว')
  } catch (error: any) {
    console.error('Error saving member:', error)
    swal.error('ไม่สามารถบันทึกข้อมูลได้')
  } finally {
    saving.value = false
  }
}

const goBack = () => {
  navigateTo(`/Learn/Courses/${courseId.value}/members`)
}

onMounted(() => {
  fetchMember()
})
</script>

<template>
  <div class="container mx-auto px-3 sm:px-4 py-4 sm:py-8 max-w-3xl pb-24 lg:pb-8">
    <!-- Back Button -->
    <button 
      @click="goBack"
      class="flex items-center gap-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-6 transition-colors"
    >
      <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
      <span>กลับไปรายชื่อสมาชิก</span>
    </button>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <Icon icon="svg-spinners:ring-resize" class="w-10 h-10 text-blue-500" />
    </div>

    <!-- Content -->
    <div v-else-if="member" class="space-y-6">
      <!-- Header Card -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-20"></div>
        <div class="px-6 pb-6 -mt-10">
          <div class="flex items-end gap-4">
            <img 
              :src="memberAvatar" 
              :alt="memberDisplayName"
              class="w-20 h-20 rounded-xl border-4 border-white dark:border-gray-800 shadow-lg object-cover"
            >
            <div class="pb-1">
              <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ memberDisplayName }}</h1>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ member.user?.email || member.member_email || '-' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit Form -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-6">
          <Icon icon="fluent:person-edit-24-regular" class="w-5 h-5 text-blue-500" />
          แก้ไขข้อมูลสมาชิก
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <!-- Member Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
              ชื่อ-สกุล (ในรายวิชา)
            </label>
            <input 
              v-model="form.member_name"
              type="text"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
              :placeholder="member.user?.name || 'ระบุชื่อ-สกุล'"
            >
            <p class="mt-1 text-xs text-gray-400">หากเว้นว่าง จะใช้ชื่อจากบัญชีผู้ใช้: {{ member.user?.name }}</p>
          </div>

          <!-- Order Number -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
              เลขที่
            </label>
            <div class="flex items-center gap-2">
              <button 
                @click="form.order_number = Math.max(0, (form.order_number || 0) - 1)"
                class="p-2 text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg border border-gray-300 dark:border-gray-600 transition-colors"
              >
                <Icon icon="fluent:subtract-12-filled" class="w-4 h-4" />
              </button>
              <input 
                v-model.number="form.order_number"
                type="number"
                min="0"
                class="flex-1 px-3 py-2.5 text-center border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                placeholder="ระบุเลขที่"
              >
              <button 
                @click="form.order_number = (form.order_number || 0) + 1"
                class="p-2 text-gray-500 hover:text-green-500 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg border border-gray-300 dark:border-gray-600 transition-colors"
              >
                <Icon icon="fluent:add-12-filled" class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Member Code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
              รหัสประจำตัว
            </label>
            <input 
              v-model="form.member_code"
              type="text"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
              placeholder="ระบุรหัสประจำตัว"
            >
          </div>

          <!-- Group -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
              กลุ่ม
            </label>
            <select 
              v-model="form.group_id"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            >
              <option :value="null">ไม่มีกลุ่ม</option>
              <option v-for="group in groups" :key="group.id" :value="group.id">
                {{ group.name }}
              </option>
            </select>
          </div>

          <!-- Status -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
              สถานะ
            </label>
            <select 
              v-model="form.course_member_status"
              class="w-full px-3 py-2.5 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            >
              <option :value="1">ปกติ (Active)</option>
              <option :value="0">พักการเรียน (Suspended)</option>
            </select>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
          <button
            @click="saveMember"
            :disabled="saving"
            class="flex-1 sm:flex-none px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 font-medium"
          >
            <Icon v-if="!saving" icon="fluent:save-24-regular" class="w-5 h-5" />
            <Icon v-else icon="svg-spinners:ring-resize" class="w-5 h-5" />
            {{ saving ? 'กำลังบันทึก...' : 'บันทึกข้อมูล' }}
          </button>
          <button
            @click="goBack"
            class="px-6 py-2.5 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium"
          >
            ยกเลิก
          </button>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else class="text-center py-20">
      <Icon icon="fluent:person-warning-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
      <p class="text-gray-500 dark:text-gray-400 text-lg">ไม่พบข้อมูลสมาชิก</p>
      <button @click="goBack" class="mt-4 text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium">
        กลับไปรายชื่อสมาชิก
      </button>
    </div>
  </div>
</template>
