<script setup>

import { Icon } from '@iconify/vue';
import AdvertiseItemCard from '@/PlearndComponents/widgets/advertises/AdvertiseItemCard.vue';

const props = defineProps({
    advertises: Object,
});

</script>
<template>
    <div class="bg-white p-1 rounded-lg shadow-lg border-t-4 border-blue-500">
        <p class="text-sm md:text-md font-bold mb-2 p-2">รายการประชาสัมพันธ์สินค้า</p>
        <hr class="mb-2">
        <div v-for="(advert, index) in props.advertises.data" :key="index"
            class="border mb-2 rounded-lg hover:shadow-indigo-300 hover:shadow-md bg-indigo-50/30">
            <AdvertiseItemCard 
                :advert="advert"
                :index="index"
            />
        </div>
        
        <hr class="my-2">

            <!-- @click.prevent="()=> router.visit('/supports/advertise') " -->
        <Link :href="'/supports/advertise/create'"
        class="flex items-center justify-center w-full py-2 my-1 font-medium text-center text-white rounded-md bg-teal-500">
            <Icon icon="eos-icons:product-subscriptions-outlined" class="w-6 h-6" />
            <span>ประชาสัมพันธ์สินค้า</span>
        </Link>
        
    </div>
</template>
