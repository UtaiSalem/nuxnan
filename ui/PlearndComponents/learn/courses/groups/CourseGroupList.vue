<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import axios from 'axios';

import CourseGroupItem from '@/PlearndComponents/learn/courses/groups/CourseGroupItem.vue';

const props = defineProps({
    course: Object,
    groups: Object,
    courseMemberOfAuth: Object
});

// Safe computed property for groups with validation
const computedGroup = computed(() => {
    const groupsData = usePage().props.groups?.data;
    
    // Validate and filter out invalid group entries
    if (!groupsData || !Array.isArray(groupsData)) {
        console.warn('Groups data is not an array:', groupsData);
        return [];
    }
    
    // Filter out any non-object entries (like boolean values)
    const validGroups = groupsData.filter((group, index) => {
        if (!group || typeof group !== 'object') {
            console.warn(`Invalid group at index ${index}:`, group);
            return false;
        }
        if (!group.id) {
            console.warn(`Group missing id at index ${index}:`, group);
            return false;
        }
        return true;
    });
    
    return validGroups;
});

const emit = defineEmits([
    'add-new-group',
    'delete-group',
    'update-group',
]);

// watch(()=>usePage().props.courseMemberOfAuth, () => {
//     router.reload({ only: ['groups']});
//     console.log('reload groups');
//     router.reload({ only: ['members']});
//     console.log('reload members');
// })

const tempCover = ref('/storage/images/courses/covers/default_cover.jpg');
const coverInput = ref(null);

const browseCover = () => { coverInput.value.click() };
function onCoverInputChange(event){
  form.value.cover = event.target.files[0];
  tempCover.value = URL.createObjectURL(event.target.files[0]);
}

const browseEditCover = () => { editCoverInput.value.click() };
function onEditCoverInputChange(event){
  if (event.target.files && event.target.files[0]) {
    editingGroup.value.image_url = event.target.files[0];
    tempEditCover.value = URL.createObjectURL(event.target.files[0]);
  }
}

const defaultForm = ref({
    name: '',
    description: '',
    cover: null
});

const form = ref({
    name: '',
    description: '',
    cover: null
});

const openCreateNewGroupModal = ref(false);

// Edit Group Modal State
const openEditGroupModal = ref(false);
const editingGroupIndex = ref(-1);
const editingGroup = ref({
    id: null,
    name: '',
    description: '',
    image_url: null
});
const tempEditCover = ref('/storage/images/courses/covers/default_cover.jpg');
const editCoverInput = ref(null);
const isEditingGroup = ref(false);

//Create new course's group
async function handleFormSubmit(){
    try {
            if (form.value.name.trim().length>0) {

            const config = { headers: { 'Content-Type': 'multipart/form-data' } };
            let newCourseGroup = new FormData();
            newCourseGroup.append('name', form.value.name);
            newCourseGroup.append('description', form.value.description);
            newCourseGroup.append('cover', form.value.cover ?? null);
            const cgResp = await axios.post(`/courses/${props.course.id}/groups`, newCourseGroup, config);

            if (cgResp.data && cgResp.data.success) {
                emit('add-new-group', cgResp.data.newGroup);
                openCreateNewGroupModal.value = false;
                Swal.fire('สำเร็จ','เพิ่มกลุ่มเรียบร้อยแล้ว.','success');
                form.value = defaultForm.value;
            }
        }
    } catch (error) {
        Swal.fire('ล้มเหลว','เกิดข้อผิดพลาด. <br />'+ error + "กรุณาลองใหม่อีกครั้ง",'error' );
    }
}

function onCancleCreateNewCourseGroup(){
    form.value = defaultForm.value;
    openCreateNewGroupModal.value = false;
}

function onDeleteCourseGroup(idx){
    Swal.fire({
            title: 'แน่ใจหรือไม่?',
            text: "ที่จะลบกลุ่มนี้อย่างถาวร!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: 'ยกเลิก',
            confirmButtonText: 'ใช่, ยืนยัน!'
        })
        .then(async (result) => {
            if (result.isConfirmed) {
                let delResp = await axios.delete(`/courses/${props.course.id}/groups/${props.groups[idx].id}`);
                if (delResp.data && delResp.data.success) {
                    // props.groups.splice(idx, 1);
                    emit('delete-group', idx);
                    Swal.fire('การลบสมบูรณ์!','ลบกลุ่มเรียบร้อยแล้ว.','success');                
                }
            }
            // console.log(`/courses/${props.course.id}/groups/${props.groups[idx].id}`);
        }
    );
}

