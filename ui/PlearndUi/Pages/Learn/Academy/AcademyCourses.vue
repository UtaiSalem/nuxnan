<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import MainLayout from '@/Layouts/MainLayout.vue';
import AcademyCoverProfile from '@/PlearndComponents/learn/academies/AcademyCoverProfile.vue';
import CourseCard from '@/PlearndComponents/learn/courses/CourseCard.vue';
import AcademyNavbarTab from '@/PlearndComponents/learn/academies/AcademyNavbarTab.vue';

const props = defineProps({
    academy: Object,
    courses: Object,
    isAcademyAdmin: Boolean,
    app_url: String,
});

const isDarkMode = ref(false);

const tempLogo = ref(props.academy.data.logo? '/storage/images/academies/logos/' + props.academy.data.logo:'/storage/images/academies/logos/default_logo.png');
// const tempLogo = ref(props.app_url + '/storage/images/academies/logos/' + props.academy.data.logo  || props.app_url + '/storage/images/academies/logos/default_logo.png');
// const tempCover = ref(props.app_url + '/storage/images/academies/covers/' + props.academy.data.cover || props.app_url + '/storage/images/academies/covers/default_cover.png');
const tempCover = ref(props.academy.data.cover? '/storage/images/academies/covers/' + props.academy.data.cover : '/storage/images/academies/covers/default_cover.png');
const tempHeader = ref(props.academy.data.name);
const tempSubheader = ref(props.academy.data.slogan);

const formAcademyUpdate = useForm({
    name:'', 
    slogan:'', 
    address:'', 
    cover:null, 
    logo:null,
});

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
        if (memberResp.data.success) {
            props.academy.data.isMember=memberResp.data.isMember;
            props.academy.data.total_students = memberResp.data.totalStudents;
            if (memberResp.data.isMember) {
                Swal.fire(
                    'เสร็จสิ้น',
                    'ขอเป็นสมาชิกเรียบร้อยแล้ว',
                    'success'
                )
            }else{
                Swal.fire(
                    'เสร็จสิ้น',
                    'ออกจากการเป็นสมาชิกเรียบร้อยแล้ว',
                    'success'
                )
            }
        }
    } catch (error) {
        console.log(error);
    }
}


</script>

<template>
    <MainLayout title="Academy" :appUrl="props.app_url">
        <template #coverProfileCard>
            <div class="">
                <AcademyCoverProfile :coverImage="tempCover" :logoImage="tempLogo" v-model:coverHeader="tempHeader"
                    v-model:coverSubheader="tempSubheader" :model="'Academy'" :modelTable="'academies'"
                    :modelableId="props.academy.data.id" :modelableType="'app/models/Academy'"
                    :modelableRoute="`/academies/${props.academy.data.id}`" :modelData="props.academy.data"
                    :subModelData="props.courses.data" :subModelNameTh="'รายวิชา'"
                    :isAcademyAdmin="props.isAcademyAdmin" @cover-image-change="(data) => { onCoverImageChange(data); }"
                    @logo-image-change="(data) => { onLogoImageChange(data); }"
                    @header-change="(data)=>{ onHeaderChange(data) }"
                    @subheader-change="(data)=>{ onSubheaderChange(data) }"
                    @request-tobe-member="onRequestToBeAMember()"></AcademyCoverProfile>

            </div>

            <AcademyNavbarTab :academy :activeTab="1" />
        </template>
        <template #leftSideWidget>
            <div>

            </div>
        </template>

        <template #mainContent>
            <div class="section-header my-4 p-4 bg-white rounded-xl shadow-lg">
                <div class="section-header-info">
                    <h2 class="section-title font-prompt">รายวิชาทั้งหมด
                        {{ ' ' + props.courses.data.length + ' วิชา' || 'ยังไม่มีรายวิชา' }}</h2>
                </div>
            </div>

            <div class="flex flex-wrap justify-between gap-4 ">
                <div v-for="(course, index) in props.courses.data" :key="index" class="w-full sm:w-[48%]">
                    <CourseCard :course="course" />
                </div>
            </div>
        </template>

    </MainLayout>
</template>
