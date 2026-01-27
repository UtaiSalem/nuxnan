<script setup>
import { ref, watch } from "vue";

const props = defineProps({
  member_id: Number,
  course_id: Number,
  grade: Number,
  gradeStatus: Boolean,
});

const emit = defineEmits(['update:gradeProgress']);

// กำหนดค่าเริ่มต้นให้ localGradeStatus
const localGradeStatus = ref(props.gradeStatus ? props.grade : 5);

// ตรวจสอบการเปลี่ยนแปลงของ props.gradeStatus
watch(() => props.gradeStatus, (newStatus) => {
  localGradeStatus.value = newStatus ? props.grade : 5;
});

async function handleGradeStatusChange(event) {
  const selectedStatus = event.target.value;
  localGradeStatus.value = parseInt(selectedStatus);
  console.log(localGradeStatus.value);

  try {
    let resp = await axios.patch(`/courses/${props.course_id}/members/${props.member_id}/grade_progress`, { grade_progress: selectedStatus });
    if (resp.data && resp.data.success) {
      console.log(resp.data);
      emit('update:gradeProgress', localGradeStatus.value);
    }
  } catch (error) {
    console.log(error);
  }
}
</script>

<template>
    <select 
        :id="`select-grade-status-progress-${props.member_id}`" 
        v-model="localGradeStatus" 
        @change="handleGradeStatusChange"
        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-[72px] p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
      <option :value="5">{{ 'ร' }}</option>
      <option :value="grade">{{ grade }}</option>
    </select>
</template>
