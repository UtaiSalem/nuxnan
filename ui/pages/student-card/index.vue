<script setup lang="ts">
import { ref, onMounted } from 'vue'

useHead({
  title: 'ระบบบัตรนักเรียน'
})

definePageMeta({
  layout: false
})

const isNavigating = ref(false)
const isLoading = ref(true)
const activeTab = ref(0)
const config = useRuntimeConfig()

interface DynamicLevel {
    level: string;
    name: string;
    sections: string[];
    studentCount: number;
}

const levels = ref<DynamicLevel[]>([])
const currentLevel = ref<DynamicLevel | null>(null)

onMounted(async () => {
    try {
        const response: any = await $fetch(`${config.public.apiBase}/api/student-card/dashboard`)
        if (response?.success && response.levels) {
            levels.value = response.levels
            if (levels.value.length > 0) {
                activeTab.value = 0
                currentLevel.value = levels.value[0]
            }
        }
    } catch (e) {
        console.error('Failed to load levels', e)
    } finally {
        isLoading.value = false
    }
})

const handleSelectLevel = (index: number) => {
    activeTab.value = index;
    currentLevel.value = levels.value[index];
}

const getClassrooms = (index: number) => {
    if (!levels.value[index]) return []
    const level = levels.value[index]
    return level.sections.map(section => ({
        id: section,
        name: section,
        fullName: `${level.name}/${section}`,
        link: `/student-card/${level.level}/${section}`,
        levelId: level.level,
    }))
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

            <!-- Loading State -->
            <div v-if="isLoading" class="flex justify-center my-12">
                <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <template v-else>
                <!-- Level Tabs -->
                <div class="max-w-5xl mx-auto mb-6">
                    <div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg p-2">
                        <div class="flex flex-wrap justify-center gap-2">
                            <button
                                v-for="(level, index) in levels"
                                :key="level.level"
                                @click="handleSelectLevel(index)"
                                :class="[
                                    'px-6 py-3 rounded-xl font-semibold transition-all duration-200',
                                    activeTab === index
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
                <div class="max-w-5xl mx-auto" v-if="currentLevel">
                    <div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg p-4 sm:p-6">
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
            </template>

            <!-- Footer -->
            <div class="max-w-5xl mx-auto mt-8 text-center text-gray-500 text-sm">
                <p>© {{ new Date().getFullYear() }} ระบบบัตรนักเรียน - โรงเรียนจริยธรรมศึกษามูลนิธิ</p>
            </div>
        </div>
    </div>
</template>
