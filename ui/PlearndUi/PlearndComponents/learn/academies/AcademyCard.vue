<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import SVGJoinGroup from '@/Components/SVGIcons/Join Group Icon.svg';
import SVGLeaveGroup from '@/Components/SVGIcons/Leave Group Icon.svg';
import SVGMembersIcon from '@/Components/SVGIcons/Members Icon.svg';
import SVGGoBackIcon from '@/Components/SVGIcons/Go Back Arrow Icon.svg';
import Swal from 'sweetalert2';

const props = defineProps({
    academy:Object
});

const authUser = usePage().props.auth.user;
async function onRequestToBeAMember(){
    try {
        let memberResp = await axios.post(`/academies/${props.academy.id}/members`);
        if (memberResp.data && memberResp.data.success) {
            // console.log(memberResp.data);
            props.academy.memberStatus = memberResp.data.memberStatus;
            if(memberResp.data.memberStatus === 1){props.academy.total_students++};
            Swal.fire(
                'เสร็จสมบูรณ์',
                    memberResp.data.memberStatus ===0 ? 'ขอเข้าร่วมเรียบร้อยแล้ว รอการตอบรับ':'เป็นสมาชิกเรียบร้อยแล้ว' ,
                'success'
            )
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

async function onRequestToBeUnmember(msg){
    try {
        Swal.fire({
            title: 'คุณแน่ใจหรือไม่ที่จะออกจากการเป็นสมาชิกของแหล่งเรียนรู้นี้?',
            showDenyButton: true,
            confirmButtonText: `ใช่`,
            denyButtonText: `ไม่`,
        }).then(async (result) => {
            if (result.isConfirmed) {
                // let memberResp = await axios.post(`/academies/${props.academy.id}/unmembers`);
                // if (memberResp.data && memberResp.data.success) {
                //     if (props.academy.memberStatus==1) {
                //         props.academy.total_students--;
                //     }
                //     props.academy.memberStatus = null;
                    Swal.fire(
                        'เสร็จสมบูรณ์',
                        msg ,
                        'warning'
                    )
            //     }
            }
        })
        // let memberResp = await axios.post(`/academies/${props.academy.id}/unmembers`);
        // if (memberResp.data && memberResp.data.success) {
        //     if (props.academy.memberStatus==1) {
        //         props.academy.total_students--;
        //     }
        //     props.academy.memberStatus = null;
        //     Swal.fire(
        //         'เสร็จสมบูรณ์',
        //         msg ,
        //         'warning'
        //     )
        // }
    } catch (error) {
        console.log(error);
    }
}

</script>

<template>
    <div>
        <div class="overflow-hidden bg-white rounded-lg shadow-xl">
            <figure class="bg-center bg-no-repeat bg-cover user-preview-cover liquid">
                <img :src="academy.cover? 'storage/images/academies/covers/'+academy.cover : 'storage/images/academies/covers/default_cover.png'" alt="cover-46">
            </figure>
            <div class="p-2 user-preview-info">
                <div class="flex flex-col justify-center">
                    <Link class="flex justify-center h-24 mb-2 -mt-12 " :href="'/academies/'+academy.name">
                    <div class="-mt-4">
                        <img :src="academy.logo? 'storage/images/academies/logos/'+academy.logo : 'storage/images/academies/logos/default_logo.png'"
                            alt="" class="border-2 rounded-full w-28 h-28">
                    </div>
                    </Link>
                    <p class="flex justify-center my-2 text-xl font-bold text-center">
                        <Link :href="'/academies/'+academy.name" class="font-prompt">{{ academy.name }}</Link>
                    </p>
                    <p class="flex justify-center w-full p-2 text-center">
                        <Link :href="'/academies/'+academy.name" class="">
                         <span>{{ academy.slogan }} </span> 
                        </Link>
                    </p>
                </div>

                <div class="my-1 ">
                    <div class="p-2">
                        <div class="flex flex-col items-center space-x-2">
                            <a class=" small" href="#">
                                <div class="user-avatar-content">
                                    <img :src="academy.director? academy.director.avatar : academy.creater.avatar" alt="" class="w-20 rounded-full">
                                </div>
                            </a>
                            <div class="flex flex-col items-center justify-center">
                                <!-- <p class="user-short-description-title"><a href="#">{{ academy.director? academy.director.name : academy.creater.name }}</a></p> -->
                                <p class="user-short-description-text">{{ academy.director? 'Director' : 'Creater' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-center">
                    <div class="flex flex-col items-center justify-center">
                        <p class="user-stat-title">{{ academy.total_students }}</p>
                        <p class="user-stat-text">สมาชิก</p>
                    </div>
                    <!-- /USER STAT -->

                    <!-- USER STAT -->
                    <!-- <div class="user-stat">
                        <p class="user-stat-title">101</p>
                        <p class="user-stat-text">posts</p>
                    </div> -->
                    <!-- /USER STAT -->

                    <!-- USER STAT -->
                    <!-- <div class="user-stat">
                        <p class="user-stat-title">2.4k</p>
                        <p class="user-stat-text">visits</p>
                    </div> -->
                    <!-- /USER STAT -->
                </div>
                <!-- /USER STATS -->

                <!-- USER PREVIEW ACTIONS -->
                <div class="user-preview-actions" v-if="$page.props.auth.user.id != props.academy.creater.id">
                <!-- <div class="user-preview-actions" > -->
                    <!-- BUTTON -->
                    <button v-if="academy.memberStatus == 0" class="flex justify-center plearnd-btn-primary"
                        @click.prevent="onRequestToBeUnmember('ยกเลิกคำขอเป็นสมาชิกเรียบร้อยแล้ว')">
                        <SVGLeaveGroup class="inline-flex mx-2 " />รอการตอบรับสมาชิก
                    </button>
                    <!-- <button v-else-if="academy.memberStatus == 1" class="flex items-center justify-center plearnd-btn-indigo" > -->
                        <!-- @click.prevent="onRequestToBeUnmember('ออกจากการเป็นสมาชิกเรียบร้อยแล้ว')"     -->
                        <!-- <SVGMembersIcon class="inline-flex mx-2 " />ไปยังหน้าแหล่งเรียนรู้ -->
                    <!-- </button> -->
                    <!-- <button v-else class="flex justify-center plearnd-btn-primary" @click.prevent="onRequestToBeAMember">
                        <SVGJoinGroup class="inline-flex mx-2 " />ขอเป็นสมาชิก
                    </button> -->

                </div>
                <div class="flex items-center justify-center w-full mt-2 h-12" >
                    <!-- @click.prevent="onRequestToBeUnmember('ออกจากการเป็นสมาชิกเรียบร้อยแล้ว')"     -->
                    <span>
                        ไปยังแหล่งเรียนรู้
                    </span>
                    <button class="bg-indigo-500 rounded-full m-2" >
                        <SVGGoBackIcon class="inline-flex m-3 rotate-180" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
