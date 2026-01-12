<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import BaseCard from '~/components/atoms/BaseCard.vue'

definePageMeta({
  layout: 'main',
})

useHead({
  title: 'แต้มของฉัน - Nuxni'
})

const authStore = useAuthStore()

// Points history (mock data - would come from API)
const pointsHistory = ref([
  { id: 1, type: 'earn', action: 'รับการสนับสนุน', points: 240, date: '2026-01-11 10:30' },
  { id: 2, type: 'spend', action: 'กดถูกใจโพสต์', points: -24, date: '2026-01-11 09:15' },
  { id: 3, type: 'earn', action: 'โพสต์ถูกกดถูกใจ', points: 12, date: '2026-01-10 18:45' },
  { id: 4, type: 'spend', action: 'แสดงความคิดเห็น', points: -12, date: '2026-01-10 14:20' },
  { id: 5, type: 'earn', action: 'รับการสนับสนุน', points: 240, date: '2026-01-09 11:00' },
])

// Points usage breakdown
const pointsUsage = ref([
  { label: 'กดถูกใจ', count: 15, points: 360, icon: 'mdi:thumb-up', color: 'blue' },
  { label: 'แสดงความคิดเห็น', count: 8, points: 96, icon: 'mdi:comment-text', color: 'green' },
  { label: 'แชร์โพสต์', count: 3, points: 36, icon: 'mdi:share-variant', color: 'purple' },
])

// Earning sources
const earningSources = ref([
  { label: 'การสนับสนุน', points: 1200, icon: 'mdi:hand-coin', color: 'yellow' },
  { label: 'ถูกกดถูกใจ', points: 360, icon: 'mdi:thumb-up', color: 'blue' },
  { label: 'โบนัสแนะนำ', points: 90, icon: 'mdi:account-plus', color: 'green' },
])

const totalEarned = computed(() => {
  return earningSources.value.reduce((sum, s) => sum + s.points, 0)
})

