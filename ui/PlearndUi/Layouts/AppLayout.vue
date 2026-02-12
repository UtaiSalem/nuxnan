<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

defineProps({
    title: String,
});

const showingNavigationDropdown = ref(false);


const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div>
        <Head>
            <title>{{ title }}</title>
            <link rel="icon" type="image/*" :href="'/storage/images/favicon.ico'" />
        </Head>

        <Banner />

        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav class="bg-white border-b border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <!-- Primary Navigation Menu -->
                <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex items-center justify-center shrink-0">
                                <Link :href="route('welcome')">
                                    <!-- <ApplicationMark class="block w-auto h-9" /> -->
                                    <ApplicationMark class="block" />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                <!-- <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    แผงจัดการ
                                </NavLink> -->
                                <!-- <NavLink :href="route('newsfeed')" :active="route().current('newsfeed')">
                                    กระดานข่าว
                                </NavLink> -->
                                <NavLink :href="'/newsfeed'" :active="route().current('/newsfeed')">
                                    กระดานข่าว
                                </NavLink>

                                <!-- <NavLink :href="'/academies'" :active="route().current('/academies')">
                                    แหล่งเรียนรู้
                                </NavLink> -->

                                <NavLink :href="'/courses'" :active="route().current('/courses')">
                                    รวมรายวิชา
                                </NavLink>

                                <!-- <NavLink :href="`/users/${$page.props.auth.user.id}/feed`" :active="route().current('myfeed')">
                                    กระดานของฉัน
                                </NavLink> -->
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ml-6">

                            <!-- Settings Dropdown -->
                            <div class="relative ml-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex overflow-hidden text-sm transition border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300">
                                            <img class="object-cover w-8 h-8 rounded-full" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                                            <div class="inline-flex items-center px-1 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out bg-white border border-transparent rounded-md dark:text-gray-400 dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700">
                                                {{ $page.props.auth.user.name }}
                                                <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </div>
                                        </button>

                                        <span v-else class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out bg-white border border-transparent rounded-md dark:text-gray-400 dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700">
                                                {{ $page.props.auth.user.name }}
                                                <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <!-- Account Management -->
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            Manage Account
                                        </div>

                                        <DropdownLink :href="route('profile.show')">
                                            ข้อมูลส่วนตัว
                                        </DropdownLink>

                                        <DropdownLink :href="route('dashboard')">
                                            แผงจัดการ
                                        </DropdownLink>

                                        <!-- <DropdownLink :href="route('newsfeed')">
                                            กระดานข่าว
                                        </DropdownLink> -->

                                        <DropdownLink href="/courses">
                                            รวมรายวิชา
                                        </DropdownLink>

                                        <!-- <DropdownLink href="/academies">
                                            รวมแหล่งเรียนรู้
                                        </DropdownLink> -->

                                        <DropdownLink href="/supports/advertise">
                                            ประชาสัมพันธ์สินค้า
                                        </DropdownLink>

                                        <DropdownLink href="/supports/loan">
                                            สนับสนุนทุนการศึกษา
                                        </DropdownLink>

                                        <DropdownLink href="/forgot-password" v-if="$page.props.auth.user.is_plearnd_admin">
                                            <span class="mx-1">รีเซ็ตรหัสผ่าน </span>
                                        </DropdownLink>
                                        
                                        <DropdownLink href="/plearnd-admin/supports/donates" v-if="$page.props.auth.user.is_plearnd_admin">
                                            <span class="mx-1">อนุมัติทุนสนับสนุนเว็บ </span>
                                        </DropdownLink>

                                        <div class="border-t border-gray-200 dark:border-gray-600" />

                                        <!-- Authentication -->
                                        <form @submit.prevent="logout">
                                            <DropdownLink as="button" class="m-2 ">
                                                ออกจากระบบ
                                            </DropdownLink>
                                        </form>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="flex items-center -mr-2 sm:hidden">
                            <button class="inline-flex items-center justify-center p-2 text-gray-400 transition duration-150 ease-in-out rounded-md dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400" @click="showingNavigationDropdown = ! showingNavigationDropdown">
                                <svg class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                      :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 6h16M4 12h16M4 18h16" />
                                    <path
                                        :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <!-- <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            แผงจัดการ
                        </ResponsiveNavLink> -->
                    </div>


                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex items-center px-4">
                            <div v-if="$page.props.jetstream.managesProfilePhotos" class="mr-3 shrink-0">
                                <img class="object-cover w-10 h-10 rounded-full" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                            </div>

                            <div>
                                <div class="text-base font-medium text-gray-800 dark:text-gray-200">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="text-sm font-medium text-gray-500">
                                    {{ $page.props.auth.user.email }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.show')" :active="route().current('profile.show')">
                                ข้อมูลส่วนตัว
                            </ResponsiveNavLink>
                            <!-- <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                แผงจัดการ
                            </ResponsiveNavLink> -->
                            <ResponsiveNavLink :href="route('newsfeed')" :active="route().current('newsfeed')">
                                กระดานข่าว
                            </ResponsiveNavLink>
                            <ResponsiveNavLink href="/courses" :active="false">
                                รวมรายวิชา
                            </ResponsiveNavLink>
                            <!-- <ResponsiveNavLink href="/academy" :active="false">
                                รวมแหล่งเรียนรู้
                            </ResponsiveNavLink> -->

                            <!-- Authentication -->
                            <form method="POST" @submit.prevent="logout" >
                                <ResponsiveNavLink as="button" >
                                    ออกจากระบบ
                                </ResponsiveNavLink>
                            </form>

                        </div>
                    </div>
                </div>
            </nav>


            <!-- Page Heading -->
            <header v-if="$slots.header" class="bg-white shadow dark:bg-gray-800">
                <div class="px-4 py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
<style>
@import url('https://fonts.googleapis.com/css2?family=Audiowide&display=swap');

.plearnd-brand {
                font-family: 'Audiowide', sans-serif;
                font-size: 1.5rem;
                font-weight: 400;
                font-style: normal;
            }
</style>
