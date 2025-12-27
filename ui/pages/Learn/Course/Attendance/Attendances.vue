<script setup>

import CourseLayout from '@/Layouts/CourseLayout.vue';
import CourseAttendance from '@/PlearndComponents/learn/courses/attendances/CourseAttendance.vue';
import CourseMemberAttendanceCard from '@/PlearndComponents/learn/courses/attendances/CourseMemberAttendanceCard.vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
    course: Object,
    groups: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object,
});


</script>

<template>
    <div>
        <CourseLayout 
            :course 
            :isCourseAdmin
            :courseMemberOfAuth
            :activeTab="7"
        >
            <template #courseContent>

                <div class=" mt-4" v-if="props.isCourseAdmin">
                    <CourseAttendance :groups="props.groups.data" />
                </div>
                
                <div class=" mt-4" v-else-if="props.courseMemberOfAuth">
                    <!-- Show warning if member has no group -->
                    <div v-if="!props.courseMemberOfAuth.group_id" class="bg-gradient-to-r from-amber-50 via-orange-50 to-yellow-50 border-l-4 border-amber-500 rounded-xl p-8 mb-6 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="flex-shrink-0 w-14 h-14 bg-amber-100 rounded-full flex items-center justify-center animate-pulse">
                                    <Icon icon="heroicons:exclamation-triangle" class="h-8 w-8 text-amber-600" />
                                </div>
                                <div>
                                    <p class="text-xl font-bold text-amber-800">คุณยังไม่มีกลุ่มสังกัด</p>
                                    <p class="text-amber-600 mt-1">กรุณาเข้าร่วมกลุ่มก่อนเพื่อดูข้อมูลการเข้าเรียน</p>
                                </div>
                            </div>
                            <a :href="`/courses/${props.course.id}/groups`"
                               class="px-6 py-3 bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-xl hover:from-amber-700 hover:to-orange-700 transition-all duration-300 text-sm font-bold flex items-center gap-2 shadow-md hover:shadow-lg transform hover:scale-105">
                                <Icon icon="heroicons:user-group" class="h-5 w-5" />
                                ไปที่เมนูกลุ่ม
                            </a>
                        </div>
                    </div>
                    
                    <!-- Show attendance card if member has a group -->
                    <div v-else class="w-full mx-auto">
                        <CourseMemberAttendanceCard
                            :courseMemberOfAuth
                        />
                    </div>
                </div>
            </template>
        </CourseLayout>
    </div>
</template>

