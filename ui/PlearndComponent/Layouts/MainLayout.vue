<script setup>
    import { ref, onMounted } from 'vue';
    import { Link, Head, usePage, router } from '@inertiajs/vue3';
    import { initFlowbite } from 'flowbite';
    import { Icon } from '@iconify/vue';
    // import SVGJoinGroup from '@/Components/SVGIcons/Join Group Icon.svg';

    import Dropdown from '@/Components/Dropdown.vue';
    import DropdownLink from '@/Components/DropdownLink.vue';
    import LoadingPage from '@/PlearndComponents/accessories/LoadingPage.vue';
    import MobileNavbar from '@/PlearndComponents/accessories/MobileNavbar.vue';
    // import FlashSidebarLeft from '@/PlearndComponents/partials/FlashSidebarLeft.vue';


    defineProps({
        title: String,
    });
    
    const authUser  = ref(usePage().props.auth.user);
    const isOpenSettingMenu = ref(false);
    const isSidebarOpen = ref(false);
    const isLoadingPage = ref(false);
    const currentColumn = ref(2);

    const isDarkMode = ref(false);

    const navigation = [
        // { name: 'แผงจัดการ',     href: '/dashboard', },
        // { name: 'แหล่งเรียนรู้',     href: '/academies', icon: 'teenyicons:school-outline'},
        { name: 'กระดานข่าว',        href: '/newsfeed',              icon: 'fluent:feed-24-regular',},
        { name: 'รายวิชา',           href: '/courses',               icon: 'fluent-mdl2:publish-course'},
        { name: 'สะสมแต้ม',          href: '/donates',               icon: 'mdi:hand-coin-outline'},
        { name: 'ดูสินค้า',            href: '/supports',              icon: 'eos-icons:product-subscriptions-outlined'},

    ];


    onMounted(() => {
        initFlowbite();
        // checkScreenWidth();
    });

    //a function to get screen width and set isSidebarOpen value if the screen width is lg or md
    const checkScreenWidth = () => {
        if (window.innerWidth > 1024) {
            isSidebarOpen.value = true;
        }
    };

    const handleLinkToPage = (href) => {
        isLoadingPage.value = true;
        router.visit(href);
    };

    const toggleSettingMenu = () => isOpenSettingMenu.value = !isOpenSettingMenu.value;  

    const logout = () => {
        router.post(route('logout'));
    };

    // set current column value
    const setCurrentColumn = (column) => {
        currentColumn.value = column;
        console.log(currentColumn.value);
    };


