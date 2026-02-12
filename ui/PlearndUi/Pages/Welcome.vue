<script setup>
import { ref, onMounted} from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import LoadingPage from '@/PlearndComponents/accessories/LoadingPage.vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,

    usersCount: Number,
    coursesCount: Number,
    lessonsCount: Number,
    postsCount: Number,
    ceo: Object,
    visitorCounter: Number,

    donates: Object,
    donateRecipients: Object,
    meta: Object,

});

const currentTimes = ref(null);
const seconds = ref(0);
const minutes = ref(0);
const hours = ref(0);
const daysNameTh = ref('');
const todayDate = ref(0);
const months = ref(0);
const years = ref(0);

const isCreateDonatePageLoading = ref(false);
const isGetDonateLoading = ref(false);


onMounted(()=>{
    getDayOfWeek();
    setInterval(function(){
        let datetime = new Date();
        currentTimes.value = datetime.toLocaleTimeString();
        seconds.value = datetime.getSeconds();
        minutes.value = datetime.getMinutes();
        hours.value = datetime.getHours();
    }, 1000);
});

const getDayOfWeek = () => {
    const today = new Date();
    const weekDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
    const todayMonth = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    daysNameTh.value = weekDays[today.getDay()];
    months.value = todayMonth[today.getMonth()];
    years.value = today.getFullYear();
    todayDate.value = today.getDate();
};

const handleLinkToCreateDonate = () => {
    isCreateDonatePageLoading.value = true;
    window.location.href = "/supports/donates/create";
};

const handleGetDonate = async (donateId, idx) => {
    try {
        isGetDonateLoading.value = true;
        let getDonateResponse = await axios.get(`/donates/${donateId}/get-donate`);
        if (getDonateResponse.data.success) {
            Swal.fire({
                title: 'รับการสนับสนุนสำเร็จ',
                text: 'คุณได้รับการสนับสนุนเรียบร้อยแล้ว' + ' 270' + ' แต้ม',
                icon: 'success',
                showConfirmButton: false,
                timer: 1200
            });

            if(usePage().props.auth.user) { usePage().props.auth.user.pp += 270 };

            props.donates.data[idx].remaining_points = getDonateResponse.data.donate.remaining_points;

            if (props.donates.data[idx].remaining_points <= 0) {
                props.donates.data.splice(idx, 1);
            }

            isGetDonateLoading.value = false;
            // window.location.href = "/dashboard";
        }else{
            Swal.fire({
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถรับการสนับสนุนได้',
                icon: 'error',
                showConfirmButton: false,
                timer: 1200
            });
            isGetDonateLoading.value = false;
        }
    } catch (error) {
        console.log(error);
    }

};

</script>

