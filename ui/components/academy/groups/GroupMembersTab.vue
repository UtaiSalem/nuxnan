<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  group: any
  admins: any[]
  members: any[]
  canManage: boolean
}
const props = defineProps<Props>()
const emit = defineEmits<{ openManage: [tab: 'admins' | 'members'] }>()

const query = ref('')
const filteredMembers = computed(() => {
  const q = query.value.toLowerCase().trim()
  if (!q) return props.members
  return props.members.filter((m) => {
    const name = m.name || m.user?.name || ''
    return name.toLowerCase().includes(q)
  })
})

const roleLabel: Record<string, string> = {
  leader: 'หัวหน้าส่วนงาน',
  co_leader: 'รองหัวหน้าส่วนงาน',
  advisor: 'ที่ปรึกษา',
  student: 'สมาชิก (นักเรียน)',
  teacher: 'สมาชิก (ครู)',
  admin: 'ผู้ดูแลกลุ่ม',
}
</script>

<template>
  <div class="space-y-5">
    <!-- Admins banner -->
    <section class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden border border-gray-200 dark:border-gray-700">
      <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <Icon icon="heroicons:star-solid" class="w-5 h-5 text-amber-500" />
          <h3 class="font-bold text-gray-900 dark:text-white text-sm">หัวหน้าส่วนงาน</h3>
          <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 font-bold">
            {{ admins.length }}
          </span>
        </div>
        <button
          v-if="canManage"
          class="text-xs font-bold text-vikinger-purple hover:underline"
          @click="emit('openManage', 'admins')"
        >
          จัดการ
        </button>
      </div>
      <div class="p-5">
        <div v-if="admins.length === 0" class="text-sm text-gray-450 text-center py-4 font-medium italic">
          ยังไม่มีหัวหน้าส่วนงานระบุไว้
        </div>
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          <div
            v-for="a in admins"
            :key="a.id"
            class="flex flex-col items-center text-center p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors"
          >
            <div class="w-16 h-16 rounded-full bg-gray-250 dark:bg-gray-750 overflow-hidden mb-2 shadow-sm flex-shrink-0">
              <img v-if="a.profile_photo_path" :src="a.profile_photo_path" class="w-full h-full object-cover" />
              <img v-else-if="a.user?.profile_photo_path" :src="a.user.profile_photo_path" class="w-full h-full object-cover" />
              <Icon v-else icon="heroicons:user" class="w-full h-full p-3 text-gray-400 dark:text-gray-500" />
            </div>
            <div class="text-sm font-semibold text-gray-900 dark:text-white truncate w-full">
              {{ a.name || a.user?.name }}
            </div>
            <div class="text-xs text-gray-500 font-medium mt-0.5">
              {{ a.pivot?.role === 'admin' ? 'แอดมินกลุ่ม' : a.pivot?.role === 'teacher' ? 'ครูประจำกลุ่ม' : roleLabel[a.role] || a.role || 'ผู้แลความเรียบร้อย' }}
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Members list -->
    <section class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden border border-gray-200 dark:border-gray-700">
      <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <Icon icon="heroicons:user-group" class="w-5 h-5 text-vikinger-purple" />
          <h3 class="font-bold text-gray-900 dark:text-white text-sm">สมาชิกกลุ่ม</h3>
          <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 font-bold">
            {{ members.length }}
          </span>
        </div>
        <button
          v-if="canManage"
          class="text-xs font-bold text-vikinger-purple hover:underline"
          @click="emit('openManage', 'members')"
        >
          จัดการ
        </button>
      </div>

      <!-- Search -->
      <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-vikinger-dark-100/20">
        <div class="relative">
          <Icon icon="heroicons:magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            v-model="query"
            type="text"
            placeholder="ค้นหาสมาชิกในส่วนงาน..."
            class="w-full pl-9 pr-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-vikinger-dark-100 text-sm focus:outline-none focus:border-vikinger-purple text-gray-700 dark:text-gray-250 font-medium"
          />
        </div>
      </div>

      <!-- List -->
      <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[500px] overflow-y-auto">
        <div v-if="filteredMembers.length === 0" class="py-8 text-center text-sm text-gray-500 font-medium italic">
          {{ query ? 'ไม่พบข้อมูลสมาชิกตามคำค้น' : 'ยังไม่มีสมาชิกอื่นในส่วนงานนี้' }}
        </div>
        <div
          v-for="m in filteredMembers"
          :key="m.id"
          class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100/50 transition-colors"
        >
          <div class="w-10 h-10 rounded-full bg-gray-250 dark:bg-gray-750 overflow-hidden flex-shrink-0 shadow-inner">
            <img v-if="m.profile_photo_path" :src="m.profile_photo_path" class="w-full h-full object-cover" />
            <img v-else-if="m.user?.profile_photo_path" :src="m.user.profile_photo_path" class="w-full h-full object-cover" />
            <Icon v-else icon="heroicons:user" class="w-full h-full p-2 text-gray-400 dark:text-gray-500" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
              {{ m.name || m.user?.name }}
            </div>
            <div class="text-xs text-gray-500 font-medium mt-0.5">
              {{ m.pivot?.role === 'admin' ? 'แอดมินกลุ่ม' : m.pivot?.role === 'teacher' ? 'ครูประจำกลุ่ม' : m.role || 'สมาชิก' }}
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
