<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useCourseGroupStore } from '~/stores/courseGroup'
import { useCourseMemberStore } from '~/stores/courseMember'

// Inject dependencies
const course = inject<Ref<any>>('course')
// const courseMember = inject<Ref<any>>('courseMember')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')
const api = useApi()
const route = useRoute()
const config = useRuntimeConfig()
const courseGroupStore = useCourseGroupStore()
const courseMemberStore = useCourseMemberStore()

// State
const searchQuery = ref('')
const activeGroupTab = ref(0) // 0 = all, -1 = ungrouped, 1+ = group index
const viewMode = ref<'card' | 'table' | 'list'>('card') // Default to card view
const isSavingGroupTab = ref(false)
const sortBy = ref<'number' | 'score'>('number') // 'number' | 'score'
const selectedMemberIds = ref<number[]>([])
const assigningGroupForMember = ref<number | null>(null) // member id currently being assigned

// Mobile detection for view mode
const isMobile = ref(false)
const updateMobileDetection = () => {
  isMobile.value = window.innerWidth < 768 // md breakpoint
}

// Auto-switch to card view on mobile
const effectiveViewMode = computed(() => {
  return isMobile.value ? 'card' : viewMode.value
})

// Persist view mode preference (only for desktop)
const savedViewMode = useCookie<'card' | 'table' | 'list'>('course-members-view-mode')
onMounted(() => {
  updateMobileDetection()
  if (savedViewMode.value && !isMobile.value) {
    viewMode.value = savedViewMode.value
  }
})
watch(viewMode, (val) => {
  if (!isMobile.value) {
    savedViewMode.value = val
  }
})

// Watch for mobile changes
watch(isMobile, (mobile) => {
  if (mobile && viewMode.value !== 'card') {
    // Force card view on mobile
    viewMode.value = 'card'
  } else if (!mobile && savedViewMode.value) {
    // Restore saved preference on desktop
    viewMode.value = savedViewMode.value
  }
})

// Window resize listener
onMounted(() => {
  window.addEventListener('resize', updateMobileDetection)
})
onUnmounted(() => {
  window.removeEventListener('resize', updateMobileDetection)
})

// Get initial group tab based on last_accessed_group_tab
const getInitialGroupTab = () => {
    if (!isCourseAdmin.value) {
        // For students, find their group
        const userGroupId = courseMemberStore.member?.group_id
        if (userGroupId) {
            const index = courseGroupStore.groups.findIndex(g => g.id === userGroupId)
            return index >= 0 ? index + 1 : 0 // +1 because 0 is "all"
        }
        return 0
    }
    
    const lastAccessedGroupId = courseMemberStore.member?.last_accessed_group_tab
    if (!lastAccessedGroupId) return 0
    
    const index = courseGroupStore.groups.findIndex(g => g.id === lastAccessedGroupId)
    return index >= 0 ? index + 1 : 0 // +1 because 0 is "all"
}

// Set active group tab with API save
async function setActiveGroupTab(tabIndex: number) {
    activeGroupTab.value = tabIndex

    // Only save if admin and not 'all' and course exists
    if (isCourseAdmin.value && tabIndex > 0 && course?.value?.id && !isSavingGroupTab.value) {
        isSavingGroupTab.value = true
        try {
            const groupId = courseGroupStore.groups[tabIndex - 1]?.id
            if (groupId) {
                await api.patch(`/api/courses/${course.value.id}/members/update-last-access-group`, {
                    last_accessed_group_tab: Number(groupId)
                })
                // Update local store
                if (courseMemberStore.member) {
                    courseMemberStore.member.last_accessed_group_tab = Number(groupId)
                }
            }
        } catch (error) {
            console.error('Error saving last accessed group tab:', error)
            // Show error notification with SweetAlert
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'ไม่สามารถบันทึกกลุ่มเริ่มต้นได้',
                text: 'กรุณาลองใหม่อีกครั้ง',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            })
        } finally {
            isSavingGroupTab.value = false
        }
    }
}

