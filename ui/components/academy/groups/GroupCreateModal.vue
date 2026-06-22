<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, watch, toRef, computed } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useFieldValidation, validationRules } from '~/composables/useFieldValidation'

interface Props {
  /** v-model:open or :open binding */
  open: boolean
  academyId: number
  /** Optional default type for the picker (e.g. 'department') */
  defaultType?: string
}

const props = withDefaults(defineProps<Props>(), {
  defaultType: 'department',
})

const emit = defineEmits<{
  'update:open': [value: boolean]
  close: []
  /** Fired with the newly created group payload returned by the API */
  created: [group: any]
}>()

const api = useApi()
const { types, fetch } = useAcademyGroupTypes()

const form = ref({
  name: '',
  description: '',
  type: props.defaultType,
})
const isCreating = ref(false)
const containerRef = ref<HTMLElement | null>(null)

const nameRef = toRef(form.value, 'name')
const { error: nameError, isValid: isNameValid } = useFieldValidation(nameRef, [
  validationRules.required('กรุณากรอกชื่อส่วนงาน'),
  validationRules.minLen(3)('ชื่อส่วนงานต้องมีความยาวอย่างน้อย 3 ตัวอักษร'),
  validationRules.maxLen(50)('ชื่อส่วนงานต้องไม่เกิน 50 ตัวอักษร')
])

// Reset form whenever the modal opens (so leftover state doesn't leak between sessions)
watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) resetForm()
  },
)

useFocusTrap(containerRef, computed(() => props.open))

const handleKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && props.open) {
    close()
  }
}

onMounted(() => {
  void fetch()
  window.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeydown)
})

const resetForm = () => {
  form.value = { name: '', description: '', type: props.defaultType }
}

const close = () => {
  if (isCreating.value) return // don't allow close mid-submit
  emit('update:open', false)
  emit('close')
}

const submit = async () => {
  if (!isNameValid.value || isCreating.value) return

  isCreating.value = true
  try {
    const res: any = await api.post(`/api/academies/${props.academyId}/groups`, {
      name: form.value.name,
      description: form.value.description,
      type: form.value.type,
    })

    if (res?.success) {
      emit('created', res.group)
      emit('update:open', false)
      emit('close')

      Swal.fire({
        icon: 'success',
        title: 'เปิดส่วนงานสำเร็จ',
        timer: 1800,
        showConfirmButton: false,
      })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err?.data?.message || 'ไม่สามารถเปิดส่วนงานได้',
    })
  } finally {
    isCreating.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      @click.self="close"
    >
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

      <!-- Modal -->
      <div ref="containerRef" class="relative bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95" role="dialog" aria-modal="true" aria-labelledby="modal-title-group">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
          <h3 id="modal-title-group" class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:building-multiple-24-regular" class="w-6 h-6 text-vikinger-purple" />
            เปิดส่วนงานใหม่
          </h3>
          <button
            type="button"
            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            @click="close"
          >
            <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
          </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-4">
          <!-- Name -->
          <CommonFormField label="ชื่อส่วนงาน" :error="nameError" required hint="เช่น ฝ่ายวิชาการ, ชมรมหุ่นยนต์, ห้อง ม.5/2">
            <template #default="{ id, describedBy }">
              <input
                :id="id"
                v-model="form.name"
                type="text"
                placeholder="เช่น ฝ่ายวิชาการ, ชมรมหุ่นยนต์, ห้อง ม.5/2"
                :aria-describedby="describedBy"
                :aria-invalid="!!nameError"
                class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50"
                @keydown.enter="submit"
              />
            </template>
          </CommonFormField>

          <!-- Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              ประเภทส่วนงาน
            </label>
            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
              <button
                v-for="gtype in types"
                :key="gtype.key"
                type="button"
                :class="[
                  'p-2.5 rounded-lg border-2 flex flex-col items-center gap-1.5 transition-all',
                  form.type === gtype.key
                    ? 'border-vikinger-purple bg-vikinger-purple/10 text-vikinger-purple'
                    : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-vikinger-purple/50',
                ]"
                @click="form.type = gtype.key"
              >
                <Icon :icon="gtype.icon" class="w-5 h-5" />
                <span class="text-[11px] font-semibold leading-tight">{{ gtype.label }}</span>
              </button>
            </div>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              รายละเอียด (ไม่บังคับ)
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="อธิบายเกี่ยวกับส่วนงานนี้..."
              class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50 resize-none"
            ></textarea>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-end gap-3">
          <button
            type="button"
            class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            :disabled="isCreating"
            @click="close"
          >
            ยกเลิก
          </button>
          <button
            type="button"
            class="px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium hover:bg-vikinger-purple/90 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            :disabled="!isNameValid || isCreating"
            @click="submit"
          >
            <Icon v-if="isCreating" icon="svg-spinners:ring-resize" class="w-4 h-4" />
            <Icon v-else icon="fluent:add-24-regular" class="w-4 h-4" />
            เปิดส่วนงาน
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
