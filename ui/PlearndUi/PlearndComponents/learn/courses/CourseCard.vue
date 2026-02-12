<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

const props = defineProps({
    course: Object,
});

const emit = defineEmits(['loading-page']);

const isLoading = ref(false);

async function requestTobeCourseMember(){
    try {
        isLoading.value= true;
        let courseMemberResp = await axios.post(`/courses/${props.course.id}/members`);

        if (courseMemberResp && courseMemberResp.data.success) {

            props.course.member_status = courseMemberResp.data.memberStatus;
            props.course.enrolled_students++;
            isLoading.value= false;
            Swal.fire(
                'เสร็จสิ้น',
                'สมัครสมาชิกเรียบร้อยแล้ว',
                'success'
            )
        }else if(courseMemberResp && !courseMemberResp.data.success){
            isLoading.value= false;
            Swal.fire(
                'เกิดข้อผิดพลาด',
                    courseMemberResp.data.msg ,
                'error'
            )
        }
    } catch (error) {
        console.log(error);
    }
}

async function requestTobeUnmemberCourse(){
    try {
        isLoading.value= true;
        let memberResp = await axios.post(`/courses/${props.course.id}/unmembers`);
        if (memberResp.data && memberResp.data.success) {
            // console.log(memberResp.data);
            props.course.enrolled_students =  memberResp.data.enrolledStudents;
            props.course.isMember =  memberResp.data.isMember;
            props.course.member_status =  memberResp.data.memberStatus;
            isLoading.value= false;
            Swal.fire(
                'เสร็จสิ้น',
                'ออกจากสมาชิกเรียบร้อยแล้ว',
                'success'
            )
        }
    } catch (error) {
        isLoading.value= false;
        console.log(error);
    }
}

const handleClick = () => {
    // router.push({ name: 'course.show', params: { id: props.course.id } })
    // router.push(route('course.show', props.course.id));
    // route(`/courses/${props.course.id}`);
    // router.visit(`/courses/${props.course.id}`);
    emit('loading-page');
    
}

</script>

