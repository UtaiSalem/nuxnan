<script setup lang="ts">
import { ref, watch } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  course: { type: Object, required: true },
})

const emit = defineEmits(['close', 'refresh'])

const api = useApi()
const courseStore = useCourseStore()

const tempName = ref('')
const tempCode = ref('')
const isUpdating = ref(false)

// Watch for modal open to sync data
watch(() => props.show, (val) => {
  if (val && props.course) {
    tempName.value = props.course.name || ''
    tempCode.value = props.course.code || ''
  }
}, { immediate: true })

async function save() {
  if (!tempName.value.trim() || isUpdating.value) return
  isUpdating.value = true
  try {
    const data = { name: tempName.value.trim(), code: tempCode.value.trim() }
    await api.put(`/api/courses/${props.course.id}`, data)
    courseStore.updateCourse(data)
    emit('refresh')
    emit('close')
  } catch (error) {
    console.error('Failed to update course info:', error)
  } finally {
    isUpdating.value = false
  }
}
</script>

<template>
  <DialogModal :show="show" @close="$emit('close')" max-width="2xl">
    <template #title>
      <div class="flex items-center gap-3">
        <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg">
          <Icon icon="fluent:edit-24-filled" class="w-6 h-6 text-white" />
        </div>
        <div>
          <h3 class="text-xl font-black text-gray-900 dark:text-white">แก้ไขข้อมูลรายวิชา</h3>
          <p class="text-sm font-bold text-gray-500 dark:text-gray-400">ชื่อและรหัสวิชา</p>
        </div>
      </div>
    </template>

    <template #content>
      <div class="space-y-6 pt-2">
        <!-- Course Name -->
        <div class="space-y-2">
          <label class="flex items-center gap-2 text-sm font-black text-gray-700 dark:text-gray-300">
            <Icon icon="heroicons:book-open-solid" class="w-5 h-5 text-indigo-600" />
            <span>ชื่อรายวิชา</span>
            <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="tempName"
            rows="3"
            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-bold text-lg"
            placeholder="กรอกชื่อรายวิชา..."
          ></textarea>
        </div>

        <!-- Course Code -->
        <div class="space-y-2">
          <label class="flex items-center gap-2 text-sm font-black text-gray-700 dark:text-gray-300">
            <Icon icon="fluent:number-symbol-square-24-filled" class="w-5 h-5 text-cyan-600" />
            <span>รหัสวิชา</span>
          </label>
          <input
            v-model="tempCode"
            type="text"
            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-cyan-500/20 focus:border-cyan-500 transition-all font-bold"
            placeholder="เช่น CS101, MATH201"
          />
        </div>
      </div>
    </template>

    <template #footer>
      <div class="flex gap-3">
        <button
          @click="$emit('close')"
          class="flex-1 px-6 py-3 rounded-xl bg-gray-100 text-gray-700 font-black hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-all"
        >
          ยกเลิก
        </button>
        <button
          @click="save"
          :disabled="!tempName.trim() || isUpdating"
          class="flex-[2] flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-black hover:from-indigo-700 hover:to-purple-700 shadow-lg shadow-indigo-600/20 transition-all active:scale-95 disabled:opacity-50"
        >
          <Icon v-if="isUpdating" icon="svg-spinners:ring-resize" class="w-5 h-5" />
          <Icon v-else icon="fluent:save-24-filled" class="w-5 h-5" />
          <span>บันทึกข้อมูล</span>
        </button>
      </div>
    </template>
  </DialogModal>
</template>
