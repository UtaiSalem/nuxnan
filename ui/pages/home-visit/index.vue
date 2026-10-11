<template>
  <div class="min-h-screen bg-gray-50 dark:bg-vikinger-dark-300">
    <div class="max-w-5xl mx-auto px-4 py-6 sm:px-6 sm:py-10">
      <!-- Header -->
      <div class="flex items-start gap-3 mb-6 sm:mb-8">
        <div class="flex-shrink-0 w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-cyan-500 to-sky-600 rounded-2xl flex items-center justify-center">
          <Icon icon="fluent:home-person-24-filled" class="w-7 h-7 sm:w-8 sm:h-8 text-white" />
        </div>
        <div class="min-w-0 flex-1">
          <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white break-words">
            ระบบเยี่ยมบ้านนักเรียน
          </h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 break-words">
            บันทึกและติดตามการเยี่ยมบ้านนักเรียนของโรงเรียนที่คุณเป็นสมาชิก
          </p>
        </div>
      </div>

      <!-- Note: unified login -->
      <div class="flex items-start gap-2.5 rounded-xl border border-cyan-100 dark:border-cyan-900/40 bg-cyan-50 dark:bg-cyan-900/20 p-3 sm:p-4 mb-6">
        <Icon icon="fluent:info-24-filled" class="flex-shrink-0 w-5 h-5 text-cyan-600 dark:text-cyan-400 mt-0.5" />
        <p class="min-w-0 text-sm text-cyan-900 dark:text-cyan-100 break-words">
          ระบบเยี่ยมบ้านรวมเข้ากับบัญชี Nuxnan ของคุณแล้ว — ไม่ต้องใช้รหัสผ่านแยกอีกต่อไป
          เลือกโรงเรียนด้านล่างเพื่อเข้าสู่หน้าจัดการเยี่ยมบ้าน
        </p>
      </div>

      <!-- Loading -->
      <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div v-for="n in 4" :key="n" class="h-24 rounded-2xl bg-gray-100 dark:bg-vikinger-dark-200 animate-pulse" />
      </div>

      <!-- Empty -->
      <div
        v-else-if="academies.length === 0"
        class="text-center py-12 px-4 rounded-2xl border border-dashed border-gray-200 dark:border-vikinger-dark-100"
      >
        <Icon icon="fluent:building-multiple-24-regular" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
        <p class="text-gray-600 dark:text-gray-300 font-medium">คุณยังไม่ได้เป็นสมาชิกของโรงเรียนใด</p>
        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1 mb-4">
          เข้าร่วมโรงเรียนก่อนจึงจะใช้ระบบเยี่ยมบ้านได้
        </p>
        <NuxtLink
          to="/academies"
          class="inline-flex items-center justify-center min-h-[44px] px-5 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium transition-colors"
        >
          ค้นหาโรงเรียน
        </NuxtLink>
      </div>

      <!-- Academy cards -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <NuxtLink
          v-for="academy in academies"
          :key="academy.id"
          :to="`/academies/${encodeURIComponent(academy.name)}/admin/home-visits`"
          class="group flex items-center gap-3 rounded-2xl border border-gray-100 dark:border-vikinger-dark-100 bg-white dark:bg-vikinger-dark-200 p-4 hover:shadow-md hover:border-cyan-200 dark:hover:border-cyan-900/50 transition-all min-h-[44px]"
        >
          <div class="flex-shrink-0 w-12 h-12 rounded-xl overflow-hidden bg-gradient-to-br from-cyan-500 to-sky-600 flex items-center justify-center">
            <img
              v-if="academy.logo || academy.academy_logo"
              :src="academy.logo || academy.academy_logo"
              :alt="academy.name"
              class="w-full h-full object-cover"
            >
            <span v-else class="text-white text-lg font-bold">{{ (academy.name || '?').charAt(0) }}</span>
          </div>
          <div class="min-w-0 flex-1">
            <p class="font-semibold text-gray-900 dark:text-white truncate">{{ academy.name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
              {{ academy.academy_full_name || academy.description || 'เข้าสู่ระบบเยี่ยมบ้าน' }}
            </p>
          </div>
          <Icon
            icon="fluent:chevron-right-24-filled"
            class="flex-shrink-0 w-5 h-5 text-gray-300 dark:text-gray-600 group-hover:text-cyan-500 transition-colors"
          />
        </NuxtLink>
      </div>

      <!-- Back -->
      <div class="mt-8">
        <NuxtLink
          to="/"
          class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 min-h-[44px]"
        >
          <Icon icon="fluent:arrow-left-24-filled" class="w-4 h-4" />
          กลับหน้าหลัก
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'main',
  middleware: ['auth'],
})

useHead({ title: 'ระบบเยี่ยมบ้านนักเรียน - Nuxnan' })

const api = useApi()
const { user } = storeToRefs(useAuthStore())

const academies = ref<any[]>([])
const isLoading = ref(true)

const fetchMyAcademies = async () => {
  if (!user.value) {
    isLoading.value = false
    return
  }
  try {
    const response: any = await api.get(`/api/academies/users/${user.value.id}/membered-academies`, {
      params: { per_page: 50 },
    })
    if (response?.success) {
      academies.value = response.academies?.data || response.academies || []
    }
  } catch (error) {
    console.error('Failed to fetch membered academies:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchMyAcademies)
</script>
