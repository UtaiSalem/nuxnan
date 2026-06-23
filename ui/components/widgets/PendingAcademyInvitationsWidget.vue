<script setup>
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import { useAcademyMemberService } from '~/composables/useAcademyMemberService'

const authStore = useAuthStore()
const { getMyInvitations, acceptInvitation, declineInvitation } = useAcademyMemberService()

const invitations = ref([])
const isLoading = ref(true)
const error = ref(null)
const processingId = ref(null)

const fetchInvitations = async () => {
  if (!authStore.isAuthenticated) {
    isLoading.value = false
    return
  }
  isLoading.value = true
  error.value = null
  try {
    invitations.value = await getMyInvitations()
  } catch (err) {
    console.error('Error fetching academy invitations:', err)
    error.value = 'ไม่สามารถโหลดข้อมูลได้'
  } finally {
    isLoading.value = false
  }
}

const accept = async (invitation) => {
  processingId.value = invitation.id
  try {
    await acceptInvitation(invitation.academy_id)
    invitations.value = invitations.value.filter(i => i.id !== invitation.id)
  } catch (err) {
    console.error('Error accepting academy invitation:', err)
  } finally {
    processingId.value = null
  }
}

const decline = async (invitation) => {
  processingId.value = invitation.id
  try {
    await declineInvitation(invitation.academy_id)
    invitations.value = invitations.value.filter(i => i.id !== invitation.id)
  } catch (err) {
    console.error('Error declining academy invitation:', err)
  } finally {
    processingId.value = null
  }
}

onMounted(() => {
  fetchInvitations()
})
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-gray-900 dark:text-white">คำเชิญเข้าโรงเรียน</h3>
      <span
        v-if="invitations.length > 0"
        class="px-2 py-0.5 text-xs font-medium text-white bg-vikinger-purple rounded-full"
      >
        {{ invitations.length }}
      </span>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 2" :key="i" class="flex items-center gap-3 animate-pulse">
        <div class="w-10 h-10 bg-gray-200 dark:bg-vikinger-dark-100 rounded-full"></div>
        <div class="flex-1 space-y-2">
          <div class="h-3 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-3/4"></div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-4 text-gray-500 dark:text-gray-400">
      <Icon icon="fluent:error-circle-24-regular" class="w-8 h-8 mx-auto mb-2 text-red-400" />
      <p class="text-sm">{{ error }}</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="invitations.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400">
      <Icon icon="fluent:building-people-24-regular" class="w-8 h-8 mx-auto mb-2 opacity-50" />
      <p class="text-sm">ไม่มีคำเชิญเข้าโรงเรียนใหม่</p>
    </div>

    <!-- Invitations List -->
    <div v-else class="space-y-3">
      <div
        v-for="invitation in invitations"
        :key="invitation.id"
        class="p-3 rounded-lg bg-gray-50 dark:bg-vikinger-dark-100"
      >
        <div class="flex items-center gap-3 mb-3">
          <img
            :src="invitation.academy?.logo || '/images/default-academy.png'"
            :alt="invitation.academy?.name"
            class="w-10 h-10 rounded-full object-cover ring-2 ring-vikinger-purple/20"
          />
          <div class="flex-1 min-w-0">
            <p class="font-medium text-gray-900 dark:text-white truncate">
              {{ invitation.academy?.name }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
              {{ invitation.academy?.slogan || 'เชิญคุณเข้าร่วมเป็นสมาชิก' }}
            </p>
          </div>
        </div>
        <div class="flex gap-2">
          <button
            :disabled="processingId === invitation.id"
            @click="accept(invitation)"
            class="flex-1 py-2 text-sm font-medium text-white bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50"
          >
            ยอมรับ
          </button>
          <button
            :disabled="processingId === invitation.id"
            @click="decline(invitation)"
            class="flex-1 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-200 dark:bg-vikinger-dark-200 rounded-lg hover:bg-gray-300 dark:hover:bg-vikinger-dark-50 transition-colors disabled:opacity-50"
          >
            ปฏิเสธ
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
