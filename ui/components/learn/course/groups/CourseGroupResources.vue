<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import CourseFeedsList from '~/components/learn/course/CourseFeedsList.vue'

interface Props {
  courseId: string | number
  groupId: string | number
  isCourseAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 sm:p-6 min-h-[400px]">
    <div class="mb-6 flex items-center justify-between">
      <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <Icon icon="heroicons:document-duplicate" class="w-6 h-6 text-blue-500" />
        ไฟล์และเอกสารประกอบการเรียน
      </h2>
    </div>

    <!-- Reuse CourseFeedsList but filter only materials -->
    <!-- Note: CourseFeedsList doesn't support forced tab/type via props yet based on my reading, 
         but I can add that or just rely on the user clicking the tab.
         Actually, I'll modify CourseFeedsList to accept a default tab or fixed tab.
         For now, let's just use it and user can filter.
         Wait, better implementation: Pass a prop to CourseFeedsList to lock it to 'materials' or default it.
    -->
    <CourseFeedsList 
      :course-id="props.courseId"
      :group-id="props.groupId"
      :is-course-admin="props.isCourseAdmin"
      initial-tab="materials"
    />
  </div>
</template>
