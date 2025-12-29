<script setup>
import { ref, reactive } from 'vue';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

const props = defineProps({
    groupId: Number,
    attendance: Object,
});

const emit = defineEmits([
    'close-form',
    'updat-attendance',
    'delete-attendance'
]);

const isSaveingNewAttendance = ref(false);

const form = reactive(
    {
        description: props.attendance.description,
        start_at: props.attendance.start_at,
        finish_at: props.attendance.finish_at,
        late_time: props.attendance.late_time,
    }
);

const handleUpdateAttendance = async () => {
    try {
        isSaveingNewAttendance.value = true;
        let resp = await axios.patch(`/attendances/${props.attendance.id}`, {
            description: form.description,
            start_at: form.start_at,
            finish_at: form.finish_at,
            late_time: form.late_time,
        });
        if (resp.data && resp.data.success) {
            Swal.fire({
                title: 'บันทึกการแก้ไขสำเร็จ',
                icon: 'success',
                showConfirmButton: false,
                timer: 1200
            });
            emit('updat-attendance', resp.data.attendance);          
        }
    } catch (error) {
    } finally {
        isSaveingNewAttendance.value = false;
    }
}

const handleCloseAttendance = () => {
    emit('close-form');
}

</script>

<template>
    <div class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-700 opacity-90"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-visible shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6"
                role="dialog" aria-modal="true" aria-labelledby="modal-headline">
                <div class="hidden sm:block absolute top-0 right-0 pt-2 pr-2">
                    <button @click.prevent="handleCloseAttendance" type="button" data-behavior="cancel"
                        class="bg-red-200 hover:bg-red-300 p-1 rounded-full text-red-400 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex flex-col space-y-4">
                    <form @submit.prevent="handleUpdateAttendance" class="">
                        <div class="space-y-4">
                            <div class="border-b border-gray-900/10 pb-4">
                                <div class=" grid grid-cols-1 gap-4 sm:grid-cols-6">
                                    <div class="sm:col-span-3">
                                        <span :id="`attendance-start_date-input`"
                                            class="block text-sm font-medium leading-6 text-gray-900">เริ่มต้น</span>
                                        <div class="mt-2">
                                            <VueDatePicker id="attendanceDateInput" name="attendanceDateInput"
                                                :format="'dd/MM/yyyy HH:mm'" v-model="form.start_at"
                                                :model-value="form.start_at" placeholder="วันที่" />
                                        </div>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <span :id="`attendance-finish_date-input`"
                                            class="block text-sm font-medium leading-6 text-gray-900">สิ้นสุด</span>
                                        <div class="mt-2">
                                            <VueDatePicker id="attendanceDateInput" name="attendanceDateInput"
                                                :format="'dd/MM/yyyy HH:mm'" v-model="form.finish_at"
                                                :model-value="form.finish_at" placeholder="วันที่" />
                                        </div>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="late_time"
                                            class="block text-sm font-medium leading-6 text-gray-900">
                                            สายได้ไม่เกิน (นาที)
                                        </label>
                                        <div class="mt-2 w-24">
                                            <input type="number" :id="`${attendance.id}+ 'late_time'`" v-model="form.late_time"
                                                min="0"
                                                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                        </div>
                                    </div>
                                    <div class="sm:col-span-6">
                                        <label for="first-name"
                                            class="block text-sm font-medium leading-6 text-gray-900">คำอธิบาย</label>
                                        <div class="mt-2">
                                            <Textarea type="text" :name="form.description" id="first-name"
                                                :autocomplete="form.description || '_'"
                                                v-model="form.description" autoResize
                                                class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="mt-2 flex items-center justify-end gap-x-4">
                            <button type="button" @click.prevent="handleCloseAttendance"
                                class="text-sm font-semibold leading-6 text-orange-400 hover:text-orange-700 rounded-md bg-orange-200 hover:bg-orange-300 px-3 py-1.5">ปิด</button>
                            <button type="button" @click.prevent="emit('delete-attendance')"
                                class="text-sm font-semibold leading-6 text-rose-400 hover:text-rose-700 rounded-md bg-rose-200 hover:bg-rose-300 px-3 py-1.5">ลบบันทึกการเข้าเรียน</button>
                            <button type="submit" :disabled="isSaveingNewAttendance"
                                class="flex justify-center space-x-1 items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                                <Icon v-if="isSaveingNewAttendance" icon="svg-spinners:270-ring-with-bg"
                                    class="h-6 w-6 text-white" />
                                <span v-else> บันทึกการแก้ไข </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
