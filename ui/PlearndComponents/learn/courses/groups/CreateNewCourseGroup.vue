<script setup>
import { ref } from 'vue';
const props = defineProps({
    course: Object,
});
const form = ref({
    name: '',
    description: '',
});

const emit = defineEmits(['add-new-group']);

async function handleFormSubmit(){
    try {
        if (form.value.name.length>0) {
            let groupResp = await axios.post(`/courses/${props.course.id}/groups`, form.value);
            if (groupResp.data.success) {
                // console.log(groupResp.data);
                emit('add-new-group', groupResp.data.group)
                form.value.name = '';
                form.value.description = '';
            }
        }
    } catch (error) {
        // Handle error silently
    }
}
</script>

<template>
    <div class="">
        <form id="create-new-course-group-form" class="flex flex-col space-y-4"
            @submit.prevent="handleFormSubmit"
        >
            <!-- Component: Plain large input with leading icon  -->
            <div class="relative my-2">
            <input id="id-l12" type="text" v-model="form.name" placeholder="ชื่อกลุ่ม" class="relative w-full h-12 px-4 pl-12 placeholder-transparent transition-all border-b outline-none focus-visible:outline-none peer border-slate-200 text-slate-500 autofill:bg-white invalid:border-pink-500 invalid:text-pink-500 focus:border-violet-500 focus:outline-none invalid:focus:border-pink-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400" />
            <label for="id-l12" class="cursor-text peer-focus:cursor-default peer-autofill:-top-2 absolute left-2 -top-2 z-[1] px-2 text-xs text-slate-400 transition-all before:absolute before:top-0 before:left-0 before:z-[-1] before:block before:h-full before:w-full before:bg-white before:transition-all peer-placeholder-shown:top-3 peer-placeholder-shown:left-10 peer-placeholder-shown:text-base peer-required:after:text-pink-500 peer-required:after:content-['\00a0*'] peer-invalid:text-pink-500 peer-focus:-top-2 peer-focus:left-2 peer-focus:text-xs peer-focus:text-violet-500 peer-invalid:peer-focus:text-pink-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400 peer-disabled:before:bg-transparent">
                ชื่อกลุ่ม
            </label>
            <svg xmlns="http://www.w3.org/2000/svg" class="absolute w-6 h-6 top-3 left-4 stroke-slate-400 peer-disabled:cursor-not-allowed" fill="none" aria-hidden="true" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-labelledby="title-5 description-5" role="graphics-symbol">
                <title id="title-5">Check mark icon</title>
                <desc id="description-5">Icon description here</desc>
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <small class="absolute flex justify-between w-full px-4 py-1 text-xs transition text-slate-400 peer-invalid:text-pink-500">
                <span>Text field with helper text</span>
            </small>
            </div>
            <!-- End Plain large input with leading icon -->  
            <!-- Component: Plain base size basic textarea -->
            <div class="relative">
            <textarea id="id-b02" type="text" v-model="form.description" rows="2" placeholder="Write your message" class="relative w-full px-4 py-2 text-sm placeholder-transparent transition-all border-b outline-none focus-visible:outline-none peer border-slate-200 text-slate-500 autofill:bg-white invalid:border-pink-500 invalid:text-pink-500 focus:border-violet-500 focus:outline-none invalid:focus:border-pink-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400"></textarea>
            <label for="id-b02" class="cursor-text peer-focus:cursor-default absolute left-2 -top-2 z-[1] px-2 text-xs text-slate-400 transition-all before:absolute before:top-0 before:left-0 before:z-[-1] before:block before:h-full before:w-full before:bg-white before:transition-all peer-placeholder-shown:top-2.5 peer-placeholder-shown:text-sm peer-required:after:text-pink-500 peer-required:after:content-['\00a0*'] peer-invalid:text-pink-500 peer-focus:-top-2 peer-focus:text-xs peer-focus:text-violet-500 peer-invalid:peer-focus:text-pink-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400 peer-disabled:before:bg-transparent">
                คำอธิบาย (ถ้ามี)
            </label>
            </div>
            <!-- End Plain base size basic textarea -->     
            <div class="text-right my-6" v-if="form.name.length>0">
                <button type="submit" class="py-3 px-8 bg-green-500 text-green-100 font-bold rounded">Submit</button>
            </div>
        </form>
    </div>
</template>
