<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { Link, Head, useForm } from '@inertiajs/vue3';
import MainLayout from '~/layouts/main.vue';
import Navbar from '@/PlearndComponents/Navbar.vue';
import ProfileCover from '@/PlearndComponents/partials/ProfileCover.vue';
// import PostViewer from '@/HopeuiComponents/partials/PostViewer.vue';
// import PollViewer from '@/HopeuiComponents/partials/PollViewer.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    academy: Object,
    courses: Object,
    imagePath: String,
});

const isDarkMode = ref(false);

const tempLogo = ref(props.imagePath + 'storage/images/' + props.academy.data.logo  || props.imagePath + 'storage/images/default_logo.png');
const tempCover = ref(props.imagePath + 'storage/images/' + props.academy.data.cover || props.imagePath + 'storage/images/default_cover.png');
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
    <div>
        <MainLayout>
            <template #header>
                <Head title="Academy"></Head>
            </template>
            <template #navbar>
                <Navbar :isDarkMode="isDarkMode" @toggle-dark-mode="isDarkMode = !isDarkMode" />
            </template>
            <template #coverProfileCard>
                <ProfileCover 
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
                    :subModelData="props.courses.data"
                    :subModelNameTh="'รายวิชา'"

                    @cover-image-change="(data) => { onCoverImageChange(data); }"
                    @logo-image-change="(data) => { onLogoImageChange(data); }"
                    @header-change="(data)=>{ onHeaderChange(data) }"
                    @subheader-change="(data)=>{ onSubheaderChange(data) }"
                    @request-tobe-member="onRequestToBeAMember()"
                ></ProfileCover>
            </template>


            <template #leftSideWidget>
                <div>
                    
                </div>
            </template>

            <template #mainContent>
                <div class="bg-white rounded-lg">
                    <div v-for="course in props.courses.data" :key="course.id" class="my-2">
                        <Link :href="`/courses/${course.id}`">{{ course.name }}</Link>                       
                    </div>
                </div>
            </template>

            <template #rightSideWidget>
                <div>
                   
                </div>
            </template>

        </MainLayout>

    </div>
</template>


