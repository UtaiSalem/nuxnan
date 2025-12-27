<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3';
import DotsDropdownMenu from '@/PlearndComponents/accessories/DotsDropdownMenu.vue';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
    group: {
        type: Object,
        required: true
    },
    group_index: {
        type: Number,
        required: true
    },
    courseMemberOfAuth: {
        type: Object,
        default: null
    },
});

const emit = defineEmits([
    'edit-course-group',
    'delete-course-group',
    'request-tobe-group-member',
    'request-tobe-group-unmember'
]);

const isLoading = ref(false);
const showGroupOptions = ref(false);

const authIsGroupMember = computed(() => {
    return props.courseMemberOfAuth ? props.courseMemberOfAuth.group_member_status===1:false;
})


async function handleGroupMemberRequest(){
    try {
        // Validate props before proceeding
        if (!props.group || typeof props.group !== 'object' || !props.group.id) {
            Swal.fire({
                icon: 'error',
                title: 'ข้อผิดพลาด',
                text: 'ข้อมูลกลุ่มไม่ถูกต้อง',
                confirmButtonText: 'ตกลง'
            });
            return;
        }

        if (usePage().props.auth.user.pp < usePage().props.course.data.tuition_fees) {
            Swal.fire({
                icon: 'warning',
                title: 'ขออภัย',
                text: 'คุณมีแต้มสะสมไม่เพียงพอที่จะสมัครเข้าร่วมกลุ่มรายวิชานี้ กรุณาเติมแต้มสะสมก่อน',
                confirmButtonText: 'ตกลง'
            });
            return;
        }

        isLoading.value = true;

        const memberResp = await axios.post(`/courses/${usePage().props.course.data.id}/groups/${props.group.id}/members`);

        if (memberResp.data.success) {
            // อัพเดทข้อมูลสมาชิก
            usePage().props.courseMemberOfAuth = memberResp.data.courseMemberOfAuth;
            
            // อัพเดทข้อมูลกลุ่ม - safely check if groups data exists
            if (memberResp.data.group && usePage().props.groups.data && usePage().props.groups.data[props.group_index]) {
                usePage().props.groups.data[props.group_index].members = memberResp.data.group.members;
                if (props.group) {
                    props.group.members = memberResp.data.group.members;
                }
            }
            
            // อัพเดทจำนวนผู้เรียนในคอร์ส
            if (memberResp.data.course && memberResp.data.course.enrolled_students !== undefined) {
                usePage().props.course.data.enrolled_students = memberResp.data.course.enrolled_students;
            }
            
            // อัพเดทสถานะสมาชิก
            const isActiveMember = memberResp.data.courseMemberOfAuth.course_member_status == 1;
            usePage().props.course.data.isMember = isActiveMember;
            
            Swal.fire({
                icon: 'success',
                title: 'เสร็จสิ้น',
                text: memberResp.data.message || 'ขอเป็นสมาชิกเรียบร้อยแล้ว',
                confirmButtonText: 'ตกลง',
                timer: 2000
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'ขออภัย',
                text: memberResp.data.msg || 'เกิดข้อผิดพลาดในการขอเข้าร่วมกลุ่ม',
                confirmButtonText: 'ตกลง'
            });
        }

        isLoading.value = false;
    } catch (error) {
        console.error('Error joining group:', error);
        
        const errorMessage = error.response?.data?.msg || error.response?.data?.message || 'เกิดข้อผิดพลาดในการขอเข้าร่วมกลุ่ม';
        
        Swal.fire({
            icon: 'error',
            title: 'ข้อผิดพลาด',
            text: errorMessage,
            confirmButtonText: 'ตกลง'
        });
        
        isLoading.value = false;
    }
}


