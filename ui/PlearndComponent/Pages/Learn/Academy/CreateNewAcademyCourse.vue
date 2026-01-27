<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

import MainLayout from '@/Layouts/MainLayout.vue';
import AcademyCoverProfile from '@/PlearndComponents/learn/academies/AcademyCoverProfile.vue';
import CreateNewAcademyCourse from '@/PlearndComponents/learn/academies/CreateNewAcademyCourse.vue';

const props = defineProps({
    academy: Object,
    courses: Object,
    isAcademyAdmin: Boolean,

});

const isDarkMode = ref(false);

const tempLogo = ref(props.academy.data.logo? '/storage/images/academies/logos/' + props.academy.data.logo:'/storage/images/academies/logos/default_logo.png');
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
    <MainLayout title="Academy" :appUrl="props.app_url" >
        <template #coverProfileCard>
            <div class="">
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
                    :subModelData="props.courses.data"
                    :subModelNameTh="'รายวิชา'"
                    :isAcademyAdmin="props.isAcademyAdmin"

                    @cover-image-change="(data) => { onCoverImageChange(data); }"
                    @logo-image-change="(data) => { onLogoImageChange(data); }"
                    @header-change="(data)=>{ onHeaderChange(data) }"
                    @subheader-change="(data)=>{ onSubheaderChange(data) }"
                    @request-tobe-member="onRequestToBeAMember"
                    @request-tobe-unmember="onRequestToBeUnmember"
                ></AcademyCoverProfile>
            </div>
            <div class=" bg-white shadow-xl w-full rounded-lg overflow-hidden mt-4">
                <div class="flex flex-row justify-around">
                    <Link :href="`/academies/${props.academy.data.id}`" class="border-b-4 hover:border-cyan-500 rounded-none w-full text-center flex-row justify-center"
                        :class="{'border-b-4 border-cyan-500': $page.url===`/academies/${props.academy.data.id}`}"
                    >
                        <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                            <Icon icon="bi:view-list" class="w-8 h-8" />
                            <span>กระดานข่าว</span>
                        </div>
                    </Link>
                    <Link :href="`/academies/${props.academy.data.id}/courses`" class="border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center ">
                        <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                            <Icon icon="lucide:layout-list" class="w-8 h-8" />
                            <span>รายวิชา</span>
                        </div>
                    </Link>
                    <Link :href="`/academies/${props.academy.data.id}/courses/create`"
                        class="border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center "
                        :class="{'border-b-4 border-cyan-500': $page.url===`/academies/${props.academy.data.id}/courses/create`}">
                        <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                            <Icon icon="lucide:layout-list" class="w-8 h-8" />
                            <span>เพิ่มรายวิชาใหม่</span>
                        </div>
                    </Link>
                </div>
            </div>
        </template>
        <template #leftSideWidget>
            <div>
                
            </div>
        </template>

        <template #mainContent>
            <div class="my-4">
                <CreateNewAcademyCourse :academy="props.academy" />
            </div>
        </template>

    </MainLayout>
</template>
