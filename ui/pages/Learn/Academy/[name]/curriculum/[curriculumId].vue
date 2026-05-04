<template>
    <AcademyLayout 
        v-if="academy"
        :academy="academy"
        :isAcademyAdmin="isAcademyAdmin"
    >
        <template #academyContent>
            <div v-if="curriculum" class="space-y-6">
                <!-- Header -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <Link :href="`/Learn/Academy/${academyName}/curriculum`" class="text-sm text-gray-500 hover:text-gray-700">
                                    &larr; กลับไปหน้ารายการ
                                </Link>
                            </div>
                            <h1 class="text-2xl font-bold dark:text-white">{{ curriculum.name }}</h1>
                            <p class="text-gray-600 dark:text-gray-400">{{ curriculum.code }} • {{ curriculum.level }}</p>
                            <p class="mt-2 text-gray-700 dark:text-gray-300">{{ curriculum.description }}</p>
                        </div>
                        <div class="flex gap-2">
                           <button v-if="isAcademyAdmin" @click="showEditModal = true" class="px-4 py-2 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">แก้ไข</button>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <div class="flex border-b dark:border-gray-700">
                        <button 
                            @click="activeTab = 'courses'" 
                            :class="['px-6 py-3 font-medium text-sm', activeTab === 'courses' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700']"
                        >
                            โครงสร้างรายวิชา
                        </button>
                        <button 
                            @click="activeTab = 'students'" 
                            :class="['px-6 py-3 font-medium text-sm', activeTab === 'students' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700']"
                        >
                            นักเรียน ({{ curriculum.active_students_count || 0 }})
                        </button>
                    </div>

                    <div class="p-6">
                        <!-- Courses Tab -->
                        <div v-if="activeTab === 'courses'">
                            <CurriculumCourseList 
                                :academyId="academy.id" 
                                :curriculumId="curriculum.id"
                                :isAcademyAdmin="isAcademyAdmin"
                            />
                        </div>

                        <!-- Students Tab -->
                        <div v-if="activeTab === 'students'">
                             <!-- TODO: Student List Component -->
                             <div class="text-center py-8 text-gray-500">
                                ส่วนจัดการนักเรียนกำลังพัฒนา...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="isLoading" class="text-center py-12">
                 <span class="loading loading-spinner loading-lg"></span>
            </div>
             <div v-else class="text-center py-12 text-red-500">
                 ไม่พบข้อมูลหลักสูตร
            </div>

            <!-- Edit Modal -->
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
                 <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <div class="p-6 border-b dark:border-gray-700">
                        <h3 class="text-lg font-bold dark:text-white">แก้ไขหลักสูตร</h3>
                    </div>
                    <div class="p-6">
                        <CurriculumForm 
                            :academyId="academy.id"
                            :curriculum="curriculum"
                            @saved="handleUpdated" 
                            @cancel="showEditModal = false" 
                        />
                    </div>
                </div>
            </div>

        </template>
    </AcademyLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import AcademyLayout from '@/layouts/AcademyLayout.vue';
import CurriculumForm from '@/components/learn/academy/curriculum/CurriculumForm.vue';
import CurriculumCourseList from '@/components/learn/academy/curriculum/CurriculumCourseList.vue';

const api = useApi();

const route = useRoute();
const academyName = route.params.name;
const curriculumId = route.params.curriculumId;

const academy = ref(null);
const isAcademyAdmin = ref(false);
const curriculum = ref(null);
const isLoading = ref(true);
const activeTab = ref('courses');
const showEditModal = ref(false);

const fetchAcademy = async () => {
    try {
        const response = await api.get(`/api/academies/${academyName}`);
        if(response.success) {
            academy.value = response.academy;
            isAcademyAdmin.value = response.isAcademyAdmin;
            fetchCurriculumDetails();
        }
    } catch (error) {
        console.error("Error fetching academy", error);
    }
};

const fetchCurriculumDetails = async () => {
    isLoading.value = true;
    try {
        const response = await api.get(`/api/academies/curriculums/${curriculumId}`); // Uses curriculum route
        if(response.success) {
            curriculum.value = response.curriculum;
        }
    } catch (error) {
         console.error("Error fetching curriculum detail", error);
    } finally {
        isLoading.value = false;
    }
};

const handleUpdated = (updatedCurriculum) => {
    curriculum.value = updatedCurriculum;
    showEditModal.value = false;
};

onMounted(() => {
    fetchAcademy();
});
</script>
