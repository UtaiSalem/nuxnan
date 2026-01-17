<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Icon } from '@iconify/vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
    course: Object,
    isCourseAdmin: Boolean
});

const emit = defineEmits(['update-course']);

// --- Form Data & Initialization ---
const form = ref({
    academy_id: '',
    code: '',
    name: '',
    description: '',
    category: '',
    level: '',
    credit_units: 0,
    hours_per_week: 0,
    start_date: null,
    end_date: null,
    auto_accept_members: false,
    tuition_fees: 0,
    saleable: false,
    price: 0,
    discount: 0,
    discount_type: 'fixed',
    semester: '',
    academic_year: '',
    status: true,
    cover: null, // For new file upload
});

const tempCover = ref(null);
const coverInput = ref(null);
const courseRange = ref([null, null]); // For DatePicker range

// Options (Can be moved to a shared constant or fetched)
const courseCategories = ref([
    { name: 'ภาษาไทย' }, { name: 'คณิตศาสตร์' }, { name: 'วิทยาศาสตร์' },
    { name: 'สังคมศึกษา ศาสนา และวัฒนธรรม' }, { name: 'สุขศึกษาและพลศึกษา' },
    { name: 'ศิลปะ' }, { name: 'การงานอาชีพและเทคโนโลยี' }, { name: 'ภาษาต่างประเทศ' },
]);

const courseLevelOptions = ref([
    { level: 'ชั้นประถมศึกษาปีที่ 1' }, { level: 'ชั้นประถมศึกษาปีที่ 2' },
    { level: 'ชั้นประถมศึกษาปีที่ 3' }, { level: 'ชั้นประถมศึกษาปีที่ 4' },
    { level: 'ชั้นประถมศึกษาปีที่ 5' }, { level: 'ชั้นประถมศึกษาปีที่ 6' },
    { level: 'ชั้นมัธยมศึกษาปีที่ 1' }, { level: 'ชั้นมัธยมศึกษาปีที่ 2' },
    { level: 'ชั้นมัธยมศึกษาปีที่ 3' }, { level: 'ชั้นมัธยมศึกษาปีที่ 4' },
    { level: 'ชั้นมัธยมศึกษาปีที่ 5' }, { level: 'ชั้นมัธยมศึกษาปีที่ 6' },
]);

const myAcademies = ref([]);

// --- Initialize Form from Props ---
onMounted(async () => {
    fetchMyAcademies();
    initializeForm();
});

function initializeForm() {
    if (!props.course) return;
    const c = props.course;
    
    // Check if academy_id exists in course object, if not, it should be null/empty
    // But importantly, we need to bind it.
    
    form.value = {
        academy_id: c.academy_id || '',
        code: c.code || '',
        name: c.name || '',
        description: c.description || '',
        category: c.category || '',
        level: c.level || '',
        credit_units: c.credit_units || 0,
        hours_per_week: c.hours_per_week || 0,
        auto_accept_members: c.course_settings?.auto_accept_members == 1,
        tuition_fees: c.tuition_fees || 0,
        saleable: c.saleable == 1,
        price: c.price || 0,
        discount: c.discount || 0,
        discount_type: 'fixed', // Assuming default
        semester: c.semester || '',
        academic_year: c.academic_year || '',
        status: c.status == 1,
        cover: null,
    };

    // Dates
    if (c.start_date) form.value.start_date = new Date(c.start_date);
    if (c.end_date) form.value.end_date = new Date(c.end_date);
    courseRange.value = [form.value.start_date, form.value.end_date];

    // Cover
    if (c.cover_url) {
        tempCover.value = c.cover_url;
    } else if (c.cover) {
         // Fallback if cover_url not computed yet or just filename
         tempCover.value = c.cover.startsWith('http') ? c.cover : `/storage/images/courses/covers/${c.cover}`;
    }
}

async function fetchMyAcademies() {
    try {
        const response = await axios.get('/academies/users/' + usePage().props.auth.user.id + '/my-academies');
        if (response.data.success) {
            myAcademies.value = response.data.academies;
        }
    } catch (error) {
        console.error("Failed to fetch academies", error);
    }
}


// --- Handlers ---
const browseCover = () => { coverInput.value.click() };
function onCoverInputChange(event) {
    const file = event.target.files[0];
    if (file) {
        form.value.cover = file;
        tempCover.value = URL.createObjectURL(file);
    }
}


