<template>
  <div class="rounded-2xl bg-gradient-to-br from-primary-600 to-sky-500 p-4 text-white shadow-lg sm:p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-white/20 sm:h-16 sm:w-16">
          <Icon icon="fluent:building-24-filled" class="h-7 w-7 sm:h-8 sm:w-8" />
        </div>
        <div class="min-w-0 flex-1">
          <div v-if="department?.academy?.name" class="mb-1">
            <span class="rounded-full bg-black/20 px-2 py-0.5 text-xs text-white/90">{{ department.academy.name }}</span>
          </div>
          <h1 class="break-words text-xl font-bold sm:text-2xl">{{ department?.name }}</h1>
          <p class="mt-1 break-words text-sm text-white/80">
            {{ department?.description || 'จัดการข้อมูล สมาชิก และสิทธิ์ของฝ่ายงานนี้' }}
          </p>
          <div class="mt-3 flex flex-wrap gap-2">
            <span class="flex-shrink-0 whitespace-nowrap rounded-full bg-white/15 px-2.5 py-1 text-xs sm:text-sm">
              {{ formatDepartmentType(department?.type) }}
            </span>
            <span v-if="parent" class="break-words rounded-full bg-white/15 px-2.5 py-1 text-xs sm:text-sm">
              สังกัด: {{ parent.name }}
            </span>
            <span class="flex-shrink-0 whitespace-nowrap rounded-full bg-white/15 px-2.5 py-1 text-xs sm:text-sm">
              {{ membersCount || 0 }} สมาชิก
            </span>
            <span v-if="head" class="flex-shrink-0 whitespace-nowrap rounded-full bg-white/15 px-2.5 py-1 text-xs sm:text-sm">
              หัวหน้าฝ่าย: {{ head.name }}
            </span>
            <span v-else class="flex-shrink-0 whitespace-nowrap rounded-full bg-amber-400/25 px-2.5 py-1 text-xs sm:text-sm">
              ยังไม่กำหนดหัวหน้าฝ่าย
            </span>
          </div>
        </div>
      </div>
      
      <div class="flex flex-col gap-4 md:flex-row md:items-center">
        <div v-if="memberAvatars?.length" class="flex items-center">
          <img
            v-for="member in memberAvatars.slice(0, 5)"
            :key="member.id"
            :src="member.profile_photo_url || member.avatar || '/images/default-avatar.png'"
            :alt="member.name"
            class="-ml-2 h-9 w-9 flex-shrink-0 rounded-full border-2 border-white/60 object-cover first:ml-0"
          />
          <span
            v-if="membersCount > 5"
            class="-ml-2 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-white/25 text-xs font-semibold"
          >
            +{{ membersCount - 5 }}
          </span>
        </div>
        <button
          v-if="canManage !== false"
          type="button"
          class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-primary-700 shadow-sm md:w-auto"
          @click="emit('add-member')"
        >
          <Icon icon="fluent:person-add-24-regular" class="h-5 w-5" />
          <span>เพิ่มสมาชิก</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { getAcademyGroupTypeMeta } from '~/constants/academyGroupTypes'

defineProps<{
  department: any
  academyName: string
  parent: { id: number; name: string; type: string } | null
  head: { id: number; name: string; email?: string; avatar?: string } | null
  membersCount: number
  memberAvatars: any[]
  canManage?: boolean
}>()

const emit = defineEmits(['add-member'])

const formatDepartmentType = (type: string | null | undefined) =>
  getAcademyGroupTypeMeta(type).label
</script>
