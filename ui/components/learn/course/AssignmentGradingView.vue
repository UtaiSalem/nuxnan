<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  assignment: any
  courseId: number | string
}>()

const api = useApi()
const swal = useSweetAlert()
const { getAvatarUrl } = useAvatar()

// State
const allAnswers = ref<any[]>([])
const groups = ref<any[]>([])
const selectedGroup = ref<number | null>(null)
const courseMemberId = ref<number | null>(null)
const isFetchingAnswers = ref(false)
const isLoadingGroups = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const hasLoadedAnswers = ref(false)
const totalAnswers = ref(0)
const showGradedSection = ref(false)

const fetchGroups = async () => {
    isLoadingGroups.value = true
    try {
        const res = await api.get(`/api/courses/${props.courseId}/groups`)
        groups.value = res.groups || []
        
        // Set default selected group based on last_accessed_group_tab from courseMemberOfAuth
        if (res.courseMemberOfAuth) {
            // Store the member ID for later API calls
            courseMemberId.value = res.courseMemberOfAuth.id
            
            if (res.courseMemberOfAuth.last_accessed_group_tab) {
                const lastAccessedGroupId = res.courseMemberOfAuth.last_accessed_group_tab
                // Verify this group exists in the list
                if (groups.value.some((g: any) => g.id === lastAccessedGroupId)) {
                    selectedGroup.value = lastAccessedGroupId
                } else if (groups.value.length > 0) {
                    selectedGroup.value = groups.value[0].id
                }
            } else if (groups.value.length > 0) {
                selectedGroup.value = groups.value[0].id
            }
        } else if (groups.value.length > 0) {
            // Default to first group if no courseMemberOfAuth
            selectedGroup.value = groups.value[0].id
        }
    } catch (e) {
        console.error(e)
        groups.value = []
    } finally {
        isLoadingGroups.value = false
    }
}

const fetchAllAnswers = async (page = 1, reset = false) => {
  isFetchingAnswers.value = true
  try {
    const response = await api.get(`/api/assignments/${props.assignment.id}/answers`, {
        params: {
            page: page,
            group_id: selectedGroup.value || 'all'
        }
    })
    
    // Map response.data - Admin Grading View expansion logic:
    // - Ungraded (points = null) -> expanded (needs grading)
    // - Graded but failed (points < passing_score) -> expanded (may need review)
    // - Graded and passed (points >= passing_score) -> collapsed (done)
    const passingScore = props.assignment.passing_score || 0
    const newAnswers = (response.data || []).map((a: any) => {
      const isGraded = a.points !== null && a.points !== undefined
      const isPassed = isGraded && a.points >= passingScore
      return {
        ...a,
        points: a.points, 
        originalPoints: a.points,
        isUpdating: false,
        isExpanded: !isPassed // Expand if NOT passed (ungraded or failed), collapse if passed
      }
    })

    if (reset) {
        allAnswers.value = newAnswers
    } else {
        allAnswers.value = [...allAnswers.value, ...newAnswers]
    }
    
    currentPage.value = response.meta.current_page
    lastPage.value = response.meta.last_page
    totalAnswers.value = response.meta.total
    hasLoadedAnswers.value = true
    
  } catch (error) {
    console.error('Failed to fetch answers:', error)
  } finally {
    isFetchingAnswers.value = false
  }
}

// Computed: Separate ungraded and graded based on points
// No points = ungraded, Has points = graded
const ungradedAnswers = computed(() => allAnswers.value.filter(a => a.points === null || a.points === undefined))
const gradedAnswers = computed(() => allAnswers.value.filter(a => a.points !== null && a.points !== undefined))

// Watch group - fetch answers and save selection to database
watch(selectedGroup, async (newGroupId, oldGroupId) => {
    if (hasLoadedAnswers.value) {
        fetchAllAnswers(1, true)
    }
    
    // Save selected group to database when user clicks on a group tab
    if (newGroupId && oldGroupId !== undefined && courseMemberId.value) {
        try {
            await api.post(`/api/courses/${props.courseId}/members/${courseMemberId.value}/set-active-group-tab`, {
                group_tab: newGroupId
            })
        } catch (e) {
            console.error('Failed to save group selection:', e)
        }
    }
})

