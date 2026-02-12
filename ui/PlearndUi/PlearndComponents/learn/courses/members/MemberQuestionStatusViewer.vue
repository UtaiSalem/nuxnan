<script setup>
const emit = defineEmits(['sendErrorMsg']);
</script>
<template>
    <div class="shadow-lg rounded-lg overflow-hidden">
        <table class="w-full table-fixed">
            <thead>
                <tr class="bg-gray-100">
                    <th class="w-1/12 py-2 px-4 text-center text-gray-600 font-bold uppercase">ที่</th>
                    <th class="w-full py-2 px-4 text-center text-gray-600 font-bold uppercase">แบบทดสอบ</th>
                    <th class="w-8/12 xs:w-4/12 px-4 py-2 text-center text-gray-600 font-bold uppercase">คะแนน</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <tr v-for="(quiz, index) in $page.props.quizzes.data" :key="quiz.id" class="border-b border-gray-200">
                    <td class="w-1/12 p-2">{{ index+1 }}</td>
                    <td class="w-full p-2 text-sm">{{ quiz.title }}</td>
                    <td class="p-2">
                        <div class="rounded-md text-xs font-semibold text-center">
                            <span class="text-white py-1 px-2 rounded-md text-xs font-semibold"
                                :class="quiz.member_achieved_score >= quiz.total_score/2 ? 'bg-green-400' : quiz.member_achieved_score > 0 ? 'bg-orange-400' : 'bg-red-500'"
                            >
                                {{ (quiz.member_achieved_score ?? 0) + ' / ' + quiz.total_score }}
                            </span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

