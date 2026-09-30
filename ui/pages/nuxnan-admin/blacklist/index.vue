<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string

interface SuspendedUser {
  id: number
  name?: string
  username?: string
  email?: string
  points_suspended?: boolean
  wallet_suspended?: boolean
  economy_suspended_reason?: string | null
  economy_suspended_at?: string | null
  economy_suspended_by?: { id: number; name?: string; username?: string } | null
}

const users = ref<SuspendedUser[]>([])
const isLoading = ref(true)
const errorMessage = ref('')
const searchQuery = ref('')
const currentPage = ref(1)
const totalPages = ref(1)
const total = ref(0)
const restoringId = ref<number | null>(null)

const fetchSuspensions = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const token = useCookie('token')
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      per_page: '20',
      ...(searchQuery.value.trim() && { search: searchQuery.value.trim() })
    })
    const response = await $fetch<any>(`${apiBase}/api/admin/economy-suspensions?${params}`, {
      headers: { Authorization: `Bearer ${token.value}` }
    })
    if (response.success) {
      users.value = response.data.data || []
      totalPages.value = response.data.last_page || 1
      total.value = response.data.total || users.value.length
    }
  } catch (error) {
    console.error('Failed to fetch suspensions:', error)
    users.value = []
    errorMessage.value = 'ไม่สามารถโหลดข้อมูลได้ กรุณาลองใหม่'
  } finally {
    isLoading.value = false
  }
}

let searchTimer: ReturnType<typeof setTimeout> | null = null
watch(searchQuery, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    currentPage.value = 1
    fetchSuspensions()
  }, 400)
})

const goToPage = (page: number) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  fetchSuspensions()
}

