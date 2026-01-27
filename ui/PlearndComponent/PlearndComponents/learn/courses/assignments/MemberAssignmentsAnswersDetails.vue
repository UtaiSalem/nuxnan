<template>
    <div>
        <h2>รายละเอียดคำตอบงานที่มอบหมายของสมาชิก</h2>
        <div v-if="answer">
            <p>คำตอบ: {{ answer.content }}</p>
            <img v-if="answer.file_url" :src="answer.file_url" alt="ไฟล์แนบ" />
            <p>คะแนน: {{ answer.points }}</p>
        </div>
        <div v-else>
            <p>ไม่มีคำตอบสำหรับงานที่มอบหมายนี้</p>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    answer: Array,
});
</script>

<style lang="scss" scoped>

</style>