// Grading Logic
const updateGrade = async (answer: any) => {
  try {
    answer.isUpdating = true
    await api.post(`/api/assignments/${props.assignment.id}/answers/${answer.id}/set-points`, {
      points: answer.points,
      course_id: props.courseId
    })
    
    answer.originalPoints = answer.points
    answer.status = 'graded' // Mark as graded in local state
    answer.isUpdating = false
    
    // Only collapse if PASSED (points >= passing_score), keep expanded if failed
    const passingScore = props.assignment.passing_score || 0
    answer.isExpanded = answer.points < passingScore // Failed = stay expanded, Passed = collapse
    
    swal.toast('บันทึกคะแนนเรียบร้อย', 'success')
  } catch (error) {
    console.error('Grading error:', error)
    swal.toast('บันทึกคะแนนไม่สำเร็จ', 'error')
    answer.isUpdating = false
  }
}

const cancelGrade = (answer: any) => {
  answer.points = answer.originalPoints
}

// Helpers
const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('th-TH', {
    dateStyle: 'medium',
    timeStyle: 'short'
  })
}

const isLate = (answer: any) => {
    if (!props.assignment.due_date) return false
    return new Date(answer.created_at) > new Date(props.assignment.due_date)
}

const userAvatar = (user: any) => getAvatarUrl(user)

const getAnswerCardClass = (answer: any) => {
   // No points = ungraded (white background)
   if (answer.points === null || answer.points === undefined) {
     return 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700'
   }
   // Has points = graded (green/red based on passing score)
   const passing = props.assignment.passing_score ?? 0
   return answer.points >= passing 
      ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800'
      : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'
}

onMounted(() => {
    fetchGroups()
    fetchAllAnswers(1, true)
    window.addEventListener('scroll', checkScroll)
})

onUnmounted(() => {
    window.removeEventListener('scroll', checkScroll)
})

// Back to Top
const showBackToTop = ref(false)

const checkScroll = () => {
    showBackToTop.value = window.scrollY > 300
}

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>

