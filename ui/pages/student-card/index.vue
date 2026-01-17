<script setup lang="ts">
import { ref } from 'vue'

useHead({
  title: 'ระบบบัตรนักเรียน'
})

definePageMeta({
  layout: false
})

const isNavigating = ref(false)
const activeTab = ref(0)
const mattayomLevels = [
    { id: 0, name: 'ม.1', rooms: 11, color: 'blue' },
    { id: 1, name: 'ม.2', rooms: 9, color: 'blue' },
    { id: 2, name: 'ม.3', rooms: 9, color: 'blue' },
    { id: 3, name: 'ม.4', rooms: 8, color: 'blue' },
    { id: 4, name: 'ม.5', rooms: 7, color: 'blue' },
    { id: 5, name: 'ม.6', rooms: 7, color: 'blue' },
]
const currentLevel = ref(mattayomLevels[0])

const handleSelectLevel = (levelId: number) => {
    activeTab.value = levelId;
    currentLevel.value = mattayomLevels[levelId];
}

const getClassrooms = (levelId: number) => {
    const level = mattayomLevels[levelId]
    const rooms = []
    for (let i = 1; i <= level.rooms; i++) {
        rooms.push({
            id: i,
            name: `${i}`,
            fullName: `${level.name}/${i}`,
            link: `/student-card/${levelId+1}/${i}`,
            levelId: level.id,
        })
    }
    return rooms
}

const handleSelectRoom = (link: string) => {
    isNavigating.value = true;
    navigateTo(link)
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-100 via-white to-blue-200 relative overflow-x-hidden">
        
        <!-- Background decorations -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-blue-200 rounded-full opacity-30 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-blue-300 rounded-full opacity-20 blur-3xl"></div>
        </div>

        <!-- Loading Overlay -->
        <div v-if="isNavigating" class="fixed inset-0 bg-white/80 backdrop-blur-sm z-50 flex items-center justify-center">
            <div class="text-center">
                <div class="w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-blue-600 font-medium">กำลังโหลด...</p>
            </div>
        </div>

        <div class="relative z-10 p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <div class="max-w-5xl mx-auto mb-8">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                        </svg>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">ระบบบัตรนักเรียน</h1>
                    <p class="text-gray-600">เลือกระดับชั้นและห้องเรียนเพื่อดูบัตรนักเรียน</p>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="max-w-5xl mx-auto mb-6">
                <div class="flex flex-wrap justify-center gap-3">
                    <NuxtLink 
                        to="/student-card/admin" 
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white/80 backdrop-blur rounded-lg shadow-sm hover:shadow-md transition-all text-gray-700 hover:text-blue-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        จัดการบัตรนักเรียน (Admin)
                    </NuxtLink>
                </div>
            </div>

            <!-- Level Tabs -->
            <div class="max-w-5xl mx-auto mb-6">
                <div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg p-2">
                    <div class="flex flex-wrap justify-center gap-2">
                        <button
                            v-for="level in mattayomLevels"
                            :key="level.id"
                            @click="handleSelectLevel(level.id)"
                            :class="[
                                'px-6 py-3 rounded-xl font-semibold transition-all duration-200',
                                activeTab === level.id 
                                    ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-md' 
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            ]"
                        >
                            {{ level.name }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Room Grid -->
            <div class="max-w-5xl mx-auto">
                <div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </span>
                        เลือกห้องเรียน - {{ currentLevel.name }}
                    </h2>
                    
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3">
                        <button
                            v-for="room in getClassrooms(activeTab)"
                            :key="room.id"
                            @click="handleSelectRoom(room.link)"
                            class="group relative bg-gradient-to-br from-blue-50 to-white border-2 border-blue-100 rounded-xl p-4 hover:border-blue-400 hover:shadow-lg transition-all duration-200"
                        >
                            <div class="text-center">
                                <span class="block text-2xl font-bold text-blue-600 group-hover:text-blue-700">{{ room.name }}</span>
                                <span class="text-xs text-gray-500">{{ room.fullName }}</span>
                            </div>
                            <div class="absolute inset-0 bg-blue-500/5 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="max-w-5xl mx-auto mt-8 text-center text-gray-500 text-sm">
                <p>© {{ new Date().getFullYear() }} ระบบบัตรนักเรียน - โรงเรียนจอมสุรางค์อุปถัมภ์</p>
            </div>
        </div>
    </div>
</template>