import MemberCard from '~/components/learn/course/MemberCard.vue'
import MemberListView from '~/components/learn/course/MemberListView.vue'
import TopPerformers from '~/components/learn/course/TopPerformers.vue'

// Computed Members
const members = computed(() => {
    let list = []
    
    if (activeGroupTab.value === 0) {
        // "All" tab - aggregate all members from all groups + ungrouped
        const allMembers = [
            ...courseGroupStore.groups.flatMap(g => g.members || []),
            ...(courseGroupStore.ungroupedMembers || [])
        ]
        // Deduplicate by ID
        const seen = new Set()
        list = allMembers.filter(m => {
            const duplicate = seen.has(m.id)
            seen.add(m.id)
            return !duplicate && m.course_member_status === 1 // Only approved
        })
    } else if (activeGroupTab.value === -1) {
        // "ไม่มีกลุ่ม" tab - ungrouped members (exclude admins role=4)
        list = (courseGroupStore.ungroupedMembers || []).filter(m => m.role !== 4 && m.course_member_status === 1)
    } else if (activeGroupTab.value === -2) {
        // "รออนุมัติ" tab - all members with course_member_status === 0
        const allMembers = [
            ...courseGroupStore.groups.flatMap(g => g.members || []),
            ...(courseGroupStore.ungroupedMembers || [])
        ]
        const seen = new Set()
        list = allMembers.filter(m => {
            const duplicate = seen.has(m.id)
            seen.add(m.id)
            return !duplicate && m.course_member_status === 0
        })
    } else {
        // Specific group tab
        const group = courseGroupStore.groups[activeGroupTab.value - 1]
        list = (group?.members || []).filter(m => m.course_member_status === 1)
    }

    // Restrict for students
    if (!isCourseAdmin.value && courseMemberStore.member?.group_id) {
        const userGroupId = Number(courseMemberStore.member.group_id)
        list = list.filter(m => m.group_id === userGroupId)
    }

    // Client-side search
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        list = list.filter(m => 
            (m.member_name || m.user?.name || m.student?.name || m.name || '').toLowerCase().includes(query) ||
            (m.user?.email || '').toLowerCase().includes(query) ||
            (m.student_id || '').toLowerCase().includes(query)
        )
    }

    // Sort by order_number or score
    list.sort((a, b) => {
        if (sortBy.value === 'score') {
            const scoreA = Number(a.achieved_score || 0) + Number(a.bonus_points || 0)
            const scoreB = Number(b.achieved_score || 0) + Number(b.bonus_points || 0)
            if (scoreA !== scoreB) {
                return scoreB - scoreA // Descending score
            }
        }
        
        // Default / Fallback to order_number
        const orderA = a.order_number != null ? Number(a.order_number) : Infinity
        const orderB = b.order_number != null ? Number(b.order_number) : Infinity
        return orderA - orderB
    })

    return list
})

const isLoading = computed(() => courseGroupStore.isLoading)
const totalmembers = computed(() => members.value.length)

// Lifecycle
onMounted(async () => {
    if (course?.value?.id) {
        await courseGroupStore.fetchGroups(course.value.id)
    }
    
    // Set initial group tab after groups are loaded
    activeGroupTab.value = getInitialGroupTab()
})

// Watch for course changes
watch(() => course?.value?.id, async (newId) => {
    if (newId) {
        await courseGroupStore.fetchGroups(newId)
        activeGroupTab.value = getInitialGroupTab()
    }
})

import Swal from 'sweetalert2'

// ── Eligibility Unlock ───────────────────────────────────────────────
const toast = useToast()
const showUnlockModal = ref(false)
const unlockTargetMember = ref<any>(null)
const unlockReason = ref('')
const isUnlocking = ref(false)
const isBulkUnlocking = ref(false)

const handleUnlockMember = (member: any) => {
  unlockTargetMember.value = member
  unlockReason.value = ''
  showUnlockModal.value = true
}

