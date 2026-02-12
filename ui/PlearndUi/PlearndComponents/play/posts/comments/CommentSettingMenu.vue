<script setup>
import { reactive } from 'vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { Icon } from '@iconify/vue';

const emit = defineEmits(['delete-comment', 'edit-comment']);

const commentSettingMenus = reactive([
    { 
        name: 'แก้ไขความคิดเห็น', 
        icon: 'fa-regular:edit', 
        action: 'edit', 
        color: 'blue-500'
    },
    { 
        name: 'ลบความคิดเห็น', 
        icon: 'fa-solid:trash-alt', 
        action: 'delete', 
        color: 'red-500'
    },
]);

const handleMenuAction = (action) => {
    if (action === 'delete') {
        emit('delete-comment');
    } else if (action === 'edit') {
        emit('edit-comment');
    }
};

</script>

<template>
    <div>
        <Menu as="div" class="relative inline-block text-right ">
            <MenuButton
                class="inline-flex w-full justify-center rounded-md bg-white px-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                <Icon icon="heroicons-outline:dots-horizontal" class="h-5 w-5 text-gray-400" />
            </MenuButton>
            <transition enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95">
                <MenuItems class="absolute right-0 z-10 mt-2 min-w-fit origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                    <div class="py-1">
                        <MenuItem v-slot="{ active }" v-for="(item, index) in commentSettingMenus" :key="index">
                            <button @click.prevent="handleMenuAction(item.action)"
                                :class="[active ? 'bg-gray-100 ' : item.color]"
                                class="flex w-full items-start justify-start gap-2 p-2 px-5 transition-colors duration-300 hover:bg-emerald-50 focus:bg-emerald-50 focus:text-emerald-600 focus:outline-none focus-visible:outline-none">
                                <Icon :icon="item.icon" class="w-4 h-4" :class="'text-' + item.color" />
                                <span class="flex flex-col gap-1 overflow-hidden whitespace-nowrap">
                                    <span class="" :class="'text-'+item.color">{{ item.name }}</span>
                                </span>
                            </button>
                        </MenuItem>
                    </div>
                </MenuItems>
            </transition>
        </Menu>
    </div>
</template>

