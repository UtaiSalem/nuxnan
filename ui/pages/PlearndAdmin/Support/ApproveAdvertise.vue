<script setup>
import MainLayout from '~/layouts/main.vue';
import ApproveAdvertCard from '@/PlearndComponents/earn/advertise/ApproveAdvertCard.vue';

const props = defineProps({
    advertises: {
        type: Object,
        required: true,
    },
});

</script>

<template>
    <MainLayout title="Donate List">
        <template #coverProfileCard>
            <div class="flex items-center max-w-full mx-auto mt-2 md:mb-4 shadow-lg bg-[url('/storage/images/banner/banner-bg.png')] bg-cover bg-no-repeat rounded-lg">
                <img class="section-banner-icon hidden md:block" :src="'/storage/images/banner/badges-icon.png'" alt="forums-icon">
                <p class="text-white font-bold text-sm md:text-xl p-2">รายการสื่อประชาสัมพันธ์สินค้า</p>
            </div>
        </template>

        <template #mainContent>
            <div class="py-4">
                <div v-if="props.advertises.data.length" class="mx-auto max-w-full flex flex-wrap justify-center gap-2 ">
                    <div v-for="(advert,index) in props.advertises.data" :key="index" class="w-full md:w-[48%] xl:w-[30%]">
                        <ApproveAdvertCard :advert />
                    </div>
                </div>
                <div v-else class="text-center">
                    <p class="text-gray-500">ไม่มีรายการสนับสนุนทุนการเรียนรู้</p>
                </div>
            </div>
        </template>
    </MainLayout>
</template>

