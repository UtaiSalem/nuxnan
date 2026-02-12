<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from "@inertiajs/vue3";
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

import MainLayout from "@/Layouts/MainLayout.vue";
import AcademyCoverProfile from '@/PlearndComponents/learn/academies/AcademyCoverProfile.vue';
import AcademyNavbarTab from '@/PlearndComponents/learn/academies/AcademyNavbarTab.vue';

const props = defineProps({
    academy: Object,
    isAcademyAdmin: Boolean,
});

const isLoadingAcademyPosts = ref(false);
const academyActivities = ref([]);


// onMounted(() => {
    // handleGetAcademyPosts();
    // getAcademyCourses();
    // getAcademyActivities();
// });

// const handleGetAcademyPosts = async () => {
//     try {
//         isLoadingAcademyPosts.value = true;
//         const response = await axios.get(`/api/academies/${props.academy.data.id}/posts`);
//         if (response.data.success) {
//             academyPosts.value = response.data.posts;
//             isLoadingAcademyPosts.value = false;
//         } else {
//             isLoadingAcademyPosts.value = false;
//             Swal.fire({
//                 icon: 'error',
//                 title: 'เกิดข้อผิดพลาด! กรุณาลองใหม่อีกครั้ง',
//                 text: response.data.message,
//             });
//         }
//     } catch (error) {
//         console.log(error);
//         isLoadingAcademyPosts.value = false;
//     }
// };

const getAcademyCourses = async () => {
    try {
        isLoadingAcademyPosts.value = true;
        const response = await axios.get(`/api/academies/${props.academy.data.id}/courses`);
        if (response.data.success) {
            console.log(response.data.courses);
            academyCourses.value = response.data.courses;

            console.log(academyCourses.value);

            isLoadingAcademyPosts.value = false;
        } else {
            isLoadingAcademyPosts.value = false;
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด! กรุณาลองใหม่อีกครั้ง',
                text: response.data.message,
            });
        }
    } catch (error) {
        console.log(error);
        isLoadingAcademyPosts.value = false;
    }
};

const getAcademyActivities = async () => {
    try {
        isLoadingAcademyPosts.value = true;
        const response = await axios.get(`/api/academies/${props.academy.data.id}/activities`);
        if (response.data.success) {
            console.log(response.data);
            academyActivities.value = response.data.activities;

            // console.log(academyActivities.value);

            isLoadingAcademyPosts.value = false;
        } else {
            isLoadingAcademyPosts.value = false;
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด! กรุณาลองใหม่อีกครั้ง',
                text: response.data.message,
            });
        }
    } catch (error) {
        console.log(error);
        isLoadingAcademyPosts.value = false;
    }
};

// const isDarkMode = ref(false);
// const currentTabIndex = ref(0);

const tempLogo = ref(props.academy.data.logo? '/storage/images/academies/logos/' + props.academy.data.logo:'/storage/images/academies/logos/default_logo.png');
// const tempLogo = ref(props.app_url + '/storage/images/academies/logos/' + props.academy.data.logo  || props.app_url + '/storage/images/academies/logos/default_logo.png');
// const tempCover = ref(props.app_url + '/storage/images/academies/covers/' + props.academy.data.cover || props.app_url + '/storage/images/academies/covers/default_cover.png');
const tempCover = ref(props.academy.data.cover? '/storage/images/academies/covers/' + props.academy.data.cover : '/storage/images/academies/covers/default_cover.png');
const tempHeader = ref(props.academy.data.name);
const tempSubheader = ref(props.academy.data.slogan);

// const formAcademyUpdate = useForm({
//     name:'', 
//     slogan:'', 
//     address:'', 
//     cover:null, 
//     logo:null,
// });

async function onCoverImageChange(coverFile) {
    const academyCoverUpdate = new FormData();
    academyCoverUpdate.append('cover', coverFile); 
    academyCoverUpdate.append('_method', 'patch');
    await axios.post(`/academies/${props.academy.data.id}/update`, academyCoverUpdate);
}

async function onLogoImageChange(logoFile) {
    const academyLogoUpdate = new FormData();
    academyLogoUpdate.append('logo', logoFile); 
    academyLogoUpdate.append('_method', 'patch');
    await axios.post(`/academies/${props.academy.data.id}/update`, academyLogoUpdate);
}

async function onHeaderChange(academyName) {
    await axios.patch(`/academies/${props.academy.data.id}/update`, { name:academyName });
}

async function onSubheaderChange(academySlogan) {
    await axios.patch(`/academies/${props.academy.data.id}/update`, { slogan:academySlogan });
}

async function onRequestToBeAMember(){
    try {
        let memberResp = await axios.post(`/academies/${props.academy.data.id}/members`);
        if (memberResp.data && memberResp.data.success) {
            props.academy.data.memberStatus=memberResp.data.memberStatus;
            props.academy.data.total_students = memberResp.data.totalStudents;
            if (memberResp.data && memberResp.data.success) {
                props.academy.data.memberStatus = memberResp.data.memberStatus;
                Swal.fire(
                    'เสร็จสิ้น',
                    'ขอเป็นสมาชิกเรียบร้อยแล้ว',
                    'success'
                )
            }
        }else if(memberResp.data && !memberResp.data.success) {
            Swal.fire(
                'เกิดข้อผิดพลาด',
                    memberResp.data.msg ,
                'error'
            )
        }
    } catch (error) {
        console.log(error);
    }
}

async function onRequestToBeUnmember(){
    console.log('unmember');
    try {
        let memberResp = await axios.post(`/academies/${props.academy.data.id}/unmembers`);
        if (memberResp.data && memberResp.data.success) {
            if (props.academy.data.memberStatus==2) {
                props.academy.data.total_students--;
            }
            props.academy.data.memberStatus=null;
            Swal.fire(
                'เสร็จสิ้น',
                'ออกจากการเป็นสมาชิกเรียบร้อยแล้ว',
                'success'
            )
        }
    } catch (error) {
        console.log(error);
    }
}

</script>

<template>
    <MainLayout :title="props.academy.data.name">

        <template #coverProfileCard>
            <AcademyCoverProfile 
                        :coverImage="tempCover" 
                        :logoImage="tempLogo" 
                        v-model:coverHeader="tempHeader"
                        v-model:coverSubheader="tempSubheader"

                        :model="'Academy'"
                        :modelTable="'academies'"
                        :modelableId="props.academy.data.id"
                        :modelableType="'app/models/Academy'"
                        :modelableRoute="`/academies/${props.academy.data.id}`"
                        :modelData="props.academy.data"
                        :subModelDataLength="props.academy.data.courses_offered"
                        :subModelNameTh="'รายวิชา'"
                        :isAcademyAdmin="props.isAcademyAdmin"

                        @cover-image-change="(data) => { onCoverImageChange(data); }"
                        @logo-image-change="(data) => { onLogoImageChange(data); }"
                        @header-change="(data)=>{ onHeaderChange(data) }"
                        @subheader-change="(data)=>{ onSubheaderChange(data) }"
                        @request-tobe-member="onRequestToBeAMember"
                        @request-tobe-unmember="onRequestToBeUnmember"
                    ></AcademyCoverProfile>

                    <AcademyNavbarTab :academy :activeTab="0" />

        </template>

        <template #leftSideWidget>
            <div class="space-y-4">
                <!-- <CourseCardWidget :course="$page.props.course.data"/> -->

                <!-- <CourseGroupProfileWidget /> -->
            </div>
        </template>

        <template #mainContent>
            <div class="mt-4">
                <div v-if="$slots.academyContent">
                    <slot name="academyContent"></slot>
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
