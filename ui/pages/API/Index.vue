<script setup>
import ApiTokenManager from '@/pages/API/Partials/ApiTokenManager.vue';

definePageMeta({
    layout: 'main',
    middleware: 'auth',
});

defineProps({
    tokens: Array,
    availablePermissions: Array,
    defaultPermissions: Array,
});

useHead({
    title: 'API Tokens',
});
</script>

<template>
    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight mb-6">
                API Tokens
            </h2>

            <ApiTokenManager
                :tokens="tokens"
                :available-permissions="availablePermissions"
                :default-permissions="defaultPermissions"
            />
        </div>
    </div>
</template>

