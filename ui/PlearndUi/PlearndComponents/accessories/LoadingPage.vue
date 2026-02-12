<script setup>
    import { Icon } from '@iconify/vue';
</script>
<template>
    <div class="fixed top-0 left-0 z-50 flex items-center justify-center w-full h-full modal">
        <div class="absolute w-full h-full bg-gray-900 opacity-75 modal-overlay"></div>
        <div class="flex items-center justify-center h-64">
            <Icon icon="svg-spinners:bars-rotate-fade" class="z-30 w-32 h-32 text-white" />
        </div>
    </div>
</template>
