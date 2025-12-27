<script setup>
import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
// import TextareaAutosize from 'vue-textarea-autosize';
import { Icon } from '@iconify/vue';

defineProps({
    // image: { type: String, default: null },
    name: { type: String, default: null },
    code: { type: String, default: null },
    description: { type: String, default:null},
    category: { type: String, default: null },
});

const textarea = ref(null);
const myTextArea = ref(null);
const showEditForm = ref(false);

const emit = defineEmits([
    // 'update:image', 
    'update:name',
    'update:code', 
    'update:description',
    'update:category',
    'save-update'
]);

onMounted(() => {
    // myTextArea.value.focus();
})
// textarea.value.addEventListener('input', resizeTextarea);
// resizeTextarea();

function resizeTextarea(e) {
    emit('update:description', e.target.value)
    // e.target.style.height = "auto";
    // e.target.style.height = `${e.target.scrollHeight}px`;
}

function saveUpdate(){
    emit('save-update');
}

</script>
<template>
    <div class="bg-white rounded-lg border-t-4 border-blue-400 shadow-lg">
        <div class="xheader">
            <h3 class="text-xl font-semibold px-2 py-3 border-b-2 border-blue-400">
                ข้อมูลทั่วไปเกี่ยวกับรายวิชา
            </h3>
        </div>
        <form action="" @submit.prevent="saveUpdate()" class="pb-4">
            <div class="p-3">
                <div class="flex flex-col space-y-3">
                    <div class="">
                        <label for="course-name" class="text-sm font-medium text-gray-900 block mb-1">ชื่อวิชา</label>
                        <input type="text" :value="name" @input="emit('update:name', $event.target.value)" id="course-name" :disabled="!showEditForm"
                            class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-cyan-600 focus:border-cyan-600 block w-full p-2.5" placeholder="Apple Imac 27”" required="">
                    </div>
                    <div class="">
                        <label for="course-code" class="text-sm font-medium text-gray-900 block mb-1">รหัสวิชา</label>
                        <input type="text" :value="code" @input="emit('update:code', $event.target.value)" id="course-code" :disabled="!showEditForm"
                            class="shadow-sm bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-cyan-600 focus:border-cyan-600 block w-full p-2.5" placeholder="Apple Imac 27”" required=""
                            
                        >
                    </div>
                    <div class="">
                        <label for="course-description" class="text-sm font-medium text-gray-900 block mb-1">คำอธิบายรายวิชา</label>    
                        <textarea id="course-description"
                            :value="description" @input="resizeTextarea" ref="myTextArea" :disabled="!showEditForm"
                            class="bg-gray-50 overflow-hidden border min-h-fit border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-cyan-600 focus:border-cyan-600 block w-full p-4"
                        ></textarea>
                    </div>

                    <div class="">
                        <label for="course-category" class="block text-gray-700 font-medium mb-2">หมวดหมู่/กลุ่มสาระ</label>
                        <select id="course-category" name="category" :value="category" @change="emit('update:category', $event.target.value)" :disabled="!showEditForm"
                            class="border border-gray-400 p-2 w-full rounded-lg focus:outline-none focus:border-blue-400" required>
                            <option value="">เลือกหมวดหมู่รายวิชา/กลุ่มสาระ</option>
                            <option value="กลุ่มสาระการเรียนรู้คณิตศาสตร์">กลุ่มสาระการเรียนรู้คณิตศาสตร์</option>
                            <option value="กลุ่มสาระการเรียนรู้วิทยาศาสตร์และเทคโนโลยี">กลุ่มสาระการเรียนรู้วิทยาศาสตร์และเทคโนโลยี</option>
                            <option value="กลุ่มสาระการเรียนรู้ภาษาไทย">กลุ่มสาระการเรียนรู้ภาษาไทย</option>
                            <option value="กลุ่มสาระการเรียนรู้ภาษาต่างประเทศ">กลุ่มสาระการเรียนรู้ภาษาต่างประเทศ</option>
                            <option value="กลุ่มสาระการเรียนรู้สังคมศึกษา ศาสนาและวัฒนธรรม">กลุ่มสาระการเรียนรู้สังคมศึกษา ศาสนาและวัฒนธรรม</option>
                            <option value="กลุ่มสาระการเรียนรู้สุขศึกษา พลศึกษา">กลุ่มสาระการเรียนรู้สุขศึกษา พลศึกษา</option>
                            <option value="กลุ่มสาระการเรียนรู้ศิลปศึกษา">กลุ่มสาระการเรียนรู้ศิลปศึกษา</option>
                            <option value="กลุ่มสาระการเรียนรู้การงานอาชีพ">กลุ่มสาระการเรียนรู้การงานอาชีพ</option>
                            <option value="กิจกรรมพัฒนาผู้เรียน">กิจกรรมพัฒนาผู้เรียน</option>
                            <option value="งานแนะแนว">งานแนะแนว</option>
                        </select>
                    </div>
                </div>
            </div>
            <!-- <div class="p-4 border-t-2 border-gray-200 rounded-b flex justify-end items-center">
                <button type="submit" class="text-white bg-cyan-600 hover:bg-cyan-700 focus:ring-4 focus:ring-cyan-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center">บันทึก</button>
            </div> -->
            <div class="rounded-b flex justify-center items-center">
                <div v-if="showEditForm" class="flex space-x-4">
                    <button @click.prevent="showEditForm=false" class="text-gray-600 bg-red-300 hover:bg-red-400 hover:text-white focus:ring-4 focus:ring-violet-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        ยกเลิก
                    </button>
                    <button type="submit" class="text-white bg-green-500 hover:bg-violet-700 focus:ring-4 focus:ring-violet-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        บันทึก
                    </button>
                </div>
                <div v-else >
                    <button type="button" @click.prevent="showEditForm=true" class="text-white bg-violet-600 hover:bg-violet-700 focus:ring-4 focus:ring-violet-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        <span class="flex justify-center items-center space-x-2">
                            <Icon icon="heroicons:pencil-square" class="w-5 h-5 mx-1" />แก้ไข
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>
