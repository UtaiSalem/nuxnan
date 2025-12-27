<script setup>
import { ref, reactive, computed, inject } from 'vue';
import { Icon } from '@iconify/vue';
import { usePage } from '@inertiajs/vue3';

import AssignmentImagesViewer from '@/PlearndComponents/learn/courses/assignments/AssignmentImagesViewer.vue'
import DotsDropdownMenu from '@/PlearndComponents/accessories/DotsDropdownMenu.vue';
import AssignmentAnswerViewer from '@/PlearndComponents/learn/courses/assignments/answers/AssignmentAnswerViewer.vue';
import AssignmentAnswerForm from '@/PlearndComponents/learn/courses/assignments/AssignmentAnswerForm.vue';
import Swal from 'sweetalert2';

import Textarea from "primevue/textarea";
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
    asmIndex: Number,
    assignment: Object,
    assignmentableType: String,
    assignmentableId: Number,
    assignmentNameTh: String,
    assignmentApiRoute: String,
});

const emit = defineEmits([
    'update-assignment',
]);

const { setLoadingPage } = inject('isLoadingPage');

const increasePPWhenCorrect = ref(props.assignment.increase_points !== null);
// const increasePoints = ref(props.assignment.increase_points);

const decreasePPWhenWrong = ref(props.assignment.decrease_points !== null);
// const decreasePoints = ref(props.assignment.decrease_points);


const hasDueDate = ref(props.assignment.due_date !== null);
const asmDueDate = ref(props.assignment.due_date);

const hasStartDate = ref(props.assignment.start_date !== null);
const asmStartDate = ref(props.assignment.start_date);

const hasEndDate = ref(props.assignment.end_date !== null);
const asmEndDate = ref(props.assignment.end_date);

const showEditAsmForm = ref(false);
const addAsmImagesInput = ref(null);
const tempImagesFile = reactive([]);
const tempImagesUrl = reactive([]);

const isGroupAssignment = ref(true);
const selectedGroups = ref(props.assignment.target_groups ?? []);
// const selectedGroups = ref(props.assignment.target_groups ? props.assignment.target_groups.map(id => parseInt(id)) :[]);

const courseGroups = ref(usePage().props.groups);
const isSelected = (groupId) => {
    if (!selectedGroups.value) return false;
    return selectedGroups.value.includes(groupId);
};

const allCourseGroupSelected = computed(() => {
    if (!selectedGroups.value) return false;
    return selectedGroups.value.length === courseGroups.value.length ? true : false;
});

const toggleGroup = (groupId) => {
    if (selectedGroups.value.includes(groupId)) {
        selectedGroups.value = selectedGroups.value.filter(id => id !== groupId);
        form.value.target_groups = selectedGroups.value;
    } else {
        selectedGroups.value.push(groupId);
        form.value.target_groups = selectedGroups.value;
    }
};

const toggleAllGroups = () => {
    if (allCourseGroupSelected.value) {
        selectedGroups.value = [];
        form.value.target_groups = selectedGroups.value;
    } else {
        selectedGroups.value = courseGroups.value.map(group => group.id);
        form.value.target_groups = selectedGroups.value;
    }
};



const form = ref({
    title: props.assignment.title,
    points: props.assignment.points,
    increase_points: props.assignment.increase_points,
    decrease_points: props.assignment.decrease_points,
    due_date: asmDueDate.value ?? null,
    start_date: asmStartDate.value ?? null,
    end_date: asmEndDate.value ?? null,
    is_group_assignment: props.assignment.is_group_assignment,
    target_groups: selectedGroups,
    status: props.assignment.status === 1 ? true : false,
});

const browseAddAsmImages = () => {
    document.getElementById('addAsmImagesInput').click();
    // inputAsmImages.value.click();
};

function onAddAsmImageInputChange(e){
    Array.from(e.target.files).forEach(image => {
        tempImagesFile.push(image);
        tempImagesUrl.push(URL.createObjectURL(image));
    });
}

function onAddNewAnswerHandler(newAnswer) {
    props.assignment.answers.splice(0);
    props.assignment.answers.push(newAnswer);
}

function onDeleteTempImagesHandler(index) {
    tempImagesFile.splice(index, 1);
    tempImagesUrl.splice(index, 1);
}