const restore = async (user: SuspendedUser) => {
  if (!confirm(`ยกเลิกการระงับบัญชี "${user.name || user.username || user.email}" ?`)) return
  restoringId.value = user.id
  try {
    const token = useCookie('token')
    const response = await $fetch<any>(`${apiBase}/api/admin/users/${user.id}/restore-economy`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token.value}` }
    })
    if (response.success) {
      users.value = users.value.filter(u => u.id !== user.id)
      total.value = Math.max(0, total.value - 1)
    }
  } catch (error) {
    console.error('Restore failed:', error)
    alert('ไม่สามารถยกเลิกการระงับได้ กรุณาลองใหม่')
  } finally {
    restoringId.value = null
  }
}

const userName = (u: SuspendedUser) => u.name || u.username || u.email || `#${u.id}`

const formatDate = (dateStr?: string | null) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleString('th-TH', {
    year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

onMounted(fetchSuspensions)
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="min-w-0">
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Blacklist</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1">
          บัญชีที่ถูกระงับระบบแต้ม/Wallet ({{ total.toLocaleString() }} บัญชี)
        </p>
      </div>
      <div class="flex items-center gap-2 flex-shrink-0">
        <NuxtLink
          to="/nuxnan-admin/blacklist/audit"
          class="min-h-[44px] sm:min-h-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl text-slate-700 dark:text-slate-300 transition-colors whitespace-nowrap"
        >
          <Icon icon="fluent:history-24-regular" class="w-5 h-5" />
          ประวัติ
        </NuxtLink>
        <button
          @click="fetchSuspensions"
          class="min-h-[44px] sm:min-h-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl text-slate-700 dark:text-slate-300 transition-colors whitespace-nowrap"
        >
          <Icon icon="fluent:arrow-sync-24-regular" class="w-5 h-5" :class="{ 'animate-spin': isLoading }" />
          <span class="hidden sm:inline">รีเฟรช</span>
        </button>
      </div>
    </div>

    <!-- Search -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-hopeui border border-slate-100 dark:border-slate-700">
      <div class="relative">
        <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="ค้นหาผู้ใช้ (ชื่อ / username / อีเมล)..."
          class="w-full pl-10 pr-4 py-2.5 min-h-[44px] sm:min-h-0 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
        />
      </div>
    </div>

    <!-- List -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-hopeui border border-slate-100 dark:border-slate-700 overflow-hidden">
      <div v-if="isLoading" class="p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-hopeui-primary-600 animate-spin mx-auto" />
        <p class="text-slate-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>

      <div v-else-if="errorMessage" class="p-8 text-center">
        <Icon icon="fluent:error-circle-24-regular" class="w-12 h-12 text-red-400 mx-auto" />
        <p class="text-red-500 mt-2">{{ errorMessage }}</p>
        <button @click="fetchSuspensions" class="mt-4 min-h-[44px] px-4 py-2 bg-hopeui-primary-500 hover:bg-hopeui-primary-600 rounded-xl text-white">ลองใหม่</button>
      </div>

      <div v-else-if="users.length === 0" class="p-8 text-center">
        <Icon icon="fluent:shield-checkmark-24-regular" class="w-12 h-12 text-slate-300 mx-auto" />
        <p class="text-slate-500 mt-2">ไม่มีบัญชีที่ถูกระงับ</p>
      </div>

      <ul v-else class="divide-y divide-slate-100 dark:divide-slate-700">
        <li v-for="u in users" :key="u.id" class="p-4 flex flex-col sm:flex-row sm:items-center gap-3">
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="font-medium text-slate-800 dark:text-white break-words">{{ userName(u) }}</span>
              <span v-if="u.points_suspended" class="px-2 py-0.5 text-xs rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">ระงับแต้ม</span>
              <span v-if="u.wallet_suspended" class="px-2 py-0.5 text-xs rounded-lg bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">ระงับ Wallet</span>
            </div>
            <p v-if="u.economy_suspended_reason" class="text-sm text-slate-600 dark:text-slate-300 mt-1 break-words">
              เหตุผล: {{ u.economy_suspended_reason }}
            </p>
            <p class="text-xs text-slate-400 mt-1">
              เมื่อ {{ formatDate(u.economy_suspended_at) }}
              <template v-if="u.economy_suspended_by"> • โดย {{ u.economy_suspended_by.name || u.economy_suspended_by.username }}</template>
            </p>
          </div>
          <div class="flex items-center gap-2 flex-shrink-0">
            <NuxtLink
              :to="`/nuxnan-admin/users/${u.id}`"
              class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1 px-3 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl text-sm text-slate-700 dark:text-slate-300"
            >
              <Icon icon="fluent:person-24-regular" class="w-4 h-4" />
              ดูบัญชี
            </NuxtLink>
            <button
              @click="restore(u)"
              :disabled="restoringId === u.id"
              class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1 px-3 py-2 bg-green-500 hover:bg-green-600 rounded-xl text-sm text-white disabled:opacity-50"
            >
              <Icon v-if="restoringId === u.id" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
              <Icon v-else icon="fluent:arrow-undo-24-regular" class="w-4 h-4" />
              ยกเลิกระงับ
            </button>
          </div>
        </li>
      </ul>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="p-4 border-t border-slate-100 dark:border-slate-700">
        <div class="flex items-center justify-center gap-1 flex-wrap">
          <button
            @click="goToPage(currentPage - 1)"
            :disabled="currentPage === 1"
            class="min-w-[44px] min-h-[44px] sm:min-w-[40px] sm:min-h-[40px] flex items-center justify-center rounded-lg text-sm font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 disabled:opacity-40 disabled:cursor-not-allowed"
          >
            <Icon icon="fluent:chevron-left-24-regular" class="w-5 h-5" />
          </button>
          <button
            v-for="page in Math.min(totalPages, 10)"
            :key="page"
            @click="goToPage(page)"
            class="min-w-[44px] min-h-[44px] sm:min-w-[40px] sm:min-h-[40px] rounded-lg text-sm font-medium"
            :class="currentPage === page
              ? 'bg-hopeui-primary-500 text-white'
              : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
          >
            {{ page }}
          </button>
          <button
            @click="goToPage(currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="min-w-[44px] min-h-[44px] sm:min-w-[40px] sm:min-h-[40px] flex items-center justify-center rounded-lg text-sm font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 disabled:opacity-40 disabled:cursor-not-allowed"
          >
            <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
