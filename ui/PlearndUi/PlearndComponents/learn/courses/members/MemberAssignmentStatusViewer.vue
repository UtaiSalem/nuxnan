<script setup>
// import { ref, computed, onMounted } from 'vue';
import MemberAssignmentsAnswerStatus from '@/PlearndComponents/learn/courses/members/MemberAssignmentAnswerStatus.vue';

const props = defineProps({
    member_info: Object,
});

const emit = defineEmits(['handleEmptyPoints']);

</script>

<template>
<div class="shadow-lg rounded-lg overflow-hidden">
    <table class="w-full table-fixed">
        <thead>
            <tr class="bg-gray-100 text-center">
                <th class="w-1/12 py-2 px-4 text-center text-gray-600 font-bold uppercase">ที่</th>
                <th class="w-full py-2 px-4 text-center text-gray-600 font-bold uppercase">แบบฝึกหัด/ภาระงาน</th>
                <th class="w-8/12 xs:w-4/12 py-2 px-4 text-center text-gray-600 font-bold uppercase">คะแนน</th>
            </tr>
        </thead>
        <tbody class="bg-white">
            <tr v-for="(assignment, index) in $page.props.assignments.data" :key="assignment.id" class="border-b border-gray-200">
                <td class="p-2">{{ index+1 }}</td>
                <td class="p-2 text-sm">{{ assignment.title }}</td>
                <td class="p-2">
                    <!-- <span class="bg-green-500 text-white py-0.5 px-2 rounded-full text-xs"> -->
                        <MemberAssignmentsAnswerStatus
                            :member_info="props.member_info"
                            :assignment="assignment"  
                            :answers="assignment.answers"

                            @getErrorMsg="(msg)=> emit('handleEmptyPoints', 'ภาระงานที่ ' + (index+1) + msg)"
                        />
                    <!-- </span> -->
                </td>
            </tr>
            <!-- Add more rows here -->
        </tbody>
    </table>
</div>
</template>
