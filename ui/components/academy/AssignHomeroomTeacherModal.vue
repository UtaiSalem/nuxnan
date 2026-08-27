<script setup lang="ts">
import { ref, watch } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  academyId: number
  classroomId: number
  currentTeacherId?: number | null
}>()
const emit = defineEmits<{ close: []; updated: [] }>()

const api = useApi()
const query = ref('')
const teachers = ref<any[]>([])
const selectedId = ref<number | null>(props.currentTeacherId ?? null)
const loading = ref(false)
const saving = ref(false)
const error = ref('')
let timer: ReturnType<typeof setTimeout> | undefined

const loadTeachers = async () => {
  loading.value = true
  error.value = ''
  try {
    const response: any = await api.get(`/api/academies/${props.academyId}/members/search`, {
      params: {
        search: query.value,
        per_page: 50,
        status: 2,
      },
    })
    const items = response?.members?.data ?? response?.members ?? response?.data ?? []
    teachers.value = items.filter((item: any) =>
      (item.user_id || item.user?.id)
      && item.role !== 'student'
      && item.role !== 'parent'
    )
  } catch (e: any) {
    error.value = e?.data?.message || 'ไม่สามารถค้นหาครูได้'
  } finally {
    loading.value = false
  }
}

watch(query, () => {
  if (timer) clearTimeout(timer)
  timer = setTimeout(loadTeachers, 300)
})
watch(() => props.currentTeacherId, (value) => { selectedId.value = value ?? null })
loadTeachers()

const save = async () => {
  saving.value = true
  error.value = ''
  try {
    await api.patch(`/api/academies/${props.academyId}/classrooms/${props.classroomId}`, { homeroom_teacher_id: selectedId.value })
    emit('updated')
  } catch (e: any) {
    error.value = e?.data?.message || 'ไม่สามารถบันทึกครูประจำชั้นได้'
  } finally {
    saving.value = false
  }
}

// Clearing the post leaves the room without a homeroom teacher. The teacher
// keeps any classroom_members role they already have.
const removing = ref(false)
const remove = async () => {
  removing.value = true
  error.value = ''
  try {
    await api.patch(`/api/academies/${props.academyId}/classrooms/${props.classroomId}`, { homeroom_teacher_id: null })
    emit('updated')
  } catch (e: any) {
    error.value = e?.data?.message || 'ไม่สามารถเอาครูประจำชั้นออกได้'
  } finally {
    removing.value = false
  }
}
</script>

<template>
  <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="emit('close')" />
    <div class="relative flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-800">
      <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-700">
        <h2 class="font-bold text-slate-900 dark:text-white">{{ props.currentTeacherId ? 'เปลี่ยนครูประจำชั้น' : 'แต่งตั้งครูประจำชั้น' }}</h2>
        <button class="text-slate-400 hover:text-slate-700" @click="emit('close')"><Icon icon="fluent:dismiss-24-regular" class="h-5 w-5" /></button>
      </div>
      <div class="space-y-4 overflow-y-auto p-6">
        <input v-model="query" type="search" placeholder="ค้นหาชื่อหรืออีเมล" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-primary-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        <div v-if="loading" class="py-8 text-center text-sm text-slate-500">กำลังค้นหา...</div>
        <div v-else class="space-y-2">
          <button v-for="teacher in teachers" :key="teacher.user_id || teacher.user?.id" class="flex w-full items-center gap-3 rounded-xl border p-3 text-left transition" :class="selectedId === (teacher.user_id || teacher.user?.id) ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/30' : 'border-slate-100 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/50'" @click="selectedId = teacher.user_id || teacher.user?.id">
            <img :src="teacher.member_avatar || teacher.user?.profile_photo_url || teacher.profile_photo_url || '/images/default-avatar.png'" class="h-10 w-10 rounded-full object-cover" />
            <span class="min-w-0 flex-1"><span class="block truncate font-semibold text-slate-900 dark:text-white">{{ teacher.member_name || teacher.user?.name || teacher.name || '-' }}</span><span class="block truncate text-xs text-slate-500">{{ teacher.user?.email || teacher.email || teacher.role_display_name || teacher.role }}</span></span>
            <Icon v-if="selectedId === (teacher.user_id || teacher.user?.id)" icon="fluent:checkmark-circle-24-filled" class="h-5 w-5 text-primary-600" />
          </button>
          <p v-if="!teachers.length" class="py-6 text-center text-sm text-slate-500">ไม่พบสมาชิกที่เป็นครูหรือผู้ดูแล</p>
        </div>
      </div>
      <div class="flex items-center justify-between gap-2 border-t border-slate-100 px-6 py-4 dark:border-slate-700">
        <button
          v-if="props.currentTeacherId"
          class="min-h-[44px] sm:min-h-0 rounded-xl px-3 py-2 text-sm font-semibold text-red-600 transition-colors hover:bg-red-50 disabled:opacity-50 dark:text-red-400 dark:hover:bg-red-950/40"
          :disabled="saving || removing"
          @click="remove"
        >
          {{ removing ? 'กำลังเอาออก...' : 'เอาออกจากตำแหน่ง' }}
        </button>
        <span v-else></span>
        <div class="flex gap-2">
          <button class="min-h-[44px] sm:min-h-0 rounded-xl px-4 py-2 text-sm text-slate-600 dark:text-slate-300" @click="emit('close')">ยกเลิก</button>
          <button class="min-h-[44px] sm:min-h-0 rounded-xl bg-primary-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-50" :disabled="saving || removing" @click="save">{{ saving ? 'กำลังบันทึก...' : 'บันทึก' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>
