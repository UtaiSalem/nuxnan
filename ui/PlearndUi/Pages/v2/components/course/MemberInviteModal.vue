<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    groups: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits([
    'invite',
    'cancel',
]);

const inviteType = ref('single');
const email = ref('');
const groupId = ref('');
const csvFile = ref(null);
const csvData = ref([]);
const isProcessing = ref(false);

const selectedGroupName = computed(() => {
    const group = props.groups.find(g => g.value === groupId.value);
    return group ? group.label : '';
});

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    csvFile.value = file;
    isProcessing.value = true;

    const reader = new FileReader();
    reader.onload = (e) => {
        const text = e.target.result;
        parseCSV(text);
        isProcessing.value = false;
    };
    reader.readAsText(file);
};

const parseCSV = (text) => {
    const lines = text.split('\n').filter(line => line.trim());
    const headers = lines[0].split(',').map(h => h.trim());
    
    const data = [];
    for (let i = 1; i < lines.length; i++) {
        const values = lines[i].split(',').map(v => v.trim());
        if (values.length >= 2) {
            data.push({
                name: values[0],
                email: values[1],
                student_id: values[2] || '',
                role: values[3] || 'student',
            });
        }
    }
    
    csvData.value = data;
};

const handleInvite = () => {
    let inviteData;
    
    if (inviteType.value === 'single') {
        if (!email.value) {
            alert('กรุณาระบุอีเมล');
            return;
        }
        
        inviteData = {
            type: 'single',
            email: email.value,
            groupId: groupId.value,
        };
    } else {
        if (csvData.value.length === 0) {
            alert('กรุณาอัปโหลดไฟล์ CSV');
            return;
        }
        
        inviteData = {
            type: 'bulk',
            members: csvData.value,
            groupId: groupId.value,
        };
    }
    
    emit('invite', inviteData);
};

const handleCancel = () => {
    emit('cancel');
};

const resetForm = () => {
    inviteType.value = 'single';
    email.value = '';
    groupId.value = '';
    csvFile.value = null;
    csvData.value = [];
};
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                เชิญสมาชิกเข้าร่วมรายวิชา
                            </h3>

                            <!-- Invite Type Selection -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    ประเภทการเชิญ
                                </label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input
                                            type="radio"
                                            v-model="inviteType"
                                            value="single"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">เชิญคนเดียว</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            type="radio"
                                            v-model="inviteType"
                                            value="bulk"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">เชิญหลายคน (CSV)</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Single Invite Form -->
                            <div v-if="inviteType === 'single'" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        อีเมล
                                    </label>
                                    <input
                                        v-model="email"
                                        type="email"
                                        placeholder="example@email.com"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    >
                                </div>
                            </div>

                            <!-- Bulk Invite Form -->
                            <div v-if="inviteType === 'bulk'" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        อัปโหลดไฟล์ CSV
                                    </label>
                                    <input
                                        type="file"
                                        accept=".csv"
                                        @change="handleFileUpload"
                                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                    >
                                    <p class="mt-1 text-xs text-gray-500">
                                        รูปแบบ: ชื่อ,อีเมล,รหัสนักเรียน,บทบาท (student/teacher/ta)
                                    </p>
                                </div>

                                <!-- CSV Preview -->
                                <div v-if="csvData.length > 0" class="mt-4">
                                    <p class="text-sm font-medium text-gray-700 mb-2">
                                        พบ {{ csvData.length }} คนในไฟล์
                                    </p>
                                    <div class="max-h-32 overflow-y-auto border border-gray-200 rounded-md">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">ชื่อ</th>
                                                    <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">อีเมล</th>
                                                    <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">รหัส</th>
                                                    <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">บทบาท</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr v-for="(member, index) in csvData.slice(0, 5)" :key="index">
                                                    <td class="px-2 py-1 text-xs text-gray-900">{{ member.name }}</td>
                                                    <td class="px-2 py-1 text-xs text-gray-900">{{ member.email }}</td>
                                                    <td class="px-2 py-1 text-xs text-gray-900">{{ member.student_id }}</td>
                                                    <td class="px-2 py-1 text-xs text-gray-900">{{ member.role }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div v-if="csvData.length > 5" class="px-2 py-1 text-xs text-gray-500 text-center">
                                            ... และอีก {{ csvData.length - 5 }} คน
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Group Selection -->
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    กลุ่ม (ไม่ระบุก็ได้)
                                </label>
                                <select
                                    v-model="groupId"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                    <option value="">ไม่ระบุกลุ่ม</option>
                                    <option
                                        v-for="group in groups"
                                        :key="group.value"
                                        :value="group.value"
                                    >
                                        {{ group.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button
                        type="button"
                        @click="handleInvite"
                        :disabled="isProcessing"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        {{ inviteType === 'single' ? 'เชิญสมาชิก' : 'เชิญสมาชิก' }}
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