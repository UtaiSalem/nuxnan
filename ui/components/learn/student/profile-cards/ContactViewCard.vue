<script setup lang="ts">
import type { StudentContact } from '~/composables/useStudentProfile'

defineProps<{
  contacts?: StudentContact[]
}>()

const typeText = (type: string) => {
  const map: Record<string, string> = {
    phone: 'โทรศัพท์',
    mobile: 'มือถือ',
    email: 'อีเมล',
    line: 'LINE',
    facebook: 'Facebook',
  }
  return map[type] || type
}

const typeIcon = (type: string) => {
  const map: Record<string, string> = {
    phone: '📞',
    mobile: '📱',
    email: '✉️',
    line: '💬',
    facebook: '👤',
  }
  return map[type] || '📋'
}

const typeColor = (type: string) => {
  const map: Record<string, string> = {
    phone: 'bg-blue-100 text-blue-800',
    mobile: 'bg-green-100 text-green-800',
    email: 'bg-purple-100 text-purple-800',
    line: 'bg-emerald-100 text-emerald-800',
    facebook: 'bg-indigo-100 text-indigo-800',
  }
  return map[type] || 'bg-gray-100 text-gray-800'
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-teal-500 to-cyan-600 px-5 py-4">
      <div class="flex items-center">
        <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
          </svg>
        </div>
        <h3 class="ml-3 text-lg font-semibold text-white">ข้อมูลติดต่อ</h3>
      </div>
    </div>
    <div class="p-5">
      <div v-if="!contacts || contacts.length === 0" class="text-center py-8">
        <p class="text-sm text-gray-500">ยังไม่มีข้อมูลติดต่อ</p>
      </div>
      <div v-else class="space-y-3">
        <div v-for="contact in contacts" :key="contact.id" class="flex items-center gap-3 rounded-xl border border-gray-250 p-3">
          <span class="text-xl flex-shrink-0">{{ typeIcon(contact.contact_type) }}</span>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', typeColor(contact.contact_type)]">
                {{ typeText(contact.contact_type) }}
              </span>
              <span v-if="contact.is_primary" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-teal-600 text-white">หลัก</span>
            </div>
            <p class="text-sm font-medium text-gray-900 mt-1 truncate">{{ contact.contact_value }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