const submitUnlock = async () => {
  if (!unlockTargetMember.value || !unlockReason.value.trim()) return
  isUnlocking.value = true
  try {
    const res: any = await api.post(
      `/api/courses/${course?.value?.id}/eligibility/members/${unlockTargetMember.value.id}/unlock`,
      { reason: unlockReason.value }
    )
    if (res.success !== false) {
      toast.success('ปลดล็อคสิทธิ์สอบแล้ว')
      showUnlockModal.value = false
      await courseGroupStore.fetchGroups(course!.value.id, true)
    }
  } catch (err) {
    console.error('Failed to unlock member:', err)
    toast.error('ไม่สามารถปลดล็อคได้')
  } finally {
    isUnlocking.value = false
  }
}

// ── Group Bulk Unlock ────────────────────────────────────────────────
const showBulkUnlockConfirm = ref(false)
const bulkUnlockGroupReason = ref('')
const bulkUnlockGroupId = computed<number | null>(() => {
  if (activeGroupTab.value > 0) {
    return courseGroupStore.groups[activeGroupTab.value - 1]?.id ?? null
  }
  return null
})

const handleBulkUnlockGroup = () => {
  bulkUnlockGroupReason.value = ''
  showBulkUnlockConfirm.value = true
}

const submitBulkUnlockGroup = async () => {
  if (!bulkUnlockGroupId.value || !bulkUnlockGroupReason.value.trim()) return
  isBulkUnlocking.value = true
  try {
    const res: any = await api.post(
      `/api/courses/${course?.value?.id}/eligibility/bulk-unlock`,
      { group_id: bulkUnlockGroupId.value, only_ineligible: true, reason: bulkUnlockGroupReason.value }
    )

    if (res.success === false) {
      toast.error(res.message || 'ไม่สามารถปลดล็อคแบบกลุ่มได้')
      return
    }

    // อ่านผลจริงจาก API — บางคนอาจถูกข้ามหรือปลดล็อคไม่สำเร็จ
    const unlocked = Number(res?.data?.success ?? 0)
    const skipped = Number(res?.data?.skipped ?? 0)
    const failed = res?.data?.errors?.length ?? 0

    if (unlocked > 0) {
      const extra = [
        skipped ? `ข้าม ${skipped} คน` : '',
        failed ? `ผิดพลาด ${failed} คน` : '',
      ].filter(Boolean).join(', ')
      toast.success(`ปลดล็อคสิทธิ์สอบ ${unlocked} คนแล้ว${extra ? ` (${extra})` : ''}`)
    } else {
      toast.warning(res.message || 'ไม่มีสมาชิกที่หมดสิทธิ์สอบในกลุ่มนี้')
    }

    showBulkUnlockConfirm.value = false
    await courseGroupStore.fetchGroups(course!.value.id, true)
  } catch (err) {
    console.error('Failed to bulk unlock group:', err)
    toast.error('ไม่สามารถปลดล็อคแบบกลุ่มได้')
  } finally {
    isBulkUnlocking.value = false
  }
}
// ─────────────────────────────────────────────────────────────────────

