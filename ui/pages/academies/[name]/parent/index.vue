<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 dark:from-blue-800 dark:to-indigo-950 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
          <div class="flex items-center space-x-4">
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
              <Icon icon="fluent:person-heart-24-filled" class="w-10 h-10" />
            </div>
            <div>
              <h1 class="text-2xl font-bold">สวัสดี, {{ $auth?.user?.name }}</h1>
              <p class="text-blue-100">{{ academy?.name }} • แดชบอร์ดผู้ปกครอง</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <NuxtLink 
              :to="`/academies/${academyName}/parent/meetings`"
              class="px-4 py-2 bg-white text-blue-700 hover:bg-blue-50 rounded-xl font-semibold shadow-sm transition-colors flex items-center gap-2"
            >
              <Icon icon="fluent:calendar-chat-24-regular" class="w-5 h-5" />
              นัดพบครู
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
      <!-- Children Cards -->
      <section>
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:people-community-24-regular" class="w-6 h-6 text-blue-600" />
            บุตรหลานของฉัน
          </h2>
        </div>

        <div v-if="loadingChildren" class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div v-for="i in 3" :key="i" class="h-32 bg-gray-200 dark:bg-gray-800 animate-pulse rounded-xl"></div>
        </div>

        <div v-else-if="children.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
          <div class="w-20 h-20 bg-gray-50 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
            <Icon icon="fluent:person-search-24-regular" class="w-10 h-10 text-gray-400" />
          </div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ไม่พบข้อมูลบุตรหลาน</h3>
          <p class="text-gray-500 dark:text-gray-400 mt-1 max-w-xs mx-auto">หากคุณมีบุตรหลานเรียนอยู่ที่นี่ กรุณาติดต่อฝ่ายธุรการเพื่อเชื่อมโยงบัญชี</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="child in children"
            :key="child.id"
            :class="[
              'group bg-white dark:bg-gray-800 rounded-2xl shadow-sm border-2 transition-all cursor-pointer overflow-hidden',
              selectedChild?.id === child.id 
                ? 'border-blue-500 ring-4 ring-blue-500/10' 
                : 'border-transparent hover:border-blue-200 dark:hover:border-blue-900'
            ]"
            @click="selectChild(child)"
          >
            <div class="p-5">
              <div class="flex items-center space-x-4">
                <div class="relative">
                  <img
                    :src="child.photo || '/images/default-student.png'"
                    :alt="child.name"
                    class="w-16 h-16 rounded-2xl object-cover border-2 border-gray-100 dark:border-gray-700 group-hover:scale-105 transition-transform"
                  />
                  <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-green-500 border-2 border-white dark:border-gray-800 rounded-full"></div>
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="font-bold text-gray-900 dark:text-white truncate text-lg">{{ child.name }}</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">รหัส: {{ child.student_id }}</p>
                  <p class="text-sm font-medium text-blue-600 dark:text-blue-400 mt-0.5">{{ child.classroom }}</p>
                </div>
                <Icon icon="fluent:chevron-right-24-regular" class="w-6 h-6 text-gray-300 group-hover:translate-x-1 transition-transform" />
              </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-900/50 px-5 py-3 flex items-center justify-between text-xs font-medium text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700">
              <span class="flex items-center gap-1">
                <Icon icon="fluent:tag-24-regular" class="w-3.5 h-3.5" />
                เลขที่: {{ child.student_number }}
              </span>
              <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 rounded-full">
                {{ child.grade_level }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- Selected Child Details -->
      <div v-if="selectedChild" class="animate-in fade-in slide-in-from-bottom-4 duration-500">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
          <!-- Child Header -->
          <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 flex items-center justify-between">
            <div class="flex items-center gap-4">
              <h3 class="text-xl font-bold text-gray-900 dark:text-white">ข้อมูลของ {{ selectedChild.name }}</h3>
            </div>
            <button @click="selectedChild = null" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors">
              <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6 text-gray-400" />
            </button>
          </div>

          <!-- Tab Navigation -->
          <div class="px-6 border-b border-gray-100 dark:border-gray-700 flex gap-8 overflow-x-auto scrollbar-hide">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'py-4 text-sm font-bold whitespace-nowrap border-b-2 transition-all flex items-center gap-2',
                activeTab === tab.id
                  ? 'border-blue-600 text-blue-600 dark:text-blue-400'
                  : 'border-transparent text-gray-400 hover:text-gray-600 dark:hover:text-gray-200'
              ]"
            >
              <Icon :icon="tab.icon" class="w-5 h-5" />
              {{ tab.label }}
            </button>
          </div>

          <!-- Tab Content -->
          <div class="p-6 min-h-[400px]">
            <!-- Grades Tab -->
            <div v-if="activeTab === 'grades'" class="space-y-4">
              <div v-if="loadingGrades" class="flex flex-col items-center justify-center py-20">
                <div class="animate-spin rounded-full h-10 w-10 border-4 border-blue-500 border-t-transparent mb-4"></div>
                <p class="text-gray-500">กำลังโหลดผลการเรียน...</p>
              </div>
              <div v-else-if="grades.length === 0" class="text-center py-20 bg-gray-50 dark:bg-gray-900/30 rounded-2xl">
                <Icon icon="fluent:document-error-24-regular" class="w-16 h-16 mx-auto text-gray-300 mb-2" />
                <p class="text-gray-500">ยังไม่มีข้อมูลผลการเรียนในเทอมนี้</p>
              </div>
              <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                  v-for="grade in grades"
                  :key="grade.course_id"
                  class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm hover:shadow-md transition-shadow"
                >
                  <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                      <Icon icon="fluent:book-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                      <div class="font-bold text-gray-900 dark:text-white">{{ grade.course_name }}</div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">รหัส: {{ grade.course_code }}</div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-2xl font-black" :class="getGradeColor(grade.grade)">
                      {{ grade.grade }}
                    </div>
                    <div v-if="grade.score" class="text-xs font-semibold text-gray-400">
                      {{ grade.score }} คะแนน
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Attendance Tab -->
            <div v-if="activeTab === 'attendance'" class="space-y-8">
              <div v-if="loadingAttendance" class="flex flex-col items-center justify-center py-20">
                <div class="animate-spin rounded-full h-10 w-10 border-4 border-blue-500 border-t-transparent mb-4"></div>
              </div>
              <div v-else>
                <!-- Summary -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                  <div class="p-6 bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-900/30 rounded-2xl">
                    <div class="text-sm font-bold text-green-700 dark:text-green-400 mb-1">มาเรียน</div>
                    <div class="text-3xl font-black text-green-600">{{ attendanceSummary.present }}</div>
                  </div>
                  <div class="p-6 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/30 rounded-2xl">
                    <div class="text-sm font-bold text-red-700 dark:text-red-400 mb-1">ขาดเรียน</div>
                    <div class="text-3xl font-black text-red-600">{{ attendanceSummary.absent }}</div>
                  </div>
                  <div class="p-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-100 dark:border-yellow-900/30 rounded-2xl">
                    <div class="text-sm font-bold text-yellow-700 dark:text-yellow-400 mb-1">มาสาย</div>
                    <div class="text-3xl font-black text-yellow-600">{{ attendanceSummary.late }}</div>
                  </div>
                  <div class="p-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/30 rounded-2xl">
                    <div class="text-sm font-bold text-blue-700 dark:text-blue-400 mb-1">ลากิจ/ป่วย</div>
                    <div class="text-3xl font-black text-blue-600">{{ attendanceSummary.leave }}</div>
                  </div>
                </div>

                <!-- History Table -->
                <div class="mt-8">
                  <h4 class="font-bold text-gray-800 dark:text-white mb-4">ประวัติการเข้าเรียนล่าสุด</h4>
                  <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                      <table class="w-full text-left">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                          <tr>
                            <th class="px-6 py-4">วันที่</th>
                            <th class="px-6 py-4">สถานะ</th>
                            <th class="px-6 py-4 hidden sm:table-cell">เวลา</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                          <tr v-for="att in attendance" :key="att.date" class="hover:bg-gray-50 dark:hover:bg-gray-900/20 transition-colors">
                            <td class="px-6 py-4">
                              <div class="font-bold text-gray-900 dark:text-white">{{ formatDate(att.date) }}</div>
                              <div class="text-xs text-gray-400">{{ att.day_name }}</div>
                            </td>
                            <td class="px-6 py-4">
                              <span :class="['px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1', getAttendanceClass(att.status)]">
                                {{ getAttendanceLabel(att.status) }}
                              </span>
                            </td>
                            <td class="px-6 py-4 hidden sm:table-cell text-sm text-gray-500">
                              <div v-if="att.check_in">{{ att.check_in }} - {{ att.check_out || '??:??' }}</div>
                              <div v-else>-</div>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Fees Tab -->
            <div v-if="activeTab === 'fees'" class="space-y-6">
              <div v-if="loadingFees" class="flex justify-center py-20">
                <div class="animate-spin rounded-full h-10 w-10 border-4 border-blue-500 border-t-transparent"></div>
              </div>
              <div v-else>
                <!-- Fee Summary -->
                <div class="flex flex-col sm:flex-row gap-4 mb-6">
                  <div class="flex-1 p-6 bg-blue-600 text-white rounded-2xl shadow-lg shadow-blue-200 dark:shadow-none">
                    <div class="text-sm font-bold opacity-80 mb-1">ยอดค้างชำระทั้งหมด</div>
                    <div class="text-3xl font-black">฿{{ formatNumber(feeSummary.total_due) }}</div>
                  </div>
                  <div class="flex-1 p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm">
                    <div class="text-sm font-bold text-gray-400 mb-1">ชำระแล้ว</div>
                    <div class="text-3xl font-black text-green-600">฿{{ formatNumber(feeSummary.total_paid) }}</div>
                  </div>
                </div>

                <div class="space-y-4">
                  <div
                    v-for="fee in fees.filter(f => f.student_id === selectedChild.id)"
                    :key="fee.id"
                    class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl flex items-center justify-between"
                  >
                    <div>
                      <div class="font-bold text-gray-900 dark:text-white">{{ fee.description }}</div>
                      <div class="text-xs text-gray-500 mt-1 flex items-center gap-2">
                        <Icon icon="fluent:calendar-clock-24-regular" class="w-3.5 h-3.5" />
                        ครบกำหนด: {{ formatDate(fee.due_date) }}
                      </div>
                    </div>
                    <div class="text-right">
                      <div class="font-black text-gray-900 dark:text-white">฿{{ formatNumber(fee.amount) }}</div>
                      <span :class="[
                        'px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-tight',
                        fee.status === 'paid' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                      ]">
                        {{ fee.status === 'paid' ? 'ชำระแล้ว' : 'ค้างชำระ' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Info Tab -->
            <div v-if="activeTab === 'info'" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                  <h4 class="font-bold text-blue-600 dark:text-blue-400 flex items-center gap-2">
                    <Icon icon="fluent:person-info-24-regular" class="w-5 h-5" />
                    ข้อมูลทั่วไป
                  </h4>
                  <div class="space-y-3 bg-gray-50 dark:bg-gray-900/50 p-6 rounded-2xl">
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                      <span class="text-gray-500">รหัสนักเรียน</span>
                      <span class="font-bold text-gray-900 dark:text-white">{{ childDetail?.student_id }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                      <span class="text-gray-500">ชั้นเรียน</span>
                      <span class="font-bold text-gray-900 dark:text-white">{{ childDetail?.classroom?.name }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                      <span class="text-gray-500">วันเกิด</span>
                      <span class="font-bold text-gray-900 dark:text-white">{{ formatDate(childDetail?.birth_date) }}</span>
                    </div>
                    <div class="flex justify-between py-2">
                      <span class="text-gray-500">อายุ</span>
                      <span class="font-bold text-gray-900 dark:text-white">{{ childDetail?.age }} ปี</span>
                    </div>
                  </div>
                </div>

                <div class="space-y-4">
                  <h4 class="font-bold text-purple-600 dark:text-purple-400 flex items-center gap-2">
                    <Icon icon="fluent:people-24-regular" class="w-5 h-5" />
                    ผู้ปกครองในระบบ
                  </h4>
                  <div class="space-y-3">
                    <div v-for="g in childDetail?.guardians" :key="g.id" class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl flex items-center justify-between">
                      <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-50 dark:bg-purple-900/30 rounded-full flex items-center justify-center font-bold text-purple-600">
                          {{ g.full_name?.charAt(0) }}
                        </div>
                        <div>
                          <div class="font-bold text-gray-900 dark:text-white text-sm">{{ g.full_name }}</div>
                          <div class="text-[10px] text-gray-400">{{ g.relationship }}</div>
                        </div>
                      </div>
                      <div v-if="g.is_primary" class="px-2 py-0.5 bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 rounded-full text-[10px] font-black">หลัก</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Announcements -->
        <section>
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
              <Icon icon="fluent:megaphone-24-regular" class="w-6 h-6 text-orange-500" />
              ประกาศจากโรงเรียน
            </h2>
          </div>

          <div v-if="loadingAnnouncements" class="space-y-4">
            <div v-for="i in 3" :key="i" class="h-24 bg-gray-200 dark:bg-gray-800 animate-pulse rounded-2xl"></div>
          </div>

          <div v-else-if="announcements.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 text-center text-gray-500">
            ไม่มีประกาศใหม่
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="ann in announcements"
              :key="ann.id"
              class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md transition-shadow relative overflow-hidden group"
            >
              <div v-if="ann.is_pinned" class="absolute top-0 right-0 p-1 bg-red-500 text-white rounded-bl-xl shadow-sm">
                <Icon icon="fluent:pin-16-filled" class="w-4 h-4" />
              </div>
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center flex-shrink-0">
                  <Icon icon="fluent:news-24-regular" class="w-6 h-6 text-orange-600" />
                </div>
                <div class="flex-1">
                  <h3 class="font-bold text-gray-900 dark:text-white group-hover:text-blue-600 transition-colors">{{ ann.title }}</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ ann.excerpt }}</p>
                  <div class="flex items-center mt-3 text-[10px] font-black uppercase tracking-widest text-gray-400">
                    <Icon icon="fluent:calendar-16-regular" class="w-3.5 h-3.5 mr-1" />
                    {{ formatDate(ann.published_at) }}
                    <span class="mx-2 text-gray-200">|</span>
                    <Icon icon="fluent:person-16-regular" class="w-3.5 h-3.5 mr-1" />
                    {{ ann.author }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Upcoming Events -->
        <section>
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
              <Icon icon="fluent:calendar-star-24-regular" class="w-6 h-6 text-purple-500" />
              กิจกรรมที่จะมาถึง
            </h2>
          </div>

          <div v-if="events.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 text-center text-gray-500">
            ไม่มีกิจกรรมที่กำลังจะเกิดขึ้น
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="event in events"
              :key="event.id"
              class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 flex items-center gap-5 hover:bg-gray-50 dark:hover:bg-gray-900/40 transition-all"
            >
              <div class="flex-shrink-0 w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex flex-col items-center justify-center border border-purple-200 dark:border-purple-800 shadow-inner">
                <span class="text-xl font-black text-purple-700 dark:text-purple-400 leading-none">{{ formatDay(event.start_date) }}</span>
                <span class="text-[10px] font-black text-purple-600 dark:text-purple-500 uppercase">{{ formatMonth(event.start_date) }}</span>
              </div>
              <div class="flex-1">
                <h3 class="font-bold text-gray-900 dark:text-white">{{ event.title }}</h3>
                <div class="flex items-center gap-3 mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                  <span class="flex items-center gap-1">
                    <Icon icon="fluent:location-16-regular" class="w-3.5 h-3.5" />
                    {{ event.location || 'ไม่ระบุสถานที่' }}
                  </span>
                  <span v-if="event.is_holiday" class="px-2 py-0.5 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-full text-[10px] font-black uppercase">วันหยุด</span>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main',
  middleware: ['auth']
})

const route = useRoute()
const api = useApi()

const academyName = computed(() => route.params.name as string)
const academyId = ref<number | null>(null)
const academy = ref<any>(null)

// State
const children = ref<any[]>([])
const selectedChild = ref<any>(null)
const childDetail = ref<any>(null)
const grades = ref<any[]>([])
const attendance = ref<any[]>([])
const attendanceSummary = ref({ present: 0, absent: 0, late: 0, leave: 0 })
const fees = ref<any[]>([])
const feeSummary = ref({ total_due: 0, total_paid: 0, overdue_count: 0 })
const announcements = ref<any[]>([])
const events = ref<any[]>([])

// Loading states
const loadingChildren = ref(true)
const loadingGrades = ref(false)
const loadingAttendance = ref(false)
const loadingFees = ref(false)
const loadingAnnouncements = ref(false)

const activeTab = ref('grades')
const tabs = [
  { id: 'grades', label: 'ผลการเรียน', icon: 'fluent:hat-graduation-24-regular' },
  { id: 'attendance', label: 'การเข้าเรียน', icon: 'fluent:calendar-clock-24-regular' },
  { id: 'fees', label: 'การเงิน', icon: 'fluent:money-hand-24-regular' },
  { id: 'info', label: 'ข้อมูลส่วนตัว', icon: 'fluent:person-info-24-regular' },
]

// Load academy info
const loadAcademyInfo = async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
    }
  } catch (error) {
    console.error('Error loading academy:', error)
  }
}

