<script setup lang="ts">
import { Icon } from '@iconify/vue'
import MyProgressDetails from '@/components/learn/course/MyProgressDetails.vue'
import { inject, type Ref } from 'vue'

// Inject course data from parent
const course = inject<Ref<any>>('course')
const courseMemberOfAuth = inject<Ref<any>>('courseMemberOfAuth')

</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
      <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <Icon icon="mdi:graph-box-plus-outline" class="w-5 h-5 text-cyan-500" />
        ผลการเรียนของฉัน
      </h2>
    </div>

    <!-- Details Component -->
    <MyProgressDetails 
        v-if="course?.id && courseMemberOfAuth?.id"
        :courseId="course.id" 
        :memberId="courseMemberOfAuth.id" 
    />
    
    <div v-else class="text-center py-10 text-gray-500">
        ไม่พบข้อมูลรายวิชาหรือสมาชิก
    </div>
  </div>
</template>
