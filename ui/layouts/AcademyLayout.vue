<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from "@inertiajs/vue3";
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import { useApi } from '@/composables/useApi';

import MainLayout from "~/layouts/main.vue";
import AcademyCoverProfile from '~/components/learn/academy/AcademyCoverProfile.vue';
import AcademyNavbarTab from '~/components/learn/academy/AcademyNavbarTab.vue';

const props = defineProps({
    academy: Object,
    isAcademyAdmin: Boolean,
    activeTab: {
        type: Number,
        default: 0
    }
});

const api = useApi();
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
//         
//         isLoadingAcademyPosts.value = false;
//     }
// };

const getAcademyCourses = async () => {
    try {
        isLoadingAcademyPosts.value = true;
        const response = await api.get(`/api/academies/${props.academy.data.id}/courses`);
        if (response.success) {
            academyCourses.value = response.courses;
            isLoadingAcademyPosts.value = false;
        } else {
            isLoadingAcademyPosts.value = false;
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด! กรุณาลองใหม่อีกครั้ง',
                text: response.message,
            });
        }
        } catch (error) {
          // Dev-only logging
          isLoadingAcademyPosts.value = false;
        }
      };

      const getAcademyActivities = async () => {
    try {
        isLoadingAcademyPosts.value = true;
        const response = await api.get(`/api/academies/${props.academy.data.id}/activities`);
        if (response.success) {
            academyActivities.value = response.activities;

          // Dev-only logging

              isLoadingAcademyPosts.value = false;
        } else {
            isLoadingAcademyPosts.value = false;
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด! กรุณาลองใหม่อีกครั้ง',
                text: response.message,
            });
        }
        } catch (error) {
          // Dev-only logging
          isLoadingAcademyPosts.value = false;
        }
      };

      // const isDarkMode = ref(false);
      // const currentTabIndex = ref(0);


// Safe accessors for academy data
const academyData = computed(() => props.academy?.data || props.academy || {});

const tempLogo = computed(() => {
    const logo = academyData.value.logo;
    return logo ? '/storage/images/academies/logos/' + logo : '/storage/images/academies/logos/default_logo.png';
});

const tempCover = computed(() => {
    const cover = academyData.value.cover;
    return cover ? '/storage/images/academies/covers/' + cover : '/storage/images/academies/covers/default_cover.png';
});

const tempHeader = ref(academyData.value.name || '');
const tempSubheader = ref(academyData.value.slogan || '');

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
    await api.post(`/academies/${props.academy.data.id}/update`, academyCoverUpdate);
}

async function onLogoImageChange(logoFile) {
    const academyLogoUpdate = new FormData();
    academyLogoUpdate.append('logo', logoFile);
    academyLogoUpdate.append('_method', 'patch');
    await api.post(`/academies/${props.academy.data.id}/update`, academyLogoUpdate);
}

async function onHeaderChange(academyName) {
    await api.patch(`/academies/${props.academy.data.id}/update`, { name:academyName });
}

async function onSubheaderChange(academySlogan) {
    await api.patch(`/academies/${props.academy.data.id}/update`, { slogan:academySlogan });
}

    async function onRequestToBeAMember(){
      try {
        let memberResp = await api.post(`/academies/${props.academy.data.id}/members`);
        if (memberResp.success) {
          props.academy.data.memberStatus=memberResp.memberStatus;
          props.academy.data.total_students = memberResp.totalStudents;
          if (memberResp.success) {
            props.academy.data.memberStatus = memberResp.memberStatus;
            Swal.fire(
              'เสร็จสิ้น',
              'ขอเป็นสมาชิกเรียบร้อยแล้ว',
              'success'
            )
          }
        }else if(!memberResp.success) {
          Swal.fire(
            'เกิดข้อผิดพลาด',
                memberResp.msg ,
            'error'
          )
        }
      } catch (error) {
        // Dev-only logging
      }
}

    async function onRequestToBeUnmember(){
      try {
        let memberResp = await api.post(`/academies/${props.academy.data.id}/unmembers`);
        if (memberResp.success) {
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
        // Dev-only logging
      }
    }

</script>

<template>
    <MainLayout :title="academyData.name || 'Academy'">

        <template #coverProfileCard>
            <AcademyCoverProfile 
                        :coverImage="tempCover" 
                        :logoImage="tempLogo" 
                        v-model:coverHeader="tempHeader"
                        v-model:coverSubheader="tempSubheader"

                        :model="'Academy'"
                        :modelTable="'academies'"
                        :modelableId="academyData.id"
                        :modelableType="'app/models/Academy'"
                        :modelableRoute="`/academies/${academyData.id}`"
                        :modelData="academyData"
                        :subModelDataLength="academyData.courses_offered"
                        :subModelNameTh="'รายวิชา'"
                        :isAcademyAdmin="props.isAcademyAdmin"

                        @cover-image-change="(data) => { onCoverImageChange(data); }"
                        @logo-image-change="(data) => { onLogoImageChange(data); }"
                        @header-change="(data)=>{ onHeaderChange(data) }"
                        @subheader-change="(data)=>{ onSubheaderChange(data) }"
                        @request-tobe-member="onRequestToBeAMember"
                        @request-tobe-unmember="onRequestToBeUnmember"
                    ></AcademyCoverProfile>

                    <AcademyNavbarTab :academy="academy" :activeTab="props.activeTab" :isAcademyAdmin="props.isAcademyAdmin" />

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
