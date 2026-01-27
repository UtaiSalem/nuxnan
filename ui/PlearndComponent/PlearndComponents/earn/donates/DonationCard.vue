<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';


const props = defineProps({
    donate: Object,
    isProcessing: Boolean,
});

const emit = defineEmits(['getDonateRequest']);

const isAddFriendRequestLoading = ref(false);
const isDeleteFriendRequestLoading = ref(false);
const refIsFriend = ref(props.donate.donor ? props.donate.donor.is_friend : false);
const refFriendStatus = ref(props.donate.donor ? props.donate.donor.friendships_status : null);
const pageProps = usePage().props;

const canBeFriend = computed(() => {
    return props.donate.donor ? (props.donate.donor.id !== pageProps.auth.user.id) && !props.donate.donor.is_friend : false; 
});

const canBeDownline = computed(() => {
    return props.donate.donor && props.donate.donor.no_of_ref <5;
});

const canGetPoints = computed(() => {
    return parseInt(props.donate.remaining_points) > 0;
});

const addFriend = async () => {
    try {
        isAddFriendRequestLoading.value = true;
        const response = await axios.post(`/friends/${props.donate.donor.id}`);
        if (response.data.success) {
            Swal.fire({
                icon: 'success',
                title: 'เพิ่มเพื่อนสำเร็จ',
            });
            refIsFriend.value= true;  
            refFriendStatus.value = 0; 
            isAddFriendRequestLoading.value = false;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด! กรุณาลองใหม่อีกครั้ง',
            });
            isAddFriendRequestLoading.value = false;
        }
    } catch (error) {
        console.log(error);
        isAddFriendRequestLoading.value = false;
    }
};

// delete friend request
const handleDeleteFriendRequest = async () => {
    try {
        isAddFriendRequestLoading.value = true;
        const response = await axios.delete(`/friends/${props.donate.donor.id}`);
        if (response.data.success) {
            Swal.fire({
                icon: 'success',
                title: 'ลบคำขอเพื่อนสำเร็จ',
            });
            refIsFriend.value = false;
            refFriendStatus.value = null;
            isAddFriendRequestLoading.value = false;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด! กรุณาลองใหม่อีกครั้ง',
            });
            isAddFriendRequestLoading.value = false;
        }
    } catch (error) {
        console.log(error);
        isAddFriendRequestLoading.value = false;
    }
};


</script>

<template>
  
  <div class="bg-white shadow-xl rounded-lg text-gray-900">
    <div class="rounded-t-lg h-32 overflow-hidden">
        <img class="object-cover object-top w-full" :src="donate.donor ? donate.donor.cover_image : '/storage/images/banner/banner-profile-stats.jpg'" alt='Mountain'>
    </div>
    <div class="m-auto w-32 h-32 relative -mt-16 border-4 border-white rounded-full overflow-hidden bg-white">
        <img class="object-cover object-center w-full h-full" :src="donate.donor ? donate.donor.avatar : '/storage/plearnd-logo.png'" alt='Woman looking front'>
    </div>
    <div class="text-center mt-2">
        <h2 class="font-semibold">{{ donate.donor_name }}</h2>
        <!-- <p class="text-gray-500">Freelance Web Designer</p> -->
    </div>

    <div class="m-2 p-2 bg-gray-200 rounded-lg">
            <!-- <p class="py-1 text-sm">ให้การสนับสนุน</p> -->
        <div class="w-full text-base font-bold tracking-tight text-blue-500">
            <!-- <span>
                {{ donate.amounts }} บาท
            </span> -->
            <div class="w-full flex justify-center space-x-1">
                <span class="text-[28px] text-yellow-400">{{ donate.total_points.toLocaleString() }}/</span>
                <span class="text-[16px] text-yellow-400 mt-1"><span class="text-[14px] text-blue-500 mt-1 ml-1">เหลือ </span>{{ donate.remaining_points }}</span>
                <span class="text-[14px] mt-1 ml-1"> แต้ม</span>
            </div>
        </div>

    </div>

    <hr class="mx-2" v-if="donate.donor " />
    
    <div class="flex items-center justify-center gap-2 px-2 my-2 " v-if="canBeFriend && canBeDownline">
        <!-- <button v-if="refFriendStatus===null" @click.prevnet="addFriend" 
            class="flex w-full items-center justify-center rounded-lg bg-blue-600 hover:bg-blue-800 text-sm text-white font-semibold dark:text-white antialiased px-4 py-2">
            <Icon icon="svg-spinners:6-dots-rotate" class="w-5 h-5 mx-1" v-if="isAddFriendRequestLoading"/>
            <Icon icon="heroicons:user-plus" class="w-5 h-5 mx-1" v-else/>
            <span>เพิ่มเพื่อน</span>
        </button> -->
        <button v-if="refFriendStatus===null" @click.prevnet="addFriend" 
            class="flex w-full items-center justify-center rounded-lg border border-purple-200 text-purple-600 text-sm font-base hover:text-white bg-purple-200 hover:bg-purple-600 hover:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 dark:text-white antialiased px-4 py-2">
            <Icon icon="svg-spinners:6-dots-rotate" class="w-5 h-5 mx-1" v-if="isAddFriendRequestLoading"/>
            <Icon icon="heroicons:user-plus" class="w-5 h-5 mx-1" v-else/>
            <span>เพิ่มเพื่อน</span>
        </button>
        <button v-if="refFriendStatus===0" @click.prevnet="handleDeleteFriendRequest" 
            class="flex w-full items-center justify-center rounded-lg bg-purple-500 hover:bg-purple-200 text-sm text-white hover:text-purple-600 font-semibold dark:text-white antialiased px-4 py-2">
            <Icon icon="svg-spinners:6-dots-rotate" class="w-5 h-5 mx-1" v-if="isAddFriendRequestLoading"/>
            <Icon icon="heroicons:user-minus" class="w-5 h-5 mx-1" v-else/>
            <span>ลบคำขอ</span>
        </button>

        <!-- <a v-if="donate.donor && donate.donor.no_of_ref < 5" :href="donate.donor ? donate.donor.referal_link : donate.user.referal_link" target="_blank"
            class="flex w-full items-center justify-center rounded-lg bg-teal-500 hover:bg-teal-600 text-sm text-white font-semibold dark:text-white antialiased px-4 py-2">
            สมัครต่อ
        </a> -->
    </div>

    <hr class="mx-2" v-if="canGetPoints"/>

    <div v-if="canGetPoints" class="flex flex-col gap-2 m-2 ">
        <button v-if="donate.status === 0" disabled
            class="w-full py-2 mt-1 font-medium text-center text-white bg-orange-400 rounded-md">
            รออนุมัต
        </button>
        <button v-if="donate.status === 1 && $page.props.auth.user" :disabled="isProcessing"
            class="w-full py-2 mt-1 font-medium text-center text-white rounded-md bg-sky-400"
            @click.prevent="emit('getDonateRequest')">
            รับการสนับสนุน
        </button>
    </div>
</div>

</template>
