<script setup>
import { ref, computed } from 'vue';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import { router } from '@inertiajs/vue3';

import CoursesLayout from '~/layouts/CoursesLayout.vue';

// VueDatePicker is imported as a global plugin (Backup: local import)
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const headerTitle = ref('สร้างรายวิชาใหม่');

// Imports cleaned up
// import InputLabel from '@/components/InputLabel.vue'
// import TextInput from '@/components/TextInput.vue';


const config = useRuntimeConfig();
const tempCover = ref(`${config.public.apiBase}/storage/images/courses/covers/default_cover.jpg`);
const coverInput = ref(null);
const isOpenCategoryOptions = ref(false);
const isOpenLevelOptions = ref(false);
const isOpenYearOptions = ref(false);
const crsStartDate = ref(new Date());
const crsEndDate = ref(new Date());
const courseRange = ref([crsStartDate.value,crsEndDate.value]);

// Calculate Academic Year and Semester
const currentDate = new Date();
const currentMonth = currentDate.getMonth() + 1; // 1-12
const currentYear = currentDate.getFullYear() + 543; // Thai Year (BE)
// Or should we use AD? The user didn't specify, but "2567" is BE. So I'll use BE.
// User example: "2567". 2024 = 2567. 2026 = 2569.
// Logic:
let initialSemester = '1';
let initialAcademicYear = currentYear;

if (currentMonth >= 5 && currentMonth <= 9) {
    // May to September: Semester 1, Year = Current
    initialSemester = '1';
    initialAcademicYear = currentYear;
} else if (currentMonth >= 10 && currentMonth <= 12) {
    // Oct to Dec: Semester 2, Year = Current
    initialSemester = '2';
    initialAcademicYear = currentYear;
} else if (currentMonth >= 1 && currentMonth <= 3) {
    // Jan to Mar: Semester 2, Year = Current - 1 (Academic Year continues)
    initialSemester = '2';
    initialAcademicYear = currentYear - 1; // Wait, currentYear is already +543 based on NOW. 
    // If NOW is Jan 2569 (2026), Academic Year is 2568 (2025). Correct.
} else {
   // April (Summer)
   initialSemester = 'summer';
   initialAcademicYear = currentYear - 1; // Summer is usually the end of the academic year
}


const defaultFormValue = ref({
  academy_id: '',
  code: '',
  name: '',
  description: '',
  category: '',
  education_level: '',
  education_year: null,
  credit_units: '',
  hours_per_week: '',
  start_date: courseRange.value[0],
  end_date: courseRange.value[1],
  auto_accept_members: true,
  tuition_fees: 0,
  saleable: true,
  price: 0,
  discount: 0,
  discount_type: 'fixed',
  semester: initialSemester,
  academic_year: initialAcademicYear.toString(),
  status: true,
  cover: tempCover.value === `${config.public.apiBase}/storage/images/courses/covers/default_cover.jpg` ? null : tempCover.value,
});

// generate fake form value to test purpose
const form = ref({
  academy_id: '',
  code: '',
  name: '',
  description: '', //
  category: '',
  education_level: '',
  education_year: null,
  credit_units: 1,
  hours_per_week: 1,
  start_date: courseRange.value[0] ? new Date(courseRange.value[0]) : null,
  end_date: new Date(new Date().setDate(new Date().getDate() + 30)), // new Date(new Date().setDate(new Date().getDate() + 30)),
  auto_accept_members: true,
  tuition_fees: 0,
  saleable: true,
  price: 0,
  discount: 0,
  discount_type: 'fixed',
  semester: initialSemester,
  academic_year: initialAcademicYear.toString(),
  status: true,
  cover: '',
});


