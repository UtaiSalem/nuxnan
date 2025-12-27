<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { usePage } from "@inertiajs/vue3";
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

import '@vuepic/vue-datepicker/dist/main.css'

import StaggeredFade from '@/PlearndComponents/accessories/StaggeredFade.vue';
import MembersAttendanceDetails from './MembersAttendanceDetails.vue';
import CreateNewCourseAttendanceForm from './CreateNewCourseAttendanceForm.vue';
import ShowCourseAttendanceForm from './ShowCourseAttendanceForm.vue';
import { useAttendanceStore } from '@/stores/attendance';

const props = defineProps({
    groups: Object,
});

// Use attendance store
const attendanceStore = useAttendanceStore();

const activeGroupTab = ref(usePage().props.courseMemberOfAuth ? usePage().props.courseMemberOfAuth.last_accessed_group_tab : 0);
const openCreateNewAttendanceForm = ref(false);
const attendanceResource = ref(null);

const isShowCurrentAttendance = ref(false);
const currentAttendance = ref(null);
const currentAttendanceIndex = ref(null);
const isRefreshingAllStatuses = ref(false);

// Computed properties using store
const isLoadingAttendances = computed(() => {
    return attendanceStore.isLoading(`group_${props.groups[activeGroupTab.value]?.id}`);
});

const groupAttendances = computed(() => {
    return attendanceStore.getGroupAttendances(props.groups[activeGroupTab.value]?.id) || [];
});

const showAttendanceResource = computed(()=> {
    return !isLoadingAttendances.value;
});

const activeGroupMembers = computed(()=> {
    return props.groups[activeGroupTab.value]?.members?.sort((a,b) => a.order_number - b.order_number) || [];
});

const attendanceError = computed(() => {
    return attendanceStore.getError(`group_${props.groups[activeGroupTab.value]?.id}`);
});

async function setActiveGroupTab(tab){
    activeGroupTab.value = tab;
    attendanceResource.value = null;
    
    // Load attendances for the new group using store
    await loadGroupAttendances(tab);

    if (usePage().props.courseMemberOfAuth && (tab < props.groups.length)) {
         let resp = await axios.post(`/courses/${usePage().props.course.data.id}/members/${usePage().props.courseMemberOfAuth.id}/set-active-group-tab`, {group_tab: activeGroupTab.value});
         if (resp.data.success) {
             usePage().props.courseMemberOfAuth.last_accessed_group_tab = tab;
         }
    }
}

async function loadGroupAttendances(tabIndex) {
    const group = props.groups[tabIndex];
    if (!group) return;
    
    try {
        await attendanceStore.fetchGroupAttendances(usePage().props.course.data.id, group.id, usePage().props.isCourseAdmin);
    } catch (error) {
        console.error('Failed to load group attendances:', error);
        Swal.fire({
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถโหลดข้อมูลการเข้าเรียนได้ กรุณาลองใหม่',
            icon: 'error'
        });
    }
}

onMounted(async ()=>{
    await loadGroupAttendances(activeGroupTab.value);
});

// Watch for group changes and load data accordingly
watch(activeGroupTab, async (newTab) => {
    await loadGroupAttendances(newTab);
});

const handleAddNewAttendance = async (atdn) => {
    attendanceResource.value = null;
    // Store will handle adding the attendance to the correct group
    const groupId = props.groups[activeGroupTab.value].id;
    attendanceStore.addAttendance(groupId, atdn);
    handleCancleCreateNewAttendance();
}

const handleCancleCreateNewAttendance = () => {
    openCreateNewAttendanceForm.value = false;
}

const handleUpdateAttendance = async (atdn) => {
    // Store will handle updating the attendance
    await attendanceStore.updateAttendance(atdn.id, atdn);
    handleCloseAttendance();
}

