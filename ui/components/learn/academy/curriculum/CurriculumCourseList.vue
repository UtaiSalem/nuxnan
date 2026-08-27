<template>
    <div class="space-y-6">
        <!-- Summary Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl">
                <span class="text-sm text-blue-600 dark:text-blue-400">รายวิชาทั้งหมด</span>
                <p class="text-2xl font-bold text-blue-800 dark:text-blue-200">{{ summary.total_courses || 0 }}</p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-xl">
                <span class="text-sm text-green-600 dark:text-green-400">หน่วยกิตรวม</span>
                <p class="text-2xl font-bold text-green-800 dark:text-green-200">{{ summary.total_credits || 0 }}</p>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-xl">
                <span class="text-sm text-purple-600 dark:text-purple-400">วิชาบังคับ</span>
                <p class="text-2xl font-bold text-purple-800 dark:text-purple-200">{{ summary.required_count || 0 }}</p>
            </div>
            <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-xl">
                <span class="text-sm text-orange-600 dark:text-orange-400">วิชาเลือก</span>
                <p class="text-2xl font-bold text-orange-800 dark:text-orange-200">{{ summary.elective_count || 0 }}</p>
            </div>
        </div>

        <!-- Add Course Button -->
        <div v-if="isAcademyAdmin" class="flex justify-end">
            <button @click="openAddModal" class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                <span class="text-xl">+</span> เพิ่มรายวิชา
            </button>
        </div>

        <!-- Course List Grouped by Year/Semester -->
        <div v-if="Object.keys(groupedCourses).length > 0" class="space-y-8">
            <div v-for="(semesters, year) in groupedCourses" :key="year" class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6">
                <h3 class="text-lg font-bold mb-4 dark:text-white">ชั้นปีที่ {{ year }}</h3>
                <div class="space-y-6">
                    <div v-for="(courses, semester) in semesters" :key="semester">
                        <h4 class="text-md font-semibold text-gray-600 dark:text-gray-400 mb-3 px-2 border-l-4 border-blue-500">
                            ภาคเรียนที่ {{ semester }}
                        </h4>
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border dark:border-gray-700 overflow-hidden">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 dark:bg-gray-700 text-xs text-gray-500 dark:text-gray-300 uppercase">
                                    <tr>
                                        <th class="px-4 py-3">รหัสวิชา</th>
                                        <th class="px-4 py-3">ชื่อวิชา</th>
                                        <th class="px-4 py-3">ประเภท</th>
                                        <th class="px-4 py-3 text-center">หน่วยกิต</th>
                                        <th v-if="isAcademyAdmin" class="px-4 py-3 text-center">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <tr v-for="course in courses" :key="course.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 text-sm font-medium dark:text-gray-200">{{ course.course?.code || '-' }}</td>
                                        <td class="px-4 py-3 text-sm dark:text-gray-200">{{ course.course?.name }}</td>
                                        <td class="px-4 py-3 text-xs">
                                            <span :class="{
                                                'bg-red-100 text-red-800': course.course_type === 'required',
                                                'bg-blue-100 text-blue-800': course.course_type === 'elective',
                                                'bg-gray-100 text-gray-800': course.course_type === 'general'
                                            }" class="px-2 py-1 rounded-full">
                                                {{ getCourseTypeLabel(course.course_type) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-center dark:text-gray-200">{{ course.credits }}</td>
                                        <td v-if="isAcademyAdmin" class="px-4 py-3 text-center">
                                            <button @click="removeCourse(course)" class="text-red-500 hover:text-red-700 text-sm">
                                                ลบ
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="text-center py-12 text-gray-500 bg-white dark:bg-gray-800 rounded-xl border dark:border-gray-700">
            ยังไม่มีรายวิชาในหลักสูตร
        </div>

        <!-- Add Course Modal -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-bold dark:text-white">เพิ่มรายวิชาเข้าหลักสูตร</h3>
                    <button @click="showAddModal = false" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <div class="p-6">
                    <!-- Step 1: Select Course -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1 dark:text-gray-300">เลือกรายวิชา</label>
                        <select v-model="addForm.course_id" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" @change="onCourseSelect">
                            <option value="">-- เลือกรายวิชา --</option>
                            <option v-for="c in availableCourses" :key="c.id" :value="c.id">
                                {{ c.code ? c.code + ' - ' : '' }}{{ c.name }} ({{ c.credit_units }} หน่วยกิต)
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                         <div>
                            <label class="block text-sm font-medium mb-1 dark:text-gray-300">ชั้นปี</label>
                            <input v-model="addForm.year_level" type="number" min="1" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                         <div>
                            <label class="block text-sm font-medium mb-1 dark:text-gray-300">ภาคเรียน</label>
                            <select v-model="addForm.semester" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="ฤดูร้อน">ฤดูร้อน</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 dark:text-gray-300">ประเภทวิชา</label>
                            <select v-model="addForm.course_type" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="required">วิชาบังคับ</option>
                                <option value="elective">วิชาเลือก</option>
                                <option value="general">วิชาทั่วไป</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 dark:text-gray-300">หน่วยกิต</label>
                            <input v-model="addForm.credits" type="number" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button @click="showAddModal = false" class="min-h-[44px] sm:min-h-0 px-4 py-2 border rounded-lg hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">ยกเลิก</button>
                        <button @click="submitAddCourse" :disabled="!addForm.course_id || isAdding" class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                            {{ isAdding ? 'กำลังบันทึก...' : 'บันทึก' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useApi } from '@/composables/useApi';
import Swal from 'sweetalert2';

const props = defineProps({
    academyId: { type: [Number, String], required: true },
    curriculumId: { type: [Number, String], required: true },
    isAcademyAdmin: { type: Boolean, default: false }
});

const courses = ref([]);
const groupedCourses = ref({});
const summary = ref({});
const isLoading = ref(true);

const showAddModal = ref(false);
const availableCourses = ref([]);
const isAdding = ref(false);
const api = useApi();
const addForm = ref({
    course_id: '',
    year_level: 1,
    semester: '1',
    course_type: 'required',
    credits: 0
});

const getCourseTypeLabel = (type) => {
    switch(type) {
        case 'required': return 'วิชาบังคับ';
        case 'elective': return 'วิชาเลือก';
        case 'general': return 'วิชาทั่วไป';
        default: return type;
    }
}

const fetchCourses = async () => {
    isLoading.value = true;
    try {
        const response = await api.get(`/api/academies/${props.academyId}/curriculums/${props.curriculumId}/courses`);
        if (response.success) {
            courses.value = response.courses;
            groupedCourses.value = response.grouped_courses;
            summary.value = response.summary;
        }
    } catch (error) {
        console.error("Error fetching courses", error);
    } finally {
        isLoading.value = false;
    }
};

const openAddModal = async () => {
    showAddModal.value = true;
    try {
        const response = await api.get(`/api/academies/${props.academyId}/curriculums/${props.curriculumId}/available-courses`);
        if (response.success) {
            availableCourses.value = response.courses;
        }
    } catch (error) {
        console.error("Error fetching available courses", error);
    }
};

const onCourseSelect = () => {
    const selected = availableCourses.value.find(c => c.id == addForm.value.course_id);
    if (selected) {
        addForm.value.credits = selected.credit_units;
    }
};

const submitAddCourse = async () => {
    isAdding.value = true;
    try {
        const response = await api.post(`/api/academies/${props.academyId}/curriculums/${props.curriculumId}/courses`, addForm.value);
        if (response.success) {
            Swal.fire('สำเร็จ', 'เพิ่มรายวิชาเรียบร้อยแล้ว', 'success');
            showAddModal.value = false;
            fetchCourses();
            // Reset form
            addForm.value = {
                course_id: '', year_level: 1, semester: '1', course_type: 'required', credits: 0
            };
        }
    } catch (error) {
        Swal.fire('ข้อผิดพลาด', error.response?.data?.message || 'ไม่สามารถเพิ่มรายวิชาได้', 'error');
    } finally {
        isAdding.value = false;
    }
};

const removeCourse = async (course) => {
     const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: `ต้องการลบรายวิชา ${course.course?.name} ออกจากหลักสูตรหรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#d33'
    });

    if (result.isConfirmed) {
        try {
            // endpoint: /curriculums/{curriculum}/courses/{curriculumCourse}
             // Note: Route might be nested under academy or directly curriculum resource?
             // Let's check routes again. 
             // Usually DELETE /api/academies/{academy}/curriculums/{curriculum}/courses/{curriculumCourse} based on nesting
             
             // Wait, I didn't check the DELETE route url. 
             // Based on Add: /api/academies/{id}/curriculums/{id}/courses
             // Remove: /api/academies/{id}/curriculums/{id}/courses/{courseId} ??
             // Controller `removeCourse` takes `(Curriculum $curriculum, CurriculumCourse $curriculumCourse)`.
             // So route is likely `.../courses/{curriculumCourse}`.
             
            await api.delete(`/api/academies/${props.academyId}/curriculums/${props.curriculumId}/courses/${course.id}`);

            Swal.fire('ลบสำเร็จ', '', 'success');
            fetchCourses();
        } catch (error) {
            Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถลบได้', 'error');
        }
    }
}

onMounted(() => {
    fetchCourses();
});
</script>