// Load children
const loadChildren = async () => {
  if (!academyId.value) return
  loadingChildren.value = true
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/parent/children`)
    if (response.success) {
      children.value = response.children || []
      // Auto-select first child if available
      if (children.value.length > 0) {
        selectChild(children.value[0])
      }
    }
  } catch (error) {
    console.error('Error loading children:', error)
  } finally {
    loadingChildren.value = false
  }
}

// Select child
const selectChild = async (child: any) => {
  selectedChild.value = child
  loadChildDetail(child.id)
  loadGrades(child.id)
  loadAttendance(child.id)
}

// Load child detail
const loadChildDetail = async (studentId: number) => {
  if (!academyId.value) return
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/parent/children/${studentId}`)
    if (response.success) {
      childDetail.value = response.student
    }
  } catch (error) {
    console.error('Error loading child detail:', error)
  }
}

// Load grades
const loadGrades = async (studentId: number) => {
  if (!academyId.value) return
  loadingGrades.value = true
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/parent/children/${studentId}/grades`)
    if (response.success) {
      grades.value = response.grades || []
    }
  } catch (error) {
    console.error('Error loading grades:', error)
  } finally {
    loadingGrades.value = false
  }
}

// Load attendance
const loadAttendance = async (studentId: number) => {
  if (!academyId.value) return
  loadingAttendance.value = true
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/parent/children/${studentId}/attendance`)
    if (response.success) {
      attendance.value = response.attendance || []
      attendanceSummary.value = response.summary || { present: 0, absent: 0, late: 0, leave: 0 }
    }
  } catch (error) {
    console.error('Error loading attendance:', error)
  } finally {
    loadingAttendance.value = false
  }
}

