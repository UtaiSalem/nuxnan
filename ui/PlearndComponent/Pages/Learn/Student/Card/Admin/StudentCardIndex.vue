<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

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

const handleSelectLevel = (levelId) => {
    activeTab.value = levelId;
    currentLevel.value = mattayomLevels[levelId];
}

const getClassrooms = (levelId) => {
    const level = mattayomLevels[levelId]
    const rooms = []
    for (let i = 1; i <= level.rooms; i++) {
        rooms.push({
            id: i,
            name: `${i}`,
            fullName: `${level.name}/${i}`,
            link: `/student-card/admin/students/${levelId+1}/${i}`,
            levelId: level.id,
        })
    }
    return rooms
}

const handleSelectRoom = (link) => {

    isNavigating.value = true;

    window.location.href = link;
    // Alternatively, you can use Inertia to navigate:
    // Inertia.visit(link); 
    // or use router.push(link) if using Vue Router  
}

</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-100 via-white to-blue-200 relative overflow-x-hidden">
        <!-- Watermark background logo -->
        <div
            class="fixed inset-0 flex items-center justify-center pointer-events-none select-none z-0"
            aria-hidden="true"
        >
            <img
                src="/storage/jsm_logo.png"
                alt="Watermark"
                class="opacity-10 w-[60vw] max-w-[500px] h-auto"
                style="filter: blur(0.5px);"
            />
        </div>

        <!-- Centered School Logo -->
        <div class="flex justify-center mt-6 mb-2 relative z-10">
            <img src="/storage/jsm_logo.png" alt="Logo"
                class="w-20 h-20 rounded-full shadow-xl border-4 border-white animate-float" />
        </div>

        <div v-if="isNavigating" class="fixed inset-0 bg-gray-100 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <p class="text-gray-700">กำลังโหลด...</p>
            </div>
        </div>

        <Head title="Student Card" />
        <div class="container mx-auto px-4 py-10 relative z-10">
            <div class="text-center mb-8">
                <h1 class="text-3xl md:text-4xl font-extrabold text-blue-700 drop-shadow-lg mb-2 tracking-wide">
                    ระบบบันทึกข้อมูลนักเรียน
                </h1>
                <p class="text-lg text-blue-500 font-medium mb-2">สำหรับครูประจำชั้น</p>
                <div class="flex justify-center gap-2 mt-2">
                    <span class="inline-block w-2 h-2 bg-blue-700 rounded-full animate-pulse"></span>
                    <span class="inline-block w-2 h-2 bg-blue-400 rounded-full animate-pulse delay-150"></span>
                    <span class="inline-block w-2 h-2 bg-blue-300 rounded-full animate-pulse delay-300"></span>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex flex-wrap w-full border-b-2 border-blue-200 mb-8 justify-center min-h-[64px]">
                <button v-for="level in mattayomLevels" :key="level.id"
                    @click="handleSelectLevel(level.id)"
                    :class="[
                        'relative px-6 py-4 mx-1 my-2 font-semibold rounded-t-xl transition-all duration-200 whitespace-nowrap focus:outline-none',
                        activeTab === level.id
                            ? 'bg-gradient-to-r from-blue-500 to-blue-700 text-white shadow-lg scale-105'
                            : 'bg-white text-blue-700 hover:bg-blue-100 border-2 border-blue-300 shadow-xl hover:scale-105 hover:shadow-lg'
                    ]"
                    style="min-width: 90px; min-height: 56px;"
                >
                    <span class="text-xl mr-2">🏫</span>
                    {{ level.name }}
                    <span v-if="activeTab === level.id" class="absolute left-1/2 -bottom-2 transform -translate-x-1/2 w-8 h-1 bg-blue-600 rounded-full"></span>
                </button>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-8">
                    <div v-for="room in getClassrooms(activeTab)" :key="room.id"
                        class="group bg-blue-200 shadow-xl rounded-2xl p-6 cursor-pointer hover:scale-105 hover:shadow-2xl transition-all duration-200 text-center border-2 border-blue-400 hover:border-blue-400 relative overflow-hidden"
                        @click="handleSelectRoom(room.link)">
                        <div class="absolute top-5 left-0 opacity-10 text-5xl pointer-events-none select-none">🏫</div>
                        <div class="flex flex-col items-center justify-center z-10 relative">
                            <span class="text-3xl font-bold text-blue-700 drop-shadow-sm mb-2 group-hover:text-blue-900 transition">
                                {{ room.fullName }}
                            </span>
                            <span class="text-xs text-blue-400 font-semibold tracking-widest uppercase">Classroom</span>
                        </div>
                        <div class="absolute bottom-2 left-1/2 transform -translate-x-1/2 w-10 h-1 bg-gradient-to-r from-blue-300 to-blue-500 rounded-full opacity-70"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-float {
    animation: float 3s ease-in-out infinite;
}
@keyframes float {
    0%, 100% { transform: translateY(0);}
    50% { transform: translateY(-12px);}
}
</style>
