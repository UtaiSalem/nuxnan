<script setup>
import { ref, computed } from 'vue';
import { useApi } from '~/composables/useApi';
import { Icon } from '@iconify/vue';
import CourseLayout from '@/Layouts/CourseLayout.vue';
import StaggeredFade from '@/components/accessories/StaggeredFade.vue';
import MemberCard from '@/components/learn/course/MemberCard.vue';

const api = useApi();

const props = defineProps({
    course: Object,
    members: Object, 
    groups: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object,
});

// Get initial group tab based on last_accessed_group_tab
const getInitialGroupTab = () => {
    const lastAccessedGroupId = props.courseMemberOfAuth?.last_accessed_group_tab;
    if (!lastAccessedGroupId) return 0;
    
    const groups = props.groups?.data || [];
    const index = groups.findIndex(group => group.id === lastAccessedGroupId);
    return index >= 0 ? index : 0;
};

const activeGroupTab = ref(getInitialGroupTab());

// Computed helper for groups data
const groupsData = computed(() => props.groups?.data || []);

// Get ungrouped members (members without a group)
const unGroupedMembers = computed(() => {
    const members = props.members?.data || [];
    const courseOwnerId = props.course?.data?.user?.id;
    return members.filter(member => !member.group && !member.group_id).filter(member => member.user?.id !== courseOwnerId);
});

// Get members of the currently selected group
const activeGroupMembers = computed(() => {
    const groups = groupsData.value;
    
    // If "ไม่มีกลุ่ม" (ungrouped) tab is selected
    if (activeGroupTab.value >= groups.length) {
        return unGroupedMembers.value;
    }
    
    // Return members of the selected group
    if (groups[activeGroupTab.value]) {
        return groups[activeGroupTab.value].members || [];
    }
    
    return [];
});

// Get the current group info
const activeGroup = computed(() => {
    const groups = groupsData.value;
    if (activeGroupTab.value >= groups.length) {
        return { id: null, name: 'ไม่มีกลุ่ม', members_count: unGroupedMembers.value.length };
    }
    return groups[activeGroupTab.value] || null;
});

// Update last accessed group tab
async function setActiveGroupTab(tabIndex) {
    if (activeGroupTab.value === tabIndex) return;
    
    activeGroupTab.value = tabIndex;
    
    // Get the actual group ID from the index
    const groups = groupsData.value;
    if (tabIndex < groups.length && props.courseMemberOfAuth) {
        const groupId = groups[tabIndex].id;
        try {
            await api.patch(`/api/courses/${props.course.data.id}/members/update-last-access-group`, {
                last_accessed_group_tab: groupId
            });
        } catch (error) {
            console.error('Error saving group tab:', error);
        }
    }
}

// Handle unmember request
const handleUnmemberRequest = (data) => {
    // TODO: Implement unmember functionality
    console.log('Unmember request:', data);
};
</script>

<template>
    <div>
     <CourseLayout
        :isCourseAdmin
        :courseMemberOfAuth
        :course
        :activeTab="4"
     >
        <template #courseContent>
            <div class="mt-4">
                <!-- Group Selector Tabs -->
                <div class="mb-6">
                    <div class="flex items-center mb-4">
                        <Icon icon="heroicons:user-group-solid" class="w-6 h-6 text-indigo-600 mr-2" />
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">สมาชิกรายวิชา</h3>
                    </div>
                    
                    <!-- Group Tabs -->
                    <div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700 pb-2">
                        <!-- Group tabs -->
                        <button
                            v-for="(group, index) in groupsData"
                            :key="group.id"
                            @click="setActiveGroupTab(index)"
                            class="px-4 py-2 rounded-t-lg font-medium text-sm transition-all duration-200 flex items-center gap-2"
                            :class="activeGroupTab === index 
                                ? 'bg-indigo-600 text-white shadow-md' 
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                        >
                            <Icon icon="heroicons:users" class="w-4 h-4" />
                            <span>{{ group.name }}</span>
                            <span class="px-2 py-0.5 text-xs rounded-full"
                                  :class="activeGroupTab === index 
                                      ? 'bg-white/20 text-white' 
                                      : 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300'">
                                {{ group.members_count || group.members?.length || 0 }}
                            </span>
                        </button>
                        
                        <!-- Ungrouped members tab -->
                        <button
                            v-if="unGroupedMembers.length > 0"
                            @click="setActiveGroupTab(groupsData.length)"
                            class="px-4 py-2 rounded-t-lg font-medium text-sm transition-all duration-200 flex items-center gap-2"
                            :class="activeGroupTab === groupsData.length 
                                ? 'bg-orange-500 text-white shadow-md' 
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                        >
                            <Icon icon="heroicons:user-minus" class="w-4 h-4" />
                            <span>ไม่มีกลุ่ม</span>
                            <span class="px-2 py-0.5 text-xs rounded-full"
                                  :class="activeGroupTab === groupsData.length 
                                      ? 'bg-white/20 text-white' 
                                      : 'bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-300'">
                                {{ unGroupedMembers.length }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Active Group Info -->
                <div v-if="activeGroup" class="mb-4 p-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-700 rounded-xl border border-indigo-100 dark:border-gray-600">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="p-2 bg-indigo-100 dark:bg-indigo-900 rounded-lg mr-3">
                                <Icon icon="heroicons:user-group-solid" class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800 dark:text-white">{{ activeGroup.name }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ activeGroupMembers.length }} สมาชิก
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Members List -->
                <staggered-fade :duration="50" tag="ul" class="flex flex-col w-full space-y-3">
                    <template v-if="activeGroupMembers.length > 0">
                        <MemberCard
                            v-for="(member, index) in activeGroupMembers"
                            :key="member.id"
                            :member="member"
                            :dataIndex="index"
                            @request-unmember-course="handleUnmemberRequest"
                        />
                    </template>
                    <template v-else>
                        <div class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-xl">
                            <Icon icon="heroicons:users" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                            <p class="text-gray-500 dark:text-gray-400 text-lg">ยังไม่มีสมาชิกในกลุ่มนี้</p>
                        </div>
                    </template>
                </staggered-fade>
            </div>
        </template>

     </CourseLayout>
    </div>
</template>

