<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticationCard from '@/components/AuthenticationCard.vue';
defineProps({
    pageName: String
});
</script>

<template>
    <Head :title="pageName" />
    <AuthenticationCard class="page-bg">
        <div class="rounded-t mb-0 p-4">
            <div class="text-center mb-2">
                <h6 class="text-gray-600 text-2xl font-bold font-prompt">
                    ยินดีต้อนรับสู่ PLEARND!
                </h6>
            </div>
        </div>
        <hr />
        <div class="text-gray-500 text-center text-2xl my-4 font-bold">
            <span class="font-prompt">{{ pageName }}</span>
        </div>
        <slot></slot>

    </AuthenticationCard>
</template>


<style>
.page-bg {
    background-image: url('/storage/landing/landing-background.jpg');
    background-size: cover;
    background-repeat: no-repeat;
}
</style>