<template>
    <!-- Create By Joker Banny -->
    <button @click.prevent="handleClick" class="overflow-hidden bg-white shadow-lg rounded-xl hover:scale-105">
        <div class="relative">
            <img class="w-full "
                :src="props.course.cover ? '/storage/images/courses/covers/'+props.course.cover : '/storage/images/courses/covers/default_cover.jpg'"
                alt="Colors" />
            <p class="absolute top-0 right-0 px-2 py-1 font-semibold text-gray-800 rounded-bl-lg bg-yellow-300/75 ">
                <span class="flex text-sm text-white">
                <img :src="'/storage/images/badge/completedq.png'" alt="completedq-b" class="w-5 h-5 mr-1">
                {{ props.course.tuition_fees }} แต้ม
                </span>
            </p>
            <p class="absolute bottom-0 right-0 px-1 py-1 font-semibold text-gray-800 rounded-tl-lg bg-yellow-300/75">
                <span class="flex items-center justify-between space-x-2 text-sm text-white">
                <Icon icon="ri:bit-coin-line" class="w-6 h-6 mr-1" />
                {{ props.course.price }}
                </span>
            </p>
        </div>

        <h1 class="p-2 cursor-pointer hover:underline">
            <p class="text-base font-bold text-gray-700 ">
                {{ props.course.name }}
            </p>
            <p v-if="props.course.code" class="text-sm font-bold text-gray-500">{{ props.course.code }} </p>
            <p v-if="props.course.category" class="text-sm font-bold text-gray-500">{{ props.course.category }} </p>
            <!-- <p class="text-sm font-bold text-gray-500 truncate">{{ props.course.description }} </p> -->
        </h1>

        <div class="flex justify-between px-2">
            <div class="flex items-center space-x-2 author">
                <div class="user-logo">
                    <img class="object-cover w-10 h-10 rounded-full shadow" 
                    :src="props.course.user.avatar"
                    alt="avatar">
                </div>
                <h2 class="flex flex-col">
                    <a href="#" class="tracking-tighter text-gray-600 text-md">{{ props.course.user.name }}</a>
                    <span class="text-xs text-gray-400">ผู้ดูแล</span> 
                </h2>
            </div>

            <!-- <div class="flex justify-end">
                <p class="inline-block font-semibold leading-tight text-primary whitespace-nowrap rounded-xl">
                    <span class="text-sm uppercase">
                        $
                    </span>
                    <span class="text-lg">3,200</span>/m
                </p>
            </div> -->
        </div>

        <div class="flex flex-row flex-wrap justify-between p-2 ">
            <!-- <div class="flex items-center space-x-1">
                <span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600 mb-1.5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                <p>1:34:23 Minutes</p>
            </div> -->
            <div class="flex items-center space-x-1">
                <span class="text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M18 18.72a9.094 9.094 0 0 0 3.741-.479a3 3 0 0 0-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72a8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0a3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0a2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0a2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </span>
                <p>{{ props.course.enrolled_students }} คน</p>
            </div>
            <div class="flex items-center space-x-1">
                <span class="text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M11.3 20q.15.5.413 1.038t.537.962H5q-.825 0-1.413-.588T3 20V4q0-.825.588-1.413T5 2h12q.825 0 1.413.588T19 4v7.1q-.45-.05-1-.05t-1 .05V4h-5v6.125q0 .3-.25.438t-.5-.013L9.5 9.5l-1.75 1.05q-.25.15-.5.013T7 10.124V4H5v16h6.3Zm6.7 3q-2.075 0-3.538-1.463T13 18q0-2.075 1.463-3.538T18 13q2.075 0 3.538 1.463T23 18q0 2.075-1.463 3.538T18 23Zm-.475-2.975l2.55-1.6q.25-.15.25-.425t-.25-.425l-2.55-1.6q-.25-.15-.513-.013t-.262.438v3.2q0 .3.263.438t.512-.013ZM7 4h5h-5Zm4.3 0H5h12h-6h.3Z"/></svg>
                </span>
                <p>{{ props.course.lessons_count }} บทเรียน</p>
            </div>
        </div>
        <!-- <div class="p-2 ">
            <p class="text-gray-600">Course 75% completed</p>
            <div class="w-full h-3 mt-2 overflow-hidden bg-gray-400 rounded-lg">
                <div class="bg-indigo-600 w-[75%] h-full rounded-lg shadow-md"></div>
            </div>
        </div> -->
        <!-- <div v-if="props.course.user.id !== $page.props.auth.user.id" class="px-2 my-2 ">
            <button class="flex justify-center plearnd-btn-warning" :disabled="isLoading" v-if="props.course.member_status==3" @click.prevent="requestTobeUnmemberCourse">
                <span v-if="isLoading && props.course.member_status==3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><circle cx="12" cy="2" r="0" fill="currentColor"><animate attributeName="r" begin="0" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(45 12 12)"><animate attributeName="r" begin="0.125s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(90 12 12)"><animate attributeName="r" begin="0.25s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(135 12 12)"><animate attributeName="r" begin="0.375s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(180 12 12)"><animate attributeName="r" begin="0.5s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(225 12 12)"><animate attributeName="r" begin="0.625s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(270 12 12)"><animate attributeName="r" begin="0.75s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(315 12 12)"><animate attributeName="r" begin="0.875s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle></svg>
                </span>
                รอการตอบรับ
            </button>
            <button v-else-if="props.course.member_status==1" @click.prevent="requestTobeUnmemberCourse" :disabled="isLoading" class="flex justify-center plearnd-btn-secondary"

            >
                <span v-if="isLoading && props.course.member_status==1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><circle cx="12" cy="2" r="0" fill="currentColor"><animate attributeName="r" begin="0" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(45 12 12)"><animate attributeName="r" begin="0.125s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(90 12 12)"><animate attributeName="r" begin="0.25s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(135 12 12)"><animate attributeName="r" begin="0.375s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(180 12 12)"><animate attributeName="r" begin="0.5s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(225 12 12)"><animate attributeName="r" begin="0.625s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(270 12 12)"><animate attributeName="r" begin="0.75s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(315 12 12)"><animate attributeName="r" begin="0.875s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle></svg>
                </span>
                ออกจากรายวิชา
            </button>
            <button v-else class="flex justify-center plearnd-btn-primary " :disabled="isLoading" @click.prevent="requestTobeCourseMember" v-if="!props.course.memberStatus">
                <span v-if="isLoading && (props.course.member_status !=1 || props.course.member_status !=3)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><circle cx="12" cy="2" r="0" fill="currentColor"><animate attributeName="r" begin="0" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(45 12 12)"><animate attributeName="r" begin="0.125s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(90 12 12)"><animate attributeName="r" begin="0.25s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(135 12 12)"><animate attributeName="r" begin="0.375s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(180 12 12)"><animate attributeName="r" begin="0.5s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(225 12 12)"><animate attributeName="r" begin="0.625s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(270 12 12)"><animate attributeName="r" begin="0.75s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle><circle cx="12" cy="2" r="0" fill="currentColor" transform="rotate(315 12 12)"><animate attributeName="r" begin="0.875s" calcMode="spline" dur="1s" keySplines="0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8;0.2 0.2 0.4 0.8" repeatCount="indefinite" values="0;2;0;0"/></circle></svg>
                </span>
                สมัครเรียน
            </button>
        </div> -->
    </button>
</template>