async function handleUnmemberGroupRequest(){
    try {
        // Validate props before proceeding
        if (!props.group || typeof props.group !== 'object' || !props.group.id) {
            Swal.fire({
                icon: 'error',
                title: 'ข้อผิดพลาด',
                text: 'ข้อมูลกลุ่มไม่ถูกต้อง',
                confirmButtonText: 'ตกลง'
            });
            return;
        }

        if (!props.courseMemberOfAuth || !props.courseMemberOfAuth.id) {
            Swal.fire({
                icon: 'error',
                title: 'ข้อผิดพลาด',
                text: 'ข้อมูลสมาชิกไม่ถูกต้อง',
                confirmButtonText: 'ตกลง'
            });
            return;
        }

        // ยืนยันการออกจากกลุ่ม
        const result = await Swal.fire({
            icon: 'warning',
            title: 'ยืนยันการออกจากกลุ่ม',
            text: 'คุณต้องการออกจากกลุ่มนี้ใช่หรือไม่?',
            showCancelButton: true,
            confirmButtonText: 'ใช่, ออกจากกลุ่ม',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280'
        });

        if (!result.isConfirmed) {
            return;
        }

        isLoading.value = true;

        const unmemberGroupResp = await axios.delete(`/courses/${usePage().props.course.data.id}/groups/${props.group.id}/members/${props.courseMemberOfAuth.id}`);
        
        if (unmemberGroupResp.data.success) {
            // อัพเดทข้อมูลสมาชิก
            usePage().props.courseMemberOfAuth = unmemberGroupResp.data.courseMember;
            usePage().props.course.data.status = unmemberGroupResp.data.courseMember.status;
            usePage().props.course.data.member_status = unmemberGroupResp.data.courseMember.member_status;
            
            // อัพเดทข้อมูลกลุ่ม - safely check if groups data exists
            if (unmemberGroupResp.data.group && usePage().props.groups.data && usePage().props.groups.data[props.group_index]) {
                usePage().props.groups.data[props.group_index].members = unmemberGroupResp.data.group.members;
            }
            
            // อัพเดทจำนวนผู้เรียนในคอร์ส
            if (unmemberGroupResp.data.course && unmemberGroupResp.data.course.enrolled_students !== undefined) {
                usePage().props.course.data.enrolled_students = unmemberGroupResp.data.course.enrolled_students;
            }

            // อัพเดทสถานะสมาชิก
            const isActiveMember = unmemberGroupResp.data.courseMember.course_member_status == 1;
            usePage().props.course.data.isMember = isActiveMember;

            Swal.fire({
                icon: 'success',
                title: 'เสร็จสิ้น',
                text: unmemberGroupResp.data.message || 'ออกจากสมาชิกกลุ่มเรียบร้อยแล้ว',
                confirmButtonText: 'ตกลง',
                timer: 2000
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'ขออภัย',
                text: unmemberGroupResp.data.msg || 'เกิดข้อผิดพลาดในการออกจากกลุ่ม',
                confirmButtonText: 'ตกลง'
            });
        }

        showGroupOptions.value = false;
        isLoading.value = false;
    } catch (error) {
        console.error('Error leaving group:', error);
        
        const errorMessage = error.response?.data?.msg || error.response?.data?.message || 'เกิดข้อผิดพลาดในการออกจากกลุ่ม';
        
        Swal.fire({
            icon: 'error',
            title: 'ข้อผิดพลาด',
            text: errorMessage,
            confirmButtonText: 'ตกลง'
        });
        
        showGroupOptions.value = false;
        isLoading.value = false;
    }
}

</script>

