<script setup>
    import { ref } from "vue";
    import { Head, Link } from '@inertiajs/vue3'
    import { Icon } from "@iconify/vue";
    import { useObjectUrl } from "@vueuse/core";
    // import MainLayout from "@/Layouts/MainLayout.vue";
    import Swal from "sweetalert2";
    import VueDatePicker from "@vuepic/vue-datepicker";
    import "@vuepic/vue-datepicker/dist/main.css";
    const isDarkMode = ref(false);
    const isLoading = ref(false);
    const totalMoneySupport = ref(10);
    const moneyIndexSelected = ref(0);
    const slipImage = ref(null);
    const transferDate = ref(new Date());
    const transferTime = ref(null);

    const props = defineProps({
        donor: {
            type: Object,
            default: () => {
                return {
                    id: null,
                    name: null,
                    personal_code: null,
                };
            },
        },
    });

    const refDonor = ref(props.donor);

    const personalCode = ref(null);
    const isLoadingDonor = ref(false);

    const inputSlip = ref(null);
    const draging = ref(false);
    const generateOptions = (min, max, step) => {
        const options = [];
        for (let i = min; i <= max; i += step) {
            options.push(i);
        }
        return options;
    };
    const browseInputSlip = () => {
        inputSlip.value.click();
    };

    function onInputSlipChange(e) {
        slipImage.value = {
            file: e.target.files[0],
            url: URL.createObjectURL(e.target.files[0]),
        };
    }
    const tempDonate2Digit = ref(generateOptions(10, 100, 10));
    const tempDonate3Digit = ref(generateOptions(150, 500, 50));
    const tempDonate4Digit = ref(generateOptions(600, 1000, 100));
    // const totalMoneySupportOptions = ref(generateOptions(100, 1000, 50));
    const totalMoneySupportOptions = ref([...tempDonate2Digit.value, ...tempDonate3Digit.value, ...tempDonate4Digit.value]);

    function onDropFile(e) {
        // let file = e.target.files[0];
        let files = [...e.dataTransfer.items]
            .filter((item) => item.kind === "file")
            .map((item) => item.getAsFile());
        slipImage.value = {
            file: files[0],
            url: useObjectUrl(files[0]),
        };
    }

    function handleSelectedMoneyQuantity() {
        totalMoneySupport.value = totalMoneySupportOptions.value[moneyIndexSelected.value];
        // console.log(totalMoneySupport.value);
    }
    async function submitForm(e) {
        e.preventDefault();
        if (!slipImage.value) {
            Swal.fire("เกิดข้อผิดพลาด", "กรุณาอัพโหลดสลิปการทำรายการ", "error");
            return;
        }
        isLoading.value = true;
        const config = {
            headers: {
                "content-type": "multipart/form-data"
            }
        };
        const donateInfo = new FormData();
        donateInfo.append("user_id", refDonor.value ? refDonor.value.id : 7);
        donateInfo.append("donor_id", refDonor.value ? refDonor.value.id: null);
        donateInfo.append("donor_name", refDonor.value ? refDonor.value.name: 'ไม่ระบุนาม');
        donateInfo.append("amounts", totalMoneySupport.value);
        donateInfo.append("transfer_date", transferDate.value.toDateString());
        donateInfo.append("transfer_time", transferTime.value["hours"] + ":" + transferTime.value["minutes"]);
        donateInfo.append("slip", slipImage.value.file);
        try {
            let supportDonateResp = await axios.post("/supports/donates", donateInfo, config);
            
            if (supportDonateResp.data && supportDonateResp.data.success) {
                // console.log(supportDonateResp.data);
                Swal.fire(
                    "เสร็จสมบูรณ์",
                    "ขอบคุณที่สนับสนุนเว็บไซต์เพลินด์ ทางทีมงานจะตรวจสอบข้อมูลและทำการอัพเดทข้อมูลให้เร็วที่สุด",
                    "success"
                );
                // router.get(`/academies/${resp.data.academy}`)
                // window.location.href = "/";
                slipImage.value = null;
                transferDate.value = new Date();
                transferTime.value = null;
                
                } else {
                Swal.fire("เกิดข้อผิดพลาด", supportDonateResp.data.message, "error");
                } 
                isLoading.value = false;
        } catch (error) {
            console.log(error);
            Swal.fire("เกิดข้อผิดพลาด", error.response.data.message, "error");
        }
        isLoading.value = false;
    }

    function deleteImage() {
        slipImage.value = null;
    }

    function handleCancle() {
        window.location.href = "/";
    }

    async function handlePersonalcodeInput() {
        if (personalCode.value.length === 8) {
            isLoadingDonor.value = true;
            try {
                let donorResp = await axios.get(`/supports/donates/donor/${personalCode.value}`);
                if (donorResp.data && donorResp.data.success) {
                    refDonor.value = donorResp.data.donor;
                }
                personalCode.value = null;
                isLoadingDonor.value = false;
            } catch (error) {
                console.log(error);
                Swal.fire("ขออภัย", "ไม่พบสมาชิกที่ระบุ", "error");
            }
        }else{
            refDonor.value = null;
        }
    }

    function handleEmptyDonor() {
        refDonor.value = null;
    }
    
