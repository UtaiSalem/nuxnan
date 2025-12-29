<script setup>
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import TextInput from '@/components/TextInput.vue';
import MainLayout from '~/layouts/main.vue';
import Swal from 'sweetalert2';

const emit = defineEmits(['close-modal','add-new-academy']);

const isDarkMode = ref(false);
const coverInput =ref(null);
const logoInput =ref(null);
const tempCover = ref(null);
const tempLogo = ref(null);
const isLoading = ref(false);

const form = ref({
  name:'', 
  slogan:'', 
  address:'', 
  cover:null, 
  logo:null,
  autoAcceptMember: true,
  membershipFees: 0 
});

const browseCover = () => { coverInput.value.click() };
const browseLogo = () => { logoInput.value.click() };

function onCoverInputChange(event){
  form.value.cover = event.target.files[0];
  tempCover.value = URL.createObjectURL(event.target.files[0]);
}
function onLogoInputChange(event){
  form.value.logo = event.target.files[0];
  tempLogo.value = URL.createObjectURL(event.target.files[0]);
}

async function formSubmit(e) {
  e.preventDefault();
  
  isLoading.value = true;
  const config = { headers: { 'content-type': 'multipart/form-data' } }
  const data = new FormData();
  
  data.append('name', form.value.name);
  data.append('slogan', form.value.slogan);
  data.append('address', form.value.address);
  data.append('autoAcceptMember', form.value.autoAcceptMember);
  data.append('membershipFees', form.value.membershipFees);

  form.value.logo ? data.append('logo', form.value.logo) : null;
  form.value.cover ? data.append('cover', form.value.cover): null;

  try {
    let resp = await axios.post('/academies', data, config);
    if (resp.data && resp.data.success) {
        Swal.fire(
            'เสร็จสมบูรณ์',
            'เพิ่มแหล่งเรียนรู้ใหม่เรียบร้อยแล้ว',
            'success'
        )
        router.get(`/academies/${resp.data.academy}`)
    }else {
        Swal.fire(
            'เกิดข้อผิดพลาด',
            'แต้มสะสมไม่เพียงพอ ต้องมีแต้มสะสมเกิน 1,000,000 แต้ม',
            'error'
        )
    }
  } catch (error) {
    // Handle error silently
  }
  isLoading.value = false;
}

function handleCancle() {
    window.location = '/academies';
}
</script>

