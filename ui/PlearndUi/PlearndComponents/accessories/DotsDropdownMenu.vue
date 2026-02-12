<script setup>
    import { Menu, MenuButton, MenuItem, MenuItems } from "@headlessui/vue";
    import { Icon } from '@iconify/vue';

    const emit = defineEmits([
        'edit-model',
        'delete-model',
        'delete-description',
    ]);

</script>
<template>
    <Menu as="div" class="relative inline-block overflow-visible text-left">
        <div>
            <MenuButton class="inline-flex w-full justify-center gap-x-1.5 rounded-md bg-white/60 px-3 py-1 text-sm font-semibold text-gray-900  hover:bg-gray-50">
                <Icon icon="heroicons-solid:dots-horizontal" class="w-5 h-5 text-gray-400 " aria-hidden="true" />
            </MenuButton>
        </div>

        <transition enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in" leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0">
            <MenuItems
                class="absolute right-0 z-10 w-40 mt-1 origin-top-right bg-white rounded-md shadow-lg top-6 ring-2 ring-black ring-opacity-5 focus:outline-none">
                <div class="p-1">
                    <MenuItem v-slot="{ active }" class="" v-if="$slots.editModel">
                        <button @click.prevent="emit('edit-model')" class="flex items-center justify-start w-full space-x-2"
                            :class="[ active ? 'bg-gray-100 text-gray-900' : 'text-gray-700','block p-2 text-sm', ]">
                            <Icon icon="tabler:edit" class="w-5 h-5 text-red-500 " /> 
                            <slot name="editModel"></slot>
                        </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }" v-if="$slots.deleteModel">
                        <button @click.prevent="emit('delete-model')" class="flex items-center justify-start w-full space-x-2"
                            :class="[ active ? 'bg-gray-100 text-gray-900' : 'text-gray-700','block p-2 text-sm', ]">
                            <Icon icon="heroicons-solid:trash" class="w-5 h-5 text-red-500 " /> 
                            <slot name="deleteModel"></slot>
                        </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }" v-if="$slots.deleteDescription">
                        <button @click.prevent="emit('delete-description')" class="flex items-center justify-start w-full space-x-2"
                            :class="[ active ? 'bg-gray-100 text-gray-900' : 'text-gray-700','block p-2 text-sm', ]">
                            <Icon icon="heroicons-solid:trash" class="w-5 h-5 text-red-500 " /> 
                            <slot name="delete-description"></slot>
                        </button>
                    </MenuItem>
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>
