<script setup>
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import DotsDropdownMenu from '@/PlearndComponents/accessories/DotsDropdownMenu.vue';

const props = defineProps({
    user: Object
});

const userPoints = ref(props.user.points);
const moneyQnt = ref(null);
const isLoading = ref(false);

async function handleResetButtonClicked() {
    try {
        if (userPoints.value<4800) {
            Swal.fire('แต้มสะสมไม่เพียงพอ','ต้องใช้แต้ม 4800 แต้ม กรุณาเพิ่มแต้มสะสม.','warning' );
        }else{
            let resetResp = await axios.post('/forgot-password/reset/' + props.user.id);
            if(resetResp.data.success){
                userPoints.value -= 4800;
                Swal.fire("เสร็จสมบูรณ์" , resetResp.data.message, 'success' );
            }else{
                Swal.fire("เกิดข้อผิดพลาด", resetResp.data.message, 'warning' );
            }
        }
    } catch (error) {
        // Handle error silently
    }
}

async function handleExchangeButtonClick() {
    if (moneyQnt.value && moneyQnt.value>0) {
        let excResp = await axios.post('/forgot-password/exchange/' + props.user.id, { money: moneyQnt.value });
        if (excResp.data.success) {
            userPoints.value += moneyQnt.value*1080;
            moneyQnt.value=null;
            Swal.fire("เสร็จสมบูรณ์" , excResp.data.message, 'success' );
        }else{
            Swal.fire("เกิดข้อผิดพลาด", excResp.data.message, 'warning' );
        }
    }else{
        Swal.fire("เกิดข้อผิดพลาด", 'จำนวนเงินน้อยเกินไป', 'warning' );
    }
}

const handleDeleteModel = () => {
    Swal.fire({
        title: 'ยืนยันการลบบัญชีผู้ใช้',
        text: "คุณต้องการลบบัญชีผู้ใช้นี้หรือไม่?",
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ยืนยันการลบ!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            // isLoading.value = true;
            // confirmDeleteUser();
        }
    });
}

async function confirmDeleteUser() {
    try {
        let delResp = await axios.delete(`/forgot-password/users/${props.user.id}`);
        if (delResp.data.success) {
            Swal.fire("เสร็จสมบูรณ์" , delResp.data.message, 'success' );
            location.reload();
        }else{
            Swal.fire("เกิดข้อผิดพลาด", delResp.data.message, 'warning' );
        }
    } catch (error) {
        // Handle error silently
    }
}
</script>

<template>
    <div class="relative max-w-2xl text-gray-900 bg-white rounded-lg shadow-xl sm:max-w-sm md:max-w-sm lg:max-w-sm xl:max-w-sm sm:mx-auto md:mx-auto lg:mx-auto xl:mx-auto">
        <div class="absolute top-0 right-0 m-2 ">
            <DotsDropdownMenu @delete-model="handleDeleteModel">
                <template #deleteModel>
                    <div>
                        ลบบัญชีผู้ใช้
                    </div>
                </template>
            </DotsDropdownMenu>
        </div>
        <div class="h-32 overflow-hidden rounded-t-lg">
            <img class="object-cover object-top w-full"
                src='https://images.unsplash.com/photo-1549880338-65ddcdfd017b?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=400&fit=max&ixid=eyJhcHBfaWQiOjE0NTg5fQ'
                alt='Mountain'>
        </div>
        <div class="relative w-24 h-24 mx-auto -mt-12 overflow-hidden border-4 border-white rounded-full">
            <img class="object-cover object-center h-24"
                :src="user.avatar ?? 'https://cdn.pixabay.com/photo/2016/08/08/09/17/avatar-1577909_1280.png'"
                alt='Woman looking front'>
        </div>
        <div class="mt-2 text-center">
            <h2 class="font-semibold">{{ props.user.name }}</h2>
            <p class="text-gray-500">{{ props.user.email }}</p>
            <p class="text-gray-500">{{ props.user.personal_code }}</p>
        </div>
        <ul class="flex items-center justify-around px-2 my-2 text-gray-700">
            <li class="flex flex-col items-center justify-between">
                <Icon icon="la:id-card-solid" />
                <div>{{ props.user.id }}</div>
            </li>
            <li class="flex flex-col items-center justify-between">
                <Icon icon="noto:coin" />
                <div>{{ userPoints }}</div>
            </li>
        </ul>
        <!-- <div class="p-4 mx-8 mt-2 border-t" v-if="userPoints >= 4800"> -->
        
        <div class="p-2 border-t">
            <div class="flex items-center justify-center ">
                <div class="w-full">
                    <form :id="props.user.id+'exchange-money-form'" class="space-x-2 sm:flex sm:items-center text-end">
                        <input :id="props.user.id+'exchange-money-input'" type="number" min="0" v-model="moneyQnt" placeholder="จำนวนเงิน" 
                        class="inline w-full leading-5 placeholder-gray-500 bg-white border border-gray-400 rounded-md md:w-3/4 focus:border-indigo-500 focus:placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm">
                        <button type="submit" 
                            class="inline-flex items-center justify-center w-32 font-medium text-white border border-transparent rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            :class="moneyQnt ? 'bg-cyan-600 hover:bg-indigo-700 cursor-default':'bg-gray-500 cursor-not-allowed'"
                            :disabled="!moneyQnt || moneyQnt<=0" @click.prevent="handleExchangeButtonClick"
                        >
                            <Icon icon="circum:coin-insert" class="w-8 h-8" />
                            <span class="mx-1">เติม</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div v-if="userPoints > 3799" class="px-4 py-2 border-t">
            <button 
                type="button" 
                @click.prevent="handleResetButtonClicked"
                class="block w-full py-2 mx-auto text-xs font-light text-white bg-blue-600 rounded-full hover:bg-red-600 hover:scale-105 hover:shadow-lg">
                รีเซ็ตรหัสผ่าน
            </button>
        </div>

    </div>
</template>

