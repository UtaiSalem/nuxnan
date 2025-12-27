<script setup>
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue';
import AssignmentImagesViewer from '../AssignmentImagesViewer.vue';
import DotsDropdownMenu from '@/PlearndComponents/accessories/DotsDropdownMenu.vue';
import PointsManager from '@/PlearndComponents/learn/courses/assignments/answers/PointsManager.vue';

const props = defineProps({
    assignment: Object,
    answer: Object,
    index: Number,
    isDeleting: Boolean,
});
const emit = defineEmits(['delete-answer','update-answer']);

const isLated = computed(()=> {
    return props.answer.created_at > props.assignment.end_date
});

async function onSetPoints(points){
    try {
        emit('update-answer', points);
    } catch (error) {
        console.log(error);
    }
}

</script>
<template>
    <div v-if="answer.student.id === $page.props.auth.user.id || $page.props.isCourseAdmin"
        class="relative flex gap-4 w-full justify-start p-2 rounded-lg"
        :class="answer.points >= (assignment.points/2) ? 'bg-green-100': answer.points ? 'bg-red-200': 'bg-gray-200'">
        <img :src="answer.student.avatar"
            class="hidden sm:block w-9 h-9 p-[2px] rounded-full ring-1 ring-blue-600 dark:ring-gray-500" alt="">
        <div class="w-full">
            <div class="flex justify-between">
                <div class="flex items-end space-x-2">
                    <p class="mb-0">{{ answer.member_name ?? answer.student.name }}</p>
                    <span class="text-sm"
                        :class="isLated ? 'text-red-600':'text-gray-600'"
                    >
                        <!-- {{ 'ส่ง' + answer.created_at }} {{ 'กำหนด' + assignment.end_date }} -->
                        {{ answer.created_at }}
                    </span>
                </div>
                <div v-if="$page.props.isCourseAdmin || (answer.student.id === $page.props.auth.user.id)">
                    <DotsDropdownMenu @delete-model="emit('delete-answer')">
                        <template #deleteModel>
                            <div>ลบคำตอบ</div>
                        </template>
                    </DotsDropdownMenu>
                </div>
            </div>
            <div v-if="answer.content" class="text-gray-700 ml-auto my-2">
                <Textarea 
                    :id="`assignment-answer-content${answer.id}`"
                    type="text" 
                    v-model="answer.content" 
                    autoResize 
                    class="text-sm bg-slate-100 w-full dark:text-gray-600 rounded-lg focus:ring-0 border-gray-400"
                >
                </Textarea>
            </div>
            <div>
                <AssignmentImagesViewer :images="answer.images" />
            </div>
            <div v-if="$page.props.isCourseAdmin">
                <PointsManager 
                    :assignment="props.assignment" 
                    :answer="props.answer"

                    @setPoints="(points)=>onSetPoints(points)" 
                />
            </div>
        </div>

        <div v-if="isDeleting" class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50">
            <div class="text-white">
                <Icon icon="mdi:loading" class="animate-spin w-24 h-24 " />
            </div>
        </div>
    </div>
</template>
