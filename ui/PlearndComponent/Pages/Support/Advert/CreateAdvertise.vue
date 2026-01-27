<script setup>

import MainLayout from '@/Layouts/MainLayout.vue';
import '@vuepic/vue-datepicker/dist/main.css';
import { Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue';
import CreateAdvert from '@/PlearndComponents/earn/advertise/CreateAdvert.vue';

const props = defineProps({
    Supporter: Object,
});

</script>

<template>
    <div>
        <MainLayout title="Support Plearnd.com">
            <template #coverProfileCard>

                <div class="hidden sm:flex  items-center justify-between max-w-7xl mx-auto mt-2 mb-4 shadow-lg bg-[url('/storage/images/banner/banner-bg.png')] bg-cover bg-no-repeat rounded-lg overflow-hidden">
                    <div class="flex items-center">
                        <img class="" :src="'/storage/images/banner/badges-icon.png'" alt="forums-icon" />
                        <Link href="/" class="text-xl font-bold text-white underline flex items-center">
                            สนับสนุน เว็บไซต์ เพลินด์
                        </Link>
                    </div>
                    <div class="flex">
                        <Link href="/" class="text-xl font-bold text-white mx-2 underline flex items-center"><Icon icon="heroicons-solid:home" />Home</Link>
                        <Link href="/newsfeed" class="text-xl font-bold text-white mx-2 underline flex items-center"><Icon icon="heroicons:newspaper-solid" />กระดานข่าว</Link>
                    </div>
                </div>

            </template>
            <template #leftSideWidget>
                <div>
                </div>
            </template>

            <template #mainContent>

                <CreateAdvert :Supporter="Supporter" />

            </template>

            <template #rightSideWidget>
                <div>

                </div>
            </template>
        </MainLayout>
    </div>
</template>
