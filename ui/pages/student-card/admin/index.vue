<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

useHead({
  title: 'จัดการบัตรนักเรียน - Admin'
})

definePageMeta({
  layout: false,
  middleware: ['auth']
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

const loadLevels = async () => {
    isLoading.value = true
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
}

onMounted(() => {
    loadLevels()
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
        link: `/student-card/admin/students/${level.level}/${section}`,
        levelId: level.level,
    }))
}

const handleSelectRoom = (link: string) => {
    isNavigating.value = true;
    navigateTo(link)
}

</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-purple-100 via-white to-purple-200 relative overflow-x-hidden">
        
        <!-- Background decorations -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-purple-200 rounded-full opacity-30 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-purple-300 rounded-full opacity-20 blur-3xl"></div>
        </div>

        <!-- Loading Overlay -->
        <div v-if="isNavigating" class="fixed inset-0 bg-white/80 backdrop-blur-sm z-50 flex items-center justify-center">
            <div class="text-center">
                <div class="w-16 h-16 border-4 border-purple-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-purple-600 font-medium">กำลังโหลด...</p>
            </div>
        </div>

        <div class="relative z-10 p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <div class="max-w-5xl mx-auto mb-8">
                <div class="flex items-center gap-4 mb-6">
                    <NuxtLink 
                        to="/student-card" 
                        class="p-2 bg-white/80 backdrop-blur rounded-lg shadow-sm hover:shadow-md transition-all"
                    >
                        <Icon icon="heroicons:arrow-left" class="w-6 h-6 text-gray-600" />
                    </NuxtLink>
                    <div>
                        <span class="inline-flex items-center gap-2 px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-medium">
                            <Icon icon="heroicons:shield-check" class="w-4 h-4" />
                            Admin Mode
                        </span>
                    </div>
                </div>
                
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-lg mb-4">
                        <Icon icon="heroicons:cog-6-tooth" class="w-8 h-8 text-white" />
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">จัดการบัตรนักเรียน</h1>
                    <p class="text-gray-600">เลือกระดับชั้นและห้องเรียนเพื่อจัดการข้อมูลนักเรียน</p>
                </div>
            </div>

            <!-- Features Overview -->
            <div class="max-w-5xl mx-auto mb-8">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-white/70 backdrop-blur-sm rounded-xl p-4 text-center">
                        <Icon icon="heroicons:camera" class="w-8 h-8 text-purple-500 mx-auto mb-2" />
                        <p class="text-sm text-gray-600">อัปโหลดรูป</p>
                    </div>
                    <div class="bg-white/70 backdrop-blur-sm rounded-xl p-4 text-center">
                        <Icon icon="heroicons:pencil-square" class="w-8 h-8 text-purple-500 mx-auto mb-2" />
                        <p class="text-sm text-gray-600">แก้ไขข้อมูล</p>
                    </div>
                    <div class="bg-white/70 backdrop-blur-sm rounded-xl p-4 text-center">
                        <Icon icon="heroicons:qr-code" class="w-8 h-8 text-purple-500 mx-auto mb-2" />
                        <p class="text-sm text-gray-600">QR Code</p>
                    </div>
                    <div class="bg-white/70 backdrop-blur-sm rounded-xl p-4 text-center">
                        <Icon icon="heroicons:printer" class="w-8 h-8 text-purple-500 mx-auto mb-2" />
                        <p class="text-sm text-gray-600">พิมพ์บัตร</p>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex justify-center my-12">
                <div class="w-12 h-12 border-4 border-purple-500 border-t-transparent rounded-full animate-spin"></div>
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
                                        ? 'bg-gradient-to-r from-purple-500 to-purple-600 text-white shadow-md'
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
                    <div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg p-6">
                        <h2 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                            <span class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                <Icon icon="heroicons:building-office-2" class="w-5 h-5 text-purple-600" />
                            </span>
                            เลือกห้องเรียน - {{ currentLevel.name }}
                        </h2>

                        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3">
                            <button
                                v-for="room in getClassrooms(activeTab)"
                                :key="room.id"
                                @click="handleSelectRoom(room.link)"
                                class="group relative bg-gradient-to-br from-purple-50 to-white border-2 border-purple-100 rounded-xl p-4 hover:border-purple-400 hover:shadow-lg transition-all duration-200"
                            >
                                <div class="text-center">
                                    <span class="block text-2xl font-bold text-purple-600 group-hover:text-purple-700">{{ room.name }}</span>
                                    <span class="text-xs text-gray-500">{{ room.fullName }}</span>
                                </div>
                                <div class="absolute top-2 right-2">
                                    <Icon icon="heroicons:cog-6-tooth" class="w-4 h-4 text-purple-300 group-hover:text-purple-500" />
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Footer -->
            <div class="max-w-5xl mx-auto mt-8 text-center text-gray-500 text-sm">
                <p>© {{ new Date().getFullYear() }} ระบบจัดการบัตรนักเรียน - โรงเรียนจริยธรรมศึกษามูลนิธิ</p>
            </div>
        </div>
    </div>
</template>
