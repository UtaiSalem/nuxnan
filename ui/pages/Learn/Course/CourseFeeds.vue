<script setup>
import { ref, onMounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import InfiniteLoading from "v3-infinite-loading";

import CourseLayout from "@/Layouts/CourseLayout.vue";
import CreateCoursePost from "@/PlearndComponents/learn/courses/posts/CreateCoursePost.vue";
import CoursePostViewer from '@/PlearndComponents/learn/courses/posts/CoursePostViewer.vue';
import PostLoadingSkeleton from '@/PlearndComponents/accessories/PostLoadingSkeleton.vue';

const props = defineProps({
    academy: Object,
    course: Object,
    courseMemberOfAuth: Object,
    isCourseAdmin: Boolean,
    activities: Object,
    courseGroups: Object,
});

const isDarkMode = ref(false);
const isMobile = ref(false);
const loading = ref(false);
const isLoadingCoursePosts = ref(false);
const courseActivities = ref(props.activities.data);

const currentPage = ref(1);
const lastPage = ref(props.activities.meta.last_page);
   

onMounted(() => { 
    if ('ontouchstart' in window) { isMobile.value = true; };
});

async function fetchMorePosts(){
    // revent from loading if already loading
    if (loading.value) return;

    try {
        currentPage.value++;
        if (currentPage.value < lastPage.value) {
            loading.value = true;
            isLoadingCoursePosts.value = true;
            let coursesActResp = await axios.get(usePage().url + '/get-more-activities?page='+ currentPage.value);
            if (coursesActResp.data.success) {
                coursesActResp.data.activities.forEach(activity => {
                    courseActivities.value.push(activity);
                });
            }
            loading.value = false;
            isLoadingCoursePosts.value = false;
        }
        loading.value = false;
    } catch (error) {
        console.log(error);
        loading.value = false;
        isLoadingCoursePosts.value = false;
    }
};

const handleAddNewActivity = (newActivity) => {
    courseActivities.value = [newActivity, ...courseActivities.value];
    // console.log(courseActivities.value);
}

</script>

<template>
    <CourseLayout
        :course 
        :isCourseAdmin
        :courseMemberOfAuth
        :activeTab="11"
    >
        <template #courseContent>
            <div class="mt-4">
                <CreateCoursePost v-if="courseMemberOfAuth" 
                    :course_id="props.course.data.id" 
                    @add-new-post="(newActivity) => handleAddNewActivity(newActivity)"
                />

                <div v-for="(activity, index) in courseActivities" :key="activity.id">
                    <CoursePostViewer 
                        :postKey="`post-${activity.id}`"
                        :postIndex="index"
                        :activity="activity"

                        @delete-post="courseActivities.splice(index, 1)"
                    />
                </div>

                <div v-if="isLoadingCoursePosts" class="mt-4">
                    <PostLoadingSkeleton />
                </div>

                <InfiniteLoading @distance="1" @infinite="fetchMorePosts" /> 

            </div>
        </template>
    </CourseLayout> 
</template>

