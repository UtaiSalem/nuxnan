<template>
  <Head title="Welcome" />

  <LoadingPage v-if="isGetDonateLoading" />

  <div class="">

    <!-- Hero Section -->
    <HeroSection />
    <!-- Welcome Section -->
    <WelcomeSection />
    <!-- Services Section -->
    <!-- <ServicesSection /> -->
    <!-- Portfolio Section -->
    <!-- <PortfolioSection /> -->
    <!-- Blog Section -->
    <!-- <BlogSection /> -->
    <!-- Fun Facts Section -->
    <!-- <FunFactsSection /> -->
    <!-- Tabs & Testimonial Section -->
    <!-- <TabsTestimonialSection /> -->
    <!-- Team Section -->
    <!-- <TeamSection /> -->
    <!-- Pricing Section -->
    <!-- <PricingSection /> -->
    <!-- Sponsors Section -->
    <!-- <SponsorsSection /> -->
    <!-- Newsletter Section -->
    <!-- <NewsletterSection /> -->
    <!-- Footer Section -->
    <!-- <FooterSection /> -->
  </div>
</template>


<script setup>
import HeroSection from '@/Components/landing/HeroSection.vue';
import WelcomeSection from '@/Components/landing/WelcomeSection.vue';
import ServicesSection from '@/Components/landing/ServicesSection.vue';
import PortfolioSection from '@/Components/landing/PortfolioSection.vue';
import BlogSection from '@/Components/landing/BlogSection.vue';
import FunFactsSection from '@/Components/landing/FunFactsSection.vue';
import TabsTestimonialSection from '@/Components/landing/TabsTestimonialSection.vue';
import TeamSection from '@/Components/landing/TeamSection.vue';
import PricingSection from '@/Components/landing/PricingSection.vue';
import SponsorsSection from '@/Components/landing/SponsorsSection.vue';
import NewsletterSection from '@/Components/landing/NewsletterSection.vue';
import FooterSection from '@/Components/landing/FooterSection.vue';


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