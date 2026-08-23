<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import jsQR from 'jsqr'

definePageMeta({ layout: false, middleware: ['auth'] })

const route = useRoute()
const { t } = useI18n()
const api = useApi()
const config = useRuntimeConfig()
const academyName = computed(() => route.params.name as string)
const academyId = ref<number | null>(null)
const electionId = computed(() => Number(route.params.id))
const stationId = computed(() => Number(route.query.station || route.params.station || 1))
const { openStation, closeStation, stationProgress, lookupVoter, searchVoters, issueBallot, castBallot } = useElections()
const { can, fetchMyRole } = useAcademyRole(academyId)

const station = ref<any>(null)
const stationNotFound = ref(false)
const voter = ref<any>(null)
const mode = ref<'identify' | 'ballot' | 'done'>('identify')
const code = ref('')
const query = ref('')
const searchResults = ref<any[]>([])
const ballotParties = ref<any[]>([])
const error = ref('')
const token = ref('')
const selected = ref<any>(null)
const seconds = ref(180)
let timer: ReturnType<typeof setInterval> | undefined
const video = ref<HTMLVideoElement>()
const canvas = ref<HTMLCanvasElement>()

const apiArgs = () => academyId.value
  ? [academyId.value, electionId.value, stationId.value] as [number, number, number]
  : null
const unwrap = (response: any) => response?.data?.data ?? response?.data ?? response
const imageUrl = (path: string | null) => {
  if (!path) return null
  if (path.startsWith('http')) return path
  return `${config.public.apiBase}${path.startsWith('/') ? '' : '/storage/'}${path}`
}
const handleError = (e: any) => {
  error.value = e?.data?.message || e?.message || 'เกิดข้อผิดพลาด'
  return null
}
const refresh = async () => {
  const args = apiArgs()
  if (!args) return
  try {
    station.value = { ...(station.value || {}), ...unwrap(await stationProgress(...args)) }
  } catch (e: any) {
    if (e?.statusCode === 404 || e?.status === 404) stationNotFound.value = true
    else handleError(e)
  }
}
const identify = async (value: string) => {
  const args = apiArgs()
  if (!args || !value.trim()) return
  error.value = ''
  try {
    voter.value = unwrap(await lookupVoter(...args, value.trim()))
    searchResults.value = []
  } catch (e) { handleError(e) }
}
const search = async () => {
  const args = apiArgs()
  if (!args || !query.value.trim()) return
  try {
    const data = unwrap(await searchVoters(...args, query.value.trim()))
    searchResults.value = Array.isArray(data) ? data : data?.data || []
  } catch (e) { handleError(e) }
}
const selectCandidate = async (candidate: any) => {
  searchResults.value = []
  await identify(candidate.member_code || String(candidate.user_id))
}
const issue = async () => {
  const args = apiArgs()
  if (!args || !voter.value?.user_id || voter.value.status !== 'eligible') return
  try {
    const data = unwrap(await issueBallot(...args, voter.value.user_id))
    token.value = data.ballot_token
    ballotParties.value = data.parties || []
    station.value.allow_abstain = data.allow_abstain
    seconds.value = data.ballot_ttl_seconds
    mode.value = 'ballot'
    startTimer()
  } catch (e) { handleError(e) }
}
const startTimer = () => {
  clearInterval(timer)
  timer = setInterval(() => {
    seconds.value -= 1
    if (seconds.value <= 0) {
      clearInterval(timer)
      mode.value = 'identify'
      voter.value = null
      error.value = t('elections.station.ballotExpired')
    }
  }, 1000)
}
const choose = (party: any) => { selected.value = party }
const resetAfterCast = () => {
  code.value = ''
  query.value = ''
  searchResults.value = []
  ballotParties.value = []
  token.value = ''
  error.value = ''
  voter.value = null
  selected.value = null
}
const cast = async () => {
  if (!selected.value || !academyId.value) return
  try {
    await castBallot(academyId.value, electionId.value, {
      ballot_token: token.value,
      party_id: selected.value.id ?? null,
    })
    mode.value = 'done'
    clearInterval(timer)
    await refresh()
    setTimeout(() => { resetAfterCast(); mode.value = 'identify' }, 3000)
  } catch (e) { handleError(e) }
}
const toggleStation = async () => {
  const args = apiArgs()
  if (!args) return
  try {
    if (station.value?.is_open && !window.confirm('ยืนยันปิดหน่วยเลือกตั้งหรือไม่?')) return
    if (station.value?.is_open) await closeStation(...args)
    else await openStation(...args)
    await refresh()
  } catch (e) { handleError(e) }
}
let stream: MediaStream | null = null
let scanFrame: number | null = null
const stopScanning = () => {
  if (scanFrame !== null) cancelAnimationFrame(scanFrame)
  scanFrame = null
  stream?.getTracks().forEach(track => track.stop())
  stream = null
  if (video.value) video.value.srcObject = null
}
const scanLoop = () => {
  if (!video.value || !canvas.value || mode.value !== 'identify') return
  const context = canvas.value.getContext('2d')
  if (context && video.value.readyState >= 2) {
    canvas.value.width = video.value.videoWidth
    canvas.value.height = video.value.videoHeight
    context.drawImage(video.value, 0, 0, canvas.value.width, canvas.value.height)
    const result = jsQR(context.getImageData(0, 0, canvas.value.width, canvas.value.height).data, canvas.value.width, canvas.value.height)
    if (result?.data) { stopScanning(); identify(result.data); return }
  }
  scanFrame = requestAnimationFrame(scanLoop)
}
const startScanning = async () => {
  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
    if (video.value) { video.value.srcObject = stream; await video.value.play(); scanLoop() }
  } catch (e) { handleError(e) }
}
onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (!response?.success) return
    academyId.value = response.academy.id
    await fetchMyRole()
    if (!can('elections.station')) return navigateTo(`/academies/${academyName.value}`)
    await refresh()
  } catch (e) { handleError(e) }
})
onUnmounted(() => { stopScanning(); clearInterval(timer) })
const statusClass = computed(() => voter.value?.status === 'eligible' ? 'text-emerald-600' : 'text-amber-600')
</script>

