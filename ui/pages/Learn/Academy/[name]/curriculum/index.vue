<template>
    <AcademyLayout 
        v-if="academy"
        :academy="academy"
        :isAcademyAdmin="isAcademyAdmin"
    >
        <template #academyContent>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold dark:text-white">หลักสูตร ({{ curriculums.length }})</h2>
                    <button 
                        v-if="isAcademyAdmin"
                        @click="showCreateModal = true"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        สร้างหลักสูตรใหม่
                    </button>
                </div>

                <div v-if="isLoading" class="text-center py-8">
                    <span class="loading loading-spinner loading-lg"></span>
                </div>

                <div v-else-if="curriculums.length > 0" class="space-y-4">
                    <div 
                        v-for="curriculum in curriculums" 
                        :key="curriculum.id" 
                        @click="goToDetail(curriculum.id)"
                        class="border dark:border-gray-700 rounded-lg p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                    >
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-bold text-lg dark:text-white">{{ curriculum.name }}</h3>
                                <p class="text-gray-500 text-sm">{{ curriculum.code }} • {{ curriculum.academic_year }}</p>
                            </div>
                            <div class="flex gap-2">
                                <span :class="curriculum.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="px-2 py-1 rounded text-xs">
                                    {{ curriculum.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-4 text-sm text-gray-600 dark:text-gray-400">
                             <div>
                                <span class="font-bold">{{ curriculum.total_credits }}</span> หน่วยกิต
                             </div>
                             <div>
                                <span class="font-bold">{{ curriculum.curriculum_courses_count || 0 }}</span> รายวิชา
                             </div>
                        </div>
                         <!-- Actions -->
                         <div v-if="isAcademyAdmin" class="mt-4 border-t dark:border-gray-700 pt-3 flex gap-2" @click.stop>
                             <button @click.stop="editCurriculum(curriculum)" class="text-blue-600 hover:text-blue-800 text-sm">แก้ไข</button>
                             <button @click.stop="deleteCurriculum(curriculum.id)" class="text-red-600 hover:text-red-800 text-sm">ลบ</button>
                         </div>
                    </div>
                </div>

                <div v-else class="text-center py-12 text-gray-500">
                     ยังไม่มีหลักสูตร
                </div>
            </div>

            <!-- Create/Edit Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <div class="p-6 border-b dark:border-gray-700">
                        <h3 class="text-lg font-bold dark:text-white">{{ editingCurriculum ? 'แก้ไขหลักสูตร' : 'สร้างหลักสูตรใหม่' }}</h3>
                    </div>
                    <div class="p-6">
                        <CurriculumForm 
                            :academyId="academy.id" 
                            :curriculum="editingCurriculum"
                            @saved="handleSaved" 
                            @cancel="closeModal" 
                        />
                    </div>
                </div>
            </div>
        </template>
    </AcademyLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AcademyLayout from '@/layouts/AcademyLayout.vue';
import CurriculumForm from '@/components/learn/academy/curriculum/CurriculumForm.vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();
const academyName = route.params.name;

const academy = ref(null);
const isAcademyAdmin = ref(false);
const curriculums = ref([]);
const isLoading = ref(true);
const showCreateModal = ref(false);
const editingCurriculum = ref(null);

const fetchAcademy = async () => {
    try {
        const response = await axios.get(`/api/academies/${academyName}`);
        if(response.data.success) {
            academy.value = response.data.academy;
            isAcademyAdmin.value = response.data.isAcademyAdmin;
            fetchCurriculums(academy.value.id);
        }
    } catch (error) {
        console.error("Error fetching academy", error);
    }
};

const fetchCurriculums = async (academyId) => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/api/academies/${academyId}/curriculums`);
        if(response.data.success) {
            curriculums.value = response.data.curriculums;
        }
    } catch (error) {
        console.error("Error fetching curriculums", error);
    } finally {
        isLoading.value = false;
    }
};

const handleSaved = (savedCurriculum) => {
    if (editingCurriculum.value) {
        const index = curriculums.value.findIndex(c => c.id === savedCurriculum.id);
        if (index !== -1) curriculums.value[index] = savedCurriculum;
    } else {
        curriculums.value.push(savedCurriculum);
    }
    closeModal();
};

const closeModal = () => {
    showCreateModal.value = false;
    editingCurriculum.value = null;
};

const editCurriculum = (curriculum) => {
    editingCurriculum.value = curriculum;
    showCreateModal.value = true;
};

const deleteCurriculum = async (id) => {
    const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'การลบหลักสูตรจะไม่สามารถกู้คืนได้ และอาจส่งผลกระทบต่อนักเรียนที่ลงทะเบียนเรียนอยู่',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.delete(`/api/academies/curriculums/${id}`);
            if (response.data.success) {
                curriculums.value = curriculums.value.filter(c => c.id !== id);
                Swal.fire('สำเร็จ', 'ลบหลักสูตรเรียบร้อยแล้ว', 'success');
            }
        } catch (error) {
            Swal.fire('ข้อผิดพลาด', error.response?.data?.message || 'ไม่สามารถลบได้', 'error');
        }
    }
};

const goToDetail = (id) => {
     router.push(`/Learn/Academy/${academyName}/curriculum/${id}`);
}

onMounted(() => {
    fetchAcademy();
});
</script>
