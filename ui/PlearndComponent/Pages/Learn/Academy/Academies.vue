<script setup>
import { ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import AcademiesLoadingSkeleton from '@/PlearndComponents/accessories/AcademiesLoadingSkeleton.vue';
import AcademyCard from '@/PlearndComponents/learn/academies/AcademyCard.vue';

const isLoading = ref(false);
const authUser = usePage().props.auth.user;
const academies = ref([]);
const academiesType = ref([
    {title: 'แหล่งเรียนรู้ของฉัน', errorMessages: 'ยังไม่มีแหล่งเรียนรู้ที่ฉันเป็นเจ้าของ'},
    {title: 'แหล่งเรียนรู้ที่ฉันเป็นสมาชิก', errorMessages: 'ยังไม่มีแหล่งเรียนรู้ที่ฉันเป็นสมาชิก'},
    {title: 'แหล่งเรียนรู้ทั้งหมด', errorMessages: 'ยังไม่มีแหล่งเรียนรู้'},
]);
const academiesTypeIndex = ref(1);

onMounted(() => {
    handleGetAcademies(academiesTypeIndex.value);
});


const handleGetAcademies = async (index) => {
    academiesTypeIndex.value = index;
    academies.value = [];
    switch (index) {
        case 0:
            getAuthOwnerAcademies();
            break;
        case 1:
            getAuthMemberedAcademies();
            break;
        case 2:
            getAllAcademies();
            break;
        default:
            break;
    }
};
const getAuthOwnerAcademies = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get(`/api/academies/users/${authUser.id}/my-academies`);
        if (response.data.success){
            academies.value = response.data.academies;
            isLoading.value = false;
        }else{
            isLoading.value = false;
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด! กรุณาลองใหม่อีกครั้ง',
                text: response.data.message,
            });
        }
    } catch (error) {
        console.log(error);
        isLoading.value = false;
    }
};

const getAuthMemberedAcademies = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get(`/api/academies/users/${authUser.id}/membered-academies`);
        if (response.data.success){
            academies.value = response.data.academies;
            isLoading.value = false;
        }else{
            isLoading.value = false;
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด! กรุณาลองใหม่อีกครั้ง',
                text: response.data.message,
            });
        }
    } catch (error) {
        console.log(error);
        isLoading.value = false;
    }
};

const getAllAcademies = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get(`/api/academies/all-academies`);
        if (response.data.success){
            academies.value = response.data.academies;
            isLoading.value = false;
        }else{
            isLoading.value = false;
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด! กรุณาลองใหม่อีกครั้ง',
                text: response.data.message,
            });
        }
    } catch (error) {
        console.log(error);
        isLoading.value = false;
    }
};

</script>

<template>
    <div>
        <MainLayout title="Academies">
            <template #coverProfileCard>
                <div :style="`background-image: url(${'/storage/images/banner/banner-bg.png'});`"
                    class="flex items-center max-w-7xl mx-auto jusb mt-2 mb-4 shadow-lg bg-image bg-cover bg-no-repeat">
                    <img class="section-banner-icon" :src="'/storage/images/banner/forums-icon.png'" alt="forums-icon">
                    <p class="text-white font-bold text-4xl">แหล่งเรียนรู้</p>
                </div>
            </template>
            <template #leftSideWidget>
                <div>
                    
                </div>
            </template>
            <template #mainContent>

                <!-- Component: Basic lg sized full width tab -->
                <div class=" bg-white shadow-xl w-full max-w-7xl mx-auto rounded-lg overflow-hidden mt-4">
                    <div class="flex flex-row justify-around">

                        <button @click.prevent="handleGetAcademies(0)"
                            class="border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center "
                            :class="{'border-b-4 border-cyan-500 bg-cyan-100': academiesTypeIndex===0}"
                        >
                            <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                                <Icon icon="lucide:layout-list" class="w-6 h-6" :class="{'text-cyan-500': academiesTypeIndex===0}" />
                                <span :class="{'text-cyan-500': academiesTypeIndex===0}">{{ academiesType[0].title }}</span>
                            </div>
                        </button>
                        <button @click.prevent="handleGetAcademies(1)" 
                            class="border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center "
                            :class="{'border-b-4 border-cyan-500 bg-cyan-100': academiesTypeIndex===1}"
                        >
                            <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                                <Icon icon="lucide:layout-list" class="w-6 h-6" :class="{'text-cyan-500': academiesTypeIndex===1}" />
                                <span :class="{'text-cyan-500': academiesTypeIndex===1}">{{ academiesType[1].title }}</span>
                            </div>
                        </button>
                        <button @click.prevent="handleGetAcademies(2)" class="border-b-4 hover:border-gray-400 rounded-none w-full text-center flex-row justify-center "
                            :class="{'border-b-4 border-cyan-500 bg-cyan-100': academiesTypeIndex===2}"
                        >
                            <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                                <Icon icon="lucide:layout-list" class="w-6 h-6" :class="{'text-cyan-500': academiesTypeIndex===2}" />
                                <span :class="{'text-cyan-500': academiesTypeIndex===2}">{{ academiesType[2].title }}</span>
                            </div>
                        </button>
                    </div>
                </div>
               <!-- End Basic lg sized full width tab -->

                <div class="section-header my-4 p-4 bg-white rounded-xl shadow-lg">
                    <div class="flex justify-between">               
                        <h2 class="font-bold text-2xl text-gray-700 font-prompt">{{ academiesType[academiesTypeIndex].title }} {{ ' (' + academies.length +')' || 'ยังไม่มีแหล่งเรียนรู้' }}</h2>
                    </div>
                </div>

                <div v-if="isLoading" class="grid grid-cols-1 mx-auto w-1/2">
                    <AcademiesLoadingSkeleton />
                </div>

                <div v-else-if="academies.length" class="flex flex-wrap justify-between gap-4 " >
                    <div v-for="(academy, index) in academies" :key="index" class="w-full sm:w-[48%]"> 
                        <Link :href="`/academies/${academy.name}`">
                            <AcademyCard :academy="academy" />
                        </Link>
                    </div>
                </div>                
                <div v-else class="flex items-center justify-center h-48 w-full bg-white dark:bg-gray-700 rounded-lg shadow-lg">
                    <p class="text-gray-800 dark:text-gray-200">{{ academiesType[academiesTypeIndex].errorMessages }}</p>
                </div>

            </template>
        </MainLayout>
    </div>
</template>