const handleRequestUnmember = async ({ memberId, memberName }: { memberId: number, memberName: string }) => {
    // 1. Get Preview
    try {
        const previewRes = await api.get(`/api/courses/${course.value.id}/members/${memberId}/removal-preview`)
        const preview = previewRes.preview
        
        let html = `คุณต้องการลบ "${memberName}" ออกจากรายวิชานี้ใช่หรือไม่?<br><br>`
        
        // Data Summary
        html += `<div class="text-left text-sm bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-100 dark:border-gray-800 space-y-1 mb-3">`
        html += `<div class="font-bold mb-1 text-gray-700 dark:text-gray-300 underline">ข้อมูลที่จะถูกลบอย่างถาวร:</div>`
        html += `<div>• ผลการทดสอบ: ${preview.data_summary.quiz_results} รายการ</div>`
        html += `<div>• คำตอบแบบฝึกหัด: ${preview.data_summary.question_answers} รายการ</div>`
        html += `<div>• งานที่ส่ง: ${preview.data_summary.assignment_answers} รายการ</div>`
        html += `</div>`

        // Refund info
        if (preview.payment.is_paid) {
            html += `<div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-sm text-blue-700 dark:text-blue-400 font-medium mb-3">`
            html += `<strong>การคืนเงิน:</strong> รายวิชานี้มีการชำระเงิน ระบบจะคืนเงินจำนวน ${preview.payment.amount} บาท เข้ากระเป๋าของผู้เรียนโดยอัตโนมัติ`
            html += `</div>`
        }

        html += `<div class="text-xs text-red-500 font-bold italic">คำเตือน: การกระทำนี้ไม่สามารถย้อนคืนได้</div>`

        const result = await Swal.fire({
            title: 'ยืนยันการลบสมาชิก?',
            html: html,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        })

        if (result.isConfirmed) {
            try {
                const res = await api.post(`/api/courses/${course.value.id}/members/${memberId}/remove`, {
                    mode: 'admin_remove',
                    reason: 'Removed by admin'
                })
                
                let successMsg = 'สมาชิกถูกลบออกจากรายวิชาเรียบร้อยแล้ว'
                if (res.refunded) {
                    successMsg += ` และคืนเงินจำนวน ${res.refund_amount} บาทสำเร็จ`
                }

                Swal.fire(
                    'ลบสำเร็จ!',
                    successMsg,
                    'success'
                )
                // Refresh groups to update member lists
                await courseGroupStore.fetchGroups(course.value.id, true)
            } catch (error: any) {
                console.error('Failed to remove member:', error)
                Swal.fire(
                    'เกิดข้อผิดพลาด!',
                    error?.response?.data?.message || 'ไม่สามารถลบสมาชิกได้.',
                    'error'
                )
            }
        }
    } catch (error) {
        console.error('Failed to get removal preview:', error)
        // Fallback to simple removal if preview fails
        const result = await Swal.fire({
            title: 'ยืนยันการลบสมาชิก?',
            text: `คุณต้องการลบ "${memberName}" ออกจากรายวิชานี้ใช่หรือไม่? (ไม่สามารถดูสรุปผลกระทบได้)`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        })
        if (result.isConfirmed) {
            try {
                await api.post(`/api/courses/${course.value.id}/members/${memberId}/remove`, { mode: 'admin_remove' })
                await courseGroupStore.fetchGroups(course.value.id, true)
                Swal.fire('ลบสำเร็จ!', 'สมาชิกถูกลบออกจากรายวิชาแล้ว', 'success')
            } catch (err) {
                Swal.fire('ผิดพลาด', 'ไม่สามารถลบสมาชิกได้', 'error')
            }
        }
    }
}

// Handler for approving request
const handleApproveRequest = async (member: any) => {
    const result = await Swal.fire({
        title: 'อนุมัติการสมัคร?',
        text: `คุณต้องการอนุมัติ "${member.name || member.member_name}" เข้าสู่รายวิชาใช่หรือไม่?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'อนุมัติ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#10b981'
    })

    if (result.isConfirmed) {
        try {
            await api.post(`/api/courses/${course.value.id}/members/${member.id}/approve`)
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'อนุมัติเรียบร้อยแล้ว',
                showConfirmButton: false,
                timer: 2000
            })
            await courseGroupStore.fetchGroups(course.value.id, true)
        } catch (error) {
            console.error('Failed to approve request:', error)
            Swal.fire('ผิดพลาด', 'ไม่สามารถอนุมัติได้', 'error')
        }
    }
}

// Handler for rejecting request
const handleRejectRequest = async (member: any) => {
    const result = await Swal.fire({
        title: 'ปฏิเสธการสมัคร?',
        text: `คุณต้องการปฏิเสธ "${member.name || member.member_name}" หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ปฏิเสธ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#ef4444'
    })

    if (result.isConfirmed) {
        try {
            await api.post(`/api/courses/${course.value.id}/members/${member.id}/reject`)
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'ปฏิเสธเรียบร้อยแล้ว',
                showConfirmButton: false,
                timer: 2000
            })
            await courseGroupStore.fetchGroups(course.value.id, true)
        } catch (error) {
            console.error('Failed to reject request:', error)
            Swal.fire('ผิดพลาด', 'ไม่สามารถปฏิเสธได้', 'error')
        }
    }
}

