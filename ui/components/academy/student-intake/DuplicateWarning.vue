<script setup lang="ts">
import { Icon } from '@iconify/vue'
import type { DuplicateCheckMatch } from '~/types/studentIntake'

defineProps<{
  matches: DuplicateCheckMatch[]
}>()

const emit = defineEmits(['proceed', 'cancel'])
</script>

<template>
  <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
    <div class="flex items-start gap-3">
      <Icon icon="fluent:warning-24-filled" class="w-6 h-6 text-amber-500 flex-shrink-0 mt-0.5" />
      <div class="flex-1">
        <h4 class="text-amber-800 dark:text-amber-400 font-medium mb-1">
          พบข้อมูลที่อาจซ้ำซ้อนในระบบ
        </h4>
        <p class="text-sm text-amber-700 dark:text-amber-300 mb-3">
          รหัสนักเรียนหรือเลขประจำตัวประชาชนที่ระบุ อาจมีอยู่ในระบบแล้ว กรุณาตรวจสอบข้อมูลด้านล่าง:
        </p>
        
        <div class="space-y-2 mb-4">
          <div 
            v-for="match in matches" 
            :key="match.id"
            class="bg-white/50 dark:bg-black/20 rounded p-2.5 text-sm"
          >
            <div class="font-medium text-gray-900 dark:text-white">
              {{ match.first_name_th }} {{ match.last_name_th }}
            </div>
            <div class="text-gray-600 dark:text-gray-400 mt-1 flex flex-wrap gap-x-4 gap-y-1">
              <span>รหัส: <strong :class="{'text-amber-600 dark:text-amber-400': match.matched_fields.includes('student_id')}">{{ match.student_id }}</strong></span>
              <span v-if="match.citizen_id">เลขบัตร: <strong :class="{'text-amber-600 dark:text-amber-400': match.matched_fields.includes('citizen_id')}">{{ match.citizen_id }}</strong></span>
              <span>สถานะ: <strong>{{ match.status }}</strong></span>
            </div>
          </div>
        </div>
        
        <div class="flex gap-2">
          <button 
            type="button" 
            @click="emit('cancel')"
            class="min-h-[44px] sm:min-h-0 px-4 py-2 text-sm font-medium text-amber-900 dark:text-amber-100 bg-amber-100 dark:bg-amber-800 hover:bg-amber-200 dark:hover:bg-amber-700 rounded-lg transition-colors"
          >
            ยกเลิกและแก้ไขข้อมูล
          </button>
          <button 
            type="button" 
            @click="emit('proceed')"
            class="min-h-[44px] sm:min-h-0 px-4 py-2 text-sm font-medium text-amber-900 dark:text-amber-100 border border-amber-300 dark:border-amber-700 hover:bg-amber-100 dark:hover:bg-amber-800/50 rounded-lg transition-colors"
          >
            ยืนยันดำเนินการต่อ
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