const courseCategories = ref([
    { name: 'ภาษาไทย' },
    { name: 'คณิตศาสตร์' },
    { name: 'วิทยาศาสตร์' },
    { name: 'สังคมศึกษา ศาสนา และวัฒนธรรม' },
    { name: 'สุขศึกษาและพลศึกษา' },
    { name: 'ศิลปะ' },
    { name: 'การงานอาชีพและเทคโนโลยี' },
    { name: 'ภาษาต่างประเทศ' },
]);
const educationLevelOptions = [
    { value: 'ประถมศึกษา', label: 'ประถมศึกษา', maxYear: 6 },
    { value: 'มัธยมศึกษา', label: 'มัธยมศึกษา', maxYear: 6 },
    { value: 'ปวช.',        label: 'ปวช.',        maxYear: 3 },
    { value: 'ปวส.',        label: 'ปวส.',        maxYear: 2 },
    { value: 'อุดมศึกษา',  label: 'อุดมศึกษา',  maxYear: 4 },
    { value: 'อื่นๆ',      label: 'อื่นๆ',      maxYear: 0 },
]

const selectedLevelConfig = computed(() =>
    educationLevelOptions.find(l => l.value === form.value.education_level) ?? null
)

const educationYearOptions = computed(() => {
    if (!selectedLevelConfig.value || selectedLevelConfig.value.maxYear === 0) return []
    return Array.from({ length: selectedLevelConfig.value.maxYear }, (_, i) => i + 1)
})

const academicYearOptions = computed(() => {
    const current = new Date().getFullYear() + 543
    return Array.from({ length: 5 }, (_, i) => current - 1 + i)
    // Example: 2568, 2569, 2570, 2571, 2572
})

const browseCover = () => { coverInput.value.click() };
function onCoverInputChange(event){
  form.value.cover = event.target.files[0];
  tempCover.value = URL.createObjectURL(event.target.files[0]);
}
function handleSelectCategory(category){
  form.value.category = category;
  isOpenCategoryOptions.value = false;
}
function handleSelectLevel(level) {
    form.value.education_level = level
    form.value.education_year = null
    isOpenLevelOptions.value = false
    isOpenYearOptions.value = false
}

function handleSelectYear(year) {
    form.value.education_year = year
    isOpenYearOptions.value = false
}
// function handleDateSelect(modelData){
//   courseRange.value = modelData;
//   form.value.start_date = new Date(modelData[0]);
//   form.value.end_date = new Date(modelData[1]);
// }

function handleStartDateSelection(startData){
    crsStartDate.value = startData;
    courseRange.value[0] = crsStartDate.value;
    form.value.start_date = new Date(crsStartDate.value) || null;
}

function handleEndDateSelection(endDateData){
    crsEndDate.value = endDateData;
    courseRange.value[1] = crsEndDate.value;
    form.value.end_date = new Date(crsEndDate.value) || null;
}



// Net Price Calculation
const netPrice = computed(() => {
    if (!form.value.saleable) return 0;
    const price = Number(form.value.price) || 0;
    const discount = Number(form.value.discount) || 0;
    
    if (form.value.discount_type === 'percent') {
        const discountAmount = (price * discount) / 100;
        return Math.max(0, price - discountAmount);
    }
    
    return Math.max(0, price - discount);
});