</script>
<template>
    <!-- <div class="min-h-screen bg-slate-300"> -->
    <div class="min-h-screen mx-auto bg-slate-200 dark:bg-indigo-950" :class="{'dark': isDarkMode}">

        <LoadingPage v-if="isLoadingPage" />
        
        <!-- Page Heading -->
        <Head>
            <title>{{ title }}</title>
        </Head>

        <!-- Navbar -->
        <header class="fixed top-0 h-[64px] sm:shadow-md pl-2 flex justify-between w-full items-center z-30 bg-[#34465d]">
            <!-- logo && Title -->
            <div class="flex items-center md:justify-between min-w-fit">
                <Link :href="route('welcome')"> 
                    <img :src="'/storage/images/plearnd-logo.png'" alt="" class="flex w-12 h-12" />
                </Link>
                <Link :href="route('welcome')" class="items-center justify-between hidden space-x-3 md:flex ">
                    <span class="px-2 text-2xl font-semibold text-white rounded-lg plearnd-brand bg-violet-500">Plearnd</span>
                </Link>
                <button 
                type="button" 
                title="Plearnd-Hamburger-Menu" 
                class="text-gray-400 " 
                @click.prevent="isSidebarOpen = !isSidebarOpen"
                >
                    <Icon icon="solar:hamburger-menu-bold" class="w-9 h-9" />
                </button>
            </div>

            <!-- Navigation Menus -->
            <div class="items-center justify-center hidden w-1/3 md:flex">
                <div class="flex space-x-1">
                    <button v-for="navbarMenu in navigation" :key="navbarMenu.name" @click.prevent="handleLinkToPage(navbarMenu.href)"
                        :class="[$page.url.startsWith(navbarMenu.href) ? 'bg-violet-500 text-white' : 'text-gray-300 hover:bg-violet-700 hover:text-white', 'flex items-center justify-center rounded-md px-3 py-2 text-sm font-medium']"
                        :aria-current="navbarMenu.current ? 'page' : undefined">
                        <Icon :icon="navbarMenu.icon" class="w-5 h-5 " />
                        <span class="hidden xl:block">
                            {{ navbarMenu.name }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- User wallet -->
            <div class="items-center space-x-1 text-white flex">
                <div class="plearn-navbar-icon min-w-fit">
                    <Link :href="route('donates.list')" class="flex items-center">
                        <img :src="'/storage/images/badge/completedq.png'" alt="completedq-b" class="w-8 h-8">
                        <span>{{ authUser.pp.toLocaleString() }}</span><span class="hidden ml-1 md:flex">แต้ม</span>
                    </Link>
                </div>
                <div class="plearn-navbar-icon min-w-fit">
                    <img :src="'/storage/images/badge/goldc.png'" alt="completedq-b" class="w-8 h-8">
                    <span>{{ authUser.wallet.toLocaleString() }}</span> <span class="hidden ml-1 md:flex">บาท</span>
                </div>
            </div>

            <!-- Setting dropdown menus -->
            <div class="flex items-center justify-end space-x-2 md:w-1/2 lg:w-1/4 sm:w-24 min-w-fit">
                <div class="hidden font-semibold text-white lg:flex dark:text-white">
                    <div>
                        <Link :href="'#'" >{{ authUser.name }}</Link>
                    </div>
                </div>
                <div class="relative avatar online">
                    <Link :href="'#'" class="hidden border-2 border-white rounded-full sm:block">
                        <img class="object-cover w-8 h-8 rounded-full" :src="authUser.profile_photo_url" :alt="authUser.name" />
                    </Link>
                    <!-- <span class="bottom-3 left-7 absolute w-2.5 h-2.5 bg-green-400 border-2 border-white dark:border-gray-800 rounded-full"></span> -->
                </div>

                <Dropdown align="right" width="56">
                    <template #trigger>
                        <button v-if="$page.props.jetstream.managesProfilePhotos"
                            class="flex overflow-hidden text-sm transition border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300">
                            <!-- <img class="object-cover w-8 h-8 rounded-full" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name"> -->
                            <div class="inline-flex items-center p-1 text-sm font-medium leading-4 text-gray-200 transition duration-150 ease-in-out border border-transparent rounded-md dark:text-gray-400 dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700">
                                <!-- {{ $page.props.auth.user.name }} -->
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                    class="w-6 h-6">
                                    <path fill-rule="evenodd"
                                        d="M11.078 2.25c-.917 0-1.699.663-1.85 1.567L9.05 4.889c-.02.12-.115.26-.297.348a7.493 7.493 0 00-.986.57c-.166.115-.334.126-.45.083L6.3 5.508a1.875 1.875 0 00-2.282.819l-.922 1.597a1.875 1.875 0 00.432 2.385l.84.692c.095.078.17.229.154.43a7.598 7.598 0 000 1.139c.015.2-.059.352-.153.43l-.841.692a1.875 1.875 0 00-.432 2.385l.922 1.597a1.875 1.875 0 002.282.818l1.019-.382c.115-.043.283-.031.45.082.312.214.641.405.985.57.182.088.277.228.297.35l.178 1.071c.151.904.933 1.567 1.85 1.567h1.844c.916 0 1.699-.663 1.85-1.567l.178-1.072c.02-.12.114-.26.297-.349.344-.165.673-.356.985-.57.167-.114.335-.125.45-.082l1.02.382a1.875 1.875 0 002.28-.819l.923-1.597a1.875 1.875 0 00-.432-2.385l-.84-.692c-.095-.078-.17-.229-.154-.43a7.614 7.614 0 000-1.139c-.016-.2.059-.352.153-.43l.84-.692c.708-.582.891-1.59.433-2.385l-.922-1.597a1.875 1.875 0 00-2.282-.818l-1.02.382c-.114.043-.282.031-.449-.083a7.49 7.49 0 00-.985-.57c-.183-.087-.277-.227-.297-.348l-.179-1.072a1.875 1.875 0 00-1.85-1.567h-1.843zM12 15.75a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </template>

                    <template #content>
                        <div class="overflow-hidden w-60">
                            <DropdownLink href="#">
                                <div
                                    class="flex items-center justify-between text-sm text-gray-600 transition-colors duration-300 transform dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                                    <img class="object-cover w-12 h-12 mx-1 rounded-full"
                                        :src="authUser.profile_photo_url" :alt="authUser.name" />
                                    <div class="mx-1">
                                        <h1 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                            {{ authUser.name }}
                                        </h1>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ authUser.email}}</p>
                                    </div>
                                </div>
                            </DropdownLink>

                            <hr class="border-gray-200 dark:border-gray-700" />

                            <DropdownLink :href="route('profile.show')">
                                <div
                                    class="flex items-center p-1 text-sm text-gray-600 capitalize transition-colors duration-300 transform dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                                    <svg class="w-5 h-5 mx-1" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M7 8C7 5.23858 9.23858 3 12 3C14.7614 3 17 5.23858 17 8C17 10.7614 14.7614 13 12 13C9.23858 13 7 10.7614 7 8ZM12 11C13.6569 11 15 9.65685 15 8C15 6.34315 13.6569 5 12 5C10.3431 5 9 6.34315 9 8C9 9.65685 10.3431 11 12 11Z"
                                            fill="currentColor"></path>
                                        <path
                                            d="M6.34315 16.3431C4.84285 17.8434 4 19.8783 4 22H6C6 20.4087 6.63214 18.8826 7.75736 17.7574C8.88258 16.6321 10.4087 16 12 16C13.5913 16 15.1174 16.6321 16.2426 17.7574C17.3679 18.8826 18 20.4087 18 22H20C20 19.8783 19.1571 17.8434 17.6569 16.3431C16.1566 14.8429 14.1217 14 12 14C9.87827 14 7.84344 14.8429 6.34315 16.3431Z"
                                            fill="currentColor"></path>
                                    </svg>
                                    <span class="mx-1"> ข้อมูลส่วนตัว </span>
                                </div>
                            </DropdownLink>

                            <!-- <DropdownLink :href="route('dashboard')">
                                <div
                                    class="flex items-center p-1 text-sm text-gray-600 capitalize transition-colors duration-300 transform dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                                    <svg class="w-5 h-5 mx-1" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M7 8C7 5.23858 9.23858 3 12 3C14.7614 3 17 5.23858 17 8C17 10.7614 14.7614 13 12 13C9.23858 13 7 10.7614 7 8ZM12 11C13.6569 11 15 9.65685 15 8C15 6.34315 13.6569 5 12 5C10.3431 5 9 6.34315 9 8C9 9.65685 10.3431 11 12 11Z"
                                            fill="currentColor"></path>
                                        <path
                                            d="M6.34315 16.3431C4.84285 17.8434 4 19.8783 4 22H6C6 20.4087 6.63214 18.8826 7.75736 17.7574C8.88258 16.6321 10.4087 16 12 16C13.5913 16 15.1174 16.6321 16.2426 17.7574C17.3679 18.8826 18 20.4087 18 22H20C20 19.8783 19.1571 17.8434 17.6569 16.3431C16.1566 14.8429 14.1217 14 12 14C9.87827 14 7.84344 14.8429 6.34315 16.3431Z"
                                            fill="currentColor"></path>
                                    </svg>
                                    <span class="mx-1"> แผงควบคุม </span>
                                </div>
                            </DropdownLink> -->

                            <DropdownLink :href="route('newsfeed')">
                                <div
                                    class="flex items-center p-1 text-sm text-gray-600 capitalize transition-colors duration-300 transform dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                                    <Icon icon="solar:card-send-broken" class="w-5 h-5 mx-1" />
                                    <span class="mx-1"> กระดานข่าว </span>
                                </div>
                            </DropdownLink>

                            <DropdownLink href="/courses">
                                <div
                                    class="flex items-center p-1 text-sm text-gray-600 capitalize transition-colors duration-300 transform dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                                    <Icon icon="solar:card-send-broken" class="w-5 h-5 mx-1" />
                                    <span class="mx-1"> รวมรายวิชา </span>
                                </div>
                            </DropdownLink>

                            <!-- <DropdownLink href="/academies">
                                <div
                                    class="flex items-center p-1 text-sm text-gray-600 capitalize transition-colors duration-300 transform dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                                    <Icon icon="solar:card-send-broken" class="w-5 h-5 mx-1" />
                                    <span class="mx-1"> รวมแหล่งเรียนรู้ </span>
                                </div>
                            </DropdownLink> -->

                            <DropdownLink href="/supports/advertises/create">
                                <div
                                    class="flex items-center p-1 text-sm text-gray-600 capitalize transition-colors duration-300 transform dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                                    <Icon icon="solar:card-send-broken" class="w-5 h-5 mx-1" />
                                    <span class="mx-1">ประชาสัมพันธ์สินค้า </span>
                                </div>
                            </DropdownLink>

                            <DropdownLink href="/supports/donates/create">
                                <div
                                    class="flex items-center p-1 text-sm text-gray-600 capitalize transition-colors duration-300 transform dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                                    <Icon icon="solar:card-send-broken" class="w-5 h-5 mx-1" />
                                    <span class="mx-1">สนับสนุนทุนการศึกษา </span>
                                </div>
                            </DropdownLink>

                            <!-- <DropdownLink href="/supports/loan">
                                <div
                                    class="flex items-center p-1 text-sm text-gray-600 capitalize transition-colors duration-300 transform dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                                    <Icon icon="solar:card-send-broken" class="w-5 h-5 mx-1" />
                                    <span class="mx-1">สนับสนุนทุนการศึกษา </span>
                                </div>
                            </DropdownLink> -->

                            <DropdownLink href="/forgot-password" v-if="$page.props.auth.user.is_plearnd_admin">
                                <div
                                    class="flex items-center p-1 text-sm text-gray-600 capitalize transition-colors duration-300 transform dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                                    <Icon icon="solar:card-send-broken" class="w-5 h-5 mx-1" />
                                    <span class="mx-1">รีเซ็ตรหัสผ่าน </span>
                                </div>
                            </DropdownLink>

                            <DropdownLink href="/plearnd-admin/supports/advertises" v-if="$page.props.auth.user.is_plearnd_admin">
                                <div
                                    class="flex items-center p-1 text-sm text-gray-600 capitalize transition-colors duration-300 transform dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                                    <Icon icon="solar:card-send-broken" class="w-5 h-5 mx-1" />
                                    <span class="mx-1">อนุมัติโฆษณา </span>
                                </div>
                            </DropdownLink>

                            <DropdownLink href="/plearnd-admin/supports/donates" v-if="$page.props.auth.user.is_plearnd_admin">
                                <div
                                    class="flex items-center p-1 text-sm text-gray-600 capitalize transition-colors duration-300 transform dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white">
                                    <Icon icon="solar:card-send-broken" class="w-5 h-5 mx-1" />
                                    <span class="mx-1">อนุมัติทุนสนับสนุนเว็บ </span>
                                </div>
                            </DropdownLink>

                            <hr class="border-t border-gray-200 dark:border-gray-600" />

                            <div class="flex items-center justify-center p-2 ">
                                <button @click.prevent="logout"
                                    class="flex items-center justify-center w-full p-2 space-x-2 font-bold text-indigo-500 rounded-md font-prompt hover:bg-indigo-200">
                                    <span>
                                        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                    </span>
                                    <span>ออกจากระบบ</span>
                                </button>
                            </div>

                        </div>
                    </template>
                </Dropdown>
            </div>

            <!-- <div>
                
                <button type="button" title="Plearnd-dark-mode-Setting" class="mt-1 text-gray-200" @click.prevent="isDarkMode = !isDarkMode">
                    <Icon icon="line-md:moon-loop" class="w-8 h-8" v-if="!isDarkMode" />
                    <Icon icon="line-md:sun-rising-loop" class="w-8 h-8" v-if="isDarkMode" />
                </button> 
               
            </div> -->

            <!-- 
            <div class="items-center justify-between hidden space-x-3 md:flex">

            </div> 
            -->

        </header>

        <MobileNavbar 
            :navigation="navigation"
            @go-to-page="(url)=>handleLinkToPage(url)"
            @set-current-active-column="(column)=> setCurrentColumn(column)"
        />

        <!-- left Sidbar -->
        <div v-if="$slots.flashSidebarLeft">
            <slot name="flashSidebarLeft" />
        </div>

        <!-- Main content -->
        <div :class="$slots.flashSidebarLeft ? 'ml-64' : ''" class="flex flex-col justify-between sm:mx-4 md:pt-16 rounded-lg">

            <div v-if="$slots.coverProfileCard" class="w-full" >
                <slot name="coverProfileCard"></slot>
            </div>

            <div class="flex justify-center gap-4 md:justify-between" >

                <!-- Sidebar start-->
                <div class="hidden md:block lg:w-[35%] md:w-[40%]">
                    <div v-if="$slots.leftSideWidget">
                        <slot name="leftSideWidget" />
                    </div>
                </div>
                <!-- Sidebar end -->

                <!-- content -->
                <div class="w-full mb-4">
                    <main>
                        <slot name="mainContent"></slot>
                    </main>
                </div>
                <!-- End content -->

                <!-- right sidebar -->
                <div class="hidden lg:block w-[35%]">
                    <div v-if="$slots.rightSideWidget">
                        <slot name="rightSideWidget" />
                    </div>
                </div>
                <!--End right sidebar -->
            </div>
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