// Handler for viewing member details
const handleViewMember = (member: any) => {
    // Navigate to member profile or show modal
    navigateTo(`/Learn/Courses/${course?.value?.id}/members/${member.id}`)
}

// Handler for editing member
const handleEditMember = (member: any) => {
    // Navigate to member edit page or show modal
    navigateTo(`/Learn/Courses/${course?.value?.id}/members/${member.id}/edit`)
}

// Total members count including ungrouped (exclude admins from ungrouped count)
const totalAllMembers = computed(() => {
    const allMembers = [
        ...courseGroupStore.groups.flatMap(g => g.members || []),
        ...(courseGroupStore.ungroupedMembers || [])
    ]
    const seen = new Set()
    return allMembers.filter(m => {
        const duplicate = seen.has(m.id)
        seen.add(m.id)
        return !duplicate && m.course_member_status === 1 && m.role !== 4
    }).length
})

// Ungrouped non-admin count for tab display
const ungroupedCount = computed(() => {
    return (courseGroupStore.ungroupedMembers || []).filter((m: any) => m.role !== 4 && m.course_member_status === 1).length
})

// Pending count for tab display
const pendingCount = computed(() => {
    const allMembers = [
        ...courseGroupStore.groups.flatMap(g => g.members || []),
        ...(courseGroupStore.ungroupedMembers || [])
    ]
    const seen = new Set()
    return allMembers.filter(m => {
        const duplicate = seen.has(m.id)
        seen.add(m.id)
        return !duplicate && m.course_member_status === 0
    }).length
})

// Assign group to ungrouped member
async function assignGroupToMember(memberId: number, groupId: number) {
    if (!course?.value?.id || !groupId) return
    
    assigningGroupForMember.value = memberId
    try {
        await api.patch(`/api/courses/${course.value.id}/members/${memberId}/update`, {
            group_id: groupId
        })
        
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'ย้ายเข้ากลุ่มสำเร็จ',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        })

        // Refresh groups to update member lists (don't let refresh failure override success)
        try {
            await courseGroupStore.fetchGroups(course.value.id, true)
        } catch (refreshErr) {
            console.warn('Group list refresh failed after successful assignment:', refreshErr)
        }
    } catch (error) {
        console.error('Failed to assign group:', error)
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: 'ไม่สามารถย้ายกลุ่มได้',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        })
    } finally {
        assigningGroupForMember.value = null
    }
}
</script>

