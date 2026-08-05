<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CampaignCreateModal from '~/components/campaign/CampaignCreateModal.vue'

const props = defineProps<{
  scopeType: 'academy' | 'course'
  targetId: number | string
  targetName?: string
  // For a course inside an academy, pass it so the campaign links to the academy.
  academyId?: number | string | null
}>()

const emit = defineEmits<{ created: [unknown] }>()

const showModal = ref(false)
const modalType = ref<'advertisement' | 'support'>('advertisement')
const scopeNoun = computed(() => (props.scopeType === 'academy' ? 'โรงเรียนนี้' : 'รายวิชานี้'))

function open(type: 'advertisement' | 'support') {
  modalType.value = type
  showModal.value = true
}
</script>

<template>
  <section class="rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white p-4 shadow-sm dark:border-indigo-900/40 dark:from-indigo-950/40 dark:to-gray-800">
    <div class="flex items-start gap-3">
      <div class="rounded-xl bg-indigo-600 p-2 text-white"><Icon icon="solar:megaphone-bold-duotone" class="h-6 w-6" /></div>
      <div class="min-w-0 flex-1">
        <h3 class="font-bold text-gray-900 dark:text-white">ลงโฆษณาใน{{ scopeNoun }}</h3>
        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">โปรโมตสินค้าหรือบริการให้ผู้เรียน{{ targetName ? `ใน ${targetName}` : '' }}เห็น จ่ายตามจำนวนวิว</p>
        <button
          type="button"
          class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
          @click="open('advertisement')"
        >
          <Icon icon="heroicons:plus" class="h-4 w-4" />
          สร้างแคมเปญโฆษณา
        </button>
      </div>
    </div>

    <div class="mt-4 flex items-start gap-3 border-t border-indigo-100 pt-4 dark:border-indigo-900/40">
      <div class="rounded-xl bg-emerald-600 p-2 text-white"><Icon icon="solar:hand-heart-bold-duotone" class="h-6 w-6" /></div>
      <div class="min-w-0 flex-1">
        <h3 class="font-bold text-gray-900 dark:text-white">สนับสนุน{{ scopeNoun }}</h3>
        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">ให้ทุนสนับสนุนการเรียนรู้โดยตรง ไม่ต้องมีสื่อโฆษณา</p>
        <button
          type="button"
          class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
          @click="open('support')"
        >
          <Icon icon="solar:hand-heart-bold-duotone" class="h-4 w-4" />
          สนับสนุนด้วยเงิน
        </button>
      </div>
    </div>

    <CampaignCreateModal
      v-model:visible="showModal"
      :scope-type="scopeType"
      :target-id="targetId"
      :target-name="targetName"
      :academy-id="academyId"
      :default-type="modalType"
      @created="emit('created', $event)"
    />
  </section>
</template>
