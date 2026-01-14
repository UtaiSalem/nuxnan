<template>
  <div class="space-y-6">
    <div v-for="group in groups" :key="group.id" 
         class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 p-6 shadow-lg rounded-xl border border-gray-100 dark:border-gray-700">
        <!-- Group Header -->
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="p-2 bg-indigo-100 dark:bg-indigo-900 rounded-lg mr-3">
                    <Icon icon="heroicons:user-group-solid" class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div>
                    <h4 class="font-bold text-lg text-gray-800 dark:text-white">{{ group.name }}</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ group.description || 'ไม่มีคำอธิบาย' }}</p>
                </div>
            </div>
            <div class="flex items-center px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/50 rounded-full">
                <Icon icon="heroicons:users" class="w-4 h-4 text-indigo-600 dark:text-indigo-400 mr-1.5" />
                <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">
                    {{ group.members?.length || 0 }} สมาชิก
                </span>
            </div>
        </div>
        
        <!-- Members Grid -->
        <div v-if="group.members && group.members.length > 0" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div v-for="member in group.members" :key="member.id" 
                 class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center">
                    <!-- Avatar -->
                    <img 
                        :src="member.avatar || member.user?.avatar || '/images/default-avatar.png'"
                        :alt="member.name || member.user?.name || 'Member'"
                        class="w-12 h-12 rounded-full ring-2 ring-indigo-100 dark:ring-indigo-900"
                    />
                    <div class="ml-3 flex-1 min-w-0">
                        <!-- Name -->
                        <p class="font-semibold text-gray-800 dark:text-white truncate">
                            {{ member.member_name || member.name || member.user?.name || 'ไม่ระบุชื่อ' }}
                        </p>
                        <!-- Order Number & Role -->
                        <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            <span v-if="member.order_number" class="flex items-center mr-3">
                                <Icon icon="fluent:number-symbol-square-20-filled" class="w-4 h-4 mr-1" />
                                {{ member.order_number }}
                            </span>
                            <span v-if="member.member_code" class="flex items-center">
                                <Icon icon="heroicons:identification" class="w-4 h-4 mr-1" />
                                {{ member.member_code }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Empty State -->
        <div v-else class="text-center py-8 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
            <Icon icon="heroicons:user-minus" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
            <p class="text-gray-400 dark:text-gray-500">ยังไม่มีสมาชิกในกลุ่มนี้</p>
        </div>
    </div>
    
    <!-- Empty Groups State -->
    <div v-if="!groups || groups.length === 0" class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-xl">
        <Icon icon="heroicons:user-group" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
        <p class="text-gray-500 dark:text-gray-400 text-lg">ยังไม่มีกลุ่มในรายวิชานี้</p>
    </div>
  </div>
</template>
<script setup>
import { Icon } from '@iconify/vue';

defineProps({
    groups: Array
})
</script>
