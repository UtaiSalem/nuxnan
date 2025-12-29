<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from "@inertiajs/vue3";
import { Icon } from '@iconify/vue';

import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'

import StaggeredFade from '@/PlearndComponents/accessories/StaggeredFade.vue';
import MembersAttendanceDetails from './MembersAttendanceDetails.vue';
import Textarea from 'primevue/textarea';

const props = defineProps({
    groups: Object,
});

const activeGroupTab = ref(usePage().props.courseMemberOfAuth ? usePage().props.courseMemberOfAuth.last_accessed_group_tab : 0);
const isLoadingAttendances = ref(false);
const openCreateNewAttendanceForm = ref(false);
const groupAttendances = ref([]);
const attendanceResource = ref(null);

// const currentDate = new Date();
const startAt = new Date();

// finishAt = startAt + 45  minutes
const finishAt = new Date(startAt.getTime() + 45 * 60 * 1000);

const showAttendanceResource = computed(()=> {
    return !isLoadingAttendances.value;
});

const form = ref({
    description:'',
    start_at: startAt,
    finish_at: finishAt,
});

const activeGroupMembers = computed(()=> {
    return props.groups[activeGroupTab.value].members.sort((a,b) => a.order_number - b.order_number);
});

async function setActiveGroupTab(tab){
    activeGroupTab.value = tab;
    attendanceResource.value = null;
    getGroupAttendances(usePage().props.course.data.id, props.groups[activeGroupTab.value].id);

    if (usePage().props.courseMemberOfAuth && (tab < props.groups.length)) {
         let resp = await axios.post(`/courses/${usePage().props.course.data.id}/members/${usePage().props.courseMemberOfAuth.id}/set-active-group-tab`, {group_tab: activeGroupTab.value});
         if (resp.data.success) {
             usePage().props.courseMemberOfAuth.last_accessed_group_tab = tab;
         }
    }
}

async function handleAttendanceDateSelection(e){
    isLoadingAttendances.value = true;
    attendanceResource.value = null;
    let attendance_id = e.target.value;
    if(attendance_id == 0){
        return;
    }else{
        attendanceResource.value = null;
        let detailsResp = await axios.get(`/attendances/${attendance_id}/details`);
        if(detailsResp.data && detailsResp.data.success){
            // console.log(detailsResp.data.attendance_resource.details.sort((a,b) => a.course_member.order_number - b.course_member.order_number));
            attendanceResource.value = detailsResp.data.attendance_resource;
            isLoadingAttendances.value = false;
        }
    }
}

onMounted(async ()=>{
    // form.value.date = currentDate;
    getGroupAttendances(usePage().props.course.data.id, props.groups[activeGroupTab.value].id);
});

async function getGroupAttendances(courseId, groupId){
    try {
        isLoadingAttendances.value = true;
        groupAttendances.value = [];
        let resp = await axios.get(`/courses/${courseId}/groups/${groupId}/attendances`, {
            params: {
                isCourseAdmin: usePage().props.isCourseAdmin,
            }
        });
        
        if(resp.data && resp.data.success){
            // console.log(resp.data);
            groupAttendances.value = resp.data.attendances;
            // attendanceResource.value = resp.data.attendances.details
            isLoadingAttendances.value = false;
        }else{
            isLoadingAttendances.value = false;
        }
    } catch (error) {
        isLoadingAttendances.value = false;
    }
}

const handleCreateNewAttendanceFormSubmit = async () => {
    try {
        attendanceResource.value = null;
        isLoadingAttendances.value = true;
        let resp = await axios.post(`/courses/${usePage().props.course.data.id}/groups/${props.groups[activeGroupTab.value].id}/attendances`, form.value);
        if(resp.data && resp.data.success){
            groupAttendances.value.push(resp.data.attendance);

            isLoadingAttendances.value = false;
            openCreateNewAttendanceForm.value = false;
        }else{
                isLoadingAttendances.value = false;
        }
    } catch (error) {
        isLoadingAttendances.value = false;
        openCreateNewAttendanceForm.value = false;
    }
}

const handleCancleCreateNewAttendance = () => {
    form.value.description = '';
    openCreateNewAttendanceForm.value = false;
}


</script>

