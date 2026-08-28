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
                <Icon icon="mdi:tag-plus" class="w-5 h-5 text-indigo-600" />
                {{ mode === 'add' ? 'เพิ่มแท็กให้สมาชิก' : 'นำแท็กออกจากสมาชิก' }}
              </DialogTitle>

              <!-- Member info -->
              <div v-if="member" class="mt-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg flex items-center gap-3">
                <img
                  :src="member.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(member.name)"
                  :alt="member.name"
                  class="w-10 h-10 rounded-full object-cover"
                />
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">{{ member.name }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ member.role }}</p>
                </div>
              </div>

              <!-- Bulk mode info -->
              <div v-if="bulkMode && selectedCount > 0" class="mt-3 p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg">
                <p class="text-sm text-indigo-700 dark:text-indigo-300">
                  <Icon icon="mdi:account-multiple" class="w-4 h-4 inline mr-1" />
                  เลือก {{ selectedCount }} สมาชิก
                </p>
              </div>

              <!-- Current tags (for single member) -->
              <div v-if="member && memberTags.length > 0" class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  แท็กปัจจุบัน
                </label>
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="tag in memberTags"
                    :key="tag.id"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium border"
                    :style="{ backgroundColor: tag.color + '20', color: tag.color, borderColor: tag.color }"
                  >
                    <Icon icon="mdi:tag" class="w-3 h-3" />
                    {{ tag.name }}
                  </span>
                </div>
              </div>

              <!-- Loading -->
              <div v-if="loadingTags" class="mt-4 text-center py-4">
                <Icon icon="mdi:loading" class="w-6 h-6 text-indigo-600 animate-spin" />
              </div>

              <!-- Tag selection -->
              <div v-else class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  {{ mode === 'add' ? 'เลือกแท็กที่ต้องการเพิ่ม' : 'เลือกแท็กที่ต้องการนำออก' }}
                </label>
                <div v-if="availableTags.length === 0" class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                  {{ mode === 'add' ? 'ไม่มีแท็กที่สามารถเพิ่มได้' : 'ไม่มีแท็กที่สามารถนำออกได้' }}
                </div>
                <div v-else class="space-y-2 max-h-60 overflow-y-auto">
                  <label
                    v-for="tag in availableTags"
                    :key="tag.id"
                    class="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                  >
                    <input
                      type="checkbox"
                      :value="tag.id"
                      v-model="selectedTagIds"
                      class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                    />
                    <span
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium border"
                      :style="{ backgroundColor: tag.color + '20', color: tag.color, borderColor: tag.color }"
                    >
                      <Icon icon="mdi:tag" class="w-3 h-3" />
                      {{ tag.name }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                      ({{ tag.member_count }} สมาชิก)
                    </span>
                  </label>
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
                  type="button"
                  class="min-h-[44px] sm:min-h-0 px-4 py-2 text-sm font-medium text-white rounded-lg disabled:opacity-50"
                  :class="mode === 'add' ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-red-600 hover:bg-red-700'"
                  :disabled="selectedTagIds.length === 0 || loading"
                  @click="handleSubmit"
                >
                  <Icon v-if="loading" icon="mdi:loading" class="w-4 h-4 animate-spin inline mr-1" />
                  {{ mode === 'add' ? 'เพิ่มแท็ก' : 'นำออก' }}
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
  DialogTitle,
} from '@headlessui/vue'

interface Tag {
  id: number
  name: string
  color: string
  member_count: number
}

interface Member {
  id: number
  name: string
  avatar?: string
  role?: string
}

interface Props {
  isOpen: boolean
  mode?: 'add' | 'remove'
  member?: Member | null
  bulkMode?: boolean
  selectedCount?: number
  academyId: number | null
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'add',
  member: null,
  bulkMode: false,
  selectedCount: 0,
})

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'submit', tagIds: number[]): void
}>()

const { $api } = useNuxtApp()

const loading = ref(false)
const loadingTags = ref(false)
const allTags = ref<Tag[]>([])
const memberTags = ref<Tag[]>([])
const selectedTagIds = ref<number[]>([])

const availableTags = computed(() => {
  if (props.mode === 'add') {
    // Show tags that member doesn't have
    const memberTagIds = memberTags.value.map((t) => t.id)
    return allTags.value.filter((t) => !memberTagIds.includes(t.id))
  } else {
    // Show only tags that member has
    return memberTags.value
  }
})

watch(
  () => props.isOpen,
  async (newVal) => {
    if (newVal) {
      selectedTagIds.value = []
      await fetchAllTags()
      if (props.member && !props.bulkMode) {
        await fetchMemberTags()
      }
    }
  }
)

async function fetchAllTags() {
  if (!props.academyId) return
  loadingTags.value = true
  try {
    const response = await $api.get(`/academies/${props.academyId}/member-tags`)
    if (response.data?.success) {
      allTags.value = response.data.tags
    }
  } catch (error) {
    console.error('Failed to fetch tags:', error)
  } finally {
    loadingTags.value = false
  }
}

async function fetchMemberTags() {
  if (!props.academyId || !props.member) return
  try {
    const response = await $api.get(`/academies/${props.academyId}/members/${props.member.id}/tags`)
    if (response.data?.success) {
      memberTags.value = response.data.tags
    }
  } catch (error) {
    console.error('Failed to fetch member tags:', error)
  }
}

function closeModal() {
  emit('close')
}

function handleSubmit() {
  if (selectedTagIds.value.length === 0) return
  emit('submit', selectedTagIds.value)
}
</script>