<template>
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
               <Icon icon="fluent:people-community-24-regular" class="w-5 h-5" />
               การส่งงาน ({{ hasLoadedAnswers ? totalAnswers : (props.assignment.answer_count || 0) }})
            </h2>
        </div>

        <!-- Group Tabs -->
        <div v-if="groups && groups.length > 0" class="flex flex-wrap gap-2 mb-4 border-b border-gray-200 dark:border-gray-700 pb-3">
            <button 
                @click="selectedGroup = null"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
                :class="selectedGroup === null 
                    ? 'bg-indigo-600 text-white shadow-md' 
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
            >
                ทั้งหมด
            </button>
            <button 
                v-for="group in groups" 
                :key="group.id"
                @click="selectedGroup = group.id"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
                :class="selectedGroup === group.id 
                    ? 'bg-indigo-600 text-white shadow-md' 
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
            >
                {{ group.name }}
            </button>
        </div>

        <!-- Loading -->
        <div v-if="isFetchingAnswers && allAnswers.length === 0" class="flex justify-center py-8">
             <Icon icon="eos-icons:loading" class="w-8 h-8 text-orange-500" />
        </div>

        <!-- Empty State -->
        <div v-else-if="allAnswers.length === 0" class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 text-center text-gray-500 border border-dashed border-gray-300 dark:border-gray-700 text-sm">
            {{ selectedGroup ? 'ไม่มีงานที่ส่งในกลุ่มนี้' : 'ยังไม่มีนักเรียนส่งงาน' }}
        </div>

        <div v-else class="space-y-3">
            <!-- All Answers List (Unified) -->
            <div class="grid gap-3">
                <div v-for="answer in allAnswers" :key="answer.id" 
                     class="rounded-xl p-4 border shadow-sm transition-shadow hover:shadow-md"
                     :class="getAnswerCardClass(answer)"
                >
                    <div class="flex items-start gap-3">
                       <img :src="userAvatar(answer.student)" class="w-10 h-10 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-700" />
                       <div class="flex-1 min-w-0">
                          <div class="flex justify-between">
                             <div>
                                <h3 class="font-bold text-gray-900 dark:text-white text-sm">{{ answer.member_name || answer.student?.username || 'Unknown Student' }}</h3>
                                <p class="text-xs flex items-center gap-1" :class="isLate(answer) ? 'text-red-500 font-bold' : 'text-green-600'">
                                   {{ formatDate(answer.created_at) }}
                                   <span v-if="isLate(answer)">(Late)</span>
                                </p>
                             </div>
                             <div class="text-right flex items-center gap-2">
                                 <button 
                                   @click="answer.isExpanded = !answer.isExpanded"
                                   class="p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-400"
                                 >
                                    <Icon :icon="answer.isExpanded ? 'fluent:chevron-up-24-regular' : 'fluent:chevron-down-24-regular'" class="w-5 h-5" />
                                 </button>

                                 <div class="text-xl font-bold" :class="answer.points !== null ? (answer.points >= (assignment.passing_score || 0) ? 'text-green-600' : 'text-red-500') : 'text-gray-300'">
                                    {{ answer.points ?? '-' }} <span class="text-xs font-normal text-gray-400">/ {{ assignment.points }}</span>
                                 </div>
                             </div>
                          </div>

                          <div v-show="answer.isExpanded">
                               <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap border border-gray-100 dark:border-gray-800">
                                  {{ answer.content }}
                                  <div v-if="answer.images?.length" class="mt-3 flex flex-wrap gap-2">
                                     <img v-for="img in answer.images" :key="img.id" :src="img.full_url || img.image_url" class="w-12 h-12 object-cover rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer" />
                                  </div>
                               </div>
                          </div>

                          <!-- Grading Controls -->
                          <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center gap-3">
                              <input 
                                  type="number" 
                                  v-model.number="answer.points"
                                  :min="0" 
                                  :max="assignment.points"
                                  class="w-16 px-2 py-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-center font-bold text-sm focus:ring-2 focus:ring-orange-500 outline-none"
                              />
                              <input 
                                  type="range" 
                                  v-model.number="answer.points"
                                  :min="0" 
                                  :max="assignment.points"
                                  class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-orange-500"
                              />
                              <button 
                                  @click="updateGrade(answer)"
                                  :disabled="answer.isUpdating || (answer.points === null && answer.originalPoints === null) || answer.points === answer.originalPoints"
                                  class="px-3 py-1 text-xs font-bold text-white bg-orange-500 rounded-lg hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                              >
                                  {{ answer.isUpdating ? '...' : 'บันทึก' }}
                              </button>
                           </div>
                       </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="currentPage < lastPage" class="flex justify-center mt-4">
                 <button 
                     @click="fetchAllAnswers(currentPage + 1)" 
                     :disabled="isFetchingAnswers"
                     class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 font-medium disabled:opacity-50 flex items-center gap-2"
                 >
                     {{ isFetchingAnswers ? 'กำลังโหลด...' : 'โหลดเพิ่ม' }}
                 </button>
            </div>
        </div>

        <!-- Back to Top Button -->
        <transition 
           enter-active-class="transition ease-out duration-300"
           enter-from-class="opacity-0 translate-y-10"
           enter-to-class="opacity-100 translate-y-0"
           leave-active-class="transition ease-in duration-300"
           leave-from-class="opacity-100 translate-y-0"
           leave-to-class="opacity-0 translate-y-10"
        >
             <button 
               v-show="showBackToTop"
               @click="scrollToTop"
               class="fixed bottom-6 right-6 p-3 bg-orange-500 text-white rounded-full shadow-lg hover:bg-orange-600 transition-all z-50 animate-bounce-slow"
               title="Back to Top"
             >
               <Icon icon="fluent:arrow-up-24-filled" class="w-6 h-6" />
             </button>
        </transition>
    </div>
</template>

