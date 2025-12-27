<script setup>
import CourseLayout from '@/Layouts/CourseLayout.vue';

const props = defineProps({
    isCourseAdmin: Boolean,
    course: Object,
    lessons: Object,
    groups: Object,
    member: Boolean,
});

</script>

<template>
    <div>
     <CourseLayout
        :isCourseAdmin="props.isCourseAdmin"
        :course="props.course"
        :lessons="props.lessons"
        :groups="props.groups"
        :activeTab="4"
     >
        <template #courseContent>
            <div>
                <div v-if="!props.lessons.data.length"  class="text-base text-gray-600 bg-white border-t-4 border-blue-500 rounded-lg p-4 my-4 shadow-lg ">
                    <div class="text-center">
                        <p>ยังไม่มีบทเรียนในรายวิชานี้</p>
                    </div>
                </div>

                <!-- <CourseLessonsList :lessons="props.lessons.data"  /> -->
                
                <!-- <CreateNewLesson @add-new-lesson="(newLesson) => props.lessons.data.push(newLesson)" v-if="props.isCourseAdmin" /> -->

            </div>
        </template>
     </CourseLayout>
    </div>
</template>