function onEditCourseGroup(idx){
    // Validate index and group data
    if (!computedGroup.value || !computedGroup.value[idx] || typeof computedGroup.value[idx] !== 'object') {
        console.error('Invalid group data at index:', idx, computedGroup.value);
        Swal.fire({
            icon: 'error',
            title: 'ข้อผิดพลาด',
            text: 'ไม่พบข้อมูลกลุ่มที่ต้องการแก้ไข',
            confirmButtonText: 'ตกลง'
        });
        return;
    }
    
    const group = computedGroup.value[idx];
    
    // Additional validation
    if (!group.id) {
        console.error('Group missing id:', group);
        Swal.fire({
            icon: 'error',
            title: 'ข้อผิดพลาด',
            text: 'ข้อมูลกลุ่มไม่ถูกต้อง',
            confirmButtonText: 'ตกลง'
        });
        return;
    }
    
    editingGroupIndex.value = idx;
    editingGroup.value = {
        id: group.id,
        name: group.name || '',
        description: group.description || '',
        image_url: group.image_url || null
    };
    tempEditCover.value = group.image_url ? `/storage/images/courses/groups/${group.image_url}` : '/storage/images/courses/covers/default_cover.jpg';
    openEditGroupModal.value = true;
}

function onCancelEditGroup(){
    openEditGroupModal.value = false;
    editingGroupIndex.value = -1;
    editingGroup.value = {
        id: null,
        name: '',
        description: '',
        image_url: null
    };
    tempEditCover.value = '/storage/images/courses/covers/default_cover.jpg';
}

async function handleEditGroupSubmit(){
    if (!editingGroup.value.name || editingGroup.value.name.trim().length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'กรุณากรอกข้อมูล',
            text: 'กรุณากรอกชื่อกลุ่ม',
            confirmButtonText: 'ตกลง'
        });
        return;
    }

    try {
        isEditingGroup.value = true;
        
        const formData = new FormData();
        formData.append('name', editingGroup.value.name.trim());
        formData.append('description', editingGroup.value.description?.trim() || '');
        
        // Only append image if it's a file object
        if (editingGroup.value.image_url instanceof File) {
            formData.append('image_url', editingGroup.value.image_url);
        } else if (typeof editingGroup.value.image_url === 'string') {
            formData.append('existing_image_url', editingGroup.value.image_url);
        }
        
        const updateResp = await axios.post(`/courses/${props.course.id}/groups/${editingGroup.value.id}?_method=PATCH`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        
        if (updateResp.data && updateResp.data.success) {
            // Get the current group data before update
            const currentGroup = computedGroup.value[editingGroupIndex.value];
            
            // Create updated group object with all necessary fields
            const updatedGroup = {
                id: editingGroup.value.id,
                name: editingGroup.value.name.trim(),
                description: editingGroup.value.description?.trim() || '',
                image_url: updateResp.data.group?.image_url || editingGroup.value.image_url,
                // Preserve existing fields that weren't updated
                members: currentGroup?.members || [],
                created_at: currentGroup?.created_at || null,
                updated_at: new Date().toISOString(),
                // Merge any additional fields from the response
                ...(updateResp.data.group || {})
            };
            
            // Update groups data using Vue's reactivity system
            if (usePage().props.groups && usePage().props.groups.data) {
                // Direct assignment to trigger reactivity
                usePage().props.groups.data[editingGroupIndex.value] = updatedGroup;
            }
            
            // Emit the update event to parent
            emit('update-group', { index: editingGroupIndex.value, group: updatedGroup });
            
            Swal.fire({
                icon: 'success',
                title: 'เสร็จสิ้น',
                text: updateResp.data.message || 'แก้ไขกลุ่มเรียบร้อยแล้ว',
                confirmButtonText: 'ตกลง',
                timer: 2000
            });
            
            // Close the modal and reset state after a short delay to ensure state is updated
            setTimeout(() => {
                onCancelEditGroup();
                isEditingGroup.value = false;
            }, 100);
        } else {
            // Handle case where server returns success: false
            Swal.fire({
                icon: 'error',
                title: 'ข้อผิดพลาด',
                text: updateResp.data?.message || updateResp.data?.msg || 'ไม่สามารถแก้ไขกลุ่มได้',
                confirmButtonText: 'ตกลง'
            });
            isEditingGroup.value = false;
        }
        
    } catch (error) {
        console.error('Error updating group:', error);
        isEditingGroup.value = false;
        
        const errorMessage = error.response?.data?.message || error.response?.data?.msg || 'เกิดข้อผิดพลาดในการแก้ไขกลุ่ม';
        
        Swal.fire({
            icon: 'error',
            title: 'ข้อผิดพลาด',
            text: errorMessage,
            confirmButtonText: 'ตกลง'
        });
    }
}