<template>
  <main class="min-h-screen bg-slate-50 px-4 py-4 text-slate-900 dark:bg-slate-950 dark:text-white sm:px-8">
    <section v-if="stationNotFound" class="mx-auto flex min-h-[calc(100vh-2rem)] max-w-xl items-center justify-center text-center">
      <div class="rounded-2xl bg-white p-6 shadow-sm dark:bg-slate-900 sm:p-10">
        <Icon icon="lucide:map-pin-off" class="mx-auto text-6xl text-rose-500" />
        <h1 class="mt-5 text-2xl font-bold">ไม่พบหน่วยเลือกตั้งนี้</h1>
        <p class="mt-2 text-slate-500">กรุณาเปิดหน้านี้จากลิงก์ที่ผู้ดูแลแจกให้</p>
      </div>
    </section>
    <template v-else-if="station">
      <header class="mx-auto flex max-w-6xl flex-col gap-4 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
        <div class="min-w-0"><p class="text-sm text-slate-500">{{ t('elections.station.title') }}</p><h1 class="break-words text-xl font-bold">{{ station.name }}</h1><p v-if="station.location" class="text-sm text-slate-500">{{ station.location }}</p></div>
        <div class="flex shrink-0 flex-wrap items-center gap-3 sm:justify-end"><div class="text-left sm:text-right"><span :class="station.is_open ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'" class="rounded-full px-3 py-2 text-sm">{{ station.is_open ? t('elections.station.open') : t('elections.station.closed') }}</span><p class="mt-2 text-sm text-slate-500">{{ t('elections.station.progress', { issued: station.issued || 0, cast: station.cast || 0 }) }}</p></div><button class="min-h-[44px] rounded-xl px-4 py-2 font-semibold text-white" :class="station.is_open ? 'bg-rose-600' : 'bg-emerald-600'" @click="toggleStation">{{ station.is_open ? 'ปิดหน่วย' : 'เปิดหน่วย' }}</button></div>
      </header>
      <section v-if="mode === 'identify'" class="mx-auto grid min-h-[calc(100vh-8rem)] max-w-6xl items-center gap-6 py-6 lg:grid-cols-2">
        <div class="rounded-2xl bg-white p-6 shadow-sm dark:bg-slate-900 sm:p-10"><Icon icon="lucide:user-round-check" class="mb-6 text-5xl text-primary-600" /><h2 class="text-3xl font-bold">{{ t('elections.station.identifyHeading') }}</h2><p class="mt-2 text-slate-500">{{ t('elections.station.identifyHelp') }}</p><div class="mt-8 space-y-4"><form @submit.prevent="identify(code)"><label class="mb-2 block font-medium">{{ t('elections.station.codeLabel') }}</label><input v-model="code" class="min-h-[52px] w-full rounded-xl border border-slate-300 bg-transparent px-4 text-lg outline-none focus:border-primary-500" :placeholder="t('elections.station.codePlaceholder')" /></form><form @submit.prevent="search"><label class="mb-2 block font-medium">{{ t('elections.station.searchLabel') }}</label><input v-model="query" class="min-h-[52px] w-full rounded-xl border border-slate-300 bg-transparent px-4 text-lg outline-none focus:border-primary-500" :placeholder="t('elections.station.searchPlaceholder')" /></form><button type="button" class="min-h-[52px] w-full rounded-xl bg-primary-600 px-5 py-4 font-semibold text-white" @click="startScanning"><Icon icon="lucide:scan-line" class="mr-2 inline" />{{ t('elections.station.scan') }}</button><video ref="video" v-show="false" /><canvas ref="canvas" v-show="false" /></div></div>
        <div v-if="searchResults.length" class="space-y-2"><button v-for="candidate in searchResults" :key="candidate.user_id || candidate.id" class="min-h-[56px] w-full rounded-xl border bg-white p-3 text-left dark:bg-slate-900" @click="selectCandidate(candidate)"><span class="font-semibold">{{ candidate.display_name || candidate.name }}</span><span class="ml-2 text-sm text-slate-500">{{ candidate.classroom_name || candidate.classroom }}</span></button></div>
        <div v-else-if="voter" class="rounded-2xl bg-white p-6 shadow-sm dark:bg-slate-900 sm:p-10"><img v-if="voter.photo" :src="imageUrl(voter.photo) || undefined" class="mx-auto h-32 w-32 rounded-full object-cover" /><div class="mt-5 text-center"><h2 class="break-words text-3xl font-bold">{{ voter.name }}</h2><p class="mt-2 text-xl">{{ voter.grade_level }} {{ voter.classroom }}</p><p :class="statusClass" class="mt-4 text-lg font-semibold">{{ voter.status_label }}</p><button v-if="voter.status === 'eligible'" class="mt-6 min-h-[52px] w-full rounded-xl bg-primary-600 px-5 py-4 text-lg font-semibold text-white" @click="issue">{{ t('elections.station.issue') }}</button></div></div>
      </section>
      <section v-else-if="mode === 'ballot'" class="mx-auto max-w-5xl py-10"><div class="mb-8 flex items-center justify-between gap-4"><h2 class="text-3xl font-bold">{{ t('elections.station.ballotHeading') }}</h2><span class="shrink-0 rounded-full bg-amber-100 px-4 py-2 font-bold text-amber-800">{{ seconds }}s</span></div><div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"><button v-for="party in ballotParties" :key="party.id" class="rounded-2xl bg-white p-5 text-left shadow-sm dark:bg-slate-900" @click="choose(party)"><div class="flex items-center gap-3"><span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-100 text-2xl font-bold text-primary-700">{{ party.number }}</span><img v-if="party.logo_path" :src="imageUrl(party.logo_path) || undefined" class="h-12 w-12 rounded-lg object-contain" /></div><p class="mt-5 text-xl font-bold">{{ party.name }}</p></button><button v-if="station.allow_abstain !== false" class="min-h-[100px] rounded-2xl border-2 border-dashed border-slate-300 p-5 text-xl font-bold dark:border-slate-700" @click="choose({ id: null })">{{ t('elections.station.abstain') }}</button></div><div v-if="selected" class="fixed inset-x-4 bottom-4 mx-auto max-w-xl rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900"><p class="text-center">{{ t('elections.station.confirmPrompt') }}</p><div class="mt-4 flex gap-3"><button class="min-h-[44px] flex-1 rounded-xl border px-4 py-3" @click="selected = null">{{ t('common.cancel') }}</button><button class="min-h-[44px] flex-1 rounded-xl bg-primary-600 px-4 py-3 font-semibold text-white" @click="cast">{{ t('common.confirm') }}</button></div></div></section>
      <section v-else class="flex min-h-[calc(100vh-8rem)] items-center justify-center"><div class="text-center"><Icon icon="lucide:check-circle-2" class="mx-auto text-7xl text-emerald-500" /><h2 class="mt-6 text-3xl font-bold">{{ t('elections.station.castSuccess') }}</h2></div></section>
      <p v-if="error" class="fixed bottom-4 left-4 right-4 mx-auto max-w-xl rounded-xl bg-rose-100 p-4 text-center text-rose-800">{{ error }}</p>
    </template>
  </main>
</template>
