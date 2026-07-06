<script setup lang="ts">
/**
 * Academy Student Card Modal
 * แสดงบัตรประจำตัวนักเรียนในรูปแบบ Modal
 */
import { Icon } from '@iconify/vue'

interface Props {
  modelValue: boolean
  member: any | null
  academyName: string
  academyId: number | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const api = useApi()

const student = ref<any>(null)
const isLoading = ref(false)
const hasError = ref(false)

// Fetch student card data when modal opens
watch(() => props.modelValue, async (isVisible) => {
  if (isVisible && props.member?.student_id) {
    await fetchStudentCard()
  }
})

async function fetchStudentCard() {
  isLoading.value = true
  hasError.value = false
  student.value = null

  try {
    try {
      // Try academy-specific API first
      const response: any = await api.get(
        `/api/academies/${props.academyId}/student-cards/by-student/${props.member.student_id}`
      )
      if (response.success) {
        student.value = response.student
        return
      }
    } catch (err) {
      // Fallback to legacy API
      try {
        const response: any = await api.get(
          `/api/student-card/profile/${props.member.student_id}`
        )
        if (response.student) {
          student.value = response.student
          return
        }
      } catch (legacyErr) {
        // Both APIs failed
      }
    }

    // If we also have student data inline, use that as fallback
    if (!student.value && props.member?.student) {
      student.value = {
        first_name_thai: props.member.student.full_name_th?.split(' ')[0] || props.member.member_name?.split(' ')[0] || '',
        last_name_thai: props.member.student.full_name_th?.split(' ').slice(1).join(' ') || '',
        student_number: props.member.student.student_id || props.member.member_code || '',
        class_level: props.member.student.class_level || '',
        class_section: props.member.student.class_section || '',
        profile_image: props.member.student.profile_image || null,
      }
    }

    if (!student.value) {
      hasError.value = true
    }
  } finally {
    isLoading.value = false
  }
}

function getProfileImage() {
  if (!student.value) return '/images/default-avatar.png'
  return student.value.profile_image_url || props.member?.member_avatar || '/images/default-avatar.png'
}

function closeModal() {
  isOpen.value = false
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="closeModal"
      >
        <Transition
          enter-active-class="transition-all duration-200"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition-all duration-200"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div 
            v-if="isOpen"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md overflow-hidden"
          >
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <Icon icon="fluent:person-card-24-regular" class="w-5 h-5 text-primary-500" />
                บัตรประจำตัวนักเรียน
              </h3>
              <button 
                @click="closeModal"
                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              >
                <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
              </button>
            </div>

            <!-- Body -->
            <div class="p-6">
              <!-- Loading State -->
              <div v-if="isLoading" class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
              </div>

              <!-- Error State -->
              <div v-else-if="hasError" class="text-center py-12">
                <Icon icon="fluent:person-card-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                <p class="text-gray-500 dark:text-gray-400 mb-2">ไม่พบข้อมูลบัตรนักเรียน</p>
                <p class="text-sm text-gray-400 dark:text-gray-500">
                  อาจยังไม่ได้สร้างบัตรประจำตัวสำหรับนักเรียนคนนี้
                </p>
              </div>

              <!-- Student Card Preview -->
              <div v-else-if="student" class="space-y-4">
                <!-- Card -->
                <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-5 text-white shadow-xl">
                  <!-- School Header -->
                  <div class="text-center mb-3">
                    <h2 class="text-base font-bold">{{ member?.academy_name || 'โรงเรียน' }}</h2>
                    <p class="text-xs opacity-80">บัตรประจำตัวนักเรียน</p>
                  </div>
                  
                  <!-- Photo & Info -->
                  <div class="flex gap-4">
                    <div class="w-20 h-[6.5rem] bg-white rounded-lg overflow-hidden shrink-0">
                      <img 
                        :src="getProfileImage()"
                        :alt="student.first_name_thai"
                        class="w-full h-full object-cover"
                        @error="($event.target as HTMLImageElement).src = '/images/default-avatar.png'"
                      />
                    </div>
                    
                    <div class="flex-1 space-y-1.5 min-w-0">
                      <div>
                        <p class="text-[10px] opacity-70">ชื่อ-นามสกุล</p>
                        <p class="font-semibold text-sm leading-tight truncate">
                          {{ student.first_name_thai }} {{ student.last_name_thai }}
                        </p>
                      </div>
                      <div>
                        <p class="text-[10px] opacity-70">รหัสนักเรียน</p>
                        <p class="font-mono text-base">{{ student.student_number }}</p>
                      </div>
                      <div class="flex gap-4">
                        <div>
                          <p class="text-[10px] opacity-70">ชั้น</p>
                          <p class="text-sm">ม.{{ student.class_level }}</p>
                        </div>
                        <div>
                          <p class="text-[10px] opacity-70">ห้อง</p>
                          <p class="text-sm">{{ student.class_section }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Barcode Area -->
                  <div class="mt-3 pt-3 border-t border-white/20 text-center">
                    <div class="bg-white text-black font-mono text-xs py-1 px-3 rounded inline-block">
                      {{ student.student_number }}
                    </div>
                  </div>
                </div>

                <!-- Additional Info -->
                <div v-if="student.first_name_english || student.birth_date || student.national_id" 
                  class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 space-y-2"
                >
                  <div v-if="student.first_name_english" class="flex justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">ชื่อ (อังกฤษ)</span>
                    <span class="text-sm text-gray-900 dark:text-white">
                      {{ student.first_name_english }} {{ student.last_name_english || '' }}
                    </span>
                  </div>
                  <div v-if="student.birth_date" class="flex justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">วันเกิด</span>
                    <span class="text-sm text-gray-900 dark:text-white">{{ student.birth_date }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
              <NuxtLink
                v-if="student && member?.student_id"
                :to="`/academies/${academyName}/admin/student-cards/${member.student_id}`"
                class="flex items-center gap-2 px-4 py-2 text-primary-600 hover:text-primary-700 hover:bg-primary-50 dark:hover:bg-primary-900/30 rounded-lg transition-colors text-sm font-medium"
                @click="closeModal"
              >
                <Icon icon="fluent:open-24-regular" class="w-4 h-4" />
                ดูรายละเอียด
              </NuxtLink>
              <div v-else></div>
              <button
                @click="closeModal"
                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors text-sm"
              >
                ปิด
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