<template>
    <Head title="Welcome" />

    <LoadingPage v-if="isGetDonateLoading" />

    <div
    class="relative flex justify-center items-center min-h-screen bg-[url('/storage/landing/joanna-kosinska-education-unsplash.png')] bg-cover bg-no-repeat dark:bg-gray-900 selection:bg-red-500 selection:text-white">

        <div class="flex flex-col items-center justify-center w-full h-full mt-8">
            <div v-if="canLogin" class="z-10 p-6 text-center sm:fixed sm:top-0 sm:right-0 md:text-right">

                <Link v-if="$page.props.auth.user" :href="'/newsfeed'"
                    class="text-md font-semibold text-gray-700 md:text-lg bg-gray-300 p-4 rounded-md dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">
                    <span class="">กระดานข่าว</span>
                </Link>

                <template v-else>
                    <Link :href="route('login')"
                        class="p-2 text-sm font-semibold text-gray-800 bg-gray-200 bg-opacity-50 rounded-md md:text-lg font-prompt hover:text-white hover:bg-gray-600 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">
                    เข้าใช้งาน</Link>

                    <Link v-if="canRegister" :href="route('register')"
                        class="p-2 ml-4 text-sm font-semibold text-gray-800 bg-gray-200 bg-opacity-50 rounded-md md:text-lg font-prompt hover:text-white hover:bg-gray-600 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">
                    สมัครสมาชิก</Link>
                </template>
            </div>

            <p class="text-xs text-white sm:text-2xl sm:mt-6 font-prompt ">เรียนบ้าง เล่นบ้าง สร้างรายได้ด้วย</p>
            <h3 class="text-base text-white md:text-4xl ">www.<b class="text-base md:text-6xl ">plearnd</b>.com</h3>


            <div class="p-3 mb-4 font-medium min-w-32 min-h-48">
                <div class="flex-none w-32 text-center rounded-t shadow-lg lg:rounded-t-none lg:rounded-l ">
                    <div class="block overflow-hidden text-center rounded-t font-prompt">
                        <div class="py-1 bg-blue-500">
                            <p class="text-white font-prompt ">
                                {{ months }}
                            </p>
                            <p class="text-white font-prompt ">
                                {{ years }}
                            </p>
                        </div>
                        <div class="pt-1 bg-white border-l border-r border-white">
                            <span class="text-5xl font-bold leading-tight text-blue-500">
                                {{ todayDate }}
                            </span>
                        </div>
                        <div class="pb-2 -mb-1 text-center bg-white border-b border-l border-r border-white rounded-b-lg -pt-2">
                            <span class="font-extrabold text-blue-500 text-md">
                                {{ daysNameTh }}
                            </span>
                        </div>
                        <div class="py-1 mt-2 text-center bg-white border-b border-l border-r border-white rounded-lg">
                            <span class="text-lg leading-normal text-gray-800">
                                {{  currentTimes }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <section class="text-gray-700 body-font">
                <div class="container px-5 py-12 mx-auto">
                    <div class="flex flex-wrap -m-4 text-center text-blue-500">

                        <div class="w-full px-4 py-2 md:w-1/4 sm:w-1/2">
                            <div class="w-full p-4 bg-white rounded-lg aspect ">
                                <div class="flex justify-center ">
                                    <Icon icon="la:users" class="w-10 h-10 text-blue-500" />
                                </div>
                                <div class="my-2">
                                    <h2 class="text-2xl font-bold text-blue-500">
                                        <span>{{ props.usersCount }}</span>
                                    </h2>
                                </div>
                                <div>Users</div>
                            </div>
                        </div>
                        <div class="w-full px-4 py-2 md:w-1/4 sm:w-1/2">
                            <div class="w-full p-4 bg-white rounded-lg aspect">
                                <div class="flex justify-center ">
                                    <Icon icon="icon-park-outline:comments" class="w-10 h-10 text-blue-500 " />
                                </div>
                                <div class="my-2">
                                    <h2 class="text-2xl font-bold text-blue-500">
                                        <span>{{ props.postsCount }}</span>
                                    </h2>
                                </div>
                                <div>Posts</div>
                            </div>
                        </div>
                        <div class="w-full px-4 py-2 md:w-1/4 sm:w-1/2">
                            <div class="w-full p-4 bg-white rounded-lg aspect">
                                <div class="flex justify-center ">
                                    <Icon icon="uil:notebooks" class="w-10 h-10 text-blue-500"/>
                                </div>
                                <div class="my-2">
                                    <h2 class="text-2xl font-bold text-blue-500">
                                        <span>{{ props.coursesCount }}</span>
                                    </h2>
                                </div>
                                <div>Courses</div>
                            </div>
                        </div>
                        <div class="w-full px-4 py-2 md:w-1/4 sm:w-1/2">
                            <div class="w-full p-4 bg-white rounded-lg aspect">
                                <div class="flex justify-center ">
                                    <Icon icon="material-symbols:library-books-outline" class="w-10 h-10 text-blue-500" />
                                </div>
                                <div class="my-2">
                                    <h2 class="text-2xl font-bold text-blue-500">
                                        <span>{{ props.lessonsCount }}</span>
                                    </h2>
                                </div>
                                <div>Lessons</div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>
            
            <section class="container flex justify-center w-full">
                <div class="flex items-center justify-center w-full m-5 bg-white rounded-lg sm:w-40">
                    <div class="p-3 space-y-2">
                        <div class="text-center">
                            <Icon icon="mdi:home-group" class="w-10 h-10 mx-auto text-blue-500" />
                        </div>
                        <div class="my-2 text-center">
                            <h2 class="text-2xl font-bold text-blue-500">
                                <span>{{ props.visitorCounter }}</span>
                            </h2>
                        </div>
                        <div class="text-center text-blue-500">ผู้เข้าชม</div>
                        <div class="text-sm text-center text-red-500">ตั้งแต่ {{ new Date('1/1/2024').toLocaleDateString() }}</div>
                    </div>
                </div>
            </section>

            <div class="max-w-6xl mt-20 mb-4 text-center">
                <p class="text-white text-md sm:text-lg font-prompt">***อยู่ระหว่างการพัฒนาและทดลองใช้งาน***</p>
            </div>
        </div>
    </div>
    <section class="text-gray-700 border-t border-gray-200 body-font">
        <div class="container px-5 py-10 mx-auto">
            <div class="flex flex-col w-full mb-10 text-center">
                <!-- <h2 class="mb-1 text-xs font-medium tracking-widest text-indigo-500 title-font">ROOF PARTY POLAROID</h2> -->
                <h1 class="text-2xl font-semibold text-gray-700 sm:text-3xl title-font">ผู้ให้การสนับสนุนทุนการเรียนรู้</h1>
            </div>
            <div class="flex flex-wrap ">
                <div class="grid grid-cols-1 gap-4 p-4 mx-auto sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 lg:gap-4">
                    <div v-for="(donate, index) in props.donates.data" :key="index" 
                        class="bg-white border rounded-lg hover:shadow-indigo-300 hover:shadow-md bg-indigo-50/30">

                        <!-- <div class="p-1 rounded-t-lg bg-sky-400"></div> -->
                        <div class="flex flex-col justify-between h-full p-4 rounded border-t-4 border-sky-400">
                            <figure class="flex items-center p-2 mb-2 bg-gray-300 rounded-md">
                                <div class="flex-shrink-0">
                                    <img class="w-16 h-16 rounded-full" :src="donate.donor ? donate.donor.avatar : '/storage/plearnd-logo.png'"
                                        :alt="donate.donor ? donate.donor.name + 'photo': 'donor-image'">
                                </div>
                                <div class="w-full ps-3">
                                    <div class="flex flex-col mb-1 text-sm text-gray-700 dark:text-gray-400">
                                        <span class="text-lg font-bold text-blue-500">{{ donate.donor ? donate.donor.name : 'ไม่ระบุนาม' }}</span>
                                        <span class="font-semibold">{{ donate.donor ? donate.donor.personal_code :'' }}</span>
                                    </div>
                                    <!-- <div class="text-base text-blue-600 dark:text-blue-500">{{ donate.donor ? donate.donor.referal_link:'' }}</div> -->
                                    <Link v-if="donate.donor && donate.donor.no_of_ref < 5" :href="donate.donor.referal_link" class="px-3 py-1 text-sm text-white bg-teal-400 rounded-md center font-base" >สมัครต่อ</Link>
                                </div>
                            </figure>
                            <div class="grid mx-auto">
                                <p class="pb-1 text-sm">ให้การสนับสนุน</p>
                                <div class="w-full pt-2 text-4xl font-bold tracking-tight flex justify-center">
                                    <span class="text-[28px] text-yellow-400">{{ donate.total_points.toLocaleString() }}/</span>
                                    <span class="text-[16px] text-yellow-400 mt-1"><span class="text-[14px] text-blue-500 mt-1 ml-1">คงเหลือ </span>{{ donate.remaining_points }}</span>
                                    <span class="text-[14px] mt-1 ml-1"> แต้ม</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container mx-auto mb-20 text-center rounded-lg">
            <button @click.prevent="handleLinkToCreateDonate" class="px-12 py-5 text-lg text-white bg-teal-500 rounded hover:bg-teal-600 hover:scale-105">
                <div class="flex items-center justify-center space-x-2">
                    <Icon icon="svg-spinners:bars-rotate-fade" class="w-10 h-10" v-if="isCreateDonatePageLoading" />
                    <span>สนับสนุนทุนการเรียนรู้</span>   
                </div>
            </button>
        </div>
    </section>

    <section class="text-gray-700 border-t bg-slate-200 body-font sm:px-4">
        <div class="container px-5 py-10 mx-auto">
            <div class="flex flex-col w-full mb-4 text-center">
                <h1 class="text-2xl font-semibold text-gray-700 sm:text-3xl title-font">ผู้ได้รับการสนับสนุนทุนการเรียนรู้</h1>
            </div>
        </div>
        <div class="pb-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div v-for="(recipient, index) in donateRecipients.data" :key="index">
                <div class="p-2 max-w-md mx-auto bg-white rounded-lg shadow-lg space-y-2 sm:py-4 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <img class="h-16 w-16 rounded-full sm:mx-0 sm:shrink-0" :src="recipient.avatar" alt="recipient-image">
                        <div class="">
                            <div class="mb-2">
                                <p class="text-sm text-gray-700 font-semibold">{{ recipient.name }}</p>
                            </div>
                            <Link v-if="recipient.no_of_ref < 5" :href="recipient.referal_link" class="px-3 py-1 text-sm text-white bg-teal-400 rounded-md center font-base" >สมัครต่อ</Link>
                        </div>
                    </div>
                    <span class="text-green-500 font-semibold">{{ recipient.points }} แต้ม</span>
                </div>
            </div>
        </div>
    </section>
    <!-- <section class="w-full px-4 mx-auto bg-blue-100 max-w-container sm:px-6 lg:px-8">
        <div class="container px-5 py-10 mx-auto">
            <div class="flex flex-col w-full mb-10 text-center">
                <h1 class="text-2xl font-semibold text-gray-700 sm:text-3xl title-font">สินค้าร่วมรายการ</h1>
            </div>
        </div>
    </section> -->
    <footer class="w-full px-4 mx-auto bg-gray-200 max-w-container sm:px-6 lg:px-8">
        <div class="py-10 text-center border-t border-slate-900/5">
            <h3 class="text-gray-700 ">
                <span>www.</span>
                <b class=" md:text-4xl">plearnd</b>
                <span>.com</span>
            </h3>
            <p class="mt-5 text-sm leading-6 text-center text-gray-800 font-prompt">เล่นบ้าง เรียนบ้าง สร้างรายได้ด้วย เพลิน!!</p>
            <div class="flex items-center justify-center mt-8 space-x-4 text-sm font-semibold leading-6 text-slate-700">
                <img :src="'/storage/landing/ceo.jpg'" alt="" class="w-24 h-24 rounded-full">
            </div>
            <div class="flex flex-col items-center justify-center mt-2 ml-16 space-x-4 text-sm font-semibold leading-6 text-slate-700">
                <div class="flex justify-start w-48">
                    <div class="flex items-center justify-start space-x-2">
                        <p><Icon icon="devicon:facebook" class="w-6 h-6" /></p>
                        <p>Utai Salem</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-col items-center justify-center mt-2 ml-16 space-x-4 text-sm font-semibold leading-6 text-slate-700">
                <div class="flex justify-start w-48">
                    <div class="flex items-center justify-start space-x-2">
                        <p><Icon icon="logos:messenger" class="w-6 h-6" /></p>
                        <p>Bhupha MustaFa</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-col items-center justify-center mt-2 ml-16 space-x-4 text-sm font-semibold leading-6 text-slate-700">
                <div class="flex justify-start w-48">
                    <div class="flex items-center justify-start space-x-2">
                        <p><Icon icon="uil:line" class="w-6 h-6 text-green-600" /></p>
                        <p>babobhupha</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-col items-center justify-center mt-2 ml-16 space-x-4 text-sm font-semibold leading-6 text-slate-700">
                <div class="flex justify-start w-48">
                    <div class="flex items-center justify-start space-x-2">
                        <p><Icon icon="bi:telephone" class="w-6 h-6 " /></p>
                        <p>093-840-3000</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>