const handleDeleteAttendance = async (aIndx) => {
    const attendance = groupAttendances.value[aIndx];
    if (!attendance) return;
    
    Swal.fire({
        title: 'คุณแน่ใจหรือไม่ที่จะลบการบันทึกนี้?',
        text: "คุณจะไม่สามารถเรียกดูการบันทึกนี้ได้อีกต่อไป!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ยืนยัน',
        showLoaderOnConfirm: true,
        preConfirm: async () => {
            try {
                await attendanceStore.deleteAttendance(attendance.id);
                return { success: true };
            } catch (error) {
                return { success: false, error: error.message };
            }
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then(async (result) => {
        if (result.isConfirmed) {
            if (result.value.success) {
                Swal.fire(
                    'ลบข้อมูลสำเร็จ!',
                    'บันทึกการมาเรียนถูกลบออกเรียบร้อยแล้ว',
                    'success'
                )
            } else {
                Swal.fire(
                    'เกิดข้อผิดพลาด!',
                    result.value.error || 'เกิดข้อผิดพลาดในการลบการบันทึกนี้ กรุณาลองใหม่อีกครั้ง',
                    'error'
                )
            }
            handleCloseAttendance();
            handleCancleCreateNewAttendance();
        }
    });
}

const handleShowAttendance = (aIndx) => {
    currentAttendance.value = groupAttendances.value[aIndx];
    // console.log(currentAttendance.value);
    currentAttendanceIndex.value = aIndx;
    isShowCurrentAttendance.value = true;
}

const handleCloseAttendance = () => {
    isShowCurrentAttendance.value = false;
    currentAttendance.value = null;
}

const preDeleteAttendance = () => {
    handleDeleteAttendance(currentAttendanceIndex.value);
    handleCloseAttendance();
    handleCancleCreateNewAttendance();
}

const refreshAllAttendanceStatuses = async () => {
    if (!usePage().props.isCourseAdmin) return;
    
    isRefreshingAllStatuses.value = true;
    
    try {
        // Get all attendances that are still active (not finished)
        const activeAttendances = groupAttendances.value.filter(attendance => {
            return new Date() < new Date(attendance.finish_at);
        });
        
        // For each active attendance, check all members' status
        for (const attendance of activeAttendances) {
            for (const member of activeGroupMembers.value) {
                try {
                    const status = await attendanceStore.fetchMemberJoinStatus(attendance.id, member.id);
                    if (status !== null && status !== undefined) {
                        attendanceStore.updateMemberStatusInAttendance(attendance.id, member.id, status);
                    }
                } catch (error) {
                    console.error(`Failed to refresh status for member ${member.id} in attendance ${attendance.id}:`, error);
                }
            }
        }
        
        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'อัปเดตสถานะสำเร็จ',
            text: 'สถานะการเข้าเรียนทั้งหมดได้รับการอัปเดตเรียบร้อยแล้ว',
            timer: 2000,
            showConfirmButton: false
        });
        
    } catch (error) {
        console.error('Failed to refresh attendance statuses:', error);
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถอัปเดตสถานะการเข้าเรียนได้ กรุณาลองใหม่',
        });
    } finally {
        isRefreshingAllStatuses.value = false;
    }
};

</script>

