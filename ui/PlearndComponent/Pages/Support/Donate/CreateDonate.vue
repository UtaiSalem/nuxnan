<script setup>
    import { ref, computed, onMounted } from "vue";
    import { Head, Link, usePage } from '@inertiajs/vue3'
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

    const page = usePage();
    const authUser = computed(() => page.props.auth?.user);

    const props = defineProps({
        donor: {
            type: Object,
            default: null,
        },
    });

    const refDonor = ref(null);
    const paymentMethod = ref('slip');
    const isAnonymous = ref(false);

    onMounted(() => {
        if (props.donor && props.donor.id) {
            refDonor.value = props.donor;
        } else if (authUser.value) {
            refDonor.value = authUser.value;
        }
    });

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
        // e.preventDefault(); // removed preventDefault as it is handled by @submit.prevent
        if (paymentMethod.value === 'slip' && !slipImage.value) {
            Swal.fire("เกิดข้อผิดพลาด", "กรุณาอัพโหลดสลิปการทำรายการ", "error");
            return;
        }

        if (paymentMethod.value === 'wallet' && authUser.value.wallet < totalMoneySupport.value) {
            Swal.fire("ยอดเงินไม่เพียงพอ", "กรุณาเติมเงินหรือเลือกวิธีการชำระเงินอื่น", "error");
            return;
        }

        if (paymentMethod.value === 'points' && authUser.value.pp < (totalMoneySupport.value * 100)) {
            Swal.fire("แต้มสะสมไม่เพียงพอ", "คุณต้องการ " + (totalMoneySupport.value * 100) + " แต้ม", "error");
            return;
        }

        isLoading.value = true;
        const config = {
            headers: {
                "content-type": "multipart/form-data"
            }
        };
        const donateInfo = new FormData();
        
        // Handle User ID and Donor Name
        // If Anonymous, backend handles hiding, but we can send explicit flags
        donateInfo.append("is_anonymous", isAnonymous.value ? 1 : 0);
        donateInfo.append("payment_method", paymentMethod.value);
        donateInfo.append("amounts", totalMoneySupport.value);
        donateInfo.append("transfer_date", transferDate.value.toDateString());
        
        if (transferTime.value) {
             donateInfo.append("transfer_time", transferTime.value["hours"] + ":" + transferTime.value["minutes"]);
        } else {
             // Default to now if not set (though required in UI)
             let now = new Date();
             donateInfo.append("transfer_time", now.getHours() + ":" + now.getMinutes());
        }

        if (paymentMethod.value === 'slip' && slipImage.value) {
            donateInfo.append("slip", slipImage.value.file);
        }

        // Logic to determine donor_name sent to backend
        if (isAnonymous.value) {
             donateInfo.append("donor_name", 'ไม่ประสงค์ออกนาม');
             // Backend will ignore user_id linkage for anonymous if logic matches
        } else {
             if (refDonor.value) {
                 donateInfo.append("donor_name", refDonor.value.name);
                 donateInfo.append("user_id", refDonor.value.id); // Contextual user
             } else {
                 // Fallback
                 donateInfo.append("donor_name", 'ผู้เยี่ยมชม');
             }
        }

        try {
            let supportDonateResp = await axios.post("/supports/donates", donateInfo, config);
            
            if (supportDonateResp.data && supportDonateResp.data.success) {
                // console.log(supportDonateResp.data);
                Swal.fire(
                    "เสร็จสมบูรณ์",
                    "ขอบคุณที่สนับสนุนเว็บไซต์เพลินด์ " + (supportDonateResp.data.donate.status === 1 ? "ระบบดำเนินการเรียบร้อยแล้ว" : "ทางทีมงานจะตรวจสอบข้อมูลและทำการอัพเดทข้อมูลให้เร็วที่สุด"),
                    "success"
                );
                
                // Reset form
                slipImage.value = null;
                transferDate.value = new Date();
                transferTime.value = null;
                // Keep the donor logged in
                
                } else {
                Swal.fire("เกิดข้อผิดพลาด", supportDonateResp.data.message, "error");
                } 
        } catch (error) {
            console.log(error);
            let msg = "เกิดข้อผิดพลาด";
            if (error.response && error.response.data && error.response.data.message) {
                msg = error.response.data.message;
            }
            Swal.fire("เกิดข้อผิดพลาด", msg, "error");
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

                    <!-- Payment Method Selection -->
                    <div class="mt-6 mb-4">
                        <label class="block mb-2 text-lg font-semibold text-gray-700 dark:text-white">ช่องทางการสนับสนุน</label>
                        <div class="flex flex-wrap gap-4">
                            <!-- Slip Transfer -->
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                                :class="{'ring-2 ring-teal-500 bg-teal-50 dark:bg-gray-800': paymentMethod === 'slip'}">
                                <input type="radio" v-model="paymentMethod" value="slip" class="hidden">
                                <div class="flex items-center space-x-2">
                                    <Icon icon="heroicons:receipt-refund" class="w-6 h-6 text-gray-600 dark:text-gray-300" />
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">โอนเงินธนาคาร</div>
                                        <div class="text-sm text-gray-500">แนบสลิปการโอน</div>
                                    </div>
                                </div>
                            </label>

                            <!-- Wallet -->
                            <label v-if="authUser" class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                                :class="{'ring-2 ring-teal-500 bg-teal-50 dark:bg-gray-800': paymentMethod === 'wallet'}">
                                <input type="radio" v-model="paymentMethod" value="wallet" class="hidden" :disabled="isAnonymous">
                                <div class="flex items-center space-x-2">
                                    <Icon icon="heroicons:wallet" class="w-6 h-6 text-blue-600" />
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">กระเป๋าเงินสะสม</div>
                                        <div class="text-sm text-gray-500">ยอดเงินคงเหลือ: {{ authUser.wallet }} บาท</div>
                                    </div>
                                </div>
                            </label>

                            <!-- Points -->
                            <label v-if="authUser" class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                                :class="{'ring-2 ring-teal-500 bg-teal-50 dark:bg-gray-800': paymentMethod === 'points'}">
                                <input type="radio" v-model="paymentMethod" value="points" class="hidden" :disabled="isAnonymous">
                                <div class="flex items-center space-x-2">
                                    <Icon icon="heroicons:star" class="w-6 h-6 text-yellow-500" />
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">แลกแต้มสะสม</div>
                                        <div class="text-sm text-gray-500">แต้มคงเหลือ: {{ authUser.pp }} PP</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Anonymous Checkbox -->
                    <div class="flex items-center mb-4">
                        <input id="is-anonymous" type="checkbox" v-model="isAnonymous" @change="() => { if(isAnonymous) paymentMethod = 'slip' }"
                            class="w-4 h-4 text-teal-600 bg-gray-100 border-gray-300 rounded focus:ring-teal-500 dark:focus:ring-teal-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="is-anonymous" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                            ไม่ประสงค์ออกนาม (บริจาคแบบไม่ต้องระบุตัวตน)
                        </label>
                    </div>
                    
                    <div v-if="isAnonymous && authUser" class="p-2 mb-4 text-sm text-yellow-700 bg-yellow-100 rounded-lg dark:bg-yellow-200 dark:text-yellow-800" role="alert">
                        <span class="font-medium">หมายเหตุ!</span> การบริจาคแบบไม่ระบุตัวตน จะรองรับเฉพาะวิธีการโอนเงิน (แนบสลิป) เท่านั้น
                    </div>
                    
                    <div v-if="paymentMethod === 'points'" class="p-2 mb-4 text-sm text-blue-700 bg-blue-100 rounded-lg" role="alert">
                        <span class="font-medium">ข้อมูลการแลกเปลี่ยน:</span> 1 บาท ใช้ 100 PP (ยอด {{ totalMoneySupport }} บาท ใช้ {{ totalMoneySupport * 100 }} PP)
                    </div>

                    <div class="grid grid-cols-1 gap-4 mt-4 text-center" v-if="paymentMethod === 'slip'">
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
                    <div class="grid grid-cols-1 gap-4 mt-4" v-if="paymentMethod === 'slip' && !slipImage">
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
                    <div class="grid grid-cols-1 mt-4" v-if="paymentMethod === 'slip'">
                        <p class="block mb-1 text-gray-700 dark:text-white">
                            วัน/เวลา ที่ทำรายการ
                        </p>
                    </div>
                    <div class="grid grid-cols-1 gap-2" v-if="paymentMethod === 'slip'">
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
                    <div class="grid grid-cols-1 gap-4" v-if="paymentMethod === 'slip'">
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