<template>
    <div>
        <MainLayout title="Create new Academy">

            <template #leftSideWidget>
                <div>
                </div>
            </template>

            <template #mainContent>

                <div class="bg-indigo-500 p-2  rounded-lg shadow-xl">
                    <div class="flex items-center justify-between w-full px-4 py-2 rounded-t-lg bg-indigo-500">
                        <h3 class="text-2xl text-white font-prompt">สร้างแหล่งเรียนรู้ใหม่</h3>
                        <Link href="/academies" class="w-10 p-3 rounded-full cursor-pointer hover:bg-indigo-400">
                            <!-- <Icon icon="heroicons:x-mark-20-solid" class="w-7 h-7" /> -->
                            <svg version="1.1" id="Go_Back_Arrow_Icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px"
                                    y="0px" width="20px" height="18px" viewBox="0 0 14 10" enable-background="new 0 0 14 10" xml:space="preserve">
                                <path fill-rule="evenodd" clip-rule="evenodd" fill="#FFFFFF" d="M14,6H3.364l2.644,2.75L4.806,10L1.202,6.25l0,0L0,5L4.806,0
                                    l1.201,1.25L3.364,4H14V6z"/>
                            </svg>
                        </Link>
                    </div>
                </div>
                
                <div class=" mt-4 rounded-lg shadow-xl">

                    <form id="create-new-academy-form" @submit.prevent="formSubmit"
                        class=" flex flex-col justify-center" enctype="multipart/form-data">
                        <div class="bg-white rounded-t-lg shadow-xl">
                            <div class="relative w-full h-[256px]">
                                <img :src="tempCover || '/storage/images/academies/covers/default_cover.png'" class="w-full h-full rounded-tl-lg rounded-tr-lg">
                                <div class="absolute top-2 right-2 flex flex-col">
                                    <input type="file" class="hidden" accept="image/*" ref="coverInput"
                                        @change="onCoverInputChange">
                                    <button type="button" @click.prevent="browseCover"
                                        class="text-white hover:bg-white hover:bg-opacity-50 hover:text-gray-600 transition duration-200 rounded-full p-2">
                                        <Icon icon="heroicons:camera" class="w-6 h-6" />
                                    </button>
                                </div>
                            </div>
                            <div class="relative flex flex-col items-center -mt-44">
                                <img class="w-40 h-40 border-4 border-white rounded-full"
                                    :src="tempLogo || '/storage/images/badge/level-badge.png'"
                                    alt='school logo'>
                                <div class="relative">
                                    <input type="file" class="hidden" accept="image/*" ref="logoInput"
                                        @change="onLogoInputChange">
                                    <button type="button" @click.prevent="browseLogo"
                                        class=" absolute bottom-1 -right-4 flex justify-center hover:bg-opacity-80 hover:text-gray-600 transition duration-200">
                                        <Icon icon="heroicons:camera" class="w-[28px] h-[28px] text-white bg-gray-600 rounded-full p-1 bg-opacity-70 hover:bg-opacity-90" />                                  
                                    </button>
                                </div>
                            </div>

                        </div>

                        <div class="bg-white rounded-b-lg mt-4">
                            <div class="space-y-2 p-4">
                                <TextInput id="school_name" type="text" class="mt-1 block w-full text-center"
                                    v-model="form.name" required autocomplete="school_name" placeholder="ชื่อแหล่งเรียนรู้" />

                                <TextInput id="slogan_name" type="text" class="mt-1 block w-full text-center"
                                    v-model="form.slogan" required autocomplete="slogan_name" placeholder="คำขวัญ, ปณิธาน" />
                                <textarea
                                    class="w-full py-3 border border-slate-200 rounded-lg px-3 focus:outline-none focus:border-slate-500 hover:shadow dark:bg-gray-600 dark:text-gray-100"
                                    name="bio" placeholder="สถานที่ตั้ง" v-model="form.address">
                                </textarea>
                            </div>

                        <!-- Component: Primary basic checkbox -->
                        <div class="relative flex flex-wrap items-center ml-4 -mt-2 mb-2">
                            <input class="w-4 h-4 transition-colors bg-white border-2 rounded appearance-none cursor-pointer focus-visible:outline-none peer border-slate-500 checked:border-violet-500 checked:bg-violet-500 checked:hover:border-violet-600 checked:hover:bg-violet-600 focus:outline-none checked:focus:border-violet-700 checked:focus:bg-violet-700 disabled:cursor-not-allowed disabled:border-slate-100 disabled:bg-slate-50" 
                            type="checkbox" id="id-c01" v-model="form.autoAcceptMember" :checked="form.autoAcceptMember" />
                            <label class="pl-2 cursor-pointer text-slate-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400" for="id-c01">
                                ตอบรับคำขอเป็นสมาชิกอัตโนมัติ
                            </label>
                        </div>

                        <!-- Component: Rounded large input with leading icon -->
                        <div class="relative m-4 w-1/2">
                            <input id="id-l11" type="number" v-model="form.membershipFees" placeholder="" class="relative w-full h-12 px-4 pl-12 placeholder-transparent transition-all border rounded outline-none focus-visible:outline-none peer border-slate-200 text-slate-500 autofill:bg-white invalid:border-pink-500 invalid:text-pink-500 focus:border-violet-500 focus:outline-none invalid:focus:border-pink-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400" />
                            <label for="id-l11" class="cursor-text peer-focus:cursor-default peer-autofill:-top-2 absolute left-2 -top-2 z-[1] px-2 text-xs text-slate-400 transition-all before:absolute before:top-0 before:left-0 before:z-[-1] before:block before:h-full before:w-full before:bg-white before:transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:left-10 peer-placeholder-shown:text-sm peer-required:after:text-pink-500 peer-required:after:content-['\00a0*'] peer-invalid:text-pink-500 peer-focus:-top-2 peer-focus:left-2 peer-focus:text-xs peer-focus:text-violet-500 peer-invalid:peer-focus:text-pink-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400 peer-disabled:before:bg-transparent">
                                ค่าธรรมเนียมสมัครสมาชิก
                            </label>
                            <Icon icon="noto:coin" class="absolute w-6 h-6 top-3 left-4 stroke-slate-400 peer-disabled:cursor-not-allowed" />
                            <small class="absolute flex justify-between w-full px-4 py-1 text-xs transition text-slate-400 peer-invalid:text-pink-500">
                                <span>คะแนนที่ต้องใช้เมื่อผู้อื่นต้องการเข้าร่วมสมาชิก</span>
                                <!-- <span class="text-slate-500">1/10</span> -->
                            </small>
                        </div>
                        <!-- End Rounded large input with leading icon -->

                        <!-- End Primary basic checkbox -->
                            <div class="flex items-center justify-end w-full p-4">
                                <div class="flex space-x-4">
                                    <button type="submit" :disabled="isLoading"
                                        class="inline-flex items-center justify-center h-10 gap-2 px-5 text-sm font-medium tracking-wide text-white transition duration-300 rounded-full focus-visible:outline-none whitespace-nowrap bg-violet-500 hover:bg-violet-600 focus:bg-violet-700 disabled:cursor-not-allowed disabled:border-violet-300 disabled:bg-violet-300 disabled:shadow-none">
                                        <span>บันทึก</span>
                                        <Icon icon="heroicons-outline:plus-circle" class="w-7 h-7" />
                                    </button>
                                    <button type="button" @click.prevent="handleCancle"
                                        class="px-6 py-2 bg-slate-200 hover:bg-red-300 hover:text-gray-800 border rounded-full">
                                        ยกเลิก
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

            </template>

            <template #rightSideWidget>
                <div>
                   
                </div>
            </template>
        </MainLayout>
    </div>
</template>