function handleSubmit() {
    // Clone form data to emit, maybe format dates if needed by parent
    emit('update-course', form.value);
}

// Net Price Calculation (Same as Create Page)
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

</script>

<template>
  <div class="bg-white dark:bg-gray-800 p-8 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700">
      
      <div class="mb-8 border-b border-gray-100 dark:border-gray-700 pb-4">
          <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
              <Icon icon="mdi:cog" class="text-violet-500" />
              ตั้งค่ารายวิชา
          </h3>
          <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">แก้ไขข้อมูลและรายละเอียดของรายวิชา</p>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-8">
          
          <!-- Section 1: Identity -->
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
              <!-- Cover Image -->
              <div class="lg:col-span-4">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปภาพปก</label>
                  <div class="group relative aspect-video w-full rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-violet-500 transition-colors cursor-pointer"
                       @click="browseCover">
                      <img v-if="tempCover" :src="tempCover" class="w-full h-full object-cover" />
                      <div class="absolute inset-0 flex flex-col items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity">
                           <Icon icon="heroicons:camera" class="w-8 h-8 text-white mb-2" />
                           <span class="text-white text-xs">เปลี่ยนรูป</span>
                      </div>
                      <div v-if="!tempCover" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
                           <Icon icon="heroicons:photo" class="w-10 h-10 mb-2" />
                           <span class="text-xs">อัปโหลด</span>
                      </div>
                      <input type="file" class="hidden" accept="image/*" ref="coverInput" @change="onCoverInputChange" />
                  </div>
              </div>

              <!-- Basic Fields -->
              <div class="lg:col-span-8 space-y-4">
                  <!-- Name -->
                  <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อรายวิชา <span class="text-red-500">*</span></label>
                      <input type="text" v-model="form.name" required
                          class="block w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:outline-none"
                      />
                  </div>
                   <!-- Code & Academy -->
                   <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รหัสวิชา</label>
                          <input type="text" v-model="form.code"
                              class="block w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:outline-none"
                          />
                      </div>
                      <div>
                          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานศึกษา (Academy)</label>
                          <select v-model="form.academy_id" 
                              class="block w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:outline-none">
                              <option value="">-- ไม่สังกัด (อิสระ) --</option>
                              <option v-for="academy in myAcademies" :key="academy.id" :value="academy.id">
                                  {{ academy.name }}
                              </option>
                          </select>
                          <p class="text-xs text-gray-500 mt-1">เลือกสถานศึกษาที่ต้องการสังกัด หรือเลือก "ไม่สังกัด" เพื่อเป็นวิชาอิสระ</p>
                      </div>
                   </div>

                   <!-- Description -->
                   <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบายรายวิชา</label>
                      <textarea v-model="form.description" rows="3"
                          class="block w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:outline-none"
                      ></textarea>
                   </div>
              </div>
          </div>

          <hr class="border-gray-100 dark:border-gray-700" />

          <!-- Section 2: Details -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <!-- Category -->
              <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">กลุ่มสาระ</label>
                  <select v-model="form.category" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500">
                      <option value="">เลือกกลุ่มสาระ</option>
                      <option v-for="cat in courseCategories" :key="cat.name" :value="cat.name">{{ cat.name }}</option>
                  </select>
              </div>

               <!-- Level -->
               <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ระดับชั้น</label>
                  <select v-model="form.level" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500">
                      <option value="">เลือกระดับชั้น</option>
                      <option v-for="lvl in courseLevelOptions" :key="lvl.level" :value="lvl.level">{{ lvl.level }}</option>
                  </select>
              </div>

              <!-- Credits -->
              <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หน่วยกิต</label>
                  <input type="number" v-model="form.credit_units" min="0" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500" />
              </div>

              <!-- Hours -->
              <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชม./สัปดาห์</label>
                  <input type="number" v-model="form.hours_per_week" min="0" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500" />
              </div>
          </div>
          
           <!-- Dates & Semester -->
           <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <!-- Start Date -->
              <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่เริ่ม</label>
                  <ClientOnly>
                      <VueDatePicker v-model="form.start_date" :format="'dd/MM/yyyy'" auto-apply 
                          input-class-name="!bg-white dark:!bg-gray-700 !border-gray-200 dark:!border-gray-600 !rounded-xl !py-2.5 !text-gray-900 dark:!text-white"
                      />
                  </ClientOnly>
              </div>
               <!-- End Date -->
               <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่สิ้นสุด</label>
                  <ClientOnly>
                      <VueDatePicker v-model="form.end_date" :format="'dd/MM/yyyy'" auto-apply 
                          input-class-name="!bg-white dark:!bg-gray-700 !border-gray-200 dark:!border-gray-600 !rounded-xl !py-2.5 !text-gray-900 dark:!text-white"
                      />
                  </ClientOnly>
              </div>
               <!-- Semester -->
               <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ภาคเรียน</label>
                  <select v-model="form.semester" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500">
                      <option value="1">ภาคเรียนที่ 1</option>
                      <option value="2">ภาคเรียนที่ 2</option>
                      <option value="summer">ภาคเรียนฤดูร้อน</option>
                  </select>
              </div>
               <!-- Year -->
               <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ปีการศึกษา</label>
                   <input type="text" v-model="form.academic_year" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500" />
               </div>
           </div>


          <hr class="border-gray-100 dark:border-gray-700" />

          <!-- Section 3: Settings & Price -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <!-- Settings -->
              <div class="space-y-4">
                  <h4 class="font-medium text-gray-900 dark:text-white">การตั้งค่าทั่วไป</h4>
                  <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ตอบรับสมาชิกอัตโนมัติ</span>
                       <label class="relative inline-flex items-center cursor-pointer">
                          <input type="checkbox" v-model="form.auto_accept_members" class="sr-only peer">
                          <div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-violet-600"></div>
                       </label>
                  </div>
                   <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดสถานะรายวิชา (Active)</span>
                       <label class="relative inline-flex items-center cursor-pointer">
                          <input type="checkbox" v-model="form.status" class="sr-only peer">
                          <div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-500"></div>
                       </label>
                  </div>
              </div>

               <!-- Pricing -->
               <div class="space-y-4">
                  <h4 class="font-medium text-gray-900 dark:text-white">ราคาและค่าธรรมเนียม</h4>
                   <div class="flex items-center justify-between mb-2">
                       <span class="text-sm text-gray-500">เปิดขายรายวิชา</span>
                       <label class="relative inline-flex items-center cursor-pointer">
                          <input type="checkbox" v-model="form.saleable" class="sr-only peer">
                          <div class="w-11 h-6 bg-gray-200 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
                       </label>
                   </div>
                   
                   <div v-if="form.saleable" class="space-y-3">
                       <div class="grid grid-cols-2 gap-3">
                           <div>
                               <label class="text-xs text-gray-500 block mb-1">ราคาปกติ (฿)</label>
                               <input type="number" v-model="form.price" class="w-full px-3 py-2 border rounded-lg bg-gray-50" />
                           </div>
                           <div>
                               <label class="text-xs text-gray-500 block mb-1">ส่วนลด</label>
                               <div class="flex">
                                  <input type="number" v-model="form.discount" class="w-full px-3 py-2 border rounded-l-lg bg-gray-50" />
                                  <select v-model="form.discount_type" class="border-y border-r rounded-r-lg bg-gray-100 px-2">
                                      <option value="fixed">฿</option>
                                      <option value="percent">%</option>
                                  </select>
                               </div>
                           </div>
                       </div>
                       <div class="p-3 bg-emerald-50 rounded-lg text-emerald-800 text-sm flex justify-between font-bold">
                           <span>ราคาสุทธิ</span>
                           <span>฿{{ netPrice.toLocaleString() }}</span>
                       </div>
                   </div>

                   <div class="pt-2">
                       <label class="text-sm text-gray-500 block mb-1">ค่าธรรมเนียมเข้าเรียน (Point)</label>
                       <input type="number" v-model="form.tuition_fees" class="w-full px-4 py-2 border rounded-xl bg-gray-50 dark:bg-gray-700 dark:text-white" />
                   </div>
               </div>
          </div>

          <!-- Submit Button -->
          <div class="pt-6 flex justify-end">
              <button type="submit" 
                  class="px-8 py-3 bg-gradient-to-r from-violet-600 to-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-violet-200 dark:shadow-none hover:shadow-xl hover:-translate-y-0.5 transition-all">
                  บันทึกการเปลี่ยนแปลง
              </button>
          </div>

      </form>
  </div>
</template>
