<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { usePage } from "@inertiajs/vue3";
import { Icon } from '@iconify/vue';
import axios from 'axios';

const props = defineProps({
    courseId: {
        type: Number,
        required: true,
    },
    groupId: {
        type: Number,
        required: true,
    },
    attendanceId: {
        type: Number,
        required: true,
    },
    attendanceDetail: {
        type: Object,
        required: true,
    },
    dataIndex: {
      type: Number,
    },
});

const emit = defineEmits(['request-unmember-course']);

const isLoading = ref([false, false, false, false, false]);
const loadingIndex = ref(null);
const isSaveCommentLoading = computed(()=> isLoading.value[4]);

const form = reactive({
    course_id: props.courseId,
    group_id: props.groupId,
    course_attendance_id: props.attendanceId,
    course_member_id: props.attendanceDetail.course_member.id,
    status: props.attendanceDetail.status,
    comments: props.attendanceDetail.comments,
});

const handleClick = (ev) => {
    loadingIndex.value = ev.target.value;
    isLoading.value[loadingIndex.value] = true;

    form.status = parseInt(ev.target.value);
    storeAttendance();
}

const handleSaveComment = () => {
    loadingIndex.value = 4;
    isLoading.value[loadingIndex.value] = true;
    storeAttendance();

}

async function storeAttendance() {
    // await axios.patch('/attendances/details/'+props.member.id, form);
    let storeResultResp = await axios.post(`/attendances/${props.attendanceId}/details`, form);
    if (storeResultResp.data.success) {
        isLoading.value[loadingIndex.value] = false;
        loadingIndex.value = null;
    }
    // isLoading.value[loadingIndex.value] = false;
    // loadingIndex.value = null;
}


</script>

<template>
    <li class="bg-white shadow-lg p-2 rounded-lg flex flex-wrap justify-between items-center w-full mb-3">
        <div class="flex items-center min-w-fit">
            <img class="w-12 h-12 rounded-full" :src="attendanceDetail.course_member.user.avatar" :alt="attendanceDetail.course_member.user.name">
            <div class="ml-2">
                <p class="text-gray-900  tracking-wide text-sm">{{attendanceDetail.course_member.user.name}}</p>
                <div class="flex items-center mt-2">
                    <span class="text-slate-600">No.# {{ attendanceDetail.course_member.order_number }}</span>
                </div>
            </div>
        </div>

        <ul class="items-center w-full mt-2 overflow-hidden text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg sm:flex dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <li class="w-full py-2 border-b border-gray-200 sm:border-b-0 sm:border-r dark:border-gray-600"
                :class="{'bg-red-200': form.status === 0}"
            >
                <div class="flex items-center ps-3">
                    <input :id="`ingredient-absent-${attendanceDetail.id}`" type="radio" :value="0" v-model="form.status" @click="handleClick"
                        class="w-6 h-6  text-red-600 bg-gray-100 border-gray-300 focus:ring-red-500 dark:focus:ring-red-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label :for="`ingredient-absent-${attendanceDetail.id}`" 
                        class="ms-2 text-sm font-medium w-full text-gray-900 dark:text-gray-300">
                        <Icon v-if="isLoading[0]" icon="svg-spinners:8-dots-rotate" class="w-6 h-6 text-gray-600" />
                        <span v-else>ขาด</span>
                        </label>
                </div>
            </li>
            <li class="w-full py-2 border-b border-gray-200 sm:border-b-0 sm:border-r dark:border-gray-600"
                :class="{'bg-yellow-200': form.status === 1}"
            >
                <div class="flex items-center ps-3">
                    <input :id="`ingredient-leave-${attendanceDetail.id}`" type="radio" :value="1" v-model="form.status" @click="handleClick"
                        class="w-6 h-6  text-yellow-400 bg-gray-100 border-gray-300 focus:ring-yellow-500 dark:focus:ring-yellow-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label :for="`ingredient-leave-${attendanceDetail.id}`"
                        class="ms-2 text-sm font-medium w-full text-gray-900 dark:text-gray-300">
                        <Icon v-if="isLoading[1]" icon="svg-spinners:8-dots-rotate" class="w-6 h-6 text-gray-600" />
                        <span v-else>ลา</span>
                    </label>
                </div>
            </li>
            <li class="w-full py-2 border-b border-gray-200 sm:border-b-0 sm:border-r dark:border-gray-600"
                :class="{'bg-green-200': form.status === 2}"
            >
                <div class="flex items-center ps-3">
                    <input :id="`ingredient-present-${attendanceDetail.id}`" type="radio" :value="2" v-model="form.status" @click="handleClick"
                        class="w-6 h-6  text-green-600 bg-gray-100 border-gray-300 focus:ring-green-500 dark:focus:ring-green-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label :for="`ingredient-present-${attendanceDetail.id}`"
                        class="ms-2 text-sm font-medium w-full text-gray-900 dark:text-gray-300">
                        <Icon v-if="isLoading[2]" icon="svg-spinners:8-dots-rotate" class="w-6 h-6 text-gray-600" />
                        <span v-else>มา</span>
                    </label>
                </div>
            </li>
            <li class="w-full py-2 dark:border-gray-600"
                :class="{'bg-orange-200': form.status === 3}"
            >
                <div class="flex items-center ps-3">
                    <input :id="`ingredient-late-${attendanceDetail.id}`" type="radio" :value="3" v-model="form.status" @click="handleClick"
                        class="w-6 h-6  text-orange-500 bg-gray-100 border-gray-300 focus:ring-orange-500 dark:focus:ring-orange-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label :for="`ingredient-late-${attendanceDetail.id}`"
                        class="ms-2 text-sm font-medium w-full text-gray-900 dark:text-gray-300">
                        <Icon v-if="isLoading[3]" icon="svg-spinners:8-dots-rotate" class="w-6 h-6 text-gray-600" />
                        <span v-else>สาย</span>
                    </label>
                </div>
            </li>
        </ul>
        <div class="flex items-center space-x-2 w-full">
            <label :for="`attendance-comments-input-${props.dataIndex}`" class="text-sm font-medium leading-6 text-gray-900">หมายเหตุ: </label>
            <div class="flex w-full mt-2 rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-emerald-400 ">
            <input type="text" v-model="form.comments" name="username" :id="`attendance-comments-input-${props.dataIndex}`" autocomplete="comments" class="w-full border-0 bg-transparent py-1.5 pl-1 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6" placeholder="" />
            </div>
            <button v-if="form.comments" :disabled="isSaveCommentLoading" @click.prevent="handleSaveComment" class="flex items-center justify-center bg-indigo-500 rounded-md mt-2 w-8 h-8 p-1.5 hover:scale-110">
                <Icon v-if="isSaveCommentLoading" icon="svg-spinners:8-dots-rotate" class="w-8 h-8 text-white" />
                <Icon v-else icon="fluent:save-sync-20-regular" class="w-8 h-8 text-white " />
            </button>
        </div>
    </li>
</template>