function onCancleEditAsm() {
    tempImagesFile.splice(0);
    tempImagesUrl.splice(0);
    addAsmImagesInput.value = [];
    showEditAsmForm.value = false;
}
 
async function onSubmitEditAsm() {
    try {
        if(form.value.title.trim()==='' || form.value.target_groups.length===0 ){
            Swal.fire(
                'เกิดข้อผิดพลาด',
                'กรุณากรอกข้อมูลให้ครบถ้วน',
                'error'
            )
            return;
        }
        setLoadingPage(true);

        const config = { headers: { 'Content-Type': 'multipart/form-data' } };
        let asmUpdateForm = new FormData();
        asmUpdateForm.append('title', form.value.title);
        asmUpdateForm.append('points', form.value.points);
        asmUpdateForm.append('increase_points', form.value.increase_points);
        asmUpdateForm.append('decrease_points', form.value.decrease_points);
        asmUpdateForm.append('due_date', form.value.due_date ? new Date(form.value.due_date).toUTCString() : null);
        asmUpdateForm.append('start_date', form.value.start_date ? new Date(form.value.start_date).toUTCString() : null);
        asmUpdateForm.append('end_date', form.value.end_date ? new Date(form.value.end_date).toUTCString() : null);
        asmUpdateForm.append('is_group_assignment', form.value.is_group_assignment ? 1 : 0);
        // asmUpdateForm.append('target_groups', form.value.target_groups);
        form.value.target_groups.forEach((group) => {
            asmUpdateForm.append('target_groups[]', parseInt(group));
        });

        asmUpdateForm.append('status', form.value.status ? 1 : 0);

        Array.from(tempImagesFile).forEach((image)=>{
        asmUpdateForm.append('images[]', image);
        });

        asmUpdateForm.append('_method', 'put');
        let asmResp = await axios.post(`${props.assignmentApiRoute}/assignments/${props.assignment.id}`, asmUpdateForm, config);
        if (asmResp.status===200) {

            // props.assignment.images = asmResp.data.images;
            emit('update-assignment', asmResp.data.assignment);

            onCancleEditAsm();

            Swal.fire(
                'เรียบร้อย',
                'บันทึกการแก้ไขแบบฝึกหัดเสร็จสมบูรณ์',
                'success'
            );
            // setLoadingPage(false);
        }
        setLoadingPage(false);  
    } catch (err) {
        console.error(err);
        Swal.fire(
            'เกิดข้อผิดพลาด',
            'ไม่สามารถบันทึกการแก้ไขแบบฝึกหัดได้',
            'error'
        );
        setLoadingPage(false);
    }
}

function handleDeleteAssignment(idx, asmId){
    if (!usePage().props.isCourseAdmin) return;
    Swal.fire({
        title: 'แน่ใจหรือไม่?',
        text: "ที่จะลบแบบฝึกหัดนี้อย่างถาวร!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: 'ยกเลิก',
        confirmButtonText: 'ใช่, ยืนยัน!'
        }).then(async (result) => {
        if (result.isConfirmed) {
            setLoadingPage(true);
            const delResp =  await axios.delete(`/assignments/${asmId}`);
            if (delResp.status===200){
                usePage().props.assignments.data.splice(idx, 1);
                setLoadingPage(false);
                Swal.fire('ลบเสร็จสมบูรณ์!','ลบแบบฝึกหัดแล้ว.','success' )
            }else{
                setLoadingPage(false);
                Swal.fire('ลบไม่สำเร็จ!','ลองอีกครั้งหรือติดต่อผู้ดูแลระบบ','error' );
            }
        }
    });
}


function handleDueDateSelection(dueDateData){
  asmDueDate.value = dueDateData;
  form.value.due_date = asmDueDate.value ? new Date(asmDueDate.value) : null;
}

function handleStartDateSelection(startDateData){
    asmStartDate.value = startDateData;
    form.value.start_date = new Date(asmStartDate.value) || null;
}

function handleEndDateSelection(endDateData){
    asmEndDate.value = endDateData;
    form.value.end_date = new Date(asmEndDate.value) || null;
}

</script>

