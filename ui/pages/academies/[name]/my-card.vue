<script setup lang="ts">
/**
 * My Student Card
 * หน้าดูบัตรนักเรียนของตัวเอง — ใช้ StudentCardFront/Back component เหมือนระบบเดิม
 */
import { Icon } from '@iconify/vue'
import StudentCardFront from '~/components/learn/student-card/StudentCardFront.vue'
import StudentCardBack from '~/components/learn/student-card/StudentCardBack.vue'

definePageMeta({
  layout: false
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const isLoading = ref(true)
const studentCard = ref<any>(null)
const student = ref<any>(null)
const isFlipped = ref(false)

onMounted(() => {
  navigateTo(`/academies/${academyName.value}/my-profile?tab=card`, { replace: true })
})

const fetchMyStudentCard = async () => {
  try {
    const res: any = await api.get(`/api/academies/my-student-card`, {
      params: { academy_id: academy.value.id }
    })
    if (res.success) {
      studentCard.value = res.studentCard
      student.value = res.student
    }
  } catch (err) {
    console.error('Failed to fetch student card:', err)
  }
}

const flipCard = () => {
  isFlipped.value = !isFlipped.value
}

// Map studentCard data to StudentCardFront props
const cardStudent = computed(() => {
  const card = studentCard.value
  const stu = student.value
  if (!card && !stu) return null
  return {
    id: card?.id || stu?.id,
    student_number: card?.student_number || stu?.student_id,
    title_name: card?.title_name || stu?.title_prefix_th,
    first_name_thai: card?.first_name_thai || stu?.first_name_th,
    last_name_thai: card?.last_name_thai || stu?.last_name_th,
    full_name_thai: card?.full_name_thai || '',
    first_name_english: card?.first_name_english || stu?.first_name_en,
    national_id: card?.national_id || stu?.citizen_id,
    class_level: card?.class_level,
    class_section: card?.class_section,
    birth_date: card?.birth_date || stu?.birth_date,
    card_expiry_date: card?.card_expiry_date,
    profile_image: card?.profile_image,
  }
})

const academyAddress = computed(() => {
  return academy.value?.address || ''
})

const academyLogo = computed(() => {
  return academy.value?.logo || '/images/default-school-logo.png'
})

const academyDisplayName = computed(() => {
  return academy.value?.name || 'โรงเรียน'
})
</script>

<template>
  <NuxtLayout name="academy" :academy-name="academyName">
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-blue-900/20 py-8 px-0 sm:px-4">
      <div class="max-w-2xl mx-auto">
        <div v-if="isLoading" class="flex items-center justify-center py-20">
          <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
        </div>

        <div v-else class="space-y-6">
          <!-- Header -->
          <div class="text-center">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center justify-center gap-3">
              <Icon icon="fluent:card-ui-24-filled" class="w-7 h-7 text-blue-500" />
              บัตรนักเรียนของฉัน
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ academy?.name }}</p>
          </div>

          <!-- No Card State -->
          <div v-if="!cardStudent" class="bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-12 text-center shadow-lg border border-gray-200 dark:border-gray-700">
            <Icon icon="fluent:card-ui-24-regular" class="w-20 h-20 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
            <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีบัตรนักเรียน</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">คุณยังไม่ได้รับบัตรนักเรียน กรุณาติดต่อเจ้าหน้าที่</p>
            <NuxtLink
              :to="`/academies/${academyName}`"
              class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors"
            >
              <Icon icon="fluent:arrow-left-24-filled" class="w-5 h-5" />
              กลับหน้าหลัก
            </NuxtLink>
          </div>

          <!-- Student Card -->
          <div v-else class="space-y-6">
            <!-- Card Container with Flip Animation -->
            <div 
              class="relative w-full cursor-pointer perspective-1000"
              @click="flipCard"
            >
              <!-- Card Inner -->
              <div 
                :class="[
                  'w-full transition-transform duration-700 transform-style-preserve-3d',
                  isFlipped ? 'rotate-y-180' : ''
                ]"
              >
                <!-- Front Side -->
                <div class="backface-hidden">
                  <StudentCardFront
                    :student="cardStudent"
                    :academy-name="academyDisplayName"
                    :academy-logo="academyLogo"
                    :academy-address="academyAddress"
                    :show-qr-code="true"
                    card-id="my-student-card-front"
                  />
                </div>

                <!-- Back Side -->
                <div class="absolute inset-0 backface-hidden rotate-y-180">
                  <StudentCardBack
                    :academy-name="academyDisplayName"
                    :academy-logo="academyLogo"
                    :academy-address="academyAddress"
                    card-id="my-student-card-back"
                  />
                </div>
              </div>
            </div>

            <!-- Info & Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 shadow-sm border border-gray-200 dark:border-gray-700">
              <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <div class="text-sm text-gray-500 dark:text-gray-400">สถานะ</div>
                  <div class="font-semibold text-green-600 dark:text-green-400 flex items-center justify-center gap-1">
                    <Icon icon="fluent:checkmark-circle-24-filled" class="w-4 h-4" />
                    ใช้งานได้
                  </div>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <div class="text-sm text-gray-500 dark:text-gray-400">วันหมดอายุ</div>
                  <div class="font-semibold text-gray-900 dark:text-white">
                    {{ studentCard?.card_expiry_date ? new Date(studentCard.card_expiry_date).toLocaleDateString('th-TH') : 'ไม่มีกำหนด' }}
                  </div>
                </div>
              </div>
              
              <div class="flex gap-3">
                <NuxtLink
                  :to="`/academies/${academyName}`"
                  class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  <Icon icon="fluent:arrow-left-24-filled" class="w-5 h-5" />
                  กลับหน้าหลัก
                </NuxtLink>
              </div>
            </div>

            <!-- Tips -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
              <div class="flex items-start gap-3">
                <Icon icon="fluent:lightbulb-24-filled" class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                <div class="text-sm text-blue-800 dark:text-blue-200">
                  <p class="font-medium mb-1">เคล็ดลับ</p>
                  <p class="text-blue-700 dark:text-blue-300">คุณสามารถคลิกที่บัตรเพื่อพลิกดูด้านหลัง ซึ่งมีเงื่อนไขการใช้บัตร</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<style scoped>
.perspective-1000 {
  perspective: 1000px;
}

.transform-style-preserve-3d {
  transform-style: preserve-3d;
}

.backface-hidden {
  backface-visibility: hidden;
}

.rotate-y-180 {
  transform: rotateY(180deg);
}
</style>
