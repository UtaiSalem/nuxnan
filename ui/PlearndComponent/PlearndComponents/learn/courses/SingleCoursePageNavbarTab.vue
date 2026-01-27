<script setup>
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    isCourseAdmin: Boolean,
    course: Object,
    courseMemberOfAuth: Object,
    activeTab: Number,
});

const isLoading = ref(false);

const setActiveTab = async (tab) => {
    // if(props.courseMemberOfAuth) { await axios.post(`/courses/${props.course.data.id}/members/${props.courseMemberOfAuth.id}/set-active-tab`, {tab: tab}) };
    switch (tab) {
        case 1:
            isLoading.value = true;
            router.visit('/courses/'+props.course.data.id+'/lessons');
            break;
        case 2:
            // router.visit('/courses/'+props.course.data.id+'/exercises');
            isLoading.value = true;
            router.visit('/courses/'+props.course.data.id+'/assignments');
            break;
        case 3:
            // router.visit('/courses/'+props.course.data.id+'/exams');
            isLoading.value = true;
            router.visit('/courses/'+props.course.data.id+'/quizzes');
            break;
        case 4:
            isLoading.value = true;
            router.visit('/courses/'+props.course.data.id+'/members');
            break;
        case 5:
            isLoading.value = true;
            router.visit('/courses/'+props.course.data.id+'/groups');
            break;
        case 6:
            // router.visit('/courses/'+props.course.data.id+'/member-requests');
            isLoading.value = true;
            router.visit(`/courses/${props.course.data.id}/members/member-requesters`);
            break;
        case 7:
            isLoading.value = true;
            router.visit('/courses/'+props.course.data.id+'/attendances');
            break;
        case 8:
            isLoading.value = true;
            router.visit('/courses/'+props.course.data.id+'/settings');
            break;
        case 9:
            isLoading.value = true;
            router.visit('/courses/'+props.course.data.id+'/members/'+props.courseMemberOfAuth.id+'/member-settings');
            break;
        case 10:
            isLoading.value = true;
            router.visit('/courses/'+props.course.data.id+'/progress');
            break;
        case 11:
            isLoading.value = true;
            router.visit('/courses/'+props.course.data.id+'/feeds');
            break;
        case 12:
            isLoading.value = true;
            router.visit('/courses/'+props.course.data.id+'/basic-info');
        break;
        default:
            isLoading.value = true;
            router.visit('/courses/'+props.course.data.id+'/lessons');
            break;
    }
};
</script>

