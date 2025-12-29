<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

const props = defineProps({
    advert: Object,
    index: Number,
});

const authStore = useAuthStore();

const refAdvert = ref(props.advert);
// const refExchangePoints = ref(props.advert.exchange_points);
const refExchangePoints = ref(120);

const emit = defineEmits(['getDonateRequest']);
const isProcessing = ref(false);

const isGetAdvertLoading = ref(false);
// const isGetDonateSuccess = ref(false);
const timeLeft = ref(null);
const advertRecieved = ref(0);

const advertResponse = ref();
const recieveAdvertActivity = ref();

const showAdvertProgress = computed(()=>{
    return timeLeft.value >= 0 && isGetAdvertLoading.value;
});


const handleViewAdvert = async () => {

    if (refAdvert.value.remaining_views < 1) {
        alertGetAdvertError('โฆษณานี้ถูกแสดงครบจำนวนแล้ว');
        return;
    }
    if (isProcessing.value) {
        alertGetAdvertError('โฆษณากำลังแสดงอยู่');
        return;
    }

    if(authStore.points < refExchangePoints.value){
        alertGetAdvertError('คุณมีแต้มใช้งานสะสมไม่เพียงพอ กรุณาสะสมแต้มเพิ่มเติมเพื่อดำเนินการต่อ');
        return;
    }

    isProcessing.value = true;

    isGetAdvertLoading.value = true;
    timeLeft.value = props.advert.duration * 1000;
    advertRecieved.value = 0;

    try {

        getAdvert();

        countdown();

    } catch (error) {
        isProcessing.value = false;
    }
};

const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms));
const countdown = () => {
    timeLeft.value -= 250;
    if (timeLeft.value > 0) {
        advertRecieved.value += 0.01;
        wait(250).then(countdown);
    } else {
        isGetAdvertLoading.value = false;
        handleGetAdvertSuccess();
    }    
}; 

const getAdvert = async () => {
    try {
        isProcessing.value = true;
        const response = await axios.post(`/supports/advertises/${props.advert.id}/view`);
        if (response.data.success) {
            advertResponse.value = response.data.advert;
        } else {
            advertResponse.value = response.data.advert;
            alertGetAdvertError(response.data.message);
        }
        isProcessing.value = false;
    } catch (error) {
        alertGetAdvertError(error.message);
    }
};

const handleGetAdvertSuccess = () => {

    authStore.deductPoints(refExchangePoints.value);
    // TODO: ต้องเพิ่ม wallet ใน authStore ด้วย
    if (authStore.user) {
        authStore.user.wallet = (authStore.user.wallet || 0) + 0.20;
    }
        
    timeLeft.value = null;
    advertRecieved.value = 0;
    isProcessing.value = false;
    // isGetAdvertLoading.value = false;
    refAdvert.value = advertResponse.value;
    
    alertGetAdvertSuccess();

    // Note: ไม่สามารถลบจาก props.advertises.data ได้เพราะไม่ได้ผ่าน usePage() มาแล้ว
    // ควร emit event ไปให้ parent component จัดการ
    // if (advertResponse.value.remaining_views < 1) {
    //     // Remove from list
    // }

};

const progressValue = computed(() => {
    return parseInt((advertRecieved.value * 500).toLocaleString());
});

const alertGetAdvertError = (msg) => {
    Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด!',
        text: msg,
    });
};

const alertGetAdvertSuccess = () => {
    Swal.fire({
        title: 'รับการสนับสนุนสำเร็จ',
        text: 'คุณได้รับการสนับสนุนเรียบร้อยแล้ว 0.20 บาท',
        icon: 'success',
        showConfirmButton: false,
        timer: 1200
    });
};

</script>

<template>
    <div class="flex flex-col justify-between h-full p-2 rounded ">
        <figure class="flex items-center p-0 mb-2 rounded-md">
            <div class="flex-shrink-0">
                <img class="w-12 h-12 rounded-full" :src="refAdvert.advertiser.avatar" :alt="refAdvert.advertiser.name + 'photo'">
            </div>
            <div class="w-full ps-3">
                <div class="flex flex-col mb-1 text-sm text-gray-700 dark:text-gray-400">
                    <span class="text-sm font-bold text-blue-500">{{ refAdvert.advertiser.name }}</span>
                    <span class="font-semibold">{{ refAdvert.advertiser.personal_code }}</span>
                </div>
                <a v-if="refAdvert.advertiser.no_of_ref < 5" :href="refAdvert.advertiser.referal_link"
                    class="px-3 py-1 text-sm text-white bg-teal-400 rounded-md center font-base">สมัครต่อ
                </a>
            </div>
        </figure>

        <p class="text-sm">ประชาสัมพันธ์</p>
        <figure class="flex items-center p-0 rounded-md">
            <img class="w-full rounded-md" :src="refAdvert.media_image" :alt="refAdvert.advertiser.name + 'photo'">
        </figure>
        <div class="w-full flex justify-center text-base font-bold tracking-tight text-blue-500 p-2 rounded-lg">
            <div class="">
                <span class="text-[24px] text-yellow-400">{{ refAdvert.total_views.toLocaleString() }}</span>
                <span class="text-[10px] text-green-500"> views</span>
            </div>
            <span class="mt-1">/</span>
            <div class="flex items-end">
                <span class="text-[16px] text-yellow-400 mt-1">
                    <span class="text-[10px] text-blue-500 mt-1 ml-1">ใช้ได้ </span>
                    {{ refAdvert.remaining_views }}
                </span>
                <span class="text-[10px] mt-1 ml-1"> views</span>
            </div>
        </div>
        <div class="flex flex-col gap-2 ">
            <button v-if="refAdvert.status === 0" disabled
                class="w-full py-2 mt-3 font-medium text-center text-white bg-orange-400 rounded-md">
                รออนุมัต
            </button>
            <button v-if="refAdvert.status === 1 && refAdvert.remaining_views>0" :disabled="isProcessing"
                class="w-full flex items-center justify-center py-2 mt-3 font-base text-center text-white rounded-md bg-sky-400"
                @click.prevent="handleViewAdvert">
                <Icon icon="hugeicons:bitcoin-03" class="mx-1 w-5 h-5" />
                แสดงโฆษณา
            </button>
        </div>
        
        <div v-show="showAdvertProgress" class="fixed top-0 left-0 z-40 pt-[40%] md:pt-0 flex items-center justify-center w-full h-full modal bg-gray-900/100 modal-overlay">
            <div class="relative w-[80%] h-screen z-30 mx-auto overflow-hidden">
                <div class="w-full bg-gray-200 rounded-lg dark:bg-gray-700">
                    <div class="bg-violet-600 text-xs font-medium text-blue-100 text-center p-0.5 leading-none rounded-lg z-30" :style="`width: ${progressValue}%`"> {{ advertRecieved.toLocaleString() }}บาท</div>
                </div>
                <img :src="refAdvert.media_image" alt="" srcset="" class="w-full bg-cover">
                <!-- <div class="absolute z-40 bottom-4 left-[40%] text-[60px] font-bold text-yellow-400 ">{{ advertRecieved.toLocaleString() }} บาท</div> -->
            </div>
        </div>
    </div>
</template>
