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
const activeGroupTab = ref(0) // 0 = all, 1+ = group index
const viewMode = ref<'grid' | 'list'>('grid')
const isSavingGroupTab = ref(false)
const sortBy = ref<'number' | 'score'>('number') // 'number' | 'score'

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
        } finally {
            isSavingGroupTab.value = false
        }
    }
}

import MemberCard from '~/components/learn/course/MemberCard.vue'
import TopPerformers from '~/components/learn/course/TopPerformers.vue'

// Computed Members
const members = computed(() => {
    let list = []
    
    if (activeGroupTab.value === 0) {
        // "All" tab - aggregate all members from all groups
        const allMembers = courseGroupStore.groups.flatMap(g => g.members || [])
        // Deduplicate by ID
        const seen = new Set()
        list = allMembers.filter(m => {
            const duplicate = seen.has(m.id)
            seen.add(m.id)
            return !duplicate
        })
    } else {
        // Specific group tab
        const group = courseGroupStore.groups[activeGroupTab.value - 1]
        list = group?.members || []
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
            const scoreA = Number(a.achieved_score || 0)
            const scoreB = Number(b.achieved_score || 0)
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
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
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

            <!-- Search and Sort -->
            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                <!-- Sort Tabs -->
                <div class="flex bg-gray-100 dark:bg-gray-800 p-1 rounded-lg">
                    <button 
                        @click="sortBy = 'number'"
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition-all"
                        :class="sortBy === 'number' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    >
                        เรียงตามเลขที่
                    </button>
                    <button 
                        @click="sortBy = 'score'"
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition-all"
                        :class="sortBy === 'score' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    >
                        เรียงตามคะแนน
                    </button>
                </div>

                <!-- Search -->
                <div class="relative w-full sm:w-64">
                    <Icon icon="heroicons:magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="ค้นหาชื่อ, รหัสนักศึกษา..." 
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all placeholder:text-gray-400 dark:placeholder:text-gray-500 dark:text-white"
                    >
                </div>
            </div>
        </div>


        <!-- Layout with Leaderboard -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Main Content: Member List -->
            <div class="lg:col-span-3 order-2 lg:order-1">
                <!-- Group Tabs (Moved here for better flow) -->
                <div v-if="isCourseAdmin && courseGroupStore.groups.length > 0" class="mb-6">
                    <div class="flex flex-wrap items-center gap-2 p-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                        <button 
                            @click="setActiveGroupTab(0)"
                            class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200"
                            :class="activeGroupTab === 0
                                ? 'bg-blue-500 text-white shadow-md'
                                : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        >
                            <Icon icon="heroicons:users" class="w-4 h-4 mr-2" />
                            ทั้งหมด ({{ courseGroupStore.groups.reduce((sum, g) => sum + (g.members?.length || 0), 0) }})
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
                            {{ group.name }} ({{ group.members?.length || 0 }})
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

            <!-- Sidebar: Leaderboard -->
            <div class="lg:col-span-1 order-1 lg:order-2">
                <TopPerformers v-if="members.length > 0" :members="members" />
            </div>
        </div>
    </div>
</template>
