<script setup lang="ts">
import type { StudentAddress } from '~/composables/useStudentProfile'

defineProps<{
  addresses?: StudentAddress[]
}>()

const typeText = (type: string) => {
  const map: Record<string, string> = {
    current: 'ที่อยู่ปัจจุบัน',
    permanent: 'ที่อยู่ตามทะเบียนบ้าน',
    temporary: 'ที่อยู่ชั่วคราว',
  }
  return map[type] || type
}

const typeColor = (type: string) => {
  const map: Record<string, string> = {
    current: 'bg-green-100 text-green-800',
    permanent: 'bg-blue-100 text-blue-800',
    temporary: 'bg-amber-100 text-amber-800',
  }
  return map[type] || 'bg-gray-100 text-gray-800'
}

const formatAddress = (addr: StudentAddress) => {
  const parts = [
    addr.house_number ? `บ้านเลขที่ ${addr.house_number}` : null,
    addr.village_number ? `หมู่ ${addr.village_number}` : null,
    addr.village_name ? `หมู่บ้าน${addr.village_name}` : null,
    addr.alley ? `ซอย ${addr.alley}` : null,
    addr.road ? `ถนน ${addr.road}` : null,
    addr.subdistrict ? `ต.${addr.subdistrict}` : null,
    addr.district ? `อ.${addr.district}` : null,
    addr.province ? `จ.${addr.province}` : null,
    addr.postal_code || null,
  ].filter(Boolean)
  return parts.join(' ') || '-'
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-amber-500 to-orange-600 px-5 py-4">
      <div class="flex items-center">
        <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <h3 class="ml-3 text-lg font-semibold text-white">ที่อยู่</h3>
      </div>
    </div>
    <div class="p-5">
      <div v-if="!addresses || addresses.length === 0" class="text-center py-8">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
        </svg>
        <p class="text-sm text-gray-500">ยังไม่มีข้อมูลที่อยู่</p>
      </div>
      <div v-else class="space-y-3">
        <div v-for="addr in addresses" :key="addr.id" class="rounded-xl border border-gray-250 p-4">
          <div class="flex items-center gap-2 mb-2">
            <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', typeColor(addr.address_type)]">
              {{ typeText(addr.address_type) }}
            </span>
            <span v-if="addr.is_current" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-600 text-white">
              ปัจจุบัน
            </span>
          </div>
          <p class="text-sm text-gray-700 leading-relaxed">{{ formatAddress(addr) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
