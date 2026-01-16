<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

const props = defineProps<{
  isOpen: boolean
  academyId: number
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'invited'): void
}>()

const api = useApi()

// State
const searchQuery = ref('')
const searchResults = ref<any[]>([])
const isSearching = ref(false)
const isInviting = ref(false)
const invitingUserId = ref<number | null>(null)
const debounceTimer = ref<any>(null)

// Watch search query with debounce
watch(searchQuery, (newQuery) => {
  if (debounceTimer.value) {
    clearTimeout(debounceTimer.value)
  }
  
  if (newQuery.length < 2) {
    searchResults.value = []
    return
  }
  
  debounceTimer.value = setTimeout(() => {
    searchUsers()
  }, 300)
})

// Methods
const searchUsers = async () => {
  if (searchQuery.value.length < 2) return
  
  isSearching.value = true
  try {
    const response: any = await api.get('/api/users/search', {
      params: { q: searchQuery.value, limit: 10 }
    })
    
    if (response.success) {
      searchResults.value = response.data || []
    }
  } catch (err) {
    console.error('Search users error:', err)
  } finally {
    isSearching.value = false
  }
}

const inviteUser = async (user: any) => {
  if (isInviting.value) return
  
  isInviting.value = true
  invitingUserId.value = user.id
  
  try {
    const response: any = await api.post(`/api/academies/${props.academyId}/invite`, {
      user_id: user.id
    })
    
    if (response.success) {
      Swal.fire({
        icon: 'success',
        title: 'ส่งคำเชิญสำเร็จ',
        text: `ส่งคำเชิญให้ ${user.name} เรียบร้อยแล้ว`,
        timer: 2000,
        showConfirmButton: false
      })
      
      // Remove invited user from results
      searchResults.value = searchResults.value.filter(u => u.id !== user.id)
      emit('invited')
    } else {
      Swal.fire({
        icon: 'error',
        title: 'ไม่สามารถเชิญได้',
        text: response.message || 'เกิดข้อผิดพลาด',
      })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err?.data?.message || 'ไม่สามารถส่งคำเชิญได้',
    })
  } finally {
    isInviting.value = false
    invitingUserId.value = null
  }
}

const close = () => {
  searchQuery.value = ''
  searchResults.value = []
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="close"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        
        <!-- Modal -->
        <div class="relative bg-white dark:bg-vikinger-dark-200 rounded-2xl shadow-xl w-full max-w-lg max-h-[80vh] flex flex-col overflow-hidden">
          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-vikinger-purple/10 flex items-center justify-center">
                <Icon icon="fluent:person-add-24-regular" class="w-5 h-5 text-vikinger-purple" />
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">เชิญสมาชิก</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">ค้นหาและเชิญผู้ใช้เข้าร่วมโรงเรียน</p>
              </div>
            </div>
            <button
              @click="close"
              class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            >
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <!-- Search Input -->
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="relative">
              <Icon 
                icon="fluent:search-24-regular" 
                class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" 
              />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="ค้นหาด้วยชื่อ, อีเมล, หรือรหัสอ้างอิง..."
                class="w-full pl-10 pr-4 py-3 rounded-xl bg-gray-50 dark:bg-vikinger-dark-100 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50"
              />
              <Icon 
                v-if="isSearching" 
                icon="svg-spinners:ring-resize" 
                class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-vikinger-purple" 
              />
            </div>
            <p v-if="searchQuery.length > 0 && searchQuery.length < 2" class="mt-2 text-sm text-gray-500">
              พิมพ์อย่างน้อย 2 ตัวอักษร
            </p>
          </div>
          
          <!-- Results -->
          <div class="flex-1 overflow-y-auto px-6 py-4">
            <!-- Empty State -->
            <div v-if="!searchQuery || searchQuery.length < 2" class="text-center py-8">
              <Icon icon="fluent:search-24-regular" class="w-16 h-16 text-gray-200 dark:text-gray-700 mx-auto mb-4" />
              <p class="text-gray-500 dark:text-gray-400">ค้นหาผู้ใช้ที่ต้องการเชิญ</p>
            </div>
            
            <!-- No Results -->
            <div v-else-if="searchResults.length === 0 && !isSearching" class="text-center py-8">
              <Icon icon="fluent:person-search-24-regular" class="w-16 h-16 text-gray-200 dark:text-gray-700 mx-auto mb-4" />
              <p class="text-gray-500 dark:text-gray-400">ไม่พบผู้ใช้ที่ค้นหา</p>
            </div>
            
            <!-- User List -->
            <div v-else class="space-y-2">
              <div
                v-for="user in searchResults"
                :key="user.id"
                class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-vikinger-dark-100 hover:bg-gray-100 dark:hover:bg-vikinger-dark-300 transition-colors"
              >
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                    <img 
                      v-if="user.avatar || user.profile_photo_url" 
                      :src="user.avatar || user.profile_photo_url"
                      :alt="user.name"
                      class="w-full h-full object-cover"
                    />
                    <Icon v-else icon="fluent:person-24-regular" class="w-full h-full p-2 text-gray-400" />
                  </div>
                  <div>
                    <h4 class="font-medium text-gray-900 dark:text-white">{{ user.name }}</h4>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                      <span v-if="user.reference_code">@{{ user.reference_code }}</span>
                      <span v-if="user.email">{{ user.email }}</span>
                    </div>
                  </div>
                </div>
                
                <button
                  @click="inviteUser(user)"
                  :disabled="isInviting && invitingUserId === user.id"
                  class="px-4 py-2 bg-vikinger-purple text-white rounded-lg text-sm font-medium hover:bg-vikinger-purple/90 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 transition-colors"
                >
                  <Icon 
                    v-if="isInviting && invitingUserId === user.id" 
                    icon="svg-spinners:ring-resize" 
                    class="w-4 h-4" 
                  />
                  <Icon v-else icon="fluent:person-add-24-regular" class="w-4 h-4" />
                  <span>เชิญ</span>
                </button>
              </div>
            </div>
          </div>
          
          <!-- Footer -->
          <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-vikinger-dark-100">
            <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
              <Icon icon="fluent:info-16-regular" class="inline w-4 h-4 mr-1" />
              ผู้ใช้ที่ถูกเชิญจะได้รับแจ้งเตือนและสามารถยอมรับหรือปฏิเสธคำเชิญได้
            </p>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from > div:last-child,
.modal-leave-to > div:last-child {
  transform: scale(0.95) translateY(10px);
}
</style>
