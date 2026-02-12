<script setup>
import { ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

import { Icon } from '@iconify/vue';
import MainLayout from '@/Layouts/MainLayout.vue';

defineProps({
    coursePageTitle: String,
});
const authUser = usePage().props.auth.user;
const isLoadingPage = ref(false);

const handleLoadingPage = (option) => {
    isLoadingPage.value = true;
    switch (option){
        case 1:
            router.visit(`/courses/users/${usePage().props.auth.user.id}`);
            break;
        case 2:
            router.visit(`/courses/users/${usePage().props.auth.user.id}/member`);
            break;
        case 3:
            router.visit(`/courses`);
            break;
        case 4:
            router.visit(`/courses/create`);
            break;
        default:
            // router.visit(option);
    };
}
</script>

<template>
        <MainLayout :title="coursePageTitle" >
            <template #coverProfileCard>

                <div v-if="isLoadingPage" class="fixed top-0 left-0 z-20 flex items-center justify-center w-full h-full modal">
                    <div class="absolute w-full h-full bg-gray-900 opacity-75 modal-overlay"></div>
                    <div class="flex items-center justify-center h-64">
                        <Icon icon="svg-spinners:bars-rotate-fade" class="z-30 w-32 h-32 text-white" />
                    </div>
                </div>
            
                <div class="hidden md:flex items-center max-w-7xl mx-auto mt-2 mb-4 shadow-lg bg-[url('/storage/images/banner/banner-bg.png')] bg-cover bg-no-repeat rounded-lg">
                    <img class="section-banner-icon" :src="'/storage/images/banner/forums-icon.png'" alt="forums-icon">
                    <p class="text-xl lg:text-4xl font-bold text-white">รวมรายวิชา</p>
                </div>
                
                <div class="w-full mx-auto mt-4 overflow-hidden bg-white rounded-lg shadow-xl max-w-7xl">
                    <div class="flex flex-row justify-around">
                        <!-- <Link :href="`/courses`" v-if="$page.url===`/courses/feeds`" class="flex-row justify-center w-full text-center border-b-4 rounded-none hover:border-cyan-500"
                            :class="{'border-b-4 border-cyan-500 bg-cyan-100': $page.url===`/courses/feeds`}"
                        >
                            <div class="flex flex-col items-center justify-center py-2 text-slate-700 ">
                                <Icon icon="bi:view-list" class="w-8 h-8" :class="{'text-cyan-500': $page.url===`/courses/feeds`}" />
                                <span :class="{'text-cyan-500': $page.url===`/courses/feeds`}">กระดานข่าวรายวิชา</span>
                            </div>
                        </Link> -->
                        <button type="button" @click="handleLoadingPage(1)"
                            class="flex-row justify-center w-full text-center border-b-4 rounded-none hover:border-gray-400 "
                            :class="{'border-b-4 border-cyan-500 bg-cyan-100': $page.url===`/courses/users/${$page.props.auth.user.id}`}"
                        >
                            <div class="flex flex-col items-center justify-center py-2 text-slate-700 ">
                                <Icon icon="lucide:layout-list" class="w-8 h-8" :class="{'text-cyan-500': $page.url===`/courses/users/${$page.props.auth.user.id}`}" />
                                <span :class="{'text-cyan-500': $page.url===`/courses/users/${$page.props.auth.user.id}`}">รายวิชาของฉัน</span>
                            </div>
                        </button>
                        <button type="button" @click="handleLoadingPage(2)"
                            class="flex-row justify-center w-full text-center border-b-4 rounded-none hover:border-gray-400 "
                            :class="{'border-b-4 border-cyan-500 bg-cyan-100': $page.url===`/courses/users/${$page.props.auth.user.id}/member`}"
                        >
                            <div class="flex flex-col items-center justify-center py-2 text-slate-700 ">
                                <Icon icon="lucide:layout-list" class="w-8 h-8" :class="{'text-cyan-500': $page.url===`/courses/users/${$page.props.auth.user.id}/member`}" />
                                <span :class="{'text-cyan-500': $page.url===`/courses/users/${$page.props.auth.user.id}/member`}">รายวิชาที่ฉันเป็นสมาชิก</span>
                            </div>
                        </button>
                        <button type="button" @click="handleLoadingPage(3)" class="flex-row justify-center w-full text-center border-b-4 rounded-none hover:border-gray-400 "
                            :class="{'border-b-4 border-cyan-500 bg-cyan-100': $page.url===`/courses`}"
                        >
                            <div class="flex flex-col items-center justify-center py-2 text-slate-700 ">
                                <Icon icon="lucide:layout-list" class="w-8 h-8" :class="{'text-cyan-500': $page.url===`/courses`}" />
                                <span :class="{'text-cyan-500': $page.url===`/courses`}">รวมรายวิชาทั้งหมด</span>
                            </div>
                        </button>
                        <button type="button" @click="handleLoadingPage(4)" v-if="authUser.pp > 120000" class="flex-row justify-center w-full text-center border-b-4 rounded-none hover:border-gray-400 "
                            :class="{'border-b-4 border-cyan-500 bg-cyan-100': $page.url===`/courses/create`}"
                        >
                            <div class="flex flex-col items-center justify-center py-2 text-slate-700">
                                <Icon icon="lucide:layout-list" class="w-8 h-8" :class="{'text-cyan-500': $page.url===`/courses/create`}" />
                                <span :class="{'text-cyan-500': $page.url===`/courses/create`}">เพิ่มรายวิชาใหม่</span>
                            </div>
                        </button>
                    </div>
                </div>
            </template>
            
            <template #leftSideWidget>
                <div>
                    
                </div>
            </template>

            <template #mainContent>
                <slot name="coursesMainContent"></slot>
            </template>

        </MainLayout>
</template>
