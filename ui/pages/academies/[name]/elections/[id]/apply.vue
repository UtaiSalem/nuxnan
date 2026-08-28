<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed, onMounted, ref } from 'vue'

definePageMeta({ middleware: ['auth'] })

const route = useRoute()
const api = useApi()
const config = useRuntimeConfig()
const auth = useAuthStore()

const academyName = computed(() => route.params.name as string)
const electionId = computed(() => Number(route.params.id))
const academyId = ref<number | null>(null)

const { can, fetchMyRole } = useAcademyRole(academyId)
const {
  getElection,
  getMyParty,
  searchCandidates,
  applyParty,
  updateMyParty,
  updateMyPartyWithLogo,
  withdrawParty,
} = useElections()

const unwrap = (response: any) => {
  if (!response || typeof response !== 'object' || !('data' in response)) return response
  const payload = (response as any).data
  if (payload && typeof payload === 'object' && !Array.isArray(payload) && 'data' in payload) {
    return (payload as any).data
  }
  return payload
}
const imageUrl = (path: string | null) => {
  if (!path) return null
  if (path.startsWith('http')) return path
  return `${config.public.apiBase}${path.startsWith('/') ? '' : '/storage/'}${path}`
}

const election = ref<any>(null)
const myParty = ref<any>(null)
const errorMsg = ref('')
const successMsg = ref('')
const isSubmitting = ref(false)

const form = ref({
  name: '',
  slogan: '',
  policy: '',
  logo: null as File | null,
  logoPreview: null as string | null,
  members: [] as any[],
})

const query = ref('')
const searchResults = ref<any[]>([])
let searchTimeout: any = null

const handleError = (e: any) => {
  errorMsg.value = e?.data?.message || e?.message || 'เกิดข้อผิดพลาด'
  successMsg.value = ''
  return null
}

const loadData = async () => {
  if (!academyId.value) return
  try {
    const [elecRes, partyRes] = await Promise.all([
      getElection(academyId.value, electionId.value),
      getMyParty(academyId.value, electionId.value),
    ])
    election.value = unwrap(elecRes)
    myParty.value = unwrap(partyRes)
    initForm()
  } catch (e) {
    handleError(e)
  }
}

const initForm = () => {
  if (
    myParty.value &&
    (myParty.value.status === 'pending' || myParty.value.status === 'approved')
  ) {
    form.value.name = myParty.value.name || ''
    form.value.slogan = myParty.value.slogan || ''
    form.value.policy = myParty.value.policy || ''
    form.value.logoPreview = imageUrl(myParty.value.logo_path)
    form.value.logo = null
    form.value.members = (myParty.value.members || []).map((m: any) => ({
      user_id: m.user_id,
      name: m.user?.name || m.user?.display_name || 'Unknown',
      role: m.role,
      position_label: m.position_label || '',
    }))
  } else {
    // New party form
    form.value.name = ''
    form.value.slogan = ''
    form.value.policy = ''
    form.value.logo = null
    form.value.logoPreview = null
    form.value.members = []
    if (auth.user) {
      form.value.members.push({
        user_id: auth.user.id,
        name:
          auth.user.name ||
          (auth.user as any).display_name ||
          (auth.user as any).first_name ||
          'Me',
        role: 'leader',
        position_label: '',
      })
    }
  }
}

const isNominationOpen = computed(() => {
  if (!election.value) return false
  if (election.value.status !== 'nomination') return false
  if (
    election.value.nomination_closes_at &&
    new Date(election.value.nomination_closes_at).getTime() < Date.now()
  )
    return false
  return true
})

const isReadOnly = computed(() => {
  if (!isNominationOpen.value) return true
  if (myParty.value && myParty.value.status !== 'pending') return true
  return false
})

const leaderCountError = computed(() => {
  const leaders = form.value.members.filter((m) => m.role === 'leader')
  if (leaders.length !== 1) return 'ต้องมีประธานพรรค 1 คนเท่านั้น'
  return ''
})

const roles = [
  { value: 'leader', label: 'ประธาน' },
  { value: 'deputy', label: 'รองประธาน' },
  { value: 'secretary', label: 'เลขานุการ' },
  { value: 'treasurer', label: 'เหรัญญิก' },
  { value: 'member', label: 'สมาชิก' },
]

