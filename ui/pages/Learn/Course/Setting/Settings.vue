<script setup>

import CourseLayout from '@/Layouts/CourseLayout.vue';
import CourseSettings from '@/PlearndComponents/learn/courses/CourseSettings.vue';

const props = defineProps({
    course: Object,
    lessons: Object,
    groups: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object,
});

async function onUpdateCourseHandler(courseData){
    try {
        const config = { headers: { 'Content-Type': 'multipart/form-data' } };
        let courseUpdateForm = new FormData();
        courseUpdateForm.append('name', courseData.name);
        courseUpdateForm.append('code', courseData.code);
        courseUpdateForm.append('description', courseData.description);
        courseUpdateForm.append('category', courseData.category);
        courseUpdateForm.append('level', courseData.level);   
        courseUpdateForm.append('credit_units', courseData.credit_units);
        courseUpdateForm.append('hours_per_week', courseData.hours_per_week);
        courseUpdateForm.append('start_date', courseData.start_date);
        courseUpdateForm.append('end_date', courseData.end_date);
        courseUpdateForm.append('auto_accept_members', courseData.auto_accept_members);
        courseUpdateForm.append('tuition_fees', courseData.tuition_fees);
        courseUpdateForm.append('saleable', courseData.saleable);
        courseUpdateForm.append('price', courseData.price);
        courseUpdateForm.append('status', courseData.status);
        
        courseData.cover ? courseUpdateForm.append('cover', courseData.cover): null;
        courseUpdateForm.append('_method', 'put');
        let resultResp = await axios.put(`/courses/${props.course.data.id}`, courseUpdateForm ,config);

        if (resultResp.data && resultResp.data.success) {
            // console.log(resultResp.data);
            router.reload({ only: ['course']});
        }
    } catch (error) {
        // Handle error silently
    }

}

</script>

<template>
    <div>
        <CourseLayout 
            :course 
            :isCourseAdmin
            :activeTab="8"
            :courseMemberOfAuth
        >
            <template #courseContent>
                <div v-if="props.isCourseAdmin" class=" mt-4">
                    <CourseSettings 
                        :course="props.course.data" 
                        :isCourseAdmin="props.isCourseAdmin"

                        @update-course="(formData)=> onUpdateCourseHandler(formData)"
                    />
                </div>
            </template>
        </CourseLayout>
    </div>
</template>