</script>

<template>
    <div>

        <Head>
            <title>{{ 'Donate' }}</title>
            <!-- <link rel="icon" type="image/*" :href="'/storage/images/favicon.ico'" /> -->
        </Head>
    
        <div class="mx-4">
            <div class="flex items-center justify-between max-w-7xl mx-auto mt-2 mb-4 shadow-lg bg-[url('/storage/images/banner/banner-bg.png')] bg-cover bg-no-repeat rounded-lg overflow-hidden">
                <div class="hidden md:flex items-center">
                    <img class="" :src="'/storage/images/banner/badges-icon.png'" alt="forums-icon" />
                    <Link href="/" class="text-xl font-bold text-white underline flex items-center">
                        <!-- <Icon icon="heroicons-solid:home" class="" />  -->
                        สนับสนุน เว็บไซต์ เพลินด์
                    </Link>
                </div>
                <div class="flex">
                    <Link href="/" class="text-xl font-bold text-white mx-2 underline flex items-center"><Icon icon="heroicons-solid:home" />Home</Link>
                    <Link href="/newsfeed" class="text-xl font-bold text-white mx-2 underline flex items-center"><Icon icon="heroicons:newspaper-solid" />กระดานข่าว</Link>
                </div>
            </div>
        </div>

        <div class="flex flex-row items-center justify-center mb-4">
            <div class="max-w-xl plearnd-card">
                <h2 class="mb-2 text-xl font-semibold text-gray-700 dark:text-white">
                    เพลินด์ขอขอบคุณผู้ให้การสนับสนุนทุกท่าน
                </h2>
                <h4 class="mb-2 text-base font-semibold text-gray-700 dark:text-white">
                    การสนับสนุนจากท่านจะถูกจัดสรรให้กับสมาชิกผู้ใช้งาน
                </h4>
                <h2 class="mb-2 text-xl font-semibold text-gray-700 dark:text-white">
                    ข้อมูลการสนับสนุน
                </h2>
                <div class="px-6">
                    <ol class="text-red-600 list-decimal">
                        <li>โอนเงินเข้าบัญชีหมายเลข 677-7724-60-5 ธนาคารกรุงไทย</li>
                        <li>ชื่อบัญชี นายอุทัย สาเหล็ม</li>
                        <li>กรอกข้อมูลให้ครบถ้วน</li>
                        <li>บันทึกข้อมูล</li>
                    </ol>
                </div>

                <form id="create-new-academy-form" @submit.prevent="submitForm" class="flex flex-col justify-center" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 gap-4 mt-4 ">
                        <div class="mb-2" v-if="!refDonor">
                            <label for="personal_code" class="block mb-2 font-medium text-gray-700">รหัสประจำตัวสมาชิก (ถ้ามี) </label>
                            <input type="text" id="personal_code" name="personal_code" v-model="personalCode" @input="handlePersonalcodeInput"
                                class="w-full p-2 border border-gray-400 rounded-lg focus:outline-none focus:border-blue-400">
                        </div>
                    
                        <h3 class="text-xl font-semibold text-gray-700 dark:text-white" v-if="refDonor">ผู้สนับสนุนทุนการเรียนรู้</h3>
                        <div v-if="isLoadingDonor" class="w-full p-4 bg-white rounded shadow">
                            <div class="flex space-x-4 animate-pulse">
                                <div class="w-12 h-12 bg-gray-300 rounded-full"></div>
                                <div class="flex-1 py-1 space-y-4">
                                    <div class="w-3/4 h-4 bg-gray-300 rounded"></div>
                                    <div class="space-y-2">
                                        <div class="h-4 bg-gray-300 rounded"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <figure v-if="refDonor && !isLoadingDonor" class="relative flex p-2 mb-4 bg-gray-300 rounded-md">

                            <button @click.prevent="handleEmptyDonor" class="absolute top-0 right-0 p-1 m-2 bg-white rounded-full opacity-60">
                                <Icon icon="heroicons-outline:trash" class="w-5 h-5 text-red-500" />
                            </button>

                            <div class="flex-shrink-0">
                                <img class="w-16 h-16 rounded-full" :src="refDonor.profile_photo_url" :alt="refDonor.name + 'photo'">
                            </div>

                            <div class="ps-3 ">
                                <div class="mb-1 md:space-x-2 text-sm text-gray-700 dark:text-gray-400 flex flex-col md:flex-row md:items-center">
                                    <span class="text-2xl font-bold">{{ refDonor.name }}</span>
                                    <span class="">รหัสประจำตัว</span>
                                    <span class="font-semibold">{{ refDonor.personal_code }}</span>
                                </div>

                                <Link :href="refDonor.referal_link" class="text-base text-white bg-teal-500 py-1 px-4 rounded-lg">
                                    สมัครต่อ
                                </Link>
                            </div>
                        </figure>
                        
                        <div>
                            <label for="money-quantity" class="block mb-1 text-gray-700 dark:text-white">
                                จำนวนเงิน
                            </label>
                            <select id="money-quantity" v-model="moneyIndexSelected" @change="handleSelectedMoneyQuantity"
                                class="w-full px-3 py-2 text-center border border-gray-400 rounded-lg dark:bg-gray-700 dark:text-white dark:border-none">
                                <option v-for="(
                                            moneyOption, midx
                                        ) in totalMoneySupportOptions" :key="midx" :value="midx"
                                    :selected="moneyOption.value == 20">
                                    {{ moneyOption }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 mt-4">
                        <div v-if="slipImage">
                            <div class="relative mb-2 overflow-hidden max-h-fit">
                                <img :src="slipImage.url" class="rounded-lg" />
                                <button @click.prevent="deleteImage"
                                    class="absolute p-2 bg-gray-100 rounded-full cursor-pointer top-1 left-1">
                                    <Icon icon="fa-solid:trash-alt" class="w-5 h-5 text-red-500" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 mt-4" v-if="!slipImage">
                        <p>สลิปการทำรายการ</p>
                        <div id="dropzone" @dragover.prevent="draging = true" @dragleave.prevent="draging = false"
                            @drop.prevent="onDropFile" :class="draging ? 'bg-blue-200 border-blue-400': 'bg-gray-200 border-gray-400'" 
                            class="relative w-full p-2 border-2 border-dashed rounded-lg">
                            <div class="text-center">
                                <img class="w-8 h-8 mx-auto opacity-70"
                                    src="https://www.svgrepo.com/show/357902/image-upload.svg" alt="" />

                                <h3 class="mt-2 text-sm font-medium text-gray-900">
                                    <p class="relative">
                                        <span>Drag and drop</span>
                                        <button class="mx-2 plearnd-btn-primary w-36" @click.prevent="browseInputSlip">
                                            or browse
                                        </button>
                                        <span>to upload</span>
                                    </p>
                                    <input id="slip-file-upload" type="file" accept="image/*" ref="inputSlip"
                                        class="hidden" @change.prevent="onInputSlipChange" />
                                </h3>
                                <p class="mt-1 text-xs text-gray-500">
                                    PNG, JPG, GIF up to 4MB
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 mt-4">
                        <p class="block mb-1 text-gray-700 dark:text-white">
                            วัน/เวลา ที่ทำรายการ
                        </p>
                    </div>
                    <div class="grid grid-cols-1 gap-2">
                        <div class="flex flex-wrap -mx-3">
                            <div class="w-full px-3 sm:w-1/2">
                                <div class="">
                                    <VueDatePicker v-model="transferDate" :format="'dd-MM-yyyy'" :enable-time-picker="false" />
                                </div>
                            </div>
                            <div class="w-full px-3 sm:w-1/2">
                                <div class="mt-2 sm:mt-0">
                                    <VueDatePicker v-model="transferTime" format="HH:mm" :calendar-button="false" time-picker required />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4">
                        <p class="block my-1 text-sm text-red-500 dark:text-white">
                            กรุณาตรวจสอบวันที่และเวลาให้ตรงกับใบสลิปโอนเงิน
                            (หากเกิดข้อผิดพลาดจะไม่สามารถคืนเงินได้)
                        </p>
                    </div>

                    <div class="flex justify-end mt-8 space-x-2">
                        <button type="submit" :disabled="isLoading"
                            class="flex items-center justify-center px-4 py-2 text-white bg-teal-500 rounded-lg hover:bg-teal-700 dark:bg-teal-600 dark:text-white dark:hover:bg-teal-900">
                            <!-- spinner icon -->
                            <Icon v-if="isLoading" icon="fa-solid:spinner" class="w-5 h-5 mx-1 animate-spin" />
                            <Icon v-else icon="fa-solid:save" class="w-5 h-5 mx-1" />
                            บันทึกข้อมูล
                        </button>
                        <Link href="/"
                            class="px-4 py-2 text-white bg-red-400 rounded-lg hover:bg-red-700 dark:bg-red-600 dark:text-white dark:hover:bg-red-900">
                            ยกเลิก
                        </Link>
                    </div>
                </form>
            </div>
        </div>
        <!-- </template>

            <template #rightSideWidget>
                <div></div>
            </template>
        </MainLayout> -->
    </div>
</template>

