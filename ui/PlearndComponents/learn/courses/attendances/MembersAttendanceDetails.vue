<script setup>
import { computed } from 'vue';
import AttendanceStatus from './AttendanceStatus.vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
    member: Object,
    groupAttendances: Object,
    isCourseAdmin: {
        type: Boolean,
        default: false
    }
});

</script>

<template>
    <tr class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 dark:hover:from-gray-700 dark:hover:to-gray-600 transition-all duration-200">
        <td class="p-2 border border-slate-300 dark:border-gray-600 whitespace-nowrap dark:text-gray-200">
            <div class="flex items-center min-w-fit group">
                <!-- Enhanced avatar with hover effect -->
                <div class="relative">
                    <img
                        class="w-14 h-14 rounded-full border-2 border-white dark:border-gray-700 shadow-md transition-all duration-300 group-hover:scale-105 group-hover:shadow-lg"
                        :src="member.user.avatar"
                        :alt="member.user.name"
                    >
                    <!-- Add status indicator ring -->
                    <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white dark:border-gray-800"></div>
                </div>
                
                <!-- Enhanced member info -->
                <div class="ml-3 flex-1">
                    <p class="text-gray-900 dark:text-gray-100 font-bold text-md tracking-wide group-hover:text-blue-700 dark:group-hover:text-violet-400 transition-colors duration-200">
                        {{ member.member_name ?? member.user.name }}
                    </p>
                    <div class="flex items-center mt-1 space-x-3 text-xs">
                        <!-- Enhanced order number badge -->
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-gradient-to-r from-violet-100 to-purple-100 dark:from-violet-900 dark:to-purple-900 text-violet-700 dark:text-violet-300 rounded-full font-medium border border-violet-200 dark:border-violet-700">
                            <Icon icon="fluent:number-symbol-square-20-regular" width="14" height="14" />
                            <span>{{ props.member.order_number ?? '#' }}</span>
                        </span>
                        
                        <!-- Enhanced group badge -->
                        <span v-if="props.member.group" class="inline-flex items-center gap-1 px-2 py-1 bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900 dark:to-indigo-900 text-blue-700 dark:text-blue-300 rounded-full font-medium border border-blue-200 dark:border-blue-700">
                            <Icon icon="heroicons:user-group" width="14" height="14" />
                            <span>{{ props.member.group.name }}</span>
                        </span>
                        <span v-else class="inline-flex items-center gap-1 px-2 py-1 bg-gradient-to-r from-gray-100 to-slate-100 dark:from-gray-700 dark:to-slate-700 text-gray-600 dark:text-gray-300 rounded-full font-medium border border-gray-200 dark:border-gray-600">
                            <Icon icon="heroicons:user-group" width="14" height="14" />
                            <span>ไม่มีกลุ่ม</span>
                        </span>
                    </div>
                </div>
            </div>
        </td>

        <!-- Enhanced attendance status cells -->
        <td v-for="(groupAttendance) in groupAttendances" :key="groupAttendance.id" class="p-3 whitespace-nowrap border border-slate-300 dark:border-gray-600 text-center">
            <div class="flex justify-center">
                <AttendanceStatus
                 :status="groupAttendance.details.filter(detail => detail.course_member_id === member.id)[0]?.status"
                 :attendance="groupAttendance"
                 :memberId="member.id"
                 :isCourseAdmin="isCourseAdmin"
                />
            </div>
        </td>
    </tr>
</template>
