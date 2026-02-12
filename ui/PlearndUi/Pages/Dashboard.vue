<script setup>
// import { onMounted } from 'vue';
// import Welcome from '@/Components/Welcome.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
// import { route } from 'vendor/tightenco/ziggy/src/js';

// onMounted(() => {
//     window.location.href = '/newsfeed';
// });
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- <Welcome /> -->
                    <!-- <a href="/newsfeed">Newsfeed</a> -->
                </div>
            </div>
        </div>
    </AppLayout>
</template>
