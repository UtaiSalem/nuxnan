<script setup>
import { ref } from 'vue';
import DonationsViewer from './DonationsViewer.vue';

const props = defineProps({
    activity: Object,
});

const donation = ref(props.activity.target_resource.donation);
const reciever = ref(props.activity.target_resource.reciever);

</script>

<template>
    <div class="my-4">
        <div class="bg-white shadow-lg rounded-lg dark:bg-dark-card p-2 relative " v-if="activity">
            <div class="flex flex-wrap w-full gap-4">
                <div class="flex items-center gap-2 min-w-fit">
                    <div class="flex-shrink-0">
                        <img class="w-12 h-12 p-[3px] rounded-full ring-1 ring-blue-600 dark:ring-gray-500"
                            :src="reciever.avatar" :alt="reciever.name + 'photo'">
                    </div>
                    <h6 class="">{{ reciever.name }}</h6>
                    <div class="flex justify-between">
                        <div class="flex flex-wrap items-center gap-1">
                            <small class="text-gray-800 dark:text-secondary-600">
                                ได้รับการสนับสนุน
                            </small>

                            <small class="text-gray-800 dark:text-secondary-600">
                                เมื่อ
                            </small>

                            <small class="text-gray-800 dark:text-secondary-600">
                                {{ props.activity.diff_humans_created_at }} จาก
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-2">
                <div class="border rounded-lg w-full p-2">
                    <div class="flex items-center gap-2 min-w-fit">
                        <div class="flex-shrink-0">
                            <img class="w-12 h-12 p-[3px] rounded-full ring-1 ring-blue-600 dark:ring-gray-500"
                                :src="donation.donor ? donation.donor.avatar : '/storage/plearnd-logo.png'"
                                :alt="donation.donor ? donation.donor.name + 'photo' : 'donor-photo'">
                        </div>
                        <h6 class="">{{ donation.donor_name }}</h6>
                        <div class="flex justify-between">
                            <div class="flex flex-wrap items-center gap-1">
                                <small class="text-gray-800 dark:text-secondary-600">
                                    ให้การสนับสนุน
                                </small>

                                <small class="text-gray-800 dark:text-secondary-600">
                                    เมื่อ
                                </small>
                                <small class="text-gray-800 dark:text-secondary-600">
                                    {{ donation.diff_humans_created_at }}
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="bg-green-100 rounded-lg flex items-center justify-center text-base font-bold tracking-tight">
                            <div class="flex items-center justify-center space-x-1 p-2">
                                    <span class="text-[28px] text-yellow-400"> 240</span>
                                    <span class="text-[12px] text-green-500 mt-1.5"> แต้ม</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
