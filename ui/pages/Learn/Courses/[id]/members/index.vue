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
const filterGroup = ref<string | number>('all')
// const groups = ref<any[]>([]) // Removed in favor of store
const viewMode = ref<'grid' | 'list'>('grid')
const isSavingGroupTab = ref(false)

import MemberCard from '~/components/learn/course/MemberCard.vue'

// Computed Members
const members = computed(() => {
    let list = []
    
    // Safety check for group existence
    const currentGroupId = filterGroup.value === 'all' ? 'all' : Number(filterGroup.value)

    if (currentGroupId === 'all') {
        // Aggregate all members from all groups (removing duplicates if a user is in multiple groups, though usually 1:1)
        // Or if API is still needed for 'all', we can keep a separate fetch. 
        // Per user request, "each group has members already", so we try to use store first.
        
        // Flatten members from all groups
        const allMembers = courseGroupStore.groups.flatMap(g => g.members || [])
        // Deduplicate by ID
        const seen = new Set()
        list = allMembers.filter(m => {
            const duplicate = seen.has(m.id)
            seen.add(m.id)
            return !duplicate
        })
    } else {
        const group = courseGroupStore.getGroupById(Number(currentGroupId))
        list = group?.members || []
    }

    // Restrict for students
    if (!isCourseAdmin.value && courseMemberStore.member?.group_id) {
        // Force filter to only show members in the same group as the current user
        // We filter the already populated 'list' (which might be 'all' or 'specific')
        // Ideally, if a student is restricted, 'group' logic below should already handle checking their group ID,
        // but this acts as a hard filter on the final result for safety.
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

    // Sort by order_number
    list.sort((a, b) => {
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
    // Set default group from preference (Only for admins or if filtering is allowed)
    // Note: API returns 'last_accessed_group_tab' (with 'd')
    if (isCourseAdmin.value && courseMemberStore.member?.last_accessed_group_tab) {
         filterGroup.value = courseMemberStore.member.last_accessed_group_tab
    } 
    // For students, we might want to default 'filterGroup' to their group ID conceptually, 
    // although our 'members' computed handles the filtering regardless.
    else if (!isCourseAdmin.value && courseMemberStore.member?.group_id) {
        filterGroup.value = courseMemberStore.member.group_id
    }

    if (course?.value?.id) {
        await courseGroupStore.fetchGroups(course.value.id)
    }
})

// Watch for group filter changes - save to last_accessed_group_tab
watch(filterGroup, async (newGroupId) => {
    // Only save if admin and not 'all' and course exists
    if (isCourseAdmin.value && newGroupId !== 'all' && course?.value?.id && !isSavingGroupTab.value) {
        isSavingGroupTab.value = true
        try {
            await api.patch(`/api/courses/${course.value.id}/members/update-last-access-group`, {
                last_accessed_group_tab: Number(newGroupId)
            })
            // Update local store
            if (courseMemberStore.member) {
                courseMemberStore.member.last_accessed_group_tab = Number(newGroupId)
            }
        } catch (error) {
            console.error('Error saving last accessed group tab:', error)
        } finally {
            isSavingGroupTab.value = false
        }
    }
})

// Watch for course changes
watch(() => course?.value?.id, async (newId) => {
    if (newId) {
        await courseGroupStore.fetchGroups(newId)
    }
})

import Swal from 'sweetalert2'

const handleRequestUnmember = async ({ memberId, memberName }: { memberId: number, memberName: string }) => {
    const result = await Swal.fire({
        title: 'ยืนยันการลบสมาชิก?',
        text: `คุณต้องการลบ "${memberName}" ออกจากรายวิชานี้ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    })

    if (result.isConfirmed) {
        try {
            await api.delete(`/api/courses/${course.value.id}/members/${memberId}`)
            Swal.fire(
                'ลบสำเร็จ!',
                'สมาชิกธถูกลบออกจากรายวิชาเรียบร้อยแล้ว.',
                'success'
            )
            // Refresh groups to update member lists
            await courseGroupStore.fetchGroups(course.value.id, true)
        } catch (error) {
            console.error('Failed to remove member:', error)
            Swal.fire(
                'เกิดข้อผิดพลาด!',
                'ไม่สามารถลบสมาชิกได้.',
                'error'
            )
        }
    }
}
</script>

<template>
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <Icon icon="ph:users-three-duotone" class="w-8 h-8 text-blue-500" />
                    สมาชิกในรายวิชา
                    <span v-if="!isLoading" class="text-sm font-normal text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full">
                        {{ totalmembers }} คน
                    </span>
                </h1>
                <p class="mt-1 text-gray-500 dark:text-gray-400 text-sm">
                    รายชื่อนักเรียนและผู้สอนทั้งหมดในรายวิชานี้
                </p>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-3">
                <!-- Search -->
                <div class="relative w-full md:w-64">
                    <Icon icon="heroicons:magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="ค้นหาชื่อ, รหัสนักศึกษา..." 
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all placeholder:text-gray-400 dark:placeholder:text-gray-500 dark:text-white"
                    >
                </div>

                <!-- Group Filter -->
                <div v-if="isCourseAdmin" class="flex items-center gap-2 w-full md:w-auto">
                    <Icon icon="heroicons:funnel" class="w-5 h-5 text-gray-400" />
                    <select 
                        v-model="filterGroup"
                        class="w-full md:w-64 px-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none cursor-pointer"
                    >
                        <option value="all">ทุกกลุ่มเรียน (All Groups)</option>
                        <option v-for="group in courseGroupStore.groups" :key="group.id" :value="group.id">
                            {{ group.name }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- content -->
        <div v-if="isLoading" class="flex justify-center py-20">
            <div class="flex flex-col items-center gap-3">
                <Icon icon="svg-spinners:ring-resize" class="w-10 h-10 text-blue-500" />
                <span class="text-gray-500 animate-pulse">กำลังโหลดข้อมูลสมาชิก...</span>
            </div>
        </div>

        <div v-else-if="members.length > 0">
            <!-- Unified List View -->
            <ul class="flex flex-col gap-3">
                <MemberCard 
                    v-for="(member, index) in members" 
                    :key="member.id"
                    :member="member"
                    :data-index="index"
                    @request-unmember-course="handleRequestUnmember"
                />
            </ul>
        </div>
        
        <!-- Empty State -->
        <div v-else class="text-center py-20 bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700">
            <div class="inline-flex p-4 rounded-full bg-gray-50 dark:bg-gray-900 mb-4">
                <Icon icon="ph:users-three-duotone" class="w-12 h-12 text-gray-400" />
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">ไม่พบสมาชิก</h3>
            <p class="text-gray-500">ลองเปลี่ยนคำค้นหา หรือตัวกรองกลุ่มเรียน</p>
        </div>
    </div>
</template>
