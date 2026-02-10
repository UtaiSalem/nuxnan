<script setup lang="ts">
/**
 * Invite Member Modal
 * Modal สำหรับเชิญสมาชิกใหม่เข้าร่วมโรงเรียน
 */
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

interface Props {
  academyId: number | null
  modelValue?: boolean
}

const props = defineProps<Props>()
const emit = defineEmits(['update:modelValue', 'invited'])

const api = useApi()

// State
const isOpen = computed({
  get: () => props.modelValue || false,
  set: (val) => emit('update:modelValue', val)
})

const inviteMethod = ref<'email' | 'search'>('search')
const searchQuery = ref('')
const searchResults = ref<any[]>([])
const selectedUsers = ref<any[]>([])
const selectedRole = ref('student')
const isSearching = ref(false)
const isInviting = ref(false)
const emailInput = ref('')
const emailList = ref<string[]>([])

// Available roles for invitation
const roleOptions = [
  { value: 'student', label: 'นักเรียน' },
  { value: 'parent', label: 'ผู้ปกครอง' },
  { value: 'teacher', label: 'ครู/อาจารย์' },
  { value: 'staff', label: 'เจ้าหน้าที่' },
]

// Search users
let searchTimeout: ReturnType<typeof setTimeout> | null = null

const onSearchInput = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  
  if (searchQuery.value.length < 2) {
    searchResults.value = []
    return
  }
  
  searchTimeout = setTimeout(async () => {
    isSearching.value = true
    try {
      const response: any = await api.get(`/api/users/search?q=${encodeURIComponent(searchQuery.value)}&limit=10`)
      if (response.success) {
        // Filter out already selected users
        const selectedIds = selectedUsers.value.map(u => u.id)
        searchResults.value = (response.users || []).filter((u: any) => !selectedIds.includes(u.id))
      }
    } catch (err) {
      console.error('Search failed:', err)
    } finally {
      isSearching.value = false
    }
  }, 300)
}

const selectUser = (user: any) => {
  if (!selectedUsers.value.find(u => u.id === user.id)) {
    selectedUsers.value.push(user)
  }
  searchQuery.value = ''
  searchResults.value = []
}

const removeUser = (userId: number) => {
  selectedUsers.value = selectedUsers.value.filter(u => u.id !== userId)
}

// Email handling
const addEmail = () => {
  const email = emailInput.value.trim()
  if (email && isValidEmail(email) && !emailList.value.includes(email)) {
    emailList.value.push(email)
    emailInput.value = ''
  }
}

const removeEmail = (email: string) => {
  emailList.value = emailList.value.filter(e => e !== email)
}

const isValidEmail = (email: string) => {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
}

const handleEmailKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Enter' || e.key === ',') {
    e.preventDefault()
    addEmail()
  }
}

// Send invitations
const sendInvitations = async () => {
  const hasUsers = selectedUsers.value.length > 0
  const hasEmails = emailList.value.length > 0
  
  if (!hasUsers && !hasEmails) {
    Swal.fire('กรุณาเลือกผู้ใช้', 'เลือกผู้ใช้หรือกรอกอีเมลที่ต้องการเชิญ', 'warning')
    return
  }
  
  isInviting.value = true
  try {
    const payload = {
      user_ids: selectedUsers.value.map(u => u.id),
      emails: emailList.value,
      role: selectedRole.value
    }
    
    const response: any = await api.post(`/api/academies/${props.academyId}/members/invite`, payload)
    
    if (response.success) {
      Swal.fire({
        icon: 'success',
        title: 'ส่งคำเชิญสำเร็จ',
        text: `ส่งคำเชิญไปยัง ${response.invited_count || (selectedUsers.value.length + emailList.value.length)} คนเรียบร้อยแล้ว`,
        timer: 2000,
        showConfirmButton: false
      })
      
      emit('invited')
      close()
    }
  } catch (err: any) {
    Swal.fire('เกิดข้อผิดพลาด', err.data?.message || 'ไม่สามารถส่งคำเชิญได้', 'error')
  } finally {
    isInviting.value = false
  }
}

const close = () => {
  isOpen.value = false
  searchQuery.value = ''
  searchResults.value = []
  selectedUsers.value = []
  emailInput.value = ''
  emailList.value = []
  selectedRole.value = 'student'
}
</script>