async function handleSubmitForm(){
  try {
    const config = { headers: { 'content-type': 'multipart/form-data' } }
    const courseFormData = new FormData();

    courseFormData.append('academy_id', form.value.academy_id ?? null);
    courseFormData.append('code', form.value.code);
    courseFormData.append('name', form.value.name);
    courseFormData.append('description', form.value.description);
    courseFormData.append('category', form.value.category);
    if (form.value.education_level) courseFormData.append('education_level', form.value.education_level)
    if (form.value.education_year)  courseFormData.append('education_year',  form.value.education_year)
    courseFormData.append('credit_units', form.value.credit_units);
    courseFormData.append('hours_per_week', form.value.hours_per_week);
    courseFormData.append('start_date', new Date(form.value.start_date).toISOString() ?? null);
    courseFormData.append('end_date', new Date(form.value.end_date).toISOString() ?? null);
    courseFormData.append('auto_accept_members', form.value.auto_accept_members ? 1 : 0);
    courseFormData.append('tuition_fees', form.value.tuition_fees);
    courseFormData.append('saleable', form.value.saleable ? 1 : 0);
    courseFormData.append('price', form.value.price);
    courseFormData.append('discount', form.value.discount);
    courseFormData.append('discount_type', form.value.discount_type);
    courseFormData.append('semester', form.value.semester);
    courseFormData.append('academic_year', form.value.academic_year);
    courseFormData.append('status', form.value.status ? 1 : 0);
    
    // form.value.cover ? courseFormData.append('cover', form.value.cover): null;
    if (form.value.cover) {
      courseFormData.append('cover', form.value.cover);
    }

    const courseResp = await axios.post(`/courses`, courseFormData , config);

      if (courseResp.data && courseResp.data.success) {
        // Dev-only logging

        Swal.fire(
          'สำเร็จ',
          'การบันทึกข้อมูลเสร็จสมบูรณ์',
          'success'
      );

      // router.get(`/academies/${props.academy.data.id}/courses/${newCourse.id}`);
      router.get(`/Learn/Courses/${courseResp.data.newCourse.id}`);

    }

  } catch (error) {
    // form.value = defaultFormValue.value;
    Swal.fire(
        'ล้มเหลว',
        'เกิดข้อผิดพลาดในการบันทึกข้อมูล, <br />กรุณาตรวจสอบความถูกต้องของข้อมูล' + ' ' + error.message ,
        'error'
    );
  }

}


</script>

