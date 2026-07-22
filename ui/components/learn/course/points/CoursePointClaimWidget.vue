<script setup lang="ts">
const props = defineProps<{ courseId: string | number }>()
const { campaigns, isLoadingCampaigns, isClaiming, fetchAvailableCampaigns, claimCampaign } = useCoursePoints(props.courseId)
const sweetAlert = useSweetAlert()

onMounted(fetchAvailableCampaigns)

const claim = async (id: number) => {
  const result = await claimCampaign(id) as any
  if (result?.success) sweetAlert.toast(`+${result.points_received} แต้ม!`)
}
</script>

<template>
  <section v-if="isLoadingCampaigns || campaigns.length" class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-900/40">
    <div class="mb-4 flex items-center gap-2"><Icon icon="mdi:star-circle" class="h-6 w-6 text-vikinger-purple" /><h2 class="text-lg font-bold text-gray-900 dark:text-white">สะสมแต้ม</h2></div>
    <div v-if="isLoadingCampaigns" class="grid gap-3 sm:grid-cols-2"><div v-for="n in 2" :key="n" class="h-32 animate-pulse rounded-xl bg-gray-200 dark:bg-gray-700" /></div>
    <div v-else-if="campaigns.length" class="grid gap-3 sm:grid-cols-2"><LearnCoursePointsCoursePointClaimCard v-for="campaign in campaigns" :key="campaign.id" :campaign="campaign" :is-claiming="isClaiming === campaign.id" @claim="claim" /></div>
    <p v-else class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีแต้มให้รับตอนนี้</p>
  </section>
</template>
