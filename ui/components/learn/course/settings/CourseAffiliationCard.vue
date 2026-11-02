<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import ResponsiveCard from '~/components/Common/ResponsiveCard.vue'

const course = inject<Ref<any>>('course')
// [id].vue ส่งโรงเรียนแยกมาทาง inject('academy') — course ในหน้านี้ไม่มี field academy
const academy = inject<Ref<any>>('academy')
const courseStore = useCourseStore()

const { hostAcademies, isLoadingHosts, fetchHostAcademies, attach, detach } = useCourseAffiliation()

const selectedAcademyId = ref<number | ''>('')
const isSubmitting = ref(false)

const currentAcademy = computed(() => academy?.value ?? null)

// refreshCourse() ของหน้าแม่คืน cache ของ store — อัพเดทจาก response ตรง ๆ แล้วล้าง cache ให้รอบหน้าโหลดใหม่
const applyAffiliation = (res: any) => {
  if (academy) academy.value = res?.course?.academy ?? null
  if (course?.value) course.value = { ...course.value, academy_id: res?.course?.academy?.id ?? null }
  courseStore.invalidateCourse()
}

onMounted(fetchHostAcademies)

const escapeHtml = (value: unknown): string =>
  String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')

const onAttach = async () => {
  if (!selectedAcademyId.value || isSubmitting.value || !course?.value) return

  const chosen = hostAcademies.value.find(a => a.id === Number(selectedAcademyId.value))
  const name = escapeHtml(chosen?.name ?? '')

  const result = await Swal.fire({
    title: 'เพิ่มรายวิชาเข้าสังกัดโรงเรียน?',
    html: `รายวิชาจะอยู่ใต้สังกัด <b>${name}</b><div style="text-align:left;margin-top:.75rem">• แสดงในรายการและรายงานของโรงเรียน<br>• ผู้ดูแลโรงเรียนแก้ไข ลบ หรือถอนรายวิชานี้ออกจากสังกัดได้<br>• ดึงนักเรียนจากห้องเรียนของโรงเรียนได้<br>• รายได้ค่าเรียนยังเข้าบัญชีเจ้าของรายวิชาเหมือนเดิม</div>`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'ยืนยัน',
    cancelButtonText: 'ยกเลิก',
  })
  if (!result.isConfirmed) return

  isSubmitting.value = true
  try {
    const res: any = await attach(course.value.id, Number(selectedAcademyId.value))
    useToast().success(res?.message || 'เพิ่มเข้าสังกัดเรียบร้อยแล้ว')
    selectedAcademyId.value = ''
    applyAffiliation(res)
  } catch (err: any) {
    useToast().error(err?.data?.message || 'ไม่สามารถเพิ่มเข้าสังกัดได้')
  } finally {
    isSubmitting.value = false
  }
}

const onDetach = async () => {
  if (isSubmitting.value || !course?.value) return

  const name = escapeHtml(currentAcademy.value?.name ?? '')

  const result = await Swal.fire({
    title: 'ถอนรายวิชาออกจากสังกัด?',
    html: `รายวิชาจะกลับเป็นรายวิชาส่วนตัว และหายจากรายการของ <b>${name}</b><br>สมาชิกและผลการเรียนเดิมยังอยู่ครบ`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ถอนออก',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#dc2626',
  })
  if (!result.isConfirmed) return

  isSubmitting.value = true
  try {
    const res: any = await detach(course.value.id)
    useToast().success(res?.message || 'ถอนออกจากสังกัดเรียบร้อยแล้ว')
    applyAffiliation(res)
  } catch (err: any) {
    useToast().error(err?.data?.message || 'ไม่สามารถถอนออกจากสังกัดได้')
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <ResponsiveCard title="สังกัดโรงเรียน" icon="mdi:school-outline" icon-color="text-indigo-500">
    <!-- State B: attached to a school -->
    <div v-if="currentAcademy" class="flex flex-col sm:flex-row sm:items-center gap-3">
      <div class="flex items-center gap-3 min-w-0 flex-1">
        <img
          v-if="currentAcademy.logo_url"
          :src="currentAcademy.logo_url"
          alt=""
          class="w-12 h-12 rounded-xl object-cover flex-shrink-0 bg-gray-100 dark:bg-gray-700"
        >
        <div
          v-else
          class="w-12 h-12 rounded-xl flex-shrink-0 flex items-center justify-center bg-indigo-50 dark:bg-indigo-900/30"
        >
          <Icon icon="mdi:school" class="w-6 h-6 text-indigo-500 dark:text-indigo-300" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-xs text-gray-500 dark:text-gray-400">สังกัดอยู่ที่</p>
          <p class="font-semibold text-gray-900 dark:text-white text-sm sm:text-base break-words">
            {{ currentAcademy.name }}
          </p>
        </div>
      </div>
      <button
        type="button"
        :disabled="isSubmitting"
        class="min-h-[44px] sm:min-h-0 w-full sm:w-auto flex-shrink-0 whitespace-nowrap inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold
          text-red-600 dark:text-red-400
          bg-white dark:bg-gray-800
          border border-red-200 dark:border-red-900/50
          hover:bg-red-50 dark:hover:bg-red-900/20 disabled:opacity-50"
        @click="onDetach"
      >
        <Icon v-if="isSubmitting" icon="svg-spinners:ring-resize" class="w-4 h-4" />
        <Icon v-else icon="mdi:link-off" class="w-4 h-4" />
        ถอนออกจากสังกัด
      </button>
    </div>

    <!-- State A: personal course -->
    <template v-else>
      <div v-if="isLoadingHosts" class="h-11 rounded-xl bg-gray-200 dark:bg-gray-700 animate-pulse" />
      <p v-else-if="hostAcademies.length === 0" class="text-sm text-gray-500 dark:text-gray-400 break-words">
        รายวิชาส่วนตัว — คุณยังไม่ได้เป็นครูหรือผู้ดูแลของโรงเรียนใด จึงยังเพิ่มรายวิชาเข้าสังกัดไม่ได้
      </p>
      <template v-else>
        <p class="text-sm text-gray-600 dark:text-gray-300 break-words mb-3">
          รายวิชานี้เป็นรายวิชาส่วนตัว เลือกโรงเรียนเพื่อนำรายวิชาไปไว้ใต้สังกัด
        </p>
        <div class="flex flex-col sm:flex-row gap-3">
          <select
            v-model="selectedAcademyId"
            aria-label="เลือกโรงเรียน"
            class="min-h-[44px] w-full min-w-0 flex-1 px-4 py-2 rounded-xl
              border border-gray-200 dark:border-gray-600
              bg-gray-50 dark:bg-gray-700
              text-gray-900 dark:text-white text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            <option value="">เลือกโรงเรียน</option>
            <option v-for="a in hostAcademies" :key="a.id" :value="a.id">
              {{ a.name }}
            </option>
          </select>
          <button
            type="button"
            :disabled="!selectedAcademyId || isSubmitting"
            class="min-h-[44px] sm:min-h-0 w-full sm:w-auto flex-shrink-0 whitespace-nowrap inline-flex items-center justify-center gap-2 px-5 py-2 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 disabled:text-gray-500 dark:disabled:bg-gray-700 dark:disabled:text-gray-400"
            @click="onAttach"
          >
            <Icon v-if="isSubmitting" icon="svg-spinners:ring-resize" class="w-4 h-4" />
            <Icon v-else icon="mdi:link-variant" class="w-4 h-4" />
            เพิ่มเข้าสังกัด
          </button>
        </div>
      </template>
    </template>
  </ResponsiveCard>
</template>