<template>
  <CoursesLayout coursePageTitle="สร้างรายวิชาใหม่">
    <template #coursesMainContent>
       <div class="max-w-7xl mx-auto pb-12">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">{{ headerTitle }}</h1>
            <p class="text-gray-500 dark:text-gray-400">กรอกข้อมูลเพื่อสร้างรายวิชาใหม่สำหรับชุมชนแห่งการเรียนรู้</p>
        </div>

        <form @submit.prevent="handleSubmitForm" class="grid grid-cols-1 lg:grid-cols-12 gap-8" id="create-new-course-form">
            
            <!-- LEFT COLUMN: Identity & Preview -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Cover Image Preview Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">รูปภาพปกรายวิชา</label>
                    
                    <div class="group relative aspect-video w-full rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-violet-500 dark:hover:border-violet-500 transition-colors cursor-pointer"
                         @click="browseCover">
                        
                        <!-- Image -->
                        <img v-if="tempCover" :src="tempCover" class="w-full h-full object-cover" />
                        
                        <!-- Overlay/Placeholder -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity">
                             <Icon icon="heroicons:camera" class="w-10 h-10 text-white mb-2" />
                             <span class="text-white text-sm font-medium">เปลี่ยนรูปปก</span>
                        </div>
                        
                        <div v-if="!tempCover || tempCover.includes('default_cover')" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                             <Icon icon="heroicons:photo" class="w-12 h-12 text-gray-400 mb-2" />
                             <span class="text-gray-500 text-sm">อัปโหลดรูปภาพ</span>
                        </div>

                        <input type="file" class="hidden" accept="image/*" ref="coverInput" @change="onCoverInputChange" />
                    </div>
                    <p class="text-xs text-gray-500 mt-2 text-center">แนะนำขนาด 1920x1080px หรืออัตราส่วน 16:9</p>
                </div>

                <!-- Instructor Card -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">ผู้สอน</label>
                    <div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                        <img 
                            :src="$page.props.auth.user.avatar || $page.props.auth.user.profile_photo_url" 
                            :alt="$page.props.auth.user.name"
                            class="w-12 h-12 rounded-full ring-2 ring-white dark:ring-gray-600 object-cover"
                        >
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-gray-100">{{ $page.props.auth.user.name }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Instructor</p>
                        </div>
                    </div>
                </div>

                 <!-- Course Code -->
                 <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                     <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รหัสวิชา</label>
                     <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Icon icon="heroicons:qr-code" class="h-5 w-5 text-gray-400" />
                        </div>
                        <input type="text" v-model="form.code" 
                            class="block w-full pl-10 pr-3 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all"
                            placeholder="เช่น CS101"
                        />
                     </div>
                 </div>
            </div>

            <!-- RIGHT COLUMN: Form Details -->
            <div class="lg:col-span-8 space-y-8">
                
                <!-- Section 1: Basic Info -->
                <section>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/50 text-violet-600 flex items-center justify-center text-sm">1</span>
                        ข้อมูลทั่วไป
                    </h2>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 space-y-6">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อรายวิชา <span class="text-red-500">*</span></label>
                            <input type="text" v-model="form.name" required
                                class="block w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:bg-white dark:focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all font-medium text-lg"
                                placeholder="ตั้งชื่อวิชาให้สื่อความหมายและน่าสนใจ"
                            />
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คำอธิบายรายวิชา</label>
                            <textarea v-model="form.description" rows="5"
                                class="block w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:bg-white dark:focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all"
                                placeholder="รายละเอียดเกี่ยวกับสิ่งที่ผู้เรียนจะได้เรียนรู้..."
                            ></textarea>
                        </div>
                    </div>
                </section>

                <!-- Section 2: Classification -->
                <section>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/50 text-blue-600 flex items-center justify-center text-sm">2</span>
                        การจัดหมวดหมู่และระดับ
                    </h2>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Category -->
                         <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">กลุ่มสาระการเรียนรู้</label>
                            <div class="relative">
                                <button type="button" @click="isOpenCategoryOptions = !isOpenCategoryOptions"
                                    class="w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-left px-4 py-3 rounded-xl flex items-center justify-between hover:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all"
                                >
                                    <span :class="form.category ? 'text-gray-900 dark:text-white' : 'text-gray-400'">
                                        {{ form.category || 'เลือกกลุ่มสาระ' }}
                                    </span>
                                    <Icon icon="heroicons:chevron-down" class="w-5 h-5 text-gray-400" />
                                </button>
                                
                                <!-- Dropdown -->
                                <div v-if="isOpenCategoryOptions" class="absolute z-10 mt-2 w-full bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 max-h-60 overflow-y-auto">
                                    <div v-for="cat in courseCategories" :key="cat.name" 
                                        @click="handleSelectCategory(cat.name)"
                                        class="px-4 py-3 hover:bg-violet-50 dark:hover:bg-violet-900/20 cursor-pointer text-gray-700 dark:text-gray-200 text-sm transition-colors border-b border-gray-50 dark:border-gray-700/50 last:border-0"
                                    >
                                        {{ cat.name }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Level -->
                        <div class="grid grid-cols-2 gap-3">
                            <!-- education_level dropdown -->
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ระดับการศึกษา</label>
                                <div class="relative">
                                    <button type="button" @click="isOpenLevelOptions = !isOpenLevelOptions"
                                        class="w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-left px-4 py-3 rounded-xl flex items-center justify-between hover:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all"
                                    >
                                        <span :class="form.education_level ? 'text-gray-900 dark:text-white' : 'text-gray-400'">
                                            {{ form.education_level || 'เลือกระดับ' }}
                                        </span>
                                        <Icon icon="heroicons:chevron-down" class="w-5 h-5 text-gray-400" />
                                    </button>
                                    <div v-if="isOpenLevelOptions" class="absolute z-10 mt-2 w-full bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                                        <div v-for="lvl in educationLevelOptions" :key="lvl.value"
                                            @click="handleSelectLevel(lvl.value)"
                                            class="px-4 py-3 hover:bg-violet-50 dark:hover:bg-violet-900/20 cursor-pointer text-gray-700 dark:text-gray-200 text-sm transition-colors border-b border-gray-50 dark:border-gray-700/50 last:border-0"
                                        >{{ lvl.label }}</div>
                                    </div>
                                </div>
                            </div>
                            <!-- education_year dropdown -->
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชั้นปีที่</label>
                                <div class="relative">
                                    <button type="button"
                                        :disabled="!educationYearOptions.length"
                                        @click="isOpenYearOptions = !isOpenYearOptions"
                                        class="w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-left px-4 py-3 rounded-xl flex items-center justify-between hover:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                                    >
                                        <span :class="form.education_year ? 'text-gray-900 dark:text-white' : 'text-gray-400'">
                                            {{ form.education_year ? `ปีที่ ${form.education_year}` : 'เลือกชั้นปี' }}
                                        </span>
                                        <Icon icon="heroicons:chevron-down" class="w-5 h-5 text-gray-400" />
                                    </button>
                                    <div v-if="isOpenYearOptions && educationYearOptions.length" class="absolute z-10 mt-2 w-full bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                                        <div v-for="yr in educationYearOptions" :key="yr"
                                            @click="handleSelectYear(yr)"
                                            class="px-4 py-3 hover:bg-violet-50 dark:hover:bg-violet-900/20 cursor-pointer text-gray-700 dark:text-gray-200 text-sm transition-colors border-b border-gray-50 dark:border-gray-700/50 last:border-0"
                                        >ปีที่ {{ yr }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section 3: Schedule & Settings -->
                <section>
                     <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-600 flex items-center justify-center text-sm">3</span>
                        การตั้งค่าและเวลา
                    </h2>
                     <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 space-y-6">
                        
                         <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                             <!-- Semester -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ภาคเรียน</label>
                                <select v-model="form.semester" class="block w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500">
                                    <option value="">เลือกภาคเรียน</option>
                                    <option value="1">ภาคเรียนที่ 1</option>
                                    <option value="2">ภาคเรียนที่ 2</option>
                                    <option value="3">ภาคเรียนที่ 3</option>
                                    <option value="summer">ภาคเรียนฤดูร้อน</option>
                                    <option value="weekend">เสาร์-อาทิตย์</option>
                                </select>
                            </div>

                            <!-- Year -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ปีการศึกษา</label>
                                <select v-model="form.academic_year" class="block w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500">
                                    <option value="">เลือกปีการศึกษา</option>
                                    <option v-for="year in academicYearOptions" :key="year" :value="year.toString()">{{ year }}</option>
                                </select>
                            </div>

                            <!-- Credits -->
                             <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หน่วยกิต</label>
                                <div class="relative">
                                    <input type="number" v-model="form.credit_units" min="0" 
                                        class="block w-full pl-4 pr-12 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    />
                                    <span class="absolute right-4 top-3 text-gray-400 text-sm">หน่วย</span>
                                </div>
                            </div>

                             <!-- Hours -->
                             <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">คาบ/สัปดาห์</label>
                                <div class="relative">
                                    <input type="number" v-model="form.hours_per_week" min="0" 
                                        class="block w-full pl-4 pr-12 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500"
                                    />
                                    <span class="absolute right-4 top-3 text-gray-400 text-sm">ชม.</span>
                                </div>
                            </div>
                         </div>

                         <div class="border-t border-gray-100 dark:border-gray-700 pt-6 mt-2">
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Start Date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่เริ่มเรียน</label>
                                    <ClientOnly>
                                        <VueDatePicker v-model="crsStartDate" placeholder="เลือกวันเริ่มต้น" :format="'dd/MM/yyyy'" auto-apply @update:model-value="handleStartDateSelection" 
                                            input-class-name="!bg-white dark:!bg-gray-700 !border-gray-200 dark:!border-gray-600 !rounded-xl !py-3 !text-gray-900 dark:!text-white"
                                        />
                                    </ClientOnly>
                                </div>
                                <!-- End Date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่สิ้นสุด</label>
                                    <ClientOnly>
                                        <VueDatePicker v-model="crsEndDate" placeholder="เลือกวันสิ้นสุด" :format="'dd/MM/yyyy'" auto-apply @update:model-value="handleEndDateSelection"
                                             input-class-name="!bg-white dark:!bg-gray-700 !border-gray-200 dark:!border-gray-600 !rounded-xl !py-3 !text-gray-900 dark:!text-white"
                                        />
                                    </ClientOnly>
                                </div>
                             </div>
                         </div>

                         <!-- Auto Accept -->
                         <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-100 dark:border-gray-700">
                             <div>
                                 <h4 class="font-medium text-gray-900 dark:text-white">ตอบรับสมาชิกอัตโนมัติ</h4>
                                 <p class="text-sm text-gray-500">ผู้เรียนจะเข้าเรียนได้ทันทีโดยไม่ต้องรอการอนุมัติ</p>
                             </div>
                              <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.auto_accept_members" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-violet-300 dark:peer-focus:ring-violet-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-violet-600"></div>
                             </label>
                         </div>
                     </div>
                </section>

                <!-- Section 4: Pricing -->
                <section>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 flex items-center justify-center text-sm">4</span>
                        ค่าธรรมเนียมและราคา
                    </h2>
                     <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 space-y-6">
                         
                         <!-- Is Saleable Toggle -->
                        <div class="flex items-center justify-between mb-4">
                             <div>
                                 <h4 class="font-medium text-gray-900 dark:text-white">เปิดขายรายวิชา</h4>
                                 <p class="text-sm text-gray-500">เปิดใช้งานหากต้องการขายคอร์สนี้ให้บุคคลทั่วไป</p>
                             </div>
                              <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.saleable" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
                             </label>
                         </div>

                         <div v-if="form.saleable" class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fade-in-down">
                            <!-- Price -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ราคาปกติ</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 font-bold">฿</span>
                                    </div>
                                    <input type="number" v-model="form.price" min="0" placeholder="0.00"
                                        class="block w-full pl-8 pr-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    />
                                </div>
                            </div>
                            
                            <!-- Discount -->
                             <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ส่วนลด</label>
                                <div class="flex">
                                    <div class="relative flex-1">
                                        <input type="number" v-model="form.discount" min="0" 
                                            class="block w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-l-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 z-10"
                                            placeholder="0"
                                        />
                                    </div>
                                    <select v-model="form.discount_type" class="px-3 border-y border-r border-gray-200 dark:border-gray-600 rounded-r-xl bg-gray-50 dark:bg-gray-600 text-gray-700 dark:text-white focus:outline-none">
                                        <option value="fixed">฿</option>
                                        <option value="percent">%</option>
                                    </select>
                                </div>
                            </div>

                             <!-- Net Price Summary -->
                            <div class="md:col-span-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-4 border border-emerald-100 dark:border-emerald-800/50 flex justify-between items-center">
                                <span class="text-emerald-800 dark:text-emerald-100 font-medium">ราคาสุทธิ (Net Price)</span>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">฿{{ netPrice.toLocaleString() }}</div>
                                    <div v-if="Number(form.discount) > 0" class="text-xs text-emerald-600/70">
                                        (ลด {{ form.discount_type === 'percent' ? form.discount + '%' : '฿' + form.discount }})
                                    </div>
                                </div>
                            </div>
                         </div>
                         
                         <!-- Member Fee -->
                         <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ค่าธรรมเนียมสมัครสมาชิก (Point)</label>
                            <div class="relative max-w-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <Icon icon="noto:coin" class="h-5 w-5" />
                                </div>
                                <input type="number" v-model="form.tuition_fees" min="0" placeholder="0"
                                    class="block w-full pl-10 pr-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500"
                                />
                            </div>
                            <p class="text-xs text-gray-500 mt-1">คะแนนที่ผู้เรียนต้องใช้เพื่อเข้าร่วมรายวิชานี้ (0 = ฟรี)</p>
                         </div>
                     </div>
                </section>
                
                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-4 pt-4 pb-12">
                     <button type="button" @click="router.push('/Learn/Courses')" 
                        class="px-6 py-3 rounded-xl border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                     >
                         ยกเลิก
                     </button>
                     <button type="submit" 
                        class="px-8 py-3 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-bold shadow-lg shadow-violet-200 dark:shadow-none transition-all transform hover:-translate-y-0.5"
                     >
                         สร้างรายวิชา
                     </button>
                </div>
            </div>
        </form>
       </div>
    </template>
  </CoursesLayout>
</template>

