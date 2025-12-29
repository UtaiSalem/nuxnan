<script setup>
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

const props = defineProps({
    person: Object,
});

const isLoading = ref(false);
const refPerson = ref(props.person);

const addFriend = async () => {
    try {
        isLoading.value = true;
        const response = await axios.post(`/friends/${props.person.id}`);
        if (response.data.success) {
            // console.log(response.data.user);
            Swal.fire({
                icon: 'success',
                title: 'เพิ่มเพื่อนสำเร็จ',
                // text: response.data.message,
            });
            refPerson.value.is_friend = true;
            isLoading.value = false;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด! กรุณาลองใหม่อีกครั้ง',
                // text: response.data.message,
            });
            isLoading.value = false;
        }
    } catch (error) {
        isLoading.value = false;
    }
};

// delete friend request
const handleDeleteFriendRequest = async () => {
    try {
        isLoading.value = true;
        const response = await axios.delete(`/friends/${props.person.id}`);
        if (response.data.success) {
            Swal.fire({
                icon: 'success',
                title: 'ลบคำขอเพื่อนสำเร็จ',
                // text: response.data.message,
            });
            refPerson.value.is_friend = false;
            isLoading.value = false;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด! กรุณาลองใหม่อีกครั้ง',
                // text: response.data.message,
            });
            isLoading.value = false;
        }
    } catch (error) {
        isLoading.value = false;
    }
};

</script>

<template>
    <div class="max-w-sm mx-auto space-y-2 sm:py-2 sm:flex sm:items-center sm:space-y-0 sm:space-x-2">
        <img class="block mx-auto w-12 h-12 border border-blue-500 rounded-full sm:mx-0 sm:shrink-0" :src="refPerson.avatar"
            :alt="refPerson.name">
        <div class="text-center space-y-2 sm:text-left">
            <div class="space-y-0.5">
                <p class="text-sm text-black font-normal">
                    {{ refPerson.name }}
                </p>
            </div>
            <button @click.prevent="handleDeleteFriendRequest" v-if="refPerson.is_friend"
                class="px-3 py-1 text-sm text-purple-600 font-normal rounded-full border border-purple-200 hover:text-white bg-purple-200 hover:bg-purple-600 hover:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2">
                <div class="flex items-center justify-center">
                    <Icon v-if="isLoading" icon="bi:arrow-repeat" class="inline-block w-4 h-4 mr-1.5 animate-spin" />
                    <Icon v-else icon="fa6-solid:user-minus" class="inline-block w-4 h-4 mr-1.5" />
                    <span class="hidden xl:block">ลบคำขอ</span>
                </div>
            </button>
            <button @click.prevent="addFriend" v-else
                class="px-3 py-1 text-sm text-purple-600 font-normal rounded-full border border-purple-200 hover:text-white bg-purple-200 hover:bg-purple-600 hover:border-transparent focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2">
                <div class="flex items-center justify-center">
                    <Icon v-if="isLoading" icon="bi:arrow-repeat" class="inline-block w-4 h-4 mr-1.5 animate-spin" />
                    <Icon v-else icon="bi:person-plus-fill" class="inline-block w-4 h-4 mr-1.5" />
                    <!-- <span class="hidden xl:block" v-if="refPerson.is_friend">รอตอบรับ</span> -->
                    <span class="hidden xl:block">เพิ่มเพื่อน</span>
                </div>
            </button>
        </div>
    </div>  
</template>
