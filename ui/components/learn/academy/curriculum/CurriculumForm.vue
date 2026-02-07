<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Name -->
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ชื่อหลักสูตร <span class="text-red-500">*</span></label>
                <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
            </div>

            <!-- Code -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">รหัสหลักสูตร</label>
                <input v-model="form.code" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <!-- Academic Year -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ปีการศึกษา</label>
                <input v-model="form.academic_year" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

             <!-- Level -->
             <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ระดับการศึกษา</label>
                <select v-model="form.level" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="มัธยมศึกษาตอนต้น">มัธยมศึกษาตอนต้น</option>
                    <option value="มัธยมศึกษาตอนปลาย">มัธยมศึกษาตอนปลาย</option>
                    <option value="ปวช.">ปวช.</option>
                    <option value="ปวส.">ปวส.</option>
                    <option value="ปริญญาตรี">ปริญญาตรี</option>
                    <option value="อื่นๆ">อื่นๆ</option>
                </select>
            </div>

            <!-- Duration -->
             <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ระยะเวลา (ปี)</label>
                <input v-model="form.duration_years" type="number" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <!-- Total Credits -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">จำนวนหน่วยกิตรวม</label>
                 <input v-model="form.total_credits" type="number" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

             <!-- Description -->
             <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">รายละเอียด</label>
                <textarea v-model="form.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
            </div>
             
             <!-- Active Status -->
             <div class="col-span-2 flex items-center">
                <input v-model="form.is_active" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label class="ml-2 block text-sm text-gray-900 dark:text-gray-300">เปิดใช้งานหลักสูตร</label>
             </div>
        </div>
        
        <div class="flex justify-end gap-3 pt-4 border-t dark:border-gray-700">
             <button @click="$emit('cancel')" type="button" class="px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                ยกเลิก
            </button>
            <button @click="submit" :disabled="isLoading" type="button" class="px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none disabled:opacity-50">
                <span v-if="isLoading">กำลังบันทึก...</span>
                <span v-else>บันทึก</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    academyId: { type: [Number, String], required: true },
    curriculum: { type: Object, default: null } // If provided, edit mode
});

const emit = defineEmits(['saved', 'cancel']);

const isLoading = ref(false);

const form = ref({
    name: '',
    code: '',
    academic_year: new Date().getFullYear() + 543, // Default Thai year
    level: 'มัธยมศึกษาตอนปลาย',
    duration_years: 3,
    total_credits: 0,
    description: '',
    is_active: true,
});

// Init form if editing
if (props.curriculum) {
    form.value = { ...props.curriculum };
    // ensure boolean
    form.value.is_active = !!form.value.is_active;
}

const submit = async () => {
    if (!form.value.name) {
        Swal.fire('แจ้งเตือน', 'กรุณาระบุชื่อหลักสูตร', 'warning');
        return;
    }

    isLoading.value = true;
    try {
        let response;
        if (props.curriculum) {
             // Update
             response = await axios.patch(`/api/academies/curriculums/${props.curriculum.id}`, form.value);
        } else {
             // Create
             response = await axios.post(`/api/academies/${props.academyId}/curriculums`, form.value);
        }

        if (response.data.success) {
            Swal.fire('สำเร็จ', response.data.message, 'success');
            emit('saved', response.data.curriculum);
        }
    } catch (error) {
        console.error(error);
        Swal.fire('ข้อผิดพลาด', error.response?.data?.message || 'เกิดข้อผิดพลาดในการบันทึก', 'error');
    } finally {
        isLoading.value = false;
    }
};
</script>
