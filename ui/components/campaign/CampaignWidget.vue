<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import CampaignCreateModal from '~/components/campaign/CampaignCreateModal.vue'

type CampaignScope = 'public' | 'academy' | 'course'

interface Campaign {
  id: number
  campaign_type: 'advertisement' | 'support'
  scope_type: CampaignScope
  title?: string | null
  description?: string | null
  media_image?: string | null
  media_link?: string | null
  duration?: number | null
  remaining_views?: number
  reward_per_view?: number | null
  advertiser?: { name?: string; avatar?: string | null }
  academy?: { name?: string }
  course?: { title?: string }
}

const props = withDefaults(defineProps<{
  scope?: CampaignScope
  academyId?: number | string | null
  courseId?: number | string | null
  placement?: string
  limit?: number
  hideWhenEmpty?: boolean
  // Creating is opt-in: most placements are consumption surfaces (sidebars, "watch to
  // earn" panels) where the page already carries its own create CTA.
  allowCreate?: boolean
  // Set when the host card already has a heading, so the widget does not repeat it.
  hideHeader?: boolean
}>(), { scope: 'public', placement: 'sidebar', limit: 5 })

const api = useApi()
const authStore = useAuthStore()
const campaigns = ref<Campaign[]>([])
const isLoading = ref(true)
const error = ref('')
const selected = ref<Campaign | null>(null)
const isWatching = ref(false)
const secondsLeft = ref(0)
const isSubmitting = ref(false)
const completedIds = new Set<number>()

const scopeLabel = computed(() => ({ public: 'สาธารณะ', academy: 'โรงเรียน', course: 'รายวิชา' }[props.scope]))

const showCreateModal = ref(false)
const canCreateInline = computed(() => !!props.allowCreate && props.scope !== 'public' && (props.scope === 'academy' ? !!props.academyId : !!props.courseId))
const createModalScope = computed<'academy' | 'course'>(() => props.scope === 'course' ? 'course' : 'academy')
const createModalTargetId = computed(() => props.scope === 'course' ? (props.courseId as number | string) : (props.academyId as number | string))

const campaignCreateLink = computed(() => {
  // The create page prefills from `scope` (not `scope_type`) — see applyQueryPrefill().
  if (props.scope === 'academy' && props.academyId) {
    return { path: '/earn/advertise/create', query: { scope: 'academy', academy_id: props.academyId } }
  }

  if (props.scope === 'course' && props.courseId) {
    return { path: '/earn/advertise/create', query: { scope: 'course', course_id: props.courseId, academy_id: props.academyId || undefined } }
  }

  return '/earn/advertise'
})

// Without create rights the widget only offers browsing, never the create page.
const secondaryLink = computed(() => (props.allowCreate ? campaignCreateLink.value : '/earn/advertise'))
const emptyText = computed(() => (props.allowCreate ? 'ยังไม่มีแคมเปญในพื้นที่นี้' : 'ยังไม่มีแคมเปญให้รับชมตอนนี้'))

function idempotencyKey(campaign: Campaign) {
  return `campaign-view-${campaign.id}-${Date.now()}-${Math.random().toString(36).slice(2)}`
}

async function loadCampaigns() {
  isLoading.value = true
  error.value = ''
  try {
    const query = new URLSearchParams({ scope: props.scope, limit: String(props.limit) })
    if (props.academyId) query.set('academy_id', String(props.academyId))
    if (props.courseId) query.set('course_id', String(props.courseId))
    const response = await api.get(`/api/campaigns/widget?${query.toString()}`)
    campaigns.value = response?.campaigns?.data || response?.campaigns || []
    await Promise.all(campaigns.value.map(campaign => recordImpression(campaign)))
  } catch (err) {
    console.error('Campaign widget load failed', err)
    error.value = 'ไม่สามารถโหลดแคมเปญได้'
  } finally {
    isLoading.value = false
  }
}

async function recordImpression(campaign: Campaign) {
  try {
    await api.post(`/api/campaigns/${campaign.id}/impression`, {
      placement: props.placement,
      idempotency_key: `campaign-impression-${campaign.id}-${props.placement}`,
    })
  } catch (err) {
    console.debug('Campaign impression was not recorded', err)
  }
}

function openCampaign(campaign: Campaign) {
  if (campaign.campaign_type === 'support') {
    selected.value = campaign
    return
  }
  selected.value = campaign
}

async function completeView() {
  if (!selected.value || completedIds.has(selected.value.id) || !authStore.isAuthenticated) return
  isSubmitting.value = true
  try {
    await api.post(`/api/campaigns/${selected.value.id}/view`, { idempotency_key: idempotencyKey(selected.value) })
    completedIds.add(selected.value.id)
    if (selected.value.remaining_views) selected.value.remaining_views -= 1
    if (selected.value.media_link) window.open(selected.value.media_link, '_blank', 'noopener,noreferrer')
    selected.value = null
  } catch (err: any) {
    error.value = err?.data?.message || 'ไม่สามารถบันทึกการรับชมได้'
  } finally {
    isSubmitting.value = false
    isWatching.value = false
  }
}

function startWatching() {
  if (!selected.value) return
  if (!authStore.isAuthenticated) {
    error.value = 'กรุณาเข้าสู่ระบบเพื่อรับรางวัลจากการรับชม'
    return
  }
  isWatching.value = true
  secondsLeft.value = Math.max(1, selected.value.duration || 5)
  const timer = window.setInterval(() => {
    secondsLeft.value -= 1
    if (secondsLeft.value <= 0) {
      window.clearInterval(timer)
      completeView()
    }
  }, 1000)
}

