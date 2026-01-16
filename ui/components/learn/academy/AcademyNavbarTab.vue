<template>
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-2 mb-4 flex gap-2 overflow-x-auto">
        <NuxtLink 
            :to="`/Learn/Academy/${academy?.data?.name || academy?.name}`"
            :class="[
                'px-4 py-2 rounded font-medium transition-colors',
                activeTab === 0 ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-bold' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300'
            ]"
        >
            Home
        </NuxtLink>
        <NuxtLink 
            :to="`/Learn/Academy/${academy?.data?.name || academy?.name}/groups`"
            :class="[
                'px-4 py-2 rounded font-medium transition-colors',
                activeTab === 1 ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-bold' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300'
            ]"
        >
            Groups
        </NuxtLink>
        <NuxtLink 
            :to="`/Learn/Academy/${academy?.data?.name || academy?.name}/courses`"
            :class="[
                'px-4 py-2 rounded font-medium transition-colors',
                activeTab === 2 ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-bold' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300'
            ]"
        >
            Courses
        </NuxtLink>
        <NuxtLink 
            :to="`/Learn/Academy/${academy?.data?.name || academy?.name}/members`"
            :class="[
                'px-4 py-2 rounded font-medium transition-colors',
                activeTab === 3 ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-bold' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300'
            ]"
        >
            Members
        </NuxtLink>
    </div>
</template>
<script setup>
defineProps({
    academy: Object,
    activeTab: {
        type: Number,
        default: 0
    }
})
</script>
