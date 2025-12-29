<script setup>
import { computed } from 'vue';
import { Icon } from '@iconify/vue';

import MainLayout from "~/layouts/main.vue";
import CourseNavbarTab from '@/components/course/CourseNavbarTab.vue';

const props = defineProps({
    course: Object,
    groups: Object,
    lessons: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object,
    courseGroups: { type: Array, default: () => [] },
});

// Computed course data for the new component
const courseData = computed(() => props.course?.data || props.course);

// Refresh handler
function handleRefresh() {
    // Reload the page or emit refresh event
    window.location.reload();
}
</script>

<template>
    <MainLayout :title="courseData?.name">
        <template #coverProfileCard>
            <CourseProfileCover
                :course="courseData"
                :isCourseAdmin="props.isCourseAdmin"
                :courseMemberOfAuth="props.courseMemberOfAuth"
                :courseGroups="props.courseGroups || props.groups || []"
                @refresh="handleRefresh"
            />

            <CourseNavbarTab
                :courseId="courseData?.id"
                :courseMemberOfAuth="props.courseMemberOfAuth"
                :isCourseAdmin="props.isCourseAdmin"
            />
        </template>

        <template #flashSidebarLeft>
            <div class="top-16 left-0">
                
            </div>
        </template>

        <template #leftSideWidget>
            <div class="space-y-4">
                <!-- <CourseCardWidget :course="$page.props.course.data"/> -->

                <!-- <CourseGroupProfileWidget /> -->
                <!-- <NavigationWidgets /> -->
            </div>
        </template>

        <template #mainContent>
            <div class="mt-4">
                <div v-if="$slots.courseContent">
                    <slot name="courseContent"></slot>
                </div>
            </div>
        </template>

        <template #rightSideWidget>
            <div>
                <!-- <CourseCardWidget :course="$page.props.course.data"/> -->
            </div>
        </template>
    </MainLayout>
</template>