<template>
    <div class="relative bg-white border-t-4 border-blue-600 rounded-lg overflow-auto shadow-lg">
        <div class="absolute top-1 right-2 overflow-visible" v-if="$page.props.isCourseAdmin">
            <DotsDropdownMenu 
                @edit-model="showEditAsmForm=true"
                @delete-model="handleDeleteAssignment(props.asmIndex, props.assignment.id)"
            >
                <template #editModel>
                    <div>แก้ไข</div>
                </template>
                <template #deleteModel>
                    <div>ลบแบบฝึกหัด</div>
                </template>
            </DotsDropdownMenu>
        </div>
        <div class="tabs flex flex-col justify-center ">
            <div class="font-medium p-4 border-b-2 border-blue-600">
                <span class="text-gray-800 text-xl ">
                    แบบฝึกหัด/ภาระงานที่ {{ props.asmIndex+1 }}
                </span> <br />
                <span class="text-blue-500 font-semibold text-xl ">{{ form.points }} คะแนน</span>
                <span class=" font-semibold text-sm" v-if="asmEndDate"
                    :class="new Date() > asmEndDate ? 'text-red-500':'text-green-500'"
                > ( กำหนดส่ง @ {{ asmEndDate.toLocaleString() }} )
                </span>  <br />
                <span class=" font-semibold text-sm" v-if="asmEndDate && assignment.decrease_points>0"
                    :class="'text-red-500'"
                > ( เกินกำหนดส่ง  {{ asmEndDate.toLocaleString() }} )
                </span>
            </div>
            <div class=" p-3 w-full border-b-2 border-blue-400">
                <div class="mb-2">
                    <form :id="`comment-form-${props.assignment.id}`" v-if="$page.props.isCourseAdmin && showEditAsmForm"
                        class="mt-3 relative dark:border-secondary-800 flex flex-col space-y-2 items-center w-full">
                        <Textarea 
                            :id="`comment-form-comment-input-${props.assignment.id}`"
                            type="text" 
                            v-model="form.title" 
                            autoResize 
                            class="text-sm bg-slate-100 w-full dark:text-gray-600 rounded-lg focus:ring-0 border-gray-400"
                        ></Textarea>
                    </form>
                    <div v-else class="flex flex-col space-y-2">
                        <Textarea type="text" v-model="props.assignment.title" autoResize :id="`comment-form-comment-title${props.assignment.id}`"
                            class="text-sm bg-slate-100 w-full dark:text-gray-600 rounded-lg focus:ring-0 border-gray-400"></Textarea>
                    </div>
                </div>
                <div v-if="props.assignment.images.length>0" class="my-2">
                    <AssignmentImagesViewer 
                        :model_id="assignmentableId"
                        :edit="$page.props.isCourseAdmin && showEditAsmForm" 
                        :images="props.assignment.images" 
                    />
                </div>
                <div v-if="tempImagesUrl.length>0">
                    <div v-for="(image,index) in tempImagesUrl" :key="index" class="">
                        <div class="relative mb-2 max-h-fit overflow-hidden">
                            <img :src="image" class="rounded-lg border" />
                            <button @click.prevent="onDeleteTempImagesHandler(index)" v-if="showEditAsmForm"
                                class="absolute top-1 right-1 rounded-full cursor-pointer bg-gray-100 p-[6px]">
                                <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
                            </button>
                        </div>
                    </div>
                </div>
                <div v-if="$page.props.isCourseAdmin && showEditAsmForm" class="flex justify-end">
                    <input type="file" accept="image/*" multiple ref="addAsmImagesInput" id="addAsmImagesInput"
                        class="hidden" @change="onAddAsmImageInputChange">
                    <button class="" id="" @click.prevent="browseAddAsmImages">
                        <Icon icon="heroicons:photo"
                            class="w-9 h-9 hover:scale-110 bg-violet-300 hover:bg-blue-300 text-violet-600 rounded-full p-2 mr-auto"
                        />
                    </button>
                </div>
                <div v-if="$page.props.isCourseAdmin && showEditAsmForm" class="flex justify-start">
                    <label for="assignment-point-input"
                        class=" mr-4 mt-1 block text-lg font-medium text-gray-900 dark:text-white">คะแนน</label>
                    <button @click.prevent="form.points<=0? 0: form.points--;"
                        class="border-violet-500 hover:bg-violet-600 active:bg-violet-700 dark:border-violet-400 flex items-center justify-center rounded-l-xl border-2 p-2 text-xl  transition duration-200 hover:cursor-pointer dark:bg-white/5 dark:text-white dark:hover:bg-white/10 dark:active:bg-white/20">
                        <Icon icon="heroicons-solid:minus-sm"
                            class="hover:text-white hover:scale-150 dark:text-white" />
                    </button>
                    <input type="number" v-model="form.points" id="assignment-point-input"
                        class="w-20 border-x-0 border-y-2 border-violet-500">
                    <button @click.prevent="form.points++"
                        class="border-violet-500 hover:bg-violet-600 active:bg-violet-700 dark:border-violet-400 flex items-center justify-center rounded-r-xl border-2 p-2 text-xl  transition duration-200 hover:cursor-pointer dark:bg-white/5 dark:text-white dark:hover:bg-white/10 dark:active:bg-white/20">
                        <Icon icon="heroicons-solid:plus-sm"
                            class=" hover:text-white hover:scale-150 dark:text-white" />
                    </button>
                    <p class=" mt-5 block text-sm font-base text-red-500 dark:text-white"> * สำหรับคำนวณเกรด</p>
                </div>

                <div  v-if="$page.props.isCourseAdmin && showEditAsmForm" class="flex-wrap items-center mt-2">
                    <div class="flex items-center mt-2">
                        <div class="relative w-1/3 flex items-center justify-start h-10">
                            <input :id="`asm-has-due-date-input-${assignment.id}`" type="checkbox" v-model="hasDueDate"
                                class="w-4 h-4 transition-colors bg-white border-2 rounded appearance-none cursor-pointer focus-visible:outline-none peer border-slate-500 checked:border-emerald-500 checked:bg-emerald-500 checked:hover:border-emerald-600 checked:hover:bg-emerald-600 focus:outline-none checked:focus:border-emerald-700 checked:focus:bg-emerald-700 disabled:cursor-not-allowed disabled:border-slate-100 disabled:bg-slate-50" />
                            <label class="px-2 cursor-pointer text-slate-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400"
                                :for="`asm-has-due-date-input-${assignment.id}`">
                                วันที่สั่งงาน
                            </label>
                        </div>
                        <div v-if="hasDueDate" class="w-2/3 flex flex-wrap justify-between gap-2">
                            <div v-if="hasDueDate" class="flex items-center w-full">
                                <VueDatePicker 
                                    id="assigment-due-date-datepicker-input"
                                    name="assignmentDueDateInput"
                                    locale="th-TH"
                                    :action-row="{ showNow: true }" now-button-label="Now"
                                    :model-value="asmDueDate" 
                                    placeholder="วันที่สั่งงาน"
                                    @update:model-value="handleDueDateSelection"
                                    :format="'dd/MM/yyyy H:mm:ss'"
                                ></VueDatePicker>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center mt-2">
                        <div class="relative w-1/3 flex items-center justify-start h-10">
                            <input :id="`asm-has-starte-date-input-${props.assignment.id}`" type="checkbox" v-model="hasStartDate"
                                class="w-4 h-4 transition-colors bg-white border-2 rounded appearance-none cursor-pointer focus-visible:outline-none peer border-slate-500 checked:border-emerald-500 checked:bg-emerald-500 checked:hover:border-emerald-600 checked:hover:bg-emerald-600 focus:outline-none checked:focus:border-emerald-700 checked:focus:bg-emerald-700 disabled:cursor-not-allowed disabled:border-slate-100 disabled:bg-slate-50" />
                            <label class="px-2 cursor-pointer text-slate-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400"
                                :for="`asm-has-starte-date-input-${props.assignment.id}`">
                                วันที่เริ่มส่ง
                            </label>
                        </div>
                        <div v-if="hasStartDate" class="w-2/3 flex flex-wrap justify-between gap-2">
                            <div class="flex items-center w-full">
                                <VueDatePicker 
                                    id="assigment-start-date-datepicker-input"
                                    name="assignment-start-date-input" 
                                    :action-row="{ showNow: true }" now-button-label="Now"
                                    :model-value="asmStartDate" 
                                    placeholder="วันที่เริ่มส่ง"
                                    @update:model-value="handleStartDateSelection"
                                    :format="'dd/MM/yyyy H:mm:ss'"
                                ></VueDatePicker>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center mt-2">
                        <div class="relative w-1/3 flex items-center justify-start h-10">
                            <input :id="`asm-has-end-date-input-${assignment.id}`" type="checkbox" v-model="hasEndDate"
                                class="w-4 h-4 transition-colors bg-white border-2 rounded appearance-none cursor-pointer focus-visible:outline-none peer border-slate-500 checked:border-emerald-500 checked:bg-emerald-500 checked:hover:border-emerald-600 checked:hover:bg-emerald-600 focus:outline-none checked:focus:border-emerald-700 checked:focus:bg-emerald-700 disabled:cursor-not-allowed disabled:border-slate-100 disabled:bg-slate-50" />
                            <label class="px-2 cursor-pointer text-slate-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400"
                                :for="`asm-has-end-date-input-${assignment.id}`">
                                วันที่สิ้นสุด
                            </label>
                        </div>
                        <div v-if="hasEndDate" class="w-2/3 flex flex-wrap justify-between gap-2">
                            <div class="flex items-center w-full">
                                <VueDatePicker 
                                    id="assigment-end-date-datepicker-input"
                                    name="assignment-end-date-input" 
                                    :model-value="asmEndDate" 
                                    placeholder="วันที่เริ่มส่ง"
                                    @update:model-value="handleEndDateSelection"
                                    :format="'dd/MM/yyyy H:mm:ss'"
                                ></VueDatePicker>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="$page.props.isCourseAdmin && showEditAsmForm" class="flex items-center mt-2">
                    <div class="relative w-1/3 flex items-center justify-start h-10">
                        <span class="px-2 cursor-pointer text-slate-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400"
                        >
                            กลุ่มเป้าหมาย
                        </span>
                    </div>
                    <div v-if="isGroupAssignment" class="w-2/3 flex flex-wrap gap-2">
                            <div class="flex items-center">
                                <input id="asm-target-group-all-input"
                                    type="checkbox" 
                                    :checked="allCourseGroupSelected" 
                                    @change="toggleAllGroups"
                                    class="w-4 h-4 transition-colors bg-white border-2 rounded appearance-none cursor-pointer focus-visible:outline-none peer border-slate-500 checked:border-emerald-500 checked:bg-emerald-500 checked:hover:border-emerald-600 checked:hover:bg-emerald-600 focus:outline-none checked:focus:border-emerald-700 checked:focus:bg-emerald-700 disabled:cursor-not-allowed disabled:border-slate-100 disabled:bg-slate-50" 
                                />
                                <label for="asm-target-group-all-input"
                                    class="px-2 cursor-pointer text-slate-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400">
                                    เลือกทั้งหมด
                                </label>
                            </div>

                            <div class="flex items-center" v-for="group in courseGroups" :key="group.id">
                                <input :id="`asm-target-group-input${group.id}`" 
                                    type="checkbox" 
                                    :value="group.id" 
                                    :checked="isSelected(group.id)"
                                    @change="toggleGroup(group.id)" 
                                    class="w-4 h-4 transition-colors bg-white border-2 rounded appearance-none cursor-pointer focus-visible:outline-none peer border-slate-500 checked:border-emerald-500 checked:bg-emerald-500 checked:hover:border-emerald-600 checked:hover:bg-emerald-600 focus:outline-none checked:focus:border-emerald-700 checked:focus:bg-emerald-700 disabled:cursor-not-allowed disabled:border-slate-100 disabled:bg-slate-50" />
                                <label :for="`asm-target-group-input${group.id}`"
                                    class="px-2 cursor-pointer text-slate-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400">
                                    {{ group.name }}
                                </label>
                            </div>
                    </div>
                </div>

                <div v-if="$page.props.isCourseAdmin && showEditAsmForm" class="flex items-center mt-2">
                    <div class="relative flex items-center justify-start h-10">
                        <input 
                            :id="'edit-asm-increase-personal-point-input'+props.assignment.id" 
                            type="checkbox" 
                            v-model="increasePPWhenCorrect" 
                            class="w-4 h-4 transition-colors bg-white border-2 rounded appearance-none cursor-pointer focus-visible:outline-none peer border-slate-500 checked:border-emerald-500 checked:bg-emerald-500 checked:hover:border-emerald-600 checked:hover:bg-emerald-600 focus:outline-none checked:focus:border-emerald-700 checked:focus:bg-emerald-700 disabled:cursor-not-allowed disabled:border-slate-100 disabled:bg-slate-50" 
                        />
                        <label :for="'edit-asm-increase-personal-point-input'+props.assignment.id" class="px-2 cursor-pointer text-slate-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400">
                            เพิ่มแต้มสะสมเมื่อตอบถูก <span></span>
                        </label>
                    </div>
                    <div v-if="increasePPWhenCorrect" class="flex items-center">
                        <input type="number" v-model="form.increase_points" 
                        id="pp-increased-when-แorrect-input" class="rounded-lg border-2 border-violet-500 w-24"
                        ><span class="text-gray-500 pl-1"> แต้ม</span>
                    </div>
                </div>
                <div v-if="$page.props.isCourseAdmin && showEditAsmForm" class="">
                    <span class="text-sm text-red-400">*แต้มสะสมเมื่อตอบถูก จะหักจากแต้มสะสมของรายวิชา</span>
                </div>
                <div v-if="$page.props.isCourseAdmin && showEditAsmForm" class="flex items-center mt-2">
                    <div class="relative flex items-center justify-start h-10">
                        <input 
                            :id="'edit-asm-decrease-personal-point-input'+props.assignment.id" 
                            type="checkbox" 
                            v-model="decreasePPWhenWrong" 
                            class="w-4 h-4 transition-colors bg-white border-2 rounded appearance-none cursor-pointer focus-visible:outline-none peer border-slate-500 checked:border-emerald-500 checked:bg-emerald-500 checked:hover:border-emerald-600 checked:hover:bg-emerald-600 focus:outline-none checked:focus:border-emerald-700 checked:focus:bg-emerald-700 disabled:cursor-not-allowed disabled:border-slate-100 disabled:bg-slate-50" 
                        />
                        <label :for="'edit-asm-decrease-personal-point-input'+props.assignment.id" class="px-2 cursor-pointer text-slate-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400">
                            ตัดแต้มสะสมเมื่อตอบผิด/เกินกำหนด
                        </label>
                    </div>
                    <div v-if="decreasePPWhenWrong" class="flex items-center">
                        <input type="number" v-model="form.decrease_points" 
                        :id="'edit-pp-decreased-when-wrong-input'+props.assignment.id" class="rounded-lg border-2 border-violet-500 w-24"
                        ><span class="text-gray-500 pl-1"> แต้ม</span>
                    </div>
                </div>
                <div v-if="$page.props.isCourseAdmin && showEditAsmForm" class="">
                    <span class="text-sm text-red-400">*แต้มสะสมเมื่อตอบผิด/เกินกำหนด จะหักจากแต้มสะสมของผู้เรียนให้กับรายวิชา</span>
                </div>
                <div  v-if="$page.props.isCourseAdmin && showEditAsmForm" class="flex">
                    <p class="text-gray-600">ร่าง</p>
                    <input type="checkbox" v-model="form.status" class="peer sr-only opacity-0" :id="'toggle-edit-asm-status'+props.assignment.id" />
                    <label :for="'toggle-edit-asm-status'+props.assignment.id" class="relative flex h-6 w-11 mx-2 cursor-pointer items-center rounded-full bg-gray-400 px-0.5 outline-gray-400 transition-colors before:h-5 before:w-5 before:rounded-full before:bg-white before:shadow before:transition-transform before:duration-300 peer-checked:bg-green-500 peer-checked:before:translate-x-full peer-focus-visible:outline peer-focus-visible:outline-offset-2 peer-focus-visible:outline-gray-400 peer-checked:peer-focus-visible:outline-green-500" >
                        <span class="sr-only">Enable</span>
                    </label>
                    <p :class="form.status==1 ? 'text-green-500 font-semibold':'text-gray-600'">เปิดใช้งาน</p>
                </div>

                <div class="mt-2">
                    <div class="quick-post-footer flex items-center justify-end">
                        <div class="  rounded-b flex justify-center items-center">
                            <div v-if="showEditAsmForm" class="flex space-x-4">
                                <button @click.prevent="onCancleEditAsm"
                                    class="text-gray-600 bg-red-300 hover:bg-red-400 hover:text-white focus:ring-4 focus:ring-violet-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                                    ยกเลิก
                                </button>
                                <button type="button" @click.prevent="onSubmitEditAsm()"
                                    class="text-white bg-green-500 hover:bg-violet-700 focus:ring-4 focus:ring-violet-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                                    บันทึก
                                </button>
                            </div>
                            <div v-else-if="$page.props.isCourseAdmin">
                                <button type="button" @click.prevent="showEditAsmForm=true"
                                    class="text-white bg-violet-600 hover:bg-violet-700 focus:ring-4 focus:ring-violet-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                                    <span class="flex justify-center items-center space-x-2">
                                        <Icon icon="heroicons:pencil-square" class="w-5 h-5 mx-1" />แก้ไข
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-2">
                <div v-if="$page.props.isCourseAdmin">
                    <!-- Component: Icon accordion -->
                    <!-- <div> -->
                    <section class="w-full divide-y rounded divide-slate-200">
                        <details class="p-4 group" v-for="(group, index) in $page.props.groups" :key="index">
                            <summary class="[&::-webkit-details-marker]:hidden relative flex gap-4 pr-8  rounded-lg font-medium list-none cursor-pointer text-slate-700 focus-visible:outline-none group-hover:text-slate-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 stroke-emerald-500  shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-labelledby="title-ac05 desc-ac05">
                                    <title id="title-ac05">Leading icon</title>
                                    <desc id="desc-ac05">Icon that describes the summary</desc>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.25V18a2.25 2.25 0 002.25 2.25h13.5A2.25 2.25 0 0021 18V8.25m-18 0V6a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 6v2.25m-18 0h18M5.25 6h.008v.008H5.25V6zM7.5 6h.008v.008H7.5V6zm2.25 0h.008v.008H9.75V6z" />
                                </svg>
                                {{ group.name }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="absolute right-0 w-4 h-4 transition duration-300 top-1 stroke-slate-700 shrink-0 group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-labelledby="title-ac06 desc-ac06">
                                    <title id="title-ac06">Open icon</title>
                                    <desc id="desc-ac06">icon that represents the state of the summary</desc>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            <!-- <Icon icon="lucide:chevron-down-square" class="absolute right-0 w-4 h-4 transition duration-300 top-1 stroke-slate-700 shrink-0 group-open:rotate-45" /> -->
                            </summary>
                            <div class="mt-4 text-slate-500 ">
                                <AssignmentAnswerViewer 
                                    :assignment="props.assignment" 
                                    :answers="props.assignment.answers.filter((answer)=>answer.course_group===group.id)" 

                                    @delete-answers="(answerIndex) => props.assignment.answers.splice(answerIndex, 1)"
                                />
                            </div>
                        </details>
                    </section>
                    <!-- End Icon accordion -->
                    <!-- </div> -->
                </div>
                <div v-else>
                    <AssignmentAnswerViewer 
                        :assignment="props.assignment" 
                        :answers="props.assignment.answers" 

                        @delete-answers="(answerIndex) => props.assignment.answers.splice(answerIndex, 1)"
                    />
                </div>
            </div>

            <div class="mb-4" v-if="!$page.props.isCourseAdmin && ($page.props.courseMemberOfAuth && $page.props.courseMemberOfAuth.group_member_status == 1 )">
                <AssignmentAnswerForm 
                    :assignmentableType="props.assignmentableType"
                    :assignmentableId="props.assignmentableId" 
                    :assignment_id="props.assignment.id"
                    :wasAnswered="props.assignment.answers ? props.assignment.answers.length > 0 : false"

                    @add-new-answer="(newAnswer) => onAddNewAnswerHandler(newAnswer)" 
                />
            </div>

        </div>
    </div>
</template>