const onFileChange = (e: any) => {
  const file = e.target.files[0]
  if (!file) return
  if (file.size > 5 * 1024 * 1024) {
    alert('ขนาดไฟล์โลโก้ต้องไม่เกิน 5MB')
    e.target.value = ''
    return
  }
  form.value.logo = file
  form.value.logoPreview = URL.createObjectURL(file)
}

const searchDebounce = () => {
  clearTimeout(searchTimeout)
  if (query.value.trim().length < 2) {
    searchResults.value = []
    return
  }
  searchTimeout = setTimeout(async () => {
    if (!academyId.value) return
    try {
      const res = await searchCandidates(academyId.value, electionId.value, query.value.trim())
      const data = unwrap(res)
      searchResults.value = Array.isArray(data) ? data : data?.data || []
      searchResults.value = searchResults.value.filter(
        (cand) => !form.value.members.some((m) => m.user_id === cand.user_id)
      )
    } catch (e) {
      handleError(e)
    }
  }, 300)
}

const addMember = (candidate: any) => {
  if (form.value.members.some((m) => m.user_id === candidate.user_id)) return
  form.value.members.push({
    user_id: candidate.user_id,
    name: candidate.display_name || candidate.name,
    role: 'member',
    position_label: '',
  })
  query.value = ''
  searchResults.value = []
}

const removeMember = (index: number) => {
  if (form.value.members[index].user_id === auth.user?.id) return
  form.value.members.splice(index, 1)
}

const prepareFormData = () => {
  const fd = new FormData()
  fd.append('name', form.value.name)
  if (form.value.slogan) fd.append('slogan', form.value.slogan)
  if (form.value.policy) fd.append('policy', form.value.policy)
  if (form.value.logo) fd.append('logo', form.value.logo)

  form.value.members.forEach((m, idx) => {
    fd.append(`members[${idx}][user_id]`, String(m.user_id))
    fd.append(`members[${idx}][role]`, m.role)
    fd.append(`members[${idx}][sort_order]`, String(idx))
    if (m.position_label) fd.append(`members[${idx}][position_label]`, m.position_label)
  })
  return fd
}

const prepareJsonData = () => {
  return {
    name: form.value.name,
    slogan: form.value.slogan,
    policy: form.value.policy,
    members: form.value.members.map((m, idx) => ({
      user_id: m.user_id,
      role: m.role,
      sort_order: idx,
      position_label: m.position_label,
    })),
  }
}

const submit = async () => {
  if (isReadOnly.value || isSubmitting.value || leaderCountError.value) return
  errorMsg.value = ''
  successMsg.value = ''
  isSubmitting.value = true

  try {
    if (myParty.value && myParty.value.status === 'pending') {
      if (form.value.logo) {
        const fd = prepareFormData()
        fd.append('_method', 'PUT')
        await updateMyPartyWithLogo(academyId.value!, electionId.value, myParty.value.id, fd)
      } else {
        const payload = prepareJsonData()
        await updateMyParty(academyId.value!, electionId.value, myParty.value.id, payload)
      }
      successMsg.value = 'อัปเดตใบสมัครเรียบร้อยแล้ว'
    } else {
      const payload = form.value.logo ? prepareFormData() : prepareJsonData()
      await applyParty(academyId.value!, electionId.value, payload)
      successMsg.value = 'ส่งใบสมัครเรียบร้อยแล้ว'
    }
    await loadData()
  } catch (e) {
    handleError(e)
  } finally {
    isSubmitting.value = false
  }
}

const withdraw = async () => {
  if (!myParty.value) return
  if (!window.confirm('คุณต้องการถอนใบสมัครพรรคใช่หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้')) return
  errorMsg.value = ''
  successMsg.value = ''
  isSubmitting.value = true
  try {
    await withdrawParty(academyId.value!, electionId.value, myParty.value.id)
    successMsg.value = 'ถอนใบสมัครเรียบร้อยแล้ว'
    await loadData()
  } catch (e) {
    handleError(e)
  } finally {
    isSubmitting.value = false
  }
}

const resetParty = () => {
  myParty.value = null
  initForm()
}

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (!response?.success) return
    academyId.value = response.academy.id
    await fetchMyRole()
    if (!can('elections.view')) return navigateTo(`/academies/${academyName.value}`)
    await loadData()
  } catch (e) {
    handleError(e)
  }
})
</script>

