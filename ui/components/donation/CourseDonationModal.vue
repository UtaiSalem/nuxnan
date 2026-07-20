<script setup lang="ts">
import SupportDonationModal from './SupportDonationModal.vue'
defineProps<{ visible: boolean; courseId: number; courseName: string; courseOwnerId: number; balance?: number }>()
defineEmits<{ 'update:visible': [boolean]; donated: [unknown] }>()
</script>
<template>
  <SupportDonationModal :visible="visible" scope="course" :target-id="courseId" :target-name="courseName" :balance="balance" @update:visible="$emit('update:visible', $event)" @donated="$emit('donated', $event)" />
</template>