<template>
    <div class="w-full mx-auto ">
        <section class="max-w-full" aria-multiselectable="false">
            <ul v-if="$page.props.isCourseAdmin" class="flex flex-wrap items-center border-b border-slate-200 plearnd-card" role="tablist">
                <li v-for="(group, index) in groups" :key="index" class="w-1/2 md:w-1/3 lg:w-1/4" role="presentation ">
                    <button @click.prevent="setActiveGroupTab(index)"
                        class="inline-flex items-center justify-center w-full h-12 gap-2 px-6 mb-2 text-sm tracking-wide transition duration-300 border-b-2 rounded-t focus-visible:outline-none whitespace-nowrap hover:border-violet-600 focus:border-violet-700 text-violet-500 hover:text-violet-600 focus:text-violet-700 hover:bg-violet-50 focus:bg-violet-50 disabled:cursor-not-allowed disabled:border-slate-500 stroke-violet-500 hover:stroke-violet-600 focus:stroke-violet-700"
                        :class="activeGroupTab === index ? 'border-violet-500 bg-violet-200 font-bold':'font-medium'"
                        id="tab-label-1a" role="tab" aria-setsize="3" aria-posinset="1" tabindex="0"
                        aria-controls="tab-panel-1a" aria-selected="true">
                        <span>{{ group.name + ' (' + group.members.length + ')' }}</span>
                    </button>
                </li>
            </ul>

            <div class="plearnd-card mt-4 dark:bg-gray-800">
                <div v-if="openCreateNewAttendanceForm" class="flex flex-col space-y-4">
                    <form @submit.prevent="handleCreateNewAttendanceFormSubmit">
                        <div class="space-y-4">
                            <div class="border-b border-gray-900/10 pb-4">
                                <div class=" grid grid-cols-1 gap-4 sm:grid-cols-6">
                                    <div class="sm:col-span-3">
                                        <span :id="`attendance-start_date-input`"
                                            class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-200">เริ่มต้น</span>
                                        <div class="mt-2">
                                            <VueDatePicker id="attendanceDateInput" name="attendanceDateInput"
                                                :format="'dd/MM/yyyy HH:mm'"
                                                v-model="form.start_at"
                                                :model-value="form.start_at" placeholder="วันที่"
                                            />
                                        </div>
                                    </div>
                                    <div class="sm:col-span-3">
                                        <span :id="`attendance-finish_date-input`"
                                            class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-200">สิ้นสุด</span>
                                        <div class="mt-2">
                                            <VueDatePicker id="attendanceDateInput" name="attendanceDateInput"
                                                :format="'dd/MM/yyyy HH:mm'"
                                                v-model="form.finish_at"
                                                :model-value="form.finish_at" placeholder="วันที่"
                                            />
                                        </div>
                                    </div>
                                    <div class="sm:col-span-6">
                                        <label for="first-name"
                                            class="block text-sm font-medium leading-6 text-gray-900 dark:text-gray-200">คำอธิบาย</label>
                                        <div class="mt-2">
                                            <Textarea type="text" :name="form.description" id="first-name" required :autocomplete="form.description||'_'" v-model="form.description"
                                                class="block w-full rounded-md border-0 py-1.5 text-gray-900 dark:text-gray-200 dark:bg-gray-700 dark:border-gray-600 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:focus:ring-violet-500 sm:text-sm sm:leading-6" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="mt-2 flex items-center justify-end gap-x-4">
                            <button type="button" @click.prevent="handleCancleCreateNewAttendance" class="text-sm font-semibold leading-6 text-rose-600 rounded-md bg-rose-200 px-3 py-1.5">ยกเลิก</button>
                            <button type="submit" :disabled="isLoadingAttendances"
                                class="flex justify-center space-x-1 items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                                <Icon v-if="isLoadingAttendances" icon="svg-spinners:270-ring-with-bg" class="h-6 w-6 text-white" />
                                <span v-else> บันทึก </span>
                            </button>
                        </div>
                    </form>
                </div>
                <div v-if="!openCreateNewAttendanceForm" class="flex justify-center ">
                    <button @click.prevent="openCreateNewAttendanceForm = true" class="plearnd-btn-success w-40 flex items-center space-x-2">
                        <Icon icon="heroicons-solid:plus" class="h-6 w-6 text-white" />
                        <small>เพิ่มการเชคชื่อ</small>
                    </button>
                </div>
            </div>

            <div v-if="isLoadingAttendances" class="plearnd-card mt-4 dark:bg-gray-800">
                <Icon icon="svg-spinners:blocks-shuffle-3" class="h-6 w-6 mx-auto text-violet-600 dark:text-violet-400" />
            </div>

            <div v-if="showAttendanceResource" class="">
                <div class="pt-4" id="tab-panel-1a" aria-hidden="false" role="tabpanel" aria-labelledby="tab-label-1a"
                    tabindex="-1">
                    <staggered-fade :duration="50" tag="ul" class="flex flex-col w-full ">
                        <div class="bg-white dark:bg-gray-800 rounded-md p-2">
                            <div class="relative overflow-x-auto rounded-lg">
                                <table class="w-full divide-y divide-gray-200 overflow-x-auto">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-200 dark:bg-gray-700 dark:text-gray-400">
                                        <tr class="text-center">
                                            
                                            <th scope="col" class=" px-1 py-3 border border-slate-300 dark:border-gray-600">
                                                เลขที่
                                            </th>

                                            <th scope="col" class=" px-1 py-3 border border-slate-300 dark:border-gray-600">
                                                สมาชิก
                                            </th>

                                            <th scope="col" v-for="(attendance, index) in groupAttendances" :key="attendance.id"
                                                class="px-1 py-3 border border-slate-300 dark:border-gray-600">
                                                #{{ index + 1 }}
                                            </th>
                                        </tr>
                                    </thead>
                                    
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        <MembersAttendanceDetails 
                                            v-for="(member) in activeGroupMembers" :key="member.id"
                                            :member="member"
                                            :groupAttendances="groupAttendances"
                                            :isCourseAdmin="$page.props.isCourseAdmin"
                                        />
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </staggered-fade>
                </div>
            </div>

        </section>
    </div>
</template>