async function requestTobeGroupMember(grpId, indx){

    try {   
        let memberResp = await axios.post(`/courses/${props.course.id}/groups/${grpId}/members`);
        if (memberResp.data.success) {

            usePage().props.courseMemberOfAuth=memberResp.data.courseMemberOfAuth;
            usePage().props.groups.data[indx].members = memberResp.data.group.members;
            memberResp.data.courseMemberOfAuth.course_member_status == 1 ? usePage().props.course.data.isMember=true : usePage().props.course.data.isMember=false;

            Swal.fire(
                'เสร็จสิ้น',
                'ขอเป็นสมาชิกเรียบร้อยแล้ว',
                'success'
                );
        }
    } catch (error) {
        // Handle error silently
    }
}

async function requestTobeUnMemberGroup(grpId, indx){
    
    try {
        let unmemberGroupResp = await axios.delete(`/courses/${props.course.id}/groups/${grpId}/members/${props.courseMemberOfAuth.id}`);
        if (unmemberGroupResp.data.success) {
           
            usePage().props.courseMemberOfAuth          = unmemberGroupResp.data.courseMember;
            usePage().props.course.data.status          = unmemberGroupResp.data.courseMember.status;
            usePage().props.course.data.member_status   = unmemberGroupResp.data.courseMember.member_status;
            usePage().props.groups.data[indx].members   = unmemberGroupResp.data.group.members;
            unmemberGroupResp.data.courseMember.course_member_status == 1 ? usePage().props.course.data.isMember=true : usePage().props.course.data.isMember=false;
            
            Swal.fire(
                'เสร็จสิ้น',
                'ออกจากสมาชิกกลุ่มเรียบร้อยแล้ว',
                'success'
            );

        }
    } catch (error) {
        // Handle error silently
    }

}

</script>