<template>
    <div
        class="sm:max-w-sm md:max-w-sm lg:max-w-sm xl:max-w-sm sm:mx-auto md:mx-auto lg:mx-auto xl:mx-auto bg-white shadow-lg hover:shadow-2xl rounded-xl text-gray-900 overflow-hidden transition-all duration-300 transform hover:-translate-y-1 border border-gray-100">

        <div class="relative rounded-t-xl h-40 overflow-hidden" v-if="props.group && typeof props.group === 'object'">
            <img class="object-cover object-center w-full h-40 transition-transform duration-700 hover:scale-110"
                :src="props.group.image_url ? '/storage/images/courses/groups/'+props.group.image_url :'/storage/images/courses/covers/default_cover.jpg'"
                alt='Group Cover'>
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
            <div class="absolute top-3 right-3 text-end" v-if="$page.props.isCourseAdmin">
                <DotsDropdownMenu
                    @edit-model="emit('edit-course-group')"
                    @delete-model="emit('delete-course-group')"
                >
                    <template #editModel><div>แก้ไขกลุ่ม</div></template>
                    <template #deleteModel><div>ลบกลุ่ม</div></template>
                </DotsDropdownMenu>
            </div>
            <!-- Badge overlay สำหรับแสดงสถานะพิเศษ (ถ้ามี) -->
            <div class="absolute bottom-3 left-3">
                <div class="flex items-center gap-2 bg-white/90 backdrop-blur-sm px-3 py-1.5 rounded-full shadow-lg">
                    <Icon icon="heroicons:sparkles" class="w-4 h-4 text-violet-600" />
                    <span class="text-xs font-semibold text-gray-800">กลุ่มเรียนรู้</span>
                </div>
            </div>
        </div>

        <div class="text-center px-4 pt-5 pb-3" v-if="props.group && typeof props.group === 'object'">
            <h2 class="text-xl font-bold text-gray-800 mb-2 hover:text-violet-600 transition-colors duration-300 cursor-default">
                {{ props.group.name || 'ไม่มีชื่อกลุ่ม' }}
            </h2>
            <p class="text-sm text-gray-600 line-clamp-2 min-h-[40px]">
                {{ props.group.description || 'ไม่มีคำอธิบาย' }}
            </p>
        </div>
        
        <!-- Error state when group is not a valid object -->
        <div class="text-center px-4 pt-5 pb-3" v-else>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-red-800 mb-2">ข้อมูลกลุ่มไม่ถูกต้อง</h2>
                <p class="text-sm text-red-600">ไม่สามารถแสดงข้อมูลกลุ่มได้เนื่องจากข้อมูลไม่ถูกต้อง</p>
            </div>
        </div>

        <ul class="py-3 px-4 flex items-center justify-center" v-if="props.group && typeof props.group === 'object'">
            <li class="flex items-center gap-3 px-6 py-3 rounded-xl bg-gradient-to-br from-indigo-50 via-purple-50 to-blue-50 shadow-sm hover:shadow-md transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-indigo-500 via-purple-500 to-blue-500 rounded-full shadow-lg animate-pulse-slow">
                    <Icon icon="heroicons:user-group" class="w-6 h-6 text-white" />
                </div>
                <div class="flex flex-col">
                    <span class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                        {{ (props.group.members && Array.isArray(props.group.members)) ? props.group.members.length : 0 }}
                    </span>
                    <span class="text-xs font-semibold text-gray-600">สมาชิก</span>
                </div>
            </li>
        </ul>

        <div v-if="!$page.props.isCourseAdmin && props.group && typeof props.group === 'object'" class="p-4 border-t border-gray-100">
            <div v-if="authIsGroupMember && props.courseMemberOfAuth.group_id === props.group.id" class="group relative cursor-pointer w-full">
                <button @click.prevent="showGroupOptions = !showGroupOptions" :disabled="isLoading"
                    class="w-full flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 shadow-md hover:shadow-lg font-medium text-white px-5 py-2.5 transition-all duration-300">
                    <Icon icon="uiw:loading" v-if="isLoading" class="animate-spin h-5 w-5"/>
                    <Icon v-else icon="heroicons:check-badge" class="h-5 w-5" />
                    <span>เป็นสมาชิกแล้ว</span>
                    <Icon icon="heroicons:chevron-down" class="h-5 w-5 text-white transition-transform duration-300" :class="{'rotate-180':showGroupOptions}" />
                </button>
                <div v-if="showGroupOptions"
                    class="absolute z-[50] flex w-full flex-col mt-2 shadow-xl rounded-lg overflow-hidden border border-red-200">
                    <button @click.prevent="handleUnmemberGroupRequest" :disabled="isLoading"
                        class="py-3 bg-gradient-to-r from-red-50 to-pink-50 hover:from-red-100 hover:to-pink-100 font-semibold text-red-600 hover:text-red-700 transition-all duration-200 flex items-center justify-center gap-2">
                        <Icon icon="heroicons:arrow-right-on-rectangle" class="h-5 w-5" />
                        <span>ออกจากกลุ่ม</span>
                    </button>                       
                </div>
            </div>
            <button v-else-if="props.courseMemberOfAuth && props.courseMemberOfAuth.group_member_status===0 && props.courseMemberOfAuth.group_id === props.group.id" :disabled="isLoading"
                class="w-full flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-amber-400 via-yellow-500 to-orange-400 shadow-md font-medium text-white px-5 py-2.5 cursor-not-allowed relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent animate-shimmer"></div>
                <Icon icon="heroicons:clock" class="h-5 w-5 animate-pulse" />
                <span>รอการตอบรับ</span>
            </button>
            <div v-else>
                <button
                    :disabled="isLoading"
                    @click.prevent="handleGroupMemberRequest"
                    class="w-full flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-blue-500 via-indigo-600 to-purple-600 hover:from-blue-600 hover:via-indigo-700 hover:to-purple-700 shadow-md hover:shadow-xl font-semibold text-white px-5 py-2.5 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-105 active:scale-95 relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                    <Icon icon="uiw:loading" v-if="isLoading" class="animate-spin h-5 w-5 relative z-10"/>
                    <Icon v-else icon="heroicons:user-plus" class="h-5 w-5 relative z-10" />
                    <span class="relative z-10">ขอเข้าร่วมกลุ่ม</span>
                </button>
            </div>
        </div>
    </div>
</template>
