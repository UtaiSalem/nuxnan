<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

import MainLayout from '~/layouts/main.vue';

import DonationCard from '@/PlearndComponents/earn/donates/DonationCard.vue';

import LoadingPage from '@/PlearndComponents/accessories/LoadingPage.vue';

const props = defineProps({
    donates: Object,
});

const isCreateDonatePageLoading = ref(false);
const isGetDonateLoading = ref(false);

const timeLeft = ref();
const donateRecieved = ref(0);

const currentIndex = ref(0);
const currentDonateId = ref();
const donateResponse = ref();
const recieveDonateActivity = ref();

const showDonorProgress = computed(()=>{
    return timeLeft.value >= 0 && isGetDonateLoading.value;
});

const handleGetDonate = (donateId, idx) => {
    isGetDonateLoading.value = true;
    timeLeft.value = 5000;
    donateRecieved.value = 0;
    currentIndex.value = idx;
    currentDonateId.value = donateId; 
    try {

        getDonate(donateId);

        countdown();

    } catch (error) {
        // Handle error silently
    }
};

const getDonate = async (id) => {
    try {
        let getDonateResponse = await axios.get(`/donates/${id}/get-donate`);

        if (getDonateResponse.data.success) {
            donateResponse.value = getDonateResponse.data.donate;
            recieveDonateActivity.value = getDonateResponse.data.activity;            
        }
        else{
            alertGetDonateError();
        }
    } catch (error) {
        alertGetDonateError();
    }
};

const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms));

const countdown = () => {
    timeLeft.value -= 20.83;
    if (timeLeft.value > 20.83) {
        donateRecieved.value += 1;
        wait(20.84).then(countdown);
    } else {
        isGetDonateLoading.value = false;
        handleGetDonateSuccess();
    }    
};  

const handleGetDonateSuccess = () => {

        if (usePage().props.auth.user) { usePage().props.auth.user.pp += 240 };

        props.donates.data[currentIndex.value].remaining_points = donateResponse.value.remaining_points;

        if (props.donates.data[currentIndex.value].remaining_points < 240) {
            props.donates.data.splice(currentIndex.value, 1);
        }

        recieveDonateActivity.value = null;
        alertGetDonateSuccess();

};

const alertGetDonateSuccess = () => {
    Swal.fire({
        title: 'รับการสนับสนุนสำเร็จ',
        text: 'คุณได้รับการสนับสนุนเรียบร้อยแล้ว' + ' 240' + ' แต้ม',
        icon: 'success',
        showConfirmButton: false,
        timer: 1200
    });
};

const alertGetDonateError = () => {
    Swal.fire({
        title: 'เกิดข้อผิดพลาด',
        text: 'ไม่สามารถรับการสนับสนุนได้',
        icon: 'error',
        showConfirmButton: false,
        timer: 1200
    });
};

const handleLinkToPage = (href) => {
    isCreateDonatePageLoading.value = true;
    router.visit(href);
};


</script>

<template>
    <div>
        <MainLayout title="ทุนสนับสนุนการเรียนรู้">
            <template #coverProfileCard>
                <div class="hidden md:flex items-center mx-auto mt-2 mb-4 shadow-lg bg-[url('/storage/images/banner/banner-bg.png')] bg-cover bg-no-repeat rounded-lg">
                    <img class="section-banner-icon " :src="'/storage/images/banner/badges-icon.png'" alt="forums-icon">
                    <p class="text-white font-bold text-2xl">ทุนสนับสนุนการเรียนรู้</p>
                </div>
                <div class="">
                    <!-- <MobileNavbar /> -->
                </div>
            </template>

            <template #leftSideWidget>
                <!-- Donation List -->
                <!-- <DonationListWidget v-if="donates.data.length"
                    :donates
                    @add-new-donate-activity=""
                    @go-to-create-donate="handleLinkToPage('/supports/donates/create')"
                /> -->
            </template>

            <template #rightSidebar>
                <!-- <PendingFriendsWidget :pendingFriends="props.pendingFriends" /> -->
            </template>

            <template #mainContent>

                <LoadingPage v-if="isCreateDonatePageLoading" />

                <div class="mt-2 md:mt-0 mb-4 flex justify-center bg-white p-2 rounded-lg border-t-4 border-blue-500">
                    <button class="flex items-center justify-center max-w-fit px-4 py-2 my-1 font-medium text-center text-white rounded-md bg-green-400"
                        @click.prevent="handleLinkToPage('/supports/donates/create')">
                        <Icon icon="flat-color-icons:donate" class="w-7 h-7" />
                        <span>ให้การสนับสนุนทุนการเรียนรู้</span>
                    </button>
                </div>
                <template v-if="donates.data.length">
                    <div class=" grid md:grid-cols-2 gap-4">
                        <DonationCard 
                            v-for="(donate, index) in props.donates.data" 
                            :key="index"
                            :donate
                            :isProcessing="false"
                            @getDonateRequest="handleGetDonate(donate.id, index)"
                        />
                    </div>
                </template>  
                <template v-else>
                    <div class="flex justify-center items-center h-96">
                        <p class="text-2xl font-bold text-gray-400">No Donations</p>
                    </div>
                </template>
            </template>

        </MainLayout>

        <div v-if="props.donates.data[currentIndex].donor" :class="{'hidden' : !showDonorProgress}" class="fixed top-0 left-0 z-20 flex items-center justify-center w-full h-full modal bg-gray-900/100 modal-overlay">
            <div class="relative w-[40rem] h-[22rem] z-30 bg-white rounded-lg mx-auto overflow-hidden">
                <figure class="flex items-center p-2 mb-4 bg-gray-300 rounded-md">
                    <div class="flex-shrink-0">
                        <img class="w-40 h-40 rounded-full border-4 border-yellow-400"
                            :src="props.donates.data[currentIndex].donor.avatar"
                            :alt="props.donates.data[currentIndex].donor.name + 'photo'">
                    </div>
                    <div class="w-full ps-3">
                        <div class="flex flex-col mb-1 text-[40px] text-gray-700 dark:text-gray-400">
                            <span class="font-bold text-blue-500">{{ props.donates.data[currentIndex].donor.name }}</span>
                            <span class="font-semibold">{{ props.donates.data[currentIndex].donor.personal_code }}</span>
                        </div>
                    </div>
                </figure>
                <ProgressBar mode="indeterminate" style="height: 16px"></ProgressBar>
                <div class="w-full flex justify-center">
                    <p class="mt-4 text-[20px]">ให้การสนับสนุน แก่คุณ</p>
                </div>
                <div class="absolute top-[75%] left-[14rem] text-[60px] text-yellow-400/80 ">{{ donateRecieved}} แต้ม</div>
            </div>
        </div>

        <div v-show="!props.donates.data[currentIndex].donor" :class="{'hidden' : !showDonorProgress}" class="fixed top-0 left-0 z-20 flex items-center justify-center w-full h-full modal bg-gray-900/100 modal-overlay">
            <div class="relative w-[40rem] h-[39rem] z-30 bg-white rounded-lg mx-auto  overflow-hidden">
                <img :src="'/storage/images/donates/plearnd_poster.png'" alt="" srcset="" class="w-full">
                <ProgressBar mode="indeterminate" style="height: 16px"></ProgressBar>
                <div class="absolute top-[30%] left-[14rem] text-[60px] text-yellow-300/80 ">{{ donateRecieved}} แต้ม</div>
            </div>
        </div>
    </div>
</template>

