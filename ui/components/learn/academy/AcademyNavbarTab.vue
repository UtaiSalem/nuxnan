<template>
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-2 mb-4 flex gap-2 overflow-x-auto scrollbar-hide">
        <NuxtLink 
            :to="`/Learn/Academy/${academy?.data?.name || academy?.name}`"
            :class="[
                'px-4 py-2 rounded-lg font-bold text-sm transition-all duration-300 whitespace-nowrap',
                activeTab === 0 
                    ? 'bg-gradient-to-r from-blue-600 to-cyan-500 text-white shadow-md' 
                    : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300'
            ]"
        >
            หน้าหลัก
        </NuxtLink>
        <NuxtLink 
            :to="`/Learn/Academy/${academy?.data?.name || academy?.name}/groups`"
            :class="[
                'px-4 py-2 rounded-lg font-bold text-sm transition-all duration-300 whitespace-nowrap',
                activeTab === 1 
                    ? 'bg-gradient-to-r from-blue-600 to-cyan-500 text-white shadow-md' 
                    : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300'
            ]"
        >
            กลุ่ม
        </NuxtLink>
        <NuxtLink 
            :to="`/Learn/Academy/${academy?.data?.name || academy?.name}/courses`"
            :class="[
                'px-4 py-2 rounded-lg font-bold text-sm transition-all duration-300 whitespace-nowrap',
                activeTab === 2 
                    ? 'bg-gradient-to-r from-blue-600 to-cyan-500 text-white shadow-md' 
                    : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300'
            ]"
        >
            รายวิชา
        </NuxtLink>
        <NuxtLink 
            :to="`/Learn/Academy/${academy?.data?.name || academy?.name}/members`"
            :class="[
                'px-4 py-2 rounded-lg font-bold text-sm transition-all duration-300 whitespace-nowrap',
                activeTab === 3 
                    ? 'bg-gradient-to-r from-blue-600 to-cyan-500 text-white shadow-md' 
                    : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300'
            ]"
        >
            สมาชิก
        </NuxtLink>
        <NuxtLink 
            :to="`/Learn/Academy/${academy?.data?.name || academy?.name}/curriculum`"
            :class="[
                'px-4 py-2 rounded-lg font-bold text-sm transition-all duration-300 whitespace-nowrap',
                activeTab === 4 
                    ? 'bg-gradient-to-r from-blue-600 to-cyan-500 text-white shadow-md' 
                    : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300'
            ]"
        >
            หลักสูตร
        </NuxtLink>
        <NuxtLink 
            v-if="isAcademyAdmin"
            :to="`/Learn/Academy/${academy?.data?.name || academy?.name}/Settings`"
            :class="[
                'px-4 py-2 rounded-lg font-bold text-sm transition-all duration-300 whitespace-nowrap',
                activeTab === 5 
                    ? 'bg-gradient-to-r from-purple-600 to-indigo-500 text-white shadow-md' 
                    : 'hover:bg-purple-50 dark:hover:bg-purple-900/20 text-purple-600 dark:text-purple-400'
            ]"
        >
            บริหารจัดการ
        </NuxtLink>
    </div>
</template>
<script setup>
defineProps({
    academy: Object,
    activeTab: {
        type: Number,
        default: 0
    },
    isAcademyAdmin: Boolean
})
</script>
<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

