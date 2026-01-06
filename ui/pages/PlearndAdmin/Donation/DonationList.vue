<script setup>
import { ref } from 'vue'
import MainLayout from '~/layouts/main.vue'
import DonateCard from '@/components/earn/donates/DonateCard.vue'
import InfiniteLoading from 'v3-infinite-loading'

const props = defineProps({
  donates: Object,
})

const isLoadingDonates = ref(false)
const refDonatesData = ref(props.donates.data ?? [])
const nextPageUrl = ref(
  props.donates.links.next ? '/plearnd-admin/supports/donates/more?page=2' : null
)

const fetchMoreDonations = async () => {
  try {
    if (!nextPageUrl.value) return
    isLoadingDonates.value = true
    const moreDonatesResp = await axios.get(nextPageUrl.value)
    if (moreDonatesResp.data) {
      moreDonatesResp.data.donates.data.forEach((donate) => {
        refDonatesData.value.push(donate)
      })
      nextPageUrl.value = moreDonatesResp.data.donates.next_page_url
    }
    isLoadingDonates.value = false
  } catch (error) {
    // Handle error silently
  }
}
</script>

<template>
  <MainLayout title="Donate List">
    <template #coverProfileCard>
      <div
        class="flex items-center max-w-full mx-auto mt-2 mb-4 shadow-lg bg-cover bg-no-repeat rounded-lg"
        :style="{ backgroundImage: 'url(/storage/images/banner/banner-bg.png)' }"
      >
        <img
          class="section-banner-icon"
          :src="'/storage/images/banner/badges-icon.png'"
          alt="forums-icon"
        />
        <p class="text-white font-bold text-4xl">รายการสนับสนุนทุนการเรียนรู้</p>
      </div>
    </template>

    <template #mainContent>
      <div class="py-4">
        <div
          v-if="refDonatesData.length"
          class="mx-auto max-w-full flex flex-wrap justify-center gap-2"
        >
          <div
            v-for="(donate, index) in refDonatesData"
            :key="index"
            class="w-full md:w-[48%] xl:w-[30%]"
          >
            <DonateCard :donate="donate" />
          </div>
        </div>
        <div v-else class="text-center">
          <p class="text-gray-500">ไม่มีรายการสนับสนุนทุนการเรียนรู้</p>
        </div>
        <InfiniteLoading @distance="1" @infinite="fetchMoreDonations" />
        <div
          v-if="isLoadingDonates"
          class="w-full flex justify-center items-center mt-2 text-violet-600"
        >
          <svg
            width="120"
            height="120"
            fill="currentColor"
            class="m-2 animate-spin"
            viewBox="0 0 1792 1792"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M526 1394q0 53-37.5 90.5t-90.5 37.5q-52 0-90-38t-38-90q0-53 37.5-90.5t90.5-37.5 90.5 37.5 37.5 90.5zm498 206q0 53-37.5 90.5t-90.5 37.5-90.5-37.5-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm-704-704q0 53-37.5 90.5t-90.5 37.5-90.5-37.5-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm1202 498q0 52-38 90t-90 38q-53 0-90.5-37.5t-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm-964-996q0 66-47 113t-113 47-113-47-47-113 47-113 113-47 113 47 47 113zm1170 498q0 53-37.5 90.5t-90.5 37.5-90.5-37.5-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm-640-704q0 80-56 136t-136 56-136-56-56-136 56-136 136-56 136 56 56 136zm530 206q0 93-66 158.5t-158 65.5q-93 0-158.5-65.5t-65.5-158.5q0-92 65.5-158t158.5-66q92 0 158 66t66 158z"
            ></path>
          </svg>
        </div>
      </div>
    </template>
  </MainLayout>
</template>
