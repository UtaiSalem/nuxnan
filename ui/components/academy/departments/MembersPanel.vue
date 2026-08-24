<template>
  <div class="relative flex flex-col bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 rounded-2xl">
    <!-- Header -->
    <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6 pb-0 sm:pb-0">
      <div>
        <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-1">สมาชิกฝ่าย</h4>
        <p class="text-sm text-gray-500">เพิ่ม นำออก และกำหนดบทบาทของสมาชิกในฝ่าย</p>
      </div>
      <button v-if="canManage !== false" type="button" class="inline-flex min-h-[44px] w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 shadow-sm" @click="emit('add-member')">
        <Icon icon="fluent:person-add-24-regular" class="h-5 w-5" />
        <span>เพิ่มสมาชิก</span>
      </button>
    </div>
    
    <!-- Body -->
    <div class="p-4 sm:p-6 pt-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center mb-4">
        <div class="relative min-w-0 flex-1">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <Icon icon="fluent:search-24-regular" class="h-5 w-5 text-gray-400" />
          </div>
          <input v-model="search" type="text" placeholder="ค้นหาสมาชิก..." class="block min-h-[44px] w-full rounded-xl border border-gray-300 bg-white py-2 pl-10 pr-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
        </div>
        <select v-model="roleFilter" class="block min-h-[44px] w-full sm:w-auto flex-shrink-0 rounded-xl border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
          <option value="all">ทุกบทบาท</option>
          <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
      </div>
      
      <p class="text-sm text-gray-500 mb-4">แสดง {{ filteredMembers.length }} จาก {{ members.length }} คน</p>
      
      <div v-if="isLoading" class="flex justify-center py-8">
        <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary-500 border-t-transparent"></div>
      </div>
      <div v-else-if="filteredMembers.length === 0" class="flex flex-col items-center justify-center py-12 text-gray-500 text-center">
        <Icon :icon="members.length === 0 ? 'fluent:people-24-regular' : 'fluent:search-24-regular'" class="mb-3 h-12 w-12 opacity-50 text-gray-400" />
        <template v-if="members.length === 0">
          <p class="text-base font-medium text-gray-900 dark:text-white mb-1">ยังไม่มีสมาชิกในฝ่ายนี้</p>
          <p class="text-sm">กด "เพิ่มสมาชิก" เพื่อดึงครูและบุคลากรเข้าฝ่าย</p>
        </template>
        <template v-else>
          <p class="text-base font-medium text-gray-900 dark:text-white mb-1">ไม่พบสมาชิกที่ตรงกับเงื่อนไข</p>
          <p class="text-sm">ลองล้างคำค้นหรือเปลี่ยนตัวกรองบทบาท</p>
        </template>
      </div>
      <div v-else class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-700">
        <table class="min-w-full overflow-hidden divide-y divide-gray-100 dark:divide-gray-700">
          <thead>
            <tr class="bg-gray-50 dark:bg-gray-900/50">
              <th class="px-3 py-3 sm:px-6 sm:py-4 text-left text-sm font-medium text-gray-500 whitespace-nowrap">สมาชิก</th>
              <th class="px-3 py-3 sm:px-6 sm:py-4 text-left text-sm font-medium text-gray-500 whitespace-nowrap">บทบาท</th>
              <th class="px-3 py-3 sm:px-6 sm:py-4 text-left text-sm font-medium text-gray-500 whitespace-nowrap">เข้าร่วมเมื่อ</th>
              <th class="px-3 py-3 sm:px-6 sm:py-4 text-center text-sm font-medium text-gray-500 whitespace-nowrap">จัดการ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
            <tr v-for="member in filteredMembers" :key="member.id">
              <td class="px-3 py-3 sm:px-6 sm:py-4">
                <div class="flex items-center gap-3">
                  <img :src="member.profile_photo_url || member.avatar || '/images/default-avatar.png'" class="h-10 w-10 flex-shrink-0 rounded-full object-cover border border-gray-200 dark:border-gray-700" alt="" />
                  <div class="min-w-0">
                    <div class="flex items-center gap-2">
                      <p class="font-medium text-gray-900 dark:text-white truncate max-w-[150px] sm:max-w-xs" :title="member.name">{{ member.name }}</p>
                      <span v-if="member.id === headUserId" class="flex-shrink-0 rounded bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">หัวหน้าฝ่าย</span>
                    </div>
                    <p class="text-xs text-gray-500 truncate max-w-[150px] sm:max-w-xs" :title="member.email">{{ member.email || '-' }}</p>
                  </div>
                </div>
              </td>
              <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                <select :value="member.role" class="block min-h-[36px] w-full rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-8 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" :disabled="canManage === false" @change="(e) => emit('change-role', { userId: member.id, role: (e.target as HTMLSelectElement).value })">
                  <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
              </td>
              <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ formatDate(member.joined_at) }}
              </td>
              <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                <div class="flex items-center justify-center gap-1">
                  <button v-if="canManage !== false && member.id !== headUserId" type="button" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-400 hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-900/30" title="ตั้งเป็นหัวหน้าฝ่าย" aria-label="ตั้งเป็นหัวหน้าฝ่าย" @click="emit('set-head', member.id)">
                    <Icon icon="fluent:person-star-24-regular" class="h-5 w-5" />
                  </button>
                  <button v-if="canManage !== false" type="button" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/30" title="นำออกจากฝ่าย" aria-label="นำออกจากฝ่าย" @click="emit('remove', member.id)">
                    <Icon icon="fluent:person-delete-24-regular" class="h-5 w-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = withDefaults(defineProps<{
  members: any[]
  isLoading: boolean
  headUserId: number | null
  canManage?: boolean
}>(), {
  canManage: true
})

const emit = defineEmits(['add-member', 'change-role', 'remove', 'set-head'])

const search = ref('')
const roleFilter = ref('all')

const roleOptions = [
  { value: 'head', label: 'หัวหน้าฝ่าย' },
  { value: 'admin', label: 'ผู้ดูแลฝ่าย' },
  { value: 'staff', label: 'เจ้าหน้าที่' },
  { value: 'member', label: 'สมาชิก' },
]

const filteredMembers = computed(() => {
  return props.members.filter(m => {
    let match = true
    if (search.value) {
      const s = search.value.toLowerCase()
      match = (m.name || '').toLowerCase().includes(s) || (m.email || '').toLowerCase().includes(s)
    }
    if (match && roleFilter.value !== 'all') {
      match = m.role === roleFilter.value
    }
    return match
  })
})

const formatDate = (val: string | null | undefined) => {
  if (!val) return '-'
  try {
    return new Date(val).toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' })
  } catch {
    return '-'
  }
}
</script>