<template>
    <div class="w-full mt-4 overflow-hidden bg-white rounded-lg shadow-xl border border-gray-100">
        <div class="flex flex-row justify-around relative">

            <button type="button" @click.prevent="setActiveTab(12)"
                class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
                :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 to-white shadow-sm': activeTab === 12, 'hover:bg-gray-50': activeTab !== 12 }">
                <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 transition-all duration-300">
                    <Icon icon="heroicons:information-circle" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
                        :class="{ 'text-cyan-500 scale-110': activeTab === 12, 'hover:text-cyan-400': activeTab !== 12 }" />
                    <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 12 }">ข้อมูลทั้วไป</span>
                </div>
            </button>

            <button type="button" @click.prevent="setActiveTab(11)"
                class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
                :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 to-white shadow-sm': activeTab === 11, 'hover:bg-gray-50': activeTab !== 11 }">
                <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 transition-all duration-300">
                    <Icon icon="codicon:feedback" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
                        :class="{ 'text-cyan-500 scale-110': activeTab === 11, 'hover:text-cyan-400': activeTab !== 11 }" />
                    <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 11 }">กระดาน</span>
                </div>
            </button>

            <button v-if="props.isCourseAdmin || props.courseMemberOfAuth" type="button" @click.prevent="setActiveTab(7)"
                class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
                :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 to-white shadow-sm': activeTab === 7, 'hover:bg-gray-50': activeTab !== 7 }">
                <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 transition-all duration-300">
                    <Icon icon="tabler:calendar-user" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
                        :class="{ 'text-cyan-500 scale-110': activeTab === 7, 'hover:text-cyan-400': activeTab !== 7 }" />
                    <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 7 }">การเข้าเรียน</span>
                </div>
            </button>

            <button type="button" @click.prevent="setActiveTab(1)"
                class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
                :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 to-white shadow-sm': activeTab === 1, 'hover:bg-gray-50': activeTab !== 1 }">
                <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 transition-all duration-300">
                    <Icon icon="icon-park-outline:view-grid-detail" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
                        :class="{ 'text-cyan-500 scale-110': activeTab === 1, 'hover:text-cyan-400': activeTab !== 1 }" />
                    <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 1 }">บทเรียน</span>
                </div>
            </button>

            <button type="button" @click.prevent="setActiveTab(2)"
                class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
                :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 to-white shadow-sm': activeTab === 2, 'hover:bg-gray-50': activeTab !== 2 }">
                <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 transition-all duration-300">
                    <Icon icon="material-symbols:assignment-add-outline" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
                        :class="{ 'text-cyan-500 scale-110': activeTab === 2, 'hover:text-cyan-400': activeTab !== 2 }" />
                    <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 2 }">ภาระงาน</span>
                </div>
            </button>

            <button type="button" @click.prevent="setActiveTab(3)" v-if="props.courseMemberOfAuth || props.isCourseAdmin"
                class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
                :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 to-white shadow-sm': activeTab === 3, 'hover:bg-gray-50': activeTab !== 3 }">
                <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 transition-all duration-300">
                    <Icon icon="healthicons:i-exam-qualification-outline" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
                        :class="{ 'text-cyan-500 scale-110': activeTab === 3, 'hover:text-cyan-400': activeTab !== 3 }" />
                    <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 3 }">ทดสอบ</span>
                </div>
            </button>

            <button type="button" @click.prevent="setActiveTab(5)"
                class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
                :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 to-white shadow-sm': activeTab === 5, 'hover:bg-gray-50': activeTab !== 5 }">
                <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 transition-all duration-300">
                    <Icon icon="heroicons-outline:user-group" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
                        :class="{ 'text-cyan-500 scale-110': activeTab === 5, 'hover:text-cyan-400': activeTab !== 5 }" />
                    <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 5 }">กลุ่ม</span>
                </div>
            </button>
            
            <button type="button" @click.prevent="setActiveTab(4)" v-if="props.courseMemberOfAuth !== null"
                class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
                :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 to-white shadow-sm': activeTab === 4, 'hover:bg-gray-50': activeTab !== 4 }">
                <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 transition-all duration-300">
                    <Icon icon="ph:users-four" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
                        :class="{ 'text-cyan-500 scale-110': activeTab === 4, 'hover:text-cyan-400': activeTab !== 4 }" />
                    <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 4 }">สมาชิก</span>
                </div>
            </button>

            <!-- <button v-if="props.isCourseAdmin" type="button" @click.prevent="setActiveTab(6)"
                class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 "
                :class="{ 'border-b-4 border-cyan-500 bg-cyan-100': activeTab === 6 }">
                <div class="flex flex-col items-center justify-center py-2 text-slate-600/80 ">
                    <Icon icon="lets-icons:group-add-light" class="w-6 h-6 md:w-8 md:h-8"
                        :class="{ 'text-cyan-500': activeTab === 6 }" />
                    <span class="hidden md:block" :class="{ 'text-cyan-500': activeTab === 6 }">คำขอเป็นสมาชิก</span>
                </div>
            </button> -->



            <button type="button" @click.prevent="setActiveTab(8)" v-if="props.isCourseAdmin"
                class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
                :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 to-white shadow-sm': activeTab === 8, 'hover:bg-gray-50': activeTab !== 8 }">
                <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 transition-all duration-300">
                    <Icon icon="mdi-light:settings" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
                        :class="{ 'text-cyan-500 scale-110': activeTab === 8, 'hover:text-cyan-400': activeTab !== 8 }" />
                    <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 8 }">ตั้งค่า</span>
                </div>
            </button>

            <button type="button" @click.prevent="setActiveTab(9)" v-if="!props.isCourseAdmin && props.courseMemberOfAuth"
                class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
                :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 to-white shadow-sm': activeTab === 9, 'hover:bg-gray-50': activeTab !== 9 }">
                <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 transition-all duration-300">
                    <Icon icon="mdi:graph-box-plus-outline" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
                        :class="{ 'text-cyan-500 scale-110': activeTab === 9, 'hover:text-cyan-400': activeTab !== 9 }" />
                    <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 9 }">ผลการเรียน</span>
                </div>
            </button>

            <button type="button" @click.prevent="setActiveTab(10)" v-if="props.isCourseAdmin"
                class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
                :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 to-white shadow-sm': activeTab === 10, 'hover:bg-gray-50': activeTab !== 10 }">
                <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 transition-all duration-300">
                    <Icon icon="mdi:graph-box-plus-outline" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
                        :class="{ 'text-cyan-500 scale-110': activeTab === 10, 'hover:text-cyan-400': activeTab !== 10 }" />
                    <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 10 }">ผลการเรียน</span>
                </div>
            </button>
            
            <!-- <Link :href="`/course/${$page.props.course.data.id}/attendaces`"
                class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 "
                >
                <div class="flex flex-col items-center justify-center py-2 text-slate-600/80 ">
                    <Icon icon="mdi:graph-box-outline" class="w-6 h-6 md:w-8 md:h-8"
                            />
                    <span class="hidden md:block">ผลการเรียน</span>
                </div>
            </Link> -->

        </div>
    </div>
    <div v-if="isLoading" class="fixed top-0 left-0 z-50 flex items-center justify-center w-full h-full modal">
        <div class="absolute w-full h-full bg-gray-900 opacity-75 modal-overlay backdrop-blur-sm"></div>
        <div class="flex flex-col items-center justify-center p-8 bg-white rounded-2xl shadow-2xl transform transition-all duration-300 scale-100">
            <Icon icon="svg-spinners:bars-rotate-fade" class="w-16 h-16 text-cyan-500 mb-4 animate-pulse" />
            <p class="text-gray-700 font-medium animate-pulse">กำลังโหลด...</p>
        </div>
    </div>
</template>
