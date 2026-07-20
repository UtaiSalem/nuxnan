<script setup lang="ts">
import SupportDonationModal from './SupportDonationModal.vue'
defineProps<{ visible: boolean; academyId: number; academyName: string; academyOwnerId: number }>()
defineEmits<{ 'update:visible': [boolean]; donated: [unknown] }>()
</script>
<template>
  <SupportDonationModal :visible="visible" scope="academy" :target-id="academyId" :target-name="academyName" @update:visible="$emit('update:visible', $event)" @donated="$emit('donated', $event)" />
</template>
