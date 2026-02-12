<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { Icon } from '@iconify/vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    activity: Object,
    postSettingMenus: Array
});

</script>

<template>
    <div>
        <Menu as="div" class="relative inline-block text-right" v-if="postSettingMenus[0].isAuthor">
            <MenuButton
                class="inline-flex w-full justify-center rounded-md bg-white py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                <Icon icon="heroicons-outline:dots-vertical" class="h-5 w-5 text-gray-400" />
            </MenuButton>
            <transition enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95">
                <MenuItems
                    class="absolute right-0 z-10 mt-2 w-40 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                    <div class="py-1">
                        <MenuItem v-slot="{ active }" v-if="postSettingMenus[0].isAuthor">
                            <Link :href="postSettingMenus[0].url"
                                :class="[active ? 'bg-gray-100 ' : postSettingMenus[0].color]"
                                class="flex items-start justify-start gap-2 p-2 px-5 transition-colors duration-300 hover:bg-emerald-50 focus:bg-emerald-50 focus:text-emerald-600 focus:outline-none focus-visible:outline-none">
                                <Icon :icon="postSettingMenus[0].icon" class="w-5 h-5" :class="'text-' + postSettingMenus[0].color" />
                                <span class="flex flex-col gap-1 overflow-hidden whitespace-nowrap">
                                    <span class="leading-5 truncate" :class="'text-'+postSettingMenus[0].color">{{ postSettingMenus[0].name }}</span>
                                </span>
                            </Link>
                        </MenuItem>
                        <MenuItem v-slot="{ active }" v-if="postSettingMenus[1].isAuthor">
                            <Link :href="activity.target_resource.post_url"
                                :class="[active ? 'bg-gray-100 ' : postSettingMenus[1].color]"
                                class="flex items-start justify-start gap-2 p-2 px-5 transition-colors duration-300 hover:bg-emerald-50 focus:bg-emerald-50 focus:text-emerald-600 focus:outline-none focus-visible:outline-none">
                                <Icon :icon="postSettingMenus[1].icon" class="w-5 h-5" :class="'text-' + postSettingMenus[1].color" />
                                <span class="flex flex-col gap-1 overflow-hidden whitespace-nowrap">
                                    <span class="leading-5 truncate" :class="'text-'+postSettingMenus[1].color">{{ postSettingMenus[1].name }}</span>
                                </span>
                            </Link>
                        </MenuItem>
                        <!-- <MenuItem v-slot="{ active }" v-if="!postSettingMenus[2].isAuthor">
                            <Link :href="activity.target_resource.post_url"
                                :class="[active ? 'bg-gray-100 ' : postSettingMenus[2].color]"
                                class="flex items-start justify-start gap-2 p-2 px-5 transition-colors duration-300 hover:bg-emerald-50 focus:bg-emerald-50 focus:text-emerald-600 focus:outline-none focus-visible:outline-none">
                                <Icon :icon="postSettingMenus[2].icon" class="w-5 h-5" :class="'text-' + postSettingMenus[2].color" />
                                <span class="flex flex-col gap-1 overflow-hidden whitespace-nowrap">
                                    <span class="leading-5 truncate" :class="'text-'+postSettingMenus[2].color">{{ postSettingMenus[2].name }}</span>
                                </span>
                            </Link>
                        </MenuItem> -->

                    </div>
                </MenuItems>
            </transition>
        </Menu>
    </div>
</template>
