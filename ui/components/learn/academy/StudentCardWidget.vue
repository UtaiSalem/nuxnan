<script setup lang="ts">
/**
 * StudentCardWidget - วิดเจ็ตบัตรประจำตัวนักเรียนแบบย่อ
 * แสดงในไซด์บาร์ของหน้าโรงเรียน สำหรับสมาชิกที่ได้รับอนุมัติแล้ว
 */
import { onMounted, ref } from 'vue'
import { Icon } from '@iconify/vue'
import { useApi } from '../../../composables/useApi'

const props = defineProps<{
  academyId: number
  academyName: string
}>()

const api = useApi()

// State
const studentCard = ref<any>(null)
const student = ref<any>(null)
const isLoading = ref(true)
const hasError = ref(false)
const hasData = ref(false)

const fetchStudentCard = async () => {
  isLoading.value = true
  hasError.value = false
  try {
    const res: any = await api.get(`/api/academies/my-student-card`, {
      params: { academy_id: props.academyId }
    })
    if (res.success) {
      studentCard.value = res.studentCard
      student.value = res.student
      hasData.value = !!(res.studentCard && res.student)
    }
  } catch (err) {
    console.error('Failed to fetch student card:', err)
    hasError.value = true
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchStudentCard()
})
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden">
    <!-- Header -->
    <div class="px-5 pt-5 pb-3">
      <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <Icon icon="fluent:card-ui-24-filled" class="w-5 h-5 text-purple-500" />
        บัตรประจำตัวนักเรียน
      </h3>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="px-4 pb-4">
      <div class="rounded-xl bg-gray-100 dark:bg-vikinger-dark-100 p-4 animate-pulse">
        <div class="flex gap-3">
          <div class="w-14 h-[68px] rounded-lg bg-gray-200 dark:bg-gray-700"></div>
          <div class="flex-1 space-y-2 py-1">
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-2/3"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="hasError" class="px-4 pb-4">
      <div class="rounded-xl bg-red-50 dark:bg-red-900/20 p-4 text-center">
        <Icon icon="fluent:error-circle-24-regular" class="w-8 h-8 text-red-400 mx-auto mb-2" />
        <p class="text-sm text-red-600 dark:text-red-400 mb-3">ไม่สามารถโหลดข้อมูลบัตรได้</p>
        <button
          @click="fetchStudentCard"
          class="text-sm text-red-600 dark:text-red-400 underline hover:no-underline"
        >
          ลองใหม่
        </button>
      </div>
    </div>

    <!-- Has Card Data -->
    <template v-else-if="hasData">
      <!-- Mini Card -->
      <div class="mx-4 mb-3 rounded-xl overflow-hidden bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-800 p-4 text-white relative">
        <!-- Decorative circles -->
        <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-0 w-16 h-16 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>

        <div class="flex gap-3 relative z-10">
          <!-- Photo -->
          <div class="flex-shrink-0">
            <img
              v-if="student?.user?.avatar"
              :src="student.user.avatar"
              :alt="student.user?.displayname"
              class="w-14 h-[68px] rounded-lg object-cover border-2 border-white/30"
            />
            <div v-else class="w-14 h-[68px] rounded-lg bg-white/20 flex items-center justify-center border-2 border-white/30">
              <Icon icon="fluent:person-24-filled" class="w-6 h-6 text-white/60" />
            </div>
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0 space-y-1">
            <div>
              <div class="text-white/60 text-[10px] leading-none">ชื่อ-นามสกุล</div>
              <div class="font-semibold text-sm truncate">{{ studentCard?.full_name_thai || student?.user?.displayname }}</div>
            </div>
            <div>
              <div class="text-white/60 text-[10px] leading-none">เลขประจำตัว</div>
              <div class="font-semibold text-sm">{{ studentCard?.student_number || student?.student_id }}</div>
            </div>
            <div v-if="studentCard?.level_and_room">
              <div class="text-white/60 text-[10px] leading-none">ระดับชั้น</div>
              <div class="font-semibold text-sm">{{ studentCard.level_and_room }}</div>
            </div>
          </div>
        </div>

        <!-- Card number footer -->
        <div class="mt-2 pt-2 border-t border-white/20 text-[10px] text-white/50 relative z-10">
          เลขประจำตัว: {{ studentCard?.student_number || student?.student_id }}
        </div>
      </div>

      <!-- View Full Card Button - ปิดไว้ชั่วคราว -->
      <!-- <div class="px-4 pb-4">
        <NuxtLink
          :to="`/academies/${academyName}/my-card`"
          class="flex items-center justify-center gap-2 w-full py-2.5 text-sm font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 rounded-lg transition-colors"
        >
          <Icon icon="fluent:open-24-regular" class="w-4 h-4" />
          ดูบัตรเต็ม
        </NuxtLink>
      </div> -->
    </template>

    <!-- No Card Data -->
    <div v-else class="px-4 pb-4">
      <div class="rounded-xl bg-gray-50 dark:bg-vikinger-dark-100 p-5 text-center">
        <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
          <Icon icon="fluent:card-ui-24-regular" class="w-8 h-8 text-purple-400" />
        </div>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ยังไม่มีบัตรนักเรียน</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">กรุณาติดต่อเจ้าหน้าที่เพื่อออกบัตร</p>
        <!-- ปิดไว้ชั่วคราว
        <NuxtLink
          :to="`/academies/${academyName}/my-card`"
          class="inline-flex items-center gap-1.5 text-sm font-medium text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 transition-colors"
        >
          <Icon icon="fluent:arrow-right-24-regular" class="w-4 h-4" />
          ไปหน้าบัตรนักเรียน
        </NuxtLink>
        -->
      </div>
    </div>
  </div>
</template>