<template>
    <div class="w-full mx-auto">
        <!-- Warning message when there are no groups -->
        <div v-if="!props.groups || props.groups.length === 0" class="bg-gradient-to-r from-amber-50 via-orange-50 to-yellow-50 border-l-4 border-amber-500 rounded-xl p-8 mb-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-14 h-14 bg-amber-100 rounded-full flex items-center justify-center animate-pulse">
                        <Icon icon="heroicons:exclamation-triangle" class="h-8 w-8 text-amber-600" />
                    </div>
                    <div>
                        <p class="text-xl font-bold text-amber-800">ยังไม่มีกลุ่มในรายวิชานี้</p>
                        <p class="text-amber-600 mt-1">กรุณาสร้างกลุ่มก่อนเพื่อจัดการข้อมูลการเข้าเรียน</p>
                    </div>
                </div>
                <a :href="`/courses/${$page.props.course.data.id}/groups`"
                   class="px-6 py-3 bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-xl hover:from-amber-700 hover:to-orange-700 transition-all duration-300 text-sm font-bold flex items-center gap-2 shadow-md hover:shadow-lg transform hover:scale-105">
                    <Icon icon="heroicons:user-group" class="h-5 w-5" />
                    ไปที่เมนูกลุ่ม
                </a>
            </div>
        </div>

        <section v-else class="max-w-full" aria-multiselectable="false">
            <!-- Enhanced Group Tabs -->
            <div v-if="$page.props.isCourseAdmin" class="bg-gradient-to-br from-white via-gray-50 to-white rounded-2xl shadow-xl overflow-hidden p-4 border border-gray-100">
                <div class="flex flex-wrap items-center gap-2">
                    <div v-for="(group, index) in $page.props.groups.data" :key="index" class="flex-1 min-w-[200px]">
                        <button @click.prevent="setActiveGroupTab(index)"
                            class="w-full h-14 px-4 rounded-xl text-sm font-bold tracking-wide transition-all duration-300 border-2 focus-visible:outline-none whitespace-nowrap transform hover:scale-105 hover:shadow-lg relative overflow-hidden group"
                            :class="[
                                activeGroupTab === index
                                    ? 'bg-gradient-to-r from-violet-600 to-purple-600 text-white border-violet-600 shadow-lg'
                                    : 'bg-white text-violet-600 border-violet-200 hover:border-violet-400 hover:bg-violet-50',
                                'disabled:cursor-not-allowed disabled:border-slate-500'
                            ]"
                            id="tab-label-1a" role="tab" aria-setsize="3" aria-posinset="1" tabindex="0"
                            aria-controls="tab-panel-1a" aria-selected="true">
                            <!-- Add animated background effect for active tab -->
                            <div v-if="activeGroupTab === index" class="absolute inset-0 bg-gradient-to-r from-white to-transparent opacity-20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-all duration-700"></div>
                            
                            <div class="relative flex items-center justify-center gap-2">
                                <Icon icon="heroicons:user-group" class="w-5 h-5" />
                                <span>{{ group.name }}</span>
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold"
                                      :class="activeGroupTab === index ? 'bg-white text-violet-600' : 'bg-violet-100 text-violet-600'">
                                    {{ group.members.length }}
                                </span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Enhanced Loading State -->
            <div v-if="isLoadingAttendances" class="mt-6 p-12 text-center bg-gradient-to-br from-violet-50 via-purple-50 to-indigo-50 rounded-2xl shadow-xl border border-violet-100">
                <div class="relative">
                    <div class="w-20 h-20 border-4 border-violet-200 border-t-violet-600 rounded-full animate-spin mx-auto"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <Icon icon="heroicons:academic-cap" class="h-10 w-10 text-violet-600 animate-pulse" />
                    </div>
                    <!-- Add rotating ring effect -->
                    <div class="absolute inset-0 rounded-full border-2 border-violet-300 animate-ping opacity-20"></div>
                </div>
                <p class="mt-8 text-xl font-bold text-gray-700 animate-pulse">กำลังโหลดข้อมูลการเข้าเรียน...</p>
                <p class="mt-2 text-sm text-gray-500">กรุณารอสักครู่</p>
            </div>
            
            <!-- Enhanced Error State -->
            <div v-if="attendanceError" class="mt-6 p-8 bg-gradient-to-br from-red-50 via-pink-50 to-rose-50 border-2 border-red-200 rounded-2xl shadow-xl">
                <div class="flex items-start mb-6">
                    <div class="flex-shrink-0 w-14 h-14 bg-red-100 rounded-full flex items-center justify-center animate-pulse">
                        <Icon icon="heroicons:exclamation-triangle" class="h-8 w-8 text-red-600" />
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-xl font-bold text-red-800 mb-2">เกิดข้อผิดพลาด</h3>
                        <p class="text-red-700 font-medium">{{ attendanceError }}</p>
                    </div>
                </div>
                <button
                    @click="loadGroupAttendances(activeGroupTab)"
                    class="flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-red-600 to-pink-600 text-white rounded-xl hover:from-red-700 hover:to-pink-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 font-bold"
                >
                    <Icon icon="heroicons:arrow-path" class="h-5 w-5" />
                    <span>ลองใหม่อีกครั้ง</span>
                </button>
            </div>

            <!-- Enhanced Attendance Resource Display -->
            <div v-if="showAttendanceResource" class="mt-6">
                <!-- Enhanced Refresh button for course admins -->
                <div v-if="$page.props.isCourseAdmin" class="mb-6 flex justify-end">
                    <button
                        @click="refreshAllAttendanceStatuses"
                        :disabled="isRefreshingAllStatuses"
                        class="flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white rounded-xl hover:from-blue-700 hover:via-indigo-700 hover:to-purple-700 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl transform hover:scale-105 font-bold"
                    >
                        <Icon
                            :icon="isRefreshingAllStatuses ? 'eos-icons:loading' : 'heroicons:arrow-path'"
                            class="h-5 w-5"
                            :class="{ 'animate-spin': isRefreshingAllStatuses }"
                        />
                        <span>
                            {{ isRefreshingAllStatuses ? 'กำลังอัปเดตสถานะ...' : 'โหลดใหม่' }}
                        </span>
                    </button>
                </div>
                
                <div id="tab-panel-1a" aria-hidden="false" role="tabpanel" aria-labelledby="tab-label-1a" tabindex="-1">
                    <staggered-fade :duration="50" tag="ul" class="flex flex-col w-full">
                        <div class="bg-gradient-to-br from-white via-gray-50 to-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                            <div class="relative overflow-x-auto">
                                <table class="w-full">
                                    <!-- Enhanced Table Header -->
                                    <thead class="bg-gradient-to-r from-gray-50 via-gray-100 to-slate-100 border-b-2 border-gray-200">
                                        <tr class="text-center">
                                            <th scope="col" class="px-6 py-4 border border-slate-300 font-black text-gray-800 bg-white min-w-[280px]">
                                                <div class="flex items-center justify-center gap-3">
                                                    <div class="w-10 h-10 bg-gradient-to-br from-violet-100 to-purple-100 rounded-xl flex items-center justify-center shadow-sm">
                                                        <Icon icon="heroicons:user-group" class="w-5 h-5 text-violet-600" />
                                                    </div>
                                                    <span class="uppercase tracking-wide">สมาชิก</span>
                                                </div>
                                            </th>

                                            <th scope="col" v-for="(attendance, index) in groupAttendances" :key="attendance.id"
                                                class="px-3 py-4 border border-slate-300 min-w-[80px]">
                                                <button
                                                    @click.prevent="handleShowAttendance(index)"
                                                    class="flex justify-center items-center mx-auto text-sm font-bold text-green-600 hover:text-green-700 hover:bg-green-50 rounded-xl px-3 py-2 transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md"
                                                >
                                                    <span class="mr-2">#{{ index + 1 }}</span>
                                                    <Icon icon="hugeicons:view" width="20" height="20" />
                                                </button>
                                            </th>

                                            <th v-if="!openCreateNewAttendanceForm" scope="col" class="px-3 py-4 border bg-white sticky right-0 z-10">
                                                <button
                                                    @click.prevent="openCreateNewAttendanceForm = true"
                                                    class="flex justify-center items-center mx-auto text-green-600 hover:text-green-700 hover:bg-green-50 rounded-xl p-3 transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md"
                                                    title="เพิ่มการเช็คชื่อใหม่"
                                                >
                                                    <Icon icon="icon-park-outline:add-one" width="24" height="24" />
                                                </button>
                                            </th>
                                        </tr>
                                    </thead>
                                    
                                    <!-- Enhanced Table Body -->
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        <MembersAttendanceDetails
                                            v-for="(member) in activeGroupMembers" :key="member.id"
                                            :member="member"
                                            :groupAttendances="groupAttendances"
                                        />
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </staggered-fade>
                </div>
            </div>

            <!-- Enhanced Form Sections -->
            <section v-if="openCreateNewAttendanceForm" class="mt-6">
                <div class="bg-gradient-to-br from-white via-gray-50 to-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                    <CreateNewCourseAttendanceForm
                        :groupId="props.groups[activeGroupTab].id"
                        @close-form="openCreateNewAttendanceForm = false"
                        @cancle-create-new-attendance="handleCancleCreateNewAttendance"
                        @add-new-attendance="(atdn)=> handleAddNewAttendance(atdn)"
                    />
                </div>
            </section>

            <section v-if="isShowCurrentAttendance" class="mt-6">
                <div class="bg-gradient-to-br from-white via-gray-50 to-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                    <ShowCourseAttendanceForm
                        :groupId="props.groups[activeGroupTab].id"
                        :attendance="currentAttendance"
                        @close-form="isShowCurrentAttendance = false"
                        @delete-attendance="preDeleteAttendance"
                        @updat-attendance="(respAtd)=>handleUpdateAttendance(respAtd)"
                    />
                </div>
            </section>
        </section>
    </div>
</template>
