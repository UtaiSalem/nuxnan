<script setup>
import { ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3'
import CoursesLayout from '@/Layouts/CoursesLayout.vue';
import CourseCard from '@/PlearndComponents/learn/courses/CourseCard.vue';
import InfiniteLoading from "v3-infinite-loading";
import { Icon } from '@iconify/vue';

const props = defineProps({
    courses: Object,
});

const authMemberedCourses = ref(props.courses.data);
const isLoading = ref(false);
const isLoadingPage = ref(false);
const currentPage = ref(1);
const lastPage = ref(props.courses.meta.last_page);


async function fetchMoreCourses(){
    try {
        currentPage.value++;
        if (currentPage.value <= lastPage.value) {
            isLoading.value = true;
            let coursesResp = await axios.get(`/api/courses/users/${usePage().props.auth.user.id}/membered?page=${ currentPage.value }`);
            if (coursesResp.data.success) {
                coursesResp.data.courses.forEach(course => {
                    authMemberedCourses.value.push(course);
                });
            }
        }
        isLoading.value = false;
    } catch (error) {
        console.log(error);
        isLoading.value = false;
    }
};

const handleLoadingPage = (courseId) => {
    isLoadingPage.value = true;
    router.visit(`/courses/${courseId}`);
};
</script>

<template>
        <CoursesLayout title="Courses" >
            <template #coursesMainContent>

            
                <div v-if="isLoadingPage" class="fixed top-0 left-0 z-20 flex items-center justify-center w-full h-full modal">
                    <div class="absolute w-full h-full bg-gray-900 opacity-75 modal-overlay"></div>
                    <div class="flex items-center justify-center h-64">
                        <Icon icon="svg-spinners:bars-rotate-fade" class="z-30 w-32 h-32 text-white" />
                    </div>
                </div>
                
                <div class="p-4 my-4 bg-white shadow-lg section-header rounded-xl">
                    <div class="section-header-info">               
                        <h2 class="section-title font-prompt"> รายวิชาที่ฉันเป็นสมาชิก  {{ ' ' + props.courses.meta.total + ' วิชา' || 'ยังไม่มีรายวิชา' }}</h2>
                    </div>
                </div>

                <div class="flex flex-wrap justify-between gap-4 ">
                    <div v-for="(course, index) in authMemberedCourses" :key="index" class="w-full sm:w-[48%]"> 
                        <CourseCard :course="course"  @loading-page="handleLoadingPage(course.id)" />
                    </div>
                </div>

                <div class="flex flex-wrap justify-between gap-4 mt-4" v-if="isLoading">
                    <div v-for="index in 2" :key="index" class="w-full sm:w-[48%]">
                        <!-- <div class="p-4 md:w-1/3"> -->
                            <div class="w-full overflow-hidden rounded-lg shadow-xl">
                                <div class="object-cover object-center w-full bg-gray-400/40 h-36"></div>
                                <div class="p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex-shrink-0 w-10 h-10 bg-gray-400 rounded-full"></div>
                                        <div class="flex-1 py-2 mx-2 space-y-4">
                                            <div class="w-full h-3 leading-relaxed bg-gray-400 rounded animate-pulse"></div>
                                            <div class="w-5/6 h-3 leading-relaxed bg-gray-400 rounded animate-pulse"></div>
                                        </div>
                                    </div>
                                    <p class="w-full h-3 mb-3 leading-relaxed bg-gray-400 rounded animate-pulse"></p>
                                    <p class="w-2/3 h-3 mb-3 leading-relaxed bg-gray-400 rounded animate-pulse"></p>
                                    <p class="w-1/2 h-3 mb-3 leading-relaxed bg-gray-400 rounded animate-pulse"></p>
                                </div>
                            </div>
                        <!-- </div> -->
                    </div>
                </div>

                <InfiniteLoading @distance="1" @infinite="fetchMoreCourses" /> 

            </template>
        </CoursesLayout>
</template>
