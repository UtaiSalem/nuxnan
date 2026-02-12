<script setup>
import { ref} from 'vue';
import { router } from '@inertiajs/vue3';

import CoursesLayout from '@/Layouts/CoursesLayout.vue';
import CourseCard from '@/PlearndComponents/learn/courses/CourseCard.vue';
import InfiniteLoading from "v3-infinite-loading";
import CoursesLoadingSkeleton from '@/PlearndComponents/accessories/CoursesLoadingSkeleton.vue'
import LoadingPage from '@/PlearndComponents/accessories/LoadingPage.vue';


const props = defineProps({
    courses: Object,
});

const loading = ref(false);
const currentPage = ref(1);
const lastPage = ref(props.courses.meta.last_page);
const allCourses = ref(props.courses.data);
const isLoadingPage = ref(false);

async function fetchMoreCourses(){
    try {
        currentPage.value++;
        if (currentPage.value <= lastPage.value) {
            loading.value = true;
            let coursesResp = await axios.get('/api/courses?page='+ currentPage.value);
            if (coursesResp.data.success) {
                coursesResp.data.courses.forEach(course => {
                    allCourses.value.push(course);
                });
            }
            loading.value = false;
        }
    } catch (error) {
        console.log(error);
        loading.value = false;
    }

};

const handleLoadingPage = (courseId) => {
    isLoadingPage.value = true;
    router.visit(`/courses/${courseId}`);
};

</script>

<template>
    <CoursesLayout coursePageTitle="รายวิชาทั้งหมด">
        <template #coursesMainContent>

            <LoadingPage v-if="isLoadingPage" />

            <div class="p-4 my-4 bg-white shadow-lg section-header rounded-xl">
                <div class="section-header-info">               
                    <h2 class="section-title font-prompt"> รายวิชาของฉัน {{ ' ' + props.courses.meta.total + ' วิชา' || 'ยังไม่มีรายวิชา' }}</h2>
                </div>
            </div>
            <div class="flex flex-wrap justify-between gap-4 ">
                <div v-for="(course, index) in allCourses" :key="index" class="w-full sm:w-[48%]"> 
                    <CourseCard :course @loading-page="handleLoadingPage(course.id)" />
                </div>
            </div>

            <div v-if="loading" class="flex flex-wrap justify-between gap-4">
                <div v-for="(index) in 2" :key="index" class="mt-4 w-full sm:w-[48%]">
                    <CoursesLoadingSkeleton />
                </div>
            </div>
            <InfiniteLoading @distance="1" @infinite="fetchMoreCourses" /> 
        </template>
    </CoursesLayout>
</template>
