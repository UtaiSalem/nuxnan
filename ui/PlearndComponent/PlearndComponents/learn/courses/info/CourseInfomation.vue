<script setup>
import { ref} from 'vue';
import { Icon } from '@iconify/vue';

import InputLabel from '@/Components/InputLabel.vue'

import Textarea from 'primevue/textarea';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
    course: Object,
    isCourseAdmin: Boolean
});

const emit = defineEmits(['update-course']);

const crsStartDate = ref(props.course.start_date);
const crsEndDate = ref(props.course.end_date);

const form = ref({
  code: props.course.code,
  name: props.course.name,
  description: props.course.description,
  category: props.course.category,
  level: props.course.level,
  credit_units: props.course.credit_units,
  hours_per_week: props.course.hours_per_week,

  start_date: crsStartDate.value,
  end_date: crsEndDate.value,

  auto_accept_members: props.course.setting.auto_accept_members === 1 ? true : false,
  tuition_fees: props.course.tuition_fees ?? 0,
  saleable: props.course.saleable === 1 ? true : false,
  price: props.course.price ?? 0,
  status: props.course.status,

});

</script>

<template>
    <div class="w-full mx-auto">
        <div class="plearnd-card my-4 font-bold uppercase text-2xl bg-blue-500 text-white">
            ข้อมูลรายวิชา
        </div>

        <div class="bg-white rounded-lg border-t-4 border-blue-500 px-4 pb-4 mb-4">
            <form class="space-y-2" id="create-new-course-form">
                <div class="grid grid-cols-1  my-2">
                    <h3 class="text-lg font-semibold text-gray-600 dark:text-white ">
                        รหัสวิชา
                    </h3>
                    <input 
                        id="course_code_input_form" 
                        type="text" 
                        class="bg-gray-100 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" 
                        v-model="form.code" 
                        disabled 
                    />
                </div>
                <div class="grid grid-cols-1 gap-4 my-2">
                    <h3 class="text-lg font-semibold text-gray-600 dark:text-white ">
                        ชื่อวิชา
                    </h3>
                    <Textarea id="course_name_input_form" v-model="form.name" rows="1" autoResize readonly
                        class="-mt-2 w-full items-center py-4 text-sm text-gray-800 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        placeholder="ชื่อรายวิชา" required>
                    </Textarea>
                </div>
                <div class="grid grid-cols-1 gap-4 my-2">
                    <InputLabel for="course_description_input_form" value="คำอธิบายรายวิชา" />
                    <Textarea 
                        id="course_description_input_form" 
                        v-model="form.description" 
                        rows="8" autoResize readonly
                        class="block p-2.5 -mt-2 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        placeholder="คำอธิบายรายวิชา">
                    </Textarea>

                </div>

                <div class="grid grid-cols-1 gap-2 my-2">
                    <InputLabel for="course_category_input_form" value="กลุ่มสาระการเรียนรู้" />
                    <div class="flex justify-between items-center relative">

                        <input id="course_category_input_form" type="text"
                            class="text-start w-full rounded-lg mr-[1px] border-1 border-gray-300"
                            v-model="form.category" disabled/>

                    </div>
                </div>

                <div class="grid grid-cols-1 gap-2 my-2">
                    <InputLabel for="course_level_input_form" value="ระดับการเรียนรู้" />
                    <div class="flex justify-between items-center relative">
                        <input id="course_level_input_form" type="text"
                            class="text-start w-full rounded-lg mr-[1px] border-1 border-gray-300"
                            v-model="form.level" disabled />
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-2">

                    <div class="relative sm:my-3">
                        <label for="course-credit_unit-input"
                            class="cursor-text peer-focus:cursor-default peer-autofill:-top-2 absolute left-2 -top-2 z-[1] px-2 text-xs text-slate-400 transition-all before:absolute before:top-0 before:left-0 before:z-[-1] before:block before:h-full before:w-full before:bg-white before:transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:left-12 peer-placeholder-shown:text-sm peer-required:after:text-pink-500 peer-required:after:content-['\00a0*'] peer-invalid:text-pink-500 peer-focus:-top-2 peer-focus:left-2 peer-focus:text-xs peer-focus:text-violet-500 peer-invalid:peer-focus:text-pink-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400 peer-disabled:before:bg-transparent">
                            หน่วยกิต
                        </label>
                        <input id="course-credit_unit-input" type="number" min="0" v-model="form.credit_units"
                            placeholder="0" disabled
                            class="relative w-full h-12 px-4 pl-14 placeholder-transparent transition-all border rounded-lg outline-none focus-visible:outline-none peer border-slate-300 text-slate-500 autofill:bg-white invalid:border-pink-500 invalid:text-pink-500 focus:border-violet-500 focus:outline-none invalid:focus:border-pink-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400" />
                        <Icon icon="fluent:number-symbol-square-24-regular"
                            class="absolute w-7 h-7 top-3 left-4 text-violet-600 peer-disabled:cursor-not-allowed" />
                    </div>

                    <div class="relative sm:my-3">
                        <label for="course-hpw-input"
                            class="cursor-text peer-focus:cursor-default peer-autofill:-top-2 absolute left-4 -top-2 z-[1] px-2 text-xs text-slate-400 transition-all before:absolute before:top-0 before:left-0 before:z-[-1] before:block before:h-full before:w-full before:bg-white before:transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:left-10 peer-placeholder-shown:text-sm peer-required:after:text-pink-500 peer-required:after:content-['\00a0*'] peer-invalid:text-pink-500 peer-focus:-top-2 peer-focus:left-2 peer-focus:text-xs peer-focus:text-violet-500 peer-invalid:peer-focus:text-pink-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400 peer-disabled:before:bg-transparent">
                            คาบ / สัปดาห์
                        </label>
                        <input id="course-hpw-input" type="number" min="0" v-model="form.hours_per_week" placeholder="0" disabled
                            class="relative w-full h-12 px-4 pl-12 placeholder-transparent transition-all border rounded-lg outline-none focus-visible:outline-none peer border-slate-300 text-slate-500 autofill:bg-white invalid:border-pink-500 invalid:text-pink-500 focus:border-violet-500 focus:outline-none invalid:focus:border-pink-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400" />
                        <Icon icon="bi:calendar4-week"
                            class="absolute w-5 h-5 top-4 left-4 text-violet-600 peer-disabled:cursor-not-allowed" />
                    </div>

                </div>

                <div class="flex">
                    <div class="flex items-center mt-2">
                        <div class="relative w-1/5 flex items-center justify-start h-10">
                            <input id="asm-has-starte-date-input" type="checkbox"
                                class="hidden w-4 h-4 transition-colors bg-white border-2 rounded appearance-none cursor-pointer focus-visible:outline-none peer border-slate-500 checked:border-emerald-500 checked:bg-emerald-500 checked:hover:border-emerald-600 checked:hover:bg-emerald-600 focus:outline-none checked:focus:border-emerald-700 checked:focus:bg-emerald-700 disabled:cursor-not-allowed disabled:border-slate-100 disabled:bg-slate-50" />
                            <label class="px-2 cursor-pointer text-slate-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400"
                                for="asm-has-starte-date-input">
                                เริ่ม
                            </label>
                        </div>
                        <div class="w-4/5 flex flex-wrap justify-between gap-2">
                            <div class="flex items-center w-full">
                                <VueDatePicker 
                                    id="assigment-start-date-input"
                                    name="assignment-start-date-input" 
                                    :action-row="{ showNow: true }" now-button-label="Now"
                                    :model-value="crsStartDate" 
                                    placeholder="วันที่เริ่ม"
                                    :format="'dd/MM/yyyy'" disabled
                                ></VueDatePicker>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center mt-2">
                        <div class="relative w-1/5 flex items-center justify-start h-10">
                            <input id="asm-has-end-date-input" type="checkbox"
                                class="hidden w-4 h-4 transition-colors bg-white border-2 rounded appearance-none cursor-pointer focus-visible:outline-none peer border-slate-500 checked:border-emerald-500 checked:bg-emerald-500 checked:hover:border-emerald-600 checked:hover:bg-emerald-600 focus:outline-none checked:focus:border-emerald-700 checked:focus:bg-emerald-700 disabled:cursor-not-allowed disabled:border-slate-100 disabled:bg-slate-50" />
                            <label class="px-2 cursor-pointer text-slate-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400"
                                for="asm-has-end-date-input">
                                ถึง
                            </label>
                        </div>
                        <div class="w-4/5 flex flex-wrap justify-between gap-2">
                            <div class="flex items-center w-full">
                                <VueDatePicker 
                                    id="assigment-end-date-input"
                                    name="assignment-end-date-input"
                                    :model-value="crsEndDate" 
                                    placeholder="วันที่สิ้นสุด"
                                    :format="'dd/MM/yyyy'" disabled
                                ></VueDatePicker>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

    </div>
</template>
