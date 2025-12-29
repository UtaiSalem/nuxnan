<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';


import InfiniteLoading from "v3-infinite-loading";
import MainLayout from '~/layouts/main.vue';
import AdvertiseItemCard from '@/PlearndComponents/widgets/advertises/AdvertiseItemCard.vue';
import LoadingPage from '@/PlearndComponents/accessories/LoadingPage.vue';


const props = defineProps({
    // donates: Object,
    adverts: Object,
});

const refAdvertsData = ref(props.adverts.data??[]);
const isCreateAdvertPageLoading = ref(false);
const isLoadingMoreAdverts = ref(false);
const nextPageUrl = ref(props.adverts.links.next ? '/supports/advertises/more?page=2': null);

const handleLinkToPage = (href) => {
    isCreateAdvertPageLoading.value = true;
    router.visit(href);
};

async function fetchMoreAdvertises(){
    try {
        if(!nextPageUrl.value) return;
        isLoadingMoreAdverts.value = true;
        const advertsResp = await axios.get(nextPageUrl.value);
        if (advertsResp.data) {
            advertsResp.data.adverts.data.forEach(advert => {
                refAdvertsData.value.push(advert);
            });
            nextPageUrl.value = advertsResp.data.adverts.next_page_url;
            // console.log(advertsResp.data);
            isLoadingMoreAdverts.value = false;
        }
    } catch (error) {
        // Handle error silently
    }
}

</script>

<template>
    <div>
        <MainLayout title="ทุนสนับสนุนการเรียนรู้">
            <template #coverProfileCard>
                <div class="hidden md:flex items-center mx-auto mt-2 mb-4 shadow-lg bg-[url('/storage/images/banner/banner-bg.png')] bg-cover bg-no-repeat rounded-lg">
                    <img class="section-banner-icon " :src="'/storage/images/banner/badges-icon.png'" alt="forums-icon">
                    <p class="text-white font-bold text-2xl">ทุนสนับสนุนการเรียนรู้</p>
                </div>
                <div class="">
                    <!-- <MobileNavbar /> -->
                </div>
            </template>

            <template #leftSideWidget>
                <!-- Donation List -->
                <!-- <DonationListWidget v-if="donates.data.length"
                    :donates
                    @add-new-donate-activity=""
                    @go-to-create-donate="handleLinkToPage('/supports/donates/create')"
                /> -->
            </template>

            <template #rightSidebar>
                <!-- <PendingFriendsWidget :pendingFriends="props.pendingFriends" /> -->
            </template>

            <template #mainContent>

                <LoadingPage v-if="isCreateAdvertPageLoading" />

                <div class="mt-2 md:mt-0 mb-4 flex justify-center bg-white p-2 rounded-lg border-t-4 border-blue-500">
                    <button class="flex items-center justify-center max-w-fit px-4 py-2 my-1 font-medium text-center text-white rounded-md bg-teal-400"
                        @click.prevent="handleLinkToPage('/supports/advertises/create')">
                        <Icon icon="flat-color-icons:donate" class="w-7 h-7" />
                        <span>ให้การสนับสนุนทุนการเรียนรู้</span>
                    </button>
                </div>
                <template v-if="props.adverts.data.length">
                    <div class=" grid md:grid-cols-2 gap-4">
                        <div v-for="(advert, index) in refAdvertsData" :key="index"
                            class="border mb-2 rounded-lg hover:shadow-indigo-300 hover:shadow-md bg-white">
                            <AdvertiseItemCard 
                                :advert="advert"
                                :index="index"
                            />
                        </div>
                    </div>
                </template>  
                <template v-else>
                    <div class="flex justify-center items-center h-96">
                        <p class="text-2xl font-bold text-gray-400">No Supports</p>
                    </div>
                </template>
                <InfiniteLoading @distance="1" @infinite="fetchMoreAdvertises" />
                <div v-if="isLoadingMoreAdverts" class="w-full flex justify-center items-center mt-2 text-violet-600">
                    <svg width="120" height="120" fill="currentColor" class="m-2 animate-spin" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg">
                        <path d="M526 1394q0 53-37.5 90.5t-90.5 37.5q-52 0-90-38t-38-90q0-53 37.5-90.5t90.5-37.5 90.5 37.5 37.5 90.5zm498 206q0 53-37.5 90.5t-90.5 37.5-90.5-37.5-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm-704-704q0 53-37.5 90.5t-90.5 37.5-90.5-37.5-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm1202 498q0 52-38 90t-90 38q-53 0-90.5-37.5t-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm-964-996q0 66-47 113t-113 47-113-47-47-113 47-113 113-47 113 47 47 113zm1170 498q0 53-37.5 90.5t-90.5 37.5-90.5-37.5-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm-640-704q0 80-56 136t-136 56-136-56-56-136 56-136 136-56 136 56 56 136zm530 206q0 93-66 158.5t-158 65.5q-93 0-158.5-65.5t-65.5-158.5q0-92 65.5-158t158.5-66q92 0 158 66t66 158z">
                        </path>
                    </svg>
                </div>
            </template>

        </MainLayout>
    </div>
</template>