<template>
  <Teleport to="body">
    <div 
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-black/50" @click="close" />
      
      <!-- Modal -->
      <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">เชิญสมาชิกใหม่</h3>
          <button @click="close" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
            <Icon name="fluent:dismiss-24-regular" class="w-5 h-5" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-5 overflow-y-auto max-h-[calc(90vh-160px)]">
          <!-- Method Tabs -->
          <div class="flex gap-2 p-1 bg-gray-100 dark:bg-gray-700 rounded-lg">
            <button
              @click="inviteMethod = 'search'"
              :class="[
                'flex-1 py-2 rounded-md text-sm font-medium transition-colors',
                inviteMethod === 'search' 
                  ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                  : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
              ]"
            >
              ค้นหาผู้ใช้
            </button>
            <button
              @click="inviteMethod = 'email'"
              :class="[
                'flex-1 py-2 rounded-md text-sm font-medium transition-colors',
                inviteMethod === 'email' 
                  ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                  : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
              ]"
            >
              เชิญทางอีเมล
            </button>
          </div>

          <!-- Search Method -->
          <div v-if="inviteMethod === 'search'" class="space-y-4">
            <!-- Search Input -->
            <div class="relative">
              <Icon name="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                v-model="searchQuery"
                @input="onSearchInput"
                type="text"
                placeholder="ค้นหาชื่อหรืออีเมล..."
                class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
              <div v-if="isSearching" class="absolute right-3 top-1/2 -translate-y-1/2">
                <div class="animate-spin rounded-full h-5 w-5 border-2 border-primary-500 border-t-transparent"></div>
              </div>
            </div>

            <!-- Search Results -->
            <div 
              v-if="searchResults.length > 0"
              class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden"
            >
              <div
                v-for="user in searchResults"
                :key="user.id"
                @click="selectUser(user)"
                class="flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors"
              >
                <img 
                  :src="user.avatar || '/images/default-avatar.png'"
                  :alt="user.name"
                  class="w-10 h-10 rounded-full object-cover"
                />
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-gray-900 dark:text-white truncate">{{ user.name }}</p>
                  <p class="text-sm text-gray-500 truncate">{{ user.email }}</p>
                </div>
                <Icon name="fluent:add-circle-24-regular" class="w-5 h-5 text-primary-500" />
              </div>
            </div>

            <!-- Selected Users -->
            <div v-if="selectedUsers.length > 0">
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ผู้ใช้ที่เลือก ({{ selectedUsers.length }})
              </p>
              <div class="flex flex-wrap gap-2">
                <div 
                  v-for="user in selectedUsers"
                  :key="user.id"
                  class="flex items-center gap-2 px-3 py-1.5 bg-primary-50 dark:bg-primary-900/30 rounded-full"
                >
                  <img 
                    :src="user.avatar || '/images/default-avatar.png'"
                    :alt="user.name"
                    class="w-5 h-5 rounded-full object-cover"
                  />
                  <span class="text-sm text-primary-700 dark:text-primary-300">{{ user.name }}</span>
                  <button @click="removeUser(user.id)" class="text-primary-500 hover:text-primary-700">
                    <Icon name="fluent:dismiss-12-regular" class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Email Method -->
          <div v-if="inviteMethod === 'email'" class="space-y-4">
            <!-- Email Input -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                อีเมลผู้รับเชิญ
              </label>
              <div class="relative">
                <input
                  v-model="emailInput"
                  @keydown="handleEmailKeydown"
                  type="email"
                  placeholder="กรอกอีเมลแล้วกด Enter"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
                <button 
                  @click="addEmail"
                  :disabled="!emailInput || !isValidEmail(emailInput)"
                  class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/30 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <Icon name="fluent:add-24-regular" class="w-5 h-5" />
                </button>
              </div>
            </div>

            <!-- Email List -->
            <div v-if="emailList.length > 0">
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                รายการอีเมล ({{ emailList.length }})
              </p>
              <div class="flex flex-wrap gap-2">
                <div 
                  v-for="email in emailList"
                  :key="email"
                  class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-full"
                >
                  <Icon name="fluent:mail-24-regular" class="w-4 h-4 text-gray-500" />
                  <span class="text-sm text-gray-700 dark:text-gray-300">{{ email }}</span>
                  <button @click="removeEmail(email)" class="text-gray-500 hover:text-red-500">
                    <Icon name="fluent:dismiss-12-regular" class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Role Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              บทบาทที่จะกำหนด
            </label>
            <div class="grid grid-cols-2 gap-2">
              <label 
                v-for="role in roleOptions"
                :key="role.value"
                :class="[
                  'flex items-center gap-2 p-3 rounded-lg border-2 cursor-pointer transition-colors',
                  selectedRole === role.value 
                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'
                ]"
              >
                <input
                  type="radio"
                  v-model="selectedRole"
                  :value="role.value"
                  class="text-primary-600 focus:ring-primary-500"
                />
                <span class="text-sm text-gray-900 dark:text-white">{{ role.label }}</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3">
          <button 
            @click="close"
            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
          >
            ยกเลิก
          </button>
          <button 
            @click="sendInvitations"
            :disabled="isInviting || (selectedUsers.length === 0 && emailList.length === 0)"
            class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <span v-if="isInviting" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
            <Icon v-else name="fluent:send-24-regular" class="w-5 h-5" />
            ส่งคำเชิญ
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
