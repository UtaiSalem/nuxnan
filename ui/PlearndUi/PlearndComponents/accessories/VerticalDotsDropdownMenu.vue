<script setup>
    import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
    import { Icon } from '@iconify/vue';

    const emit = defineEmits(['edit-model','delete-model']);

</script>
<template>
    <Menu as="div" class="relative inline-block text-left overflow-visible ">
        <MenuButton
            class="inline-flex w-full justify-center gap-x-1.5 rounded-md bg-white mx-[2px] px-1 py-3 text-sm font-semibold text-violet-700  hover:bg-gray-100">
            <Icon icon="heroicons-solid:dots-vertical" class=" h-5 w-5 text-violet-700" aria-hidden="true" />
        </MenuButton>

        <transition enter-active-class="transition ease-in-out duration-300"
            enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95">
            <MenuItems
                class="absolute top-6 right-2 z-30 mt-1 w-40 origin-top-right rounded-md bg-white shadow-lg ring-2 ring-black ring-opacity-5 focus:outline-none">
                <div class="p-1">
                    <MenuItem v-slot="{ active }" class="">
                        <button @click.prevent="emit('edit-model')" class="flex justify-start items-center space-x-2 w-full"
                            :class="[ active ? 'bg-gray-100 text-gray-900' : 'text-gray-700','block px-4 py-2 text-sm', ]">
                            <Icon icon="heroicons:pencil-square" class="text-orange-500 w-5 h-5 " />
                            <slot name="editModel"></slot>
                        </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }" class="">
                        <button @click.prevent="emit('delete-model')" class="flex justify-start items-center space-x-2 w-full"
                            :class="[ active ? 'bg-gray-100 text-gray-900' : 'text-gray-700','block px-4 py-2 text-sm', ]">
                            <Icon icon="heroicons-solid:trash" class="text-red-500 w-5 h-5 " />
                            <slot name="deleteModel"></slot>
                        </button>
                    </MenuItem>
                    <!-- <MenuItem v-slot="{ active }">
                        <a href="#" :class="[
                                active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                                'block px-4 py-2 text-sm',
                            ]">Support
                        </a>
                    </MenuItem> -->

                    <!-- <form method="POST" action="#">
                        <MenuItem v-slot="{ active }">
                        <button type="submit" :class="[
                            active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                            'block w-full px-4 py-2 text-left text-sm',
                        ]">
                            Sign out
                        </button>
                        </MenuItem>
                    </form> -->
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>