// Load fees
const loadFees = async () => {
  if (!academyId.value) return
  loadingFees.value = true
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/parent/fees`)
    if (response.success) {
      fees.value = response.fees || []
      feeSummary.value = response.summary || { total_due: 0, total_paid: 0, overdue_count: 0 }
    }
  } catch (error) {
    console.error('Error loading fees:', error)
  } finally {
    loadingFees.value = false
  }
}

// Load announcements
const loadAnnouncements = async () => {
  if (!academyId.value) return
  loadingAnnouncements.value = true
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/parent/announcements`)
    if (response.success) {
      announcements.value = response.announcements || []
    }
  } catch (error) {
    console.error('Error loading announcements:', error)
  } finally {
    loadingAnnouncements.value = false
  }
}

// Load events
const loadEvents = async () => {
  if (!academyId.value) return
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/parent/events`)
    if (response.success) {
      events.value = response.events || []
    }
  } catch (error) {
    console.error('Error loading events:', error)
  }
}

// Helper functions
const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const formatDay = (date: string) => {
  if (!date) return '-'
  return new Date(date).getDate()
}

const formatMonth = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', { month: 'short' })
}

const formatNumber = (num: number) => {
  return new Intl.NumberFormat('th-TH').format(num || 0)
}

const getGradeColor = (grade: string) => {
  const g = grade?.toString().toUpperCase()
  if (['A', '4', '4.0'].includes(g)) return 'text-green-600'
  if (['B+', 'B', '3.5', '3'].includes(g)) return 'text-blue-600'
  if (['C+', 'C', '2.5', '2'].includes(g)) return 'text-yellow-600'
  if (['D+', 'D', '1.5', '1'].includes(g)) return 'text-orange-600'
  if (['F', '0'].includes(g)) return 'text-red-600'
  return 'text-gray-400'
}

const getAttendanceClass = (status: string) => {
  const classes: Record<string, string> = {
    present: 'text-green-700 bg-green-100 dark:bg-green-900/30 dark:text-green-400',
    absent: 'text-red-700 bg-red-100 dark:bg-red-900/30 dark:text-red-400',
    late: 'text-yellow-700 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400',
    sick: 'text-blue-700 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400',
    leave: 'text-purple-700 bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400',
  }
  return classes[status] || 'text-gray-600 bg-gray-100'
}

const getAttendanceLabel = (status: string) => {
  const labels: Record<string, string> = {
    present: 'มาเรียน',
    absent: 'ขาดเรียน',
    late: 'มาสาย',
    sick: 'ลาป่วย',
    leave: 'ลากิจ',
  }
  return labels[status] || status
}

onMounted(async () => {
  await loadAcademyInfo()
  if (academyId.value) {
    loadChildren()
    loadAnnouncements()
    loadEvents()
    loadFees()
  }
})
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
