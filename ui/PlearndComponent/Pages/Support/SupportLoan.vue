<script setup>
import { ref, onMounted } from 'vue';
import { Icon } from '@iconify/vue';
import { useObjectUrl } from '@vueuse/core'
import { Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import Swal from 'sweetalert2';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';


const isDarkMode = ref(false);
const isLoading = ref(false);

const totalMoneySupport = ref(20);
const timeToShowProductMedia = ref(1);
const moneyIndexSelected = ref(0);
const timeIndexSelected = ref(0);
const quantityToShowProductMedia = ref(0);
const bonus = ref(0);
const slipImage = ref(null);
const mediaImage = ref(null);
const transferDate = ref(new Date());
const transferTime = ref(null);
const inputSlip = ref(null);
const inputMediaImage = ref(null);
const draging = ref(false);

const generateOptions = (min, max, step) => {
    const options = [];
    for (let i = min; i <= max; i += step) {
        options.push(i);
    }
    return options;
}

const browseInputSlip = () => { inputSlip.value.click() };
const browseInputMedia = () => { inputMediaImage.value.click() };
function onInputSlipChange(e) {
    slipImage.value = {
        file: e.target.files[0],
        url: URL.createObjectURL(e.target.files[0]),
    };
}
function onInputMediaChange(e) {
    mediaImage.value = {
        file: e.target.files[0],
        url: URL.createObjectURL(e.target.files[0]),
    };
}
const totalMoneySupportOptions = ref(generateOptions(20, 100, 10));
const timeToShowProductMediaOptions = ref(generateOptions(1, 15, 1));

function computeQuantityToShowProductMedia(mIdx=0,tIdx=0){
    totalMoneySupport.value = totalMoneySupportOptions.value[mIdx];
    timeToShowProductMedia.value = timeToShowProductMediaOptions.value[tIdx];
    quantityToShowProductMedia.value = Math.floor(((totalMoneySupport.value)*684)/(timeToShowProductMedia.value*64));
    bonus.value = totalMoneySupport.value >= 50 && timeToShowProductMedia.value >= 5 ? quantityToShowProductMedia.value + timeToShowProductMedia.value : quantityToShowProductMedia.value;
}

function onDropFile(e){
    // let file = e.target.files[0];
    let files = [...e.dataTransfer.items]
        .filter( item => item.kind ==='file' )
        .map(item => item.getAsFile());

    slipImage.value = {
        file: files[0],
        url: useObjectUrl(files[0]),
    };

}
function onDropMediaFile(e){
    // let file = e.target.files[0];
    let files = [...e.dataTransfer.items]
        .filter( item => item.kind ==='file' )
        .map(item => item.getAsFile());
    mediaImage.value = {
        file: files[0],
        url: useObjectUrl(files[0]),
    };
}

onMounted(() => {computeQuantityToShowProductMedia()})

async function submitForm(e) {
  e.preventDefault();

  isLoading.value = true;
  const config = { headers: { 'content-type': 'multipart/form-data' } }
  const supportData = new FormData();

  supportData.append('amounts', totalMoneySupport.value);
  supportData.append('duration', timeToShowProductMedia.value);
  supportData.append('total_views', bonus.value);
  supportData.append('transfer_date', transferDate.value.toLocaleDateString());
  supportData.append('transfer_time', transferTime.value['hours'] + ':' + transferTime.value['minutes']);

  supportData.append('slip', slipImage.value.file);
  supportData.append('media_image', mediaImage.value.file);

  try {
    let supportResp = await axios.post('/supports', supportData, config);
    if (supportResp.data && supportResp.data.success) {
        Swal.fire(
            'เสร็จสมบูรณ์',
            'เพิ่มแหล่งเรียนรู้ใหม่เรียบร้อยแล้ว',
            'success'
        )
        // router.get(`/academies/${resp.data.academy}`)
    }else {
        Swal.fire(
            'เกิดข้อผิดพลาด',
            supportResp.data.message,
            'error'
        )
    }
  } catch (error) {
    console.log(error);
    Swal.fire(
            'เกิดข้อผิดพลาด',
            error.response.data.message,
            'error'
        )
  }
  isLoading.value = false;
}


function deleteImage(){ slipImage.value = null;}
function deleteMediaImage(){ mediaImage.value = null;}
function handleCancle() { window.location = '/';}

</script>

<template>
    <div>
        <MainLayout title="Support Plearnd.com">
            <template #coverProfileCard>
                <div class="hidden md:flex items-center max-w-7xl mx-auto jusb mt-2 shadow-lg bg-[url('/storage/images/banner/banner-bg.png')] bg-cover bg-no-repeat rounded-lg overflow-hidden">
                    <img class="" :src="'/storage/images/banner/badges-icon.png'" alt="forums-icon">
                    <p class="text-white font-bold text-xl">สนับสนุน เว็บไซต์ เพลินด์</p>
                </div>
            </template>
            <template #leftSideWidget>
                <div>
                </div>
            </template>

            <template #mainContent>
                <div class="plearnd-card mt-4">
                    <h2 class="text-xl font-semibold text-gray-700 dark:text-white mb-2">
                        ข้อมูลการสนับสนุน
                    </h2>

                    <form id="create-new-academy-form" @submit.prevent="submitForm"
                        class=" flex flex-col justify-center" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 gap-4 mt-4">
                            <div>
                                <label for="money-quantity" class="block text-gray-700 dark:text-white mb-1">
                                    จำนวนเงิน
                                </label>
                                <select id="money-quantity" v-model="moneyIndexSelected"
                                    @change.prevent="computeQuantityToShowProductMedia(moneyIndexSelected, timeIndexSelected)"
                                    class="w-full text-center rounded-lg border border-gray-400 py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none">

                                    <option v-for="(moneyOption, midx) in totalMoneySupportOptions" :key="midx" :value="midx"
                                        :selected="moneyOption.value == 20">
                                        {{ moneyOption }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 mt-4">
                            <div>
                                <label for="time-to-show-Product-media-dropdown" class="block text-gray-700 dark:text-white mb-1 w-3/4">ระยะเวลาแสดงผลสื่อประชาสัมพันธ์สินค้า(วินาที/ครั้ง)</label>
                                <select id="time-to-show-Product-media-dropdown" v-model="timeIndexSelected" @change.prevent="computeQuantityToShowProductMedia(0, timeIndexSelected)"
                                    class="w-full text-center rounded-lg border border-gray-400 py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none">
                                    <!-- <option value="5" selected>5</option> -->
                                    <option v-for="(timeOption, tidx) in timeToShowProductMediaOptions" :key="tidx" :value="tidx" :selected="timeOption.value==1">
                                        {{ timeOption }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 mt-4">
                            <div>
                                <p class="block text-gray-700 dark:text-white mb-1">จำนวนครั้งที่ผู้ใช้สามารถกดรับและแสดงผลสื่อประชาสัมพันธ์สินค้า</p>
                                <p  class="w-full rounded-lg border border-gray-400 bg-gray-300 text-center py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none">
                                    <!-- {{ quantityToShowProductMedia }}  (+{{ timeToShowProductMedia }} ) -->
                                    {{ bonus }}
                                </p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 mt-4">
                            <div v-if="slipImage">
                                <div class="relative mb-2 max-h-fit overflow-hidden">
                                    <img :src="slipImage.url" class="rounded-lg"/>
                                    <button @click.prevent="deleteImage"
                                    class="absolute top-1 left-1 rounded-full cursor-pointer bg-gray-100 p-2">
                                        <Icon icon="fa-solid:trash-alt" class="w-5 h-5 text-red-500" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 mt-4" v-if="!slipImage">
                            <p>สลิปการทำรายการ</p>
                            <div id="dropzone"
                                @dragover.prevent="draging = true"
                                @dragleave.prevent="draging = false"
                                @drop.prevent="onDropFile"
                                :class="draging ? 'bg-blue-200 border-blue-400': 'bg-gray-200 border-gray-400'"
                                class="w-full relative border-2 border-dashed rounded-lg p-2"
                            >
                                <!-- <input type="file" class="absolute inset-0 w-full h-full opacity-0 z-50" /> -->
                                <div class="text-center">
                                    <img class="mx-auto h-8 w-8 opacity-70"
                                        src="https://www.svgrepo.com/show/357902/image-upload.svg" alt="">

                                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                                        <p class="relative">
                                            <span>Drag and drop</span>
                                            <button class="plearnd-btn-primary w-36 mx-2" @click.prevent="browseInputSlip"> or browse</button>
                                            <span>to upload</span>
                                        </p>
                                        <input id="slip-file-upload" type="file" accept="image/*" ref="inputSlip" class="hidden"  @change.prevent="onInputSlipChange">
                                    </h3>
                                    <p class="mt-1 text-xs text-gray-500">
                                        PNG, JPG, GIF up to 10MB
                                    </p>
                                </div>
                                <!-- <img v-if="slipImage" :src="slipImage.url" class="mt-4 mx-auto max-h-40 hidden" id="preview"> -->
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <p class="block text-gray-700 dark:text-white mb-1">
                                วัน/เวลา ที่ทำรายการ
                            </p>
                        </div>
                        <div class="grid grid-cols-1 gap-4 ">
                            <div class="-mx-3 flex flex-wrap">
                                <div class="w-full px-3 sm:w-1/2">
                                    <div class="">
                                        <VueDatePicker v-model="transferDate" :format="'dd-MM-yyyy'" :enable-time-picker="false"></VueDatePicker>
                                    </div>
                                </div>
                                <div class="w-full px-3 sm:w-1/2">
                                    <div class="">
                                        <VueDatePicker v-model="transferTime" format="HH:mm" :calendar-button="false" time-picker required />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 ">
                            <p class="block text-red-500 text-sm dark:text-white mb-1">
                                กรุณาตรวจสอบวันที่และเวลาให้ตรงกับใบสลิปโอนเงิน (หากเกิดข้อผิดพลาดจะไม่สามารถคืนเงินได้)
                            </p>
                        </div>
                        <div class="grid grid-cols-1 gap-4 mt-2">
                            <div v-if="mediaImage">
                                <div class="relative mb-2 max-h-fit overflow-hidden">
                                    <img :src="mediaImage.url" class="rounded-lg"/>
                                    <button @click.prevent="deleteMediaImage"
                                    class="absolute top-1 left-1 rounded-full cursor-pointer bg-gray-100 p-2">
                                        <Icon icon="fa-solid:trash-alt" class="w-5 h-5 text-red-500" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 mt-4" v-if="!mediaImage">
                            <p>รูปสื่อประชาสัมพันธ์</p>
                            <div id="dropzone"
                                @dragover.prevent="draging = true"
                                @dragleave.prevent="draging = false"
                                @drop.prevent="onDropMediaFile"
                                :class="draging ? 'bg-blue-200 border-blue-400': 'bg-gray-200 border-gray-400'"
                                class="w-full relative border-2 border-dashed rounded-lg p-2"
                            >
                                <!-- <input type="file" class="absolute inset-0 w-full h-full opacity-0 z-50" /> -->
                                <div class="text-center">
                                    <img class="mx-auto h-8 w-8 opacity-70"
                                        src="https://www.svgrepo.com/show/357902/image-upload.svg" alt="">

                                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                                        <p class="relative">
                                            <span>Drag and drop</span>
                                            <button class="plearnd-btn-primary w-36 mx-2" @click.prevent="browseInputMedia"> or browse</button>
                                            <span>to upload</span>
                                        </p>
                                        <input id="media-image-upload" type="file" accept="image/*" ref="inputMediaImage" class="hidden"  @change.prevent="onInputMediaChange">
                                    </h3>
                                    <p class="mt-1 text-xs text-gray-500">
                                        PNG, JPG, GIF up to 10MB
                                    </p>
                                </div>
                                <!-- <img v-if="slipImage" :src="slipImage.url" class="mt-4 mx-auto max-h-40 hidden" id="preview"> -->
                            </div>
                        </div>


                        <div class="mt-8 flex justify-end space-x-2">
                            <button type="submit"
                                class="bg-teal-500 text-white px-4 py-2 rounded-lg hover:bg-teal-700 dark:bg-teal-600 dark:text-white dark:hover:bg-teal-900"
                            >
                                บันทึกข้อมูล
                            </button>
                            <button type="button" @click.prevent="handleCancle"
                                class="bg-red-400 text-white px-4 py-2 rounded-lg hover:bg-red-700 dark:bg-red-600 dark:text-white dark:hover:bg-red-900"
                            >
                                ยกเลิก
                            </button>
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
