<script setup>
import { ref } from 'vue';
import { useDebounceFn } from "@vueuse/core"

import MainLayout from '~/layouts/main.vue';
import PWDUserItem from '@/pages/Admin/passwordreset/PWDUserItem.vue'

const searchEmail = ref('');
const usersResult = ref([]);

const handleSearchFormSubmit = useDebounceFn( async ()=> {
    try {
        usersResult.value.splice(0);
        if (searchEmail.value.trim().length) {
            const userResp = await axios.post('/forgot-password/getuser', { email: searchEmail.value.trim().toString() });
            if (userResp.data) {
                usersResult.value = userResp.data.users;
            }
        }else{
            searchEmail.value = ''; 
            usersResult.value.splice(0); 
        }
    } catch (error) {
        console.log(error);
    }
}, 480);

</script>

<template>
    <div>
        <MainLayout title="Reset password">
            <template #mainContent>
                <div class="mt-4">
                    <div class="bg-white border rounded-lg px-4 py-3 mx-auto my-4 max-w-full">
                        <h2 class="text-2xl font-medium mb-4">รีเซ็ตรหัสผ่าน</h2>
                        <div class="flex items-center justify-center ">
                            <div class="w-full">
                                <form class="sm:flex sm:items-center text-end space-x-2">
                                    <!-- <input id="q" v-model="searchEmail" @change="handleSearchFormSubmit" class="inline w-full md:w-3/4 rounded-md border border-gray-400 bg-white py-2 pl-3 pr-3 leading-5 placeholder-gray-500 focus:border-indigo-500 focus:placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm" placeholder="อีเมลผู้ใช้" type="search"> -->
                                    <input id="q" v-model="searchEmail" @input="handleSearchFormSubmit" 
                                    class="inline w-full rounded-md border border-gray-400 bg-white py-2 pl-3 pr-3 leading-5 placeholder-gray-500 focus:border-indigo-500 focus:placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:text-sm" 
                                    placeholder="ค้นหาด้วย อีเมล หรือ ชื่อ ผู้ใช้" 
                                    type="search">
                                    <!-- <button type="button" @click.prevent="() => { searchEmail=''; userResult = []; userResultCount=null;}" class="mt-3 inline-flex w-32 items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">ล้างข้อมูล</button> -->
                                </form>
                            </div>
                        </div>
                    </div>

                    <div v-if="usersResult.length>0">
                        <div class="max-w-screen-xl mx-auto ">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div v-for="(user, index) in usersResult" :key="index" >
                                    <PWDUserItem :user="user" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="bg-white border rounded-lg px-4 py-3 mx-auto my-4 max-w-full">ไม่มีข้อมูล</div>
                </div>
            </template>
        </MainLayout>
    </div>
</template>


