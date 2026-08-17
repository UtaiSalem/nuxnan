<template>
  <div class="max-w-4xl mx-auto p-6">
    <div v-if="pending" class="text-center py-16 text-gray-400">{{ $t('discoverySchools.loading') }}</div>
    <div v-else-if="!school" class="text-center py-16">
      <div class="text-6xl mb-3">😕</div>
      <div class="text-gray-500">{{ $t('discoverySchools.not_available') }}</div>
      <NuxtLink to="/schools" class="text-primary-500 mt-4 inline-block">{{ $t('discoverySchools.back_to_list') }}</NuxtLink>
    </div>
    <div v-else>
      <div class="relative rounded-2xl overflow-hidden h-56 bg-gradient-to-br from-primary-500/20 to-primary-500/40 mb-6">
        <img v-if="school.cover" :src="school.cover" class="w-full h-full object-cover" />
      </div>
      <div class="flex items-start gap-4 mb-6">
        <img v-if="school.logo" :src="school.logo" class="w-16 h-16 rounded-full object-cover border" />
        <div class="flex-1">
          <h1 class="text-2xl font-bold">{{ school.name }}</h1>
          <div class="text-sm text-gray-500">{{ school.owner_display_name }}</div>
        </div>
        <Button :label="$t('discoverySchools.support_button')" icon="pi pi-heart" severity="primary" @click="onSupport" />
      </div>

      <p v-if="school.description" class="text-gray-700 leading-relaxed mb-6">{{ school.description }}</p>

      <div v-if="school.support_summary" class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="p-4 bg-gray-50 rounded-xl">
          <div class="text-xs text-gray-500">{{ $t('discoverySchools.stat.donated_points') }}</div>
          <div class="text-2xl font-bold">{{ school.support_summary.total_donated_points?.toLocaleString() || 0 }}</div>
        </div>
        <div class="p-4 bg-gray-50 rounded-xl">
          <div class="text-xs text-gray-500">{{ $t('discoverySchools.stat.donors') }}</div>
          <div class="text-2xl font-bold">{{ school.support_summary.total_donors || 0 }}</div>
        </div>
        <div class="p-4 bg-gray-50 rounded-xl">
          <div class="text-xs text-gray-500">{{ $t('discoverySchools.stat.courses') }}</div>
          <div class="text-2xl font-bold">{{ school.support_summary.courses_count || 0 }}</div>
        </div>
        <div class="p-4 bg-gray-50 rounded-xl">
          <div class="text-xs text-gray-500">{{ $t('discoverySchools.stat.active_campaigns') }}</div>
          <div class="text-2xl font-bold">{{ school.support_summary.active_campaigns_count || 0 }}</div>
        </div>
      </div>
    </div>

    <AcademyDonationModal
      v-if="school && donationVisible"
      v-model:visible="donationVisible"
      :academy-id="school.id"
      :academy-name="school.name"
      @donated="donationVisible = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { usePublicSchools } from '~/composables/usePublicSchools'
import AcademyDonationModal from '~/components/donation/AcademyDonationModal.vue'
import Button from 'primevue/button'

const route = useRoute()
const { t } = useI18n()
const { detail } = usePublicSchools()

const school = ref<any>(null)
const pending = ref(true)
const donationVisible = ref(false)

async function load () {
  try {
    const res: any = await detail(route.params.slug as string)
    school.value = res?.data || null
  } catch (e: any) {
    school.value = null
  } finally {
    pending.value = false
  }
}

function onSupport () {
  const authCookie = useCookie('auth_token')
  if (!authCookie.value) {
    return navigateTo(`/login?return=${encodeURIComponent(route.fullPath)}`)
  }
  donationVisible.value = true
}

useHead(() => ({ title: school.value?.name || t('discoverySchools.title') }))
await load()
</script>