<template>
    <div class="container mx-auto px-3 sm:px-4 py-4 sm:py-8 max-w-7xl pb-24 lg:pb-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <Icon icon="ph:users-three-duotone" class="w-8 h-8 text-blue-500" />
                    สมาชิกในรายวิชา
                    <span v-if="!isLoading" class="text-sm font-normal text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full">
                        {{ activeGroupTab === -2 ? pendingCount : totalmembers }} คน
                    </span>
                </h1>
                <p class="mt-1 text-gray-500 dark:text-gray-400 text-sm">
                    {{ activeGroupTab === -2 ? 'รายการผู้ที่ต้องการเข้าร่วมรายวิชา' : 'รายชื่อนักเรียนและผู้สอนทั้งหมดในรายวิชานี้' }}
                </p>
            </div>

            <!-- Search and Sort -->
            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                <!-- View Mode Toggle -->
                <div v-if="!isMobile" class="flex bg-gray-100 dark:bg-gray-800 p-1 rounded-lg">
                    <button 
                        @click="viewMode = 'card'"
                        class="p-2 rounded-md transition-all"
                        :class="viewMode === 'card' ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                        title="มุมมองการ์ด"
                    >
                        <Icon icon="fluent:grid-24-regular" class="w-5 h-5" />
                    </button>
                    <button 
                        @click="viewMode = 'table'"
                        class="p-2 rounded-md transition-all"
                        :class="viewMode === 'table' ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                        title="มุมมองตาราง"
                    >
                        <Icon icon="fluent:table-24-regular" class="w-5 h-5" />
                    </button>
                    <button 
                        @click="viewMode = 'list'"
                        class="p-2 rounded-md transition-all"
                        :class="viewMode === 'list' ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                        title="มุมมองรายการ"
                    >
                        <Icon icon="fluent:list-24-regular" class="w-5 h-5" />
                    </button>
                </div>

                <!-- Sort Tabs -->
                <div v-if="activeGroupTab !== -2" class="flex bg-gray-100 dark:bg-gray-800 p-1 rounded-lg">
                    <button 
                        @click="sortBy = 'number'"
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition-all"
                        :class="sortBy === 'number' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    >
                        เลขที่
                    </button>
                    <button 
                        @click="sortBy = 'score'"
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition-all"
                        :class="sortBy === 'score' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    >
                        คะแนน
                    </button>
                </div>

                <!-- Search -->
                <div class="relative w-full sm:w-64">
                    <Icon icon="heroicons:magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="ค้นหาชื่อ, รหัส..." 
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all placeholder:text-gray-400 dark:placeholder:text-gray-500 dark:text-white"
                    >
                </div>
            </div>
        </div>


        <!-- Layout: Top Performers followed by Member List -->
        <div class="space-y-8">
            <!-- Top Performers Section (Full Width) -->
            <div v-if="members.length > 0 && activeGroupTab !== -2">
                <TopPerformers :members="members" />
            </div>

            <!-- Main Content: Member List -->
            <div class="space-y-6">
                <!-- Group Tabs (Moved here for better flow) -->
                <div v-if="isCourseAdmin">
                    <div class="flex flex-wrap items-center gap-2 p-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                        <button 
                            @click="setActiveGroupTab(0)"
                            class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200"
                            :class="activeGroupTab === 0
                                ? 'bg-blue-500 text-white shadow-md'
                                : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        >
                            <Icon icon="heroicons:users" class="w-4 h-4 mr-2" />
                            ทั้งหมด ({{ totalAllMembers }})
                        </button>

                        <button 
                            v-for="(group, index) in courseGroupStore.groups" 
                            :key="group.id"
                            @click="setActiveGroupTab(index + 1)"
                            class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200"
                            :class="activeGroupTab === index + 1
                                ? 'bg-blue-500 text-white shadow-md'
                                : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        >
                            {{ group.name }} ({{ (group.members || []).filter(m => m.course_member_status === 1).length }})
                        </button>

                        <!-- ไม่มีกลุ่ม tab -->
                        <button 
                            @click="setActiveGroupTab(-1)"
                            class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200"
                            :class="activeGroupTab === -1
                                ? 'bg-amber-500 text-white shadow-md'
                                : (ungroupedCount > 0
                                    ? 'text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30 border border-amber-200 dark:border-amber-800'
                                    : 'text-gray-400 dark:text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800')"
                        >
                            <Icon icon="heroicons:user-minus" class="w-4 h-4 mr-2" />
                            ไม่มีกลุ่ม ({{ ungroupedCount }})
                        </button>

                        <!-- รออนุมัติ tab -->
                        <button 
                            @click="setActiveGroupTab(-2)"
                            class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 ml-auto"
                            :class="activeGroupTab === -2
                                ? 'bg-emerald-600 text-white shadow-md'
                                : (pendingCount > 0
                                    ? 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800'
                                    : 'text-gray-400 dark:text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800')"
                        >
                            <Icon icon="heroicons:clock" class="w-4 h-4 mr-2" />
                            รออนุมัติ ({{ pendingCount }})
                        </button>
                    </div>
                </div>

                <!-- Student View: Show only their group -->
                <div v-else-if="!isCourseAdmin && courseMemberStore.member?.group_id" class="mb-6">
                    <div class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/30 rounded-xl border border-blue-100 dark:border-blue-800">
                        <Icon icon="heroicons:user-group-solid" class="w-5 h-5 text-blue-500" />
                        <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                            กลุ่มเรียน: {{ courseGroupStore.groups.find(g => g.id === courseMemberStore.member?.group_id)?.name || 'ไม่ระบุ' }}
                        </span>
                    </div>
                </div>

                <!-- Content -->
                <div v-if="isLoading" class="flex justify-center py-20">
                    <div class="flex flex-col items-center gap-3">
                        <Icon icon="svg-spinners:ring-resize" class="w-10 h-10 text-blue-500" />
                        <span class="text-gray-500 animate-pulse">กำลังโหลดข้อมูลสมาชิก...</span>
                    </div>
                </div>

                <div v-else-if="members.length > 0">
                    <!-- Ungrouped Tab: Info banner -->
                    <div v-if="activeGroupTab === -1 && isCourseAdmin" class="mb-4">
                        <!-- Info banner -->
                        <div class="flex items-center gap-3 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                            <Icon icon="heroicons:information-circle" class="w-5 h-5 text-amber-500 flex-shrink-0" />
                            <span class="text-sm text-amber-700 dark:text-amber-300">
                                สมาชิกด้านล่างยังไม่ได้เข้าร่วมกลุ่มใดๆ สามารถจัดกลุ่ม ลบออกจากรายวิชา หรือแก้ไขข้อมูลสมาชิกได้
                            </span>
                        </div>
                    </div>

                    <!-- Pending Tab: Info banner -->
                    <div v-if="activeGroupTab === -2 && isCourseAdmin" class="mb-4">
                        <div class="flex items-center gap-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800">
                            <Icon icon="heroicons:check-badge" class="w-5 h-5 text-emerald-500 flex-shrink-0" />
                            <span class="text-sm text-emerald-700 dark:text-emerald-300">
                                รายการผู้สมัครที่รอการอนุมัติเข้าร่วมรายวิชา คุณสามารถตรวจสอบข้อมูลและอนุมัติหรือปฏิเสธได้ทันที
                            </span>
                        </div>
                    </div>

                    <!-- Group Bulk Unlock button (admin + specific group tab) -->
                    <div v-if="isCourseAdmin && activeGroupTab > 0" class="flex justify-end mb-3">
                        <button
                            @click="handleBulkUnlockGroup"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors"
                        >
                            <Icon icon="heroicons:lock-open" class="w-4 h-4" />
                            ปลดล็อคทั้งกลุ่ม (เฉพาะหมดสิทธิ์)
                        </button>
                    </div>

                    <!-- Card/Table View using new component (used for ALL tabs including ungrouped) -->
                    <MemberListView
                        v-if="effectiveViewMode === 'card' || effectiveViewMode === 'table'"
                        :members="members"
                        :view-mode="effectiveViewMode === 'card' ? 'card' : 'table'"
                        :course-total-score="course?.total_score || 100"
                        :is-course-admin="isCourseAdmin"
                        :available-groups="activeGroupTab === -1 ? courseGroupStore.groups : []"
                        :assigning-member-id="assigningGroupForMember"
                        :is-pending-view="activeGroupTab === -2"
                        @request-unmember="handleRequestUnmember"
                        @view-member="handleViewMember"
                        @edit-member="handleEditMember"
                        @assign-group="({ memberId, groupId }) => assignGroupToMember(memberId, groupId)"
                        @approve-request="handleApproveRequest"
                        @reject-request="handleRejectRequest"
                        @unlock-member="handleUnlockMember"
                    />

                    <!-- List View (original MemberCard) -->
                    <ul v-else class="flex flex-col gap-3">
                        <MemberCard
                            v-for="(member, index) in members"
                            :key="member.id"
                            :member="member"
                            :data-index="index"
                            :is-course-admin="isCourseAdmin"
                            :course-total-score="course?.total_score || 100"
                            :available-groups="activeGroupTab === -1 ? courseGroupStore.groups : []"
                            :assigning-member-id="assigningGroupForMember"
                            :is-pending-view="activeGroupTab === -2"
                            @request-unmember-course="handleRequestUnmember"
                            @view-member="handleViewMember"
                            @edit-member="handleEditMember"
                            @assign-group="({ memberId, groupId }) => assignGroupToMember(memberId, groupId)"
                            @approve-request="handleApproveRequest"
                            @reject-request="handleRejectRequest"
                            @unlock-member="handleUnlockMember"
                        />
                    </ul>
                </div>
                
                <!-- Empty State -->
                <div v-else class="text-center py-20 bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700">
                    <div class="inline-flex p-4 rounded-full bg-gray-50 dark:bg-gray-900 mb-4">
                        <Icon :icon="activeGroupTab === -2 ? 'heroicons:clock' : 'ph:users-three-duotone'" class="w-12 h-12 text-gray-400" />
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">
                        {{ activeGroupTab === -2 ? 'ไม่มีคำขอรออนุมัติ' : 'ไม่พบสมาชิก' }}
                    </h3>
                    <p class="text-gray-500">
                        {{ activeGroupTab === -2 ? 'เมื่อมีคนขอเข้าร่วมรายวิชา รายชื่อจะปรากฏที่นี่' : 'ลองเปลี่ยนคำค้นหา หรือตัวกรองกลุ่มเรียน' }}
                    </p>
                </div>
            </div>
        </div>
    <!-- Single Unlock Modal -->
    <Teleport to="body">
        <div v-if="showUnlockModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" @click="showUnlockModal = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <Icon icon="heroicons:lock-open" class="w-5 h-5 text-blue-500" />
                        ปลดล็อคสิทธิ์สอบ
                    </h3>

                    <div class="mb-4 flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <img
                            :src="unlockTargetMember?.user?.avatar || unlockTargetMember?.avatar || '/images/default-avatar.png'"
                            class="w-10 h-10 rounded-full object-cover flex-shrink-0"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-900 dark:text-white text-sm break-words">
                                {{ unlockTargetMember?.member_name || unlockTargetMember?.user?.name || '-' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 break-words">
                                {{ unlockTargetMember?.member_code || unlockTargetMember?.user?.email || '' }}
                            </p>
                            <p
                                v-if="unlockTargetMember?.absence_percent != null"
                                class="mt-0.5 text-xs font-medium text-red-600 dark:text-red-400"
                            >
                                ขาดเรียน {{ Number(unlockTargetMember.absence_percent).toFixed(1) }}%
                            </p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            เหตุผลในการปลดล็อค <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="unlockReason"
                            rows="3"
                            placeholder="ระบุเหตุผล..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 text-sm"
                        ></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button
                            @click="showUnlockModal = false"
                            class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
                        >
                            ยกเลิก
                        </button>
                        <button
                            @click="submitUnlock"
                            :disabled="!unlockReason.trim() || isUnlocking"
                            class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                        >
                            <Icon v-if="isUnlocking" icon="heroicons:arrow-path" class="w-4 h-4 mr-2 animate-spin inline" />
                            ปลดล็อค
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Group Bulk Unlock Confirm Modal -->
    <Teleport to="body">
        <div v-if="showBulkUnlockConfirm" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" @click="showBulkUnlockConfirm = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                        <Icon icon="heroicons:lock-open" class="w-5 h-5 text-blue-500" />
                        ปลดล็อคทั้งกลุ่ม
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        ปลดล็อคสิทธิ์สอบสำหรับสมาชิกที่หมดสิทธิ์ทั้งหมดในกลุ่ม
                        <strong class="text-gray-700 dark:text-gray-200">{{ courseGroupStore.groups[activeGroupTab - 1]?.name }}</strong>
                    </p>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            เหตุผล <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="bulkUnlockGroupReason"
                            rows="3"
                            placeholder="ระบุเหตุผลในการปลดล็อคกลุ่ม..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 text-sm"
                        ></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button
                            @click="showBulkUnlockConfirm = false"
                            class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
                        >
                            ยกเลิก
                        </button>
                        <button
                            @click="submitBulkUnlockGroup"
                            :disabled="!bulkUnlockGroupReason.trim() || isBulkUnlocking"
                            class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                        >
                            <Icon v-if="isBulkUnlocking" icon="heroicons:arrow-path" class="w-4 h-4 mr-2 animate-spin inline" />
                            ยืนยันปลดล็อค
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
    </div>
</template>
