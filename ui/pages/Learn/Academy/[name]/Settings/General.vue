<script setup>
import { ref, defineProps } from 'vue';
import AcademyLayout from '@/layouts/AcademyLayout.vue';
import { Icon } from '@iconify/vue';
import { useForm } from '@inertiajs/vue3'; // Assuming usage based on Link import, or just use ref/axios
// If strictly Nuxt/Vue without Inertia form helper, we'd use standard ref & axios/fetch. 
// Given the mix, I'll stick to standard Vue refs + API call for safety, or check if useForm is available.
// For now, I'll use standard Vue refs to be safe.

const props = defineProps({
    academy: Object,
    isAcademyAdmin: Boolean,
});

const form = ref({
    name: props.academy?.data?.name || '',
    description: props.academy?.data?.description || '',
    logo: null,
    cover: null
});

const isSaving = ref(false);

const saveSettings = async () => {
    isSaving.value = true;
    // Simulate API call for now or use actual endpoint if known
    // await axios.post(`/api/academies/${props.academy.data.id}/update`, form.value)
    setTimeout(() => {
        isSaving.value = false;
        alert('บันทึกข้อมูลเรียบร้อย (Simulation)');
    }, 1000);
};
</script>

<template>
    <AcademyLayout :academy="props.academy" :isAcademyAdmin="props.isAcademyAdmin">
        <template #academyContent>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mt-4">
                
                <!-- Breadcrumb / Header -->
                <div class="flex items-center mb-6 border-b border-gray-200 dark:border-gray-700 pb-4">
                     <NuxtLink :to="`/Learn/Academy/${props.academy?.data?.name}/Settings`" class="mr-3 text-gray-500 hover:text-gray-700">
                        <Icon icon="lucide:arrow-left" class="w-6 h-6" />
                    </NuxtLink>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">ข้อมูลทั่วไป (General Settings)</h2>
                        <p class="text-sm text-gray-500">จัดการข้อมูลพื้นฐานของสถานศึกษา</p>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="saveSettings" class="space-y-6 max-w-2xl">
                    
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อสถานศึกษา</label>
                        <input 
                            v-model="form.name"
                            type="text" 
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="ระบุชื่อสถานศึกษา"
                        />
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำอธิบายสังเขป</label>
                        <textarea 
                            v-model="form.description"
                            rows="4"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="ระบุคำอธิบาย..."
                        ></textarea>
                    </div>

                    <!-- Images (Placeholder) -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <Icon icon="lucide:image" class="w-8 h-8 mx-auto text-gray-400 mb-2" />
                            <span class="text-sm text-gray-500">อัพโหลดโลโก้</span>
                        </div>
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <Icon icon="lucide:image" class="w-8 h-8 mx-auto text-gray-400 mb-2" />
                            <span class="text-sm text-gray-500">อัพโหลดรูปปก</span>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="pt-4">
                        <button 
                            type="submit" 
                            :disabled="isSaving"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center justify-center gap-2 disabled:opacity-50"
                        >
                            <Icon v-if="isSaving" icon="lucide:loader-2" class="animate-spin" />
                            <span>{{ isSaving ? 'กำลังบันทึก...' : 'บันทึกการเปลี่ยนแปลง' }}</span>
                        </button>
                    </div>

                </form>

            </div>
        </template>
    </AcademyLayout>
</template>
