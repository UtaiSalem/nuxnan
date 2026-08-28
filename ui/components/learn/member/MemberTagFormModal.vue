<template>
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog as="div" @close="closeModal" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/25" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel
              class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-4 sm:p-6 text-left align-middle shadow-xl transition-all"
            >
              <DialogTitle
                as="h3"
                class="text-lg font-medium leading-6 text-gray-900 dark:text-white flex items-center gap-2"
              >
                <Icon icon="mdi:tag-multiple" class="w-5 h-5" />
                {{ mode === 'create' ? 'สร้างแท็กใหม่' : 'แก้ไขแท็ก' }}
              </DialogTitle>

              <form @submit.prevent="handleSubmit" class="mt-4 space-y-4">
                <!-- Name -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    ชื่อแท็ก <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="เช่น VIP, พิเศษ, รุ่น 1"
                    maxlength="50"
                    required
                  />
                </div>

                <!-- Color -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    สี
                  </label>
                  <div class="flex flex-wrap gap-2">
                    <button
                      v-for="color in availableColors"
                      :key="color.value"
                      type="button"
                      class="w-8 h-8 rounded-full border-2 transition-transform hover:scale-110"
                      :class="form.color === color.value ? 'border-gray-800 dark:border-white ring-2 ring-offset-2 ring-indigo-500' : 'border-transparent'"
                      :style="{ backgroundColor: color.value }"
                      :title="color.label"
                      @click="form.color = color.value"
                    />
                  </div>
                </div>

                <!-- Description -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    คำอธิบาย
                  </label>
                  <textarea
                    v-model="form.description"
                    rows="2"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="คำอธิบายสั้นๆ เกี่ยวกับแท็กนี้"
                    maxlength="200"
                  />
                </div>

                <!-- Preview -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    ตัวอย่าง
                  </label>
                  <div class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium border"
                      :style="{ backgroundColor: form.color + '20', color: form.color, borderColor: form.color }"
                    >
                      <Icon icon="mdi:tag" class="w-3 h-3" />
                      {{ form.name || 'ชื่อแท็ก' }}
                    </span>
                  </div>
                </div>

                <!-- Actions -->
                <div class="mt-6 flex justify-end gap-3">
                  <button
                    type="button"
                    class="min-h-[44px] sm:min-h-0 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
                    @click="closeModal"
                  >
                    ยกเลิก
                  </button>
                  <button
                    type="submit"
                    class="min-h-[44px] sm:min-h-0 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                    :disabled="!form.name || loading"
                  >
                    <Icon v-if="loading" icon="mdi:loading" class="w-4 h-4 animate-spin inline mr-1" />
                    {{ mode === 'create' ? 'สร้างแท็ก' : 'บันทึก' }}
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { Icon } from '@iconify/vue'
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
  DialogTitle,
} from '@headlessui/vue'

interface Tag {
  id?: number
  name: string
  color: string
  description?: string | null
}

interface Props {
  isOpen: boolean
  mode?: 'create' | 'edit'
  tag?: Tag | null
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'create',
  tag: null,
})

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'submit', data: { name: string; color: string; description?: string | null }): void
}>()

const loading = ref(false)

const form = reactive({
  name: '',
  color: '#6366f1',
  description: '',
})

const availableColors = [
  { label: 'น้ำเงิน', value: '#3b82f6' },
  { label: 'ม่วง', value: '#8b5cf6' },
  { label: 'ม่วงแดง', value: '#6366f1' },
  { label: 'ชมพู', value: '#ec4899' },
  { label: 'แดง', value: '#ef4444' },
  { label: 'ส้ม', value: '#f97316' },
  { label: 'เหลือง', value: '#eab308' },
  { label: 'เขียวมะนาว', value: '#84cc16' },
  { label: 'เขียว', value: '#22c55e' },
  { label: 'เขียวน้ำเงิน', value: '#14b8a6' },
  { label: 'ฟ้า', value: '#06b6d4' },
  { label: 'เทา', value: '#6b7280' },
]

watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal && props.tag && props.mode === 'edit') {
      form.name = props.tag.name
      form.color = props.tag.color
      form.description = props.tag.description || ''
    } else if (newVal && props.mode === 'create') {
      form.name = ''
      form.color = '#6366f1'
      form.description = ''
    }
  }
)

function closeModal() {
  emit('close')
}

function handleSubmit() {
  if (!form.name) return
  emit('submit', {
    name: form.name.trim(),
    color: form.color,
    description: form.description?.trim() || null,
  })
}
</script>
