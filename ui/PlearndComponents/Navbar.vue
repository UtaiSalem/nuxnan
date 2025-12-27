<script setup>
    import { ref, computed } from 'vue'
    import { Disclosure, DisclosureButton, DisclosurePanel} from '@headlessui/vue'
    import { Icon } from '@iconify/vue';
    import { Link,router, usePage } from '@inertiajs/vue3';

    defineProps({
        isDarkMode: Boolean
    });
    // const isDarkMode = ref(false);
    const navigation = [
        { name: 'แผงจัดการ',     href: '/dashboard', current: true },
        { name: 'กระดานข่าว',    href: '/newsfeed', current: false },
        { name: 'รายวิชา',       href: '/courses', current: false },
        // { name: 'Calendar',     href: '#', current: false },
    ];

    const logout = () => {
        router.post(route('logout'));
    };
</script>

<template>
    <div>
        <Disclosure as="nav" :class="[ isDarkMode ?  'bg-indigo-950': 'bg-white']" class="shadow-md fixed w-full top-0 z-50" v-slot="{ open }">
            <div class="mx-auto max-w-7xl px-2 sm:px-6 lg:px-8">
                <div class="relative flex h-16 items-center justify-between">
                        <!-- Mobile menu button-->
                        <DisclosureButton
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                            <span class="sr-only">Open main menu</span>
                            <Icon icon="heroicons-outline:menu" v-if="!open" class="block h-6 w-6" aria-hidden="true" />
                            <Icon icon="heroicons-outline:x" v-else class="block h-6 w-6" aria-hidden="true" />
                        </DisclosureButton>
                        <div class="flex flex-1 items-center justify-center sm:items-stretch sm:justify-start">
                            <div class="hidden md:flex flex-shrink-0 items-center">
                                <img class="block h-8 w-auto "
                                    src="https://tailwindui.com/img/logos/mark.svg?color=indigo&shade=500" alt="Your Company" />
                            </div>
                            <div class=" flex items-center mx-4 ">

                            </div>
                                <div class="hidden md:ml-6 md:block">
                                    <div class="flex space-x-4">
                                        <a v-for="item in navigation" :key="item.name" :href="item.href"
                                            :class="[item.current ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white', 'rounded-md px-3 py-2 text-sm font-medium']"
                                            :aria-current="item.current ? 'page' : undefined">{{ item.name }}</a>
                                    </div>
                                </div>
                            </div>
                            <div class="hidden sm:flex sm:justify-center space-x-2 ">
                                <div class="flex items-center space-x-2">
                                    <Icon  icon="noto:coin" class="h-6 w-6 rounded-full" aria-hidden="true" />
                                    <span class="text-sm dark:text-gray-200">{{ $page.props.auth.user.pp.toLocaleString() }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Icon  icon="emojione:money-bag" class="h-6 w-6 rounded-full" aria-hidden="true" />
                                    <span class="text-sm dark:text-gray-200">{{ $page.props.auth.user.wallet.toLocaleString() }}</span>
                                </div>
                            </div>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0 space-x-2">
                                <!-- <button type="button" @click.prevent="$emit('toggleDarkMode')"
                                    class="rounded-full bg-gray-200 p-[6px] text-gray-400 hover:text-white focus:outline-none ">
                                    <span class="sr-only">View notifications</span>
                                    <Icon :icon="`heroicons-outline:${isDarkMode ? 'sun': 'moon' }`" class="h-6 w-6" aria-hidden="true" />
                                </button>
                                <button type="button"
                                    class="rounded-full bg-gray-200 p-[6px] text-gray-400 hover:text-white focus:outline-none ">
                                    <span class="sr-only">View notifications</span>
                                    <Icon icon="heroicons-outline:bell" class="h-6 w-6" aria-hidden="true" />
                                </button> -->

                                <!-- Profile dropdown -->
                                <!-- <Menu as="div" class="relative ml-3">
                                    <div>
                                        <MenuButton
                                            class="flex rounded-full bg-gray-800 text-sm  ">
                                            <span class="sr-only">Open user menu</span>
                                            <img class="h-[42px] w-[42px] rounded-full overflow-hidden border-2 dark:border-white border-gray-400" :src="$page.props.auth.user.profile_photo_url" alt="" />
                                        </MenuButton>
                                    </div>
                                    <transition enter-active-class="transition ease-out duration-200"
                                        enter-from-class="transform opacity-0 scale-95"
                                        enter-to-class="transform opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="transform opacity-100 scale-100"
                                        leave-to-class="transform opacity-0 scale-95">
                                        <MenuItems
                                            class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                            <MenuItem v-slot="{ active }">
                                            <a href="#"
                                                :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-gray-700']">Your
                                                Profile</a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }">
                                            <a href="#"
                                                :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-gray-700']">Settings</a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }">
                                            <a href="/logout" :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-gray-700']">Sign out</a>
                                            <form method="POST" @submit.prevent="logout">
                                                <button type="submit" :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-gray-700']">
                                                    Logout
                                                </button>                                     
                                            </form>
                                            </MenuItem>
                                        </MenuItems>
                                    </transition>
                                </Menu> -->

                                <button id="dropdownAvatarNameButton" data-dropdown-toggle="dropdownAvatarName" class="flex items-center text-sm pr-2 font-medium text-gray-900 rounded-full hover:text-blue-600 dark:hover:text-blue-500 md:mr-0 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:text-white" type="button">
                                    <span class="sr-only">Open user menu</span>
                                    <img class="w-8 h-8 mr-2 rounded-full" :src="$page.props.auth.user.profile_photo_url" alt="user photo">
                                        <span class="hidden sm:block">{{  $page.props.auth.user.name }}</span>
                                    <svg class="w-2.5 h-2.5 ml-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                                    </svg>
                                </button>

                                <!-- Dropdown menu -->
                                <div id="dropdownAvatarName" class="z-10 border hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700 dark:divide-gray-600">
                                    <div class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                        <div class="font-medium ">{{  $page.props.auth.user.name }}</div>
                                        <div class="truncate">{{  $page.props.auth.user.email }}</div>
                                    </div>

                                    <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownInformdropdownAvatarNameButtonationButton">
                                        <li>
                                            <Link href="/dashboard" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">แผงจัดการ</Link>
                                        </li>
                                        <li>
                                            <Link :href="route('profile.show')" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">ข้อมูลส่วนตัว</Link>
                                        </li>
                                        <!-- <li>
                                            <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Earnings</a>
                                        </li> -->
                                    </ul>

                                    <div class="py-2">
                                    <!-- <a :href="router.route('logout')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">Sign out</a> -->
                                        <form @submit.prevent="logout()" class="">
                                            <button type="submit" id="logout-form" class="block w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">
                                                <span class="flex items-center">
                                                    <Icon icon="majesticons:door-exit-line" class="mx-2" /> ออกจากระบบ
                                                </span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <DisclosurePanel class=" absolute bg-gray-800 w-64 rounded-lg">
                        <div class="space-y-1 px-2 pb-3 pt-2">
                            <DisclosureButton v-for="item in navigation" :key="item.name" as="a" :href="item.href"
                                :class="[item.current ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white', 'block rounded-md px-3 py-2 text-base font-medium']"
                                :aria-current="item.current ? 'page' : undefined">{{ item.name }}
                            </DisclosureButton>
                        </div>
                    </DisclosurePanel>
        </Disclosure>
    </div>
</template>
