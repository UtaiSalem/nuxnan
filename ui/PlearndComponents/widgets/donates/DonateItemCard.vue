<script setup>
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
    donate: Object,
    isProcessing: Boolean,
});

const emit = defineEmits(['getDonateRequest']);
const authStore = useAuthStore();
const isLoggedin = ref(authStore.user);

</script>

<template>
  
    <div class="flex flex-col justify-between h-full p-2 rounded ">
        <figure class="flex items-center p-2 mb-4 bg-gray-300 rounded-md">
            <div class="flex-shrink-0">
                <img class="w-10 h-10 rounded-full"
                    :src="donate.donor ? donate.donor.avatar : '/storage/plearnd-logo.png'"
                    :alt="donate.donor ? donate.donor.name + 'photo':'donor-photo'">
            </div>
            <div class="w-full ps-3">
                <div class="flex flex-col mb-1 text-sm text-gray-700 dark:text-gray-400">
                    <span class="text-sm font-bold text-blue-500">{{ donate.donor ? donate.donor.name :
                    'ไม่ประสงค์ออกนาม' }}</span>
                    <span class="font-semibold">{{ donate.donor ? donate.donor.personal_code : '' }}</span>
                </div>
                <!-- <div class="text-base text-blue-600 dark:text-blue-500">{{ donate.donor ? donate.donor.referal_link:'' }}</div> -->
                <a v-if="donate.donor && donate.donor.no_of_ref < 5" :href="donate.donor.referal_link"
                    class="px-3 py-1 text-sm text-white bg-teal-400 rounded-md center font-base">สมัครต่อ
                </a>
            </div>
        </figure>
        <div>
            <p class="text-sm">ให้การสนับสนุน</p>
            <div class="w-full flex justify-center text-base font-bold tracking-tight text-blue-500 p-2 rounded-lg">
                <!-- <span>
                    {{ donate.amounts }} บาท
                </span>    -->
                <div class="">
                    <span class="text-[28px] text-yellow-400">{{ donate.total_points.toLocaleString() }}/</span>
                    <span class="text-[16px] text-yellow-400 mt-1"><span class="text-[14px] text-blue-500 mt-1 ml-1">คงเหลือ </span>{{ donate.remaining_points }}</span>
                    <span class="text-[14px] mt-1 ml-1"> แต้ม</span>
                </div>
            </div>
            <div class="flex flex-col gap-2 ">
                <button v-if="donate.status === 0" disabled
                    class="w-full py-2 mt-3 font-medium text-center text-white bg-orange-400 rounded-md">
                    รออนุมัต
                </button>
                <button v-if="isLoggedin && donate.status === 1 && parseInt(donate.remaining_points)>0" :disabled="isProcessing"
                    class="w-full py-2 mt-3 font-medium text-center text-white rounded-md bg-sky-400"
                    @click.prevent="emit('getDonateRequest')">
                    รับการสนับสนุน
                </button>
            </div>
        </div>
    </div>

</template>