onMounted(loadCampaigns)
</script>

<template>
  <section v-if="!props.hideWhenEmpty || isLoading || error || campaigns.length" class="rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-800" :aria-label="`แคมเปญ${scopeLabel}`">
    <div v-if="!hideHeader || canCreateInline" class="mb-4 flex items-center justify-between gap-3">
      <div v-if="!hideHeader">
        <p class="text-[10px] font-bold uppercase tracking-wider text-indigo-500">{{ scopeLabel }}</p>
        <h3 class="font-bold text-gray-900 dark:text-white">โฆษณาและการสนับสนุน</h3>
      </div>
      <button v-if="canCreateInline" type="button" class="ms-auto inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300" @click="showCreateModal = true">
        <Icon icon="heroicons:plus" class="h-3.5 w-3.5" />
        สร้างแคมเปญ
      </button>
      <NuxtLink v-else-if="!hideHeader" :to="secondaryLink" class="text-xs text-blue-500 hover:underline">
        ดูทั้งหมด
      </NuxtLink>
    </div>

    <div v-if="isLoading" class="space-y-3">
      <div v-for="n in 2" :key="n" class="h-24 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-700" />
    </div>
    <div v-else-if="error" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
      <Icon icon="fluent:error-circle-24-regular" class="mx-auto mb-2 h-7 w-7 text-red-400" />
      {{ error }}
    </div>
    <div v-else-if="campaigns.length === 0" class="rounded-xl border border-dashed border-indigo-200 bg-indigo-50/40 px-4 py-6 text-center dark:border-indigo-900/50 dark:bg-indigo-950/20">
      <Icon icon="fluent:megaphone-off-24-regular" class="mx-auto mb-2 h-8 w-8 text-indigo-400 opacity-70" />
      <p class="text-sm text-gray-600 dark:text-gray-300">{{ emptyText }}</p>
      <button v-if="canCreateInline" type="button" class="min-h-[44px] sm:min-h-0 mt-3 inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700" @click="showCreateModal = true">
        <Icon icon="heroicons:plus" class="h-4 w-4" />
        สร้างแคมเปญแรก
      </button>
      <NuxtLink v-else-if="allowCreate" :to="campaignCreateLink" class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">
        <Icon icon="heroicons:plus" class="h-4 w-4" />
        ดูแคมเปญทั้งหมด
      </NuxtLink>
    </div>
    <div v-else class="space-y-3">
      <button v-for="campaign in campaigns" :key="campaign.id" type="button" class="w-full overflow-hidden rounded-xl bg-gray-50 text-left transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-700/60" @click="openCampaign(campaign)">
        <img v-if="campaign.media_image" :src="campaign.media_image" :alt="campaign.title || 'Campaign'" class="h-28 w-full object-cover" loading="lazy">
        <div v-else class="flex h-20 items-center justify-center bg-gradient-to-br from-indigo-500 to-cyan-500"><Icon icon="fluent:megaphone-24-regular" class="h-8 w-8 text-white/80" /></div>
        <div class="p-3">
          <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ campaign.title || campaign.advertiser?.name || 'สนับสนุนการเรียนรู้' }}</p>
          <div class="mt-1 flex items-center justify-between text-xs text-gray-500 dark:text-gray-300">
            <span>{{ campaign.campaign_type === 'support' ? 'การสนับสนุน' : 'รับชมเพื่อรับรางวัล' }}</span>
            <span v-if="campaign.campaign_type === 'advertisement'" class="font-semibold text-emerald-600">+{{ (campaign.reward_per_view || 0).toFixed(2) }} ฿</span>
          </div>
        </div>
      </button>
    </div>

    <CampaignCreateModal
      v-if="canCreateInline"
      v-model:visible="showCreateModal"
      :scope-type="createModalScope"
      :target-id="createModalTargetId"
      :academy-id="props.scope === 'course' ? (props.academyId ?? null) : null"
      @created="loadCampaigns"
    />

    <div v-if="selected" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4" @click.self="selected = null">
      <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-800">
        <img v-if="selected.media_image" :src="selected.media_image" :alt="selected.title || 'Campaign'" class="max-h-72 w-full object-contain bg-black">
        <div class="space-y-3 p-5">
          <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ selected.title || 'สนับสนุนการเรียนรู้' }}</h4>
          <p v-if="selected.description" class="text-sm text-gray-600 dark:text-gray-300">{{ selected.description }}</p>
          <p v-if="selected.campaign_type === 'advertisement'" class="text-sm text-emerald-600">รับรางวัล +{{ (selected.reward_per_view || 0).toFixed(2) }} บาท เมื่อดูครบเวลา</p>
          <p v-if="isWatching" class="text-center text-sm text-gray-500">กำลังนับเวลาเหลือ {{ secondsLeft }} วินาที</p>
          <div class="flex gap-2">
            <button type="button" class="min-h-[44px] sm:min-h-0 flex-1 rounded-xl border px-4 py-2 text-sm" @click="selected = null">ปิด</button>
            <button v-if="!isWatching" type="button" class="min-h-[44px] sm:min-h-0 flex-1 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" :disabled="isSubmitting" @click="startWatching">
              {{ authStore.isAuthenticated ? 'เริ่มรับชม' : 'เข้าสู่ระบบเพื่อรับชม' }}
            </button>
            <span v-else class="flex flex-1 items-center justify-center rounded-xl bg-gray-100 px-4 py-2 text-sm dark:bg-gray-700">กำลังรับชม...</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