const totalSpent = computed(() => {
  return pointsUsage.value.reduce((sum, u) => sum + u.points, 0)
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
      <!-- Page Header -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-yellow-400 to-amber-600 rounded-full flex items-center justify-center shadow-lg">
          <Icon icon="mdi:star-circle" class="w-10 h-10 text-white" />
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
          แต้มของฉัน
        </h1>
        <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
          ดูสถิติและประวัติการใช้งานแต้มของคุณ
        </p>
      </div>

      <!-- Not Authenticated Message -->
      <div v-if="!authStore.isAuthenticated" class="text-center py-12">
        <BaseCard>
          <Icon icon="mdi:account-lock" class="w-16 h-16 mx-auto text-gray-400 mb-4" />
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">กรุณาเข้าสู่ระบบ</h2>
          <p class="text-gray-500 dark:text-gray-400 mb-4">เข้าสู่ระบบเพื่อดูข้อมูลแต้มของคุณ</p>
          <NuxtLink 
            to="/auth"
            class="inline-flex items-center gap-2 px-6 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-lg hover:opacity-90 transition-opacity"
          >
            <Icon icon="mdi:login" class="w-5 h-5" />
            เข้าสู่ระบบ
          </NuxtLink>
        </BaseCard>
      </div>

      <!-- Authenticated Content -->
      <div v-else>
        <!-- Main Points Card -->
        <BaseCard class="mb-8 bg-gradient-to-r from-yellow-400 via-amber-500 to-orange-500 border-0">
          <div class="flex flex-col lg:flex-row items-center justify-between gap-6 text-white">
            <div class="flex items-center gap-6">
              <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center backdrop-blur">
                <Icon icon="mdi:star-circle" class="w-14 h-14" />
              </div>
              <div>
                <p class="text-white/80 text-sm mb-1">แต้มปัจจุบัน</p>
                <p class="text-5xl font-bold">{{ authStore.points.toLocaleString() }}</p>
                <p class="text-white/80 text-sm mt-1">Plearnd Points</p>
              </div>
            </div>
            <div class="flex gap-4">
              <NuxtLink
                to="/earn/donates"
                class="px-6 py-3 bg-white text-amber-600 font-semibold rounded-xl hover:bg-amber-50 transition-colors flex items-center gap-2 shadow-lg"
              >
                <Icon icon="mdi:plus-circle" class="w-5 h-5" />
                สะสมแต้มเพิ่ม
              </NuxtLink>
            </div>
          </div>
        </BaseCard>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
          <BaseCard>
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                <Icon icon="mdi:trending-up" class="w-7 h-7 text-green-600 dark:text-green-400" />
              </div>
              <div>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">+{{ totalEarned.toLocaleString() }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">แต้มที่ได้รับ</p>
              </div>
            </div>
          </BaseCard>
          
          <BaseCard>
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                <Icon icon="mdi:trending-down" class="w-7 h-7 text-red-600 dark:text-red-400" />
              </div>
              <div>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">-{{ totalSpent.toLocaleString() }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">แต้มที่ใช้ไป</p>
              </div>
            </div>
          </BaseCard>
          
          <BaseCard>
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                <Icon icon="mdi:chart-line" class="w-7 h-7 text-blue-600 dark:text-blue-400" />
              </div>
              <div>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ (totalEarned - totalSpent).toLocaleString() }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">สุทธิ</p>
              </div>
            </div>
          </BaseCard>
        </div>

        <!-- Earning Sources & Usage -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <!-- Earning Sources -->
          <BaseCard>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <Icon icon="mdi:arrow-down-circle" class="w-5 h-5 text-green-500" />
              แหล่งที่มาของแต้ม
            </h2>
            <div class="space-y-4">
              <div 
                v-for="source in earningSources" 
                :key="source.label"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl"
              >
                <div class="flex items-center gap-3">
                  <div 
                    class="w-10 h-10 rounded-lg flex items-center justify-center"
                    :class="{
                      'bg-yellow-100 dark:bg-yellow-900/30': source.color === 'yellow',
                      'bg-blue-100 dark:bg-blue-900/30': source.color === 'blue',
                      'bg-green-100 dark:bg-green-900/30': source.color === 'green'
                    }"
                  >
                    <Icon 
                      :icon="source.icon" 
                      class="w-5 h-5"
                      :class="{
                        'text-yellow-600 dark:text-yellow-400': source.color === 'yellow',
                        'text-blue-600 dark:text-blue-400': source.color === 'blue',
                        'text-green-600 dark:text-green-400': source.color === 'green'
                      }"
                    />
                  </div>
                  <span class="font-medium text-gray-900 dark:text-white">{{ source.label }}</span>
                </div>
                <span class="font-bold text-green-600 dark:text-green-400">+{{ source.points.toLocaleString() }}</span>
              </div>
            </div>
          </BaseCard>

          <!-- Points Usage -->
          <BaseCard>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <Icon icon="mdi:arrow-up-circle" class="w-5 h-5 text-red-500" />
              การใช้แต้ม
            </h2>
            <div class="space-y-4">
              <div 
                v-for="usage in pointsUsage" 
                :key="usage.label"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl"
              >
                <div class="flex items-center gap-3">
                  <div 
                    class="w-10 h-10 rounded-lg flex items-center justify-center"
                    :class="{
                      'bg-blue-100 dark:bg-blue-900/30': usage.color === 'blue',
                      'bg-green-100 dark:bg-green-900/30': usage.color === 'green',
                      'bg-purple-100 dark:bg-purple-900/30': usage.color === 'purple'
                    }"
                  >
                    <Icon 
                      :icon="usage.icon" 
                      class="w-5 h-5"
                      :class="{
                        'text-blue-600 dark:text-blue-400': usage.color === 'blue',
                        'text-green-600 dark:text-green-400': usage.color === 'green',
                        'text-purple-600 dark:text-purple-400': usage.color === 'purple'
                      }"
                    />
                  </div>
                  <div>
                    <span class="font-medium text-gray-900 dark:text-white">{{ usage.label }}</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ usage.count }} ครั้ง</p>
                  </div>
                </div>
                <span class="font-bold text-red-600 dark:text-red-400">-{{ usage.points.toLocaleString() }}</span>
              </div>
            </div>
          </BaseCard>
        </div>

        <!-- Points History -->
        <BaseCard>
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <Icon icon="mdi:history" class="w-5 h-5 text-gray-500" />
              ประวัติการทำรายการ
            </h2>
          </div>
          <div class="space-y-3">
            <div 
              v-for="item in pointsHistory" 
              :key="item.id"
              class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            >
              <div class="flex items-center gap-4">
                <div 
                  class="w-10 h-10 rounded-full flex items-center justify-center"
                  :class="item.type === 'earn' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'"
                >
                  <Icon 
                    :icon="item.type === 'earn' ? 'mdi:arrow-down' : 'mdi:arrow-up'" 
                    class="w-5 h-5"
                    :class="item.type === 'earn' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                  />
                </div>
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">{{ item.action }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ item.date }}</p>
                </div>
              </div>
              <span 
                class="font-bold text-lg"
                :class="item.type === 'earn' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
              >
                {{ item.points > 0 ? '+' : '' }}{{ item.points.toLocaleString() }}
              </span>
            </div>
          </div>
          
          <!-- View All Link -->
          <div class="text-center mt-6">
            <button class="text-amber-600 dark:text-amber-400 hover:underline text-sm font-medium">
              ดูประวัติทั้งหมด
            </button>
          </div>
        </BaseCard>

        <!-- Quick Actions -->
        <div class="mt-8">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 text-center">
            ทำกิจกรรมเพื่อสะสมแต้ม
          </h2>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <NuxtLink to="/earn/donates" class="block">
              <BaseCard class="h-full hover:shadow-lg transition-shadow cursor-pointer group text-center">
                <div class="w-12 h-12 mx-auto mb-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                  <Icon icon="mdi:hand-coin" class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white text-sm">รับการสนับสนุน</h3>
                <p class="text-xs text-green-600 dark:text-green-400 mt-1">+240 แต้ม</p>
              </BaseCard>
            </NuxtLink>
            
            <NuxtLink to="/newsfeed" class="block">
              <BaseCard class="h-full hover:shadow-lg transition-shadow cursor-pointer group text-center">
                <div class="w-12 h-12 mx-auto mb-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                  <Icon icon="mdi:post-outline" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white text-sm">โพสต์เนื้อหา</h3>
                <p class="text-xs text-green-600 dark:text-green-400 mt-1">รับแต้มจากการถูกใจ</p>
              </BaseCard>
            </NuxtLink>
            
            <NuxtLink to="/courses" class="block">
              <BaseCard class="h-full hover:shadow-lg transition-shadow cursor-pointer group text-center">
                <div class="w-12 h-12 mx-auto mb-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                  <Icon icon="mdi:school" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white text-sm">เรียนรู้</h3>
                <p class="text-xs text-green-600 dark:text-green-400 mt-1">รับแต้มจากบทเรียน</p>
              </BaseCard>
            </NuxtLink>
            
            <NuxtLink to="/quests" class="block">
              <BaseCard class="h-full hover:shadow-lg transition-shadow cursor-pointer group text-center">
                <div class="w-12 h-12 mx-auto mb-3 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                  <Icon icon="mdi:trophy" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white text-sm">ทำภารกิจ</h3>
                <p class="text-xs text-green-600 dark:text-green-400 mt-1">รับแต้มโบนัส</p>
              </BaseCard>
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
