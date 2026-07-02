<script setup lang="ts">
import type { StudentCardInfo } from '~/composables/useStudentProfile'

const props = defineProps<{
  studentCard: StudentCardInfo | null
  canEdit?: boolean
}>()
</script>

<template>
  <div class="space-y-4">
    <!-- No card -->
    <div v-if="!studentCard || !studentCard.exists"
         class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
      <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0" />
        </svg>
      </div>
      <p class="text-sm font-medium text-gray-700">ยังไม่มีบัตรนักเรียน</p>
      <p class="text-xs text-gray-400 mt-1">ติดต่อผู้ดูแลโรงเรียนเพื่อออกบัตร</p>
    </div>

    <!-- Card info -->
    <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-900">บัตรนักเรียน</h3>
        <span :class="[
          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
          studentCard.photo_status === 'approved' ? 'bg-green-100 text-green-700' :
          studentCard.photo_status === 'pending'  ? 'bg-yellow-100 text-yellow-700' :
                                                    'bg-gray-100 text-gray-500'
        ]">
          {{ studentCard.photo_status === 'approved' ? 'มีรูป' :
             studentCard.photo_status === 'pending'  ? 'รอตรวจสอบ' : 'ไม่มีรูป' }}
        </span>
      </div>

      <!-- Preview image -->
      <div v-if="studentCard.preview_url" class="flex justify-center">
        <img :src="studentCard.preview_url" alt="รูปบัตรนักเรียน"
             class="w-32 h-32 rounded-xl object-cover border border-gray-200 shadow-sm" />
      </div>
      <div v-else class="flex justify-center">
        <div class="w-32 h-32 rounded-xl bg-gray-100 flex items-center justify-center border border-gray-200">
          <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </div>
      </div>

      <dl class="grid grid-cols-2 gap-3 text-sm">
        <div>
          <dt class="text-xs text-gray-500">เลขบัตร</dt>
          <dd class="font-medium text-gray-900">{{ studentCard.card_number || '—' }}</dd>
        </div>
        <div>
          <dt class="text-xs text-gray-500">วันออกบัตร</dt>
          <dd class="font-medium text-gray-900">{{ studentCard.issued_at || '—' }}</dd>
        </div>
        <div>
          <dt class="text-xs text-gray-500">วันหมดอายุ</dt>
          <dd class="font-medium text-gray-900">{{ studentCard.expires_at || '—' }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>