<template>
    <div class="flex flex-col space-y-6">
        <!-- Section header with gradient background -->
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-violet-600 via-purple-600 to-indigo-600 p-6 shadow-xl">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 h-16 w-16 rounded-full bg-violet-300 opacity-20 blur-xl"></div>
            <div class="absolute bottom-0 left-0 -mb-4 -ml-4 h-24 w-24 rounded-full bg-indigo-300 opacity-20 blur-xl"></div>
            
            <div class="relative z-10">
                <h2 class="text-2xl font-bold text-white mb-2">กลุ่มในรายวิชา</h2>
                <p class="text-violet-100">เข้าร่วมกลุ่มเพื่อเรียนรู้ร่วมกันและแลกเปลี่ยนความรู้</p>
            </div>
        </div>

        <!-- Groups grid with enhanced styling -->
        <div class="flex flex-wrap justify-between gap-6">
            <div v-for="(group, index) in computedGroup" :key="index"
                 class="w-full sm:w-[48%] transform transition-all duration-300 hover:scale-[1.02]">
                <CourseGroupItem class="shadow-lg hover:shadow-2xl transition-shadow duration-300 rounded-xl overflow-hidden"
                    :group="group"
                    :courseMemberOfAuth="props.courseMemberOfAuth"
                    :group_index="index"
                    
                    @delete-course-group="()=> onDeleteCourseGroup(index)"
                    @edit-course-group="()=> onEditCourseGroup(index)"
                    @request-tobe-group-member="()=> requestTobeGroupMember(group.id, index)"
                    @request-tobe-group-unmember="()=> requestTobeUnMemberGroup(group.id, index)"
                />
            </div>
        </div>

        <!-- Empty state when no groups -->
        <div v-if="computedGroup.length === 0" class="flex flex-col items-center justify-center py-12 px-4">
            <div class="w-24 h-24 bg-gradient-to-br from-violet-100 to-indigo-100 rounded-full flex items-center justify-center mb-4">
                <Icon icon="heroicons:user-group" class="w-12 h-12 text-violet-600" />
            </div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">ยังไม่มีกลุ่มในรายวิชานี้</h3>
            <p class="text-gray-500 text-center max-w-md">สร้างกลุ่มแรกเพื่อเริ่มการเรียนรู้ร่วมกันหรือรอให้ผู้สอนสร้างกลุ่ม</p>
        </div>

        <!-- Enhanced add group button -->
        <div v-if="$page.props.isCourseAdmin" class="relative overflow-hidden rounded-xl bg-gradient-to-r from-violet-50 to-indigo-50 p-6 shadow-lg border border-violet-100">
            <div class="absolute top-0 right-0 -mt-2 -mr-2 h-12 w-12 rounded-full bg-violet-200 opacity-30 blur-lg"></div>
            <div class="absolute bottom-0 left-0 -mb-2 -ml-2 h-16 w-16 rounded-full bg-indigo-200 opacity-30 blur-lg"></div>
            
            <div class="relative z-10 text-center">
                <button @click.prevent="openCreateNewGroupModal = true"
                        class="group relative inline-flex items-center justify-center px-8 py-3 overflow-hidden font-medium text-white transition duration-300 ease-out border-2 border-violet-600 rounded-full shadow-md hover:shadow-xl">
                    
                    <!-- Button background with gradient -->
                    <span class="absolute inset-0 flex items-center justify-center w-full h-full text-white duration-300 -translate-x-full bg-gradient-to-r from-violet-600 to-indigo-600 group-hover:translate-x-0 ease">
                        <Icon icon="heroicons:plus" class="w-5 h-5 mr-2" />
                        <span>สร้างกลุ่มใหม่</span>
                    </span>
                    
                    <!-- Button text that shows on hover -->
                    <span class="absolute flex items-center justify-center w-full h-full text-violet-600 transition-all duration-300 transform group-hover:translate-x-full ease">
                        <Icon icon="heroicons:sparkles" class="w-5 h-5 mr-2" />
                        <span>เพิ่มกลุ่มใหม่</span>
                    </span>
                    
                    <!-- Default state text -->
                    <span class="relative invisible">เพิ่มกลุ่มใหม่</span>
                </button>
                
                <p class="mt-3 text-sm text-gray-600">สร้างกลุ่มใหม่เพื่อจัดการนักเรียนและกิจกรรมการเรียนรู้</p>
            </div>
        </div>

        <!-- Edit Group Modal -->
        <div v-if="openEditGroupModal"
             class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm animate-fade-in"
             @click.self="onCancelEditGroup"
             aria-labelledby="edit-modal-header"
             aria-modal="true"
             tabindex="-1"
             role="dialog">
            <div class="flex max-h-[90vh] w-full max-w-lg mx-4 flex-col overflow-hidden rounded-2xl bg-white shadow-2xl animate-slide-up"
                 id="edit-modal"
                 role="document"
                 @click.stop>
                
                <!-- Modal header -->
                <header id="edit-modal-header"
                        class="flex items-center justify-between bg-gradient-to-r from-violet-600 to-indigo-600 px-6 py-4 shrink-0">
                    <div>
                        <h3 class="text-lg font-semibold text-white">แก้ไขกลุ่ม</h3>
                        <p class="text-sm text-violet-100 mt-0.5">อัพเดทข้อมูลกลุ่มเรียนรู้</p>
                    </div>
                    <button 
                        type="button"
                        @click.prevent="onCancelEditGroup" 
                        class="flex items-center justify-center h-8 w-8 rounded-full bg-white/20 hover:bg-white/30 text-white transition-colors duration-200" 
                        aria-label="ปิด">
                        <Icon icon="heroicons:x-mark" class="w-5 h-5" />
                    </button>
                </header>
                
                <!-- Modal body with scroll -->
                <div class="flex-1 overflow-y-auto">
                    <form id="edit-course-group-form" class="flex flex-col"
                        @submit.prevent="handleEditGroupSubmit">

                        <!-- Cover Image -->
                        <div class="relative w-full h-48 group">
                            <img :src="tempEditCover" class="w-full h-full object-cover" alt="Group Cover" />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <div class="absolute bottom-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <input type="file" class="hidden" accept="image/*" ref="editCoverInput" @change="onEditCoverInputChange" />
                                <button type="button" @click.prevent="browseEditCover"
                                    class="flex items-center gap-2 bg-white/90 hover:bg-white text-violet-600 font-medium px-4 py-2 rounded-lg shadow-lg transition-all duration-200 hover:shadow-xl">
                                    <Icon icon="heroicons:camera" class="w-5 h-5" />
                                    <span>เปลี่ยนรูปภาพ</span>
                                </button>
                            </div>
                        </div>

                        <!-- Form Fields -->
                        <div class="p-6 space-y-6">
                            <!-- Group Name -->
                            <div class="relative">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <Icon icon="heroicons:user-group" class="h-5 w-5 text-violet-400" />
                                    </div>
                                    <input 
                                        id="edit-group-name" 
                                        type="text" 
                                        v-model="editingGroup.name" 
                                        placeholder="ชื่อกลุ่ม"
                                        required
                                        class="w-full h-12 pl-12 pr-4 border-2 border-gray-200 rounded-lg text-gray-900 placeholder-transparent transition-all duration-200 focus:border-violet-500 focus:ring-2 focus:ring-violet-200 focus:outline-none peer" />
                                    <label for="edit-group-name"
                                        class="absolute left-12 -top-2.5 px-1 text-sm text-violet-600 bg-white transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-sm peer-focus:text-violet-600">
                                        ชื่อกลุ่ม <span class="text-red-500">*</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="relative">
                                <textarea 
                                    id="edit-group-description" 
                                    v-model="editingGroup.description" 
                                    rows="3"
                                    placeholder="คำอธิบาย"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg text-gray-900 placeholder-transparent transition-all duration-200 focus:border-violet-500 focus:ring-2 focus:ring-violet-200 focus:outline-none peer resize-none"></textarea>
                                <label for="edit-group-description"
                                    class="absolute left-4 -top-2.5 px-1 text-sm text-violet-600 bg-white transition-all peer-placeholder-shown:top-2.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-sm peer-focus:text-violet-600">
                                    คำอธิบาย (ถ้ามี)
                                </label>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal footer -->
                <div class="flex gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50 shrink-0">
                    <button  
                        type="button" 
                        @click.prevent="onCancelEditGroup"
                        :disabled="isEditingGroup" 
                        class="flex-1 inline-flex items-center justify-center h-10 px-5 text-sm font-medium tracking-wide text-red-600 transition duration-200 rounded-lg bg-white border border-red-300 hover:bg-red-50 hover:border-red-400 focus:ring-2 focus:ring-red-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        <Icon icon="heroicons:x-mark" class="w-5 h-5 mr-2" />
                        <span>ยกเลิก</span>
                    </button>
                    <button  
                        type="submit" 
                        @click.prevent="handleEditGroupSubmit"
                        :disabled="isEditingGroup"
                        class="flex-1 inline-flex items-center justify-center h-10 px-5 text-sm font-medium tracking-wide text-white transition duration-200 rounded-lg bg-gradient-to-r from-violet-500 to-indigo-600 hover:from-violet-600 hover:to-indigo-700 focus:ring-2 focus:ring-violet-300 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                        <Icon v-if="isEditingGroup" icon="eos-icons:loading" class="w-5 h-5 mr-2 animate-spin" />
                        <Icon v-else icon="heroicons:check" class="w-5 h-5 mr-2" />
                        <span>{{ isEditingGroup ? 'กำลังบันทึก...' : 'บันทึก' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Enhanced Create new group modal form  -->
        <div v-if="openCreateNewGroupModal"
             class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm animate-fade-in"
             @click.self="onCancleCreateNewCourseGroup"
             aria-labelledby="header-4a content-4a"
             aria-modal="true"
             tabindex="-1"
             role="dialog">
            
            <div class="flex max-h-[90vh] max-w-md w-full mx-4 flex-col gap-0 overflow-hidden rounded-2xl bg-white shadow-2xl shadow-slate-900/20 transform transition-all duration-300 scale-100 animate-slide-up"
                 id="modal"
                 role="document"
                 @click.stop>
                
                <!-- Modal header with gradient -->
                <header id="header-4a"
                        class="flex items-center justify-between bg-gradient-to-r from-violet-600 to-indigo-600 px-6 py-4 shrink-0">
                    <div>
                        <h3 class="text-lg font-semibold text-white">สร้างกลุ่มใหม่</h3>
                        <p class="text-violet-100 text-sm mt-0.5">เพิ่มกลุ่มเรียนรู้ใหม่ในรายวิชานี้</p>
                    </div>
                    <button 
                        type="button"
                        @click.prevent="onCancleCreateNewCourseGroup"
                        class="flex items-center justify-center h-8 w-8 rounded-full bg-white/20 hover:bg-white/30 text-white transition-colors duration-200"
                        aria-label="ปิด">
                        <Icon icon="heroicons:x-mark" class="w-5 h-5" />
                    </button>
                </header>
                
                <!-- Modal body -->
                <div id="content-4a" class="flex-1 overflow-y-auto p-6">
                    <form id="create-new-course-group-form"
                          class="flex flex-col space-y-6"
                          @submit.prevent="handleFormSubmit">

                        <!-- Enhanced cover image upload -->
                        <div class="relative group">
                            <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-violet-50 to-indigo-50 border-2 border-dashed border-violet-200 transition-all duration-300 hover:border-violet-400">
                                <img :src="tempCover"
                                     class="w-full h-48 object-cover transition-transform duration-300 group-hover:scale-105"
                                     alt="Group cover" />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                <div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <input type="file"
                                           class="hidden"
                                           accept="image/*"
                                           ref="coverInput"
                                           @change="onCoverInputChange" />
                                    <button type="button"
                                            @click.prevent="browseCover"
                                            class="flex items-center gap-2 bg-white/90 hover:bg-white text-violet-600 font-medium px-4 py-2 rounded-lg shadow-lg transition-all duration-200 hover:shadow-xl">
                                        <Icon icon="heroicons:camera" class="w-5 h-5" />
                                        <span>เปลี่ยนรูปภาพ</span>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 text-center">
                                <p class="text-sm text-gray-500">คลิกเพื่อเปลี่ยนรูปภาพปกกลุ่ม</p>
                            </div>
                        </div>

                        <!-- Enhanced name input -->
                        <div class="relative">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <Icon icon="heroicons:user-group" class="h-5 w-5 text-violet-400" />
                                </div>
                                <input id="id-l12"
                                       type="text"
                                       v-model="form.name"
                                       placeholder="ชื่อกลุ่ม"
                                       class="w-full h-12 pl-12 pr-4 border-2 border-gray-200 rounded-lg text-gray-900 placeholder-transparent transition-all duration-200 focus:border-violet-500 focus:ring-2 focus:ring-violet-200 focus:outline-none peer" />
                                <label for="id-l12"
                                       class="absolute left-12 -top-2.5 px-1 text-sm text-violet-600 bg-white transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-sm peer-focus:text-violet-600">
                                    ชื่อกลุ่ม <span class="text-red-500">*</span>
                                </label>
                            </div>
                        </div>

                        <!-- Enhanced description textarea -->
                        <div class="relative">
                            <div class="relative">
                                <textarea id="id-b02"
                                          type="text"
                                          v-model="form.description"
                                          rows="3"
                                          placeholder="คำอธิบายกลุ่ม"
                                          class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg text-gray-900 placeholder-transparent transition-all duration-200 focus:border-violet-500 focus:ring-2 focus:ring-violet-200 focus:outline-none peer resize-none"></textarea>
                                <label for="id-b02"
                                       class="absolute left-4 -top-2.5 px-1 text-sm text-violet-600 bg-white transition-all peer-placeholder-shown:top-2.5 peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-focus:-top-2.5 peer-focus:text-sm peer-focus:text-violet-600">
                                    คำอธิบาย (ถ้ามี)
                                </label>
                            </div>
                        </div>

                        <!-- Enhanced action buttons -->
                        <div class="flex gap-3 pt-4">
                            <button type="button"
                                    @click.prevent="onCancleCreateNewCourseGroup"
                                    class="flex-1 flex items-center justify-center gap-2 h-12 px-6 font-medium text-gray-700 bg-white border-2 border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-200 transition-all duration-200">
                                <Icon icon="heroicons:x-mark" class="w-5 h-5" />
                                <span>ยกเลิก</span>
                            </button>
                            <button type="submit"
                                    class="flex-1 flex items-center justify-center gap-2 h-12 px-6 font-medium text-white bg-gradient-to-r from-violet-600 to-indigo-600 rounded-lg hover:from-violet-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-violet-200 transition-all duration-200 shadow-lg hover:shadow-xl">
                                <Icon icon="heroicons:check" class="w-5 h-5" />
                                <span>สร้างกลุ่ม</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- End Modal with form -->
    </div>

</template>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slide-up {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

.animate-slide-up {
    animation: slide-up 0.3s ease-out;
}

/* Custom scrollbar for better visual appeal */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #8b5cf6, #6366f1);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #7c3aed, #4f46e5);
}
</style>
