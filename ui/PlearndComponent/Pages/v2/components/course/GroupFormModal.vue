<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    group: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['save', 'cancel']);

// State
const formData = ref({
    name: '',
    description: '',
    capacity: null,
    status: 'active',
});

const errors = ref({});

// Computed
const isEditing = computed(() => !!props.group);
const title = computed(() => isEditing.value ? 'แก้ไขกลุ่มเรียน' : 'สร้างกลุ่มเรียนใหม่');

// Watch for group changes
watch(() => props.group, (newGroup) => {
    if (newGroup) {
        formData.value = {
            name: newGroup.name || '',
            description: newGroup.description || '',
            capacity: newGroup.capacity || null,
            status: newGroup.status || 'active',
        };
    } else {
        resetForm();
    }
}, { immediate: true });

// Methods
const resetForm = () => {
    formData.value = {
        name: '',
        description: '',
        capacity: null,
        status: 'active',
    };
    errors.value = {};
};

const validateForm = () => {
    const newErrors = {};
    
    if (!formData.value.name.trim()) {
        newErrors.name = 'กรุณาระบุชื่อกลุ่ม';
    }
    
    if (formData.value.capacity && (formData.value.capacity < 1 || formData.value.capacity > 100)) {
        newErrors.capacity = 'ความจุต้องอยู่ระหว่าง 1-100 คน';
    }
    
    errors.value = newErrors;
    return Object.keys(newErrors).length === 0;
};

const handleSubmit = () => {
    if (validateForm()) {
        emit('save', { ...formData.value });
    }
};

const handleCancel = () => {
    resetForm();
    emit('cancel');
};
</script>

<template>
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <!-- Header -->
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-start">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            {{ title }}
                        </h3>
                        <button 
                            @click="handleCancel"
                            class="text-gray-400 hover:text-gray-500 focus:outline-none"
                        >
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                ชื่อกลุ่ม <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1">
                                <input
                                    id="name"
                                    v-model="formData.name"
                                    type="text"
                                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    :class="{ 'border-red-500': errors.name }"
                                    placeholder="ระบุชื่อกลุ่มเรียน"
                                >
                                <p v-if="errors.name" class="mt-2 text-sm text-red-600">
                                    {{ errors.name }}
                                </p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">
                                คำอธิบาย
                            </label>
                            <div class="mt-1">
                                <textarea
                                    id="description"
                                    v-model="formData.description"
                                    rows="3"
                                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    placeholder="ระบุคำอธิบายกลุ่มเรียน (ไม่จำเป็น)"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Capacity -->
                        <div>
                            <label for="capacity" class="block text-sm font-medium text-gray-700">
                                ความจุ
                            </label>
                            <div class="mt-1">
                                <input
                                    id="capacity"
                                    v-model.number="formData.capacity"
                                    type="number"
                                    min="1"
                                    max="100"
                                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    :class="{ 'border-red-500': errors.capacity }"
                                    placeholder="จำนวนสูงสุดของสมาชิกในกลุ่ม"
                                >
                                <p v-if="errors.capacity" class="mt-2 text-sm text-red-600">
                                    {{ errors.capacity }}
                                </p>
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">
                                สถานะ
                            </label>
                            <div class="mt-1">
                                <select
                                    id="status"
                                    v-model="formData.status"
                                    class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                >
                                    <option value="active">ใช้งาน</option>
                                    <option value="inactive">ไม่ใช้งาน</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button
                        type="submit"
                        @click="handleSubmit"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        {{ isEditing ? 'อัพเดท' : 'สร้างกลุ่ม' }}
                    </button>
                    <button
                        type="button"
                        @click="handleCancel"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        ยกเลิก
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>