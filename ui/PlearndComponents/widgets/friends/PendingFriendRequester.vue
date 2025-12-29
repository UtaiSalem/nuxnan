<script setup>
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

const props = defineProps({
    friendship: Object,
});

const emit = defineEmits(['splice-friend-request']);

const isAccepting = ref(false);
const isDenying = ref(false);
const refSender = ref(props.friendship.sender);

const handleAcceptFriendRequest = async () => {
    try {
        isAccepting.value = true;
        const acceptedResp = await axios.patch(`/friends/${refSender.value.id}/accept`);
        if (acceptedResp.data.success) {
            Swal.fire({
                icon: 'success',
                title: 'เพิ่มเพื่อนสำเร็จ',
            });
            // refSender.value.is_friend = true;
            emit('splice-friend-request');
            isAccepting.value = false;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด! กรุณาลองใหม่อีกครั้ง',
            });
            isAccepting.value = false;
        }
    } catch (error) {
        isAccepting.value = false;
    }
};

// delete friend request
const handleDenyFriendRequest = async () => {
    try {
        isDenying.value = true;
        const response = await axios.post(`/friends/${refSender.value.id}/deny`);
        if (response.data.success) {
            Swal.fire({
                icon: 'success',
                title: 'ลบคำขอเพื่อนสำเร็จ',
            });
            emit('splice-friend-request');
            isDenying.value = false;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด! กรุณาลองใหม่อีกครั้ง',
            });
            isDenying.value = false;
        }
    } catch (error) {
        isDenying.value = false;
    }
};

</script>

<template>
    <div class="max-w-sm mx-auto space-y-2 sm:py-2 sm:flex sm:items-center sm:space-y-0 sm:space-x-2">
        <img class="block mx-auto w-12 h-12 border border-blue-500 rounded-full sm:mx-0 sm:shrink-0" :src="refSender.avatar"
            :alt="refSender.name">
        <div class="text-center space-y-2 sm:text-left">
            <div class="space-y-0.5 flex">
                <p class="text-sm text-black font-normal">
                    {{ refSender.name }}
                </p>
            </div>
            <div class="flex space-x-1.5">
                <button @click.prevent="handleAcceptFriendRequest"
                    class="px-3 py-1 text-sm text-purple-500 font-normal rounded-full  hover:text-white bg-blue-200 hover:bg-blue-600 hover:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2">
                    <div class="flex items-center justify-center">
                        <Icon v-if="isAccepting" icon="bi:arrow-repeat" class="inline-block w-4 h-4 mr-1.5 animate-spin" />
                        <Icon v-else icon="bi:person-plus-fill" class="inline-block w-4 h-4 mr-1.5" />
                        <span class="hidden xl:block">ตอบรับ</span>
                    </div>
                </button>
                <button @click.prevent="handleDenyFriendRequest"
                    class="px-3 py-1 text-sm text-purple-500 font-normal rounded-full  hover:text-white bg-red-200 hover:bg-red-500 hover:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2">
                    <div class="flex items-center justify-center">
                        <Icon v-if="isDenying" icon="bi:arrow-repeat" class="inline-block w-4 h-4 mr-1.5 animate-spin" />
                        <Icon v-else icon="fa6-solid:user-minus" class="inline-block w-4 h-4 mr-1.5" />
                        <span class="hidden xl:block">ปฏิเสธ</span>
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>
