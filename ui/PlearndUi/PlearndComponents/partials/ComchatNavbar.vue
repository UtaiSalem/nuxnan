<template>
    <div class="sticky bg-white flex items-center justify-between p-2 lg:px-5 shadow-b-md">
        <!-- Left -->
        <div class="flex items-center space-x-2">
            <button class="sm:hidden bg-purple-600 rounded-full text-white">
                <Icon icon="mdi:menu-close" class="w-6 h-6 m-2" />
            </button>
            <img src="storage/images/logo.png" alt="Facebook Logo" width="40" height="40" loading="lazy"
                class="cursor-pointer" />


        </div>
        <!-- Center -->
        <form action="/search" class="max-w-[480px] w-full px-4">
            <div class="relative">
                <input type="text" name="q" class="w-full border-none bg-gray-100 focus:border-none h-10 shadow px-4 rounded-full" placeholder="search">
                <button type="submit">  
                    <Icon icon="cil:search" class="text-teal-400 h-5 w-5 absolute top-2.5 right-3 fill-current" />
                </button>
            </div>
        </form>

        <div class="flex justify-center flex-grow">
            <div class="flex space-x-6 md:space-x-2 text-2xl">
                <div class=" flex items-center cursor-pointer px-3 py-2 md:hover:bg-gray-100 rounded-xl group">
                    <Icon icon="tabler:home" class="h-5 text-gray-500 group-hover:text-blue-500" />
                </div>
                <div class=" flex items-center cursor-pointer px-3 py-2 md:hover:bg-gray-100 rounded-xl group">
                    <Icon icon="ph:flag-bold" class="h-5 text-gray-500 group-hover:text-blue-500" />
                </div>
                <div class=" flex items-center cursor-pointer px-3 py-2 md:hover:bg-gray-100 rounded-xl group">
                    <Icon icon="octicon:play-24" class="h-5 text-gray-500 group-hover:text-blue-500" />
                </div>
                <div class=" flex items-center cursor-pointer px-3 py-2 md:hover:bg-gray-100 rounded-xl group">
                    <Icon icon="basil:user-outline" class="h-5 text-gray-500 group-hover:text-blue-500" />
                </div>
                <div class=" flex items-center cursor-pointer px-3 py-2 md:hover:bg-gray-100 rounded-xl group">
                    <Icon icon="heroicons:user-group" class="h-5 text-gray-500 group-hover:text-blue-500" />
                </div>
            </div>
        </div>

        <!-- Right -->
        <div class="flex items-center sm:space-x-2 justify-end text-xl">
            <!-- <LogoutIcon class="icon"/> -->
            <img :src="'storage/images/avatar/17.jpg'" alt="avatar" width="30" height="30" loading="lazy" title="Username" class="icon rounded-full"/>
            <p class="whitespace-nowrap font-semibold pr-3 capitalize">
                {{ 'user-Name' }}
            </p>
            <Icon icon="heroicons-solid:plus" />
            <Icon icon="heroicons-solid:chat-alt-2" />
            <Icon icon="heroicons-solid:bell" />
            <Icon icon="heroicons-solid:chevron-down" />
        </div>
    </div>
</template>

<script setup>
import { Icon } from '@iconify/vue';

defineProps({
    isSidebarMini: Boolean
});
</script>
