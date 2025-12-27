<script setup lang="ts">
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import MainLayout from '~/layouts/main.vue';

const supporter = usePage().props.auth;

const totalMoneySupport = ref(0);
const tatalPPTransform =ref('');


function computePP(e){
    // console.log(totalMoneySupport.value);
    tatalPPTransform.value = (totalMoneySupport.value * 684).toLocaleString(); 
}

</script>

<template>
    <div>
        <MainLayout title="Support Plearnd.com">
            <template #coverProfileCard>
                <div class="flex items-center max-w-7xl mx-auto jusb mt-2 mb-4 shadow-lg bg-[url('/storage/images/banner/banner-bg.png')] bg-cover bg-no-repeat">
                    <img class="section-banner-icon" :src="'/storage/images/banner/badges-icon.png'" alt="forums-icon">
                    <p class="text-white font-bold text-4xl">สนับสนุน เว็บไซต์ เพลินดิ์</p>
                </div>
            </template>
            <template #mainContent>
                <div>
                    <div class="plearnd-card">
                        <!-- <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">Checkout</h1> -->

                        <!-- Shipping Address -->
                        <!-- <div class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-700 dark:text-white mb-2">ข้อมูลการสนับสนุน</h2>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block text-gray-700 dark:text-white mb-1">First
                                        Name</label>
                                    <input type="text" id="first_name"
                                        class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none">
                                </div>
                                <div>
                                    <label for="last_name" class="block text-gray-700 dark:text-white mb-1">Last
                                        Name</label>
                                    <input type="text" id="last_name"
                                        class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none">
                                </div>
                            </div>

                            <div class="mt-4">
                                <label for="address" class="block text-gray-700 dark:text-white mb-1">Address</label>
                                <input type="text" id="address"
                                    class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none">
                            </div>

                            <div class="mt-4">
                                <label for="city" class="block text-gray-700 dark:text-white mb-1">City</label>
                                <input type="text" id="city"
                                    class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none">
                            </div>

                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label for="state" class="block text-gray-700 dark:text-white mb-1">State</label>
                                    <input type="text" id="state"
                                        class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none">
                                </div>
                                <div>
                                    <label for="zip" class="block text-gray-700 dark:text-white mb-1">ZIP Code</label>
                                    <input type="text" id="zip"
                                        class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none">
                                </div>
                            </div>
                        </div> -->

                        <!-- Payment Information -->
                        <div>
                            <h2 class="text-xl font-semibold text-gray-700 dark:text-white mb-2">
                                ข้อมูลการสนับสนุน
                            </h2>

                            <h3 class="text-lg font-semibold text-gray-600 dark:text-white mb-2">
                                ผู้สนับสนุน
                            </h3>
                            <div class="max-w-sm space-y-2 sm:py-4 sm:flex sm:items-center sm:space-y-0 sm:space-x-6">
                                <img class="block mx-auto h-16 rounded-full sm:mx-0 sm:shrink-0" src="https://tailwindcss.com/img/erin-lindford.jpg" alt="Woman's Face">
                                <div class="text-center space-y-2 sm:text-left">
                                    <div class="space-y-0.5">
                                        <p class="text-lg text-black font-semibold">
                                            <!-- {{ $page.props.auth.user.name }} -->
                                        </p>
                                        <p class="text-slate-500 font-medium">
                                            Product Engineer
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <!-- <div class="mt-4">
                                <label for="card_number" class="block text-gray-700 dark:text-white mb-1">
                                    จำนวนเงิน
                                </label>
                                <input type="number" id="card_number"
                                    class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none">
                            </div> -->

                            <div class="grid md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label for="card_number" class="block text-gray-700 dark:text-white mb-1">
                                    จำนวนเงิน
                                </label>
                                <input type="number" min="0" id="card_number" v-model="totalMoneySupport"  @input="computePP"
                                    class="w-full rounded-lg border py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none">
                                </div>
                                <div>
                                    <label for="cvv" class="block text-gray-700 dark:text-white mb-1">จำนวนแต้ม</label>
                                    <input type="text" id="cvv" disabled v-model="tatalPPTransform"
                                        class="w-full rounded-lg border border-gray-400 py-2 px-3 dark:bg-gray-700 dark:text-white dark:border-none">
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <button
                                class="bg-teal-500 text-white px-4 py-2 rounded-lg hover:bg-teal-700 dark:bg-teal-600 dark:text-white dark:hover:bg-teal-900">Place
                                Order</button>
                        </div>
                    </div>
                </div>
            </template>
        </MainLayout>
    </div>
</template>

<style lang="scss" scoped>

</style>