<template>
  <main
    class="min-h-screen bg-slate-50 px-0 sm:px-4 py-4 text-slate-900 dark:bg-slate-950 dark:text-white sm:px-8"
  >
    <div class="mx-auto max-w-4xl">
      <!-- Alerts -->
      <div
        v-if="election && !isNominationOpen"
        class="mb-4 rounded-xl bg-amber-100 p-4 text-amber-800"
      >
        {{ election.status === 'draft' ? 'ยังไม่เปิดรับสมัคร' : 'ปิดรับสมัครแล้ว' }}
      </div>
      <div v-if="errorMsg" class="mb-4 rounded-xl bg-rose-100 p-4 text-rose-800">
        {{ errorMsg }}
      </div>
      <div v-if="successMsg" class="mb-4 rounded-xl bg-emerald-100 p-4 text-emerald-800">
        {{ successMsg }}
      </div>

      <!-- Rejected / Withdrawn Status -->
      <div
        v-if="myParty?.status === 'rejected'"
        class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900 dark:bg-rose-950"
      >
        <h3 class="font-bold text-rose-700 dark:text-rose-400">ใบสมัครถูกปฏิเสธ</h3>
        <p class="mt-1 text-sm text-rose-600 dark:text-rose-300">
          หมายเหตุ: {{ myParty.review_note || '-' }}
        </p>
        <p class="mt-1 text-xs text-rose-500">เมื่อ: {{ myParty.reviewed_at }}</p>
        <button
          @click="resetParty"
          class="mt-3 min-h-[44px] rounded-xl bg-rose-600 px-4 py-2 font-semibold text-white"
        >
          สมัครใหม่
        </button>
      </div>
      <div
        v-else-if="myParty?.status === 'withdrawn'"
        class="mb-4 rounded-xl bg-slate-200 p-4 dark:bg-slate-800"
      >
        <h3 class="font-bold">คุณได้ถอนใบสมัครแล้ว</h3>
        <button
          v-if="isNominationOpen"
          @click="resetParty"
          class="mt-3 min-h-[44px] rounded-xl bg-primary-600 px-4 py-2 font-semibold text-white"
        >
          สมัครใหม่
        </button>
      </div>

      <!-- Approved Status Header -->
      <div
        v-if="myParty?.status === 'approved'"
        class="mb-6 rounded-2xl bg-white p-6 text-center shadow-sm dark:bg-slate-900"
      >
        <h2 class="text-2xl font-bold text-emerald-600">ใบสมัครได้รับการอนุมัติ</h2>
        <div class="mt-4 flex flex-col items-center justify-center gap-4">
          <div
            v-if="myParty.number"
            class="flex h-24 w-24 items-center justify-center rounded-full bg-primary-100 text-5xl font-bold text-primary-700"
          >
            {{ myParty.number }}
          </div>
          <button
            v-if="election?.status === 'nomination' || election?.status === 'draft'"
            @click="withdraw"
            class="min-h-[44px] rounded-xl border border-rose-600 px-4 py-2 font-semibold text-rose-600"
          >
            ถอนตัว
          </button>
        </div>
      </div>

      <!-- Form (Shows for pending/approved or new) -->
      <form
        v-if="!myParty || myParty.status === 'pending' || myParty.status === 'approved'"
        @submit.prevent="submit"
        class="rounded-2xl bg-white p-4 shadow-sm sm:p-6 dark:bg-slate-900"
      >
        <div
          class="flex flex-col gap-4 border-b border-slate-200 pb-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between"
        >
          <h1 class="text-2xl font-bold">ใบสมัครพรรค</h1>
          <button
            v-if="myParty?.status === 'pending'"
            type="button"
            @click="withdraw"
            class="min-h-[44px] shrink-0 rounded-xl bg-rose-100 px-4 py-2 font-semibold text-rose-700 dark:bg-rose-900/30 dark:text-rose-400"
          >
            ถอนใบสมัคร
          </button>
        </div>

        <div class="mt-6 space-y-4">
          <div>
            <label class="mb-2 block font-medium"
              >ชื่อพรรค <span class="text-rose-500">*</span></label
            >
            <input
              v-model="form.name"
              :disabled="isReadOnly"
              required
              maxlength="120"
              class="min-h-[44px] w-full rounded-xl border border-slate-300 bg-transparent px-3 py-2 outline-none focus:border-primary-500 disabled:opacity-50 dark:border-slate-700"
            />
          </div>
          <div>
            <label class="mb-2 block font-medium">คำขวัญ</label>
            <input
              v-model="form.slogan"
              :disabled="isReadOnly"
              maxlength="200"
              class="min-h-[44px] w-full rounded-xl border border-slate-300 bg-transparent px-3 py-2 outline-none focus:border-primary-500 disabled:opacity-50 dark:border-slate-700"
            />
          </div>
          <div>
            <label class="mb-2 block font-medium">นโยบาย</label>
            <textarea
              v-model="form.policy"
              :disabled="isReadOnly"
              rows="4"
              class="w-full rounded-xl border border-slate-300 bg-transparent px-3 py-2 outline-none focus:border-primary-500 disabled:opacity-50 dark:border-slate-700"
            ></textarea>
          </div>
          <div>
            <label class="mb-2 block font-medium">โลโก้พรรค (ไม่เกิน 5MB)</label>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
              <img
                v-if="form.logoPreview"
                :src="form.logoPreview"
                class="h-24 w-24 rounded-lg object-cover"
              />
              <div
                v-else
                class="flex h-24 w-24 items-center justify-center rounded-lg bg-slate-100 text-slate-400 dark:bg-slate-800"
              >
                <Icon icon="lucide:image" class="text-3xl" />
              </div>
              <input
                v-if="!isReadOnly"
                type="file"
                accept="image/*"
                @change="onFileChange"
                class="min-h-[44px] w-full text-sm file:mr-4 file:min-h-[44px] file:rounded-xl file:border-0 file:bg-primary-50 file:px-4 file:font-semibold file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-900/30 dark:file:text-primary-400"
              />
            </div>
          </div>
        </div>

        <div class="mt-8 border-t border-slate-200 pt-6 dark:border-slate-800">
          <h2 class="mb-4 text-xl font-bold">ทีมผู้สมัคร</h2>

          <div v-if="!isReadOnly" class="relative mb-6">
            <input
              v-model="query"
              @input="searchDebounce"
              placeholder="ค้นหาสมาชิก (พิมพ์ 2 ตัวอักษรขึ้นไป)..."
              class="min-h-[44px] w-full rounded-xl border border-slate-300 bg-transparent px-3 py-2 outline-none focus:border-primary-500 dark:border-slate-700"
            />
            <div
              v-if="searchResults.length"
              class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-800"
            >
              <button
                v-for="cand in searchResults"
                :key="cand.user_id"
                type="button"
                @click="addMember(cand)"
                class="flex min-h-[44px] w-full flex-col rounded-lg p-2 text-left hover:bg-slate-50 dark:hover:bg-slate-700 sm:flex-row sm:items-center sm:justify-between"
              >
                <span class="font-semibold">{{ cand.display_name }}</span>
                <span class="text-sm text-slate-500"
                  >{{ cand.grade_level }} {{ cand.classroom_name }} ({{ cand.member_code }})</span
                >
              </button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <div class="min-w-[500px] space-y-2 pb-2">
              <div
                v-for="(member, idx) in form.members"
                :key="member.user_id"
                class="flex flex-row items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/50"
              >
                <div class="min-w-0 flex-1 break-words font-semibold">{{ member.name }}</div>
                <select
                  v-model="member.role"
                  :disabled="isReadOnly"
                  class="min-h-[44px] w-32 shrink-0 rounded-xl border border-slate-300 bg-white px-3 py-2 outline-none dark:border-slate-700 dark:bg-slate-900"
                >
                  <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                </select>
                <input
                  v-model="member.position_label"
                  :disabled="isReadOnly"
                  placeholder="ตำแหน่ง (ระบุเอง)"
                  class="min-h-[44px] w-40 shrink-0 rounded-xl border border-slate-300 bg-white px-3 py-2 outline-none dark:border-slate-700 dark:bg-slate-900"
                />
                <button
                  v-if="!isReadOnly && member.user_id !== auth.user?.id"
                  type="button"
                  @click="removeMember(idx)"
                  class="flex min-h-[44px] w-11 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400"
                >
                  <Icon icon="lucide:trash-2" />
                </button>
              </div>
            </div>
          </div>
          <p v-if="leaderCountError && !isReadOnly" class="mt-2 text-sm text-rose-500">
            {{ leaderCountError }}
          </p>
        </div>

        <div v-if="!isReadOnly" class="mt-8 flex justify-end">
          <button
            type="submit"
            :disabled="isSubmitting || !!leaderCountError"
            class="min-h-[44px] w-full rounded-xl bg-primary-600 px-6 py-2 font-semibold text-white disabled:opacity-50 sm:w-auto"
          >
            {{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึกใบสมัคร' }}
          </button>
        </div>
      </form>
    </div>
  </main>
</template>
