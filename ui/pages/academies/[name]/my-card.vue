<script setup lang="ts">
/**
 * My Student Card
 * หน้าดูบัตรนักเรียนของตัวเอง
 */
import { Icon } from '@iconify/vue'

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

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${encodeURIComponent(academyName.value)}`)
    if (response.success) {
      academy.value = response.academy
      await fetchMyStudentCard()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
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

const downloadCard = async () => {
  if (!studentCard.value?.id) return
  
  try {
    const res = await api.get(`/api/student-cards/${studentCard.value.id}/download`, {
      responseType: 'blob'
    })
    const blob = new Blob([res as any], { type: 'image/png' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `student-card-${studentCard.value.card_number}.png`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Failed to download:', err)
    alert('ไม่สามารถดาวน์โหลดได้')
  }
}
</script>

<template>
  <NuxtLayout name="academy" :academy-name="academyName">
    <div class="min-h-screen bg-gradient-to-br from-purple-100 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-purple-900/20 py-8 px-4">
      <div class="max-w-xl mx-auto">
        <div v-if="isLoading" class="flex items-center justify-center py-20">
          <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
        </div>

        <div v-else class="space-y-6">
          <!-- Header -->
          <div class="text-center">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center justify-center gap-3">
              <Icon icon="fluent:card-ui-24-filled" class="w-7 h-7 text-purple-500" />
              บัตรนักเรียนของฉัน
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ academy?.name }}</p>
          </div>

          <!-- No Card State -->
          <div v-if="!studentCard" class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center shadow-lg border border-gray-200 dark:border-gray-700">
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
              class="relative w-full aspect-[1.6/1] cursor-pointer perspective-1000"
              @click="flipCard"
            >
              <!-- Card Inner -->
              <div 
                :class="[
                  'w-full h-full transition-transform duration-700 transform-style-preserve-3d',
                  isFlipped ? 'rotate-y-180' : ''
                ]"
              >
                <!-- Front Side -->
                <div class="absolute inset-0 backface-hidden">
                  <div class="w-full h-full bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-800 rounded-2xl shadow-2xl p-6 text-white overflow-hidden relative">
                    <!-- Decorative Elements -->
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>
                    
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4 relative z-10">
                      <div>
                        <h3 class="text-lg font-bold">{{ academy?.name }}</h3>
                        <p class="text-white/70 text-sm">บัตรนักเรียน</p>
                      </div>
                      <Icon icon="fluent:shield-checkmark-24-filled" class="w-10 h-10 text-white/80" />
                    </div>
                    
                    <!-- Content -->
                    <div class="flex gap-4 relative z-10">
                      <!-- Photo -->
                      <div class="flex-shrink-0">
                        <img
                          v-if="student?.user?.avatar"
                          :src="student.user.avatar"
                          :alt="student.user?.displayname"
                          class="w-20 h-24 rounded-lg object-cover border-2 border-white/30"
                        />
                        <div v-else class="w-20 h-24 rounded-lg bg-white/20 flex items-center justify-center border-2 border-white/30">
                          <Icon icon="fluent:person-24-filled" class="w-8 h-8 text-white/60" />
                        </div>
                      </div>
                      
                      <!-- Info -->
                      <div class="flex-1 space-y-1 text-sm">
                        <div>
                          <div class="text-white/60 text-xs">ชื่อ-นามสกุล</div>
                          <div class="font-semibold">{{ student?.user?.displayname }}</div>
                        </div>
                        <div>
                          <div class="text-white/60 text-xs">เลขประจำตัว</div>
                          <div class="font-semibold">{{ student?.student_number || studentCard?.card_number }}</div>
                        </div>
                        <div v-if="student?.classroom">
                          <div class="text-white/60 text-xs">ห้องเรียน</div>
                          <div class="font-semibold">{{ student.classroom.name }}</div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="absolute bottom-4 left-6 right-6 flex items-center justify-between text-xs text-white/60">
                      <span>หมายเลขบัตร: {{ studentCard?.card_number }}</span>
                      <span>คลิกเพื่อพลิกบัตร</span>
                    </div>
                  </div>
                </div>

                <!-- Back Side -->
                <div class="absolute inset-0 backface-hidden rotate-y-180">
                  <div class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-2xl shadow-2xl p-6 overflow-hidden relative">
                    <!-- Barcode/QR Section -->
                    <div class="flex flex-col items-center justify-center h-full">
                      <!-- QR Code Placeholder -->
                      <div class="w-32 h-32 bg-white dark:bg-gray-600 rounded-lg flex items-center justify-center mb-4 shadow-inner">
                        <Icon icon="fluent:qr-code-24-filled" class="w-20 h-20 text-gray-400 dark:text-gray-300" />
                      </div>
                      
                      <!-- Barcode -->
                      <div class="w-full max-w-xs h-12 bg-white dark:bg-gray-600 rounded flex items-center justify-center mb-2">
                        <div class="flex gap-px">
                          <div v-for="i in 40" :key="i" :class="['h-8', Math.random() > 0.5 ? 'w-1 bg-black dark:bg-white' : 'w-0.5 bg-black dark:bg-white']"></div>
                        </div>
                      </div>
                      
                      <p class="text-sm text-gray-600 dark:text-gray-400 font-mono">{{ studentCard?.card_number }}</p>
                      
                      <!-- Contact Info -->
                      <div class="absolute bottom-4 left-0 right-0 text-center text-xs text-gray-500 dark:text-gray-400">
                        <p>หากพบบัตรนี้ กรุณาติดต่อ {{ academy?.name }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Info & Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
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
                    {{ studentCard?.expires_at ? new Date(studentCard.expires_at).toLocaleDateString('th-TH') : 'ไม่มีกำหนด' }}
                  </div>
                </div>
              </div>
              
              <div class="flex gap-3">
                <button
                  @click="downloadCard"
                  class="flex-1 px-4 py-3 bg-purple-500 hover:bg-purple-600 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
                >
                  <Icon icon="fluent:arrow-download-24-filled" class="w-5 h-5" />
                  ดาวน์โหลด
                </button>
                <NuxtLink
                  :to="`/academies/${academyName}/my-transcript`"
                  class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  <Icon icon="fluent:document-text-24-regular" class="w-5 h-5" />
                  ดูผลการเรียน
                </NuxtLink>
              </div>
            </div>

            <!-- Tips -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
              <div class="flex items-start gap-3">
                <Icon icon="fluent:lightbulb-24-filled" class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" />
                <div class="text-sm text-yellow-800 dark:text-yellow-200">
                  <p class="font-medium mb-1">เคล็ดลับ</p>
                  <p class="text-yellow-700 dark:text-yellow-300">คุณสามารถคลิกที่บัตรเพื่อพลิกดูด้านหลัง ซึ่งมี QR Code และบาร์โค้ดสำหรับสแกน</p>
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
