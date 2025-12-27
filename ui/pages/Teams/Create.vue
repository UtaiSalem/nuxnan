<script setup>
import CreateTeamForm from '@/pages/Teams/Partials/CreateTeamForm.vue';

definePageMeta({
    layout: 'main',
    middleware: 'auth',
});

useHead({
    title: 'Create Team',
});
</script>

<template>
    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight mb-6">
                Create Team
            </h2>

            <CreateTeamForm />
        </div>
    </div>
</template>

