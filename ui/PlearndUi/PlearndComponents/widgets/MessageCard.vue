<template>

        <div class="bg-white shadow rounded-lg  dark:bg-dark-card">
            <div class="flex flex-row items-center justify-between px-6 pt-6">
                <h4>Message</h4>
                <a href="#" class="text-gray-400">
                    <span>
                        <svg class="icon-24" width="24" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M11.4925 2.78906H7.75349C4.67849 2.78906 2.75049 4.96606 2.75049 8.04806V16.3621C2.75049 19.4441 4.66949 21.6211 7.75349 21.6211H16.5775C19.6625 21.6211 21.5815 19.4441 21.5815 16.3621V12.3341"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            </path>
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M8.82812 10.921L16.3011 3.44799C17.2321 2.51799 18.7411 2.51799 19.6721 3.44799L20.8891 4.66499C21.8201 5.59599 21.8201 7.10599 20.8891 8.03599L13.3801 15.545C12.9731 15.952 12.4211 16.181 11.8451 16.181H8.09912L8.19312 12.401C8.20712 11.845 8.43412 11.315 8.82812 10.921Z"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            </path>
                            <path d="M15.1655 4.60254L19.7315 9.16854" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </span>
                </a>
            </div>
            <div class="p-6">
                <form action="" class="border rounded-lg mb-4 dark:border-secondary-600">
                 <div class="relative xl:flex items-center text-secondary-500 hidden">
                <svg class="w-5 h-5 absolute ltr:right-3 rtl:left-[1rem]" width="18" viewBox="0 0 24 24" fill="none"
                  xmlns="http://www.w3.org/2000/svg">
                  <circle cx="11.7669" cy="11.7666" r="8.98856" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round"></circle>
                  <path d="M18.0186 18.4851L21.5426 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                    stroke-linejoin="round"></path>
                </svg>
                <input type="search"
                  class="dark:bg-dark-card focus:shadow-lg w-full rounded px-3 py-2 border placeholder-slate-400 dark:border-secondary-800 outline-none focus:border-primary-500 dark:focus:border-primary-500"
                  name="search" placeholder="Search..." autocomplete="off">
              </div>
                </form>
                <div class="">
                    <div class="flex flex-wrap" id="wrapper-for-border-blue">
                        <ul class="flex w-full grid-cols-3 mb-2">
                            <li class="w-full">
                                <a class="inline-block px-4 py-2 border-b-4 w-full cursor-pointer text-center text-primary-500 transition-all duration-500 ease-in-out"
                                    data-tab-toggle3="border-tab-profile-blue"
                                    onclick="changeAtiveBorder(event,'wrapper-for-border-blue','primary','border-tab-profile-blue')">Archive</a>
                            </li>
                            <li class="w-full">
                                <a class="inline-block px-4 py-2 border-b-4 w-full cursor-pointer text-center text-primary-500 transition-all duration-500 ease-in-out"
                                    data-tab-toggle3="border-tab-settings-blue"
                                    onclick="changeAtiveBorder(event,'wrapper-for-border-blue','primary','border-tab-settings-blue')">Recent</a>
                            </li>
                            <li class="w-full">
                                <a class="inline-block px-4 py-2 border-b-4 w-full cursor-pointer text-center text-primary-500 transition-all duration-500 ease-in-out"
                                    data-tab-toggle3="border-tab-options-blue"
                                    onclick="changeAtiveBorder(event,'wrapper-for-border-blue','primary','border-tab-options-blue')">New</a>
                            </li>
                        </ul>
                        <div class="relative flex flex-col w-full min-w-0 mb-3 break-words bg-white dark:bg-dark-card">
                            <div class="flex-auto">
                                <div class="border-content border-space">
                                    <div class="block" data-tab-content3="true" id="border-tab-profile-blue">
                                        <div class="w-full">
                                            <div class="grid gap-3">
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/11.png" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">Arman
                                                                                                Rokni </h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/12.png" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">MD
                                                                                                Alidoost</h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/13.png" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">Afshin
                                                                                                Rotni</h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/05.png" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">Salman
                                                                                                Shaha</h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/31.png" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">Vida
                                                                                                Lakhani</h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/06.png" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">Goutham</h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="hidden" data-tab-content3="true" id="border-tab-settings-blue">
                                        <div class="w-full">
                                            <div class="grid gap-3">
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/07.png" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">Arman
                                                                                                Rokni </h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/13.png" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">MD
                                                                                                Alidoost</h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/08.png" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">Afshin
                                                                                                Rotni</h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/04.png" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">Salman
                                                                                                Shaha</h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/03.png" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">Vida
                                                                                                Lakhani</h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/05.png" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">Goutham</h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="hidden" data-tab-content3="true" id="border-tab-settings-blue">
                                        <div class="w-full">
                                            <div class="grid gap-3">
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/06.png" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">Arman
                                                                                                Rokni </h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/08.png" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">MD
                                                                                                Alidoost</h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/13.png" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">Afshin
                                                                                                Rotni</h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/03.png" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">Salman
                                                                                                Shaha</h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/09.jpg" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">Vida
                                                                                                Lakhani</h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 justify-center">
                                                    <img src="storage/images/users/avatar/07.png" class="rounded-full h-12 w-12" alt="">
                                                    <div class="flex grow justify-between items-center">
                                                        <div class="mr-3">
                                                            <h5 class="mb-0">Goutham</h5>
                                                            <small class="text-gray-400">Active 30m</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

</template>

<script setup>

</script>

<style lang="scss" scoped>

</style>
